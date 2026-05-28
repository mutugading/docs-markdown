"""
Multi-metric transformer for ETL worker — final version.

Drop-in addition to existing src/transformer.py. Handles modules where each
Oracle row contains multiple metrics that need to be expanded into multiple
FACT_METRIC rows (one per metric).

Convention:
- FM_METRIC_NAME uses UPPERCASE_SNAKE_CASE (GROSS_SALES, NETT_SALES, etc.)
- FM_METRIC_CATEGORY: VOLUME | VALUE | AVERAGE | RATIO | DERIVED
- FM_AGG_METHOD: SUM | WEIGHTED_AVG | AVG | LAST | RATIO

Usage:
    from .transformer_multi import expand_multi_metric, MODULE_CONFIG

    for oracle_row in oracle_cursor:
        for fact_tuple in expand_multi_metric(
            oracle_row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1
        ):
            tuples.append(fact_tuple)
"""
import logging
from decimal import Decimal
from datetime import datetime, timezone, date

logger = logging.getLogger(__name__)


# ============================================================================
# MODULE CONFIGURATIONS — registry of all multi-metric modules
# ============================================================================

MODULE_CONFIG = {
    'DELIVERY_MARGIN': {
        'fm_type': 'SALES',
        'sign_flip_groups': set(),  # SALES — no sign flip
        'metrics': [
            # (oracle_column_lc, metric_name_uc,  category,  agg_method, uom)
            ('quantity',        'QUANTITY',       'VOLUME',  'SUM',      'PCS'),
            ('gross_sales',     'GROSS_SALES',    'VALUE',   'SUM',      'USD'),
            ('selling_cost',    'SELLING_COST',   'VALUE',   'SUM',      'USD'),
            ('nett_sales',      'NETT_SALES',     'VALUE',   'SUM',      'USD'),
            ('cost_prod',       'COST_PROD',      'VALUE',   'SUM',      'USD'),
            ('margin',          'MARGIN',         'VALUE',   'SUM',      'USD'),
        ],
    },
    # Placeholder configs for Phase 2 modules
    'INVENTORY': {
        'fm_type': 'INV',
        'sign_flip_groups': set(),
        'metrics': [
            ('stock_qty',     'STOCK_QTY',     'VOLUME', 'LAST', 'PCS'),
            ('stock_value',   'STOCK_VALUE',   'VALUE',  'LAST', 'USD'),
            ('avg_cost',      'AVG_COST',      'AVERAGE','WEIGHTED_AVG', 'USD/PCS'),
            ('days_on_hand',  'DAYS_ON_HAND',  'DERIVED','LAST', 'DAYS'),
        ],
    },
    'PRODUCTION': {
        'fm_type': 'PROD',
        'sign_flip_groups': set(),
        'metrics': [
            ('output_qty',     'OUTPUT_QTY',     'VOLUME', 'SUM',          'PCS'),
            ('reject_qty',     'REJECT_QTY',     'VOLUME', 'SUM',          'PCS'),
            ('yield_pct',      'YIELD_PCT',      'RATIO',  'WEIGHTED_AVG', '%'),
            ('cost_per_unit',  'COST_PER_UNIT',  'AVERAGE','WEIGHTED_AVG', 'USD/PCS'),
        ],
    },
    'HR_OVERTIME': {
        'fm_type': 'HR',
        'sign_flip_groups': set(),
        'metrics': [
            ('headcount',      'HEADCOUNT',      'VOLUME', 'LAST',         'PERSON'),
            ('ot_hours',       'OT_HOURS',       'VOLUME', 'SUM',          'HOURS'),
            ('ot_cost',        'OT_COST',        'VALUE',  'SUM',          'USD'),
            ('productivity',   'PRODUCTIVITY',   'RATIO',  'WEIGHTED_AVG', '%'),
        ],
    },
}


def expand_multi_metric(oracle_row, module_config, source_id):
    """
    Expand a single Oracle row into multiple FACT_METRIC tuples.

    Args:
        oracle_row: dict with lowercase keys matching Oracle column names.
                    Required: group_1, periode_date OR periode_label.
                    Plus metric value keys defined in module_config['metrics'].
        module_config: entry from MODULE_CONFIG.
        source_id: DS_SOURCE_ID from DATA_SOURCE table.

    Yields:
        Tuple matching PG_UPSERT_FACT_METRIC column order (19 fields total).
    """
    fm_type = module_config['fm_type']
    sign_flip = module_config['sign_flip_groups']

    if not oracle_row.get('group_1'):
        logger.warning("Skipping row with missing GROUP_1: %s", oracle_row)
        return

    # Parse periode
    periode_date = oracle_row.get('periode_date')
    periode_label = oracle_row.get('periode_label')

    if isinstance(periode_date, str):
        try:
            periode_date = datetime.strptime(periode_date, '%Y-%m-%d').date()
        except ValueError:
            logger.warning("Invalid periode_date: %r", periode_date)
            return
    elif isinstance(periode_date, datetime):
        periode_date = periode_date.date()

    if not periode_date:
        logger.warning("Missing periode_date in row: %s", oracle_row)
        return

    if not periode_label:
        periode_label = periode_date.strftime('%Y%m')
    periode_label = str(periode_label)

    now = datetime.now(timezone.utc)
    group_2 = oracle_row.get('group_2')

    # Yield one tuple per metric
    for oracle_col, metric_name, category, agg_method, uom in module_config['metrics']:
        raw_value = oracle_row.get(oracle_col)
        if raw_value is None:
            continue

        try:
            if not isinstance(raw_value, Decimal):
                raw_value = Decimal(str(raw_value))
        except (ValueError, TypeError):
            logger.warning("Non-numeric value for %s: %r", metric_name, raw_value)
            continue

        # Sign flip (only for modules that need it — MIS/EBITDA)
        display_value = -raw_value if group_2 in sign_flip else raw_value

        yield (
            fm_type,
            oracle_row['group_1'],
            group_2,
            oracle_row.get('group_3'),
            int(oracle_row.get('group_1_order') or 1),
            int(oracle_row.get('group_2_order') or 1),
            int(oracle_row.get('group_3_order') or 1),
            'MONTHLY',
            periode_date,
            periode_label,
            raw_value,
            display_value,
            uom,
            'ACTUAL',
            source_id,
            metric_name,      # FM_METRIC_NAME (UPPERCASE)
            category,         # FM_METRIC_CATEGORY
            agg_method,       # FM_AGG_METHOD
            now,              # FM_LOADED_AT
            True,             # FM_IS_ACTIVE
        )


def expand_batch(oracle_rows, module_config, source_id):
    """
    Expand iterable of Oracle rows. Returns (tuples_list, stats_dict).
    """
    all_tuples = []
    stats = {'oracle_rows': 0, 'metric_rows': 0, 'skipped_rows': 0}
    for row in oracle_rows:
        stats['oracle_rows'] += 1
        before = len(all_tuples)
        all_tuples.extend(expand_multi_metric(row, module_config, source_id))
        if len(all_tuples) == before:
            stats['skipped_rows'] += 1
        else:
            stats['metric_rows'] += (len(all_tuples) - before)

    avg = stats['metric_rows'] / max(stats['oracle_rows'] - stats['skipped_rows'], 1)
    logger.info(
        "Expanded %d Oracle rows → %d metric rows (skipped: %d, avg %.1f metrics/row)",
        stats['oracle_rows'], stats['metric_rows'], stats['skipped_rows'], avg
    )
    return all_tuples, stats


# UPSERT SQL for v1.1 schema (includes FM_METRIC_NAME, FM_METRIC_CATEGORY, FM_AGG_METHOD)
PG_UPSERT_FACT_METRIC_V11 = """
INSERT INTO FACT_METRIC (
    FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
    FM_GROUP_1_ORDER, FM_GROUP_2_ORDER, FM_GROUP_3_ORDER,
    FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_PERIODE_LABEL,
    FM_VALUE, FM_DISPLAY_VALUE, FM_UOM, FM_SCENARIO,
    FM_SOURCE_ID, FM_METRIC_NAME, FM_METRIC_CATEGORY, FM_AGG_METHOD,
    FM_LOADED_AT, FM_IS_ACTIVE
) VALUES %s
ON CONFLICT (
    FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
    FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_METRIC_NAME, FM_SCENARIO, FM_DIMENSION_KEY
) DO UPDATE SET
    FM_GROUP_1_ORDER   = EXCLUDED.FM_GROUP_1_ORDER,
    FM_GROUP_2_ORDER   = EXCLUDED.FM_GROUP_2_ORDER,
    FM_GROUP_3_ORDER   = EXCLUDED.FM_GROUP_3_ORDER,
    FM_PERIODE_LABEL   = EXCLUDED.FM_PERIODE_LABEL,
    FM_VALUE           = EXCLUDED.FM_VALUE,
    FM_DISPLAY_VALUE   = EXCLUDED.FM_DISPLAY_VALUE,
    FM_UOM             = EXCLUDED.FM_UOM,
    FM_METRIC_CATEGORY = EXCLUDED.FM_METRIC_CATEGORY,
    FM_AGG_METHOD      = EXCLUDED.FM_AGG_METHOD,
    FM_SOURCE_ID       = EXCLUDED.FM_SOURCE_ID,
    FM_LOADED_AT       = EXCLUDED.FM_LOADED_AT,
    FM_IS_ACTIVE       = TRUE
"""
