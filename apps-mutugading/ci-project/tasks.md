# tasks.md — CI Project migration

Dependency-ordered. Each task is one commit or one small stack of commits, Conventional Commits with scope
`finance`. Run `vendor/bin/pint --dirty` and the module's tests before each. Test numbers refer to
`spec.md` §10.

---

## P0 — Data check

### T01 — Audit the legacy saving data
**Depends on:** —
Run in production (read-only):
- duplicate `(cps_cpm_sys_id, cps_date)` pairs;
- `cps_date <> TRUNC(cps_date,'MM')`;
- the current `ci_project_master` rows for `APPROVE`, `ADMIN`, `CAN_VIEW_ALL`.

**Done when:** the results are pasted into `gap-analysis.md` §6 as answers to Q1 and Q3, and — if duplicates
exist — a reviewed clean-up script sits in this folder as `saving_duplicates_cleanup.sql`.

### T02 — Audit titles, departments and master usage
**Depends on:** —
- case-insensitive duplicate `cpm_title`;
- every distinct `cpm_dept` compared against the distinct `hmemd_hmdep_dept_id` of active employees, and
  against master `type='DEPT'` codes (Q6);
- for each master type, the count of projects referencing each `code`, and any project value that has **no**
  master row — those are the rows the master page (T08) must not let anyone create a conflict with.

**Done when:** Q2 and Q6 are answered in writing, and D8 is settled.

---

## P1 — Foundation & masters

### T03 — Migrations
**Depends on:** T01, T02
`create_ci_project_period_table` (+ backfill), `add_ci_project_main_approval_columns`,
`add_ci_project_saving_unique_index`, `add_ci_project_main_indexes` — the order of `schema.md` §5. Each with
the `migrationDisabled()` guard and a real `down()`. Add the `CI_PROJECT_PERIOD` sequence row if D7 stays on
`SysIdHelper`.

**Done when:** `php artisan migrate` and `migrate:rollback` both run clean on SQLite and on the Oracle test
schema; the period backfill produces one Approved row per month up to the legacy cut-off; **every existing
project reads back `cpm_appr_status = 1`**; the saving unique-index migration **throws** if duplicates remain
rather than skipping.

### T04 — Models & enums
**Depends on:** T03
Four models per `design.md` §2, four enums per §3 — including `CiProjectApprovalStatusEnum` with its
transition table. `Searchable` + `LogsActivityWithDescription` on all four models.

**Done when:** a Pest test round-trips each model against the test connection; `CiProjectStatusEnum::tryFrom()`
on an unknown legacy status returns null without throwing; test 15 passes against the approval enum alone.

### T05 — Permissions & roles
**Depends on:** —
`CiProjectRolesAndPermissionsSeeder` creating the nineteen permissions of `design.md` §7.1, the four roles
and their grants. No permission migration — the seeder owns the whole set (`design.md` §7.2). Settle D10.

**Done when:** the seeder is idempotent (`findOrCreate`), re-running it changes nothing, the permission
cache is forgotten afterwards, `CI Project - Department` does **not** carry `finance-ci_project-approve`,
and running it is written into the deploy steps alongside `php artisan migrate`.

### T06 — DTOs
**Depends on:** T04
`CiProjectData`, `CiProjectFilterData` (including `approvalStatus`), `CiProjectMasterData`,
`CiProjectSavingEntryData` (`design.md` §4).

**Done when:** `CiProjectData` validates the required-field set of `spec.md` §4.1 and the date pair of §4.3
minus the permission-dependent ceiling.

### T07 — Repositories & bindings
**Depends on:** T04, T06
The four repositories and their interfaces (`design.md` §5), bound in Finance's `RepositoryServiceProvider`.
`reportQuery()` carries the three-month join, the department scope, the approval filter and the search;
`usageCount()` carries the master-type → column map.

**Done when:** tests 1 and 3 pass against the repository directly, and `paginateReport()` issues one query for
the page plus one for the count — no N+1.

### T08 — Master service & maintenance page (D6)
**Depends on:** T05, T06, T07
`CiProjectMasterService` + `CiProjectMasterManager` per `spec.md` §8 and `design.md` §8: type selector,
paginated searchable list, Flux create/edit/delete modal, `(type, code)` identity, `code` frozen once in use,
deletion of a row in use refused with the count, cache flushed on write. Route, breadcrumb,
`can:finance-ci_project-master-view`, sidebar entry.

**Done when:** tests 18 and 19 pass; `ADMIN` / `CAN_VIEW_ALL` / `APPROVE` appear neither in the type list nor
in the listing; every rule is enforced in the service, not only in the form.

*Built here rather than last on purpose — every dropdown in P3 reads from it, and a report page demoed
against hand-inserted master rows proves nothing.*

---

## P2 — Approval workflows

### T09 — `CiProjectPeriodService` + state machine
**Depends on:** T05, T07
Transitions per `spec.md` §2.2 (`Draft → Approved`, `Approved → Reopened`, `Reopened → Approved` — there is no
`Submitted`; the reopen remark is required with no minimum length), `isLocked()` per §2.4, `syncLegacyCutOff()` per §2.3 behind
`config('finance.ci_project.legacy_cutoff_sync')`. Activity log on every transition.

**Done when:** tests 4 and 5 pass, including the case where reopening the newest approved month lowers the
legacy cut-off to the next one down.

### T10 — `CiProjectPeriodManager` page
**Depends on:** T09
Month list with status badge, audit trail, permitted transition buttons, remark required on reopen. Route,
breadcrumb, `can:` middleware.

**Done when:** a month can be approved and reopened end to end by users holding only the matching permission,
and refused for everyone else — checked in the action, not only in the view.

### T11 — `CiProjectApprovalService` (D5)
**Depends on:** T04, T05, T07
`approve()`, `amend()`, `bulkApprove()` per `spec.md` §2A. Writes `cpm_appr_status`, `cpm_appr_by`,
`cpm_appr_date`, `cpm_appr_remark` through `setApprovalStatus()`; refuses every transition the enum does not
allow; requires a remark on amend — `required` and trimmed, `max:500`, **no minimum length**; one
activity-log entry per transition. Settle D13 here.

Nothing in this service touches saving input: approval status does not gate it (`spec.md` §2A.1).

**Done when:** tests 14, 15, 16 and 17 pass, and `bulkApprove()` demonstrably calls the same `approve()` as the
single-row path — no second copy of the rules.

---

## P3 — Report page

### T12 — `CiProjectService` (create / edit / delete)
**Depends on:** T07, T09, T11
`spec.md` §4 in full: the title check inside the transaction, the permission-dependent 20-month ceiling,
`cpm_appr_status = Draft` **written explicitly** on create, edit refused on an `Approved` project, delete
restricted to `Draft`, and the delete-with-savings behaviour.

**Done when:** tests 6, 7, 8, 13 and 14 pass, and test 20 confirms pre-existing projects still behave.

### T13 — `CiProjectSavingService`
**Depends on:** T07, T09, T11
`spec.md` §3. One write path, per row, transactional, upsert on `(project, month)`. The read-only rules are
the month lock, `FINISH` and scope — **not** the project's approval status. Settle D9.

**Done when:** tests 3 and 12 pass, and a request that targets another department's project is refused at the
service even when the view would have rendered the input read-only.

### T14 — `CiProjectAuthorizationService`
**Depends on:** T05, T11
The matrix of `spec.md` §5, with `PermissionDoesNotExist` degrading to "not granted".

**Done when:** it is the only class in the domain calling `can()` / `hasRole()`, and every service write
routes through it.

### T15 — `CiProjectReport` component + views
**Depends on:** T08, T12, T13, T14
Component per `design.md` §8; view split into the five partials. Pagination, `#[Url]` on period and search,
butter toasts, Flux confirmation modal, Alpine for the enter-to-next-input and the +18-month suggestion.
Approval badge per row, Approve / Amend actions, approval-status filter, pending-approval count in the
header, bulk approve. Route, breadcrumb, `can:` middleware, sidebar entry. Settle D12.

**Done when:** for three sampled months × three departments every figure equals the legacy page's; tests 1, 7,
11, 12 and 20 pass; no `wire:navigate`, no jQuery, no Bootstrap classes; Pint clean.

---

## P4 — Exports & command

### T16 — Three exports + queued job
**Depends on:** T07, T15
`CiProjectHorizontalExport`, `CiProjectVerticalExport`, `CiProjectPivotExport`, all fed by
`CiProjectFilterData`, the horizontal one carrying an approval-status column; `ExportCiProject` job +
`ReportStatusNotification`. The pivot's Oracle `PIVOT` moves into `EloquentCiProjectRepository::pivotRows()`
with bound parameters.

**Done when:** test 10 passes and the pivot output matches a legacy run for the same year, cell for cell.

### T17 — Auto-finish command
**Depends on:** T04
`ci-project:auto-finish` as one mass update, scheduled daily, `command_ci_project_auto_finish` added to
`config/logging.php`, one activity entry naming the affected transaction numbers. It does not touch
`cpm_appr_status` — lifecycle and approval are independent (`spec.md` §2A).

**Done when:** test 9 passes and a dry run on the test schema reports the same count as the legacy command
would.

---

## P5 — Cut-over

### T18 — Assign the roles to real people **(blocking, human)**
**Depends on:** T05, T01
Take the `ADMIN` and `CAN_VIEW_ALL` lists from T01 and assign `CI Project - Admin` / `CI Project - Viewer` to
those users, plus `CI Project - Department` to each department's PIC. Confirm with the CI coordinator that
**at least two people** hold `finance-ci_project-approve` — a single approver is a single point of failure
now that drafts cannot record savings.

**Done when:** every person who could use the legacy page can use the new one, confirmed by them, and the
assignment is recorded in the PR description.

### T19 — Release note & hand-over
**Depends on:** T15, T18
Cover the eight user-visible changes in `gap-analysis.md` §5 — leading with the project approval workflow,
which is the one nobody has seen before — plus a walk-through of the period page and the master page for the
coordinator.

**Done when:** the coordinator has approved a real project and closed one real month on the new page while
the legacy page still shows the same figures.

---

## P6 — Retirement (separate PR, after the old app is off)

### T20 — Remove the compatibility layer
**Depends on:** the legacy app being switched off
Delete `syncLegacyCutOff()` and its config flag; migration to delete the `ADMIN`, `CAN_VIEW_ALL` and `APPROVE`
rows from `ci_project_master`; optionally flip `cpm_appr_status` to `DEFAULT 0` now that no outside writer
inserts projects.

**Done when:** no code path writes `ci_project_master` outside the master page, and the three types are gone.
