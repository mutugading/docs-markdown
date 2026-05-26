# Executive Dashboard — Developer Test Data Package

Package ini berisi semua yang developer butuhkan untuk mulai coding tanpa nunggu Oracle ETL siap.

## 📦 File Manifest

| File | Purpose | Size | Format |
|------|---------|------|--------|
| `seed_database.sql` | DDL + 2,135 baris seed data + dashboard config + master data | 355 KB | SQL |
| `fact_metric_seed.xlsx` | Same data as Excel (alternatif untuk yang prefer GUI import) | 174 KB | XLSX |
| `api_samples.json` | Sample API request/response untuk frontend mocking | 12 KB | JSON |
| `PRD_Executive_Dashboard.docx` | Full PRD spec | 47 KB | DOCX |
| `PRD_Executive_Dashboard.md` | Same PRD in Markdown (mudah di-version control) | ~70 KB | MD |

## 🚀 Quick Start

### Setup PostgreSQL Database

```bash
# 1. Buat database baru
createdb dashboard_dev

# 2. Pastikan pgvector extension tersedia
# Di Ubuntu/Debian:
sudo apt install postgresql-15-pgvector
# Atau via Docker:
docker run -d --name pg-dashboard \
  -e POSTGRES_PASSWORD=devpass \
  -e POSTGRES_DB=dashboard_dev \
  -p 5432:5432 \
  pgvector/pgvector:pg15

# 3. Run seed SQL
psql -h localhost -U postgres -d dashboard_dev -f seed_database.sql

# 4. Verifikasi
psql -h localhost -U postgres -d dashboard_dev -c \
  "SELECT FM_PERIODE_LABEL, ROUND(SUM(FM_DISPLAY_VALUE),2) AS ebitda
   FROM FACT_METRIC WHERE FM_GROUP_1='EBITDA'
   GROUP BY 1 ORDER BY 1 DESC LIMIT 5;"
```

Expected output for last 5 months:
```
 fm_periode_label |   ebitda
------------------+------------
 202604           | 1015881.07
 202603           |  124032.62
 202602           |  224957.64
 202601           |  189323.58
 202512           |  753354.82
```

### Data Coverage

- **Time range**: January 2023 → April 2026 (40 months)
- **Module**: MIS (Finance) — GROUP_1: EBITDA & NET PROFIT
- **Hierarchy depth**: 3 levels (TYPE → GROUP_1 → GROUP_2 → GROUP_3)
- **Total rows**: 2,135 in FACT_METRIC
- **Unique GROUP_2**: 12 categories (INCOME, PRODUCTION COST, MATERIAL CONSUMPTION, ENERGY COST, MANPOWER, etc.)
- **Unique GROUP_3**: 61 line items

## 🎯 Pre-configured Dashboards

Seeded `DASHBOARD_CONFIG` table sudah punya 2 dashboard siap pakai:

1. **EBITDA** (`DC_DASHBOARD_CODE='EBITDA'`)
   - Chart: Waterfall
   - Drill: 3 levels
   - Compare: MoM, QoQ, YoY, YTD, R12
   - Access roles: CEO, CFO, COO, FINANCE_MGR, IT_LEADER

2. **NET_PROFIT** (`DC_DASHBOARD_CODE='NET_PROFIT'`)
   - Chart: Bar with YoY line overlay
   - Drill: 2 levels
   - Compare: MoM, QoQ, YoY, YTD, R12
   - Access roles: CEO, CFO, FINANCE_MGR, IT_LEADER

## 📊 Sample Queries for API Development

### Get EBITDA breakdown for April 2026 (waterfall data)

```sql
SELECT
  FM_GROUP_2 AS label,
  FM_GROUP_2_ORDER AS order_id,
  ROUND(SUM(FM_DISPLAY_VALUE), 2) AS value
FROM FACT_METRIC
WHERE FM_TYPE = 'MIS'
  AND FM_GROUP_1 = 'EBITDA'
  AND FM_PERIODE_LABEL = '202604'
  AND FM_IS_ACTIVE = TRUE
GROUP BY FM_GROUP_2, FM_GROUP_2_ORDER
ORDER BY FM_GROUP_2_ORDER;
```

### Drill to GROUP_3 of PRODUCTION COST

```sql
SELECT
  FM_GROUP_3 AS label,
  FM_GROUP_3_ORDER AS order_id,
  ROUND(SUM(FM_DISPLAY_VALUE), 2) AS value
FROM FACT_METRIC
WHERE FM_TYPE = 'MIS'
  AND FM_GROUP_1 = 'EBITDA'
  AND FM_GROUP_2 = 'PRODUCTION COST'
  AND FM_PERIODE_LABEL = '202604'
GROUP BY FM_GROUP_3, FM_GROUP_3_ORDER
ORDER BY FM_GROUP_3_ORDER;
```

### MoM and YoY comparison in one query

```sql
WITH MONTHLY AS (
  SELECT FM_PERIODE_DATE, SUM(FM_DISPLAY_VALUE) AS MV_EBITDA
  FROM FACT_METRIC
  WHERE FM_GROUP_1 = 'EBITDA'
  GROUP BY FM_PERIODE_DATE
)
SELECT
  FM_PERIODE_DATE,
  MV_EBITDA AS current_value,
  LAG(MV_EBITDA, 1) OVER (ORDER BY FM_PERIODE_DATE) AS prev_month,
  LAG(MV_EBITDA, 12) OVER (ORDER BY FM_PERIODE_DATE) AS same_month_last_year,
  ROUND((MV_EBITDA - LAG(MV_EBITDA, 1) OVER (ORDER BY FM_PERIODE_DATE)) /
        NULLIF(ABS(LAG(MV_EBITDA, 1) OVER (ORDER BY FM_PERIODE_DATE)), 0) * 100, 2) AS mom_pct,
  ROUND((MV_EBITDA - LAG(MV_EBITDA, 12) OVER (ORDER BY FM_PERIODE_DATE)) /
        NULLIF(ABS(LAG(MV_EBITDA, 12) OVER (ORDER BY FM_PERIODE_DATE)), 0) * 100, 2) AS yoy_pct
FROM MONTHLY
ORDER BY FM_PERIODE_DATE DESC
LIMIT 12;
```

## 🔧 API Mocking for Frontend

`api_samples.json` berisi sample response untuk endpoint utama. Frontend developer bisa langsung pakai untuk:

- Build component dengan data shape yang benar
- Test rendering tanpa nunggu backend
- Validate error state (403, 404)

Endpoint yang ter-cover:

- `GET /api/v1/dashboards` — list dashboards untuk user
- `GET /api/v1/dashboards/:code/config` — chart config
- `GET /api/v1/dashboards/:code/data` — level 1 data
- `GET /api/v1/dashboards/:code/drill` — drill to level 2
- `GET /api/v1/dashboards/:code/compare` — comparison data
- `POST /api/v1/upload/parse` — Excel upload preview
- Error 403 & 404 responses

## 🔄 Re-running the Seed

Seed SQL safe untuk re-run — semua tabel akan di-`DROP CASCADE` di awal lalu di-recreate. Jangan dipakai di production setelah ada data real.

## 📝 Notes untuk Developer

### Sign Convention

Data Oracle menyimpan income sebagai negatif dan cost sebagai positif (accounting convention). Kolom `FM_DISPLAY_VALUE` sudah sign-flipped untuk frontend consumption — **selalu pakai `FM_DISPLAY_VALUE` untuk display, jangan `FM_VALUE`**.

Contoh: untuk EBITDA Apr 2026:
- INCOME (raw `FM_VALUE`): `-5,602,761.07` → `FM_DISPLAY_VALUE`: `+5,602,761.07` ✓ tampil positif di chart
- PRODUCTION COST (raw `FM_VALUE`): `+2,698,479.89` → `FM_DISPLAY_VALUE`: `-2,698,479.89` ✓ tampil negatif

Sum dari `FM_DISPLAY_VALUE` untuk semua GROUP_2 = EBITDA total yang ditampilkan ke BOD.

### Column Prefix Convention

Setiap tabel pakai prefix kolom unik (FM_, DC_, DS_, dll). Hasilnya:

```sql
-- Tidak perlu alias karena nama kolom unik global
SELECT FM_TYPE, FM_GROUP_1, DS_SOURCE_NAME, DC_DASHBOARD_TITLE
FROM FACT_METRIC
JOIN DATA_SOURCE ON FM_SOURCE_ID = DS_SOURCE_ID
JOIN DASHBOARD_CONFIG ON DC_FILTER_TYPE = FM_TYPE
WHERE FM_TYPE = 'MIS';
```

### Materialized Views

Dua MV ter-create otomatis dari seed:
- `MV_METRIC_G1` — aggregated by GROUP_1 level
- `MV_METRIC_G2` — aggregated by GROUP_2 level

Query level 1 dashboard sebaiknya pakai MV. Query level 3 (detail) dari `FACT_METRIC` langsung.

Refresh setelah data update:
```sql
SELECT REFRESH_DASHBOARD_MVS();
```

## 🐛 Troubleshooting

**Error: extension "vector" is not available**
→ Install pgvector extension. Atau remove `CREATE EXTENSION` line jika belum butuh fitur AI Phase 3.

**Error: function REFRESH_DASHBOARD_MVS() does not exist**
→ Pastikan seed SQL ter-execute sampai habis. Function ada di bagian #7.

**EBITDA total tidak match dengan Excel original**
→ Cek kolom yang dipakai. Excel pakai `VALUE` (raw, signed accounting). Display pakai `DISPLAY_VALUE` (sign-flipped). EBITDA total = `SUM(FM_DISPLAY_VALUE)` untuk GROUP_1='EBITDA'.

---

Questions? Refer ke `PRD_Executive_Dashboard.md` section 7 (Data Architecture) atau section 21 (Appendix).
