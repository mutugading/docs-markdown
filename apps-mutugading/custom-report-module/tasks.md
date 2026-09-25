# tasks.md — Phase 1 task list

Dependency-ordered. Each task names what "done" means. Acceptance-criteria numbers refer to
`PRD/PRD_Custom_Report_Module_Phase1.md` §10.

`vendor/bin/pint --dirty` before every commit. Conventional Commits, scope `report` (add it to the
allowed scope list in `CLAUDE.md`/`CONTRIBUTING.md` if not already there).

---

## Progress log

**Status as of 2026-09-15: T01–T31 done, T31 VERIFIED against real Oracle, T04 dropped, T33-T39 added
and done. T32 (UAT) is the only task left and it needs people, not code.**

Committed, on its own dedicated feature branch (not merged, not pushed to remote — pushing/PR is
manual, done by the user from GitHub, per their own request):

- Branch: `feat/custom-report-module`, based on `origin/develop` (not on `feat/docker-dev-environment`
  — that branch is a separate, unrelated feature, so this one was rebuilt on `develop` directly to keep
  "one feature = one branch = one PR" per the root `CLAUDE.md`)
- Working checkout: `/home/indraputro/apps-mutugading` (the branch is checked out in the main repo —
  the scratch worktrees the earlier runs used, `.claude/worktrees/feat-custom-report-module` and
  `worktree-agent-a1178b86a6c86686f`, have both been deleted after their content was copied onto this
  branch). Tests/Pint run inside the `apps-mutugading-app-1` docker container (PHP 8.3), which is
  where `vendor/` actually lives — the host `vendor/` directory is an empty mount point.
- Commits so far (Conventional Commits, scope `report`):
  1. `feat(report): scaffold Custom Report Module foundation (P0+P1)` — T01–T12
  2. `feat(report): add SqlGuard and ReportQueryService query engine (P2)` — T13–T16
  3. `refactor(report): run report queries on oracle_mgthris, drop rpt_readonly` — T03/T04 reversal
  4. `feat(report): add Admin UI for report groups, definitions and parameters (P3)` — T17–T19
  5. `feat(report): add end-user report catalog and viewer (P4)` — T20–T22
  6. `feat(report): add client-side result table and Excel export (P5)` — T23–T26
  7. `feat(report): register the Custom Report Module in the sidebar menu` — T28
  8. `fix(report): remove ON DELETE RESTRICT from the Oracle FK (ORA-00905)`
  9. `refactor(report): drop the Developer role, gate Admin UI to Super Admin`
  (T27 and T29 landed inside the P3/P4 and P2 commits respectively — the routes were gated and the
  `report_execution` log channel added at the point each was first needed, rather than deferred to a
  separate P6 commit.)
- **Current suite state: 252 passed (619 assertions), 8 skipped, 0 failed** (`php artisan test
  --parallel`). The 8 skips are T31's Oracle integration tests, which skip themselves whenever their
  opt-in environment variables are absent — see the T31 note below. Pint clean on every file touched.

P0–P2 were built by two Claude Code subagent runs (general-purpose agent, see the repo's Claude Code
session — not a durable artifact, mentioned here only for provenance):

| Run | Scope | Result |
|---|---|---|
| 1 | T01–T12 (P0 Setup + P1 Foundation) | 23 tests passed, 34 files Pint-clean |
| 2 | T13–T16 (P2 Query engine) | full suite 156 tests passed (322 assertions), 43 files Pint-clean |

Both runs were independently re-verified (tests + Pint re-run, key files read) rather than taken on
the agents' own word — see the deviations below, several of which were caught this way. The
T03/T04 reversal, P3–P6, and P7's T30/T31 were done directly, not via a subagent.

**Deviations from this file, discovered during implementation (all intentional, documented in code):**

- **T05** — `RD_CREATED_BY`'s type in PRD §6.2 (`NUMBER(10)`) doesn't match its actual FK target: the
  confirmed target `HmEmpData::hmemd_sys_id` is a non-incrementing `VARCHAR2(30)`, not numeric.
  Migration uses `VARCHAR2(30)` instead — documented in `Modules/Report/CLAUDE.md` and the migration's
  own docblock.
- **T05, follow-up** — the Oracle branch's `RD_GROUP_ID` FK cannot carry `ON DELETE RESTRICT` (Oracle
  has no such clause — `ORA-00905: missing keyword`). Removed; restrict is Oracle's *default* FK
  behaviour anyway, and FR-1.3 is enforced up-front in `ReportGroupManager::delete()` regardless, so
  nothing was lost. See commit `fix(report): remove ON DELETE RESTRICT from the Oracle FK`.
- **T09/T12** — the seeder deliberately imports `Modules\Auth\Models\RolePermission\{Role,Permission}`
  (this app's configured Spatie models) rather than the base `Spatie\Permission\Models\*` classes, so
  permission rows land on the `oracle_mgthris` connection where this app's permission tables actually
  live, not whatever the *default* connection happens to be.
- **T12/T27, changed 2026-09-14 — the `Developer` role was dropped entirely.** PRD §4 designs a
  separate `Developer` role as the only one allowed to write report SQL; during manual trial the team
  decided to reuse this app's existing `Super Admin` role instead rather than maintain a second
  Report-specific one. The Admin UI routes are gated `role:Super Admin`. **Treat every `Developer`
  reference remaining in the PRD, `plan.md` and this file as superseded by this note** —
  `Modules/Report/CLAUDE.md` carries the same warning.
- **T14** — the multi-select "nothing selected" sentinel (left open by the PRD) is the literal string
  `'-999999999'` — safe as a bind value against both a `VARCHAR2` column (just an ordinary, never-real
  value) and a `NUMBER` column (Oracle coerces it instead of raising `ORA-01722`). T31 now contains
  the assertion for this against real Oracle; until that suite is actually run it stays a claim.
- **T15** — timeout enforcement uses `Yajra\Pdo\Oci8::setCallTimeout()` (already vendored, wraps
  `oci_set_call_timeout()`), found by searching the codebase rather than invented. It no-ops on every
  driver except real Oracle, so **whether a slow query is actually killed at `timeout_sec` is still
  unproven** — that requires running T31 against a real test schema, which this environment doesn't
  have.
- **Not in this file originally** — `ReportQueryExecutorInterface` +
  `ConnectionReportQueryExecutor` were added so `ReportQueryService` could be unit-tested against
  SQLite/fakes in this repo's CI-only environment.
- **T03/T04, reversed 2026-09-14** — see `plan.md` §2/§6 item 3. Report queries run on the app's
  normal `oracle_mgthris` connection, not a separate SELECT-only `oracle_readonly` one. **Accepted
  risk: `SqlGuard` (T13) is now the only thing preventing a report query from writing to production
  data** — there is no database-level backstop if it's ever bypassed.
- **T24** — the client-side table is Report-module-owned
  (`resources/views/livewire/partials/_result-table.blade.php` + `resources/js/components/
  report-result-table.js`), **not** a new mode bolted onto `Modules/UI`'s shared `<x-ui::table>` as
  `plan.md` §2/§6 item 6 proposed. Reason: `<x-ui::table>` is used across many other modules and the
  two modes share almost nothing (server-paginated Livewire vs. full-dataset Alpine); the risk
  `plan.md` itself flagged — "couples two unrelated concerns into one component and destabilizes its
  existing usages" — was judged the larger one. The partial still reuses
  `<x-ui::table.td>`/`<x-ui::table.tr>` for styling, so there is no visual drift.
- **T30** — lives in `tests/Feature/Report/AcceptanceCriteriaTest.php` (repo-level `tests/`, where
  every other Report test already sits — `Modules/Report/tests/` holds only `.gitkeep` files), with
  one test per criterion named `AC-{n}: ...`. It re-proves each criterion end to end through the real
  Livewire components; the per-task files (T13–T26) keep testing their own unit in isolation. Two
  honest limits, stated in the file rather than papered over: **AC-7's "no extra server request" is
  asserted structurally** (the whole dataset is serialized into the Alpine component and the search/
  sort/paginate controls carry no `wire:` directive) because this repo has no browser-test runner
  wired up; and nothing Oracle-specific is provable there.
- **T31** — needed infrastructure this file didn't anticipate. `tests/TestCase.php` refuses to boot if
  *any* connection resolves to a non-sqlite driver, so an Oracle test cannot simply use
  `oracle_mgthris`. Three things were added: a `tests/Integration/` directory, an `Integration`
  testsuite in `phpunit.xml`, and a `pest()->extend(...)->in('Integration')` line (no
  `RefreshDatabase` — these tests must never migrate or write on the schema they reach). The test
  registers its own separately-named connection (`report_oracle_integration`) after boot, only when
  the opt-in variables are set, so the sqlite guard is not weakened. Every query it runs is a read
  against `dual` — it creates nothing on the target schema.

  Run it with `php artisan test --testsuite=Integration` and:
  `REPORT_ORACLE_IT_USERNAME` (its presence is the opt-in), `REPORT_ORACLE_IT_PASSWORD`,
  `REPORT_ORACLE_IT_HOST` (or `REPORT_ORACLE_IT_TNS`), `REPORT_ORACLE_IT_PORT`,
  `REPORT_ORACLE_IT_SERVICE_NAME` (or `REPORT_ORACLE_IT_SID`). Without them all 8 tests skip, which
  is why CI stays green.

**T31 VERIFIED against real Oracle — 2026-09-15:**

An earlier version of this log said T31 could not be run here "no Oracle, no `oci8` extension". That
was wrong: the `apps-mutugading-app-1` container has `oci8` and a live connection. Run against
`althara` (192.168.0.7:1521, user `mgthris`): **8 passed, 19 assertions, 5.2s.** Everything the module
had only asserted is now observed:

| Behaviour | Verdict |
|---|---|
| `ROWNUM` wrap caps a 50-row query at `max_rows` | ✅ |
| Inner `ORDER BY` survives the wrap (top-N is the right N) | ✅ |
| `truncated` false when the result fits under the cap | ✅ |
| Multi-select `IN` expansion via OCI named binds | ✅ |
| `'-999999999'` sentinel vs. a `NUMBER` column — no `ORA-01722` | ✅ the T14 deviation is sound |
| Same sentinel vs. a `VARCHAR2` column | ✅ |
| `date_range` two-bind split under implicit date conversion | ✅ |
| `timeout_sec` genuinely kills a slow query | ✅ |

The timeout case is the one that mattered most: the ~10^10-row query died after 4.1s against a 2s
budget, and `storage/logs/report_execution-*.log` names the real cause — `ORA-03156: OCI call timed
out`. So `oci_set_call_timeout()` exists on this client (12.1+), `applyTimeout()` is not a no-op, and
`RD_TIMEOUT_SEC` is really enforced. That same log line doubles as live proof of NFR-1.4: the `ORA-`
detail stayed in the log while the caller saw only the generic message.

**Gotcha when re-running:** `REPORT_ORACLE_IT_*` must be real *shell* variables. `.env` is read by
Laravel, not exported to the shell, so `REPORT_ORACLE_IT_HOST="$DB_HOST"` resolves to an empty string
and all 8 tests skip while appearing to run. Parse `.env` explicitly.

**Still genuinely open (not just "not built yet"):**

- The `< 12.1` Oracle client caveat remains a **portability** note, not a live problem: on an older
  client `oci_set_call_timeout()` doesn't exist, `applyTimeout()` silently no-ops, and `timeout_sec`
  is not enforced at all. The test self-skips there rather than passing misleadingly — so a green run
  on a different host only counts if that row says ✅, not "skipped".
- Whether the `SqlGuard`-only protection (above) needs revisiting once Admin UI access is opened
  beyond a small trusted `Super Admin` group.
- Nothing in this module has been verified in a real browser — there is no browser-test runner wired
  up in this repo. The manual trial on 2026-09-15 caught two things the entire test suite had missed
  for exactly this reason: the teleported-dropdown drawer bug (see below) and the sidebar menu cache.

**Test-environment fidelity bug found 2026-09-15 (repo-wide, not Report-specific):**

`tests/TestCase.php` now forces `PDO::ATTR_CASE => PDO::CASE_LOWER` on every sqlite connection,
mirroring what `yajra/laravel-oci8`'s connector does in production. Without it, SQLite echoed back the
casing each migration declared — and several migrations declare columns in UPPERCASE (notably
`HM_EMP_DATA`'s `HMEMD_SYS_ID`). The consequence was quiet and nasty: a model **re-fetched** from such
a table under SQLite carried `HMEMD_SYS_ID`, so `$model->getKey()` returned `null` even though the row
had loaded fine, and anything keyed off it (a morph pivot write, a relation query) silently did
nothing — in tests only. Production, on Oracle, was never affected. Found while building T33, whose
service looks employees up by id; four of its tests failed in a way that looked like a feature bug and
wasn't. Worth knowing before writing any test that re-fetches an Hr model.

**Tests reached real object storage — found and fixed 2026-09-15:**

Eight orphaned `.xlsx` objects turned up in the shared dev MinIO bucket, written by the test suite.
Cause: `QUEUE_CONNECTION` is `sync` under phpunit, so a test that dispatches a `ShouldQueue` job
without `Bus::fake()` executes it **inline** — and `RunReportJob` writes to `minio_private`. One
Report test was doing exactly that while failing for an unrelated reason. The objects were deleted
and `tests/TestCase.php` now rewrites every `s3` disk to local scratch space for the whole test run,
the same way it already guards against real databases. `Storage::fake()` in an individual test still
works and is still clearer; this only makes *forgetting* it harmless.

**Environment blocker found and fixed 2026-09-15 — the queue could not dispatch at all:**

`.env` had `DB_QUEUE_TABLE=jobs_v2` and `DB_QUEUE_FAILED_TABLE=failed_jobs_v2`, but neither table
exists in the schema — only `jobs` and `failed_jobs` do — and **no migration in this repo creates
them**. Every `ShouldQueue` dispatch failed with `ORA-00942` on insert, so this was never specific to
the Report module: **every export job in the app was equally broken in this environment.** Pointed at
`jobs`/`failed_jobs` per the team's call — which is what `.env.example` has always said, so the `_v2`
values were a local drift. Verified with a real worker: dispatch 0.026s, run completes in 38s,
notification delivered.

⚠️ **`.env` is not in version control, so this fix does not travel with the repo.** Any other
environment showing `ORA-00942` on a queue insert needs the same one-line change.

⚠️ **And the `.env` change alone is not enough on this dev box.** The app container runs
`php artisan serve --no-reload`, which bakes `.env` into the `php -S` worker processes at boot;
Laravel's Dotenv will not overwrite a variable already in the process environment, so the browser kept
inserting into `JOBS_V2` long after `.env` said `jobs` and `config:clear` had been run repeatedly (no
cached config file existed at all). Confirmed by reading `/proc/<pid>/environ`. **Restart the app
container after any `.env` change** — or drop `--no-reload`, which is precisely what disables
`serve`'s own restart-on-env-change. Note this also means verification driven from `artisan tinker`
cannot catch it: a fresh process reads `.env` correctly every time.

**Manual-trial findings, 2026-09-15 (not caught by any test):**

- **The Group select closed the whole definition drawer.** `<x-ui::form.select>` teleports its option
  panel into `<body>`, so clicking an option counted as a click outside the drawer and
  `x-on:click.outside` fired — making a report definition impossible to create. Fixed with
  `:closeOnOutside="false"` (commit `fix(report): stop the definition drawer closing when a group is
  picked`), pinned by a structural regression test. `<x-ui::drawer>`'s own docblock had documented
  this trap all along; the drawer was simply missing the prop.
- **`ReportMenuSeeder` had never been run on `althara`** — `cm_menus` had no report rows, so the
  sidebar had no "Reports" link at all even though the routes worked. Seeded 2026-09-15; the three
  items now sit under Core. Remember `MenuTreeService` caches per user for 600s.
- **The first real report query failed twice, for two unrelated reasons**, both found by running it
  against Oracle rather than by reading it: (1) `ORA-00942` — four of its five tables live in the
  `MGTDAT` schema with no synonyms, so they need an explicit `MGTDAT.` prefix; `MGTHRIS` does hold
  SELECT grants on them. (2) `ORA-00932: expected CHAR got DATE` — a `date` parameter binds as a
  **string**, so comparing `:p_date` straight against a DATE column fails; the query must wrap it,
  `TO_DATE(:p_date, 'YYYY-MM-DD')` (the datepicker sends `Y-m-d`). Neither is a module bug, but both
  are traps every report author will hit, so they belong in whatever authoring guide ships with this.
- ~~**Column aliases come back lowercased.**~~ **FIXED, T34.** `yajra/laravel-oci8`'s connector
  hardcodes `PDO::ATTR_CASE => PDO::CASE_LOWER`, so a carefully quoted `"Item Group"` rendered as
  `item group` in both the web table and the Excel export. Fixed by giving report execution its own
  `oracle_report` connection with `CASE_NATURAL` — **not** by changing `oracle_mgthris`, which the
  whole app relies on being lowercase. Verified on the live DB: all 18 headers of the pilot report now
  come back exactly as written.
- **The default `timeout_sec` of 30 is too low for a real analytical query, and `max_rows` of 1000
  silently truncated 77% of the output.** The pilot report needs ~34s and produces 4,357 rows; it kept
  failing with `ORA-03156: OCI call timed out` until `timeout_sec` was raised to 120 and `max_rows`
  to 5000. Key insight: **`max_rows` does not make a slow report faster** — the `ROWNUM` wrap caps
  what is transferred, but the `GROUP BY` runs over everything first. It took ~34s whether capped at
  5 rows or 5,000. Also worth noting `ReportViewer::MAX_DOWNLOAD_ROWS` is 5,000 and this report is
  already at 4,357 — once output crosses that, the Excel download aborts with a 400 rather than
  truncating. Full measurements in `Modules/Report/CLAUDE.md`.
- **A report left at `draft` is invisible everywhere** (catalog filters to `active`, `ReportViewer`
  404s) and the auto-created `report.view.{id}` permission starts assigned to nobody. Both are by
  design, but together they make "I built a report and nothing happened" the expected first
  experience — worth saying out loud in whatever user-facing note accompanies rollout.

---

## P0 — Setup (blocks everything)

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T01** | Resolve `plan.md` §6 open questions 1–6 with the team | — | Written answers for module placement, DB connection, `rpt_readonly` ownership/timeline, `RD_CREATED_BY` target, `Developer` role, and client-side table approach |
| ✅ **T02** | `php artisan module:make Report`; wire `module.json` (`requires: ["Core"]`), `RouteServiceProvider`, empty `routes/web.php` + `routes/breadcrumbs.php` | T01 | Module appears in `php artisan module:list`, boots with no routes yet |
| ~~T03~~ | ~~Add `oracle_readonly` connection block...~~ | — | **Superseded 2026-09-14** — this connection was built (T03 done as originally written) then removed again the same day once T04 was dropped; see the `refactor(report)` commit removing `config/database.php`'s `oracle_readonly` block. Report queries use `oracle_mgthris` |
| ~~T04~~ | ~~Open DBA ticket: create Oracle user `rpt_readonly`...~~ | — | **Dropped 2026-09-14** — team decided to run report queries on `oracle_mgthris` directly instead of a separate SELECT-only connection (see `plan.md` §2/§6 item 3). No DBA ticket needed |

---

## P1 — Foundation

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T05** | 3 migrations on `oracle_mgthris` (PRD §6): `RPT_GROUPS`, `RPT_DEFINITIONS`, `RPT_PARAMETERS`. PK via `$table->integer('RG_ID')->autoIncrement();` — one call, both drivers (verified pattern: `Modules/LcControl/database/migrations/2026_05_04_100400_create_lc_goods_table.php`; no manual trigger DDL needed, that's only for `SysIdHelper`-style business IDs). `hasTable()` guard, `DISABLE_MIGRATIONS` key per table, CHECK constraints on the Oracle branch only, FKs per §6 (`RD_GROUP_ID` restrict, `RP_REPORT_ID` cascade), unique `(RP_REPORT_ID, RP_PARAM_KEY)` | T01, T02 | `php artisan migrate` clean on SQLite and the Oracle test schema; matches §6 column-for-column |
| ✅ **T06** | `App\Casts\ClobJson` (app-level, not module-specific — generic Oracle CLOB→JSON caveat per §6.4) | — | Round-trips a PHP array through `RP_STATIC_OPTIONS` on the Oracle branch without the OCI-Lob object leaking through |
| ✅ **T07** | Enums: `ReportStatusEnum` (`Draft`/`Active`/`Inactive`), `ParameterInputTypeEnum` (8 cases per §7.1) — both with `label()`, `badgeVariant()`, `options()` | — | Backed values match the Oracle CHECK constraints character for character |
| ✅ **T08** | Models: `RptGroup`, `RptDefinition` (uses `ClobJson`? no — `RD_QUERY_TEXT` is plain CLOB text, not JSON), `RptParameter` (`RP_STATIC_OPTIONS` uses `ClobJson`) | T05, T06, T07 | Relationships (`RptGroup hasMany RptDefinition`, `RptDefinition hasMany RptParameter`) work; `RptDefinition belongsTo` the FK target confirmed in T01 |
| ✅ **T09** | DTOs: `ReportGroupData`, `ReportDefinitionData`, `ReportParameterData` | T07 | Validation rules match PRD §5.1–§5.3 (required fields, `param_key` format, JSON shape for static options) |
| ✅ **T10** | Repositories + interfaces + `RepositoryServiceProvider` bindings: `RptGroupRepository`, `RptDefinitionRepository`, `RptParameterRepository` | T08 | Repository tests green; `Searchable` trait wired for the admin list views |
| ✅ **T11** | `RptDefinitionObserver`: `created` → create Spatie permission `report.view.{id}`; `deleted` → delete it (FR-2.7/2.8) | T08 | A feature test creates then deletes a `RptDefinition` and asserts the permission's existence both times |
| ✅ **T12** | `Developer` role + `report.manage` permission seeder | T01 | Seeder is idempotent; documented how it's assigned to real users (manual via existing Spatie UI, per PRD §4) |

---

## P2 — Query engine

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T13** | `SqlGuard`: strip `--` and `/* */` comments, then reject if any of the FR-2.3 keyword list appears (case-insensitive, word-boundary aware to avoid false positives like a column named `CREATED_AT`) | — | Acceptance criterion 2: a query with `DELETE` inside a `/* */` comment is rejected; a query with a column named `SELECTOR` is not falsely rejected |
| ✅ **T14** | `ReportQueryService::buildQuery`: parse `:param_key` placeholders from raw SQL text, expand multi-select (`multi_select_static`/`multi_select_query`) into `IN (:key_0, :key_1, ...)` with a sentinel binding when nothing is selected, split `date_range` into `:key_start`/`:key_end`, wrap the final query with `ROWNUM <= :max_rows` | T13 | Acceptance criteria 4, 5: a `date_range` parameter yields two bindings; three multi-select values yield three named bindings; an unselected optional multi-select still returns a valid (zero-row) query |
| ✅ **T15** | `ReportQueryService::runQuery`: execute on `oracle_mgthris` (originally planned as a separate `oracle_readonly` connection — dropped, see T04) via the built query + bindings, enforce `timeout_sec`, catch and convert any DB exception to a generic message for end users while logging the real one for developers (NFR-1.4, NFR-2.3) | T14 | A forced timeout fails cleanly with the generic message; the real Oracle error appears only in the log channel, never in the HTTP response body |
| ✅ **T16** | `ReportQueryService::fetchDropdownOptions`: run a `dropdown_query`/`multi_select_query` source query through the same `SqlGuard`, cap at 500 rows, return `[value, label]` pairs | T13, T15 | NFR-2.2 enforced; a source query returning >500 rows is truncated, not errored |

---

## P3 — Admin UI (Super Admin; the PRD's `Developer` role was dropped)

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T17** | `ReportGroupManager` (Livewire, `Modules/Report/app/Livewire/Admin/`): CRUD, `RG_DISPLAY_ORDER`, `RG_IS_ACTIVE` toggle, delete blocked while it still has definitions (FR-1.3) | T10, T12 | Deleting a non-empty group shows a clear error instead of an FK violation |
| ✅ **T18** | `ReportDefinitionManager`: create/edit with group picker, name, description, `RD_QUERY_TEXT` (code editor field), `max_rows`/`timeout_sec` (with the PRD defaults 1000/30), status (`draft`/`active`/`inactive`); save calls `SqlGuard` and shows the exact rejected keyword on failure | T13, T17 | Acceptance criterion 1 (partial) and criterion 2 from the admin-UI side: a `DROP TABLE` typed into the query field is rejected before save, with a specific message |
| ✅ **T19** | `ReportParameterManager`, nested under a definition: CRUD for `RP_*` fields, `param_key` uniqueness within the report (FR-3.3), JSON editor for `*_static` options, source-query field for `*_query` types reusing `SqlGuard`, `display_order` | T10, T13, T18 | Acceptance criterion 1 completed: a developer builds group → definition → all 8 parameter types without touching code |

---

## P4 — End-user catalog & viewer

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T20** | `ReportCatalog` (Livewire, `Modules/Report/app/Livewire/`): reports grouped by `RptGroup`, filtered to `active` status and `can('report.view.{id}')` | T11, T16 | Acceptance criterion 6: a user without the permission never sees the report in the list |
| ✅ **T21** | `ParameterFormBuilder`: builds a dynamic form schema from `RptParameter::orderBy(display_order)`, resolves `dropdown_query`/`multi_select_query` options via T16 on form open (FR-4.4) | T16, T19 | Acceptance criterion 3: all 8 input types render correctly, including async-loaded dropdowns |
| ✅ **T22** | `ReportViewer`: validates required parameters (FR-4.3), submits to `ReportQueryService::runQuery`, renders zero-row message (FR-4.7) or truncated-result warning (FR-4.8) | T15, T21 | Acceptance criteria 10, 11 |

---

## P5 — Client-side table & Excel

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T23** | `ReportColumnTypeInspector`: infers numeric/date/text per column from the result set, shared by the table renderer and Excel export (FR-5.7) | T22 | One class, two call sites; a unit test asserts the table and Excel format the same sample dataset identically |
| ✅ **T24** | Client-side result table — **built as a Report-module-owned partial + Alpine component, NOT as a new mode on the shared `<x-ui::table>`** (see the T24 deviation in the progress log): full dataset rendered once, Alpine-driven search/sort/pagination, numeric right-align + thousands separator, date `dd/MM/yyyy` (FR-5.1–5.6) via T23 | T22, T23 | Acceptance criterion 7; existing server-paginated usages of `<x-ui::table>` elsewhere in the app are unaffected (regression check) |
| ✅ **T25** | `ReportExcelService` + `ReportResultExport` (maatwebsite): full formatting spec (PRD §8.4 — fonts, header/data row styling, freeze pane, auto-filter, column autosize, number formats, landscape print, header/footer), formula-injection sanitization (FR-6.5) on any cell starting `=`/`+`/`-`/`@`, filename `{slug}_{YYYYMMDD}_{HHmmss}.xlsx` | T23 | Acceptance criteria 8, 9 |
| ✅ **T26** | Download endpoint: browser POSTs the currently-filtered/sorted dataset (post client-side search/sort) to the server, server streams the `.xlsx` synchronously — no job/queue (FR-6.2/6.3) | T24, T25 | Downloaded file matches exactly what's on screen, including active search filter and sort order |

---

## P6 — Access control & NFR hardening

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T27** | Route middleware: admin routes (`T17`–`T19`) gated to **`Super Admin` only — the `Developer` role was dropped**, see the progress log; catalog/viewer routes gated per-report via `report.view.{id}` (NFR-1.3) | T17, T18, T19, T20 | Acceptance criterion 6 as a negative test: direct URL access to a report the user lacks permission for returns 403, not just "hidden from the list" |
| ✅ **T28** | Sidebar entries + breadcrumbs for both the admin section and the report catalog | T27 | Nothing appears in the sidebar the current user can't open |
| ✅ **T29** | `report_execution` log channel (`config/logging.php`) for the developer-facing error detail from T15 | T15 | Channel writes; nothing reads `env()` outside config |

---

## P7 — Testing & UAT

| # | Task | Depends on | Done when |
|---|---|---|---|
| ✅ **T30** | Pest suite covering acceptance criteria 1–11 end to end. Landed as `tests/Feature/Report/AcceptanceCriteriaTest.php` (repo-level `tests/`, not `Modules/Report/tests/` — that's where every other Report test already lives), one `AC-{n}: ...` test per criterion | T01–T29 | ✅ 12 passing tests, all 11 criteria named. AC-7's "no server round-trip" is asserted structurally, not in a browser — see the progress log |
| ✅ **T31** | Integration tests against the Oracle test schema: real `ROWNUM` capping (incl. with `ORDER BY`), real `timeout_sec` cutoff, multi-select `IN` expansion under OCI named binds, the `'-999999999'` empty-selection sentinel against `NUMBER` and `VARCHAR2`, and the `date_range` two-bind split. `tests/Integration/Report/ReportOracleIntegrationTest.php` + a new `Integration` phpunit testsuite; opt-in via `REPORT_ORACLE_IT_*`, skips otherwise. No pattern existed to copy — no other module in this repo has an Oracle integration test | T15 | ✅ **Verified 2026-09-15 against the live `althara` DB: 8 passed, 19 assertions.** `timeout_sec` confirmed real via `ORA-03156: OCI call timed out` in the log. See the progress log for the full result table |
| ✅ **T33** | **Not in the original plan — added 2026-09-15 on the team's request.** "Report Access" screen (`ReportAccessManager` + `ReportAccessService`, route `report.admin.access`): per-report, per-user access control with two tabs (Per Report / Per User), a pending-decisions model that survives pagination, and a summary of direct-grantee counts. **Supersedes PRD §4's "tidak ada layar baru untuk ini"** — report *groups* organize by domain, but access cuts across domains (a Finance user needing a Material Control report), which roles express badly. Direct permissions only; role-derived access stays in Core's roles UI and is deliberately not editable here | T11, T27 | 10 passing tests incl. the direct-vs-role boundary; menu item seeded; smoke-tested against the live `althara` DB |
| ✅ **T34** | **Not in the original plan — added 2026-09-15.** Report execution moved to its own `oracle_report` connection (`config/database.php`), identical to `oracle_mgthris` except `PDO::ATTR_CASE => PDO::CASE_NATURAL`, so a report's quoted column aliases survive as its headers instead of being flattened to lowercase by the Oracle driver. Header casing is now the report author's choice: quote the alias to control it; an unquoted identifier comes back in Oracle's uppercase. `tests/TestCase.php` was taught not to clobber a connection that states its own `ATTR_CASE` | T15 | 4 config/binding tests + 2 Oracle integration tests (both directions); the pilot report's 18 headers verified on the live DB |
| ✅ **T35** | **Not in the original plan — added 2026-09-15, phase 1 of 2.** Background report execution: `RPT_RUNS` table + `RptRun`/`ReportRunStatusEnum`/repository, `RunReportJob` (query → xlsx + JSON snapshot → `minio_private` → `ReportStatusNotification`), `ReportRunService`, and a "Proses di Background" button on the viewer. **Reverses `plan.md`'s "nothing to queue" decision** — measurement showed the query is ~38s and the Excel formatting 3.2s, so it is the query that had to leave the request cycle, not the export. Mirrors `ExportLedgerJob`'s pipeline exactly | T15, T25 | 7 tests; verified end to end on the live DB (run #1: 4,357 rows in 40s, both artifacts on MinIO, notification delivered). `run()` stays until T36's result page lands |
| ✅ **T36** | **Phase 2, done 2026-09-15.** `ReportRunViewer` (`/dashboard/reports/runs/{run}`) renders a finished run from its JSON snapshot — **4,357 rows in 0.4s vs 38s inline**. Notification deep-links to it. `ReportViewer` is now form-and-dispatch only (0.48s), watching its queued run via `wire:poll.5s` plus the Reverb private-channel listener, and redirecting to the result on completion. Run access is scoped to the requester, not just `report.view.{id}`. Two downloads on the result page: the pre-built full file (no row ceiling) and the client-filtered subset (AC-8, keeps the 5,000 bound) | T35 | A finished run is viewable without re-running the query; the notification opens it |
| ✅ **T37** | **Retention sweep, done 2026-09-15.** `report:prune-runs` (nightly 03:30, `report_retention` log channel, `--dry-run`, `--limit`, `--stale-minutes`): deletes expired artifacts **then** rows — never the reverse, since the row is the only record of where the artifacts live — and marks runs whose worker died as failed so the viewer stops polling them. Sweeps by run DIRECTORY, so a file orphaned by a job that crashed mid-write is caught too. Retention window moved to `report.retention_days` config, read by both the job and the sweep | T35 | 10 tests; verified against the real bucket: `Swept 1 run(s), freed 303.7 KB` |
| ✅ **T38** | **Not in the original plan — 2026-09-15.** Large-report handling. Export picks its writer by size: PhpSpreadsheet (PRD §8.4 formatting) up to `report.rich_format_max_rows`, OpenSpout streaming beyond. `RD_MAX_ROWS` now caps only the ON-SCREEN snapshot — the query is capped by `report.max_export_rows` instead, so a small view limit no longer truncates the download. Executor gained `cursor()`; the job walks the result once, teeing the snapshot off as rows pass to the writer | T35, T36 | Measured: PhpSpreadsheet died at 40k rows; streaming does 300k at a flat 72 MB. 7 tests on the switch boundary |
| ✅ **T39** | **2026-09-15.** `exports:prune` moved from app-level into **Modules/Core** (`Modules\Core\Console\Commands\PruneExportsCommand`, config `core.exports.*`, scheduled from `CoreServiceProvider`), so it belongs to a module the team can surface in the app | T37 | Command + schedule live in Core; 12 tests moved with it |
| ⏳ **T32** | **Only task left; needs people, not code.** T31 is green now, so nothing blocks this. UAT: a developer builds one real pilot report (group → definition → parameters) with no code deploy, assigns `report.view.{id}` to a test role via the existing Spatie UI, an end user runs it and downloads Excel. The developer here is a **`Super Admin`**, not a `Developer` — that role was dropped | T30, T31, T12 | Sign-off from both a developer and an end-user tester; the downloaded Excel matches the on-screen table exactly |
