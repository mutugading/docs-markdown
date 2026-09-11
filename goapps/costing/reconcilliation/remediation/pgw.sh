#!/bin/sh
# ============================================================================
#  pgw.sh - runner remediasi ke Postgres goapps (SISTEM BARU) SAJA.
#
#  Target koneksi DIPATOK KERAS: localhost:25432 db goapps. Tidak ada parameter
#  host/port/db -> script ini secara struktural TIDAK BISA menulis ke Oracle
#  legacy (ALTHARA) maupun host lain. Kredensial dibaca dari D:\DataGripQuery\env.
#
#  TRANSAKSI DISEDIAKAN OLEH RUNNER, BUKAN OLEH FILE.
#  Revisi 2026-09-10: versi pertama mengandalkan BEGIN;/COMMIT; di dalam file
#  SQL. File paket 01-06 TIDAK punya keduanya (headernya justru meminta
#  pemanggil menyediakan transaksi), jadi psql jalan autocommit dan mode
#  --rollback tidak berefek apa pun -> paket 01 ter-commit saat "dry-run".
#  Sekarang runner membungkus sendiri: BEGIN; <isi file>; COMMIT|ROLLBACK.
#  Baris BEGIN;/COMMIT;/ROLLBACK; milik file dibuang lebih dulu supaya file
#  yang sudah punya transaksi sendiri (paket 07) tidak dobel.
#
#  Pemakaian:
#     bash ./pgw.sh <file.sql> --rollback   -> DRY-RUN, dijamin ter-rollback
#     bash ./pgw.sh <file.sql>              -> eksekusi + COMMIT
#     bash ./pgw.sh --sql "<SELECT ...>"    -> query verifikasi ad-hoc
#
#  ON_ERROR_STOP=1: statement gagal ⇒ psql berhenti sebelum COMMIT ⇒ rollback.
# ============================================================================
set -e
ENVF=/d/DataGripQuery/env
PGHOST=localhost
PGPORT=25432
PGDATABASE=$(grep -i '^> *DB *:' "$ENVF" | head -1 | sed 's/.*: *//' | tr -d '\r')
PGUSER=$(grep -i '^> *User *:' "$ENVF" | head -1 | sed 's/.*: *//' | tr -d '\r')
PGPASSWORD=$(grep -i '^> *Pass *:' "$ENVF" | head -1 | sed 's/.*: *//' | tr -d '\r')
export PGHOST PGPORT PGDATABASE PGUSER PGPASSWORD

if [ "$1" = "--sql" ]; then
  shift
  exec psql -v ON_ERROR_STOP=1 -X -A -F '|' -P pager=off -c "$*"
fi

SQLF=$1
[ -f "$SQLF" ] || { echo "pgw.sh: file tidak ada: $SQLF" >&2; exit 3; }

if [ "$2" = "--rollback" ]; then AKHIR=ROLLBACK; LABEL="DRY-RUN (ROLLBACK)";
else                             AKHIR=COMMIT;   LABEL="EKSEKUSI (COMMIT) "; fi

TMP="${TMPDIR:-/tmp}/pgw_$$.sql"
{ echo "BEGIN;"
  grep -v -E '^[[:space:]]*(BEGIN|COMMIT|ROLLBACK)[[:space:]]*;' "$SQLF"
  echo "$AKHIR;"
} > "$TMP"

echo "== $LABEL : $SQLF"
psql -v ON_ERROR_STOP=1 -X -P pager=off -f "$TMP"
rm -f "$TMP"
