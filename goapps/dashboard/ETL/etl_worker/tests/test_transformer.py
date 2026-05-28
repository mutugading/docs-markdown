"""
Unit tests for transformer module.

Run: pytest tests/test_transformer.py -v
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import pytest
from datetime import date
from decimal import Decimal

from src.transformer import (
    periode_to_date,
    compute_display_value,
    transform_row,
    transform_batch,
)


# ----- periode_to_date -----

def test_periode_to_date_basic():
    assert periode_to_date("202604") == date(2026, 4, 1)
    assert periode_to_date("202301") == date(2023, 1, 1)
    assert periode_to_date("202512") == date(2025, 12, 1)


def test_periode_to_date_invalid_length():
    with pytest.raises(ValueError):
        periode_to_date("20260")
    with pytest.raises(ValueError):
        periode_to_date("2026044")


def test_periode_to_date_invalid_month():
    with pytest.raises(ValueError):
        periode_to_date("202613")
    with pytest.raises(ValueError):
        periode_to_date("202600")


def test_periode_to_date_none_raises():
    with pytest.raises(ValueError):
        periode_to_date(None)


# ----- compute_display_value -----

SIGN_FLIP = {"INCOME", "PRODUCTION COST", "MANPOWER"}


def test_display_value_flip_income():
    # Income stored as -5,000,000 in accounting → display +5,000,000
    result = compute_display_value("INCOME", Decimal("-5000000"), SIGN_FLIP)
    assert result == Decimal("5000000")


def test_display_value_flip_cost():
    # Cost stored as +2,000,000 → display -2,000,000
    result = compute_display_value("PRODUCTION COST", Decimal("2000000"), SIGN_FLIP)
    assert result == Decimal("-2000000")


def test_display_value_no_flip_for_unknown_group():
    result = compute_display_value("OTHER", Decimal("100"), SIGN_FLIP)
    assert result == Decimal("100")


def test_display_value_no_flip_for_net_profit_component():
    # NET PROFIT group_2 is not in sign_flip list — keep as-is
    result = compute_display_value("Net Profit Component", Decimal("123.45"), SIGN_FLIP)
    assert result == Decimal("123.45")


def test_display_value_none_input():
    assert compute_display_value("INCOME", None, SIGN_FLIP) is None


def test_display_value_accepts_float():
    # Should convert float to Decimal
    result = compute_display_value("INCOME", -1000.50, SIGN_FLIP)
    assert result == Decimal("1000.5")


# ----- transform_row -----

def _row(**kwargs):
    """Helper: build a row dict with defaults."""
    defaults = {
        "type": "MIS",
        "group_1": "EBITDA",
        "group_2": "INCOME",
        "group_3": "LOCAL SALES",
        "group_1_order": 1,
        "group_2_order": 1,
        "group_3_order": 1,
        "periode": "202604",
        "value": Decimal("-1000000"),
    }
    defaults.update(kwargs)
    return defaults


def test_transform_row_happy_path():
    row = _row()
    result = transform_row(row, SIGN_FLIP, source_id=1)
    assert result is not None
    # Tuple position assertions
    assert result[0] == "MIS"          # FM_TYPE
    assert result[1] == "EBITDA"       # FM_GROUP_1
    assert result[2] == "INCOME"       # FM_GROUP_2
    assert result[3] == "LOCAL SALES"  # FM_GROUP_3
    assert result[7] == "MONTHLY"      # FM_PERIODE_GRAIN
    assert result[8] == date(2026, 4, 1)  # FM_PERIODE_DATE
    assert result[9] == "202604"       # FM_PERIODE_LABEL
    assert result[10] == Decimal("-1000000")  # FM_VALUE (raw)
    assert result[11] == Decimal("1000000")   # FM_DISPLAY_VALUE (flipped)
    assert result[12] == "USD"         # FM_UOM
    assert result[13] == "ACTUAL"      # FM_SCENARIO
    assert result[14] == 1             # FM_SOURCE_ID


def test_transform_row_skips_missing_type():
    row = _row(type=None)
    assert transform_row(row, SIGN_FLIP, source_id=1) is None


def test_transform_row_skips_missing_group_1():
    row = _row(group_1=None)
    assert transform_row(row, SIGN_FLIP, source_id=1) is None


def test_transform_row_skips_null_value():
    row = _row(value=None)
    assert transform_row(row, SIGN_FLIP, source_id=1) is None


def test_transform_row_skips_invalid_periode():
    row = _row(periode="2026")
    assert transform_row(row, SIGN_FLIP, source_id=1) is None


def test_transform_row_optional_group_2_3():
    # GROUP_2 and GROUP_3 are nullable
    row = _row(group_2=None, group_3=None)
    result = transform_row(row, SIGN_FLIP, source_id=1)
    assert result is not None
    assert result[2] is None
    assert result[3] is None


# ----- transform_batch -----

def test_transform_batch_counts():
    rows = [
        _row(group_1="EBITDA"),
        _row(group_1=None),  # invalid
        _row(group_1="NET PROFIT"),
        _row(periode="invalid"),  # invalid
    ]
    tuples, stats = transform_batch(rows, SIGN_FLIP, source_id=1)
    assert stats["total"] == 4
    assert stats["valid"] == 2
    assert stats["skipped"] == 2
    assert len(tuples) == 2


def test_transform_batch_empty():
    tuples, stats = transform_batch([], SIGN_FLIP, source_id=1)
    assert tuples == []
    assert stats == {"total": 0, "valid": 0, "skipped": 0}
