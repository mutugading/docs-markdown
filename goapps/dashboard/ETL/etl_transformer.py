"""
Data transformation logic.

Pure functions — no side effects, easy to unit test.

Responsibilities:
  - Sign-flip logic for FM_DISPLAY_VALUE (accounting convention → display friendly)
  - Periode label (YYYYMM) → date conversion (first of month)
  - Field name mapping & validation
  - Skip malformed rows with logging
"""
import logging
from datetime import date
from decimal import Decimal, InvalidOperation


logger = logging.getLogger(__name__)


def periode_to_date(periode_label):
    """
    Convert 'YYYYMM' string → date object (1st of month).

    >>> periode_to_date('202604')
    datetime.date(2026, 4, 1)
    """
    if not periode_label or len(str(periode_label)) != 6:
        raise ValueError(f"Invalid periode format: {periode_label!r} (expected YYYYMM)")
    s = str(periode_label)
    try:
        year = int(s[:4])
        month = int(s[4:6])
    except ValueError:
        raise ValueError(f"Non-numeric periode: {periode_label!r}")
    if not (1 <= month <= 12):
        raise ValueError(f"Invalid month in periode: {periode_label!r}")
    if not (2000 <= year <= 2100):
        # Sanity check; loosen if needed
        raise ValueError(f"Year out of expected range: {year}")
    return date(year, month, 1)


def compute_display_value(group_2, raw_value, sign_flip_groups):
    """
    Apply sign-flip rule for FM_DISPLAY_VALUE.

    Income & cost categories are stored in accounting convention (income negative,
    cost positive) in source. For display, we flip them so income shows positive
    and cost shows negative.

    Args:
        group_2: GROUP_2 value from row (e.g., 'INCOME', 'PRODUCTION COST')
        raw_value: original signed value
        sign_flip_groups: list of GROUP_2 names that should be sign-flipped

    Returns:
        Decimal (sign-flipped if applicable, otherwise unchanged)
    """
    if raw_value is None:
        return None
    if not isinstance(raw_value, Decimal):
        try:
            raw_value = Decimal(str(raw_value))
        except (InvalidOperation, ValueError):
            raise ValueError(f"Non-numeric value: {raw_value!r}")
    if group_2 in sign_flip_groups:
        return -raw_value
    return raw_value


def transform_row(row, sign_flip_groups, source_id):
    """
    Transform a single Oracle row into a tuple ready for FACT_METRIC INSERT.

    Returns tuple matching the order in PG_UPSERT_FACT_METRIC VALUES clause:
      (FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
       FM_GROUP_1_ORDER, FM_GROUP_2_ORDER, FM_GROUP_3_ORDER,
       FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_PERIODE_LABEL,
       FM_VALUE, FM_DISPLAY_VALUE, FM_UOM, FM_SCENARIO,
       FM_SOURCE_ID, FM_LOADED_AT, FM_IS_ACTIVE)

    Returns None for rows that should be skipped (with warning logged).
    FM_LOADED_AT is left as NOW() — set in DB via DEFAULT.
    """
    try:
        # Mandatory fields
        if not row.get("type") or not row.get("group_1"):
            logger.warning("Skipping row with missing TYPE or GROUP_1: %s", row)
            return None
        if row.get("value") is None:
            logger.warning(
                "Skipping row with NULL value (type=%s, g1=%s, g2=%s, periode=%s)",
                row.get("type"), row.get("group_1"), row.get("group_2"), row.get("periode"),
            )
            return None

        periode_date = periode_to_date(row["periode"])
        raw_value = row["value"]
        if not isinstance(raw_value, Decimal):
            raw_value = Decimal(str(raw_value))
        display_value = compute_display_value(
            row.get("group_2"), raw_value, sign_flip_groups
        )

        # Timestamp for FM_LOADED_AT — explicit Python timestamp for consistency
        # across all rows in this batch (DB DEFAULT NOW() would differ row-by-row).
        from datetime import datetime, timezone
        now = datetime.now(timezone.utc)

        return (
            row["type"],
            row["group_1"],
            row.get("group_2"),
            row.get("group_3"),
            _int_or_none(row.get("group_1_order")),
            _int_or_none(row.get("group_2_order")),
            _int_or_none(row.get("group_3_order")),
            "MONTHLY",
            periode_date,
            str(row["periode"]),
            raw_value,
            display_value,
            "USD",
            "ACTUAL",
            source_id,
            now,
            True,
        )
    except (ValueError, TypeError) as e:
        logger.warning("Skipping malformed row: %s — %s", row, e)
        return None


def _int_or_none(v):
    if v is None:
        return None
    try:
        return int(v)
    except (ValueError, TypeError):
        return None


def transform_batch(rows_iter, sign_flip_groups, source_id):
    """
    Generator that yields transformed tuples, skipping malformed ones.

    Tracks counts: total in, valid out, skipped.
    Returns a tuple of (tuples_list, stats_dict).
    """
    out = []
    stats = {"total": 0, "valid": 0, "skipped": 0}
    for row in rows_iter:
        stats["total"] += 1
        tpl = transform_row(row, sign_flip_groups, source_id)
        if tpl is None:
            stats["skipped"] += 1
        else:
            out.append(tpl)
            stats["valid"] += 1
    return out, stats
