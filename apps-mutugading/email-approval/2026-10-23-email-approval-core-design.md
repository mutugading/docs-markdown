# Email approval as a Core service — design

Date: 2026-10-23
Status: draft, waiting for approval

---

## 1. Why

MinPrice (Finance → Sales Control) already lets an approver decide a request from a link in an email,
without logging in. It works well, but all of it lives inside Finance and knows only about price
requests (`SalesEmailApprovalService`, `SALES_CTL_APPR_EML`, `EmailApprovalController`, see
`.docs-me/sales-control-suites/email-approval.md`).

We want the same thing for MIS transactions — leave first, then others, and later the Asmen+ hierarchy
flow — without copying it per module. So the link machinery moves into **Core**, and each transaction
plugs in only what is specific to it.

Success: a module can make a transaction approvable by email by writing one handler class and calling
one Core method when it wants links sent. MinPrice runs on it, and nothing in Core is specific to it.

## 2. Goals, non-goals, anti-goals

**Goals**
- One Core service owns tokens, expiry, single use, cancelling old links, the audit trail (open,
  decision, IP, browser), the guest decision page and the email.
- A **handler** per transaction type decides everything business-specific: is this step still waiting
  for this person, what the page shows, what the buttons say and do, what the email says.
- Works for **any step** of a workflow (submit, approve, release, …), chosen by the handler/caller.
- Recipients may be **non-employees** (MinPrice board members).
- **MinPrice moves onto Core**, fresh start. It is the only handler shipped in this work.

**Non-goals (later tasks)**
- **Leave** and every other MIS transaction. §6 records what is known so far, but who receives the
  link, the email text and the buttons are decided later, in a separate piece of work.
- Asmen+ / SelfService hierarchy flow (`NotifyHierarchyActorsListener`). Core must not block it, but
  it is not wired now.
- Any admin page for browsing links across modules.

**Anti-goals — fail even if it "works"**
- A link that approves when merely **opened**. Mail scanners (Outlook Safe Links etc.) open every
  link; opening must never change a transaction.
- A link that can do **more than its owner could do in the app**. The handler runs the module's normal
  service, with its normal authorization, as the link owner.
- A link usable for a **different step** than the one it was sent for.
- A raw token stored anywhere but the email.

## 3. How it behaves

### 3.1 The flow

```
module calls Core: issue(handler, subject, step, recipients)
  -> Core cancels this subject's live links, creates one link per recipient,
     queues one email each (single button, e.g. "Review request")
recipient opens the link  (GET, signed URL, no login)
  -> Core checks: link valid, not expired, not used, not cancelled
  -> Core asks handler: is this step still waiting for this person?
  -> records first open (time, IP, browser); nothing else changes
  -> shows the decision page: handler's summary + handler's buttons
recipient presses a button
  -> "link" button: plain navigation to the handler's URL (e.g. a login page); token not used
  -> "action" button: POST to Core -> re-check everything -> handler performs it as the link owner
     -> link closed with that decision; sibling links for the same subject+step cancelled
     -> "done" page
```

### 3.2 Link lifecycle

```
SENT --open--> OPENED --action button--> DECIDED
  |               |
  |               `--(ttl passes)--> EXPIRED
  `--(new issue for same subject / sibling decided / step moved on)--> CANCELLED
```

- **Token:** opaque 64-char random string, carried in a Laravel **signed** URL. Only its SHA-256 is
  stored (same as MinPrice today).
- **Single use:** only an *action* button spends it. A *link* button does not, so someone who opens
  the reject page and changes their mind can still come back and press YES.
- **Expiry:** each handler may set its own; otherwise Core's default (72 hours, configurable) applies.
- **One live link per recipient per subject:** issuing a link cancels that *same recipient's* earlier
  live links for the subject (any step). Other recipients' links are left alone — MinPrice sends to one
  board member at a time and keeps the earlier ones live, as it does today. Recipient identity is the
  email address (plus the handler-side reference when set).
- **First action wins:** when any action button is pressed, every other live link for that subject is
  cancelled; the others get "already handled".
- **Step bound:** the step is stored on the link and passed to the handler; the handler must refuse
  if the transaction is no longer at that step. That is also how a link dies when the transaction
  moved on through the normal app (it is checked on open and on decide; no listener needed to
  cancel it, though modules may call `cancelFor()` to be tidy).

### 3.3 The decision page

- A plain controller with a form POST, **not Livewire** — Livewire's update endpoint is a different,
  unsigned URL (same reason as MinPrice, email-approval.md §4.1).
- GET is behind `signed`. POST carries the token in the body, behind CSRF; single use stops replay.
- Layout is a Core guest layout. The middle is the handler's summary (a Blade view + data).
- Buttons come from the handler. Each button has:
  - `key` and `label` (e.g. "Approve", "Release", "Reject")
  - `kind`: `action` (done here, no login) or `link` (navigate to a URL the handler gives)
  - `note`: `none`, `optional` or `required` — shows a textarea for that button (MinPrice: reject
    requires a reason, approve takes an optional note)
  - a style hint (primary / danger) for the button colour
- Errors from the handler (`ValidationException`) come back to the page as a message; anything else
  is reported and shown as "nothing was changed, try again". A handler whose service refuses with its
  own exception (leave's `BulkActionFailedException`) converts it to a `ValidationException` carrying
  the real reason, so the approver sees why.
- Unavailable states (invalid, expired, used, cancelled, step moved on, feature off) show one page
  with the reason and a contact line.

### 3.4 The email

- One Core mailable, queued on `high`. One button, whose label the handler gives.
- Subject and body text come from the handler. Recipient is name + email address stored on the link.
- A send failure leaves the link `SENT` with nothing delivered — same as MinPrice today.

## 4. Pieces in Core

Names are suggestions; the builder may adjust to the module's conventions.

| Piece | Purpose |
|---|---|
| Table `CM_APPR_TOKEN` (`oracle_mgthris`) + model `CmApprToken`, column prefix `cmat_` | one row per link |
| `EmailApprovalHandler` interface | the contract a module implements |
| Handler registry | maps a handler key (e.g. `finance.min-price`, `mis.leave`) to a class; modules register from their own service provider |
| `SalesEmailApprovalService` | `issue`, `cancelFor`, `resolve`, `open`, `decide`, `historyFor` — the only writer of the link status |
| Repository + interface | data access, per project pattern |
| `EmailApprovalController` + guest routes + views | review page, decide POST, done page, unavailable page |
| Mailable + view | the single-button email |
| Config | master switch, default TTL, per-handler enable + TTL, contact line |

### 4.1 The table (columns, roughly)

- id; **handler key**; **subject id** (string — MinPrice uses an integer id, leave a string sys id);
  **step** (string)
- recipient: name, email, **NIK nullable** (employee link), plus an optional handler-side reference
  (MinPrice's approver master id)
- token hash (unique), status
- sent at / sent by, expires at, opened at, decided at
- decision (the action button key pressed), note, IP, user agent
- standard audit columns

No columns for a handler's own output. A document a handler makes from a decision (MinPrice's
decision-record PDF) is that module's to store, keyed on the Core link id — see §5.

Must be SQLite-compatible (CI) and follow the `DISABLE_MIGRATIONS` pattern.

### 4.2 The handler contract (what a module answers)

For a given link:
1. **Still open?** — is the subject still at this step, and may this recipient act on it. Return a
   reason when not.
2. **Summary** — view name + data for the middle of the page.
3. **Buttons** — the list described in §3.3.
4. **Perform** — do the action button's work as the link owner, returning nothing. Runs inside Core's
   decide call, which re-checks (1) first; anything the handler saves alongside happens in the same
   transaction.
5. **Mail content** — subject, body text, button label.
6. **Link lifetime** — optional. The handler may return its own expiry (hours); when it returns null,
   Core's default (72 h, from config) applies.

The handler, not Core, decides who the "actor" is. Core never logs anyone in.

## 5. MinPrice onto Core — fresh start

- **Delete from Finance:** `EmailApprovalController`, its three guest routes, the `email-approval`
  views (show/done/unavailable), `SalesCtlApprEmail`, `EmailApprovalStatusEnum`, the email approval
  repository + interface + binding, `PriceApprovalRequested` mailable.
- **New migration** drops `SALES_CTL_APPR_EML` (never edit the deployed create migration). Old links
  already in inboxes stop working; no data is copied. Its `down()` recreates the table.
- **Finance keeps:** the approver master (`SALES_CTL_APPROVER`) and its page, the PRINTED-only rule,
  requester-may-not-approve, filling the three BOD fields, the decision-record PDF and its hash
  (`ApprovalDocumentService` now reads from the Core link instead of `SalesCtlApprEmail`), notifying the
  requester, activating price-list rules on approve.
- **The PDF's per-link copy stays in Finance.** Today `SCAE_DOC_PATH` / `SCAE_DOC_HASH` keep the path
  and hash per email decision, so the trail survives a later paper scan overwriting
  `SCAR_ATTACH_PATH` / `SCAR_ATTACH_HASH`. They move to a small Finance table (e.g.
  `SALES_CTL_APPR_DOC`: Core link id, path, hash, audit columns), written by the handler's perform.
- These move into a Finance **handler** (`finance.min-price`): approve = action, optional note; reject =
  action, required reason. Summary = the request, its lines and min-price rules (today's show page body).
- Finance's own `SalesEmailApprovalService` shrinks to the Finance-side rules and calls Core's `issue`.
  `PriceExceptionApproval` and `MinPriceApprovalManager` keep their send button and history list,
  reading Core links for `finance.min-price` + request id.
- Finance's `sales_control.email_approval` config moves to Core's per-handler config (enable switch,
  TTL, contact line), **still reading the existing env names** (`SALES_CONTROL_EMAIL_APPROVAL`,
  `SALES_CONTROL_APPROVAL_LINK_TTL_HOURS`) so production does not quietly switch it off on deploy.
- `email-approval.md` is updated to point at Core; its tests E1–E9 are kept, rewritten against Core.

## 6. Leave — notes for the later task (not built now)

Not part of this work. Kept so Core is shaped to fit it. Open: who receives the link — everyone the
listener finds can be a whole HR team at the Release step.

- Handler `mis.leave`, steps `submit` / `approve` / `release` (the listener's existing verb map).
- `NotifyNextApproversListener`, after it picks `$approversToNotify` and the verb, calls Core `issue`
  for those approvers who have an email address (`HmEmpData::routeNotificationForMail` logic — work
  email unless set to personal). The in-app `ApprovalRequired` notification is unchanged.
  Asmen+ rows keep returning early, as now.
- **Still open?** — transaction still at the status matching the step, and the recipient (by NIK) is
  still among the approvers the module would allow (same rules as the approval page: org scope,
  permission, self-approval whitelist). A guest request has no navbar site picker, so the check runs
  on the recipient's full reach — the same set the listener used to choose them.
- **Perform (YES)** — load the recipient's `HmEmpData` and run the existing leave approval path
  (`LeaveApprovalService::processBulkAction` with one id and the step's action), so the normal checks,
  status event and next-step notification all happen as if they clicked in the app.
- **NO** — for now a `link` button to the leave approval page, where they log in and write a reason.
- Email text: simple placeholder ("Leave request {no} for {employee} is waiting for your
  {step}"). Final wording later.
- Behind its own enable switch, off by default, so it only goes live once mail is configured.

## 7. Constraints

- Project rules apply: repository pattern, services injected via interfaces, no `DB::` facade in new
  code where the repository can do it, Oracle + SQLite compatible migrations, no `wire:navigate`.
- `MAIL_MAILER` must point at the SMTP relay before any of this is useful in production (already true
  for MinPrice's rollout).
- Guest routes are the only unauthenticated write path; nothing but the token, the button key and the
  note is read from the request.

## 8. Tests

- **Core** (with a fake handler): issue cancels earlier live links; open records once and changes
  nothing; expired / used / cancelled / feature-off show unavailable; handler "not open" shows
  unavailable; action spends the token and cancels siblings; link button does not spend it; required
  note enforced; step mismatch refused; tampered signature refused; only the hash is stored.
- **MinPrice:** E1–E9 from email-approval.md, against Core.
- **Handler expiry:** a handler's own lifetime is used; null falls back to Core's default.
- **Employee recipient:** a link with a NIK and no handler-side reference works end to end with the
  fake handler — proves Core is ready for MIS without shipping it.

## 9. Left to the builder

- Exact class, table, column and route names (route prefix suggestion: `/email-approval/...`).
- Whether the handler registry is a config array or a container-tagged binding.
- Page styling, within the existing guest/Flux look.

## 10. Calls I made (check these)

- Opening never decides; only a POSTed action button does.
- A `link` button never spends the token.
- Issuing cancels only the same recipient's live links for the subject; any action cancels all the rest.
- Handlers turn their own refusal exceptions into `ValidationException` so the real reason shows.
- MinPrice keeps its current env variable names.
- Core does not log anyone in; the handler chooses the actor.
- No record path/hash on the Core table; MinPrice keeps its PDF's per-link copy in its own Finance table.
- Old `SALES_CTL_APPR_EML` dropped by a new migration; no data copied.
- Handler expiry is in hours; null means Core's default.
