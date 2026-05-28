"""
Standalone connection test — verify Oracle + Postgres connectivity before running ETL.

Usage:
    python scripts/test_connection.py
"""
import sys
import os
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import logging
from src.settings import load_config
from src.oracle_reader import OracleReader
from src.postgres_writer import PostgresWriter

logging.basicConfig(level=logging.INFO, format="%(asctime)s [%(levelname)s] %(message)s")
log = logging.getLogger("test")

print("=" * 60)
print("Connection Test — Executive Dashboard ETL")
print("=" * 60)

try:
    config = load_config()
    print(f"✓ Config loaded from {os.environ.get('CONFIG_PATH', 'default')}")
except Exception as e:
    print(f"✗ Config load failed: {e}")
    sys.exit(1)

# Test Oracle
print("\n[1/2] Testing Oracle connection...")
try:
    oracle = OracleReader(config["oracle"], config.get("retry", {}))
    oracle.connect()
    with oracle.cursor() as cur:
        cur.execute("SELECT TO_CHAR(SYSDATE, 'YYYY-MM-DD HH24:MI:SS') FROM dual")
        result = cur.fetchone()
        print(f"  ✓ Oracle connection OK — server time: {result[0]}")
    oracle.close()
except Exception as e:
    print(f"  ✗ Oracle connection FAILED: {e}")
    sys.exit(1)

# Test Postgres
print("\n[2/2] Testing Postgres connection...")
try:
    pg = PostgresWriter(config["postgres"])
    pg.connect()
    with pg.cursor() as cur:
        cur.execute("SELECT version()")
        version = cur.fetchone()[0]
        print(f"  ✓ Postgres connection OK")
        print(f"    Version: {version[:80]}")

        cur.execute("SELECT COUNT(*) FROM FACT_METRIC")
        count = cur.fetchone()[0]
        print(f"    FACT_METRIC current row count: {count:,}")

        cur.execute(
            "SELECT DS_SOURCE_ID, DS_SOURCE_CODE FROM DATA_SOURCE WHERE DS_SOURCE_CODE = %s",
            (config["source_code"],)
        )
        row = cur.fetchone()
        if row:
            print(f"    Source resolved: {config['source_code']} → ID={row[0]}")
        else:
            print(f"  ⚠ Source code '{config['source_code']}' not found in DATA_SOURCE")
    pg.close()
except Exception as e:
    print(f"  ✗ Postgres connection FAILED: {e}")
    sys.exit(1)

print("\n" + "=" * 60)
print("All connection tests PASSED ✓")
print("=" * 60)
