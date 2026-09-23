# design.md — Shipment Control & Exim Front Office architecture

Stack: Laravel 12, Livewire 3 (+ Volt where a page is trivial), Flux UI Pro 2, Tailwind 4,
Oracle via `yajra/laravel-oci8`, Spatie Permission 7 + Activitylog 4, Spatie Laravel Data 4, Pest 4.
Pattern: **Livewire → Service → Repository interface → Eloquent repository → Model**.

Everything lives in `Modules/Finance` under a `Shipment/` sub-namespace in each layer, matching how
Finance already separates `RawMaterial`, `Sales` and `FpScan`.

**One domain folder, two deliveries.** The Exim Front Office (`PRD EXIM.md`: BIM, import BL file, export
SI, payment request) is **not** a second module and not a second folder tree. It is more classes in the
same `Shipment/` namespace, because it shares the tariff service, the account resolver, the cost
calculator, the parameter store and the voucher plumbing — splitting it would mean either duplicating
those five or building a module-to-module dependency the project forbids. Front-office classes are
prefixed `ShipBim*`, `ShipImport*`, `ShipExportSi*` and `ShipPaymentRequest*` so a file's owner is
obvious from its name.

---

## 1. Folder map

```
Modules/Finance/app/
├── Data/
│   ├── Transaction/Shipment/
│   │   ├── ShipProvisionData.php            # header DTO (Wireable)
│   │   ├── ShipProvisionInvoiceData.php
│   │   ├── ShipProvisionCostData.php
│   │   ├── ShipContainerData.php            # shared by provision + bill
│   │   ├── ShipDocData.php                  # shared by provision + bill
│   │   ├── ShipBillData.php
│   │   ├── ShipBillInvoiceData.php
│   │   ├── ShipBillCostData.php
│   │   └── Concerns/CalculatesLineAmounts.php
│   └── Master/Shipment/
│       ├── ShipCostTypeData.php  ShipContainerTypeData.php  ShipActivityData.php
│       ├── ShipTariffData.php    ShipFacilityData.php    ShipPortData.php
│       └── ShipPostingAccountData.php  ShipParameterData.php
├── Enums/Transaction/Shipment/
│   ├── ShipDirectionEnum.php        # EXPORT | IMPORT
│   ├── ShipStatusEnum.php           # 0..4, shared by provision and bill
│   ├── ShipBillSourceEnum.php       # PROVISION | DIRECT
│   ├── ShipDocTypeEnum.php          # EDN | PO | SAMPLE
│   ├── ShipVoucherTypeEnum.php      # EPJV EBJV IPJV IBJV ADVP BPS
│   ├── ShipTariffShapeEnum.php      # RATE | FIX | TIER   (was TIER|FIX|FLAT — spec.md §4.1)
│   ├── ShipTariffUomEnum.php        # CONT CBM KGS CHARGEABLE_KG DAYS UNIT
│   ├── ShipPostingPurposeEnum.php   # EXPENSE PROVISION PPN PPH DUTY_* BANK
│   │   # — front office —
│   ├── ShipIncotermEnum.php         # EXW FCA FOB CFR CIF CPT CIP DAP DDP (+ CNF→CFR mapping)
│   ├── ShipTransportModeEnum.php    # SEA_FCL | SEA_LCL | AIR | COURIER
│   ├── ShipImportFileStatusEnum.php # DRAFT ESTIMATED SUBMITTED_PIB CLEARED CLOSED
│   ├── ShipSiStatusEnum.php         # BOOKED CONFIRMED SHIPPED CLOSED CANCELLED — its OWN enum
│   ├── ShipSiCostStatusEnum.php     # DRAFT | APPROVED | VOID
│   ├── ShipBmSourceEnum.php         # PREFERENCE | MFN | MANUAL
│   ├── ShipPreferenceSchemeEnum.php # ACFTA | AIFTA | ATIGA | RCEP
│   ├── ShipDutyTypeEnum.php         # BM | BM_KITE
│   ├── ShipFreeTimeBasisEnum.php    # DAYS | CARRIER_STANDARD | NOT_APPLICABLE
│   ├── ShipCarrierRuleTypeEnum.php  # ALLOW_ONLY | EXCLUDE
│   ├── ShipPaymentRequestStatusEnum.php  # DRAFT | SENT | PAID  (no REJECTED)
│   └── ShipProvisionSourceTypeEnum.php   # SI | ESC | MANUAL | FILE
├── Interfaces/Transaction/Shipment/
│   ├── ShipProvisionRepositoryInterface.php
│   ├── ShipBillRepositoryInterface.php
│   ├── ShipTariffRepositoryInterface.php
│   ├── ShipMasterRepositoryInterface.php        # cost type / container / activity / facility / port
│   ├── ShipPostingAccountRepositoryInterface.php
│   ├── ShipParameterRepositoryInterface.php
│   │   # — front office —
│   ├── ShipBimRepositoryInterface.php
│   ├── ShipImportFileRepositoryInterface.php       # incl. shippedQtyByPoLine() for the remainder
│   ├── ShipExportSiRepositoryInterface.php         # incl. unprovisionedApprovedCosts()
│   ├── ShipPaymentRequestRepositoryInterface.php   # incl. lastDoPayments(shippingLine, 3)
│   ├── ShipHsTariffRepositoryInterface.php         # MFN + preference lookups
│   ├── ShipKursPajakRepositoryInterface.php
│   ├── ShipDocTypeRepositoryInterface.php
│   ├── ShipTransitTimeRepositoryInterface.php
│   ├── ShipSkbRepositoryInterface.php
│   └── Erp/
│       ├── ShipSalesDocRepositoryInterface.php  # ESC / STA / EDN / EIN (export)
│       ├── ShipPurchaseDocRepositoryInterface.php # PO lists (import)
│       ├── ShipVendorRepositoryInterface.php    # vendor, vendor bank, NPWP/SKB
│       ├── ShipAccountRepositoryInterface.php   # fm_acnt_comp / fm_acnt_curr checks
│       └── ShipFakturRepositoryInterface.php    # mgt_bill_fp_scan_v
├── Repositories/Transaction/Shipment/…         # Eloquent* implementations
├── Repositories/Erp/Shipment/…                 # Orion (oracle_mgtdat) implementations
├── Services/Transaction/Shipment/
│   ├── ShipProvisionService.php        # CRUD + workflow for the provision
│   ├── ShipBillService.php             # CRUD + workflow for the settlement
│   ├── ShipTariffService.php           # tariff resolution (RATE/FIX/TIER — both modules call this)
│   ├── ShipCostCalculator.php          # the amount formulas, pure
│   ├── ShipInsuranceService.php        # INS cost from EDN value + Inco Term
│   ├── ShipCommissionService.php       # COMM cost from ESC agent terms
│   ├── ShipAccountResolver.php         # posting-account resolution
│   ├── ShipDocumentNumberService.php   # SysIdHelper wrappers
│   ├── ShipStatusService.php           # transition guard + activity log
│   ├── ShipVoucherService.php          # builds voucher payloads, delegates posting
│   │   # — front office —
│   ├── ShipBimService.php              # BIM CRUD, template inherit, ESC rate pull, flex-guard scan
│   ├── ShipBimMergeService.php         # multi-BIM → one SI: refuse / warn / strictest (spec §12.7)
│   ├── ShipImportFileService.php       # BL CRUD + status, PO pull with remaining qty
│   ├── ShipPibEstimator.php            # pure: Mode A/B, allocations, the 3 rounding rules (spec §11.7)
│   ├── ShipDutyRounding.php            # pure: CEIL_1000 / TRUNC_1 / FLOOR_1000, parameter-driven
│   ├── ShipBmResolver.php              # preference → MFN → manual, + the fallback exposure figure
│   ├── ShipKursPajakService.php        # KMK rate for a PIB date, snapshot + recompute with history
│   ├── ShipSkbService.php              # validity at the PIB date, expiry warning
│   ├── ShipDocChecklistService.php     # evaluates ship_doc_type against a BL → checklist rows
│   ├── ShipTransitTimeService.php      # port-pair → country resolution, notify lead days
│   ├── ShipChargeableWeightService.php # volumetric vs gross (spec §11.2)
│   ├── ShipExportSiService.php         # SI CRUD, BIM pull + snapshot, status, cancel screen
│   ├── ShipSiCostService.php           # cost lines, per-line approval, void
│   ├── ShipSiProvisionPostingService.php  # APPROVED + spv null → provision, per period
│   └── ShipPaymentRequestService.php   # DRAFT→SENT→PAID, last-3-DO helper, variance capture
├── Services/Master/Shipment/…          # one service per master (14 in total)
├── Livewire/Transaction/Shipment/
│   ├── ShipProvisionList.php           # list + search + filters
│   ├── ShipProvisionInput.php          # create / edit (both directions)
│   ├── ShipProvisionDetail.php         # read-only + workflow buttons + vouchers
│   ├── ShipBillList.php
│   ├── ShipBillInput.php
│   ├── ShipBillDetail.php
│   └── Components/
│       ├── ShipContainerEditor.php     # container grid (provision + bill)
│       ├── ShipDocPicker.php           # EDN picker (export) / PO picker (import)
│       ├── ShipCostGrid.php            # the cost-line grid, duty mode switch
│       ├── ShipProvisionPicker.php     # "what to settle" modal (single + multi)
│       └── ShipFakturPicker.php
│   # — front office —
│   ├── ShipBimList.php  ShipBimInput.php  ShipBimTemplateManager.php
│   ├── ShipImportFileList.php  ShipImportFileInput.php  ShipImportFileDetail.php
│   ├── ShipExportSiList.php  ShipExportSiInput.php  ShipExportSiDetail.php
│   ├── ShipExportSiCancel.php          # the cancel SCREEN, not a button (spec §12.4)
│   ├── ShipPaymentRequestList.php  ShipPaymentRequestInput.php
│   └── Components/
│       ├── ShipBimPicker.php           # multi-select BIM → SI, shows merge conflicts inline
│       ├── ShipImportItemGrid.php      # PO pull with remaining qty, HS + BM% per item
│       ├── ShipPibEstimatePanel.php    # estimate vs actual, 3-way variance, exposure pair
│       ├── ShipDocChecklist.php        # generated rows + upload + version history
│       └── ShipSiCostGrid.php          # baseline + additional lines, per-line approve/void
├── Livewire/Master/Shipment/…          # 14 master CRUD pages (8 + 6 front office)
├── Livewire/Dashboard/ShipmentDashboard.php
├── Livewire/Dashboard/EximFrontOfficeDashboard.php   # arriving BLs, doc gaps, SIs without EIN
├── Jobs/Transaction/Shipment/          # Phase 2 exports, backfill job
├── Exports/Transaction/Shipment/       # Phase 2
├── Models/MgtHris/Transaction/Shipment/…   # 10 + 17 transaction models
├── Models/MgtHris/Master/Shipment/…        # 8 + 6 master models + 3 views
├── Models/MgtDat/Shipment/…                # Orion read models (see §5)
├── Notifications/Shipment/
│   ├── ShipEtaApproachingNotification.php      # ETA − N, to Purchase
│   ├── ShipDocChecklistChangedNotification.php
│   ├── ShipDocIncompleteEscalation.php         # ETA − 1, includes the exposure figure
│   └── ShipPibVarianceNotification.php         # estimate vs actual over threshold
└── Console/Commands/Shipment/
    ├── BackfillShipmentControl.php     # the one-off legacy backfill
    ├── VerifyShipmentBackfill.php      # reconciliation report
    ├── ImportBimLegacyCommand.php      # BIM upload + assisted mapping (data-migration.md §9)
    └── ShipEximNotifySweepCommand.php  # the daily ETA / checklist / EIN-gap sweep
```

The notification sweep is a **single scheduled command**, not four. All four triggers read the same two
tables and the same masters; four commands would mean four passes over the same rows and four places to
keep the lead-day logic consistent. It follows `.docs-me/StandardCronJobCreation.md`: its own log
channel (`exim_notify`), set-based queries rather than per-row loops, and an activity-log entry per
notification raised.

Views: `Modules/Finance/resources/views/livewire/transaction/shipment/*.blade.php`,
`…/master/shipment/*.blade.php`. Blade component namespace stays `finance::`.

---

## 2. Models

All transaction and master models: `protected $connection = 'oracle_mgthris';`,
`protected $keyType = 'int';` with `$incrementing` left at its **default of true** (see `schema.md` —
false breaks `getKey()` after a create), `const CREATED_AT = '<prefix>_created_timestamp';`,
`const UPDATED_AT = '<prefix>_modified_timestamp';`, `use Searchable, LogsActivityWithDescription;` and
an explicit `$fillable`. The code-PK masters set `$incrementing = false` and `$keyType = 'string'`.

The two `*_created_by` / `*_modified_by` columns come from
`Modules\Finance\Models\Concerns\StampsShipAudit`, which derives their names from the two timestamp
constants — so a model names its prefix once, and the backfill and the sweeps (which run with no
logged-in user) stamp `SYSTEM` rather than null. The timestamps themselves are Eloquent's, through the
constants.

Two relations cannot be expressed honestly in Eloquent and are documented as such on the models:
`costType()` and `activity()` join on the code alone, but `ship_cost_type` and `ship_activity` are
unique on **(code, direction)**. The relation may therefore attach the wrong direction's row — the names
match, the behaviour flags do not. Anywhere a flag is read, go through
**`ShipMasterRepositoryInterface::costType($code, $direction)` / `activity($code, $direction)`**, which
prefer the direction-specific row over the `BOTH` row and return null when the code belongs to the other
direction only. The relation is for showing a name.

| Model | Relations |
|---|---|
| `ShipProvision` | `invoices()` hasMany · `containers()` · `docs()` · `costs()` hasManyThrough(invoices) · `bills()` hasMany(`ShipBill`, `stl_spv_sys_id`) · `source()` belongsTo self · `costType()` belongsTo |
| `ShipProvisionInvoice` | `provision()` · `costs()` |
| `ShipProvisionCost` | `invoice()` · `billCost()` hasOne(`ShipBillCost`, `stc_spc_sys_id`) · `activity()` · `tariff()` |
| `ShipBill` | `provision()` belongsTo · `invoices()` · `containers()` · `docs()` · `costs()` hasManyThrough |
| `ShipBillInvoice` | `bill()` · `costs()` |
| `ShipBillCost` | `invoice()` · `provisionCost()` belongsTo(`ShipProvisionCost`, `stc_spc_sys_id`) |
| masters | `ShipTariff::activity()`, `ShipActivity::costTypeMaster()`, … |

`$searchable` on `ShipProvision` covers `spv_trans_no`, `spv_si_no`, `spv_ein_no`, `spv_aju_no`,
`spv_pib_no`, `spv_bl_no`, `spv_invoice_no`, `spv_vendor_name`, `spv_prov_voucher`, `spv_pay_voucher`
plus the relation columns `docs.spd_doc_no`, `docs.spd_esc_no`, `docs.spd_sta_no` — the `Searchable`
trait already handles relation-qualified entries.

Casts: dates as `date`, timestamps as `datetime`, `*_is_cross_currency` / `*_pph_advanced` /
`shc_*` flags as `boolean`, `spv_status` / `stl_status` as `ShipStatusEnum::class`,
`spv_direction` as `ShipDirectionEnum::class`, amounts as `decimal:2`, rates as `decimal:6`.

Front-office models, same conventions. The relations worth naming:

| Model | Relations |
|---|---|
| `ShipBim` | `template()` belongsTo · `notifyParties()` · `carrierRules()` · `freightHistory()` · `sis()` belongsToMany(`ShipExportSi`, `ship_export_si_bim`) |
| `ShipImportFile` | `invoices()` · `items()` hasManyThrough(invoices) · `containers()` · `docs()` · `skb()` belongsTo · `port()` belongsTo · `provisions()` hasMany(`ShipProvision`, `spv_sif_sys_id`) · `paymentRequests()` |
| `ShipImportItem` | `invoice()` belongsTo · `file()` through the invoice |
| `ShipExportSi` | `bims()` belongsToMany · `costs()` · `containers()` · `edns()` · `docs()` · `provisions()` hasMany(`ShipProvision`, `spv_ses_sys_id`) |
| `ShipExportSiCost` | `si()` belongsTo · `provision()` belongsTo(`ShipProvision`, `sec_spv_sys_id`) · `provisionCost()` hasOne(`ShipProvisionCost`, `spc_sec_sys_id`) |
| `ShipProvision` | gains `importFile()` and `exportSi()` belongsTo, plus `siCosts()` hasMany(`ShipExportSiCost`, `sec_spv_sys_id`) |

`$searchable` on `ShipImportFile`: `sif_trans_no`, `sif_bl_no`, `sif_aju_no`, `sif_pib_no`,
`sif_supplier_name`, `sif_vessel`, plus the relation columns `invoices.sii_invoice_no` and
`items.sit_po_no`. On `ShipExportSi`: `ses_trans_no`, `ses_ein_no`, `ses_peb_no`, `ses_bl_no`,
`ses_vessel`, plus `bims.sbm_esc_no` and `edns.sen_edn_no`.

**`ses_cost_complete` and `sif_doc_complete_flag` are stored columns that a service recomputes**, not
accessors. They are filtered and sorted on in list pages and in the notification sweep, and an accessor
cannot be indexed.

---

## 3. DTOs

Spatie Laravel Data + `WireableData`, because the input pages bind arrays of them straight to Livewire.
Rules follow LcControl's DTO typing note: a non-nullable numeric property must be `required` in its
rule, and a blank input must be normalised to `0` (or the property made nullable) before the DTO is
constructed — otherwise Livewire hands `""` to a `float` and the page 500s.

- `ShipProvisionData` — the header; carries `direction`, and its `headerRules($payload)` switches the
  conditional document requirements on it and on the cost type. The cost-type **flags travel in the
  payload** (`requires_vendor`, `requires_vendor_bank`, `has_payment_voucher`) rather than the DTO
  loading the master, which keeps the rules pure and testable; they default to the stricter reading, so
  a forgotten flag asks for too much rather than too little.
- The `spec.md` §3.2 case names are static rule methods on the DTO that owns the data
  (`headerRules`, `invoiceRules`, `costRules`, `dutyRules`, `containerRules`, `docRules`,
  `paymentRules`, `voucherDateRules`, `confirmRules`). Composing them into `submit` / `confirm`, and the
  collection-level rules a DTO cannot see (at least one EDN, invoice numbers distinct within the
  document), belong to the service.
- `ShipProvisionCostData` / `ShipBillCostData` — one cost line, with the computed amounts as
  **read-only outputs** the calculator writes back, never user input.
- `Concerns\CalculatesLineAmounts` — shared trait so the provision and bill line DTOs cannot drift.

Front-office DTOs live in `Data/Transaction/Shipment/` alongside them: `ShipBimData`,
`ShipBimTemplateData`, `ShipImportFileData`, `ShipImportInvoiceData`, `ShipImportItemData`,
`ShipExportSiData`, `ShipExportSiCostData`, `ShipPaymentRequestData`, plus `ShipDocChecklistRowData`.

Two rules specific to them:

- **`ShipExportSiCostData` uses the same `CalculatesLineAmounts` trait** as the provision and bill line
  DTOs. An SI cost line, a provision line and a bill line are the same arithmetic at three points in
  time; a fourth copy of the formula is how the four-point variance chain (`spec.md` §12.1) would start
  reporting differences that are really rounding.
- **Computed outputs are never writable inputs.** `sit_value_cif_idr`, the four duty columns,
  `ses_chargeable_kg` and `sif_eta_calculated` are written by their service and are read-only on the
  DTO. `PRD EXIM.md` is explicit that chargeable weight must not be typed — it is the number most often
  disputed with a forwarder, and a hand-typed one cannot be defended.

---

## 4. Services

### `ShipProvisionService`
`list()`, `find()`, `startFromSalesContract()`, `startFromProvision()`, `save()`, `submit()`,
`revoke()`, `confirm()`, `amend()`, `delete()`. `save()` runs in one `oracle_mgthris` transaction:
upsert header → sync containers → sync docs → sync invoices (keyed by cost type + vendor) → sync cost
lines (keyed by PK, deleting what the screen dropped) → recompute rollups → log field-level changes.
Confirm delegates to `ShipStatusService` and then `ShipVoucherService`.

### `ShipBillService`
Same shape, plus `startFromProvisionInvoice()`, `startFromProvisionLines(array $costIds)` (the
multi-provision merge) and `generatePayment()`. Every seeded line keeps `stc_spc_sys_id`.

### `ShipTariffService`
`resolve(direction, vendorCode, portCode, containerType, ShipQuantityContext $qty, onDate): ShipTariffResolutionData`
— `lines` (a neutral `ShipResolvedTariffLineData` each), `notices` and `matchedStep`. **Built as a
result object rather than the bare line array first sketched here**: two cases §4.1 insists on
reporting — a call with no port code, and a vendor with no tariff at all — produce no line to hang the
warning off, and a warning nobody can see is the failure the flagged-zero rule exists to prevent.

Each line is a neutral DTO (activity, quantity, rate, currency, the three default accounts, the tariff's
`sys_id`, the `no_tariff_match` flag) which the caller maps into its own line DTO:
`ShipProvisionCostData` on a provision, `ShipExportSiCostData` on an SI. The service returns neither, so
adding a third consumer needs no change here.

The vendor+port ladder is four steps (vendor+port → vendor+any → `ALL`+port → `ALL`+any), the first
non-empty result winning. Rows are grouped by `sht_tier_group` and the shape applied: `RATE` one line,
`FIX` one line at quantity 1, **`TIER` one line per consumed band** — see `spec.md` §4.1 and do not
shortcut it to a band lookup. A group matching no band returns a line at amount 0 with
`no_tariff_match = 1` rather than nothing, so the gap is visible instead of appearing later as an
unexplained vendor charge.

`ShipQuantityContext` carries container count, CBM, gross weight, chargeable weight and a day count, and
the service picks from it by `sht_uom` — the caller does not decide which number is the quantity. That
keeps one service serving both the provision screens and the SI cost grid without a second signature.

Returns lines stamped with the tariff's `sys_id` for provenance. **This is the single pricing entry point
for both modules**; if the front office ever needs behaviour this service does not have, it is added
here, not reimplemented.

### `ShipCostCalculator`
Pure, no DB: `line(ShipProvisionCostData|ShipBillCostData $line, string $headerCurrency, float $rate,
bool $isCC): void` writes the six amount fields; `invoice()` and `header()` roll them up. This is where
the formulas in `spec.md` §4.2–4.4 live, and it is the most heavily unit-tested class in the module.

### `ShipAccountResolver`
`resolve(purpose, direction, document, costType, activityCode, condition): ?array{main, sub}` — walks
`ship_posting_account` by `spa_priority`, most specific first. Falls back to the activity master, then
to the PO's account for import, then null (which the validator turns into a friendly error). No account
number appears in PHP.

### `ShipVoucherService`
Builds the voucher payload for a document + voucher type, then hands it to the ERP posting services.
It does **not** know how to write ERP rows — that is the repositories' job (§5).

```
postProvisionJournal(ShipProvision $p): string        # EPJV / IPJV, dated spv_prov_date
postProvisionPayment(ShipProvision $p): string        # ADVP / BPS, dated spv_pay_date
postBillJournal(ShipBill $b): string                  # EBJV / IBJV, dated stl_bill_date
postBillPayment(ShipBill $b): string                  # ADVP-EXP / ADVP, dated stl_pay_date
```

Each returns the `{tranCode}-{docNo}` reference the caller stores. Which voucher type applies comes from
`ship_cost_type`, never from a `match` on a string literal.

**Period guard.** `postProvisionJournal()` resolves and checks the ERP accounting period **before**
building anything, and throws a `ClosedPeriodException` naming the period and the date. Export provisions
are routinely backdated to the EIN date, so this path is hit in normal use, not only in error
(`spec.md` §12.8).

### Front-office services worth a contract

```
ShipPibEstimator::estimate(ShipImportFile $bl): ShipPibEstimateResult
```
Pure — takes a fully loaded BL (invoices, items, kurs, premium) and returns per-item, per-invoice and
per-BL figures plus the two exposure totals. **No DB, no parameters read inside**: the rounding rules and
both PPN rates are injected as a `ShipDutyRules` value object built from `ship_parameter` by the caller,
so the 10-PIB regression can pin them and a parameter change cannot silently rewrite a passing test.
The Mode A / Mode B decision is made **inside**, from the incoterm; there is no argument for it.

```
ShipSiProvisionPostingService::post(ShipExportSi $si, CarbonInterface $date): ShipProvision
```
Takes `sec_status = APPROVED AND sec_spv_sys_id IS NULL`, refuses when `ses_ein_no` is empty, checks the
period, posts one provision, stamps the lines both ways (`sec_spv_sys_id` and `spc_sec_sys_id`) in the
same transaction. Called once at EIN time and again whenever a later cost line is approved — the second
call is the normal case, not an exception (`spec.md` §12.3).

```
ShipBimMergeService::merge(array $bimIds): ShipBimMergeResult
```
Returns the merged terms plus **refusals and warnings as data**, so the picker can show them inline
before anything is created. A refusal names the field and the two ESC numbers that disagree; "cannot
combine" on its own sends the user hunting.

```
ShipDocChecklistService::generate(ShipImportFile $bl): array<ShipDocChecklistRowData>
```
Evaluates every active `ship_doc_type` against the BL's origin country, HS prefixes, incoterm, facility
and transport mode. The mandatory result is **snapshotted** onto each row, so editing the master later
changes the next BL's checklist and not this one.

---

## 5. ERP integration (`oracle_mgtdat`)

Read models under `Models/MgtDat/Shipment/`, all read-only (`$timestamps = false`, no writes):

| Purpose | Source |
|---|---|
| Export sales docs | `ot_so_head` (ESC / STA), `ot_invoice_head` / `_item` / `_ref` (EDN, EIN), `ot_so_item_ted` (agent terms), `om_expense` |
| Import purchase docs | `ov_po_mgt` (all POs), `mgt_po_planning_v` (valid POs with values) |
| **Contract freight rate** (front office) | `ot_so_item.soi_flex_10` — the agreed rate, per container in USD — with the contract incoterm in `ot_so_head.soh_flex_01`. Pulled into the BIM once and frozen (`spec.md` §10.6). Confirm the field: `open-questions.md` S3a |
| **PO remaining quantity** (front office) | `ov_po_mgt` for the PO quantity, minus `Σ sit_shipped_qty` from **our own** `ship_import_item` — the shipped side is ours, not the ERP's, so this is one query across both connections and cannot be a single join. Compute the shipped totals first, keyed by (PO no., PO line), then subtract |
| Parties | `om_supplier` (vendor, NPWP/SKB `supp_flex_07..10`), `om_customer` — read through the `v_ship_*` views on `oracle_mgthris`, not directly |

> **Correction (T009, checked on production 2026-09-02):** the agent → vendor step in §4.6 has no data
> behind it. `om_expense` carries **no supplier code** — `AGENT` rows have all 20 flex columns null, and
> only 14 of 123 `AGENT` names match an `om_supplier` name exactly. `ShipSalesDocRepository::expense()`
> therefore returns the expense row and nothing more; see `open-questions.md` **Q10**.
| Banks | `fm_bank_acnt_detail` (company), `fm_supplier_bank_cont_detail` (vendor) |
| Chart of accounts | `fm_acnt_comp` (existence), `fm_acnt_curr` (main+sub+currency combination) |
| Faktur Pajak | `mgt_bill_fp_scan_v` |
| Exchange rate | `fm_exchange_rate` — via the **existing** `Modules\LcControl\Services\ExchangeRateService` pattern; Finance gets its own copy rather than depending on LcControl (module isolation), or the service moves to `Modules/Core` if a third module needs it |
| GRN (Phase 2 report) | `ot_gr_head` / `ot_gr_item` |

**Voucher posting (D3 — PHP port).** Two write paths, both mirroring LcControl exactly:

| Path | Tables | Voucher types |
|---|---|---|
| Journal | `ft_unposted_trans_header` + `ft_unposted_trans_detail` | EPJV, EBJV, IPJV, IBJV |
| Payment | `ft_payment_header` + `fs_payment` + `ft_payment_oth_acnt_detail` | ADVP, ADVP-EXP, BPS |

Both resolve the accounting period from `fm_acnt_period`, lock and advance `fm_tran_doc_no` for the
tran code, and build the doc no as `calYear + MM + 4-digit seq`. Implemented as
`Repositories/Erp/Shipment/EloquentShipJournalVoucherRepository` and
`…EloquentShipPaymentVoucherRepository`.

> **Reuse note.** `Modules\LcControl\Repositories\Erp\EloquentJournalVoucherRepository` already does
> this correctly. Modules must not reach into each other, so the options are (a) copy the two
> repositories into Finance, or (b) promote them plus their DTOs to `Modules/Core` and have both
> modules depend on Core. **Decide in T00 of `plan.md` §1 (decision D7); the plan's default is (b)** —
> two independent copies of doc-no/period plumbing will drift, and it is the one piece of this module
> that must never be wrong.

---

## 6. Livewire pages

| Page | Route name suffix | Notes |
|---|---|---|
| `ShipProvisionList` | `provision.index` | filters: direction, status[], cost type, date range, vendor, document no. `WithPagination`. Direction comes from a query string so the sidebar can link Export / Import separately |
| `ShipProvisionInput` | `provision.create` / `provision.edit` | one component for both directions; the cost-type master's flags drive which panels and columns render |
| `ShipProvisionDetail` | `provision.detail` | read-only view + submit/revoke/confirm/amend/delete + voucher modal + print |
| `ShipBillList` | `bill.index` | filters incl. pay status (PAID / UNPAID / OVERDUE) |
| `ShipBillInput` | `bill.create` / `bill.edit` | source toggle, provision picker (single + multi), variance column |
| `ShipBillDetail` | `bill.detail` | + generate payment |
| `ShipmentDashboard` | `dashboard` | Aju/SI-keyed reconciliation table + counters |
| 14 master pages | `master.*` | standard master CRUD (`mutugading-crud` recipe); the port page is a 3-row list, so it stays deliberately plain |

Front office:

| Page | Route name suffix | Notes |
|---|---|---|
| `ShipBimList` / `ShipBimInput` | `bim.index` / `.create` / `.edit` | keyed on ESC no., not a trans no. The save button runs the free-text scan and shows soft warnings without blocking |
| `ShipBimTemplateManager` | `bim.template` | per customer; editing one moves every BIM not yet pulled into an SI, so the page states that plainly before saving |
| `ShipImportFileList` / `Input` / `Detail` | `import-file.*` | the input page is three nested levels (BL → invoice → item); the estimate panel and the checklist are components, not tabs of their own component |
| `ShipExportSiList` / `Input` / `Detail` | `export-si.*` | input starts from the BIM picker; the detail page owns the cost grid and the per-line approvals |
| `ShipExportSiCancel` | `export-si.cancel` | its own page: line-by-line keep / void plus an optional fee. A modal is too small for a decision with money in it |
| `ShipPaymentRequestList` / `Input` | `payment-request.*` | the DO form shows the last 3 DO payments for the shipping line; Finance's pay action captures the variance and its reason |
| `EximFrontOfficeDashboard` | `exim.dashboard` | BLs arriving within N days, incomplete checklists with their exposure, SIs shipped without an EIN, cost lines awaiting approval, lines priced at zero |

Conventions: services injected in `boot()`, never the constructor; toasts via
`$this->dispatch('butter-success', message: …)`; `wire:key` in every loop; **no `wire:navigate`** and no
`navigate: true` redirects; Flux/UI-module components before anything custom; heavy grids get
`wire:loading` states; `#[Title]` only where the tab text must differ from the breadcrumb — otherwise
define the breadcrumb and let `PageTitleHelper` do it.

The cost grid is the one place worth extracting (`Components/ShipCostGrid`): four screens render it,
and legacy's duplicated copies are why the export and import calculations drifted.

---

## 7. Routes

`Modules/Finance/routes/web.php`, inside the existing `dashboard/module-finance` prefix:

```php
Route::prefix('transaction/shipment')->name('transaction.shipment.')->group(function () {
    Route::get('/', ShipmentDashboard::class)->middleware('can:finance-shipment-dashboard-view')->name('dashboard');

    Route::get('provisions', ShipProvisionList::class)->middleware('can:finance-shipment-provision-view')->name('provision.index');
    Route::get('provisions/create', ShipProvisionInput::class)->middleware('can:finance-shipment-provision-create')->name('provision.create');
    Route::get('provisions/{transNo}', ShipProvisionDetail::class)->middleware('can:finance-shipment-provision-view')->name('provision.detail');
    Route::get('provisions/{transNo}/edit', ShipProvisionInput::class)->middleware('can:finance-shipment-provision-edit')->name('provision.edit');

    Route::get('bills', ShipBillList::class)->middleware('can:finance-shipment-bill-view')->name('bill.index');
    Route::get('bills/create', ShipBillInput::class)->middleware('can:finance-shipment-bill-create')->name('bill.create');
    Route::get('bills/{transNo}', ShipBillDetail::class)->middleware('can:finance-shipment-bill-view')->name('bill.detail');
    Route::get('bills/{transNo}/edit', ShipBillInput::class)->middleware('can:finance-shipment-bill-edit')->name('bill.edit');
});

Route::prefix('master/shipment')->name('master.shipment.')->group(function () { /* 14 pages */ });
```

The front office adds, inside the same `transaction/shipment` prefix:

```php
Route::get('exim', EximFrontOfficeDashboard::class)->middleware('can:finance-shipment-exim-dashboard-view')->name('exim.dashboard');

Route::get('bim', ShipBimList::class)->middleware('can:finance-shipment-bim-view')->name('bim.index');
Route::get('bim/create', ShipBimInput::class)->middleware('can:finance-shipment-bim-create')->name('bim.create');
Route::get('bim/templates', ShipBimTemplateManager::class)->middleware('can:finance-shipment-bim-template-manage')->name('bim.template');
Route::get('bim/{escNo}', ShipBimInput::class)->middleware('can:finance-shipment-bim-edit')->name('bim.edit');

Route::get('import-files', ShipImportFileList::class)->middleware('can:finance-shipment-import-file-view')->name('import-file.index');
Route::get('import-files/create', ShipImportFileInput::class)->middleware('can:finance-shipment-import-file-create')->name('import-file.create');
Route::get('import-files/{transNo}', ShipImportFileDetail::class)->middleware('can:finance-shipment-import-file-view')->name('import-file.detail');
Route::get('import-files/{transNo}/edit', ShipImportFileInput::class)->middleware('can:finance-shipment-import-file-edit')->name('import-file.edit');

Route::get('export-si', ShipExportSiList::class)->middleware('can:finance-shipment-si-view')->name('export-si.index');
Route::get('export-si/create', ShipExportSiInput::class)->middleware('can:finance-shipment-si-create')->name('export-si.create');
Route::get('export-si/{transNo}', ShipExportSiDetail::class)->middleware('can:finance-shipment-si-view')->name('export-si.detail');
Route::get('export-si/{transNo}/edit', ShipExportSiInput::class)->middleware('can:finance-shipment-si-edit')->name('export-si.edit');
Route::get('export-si/{transNo}/cancel', ShipExportSiCancel::class)->middleware('can:finance-shipment-si-confirm')->name('export-si.cancel');

Route::get('payment-requests', ShipPaymentRequestList::class)->middleware('can:finance-shipment-payment-request-view')->name('payment-request.index');
Route::get('payment-requests/create', ShipPaymentRequestInput::class)->middleware('can:finance-shipment-payment-request-create')->name('payment-request.create');
Route::get('payment-requests/{transNo}', ShipPaymentRequestInput::class)->middleware('can:finance-shipment-payment-request-view')->name('payment-request.edit');
```

**BIM's route key is the ESC no.**, the only identifier for it that anyone quotes; everything else keys
on its trans no. as the Shipment Control pages do. Cancelling an SI has **no permission of its own**
(`spec.md` §10.4) — whoever may confirm may cancel, so the cancel route reuses `-si-confirm`.

**Route keys are the direction and the trans no., not the sys id** — a change from legacy's `?transno=`
query string, and it drops the "exactly 10 digits or HTTP 400" hack: a missing document is a 404 through
the repository. Every route that opens one is
`…/provisions/{direction}/{transNo}` (`export` or `import`, constrained on the group), because **the
number by itself does not name a document**: legacy ran a series per direction and 454 migrated
provision numbers and 638 settlement numbers exist once on each side (open-questions.md Q12). Behind it,
`findByTransNo()` takes the direction and returns null rather than an arbitrary row when a number matches
two, so the wrong document cannot open even from a hand-typed URL.

Breadcrumbs go in `Modules/Finance/routes/breadcrumbs.php` for every route above — required, and it is
what gives each page its browser tab title via `PageTitleHelper`.

---

## 8. Permissions

Seeded by `FinanceRolesAndPermissionsSeeder` (extend, don't replace):

```
finance-shipment-dashboard-view
finance-shipment-export              -import          # which documents, not which actions (Q2)
finance-shipment-provision-view      -create  -edit  -delete  -submit  -confirm  -amend  -generate
finance-shipment-bill-view           -create  -edit  -delete  -submit  -confirm  -amend  -generate
finance-shipment-master-manage                        # NOT in the Finance role's grant (Q7)
finance-shipment-report-view

# — front office —
finance-shipment-exim-dashboard-view
finance-shipment-bim-view            -create  -edit  -delete
finance-shipment-bim-template-manage
finance-shipment-import-file-view    -create  -edit  -delete  -estimate
finance-shipment-si-view             -create  -edit  -delete  -confirm  -cost-approve
finance-shipment-payment-request-view  -create  -send  -pay
```

Front-office roles, and why the split falls where it does. The names below are the people; the seeder
spells them `Exim - Sales`, `Exim - Import`, `Exim - Export` and `Exim - Purchase` (T040), matching the
`Lc Control - Admin` shape the rest of the app uses. Finance keeps the role it has.

| Role | Holds |
|---|---|
| **Sales / Marketing** | `-bim-*` and `-bim-template-manage`, nothing else. BIM moved into this module, so sales become users of it — same job, different platform |
| **Import staff** | `-import-file-*`, `-payment-request-view/-create/-send`, and the provision permissions they already have |
| **Export staff** | `-si-*` including `-cost-approve`, and the export provision permissions |
| **Finance (AP)** | `-payment-request-pay` plus the existing bill permissions. Paying is theirs; requesting is not |
| **Purchase** | `-import-file-view` only. They receive the ETA−N notification and chase the supplier's originals; they enter nothing (`open-questions.md` O5 confirms whether even the view is wanted) |

`-estimate` is separate from `-edit` because recomputing an estimate rewrites the figure Finance is
about to pay against, and the recompute keeps history precisely so that action is attributable.
`-cost-approve` is separate from `-confirm` because additional cost is approved line by line, long after
the document is confirmed — often by a different person (`spec.md` §12.2).

Legacy mapping: `create-provision-export` / `create-provision-import` → `…-provision-create`, **plus
one of `finance-shipment-export` / `-import`**. Q2 was answered "separate" on 2026-09-10, so the action
permissions cover both sides and that pair says which documents a user may reach. A user allowed export
alone still holds `-provision-create`; the import documents are simply not theirs to open. One trait,
`AppliesShipDirectionAuthorization`, holds the rule, and it is applied on both lists, both input
screens, both detail pages, the provision picker, the dashboard and the print controller — the last of
those on its own route, because a printed sheet leaves the building. The six reports are deliberately
outside it: read-only, over the whole history, behind `-report-view` alone.

There is no per-direction copy of each action. Splitting all sixteen provision and settlement actions
per direction is 32 permissions for a distinction nobody asked to vary that finely — nothing suggests
someone may confirm an export but only submit an import — and the action permissions are still there to
split if that day comes.

**`finance-shipment-master-manage` is granted to Super Admin only** (Q7, same date): the eight masters
decide what a provision posts to and what a tariff charges, so they are held by the people who own that
data, named one at a time, rather than by everyone who can key a settlement.

`-confirm` additionally requires the user's Orion id (`hmemd_user_orion`) at call time, because the ERP
row stores it — same rule as legacy, but checked in the service, not the Blade.

---

## 9. Testing

| Suite | Covers |
|---|---|
| Unit | `ShipCostCalculator` (all formula branches incl. cross-currency, PPh advanced, PIB duties, rounding), `ShipTariffService` (progressive `TIER` band consumption producing one line per band, overlapping `FIX` band selection, `RATE` out-of-band warning, the flagged no-match zero, vendor `ALL` fallback, the four-step port ladder, `sht_uom`-driven quantity), `ShipAccountResolver` priority, `ShipStatusEnum` transitions |
| Feature | provision save → submit → confirm happy path per direction; unbilled-line filter; multi-provision merge; duplicate invoice guard; validation matrices; permission gating (403 without each ability) |
| Feature (ERP) | voucher payload building against a SQLite stand-in of the ERP tables — asserts accounts, amounts, DR=CR balance and doc-no shape, not the Oracle package |
| Backfill | `VerifyShipmentBackfill` reconciliation on a seeded legacy fixture |

Front office:

| Suite | Covers |
|---|---|
| Unit | **`ShipPibEstimator` against the 10 issued PIBs** (the module's headline regression — see below); `ShipDutyRounding` (each of the three rules, and PPN using the *unrounded* BM); Mode A with a freight figure entered producing **no** BM increase; `ShipBmResolver` (preference → MFN → manual, and the exposure pair being withheld when a fallback rate is missing); `ShipChargeableWeightService`; `ShipTransitTimeService` (port-pair beating country); `ShipTariffService` progressive `TIER` (10 Jasindo containers → 3 lines, 3,750,000) and overlapping `FIX` bands (50.5 KGS → tier 2) |
| Feature | BL create → items pulled with remaining quantity → estimate → status; a second BL for the same PO line offering only the remainder; checklist generated per origin country / HS / incoterm / facility; SKB expired at the PIB date refusing the exemption; multi-BIM SI merge (identity conflict refused **naming the field**, constraints taken at their strictest); per-line approval of an additional cost on a `SHIPPED` SI; the provision pull taking only `APPROVED` + unprovisioned lines; a second pull months later landing in its own period; a backdated provision into a closed period refused **before** posting; cancel keeping some lines and voiding others; payment request `DRAFT→SENT→PAID` with the variance stored |
| Feature (notifications) | the sweep raising each of the four notifications exactly once per trigger, with per-country lead days and the escalation carrying the exposure figure |

> **The acceptance test for the import side is not a spreadsheet.** `ShipPibEstimator` must reproduce
> **10 real PIBs to the rupiah** for BM, PPN and PPh (`PRD EXIM.md` §12.1). Fixtures go in
> `Modules/Finance/tests/Fixtures/Shipment/pib/`, one file per PIB, each naming its document number so a
> failure can be traced to a piece of paper. Two of the 30 figures are known to differ by ≤ Rp 4 because
> the PDF prints per-item customs value to 3 decimals — assert those two with a tolerance and a comment
> saying why, rather than fudging the expected value.

Factories for all 27 transaction tables + 14 masters, in `Modules/Finance/database/factories/Shipment/`,
plus an `EximFixture` helper building a realistic BL (1 supplier, 2 invoices, 5 items across 2 POs, mixed
HS codes) and an SI drawn from 2 BIMs. Shipment Control's own 19 factories and `ShipmentFixture` (in
`Modules/Finance/tests/Fixtures/Shipment/`) landed with T006 and are the model to follow.

A module's models need `Modules\Finance\Models\Concerns\ResolvesShipmentFactory` for `Model::factory()`
to resolve at all — Laravel's guesser only looks in the application namespace. It **includes**
`HasFactory`, because two traits declaring `newFactory()` collide; use it *instead of* `HasFactory`.
