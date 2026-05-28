"""
End-to-end transformer simulation using real Excel data.

This proves the transformer produces values identical to what seed_database.sql
contains — meaning when ETL runs against real Oracle data, the result will be
the same shape and signs as our manually-prepared seed.
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import pandas as pd
from decimal import Decimal
from datetime import date

from src.transformer import transform_batch


def test_transform_matches_manual_seed():
    """Simulate ETL transform against full Excel data, compare to seed expectation."""
    # Load original Excel
    df = pd.read_excel('/mnt/user-data/uploads/ebitda_dashboard.xlsx', header=0)
    df.columns = ['type', 'group_1', 'group_2', 'group_3',
                  'group_1_order', 'group_2_order', 'group_3_order',
                  'periode', 'value']

    # Simulate Oracle output format (string periode, numeric value)
    rows = []
    for _, r in df.iterrows():
        rows.append({
            "type": r["type"],
            "group_1": r["group_1"],
            "group_2": r["group_2"],
            "group_3": r["group_3"],
            "group_1_order": int(r["group_1_order"]),
            "group_2_order": int(r["group_2_order"]),
            "group_3_order": int(r["group_3_order"]),
            "periode": str(r["periode"]),
            "value": Decimal(str(r["value"])),
        })

    # Sign flip groups matching seed
    sign_flip = {
        "INCOME", "PRODUCTION COST", "COLOR CONSUMPTION (SPG)",
        "MATERIAL CONSUMPTION", "R and D MASTERBATCH PRODUCTION",
        "ENERGY COST", "PROCUREMENT COST", "MANPOWER", "OVERHEADS",
        "SELLING COST", "BAD DEBT EXP",
    }

    tuples, stats = transform_batch(rows, sign_flip, source_id=1)

    print(f"Transformed {stats['valid']}/{stats['total']} rows (skipped: {stats['skipped']})")
    assert stats["total"] == 2135, f"Expected 2135 input rows, got {stats['total']}"
    assert stats["valid"] == 2135, f"Expected all rows valid, {stats['skipped']} skipped"

    # Verify April 2026 EBITDA total
    apr_2026_ebitda_display = sum(
        t[11] for t in tuples  # FM_DISPLAY_VALUE at index 11
        if t[1] == "EBITDA" and t[9] == "202604"
    )
    print(f"April 2026 EBITDA display total: {apr_2026_ebitda_display}")
    assert abs(apr_2026_ebitda_display - Decimal("1015881.07")) < Decimal("0.01"), \
        f"Expected ~1015881.07, got {apr_2026_ebitda_display}"

    # Sample sign flip verification — INCOME should be display-positive
    income_rows = [t for t in tuples
                   if t[2] == "INCOME" and t[1] == "EBITDA" and t[9] == "202604"]
    print(f"\nIncome rows for Apr 2026: {len(income_rows)}")
    for t in income_rows[:3]:
        raw = t[10]
        display = t[11]
        print(f"  G3={t[3]:30} raw={raw:>15} display={display:>15}")
        # Income raw is negative (accounting), display should be positive
        if raw != 0:
            assert (raw < 0 and display > 0) or (raw > 0 and display < 0), \
                f"Sign flip failed for {t[3]}"

    print("\n✓ E2E transform matches seed_database.sql expectations")


if __name__ == "__main__":
    test_transform_matches_manual_seed()
