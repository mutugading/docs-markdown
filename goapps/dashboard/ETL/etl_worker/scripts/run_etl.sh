#!/usr/bin/env bash
# =====================================================================
# Cron-friendly ETL runner
# =====================================================================
# Why a wrapper?
#   - Cron has a sparse environment — we need to source .profile / venv
#   - We want a single stderr/stdout file with timestamps
#   - We want a lock file to prevent overlapping runs
#
# Setup:
#   chmod +x scripts/run_etl.sh
#   crontab -e   # then paste from deploy/crontab.txt
# =====================================================================

set -euo pipefail

# Project root (adjust if you install elsewhere)
PROJECT_ROOT="${ETL_PROJECT_ROOT:-/opt/dashboard-etl}"
JOB_NAME="${1:-ETL_MIS_EBITDA}"

# Lock file prevents overlap if previous run is still going
LOCK_FILE="/var/run/etl_${JOB_NAME}.lock"
exec 200>"$LOCK_FILE" || exit 1
flock -n 200 || { echo "ETL $JOB_NAME already running, skipping"; exit 0; }

cd "$PROJECT_ROOT"

# Load environment (credentials live here in production)
if [ -f /etc/dashboard-etl/env ]; then
    set -a
    # shellcheck disable=SC1091
    source /etc/dashboard-etl/env
    set +a
fi

# Activate virtualenv
# shellcheck disable=SC1091
source venv/bin/activate

# Run ETL with output also going to stdout for cron's MAILTO if configured
python -m src.main --job "$JOB_NAME"
EXIT_CODE=$?

exit $EXIT_CODE
