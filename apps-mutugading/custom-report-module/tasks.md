# tasks.md — Phase 1 task list

Dependency-ordered. Each task names what "done" means. Acceptance-criteria numbers refer to
`PRD/PRD_Custom_Report_Module_Phase1.md` §10.

`vendor/bin/pint --dirty` before every commit. Conventional Commits, scope `report` (add it to the
allowed scope list in `CLAUDE.md`/`CONTRIBUTING.md` if not already there).

---

## Progress log

**Status as of 2026-09-14: T01–T31 done (P0–P7 code complete), T04 dropped. T32 (UAT) is the only
task left and it needs people, not code.**

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

**Still genuinely open (not just "not built yet"):**

- **T31 is written but has never been run against a real Oracle schema** — it skips in this
  environment (no Oracle, no `oci8` extension). So every Oracle-dependent claim the module makes is
  still a claim: real `ROWNUM` capping, real `timeout_sec` cutoff, the `'-999999999'` sentinel against
  a `NUMBER` column, `date_range`'s two binds under OCI's implicit date conversion. The assertions
  exist; the verification doesn't. **Running this suite once against the test schema is the single
  highest-value thing left** — it is also a prerequisite for T32 being meaningful.
- The timeout test additionally self-skips when the Oracle client is older than 12.1
  (`oci_set_call_timeout()` doesn't exist there, so `applyTimeout()` is a no-op and `timeout_sec` is
  **not enforced at all**). If the target environment's client is 11g, that is a real functional gap,
  not a test-environment quirk.
- Whether the `SqlGuard`-only protection (above) needs revisiting once Admin UI access is opened
  beyond a small trusted `Super Admin` group.

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
| ⚠️ **T31** | Integration tests against the Oracle test schema: real `ROWNUM` capping (incl. with `ORDER BY`), real `timeout_sec` cutoff, multi-select `IN` expansion under OCI named binds, the `'-999999999'` empty-selection sentinel against `NUMBER` and `VARCHAR2`, and the `date_range` two-bind split. `tests/Integration/Report/ReportOracleIntegrationTest.php` + a new `Integration` phpunit testsuite; opt-in via `REPORT_ORACLE_IT_*`, skips otherwise. No pattern existed to copy — no other module in this repo has an Oracle integration test | T15 | **Written, not yet verified.** All 8 skip here (no Oracle, no `oci8` ext). Needs one run against the test schema — see "Still genuinely open" above |
| ⏳ **T32** | **Only task left; needs people, not code.** Run T31 against the test schema first, then UAT: a developer builds one real pilot report (group → definition → parameters) with no code deploy, assigns `report.view.{id}` to a test role via the existing Spatie UI, an end user runs it and downloads Excel. The developer here is a **`Super Admin`**, not a `Developer` — that role was dropped | T30, T31, T12 | Sign-off from both a developer and an end-user tester; the downloaded Excel matches the on-screen table exactly |
