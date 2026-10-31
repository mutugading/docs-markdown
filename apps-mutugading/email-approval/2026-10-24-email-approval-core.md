# Email approval as a Core service — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the email-link approval machinery out of Finance into Core so any module can make a transaction approvable by email with one handler class and one `issue()` call; MinPrice becomes the first (and only) handler.

**Architecture:** Core owns one table (`CM_APPR_TOKEN`), one service (the only writer of link status), a guest controller with signed GET + CSRF POST, and one queued mailable. Modules register an `EmailApprovalHandler` under a key (`finance.min-price`) from their service provider; the handler answers "still open?", summary view, buttons, perform, mail text and lifetime. Finance's old `SALES_CTL_APPR_EML` stack is deleted; its per-link PDF copy moves to a small Finance table keyed on the Core link id.

**Tech Stack:** Laravel 12, plain controller + Blade (not Livewire), Laravel signed URLs, markdown Mailable on queue `high`, Pest 4 on SQLite.

**Spec:** `.docs-me/superpowers/specs/2026-10-23-email-approval-core-design.md` — read it alongside this plan. Today's Finance implementation (being replaced) is the best reference for tone and edge cases: `Modules/Finance/app/Services/Transaction/SalesControl/EmailApprovalService.php`, `Modules/Finance/app/Http/Controllers/SalesControl/EmailApprovalController.php`.

## Global Constraints

- Token: `bin2hex(random_bytes(32))` (64 hex chars); only `hash('sha256', $token)` is stored. Raw token never reaches a repository or a query binding.
- Opening (GET) never changes a transaction. Only a POSTed `action` button decides. A `link` button never spends the token.
- Issuing cancels only **the same recipient's** live links for the subject (any step). Deciding cancels **every other** live link for the subject.
- Default lifetime 72 hours; handler may return its own (hours); `null` → default.
- Signed URL expiry = link expiry + 6 hours (so the user sees our "expired" page, not Laravel's 403).
- Mail queued on `high`.
- MinPrice keeps env names `SALES_CONTROL_EMAIL_APPROVAL`, `SALES_CONTROL_APPROVAL_LINK_TTL_HOURS`, `SALES_CONTROL_APPROVAL_CONTACT`.
- Core master switch defaults **on** (`EMAIL_APPROVAL_ENABLED`, default `true`) so production MinPrice does not switch off on deploy; each handler has its own switch.
- Migrations: Oracle + SQLite compatible, `DISABLE_MIGRATIONS` pattern, never edit the deployed `2026_09_14_*` Finance migrations.
- No `wire:navigate`. Escape all Blade output. `vendor/bin/pint --dirty` before each commit. Tests: `php artisan test --parallel <path>`.
- Commits follow `.github/COMMIT_CONVENTION.md` (scopes `core`, `finance`). Never push.

## Review Focus

1. **Two approvers press an action at the same moment** — the second must get "already handled", never a second decision. `decide()` re-reads the link `lockForUpdate()` inside the transaction and re-checks status. Test in Task 2.
2. **Handler throws a non-validation exception mid-perform** (storage down, DB error) — everything rolls back, the link stays live and usable, page says "nothing was changed, try again". Tests in Task 2 (service) and Task 3 (page).
3. **Link whose handler is no longer registered** (module disabled, key renamed) — unavailable page, not a 500. Test in Task 3.
4. **Forged POST**: unknown button key, or a `link`-kind key posted as a decision — refused, token not spent. Test in Task 2.
5. **Recipient address differing only in case** between two issues (`Board@x` vs `board@x`) — still the same recipient, the older link is cancelled. Store email lower-cased and trimmed. Test in Task 2.

---

## File map

**Core — create**
- `Modules/Core/database/migrations/2026_10_24_100000_create_cm_appr_token_table.php`
- `Modules/Core/app/Enums/EmailApproval/EmailApprovalStatusEnum.php`
- `Modules/Core/app/Models/MgtHris/EmailApproval/CmApprToken.php`
- `Modules/Core/app/Interfaces/EmailApproval/EmailApprovalRepositoryInterface.php`
- `Modules/Core/app/Repositories/EmailApproval/EloquentEmailApprovalRepository.php`
- `Modules/Core/app/Interfaces/EmailApproval/EmailApprovalHandler.php`
- `Modules/Core/app/Data/EmailApproval/EmailApprovalRecipient.php`, `EmailApprovalButton.php`, `EmailApprovalMail.php` (plain readonly DTOs)
- `Modules/Core/app/Services/EmailApproval/EmailApprovalHandlers.php` (registry, singleton)
- `Modules/Core/app/Services/EmailApproval/EmailApprovalService.php`
- `Modules/Core/app/Mail/EmailApprovalRequested.php` + `Modules/Core/resources/views/mail/email-approval.blade.php`
- `Modules/Core/config/email_approval.php` (→ `config('core.email_approval.*')`)
- `Modules/Core/app/Http/Controllers/EmailApprovalController.php`
- `Modules/Core/resources/views/email-approval/{layout,show,unavailable,done}.blade.php`
- Tests: `tests/Feature/Core/EmailApproval/EmailApprovalServiceTest.php`, `tests/Feature/Core/EmailApproval/EmailApprovalPageTest.php`, shared fake handler `tests/Feature/Core/EmailApproval/FakeEmailApprovalHandler.php` (namespace `Tests\Feature\Core\EmailApproval`)

**Core — modify**
- `Modules/Core/app/Providers/RepositoryServiceProvider.php` (binding), `CoreServiceProvider.php` (registry singleton), `Modules/Core/routes/web.php` (guest routes)

**Finance — create**
- `Modules/Finance/database/migrations/2026_10_24_100000_replace_sales_ctl_appr_eml_with_appr_doc.php`
- `Modules/Finance/app/Models/MgtHris/Transaction/SalesControl/SalesCtlApprDoc.php`
- `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceEmailApprovalHandler.php`
- `Modules/Finance/resources/views/sales-control/email-approval/summary.blade.php` (body of today's `show.blade.php`)

**Finance — delete**
- `Http/Controllers/SalesControl/EmailApprovalController.php`, the three guest routes in `routes/web.php`, `resources/views/sales-control/email-approval/{show,done,unavailable}.blade.php`, `resources/views/layouts/approval.blade.php` (if nothing else extends it), `resources/views/mail/sales-control/price-approval-requested.blade.php`, `Models/.../SalesCtlApprEmail.php`, `Enums/.../EmailApprovalStatusEnum.php`, `Interfaces/.../EmailApprovalRepositoryInterface.php`, `Repositories/.../EloquentEmailApprovalRepository.php` + both bindings in `RepositoryServiceProvider.php`, `Mail/SalesControl/PriceApprovalRequested.php`, `config/config.php` `sales_control.email_approval` block

**Finance — modify**
- `Services/Transaction/SalesControl/EmailApprovalService.php` (shrinks), `ApprovalDocumentService.php` (reads Core link), `Providers/FinanceServiceProvider.php` (register handler), `Livewire/.../PriceExceptionApproval.php`, `Livewire/.../MinPriceApprovalManager.php` + their two blades, `resources/views/pdf/sales-control/email-approval-record.blade.php`, `tests/Helpers/Finance/SalesControlHelpers.php`, `tests/Feature/Finance/SalesControl/EmailApprovalTest.php`
- Docs: `.docs-me/sales-control-suites/email-approval.md`, `CLAUDE.md`

---

### Task 1: Core link table, model, status enum, repository

**Files:**
- Create: migration, enum, model, repository + interface (paths above)
- Modify: `Modules/Core/app/Providers/RepositoryServiceProvider.php`
- Test: `tests/Feature/Core/EmailApproval/EmailApprovalServiceTest.php` (repository cases live here too; Task 2 appends)

**Interfaces:**
- Produces:
  - Table `cm_appr_token` on `oracle_mgthris`, columns: `cmat_sys_id` int PK; `cmat_handler` string(60); `cmat_subject_id` string(40); `cmat_step` string(30); `cmat_to_name` string(120); `cmat_to_email` string(150); `cmat_to_nik` string(12) null; `cmat_to_ref` string(40) null; `cmat_token_hash` string(64) unique; `cmat_status` string(12) default `SENT`; `cmat_sent_dt` datetime; `cmat_sent_uid` string(12); `cmat_expires_dt` datetime; `cmat_opened_dt`, `cmat_decided_dt` datetime null; `cmat_decision` string(30) null; `cmat_note` string(2000) null; `cmat_ip` string(45) null; `cmat_user_agent` string(500) null; `cmat_cr_uid` string(12); `cmat_cr_dt` datetime; `cmat_upd_uid` string(12) null; `cmat_upd_dt` datetime null. Index `cm_appr_token_nx01` on (`cmat_handler`, `cmat_subject_id`, `cmat_status`). Oracle only: check constraint `CM_APPR_TOKEN_C01` on status values. Migration key `cm_appr_token`. Also registers sequence row `CM_APPR_TOKEN_CMAT_SYS_ID_SEQ` in `hm_mst_sequences` (copy `registerSequenceRows()` from `Modules/Finance/database/migrations/2026_09_14_100300_create_sales_ctl_email_sys_id_trg.php`; no trigger — the app always supplies the id). `down()` drops the table and the sequence row.
  - `enum EmailApprovalStatusEnum: string { Sent='SENT'; Opened='OPENED'; Decided='DECIDED'; Expired='EXPIRED'; Cancelled='CANCELLED' }` with `label()`, `badgeVariant()`, `isLive(): bool` (Sent|Opened), `unavailableReason(): string` (generic wording, no "Finance": e.g. Expired → "This link has expired. Ask {contact} to send a new one." — take `string $contact` as a parameter).
  - `CmApprToken` model: `$table='cm_appr_token'`, int non-incrementing key, `$timestamps=false`, `$fillable` = all columns, casts (status enum, datetimes), `LogsActivityWithDescription` (log status, to_email, decision). Methods `isUsable(): bool` (live and not expired), `hasExpired(): bool`, `outcomeLabel(): string` (`Str::headline($cmat_decision)` when Decided, else status label — used by history lists).
  - `EmailApprovalRepositoryInterface`: `find(int): ?CmApprToken`; `findByTokenHash(string): ?CmApprToken`; `lockForUpdate(int): ?CmApprToken`; `forSubject(string $handler, string $subjectId): Collection` (newest first); `create(array): CmApprToken`; `update(int, array): CmApprToken`; `cancelLive(string $handler, string $subjectId, string $uid, ?string $email = null, ?string $ref = null, ?int $exceptSysId = null, ?string $step = null): int` (mass update, like Finance's `cancelLiveForRequest`); `nextSysId(): int` (`SysIdHelper::generateBatch(SEQ, uid, 1, null)[0]`).

- [ ] **Step 1: Write failing tests**

```php
it('cancels only the matching recipient when an email is given', function () {
    coreLink(1, ['cmat_to_email' => 'a@x.test']);
    coreLink(2, ['cmat_to_email' => 'b@x.test']);
    coreLink(3, ['cmat_subject_id' => '99', 'cmat_to_email' => 'a@x.test']);

    $n = app(EmailApprovalRepositoryInterface::class)->cancelLive('test.fake', '1', 'SYSTEM', email: 'a@x.test');

    expect($n)->toBe(1)
        ->and(CmApprToken::find(1)->cmat_status)->toBe(EmailApprovalStatusEnum::Cancelled)
        ->and(CmApprToken::find(2)->cmat_status)->toBe(EmailApprovalStatusEnum::Sent)
        ->and(CmApprToken::find(3)->cmat_status)->toBe(EmailApprovalStatusEnum::Sent);
});

it('leaves decided links alone when cancelling, and honours exceptSysId', function () { /* link 1 Decided, 2 Sent, 3 Opened; cancelLive(except: 3) → 1 Decided, 2 Cancelled, 3 Opened */ });
```

`coreLink(int $sysId, array $overrides = []): CmApprToken` is a helper function at the top of the test file (handler `test.fake`, subject `'1'`, step `approve`, status `SENT`, expires +72h, token hash of `"token-$sysId"`). Task 2/3 reuse it — put it in `FakeEmailApprovalHandler.php` alongside the class.

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Core/EmailApproval` — expect FAIL (class not found).
- [ ] **Step 3: Implement** migration, enum, model, interface, repository; bind in Core `RepositoryServiceProvider`.
- [ ] **Step 4: Run** same command — expect PASS. Also `php artisan migrate --pretend --database=oracle_mgthris --path=Modules/Core/database/migrations/2026_10_24_100000_create_cm_appr_token_table.php` only if pointed at the **dev** DB (check `DB_HOST` is not the prod scan host) — otherwise skip and say so.
- [ ] **Step 5: Commit** `feat(core): add the email approval link table and repository`

---

### Task 2: Handler contract, registry, Core service, mailable, config

**Files:**
- Create: handler interface, 3 DTOs, registry, service, mailable + view, config (paths above)
- Modify: `Modules/Core/app/Providers/CoreServiceProvider.php` (`$this->app->singleton(EmailApprovalHandlers::class)`)
- Test: append to `tests/Feature/Core/EmailApproval/EmailApprovalServiceTest.php`; fake handler in `FakeEmailApprovalHandler.php`

**Interfaces:**
- Consumes: Task 1 model, enum, repository.
- Produces:

```php
interface EmailApprovalHandler
{
    /** null = still waiting for this recipient at $link->cmat_step; otherwise the reason shown. */
    public function stillOpen(CmApprToken $link): ?string;
    public function summary(CmApprToken $link): \Illuminate\Contracts\View\View;
    /** @return list<EmailApprovalButton> */
    public function buttons(CmApprToken $link): array;
    /** Runs inside Core's transaction, after stillOpen() re-check. Throw ValidationException to refuse with a reason. */
    public function perform(CmApprToken $link, string $buttonKey, ?string $note, ?string $ip, ?string $userAgent): void;
    public function mail(CmApprToken $link): EmailApprovalMail;
    /** Hours; null = core.email_approval.default_ttl_hours. */
    public function lifetimeHours(): ?int;
    public function enabled(): bool;
    /** Who the recipient should ask, shown in mail and pages. */
    public function contact(): string;
}

readonly class EmailApprovalRecipient { __construct(string $name, string $email, ?string $nik = null, ?string $ref = null) }
readonly class EmailApprovalButton  { __construct(string $key, string $label, string $kind /* 'action'|'link' */, string $note = 'none' /* none|optional|required */, string $style = 'primary' /* primary|danger */, ?string $url = null /* required when kind=link */) }
readonly class EmailApprovalMail    { __construct(string $subject, string $intro, array $details /* label => value */, string $buttonLabel) }

class EmailApprovalHandlers  // registry
{
    public function register(string $key, string $handlerClass): void;
    public function get(string $key): ?EmailApprovalHandler;   // resolved from container; null when unknown
}

class EmailApprovalService
{
    public function enabled(string $handlerKey): bool;          // master switch && handler registered && handler->enabled()
    /** @param list<EmailApprovalRecipient> $recipients  @return Collection<int, CmApprToken> */
    public function issue(string $handlerKey, string $subjectId, string $step, array $recipients): Collection;
    public function cancelFor(string $handlerKey, string $subjectId, ?string $step = null): int;
    public function resolve(mixed $token): ?CmApprToken;        // null for non-string/blank/unknown
    /** null = usable (and first open recorded); string = why not. Never touches the transaction. */
    public function open(CmApprToken $link, ?string $ip, ?string $userAgent): ?string;
    /** Throws ValidationException with the reason on any refusal. Returns the button pressed. */
    public function decide(CmApprToken $link, string $buttonKey, ?string $note, ?string $ip, ?string $userAgent): EmailApprovalButton;
    public function historyFor(string $handlerKey, string $subjectId): Collection;
    public function handlerFor(CmApprToken $link): ?EmailApprovalHandler;
    public function contactFor(?CmApprToken $link): string;    // handler contact, else core.email_approval.contact
}
```

- Config `Modules/Core/config/email_approval.php`:

```php
return [
    'enabled' => (bool) env('EMAIL_APPROVAL_ENABLED', true),
    'default_ttl_hours' => 72,
    'contact' => 'the sender of this email',
    'handlers' => [
        'finance.min-price' => [
            'enabled' => (bool) env('SALES_CONTROL_EMAIL_APPROVAL', false),
            'ttl_hours' => (int) env('SALES_CONTROL_APPROVAL_LINK_TTL_HOURS', 72),
            'contact' => env('SALES_CONTROL_APPROVAL_CONTACT', 'the Finance department'),
        ],
    ],
];
```
Check `config('core.email_approval.enabled')` resolves (Core's `registerConfig` keys a non-`config.php` file as `core.<file>`); adjust the key if it doesn't.

Behaviour that the signatures don't fix:
- `issue`: refuse with `ValidationException` (`'email_approval' => 'Approval by email is switched off on this environment.'`) when `! enabled()`. Per recipient, inside one `oracle_mgthris` transaction: `cancelLive(handler, subject, uid, email: lower(trim(email)), ref: recipient ref)` then create the row (email stored lower-cased/trimmed). After the commit, queue one `EmailApprovalRequested` per row. Queue failure: `report()`, then throw `ValidationException` ("The email could not be sent. The link is recorded; try sending again.") — rows stay `SENT`.
- `open`: order of checks — feature off → handler missing → status not live → expired (write `EXPIRED`) → `stillOpen()` reason. When usable and status `SENT`: set `OPENED`, `opened_dt`, ip, user agent (truncate 500). Later opens change nothing.
- `decide`: find button by key among `buttons()`; refuse unknown key and `kind !== 'action'` ("That choice is not available."); refuse blank note when `note === 'required'` (`'note' => 'Please give a reason.'`). Then in one transaction: `lockForUpdate` re-read, re-check live/expired/`stillOpen`, `perform()`, update the link (`DECIDED`, decision = key, note trimmed ≤2000, decided_dt, ip, ua, upd_uid `EMAIL`), `cancelLive(handler, subject, 'EMAIL', exceptSysId: link id)`. Any exception rolls the whole thing back and is rethrown.
- `linkFor` is private: `URL::temporarySignedRoute('email-approval.show', expires + 6h, ['token' => $token])`.
- Mailable `EmailApprovalRequested(CmApprToken $link, EmailApprovalMail $mail, string $link url, string $contact)`, `onQueue('high')`, markdown view `core::mail.email-approval` modelled on `Modules/Finance/resources/views/mail/sales-control/price-approval-requested.blade.php` (greeting, `intro`, `details` in a panel, one button with `buttonLabel`, expiry line, "nothing happens until you open the link and confirm", subcopy with raw URL). Keep that file's docblock note about the token living in the queued payload.

`FakeEmailApprovalHandler` (key `test.fake`): public static props `?string $notOpenReason = null`, `?int $hours = null`, `bool $throw = false`, `array $performed = []`; buttons: `yes` (action, note optional), `no` (action, note required, danger), `login` (link, url `https://example.test/login`). `perform` appends `[$key, $note]` to `$performed` and throws `RuntimeException` when `$throw`. Reset statics in `beforeEach`; register it in `beforeEach` via `app(EmailApprovalHandlers::class)->register('test.fake', FakeEmailApprovalHandler::class)`. `enabled()` true.

- [ ] **Step 1: Write failing tests** (all in `EmailApprovalServiceTest.php`, `Mail::fake()` in `beforeEach`)

```php
it('stores only the hash and queues one mail per recipient on high', function () {
    $links = svc()->issue('test.fake', '1', 'approve', [recipient('a@x.test'), recipient('b@x.test')]);
    expect($links)->toHaveCount(2)->and(strlen($links[0]->cmat_token_hash))->toBe(64);
    Mail::assertQueued(EmailApprovalRequested::class, 2);
    Mail::assertQueued(EmailApprovalRequested::class, fn ($m) => $m->queue === 'high'
        && ! str_contains(CmApprToken::query()->pluck('cmat_token_hash')->implode(','), tokenFrom($m->url)));
});
it('cancels the same recipient\'s earlier link only, ignoring address case', /* issue A, issue B, issue 'A@X.TEST ' → A1 Cancelled, B Sent, A2 Sent */);
it('records the first open once and changes nothing else', /* open twice → OPENED, opened_dt unchanged second time, FakeHandler::$performed empty */);
it('reports expired and writes EXPIRED', /* expires -1 min → open returns reason, status EXPIRED */);
it('reports the handler\'s reason when the step moved on', /* $notOpenReason = 'Moved on.' → open returns 'Moved on.'; decide throws ValidationException 'Moved on.'; performed empty */);
it('refuses everything when the master switch is off', /* config core.email_approval.enabled=false → issue throws; open returns a reason */);
it('decides, spends the token and cancels every other live link for the subject', /* 3 links same subject, decide link1 'yes' → link1 DECIDED decision 'yes', others CANCELLED, other subject untouched */);
it('cannot decide the same link twice', /* decide again → ValidationException, performed has 1 entry */);
it('lets a second approver lose cleanly after a sibling decided', /* review focus 1: links 1,2; decide(1); decide(2 — stale in-memory model still saying SENT) → ValidationException, performed has 1 entry */);
it('refuses unknown and link-kind buttons without spending the token', /* review focus 4: decide 'nope' and 'login' → ValidationException; link still SENT/OPENED */);
it('requires a note for a required-note button', /* decide 'no' with '  ' → ValidationException key 'note'; with 'Too high' → decided, note stored */);
it('rolls back and keeps the link live when perform throws', /* review focus 2: $throw → RuntimeException propagates; link still live */);
it('uses the handler lifetime, else the default', /* $hours=5 → expires ≈ now+5h; null → now+72h (use Carbon::setTestNow) */);
it('works for an employee recipient with a NIK and no ref', /* issue recipient(nik:'0040', ref:null) → open → decide 'yes' → DECIDED, cmat_to_nik '0040' */);
```

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Core/EmailApproval` — expect FAIL.
- [ ] **Step 3: Implement** contract, DTOs, registry, config, service, mailable + view; register the singleton.
- [ ] **Step 4: Run** same — expect PASS.
- [ ] **Step 5: Commit** `feat(core): add the email approval service and handler contract`

---

### Task 3: Guest controller, routes and pages

**Files:**
- Create: `Modules/Core/app/Http/Controllers/EmailApprovalController.php`, `Modules/Core/resources/views/email-approval/{layout,show,unavailable,done}.blade.php`
- Modify: `Modules/Core/routes/web.php` (outside any `auth` group)
- Test: `tests/Feature/Core/EmailApproval/EmailApprovalPageTest.php`

**Interfaces:**
- Consumes: Task 2 `SalesEmailApprovalService` (`resolve`, `open`, `decide`, `handlerFor`, `contactFor`).
- Produces routes: `GET /email-approval/review` → `email-approval.show` (middleware `signed`); `POST /email-approval/decide` → `email-approval.decide`; `GET /email-approval/done` → `email-approval.done`.

Behaviour:
- `show`: resolve → `open()` → on reason render `unavailable` (message + contact); else render `show` with `$link`, `$summary` (`handler->summary($link)`), `$buttons`, `$token`, `$contact`. Invalid/unknown token → "This link is not valid. Check that you opened the most recent email."
- `show.blade.php`: `@extends('core::email-approval.layout')`; render the handler's summary with `{!! $summary->render() !!}` and a one-line comment saying why that is safe (it is a module's own Blade view, which escapes its own output). One form, POST to `email-approval.decide`, hidden `token`; one `<textarea name="note">` shown when any action button takes a note (label it "Note (required to reject)" style from the button flags); each action button is `<button name="decision" value="{key}">` styled by `style`; each link button is `<a href="{url}">`. Show `$errors->first()` above the form. Use Flux/Tailwind look of today's `Modules/Finance/resources/views/layouts/approval.blade.php` and `.../email-approval/show.blade.php` (keep `@livewireScripts` note). Layout subtitle reads "Approval", not "Sales Control".
- `decide`: validate `token` required string, `decision` required string ≤30, `note` nullable string ≤2000. Resolve (invalid → back with error). `decide()`; `ValidationException` → `back()->withInput()->withErrors(['decision' => first message])`; any other `Throwable` → `report()` + back with "Something went wrong recording your decision. Nothing was changed - please try again." Success → redirect to `email-approval.done` with session flash `decided` = button label and `contact`.
- `done`: "Recorded: {label}. You can close this page." No token in the URL.

- [ ] **Step 1: Write failing tests**

```php
it('shows the summary and buttons for a valid signed link and records the open', /* get(signedUrl) → 200, sees 'Fake summary', 'Yes', 'No', link to https://example.test/login; status OPENED */);
it('refuses a tampered signature', /* signed url with token swapped → 403 */);
it('shows unavailable for an expired, used or cancelled link', /* dataset of three statuses/overrides → 200, sees reason, no form */);
it('shows unavailable when the link\'s handler is not registered', /* review focus 3: coreLink with cmat_handler 'gone.away' → 200 unavailable, not 500 */);
it('posting yes decides and lands on done', /* post decide → redirect to done; follow → sees 'Yes'; status DECIDED */);
it('posting no without a note returns to the page with the error', /* post decision=no note='' → redirect back, session errors 'decision'; link still live */);
it('shows nothing-changed when the handler blows up', /* $throw → back with 'Nothing was changed' message; link live */);
it('following the link button never spends the token', /* the login button is a plain href; after GET, link status OPENED and decided_dt null */);
```
Build signed URLs in tests with `URL::temporarySignedRoute('email-approval.show', now()->addDay(), ['token' => 'token-1'])`. For POSTs, `withoutMiddleware(VerifyCsrfToken::class)` is not needed in Laravel tests (CSRF is skipped when running tests) — check and drop it if so.

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Core/EmailApproval` — expect FAIL.
- [ ] **Step 3: Implement** controller, routes, four views.
- [ ] **Step 4: Run** same — expect PASS. Then `vendor/bin/pint --dirty`.
- [ ] **Step 5: Commit** `feat(core): add the guest pages for approving by email`

---

### Task 4: Move MinPrice onto Core (fresh start)

**Files:** see the Finance create / delete / modify lists in the file map.

**Interfaces:**
- Consumes: Core `EmailApprovalService::issue/cancelFor/historyFor/enabled`, `EmailApprovalHandler`, `EmailApprovalHandlers::register`, `CmApprToken`, `EmailApprovalRecipient`, `EmailApprovalButton`, `EmailApprovalMail`.
- Produces:
  - Handler key `finance.min-price`, step `approve`, subject id = `(string) scar_sys_id`, recipient ref = `(string) scap_sys_id`.
  - Table `sales_ctl_appr_doc`: `scad_cmat_sys_id` int PK, `scad_doc_path` string(500), `scad_doc_hash` string(64), `scad_cr_uid` string(12), `scad_cr_dt` datetime. Model `SalesCtlApprDoc`.
  - Finance `SalesEmailApprovalService` keeps only: `enabled(): bool`, `send(int $requestSysId, int $approverSysId): CmApprToken`, `cancelFor(int $requestSysId): int`, `historyFor(int $requestSysId): Collection`, `rulesFor(SalesCtlApprRequest): Collection`.
  - `ApprovalDocumentService::storeDecisionRecord(SalesCtlApprRequest $request, CmApprToken $link, iterable $lines = [], iterable $minPrices = []): array{path,hash}` (file name uses `cmat_sys_id`).

Behaviour:
- **Migration** (key `sales_ctl_appr_eml_to_doc`): `up()` drops `sales_ctl_appr_eml` if present (Oracle drops its trigger with it), deletes the `SALES_CTL_APPR_EML_SCAE_SYS_ID_SEQ` row from `hm_mst_sequences` (leave `SALES_CTL_APPROVER_SCAP_SYS_ID_SEQ` — the approver table still uses it), creates `sales_ctl_appr_doc`. `down()` drops `sales_ctl_appr_doc` and recreates `sales_ctl_appr_eml` with the same columns/indexes as `2026_09_14_100100` plus the sequence row.
- **Finance `send`**: keep `assertSendable` + `assertNotRequester` exactly; then `core->issue('finance.min-price', (string) $requestSysId, 'approve', [new EmailApprovalRecipient($approver->displayName(), $approver->scap_email, $approver->scap_nik, (string) $approver->scap_sys_id)])->first()`, then the existing "Approval request sent" requester notification. `enabled()` delegates to Core `enabled('finance.min-price')`.
- **`MinPriceEmailApprovalHandler`**:
  - `stillOpen`: request exists and is `Printed`, else "This request has already been decided elsewhere. Nothing more is needed from you." (missing request: "The request this link points at no longer exists.").
  - `summary`: `view('finance::sales-control.email-approval.summary', [...])` with request, lines, min-price rules — the body of today's `show.blade.php` minus the form.
  - `buttons`: `approve` "Approve" action / note optional / primary; `reject` "Reject" action / note required / danger.
  - `perform('approve')`: actor (master approver by `cmat_to_ref`, else `ApprovalActorData::fromInvitation(cmat_to_name, cmat_to_email)`), render+store the decision record from a clone of the link already showing `Decided` + decision `approve` + note/ip/ua, `recordGeneratedAttachment`, `ApprovalRequestService::approve(...)` with BOD fields and the price-list activation callback (lift from today's service), write `SalesCtlApprDoc`. `perform('reject')`: `ApprovalRequestService::reject($id, $note, $actor)`. Requester notifications ("Request approved/rejected … by email", `mail: true`) go in `DB::connection('oracle_mgthris')->afterCommit(...)` so a rolled-back decision sends nothing.
  - `mail`: subject `Approval needed: {req_no} ({type label})`, intro "A {type} request is waiting for your decision.", details Request / Customer / Document / Reason given (only filled ones), button "Review this request".
  - `lifetimeHours`, `enabled`, `contact`: from `config('core.email_approval.handlers.finance.min-price.*')`.
  - Registered in `FinanceServiceProvider::boot()`: `$this->app->make(EmailApprovalHandlers::class)->register('finance.min-price', MinPriceEmailApprovalHandler::class)`.
- **PDF view**: `$invitation->scae_*` → `$link->cmat_*`; status line prints `APPROVED` / `REJECTED` from `cmat_decision`; update the header comment's table names.
- **Livewire + blades**: `historyFor` now returns `CmApprToken`; blades use `cmat_sys_id`, `cmat_to_email`, `cmat_sent_dt`, `$link->cmat_status->badgeVariant()` and `$link->outcomeLabel()`. Success toast reads `cmat_to_email` / `cmat_expires_dt`.
- **Test helper** `salesControlInvitation(int $sysId, int $requestSysId, string $token, array $overrides = [])` now creates a `CmApprToken` (handler `finance.min-price`, subject `(string) $requestSysId`, step `approve`, ref from `scae_scap_sys_id` override renamed to `cmat_to_ref`).
- **Tests**: rewrite every case in `tests/Feature/Finance/SalesControl/EmailApprovalTest.php` (these are E1–E9 of email-approval.md) to drive Core: sends via Finance `send`, decisions via `app(Core EmailApprovalService::class)->decide($link, 'approve'|'reject', ...)` or the Core routes, `Mail::assertQueued(EmailApprovalRequested::class)`. Same assertions as today, with `scae_doc_path` → `SalesCtlApprDoc::find($link->cmat_sys_id)->scad_doc_path`. Add one: `it('keeps working with the existing env switch name')` — `config(['core.email_approval.handlers.finance.min-price.enabled' => false])` → Finance `enabled()` false and `send` throws.
- **Docs**: rewrite the mechanics sections of `.docs-me/sales-control-suites/email-approval.md` to point at Core (table, service, routes, handler); add a row in `CLAUDE.md` "Reusable Helpers"-style or a short "Email approval" subsection: *"To make a transaction approvable by email, implement `Modules\Core\Interfaces\EmailApproval\EmailApprovalHandler`, register it from your module's service provider, and call `EmailApprovalService::issue()`. See `.docs-me/sales-control-suites/email-approval.md`."*

- [ ] **Step 1: Rewrite tests + helper** against the new names (they fail: classes missing).
- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Finance/SalesControl` — expect FAIL.
- [ ] **Step 3: Implement** migration, `SalesCtlApprDoc`, handler, slimmed service, document service, provider registration, PDF/Livewire/blade updates.
- [ ] **Step 4: Delete** the Finance files listed above and the config block; `grep -rn "SalesCtlApprEmail\|scae_\|PriceApprovalRequested\|sales-control.email-approval\|finance.sales_control.email_approval" Modules tests app config` → no hits outside the two deployed `2026_09_14_*` migrations and the new replace migration.
- [ ] **Step 5: Run** `php artisan test --parallel tests/Feature/Finance/SalesControl tests/Feature/Core` — expect PASS. Then the full suite `php artisan test --parallel` and `vendor/bin/pint --test` — expect PASS. Also once with `ACTIVITY_LOGGER_DB_CONNECTION= php artisan test --parallel tests/Feature/Core/EmailApproval tests/Feature/Finance/SalesControl` to see what CI sees.
- [ ] **Step 6: Docs** — edit `email-approval.md` and `CLAUDE.md` as above.
- [ ] **Step 7: Commit** in two, per concern: `refactor(finance): approve min price requests through the core email approval` (code + migration + tests) and `docs(core): document email approval as a core service` (docs).
