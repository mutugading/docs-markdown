# plan.md — implementation plan

Derived from `PRD/PRD_Custom_Report_Module_Phase1.md`. Aligned to the root `CLAUDE.md`: modular
monolith (`nwidart/laravel-modules`), Repository → Service → Livewire, Oracle via
`yajra/laravel-oci8`, Spatie Permission, Pest 4, Pint before every commit.

Scope of this plan is **Phase 1 only** — report customize (build + view + Excel). Phase 2 (email
alert) is out of scope per PRD §3.2 and §12.

---

## 1. Non-negotiables

From the root `CLAUDE.md`: no `wire:navigate` / `navigate: true`; no `DB::` facade outside
repositories; no `env()` outside config; no `dd()`/`dump()`; `{{ }}` not `{!! !!}`; `wire:key` in
loops; UI-module components before Flux before custom; `vendor/bin/pint --dirty` before every
commit; migrations SQLite-compatible because CI runs on SQLite; **never `migrate:fresh` /
`migrate:refresh`** on this environment.

From the PRD, just as binding:

- **Every query — main and dropdown source — goes through the same DML/DDL guard** (FR-2.2–2.4,
  FR-3.5), including comment-stripping so a keyword can't hide in `-- DELETE` or `/* DROP */`.
- **Parameters are always named bindings, never string-concatenated** (NFR-1.2). This applies to the
  multi-select expansion and the date_range split too — expansion changes the SQL text and the
  binding map, it never inlines a value.
- **Row limiting happens in the database via `ROWNUM`, not in PHP** (FR-4.6, NFR-2.1).
- ~~Query execution uses a separate, SELECT-only Oracle connection (`oracle_readonly`)~~ **Overridden
  2026-09-14**: report queries run on the app's normal `oracle_mgthris` connection instead. See §2's
  updated decision row and §6 item 3 (resolved) for why.
- **Error detail is role-gated**: developers see the real Oracle error, end users see a generic
  message (NFR-1.4). Never let a stack trace or SQL fragment reach an end user.
- **Web table and Excel must agree on column typing** (FR-5.7) — one column-type detector, used by
  both, or the two will drift the first time someone adds a column format.

---

## 2. Key architecture decisions

The PRD specifies *what*, not always *where in the module tree*. These decisions should be
confirmed with the team before P1 starts (see §6, Open Questions) but this plan proceeds on them:

| Decision | Choice | Rationale |
|---|---|---|
| New module | `Modules/Report` (alias `report`) | None of the 7 existing modules own "developer-defined SQL reports" as a domain; doesn't belong inside Hr/Mis/Finance |
| Module dependency | `Core` (layout, breadcrumbs, sidebar, UI components), `Hr` (auth user is `HmEmpData`, same schema) | Matches existing dependency graph shape |
| Catalog tables connection | `oracle_mgthris` **(decided — per user)** | `RPT_GROUPS/DEFINITIONS/PARAMETERS` live in the HRIS schema. `RPT_` doesn't match the `HM_`/`HT_`/`CM_` prefix families already on this schema, but the PRD's own `RPT_` prefix is schema-agnostic, and keeping it here puts `RD_CREATED_BY` in the same schema as `HmEmpData`, avoiding a cross-schema FK |
| Report execution connection | **Reversed 2026-09-14** — the app's normal `oracle_mgthris` connection, not a separate `oracle_readonly` one | PRD §8.3/NFR-1.1 wanted a dedicated SELECT-only Oracle user (`rpt_readonly`) as a DB-level backstop under `SqlGuard`. Team decision: skip it for Phase 1 — it needs an Oracle DBA to create the user and then keep granting `SELECT` per table as new reports are built, which is ongoing overhead judged not worth it for a small, trusted `Developer` group. **Accepted risk: `SqlGuard` is now the only thing preventing a report query from writing to production data** — no database-level backstop if it's ever bypassed. Reversible later (config + one-line binding change) if the risk profile changes |
| `RD_CREATED_BY` FK target | `HmEmpData` (`hmemd_sys_id`) | `auth()->user()` resolves to `HmEmpData` everywhere in this app (CLAUDE.md Auth Flow); same-schema FK now that the catalog tables are on `oracle_mgthris` |
| PK strategy for `RPT_*` tables | `$table->integer('RG_ID')->autoIncrement();` — same call on both drivers | Verified against `Modules/LcControl/database/migrations/2026_05_04_100400_create_lc_goods_table.php`: `yajra/laravel-oci8` translates `->autoIncrement()` into the sequence+trigger itself. The manual `PKG_HM_SEQUENCES` trigger pattern seen elsewhere in that module (e.g. `lc_master`'s `LCM_SYS_ID`) is only for *business-formatted* IDs (date-prefixed doc numbers via `SysIdHelper`), not needed here since `RPT_*` PKs are plain counters per PRD §6 |
| `Developer` role | New Spatie role `Developer`, seeded, distinct from `Super Admin` | PRD §4 treats it as its own role with SQL-writing privilege; reusing `Super Admin` would over-grant |
| Client-side result table | Extend `UI` module's `<x-ui::table>` with an Alpine-driven client-side mode (full dataset in DOM, JS search/sort/paginate) | PRD §11 lists a "client-side table library" as a dependency, but FR-5.2 wants the whole result set loaded once with no server round-trip for search/sort/page — that's a different mode than the existing server-paginated `<x-ui::table>`. Extending it keeps one table component instead of two, and avoids a new npm dependency (Alpine is already bundled) |
| Excel generation | Synchronous, not queued | FR-6.3 has the browser POST the currently-displayed dataset and the server streams a file back immediately — no `ReportExport`/`ImportReport` job pair like the standard CRUD skill scaffolds. There's nothing to queue: the query already ran, this step formats a small in-memory array |

---

## 3. Phases

| Phase | Content | Exit criteria |
|---|---|---|
| **P0 — Setup** | Module scaffold (`php artisan module:make Report`), `oracle_readonly` connection config + `.env.example` entries, DBA ticket for the `rpt_readonly` Oracle user (§8.3), confirm the 6 open questions in §6 | Module boots with an empty route file; DBA has created `rpt_readonly` on the test schema; open questions have written answers |
| **P1 — Foundation** | 3 migrations (`RPT_GROUPS`, `RPT_DEFINITIONS`, `RPT_PARAMETERS`), models, `App\Casts\ClobJson`, enums (`ReportStatusEnum`, `ParameterInputTypeEnum`), DTOs, repositories + `RepositoryServiceProvider` bindings, `RptDefinitionObserver` (permission sync), `Developer` role + permission seeder | `php artisan migrate` clean on SQLite and the Oracle test schema; a `RptDefinition` created in a test triggers `report.view.{id}` to exist and deleting it removes the permission (covers FR-2.7/2.8) |
| **P2 — Query engine** | `SqlGuard` (comment-stripped DML/DDL detection), `ReportQueryService::buildQuery` (multi-select expansion, date_range split, `ROWNUM` wrap), `::runQuery` (execute on `oracle_readonly`, timeout, error segregation), `::fetchDropdownOptions` (500-row cap, same guard) | Acceptance criteria 2, 4, 5 pass under Pest with pure-PHP tests (no Oracle needed — this layer is string/array logic plus a mockable query executor) |
| **P3 — Admin UI (Developer)** | `ReportGroupManager`, `ReportDefinitionManager` (SQL validated via `SqlGuard` on save — FR-2.1–2.3, `max_rows`/`timeout_sec` fields), `ReportParameterManager` nested under a definition (8 input types, JSON static-options editor, source-query field reusing the guard) | Acceptance criterion 1: a developer creates a full report (group → definition → parameters) with zero deploys |
| **P4 — End-user catalog & viewer** | `ReportCatalog` (grouped list, filtered by `can('report.view.{id}')`), `ParameterFormBuilder` (dynamic form from `RPT_PARAMETERS`, ordered by `display_order`, async dropdown_query loading), `ReportViewer` (submit → `ReportQueryService::runQuery` → render), zero-row and truncated-row messaging | Acceptance criteria 3, 4, 5, 6, 10, 11 |
| **P5 — Client-side table & Excel** | `<x-ui::table>` client-side mode (search/sort/paginate in Alpine, no server round-trip), shared `ReportColumnTypeInspector` (numeric/date detection used by both table and Excel), `ReportExcelService` + `ReportResultExport` (full formatting spec §8.4), formula-injection sanitization (FR-6.5), filename pattern, synchronous download endpoint | Acceptance criteria 7, 8, 9 |
| **P6 — Access control & NFR hardening** | Admin routes gated to `Developer` (+ `Super Admin`), error message segregation (developer sees real Oracle error, end user sees generic — NFR-1.4), `timeout_sec` enforcement + failure reporting (NFR-2.3), sidebar/breadcrumb entries for both admin and catalog | Acceptance criterion 6 re-verified as a negative test (a user without `report.view.{id}` cannot reach that report by URL, not just hidden from the list); a query forced past `timeout_sec` fails cleanly |
| **P7 — Testing & UAT** | Pest suite mapped to PRD §10 acceptance criteria 1–11, one integration test against the Oracle test schema (guard + multi-select + `ROWNUM` behave the same as on SQLite), UAT: a developer builds one real report end to end, an end user runs it and downloads Excel | All 11 acceptance criteria demonstrably met on the test schema; UAT sign-off |

P2 before P3 on purpose — the admin UI's "validate on save" (FR-2.2) is a call into `SqlGuard`, and
building the form before the guard exists means either building a placeholder validator (thrown
away) or leaving validation unimplemented and confusing "done" for P3. P5's Excel work depends on
P4 producing a dataset shape (assoc rows + inferred column types) to format, so it comes after, not
in parallel.

---

## 4. Risks

| Risk | Handling |
|---|---|
| DML guard is regex/keyword based — a determined developer (the only role with SQL access) could still write something dangerous inside a technically-valid `SELECT`, e.g. a subquery calling a PL/SQL function with side effects | Accepted risk per PRD scope (Developer is a trusted role, not an untrusted input surface). The guard's job is to catch mistakes and obvious DML, not to sandbox a malicious developer. Document this boundary explicitly in the admin UI's help text |
| `oracle_readonly` user is granted broader `SELECT` access over time as more reports are built, becoming a de facto full read replica of production data with no per-report scoping | NFR-1.1 only requires SELECT-only, not per-table scoping. Flag as a Phase 2+ consideration (per-report grant review), not blocking for Phase 1 |
| Multi-select with a very large option set hits Oracle's 1000-element `IN` limit (ORA-01795) | PRD §7.3 accepts this as a known limitation requiring query redesign by the developer; not code-enforced in Phase 1. Add a soft warning in the admin UI when a `dropdown_query`/`multi_select_query` source returns close to 500 rows, since that's the realistic precursor |
| Client-side table with `max_rows` near 1000 becomes sluggish in the browser (NFR-2.4) | Build the Alpine-driven table with the full 1000-row case as the baseline perf test, not the common small-report case; if it's not responsive, cap the default column count rendered eagerly or virtualize rows before shipping P5 |
| `RD_CREATED_BY` FK choice (HmEmpData vs MstUsers) turns out wrong once someone reviews actual auth data | Confirmed early in P0/§6 before migrations are written in P1 — cheap to fix before the FK exists, expensive after |
| Extending `<x-ui::table>` with a second (client-side) mode couples two unrelated concerns into one component and destabilizes its existing server-paginated usages elsewhere in the app | Add the client-side mode behind an explicit prop (e.g. `mode="client"`), default remains server-paginated; existing call sites get zero behavior change. Cover both modes with a Livewire/Blade test before merging |
| Error segregation (NFR-1.4) leaks a raw Oracle exception message to an end user through an unhandled exception path (e.g. a timeout, a connection drop) not just the "bad query" path | Wrap `ReportQueryService::runQuery` in a single try/catch boundary that always converts to a generic end-user message + a `Log::channel('report_execution')` entry with the real detail, rather than relying on each call site to remember |

---

## 5. Testing

Pest 4, `Modules/Report/tests/`. CI is SQLite, so:

- `SqlGuard`, multi-select expansion, and date_range binding-splitting are pure string/array logic —
  fully unit-testable without any real database;
- `ReportQueryService::runQuery` is tested against a bindable query executor abstraction so the
  `ROWNUM` wrap and timeout behavior can be asserted without Oracle;
- what SQLite cannot prove — that `ROWNUM` wrapping and the `oracle_readonly` grant actually behave
  against real Oracle 11g, and that a genuine `timeout_sec` cutoff works — is covered by one
  integration test against the Oracle test schema, skipped when unreachable (same pattern as other
  Oracle-dependent modules in this repo).

Map every test back to an acceptance criterion (PRD §10, items 1–11) so "done" for P7 has a
one-to-one checklist, not a vague "tests pass."

---

## 6. Open questions (resolve before/at start of P1)

1. Confirm module name/placement — `Modules/Report` as proposed, or does this belong under an
   existing module (e.g. `Core`)?
2. ~~Confirm `RPT_*` tables live on `oracle_mgtapps` vs `oracle_mgthris`.~~ **Resolved: `oracle_mgthris`.**
3. ~~Who creates the `rpt_readonly` Oracle user and its grants (§8.3)?~~ **Resolved 2026-09-14: not
   created.** Report queries run on `oracle_mgthris` directly — see §2's updated decision row.
4. Confirm `RD_CREATED_BY` FK target: `HmEmpData` (assumed here) or `MstUsers`.
5. Confirm a new `Developer` Spatie role is wanted (assumed here), rather than reusing an existing
   role or a single `report.manage` permission on top of `Super Admin`.
6. Client-side table: sign off on extending `<x-ui::table>` (assumed here) vs. introducing a
   dedicated JS library (e.g. Grid.js) — affects bundle size and whether P5 touches shared UI-module
   code other pages already depend on.
