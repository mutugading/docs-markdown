# Min Price Two-Stage Approval Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split a price list (PRICELIST) approval into a signature stage (B gets the boss's signature on paper or by a signature-only email → SIGNED) and an approval stage (any C approves or rejects by email or in the app → APPROVED).

**Architecture:** Add a `Signed` status to the existing request state machine, with the PRICELIST-only paths decided in `ApprovalRequestStatusEnum` and `ApprovalRequestService`. A second Core email handler, `finance.min-price-signature`, does the boss's Sign or Refuse. The existing `finance.min-price` handler keeps approving, but for PRICELIST it no longer generates a PDF. MINPRICE exception requests stay untouched.

**Tech Stack:** Laravel 12, Livewire 3, Pest 4 (SQLite), Oracle (`oracle_mgthris`), Core email approval.

**Spec:** `.docs-me/superpowers/specs/2026-11-12-min-price-two-stage-approval-design.md`

## Global Constraints

- Every behaviour change applies only when `scar_ctrl_type === ApprovalControlTypeEnum::PriceList`. MINPRICE keeps PRINTED → APPROVED, the one-click email with a generated PDF, and its current self-approval block.
- Status value `'SIGNED'`, enum case `Signed`, label `Signed`.
- C may not be the submitter (`scar_cr_uid`) or the person who got the signature (`scar_sign_uid`). A and B may be the same person.
- Any C may decide a SIGNED request. There is no lock to the approver B sent the email to.
- A SIGNED request leaves only through Approved or Rejected. It cannot be revised, cancelled or voided.
- Never edit `2026_09_04_100000_create_sales_ctl_appr_request_table.php` or `2026_09_14_100000_create_sales_ctl_approver_table.php`. Add a new migration.
- Email text: Font Awesome 5 icon names only. No `wire:navigate`.
- Tests: `php artisan test --parallel tests/Feature/Finance/SalesControl`.

## Review Focus

1. Two approval links are open (B sent to C1 and then to C2), and C1 approves → C2's link shows "already decided" and approves nothing. Test in Task 4.
2. A signature link is opened after B has already uploaded a scan and signed on paper → the link reports "already signed" and does nothing. Test in Task 3.
3. C approves in the app while an approval link is still open → that link is cancelled. Test in Task 5.
4. The boss opens a signature link for a request that has since been revised (the old row is now VOID) → the link refuses. This is covered by `stillOpen` requiring PRINTED, and tested in Task 3.
5. A signature link is sent to a row whose role is APPROVE, or an approval link to a row whose role is SIGN → the send is refused. Test in Task 3 and Task 4.

---

### Task 1: Schema: SIGNED status, signer column, approver role

**Files:**
- Create: `Modules/Finance/database/migrations/2026_11_12_100000_add_signed_stage_to_sales_ctl.php`
- Modify: `Modules/Finance/app/Enums/Transaction/SalesControl/ApprovalRequestStatusEnum.php`
- Create: `Modules/Finance/app/Enums/Master/SalesControl/ApproverRoleEnum.php`
- Modify: `SalesCtlApprRequest` (`$fillable` adds `scar_sign_uid`, `scar_sign_dt` as a datetime cast) and `SalesCtlApprover` (`$fillable`, cast `scap_role` → `ApproverRoleEnum`, and `scopeRole(Builder, ApproverRoleEnum)`)
- Modify: `tests/Helpers/Finance/SalesControlHelpers.php` (`salesControlApprover` defaults to `scap_role => 'APPROVE'`)
- Test: `tests/Feature/Finance/SalesControl/SignedStageTest.php`

**Interfaces:**
- Produces: `ApprovalRequestStatusEnum::Signed`; `ApprovalRequestStatusEnum::allowedTransitionsFor(ApprovalControlTypeEnum $type): array` and `canTransitionTo(self $target, ?ApprovalControlTypeEnum $type = null): bool`; `ApproverRoleEnum { Approve = 'APPROVE'; Sign = 'SIGN'; label() }`; the columns `scar_sign_uid` (varchar 20, nullable), `scar_sign_dt` (timestamp, nullable) and `scap_role` (varchar 8, not null, default 'APPROVE').

- [ ] **Step 1: Write the failing tests** in `SignedStageTest.php`:
  - `it('lets a price list go printed → signed → approved but not printed → approved')`: `Printed->canTransitionTo(Signed, PriceList)` is true, `Printed->canTransitionTo(Approved, PriceList)` is false, and `Signed->allowedTransitionsFor(PriceList)` equals `[Approved, Rejected]`.
  - `it('keeps printed → approved for MINPRICE')`: `Printed->canTransitionTo(Approved, MinPrice)` is true and `Printed->canTransitionTo(Signed, MinPrice)` is false.
  - `it('adds the signer columns and approver role')`: `Schema::connection('oracle_mgthris')->hasColumns(...)` for all three columns.
- [ ] **Step 2: Run them.** `php artisan test --parallel tests/Feature/Finance/SalesControl/SignedStageTest.php`. Expected: FAIL (no `Signed` case).
- [ ] **Step 3: Implement.**
  - Enum: label `Signed`, badge variant `'info'`. `nextActionLabel`: Printed → `'Get the signature'`, Signed → `'Send for approval'`.
  - Transitions. For PriceList: `Printed => [Printed, Signed, Rejected, Draft, Void]` and `Signed => [Approved, Rejected]`. For other types: today's table. The type-less `canTransitionTo` keeps today's table.
  - `timelineSteps(?ApprovalControlTypeEnum $type = null)` adds Signed for PriceList.
  - Migration: copy the shape of `2026_09_14_100200_add_appr_channel_to_sales_ctl_appr_request.php`, with key `sales_ctl_signed_stage`.
    - On Oracle only, drop and re-add the status CHECK constraint with `'SIGNED'` added. Look up its name in the 2026_09_04 migration, line 122.
    - Also widen the approver `SCAP_ROLE` with `CHECK (SCAP_ROLE IN ('APPROVE','SIGN'))`.
    - `down()` reverses both.
- [ ] **Step 4: Run them.** Expected: PASS, and the existing `tests/Feature/Finance/SalesControl` tests still PASS.
- [ ] **Step 5: Commit.** `feat(finance): add signed status and signer role for price lists`

### Task 2: Signing on paper, and the approval checks

**Files:**
- Modify: `Modules/Finance/app/Services/Transaction/SalesControl/ApprovalRequestService.php`
- Modify: `Modules/Finance/app/Services/Master/SalesControl/MinPriceApprovalService.php`
- Test: `tests/Feature/Finance/SalesControl/SignedStageTest.php`

**Interfaces:**
- Consumes: Task 1.
- Produces:
  - `ApprovalRequestService::markSigned(int $sysId, PriceApprovalDocumentData $document, string $signUid): SalesCtlApprRequest`. It requires `scar_attach_path` and all board fields (the same messages as `approve()`), moves to Signed, and writes `$document->toRow()` + `scar_sign_uid` + `scar_sign_dt`.
  - `MinPriceApprovalService::sign(int $requestSysId, PriceApprovalDocumentData $document): SalesCtlApprRequest`. It calls `assertHasRules`, then `markSigned` with the current user's NIK.
  - `MinPriceApprovalService::approve(int $requestSysId, ?ApprovalActorData $actor = null): SalesCtlApprRequest`. The document parameter is removed. It builds `PriceApprovalDocumentData` from the request's stored `scar_bod_*` fields and calls `requests->approve(...)` with the activate closure.
  - In `ApprovalRequestService::approve` and `reject`, `transition()` passes `$request->scar_ctrl_type` to `canTransitionTo`. For PriceList, `assertNotSelfApproval` also refuses an actor whose NIK equals `scar_sign_uid`, with the message `'A price list cannot be approved by the person who got it signed.'`
  - `revise()` and `cancel()` already refuse SIGNED through the transition table, so nothing to change there. Confirm it with a test.

- [ ] **Step 1: Write the failing tests:**
  - `it('signs a printed price list with the scan and board fields, rules stay drafts')`: after `sign()` the status is Signed, `scar_sign_uid` is the user's NIK, and the rule's `smp_status` is `'DRAFT'`.
  - `it('refuses to sign without a scan')`: throws ValidationException `'Upload the signed document first.'`.
  - `it('approves a signed price list, keeps the board fields and makes rules live')`: `scar_bod_doc_no` is unchanged and the rule is `'ACTIVE'`.
  - `it('refuses to approve a printed price list directly')`: throws `'Printed to Approved is not a move this workflow allows.'`.
  - `it('refuses approval by the person who got it signed')`.
  - `it('lets the submitter also be the signer')`: the request has `scar_cr_uid` equal to the signer, and `sign()` succeeds.
  - `it('refuses to revise or cancel a signed price list')`.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement** the interfaces above. Update the existing callers of `MinPriceApprovalService::approve($id, $document)`. Search with `grep -rn "->approve(" Modules/Finance/app/Livewire/Master`. The Livewire caller is redone in Task 5, so for now only make it compile.
- [ ] **Step 4: Run** `tests/Feature/Finance/SalesControl`. Expected: PASS. Any existing test that approves a PRINTED PRICELIST must now sign first. Update it, and do not loosen the rule.
- [ ] **Step 5: Commit.** `feat(finance): sign a price list before it can be approved`

### Task 3: Signature email handler

**Files:**
- Create: `Modules/Finance/app/Services/Transaction/SalesControl/MinPriceSignatureEmailHandler.php`
- Modify: `SalesEmailApprovalService.php` (add `sendForSignature`, filter by role)
- Modify: `Modules/Core/config/email_approval.php`: add `'finance.min-price-signature'`, with the same `enabled` env `SALES_CONTROL_EMAIL_APPROVAL`, the same TTL and the same contact
- Modify: `FinanceServiceProvider.php:42`: register the new key
- Test: `tests/Feature/Finance/SalesControl/SignatureEmailTest.php`

**Interfaces:**
- Consumes: Task 2's `ApprovalRequestService::markSigned`.
- Produces:
  - `MinPriceSignatureEmailHandler::KEY = 'finance.min-price-signature'`. It implements `EmailApprovalHandler`, with buttons `sign` ('Sign', primary, note optional) and `refuse` ('Refuse', danger, note required).
  - `SalesEmailApprovalService::sendForSignature(int $requestSysId, int $signerSysId): CmApprToken`.
- Behaviour:
  - `sendForSignature`:
    - The request must be PRICELIST and in DRAFT or PRINTED. If it is DRAFT, call `ApprovalRequestService::markPrinted` first, to freeze it.
    - The signer row must be active, of type PRICELIST, with role `Sign`.
    - Record the sender's NIK so the sign step can stamp it: store it as `scar_sign_uid` at send time. A later sign keeps it.
    - Notify the requester, "Signature request sent".
  - `stillOpen` is null only while the request is PRINTED. Otherwise it returns `'This request has already been signed or decided elsewhere. Nothing more is needed from you.'`
  - `perform('sign')`:
    - Call `ApprovalDocumentService::storeDecisionRecord`, passing a `$heading = 'Signed'` variant. Add an optional `string $heading = 'Approved'` parameter to it and to `email-approval-record.blade.php`.
    - Then `recordGeneratedAttachment`, then `markSigned` with the document `EML-{req_no}-r{revision}` (substr 60), now, and the signer's name (substr 120). Use `signUid` = the existing `scar_sign_uid`, or the signer's NIK if that is empty.
    - Then create a `SalesCtlApprDoc` row, as the approve path does.
    - Then notify the requester after commit, "Request signed".
  - `perform('refuse')`: `requests->reject(id, note, actor)`, then notify the requester after commit, "Signature refused".
  - `mail()`: heading `'Signature needed'`, subject `'Signature needed: {req_no} (Price list)'`.
  - `summary()`: reuse the `finance::sales-control.email-approval.summary` view.

- [ ] **Step 1: Write the failing tests**, modelled on `EmailApprovalTest.php`. Use its `beforeEach`, with the new key enabled the same way.
  - `it('sends a signature link only to a SIGN-role approver')`
  - `it('freezes a draft price list when the signature link is sent')`: the status is Printed.
  - `it('signs by email: stores a hashed PDF, fills board fields, status signed, rules still drafts')`
  - `it('rejects the request straight away when the boss refuses')`: the status is Rejected and the rules stay `'DRAFT'`.
  - `it('does nothing when the link is opened after the request was signed on paper')`: `stillOpen` is not null.
  - `it('cannot approve through a signature link')`: performing button `'approve'` throws InvalidArgumentException.
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement** the handler and the send.
- [ ] **Step 4: Run** `tests/Feature/Finance/SalesControl`. Expected: PASS.
- [ ] **Step 5: Commit.** `feat(finance): ask for a price list signature by email`

### Task 4: Approval email for a signed price list

**Files:**
- Modify: `MinPriceEmailApprovalHandler.php`
- Modify: `SalesEmailApprovalService.php` (`assertSendable`, `approverOptions` filtered by role `Approve`)
- Test: `tests/Feature/Finance/SalesControl/EmailApprovalTest.php`

**Interfaces:**
- Consumes: Task 2's `MinPriceApprovalService::approve(int, ?ApprovalActorData)`.
- Behaviour:
  - For PriceList:
    - `assertSendable` requires Signed. For MinPrice it still requires Printed. The message for PriceList is `'Only a signed price list can be sent for approval. Get it signed first.'`
    - `stillOpen` is open only on Signed.
    - `perform('approve')` calls `MinPriceApprovalService::approve($id, $actor)`: no PDF, no new attachment, no `SalesCtlApprDoc`.
    - `perform('reject')` is unchanged.
  - For MinPrice, the current code path stays as it is.
  - `assertSendable` refuses an approver row whose role is `Sign`.
  - `assertNotRequester` also refuses, for PriceList, an approver whose NIK equals `scar_sign_uid`.

- [ ] **Step 1: Write the failing tests:**
  - Update `it('approves a price list by email and makes its rules live')`: start the request at Signed, with the board fields set, and assert `scar_bod_doc_no` and `scar_attach_path` are unchanged after approval.
  - `it('refuses to send a printed price list for approval')`
  - `it('closes the second approver link once the first approves a price list')`: send to approver 1 and then approver 2, perform approve on link 1, and `stillOpen(link2)` is not null.
  - `it('refuses to send an approval link to a SIGN-role row')`
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.**
- [ ] **Step 4: Run** `tests/Feature/Finance/SalesControl`. Expected: PASS, including every MINPRICE email test unchanged.
- [ ] **Step 5: Commit.** `feat(finance): approve a signed price list by email without a new pdf`

### Task 5: Screens: approval manager, approver master, dashboard

**Files:**
- Modify: `Modules/Finance/app/Livewire/Master/SalesControl/MinPriceApprovalManager.php` and `.../views/livewire/master/sales-control/min-price-approval-manager.blade.php`
- Modify: the approver master Livewire component (the one rendering `approver-manager.blade.php`), plus `ApproverData` and `ApproverService`: add a Role select
- Modify: `Modules/Finance/app/Livewire/Dashboard/SalesControlDashboard.php:190-240`: count Signed as "waiting for approval", and keep Printed as "waiting for the signature"
- Modify: `SalesMinPrice.php:131`: `isLockedByRequest()` is true for Printed **or Signed**
- Test: `tests/Feature/Finance/SalesControl/SignedStageTest.php` and `MinPriceLockTest.php`

**Interfaces:**
- Consumes: Tasks 2 to 4.
- The manager's actions. Every permission stays on `finance-min_price-approve` except the existing print, submit and void.
  - `openSign(int)` / `sign()` on **Printed**: the current approve modal (scan + board fields), now calling `MinPriceApprovalService::sign`. Toast: `'Request {no} signed. Send it for approval.'`
  - `openSendSignature(int)` / `sendForSignature()` on **Draft or Printed**: approver options filtered to role `Sign`.
  - `openSend(int)` / `sendForApproval()` on **Signed**: approver options filtered to role `Approve`.
  - `approveSigned(int)` on **Signed**: a confirmation modal (no form) that calls `MinPriceApprovalService::approve($id)` and then `emails->cancelFor($id)`. Toast: `'Request {no} approved. Its rules are now live.'`
  - `openReject` / `reject` allowed on Printed **and** Signed. On Signed, also call `emails->cancelFor`.
  - Hide revise and cancel on Signed.

- [ ] **Step 1: Write the failing tests** (Livewire):
  - `it('shows Sign on printed and Approve on signed price lists')`
  - `it('cancels open approval links when approved in the app')`
  - `it('locks a draft rule on a signed price list')`: in `MinPriceLockTest`, `lockedRuleOn(Signed)` throws `'Revise that request to change it.'`. Then adjust the copy for Signed: `'Approve or reject that request first.'`
- [ ] **Step 2: Run them.** Expected: FAIL.
- [ ] **Step 3: Implement.** Use the UI components first (`x-ui::modal.confirmation` for approve), with FA5 icons.
- [ ] **Step 4: Run** `php artisan test --parallel tests/Feature/Finance/SalesControl`, then `vendor/bin/pint --dirty`. Expected: PASS and clean.
- [ ] **Step 5: Manual check** with the `run` skill: as A, submit. As B, send for signature (Mailpit), sign through the link, then send for approval. As C, approve in the app. The rule is live and the stored PDF is the signed one.
- [ ] **Step 6: Commit.** `feat(finance): split price list screens into sign and approve`
