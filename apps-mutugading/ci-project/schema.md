# schema.md — tables

Connection: `oracle_mgtapps`. Per **D2** the three legacy tables keep their names, columns and Oracle
triggers. Everything below is either new or additive; nothing is renamed, dropped or retyped, so the
legacy `mgthris` app keeps working against the same tables during cut-over.

---

## 1. `ci_project_main` — four new columns

Primary key `cpm_sys_id`, filled by the Oracle trigger `CI_PROJECT_MAIN_TRG1` (which also assigns
`cpm_trans_no` from the master sequence). The app inserts `cpm_sys_id = 0` and lets the trigger win —
keep doing exactly that, and keep `public $incrementing = false`.

| Column | Meaning |
|---|---|
| `cpm_sys_id` | PK |
| `cpm_trans_code` | From master `type='TRANS_CODE'`, looked up by group |
| `cpm_trans_no` | Trigger-assigned running number |
| `cpm_date` | Registration date |
| `cpm_dept` | Department code (master `type='DEPT'`) — **the authorization key** |
| `cpm_group` | Master `type='GROUP'` |
| `cpm_project_id`, `cpm_wave`, `cpm_leader`, `cpm_leader_uid`, `cpm_title` | Descriptive |
| `cpm_completion_date`, `cpm_saving_end` | Drive the 20-month rule and auto-finish |
| `cpm_remarks`, `cpm_frequency`, `cpm_improve_type`, `cpm_impact_1..3` | Master-backed attributes |
| `cpm_status` | `ACTIVE` / `INDIRECT-SAVING` / `FINISH` / other (master `type='STATUS'`) |
| `cpm_sav_type` | `LSS` / `PRO_SAV` / `PRO_INC` / `OTH` / `OTH_OT` (master `type='SAV_TYPE'`) |
| `cpm_cr_uid`, `cpm_cr_date`, `cpm_upd_uid`, `cpm_upd_date` | Audit |

### 1.1 New columns — the project approval workflow (D5)

| Column | Type | Null | Meaning |
|---|---|---|---|
| `cpm_appr_status` | `NUMBER(1) DEFAULT 1` | no | `0` Draft, `1` Approved, `2` Amend |
| `cpm_appr_by` | `VARCHAR2(50)` | yes | Who last approved |
| `cpm_appr_date` | `TIMESTAMP` | yes | When |
| `cpm_appr_remark` | `VARCHAR2(500)` | yes | Reason, required when moving to `Amend` |

```sql
ALTER TABLE ci_project_main ADD (
  cpm_appr_status NUMBER(1)    DEFAULT 1 NOT NULL,
  cpm_appr_by     VARCHAR2(50),
  cpm_appr_date   TIMESTAMP,
  cpm_appr_remark VARCHAR2(500)
);
ALTER TABLE ci_project_main ADD CONSTRAINT ci_project_main_appr_ck
  CHECK (cpm_appr_status IN (0,1,2));
```

**The default is `1` (Approved), not `0`, and that is deliberate.** Two reasons:

1. Every project that exists today predates the workflow and is a project of record. The `DEFAULT` backfills
   all of them to `Approved` in one statement — nobody has to re-approve years of history (PRD success
   criterion 7).
2. The legacy app is still inserting rows during cut-over and knows nothing about this column. Those rows
   must land as `Approved`, or projects registered in the old page would silently refuse savings in the new one.

The cost is that the new app must write `cpm_appr_status = 0` **explicitly** on create rather than relying on
the column default. `CiProjectService::create()` sets it; the test in `spec.md` §10 case 13 pins it.

If the column ever needs to become `DEFAULT 0` (after the legacy app is retired), that is a one-line change
in a later migration — see `tasks.md` T20.

### 1.2 Additive indexes

```sql
CREATE INDEX ci_project_main_dept_status_ix ON ci_project_main (cpm_dept, cpm_status);
CREATE INDEX ci_project_main_appr_ix        ON ci_project_main (cpm_appr_status);
```

Every non-privileged query filters on `cpm_dept`, and the list always orders by status. The second serves the
approval queue ("show me every draft"). Advisory — confirm with the DBA before adding; the Laravel migration
is the source of truth.

### 1.3 Additive constraint (recommended, needs a data check first)

`FR-10` requires case-insensitive unique titles, and legacy enforces it only in PHP — two users saving
at once both pass. A functional unique index makes it real:

```sql
CREATE UNIQUE INDEX ci_project_main_title_uix ON ci_project_main (UPPER(cpm_title));
```

**Run this first** and resolve the hits before creating it:

```sql
SELECT UPPER(cpm_title), COUNT(*) FROM ci_project_main
GROUP BY UPPER(cpm_title) HAVING COUNT(*) > 1;
```

If legacy duplicates exist and cannot be cleaned, drop this index and keep the PHP check plus a
transaction-level re-check (see `spec.md` §4.2).

---

## 2. `ci_project_saving` — unchanged columns, one new constraint

| Column | Meaning |
|---|---|
| `cps_sys_id` | PK (trigger-assigned) |
| `cps_cpm_sys_id` | FK → `ci_project_main.cpm_sys_id` |
| `cps_date` | **First day of the saving month** — the period key |
| `cps_saving` | Amount, USD |
| `cps_remark` | Free text |
| `cps_cr_uid`, `cps_cr_date`, `cps_upd_uid`, `cps_upd_date` | Audit |

### 2.1 Additive unique index — this one matters

```sql
CREATE UNIQUE INDEX ci_project_saving_uix ON ci_project_saving (cps_cpm_sys_id, cps_date);
```

The report joins `ci_project_saving` three times, once per month. A duplicate `(project, month)` pair
therefore **multiplies the project row** in the list — one project appears twice, and both its other two
month columns are wrong. Legacy hit exactly this and patched it in PHP by matching and writing the same
date in `firstOrNew`. The constraint is what actually guarantees it.

**Run this first** and clean the duplicates before creating the index:

```sql
SELECT cps_cpm_sys_id, cps_date, COUNT(*), SUM(cps_saving)
FROM ci_project_saving
GROUP BY cps_cpm_sys_id, cps_date
HAVING COUNT(*) > 1;
```

Resolution rule: keep the most recently updated row (`NVL(cps_upd_date, cps_cr_date)` descending), delete
the rest — do **not** sum them; the legacy bug wrote the full amount into each duplicate, so summing
would double the saving.

`cps_date` must be a clean date at midnight, day 1. If any row carries a time component, the equality
join misses it. Check and normalise:

```sql
SELECT COUNT(*) FROM ci_project_saving
WHERE cps_date <> TRUNC(cps_date, 'MM');
```

---

## 3. `ci_project_master` — unchanged shape, three types retired

| Column | Meaning |
|---|---|
| `type` | Row family |
| `code` | Value stored on the project |
| `name` | Display label |
| `remark`, `flex_1`, `flex_2` | Spare |

Types still used as dropdown sources: `GROUP`, `STATUS`, `IMPROVE`, `IMPACT1`, `IMPACT2`, `IMPACT3`,
`FREQUENCY`, `DEPT`, `SAV_TYPE`, `TRANS_CODE`.

Types the new app no longer reads (**D3**, **D4**):

| Type | Was | Becomes |
|---|---|---|
| `ADMIN` | Usernames with full CI access | `finance-ci_project-*` permissions |
| `CAN_VIEW_ALL` | Usernames who see all departments | `finance-ci_project-view_all_scope` |
| `APPROVE` | One row: `code` = cut-off date, `name` = when it was set | `ci_project_period` rows |

The rows are **left in place**, not deleted, so the legacy app keeps working until it is switched off.
The new app never writes `ADMIN` or `CAN_VIEW_ALL`, and writes `APPROVE` only as the compatibility
dual-write described in §4.3. A follow-up migration deletes all three types after cut-over.

The table has no primary key. Give the model `public $incrementing = false` and address rows by
`(type, code)`; the master maintenance page must treat that pair as the identity.

---

## 4. `ci_project_period` — new

One row per saving month. This is the whole of **D3**.

| Column | Type | Null | Meaning |
|---|---|---|---|
| `cpp_sys_id` | `VARCHAR2(20)` | no | PK, from `SysIdHelper::generate('CI_PROJECT_PERIOD', $user)` |
| `cpp_period` | `DATE` | no | First day of the month. **Unique.** |
| `cpp_status` | `NUMBER(1)` | no | `0` Draft, `1` Approved, `2` Reopened |
| `cpp_approved_by` | `VARCHAR2(50)` | yes | NIK / user id |
| `cpp_approved_at` | `TIMESTAMP` | yes | |
| `cpp_reopened_by` | `VARCHAR2(50)` | yes | |
| `cpp_reopened_at` | `TIMESTAMP` | yes | |
| `cpp_remark` | `VARCHAR2(500)` | yes | Required on reopen, optional elsewhere |
| `cpp_cr_uid` | `VARCHAR2(50)` | no | |
| `cpp_cr_date` | `TIMESTAMP` | no | `CREATED_AT` |
| `cpp_upd_uid` | `VARCHAR2(50)` | yes | |
| `cpp_upd_date` | `TIMESTAMP` | yes | `UPDATED_AT` |

```sql
CREATE TABLE ci_project_period (
  cpp_sys_id       VARCHAR2(20)  NOT NULL,
  cpp_period       DATE          NOT NULL,
  cpp_status       NUMBER(1)     DEFAULT 0 NOT NULL,
  cpp_approved_by  VARCHAR2(50),
  cpp_approved_at  TIMESTAMP,
  cpp_reopened_by  VARCHAR2(50),
  cpp_reopened_at  TIMESTAMP,
  cpp_remark       VARCHAR2(500),
  cpp_cr_uid       VARCHAR2(50)  NOT NULL,
  cpp_cr_date      TIMESTAMP     DEFAULT SYSTIMESTAMP NOT NULL,
  cpp_upd_uid      VARCHAR2(50),
  cpp_upd_date     TIMESTAMP,
  CONSTRAINT ci_project_period_pk  PRIMARY KEY (cpp_sys_id),
  CONSTRAINT ci_project_period_uk  UNIQUE (cpp_period),
  CONSTRAINT ci_project_period_ck  CHECK (cpp_status IN (0,1,2))
);
```

The Laravel migration is the source of truth; write it SQLite-compatible (`string`, `date`, `tinyInteger`,
`timestamp`) so CI can run it, and guard it with the standard `DISABLE_MIGRATIONS` key
`create_ci_project_period_table`.

### 4.1 Why there is no `Submitted`

An earlier draft had `Draft → Submitted → Approved`. It is gone: nobody submits a month. Departments type
savings continuously and the coordinator closes the month when they judge it complete — there is no moment
when a department hands the month over, so a `Submitted` state would only ever be a flag the coordinator sets
on themselves one click before approving. The columns `cpp_submitted_by` / `cpp_submitted_at` and the
`finance-ci_project-period-submit` permission are dropped with it.

`Reopened` stays, because that one carries information the audit needs: this month was closed and then
deliberately opened again, by whom and why.

### 4.2 Why one row per month and not per month × department

Considered and rejected for now. Legacy approves globally, users think of it as "August is closed", and
per-department rows triple the UI and the queries for a rule nobody has asked for. The table is shaped so
that adding `cpp_dept` later is a column plus a widened unique key — the lock lookup already goes through
one service method (`spec.md` §2.4), so the change stays contained.

### 4.3 Backfill

Run once, inside the migration:

1. Read the legacy cut-off: `SELECT code FROM ci_project_master WHERE type = 'APPROVE'`.
2. Find the first saving month: `SELECT MIN(TRUNC(cps_date,'MM')) FROM ci_project_saving`.
3. Insert one row per month from that first month up to and including the cut-off month with
   `cpp_status = 1` (Approved), `cpp_approved_by = 'SYSTEM'`, `cpp_approved_at` = the legacy `name` value
   when it parses as a timestamp, otherwise the migration time, and
   `cpp_remark = 'Backfilled from legacy APPROVE cut-off'`.
4. Insert rows with `cpp_status = 0` (Draft) for every month after the cut-off up to the current month.

Months with no row at all are treated as open (`spec.md` §2.4), so step 4 is convenience, not correctness.

### 4.4 Compatibility dual-write (cut-over only)

While the legacy app is still reachable, approving month *M* must also do:

```
UPDATE ci_project_master SET code = <M as YYYY-MM-01>, name = <now> WHERE type = 'APPROVE'
```

using the **highest approved month**, not necessarily *M* — reopening month *M* must lower the legacy
cut-off to the highest still-approved month below it, or the legacy app will keep the month locked.

This lives in one method (`CiProjectPeriodService::syncLegacyCutOff()`), is guarded by a config flag
`finance.ci_project.legacy_cutoff_sync` defaulting to `true`, and is deleted together with the flag once
the legacy app is switched off.

### 4.5 Sequence

Add a `CI_PROJECT_PERIOD` row to `HmMstSequences` (prefix `CPP`, date segment, 4-digit padding) so
`SysIdHelper` can serve ids. Alternatively let Oracle own it with a trigger, like the other CI tables —
but `SysIdHelper` is the house standard for new tables and works under SQLite in CI, so prefer it.

---

## 5. Migration list

| Order | Key | Contents |
|---|---|---|
| 1 | `create_ci_project_period_table` | §4 table + backfill §4.3 |
| 2 | `add_ci_project_main_approval_columns` | §1.1 — the `DEFAULT 1` is what backfills history |
| 3 | `add_ci_project_saving_unique_index` | §2.1 — **must run after the duplicate clean-up** |
| 4 | `add_ci_project_main_indexes` | §1.2, and §1.3 if the title check comes back clean |
| 5 | *(post cut-over, separate PR)* `drop_ci_project_master_access_rows` | Delete `ADMIN` / `CAN_VIEW_ALL` / `APPROVE` rows and the dual-write flag; optionally flip `cpm_appr_status` to `DEFAULT 0` |

The permissions of `design.md` §7.1 are **not** a migration. They are created by
`CiProjectRolesAndPermissionsSeeder` together with the roles that carry them — permissions and role grants
are seed data, and splitting them across a migration and a seeder means two places to keep in step. The
cost is that the seeder is a required deploy step; see `design.md` §7.2.

Every migration follows the project's `migrationDisabled()` guard and provides a real `down()`. Migration 3
must not silently fail: if duplicates remain, let it throw — a half-applied lock is worse than a blocked deploy.
