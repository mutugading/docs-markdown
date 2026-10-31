# Forgot Password by Work Email — Design

## Why

Every forgotten password and every 3-strikes lockout is a phone call to IT-SOFT (Mike). Today Mike opens
Master User, clears the block, has the caller say their password, and either leaves it or resets it to
`123456789`. User 2875 is the latest; there are many. Goal: a user with a work email gets back in alone,
with no call and no guessable default password.

## Goals

- "Forgot password?" on the production login page → user enters their **user ID** → a single-use link
  goes to their work email.
- The link opens a guest page where they type a new password (twice). Saving it sets the password,
  resets the expiry, and clears the lockout.
- Built on the existing email token (`CM_APPR_TOKEN`, `EmailApprovalService`) for audit and single use.

## Non-goals

- Users with no work email. They keep calling IT-SOFT.
- Forcing a password change at login when the 3-month expiry passes (today it only notifies). Separate job.
- The Fortify `local`-mode reset (`app/Livewire/Auth/ForgotPassword.php`). Left as is.

## Anti-goals (failure even if it works)

- Changing the Approve/Reject guest page or how existing handlers behave.
- A default or shared password at any point.
- The form revealing whether a user ID exists or has an email.
- Someone using the form to flood a coworker's inbox.

## Decided by Mike

- **Recipient:** `HmEmpData.hmemd_work_email` of the employee linked to the `MstUsers` row
  (`mu_hmemd_sys_id`). Work email only, never the personal one.
- **Link lifetime:** 15 minutes.
- **Resend limits:** fixed, not exponential. One email per **2 minutes** and at most **3 per hour** per
  account, plus a per-IP cap.
- **Separate guest page** for the reset. The Approve/Reject page is untouched.
- **The user types the new password** on that page. No default, no temporary password.

## How it works

### Request page (guest)
- Route on the production login side, linked as "Forgot password?" from `Modules\Auth\Livewire\Login`
  (plain `href`, no `wire:navigate`).
- One field: user ID. On submit, always show the same message:
  *"If this account has a work email, a reset link has been sent. It is valid for 15 minutes. If you do
  not receive it, contact IT Support."*
  The same text appears for an unknown user ID, an inactive account, no linked employee, or no work email.
- If the account qualifies: `EmailApprovalService::issue('auth.password-reset', <user_id>, 'reset',
  [recipient])`. `issue()` already cancels the previous live link for the same recipient, so only the
  newest email works.
- Rate limits use Laravel `RateLimiter`. Per account: 1 per 120s, 3 per hour. Per IP: *(builder's call,
  suggested 10 per hour)*. When a limit is hit, show "Please wait before requesting another link" (for
  the 2-minute limit, the seconds left). Count the attempt even when the account doesn't qualify, so the
  timing and messages don't reveal anything.

### Email
- Uses Core's existing `EmailApprovalRequested` mail through the handler's `mail()`. Subject e.g. "Reset
  your password", button "Set a new password", text saying it expires in 15 minutes and to ignore it if
  they didn't ask.

### Reset page (guest, new)
- Signed URL, like the approval link, but its own route and view.
- **Core change:** today `EmailApprovalService::linkFor()` always targets `email-approval.show`. Let a
  handler name its own route (e.g. an optional method or config value; the default stays
  `email-approval.show`). This is the only change to Core's existing behaviour, and existing handlers
  must behave exactly as before.
- GET: resolve the token, `open()` it, and show the friendly expired/used page if it no longer works
  (reuse Core's existing ones). Otherwise show the employee's name, user ID, and the new password +
  confirm fields.
- POST (token in the form body, not the URL; throttled like `email-approval.decide`): validate with the
  same rules as User Settings' change password (`required|string|min:8|max:15|confirmed`), then
  complete the link through Core, which marks it DECIDED with IP and user agent. On success, show a
  "Password changed, you can log in now" page with a link to login.
- **How the password reaches the write (my call):** not through `decide()`/`perform()`, which have no
  slot for it and would put it in `cmat_note`. Add one new Core method instead, e.g.
  `EmailApprovalService::complete(CmApprToken $link, string $decision, ?string $ip, ?string $ua, callable $work)`:
  in the `oracle_mgthris` transaction it locks the link, re-checks it (`stillOpen()` included), runs
  `$work()`, and marks the link DECIDED. Existing methods and the handler interface are unchanged.
- **Closing the generic route (my call):** the handler's `buttons()` returns `[]`, so posting a reset
  token to `email-approval.decide` is refused as "not a real action button". `perform()` throws.
- **Email (my call):** its own small mailable and view in Auth with reset wording. Core's
  `EmailApprovalRequested` reads as an approval request and stays untouched. This means the reset sends its
  own mail rather than relying on `issue()`'s mail. Add an `issue(..., send: true)` flag (default true) or
  a sibling method that returns the link and its URL without mailing; the default path is unchanged.

### What saving does to the account
Same as `UserSetting::changePassword()`, minus the current-password check:
- `user_password_new = encryptPassword(new)` (`Pkg_Password_Apps.fEncrypt`)
- `mu_pwd_expired_dt = today + 3 months`
- `mu_tot_wrong_pwd = 0` (this clears the block)

Put this in one place (a service method in Auth) that both User Settings and the reset can call, rather
than copying it.

### The two-database catch
In production `MstUsers` is on `oracle_mgtapps`, and Core's decision transaction is on `oracle_mgthris`,
so the password write can't roll back with the token. **My call:** write the password inside the
`complete()` callback. If the password write fails, the exception rolls back the token and the link stays
usable for a retry. If the token commit fails after the password was written, the password has changed
but the link is still live, which is harmless because using it again only sets a password the owner
chooses. The docs' "write only on mgthris" rule gets a documented exception for this handler.

### Handler `auth.password-reset` (Auth module, registered from Auth's service provider)
- `stillOpen()`: the account still exists, is active, and still has the same work email the link was
  sent to. Otherwise refuse.
- `buttons()`: one `action` button, `reset`, no note. The reset page uses it internally; the generic
  page never shows it because the link points to the reset route.
- `lifetimeHours()`: 0.25 from config. `enabled()`, `contact()` ("IT-SOFT") come from
  `core.email_approval.handlers['auth.password-reset']`.
- **Off by default (my call)**, through an env flag like the sales control handler. The "Forgot password?"
  link only shows when `EmailApprovalService::enabled('auth.password-reset')` is true, so deploying changes
  nothing until it is switched on.
- Activity log: record "password reset by email" against the user, with no password data.

## Security checklist
- Raw token only in the email (Core already does this). Password never logged, flashed, or kept in session.
- Both guest pages escape all output.
- Same response for every request outcome. Rate limits per account and per IP.
- The reset POST is throttled.
- Only the `production` auth mode gets the link; `local` keeps Fortify's.

## Tests (Pest, SQLite)
- A request for a qualifying account sends one mail to the work email; unknown ID / no email / inactive
  sends nothing and shows the same message.
- The second request within 2 minutes is refused; a 4th within the hour is refused.
- A valid link plus matching passwords: the password is encrypted (mock `encryptPassword`), expiry is
  +3 months, wrong-try count is 0, and the link is DECIDED. A second use is refused.
- An expired link (>15 min) is refused; a newer request cancels the older link.
- Approve/Reject links still point to `email-approval.show`.

## Left to the builder
- Route names and paths, view layout (match Core's existing guest pages), the exact email wording, the
  per-IP number, and how the handler names its route.
