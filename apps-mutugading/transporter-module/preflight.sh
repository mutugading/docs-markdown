#!/usr/bin/env bash
# preflight.sh — Transporter. Jalankan dari root repo:
#   bash .ai/transporter/preflight.sh
# Keluar dengan kode 1 bila ada BLOCKER yang gagal.
#
# Catatan: `php artisan tinker --execute="exit(0)"` SELALU mengembalikan kode != 0
# (psysh melempar BreakException). Cek lewat tinker karena itu harus memakai
# pola `echo "TANDA:..."` lalu `grep -q`, bukan kode keluar.
#
# PHP tidak terpasang di host WSL — ia jalan di dalam container Docker.
# Skrip ini mencari runner-nya sendiri: `php` di host kalau ada, kalau tidak
# container yang sedang jalan. Override dengan:
#   TRANSPORTER_APP_CONTAINER=nama-container bash .ai/transporter/preflight.sh

set -uo pipefail

BLOCKERS_FAILED=0
WARNINGS_FAILED=0
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
PRD_DIR="/mnt/d/IT Project/docs-markdown/apps-mutugading/transporter-module"

cd "$ROOT" || { echo "FAIL: tidak bisa masuk ke root repo"; exit 1; }

red()   { printf '\033[31m%s\033[0m\n' "$1"; }
green() { printf '\033[32m%s\033[0m\n' "$1"; }
amber() { printf '\033[33m%s\033[0m\n' "$1"; }
dim()   { printf '\033[2m%s\033[0m\n'  "$1"; }

# ---------------------------------------------------------------- PHP runner
APP_CONTAINER="${TRANSPORTER_APP_CONTAINER:-}"
QUEUE_CONTAINER=""
RUNNER=""

if command -v php >/dev/null 2>&1; then
  RUNNER="host"
elif command -v docker >/dev/null 2>&1; then
  if [ -z "$APP_CONTAINER" ]; then
    # Container aplikasi = yang namanya mengandung "app" (bukan vite/queue) dan
    # bisa menjalankan php. `laravel-app` (nama di e2e/bin/state.sh) dicoba dulu.
    for candidate in laravel-app $(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'app' | grep -vE 'vite|queue'); do
      if docker exec "$candidate" php -v >/dev/null 2>&1; then
        APP_CONTAINER="$candidate"
        break
      fi
    done
  fi
  [ -n "$APP_CONTAINER" ] && RUNNER="docker"
  QUEUE_CONTAINER="$(docker ps --format '{{.Names}}' 2>/dev/null | grep -E 'queue' | head -1)"
fi

php_run() { # php_run "<perintah lengkap, diawali php/vendor/npm>"
  case "$RUNNER" in
    host)   eval "$1" ;;
    # Container dev kini jalan sebagai uid 1000 dengan HOME=/ — psysh (tinker) gagal
    # menulis /.config dan setiap cek tinker jatuh FAIL palsu. Lihat gap.md C-10 butir 4.
    docker) docker exec -w /var/www/html "$APP_CONTAINER" bash -lc '[ -w "$HOME" ] || export HOME=/tmp; '"$1" ;;
    *)      return 127 ;;
  esac
}

blocker() { # blocker "<judul>" "<perintah>" [php]
  local ok=1
  if [ "${3:-shell}" = "php" ]; then
    php_run "$2" >/dev/null 2>&1 && ok=0
  else
    eval "$2" >/dev/null 2>&1 && ok=0
  fi
  if [ "$ok" -eq 0 ]; then
    green "  PASS  $1"
  else
    red   "  FAIL  $1"
    red   "        → $2"
    BLOCKERS_FAILED=$((BLOCKERS_FAILED + 1))
  fi
}

warning() { # warning "<judul>" "<perintah>" [php]
  local ok=1
  if [ "${3:-shell}" = "php" ]; then
    php_run "$2" >/dev/null 2>&1 && ok=0
  else
    eval "$2" >/dev/null 2>&1 && ok=0
  fi
  if [ "$ok" -eq 0 ]; then
    green "  OK    $1"
  else
    amber "  WARN  $1"
    WARNINGS_FAILED=$((WARNINGS_FAILED + 1))
  fi
}

echo
echo "=============================================="
echo " PREFLIGHT — Transporter"
echo " repo: $ROOT"
echo " $(date '+%Y-%m-%d %H:%M:%S')"
case "$RUNNER" in
  host)   echo " php:  host" ;;
  docker) echo " php:  docker exec $APP_CONTAINER" ;;
  *)      echo " php:  TIDAK KETEMU" ;;
esac
echo "=============================================="
echo
echo "BLOCKER"
echo "----------------------------------------------"

blocker "1  repo yang benar"            'test -f artisan && test -f CLAUDE.md'
blocker "2  runner PHP tersedia"        'test -n "$RUNNER"'

if [ -z "$RUNNER" ]; then
  echo
  red " Tidak ada cara menjalankan PHP."
  dim " Nyalakan container aplikasi, atau set TRANSPORTER_APP_CONTAINER=<nama>."
  dim " Lihat container yang jalan:  docker ps --format '{{.Names}}'"
  echo
  exit 1
fi

blocker "3  PHP >= 8.2"                 'php -r "exit(PHP_VERSION_ID >= 80200 ? 0 : 1);"' php
blocker "4  dependency terpasang"       'test -d vendor && test -d node_modules'
blocker "5  .env ada"                   'test -f .env'
blocker "6  Laravel bisa boot"          'php artisan --version' php
blocker "7  koneksi oracle_mgtdat"      'php artisan tinker --execute="echo \"DRV:\".config(\"database.connections.oracle_mgtdat.driver\");" | grep -q "DRV:[a-z]"' php
blocker "8  koneksi oracle_mgthris"     'php artisan tinker --execute="echo \"DRV:\".config(\"database.connections.oracle_mgthris.driver\");" | grep -q "DRV:[a-z]"' php
blocker "9  modul referensi ada"        'test -d Modules/LcControl && test -d Modules/Core'
blocker "10 service GL referensi ada"   'test -f Modules/LcControl/app/Services/Erp/JournalVoucherPostingService.php || test -f Modules/Core/app/Services/Erp/JournalVoucherPostingService.php'
blocker "11 SysIdHelper ada"            'test -f app/Helpers/SysIdHelper.php'
blocker "12 trait wajib ada"            'test -f app/Traits/Searchable.php && test -f app/Traits/LogsActivityWithDescription.php'
blocker "13 test suite hijau"           'php artisan test --parallel' php
blocker "14 pint bersih"                'vendor/bin/pint --test' php
blocker "15 berkas konteks lengkap"     'for f in README gap plan design spec TASKS PREFLIGHT PROGRESS DECISIONS ACCEPTANCE; do test -f ".ai/transporter/$f.md" || exit 1; done'
blocker "16 PRD terjangkau"             "test -f \"$PRD_DIR/PRD_Transporter_Module.md\""
blocker "17 sumber legacy terjangkau"   "test -d \"$PRD_DIR/legacy-source\""

echo
echo "WARNING"
echo "----------------------------------------------"

warning "1  working tree bersih"        'test -z "$(git status --porcelain)"'
warning "2  bukan di main/develop"      'b=$(git rev-parse --abbrev-ref HEAD); test "$b" != "main" && test "$b" != "develop"'
warning "3  queue worker jalan"         'test -n "$QUEUE_CONTAINER" || pgrep -f "queue:(work|listen)"'
warning "4  e2e descriptor cocok"       'node e2e/bin/check-pages.mjs' php
warning "5  migration terakhir 2026_09" 'test "$(ls Modules/*/database/migrations/ | grep -E "^2026_" | sort | tail -1 | cut -c1-7)" = "2026_09"'
warning "6  Oracle bisa dihubungi"      'php artisan tinker --execute="DB::connection(\"oracle_mgtdat\")->getPdo(); echo \"PDO:ok\";" | grep -q "PDO:ok"' php

echo
echo "=============================================="
if [ "$BLOCKERS_FAILED" -eq 0 ]; then
  green " SEMUA BLOCKER PASS — boleh mulai."
  [ "$WARNINGS_FAILED" -gt 0 ] && amber " $WARNINGS_FAILED warning — sebutkan di laporan."
  echo "=============================================="
  echo
  echo " Berikutnya: buka .ai/transporter/TASKS.md, ambil task [TODO]"
  echo " paling atas yang blocker-nya sudah terpenuhi."
  echo
  exit 0
else
  red " $BLOCKERS_FAILED BLOCKER GAGAL — STOP."
  red " Laporkan ke Indra. Jangan dikerjakan sambil jalan."
  echo "=============================================="
  echo
  exit 1
fi
