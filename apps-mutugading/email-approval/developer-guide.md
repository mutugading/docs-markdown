# Email Approval — Developer Guide

How to let someone approve or reject a transaction from a link in an email, without logging in.

Core owns the link: the token, the expiry, the guest pages, the email and the audit trail. Your module
owns what the link is about: what the reviewer sees, which buttons they get, and what pressing one does.
The first user is Finance's minimum price control, handler `finance.min-price`
(`Modules\Finance\Services\Transaction\SalesControl\MinPriceEmailApprovalHandler`).

## 1. The parts

| Part | Where |
|---|---|
| Table | `MGTHRIS.CM_APPR_TOKEN` (`CMAT_`), model `Modules\Core\Models\MgtHris\EmailApproval\CmApprToken` |
| Migration | `Modules/Core/database/migrations/2026_10_24_100000_create_cm_appr_token_table.php` (key `cm_appr_token`) |
| Status enum | `Modules\Core\Enums\EmailApproval\EmailApprovalStatusEnum` |
| Service | `Modules\Core\Services\EmailApproval\EmailApprovalService` |
| Handler contract | `Modules\Core\Interfaces\EmailApproval\EmailApprovalHandler` |
| Handler list | `Modules\Core\Services\EmailApproval\EmailApprovalHandlers` (singleton; modules call `register()`) |
| Repository | `EmailApprovalRepositoryInterface` → `EloquentEmailApprovalRepository` |
| DTOs | `EmailApprovalRecipient`, `EmailApprovalButton`, `EmailApprovalMail` in `Modules\Core\Data\EmailApproval` |
| Guest pages | `EmailApprovalController`; views in `Modules/Core/resources/views/email-approval/` |
| Mail | `Modules\Core\Mail\EmailApprovalRequested`, view `core::mail.email-approval` |
| Settings | `Modules/Core/config/email_approval.php` → `config('core.email_approval.*')` |

## 2. The life of a link

One row per link sent. `EmailApprovalService` is the **only** code that writes `CMAT_STATUS`.

```
SENT --open--> OPENED --decide--> DECIDED (CMAT_DECISION = the button key)
  |               |
  |               `--(ttl passes)--> EXPIRED
  `--(sent again / withdrawn / someone else decided)--> CANCELLED
```

- **Token.** 32 random bytes as hex. Only its SHA-256 (`CMAT_TOKEN_HASH`) is stored; the raw token is
  in the email and nowhere else. A copy of the table is not a set of working links.
- **Signed URL.** The link is a `temporarySignedRoute`, so the query string cannot be edited. Its
  signature lasts 6 hours past `CMAT_EXPIRES_DT`, so a late click gets the friendly "expired" page
  rather than Laravel's bare 403.
- **Single use.** Pressing an action button moves the row to `DECIDED`. A second visit gets "already
  decided".
- **Expiry.** `CMAT_EXPIRES_DT` = sent time + the handler's `lifetimeHours()`, else
  `default_ttl_hours` (72). A row is only marked `EXPIRED` when someone touches it, so to check
  whether a link still works use `$link->isUsable()`, not the status.
- **One live link per recipient.** Sending to the same person again cancels their old link. "Same
  person" is the same email (stored lower-cased) and, when the recipient has a `ref`, the same `ref`. Different people may hold live links at once; the first to
  decide wins and the others are cancelled in the same transaction.
- **No double decisions.** `decide()` locks every link of the subject (ordered by sys id) before it
  checks anything. A second click — on the same link or another recipient's — waits, then finds its
  link `DECIDED` or `CANCELLED` and is refused. `perform()` never runs twice.
- **Audit.** Sent time and sender, first open, decision time, note, IP and user agent are on the row,
  so the row itself is the audit trail. Only the send lands in the activity log (`Email Approval`):
  status changes are mass updates and skip model events.

## 3. What a decision does

1. The guest opens the link → `GET email-approval.show` (signed). `open()` checks the link still works
   and records the first open. The page shows the handler's `summary()` and `buttons()`.
2. The guest presses a button → `POST email-approval.decide` (throttled 30/min). The token travels in
   the form, not the URL.
3. `decide()` checks the button is a real `action` button and the note rule, then, in one
   `oracle_mgthris` transaction: lock the subject → re-check the link (`stillOpen()` included) →
   **`perform()`** → mark this link `DECIDED` → cancel every other live link of the subject.
4. Anything `perform()` throws rolls back the whole thing, and the link stays live so the guest can
   try again. A `ValidationException` shows its message on the page; anything else shows
   "Nothing was changed - please try again".
5. Success redirects to `email-approval.done`.

Some handlers own their page and do the write themselves. They call
`complete($link, $decision, $ip, $userAgent, $work)`: the same lock → re-check → `$work($fresh)` → mark
`DECIDED` → cancel siblings, with no button and no note. A refusal is a `ValidationException` on key `token`;
anything `$work` throws rolls the Core transaction back and the link stays live.

`$work` may write on another connection. That write is not part of Core's `oracle_mgthris` transaction,
so the two can disagree if the process dies between them. `auth.password-reset` is the one handler that
does this: it writes the new password on `oracle_mgtapps`. It is safe because the password is written
first and the link is spent after; a failure in between leaves the link live, and trying again just
writes the password again. See the forgot-password spec, "The two-database catch".

The guest pages are a plain controller, not Livewire: Livewire's update URL is not signed, so a
component on a signed page would either break or need the signature check weakened.

## 4. Adding email approval to a transaction

### 4.1 Write the handler

An illustrative leave handler (the leave names are made up; the real example is
`Modules\Finance\Services\Transaction\SalesControl\MinPriceEmailApprovalHandler`):

```php
class LeaveEmailApprovalHandler implements EmailApprovalHandler
{
    public const KEY = 'mis.leave';

    public function stillOpen(CmApprToken $link): ?string
    {
        // null = still waiting for this person at $link->cmat_step; a string = why not.
        $leave = HtLeave::find($link->cmat_subject_id);

        return $leave?->status === LeaveStatusEnum::SUBMITTED ? null : 'This request has already been decided.';
    }

    public function summary(CmApprToken $link): View
    {
        // Rendered on a guest page: escape everything with {{ }}.
        return view('mis::email-approval.leave-summary', [...]);
    }

    public function buttons(CmApprToken $link): array
    {
        return [
            new EmailApprovalButton('approve', 'Approve', 'action', note: 'optional'),
            new EmailApprovalButton('reject', 'Reject', 'action', note: 'required', style: 'danger'),
        ];
    }

    public function perform(CmApprToken $link, string $buttonKey, ?string $note, ?string $ip, ?string $userAgent): void
    {
        // Runs inside Core's transaction. Go through your normal service so the
        // state machine and its checks stay in one place.
    }

    public function mail(CmApprToken $link): EmailApprovalMail
    {
        return new EmailApprovalMail(
            heading: 'Approval needed',
            subject: 'Approval needed: leave '.$link->cmat_subject_id,
            intro: 'A leave request is waiting for your decision.',
            details: ['Employee' => '...', 'Dates' => '...'],
            buttonLabel: 'Review request',
        );
    }

    public function lifetimeHours(): ?float
    {
        return isset($this->config()['ttl_hours']) ? (float) $this->config()['ttl_hours'] : null;
    }

    public function enabled(): bool
    {
        return (bool) ($this->config()['enabled'] ?? false);
    }

    public function contact(): string
    {
        return (string) ($this->config()['contact'] ?? 'the HR department');
    }

    private function config(): array
    {
        return (array) (config('core.email_approval.handlers')[self::KEY] ?? []);
    }
}
```

Rules for `perform()`:

- Write only on `oracle_mgthris`. That is the connection Core's transaction is on; a write anywhere
  else will not roll back.
- Send notifications in `DB::connection('oracle_mgthris')->afterCommit(...)`, so nobody hears
  "approved" about a decision that was rolled back.
- Throw `ValidationException` to refuse with a message the guest can read.
- Files written to disk are not rolled back. If you store one, accept that a failure leaves it behind
  with nothing pointing at it.
- The guest is not logged in: `auth()->user()` is null. Record who decided from the link
  (`cmat_to_name`, `cmat_to_nik`, `cmat_to_ref`), and use `'EMAIL'` as the user id for audit columns.

Buttons:

| Field | Values |
|---|---|
| `kind` | `action` — posts back and spends the token; `link` — plain navigation to `url`, never spends it (e.g. "Sign in to the app instead") |
| `note` | `none`, `optional`, `required` (Core enforces `required`; max 2000 characters) |
| `style` | `primary`, `danger` |

### 4.2 Register it

In your module's service provider `boot()`:

```php
$this->app->make(EmailApprovalHandlers::class)->register(LeaveEmailApprovalHandler::KEY, LeaveEmailApprovalHandler::class);
```

The key is `module.thing`, at most 60 characters. Never rename a key once links exist — old rows
would point at a handler that is no longer registered, and their pages say "no longer usable".

### 4.3 Add its settings

Under `handlers` in `Modules/Core/config/email_approval.php`, fed by env vars:

```php
'mis.leave' => [
    'enabled' => (bool) env('MIS_LEAVE_EMAIL_APPROVAL', false),
    'ttl_hours' => (float) env('MIS_LEAVE_APPROVAL_LINK_TTL_HOURS', 72),
    'contact' => env('MIS_LEAVE_APPROVAL_CONTACT', 'the HR department'),
    'route' => 'email-approval.show', // optional: the page the link opens; this is the default
],
```

`route` names a signed route that takes `?token=`. Leave it out unless the handler has its own page
(`auth.password-reset` does).

The key has a dot in it, so read it as `config('core.email_approval.handlers')['mis.leave']`, not as one
dotted path. Add the env vars to `.env.example`.

### 4.4 Send

```php
$links = app(EmailApprovalService::class)->issue(
    LeaveEmailApprovalHandler::KEY,
    subjectId: (string) $leave->id,
    step: 'approve',
    recipients: [new EmailApprovalRecipient($name, $email, nik: $nik, ref: $approverRowId)],
);
```

- Check your own rules first (is the transaction in the right state, is the recipient allowed, is the
  recipient not the requester). Core does not know them.
- `issue()` throws `ValidationException` when the feature is switched off or the mail cannot be queued.
  On a queue failure the rows are kept, as the record that Send was pressed; the fix is to send again.
- The mail is queued on `high` and only after the transaction commits, including a caller's own
  transaction — so a worker never picks up a link that was rolled back.
- `step` lets one subject go through several stages (`check`, `approve`); `stillOpen()` decides whether
  `$link->cmat_step` is still the current one.

A handler that sends its own mail (its own wording, or a recipient who is not in the system the usual way)
calls `issueUrl($key, $subjectId, $step, $recipient)` instead. It runs the same switch-off check, cancels
the recipient's live link and sets the same expiry, but queues nothing. It returns
`['link' => CmApprToken, 'url' => string]`; mail the `url` yourself.

Other calls:

| Method | Use |
|---|---|
| `enabled($key)` | Show or hide the "Send by email" button |
| `cancelFor($key, $subjectId, ?$step)` | Withdraw live links, e.g. when the request is approved on paper or cancelled |
| `historyFor($key, $subjectId)` | Every link for the subject, newest first, for a history panel (`outcomeLabel()`, `cmat_status->badgeVariant()`) |

## 5. Settings

| Setting | Env | Default | Meaning |
|---|---|---|---|
| `enabled` | `EMAIL_APPROVAL_ENABLED` | `true` | Master switch. Off = no link can be sent, opened or used, for every handler |
| `default_ttl_hours` | — | `72` | Link lifetime when the handler returns `null`. Fractions allowed: `0.25` = 15 minutes |
| `contact` | — | `the sender of this email` | Who to ask, when no handler can be found |
| `handlers.{key}` | per handler | — | Each handler's own switch, lifetime and contact |

Both switches must be on for a handler to work. Mail needs `MAIL_MAILER` pointed at the company SMTP
relay and a running queue worker; with `MAIL_MAILER=log` the link only appears in the log.

## 6. Security

- The recipient holds **no permission**: the link is the only key. That is why it is single use,
  short-lived, signed and hashed at rest. Do not add a handler whose `perform()` does more than the
  one decision the email asked for.
- The pages are public. Never put anything in `summary()` the recipient should not see, and escape
  output (`{{ }}`).
- `stillOpen()` is checked again inside the lock, so a request decided on paper while the email sat
  unread cannot also be decided by email.
- Withdraw links (`cancelFor`) whenever the subject leaves the state the link was sent for.

## 7. Tests

Core's rules are tested against a fake handler (`tests/Feature/Core/EmailApproval/FakeEmailApprovalHandler.php`,
key `test.fake`):

- `EmailApprovalServiceTest.php` — hashing, mail queueing, re-send cancelling, open, expiry, switch-off,
  deciding once, sibling races, note rules, rollback, lifetime, per-handler route, `issueUrl()`, `complete()`.
- `EmailApprovalPageTest.php` — signed pages, tampered signature, unknown token, unavailable reasons,
  decide/done round trip, link buttons never spending the token.

Helpers in `tests/Helpers/Core/EmailApprovalHelpers.php`: `coreLink()` builds a row directly,
`recipient()`, `refusal()` returns the first validation message, `tokenFrom()` pulls the token out of a
mailed URL.

For a new handler, test only what the handler adds — what `perform()` writes, `stillOpen()` after
the transaction moves on, and your pre-send checks — through `issue()` and the real routes. See
`tests/Feature/Finance/SalesControl/EmailApprovalTest.php`.
