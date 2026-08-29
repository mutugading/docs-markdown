# PRD — Shipment Control (Export & Import / Exim)

| | |
|---|---|
| **Target** | `Modules/Finance` → `Shipment` domain |
| **Schema** | Write: MGTHRIS (`ship_*`) · Read: MGTDAT (Orion ERP) |
| **Replaces** | Legacy `mgthris` app, Shipping module (`app/Http/Livewire/Shipping/*`) |
| **Status** | Draft v1.0 — 2026-08-28 |
| **Author** | IT (Mike) |
| **References** | `design.md`, `spec.md`, `schema.md`, `gap-analysis.md`, `data-migration.md`, `plan.md`, `tasks.md` |

---

## 1. Background

Every inbound and outbound shipment carries cost that is not part of the goods price: freight,
EMKL/customs handling, insurance, agent commission, and — on the import side — the customs duties
(PIB: BM, PPN, PPh). Finance books that cost in two stages:

1. **Provision** — the estimated (accrued) cost per vendor, taken from a tariff master, booked as a
   journal voucher so the cost lands in the right period even before the vendor bills.
2. **Settlement** — the vendor's real bill, matched line by line against the provision, booked as a
   second journal voucher, then paid with a payment voucher.

The legacy implementation works but has become expensive to change:

- **Flex columns.** `sah_flex_1` means "SI No." on export and "Pabean No." on import; `sad_flex_4/5/6`
  and `sbd_flex_1..6` carry six different meanings each. Every report and every screen re-derives
  those meanings, and a change touches all of them.
- **Two flows in one table pair.** Export (`SHPEXP`) and import (`SHPARR`) provisions share
  `SHP_ARR_HEAD`, discriminated by a trans code, with per-direction behaviour hardcoded in PHP.
- **Hardcoded accounting.** Account numbers (`208023`, `108004/108005`, `206005`, `206029`, `401100`,
  `401110/401112`, `401130/401134/401141`, `100343`), the insurance formula (`110% × 0.0275%`, `+2` for
  CIF), the PPh advance rule and the per-cost-type voucher matrix all live in `if` branches spread over
  four ~1.5k-line Livewire components plus a shared trait.
- **PL/SQL dependency.** Voucher generation calls `pkg_gen_voucher_ship`, so it cannot be tested and
  every fix needs a database release.
- **Report queries in a CLOB.** The Bill and Pending Bill reports store their SQL in
  `MST_PARAMS.PARAM_VALUE_LONG` — deliberately, to avoid deployments — but that puts business logic
  outside version control.
- **No workflow guardrails.** The import settlement skips submit/revoke entirely, its `$approved` is a
  boolean while every other screen uses the 0–4 code, and duplicate invoice numbers are prevented only
  by an application-level query.

The new app already solved the same class of problem for LcControl: named columns, int-backed status
enums, Repository → Service → Livewire, operator-editable parameters, and ERP vouchers posted from PHP
into the ERP tables. This PRD applies that to Exim.

---

## 2. Goals

1. **Feature parity first.** Everything Finance does today on the four legacy screens is possible on
   the new ones, with the same numbers coming out. Parity is verified against a backfilled copy of real
   data (see `data-migration.md` §6).
2. **Named schema.** No flex columns. One column, one meaning, both directions.
3. **Configuration, not code.** Cost types, container types, activities, tariffs, posting accounts,
   customs facilities and the calculation parameters are master data an authorised user can edit.
4. **One lifecycle.** Both provisions and both settlements use the same status enum and the same
   submit → confirm → (pay) → close transitions, with permissions gating each step.
5. **Testable posting.** Voucher generation is PHP, runs on SQLite in CI, and is covered by Pest.
6. **Auditable.** Every state change and field change is in the activity log, as it is today.

### Non-goals

- Not rebuilding the **Arrival Input** wizard (legacy `ShipArrInput`). It is a separate data-entry front
  door; it stays where it is until the provision screen is live, then gets its own PRD.
- Not touching the ERP's own posting logic. We write the same rows `pkg_gen_voucher_ship` writes.
- **Phase 1 excludes the six Excel reports.** Their contracts are in `spec.md` §8 so the schema carries
  every column they need, but they ship in Phase 2.
- Not migrating the legacy `Merge PDF` helper (already dead code, Windows-only paths).
- No new approval hierarchy. Same single-confirm model as today.

---

## 3. Users

| Role | Does |
|---|---|
| **Exim / Export staff** | Creates export provisions from a sales contract (ESC), attaches EDNs and containers, pulls the tariff, submits. |
| **Exim / Import staff** | Creates import provisions (PIB / EMKL / SHIP), attaches POs and containers, enters or pulls cost, submits. |
| **Finance (AP)** | Enters the vendor bill against a confirmed provision, checks the variance, confirms, generates the bill voucher and the payment voucher. |
| **Finance supervisor** | Confirms provisions, amends confirmed documents, monitors pending bills. |
| **Master data owner (Finance)** | Maintains cost types, container types, activities, tariffs, posting accounts, parameters. |
| **IT** | Backfill, ERP posting, reports. |

---

## 4. Core concepts

- **Direction** — `EXPORT` or `IMPORT`. Replaces the legacy trans-code discriminator (`SHPEXP` /
  `SHPARR` / `EXPBILL` / `IMPBILL`) as the thing code branches on. Trans-code strings survive only as
  ERP references and in the backfill mapping.
- **Provision** (`ship_provision`) — one shipment's accrued cost. Header (shipment identity, customs or
  export documents, currency, bank) → invoices, one per vendor per cost type → cost lines.
- **Settlement / bill** (`ship_bill`) — one vendor bill. Either **PROVISION-sourced** (every line points
  at the provision line it settles, so the variance is computable) or **DIRECT** (standalone, carries its
  own containers and documents).
- **Cost type** — `PIB`, `EMKL`, `SHIP`, `INS`, `COMM`, … Master data that says which direction it
  belongs to, which vouchers it raises, whether it raises a payment request, and whether confirming it
  closes the provision (today: PIB and SHIP close, EMKL waits for the bill).
- **Tariff** (`ship_tariff`) — vendor × container type × activity, with a shape: `TIER` (bands consumed
  by container quantity), `FIX` (one band, quantity forced to 1), `FLAT` (single row, quantity capped).
- **Cost line** — activity × quantity × rate, plus PPN/PPh percentages, the four accounts it posts to,
  and the amounts in three currencies (line currency FC, USD LC, bank currency CC).
- **Settlement link** — `stc_spc_sys_id`: a bill cost line points at the provision cost line it settles.
  A provision line may be settled **once**; "still unbilled" is "no bill line points at me".

---

## 5. Functional requirements

### FR-1 — Provision list & search
Paginated, searchable list of provisions filtered by direction, status, cost type, date range, vendor
and document numbers (SI / EIN / Aju / BL / PIB / invoice). Row links to the detail page. Search covers
child document numbers (DO/EDN/PO/ESC/STA), as legacy did.

### FR-2 — Provision input (export)
1. Start blank, or **from a sales contract (ESC)** — copies customer, destination and the ESC reference.
2. Attach **DO/EDN rows**: list approved EDNs for the customer above the `EDN_START_NO` parameter,
   excluding EDNs already attached to another provision. First selected row seeds SI no., destination,
   PEB type and Inco Term.
3. Attach **containers** (type + quantity).
4. Add one **invoice per vendor per cost type** (`SHIP`, `EMKL`, `INS`, `COMM`).
5. **Pull the tariff** per invoice: `SHIP`/`EMKL` from `ship_tariff`; `INS` computed from the attached
   EDN value; `COMM` computed from the ESC agent terms (which also sets the invoice's vendor).
6. Edit lines, see per-line and per-invoice totals in FC / USD / cross-currency.
7. Save (draft), submit, revoke, confirm, amend, delete (draft/amended only).

### FR-3 — Provision input (import)
As FR-2, with:
- **Cost type drives the form.** `PIB` forces IDR + the customs bank, hides the qty/rate grid and shows
  the three duty columns (BM / PPN / PPh), and requires the **port** (Pabean code, picked from the port
  master) plus the Pelabuhan text. `EMKL` fetches the vendor bank and pulls the tariff immediately —
  **priced per port**, so it takes the port from the PIB provision with the same Aju no. and lets the
  user override it. Other types are hand-entered.
- **POs instead of DOs**: full PO list for admins and EMKL, valid-PO list (with PO value and available
  value) otherwise, plus a synthetic `SAMPLE` PO for sample goods with no PO.
- Customs identity on the header: Aju no. (14 chars), PIB no. (≤ 6 chars) + date, SPPB no., PL/BL no.
- **EMKL back-fills its PIB provision**: on save, the PIB provision with the same Aju no. receives the
  PIB no., PIB date and SPPB no. The port travels the other way, PIB → EMKL.
- A customs **facility** typed on a line that is not in the master is registered on the fly.

### FR-4 — Provision confirm & vouchers
Confirming validates that every line has an expense account and a provision account and that those
accounts exist in the ERP, then posts the journal voucher for the cost type
(`EPJV` export, `IPJV` import), stores the reference and the voucher date, and moves the status to
CONFIRMED — or to CLOSED for cost types flagged as closing (PIB, SHIP), which also raise their payment
voucher (`BPS` for PIB, `ADVP` for SHIP). A payment date is required only when a payment voucher is
actually raised, and is never stamped otherwise.

### FR-5 — Settlement input
1. Pick the source: **PROVISION** or **DIRECT** (locked once saved).
2. **PROVISION**: pick a confirmed provision **that still has unbilled lines**, optionally for one
   vendor across **several** provisions, and merge them into one bill — document numbers concatenated,
   containers and documents unioned. Each bill line is seeded from its provision line
   (`stc_provision_amount`, quantity 1, rate = provision amount) and keeps the link.
3. Enter the vendor's invoice no. + date + received date, due date, bank and Faktur Pajak.
4. See the **variance** per line, per invoice and per bill (`provision − bill`).
5. Save, submit, revoke, confirm, amend. Confirm posts the bill voucher (`EBJV` export, `IBJV` import).
6. **Generate payment** posts the payment voucher (`ADVP-EXP` export, `BPS`/`ADVP` import) against the
   bill's pay date and stores the reference.

### FR-6 — Faktur Pajak
Line-level Faktur Pajak no. + date, searchable from the ERP faktur view, appliable to every line of an
invoice at once, and required on any line carrying PPN before confirming.

### FR-7 — Duplicate & account guards
- A vendor invoice number may not repeat for the same vendor across settlements (enforced by a unique
  index **and** a friendly pre-save check).
- Expense / provision / PPN / PPh accounts are verified against the ERP chart of accounts before
  posting, including the `(main, sub, currency)` combination.

### FR-8 — Import dashboard
One row per Aju no. showing provision, payment voucher, bill and bill voucher — a full outer join of
provisions and bills, searchable and sortable, with counters for pending approval, confirmed today and
created today.

### FR-9 — Master data (CRUD pages)
Cost types, container types, **ports**, activities, tariffs, posting accounts, facilities, parameters.
All follow the standard master-CRUD recipe with export/import. The port master holds the three customs
offices we clear through — the Pabean code, the port name, and the pricing key the import tariff uses.

### FR-10 — Reports (Phase 2)
Shipment/EMKL, Custom (provision-vs-settlement, ~90 columns), Bill, Pending Bill (export + import),
Import Details, GRN Details. Contracts in `spec.md` §8. Queries live in PHP under version control, not
in `MST_PARAMS`.

### FR-11 — Audit
Every status change and every field-level change is written to the activity log with the trans no. in
the description, as legacy `useLog('Export')` / `useLog('Import')` did.

---

## 6. Status lifecycle

Same codes both directions, both documents (`spec.md` §2):

```
0 DRAFT ──submit──> 2 SUBMITTED ──confirm──> 3 CONFIRMED ──settle/pay──> 4 CLOSED (bill: SETTLED)
   ▲                     │                        │
   └──── 1 AMENDED <─────┴── revoke / amend ──────┘
```

Deleting is allowed at status ≤ 1. Editing is blocked above 1 until amended. Import settlements gain the
submit/revoke step they never had (see `gap-analysis.md` §4).

---

## 7. Success criteria

| # | Criterion |
|---|---|
| S1 | Every legacy `SHP_*` row is backfilled and reconciles: row counts per document, and the sum of `sad_fc_amt` / `sbd_fc_amt` per trans no. matches to the cent. |
| S2 | For 20 sampled provisions and 20 bills across both directions, the new screen shows the same totals, PPN, PPh, FC/LC/CC and variance as the legacy screen. |
| S3 | A voucher posted by the new code is byte-comparable (same accounts, amounts, doc-no series, flex fields) to one posted by `pkg_gen_voucher_ship` for the same input. |
| S4 | Changing an account number, an insurance rate or a cost type's voucher matrix needs no deployment. |
| S5 | `php artisan test --parallel` green on SQLite, including tariff resolution, amount calculation, status transitions and voucher payload building. |
| S6 | `vendor/bin/pint --test` clean; no `wire:navigate`; no `DB::` facade outside repositories. |

---

## 8. Risks

| Risk | Mitigation |
|---|---|
| Backfill mis-maps a flex column and silently corrupts history | Explicit mapping table (`gap-analysis.md` §2), reconciliation queries (`data-migration.md` §6), dry-run into a copy schema first, all-new column prefixes so a wrong name fails loudly |
| Legacy rows violate the new unique constraints (duplicate vendor invoice no.) | Pre-flight duplicate report, decided per row with Finance, constraints created **after** the backfill |
| Voucher posted twice (once by each app) during cut-over | Hard cut-over date; legacy screens made read-only on go-live; posting guarded by "voucher reference already set" |
| PHP voucher port drifts from the PL/SQL package | S3 comparison on a sample per voucher type before go-live; the package stays in the database as the reference |
| Cost-type behaviour that is really code, not config, gets forced into the master | Master carries flags for the behaviours legacy actually branches on; anything else stays in a service `match` and is documented in `spec.md` §5 |
