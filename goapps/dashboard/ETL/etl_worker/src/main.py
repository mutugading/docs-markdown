"""
Main ETL orchestrator.

Entry point: `python -m src.main --job ETL_MIS_EBITDA`

Flow:
  1. Load config + setup logging
  2. Connect to Postgres (for job logging + writing)
  3. Start ETL_JOB_LOG row (status=RUNNING)
  4. Connect to Oracle, fetch query results
  5. Transform rows (sign-flip, periode conversion)
  6. UPSERT to FACT_METRIC in batches
  7. Refresh materialized views
  8. (Optional) Invalidate Redis cache
  9. Close log (status=SUCCESS, rows_affected=N)
 10. On any failure: log FAILED, send Slack alert, exit non-zero
"""
import argparse
import logging
import logging.config
import os
import sys
import time
import traceback
from pathlib import Path

import psycopg2

from .settings import load_config
from .oracle_reader import OracleReader
from .postgres_writer import PostgresWriter
from .transformer import transform_batch
from .job_logger import JobLogger
from .notifier import Notifier


def setup_logging(config):
    """Configure root logger with file + console handlers."""
    log_dir = Path(config.get("logging", {}).get("directory", "logs"))
    log_dir.mkdir(parents=True, exist_ok=True)
    log_level = config.get("logging", {}).get("level", "INFO").upper()

    from datetime import date
    log_file = log_dir / f"etl_{date.today().isoformat()}.log"

    logging.config.dictConfig({
        "version": 1,
        "disable_existing_loggers": False,
        "formatters": {
            "standard": {
                "format": "%(asctime)s [%(levelname)s] %(name)s — %(message)s",
                "datefmt": "%Y-%m-%d %H:%M:%S",
            },
        },
        "handlers": {
            "console": {
                "class": "logging.StreamHandler",
                "formatter": "standard",
                "level": log_level,
                "stream": "ext://sys.stdout",
            },
            "file": {
                "class": "logging.handlers.TimedRotatingFileHandler",
                "formatter": "standard",
                "level": log_level,
                "filename": str(log_file),
                "when": "midnight",
                "backupCount": config.get("logging", {}).get("rotation_days", 30),
                "encoding": "utf-8",
            },
        },
        "root": {
            "level": log_level,
            "handlers": ["console", "file"],
        },
    })


def run_etl(job_name, config, dry_run=False, triggered_by="SCHEDULER"):
    """Main ETL flow. Returns (status, rows_affected, error_message)."""
    logger = logging.getLogger("etl.main")
    start_time = time.time()

    # Look up job-specific config
    job_cfg = config.get("jobs", {}).get(job_name)
    if not job_cfg:
        return "FAILED", 0, f"Job '{job_name}' not defined in config"

    sign_flip_groups = set(job_cfg.get("sign_flip_groups", []))
    batch_size = job_cfg.get("upsert_batch_size", 500)
    refresh_mvs_flag = job_cfg.get("refresh_materialized_views", True)

    logger.info("=" * 70)
    logger.info("Starting ETL job: %s (dry_run=%s)", job_name, dry_run)
    logger.info("=" * 70)

    # ---- Connect to Postgres (early — we need it for job logging) ----
    pg = PostgresWriter(config["postgres"])
    notifier = Notifier(config.get("notification", {}))

    job_log = None
    rows_affected = 0
    try:
        pg.connect()
        source_id = pg.get_source_id(config["source_code"])
        job_id = pg.get_job_id(job_name)
        logger.info("Resolved source_id=%d, job_id=%d", source_id, job_id)

        # ---- Start log entry (separate connection? we share for simplicity) ----
        # Note: we use the same connection. If transaction needs rollback,
        # the log row stays because we commit() inside start().
        job_log = JobLogger(pg._conn, job_id, triggered_by)
        job_log.start()

        # ---- Connect to Oracle and fetch data ----
        oracle = OracleReader(config["oracle"], config.get("retry", {}))
        oracle.connect()
        try:
            rows_iter = oracle.fetch_mis_data()
            tuples, stats = transform_batch(rows_iter, sign_flip_groups, source_id)
        finally:
            oracle.close()

        logger.info("Transform stats: %s", stats)

        if not tuples:
            logger.warning("No valid rows to upsert — completing successfully with 0 rows")
            job_log.finish_success(0)
            duration = time.time() - start_time
            logger.info("ETL completed in %.1fs (no data)", duration)
            return "SUCCESS", 0, None

        # ---- Dry run exit before write ----
        if dry_run:
            logger.info("DRY RUN — skipping UPSERT. Would write %d rows.", len(tuples))
            logger.info("Sample first row: %s", tuples[0])
            logger.info("Sample last row: %s", tuples[-1])
            job_log.finish_success(0)
            return "SUCCESS", 0, "dry_run completed"

        # ---- UPSERT to Postgres ----
        rows_affected = pg.upsert_fact_metric(tuples, batch_size=batch_size)

        # ---- Refresh materialized views ----
        if refresh_mvs_flag:
            pg.refresh_materialized_views()

        # ---- Mark log SUCCESS ----
        job_log.finish_success(rows_affected)

        duration = time.time() - start_time
        logger.info("=" * 70)
        logger.info("ETL job '%s' SUCCESS in %.1fs — %d rows affected",
                    job_name, duration, rows_affected)
        logger.info("=" * 70)

        notifier.notify_success(job_name, rows_affected, duration)
        return "SUCCESS", rows_affected, None

    except Exception as e:
        error_msg = f"{type(e).__name__}: {e}\n{traceback.format_exc()}"
        logger.error("ETL job '%s' FAILED: %s", job_name, error_msg)

        # Try to mark log FAILED — best effort, may fail if connection is dead
        if job_log:
            try:
                job_log.finish_failure(error_msg)
            except Exception:
                logger.exception("Could not write failure to ETL_JOB_LOG")

        # Send Slack alert (won't raise even if it fails)
        log_id = job_log.log_id if job_log else None
        notifier.notify_failure(job_name, error_msg, log_id)

        return "FAILED", 0, error_msg

    finally:
        pg.close()


def main():
    parser = argparse.ArgumentParser(description="ETL Worker for Executive Dashboard")
    parser.add_argument("--job", required=True, help="Job name (e.g., ETL_MIS_EBITDA)")
    parser.add_argument("--config", default=None, help="Path to config.yaml")
    parser.add_argument("--dry-run", action="store_true", help="Read+transform but don't write")
    parser.add_argument("--triggered-by", default="SCHEDULER",
                        help="Identifier for who triggered (default SCHEDULER, use MANUAL for ad-hoc)")
    args = parser.parse_args()

    try:
        config = load_config(args.config)
    except (FileNotFoundError, ValueError) as e:
        # Logging not set up yet — print to stderr
        print(f"FATAL: Config error: {e}", file=sys.stderr)
        sys.exit(2)

    setup_logging(config)

    status, rows, error = run_etl(
        args.job, config, dry_run=args.dry_run, triggered_by=args.triggered_by
    )
    if status == "SUCCESS":
        sys.exit(0)
    else:
        sys.exit(1)


if __name__ == "__main__":
    main()
