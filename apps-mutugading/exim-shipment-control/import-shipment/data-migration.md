# data-migration.md — full backfill of the legacy Shipping data

Decision **D2**: every `MGTAPPS.SHP_*` row is migrated into the new `MGTHRIS.ship_*` tables. After
cut-over the new app reads only its own tables; the legacy tables stay in place, read-only, as the
audit copy.

Source connection: the legacy schema (`MGTAPPS`) — reachable from this app because
`config/database.php` already exposes `oracle_mgtapps` (in some environments it is aliased to
`oracle_mgthris`; the backfill command takes the connection name as an option and refuses to run when
source and target resolve to the same schema).

---

## 1. Shape of the job

`php artisan finance:shipment:backfill` (`Modules/Finance/app/Console/Commands/Shipment/BackfillShipmentControl.php`)

| Option | Meaning |
|---|---|
| `--source=oracle_mgtapps` | legacy connection |
| `--from=` / `--to=` | limit by `sah_trans_date` / `sbh_trans_date` (for staged runs) |
| `--direction=EXPORT\|IMPORT` | one side at a time |
| `--chunk=500` | provisions per transaction |
| `--dry-run` | build and validate everything, roll back at the end, still write the report |
| `--resume` | skip source rows that already have a `*_legacy_sys_id` row in the target |

Rules the command obeys:

- **Idempotent.** Every target row carries `*_legacy_sys_id`; a second run with `--resume` inserts
  nothing new. Without `--resume` it refuses to start when the target already has backfilled rows.
- **Chunked transactions.** One transaction per chunk of provisions (with all their children), so a
  failure halfway leaves whole documents, never half a document.
- **Own log channel** `shipment_backfill` in `config/logging.php` (project standard for batch work),
  plus a per-run summary written to `storage/app/shipment-backfill/{timestamp}/`.
- **No model events, no activity log.** Backfilled rows are history; `withoutEvents()` and
  `activity()->disableLogging()` wrap the run so the audit trail is not filled with 100k fake changes.
- **Never derives money.** Amounts are copied as stored, not recalculated — the legacy figures are what
  Finance reconciled and reported on. Only `spi_total_base` / `spv_total_*` (which legacy left null or
  inconsistent) are computed, and the run reports every provision where a computed rollup disagrees with
  the legacy children by more than 0.01 instead of overwriting silently.

## 2. Ordering

```
0. Pre-flight: §5 data-quality queries. Any RED finding stops the run.
1. Masters (seeded, not backfilled — see §7):
   ship_cost_type, ship_container_type, ship_activity, ship_tariff,
   ship_port, ship_facility, ship_posting_account, ship_parameter
2. ship_provision            (SHP_ARR_HEAD, both trans codes)
3. ship_provision_container  (SHP_ARR_CONT)
4. ship_provision_doc        (SHP_ARR_PO_HEAD)
5. ship_provision_invoice    (SHP_ARR_INV)
6. ship_provision_cost       (SHP_ARR_DET)
7. ship_bill                 (SHP_BILL_HEAD, both trans codes)
8. ship_bill_container / _doc (only for DIRECT bills)
9. ship_bill_invoice         (SHP_BILL_INV)
10. ship_bill_cost           (SHP_BILL_DET) — resolves stc_spc_sys_id via the id map
11. Rollups: recompute invoice + header totals, report mismatches
12. Provision closure: set spv_status = 4 where nothing is unbilled and the legacy status was 3/4
13. Unique constraints migration (schema.md §12, file 21)
14. Verification: php artisan finance:shipment:verify-backfill
```

Steps 2–10 write `ship_legacy_map` rows as they go (see §3). The whole run needs the legacy→new id map
of provisions before invoices, and of provision cost lines before bill cost lines — hence the order.

## 3. `ship_legacy_map`

| Column | Notes |
|---|---|
| `SLM_SYS_ID` | PK |
| `SLM_ENTITY` | `PROVISION`, `PROVISION_INVOICE`, `PROVISION_COST`, `PROVISION_CONTAINER`, `PROVISION_DOC`, `BILL`, `BILL_INVOICE`, `BILL_COST`, … |
| `SLM_LEGACY_TABLE` | `SHP_ARR_HEAD`, … |
| `SLM_LEGACY_SYS_ID` | source PK |
| `SLM_NEW_SYS_ID` | target PK |
| `SLM_RUN_ID` | the backfill run that produced it |
| audit ×4 | |

`UQ` (`SLM_ENTITY`, `SLM_LEGACY_SYS_ID`). The `*_legacy_sys_id` columns on the tables themselves are
what production code uses; this table is the run's own bookkeeping and the place to look when a
reconciliation fails.

## 4. Field-level mapping

The column-by-column mapping is `gap-analysis.md` §2 and is the specification for this job. The
non-mechanical decisions:

| Case | Rule |
|---|---|
| **Direction** | `sah_trans_code = 'SHPEXP'` → `EXPORT`, `'SHPARR'` → `IMPORT`. `sbh_trans_code = 'EXPBILL'` → `EXPORT`, `'IMPBILL'` → `IMPORT`. Anything else → error row, not a guess. |
| **Flex columns** | Mapped **by direction** (a wrong direction silently writes an SI number into a Pabean field, which is why the pre-flight asserts every head has a known trans code). |
| **Party** | `sah_supp_code` → `spv_customer_code` when `EXPORT`, `spv_supplier_code` when `IMPORT`. |
| **Port** | Import only. `sah_flex_1` (Pabean code) → `spv_port_code`, matched against `ship_port` after trimming, upper-casing and left-padding to 6 digits (`40300` → `040300`); `sah_flex_2` (Pelabuhan) → `spv_port` verbatim. A code that matches no port row is **not** written to `spv_port_code` (the FK would fail): it goes to `spv_port` prefixed with `?` and the row is listed in the report, so Finance can decide whether it is a typo or a fourth port. Bill rows follow the same rule into `stl_port_code` / `stl_port`. |
| **Invoice cost type** | export: `sai_flex_2`; import: `sah_cost_type`. Null or unknown → `UNKNOWN`, seeded as an inactive cost type so history renders and new documents cannot pick it. |
| **Bill source** | `sbh_sah_sys_id IS NULL` → `DIRECT`, else `PROVISION`. |
| **Payment voucher** | `NVL(sah_vcr_adv, sah_vcr_pay)` → `spv_pay_voucher`; when both are set and differ, the run reports the row and keeps `sah_vcr_adv`. |
| **Pay date** | copied only when the corresponding voucher column is non-null; otherwise null (behaviour change B2). Every row where this drops a date is listed in the report. |
| **Doc type** | export → `EDN`; import → `PO`, except `saph_po_no = 'SAMPLE'` → `SAMPLE`. |
| **Container qty** | null → 0 for `LCL`, else reported. |
| **Activity name** | copied to `*_activity_name`; the code is matched against `ship_activity` and unmatched codes are auto-created **inactive** so nothing is lost. |
| **Facility** | same: unmatched `sad_fasilitas` values are created with `shf_auto_registered = 1`, `shf_active = 0`. |
| **Trans no.** | copied as-is. After the run, the `HmMstSequences` rows for `SHIP_PROVISION` / `SHIP_BILL` are advanced past the highest migrated number for the current year — otherwise the first new document collides. **This step is easy to forget and breaks go-live.** |
| **Audit columns** | legacy `*_uid` / `*_date` columns → `*_created_by` / `*_created_timestamp`; missing → `'BACKFILL'` and the run timestamp. |

## 5. Pre-flight queries (run against the legacy schema)

Each returns rows only when there is a problem. RED = must be fixed or explicitly waived before the
real run; AMBER = recorded in the report and migrated as-is.

```sql
-- RED 1. Unknown trans code (would mis-map every flex column)
SELECT sah_sys_id, sah_trans_code FROM shp_arr_head
 WHERE sah_trans_code NOT IN ('SHPEXP','SHPARR');
SELECT sbh_sys_id, sbh_trans_code FROM shp_bill_head
 WHERE sbh_trans_code NOT IN ('EXPBILL','IMPBILL');

-- RED 2. Duplicate vendor invoice number (blocks the new unique index)
SELECT UPPER(sbi_inv_no) inv, sbi_vnd_code, COUNT(*) c
  FROM shp_bill_inv GROUP BY UPPER(sbi_inv_no), sbi_vnd_code HAVING COUNT(*) > 1;

-- RED 3. Two bill lines settling one provision line (blocks the new unique index)
SELECT sbd_sad_sys_id, COUNT(*) c FROM shp_bill_det
 WHERE sbd_sad_sys_id IS NOT NULL GROUP BY sbd_sad_sys_id HAVING COUNT(*) > 1;

-- RED 4. Orphans
SELECT sai_sys_id FROM shp_arr_inv i
 WHERE NOT EXISTS (SELECT 1 FROM shp_arr_head h WHERE h.sah_sys_id = i.sai_sah_sys_id);
SELECT sad_sys_id FROM shp_arr_det d
 WHERE NOT EXISTS (SELECT 1 FROM shp_arr_inv i WHERE i.sai_sys_id = d.sad_sai_sys_id);
SELECT sbd_sys_id, sbd_sad_sys_id FROM shp_bill_det d
 WHERE d.sbd_sad_sys_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM shp_arr_det a WHERE a.sad_sys_id = d.sbd_sad_sys_id);
SELECT sbh_sys_id FROM shp_bill_head b
 WHERE b.sbh_sah_sys_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM shp_arr_head h WHERE h.sah_sys_id = b.sbh_sah_sys_id);

-- AMBER 5. Pay date without a payment voucher (dates that will be dropped)
SELECT sah_sys_id, sah_trans_no, sah_pay_date FROM shp_arr_head
 WHERE sah_pay_date IS NOT NULL AND sah_vcr_adv IS NULL AND sah_vcr_pay IS NULL;
SELECT sbh_sys_id, sbh_trans_no, sbh_pay_date FROM shp_bill_head
 WHERE sbh_pay_date IS NOT NULL AND sbh_vcr_pay IS NULL;

-- AMBER 6. Aju no. not 14 characters
SELECT sah_sys_id, sah_aju_no FROM shp_arr_head
 WHERE sah_aju_no IS NOT NULL AND LENGTH(sah_aju_no) <> 14;

-- AMBER 7. Cost types and activities not in any master
SELECT DISTINCT sah_cost_type FROM shp_arr_head;
SELECT DISTINCT sai_flex_2 FROM shp_arr_inv;
SELECT DISTINCT sad_act_code FROM shp_arr_det
 WHERE sad_act_code NOT IN (SELECT sa_code FROM shp_activities);
SELECT DISTINCT sad_fasilitas FROM shp_arr_det WHERE sad_fasilitas IS NOT NULL;

-- AMBER 8. Confirmable-but-incomplete: lines with no accounts
SELECT sad_sys_id FROM shp_arr_det
 WHERE sad_main_acnt IS NULL OR sad_prov_acnt IS NULL;

-- AMBER 9. Duplicate voucher references (would look like a double posting)
SELECT sah_vcr_prov, COUNT(*) FROM shp_arr_head
 WHERE sah_vcr_prov IS NOT NULL GROUP BY sah_vcr_prov HAVING COUNT(*) > 1;
```

## 6. Verification (`finance:shipment:verify-backfill`)

Writes one report; every check must pass before go-live.

| # | Check |
|---|---|
| V1 | Row counts: `SHP_ARR_HEAD` = `ship_provision`, and the same for all eight child tables (per direction, and split by "in scope" when a `--from/--to` window was used) |
| V2 | Per provision: `Σ sad_fc_amt` = `Σ spc_fc_amount`, same for `_lc_amt`, `ppn_amt`, `pph_amt` — to the cent |
| V3 | Per bill: the same over `SHP_BILL_DET` / `ship_bill_cost`, plus `sbd_prv_amt` / `sbd_diff` |
| V4 | Link integrity: `COUNT(sbd_sad_sys_id NOT NULL)` = `COUNT(stc_spc_sys_id NOT NULL)`, and every pair maps to the same legacy ids through `ship_legacy_map` |
| V5 | Status distribution identical per direction and document |
| V6 | Voucher references: the multiset of `sah_vcr_prov`, `sah_vcr_adv`/`_pay`, `sbh_vcr`, `sbh_vcr_pay` matches the new columns |
| V7 | Trans-no uniqueness in the target, and `HmMstSequences` is past the maximum for the current year |
| V8 | Unbilled-line count per confirmed provision matches the legacy `whereDoesntHave('billDet')` count — this is what the settlement picker shows, so a mismatch is immediately visible to users |
| V9 | Spot check: 20 provisions + 20 bills across both directions rendered side by side, legacy vs new (PRD S2) |
| V10 | No `ship_*` row has a null in a column the new validation calls required, **for documents at status < 3** (history may be incomplete; live work may not) |

## 7. Master seeding (not a backfill)

The masters are **seeded from the legacy values plus the constants found in legacy code**, because most
of them never existed as data. Seeder:
`Modules/Finance/database/seeders/Shipment/ShipmentControlMasterSeeder.php`.

### `ship_cost_type`

| Code | Direction | Journal | Bill | Payment | Closes | Vendor | Vendor bank | Currency | Duty cols | Auto tariff |
|---|---|---|---|---|---|---|---|---|---|---|
| `EDN` | EXPORT | EPJV | EBJV | ADVP-EXP | 0 | 1 | 1 | — | 0 | 0 |
| `SHIP` | BOTH | EPJV/IPJV | EBJV/IBJV | ADVP | import: 1 | 1 | 1 | — | 0 | 0 |
| `EMKL` | BOTH | EPJV/IPJV | EBJV/IBJV | — | 0 | 1 | **0** | — | 0 | **1** |
| `PIB` | IMPORT | — | — | BPS | **1** | **0** | 0 | **IDR** | **1** | 0 |
| `INS` | EXPORT | EPJV | EBJV | ADVP-EXP | 0 | 1 | 1 | — | 0 | 0 |
| `COMM` | EXPORT | EPJV | EBJV | ADVP-EXP | 0 | 1 | 1 | — | 0 | 0 |
| `UNKNOWN` | BOTH | — | — | — | 0 | 0 | 0 | — | 0 | 0 | *(inactive, backfill only)* |

`SHIP` and `EMKL` are `BOTH` but their voucher types differ per direction — so the voucher columns are
resolved as `direction + cost type`, i.e. these two get **one row per direction**. Seed them as
`(EXPORT, SHIP)`, `(IMPORT, SHIP)`, `(EXPORT, EMKL)`, `(IMPORT, EMKL)` with `shc_direction` set
accordingly, and keep `shc_code` unique per direction. `schema.md` §10 and the reference DDL already
apply this: PK `shc_sys_id`, `UQ (shc_code, shc_direction)`. `shc_direction = 'BOTH'` therefore only
makes sense for a cost type whose behaviour truly is identical on both sides — confirm in T002 (D6)
whether any row still needs it, or whether `BOTH` should be dropped from the allowed values.

### `ship_posting_account` seeds (from legacy constants)

| Purpose | Direction | Document | Cost type | Condition | Main | Sub |
|---|---|---|---|---|---|---|
| PROVISION | — | PROVISION | — | — | `208023` | — |
| PPN | — | — | — | `PPN_1_1` | `108005` | — |
| PPN | — | — | — | — | `108004` | — |
| PPH | EXPORT | BILL | — | — | `206005` | — |
| PPH | IMPORT | BILL | — | — | `206029` | — |
| EXPENSE | IMPORT | — | `PIB` | — | `401100` | — |
| DUTY_BM | IMPORT | — | `PIB` | — | `401100` | — |
| DUTY_PPN | IMPORT | — | `PIB` | — | `108007` | — |
| DUTY_PPH | IMPORT | — | `PIB` | — | `108000` | — |
| EXPENSE | IMPORT | — | — | activity `303` | `401110` | `401112` |
| EXPENSE | IMPORT | — | `EMKL` | — | `401130` | `401134` |
| EXPENSE | IMPORT | — | — | `HAS_PPH` | `401130` | `401134` |
| EXPENSE | IMPORT | — | — | `NO_PPH` | `401130` | `401141` |
| BANK | IMPORT | — | — | — | `100343` | — |

Export expense/provision accounts come from `ship_tariff` (legacy `smc_main_acnt` / `smc_sub_acnt` /
`smc_prov_acnt`), so no seed rows are needed beyond the provision default.

> These numbers are transcribed from the legacy documentation, not from a live database read.
> **Verify every one against production before the seeder ships** (task T004 acceptance).

### `ship_tariff`

Copied from `SHP_MASTER_COSTS` with `sht_direction = smc_level`, and:

- `sht_shape` inferred once, at migration time: activity name ending in `-I`/`-II`/`-III`/… → `TIER`
  (with `sht_tier_group` = the name without the suffix and `sht_tier_seq` = the roman numeral);
  a row whose `[min, max]` band is narrower than the full range and is the only row for its
  (vendor, container, activity) → `FIX`; otherwise `FLAT`.
- `sht_eff_from` = the row's created date (or `2000-01-01` when null), `sht_eff_to` = null.
- **`sht_port_code` starts null on every copied row** — legacy had no port on the tariff, so every
  existing rate is an "any port" rate and nothing re-prices on day one. Finance then splits the rows
  that actually differ per port: duplicate the row, set `sht_port_code`, adjust the rate. A per-port row
  beats the null row (`spec.md` §4.1), so the split is additive and reversible.
- The inference is printed for review; Finance signs off the resulting table before go-live, because
  this is the one place where a mis-classification changes future pricing.

### `ship_port` — the three ports

No legacy master to copy: the Pabean code lived in `sah_flex_1` and the port name in `sah_flex_2`, both
free text. Seed the three rows Finance uses, then reconcile:

```sql
-- what the legacy data actually contains, so the seed covers every value in use
SELECT TRIM(UPPER(sah_flex_1)) AS pabean_code,
       TRIM(sah_flex_2)        AS pelabuhan,
       COUNT(*)                AS rows_using_it,
       MIN(sah_trans_date)     AS first_used,
       MAX(sah_trans_date)     AS last_used
  FROM shp_arr_head
 WHERE sah_trans_code = 'SHPARR'
   AND sah_flex_1 IS NOT NULL
 GROUP BY TRIM(UPPER(sah_flex_1)), TRIM(sah_flex_2)
 ORDER BY rows_using_it DESC;
```

Expect these three codes plus a tail of typos:

| `SHP_CODE` | `SHP_NAME` | `SHP_OFFICE` | `SHP_OFFICE_TYPE` | `SHP_TRANSPORT_MODE` | `SHP_CITY` | default |
|---|---|---|---|---|---|---|
| `040300` | Tanjung Priok | KPU Bea dan Cukai | `A` | `SEA` | Jakarta | ✔ |
| `060100` | Tanjung Emas | KPPBC | `Madya Pabean` | `SEA` | Semarang | |
| `050100` | Soekarno-Hatta | KPU Bea dan Cukai | `C` | `AIR` | Tangerang | |

The office type is stored without the word "Tipe" — the label is composed for display
(`KPU Bea dan Cukai Tipe A — Tanjung Priok`).

The codes carry a **leading zero** and are stored as strings — `040300`, not `40300`. Anywhere the code
is read from a spreadsheet or written to one, force it to text, the same rule the project already
applies to NIK columns; and when the backfill matches legacy `sah_flex_1` values, left-pad a 5-digit
value to 6 before comparing, because that is the shape a spreadsheet round-trip leaves behind.

Tanjung Priok is seeded as the default. Every remaining distinct value from the query above is either
mapped to one of the three (a typo, or an unpadded code) or waived, and that decision list is attached
to the backfill report.

### `ship_activity` / `ship_container_type` / `ship_facility`

Straight copy from `ShpActivities` and `ShpMasters` (`type = 'CONT'` / `'FASILITAS'`), plus
`shk_requires_qty = 0` for `LCL`.

### `ship_parameter`

`spec.md` §6, with `EDN_START_NO` read from the legacy `ShpMasters` row.

## 8. Cut-over runbook

1. **T-14d** — pre-flight (§5) on production; RED findings resolved with Finance.
2. **T-7d** — full `--dry-run` into a copy schema; verification (§6) green; spot checks (V9) signed off
   by Finance; master seeds (§7) reviewed, especially the tariff shapes and the account numbers.
3. **T-1d** — freeze legacy master-data edits.
4. **T-0, out of hours**
   1. Legacy Shipping screens made read-only (permission removed in the legacy app).
   2. `php artisan finance:shipment:backfill` for real (both directions).
   3. Advance the `HmMstSequences` rows.
   4. Run the unique-constraints migration.
   5. `php artisan finance:shipment:verify-backfill` — all checks green, or roll back (§9).
   6. Grant the new permissions to the Exim and Finance roles.
5. **T+1d..T+30d** — daily verification run; legacy kept read-only; keep the legacy screens reachable
   for read-only comparison.
6. **T+30d** — legacy Shipping routes removed.

## 9. Rollback

The backfill only inserts, and only into the new tables. Rollback is:

```sql
DELETE FROM ship_bill_cost;       DELETE FROM ship_bill_invoice;
DELETE FROM ship_bill_doc;        DELETE FROM ship_bill_container;   DELETE FROM ship_bill;
DELETE FROM ship_provision_cost;  DELETE FROM ship_provision_invoice;
DELETE FROM ship_provision_doc;   DELETE FROM ship_provision_container; DELETE FROM ship_provision;
DELETE FROM ship_legacy_map;
```

Scoped by `SLM_RUN_ID` / `*_legacy_sys_id IS NOT NULL` when a partial rollback is wanted — never touch
rows with a null `*_legacy_sys_id`, those were created in the new app. The command exposes this as
`finance:shipment:backfill --rollback={runId}`, which refuses to run once any backfilled document has
been modified in the new app (checked via `*_modified_timestamp`).

The legacy tables are never written by this migration, so the legacy app can be re-enabled at any point
during the window.
