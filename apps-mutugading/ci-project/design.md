# design.md — architecture

Per **D1** everything lives in `Modules/Finance` under a `CiProject` domain folder in each layer, the same
way `FpScan` and `RawMaterial` already do. No new nwidart module, no change to `module.json`.

Flow is the house standard: **Livewire → Service → Repository → Model**. Livewire never touches a model,
services never build queries inline, repositories never decide policy.

---

## 1. Folder map

```
Modules/Finance/app/
├── Models/MgtApps/Transaction/CiProject/
│   ├── CiProjectMain.php
│   ├── CiProjectSaving.php
│   ├── CiProjectMaster.php
│   └── CiProjectPeriod.php
├── Enums/Transaction/CiProject/
│   ├── CiProjectPeriodStatusEnum.php
│   ├── CiProjectApprovalStatusEnum.php
│   ├── CiProjectStatusEnum.php
│   └── CiProjectSavingTypeEnum.php
├── Data/Transaction/CiProject/
│   ├── CiProjectData.php
│   ├── CiProjectFilterData.php
│   ├── CiProjectMasterData.php
│   └── CiProjectSavingEntryData.php
├── Interfaces/Transaction/CiProject/
│   ├── CiProjectRepositoryInterface.php
│   ├── CiProjectSavingRepositoryInterface.php
│   ├── CiProjectMasterRepositoryInterface.php
│   └── CiProjectPeriodRepositoryInterface.php
├── Repositories/Transaction/CiProject/
│   ├── EloquentCiProjectRepository.php
│   ├── EloquentCiProjectSavingRepository.php
│   ├── EloquentCiProjectMasterRepository.php
│   └── EloquentCiProjectPeriodRepository.php
├── Services/Transaction/CiProject/
│   ├── CiProjectService.php
│   ├── CiProjectApprovalService.php
│   ├── CiProjectSavingService.php
│   ├── CiProjectPeriodService.php
│   ├── CiProjectMasterService.php
│   └── CiProjectAuthorizationService.php
├── Livewire/Transaction/CiProject/
│   ├── CiProjectReport.php
│   ├── CiProjectPeriodManager.php
│   └── CiProjectMasterManager.php
├── Exports/Transaction/CiProject/
│   ├── CiProjectHorizontalExport.php
│   ├── CiProjectVerticalExport.php
│   └── CiProjectPivotExport.php
├── Jobs/Transaction/CiProject/
│   └── ExportCiProject.php
└── Console/Commands/
    └── CiProjectAutoFinishCommand.php

Modules/Finance/resources/views/livewire/transaction/ci-project/
├── ci-project-report.blade.php
├── partials/_filters.blade.php
├── partials/_table.blade.php
├── partials/_form-modal.blade.php
├── partials/_detail-modal.blade.php
├── partials/_approval-actions.blade.php
├── ci-project-period-manager.blade.php
└── ci-project-master-manager.blade.php

Modules/Finance/database/migrations/   # see schema.md §5
Modules/Finance/tests/Feature/Transaction/CiProject/
```

---

## 2. Models

All four: `protected $connection = 'oracle_mgtapps';`, explicit `$fillable`, `Searchable`,
`LogsActivityWithDescription`.

### `CiProjectMain`

```php
protected $table = 'ci_project_main';
protected $primaryKey = 'cpm_sys_id';
public $incrementing = false;
protected $keyType = 'string';
public $timestamps = false;          // cpm_cr_date / cpm_upd_date are written explicitly

protected array $searchable = [
    'cpm_trans_code', 'cpm_trans_no', 'cpm_dept', 'cpm_group',
    'cpm_project_id', 'cpm_wave', 'cpm_leader', 'cpm_title',
    'cpm_remarks', 'cpm_status', 'cpm_sav_type',
];

protected function casts(): array
{
    return [
        'cpm_date' => 'date',
        'cpm_completion_date' => 'date',
        'cpm_saving_end' => 'date',
        'cpm_cr_date' => 'datetime',
        'cpm_upd_date' => 'datetime',
        'cpm_status' => CiProjectStatusEnum::class,   // see §3
        'cpm_sav_type' => CiProjectSavingTypeEnum::class,
        'cpm_appr_status' => CiProjectApprovalStatusEnum::class,
        'cpm_appr_date' => 'datetime',
    ];
}

public function savings(): HasMany;   // cps_cpm_sys_id → cpm_sys_id
```

Two deliberate departures from legacy:

- **`$searchable` is an explicit list.** Legacy searched *every* fillable column with
  `UPPER(col) LIKE '%term%'`, dates and numbers included, which forces Oracle into implicit conversions
  and can throw on a malformed value. Text columns only.
- **Casts are plain `date`, not `date:d-m-Y`.** Formatting is the view's job; a display-format cast makes
  `Carbon` comparisons in services read back wrong.

### `CiProjectSaving`

`cps_sys_id` PK, `belongsTo` main, casts `cps_date` → `date`, `cps_saving` → `float`.

### `CiProjectMaster`

No primary key; `public $incrementing = false`, `$timestamps = false`. Reads go through the repository's
cached `all()` (§5.4) — never call it statically from a view the way legacy does.

### `CiProjectPeriod`

`cpp_sys_id` PK string, `const CREATED_AT = 'cpp_cr_date'`, `const UPDATED_AT = 'cpp_upd_date'`,
`cpp_status` cast to `CiProjectPeriodStatusEnum`, `cpp_period` cast to `date`.

---

## 3. Enums

### `CiProjectPeriodStatusEnum: int`

| Case | Value | `label()` | `badgeVariant()` | Locked? |
|---|---|---|---|---|
| `Draft` | 0 | Draft | `zinc` | no |
| `Approved` | 1 | Approved | `green` | **yes** |
| `Reopened` | 2 | Reopened | `orange` | no |

Plus `isLocked(): bool`, `nextActionLabel(): ?string`, `allowedTransitions(): array`,
`timelineSteps(): array`, `options(): array`. The transition table is `spec.md` §2.2 — the enum owns it so
no caller can invent a transition.

There is no `Submitted`; the reasoning is in `schema.md` §4.1.

### `CiProjectApprovalStatusEnum: int` (D5)

The project's own workflow. Distinct from `CiProjectStatusEnum`, which is the project's *lifecycle*
(`ACTIVE` / `FINISH`) and is legacy data — do not merge the two, a finished project is still an approved one.

| Case | Value | `label()` | `badgeVariant()` | Editable? | Takes savings? |
|---|---|---|---|---|---|
| `Draft` | 0 | Draft | `zinc` | yes | yes |
| `Approved` | 1 | Approved | `green` | **no — amend first** | yes |
| `Amend` | 2 | Amendment | `amber` | yes | yes |

Plus `isEditable(): bool`, `allowedTransitions(): array`, `nextActionLabel()`, `timelineSteps()`,
`options()`. As with the period enum, the transition table (`spec.md` §2A.2) lives here so no service can
invent a jump.

There is deliberately **no** `acceptsSavings()`: every state does, so a method asking the question would only
invite a caller to gate on it. Saving input is decided by `spec.md` §3.2 alone.

`Draft` and `Amend` behave identically; they are kept apart because the audit needs to tell "never approved"
from "approved, being corrected, for this reason".

`1` is the default in the database — see `schema.md` §1.1 for why, and note that
`CiProjectService::create()` must set `Draft` explicitly rather than letting the column default apply.

### `CiProjectStatusEnum: string`

`Active` = `ACTIVE`, `IndirectSaving` = `INDIRECT-SAVING`, `Finish` = `FINISH`. Backed by the exact legacy
strings so no data changes. Provides `sortWeight(): int` (1/2/3) so the list ordering stops being a raw
`CASE` expression, and `badgeVariant()`.

Values outside the enum exist in old data (`ci_project_master type='STATUS'` may carry more). Use
`tryFrom()` and fall back to a neutral badge with the raw string — never `from()`.

### `CiProjectSavingTypeEnum: string`

`Lss` = `LSS`, `ProSav` = `PRO_SAV`, `ProInc` = `PRO_INC`, `Oth` = `OTH`, `OthOt` = `OTH_OT`, plus
`allowedGroupCodes(): ?array` returning the group filter of `FR-11` (null = all but the reserved ones).
That rule is currently a `switch` inside the Livewire class; it belongs here.

---

## 4. DTOs

- **`CiProjectData`** (Spatie `Data`, `WireableData`) — the create/edit form. Carries the validation rules
  of `FR-10`/`FR-12` as attributes, except the 20-month ceiling, which is permission-dependent and is
  applied by the service (`spec.md` §4.3).
- **`CiProjectMasterData`** (Spatie `Data`, `WireableData`) — the master maintenance form: `type`, `code`,
  `name`, `remark`, `flex_1`, `flex_2`, plus the original `(type, code)` pair when editing, so the service can
  tell a rename from a create.
- **`CiProjectFilterData`** — `dept`, `group`, `status`, `approvalStatus`, `search`, `period` (`Y-m`),
  `orderDesc`. One object
  passed identically to the repository, the exports and the export jobs, so screen and file can never diverge.
- **`CiProjectSavingEntryData`** — `projectSysId`, `period` (Carbon, first of month), `amount`.

---

## 5. Repositories

### 5.1 `CiProjectRepositoryInterface`

```php
public function paginateReport(CiProjectFilterData $filters, ?HmEmpData $user, int $perPage): LengthAwarePaginator;
public function reportQuery(CiProjectFilterData $filters, ?HmEmpData $user): Builder;   // exports reuse this
public function findBySysId(string $sysId): ?CiProjectMain;
public function findWithSavings(string $sysId): ?CiProjectMain;
public function titleExists(string $title, ?string $exceptSysId = null): bool;
public function create(array $attributes): CiProjectMain;
public function update(string $sysId, array $attributes): bool;
public function delete(string $sysId): bool;
public function markFinishedUpTo(CarbonInterface $date, string $actor): int;   // auto-finish, one mass update
public function setApprovalStatus(                                            // D5, one write path
    string $sysId,
    CiProjectApprovalStatusEnum $status,
    string $actor,
    ?string $remark,
): bool;
public function countPendingApproval(?HmEmpData $user): int;                  // the "3 drafts waiting" badge
```

`reportQuery()` is the single place that builds the three-month saving join. It is the direct replacement
for the legacy `selectRaw` + three `leftJoin`s, and it is what both the screen and the horizontal/vertical
exports call — `paginateReport()` is just `reportQuery()->paginate()`.

Department scope is applied **inside** the repository via `AppliesEmployeeAuthorization` in direct-column
mode against `cpm_dept`, with `bypassRoles: ['Super Admin']` and
`bypassPermissions: ['finance-ci_project-view_all_scope']`. Doing it here rather than in the Livewire
component is what makes `FR-4` a security boundary instead of a default filter value.

Search uses the `Searchable` trait's `search()` scope, not a hand-rolled `orWhereRaw` loop.

### 5.2 `CiProjectSavingRepositoryInterface`

```php
public function upsertForPeriod(CiProjectSavingEntryData $entry, string $actor): CiProjectSaving;
public function savingsByYear(string $projectSysId): array;   // [year => [1..12 => amount, 'Total' => n]]
public function periodsWithData(): Collection;                // distinct TRUNC(cps_date,'MM'), for the period page
```

`upsertForPeriod()` matches on `(cps_cpm_sys_id, cps_date)` and writes `cps_date` from the same value it
matched on — the legacy duplicate bug — and runs inside a transaction so it pairs with the new unique
index of `schema.md` §2.1.

`savingsByYear()` moves the year/month bucketing out of the Livewire class (`detailProject()`) and does the
grouping in SQL (`EXTRACT(YEAR …)`, `EXTRACT(MONTH …)`, `SUM`) instead of in PHP over every saving row.

### 5.3 `CiProjectPeriodRepositoryInterface`

```php
public function findByPeriod(CarbonInterface $period): ?CiProjectPeriod;
public function firstOrCreateDraft(CarbonInterface $period, string $actor): CiProjectPeriod;
public function highestApprovedPeriod(): ?CarbonInterface;   // feeds the legacy dual-write
public function paginate(int $perPage): LengthAwarePaginator;
public function transition(string $sysId, CiProjectPeriodStatusEnum $to, string $actor, ?string $remark): bool;
```

### 5.4 `CiProjectMasterRepositoryInterface`

```php
public function all(): Collection;                  // cached, key `finance.ci_project.masters`, 10 min
public function byType(string $type): Collection;
public function paginateByType(?string $type, string $search, int $perPage): LengthAwarePaginator;
public function find(string $type, string $code): ?CiProjectMaster;
public function exists(string $type, string $code): bool;
public function usageCount(string $type, string $code): int;   // FR-36 / FR-37
public function save(CiProjectMasterData $data): CiProjectMaster;
public function delete(string $type, string $code): bool;
public function flush(): void;
```

`usageCount()` maps a master type to the `ci_project_main` column that references it
(`GROUP → cpm_group`, `STATUS → cpm_status`, `DEPT → cpm_dept`, `SAV_TYPE → cpm_sav_type`,
`FREQUENCY → cpm_frequency`, `IMPROVE → cpm_improve_type`, `IMPACT1..3 → cpm_impact_1..3`,
`TRANS_CODE → cpm_trans_code`) and counts the matching projects. That mapping is a `const array` on the
repository, and it is the only place it exists.

`save()` and `delete()` call `flush()` themselves — `FR-28`. The legacy manual "delete cache" button and its
two `Cache::forget()` keys disappear.

The pivot export's raw Oracle `PIVOT` query lives in `EloquentCiProjectRepository::pivotRows()` with bound
parameters, not in the export class behind the `DB::` facade.

---

## 6. Services

| Service | Owns |
|---|---|
| `CiProjectService` | Create / edit / delete a project, title uniqueness, the 20-month rule, audit fields, activity log |
| `CiProjectApprovalService` | The project state machine: `approve()`, `amend()`, `bulkApprove()`; writes `cpm_appr_*`, logs every transition |
| `CiProjectSavingService` | Saving input: lock check → status check → upsert. The single write path for `cps_saving` |
| `CiProjectPeriodService` | The state machine, `isLocked(period)`, `syncLegacyCutOff()` |
| `CiProjectMasterService` | Master CRUD, dropdown lists, `groupsFor(CiProjectSavingTypeEnum)` |
| `CiProjectAuthorizationService` | One place answering "may this user do X to this project?" — see §7.3 |

Services add the audit fields (`*_cr_uid` / `*_cr_date` on insert, `*_upd_uid` / `*_upd_date` on update) from
`auth()->user()`, never from `session('USER_NAME')`.

---

## 7. Permissions (D4)

### 7.1 The set

Following the `finance-faktur_pajak-*` convention already in `FinanceRolesAndPermissionsSeeder`:

| Permission | Gates |
|---|---|
| `finance-ci_project-view` | Reaching the report page at all |
| `finance-ci_project-view_all_scope` | Seeing departments other than your own (replaces `CAN_VIEW_ALL`) |
| `finance-ci_project-create` | Registering a project |
| `finance-ci_project-edit` | Editing a project **in your own department** |
| `finance-ci_project-edit_all_scope` | Editing a project in any department |
| `finance-ci_project-delete` | Deleting (was hard-wired to the `Super Admin` role) |
| `finance-ci_project-approve` | Approving a project: `Draft → Approved`, `Amend → Approved` (D5) |
| `finance-ci_project-amend` | Putting an approved project back into amendment (D5) |
| `finance-ci_project-saving_input` | Typing a monthly saving |
| `finance-ci_project-saving_input_locked` | Typing into an **approved** month (part of the old `$admin`) |
| `finance-ci_project-status_finish_edit` | Typing a saving on a `FINISH` project |
| `finance-ci_project-validation_bypass` | Exceeding the 20-month saving-end ceiling |
| `finance-ci_project-period-view` | The period page |
| `finance-ci_project-period-approve` | Draft / Reopened → Approved |
| `finance-ci_project-period-reopen` | Approved → Reopened |
| `finance-ci_project-export` | Horizontal / vertical export |
| `finance-ci_project-export_pivot` | Pivot export (legacy required view-all for it) |
| `finance-ci_project-master-view` | Reaching the master maintenance page (D6) |
| `finance-ci_project-master-manage` | Creating / editing / deleting master rows |

Nineteen in total. The old single `$admin` flag is deliberately split into five of them. It conflated "sees everything", "may write a
locked month", "may write a FINISH project", "may break the 20-month rule" and "may approve" — which is why
granting somebody the ability to approve also silently let them bypass every validation on the page.

### 7.2 Roles

| Role | Gets |
|---|---|
| `Super Admin` | All of the above |
| `CI Project - Admin` (new) | Everything except `delete` — including `approve`, `amend`, both period actions and `master-manage` |
| `CI Project - Department` (new) | `view`, `create`, `edit`, `saving_input`, `export`. **Not** `approve` — a department cannot approve its own project, which is the point of D5 |
| `CI Project - Viewer` (new) | `view`, `view_all_scope`, `export`, `export_pivot`, `period-view`, `master-view` |
| `Finance` | `view`, `view_all_scope`, `export`, `export_pivot` |

Created idempotently by `CiProjectRolesAndPermissionsSeeder` — permissions and roles both, in one place.
There is deliberately no permission migration: a grant is seed data, and keeping half of it in a migration
means two files to hold in step whenever the list changes.

**So seeding is a required deploy step.** Run it with the migrations:

```bash
php artisan db:seed --class="Modules\Finance\Database\Seeders\CiProjectRolesAndPermissionsSeeder" --force
```

Until it runs, nobody holds any `finance-ci_project-*` permission and the routes' `can:` middleware turns
every CI Project page away. That fails closed rather than open, and
`CiProjectAuthorizationService` treats a missing permission row as "not granted" (§7.3), so the gap costs
access rather than raising a 500.

**Migrating the people:** before the cut-over, list the current holders and assign roles to match —

```sql
SELECT type, code FROM ci_project_master WHERE type IN ('ADMIN','CAN_VIEW_ALL') ORDER BY type, code;
```

`ADMIN` → `CI Project - Admin`, `CAN_VIEW_ALL` → `CI Project - Viewer`. This is a manual, reviewed step
(`tasks.md` T18); do not automate a permission grant from a dropdown table.

### 7.3 Where the checks live

Three layers, each doing one job:

1. **Route** — `can:finance-ci_project-view` etc. Keeps unauthorised users off the page.
2. **`CiProjectAuthorizationService`** — every per-record question (`canEdit($project)`, `canInputSaving($project, $period)`,
   `canApprove($period)`). Called by the services on **every** write.
3. **View** — `@can` around buttons, purely cosmetic.

The legacy page checked the delete permission in the Blade `@if` *and* in the action, which is right, but
checked `canModify()` only on save and not on `editProject()`, and gated the saving input in the view with a
`readonly` attribute that a client can simply drop. **Livewire actions are public endpoints**: the rule in
this codebase is that layer 3 never stands alone.

---

## 8. Livewire components

### `CiProjectReport`

State: `filters` (`CiProjectFilterData`), `period` (`Y-m`), `orderDesc`, `perPage`, `form` (`CiProjectData`),
`editMode`, `savingInput[]`, `detail`.

- `WithPagination` (`FR-5`).
- Services injected in `boot()`.
- `#[Url]` on the period and the search term (legacy used `$queryString`).
- Toasts via `$this->dispatch('butter-success', message: …)` — the legacy `dispatchBrowserEvent` calls do
  not exist in Livewire 3.
- Confirmations via `<x-ui::modal.confirmation>`. The legacy approach built a JavaScript string
  (`"Livewire.find('…').confirmDelProject('$sysid');"`) with the id interpolated into it — that is both
  Livewire-2-only and a script-injection shape. It does not come across.
- The saving input binds `wire:model.blur="savingInput.{sysId}"` — keyed by project only. Legacy keyed it by
  project *and* the existing saving id (`newSaving.{cpm}.{cps}`), which is why the write path had to dig the
  id back out with `array_key_first()`. The period comes from component state, not from the DOM.
- `wire:key` on every row; `wire:loading` targets per action.

The row carries an approval badge and, in `_approval-actions.blade.php`, the Approve / Amend buttons the
user is entitled to (`@can` around each, service-checked on click). An approval-status filter and a
"pending approval" count in the header make the coordinator's queue reachable without a separate page —
`countPendingApproval()` feeds the count. Bulk approve is a checkbox column plus one action; it loops the
same `approve()` the single button calls, so no rule can diverge between the two paths.

Its Blade file is split into five partials (§1) — one 656-line view is how the legacy page became
hard to change.

### `CiProjectPeriodManager`

Lists months (from `periodsWithData()` unioned with existing period rows), each with its status badge, the
audit trail, and the transition buttons the user is allowed to press. Reopen requires a remark before the
button submits.

### `CiProjectMasterManager` (D6)

A first-class master page, not an afterthought — every dropdown on the report page is one of its rows, and
today adding a group means an `INSERT` by hand.

- A type selector (the ten offered types of `spec.md` §8.1) drives a paginated, searchable list.
- Create / edit / delete in a Flux modal bound to `CiProjectMasterData`.
- `(type, code)` is the identity: creating a duplicate pair is refused, `code` is read-only once the row is
  in use, and deleting a row in use is refused with the count (FR-36, FR-37).
- `ADMIN`, `CAN_VIEW_ALL` and `APPROVE` are not in the type list, and the component filters them out of the
  listing too — they are legacy access data, and editing them from here would look like it did something.
- Writes go through `CiProjectMasterService`, which flushes the cache (FR-28).

---

## 9. Routes & breadcrumbs

In `Modules/Finance/routes/web.php`, inside the existing
`prefix('dashboard/module-finance')->name('dashboard.module-finance.')` → `transaction` group:

```php
Route::get('ci-project', CiProjectReport::class)
    ->name('ci-project')->middleware(['can:finance-ci_project-view']);
Route::get('ci-project/periods', CiProjectPeriodManager::class)
    ->name('ci-project.periods')->middleware(['can:finance-ci_project-period-view']);
Route::get('ci-project/masters', CiProjectMasterManager::class)
    ->name('ci-project.masters')->middleware(['can:finance-ci_project-master-view']);
```

Breadcrumbs for all three in `Modules/Finance/routes/breadcrumbs.php`, parented to
`dashboard.module-finance.index`. That also gives each page its browser title through `PageTitleHelper` —
no `#[Title]` needed.

Legacy URL `/Apps/CI/Project-List` is not preserved; the old app keeps serving it until it is retired.

---

## 10. Exports & jobs

Three export classes, all driven by `CiProjectFilterData` and all reading through the repository so the
department scope applies to the file exactly as it does to the screen (`FR-25`).

Per the project standard (`CLAUDE.md` → Import/Export Pattern) they run through one queued job,
`ExportCiProject`, which stores the file and sends `ReportStatusNotification` with the download link.
Legacy returned `Excel::download()` straight out of the Livewire action, which blocks the request for the
whole pivot build.

No NIK column is emitted, so `FormatsNikForExport` does not apply here.

---

## 11. Console command

`CiProjectAutoFinishCommand` (`ci-project:auto-finish`), registered on the schedule daily.

- One mass update via `markFinishedUpTo()`, not a per-row `save()` loop.
- Writes `cpm_upd_uid = 'SYSTEM'` (matching `AppliesTransactionAuthorization::SYSTEM_CREATED_BY`).
- Logs to the `command_ci_project_auto_finish` channel — **add it to `config/logging.php`**; it exists in the
  legacy app's config, not in this one.
- Records one activity-log entry with the affected count and the transaction numbers.
