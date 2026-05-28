# Developer Cheat Sheet — Multi-Metric Schema (FINAL)

Quick reference untuk developer yang bekerja dengan `FACT_METRIC` schema v1.1.

## Mental Model

```
┌──────────────────────────────────────────────────────────────────┐
│  FACT_METRIC (single table, two patterns)                         │
├──────────────────────────────────────────────────────────────────┤
│                                                                   │
│  Pattern A: P&L data           Pattern B: Operational data        │
│  ───────────────────           ──────────────────────             │
│  EBITDA, Net Profit            Delivery, Inventory, HR            │
│  1 metric per dim combo        Multi metrics per dim combo        │
│  METRIC_NAME='VALUE' (default) METRIC_NAME='GROSS_SALES'/etc      │
│                                                                   │
│  Same table. Same query patterns. Different storage shape.        │
└──────────────────────────────────────────────────────────────────┘
```

## Naming Conventions (Locked-in)

**Always UPPERCASE_SNAKE_CASE** for these:
- `FM_TYPE`: `MIS`, `SALES`, `INV`, `HR`, `PROD`, `OPEX`
- `FM_METRIC_NAME`: `QUANTITY`, `GROSS_SALES`, `NETT_SALES`, `MARGIN`, etc.
- `FM_METRIC_CATEGORY`: `VOLUME`, `VALUE`, `AVERAGE`, `RATIO`, `DERIVED`
- `FM_AGG_METHOD`: `SUM`, `WEIGHTED_AVG`, `AVG`, `LAST`, `RATIO`

**Display labels** ("Gross Sales", "Net Sales") live in `METRIC_REGISTRY.MR_DISPLAY_LABEL` — never in queries.

## Key Rules

### Rule 1: Always filter by METRIC_NAME

❌ **WRONG** (mixes QUANTITY pieces with USD values):
```sql
SELECT SUM(FM_DISPLAY_VALUE) FROM FACT_METRIC WHERE FM_TYPE = 'SALES';
```

✅ **RIGHT**:
```sql
SELECT SUM(FM_DISPLAY_VALUE)
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES' AND FM_METRIC_NAME = 'GROSS_SALES';
```

### Rule 2: Compute averages on-the-fly (no AVG_* stored)

For per-unit metrics, query atomic data with ratio:

```sql
-- Margin per PCS (weighted average)
SELECT FM_GROUP_2,
       SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'   THEN FM_DISPLAY_VALUE END) /
       NULLIF(SUM(CASE WHEN FM_METRIC_NAME = 'QUANTITY' THEN FM_DISPLAY_VALUE END), 0)
         AS avg_margin_per_pcs
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES' AND FM_PERIODE_LABEL = '202605'
GROUP BY FM_GROUP_2;
```

### Rule 3: Materialized views filter to SUM-able metrics

`MV_METRIC_G1` and `MV_METRIC_G2` contain `WHERE FM_AGG_METHOD = 'SUM'` filter — so only VOLUME and VALUE metrics. For RATIO/AVERAGE/DERIVED, query `FACT_METRIC` directly.

### Rule 4: Pivot multi-metric to wide format in queries

```sql
SELECT FM_PERIODE_LABEL,
       SUM(CASE WHEN FM_METRIC_NAME = 'GROSS_SALES' THEN FM_DISPLAY_VALUE END) AS gross,
       SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES'  THEN FM_DISPLAY_VALUE END) AS net,
       SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'      THEN FM_DISPLAY_VALUE END) AS margin
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_METRIC_NAME IN ('GROSS_SALES','NETT_SALES','MARGIN')
GROUP BY FM_PERIODE_LABEL;
```

## Common Query Recipes

### Recipe 1: KPI card value

```sql
-- Total margin this month
SELECT SUM(FM_DISPLAY_VALUE) AS total
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_METRIC_NAME = 'MARGIN'
  AND FM_PERIODE_LABEL = '202605';
```

### Recipe 2: Multi-series line chart

```sql
SELECT FM_PERIODE_LABEL,
       SUM(CASE WHEN FM_METRIC_NAME = 'GROSS_SALES' THEN FM_DISPLAY_VALUE END) AS gross_sales,
       SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES'  THEN FM_DISPLAY_VALUE END) AS nett_sales,
       SUM(CASE WHEN FM_METRIC_NAME = 'COST_PROD'   THEN FM_DISPLAY_VALUE END) AS cost_prod,
       SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'      THEN FM_DISPLAY_VALUE END) AS margin
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES'
  AND FM_METRIC_NAME IN ('GROSS_SALES','NETT_SALES','COST_PROD','MARGIN')
  AND FM_PERIODE_DATE >= NOW() - INTERVAL '12 months'
GROUP BY FM_PERIODE_LABEL
ORDER BY FM_PERIODE_LABEL;
```

### Recipe 3: Margin % by category

```sql
SELECT FM_GROUP_2 AS category,
       ROUND(
         SUM(CASE WHEN FM_METRIC_NAME = 'MARGIN'     THEN FM_DISPLAY_VALUE END) /
         NULLIF(SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES' THEN FM_DISPLAY_VALUE END), 0) * 100,
         2
       ) AS margin_pct
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES' AND FM_PERIODE_LABEL = '202605'
GROUP BY FM_GROUP_2
ORDER BY margin_pct DESC NULLS LAST;
```

### Recipe 4: Per-unit weighted average

```sql
-- Avg net sales per PCS, by delivery type
SELECT FM_GROUP_1 AS delivery_type,
       SUM(CASE WHEN FM_METRIC_NAME = 'NETT_SALES' THEN FM_DISPLAY_VALUE END) /
       NULLIF(SUM(CASE WHEN FM_METRIC_NAME = 'QUANTITY' THEN FM_DISPLAY_VALUE END), 0)
         AS avg_net_per_pcs
FROM FACT_METRIC
WHERE FM_TYPE = 'SALES' AND FM_PERIODE_LABEL = '202605'
GROUP BY FM_GROUP_1;
```

### Recipe 5: JOIN with METRIC_REGISTRY for display labels

```sql
-- API response with proper labels
SELECT fm.FM_METRIC_NAME AS code,
       mr.MR_DISPLAY_LABEL AS label,
       mr.MR_UOM,
       mr.MR_NUMBER_FORMAT,
       SUM(fm.FM_DISPLAY_VALUE) AS value
FROM FACT_METRIC fm
JOIN METRIC_REGISTRY mr ON fm.FM_METRIC_NAME = mr.MR_METRIC_NAME
WHERE fm.FM_TYPE = 'SALES'
  AND fm.FM_PERIODE_LABEL = '202605'
  AND fm.FM_METRIC_NAME IN ('GROSS_SALES','NETT_SALES','MARGIN')
GROUP BY 1, 2, 3, 4;
```

### Recipe 6: MoM and YoY in one query

```sql
WITH MONTHLY AS (
  SELECT FM_PERIODE_DATE, SUM(FM_DISPLAY_VALUE) AS value
  FROM FACT_METRIC
  WHERE FM_TYPE = 'SALES'
    AND FM_METRIC_NAME = 'MARGIN'
  GROUP BY FM_PERIODE_DATE
)
SELECT FM_PERIODE_DATE,
       value AS current_margin,
       LAG(value, 1)  OVER (ORDER BY FM_PERIODE_DATE) AS mom_prev,
       LAG(value, 12) OVER (ORDER BY FM_PERIODE_DATE) AS yoy_prev,
       ROUND((value - LAG(value, 1) OVER (ORDER BY FM_PERIODE_DATE)) /
              NULLIF(ABS(LAG(value, 1) OVER (ORDER BY FM_PERIODE_DATE)), 0) * 100, 2) AS mom_pct,
       ROUND((value - LAG(value, 12) OVER (ORDER BY FM_PERIODE_DATE)) /
              NULLIF(ABS(LAG(value, 12) OVER (ORDER BY FM_PERIODE_DATE)), 0) * 100, 2) AS yoy_pct
FROM MONTHLY
ORDER BY FM_PERIODE_DATE DESC;
```

## ETL Pattern (Python)

```python
from src.transformer_multi import expand_multi_metric, MODULE_CONFIG

# For each Oracle row with 6 value columns → 6 FACT_METRIC tuples
for oracle_row in oracle_cursor:
    for fact_tuple in expand_multi_metric(
        oracle_row,
        MODULE_CONFIG['DELIVERY_MARGIN'],
        source_id=1
    ):
        tuples.append(fact_tuple)

# Bulk UPSERT
execute_values(cursor, PG_UPSERT_FACT_METRIC_V11, tuples, page_size=500)
```

Oracle row needs lowercase keys matching `MODULE_CONFIG` mapping:
- `group_1`, `group_2`, `group_3`, `group_1_order`, `group_2_order`, `group_3_order`
- `periode_date`, `periode_label`
- `quantity`, `gross_sales`, `nett_sales`, `selling_cost`, `cost_prod`, `margin`

## Decision Tree: Which Pattern?

```
                  ┌─────────────────────────┐
                  │  Adding new module?     │
                  └────────────┬────────────┘
                               │
               ┌───────────────┴───────────────┐
               │                               │
           ┌───▼────┐                    ┌─────▼─────┐
           │ 1 value│                    │ 2+ values │
           │per dim │                    │ per dim   │
           └───┬────┘                    └─────┬─────┘
               │                               │
       ┌───────▼──────────┐          ┌─────────▼─────────┐
       │ Pattern A         │          │ Pattern B         │
       │ METRIC_NAME='VALUE'│          │ METRIC_NAME=specific│
       │ (default)          │          │                   │
       │ AGG_METHOD='SUM'   │          │ AGG_METHOD per metric│
       └───────────────────┘          └───────────────────┘

       Examples:                       Examples:
       • EBITDA                        • Delivery Margin
       • Net Profit                    • Inventory
       • Cash Flow                     • Production
       • P&L line items                • HR / Overtime
```

## Field Reference Card

| Column | Pattern A (EBITDA) | Pattern B (Delivery) |
|--------|--------------------|-----------------------|
| `FM_TYPE` | `'MIS'` | `'SALES'` |
| `FM_GROUP_1` | `'EBITDA'`, `'NET PROFIT'` | `'Export'`, `'Local'`, `'Popcorn'`, `'JobWork'` |
| `FM_GROUP_2` | `'INCOME'`, `'PRODUCTION COST'` | `'FG'`, `'ACY'`, `'ATY'` |
| `FM_GROUP_3` | `'LOCAL SALES'`, `'CHIPS COST'` | `'PTY'`, `'TCY'`, `'ACY'` |
| `FM_METRIC_NAME` | `'VALUE'` (always) | `'QUANTITY'`, `'GROSS_SALES'`, `'MARGIN'`, etc. |
| `FM_METRIC_CATEGORY` | `'VALUE'` (always) | `'VOLUME'` (QTY), `'VALUE'` (currency) |
| `FM_AGG_METHOD` | `'SUM'` (always) | `'SUM'` |
| `FM_UOM` | `'USD'` | `'PCS'` (QTY), `'USD'` (currency) |
| `FM_VALUE` | Raw accounting value | Raw value from source |
| `FM_DISPLAY_VALUE` | Sign-flipped for income | Same as `FM_VALUE` (no flip for SALES) |

## Common Pitfalls

### Pitfall 1: Forgetting METRIC_NAME filter
Without filter, you sum apples + oranges (QUANTITY pieces + USD).

### Pitfall 2: Hardcoding display labels in code
❌ `if metric == 'Gross Sales'` — fragile, breaks if label changes
✅ `if metric_name == 'GROSS_SALES'` — uses code, label fetched from `METRIC_REGISTRY`

### Pitfall 3: Materialized view assumption
MV excludes RATIO/AVERAGE/DERIVED. If your dashboard needs those, query `FACT_METRIC` directly.

### Pitfall 4: Sign convention difference between modules
- MIS (EBITDA): income raw negative → flipped for display
- SALES (Delivery): all values stored as-is (no flip)

ETL transformer respects `sign_flip_groups` config per module.

### Pitfall 5: Hardcoded metric lists
❌ Hardcoding `['QUANTITY', 'GROSS_SALES', ...]` in code
✅ Reading from `dashboard_config.DC_CHART_CONFIG.metric_filter.include_metrics`

---

**Test evidence**: 18 unit tests for transformer.py + 11 unit tests for transformer_multi.py + 2 E2E validations against real Excel data. All passing.

**Questions?** Reference `PRD_Addendum_v1.1_FINAL.md` Section 7 (Query Patterns).
