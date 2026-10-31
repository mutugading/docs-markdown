# Forgot Password by Work Email Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A production-mode user with a work email resets their own password through a 15-minute single-use emailed link, with no call to IT-SOFT.

**Architecture:** Core's email token (`CM_APPR_TOKEN`, `EmailApprovalService`) gets three small, additive changes: a handler may name its own landing route, a link can be issued without Core's mail, and a link can be spent by a caller-supplied callback (`complete()`). Auth adds the `auth.password-reset` handler, its own mailable, a guest request page (Livewire) and a guest reset page (controller), plus one `AccountPasswordService` that both the reset and User Settings use to write the password.

**Tech Stack:** Laravel 12, Livewire 3, Pest 4 on SQLite, `RateLimiter`, Spatie Activitylog.

**Spec:** `.docs-me/superpowers/specs/2026-10-26-forgot-password-design.md`

## Global Constraints

- Link lifetime **15 minutes** (`ttl_hours` 0.25). Recipient is **only** `HmEmpData.hmemd_work_email` of the employee whose `hmemd_sys_id = MstUsers.mu_hmemd_sys_id`.
- Rate limits: per account **1 per 120 s** and **3 per hour**; per IP **10 per hour**. Count every attempt, qualifying or not.
- The request page always shows, word for word: *"If this account has a work email, a reset link has been sent. It is valid for 15 minutes. If you do not receive it, contact IT Support."*
- Password rules: `required|string|min:8|max:15|confirmed`.
- Saving writes `user_password_new = encryptPassword(new)`, `mu_pwd_expired_dt = today + 3 months`, `mu_tot_wrong_pwd = 0`.
- Off by default: `PASSWORD_RESET_BY_EMAIL=false`. The "Forgot password?" link and both pages exist only when `config('auth_mode.mode') === 'production'` **and** `EmailApprovalService::enabled('auth.password-reset')`; otherwise 404.
- Existing Approve/Reject behaviour, `EmailApprovalHandler` interface, `issue()`, `decide()` and `EmailApprovalRequested` stay unchanged.
- Never log, flash, put in session, or `withInput()` a password. No default/shared password anywhere.
- Plain `href` links; no `wire:navigate`. Escape all output.
- Commits: Conventional Commits, scopes `core` / `auth`; read `.github/COMMIT_CONVENTION.md` first. Do not push.
- Tests: `php artisan test --parallel <path>`.

## Review Focus

1. **A blocked account** (`mu_tot_wrong_pwd = 3`) is the main user. It must qualify and, after the reset, be unblocked. Test in Task 4 and Task 5.
2. **User ID typed with spaces or different case** (`" ab123 "`, `"AB123"`): trimmed before lookup, and the rate-limit key is trimmed + lower-cased so case variants can't get around the limit. Test in Task 4.
3. **The work email changed (or was cleared) after the link went out**: the link is refused. Test in Task 3.
4. **Inactive employee** (`hmemd_sts_data != 2`, hidden by HmEmpData's global scope) or `mu_active != 'Y'`: no mail, same message. Test in Task 4.
5. **The password write fails** (e.g. Oracle error in `fEncrypt`): the link stays usable and the page says nothing changed. Test in Task 5.

---

### Task 1: Core — own route, issue without mail, `complete()`

**Files:**
- Modify: `Modules/Core/app/Services/EmailApproval/EmailApprovalService.php`
- Modify: `docs/email-approval/developer-guide.md` (sections 4.3, 4.4, 3 and 7)
- Test: `tests/Feature/Core/EmailApproval/EmailApprovalServiceTest.php`

**Interfaces:**
- Produces:
  - Handler config key `route` (optional) in `core.email_approval.handlers[<key>]`; default `'email-approval.show'`. Read through `config('core.email_approval.handlers')[$key]['route']` (keys contain dots, so no dotted path).
  - `issueUrl(string $handlerKey, string $subjectId, string $step, EmailApprovalRecipient $recipient): array{link: CmApprToken, url: string}` — same checks, cancel-then-create and expiry as `issue()`, no mail.
  - `complete(CmApprToken $link, string $decision, ?string $ip, ?string $userAgent, callable $work): void` — `$work(CmApprToken $fresh)` runs inside the `oracle_mgthris` transaction after the lock + `whyNot()` re-check; then the link is marked DECIDED (`cmat_decision = $decision`, `cmat_note = null`, ip, user agent) and siblings cancelled. Refusal → `ValidationException` key `token`. Anything `$work` throws rolls back and propagates.

- [ ] **Step 1: Write the failing tests** (use the existing `test.fake` handler and helpers)

```php
it('points the link at the handler route when one is configured', function () {
    config(['core.email_approval.handlers' => ['test.fake' => ['route' => 'email-approval.done']]]);
    $url = svc()->issueUrl('test.fake', '1', 'approve', recipient('a@x.test'))['url'];
    expect($url)->toContain('/email-approval/done');
    Mail::assertNothingQueued();
});

it('still points issue() links at email-approval.show by default', function () {
    svc()->issue('test.fake', '1', 'approve', [recipient('a@x.test')]);
    Mail::assertQueued(EmailApprovalRequested::class, fn ($m) => str_contains($m->url, '/email-approval/review'));
});

it('issueUrl cancels the live link for the same recipient', function () {
    $first = svc()->issueUrl('test.fake', '1', 'approve', recipient('a@x.test'))['link'];
    svc()->issueUrl('test.fake', '1', 'approve', recipient('a@x.test'));
    expect($first->fresh()->cmat_status)->toBe(EmailApprovalStatusEnum::Cancelled);
});

it('complete runs the work once and marks the link decided', function () {
    $link = coreLink(1);
    $calls = 0;
    svc()->complete($link, 'reset', '1.2.3.4', 'UA', function () use (&$calls) { $calls++; });
    expect($calls)->toBe(1)
        ->and($link->fresh()->cmat_status)->toBe(EmailApprovalStatusEnum::Decided)
        ->and($link->fresh()->cmat_decision)->toBe('reset')
        ->and($link->fresh()->cmat_note)->toBeNull()
        ->and(refusal(fn () => svc()->complete($link->fresh(), 'reset', null, null, fn () => null)))->not->toBe('');
});

it('complete leaves the link live when the work throws', function () {
    $link = coreLink(1);
    expect(fn () => svc()->complete($link, 'reset', null, null, fn () => throw new RuntimeException('db')))
        ->toThrow(RuntimeException::class);
    expect($link->fresh()->cmat_status->isLive())->toBeTrue();
});

it('complete refuses an expired link without running the work', function () {
    $link = coreLink(1, ['cmat_expires_dt' => now()->subMinute()->format('Y-m-d H:i:s')]);
    $ran = false;
    refusal(fn () => svc()->complete($link, 'reset', null, null, function () use (&$ran) { $ran = true; }));
    expect($ran)->toBeFalse()->and($link->fresh()->cmat_status)->toBe(EmailApprovalStatusEnum::Expired);
});
```

Handler keys contain a dot, so always set handler config as a whole array, never by dotted path.

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --parallel tests/Feature/Core/EmailApproval/EmailApprovalServiceTest.php`
Expected: the new tests FAIL (`issueUrl`/`complete` undefined); existing tests PASS.

- [ ] **Step 3: Implement in `EmailApprovalService`**
  - Extract the body of `issue()`'s transaction into a private `createLinks(string $handlerKey, string $subjectId, string $step, array $recipients): array{0: Collection, 1: array<int,string>}` (links, sysId => token). `issue()` calls it then mails exactly as now; `issueUrl()` calls it with one recipient and returns `['link' => …, 'url' => $this->linkFor(…)]`.
  - Extract `decide()`'s transaction (lock subject → `whyNot` → work → DECIDED update → `cancelLive` siblings) into a private `spend(CmApprToken $link, string $decision, ?string $note, ?string $ip, ?string $userAgent, callable $work): void`. `decide()` keeps its button/note checks and calls `spend(…, fn ($fresh) => $handler->perform($fresh, $buttonKey, $note, $ip, $userAgent))`. `complete()` = `handlerFor()` null check + `spend($link, $decision, null, …, $work)`.
  - `linkFor()`: route = handler config `route` ?? `'email-approval.show'`.

- [ ] **Step 4: Run the whole Core and Finance email-approval suites**

Run: `php artisan test --parallel tests/Feature/Core/EmailApproval tests/Feature/Finance/SalesControl/EmailApprovalTest.php`
Expected: all PASS.

- [ ] **Step 5: Docs** — in `docs/email-approval/developer-guide.md`: add `route` to the settings example (4.3), `issueUrl()` for handlers that send their own mail (4.4), `complete()` and its "write may be on another connection" rule (3) naming `auth.password-reset` as the one handler that writes on `oracle_mgtapps` and why that is safe (spec, "The two-database catch").

- [ ] **Step 6: Commit** — `feat(core): let an email link land on its handler's own page and be spent by the caller`

---

### Task 2: Auth — one place that sets a password

**Files:**
- Create: `Modules/Auth/app/Services/AccountPasswordService.php`
- Modify: `Modules/Auth/app/Livewire/User/UserSetting.php:86-89`
- Create: `tests/Helpers/Auth/PasswordResetHelpers.php`
- Test: `tests/Feature/Auth/AccountPasswordServiceTest.php`

**Interfaces:**
- Consumes: `Modules\Core\Services\Master\User\MasterUserService::encryptPassword(string): string`.
- Produces:
  - `AccountPasswordService::set(MstUsers $user, string $password, string $via): void` — writes the three fields from Global Constraints, saves with the model's own activity log suppressed (`activity()->withoutLogs(...)`), then writes one activity: log name `'Master User'`, `performedOn($user)`, description `"Password changed ({$via}), ID: {$user->user_id}"`, properties `['via' => $via]`. No password or hash in the log.
  - Helpers (auto-loaded from `tests/Helpers/`): `resetAccount(array $user = [], array $employee = []): MstUsers` — creates `MST_USERS` on `oracle_mgtapps` if missing (copy the column list from `Modules/Auth/database/migrations/2025_11_04_102000_create_mst_users_table.php`; under SQLite each connection is its own DB and that migration only runs on `oracle_mgthris`), inserts an `HmEmpData` (`hmemd_sts_data` 2, `hmemd_work_email` `'worker@mutugading.test'`) and an active `MstUsers` (`mu_active` `'Y'`, `mu_tot_wrong_pwd` 3, `mu_hmemd_sys_id` linked). `fakeEncrypt(): void` mocks `MasterUserService::encryptPassword` to return `'ENC:'.$plain`. `storedPassword(string $userId): ?string` reads `user_password_new` through `->toBase()` (the model accessor expects 16 raw bytes).

- [ ] **Step 1: Write the failing test**

```php
it('sets the password, pushes expiry 3 months and clears the block', function () {
    fakeEncrypt();
    $user = resetAccount();
    app(AccountPasswordService::class)->set($user, 'NewPass123', 'email reset');
    $fresh = MstUsers::query()->find($user->user_id);
    expect(storedPassword($user->user_id))->toBe('ENC:NewPass123')
        ->and($fresh->mu_tot_wrong_pwd)->toBe(0)
        ->and($fresh->mu_pwd_expired_dt->toDateString())->toBe(now()->addMonths(3)->toDateString())
        ->and(ActivityLog::query()->where('subject_id', $user->user_id)->get())->toHaveCount(1)
        ->and(ActivityLog::query()->first()->properties->toJson())->not->toContain('ENC:');
});
```

(`ActivityLog` = `Modules\Core\Models\MgtHris\Master\Logs\ActivityLog`, never Spatie's model.)

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Auth/AccountPasswordServiceTest.php` → FAIL (class missing).
- [ ] **Step 3: Implement `set()`**, then replace the three assignments + `save()` in `UserSetting::changePassword()` with `$this->accounts->set($this->userAccount, $this->new_password, 'user setting')` (inject in `boot()`; current-password check stays in the component).
- [ ] **Step 4: Run** the same test → PASS.
- [ ] **Step 5: Commit** — `refactor(auth): set a password through one service and keep the hash out of the activity log`

---

### Task 3: Auth — the `auth.password-reset` handler and its email

**Files:**
- Create: `Modules/Auth/app/Services/PasswordReset/PasswordResetEmailHandler.php`
- Create: `Modules/Auth/app/Mail/PasswordResetLinkMail.php`
- Create: `Modules/Auth/resources/views/mail/password-reset.blade.php` (markdown mail)
- Create: `Modules/Auth/resources/views/password-reset/summary.blade.php`
- Modify: `Modules/Core/config/email_approval.php` (add handler entry)
- Modify: `Modules/Auth/app/Providers/AuthServiceProvider.php` (register in `boot()`, like `FinanceServiceProvider:42`)
- Modify: `.env.example` (`PASSWORD_RESET_BY_EMAIL=false`)
- Test: `tests/Feature/Auth/PasswordResetHandlerTest.php`

**Interfaces:**
- Consumes: Task 1's `route` config key.
- Produces:
  - `PasswordResetEmailHandler::KEY = 'auth.password-reset'`. Subject id = `user_id`, step `'reset'`, recipient ref null.
  - Config entry: `'auth.password-reset' => ['enabled' => (bool) env('PASSWORD_RESET_BY_EMAIL', false), 'ttl_hours' => 0.25, 'contact' => env('PASSWORD_RESET_CONTACT', 'IT-SOFT'), 'route' => 'auth.password-reset.show']`.
  - `PasswordResetEmailHandler::accountFor(CmApprToken|string $subject): ?array{user: MstUsers, employee: HmEmpData}` — public; `null` unless the user exists, `mu_active === 'Y'`, `HmEmpData::find(mu_hmemd_sys_id)` exists (global scope = active) and `hmemd_work_email` is filled. Task 4 uses it to decide who qualifies.
  - `stillOpen()`: refuse (`'This link is no longer valid. Request a new one from the login page.'`) unless `accountFor()` is set and `strtolower(trim(work_email)) === $link->cmat_to_email`.
  - `buttons()` returns `[]` (so `email-approval.decide` refuses it as "not available"). `perform()` throws `LogicException`. `summary()` returns `auth::password-reset.summary` (employee name + user ID). `mail()` returns `EmailApprovalMail('Reset your password', <intro: link valid 15 minutes, ignore if you did not ask>, ['User ID' => …], 'Set a new password')`. `lifetimeHours/enabled/contact` read the config entry as the Finance handler does.
  - `PasswordResetLinkMail(CmApprToken $link, EmailApprovalMail $mail, string $url, string $contact)` — same queue/encryption/`afterCommit` shape as `Modules/Core/app/Mail/EmailApprovalRequested.php`, its own view.

- [ ] **Step 1: Write the failing tests** (beforeEach: `Mail::fake()`, `config(['core.email_approval.enabled' => true, 'core.email_approval.handlers' => ['auth.password-reset' => [...the entry above with 'enabled' => true]]])`)

```php
it('is waiting while the account still has the work email the link went to', function () {
    $user = resetAccount();
    $link = coreLink(1, ['cmat_handler' => 'auth.password-reset', 'cmat_subject_id' => $user->user_id, 'cmat_to_email' => 'worker@mutugading.test']);
    expect(app(PasswordResetEmailHandler::class)->stillOpen($link))->toBeNull();
});

it('refuses once the work email changed or was cleared', function (?string $email) {
    $user = resetAccount();
    $link = coreLink(1, ['cmat_handler' => 'auth.password-reset', 'cmat_subject_id' => $user->user_id, 'cmat_to_email' => 'worker@mutugading.test']);
    HmEmpData::query()->whereKey($user->mu_hmemd_sys_id)->update(['hmemd_work_email' => $email]);
    expect(app(PasswordResetEmailHandler::class)->stillOpen($link))->not->toBeNull();
})->with(['other@mutugading.test', null]);

it('refuses a deactivated account', function () { /* mu_active = 'N' → stillOpen not null */ });

it('cannot be decided through the generic approval page', function () {
    // issueUrl a link, then POST email-approval.decide with decision 'reset' → session error 'That choice is not available.'
});
```

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Auth/PasswordResetHandlerTest.php` → FAIL.
- [ ] **Step 3: Implement** the handler, config, mail + views, provider registration.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** — `feat(auth): add the password reset email handler, off by default`

---

### Task 4: Auth — "Forgot password?" request page

**Files:**
- Create: `Modules/Auth/app/Services/PasswordReset/PasswordResetRequestService.php`
- Create: `Modules/Auth/app/Livewire/ForgotPassword.php`
- Create: `Modules/Auth/resources/views/livewire/forgot-password.blade.php`
- Modify: `Modules/Auth/routes/web.php`
- Modify: `Modules/Auth/resources/views/livewire/login.blade.php` (link under the password field)
- Test: `tests/Feature/Auth/ForgotPasswordRequestTest.php`

**Interfaces:**
- Consumes: Task 1 `issueUrl()`; Task 3 `accountFor()`, `PasswordResetLinkMail`, `KEY`.
- Produces:
  - Route `GET /account/forgot-password` → `ForgotPassword::class`, middleware `guest`, name `auth.password-reset.request`. (Fortify already owns `/forgot-password` and `password.request`.) Component `mount()` aborts 404 unless production mode + enabled; layout `core::components.layouts.auth` like `Login`.
  - `PasswordResetRequestService::request(string $userId, string $ip): ?string` — `null` = show the standard message; string = the wait message. Order: limits first (keys `pwreset:acct2m:{lc}`, `pwreset:acct1h:{lc}`, `pwreset:ip:{ip}`; `{lc}` = `strtolower(trim($userId))`), on any limit return `"Please wait {n} seconds before requesting another link."` for the 2-minute key or `"Please wait before requesting another link."` for the others; otherwise `hit` all three, then if `accountFor(trim($userId))` qualifies: `issueUrl()` + `Mail::to(email)->queue(new PasswordResetLinkMail(...))`. Wrap the issue+mail in `try/catch (Throwable)` → `report()`, still return `null`.
  - Component: `#[Rule('required|string|max:50')] public string $userId`; `submit()` sets `public ?string $status` (standard message) or adds error `userId` with the wait message; clears `$userId` on success.
  - Login link: `<a href="{{ route('auth.password-reset.request') }}">Forgot password?</a>` wrapped in `@if (app(EmailApprovalService::class)->enabled('auth.password-reset'))` — Login is production-only already.

- [ ] **Step 1: Write the failing tests** (beforeEach: `Mail::fake()`, `Cache::flush()` so rate-limit counters start at zero, `config(['auth_mode.mode' => 'production', 'core.email_approval.enabled' => true, 'core.email_approval.handlers' => [...the Task 3 entry with 'enabled' => true]])`)

```php
const SENT = 'If this account has a work email, a reset link has been sent. It is valid for 15 minutes. If you do not receive it, contact IT Support.';

it('mails one link to the work email of a blocked but active account', function () {
    $user = resetAccount(); // mu_tot_wrong_pwd = 3
    Livewire::test(ForgotPassword::class)->set('userId', ' '.strtoupper($user->user_id).' ')->call('submit')->assertSee(SENT);
    Mail::assertQueued(PasswordResetLinkMail::class, fn ($m) => $m->hasTo('worker@mutugading.test')
        && str_contains($m->url, '/account/reset-password'));
    Mail::assertQueuedCount(1);
});

it('sends nothing and says the same for every non-qualifying account', function (Closure $setup) {
    $userId = $setup();
    Livewire::test(ForgotPassword::class)->set('userId', $userId)->call('submit')->assertSee(SENT);
    Mail::assertNothingQueued();
})->with([
    'unknown id' => fn () => fn () => 'nobody',
    'no work email' => fn () => fn () => resetAccount([], ['hmemd_work_email' => null])->user_id,
    'inactive account' => fn () => fn () => resetAccount(['mu_active' => 'N'])->user_id,
    'inactive employee' => fn () => fn () => resetAccount([], ['hmemd_sts_data' => 1])->user_id,
]);

it('refuses a second request within 2 minutes, in any letter case', function () { /* submit lower, then UPPER → assertHasErrors('userId'), error contains 'seconds'; 1 mail */ });
it('refuses a fourth request within the hour', function () { /* travel(3)->minutes() between; 3 mails, 4th errors */ });
it('caps one IP at 10 requests an hour across accounts', function () { /* 10 different unknown ids ok, 11th errors */ });
it('cancels the earlier link when a newer one is requested', function () { /* 2 requests 3 min apart → first CmApprToken Cancelled */ });
it('is a 404 while the handler is switched off', function () { /* handler enabled false → get(route) assertNotFound */ });
```

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Auth/ForgotPasswordRequestTest.php` → FAIL.
- [ ] **Step 3: Implement** the service, component, view, route, login link.
- [ ] **Step 4: Run** → PASS.
- [ ] **Step 5: Commit** — `feat(auth): let a user request a password reset link by user ID`

---

### Task 5: Auth — reset page

**Files:**
- Create: `Modules/Auth/app/Http/Controllers/PasswordResetController.php`
- Create: `Modules/Auth/resources/views/password-reset/show.blade.php`, `done.blade.php` (both `@extends('core::email-approval.layout')`, `$title = 'Reset password'`)
- Modify: `Modules/Auth/routes/web.php`
- Test: `tests/Feature/Auth/PasswordResetPageTest.php`

**Interfaces:**
- Consumes: Task 1 `complete()`; Task 2 `AccountPasswordService::set()`; Task 3 handler `summary()`.
- Produces routes (no `guest` middleware; a 404 guard as in Task 4):
  - `GET /account/reset-password` `signed` → `show`, name `auth.password-reset.show`. `resolve` → `open` → on refusal render `core::email-approval.unavailable` (same not-valid text as `EmailApprovalController`); else form with summary, hidden `token`, `password`, `password_confirmation`.
  - `POST /account/reset-password` `throttle:30,1` → `store`, name `auth.password-reset.store`. Validate `token` + Global password rules; `complete($link, 'reset', ip, ua, fn (CmApprToken $fresh) => $accounts->set(MstUsers::query()->findOrFail($fresh->cmat_subject_id), $password, 'email reset'))`. `ValidationException` → `back()->withErrors(['password' => first message])`; other `Throwable` → `report()` + `back()->withErrors(['password' => 'Something went wrong. Your password was not changed - please try again.'])`. Never `withInput()`. Success → redirect `auth.password-reset.done` with flash `reset => true`.
  - `GET /account/reset-password/done` → `done`, name `auth.password-reset.done`: "Password changed, you can log in now" + `href` to `route('login')` when flashed; otherwise a neutral "nothing to show" like Core's done page.

- [ ] **Step 1: Write the failing tests** (helper: `resetLinkFor(MstUsers $u): array{link, url, token}` via `svc()->issueUrl(...)` + `tokenFrom()`)

```php
it('shows the name and user ID for a valid link', fn () => /* get(url) assertOk assertSee(employee name) assertSee(user_id) */);

it('sets the password, unblocks, and spends the link', function () {
    fakeEncrypt();
    $user = resetAccount();
    ['token' => $token, 'link' => $link] = resetLinkFor($user);
    $this->post(route('auth.password-reset.store'), ['token' => $token, 'password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
        ->assertRedirect(route('auth.password-reset.done'));
    expect(storedPassword($user->user_id))->toBe('ENC:NewPass123')
        ->and(MstUsers::query()->find($user->user_id)->mu_tot_wrong_pwd)->toBe(0)
        ->and($link->fresh()->cmat_status)->toBe(EmailApprovalStatusEnum::Decided);
    $this->post(route('auth.password-reset.store'), ['token' => $token, 'password' => 'Other1234', 'password_confirmation' => 'Other1234'])
        ->assertSessionHasErrors('password');
    expect(storedPassword($user->user_id))->toBe('ENC:NewPass123');
});

it('rejects a mismatched or too-short password and leaves the link live', fn () => /* 'short' and mismatch → errors; link still live */);
it('refuses a link older than 15 minutes', fn () => /* travel(16)->minutes(); get(url) assertSee('expired') */);
it('keeps the link usable when the password write fails', function () {
    // mock MasterUserService::encryptPassword to throw → session error 'was not changed'; link still live; then succeed with fakeEncrypt
});
it('never puts the password in the session', fn () => /* after a failed post, session()->getOldInput() lacks 'password' */);
```

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Auth/PasswordResetPageTest.php` → FAIL.
- [ ] **Step 3: Implement** controller, views, routes.
- [ ] **Step 4: Run all touched suites + lint**

Run: `php artisan test --parallel tests/Feature/Auth tests/Feature/Core/EmailApproval tests/Feature/Finance/SalesControl/EmailApprovalTest.php && vendor/bin/pint --dirty`
Expected: all PASS, pint clean.

- [ ] **Step 5: Commit** — `feat(auth): let a user set a new password from the emailed reset link`
