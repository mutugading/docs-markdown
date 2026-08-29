# schema.md — Shipment Control tables

Connection **`oracle_mgthris`** for every `ship_*` table (the module's own data). ERP tables on
`oracle_mgtdat` are never migrated — see `spec.md` §7.

Conventions (root `CLAUDE.md`):

- Table names lowercase `snake_case`, singular-domain (`ship_provision`, `ship_bill`).
- Every column prefixed with the table's abbreviation, written **UPPERCASE** in migrations (Oracle
  stores it uppercase anyway; LcControl's migrations do the same) and referenced **lowercase** in models
  and code.
- Audit columns on every table: `*_created_by`, `*_created_timestamp`, `*_modified_by`,
  `*_modified_timestamp`. Models map `const CREATED_AT` / `UPDATED_AT` to them.
- Primary keys: `NUMBER` from a sequence, set by a `BEFORE INSERT` trigger via
  `MGTHRIS.PKG_HM_SEQUENCES.get_next_seq_no` (the LcControl pattern). Models:
  `$incrementing = false`, `$keyType = 'int'`.
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
| `SPV_TRANS_NO` | varchar2(20) | no | unique; `SysIdHelper` sequence `SHIP_PROVISION` — keeps the legacy `YYYY` + 6-digit shape |
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
| `SPV_REMARK` | varchar2(500) | yes | was `sah_flex_6` |
| `SPV_SUBMITTED_BY` / `_AT` | varchar2(30) / timestamp | yes | workflow stamps |
| `SPV_CONFIRMED_BY` / `_AT` | varchar2(30) / timestamp | yes | |
| `SPV_LEGACY_SYS_ID` | number | yes | the backfilled `sah_sys_id`; null for rows created in the new app |
| audit ×4 | | | `SPV_CREATED_BY`, `SPV_CREATED_TIMESTAMP`, `SPV_MODIFIED_BY`, `SPV_MODIFIED_TIMESTAMP` |

Indexes: `UQ` (`SPV_TRANS_NO`) · `UQ` (`SPV_LEGACY_SYS_ID`) where not null ·
`IX` (`SPV_DIRECTION`, `SPV_STATUS`, `SPV_TRANS_DATE`) · `IX` (`SPV_AJU_NO`) · `IX` (`SPV_PORT_CODE`) ·
`IX` (`SPV_SI_NO`) · `IX` (`SPV_VENDOR_CODE`) · `IX` (`SPV_COST_TYPE`).

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
| `SPC_LEGACY_SYS_ID` | number | yes | backfilled `sad_sys_id` — **the backfill's join key for bill lines** |
| audit ×4 | | | |

Indexes: `UQ` (`SPC_LEGACY_SYS_ID`) where not null · `IX` (`SPC_SPI_SYS_ID`) ·
`IX` (`SPC_ACTIVITY_CODE`).

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
| `STL_TRANS_NO` | varchar2(20) | no | unique, `SysIdHelper` sequence `SHIP_BILL` |
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

Indexes: `UQ` (`STL_TRANS_NO`) · `UQ` (`STL_LEGACY_SYS_ID`) where not null ·
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
| `SHC_ACTIVE` | number(1) | default 1 |
| audit ×4 | | |

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
| `SHT_SHAPE` | varchar2(10) | `TIER` \| `FIX` \| `FLAT` — **explicit**, replacing legacy's inference from a `-I` / `-II` name suffix |
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
| 22 | `…_add_ship_unique_constraints.php` — the two guards that legacy enforced in PHP (`STI_VENDOR_CODE`+`UPPER(STI_INVOICE_NO)`, `STC_SPC_SYS_ID`); **runs after the backfill** | `ship_unique_guards` |

Sequences needed in `HmMstSequences` (for `SysIdHelper`): `SHIP_PROVISION`, `SHIP_BILL`.
Oracle sequences for the triggers: one per table, named `<TABLE>_SYS_ID_SEQ`, driven through
`PKG_HM_SEQUENCES.get_next_seq_no`.
