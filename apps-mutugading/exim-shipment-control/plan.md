# plan.md — implementation plan

Derived from `PRD.md`, `PRD EXIM.md`, `schema.md`, `design.md`, `spec.md`, `gap-analysis.md`,
`data-migration.md`. Aligned to the root `CLAUDE.md`: modular monolith, Repository → Service → Livewire,
Oracle via `yajra/laravel-oci8`, Spatie Permission, Pest 4, Pint before every commit.

**Two deliveries, one plan.** P0–P5 are **Shipment Control** (the migration). P6–P9 are the **Exim Front
Office** (`PRD EXIM.md`). The dependency is one-way — the front office feeds provisions, so Shipment
Control never waits for it — and the only thing P0–P5 must carry for it is one additive migration
(`schema.md` §12 file 40: nullable FKs, two cost-type flags, `SHT_UOM`, `SPC_NO_TARIFF_MATCH`). Ship
that early and the two tracks are genuinely independent.

The front office can therefore start **in parallel** with P2, as soon as the schema and the tariff
service are settled. What it must not do is start before the `TIER` / `FIX` / `RATE` semantics are fixed
in code (T011), because both modules price off the same service.

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
| **D7** | ~~ERP voucher posting code~~ | **SETTLED 2026-09-02: copy into Finance.** The plan's default was to promote to Core; the requester chose the copy, because promoting means moving code that is already carrying production vouchers in LcControl. The cost is accepted knowingly: **the doc-no / period plumbing now exists twice, and a fix needs applying in both copies.** Both repositories carry that warning in their class docblock. If a shared fix is ever actually needed, promote both then rather than letting them drift. | done |
| **D8** | ~~`ExchangeRateService`~~ | **SETTLED 2026-09-02 by T009: neither copied nor promoted.** `ShipExchangeRateRepositoryInterface` already reads `fm_exchange_rate` behind an interface, and `ShipVoucherService` uses it for the USD base conversion — so there is no service to move, and a test can substitute a rate without a database. | done |
| **D9** | Whether the cost-line grid is a Livewire child component or a Blade partial fed by the parent's state | Blade partial + parent methods first (Livewire nesting on a 200-row grid is where legacy got slow); revisit if the pages get unwieldy | T012 |
| **D10** | ~~Export/import as separate permissions~~ | **SETTLED 2026-09-10: separate, reversing this row's default.** Q2 was answered "separate", so `finance-shipment-export` / `-import` now scope which documents a user may reach while the action permissions still cover both sides. Direction remains data in the schema and in the screens — there is still one set of pages — but it is a permission at the door: `AppliesShipDirectionAuthorization`, applied on the lists, the input and detail pages, the picker, the dashboard and the print controller. See `open-questions.md` §6 Q2. | done |

Confirmed for the front office (2026-09-02, with the import, export, sales, finance and tax teams —
`PRD EXIM.md`):

| # | Decision |
|---|---|
| **D11** | The front office is a **new module in front of** Shipment Control, not part of the migration. Kept in a separate PRD so migration parity stays verifiable apple-to-apple, and in the same `Shipment/` namespace so the tariff service, account resolver, calculator and parameter store are not duplicated. |
| **D12** | **Import anchors on the BL**, export on the **SI**. 1 BL = 1 AJU = 1 PIB = 1 supplier. That retires the EMKL↔PIB field-copy patch (`spec.md` §5.4–5.5) once every import provision has a BL parent. |
| **D13** | **BIM moves into this app** as structured columns with a per-customer template behind it. The legacy free-text BIM block stays in the ERP but is never read (`open-questions.md` O2). |
| **D14** | **Nothing is hardcoded**: HS tariffs, preference schemes, kurs pajak, transit time, document types, SKB, duty rounding rules and both PPN rates are master data or parameters. |
| **D15** | The import module's acceptance test is **10 issued PIBs reproduced to the rupiah**, not agreement with the budget spreadsheet. |
| **D16** | The masters are **accelerators, not prerequisites**: `ship_hs_tariff` may be empty at go-live and every BM% typed by hand. Waiting for a complete BTKI would block the module indefinitely. |

Still to settle for the front office:

| # | Topic | Default | Settle in |
|---|---|---|---|
| **D17** | ~~Should the front office get its own module `CLAUDE.md` section~~ | **SETTLED 2026-09-10 as its default: one Shipment section with a front-office subsection.** The two halves share the tariff service, the account resolver, the calculator and the parameter store, and a reader who needs one usually needs the other. Two sections would duplicate those four and then drift. (Not to be confused with `open-questions.md` D17, the kurs pajak question — also answered that day, and unrelated.) | done |
| **D18** | `ShipPibEstimator`: one class with a mode branch, or two strategies behind an interface | one class, mode chosen from the incoterm and **not** injectable — the whole point is that the user cannot pick the wrong mode (`spec.md` §11.7) | T044 |
| **D19** | Notification fan-out: the sweep command or model events | the sweep command; ETA-based triggers are date arithmetic against a set, not a reaction to a save | T052 |

Everything else the front office still needs answering lives in `open-questions.md` (blocks S, D, F, O)
— it is the single register for both modules, and `tasks.md` T037 clears the ones that block P6.

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

**Exim Front Office:**

| Phase | Content | Exit criteria |
|---|---|---|
| **P6 — Front-office foundation** | The six new masters + their CRUD pages, the additive Shipment Control migration, the 17 transaction tables, enums, models, DTOs, factories, permissions | `migrate` green on Oracle and SQLite; the masters are usable and seeded with the rows actually in use today; `EximFixture` builds a BL and an SI |
| **P7 — Import BL** | BL input (3 levels), PO pull with remaining quantity, `ShipBmResolver`, `ShipPibEstimator`, kurs pajak + SKB, document checklist, payment requests, EMKL commitment → provision | **The 10-PIB regression is green**; a BL estimates, submits and clears; a payment request runs `DRAFT→SENT→PAID` with its variance |
| **P8 — Export SI** | BIM + template + ESC rate pull, BIM migration upload, SI input from multiple BIMs, cost grid, per-line approval, cancel screen, provision posting per period, period guard | An SI built from 2 BIMs posts a baseline provision at its EIN date and a second provision months later for a demurrage line approved then |
| **P9 — Notifications & hardening** | The sweep command, the four notifications, the front-office dashboard, the variance reports, retiring the transitional EMKL↔PIB rules | Each notification fires once per trigger with per-country lead days; the escalation carries the exposure figure; `spec.md` §5.4–5.5 and their tests are deleted |

P7 is the heavy one and its risk is concentrated in a single class: `ShipPibEstimator`. Build and prove
it against the 10 PIBs **before** any BL screen exists — then the screens are only wiring, and a wrong
number can only be a wiring bug.

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
- **Ship the additive migration early.** File 40 (`schema.md` §12) is nullable columns and defaulted
  flags — no data change, no downtime. Releasing it during P1 decouples the two tracks completely, and
  it is what `PRD EXIM.md` §11 means by "can be prepared now".
- **Fix the tariff semantics before either track prices anything.** `TIER` is progressive, `X` becomes
  `RATE`, `FIX` bands may overlap, and a no-match is a flagged zero (`spec.md` §4.1). Both modules call
  the same service, so this is settled once, in T011, with the Jasindo and Jasco cases as tests.
- **Estimator before screens, as calculator before screens.** Same rule as P1, same reason, higher
  stakes: `ShipPibEstimator` is checked against paper, so it is the one class in the module that can be
  proved right rather than merely reviewed.
- **Retire the transitional rules deliberately.** `spec.md` §5.4–5.5 die in P9 as their own task, not as
  a side effect of the BL work, and the copy logic, the mismatch notice and the two feature tests go
  together. A half-retired patch is worse than either state.

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

Front office, as a share of its own delivery (it is roughly the same total size as P0–P5, so do not read
these against the table above):

| Phase | Tasks | Weight |
|---|---|---|
| P6 | T037–T043 | ~25% |
| P7 | T044–T050 | ~35% |
| P8 | T051–T055 | ~30% |
| P9 | T056–T058 | ~10% |

`ShipImportFileInput` (T047) and `ShipExportSiInput` (T053) are the heavy screens here — three nested
levels and a multi-document merge respectively — but the task most likely to overrun is **T044,
`ShipPibEstimator`**, and overrunning there is money well spent. It is a pure class with paper to check
against; every day spent getting it exactly right is a day not spent reconciling estimates that are
quietly 9% high.

---

## 5. Testing strategy

| Level | What |
|---|---|
| Unit | calculator (every branch), tariff resolution (progressive `TIER`, overlapping `FIX`, flagged no-match), account resolver, status transitions, parameter casting |
| Unit (front office) | `ShipPibEstimator`, `ShipDutyRounding`, `ShipBmResolver`, chargeable weight, transit-time resolution, BIM merge rules |
| Feature | one happy path per direction per document; validation matrices; permission gating; duplicate guards; unbilled filter; multi-provision merge; the transitional EMKL→PIB copy (until P9 deletes it) |
| Feature (front office) | BL → estimate → submit → clear; remaining-quantity on a split PO; generated checklist; expired SKB refused; multi-BIM merge conflicts; per-line approval on a shipped SI; two provisions from one SI in different periods; closed-period refusal; cancel with partial void |
| Feature (ERP) | voucher payload assertions against SQLite stand-ins for the ERP tables |
| Backfill | seeded legacy fixture → backfill → verification, asserting all V-checks; BIM upload mapping leaving unmappable fields **empty and flagged**, never guessed |
| Manual | the 20+20 spot check with Finance (PRD S2), and one real voucher per type compared to the package (PRD S3) |
| **Against paper** | the 10-PIB regression (`PRD EXIM.md` §12.1). This is the only suite in either module that checks the code against an external authority rather than against our own prior behaviour, which makes it the most valuable one here |

CI runs on SQLite, so every migration must be Blueprint-compatible and every trigger/view migration must
early-return on `sqlite`.

---

## 6. Documentation duties

- A **module-level `CLAUDE.md` section** for the Shipment domain (Finance has no module `CLAUDE.md` yet;
  create one modelled on `Modules/LcControl/CLAUDE.md`) — folder map, table/column prefixes, lifecycle,
  the ERP paths, and a Gotchas section. Written in P0 and updated in the same PR as any architectural
  change. This is what stops the next person re-scanning 20 files.
- Update `.docs-me/exim-shipment-control/*` whenever a decision here is settled — especially `schema.md`
  for D6, `design.md` §5 for D7, and `open-questions.md`, which is the **only** register of what is
  still unanswered. Closing a question means writing the answer into the document it blocks and moving
  its row to `open-questions.md` §6 with the date and who decided — not just saying so in chat.
- The front office adds three things the module `CLAUDE.md` must carry beyond the folder map: the
  **prefix registry** for both modules side by side, the **`PPN_RATE_EFFECTIVE` trap** (12% printed,
  11% calculated), and the **`TIER` is progressive** rule. Those three are what a newcomer gets wrong,
  and each is expensive.
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
