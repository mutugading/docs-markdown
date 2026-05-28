"""
Dry run — execute Oracle query and transform, but do NOT write to Postgres.

Useful for:
  - Verifying query returns expected data
  - Testing sign-flip logic without changing FACT_METRIC
  - Sanity check before first production run

Usage:
    python scripts/dry_run.py [--job ETL_MIS_EBITDA]
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import argparse

from src.settings import load_config
from src.main import setup_logging, run_etl


parser = argparse.ArgumentParser()
parser.add_argument("--job", default="ETL_MIS_EBITDA")
args = parser.parse_args()

config = load_config()
setup_logging(config)

print("\n" + "=" * 60)
print(f"DRY RUN: {args.job}")
print("=" * 60 + "\n")

status, rows, msg = run_etl(args.job, config, dry_run=True, triggered_by="DRY_RUN")
print(f"\nResult: status={status}, rows={rows}, message={msg}")
sys.exit(0 if status == "SUCCESS" else 1)
