# Price Exception across several contracts — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Marketing picks a customer, ticks below-floor lines across that customer's not-yet-approved contracts, and saves them as one price exception request.

**Architecture:** Each request line records its own contract (four new `SCAL_*` columns). The single-contract preview (`PricePreviewService::preview()`) is run once per open contract. `PriceExceptionService` gets one multi-preview path that the single-contract path also goes through. Screens and exports read the contract from the line, and fall back to the header when every line shares one contract.

**Tech Stack:** Laravel 12, Livewire 3, Pest 4 (SQLite in CI), Oracle via yajra/oci8.

**Spec:** `.docs-me/superpowers/sales-exception-multi-contract/2026-11-21-sales-exception-multi-contract-design.md`

## Global Constraints

- No typed price or quantity anywhere. Lines are built only through `PriceExceptionLineData::fromPreviewLine()` (D8).
- `ApprovalRequestService` stays the only writer of `scar_status`.
- Open contract = in-scope txn code (`inScopeTxnCodes()`) AND `NVL(SOH_APPR_STATUS, 0) <> 3`. No date cutoff, no `soh_clo_status` filter.
- Open-contract cap per customer: 100, with a `ponytail:` comment and a notice on screen when it is hit.
- Request in progress = status `DRAFT`, `PRINTED` or `SIGNED`. Its lines cannot be ticked again; `CANCELLED`, `VOID` and `REJECTED` free them.
- Multi-contract header: `scar_soh_sys_id`, `scar_txn_code` and `scar_doc_no` are null; `scar_doc_dt` = the earliest contract date; one `scar_cust_code` and one `scar_curr_code`.
- Migrations must run on SQLite, follow the `DISABLE_MIGRATIONS` pattern, and leave `down()` empty (the suite's convention).
- No `wire:navigate`. Icons are Font Awesome 5. Component priority: UI module first (`x-ui::form.combobox`), then Flux.
- Tests: `php artisan test --parallel --processes=4 <path>`.
- Commits follow Conventional Commits, scope `finance`. Never push.

## Review Focus

1. **The same SOI ticked on two contracts' previews after an STA/ESC parent link.** SOI ids are unique, but an STA line can inherit cover from its ESC parent. Expected: a line the parent already covers shows as covered and is refused. Pinned in Task 2.
2. **A sales order edited between page load and save** (rate lowered, a line removed). Expected: the save re-checks the contracts that have ticked lines and refuses lines that are no longer offerable. Pinned in Task 4.
3. **A customer with no open contracts, or none below the floor.** Expected: a plain message ("No open contracts" / "n contracts checked, none below the floor"), with no empty table and no Save button. Pinned in Task 5.
4. **A contract whose exchange rate can't be fetched, in the middle of the customer's list.** Expected: that group shows its notice with its lines not tickable, and the other contracts still work. Pinned in Task 5.
5. **Revising a multi-contract request.** Expected: the new revision keeps each line's contract and the null header fields. Pinned in Task 1.

---

### Task 1: Per-line contract columns

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_21_100000_add_contract_to_sales_ctl_appr_line.php`
- Modify: `Modules/Finance/app/Models/MgtHris/Transaction/SalesControl/SalesCtlApprLine.php` (fillable, casts)
- Modify: `Modules/Finance/app/Data/Transaction/SalesControl/PriceExceptionLineData.php`
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/ApprovalRequestService.php` (`revise()` line copy, around line 450)
- Test: `tests/Feature/Finance/SalesControl/MultiContractLineTest.php`

**Interfaces:**
- Produces: columns `scal_soh_sys_id` (integer), `scal_txn_code` (string 10), `scal_doc_no` (integer), `scal_doc_dt` (datetime), all nullable, plus index `sales_ctl_appr_line_nx_soh` on `scal_soh_sys_id`.
- Produces: `PriceExceptionLineData` gains `public ?int $soh_sys_id = null, public ?string $txn_code = null, public ?int $doc_no = null, public ?string $doc_dt = null` (added after `item_desc`). `fromPreviewLine(PricePreviewLineData $line, ?object $header = null): self` reads `soh_sys_id`, `soh_txn_code`, `soh_no` and `soh_dt` from `$header`. `toRow()` writes the four `scal_*` keys.

- [ ] **Step 1: Write the failing tests**

```php
it('carries the contract from the preview header onto the line row', function () {
    $header = (object) ['soh_sys_id' => 55, 'soh_txn_code' => 'LSC', 'soh_no' => 2026003755, 'soh_dt' => '2026-11-12 00:00:00'];
    $row = PriceExceptionLineData::fromPreviewLine(underLine(1), $header)->toRow();
    expect($row)->toMatchArray(['scal_soh_sys_id' => 55, 'scal_txn_code' => 'LSC', 'scal_doc_no' => 2026003755, 'scal_doc_dt' => '2026-11-12 00:00:00']);
});

it('backfills existing lines from their request header', function () {
    // create request (salesControlRequest) + line (salesControlLine) with the new columns null,
    // re-run the migration's up() (require the file, ->up()),
    // expect line scal_soh_sys_id = 9001, scal_txn_code = 'ESC', scal_doc_no = 12345
});

it('keeps each line\'s contract on a revision', function () {
    // PRINTED request with scar_soh_sys_id/txn/doc_no null, two lines with contracts 55 and 66;
    // actingAs(salesControlUser()); app(ApprovalRequestService::class)->revise(1);
    // expect the new revision's lines to have scal_soh_sys_id [55, 66] and its header scar_soh_sys_id null
});
```

`underLine(int $id)` is a small file-local helper building a `PricePreviewLineData` with `verdict: VERDICT_UNDER` (copy the shape of `belowLine()` in `ApprovedBelowMinPriceTest.php`; give it a different name, since Pest helpers share one global namespace).

- [ ] **Step 2: Run, expect FAIL** (unknown named parameter / missing column)

Run: `php artisan test --parallel --processes=4 tests/Feature/Finance/SalesControl/MultiContractLineTest.php`

- [ ] **Step 3: Write the migration**

Copy the shape of `2026_11_18_100000_add_item_desc_to_sales_ctl_appr_line.php` (migration key `sales_ctl_appr_line_contract`, guarded by `hasColumn('scal_soh_sys_id')`). Unlike that one, the backfill runs on SQLite too: four `SET col = (SELECT … FROM sales_ctl_appr_request r WHERE r.scar_sys_id = scal_scar_sys_id)` clauses in a single `UPDATE … WHERE scal_soh_sys_id IS NULL`. Both Oracle and SQLite accept this form. Keep the backfill in its own method so the test can call `up()` again after the columns exist: guard only the `addColumn` part on `hasColumn`, not the backfill.

- [ ] **Step 4: Update the model, DTO and `revise()` copy** to match the Interfaces block. In `revise()` the line map adds the four `scal_*` keys from `$line`.

- [ ] **Step 5: Run, expect PASS.** Also run the whole `tests/Feature/Finance/SalesControl` folder: existing callers of `fromPreviewLine($line)` must still pass.

- [ ] **Step 6: Commit** `feat(finance): record which contract each price exception line came from`

---

### Task 2: Lines on an unfinished request are not offered again

**Files:**
- Modify: `Modules/Finance/app/Data/Transaction/SalesControl/PricePreviewLineData.php`
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/PricePreviewService.php` (`existingCover()`, `applyCover()`)
- Modify: `Modules/Finance/app/Enums/Transaction/SalesControl/ApprovalRequestStatusEnum.php`
- Test: `tests/Feature/Finance/SalesControl/PendingCoverTest.php`

**Interfaces:**
- Produces: `ApprovalRequestStatusEnum::inProgress(): array` returns `[Draft, Printed, Signed]`.
- Produces: `PricePreviewLineData` gains `public ?string $pending_req_no = null` and `public ?string $pending_status = null`, plus `withPending(string $reqNo, string $statusLabel): self` and `isPending(): bool`. `isOfferableForException()` becomes `UNDER && ! covered && ! pending`. `verdictLabel()` returns `"On request {$reqNo} ({$status})"` when pending; `verdictBadgeVariant()` returns `'warning'`.
- `withCover()` and `withPending()` must carry every field (including the other's). Add a private `copyWith(array $overrides)` if that is shorter than two full constructor calls.

- [ ] **Step 1: Write the failing tests** against `PricePreviewService::preview()`, with `SalesOrderLookupRepositoryInterface` and `PriceControlGatewayInterface` mocked (the gateway returns a divisor of 1 and a min-price rule with a floor of 2.5; one line at rate 2.1):

```php
it('marks an under-floor line already on a printed request as pending and not offerable', ...);
// salesControlRequest(1) PRINTED + salesControlLine(1, 1, ['scal_soi_sys_id' => 7001])
// expect line->isPending() true, offerableLines() empty, verdictLabel() 'On request MPO-2026-0001 (Printed)'

it('offers the line again once that request is cancelled', ...);
// same, scar_status CANCELLED -> offerable

it('prefers a matching approved cover over a pending one', ...);
// approved request 1 + draft request 2 on the same SOI -> isCovered() true, label 'Covered by …'

it('still covers an STA line through its parent ESC line', ...);
// row soi_soi_sys_id = 7001, approved line on 7001 -> covered via parent (guards Review Focus 1)
```

- [ ] **Step 2: Run, expect FAIL**

- [ ] **Step 3: Implement.** `existingCover()` returns two maps from the same `forSoLines()` result: approved (as today) and in-progress (status in `inProgress()`). For in-progress, keep the newest request per SOI. `applyCover()` checks approved-and-still-matching first, then direct pending, then parent pending, using the label from the status enum's `label()`. A pending line does not need to match the current rate: any open request for that SOI blocks it.

- [ ] **Step 4: Run, expect PASS**, plus the full SalesControl folder.

- [ ] **Step 5: Commit** `feat(finance): stop offering lines already on an unfinished price exception`

---

### Task 3: Customers and open contracts lookup

**Files:**
- Modify: `Modules/Finance/app/Interfaces/Transaction/SalesControl/SalesOrderLookupRepositoryInterface.php`
- Modify: `Modules/Finance/app/Repositories/Transaction/SalesControl/EloquentSalesOrderLookupRepository.php`
- Test: `tests/Feature/Finance/SalesControl/OpenContractLookupTest.php`

**Interfaces:**
- Produces: `customersWithOpenContractsQuery(array $txnCodes): \Illuminate\Database\Query\Builder` and `customersWithOpenContracts(array $txnCodes): Collection`. Each row has `cust_code` and `cust_name`: `OT_SO_HEAD` joined to `OM_CUSTOMER` on `soh_cust_code = cust_code`, distinct, ordered by name.
- Produces: `openContractsQuery(string $custCode, array $txnCodes): \Illuminate\Database\Query\Builder` and `openContracts(string $custCode, array $txnCodes, int $limit = 100): Collection` of header rows, the same columns as `headerQuery()`, ordered by `soh_dt, soh_sys_id`.
- Both filter `soh_txn_code IN $txnCodes` and `NVL(soh_appr_status, 0) <> 3`. The "Query" methods exist so the SQL can be asserted, as `checkableLinesQuery()` is.

- [ ] **Step 1: Write the failing tests** in the style of `LineQtyAndRateFormatTest.php:16`: build each query, `->toSql()`, and assert it contains `nvl(soh_appr_status, 0) <> 3` (case-insensitive) and `soh_txn_code" in` (or the grammar's equivalent), and that the open-contracts query is bound to the customer code. Also assert that neither query mentions `soh_clo_status` or `soh_dt >=` (no hidden cutoff).

- [ ] **Step 2: Run, expect FAIL** · **Step 3: Implement** · **Step 4: Run, expect PASS**

- [ ] **Step 5: Commit** `feat(finance): look up a customer's open contracts for price exceptions`

---

### Task 4: One request from several contracts' previews

**Files:**
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/PriceExceptionService.php`
- Test: `tests/Feature/Finance/SalesControl/MultiContractRequestTest.php`

**Interfaces:**
- Consumes: Task 1's `fromPreviewLine($line, $header)`; Task 2's `offerableLines()`.
- Produces: `requestMany(array $previews, array $soiSysIds, string $reason): SalesCtlApprRequest`, where `$previews` is `array<int, PricePreviewData>`. `request(PricePreviewData $preview, array $soiSysIds, string $reason)` keeps its signature and becomes `return $this->requestMany([$preview], $soiSysIds, $reason);`.

Rules, in this order, each a `ValidationException` (key `lines` unless noted). Keep today's messages where they exist:
1. No preview that is checked and has a document → today's "could not be checked" message.
2. Empty selection → "Tick the lines you need an exception for."
3. Reason under 10 characters (key `reason`) → today's message.
4. Any ticked SOI not in some preview's `offerableLines()` → today's "Line {id} is not below the floor, or is already covered…" message.
5. Ticked lines span more than one `soh_cust_code` → "All lines on one request must be for the same customer."
6. Ticked lines span more than one `soh_curr_code` → "These contracts are in different currencies ({list}). Raise one request per currency."

Header: the contract fields come from the single contract when all ticked lines share one, otherwise null. `scar_doc_dt` = the minimum `soh_dt` of the contracts that have ticked lines. Customer and currency are the shared values.

- [ ] **Step 1: Write the failing tests** (`actingAs(salesControlUser())`, previews built with `PricePreviewData::checked()` and the `underLine()` helper from Task 1):

```php
it('saves lines from two contracts as one request with per-line contracts', ...);
// contracts 55 (2026-11-15) and 66 (2026-11-12), same customer/currency, tick one line each
// expect one request, scar_soh_sys_id null, scar_doc_no null, scar_doc_dt '2026-11-12', 2 lines with scal_soh_sys_id [55, 66]

it('fills the header exactly as before when every ticked line is from one contract', ...);
// via request() and via requestMany([$a, $b]) ticking only $a lines: scar_soh_sys_id 55, scar_doc_no set

it('refuses lines from two currencies', ...);   // message contains 'different currencies'
it('refuses lines from two customers', ...);
it('refuses a line that is no longer offerable in its own contract', ...); // pending/covered line id -> 'is not below the floor'
```

- [ ] **Step 2: Run, expect FAIL** · **Step 3: Implement** · **Step 4: Run, expect PASS**, plus the full SalesControl folder.

- [ ] **Step 5: Commit** `feat(finance): raise one price exception across several contracts`

---

### Task 5: "By customer" on the request page

**Files:**
- Modify: `Modules/Finance/app/Livewire/Transaction/SalesControl/PriceExceptionRequest.php`
- Modify: `Modules/Finance/resources/views/livewire/transaction/sales-control/price-exception-request.blade.php`
- Create: `Modules/Finance/resources/views/livewire/transaction/sales-control/partials/_customer-lines.blade.php`
- Test: `tests/Feature/Finance/SalesControl/PriceExceptionByCustomerTest.php`

**Interfaces:**
- Consumes: Task 3's lookups; `PricePreviewService::preview(object $header)` and `previewBySysId(int)`; Task 4's `requestMany()`.
- Component state: `#[Url(as: 'mode', except: 'contract')] public string $mode = 'contract'` (`'contract'|'customer'`) and `public string $custCode = ''`.
- `#[Computed] customers(): array` returns combobox options `[['value' => code, 'text' => name, 'secondary' => code], …]`.
- `#[Computed(persist: true)] customerPreviews(): array<int, PricePreviewData>` is keyed by `soh_sys_id`. It runs `preview()` for each of `openContracts($custCode, inScopeTxnCodes(), 101)` and keeps the first 100. `customerCapped(): bool` is true when 101 came back. Persisted so ticking and paging don't re-run hundreds of Oracle calls. Clear it (`unset($this->customerPreviews)`) and `selectedLines` on `updatedCustCode`, on `updatedMode` and after a successful save.
- `selectAllUnder()` works on whichever mode is active.
- `save()` in customer mode: find the contracts that own the ticked SOIs (from `customerPreviews`), re-run `previewBySysId()` for just those contracts, then call `requestMany()`. Errors map to `selectedLines`/`reason` as today.

View: a radio/segmented switch "By contract / By customer" at the top of the Sales Order card. In customer mode, show `x-ui::form.combobox wire:model.live="custCode"` and a loading state on the computed. `_customer-lines` renders one group per contract that has at least one under line: a header row `TXN-NO · d M Y · CURR · n below floor` and, for a contract that wasn't checked, its notice. The rows reuse the column markup of `_so-lines` (extract a shared `_so-line-row` partial if that is shorter than duplicating it). Above the table: "n open contracts checked, m have lines below the floor." With no open contracts: "This customer has no open contracts." When capped: "Showing the oldest 100 open contracts." The Reason/Save card shows when any preview has offerable lines.

- [ ] **Step 1: Write the failing tests** (Livewire; mock `SalesOrderLookupRepositoryInterface` and `PricePreviewService`, bound with `app()->instance`, as `ApprovedBelowMinPriceTest` does; the user has `finance-sales_control_price_exception-create`):

```php
it('lists below-floor lines grouped by contract for a picked customer', ...);
// set('mode','customer')->set('custCode','C1') -> assertSee('LSC-1') & assertSee('LSC-2'), contract with only PASS lines not shown

it('saves ticked lines from two contracts as one request', ...);
// set selectedLines [1, 2], reason, call('save') -> one SalesCtlApprRequest, 2 lines; previewBySysId called only for the two ticked contracts

it('says so when the customer has no open contracts', ...);       // assertSee('no open contracts'), assertDontSee('Save Draft Request')
it('keeps other contracts usable when one could not be checked', ...); // notChecked preview -> assertSee(notice), other group's line still offerable
it('refuses a line changed since the page loaded', ...);         // re-run previewBySysId returns the line as PASS -> assertHasErrors('selectedLines')
```

- [ ] **Step 2: Run, expect FAIL** · **Step 3: Implement** · **Step 4: Run, expect PASS**, plus the full SalesControl folder.

- [ ] **Step 5: Check it in the browser** (`composer run dev`, log in with the create permission): pick a real customer on the dev DB (192.168.0.7, never prod), tick lines across two contracts, save, and confirm one draft request appears in the list.

- [ ] **Step 6: Commit** `feat(finance): pick a customer to raise one price exception across contracts`

---

### Task 6: Show each line's contract everywhere a request is read

**Files:**
- Modify: `Modules/Finance/app/Models/MgtHris/Transaction/SalesControl/SalesCtlApprRequest.php`
- Modify: `Modules/Finance/resources/views/pdf/sales-control/price-exception-form.blade.php` (header line 53, lines table)
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceEmailApprovalHandler.php:189`
- Modify: `Modules/Finance/resources/views/sales-control/email-approval/summary.blade.php:33`, `Modules/Finance/resources/views/pdf/sales-control/email-approval-record.blade.php:60`
- Modify: `price-exception-request.blade.php:129`, `price-exception-approval.blade.php:89`, `partials/_exception-lines.blade.php`, `reports/sales-control/price-exception-report.blade.php:51`
- Modify: `Modules/Finance/app/Exports/Reports/SalesControl/PriceExceptionExport.php`
- Test: `tests/Feature/Finance/SalesControl/MultiContractDisplayTest.php`

**Interfaces:**
- Consumes: Task 1's columns.
- Produces on `SalesCtlApprRequest`:
  - `isMultiContract(): bool`: `scar_soh_sys_id === null` and the lines carry more than one distinct `scal_soh_sys_id`.
  - `contractLabel(int $max = 3): string`: for a single contract, `"{txn} {doc_no}"` as today; otherwise `"{n} contracts: LSC 1, LSC 2, LSC 3 +{k} more"`. It reads the loaded `lines` relation; the callers eager-load it.
- Produces on `SalesCtlApprLine`: `contractRef(): ?string`, returning `"{scal_txn_code} {scal_doc_no}"` or null.
- `$searchable` on the request adds `'lines.scal_doc_no'`.

Edits:
- **PDF:** the header shows `contractLabel()`. When `isMultiContract()`, the lines table gets a Contract column after `#`, and lines are sorted by contract.
- **Email handler, summary and record:** "Document" uses `contractLabel()`.
- **List, approval and report views:** the Sales Order column uses `contractLabel()`.
- **`_exception-lines`:** show `contractRef()` per line when multi.
- **Export:** `txn_code`/`doc_no`/`doc_date` read from the line first (`$line->scal_txn_code ?? $request->scar_txn_code`, and so on). The headings stay the same.

- [ ] **Step 1: Write the failing tests:**

```php
it('labels a single-contract request as before', ...);        // 'ESC 12345'
it('labels a multi-contract request with a count and the first three', ...); // 5 lines on 5 contracts -> '5 contracts: LSC 1, LSC 2, LSC 3 +2 more'
it('finds a multi-contract request by one of its contract numbers', ...);    // listQuery()->search('2026003790') returns it
it('exports each line with its own contract', ...);           // PriceExceptionExport row doc_no per line
it('prints a contract column on a multi-contract form', ...); // render the PDF view with view() and assertSee('Contract') + both refs
```

- [ ] **Step 2: Run, expect FAIL** · **Step 3: Implement** · **Step 4: Run, expect PASS**, then the whole suite: `php artisan test --parallel --processes=4`.

- [ ] **Step 5:** `vendor/bin/pint --dirty`

- [ ] **Step 6: Commit** `feat(finance): show each contract on multi-contract price exceptions`
