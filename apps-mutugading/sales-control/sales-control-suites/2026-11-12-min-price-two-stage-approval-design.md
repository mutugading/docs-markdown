# Min price list: separate signature and approval

Date: 2026-11-12. Branch: `feat/min-price-and-provision`.

## Why

Today one person does the whole price list (PRICELIST) approval in one step: they upload the signed
scan, enter the board document fields and approve. The email route does the same in one click:
the system generates a decision PDF in place of the scan and approves.

In practice three people take part, and the boss who signs the form is not the person who
approves the price list. The system has to keep **getting the signature** separate from
**approving the request**.

## Who does what

| User | Does |
|---|---|
| **A** | Enters the min price rules and submits them as one price list request. Unchanged. |
| **B** | Gets the price list signed by their boss, on paper or by email. Then picks C and sends the approval email. Rejects the request if the boss refuses on paper. |
| **Boss** | Signs, or refuses, on paper or from a signature email. Has no app account role. |
| **C** | Approves or rejects, from the email **or** from the app. |

## The flow

```
DRAFT ──print──▶ PRINTED ──signature──▶ SIGNED ──C approves──▶ APPROVED
                    │                     │
                    │ B rejects /          └──C rejects──▶ REJECTED
                    │ boss refuses by email
                    ▼
                 REJECTED
```

1. **A** submits. Request is DRAFT and its rules are drafts. Unchanged.
2. **B** gets the signature in one of two ways:
   - **Paper:** print (DRAFT → PRINTED), the boss signs, B uploads the scan and fills in the board
     document number, date and signer. Saving this moves the request to **SIGNED**.
   - **Email:** B sends a **signature link** to the boss. Sending it also counts as printing
     (DRAFT → PRINTED, the content is frozen), like today's rule that only a frozen request goes
     out by email. The boss opens the link and chooses:
     - **Sign:** the system generates the signed PDF (today's decision record, worded as a
       signature, not an approval), stores it as the request's attachment and fills the board
       fields: doc no `EML-{req_no}-r{revision}`, the time, the boss's name. Request → **SIGNED**.
     - **Refuse** (reason required): the request is **rejected straight away**. Its rules stay drafts.
3. While PRINTED, B can still reject (the boss refused on paper), revise, or reprint, as today.
4. **B** picks C from the approver list and presses send. Only a SIGNED request can be sent.
5. **C** approves or rejects, by email or in the app. Approving moves the request to APPROVED and
   makes the rules live in the same transaction, as today. B's attachment and board fields are
   **kept**, never overwritten.
6. **Once SIGNED, the only ways out are C's approve or reject.** No revise, no cancel and no
   B-reject on a SIGNED request. Void after APPROVED is unchanged.

## Decisions made

- New status **SIGNED** (`ApprovalRequestStatusEnum::Signed`), between PRINTED and APPROVED.
  - Transitions: PRINTED → SIGNED; SIGNED → APPROVED | REJECTED. PRINTED → APPROVED is no longer
    allowed **for PRICELIST**.
  - `nextActionLabel()`: PRINTED → "Get the signature", SIGNED → "Send for approval".
  - `timelineSteps()` for PRICELIST: Draft, Printed, Signed, Approved.
- **New email link type for signatures only**, a separate Core handler key
  (e.g. `finance.min-price-signature`). Its buttons are Sign and Refuse. It can never approve.
  The existing `finance.min-price` handler stays the approval link.
- **The approval email no longer generates a PDF or fills board fields for PRICELIST.** It only
  approves, because the SIGNED request already has both. `approve()` still runs its checks for an
  attachment and the board fields; they pass because the signature stage filled them.
- **Who may be the same person** (user's answer): A (draft or submit) and B (upload the PDF or
  send the signature email) **may be the same person**. *My reading, to confirm:* C must still be
  someone other than A and B. The existing submitter check therefore stays on approve/send, and a
  new check also stops the person who got the signature (whoever uploaded the scan or sent the
  signature link) from being C. The DB constraint C03 (APPROVED needs an attachment) stays.
- **The boss comes from the approver list, as a new signer type.** *My call:* add a column
  `SCAP_ROLE` (`APPROVE` default, `SIGN`) (user approved) to `SALES_CTL_APPROVER`, by a new migration, and show it
  on the approver master screen. The signature send lists only `SIGN` rows of type PRICELIST; the
  approval send lists only `APPROVE` rows. The signature handler also refuses any link whose
  recipient row is not `SIGN`.
- **Any C may approve, no lock** (user's answer): anyone with `finance-min_price-approve` in the
  app, or any open approval link, can decide a SIGNED request, even if B sent the email to someone
  else. The only limit is the A/B-is-not-C check above. Deciding by either route cancels the other
  open approval links (the existing `cancelFor`).
- **In-flight price lists: nothing special** (user's answer). No cutoff and no data migration.
  PRINTED → APPROVED is simply gone for PRICELIST.
- **MINPRICE exception flow is unchanged**: print → upload and approve in one step, or one-click
  email approval with a generated PDF, and the self-approval block stays for it. All the changes
  apply only to `scar_ctrl_type = PRICELIST`.
- **Database:** a new migration widens the status CHECK constraint to include `'SIGNED'`. Per the
  CLAUDE.md rule, do not edit `2026_09_04_100000_create_sales_ctl_appr_request_table.php`.
  SQLite (CI) needs no constraint change if it has none; check. The same migration adds
  `SCAP_ROLE`, and a column recording who got the signature (the uploader's NIK, or the NIK of
  whoever sent the signature link), so the A/B-is-not-C check has something to compare against.
- Other readers of PRINTED to update for SIGNED: `SalesControlDashboard` (pending counts and
  notes), `SalesMinPrice` line 131 (the "waiting" state of a rule), `MinPriceEmailApprovalHandler::stillOpen`
  (PRICELIST approval links are now open on SIGNED), and `SalesEmailApprovalService::assertSendable`.

## Non-goals

- No automatic send to C; B always picks and sends.
- No multi-level boss chain; one signature.
- No change to the void, revise-while-PRINTED or MINPRICE screens.

## Anti-goals (a failure even if it works)

- A signature link that can approve anything.
- C's approval overwriting the boss's signed PDF or board fields.
- Rules going live without an approval, or an approval without its rules going live.
- A SIGNED request being edited, revised or cancelled.

## Left to the builder

- How the Livewire screens (`MinPriceApprovalManager`) split "Upload signed scan + board fields"
  from "Approve" into separate actions and buttons.
- Signature PDF wording and template, reusing `ApprovalDocumentService::storeDecisionRecord`.
- Notifications to B when the boss signs or refuses, and when C decides (reuse
  `SalesEmailApprovalService::notifyRequester`).
- Tests: the transition table, both signature routes, email refusal rejecting, approval keeping
  B's document, self-approval allowed for PRICELIST but blocked for MINPRICE.
