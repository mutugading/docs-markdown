"""
ETL job execution logging — persists to ETL_JOB_LOG.

Pattern:
  1. start() — insert RUNNING row, returns log_id
  2. finish_success(rows) — UPDATE with SUCCESS + row count
  3. finish_failure(error_msg) — UPDATE with FAILED + error
"""
import logging

from .queries import PG_INSERT_LOG_START, PG_UPDATE_LOG_END


logger = logging.getLogger(__name__)


class JobLogger:
    """Records ETL execution in ETL_JOB_LOG.

    Uses a separate connection from the main writer because we want to log
    failures even when the main transaction rolled back.
    """

    def __init__(self, pg_connection, job_id, triggered_by="SCHEDULER"):
        self.conn = pg_connection
        self.job_id = job_id
        self.triggered_by = triggered_by
        self.log_id = None

    def start(self):
        """Insert RUNNING row, capture EJL_LOG_ID."""
        cur = self.conn.cursor()
        try:
            cur.execute(PG_INSERT_LOG_START, (self.job_id, self.triggered_by))
            self.log_id = cur.fetchone()[0]
            self.conn.commit()
            logger.info("ETL_JOB_LOG started: log_id=%d", self.log_id)
            return self.log_id
        except Exception:
            self.conn.rollback()
            logger.exception("Failed to insert ETL_JOB_LOG start record")
            raise
        finally:
            cur.close()

    def finish_success(self, rows_affected):
        self._finish("SUCCESS", rows_affected, None)

    def finish_failure(self, error_message):
        # Truncate error message — TEXT column is fine but extremely long
        # stack traces clutter the log; first 2000 chars is enough for triage
        msg = (error_message or "")[:2000]
        self._finish("FAILED", 0, msg)

    def _finish(self, status, rows, error):
        if self.log_id is None:
            logger.warning("finish() called but no log_id — start() not called?")
            return
        cur = self.conn.cursor()
        try:
            cur.execute(PG_UPDATE_LOG_END, (status, rows, error, self.log_id))
            self.conn.commit()
            logger.info("ETL_JOB_LOG closed: log_id=%d status=%s rows=%d",
                        self.log_id, status, rows)
        except Exception:
            self.conn.rollback()
            logger.exception("Failed to update ETL_JOB_LOG end record")
        finally:
            cur.close()
