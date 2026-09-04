# tasks.md — Phase 1 task list

Dependency-ordered. Each task names what "done" means. Test numbers refer to `spec.md` §9.

`vendor/bin/pint --dirty` before every commit. Conventional Commits, scope `finance`.

---

## P0 — Verify (blocks everything)

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T01** | Run `verification.md` §1 checks 1–7 against production (read-only) and the test schema | — | Every "Result" line in `verification.md` §1 is filled in and dated. Checks 1, 3 and 4 have an explicit verdict against the assumption, not just raw output |
| **T01b** | Walk Indra through the reworked `minimum-price-control.sql` — `schema.md` §9 is the changelog: MGTHRIS, `SALES_CTL_*` / `SALES_MIN_*`, new column prefixes, the F1 grants, the new deploy order | — | Indra has reviewed and accepted it, or given corrections. Settle **Q10** (tablespace) with the DBA in the same pass |
| **T02** | Agree with the DBA who creates the tables — the Laravel migrations or section A (Q1) | T01, T01b | A written answer. If the DBA runs the DDL, the Laravel migrations are still written and still no-op |
| **T02b** | Apply the grants from `schema.md` §9.3 on the test schema, compile the package, run smoke test **G0** as MGTDAT | T02 | G0 passes and the package is `VALID`. A grant given through a role does not count — `verification.md` §1 check 5c is the proof |
| **T03** | Settle Q2 (approver roles and holders) and Q3 (is there an existing paper form) with Finance | — | Written answers. Q2 needs at least two named holders per approver role |

If T01 check 1 or 3 contradicts the assumption, stop and take it to Indra — those are package changes,
and building on top of a wrong divisor or a wrong parent-line column wastes P2–P4. Fold them into the
same rework as T02 rather than asking him to edit the file twice.

---

## P1 — Foundation

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T04** | Five migrations on `oracle_mgthris` (`schema.md` §6): the four `SALES_MIN_*` tables, plus one that registers the four `HM_MST_SEQUENCES` rows (`TM9`, seq type 0) on both drivers and creates the four `BEFORE INSERT` sys-id triggers on the Oracle branch only. `hasTable()` guard, `DISABLE_MIGRATIONS` key, CHECK constraints on the Oracle branch only. `down()` empty on the four table migrations with the reason in a comment; the sequence migration **does** reverse — drop triggers, delete the rows | T02 | `php artisan migrate` clean on SQLite and on the Oracle test schema; running it twice does nothing the second time and does not reset a counter; the result matches §9.1's DDL column for column |
| **T04b** | Add the `DB_MGTDAT_*` block to `.env.example` — it is missing, and this domain is the first thing that makes it non-optional for a new developer | — | A fresh clone documents all three Oracle connections |
| **T05** | Enums: `MinPriceStatusEnum`, `MinPriceScopeLevelEnum`, `ApprovalRequestStatusEnum`, `ApprovalControlTypeEnum`, `PriceCheckResultEnum` — each with `label()`, `badgeVariant()`, `options()` | — | Backed values match the Oracle CHECK constraints character for character |
| **T06** | Four models (`design.md` §2), all on `oracle_mgthris`. `SalesMinPriceCheckLog` throws on create/update/delete | T04, T05 | Test 23 green |
| **T07** | DTOs: `MinPriceData`, `MinPriceFilterData`, `MinPriceImportRowData`, `ApprovalRequestData`, `PriceExceptionLineData`, `PriceApprovalDocumentData`, `PricePreviewLineData` | T05 | Validation rules match `spec.md` §3.1 and §5.1 |
| **T08** | Repositories + interfaces + `RepositoryServiceProvider` bindings: min price, approval request, approval line, price check log, SO lookup | T06 | Repository tests green; `SalesOrderLookupRepository::inScopeTxnCodes()` reads `IM_VS_STATIC_VALUE` with the config fallback |
| **T09** | `nextSysId()` per writable repository — `SysIdHelper::generate($seqName, $nik, dateFormat: null)`, cast to int, no driver branch; `generateBatch()` for the line and import paths (`schema.md` §7.1) | T08 | Ids are unique under both drivers in a parallel test run, and are bare counters — a test asserts no `Ymd` prefix leaked in, which is what the `dateFormat: null` argument prevents |
| **T10** | `ApprovalRequestNumberGenerator`: prefix by ctrl type, year, 4-digit counter, retry on `UK01` | T08 | Tests 15, 16 green |
| **T11** | `PriceControlGatewayInterface`, `OraclePriceControlGateway`, `FakePriceControlGateway`, service-provider binding by environment (`design.md` §4) | T04 | The fake returns fixtures with no arithmetic. The real one has an integration test against the Oracle test schema, skipped when unreachable, proving both OUT-parameter calls and that a raise becomes `PriceControlUnavailableException` |
| **T12** | `SalesControlRolesAndPermissionsSeeder` + the roles from Q2 (`design.md` §9) | T03 | Seeder is idempotent; `FinanceDatabaseSeeder` calls it |
| **T13** | `finance_price_control` log channel; `sales_control` config block | — | Channel writes; nothing reads `env()` outside config |

---

## P2 — Minimum price master

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T14** | `MinPriceService`: create, edit draft, overlap check (`spec.md` §3.2), void | T08 | Tests 2, 3 green; the overlap message names the conflicting rule id and its period |
| **T15** | Supersede (`spec.md` §3.4): draft now, close the old row only at approval | T14 | Test 4 green, including that the old rule is untouched while the request is unsigned |
| **T16** | `MinPriceManager` page + view + partials; route, breadcrumb, sidebar entry | T14, T12 | List, filter, paginate, create, edit, supersede, void — all through the service |
| **T17** | `MinPriceImport` + `ImportMinPrice` job + `MinPriceExport` + `ExportMinPrice` job, `ReportStatusNotification` on both | T14 | Tests 5, 6 green; every imported row lands `DRAFT`; a bad file reports every failing row with its number |

---

## P3 — Requests and documents

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T18** | `ApprovalRequestService`: the full state machine (`spec.md` §2.1), `assertEditable()`, the transition method as the only writer of `SCAR_STATUS` | T10 | Tests 7–9, 11–14 green |
| **T19** | Revision flow (`spec.md` §2.3) | T18 | Test 10 green, including the `UK01` collision path |
| **T20** | `ApprovalDocumentService`: dompdf render, print bookkeeping, upload + SHA-256 + `minio_private`, `temporaryUrl()` download, hash re-verify action | T18, T03 | Test 11 green; an approval with no scan is refused in PHP and by `C03` |
| **T21** | PDF templates for both forms (`resources/views/pdf/sales-control/`) | T20, T03 | Finance has seen a printed sample and said it is signable |
| **T22** | `MinPriceApprovalManager` page + view + route + breadcrumb: print, upload, approve, reject, void | T18, T20 | A batch of drafts goes draft → printed → uploaded → approved → live, end to end |

---

## P4 — Preview and exceptions

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T23** | `PricePreviewService`: SO lookup, scope check, skip rules, one `usdDivisor` per document, one `minPrice` per line, verdict per line, existing-exception join | T11, T08 | Tests 17, 18, 19 green |
| **T24** | `PriceCheckPreview` page + view + `_so-lines` partial + route + breadcrumb; header shows divisor, its date and its source | T23 | Out-of-scope TXN code, unsupported currency and missing FX rate each produce the message from `spec.md` §8 and check nothing |
| **T25** | `PriceExceptionService` + `PriceExceptionRequest` page: tick `UNDER` lines only, reason required, snapshot rate and qty from the SO | T23, T18 | Tests 20, 21 green; there is no price input on the form |
| **T26** | `PriceExceptionApproval` page: upload, BOD fields, approve, reject | T20, T25 | Approver ≠ requester is asserted in the service, not only by permissions (test 13) |
| **T27** | **Agreement check:** on the Oracle test schema, run `P_VALIDATE_SO` on a sample of real documents and compare its log rows against the preview's verdicts, line for line | T23, T01 | A written result for at least 10 documents covering USD and IDR, a `PASS_NORULE` case, an `OVERRIDE` case and an STA `INHERIT` case. Any disagreement is a bug in the preview, never a reason to change the package |

T27 is the phase's real exit criterion. Everything else in P4 can look right and still be wrong.

---

## P5 — Reports

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T28** | Active rules report + export (`spec.md` §7.1) | T16 | Figures match the master page for the same filters |
| **T29** | Exceptions granted report + export (`spec.md` §7.2) | T26 | Includes revision, print count and a working attachment link |
| **T30** | Rejections report + export (`spec.md` §7.3), deduplicated per line with an attempt count | T04 | Test 22 green |
| **T31** | Gross-vs-net monitoring + overlapping-approved-rules panel (`spec.md` §7.4) | T30 | Labelled on the page as monitoring, not as a control |

---

## P6 — UAT and cut-over

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T32** | Sidebar entries for all six pages; permissions checked as an unprivileged user | T16, T22, T24, T25, T26 | Test 24 green; nothing shows in the sidebar that the user cannot open |
| **T33** | UAT with Finance and Marketing on the test schema | T27, T31 | Both sign off on a full cycle: rule drafted → approved → SO checked → exception requested → signed → approved |
| **T34** | Production release: sections A–D + F, app deploy, roles assigned to the people from Q2 | T33 | `verification.md` §1 check 5 passes on production |
| **T35** | Seed the real minimum-price master and take it through approval **in the app** | T34, Q6 | Finance confirms the live rule list is what they intend to enforce |

---

## P7 — The trigger (separate PR, separate approval)

| # | Task | Depends on | Done when |
|---|---|---|---|
| **T36** | Deploy `minimum-price-control.sql` section E on production | T35 | An SO below floor is blocked with 1012110; an SO with an approved exception passes; `SALES_MIN_PRICE_CHECK_LOG` has a row per checked line |
| **T37** | Watch for a week: daily read of the rejection report, an agreed rollback (`DROP TRIGGER`) with a named owner | T36 | No unexplained blocks; Marketing knows where the exception page is |

T36 does not go out in the same release as anything else, and not on a Friday.
