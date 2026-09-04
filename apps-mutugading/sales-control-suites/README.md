# Sales Control Suite — development doc set

A shared request-and-approval flow for three ERP controls that today are bypassed by editing master
data with no record of who decided what, or why. Built as the **SalesControl** domain inside
`Modules/Finance`.

**The four new tables live in `MGTHRIS`** (`oracle_mgthris`, the app's default connection —
`DB_CONNECTION=oracle_mgthris`): `SALES_CTL_*` for the approval flow all three controls share,
`SALES_MIN_*` for what belongs to minimum price alone. **`MGTDAT` is read-only**: the app reads
Orion's sales orders, the transaction-code list and the exchange rates from it, and never writes a row
there.

**Phase 1 — minimum price control — is the buildable scope.** Phases 2 and 3 are outlines only.

Sources (read these first, they are the input to everything here):

| File | What it is |
|---|---|
| [`PRD-sales-control-suite.md`](PRD-sales-control-suite.md) | Indra's product brief. Background, design principles, all three phases, deploy order |
| [`minimum-price-control.sql`](minimum-price-control.sql) | The Oracle side, ready to deploy: 4 tables, their `HM_MST_SEQUENCES` rows + sys-id triggers, config + message registry, `PKG_MGT_PRICE_CTRL`, the trigger, grants, smoke tests |

The Oracle half is **already written and owned by Indra**, and the schema move described here is a
change to it. `schema.md` §9 lists the exact edits `minimum-price-control.sql` needs — table names,
owning schema, and the cross-schema grants the package now depends on. That file is not edited from
this repo; take §9 to Indra.

## Documents

| File | What it answers |
|---|---|
| [`schema.md`](schema.md) | The four MGTHRIS tables, what each schema owns, the Laravel migrations, id and request-number generation, **and §9: the exact edits `minimum-price-control.sql` needs** |
| [`design.md`](design.md) | Folder map, models, repositories, services, the Oracle package gateway, Livewire pages, routes, permissions, config, storage |
| [`spec.md`](spec.md) | Behaviour contracts: the two state machines, price-preview rules, revision handling, overlap validation, attachments, reports, and the test list |
| [`verification.md`](verification.md) | The six pre-deploy checks from PRD §4.8 written as runnable SQL, plus the open questions and who answers them |
| [`plan.md`](plan.md) | Phased implementation plan, risks, testing approach |
| [`tasks.md`](tasks.md) | Numbered, dependency-ordered tasks with acceptance criteria |

Read `schema.md` → `design.md` first. `spec.md` is the one you keep open while coding.
`verification.md` blocks everything: **P0 runs before a single migration is written.**

## The shape of the thing, in one paragraph

Finance registers minimum prices per item/grade as a **master with maker-checker** — drafted, printed
on a numbered form, signed by BOD, scan uploaded, then and only then `APPROVED` and live. An Oracle
trigger on `OT_SO_HEAD` blocks approval of an ESC/LSC/STA sales order whose lines sit below the
approved floor, and writes every check — pass or fail — to an audit log. When Marketing needs to sell
below the floor, they open the SO in the app, the app **shows them which lines are under and by how
much**, they tick lines and give a reason, and the same print → sign → upload → approve flow produces
an exception that the trigger honours for exactly that SO line at exactly that rate and quantity.

## Decisions taken

Where the PRD already decided, this repeats it. Where it left a choice to the build, this is the call
and the reason. Anything still open is in `verification.md` §3, not here.

| # | Decision |
|---|---|
| **D1** | **Placement:** the `SalesControl` domain folder inside `Modules/Finance`, in every layer. No new nwidart module — this is finance policy, and Finance already has models on both connections this needs (`MgtFpScan` on `oracle_mgtdat`, the raw-material models on `oracle_mgthris`). |
| **D2** | **Oracle owns the rules; Laravel owns the paperwork.** `PKG_MGT_PRICE_CTRL` is the single implementation of FX conversion and rule resolution. Laravel calls it and never re-derives a minimum price, a divisor or a verdict in PHP. This is PRD §4.7 and it is the decision most likely to be quietly broken later. |
| **D3** | **The four new tables belong to Laravel, so they live in `MGTHRIS` and Laravel migrations create them.** They are this app's tables: the app writes every row, and MGTHRIS is the connection it writes on. `MGTDAT` holds Orion's data and stays read-only. The migrations still carry the `hasTable()` guard, so a schema where the DBA got there first is left alone. |
| **D4** | **`SALES_MIN_PRICE_CHECK_LOG` is read-only to Laravel.** The trigger writes it; the app reports on it. The price-preview page must not insert a row — a preview is not a decision, and log rows that no approval attempt produced would poison the spot-check report. Note that after D3 this is **code-enforced only**: MGTHRIS owns the table, so no missing grant backs it up any more (`schema.md` §1.1). |
| **D5** | **Two request types, two number series, one table.** The min-price *master* approval is `SCAR_CTRL_TYPE = 'PRICELIST'`, numbered `MPL-YYYY-NNNN`. The per-SO-line *exception* is `SCAR_CTRL_TYPE = 'MINPRICE'`, numbered `MPO-YYYY-NNNN`. Easy to get backwards; the enum is the guard. |
| **D6** | **A price change is a new row, never an update.** Superseding an approved rule closes the old row (`SMP_VALID_TO = new valid_from − 1 day`) and inserts a new `DRAFT`. The audit log has to be reconstructable years later, and an in-place edit makes an old decision unexplainable. |
| **D7** | **A revision is a new row too.** Editing a `PRINTED` request writes a new row with the same `SCAR_REQ_NO`, `SCAR_REVISION + 1`, status `DRAFT`, and voids the previous revision. `SALES_CTL_APPR_REQUEST_UK01` on `(REQ_NO, REVISION)` is what makes this safe. |
| **D8** | **Marketing never types a price.** The exception form takes rate and quantity from `OT_SO_ITEM` through the package, and Marketing supplies only the ticked lines and the reason. A typed price would drift from the SO and the trigger's exact-match rule would silently reject the approval nobody knew was wrong. |
| **D9** | **The approver is a role, not a person** (PRD §4.7). More than one holder, always — the alternative is every sales order in the company stopping when one person takes leave. |
| **D10** | **The trigger goes last.** It is deployed after UAT, in its own change. Until Marketing has a way to request an exception, the trigger is a wall with no door. |
| **D11** | **Tests live in `tests/Feature/Finance/SalesControl/`**, not `Modules/Finance/tests/` — `phpunit.xml` only scans `tests/`, so a suite under `Modules/` never runs. Learned the hard way on CI Project. |
| **D12** | **Permissions follow the house shape**, `finance-{domain}-{action}` with underscores: `finance-min_price-*` and `finance-price_exception-*`. The PRD's `min-price.draft` spelling is not what Spatie is fed anywhere else in this repo. |
| **D13** | **Two prefixes, by lifetime, in `MGTHRIS`.** `SALES_CTL_APPR_REQUEST` and `SALES_CTL_APPR_LINE` are the approval flow all three controls share; `SALES_MIN_PRICE` and `SALES_MIN_PRICE_CHECK_LOG` are minimum-price only. Sequence rows follow their tables. `APPR`, not `APPROVAL`, because 11g caps identifiers at 30 bytes — `schema.md` §0. |
| **D14** | **The package reaches across schemas.** `MGTDAT.PKG_MGT_PRICE_CTRL` now reads three MGTHRIS tables and writes one. That needs **direct** grants from MGTHRIS to MGTDAT — a role will not do, PL/SQL under definer's rights ignores role privileges — plus schema-qualified references or synonyms. `schema.md` §9. This is the single most likely thing to break the deploy. |
| **D15** | **Column prefixes follow the table name**, per house convention: `SCAR_`, `SCAL_`, `SMP_`, `SMPCL_`. `minimum-price-control.sql` has been rewritten accordingly — DDL, package body and smoke tests. |
| **D16** | **Sys ids come from `SysIdHelper`, not from Oracle sequences.** The four `*_SYS_ID` keys are drawn from `MGTHRIS.HM_MST_SEQUENCES` — `SysIdHelper::generate($name, $nik, dateFormat: null)` from PHP, `PKG_HM_SEQUENCES` from a `BEFORE INSERT` trigger for the package's own inserts. The delivered SQL used four `CREATE SEQUENCE` objects; those are gone. One numbering mechanism for the whole application beats a second one reachable only from PL/SQL, it works unchanged under SQLite so tests need no driver branch, and it drops a grant instead of adding one. The cost is a row lock where a cached sequence had none — accepted, and measured, in `schema.md` §7.1. |

## Naming at a glance

- **Schema/connection:** `MGTHRIS` via `oracle_mgthris` (the app's default connection). Tables
  `SALES_CTL_APPR_REQUEST` (`SCAR_`), `SALES_CTL_APPR_LINE` (`SCAL_`), `SALES_MIN_PRICE`
  (`SMP_`), `SALES_MIN_PRICE_CHECK_LOG` (`SMPCL_`). Models `SalesCtlApprRequest`,
  `SalesCtlApprLine`, `SalesMinPrice`, `SalesMinPriceCheckLog`.
- **Read-only sources:** `MGTDAT` — `OT_SO_HEAD`, `OT_SO_ITEM`, `IM_VS_STATIC_VALUE`,
  `FM_EXCHANGE_RATE`, `PKG_MGT_PRICE_CTRL`. `MGTAPPS` — `MST_EXC_RATE_SAL`, read by the package.
- **Routes:** `dashboard/module-finance/master/min-price*` and
  `dashboard/module-finance/transaction/price-exception*`; names mirror the paths.
- **Permissions:** `finance-min_price-{view,draft,submit,print,approve,void}`,
  `finance-price_exception-{view,create,print,approve,report}`.
- **Log channel:** `finance_price_control`.
- **Config:** `Modules/Finance/config/config.php` → `sales_control` key.
