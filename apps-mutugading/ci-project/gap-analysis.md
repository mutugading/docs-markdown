# gap-analysis.md — legacy → new

## 1. File mapping

| Legacy | New | Note |
|---|---|---|
| `App\Http\Livewire\CiProject\CiProjectReport` | `Modules\Finance\Livewire\Transaction\CiProject\CiProjectReport` + `CiProjectPeriodManager` + `CiProjectMasterManager` | 738 lines split across 3 components, 5 services, 4 repositories |
| `resources/views/livewire/ci-project/ci-project-report.blade.php` | `…/livewire/transaction/ci-project/ci-project-report.blade.php` + 4 partials | Bootstrap/AdminLTE → Flux + UI module |
| `App\Models\CiProject\CiProjectMains` | `…\Models\MgtApps\Transaction\CiProject\CiProjectMain` | Same table |
| `…\CiProjectSavings` | `…\CiProjectSaving` | Same table |
| `…\CiProjectMasters` | `…\CiProjectMaster` + `EloquentCiProjectMasterRepository` | Static cached accessors become repository methods |
| — | `…\CiProjectPeriod` | New (D3) |
| `App\Services\CiProject\CiProjectService::applyFilterScope()` | `EloquentCiProjectRepository::reportQuery()` | Scope moves into the repository; the shared-with-exports property is kept |
| `App\Exports\CiProject\CiProjectExportHor` | `…\Exports\Transaction\CiProject\CiProjectHorizontalExport` | |
| `…\CiProjectExportVer` | `…\CiProjectVerticalExport` | |
| `…\CiProjectExportPivot2` | `…\CiProjectPivotExport` | |
| `…\CiProjectExportPivot` | — | Dropped, superseded |
| `App\Console\Commands\CiProjectAutoFinish` | `Modules\Finance\Console\Commands\CiProjectAutoFinishCommand` | Loop → mass update |
| `routes/web.php:529` `apps.ci.project.list` | `dashboard.module-finance.transaction.ci-project` | Legacy URL not preserved |

## 2. Method-by-method

| Legacy method | Where it goes |
|---|---|
| `mount()` / `getUser()` | `mount()` — but the manual `Auth::login()` and `session('USER_NAME')` disappear; this app is already authenticated as `HmEmpData` |
| `setOrder()` | Component, unchanged |
| `getProjectList()` | `EloquentCiProjectRepository::paginateReport()` |
| `validateData()` | `CiProjectData` rules + `CiProjectService` for the permission-dependent ceiling |
| `saveProject()` / `saveEdit()` | `CiProjectService::create()` / `::update()` |
| `editProject()` | Component `edit()`, now with the authorization check |
| `updatedNewSaving()` / `saveSaving()` | `CiProjectSavingService::store()`, per row |
| `detailProject()` | `CiProjectSavingRepository::savingsByYear()` |
| `confirmApprove()` / `approveSaving()` | `CiProjectPeriodService::transition()` on the period page |
| `exportExcel{Hor,Ver,Pivot}()` | `ExportCiProject` job + the three export classes |
| `clearFilters()` | Component, unchanged |
| `delProject()` / `confirmDelProject()` | One `delete()` behind `<x-ui::modal.confirmation>` |
| `closeModal()` | Flux modal state |
| `deleteCache()` | Gone — the master repository invalidates on write (FR-28) |
| `getFilteredDepartments()` | Repository/service, driven by the scope permission |
| `getFilteredGroups()` | `CiProjectSavingTypeEnum::allowedGroupCodes()` |
| `isSuperAdmin()` / `canModify()` | `CiProjectAuthorizationService` |

Three pieces have no legacy counterpart at all: `CiProjectApprovalService` and the approval column of the
report page (D5), and `CiProjectMasterManager` (D6) — legacy maintained `ci_project_master` by hand.

## 3. Parity checklist

Every legacy behaviour, and whether it survives.

| # | Behaviour | Status |
|---|---|---|
| 1 | Three rolling month columns + inline input for the selected month | kept |
| 2 | Month defaults to last month, overridable by query string | kept |
| 3 | Status-weighted ordering + trans-no direction toggle | kept |
| 4 | Dept / group / status filters + free-text search + clear | kept (search narrowed, `spec.md` §1.3) |
| 5 | Own-department restriction | kept, moved into the repository |
| 6 | Add / edit project with the full field set | kept |
| 7 | Group options filtered by saving type | kept, moved to the enum |
| 8 | Completion / saving-end validation incl. the 20-month ceiling | kept, bypass is now a permission |
| 9 | `saving_end` auto-suggested at completion + 18 months | kept, Alpine instead of jQuery |
| 10 | Title uniqueness | kept **and extended to edit** |
| 11 | Saving detail modal, year × month + totals | kept, opens even when empty |
| 12 | Approve month | **rebuilt** (D3) |
| 13 | Delete project | kept, permission instead of hard-coded role |
| 14 | Three exports | kept, queued |
| 15 | Manual cache-clear button | **dropped** — automatic |
| 15b | Master rows maintained by hand-written `INSERT` | **replaced** by a maintenance page (D6) |
| 16 | Enter-moves-to-next-input in the saving column | kept, Alpine |
| 17 | Auto-finish command | kept, mass update |
| 18 | `ADMIN` / `CAN_VIEW_ALL` master rows | **dropped** (D4) |
| 19 | Legacy `/Apps/CI/Project-List` URL | dropped |
| 20 | `CiProjectExportPivot` v1 | dropped |
| 21 | *(no legacy equivalent)* Project approval `Draft / Approved / Amend` — an edit gate, not a gate on saving input | **new** (D5) |

## 4. Legacy defects this migration fixes

| # | Defect | Fix |
|---|---|---|
| **B1** | Duplicate `(project, month)` saving rows multiply the project in the report, because the list left-joins savings three times | Unique index (`schema.md` §2.1) + a single upsert path |
| **B2** | The saving input's read-only state is a Blade `readonly` attribute; `saveSaving()` re-checks the lock but not the department, so a crafted request could write another department's saving | Every write goes through `CiProjectAuthorizationService`; the view check is cosmetic |
| **B3** | `editProject()` loads any project by client-supplied id without an authorization check — the refusal only arrives at save time | Check on open and on save |
| **B4** | Title uniqueness is create-only; an edit can duplicate a title | Checked on both, plus a DB index |
| **B5** | The delete confirmation builds JavaScript by string interpolation of a client-supplied id | Flux confirmation modal; no generated script |
| **B6** | Moving the approve date backwards silently reopens every month above it, with no record | Explicit per-month reopen with a remark and an activity entry |
| **B7** | One `$admin` flag conflates five unrelated privileges | Five permissions (`design.md` §7.1) |
| **B8** | An employee with no department is met with `abort(401)` | Empty state with an explanation |
| **B9** | Free-text search runs `UPPER(col) LIKE` over date and numeric columns | Text columns only |
| **B10** | The list loads every project row, unpaginated | Pagination |
| **B11** | Exports build synchronously inside the request; the pivot scans every saving row since 2021 | Queued job + notification |
| **B12** | `getApproveDate()` dereferences `->first()` without a null check — no `APPROVE` row is a fatal error | Missing period row means "open" |
| **B13** | The master cache is cleared by a button a user has to remember to press | Invalidated on write |
| **B14** | An approved project's department, saving type or saving-end date can be changed at any time with no trace, silently changing what past months of savings mean | The project workflow (D5): an approved project must be amended, with a reason, before it can change, and both transitions are logged |
| **B15** | A group, status or department can only be added by an `INSERT` into `ci_project_master`, and a `code` can be changed there with nothing to stop it orphaning every project that holds the old string | The master page (D6): `(type, code)` identity enforced, `code` frozen once in use, deletion of a row in use refused |

## 5. Behaviour changes users will notice

Worth naming in the release note and in the hand-over to the CI coordinator:

0. **A project now goes through approval, and an approved one can no longer be edited directly.** A
   department registers it as a draft and works with it normally — editing it and recording savings — until
   someone with the approve permission accepts it. From then on, changing it means amending it first, with a
   reason that everyone can see. Nothing is blocked while a project waits for approval; what changes is that
   an approved project stops being quietly editable.
1. **Approving a month is now a page, not a button.** Each month is a record with a status and a history.
2. **Reopening a closed month needs a reason** and is visible to everyone afterwards.
3. **Access no longer comes from the master table.** Someone must assign the new roles (`tasks.md` T18)
   before cut-over, or today's admins arrive with no access.
4. **The list is paged.** Anyone used to Ctrl-F over the whole table needs the export instead.
5. **Exports arrive as a notification with a link**, not as an immediate download.
6. **Delete is a permission**, so it can be granted to someone who is not Super Admin — and conversely a
   Super Admin who should not delete can have it withheld. It now only applies to drafts.
7. **Dropdown values are maintained in the app**, by anyone holding `master-manage` — no more asking IT for
   an `INSERT`.

## 6. Audit results — dev schema `ALTHARA` / `MGTAPPS`, 2026-08-31

Run with `data_audit.sql` (tasks.md T01, T02). Oracle Database 11g Release 11.2.0.4.

**The data is clean. Both indexes can be created, and no clean-up script is needed.**

| Check | Result |
|---|---|
| Projects / savings | 281 projects, 3,074 saving rows, months 2021-01 → 2026-07 (67 distinct) |
| **Q1** Duplicate `(cps_cpm_sys_id, cps_date)` | **None.** `add_ci_project_saving_unique_index` applies cleanly; no `saving_duplicates_cleanup.sql` needed |
| `cps_date <> TRUNC(cps_date,'MM')` | **0 rows.** Every saving date is a first-of-month at midnight, so the exact-equality join is safe |
| **Q2 / D8** Case-differing duplicate titles | **None.** The functional unique index on `UPPER(cpm_title)` **will be created** — `add_ci_project_main_indexes` does it automatically now that the check passes |
| Project values with no master row | **None.** Every `cpm_group` / `cpm_status` / `cpm_sav_type` / `cpm_frequency` has its master row |
| Legacy `APPROVE` cut-off | `code = 2026-06-01`, `name = 2026-07-09 10:21:55`. The backfill produces **68 period rows: 66 Approved (2021-01 → 2026-06), 2 Draft (2026-07, 2026-08)** |
| Statuses in use | `FINISH` 194, `ACTIVE` 71, `INDIRECT-SAVING` 16 — exactly the three `CiProjectStatusEnum` knows |
| Saving types / groups in use | `LSS`→GKM, LSS BB, LSS GB · `OTH`/`OTH_OT`→Dept.Idea, KAIZEN · `PRO_INC`→PRO.INC · `PRO_SAV`→PRO.SAV. The `allowedGroupCodes()` rule matches the data exactly |
| `TRANS_CODE` coverage | All 7 groups have a row; `name` → group code, `code` → prefix (`Dept.Idea` → `DEPIDE`) |

### 6.1 Q6 — one department code does not match: `HSES`

Six projects carry `cpm_dept = 'HSES'`. **No employee has that department.** The HR department is
`HSS` — *Health Safety Environment and Security*, 31 active employees. Every other project department
(`MB`, `MC`, `SPG`, `TPME`, `TPMU`, `TQM`, `TWT`, `TXT`) matches an employee department exactly.

So those six projects are invisible to their own department under the department scope. **This is not a
regression** — legacy compared the same two columns, so HSS staff could never see them there either
unless they were an `ADMIN` or `CAN_VIEW_ALL` user.

| sys id | Trans no | Status |
|---|---|---|
| 217 | DEPIDE-2023000068 | FINISH |
| 218 | DEPIDE-2023000069 | FINISH |
| 219 | DEPIDE-2023000070 | FINISH |
| 229 | DEPIDE-2025000053 | ACTIVE |
| 230 | DEPIDE-2025000054 | ACTIVE |
| 252 | GKM-2026000076 | INDIRECT-SAVING |

Recommended fix, as a reviewed data change before cut-over — not automated, and **needs a decision**:

```sql
UPDATE ci_project_main  SET cpm_dept = 'HSS' WHERE cpm_dept = 'HSES';   -- 6 rows
UPDATE ci_project_master SET code    = 'HSS', name = 'HSS'
 WHERE type = 'DEPT' AND code = 'HSES';                                  -- 1 row
```

Note the second statement is exactly the rename the master page refuses once a code is in use — which is
the point of that rule. Run them together, in this order, or the projects orphan.

The alternative is to leave it and give the HSS PIC `finance-ci_project-view_all_scope`, which also
grants sight of every other department.

### 6.2 T18 — who holds the legacy access rows

| Legacy row | User id | Employee | Dept | New role |
|---|---|---|---|---|
| `ADMIN` | 0686 | Indra Cahyaadhi Putro | ITS | CI Project - Admin |
| `ADMIN` | 2050 | Dedy Kwartono | CIM | CI Project - Admin |
| `ADMIN` | 2443 | Mickel Dohan | ITS | CI Project - Admin |
| `ADMIN` | MANHAR | Manhar Shyam Baraja | FIN | CI Project - Admin |
| `ADMIN` | Pabasker | Arjunan Baskar | FIN | **inactive** (`hmemd_sts_data = 1`) — confirm before granting |
| `CAN_VIEW_ALL` | 1943 | Muhammad A Aziz Henditama | FIN | CI Project - Viewer |
| `CAN_VIEW_ALL` | 2050 | Dedy Kwartono | CIM | already Admin above |

Four active people would hold `finance-ci_project-approve`, which satisfies the "at least two approvers"
requirement. Note none of them sits in a department that runs projects except CIM — the department PICs
still need `CI Project - Department` assigning separately, and that list has to come from the coordinator.

### 6.3 Report query verified against the real schema

`reportQuery()` was run read-only against `ALTHARA`. For 2026-04, 2026-05 and 2026-06 the three-month
join totals match a direct `SUM(cps_saving)` on `ci_project_saving` to the cent
(−16,096.65 / 59,239.42 / 63,879.51), pagination compiles correctly on 11g, and the search, filters,
`savingsByYear()` grouping and pivot aggregation all return sensible figures. That is PRD success
criterion 1 confirmed on real data before deploy.

## 7. Open questions

| # | Question | Default if unanswered |
|---|---|---|
| **Q1** | ~~Does any legacy `(cps_cpm_sys_id, cps_date)` duplicate exist?~~ | **Answered §6: none on dev. No clean-up needed** |
| **Q2** | ~~Are there case-differing duplicate titles today?~~ | **Answered §6: none on dev. The index is created** |
| **Q3** | How long do both apps run against these tables in parallel? That is exactly how long the legacy dual-write must live | Keep the dual-write until someone says the old page is off |
| **Q4** | Should approval eventually be per department (the option not taken)? | Global; the table is shaped so a `cpp_dept` column is a contained change (`schema.md` §4.1) |
| **Q5** | Is clearing an already-saved month's amount ever needed? | Not in phase 1; empty input is a no-op |
| **Q6** | ~~Do the `DEPT` master codes line up with `hmemd_hmdep_dept_id`?~~ | **Answered §6.1: all but `HSES` (6 projects), which should be `HSS`. Needs a decision** |
| **Q7** | Should the period page also show per-department completeness (which departments have entered nothing this month)? | Out of phase 1; likely the first enhancement asked for |
| **Q8** | Who approves projects — the CI coordinator alone, or a department head first? A second approval stage is a fourth state, not a tweak | The coordinator, single stage. Settle before T11 |
| **Q9** | Should approving a project notify the department that raised it, and amending notify them too? | Out of phase 1. The activity log records both; a `BaseNotification` is a small follow-up if asked for |
| **Q10** | Are there projects registered in the legacy app during cut-over that ought to have gone through approval? | They land as `Approved` (`schema.md` §1.1) — unavoidable while both apps run, and the reason cut-over should be short |
| **Q11** | Should savings on projects that are still `Draft` be excluded from the pivot / management totals? | No — they are included, with an approval column so a reader can see them. Excluding them makes numbers vanish silently (`spec.md` §2A.5) |
