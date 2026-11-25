# Export SI — booking parties, pickers, BIM drawer, print — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let Export hold Forwarder/EMKL booking data, BC no. and EDNs on the SI, see BIM detail, and print the SI sheet for the forwarder/EMKL.

**Architecture:** Six new nullable columns on `ship_export_si`. Booking amounts are read from cost lines, never stored. Pickers reuse the existing ERP vendor and delivery-note repositories. Print goes through the existing `ShipPrintService` / `ShipPrintController` dompdf path.

**Tech Stack:** Laravel 12, Livewire 3, Alpine, dompdf, Pest (SQLite).

**Spec:** `.docs-me/superpowers/export-si-booking-print/2026-11-23-export-si-booking-print-design.md`

## Global Constraints
- Migrations: Oracle + SQLite compatible, `DISABLE_MIGRATIONS` key guard, idempotent (`hasColumn`), with `down()`.
- Commits: Conventional Commits, scope `finance`. Do not push.
- Tests: `php artisan test --parallel --processes=4 <file>`. Run `vendor/bin/pint --dirty` before each commit.
- Edit files with Edit/Write, never sed.
- No `wire:navigate`. Font Awesome 5 icon names only.
- Booking amount = Σ `sec_base_amount` where `sec_status != 'VOID'` and `sec_is_additional = false`, grouped by `sec_cost_type` (`SHIP` → forwarder, `EMKL` → EMKL), then by `sec_currency`.

## Review Focus
1. Hand-typing a forwarder/EMKL name after picking a vendor → the hidden code is cleared (otherwise the cost page suggests the wrong vendor). Test in Task 2.
2. Picking an EDN that is already on another SI → shown disabled; and if forced, save still fails cleanly on `uq_ship_si_edn_no`, not with a 500. Test in Task 3.
3. Picking the same EDN twice, or one already on the form → not duplicated. Test in Task 3.
4. ERP unreachable when a picker opens → empty list, page still works (existing `try/report` pattern). Test in Task 3.
5. Print of an SI with no cost lines, no BIM, no containers → PDF still renders, blanks not errors. Test in Task 5.

---

### Task 1: Columns, DTO, booking-amount read

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_23_100000_add_booking_fields_to_ship_export_si.php`
- Modify: `Modules/Finance/app/Models/MgtHris/Transaction/Shipment/ShipExportSi.php` (`$fillable`)
- Modify: `Modules/Finance/app/Data/Transaction/Shipment/ShipExportSiData.php` (constructor, `headerRules`, `attributes`, `fromForm`, `fromModel`, `toAttributes`)
- Modify: `Modules/Finance/app/Services/Transaction/Shipment/ShipExportSiService.php` (add `bookingAmounts`)
- Test: `tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`

**Interfaces:**
- Produces: columns `ses_forwarder_vendor_code` (30), `ses_emkl_vendor_code` (30), `ses_emkl_name` (200), `ses_bc_no` (50), `ses_goods_description` (500), `ses_hs_code` (20).
- Produces: form keys `forwarder_vendor_code`, `emkl_vendor_code`, `emkl_name`, `bc_no`, `goods_description`, `hs_code`. Rules: `nullable|string|max:<len>`.
- Produces: `ShipExportSiService::bookingAmounts(ShipExportSi $si): array{forwarder: array<string, float>, emkl: array<string, float>}`, keyed by currency.
- Produces: `ShipExportSiService::formatBooking(array<string, float> $byCurrency): ?string`, e.g. `"USD 550.00 + IDR 1,250,000.00"`, null when empty.

- [ ] **Step 1: Write failing tests**
  - `it('sums baseline cost lines per party and currency')`: use `EximFixture::exportSi()` (SHIP OFR 3×rate USD, EMKL 3,500,000 IDR). Add a VOID SHIP line of USD 999 and an additional (`sec_is_additional=true`) SHIP line of USD 777. Assert `bookingAmounts($si)` equals `['forwarder' => ['USD' => 3 * EximFixture::CONTRACT_FREIGHT_USD], 'emkl' => ['IDR' => 3_500_000.0]]`.
  - `it('formats several currencies with a plus')`: `formatBooking(['USD' => 550, 'IDR' => 1250000])` equals `'USD 550.00 + IDR 1,250,000.00'`. `formatBooking([])` is null.
  - `it('saves and reloads the new header fields')`: Livewire `ShipExportSiInput` on the fixture SI. Set all six form keys, call `save`, `assertHasNoErrors`. Then `$si->refresh()` holds each value.
- [ ] **Step 2: Run, expect FAIL**: `php artisan test --parallel --processes=4 tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`
- [ ] **Step 3: Migration + model + DTO + service.** In `bookingAmounts`, query `$si->costs()` and group in PHP. The cost-type → party map is a private const `['SHIP' => 'forwarder', 'EMKL' => 'emkl']`.
- [ ] **Step 4: Run, expect PASS.** Also run `tests/Feature/Finance/Shipment/ShipSiCostTest.php` and `ShipExportSiListTest.php` (no regressions).
- [ ] **Step 5: Commit** `feat(finance): store EMKL, vendor codes and BC no. on the export SI`

### Task 2: Form fields + supplier picker + customer read-only

**Files:**
- Modify: `Modules/Finance/app/Livewire/Transaction/Shipment/ShipExportSiInput.php`
- Modify: `Modules/Finance/resources/views/livewire/transaction/shipment/ship-export-si-input.blade.php` (terms card ~L186-286)
- Create: `Modules/Finance/resources/views/livewire/transaction/shipment/partials/_si-supplier-picker.blade.php` (modelled on `_import-supplier-picker`)
- Test: `tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`

**Interfaces:**
- Consumes: `bookingAmounts`, `formatBooking` (Task 1); `ShipVendorRepositoryInterface::vendors(['search','limit'])` → rows with `vendor_code`, `vendor_name`.
- Produces on the component: `public bool $showSupplierPicker`, `public string $supplierSearch`, `public ?string $supplierTarget` (`'forwarder'|'emkl'`), `const SUPPLIER_PICKER_LIMIT = 50`, `openSupplierPicker(string $target)`, `#[Computed] supplierCandidates(): Collection`, `chooseSupplier(string $code, string $name)`, `updatedFormNominatedForwarder()`, `updatedFormEmklName()`, `#[Computed] bookingAmounts(): array`.
- `chooseSupplier` writes `form.nominated_forwarder` + `form.forwarder_vendor_code`, or `form.emkl_name` + `form.emkl_vendor_code`. It does nothing when the SI is not editable or the target is invalid.
- The `updatedForm*` hooks null the matching `*_vendor_code`. `chooseSupplier` sets the name before the code, so the hook does not clear a just-picked code (the hook only fires for wire:model updates, not for server-side sets).

- [ ] **Step 1: Failing tests**
  - `it('fills forwarder name and code from the picker')`: bind a fake `ShipVendorRepositoryInterface`. `call('openSupplierPicker','forwarder')->call('chooseSupplier','V100','Evergreen Line')` → `form.nominated_forwarder`=`Evergreen Line`, `form.forwarder_vendor_code`=`V100`. The same for `emkl`.
  - `it('clears the vendor code when the name is typed by hand')`: after choosing, `set('form.emkl_name','PT Other')` → `form.emkl_vendor_code` is null.
  - `it('shows booking amounts read-only with a cost lines link')`: on the fixture SI, `assertSee('USD')` and `assertSee(route('dashboard.module-finance.transaction.shipment.export-si.show', $si->ses_trans_no))`. Check the real route name for the detail page in `routes/web.php` L242.
- [ ] **Step 2: Run, expect FAIL**
- [ ] **Step 3: Implement.**
  - Terms card layout: Customer becomes read-only, showing "code — name" (`$this->customerName`).
  - Forwarder row: name input + search button + read-only "Booking" text. EMKL row: the same.
  - BC no. input. Goods description textarea, HS code input (sailing/cargo card).
  - Booking text: `formatBooking(...) ?? '— no cost line yet'`, plus a "Cost lines" link to the detail page, shown only once `$transNo` exists.
  - `@include` the picker partial.
- [ ] **Step 4: Run, expect PASS**
- [ ] **Step 5: Commit** `feat(finance): pick forwarder and EMKL from vendors on the export SI`

### Task 3: EDN multi-picker

**Files:**
- Modify: `ShipExportSiInput.php`, `ship-export-si-input.blade.php` (documents card ~L477)
- Create: `partials/_si-edn-picker.blade.php`
- Modify: `Modules/Finance/app/Interfaces/Transaction/Shipment/ShipExportSiRepositoryInterface.php` + its Eloquent impl: add `ednOwners(array $ednNos): array<string, string>` (edn_no → owning `ses_trans_no`)
- Test: `tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`

**Interfaces:**
- Consumes: `ShipSalesDocRepositoryInterface::deliveryNotes(['customer_code' => form.customer_code, 'limit' => 200])` → rows with `doc_no`, `doc_date`, `value`, `esc_no`, `customer_name`.
- Produces: `public bool $showEdnPicker`, `public array $ednCandidates` (plain arrays: `doc_no, doc_date, value, esc_no, taken_by`), `public array $ednPicked` (doc_nos), `openEdnPicker()`, `addPickedEdns()`.
- `openEdnPicker` loads once per open, wraps the ERP call in `try { } catch (Throwable) { report(); [] }`, and sets `taken_by` from `ednOwners`, excluding this SI's own trans no.
- `addPickedEdns` appends rows `{sys_id:null, edn_no, edn_date, value, currency: form.currency}` for picked doc_nos not already in `$edns` and with no `taken_by`. It then closes the modal and clears `$ednPicked`.
- Search: Alpine `x-data="{ q: '' }"`. Each row has `x-show` matching `q` against doc_no / esc_no (case-insensitive). No server round-trip.
- Keep the "Add an EDN" button.

- [ ] **Step 1: Failing tests**, with a fake `ShipSalesDocRepositoryInterface` returning `EDN-1`, `EDN-2`, `EDN-3`. The fixture SI already owns `EDN-<its trans no>`. Create a second SI owning `EDN-3`.
  - `it('adds the picked EDNs once')`: `set('ednPicked',['EDN-1','EDN-2'])->call('addPickedEdns')`, then repeat with `['EDN-1']` → `edns` contains EDN-1 and EDN-2 once each.
  - `it('marks an EDN taken by another SI and will not add it')`: `ednCandidates` row EDN-3 has `taken_by` = the second SI's trans no. Picking it adds nothing.
  - `it('leaves the list empty when the ERP fails')`: the fake throws → `ednCandidates === []`, no exception.
  - `it('refuses a duplicate EDN on save with a message')`: manually add `EDN-3` via `addEdn` + set, then `save` → `assertHasErrors` or a `butter-error` dispatch, not a thrown exception. If the existing save does not catch the unique violation, add a validation rule in `ShipExportSiService::rules()` that rejects `edn_no` owned by another SI (`ednOwners`).
- [ ] **Step 2: Run, expect FAIL**
- [ ] **Step 3: Implement**
- [ ] **Step 4: Run, expect PASS**
- [ ] **Step 5: Commit** `feat(finance): pick EDNs from the ERP on the export SI`

### Task 4: BIM drawer

**Files:**
- Modify: `ShipExportSiInput.php`, `ship-export-si-input.blade.php` (contracts card ~L73-128)
- Create: `partials/_si-bim-drawer.blade.php`
- Test: `tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`

**Interfaces:**
- Produces: `public ?int $drawerBimId = null`, `openBimDrawer(int $sysId)`, `closeBimDrawer()`, `#[Computed] drawerBim(): ?ShipBim`, which returns null when `$drawerBimId` is null and otherwise `$this->bims->findManyWithChildren([$id])->first()`.
- View: eye button (`fas fa-eye`) on each contract row. `<x-ui::drawer wire:model="showBimDrawer" ...>`; add `public bool $showBimDrawer` toggled together with the id. The body uses `@if ($this->drawerBim)` so nothing is queried while closed.
- Content, read-only: ESC no., customer, incoterm, payment term, currency, POL/POD, final destination, consignee name/address, notify parties, nominated forwarder, BL type, partial shipment, fumigation, free time, planned containers, contract freight, special instruction.

- [ ] **Step 1: Failing tests**
  - `it('does not load a BIM until the drawer opens')`: a fresh component has `drawerBimId === null`, and the view does not contain the consignee text `Hengyi Trading`.
  - `it('shows BIM detail when the drawer opens')`: `call('openBimDrawer', $bimSysId)->assertSee('Hengyi Trading')->assertSee('ESC-2026-001')`.
- [ ] **Step 2: FAIL. Step 3: Implement. Step 4: PASS.**
- [ ] **Step 5: Commit** `feat(finance): show BIM detail in a drawer on the export SI`

### Task 5: SI print

**Files:**
- Modify: `Modules/Finance/app/Services/Transaction/Shipment/ShipPrintService.php` (add `exportSi`)
- Modify: `Modules/Finance/app/Http/Controllers/Shipment/ShipPrintController.php` (add `exportSi(string $transNo)`)
- Modify: `Modules/Finance/routes/web.php` (beside the export-si routes, **before** `export-si/{transNo}` if order matters): `Route::get('export-si/{transNo}/print', [ShipPrintController::class, 'exportSi'])->middleware('can:finance-shipment-si-view')->name('export-si.print')`
- Create: `Modules/Finance/resources/views/pdf/shipment/export-si.blade.php` (extend `pdf/shipment/layout` if it fits portrait; otherwise standalone)
- Modify: `ship-export-si-input.blade.php` and `ship-export-si-detail.blade.php`: Print button (`fas fa-print`, `target="_blank"`), shown once saved
- Test: `tests/Feature/Finance/Shipment/ShipExportSiBookingTest.php`

**Interfaces:**
- Consumes: `ShipExportSiService::findByTransNo`, `bookingAmounts`, `formatBooking`.
- Produces: `ShipPrintService::exportSi(ShipExportSi $si): Response`. It eager-loads `bims.notifies` (check the relation name on `ShipBim`), `containers`, `loadingPort`, `dischargePort`; A4 portrait; `->stream($si->ses_trans_no.'.pdf')`.
- Field mapping: the table in the spec ("Print" section). Freight = `PREPAID` when `$si->incoterm()?->addsFreight()`, else `COLLECT`. Shipment type = containers grouped by type, `"{sum qty}X{container type name}"`, joined by `, `. Notify = BIM notify parties, else `SAME AS CONSIGNEE`. Shipper address + footer instructions copied verbatim from SI-579, as constants in the view.

- [ ] **Step 1: Failing tests**
  - `it('prints the SI as a PDF')`: on the fixture SI, set `ses_nominated_forwarder='Evergreen Line'`, `ses_emkl_name='PT Jasindo'`, `ses_bc_no='BC-1'`. Then `get(route('dashboard.module-finance.transaction.shipment.export-si.print', $si->ses_trans_no))` → `assertOk()`, header `content-type` contains `application/pdf`.
  - `it('builds the sheet from the SI')`: render the view directly (`view('finance::pdf.shipment.export-si', [...])->render()`) with the same data passed by the service. Assert it contains `Evergreen Line`, `PT Jasindo`, `BC-1`, `PREPAID` or `COLLECT` per the fixture incoterm, and `Hengyi Trading`. To keep this simple, have the service expose `public function exportSiViewData(ShipExportSi $si): array` and use it in both.
  - `it('prints a bare SI without errors')`: `ShipExportSi::factory()->create()` with no children → `assertOk()`.
  - `it('refuses print without si-view')`: a user without the permission → 403.
- [ ] **Step 2: FAIL. Step 3: Implement. Step 4: PASS.**
- [ ] **Step 5: Full shipment suite**: `php artisan test --parallel --processes=4 tests/Feature/Finance/Shipment` → all green. Run `vendor/bin/pint --dirty`.
- [ ] **Step 6: Commit** `feat(finance): print the export shipping instruction`
