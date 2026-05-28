"""
Oracle connection and query execution.

Handles connection lifecycle, retries on transient failures, and yields
rows from the EBITDA query as plain dict (no Oracle-specific objects exposed
to downstream code).
"""
import time
import logging
from contextlib import contextmanager

# cx_Oracle is the standard Oracle client. For Oracle 11g/12c on modern Python:
# - cx_Oracle (legacy, requires Instant Client) — used here for stability
# - python-oracledb (newer, thin mode without client) — alternative
import cx_Oracle

from .queries import ORACLE_MIS_QUERY


logger = logging.getLogger(__name__)


class OracleReader:
    """Thin wrapper around cx_Oracle with retry + clean iteration."""

    def __init__(self, oracle_config, retry_config):
        self.cfg = oracle_config
        self.retry = retry_config
        self._conn = None

    def connect(self):
        """Establish connection with retry on transient failures."""
        dsn = cx_Oracle.makedsn(
            self.cfg["host"],
            self.cfg.get("port", 1521),
            service_name=self.cfg["service_name"],
        )
        attempts = self.retry.get("max_attempts", 3)
        delay = self.retry.get("initial_delay_sec", 10)
        multiplier = self.retry.get("backoff_multiplier", 3)

        last_error = None
        for attempt in range(1, attempts + 1):
            try:
                logger.info(
                    "Connecting to Oracle %s:%s/%s (attempt %d/%d)",
                    self.cfg["host"], self.cfg.get("port", 1521),
                    self.cfg["service_name"], attempt, attempts,
                )
                self._conn = cx_Oracle.connect(
                    user=self.cfg["user"],
                    password=self.cfg["password"],
                    dsn=dsn,
                    encoding=self.cfg.get("encoding", "UTF-8"),
                )
                logger.info("Oracle connection established")
                return
            except cx_Oracle.DatabaseError as e:
                last_error = e
                error_obj = e.args[0]
                code = getattr(error_obj, "code", None)
                # Don't retry on auth failures or permission errors — fail fast
                if code in (1017, 1031, 28000):  # bad credential / locked / restricted
                    logger.error("Auth/permission failure (ORA-%s) — not retrying", code)
                    raise
                logger.warning(
                    "Oracle connect attempt %d failed: %s — retrying in %ds",
                    attempt, e, delay,
                )
                if attempt < attempts:
                    time.sleep(delay)
                    delay *= multiplier
        raise ConnectionError(f"Oracle connection failed after {attempts} attempts: {last_error}")

    def close(self):
        if self._conn:
            try:
                self._conn.close()
            except Exception as e:
                logger.warning("Error closing Oracle connection: %s", e)
            self._conn = None

    @contextmanager
    def cursor(self):
        if not self._conn:
            raise RuntimeError("Not connected — call connect() first")
        cur = self._conn.cursor()
        try:
            cur.arraysize = self.cfg.get("fetch_arraysize", 1000)
            yield cur
        finally:
            cur.close()

    def fetch_mis_data(self):
        """
        Execute the EBITDA/MIS query and yield rows as dicts.

        Yields dict with keys (lowercased):
            type, group_1, group_2, group_3,
            group_1_order, group_2_order, group_3_order,
            periode, value
        """
        with self.cursor() as cur:
            logger.info("Executing MIS query...")
            cur.execute(ORACLE_MIS_QUERY)
            cols = [d[0].lower() for d in cur.description]
            logger.debug("Result columns: %s", cols)

            row_count = 0
            while True:
                rows = cur.fetchmany(self.cfg.get("fetch_arraysize", 1000))
                if not rows:
                    break
                for row in rows:
                    row_count += 1
                    rec = dict(zip(cols, row))
                    # Normalize column names — Oracle returns MFMG_TYPE,
                    # we want clean shorter keys
                    yield {
                        "type": rec.get("mfmg_type"),
                        "group_1": rec.get("mfmg_group_1"),
                        "group_2": rec.get("mfmg_group_2"),
                        "group_3": rec.get("mfmg_group_3"),
                        "group_1_order": rec.get("mfmg_group_1_ord"),
                        "group_2_order": rec.get("mfmg_group_2_ord"),
                        "group_3_order": rec.get("mfmg_group_3_ord"),
                        "periode": str(rec.get("periode")) if rec.get("periode") else None,
                        "value": rec.get("curr_period"),
                    }
            logger.info("Oracle query returned %d rows", row_count)
