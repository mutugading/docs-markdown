# plan.md — implementation plan

Derived from `PRD.md`, `schema.md`, `design.md`, `spec.md`, `gap-analysis.md`, `data-migration.md`.
Aligned to the root `CLAUDE.md`: modular monolith, Repository → Service → Livewire, Oracle via
`yajra/laravel-oci8`, Spatie Permission, Pest 4, Pint before every commit.

---

## 1. Decisions

Confirmed with the requester (2026-08-28):

| # | Decision |
|---|---|
| **D1** | Lives in `Modules/Finance` as the `Shipment` domain in every layer. No new module, no change to `module.json` beyond keeping `requires: ["Core"]`. |
| **D2** | Full backfill of the legacy `SHP_*` data (`data-migration.md`). New tables are the only thing the app reads after cut-over. |
| **D3** | Vouchers are a PHP port writing directly into the ERP tables. `pkg_gen_voucher_ship` is not called. |
| **D4** | Phase 1 = export + import, provision → settlement, + dashboard + masters. Reports are Phase 2. |

Still to settle before coding the affected task:

| # | Topic | Default | Settle in |
|---|---|---|---|
| **D5** | `spv_status` / `stl_status` cast to a **shared** `ShipStatusEnum` whose `label()` differs per document, vs two enums | shared enum, `label(?string $document = null)` | T003 |
| **D6** | `ship_cost_type` PK: `shc_code` alone, or `shc_sys_id` with `UQ (code, direction)` — needed because `SHIP`/`EMKL` differ per direction (`data-migration.md` §7) | `shc_sys_id` + unique (code, direction); update `schema.md` §10 when decided | T002 |
| **D7** | ERP voucher posting code: copy LcControl's two repositories into Finance, or promote them (+ their DTOs) to `Modules/Core` and depend on Core from both modules | **promote to Core** — doc-no/period plumbing must not exist twice | T010 |
| **D8** | `ExchangeRateService`: copy into Finance or promote to Core alongside D7 | promote with D7 | T010 |
| **D9** | Whether the cost-line grid is a Livewire child component or a Blade partial fed by the parent's state | Blade partial + parent methods first (Livewire nesting on a 200-row grid is where legacy got slow); revisit if the pages get unwieldy | T012 |
| **D10** | Export/import as separate permissions (`gap-analysis.md` Q2) | one permission set, direction is data | T005 |

Non-negotiables carried from the root `CLAUDE.md`: no `wire:navigate`, no `DB::` facade outside
repositories, no `env()` outside config, no `dd()`/`dump()`, `{{ }}` not `{!! !!}`, `wire:key` in loops,
UI-module components before Flux before custom.

---

## 2. Phases

| Phase | Content | Exit criteria |
|---|---|---|
| **P0 — Foundation** | Schema, models, enums, DTOs, masters (data + CRUD), parameters, permissions, breadcrumbs, ERP read repositories | `migrate` green on Oracle and SQLite; master pages usable; Pest fixtures exist |
| **P1 — Provision** | Export + import provision: list, input, detail, tariff pull, insurance, commission, calculator, workflow, provision journal + payment vouchers | A provision can be created, submitted, confirmed and posted in both directions; calculator unit tests green |
| **P2 — Settlement** | Bill list, input (single + multi provision + direct), variance, Faktur Pajak, workflow, bill journal + payment vouchers, provision auto-close | A bill can settle a provision end to end in both directions |
| **P3 — Dashboard & backfill** | Dashboard, missing-voucher repair, backfill command, verification command, cut-over rehearsal | Verification green on a production copy; Finance signs off the spot checks |
| **P4 — Cut-over** | Runbook `data-migration.md` §8 | Legacy read-only, new app live |
| **P5 — Reports** (Phase 2) | The six exports + filter modals + jobs | Column-for-column agreement with legacy output on a sample period |

P0 → P2 is where the risk is; P5 can start in parallel once P0 lands, because the reports only need the
schema.

---

## 3. Sequencing notes

- **Calculator first, screens second.** `ShipCostCalculator` and `ShipTariffService` are pure and fully
  testable. Build and test them against numbers taken from real legacy documents before any Livewire
  work, so the screens are only wiring.
- **One input component, two directions.** Resist the urge to fork `ShipProvisionInput` per direction —
  that fork is exactly why the legacy export and import calculations drifted. The cost-type master
  flags drive the differences.
- **Voucher posting is the last thing in each phase, and is proved against the package** (PRD S3) on a
  sample before it is wired to a button.
- **Masters before transactions.** Nothing in P1 may hardcode an account, a rate or a cost-type
  behaviour; if a value is missing from the master, the task is to add it to the master, not to the code.
- **Backfill after the schema is frozen.** Every schema change after T020 means re-running a dry-run.

---

## 4. Effort shape (rough, for planning only)

| Phase | Tasks | Weight |
|---|---|---|
| P0 | T001–T009 | ~25% |
| P1 | T010–T017 | ~30% |
| P2 | T018–T023 | ~20% |
| P3 | T024–T028 | ~15% |
| P4 | T029 | ~2% |
| P5 | T030–T036 | ~8% (Phase 2) |

The two heaviest single tasks are `ShipProvisionInput` (T014) and `ShipBillInput` (T019) — the legacy
components they replace are ~1.3k and ~1.6k lines. Budget for them to need a follow-up simplification
pass once both are working; `/simplify` on the diff is the right tool.

---

## 5. Testing strategy

| Level | What |
|---|---|
| Unit | calculator (every branch), tariff resolution, account resolver, status transitions, parameter casting |
| Feature | one happy path per direction per document; validation matrices; permission gating; duplicate guards; unbilled filter; multi-provision merge; EMKL→PIB back-fill |
| Feature (ERP) | voucher payload assertions against SQLite stand-ins for the ERP tables |
| Backfill | seeded legacy fixture → backfill → verification, asserting all V-checks |
| Manual | the 20+20 spot check with Finance (PRD S2), and one real voucher per type compared to the package (PRD S3) |

CI runs on SQLite, so every migration must be Blueprint-compatible and every trigger/view migration must
early-return on `sqlite`.

---

## 6. Documentation duties

- A **module-level `CLAUDE.md` section** for the Shipment domain (Finance has no module `CLAUDE.md` yet;
  create one modelled on `Modules/LcControl/CLAUDE.md`) — folder map, table/column prefixes, lifecycle,
  the ERP paths, and a Gotchas section. Written in P0 and updated in the same PR as any architectural
  change. This is what stops the next person re-scanning 20 files.
- Update `.docs-me/shipment-control/*` whenever a decision here is settled — especially `schema.md` for
  D6 and `design.md` §5 for D7.
- Sidebar entries in `Modules/Core/resources/views/partials/dashboard/_sidebar.blade.php` and menu rows
  if the project drives navigation from `MstUserMenus`.

---

## 7. Commits and PRs

Conventional Commits, scope **`finance`** (the existing scope list has no `shipment` scope; add one to
`.github/COMMIT_CONVENTION.md` if Finance's other work makes the log noisy):

```
feat(finance): [T00X] <what>
```

One task per commit where practical, one phase per PR, PR title in Conventional Commits form, screenshots
for every UI task, `vendor/bin/pint --dirty` and `php artisan test --parallel` before pushing. Follow the
repo's PR draft workflow (`.docs-me/PR_DRAFT.md`).
