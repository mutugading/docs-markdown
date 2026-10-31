# CI Project Savings — migration doc set

Migration of the legacy **CI Project** page (continuous-improvement project savings report) from the
old `mgthris` Laravel app into `apps-mutugading`, as the **CiProject** domain inside `Modules/Finance`.

Legacy sources (read-only reference — do not copy the code, port the behaviour):

| Legacy file | What it is |
|---|---|
| `app/Http/Livewire/CiProject/CiProjectReport.php` | The whole page: list, filters, inline saving input, add/edit modal, detail modal, approve, delete, 3 exports |
| `resources/views/livewire/ci-project/ci-project-report.blade.php` | 656-line Bootstrap/AdminLTE view |
| `app/Models/CiProject/CiProjectMains.php` | `ci_project_main` on `oracle_mgtapps` |
| `app/Models/CiProject/CiProjectSavings.php` | `ci_project_saving` |
| `app/Models/CiProject/CiProjectMasters.php` | `ci_project_master` — dropdown values **and** the ADMIN / CAN_VIEW_ALL / APPROVE rows |
| `app/Services/CiProject/CiProjectService.php` | `applyFilterScope()` shared by the page and the exports |
| `app/Exports/CiProject/CiProjectExport{Hor,Ver,Pivot,Pivot2}.php` | Three usable exports (Pivot is superseded by Pivot2) |
| `app/Console/Commands/CiProjectAutoFinish.php` | Nightly job: ACTIVE → FINISH once `cpm_saving_end` has passed |
| `routes/web.php:529` | `GET /Apps/CI/Project-List` → `apps.ci.project.list` |

## Documents

| File | What it answers |
|---|---|
| [`PRD.md`](PRD.md) | Why we migrate, who uses it, functional requirements, scope & non-goals, success criteria |
| [`schema.md`](schema.md) | The one new table, the additive indexes/columns on the legacy tables, and the backfill |
| [`design.md`](design.md) | Folder map, models, repositories, services, DTOs, enums, Livewire pages, permissions, routes |
| [`spec.md`](spec.md) | Behaviour contracts: period lock, saving upsert, validation, authorization matrix, exports, auto-finish |
| [`gap-analysis.md`](gap-analysis.md) | Legacy → new mapping, parity checklist, legacy bugs being fixed, what changes behaviour, open questions |
| [`plan.md`](plan.md) | Phased implementation plan |
| [`tasks.md`](tasks.md) | Numbered, dependency-ordered tasks with acceptance criteria |

Read `PRD.md` → `schema.md` → `design.md` first. `spec.md` is the one you keep open while coding.

## Decisions already taken (2026-08-31)

| # | Decision |
|---|---|
| **D1** | **Placement:** inside `Modules/Finance`, as the `CiProject` domain folder in every layer (`app/Livewire/Transaction/CiProject/`, `app/Models/MgtApps/Transaction/CiProject/`, …). No new nwidart module. |
| **D2** | **Legacy tables stay.** The new app reads and writes the same `ci_project_main` / `ci_project_saving` / `ci_project_master` on `oracle_mgtapps`. No backfill of project or saving rows. Only additive changes: one new table, four new columns on `ci_project_main`, one unique index, two supporting indexes. |
| **D3** | **Period approval becomes a per-period lock table** (`ci_project_period`): one row per saving month, `Draft → Approved`, plus `Reopened`, with approver, timestamp, remark and activity log. It replaces the single `ci_project_master (type='APPROVE')` cut-off row. During cut-over that legacy row is **dual-written** so the old app keeps working. |
| **D4** | **Access control is Spatie permissions + department scope.** `finance-ci_project-*` permissions plus `finance-ci_project-view_all_scope`; the department restriction comes from the signed-in employee's own department. The `ADMIN` and `CAN_VIEW_ALL` master rows are dropped. |
| **D5** | **Projects get their own approval workflow:** `Draft → Approved`, and `Approved → Amend → Approved`. A project is registered as a draft and stays fully usable — editable, and taking monthly savings — while it waits; approval is an **edit gate**, so once approved it is not changed in place but amended, with a reason, and approved again. Four additive columns on `ci_project_main` (`cpm_appr_*`). |
| **D6** | **The master table gets its own maintenance page** (`CiProjectMasterManager`), built early rather than last — every dropdown on the report page reads from it. |

## Where the build differs from the docs

Decisions taken while implementing P1–P4. Each one is deliberate; the docs above stay
the intent, this is what the code does.

| # | Decision | Why |
|---|---|---|
| **D7 settled** | `cpp_sys_id` is derived from the month (`CPP` + `Ymd` of the first day), not from `SysIdHelper` | `cpp_period` is already unique, so the month is a collision-free key. It also keeps the table free of a `HmMstSequences` row that CI would have to seed before any test could touch a period. |
| **D8 settled** | `add_ci_project_main_indexes` creates the `UPPER(cpm_title)` unique index **only when the data is already clean**, and logs a warning otherwise | The audit (`data_audit.sql` §3) cannot run before the migration does. The service-level check runs either way, so a skipped index costs the race protection, not the rule. **The dev audit came back clean, so the index will be created** — see `gap-analysis.md` §6. |
| **Legacy tables** | A `create_ci_project_legacy_tables` migration creates `ci_project_main` / `_saving` / `_master` **only if missing** | In production all three exist and it does nothing. Without it, a fresh schema — CI on SQLite above all — has nothing for the later migrations to alter. Its `down()` is deliberately empty: these hold live data. |
| **Pivot export** | One grouped query in `EloquentCiProjectRepository::pivotRows()`, pivoted in the export class, instead of the Oracle `PIVOT` statement | Same figures, bound parameters, and it runs under SQLite so the export is testable. The grouped result is one row per combination per month — small. |
| **Date comparisons** | Every date binding is `Y-m-d H:i:s`, and month matching is exact equality, never `whereDate` | `NLS_DATE_FORMAT` here is `YYYY-MM-DD HH24:MI:SS`, so a bare `Y-m-d` raises ORA-01861; and `whereDate` compiles to `TRUNC` on Oracle but a string `strftime` on SQLite, which disagree at exactly the boundaries that matter. Equality is also what lets the report join use the new unique index. |
| **Tests** | Live in `tests/Feature/Finance/CiProject/`, not `Modules/Finance/tests/` | `phpunit.xml` only scans `tests/`. A suite under `Modules/` would never have run. |
| **`connectionsToTransact`** | `Tests\TestCase` now transacts the three Oracle connections as well as the default | SQLite gives each connection its own `:memory:` database, and RefreshDatabase only preserves the handles of connections it transacts — so anything on `oracle_mgtapps` vanished after the first test in a run. |
| **Rows-per-page control** | A plain `<x-ui::form.select>` instead of `<x-ui::dropdown.rows-select>` | That shared component reads its options through an Oracle-only query (`REGEXP_LIKE`), which makes every page using it impossible to render in a test. |

## Naming at a glance

- Tables: `ci_project_main` / `ci_project_saving` / `ci_project_master` (same names, `oracle_mgtapps`) plus the
  new `ci_project_period`, column prefix `cpp_`. `ci_project_main` gains `cpm_appr_status`, `cpm_appr_by`,
  `cpm_appr_date`, `cpm_appr_remark`.
- Routes: `dashboard/module-finance/transaction/ci-project*`, names `dashboard.module-finance.transaction.ci-project*`.
- Permissions: `finance-ci_project-{action}` — the same shape as the existing `finance-faktur_pajak-*` set.
- Log channel: `command_ci_project_auto_finish`.
