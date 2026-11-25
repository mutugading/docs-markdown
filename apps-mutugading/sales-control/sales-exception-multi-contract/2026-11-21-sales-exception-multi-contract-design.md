# Price Exception: one request across several contracts of one customer

Date: 2026-11-21 · Module: Finance (Sales Control) · Status: approved 2026-11-21

## Why

Today one below-minimum price exception request covers one sales order (contract).
When a customer has many contracts below the floor, Marketing raises one request
per contract, and the paperwork multiplies: last week customer A needed 24
requests, so 24 entries, 24 signatures, 24 uploads and 24 approvals.

Goal: Marketing picks a customer, ticks lines from as many of that customer's
contracts as needed, and saves **one** request, which then goes through **one**
print, one signature, one upload and one approval.

## Goals

- On the Price Exception request page, a second way in: pick a customer and see
  every below-floor line across that customer's open contracts, grouped by
  contract, in one table.
- Tick lines across contracts, write one reason, save one draft request.
- The printed form, the email approval summary, the approval page and the Price
  Exception report/export all show which contract each line came from.
- Revise, cancel, print, sign, approve, reject and void work on a multi-contract
  request exactly as they do today.

## Non-goals

- No change to the single-contract way in (`?doc=LSC-...`, and the link from Price
  Check keep working as they do).
- No change to how approval unblocks orders. The Oracle check (`F_GET_OVERRIDE`)
  already matches approved lines per sales order line (`SCAL_SOI_SYS_ID`, exact
  rate, at-least quantity), so it does not care how many contracts share a request.
- No mixed customers on one request.
- No batch actions over several requests (that was the rejected approach C).

## Anti-goals (would be a failure even if it works)

- A typed price or quantity anywhere. Rate and quantity still come only from the
  preview of `OT_SO_ITEM` (D8); `PriceExceptionLineData::fromPreviewLine()` stays
  the only way to build a line.
- A request whose printed form doesn't let the signer see which contract each
  line belongs to.
- A line that is offered again while an approved exception already covers it
  (the existing "covered" rule must hold across contracts).

## Constraints

- Sales orders live on `oracle_mgtdat` (`OT_SO_HEAD`, `OT_SO_ITEM`); requests live
  on `oracle_mgthris` (`SALES_CTL_APPR_REQUEST`, `SALES_CTL_APPR_LINE`). There is
  no cross-connection join, so anything shown per line has to be stored on the line.
- A customer's contracts are in one currency in practice (confirmed by the user).
  Still enforced: a selection that spans currencies is refused with a clear message.
- The request number is allocated from the contract date (`scar_doc_dt`) by
  `ApprovalRequestNumberGenerator`.
- `ApprovalRequestService` remains the only writer of `scar_status`.
- Migrations must also run on SQLite (CI) and follow the `DISABLE_MIGRATIONS`
  pattern. Never edit a deployed migration.

## Approaches considered

- **A. Pick a customer on the request page (chosen).** The page lists all of the
  customer's open, in-scope contracts, checks each the same way a single contract
  is checked today, and shows the lines grouped by contract. Most screen work,
  but it matches how Marketing thinks ("everything for customer A") and makes it
  hard to miss a contract.
- **B. Basket of contract numbers.** Same data changes, but Marketing types each
  contract number and adds lines to a basket. Less screen work, but with 24
  contracts they have to know and type all 24 and nothing helps them spot one
  they forgot.
- **C. Keep one request per contract and batch the paperwork** (combined PDF, one
  upload, bulk approve). No data change, but every batch step has to handle partial
  rejects and revisions, and the approval list still shows 24 rows.

The user picked A.

## Design

### Data

New nullable columns on `SALES_CTL_APPR_LINE` (new migration):

| Column | Type | Meaning |
|---|---|---|
| `scal_soh_sys_id` | integer | the contract (`OT_SO_HEAD.SOH_SYS_ID`) the line came from |
| `scal_txn_code` | varchar(10) | contract transaction code, for display (`LSC`) |
| `scal_doc_no` | integer | contract number, for display |
| `scal_doc_dt` | date | contract date, for display |

The same migration backfills existing lines from their parent request's
`scar_soh_sys_id / scar_txn_code / scar_doc_no / scar_doc_dt`, so old and new
requests read the same way. Add an index on `scal_soh_sys_id`.

`PriceExceptionLineData` gains the four values. They come from the preview's
header (the contract), never from input. `toRow()` writes them;
`ApprovalRequestService::revise()` copies them.

Request header on a multi-contract request:

- `scar_cust_code`, `scar_curr_code`: as today (one customer, one currency).
- `scar_soh_sys_id`, `scar_txn_code`, `scar_doc_no`: filled when every ticked line
  comes from one contract (identical to today), **null** when lines span contracts.
- `scar_doc_dt`: the **earliest** contract date among the ticked lines. It drives
  the request number and reads as "the oldest contract on this request".

No status, permission, route or menu changes: it is the same page and the same
`finance-sales_control_price_exception-create` permission.

### Request page (`PriceExceptionRequest`)

A small switch at the top of the "Sales Order" card: **By contract** (today's
box, the default, still filled by `?doc=`) or **By customer**.

By customer:

1. A searchable customer picker listing customers that have at least one open
   in-scope contract. In-scope = `inScopeTxnCodes()`. Open = **not yet approved**,
   `NVL(SOH_APPR_STATUS, 0) <> 3`, and nothing else: no date cutoff and no
   `soh_clo_status` filter (decided by the user). The by-contract box keeps
   accepting any contract, as today. Shown as `code - name`.
2. On pick, the page loads that customer's open in-scope contracts (oldest
   first) and runs the existing per-contract preview (`PricePreviewService::preview()`)
   for each.
3. One table, grouped by contract: a contract header row (`LSC-2026003755 · 12 Nov
   2026 · 3 lines below floor`) followed by its lines, reusing the existing
   line columns and "covered" label. Contracts with nothing below the floor are
   left out of the table; a line under it says how many were checked and found
   fine ("9 contracts checked, 3 have lines below the floor").
4. A contract that could not be checked (rate missing, out of scope) shows its
   notice in its group header, with its lines not tickable, the same rule as today.
5. "Tick all below floor" ticks every offerable line across all contracts.
6. A line already on a request that is still in progress (DRAFT, PRINTED or
   SIGNED) shows "on request PE-0012 (printed)" and cannot be ticked, in both the
   by-customer and the by-contract views, and the service refuses it on save.
   Cancelling or revising that request (the old revision goes VOID) frees the
   line. Today only APPROVED cover hides a line; this widens that rule so the
   customer-wide "tick all" doesn't create duplicate requests. (My call; the user
   can overrule.)
7. Reason + **Save Draft Request**, as today.

Approval stays all-or-nothing (my call). If the board disputes one contract,
Finance revises the request to drop those lines, as today.

Rough look:

```
Sales Order   ( ) By contract   (•) By customer
Customer [ C0123 - PT Customer A          v ]
9 open contracts checked, 3 have lines below the floor.     [Tick all below floor]

┌ LSC-2026003755 · 12 Nov 2026 · USD ─────────────────────────────────────┐
│ [x] ITEM-A  30S  ...  rate 2.10  floor 2.25  BELOW                       │
│ [ ] ITEM-B  40S  ...  rate 2.40  floor 2.50  BELOW                       │
├ LSC-2026003790 · 15 Nov 2026 · USD ─────────────────────────────────────┤
│ [x] ITEM-C  ...                                    BELOW                 │
│ --- ITEM-D  ...                                    COVERED (PE-0012)     │
└──────────────────────────────────────────────────────────────────────────┘
Reason (2 line(s) ticked) [..............................]  [Save Draft Request]
```

Ticked lines are keyed by SOI sys id as today; SOI ids are unique across contracts.

### Service

`PriceExceptionService::request()` today takes one `PricePreviewData`. It gains a
sibling path that takes a list of previews (one per contract) and the ticked SOI
ids, and:

- refuses an empty selection, a short reason, and any ticked line that is not
  offerable in its own contract's preview (same messages as today);
- refuses lines from more than one customer, or more than one currency;
- builds lines with `fromPreviewLine()` plus the contract values from that
  preview's header;
- fills the header as described above, and hands off to `createDraft()`.

The single-contract `request()` keeps working, and is best rewritten to call the
new path with a one-item list so there is one code path.

The previews are re-run on save (as today's computed `preview` is), so what is
saved reflects the sales orders at the moment of saving, not when the page loaded.
My call (to limit Oracle round trips): on save, re-run the preview **only for
the contracts that have a ticked line**, not for every contract of the customer.
The full set is checked once when the customer is picked, behind a loading state.

### Everything that shows the contract

- **Printed form** (`pdf/sales-control/price-exception-form`): when the header has
  a contract, unchanged. When it is null, the header shows "Contracts: n" and the
  lines table gains a **Contract** column (or groups lines under contract
  subheadings, builder's call), so the signer sees every contract.
- **Email approval summary** (`MinPriceEmailApprovalHandler`, `email-approval/summary`,
  `email-approval-record`): "Document" shows the single contract as today, or
  "n contracts: LSC-..., LSC-..." (first few, then "+k more").
- **Request list and approval page** (`price-exception-request`, `price-exception-approval`
  and `_exception-lines`): the "Sales Order" column shows the contract, or
  "n contracts"; the line detail shows each line's contract.
- **Report and export** (`PriceExceptionReport`, `PriceExceptionExport`): a per-line
  Contract column, read from the line, not the header.
- **List search** ("Request no, SO no, customer..."): searching a contract number
  must also find multi-contract requests that include it, by matching the line's
  `scal_doc_no`.

## Left to the builder

- Exact component for the customer picker (UI module `combobox` first, Flux
  second, per the component priority).
- Whether the multi-contract form shows a Contract column or contract subheadings.
- How many contracts to show before abbreviating in the email summary.
- Safety ceiling on contracts loaded per customer. My call: cap at 100 open
  contracts with a notice if hit, marked `ponytail:` in code. A customer with
  more than 100 unapproved contracts is not expected.
- Query shape for the customer list and open-contract list (new methods on
  `SalesOrderLookupRepositoryInterface`).

## Tests (Pest, SQLite)

- Saving lines from two contracts of one customer creates one request with the
  lines carrying their own contract, header contract fields null, `scar_doc_dt` =
  earliest contract date.
- Saving lines from one contract through the customer path gives the same header
  as today's single-contract path.
- Mixed currency or mixed customer is refused.
- A covered line in any contract cannot be ticked/saved.
- A line on a DRAFT/PRINTED/SIGNED request is refused; after that request is
  cancelled it is offered again.
- The customer and contract lists leave out approved contracts (query shape
  asserted from the built SQL, as the existing `checkableLinesQuery` test does).
- Revise copies the per-line contract columns.
- The backfill migration fills the new columns of existing lines from their header.
- The existing single-contract tests keep passing unchanged.
