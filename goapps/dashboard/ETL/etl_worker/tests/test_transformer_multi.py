"""
Tests for multi-metric transformer.

Run: pytest tests/test_transformer_multi.py -v
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import pytest
from decimal import Decimal
from datetime import date

from src.transformer_multi import (
    expand_multi_metric, expand_batch, MODULE_CONFIG,
)


# -------------------- expand_multi_metric --------------------

def _delivery_row(**kwargs):
    defaults = {
        'group_1': 'Export',
        'group_2': 'ACY',
        'group_3': 'ACY',
        'group_1_order': 1,
        'group_2_order': 2,
        'group_3_order': 1,
        'periode_date': '2026-05-01',
        'periode_label': '202605',
        'quantity':     9766.47,
        'gross_sales':  33526.34,
        'selling_cost': 730.83,
        'nett_sales':   32795.52,
        'cost_prod':    23343.97,
        'margin':       9451.55,
    }
    defaults.update(kwargs)
    return defaults


def test_expand_yields_one_tuple_per_metric():
    row = _delivery_row()
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    assert len(tuples) == 6  # 6 metrics: QUANTITY + 5 VALUE


def test_expand_metric_names_uppercase():
    row = _delivery_row()
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    names = [t[15] for t in tuples]  # FM_METRIC_NAME is index 15
    assert 'QUANTITY' in names
    assert 'GROSS_SALES' in names
    assert 'NETT_SALES' in names
    assert 'MARGIN' in names


def test_expand_quantity_has_volume_category():
    row = _delivery_row()
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    qty_tuple = next(t for t in tuples if t[15] == 'QUANTITY')
    assert qty_tuple[16] == 'VOLUME'   # FM_METRIC_CATEGORY
    assert qty_tuple[12] == 'PCS'      # FM_UOM


def test_expand_currency_metrics_have_value_category():
    row = _delivery_row()
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    for t in tuples:
        metric = t[15]
        if metric in {'GROSS_SALES', 'NETT_SALES', 'SELLING_COST', 'COST_PROD', 'MARGIN'}:
            assert t[16] == 'VALUE'
            assert t[12] == 'USD'


def test_expand_sales_no_sign_flip():
    """SALES module — display_value == raw_value (no flipping)."""
    row = _delivery_row(margin=9451.55)
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    margin_tuple = next(t for t in tuples if t[15] == 'MARGIN')
    assert margin_tuple[10] == Decimal('9451.55')   # raw
    assert margin_tuple[11] == Decimal('9451.55')   # display (same)


def test_expand_skips_null_value_metric():
    """If a metric column is None, that specific metric is skipped (not the whole row)."""
    row = _delivery_row(selling_cost=None)
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    names = [t[15] for t in tuples]
    assert 'SELLING_COST' not in names
    assert 'GROSS_SALES' in names   # other metrics still present
    assert len(tuples) == 5


def test_expand_skips_row_with_missing_group_1():
    row = _delivery_row(group_1=None)
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    assert tuples == []


def test_expand_parses_date_string():
    row = _delivery_row(periode_date='2026-05-01')
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1))
    assert tuples[0][8] == date(2026, 5, 1)   # FM_PERIODE_DATE


def test_expand_propagates_source_id():
    row = _delivery_row()
    tuples = list(expand_multi_metric(row, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=42))
    assert all(t[14] == 42 for t in tuples)   # FM_SOURCE_ID


# -------------------- expand_batch --------------------

def test_expand_batch_stats():
    rows = [
        _delivery_row(),
        _delivery_row(group_1=None),   # skipped
        _delivery_row(margin=None),    # one metric skipped, row OK
    ]
    tuples, stats = expand_batch(rows, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1)
    assert stats['oracle_rows'] == 3
    assert stats['skipped_rows'] == 1
    assert stats['metric_rows'] == 6 + 5  # 6 from first, 5 from third (margin null)
    assert len(tuples) == 11


# -------------------- E2E validation against user's Excel --------------------

def test_e2e_matches_seed_total():
    """
    Process all 519 delivery margin rows from Excel and verify totals match
    expected May 2026 numbers.
    """
    import pandas as pd
    df = pd.read_excel('/mnt/user-data/uploads/DELIVERY_MARGIN_DASHBOARD.xlsx')
    df.columns = df.columns.str.strip()

    # Convert user's long-format Excel back to wide-format Oracle-like rows
    # (since user already converted, we re-pivot for ETL simulation)
    metric_map = {
        'Quantity': 'quantity', 'Gross Sales': 'gross_sales',
        'Nett Sales': 'nett_sales', 'Selling Cost': 'selling_cost',
        'Production Cost': 'cost_prod', 'Margin': 'margin',
    }
    df['metric_lc'] = df['FM_METRIC_NAME'].map(metric_map)

    # Pivot to wide: one row per (dim combo + period), columns = metrics
    df_wide = df.pivot_table(
        index=['FM_GROUP_1', 'FM_GROUP_2', 'FM_GROUP_3', 'FM_PERIODE_LABEL', 'FM_PERIODE_DATE',
               'FM_GROUP_1_ORDER', 'FM_GROUP_2_ORDER'],
        columns='metric_lc',
        values='FM_VALUE',
        aggfunc='first',
    ).reset_index()

    # Build Oracle-like row dicts
    oracle_rows = []
    for _, r in df_wide.iterrows():
        oracle_rows.append({
            'group_1': r['FM_GROUP_1'],
            'group_2': r['FM_GROUP_2'],
            'group_3': r['FM_GROUP_3'],
            'group_1_order': int(r['FM_GROUP_1_ORDER']),
            'group_2_order': int(r['FM_GROUP_2_ORDER']),
            'group_3_order': 1,
            'periode_date': r['FM_PERIODE_DATE'].strftime('%Y-%m-%d') if hasattr(r['FM_PERIODE_DATE'], 'strftime') else str(r['FM_PERIODE_DATE'])[:10],
            'periode_label': str(r['FM_PERIODE_LABEL']),
            'quantity':     r.get('quantity'),
            'gross_sales':  r.get('gross_sales'),
            'nett_sales':   r.get('nett_sales'),
            'selling_cost': r.get('selling_cost'),
            'cost_prod':    r.get('cost_prod'),
            'margin':       r.get('margin'),
        })

    tuples, stats = expand_batch(oracle_rows, MODULE_CONFIG['DELIVERY_MARGIN'], source_id=1)
    print(f"\nProcessed {stats['oracle_rows']} Oracle rows → {stats['metric_rows']} metric rows")
    assert stats['oracle_rows'] == 519, f"Expected 519 unique dim combos, got {stats['oracle_rows']}"

    # Verify May 2026 total margin: expected 303,439.29
    may_margin = sum(
        t[10] for t in tuples
        if t[15] == 'MARGIN' and t[9] == '202605'
    )
    expected = Decimal('303439.29')
    diff = abs(may_margin - expected)
    print(f"May 2026 Margin: {may_margin:,.2f} (expected: {expected:,.2f}, diff: {diff})")
    assert diff < Decimal('0.05'), f"Margin mismatch: got {may_margin}, expected {expected}"

    print("E2E test PASSED — ETL transformer matches seed SQL expectations")


if __name__ == "__main__":
    # Run E2E manually
    test_e2e_matches_seed_total()
