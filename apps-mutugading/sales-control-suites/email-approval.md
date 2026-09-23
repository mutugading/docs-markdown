# email-approval.md — approving by email

An addendum to `spec.md` §6, `schema.md` §2 and `design.md` §7. Everything here is **additive**: the
print-sign-scan flow is untouched and still works exactly as it did.

---

## 1. Why

The board member who signs a price exception is often not in the building, and the request is holding
up a sales order. The paper route needs a printer, a pen, a scanner and two hand-offs. Email needs a
phone.

So a request now has **two valid approval channels**, and a request is approved when either one of
them completes:

| Channel | How | What ends up on file |
|---|---|---|
| `PAPER` | Print → sign → Finance uploads the scan and types the BOD fields | the scan |
| `EMAIL` | Finance sends the request to a configured approver → the approver opens a signed link and decides | a generated decision record (PDF) |

Uploading the physical scan **after** an email approval is allowed and optional. It replaces what
`SCAR_ATTACH_PATH` points at; the email decision record keeps its own path and hash in
`SALES_CTL_APPR_EML`, so nothing is lost.

---

## 2. What does not change

These are the guards the whole control exists for. Email approval is built to fit inside them, not
around them.

- **`C03` stands.** Oracle still refuses an `APPROVED` row with a null `SCAR_ATTACH_PATH`. An email
  approval satisfies it by generating a decision-record PDF, storing it on `minio_private` and
  hashing it exactly like a scan. The hash is still verifiable through `ApprovalDocumentService::verify()`.
- **The four-things rule stands.** `SCAR_BOD_DOC_NO`, `SCAR_BOD_DOC_DT` and `SCAR_BOD_SIGNER` are still
  required before `APPROVED`. For an email decision the service fills them from the decision itself:

  | Column | Value on an email approval |
  |---|---|
  | `SCAR_BOD_DOC_NO` | `EML-{SCAR_REQ_NO}-r{revision}` — the decision's own reference |
  | `SCAR_BOD_DOC_DT` | the moment the approver clicked |
  | `SCAR_BOD_SIGNER` | the approver's name from the approver master |

  So an approved row is still attributable to a named person on a dated document, which is the only
  thing those three columns were ever for.
- **`PRINTED → APPROVED` only.** The state machine is unchanged and `ApprovalRequestService` is still
  its only writer. An email invitation can only be sent for a `PRINTED` request, for the same reason
  a paper form can only be signed after it is printed: printing is what freezes the content.
- **The requester may not approve.** Checked against the approver master row's NIK where it has one,
  and against the requester's email address where it does not.

---

## 3. The approver master

`MGTHRIS.SALES_CTL_APPROVER` (`SCAP_`). A small, explicitly maintained list — not "whoever holds the
permission", because the people who sign these are board members who mostly do not use this app, and
routing a price approval should not depend on a permission grant nobody looks at.

One row is one person who may decide one control type. A person who signs both `MINPRICE` and
`PRICELIST` gets two rows; the two lists genuinely differ and merging them costs a column to split
them again.

`SCAP_NIK` is optional and is the link to `HM_EMP_DATA` when the approver is an employee. It is what
makes the requester-is-not-approver check exact; without it the check falls back to comparing email
addresses, which is weaker but better than nothing.

---

## 4. The invitation

`MGTHRIS.SALES_CTL_APPR_EML` (`SCAE_`). One row per invitation sent, never updated in place except by
its own lifecycle.

```
SENT --open--> OPENED --decide--> APPROVED | REJECTED
  |               |
  |               `--(ttl passes)--> EXPIRED
  `--(superseded / withdrawn)--> CANCELLED
```

- The link is a **signed URL** (Laravel's `signed` middleware, so the query string cannot be edited)
  carrying an **opaque 64-character token**. Only the token's SHA-256 is stored; the raw token exists
  in the email and nowhere else. Losing the database does not hand anyone an approval link.
- **Single use.** Deciding moves the row to a terminal status; a second visit to the same link gets
  the "already decided" page, not a second form.
- **Expiring.** `SCAE_EXPIRES_DT`, default 72 hours, configurable. An expired link cannot be opened;
  Finance sends a fresh one.
- **Superseding.** Sending a new invitation for the same request cancels the outstanding ones. Two
  live links for one request means two people can approve it, and the second one loses silently.
- The open, the decision, the IP and the user agent are recorded. That is the email channel's
  equivalent of a wet signature: it cannot prove who held the phone, and neither can a signature, but
  it says what arrived, where from and when.

### 4.1 Why a controller and not Livewire

The decision page is a guest route behind `signed`. Livewire's update endpoint is a different URL and
is not itself signed, so a Livewire component on a signed route either breaks on its first action or
needs the signature check weakened. A plain controller with a form POST keeps the signature meaningful
and the page is two buttons and a textarea.

---

## 5. Mail

This is the **first outbound email in the application**. Before it is any use in production:

- `MAIL_MAILER` is `log` in the current `.env`. It must point at the company SMTP relay.
- `MAIL_FROM_ADDRESS` is already `noreply-apps@mutugading.com`.
- The mailable is queued (`high`), so the queue worker must be running — it already is, for exports.
- Approvers' addresses come from the approver master, not from `HM_EMP_DATA`, so a board member with
  no employee email still gets the mail.

A send failure leaves the invitation row in `SENT` with nothing delivered. That is visible on the
approval page as an invitation with no open and no decision, and the fix is to send again.

---

## 6. Permissions

| Permission | Who | What it allows |
|---|---|---|
| `finance-price_exception-approve` | Finance | existing — approve on paper, and now also send an invitation |
| `finance-sales_control-approver-manage` | Finance / Super Admin | maintain the approver master |

The approver deciding by email holds **no permission at all** — they are a guest with a token. That is
the point of the channel, and it is why the token is short-lived, single-use and hashed at rest.

---

## 7. Tests

| # | Statement |
|---|---|
| E1 | An invitation can only be issued for a `PRINTED` request |
| E2 | Issuing a second invitation cancels the first |
| E3 | A valid token renders the decision page and stamps `SCAE_OPENED_DT` |
| E4 | An expired token renders the unavailable page and approves nothing |
| E5 | A decided token cannot decide twice |
| E6 | Approving by email moves the request to `APPROVED`, fills the three BOD fields and writes a hashed decision record |
| E7 | Rejecting by email requires a reason and moves the request to `REJECTED` |
| E8 | An approver whose NIK equals `SCAR_CR_UID` is refused |
| E9 | Uploading a scan after an email approval leaves the email record's own path and hash intact |
