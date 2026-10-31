# spec.md — behaviour contracts

The reference to keep open while coding. Every rule here is either ported from the legacy page or is a
deliberate change; the changes are marked **[CHANGE]** and explained.

---

## 1. The report query

### 1.1 The three month columns

Given the selected month `P` (`Y-m`, defaulting to **last month**, `now()->subMonthNoOverflow()`):

| Column | Month | Header format |
|---|---|---|
| `save_3` | `P − 2 months` | `M Y` |
| `save_2` | `P − 1 month` | `M Y` |
| `save_1` | `P` | `M Y` |
| input | `P` | writable |

All three are `startOfMonth()`. Month arithmetic uses `subMonthNoOverflow()` — with plain `subMonth()`,
31 March minus one month is 3 March in Carbon, and the query silently returns nothing.

The query left-joins `ci_project_saving` three times, once per month, on
`cps_cpm_sys_id = cpm_sys_id AND cps_date = <month start>`, selecting `cps_saving` and `cps_sys_id` for each.
This is correct **only** while `(cps_cpm_sys_id, cps_date)` is unique — see `schema.md` §2.1.

### 1.2 Ordering

`ACTIVE` (1) → `INDIRECT-SAVING` (2) → `FINISH` (3) → anything else (4), then `cpm_trans_no`
`desc`/`asc` per the toggle. The weights come from `CiProjectStatusEnum::sortWeight()`.

### 1.3 Filters

| Filter | Behaviour |
|---|---|
| `dept` | Exact `cpm_dept =`. Ignored when empty |
| `group` | Exact `cpm_group =` |
| `status` | Exact `cpm_status =` |
| `approvalStatus` | Exact `cpm_appr_status =` (D5). Defaults to all; the coordinator's queue is this filter set to `Draft` |
| `search` | `Searchable` scope over the text columns in `$searchable` — case-insensitive contains |

**[CHANGE]** The search covers text columns only, not every fillable column. Searching a date column with
`UPPER(col) LIKE` makes Oracle convert every row and can raise `ORA-01861` on some NLS settings. Users who
searched by date were matching the display string, which the new page renders in the view anyway — if date
search turns out to be needed, add an explicit `TO_CHAR(cpm_date,'YYYY-MM-DD')` predicate rather than
widening `$searchable`.

### 1.4 Department scope (FR-4)

Applied in the repository, before any user filter:

```
user has role Super Admin  OR  permission finance-ci_project-view_all_scope
    → no restriction
otherwise
    → where cpm_dept = <employee's hmemd_hmdep_dept_id>
```

If the signed-in employee has **no** department, the query returns nothing and the page shows an empty state
with an explanatory message. **[CHANGE]** Legacy called `abort(401)` — a hard failure on a data problem that
is not the user's fault, and one that produced a confusing login-looking error.

The department dropdown is filtered to match: everything for a view-all user, the user's own department
otherwise.

---

## 2. Period approval (D3)

### 2.1 States

`Draft (0)` → `Approved (1)`; `Approved` → `Reopened (2)`; `Reopened` → `Approved`.

Only `Approved` locks. There is no `Submitted` — see `schema.md` §4.1.

### 2.2 Transition table

| From | To | Permission | Requires | Writes |
|---|---|---|---|---|
| Draft / Reopened | Approved | `finance-ci_project-period-approve` | — | `cpp_approved_by`, `cpp_approved_at`, legacy dual-write |
| Approved | Reopened | `finance-ci_project-period-reopen` | **a remark — required, any length** | `cpp_reopened_by`, `cpp_reopened_at`, `cpp_remark`, legacy dual-write |

The reopen remark is `required` and trimmed — an empty or whitespace-only string is refused — with **no
minimum length**; `max:500` applies from the column. Same rule and same reasoning as the amend remark
(§2A.2): what matters is that the field cannot be skipped, not how long it is.

Any other pair is rejected by `CiProjectPeriodStatusEnum::allowedTransitions()` before the service runs.
A transition on a period row that does not exist creates it as `Draft` first.

Approve is allowed on a month in the future or the current month — the coordinator sometimes closes early.
No calendar restriction; the permission is the control.

Every transition writes an activity-log entry (`LogsActivityWithDescription`) naming the month, the old and
new status, the actor and the remark.

### 2.3 Legacy dual-write

After any transition that changes the set of approved months, call
`CiProjectPeriodService::syncLegacyCutOff()`:

```
cutoff = highestApprovedPeriod()      // MAX(cpp_period) WHERE cpp_status = Approved
if cutoff is null: leave the legacy row untouched
else: UPDATE ci_project_master SET code = cutoff, name = now() WHERE type = 'APPROVE'
```

Note the reopen case: reopening the newest approved month must **lower** the legacy cut-off, otherwise the
legacy app keeps that month locked while the new app has opened it. Taking the max rather than the
transitioned month is what handles that.

Guarded by `config('finance.ci_project.legacy_cutoff_sync')`. Delete the method and the flag once the legacy
app is retired.

### 2.4 The lock question

One method answers it everywhere:

```php
CiProjectPeriodService::isLocked(CarbonInterface $period): bool
    // period row missing            → false (open)
    // row status Approved           → true
    // any other status              → false
```

**[CHANGE]** Legacy compared the selected month against a single global date:
`approveDate >= targetDate`. Two consequences it had, both gone: every month at or below the cut-off was
locked as one undifferentiated block with no record of who closed which month, and moving the date backwards
to fix one month silently reopened every month after it.

---

## 2A. Project approval (D5)

Wholly new — legacy has no such concept. Do not confuse it with `cpm_status` (`ACTIVE` / `INDIRECT-SAVING` /
`FINISH`), which is the project's lifecycle and stays exactly as it is. A project can be `FINISH` and
`Approved` at the same time; the two answer different questions.

### 2A.1 States

| State | Value | Meaning | Editable | Accepts savings | Deletable |
|---|---|---|---|---|---|
| `Draft` | 0 | Registered, not yet approved | yes | yes | yes |
| `Approved` | 1 | A project of record | **no — amend first** | yes | **no** |
| `Amend` | 2 | Approved, but reopened for correction | yes | yes | no |

**Approval status never gates saving input.** All three states take savings; the workflow controls who may
change the project's own attributes, and nothing else. A department that has registered a project can start
recording against it the same day, whether or not the coordinator has got to it yet — approval is a review,
not a queue the work waits behind.

`Draft` and `Amend` are therefore behaviourally identical. What separates them is provenance, and it matters
to the audit: a `Draft` has never been approved, an `Amend` was approved once and is being corrected, and its
`cpm_appr_remark` says why. Keep them as two cases rather than collapsing them into one "open" state.

### 2A.2 Transition table

| From | To | Permission | Requires | Writes |
|---|---|---|---|---|
| Draft | Approved | `finance-ci_project-approve` | — | `cpm_appr_status=1`, `cpm_appr_by`, `cpm_appr_date` |
| Approved | Amend | `finance-ci_project-amend` | **a remark — required, any length** | `cpm_appr_status=2`, `cpm_appr_remark`, `cpm_appr_by`, `cpm_appr_date` |
| Amend | Approved | `finance-ci_project-approve` | — | `cpm_appr_status=1`, `cpm_appr_by`, `cpm_appr_date`, clears `cpm_appr_remark` |

Everything else is rejected by `CiProjectApprovalStatusEnum::allowedTransitions()` before
`CiProjectApprovalService` runs — including `Draft → Amend`, which has no meaning, and `Approved → Draft`,
which would erase the fact that the project was ever approved.

A project may only be transitioned by someone who can also see it (department scope, §1.4). Approving is
**not** restricted to another department than the creator's — the separation is by permission, and the
`CI Project - Department` role does not carry `approve` (`design.md` §7.2). If a same-person check is ever
wanted, it belongs in `CiProjectAuthorizationService::canApproveProject()` and nowhere else.

The amend remark is `required` and trimmed — an empty or whitespace-only string is refused — but carries **no
minimum length**. `max:500` still applies (the column). A length floor only teaches people to type `..........`;
the rule that matters is that the field cannot be skipped, and the reason ends up in the activity log with a
name against it.

Every transition writes an activity-log entry naming the project, both statuses, the actor and the remark.

### 2A.3 Why `Amend` rather than "just edit it"

An approved project is the record the savings hang off. Editing its department, its saving type or its
saving-end date changes what months of savings mean, and legacy allowed exactly that with no trace. Amend
makes the change visible: the project leaves `Approved`, carries a reason, and someone with the approve
permission has to put it back. The cost is two extra clicks for the coordinator, which is why `amend` and
`approve` are separate permissions — one person can hold both.

This is the whole of what the project workflow does. It is an **edit gate with an audit trail**, not a gate on
the work: the savings keep flowing in whatever state the project is in.

### 2A.4 Interaction with the period lock

The two are independent, and only one of them touches saving input. Whether a saving may be entered is
decided entirely by §3.2 — the month's lock, the `FINISH` status, and the user's scope. A project moving into
`Amend` does not unlock a closed month, and a month being open does not make an `Approved` project editable.

### 2A.5 Drafts and reporting

`Draft` projects appear in the list, marked and filterable, and their savings count in every total and every
export exactly like anyone else's — a draft is a real project doing real work, it simply has not been reviewed
yet. The exports carry an approval-status column so a reader can see which figures come from projects still
awaiting approval, and the report page has an approval filter for the same purpose.

If the coordinator ever wants unapproved savings held out of the pivot totals, that is a filter on the export
and a decision to take deliberately — see `gap-analysis.md` Q11. It is not the default, because a number that
silently disappears from a report is worse than one that is labelled.

---

## 3. Saving input

### 3.1 Write path (FR-6, FR-8)

`CiProjectSavingService::store(CiProjectSavingEntryData $entry)`:

1. Load the project. Missing → warning toast, stop.
2. `canInputSaving()` (§5) — refused → warning toast, stop. **No exception**; the user just typed in the
   wrong row.
3. Normalise `period` to `startOfMonth()`.
4. Inside a transaction: `upsertForPeriod()` matches `(cps_cpm_sys_id, cps_date)`; on update write
   `cps_saving`, `cps_upd_uid`, `cps_upd_date`; on insert write `cps_date` **from the same value that was
   matched on**, plus `cps_saving`, `cps_cr_uid`, `cps_cr_date`.
5. Success toast, clear that row's input state, refresh only that row's figures.

Step 4's "same value" is not a detail. Legacy matched on `$this->dateSaving` but had an earlier version that
wrote `now()` — every save inserted a fresh row, and because the report joins savings three times, each extra
row duplicated the project in the list.

An empty input is a no-op, not a write of `0`. Clearing a saving is a separate, deliberate action (leave it
out of phase 1; if asked for, it is an explicit "clear" button that deletes the row, permissioned the same as
input).

### 3.2 Read-only rules (FR-7, FR-9)

The input renders read-only when any of these holds, and the same conditions are re-checked in the service:

| Condition | Bypass |
|---|---|
| `isLocked(period)` | `finance-ci_project-saving_input_locked` |
| `cpm_status === FINISH` | `finance-ci_project-status_finish_edit` |
| no `finance-ci_project-saving_input` | — |
| project outside the user's department | `finance-ci_project-edit_all_scope` |

Placeholder text follows: `FINISHED!`, `APPROVED!`, or `Input here…`.

The project's approval status is **not** in this table (§2A.1). A `Draft`, an `Amend` and an `Approved`
project all take savings on the same terms.

**[CHANGE]** Legacy enforced the FINISH rule as one bulk query over all submitted ids and refused the whole
batch if any project was finished. Per-row is both clearer to the user and unavoidable now that the write
path is per row.

---

## 4. Project create & edit

### 4.1 Required fields (FR-10)

`sav_type`, `dept`, `group`, `title`, `frequency`, `status`, `improve_type`, `impact_1`.
Optional: `project_id`, `wave`, `leader`, `leader_uid`, `impact_2`, `impact_3`, `remarks`,
`completion_date`, `saving_end`.

All string inputs are trimmed before validation.

### 4.2 Title uniqueness

Case-insensitive, against every project, excluding the row being edited. Checked in the service **inside the
same transaction as the insert**, and backed by the functional unique index of `schema.md` §1.3 where the
data allows it. A unique-violation from Oracle is caught and turned into the same field error, so a race
produces a form error rather than a 500.

**[CHANGE]** Legacy checked uniqueness on create only; an edit could rename a project onto an existing title.

### 4.3 Date rules (FR-12)

If either `completion_date` or `saving_end` is filled, both become required, and:

- `completion_date <= saving_end`
- without `finance-ci_project-validation_bypass`: `saving_end <= completion_date + 20 months`
  (`addMonthsNoOverflow(20)`)

Messages: *"Completion Date must be before Saving End"*, *"Saving End must be after Completion Date"*,
*"Saving End cannot be more than 20 months after Completion Date"*.

The UI convenience of pre-filling `saving_end` at completion + **18** months stays (legacy did it in jQuery;
do it in Alpine, or in an `updated` hook on the DTO field). Note the deliberate gap: the suggestion is 18
months, the ceiling is 20.

### 4.4 Group filtering by saving type (FR-11)

From `CiProjectSavingTypeEnum::allowedGroupCodes()`:

| Saving type | Groups offered | Default |
|---|---|---|
| `LSS` | `LSS BB`, `LSS GB`, `GKM` | — |
| `PRO_SAV` | `PRO.SAV` | pre-selects `PRO.SAV` |
| `PRO_INC` | `PRO.INC` | pre-selects `PRO.INC` |
| `OTH`, `OTH_OT` | everything except `PRO.SAV`, `PRO.INC`, `LSS BB`, `LSS GB`, `GKM` | — |
| unset | all | — |

Changing the saving type clears a group that is no longer valid. **[CHANGE]** Legacy left a stale group
selected because the filter only affected the rendered options.

### 4.5 Create

- `cpm_sys_id` is sent as `0`; the Oracle trigger assigns the real id and `cpm_trans_no`. Do not
  "improve" this — the numbering is shared with the legacy app.
- `cpm_trans_code` is looked up from master `type='TRANS_CODE'` by the chosen **group**.
- `cpm_date` = today. `cpm_cr_uid` = the signed-in user, `cpm_cr_date` = now.
- `cpm_appr_status` = `Draft`, **written explicitly** — the column defaults to `Approved` for the reasons in
  `schema.md` §1.1, so relying on the default here would let every new project skip approval. This is the one
  place in the domain where the database default is deliberately not the application default.

### 4.6 Edit (FR-13, FR-14)

Permission chain, in order:

1. `finance-ci_project-edit` is required at all.
2. The project's department must equal the user's, **unless** `finance-ci_project-edit_all_scope`.
3. The project's approval status must be `Draft` or `Amend` (D5). An `Approved` project is refused with
   *"Approved projects must be amended before they can be changed"* and, when the user holds
   `finance-ci_project-amend`, an Amend action alongside the message.
4. If the project's `cpm_date` month is already closed (`isLocked(cpm_date)`),
   `finance-ci_project-saving_input_locked` is required.

**[CHANGE]** The check runs in `edit()` (opening the modal) as well as in `save()`. Legacy checked only on
save, so a user could load another department's project into the form, and the refusal only arrived after
they had filled it in.

`cpm_trans_code`, `cpm_trans_no`, `cpm_sys_id`, `cpm_date` and `cpm_cr_*` are never updated.

### 4.7 Delete (FR-15)

`finance-ci_project-delete`, checked in the action and not only in the view. Confirmation names the
transaction number. Deleting a project with savings deletes them too — do it in one transaction and log the
saving count in the activity entry, because the amounts are gone afterwards.

Only a `Draft` project can be deleted (D5). An `Approved` or `Amend` project is a record of a project that
was accepted; removing it would take its savings with it and leave a hole in every past month's total, and
the correct action is `cpm_status = FINISH` or an amendment. This is refused in the service regardless of
permission.

A draft can carry savings too (§2A.1), so the confirmation names the saving count when there is one —
"this will also delete 4 recorded savings" — rather than implying a draft is always empty.

---

## 5. Authorization matrix

`CiProjectAuthorizationService` is the only place that answers these.

| Question | True when |
|---|---|
| `canView()` | `finance-ci_project-view` |
| `canViewAllDepartments()` | role `Super Admin` **or** `finance-ci_project-view_all_scope` |
| `canCreate()` | `finance-ci_project-create` |
| `canEdit($project)` | `finance-ci_project-edit` **and** (own department **or** `-edit_all_scope`) **and** approval status is `Draft` or `Amend` **and** (project month open **or** `-saving_input_locked`) |
| `canDelete($project)` | `finance-ci_project-delete` **and** approval status is `Draft` |
| `canInputSaving($project, $period)` | `-saving_input` **and** (own department **or** `-edit_all_scope`) **and** (period open **or** `-saving_input_locked`) **and** (status ≠ FINISH **or** `-status_finish_edit`). Approval status is not consulted |
| `canApproveProject($project)` | `finance-ci_project-approve` **and** visible to the user **and** the enum allows `→ Approved` from its current state |
| `canAmendProject($project)` | `finance-ci_project-amend` **and** visible to the user **and** current state is `Approved` |
| `canBypassDateValidation()` | `-validation_bypass` |
| `canTransition($period, $to)` | the transition's permission in §2.2 **and** the transition is allowed by the enum |
| `canManageMasters()` | `finance-ci_project-master-manage` |
| `canExport()` / `canExportPivot()` | `-export` / `-export_pivot` |

Spatie throws `PermissionDoesNotExist` when a permission row is missing. Treat that as "not granted"
(the pattern in `AppliesEmployeeAuthorization`), so a lagging seeder degrades to no access rather than a 500.

---

## 6. Saving detail (FR-16)

`savingsByYear($projectSysId)` returns:

```
[
  2024 => [1 => 0.0, 2 => 1200.5, …, 12 => 0.0, 'Total' => 14300.75],
  2025 => [...],
  'Total' => ['Total' => 31900.25],
]
```

Years ascending, months always 1–12 (absent months are `0.0`), per-year total, then a grand total row.
Grouping happens in SQL. A project with no savings shows an empty state inside the modal rather than
refusing to open — **[CHANGE]**; legacy toasted "Savings Data Not Found" and never opened the modal, which
made it impossible to confirm from the UI that a project really had nothing.

---

## 7. Exports

All three take the same `CiProjectFilterData` the screen holds and go through the repository, so the
department scope and every filter apply identically (FR-25).

| Export | Shape |
|---|---|
| Horizontal | One row per project; the descriptive columns, an approval-status column (D5), then saving columns grouped by year × month across every year that has data |
| Vertical | One row per project × saving month |
| Pivot | `cpm_sav_type` / `cpm_frequency` / `cpm_dept` × year, pivoted to 12 month columns, years 2021 → current |

Pivot additionally requires `finance-ci_project-export_pivot` (legacy tied it to view-all) and is built by
`EloquentCiProjectRepository::pivotRows()` — the Oracle `PIVOT` statement with bound parameters, not string
interpolation and not the `DB::` facade in the export class.

All three dispatch `ExportCiProject` and notify with a download link (FR-26).

Legacy `CiProjectExportPivot` (v1) is not ported.

---

## 8. Master maintenance page (D6, FR-27 / FR-28 / FR-36 / FR-37)

### 8.1 Types offered

`GROUP`, `STATUS`, `IMPROVE`, `IMPACT1`, `IMPACT2`, `IMPACT3`, `FREQUENCY`, `DEPT`, `SAV_TYPE`, `TRANS_CODE`.

`ADMIN`, `CAN_VIEW_ALL` and `APPROVE` are neither offered nor listed. They still exist in the table for the
legacy app (`schema.md` §3) and are deleted after cut-over.

### 8.2 Identity and the rename trap

`ci_project_master` has **no primary key**; `(type, code)` is the identity, and `code` is what
`ci_project_main` stores — `cpm_group`, `cpm_status`, `cpm_dept` and the rest hold the code, not a foreign
key. So:

- Creating a `(type, code)` that already exists is refused.
- `code` is **read-only** on a row with `usageCount() > 0`. Renaming it would leave every project holding the
  old string pointing at nothing — the project would keep its value, the dropdown would no longer contain it,
  and the row would quietly drop out of every filtered list. `name` and the flex columns stay editable.
- Deleting a row with `usageCount() > 0` is refused, naming the count: *"Used by 14 projects."*

All three checks run in `CiProjectMasterService`, not only in the form.

### 8.3 Special rows

| Type | Extra rule |
|---|---|
| `TRANS_CODE` | Its `name` must match a `GROUP` code — that is how `cpm_trans_code` is resolved on create (§4.5). The form validates it against the existing groups and warns when a group has no `TRANS_CODE` row |
| `DEPT` | Its `code` should match an `hmemd_hmdep_dept_id`; a mismatch hides projects from their own department (`gap-analysis.md` Q6). The form shows a warning, not an error — legacy data may already diverge |
| `SAV_TYPE`, `STATUS` | The application also knows these as enums (`design.md` §3). Adding a master row does **not** create an enum case: a new saving type gets no group filtering and a new status no sort weight until the enum is extended. The page says so next to those two types |

### 8.4 Cache

`save()` and `delete()` flush `finance.ci_project.masters` themselves (FR-28). The legacy manual clear-cache
button does not come across; if a stale dropdown is ever reported, the bug is a write path that bypassed the
service.

---

## 9. Auto-finish (FR-29)

Daily. One statement:

```
UPDATE ci_project_main
   SET cpm_status = 'FINISH', cpm_upd_uid = 'SYSTEM', cpm_upd_date = <now>
 WHERE cpm_status = 'ACTIVE'
   AND cpm_saving_end < TRUNC(SYSDATE)
```

Collect the affected transaction numbers first (one `SELECT`) so the log and the activity entry can name
them. Log to `command_ci_project_auto_finish` at `info` for the summary and `debug` per project; failures at
`error` with the exception, and the command exits non-zero.

Note the interaction with §3.2: once a project is `FINISH`, its saving input is read-only for anybody without
`-status_finish_edit`. That is the intended behaviour and matches legacy.

---

## 10. Test checklist (Pest)

| # | Test |
|---|---|
| 1 | A user without `view_all_scope` sees only their own department's projects — asserted on the repository, not the component |
| 2 | Approving a period locks saving input; a user with `-saving_input_locked` can still write |
| 3 | Saving the same project and month twice updates one row and creates no second row |
| 4 | Reopen is refused without a remark (whitespace-only included), accepted with a one-character one, and lowers the legacy cut-off to the next highest approved month |
| 5 | The period enum rejects every pair outside §2.2 — `Draft → Reopened` in particular |
| 6 | `saving_end` more than 20 months after `completion_date` fails without the bypass, passes with it |
| 7 | Editing a project in another department is refused when opening the modal, not only on save |
| 8 | A case-differing duplicate title is refused on create **and** on edit |
| 9 | Auto-finish flips only ACTIVE projects whose saving end has passed, and writes `SYSTEM` |
| 10 | Each export's row count and totals match `reportQuery()` for the same filters |
| 11 | An employee with no department gets an empty list, not a 401 |
| 12 | A `Draft` project accepts savings and accepts an edit, on exactly the same terms as an `Approved` one accepts savings — approval status changes neither |
| 13 | `create()` writes `cpm_appr_status = Draft` explicitly — a project created through the app is never born approved, whatever the column default is |
| 14 | An `Approved` project refuses `edit()` and `delete()`; after `amend()` with a remark it accepts `edit()`, and `approve()` returns it to `Approved` |
| 15 | The project enum rejects `Draft → Amend` and `Approved → Draft` |
| 16 | `amend()` without a remark, or with a whitespace-only one, is refused; a one-character remark is accepted |
| 17 | A user with `approve` but not `amend` cannot amend, and vice versa |
| 18 | A master row in use refuses a `code` change and refuses deletion, naming the count; an unused one allows both |
| 19 | Saving a master row flushes the cache — the new value appears in the report dropdown on the next render |
| 20 | Existing projects (rows predating the approval columns) read back as `Approved` and behave exactly as before |
