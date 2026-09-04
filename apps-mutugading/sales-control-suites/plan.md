# plan.md — implementation plan

Derived from `PRD-sales-control-suite.md`, `schema.md`, `design.md`, `spec.md`, `verification.md`.
Aligned to the root `CLAUDE.md`: modular monolith, Repository → Service → Livewire, Oracle via
`yajra/laravel-oci8`, Spatie Permission, Pest 4, Pint before every commit.

Scope of this plan is **Phase 1 only** — minimum price control. Phases 2 and 3 are §5.

---

## 1. Non-negotiables

From the root `CLAUDE.md`, repeated because each one has bitten this repo: no `wire:navigate` /
`navigate: true`; no `DB::` facade outside repositories and the gateway; no `env()` outside config;
no `dd()`/`dump()`; `{{ }}` not `{!! !!}`; `wire:key` in loops; UI-module components before Flux
before custom; `vendor/bin/pint --dirty` before every commit; migrations SQLite-compatible because CI
runs on SQLite.

From this doc set, and just as binding:

- **The gateway is the only path to price logic** (D2, `design.md` §4). No minimum-price resolution
  and no FX arithmetic in PHP, in any layer, ever — including "just for the preview".
- **`SALES_MIN_PRICE_CHECK_LOG` is read-only** (D4) — and after the schema move, only the model guard
  says so (`schema.md` §1.1).
- **Marketing never types a price** (D8).
- **The trigger is deployed last** (D10).

---

## 2. Phases

| Phase | Content | Exit criteria |
|---|---|---|
| **P0 — Verify & land the DDL** | `verification.md` §1 items 1–7. Answer Q1–Q3 and **Q10**. Get Indra's review of the reworked `minimum-price-control.sql` (`schema.md` §9), then run A → B → C → F1 → D → F2 and prove F1 with smoke test G0 | Written, dated answers in `verification.md`; the package compiles `VALID` and G0 passes. If item 1 or 3 comes back against the assumption, it is fixed in the same pass |
| **P1 — Foundation** | Migrations, models, enums, DTOs, repositories, the gateway + its fake, sys-id and request-number generation, permission seeder | `php artisan migrate` clean on SQLite and on the Oracle test schema; repository and generator tests green (tests 15, 16, 23) |
| **P2 — Min price master** | `MinPriceService` (overlap, supersede), `MinPriceManager`, Excel import/export + jobs | Tests 1–6 green; a rule can be drafted, imported, superseded and listed |
| **P3 — Requests & documents** | `ApprovalRequestService` state machine, `ApprovalDocumentService` (PDF + attachment + hash), `MinPriceApprovalManager` | Tests 7–14 green; a batch of drafts prints, uploads and goes live end to end |
| **P4 — Preview & exceptions** | `PricePreviewService`, `PriceCheckPreview`, `PriceExceptionRequest`, `PriceExceptionApproval` | Tests 17–21 green; against the Oracle test schema the preview's verdict matches `P_VALIDATE_SO`'s log rows for the same document, line for line |
| **P5 — Reports** | Four reports + their exports and jobs, the log channel | Tests 22, 24 green; each export tallies with its screen |
| **P6 — UAT & cut-over** | Sidebar entries, roles assigned to real people, master seeded and approved on production, release note, hand-over to Finance and Marketing | Finance has approved a real price list and Marketing has taken a real exception through print → sign → upload, with the trigger still off |
| **P7 — Trigger** *(separate change)* | `minimum-price-control.sql` section E on production, watched for a week | An SO below floor is blocked with message 1012110; an approved exception lets the same SO through; the log has a row per line |

P3 before P4 on purpose: the exception request is a request first and a price thing second, and
building the exception page before the state machine means writing the transitions twice. P5 last
because a report of an empty table teaches nothing.

P7 is a **separate PR with its own approval**, not the tail of P6. It is the change that can stop the
company selling.

---

## 3. Risks

| Risk | Handling |
|---|---|
| **Cross-schema grants given through a role** — the package compiles, then every in-scope SO approval fails with ORA-00942 | `verification.md` §1 check 5c reads `USER_TAB_PRIVS_RECD`, which shows direct grants only; smoke test G0 runs as MGTDAT before anything else. Both are in P0, not in the deploy |
| The read-only log guard lost with the schema move (`schema.md` §1.1) | Model guard + repository shape + test 23, and a docblock saying the guard is now the only thing there |
| FX direction assumption wrong (`verification.md` §1.1) | P0 blocks on it; the package's 1 000–100 000 sanity check turns a wrong assumption into a loud failure rather than a floor everything passes |
| Price logic quietly reimplemented in PHP — the slow, likely failure | The gateway interface exposes no arithmetic; the test fake returns fixtures only; P4's exit criterion is agreement with `P_VALIDATE_SO` on real documents |
| STA→ESC parent mapping wrong | P0 item 3; until answered the preview labels inherited lines explicitly and the report counts them separately, so a wrong assumption shows up as user confusion, not as a wrong approval |
| Trigger turned on before the master is populated | P7 is separate and gated on step 8 of `verification.md` §4. An empty master is harmless; a half-entered one is not |
| Nobody can approve — one approver on leave | Q2 requires at least two holders per role; D9 makes it a role, not a user |
| The preview disagreeing with the trigger for any reason | Same code path by construction; P4's exit criterion measures it on real documents rather than trusting the construction |
| Log table growth | Reports take a required date range; `NX02 (CR_DT, RESULT)` supports it. Revisit partitioning with the DBA if it passes a few million rows |
| Oracle OUT-parameter binding proving fragile across driver versions | Isolated in one class with one integration test against the Oracle test schema; a driver change breaks one file |
| Two apps' worth of assumptions about `IM_VS_STATIC_VALUE` | Read at runtime, one repository method, config fallback only |

---

## 4. Testing

Pest 4, `tests/Feature/Finance/SalesControl/` (D11 — `phpunit.xml` only scans `tests/`). The 24 cases
in `spec.md` §9 are the minimum.

CI is SQLite, so:

- the gateway is bound to `FakePriceControlGateway` in tests, returning fixtures and never arithmetic;
- migrations skip the Oracle CHECK constraints and the sequences (`schema.md` §6, §7.1);
- `Tests\TestCase` already transacts every Oracle connection, so both `oracle_mgthris` and
  `oracle_mgtdat` are covered — that came from CI Project and does not need redoing.

What SQLite cannot test — the resolution ranking, the FX cascade, the exact-match binding, the trigger
— is covered by the SQL smoke tests in `verification.md` §2, run by hand and recorded there. Say this
in the test file's header comment so the gap is deliberate rather than discovered.

One integration test lives outside CI: `OraclePriceControlGateway` against the Oracle test schema,
skipped unless the connection is reachable. It is the only proof the OUT-parameter binding works.

---

## 5. Phases 2 and 3 — what to remember when they come

Outlines only, from PRD §5–§7. Nothing here gets built now.

**Phase 2 — overdue delivery.** No new trigger. Add `AND NOT EXISTS (active exception)` to the
existing `raise_application` in `ODBTRG_WAVE_CUST_OVERDUE`. The exception attaches to the customer as
a **time window**, because the trigger fires before the wave exists and there is nothing per-document
to attach to. Migration: fewer than 5 customers hold `CUST_FLEX_18 = 'Y'`; convert each to a dated
exception and set the flag back to `'N'` — a policy change, so Finance approves it first. That trigger
runs three cursors on every insert and update with no status filter, and `C1` is heavy; if it is
opened at all, put a guard in front.

**Phase 3 — credit limit.** The exception is a **dated top-up**, not a per-document pass:
`CCO_CREDIT_LIMIT + SUM(SCAR_AMOUNT of active exceptions)`. The ERP master is never touched, so the
limit returns to normal by itself. Windows are short, 7–14 days. Note that a top-up applies to every
shipment for that customer in the window — the amount and the dates are the whole control.

Its prerequisite is a `SOH_FLEX_18 → SOH_CUST_CODE` migration touching `OT_SO_HEAD` and
`OT_INVOICE_HEAD` plus their history. **That is its own project with its own data verification.** Do
not fold it into anything else: getting it slightly wrong means customer outstanding is wrong, which
means shipments blocked or released for the wrong reasons.

**Cross-phase.** Once both are live, three triggers react to `WWR_STATUS = 3` on `OT_WMS_WAVE_REF`
(CBD, credit limit, overdue). Oracle 11g does not guarantee the firing order of same-type triggers, so
a wave breaking two controls can report either one, and not always the same one. Not a bug; say so
when a user asks. And follow the house pattern — call
`ODBPROC_PRAGMA_DEL_WAVE_MGT(:NEW.WWR_WWH_SYS_ID)` before raising, so no wave is left hanging.
