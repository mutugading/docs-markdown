"""
PostgreSQL connection and writer.

Performs UPSERT into FACT_METRIC with batched execute_values for performance.
Also handles materialized view refresh and source_id resolution.
"""
import logging
from contextlib import contextmanager

import psycopg2
from psycopg2.extras import execute_values

from .queries import (
    PG_GET_SOURCE_ID, PG_GET_JOB_ID,
    PG_UPSERT_FACT_METRIC, PG_REFRESH_MVS,
)


logger = logging.getLogger(__name__)


class PostgresWriter:
    def __init__(self, pg_config):
        self.cfg = pg_config
        self._conn = None

    def connect(self):
        logger.info(
            "Connecting to Postgres %s:%s/%s",
            self.cfg["host"], self.cfg.get("port", 5432), self.cfg["database"],
        )
        self._conn = psycopg2.connect(
            host=self.cfg["host"],
            port=self.cfg.get("port", 5432),
            dbname=self.cfg["database"],
            user=self.cfg["user"],
            password=self.cfg["password"],
            connect_timeout=self.cfg.get("connect_timeout_sec", 10),
            options=f"-c statement_timeout={self.cfg.get('statement_timeout_sec', 600) * 1000}",
        )
        # We control commits explicitly — autocommit off
        self._conn.autocommit = False
        logger.info("Postgres connection established")

    def close(self):
        if self._conn:
            try:
                self._conn.close()
            except Exception as e:
                logger.warning("Error closing Postgres connection: %s", e)
            self._conn = None

    @contextmanager
    def cursor(self):
        if not self._conn:
            raise RuntimeError("Not connected — call connect() first")
        cur = self._conn.cursor()
        try:
            yield cur
        finally:
            cur.close()

    def get_source_id(self, source_code):
        """Resolve DS_SOURCE_CODE → DS_SOURCE_ID."""
        with self.cursor() as cur:
            cur.execute(PG_GET_SOURCE_ID, (source_code,))
            row = cur.fetchone()
            if not row:
                raise ValueError(
                    f"Source code '{source_code}' not found in DATA_SOURCE table. "
                    "Did you run seed_database.sql?"
                )
            return row[0]

    def get_job_id(self, job_name):
        """Resolve job name → ETL_JOB.EJ_JOB_ID."""
        with self.cursor() as cur:
            cur.execute(PG_GET_JOB_ID, (job_name,))
            row = cur.fetchone()
            if not row:
                raise ValueError(
                    f"Job '{job_name}' not found in ETL_JOB table. "
                    "Add row to ETL_JOB before running."
                )
            return row[0]

    def upsert_fact_metric(self, tuples, batch_size=500):
        """
        Bulk UPSERT into FACT_METRIC.

        Wraps in transaction — if any batch fails, full rollback.
        Returns total rows affected.
        """
        if not tuples:
            logger.info("No rows to upsert")
            return 0

        total = 0
        try:
            with self.cursor() as cur:
                # execute_values handles batching internally and is much faster
                # than executemany (~10x for large inserts)
                for i in range(0, len(tuples), batch_size):
                    batch = tuples[i : i + batch_size]
                    execute_values(
                        cur, PG_UPSERT_FACT_METRIC, batch,
                        template=None,  # default tuple expansion
                        page_size=batch_size,
                    )
                    total += len(batch)
                    logger.debug("Upserted batch %d–%d (%d rows so far)",
                                 i, i + len(batch), total)
            self._conn.commit()
            logger.info("UPSERT committed: %d rows affected", total)
            return total
        except Exception:
            self._conn.rollback()
            logger.exception("UPSERT failed, rolled back")
            raise

    def refresh_materialized_views(self):
        """
        Refresh MV_METRIC_G1 and MV_METRIC_G2 via REFRESH_DASHBOARD_MVS().

        Uses CONCURRENTLY internally (defined in function) so reads are not
        blocked during refresh.
        """
        logger.info("Refreshing materialized views...")
        try:
            with self.cursor() as cur:
                cur.execute(PG_REFRESH_MVS)
            self._conn.commit()
            logger.info("Materialized views refreshed")
        except Exception:
            self._conn.rollback()
            logger.exception("MV refresh failed")
            raise
