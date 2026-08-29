# design.md — Shipment Control architecture

Stack: Laravel 12, Livewire 3 (+ Volt where a page is trivial), Flux UI Pro 2, Tailwind 4,
Oracle via `yajra/laravel-oci8`, Spatie Permission 7 + Activitylog 4, Spatie Laravel Data 4, Pest 4.
Pattern: **Livewire → Service → Repository interface → Eloquent repository → Model**.

Everything lives in `Modules/Finance` under a `Shipment/` sub-namespace in each layer, matching how
Finance already separates `RawMaterial`, `Sales` and `FpScan`.

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
│   ├── ShipVoucherTypeEnum.php      # EPJV EBJV IPJV IBJV ADVP ADVP_EXP BPS
│   ├── ShipTariffShapeEnum.php      # TIER | FIX | FLAT
│   └── ShipPostingPurposeEnum.php   # EXPENSE PROVISION PPN PPH DUTY_* BANK
├── Interfaces/Transaction/Shipment/
│   ├── ShipProvisionRepositoryInterface.php
│   ├── ShipBillRepositoryInterface.php
│   ├── ShipTariffRepositoryInterface.php
│   ├── ShipMasterRepositoryInterface.php        # cost type / container / activity / facility / port
│   ├── ShipPostingAccountRepositoryInterface.php
│   ├── ShipParameterRepositoryInterface.php
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
│   ├── ShipTariffService.php           # tariff resolution (TIER/FIX/FLAT)
│   ├── ShipCostCalculator.php          # the amount formulas, pure
│   ├── ShipInsuranceService.php        # INS cost from EDN value + Inco Term
│   ├── ShipCommissionService.php       # COMM cost from ESC agent terms
│   ├── ShipAccountResolver.php         # posting-account resolution
│   ├── ShipDocumentNumberService.php   # SysIdHelper wrappers
│   ├── ShipStatusService.php           # transition guard + activity log
│   └── ShipVoucherService.php          # builds voucher payloads, delegates posting
├── Services/Master/Shipment/…          # one service per master
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
├── Livewire/Master/Shipment/…          # 8 master CRUD pages (incl. ShipPortPage)
├── Livewire/Dashboard/ShipmentDashboard.php
├── Jobs/Transaction/Shipment/          # Phase 2 exports, backfill job
├── Exports/Transaction/Shipment/       # Phase 2
├── Models/MgtHris/Transaction/Shipment/…   # 10 transaction models
├── Models/MgtHris/Master/Shipment/…        # 8 master models + 3 views
├── Models/MgtDat/Shipment/…                # Orion read models (see §5)
└── Console/Commands/Shipment/
    ├── BackfillShipmentControl.php     # the one-off legacy backfill
    └── VerifyShipmentBackfill.php      # reconciliation report
```

Views: `Modules/Finance/resources/views/livewire/transaction/shipment/*.blade.php`,
`…/master/shipment/*.blade.php`. Blade component namespace stays `finance::`.

---

## 2. Models

All transaction and master models: `protected $connection = 'oracle_mgthris';`,
`public $incrementing = false;`, `protected $keyType = 'int';` (string for the code-PK masters),
`const CREATED_AT = '<prefix>_created_timestamp';`, `const UPDATED_AT = '<prefix>_modified_timestamp';`,
`use Searchable, LogsActivityWithDescription;` and an explicit `$fillable`.

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

---

## 3. DTOs

Spatie Laravel Data + `WireableData`, because the input pages bind arrays of them straight to Livewire.
Rules follow LcControl's DTO typing note: a non-nullable numeric property must be `required` in its
rule, and a blank input must be normalised to `0` (or the property made nullable) before the DTO is
constructed — otherwise Livewire hands `""` to a `float` and the page 500s.

- `ShipProvisionData` — the header; carries `direction`, and its `rules()` switches the conditional
  document requirements on it (`required_if:direction,IMPORT` for `aju_no`, and on cost type for
  `port_code` / `port`).
- `ShipProvisionCostData` / `ShipBillCostData` — one cost line, with the computed amounts as
  **read-only outputs** the calculator writes back, never user input.
- `Concerns\CalculatesLineAmounts` — shared trait so the provision and bill line DTOs cannot drift.

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
`resolve(direction, vendorCode, portCode, containerType, qty, onDate): array<ShipProvisionCostData>`.
`$portCode` is the provision's `spv_port_code`; a tariff row naming that port wins over the row that
leaves the port null (`spec.md` §4.1).
Groups the effective tariff rows by `sht_tier_group` and applies the shape (`spec.md` §4.1). Vendor
fallback to `ALL` for import. Returns lines stamped with `spc_tariff_sys_id`.

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

---

## 5. ERP integration (`oracle_mgtdat`)

Read models under `Models/MgtDat/Shipment/`, all read-only (`$timestamps = false`, no writes):

| Purpose | Source |
|---|---|
| Export sales docs | `ot_so_head` (ESC / STA), `ot_invoice_head` / `_item` / `_ref` (EDN, EIN), `ot_so_item_ted` (agent terms), `om_expense` |
| Import purchase docs | `ov_po_mgt` (all POs), `mgt_po_planning_v` (valid POs with values) |
| Parties | `om_supplier` (vendor, NPWP/SKB `supp_flex_07..10`), `om_customer` |
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
| 8 master pages | `master.*` | standard master CRUD (`mutugading-crud` recipe); the port page is a 3-row list, so it stays deliberately plain |

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

Route::prefix('master/shipment')->name('master.shipment.')->group(function () { /* 7 pages */ });
```

**Route keys are the trans no., not the sys id** — a change from legacy's `?transno=` query string, and
it drops the "exactly 10 digits or HTTP 400" hack: a missing trans no. is a 404 through the repository.

Breadcrumbs go in `Modules/Finance/routes/breadcrumbs.php` for every route above — required, and it is
what gives each page its browser tab title via `PageTitleHelper`.

---

## 8. Permissions

Seeded by `FinanceRolesAndPermissionsSeeder` (extend, don't replace):

```
finance-shipment-dashboard-view
finance-shipment-provision-view      -create  -edit  -delete  -submit  -confirm  -amend  -generate
finance-shipment-bill-view           -create  -edit  -delete  -submit  -confirm  -amend  -generate
finance-shipment-master-manage
finance-shipment-report-view
```

Legacy mapping: `create-provision-export` / `create-provision-import` → `…-provision-create` (direction
is data, not a permission — matching how Finance treats its other pages). If Finance needs export and
import split by role, that is one extra pair of permissions and a policy check on `spv_direction`;
raised as an open question in `gap-analysis.md` §6.

`-confirm` additionally requires the user's Orion id (`hmemd_user_orion`) at call time, because the ERP
row stores it — same rule as legacy, but checked in the service, not the Blade.

---

## 9. Testing

| Suite | Covers |
|---|---|
| Unit | `ShipCostCalculator` (all formula branches incl. cross-currency, PPh advanced, PIB duties, rounding), `ShipTariffService` (TIER band consumption, FIX band selection, FLAT cap, vendor `ALL` fallback, port match beating the any-port row), `ShipAccountResolver` priority, `ShipStatusEnum` transitions |
| Feature | provision save → submit → confirm happy path per direction; unbilled-line filter; multi-provision merge; duplicate invoice guard; validation matrices; permission gating (403 without each ability) |
| Feature (ERP) | voucher payload building against a SQLite stand-in of the ERP tables — asserts accounts, amounts, DR=CR balance and doc-no shape, not the Oracle package |
| Backfill | `VerifyShipmentBackfill` reconciliation on a seeded legacy fixture |

Factories for all 10 transaction tables + 7 masters, in `Modules/Finance/database/factories/Shipment/`.
