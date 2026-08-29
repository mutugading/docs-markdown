# gap-analysis.md — legacy Shipping → Shipment Control

Legacy = `mgthris` app, `MGTAPPS.SHP_*` tables, screens `ExportContract`, `ExportSettlement`,
`ImportPayReq`, `ImportShipSettlement`, `ImportDashboard`, trait `ShipCommonFunction`.
New = `Modules/Finance` → `Shipment`, `MGTHRIS.ship_*`.

---

## 1. Screen map

| Legacy screen / route | New page / route |
|---|---|
| `ExportContract` · `/Apps/Shipping/Export/Provision` | `ShipProvisionInput` (direction `EXPORT`) · `…/transaction/shipment/provisions/create` |
| `ExportSettlement` · `/Apps/Shipping/Export/Settlement` | `ShipBillInput` (direction `EXPORT`) · `…/bills/create` |
| `ImportPayReq` · `/Apps/Shipping/Import/Provision` | `ShipProvisionInput` (direction `IMPORT`) |
| `ImportShipSettlement` · `/Apps/Shipping/Import/Settlement` | `ShipBillInput` (direction `IMPORT`) |
| `ImportDashboard` · `/Apps/Shipping/Import/Dashboard` | `ShipmentDashboard` · `…/transaction/shipment` (both directions) |
| `ShipArrInput` · `/Apps/Shipping/Arrival-Input` | **not migrated in Phase 1** (PRD non-goal) |
| 5 report modals | Phase 2, `spec.md` §8 |
| — | **new:** `ShipProvisionList` / `ShipBillList` (legacy had only a search modal), `ShipProvisionDetail` / `ShipBillDetail` (legacy edited in place) |
| — | **new:** 8 master CRUD pages (legacy masters were edited in the database; the port master did not exist at all) |

## 2. Column mapping

### 2.1 `SHP_ARR_HEAD` → `ship_provision`

| Legacy | New | Note |
|---|---|---|
| `sah_sys_id` | `spv_legacy_sys_id` (+ new `spv_sys_id`) | the backfill's join key |
| `sah_trans_code` = `SHPEXP`/`SHPARR` | `spv_direction` = `EXPORT`/`IMPORT` | |
| `sah_trans_no` | `spv_trans_no` | |
| `sah_trans_date` | `spv_trans_date` | |
| `sah_status` | `spv_status` | same codes |
| `sah_cost_type` | `spv_cost_type` | export legacy hardcoded `EDN`; see Q1 |
| `sah_supp_code` | `spv_customer_code` (export) / `spv_supplier_code` (import) | legacy reused one column for both roles |
| `sah_vnd_name` | `spv_vendor_name` | + `spv_vendor_code` where derivable |
| `sah_curr` / `sah_exg_rate` | `spv_currency` / `spv_exchange_rate` | |
| `sah_bank_curr` / `sah_bank_code` | `spv_bank_currency` / `spv_bank_code` | |
| `sah_is_cc` | `spv_is_cross_currency` | |
| `sah_v_bank_acc` / `_name` / `_no` | `spv_vendor_bank_acc` / `_name` / `_no` | |
| `sah_flex_1` | `spv_si_no` (export) / `spv_port_code` (import) | **direction-dependent**. On import this is the **Pabean code**, now an FK to the new `ship_port` master and the key the EMKL tariff is priced from |
| `sah_flex_2` | `spv_peb_type` (export) / `spv_port` (import) | **direction-dependent**. Pelabuhan stays free text; the port master carries the canonical name |
| `sah_flex_3` | `spv_destination` (export) / `spv_sptnp_no` (import) | **direction-dependent** |
| `sah_flex_4` | `spv_inco_term` | export only |
| `sah_flex_5` | `spv_ein_no` | export only |
| `sah_flex_6` | `spv_remark` | |
| `sah_aju_no` / `_date` | `spv_aju_no` / `spv_aju_date` | |
| `sah_pib_no` / `_date` | `spv_pib_no` / `spv_pib_date` | |
| `sah_sppb_no` | `spv_sppb_no` | |
| `sah_pl_no` / `sah_bl_no` | `spv_pl_no` / `spv_bl_no` | |
| `sah_inv_no` | `spv_invoice_no` | import supplier invoice |
| `sah_doc_ref` / `sah_doc_ref3` / `sah_doc_reff` | `spv_doc_ref` (+ `spv_ref_sys_id` when resolvable) | legacy had three near-identical columns |
| `sah_prov_date` | `spv_prov_date` | |
| `sah_vcr_prov` | `spv_prov_voucher` | |
| `sah_vcr_adv` / `sah_vcr_pay` | `spv_pay_voucher` | legacy split ADVP/BPS (`_adv`) from "paid off the request" (`_pay`); one column + the cost type tells you which |
| `sah_pay_date` | `spv_pay_date` | only where a payment voucher exists (see §4) |
| — | `spv_prov_voucher_date`, `spv_pay_voucher_date`, `spv_submitted_by/_at`, `spv_confirmed_by/_at`, the six total columns | new |

### 2.2 `SHP_ARR_INV` → `ship_provision_invoice`

| Legacy | New |
|---|---|
| `sai_sys_id` | `spi_legacy_sys_id` |
| `sai_sah_sys_id` | `spi_spv_sys_id` (resolved through the id map) |
| `sai_flex_2` (export) / `sah_cost_type` (import) | `spi_cost_type` — **always filled** |
| `sai_vnd_code` / `sai_vnd_name` | `spi_vendor_code` / `spi_vendor_name` |
| `sai_inv_no` / `sai_inv_date` | `spi_invoice_no` / `spi_invoice_date` |
| `sai_flex_1` | `spi_carrier_name` |
| `sai_curr` / `sai_exg_rate` | `spi_currency` / `spi_exchange_rate` |
| `sai_total` | `spi_total_base` (recomputed, not copied — legacy left it null on export) |
| `sai_total_ppn` / `_pph` | `spi_total_ppn` / `_pph` |
| `sai_fc_amt` / `sai_lc_amt` / `sai_total_cc` / `sai_total_amt` | `spi_total_fc` / `_lc` / `_cc` / *(dropped — duplicate of `_fc`)* |

### 2.3 `SHP_ARR_DET` → `ship_provision_cost`

| Legacy | New |
|---|---|
| `sad_sys_id` | `spc_legacy_sys_id` |
| `sad_sai_sys_id` | `spc_spi_sys_id` |
| `sad_act_id` / `sad_act_code` / activity name | `spc_activity_code` / `spc_activity_name` |
| `sad_cont_type` | `spc_container_type` |
| `sad_fasilitas` | `spc_facility_code` |
| `sad_qty` / `sad_rate` / `sad_curr` | `spc_qty` / `spc_rate` / `spc_currency` |
| `sad_ppn` / `sad_pph` / `sad_pph_adv` | `spc_ppn_pct` / `spc_pph_pct` / `spc_pph_advanced` |
| `sad_amt` | `spc_base_amount` |
| `sad_ppn_amt` / `sad_pph_amt` | `spc_ppn_amount` / `spc_pph_amount` |
| `sad_fc_amt` / `sad_lc_amt` / `sad_cc_amt` / `sad_is_cc` | `spc_fc_amount` / `_lc_amount` / `_cc_amount` / `spc_is_cross_currency` |
| `sad_pib_bm` / `_ppn` / `_pph` | `spc_duty_bm` / `_duty_ppn` / `_duty_pph` |
| `sad_main_acnt` / `sad_sub_acnt` / `sad_prov_acnt` | `spc_expense_main_acnt` / `_expense_sub_acnt` / `spc_provision_acnt` |
| *(implicit)* | `spc_ppn_acnt` / `spc_pph_acnt` — legacy carried these only on the bill line |
| `sad_flex_4` / `_5` / `_6` | `spc_si_no` / `spc_bl_no` / `spc_remark` |
| — | `spc_tariff_sys_id` (provenance) |

### 2.4 `SHP_ARR_CONT` → `ship_provision_container`

`sac_sys_id` → `spk_sys_id` (new) · `sac_sah_sys_id` → `spk_spv_sys_id` ·
`sac_cont_type` → `spk_container_type` · `sac_cont_qty` → `spk_qty`.

### 2.5 `SHP_ARR_PO_HEAD` → `ship_provision_doc`

| Legacy | New |
|---|---|
| `saph_sys_id` | `spd_legacy_sys_id` |
| `saph_sah_sys_id` | `spd_spv_sys_id` |
| `saph_po_no` | `spd_doc_no` (+ `spd_doc_type` = `EDN` for export, `PO` for import, `SAMPLE` when the no. is `SAMPLE`) |
| `saph_esc` / `saph_sta` | `spd_esc_no` / `spd_sta_no` |
| `po_inv_no` / `po_val` / `po_inv_val` / `rem_val` | `spd_invoice_no` / `spd_po_value` / `spd_invoiced_value` / `spd_remaining_value` |
| PO `main_acc` | `spd_main_acnt` |

### 2.6 `SHP_BILL_HEAD` → `ship_bill`

| Legacy | New |
|---|---|
| `sbh_sys_id` | `stl_legacy_sys_id` |
| `sbh_trans_code` = `EXPBILL`/`IMPBILL` | `stl_direction` |
| `transType` (`PROVISION`/`DIRECT`) — screen state only | `stl_source` — **now persisted** |
| `sbh_sah_sys_id` | `stl_spv_sys_id` |
| `sbh_trans_no` / `_date` / `_status` | `stl_trans_no` / `stl_trans_date` / `stl_status` |
| `sbh_bill_date` / `sbh_due_date` | `stl_bill_date` / `stl_due_date` |
| `sbh_cost_type` | `stl_cost_type` |
| `sbh_vnd_code` / `sbh_vnd_name` | `stl_vendor_code` / `stl_vendor_name` |
| `sbh_curr` / `sbh_exg_rate` / `sbh_bank_curr` / `sbh_bank_code` | `stl_currency` / `stl_exchange_rate` / `stl_bank_currency` / `stl_bank_code` |
| `sbh_v_bank_acc` / `_name` / `_no` | `stl_vendor_bank_acc` / `_name` / `_no` |
| `sbh_flex_1..5` | `stl_si_no`, `stl_peb_type`, `stl_destination`, `stl_inco_term`, `stl_ein_no` (export) / `stl_port_code`, `stl_port`, … (import) |
| `sbh_aju_no` / `sbh_bl_no` / `sbh_pib_no` | `stl_aju_no` / `stl_bl_no` / `stl_pib_no` |
| `sbh_vcr` | `stl_bill_voucher` |
| `sbh_vcr_pay` / `sbh_pay_date` | `stl_pay_voucher` / `stl_pay_date` |
| — | `stl_bill_voucher_date`, `stl_pay_voucher_date`, `stl_total_*`, `stl_total_diff`, workflow stamps |

### 2.7 `SHP_BILL_INV` → `ship_bill_invoice`

`sbi_sys_id` → `sti_legacy_sys_id` · `sbi_sbh_sys_id` → `sti_stl_sys_id` ·
`sbi_inv_no` / `_date` → `sti_invoice_no` / `sti_invoice_date` · `sbi_rcv_date` → `sti_received_date` ·
`sbi_vnd_code` / `_name` → `sti_vendor_code` / `_name` · `sbi_flex_1` → `sti_carrier_name` ·
`sbi_curr` / `sbi_exg_rate` → `sti_currency` / `sti_exchange_rate` ·
`sbi_total` / `_ppn` / `_pph` / `sbi_fc_amt` / `sbi_lc_amt` / `sbi_total_cc` / `sbi_total_diff`
→ `sti_total_base` / `_ppn` / `_pph` / `_fc` / `_lc` / `_cc` / `_diff` ·
cost type: from the head → `sti_cost_type`.

### 2.8 `SHP_BILL_DET` → `ship_bill_cost`

| Legacy | New |
|---|---|
| `sbd_sys_id` | `stc_legacy_sys_id` |
| `sbd_sbi_sys_id` | `stc_sti_sys_id` |
| **`sbd_sad_sys_id`** | **`stc_spc_sys_id`** — resolved through the `spc_legacy_sys_id` map |
| `sbd_act_*` | `stc_activity_code` / `_name` |
| `sbd_qty` / `sbd_rate` / `sbd_curr` | `stc_qty` / `stc_rate` / `stc_currency` |
| `sbd_ppn` / `sbd_pph` / `sbd_pph_adv` | `stc_ppn_pct` / `stc_pph_pct` / `stc_pph_advanced` |
| `sbd_amt` / `sbd_ppn_amt` / `sbd_pph_amt` | `stc_base_amount` / `_ppn_amount` / `_pph_amount` |
| `sbd_fc_amt` / `_lc_amt` / `_cc_amt` / `sbd_is_cc` | `stc_fc_amount` / `_lc_amount` / `_cc_amount` / `stc_is_cross_currency` |
| `sbd_prv_amt` / `sbd_diff` | `stc_provision_amount` / `stc_diff_amount` |
| `sbd_main_acnt` / `_sub_acnt` / `_prov_acnt` / `_ppn_acnt` / `_pph_acnt` | `stc_expense_main_acnt` / `_expense_sub_acnt` / `stc_provision_acnt` / `stc_ppn_acnt` / `stc_pph_acnt` |
| `sbd_fp_no` / `sbd_fp_date` | `stc_fp_no` / `stc_fp_date` |
| `sbd_flex_1..6` | `stc_ein_no`, `stc_sta_no`, `stc_esc_no`, `stc_si_no`, `stc_invoice_no`, `stc_remark` |

### 2.9 Masters

| Legacy | New |
|---|---|
| `ShpMasters` `type='STATUS'` | `ShipStatusEnum` (code, not data) |
| `ShpMasters` `type='COST'` | `ship_cost_type` (+ behaviour flags) |
| `ShpMasters` `type='CONT'` | `ship_container_type` |
| *(none — Pabean code and Pelabuhan were free text)* | **`ship_port`** (3 rows) |
| `ShpMasters` `type='FASILITAS'` | `ship_facility` |
| `ShpActivities` | `ship_activity` |
| `ShpMasterCosts` (`smc_*`) | `ship_tariff` (+ explicit shape, tier group, effective dates) |
| `ShpVendors` | `v_ship_vendor` view over `om_supplier` |
| hardcoded account numbers | `ship_posting_account` |
| hardcoded rates / limits | `ship_parameter` |
| `MST_PARAMS.*_REPORT_SQL` | dropped — report queries in PHP |

---

## 3. Feature parity checklist

| # | Legacy feature | Phase | Notes |
|---|---|---|---|
| 1 | Export provision from ESC | 1 | |
| 2 | EDN/DO picker with `EDN_START_NO` floor + "already attached" exclusion | 1 | |
| 3 | Container editor | 1 | |
| 4 | Invoice per cost type (SHIP / EMKL / INS / COMM) | 1 | |
| 5 | Tariff pull (TIER / FIX / FLAT) | 1 | shape now explicit; import tariffs additionally keyed by port |
| 6 | Insurance computation | 1 | parameters, not constants |
| 7 | Commission from ESC agent terms (+ vendor switch) | 1 | |
| 8 | Amount calculation incl. cross currency + PPh advance | 1 | one calculator, both documents |
| 9 | Import provision by cost type (PIB / EMKL / SHIP) | 1 | master flags, not `isPib`/`isEmkl` |
| 10 | PO picker (all / valid / SAMPLE) | 1 | |
| 11 | PIB duty columns | 1 | |
| 12 | EMKL → PIB back-fill | 1 | |
| 13 | Facility auto-registration | 1 | + `shf_auto_registered` flag |
| 14 | Submit / revoke / confirm / amend / delete | 1 | import bill **gains** submit/revoke |
| 15 | Vouchers EPJV / IPJV / EBJV / IBJV | 1 | PHP port |
| 16 | Vouchers ADVP / ADVP-EXP / BPS | 1 | PHP port |
| 17 | Settlement from one provision invoice | 1 | |
| 18 | Settlement merging several provisions for one vendor | 1 | |
| 19 | DIRECT bill with its own containers / docs | 1 | `stl_source` persisted |
| 20 | Variance per line / invoice / bill | 1 | |
| 21 | Faktur Pajak picker + apply-to-invoice | 1 | |
| 22 | Duplicate invoice + account existence guards | 1 | + DB unique index |
| 23 | Import dashboard | 1 | now both directions |
| 24 | Missing-voucher repair | 1 | artisan command + dashboard panel |
| 25 | Voucher / transaction PDF print | 1 | |
| 26 | Activity log on every change | 1 | |
| 27 | Master data CRUD | 1 | new capability |
| 28 | 6 Excel reports | 2 | `spec.md` §8 |
| 29 | Arrival Input wizard | later | own PRD |
| 30 | Merge PDF | dropped | dead code |
| 31 | `IBJV-EMKL` | dropped | unreachable in legacy |
| 32 | Report SQL in `MST_PARAMS` | dropped | |

---

## 4. Behaviour changes to announce to Finance

| # | Change | Why |
|---|---|---|
| B1 | Import settlements now go through **Submit → Confirm** instead of straight to Confirm | consistency, and a supervisor gate that legacy lacked |
| B2 | A pay date is only ever set when a payment voucher is raised | legacy stamped it on journal-only paths and the reports read it as "paid" — both legacy docs record the data cleanup |
| B3 | A vendor invoice number cannot repeat for the same vendor (DB-enforced) | legacy checked in PHP only, so races and direct DB edits let duplicates in |
| B4 | A provision cost line can be settled only once (DB-enforced) | legacy's "unbilled" filter assumed it |
| B5 | The provision closes automatically when nothing is left to bill | legacy set status 4 "outside these screens" |
| B6 | Account numbers, insurance rates, cost-type voucher matrix are now master data | no deployment to change them; **someone must own that data** |
| B7 | Editing a tariff no longer rewrites history — tariffs are effective-dated | past provisions keep showing what they were priced from |
| B8 | Trans no. appears in the URL; documents have a read-only detail page | deep links, and edit is a deliberate step |
| B9 | The bill's headline figure is the invoice total, with the variance beside it (import used to headline the variance) | the two screens agreeing matters more than either default |
| B10 | `sah_vcr_adv` and `sah_vcr_pay` collapse into one payment-voucher column | the cost type already says which kind it is |
| B11 | The Pabean code is picked from a 3-row port master instead of typed free-hand, and the EMKL tariff is priced per port | a typo used to produce a provision that matched no tariff; the port also stops one vendor's rates for two customs offices from having to live in two vendor codes |

---

## 5. Data-quality issues found in the legacy docs (confirm during backfill)

1. Rows where `sah_pay_date` / `sbh_pay_date` was stamped without a payment voucher — legacy already
   nulled these; verify none remain.
2. `sah_aju_no` values that are not 14 characters (validation was added later than the data).
3. Duplicate `(vendor, invoice no.)` pairs in `SHP_BILL_INV` — blocks the new unique index.
4. Bill lines whose `sbd_sad_sys_id` points at a provision line that no longer exists.
5. More than one bill line pointing at the same provision line — blocks the new unique index.
6. Provision invoices with no cost lines, and cost lines with no accounts (cannot be confirmed in the
   new app, fine for closed history).
7. Export provisions whose `sah_cost_type` is not `EDN` (legacy defaulted it) and import provisions whose
   cost type is not in the master.

Each has a query in `data-migration.md` §5.

---

## 6. Open questions

| # | Question | Blocks |
|---|---|---|
| Q1 | Export provisions always carried `sah_cost_type = 'EDN'` while the real cost type sat on the invoice. Do we keep `EDN` as the export header cost type, or set the header to the dominant invoice cost type? | schema seed + backfill mapping |
| Q2 | Should export and import be separate permissions (a user who may only touch export)? | permission seeder |
| Q3 | `spv_customer_code` vs `spv_supplier_code`: is there any provision that legitimately needs both? | header DTO |
| Q4 | Account priority: should a configured PIB posting-account row beat a PO-carried account, or the reverse (legacy: PIB wins)? | `ShipAccountResolver` |
| Q5 | Is the `ft_payment_header.ph_flex_01/_04` duplicate-bill-number check still wanted as a warning? | `spec.md` §3.3 |
| Q6 | Cut-over date, and does legacy stay writable in parallel for any period? | `data-migration.md` §2 |
| Q7 | Who owns `ship_posting_account` / `ship_parameter` / `ship_tariff` data going forward? | permissions + training |
| Q8 | Do the Phase 2 reports need the pre-cut-over rows exactly as legacy rendered them, or is the backfilled shape enough? | report acceptance |
| Q9 | Air freight through Soekarno-Hatta (`050100`): should containers stop being required, and does the EMKL tariff there key on something other than container type (weight? shipment?) — legacy priced everything per container | container validation, `ShipTariffService`, `ship_tariff` shape |
