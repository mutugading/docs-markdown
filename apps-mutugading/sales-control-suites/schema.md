# schema.md — data

The four new tables live in **`MGTHRIS`**, reached through `oracle_mgthris` — the app's default
connection (`DB_CONNECTION=oracle_mgthris`). They are this application's own tables: it creates them,
it writes every row, and it owns their migrations.

**`MGTDAT` is read-only.** It holds Orion's data — sales orders, the transaction-code list, exchange
rates — and the package that reads them. Nothing in this app writes a row there.

`minimum-price-control.sql` as delivered puts all four tables in MGTDAT under `MGT_*` names. §9 lists
the edits that change; take it to Indra before anything is run.

---

## 0. Names

| Table | Schema | Column prefix | Sequence name (`HM_MST_SEQUENCES`) | Scope |
|---|---|---|---|---|
| `SALES_CTL_APPR_REQUEST` | MGTHRIS | `SCAR_` | `SALES_CTL_APPR_REQUEST_SCAR_SYS_ID_SEQ` | all three controls |
| `SALES_CTL_APPR_LINE` | MGTHRIS | `SCAL_` | `SALES_CTL_APPR_LINE_SCAL_SYS_ID_SEQ` | all three controls |
| `SALES_MIN_PRICE` | MGTHRIS | `SMP_` | `SALES_MIN_PRICE_SMP_SYS_ID_SEQ` | minimum price only |
| `SALES_MIN_PRICE_CHECK_LOG` | MGTHRIS | `SMPCL_` | `SALES_MIN_PRICE_CHECK_LOG_SMPCL_SYS_ID_SEQ` | minimum price only |

Those are **not** Oracle `SEQUENCE` objects. They are rows in `MGTHRIS.HM_MST_SEQUENCES`, the house
counter table that `App\Helpers\SysIdHelper` and `MGTHRIS.PKG_HM_SEQUENCES` both read — §7.1. Being
values rather than identifiers, they are free of the 30-byte cap, so each one spells out its table
and column in full, the way `SHIP_PROVISION_SPV_SYS_ID_SEQ` and the rest of the shipment tables do.

Two prefixes because two lifetimes. `SALES_CTL_` is the shared approval flow — minimum price now,
overdue delivery and credit limit in Phases 2 and 3 — and `SALES_MIN_` is what only minimum price will
ever use. Naming the shared tables `SALES_MIN_` would have meant a credit-limit request living in a
table named for prices.

Column prefixes are the table's initials, per house convention: **S**ales **C**tl **A**ppr
**R**equest, **S**ales **M**in **P**rice, and so on.

### Why `APPR` and not `APPROVAL`

Oracle 11g caps identifiers at **30 bytes**, and index and constraint names are built from the table
name:

```
SALES_CTL_APPROVAL_REQUEST_UK01   = 31 bytes  → ORA-00972
SALES_CTL_APPR_REQUEST_UK01       = 27 bytes  → fine
```

Shortening the table is better than inventing a second naming rule for its indexes, and `APPR` is
already this codebase's abbreviation (`SOH_APPR_STATUS`, `cpm_appr_status`). Every identifier in the
DDL now fits, with `SALES_MIN_PRICE_CHECK_LOG_NX01` the longest at exactly 30.

The cap binds the sys-id triggers (§7.1) too, and two of them land on 30 exactly:

```
SALES_CTL_APPR_REQ_SYS_ID_TRG    = 29
SALES_CTL_APPR_LINE_SYS_ID_TRG   = 30
SALES_MIN_PRICE_SYS_ID_TRG       = 26
SALES_MIN_PRICE_LOG_SYS_ID_TRG   = 30   -- CHECK_LOG abbreviated to LOG
```

Anything added later to these tables that derives its name from the table has 0 or 1 byte of room.
Count before naming.

---

## 1. Ownership map

The single most important table in this doc set. Read the "Laravel" column as "may this app write it".

| Table | Schema | Written by | Read by | Laravel writes? |
|---|---|---|---|---|
| `SALES_MIN_PRICE` | MGTHRIS | Laravel | package `P_GET_MIN_PRICE`, Laravel | **yes** — the master maintenance page |
| `SALES_CTL_APPR_REQUEST` | MGTHRIS | Laravel | package `F_GET_OVERRIDE`, Laravel | **yes** — the request header |
| `SALES_CTL_APPR_LINE` | MGTHRIS | Laravel | package `F_GET_OVERRIDE`, Laravel | **yes** — the exception lines |
| `SALES_MIN_PRICE_CHECK_LOG` | MGTHRIS | **package only** | Laravel | **no** — see §1.1 |
| `OT_SO_HEAD`, `OT_SO_ITEM` | MGTDAT | Orion | Laravel, package | never |
| `IM_VS_STATIC_VALUE` | MGTDAT | Orion / DBA | Laravel, package | never |
| `FM_EXCHANGE_RATE` | MGTDAT | Orion | package | never |
| `MST_EXC_RATE_SAL` | MGTAPPS | the sales team's own page | package | never |

No `DELETE` anywhere, on anything. Removing a rule is `SMP_STATUS = 'VOID'`; withdrawing a request is
`SCAR_STATUS = 'CANCELLED'` or `'VOID'`.

### 1.1 What the schema move costs: the log guard

In the delivered design — when this table lived in MGTDAT — the app could not write it, because it
had no `INSERT` grant. D4 was enforced by the database.

MGTHRIS now **owns** that table, so the app has full DML on it by definition. There is no grant left
to withhold. D4 therefore rests entirely on:

- `SalesMinPriceCheckLog`'s model guard, throwing on `creating`, `updating` and `deleting`
  (`design.md` §2);
- the repository exposing read methods only;
- test 23.

That is a code guard where there used to be a database one. It is a real reduction in safety and the
price of putting the table where the app lives. Say so in the model's docblock, so the guard reads as
load-bearing rather than as defensive habit somebody can tidy away.

If it ever matters more than convenience does, the alternative is to leave only this one table in
MGTDAT. It is the one table Laravel never writes, so it is the one that loses nothing by living where
its writer lives.

---

## 2. `SALES_CTL_APPR_REQUEST` — the shared header

One table for all four control types. Phase 1 uses two of them (D5):

| `SCAR_CTRL_TYPE` | Number series | What the request is | Line rows? | Which columns matter |
|---|---|---|---|---|
| `PRICELIST` | `MPL-YYYY-NNNN` | Approve a batch of minimum-price master rows | no | `VALID_FROM/TO`, BOD doc, attachment |
| `MINPRICE` | `MPO-YYYY-NNNN` | Let named SO lines sell below the floor | **yes** | `SOH_SYS_ID`, `CUST_CODE`, `TXN_CODE`, `DOC_NO/DT`, `CURR_CODE`, lines |
| `OVERDUE` | `MOV-YYYY-NNNN` | Phase 2 — outline only | no | `CUST_CODE`, `VALID_FROM/TO` |
| `CRLIMIT` | `MCL-YYYY-NNNN` | Phase 3 — outline only | no | `CUST_CODE`, `AMOUNT`, `VALID_FROM/TO` |

`SCAR_SOH_SYS_ID` points at `MGTDAT.OT_SO_HEAD`. It is a **cross-schema reference with no foreign
key** — Oracle can carry one across schemas, but pointing a constraint from this app's table at an
Orion transaction table would let this app's data block an Orion delete. Keep it as a plain number
and validate on the way in.

`SCAR_AMOUNT` is `CRLIMIT`-only. Leave it null for Phase 1; a value there on a `MINPRICE` row means
somebody wired the wrong field and the credit-limit trigger will one day read it.

### 2.1 Constraints Oracle enforces

- `UK01 (SCAR_REQ_NO, SCAR_REVISION)` — the revision mechanism (D7) leans on this. A retry-on-violation
  loop, not a `SELECT MAX` read outside a transaction, is what makes concurrent numbering safe.
- `C01` / `C02` — ctrl type and status domains. The PHP enums must match these strings exactly.
- `C03` — `SCAR_STATUS <> 'APPROVED' OR SCAR_ATTACH_PATH IS NOT NULL`. **The database refuses an
  approval with no scan.** Laravel validates it too, for a readable message rather than an ORA-02290.

### 2.2 What Oracle cannot enforce, so Laravel must

| Rule | Where it goes |
|---|---|
| A `PRICELIST` request has no `SCAL_` rows; a `MINPRICE` request has at least one | `ApprovalRequestService` |
| `SCAR_VALID_FROM <= SCAR_VALID_TO` | DTO rule + service |
| Content is immutable once `SCAR_STATUS <> 'DRAFT'` | `ApprovalRequestService::assertEditable()` |
| `SCAR_BOD_DOC_NO/DT/SIGNER` all present before `APPROVED` | `ApprovalRequestService::approve()` |
| Only the `PRINTED → APPROVED` transition may set `SCAR_APPR_UID/DT` | the state machine, `spec.md` §2 |
| `SCAR_SOH_SYS_ID` names a real, in-scope sales order | `PriceExceptionService`, via the SO lookup repository |

---

## 3. `SALES_CTL_APPR_LINE` — the binding

`SCAL_APPROVED_RATE` and `SCAL_APPROVED_QTY_BU` are not decoration. `F_GET_OVERRIDE` matches on
`SCAL_APPROVED_RATE = SOI_RATE` **exactly** and `SCAL_APPROVED_QTY_BU >= SOI_QTY_BU`. So:

- drop the price after approval → the exception stops matching, approval blocked, request again;
- raise the quantity after approval → same;
- raise the price, or cut the quantity → still matches, because both moves are in the company's favour.

That asymmetry is deliberate and it is the reason D8 exists. If Marketing typed the rate by hand and
fat-fingered a digit, the exception would look approved on screen and still block the SO — with a
signed piece of paper in the file. Take the numbers from `OT_SO_ITEM`.

`SCAL_SOI_SYS_ID` is the same kind of cross-schema reference as `SCAR_SOH_SYS_ID` (§2): a number, no FK.

`SCAL_MIN_PRICE_USD` and `SCAL_RATE_USD` are snapshots of what the floor and the converted rate were at
request time. They exist so the printed form is reconstructable after the FX table has moved on. They
are **not** read by the trigger.

The one real FK here — `SCAL_SCAR_SYS_ID → SALES_CTL_APPR_REQUEST` — is within MGTHRIS and stays.

---

## 4. `SALES_MIN_PRICE` — the master

Resolution order is PRD §3.3 and it is implemented in `P_GET_MIN_PRICE`, not in PHP. What Laravel
owns is the shape of the rows.

The **rule key** — used for the overlap check, for "supersede this rule", and as the natural key on
import — is:

```
(SMP_SCOPE_LEVEL, SMP_SCOPE_VALUE, SMP_GRADE_CODE_1, SMP_GRADE_CODE_2, SMP_UOM_CODE)
```

`SMP_UOM_CODE` is part of the key because the floor is a price *per unit*: 1.250 USD/KG and
1.250 USD/LBS are different rules, not a conflict.

`SMP_SCOPE_VALUE` holds an item code from `MGTDAT.OM_ITEM` — `ITEM_CODE`, `VARCHAR2(20)`, the
table's primary key, and exactly the width of this column, so no item code can be truncated into a
rule that silently matches nothing. Cross-schema, no FK, validated on save
(`verification.md` §1 item 8).

### 4.1 Overlap validation (Oracle 11g cannot do it, PRD §4.7)

Reject on save when, for the same rule key, another row with `SMP_STATUS IN ('DRAFT','APPROVED')`
overlaps in time:

```
new_from <= NVL(existing_to, '31-12-2099')  AND  NVL(new_to, '31-12-2099') >= existing_from
```

Row being edited excluded by `SMP_SYS_ID`. `DRAFT` rows are included in the check on purpose: two
drafts that overlap are a mistake caught cheaply now, rather than an approval that fails at the end
of a print-and-sign cycle.

This is a validation, not a constraint, so it can lose a race. The window is a person clicking save
twice; the consequence is `P_GET_MIN_PRICE` picking the newer `SMP_VALID_FROM` (its tiebreaker) and
the report in `spec.md` §7.4 listing the duplicate. Acceptable. Do not try to emulate the constraint
with a trigger.

### 4.2 Superseding (D6)

"Change the price of an approved rule" is:

1. new row, same rule key, `SMP_VALID_FROM = <effective date>`, `SMP_STATUS = 'DRAFT'`;
2. on approval of the covering `PRICELIST` request, set the old row's
   `SMP_VALID_TO = <effective date> − 1 day` and the new row to `APPROVED`, in one transaction.

Step 2 happens at approval, not at draft time, so a request that is never signed leaves the live rule
untouched.

### 4.3 Level `GROUP`

Not used in Phase 1. `F_GET_ITEM_GROUP` returns `NULL`, so a `GROUP` rule resolves against nothing.
**Build no group UI** (PRD §3.3). The scope-level enum carries the case so the resolver and the
report do not need reworking later; the create form offers `ITEM` and `ALL` only.

`OM_ITEM` does have a grouping column — `ITEM_IG_CODE`, `VARCHAR2(12)`, `NOT NULL` — which the
original "no grouping column exists" wording did not account for. Whether it is the *right* grouping
for a price floor is a different question and an open one: it is Orion's inventory item group, which
may be coarser or cut differently from how Finance thinks about finished goods. **Q11 in
`verification.md` §3.** Until that is answered the default stands and nothing is built: a group rule
resolving on the wrong grouping would put a floor under products nobody meant to include, and it
would do it silently.

---

## 5. `SALES_MIN_PRICE_CHECK_LOG` — read-only

Every check the trigger performs, pass or fail. `SMPCL_RESULT` is one of
`PASS | PASS_NORULE | OVERRIDE | INHERIT | FAIL`.

It is the only way to answer "why did this SO go through in March" after `MST_EXC_RATE_SAL` has been
edited, which is why `SMPCL_EXG_DIVISOR`, `SMPCL_EXG_RATE_DT` and `SMPCL_EXG_RATE_SRC` are all stored
rather than recomputed. Reports in `spec.md` §7 read this table and nothing else.

The read-only rule is now code-enforced only — §1.1. Read that before touching this table's model.

`SMPCL_NET_RATE_USD` and `SMPCL_HAS_DISCOUNT` are monitoring columns: validation uses gross, and these
record what net would have said. The gross-vs-net report exists to give that decision a factual basis
later. Do not validate against net without a written decision.

One row per line per approval **attempt** — a document approved after three failed tries has four
generations of rows. Reports must scope by `SMPCL_CR_DT`, and the failure report by
`(SMPCL_SOH_SYS_ID, SMPCL_SOI_SYS_ID)` taking the latest generation, or the same rejection is counted
four times.

**This is the one table the trigger writes across a schema boundary**, on the hot path of every sales
order approval. If the grant in §9 is missing, every approval of an in-scope SO fails with ORA-00942 —
not just the ones below the floor. It is the first thing to check when the trigger goes on.

---

## 6. Laravel migrations

Five files under `Modules/Finance/database/migrations/`, all `protected $connection = 'oracle_mgthris'`,
all following the house guard (`DISABLE_MIGRATIONS` key + `hasTable()`):

| Migration | Key | Content |
|---|---|---|
| `..._create_sales_ctl_appr_request_table` | `sales_ctl_appr_request` | table + the two unique indexes + the two lookup indexes |
| `..._create_sales_ctl_appr_line_table` | `sales_ctl_appr_line` | table + indexes + the FK to the request |
| `..._create_sales_min_price_table` | `sales_min_price` | table + indexes |
| `..._create_sales_min_price_check_log_table` | `sales_min_price_check_log` | table + indexes |
| `..._create_sales_ctl_sys_id_trg` | `sales_ctl_sys_id_trg` | §7.1 — registers the four `HM_MST_SEQUENCES` rows on both drivers, and creates the four `BEFORE INSERT` triggers on the Oracle branch only |

Because the tables now live where Laravel writes, these migrations are the **real** creation path, not
a CI-only mirror. Which raises the question of who runs first — Laravel or the DBA — and the answer is
either, as long as both produce the same thing:

- **`hasTable()` guard stays.** If the DBA has run §9's DDL first, the migration must do nothing.
- **The migration is the reference for a fresh schema** (CI on SQLite, a local database, a new test
  schema), so it has to be complete, not a sketch.
- **`down()` is empty**, with the reason in a comment: these tables hold signed approvals and an audit
  log. A `migrate:rollback` that drops them is a worse outcome than a migration that will not reverse.
- **SQLite-compatible types.** `decimal(18,3)` not `NUMBER(18,3)`; no tablespace or storage clauses.
- **The CHECK constraints (`C01`–`C03`) are Oracle-only.** Add them with a raw statement on the Oracle
  branch, skip them under SQLite, and note in the comment that the service layer is the enforcement in
  tests — so nobody "fixes" the gap by deleting the branch.
- **The fifth migration is the one exception to the empty `down()`.** It reverses cleanly: drop the
  four triggers on Oracle, delete the four `HM_MST_SEQUENCES` rows on both drivers. Nothing signed or
  audited is lost, because the counters are metadata, not records. Its `up()` skips any sequence row
  that already exists, so a re-run never resets a counter that has handed ids out.

Item 5 in `verification.md` §1 becomes: does the Laravel Oracle user hold `CREATE TABLE` and
`CREATE TRIGGER` in MGTHRIS? It owns the schema, so it should — but confirm rather than discover it
during a deploy. `CREATE SEQUENCE` is **no longer needed**: after §7.1 there are no Oracle sequence
objects in this design at all.

---

## 7. Identity and numbering

Three different things, do not conflate them.

### 7.1 `*_SYS_ID`

**From `SysIdHelper`, against `MGTHRIS.HM_MST_SEQUENCES`. No Oracle `SEQUENCE` objects.**

This is the house mechanism — the same one every `HM_*`, `HT_*` and `SHIP_*` table uses — and it is
what the four sequence names in §0 name. One row per table in `HM_MST_SEQUENCES`, read from PHP by
`App\Helpers\SysIdHelper` and from PL/SQL by `MGTHRIS.PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO`. Both sides
draw from the same counter, so the app and the package can never hand out the same id.

In the repository:

```php
// EloquentApprovalRequestRepository
public function nextSysId(): int
{
    return (int) SysIdHelper::generate(
        'SALES_CTL_APPR_REQUEST_SCAR_SYS_ID_SEQ',
        auth()->user()?->hmemd_nik ?? 'SYSTEM',
        dateFormat: null,   // see below — this is not optional
    );
}
```

**`dateFormat: null` is required.** `SysIdHelper::generate()` defaults to `'Ymd'` and prepends the
date, which would turn id 41 into `2026090441` — a number that grows by eight digits a day and
sorts by date rather than by insert order. These four columns are `NUMBER` surrogate keys, so the
bare counter is what belongs in them. Leaving the default in place does not fail; it quietly writes
the wrong kind of value, which is worse.

The paired setting is on the sequence row: `HMMS_NUMBER_FORMAT = 'TM9'`, Oracle's "text minimum"
format — shortest representation, no padding, no width to overflow. `SysIdHelper` pads by however
many `0` characters that string holds, and `TM9` holds none, so it returns the plain number. A
literal `'0'` looks like it means the same thing and then stops at 9 (`TO_CHAR(10, '0')` is `#`).

`HMMS_SEQ_TYPE = 0` (SysId, no prefix). Type 1 is for the prefixed transaction numbers, which here
means `SCAR_REQ_NO` — and that one is **not** built with `SysIdHelper`, because its counter resets per
prefix and per year; see §7.2.

Batch inserts use `SysIdHelper::generateBatch($name, $nik, $count, null)` — one reservation for the
whole set instead of one round trip per line. That matters for `SALES_CTL_APPR_LINE`, where a request
can tick thirty SO lines at once, and for the min-price Excel import.

#### The triggers, and why they still exist

The fifth migration also creates a `BEFORE INSERT ... WHEN (new.<pk> IS NULL)` trigger per table,
each calling `PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO` with `p_date_format => NULL` on the same sequence
name. Two reasons:

1. **The package inserts into `SALES_MIN_PRICE_CHECK_LOG` and cannot call PHP.** With the trigger in
   place, `P_VALIDATE_SO` simply leaves `SMPCL_SYS_ID` out of its insert list and the trigger fills
   it. That replaces the old `SELECT ... NEXTVAL` and removes a grant (§9.3).
2. **Anything inserting by hand** — the smoke tests in section G, a DBA fixing a row — gets a valid
   key without knowing the mechanism.

Laravel still generates its ids explicitly rather than leaning on the trigger, because it needs the
new id **before** the insert (the request header's id is the parent key for its lines) and
Oracle 11g has no `RETURNING` support through this driver.

#### The cost, stated plainly

A counter row is not a cached Oracle sequence. Every id taken locks one `HM_MST_SEQUENCES` row for
the rest of its transaction, so concurrent takers on the same sequence serialize; a sequence with
`CACHE 20` would not have. Three of the four tables are approval-flow writes at human pace and will
never notice. The fourth is `SALES_MIN_PRICE_CHECK_LOG`, which the package writes **once per SO line
on every approval attempt** (§5) — the one place in this design where that lock is on a hot path.

Accepted, because it is the same lock every `HT_*` transaction table in this application already
takes, and because the alternative is a second numbering mechanism to maintain. It is worth watching
after the trigger goes on: a document with 300 lines takes 300 ids inside one transaction, and if
`enq: TX - row lock contention` on `HM_MST_SEQUENCES` shows up in the first week, this paragraph is
where to start. The fix would be `generateBatch()`-style single reservation inside the package, not a
return to Oracle sequences.

#### Under SQLite

The migration writes the same four `HM_MST_SEQUENCES` rows on SQLite, so `SysIdHelper` works
identically in tests — same counter table, same code path, no driver branch in the repository. What
SQLite does not get is the trigger; there, the `autoIncrement` column on the Blueprint covers the
hand-written insert. `MAX(pk) + 1` appears nowhere: it races under parallel tests, which is exactly
where it would be used.

### 7.2 `SCAR_REQ_NO`

`{PREFIX}-{YYYY}-{NNNN}`, prefix from the ctrl type (D5), year from `SCAR_DOC_DT` (or today for a
`PRICELIST`), counter per prefix per year, zero-padded to 4.

Generated in `ApprovalRequestNumberGenerator`:

```
next = MAX(TO_NUMBER(SUBSTR(SCAR_REQ_NO, -4))) + 1  for that prefix and year
insert; on UK01 violation, retry (max 3)
```

The retry, not the read, is the correctness mechanism — `UK01 (REQ_NO, REVISION)` makes a lost race a
failed insert rather than a duplicate number. Four digits is 9 999 requests a year per type; if a
year ever gets close, widen the pad, do not roll over.

### 7.3 `SCAR_REVISION`

Starts at 0. Only the revision flow (D7, `spec.md` §2.3) increments it.

---

## 8. Configuration rows — these stay in MGTDAT

`minimum-price-control.sql` section C inserts into two Orion tables. **Neither moves**: they belong to
Orion, the app only reads one of them, and the DBA runs both inserts.

- Three `MGTDAT.IM_VS_STATIC_VALUE` rows under `VSSV_VS_CODE = 'MINPRC_MGT'` — the in-scope
  `SOH_TXN_CODE` list (`ESC`, `LSC`, `STA`). **Read this table, do not hardcode the list in PHP** —
  the price-preview page must agree with the trigger about which documents are in scope, and there is
  exactly one source for that. `SalesOrderLookupRepository::inScopeTxnCodes()`, on `oracle_mgtdat`.
- Three `MGTDAT.IM_APP_ERROR_MESSAGE` rows, `1012110`–`1012112`. Laravel never raises these; they are
  the Forms-side messages. Worth knowing when a user forwards a screenshot.

Both inserts carry a "verify the column names first" note in the SQL. That is `verification.md` §1
items 6 and 7 — do them before asking the DBA to run section C.

---

## 9. What changed in `minimum-price-control.sql`

**Already applied** — the file in this folder is the reworked version. This section is the changelog
for Indra, not a to-do list. The one thing still outstanding is the tablespace (§9.1).

### 9.1 Sections A and B — tables and the sequence rows

Renamed and re-owned:

| Was | Now |
|---|---|
| `MGTDAT.MGT_APPROVAL_REQUEST` (`MAR_`) | `MGTHRIS.SALES_CTL_APPR_REQUEST` (`SCAR_`) |
| `MGTDAT.MGT_APPROVAL_LINE` (`MAL_`) | `MGTHRIS.SALES_CTL_APPR_LINE` (`SCAL_`) |
| `MGTDAT.MGT_MIN_PRICE` (`MMP_`) | `MGTHRIS.SALES_MIN_PRICE` (`SMP_`) |
| `MGTDAT.MGT_PRICE_CHECK_LOG` (`MPCL_`) | `MGTHRIS.SALES_MIN_PRICE_CHECK_LOG` (`SMPCL_`) |

Indexes and constraints follow their tables. Every column prefix was rewritten with them,
so `MAL_MAR_SYS_ID` is now `SCAL_SCAR_SYS_ID`, `MPCL_MMP_SYS_ID` is `SMPCL_SMP_SYS_ID`, and so on
through the DDL, the package body and the smoke tests. Local PL/SQL variables too (`v_mar_sys_id` →
`v_scar_sys_id`), and the OUT parameter `o_mmp_sys_id` → `o_smp_sys_id` — the only rename that is
visible outside the package.

`APPR` rather than `APPROVAL` because of the 30-byte identifier limit; §0 has the arithmetic. Every
identifier in the file now fits, the longest being `SALES_MIN_PRICE_CHECK_LOG_NX01` at exactly 30.

**Section B is no longer four `CREATE SEQUENCE` statements.** The four Oracle sequence objects are
gone; in their place, section B inserts the four `HM_MST_SEQUENCES` rows and creates the four
`BEFORE INSERT` sys-id triggers, per §7.1. The reason is that the app has one numbering mechanism and
these tables were the only thing in the repository proposing a second one — `SysIdHelper` and
`PKG_HM_SEQUENCES` read `HM_MST_SEQUENCES`, so a plain Oracle sequence is reachable from PL/SQL and
from nothing else the codebase already owns.

Section B is also the one part of the file the **Laravel migration duplicates on purpose** (§6): the
migration is the path a fresh schema takes, section B the path the DBA takes, and both are written to
be no-ops on second run — the inserts skip existing sequence names, the triggers are
`CREATE OR REPLACE`.

**Still open:** the DDL keeps `TABLESPACE ORION`, inherited from when these tables were headed for
MGTDAT. Check what MGTHRIS actually uses (`verification.md` Q10) and change it before running
section A.

Section C is unchanged: both its target tables stay in MGTDAT (§8).

### 9.2 Section D — the package body

`P_GET_MIN_PRICE`, `F_GET_OVERRIDE` and `P_VALIDATE_SO` now read `MGTHRIS.SALES_*`, and
`P_VALIDATE_SO` inserts into `MGTHRIS.SALES_MIN_PRICE_CHECK_LOG` **leaving `SMPCL_SYS_ID` out of the
insert entirely** — the sys-id trigger from section B fills it (§7.1). This replaced a
`SALES_MIN_PRICE_CHECK_LOG_SEQ.NEXTVAL` in the `VALUES` list, and with it the `SELECT` grant on that
sequence (§9.3).

Everything else in the body — `MGTAPPS.MST_EXC_RATE_SAL`, `MGTDAT.FM_EXCHANGE_RATE`,
`MGTDAT.OT_SO_ITEM`, `MGTDAT.OT_SO_ITEM_TED`, `MGTDAT.IM_VS_STATIC_VALUE` — is untouched, as is the
package's own name and owner (`MGTDAT.PKG_MGT_PRICE_CTRL`) and all of its logic.

Private synonyms in MGTDAT would keep the body unqualified and make a future move cheaper. Indra's
call; qualified names are fine and are what the file already does everywhere else.

### 9.3 New: grants **from** MGTHRIS **to** MGTDAT — section F1

The package is owned by MGTDAT and runs with definer's rights. It now touches four MGTHRIS tables, so
MGTHRIS must grant to MGTDAT **directly**. In the file as section F1, commented out like the rest of
the grants:

```sql
GRANT SELECT ON MGTHRIS.SALES_CTL_APPR_REQUEST        TO MGTDAT;
GRANT SELECT ON MGTHRIS.SALES_CTL_APPR_LINE           TO MGTDAT;
GRANT SELECT ON MGTHRIS.SALES_MIN_PRICE               TO MGTDAT;
GRANT INSERT ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG     TO MGTDAT;
```

Four grants, not five. The sequence grant is gone with the sequence object, and **nothing replaces
it** — MGTDAT needs no access to `HM_MST_SEQUENCES` or `PKG_HM_SEQUENCES`. The sys-id trigger is
owned by MGTHRIS and runs with MGTHRIS's privileges, so it reaches the counter on MGTHRIS's behalf
while MGTDAT holds nothing but `INSERT` on the table. That is a tighter grant list than before, not a
looser one.

The deploy order in the file's header changed with it: **A → B → C → F1 → D → F2 → E**.

> **Direct grants, never through a role.** A stored PL/SQL unit compiled with definer's rights sees
> only privileges granted directly to its owner; privileges held via a role are invisible to it. The
> package will compile, and then fail at runtime with ORA-00942 on a table the DBA can see perfectly
> well from SQL*Plus. This is the classic Oracle trap and it will cost a day if it is not written
> down.

Compile the package **after** the grants, and check `USER_OBJECTS.STATUS` is `VALID`.

### 9.4 Section F2 — grants to the Laravel user

Simpler than before, and this is the payoff of the move. The Laravel user *is* MGTHRIS, so it needs no
grant on its own four tables. What it still needs:

```sql
GRANT SELECT  ON MGTDAT.OT_SO_HEAD          TO MGTHRIS;
GRANT SELECT  ON MGTDAT.OT_SO_ITEM          TO MGTHRIS;
GRANT SELECT  ON MGTDAT.OM_ITEM             TO MGTHRIS;
GRANT SELECT  ON MGTDAT.IM_VS_STATIC_VALUE  TO MGTHRIS;
GRANT EXECUTE ON MGTDAT.PKG_MGT_PRICE_CTRL  TO MGTHRIS;
```

Several of these may already exist — the Finance module reads MGTDAT today. Check before asking.

`OM_CUSTOMER` too, if the exception screen shows a customer name rather than just the code.

### 9.5 Section E — the trigger

Unchanged. It stays on `MGTDAT.OT_SO_HEAD` and still calls `MGTDAT.PKG_MGT_PRICE_CTRL.P_VALIDATE_SO`.
Only what the package reaches for changed, not who calls it.

### 9.6 Section G — smoke tests

G1–G4 carry the new names. A new **G0** goes first, run as the **MGTDAT** user rather than as
MGTHRIS: three counts across the schema boundary and one insert into the log, then `ROLLBACK`.

If any of it fails, F1 was not run or was granted through a role. Running G1–G4 before G0 passes
proves nothing — and finding it here beats finding it on the first sales order of the day.
