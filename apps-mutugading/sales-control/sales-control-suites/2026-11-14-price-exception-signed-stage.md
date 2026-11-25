# Price Exception Signed Stage Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give price exceptions (MINPRICE, `MPO-`) the same signature step that PR #361 gave price lists. B gets the boss's signature, on paper or by email, and the request becomes SIGNED. Then C approves or rejects it in the app or by email, and only then does it become APPROVED. The exception approvals screen gets the price list screen's split actions and labelled buttons.

**Architecture:** One new method, `ApprovalControlTypeEnum::requiresSignature()`, is true for PriceList and MinPrice. Every place #361 wrote `=== ApprovalControlTypeEnum::PriceList` to mean "has a signed stage" switches to this method. The schema needs no change: `SIGNED`, `scar_sign_uid`, `scar_sign_dt` and `scap_role` already exist. A new migration adds the permission and role, and moves the menu gate.

**Tech Stack:** Laravel 12, Livewire 3, Pest 4 (SQLite), Oracle `oracle_mgthris`, Core email approval.

**Spec:** PR #361 (`6f7ab34e`), plus `.docs-me/superpowers/plans/2026-11-12-min-price-two-stage-approval.md`. This plan applies that design to MINPRICE.

## Global Constraints

- After this change MINPRICE goes PRINTED → SIGNED → APPROVED. PRINTED → APPROVED is refused for both types.
- C may not be the requester (`scar_cr_uid`) or the person who got the signature (`scar_sign_uid`). The requester and the signer may be the same person.
- A SIGNED request can only become Approved or Rejected. It cannot be revised, cancelled or voided.
- Before signing, the signer may reject (the boss refused). After signing, only C may reject.
- Signing (on screen or by email) cancels any approval link issued before it.
- New permission `finance-price_exception-sign`, new role `Sales Exception - Signer` = `finance-sales_control-dashboard-view`, `finance-price_exception-view`, `finance-price_exception-print`, `finance-price_exception-sign`.
- `finance-price_exception-print` is removed from `Sales Exception - Requester` and `Sales Exception - Approver`. Only the signer prints.
- `finance-price_exception-approve` now covers only C's decisions: approve, reject a signed request, and void.
- The approvals page route and its menu row are gated on `finance-price_exception-view`. Each button checks its own permission.
- Never edit deployed migrations. Font Awesome 5 icon names only. No `wire:navigate`.
- Tests: `php artisan test --parallel tests/Feature/Finance/SalesControl`.

## Review Focus

1. A MINPRICE request is already APPROVED or PRINTED when this deploys. APPROVED stays as it is. PRINTED now needs signing first, and any approval link already sent must stop working (`stillOpen` now requires SIGNED). Test in Task 4.
2. A MINPRICE request has no lines left (every line voided or removed). Sending it for signature, signing it or approving it is refused. Test in Task 2.
3. An exception id is sent to the price list screen, or a price list id to the exception screen → 404. Test in Task 5.
4. The signer opens the exception approvals page → Approve and Reject are hidden on SIGNED rows, and calling `approveSigned` directly is refused by the service. Test in Task 5.
5. A signature link for an exception: the summary and the stored PDF list the SO lines, not an empty rules table. Test in Task 4.

---

### Task 1: The state machine treats MINPRICE like a price list

**Files:**
- Modify: `Modules/Finance/app/Enums/Transaction/SalesControl/ApprovalControlTypeEnum.php`
- Modify: `Modules/Finance/app/Enums/Transaction/SalesControl/ApprovalRequestStatusEnum.php` (`allowedTransitionsFor`, `nextActionLabel`, `timelineSteps`, docblock diagram)
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/ApprovalRequestService.php` (`reject()`, `assertNotSelfApproval()`)
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/ApprovalDocumentService.php` (`attach()`, the guard against replacing the signed document)
- Modify: `tests/Feature/Finance/SalesControl/SignedStageTest.php` (flip `keeps printed → approved for MINPRICE`)
- Create: `tests/Feature/Finance/SalesControl/PriceExceptionSignedStageTest.php`

**Interfaces:**
- Produces: `ApprovalControlTypeEnum::requiresSignature(): bool`, which returns `in_array($this, [self::PriceList, self::MinPrice], true)`. Every later task uses this method and never compares against `PriceList` to mean "signed stage".

- [ ] **Step 1: Write failing tests** in `PriceExceptionSignedStageTest.php`, using `salesControlRequest()` (it already defaults to MINPRICE/PRINTED):
  - `it('lets an exception go printed → signed → approved but not printed → approved')`: `Status::Printed->canTransitionTo(Status::Signed, Type::MinPrice)` is true; `->canTransitionTo(Status::Approved, Type::MinPrice)` is false; `Status::Signed->canTransitionTo(Status::Approved, Type::MinPrice)` is true.
  - `it('refuses to revise, cancel or void a signed exception')`: `Status::Signed->allowedTransitionsFor(Type::MinPrice) === [Status::Approved, Status::Rejected]`.
  - `it('refuses rejection of a signed exception by its requester or signer')`: request in SIGNED with `scar_cr_uid='0001'`, `scar_sign_uid='0002'`; calling `ApprovalRequestService::reject()` as either NIK throws a `ValidationException` on `approver`.
  - `it('refuses to replace the signed scan of a signed exception')`: `ApprovalDocumentService::attach()` on SIGNED throws on `attachment`.
  - `it('draws four timeline steps for an exception')`: `count(Status::timelineSteps(Type::MinPrice)) === 4`.
- [ ] **Step 2:** Edit `SignedStageTest::keeps printed → approved for MINPRICE` so it asserts the opposite, and rename it `it('no longer lets MINPRICE skip the signature')`.
- [ ] **Step 3:** Run `php artisan test --parallel tests/Feature/Finance/SalesControl/PriceExceptionSignedStageTest.php tests/Feature/Finance/SalesControl/SignedStageTest.php` → FAIL.
- [ ] **Step 4:** Add `requiresSignature()`. In the four files above, replace each `=== ApprovalControlTypeEnum::PriceList` that guards the signed stage with `?->requiresSignature()`. Do not touch PriceList checks that are about rules (`rulesFor`, `hasLines`, `templateFor`). Change the `assertNotSelfApproval` message from "A price list cannot be … by the person who got it signed." to "A request cannot be …".
- [ ] **Step 5:** Run the same command → PASS. Then run the whole `tests/Feature/Finance/SalesControl` folder. Any MINPRICE test that approved straight from PRINTED is now expected to fail. List them; Task 2 and Task 4 fix them, not this task.
- [ ] **Step 6: Commit** `feat(finance): give price exceptions a signed stage before approval`

---

### Task 2: Price exception service — sign, edit BOD, approve, guard empty requests

**Files:**
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/SalesEmailApprovalService.php`: rename `assertHasRules()` → `assertHasContent()`. For PriceList it checks rules (current behaviour); for MinPrice it checks `ApprovalLineRepositoryInterface::forRequest()`, with the message `'This exception has no sales order lines left. Cancel the request instead.'`
- Modify: `Modules/Finance/app/Services/Master/SalesControl/MinPriceApprovalService.php`: move `updateBoardFields()` into `ApprovalRequestService` (it becomes generic: refuse unless `requiresSignature()` and SIGNED) and call it from there. Update the callers of `assertHasRules`.
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceSignatureEmailHandler.php`: only the `assertHasRules` call site changes here.
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/PriceExceptionService.php`
- Test: `tests/Feature/Finance/SalesControl/PriceExceptionSignedStageTest.php`

**Interfaces:**
- Consumes: `requiresSignature()` (Task 1).
- Produces:
  - `PriceExceptionService::print(int): StreamedResponse`: takes `findForUpdate` inside an `oracle_mgthris` transaction, then `assertHasContent`.
  - `PriceExceptionService::sign(int $sysId, PriceApprovalDocumentData $doc): SalesCtlApprRequest`: lock, `assertHasContent`, `requests->markSigned($sysId, $doc, auth NIK)`, `emails->cancelFor($sysId)`, all in one transaction.
  - `PriceExceptionService::updateBoardFields(int, PriceApprovalDocumentData): SalesCtlApprRequest`: delegates to `ApprovalRequestService::updateBoardFields()`.
  - `PriceExceptionService::approve(int $sysId, ?ApprovalActorData $actor = null): SalesCtlApprRequest`: reuses the request's own `scar_bod_*` fields, same as `MinPriceApprovalService::approve()`, with an `assertHasContent` callback.
  - `SalesEmailApprovalService::assertHasContent(SalesCtlApprRequest): void`

- [ ] **Step 1: Write failing tests:**
  - `it('signs a printed exception with the scan and board fields')`: attach a fake scan, call `sign()` → status SIGNED, `scar_sign_uid` = the acting NIK, BOD fields saved.
  - `it('refuses to sign an exception without a scan')` → `attachment` error.
  - `it('approves a signed exception and keeps its board fields')`: SIGNED with `scar_bod_doc_no='BOD-1'`, approve as NIK `0003` → APPROVED, `scar_bod_doc_no` still `'BOD-1'`.
  - `it('lets B correct the board fields of a signed exception')`.
  - `it('refuses to print, sign or approve an exception with no lines')`.
  - `it('cancels an approval link from the printed days when an exception is signed')`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement the signatures above. `MinPriceApprovalService` keeps its public API; only its internals now call the shared code.
- [ ] **Step 4:** Run `tests/Feature/Finance/SalesControl` → the new tests PASS and the price list tests still PASS.
- [ ] **Step 5: Commit** `feat(finance): sign and approve price exceptions in two steps`

---

### Task 3: Permission, role, print restriction, route and menu

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_14_100000_add_price_exception_sign_permission.php`. Copy the shape of `2026_11_12_110000_add_min_price_sign_permission.php` (create the permission, create the role with the permissions that exist, grant Super Admin), then add what `2026_11_12_130000_restrict_min_price_print_to_signer.php` does: revoke `finance-price_exception-print` from `Sales Exception - Requester` and `Sales Exception - Approver`. Finally, update the menu row `finance.sales-control.price-exception-approval` so it is gated on `finance-price_exception-view`, swapping the `model_has_permissions` row and keying on `code`, and call `MenuTreeService::clearAllCache()`. `down()` reverses all of it. Key: `add_price_exception_sign_permission`.
- Modify: `Modules/Finance/database/seeders/SalesControlRolesAndPermissionsSeeder.php`: add the permission (comment `// B: get the boss's signature, send it to C`) and the `Sales Exception - Signer` role, and remove print from the other two roles.
- Modify: `Modules/Finance/routes/web.php:138`: change `can:finance-price_exception-approve` → `can:finance-price_exception-view`.
- Test: `tests/Feature/Finance/SalesControl/PriceExceptionSignedStageTest.php`

- [ ] **Step 1: Write failing tests:**
  - `it('adds the exception signer role with print and sign')`: after running the seeder, the role has exactly the four permissions listed in Global Constraints, and Requester and Approver lack `finance-price_exception-print`.
  - `it('opens the exception approvals page to anyone who can view exceptions')`: a user with only `finance-price_exception-view` gets 200 on `route('dashboard.module-finance.transaction.sales-control.price-exception-approval')`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement. Check `php artisan migrate --pretend` against the dev DB only (the `DB_HOST` must be 192.168.0.7) before running it for real.
- [ ] **Step 4:** Run → PASS.
- [ ] **Step 5: Commit** `feat(finance): give price exception signers their own permission and role`

---

### Task 4: Email — signature link and approval link for exceptions

**Files:**
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/SalesEmailApprovalService.php`:
  - `sendForSignature()` accepts any type with `requiresSignature()`, and the signer row must have the same `scap_ctrl_type` as the request.
  - In `assertSendable()`, the "signed only" and "not the signer" checks apply when `requiresSignature()` is true. The `elseif Printed` branch becomes dead code; delete it.
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceSignatureEmailHandler.php`:
  - `summary()` passes `'lines' => $request->scar_ctrl_type->hasLines() ? lines : collect()`, and passes the same lines to `storeDecisionRecord()`.
  - `mail()` subject is `'Signature needed: '.$req_no.' ('.$type->label().')'`, and the intro reads `'A '.strtolower($type->label()).' is waiting for your signature.'`
  - The success notification says "Send it for approval." for both types.
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceEmailApprovalHandler.php`: `stillOpen()` opens on SIGNED when `requiresSignature()`. `approve()` takes the no-PDF branch for both types, calling `PriceExceptionService::approve()` for MinPrice and `MinPriceApprovalService::approve()` for PriceList. The generated-PDF branch stays for types without a signature step (none today; keep it for Overdue and CreditLimit).
- Modify: `Modules/Finance/app/Livewire/Master/SalesControl/ApproverManager.php` and its view, only if the SIGN role is limited to PriceList there (check first; `ApproverData` currently allows any type).
- Modify: `tests/Feature/Finance/SalesControl/EmailApprovalTest.php`: MINPRICE fixtures that approve by email now start in SIGNED with BOD fields set.
- Test: `tests/Feature/Finance/SalesControl/SignatureEmailTest.php` (add the exception cases here)

**Interfaces:**
- Consumes: `PriceExceptionService::approve(int, ?ApprovalActorData)`, `assertHasContent()` (Task 2).

- [ ] **Step 1: Write failing tests:**
  - `it('sends an exception signature link only to a SIGN-role MINPRICE row')`: a PriceList SIGN row is refused.
  - `it('signs an exception by email and stores a PDF listing its SO lines')`: status SIGNED, `scar_bod_doc_no` starts with `EML-MPO-`, and the rendered record contains the line's item code.
  - `it('closes an exception approval link sent while it was printed')`: `stillOpen()` returns the "already decided" message for a PRINTED MINPRICE.
  - `it('approves a signed exception by email without overwriting its board fields')`.
  - `it('refuses to send an exception approval link to the person who got it signed')`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Run the whole `tests/Feature/Finance/SalesControl` folder → PASS, including the MINPRICE email tests updated in this task.
- [ ] **Step 5: Commit** `feat(finance): ask for a price exception signature by email`

---

### Task 5: Exception approvals screen — split actions, labelled buttons, guards

**Files:**
- Modify: `Modules/Finance/app/Livewire/Transaction/SalesControl/PriceExceptionApproval.php`
- Modify: `Modules/Finance/resources/views/livewire/transaction/sales-control/price-exception-approval.blade.php`
- Create: `tests/Feature/Finance/SalesControl/PriceExceptionApprovalScreenTest.php`. Copy the structure of `MinPriceApprovalScreenTest.php`.

**Component**, mirroring `MinPriceApprovalManager` from #361 method for method:
- `#[Locked] public ?int $activeId`; `#[Url] public string $search`; `resetFilters()`; `search()` applied in `records()`.
- `private function exception(int $sysId): SalesCtlApprRequest`: `abort_unless(type === MinPrice, 404)`. Every action calls it.
- `print(int)` needs `finance-price_exception-print`.
- `openSign(int)` / `uploadScan()` / `sign()` / `saveBoardFields()` need `finance-price_exception-sign`. `openSign` on a SIGNED request opens the modal for editing BOD fields only (`$editingSigned`).
- `openApproveSigned(int)` / `approveSigned()` need `finance-price_exception-approve`, and `approveSigned` calls `emails->cancelFor()`.
- `openReject` / `reject` use `authorizeReject()`: sign permission on PRINTED, approve permission on SIGNED. `reject` calls `emails->cancelFor()`.
- `openSend(int $sysId, string $kind = 'approve')` / `sendForApproval()` need `finance-price_exception-sign`, and `$sendKind` picks `sendForSignature()` or `send()`. `approverOptions()` filters by `ApproverRoleEnum` and adds `unset($this->approverOptions)` when the modal opens.
- `cancelInvitations` needs `finance-price_exception-sign`.
- `openVoid` / `void` keep `finance-price_exception-approve`.
- Remove the old `openApproval()` / `approve()`.

**Row buttons.** Each is an `x-ui::button.index size="xs" icon-position="right"` with a visible label and a `title`, in this order (copy the price list blade's lines 128–207):

| Label | Variant | Icon | Shows when | Gate |
|---|---|---|---|---|
| Print | secondary-soft | fas fa-print | not terminal and not Approved | print |
| Email for signature | info-soft | fas fa-envelope | email on and status Draft/Printed | sign |
| Sign | success-soft | fas fa-file-signature | Printed | sign |
| Edit BOD | secondary-soft | fas fa-edit | Signed | sign |
| Email for approval | info-soft | fas fa-envelope | Signed and email on | sign |
| Approve | success-soft | fas fa-check | Signed and viewer not requester/signer | approve |
| Reject | danger-soft | fas fa-times | Printed + sign, or Signed + approve + not own | — |
| Void | danger-soft | fas fa-ban | Approved | approve |

- The Scan column becomes **Open** (`fas fa-file-pdf`) and **Verify** (`fas fa-fingerprint`) labelled buttons. This also fixes the FA6 `fa-xmark`, which draws nothing in FA5.
- Filters: a search box (`Request no, SO no, customer, BOD doc...`), the Status filter, Rows, and a **Reset** button. Add an ID column. Show the next action under the badge with `nextActionLabel(Type::MinPrice)`.
- Modals: Sign (scan + three BOD fields; heading `Sign Request` / `Edit Board Document`; primary button `Record Signature` / `Save Board Document`), Approve signed (a warning `x-ui::alert`: "Approving lets these sales order lines be approved in Orion at the rate on the form."; button `Approve`), and the Send modal with its label switching `Signer` / `Approver` and its button `Send Signature Link` / `Send Approval Link`.
- `Modules/Finance/resources/views/livewire/transaction/sales-control/price-exception-request.blade.php`: no change. Its Print button is already behind `@can('finance-price_exception-print')`, so it disappears for requesters once Task 3 moves the permission.

- [ ] **Step 1: Write failing tests:**
  - `it('gives a signer B\'s buttons only and an approver C\'s only')`: assert the labels with `assertSee('Sign')` / `assertDontSee('Approve')` and the reverse.
  - `it('shows Sign on printed and Approve on signed exceptions')`.
  - `it('hides Approve and Reject on a signed exception from the person who got it signed')`.
  - `it('404s when a price list id is passed to the exception screen')`: `Livewire::test(...)->call('openSign', $priceListId)->assertStatus(404)`.
  - `it('refuses to change the open request id from the browser')`: `->set('activeId', 2)` throws `CannotUpdateLockedPropertyException`.
  - `it('filters exceptions by the search box')`.
  - `it('lets B correct the board fields of a signed exception without touching its document')`.
- [ ] **Step 2:** Run → FAIL.
- [ ] **Step 3:** Implement the component, then the blade.
- [ ] **Step 4:** Run `php artisan test --parallel tests/Feature/Finance/SalesControl` → PASS. Run `vendor/bin/pint --dirty`.
- [ ] **Step 5:** Open the page in the browser on dev as a signer and as an approver. Check that every button shows its label and icon, and take screenshots for the PR.
- [ ] **Step 6: Commit** `feat(finance): split the price exception screen into sign and approve`

---

### After the tasks

- Search for other places that assume a MINPRICE request is "waiting" when it is PRINTED, for example `grep -rn "Printed" Modules/Finance/app/Livewire/Dashboard Modules/Finance/app/Services/Reports/SalesControl`. Any count of "awaiting approval" for MINPRICE should now count SIGNED, and "awaiting signature" should count PRINTED. Fix these inside Task 5's commit if they are found.
- Before deploy: tell Finance that PRINTED exceptions already in the system will need a signature, and that approval links already sent for them will stop working.
