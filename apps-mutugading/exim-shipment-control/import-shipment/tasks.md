# tasks.md — Shipment Control

> Update `[TODO]` → `[DONE]` as each task lands, in the same commit.
> Commit: `feat(finance): [TXXX] <title>` · Every task ends with `vendor/bin/pint --dirty` +
> `php artisan test --parallel` green.
> Docs referenced by section number: `PRD.md`, `design.md`, `spec.md`, `schema.md`,
> `gap-analysis.md`, `data-migration.md`, `plan.md`.

## Progress

```
Phase 1 (P0–P4): [ ] 0 / 29
Phase 2 (P5):    [ ] 0 / 7
```

---

## P0 — Foundation

### [TODO] T001 — Settle the open decisions
**Refer:** `plan.md` §1, `gap-analysis.md` §6 · **Blocks:** everything
Answer D5–D10 and Q1–Q8 with Finance and the requester. Write the answers into the docs they affect
(`schema.md` for D6/Q1, `design.md` §5 for D7/D8, `spec.md` §5.3 for Q4).
**Acceptance:** no `plan.md` §1 row and no `gap-analysis.md` §6 row left unanswered; the docs are edited,
not just the chat.

### [TODO] T002 — Master migrations (8 tables)
**Refer:** `schema.md` §10, §12 (files 1–8) · **Blocks:** T003
`ship_port` comes before `ship_tariff` — the tariff FKs it.
Blueprint migrations on `oracle_mgthris` with `migrationKey` + `migrationDisabled()`, audit columns,
indexes. Applies D6's answer to `ship_cost_type`.
**Acceptance:** `php artisan migrate` green on Oracle and on SQLite in-memory; `migrate:rollback` clean;
`DISABLE_MIGRATIONS=ship_tariff` skips exactly that one.

### [TODO] T003 — Transaction migrations (10 tables + legacy map) + triggers + views
**Refer:** `schema.md` §1–9, §12 (files 9–21) · **Blocks:** T004
Blueprint tables; a separate Oracle-only migration for the sequences + `BEFORE INSERT` triggers
(early-return on `sqlite`); the three Orion views. The two unique guards (file 21) are **not** created
yet — they wait for the backfill (file 22).
**Acceptance:** `migrate` green on both drivers; on Oracle an insert without a PK gets one from the
trigger; `SELECT * FROM v_ship_vendor` returns rows.

### [TODO] T004 — Master seeder
**Refer:** `data-migration.md` §7, `spec.md` §6 · **Blocks:** T012
`ShipmentControlMasterSeeder`: cost types, container types, **ports**, activities, tariff import from
`SHP_MASTER_COSTS` with shape inference (printed for review) and a null `sht_port_code`, facilities,
posting accounts, parameters.
**Acceptance:** every account number in `data-migration.md` §7 verified against production and signed off
(record who and when in the seeder docblock); seeder idempotent; tariff shape inference report attached
to the PR; the three port rows seeded exactly as `data-migration.md` §7 lists them (`040300` Tanjung
Priok default, `060100` Tanjung Emas, `050100` Soekarno-Hatta / `AIR`), and every remaining distinct
`sah_flex_1` value from the frequency query either mapped to one of them or waived in writing.

### [TODO] T005 — Enums, models, permissions
**Refer:** `design.md` §2, §8, `spec.md` §2 · **Blocks:** T006
7 enums, 17 models (+3 view models) with connection, PK settings, casts, `$fillable`, `$searchable`,
relations, `Searchable` + `LogsActivityWithDescription`. Extend
`FinanceRolesAndPermissionsSeeder` with the `finance-shipment-*` permissions.
**Acceptance:** `ShipProvision::with('invoices.costs', 'containers', 'docs')->first()` works against a
factory-seeded row; `ShipStatusEnum` transition table matches `spec.md` §2; permissions seeded
idempotently.

### [TODO] T006 — Factories + fixtures
**Refer:** `design.md` §9 · **Blocks:** T007
Factories for all transaction + master tables, plus a `ShipmentFixture` helper that builds a realistic
export provision (2 containers, 2 invoices, 6 lines) and an import PIB provision.
**Acceptance:** `php artisan test` can build both fixtures on SQLite in under a second.

### [TODO] T007 — DTOs
**Refer:** `design.md` §3, `spec.md` §3 · **Blocks:** T008
Header / invoice / cost / container / doc DTOs for both documents, `Wireable`, with the conditional rule
sets from `spec.md` §3.1–3.2 and the blank-numeric normalisation rule.
**Acceptance:** Pest: each validation case passes valid data and rejects each required field; a blank
string in a numeric field does not throw.

### [TODO] T008 — Repositories (own tables)
**Refer:** `design.md` §1, §4 · **Blocks:** T009
Interfaces + Eloquent implementations for provision, bill, tariff, masters, posting accounts,
parameters. Bound in `FinanceServiceProvider` (or a `RepositoryServiceProvider` if Finance gains one).
Includes `unbilledCosts(provisionId)` and `unbilledCostsForVendor(vendorCode)`.
**Acceptance:** Pest: `unbilledCosts` excludes a line that already has a bill line; search covers the
relation columns listed in `design.md` §2.

### [TODO] T009 — ERP read repositories
**Refer:** `design.md` §5, `spec.md` §7.1 · **Blocks:** T011
Sales docs (ESC/STA/EDN/EIN), purchase docs (PO all / valid / SAMPLE), vendor + vendor bank + NPWP/SKB,
company banks, account existence + `(main, sub, currency)` check, Faktur Pajak list, exchange rate.
**Acceptance:** against production (read-only) each method returns rows for a known document; every one
is behind an interface so tests can fake it.

---

## P1 — Provision

### [TODO] T010 — ERP voucher posting (D7)
**Refer:** `design.md` §5, `spec.md` §7.2–7.3, `plan.md` §1 D7 · **Blocks:** T016
Promote (or copy, per D7) the journal + payment voucher repositories, their DTOs and
`ExchangeRateService`, then add the Shipment-specific payload builders.
**Acceptance:** Pest against SQLite stand-ins: period resolution failure raises a `DomainException`
naming the date; doc no. is `calYear+MM+4 digits`; JV refuses to post when DR ≠ CR; payment posts
header + one `fs_payment` + one `ft_payment_oth_acnt_detail` per line.

### [TODO] T011 — `ShipTariffService`
**Refer:** `spec.md` §4.1 · **Blocks:** T013
TIER / FIX / FLAT, tier groups, vendor `ALL` fallback, **port match**, effective dating,
`spc_tariff_sys_id` stamping.
**Acceptance:** Pest: 3 tier bands + qty 7 produce the expected per-band quantities; `FIX` forces qty 1;
`FLAT` caps at `sht_max_qty`; an expired tariff is not used; import falls back to `ALL`; a row for the
provision's port wins over the any-port row, an any-port row is used when no per-port row exists, and a
call with no port code returns only any-port rows and reports the gap.

### [TODO] T012 — Master CRUD pages (8)
**Refer:** `design.md` §6, root `CLAUDE.md` master-CRUD recipe · **Blocks:** —
Cost types, container types, ports, activities, tariffs, facilities, posting accounts, parameters —
list + create/edit + delete + export, routes, breadcrumbs, permission `finance-shipment-master-manage`.
**Acceptance:** each page CRUDs a row; tariff page can clone a row (tiers are edited as a set) and its
port column shows "Any port" for a null; a port row that any tariff or document references cannot be
deleted, only deactivated; parameter page validates by `shr_data_type`; breadcrumbs give each page its
tab title.

### [TODO] T013 — `ShipCostCalculator` + `ShipAccountResolver`
**Refer:** `spec.md` §4.2–4.4, §5.3 · **Blocks:** T014
Pure classes. Line amounts, IDR conversion of PPN/PPh, rounding by currency, cross-currency, PPh
advanced, PIB duty branch, rollups; account resolution with the 9-step order.
**Acceptance:** Pest against figures taken from **real** legacy documents (attach the source trans nos.
in the test docblock): 6 export lines and 4 import lines reproduce legacy's stored amounts exactly.
Resolver: each of the 9 steps is hit by a test, including the `PPN_1_1` and `HAS_PPH` conditions.

### [TODO] T014 — `ShipProvisionInput` (both directions)
**Refer:** `design.md` §6, `spec.md` §3, §5.1, §5.4–5.5 · **Blocks:** T015
One component. Header panel driven by the cost-type master flags; port picker on import; container
editor; EDN picker (export) / PO picker (import); invoice tabs; cost grid with the duty-column switch;
tariff pull; insurance and commission buttons; save in one transaction; field-level activity log.
Includes the two Aju-linked copies: EMKL → PIB (PIB no. / date / SPPB) and PIB → EMKL (port).
**Acceptance:** an export provision from an ESC and an import PIB provision can both be created and
re-opened with identical values; validation messages appear per `spec.md` §3.2; save is one transaction
(assert with a forced failure mid-save leaving nothing behind); entering an Aju no. on an EMKL provision
pre-fills the port from the matching PIB provision, the field stays editable, an EMKL provision with no
port is refused at tariff pull and at submit with a message naming the Aju no., and an already-priced
EMKL provision is not silently re-pointed when its PIB provision changes port — it shows the mismatch.

### [TODO] T015 — `ShipInsuranceService` + `ShipCommissionService`
**Refer:** `spec.md` §4.5–4.6 · **Blocks:** —
**Acceptance:** Pest with a faked EDN repository: CIF adds the parameter addon, non-CIF does not; the
`INSURANCE` / `INSURANCE-INL` mismatch is rejected. Commission: `Q` and `R` bases both computed, and the
invoice vendor is switched to the agent.

### [TODO] T016 — Provision workflow + vouchers
**Refer:** `spec.md` §2, §7.2–7.5 · **Blocks:** T017
`ShipStatusService`, submit / revoke / confirm / amend / delete, the confirm-time account checks, the
journal voucher, the payment voucher for cost types that raise one, `shc_closes_on_confirm` → status 4,
re-post guard, activity log.
**Acceptance:** Pest: each transition allowed only from its listed states and with its permission;
confirm without an Orion id fails; a PIB provision lands on status 4 with a payment voucher; an EMKL
provision stays at 3 with **no pay date**; posting twice is refused.

### [TODO] T017 — `ShipProvisionList` + `ShipProvisionDetail`
**Refer:** `design.md` §6–§7 · **Blocks:** T019
List with the filter set and pagination; detail page with the workflow buttons, voucher panel, print,
and the child tables read-only.
**Acceptance:** filters combine correctly; deep link by trans no. works; a missing trans no. 404s;
buttons hidden without the permission and refused server-side too.

---

## P2 — Settlement

### [TODO] T018 — Provision picker (single + multi)
**Refer:** `spec.md` §3.3, `design.md` §6 · **Blocks:** T019
Modal listing confirmed provisions **with unbilled lines**, plus the by-vendor mode that lists unbilled
lines across provisions and merges the ticked ones (document numbers concatenated, containers and docs
unioned).
**Acceptance:** Pest: a fully billed provision is absent; merging two provisions produces one bill whose
lines keep their own `stc_spc_sys_id`; `stl_si_no` holds both SI numbers.

### [TODO] T019 — `ShipBillInput`
**Refer:** `design.md` §6, `spec.md` §3–§4 · **Blocks:** T020
Source toggle (locked after save), header from the provision (read-only) or hand-entered for DIRECT,
invoice cards with received date and Faktur Pajak, cost grid with the variance column, one-transaction
save, duplicate-invoice pre-check.
**Acceptance:** a PROVISION bill and a DIRECT bill both save and re-open; variance matches
`provision − bill` per line, invoice and header; the duplicate check names the conflicting bill.

### [TODO] T020 — Faktur Pajak picker
**Refer:** `spec.md` §3.2 (`confirm`), `design.md` §5 · **Blocks:** —
Searchable list from `mgt_bill_fp_scan_v`, apply to every line of an invoice, auto-fill the date when a
known number is typed.
**Acceptance:** applying fills all lines; a PPN line without a faktur blocks confirm.

### [TODO] T021 — Bill workflow + vouchers + provision close
**Refer:** `spec.md` §2, §7 · **Blocks:** T022
Submit / revoke / confirm / amend, bill journal voucher, generate payment, and
`closeIfFullySettled()` on the provision.
**Acceptance:** Pest: confirming the last bill of a provision moves the provision to 4; a bill's pay date
is only set by the payment path; import bills now require submit before confirm.

### [TODO] T022 — `ShipBillList` + `ShipBillDetail`
**Refer:** `design.md` §6 · **Blocks:** —
Includes the pay-status filter (PAID / UNPAID / OVERDUE) the Bill report will reuse.
**Acceptance:** OVERDUE = no pay date and a past due date; counts agree with a hand-written query.

### [TODO] T023 — Print (transaction + voucher PDFs)
**Refer:** `spec.md` §7.4 · **Blocks:** —
**Acceptance:** a provision, a bill, a journal voucher and a payment voucher each render a PDF with the
document's identity and totals.

---

## P3 — Dashboard, backfill, hardening

### [TODO] T024 — `ShipmentDashboard`
**Refer:** `design.md` §6, legacy import dashboard · **Blocks:** —
One row per shipment key (Aju no. for import, SI no. for export) showing provision, provision voucher,
payment voucher, bill and bill voucher; sortable, searchable, paginated; counters for pending approval,
confirmed today, created today; a panel for the missing-voucher check.
**Acceptance:** a provision without a bill and a direct bill without a provision both appear; counters
match hand-written queries.

### [TODO] T025 — Missing-voucher repair command
**Refer:** `spec.md` §7.5 · **Blocks:** —
Finds documents whose payment voucher does not exist in `ft_payment_header`; clears the reference (with
its stamps) so it can be regenerated; logs every change.
**Acceptance:** dry-run lists, real run clears, activity log shows who and what.

### [TODO] T026 — Pre-flight query pack
**Refer:** `data-migration.md` §5 · **Blocks:** T027
An artisan command running all nine checks against the legacy connection and writing one report.
**Acceptance:** run against production; RED findings tabled with Finance; the report is attached to the
cut-over ticket.

### [TODO] T027 — Backfill command
**Refer:** `data-migration.md` §1–§4 · **Blocks:** T028
Chunked, idempotent, `--dry-run` / `--resume` / `--rollback`, own log channel, `withoutEvents`, id map,
sequence advance, rollup recomputation with mismatch reporting.
**Acceptance:** Pest on a seeded legacy fixture: full run then `--resume` inserts nothing; `--rollback`
restores an empty target; flex columns land in the right direction-specific columns.

### [TODO] T028 — Verification command + unique guards migration
**Refer:** `data-migration.md` §6, `schema.md` §12 file 21 · **Blocks:** T029
All ten V-checks with a pass/fail summary, plus the unique-constraints migration that runs after a green
backfill.
**Acceptance:** V1–V8 and V10 automated and green on a production copy; V9 signed off by Finance;
the guards migration succeeds only on clean data and says which rows block it otherwise.

---

## P4 — Cut-over

### [TODO] T029 — Cut-over
**Refer:** `data-migration.md` §8 · **Blocks:** P5
Runbook executed, permissions granted, legacy screens read-only, daily verification for 30 days.
**Acceptance:** verification green on day 1 and day 7; no Finance-reported discrepancy open.

---

## P5 — Reports (Phase 2)

Each report = filter modal (Livewire) + queued job + export class + notification, per `spec.md` §8.

### [TODO] T030 — Report scaffolding
Shared filter trait (period defaults, ordered date pair, `resetFilters()`), the queued-job + download-link
notification wiring, the all-digit-as-text value binder.
**Acceptance:** one trivial report end to end: filters → job → file → notification with a working link.

### [TODO] T031 — Shipment / EMKL report
### [TODO] T032 — Bill report (both directions)
### [TODO] T033 — Pending Bill report (Exim, both directions)
### [TODO] T034 — Custom provision-vs-settlement report (export, ~90 columns)
### [TODO] T035 — Import Details report (columns A→CV, banded styling)
### [TODO] T036 — GRN Details report

**Acceptance for each:** run for a period that predates cut-over and diff the sheet against the legacy
output — same rows, same values, same column order. Any deliberate difference is listed in the PR.
