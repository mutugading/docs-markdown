# schema.md — Shipment Control & Exim Front Office tables

Connection **`oracle_mgthris`** for every `ship_*` table (the module's own data). ERP tables on
`oracle_mgtdat` are never migrated — see `spec.md` §7.

**Two modules, one schema doc.** §1–§12 are the **Shipment Control** tables (provision, settlement and
their masters). §13–§15 are the **Exim Front Office** tables (BIM, import BL file, export SI, payment
request and six new masters). They share the connection, the naming conventions, the parameter table and
the posting-account resolver; they do not share a single prefix, so a mapping mistake between the two
cannot hide behind a familiar column name.

Conventions (root `CLAUDE.md`):

- Table names lowercase `snake_case`, singular-domain (`ship_provision`, `ship_bill`).
- **Every identifier is written lowercase and unquoted** — table, column, primary key, foreign key,
  unique constraint, index, trigger, view. Oracle folds unquoted identifiers, so the lowercase
  declaration still lands as `SPV_TRANS_NO` in MGTHRIS (`raw_material_items` proves it); let oci8 do the
  uppercasing.

  > **Not uppercase.** SQLite does not fold, and it returns result keys exactly as declared, while the
  > oci8 driver lowercases them. So an UPPERCASE declaration makes every model attribute read back
  > **null under SQLite** — the whole CI suite — while working perfectly on Oracle. LcControl's
  > migrations declare uppercase and get away with it only because little of it is covered by a
  > model-level test.

- **Oracle 11g caps every identifier at 30 characters**, and two consequences bite in this schema:

  1. **Name every constraint and index explicitly.** An unnamed one gets Blueprint's generated
     `{table}_{columns}_{type}`, and `ship_provision_container_spk_sys_id_primary` is 43 characters —
     it would fail on Oracle while passing CI, because SQLite has no cap. Naming convention:
     `pk_`, `uq_`, `fk_`, `ix_` + the table stem, abbreviating where needed
     (`ship_provision_container` → `ship_prov_cont`).
  2. **Do not use `->autoIncrement()` on the primary key.** On Oracle, yajra's `modifyIncrement` only
     queues an *unnamed* `primary()` — that is the 43-character name above — and creates no sequence,
     since the value comes from the trigger anyway. Declare a plain `integer` column plus an explicit
     `$table->primary('spv_sys_id', 'pk_ship_provision')`. SQLite still auto-assigns, because an
     `integer` column that is the primary key is a rowid alias there whether or not `AUTOINCREMENT`
     is present.

  `Modules/Finance/database/migrations/` identifiers were audited against both rules; the longest is
  `ix_ship_posting_acnt_lookup` at 27.
- Audit columns on every table: `*_created_by`, `*_created_timestamp`, `*_modified_by`,
  `*_modified_timestamp`. Models map `const CREATED_AT` / `UPDATED_AT` to them.
- Primary keys: `NUMBER` from a sequence, set by a `BEFORE INSERT` trigger via
  `MGTHRIS.PKG_HM_SEQUENCES.get_next_seq_no` (the LcControl pattern). Models:
  `$incrementing = true` (the default) and `$keyType = 'int'` — **not** `$incrementing = false`, which
  an earlier draft of this line said. With it false, Eloquent skips `insertGetId`, so `$model->getKey()`
  stays null after a `create()` and every child written through a relation lands with a null parent.
  True is also what LcControl does, and it works with the trigger: the oci8 driver reads the assigned
  value back through a `RETURNING` clause. The code-PK masters (`ship_container_type`, `ship_port`,
  `ship_facility`, `ship_parameter`) keep `$incrementing = false` with `$keyType = 'string'`.
- The human-readable transaction number is a **separate** column filled by
  `SysIdHelper::generate()` from an `HmMstSequences` row — never the PK.
- No `NUMBER` for money without scale: `decimal(18, 2)` for amounts, `decimal(18, 6)` for rates and
  percentages, `decimal(18, 4)` for quantities.
- Migrations are Blueprint (SQLite-compatible so CI passes) + a separate Oracle-only raw-SQL migration
  per trigger, guarded by `migrationDisabled()` and a `getDriverName() === 'sqlite'` early return.

## Prefix map

| Table | Prefix | PK | Model (`Modules\Finance\Models\MgtHris\Transaction\Shipment\…`) |
|---|---|---|---|
| `ship_provision` | `spv_` | `spv_sys_id` | `ShipProvision` |
| `ship_provision_invoice` | `spi_` | `spi_sys_id` | `ShipProvisionInvoice` |
| `ship_provision_cost` | `spc_` | `spc_sys_id` | `ShipProvisionCost` |
| `ship_provision_container` | `spk_` | `spk_sys_id` | `ShipProvisionContainer` |
| `ship_provision_doc` | `spd_` | `spd_sys_id` | `ShipProvisionDoc` |
| `ship_bill` | `stl_` | `stl_sys_id` | `ShipBill` |
| `ship_bill_invoice` | `sti_` | `sti_sys_id` | `ShipBillInvoice` |
| `ship_bill_cost` | `stc_` | `stc_sys_id` | `ShipBillCost` |
| `ship_bill_container` | `stk_` | `stk_sys_id` | `ShipBillContainer` |
| `ship_bill_doc` | `std_` | `std_sys_id` | `ShipBillDoc` |

Masters (`…\Models\MgtHris\Master\Shipment\…`):

| Table | Prefix | PK | Model |
|---|---|---|---|
| `ship_cost_type` | `shc_` | `shc_sys_id` + `UQ (shc_code, shc_direction)` | `ShipCostType` |
| `ship_container_type` | `shk_` | `shk_code` (string) | `ShipContainerType` |
| `ship_activity` | `sha_` | `sha_sys_id` | `ShipActivity` |
| `ship_tariff` | `sht_` | `sht_sys_id` | `ShipTariff` |
| `ship_port` | `shp_` | `shp_code` (string) | `ShipPort` |
| `ship_facility` | `shf_` | `shf_code` (string) | `ShipFacility` |
| `ship_posting_account` | `spa_` | `spa_sys_id` | `ShipPostingAccount` |
| `ship_parameter` | `shr_` | `shr_key` (string) | `ShipParameter` |
| `ship_legacy_map` | `slm_` | `slm_sys_id` | `ShipLegacyMap` (backfill only, see `data-migration.md`) |

Exim Front Office — transactions (`…\Models\MgtHris\Transaction\Shipment\…`), detailed in §13:

| Table | Prefix | PK | Model |
|---|---|---|---|
| `ship_bim_template` | `sbt_` | `sbt_sys_id` | `ShipBimTemplate` |
| `ship_bim` | `sbm_` | `sbm_sys_id` | `ShipBim` |
| `ship_bim_notify` | `sbn_` | `sbn_sys_id` | `ShipBimNotify` |
| `ship_bim_carrier_rule` | `sbc_` | `sbc_sys_id` | `ShipBimCarrierRule` |
| `ship_bim_freight_history` | `sbf_` | `sbf_sys_id` | `ShipBimFreightHistory` |
| `ship_import_file` | `sif_` | `sif_sys_id` | `ShipImportFile` |
| `ship_import_invoice` | `sii_` | `sii_sys_id` | `ShipImportInvoice` |
| `ship_import_item` | `sit_` | `sit_sys_id` | `ShipImportItem` |
| `ship_import_container` | `sic_` | `sic_sys_id` | `ShipImportContainer` |
| `ship_import_doc` | `sid_` | `sid_sys_id` | `ShipImportDoc` |
| `ship_export_si` | `ses_` | `ses_sys_id` | `ShipExportSi` |
| `ship_export_si_bim` | `seb_` | `seb_sys_id` | `ShipExportSiBim` |
| `ship_export_si_cost` | `sec_` | `sec_sys_id` | `ShipExportSiCost` |
| `ship_export_si_container` | `sek_` | `sek_sys_id` | `ShipExportSiContainer` |
| `ship_export_si_edn` | `sen_` | `sen_sys_id` | `ShipExportSiEdn` |
| `ship_export_si_doc` | `sed_` | `sed_sys_id` | `ShipExportSiDoc` |
| `ship_payment_request` | `spr_` | `spr_sys_id` | `ShipPaymentRequest` |
| `ship_notify_log` | `snl_` | `snl_sys_id` | `ShipNotifyLog` |

Exim Front Office — masters (`…\Models\MgtHris\Master\Shipment\…`), detailed in §14:

| Table | Prefix | PK | Model |
|---|---|---|---|
| `ship_hs_tariff` | `shh_` | `shh_sys_id` | `ShipHsTariff` |
| `ship_hs_preference` | `shr2_` | `shr2_sys_id` | `ShipHsPreference` |
| `ship_kurs_pajak` | `skp_` | `skp_sys_id` | `ShipKursPajak` |
| `ship_doc_type` | `sdt_` | `sdt_code` (string) | `ShipDocType` |
| `ship_transit_time` | `stt_` | `stt_sys_id` | `ShipTransitTime` |
| `ship_skb` | `ssk_` | `ssk_sys_id` | `ShipSkb` |

> **`shr2_` is deliberately ugly.** `shr_` is already taken by `ship_parameter`, and a prefix that is
> merely *similar* to an existing one is how a column ends up in the wrong table. Two characters of
> ugliness beat an hour of debugging; do not "tidy" it to `shp_` or `shr_`.
>
> Every one of these prefixes was checked against the Shipment Control set above. No collisions.

Every column carries its table's prefix without exception — primary key, foreign keys and the four audit
columns (`{PREFIX}_CREATED_BY`, `_CREATED_TIMESTAMP`, `_MODIFIED_BY`, `_MODIFIED_TIMESTAMP`). A foreign
key is named with **its own** table's prefix plus the target table's stem: `sii_sif_sys_id`, not
`sif_sys_id`.

Views over Orion (created by migration, read-only, no `ship_` prefix on columns — they expose the ERP
column names):

| View | Source | Used for |
|---|---|---|
| `v_ship_vendor` | `mgtdat.om_supplier` | vendor picker + NPWP / SKB expiry (`supp_flex_07..10`) |
| `v_ship_customer` | `mgtdat.om_customer` | customer picker (export) |
| `v_ship_company_bank` | `mgtdat.fm_bank_acnt_detail` | company bank list |

---

## 1. `ship_provision` (`spv_`)

One accrued-cost document for one shipment, either direction.

| Column | Type | Null | Notes |
|---|---|---|---|
| `SPV_SYS_ID` | number | no | PK, trigger `SHIP_PROVISION_SYS_ID_TRG` |
| `SPV_TRANS_NO` | varchar2(20) | no | unique **with `SPV_DIRECTION`**, not alone (Q12) — legacy ran a series per direction and 454 numbers exist in both; `SysIdHelper` sequence `SHIP_PROVISION` keeps the legacy `YYYY` + 6-digit shape |
| `SPV_DIRECTION` | varchar2(10) | no | `EXPORT` \| `IMPORT` — **replaces the trans-code discriminator** |
| `SPV_TRANS_DATE` | date | no | |
| `SPV_STATUS` | number(1) | no | default 0, `ShipStatusEnum` |
| `SPV_COST_TYPE` | varchar2(10) | no | FK → `ship_cost_type.shc_code` |
| `SPV_CUSTOMER_CODE` | varchar2(30) | yes | export: the ESC customer |
| `SPV_SUPPLIER_CODE` | varchar2(30) | yes | import: the goods supplier |
| `SPV_VENDOR_CODE` | varchar2(30) | yes | header vendor (forwarder / EMKL / customs). Null for a multi-vendor export provision — the vendor is then on the invoice |
| `SPV_VENDOR_NAME` | varchar2(200) | yes | snapshot at transaction time (vendor master lives in the ERP and is edited there) |
| `SPV_CURRENCY` | varchar2(3) | no | transaction currency |
| `SPV_EXCHANGE_RATE` | decimal(18,6) | no | default 1, USD→IDR for the trans date |
| `SPV_BANK_CURRENCY` | varchar2(3) | yes | currency the payment settles in |
| `SPV_IS_CROSS_CURRENCY` | number(1) | no | default 0; derived: bank currency ≠ transaction currency |
| `SPV_BANK_CODE` | varchar2(30) | yes | company bank (GL account) |
| `SPV_VENDOR_BANK_NAME` | varchar2(200) | yes | |
| `SPV_VENDOR_BANK_ACC` | varchar2(100) | yes | account holder |
| `SPV_VENDOR_BANK_NO` | varchar2(50) | yes | |
| `SPV_SI_NO` | varchar2(50) | yes | export — was `sah_flex_1` |
| `SPV_PEB_TYPE` | varchar2(50) | yes | export — was `sah_flex_2` |
| `SPV_DESTINATION` | varchar2(200) | yes | export — was `sah_flex_3` |
| `SPV_INCO_TERM` | varchar2(20) | yes | export — was `sah_flex_4`; drives the insurance formula |
| `SPV_EIN_NO` | varchar2(50) | yes | export invoice — was `sah_flex_5` |
| `SPV_PORT_CODE` | varchar2(20) | yes | import — the **Pabean code**, was `sah_flex_1`. FK → `ship_port.shp_code`. **Drives the EMKL tariff** (`spec.md` §4.1) |
| `SPV_PORT` | varchar2(100) | yes | import (Pelabuhan, free text) — was `sah_flex_2`. Descriptive only; the tariff never reads it |
| `SPV_SPTNP_NO` | varchar2(50) | yes | import — was `sah_flex_3` |
| `SPV_AJU_NO` | varchar2(14) | yes | import, exactly 14 chars (validated, not a DB check — legacy holds shorter junk) |
| `SPV_AJU_DATE` | date | yes | |
| `SPV_PIB_NO` | varchar2(10) | yes | import, ≤ 6 in practice |
| `SPV_PIB_DATE` | date | yes | |
| `SPV_SPPB_NO` | varchar2(50) | yes | import release note |
| `SPV_PL_NO` | varchar2(50) | yes | packing list |
| `SPV_BL_NO` | varchar2(50) | yes | bill of lading — **header level for both directions** (legacy kept the export one per line in `sad_flex_5`) |
| `SPV_INVOICE_NO` | varchar2(50) | yes | supplier invoice (import) |
| `SPV_INVOICE_DATE` | date | yes | |
| `SPV_REF_SYS_ID` | number | yes | self-FK: the provision this one was copied from |
| `SPV_DOC_REF` | varchar2(60) | yes | source document label, e.g. `ESC-12345`, `SHPARR-2024000123` |
| `SPV_SIF_SYS_ID` | number | yes | **front office** — FK → `ship_import_file`. The parent BL this provision accrues for. Null for a standalone provision, and that null is also the switch that keeps the transitional EMKL↔PIB copy rules alive (`spec.md` §5.4–5.5) |
| `SPV_SES_SYS_ID` | number | yes | **front office** — FK → `ship_export_si`. The parent Shipping Instruction |
| `SPV_SOURCE_TYPE` | varchar2(10) | yes | **front office** — `SI` \| `ESC` \| `MANUAL` \| `FILE`: where the figures were drawn from (`spec.md` §12.8). Distinguishes a commission line computed off the ESC from a freight line taken off the SI, without a sub-menu per source |
| `SPV_TOTAL_BASE` | decimal(18,2) | no | default 0 — rollups, recomputed on every save (see `spec.md` §4.4) |
| `SPV_TOTAL_PPN` | decimal(18,2) | no | default 0, always IDR |
| `SPV_TOTAL_PPH` | decimal(18,2) | no | default 0, always IDR |
| `SPV_TOTAL_FC` | decimal(18,2) | no | default 0, transaction currency |
| `SPV_TOTAL_LC` | decimal(18,2) | no | default 0, USD |
| `SPV_PROV_DATE` | date | yes | journal-voucher date |
| `SPV_PROV_VOUCHER` | varchar2(50) | yes | EPJV / IPJV reference `{tranCode}-{docNo}` |
| `SPV_PROV_VOUCHER_DATE` | date | yes | |
| `SPV_PAY_DATE` | date | yes | **only set when a payment voucher is actually raised** |
| `SPV_PAY_VOUCHER` | varchar2(50) | yes | ADVP / BPS reference |
| `SPV_PAY_VOUCHER_DATE` | date | yes | |
| `SPV_REVERSAL_VOUCHER` | varchar2(30) | yes | **T050** — the journal that took an unbilled accrual back off the books (`spec.md` §11.10). Kept apart from `SPV_PROV_VOUCHER` so the original is never overwritten: both documents exist in the ERP and both belong to this row |
| `SPV_REVERSAL_DATE` | date | yes | the period the reversal landed in — `PROVISION_REVERSAL_PERIOD` decides (F6) |
| `SPV_REVERSAL_REASON` | varchar2(500) | yes | why it was never billed |
| `SPV_REMARK` | varchar2(500) | yes | was `sah_flex_6` |
| `SPV_SUBMITTED_BY` / `_AT` | varchar2(30) / timestamp | yes | workflow stamps |
| `SPV_CONFIRMED_BY` / `_AT` | varchar2(30) / timestamp | yes | |
| `SPV_LEGACY_SYS_ID` | number | yes | the backfilled `sah_sys_id`; null for rows created in the new app |
| audit ×4 | | | `SPV_CREATED_BY`, `SPV_CREATED_TIMESTAMP`, `SPV_MODIFIED_BY`, `SPV_MODIFIED_TIMESTAMP` |

Indexes: `UQ` (`SPV_DIRECTION`, `SPV_TRANS_NO`) as `uq_ship_prov_dir_trans_no` (Q12) ·
`UQ` (`SPV_LEGACY_SYS_ID`) where not null ·
`IX` (`SPV_DIRECTION`, `SPV_STATUS`, `SPV_TRANS_DATE`) · `IX` (`SPV_AJU_NO`) · `IX` (`SPV_PORT_CODE`) ·
`IX` (`SPV_SI_NO`) · `IX` (`SPV_VENDOR_CODE`) · `IX` (`SPV_COST_TYPE`) ·
`IX` (`SPV_SIF_SYS_ID`) · `IX` (`SPV_SES_SYS_ID`) — one BL or one SI can produce several provisions in
different periods (`spec.md` §12.3), so these are read constantly by the cost sheet.

**Why one head instead of two (export/import) or three (common + 1:1 extensions):** the two directions
share ~35 of ~55 columns (identity, party, currency, bank, vendor bank, totals, vouchers, workflow,
audit). Splitting them duplicates the settlement link, the voucher plumbing and the reports; a
common+extension split makes every read a three-table join for no integrity gain, because the
per-direction requirements are conditional anyway (PIB needs a port code; export needs SI no.) and belong
in the DTO. Direction-specific columns are therefore nullable on one table, with a documented
"which columns apply per direction" table in `spec.md` §3.1 and DTO validation enforcing it.

---

## 2. `ship_provision_invoice` (`spi_`)

One row per **vendor × cost type** on a provision. Export legacy keyed these by vendor code and carried
the cost type in `sai_flex_2`; import legacy keyed them by bill number and took the cost type from the
head. New rule: **the cost type is on the invoice, always filled**, defaulting to the header's — which
removes every `NVL(invoice, head)` from the reports.

| Column | Type | Null | Notes |
|---|---|---|---|
| `SPI_SYS_ID` | number | no | PK, trigger |
| `SPI_SPV_SYS_ID` | number | no | FK → `ship_provision`, `on delete cascade` |
| `SPI_COST_TYPE` | varchar2(10) | no | FK → `ship_cost_type.shc_code` |
| `SPI_VENDOR_CODE` | varchar2(30) | no | |
| `SPI_VENDOR_NAME` | varchar2(200) | yes | snapshot |
| `SPI_INVOICE_NO` | varchar2(50) | yes | provisional bill no.; auto-generated for EMKL (`PROV <vendor> (<ts>)`) |
| `SPI_INVOICE_DATE` | date | yes | |
| `SPI_CARRIER_NAME` | varchar2(200) | yes | shipping company / shipper — was `sai_flex_1` |
| `SPI_CURRENCY` | varchar2(3) | no | |
| `SPI_EXCHANGE_RATE` | decimal(18,6) | no | default 1 |
| `SPI_TOTAL_BASE` | decimal(18,2) | no | default 0, Σ qty×rate |
| `SPI_TOTAL_PPN` | decimal(18,2) | no | default 0, IDR |
| `SPI_TOTAL_PPH` | decimal(18,2) | no | default 0, IDR |
| `SPI_TOTAL_FC` | decimal(18,2) | no | default 0 |
| `SPI_TOTAL_LC` | decimal(18,2) | no | default 0, USD |
| `SPI_TOTAL_CC` | decimal(18,2) | no | default 0, bank currency |
| `SPI_LEGACY_SYS_ID` | number | yes | backfilled `sai_sys_id` |
| audit ×4 | | | |

Indexes: `UQ` (`SPI_SPV_SYS_ID`, `SPI_COST_TYPE`, `SPI_VENDOR_CODE`) — one invoice per vendor per cost
type per provision, which is what both legacy keying schemes were trying to express ·
`UQ` (`SPI_LEGACY_SYS_ID`) where not null · `IX` (`SPI_VENDOR_CODE`).

> `sai_total` was deliberately null in legacy export because lines may mix currencies. Here
> `SPI_TOTAL_BASE` is the sum of base amounts **in the line currency** and is only meaningful when the
> invoice's lines share a currency; the mixed-currency case is why `SPI_TOTAL_LC` (USD) exists and is
> what reports should sum.

---

## 3. `ship_provision_cost` (`spc_`)

The cost lines.

| Column | Type | Null | Notes |
|---|---|---|---|
| `SPC_SYS_ID` | number | no | PK, trigger |
| `SPC_SPI_SYS_ID` | number | no | FK → `ship_provision_invoice`, cascade |
| `SPC_ACTIVITY_CODE` | varchar2(30) | no | FK → `ship_activity.sha_code` |
| `SPC_ACTIVITY_NAME` | varchar2(200) | yes | snapshot (tariff tiers rename over time) |
| `SPC_CONTAINER_TYPE` | varchar2(20) | yes | which container line produced this cost |
| `SPC_FACILITY_CODE` | varchar2(30) | yes | import customs facility — FK → `ship_facility` |
| `SPC_QTY` | decimal(18,4) | no | default 0 |
| `SPC_RATE` | decimal(18,4) | no | default 0 |
| `SPC_CURRENCY` | varchar2(3) | no | line currency (may differ from the header's) |
| `SPC_PPN_PCT` | decimal(18,6) | no | default 0 |
| `SPC_PPH_PCT` | decimal(18,6) | no | default 0 |
| `SPC_PPH_ADVANCED` | number(1) | no | default 0 — PPh paid in advance, so it is **not** deducted from FC |
| `SPC_BASE_AMOUNT` | decimal(18,2) | no | qty × rate |
| `SPC_PPN_AMOUNT` | decimal(18,2) | no | IDR |
| `SPC_PPH_AMOUNT` | decimal(18,2) | no | IDR |
| `SPC_FC_AMOUNT` | decimal(18,2) | no | line currency, net payable |
| `SPC_LC_AMOUNT` | decimal(18,2) | no | USD |
| `SPC_CC_AMOUNT` | decimal(18,2) | no | bank currency |
| `SPC_IS_CROSS_CURRENCY` | number(1) | no | default 0 |
| `SPC_DUTY_BM` | decimal(18,2) | yes | PIB: Bea Masuk — was `sad_pib_bm` |
| `SPC_DUTY_PPN` | decimal(18,2) | yes | PIB — was `sad_pib_ppn` |
| `SPC_DUTY_PPH` | decimal(18,2) | yes | PIB — was `sad_pib_pph` |
| `SPC_EXPENSE_MAIN_ACNT` | varchar2(20) | yes | required before confirming |
| `SPC_EXPENSE_SUB_ACNT` | varchar2(20) | yes | |
| `SPC_PROVISION_ACNT` | varchar2(20) | yes | required before confirming |
| `SPC_PPN_ACNT` | varchar2(20) | yes | required when `SPC_PPN_PCT` > 0 |
| `SPC_PPH_ACNT` | varchar2(20) | yes | required when `SPC_PPH_PCT` > 0 |
| `SPC_SI_NO` | varchar2(50) | yes | was `sad_flex_4` |
| `SPC_BL_NO` | varchar2(50) | yes | was `sad_flex_5` |
| `SPC_REMARK` | varchar2(500) | yes | was `sad_flex_6` |
| `SPC_TARIFF_SYS_ID` | number | yes | provenance: the `ship_tariff` row this line came from |
| `SPC_NO_TARIFF_MATCH` | number(1) | no | default 0. **1 = priced at zero because no tariff band matched the quantity** (`spec.md` §4.1). Without the flag the vendor's eventual bill looks like an uncommitted charge, when the master was simply one row short |
| `SPC_SEC_SYS_ID` | number | yes | **front office** — FK → `ship_export_si_cost`: the SI cost line this provision line was posted from. The reverse of `sec_spv_sys_id`, kept so the cost sheet can be read from either end |
| `SPC_REVERSED_FLAG` | number(1) | no | default 0. **T050** — the line's accrual has been reversed, so it is neither billable nor hanging (`spec.md` §11.10). On the line and not on the header because a provision routinely carries billed lines beside never-billed ones: "this document was reversed" is not a fact that exists |
| `SPC_LEGACY_SYS_ID` | number | yes | backfilled `sad_sys_id` — **the backfill's join key for bill lines** |
| audit ×4 | | | |

Indexes: `UQ` (`SPC_LEGACY_SYS_ID`) where not null · `IX` (`SPC_SPI_SYS_ID`) ·
`IX` (`SPC_ACTIVITY_CODE`) · `IX` (`SPC_NO_TARIFF_MATCH`) where = 1 — serves the "priced at zero"
report and nothing else, so a partial index is the right shape · `IX` (`SPC_REVERSED_FLAG`) — the
hanging queue filters on it on every run.

---

## 4. `ship_provision_container` (`spk_`)

| Column | Type | Null | Notes |
|---|---|---|---|
| `SPK_SYS_ID` | number | no | PK, trigger |
| `SPK_SPV_SYS_ID` | number | no | FK, cascade |
| `SPK_CONTAINER_TYPE` | varchar2(20) | no | FK → `ship_container_type.shk_code` |
| `SPK_QTY` | decimal(18,4) | no | default 0; not required for `LCL` |
| audit ×4 | | | |

`UQ` (`SPK_SPV_SYS_ID`, `SPK_CONTAINER_TYPE`).

---

## 5. `ship_provision_doc` (`spd_`)

The attached shipping documents: DO/EDN rows on export, PO rows on import. Legacy had one table
(`SHP_ARR_PO_HEAD`) doing both with overloaded columns; this keeps one table but names every column and
adds a type discriminator.

| Column | Type | Null | Notes |
|---|---|---|---|
| `SPD_SYS_ID` | number | no | PK, trigger |
| `SPD_SPV_SYS_ID` | number | no | FK, cascade |
| `SPD_DOC_TYPE` | varchar2(10) | no | `EDN` \| `PO` \| `SAMPLE` |
| `SPD_DOC_NO` | varchar2(50) | no | EDN no. / PO no. (`SAMPLE` for sample goods) |
| `SPD_ESC_NO` | varchar2(50) | yes | export sales contract |
| `SPD_STA_NO` | varchar2(50) | yes | export shipping advice |
| `SPD_INVOICE_NO` | varchar2(50) | yes | import: the supplier invoice this PO is booked under |
| `SPD_PO_VALUE` | decimal(18,2) | yes | import |
| `SPD_INVOICED_VALUE` | decimal(18,2) | yes | import |
| `SPD_REMAINING_VALUE` | decimal(18,2) | yes | import, = value − invoiced |
| `SPD_MAIN_ACNT` | varchar2(20) | yes | PO-carried expense account, overrides the tariff's |
| `SPD_LEGACY_SYS_ID` | number | yes | backfilled `saph_sys_id` |
| audit ×4 | | | |

`UQ` (`SPD_SPV_SYS_ID`, `SPD_DOC_TYPE`, `SPD_DOC_NO`) · `IX` (`SPD_DOC_NO`).

> The "an EDN may not be attached to two provisions" rule stays application-level (a query), not a
> unique index: legacy data contains a small number of deliberate exceptions, and re-shipments can
> legitimately reuse a document.

---

## 6. `ship_bill` (`stl_`)

The vendor bill / settlement.

| Column | Type | Null | Notes |
|---|---|---|---|
| `STL_SYS_ID` | number | no | PK, trigger |
| `STL_TRANS_NO` | varchar2(20) | no | unique **with `STL_DIRECTION`**, not alone (Q12) — 638 legacy numbers exist in both series; `SysIdHelper` sequence `SHIP_BILL` |
| `STL_DIRECTION` | varchar2(10) | no | `EXPORT` \| `IMPORT` |
| `STL_SOURCE` | varchar2(10) | no | `PROVISION` \| `DIRECT`, locked after first save |
| `STL_SPV_SYS_ID` | number | yes | FK → `ship_provision`; required when source = `PROVISION`. Kept even for multi-provision bills: it points at the **primary** provision, while the per-line links carry the full truth |
| `STL_TRANS_DATE` | date | no | |
| `STL_BILL_DATE` | date | yes | journal-voucher date |
| `STL_DUE_DATE` | date | yes | ≥ trans date |
| `STL_STATUS` | number(1) | no | default 0, `ShipStatusEnum` (4 = `SETTLED`) |
| `STL_COST_TYPE` | varchar2(10) | no | FK → `ship_cost_type` |
| `STL_VENDOR_CODE` / `_NAME` | varchar2(30) / (200) | yes / yes | |
| `STL_CURRENCY` | varchar2(3) | no | bill currency |
| `STL_EXCHANGE_RATE` | decimal(18,6) | no | default 1 |
| `STL_BANK_CURRENCY` | varchar2(3) | yes | |
| `STL_IS_CROSS_CURRENCY` | number(1) | no | default 0 |
| `STL_BANK_CODE` | varchar2(30) | yes | company bank |
| `STL_VENDOR_BANK_NAME` / `_ACC` / `_NO` | varchar2(200) / (100) / (50) | yes | |
| `STL_SI_NO` | varchar2(500) | yes | export; **500 chars** because a multi-provision bill concatenates them |
| `STL_PEB_TYPE` | varchar2(50) | yes | |
| `STL_DESTINATION` | varchar2(200) | yes | |
| `STL_INCO_TERM` | varchar2(20) | yes | |
| `STL_EIN_NO` | varchar2(500) | yes | concatenated likewise |
| `STL_PORT_CODE` | varchar2(20) | yes | import, Pabean code — FK → `ship_port.shp_code`, copied from the provision |
| `STL_PORT` | varchar2(100) | yes | import (Pelabuhan, free text) |
| `STL_AJU_NO` | varchar2(14) | yes | import |
| `STL_PIB_NO` | varchar2(10) | yes | import |
| `STL_BL_NO` | varchar2(50) | yes | |
| `STL_TOTAL_BASE` / `_PPN` / `_PPH` / `_FC` / `_LC` / `_CC` | decimal(18,2) | no | default 0, rollups |
| `STL_TOTAL_DIFF` | decimal(18,2) | no | default 0, Σ (provision − bill) |
| `STL_PAY_DATE` | date | yes | only when a payment voucher is raised |
| `STL_BILL_VOUCHER` | varchar2(50) | yes | EBJV / IBJV reference |
| `STL_BILL_VOUCHER_DATE` | date | yes | |
| `STL_PAY_VOUCHER` | varchar2(50) | yes | ADVP-EXP / ADVP / BPS reference |
| `STL_PAY_VOUCHER_DATE` | date | yes | |
| `STL_REMARK` | varchar2(500) | yes | |
| `STL_SUBMITTED_BY` / `_AT`, `STL_CONFIRMED_BY` / `_AT` | | yes | workflow stamps |
| `STL_LEGACY_SYS_ID` | number | yes | backfilled `sbh_sys_id` |
| audit ×4 | | | |

Indexes: `UQ` (`STL_DIRECTION`, `STL_TRANS_NO`) as `uq_ship_bill_dir_trans_no` (Q12) ·
`UQ` (`STL_LEGACY_SYS_ID`) where not null ·
`IX` (`STL_DIRECTION`, `STL_STATUS`, `STL_BILL_DATE`) · `IX` (`STL_SPV_SYS_ID`) ·
`IX` (`STL_VENDOR_CODE`) · `IX` (`STL_DUE_DATE`, `STL_PAY_DATE`) — the Bill report's `OVERDUE` filter.

---

## 7. `ship_bill_invoice` (`sti_`)

| Column | Type | Null | Notes |
|---|---|---|---|
| `STI_SYS_ID` | number | no | PK, trigger |
| `STI_STL_SYS_ID` | number | no | FK → `ship_bill`, cascade |
| `STI_COST_TYPE` | varchar2(10) | no | |
| `STI_VENDOR_CODE` / `_NAME` | varchar2(30) / (200) | no / yes | |
| `STI_INVOICE_NO` | varchar2(50) | no | the vendor's real bill number |
| `STI_INVOICE_DATE` | date | yes | |
| `STI_RECEIVED_DATE` | date | yes | "Tgl Received" on the Bill report |
| `STI_CARRIER_NAME` | varchar2(200) | yes | |
| `STI_CURRENCY` | varchar2(3) | no | |
| `STI_EXCHANGE_RATE` | decimal(18,6) | no | default 1 |
| `STI_TOTAL_BASE` / `_PPN` / `_PPH` / `_FC` / `_LC` / `_CC` / `_DIFF` | decimal(18,2) | no | default 0 |
| `STI_LEGACY_SYS_ID` | number | yes | backfilled `sbi_sys_id` |
| audit ×4 | | | |

Indexes: `UQ` (`STI_STL_SYS_ID`, `STI_INVOICE_NO`) ·
**`UQ` (`STI_VENDOR_CODE`, `UPPER(STI_INVOICE_NO)`)** — the duplicate-invoice guard legacy enforced only
in PHP (`checkInvNo()`). Created **after** the backfill; pre-existing duplicates are resolved with
Finance first (`data-migration.md` §5) · `UQ` (`STI_LEGACY_SYS_ID`) where not null.

---

## 8. `ship_bill_cost` (`stc_`)

| Column | Type | Null | Notes |
|---|---|---|---|
| `STC_SYS_ID` | number | no | PK, trigger |
| `STC_STI_SYS_ID` | number | no | FK → `ship_bill_invoice`, cascade |
| `STC_SPC_SYS_ID` | number | yes | **the provision cost line this settles** (was `sbd_sad_sys_id`); null on a DIRECT bill or a line added on top |
| `STC_ACTIVITY_CODE` / `_NAME` | varchar2(30) / (200) | no / yes | |
| `STC_QTY` | decimal(18,4) | no | default 1 — a provision-sourced line is qty 1 × the provision amount |
| `STC_RATE` | decimal(18,4) | no | default 0 |
| `STC_CURRENCY` | varchar2(3) | no | |
| `STC_PPN_PCT` / `STC_PPH_PCT` | decimal(18,6) | no | default 0 |
| `STC_PPH_ADVANCED` | number(1) | no | default 0 |
| `STC_BASE_AMOUNT` / `_PPN_AMOUNT` / `_PPH_AMOUNT` / `_FC_AMOUNT` / `_LC_AMOUNT` / `_CC_AMOUNT` | decimal(18,2) | no | default 0 |
| `STC_IS_CROSS_CURRENCY` | number(1) | no | default 0 |
| `STC_PROVISION_AMOUNT` | decimal(18,2) | no | default 0 — the provision figure (was `sbd_prv_amt`) |
| `STC_DIFF_AMOUNT` | decimal(18,2) | no | default 0 — provision − bill (was `sbd_diff`) |
| `STC_EXPENSE_MAIN_ACNT` / `_SUB_ACNT` / `STC_PROVISION_ACNT` / `STC_PPN_ACNT` / `STC_PPH_ACNT` | varchar2(20) | yes | main + sub both required on import (legacy rule) |
| `STC_FP_NO` | varchar2(50) | yes | Faktur Pajak, required when PPN > 0 |
| `STC_FP_DATE` | date | yes | |
| `STC_EIN_NO` | varchar2(50) | yes | was `sbd_flex_1` |
| `STC_STA_NO` | varchar2(50) | yes | was `sbd_flex_2` |
| `STC_ESC_NO` | varchar2(50) | yes | was `sbd_flex_3` |
| `STC_SI_NO` | varchar2(50) | yes | was `sbd_flex_4` |
| `STC_INVOICE_NO` | varchar2(50) | yes | was `sbd_flex_5` |
| `STC_REMARK` | varchar2(500) | yes | was `sbd_flex_6` |
| `STC_LEGACY_SYS_ID` | number | yes | backfilled `sbd_sys_id` |
| audit ×4 | | | |

Indexes: **`UQ` (`STC_SPC_SYS_ID`) where not null** — a provision line is settled at most once, which is
what legacy's `whereDoesntHave('billDet')` filter assumed but never enforced ·
`IX` (`STC_STI_SYS_ID`) · `IX` (`STC_FP_NO`) · `UQ` (`STC_LEGACY_SYS_ID`) where not null.

---

## 9. `ship_bill_container` (`stk_`) and `ship_bill_doc` (`std_`)

Same shape as `ship_provision_container` / `ship_provision_doc` with the FK pointing at
`ship_bill.stl_sys_id`. They exist for **DIRECT** bills, which have no provision to inherit from
(legacy `directCont()` / `directPo()`). A PROVISION-sourced bill leaves them empty and reads the
provision's.

---

## 10. Masters

### `ship_cost_type` (`shc_`) — replaces `ShpMasters(type='COST')` **and** the hardcoded per-cost-type branching

| Column | Type | Notes |
|---|---|---|
| `SHC_SYS_ID` | number PK | trigger-assigned |
| `SHC_CODE` | varchar2(10) | `PIB`, `EMKL`, `SHIP`, `INS`, `COMM`, `EDN`; unique **per direction**, because `SHIP` and `EMKL` raise different vouchers on each side (`data-migration.md` §7) |
| `SHC_NAME` | varchar2(100) | |
| `SHC_DIRECTION` | varchar2(10) | `EXPORT` \| `IMPORT` \| `BOTH` |
| `SHC_JOURNAL_VOUCHER` | varchar2(20) | provision JV type: `EPJV` / `IPJV` |
| `SHC_BILL_VOUCHER` | varchar2(20) | settlement JV type: `EBJV` / `IBJV` |
| `SHC_PAYMENT_VOUCHER` | varchar2(20) | `ADVP` / `BPS` / `ADVP-EXP`, null = no payment voucher |
| `SHC_CLOSES_ON_CONFIRM` | number(1) | 1 for PIB and SHIP — nothing left to settle, status jumps to 4 |
| `SHC_REQUIRES_VENDOR` | number(1) | 0 for PIB (KAS NEGARA) |
| `SHC_REQUIRES_VENDOR_BANK` | number(1) | 0 for EMKL |
| `SHC_FORCE_CURRENCY` | varchar2(3) | `IDR` for PIB, else null |
| `SHC_FORCE_BANK_CODE` | varchar2(30) | PIB's fixed bank, else null |
| `SHC_USES_DUTY_COLUMNS` | number(1) | 1 for PIB — the grid shows BM/PPN/PPh instead of qty×rate |
| `SHC_AUTO_PULL_TARIFF` | number(1) | 1 for EMKL |
| `SHC_IS_ADDITIONAL` | number(1) | **front office** — default 0. 1 for demurrage, detention, early pickup, `CANCELLATION_FEE` and destination charges: cost that appears after the baseline, possibly months later (`spec.md` §12.5) |
| `SHC_REQUIRES_LINE_APPROVAL` | number(1) | **front office** — default 0. 1 means each SI cost line of this type is approved individually rather than with the document |
| `SHC_ACTIVE` | number(1) | default 1 |
| audit ×4 | | |

New rows the front office needs: **`CANCELLATION_FEE`** (export, additional, line-approved, entered by
hand — never from a tariff), plus `DO` on the import side for the payment-request route (`spec.md`
§11.9). `CANCELLATION_FEE` also needs `ship_posting_account` rows, which is the only configuration it
gets — `open-questions.md` F2.

### `ship_container_type` (`shk_`)

`SHK_CODE` PK, `SHK_NAME`, `SHK_TEU` decimal(9,2), `SHK_REQUIRES_QTY` number(1) (0 for `LCL`),
`SHK_ACTIVE`, audit.

### `ship_activity` (`sha_`) — replaces `ShpActivities` + `assignAccount()`

`SHA_SYS_ID` PK, `SHA_CODE`, `SHA_NAME`, `SHA_DIRECTION`, `SHA_COST_TYPE` (nullable filter),
`SHA_DEFAULT_QTY` decimal(18,4), `SHA_PPN_PCT`, `SHA_PPH_PCT`, `SHA_EXPENSE_MAIN_ACNT`,
`SHA_EXPENSE_SUB_ACNT`, `SHA_PROVISION_ACNT`, `SHA_SORT_ORDER`, `SHA_ACTIVE`, audit.
`UQ` (`SHA_CODE`, `SHA_DIRECTION`).

### `ship_tariff` (`sht_`) — replaces `SHP_MASTER_COSTS`

| Column | Type | Notes |
|---|---|---|
| `SHT_SYS_ID` | number PK | |
| `SHT_DIRECTION` | varchar2(10) | was `smc_level` = `EXPORT` / `IMPORT` |
| `SHT_VENDOR_CODE` | varchar2(30) | `ALL` allowed (import falls back to it) |
| `SHT_CONTAINER_TYPE` | varchar2(20) | |
| `SHT_PORT_CODE` | varchar2(20) | FK → `ship_port.shp_code`. **Null = the tariff applies at any port.** Import EMKL rates differ per customs office, which legacy handled by keeping separate vendor rows |
| `SHT_ACTIVITY_CODE` | varchar2(30) | |
| `SHT_SHAPE` | varchar2(10) | `RATE` \| `FIX` \| `TIER` — **explicit**, replacing legacy's inference from a `-I` / `-II` name suffix. Legacy's `smc_type = 'X'` becomes **`RATE`** at backfill (`X` tells the next reader nothing). `FLAT` from the earlier draft of this doc is **replaced by `RATE`**: same `rate × qty`, but the band no longer silently caps the quantity — an out-of-range quantity now warns and prices in full, because a capped amount that nobody was told about is the worse of the two failures. Semantics in `spec.md` §4.1, and note that `TIER` is **progressive**: it emits one cost line per consumed band, not one line for the matching band |
| `SHT_UOM` | varchar2(20) | **front office** — `CONT` \| `CBM` \| `KGS` \| `CHARGEABLE_KG` \| `DAYS` \| `UNIT`. Decides **where the quantity comes from** (`spec.md` §4.1); `CONT` for every backfilled row. `DAYS` carries the tiered demurrage and storage rates that already exist in the data (`STORG-REG-MASA`), so no new tariff mechanism is needed for additional cost |
| `SHT_TIER_GROUP` | varchar2(50) | groups the tier rows that belong to one band set |
| `SHT_TIER_SEQ` | number(3) | order within the group |
| `SHT_MIN_QTY` / `SHT_MAX_QTY` | decimal(18,4) | band bounds (was `smc_min` / `smc_max`) |
| `SHT_RATE` | decimal(18,4) | |
| `SHT_CURRENCY` | varchar2(3) | |
| `SHT_EXPENSE_MAIN_ACNT` / `_SUB_ACNT` / `SHT_PROVISION_ACNT` | varchar2(20) | was `smc_main_acnt` / `smc_sub_acnt` / `smc_prov_acnt` |
| `SHT_EFF_FROM` / `SHT_EFF_TO` | date | **new** — legacy tariffs were edited in place, destroying the history of what a past provision was priced from |
| `SHT_ACTIVE` | number(1) | |
| audit ×4 | | |

`IX` (`SHT_DIRECTION`, `SHT_VENDOR_CODE`, `SHT_PORT_CODE`, `SHT_CONTAINER_TYPE`, `SHT_ACTIVE`).

### `ship_port` (`shp_`) — the customs offices / ports we ship through

Three rows today. Legacy had no such master: the Pabean code was free text in `sah_flex_1` and the
port name free text in `sah_flex_2`, so a typo silently produced a provision no tariff would match.

| Column | Type | Notes |
|---|---|---|
| `SHP_CODE` | varchar2(20) PK | the **Pabean code** as entered on the PIB document — `040300`, `060100`, `050100`. **A string, never a number**: the leading zero is part of the code, and the same rule the export helpers apply to NIK columns applies here (`FormatsNikForExport` in any report that emits it) |
| `SHP_NAME` | varchar2(100) | port name (Pelabuhan) — the value legacy typed into `sah_flex_2` |
| `SHP_OFFICE` | varchar2(100) | the customs office: `KPU Bea dan Cukai` or `KPPBC` |
| `SHP_OFFICE_TYPE` | varchar2(50) | `A`, `Madya Pabean`, `C` — the type **without** the word "Tipe". The label is composed for display (`Tipe {type}`, or `{office} Tipe {type}` for the full name), so the column holds the distinguishing value and nothing else, and grouping or filtering by type does not have to strip a prefix off every row |
| `SHP_TRANSPORT_MODE` | varchar2(10) | `SEA` \| `AIR`. Soekarno-Hatta is an airport, so container types and their quantities do not apply there the way they do at a seaport; the mode lets the container editor and the tariff pull say so instead of leaving the user to work it out |
| `SHP_CITY` | varchar2(100) | |
| `SHP_IS_DEFAULT` | number(1) | at most one row; pre-selects on a new import provision |
| `SHP_SORT_ORDER` | number(3) | |
| `SHP_ACTIVE` | number(1) | default 1 |
| audit ×4 | | |

The three rows:

| `SHP_CODE` | `SHP_NAME` | `SHP_OFFICE` | `SHP_OFFICE_TYPE` | `SHP_TRANSPORT_MODE` | `SHP_CITY` |
|---|---|---|---|---|---|
| `040300` | Tanjung Priok | KPU Bea dan Cukai | `A` | `SEA` | Jakarta |
| `060100` | Tanjung Emas | KPPBC | `Madya Pabean` | `SEA` | Semarang |
| `050100` | Soekarno-Hatta | KPU Bea dan Cukai | `C` | `AIR` | Tangerang |

Displayed as `KPU Bea dan Cukai Tipe A — Tanjung Priok`. The model exposes that as an accessor
(`officeLabel()` / `fullLabel()`) so the "Tipe" wording is written once.

Referenced by `ship_tariff.sht_port_code`, `ship_provision.spv_port_code` and
`ship_bill.stl_port_code`.

### `ship_facility` (`shf_`)

`SHF_CODE` PK, `SHF_NAME`, `SHF_ACTIVE`, `SHF_AUTO_REGISTERED` number(1), audit. Auto-registration
(legacy `checkFasilitas()`) stays, and marks the row so master-data owners can clean up typos.

### `ship_posting_account` (`spa_`) — replaces every hardcoded account number

| Column | Type | Notes |
|---|---|---|
| `SPA_SYS_ID` | number PK | |
| `SPA_DIRECTION` | varchar2(10) | nullable = any |
| `SPA_DOCUMENT` | varchar2(20) | `PROVISION` \| `BILL`, nullable = any |
| `SPA_COST_TYPE` | varchar2(10) | nullable = any |
| `SPA_ACTIVITY_CODE` | varchar2(30) | nullable = any |
| `SPA_PURPOSE` | varchar2(20) | `EXPENSE` \| `PROVISION` \| `PPN` \| `PPH` \| `DUTY_BM` \| `DUTY_PPN` \| `DUTY_PPH` \| `BANK` |
| `SPA_CONDITION` | varchar2(30) | optional discriminator, e.g. `PPN_1_1`, `HAS_PPH`, `NO_PPH` |
| `SPA_MAIN_ACNT` / `SPA_SUB_ACNT` | varchar2(20) | |
| `SPA_PRIORITY` | number(3) | lower wins; resolution order in `spec.md` §5.3 |
| `SPA_ACTIVE` | number(1) | |
| audit ×4 | | |

### `ship_parameter` (`shr_`) — operator-editable calculation parameters

`SHR_KEY` PK, `SHR_VALUE` varchar2(500), `SHR_VALUE_LONG` clob, `SHR_DATA_TYPE`
(`STRING`/`NUMBER`/`DATE`/`BOOL`/`JSON`), `SHR_GROUP`, `SHR_DESCRIPTION`, `SHR_ACTIVE`, audit.
Seeded keys in `spec.md` §6.

---

## 11. Entity chain

```
ShipProvision (spv_sys_id)
 ├─< ShipProvisionInvoice (spi_spv_sys_id)
 │      └─< ShipProvisionCost (spc_spi_sys_id) ──┐  settled once
 ├─< ShipProvisionContainer (spk_spv_sys_id)     │
 └─< ShipProvisionDoc (spd_spv_sys_id)           │
                                                 │
ShipBill (stl_sys_id) ── stl_spv_sys_id ─────────┤ (primary provision, nullable)
 ├─< ShipBillInvoice (sti_stl_sys_id)            │
 │      └─< ShipBillCost (stc_sti_sys_id) ── stc_spc_sys_id ─┘
 ├─< ShipBillContainer (stk_stl_sys_id)   (DIRECT only)
 └─< ShipBillDoc (std_stl_sys_id)         (DIRECT only)
```

"Unbilled provision line" = `ShipProvisionCost` with no `ShipBillCost` pointing at it
(`whereDoesntHave('billCost')`) on a provision with `spv_status >= 3`.

---

## 12. Migration files

| Order | File (`Modules/Finance/database/migrations/`) | `migrationKey` |
|---|---|---|
| 1 | `…_create_ship_cost_type_table.php` | `ship_cost_type` |
| 2 | `…_create_ship_container_type_table.php` | `ship_container_type` |
| 3 | `…_create_ship_port_table.php` | `ship_port` |
| 4 | `…_create_ship_activity_table.php` | `ship_activity` |
| 5 | `…_create_ship_tariff_table.php` (FKs `ship_port`) | `ship_tariff` |
| 6 | `…_create_ship_facility_table.php` | `ship_facility` |
| 7 | `…_create_ship_posting_account_table.php` | `ship_posting_account` |
| 8 | `…_create_ship_parameter_table.php` | `ship_parameter` |
| 9 | `…_create_ship_provision_table.php` | `ship_provision` |
| 10 | `…_create_ship_provision_invoice_table.php` | `ship_provision_invoice` |
| 11 | `…_create_ship_provision_cost_table.php` | `ship_provision_cost` |
| 12 | `…_create_ship_provision_container_table.php` | `ship_provision_container` |
| 13 | `…_create_ship_provision_doc_table.php` | `ship_provision_doc` |
| 14 | `…_create_ship_bill_table.php` | `ship_bill` |
| 15 | `…_create_ship_bill_invoice_table.php` | `ship_bill_invoice` |
| 16 | `…_create_ship_bill_cost_table.php` | `ship_bill_cost` |
| 17 | `…_create_ship_bill_container_table.php` | `ship_bill_container` |
| 18 | `…_create_ship_bill_doc_table.php` | `ship_bill_doc` |
| 19 | `…_create_ship_legacy_map_table.php` | `ship_legacy_map` |
| 20 | `…_create_ship_sys_id_triggers.php` (Oracle only, all 10 transaction tables + activity/tariff/posting) | `ship_sys_id_trg` |
| 21 | `…_create_v_ship_vendor_view.php`, `…_customer_view`, `…_company_bank_view` (Oracle only) | `v_ship_*` |
| 22 | `2026_09_08_120000_add_ship_unique_constraints.php` — the two guards that legacy enforced in PHP (`STI_VENDOR_CODE`+`UPPER(STI_INVOICE_NO)` as `uq_ship_bill_inv_vendor_no`, `STC_SPC_SYS_ID` as `uq_ship_bill_cost_prov_line`); **runs after the backfill**, checks the data first and names the rows that block it | `ship_unique_guards` |

**Exim Front Office** — a second wave, entirely additive, in its own migrations so the two deliveries
can be released independently:

| Order | File (`Modules/Finance/database/migrations/`) | `migrationKey` |
|---|---|---|
| 23 | `…_create_ship_kurs_pajak_table.php` | `ship_kurs_pajak` |
| 24 | `…_create_ship_hs_tariff_table.php` | `ship_hs_tariff` |
| 25 | `…_create_ship_hs_preference_table.php` | `ship_hs_preference` |
| 26 | `…_create_ship_doc_type_table.php` | `ship_doc_type` |
| 27 | `…_create_ship_transit_time_table.php` | `ship_transit_time` |
| 28 | `…_create_ship_skb_table.php` | `ship_skb` |
| 29 | `…_create_ship_bim_template_table.php` | `ship_bim_template` |
| 30 | `…_create_ship_bim_table.php` (FKs `ship_bim_template`) | `ship_bim` |
| 31 | `…_create_ship_bim_child_tables.php` — notify, carrier rule, freight history | `ship_bim_children` |
| 32 | `…_create_ship_import_file_table.php` (FKs `ship_port`, `ship_skb`) | `ship_import_file` |
| 33 | `…_create_ship_import_invoice_table.php` | `ship_import_invoice` |
| 34 | `…_create_ship_import_item_table.php` | `ship_import_item` |
| 35 | `…_create_ship_import_child_tables.php` — container, doc | `ship_import_children` |
| 36 | `…_create_ship_export_si_table.php` | `ship_export_si` |
| 37 | `…_create_ship_export_si_cost_table.php` (FKs `ship_provision`) | `ship_export_si_cost` |
| 38 | `…_create_ship_export_si_child_tables.php` — bim link, container, edn, doc | `ship_export_si_children` |
| 39 | `…_create_ship_payment_request_table.php` | `ship_payment_request` |
| 40 | `…_add_front_office_columns_to_shipment_control.php` — the additive changes: `SPV_SIF_SYS_ID`, `SPV_SES_SYS_ID`, `SPV_SOURCE_TYPE`, `SPC_NO_TARIFF_MATCH`, `SPC_SEC_SYS_ID`, `SHC_IS_ADDITIONAL`, `SHC_REQUIRES_LINE_APPROVAL`, `SHT_UOM` | `ship_front_office_columns` |
| 41 | `…_create_ship_front_office_sys_id_triggers.php` (Oracle only) — the **transaction** tables | `ship_fo_sys_id_trg` |
| 42 | `…_add_provision_reversal_columns.php` (T050) — `SPV_REVERSAL_VOUCHER`, `SPV_REVERSAL_DATE`, `SPV_REVERSAL_REASON`, `SPC_REVERSED_FLAG` | `ship_provision_reversal_columns` |
| 43 | `…_create_ship_notify_log_table.php` (T057) — what the notification sweep has already said, with its own sequence and trigger | `ship_notify_log` |
| 44 | `…_add_pib_variance_columns.php` (T058) — `SIF_EST_KURS_PAJAK`, the rate the **first** estimate was made at | `ship_pib_variance_columns` |

**`SIF_EST_KURS_PAJAK`** deserves its own line because it looks redundant beside `SIF_KURS_PAJAK` and is
not. That column holds the rate in force *now*, and the recompute button moves it to whichever KMK week
the PIB date falls in (`spec.md` §11.8) — which destroys the only evidence of what the budget was built
on. Written **once**, on the first estimate, never overwritten; null on every BL estimated before it
existed, and those report the variance's exchange-rate leg as unmeasurable rather than as zero.

**`ship_notify_log` (`snl_`)** — `SNL_SYS_ID` PK · `SNL_TRIGGER` · `SNL_DOC_TYPE` · `SNL_DOC_SYS_ID` ·
`SNL_DOC_NO` · `SNL_STATE` · `SNL_NOTIFIED_DATE` · `SNL_RECIPIENT_COUNT` · `SNL_SUMMARY` · audit ×4.
`UQ` (`SNL_TRIGGER`, `SNL_DOC_TYPE`, `SNL_DOC_SYS_ID`, `SNL_STATE`) — the **guard**, not merely an index:
the sweep writes the row before it sends, so two sweeps racing hit this and one stops.
`IX` (`SNL_NOTIFIED_DATE`). **No FK to `SHIP_IMPORT_FILE`**, deliberately: the log is the answer to "why
was nobody told about this shipment?", asked months later, and a cascade would delete the answer along
with the document. `SNL_DOC_NO` is carried for the same reason — the log reads without a join to a row
that may since have changed.

> **The masters' key sequences ship with the masters, not with file 41** (landed 2026-09-10 as
> `…_create_ship_fo_master_sys_id_triggers.php`, key `ship_fo_master_sys_id_trg`). T038 seeds
> `ship_doc_type` and will seed more of these tables as the import team supplies data, and a seeder
> cannot insert into an Oracle table whose key generator arrives two tasks later. File 41 keeps the
> seventeen transaction tables, which nothing seeds. `ship_doc_type` needs neither: it is keyed by its
> customs code.

File 40 is **nullable columns and defaulted flags only** — no data change, no downtime, and it can be
released before any front-office screen exists. That is what lets `PRD EXIM.md` §11 be prepared now.
The `SHT_SHAPE` value rename (`X` → `RATE`) belongs with the **backfill** (`data-migration.md` §7), not
with a schema migration: it is data, and it needs the seeder's review report beside it.

Sequences needed in `HmMstSequences` (for `SysIdHelper`): `SHIP_PROVISION`, `SHIP_BILL`,
`SHIP_IMPORT_FILE`, `SHIP_EXPORT_SI`, `SHIP_PAYMENT_REQUEST`. BIM has none — it is keyed by its ESC no.
(`spec.md` §10.2).
The primary-key sequences for the triggers are **`HmMstSequences` rows too — not Oracle sequences**.
`PKG_HM_SEQUENCES.get_next_seq_no` and `SysIdHelper` are the same counter read two ways, and both look
the sequence up in `HM_MST_SEQUENCES` by name, so a `CREATE SEQUENCE` would be a second counter nobody
reads. One row per table, named `{TABLE}_{COLUMN}_SEQ`, with `hmms_seq_type = 0`,
`hmms_number_format = 'TM9'` and no prefix; the trigger passes `p_date_format => NULL`. Together that
yields a plain incrementing integer, which is what a surrogate key should be — the date and padding
belong to the human-readable transaction numbers, not here.

> **`TM9`, not `'0'`.** `TM9` is Oracle's *text minimum* format: shortest representation, no padding, no
> width to overflow. A literal `'0'` reads like it means the same thing and then silently stops at 9 —
> `TO_CHAR(10, '0')` is `#`. `SysIdHelper` pads by however many `0` characters the format holds, so
> `TM9` gives it a padding length of zero: the bare number, matching Oracle.

Registering them in `HM_MST_SEQUENCES` also means CI gets them: the rows are inserted on **both**
drivers, so a test on SQLite can call `SysIdHelper::generate($name, $user, null)` and get an id the same
way production's trigger does.

---

## 13. Exim Front Office — transaction tables

Column lists here are the **shape**, not migration copy: types follow the same conventions as §1–§9
(`decimal(18,2)` money, `decimal(18,6)` rates and percentages, `decimal(18,4)` quantities, dates as
`date`, audit ×4 on every table, PK from a trigger).

### 13.1 `ship_bim_template` (`sbt_`)

Standing-term defaults per customer, maintained by sales. Everything here is a **default**, not a
constraint — `ship_bim` may override any of it.

`SBT_SYS_ID` PK · `SBT_CUSTOMER_CODE` (**unique, and per customer only** — S5, answered 2026-09-10 by
sales: one template per customer, edited on the BIM when a shipment needs something else. Measured, three
of 423 export customers ship to more than one address, so a customer × destination key would add a
column for BEKAERT DESLEE's seven plants and change nothing for the other 420) · `SBT_INCOTERM` · `SBT_PAYMENT_TERM` · `SBT_CURRENCY` · `SBT_CONSIGNEE_NAME` ·
`SBT_CONSIGNEE_ADDRESS` · `SBT_NOMINATED_FORWARDER` · `SBT_BL_TYPE_REQUEST` · `SBT_TRANSPORT_MODE` ·
`SBT_POD_CODE` · `SBT_FINAL_DESTINATION` · `SBT_PARTIAL_SHIPMENT` · `SBT_FUMIGATION_REQUIRED` ·
`SBT_FREE_TIME_BASIS` · `SBT_FREE_TIME_DAYS` · `SBT_NET_WEIGHT_MAX_KG` ·
`SBT_SAMPLE_APPROVAL_DEFAULT` · `SBT_SPECIAL_INSTRUCTION` (clob) · `SBT_ACTIVE` · audit ×4

### 13.2 `ship_bim` (`sbm_`)

One row per ESC. Every field stays editable even when inherited from the template, and an override is
recorded rather than hidden.

**Identity** — `SBM_SYS_ID` PK · `SBM_ESC_NO` · `SBM_ESC_DATE` · `SBM_CUSTOMER_CODE` ·
`SBM_SBT_SYS_ID` FK · `SBM_BIM_DATE` · `SBM_STATUS` (`ACTIVE` / `SUPERSEDED` / `CLOSED` —
`ShipBimStatusEnum` since T051, read through `tryFrom` and **not cast**, like the incoterm and the
free-time basis, so a migrated row carrying something else is a badge that falls back to plain text
rather than a fatal error on a list page. `SUPERSEDED` is a BIM replaced wholesale, **not** what a
renegotiated freight rate produces — that is a history row on a BIM that stays `ACTIVE`) ·
`SBM_SOURCE` (`NEW` / `MIGRATED`)

**Terms** — `SBM_INCOTERM` · `SBM_INCOTERM_FROM_ESC` (snapshot, so a divergence from the contract is
visible instead of inferred) · `SBM_PAYMENT_TERM` (`LC` / `TT_CAD` / `TT_ADVANCE` / `TT_OPEN`) ·
`SBM_LC_NO` · `SBM_LC_DATE` · `SBM_ADVANCE_PCT` · `SBM_CURRENCY`

**Routing** — `SBM_TRANSPORT_MODE` (`SEA_FCL` / `SEA_LCL` / `AIR` / `COURIER`) · `SBM_POL_CODE` ·
`SBM_POD_CODE` · `SBM_FINAL_DESTINATION` · `SBM_VIA_PORT_CODE` · `SBM_TRANSHIPMENT_MAX` ·
`SBM_TRANSIT_DAYS_MAX`

**Parties and documents** — `SBM_CONSIGNEE_NAME` · `SBM_CONSIGNEE_ADDRESS` ·
`SBM_NOMINATED_FORWARDER` (required when incoterm is `FOB` or `EXW`) · `SBM_BL_TYPE_REQUEST`
(`MBL` / `HBL` / `BOTH` / `NO_REQUEST`)

**Cargo and operations** — `SBM_PARTIAL_SHIPMENT` (`ALLOWED` / `NOT_ALLOWED`) ·
`SBM_FUMIGATION_REQUIRED` · `SBM_NET_WEIGHT_MAX_KG` · `SBM_FREE_TIME_BASIS`
(`DAYS` / `CARRIER_STANDARD` / `NOT_APPLICABLE`) · `SBM_FREE_TIME_DAYS` · `SBM_SAMPLE_APPROVAL`
(`NOT_REQUIRED` / `PENDING` / `APPROVED`) · `SBM_SAMPLE_APPROVED_DATE`

**Plan** — `SBM_PLANNED_CONTAINER_TOTAL` · `SBM_PLANNED_SHIPMENT_COUNT`

**Baseline rate, keyed at vendor booking** (S3a, answered 2026-09-10 — *not* pulled from the ESC, which
is only shown beside the field) — `SBM_CONTRACT_FREIGHT_RATE` · `_CURRENCY` · `_UOM`
(default `PER_CONTAINER`) · `_QUOTED_BY`, then **`SBM_CONTRACT_QUOTED_DATE`** and
**`SBM_CONTRACT_VALID_UNTIL`**: continuing the `_CONTRACT_FREIGHT_` stem would have made 32-character
identifiers, and Oracle 11g stops at 30 (caught by `ShipmentSchemaIdentifierTest` while T039 was being
built).

**Free text** — `SBM_SPECIAL_INSTRUCTION` (clob) · `SBM_INTERNAL_NOTE` (clob) · `SBM_LEGACY_BODY`
(clob, the original BIM text kept verbatim from migration) · `SBM_REVIEW_PENDING` (number(1) — a field
could not be mapped confidently, or the ESC carried several rates)

`UQ` (`SBM_ESC_NO`) · `IX` (`SBM_CUSTOMER_CODE`) · `IX` (`SBM_STATUS`) ·
`IX` (`SBM_REVIEW_PENDING`) where = 1.

`SBM_FREE_TIME_BASIS` is what untangles the worst field in the legacy data: `14` → `DAYS` + 14;
`Regular` / `Normal` / `Standard` → `CARRIER_STANDARD`; `FOB` → `NOT_APPLICABLE` (and that one can be
defaulted from the incoterm).

### 13.3 BIM child tables

**`ship_bim_notify` (`sbn_`)** — `SBN_SYS_ID` PK · `SBN_SBM_SYS_ID` FK cascade · `SBN_SORT_ORDER` ·
`SBN_PARTY_NAME` · `SBN_PARTY_ADDRESS` · audit. The order matters — the legacy text says
`1ST. : MERCURY FREIGHT`, so a bare set would lose information.

**`ship_bim_carrier_rule` (`sbc_`)** — `SBC_SYS_ID` PK · `SBC_SBM_SYS_ID` FK cascade · `SBC_RULE_TYPE`
(`ALLOW_ONLY` / `EXCLUDE`) · `SBC_CARRIER_CODE` · audit. The enum is not optional: `Do not use Maersk` is
a prohibition, `USE MAERSK / MSC / EVERGREEN` is an allow-list, and in the legacy free text both sit in
the same field with opposite meanings.

**`ship_bim_freight_history` (`sbf_`)** — `SBF_SYS_ID` PK · `SBF_SBM_SYS_ID` FK cascade · `SBF_RATE` ·
`SBF_CURRENCY` · `SBF_UOM` · `SBF_EFFECTIVE_DATE` · `SBF_REASON` · `SBF_SUPERSEDED_AT` · audit. Written
only when a contract is genuinely renegotiated (`spec.md` §10.6) — the baseline is superseded with
history, never overwritten.

### 13.4 `ship_import_file` (`sif_`)

**Identity** — `SIF_SYS_ID` PK · `SIF_TRANS_NO` · `SIF_TRANS_CODE` · `SIF_TRANS_DATE` · `SIF_STATUS`
(`spec.md` §10.3) · `SIF_BL_NO` · `SIF_BL_DATE` · `SIF_BL_TYPE` (`MBL` / `HBL`) · `SIF_MASTER_BL_NO`

**Parties and route** — `SIF_SUPPLIER_CODE` (1 BL = 1 supplier, so it lives here and invoices inherit
it) · `SIF_SUPPLIER_NAME` (snapshot) · `SIF_ORIGIN_COUNTRY` · `SIF_ORIGIN_PORT_CODE` ·
`SIF_DEST_PORT_CODE` · `SIF_PORT_CODE` (the **Pabean** code, FK → `ship_port.shp_code`) · `SIF_VESSEL` ·
`SIF_VOYAGE`

**Timing** — `SIF_ETD` · `SIF_TRANSIT_DAYS` (snapshot — the master must not move a shipment already in
flight) · `SIF_ETA_CALCULATED` · `SIF_ETA_ACTUAL`

**Cargo** — `SIF_GROSS_WEIGHT_KG` · `SIF_VOLUME_CBM` · `SIF_TRANSPORT_MODE` · `SIF_INCOTERM` ·
`SIF_CIF_MODE` (`A` = the value is already CIF / `B` = FOB — **derived from the incoterm, never chosen**,
`spec.md` §11.7)

**Tax and customs** — `SIF_KURS_PAJAK` · `SIF_KURS_PERIOD_FROM` · `SIF_KURS_PERIOD_TO` ·
`SIF_INSURANCE_PREMIUM` · `SIF_INSURANCE_CURRENCY` · `SIF_FACILITY_CODE` (a label; it does **not** change
BM%) · `SIF_SSK_SYS_ID` FK → `ship_skb` · `SIF_PPH_EXEMPT` · `SIF_AJU_NO` · `SIF_PIB_NO` ·
`SIF_PIB_DATE` · `SIF_SPPB_NO`

**Rollups** — `SIF_EST_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · `_TOTAL` ·
`SIF_EST_TOTAL_NO_PREFERENCE` · `SIF_EXPOSURE_COMPLETE` (0 when any item lacks a fallback rate, so a
partial exposure is never shown as whole) · `SIF_ACT_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · `_TOTAL` ·
`SIF_VAR_KURS` · `SIF_VAR_TARIFF` · `SIF_VAR_QTY` (the three-way variance split — one combined variance
figure cannot tell a bad estimate from a moving rate)

**Control** — `SIF_DOC_COMPLETE_FLAG` · `SIF_RECOMPUTE_COUNT` · audit ×4

`UQ` (`SIF_BL_NO`) · `UQ` (`SIF_AJU_NO`) where not null — the database enforcement of 1 BL = 1 AJU ·
`IX` (`SIF_ETA_CALCULATED`) — the notification sweep · `IX` (`SIF_SUPPLIER_CODE`) ·
`IX` (`SIF_STATUS`).

### 13.5 `ship_import_invoice` (`sii_`) and `ship_import_item` (`sit_`)

**`sii_`** — `SII_SYS_ID` PK · `SII_SIF_SYS_ID` FK cascade · `SII_INVOICE_NO` · `SII_INVOICE_DATE` ·
`SII_CURRENCY` · `SII_FREIGHT_TOTAL` · `SII_DISCOUNT_TOTAL` · `SII_VALUE_TOTAL` · duty rollups ·
audit ×4. Supplier is **not** repeated here — it is inherited from `SIF_SUPPLIER_CODE`.

Freight and discount live at this level and allocate **within the invoice**, while the insurance premium
lives on the BL and allocates across all its items (`spec.md` §11.7). That difference is the reason
items cannot hang directly off the BL.

**`sit_`** — `SIT_SYS_ID` PK · `SIT_SII_SYS_ID` FK cascade · `SIT_PO_NO` · `SIT_PO_LINE` ·
`SIT_ITEM_CODE` · `SIT_ITEM_NAME_PO` · `SIT_ITEM_NAME_DOC` (what customs goes by) ·
`SIT_HS_CODE_PO` (raw from the PO, format not trusted) · `SIT_HS_CODE` (normalised / edited) ·
`SIT_COUNTRY_OF_ORIGIN` · `SIT_PREFERENCE_SCHEME` (`ACFTA` / `AIFTA` / `ATIGA` / `RCEP` — **user-chosen,
never inferred from origin country**) · `SIT_BM_PCT` · `SIT_BM_SOURCE` (`PREFERENCE` / `MFN` / `MANUAL`) ·
`SIT_BM_PCT_FALLBACK` (optional; drives the exposure figure) · `SIT_BM_OVERRIDE_REASON` (mandatory when
the source is `MANUAL`) · `SIT_DUTY_TYPE` (`BM` / `BM_KITE` — reporting only in v1, does **not** drive an
account) · `SIT_PO_QTY` · `SIT_SHIPPED_QTY` · `SIT_UOM` · `SIT_RATE` · `SIT_VALUE_FOB` ·
`SIT_DISCOUNT_ALLOC` · `SIT_FREIGHT_ALLOC` · `SIT_INSURANCE_ALLOC_IDR` · `SIT_VALUE_CIF` ·
`SIT_VALUE_CIF_IDR` · `SIT_NILAI_IMPOR` · `SIT_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` ·
`SIT_ACT_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · audit ×4

`IX` (`SIT_SII_SYS_ID`) · `IX` (`SIT_PO_NO`, `SIT_PO_LINE`) — this one serves the **remaining-quantity**
query, which runs on every PO pull · `IX` (`SIT_HS_CODE`).

Estimated and actual duty sit side by side **per item**, not only as a header total, because the estimate
has to be comparable line-for-line with the issued PIB (`spec.md` §11.8).

### 13.6 `ship_import_container` (`sic_`) and `ship_import_doc` (`sid_`)

**`sic_`** — `SIC_SYS_ID` PK · `SIC_SIF_SYS_ID` FK cascade · `SIC_CONTAINER_TYPE` · `SIC_CONTAINER_NO` ·
`SIC_QTY` · `SIC_SEAL_NO` · audit ×4

**`sid_`** — `SID_SYS_ID` PK · `SID_SIF_SYS_ID` FK cascade · `SID_SDT_CODE` FK → `ship_doc_type` ·
`SID_IS_MANDATORY` (a **snapshot** of the condition evaluated when the checklist was generated, so a
later master edit does not rewrite history) · `SID_FILE_PATH` · `SID_FILE_NAME` · `SID_FILE_SIZE` ·
`SID_UPLOADED_BY` · `SID_UPLOADED_AT` · `SID_VERSION` · audit ×4

Files live in MinIO, as everywhere else in the app. Superseded versions are **kept**, not overwritten —
`open-questions.md` O3 settles whether replacement is allowed at all.

### 13.7 `ship_export_si` (`ses_`)

**Identity** — `SES_SYS_ID` PK · `SES_TRANS_NO` · `SES_TRANS_CODE` · `SES_TRANS_DATE` · `SES_STATUS`
(`BOOKED` / `CONFIRMED` / `SHIPPED` / `CLOSED` / `CANCELLED`, own enum — `spec.md` §10.4) ·
`SES_CUSTOMER_CODE`

**Terms snapshotted from the BIM** — the standing-term columns of §13.2 with an `SES_` prefix, plus
`SES_INCOTERM_FROM_BIM` and `SES_INCOTERM_CHANGE_REASON`. It is a **snapshot**: changing the BIM or its
template afterwards must not move an SI already raised.

**Shipment facts** — `SES_TRANSPORT_MODE` · `SES_POL_CODE` · `SES_POD_CODE` · `SES_VESSEL` ·
`SES_VOYAGE` · `SES_ETD` · `SES_ETA` · `SES_ATD` · `SES_STUFFING_DATE` · `SES_CONTAINER_OUT_DATE` ·
`SES_CONTAINER_RETURN_DATE` · `SES_FREE_TIME_DAYS` · `SES_GROSS_WEIGHT_KG` · `SES_NET_WEIGHT_KG` ·
`SES_VOLUME_CBM` · `SES_DIM_L` · `SES_DIM_W` · `SES_DIM_H` · `SES_CHARGEABLE_KG` (computed, `spec.md`
§11.2)

The three date columns `SES_FREE_TIME_DAYS` / `_CONTAINER_OUT_DATE` / `_CONTAINER_RETURN_DATE` are what
make a demurrage quantity verifiable against the carrier's invoice instead of hand-typed.

**Documents** — `SES_DO_NO` · `SES_DO_DATE` · `SES_BL_NO` · `SES_BL_DATE` · `SES_AWB_NO` ·
`SES_TRACKING_NO` · `SES_EIN_NO` · `SES_EIN_DATE` · `SES_PEB_NO` · `SES_PEB_DATE` · `SES_PEB_TYPE`

**Cancellation** — `SES_CANCEL_DATE` · `SES_CANCEL_REASON` · `SES_CANCEL_BY`

**Control** — `SES_COST_COMPLETE` (computed, `spec.md` §10.5) · audit ×4

`UQ` (`SES_TRANS_NO`) · `UQ` (`SES_EIN_NO`) where not null — 1 SI = 1 EIN, enforced ·
`IX` (`SES_STATUS`) · `IX` (`SES_CUSTOMER_CODE`) · `IX` (`SES_ETD`).

### 13.8 SI child tables

**`ship_export_si_bim` (`seb_`)** — the many-to-many between SI and BIM, because 1 EIN may cover several
ESCs: `SEB_SYS_ID` PK · `SEB_SES_SYS_ID` FK cascade · `SEB_SBM_SYS_ID` FK · `SEB_ESC_NO` (snapshot) ·
audit. `UQ` (`SEB_SES_SYS_ID`, `SEB_SBM_SYS_ID`).

**`ship_export_si_edn` (`sen_`)** — delivery notes under the EIN: `SEN_SYS_ID` PK · `SEN_SES_SYS_ID` FK
cascade · `SEN_EDN_NO` · `SEN_EDN_DATE` · `SEN_VALUE` · `SEN_CURRENCY` · audit.

**`ship_export_si_container` (`sek_`)** and **`ship_export_si_doc` (`sed_`)** follow the shape of `sic_`
and `sid_` exactly (§13.6), including the checklist snapshot and file versioning.

### 13.9 `ship_export_si_cost` (`sec_`)

One table for **every** SI cost — freight, EMKL, cancellation fee, demurrage, detention, early pickup,
DAP destination charges. What separates them is columns on the row, not a second table (`spec.md` §12.2).

`SEC_SYS_ID` PK · `SEC_SES_SYS_ID` FK cascade · `SEC_VENDOR_CODE` · `SEC_COST_TYPE` ·
`SEC_ACTIVITY_CODE` · `SEC_ACTIVITY_NAME` (snapshot) · `SEC_CONTAINER_TYPE` · `SEC_QTY` · `SEC_RATE` ·
`SEC_RATE_FROM_MASTER` (what the tariff would have said — the SI's own price wins, and the gap between
them is the signal that the rate card needs updating) · `SEC_CURRENCY` · `SEC_PPN_PCT` · `SEC_PPH_PCT` ·
`SEC_PPH_ADVANCED` · `SEC_BASE_AMOUNT` · `SEC_PPN_AMOUNT` · `SEC_PPH_AMOUNT` · `SEC_FC_AMOUNT` ·
`SEC_LC_AMOUNT` · `SEC_EXPENSE_MAIN_ACNT` · `SEC_EXPENSE_SUB_ACNT` · `SEC_PROVISION_ACNT` ·
`SEC_PPN_ACNT` · `SEC_PPH_ACNT` · `SEC_TARIFF_SYS_ID` (provenance) · `SEC_NO_TARIFF_MATCH` ·
`SEC_IS_ADDITIONAL` · `SEC_STATUS` (`DRAFT` / `APPROVED` / `VOID`) · `SEC_APPROVED_BY` ·
`SEC_APPROVED_AT` · `SEC_APPROVAL_NOTE` · `SEC_APPROVAL_REF` · `SEC_APPROVAL_OFFLINE_BY` ·
`SEC_VOID_REASON` · `SEC_SPV_SYS_ID` (FK → `ship_provision`; **null = not yet provisioned**) · audit ×4

`IX` (`SEC_SES_SYS_ID`) · `IX` (`SEC_VENDOR_CODE`, `SEC_STATUS`) ·
`IX` (`SEC_SPV_SYS_ID`) — serves the provision pull, which is literally
`status = APPROVED AND sec_spv_sys_id IS NULL` (`spec.md` §12.3) ·
`IX` (`SEC_NO_TARIFF_MATCH`) where = 1 · `IX` (`SEC_IS_ADDITIONAL`, `SEC_STATUS`) — the approval queue.

`SEC_APPROVAL_OFFLINE_BY` and `SEC_APPROVAL_REF` exist because the real approver is senior and does not
use the application. The alternative was a fake in-app approval or no record; this is the honest one.

### 13.10 `ship_payment_request` (`spr_`)

`SPR_SYS_ID` PK · `SPR_TRANS_NO` · `SPR_TRANS_DATE` · `SPR_DIRECTION` · `SPR_SIF_SYS_ID` FK (nullable) ·
`SPR_SES_SYS_ID` FK (nullable) · `SPR_COST_TYPE` · `SPR_VENDOR_CODE` · `SPR_REQUESTED_AMOUNT` ·
`SPR_CURRENCY` · `SPR_NEEDED_BY` · `SPR_STATUS` (`DRAFT` / `SENT` / `PAID`) · `SPR_SENT_AT` ·
`SPR_PAID_AMOUNT` · `SPR_PAID_DATE` · `SPR_VARIANCE_AMOUNT` · `SPR_VARIANCE_NOTE` · `SPR_PAID_BY` ·
audit ×4

Check constraint: **exactly one** of `SPR_SIF_SYS_ID` / `SPR_SES_SYS_ID` is not null.

There is deliberately **no `REJECTED` status** and no approver column: Finance does not refuse a customs
payment, they pay it and record the difference (`spec.md` §11.9). The variance is stored, not merely
displayed on the way past.

---

## 14. Exim Front Office — masters

### `ship_hs_tariff` (`shh_`) — an optional accelerator

Filled by hand for the HS codes actually used, **not** the whole BTKI. It may be empty on day one; every
BM% is then typed and the module still works (`spec.md` §11.8). That is a design property, not a
shortcut: waiting for a complete tariff master would block the module indefinitely.

`SHH_SYS_ID` PK · `SHH_HS_CODE` · `SHH_DESCRIPTION` · `SHH_BM_PCT_MFN` · `SHH_PPN_PCT` ·
`SHH_PPNBM_PCT` · `SHH_PPH_PCT` (2.5% generally; 7.5% for some HS — PIB 000310, HS 27101945) ·
`SHH_VALID_FROM` · `SHH_VALID_TO` · `SHH_ACTIVE` · audit ×4. `UQ` (`SHH_HS_CODE`, `SHH_VALID_FROM`)
(whether the history is needed from day one: `open-questions.md` D18).

### `ship_hs_preference` (`shr2_`)

`SHR2_SYS_ID` PK · `SHR2_HS_CODE` · `SHR2_ORIGIN_COUNTRY` · `SHR2_SCHEME`
(`ACFTA` / `AIFTA` / `ATIGA` / `RCEP`, extensible by row) · `SHR2_DOC_CODE` (the CO / ECO / DAB code the
claim requires) · `SHR2_BM_PCT` · `SHR2_VALID_FROM` · `SHR2_VALID_TO` · `SHR2_ACTIVE` · audit ×4.
`UQ` (`SHR2_HS_CODE`, `SHR2_ORIGIN_COUNTRY`, `SHR2_SCHEME`, `SHR2_VALID_FROM`).

Keyed on the scheme, not just the country, because PIB 000304 and 000307 are both from China under
different schemes (RCEP and ACFTA). Adding IJEPA, IK-CEPA or IA-CEPA is a row, not a release.

### `ship_kurs_pajak` (`skp_`)

`SKP_SYS_ID` PK · `SKP_CURRENCY` · `SKP_RATE` · `SKP_VALID_FROM` · `SKP_VALID_TO` · `SKP_KMK_NO` ·
`SKP_ACTIVE` · audit ×4. `UQ` (`SKP_CURRENCY`, `SKP_VALID_FROM`).

The weekly KMK rate — **not** the ERP's daily `fm_exchange_rate` type `B`.

**Seeds empty, and stays nearly empty** (D17, answered 2026-09-10). The ERP already keeps the KMK weeks:
`FM_EXCHANGE_RATE_KMK_MGT` holds the decree number and window, and `FM_EXCHANGE_RATE` type **`T`** holds
the rate for those same windows — 669 weeks without a gap since 2013. So the front office reads the ERP
behind a repository and this table is the fallback for a currency the ERP's KMK table does not carry,
which today means anything other than **USD**. `SKP_KMK_NO` is still the right column to keep: a rate
typed here needs its decree reference as much as one read from the ERP does.

### `ship_doc_type` (`sdt_`)

`SDT_CODE` PK (the **official customs code** — `380`, `705`, `860`, … — so a checklist compares directly
with a PIB attachment list) · `SDT_NAME` · `SDT_DIRECTION` · `SDT_IS_MANDATORY` ·
`SDT_APPLIES_ORIGIN_COUNTRY` · `SDT_APPLIES_HS_PREFIX` · `SDT_APPLIES_INCOTERM` ·
`SDT_APPLIES_FACILITY` · `SDT_APPLIES_TRANSPORT_MODE` · `SDT_SORT_ORDER` · `SDT_ACTIVE` · audit ×4.

**Null in any `APPLIES_*` column means "applies to everything"**, so the common documents need no
conditions at all. A new FTA or regulation is a master row, not a deployment (`spec.md` §11.6).

### `ship_transit_time` (`stt_`)

`STT_SYS_ID` PK · `STT_ORIGIN_COUNTRY` · `STT_ORIGIN_PORT_CODE` (nullable) · `STT_DEST_PORT_CODE`
(nullable) · `STT_TRANSIT_DAYS` · `STT_NOTIFY_LEAD_DAYS` · `STT_ACTIVE` · audit ×4.

Resolution is port-pair first, then origin country (`spec.md` §11.3), so country rows work on day one and
port rows can be added later without a code change. `STT_NOTIFY_LEAD_DAYS` lives here rather than as a
single global parameter because China's transit is short and Europe's is long — one number is wrong for
both.

**No unique key, and that is deliberate** (T038, 2026-09-10). Two rows for one route would make the ETA
depend on which the database returned first, so a unique key over
(country, origin port, destination port) is the instinct — but the columns that say "any port" are null,
SQL treats nulls as distinct, and the key would therefore guard the port-pair rows while quietly letting
the **country-level** ones double up. That is the case that matters, since country rows are what the
master works with on day one. There is a lookup index instead, and the duplicate guard belongs to the
master page's validation (T043), where a null means what it says.

`ship_skb` does have one — `UQ (SSK_SKB_NO)` — because a certificate number is never null and two rows
for it would be two answers to "was PPh exempt that month".

### `ship_skb` (`ssk_`)

`SSK_SYS_ID` PK · `SSK_SKB_NO` · `SSK_SKB_DATE` · `SSK_VALID_FROM` · `SSK_VALID_TO` · `SSK_SCOPE` ·
`SSK_TAX_TYPE` · `SSK_FILE_PATH` · `SSK_ACTIVE` · audit ×4.

Renewed quarterly in practice, so the validity check fires about four times a year — it is not a
theoretical guard. A missed expiry understates a Rp 1.4 billion BL's budget by ±Rp 35 million.

---

## 15. Front-office entity chain

```
ship_bim_template (sbt_sys_id)
 └─< ship_bim (sbm_sbt_sys_id)
        ├─< ship_bim_notify · ship_bim_carrier_rule · ship_bim_freight_history
        └── seb ──┐
                  │  many-to-many (1 EIN may cover several ESCs)
ship_export_si (ses_sys_id) ──┘
 ├─< ship_export_si_cost (sec_ses_sys_id) ── sec_spv_sys_id ──> ship_provision
 ├─< ship_export_si_container · ship_export_si_edn · ship_export_si_doc
 └──< ship_payment_request (spr_ses_sys_id)

ship_import_file (sif_sys_id)
 ├─< ship_import_invoice (sii_sif_sys_id)
 │      └─< ship_import_item (sit_sii_sys_id)
 ├─< ship_import_container · ship_import_doc
 ├──< ship_payment_request (spr_sif_sys_id)
 └──< ship_provision (spv_sif_sys_id)          nullable — the provision may stand alone
```

Two link columns carry the whole design and are worth stating plainly:

- **`sec_spv_sys_id`** — "this SI cost line has been provisioned, by that provision". Null means it has
  not. The provision pull is exactly `status = APPROVED AND sec_spv_sys_id IS NULL`, which is why one SI
  can post provisions into several different periods.
- **`spv_sif_sys_id` / `spv_ses_sys_id`** — "this provision belongs to that parent document". Both
  nullable, so Shipment Control works standalone and the front office is genuinely additive.

The pattern is the same one `stc_spc_sys_id` already uses between bill and provision lines (§11), applied
one level earlier in the chain. Learn it once.
