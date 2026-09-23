# cutover-runbook.md — Shipment Control go-live

The executable form of `data-migration.md` §8. Every step is a command you can copy, a gate you can
answer yes or no to, and a way back. Tick the boxes as you go; the ticked copy is the record.

**This document is not the decision.** T029 is executed by a person on a night, with Finance in the
room. **Every step below has now been rehearsed end to end against the dev server's copy of production
data (2026-09-08) and the verification came back green** — see the table below for what that cost. What
is left is production itself, and the four items the next section lists — five of the original six
answers came in on 2026-09-10.

> **Commands run inside the app container:** `dpa php artisan …`
> (`docker compose -f …/docker-compose.yml exec app php artisan …`).

---

## Before you start — what is still open

These are not runbook steps; they are things that have to be answered before the runbook can begin.
Each one is a real hole, and none of them is mine to close.

| # | What | Who |
|---|---|---|
| **Q6a** | **The cut-over date.** The policy is settled — a full stop, legacy read-only, no parallel window — but no date is set, and every window below hangs off it | Finance + the requester |
| **Q17** | Renumber vendor `IS00697`'s duplicate invoice on **production** before the run. Finance decided to renumber; the row is named in the rehearsal table below and the step is under T-1d | Finance |
| **Q13** | May a report recompute a historical line, knowing it can disagree with the stored figure by cents? Not a blocker for the backfill — it copies stored amounts — but it is an answer the Phase 2 reports are waiting on | Finance |
| **accounts** | Re-run the account verification against **production**. The dev copy is a copy of production and the check was green on it, but this is the one part of the sign-off a copy cannot give you | us |

**T001, F-INS1, Q14, Q16 and V9 came off this list on 2026-09-10**, along with T004 and F9 earlier the
same day. Q1–Q9, Q14, Q16 and F-INS1 are answered in `open-questions.md` §6 with what changed in the
code; four of them changed behaviour — the PO's account now beats a configured rule (Q4), export and
import are separate permissions (Q2), the Shipment masters left the Finance role's grant (Q7), the
legacy bill-number cross-check is back as a warning (Q5) — and `INSURANCE_INLAND_TERMS` replaced
`INSURANCE_INTERNATIONAL_TERMS` with the opposite sense (F-INS1). **V9 was waived**, not passed: nobody
outside the project has opened a migrated document in both systems side by side.

`ShipmentControlMasterSeeder` is written and tested, every account number in it verified against
`FM_ACNT_COMP` under company `002` on the dev copy of production. F9 turned out not to need a decision:
`203001` is what all 226 payable credits in the ERP's own settlement vouchers use.

Two more that the migration has already answered, recorded here so nobody re-opens them at 2am:
**Q12** — the unique key is `(direction, trans_no)`, measured, no renumbering needed.
**Q15** — `SHPCTR` / `EXPCTR` are a cancelled project and are not migrated.

---

## What the rehearsal found (2026-09-08, dev copy of production data)

The whole runbook below was executed against the dev server: schema migrated, backfill run, verification
green. **1,969 provisions / 3,400 invoices / 9,588 cost lines / 1,943 settlements / 3,213 invoices /
9,711 cost lines**, every automated V-check passing. What it cost, and what production still needs:

| Found | Fixed by |
|---|---|
| `spi_vendor_code` NOT NULL vs 68 duty invoices with no vendor | the backfill writes `KASNEG` / Kas Negara |
| `uq_ship_prov_inv` on (provision, cost type, vendor) — **322 provisions** legitimately carry two invoices from one vendor | key changed to (provision, vendor, invoice no.) |
| `lc` columns at 2 decimals against **2,385 lines** carrying 4 | six columns widened to `decimal(18,4)` |
| PIB duty lines have no `sad_amt`; their money is in the duty columns | base taken from the duty total, as `dutyAmounts()` defines it |
| Ten NOT NULL columns against legacy nulls — currency, rate, activity, provisioned amount | documented fallbacks, each reported |
| RED 2 compared untrimmed invoice numbers and missed a real duplicate | the check now trims, as the migration does |
| 630 settlements carried a payment voucher and no pay date, so every report called them overdue (Q20) | the backfill reads the voucher's date from the ERP; 750 pay dates recovered on the 2026-09-09 re-run |
| **One vendor invoice number on two settlements** (`IS00697`, `2026-46` / `2026-46 `, bills 2026000657 and 2026000658, different amounts) | **Finance renumbers the 5,901.88 row to `2026-46B`** — decided 2026-09-10, as the dev copy was renumbered to finish the rehearsal. It is a T-1d step now, done on production through the legacy screen |

## T-14d — pre-flight

- [ ] `dpa php artisan finance:shipment:preflight --limit=50 --path=…/preflight-{date}.md`
- [ ] Every RED finding is fixed in the legacy data or waived in writing with Finance.
- [ ] The AMBER findings are read, not just counted. As of 2026-09-08 they were: 17 pay dates that will
      be dropped (B2), 162 cost lines with no provision account, 16 `sad_fasilitas` values that are
      document names rather than facility codes, and the cost types / ports the masters do not yet know.

**Gate:** the command exits 0.

---

## T-7d — rehearsal

- [ ] The `ship_*` schema exists on the target: `dpa php artisan migrate`. **Let the guards run with
      it.** `schema.md` §12 has them applying after the backfill, on the reasoning that legacy data
      might break them — but the pre-flight now measures exactly that (RED 2 and RED 3) *before*
      anything is written, and `DISABLE_MIGRATIONS` records a skipped migration as *run*, so
      "add it afterwards" cannot be done with plain `migrate` at all. With the guards in place from the
      start, a duplicate fails the chunk that carries it instead of the whole schema change afterwards.
- [ ] Masters seeded and **reviewed** — tariff shapes and account numbers especially (T004).
- [ ] `dpa php artisan finance:shipment:backfill --dry-run` — reads everything, writes nothing, still
      writes its report.
- [ ] Read the report: row counts per table, what was skipped and why, ports no master knows, pay dates
      dropped, rollups that disagree with the legacy totals.
- [ ] Then a real run into a **copy** schema, and `finance:shipment:verify-backfill` green on it.
- [ ] ~~V9: Finance opens the 20 + 20 documents the report lists, in both systems, and says they
      match.~~ **Waived 2026-09-10.** The automated V-checks stand in its place. If anyone has half an
      hour on the night, this is the half hour to spend: it is the only check that compares what a
      person sees, and the first person to notice a systematic difference will otherwise be a user.

**Gate:** dry run clean, verification green on the copy.

---

## T-1d — freeze

- [ ] Legacy master-data edits frozen (`SHP_MASTER`, `SHP_ACTIVITIES`, `SHP_MASTER_COST`).
- [ ] Anyone who can raise a legacy Shipping document has been told the date and the hour.
- [ ] **Q17's renumber done on production, by Finance, in the legacy app.** Vendor `IS00697` carries
      invoice `2026-46` on settlement `2026000657` (1,129.56) and `2026-46 ` — same number, trailing
      space — on `2026000658` (5,901.88). Two invoices, one misnumbered; Finance decided on 2026-09-10
      to renumber, as the rehearsal did on the dev copy: the **5,901.88 row becomes `2026-46B`**. Do it
      through the legacy screen, not with SQL, so the row keeps its audit trail. Until it is done the
      backfill will refuse that chunk — correctly: the same invoice number settled twice is how a
      vendor gets paid twice.
- [ ] The account verification re-run against **production** (not the dev copy) and green.
- [ ] **The ERP has an accounting period and a document-number counter for every month we will post
      in.** Measured on the dev copy 2026-09-10: periods for company `002` stop at **2026-12-31** and
      `fm_tran_doc_no` has **nothing for 2027** — so a document dated in January is refused with
      "Periode akuntansi tidak ditemukan" or "Konfigurasi nomor dokumen tidak ditemukan", whichever it
      reaches first. Finance keys the 2027 periods and the counters for **EPJV, IPJV, EBJV, IBJV, ADVP,
      BPS and BPJ** before the first January posting; if the cut-over itself is in January, before the
      cut-over. (No period in this ERP is ever *closed* — nought of 96 rows carries a close date — so
      the closed-period policy `open-questions.md` F1 asked about does not arise. The missing period
      does.)

---

## T-0, out of hours

### 4.1 Legacy read-only
- [ ] Permission removed in the legacy app. **Nothing below writes to `MGTAPPS`** — the backfill only
      reads it — so this is about people, not about the migration.

### 4.2 The backfill
- [ ] `dpa php artisan finance:shipment:backfill --chunk=100` (both directions, no `--direction`).
      A smaller chunk is worth it: a chunk that hits bad data rolls back **whole**, and the smaller it
      is the fewer good documents wait for the fix. The rehearsal ran 27,000 rows at 100 comfortably.
- [ ] **The ERP must be reachable.** The backfill reads `MGTDAT.FT_PAYMENT_HEADER` to recover the date
      of each payment voucher a settlement names (Q20). It is a read, it is batched per chunk, and a
      run that cannot reach it still migrates everything — it just leaves those documents looking
      unpaid, and says so in the report. Check the report says *Pay date taken from the payment
      voucher* a few hundred times; on the rehearsal it said 750.
- [ ] **Write the run id down.** It is in the last line of the output and is what `--rollback` takes.
- [ ] A failed chunk does not stop the run: the rest migrates, the report names the documents in it,
      and `--resume` takes exactly those once the data behind them is fixed. On the 2026-09-08
      rehearsal that was one chunk, from one vendor invoice number claimed by two settlements.

### 4.3 The sequences
- [ ] The backfill advances `SHIP_PROVISION` and `SHIP_BILL` past the highest migrated number itself.
      Confirm it in the report's *Number sequence advanced* notice — if it is missing, the first
      document raised after cut-over will collide with a migrated one.

### 4.4 The unique guards
- [ ] Already in place from step T-7d. If they were held back for any reason, `dpa php artisan migrate`
      applies them — and refuses on dirty data, **naming the rows**. Fix those rows (or the legacy rows
      behind them and re-run the backfill); do not force it.

### 4.5 Verification
- [ ] `dpa php artisan finance:shipment:verify-backfill --sample=50`
- [ ] Every automated check green. **If not, roll back** — see below — rather than going live on data
      that does not reconcile.

### 4.6 Permissions
- [ ] `dpa php artisan db:seed --class="Modules\\Finance\\Database\\Seeders\\FinanceRolesAndPermissionsSeeder"`
- [ ] The Exim and Finance roles hold the `finance-shipment-*` permissions they need — in particular
      `-generate`, which is what clears a voucher and pays a settlement.

**Gate:** verification green, permissions granted, one real document opened and read on screen.

---

## Rolling back

```bash
dpa php artisan finance:shipment:backfill --rollback={runId}
```

- Deletes only rows carrying a `*_legacy_sys_id`, children first. Anything raised in the new app is
  untouched.
- **It refuses once a migrated document has been edited since the run**, and names those documents.
  `--force` overrides that, and throws their edits away — only with the say-so of whoever made them.
- The legacy tables were never written, so the legacy app can be turned back on at any point.

---

## T+1d … T+30d

- [ ] The daily reconciliation runs itself: `finance:shipment:verify-backfill --daily` at 05:30, from
      the Finance module's schedule. It is a no-op wherever nothing has been migrated, so it needed
      nobody to switch it on and needs nobody to switch it off.
- [ ] A failing check notifies everyone holding `finance-shipment-dashboard-view` and writes its report
      to `storage/logs/`, where the Log Viewer page lists it — no shell needed to read it.
- [ ] Legacy stays reachable, read-only, for comparison.
- [ ] Any discrepancy Finance reports is logged against this document until it is closed.

**Acceptance (tasks.md T029):** verification green on day 1 and day 7, and no Finance-reported
discrepancy left open.

---

## T+30d

- [ ] Legacy Shipping routes removed.
- [ ] The daily schedule can come out of `FinanceServiceProvider` — or be left, since it costs one
      query a day and would catch a regression nobody is looking for any more.
