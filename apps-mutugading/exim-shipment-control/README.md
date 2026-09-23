# Exim — Shipment Control & Front Office doc set

Two deliveries for `Modules/Finance`, sharing one `Shipment` domain and one doc set:

1. **Shipment Control** — migrating the legacy **Shipping** module (export + import provision →
   settlement, the accounting side) out of the old `mgthris` Laravel app. A migration, with feature
   parity as goal number one.
2. **Exim Front Office** — the **new** operational module in front of it: BIM, the import BL file, the
   export Shipping Instruction, and payment requests. New development with new numbers, so there is no
   parity to prove — its acceptance test is agreement with real customs documents.

The dependency runs **one way**: the front office feeds provisions. Shipment Control never waits for it,
and all it carries for it is one additive migration (nullable FKs and a few flags).

Legacy sources (read-only reference, do not copy code):

- `/home/mike/Projects/mgthris/docs/prd/shipping/export-shipment-control.md`
- `/home/mike/Projects/mgthris/docs/prd/shipping/import-shipment-control.md`

## Documents

| File | What it answers |
|---|---|
| [`PRD.md`](PRD.md) | **Shipment Control**: why we migrate, who uses it, functional requirements, scope & non-goals, success criteria |
| [`PRD EXIM.md`](PRD%20EXIM.md) | **Front Office**: why the module exists, BIM as structured data, incoterm and transport mode as drivers, PIB estimation, the SI and its variance chain, what Shipment Control must change (§11). Written in Indonesian — it is the record of what the teams confirmed, in the words they confirmed it in |
| [`design.md`](design.md) | Architecture for both: folder map, models, repositories, services, Livewire pages, enums, DTOs, permissions, routes, testing |
| [`spec.md`](spec.md) | Behaviour contracts. §1–§9 Shipment Control (tariffs, amounts, validation, ERP posting, reports); §10–§13 the front office (lifecycles, BIM, PIB estimation and duty rounding, the SI, shared rules) |
| [`schema.md`](schema.md) | Table and column design, one section per table, with the rationale for every departure from legacy. §1–§12 Shipment Control, §13–§15 the front office |
| [`shipment_control_schema.sql`](shipment_control_schema.sql) | Oracle DDL for DBA review (advisory — the Laravel migrations are the source of truth). Covers Shipment Control only; the front-office DDL is the migrations |
| [`gap-analysis.md`](gap-analysis.md) | Legacy → new mapping, feature parity checklist, what is dropped, and the behaviour changes Finance needs to hear about (§4) |
| [`data-migration.md`](data-migration.md) | Full backfill of `MGTAPPS.SHP_*`: mapping, ordering, id map, validation, rollback. §10 is the separate BIM upload with assisted mapping |
| [`plan.md`](plan.md) | Phases (P0–P5 Shipment Control, P6–P9 front office), the decisions behind them, sequencing, testing strategy |
| [`tasks.md`](tasks.md) | Numbered tasks with acceptance criteria, grouped and dependency-ordered |
| [`open-questions.md`](open-questions.md) | **The single register of what is still unanswered**, both modules. `PRD EXIM.md` cites its ids directly |
| [`scratch.md`](scratch.md) | Early raw notes. **Largely superseded** by `PRD EXIM.md`; the questions it raised are resolved in `open-questions.md` §6 |

Reading order for Shipment Control: `PRD.md` → `schema.md` → `plan.md`.
For the front office: `PRD EXIM.md` → `spec.md` §10–§13 → `schema.md` §13–§15.
`spec.md` is the reference you keep open while coding, either way.

## Decisions already taken

**Shipment Control (2026-08-28):**

| # | Decision |
|---|---|
| **D1** | **Module placement:** inside `Modules/Finance`, as the `Shipment` domain folder in every layer (`app/Livewire/Transaction/Shipment/`, `app/Models/MgtHris/Transaction/Shipment/`, …). No new nwidart module. |
| **D2** | **Legacy data:** **full backfill migration** of every `SHP_ARR_*` / `SHP_BILL_*` row into the new tables. Reports read the new tables only. See `data-migration.md`. |
| **D3** | **Vouchers:** **PHP port** — post directly into the ERP tables (`FT_UNPOSTED_TRANS_HEADER/_DETAIL`, `FT_PAYMENT_HEADER` + `FS_PAYMENT` + `FT_PAYMENT_OTH_ACNT_DETAIL`) reusing LcControl's proven posting shape. `pkg_gen_voucher_ship` is **not** called. |
| **D4** | **Phase 1 scope:** export + import, provision → settlement, plus the import dashboard. The six Excel reports are **Phase 2**; their contracts are specified now so the schema supports them. |

**Exim Front Office (2026-09-02, with the import, export, sales, finance and tax teams):**

| # | Decision |
|---|---|
| **D11** | A **new module in front of** Shipment Control, not part of the migration. Separate PRD so parity stays verifiable; same `Shipment/` namespace so the tariff service, account resolver, calculator and parameter store are not duplicated. |
| **D12** | **Import anchors on the BL**, export on the **SI**. 1 BL = 1 AJU = 1 PIB = 1 supplier. That retires the EMKL↔PIB field-copy patch (`spec.md` §5.4–5.5). |
| **D13** | **BIM moves into this app** as structured columns with a per-customer template behind it. The legacy free-text BIM block stays in the ERP but is never read. |
| **D14** | **Nothing hardcoded**: HS tariffs, preference schemes, kurs pajak, transit time, document types, SKB, the duty rounding rules and both PPN rates are master data or parameters. |
| **D15** | The acceptance test is **10 issued PIBs reproduced to the rupiah**, not agreement with the budget spreadsheet. |
| **D16** | Masters are **accelerators, not prerequisites**: `ship_hs_tariff` may be empty at go-live and every BM% typed by hand. |

`plan.md` §1 carries D5–D10 and D17–D19, which are still open.

## Naming at a glance

- **Tables:** `ship_*` on `oracle_mgthris`.
  Shipment Control prefixes — provision `spv_ spi_ spc_ spk_ spd_`, settlement `stl_ sti_ stc_ stk_ std_`,
  masters `shc_ shk_ shp_ sha_ sht_ shf_ spa_ shr_`.
  Front office — BIM `sbt_ sbm_ sbn_ sbc_ sbf_`, import `sif_ sii_ sit_ sic_ sid_`,
  export `ses_ seb_ sec_ sek_ sen_ sed_`, payment request `spr_`,
  masters `shh_ shr2_ skp_ sdt_ stt_ ssk_`.
  **Every prefix is new** — none collides with a legacy `sah_/sai_/sad_/sac_/saph_/sbh_/sbi_/sbd_`
  prefix, and the two modules' sets do not collide with each other, so a mapping mistake cannot hide
  behind a familiar name. (`shr2_` is deliberately ugly because `shr_` is taken — see `schema.md`.)
- **Routes:** `dashboard/module-finance/transaction/shipment/*`, names
  `dashboard.module-finance.transaction.shipment.*`.
- **Permissions:** `finance-shipment-{area}-{action}`.

## Three things that are expensive to get wrong

Worth knowing before reading anything else, because each is a number nobody would notice was wrong:

1. **PPN is 11%, not the 12% printed on every PIB** (DPP *nilai lain* 11/12). Use 12% and every import
   estimate is ~9% high — on a Rp 7.5 billion BL, Rp 75 million, on every shipment. `spec.md` §11.7.
2. **`TIER` tariffs are progressive**: the quantity is consumed band by band and **each band produces its
   own cost line**. Take only the matching band and 10 containers price at Rp 3,500,000 instead of
   Rp 3,750,000, silently. `spec.md` §4.1.
3. **Duty rounding is three different rules** — BM up to thousands, PPN truncated to the rupiah, PPh's
   base down to thousands — and PPN is computed from the *unrounded* BM. `spec.md` §11.7.
