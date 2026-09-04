# spec.md — behaviour

The contracts. Keep this open while coding. Where a rule exists in Oracle as well, the Oracle side is
named — those are the pairs that must not drift.

---

## 1. Scope of the control

A sales order is checked when **all** of these hold:

- `SOH_TXN_CODE` is listed in `IM_VS_STATIC_VALUE` under `VSSV_VS_CODE = 'MINPRC_MGT'`
  (`ESC`, `LSC`, `STA` today) — read the table, never hardcode;
- `SOH_APPR_STATUS` is moving from not-3 to 3.

A line within such a document is checked unless:

| Skipped when | Why |
|---|---|
| `NVL(SOI_SHORT_CLO_STATUS, 2) <> 2` | short-closed, not being sold |
| `NVL(SOI_FOC_YN, 'N') = 'Y'` | free of charge, no price to floor |
| `NVL(SOI_RATE, 0) = 0` | already blocked by `ODBTRG_CONTRACT_VAL` |
| no matching rule resolves | nothing to compare against — `PASS_NORULE`, silent |

The preview page (`PricePreviewService`) applies the same four rules. A line the trigger will not look
at must not appear as checkable on screen.

---

## 2. The approval request state machine

`ApprovalRequestService`. Shared by all control types; Phase 1 exercises `PRICELIST` and `MINPRICE`.

```
DRAFT ──print──▶ PRINTED ──approve──▶ APPROVED
  │                 │                    │
  │                 ├──reject──▶ REJECTED│
  │                 └──revise──▶ (new row, revision+1, DRAFT) ; this row ▶ VOID
  └──cancel──▶ CANCELLED
```

### 2.1 Transitions

| From → To | Permission | Guards | Side effects |
|---|---|---|---|
| — → `DRAFT` | `*-create` / `*-draft` | type-specific validation (§3, §4) | `SCAR_REQ_NO` allocated, `SCAR_REVISION = 0`, `SCAR_CR_UID/DT` |
| `DRAFT` → `PRINTED` | `*-print` | request is complete; `MINPRICE` has ≥ 1 line | PDF streamed; `SCAR_PRINT_COUNT + 1`, `SCAR_PRINT_DT/UID`; **content locks** |
| `PRINTED` → `PRINTED` | `*-print` | — | reprint: count and stamp only, no status change |
| `PRINTED` → `APPROVED` | `*-approve` | attachment present; `SCAR_BOD_DOC_NO`, `_DT`, `_SIGNER` all present | `SCAR_APPR_UID/DT`; for `PRICELIST`, the covered `SALES_MIN_PRICE` rows go `APPROVED` and any superseded rows get their `SMP_VALID_TO` (§4.3) — one transaction |
| `PRINTED` → `REJECTED` | `*-approve` | `SCAR_REJ_REASON` required | terminal |
| `PRINTED` → `DRAFT`(rev+1) | `*-draft` | — | §2.3 |
| `DRAFT` → `CANCELLED` | owner or `*-approve` | — | terminal |
| `APPROVED` → `VOID` | `*-void` | reason required | for `PRICELIST`, the rules it approved go `VOID` too |

Anything not in this table is refused. The transition method is the only writer of `SCAR_STATUS`; no
service and no Livewire component sets it directly.

### 2.2 The lock

Once `SCAR_STATUS <> 'DRAFT'`, the request's content — lines, dates, amounts, reason — is immutable.
`assertEditable()` at the top of every mutating service method, and the form fields disabled in the
view. Both: the disabled field is the courtesy, the assertion is the rule.

What stays writable after `PRINTED`: the attachment, the three BOD document fields, and the rejection
reason. Those are the record of the signature, not the request.

### 2.3 Revision

Editing something printed is not an edit. It is:

1. copy the header to a new row — same `SCAR_REQ_NO`, `SCAR_REVISION + 1`, status `DRAFT`, fresh
   `SCAR_CR_UID/DT`, print count 0, no attachment, no BOD fields;
2. copy the lines;
3. old row → `VOID`.

All in one transaction. `SALES_CTL_APPR_REQUEST_UK01 (REQ_NO, REVISION)` catches a concurrent second
revision — let it throw and show "this request was revised by someone else, reload".

`SCAR_PRINT_COUNT` is deliberately not carried over, and the report shows the per-revision count and
the total. A request on revision 4 with eleven prints is a signal about the process, which is what
PRD §3.1 wanted the column for.

### 2.4 Numbering

`schema.md` §7.2. Allocate at `DRAFT` creation, never at print — a request people can refer to before
it exists on paper is worth more than a gapless series. Gaps from cancelled drafts are expected and
fine.

---

## 3. Minimum price master

### 3.1 A rule

| Field | Rule |
|---|---|
| `SMP_SCOPE_LEVEL` | `ITEM` or `ALL` in Phase 1. `GROUP` accepted by the resolver, not offered in the UI (`schema.md` §4.3) |
| `SMP_SCOPE_VALUE` | item code for `ITEM`, literal `*` for `ALL`; the code must exist in `MGTDAT.OM_ITEM.ITEM_CODE` (`VARCHAR2(20)`, same width as this column). The check is best-effort — a cross-schema lookup that is down must not stop price maintenance, and a wrong code produces a rule that never resolves, which is visible and fixable |
| `SMP_GRADE_CODE_1/2` | a grade value or `*`. Never null — the DDL defaults to `*` and the resolver matches `IN ('*', :value)` |
| `SMP_UOM_CODE` | required, part of the rule key |
| `SMP_MIN_PRICE_USD` | `> 0`, 3 decimals. Same precision as `ODBTRG_SOI_DECML_DIGIT_MGT` allows on a rate |
| `SMP_TOLERANCE_PCT` | 0–100, default 0 |
| `SMP_VALID_FROM` | required |
| `SMP_VALID_TO` | optional; `>= VALID_FROM` |

### 3.2 Overlap

`schema.md` §4.1. Rejected at save with a message naming the conflicting rule's id and its period —
"overlaps rule 1042 (01-01-2026 → open)". A validation message that does not say which row is not
actionable when a user is looking at 300 of them.

### 3.3 Maker-checker

Drafts are created and edited freely. A batch of drafts is submitted as one `PRICELIST` request,
which prints as one form: every rule on one sheet, one signature. `SMP_SCAR_SYS_ID` links them.

A rule is live **only** when `SMP_STATUS = 'APPROVED'` — `P_GET_MIN_PRICE` filters on it. A draft
affects nothing, which is what makes drafting safe.

### 3.4 Supersede

`schema.md` §4.2. The UI action is "change price", and it opens a form pre-filled from the existing
rule with an effective date. It creates a draft; it does not touch the live rule until approval.

Closing the old row uses `effective_date − 1 day`, so there is no day on which both rules resolve.
Off-by-one here means a day where the wrong floor applies and nobody notices for a month.

### 3.5 Excel import

`Maatwebsite\Excel`, queued (`ImportMinPrice`), `ReportStatusNotification` on completion — the house
import shape. Columns match §3.1 one for one.

Every row lands as `DRAFT`. Import is a data-entry shortcut, not an approval path; a spreadsheet that
could create live floors is a spreadsheet that will.

Validate per row and report all failures at once with row numbers. Overlap is checked against the
database **and** within the file — a file that contradicts itself is the common case.

---

## 4. The price-check preview

Input: an SO number (or `SOH_SYS_ID`). Output, per line:

| Column | Source |
|---|---|
| item, grade 1, grade 2, UOM, qty | `OT_SO_ITEM` |
| rate, currency | `OT_SO_ITEM` / `OT_SO_HEAD` |
| USD rate | `rate / divisor`, divisor from `usdDivisor()` — **rounded to 6 decimals, as the package does** |
| floor | `minPrice()` |
| tolerance, effective floor | `floor × (1 − tol/100)` |
| gap | `usd_rate − effective_floor` |
| verdict | `PASS` / `PASS_NORULE` / `UNDER` |
| existing exception | `SALES_CTL_APPR_LINE` join, if one already matches this line |

Header shows the divisor, its date and its source (`BCA` / `BCA_PREV` / `ORION` / `USD`) — the same
three values the trigger will log. When Marketing and Finance disagree about a price, that line is
the answer.

Rules:

- **The preview writes nothing.** No `SALES_MIN_PRICE_CHECK_LOG` row, no request row (D4).
- Out-of-scope `TXN_CODE` → say so plainly and check nothing.
- Currency other than USD or IDR → the gateway raises; show the message, check nothing. This mirrors
  message 1012112.
- No FX rate for the document date → the gateway raises; show "no rate for {date}, contact Finance",
  mirroring 1012111. **Do not fall back to a divisor of 1.** That is precisely the bug in
  `mgt_get_exg_rate_bca` that PRD §4.3 wrote a new wrapper to avoid, and reproducing it in PHP would
  make every IDR line on the page look compliant.

`UNDER` lines are the ones the exception form offers. A line that already has a matching approved
exception is shown as covered, with the request number.

---

## 5. Price exception request

### 5.1 Building it

1. Marketing enters the SO number; the preview runs (§4).
2. They tick `UNDER` lines. Nothing else is selectable.
3. They give one reason for the request (`SCAR_REASON`) — required, and required to be more than a
   few characters. "urgent" is not a reason a board member can act on.
4. Save → `DRAFT`, `SCAR_CTRL_TYPE = 'MINPRICE'`, number `MPO-YYYY-NNNN`.

Header snapshot: `SCAR_SOH_SYS_ID`, `SCAR_CUST_CODE`, `SCAR_TXN_CODE`, `SCAR_DOC_NO`, `SCAR_DOC_DT`,
`SCAR_CURR_CODE`. Line snapshot per ticked line: `SCAL_SOI_SYS_ID`, item, both grades, UOM, currency,
`SCAL_APPROVED_RATE = SOI_RATE`, `SCAL_APPROVED_QTY_BU = SOI_QTY_BU`, plus `SCAL_MIN_PRICE_USD` and
`SCAL_RATE_USD` for the form.

**Rate and quantity come from the SO, never from an input** (D8). There is no price field on this
form.

### 5.2 What the binding means downstream

`F_GET_OVERRIDE` matches `SCAL_APPROVED_RATE = SOI_RATE` exactly and
`SCAL_APPROVED_QTY_BU >= SOI_QTY_BU`. So after approval:

| The SO changes | Result |
|---|---|
| rate lowered | exception no longer matches → blocked → new request |
| quantity raised above approved | same |
| rate raised, or quantity cut | still matches |

Say this on the request screen in one sentence. It is the rule that generates support tickets.

### 5.3 Validity

`SCAR_VALID_TO` is checked by `F_GET_OVERRIDE` against `SYSDATE` (null = open). A per-SO-line exception
is single-use in practice, so leave `VALID_TO` null by default and let the exact-match binding be the
control. If a policy of "exceptions expire in 30 days" is wanted later, it is a default on this field
and nothing else.

### 5.4 STA inherits from ESC

The package handles it: a line on a document with `SOH_REF_SYS_ID` whose own `SOI_SOI_SYS_ID` points
at a parent line with a matching approved exception resolves as `INHERIT`.

Laravel's part is to **not** ask for a second request. The preview shows such a line as covered by the
parent's request number, and the exception form does not offer it. Marketing asking for the same
approval twice is the failure this saves them from.

The parent-line mapping is assumption, not fact — `verification.md` §1 item 3. Until that is checked,
the preview labels an inherited line "covered by {req_no} (via {parent doc})" and the report counts
them separately so a wrong assumption is visible rather than silent.

---

## 6. Approval

Finance approver opens a `PRINTED` request, uploads the signed scan, fills BOD document number, date
and signer, and approves.

- All four are required together. Approving with three of them is refused by `C03` at best and leaves
  an unattributable approval at worst.
- File: `pdf|jpg|jpeg|png`, ≤ 10 MB, stored on `minio_private`, SHA-256 recorded (`design.md` §7).
- Rejection requires a reason and is terminal — the way back is a new request, which keeps the number
  series honest about how many attempts a decision took.
- The approver may not be the requester. Enforced by permission sets in practice; assert it in
  `approve()` as well, comparing `SCAR_CR_UID`. A single person holding both roles is a configuration
  mistake that should fail loudly, not quietly self-approve.

---

## 7. Reports

All four read `oracle_mgthris`, paginate, and export through the queued-job + notification pattern.
Where a report shows a customer name or an item description it fetches those from `oracle_mgtdat`
through the SO lookup repository and composes them in the service — no cross-connection join
(`design.md` §2).

### 7.1 Active rules

`SALES_MIN_PRICE` where `SMP_STATUS = 'APPROVED'` and today falls in the validity window. Filter by
item, grade, UOM, scope level. Shows the approving `SCAR_REQ_NO` and a link to its attachment.

### 7.2 Exceptions granted

`SALES_CTL_APPR_REQUEST` + lines where `CTRL_TYPE = 'MINPRICE'` and `STATUS = 'APPROVED'`. Per line:
SO, customer, item, approved rate, approved qty, the floor at the time, the gap, requester, approver,
BOD document, attachment link, revision, print count.

This is the board-facing document. It is the answer to "what did we let through last quarter".

### 7.3 Rejections

`SALES_MIN_PRICE_CHECK_LOG` where `SMPCL_RESULT = 'FAIL'`, over a required date range. Who attempted, which
document, which item, rate, floor, gap.

**Deduplicate.** One row per `(SMPCL_SOH_SYS_ID, SMPCL_SOI_SYS_ID)`, latest `SMPCL_CR_DT` — an SO
approved on the fourth attempt otherwise appears as four rejections. Keep the attempt count as a
column; it is more interesting than the repetition.

### 7.4 Gross vs net monitoring

`SMPCL_RATE_USD` against `SMPCL_NET_RATE_USD` where `SMPCL_HAS_DISCOUNT = 'Y'`: lines that pass on gross
and would fail on net. Purely informational, and explicitly labelled as such on the page. Its purpose
is to give the eventual "should we validate on net" decision a number instead of an opinion
(PRD §3.4).

Same report also surfaces overlapping approved rules for the same rule key — the case `schema.md`
§4.1 accepts can lose a race to. Cheap to add here, and it is the only place anyone would see it.

---

## 8. Errors the user will actually meet

| Situation | Where | What they see |
|---|---|---|
| SO below floor, approving in Orion Forms | Oracle msg 1012110 | "Harga di bawah minimum price. Baris: …" — native Forms dialog |
| No FX rate for the document date | 1012111 / gateway | "No exchange rate for {date}. Contact Finance." |
| Currency not USD/IDR | 1012112 / gateway | "Currency {x} is not covered by minimum price control." |
| Divisor outside 1 000–100 000 | 1012111 with `divisor=` in the text | Same channel, and it means the FX direction assumption is wrong — `verification.md` §1 item 1 |
| Editing a printed request | Laravel | "This request is printed and locked. Revise it to make changes." |
| Approving without a scan | Laravel, and `C03` behind it | "Upload the signed document first." |

The Forms-side messages are Indra's registry rows. Laravel never raises them; it mirrors their wording
so a user reading the app and a user reading Forms are told the same thing.

---

## 9. Tests

Pest, in `tests/Feature/Finance/SalesControl/` (D11). SQLite, so the gateway is faked (`design.md`
§4.3) and no test asserts anything about Oracle's own resolution.

Minimum set:

| # | Test |
|---|---|
| 1 | A draft rule is not returned as active; approving its `PRICELIST` request makes it active |
| 2 | Overlapping periods for the same rule key are rejected, and the message names the conflicting rule |
| 3 | Different UOM for the same item and grades is **not** an overlap |
| 4 | Supersede sets the old rule's `VALID_TO` to `new_from − 1 day`, and only at approval |
| 5 | Import lands every row as `DRAFT`, and reports all invalid rows at once with row numbers |
| 6 | Import rejects a file that overlaps within itself |
| 7 | Printing moves `DRAFT → PRINTED`, increments the count, stamps date and user |
| 8 | A printed request refuses every content edit, from the service, not just the form |
| 9 | Reprinting increments the count and does not change status |
| 10 | Revising a printed request creates revision + 1 as `DRAFT` and voids the previous row |
| 11 | Approving without an attachment is refused |
| 12 | Approving without all three BOD fields is refused |
| 13 | The requester cannot approve their own request |
| 14 | Rejection requires a reason and is terminal |
| 15 | Request numbers are per type and per year: `MPL-2026-0001` and `MPO-2026-0001` coexist |
| 16 | A second insert on the same `(REQ_NO, REVISION)` fails and the generator retries to the next number |
| 17 | The preview writes no `SALES_MIN_PRICE_CHECK_LOG` row and no request row |
| 18 | The preview skips short-closed, FOC and zero-rate lines |
| 19 | A gateway FX failure surfaces as a message, and no line is reported as passing |
| 20 | The exception form offers only `UNDER` lines, and stores rate and qty from the SO, not from input |
| 21 | A line already covered by an approved exception is shown as covered and is not offerable again |
| 22 | The rejection report counts one rejection per line, not one per attempt |
| 23 | `SalesMinPriceCheckLog` throws on create, update and delete |
| 24 | Every route is refused without its permission, and reachable with it |

Test 19 earns its place: it is the one that fails if somebody "helpfully" makes the gateway return
null on error. Test 23 is the guard on D4. Test 17 is both.

Service- and repository-level tests for the state machine, the overlap check and the numbering, not
only component tests — those three are the integrity boundaries, and a component test would pass even
if the rule moved somewhere a crafted request could skip.
