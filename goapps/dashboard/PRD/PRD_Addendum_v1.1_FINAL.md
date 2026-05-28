# PRD Addendum v1.1 (Final) — Multi-Metric Support

**Document Code**: EXEC-DASH-2026-ADD-001
**Status**: Approved & Implemented
**Supersedes**: PRD v1.0 Section 7-8 (additive changes only)
**Decision Date**: 28 May 2026

---

## 1. Background

PRD v1.0 schema (`FACT_METRIC`) dirancang untuk **single-metric per dimension combination** — cocok untuk P&L style data (EBITDA, Net Profit).

Setelah analisis modul Delivery Margin (Sales), ditemukan kebutuhan untuk **multi-metric per dimension combination** — satu kombinasi dimensi (Delivery × Category × Product × periode) punya 6 metric: QUANTITY, GROSS_SALES, NETT_SALES, SELLING_COST, COST_PROD, MARGIN.

Dokumen ini mendefinisikan extension schema yang **additive dan backward-compatible**.

## 2. Decision Summary

Pilihan: **Alt A+ Unified Schema** — extend `FACT_METRIC` dengan 3 kolom tambahan, tetap single table.

Rationale:
- Single query pattern (simplicity, primary requirement)
- Backward-compatible dengan EBITDA seed (default values handle migration)
- Type-safe (kolom terdefinisi, IDE autocomplete works)
- Forward-compatible untuk Phase 2 modul (Inventory, Production, HR)

## 3. Conventions (Locked-in)

### 3.1 Metric Name Convention

**UPPERCASE_SNAKE_CASE** untuk semua `FM_METRIC_NAME` values.

| Module | Metric Names |
|--------|--------------|
| MIS (EBITDA, Net Profit) | `VALUE` (single-metric default) |
| SALES (Delivery Margin) | `QUANTITY`, `GROSS_SALES`, `NETT_SALES`, `SELLING_COST`, `COST_PROD`, `MARGIN` |
| INV (Phase 2) | `STOCK_QTY`, `STOCK_VALUE`, `AVG_COST`, `DAYS_ON_HAND` |
| PROD (Phase 2) | `OUTPUT_QTY`, `REJECT_QTY`, `YIELD_PCT`, `COST_PER_UNIT` |
| HR (Phase 2) | `HEADCOUNT`, `OT_HOURS`, `OT_COST`, `PRODUCTIVITY` |

Display labels disimpan di tabel `METRIC_REGISTRY`, terpisah dari storage code.

### 3.2 FM_TYPE Convention

**UPPERCASE** untuk module code: `MIS`, `SALES`, `INV`, `HR`, `PROD`, `OPEX`.

### 3.3 Metric Category & Aggregation Method

| METRIC_CATEGORY | AGG_METHOD | Example metrics | Display format |
|-----------------|------------|----------------|----------------|
| `VOLUME` | `SUM` | QUANTITY, HEADCOUNT, OT_HOURS | `1.5K PCS` |
| `VALUE` | `SUM` | GROSS_SALES, EBITDA, MARGIN | `$1.5M` |
| `AVERAGE` | `WEIGHTED_AVG` | AVG_COST, COST_PER_UNIT | `$1.50/PCS` |
| `RATIO` | `WEIGHTED_AVG` | YIELD_PCT, PRODUCTIVITY | `12.5%` |
| `DERIVED` | `LAST` | Running totals, snapshots | (varies) |

## 4. Schema Changes

### 4.1 New Columns

```sql
ALTER TABLE FACT_METRIC
  ADD COLUMN IF NOT EXISTS FM_METRIC_NAME     VARCHAR(50) NOT NULL DEFAULT 'VALUE',
  ADD COLUMN IF NOT EXISTS FM_METRIC_CATEGORY VARCHAR(20) NOT NULL DEFAULT 'VALUE',
  ADD COLUMN IF NOT EXISTS FM_AGG_METHOD      VARCHAR(20) NOT NULL DEFAULT 'SUM';
```

Default values memastikan existing EBITDA data tetap valid tanpa migration.

### 4.2 New Table: METRIC_REGISTRY

Centralized metadata untuk display labels dan format hints.

```sql
CREATE TABLE METRIC_REGISTRY (
  MR_METRIC_ID         SERIAL PRIMARY KEY,
  MR_METRIC_NAME       VARCHAR(50) UNIQUE NOT NULL,
  MR_DISPLAY_LABEL     VARCHAR(100) NOT NULL,
  MR_METRIC_CATEGORY   VARCHAR(20) NOT NULL,
  MR_AGG_METHOD        VARCHAR(20) NOT NULL,
  MR_UOM               VARCHAR(20),
  MR_DESCRIPTION       TEXT,
  MR_NUMBER_FORMAT     VARCHAR(50),
  MR_IS_ACTIVE         BOOLEAN DEFAULT TRUE
);
```

API JOIN ke METRIC_REGISTRY untuk dapat display label saat construct response. Update label tidak butuh data migration.

### 4.3 Updated Unique Constraint

```sql
ALTER TABLE FACT_METRIC ADD CONSTRAINT UQ_FM_BUSINESS_KEY UNIQUE (
  FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
  FM_PERIODE_GRAIN, FM_PERIODE_DATE,
  FM_METRIC_NAME,  -- NEW
  FM_SCENARIO, FM_DIMENSION_KEY
);
```

`FM_METRIC_NAME` ditambahkan supaya 6 row per dimension combo (delivery margin) bisa coexist.

### 4.4 Updated Materialized Views

MV `MV_METRIC_G1` dan `MV_METRIC_G2` ditambahkan filter `WHERE FM_AGG_METHOD = 'SUM'` — mencegah accidentally summing averages atau ratios.

```sql
CREATE MATERIALIZED VIEW MV_METRIC_G1 AS
  SELECT FM_TYPE, FM_GROUP_1, FM_METRIC_NAME, FM_METRIC_CATEGORY,
         FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_PERIODE_LABEL, FM_SCENARIO,
         SUM(FM_DISPLAY_VALUE) AS MV_VALUE
  FROM FACT_METRIC
  WHERE FM_IS_ACTIVE = TRUE
    AND FM_AGG_METHOD = 'SUM'
  GROUP BY 1, 2, 3, 4, 5, 6, 7, 8;
```

## 5. Decision: AVG_* Metrics Dropped for MVP

Setelah evaluasi, per-unit average metrics (`AVG_GROSS_SALES`, `AVG_MARGIN`, dll) **tidak disimpan** di FACT_METRIC.

Alasan:
- Bisa di-compute on-the-fly dari atomic data (`SUM(MARGIN) / SUM(QUANTITY)`)
- Storage saving: 6 metrics vs 13 metrics — row count berkurang 54%
- Eliminate "AVG of AVG" math errors
- Weighted average di-handle di query/API layer dengan ratio computation

**Trade-off**: Setiap KPI yang butuh per-unit value harus pakai computation query, sedikit lebih kompleks dari simple SELECT. Acceptable karena:
- Query pattern terdokumentasi di cheatsheet
- Dashboard config support `"computed": "expr"` field

Kalau di Phase 2 butuh AVG sebagai stored metric (untuk performance), tinggal tambah row dengan `FM_AGG_METHOD = 'WEIGHTED_AVG'` — schema sudah ready.

## 6. Data Storage Patterns

### Pattern A: Single-Metric (EBITDA, Net Profit)

```
TYPE='MIS', GROUP_1='EBITDA', GROUP_2='INCOME', GROUP_3='LOCAL SALES',
PERIODE='202604', METRIC_NAME='VALUE', METRIC_CATEGORY='VALUE',
AGG_METHOD='SUM', VALUE=-4078919.76
```

One row per dimension combo. `FM_METRIC_NAME='VALUE'` (default).

### Pattern B: Multi-Metric (Delivery Margin)

```
TYPE='SALES', GROUP_1='Export', GROUP_2='ACY', GROUP_3='ACY', PERIODE='202605',
METRIC_NAME='QUANTITY',    METRIC_CATEGORY='VOLUME', AGG_METHOD='SUM', VALUE=9766.47, UOM='PCS'
METRIC_NAME='GROSS_SALES', METRIC_CATEGORY='VALUE',  AGG_METHOD='SUM', VALUE=33526.34, UOM='USD'
METRIC_NAME='NETT_SALES',  METRIC_CATEGORY='VALUE',  AGG_METHOD='SUM', VALUE=32795.52, UOM='USD'
METRIC_NAME='SELLING_COST','VALUE','SUM', VALUE=730.83, UOM='USD'
METRIC_NAME='COST_PROD',   'VALUE','SUM', VALUE=23343.97, UOM='USD'
METRIC_NAME='MARGIN',      'VALUE','SUM', VALUE=9451.55, UOM='USD'
```

6 rows per dimension combo, each with specific `FM_METRIC_NAME`.

## 7. Query Patterns

### 7.1 Pattern A query (unchanged from v1.0)

```sql
SELECT FM_GROUP_2, SUM(FM_DISPLAY_VALUE) AS value
FROM FACT_METRIC
WHERE FM_TYPE = 'MIS' AND FM_GROUP_1 = 'EBITDA'
  AND FM_PERIODE_LABEL = '202604'
GROUP BY FM_GROUP_2;
-- Works because EBITDA rows default to METRIC_NAME='VALUE'.
```

### 7.2 Pattern B query (multi-metric line chart)

```sql
SELECT FM_PERIODE_LABEL,
       SUM(CASE WHEN FM_METRIC_NAME = 'GROSS_SALES' THEN FM_DISPLAY_VALUE END) AS gross,
       SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES'  THEN FM_DISPLAY_VALUE END) AS net,
       SUM(CASE WHEN FM_METRIC_NAME = 'COST_PROD'   THEN FM_DISPLAY_VALUE END) AS cost,
       SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'      THEN FM_DISPLAY_VALUE END) AS margin
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_METRIC_NAME IN ('GROSS_SALES','NETT_SALES','COST_PROD','MARGIN')
  AND FM_PERIODE_DATE >= '2025-06-01'
GROUP BY FM_PERIODE_LABEL
ORDER BY FM_PERIODE_LABEL;
```

### 7.3 Weighted average on-the-fly (no stored AVG_*)

```sql
-- Margin per PCS per category (weighted average)
SELECT FM_GROUP_2 AS category,
       SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'   THEN FM_DISPLAY_VALUE END) /
       NULLIF(SUM(CASE WHEN FM_METRIC_NAME = 'QUANTITY' THEN FM_DISPLAY_VALUE END), 0)
         AS avg_margin_per_pcs
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_PERIODE_LABEL = '202605'
  AND FM_METRIC_NAME IN ('MARGIN', 'QUANTITY')
GROUP BY FM_GROUP_2;
```

### 7.4 Margin % ratio

```sql
SELECT FM_GROUP_2 AS category,
       ROUND(
         SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'     THEN FM_DISPLAY_VALUE END) /
         NULLIF(SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES' THEN FM_DISPLAY_VALUE END), 0) * 100,
         2
       ) AS margin_pct
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_PERIODE_LABEL = '202605'
GROUP BY FM_GROUP_2
ORDER BY margin_pct DESC NULLS LAST;
```

## 8. Dashboard Config Extension

### 8.1 chart_config Structure for Multi-Metric

```json
{
  "type": "multi_line",
  "metric_filter": {
    "include_metrics": ["GROSS_SALES", "NETT_SALES", "COST_PROD", "MARGIN"]
  },
  "series": [
    {"metric": "GROSS_SALES", "color": "#1F4E79", "y_axis": 0},
    {"metric": "NETT_SALES",  "color": "#2E75B6", "y_axis": 0},
    {"metric": "COST_PROD",   "color": "#a32d2d", "y_axis": 0},
    {"metric": "MARGIN",      "color": "#1d9e75", "y_axis": 1}
  ],
  "filter_chips": ["FM_GROUP_1", "FM_GROUP_2"]
}
```

API resolves `metric` → `FM_METRIC_NAME`, fetches display label from `METRIC_REGISTRY`.

### 8.2 Computed KPI Support

```json
{
  "label": "Margin %",
  "computed": "MARGIN/NETT_SALES*100",
  "format": "percent",
  "compare": "YoY"
}
```

API parses expression dengan metric names → SQL CASE/SUM/divide pattern.

## 9. ETL Implementation

Transformer module `src/transformer_multi.py` di-add ke ETL worker. Module config-driven:

```python
MODULE_CONFIG = {
    'DELIVERY_MARGIN': {
        'fm_type': 'SALES',
        'sign_flip_groups': set(),
        'metrics': [
            ('quantity',     'QUANTITY',     'VOLUME', 'SUM', 'PCS'),
            ('gross_sales',  'GROSS_SALES',  'VALUE',  'SUM', 'USD'),
            # ... 6 metrics total
        ],
    },
}
```

Single Oracle row → 6 FACT_METRIC tuples via `expand_multi_metric()`. Tested with 11 unit tests + E2E validation against real Excel data.

## 10. Migration Path

Migration v1.0 → v1.1:

1. Run `ALTER TABLE` statements (additive, no data change)
2. EBITDA existing data tetap valid (default values fill new cols)
3. Existing dashboard config tetap berfungsi
4. DROP + RECREATE materialized views (1-time)
5. INSERT delivery margin data
6. Done

**Rollback**: drop 3 new columns + METRIC_REGISTRY table, recreate old constraint. EBITDA data tidak terdampak.

## 11. Forward Compatibility

Schema v1.1 ready untuk Phase 2 tanpa schema change:

| Module | Metrics | Pattern |
|--------|---------|---------|
| Inventory | STOCK_QTY, STOCK_VALUE, AVG_COST | Multi-metric |
| Production | OUTPUT_QTY, YIELD_PCT, COST_PER_UNIT | Multi-metric |
| HR Overtime | HEADCOUNT, OT_HOURS, OT_COST | Multi-metric |
| Sales by Customer | REVENUE, DISCOUNT, NET_REVENUE | Multi-metric |

Setiap modul: define metric config di ETL + insert ke METRIC_REGISTRY + create dashboard config JSON. **Zero schema migration**.

## 12. Updated Glossary

- **Metric name** — specific KPI within a dimension combination (UPPERCASE_SNAKE_CASE, e.g., `GROSS_SALES`). Stored in `FM_METRIC_NAME`.
- **Display label** — human-readable label ("Gross Sales"). Stored in `MR_DISPLAY_LABEL`.
- **Metric category** — classification (VOLUME, VALUE, AVERAGE, RATIO, DERIVED). Stored in `FM_METRIC_CATEGORY`.
- **Aggregation method** — how to roll up (SUM, WEIGHTED_AVG, etc.). Stored in `FM_AGG_METHOD`.
- **Single-metric module** — each dimension combo has 1 metric (EBITDA pattern).
- **Multi-metric module** — each dimension combo has multiple metrics (Delivery pattern).

---

**Approved by**: IT Leader
**Implementation status**: Schema deployed, ETL transformer tested, prototype validated
**Test evidence**: 11/11 unit tests pass, E2E validates against real Excel (May 2026 Margin = $303,439.29 USD)
