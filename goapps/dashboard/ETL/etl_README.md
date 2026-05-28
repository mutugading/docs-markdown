# ETL Worker — Executive Dashboard

ETL pipeline yang membaca data EBITDA + Net Profit dari Oracle ERP 11g lalu UPSERT ke PostgreSQL warehouse.

## Project Structure

```
etl_worker/
├── README.md                       ← This file
├── requirements.txt                ← Python dependencies
├── config/
│   ├── config.example.yaml         ← Config template (copy to config.yaml)
│   └── logging.yaml                ← Logging configuration
├── src/
│   ├── __init__.py
│   ├── main.py                     ← Entry point (run via cron)
│   ├── settings.py                 ← Config loader + env override
│   ├── oracle_reader.py            ← Oracle connection + query execution
│   ├── transformer.py              ← Data transformation (sign flip, periode conversion)
│   ├── postgres_writer.py          ← UPSERT to FACT_METRIC + MV refresh
│   ├── job_logger.py               ← ETL_JOB_LOG persistence
│   ├── notifier.py                 ← Slack/email alerts on failure
│   └── queries.py                  ← SQL queries (Oracle source + Postgres targets)
├── tests/
│   ├── test_transformer.py
│   └── test_postgres_writer.py
├── scripts/
│   ├── run_etl.sh                  ← Cron-friendly wrapper
│   ├── test_connection.py          ← Standalone connection test
│   └── dry_run.py                  ← Preview without write
├── logs/                           ← Local log output (gitignored)
└── deploy/
    ├── crontab.txt                 ← Sample cron schedule
    └── systemd-timer.example       ← Alternative: systemd timer
```

## Quick Start

```bash
# 1. Setup virtualenv
python3.11 -m venv venv
source venv/bin/activate

# 2. Install dependencies
pip install -r requirements.txt

# Note: Untuk cx_Oracle, perlu Oracle Instant Client installed:
# - Download: https://www.oracle.com/database/technologies/instant-client/downloads.html
# - Set LD_LIBRARY_PATH ke folder instantclient

# 3. Config
cp config/config.example.yaml config/config.yaml
# Edit config/config.yaml dengan credential Anda

# 4. Test connection (tidak write data)
python scripts/test_connection.py

# 5. Dry run (read Oracle, transform, log — no write to Postgres)
python scripts/dry_run.py

# 6. Run ETL
python -m src.main --job ETL_MIS_EBITDA

# 7. Setup cron (production)
crontab deploy/crontab.txt
```

## Environment Variables (override config.yaml)

```bash
export ETL_ORACLE_HOST=oracle-erp.internal
export ETL_ORACLE_USER=etl_reader
export ETL_ORACLE_PASS=secret
export ETL_PG_HOST=localhost
export ETL_PG_USER=etl_writer
export ETL_PG_PASS=secret
export ETL_SLACK_WEBHOOK=https://hooks.slack.com/services/...
export ETL_LOG_LEVEL=INFO
```

Production: gunakan secret manager (Vault, AWS Secrets Manager, atau systemd EnvironmentFile dengan permission 600).

## Cron Schedule

ETL dijalankan 6x sehari setiap 4 jam. Lihat `deploy/crontab.txt`.

## Monitoring

- ETL run history: query table `ETL_JOB_LOG` di PostgreSQL
- File logs: `logs/etl_YYYY-MM-DD.log`
- Slack alerts: hanya saat job FAILED
- Health check endpoint (optional, Phase 2): expose `/health` untuk monitoring tool

## Troubleshooting

Lihat `docs/troubleshooting.md` (TBD) atau cek file log + ETL_JOB_LOG.EJL_ERROR_MESSAGE.
