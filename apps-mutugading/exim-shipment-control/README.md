# Shipment Control (Exim) — migration doc set

Migration of the legacy **Shipping** module (Export + Import Shipment Control) from the old
`mgthris` Laravel app into `apps-mutugading`, as a new **Shipment** domain inside `Modules/Finance`.

Legacy sources (read-only reference, do not copy code):

- `/home/mike/Projects/mgthris/docs/prd/shipping/export-shipment-control.md`
- `/home/mike/Projects/mgthris/docs/prd/shipping/import-shipment-control.md`

## Documents

| File | What it answers |
|---|---|
| [`PRD.md`](PRD.md) | Why we migrate, who uses it, functional requirements, scope & non-goals, success criteria |
| [`design.md`](design.md) | Architecture: folder map, models, repositories, services, Livewire pages, enums, DTOs, permissions, routes |
| [`spec.md`](spec.md) | Behaviour contracts: tariff resolution, amount formulas, validation rules, ERP posting, ERP data sources, report contracts |
| [`schema.md`](schema.md) | New table + column design, one section per table, with the rationale for every departure from legacy |
| [`shipment_control_schema.sql`](shipment_control_schema.sql) | Oracle DDL for DBA review (advisory — the Laravel migrations are the source of truth) |
| [`gap-analysis.md`](gap-analysis.md) | Legacy → new mapping, feature parity checklist, what is dropped, what changes behaviour, open questions |
| [`data-migration.md`](data-migration.md) | Full backfill of `MGTAPPS.SHP_*` into the new tables: mapping, ordering, id map, validation, rollback |
| [`plan.md`](plan.md) | Phased implementation plan and the architectural decisions behind it |
| [`tasks.md`](tasks.md) | Numbered tasks with acceptance criteria, grouped and dependency-ordered |

Read `PRD.md` → `schema.md` → `plan.md` first. `spec.md` is the reference you keep open while coding.

## Decisions already taken (2026-08-28)

| # | Decision |
|---|---|
| **D1** | **Module placement:** inside `Modules/Finance`, as the `Shipment` domain folder in every layer (`app/Livewire/Transaction/Shipment/`, `app/Models/MgtHris/Transaction/Shipment/`, …). No new nwidart module. |
| **D2** | **Legacy data:** **full backfill migration** of every `SHP_ARR_*` / `SHP_BILL_*` row into the new tables. Reports read the new tables only. See `data-migration.md`. |
| **D3** | **Vouchers:** **PHP port** — post directly into the ERP tables (`FT_UNPOSTED_TRANS_HEADER/_DETAIL`, `FT_PAYMENT_HEADER` + `FS_PAYMENT` + `FT_PAYMENT_OTH_ACNT_DETAIL`) reusing LcControl's proven posting shape. `pkg_gen_voucher_ship` is **not** called. |
| **D4** | **Phase 1 scope:** export + import, provision → settlement, plus the import dashboard. The six Excel reports are **Phase 2**; their contracts are specified now so the schema supports them. |

## Naming at a glance

- Tables: `ship_*` on `oracle_mgthris`. Column prefixes: provision `spv_ spi_ spc_ spk_ spd_`,
  settlement `stl_ sti_ stc_ stk_ std_`, masters `shc_ shk_ shp_ sha_ sht_ shf_ spa_ shr_`.
  **Every prefix is new** — none collides with a legacy `sah_/sai_/sad_/sac_/saph_/sbh_/sbi_/sbd_` prefix,
  so a mapping mistake cannot hide behind a familiar name.
- Routes: `dashboard/module-finance/transaction/shipment/*`, names `dashboard.module-finance.transaction.shipment.*`.
- Permissions: `finance-shipment-{area}-{action}`.
