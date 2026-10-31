# plan.md — implementation plan

Derived from `PRD.md`, `schema.md`, `design.md`, `spec.md`, `gap-analysis.md`. Aligned to the root
`CLAUDE.md`: modular monolith, Repository → Service → Livewire, Oracle via `yajra/laravel-oci8`,
Spatie Permission, Pest 4, Pint before every commit.

## 1. Decisions

Confirmed with the requester (2026-08-31):

| # | Decision |
|---|---|
| **D1** | Lives in `Modules/Finance` as the `CiProject` domain in every layer. No new module. |
| **D2** | The legacy `ci_project_*` tables on `oracle_mgtapps` stay and are read/written directly. No project or saving backfill; only additive schema changes. |
| **D3** | Period approval becomes a per-period lock table `ci_project_period` (`Draft → Approved`, plus `Reopened` — no `Submitted`), dual-writing the legacy cut-off row until the old app is retired. |
| **D4** | Access is Spatie permissions + department scope. The `ADMIN` / `CAN_VIEW_ALL` master rows stop being an authorization source. |
| **D5** | Projects carry their own workflow — `Draft → Approved`, `Approved → Amend → Approved` — on four additive `cpm_appr_*` columns. Savings are accepted in every state; what the workflow gates is editing — an approved project is amended, with a reason, rather than changed in place. |
| **D6** | The master table gets a real maintenance page, built in P1 rather than last, because every dropdown depends on it. |

Still to settle before the affected task:

| # | Topic | Default | Settle in |
|---|---|---|---|
| **D7** | `ci_project_period` id source: `SysIdHelper` + a new `HmMstSequences` row, or an Oracle trigger like the sibling tables | `SysIdHelper` — house standard, and it works under SQLite in CI | T03 |
| **D8** | Whether the functional unique index on `UPPER(cpm_title)` can be created (depends on Q2) | Defer the index if duplicates exist; keep the service-level check either way | T02 |
| **D9** | Whether the saving grid stays a Blade partial driven by parent methods, or becomes a child Livewire component per row | Blade partial — a child component per row on a 200-row grid is where this page would get slow | T13 |
| **D10** | Whether `CI Project - Admin` / `- Department` / `- Viewer` are the right role names for the CI team | Those three; confirm with the coordinator at T18 | T05 |
| **D11** | *(settled 2026-08-31)* Approval status never gates saving input — `Draft`, `Amend` and `Approved` all take savings. The workflow is an edit gate, not a queue the department's work waits behind | — | settled |
| **D12** | Whether the approval queue is a filter on the report page or a page of its own | A filter plus a pending count — a separate page duplicates the whole list for one predicate | T15 |
| **D13** | Whether approval must be done by someone outside the project's own department | No — separation is by permission, not by department (`spec.md` §2A.2). Revisit if the coordinator asks | T11 |

Non-negotiables from the root `CLAUDE.md`: no `wire:navigate` / `navigate: true`, no `DB::` facade outside
repositories, no `env()` outside config, no `dd()`/`dump()`, `{{ }}` not `{!! !!}`, `wire:key` in loops,
UI-module components before Flux before custom, `vendor/bin/pint --dirty` before every commit.

## 2. Phases

| Phase | Content | Exit criteria |
|---|---|---|
| **P0 — Data check** | Run the production queries in `schema.md` (§1.3, §2.1, §2.2) and the department-code check (Q6). Answer Q1, Q2, Q6 | Written answers in this doc set; the clean-up script, if needed, reviewed by the DBA |
| **P1 — Foundation & masters** | Migrations (incl. the `cpm_appr_*` columns), models, enums, DTOs, repositories, permission migration + seeder, and the master maintenance page (D6) | `php artisan migrate` clean on SQLite and on the Oracle test schema; repository tests green; a group can be added through the UI (tests 18, 19) |
| **P2 — Approval workflows** | Both state machines: `CiProjectPeriodService` + `CiProjectPeriodManager` page, and `CiProjectApprovalService` (D5) | Tests 2, 4, 5, 13–17 green; a month can be closed and reopened, and a project drafted, approved, amended and re-approved, end to end |
| **P3 — Report page** | `CiProjectReport`: list, filters, scope, pagination, saving input, create/edit/delete, detail modal, approval badges + actions + pending count | Numbers match legacy for three sampled months × three departments; tests 1, 3, 6, 7, 8, 11, 12, 20 green |
| **P4 — Exports & command** | Three exports, the queued job, the auto-finish command, the log channel | Tests 9, 10 green; each export tallies with the screen |
| **P5 — Cut-over** | Role assignment for today's ADMIN/CAN_VIEW_ALL users, sidebar entries, release note, coordinator hand-over | The CI coordinator has approved a project and closed one real month on the new page while the legacy page still agrees |
| **P6 — Retirement** *(later PR)* | Delete the dual-write + flag, delete the three master types, remove the legacy page | Old app off |

P2 before P3 on purpose: both locks are inputs to almost every rule on the report page, and building the
page first would mean stubbing them and then reworking it. The master page moves up into P1 for the same
reason from the other end — every dropdown on the report page reads from it, and a page built against
hand-inserted master rows is a page nobody can demo.

## 3. Risks

| Risk | Handling |
|---|---|
| Two apps writing the same tables during cut-over | Additive schema only; the dual-write keeps the legacy cut-off honest; no column is retyped or renamed |
| Duplicate saving rows blocking the unique index | P0 finds them; the clean-up is a reviewed script, and the migration throws rather than skipping if any remain |
| Today's admins arrive with no access | T18 is a blocking cut-over task with a named owner, not a seeder side effect |
| Existing projects landing as `Draft`, becoming freely editable when they should be frozen | The column defaults to `Approved` (`schema.md` §1.1) and test 20 pins it |
| A backlog of unapproved projects nobody notices | Low impact now that drafts work normally, but the pending count sits in the report header and more than one person holds `approve` (T18) |
| Oracle `PIVOT` behaving differently through Eloquent bindings than the legacy string-built SQL | Compare the pivot export against a legacy run for the same year before P4 closes |
| The three-month join getting slow once paginated | Index from `schema.md` §1.1; measure on the real row count in P3 |

## 4. Testing

Pest, in `Modules/Finance/tests/Feature/Transaction/CiProject/`. The twenty cases in `spec.md` §10 are the
minimum. Repository- and service-level tests for the scope, the upsert and both state machines (not just
component tests) — those are the security and the data-integrity boundaries, and a component test would pass
even if the rule moved somewhere a crafted request could skip.

Case 20 deserves naming: a fixture of projects created **without** the approval columns being set, asserting
they read back as `Approved`. That is the one that catches a wrong column default, which would leave every
existing project sitting as an editable draft on the day of deploy — the opposite of what the workflow is for.

CI runs on SQLite, so: no Oracle-only SQL outside a repository method that is either skipped or
feature-detected in tests, and the migrations must be SQLite-compatible.
