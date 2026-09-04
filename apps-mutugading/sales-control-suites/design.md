# design.md — architecture

Per **D1** everything lives in `Modules/Finance` under a `SalesControl` domain folder in each layer,
the same way `FpScan` and `RawMaterial` already do. No new nwidart module, no change to `module.json`.

Flow is the house standard: **Livewire → Service → Repository → Model**. Livewire never touches a
model, services never build queries inline, repositories never decide policy.

There is one addition to that stack, and it is the centre of this design: a **gateway** that is the
only place in PHP allowed to talk to `PKG_MGT_PRICE_CTRL`. Everything about minimum prices that is not
paperwork goes through it (D2).

---

## 1. Folder map

```
Modules/Finance/app/
├── Models/MgtHris/                      # the four new tables — MGTHRIS owns them
│   ├── Master/SalesControl/
│   │   └── SalesMinPrice.php
│   └── Transaction/SalesControl/
│       ├── SalesCtlApprRequest.php
│       ├── SalesCtlApprLine.php
│       └── SalesMinPriceCheckLog.php
├── Enums/
│   ├── Master/SalesControl/
│   │   ├── MinPriceStatusEnum.php          # DRAFT | APPROVED | VOID
│   │   └── MinPriceScopeLevelEnum.php      # ITEM | GROUP | ALL
│   └── Transaction/SalesControl/
│       ├── ApprovalRequestStatusEnum.php   # DRAFT | PRINTED | APPROVED | REJECTED | CANCELLED | VOID
│       ├── ApprovalControlTypeEnum.php     # PRICELIST | MINPRICE | OVERDUE | CRLIMIT
│       └── PriceCheckResultEnum.php        # PASS | PASS_NORULE | OVERRIDE | INHERIT | FAIL
├── Data/
│   ├── Master/SalesControl/
│   │   ├── MinPriceData.php
│   │   ├── MinPriceFilterData.php
│   │   └── MinPriceImportRowData.php
│   └── Transaction/SalesControl/
│       ├── ApprovalRequestData.php
│       ├── PriceExceptionLineData.php
│       ├── PriceApprovalDocumentData.php   # BOD doc no / date / signer / file
│       └── PricePreviewLineData.php        # what the gateway returns per SO line
├── Interfaces/
│   ├── Master/SalesControl/
│   │   └── MinPriceRepositoryInterface.php
│   └── Transaction/SalesControl/
│       ├── ApprovalRequestRepositoryInterface.php
│       ├── ApprovalLineRepositoryInterface.php
│       ├── PriceCheckLogRepositoryInterface.php
│       ├── SalesOrderLookupRepositoryInterface.php   # reads MGTDAT: OT_SO_HEAD / OT_SO_ITEM
│       └── PriceControlGatewayInterface.php          # the package (§4)
├── Repositories/
│   ├── Master/SalesControl/
│   │   └── EloquentMinPriceRepository.php
│   └── Transaction/SalesControl/
│       ├── EloquentApprovalRequestRepository.php
│       ├── EloquentApprovalLineRepository.php
│       ├── EloquentPriceCheckLogRepository.php
│       ├── EloquentSalesOrderLookupRepository.php
│       └── OraclePriceControlGateway.php
├── Services/
│   ├── Master/SalesControl/
│   │   ├── MinPriceService.php              # CRUD + overlap + supersede
│   │   └── MinPriceImportService.php
│   └── Transaction/SalesControl/
│       ├── ApprovalRequestService.php       # the shared state machine (§6)
│       ├── ApprovalRequestNumberGenerator.php
│       ├── ApprovalDocumentService.php      # print, attachment, hash
│       ├── PricePreviewService.php          # "which lines are under the floor"
│       └── PriceExceptionService.php        # request build + approve
├── Livewire/
│   ├── Master/SalesControl/
│   │   ├── MinPriceManager.php
│   │   └── MinPriceApprovalManager.php
│   ├── Transaction/SalesControl/
│   │   ├── PriceCheckPreview.php
│   │   ├── PriceExceptionRequest.php
│   │   └── PriceExceptionApproval.php
│   └── Reports/SalesControl/
│       ├── MinPriceActiveReport.php
│       ├── PriceExceptionReport.php
│       └── PriceRejectionReport.php
├── Exports/
│   ├── Master/SalesControl/MinPriceExport.php
│   └── Reports/SalesControl/
│       ├── PriceExceptionExport.php
│       └── PriceRejectionExport.php
├── Imports/Master/SalesControl/MinPriceImport.php
└── Jobs/
    ├── Master/SalesControl/{ImportMinPrice,ExportMinPrice}.php
    └── Reports/SalesControl/ExportPriceControlReport.php

Modules/Finance/resources/views/
├── livewire/master/sales-control/
│   ├── min-price-manager.blade.php
│   ├── min-price-approval-manager.blade.php
│   └── partials/{_filters,_table,_form-modal,_supersede-modal}.blade.php
├── livewire/transaction/sales-control/
│   ├── price-check-preview.blade.php
│   ├── price-exception-request.blade.php
│   ├── price-exception-approval.blade.php
│   └── partials/{_so-lines,_request-form,_approve-modal,_timeline}.blade.php
├── livewire/reports/sales-control/*.blade.php
└── pdf/sales-control/
    ├── min-price-approval-form.blade.php
    └── price-exception-form.blade.php

Modules/Finance/database/migrations/          # schema.md §6
Modules/Finance/database/seeders/SalesControlRolesAndPermissionsSeeder.php
tests/Feature/Finance/SalesControl/           # D11
```

---

## 2. Models

All four: `protected $connection = 'oracle_mgthris';`, explicit `$fillable`, `public $timestamps =
false` (audit columns are written explicitly, house style), `Searchable`, and
`LogsActivityWithDescription` on the three writable ones.

`oracle_mgthris` is the default connection, so the property is technically redundant. Set it anyway,
on every model, as `MgtFpScan` and the raw-material models do — `DB_CONNECTION` is environment-driven,
and a model that silently follows it is a model that reads the wrong schema the first time someone
runs the app with a different default.

`SalesMinPriceCheckLog` gets **no** `$fillable` and no activity log: it is read-only (D4). Give it a
`protected static function booted()` that throws on `creating`/`updating`/`deleting`, and a docblock
saying that after the schema move this guard is the *only* thing stopping a write — MGTHRIS owns the
table now, so there is no missing grant behind it (`schema.md` §1.1). Written down, the guard reads as
load-bearing; undocumented, it reads as something to tidy away.

Keys and casts:

| Model | Table | PK | Notable casts |
|---|---|---|---|
| `SalesCtlApprRequest` | `sales_ctl_appr_request` | `scar_sys_id` | status + ctrl type enums, all five dates, `scar_amount` decimal |
| `SalesCtlApprLine` | `sales_ctl_appr_line` | `scal_sys_id` | rates/qty as `decimal:6` / `decimal:3` |
| `SalesMinPrice` | `sales_min_price` | `smp_sys_id` | status + scope-level enums, `smp_min_price_usd` `decimal:3`, `smp_tolerance_pct` `decimal:2` |
| `SalesMinPriceCheckLog` | `sales_min_price_check_log` | `smpcl_sys_id` | result enum, `smpcl_doc_dt` / `smpcl_exg_rate_dt` dates |

Column prefixes do not match the class names — that is deliberate (D15) and the registry in
`schema.md` §0 is the map. Say it once in each model's docblock.

`$incrementing = false` on all four: the sys ids come from `SysIdHelper` against `HM_MST_SEQUENCES`
(`schema.md` §7.1), not from the driver. `$keyType = 'int'` stays — `SysIdHelper` returns a string and
the repository casts it, because the columns are `NUMBER`.

Relationships: `SalesCtlApprRequest hasMany SalesCtlApprLine` (`scal_scar_sys_id`) and
`hasMany SalesMinPrice` (`smp_scar_sys_id`); `SalesMinPriceCheckLog belongsTo SalesCtlApprRequest`
(`smpcl_scar_sys_id`) and `belongsTo SalesMinPrice` (`smpcl_smp_sys_id`), both nullable.

**No relationship crosses into MGTDAT.** `SCAR_SOH_SYS_ID`, `SCAL_SOI_SYS_ID` and `SMP_SCOPE_VALUE`
point at Orion rows on a different connection; Eloquent cannot join across connections, and a
relationship that looks like it works until it is eager-loaded is worse than none. Fetch that data
through `SalesOrderLookupRepository` and compose it in the service.

**Date bindings are `Y-m-d H:i:s`, always, and month/day matching is exact equality — never
`whereDate`.** `NLS_DATE_FORMAT` here is `YYYY-MM-DD HH24:MI:SS`, so a bare `Y-m-d` raises ORA-01861,
and `whereDate` compiles to `TRUNC` on Oracle but a string `strftime` on SQLite — they disagree at
exactly the boundaries that matter. This bit CI Project; it will bite here too, on
`SMP_VALID_FROM/TO`.

---

## 3. Repositories

Ordinary Eloquent repositories, `Searchable`-based filtering, pagination always. Note which
connection each one is on — this domain is the first in the module to straddle two:

| Repository | Connection |
|---|---|
| min price, approval request, approval line, price check log | `oracle_mgthris` |
| SO lookup (`OT_SO_HEAD`, `OT_SO_ITEM`, `OM_ITEM`, `IM_VS_STATIC_VALUE`) | `oracle_mgtdat` |
| the gateway (`PKG_MGT_PRICE_CTRL`) | `oracle_mgtdat` |

Three are worth a note.

**`EloquentPriceCheckLogRepository`** — read-only, and every report method takes an explicit date
range. The log grows one row per line per approval attempt; an unbounded query on it will eventually
be the slowest page in the app. `SALES_MIN_PRICE_CHECK_LOG_NX02 (SMPCL_CR_DT, SMPCL_RESULT)` is there
for these queries — order the `where` clauses to use it.

**`EloquentSalesOrderLookupRepository`** — reads `OT_SO_HEAD`, `OT_SO_ITEM`, `OM_ITEM`,
`OM_CUSTOMER` and `IM_VS_STATIC_VALUE` on `oracle_mgtdat`. Read-only; this app never writes an Orion
sales table. It also owns `inScopeTxnCodes()` (`schema.md` §8). Its line query applies the same skip
rules as the package's `c_item` cursor — short-closed, FOC, zero rate — because a preview that lists
lines the trigger will not check is a preview that teaches people to distrust the page. That query is
built by `checkableLinesQuery()` and run by `checkableLines()`, so the skip rules can be asserted
without an Oracle database.

Item names come from `OM_ITEM.ITEM_NAME` — **there is no `ITEM_DESC`**; the confirmed column list is
`verification.md` §1 item 8. The line query deliberately reads no description column from
`OT_SO_ITEM`: one confirmed source for item names beats two, one of which was a guess.

**`OraclePriceControlGateway`** — §4.

---

## 4. The Oracle package gateway

> The rule the whole design hangs on: **Laravel does not know how to compute a minimum price.**
> It knows how to ask.

The gateway is on **`oracle_mgtdat`** — the package did not move, only the tables it reads. So the
preview path spans two connections: the SO lines and the verdicts come from MGTDAT, the exception
rows go to MGTHRIS. That is fine because **the app never writes MGTDAT**, so no transaction ever needs
to span both. Keep it that way: a `DB::transaction()` on one connection does not cover the other, and
a design that needed it to would be a design with a hole in it.

`PriceControlGatewayInterface`:

```php
public function usdDivisor(string $currencyCode, CarbonInterface $docDate): FxDivisorData;
public function minPrice(string $itemCode, ?string $grade1, ?string $grade2,
                         string $uomCode, CarbonInterface $docDate): ?MinPriceLookupData;
```

Two methods, both read-only, both mapping one-to-one onto `P_GET_USD_DIVISOR` and `P_GET_MIN_PRICE`.

> PRD §4.7 calls these `F_GET_MIN_PRICE` and `F_GET_USD_DIVISOR`; the package declares them as
> procedures with OUT parameters, `P_GET_*`. The SQL is the truth — the PRD line is prose. Worth
> knowing before someone spends an hour looking for a function that was never written.
`P_VALIDATE_SO` is deliberately **not** on the interface: it writes to `SALES_MIN_PRICE_CHECK_LOG` and it
raises, which is right for a trigger and wrong for a page (D4).

### 4.1 Calling a PL/SQL procedure with OUT parameters

There is no precedent for this in the repo — every other Oracle call here is a query. `yajra/laravel-oci8`
supports it through `Oci8Connection::executeProcedure()`, which binds every parameter with
`bindParam`, so an OUT parameter comes back if you pass it by reference with the right PDO type:

```php
$divisor = null; $rateDt = null; $source = null;

DB::connection('oracle_mgtdat')->executeProcedure('MGTDAT.PKG_MGT_PRICE_CTRL.P_GET_USD_DIVISOR', [
    'p_curr_code' => $currencyCode,
    'p_txn_dt'    => $docDate->format('Y-m-d H:i:s'),
    'o_divisor'   => ['value' => &$divisor, 'type' => PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 'length' => 40],
    'o_rate_dt'   => ['value' => &$rateDt,  'type' => PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 'length' => 30],
    'o_source'    => ['value' => &$source,  'type' => PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 'length' => 10],
]);
```

Three things that will cost an afternoon if they are not written down:

1. **The `&` is load-bearing.** `'value' => $divisor` binds a copy and returns nothing. The reference
   has to survive into the array that `addBindingsToStatement` walks.
2. **`length` is required on every OUT parameter.** The default is `-1`, and OCI cannot size an output
   buffer from that. Numbers come back as strings; cast in PHP.
3. **The package raises.** `P_GET_USD_DIVISOR` calls `RAISE_APPLICATION` when there is no rate, when
   the currency is unsupported, and when the divisor fails its 1 000–100 000 sanity check. The gateway
   catches `QueryException`, reads the ORA code, and throws a typed
   `PriceControlUnavailableException` carrying a message the page can show. It must **not** swallow
   it and return null — a preview that silently reports "no floor" when the FX table is empty is the
   exact failure mode PRD §4.3 wrote a sanity check to avoid.

### 4.2 Cost, and when to revisit

One `usdDivisor` call per document plus one `minPrice` call per line: a 30-line SO is 31 PL/SQL round
trips on a preview. That is fine at the volumes described and it needs no change to Indra's package,
which is why it is the default.

If it turns out to hurt, the fix is **not** to cache resolution in PHP. It is to ask Indra for a
read-only pipelined function — `F_PREVIEW_SO(p_soh_sys_id)` returning the same per-line verdicts as
`P_VALIDATE_SO` computes, without the `INSERT` and without the `RAISE`. One call, same code path, no
second implementation. Raise it as a change to `minimum-price-control.sql` if and when the numbers
justify it; do not build it speculatively.

### 4.3 Tests

CI runs on SQLite, which has no packages. Bind `PriceControlGatewayInterface` to a
`FakePriceControlGateway` in the test environment that returns canned `FxDivisorData` /
`MinPriceLookupData` from an array the test sets up.

The fake returns **fixtures, not arithmetic**. It must never grow a copy of the resolution ranking or
the FX cascade — the moment it does, the tests start proving that PHP agrees with PHP. Everything the
fake cannot cover (the real ranking, the real cascade, the trigger) is covered by the SQL smoke tests
in `minimum-price-control.sql` section G, run by hand against the Oracle test schema and recorded in
`verification.md` §2.

---

## 5. Livewire pages

| Component | Route | Who | What it does |
|---|---|---|---|
| `MinPriceManager` | `master/min-price` | Finance | List, filter, create/edit drafts, supersede an approved rule, Excel import/export, submit a batch for approval |
| `MinPriceApprovalManager` | `master/min-price-approval` | Finance approver | The `PRICELIST` requests: print the form, upload the signed scan, approve or reject |
| `PriceCheckPreview` | `transaction/price-check` | Marketing, Finance | Enter an SO number → the lines, their rate, the floor, the gap, and the verdict per line |
| `PriceExceptionRequest` | `transaction/price-exception` | Marketing | The `MINPRICE` requests: pick an SO, tick the under-floor lines, give a reason, print |
| `PriceExceptionApproval` | `transaction/price-exception-approval` | Finance approver | Upload the signed scan, approve or reject |
| three report pages | `transaction/…-report` | Finance, management | `spec.md` §7 |

House rules that apply to all of them: service injection through `boot()`, `WithPagination`,
`WithFileUploads` where files are involved, toasts through `$this->dispatch('butter-success', …)`,
`wire:key` in every loop, UI-module components before Flux before custom, and **no `wire:navigate`,
no `navigate: true`** anywhere.

`PriceCheckPreview` and `PriceExceptionRequest` share the SO-lines table and the gateway calls behind
it. Put that in `PricePreviewService` and a `_so-lines.blade.php` partial rather than duplicating —
they are the same question asked for two different reasons, and the day they disagree is the day
Marketing stops believing the preview.

---

## 6. Services

### `ApprovalRequestService` — the shared state machine

Owns `DRAFT → PRINTED → APPROVED | REJECTED | CANCELLED | VOID` for **all** control types, because
that flow is the one genuinely shared thing (PRD §2). Transitions, guards and side effects are
specified in `spec.md` §2. It knows nothing about prices.

### `PriceExceptionService` / `MinPriceService` — the type-specific halves

They build and consume requests; they do not re-implement transitions. `PriceExceptionService`
additionally snapshots `SCAL_APPROVED_RATE` / `SCAL_APPROVED_QTY_BU` from the SO at submit time — the
binding described in `schema.md` §3.

**Resist the pull toward a generic rules engine** (PRD §2, emphatic). Overdue and credit limit will
arrive later and their checks live in Oracle triggers, not here. What they will reuse is
`ApprovalRequestService`, `ApprovalDocumentService` and the request pages' shape — nothing else.

### `ApprovalDocumentService`

Print, attach, verify. Print renders `resources/views/pdf/sales-control/*` through
`barryvdh/laravel-dompdf`, streams it, and in the same transaction bumps `SCAR_PRINT_COUNT`, stamps
`SCAR_PRINT_DT/UID` and moves `DRAFT → PRINTED`. Attachment handling is §7.

---

## 7. Attachments

Disk **`minio_private`**, the same one the Finance exports already use. Never `public`: these are
signed board documents.

```
sales-control/{ctrl_type}/{YYYY}/{SCAR_REQ_NO}-r{revision}.{ext}
```

On upload: validate (`pdf,jpg,jpeg,png`, max 10 MB), compute `hash_file('sha256', …)` **before**
storing, write path and hash to `SCAR_ATTACH_PATH` / `SCAR_ATTACH_HASH`. Download is a
`temporaryUrl()`, same as the export jobs.

The hash exists so a swapped file is detectable. Give the report a "verify" action that re-hashes and
flags a mismatch; a hash that is written and never checked is decoration.

Oracle's `C03` already refuses `APPROVED` with a null path (`schema.md` §2.1). Validate in PHP too,
for the message.

---

## 8. Routes

```php
// Modules/Finance/routes/web.php, inside the existing dashboard/module-finance group

Route::prefix('master')->name('master.')->group(function () {
    Route::get('min-price', MinPriceManager::class)
        ->name('min-price')->middleware(['can:finance-min_price-view']);
    Route::get('min-price-approval', MinPriceApprovalManager::class)
        ->name('min-price-approval')->middleware(['can:finance-min_price-approve']);
});

Route::prefix('transaction')->name('transaction.')->group(function () {
    Route::get('price-check', PriceCheckPreview::class)
        ->name('price-check')->middleware(['can:finance-price_exception-view']);
    Route::get('price-exception', PriceExceptionRequest::class)
        ->name('price-exception')->middleware(['can:finance-price_exception-create']);
    Route::get('price-exception-approval', PriceExceptionApproval::class)
        ->name('price-exception-approval')->middleware(['can:finance-price_exception-approve']);
    Route::get('price-exception-report', PriceExceptionReport::class)
        ->name('price-exception-report')->middleware(['can:finance-price_exception-report']);
    Route::get('price-rejection-report', PriceRejectionReport::class)
        ->name('price-rejection-report')->middleware(['can:finance-price_exception-report']);
});
```

The existing `master` group is wrapped in `role_or_permission:Super Admin|manage-master-finance`; the
two min-price routes need their own `can:` on top, so declare them in a sibling group rather than
inside it.

Breadcrumbs for every route in `Modules/Finance/routes/breadcrumbs.php` — required anyway, and
`PageTitleHelper` derives the browser tab title from the last crumb, so a missing breadcrumb is also
a page titled after its route name.

---

## 9. Permissions

`SalesControlRolesAndPermissionsSeeder`, modelled on `CiProjectRolesAndPermissionsSeeder` — the app's
own `Modules\Auth\Models\RolePermission\{Role,Permission}` (they pin the connection to
`oracle_mgthris`), and `forgetCachedPermissions()` either side.

```
finance-min_price-view
finance-min_price-draft         # create and edit DRAFT rules
finance-min_price-submit        # raise the PRICELIST request
finance-min_price-print
finance-min_price-approve       # upload + approve/reject  -> a role, never a person (D9)
finance-min_price-void

finance-price_exception-view    # includes the price-check preview
finance-price_exception-create
finance-price_exception-print
finance-price_exception-approve
finance-price_exception-report
```

Roles: `Min Price - Maker` (view, draft, submit, print), `Min Price - Approver` (view, print, approve,
void), `Sales Exception - Requester` (exception view, create, print), `Sales Exception - Approver`
(exception view, print, approve, report). `Super Admin` gets all of them.

Print is separate from draft on purpose: printing is what freezes the content and starts the paper
trail, and it is worth being able to say who did it.

---

## 10. Config and logging

`Modules/Finance/config/config.php`:

```php
'sales_control' => [
    // Prefix per control type; the year and the 4-digit counter are appended.
    'req_no_prefix' => ['PRICELIST' => 'MPL', 'MINPRICE' => 'MPO', 'OVERDUE' => 'MOV', 'CRLIMIT' => 'MCL'],
    'attachment' => ['disk' => 'minio_private', 'max_kb' => 10240, 'mimes' => ['pdf', 'jpg', 'jpeg', 'png']],
    // Read from IM_VS_STATIC_VALUE at runtime; this is the fallback if that lookup is empty.
    'fallback_txn_codes' => ['ESC', 'LSC', 'STA'],
],
```

`config/logging.php` gains a `finance_price_control` channel, alongside `finance_report`. Log every
gateway failure with the document, the currency and the date — those are the calls that will be
argued about.

Nothing reads `env()` outside these config files.
