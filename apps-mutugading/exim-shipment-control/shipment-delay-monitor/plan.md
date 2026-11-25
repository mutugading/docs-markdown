# Export Shipment Delay Monitor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Track each Export SI's voyage (latest ETA, ATA, transhipment leg, delay reason) and show a computed delay status on a new monitor page with a queued Excel export.

**Architecture:** Seven nullable columns on `ship_export_si`; status/delay/position computed by a small readonly class from the model's dates (never stored); the same status rule expressed as a query scope for filtering. Tracking is edited from a box on the SI detail page; the monitor is a new Livewire list page whose export extends `ShipReportExport`.

**Tech Stack:** Laravel 12, Livewire 3, Flux/UI module components, Maatwebsite Excel, Pest 4 (SQLite).

**Spec:** `.docs-me/superpowers/shipment-delay-monitor/design.md`

## Global Constraints

- Late = delay ≥ 1 day vs original ETA (`ses_eta`). No tolerance.
- Delay reason: free text, max 500 chars.
- Tracking editable in every SI status except `CANCELLED`; gate `finance-shipment-si-edit`.
- Monitor page and export gate: `finance-shipment-si-view`. Cancelled SIs never listed.
- Excel export runs as a queued job (extends `Modules\Finance\Jobs\Reports\Shipment\ShipReportExport`).
- Route names under `dashboard.module-finance.transaction.shipment.*`; no `wire:navigate`.
- Migrations: `oracle_mgthris`, SQLite-compatible, `DISABLE_MIGRATIONS` key, idempotent `hasColumn` guard, empty `down()` (pattern: `Modules/Finance/database/migrations/2026_11_23_100000_add_booking_fields_to_ship_export_si.php`).
- Tests: `php artisan test --parallel --processes=4 <path>`; `vendor/bin/pint --dirty` before each commit.
- "Today" is `Carbon::today()` (Asia/Jakarta); tests use `Carbon::setTestNow()`.

## Review Focus

1. **Not arrived, latest ETA in the past, nobody updated it** → `DELAYED`, delay counted to today. Test in Task 2.
2. **Latest ETA earlier than original** → `ON_SCHEDULE`, delay 0 (never negative). Test in Task 2.
3. **Status filter in SQL disagrees with PHP rule** (boundary: ETA == today, ATA == ETA) → shared fixture asserted both ways in Task 2.
4. **ATA before ATD / TS ATD before ATD typed by mistake** → validation error, nothing saved. Test in Task 3.
5. **SI in `CLOSED` (costs done, ship still sailing)** → tracking still saveable; `CANCELLED` refused. Test in Task 3.

---

### Task 1: Columns on `ship_export_si`

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_24_100000_add_tracking_fields_to_ship_export_si.php`
- Modify: `Modules/Finance/app/Models/MgtHris/Transaction/Shipment/ShipExportSi.php` (`$fillable`, `casts()`, `$logAttributes`, new relation)
- Test: `tests/Feature/Finance/Shipment/ShipExportSiTrackingTest.php`

**Interfaces:**
- Produces columns: `ses_eta_latest` date, `ses_ata` date, `ses_ts_port_code` string(10), `ses_ts_eta` date, `ses_ts_etd` date, `ses_ts_atd` date, `ses_delay_reason` string(500) — all nullable. Migration key `ship_export_si_tracking_fields`.
- Produces `ShipExportSi::transhipmentPort(): BelongsTo` (same target/keys as `loadingPort()`, FK `ses_ts_port_code`).
- Log labels: Latest ETA, ATA, Transhipment Port, Transhipment ETA, Transhipment ETD, Transhipment ATD, Delay Reason.

- [ ] **Step 1: Failing test** — `it('stores and casts the tracking fields')`: create an SI via `EximFixture`, update the 7 fields, refresh; assert `ses_ata` is `Carbon` and equals `'2026-11-20'`, `ses_delay_reason` round-trips, `transhipmentPort` resolves a seeded port.
- [ ] **Step 2: Run** `php artisan test --parallel --processes=4 tests/Feature/Finance/Shipment/ShipExportSiTrackingTest.php` → FAIL (no column).
- [ ] **Step 3: Implement** the migration (date columns via `$table->date()`; loop like the booking migration but with a type per column) and the model changes.
- [ ] **Step 4: Run** → PASS. Also run `ShipFrontOfficeTransactionSchemaTest.php` → PASS.
- [ ] **Step 5: Commit** `feat(finance): add tracking fields to export SI`

### Task 2: Delay status — enum, calculator, query scope

**Files:**
- Create: `Modules/Finance/app/Enums/Transaction/Shipment/ShipDelayStatusEnum.php`
- Create: `Modules/Finance/app/Services/Transaction/Shipment/ShipDelayTracker.php` (readonly value class)
- Modify: `ShipExportSi.php` — `tracking(): ShipDelayTracker` accessor method and `scopeDelayStatus(Builder $q, ShipDelayStatusEnum $s)`
- Test: `tests/Unit/Finance/ShipDelayTrackerTest.php` (pure), plus scope test in `ShipExportSiTrackingTest.php`

**Interfaces:**
- `enum ShipDelayStatusEnum: string { NO_ETA, ON_SCHEDULE, DELAYED, ARRIVED_ON_TIME, ARRIVED_LATE }` with `label()` ("No ETA", "On Schedule", "Delayed", "Arrived On Time", "Arrived Late"), `badgeVariant()` (zinc, green, red, green, amber), `static options(): array` (same shape as `ShipSiStatusEnum::options()`).
- `enum ShipPositionEnum` is NOT created — position is a string from `ShipDelayTracker::position(): string` returning one of `'Not sailed' | 'To transhipment port' | 'To destination' | 'Arrived'`.
- `final readonly class ShipDelayTracker { __construct(?CarbonInterface $etd, ?CarbonInterface $eta, ?CarbonInterface $etaLatest, ?CarbonInterface $atd, ?CarbonInterface $ata, ?string $tsPort, ?CarbonInterface $tsAtd, CarbonInterface $today); static fromSi(ShipExportSi $si): self; status(): ShipDelayStatusEnum; delayDays(): ?int; position(): string; plannedTransitDays(): ?int; actualTransitDays(): ?int }`
- `ShipExportSi::tracking(): ShipDelayTracker` (= `ShipDelayTracker::fromSi($this)` with today).

Rules (from spec):
```
effectiveEta = etaLatest ?? eta
if eta null → NO_ETA, delayDays null
if ata     → d = max(0, ata − eta);  d ≥ 1 ? ARRIVED_LATE : ARRIVED_ON_TIME
else       → ref = max(effectiveEta, today if today > effectiveEta);  d = max(0, ref − eta);  d ≥ 1 ? DELAYED : ON_SCHEDULE
position: ata → Arrived; tsAtd → To destination; atd && tsPort → To transhipment port; atd → To destination; else Not sailed
transit: planned = eta − etd; actual = ata − atd (null if either missing)
```
Scope SQL (date compares only, `COALESCE(ses_eta_latest, ses_eta)` as `eff`, `today` bound):
`NO_ETA`: `ses_eta IS NULL` · `ARRIVED_LATE`: `ata NOT NULL AND ata > ses_eta` · `ARRIVED_ON_TIME`: `ata NOT NULL AND ata <= ses_eta` · `DELAYED`: `ata NULL AND ses_eta NOT NULL AND (eff > ses_eta OR today > ses_eta)` · `ON_SCHEDULE`: `ata NULL AND ses_eta NOT NULL AND eff <= ses_eta AND today <= ses_eta`.
(Note: `DELAYED` via today needs `today > eff` *and* > eta; since `eff <= eta` in that branch, `today > ses_eta` is the exact condition.)

- [ ] **Step 1: Failing unit test** — dataset `it('classifies a shipment', fn ($dates, $status, $days) => …)` with today = 2026-11-24, eta = 2026-11-20 unless stated:
  - no eta → NO_ETA, null
  - ata 2026-11-21 → ARRIVED_LATE, 1
  - ata 2026-11-20 → ARRIVED_ON_TIME, 0
  - ata 2026-11-18 → ARRIVED_ON_TIME, 0
  - eta 2026-11-30, no latest → ON_SCHEDULE, 0
  - eta 2026-11-30, latest 2026-11-28 → ON_SCHEDULE, 0
  - eta 2026-11-30, latest 2026-12-03 → DELAYED, 3
  - eta 2026-11-20, latest null, today 11-24 → DELAYED, 4 *(Review Focus 1)*
  - eta 2026-11-24 == today → ON_SCHEDULE, 0
  plus position cases (none → Not sailed; atd+tsPort → To transhipment port; tsAtd → To destination; ata → Arrived) and transit (etd 11-01, eta 11-20 → 19).
- [ ] **Step 2: Run** `php artisan test --parallel --processes=4 tests/Unit/Finance/ShipDelayTrackerTest.php` → FAIL.
- [ ] **Step 3: Implement** enum + tracker + `tracking()`.
- [ ] **Step 4: Failing scope test** in `ShipExportSiTrackingTest.php` — `it('filters by delay status the same way the tracker classifies')`: seed one SI per dataset row above, for each `ShipDelayStatusEnum` case assert `ShipExportSi::delayStatus($case)->pluck('ses_sys_id')` equals the ids whose `tracking()->status() === $case` *(Review Focus 3)*.
- [ ] **Step 5: Implement** `scopeDelayStatus`; run both test files → PASS.
- [ ] **Step 6: Commit** `feat(finance): compute export shipment delay status`

### Task 3: Tracking box on the SI detail page

**Files:**
- Modify: `ShipExportSiService.php` — add `trackingRules(): array` and `saveTracking(ShipExportSi $si, array $state): ShipExportSi`
- Modify: `Modules/Finance/app/Livewire/Transaction/Shipment/ShipExportSiDetail.php` — `public array $tracking`, `saveTracking()`
- Modify: `Modules/Finance/resources/views/livewire/transaction/shipment/ship-export-si-detail.blade.php` — "Tracking" card
- Test: `tests/Feature/Finance/Shipment/ShipExportSiTrackingTest.php`

**Interfaces:**
- Consumes: Task 1 columns, Task 2 `tracking()` for the badge/position shown in the card header.
- `$tracking` keys: `eta_latest, ata, ts_port_code, ts_eta, ts_etd, ts_atd, delay_reason` (strings or null).
- Rules: dates `nullable|date`; `ata` `after_or_equal:` the SI's `ses_atd` when set; `ts_atd` `after_or_equal:` `ses_atd` when set; `ts_etd` `after_or_equal:tracking.ts_eta`; `ts_port_code` `nullable|exists` on the port table used by `loadingPort()`; `delay_reason` `nullable|string|max:500`.
- `saveTracking` throws/refuses when status is `CANCELLED`; writes via repository `update()` with `ses_modified_by`/`ses_modified_timestamp` the same way `save()` does.
- On mount, `ts_port_code` defaults to the first linked BIM's `sbm_via_port_code` when the SI's is empty (displayed only; saved when the user saves).

- [ ] **Step 1: Failing tests:**
  - `it('saves tracking on a CLOSED SI')` → fields persisted, `butter-success` dispatched *(Review Focus 5)*
  - `it('refuses tracking on a CANCELLED SI')` → nothing changes, error toast
  - `it('needs the SI edit permission to save tracking')` → user with only `-view` gets 403 on `saveTracking`
  - `it('rejects an arrival before departure')` → `ses_atd` 11-10, set `tracking.ata` 11-05 → `assertHasErrors('tracking.ata')`, DB unchanged *(Review Focus 4)*
  - `it('rejects a delay reason over 500 characters')`
  - `it('prefills the transhipment port from the BIM')`
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** service methods, component action (`$this->authorize('finance-shipment-si-edit')`), and the card: Flux date inputs, port select (same component the SI form uses for POL/POD), textarea for reason, header shows status badge + delay days + position; save button inside `@can('finance-shipment-si-edit')`, hidden when CANCELLED.
- [ ] **Step 4: Run** the file + `ShipExportSiInputTest.php` → PASS.
- [ ] **Step 5: Commit** `feat(finance): edit shipment tracking from the SI detail page`

### Task 4: Monitor page

**Files:**
- Modify: `ShipExportSiRepositoryInterface.php` + `EloquentShipExportSiRepository.php` — `monitorQuery(array $filters): Builder`, `paginateMonitor(array $filters, int $perPage = 20): LengthAwarePaginator`
- Create: `Modules/Finance/app/Livewire/Transaction/Shipment/ShipExportMonitor.php`
- Create: `Modules/Finance/resources/views/livewire/transaction/shipment/ship-export-monitor.blade.php`
- Modify: `Modules/Finance/routes/web.php` (next to `export-si.index`, before `export-si/{transNo}`), `Modules/Finance/routes/breadcrumbs.php`
- Test: `tests/Feature/Finance/Shipment/ShipExportMonitorTest.php`

**Interfaces:**
- Route: `GET export-monitor` → name `dashboard.module-finance.transaction.shipment.export-monitor.index`, `can:finance-shipment-si-view`. Breadcrumb parent `…exim-dashboard`, label "Export Shipment Monitor".
- Filter keys (shared with Task 5): `search`, `delay_status` (enum value or `'open'` = not arrived, default `'open'`), `etd_month` (`Y-m`), `customer_code`, `pod_code`.
- `monitorQuery` always excludes `ses_status = CANCELLED`; `'open'` = `ses_ata IS NULL`; enum value → `delayStatus()` scope; eager-loads `containers:sek_ses_sys_id,sek_container_no`, `loadingPort`, `dischargePort`, `transhipmentPort`, customer view; order by effective ETA asc, then `ses_sys_id`. Apply the same visibility the SI list applies (none beyond the permission today — keep it consistent with `paginate()`).
- Component: `#[Url]` filter props, `rows` computed, `export()` (Task 5).
- Columns: SI no. (link to `export-si.detail`), customer, shipping line/forwarder, containers (comma list), route `POL → TS → POD`, ETD, ETA, latest ETA, ATA, delay days, status badge, position, delay reason (truncated, full in tooltip).

- [ ] **Step 1: Failing tests:**
  - `it('is opened by the SI view permission and by nothing less')` (copy shape from `ShipExportSiListTest`)
  - `it('shows only shipments not yet arrived by default')`
  - `it('never lists a cancelled SI')`
  - `it('filters by delay status, ETD month, customer and destination')`
  - `it('does not let the SI number route swallow the monitor')`
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** repository methods, component, view (`x-ui::card`, `x-ui::table`, `x-ui::select`, `wire:key` per row, `wire:loading`), route, breadcrumb.
- [ ] **Step 4: Run** file + `ShipExportSiListTest.php` → PASS.
- [ ] **Step 5: Commit** `feat(finance): export shipment monitor page`

### Task 5: Queued Excel export

**Files:**
- Create: `Modules/Finance/app/Exports/Reports/Shipment/ShipExportMonitorExport.php` (`FromQuery`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`)
- Create: `Modules/Finance/app/Jobs/Reports/Shipment/ShipExportMonitorReport.php` extends `ShipReportExport`
- Modify: `ShipExportMonitor.php` — `export(): void`
- Test: `ShipExportMonitorTest.php`

**Interfaces:**
- Consumes: `ShipExportSiRepositoryInterface::monitorQuery(array $filters)`, `ShipExportSi::tracking()`.
- Job: `title()` "Export Shipment Monitor", `slug()` `export-shipment-monitor`, `export()` returns `new ShipExportMonitorExport($this->filters)`; `summary()` describes the status/month filter.
- Headings (same order as screen): SI No, Customer, Shipping Line, Containers, POL, Transhipment Port, POD, ETD, ETA, Latest ETA, Transhipment ETA, Transhipment ETD, Transhipment ATD, ATD, ATA, Planned Transit (days), Actual Transit (days), Delay (days), Status, Position, Delay Reason. Dates as `Y-m-d`.
- Component `export()`: `ShipExportMonitorReport::dispatch(auth()->user(), $filters)` then `butter-info` "The report is being prepared…" (same wording as other shipment report pages).

- [ ] **Step 1: Failing tests:**
  - `it('queues the export with the filters on screen')` — `Queue::fake()`, set filters, `call('export')`, `Queue::assertPushed(ShipExportMonitorReport::class, fn ($j) => $j->filters['delay_status'] === 'DELAYED' && $j->filters['etd_month'] === '2026-10')`
  - `it('writes one row per listed shipment with the computed status')` — build the export directly, `->query()->get()` count and `map()` of a late row returns `'Delayed'` and delay `4`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** export, job, button (`@can('finance-shipment-si-view')`).
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** `feat(finance): queued excel export for the shipment monitor`

### Task 6: Sidebar entry

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_24_110000_add_export_shipment_monitor_menu.php`
- Test: `ShipExportMonitorTest.php`

**Interfaces:**
- Row: `code` `finance.shipment.export-monitor`, type `item`, name "Export Shipment Monitor", `route_name` `dashboard.module-finance.transaction.shipment.export-monitor.index`, icon `fas fa-ship` (FA5), parent = the `parent_id` of the menu row whose `route_name` is `…export-si.index` (skip the migration quietly if that row doesn't exist); sort = that row's sort + 1.
- Gate: `model_has_permissions` with `model_type = Modules\Core\Models\MgtHris\Menu\CmMenu`, permission `finance-shipment-si-view`, `permission_logic` `or`; skip if permission not seeded.
- Upsert on `code`; end with `MenuTreeService::clearAllCache()`. Copy shape from `Modules/Core/database/migrations/2026_09_16_090000_sync_sidebar_menus_with_permissions.php` and `tablesReady()`/`migrationDisabled()` from `2026_10_22_100000_point_import_bl_menu_at_bl_list.php`.

- [ ] **Step 1: Failing test** — `it('adds the monitor to the sidebar next to the SI list, once')`: seed an SI menu row + permission, run the migration twice, assert exactly one row with the code, same parent, gated on `finance-shipment-si-view`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS.
- [ ] **Step 5: Full suite** `php artisan test --parallel --processes=4 tests/Feature/Finance/Shipment tests/Unit/Finance` → PASS; `vendor/bin/pint --test` → clean.
- [ ] **Step 6: Commit** `feat(finance): sidebar entry for the shipment monitor`
