# Parity check — legacy `ImportPayReq` → `ShipProvisionInput` (import)

Date: 2026-11-09. Branch: `feat/menu-sort-and-sidebar-search`.

Legacy: `mgthris/app/Http/Livewire/Shipping/ImportPayReq.php` + `resources/views/livewire/shipping/import-pay-req.blade.php`.
New: `Modules/Finance/app/Livewire/Transaction/Shipment/ShipProvisionInput.php` (form),
`ShipProvisionDetail.php` (workflow), `ShipProvisionService`, `ShipProvisionWorkflowService`.

Goes with `gap-analysis.md` §3. Where this file and that checklist disagree, this one is newer.

---

## 1. Done in this pass

These fields were already in the DTOs and saved by the service, but the screen had no input for them.

| Legacy | New field | Where |
|---|---|---|
| `sah_pl_no` (Packing List No.) | `form.pl_no` | `_provision-header.blade.php`, Customs block |
| `sad_pph_adv` (PPh paid in advance) | `lines.*.pph_advanced` | `_provision-cost-grid.blade.php`, checkbox under PPh %; enabled only when PPh % > 0 |
| `sad_sub_acnt` | `lines.*.expense_sub_acnt` | cost grid, "Sub Acct." column |
| `sad_flex_6` (Remark) | `lines.*.remark` | cost grid, "Remark" column |
| `sad_fasilitas` (PIB lines) | `lines.*.facility_code` | cost grid, "Facility" select, duty lines only |

Checked: `php artisan test --parallel tests/Feature/Finance/Shipment --filter=Provision`: 273 passed. Pint clean.
Not yet looked at in a browser.

---

## 2. Already covered somewhere else

| Legacy | New |
|---|---|
| `searchTrans` / `getTransList` (search modal) | `ShipProvisionList` page |
| `loadTrans` (edit in place) | detail page → edit route `provision.edit` |
| `submit` / `revoke` / `ammend` | `ShipProvisionDetail` → `ShipProvisionWorkflowService` (revoke and amend now take a remark) |
| `beforeApprove` + `approveTrans(true)` with prov/pay date modal | detail page confirm modal; pay date asked for only when the cost type raises a payment voucher (B2) |
| `genVoucherTrans` IPJV / BPS / ADVP by cost type | `confirm()`, driven by cost-type master flags (`shc_payment_voucher`, `shc_closes_on_confirm`) |
| `confirmDeleteTrans` | detail page delete |
| `checkVoucher` / `updateMissingVoucher` | `ShipVoucherRepairService` + dashboard `missingVouchers()` |
| `sah_flex_1` Pabean / `sah_flex_2` Pelabuhan / `sah_flex_3` SPTNP | `port_code` (port master select) / `port` / `sptnp_no` |
| PIB → bank acc "KAS NEGARA", vendor cleared | cost-type `force_currency` / `force_bank_code`; invoice vendor locked to `TREASURY_CODE` (screen and service) |
| `getMasterCost` (EMKL from container master costs) | "Pull tariff" → `ShipProvisionService::pullTariff` (port-aware, `TIER` progressive) |
| `getDefaultAccount` / `assignAccount` hardcoded 401100 / 401130 / 401134 / 401141 / 208023 | `ShipAccountResolver` posting rules on save, PO main account still wins |
| `checkInvNo` account existence (`fm_acnt_comp`) | `assertAccountsExist()` at **confirm**, not save |
| `distinct` bill no. within the document | `invoices.*.invoice.invoice_no` `distinct` rule |
| EMKL save → copy PIB no./date/SPPB to PIB provision | `applyAjuLinkage()` (transitional, B18) |
| Exchange rate from `ExchangeRateService` | PIB: KMK rate on PIB date (`kursPajakOn`); invoice tabs follow the header rate until edited |
| `sah_is_cc` cross currency | computed per line from bank currency (`ShipCostCalculator`) |
| PIB thousand-dot amounts (`preg_replace`) | `ShipProvisionCostData::dutyNumeric()` in `updatedInvoices()` |
| PO picker (`getPoListAll`) | doc picker → `validPurchaseOrders`; BL pull attaches the BL's POs |
| Container editor | `_shipment-containers` |
| Activity log per change | `activity()` in service / status service |

---

## 3. Decisions (2026-11-09) and what was built

| # | Decision | Built |
|---|---|---|
| G1 | Check across provision invoices only; **not** `ft_payment_header` | `ShipProvisionService::duplicateInvoiceErrors()` → repo `existingVendorInvoice()` (same vendor, case-insensitive; PIB matches on number alone). Runs on save and in `issues()` for submit/confirm |
| G2 | Needed | "Copy from provision" button + modal on a new form (both directions). `ShipProvisionService::copyFormState()`: header, containers, POs/EDNs; no ids, no cost type, no invoices. Writes `spv_ref_sys_id` / `spv_doc_ref` once, on create; badge "From …" in the header |
| G3 | Separate **Post** button; Confirm only confirms | `ShipProvisionWorkflowService::confirm()` = status only. `post()` = vouchers, needs `-generate` + Orion id, refuses while a slot is filled; PIB/SHIP close here. `confirmAndPost()` for callers without a screen. Detail page: Confirm (browser confirm prompt), Post (date modal) |
| G4 | Keep PIB No./Date required on save | no change |
| G5 | Not needed | — |
| G6 | Not needed for now | `registerFacility()` still unused; `gap-analysis.md` §3 #13 stays open |
| G7 | No bulk toggle; checkbox inline with PPh % | done in `_provision-cost-grid` |
| G8 | Third-party vendor per cost line, in a modal | New columns `spc_vendor_code` / `spc_vendor_name` (migration `2026_11_09_100000`, null = invoice vendor). "Line Details" button per invoice (next to Add Line) opens one modal with a table of all that invoice's lines: container, expense acct., sub acct., provision acct., third-party vendor (shared vendor picker), remark. The main grid keeps activity, qty/rate/curr/taxes (or duty columns + facility) and amount; a line with a third-party vendor shows a badge, and an account error shows "Check details". Downstream: `ShipProvisionCost::owedVendor()`; settlement picker and `unbilledCostsForVendor()` group by owed vendor; settling an invoice leaves its third-party lines for their own vendor; ADVP pays one leg per owed vendor; account check covers each |

Tests: `tests/Feature/Finance/Shipment/ShipProvisionLegacyParityTest.php` (new), workflow / pages / EMKL tests moved to `confirmAndPost()`.
`php artisan test --parallel tests/Feature/Finance`: 1545 passed; CI-style (`ACTIVITY_LOGGER_DB_CONNECTION=`) shipment run: 1401 passed. Pint clean. **Not checked in a browser.**

Finance needs to hear (add to `gap-analysis.md` §4): Confirm no longer posts; posting is its own button and needs `-generate`.

## 4. Original gap list (before the decisions)

| # | Legacy behaviour | Now | Suggested fix |
|---|---|---|---|
| G1 | **Duplicate bill no. across documents.** `checkInvNo()` refused a bill no. already on another `SHP_ARR_INV` for the same vendor (any vendor for PIB), or already in `ft_payment_header.ph_flex_01 / ph_flex_04` | Only `distinct` within one provision. **Highest risk.** | Reuse the bill side's `ShipBillService::duplicateInvoiceErrors()` pattern for provisions, called on save |
| G2 | **Copy from previous provision** ("Reff", `getReff` / `loadReff`): new provision with header, POs, containers of an old one; `sah_doc_reff` recorded | Only "Pull from BL" | Not needed if every import has a BL file. Otherwise a "copy from provision" picker |
| G3 | **Confirm without vouchers** (`approveTrans(false)`) | Confirm always posts | Probably for vouchers made by hand in Orion. Ask Finance whether it's still used |
| G4 | **PIB No./Date on EMKL required at submit** (`validateData('pibNo')`) | Required at **save** (`ShipProvisionData::headerRules`), so an EMKL draft can't be saved before the PIB is known | Move the `required` to the submit/confirm case |
| G5 | **Default qty from activity** (`sa_qty` when an activity is picked) | Not done | Set `qty` in `updatedInvoices()` on `.activity_code` if the activity master has a qty |
| G6 | **Facility auto-registration** (`checkFasilitas`) | `registerFacility()` exists in `EloquentShipMasterRepository` but **nothing calls it**; the screen is a select from the master. So `gap-analysis.md` §3 #13 is not actually done | Either call it on save for typed codes (needs a combobox), or accept "master only" and close #13 |
| G7 | **"All PPh adv" toggle** per invoice (`invList.*.allPph`) | Per-line checkbox only | Add only if users ask for it |
| G8 | **Vendor per cost line** (`sad_vnd_code`) | Vendor per invoice | Looks deliberate. Confirm with Finance, then drop |

Suggested order: G1, G4, then ask Finance about G3 / G6 / G8.
