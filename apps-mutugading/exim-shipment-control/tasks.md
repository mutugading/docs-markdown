# tasks.md — Shipment Control & Exim Front Office

> Update `[TODO]` → `[DONE]` as each task lands, in the same commit.
> Commit: `feat(finance): [TXXX] <title>` · Every task ends with `vendor/bin/pint --dirty` +
> `php artisan test --parallel` green.
> Docs referenced by section number: `PRD.md`, `PRD EXIM.md`, `design.md`, `spec.md`, `schema.md`,
> `gap-analysis.md`, `data-migration.md`, `plan.md`, `open-questions.md`.

**P0–P5 are Shipment Control** (the migration). **P6–P9 are the Exim Front Office**
(`PRD EXIM.md`) — a separate delivery that can run in parallel from P2 onwards, once **T009A** and
**T011** have landed. Those two are the only hard couplings between the tracks.

## Progress

```
Shipment Control  P0–P4: [=============================] 29 / 30  (T029 is a night's work, not a commit)
P5 (reports): [===========================] 7 / 7  **complete (2026-09-09)**
Suite: **1,349 passing, 0 failing** (2026-09-15, after T050–T055, T057 and T058).

**Eleven of those were failing before T048 for a reason worth writing down.** Every `ship_*`, `hm_*` and
`cm_*` table is created with Oracle's UPPERCASE column names and every model addresses them in lower
case. The oci8 driver lower-cases what comes back; the **SQLite stand-in the tests run on did not** — so
a model re-read from the database came back holding `HMEMD_SYS_ID` with `hmemd_sys_id` null. `getKey()`
null, every relation empty, and `->fresh()` quietly handing back a user with no permissions at all. One
line fixes it for both drivers: `'options' => [PDO::ATTR_CASE => PDO::CASE_LOWER]` in `config/oracle.php`.
Worth knowing because the symptom never points at the cause: the failures read as permission bugs, in
three test files that had nothing to do with each other.
Exim Front Office: T037 is part done — its whole `S` block and five of its six named gating rows are
answered; what is left is policy nobody can measure, and none of it blocks P6 or P7 starting.
**T038 and T039 landed 2026-09-10**, so P6 has begun: the six master tables and the seventeen
transaction tables exist on both drivers. Only `ship_doc_type` is seeded, for reasons the seeder states
one by one, and the whole front-office schema is now in place for T040's models.
**P6 is complete (2026-09-12).** T040–T043 all landed: the twelve enums and twenty-three models, the
permissions and four roles, the twenty-three factories and `EximFixture`, the nine DTOs, and the six
master pages. The front office now has its foundation.
**P7 has begun.** T044's two classes are written and proved against every clause except the headline one:
the **ten issued PIBs are not in the repo** — their figures are on PDFs the import team holds. The
fixture format, the loader and the regression are ready and the directory is empty, so dropping the ten
files into `Modules/Finance/tests/Fixtures/Shipment/pib/` turns the acceptance green with no code
change. Getting those figures keyed is the one thing blocking T044 from being done; T045 onward are not
waiting on it. **T045 and T046 landed the same day.** T045's acceptance clause caught a real bug: the
exposure rule for a `MANUAL` item was wrong in three classes at once, and now lives on
`ShipBmSourceEnum` where they all read it. T046 left one measured gap — the ERP's KMK **decree number**
lives in `FM_EXCHANGE_RATE_KMK_MGT`, but the columns joining it to a week were never recorded, so an
ERP-sourced rate carries no decree. That blocks nothing in the estimate; T048's PIB print needs the join
measured first. **T048 landed 2026-09-14**: the checklist generates from the master, the two documents no
`applies_*` column can demand (the preference certificate and the SKB) are raised from the BL's own
declarations, and uploads keep every superseded version. It also found a live bug in T039's seed data —
`sdt_applies_transport_mode = 'SEA'` matched no shipment, because the enum has only `SEA_FCL` and
`SEA_LCL`, so the bill of lading was never asked for. **T049 landed the same day**: payment requests
walk `DRAFT → SENT → PAID` with no approver and no rejected state, the PIB amount is re-read from the
BL's estimate rather than trusted from the form, and the variance is computed from the stored budget and
stored with the payment.
**T050 landed 2026-09-15**, which finishes P7: booking an EMKL forwarder raises the provision itself —
there is no commitment document, because everything one would hold is on the BL — and an accrual nobody
ever billed is now reversed rather than left standing, which is the gap (B17) legacy had no answer for at
all. Storage stays out of the provision by design and is checked against the `DAYS` bands when its
invoice arrives. **F6 and D16 were answered the same day** and both confirmed the shipped defaults —
reversal in the current period, a warning rather than a block on a storage line off the tariff — with the
reasoning in `open-questions.md` §6. Both stay parameters, so either can be revisited without a release.
**P8 has begun: T051 landed the same day.** The BIM screen prints what the contract quoted beside a field
that starts empty, because a prefill is pulling one keystroke later; a template edit reaches the BIMs
that still inherit and have not been pulled into an SI, and says how many before it saves; a renegotiated
rate is superseded with its reason rather than overwritten. It also made `soh_appr_status = 3` the
repository's default rather than the picker's, so no future screen can offer an unapproved contract by
forgetting to ask. **T052 followed the same day**: the legacy upload maps what is unambiguous, leaves
everything else empty and flagged, and shows the original block beside the fields so the reviewer is
deciding rather than recalling. Its tests come in pairs — what maps, and what is deliberately not
guessed — because every clause on the second list is a reading somebody could later turn into a guess.
**T053 landed the same day**, so the export document itself now exists: an SI is raised from one BIM or
several, what the contracts disagree about is shown while the set is still being chosen rather than
thrown as an exception afterwards, and the terms are snapshotted onto the SI so a BIM edited next month
cannot move a shipment that already sailed. Moving the incoterm once cost lines are priced **holds** and
asks for a review rather than re-pricing quietly. It also found a real bug in T043's DTO: three
cross-field validation rules were written against bare field names and the screen holds those fields
under `form`, so the net weight was comparing against an absent key — the kind of failure that reads as a
rule working.
**T054 landed the same day.** The SI now carries cost from birth: one grid for freight, EMKL and the
demurrage line that turns up in December, with the baseline approved by confirming the document and
everything else approved one line at a time — recording the **offline** approver, because the person who
really approves does not use this application. Cancelling is a screen where each line is kept or written
off, and keeping one is what approves it, so a cancelled SI still produces the partial provision it owes.
`ses_cost_complete` is recomputed after every change and the `CLOSED` status follows it in both
directions.
**T055 landed the same day, which finishes P8's posting half.** An SI's approved cost becomes provisions
dated at the EIN — the baseline at invoice time, the demurrage line agreed in December into December's
own period — and `sec_spv_sys_id` is the single column that makes both true and stops anything being
taken twice. It found the one thing the design could not do as written: an insurance provision **cannot**
be raised with a blank vendor, because `spi_vendor_code` is part of the invoice's unique key, so the
premium is now computed and reported rather than posted until Finance names the insurer on the new
`INSURANCE_VENDOR_CODE` parameter (**F-INS2**).
**P9 is all but complete: T057 and T058 landed the same day.** The daily sweep tells Purchase, Import and
Finance the four things §11.6 asks for, and `ship_notify_log` is what makes it safe to run every morning
— keyed on the *state* rather than the date, so it is silent while nothing moves and speaks again when
the fact itself changes. It found one thing the spec asks for that the application cannot do: there is no
supervisor hierarchy anywhere to escalate to, so the audience is a parameter and the real question is
**O6**. T058 put the front office's own page in front of a user and built the four sheets the PRD names —
and the PIB variance split needed a column the schema did not have, because the recompute button destroys
the rate the budget was built on. Two legs are measured, the third is an honest residual.
**What is left in P9 is T056 alone**, and it is gated on production: the copy logic retires only once a
query shows no import provision is being created without a BL parent, which cannot be run from here.
All six sheets are built on T030's scaffolding, each run against the dev copy of production data, and
all six are open from the report index — `ShipReportEnum::available()` asks the router, so they lit up
as they landed. Two were checked against the legacy SQL still readable in `MST_PARAMS` (T032 diffed to
the row, T033 to 856 rows against 856); the other four had no legacy query to check against, and their
column lists are readings recorded as Q18, Q18b and Q18c.
T030 and T031 landed on 2026-09-09: the shared filter trait, the queued-job-and-link wiring, the
identifiers-as-text binder and the report index page, and the first sheet on top of them — 2,261 rows in
2.2 seconds against the dev copy of production data. T032–T036 are now a query and a column list each.
The Bill and Pending Bill queries are still readable in `MST_PARAMS` on MGTAPPS
(`SHP_EXPORT_BILL_REPORT_SQL`, `SHP_IMPORT_BILL_REPORT_SQL`, `SHP_PENDING_BILL_REPORT_SQL`) — the other
three reports' legacy SQL lived in PHP and did not come across, so their column lists are a reading
(Q18). T032 followed the readable one and lists its two deliberate differences from it.
**P0 is complete. T001 was answered on 2026-09-10** — Q1–Q9, Q14, Q16, Q17, F-INS1 and the V9 waiver
are all in `open-questions.md` §6 with the decision and what moved in the code. Four answers changed
behaviour rather than confirming it: the PO's own account now beats every configured posting rule (Q4,
step 0 of the resolver), export and import are **separate permissions** (Q2), the Shipment masters left
the Finance role's grant (Q7), and legacy's bill-number cross-check against `ft_payment_header` is back
as a warning (Q5). Q16 regrouped the settlement draft by (vendor, invoice no.), and F-INS1 inverted the
insurance parameter — `INSURANCE_INLAND_TERMS = FOB,CFR,CNF` where it used to be
`INSURANCE_INTERNATIONAL_TERMS = CIF,CIP`. What T001 did **not** settle is the cut-over date, now Q6a.
**T004 landed the same day** with the account numbers verified against the ERP rather than transcribed.
**The voucher shapes were verified against production the same day, and both were wrong** — the one
check `spec.md` §7.2 says not to skip. Legacy posts **no tax at all on a provision** (970 vouchers, not
one PPN or PPh leg; the accrual credited at the base) and posts **both taxes on the settlement** (1,502
of 1,627), plus a vendor debit for PPh that is advanced. Both builders now match, the variance is
measured base-to-base as legacy's `sbd_diff` is, and `spec.md` §4.4 and §7.2 are corrected.
**A third break would have stopped the first confirm outright:** the ERP account checks defaulted to a
company code of `MGT`, which `FM_ACNT_COMP` has never held, so every account on every document read as
missing. The fixture agreed with the bug, which is why no test caught it.
P1 is complete: T010–T017 done. The provision is end to end — raise, price, submit, confirm, post,
amend, delete — and reachable from the Finance dashboard.
P2 is complete: T018–T023 done. The settlement is end to end and in front of a user — raise, price,
faktur, save, submit, confirm, post, pay, amend, delete — reachable from the Finance dashboard, and
confirming one closes every provision it finishes off. T023 added the four print sheets, so nothing
`design.md` §6 lists on either detail page is missing.
P3 is code-complete: T024–T028 done, T029 written but not run. The reconciliation dashboard is in front
of a user — one row per
shipment key, the counters, and the missing-voucher panel — and `finance:shipment:repair-vouchers` is
the write half of that panel: it clears, as a named operator, the slots the ERP cannot account for.
T026 has landed too: `finance:shipment:preflight` runs the nine legacy data-quality checks and writes
the report the cut-over ticket wants, and it has been run against production —
`preflight-2026-09-08.md` is that report, and after Q12 and Q15 it comes back with no RED finding.
The route keys were fixed first (2026-09-08): every route that opens a document is now
`…/provisions/{direction}/{transNo}`, because Q12's answer means the number alone names two documents on
1,092 migrated rows. T027 followed: `finance:shipment:backfill` is written and tested against a stand-in
of the real legacy schema **and rehearsed end to end on the dev server's copy of production data
(2026-09-08), with T028's verification green on all 27,000 rows**. T028 followed:
`finance:shipment:verify-backfill` runs the ten V-checks and the `ship_unique_guards` migration adds the
two constraints legacy only enforced in PHP. **What is left is production**, and `cutover-runbook.md` is the
document that runs it — every step in it has now been walked through on dev. T029 stays open until that
night happens. **All six of the answers it was waiting on came in on 2026-09-10** — T001, T004, F9,
F-INS1, Q16 and V9 — and what stands in front of it now is a date (Q6a), Finance renumbering one legacy
invoice on production (Q17), and re-running the account verification against production rather than the
dev copy. Q13 is open but does not block the backfill, which copies stored amounts.
**F9 is answered and seeded (2026-09-10):** `203001`, with the vendor code as its sub account, read off
the ERP's own settlement vouchers rather than asked for.
**F-INS1 is answered by the export team (2026-09-10):** `FOB`, `CFR` and `CNF` are the inland terms and
everything else — `CIF` and `CIP` included — is the international `INSURANCE`. The parameter changed
sense as well as name.
**Q14 is confirmed by Finance (2026-09-10):** the EMKL expense rule wins outright, which is what the
seeder's priorities already gave. Nothing to change.
**V9 is waived (2026-09-10),** not passed: the automated V-checks stand in its place and nobody outside
the project has opened a migrated document in both systems side by side.
**F-PAY1 is fixed too (2026-09-10).** The provision's payment voucher debited the accrual — or, for a
PIB, one expense leg for the whole duty — where legacy debits the **vendor's AP account per invoice**
(39 of 39) and **splits a PIB across its three duty accounts** (35 of 35). Debiting the accrual would
have relieved twice what the settlement relieves once. The builder now takes the resolved accounts
rather than reading them off the line, and the confirm pre-check asks about them first.
**Q12 and Q15 are answered (2026-09-08).** The unique key on both documents is **(direction, trans_no)**,
not the number alone — measured, not assumed: no legacy number is reused inside its own series, while 454
provision and 638 settlement numbers exist once in each. `SHPCTR` / `EXPCTR` are a cancelled project and
are not migrated. **The pre-flight is green against production.** One consequence followed and is done: a trans no. alone no
longer identifies a document, so the direction is now part of every document route.
Exim Front Office P6–P9: [======================= ] 19 / 22  (T038–T043, T045–T055, T057, T058; T037 and
T044 part done — T044 waits on ten PIB PDFs nobody has keyed, not on code. **T056 is the only one left,
and it is gated on a production query, not on code.**)
```

---

## P0 — Foundation

### [DONE] T001 — Settle the open decisions
**Refer:** `plan.md` §1, `open-questions.md` §1 · **Blocks:** everything
Answer D5–D10 and Q1–Q9 with Finance and the requester. Write the answers into the docs they affect
(`schema.md` for D6/Q1, `design.md` §5 for D7/D8, `spec.md` §5.3 for Q4), then move each answered row to
`open-questions.md` §6 with the date and who decided.
**Acceptance:** no `plan.md` §1 row and no `open-questions.md` §1 row left unanswered; the docs are
edited, not just the chat. The front-office blocks (S, D, F, O) are **not** in scope here — they are
T037.

**Landed 2026-09-10.** Q1–Q9 answered by Finance in one pass, with Q14, Q16, Q17, F-INS1 and V9
alongside them; every row is in `open-questions.md` §6 with the date and the decider, and the D-block
was already settled (D5, D6, D9, D10 as their documented defaults, D7 and D8 on 2026-09-02 — **D10 is
now reversed**, see below). Four answers were changes rather than confirmations, and each landed with
its own tests:

| Answer | What moved |
|---|---|
| **Q4** — the PO's account wins outright | `ShipAccountResolver` gained **step 0**, ahead of all six rule rungs; the old step 8 is gone. Import and `EXPENSE` only, because the PO carries an expense account and nothing else — as a bottom rung it could answer a PPN or accrual lookup, which was a latent bug. `spec.md` §5.3 rewritten |
| **Q2** — separate export / import permissions | `finance-shipment-export` / `-import` scope which documents a user may reach, not which actions they may take. One trait, `AppliesShipDirectionAuthorization`, applied on both lists, both input screens, both detail pages, the picker, the dashboard and the print controller. Reverses `plan.md` D10 |
| **Q7** — the masters get their own owner | `finance-shipment-master-manage` is out of the Finance role's grant; Super Admin keeps it so it can be given by name |
| **Q5** — keep legacy's bill-number check | `referencesCarryingBillNo()` on the ERP payment repository, surfaced per invoice tab as a **caution in its own list** — merging it into the blocking duplicates would have made a warning refuse a save |

Q16 regrouped the settlement draft by (vendor, invoice no.) so one forwarder invoice covering two
shipments is one settlement invoice, and F-INS1 inverted the insurance parameter's sense as well as its
name. Q1, Q3, Q8, Q9 and Q14 confirmed what was already shipped. **Q6 answered only half of itself** —
a full stop, legacy read-only, no parallel window — so the cut-over **date** is now `open-questions.md`
Q6a and still blocks T029, along with Q17's renumber on production and the account re-verification.

### [DONE] T002 — Master migrations (8 tables)
**Refer:** `schema.md` §10, §12 (files 1–8) · **Blocks:** T003
`ship_port` comes before `ship_tariff` — the tariff FKs it.
Blueprint migrations on `oracle_mgthris` with `migrationKey` + `migrationDisabled()`, audit columns,
indexes. Applies D6's answer to `ship_cost_type`.
**Acceptance:** `php artisan migrate` green on Oracle and on SQLite in-memory; `migrate:rollback` clean;
`DISABLE_MIGRATIONS=ship_tariff` skips exactly that one.
**Identifier audit (2026-09-02, after the fact).** Every identifier in all 23 migrations is now
lowercase and within Oracle 11g's 30-character cap — audited, not eyeballed. Two Oracle-only failures
came out of it, both invisible to CI because SQLite has no cap:
`->autoIncrement()` queues an **unnamed** primary key on Oracle whose generated name reaches 43
characters (`ship_provision_container_spk_sys_id_primary`), and the code-PK masters' `->primary()`
did the same at 36. Every constraint and index is now named by hand (`pk_`/`uq_`/`fk_`/`ix_`), and the
PK columns are plain `integer` + an explicit `primary()` — SQLite still auto-assigns, because an
integer primary key is a rowid alias there with or without `AUTOINCREMENT`. See `schema.md`
conventions.

**Landed 2026-09-02.** D6 applied as its documented default (`SHC_SYS_ID` + unique on code+direction).
Verified on SQLite: migrate green, rollback clean, `DISABLE_MIGRATIONS=ship_tariff` skips exactly that
one table. **Not yet run against Oracle** — that needs someone with DDL rights on MGTHRIS.
Check constraints in the advisory DDL (`CK_SHIP_PORT_MODE` and the two direction checks in T003) are
**not** in the Blueprint migrations: Blueprint cannot express them portably and CI is SQLite. The enums
enforce the same values in code; if the DBA wants them in the database they are a hand-applied add-on.

### [DONE] T003 — Transaction migrations (10 tables + legacy map) + triggers + views
**Refer:** `schema.md` §1–9, §12 (files 9–21) · **Blocks:** T004
Blueprint tables; a separate Oracle-only migration for the sequences + `BEFORE INSERT` triggers
(early-return on `sqlite`); the three Orion views. The two unique guards (file 21) are **not** created
yet — they wait for the backfill (file 22).
**Acceptance:** `migrate` green on both drivers; on Oracle an insert without a PK gets one from the
trigger; `SELECT * FROM v_ship_vendor` returns rows.
**Landed 2026-09-02.** 11 Blueprint tables, one migration registering the 15 primary-key sequences and
creating their triggers, and the three views as separate Oracle-only migrations. Verified on SQLite:
migrate green, rollback clean, all 61 existing tests still green. **The Oracle half is unverified** —
triggers and views need a run on MGTHRIS.
**The 15 sequences are `HM_MST_SEQUENCES` rows, not Oracle sequences** (corrected 2026-09-02 on the
requester's instruction). `PKG_HM_SEQUENCES` is table-driven and `SysIdHelper` is the same counter read
from PHP, so a native sequence would have been a second counter nobody reads. Each row is
`{TABLE}_{COLUMN}_SEQ`, `hmms_seq_type = 0`, `hmms_number_format = 'TM9'`, no prefix, no max value, and
the trigger passes `p_date_format => NULL` — a plain incrementing integer. **`TM9`, not `'0'`:** `'0'`
is a one-digit mask and `TO_CHAR(10, '0')` overflows to `#`, so a `'0'` format would break the moment a
table passed its ninth row. `TM9` is Oracle's text-minimum format, and `SysIdHelper` pads by the count
of `0` characters in the format, which makes `TM9` a padding length of zero on the PHP side. The rows are inserted on
**both** drivers (only the trigger is Oracle-only), so CI can hand out ids through
`SysIdHelper::generate($name, $user, null)`. Insertion skips a name that already exists, so a re-run
never resets a live counter, and `down()` deletes the 15 rows.
**Careful on SQLite:** `HM_MST_SEQUENCES` is declared in UPPERCASE and SQLite returns keys as declared,
while the oci8 driver lowercases them. So `HmMstSequences` model attributes read back **null** on
SQLite and `SysIdHelper` there silently falls back to `start_with = 0` and `fm0000`, returning `'0000'`
every call. Pre-existing, Core-wide, and not caused by this task — but T006's fixtures will hit it the
moment they take a key from the helper.
The three view definitions **were** checked against `ALL_TAB_COLUMNS` on MGTDAT, and one column in the
doc was wrong: the company bank account number is **`BAD_ACNT_NO`**, not `BAD_BANK_ACNT_NO`, which does
not exist. `shipment_control_schema.sql` is corrected.
Oracle identifiers cap at 30 characters, so the longer tables' **triggers** abbreviate
(`ship_provision_container` → `ship_prov_cont_sys_id_trg`); the full map is the `TABLES` constant in the
trigger migration. The **sequence names stay uppercase** — they are values in
`HM_MST_SEQUENCES.HMMS_SEQ_NAME`, matched literally by the package and by `SysIdHelper`, not Oracle
identifiers, and every existing row in that table is uppercase. `HMMS_SEQ_NAME` is `VARCHAR2(50)`, so
they are spelled out in full there even where the trigger abbreviates.

### [DONE] T004 — Master seeder
**Refer:** `data-migration.md` §7, `spec.md` §6 · **Blocks:** T012
`ShipmentControlMasterSeeder`: cost types, container types, **ports**, activities, tariff import from
`SHP_MASTER_COSTS` with shape inference (printed for review), a null `sht_port_code` and
`sht_uom = 'CONT'` on every row, facilities, posting accounts, parameters.
Shape values are the **new** vocabulary: legacy `smc_type = 'X'` seeds as **`RATE`**, and the old `FLAT`
name is not used at all (`schema.md` §10, `spec.md` §4.1). Parameters seed both groups, including
`FIX_BAND_BOUNDARY`.
**Acceptance:** every account number in `data-migration.md` §7 verified against production and signed off
(record who and when in the seeder docblock); seeder idempotent; tariff shape inference report attached
to the PR; the three port rows seeded exactly as `data-migration.md` §7 lists them (`040300` Tanjung
Priok default, `060100` Tanjung Emas, `050100` Soekarno-Hatta / `AIR`), and every remaining distinct
`sah_flex_1` value from the frequency query either mapped to one of them or waived in writing.

**Done (2026-09-10). 6 tests.** `ShipmentControlMasterSeeder` seeds the cost-type flag matrix, the five
container types, the three customs offices, the fifteen posting accounts, and all 28 parameters from
constants; then copies the activities, facilities and tariffs from `oracle_mgtapps`, skipping that half
rather than faking it where the legacy schema is unreachable, so it runs in CI too. Idempotent by
natural key — the test edits a seeded account and re-runs to prove it comes back.

**The accounts are verified now, not transcribed.** All twelve exist under company **`002`**; none
exists under `001` (`MGTDAT.FM_ACNT_COMP`, dev copy of production, 2026-09-10 — repeat the query on
production before the run). **F9 needed no decision in the end:** `203001` carries all 226 payable
credits across the ERP's 131 unposted settlement vouchers, and since it has 696 sub rows and none
without one, the payable posts with the vendor code as its sub account.

**Three things in `data-migration.md` §7 were wrong and are corrected there.** The legacy tables are
`SHP_MASTER` and `SHP_MASTER_COST`, singular. `smc_type` already holds `X` / `TIER` / `FIX`, so no shape
is inferred — only the `X` → `RATE` rename and the tier grouping, which gives 24 ladders, each a clean
1..n. And `smc_uom` already holds five units, so `sht_uom` is copied rather than set to `CONT`
everywhere. Three rows whose name and type disagree about being tiered are named in the seeder's report
instead of grouped quietly.

**Two seeded rows changed later the same day, both from answers rather than bugs.** F-PAY1 dropped the
`BILL` document discriminator from the `VENDOR` posting rule, because `203001` is on both sides of the
flow — the settlement credits it and the provision's advance debits it. And F-INS1 replaced
`INSURANCE_INTERNATIONAL_TERMS = CIF,CIP` with `INSURANCE_INLAND_TERMS = FOB,CFR,CNF`: still 28
parameters, one of them under a new key with the opposite meaning, so a database seeded before that day
carries a key nothing reads. Delete the old row when re-seeding an existing environment; there is none
in production yet.

### [DONE] T005 — Enums, models, permissions
**Refer:** `design.md` §2, §8, `spec.md` §2 · **Blocks:** T006
7 enums, 17 models (+3 view models) with connection, PK settings, casts, `$fillable`, `$searchable`,
relations, `Searchable` + `LogsActivityWithDescription`. Extend
`FinanceRolesAndPermissionsSeeder` with the `finance-shipment-*` permissions.
**Acceptance:** `ShipProvision::with('invoices.costs', 'containers', 'docs')->first()` works against a
factory-seeded row; `ShipStatusEnum` transition table matches `spec.md` §2; permissions seeded
idempotently.
**Landed 2026-09-02.** 7 enums, 22 models (11 transaction incl. `ShipLegacyMap`, 8 masters, 3 views),
the `StampsShipAudit` concern, the 19 `finance-shipment-*` permissions, and 12 Pest tests covering all
three acceptance criteria.

**Revised 2026-09-10 by T001's answers: 21 permissions, and one of them left the Finance role.** Q2
added `finance-shipment-export` and `finance-shipment-import` — a scope on the documents a user may
reach, enforced by `AppliesShipDirectionAuthorization` on every page that names a direction — and Q7
took `finance-shipment-master-manage` out of the Finance grant, leaving it with Super Admin so it can be
given to the master owner by name. `ShipDirectionPermissionTest` covers the split: a list narrowed, a
document in the other direction refused on the detail page, the edit screen and the print route, a blank
form opening on a direction the user can save, a save into the other direction refused, and a user
holding neither direction refused outright. The tree is built by a plain helper rather than factories, which are T006.
`ShipTariffUomEnum` is **not** here: `SHT_UOM` does not exist until T009A, and the enum lands with the
column so the two cannot disagree.
**Three corrections came out of building this**, each already written into the doc it affects:
1. **Migration columns must be declared lowercase** (`schema.md` conventions). Oracle folds unquoted
   identifiers, so lowercase still lands as `SPV_TRANS_NO` on MGTHRIS; SQLite does not fold and returns
   keys as declared, while oci8 lowercases them. The 19 uppercase migrations from T002/T003 made every
   model attribute read back **null under SQLite** — the entire CI suite — while being perfect on
   Oracle. All 19 converted.
2. **`$incrementing` stays true** (`schema.md`, `design.md` §2). `design.md` said false; with false
   Eloquent skips `insertGetId`, `getKey()` is null after `create()`, and every child written through a
   relation gets a null parent. Only the code-PK masters set it false.
3. **`FinanceRolesAndPermissionsSeeder` now uses the Auth module's `Permission`/`Role`**, not Spatie's.
   Spatie's follow the default connection, so the seeder threw `no such table: permissions` under test —
   which is why it had no test. `CiProjectRolesAndPermissionsSeeder` already carried that same comment.
**Known footgun, deliberately left:** `costType()` and `activity()` join on the code alone while their
masters are unique on (code, direction), so they can attach the wrong direction's row. Documented on
every affected model; T008 owns the `resolve($code, $direction)` repository method that services must
use instead, and `forDirection()` scopes exist on both masters already.

### [DONE] T006 — Factories + fixtures
**Refer:** `design.md` §9 · **Blocks:** T007
Factories for all transaction + master tables, plus a `ShipmentFixture` helper that builds a realistic
export provision (2 containers, 2 invoices, 6 lines) and an import PIB provision.
**Acceptance:** `php artisan test` can build both fixtures on SQLite in under a second.
**Landed 2026-09-02.** 19 factories in `Modules/Finance/database/factories/Shipment/` and
`ShipmentFixture` in `Modules/Finance/tests/Fixtures/Shipment/`. The fixture builds the export
provision (2 container types, 2 vendors' invoices in 2 currencies, 6 lines), the import PIB provision
(duty columns, no vendor, 14-character AJU) and `settlementFor($provision, $variancePct)`, which settles
every line and records the per-line variance. Both fixtures build in ~0.06s, asserted by a test rather
than assumed.
**`Model::factory()` needed wiring.** Laravel's guesser looks for `Database\Factories\{Model}Factory` in
the *application* namespace, which no module has. `Modules\Finance\Models\Concerns\ResolvesShipmentFactory`
points it at the module namespace — and it **includes** `HasFactory` rather than sitting beside it,
because both declare `newFactory()` and PHP treats that as a collision the class must resolve by hand. A
shipment model uses it *instead of* `HasFactory`, not as well as. Any later module wanting factories
needs the same three lines.
**One model changed:** `ShipPostingAccount`'s log identifier was `['spa_purpose', 'spa_main_acnt']`, and
`LogsActivityWithDescription` builds its identifier by string-casting the named attributes — `spa_purpose`
is enum-cast, so every insert threw. It is now `spa_main_acnt` alone; the purpose still shows through
`$logAttributes`. **Worth knowing generally: never name an enum-cast column in `$logIdentifier`.**
Factory states worth knowing about: `ShipCostTypeFactory::pib()/emkl()/shippingExport()`,
`ShipPortFactory::tanjungPriok()/tanjungEmas()/soekarnoHatta()` (the three real rows),
`ShipTariffFactory::jasindoTierSet()` (the three progressive bands T011 must price at 3,750,000, not
3,500,000), `ShipBillCostFactory::settling($provisionCost, $billed)` and
`ShipProvisionCostFactory::duty($bm, $ppn, $pph)`.

### [DONE] T007 — DTOs
**Refer:** `design.md` §3, `spec.md` §3 · **Blocks:** T008
Header / invoice / cost / container / doc DTOs for both documents, `Wireable`, with the conditional rule
sets from `spec.md` §3.1–3.2 and the blank-numeric normalisation rule.
**Acceptance:** Pest: each validation case passes valid data and rejects each required field; a blank
string in a numeric field does not throw.
**Landed 2026-09-02.** 8 DTOs + `Concerns/CalculatesLineAmounts`, 54 tests.
**Where the named cases live.** `spec.md` §3.2's cases are static rule methods on the DTO that owns the
data — `ShipProvisionData::headerRules()/voucherDateRules()`, `ShipProvisionInvoiceData::invoiceRules()`,
`ShipProvisionCostData::costRules()/dutyRules()/confirmRules()`, `ShipContainerData::containerRules()`,
`ShipDocData::docRules()`, `ShipBillData::headerRules()/paymentRules()/voucherDateRules()`,
`ShipBillCostData::costRules()/confirmRules()`. Composing them into `submit` and `confirm`, and the
collection-level parts ("at least one EDN", "invoice numbers distinct within the document"), belong to
the service — a DTO cannot see its siblings. T012/T014 own that.
**Cost-type flags travel in the payload.** `headerRules()` reads `requires_vendor`,
`requires_vendor_bank` and `has_payment_voucher` from the array passed in rather than loading the
master, so the rules stay pure. They default to the **stricter** reading, so a caller that forgets to
pass them gets a form that asks for too much rather than too little.
**`lc` is the USD figure**, not the local one — `fc` is the line currency, `lc` is USD, `cc` is the bank
currency. The names are legacy. This is worth reading twice before touching any amount code.
**Fixed while here:** `ShipmentFixture` had been computing `spc_lc_amount` as rupiah, contradicting
`spec.md` §4.2. It now prices its lines *through* `ShipProvisionCostData::withAmounts()`, so the fixture
and the calculator cannot disagree, and the T006 test asserts the corrected figures.
**Not built here:** `ShipCostCalculator` (T013) is still the service that owns the arithmetic; the trait
is the formula it will call, placed where both line DTOs share it so the four-point variance chain
cannot drift.

### [DONE] T008 — Repositories (own tables)
**Refer:** `design.md` §1, §4 · **Blocks:** T009
Interfaces + Eloquent implementations for provision, bill, tariff, masters, posting accounts,
parameters. Bound in `FinanceServiceProvider` (or a `RepositoryServiceProvider` if Finance gains one).
Includes `unbilledCosts(provisionId)` and `unbilledCostsForVendor(vendorCode)`.
**Acceptance:** Pest: `unbilledCosts` excludes a line that already has a bill line; search covers the
relation columns listed in `design.md` §2.
**Landed 2026-09-02.** 6 interfaces + 6 Eloquent implementations, bound in the existing
`RepositoryServiceProvider` (Finance already had one, and it is `DeferrableProvider`, so the six are
listed in `provides()` too). 22 tests.
**`ShipMasterRepository::costType($code, $direction)` closes the footgun T005 left open** — the
`costType()` / `activity()` relations join on the code alone while their masters are unique on
(code, direction). A direction-specific row beats the `BOTH` row; a code declared only for the other
direction returns null rather than the wrong row. Services must read flags through this, never the
relation.
**The ladders stay in the services, deliberately.** `ShipTariffRepository::lookup()` answers **one**
vendor+port step at a time and treats a null port code as "the rows declared for any port", not as a
wildcard — so `ShipTariffService` can tell "this vendor has no rate at this port" apart from "this
vendor has a general rate" and report the gap instead of silently pricing at the wrong customs office.
Likewise `ShipPostingAccountRepository::candidates($purpose)` returns every active row for a purpose
already ordered by `spa_priority`, and `ShipAccountResolver` walks the six steps over that one result
in PHP: the table is tens of rows, and one pass keeps "first match wins" readable in one place instead
of spread across six `where` chains.
**`forProvision()` matches both routes** — a settlement that names the provision in its header, and one
that reaches it only through `stc_spc_sys_id` on its lines, which is what the multi-provision merge
produces.
**Caching:** masters, posting accounts and parameters are cached for 10 minutes with an explicit
`flush()` that every write path calls, following `EloquentCiProjectMasterRepository`.
**Note for the seeder (T004):** `registerFacility()` implements legacy `checkFasilitas()` — an unknown
code is created rather than refused, but marked `shf_auto_registered` so the typos this forgiveness
lets in stay findable.

### [DONE] T009 — ERP read repositories
**Refer:** `design.md` §5, `spec.md` §7.1 · **Blocks:** T011
Sales docs (ESC/STA/EDN/EIN), purchase docs (PO all / valid / SAMPLE), vendor + vendor bank + NPWP/SKB,
company banks, account existence + `(main, sub, currency)` check, Faktur Pajak list, exchange rate.
**Acceptance:** against production (read-only) each method returns rows for a known document; every one
is behind an interface so tests can fake it.
**Landed 2026-09-02.** 6 interfaces (`Interfaces/Transaction/Shipment/Erp/`) + 6 implementations
(`Repositories/Erp/Shipment/`), bound and listed in `provides()`. **32 read-only checks run against
production, 0 failures** — every method returned rows for a real document (ESC `2026000318`, EDN
`2026000464` worth 3,898.80, EIN `2026000379`, PO `LPO-2026001732` with 1,800,000 remaining, FP
`08002500440017911`, USD→IDR 18,078 today). CI has 7 tests proving the bindings and that every method
can be faked without a database.
**Transaction codes confirmed on production:** ESC and STA are `ot_so_head.soh_txn_code`; EDN and EIN
are `ot_invoice_head.invh_txn_code` (6,134 / 8,486 / 9,067 / 7,873 rows).
**Two things the live check caught, both of which would have shipped as silent wrong answers:**
1. **`design.md` §5's "om_expense → supplier code" does not exist.** Every `AGENT` row has all 20 flex
   columns null, `exp_flex_01` holds a *postal address* where it is used at all, and only 14 of 123
   `AGENT` names match an `om_supplier` name exactly. The method now returns the expense row and no
   vendor; raised as **Q10**, and `ShipCommissionService` must not substitute a vendor silently.
2. **`combinationExists()` answered the wrong question for a blank sub-account.** Skipping the filter
   asks "does some sub-account work", which would pass a bad pair through to the detail insert — the
   exact failure spec.md §7.1 says this check exists to prevent. A blank sub now matches `NULL`
   explicitly. `exists()` keeps "null = any sub" because that is the account-existence check a
   provision line needs; the difference is documented on both.
**`remainingValue()` returns null, not 0,** when a PO is absent from the planning view: "not in the
view" and "nothing left" are different answers and 0 reads as an exhausted PO.
**Still unverified against Oracle:** `vendors()`, `customers()` and `companyBanks()` read the `v_ship_*`
views, which do not exist there until the T003 migrations are run. Their view bodies were checked
directly against `om_supplier` / `om_customer` / `fm_bank_acnt_detail` instead, so the column lists are
proved; re-run the check after the migration.
**D8 note:** the exchange rate is a repository, not a service, because D8 (copy `ExchangeRateService`
into Finance vs promote it to Core) is still open and a thin read interface commits to neither.

### [DONE] T009A — Additive front-office columns
**Refer:** `schema.md` §12 file 40, `PRD EXIM.md` §11 · **Blocks:** T037 (and nothing in P1–P5)
One migration adding, all nullable or defaulted: `SPV_SIF_SYS_ID`, `SPV_SES_SYS_ID`, `SPV_SOURCE_TYPE`,
`SPC_NO_TARIFF_MATCH`, `SPC_SEC_SYS_ID`, `SHC_IS_ADDITIONAL`, `SHC_REQUIRES_LINE_APPROVAL`, `SHT_UOM`
(default `CONT`), plus the `SPC_NO_TARIFF_MATCH` and `SPV_SIF/SES` indexes. The FK constraints to
`ship_import_file` / `ship_export_si` are **not** created here — those tables do not exist yet; they are
added in T039/T042 when they do.
**Deliberately released early**, during P1, so the two tracks stop touching each other. No data change,
no downtime.
**Acceptance:** `migrate` green on Oracle and SQLite; `migrate:rollback` clean; every existing Pest test
still green; a provision saved by the P1 code has null parents and `spv_source_type` null, and nothing
in P1–P5 reads any of the new columns.
**Landed 2026-09-02**, ahead of its P1 slot because **T011 needs `SHT_UOM`** — the quantity basis is
what makes one pricing service serve both modules, so the column has to exist before the service can
read it. Migration green on SQLite, rollback clean, all 184 tests still green, identifier audit still
passing. The four models carry the new columns (`sht_uom` cast to the new `ShipTariffUomEnum`,
`spc_no_tariff_match` / the two `shc_` flags to boolean).
**Not built here:** `ShipProvisionSourceTypeEnum`. `spv_source_type` stays a plain nullable string until
the front office fills it — nothing in P1–P5 writes it, and an enum with no writer is a guess about
values nobody has confirmed.

---

## P1 — Provision

### [DONE] T010 — ERP voucher posting (D7)
**Refer:** `design.md` §5, `spec.md` §7.2–7.3, `plan.md` §1 D7 · **Blocks:** T016
Promote (or copy, per D7) the journal + payment voucher repositories, their DTOs and
`ExchangeRateService`, then add the Shipment-specific payload builders.
**Acceptance:** Pest against SQLite stand-ins: period resolution failure raises a `DomainException`
naming the date; doc no. is `calYear+MM+4 digits`; JV refuses to post when DR ≠ CR; payment posts
header + one `fs_payment` + one `ft_payment_oth_acnt_detail` per line.
**Landed 2026-09-02. D7 settled as COPY into Finance** (see `plan.md` §1 — the requester chose the copy
over promoting production code to Core; the duplication is knowingly accepted and flagged in both
repositories' docblocks). D8 needed nothing: T009's `ShipExchangeRateRepositoryInterface` already
covers it.
Copied: the 5 ERP models (`Models/MgtDat/Shipment/`), both DTO pairs (`Data/Erp/Shipment/`), both
repositories (`Repositories/Erp/Shipment/`) and the posting orchestration, reduced to
`ShipVoucherService`. **17 tests, all 4 acceptance criteria covered**, against SQLite stand-ins in
`Modules/Finance/tests/Fixtures/Shipment/ErpStandIn.php` — those are test scaffolding, **not
migrations**: the ERP tables are Oracle-managed and this app must never create them.
**Two deliberate changes from the LcControl original:**
1. **`nextSeq()` falls back to `MAX(id) + 1` off Oracle.** The original reads
   `MGTDAT.TH_SYS_ID.NEXTVAL` unconditionally, which is why none of LcControl's posting path is
   testable. The Oracle branch is untouched; the fallback is test-only by construction.
2. **The DR = CR guard also checks the entered currency, when every line shares one.** The original
   balances on the USD base alone — and on a rupiah voucher that hides a real error: 16,000,000 and
   15,999,999 both round to USD 1,000.00 at 16,000, so a voucher a rupiah out of balance posts
   silently. There is a test for exactly that case. The base-only rule still applies to a genuinely
   mixed-currency voucher, which cannot balance any other way.
**Also dropped from the copy:** LcControl's `usance_type` party logic. A Shipment payment goes to a
vendor (party payment) or, with no vendor, keeps the GL classification — which is what a PIB duty
payment to KAS NEGARA is, and the cost-type master already says so via `shc_requires_vendor`.
**What this task does NOT settle: the leg composition per voucher type.** `spec.md` §7.2 gives a table
and then says to verify it line-for-line against `pkg_gen_voucher_ship` on a sample before go-live and
not to guess it from the table. So `ShipVoucherService` posts whatever balanced lines it is handed and
refuses an unbalanced set; **which** accounts make up an EPJV or an IBJV is still the caller's, and
PRD S3's sign-off is what settles it. T016 (provision posting) is where that lands.

### [DONE] T011 — `ShipTariffService`
**Refer:** `spec.md` §4.1 · **Blocks:** T013, and every front-office pricing task
`RATE` / `FIX` / `TIER`, tier groups, the 4-step vendor+port lookup, `sht_uom`-driven quantity, effective
dating, `spc_tariff_sys_id` stamping, and the `no_tariff_match` flag.
**This task fixes the semantics both modules price off**, so it lands before any front-office work:
`TIER` is **progressive** (one cost line per consumed band), `FIX` bands may **overlap** and resolve by
`MIN ≤ qty < MAX`, `RATE` is `rate × qty` with the band as a sanity check only, and a quantity that
matches no band yields a **flagged zero** rather than a refusal.
**Acceptance:** Pest, using the production master rows named in `spec.md` §4.1 —
**10 Jasindo 40FCL containers produce three lines totalling 3,750,000** (not one line of 3,500,000);
Jasco TRUCKING LCL at 50.5 KGS resolves to tier 2 and at exactly 50 KGS also to tier 2, and flipping
`FIX_BAND_BOUNDARY` moves the second case to tier 1; `FIX` forces qty 1; `RATE` prices an out-of-band
quantity in full and warns; 25 CBM against Andalan's 22 CBM ceiling returns amount 0 with
`spc_no_tariff_match = 1` and the transaction still saves; an expired tariff is not used; import falls
back to `ALL`; the port ladder is exercised at all four steps; a call with no port code returns only
any-port rows and reports the gap; `sht_uom = KGS` takes gross weight rather than the container count.
**Landed 2026-09-02. Every acceptance case above is a test, and all 16 pass** — including the headline
one: 10 Jasindo 40FCL containers produce **three** lines of 450,000 / 1,200,000 / 2,100,000 totalling
**3,750,000**, with an explicit assertion that it is *not* 3,500,000.
**Shape of the answer.** `resolve()` returns a `ShipTariffResolutionData` (lines + notices +
`matchedStep`), not the bare line array `design.md` §4 described. Two cases spec.md §4.1 insists on
reporting have no line to hang off: a call made with no port code, and a vendor with no configured
tariff at all. A warning nobody can see is exactly the failure the flagged-zero rule exists to prevent,
so the notices travel with the result. `design.md` §4 updated.
**`FIX_BAND_BOUNDARY`** is a parameter with two values: `LOWER` (default, `MIN <= qty < MAX`, so Jasco's
overlapping bands put exactly 50 KGS in tier 2) and `UPPER` (`MIN < qty <= MAX`, putting it in tier 1).
Both directions are tested.
**Two behaviours added beyond the letter of the spec**, both because the alternative is a silent wrong
number: a TIER set whose bands stop short of the quantity prices what it can and **names the unpriced
remainder** ("3 of 8 was not priced"), and a group with more than one RATE row uses the first and says
so. Neither refuses the transaction.
**`ShipQuantityContext` is what keeps the caller out of the decision** — it carries containers, CBM,
gross weight, chargeable weight and days together, and the tariff row's `sht_uom` picks. A rate per kilo
therefore cannot be multiplied by a container count; there is a test for exactly that.

### [DONE] T012 — Master CRUD pages (8)
**Refer:** `design.md` §6, root `CLAUDE.md` master-CRUD recipe · **Blocks:** —
Cost types, container types, ports, activities, tariffs, facilities, posting accounts, parameters —
list + create/edit + delete + export, routes, breadcrumbs, permission `finance-shipment-master-manage`.
**Acceptance:** each page CRUDs a row; tariff page can clone a row (tiers are edited as a set) and its
port column shows "Any port" for a null; a port row that any tariff or document references cannot be
deleted, only deactivated; parameter page validates by `shr_data_type`; breadcrumbs give each page its
tab title.
**Landed 2026-09-03 (part 1 of 2): ports, container types, facilities, cost types.** All four share
`Livewire\Master\Shipment\Concerns\ManagesShipMaster` (list, search, per-page, show-deactivated,
create/edit/clone, and a delete dialog that offers deactivation when something references the row) over
`Services\Master\Shipment\AbstractShipMasterService`, whose `deleteBlockedReason()` each service fills
in. A page is then ~60 lines of wiring plus its view. Routes sit under `shipment/master`, behind
`can:finance-shipment-master-manage`; the four breadcrumbs supply the tab titles.
Rules worth naming: the port page enforces **one default** inside a transaction (two defaults would
make which port a new provision pre-selects depend on row order); the facility page clears
`shf_auto_registered` on save, because opening an auto-registered row and confirming it *is* the review
the flag asks for, and it can filter down to the unreviewed ones; the cost-type page keeps a code unique
**per direction** and refuses a voucher from the wrong side (`assertVouchersMatchDirection()` — an
EXPORT row raising IPJV would land in the wrong journal and only show up in the GL weeks later), while a
`BOTH` row may carry either because it is resolved per document. Filtering the list by a direction
deliberately still shows the `BOTH` rows, so the list agrees with the provision screen.
**Bug found by the tests:** `ShipContainerTypeData` and `ShipFacilityData` left the code as typed while
their services assumed it arrived upper-cased, and `$attributes + ['shk_code' => $code]` never overrides
an existing key — so `45hc` was saved as a second container type. The DTOs now upper-case like
`ShipCostTypeData` already did, and all three services use `array_replace` so a stale key cannot win
silently again.
23 Pest tests (`ShipPortManagerTest`, `ShipMasterPagesTest`).

**Landed 2026-09-03 (part 2 of 2): activities, tariffs, posting accounts, parameters, and the export.**
- **Tariffs** edit a **rate set**, not a row: opening any band loads every row sharing its
  discriminators, because a TIER set's bands price a quantity together and a FIX set's bands choose
  between each other. The set is located by the `sht_sys_id`s the form was loaded with, so changing a
  discriminator moves the set's own rows instead of orphaning them under the old vendor. Clone drops
  those ids **and** the bands' own — left in place the save would retire the original's rows as
  "removed". A band dropped from a set that has priced something is deactivated rather than deleted and
  the page says so out loud. The save refuses what prices wrongly in silence: a second band on a RATE
  set, a TIER band that overlaps or follows an open-ended one, a band with no start on a
  quantity-selecting shape.
- **Activities** are unique per direction, and a `BOTH` row may not sit beside a direction-specific one
  with the same code — `ShipMasterRepository::activity()` matches `[$direction, 'BOTH']` and takes the
  first row, so two matches would make the accounts depend on row order.
- **Posting accounts** refuse a second rule with identical discriminators (comparing a blank with
  `whereNull`, not `where(col, null)`, which matches nothing), and protect the last active rule for a
  purpose every cost line must resolve — neither deletable nor deactivatable, since turning it off
  stops it resolving just as surely. `deactivateBlockedReason()` on the base service is new for that,
  and the shared dialog hides Deactivate when it answers.
- **Parameters** validate the value against the row's own `shr_data_type` and show the parsed reading
  back beside the field, so `2,750` in a NUMBER parameter is visibly 2 before it is saved.
  `ShipParameterKeys` lists the keys the code reads (spec.md §6) and those cannot be removed or turned
  off: nothing would fail, the reader would fall back to a default in the code and the figures would
  quietly change. **Add a key to that list in the same commit as the code that reads it.**
- **Export** on all eight pages, synchronous (`Excel::download()`, as `RawMaterialService` already does
  for master data) rather than queued — the queued pattern is for transaction reports that scan years.
  One `ShipMasterExport` renders a `heading => callable` map each service declares, so the file matches
  the list's current search and filters.
**Two bugs in landed code, found by writing these pages:**
1. **`ShipAccountResolver` read its six rungs as exact signatures**, so a rule whose filled columns were
   not a prefix of direction / document / cost type / activity / condition matched *nothing*. Eight of
   the thirteen rules in `data-migration.md` §7 are shaped that way: `PPN` + `PPN_1_1` silently lost to
   the 11% catch-all, and `EXPENSE` + `IMPORT` + `PIB` resolved to no account at all. A blank
   discriminator is now a wildcard and the most specific match wins, ranked by a bitmask that
   reproduces the six rungs exactly for the shapes they describe. All 15 existing resolver tests still
   pass unchanged; 2 were added. **Assumption to confirm with Finance:** where two matching rules fill
   the same number of columns, the one filling the broader column wins (cost type over condition), and
   `spa_priority` settles an exact tie — the seeder should set priorities deliberately for the EMKL /
   HAS_PPH pair in §7, which both match an EMKL line that withholds PPh.
2. `ShipTariffService::fixBoundary()` recognised only `LOWER` / `UPPER` while spec.md §6 seeds
   `FIX_BAND_BOUNDARY` as `MIN_INCLUSIVE`; `MAX_INCLUSIVE` would have fallen through to the default and
   priced the boundary quantity in the wrong band. Both vocabularies are now accepted.
**Also fixed while here:** `resetFilters` and the reset-to-page-one now cover each page's own filters
via `extraFilterProperties()`, which the cost-type page was missing.
71 Pest tests across the eight pages and the export; the whole suite is 335 green.

### [DONE] T013 — `ShipCostCalculator` + `ShipAccountResolver`
**Refer:** `spec.md` §4.2–4.4, §5.3 · **Blocks:** T014
Pure classes. Line amounts, IDR conversion of PPN/PPh, rounding by currency, cross-currency, PPh
advanced, PIB duty branch, rollups; account resolution with the 9-step order.
**Acceptance:** Pest against figures taken from **real** legacy documents (attach the source trans nos.
in the test docblock): 6 export lines and 4 import lines reproduce legacy's stored amounts exactly.
Resolver: each of the 9 steps is hit by a test, including the `PPN_1_1` and `HAS_PPH` conditions.
**Landed 2026-09-02. 35 tests** — 20 calculator, 15 resolver. Every resolver step has its own test,
including both conditions and a test that a row scoped to EXPORT must **not** answer the global step.
**The parity figures are read off production, not derived from the spec.** Documents used:
`2026000402` (INSURANCE USD, FREIGHT-EXP IDR), `2026000401` (TOLCHG), `2026000902` (JASA-IMP-I,
LIFTOFF qty 2, TRUCKING qty 2, DEMURRAGE, STORAGE), `2026000908` (STORAGE, STORG-AIRPORT-CHG,
STORG-CARGO), `2026000924` (PIB duty), `2025000936` (taxed rupiah line). Each uses the line's **own**
`sad_exc_rate`, not the header's — they differ, and using the header's rate was my first mistake here.
**`spec.md` §4.2–4.3 verified correct against all 8,833 priced legacy lines.** base/PPN/PPh/FC round to
0 decimals on a rupiah line and 2 on a USD one; `lc` is 2 decimals on an ordinary line and 4 on a duty
line. I had doubted the 0-decimal rule from early samples — the samples were the older era. Nothing in
the T007 trait needed changing.
**Two production findings came out of this, both raised as open questions and neither a code fix:**
- **Q13 — legacy used two rounding conventions.** All 8,833 lines fit one of two rules and none fits
  neither; 1,049 taxed lines are at 2 decimals against 377 at whole rupiah, overlapping in time. The
  calculator implements the current era. Recomputing a 2025 line will differ from its stored figure by
  cents.
- **Q12 — `sah_trans_no` is not unique, and this schema says it is.** 433 of 1,502 provision numbers
  and 638 of 1,293 bill numbers are duplicated, because the number is reused across cost types
  (`2026000401` is both a PIB and an EDN). **The backfill fails on ~1,070 rows as specified** — added to
  `data-migration.md` §5 as RED 1b. This blocks the unique-constraints migration and T027.
**Revised 2026-09-10 (Q4): the ladder starts at nought.** Finance answered that a PO carrying its own
main account beats every configured rule, not just the ones that failed to match, so the override moved
from the bottom (step 8) to the top (step 0) and the count went from nine steps to ten. It is guarded to
**import** and to the **`EXPENSE`** purpose: the PO carries an expense account and nothing else, and as a
bottom rung it could answer a PPN, PPh or accrual lookup that nothing else matched — a latent bug the
move made worth fixing. Three tests: the PO beating the most specific rule the master data can express,
the PO staying off the tax and accrual lookups, and export still falling through to step 9.

**Not built here:** the caller wiring. `ShipProvisionService` (T014/T016) is what calls the resolver per
line and per purpose, with `ppnCondition()` / `pphCondition()` supplying the discriminator.

### [DONE] T014 — `ShipProvisionInput` (both directions)
**Refer:** `design.md` §6, `spec.md` §3, §5.1, §5.4–5.5 · **Blocks:** T015
One component. Header panel driven by the cost-type master flags; port picker on import; container
editor; EDN picker (export) / PO picker (import); invoice tabs; cost grid with the duty-column switch;
tariff pull; insurance and commission buttons; save in one transaction; field-level activity log.
Includes the two Aju-linked copies: EMKL → PIB (PIB no. / date / SPPB) and PIB → EMKL (port).
**These two are transitional** (`spec.md` §5.4–5.5): write them behind a single
`spv_sif_sys_id === null` guard, in one service method, so T056 can delete them as a unit rather than
hunting the copy logic through the component.
**Acceptance:** an export provision from an ESC and an import PIB provision can both be created and
re-opened with identical values; validation messages appear per `spec.md` §3.2; save is one transaction
(assert with a forced failure mid-save leaving nothing behind); entering an Aju no. on an EMKL provision
pre-fills the port from the matching PIB provision, the field stays editable, an EMKL provision with no
port is refused at tariff pull and at submit with a message naming the Aju no., and an already-priced
EMKL provision is not silently re-pointed when its PIB provision changes port — it shows the mismatch.
**Landed 2026-09-04. 23 tests** — 8 service, 10 screen, 5 Aju linkage. Suite 335 → 358, all green.
`ShipProvisionService` (save in one transaction, the named validation cases, the tariff pull, the two
transitional copies), `ShipProvisionInput` + view and five partials, the create / edit routes and their
breadcrumbs, a `SHIP_PROVISION` / `SHIP_BILL` sequence migration, and `ShipErpFakes` so a screen test
does not need Oracle.
**The two transitional copies are one method** (`applyAjuLinkage()`) plus `portFromPibProvision()` and
`portMismatchNotice()`, all behind the single `spv_sif_sys_id === null` guard, and their tests are a
**separate file** (`ShipProvisionAjuLinkageTest.php`) whose docblock says T056 deletes it whole.
**Three defects found and fixed on the way, none of them in this task's own new code:**
- **`SysIdHelper` never worked outside Oracle, and its write never worked at all under a case-
  preserving driver.** `HM_MST_SEQUENCES` is declared with upper-case column names, so every
  `$row->hmms_*` read returned null: padding fell back to four digits and the increment to zero, and
  `save()` built its where clause from a null `hmms_seq_id` and updated no row. Every generated number
  came out as the same `<date>0000`. Reads now go through a case-insensitive accessor and the write is
  by sequence name. Production was unaffected (Oracle hands the names back lower-cased), but no test
  had ever exercised the helper — this is the first.
- **`ShipProvisionCostData` was missing `no_tariff_match`**, which exists on the model and on
  `ShipResolvedTariffLineData`. A tariff-pulled flagged zero lost its flag on the round trip through
  the screen, which is exactly the gap §4.1 added the flag to make visible. Added, with
  `fromResolvedTariffLine()` doing the neutral-DTO → screen-row mapping.
- **Nested `required_without_all` silently made all three duty columns required.** Laravel resolves
  the named fields against the root of the data, so under `invoices.0.lines.0.` the rule looked for two
  top-level keys, found neither, and fired on every column. `ShipProvisionService::qualify()` rewrites
  the referenced names along with the rule.
**Also:** duty amounts are normalised as they are typed. They are pasted off the PIB carrying
Indonesian thousands dots (`37.500.000`) — the DTO reads that, the `numeric` rule does not, so the
value was refused before the DTO ever saw it.
**Watch:** `spv_trans_no`'s counter does not reset in January. `2027` numbering will continue from
where `2026` stopped rather than restarting at `000001`. Cosmetic, and nothing reads the digits, but it
differs from legacy — raise with Finance before go-live if the shape matters to them.
**Not built here: the insurance and commission buttons.** They call `ShipInsuranceService` and
`ShipCommissionService`, which **T015 creates** — and T014 blocks T015, so there was nothing for them to
call yet. Neither appears in this task's acceptance. **Landed with T015 on the same day.**

### [DONE] T015 — `ShipInsuranceService` + `ShipCommissionService`
**Refer:** `spec.md` §4.5–4.6 · **Blocks:** —
**Acceptance:** Pest with a faked EDN repository: CIF adds the parameter addon, non-CIF does not; the
`INSURANCE` / `INSURANCE-INL` mismatch is rejected. Commission: `Q` and `R` bases both computed, and the
invoice vendor is switched to the agent.
**Landed 2026-09-04. 27 tests** — 12 insurance, 11 commission, 4 on the screen. Suite 360 → 387.
Both services, `ShipInsuranceQuoteData` / `ShipCommissionQuoteData` / `ShipCommissionTermData`, the two
buttons T014 left off the provision screen, and `deliveryNoteQuantity()` on the sales-doc repository —
the `Q` basis needs a quantity and nothing returned one.
**Both services return a result object, not an amount**, for the reason `ShipTariffService` gives: the
cases worth reporting — no EDN attached, an activity contradicting the Inco Term, an unrecognised basis
— produce no line to hang a warning off. Both expose `lines()` as `ShipResolvedTariffLineData`, so a
quoted line reaches the cost grid by the same path a tariff line does rather than a third one.
**Two places where the acceptance as written could not be met, and what was done instead:**
- **"the invoice vendor is switched to the agent" — it is proposed, not switched.** The agent-to-vendor
  step has no data behind it: `open-questions.md` Q10 records that every `AGENT` row in `om_expense`
  has all 20 flex columns null and only 14 of 123 agent names match a supplier exactly, and the
  interface's own docblock already said the service "must let the user confirm the vendor rather than
  substituting one silently". So the service returns a best-effort name match with
  `vendorNeedsConfirmation`, the screen shows it as a warning, and `applyCommissionVendor()` is the
  user's deliberate click. Switching it silently would misvendor roughly a third of these invoices.
- **Which Inco Term implies which insurance activity was documented nowhere.** §4.5 requires the pair
  to be validated but never says which is which, so it shipped as a parameter the export team owns —
  raised as `open-questions.md` F-INS1. **Answered 2026-09-10, and the reading it shipped with was
  backwards.** `FOB`, `CFR` and `CNF` are the inland terms; `CIF` and `CIP` carry cover to destination
  and are the international `INSURANCE`. So the parameter changed sense as well as name:
  `INSURANCE_INLAND_TERMS` (default `FOB,CFR,CNF`) replaces `INSURANCE_INTERNATIONAL_TERMS` (default
  `CIF,CIP`), and an unclassified term now falls on the international side rather than the inland one.
  Two rules stayed as they were: the CIF addon is **separate**, following the term being exactly `CIF`
  and not configurable, so a `CIF` shipment earns it whichever activity the list implies; and a **blank**
  term still defaults to the inland activity while the pair rule holds no opinion on it — a document
  with no term keyed is unfinished, not international. `spec.md` §4.5 and §6 rewritten to match.
**Unverified against production:** `ot_invoice_item.invi_txn_qty`, the column
`deliveryNoteQuantity()` sums. Named by symmetry with `ited_txn_qty`; every other column in that
repository was checked on 2026-09-02 and this one could not be. A wrong name surfaces as an immediate
SQL error rather than a wrong number, but check it before the `Q` basis is trusted on a live document.
**Deferred:** a provision whose EDNs span several contracts prices only the first and says so. Splitting
a commission across contracts is `PRD EXIM.md` §7's open question F8a and is not settled.

### [DONE] T016 — Provision workflow + vouchers
**Refer:** `spec.md` §2, §7.2–7.5 · **Blocks:** T017
`ShipStatusService`, submit / revoke / confirm / amend / delete, the confirm-time account checks, the
journal voucher, the payment voucher for cost types that raise one, `shc_closes_on_confirm` → status 4,
re-post guard, activity log.
**Acceptance:** Pest: each transition allowed only from its listed states and with its permission;
confirm without an Orion id fails; a PIB provision lands on status 4 with a payment voucher; an EMKL
provision stays at 3 with **no pay date**; posting twice is refused.

**Landed 2026-09-04. 48 tests.** Suite 387 → 435. Five pieces:
`ShipWorkflowActionEnum` (the transition table, one permission suffix and one validation case per
action), `HasShipWorkflow` on both documents, `ShipStatusService`, `ShipProvisionVoucherBuilder` and
`ShipProvisionWorkflowService`. `ShipProvisionService` is untouched apart from its docblock — the split
is by moment, not by entity: it owns everything that happens while a document is being written, and the
workflow service everything after it is saved.

**Validation is not written twice.** The workflow service turns the saved document back into the
screen's own state with `toFormState()` and runs `ShipProvisionService::rules()` over it, so a provision
confirmed from an artisan command meets exactly the bar the screen sets. Only two checks sit on top,
because the form rules cannot see them: the ERP chart of accounts (§7.1) and the voucher dates (§3.2
`voucherDate`).

**One status service for both documents.** It takes a model using `HasShipWorkflow` rather than a
`ShipProvision`, so T021 wires the settlement in by adding two properties to `ShipBill` — already done —
and calling the same methods. That is D5's whole point, and it is why `ShipStatusEnum` is not two enums.

**Four decisions worth reading before T017 or T021:**
- **`shc_closes_on_confirm` closes in the confirming step, not in a second hop.** `spec.md` §2 writes
  it as "3 → confirm → close → 4", and `ShipStatusEnum::allowedNext()` did not admit DRAFT → CLOSED.
  Doing it as two transitions would mean two writes, two log entries and a moment at which a PIB is
  Confirmed — a state it is never meaningfully in, since nothing is left to settle. So the enum now
  allows CLOSED from DRAFT / AMENDED / SUBMITTED and the confirm is one write. `ShipmentModelTest`'s
  transition assertions were updated with it.
- **The PIB and EMKL cost-type factories were wrong** against `data-migration.md` §7's seed table, and
  the acceptance could not be met until they were fixed: EMKL carried a payment voucher (`ADVP`) when
  the table says it raises none, and PIB carried a journal voucher (`IPJV`) when the table says its BPS
  payment is the whole posting. Both now match the table. **T004 must seed them the same way** — if the
  live seed disagrees, one of the two documents is wrong and it is worth settling before go-live.
- ~~**A payment voucher debits the provision account when a journal was raised, and the expense account
  when none was.**~~ **Wrong, and replaced 2026-09-10 (F-PAY1):** legacy debits the vendor's AP account
  per invoice for an advance, and splits a PIB across its three duty accounts. The reasoning here was
  sound and the ERP simply does something else.
**SUPERSEDED 2026-09-10 — the review this task asked for was done, and it changed the voucher.** What
T016 shipped was §7.2's table read literally: four legs per line, a PPN debit and a PPh credit among
them, and the accrual credited at `spc_fc_amount` with the USD residual rule that quartet needed. The
ERP holds something simpler: **970 EPJV / IPJV vouchers, not one carrying a tax leg**, and the accrual
credited at the **base** even on taxed lines. So the provision journal is now two legs — DR expense, CR
provision, both at `spc_base_amount` — and the taxes moved to the settlement, where the invoice that
carries them arrives (`ShipBillVoucherBuilder`, `spec.md` §7.2). The derived-PPh and residual rules went
with them; two legs of one amount in one currency need neither.

**Not built here:** the screens. Every method is callable and tested, but nothing renders a button —
that is T017, and the workflow service already exposes `ShipStatusService::can()` for deciding which
buttons to draw and `issues()` for previewing why a document cannot yet be submitted. §7.5's admin-only
"missing voucher repair" command and its dashboard panel are also not here; they belong with the
dashboard.

### [DONE] T017 — `ShipProvisionList` + `ShipProvisionDetail`
**Refer:** `design.md` §6–§7 · **Blocks:** T019
List with the filter set and pagination; detail page with the workflow buttons, voucher panel, print,
and the child tables read-only.
**Acceptance:** filters combine correctly; deep link by trans no. works; a missing trans no. 404s;
buttons hidden without the permission and refused server-side too.

**Landed 2026-09-04. 20 tests.** Suite 435 → 455. Both components, five Blade partials, the two routes,
four breadcrumbs, and the two Finance-dashboard tiles that make the pages reachable at all.

**Every filter is a query-string parameter**, so a filtered list is a link someone can send — and
direction especially, because Export and Import are the same page under `?direction=`, listed twice on
the dashboard because that is how the two teams think of it. Two components would drift the way
legacy's did. Reset clears the filters but keeps the direction: that is the page you chose, not
something you filtered by.

**A real bug came out of writing the list.** `EloquentShipProvisionRepository::filtered()` treated an
**empty** status array as a filter, so `whereIn('spv_status', [])` matched nothing and the page came up
blank until something was ticked. T016 never passed an array, so nothing had caught it. Fixed with
`statusFilter()`, which takes a single value, a list, `null`, `''` or `[]` and only filters on a
non-empty list; there is a test named after the symptom. **Worth knowing for T022**, which builds the
bill list from the same shape.

**The detail page draws its buttons from `ShipStatusService::can()` and every action calls the workflow
service, which asks the same three questions again.** Hiding a button is a courtesy; a `wire:click` is
reachable by anyone who can open the page, and confirming posts into the ERP. There is a test that
calls submit, confirm and delete as a view-only user and asserts nothing moved — that is the half of
the acceptance worth the most care.

Two smaller decisions:
- **`ShipProvisionRepositoryInterface::vendorOptions()` is new.** A vendor picker is too heavy for a
  list page and a free-text code box asks the user to know a code; the distinct set on an indexed
  column is small, and it can only offer vendors that would return rows.
- **`findWithChildren()` now eager-loads `invoices.costs.billCost`.** The detail page's "Settled"
  column is that relation's presence, and it was one query per line.

**Not built here:** print. `design.md` §6 lists it on this page and **T023 owns the PDF**, so there is
no button yet rather than a dead one. *(T023 has since landed; the button is there.)* The `-generate` "clear voucher" control is here, because T016
built the action and this is the only page it can live on.

---

## P2 — Settlement

### [DONE] T018 — Provision picker (single + multi)
**Refer:** `spec.md` §3.3, `design.md` §6 · **Blocks:** T019
Modal listing confirmed provisions **with unbilled lines**, plus the by-vendor mode that lists unbilled
lines across provisions and merges the ticked ones (document numbers concatenated, containers and docs
unioned).
**Acceptance:** Pest: a fully billed provision is absent; merging two provisions produces one bill whose
lines keep their own `stc_spc_sys_id`; `stl_si_no` holds both SI numbers.

**Landed 2026-09-04. 21 tests.** Suite 455 → 476. `ShipProvisionPicker`, `ShipBillService`,
`settleableInvoices()` on the provision repository, and the picker's Blade.

**The picker chooses; the service builds.** The component emits ids and
`ShipBillService::startFromProvisionLines()` does the merge, so every merge rule is asserted without a
browser and the single path is the multi path with one invoice's worth of ids rather than a second code
path that would drift.

**The single mode offers invoices, not provisions — and that is a correction to the task as written.**
A provision carries one invoice per vendor (the shipping line and the forwarder on the same shipment)
while a settlement is one vendor's bill, so "settle this provision" has no answer when the provision
spans two of them; the first draft of this task hit exactly that and could not produce a legal bill from
the export fixture. `design.md` §4 already named `startFromProvisionInvoice()`, so the design had it
right and the task summary was loose. `settleableInvoices()` is the query behind it.

**Revised 2026-09-10 (Q16): picking one provision invoice pulls in every unbilled line that vendor's
invoice *number* covers**, wherever it was provisioned — same vendor, same direction, settleable
provisions only (`unbilledLinesSharingInvoiceNo()`). The merge path already grouped by number; the gap
was this single-invoice path, which is the one the screen uses, and settling two shipments' provisions
one at a time produced two `ship_bill_invoice` rows carrying one vendor number — refused by T028's
guard, correctly. Finance chose the grouping over making the user renumber. Two tests: one forwarder
invoice across two shipments becomes one settlement invoice with both sets of lines, and another
vendor's identical number is left where it is.

**Four merge rules, each forced by a unique index rather than chosen:**
- **containers are summed per type**, not appended — `uq_ship_bill_cont` is (bill, type), so two
  provisions each declaring 2 × 40FCL become one row of 4;
- **docs are deduped on (type, number)** — `uq_ship_bill_doc` says the same;
- **lines are grouped into invoices by invoice number** — `uq_ship_bill_inv` is (bill, invoice no.);
- **SI and EIN numbers are joined, distinct** — `stl_si_no` is 500 characters precisely so a merge fits.

The import references (`stl_aju_no`, `stl_pib_no`) are single-valued columns and are **not** joined:
§13.2 keys import candidates on the BL, so a merge is within one BL and the primary provision's values
are every source's values. Two guards refuse what cannot be one bill — more than one vendor, or export
and import lines together — and a line another settlement already claims is silently dropped from the
selection rather than left to fail on `uq` at insert time.

**`ShipBillService::save()` landed here, ahead of T019**, because this task's acceptance asks for a
persisted bill (`stc_spc_sys_id`, `stl_si_no` are columns). It mirrors `ShipProvisionService::save()`.
**T019 is therefore the screen**: the source toggle, the DIRECT path, the variance column, the
duplicate-invoice pre-check and the `rules()/messages()/attributes()` trio, none of which are here.

**Also fixed:** `EloquentShipBillRepository::filtered()` had the same empty-status-array bug T017 found
on the provision side. Corrected now, before T022 builds the bill list on it.

### [DONE] T019 — `ShipBillInput`
**Refer:** `design.md` §6, `spec.md` §3–§4 · **Blocks:** T020
Source toggle (locked after save), header from the provision (read-only) or hand-entered for DIRECT,
invoice cards with received date and Faktur Pajak, cost grid with the variance column, one-transaction
save, duplicate-invoice pre-check.
**Acceptance:** a PROVISION bill and a DIRECT bill both save and re-open; variance matches
`provision − bill` per line, invoice and header; the duplicate check names the conflicting bill.

**Landed 2026-09-05. 17 tests.** Suite 509 → 526. `ShipBillInput` + its three Blade partials, the
`rules()/messages()/attributes()` trio and `costTypeFlags()` on `ShipBillService`, `blank()` on the two
bill child DTOs, the two routes and their breadcrumbs, and a `Settle` button on the provision detail
page.

**The screen mirrors `ShipProvisionInput` and adds exactly three things**, which is the whole design:
a source, a header that is read-only on one of the two paths, and the variance column.

**The source toggle is locked by `sourceOnRecord`, not by the disabled attribute.** Disabling the
select is the courtesy; a posted value is put straight back. It matters because the two sides mean
different things about the lines — a PROVISION line points at a provision line, a DIRECT one has
nothing to point at — so flipping a saved bill would either strand or invent links. Switching before
the first save empties the document rather than carrying half of it across, for the same reason.

**`headerLocked()` is narrower than it sounds.** The shipment's identity comes from the provision —
direction, cost type, vendor, SI, Aju, BL. The bill's own facts are editable on both paths: its dates,
its bank details, its remark, **and the EIN no.** The EIN is deliberately outside the lock: `spec.md`
§3.1 requires it on an export bill drawn from a provision, and the export clearance number usually
only exists by the time the vendor's invoice arrives — locking it to a provision that has none would
make the bill unsaveable. There is a test named for it.

**The variance is computed, never read back.** `pricedInvoices()` re-prices every line from what is on
screen and is the single source the grid, the invoice footer and the header totals all read — so an
edited rate shows its effect before the save rather than after, and the three cannot disagree.
`spec.md` §4.4's rule is followed on the header: the invoice total is the headline and the variance is
a second, labelled figure.

**A duty provision line is seeded as one unit at its provisioned amount** — a fix inside
`mergedInvoices()`, found while building the grid. `ship_bill_cost` has no duty columns by design (a
settlement records what the vendor charged as quantity × rate whatever the provision looked like), so
a PIB line was being copied across at `spc_rate`, which is zero — and a bill nobody had touched opened
showing its whole duty as a variance. It now opens at zero, which is what "untouched" should mean.

**Revised 2026-09-10 (Q5): a second, non-blocking notice per invoice tab.** Legacy's cross-check
against `ft_payment_header.ph_flex_01/_04` is back, so an invoice number a legacy payment voucher
already carries is named with that voucher's reference. It lives in `erpPaymentNotices`, **not** in
`duplicateNotices`: `save()` refuses to write while the latter is non-empty, so merging the two would
have turned a caution into a silent refusal — which is exactly what the first attempt did, and what the
test that saves through the warning now pins.

**Three partials became shared rather than copied.** `_provision-containers`, `_provision-docs` and
`_provision-pickers` are now `_shipment-*`: both screens hold `containers` / `docs` and expose the same
handlers, and `design.md` §6 is explicit that duplicated shipment grids are how the legacy export and
import screens drifted. The cost grid is **not** shared — the provision's has duty columns and the
settlement's has the variance and Faktur Pajak columns, so one grid with a mode flag would be worse
than two honest ones.

**Also fixed:** `FinanceRolesAndPermissionsSeeder` imported Spatie's `Permission` / `Role` models,
which take the default connection, while the tables live on `oracle_mgthris`. Four tests in
`ShipmentPermissionSeederTest` were failing on "no such table: permissions" before this task started.
It now reads the Auth module's models, as `SalesControlRolesAndPermissionsSeeder` already did.

**Not built here:** the Faktur Pajak picker — the fields are on the grid and typed by hand, and **T020
adds the picker** that fills a whole invoice from `mgt_bill_fp_scan_v`. Submit, confirm and the
vouchers are T021, so the screen saves and nothing else; `rules()` already carries the `submit`,
`confirm` and `payment` cases those will call. The settlement list and detail page are T022, so the
only way in is the `Settle` button on a confirmed provision — which is also why both breadcrumbs hang
off the Finance dashboard and **T022 must re-parent them onto `bill.index`**.

### [DONE] T020 — Faktur Pajak picker
**Refer:** `spec.md` §3.2 (`confirm`), `design.md` §5 · **Blocks:** —
Searchable list from `mgt_bill_fp_scan_v`, apply to every line of an invoice, auto-fill the date when a
known number is typed.
**Acceptance:** applying fills all lines; a PPN line without a faktur blocks confirm.

**Landed 2026-09-05. 13 tests.** Suite 526 → 539. `ShipFakturPicker` and its Blade, the apply and
auto-date handlers on `ShipBillInput`, a faktur fake in `ShipErpFakes`, and one correction to
`ShipBillService::rules('confirm', …)`.

**It answers for an invoice, not for a line.** One vendor invoice is covered by one faktur, so the
button sits on the invoice tab and the chosen number fills every line under it — the acceptance's
"applying fills all lines". It fills **every** line, not only the ones carrying PPN: the faktur is a
property of the invoice, and a line whose PPN is typed in afterwards would otherwise be the one that
fails the confirm gate.

**The date is `fp_tanggalfaktur`, not `fp_date`.** The first is printed on the faktur, the second is
when the scan landed, and a scan that arrives a week later is a different month — `stc_fp_date` is what
the tax report is built from. `fp_date` is the fallback for a row the view has read no document date
off. There is a test named for it.

**The vendor is a default, not a cage — and clearing it has to actually widen the list.** The picker
opens narrowed by NPWP when the ERP holds one for the invoice's vendor, and by the vendor's name when
it does not. The first draft made the name an automatic fallback for a cleared NPWP, which meant the
list silently re-narrowed and a faktur the scanner had filed under a different name was unreachable.
The name is now a filter field of its own, seeded only when there is no NPWP, and both are clearable.

**A typed number the scan list has not reached is allowed.** The scan often lands days after the
invoice does, and refusing the save would push the user into typing a date nothing checks. A number
that *is* in the list fills its date; clearing the number clears the date with it. The confirm gate is
where a missing faktur stops a document, which is where `spec.md` §3.2 puts it.

**Correction to the confirm rules, found while wiring the gate.** `ShipBillCostData::confirmRules()`
keys the Faktur Pajak and the tax accounts off `ppn_amount` / `pph_amount`, and `rules()` was handing
it the raw **form row** — whose amounts are whatever the last save wrote. A PPN percentage typed in
this session therefore left the gate disarmed, and the first confirm after it would have passed with no
faktur behind it. `rules('confirm', …)` now prices each line first, through the same arithmetic the
screen shows. There is a test named for the symptom.

**Not built here:** the confirm action itself. `rules('confirm', …)` is complete and tested through
`Validator`, and **T021 is what calls it** from the workflow service.

### [DONE] T021 — Bill workflow + vouchers + provision close
**Refer:** `spec.md` §2, §7 · **Blocks:** T022
Submit / revoke / confirm / amend, bill journal voucher, generate payment, and
`closeIfFullySettled()` on the provision.
**Acceptance:** Pest: confirming the last bill of a provision moves the provision to 4; a bill's pay date
is only set by the payment path; import bills now require submit before confirm.

**Landed 2026-09-05. 18 tests.** Suite 539 → 557. `ShipBillWorkflowService`, `ShipBillVoucherBuilder`,
`ShipPostingPurposeEnum::VENDOR`, `forBill()` on the provision repository, an `AssertsErpAccounts`
concern the provision workflow now shares, and posting-account resolution on the settlement save.

**The settlement journal is §7.2's table read literally**, one leg pair per line plus one payable per
invoice: DR the provision account for `stc_provision_amount` — the accrual exactly as it was raised —
DR or CR the expense account for the variance, CR the vendor account for what is actually owed. It
balances by construction (`accrued + (billed − accrued) = billed`), and a DIRECT line is the same
arithmetic with `provision_amount = 0`: its whole amount lands on the expense account and there is no
accrual leg. The payable's USD base is the residual of that invoice's debits, for the same reason the
provision builder credits the accrual with a residual — a mixed-currency voucher can only balance on
the base.

**REVISED 2026-09-10 — the tax legs T021 left out are legacy's, and they belong here.** What shipped
was §7.2's settlement row read literally: three account roles, none of them a tax account, with the
taxes riding inside the accrued and billed figures. Measured against the ERP, **1,502 of 1,627 EBJV /
IBJV vouchers carry a tax leg**, so the settlement now posts the PPN debit and the PPh credit as well —
plus a vendor debit for PPh that is *advanced* rather than withheld (legacy's "Adv PPH23", on 46 of the
131 unposted ones). The accrual is relieved at the base and the variance measured base-to-base, because
`sbd_prv_amt` is the provision's base on 200 of 200 sampled taxed lines.

**F9 is answered and no longer blocks the first confirm.** The credit account was never guessed —
`ShipPostingPurposeEnum::VENDOR` resolves through `ship_posting_account` like everything else — and the
row it needed is now seeded: **`203001`, with the vendor code as its sub account**, since that account
has 696 sub rows and none without one. A payable posted against the main alone would have been refused
by `fm_acnt_curr`, so the workflow's pre-check asks about the pair too.

**Import settlements keep their approval step, and that is the point of the enum.** The shared
transition table lets confirm run straight from Draft, which is right for export. `gap-analysis.md`
lists legacy's `ImportShipSettlement` boolean `$approved` as *replaced* by the shared status enum — and
it is only replaced if the import side keeps the step, so the shared table is the floor and
`assertSubmittedIfImport()` adds one rung to it. `spec.md` §2 now says so.

**The payment is its own action, not part of confirm.** A provision posts its payment voucher on
confirm when the cost type raises one; a settlement never does. `generatePayment()` is the only thing
that writes `stl_pay_date`, it needs `-generate` rather than `-confirm` — paying is a separate
authority from agreeing the cost — and clearing the payment voucher clears the date with it, because a
paid-looking settlement with no voucher behind it is exactly what §3.2's CHANGE was written to prevent.

**A settlement never reaches status 4.** `ShipStatusEnum` labels it "Settled" for a bill and nothing in
§2 says what moves it there; a paid settlement is `stl_pay_voucher` + `stl_pay_date`, which is what
T022's pay-status filter reads. Leaving the status unused beats inventing a transition — noted in
`spec.md` §2, and it is one line here if Finance wants it.

**Two gaps found while wiring it up, both fixed here.**
- **Settlement lines were not resolving their posting accounts.** `ShipProvisionService::save()` fills
  a line's four accounts from `ShipAccountResolver`; `ShipBillService::save()` did not, so the `BILL`
  side of `ship_posting_account` was unreachable and the settlement grid's "resolved on save"
  placeholder was a lie. It now resolves against `document = BILL`, which matters most for the **sub**
  account: §3.2 asks a settlement for expense main *and* sub where a provision needs only the main.
- **`after_or_equal:trans_date` was pointing at nothing.** Moved under `form.`, the rule looked for a
  top-level `trans_date`, found none, treated the word as a date and `strtotime()` threw — so a
  settlement with a due date on it could not be saved at all. `qualifyHeader()` carries the reference
  across, as the provision service's `qualify()` does for its `required_*` family.

**`assertAccountsExist()` was the same forty lines on both documents**, so §7.1's two checks moved into
`Concerns\AssertsErpAccounts`: each document still gathers its own (main, sub, currency) tuples and the
asking is shared.

**Not built here:** the buttons. `ShipBillDetail` is **T022**, so this is the service and its tests —
the same order T016 and T017 took on the provision side.

### [DONE] T022 — `ShipBillList` + `ShipBillDetail`
**Refer:** `design.md` §6 · **Blocks:** —
Includes the pay-status filter (PAID / UNPAID / OVERDUE) the Bill report will reuse.
**Acceptance:** OVERDUE = no pay date and a past due date; counts agree with a hand-written query.

**Landed 2026-09-05. 22 tests.** Suite 616 → 638. Both components, five Blade partials, the two routes,
four breadcrumbs (the two that existed are re-parented), `ShipPayStatusEnum`, `vendorOptions()` on the
bill repository, and the two Finance-dashboard tiles. **The settlement workflow is now reachable by a
user** — until this, nothing but a test called submit or confirm.

**Pay status is derived, and `ShipPayStatusEnum::forBill()` is the only definition.** `ship_bill` has no
pay-status column and must not grow one: paid *is* `stl_pay_date` being filled, which only
`generatePayment()` writes and clearing the payment voucher unwrites, and overdue is that same absence
next to a due date already past. A stored column would be a second answer to a question the two dates
already answer — legacy's stamped-on-confirm pay date is the bug §3.2's CHANGE was written against. The
repository's filter is those same three predicates in SQL, and the acceptance test counts each bucket
with a hand-written query rather than with the enum, because a filter agreeing with itself proves
nothing. It also asserts the three buckets partition the table: nothing is counted twice and nothing
falls out. **A bill with no due date can never be overdue** — nothing says when it was owed — so it
stays in UNPAID.

**Overdue is a kind of unpaid, not a fourth state, and paid-late is still PAID.** The pay date settles
the question; how late it was is the report's business (T032), not the badge's.

**One real fix came out of building the page.** The payment modal first defaulted its date to the due
date, which is the natural-looking choice and wrong: the voucher lands in the period of the date it
carries, and a due date a month out defaults the payment straight into a period that is very likely
closed — the first test of it failed on exactly that. It defaults to today.

**The Confirm button is drawn on an import draft even though confirming will refuse it.** The shared
transition table allows it and `assertSubmittedIfImport()` is the rung above; hiding the button would
put the two rules in two places. Instead the status strip and the confirm modal both say the sentence
the service would throw, so the user reads it before clicking rather than after.

**The two settlement breadcrumbs are re-parented onto `bill.index`**, as this task required — they hung
off the Finance dashboard because there was no list. The list crumb takes the direction, so the browser
tab reads "Export Settlements" when the dashboard linked one side, exactly as the provision list does.

**`settleableProvision()`, `settleableImportProvision()` and `settlementFrom()` moved** out of
`ShipBillWorkflowTest` into `tests/Helpers/Finance/ShipmentHelpers.php`: two test files now build a
saved settlement, and two copies of that would drift.

**Not built here:** print. `design.md` §6 lists it on this page and **T023 owns the PDF**, so there is
no button yet rather than a dead one. *(T023 has since landed; the button is there.)*

### [DONE] T023 — Print (transaction + voucher PDFs)
**Refer:** `spec.md` §7.4 · **Blocks:** —
**Acceptance:** a provision, a bill, a journal voucher and a payment voucher each render a PDF with the
document's identity and totals.

**Landed 2026-09-08. 16 tests.** Suite 546 → 562. `ShipPrintController`, `ShipPrintService`, four Blade
sheets under `resources/views/pdf/shipment/` on a shared layout, a `findByReference()` on each of the
two voucher repositories, four routes, and the Print buttons on both detail pages and on every posted
voucher slot. **P2 is finished** — this was the last thing `design.md` §6 listed on either detail page.

**A voucher is printed through the document that posted it, never by its own reference.** The route is
`…/provisions/{transNo}/voucher/{slot}` and the controller reads the reference off the document, so the
sheet is guarded by the same `-view` as the page the reference is visible on. A route keyed on the
voucher reference would have let anyone holding any shipment permission print any ERP voucher, which is
not the access §8 grants. Printing gets no permission of its own for the same reason: whoever may read
the page may print what it says.

**Nothing is stored, which is a deliberate departure from §7.4's MinIO note.** Legacy's
`getVoucherPdf()` wrote the sheet to MinIO and handed back a link; the ERP owns the voucher rows and may
change them, so a cached PDF is a picture of what the voucher said the first time somebody printed it.
These render on demand from the live rows. If Finance ever wants a signed, archived copy, that is an
attachment with a hash — the shape `ApprovalDocumentService` already uses — not a cache.

**The journal sheet foots `td_fc_amt`, not `td_doc_amt`.** `fc_amount` is the voucher's own currency and
is always set; `td_doc_amt` is the local-currency figure the poster only writes on a cross-currency
line. Footing the wrong one printed a voucher whose totals were blank. The LC column now appears only
when a line actually has one, and both sheets say so on the page when the lines and the header disagree
— an unbalanced voucher is the one thing a reader of a voucher sheet needs to see at a glance.

**The reference splits on the last hyphen, not the first.** `ADVP-EXP-2026090001` is a tran code with a
hyphen in it; splitting on the first would have looked up tran code `ADVP`. One test covers exactly that.

**An empty slot, a slot that does not exist, and a reference the ERP has lost are three different 404s**
with three different messages, rather than one blank sheet. The third is precisely what T025's
missing-voucher repair exists to find, so printing must not paper over it.

**A fix that was not part of this task.** `Modules/Finance/routes/web.php` referenced all fourteen
Shipment Livewire classes with no `use` for any of them, so `Route::get('provisions', ShipProvisionList::class)`
resolved to a global `\ShipProvisionList` — `php artisan route:list` threw and **no page in the
application would load**, shipment or otherwise. The imports are added. Worth noting because the test
suite was green throughout: Livewire component tests never route, so nothing in P0–P2 could have caught
it. Something that walks the route table belongs in the T024 dashboard work.

---

## P3 — Dashboard, backfill, hardening

### [DONE] T024 — `ShipmentDashboard`
**Refer:** `design.md` §6, legacy import dashboard · **Blocks:** —
One row per shipment key (Aju no. for import, SI no. for export) showing provision, provision voucher,
payment voucher, bill and bill voucher; sortable, searchable, paginated; counters for pending approval,
confirmed today, created today; a panel for the missing-voucher check.
**Acceptance:** a provision without a bill and a direct bill without a provision both appear; counters
match hand-written queries.

**Landed 2026-09-08. 22 tests.** Suite 562 → 584. `ShipmentDashboard`, `ShipDashboardService`,
`ShipDashboardRepositoryInterface` + its Eloquent implementation, `ShipReconciliationRowData`, the Blade
page and its voucher-cell partial, the `/` route, a dashboard breadcrumb, and a Finance-dashboard tile.

**It pages over keys, not over a join.** A shipment key carries several provisions (PIB, EMKL, SHIP) and
several settlements, so joining the two tables would multiply rows out and then need distinguishing
again. The query is a `UNION ALL` of the key each table reports, grouped to one row per
(direction, key) with the latest transaction date — the portable stand-in for the full outer join FR-8
actually describes — and then two reads fetch the documents for the twenty keys on the page. Page cost
is flat whatever the table holds.

**The key rule is written twice and must stay in step.** `EloquentShipDashboardRepository::keyFor()` is
the PHP spelling and `keyExpression()` is the SQL one; the grouping is wrong the moment they diverge, so
they sit next to each other with that said out loud. The SQL is the shape it is for portability: Oracle
treats `''` as NULL and SQLite does not, so the blank test is `IS NULL OR TRIM(...) = ''` — one disjunct
for each database.

**A document naming no Aju / SI no. keys on its own trans no. and is flagged `unkeyed`.** Grouping every
such document under one blank key would have invented a shipment that merges unrelated documents, which
is worse than the gap it was hiding.

**Direction is part of the key.** An export SI and an import Aju that happen to read the same are two
rows, not one; there is a test for exactly that, because nothing stops the two numbering schemes from
colliding.

**Gaps-only filters on the page, not in SQL** — deliberately. "Lopsided" is a property of the whole key,
and a key's documents only exist once the page is assembled; pushing it into the query would be a second
definition of the same rule, and the two would drift. The cost is that a gaps-only page can show fewer
than `perPage` rows, which is the right trade for one definition.

**The missing-voucher check is on a button.** It is the only thing on the page that reads
`oracle_mgtdat`, and running it on render would put an ERP read behind every click of "next". It is also
the only question here that spans two connections, so it cannot be a join and is not one: it reads every
reference the documents claim and asks each ERP table which of its own it holds, batched 500 at a time
through the new `existingReferences()` on both voucher repositories. An ERP that is down is reported in
the panel and leaves the rest of the page working — the reconciliation table does not need `mgtdat` up.

**`missingVouchers` starts null, not empty.** "Not asked yet" and "none found" are different answers and
the panel says which.

**The existence check matches on (tran code, doc no.), not on doc no. alone.** The document counter is
per tran code, so two tran codes genuinely share numbers; the two `whereIn`s are a cross product and the
pairs are matched back up in PHP.

**The two list breadcrumbs are re-parented onto the dashboard.** Finance > Shipment Control > Provisions
is the real hierarchy and the routes already said so; until this task there was no page to hang them on.

**Not built here:** the repair. The panel names what is broken and **T025 owns fixing it** — clearing the
slot needs `-generate` and an activity-log entry, which is a write and belongs with the command.

### [DONE] T025 — Missing-voucher repair command
**Refer:** `spec.md` §7.5 · **Blocks:** —
Finds documents whose payment voucher does not exist in `ft_payment_header`; clears the reference (with
its stamps) so it can be regenerated; logs every change.
**Acceptance:** dry-run lists, real run clears, activity log shows who and what.

**Landed 2026-09-08. 12 tests.** Suite 584 → 596. `finance:shipment:repair-vouchers`
(`Console/Commands/Shipment/RepairShipmentVouchers.php`), `ShipVoucherRepairService`, a
`command_ship_voucher_repair` log channel kept for a year, the command registered on the Finance
provider, and a line on the dashboard panel naming the command that fixes what the panel found.

**The finding half is not written twice.** The command re-runs `ShipDashboardService::missingVouchers()`
— the panel's own comparison — and filters it to the direction and kind being repaired. Legacy asked
the question per document behind an admin button; asking it once for the whole table is the only part
of that this rewrites.

**It runs as a person, not as the system.** `--as=<NIK|user id>` names the operator, the command signs
in as them, and the clearing goes through `clearVoucher()` on the two workflow services. That is
deliberate: `clearVoucher()` already owns the `-generate` check, the activity-log entry, and — on a
settlement's payment slot — dropping `stl_pay_date` with the voucher. A command that wrote the columns
itself would be a second, quieter spelling of all three, and a nameless one at that, which is the
hidden admin button back under a new name.

**Every claim is re-read before it is cleared.** The scan and the write are separate statements, and in
between somebody may have posted the slot again from the screen. A slot whose reference has moved is
skipped with the reason printed, never forced — clearing on the strength of a stale reference would
throw away a good voucher. A run that skipped anything exits non-zero so a scheduled sweep is noticed.

**`--dry-run` needs no operator**, because it writes nothing; a real run without `--as` fails before it
reads the ERP, as does an operator holding neither `-generate`.

**`hm_emp_data` is one of the old upper-case tables**, so the `--as` lookup re-keys the row it fetches:
Oracle's driver folds columns to lowercase and the SQLite the tests run on does not, and a model
hydrated from the second has no `hmemd_sys_id` — with a null key, every permission check answers no.
The shipment tables are all lowercase (there is a test for it), so this is the one lookup that needs it.

### [DONE] T026 — Pre-flight query pack
**Refer:** `data-migration.md` §5 · **Blocks:** T027
An artisan command running all nine checks against the legacy connection and writing one report.
**Acceptance:** run against production; RED findings tabled with Finance; the report is attached to the
cut-over ticket.

**Landed 2026-09-08. 16 tests.** Suite 596 → 612. `finance:shipment:preflight`,
`ShipPreflightService`, `ShipPreflightFindingData`, `ShipPreflightSeverityEnum`, a `shipment_backfill`
log channel shared with T027/T028, and `LegacyStandIn` — a SQLite stand-in of the `SHP_*` schema that
T027 and T028 will build on.

**Run against production 2026-09-08** (`dpa php artisan finance:shipment:preflight`); the report is
`preflight-2026-09-08.md` in this folder, ready for the cut-over ticket. Tabling its RED rows with
Finance is what is left of the acceptance. What it found:

- **Q12 is a measurement, and the measurement is in.** Zero numbers are reused inside a trans code, on
  either document. The 433 / 638 were the two series sharing digits — 454 and 638 of those — so
  `(direction, trans_no)` is unique across every legacy row and costs nothing to adopt. **Answered
  2026-09-08**: both unique keys are now composite, and the pre-flight re-run is green.
- **RED 1 · two rows.** `SHPCTR` and `EXPCTR`, both draft exports with lines against them. **Q15,
  answered 2026-09-08**: a cancelled project, not migrated, and reported as skipped rather than blocking.
- **AMBER 5 · 17 pay dates** will be dropped by B2. **AMBER 8 · 162 cost lines** carry no provision
  account. **AMBER 7 · 16 `sad_fasilitas` values** are document names — `COO (RCEP)`, `BKPM`,
  `E-COO (FORM D)` — not facility codes, which T004's seed has to reckon with.
- Scale for T027: 1,971 provisions / 3,406 invoices / 9,605 cost lines / 3,007 containers / 4,271 POs;
  1,943 settlements / 3,213 invoices / 9,711 cost lines. Status spread 0=7, 3=995, 4=969.

**Two bugs the real schema found that the fixture could not.** The legacy activity master keys on
`sa_act_code`, not the `sa_code` `data-migration.md` §5 writes; and the master lookups asked the target
for `ship_cost_type` before that schema exists, which is an ORA-00942 on a step-0 command. Both fixed:
an absent master now reports "everything is unmatched" and says why. Also stripped oci8's windowed `rn`
column out of the sample rows — it is how the driver spells LIMIT, not part of any answer.

**Nine checks, twenty-one findings.** Several checks ask the same question of both documents, so the
report is one finding per query rather than per check — a reader wants to know which table is wrong,
not which paragraph of the doc it came from.

**Clean checks are in the report too.** A finding always exists; `total = 0` is a pass. A report that
listed only problems could not be read as evidence that the other eighteen questions were asked, and
that evidence is exactly what the cut-over ticket is for.

**The exit code is the answer.** RED with rows → non-zero, because §2 step 0 says the run does not
start. AMBER never fails: recording it is the whole of what AMBER means.

**Duplicate checks count keys, not rows.** Five rows sharing two numbers is two findings' worth of work,
not five. `count()` on a grouped query counts the first group's rows, so the total comes from a
subquery — getting that wrong would have under-reported the checks that stop the run.

**RED 1b asks the duplicate question twice, and that is the point.** A legacy document is
**(trans code, trans no.)** — `SHPEXP` and `SHPARR` are separate series, so the same digits on an export
row and an import row are two documents. The RED finding groups on the pair, which is what a
`(direction, trans_no)` key would enforce; a second, AMBER finding reports the cross-series collisions,
which only block while the unique index stays on the bare number. Q12's 433 / 638 were measured the
bare way and over-count; Q12 and `data-migration.md` §5 now say so, and one production run replaces
them with the figure the constraint decision actually needs.

**Two checks read the *new* masters.** "A cost type no master accounts for" is only answerable against
`ship_cost_type`, which is on the other connection, so that difference is taken in PHP. Until T004
seeds, they report everything — which is honest, and the note in the report says so.

**A wrong `--source` fails loudly.** The required legacy tables are checked before the first query,
because a mistyped connection would otherwise return nineteen empty results and read as "all clear".

**The legacy schema is read with the query builder, not through models.** `SHP_*` belongs to the old
application, is never written by this codebase, and becomes a read-only audit copy at cut-over. Giving
nineteen one-off report queries an Eloquent layer would be inventing a domain for a schema being retired.

**Shipment commands now live in `Console/Commands/Shipment/`** and are named `finance:shipment:*`, as
`design.md` §1 has them. T025's repair moved with it and is `finance:shipment:repair-vouchers`.

### [DONE] T027 — Backfill command
**Refer:** `data-migration.md` §1–§4 · **Blocks:** T028
Chunked, idempotent, `--dry-run` / `--resume` / `--rollback`, own log channel, `withoutEvents`, id map,
sequence advance, rollup recomputation with mismatch reporting.
**Acceptance:** Pest on a seeded legacy fixture: full run then `--resume` inserts nothing; `--rollback`
restores an empty target; flex columns land in the right direction-specific columns.

**Landed 2026-09-08. 27 tests.** Suite 617 → 644. `finance:shipment:backfill`, `ShipBackfillService`,
`LegacyProvisionMapper`, `LegacyBillMapper`, a `MapsLegacyColumns` trait, `ShipBackfillReport`, and
`LegacyStandIn` grown to the real legacy schema. All three acceptance clauses are asserted literally.

**Rehearsed end to end on 2026-09-08** against the dev server's copy of production data, once the
schema was migrated there: **1,969 provisions / 1,978 containers / 2,709 documents / 3,400 invoices /
9,588 cost lines / 1,943 settlements / 3,213 invoices / 9,711 cost lines**, and T028's verification green
on all of it. The rehearsal paid for itself several times over — six defects, four of them in the
schema and the mapping rather than in the data:

- **`spi_vendor_code` is NOT NULL and 68 duty invoices have no vendor.** Customs duty is paid to the
  state; the backfill writes `KASNEG` / Kas Negara, which is the classification, not a supplier.
- **`uq_ship_prov_inv` was wrong.** It keyed (provision, cost type, vendor), and **322 provisions**
  legitimately carry two invoices from one vendor with different numbers and amounts — one forwarder
  billing a shipment twice. The key is now (provision, vendor, invoice no.), which is what is actually
  unique, and the screen would have rejected the same thing.
- **The `lc` columns held two decimals against 2,385 lines carrying four.** `ShipCostCalculator` says a
  duty line's USD figure has four; the columns rounded every one. Six columns are now `decimal(18,4)`.
- **PIB duty lines have no `sad_amt` at all** — the money is in the three duty columns and their sum is
  in `sad_fc_amt`. 510 lines would have migrated at zero.
- **Ten NOT NULL columns met legacy nulls** — currency (457 headers), exchange rate (1,853 invoices),
  activity, provisioned amount (1,044 lines). Each has a documented fallback and each is reported.
- **The rollup-drift check compared unlike figures.** `sai_total` is the gross (base + PPN − PPh), not
  the base; it called 503 correct invoices wrong before the comparison was fixed.

**One thing the rehearsal could not fix, and Finance must:** vendor `IS00697` has one invoice number,
`2026-46`, on two different settlements (`2026000657` and `2026000658`, different amounts) — the second
with a trailing space, which is how it got past legacy's own check. T028's guard refuses it, correctly.
The dev copy was renumbered to finish the rehearsal; production needs a decision about which number is
wrong.

**A failed chunk no longer stops the run.** Found the same night: abandoning 443 good settlements
because two documents share an invoice number is not much use at 2am. The chunk rolls back whole, its
documents are named, and `--resume` takes exactly those — which is how the rehearsal finished.

**Eight legacy tables, not ten.** There is no `shp_bill_cont` and no `shp_bill_po_head` — checked, not
assumed — so `ship_bill_container` and `ship_bill_doc` have no legacy source and stay empty for migrated
rows. §2 step 8 asks for something that does not exist.

**Whole documents per transaction, ids read back per chunk.** A chunk is N provisions with every child,
committed together, so a failure leaves whole documents and `--resume` picks up cleanly. The primary key
is an Oracle trigger and a SQLite autoincrement, and neither hands back a batch of ids, so each batch is
inserted and then read back by its `*_legacy_sys_id` — which is unique on every table, so the read-back
is exact. That map is what rebuilds `stc_spc_sys_id`, the link the whole reconciliation rests on.

**The rollups are the only computed money.** Everything else is copied as stored, per §1. Where a
computed invoice rollup disagrees with the legacy total by more than a cent, the run keeps its own
figure **and reports the row** — the argument with Finance is worth having in writing, not silently.

**`sbh_trans_type` is persisted after all.** `gap-analysis.md` §2.6 calls `transType` "screen state
only" and infers the source from whether `sbh_sah_sys_id` is set; the column is real and holds
`DIRECT` / `PROVISION` on every row. It is read, cross-checked against the parent id, and a disagreement
is reported rather than resolved quietly.

**Legacy did keep the voucher stamps.** §2.1 lists `spv_prov_voucher_date` as new, but
`sah_vcr_prov_cr_date` and `sah_vcr_adv_cr_date` are real columns — migrating a null over a date we have
would be a loss for no reason.

**Two passes have to wait for everything to be in:** `spv_doc_ref` (`SHPARR-2025000024` on 1,136 import
provisions) is resolved to `spv_ref_sys_id` once every provision exists, and step 12's closure re-derives
`CLOSED` from what the settlements actually settle. Then the number sequences are advanced past the
highest migrated number — §4 calls that the easy one to forget, and it breaks go-live.

**A refused run is a reported run.** Wrong source, missing target schema, target already backfilled: all
come back as a report the command prints and files, not as an exception someone has to catch to find out
what happened. Every error carries its file and line, because a run that fails on row 14,000 of 27,000
is debugged from the report.

### [DONE] T028 — Verification command + unique guards migration
**Refer:** `data-migration.md` §6, `schema.md` §12 file 22 · **Blocks:** T029
All ten V-checks with a pass/fail summary, plus the unique-constraints migration that runs after a green
backfill.
**Acceptance:** V1–V8 and V10 automated and green on a production copy; V9 signed off by Finance;
the guards migration succeeds only on clean data and says which rows block it otherwise.

**Landed 2026-09-08. 21 tests.** Suite 644 → 665. `finance:shipment:verify-backfill`,
`ShipVerificationService`, `ShipVerificationCheckData`, and the guards migration
`…_add_ship_unique_constraints.php` (`ship_unique_guards`). Every check is tested twice: green on a good
migration, red on one specific thing broken by hand afterwards — a verification only ever run against
correct data proves nothing.

**Green on a production copy, 2026-09-08.** Every automated check passed against the full rehearsal on
the dev server — V1's eight row counts exact, V2/V3 to the cent, V4's 8,198 links pair for pair. V9 still
needs Finance whatever happens; the report lists the 20 + 20 documents for them.

**Four of the checks were wrong until that run.** V2, V3, V4 and V8 compared *every* legacy row rather
than the rows the backfill was asked to take, so the cancelled project (Q15) failed them on a clean
migration. They now filter to the eligible trans codes, as V1 always did.

**It shares no query code with the backfill.** Deliberate: a migration that verified itself would be
marking its own homework, so each check asks both schemas the same question independently.

**"Should have been migrated" is not "every legacy row".** The cancelled project (Q15) and any row whose
trans code maps to no direction are left behind on purpose, and V1's expected counts subtract them —
including their invoices and lines, or the check would fail on every clean run.

**V5 compares document by document, not histogram to histogram** — a distribution can match while every
row inside it moved. The one move it allows is step 12's Confirmed → Closed; anything else fails.

**V4 checks the pairing, not the count.** A migration that linked every settlement line to the *wrong*
provision line has the right number of links, looks right on screen, and is wrong in every
reconciliation, so the check compares legacy line → legacy line through both schemas.

**V9 cannot pass itself.** `passed` is nullable and V9 is null: twenty documents of each kind, both
directions, printed with their legacy ids for Finance to open side by side. The report ends with two
unticked boxes — V9 and the guards migration — because a report that ticked them would be claiming
somebody had looked.

**The guards migration checks before it builds and names what blocks it.** ORA-01452 says a duplicate
exists, not which one; on a cut-over night that difference is an hour. Nulls are free on both databases,
so a direct settlement line, which settles no provision line, does not collide with the next one.

**The guard immediately found something real** — in a fixture, but the bug it points at is not: every
export provision quoted the same vendor invoice number, and a merged settlement drawn from two of them
produced two invoice rows with the same (vendor, number). The fixture is fixed; the design gap behind it
is **Q16** — `startFromProvisionLines()` groups by provision, so one forwarder invoice covering two
shipments cannot be settled as the one invoice it is.

---

## P4 — Cut-over

### [TODO — the execution] T029 — Cut-over
**Refer:** `data-migration.md` §8, `cutover-runbook.md` · **Blocks:** P5
Runbook executed, permissions granted, legacy screens read-only, daily verification for 30 days.
**Acceptance:** verification green on day 1 and day 7; no Finance-reported discrepancy open.

**Everything code can do for it is done, and the runbook has been walked end to end on dev
(2026-09-08). 12 tests.** Suite 665 → 677.
`cutover-runbook.md` is the executable form of §8; the daily reconciliation is scheduled; the rollback
guard §9 asks for is built.

**The task itself stays open, and will until somebody runs it.** It is a night's work with Finance in
the room: deploy the schema, seed and review the masters, rehearse, freeze legacy, migrate, verify,
grant permissions. None of that is a code change and none of it can be done from here.

**The decision list is clear as of 2026-09-10.** T001, T004, F9, F-INS1, Q14, Q16 and V9 are all
answered or waived, and the four things left in front of the night are not decisions:

- **Q6a** — the cut-over date. Nothing else in the runbook can be scheduled without it.
- **Q17** — Finance renumbers vendor `IS00697`'s duplicate invoice on production, through the legacy
  screen, before the run. A T-1d step now.
- **the accounts** — one query against production, re-verifying the numbers the dev copy already agreed
  with. It is the only part of the sign-off a copy cannot give.
- **V9 was waived**, so nobody will have compared a migrated document side by side in both systems. If
  there is a spare half hour on the night, that is where to spend it.

**The daily verification runs itself.** `finance:shipment:verify-backfill --daily` at 05:30 from the
Finance schedule, a no-op wherever nothing has been migrated — so it needed nobody to remember to start
it on cut-over night, and needs nobody to stop it on day 31. A failure notifies everyone holding
`finance-shipment-dashboard-view`, because a daily check nobody reads is theatre.

**The rollback now refuses to discard work.** §9 says it must, and T027 had not built it. "Modified"
cannot mean "has a modified timestamp" — the backfill copies the legacy one, so most migrated rows carry
a date from years ago. It compares against the **run id**, which is its timestamp, and names the
documents that have been touched since. `--force` is the way past it, and throws those edits away.

**One fix that reached outside the module.** `hm_emp_data` declares its columns in upper case; Oracle
folds them, SQLite does not, so an employee read from the test database had `HMEMD_NIK`, no
`hmemd_sys_id`, a null key, and therefore no permissions and no notifications. `HmEmpData` now folds
column names as it hydrates, which makes the test database behave like the one the code was written
against. It removes the workaround T025 had to carry, and the whole suite is green on it.

---

## P5 — Reports (Phase 2)

Each report = filter modal (Livewire) + queued job + export class + notification, per `spec.md` §8.

### [DONE] T030 — Report scaffolding
Shared filter trait (period defaults, ordered date pair, `resetFilters()`), the queued-job + download-link
notification wiring, the all-digit-as-text value binder.
**Acceptance:** one trivial report end to end: filters → job → file → notification with a working link.

**Done (2026-09-09). 15 tests.** Suite 677 → 692. What the six reports now inherit:

- `FiltersAShipmentReport` — the period in the address bar, defaulting to this month so far, and
  **ordered rather than rejected**: a person who types the end date into the start box has made a
  typing mistake, not a request for an error message. A blank or unreadable date falls back to the
  default, never to "no bound" — an accidental all-time run of a ninety-column report is a timeout,
  not a report. Not `PeriodDateRangeHelper`: that is the payroll month with its 26th cut-off, which
  is the wrong period for a customs report.
- `ShipReportExport` — the queued half. Where the file lands (`minio_private`), how long the link
  lives (a day), what the bell says when it is ready, and what it says when it is not. A timeout is
  named as such and asks for a narrower range, because "try again later" is the wrong advice for it.
  A disk that cannot sign falls back to a plain URL rather than failing a report already written.
- `WritesIdentifiersAsText` — **declared per column, not guessed per value.** An Aju number is 26
  digits and Excel rounds anything past fifteen, which turns a customs number into a different
  customs number. A heuristic cannot tell 16 digits of Faktur Pajak from 16 digits of rupiah, and
  oci8 hands decimals back as strings, so it would eventually turn an amount into text.
- `ShipReportEnum` + `ShipReportIndex` — one page listing all six, reached from the dashboard behind
  `finance-shipment-report-view`. `available()` asks the router, so a report lights up the moment its
  page is routed. Reports not built yet are shown greyed rather than hidden: the people waiting for
  them are the people reading the page.
- `shipment_report` log channel — who ran what over which period, and why one failed.

The end-to-end proof is a **stub report** (`tests/Fixtures/Shipment/Reports/`), not one of the six:
what is under test is the scaffolding all six share, and a stub tests it without waiting for T031.

### [DONE] T031 — Shipment / EMKL report
**Done (2026-09-09). 12 tests.** Suite 692 → 704. One row per provision invoice, cost type `SHIP` or
`EMKL`, 22 columns, no filters — the page is a description, a live row count and a Run button, because
the sheet is the whole history as legacy's was and Finance narrows it in Excel.

- **ESC and STA are joined into one cell**, not exploded into rows. A provision carrying two sales
  documents would otherwise appear twice and every total on the sheet would be doubled.
- **Export bills a customer, import buys from a supplier**, so those are one column, not two
  mostly-empty ones: the sheet carries both directions and the question is only ever whose shipment.
- Streamed with `FromQuery`, eager-loading the header, containers and docs — four queries per chunk
  rather than four per row.
- **Run against the dev copy of production data:** 2,261 rows, 22 columns, 2.2 seconds, and the
  document numbers arrive as text.

Two things the run turned up. A bug of mine: `ShipmentEmklExport` declared `identifierColumns()` but
never used the trait behind it, so trans no. was written as a number — the T030 test had called
`bindValue()` directly and passed on a value that Excel would have kept as text anyway. That test now
reads the written file, which is the only place the mistake shows. And **Q19**: the one non-IDR invoice
in the whole history carries its dollar figure in the rupiah column, in the legacy row as well as ours.

### [DONE] T032 — Bill report (both directions)
**Done (2026-09-09). 18 tests.** Suite 704 → 722. One row per settlement invoice, 13 columns, both
directions in one sheet — legacy ran two reports off two nearly identical queries and the direction is
now a filter. Filters: period, direction, vendor, invoice no., SI, EIN, Aju, BL, Faktur Pajak, pay
status, status. **Run against the dev copy: 2,280 rows for 2026 in 2.3 seconds.**

- **Checked against the legacy SQL, which is still readable** in `MST_PARAMS` on MGTAPPS
  (`SHP_EXPORT_BILL_REPORT_SQL`, `SHP_IMPORT_BILL_REPORT_SQL`). Two deliberate differences: paid means a
  pay date and not a payment voucher (`gap-analysis.md` B2 — see Q20 below), and the vendor filter also
  matches a vendor code, which legacy could not do.
- **The period is the invoice date**, falling back on the settlement's own date. `spec.md` §8 says
  `NVL(stl_bill_date, stl_trans_date)`; the legacy query filters and orders on the invoice date first,
  the acceptance is a row-for-row diff against the legacy sheet, and the invoice date is the right
  answer anyway — it is what the vendor is owed from. **`spec.md` §8's line should be corrected.**
- Half-open date range (`>= from`, `< to + 1 day`) rather than `BETWEEN`: these are Oracle DATEs, and a
  row keyed with a time on it falls out of the last day of a `BETWEEN`. That is how a report quietly
  loses a bill.
- **`overdue_days` is signed and empty where the question makes no sense** — paid, or no due date. Zero
  would read as "due today".
- The pay-status predicates moved into `FiltersByPayStatus`, shared with the settlement list, so the
  screen and the sheet cannot come to mean different things by overdue.

**Q20, found by running it, and fixed the same day.** 630 of 1,943 migrated settlements carried a
payment voucher and no pay date, so the sheet called them overdue — legacy's report counted the voucher
as payment, and the new rule cannot, because a pay date is the pay status. The backfill now reads the
voucher's date out of the ERP (`FT_PAYMENT_HEADER.PH_DOC_DT`, the join legacy's own query made), one
lookup per distinct reference per chunk — **170 vouchers cover those 630 settlements**. A keyed pay date
still wins; a voucher the ERP does not hold leaves the document unpaid rather than inventing a date.
Re-run on the dev copy 2026-09-09: **750 pay dates recovered**, settlements with a pay date 789 → 1,344,
provisions with a voucher and no date 195 → 0, the Bill report's overdue count **2,173 → 920**, and every
V-check still green.

### [DONE] T033 — Pending Bill report (Exim, both directions)
**Done (2026-09-09). 17 tests.** Suite 726 → 743. One row per provision × cost type × vendor, both
directions, 10 columns, opening on **unbilled and unpaid** — the question the sheet exists to answer.
Filters: period (provision date), direction, cost type, provision voucher, SI/Aju, vendor, pay status,
billing, status.

**Diffed against the legacy query row for row** (`MST_PARAMS.SHP_PENDING_BILL_REPORT_SQL`, run on the dev
copy with the same filters): **856 rows against 856**, and exactly one row differs — a PIB duty charge
where legacy printed the forwarder's name off the provision head and we print `KAS NEGARA`, which is who
the money actually goes to and what legacy's own `CASE` intended. Timing: 325 unpaid rows in 0.3 s.

- **Judged charge by charge, not per provision.** A provision is settled one vendor at a time, so its
  header almost always carries a settlement while other vendors on it are still waiting; legacy's first
  version dropped the whole shipment on its first settlement and took those vendors with it. There is a
  test for exactly that.
- **Grouped, not one row per invoice.** The first cut keyed on the provision invoice and produced 869
  rows against legacy's 856 — 13 provisions carry two invoices from one vendor for one cost type (Q16
  territory). On a sheet with no invoice-number column those read as duplicates, so they are summed, as
  legacy summed them.
- Most of legacy's `NVL`s are gone: the cost type and the vendor are always on the invoice now, and the
  vendorless duty invoices carry `KASNEG` as a real vendor from the backfill.
- `BILLED` and `ALL` are new — `spec.md` §8 asked for a billing filter and legacy had none at all.

### [DONE] T034 — Custom provision-vs-settlement report (~90 columns)
**Done (2026-09-09). 15 tests.** Suite 743 → 758. One row per charge, **95 columns**, provisioned next to
settled — the pivot source Finance build their own answers on. Filters: period, direction, SI, EIN, BL,
PO, customer/supplier, vendor, status. **Run against the dev copy: 11,101 rows over all history, 8,406
for 2026, written in 13 s.**

- **No full outer join, and none needed.** Legacy joined two unions; here it is every provisioned charge
  left-joined to the settlement charge that settles it, unioned with the settlement charges nothing was
  provisioned for (marked `DIRECT BILL`). `stc_spc_sys_id` is what makes that possible, and is what the
  column was added for. A charge appears exactly once — there is a test for that, because a wide sheet
  that double-counts is worse than no sheet.
- **The column list lives in one place** (`ShipCustomColumns`), read by both the `SELECT` and the
  sheet's headings. Ninety-five columns kept in two places would drift on the first change.
- **The vendor tax identity is merged, not joined** — `v_ship_vendor` is on the ERP connection. The
  first cut used `vendors()`, which is the picker's query and stops at fifty: on the dev copy that
  filled the NPWP columns for the first fifty vendors by name and left every other row blank. It now
  collects the codes the rows actually name (101 for 2026) and asks for those in one batch — 7,011 of
  8,406 rows carry an NPWP. A new `taxIdentities()` does the batch, chunked at 500 for Oracle's IN limit.
- **Account and customs-office codes are written as text.** Caught on the dev copy: `208023` came out a
  number, and `040100` would have lost its leading zero. A pivot keyed on a rounded account is nonsense.
- A run that cannot reach the ERP still writes the sheet, with those four columns empty.
- **Memory:** 374 MB peak for a year of data. Worth knowing before somebody runs it for five.

### [DONE] T035 — Import Details report (columns A→CV, banded styling)
**Done (2026-09-09). 14 tests.** Suite 758 → 772. Import only, **one row per provisioned charge** with the
settlement line padded alongside, **exactly 100 columns — A to CV**. Filters: period, cost type, status,
supplier, vendor, PO, Aju, BL, and the direct-bills switch. **Run against the dev copy: 5,644 charges
(6,113 with direct bills), 3,645 for 2026, written in 7.6 s at 239 MB.**

**The shape was the requester's call (2026-09-09).** `spec.md` §8's "provision, padded against its
settlement lines" reads two ways — a charge-level sheet, or one line per shipment with the cost types
totalled across it — and the two are different reports. Asked, and answered: **one row per charge**.
Q18c records it.

- **Duty charges show `KAS NEGARA`**, whether the row was keyed that way or filed against `KASNEG` by
  the backfill — 68 rows on the dev copy. Legacy printed the same words into an empty column.
- **The banding is on the header, not on every row.** Five blocks — the shipment, the provision invoice,
  the provisioned charge, the settlement, the settled charge — each tinted, with the header row frozen
  and filtered. Banding every row means a style object per cell: on ten thousand rows that is minutes of
  work and a far larger file, for a sheet people filter immediately anyway.
- **The hundred columns do not move when the direct-bills switch does.** With it off the second half of
  the union is still there, asked for nothing — a report whose shape depends on a checkbox is a report
  whose columns move.
- Filtering by supplier or PO leaves the direct bills out: a settlement with no provision behind it has
  neither.

### [DONE] T036 — GRN Details report
**Done (2026-09-09). 14 tests.** Suite 772 → 785. GRN header × item straight from `mgtdat`, 29 columns
(A→AC), opening on **the current year** as `spec.md` §8 asks — a receipt is checked against a
declaration months after the fact. Filters: period, supplier, GRN, PO, item, BL, Aju, PIB, and a switch
for local receipts. **Run against the real ERP: 1,228 import receipt items for 2026 in 2.6 s, 19,584 all
time.**

- **A goods receipt carries no BL, Aju or PIB.** Measured on production: `gh_bill_lad_no`,
  `gh_awb_bill_no` and `gh_container_no` are empty on all 100,170 rows and `gh_boe_no` on all but 17.
  So those three filters resolve the shipment to the **purchase orders it covers** and ask the ERP about
  those. Proved end to end on the dev copy: BL `EGLV143552970566` → order 2025000337 → 2 receipt rows.
- **`IPO-2025000337` and `2025000337` are the same order.** A provision stores the printed form, the ERP
  holds the number alone in a NUMBER column — handing it the printed form is not an empty sheet, it is
  an ORA-01722. Both the resolution and the PO filter split on the last hyphen now, as the voucher
  references do.
- **Import receipts only, by default.** Nothing on the receipt says it is an import; the order it
  fulfils does (`gh_ref_txn_code = IPO`). That separates 1,228 rows from 7,941 for 2026 — the difference
  between a customs cross-check and a warehouse dump.
- **`whereDate`, not a half-open range**, on `gh_dt`: Oracle keeps a time on a DATE, SQLite writes
  `YYYY-MM-DD` and compares it as a string, so `< the day after` let the day after in. Caught by the
  test, would not have been caught in production.
- The stand-in copies only the 29 columns the report reads, not all 259 the two ERP tables have.


**Acceptance for each:** run for a period that predates cut-over and diff the sheet against the legacy
output — same rows, same values, same column order. Any deliberate difference is listed in the PR.

---
---

# Exim Front Office

New development, not a migration (`PRD EXIM.md`). Needs **T009A** and **T011** from the Shipment Control
track and nothing else. Commit scope stays `finance`.

## P6 — Front-office foundation

### [PART DONE] T037 — Settle the front-office open questions
**Refer:** `open-questions.md` §2–§5, `plan.md` §1 D17–D19 · **Blocks:** the tasks each row names
Clear at minimum the ones that block P6 and P7: **S3a** (which ESC field holds the freight rate),
**D14a** (the `FIX` band boundary default), **D17** (how kurs pajak is loaded), **F1** (what Finance does
when the target period is closed), **F2** (cancellation-fee accounts), **O6** (which BIMs migrate).
Write each answer into the document it blocks, then move the row to `open-questions.md` §6 with the date
and who decided.
**Acceptance:** every row that a P6/P7 task names is either answered or explicitly deferred with the task
it now blocks recorded against it. No row is left silently open.

**Researched 2026-09-10, and five of the six named rows came back answered by the data rather than by a
meeting.** The method was T001's: ask production before asking a person.

| Row | What production said |
|---|---|
| **D14a** — the `FIX` band boundary | **Closed.** The ladders do share their edges (`1–50, 50–100…`), but **nought** priced legacy lines sit on one, and the two laddered activities — 203 TRUCKING, 14 bands — have **nought cost lines at all**. The default stands with no history riding on it |
| **D17** — how kurs pajak is loaded | **Closed, and it removes a data-entry job.** The ERP already keeps it: `FM_EXCHANGE_RATE_KMK_MGT` holds 669 weekly KMK decrees since 2013 (number in `kmk_flex_01`), and `FM_EXCHANGE_RATE` type **`T`** holds the rate for the same windows — 597 weeks match exactly. So T041 reads the ERP behind a repository and `ship_kurs_pajak` seeds **empty**, as the fallback for a currency the ERP's table lacks (today: everything but USD) |
| **F1** — the closed-period policy | **Closed as posed, and replaced by a real one.** No period in this ERP is ever closed — nought of 96 rows for company `002` carries a close date — and the posting code only tests that a period *exists*. What does stop a posting is a **missing** one: periods stop at 2026-12-31 and `fm_tran_doc_no` has nothing for 2027. Now a T-1d runbook step |
| **F3** — the BM KITE account | **Closed. `401100`**, on every facility: 218 of 219 legacy duty lines, KITE and KITE PENGEMBALIAN included (one stray on `110501`). The report grain stays open |
| **F8a** — multi-ESC commission | **Measured: it has never happened** — nought of 7,873 EINs reference more than one contract. Still open, because a multi-BIM SI is what `PRD EXIM.md` §4.6 asks for, but it is a new case rather than a migration risk |
| **F2** — cancellation-fee accounts | **Half answered.** Legacy's master has them — `BOOKCANC` → `404004`/`208008` and `CANCELFEE SHIPPING` → `404033`/`208011` — and **nought lines ever used either**, so PPN-bearability has no evidence. Finance still to confirm, and to say whether the two legacy fees collapse into one cost type |

**S3a, S4, S5, S6 and O6 were then answered the same day** by the export team and sales, off the back of
those measurements — the whole `S` block is now empty:

| Row | Answer | What it changes |
|---|---|---|
| **S3a** | The freight rate is **typed by the export team when they book the vendor** | **Reverses `PRD EXIM.md` §4.5's "pulled, not typed".** The ESC value is shown beside the field as a reference and never written; pulling would have stamped a pre-booking figure onto the baseline every margin comparison is measured against. T051's acceptance now refuses a save with no rate keyed |
| **S4** | **Warn**, never block | Show the rates found, set `sbm_review_pending`, do not average. No separate queue owner: keying the booked rate is what clears it |
| **S5** | **Per customer**, edited on the BIM when needed | `SBT_CUSTOMER_CODE` stays unique. Three of 423 customers ship to more than one address; a second key column would serve those three and change nothing for 420 |
| **S6** | **Confirmed only** — `soh_appr_status = 3` | The picker offers 5,848 of 6,134 ESCs. `soh_clo_status` is not filtered on: it is set on 106 rows in fourteen years |
| **O6** | **The last twelve months, by ESC date** | A date, not a status, and re-run on the cut-over day rather than frozen into a list. 447 ESCs across 76 customers as measured |

The shape `PRD EXIM.md` §4.5 assumed holds in the data, even though its conclusion did not:
`soh_flex_01` is the Inco Term on 6,123 of 6,134 ESCs with six values (CIF 4,505 / FOB 959 / CNF 353 /
CFR 286 / FOBEXWORKS 19 / FAIR 1 — which independently corroborates F-INS1's answer), and `soi_flex_10`
carries a number on 4,895 items that reads as USD per container. The ERP's own `soh_shipment_term`,
`soh_loading_port` and `soh_destination_port` are **empty on every ESC**, so the flex columns are not a
shortcut, they are the only record. Rates disagree within an ESC on 25 of 4,717 (S4); 3 of 423 customers
ship to more than one address, BEKAERT DESLEE to seven (S5); and `soh_appr_status` is the only status
worth filtering on (S6).

**`plan.md` D17 is settled** as its default: one Shipment section in `CLAUDE.md` with a front-office
subsection. D18 and D19 keep their defaults and settle in T044 and T052, as that table says.

**And the research found a defect, the same class as the `MGT` company code: F-PAY2.** Three export cost
types were seeded with the payment tran code `ADVP-EXP`, which **the ERP has never held** — nought of
276 `fm_tran_doc_no` rows, nought of 65,434 payment vouchers, absent from its tran-code list. The first
export advance would have thrown at confirm. Legacy raised `ADVP` (478) or `BPS` (510), and no export
provision ever raised an advance at all. Fixed: `ADVP` on EDN / INS / COMM, the case **removed** from
`ShipVoucherTypeEnum` so the master page cannot offer it, `in:ADVP,BPS` on the cost-type DTO, and a
seeder test pinning the configured codes to the enum. Suite 813 → 815.

**What is left for T037 is the part no query and no measurement can reach:** F2's PPN answer and which
of legacy's two cancellation fees survives, F3's report grain, F8a's multi-ESC commission rule, and the
whole of D15, D18, F4, F5 and O1–O5 — ownership, thresholds, and policies about cases that have
not happened yet. (**D16 and F6 left this list on 2026-09-15**, answered with T050.) **None of them blocks P6 or P7 starting**, which is what this task's own list was for:
of the six rows it named as gating, five are answered and F2's accounts are known with only the PPN
question outstanding.

### [DONE] T038 — Front-office master migrations + seeds (6 tables)
**Refer:** `schema.md` §12 files 23–28, §14 · **Blocks:** T039
`ship_kurs_pajak`, `ship_hs_tariff`, `ship_hs_preference`, `ship_doc_type`, `ship_transit_time`,
`ship_skb`. Blueprint on `oracle_mgthris` with `migrationKey` + `migrationDisabled()`, audit columns,
the unique keys from §14.
Seeds are **what is actually in use**, not a complete reference set: the four active preference schemes
(ACFTA, AIFTA, ATIGA, RCEP), the document types of §11.6 with their official customs codes, transit
times at country level with per-country notify lead days, and the current SKB. `ship_hs_tariff` may seed
**empty** — that is the design (`plan.md` D16).
**And so may `ship_kurs_pajak`** (D17, answered 2026-09-10): the ERP already holds the weekly KMK rate —
`FM_EXCHANGE_RATE_KMK_MGT` for the decree, `FM_EXCHANGE_RATE` type `T` for the rate — so this table is
the fallback for a currency the ERP's table does not carry, which today is anything but USD. **Do not
seed KMK rates that a repository can read.**
**Acceptance:** `migrate` green on Oracle and SQLite; rollback clean; the document-type seed produces the
exact code list in `spec.md` §11.6; a `ship_hs_preference` lookup distinguishes RCEP from ACFTA for the
same Chinese HS code.

**Done (2026-09-10). 12 tests.** Suite 815 → 827. Six Blueprint migrations on `oracle_mgthris` with the
first wave's `migrationKey` / `migrationDisabled()` pattern, plus a seventh for the key sequences, and
`ShipmentFrontOfficeMasterSeeder`. The rollback half of the acceptance is a real test rather than a
claim: it calls every `down()`, asserts the six tables and their sequences are gone, then calls every
`up()` again — `RefreshDatabase` gives it a database of its own to do that in.

**Only `ship_doc_type` is seeded, and the other five are empty for five different reasons** — which the
seeder's docblock states in a table, because "no seed" and "forgotten seed" look identical six months
later. `ship_hs_tariff` is empty by D16 and `ship_kurs_pajak` by D17. The other three could not be
seeded honestly:

- **`ship_hs_preference`** is keyed on (HS code, origin country, scheme). The four live schemes are a
  *vocabulary*, not rows — a row needs an HS code, and inventing those would put a wrong BM% in front of
  a duty estimate. This task's wording ("seed the four active preference schemes") cannot be met as
  written, and should not be.
- **`ship_transit_time`** needs days and a notify lead time per country, which is the import team's
  knowledge and written down nowhere. **The ERP cannot supply it either:** `om_supplier` has no country
  column at all and `om_supplier_address` keeps the country in an unlabelled `SA_FIELD_nn`. Measured
  while looking — worth knowing, because it also means the BL's origin country must be keyed rather
  than derived from the supplier.
- **`ship_skb`** — two certificate numbers are known (`KET-00014/…` of 2026-05-04, `KET-00025/…` of
  2026-08-04) but **not their validity dates**, and validity is the entire point of the record. A
  guessed `SSK_VALID_TO` understates a Rp 1.4 billion BL by ±Rp 35 million.

**Eleven document types, and deliberately not fourteen.** §11.6 lists fourteen checklist documents but
gives official customs codes for eleven; the packing list, the PO, the COA, the MSDS, the surveyor
report, the Surat Kuasa PPJK and the phytosanitary certificate have none, and **a made-up code is worse
than a missing row** — it looks official on a checklist and matches nothing on the PIB. Those arrive as
rows when the import team supplies their codes. `is_mandatory` is also false on every preference
document and on the SKB: "mandatory when claiming a preference" cannot be said in the `APPLIES_*`
columns and should not be, because what makes a Form E mandatory is the user choosing that scheme —
which is why `ship_hs_preference` carries the doc code and T048's generator raises it. `998` (KITE) is
the one condition the master can express alone, so it carries `applies_facility = 'KITE'`.

**Two deviations from `schema.md`, both recorded there:**

1. **The key sequences ship with these tables, not with file 41.** §12 lists one front-office trigger
   migration after the seventeen transaction tables, but a seeder cannot insert into an Oracle table
   whose key generator arrives two tasks later. File 41 keeps the transaction tables, which nothing
   seeds.
2. **`ship_transit_time` has a lookup index, not a unique key.** I added the unique key first and wrote
   a test asserting it stopped a duplicate country row. It does not, and cannot: "any port" is null, SQL
   treats nulls as distinct, so the key would guard the port-pair rows and quietly let the country-level
   ones double up — the case that matters most, since country rows are what works on day one. **A guard
   that misses the common case is worse than none**, because the next reader trusts it, as I did for
   about a minute. The duplicate guard moves to T043's validation, where a null means what it says, and
   the test now pins the honest behaviour so nobody re-adds the key believing it covers both.

**Not here:** models and factories (T040, T041), so the seeder writes columns through the query builder
rather than attributes. For eleven rows of published reference data there is no model behaviour to
borrow, and it lets the migrations and their seed land together.

### [DONE] T039 — Front-office transaction migrations (17 tables)
**Refer:** `schema.md` §12 files 29–39, 41, §13 · **Blocks:** T040
BIM + 3 children, import file + 4 children, SI + 5 children, payment request. Cascade deletes on every
child FK; the FK constraints deferred by T009A added here; the check constraint on
`ship_payment_request` (exactly one parent); the Oracle-only sys-id trigger migration with the
`sqlite` early return.
**Acceptance:** `migrate` green on both drivers; on Oracle an insert without a PK gets one from the
trigger; the unique guards actually bite — a second BL with the same `SIF_BL_NO` is rejected, so is a
second `SIF_AJU_NO`, so is a second SI with the same `SES_EIN_NO`; a payment request with both parents
null **and** one with both parents set are both rejected.

**Done (2026-09-10). 13 tests.** Suite 827 → 848. Eleven table migrations (files 29–39), the sys-id
triggers (file 41) and the deferred foreign keys, all following the first wave's `migrationKey` /
`migrationDisabled()` pattern. Every unique guard the acceptance names has a test that inserts the
duplicate and expects the refusal, and `down()` is exercised the same way T038's was: drop everything,
assert the seventeen tables and their sequences are gone, put them back, then check the payment-request
guard came back **with** the table rather than only the table.

**The "exactly one parent" check needed two implementations, not one.** Oracle takes a check
constraint; **SQLite cannot add a constraint to an existing table at all**, only rebuild it. Skipping
SQLite was the obvious shortcut and the wrong one — the acceptance test for this rule runs in CI, and a
guard that exists only in production is a guard nobody has watched work. So SQLite gets a pair of
triggers, `before insert` and `before update`, raising an abort. The update half is not padding: the
insert guard alone would let an edit add the second parent a moment later, and there is a test for
exactly that.

**Two things CI cannot reach, named rather than papered over:**

- **"An insert without a PK gets one from the trigger"** is Oracle-only — CI has no PL/SQL and there the
  rowid fills the key. What the tests assert instead is that all seventeen tables have a registered
  `HM_MST_SEQUENCES` row with `TM9` and `seq_type = 0`, which is what the trigger and `SysIdHelper` both
  read; a missing row is precisely the failure that passes in CI and breaks in production.
- **The four cross-wave foreign keys are Oracle-only**, for the same SQLite limitation. T009A deferred
  `SPV_SIF_SYS_ID`, `SPV_SES_SYS_ID` and `SPC_SEC_SYS_ID` because their targets did not exist; they are
  created here along with `SEC_SPV_SYS_ID` pointing back, so all four joins between the two waves are in
  one file. The gap is real — a broken parent id is refused in production and accepted in CI — so the
  test checks what CI can: that the columns exist and are named as the joining code expects.

**`ShipmentSchemaIdentifierTest` caught a genuine one, for the second time.**
`SBM_CONTRACT_FREIGHT_QUOTED_DATE` and `..._VALID_UNTIL` are **32 characters**, and Oracle 11g stops at
30 — the names came straight from `schema.md` §13.2, so the doc was naming columns Oracle cannot hold.
They are `SBM_CONTRACT_QUOTED_DATE` and `SBM_CONTRACT_VALID_UNTIL`, and §13.2 is corrected.

**One index differs from §13 on purpose:** the partial indexes it describes (`where = 1` on
`SBM_REVIEW_PENDING` and `SEC_NO_TARIFF_MATCH`) are Oracle-only syntax, so both are plain indexes on a
boolean column. The queue queries filter on the value either way.

### [DONE] T040 — Front-office enums, models, permissions
**Refer:** `design.md` §2, §8, `spec.md` §10.3–10.5 · **Blocks:** T041
12 front-office enums (`ShipTariffUomEnum` already exists from T011) and 23 models — 17 transaction + 6
master — with connection, PK settings, casts, `$fillable`, `$searchable`, relations,
`Searchable` + `LogsActivityWithDescription`. The Shipment Control models gain `importFile()`,
`exportSi()` and `siCosts()`. Extend `FinanceRolesAndPermissionsSeeder` with the front-office
permissions and the five role grants in `design.md` §8.
**Acceptance:** `ShipImportFile::with('invoices.items', 'containers', 'docs', 'skb')->first()` works
against a factory row; the same for an SI with `bims`, `costs` and `edns`; `ShipSiStatusEnum` allows
`CANCELLED` only from `BOOKED` and `CONFIRMED`; `ShipPaymentRequestStatusEnum` has no `REJECTED` case;
permissions seed idempotently.

**Done (2026-09-10). 17 tests.** Suite 848 → 865. Twelve enums, twenty-three models (17 transaction +
6 master), `importFile()` / `exportSi()` / `siCosts()` on `ShipProvision` — plus `siCost()` on
`ShipProvisionCost`, the inverse of a relation `design.md` §2 names only from the SI side and which the
posting service will read from whichever end it holds. Twenty-one front-office permissions and four new
roles on `FinanceRolesAndPermissionsSeeder`.

**Factories are T041, so the tests build their rows with `create()`.** Deliberate: a test that an eager
load resolves should fail on a wrong foreign key, not on a factory that has not been written. Both
acceptance trees — `invoices.items` / `containers` / `docs` / `skb`, and `bims` / `costs` / `edns` —
are asserted against real rows.

**`CPT` is why the incoterm enum answers two questions, not one.** §11.7 gives Mode A as
(`CIF`, `CIP`, `CFR`, `DAP`, `DDP`) and Mode B as (`FOB`, `FCA`, `EXW`) — fourteen incoterms in nine
cases, and **`CPT` is in neither list**. §11.8's verified table has it: the seller pays the carriage but
not the insurance, so the premium is still added to the customs value. A single mode letter cannot say
that, so `ShipIncotermEnum` exposes `addsFreight()` and `addsInsurance()` separately and derives
`cifMode()` from the first. §11.7 now says so out loud, because reading the mode alone on a `CPT`
shipment silently drops the premium — a quiet under-declaration, which is the expensive direction.

**Incoterm, transport mode and free-time basis are NOT cast to their enums, and the statuses are.**
A backed-enum cast throws on an unrecognised value, and migrated BIMs carry exactly that: `CNF` where
`CFR` belongs, the word `Regular` where a basis does. A cast would turn a row flagged for review into a
fatal error on the list page that shows the review queue. So those three are read through
`incoterm()` / `transportMode()` / `freeTimeBasis()`, which map what they can (`CNF` → `CFR`) and
return null otherwise; `sbm_review_pending` is what asks a human to look. Statuses, `sit_bm_source`,
`sit_duty_type`, `sit_preference_scheme` and `sbc_rule_type` are ours to write, so they are cast.

**`ShipImportItem::file()` is a method, not a relation.** `design.md` §2 lists it as "through the
invoice", and Eloquent has no `belongsToThrough`; `hasOneThrough` runs the join the wrong way round for
a child reaching its grandparent. It returns `$this->invoice?->file`, and a list eager-loads
`invoice.file` — which is what any honest implementation would have done anyway, only without the
relation's promise that it can be eager-loaded on its own.

**The four front-office role names are decided here, not in `design.md`.** §8 names the *people* —
"Sales / Marketing", "Import staff", "Export staff", "Purchase" — and never fixes a spelling, so the
seeder settles it: `Exim - Sales`, `Exim - Import`, `Exim - Export`, `Exim - Purchase`, following the
`Lc Control - Admin` / `CI Project - Viewer` shape the app already uses so a module's roles sort
together in the picker. Finance is not among them — it keeps its grant and gains
`-payment-request-pay` and `-payment-request-view`, since paying is theirs and requesting is not.
`Exim - Purchase` holds `-import-file-view` and nothing else, which O5 may yet remove; the test pins
that its grant is exactly one permission, so widening it has to be deliberate.

**Idempotency is asserted on the roles as well as the permissions.** Seeding twice and counting
permissions is the easy half; `findOrCreate` on a role is the half that would have gone unnoticed, so
the test also counts four `Exim - %` roles after the second pass.

**Not here:** repositories, services and DTOs for any of this (T042 onward), and the
`AppliesShipDirectionAuthorization` grant checks — the permissions exist, nothing reads them yet.

### [DONE] T041 — Factories + `EximFixture`
**Refer:** `design.md` §9 · **Blocks:** T042
Factories for all 23 tables plus a fixture helper building a realistic BL (1 supplier, 2 invoices, 5
items across 2 POs, mixed HS codes and one item on a preference scheme) and an SI drawn from 2 BIMs with
baseline freight + EMKL lines.
**Acceptance:** both fixtures build on SQLite in under a second; the BL fixture's item values are the
ones the T044 tests expect, so the estimator tests and the screen tests cannot drift apart.

**Done (2026-09-12). 34 tests.** Suite 877 → 911. Twenty-three factories in
`Modules/Finance/database/factories/Shipment/` and `EximFixture` beside `ShipmentFixture` in
`Modules/Finance/tests/Fixtures/Shipment/`.

**The BL's duty figures are published as constants, and a test derives them from the fixture's own
rows.** `EximFixture::EST_DUTY_BM` (11,676,000), `EST_DUTY_PPN` (28,837,116), `EST_DUTY_TOTAL`
(40,513,116) and `EST_TOTAL_NO_PREFERENCE` (42,396,764) are worked out by hand from `spec.md` §11.7, and
`ShipFrontOfficeFixtureTest` walks §11.7 over the fixture's invoices and items to check that the inputs
really produce them. T044 replaces that arithmetic with `ShipPibEstimator`; until then it is what stops
the published figures and the fixture drifting apart the moment someone edits a quantity. Every input is
chosen so the numbers come out whole: Σ value in IDR is exactly 248,000,000 and the premium is exactly
1% of it, so an allocation error shows up as a round number rather than as a rounding argument.

**The BL is `FOB`, so it runs Mode B.** Mode A adds nothing, and a fixture that never adds freight or
insurance to the customs value would let a merged-mode estimator pass. The RCEP item's
`sit_bm_pct_fallback` is 10%, which is the whole of the difference between the two exposure figures —
Rp 1,883,648 on this BL.

**No factory fills a computed column.** `sif_est_*`, `sit_value_cif_idr`, `sit_nilai_impor` and the four
duty columns per item are the estimator's output, so a factory that pre-filled them would let a broken
estimator pass; the fixture leaves the BL at `DRAFT` with the estimate empty, and a test asserts that.
`ShipExportSiCostFactory` is the exception that proves it: its amounts come from
`CalculatesLineAmounts`, the same trait the provision and settlement DTOs use, rather than from a fourth
copy of the formula.

**`ShipPaymentRequestFactory` defaults to a BL, because the table has a check constraint.** "A payment
request belongs to exactly one BL or one SI" — so declining to pick a parent is not a neutral default,
it is a row the schema refuses. `forExportSi()` clears `spr_sif_sys_id` as it sets the other key; that
is the pairing the constraint exists to catch.

**Incoterm, transport mode and free-time basis are written as strings**, matching T040's decision not to
cast them. `ShipBimFactory::legacy()` is the row that proves it: `CNF` where `CFR` belongs, the word
`Regular` where a basis does, `sbm_review_pending` set, and nothing that throws.

**`EximFixture::parameters()` is opt-in and seeds eight rows, not fourteen.** The authority for the full
list is `ShipmentControlMasterSeeder`; what the fixture carries is the subset the published figures
depend on, so that changing one of them is visibly a change to the constants. `masters()` delegates to
`ShipmentFixture::masters()` for ports, container types, cost types and posting accounts — there is no
second set of those — and seeds only what the front office adds.

**Not here:** DTOs and the validation gates (T042), and any repository or service for the front office.

### [DONE] T042 — Front-office DTOs
**Refer:** `design.md` §3, `spec.md` §11.5 · **Blocks:** T043
Nine DTOs, `Wireable`, with the validation gates from `spec.md` §11.5 as named cases, and the blank
numeric normalisation rule. Computed fields (`sit_value_cif_idr`, the duty columns, `ses_chargeable_kg`,
`sif_eta_calculated`) are **read-only outputs**, not bindable inputs.
`ShipExportSiCostData` uses the **same** `CalculatesLineAmounts` trait as the provision and bill line
DTOs — do not write a fourth copy of the line formula.
**Acceptance:** Pest: each gate passes valid data and rejects each required field; a blank string in a
numeric field does not throw; attempting to set a computed field from the form is ignored, not persisted.

**Done (2026-09-12). 90 tests.** Suite 911 → 1001. Nine DTOs in
`Modules/Finance/app/Data/Transaction/Shipment/`, all `Wireable`, all using `CalculatesLineAmounts` for
`numeric()` — which is the blank-input rule, not an afterthought: Livewire hands a cleared input back as
`''` and a non-nullable `float` property fatals on it.

**The gates are cumulative, and each one is the previous plus what the step needs.** `header` →
`estimate` → `submit_pib` → `clear` on the BL, `header` → `confirm` → `ship` → `provision` on the SI.
A BL therefore cannot reach `SUBMITTED_PIB` without satisfying `estimate`, which is the property that
makes the estimate blocker worth having.

**Two payload flags default to the strict reading, for the same reason `ShipProvisionData`'s cost-type
flags do.** `skb_valid_at_pib_date` defaults to **false**, so a caller that forgets to pass it gets a
refused exemption rather than an unchecked one — missing a quarterly SKB expiry understates a Rp 1.4
billion BL by around Rp 35 million. `is_additional` on `ShipExportSiCostData::approvalRules()` defaults
to **true**, so an unflagged line is asked for the offline approver and reference rather than quietly
waived.

**The expired-SKB refusal is a `declined` rule on `pph_exempt`.** The DTO must not reach for the
certificate, so the service answers "is it valid at the PIB date" and the rule set turns a false into a
refusal of the flag that claims the exemption — with a message that says which, rather than a field
error nobody can read.

**`remaining_qty` is a payload value, and its absence means no cap.** The remainder is a query across
every other BL for that PO line (§11.4) and the DTO cannot run it. When it is absent the quantity is
only checked for being positive: inventing a cap would be worse than leaving it off, because it would
pass in a unit test and refuse real work.

**The import HS code is normalised in `fromForm()`, not in a rule.** A typed `hs_code` wins; otherwise
the PO's string is normalised; if what is left cannot be eight digits, `hs_code` stays **null** rather
than a guess, and `hs_code_po` keeps the original. §11.4 is explicit that the PO's HS string is a
reference and never a lookup key.

**`ShipBimData` has two gates, because a migrated BIM is not a typed one.** `bimRules()` holds incoterm,
transport mode and free-time basis to their enums; `legacyRules()` accepts `CNF` and the word `Regular`
as plain strings and lets the consignee be missing, keeping `legacy_body` verbatim and
`review_pending` set. Refusing those rows would mean not migrating them at all, which is strictly worse
than a flagged row a person fixes. T052 uses the second gate.

**`ShipDocChecklistRowData` serves both sides.** `ship_import_doc` and `ship_export_si_doc` are the same
six columns under two prefixes, so there are two `to…Attributes()` methods rather than two classes —
the shape `ShipDocData` already uses for the provision and the settlement.

**The SI's `shipRules()` asks for the document the transport mode actually produces**: a B/L at sea, an
AWB by air, a tracking number by courier (§11.2). A gate that demanded "a BL" of a courier shipment is a
gate people learn to work around.

**`clearRules()` takes the strict reading of O4, which is still open.** The checklist must be complete
unless an explicit `doc_override_reason` is passed — the override §11.5 already provides for. That
reason has **no column**; it belongs in the activity log. If O4 comes back as "advisory", the fix is one
rule, not a redesign.

**Not here:** the collection-level rules — at least one invoice, at least one item per invoice, invoice
numbers distinct within the BL, the BL number unique across the table. Every one of them needs either a
query or the whole collection, and a rule set that pretends otherwise passes in a unit test and fails in
production. They belong to the services (T047, T053), exactly as `design.md` §3 says.

### [DONE] T043 — Front-office master CRUD pages (6)
**Refer:** `design.md` §6, root `CLAUDE.md` master-CRUD recipe · **Blocks:** —
HS tariff, HS preference, kurs pajak, document type, transit time, SKB — list + create/edit + delete +
export/import, routes, breadcrumbs, permission `finance-shipment-master-manage`.
**Kurs pajak is a much smaller page than this task assumed.** D17 was answered on 2026-09-10: the ERP
holds the weekly KMK rate already (type `T`, with the decree in `FM_EXCHANGE_RATE_KMK_MGT`), so the page
is not where the weeks are keyed — it is where a currency the ERP does not carry gets one, and where
what the ERP says is **displayed read-only** so a user can see the rate a document will use. The Excel
import is worth keeping for that fallback, but it is no longer the point of the page.
**Acceptance:** each page CRUDs a row; overlapping validity periods for the same currency are rejected
with a message naming the existing row; a document type in use by a checklist cannot be deleted, only
deactivated; an SKB whose validity has passed cannot be marked active; breadcrumbs give each page its
tab title.

**Done (2026-09-12). 45 tests.** Suite 1,001 → 1,046. Six DTOs, six services, six Livewire pages and
views, four filter partials, six routes and six breadcrumbs — all on T012's `ManagesShipMaster` trait and
`AbstractShipMasterService`, so the list, the search, the form modal, the delete dialog and the export
are the same code the eight Shipment Control masters already use. What each page adds is its fields and
its own rules, which is the whole point of that trait existing.

**The overlap guard is one trait used four times, not four copies.**
`Concerns\GuardsValidityPeriods::assertNoOverlap()` serves HS tariffs, HS preferences, kurs pajak and
SKBs, because all four answer "what applied on this date" and all four break the same way: two rows
covering one day means the answer depends on row order, which is a wrong number rather than an error.
SQL cannot express it — the unique keys catch an identical `valid_from` and nothing else. **A null
`valid_to` is an open-ended window**, so it overlaps everything after its start; treating it as a point
in time is the mistake the trait exists to avoid, and a test pins it.

**Every refusal names the row it clashes with.** "EUR already has a rate of 20,710.98 for 01 Jul 2026 to
07 Jul 2026 (KMK 39/MK/EF.2/2026)" rather than "duplicate" — the tests assert on those fragments, so a
message that stops naming the clash fails.

**Cross-field rules need the `form.` prefix, and that cost a round of red tests.** The master pages
validate under a `form` array, so `after_or_equal:valid_from` resolves against the root — where there is
no such key — and the rule silently never fires. `ShipTariffData` had already hit this and written
`after_or_equal:form.eff_from`; the five new rules follow it. `ShipKursPajakData::importRules()` strips
the prefix back off, because the import validates a flat array and a prefixed reference there is the
same trap facing the other way.

**Kurs pajak is the only one of the six with an import, and the only one that reads the ERP.** D17 makes
this table a fallback, so the page leads with a callout saying so and shows
`FM_EXCHANGE_RATE` type `T` for the currency and week on the form — a warning appears when the ERP
already carries a rate, since a document will read that one in preference. The Excel import exists
because this is where a run of weekly rows for a new currency gets keyed; it applies rows **one at a
time through the service**, so the overlap rule holds, one bad row costs one row, and each failure is
reported by the spreadsheet line number. An import that rolled everything back over a single bad date is
one nobody uses twice.

**Transit time's duplicate guard is hand-written, not the overlap trait.** It has no validity period —
what makes two rows the same is the route, and "any port" is null. SQL treats nulls as distinct, so a
unique key would guard the port-pair rows and quietly let the country-level ones double up, which is the
half that every shipment falls back to. The form also asks for **both ports or neither**: half a pair is
a country rule with a misleading label.

**The SKB rule is stated twice on purpose.** `save()` refuses an expired certificate marked active, and
`activate()` refuses turning one back on — the same decision reached two ways, and leaving either open
means a BL claims an exemption it does not have, understating the estimate by rate × Nilai Impor with
nothing on screen looking wrong.

**Deletes follow the module's existing rule and each master says what points at it.** A document type on
any checklist or named as a preference's proof; an SKB a BL claims; a kurs pajak week a BL has
snapshotted; an HS rate with preference rows keyed against it. All four deactivate instead, and the
dialog explains in the service's own words.

**No navigation link, matching the eight masters already there.** The sidebar is `CmMenu`, a database
table maintained through Core's menu master, not code — so the six pages need a menu row adding at
deploy time, exactly as the Shipment Control masters did. Nothing to write here, but it is a cut-over
step rather than a no-op.

**Not here:** `ShipKursPajakService` is the master page's service only. The resolution rules T046 owns —
the rate following the **PIB registration date**, the snapshot, the recompute that keeps history — are
not in it; `erpRate()` is a read for display and nothing more.

---

## P7 — Import BL

### [PART DONE] T044 — `ShipPibEstimator` + `ShipDutyRounding`
**Refer:** `spec.md` §11.7, `PRD EXIM.md` §6.5, §6.9, §12.1 · **Blocks:** T045
Pure classes, no DB. Mode A and Mode B, per-invoice discount and freight allocation, BL-wide insurance
allocation **in IDR**, per-item calculation summed per invoice then per BL, and the three rounding rules
(`CEIL_1000` BM, `TRUNC_1` PPN, `FLOOR_1000` PPh base) driven by parameters.
**The mode is derived from the incoterm and is not injectable** (`plan.md` D18) — a caller must not be
able to price a CIF shipment in Mode B.
**Build this before any screen.** It is the one class in the module that can be proved against paper.
**Acceptance:** Pest against **10 issued PIBs**, fixtures under
`Modules/Finance/tests/Fixtures/Shipment/pib/`, each named for its document number: BM, PPN and PPh match
**to the rupiah**, except the two figures known to differ by ≤ Rp 4 (asserted with a tolerance and a
comment saying the PDF prints 3 decimals). Plus: the Oerlikon case reproduces 18,133,251; a CIF shipment
with a freight figure entered produces **the same BM** as one with the freight field empty; PPN is
computed from the **unrounded** BM while the BL total carries the rounded-up BM; using 12% instead of
`PPN_RATE_EFFECTIVE` breaks at least one assertion (a guard test, so the trap cannot be reintroduced
quietly).

**Part done (2026-09-12). 24 tests, 1 skipped, 1 todo.** Suite 1,046 → 1,070. Both classes are written
and every acceptance clause except the ten documents is proved.

**What is missing, and why:** the ten issued PIBs' figures are **not in this repository**. The docs
record each document's incoterm, scheme and a few facts about it (`spec.md` §11.8) but none of its
amounts — those are on PDFs the import team holds. So the headline regression cannot run yet.

Everything around it is finished, and the gap is held open rather than papered over:

- `PibFixture` + `tests/Fixtures/Shipment/pib/` define the format, with a `README.md` naming the field
  each figure is read from on the declaration. `PibFixture::all()` globs the directory, so **dropping
  the ten files in makes the regression run with no code change**.
- The regression test exists and iterates whatever is on disk; with nothing there it skips, naming why.
- A separate test asserts `count(issued) === 10` and is marked `todo` with the reason. It is the standing
  record that the set is short — it turns green on its own when the files land, and it cannot be
  mistaken for a passing acceptance.

**The Oerlikon anchor is not one of the ten, and asserting it against the wrong figure would have
weakened the rounding.** Working it through: CIF EUR 5,290.26 × 20,710.98 → BM 5,478,323.45, PPN
12,654,927.18, and the spec's 18,133,251 is those two **unrounded** figures summed and rounded once —
cell `I43` of a spreadsheet. An issued PIB carries `CEIL_1000(BM) + TRUNC(PPN)`, which is 18,133,927 on
the same numbers. The two differ by Rp 676, and that Rp 676 is the rounding rules working. So
`ShipPibEstimateData` reports **both** `total` and `totalUnrounded`, the anchor is asserted against the
second, and a test pins the gap at 676. Had the anchor been asserted against `total`, the obvious fix
would have been to drop `CEIL_1000` — and four of four PIBs with BM say otherwise.

**PPN truncates per item, then sums; BM sums raw, then rounds once.** §11.7's pseudocode is explicit
(`ppn += TRUNC(ni × rate)` inside the loop, `bm = CEIL_1000(Σ bm_raw)` outside), and the first
implementation here truncated the PPN total instead — worth up to a rupiah a line, always downward. Two
tests pin the halves apart: one that the PPN total equals the sum of the line figures, one that BM is
rounded at BL level and the gap is under Rp 1,000.

**The exposure needed a flag the DTO did not have.** `exposureBmPct()` first read
`fallback ?? own rate`, which quietly made every hand-typed item "complete" — but only a *claim* can be
refused, so only a claimed item needs a fallback. `ShipPibItemInput::$isPreference` now carries that,
read from `sit_bm_source`, and the withheld-exposure test only passes because of it.

**The estimator reproduces `EximFixture`'s published constants exactly** — BM 11,676,000, PPN
28,837,116, total 40,513,116, no-preference 42,396,764, and the 6,553,875 PPh the BL would owe without
its SKB. T041 worked those out by hand from §11.7; this is the two meeting, which is the coupling that
task asked for.

**Not here:** `ShipBmResolver` (T045) and the kurs pajak / SKB / transit-time services (T046). The
estimator takes BM%, PPh% and the rates as **inputs** and never looks them up — which is exactly what
lets it be run against a piece of paper.

### [DONE] T045 — `ShipBmResolver` + preference exposure
**Refer:** `spec.md` §11.8 · **Blocks:** T047
Preference → MFN → manual, with the scheme **chosen by the user**, HS normalisation (strip dots and
spaces, take 8 digits) used only as a default, mandatory reason on `MANUAL`, and the two-figure exposure
(`sif_est_total_no_preference`, `sif_exposure_complete`).
**Acceptance:** Pest: the same Chinese HS resolves differently under RCEP and ACFTA; an unnormalisable PO
HS code leaves `bm_pct` empty rather than guessing; an item with `MANUAL` source and no
`bm_pct_fallback` sets `sif_exposure_complete = 0` and the exposure figure is **withheld, not shown
partial**; an item with no `bm_pct` at all blocks the estimate with a message naming the item.

**Done (2026-09-12). 22 tests.** Suite 1,070 → 1,092. `ShipBmResolver`, `ShipBmResolutionData`, and
`ShipHsRepositoryInterface` + `EloquentShipHsRepository` bound in `RepositoryServiceProvider`.

**The acceptance clause caught a rule three classes had got wrong.** §11.8 says a `MANUAL` item with no
`bm_pct_fallback` leaves the exposure incomplete — but T040's `ShipImportItem::exposureKnown()` read
"anything that is not `PREFERENCE` is complete on its own rate", and T042's DTO and T044's estimator both
copied it. The spec is right and the code was wrong: a rate is typed **because the HS is not in the
master**, so nothing on record says what MFN would be, and the typed figure might itself be a preference
rate somebody keyed by hand. Treating it as MFN states an exposure of zero on the item most likely to
have one.

Fixed by moving the rule to `ShipBmSourceEnum::requiresFallbackForExposure()` — `MFN` needs nothing, the
other two need a fallback — and having the model, the DTO, the estimator's input and the resolution DTO
all defer to it. A dataset test asserts the four answer identically, since they had already drifted once.

**The scheme is never inferred from the origin country, and the resolver is built so it cannot be.**
When no scheme is chosen the preference step is skipped entirely, even where rows exist — a test pins
that a Chinese HS with an RCEP row resolves to MFN until somebody claims RCEP. `schemesAvailable()` is
how the screen offers the choice instead of making it.

**A typed rate wins over the masters, permanently.** `applyTo()` keeps a `MANUAL` rate even after the
HS appears in the tariff master, and keeps the reason with it; the master's figure lands in
`bm_pct_fallback` instead, where it does the exposure some good without overwriting a deliberate entry.
A resolver that "corrected" typed rates on every recalculation would be one nobody could trust to leave
their work alone.

**The MFN rate is read whatever the outcome**, because it is the exposure figure for a claimed item —
one lookup serving two questions. When the master has no row the claim still stands and only the second
figure is withheld, with a notice saying why.

**The repository caches both masters whole and filters validity in memory**, the trade
`EloquentShipMasterRepository` already makes: the tables are small by design, and an import screen
resolves a rate per item, so a query per line would be dozens of round trips over a few hundred rows.
Caching per date was rejected — it would be unbounded and would still miss, since an estimate and its
recompute ask about different dates for the same rows. Both master services now `flush()` on save,
delete and deactivate, and a test saves through `ShipHsTariffService` and immediately resolves, which is
the assertion that would fail if a flush went missing.

**Not here:** kurs pajak, SKB, transit time and chargeable weight (T046), and the screen that calls this
(T047).

### [DONE] T046 — Kurs pajak, SKB, transit time, chargeable weight
**Refer:** `spec.md` §11.2–11.3, §11.8 · **Blocks:** T047
Four small services. Kurs pajak resolved by the **PIB registration date** and snapshotted with its KMK
period, plus a recompute that keeps history and increments `sif_recompute_count`.
**Read the rate from the ERP, do not expect it to be keyed** (D17, answered 2026-09-10): the weekly KMK
rate is `FM_EXCHANGE_RATE` type **`T`** and its decree number is in `FM_EXCHANGE_RATE_KMK_MGT` —
669 weeks without a gap since 2013, USD only. So this service reads a repository first and falls back to
`ship_kurs_pajak` for a currency the ERP's table does not carry. A test should cover the fallback, since
in practice the ERP will answer every USD lookup and the table will be empty. SKB validity at the PIB
date with the expiry warning. Transit time port-pair-then-country with per-country notify lead days.
Chargeable weight from the volumetric divisor.
**Acceptance:** Pest: the rate follows the PIB date, not the BL date or the ETA; recomputing does **not**
overwrite the previous figures and the count increments; an expired SKB refuses the exemption and one
expiring inside `SKB_EXPIRY_WARNING_DAYS` warns; a port-pair row beats a country row and a missing
port-pair falls back cleanly; `AIR` chargeable weight takes the greater of gross and volumetric and
cannot be overwritten from the form.

**Done (2026-09-12). 26 tests.** Suite 1,092 → 1,118. `ShipKursPajakResolver`, `ShipSkbGuard`,
`ShipTransitTimeService`, `ShipChargeableWeightService`, plus `ShipKmkRateRepositoryInterface` +
`EloquentShipKmkRateRepository` bound in the provider.

**The KMK rate got its own repository rather than widening the shared one.**
`ShipExchangeRateRepositoryInterface` answers "what is the rate on this date" — one figure, which is all
the provision's USD base needs. A declaration needs the **window** too, because `sif_kurs_period_from`
and `_to` are snapshotted beside the figure and a rate recorded without its week cannot be checked
against a PIB. Adding a method to the shared interface would have forced that on every caller and broken
the anonymous fakes in two existing test files, so the new question got its own contract. Same table,
same columns, type `T` rather than `B`.

**The decree number is a known gap, and it is deliberate rather than forgotten.** D17 puts it in
`FM_EXCHANGE_RATE_KMK_MGT` (`kmk_flex_01`, dated by `kmk_flex_02`), but **the columns joining it to a
week were never recorded**, and guessing them would print a wrong decree on a customs document. So an
ERP-sourced rate comes back with `decreeNo` null and `ship_kurs_pajak` stays the only place carrying one.
That blocks nothing in the estimate; it is a gap for the PIB print, and whoever picks up T048 needs the
join measured first.

**Three service names avoid a collision T043 created.** `ShipKursPajakService` and `ShipSkbService`
already exist under `Services\Master\Shipment`, so the transaction-side pair are
`ShipKursPajakResolver` and `ShipSkbGuard`. Different namespaces would have made it legal and unreadable
— T047 imports several of these into one file.

**"Keeps history" needed no second table.** `sif_kurs_pajak` and `sif_est_duty_total` are already logged
attributes on `ShipImportFile`, so Spatie's `logOnlyDirty()` records the old figure beside the new one on
every save. The recompute increments `sif_recompute_count` and the test reads the superseded rate back
out of the activity log — which is the assertion that fails if somebody drops those columns from
`$logAttributes`. It also returns **null when the rate has not moved**, so a recompute that changes
nothing neither inflates the count nor writes a log entry saying nothing happened.

**The rate date is the PIB registration date, and the test proves it by disagreeing with everything
else.** The BL date, the ETD and the ETA all point at the earlier KMK week; only the PIB date points at
the later one, and the snapshot takes the later rate. Until customs issues a PIB the **estimated** date
is used, which is exactly why the figure moves when the declaration is finally lodged.

**The SKB check is at the PIB date and nowhere else**, with a test where the certificate is valid today
and expired by the declaration — the case a "valid now" check passes and the budget then misses by ~Rp 35
million. An expired certificate is a refusal, not a warning: `expiresSoon()` returns false once past, so
the two notices cannot both fire and bury each other.

**Chargeable weight is `AIR` only.** At sea the quantity is containers or CBM and by courier it is the
gross weight, so returning a chargeable figure there would put a number on screen no invoice will
mention. The volumetric weight is withheld until every dimension is present — `MAX(gross, 0)` would hand
back the gross as though it had been checked — and a zero `AIR_VOLUMETRIC_DIVISOR` falls back to 6000
rather than fatalling on a screen.

**Not here:** the screen that calls all four (T047), and the notification sweep that uses
`reminderDate()` (T057).

### [DONE] T047 — `ShipImportFileInput`
**Refer:** `design.md` §6, `spec.md` §11.1–11.5 · **Blocks:** T048
Three nested levels: BL header → invoices → items. PO pull showing **remaining** quantity, two goods
names, HS + BM% per item with its source and reason, container grid, the estimate panel, status
transitions, one-transaction save, field-level activity log.
**Acceptance:** a BL with 2 invoices and 5 items saves and re-opens identically; pulling the same PO line
into a second BL offers only the remainder, and the two BLs together never exceed the PO quantity; the
estimate refuses to run while any item lacks a `bm_pct`, naming the item; `SUBMITTED_PIB` is refused
without a 14-character Aju no.; save is one transaction (assert with a forced mid-save failure leaving
nothing behind).

**Done (2026-09-12). 18 tests.** Suite 1,118 → 1,136. `ShipImportFileService`,
`ShipImportFileRepositoryInterface` + its Eloquent implementation, the `ShipImportFileInput` component
and five partials, two routes, two breadcrumbs, and a migration registering the front office's three
document-number sequences.

**`$ownQty` was a bug I wrote and the acceptance test caught.** `remainingFor()` first added the item's
own quantity back as an allowance, on the theory that an existing row's quantity sits inside the "other
BLs" sum. It does not — a new BL is not saved yet, and an existing one is excluded by
`$exceptFileSysId` — so the effect was to inflate the remainder by whatever the screen showed, and
keying 2,500 against a 2,000 remainder passed. Removed, with the reasoning left in the docblock. Two
tests hold both halves: a second BL sees 2,000 of a 5,000 PO line and is refused at 2,500, and a
re-opened BL still sees its own 3,000 as allowed rather than halving its remainder on every save.

**The save snapshots the kurs pajak, best-effort.** Without it a BL saved and re-opened lost the rate —
the form array is rebuilt from the model, and `kurs_pajak` is a computed output the DTO does not read
back — so the `clear` gate then refused a BL nobody had done anything wrong to. It resolves once, on the
first save, and never re-snapshots: **moving a figure Finance has budgeted against is the recompute's
job**, and that says so when it does it. The ERP being unreachable is caught and reported rather than
costing the user their keying, and when nothing answers the **estimate** is what refuses — which is the
right place, because that is where the figure would have been wrong.

**Two gaps in the PO pull, both measured rather than guessed.**
`ov_po_mgt` exposes **no HS column**, though §11.4 assumes one is pullable — so `hs_code_po` comes back
empty and the user types it. Adding the column to the ERP view, or finding the other source, is what
would close that; a guessed column name would be worse than a blank field. And `po_line` is the line's
**position within its PO**, not the ERP's `pi_sys_id`: `sit_po_line` is an integer column and a
surrogate key would not reliably fit one. The item code travels with it, so a line that has moved can be
spotted rather than silently mis-paired.

**T039's rollback test needed narrowing, and it was right to.** It asserted that no
`hm_mst_sequences` row matched `ship_export_si%` after rolling the trigger migration back — true until
this task registered a **document-number** sequence on the same table. Scoped to `hmms_seq_type = 0`, so
it now tests what it meant: the sys-id sequences are that migration's to clean up, and the trans-no ones
belong to their own.

**All three trans-no sequences are registered in one migration**, not one per task. They are the same
concern, all three tables have existed since T039, and a deployed migration may not be edited — so
splitting them would mean two more migrations later for nothing. T049 and T053 will find theirs waiting.

**The screen produces the figures T041 published and T044 reproduces**, end to end through the Livewire
component: BM 11,676,000, PPN 28,837,116, and the 6,553,875 PPh the BL owes without its SKB. That is the
fixture, the estimator and the screen all agreeing on one BL.

**Not here:** the document checklist and its uploads (T048), payment requests (T049), and the BL list
page — T058's dashboard is where that lands, which is why both breadcrumbs currently hang off the
Finance module root.

### [DONE] T048 — Document checklist + upload
**Refer:** `spec.md` §11.6, `schema.md` §13.6 · **Blocks:** T052
Checklist generated by evaluating `ship_doc_type` against the BL, with `sid_is_mandatory` snapshotted;
MinIO upload with versioning; `sif_doc_complete_flag` recomputed on every change.
**Acceptance:** Pest: a CIF BL requires the insurance certificate and a FOB one does not; a Chinese-origin
BL claiming ACFTA requires the ECO; an HS-prefix rule adds the surveyor report; adding a new master row
changes the next BL's checklist and **not** an existing one; replacing a file keeps the old version and
increments `sid_version`; the complete flag turns true only when every mandatory row has a file.

**Done (2026-09-14). 14 tests, every acceptance clause covered.** `ShipDocService`,
`ShipImportDocRepositoryInterface` + its Eloquent implementation, the `ShipImportDocChecklist` component
and its view, the provider binding, and a migration widening three `APPLIES_*` columns.

**A condition is now a comma-separated list, and the seeder was wrong without it.**
`sdt_applies_transport_mode` held `'SEA'` on the bill of lading — and `ShipTransportModeEnum` has **no
plain `SEA` case**, only `SEA_FCL` and `SEA_LCL`, so that row matched no shipment at all and the most
obviously mandatory document on an import was silently never asked for. The fix is the same shape as the
insurance certificate (required on CIF *and* CIP): `appliesTo()` reads each condition as a list, the
seeder says `SEA_FCL,SEA_LCL` and `AIR,COURIER`, and a new migration widens transport mode 10 → 60,
incoterm 20 → 60 and HS prefix 20 → 100 to hold one. One row per value was the alternative, and it would
print the same document twice on every checklist.

**Two documents cannot be made mandatory by any `applies_*` column**, which is what the seeder's own
notes predicted: the preference certificate and the SKB. Both are a **declaration the user made**, not a
property of the traffic — the Form E is mandatory because the item claims that scheme (`shr2_doc_code`
on the (HS, country, scheme) row) and the SKB because the BL took the exemption. `forcedMandatoryCodes()`
raises them from the BL itself and flips the checklist row to mandatory even though the master row says
optional; a BL carrying the same HS code and *not* claiming the scheme gets the same row, optional.

**Generation is additive, and a save only ever does it once.** An existing row is never re-decided —
what a shipment was told to collect is part of its record, so a master row edited afterwards changes
nothing already generated. `ShipImportFileService::save()` generates the checklist only when the BL has
none, after the items are synced (a prefix rule fires on the HS codes the shipment carries). Picking up a
master row added since, or a condition the user has just changed, is the screen's **Generate** button —
a deliberate act, by someone. The acceptance clause about "the next BL and not an existing one" holds
across re-saves because of that, not by accident.

**A BL with no checklist at all is not complete.** The literal reading of §11.6 — every mandatory row has
a file — is true of an empty list, and a brand-new BL would report complete on the dashboard. That is the
false negative the whole escalation exists to prevent, so `recomputeCompleteFlag()` requires rows to exist.

**The version is in the path**, which is what keeps the superseded file:
`shipment/import/{sif}/{code}/v{n}-{name}.{ext}` on `minio_private`. Two uploads against one row can never
collide even under the same original name, so nothing is ever overwritten and no delete is issued —
`open-questions.md` O3 assumed replacement is allowed and this implements that reading.

**No new permission.** Viewing the checklist is `-import-file-view` and attaching to it is
`-import-file-edit`; `design.md` §8 names neither a document permission nor a reason for one, and the
checklist is part of the BL, not a thing of its own.

**Left open, deliberately:** the **insurance certificate has no official customs code**, so the seeder
does not ship a row for it — inventing a code would make it look official on a checklist while matching
nothing on the declaration, which is the rule that seeder states for itself. The condition works (the
acceptance test proves it with a master row of its own); a master-data owner adds the row with whatever
internal code they use. The four notifications of §11.6 are **T057**, not here.

**One leftover removed:** `ShipImportFileInput::remainingFor()` still passed a fourth argument to
`ShipImportFileService::remainingFor()`, which has taken three since T047 dropped the `$ownQty`
allowance. PHP ignores it, so nothing was wrong on screen — but it read as though the service still
wanted the figure.

### [DONE] T049 — Payment requests
**Refer:** `spec.md` §11.9 · **Blocks:** —
`DRAFT → SENT → PAID`, no approval and no reject. PIB amount from the estimate, DO amount hand-entered
with the **last 3 DO payments for that shipping line** shown beside the field. Finance's pay action
captures amount, date and the variance with its reason.
**Acceptance:** Pest: a request references exactly one parent document; the DO helper returns the three
most recent payments for the shipping line and nothing for a line with no history; paying stores
`spr_variance_amount` as paid − requested and requires a note when it is non-zero; there is no code path
that sets a rejected state.

**Done (2026-09-14). 18 tests.** `ShipPaymentRequestService`,
`ShipPaymentRequestRepositoryInterface` + its Eloquent implementation, the list and input components with
their views, three routes and three breadcrumbs. The model, the enum, the DTO and the factory were
already there from T040–T043, so this task was the layers between them.

**Prefixing rule keys without prefixing their field references inverted the whole "exactly one parent"
pair.** The DTO writes `sif_sys_id => required_without:ses_sys_id, prohibits:ses_sys_id` unprefixed; the
screen holds the form under `form.*`. Qualifying only the **keys** left `required_without:ses_sys_id`
pointing at a top-level field that does not exist — so it read as always-absent and demanded *both*
parents, while `prohibits` pointed at nothing and forbade neither. Exactly backwards from the check
constraint. `qualify()` now moves the parameters with the keys, and splits the two shapes: rules whose
parameters are all field names (`required_without`, `prohibits`, `same`…) from the ones where only the
first is (`required_if`, `required_unless`…). Two acceptance tests hold both halves.

**Zero is "no estimate", not "no duty".** `sif_est_duty_total` is NOT NULL defaulting to 0, so an
un-estimated BL is indistinguishable from a free one by the value alone — and a PIB request budgeted at
zero reads as "nothing to pay". `pibAmountFor()` answers null for a non-positive total and the save
refuses by name, which is §11.8's rule about a missing figure never being read as a number.

**The PIB amount never comes off the form.** Whatever the screen sends, a `PIB` request is re-read from
the BL's kept estimate before it is written, and the field is disabled with a note saying where the
figure came from. A hand-keyed duty figure is a second number to reconcile against the estimator's, and
the variance report would then be measuring the typist.

**The variance is computed from the stored request amount, not the payload.** The screen can hold a stale
budget; a variance that disagrees with the two figures printed either side of it is worse than none. A
test drives `pay()` with a deliberately wrong `requested_amount` in the payload and asserts the row wins.

**A save cannot move the status.** `spr_status`, `spr_sent_at` and the five paid columns are stripped
from the attributes a save writes — otherwise posting a different string would send or pay a request
without going through the gate that checks it.

**`EMKL` is out of the cost-type picker as well as refused by the DTO**, so nobody has to read the
message. An EMKL commitment goes straight to a provision (§11.10), so a payment request for one would
never be reconciled against anything.

**The "no rejected state" clause has a test of its own**, and it is the one most likely to matter later:
it asserts the enum's three cases by name and reflects over the service for any public method containing
"reject". A `reject()` added in six months fails here before it reaches a reviewer.

**The DO history is paid requests only**, ordered by payment date, capped at three, and empty when no
shipping line is named — an average across every vendor is somebody else's rate. §11.9 calls it history
rather than a master, so nothing fills the amount in.

**Not here:** raising a request from the BL screen. The input page takes `?sif=` when a link provides one
and otherwise asks, with a searchable list of import files, so it is reachable without T058's dashboard.

### [DONE] T050 — EMKL commitment → provision, and storage as a direct bill
**Refer:** `spec.md` §11.10 · **Blocks:** T056
EMKL commitment generates cost lines from the tariff (per activity, progressive tiers included) and posts
a provision **per BL**, dated at the commitment date, reading the port from the BL header. Storage is
**not** provisioned: it arrives as a `DIRECT` settlement line against the same BL, validated against the
`DAYS` bands.
**Acceptance:** Pest: an EMKL commitment on a 10-container BL produces the three Jasindo tier lines; the
provision carries `spv_sif_sys_id` and `spv_source_type = FILE`; a provisioned-but-unbilled line is
reversed per the answer to F6 and appears on the hanging-provision report until it is; a storage line
with no provision posts as `DIRECT` and its day count is checked against the tiered rate.

**Done (2026-09-15). 19 tests.** `ShipEmklCommitmentService`, `ShipProvisionReversalService`,
`ShipStorageValidator`, `ShipEmklCommitmentData`, the commitment screen with its route and breadcrumb,
`ShipTariffService::priceDays()`, `hangingCosts()` on the provision repository, and one additive
migration for the three reversal columns.

**A commitment is not a document, so it has no table.** The moment the forwarder is booked the cost is
known, and everything the provision needs is on the BL already — so there is nothing for an intermediate
row to hold. `ShipEmklCommitment` keys two fields, prices the tariff and hands a draft provision to
`ShipProvisionService::save()` like any other document. It stops at **draft**: submit and confirm are the
provision screen's, under the same permissions, and there is no authority here that screen does not
already give — which is why the route is gated on `-provision-create` rather than on one of its own.

**Port, containers, Aju, PIB and supplier are read off the BL, never keyed.** The port is the one that
matters: EMKL rates differ per customs office, so a second copy of it could quietly price Tanjung Emas
work at Tanjung Priok's rate. The containers are copied **onto** the provision rather than read through
it — the provision is what a voucher posts from, and it has to still describe what was priced after
somebody corrects a container count on the BL a month later, the same reason the SI snapshots its BIM.

**§3.1's requirements are checked against the parent one at a time.** A BL with no PIB no. is refused by
name, because the person committing the forwarder is usually not the person who will go and find it.

**`spv_sif_sys_id` and `spv_source_type` are written once and never rewritten.** They are on
`ShipProvisionData` now, so they round-trip through the provision screen's form — which means an ordinary
edit could otherwise re-point a provision at a different BL, or clear the link by opening a front-office
document and pressing save. `upsertHeader()` strips both on update.

**A reversed line is not an unbilled line.** `unbilledQuery()` excludes them, which is one change with
three consequences: a reversed line stops being offered on settlement drafts (it would bill against an
account that no longer carries it), it drops off the hanging queue, and a provision whose last open line
was reversed closes itself through the existing `closeIfFullySettled()` rule rather than through a second
rule kept in step with the first.

**The reversing legs come from the builder, not from this service.** `journalLegsFor()` with `drcr`
flipped, so when §7.2's leg composition is corrected — and it has been once already, against production —
the reversal follows it with no second edit.

**F6 and D16 were answered on 2026-09-15, and both confirmed what shipped.** Each is a parameter, so
either can be revisited without a release:
- **F6** — `PROVISION_REVERSAL_PERIOD = CURRENT`. An unbilled accrual reverses in the period it is
  noticed in. `ORIGINAL` restates the month the accrual belonged to, which is the better accounting on
  paper and also the answer that stops working exactly when it is needed: the ERP refuses a posting into
  a closed period, and these are noticed months late. The cost is accepted knowingly — the month that
  carried the accrual stays overstated — and what closes that gap is reviewing the hanging list monthly,
  not the parameter.
- **D16** — `STORAGE_TARIFF_ENFORCEMENT = WARN`. The screen names the banded figure beside the invoiced
  one and the settlement still saves. Blocking would assert that the tariff master is always current, and
  when a band goes stale it is the settlement desk that is stuck, over an invoice that exists either way.

**Storage is validated by what the master already knows, not by a list of activity codes.** A settlement
line is a per-day line when the vendor's tariff prices its activity in `DAYS` — `priceDays()` answers
null for everything else, which is the useful half of the answer. A second list of "storage activities"
in code is one that goes stale silently, and the same check covers export demurrage for free.

**A day count the bands do not fully cover is reported as uncheckable, not as an overcharge.** The tiers
stop at 999 days; pricing 2,000 against them returns a partial figure, and comparing an invoice to a
partial figure reports the shortfall as though the vendor overcharged.

**Not here:** the hanging-provision **report page**, which `PRD EXIM.md` names among T058's four reports
and is built there. What landed here is the query it reads — `hangingCosts()`, lines rather than
provisions, because a provision routinely carries billed lines beside never-billed ones and "this
document is hanging" is not a fact that exists. Two tests hold it: a confirmed unbilled line appears, and
stops appearing once reversed.

---

## P8 — Export SI

### [DONE] T051 — BIM, templates, and the keyed contract rate
**Refer:** `spec.md` §10.6, `schema.md` §13.1–13.3 · **Blocks:** T053
`ShipBimService` + the three list/input/template pages. Template inheritance at create, every field still
editable, overrides recorded. Renegotiation writes `ship_bim_freight_history` rather than overwriting.
The free-text scan raising soft warnings.

**The rate is typed, not pulled** — S3a, answered 2026-09-10 by the export team, and this **reverses**
what the task said when it was written. The export team keys the contract freight rate **when they book
the vendor**; that is when it is agreed, and pulling from the ESC would have stamped a pre-booking figure
onto the baseline every margin comparison is measured against. The ESC's number is read and **shown
beside the field** as a reference (`soi_flex_10`, on 4,895 of 19,926 items), never written. Once keyed it
freezes.

**Several item rates on one ESC warn** (S4): show the rates found, set `sbm_review_pending`, do not
average, and **do not block** — the export team clears the flag by keying the booked rate, which is the
field's own job. It fires on 25 of 4,717 rate-carrying ESCs.

**One template per customer** (S5): `SBT_CUSTOMER_CODE` stays unique, and a shipment that needs a
different consignee is edited on the BIM. Three of 423 export customers ship to more than one address;
only BEKAERT DESLEE has many.

**Acceptance:** Pest: a new BIM inherits the customer template and an edit to the template afterwards
moves BIMs not yet pulled into an SI but **not** those already pulled; the ESC rate is **offered, and a
save with no rate keyed is refused** rather than silently taking it; an ESC whose items disagree shows
every rate it found, sets `sbm_review_pending` and still saves; a renegotiation leaves the old rate in
the history table with its reason; typing `FREE TIME 14 DAYS` into `special_instruction` produces a
warning and **still saves**; `Regular` in the legacy free-time field maps to `CARRIER_STANDARD`, not to
0 days. The ESC picker offers **approved contracts only** (S6, `soh_appr_status = 3`) — 5,848 of 6,134,
and a test that an unapproved ESC is not listed.

**Done (2026-09-15). 27 tests.** `ShipBimService`, `ShipBimTemplateService`,
`ShipSpecialInstructionScanner`, `ShipBimRepositoryInterface` + its Eloquent implementation, the list,
input and template screens with their views, four routes and four breadcrumbs, `ShipBimStatusEnum`,
`ShipFreeTimeBasisEnum::fromLegacy()`, and two additions to the ERP sales-doc repository. The models, the
DTOs and the factories were already there from T040–T043, so this task was the layers between them.

**The rate field is the whole argument of the page, and the reference is never a prefill.** The screen
prints every distinct `soi_flex_10` on the contract with how many items carry it, and the input starts
empty. A prefill would have been pulling one keystroke later — the user presses save and the baseline
every margin comparison is measured against is a pre-booking figure after all. `blankFor()` therefore
fills in the incoterm, the currency and the template's defaults and deliberately leaves the rate null.

**`contract_freight_rate` became required on `bimRules()`, which changed a T042 test.** That test's
"valid BIM" fixture had no rate, written when the rate was still going to be pulled. Leaving it optional
would mean a saved BIM with no baseline at all, and the obvious repair six months later is to take the
ESC's number — the exact mistake S3a exists to prevent. `legacyRules()` keeps it optional, because a
migrated row usually has none and refusing those means not migrating them (T052). **Worth a word from
the export team before go-live:** if they raise BIMs at contract signing and book the vendor weeks later,
this turns into a screen they cannot save, and the fix is one rule — make it a warning and let
`sbm_review_pending` carry it until the rate is keyed.

**Filling a rate in for the first time is not a renegotiation.** History is written only when a rate that
was already keyed moves, and then a reason is required — a rate that changed with no reason is
indistinguishable from a typo six months later. A migrated BIM being keyed for the first time therefore
writes no history and needs no reason, which is the case T052 will hit hundreds of times.

**"Not overridden" is read as "the BIM still holds what the template held before this edit".** There is
no per-field override column and there should not be: it would go stale the first time anybody edits the
template. The consequence is stated in the service — a BIM edited **to** the template's value by hand is
indistinguishable from one that inherited it, and it moves — and that is the better trade. The template
screen says how many BIMs will follow **before** the save and how many are already pulled and will not,
because a silent cascade is the kind of edit people later swear they never made.

**S6's filter defaults to on in the repository, not in the picker.** `salesContracts()` filters
`soh_appr_status = 3` unless a caller passes `approved_only => false`, so a future picker that forgets
the rule still cannot offer the 286 unapproved contracts. `salesContract()` by number stays unfiltered:
"what is this number?" is a different question from "may I use it?", and a migrated BIM naming an
unapproved contract still has to resolve.

**The rate conversion happens in PHP, and that is not a style choice.** `soi_flex_10` is free text on a
screen nobody validates, and Oracle 11g's `TO_NUMBER` throws on the first `USD 850` it meets — taking
the whole picker down over one row from 2016. The repository groups on the raw text and discards what is
not a number. A test holds it with a contract carrying `850`, `USD 850` and a blank.

**The free-text scan reports one sentence per field, not per term.** `FOB, NOT CIF OR CFR` is one
observation about the incoterm; three lines saying so get read as noise and ignored. Matching is whole
words, so `CIF` does not fire on "specific".

**`QualifiesNestedRules` is now a trait**, shared by the BIM screen and `ShipPaymentRequestService`.
T049's bug was one rule set away from repeating here: `required_if:free_time_basis,DAYS` under
`form.free_time_days` resolves `free_time_basis` at the **root**, finds nothing, and silently never
requires the day count.

**Not here:** `sbm_status` is not cast on the model. The enum exists for the list's badge and reads
through `tryFrom`, so a migrated row carrying something unexpected shows as plain text rather than
fataling a list page — the same reason the incoterm and the free-time basis are not cast either.

### [DONE] T052 — BIM legacy upload + assisted mapping
**Refer:** `data-migration.md` §10, `spec.md` §10.6 · **Blocks:** —
An artisan command plus a review screen: auto-map the fields whose pattern is unambiguous, leave the rest
**empty and flagged**, and keep the original text in `sbm_legacy_body` shown side by side during review.
**Acceptance:** Pest on a fixture built from the real samples: consignee, notify party, YES/NO flags and
`2*40 HFCL` container configs map automatically; `Shipment Type` containing `8 JUNE 2026` or `LC 90`, a
`Free Time` of `Regular`, a `BIM Date` of `8 JUN 206`, and an incoterm mentioned only inside
`Shipping Line Restriction` are all left **empty with `sbm_review_pending = 1`** — the test asserts they
are not guessed. Completed contracts are not imported.

**The scope is now a date** (O6, answered 2026-09-10): ESCs approved and dated within **twelve months**
of the cut-over — 447 across 76 customers as measured on 2026-09-10. Not a status: `soh_appr_status = 3`
and no close date describes 5,848 ESCs back to 2012, because that table is never closed. The command
should take the window as an option with twelve months as its default, so the list is produced by
re-running it on the day rather than by anyone maintaining a spreadsheet.

**Done (2026-09-15). 26 tests.** `ShipBimLegacyParser`, `ShipBimLegacyImportService`,
`ShipBimLegacyMappingData`, the `finance:shipment:import-bims` command, the review panel on the BIM
screen, and a `LegacyBimSamples` fixture built from the shapes the production samples have.

**The tests come in pairs on purpose: what maps, and what deliberately does not.** The second half is
the one that matters — every one of those assertions is a reading somebody could later "improve" into a
guess, and the test says why not. A parser that flags everything is as useless as one that guesses, so
the clean block has to come out fully mapped and unflagged, and it does.

**`Shipment Type` is never mapped, whatever it holds.** The samples put `8 JUNE 2026`, `WK 27` and
`LC 90` in it — a date, a week number and a payment term — and nothing in the value tells the three
apart. The same value in `LC Number` is refused by shape: `LC 90` is ninety days' credit, and writing it
to `sbm_lc_no` would put a payment term where a document reference belongs.

**`Regular` maps the basis and leaves the day count empty, and the field is still flagged.** That reads
like a contradiction between M2 and this task's acceptance, and it is not: `CARRIER_STANDARD` is what the
word *means*, so it is not a guess (T051 pins it), while the number of days that carrier actually gives
is not in the text — so `free_time_days` stays null and the row is flagged. A `0` there would mean
demurrage from day one. `Min 10 days` maps **nothing**: it carries a number, and taking it would record a
minimum as the agreed free time.

**The BIM date falls back to the contract's own date, and keeps the flag.** `8 JUN 206` is a year one
digit short and `strtotime` reads it as the year 206 without complaining, which is why the parser checks
for a four-digit year rather than trusting the parse. The row still needs a date to be legal, so the
import writes the ESC's date — a true statement about the document — rather than a reading of the typo,
and leaves `sbm_review_pending = 1`.

**The incoterm sweep flags rather than fills.** `FOB SHIPMENT` inside the carrier restriction and
`TERM : DAP 2020 INCOTERMS` inside the instruction are evidence that a term was agreed, not evidence of
which term governs. A dedicated incoterm label maps; a mention anywhere else flags.

**Labels are normalised and matched within two edits**, which absorbs `Specifil Instruction` and
`HBL/MBL` without a space. Labels shorter than six characters are **not** fuzzy-matched at all: `POL` is
one edit from `POD`, and a near miss on a three-letter key is a coin toss rather than a correction.

**The file is two columns — `esc_no` and `bim_text`** — because a spreadsheet keeps a multi-line block in
one cell without mangling it, and that is the format the export team can produce. The heading row is
**recognised rather than assumed**: a sheet exported with the columns the other way round would otherwise
import every contract number as a BIM body.

**Every block ends in exactly one of three places and the report says which** (M1): imported, skipped
because the contract already has a BIM (so a re-run is safe), or rejected with a reason — not in the ERP,
not approved, closed, outside the window, or named twice in the same file. The command prints the
rejected rows with their reasons and warns how many BIMs need review, which is M5's count.

**Not here:** reading the block from the ERP. `data-migration.md` §10 says there is no source table —
the old BIM is free text typed into the sales screen — so the body is uploaded and the ERP supplies the
scope and the validation (M4). If a column holding that text is ever identified, the parser does not
change; only the command's reader does.

### [DONE] T053 — `ShipExportSiInput` + `ShipBimMergeService`
**Refer:** `spec.md` §10.4, §12.7 · **Blocks:** T054
SI from one or several BIMs, with the merge rules: refuse on identity fields, warn on payment term, take
constraints at their strictest. Terms snapshotted, incoterm editable with a recorded reason and a held
review when it changes after cost lines exist. Shipment facts, container dates, free time, EIN/PEB
fields.
**Acceptance:** Pest: an SI from 2 BIMs snapshots both and links them through `ship_export_si_bim`;
combining BIMs with different incoterms is refused with a message **naming incoterm and both ESC
numbers**; different payment terms warn but proceed; the merged free time and max net weight are the
smallest of the inputs and fumigation is required if either input required it; the carrier rule is the
intersection; changing the incoterm after a cost line exists holds and asks for review; `FOB` without a
nominated forwarder is refused, `DAP` without a final destination is refused; `CNF` maps to `CFR` and
`FOBEXWORKS` is refused outright.

**The incoterm rules are worth their exact counts, measured 2026-09-10 over 6,134 ESCs:** `CIF` 4,505,
`FOB` 959, `CNF` 353, `CFR` 286, **`FOBEXWORKS` 19**, `FAIR` 1, blank 11 — and nothing else, ever. So
`CNF → CFR` normalises 353 contracts, refusing `FOBEXWORKS` refuses 19 real ones (it is two terms typed
into one field, which is why it cannot be honoured), and `FAIR` is a typo somebody made once. The list
is closed enough to validate against.

**Landed 2026-09-15.** `ShipBimMergeService` returns **refusals and warnings as data**
(`ShipBimMergeResult`), so the picker shows a conflict while the set is still being chosen and the save
is the only place that turns one into an exception. Three readings the spec left open, each recorded in
the class:

- **Customer joins the identity list**, and port of loading, nominated forwarder and BL type only warn.
  §12.7 names incoterm, POD, transport mode, currency, consignee and notify party; a customer mismatch is
  the same kind of fact — one B/L, one consignee, one customer — while a differing POL is a shipment
  detail the SI settles for itself.
- **The merged free time prefers a stated day count.** A number is the promise that can be checked
  against the carrier's invoice and is never more generous than an unenforceable carrier standard; with
  no number anywhere, `CARRIER_STANDARD` beats `NOT_APPLICABLE`, because "the buyer carries demurrage on
  this contract" says nothing about the shipment and reading it as zero is how `Regular` became demurrage
  from day one in the legacy data.
- **An empty carrier intersection warns rather than refuses.** It means no carrier satisfies both
  customers — a fact about the booking, not about whether these are one shipment.

`final_destination` is a BIM column with no SI counterpart, so the `DAP` refusal is raised on the
**merged term** and the value is carried in the result for the screen; nothing writes it to
`ship_export_si`.

**It also found a live bug in T043's DTO.** `ShipExportSiData` writes three cross-field rules against its
own field names — `lte:gross_weight_kg`, `after_or_equal:etd`, `required_if:free_time_basis,DAYS` — and
the screen holds those fields one level down under `form`. Unqualified, `lte` compared the net weight
against an absent key and failed every SI that stated both weights. `ShipExportSiService::qualify()`
rewrites an argument that **is** one of the gate's own fields and leaves literals like `gte:0` alone. Any
screen that qualifies a DTO's rules with a prefix has the same exposure, and the failure looks like a
rule working rather than like a bug.

### [DONE] T054 — SI cost lines, per-line approval, cancel screen
**Refer:** `spec.md` §12.2, §12.4–12.6 · **Blocks:** T055
One cost table for everything. Baseline lines approved with the document at `CONFIRMED`; additional lines
approved individually at any status including `SHIPPED`, recording the offline approver and reference.
The cancel **screen**: keep / void per line plus an optional hand-entered `CANCELLATION_FEE`.
`ses_cost_complete` recomputed after every change.
**Acceptance:** Pest: confirming an SI approves its baseline lines and leaves additional ones in `DRAFT`;
an additional line can be approved on a `SHIPPED` SI and requires `-cost-approve`; `sec_status = VOID`
excludes a line from provisioning and from `ses_cost_complete`; a cancelled SI still produces a partial
provision; `CANCELLED` is unreachable from `SHIPPED`; a new demurrage line flips `ses_cost_complete` back
to false with no reopen action.

**Landed 2026-09-15.** `ShipSiCostService` owns the grid, the approvals, the void and the closing
computation; `ShipExportSiDetail` is the screen and `ShipExportSiCancel` the cancellation. Five readings
worth keeping:

- **`CANCELLATION_FEE` is a label, not a code.** `shc_code` is ten characters, so the cost type is
  **`CANCELFEE`** — one of the two names legacy itself used (F2). Seeded with `DEMURRAGE`, `DETENTION`
  and `EARLYPICK`, all four flagged `shc_is_additional` **and** `shc_requires_line_approval`, because a
  cost that turns up late is exactly the cost nobody approved with the document. `CANCELFEE` alone is
  `shc_auto_pull_tariff = false`: it is hand-entered and has no tariff row to find.
- **`sec_is_additional` is read off the master, not the form.** The flag decides whether a line is
  approved with the document or one at a time, which is an authority question — leaving it to a screen
  means a demurrage line can be waved through with the baseline by unticking a box.
- **Keeping a line on the cancel screen *is* the approval decision.** Otherwise a cancelled SI leaves its
  EMKL as a draft nobody returns to, and the partial provision §12.4 promises never happens.
- **An SI with no cost line at all is not complete.** The §10.5 formula read literally says it is — all
  three clauses are vacuously true — but "nobody has priced this shipment" and "every cost is settled"
  are different facts, and only one of them should close a document.
- **A foreign-currency line with no ERP rate is refused, not priced.** `CalculatesLineAmounts` returns
  nothing but zeros when the rate is zero, which on screen reads as a line that costs nothing rather than
  one that could not be priced. A rupiah-only grid needs no rate at all and says so.

The SI keeps **no exchange rate of its own**, by schema: `sec_lc_amount` is a snapshot taken from the ERP
at the SI's date when the line is priced, and the provision re-prices at its own posting rate. The gap
between the two is variance 1 (§12.1), not a discrepancy — worth stating because the two figures will
differ and that is the design.

**The `+` array-union trap turned up twice more** (the header attributes, and the cancellation fee):
`$dto->toAttributes() + ['sec_status' => …]` keeps the DTO's own key, so the override silently does
nothing. Both are `array_merge` now. Same shape as the bug T053 found in the DTO's cross-field rules — an
override that reads as written and is never applied.

### [DONE] T055 — SI provision posting, per period
**Refer:** `spec.md` §12.3, §12.8 · **Blocks:** T057
`ShipSiProvisionPostingService`: take every line with `status = APPROVED AND sec_spv_sys_id IS NULL`,
post one provision dated at the **EIN date**, stamp the lines and set `spv_ses_sys_id` +
`spv_source_type`. Blocked while the EIN is empty. Commission and insurance computed per EIN as lines in
the same provision. The ERP period checked **before** posting.
**Acceptance:** Pest: posting is refused while `ses_ein_no` is null, with a message saying so; the first
run takes the baseline lines and dates them at the EIN date; a second run three months later takes only
the newly approved demurrage line and posts it in **its own** period; a line already carrying
`sec_spv_sys_id` is never taken twice; a backdated provision into a **closed** ERP period is refused
before any row is written, naming the period; the commission and insurance lines carry
`spv_source_type = ESC` and `SI` respectively.

**Landed 2026-09-15.** `ShipSiProvisionPostingService` is the run and `ShipExportSiDetail` the button;
`preview()` is what the screen asks before it offers one, so "nothing is waiting" and "that period is
closed" are both visible before a click rather than after. Four readings worth keeping:

- **One provision per (vendor, cost type, currency), not one per SI.** §12.8 says commission and
  insurance "sit in the same provision as freight and EMKL", which the schema cannot express:
  `spv_vendor_code`, `spv_cost_type` and `spv_currency` are **header** columns, so the shipping line, the
  forwarder, the agent and the insurer are necessarily four documents. Currency is in the key for the
  same reason — a forwarder billing part in rupiah and part in dollars is two accruals, and collapsing
  them would convert one of the two at the wrong rate.
- **An unreachable ERP is not an open period.** The period check refuses on any error rather than
  assuming the best, because treating it as open raises the document anyway and moves the failure to
  confirm time, which is the one outcome the check exists to prevent.
- **An accrual has to owe somebody, so a premium with no insurer is reported rather than posted.**
  `spi_vendor_code` is NOT NULL and part of the invoice's unique key, so the "raise it blank and let
  Finance name the vendor" design the code started with cannot be stored at all. The insurer is now the
  `INSURANCE_VENDOR_CODE` parameter, **seeded empty**, and while it is empty the run computes the premium,
  says so with the figure, and posts everything else. Same rule for a commission whose agent matched no
  supplier. Raised as **F-INS2** — a question for Finance, not a decision taken here.
- **Commission and insurance are once each per SI, not once per run.** 1 SI = 1 EIN, so both are
  properties of the invoice: the December run takes the demurrage line and must not quote a second
  premium on the same invoice value. `alreadyPosted()` is what makes that true, and it asks the database
  rather than a flag.

The ERP stand-in grew the four tables this needed — `ot_invoice_head`, `ot_invoice_item`,
`ot_so_item_ted` and `om_expense` — so the premium and the commission are exercised against the **real**
repository SQL rather than a fake. Only the vendor master stays faked, and `ShipErpFakes::bindVendors()`
was split out for it: `v_ship_vendor` is a view SQLite cannot create. Worth knowing that a fake bound
after a service is resolved does nothing — a service holds the repository it was constructed with, which
cost a confusing half hour.

---

## P9 — Notifications and hardening

### [TODO] T056 — Retire the transitional EMKL↔PIB rules
**Refer:** `spec.md` §5.4–5.5, `PRD EXIM.md` §6.1, §11 item 11 · **Blocks:** —
Delete the copy logic, the mismatch notice and both feature tests, **as one commit**. Runs only once no
import provision is being created without a BL parent — verify with a query, not an assumption.
Then remove §5.4–5.5 from `spec.md` and the `spv_sif_sys_id IS NULL` guard from the service.
**Acceptance:** the query showing import provisions with `spv_sif_sys_id IS NULL` created in the last 30
days returns zero rows before the commit lands; after it, an EMKL provision reads Aju no., PIB no., SPPB
and port from its BL and those fields are read-only on screen; no test references the copy behaviour.

### [DONE] T057 — Notification sweep + the four notifications
**Refer:** `spec.md` §11.6, `design.md` §1, `.docs-me/StandardCronJobCreation.md` · **Blocks:** —
**One** scheduled command, its own log channel (`exim_notify`), set-based queries, an activity-log entry
per notification raised, and idempotence — a second run the same day must not re-notify.
The four triggers: ETA−N to Purchase with per-country lead days; checklist completeness to Import;
ETA−1 escalation to Import **and** their supervisor carrying the exposure figure; PIB estimate-vs-actual
over `PIB_VARIANCE_THRESHOLD_PCT` to Finance.
**Acceptance:** Pest with a frozen clock: each trigger fires exactly once per document; a Chinese-origin
BL notifies later than a European one from the same ETA because the lead days differ; the escalation
message contains the exposure amount, and says "exposure incomplete" instead of a number when
`sif_exposure_complete = 0`; running twice in a day sends nothing the second time.

**Landed 2026-09-15.** `finance:shipment:exim-notify` is the one command, `ShipEximNotifyService` the
sweep, and `ship_notify_log` the new table that makes it safe to run every morning. Scheduled at 07:00
`withoutOverlapping`, with `--dry-run` for checking the lead days against a real week before anybody's
inbox is involved. Five readings worth keeping:

- **The idempotence key is the state, not the date.** Every trigger here describes a fact that is still
  true tomorrow, so "once per day" would repeat everything daily and "once per document" would go quiet
  on a shipment that moved. `snl_state` holds what made the message say what it said — the ETA, the
  PIB number — so a vessel brought forward two days reminds again, and nothing else does.
- **The checklist compares against the *latest* state, not the set of them.** That trigger is about a
  *change*: a file that goes complete, falls back and is fixed again is three events. Comparing against
  everything ever recorded announced the first of each and then went silent for good — on the file most
  likely to be moving. The stored state carries a `#n` transition number so the unique index survives
  the repeat.
- **The candidate window is bounded backwards only.** The first draft bounded the future end by the
  longest lead time any route configures, which was wrong for the checklist trigger: it has nothing to do
  with arrival dates, and a BL booked six weeks out would have had no checklist notice until the week it
  landed — exactly the period when there is still time to chase a document.
- **Nothing is recorded when a role has nobody in it.** Recording it would mean the day somebody *is*
  given the role, the sweep thinks it has already told them. The warning goes to the log instead.
- **The log row is written before the notification is sent**, so it is the lock: two sweeps racing hit
  the unique index and one stops. Sending first would let both through.

**And one thing the spec asks for that the application cannot do.** Notification 3 escalates to Import
**and their supervisor**, and there is no supervisor hierarchy anywhere — `HmEmpData` carries company,
division, department and section and nothing that names a manager. Deriving one would put somebody's name
on a guess, so the audience is the `EXIM_ESCALATION_ROLES` parameter, defaulting to `Super Admin`, and
the real question is **O6**.

### [DONE] T058 — Front-office dashboard + variance reports
**Refer:** `design.md` §6, `spec.md` §11.8, §12.8 · **Blocks:** —
`EximFrontOfficeDashboard`: BLs arriving within N days, incomplete checklists with their exposure, SIs
shipped without an EIN beyond `EIN_MISSING_WARNING_DAYS`, cost lines awaiting approval, lines priced at
zero. Plus the reports the PRD names: PIB estimate vs actual with the **three-way** variance split, BM
KITE paid per period, hanging provisions, and cost lines priced at zero for want of a tariff band.
**Acceptance:** counters agree with hand-written queries; the PIB variance report separates exchange-rate
from tariff from quantity and the three reconcile to the total difference; the BM KITE report totals per
period and per BL; the zero-priced report lists exactly the rows carrying the `no_tariff_match` flag.

**Landed 2026-09-15.** `EximFrontOfficeDashboard` is the page — five tiles that *are* the tabs — and
`ShipEximDashboardService` answers all five with the rows and the count from one call, so a tile reading
four above a table showing three is impossible by construction rather than by care. The four sheets are
on T030's scaffolding and lit up on the report index the moment they were routed. Four readings worth
keeping:

- **The three-way variance split could not be computed from what the schema stored, and one column fixed
  it.** `SIF_KURS_PAJAK` holds the rate in force *now*, and the recompute button moves it to whichever
  week the PIB date falls in (§11.8) — which destroys the only evidence of what the budget was built on.
  Without both rates the exchange-rate leg is not a measurement, it is a guess with a decimal point. So
  `SIF_EST_KURS_PAJAK` is written **once**, on the first estimate, and never overwritten.
- **Two legs are measured and the third is a residual, and the sheet says so.** The rate leg comes from
  the two rates, the tariff leg per item from the duty customs actually assessed, and everything else —
  quantity, value, freight allocation, and the rounding the duty rules apply at every step — lands in the
  third. Labelling it honestly beats a third measured-looking figure: nobody records what customs thought
  the quantity was, and a column that pretended otherwise would be believed.
- **A BL with no estimate-time rate reports the leg as unmeasurable, not as zero.** That is every file
  estimated before the column existed. Calling an unknown zero would push the whole difference into the
  residual and make the estimate look worse than it was; `kurs_measurable` is a column on the sheet.
- **`no_tariff_match` is the filter, not `rate = 0`.** A line can legitimately be free; a line nobody
  could price is a hole in the tariff master, and only the flag tells them apart. Filtering on the amount
  would bury the finding among the free services.

The hanging-provision and zero-priced sheets call the **dashboard's own methods** rather than a second
query written in the report layer, so the list Finance downloads and the count Import sees cannot come to
mean different things. **F3's open half is answered by giving both**: the BM KITE sheet carries a row per
BL and the page shows the per-period totals summed from those same rows.
