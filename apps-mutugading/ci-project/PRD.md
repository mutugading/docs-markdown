# PRD — CI Project Savings

## 1. What this is

Every department runs continuous-improvement (CI) projects — LSS, GKM, process saving, process income and
others. Each project declares an expected saving, and every month the department records how much that
project actually saved. The page is the single place where:

- a department registers a CI project (department, group, wave, leader, title, impacts, frequency, saving type);
- a department types the current month's saving straight into the row, spreadsheet-style;
- the CI coordinator reviews all departments and **approves** a month, after which nobody may change it;
- management pulls the numbers out as Excel — flat horizontal, flat vertical, and a year × month pivot.

Today it lives in the old `mgthris` app as one 738-line Livewire class and a 656-line Bootstrap view.
It is moving to `apps-mutugading` because the old app is being retired, and because its access control
and its approval step are both weak enough to be worth rebuilding rather than copying.

## 2. Who uses it

| Actor | What they do | Today |
|---|---|---|
| **Department PIC** | Registers projects for their own department, types the monthly saving, edits their own projects | Identified by their employee department (`hmemd_hmdep_dept_id`) matching `cpm_dept` |
| **CI coordinator (CIM)** | Sees every department, approves new projects, approves the month, may exceed the 20-month saving-end rule | A row in `ci_project_master` with `type='ADMIN'` holding their username |
| **Viewer (management)** | Sees every department read-only, exports | A row with `type='CAN_VIEW_ALL'` |
| **Super Admin** | Everything, including delete | Spatie role, already correct |
| **Scheduler** | Nightly `ci-project:auto-finish` flips ACTIVE → FINISH once the saving-end date has passed | Console command |

Two of those five actors are defined by **data rows in a dropdown table**, not by the permission system.
That is the main thing this migration fixes.

## 3. Functional requirements

### 3.1 Project list (the main page)

- **FR-1** Show every project the user is allowed to see, ordered `ACTIVE` → `INDIRECT-SAVING` → `FINISH` →
  everything else, then by transaction number (direction toggleable).
- **FR-2** For the selected month, show the saving for that month and the two months before it as three
  read-only columns, headed with the month name.
- **FR-3** Filter by department, group and status; free-text search; a month picker that drives the three
  columns; a clear-filters action.
- **FR-4** A user without `view_all_scope` sees only their own department — as a query restriction, not a
  default filter value.
- **FR-5** The list is paginated. (Legacy loads every row.)

### 3.2 Saving input

- **FR-6** One numeric input per row writes the saving for the selected month, saved on change.
- **FR-7** The input is read-only when the month is approved or when the project is `FINISH`, unless the user
  holds the matching bypass permission. A project's own approval status never blocks saving input.
- **FR-8** Saving a month that already has a row updates it; it never creates a second row for the same
  project and month.
- **FR-9** Entering a value on a `FINISH` project requires `finance-ci_project-status_finish_edit`.

### 3.3 Project create / edit

- **FR-10** Create requires: saving type, department, group, title, frequency, status, improvement type and
  impact 1. Title must be unique, case-insensitively.
- **FR-11** The group dropdown is filtered by the chosen saving type (LSS → LSS BB / LSS GB / GKM;
  PRO_SAV → PRO.SAV; PRO_INC → PRO.INC; OTH / OTH_OT → everything else).
- **FR-12** When either completion date or saving-end is filled, both are required, saving-end must be on or
  after completion date, and — without `finance-ci_project-validation_bypass` — no more than 20 months after it.
- **FR-13** A project may only be edited by someone in its own department, or by a user with the
  cross-department edit permission.
- **FR-14** Editing a project whose transaction month is already closed requires the bypass permission.
- **FR-15** Delete is permissioned and behind a confirmation dialog. An approved project cannot be deleted.

### 3.4 Project approval (new — D5)

Legacy has no approval of the project itself: whoever could reach the page could register a project, and it
counted from that moment. The CI coordinator has no gate between "a department typed something in" and "this
is a CI project of record".

- **FR-30** Every project carries an approval status: `Draft`, `Approved` or `Amend`.
- **FR-31** A project is created as `Draft`. It appears in the list, marked as such, and stays fully usable —
  its department can edit it and record monthly savings against it while it waits.
- **FR-32** `Draft → Approved` is an explicit, permissioned action. It records who approved and when.
- **FR-33** An approved project is **not edited in place**. To change it, an authorised user moves it to
  `Amend` — with a reason — which makes it editable again; approving it afterwards returns it to `Approved`.
- **FR-34** Approval status never blocks saving input. A project takes monthly savings in every state; what
  the workflow controls is who may change the project's own attributes, and when.
- **FR-35** Every transition is written to the activity log with actor, timestamp and reason.

### 3.5 Saving detail

- **FR-16** A per-project modal showing savings as year rows × 12 month columns, with a per-year total and a
  grand total.

### 3.6 Period approval (rebuilt — see `spec.md` §2)

- **FR-17** Each saving month is a record with a status: `Draft` → `Approved`, plus `Reopened`.
- **FR-18** `Approved` locks that month's saving input for everyone without the bypass permission.
- **FR-19** Reopening an approved month is an explicit action, separately permissioned, requiring a remark,
  and written to the activity log. Legacy achieves the same by silently overwriting a date.
- **FR-20** Every transition records who did it and when.
- **FR-21** A dedicated period page lists the months with their status and the transition actions.

### 3.7 Exports

- **FR-22** Horizontal export: one row per project, saving columns grouped by year.
- **FR-23** Vertical export: one row per project × month.
- **FR-24** Pivot export: saving type / frequency / department × year × month, 2021 → current year.
  Requires `view_all_scope`.
- **FR-25** All three respect the same filters and department scope as the screen.
- **FR-26** Exports run as queued jobs and notify the user with a download link (project standard).

### 3.8 Masters — its own page (D6)

Every dropdown on the report page is a `ci_project_master` row, and today the only way to add a group, a
status or a department is a manual `INSERT`. It gets a real page.

- **FR-27** A maintenance page for `ci_project_master`, listing rows grouped by type, with create, edit and
  delete. Types offered: `GROUP`, `STATUS`, `IMPROVE`, `IMPACT1`, `IMPACT2`, `IMPACT3`, `FREQUENCY`, `DEPT`,
  `SAV_TYPE`, `TRANS_CODE`. The `ADMIN`, `CAN_VIEW_ALL` and `APPROVE` types are not offered — they are not
  access control any more (D3, D4).
- **FR-28** The master cache invalidates itself on write. No manual "clear cache" button.
- **FR-36** `(type, code)` is the identity. The page refuses a duplicate pair, and refuses to change the
  `code` of a row that projects already reference — renaming a code silently orphans every project holding it.
- **FR-37** Deleting a row that is in use is refused, naming how many projects use it. Search and filter by
  type; the list is paginated.

### 3.9 Auto-finish

- **FR-29** A scheduled command flips `ACTIVE` projects to `FINISH` once `cpm_saving_end` is in the past,
  as one mass update, logged to its own channel and to the activity log.

## 4. Non-functional

- Oracle via `yajra/laravel-oci8`, `oracle_mgtapps` connection.
- The legacy app keeps running on the same tables until cut-over, so nothing may break its reads.
- Page must stay responsive with the full project set — hence pagination and an index on the saving join.
- Flux / UI-module components only; no Bootstrap, no jQuery, no `wire:navigate`.

## 5. Scope

**In scope:** the report page, saving input, create/edit/delete, the project approval workflow, saving
detail, the period approval page, the three exports, the master maintenance page, the auto-finish command,
permissions and seeding.

**Out of scope (explicit non-goals):**

- `CiProjectExportPivot` (v1) — superseded by `Pivot2`, not ported.
- Any change to how `cpm_sys_id` / `cpm_trans_no` are generated. The Oracle trigger `CI_PROJECT_MAIN_TRG1`
  keeps doing it.
- Migrating project or saving rows. They stay where they are (D2).
- A CI dashboard / charts. Possible later, not now.

## 6. Success criteria

1. Every number the legacy page shows for a given month and filter set is identical on the new page.
2. All three exports produce the same figures as legacy for the same filters.
3. No user's access depends on a `ci_project_master` row any more.
4. An approved month cannot be edited by anyone lacking the bypass permission, and every reopen is
   attributable in the activity log.
5. Saving the same project and month twice never produces two `ci_project_saving` rows.
6. An approved project cannot be edited without first being amended — refused at the service, not only in
   the view — while savings input works the same in every approval state.
7. Every existing project is `Approved` on day one; nobody has to re-approve history.
8. A group, status or department can be added through the UI, with no `INSERT` and no cache button.
9. Pest coverage for: the lock gate, the department scope, the saving upsert, the 20-month rule, the period
   state machine, and the project state machine.
