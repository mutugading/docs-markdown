# verification.md — what must be checked before anything is built

PRD §4.8 lists six checks. Three of them are assumptions that, if wrong, change the code. This turns
them into queries somebody can run, plus the smoke tests for after the package is deployed, plus the
questions still open and who answers them.

**Nothing in `plan.md` P1 starts until §1 items 1–4 have written answers.** They are cheap to run and
each one can invalidate a chunk of the build.

Record answers in this file, dated and initialled. A verification whose result lives in a chat message
is a verification that gets repeated.

---

## 1. Pre-deploy checks

### 1. Exchange-rate direction — **highest risk**

The whole conversion rests on `MERS_VALUE` being IDR-per-USD (~17 250) and `CER_EXG_RATE` being its
reciprocal (~0.000058). If either is the other way round, every IDR price is compared against a floor
that is off by eight orders of magnitude.

```sql
-- BCA side
SELECT MERS_DATE, MERS_VALUE
  FROM MGTAPPS.MST_EXC_RATE_SAL
 WHERE MERS_TERMS = 'LC_0_DAYS' AND MERS_TYPE = 'EXCHANGE'
   AND NVL(MERS_VALUE,0) <> 0
 ORDER BY TO_DATE(MERS_DATE,'DD/MM/RR') DESC
 FETCH FIRST 10 ROWS ONLY;   -- 11g: wrap in ROWNUM <= 10

-- Orion side
SELECT CER_EFF_FRM_DT, CER_EFF_TO_DT, CER_EXG_RATE
  FROM MGTDAT.FM_EXCHANGE_RATE
 WHERE CER_CONV_FM_CURR_CODE = 'IDR' AND CER_CONV_TO_CURR_CODE = 'USD'
   AND CER_EXG_RATE_TYPE = 'B'
 ORDER BY CER_EFF_FRM_DT DESC;
```

Expected: `MERS_VALUE` in the thousands, `CER_EXG_RATE` a small fraction. Then cross-check against a
closed IDR sales order — take its `SOH_DT`, divide a known line rate by the divisor, and see whether
the USD figure is plausible for that product.

The package's 1 000–100 000 sanity check means a wrong assumption fails loudly rather than passing
everything. That is a safety net, not a substitute for looking.

**Result:** _(pending)_

### 2. USD documents carry `SOH_EXGE_RATE = 1`

```sql
SELECT SOH_CURR_CODE, COUNT(*) docs,
       SUM(CASE WHEN NVL(SOH_EXGE_RATE,0) = 0 THEN 1 ELSE 0 END) zero_or_null,
       MIN(SOH_EXGE_RATE) min_rate, MAX(SOH_EXGE_RATE) max_rate
  FROM MGTDAT.OT_SO_HEAD
 WHERE SOH_TXN_CODE IN ('ESC','LSC','STA')
   AND SOH_DT >= ADD_MONTHS(TRUNC(SYSDATE), -12)
 GROUP BY SOH_CURR_CODE;
```

The package short-circuits USD to `divisor = 1` and never reads `SOH_EXGE_RATE`, so a null here is
harmless for Phase 1. Run it anyway: a currency in the result other than `USD` and `IDR` means the
control will raise 1012112 on live documents, and that has to be known before the trigger goes on.

**Result:** _(pending)_

### 3. STA → ESC line mapping

`F_HAS_PARENT_OVERRIDE` assumes the parent line is `SOI_SOI_SYS_ID`, following `ODBTRG_SOI_TOL_MGT`.
There are two other candidates, `SOI_SOR_SYS_ID` and `SOI_BSOI_SYS_ID` (blanket SO).

```sql
SELECT h.SOH_TXN_CODE, h.SOH_NO, h.SOH_REF_SYS_ID,
       i.SOI_SYS_ID, i.SOI_SOI_SYS_ID, i.SOI_SOR_SYS_ID, i.SOI_BSOI_SYS_ID,
       i.SOI_ITEM_CODE, i.SOI_RATE,
       p.SOI_SYS_ID parent_sys_id, p.SOI_ITEM_CODE parent_item, p.SOI_RATE parent_rate,
       ph.SOH_TXN_CODE parent_txn, ph.SOH_NO parent_no
  FROM MGTDAT.OT_SO_HEAD h
  JOIN MGTDAT.OT_SO_ITEM i  ON i.SOI_SOH_SYS_ID = h.SOH_SYS_ID
  LEFT JOIN MGTDAT.OT_SO_ITEM p  ON p.SOI_SYS_ID = i.SOI_SOI_SYS_ID
  LEFT JOIN MGTDAT.OT_SO_HEAD ph ON ph.SOH_SYS_ID = p.SOI_SOH_SYS_ID
 WHERE h.SOH_TXN_CODE = 'STA'
   AND h.SOH_REF_SYS_ID IS NOT NULL
   AND h.SOH_DT >= ADD_MONTHS(TRUNC(SYSDATE), -6);
```

Pass condition: `parent_sys_id` populated, `parent_item` equal to the child item, and `parent_no`
belonging to the referenced ESC. If `SOI_SOI_SYS_ID` comes back null while another of the three is
populated, the fix is one line in `F_HAS_PARENT_OVERRIDE` — but it also changes what the preview page
shows for inherited lines (`spec.md` §5.4), so it must be settled before P3.

**Result:** _(pending)_

### 4. Every in-scope TXN_CODE really requires approval

The trigger fires on `SOH_APPR_STATUS` reaching 3. A transaction code that never goes through
approval would be silently exempt from the whole control.

```sql
SELECT SOH_TXN_CODE,
       COUNT(*) docs,
       SUM(CASE WHEN NVL(SOH_APPR_STATUS,0) = 3 THEN 1 ELSE 0 END) approved,
       SUM(CASE WHEN NVL(SOH_APPR_STATUS,0) = 0 THEN 1 ELSE 0 END) never_approved
  FROM MGTDAT.OT_SO_HEAD
 WHERE SOH_TXN_CODE IN ('ESC','LSC','STA')
   AND SOH_DT >= ADD_MONTHS(TRUNC(SYSDATE), -12)
 GROUP BY SOH_TXN_CODE;
```

Also check `OM_TXN` for whether approval is configured as mandatory per code.

If `never_approved` is meaningful for any code, PRD §4.8 says an extra trigger on `OT_SO_ITEM` would
be needed — and equally says **do not build it before it is proven necessary**. Record the number,
raise it with Indra, build nothing yet.

**Result:** _(pending)_

### 5. Privileges, both directions

The schema move (D3) turned one grant list into two, going opposite ways. Both matter, and the second
one is the deploy's most likely failure (D14).

**5a — MGTHRIS can create its own objects.** It owns the schema, so this should be true; confirm
rather than discover it mid-deploy. `CREATE TRIGGER`, not `CREATE SEQUENCE` — the sys ids come from
`HM_MST_SEQUENCES` and a `BEFORE INSERT` trigger, not from Oracle sequence objects (`schema.md`
§7.1). Also confirm the four sequence rows landed, since a missing one fails at the first insert and
not at deploy time:

```sql
SELECT HMMS_SEQ_NAME, HMMS_LAST_VALUE, HMMS_NUMBER_FORMAT, HMMS_SEQ_TYPE
  FROM MGTHRIS.HM_MST_SEQUENCES
 WHERE HMMS_SEQ_NAME LIKE 'SALES\_%' ESCAPE '\';
```

Wanted: four rows, `HMMS_NUMBER_FORMAT = 'TM9'`, `HMMS_SEQ_TYPE = 0`.

```sql
SELECT * FROM USER_SYS_PRIVS
 WHERE PRIVILEGE IN ('CREATE TABLE','CREATE TRIGGER','UNLIMITED TABLESPACE');
SELECT TABLESPACE_NAME, BYTES/1024/1024 mb FROM USER_TS_QUOTAS;
```

**5b — MGTHRIS can read what it needs from MGTDAT** (`schema.md` §9.4).

```sql
SELECT TABLE_NAME, PRIVILEGE, GRANTOR
  FROM ALL_TAB_PRIVS
 WHERE GRANTEE = 'MGTHRIS'
   AND TABLE_NAME IN ('OT_SO_HEAD','OT_SO_ITEM','OM_ITEM','OM_CUSTOMER',
                      'IM_VS_STATIC_VALUE','PKG_MGT_PRICE_CTRL')
 ORDER BY TABLE_NAME;
```

Several of these may already exist — the Finance module reads MGTDAT today. Check before asking the
DBA for anything.

**5c — MGTDAT can reach the four new MGTHRIS tables** (`schema.md` §9.3). Run as **MGTDAT**:

```sql
SELECT TABLE_NAME, PRIVILEGE, GRANTOR
  FROM USER_TAB_PRIVS_RECD
 WHERE OWNER = 'MGTHRIS'
   AND TABLE_NAME LIKE 'SALES_MIN%'
 ORDER BY TABLE_NAME, PRIVILEGE;
```

Wanted: `SELECT` on `SALES_MIN_PRICE`, `SALES_CTL_APPR_REQUEST`, `SALES_CTL_APPR_LINE`; `INSERT` on
`SALES_MIN_PRICE_CHECK_LOG`. Four grants, no fifth — MGTDAT gets nothing on `HM_MST_SEQUENCES` or
`PKG_HM_SEQUENCES`, because the sys-id trigger is owned by MGTHRIS and reaches the counter itself
(`schema.md` §9.3). No `DELETE` anywhere, and no `UPDATE` on anything.

> The privilege must be **granted directly to MGTDAT, not through a role.** A package compiled with
> definer's rights cannot see role-held privileges. It will compile clean and then fail at runtime
> with ORA-00942 on a table the DBA can query happily from SQL*Plus. `USER_TAB_PRIVS_RECD` above
> shows only direct grants, which is why the check is written that way — if the row is missing there
> but the DBA insists access works, that is the trap, not a false alarm.

**5d — the log stays read-only in practice.** MGTHRIS owns `SALES_MIN_PRICE_CHECK_LOG`, so no grant
can stop the app writing it any more (`schema.md` §1.1). Nothing to check here; it is a note, so that
whoever reads this list knows the guard moved into the code and did not simply disappear.

Also confirm `DB_MGTHRIS_*` and `DB_MGTDAT_*` in `.env` point at the right users on staging and
production. `.env.example` currently has no `DB_MGTDAT_*` block at all — add one while you are there.

**Result:** _(pending)_

### 6. `IM_APP_ERROR_MESSAGE` column names

```sql
DESC MGTDAT.IM_APP_ERROR_MESSAGE;
SELECT * FROM MGTDAT.IM_APP_ERROR_MESSAGE WHERE ROWNUM <= 5;
```

Section C assumes `AEM_APP_CODE`, `AEM_ERROR_CODE`, `AEM_MESSAGE_ENG`, `AEM_MESSAGE_FOR`. Check the
names and whether any other column is `NOT NULL`. Also confirm 1012110–1012112 are not already taken.

**Result:** _(pending)_

### 7. `IM_VS_STATIC_VALUE` structure

Not in PRD §4.8, but section C inserts into it and the app reads it (`schema.md` §8).

```sql
DESC MGTDAT.IM_VS_STATIC_VALUE;
SELECT * FROM MGTDAT.IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = 'TOL_SO_MGT';
```

Copy the shape of the `TOL_SO_MGT` rows — that is the pattern being followed. Note what
`VSSV_FRZ_FLAG_NUM = 2` means there and whether any other column is mandatory.

**Result:** _(pending)_

### 8. `OM_ITEM` columns — **answered**

The app reads this table in two places: to validate that an `ITEM`-level rule names a real item, and
to show an item's name next to its code on the preview and the reports.

```sql
DESC MGTDAT.OM_ITEM;
```

**Result:** answered 04-09-2026 from the table DDL. What matters here:

| Column | Type | Note |
|---|---|---|
| `ITEM_CODE` | `VARCHAR2(20)`, PK (`OM_ITEM_PK`) | The natural key. Same width as `SMP_SCOPE_VALUE`, so a rule can hold any item code without truncation |
| `ITEM_NAME` | `VARCHAR2(240)` | **The description column. There is no `ITEM_DESC`** |
| `ITEM_SHORT_NAME` | `VARCHAR2(30)` | Useful where a table column is narrow |
| `ITEM_UOM_CODE` | `VARCHAR2(12)` | FK to `OM_UOM`. The item's own UOM — *not* a constraint on `SMP_UOM_CODE`, which is per rule, and the same item may be sold in KG and in LBS |
| `ITEM_IG_CODE` | `VARCHAR2(12)`, `NOT NULL` | Item group. See **Q11** — this is the candidate for scope level `GROUP` |
| `ITEM_FRZ_FLAG_NUM` | `NUMBER(1)`, default 2 | Freeze flag, following the house `2 = active` convention elsewhere in Orion. Not filtered on today: a rule for a frozen item resolves and simply never matches a live line |
| `ITEM_PRICE_CTRL_CODE` | `VARCHAR2(12)` | Orion's own price-control code. Unrelated to this control; worth knowing it exists before somebody assumes it is the same thing |
| `ITEM_FLEX_01` … `ITEM_FLEX_20` | `VARCHAR2(240)` | Twenty free-text flex columns, plus `ITEM_ANLY_CODE_01`–`20`. If a future grouping is added by convention rather than by schema change, this is where it will live |

Consequences, already applied to the code:

- `SalesOrderLookupRepository::itemDescriptions()` reads `ITEM_NAME`. It previously read `ITEM_DESC`,
  which does not exist — the guess this check was written to catch.
- `itemExists()` reads `ITEM_CODE`, which was right.
- The preview no longer reads a description column from `OT_SO_ITEM` at all; it composes item names
  from this table, so the domain has one confirmed source for them instead of a second guess.

### 9. `OT_SO_ITEM` columns the preview reads

Everything the package body touches is known good — it is running SQL. The preview reads the same
columns and nothing else, so there is nothing left to guess here **as long as it stays that way**:
`SOI_SYS_ID`, `SOI_SOI_SYS_ID`, `SOI_SOH_SYS_ID`, `SOI_ITEM_CODE`, `SOI_GRADE_CODE_1/2`,
`SOI_UOM_CODE`, `SOI_RATE`, `SOI_QTY_BU`, `SOI_DISC_PERC`, `SOI_SHORT_CLO_STATUS`, `SOI_FOC_YN`.

```sql
DESC MGTDAT.OT_SO_ITEM;
```

**Result:** _(pending — low risk; every column above appears in `PKG_MGT_PRICE_CTRL`'s `c_item`
cursor, so it compiles or it does not)_

---

## 2. Post-deploy smoke tests

Section G of `minimum-price-control.sql`, on the **test** schema, after the package compiles and
before the trigger is created. Record the output here.

| # | Test | Expected |
|---|---|---|
| G1 | `P_GET_USD_DIVISOR('IDR', SYSDATE)` | divisor in the thousands, a date, source `BCA` |
| G1b | Same for a date with no BCA rate | source `BCA_PREV` with a date up to 7 days back |
| G1c | Same for a date more than 7 days after the last BCA rate | source `ORION`, or msg 1012111 |
| G1d | `P_GET_USD_DIVISOR('EUR', SYSDATE)` | raises 1012112 |
| G2 | Insert a rule, resolve it for the matching item | the rule's id and price |
| G2b | Insert an `ITEM`+`GRADE_2` rule and an `ITEM`-only rule for the same item | the grade-2 rule wins |
| G2c | Insert an `ITEM`+`GRADE_1` rule alongside the grade-2 one | grade 2 still wins (weight 2 vs 1) |
| G3 | `P_VALIDATE_SO` on a document with an under-floor line | raises 1012110 naming that line |
| G3b | Same document with an approved exception at the exact rate | no raise; log row `OVERRIDE` |
| G3c | Same, exception rate off by 0.001 | raises; the exact-match binding is doing its job |
| G4 | Read back `SALES_MIN_PRICE_CHECK_LOG` | one row per checked line, divisor/date/source all populated |
| **G0** | **Run first, as MGTDAT:** the cross-schema read and insert in `schema.md` §9.6 | both succeed. If not, §9.3's grants are missing or were given through a role — fix that before any other test means anything |

`ROLLBACK` at the end, as the script says. G2b, G2c and G3c are additions to section G worth running —
they are the three behaviours most likely to be assumed rather than verified.

---

## 3. Open questions

| # | Question | Owner | Default if unanswered | Blocks |
|---|---|---|---|---|
| **Q1** | Who creates the four `SALES_MIN_*` tables in MGTHRIS — the Laravel migrations or the DBA from the reworked section A? | Indra / DBA | The migrations, since MGTHRIS is the app's own schema. Either is fine as long as both produce the same thing (`schema.md` §6) | T02 |
| **Q9** | *(settled)* Shared tables are `SALES_CTL_APPR_*`, minimum-price-only tables `SALES_MIN_*`, column prefixes follow the table names, and `minimum-price-control.sql` has been rewritten to match | — | — | settled |
| **Q10** | Which tablespace do MGTHRIS tables use? The DDL still says `TABLESPACE ORION`, inherited from when these were planned for MGTDAT | DBA | Whatever `USER_TABLES` shows for the existing MGTHRIS tables | T02 |
| **Q2** | Who are the min-price approvers, and who are the exception approvers? Named roles, at least two holders each (D9) | Finance manager | `Min Price - Approver` and `Sales Exception - Approver` as in `design.md` §9 | T05, T22 |
| **Q3** | Is there an existing paper form for BOD approval whose layout the PDF must match? | Finance | Design a new one from the request fields | T14 |
| **Q4** | Does Marketing search a sales order by `SOH_NO` alone, or does it need customer + date because the number repeats across TXN codes? | Marketing | Search by `SOH_NO`, list matches, let them pick | T10 |
| **Q5** | Should an exception carry an expiry by default (`spec.md` §5.3)? | Finance | Null — the rate/qty binding is the control | T12 |
| **Q6** | How far back should the initial minimum-price master go — is there a spreadsheet of current floors to import? | Finance | Import what exists; no backfill of historical floors | T08 |
| **Q7** | Does anyone need the rejection report by salesperson rather than by document? `SALES_MIN_PRICE_CHECK_LOG` stores `SMPCL_APPR_UID` (the approver), not the SO owner | Finance | Report by document and approver; add the SO owner via a join if asked | T20 |
| **Q8** | Line-count distribution on ESC/LSC/STA documents — is 30 lines typical or is 300? | data | Assume tens; revisit `design.md` §4.2 if the preview is slow | T11 |
| **Q11** | `OM_ITEM.ITEM_IG_CODE` (`NOT NULL`) is an item group. Is it the grouping a `GROUP`-level minimum price should resolve on, or is it the wrong granularity — an inventory grouping rather than a finished-goods one? | Indra / Finance | **Build nothing.** `F_GET_ITEM_GROUP` keeps returning NULL and the form keeps offering `ITEM` and `ALL` only (PRD §3.3). A group rule that resolved on the wrong grouping would set a floor for products nobody meant to include | Phase 1.1 |

Q1, Q2 and Q3 are the ones that stall work if left; the rest have defaults that are safe to build
against and cheap to change. Q11 is new and is the only one where the safe default is to build
nothing at all.

---

## 4. Deploy order

From PRD §8, with the Laravel side interleaved. The ordering constraint that matters: **the trigger is
last, after UAT**, because until Marketing can request an exception it is a block with no way past.

| Step | What | Where |
|---|---|---|
| 1 | §1 checks 1–7; settle Q10 (tablespace) before any table is created | test + production, read-only |
| 2 | Rework `minimum-price-control.sql` per `schema.md` §9 (Indra) | — |
| 3 | The four tables + the sequence rows and sys-id triggers in MGTHRIS — Laravel migrations or reworked sections A and B (Q1) | test schema |
| 4 | Section C — the two Orion config inserts | test, **MGTDAT** |
| 5 | **Grants MGTHRIS → MGTDAT** (§9.3), direct, not via a role | test |
| 6 | Package (section D), compile, confirm `VALID`, then smoke test **G0** | test, MGTDAT |
| 7 | Grants MGTDAT → MGTHRIS (§9.4), verified by §1 check 5b | test |
| 8 | The rest of §2's smoke tests | test |
| 9 | Laravel P1–P4 (`plan.md`) | app, pointed at the test schema |
| 10 | UAT with Finance and Marketing | test |
| 11 | Steps 3–8 on production; app release | production |
| 12 | Seed the real minimum-price master and get it approved through the app | production |
| 13 | **Section E — the trigger** | production |

Step 5 before step 6, always: a package compiled before its grants exist compiles clean and fails at
runtime, which is the failure this ordering exists to prevent.

Step 12 before step 13, always. Turning the trigger on against an empty master is harmless (every
line resolves `PASS_NORULE`), but turning it on against a half-entered master blocks real orders
against floors nobody agreed to.
