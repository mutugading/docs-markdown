# ENG-MB-02 — Nested-MB upstream cost resolves via `cpc_captive_cost` (yarn-only), yielding RM cost = 0 for MB-in-MB references

| Field | Value |
|---|---|
| **Defect ID** | ENG-MB-02 |
| **Severity** | High — silently understates RM cost for every multi-level MB |
| **Type** | Engine defect (cost calculation), NOT data / onboarding |
| **Component** | `services/finance` — cost calculation engine |
| **Repo / commit** | `mutugading/goapps-backend` @ `3d2d8c8` |
| **Reported by** | Reconciliation MB 202607 (Indra) |
| **Status** | Confirmed via code + live data |

---

## Summary

When an MB recipe references **another MB product as a nested component**
(`mst_mb_composition.mbcm_source_type = 'MB'`, surfaced in the route as a
`PRODUCT`-type RM), the engine resolves that upstream MB's per-unit cost from
`cst_product_cost.cpc_captive_cost`. For MB products this column is **NULL**
(captive cost is a yarn-only concept). A `COALESCE(..., 0)` then turns the
missing value into **0** instead of raising the "not calculated yet" error, so
the parent MB silently loses the entire nested contribution from its RM cost.

The parent still produces a cost row (no error, no BLOCKED status) — the number
is simply wrong.

---

## Reproduction (live, period 202607, ACTUAL)

Product **MGT RED 3527-D-05096-B** (`cpc_product_sys_id = 40895`), a 2-component
recipe:

| seq | ref | type | ratio |
|---|---|---|---|
| 1 | PIG group `202503832` | GROUP | 0.30 |
| 2 | SPC SUPERFAST PINK (`product:40418`) | PRODUCT (nested MB) | 0.70 |

Engine output vs legacy:

| | new | legacy |
|---|---|---|
| RM cost | **18.963750** | 27.679809 |
| final | **21.764989** | 30.979109 |

`rm_cost_detail` of RED shows the smoking gun:

```
ref_code=202503832   unit_cost=63.2125   ratio=0.30   -> 18.96  (GROUP resolves fine)
ref_code=product:40418 unit_cost=0        ratio=0.70   -> 0      (nested MB = 0)  ← BUG
```

The upstream product itself is healthy:

```sql
SELECT cpc_cost_per_unit, cpc_captive_cost, cpc_status
FROM cst_product_cost
WHERE cpc_product_sys_id = 40418 AND cpc_period='202607' AND cpc_calculation_type='ACTUAL';
-- cpc_cost_per_unit = 12.451513
-- cpc_captive_cost  = NULL          ← the value the engine reads
-- cpc_status        = CALCULATED
```

**Arithmetic proof the composition/onboarding is correct** — plug the real
upstream cost back in:

```
RM = (63.2125 × 0.30) + (12.451513 × 0.70)
   = 18.963750 + 8.716059
   = 27.679809   ==  legacy RM 27.67980904   ✓ (matches to 1e-6)
```

So the recipe, ratios, and group cost are all correct. The only wrong number is
the nested-MB unit cost (0 instead of 12.4515).

---

## Root cause (code)

`services/finance/internal/application/costcalc/loader.go` → `LoadUpstreamCosts`:

```go
const q = `
    SELECT cpc_product_sys_id, COALESCE(cpc_captive_cost, 0)
    FROM cst_product_cost
    WHERE cpc_product_sys_id = ANY($1)
      AND cpc_period = $2
      AND cpc_calculation_type = $3
      AND cpc_status <> 'SUPERSEDED'`
```

- The map `UpstreamCosts[productSysID]` is populated **only** from
  `cpc_captive_cost` (= `CAPTIVE_COST_QLTY_LOSS`, cost-sheet row 61 — a *yarn*
  concept).
- MB products never populate `cpc_captive_cost` (MB has no captive/quality-loss
  cascade), so the value is NULL.
- `COALESCE(cpc_captive_cost, 0)` maps NULL → 0 and keeps the map entry present.

Consumer — `services/finance/internal/application/costcalc/compute.go`
→ `resolveRMUnitCost`:

```go
case costroute.RmTypeProduct:
    cost, ok := in.UpstreamCosts[rm.RmProductSysID]
    if !ok {
        return 0, fmt.Errorf("%w: upstream product %d", ErrMissingUpstreamCost, ...)
    }
    return cost, nil    // cost == 0 for MB, ok == true -> no error
```

Because `COALESCE` kept the entry present, `ok == true` and `cost == 0`. The
guard that would have flagged the product as BLOCKED
(`ErrMissingUpstreamCost`) never fires. The zero flows straight into RM cost.

The `LoadUpstreamCosts` doc-comment explicitly states it returns
`CAPTIVE_COST_QLTY_LOSS` and calls `cpc_cost_per_unit` the "wrong source" — but
that reasoning is **yarn-specific**. For a **nested MB** upstream, `cpc_cost_per_unit`
is exactly the right source, and `cpc_captive_cost` is the wrong (empty) one.
The loader does not distinguish nested-yarn from nested-MB upstream references.

---

## Impact

- Every MB whose recipe references another MB (`mbcm_source_type='MB'`) under-counts
  RM cost by the full nested contribution.
- Period 202607 reconciliation: the nested-MB references dominate the residual
  `mismatch_rm`. Top offenders by product count (from `rm_cost_detail` scan,
  `unit_cost=0`, `ref_code LIKE 'product:%'`):

  | ref | products affected |
  |---|---|
  | product:39783 | 139 |
  | product:39798 | 106 |
  | product:39795 | 77 |
  | product:39789 | 9 |
  | product:39797 | 4 |
  | others | ~5 |

  ≈ **340 products** plus the newly-onboarded PEPON (40891) and RED (40895).
- Silent: no error, no BLOCKED status — the wrong cost is committed and pushed.

---

## Why push-to-head did not fix it

Push-to-head populates `cst_mb_cost` (the active cache used by **yarn** POY
downstream). Nested-MB resolution inside the **MB** engine does **not** read
`cst_mb_cost`; it reads `UpstreamCosts`, which is loaded from
`cst_product_cost.cpc_captive_cost`. Verified: `cst_mb_cost` for 40418/202607
was populated (12.451513, created 02:43), and RED was recalculated afterwards
(02:45) — yet the nested unit cost stayed 0. The push is irrelevant to this path.

---

## Proposed fix (engine)

Preferred — make `LoadUpstreamCosts` (or a MB-specific variant) read the correct
column for MB upstream references:

```sql
SELECT cpc_product_sys_id, COALESCE(cpc_cost_per_unit, 0)   -- for MB-type upstream
FROM cst_product_cost
WHERE cpc_product_sys_id = ANY($1)
  AND cpc_period = $2
  AND cpc_calculation_type = $3
  AND cpc_status <> 'SUPERSEDED'
```

Because MB and yarn upstream references can coexist, the correct column depends
on the **upstream product type**, not a global switch:

- upstream is MB (`cost_product_master.cpm_source = 'MB_RECIPE'` / product_type
  MB) → use `cpc_cost_per_unit`
- upstream is yarn → keep `cpc_captive_cost` (existing yarn behaviour)

Alternative — have MB auto-gen populate `cpc_captive_cost = cpc_cost_per_unit`
for MB products, so the existing loader reads a correct value. Simpler but
overloads a yarn-named column with MB semantics; prefer the type-aware read.

Either way: **do not** silently `COALESCE` a missing upstream to 0 for a product
that genuinely has no cost row — let `ErrMissingUpstreamCost` surface so the
product is BLOCKED rather than silently wrong. The zero-masking is a second,
independent hazard.

---

## Verification after fix

1. Recalculate MB 202607 in nested-MB dependency order (the `mbbatch` DAG already
   topo-sorts; ensure the parent recompute runs after the fix).
2. Re-check RED 3527:
   ```sql
   -- expect rm 27.6798, final 30.9791; nested unit_cost 12.4515
   ```
3. Re-run the 202607 rollup compare — expect `mismatch_rm` to drop by ≈340
   (all nested-MB products) plus PEPON/RED.

---

## Notes

- Onboarding of the 26 new MB products (incl. PEPON/RED) is **correct** — recipe,
  composition, ratios, routing all verified. This defect is downstream of
  onboarding, in the cost engine.
- Related but separate (do not fix here): `cst_mb_cost` SELLING is a constant
  `3.278983` across MBs — a distinct suspected push/selling defect, out of scope
  for ENG-MB-02.
