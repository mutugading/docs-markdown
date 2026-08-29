# spec.md — Shipment Control behaviour contracts

The reference you keep open while coding. Every rule here is either carried over from the legacy
implementation (with the legacy source named) or an explicit change (marked **CHANGE**).

---

## 1. Document numbers

- `spv_trans_no` / `stl_trans_no` come from `SysIdHelper::generate($sequence, $userName)` on
  `HmMstSequences` rows `SHIP_PROVISION` and `SHIP_BILL`, configured to produce the legacy shape:
  `YYYY` + 6 zero-padded digits (e.g. `2026000123`).
- **CHANGE** — legacy previewed the next number on screen with `pkg_doc_no.curr_value()` and only
  claimed it with `next_value()` on insert. The new pages show `(auto)` until the first save and claim
  the number inside the save transaction. Reason: the preview was a lie under concurrency and produced
  duplicate-looking drafts.
- ERP voucher numbers are **not** ours: the posting repository builds `{tranCode}-{docNo}` from
  `fm_tran_doc_no` (§7.2) and we store the string.

---

## 2. Status lifecycle

`ShipStatusEnum: int` — one enum for both documents.

| Code | Case | Provision label | Bill label |
|---|---|---|---|
| 0 | `DRAFT` | Draft | Draft |
| 1 | `AMENDED` | Amended | Amended |
| 2 | `SUBMITTED` | Submitted | Submitted |
| 3 | `CONFIRMED` | Confirmed | Confirmed |
| 4 | `CLOSED` | Closed | Settled |

`badgeVariant()`: 0 `secondary`, 1 `warning`, 2 `info`, 3 `success`, 4 `success` (style `soft`, as
LcControl's enums do). `label()` takes the document as an argument, or the enum exposes
`labelForBill()` — pick one and use it consistently.

Transitions (`ShipStatusService::transition()` is the only place status is written):

| From | Action | To | Guard |
|---|---|---|---|
| 0, 1 | submit | 2 | `-submit` permission; document validation `submit` case passes |
| 2 | revoke | 1 | `-submit` permission |
| 0, 1, 2 | confirm | 3 | `-confirm` permission **and** the user has an Orion id; validation `confirm` case passes |
| 3 | confirm → close | 4 | only when `shc_closes_on_confirm` (PIB, SHIP) — set in the same transaction as the payment voucher |
| 3, 4 | amend | 1 | `-amend` permission; clears nothing — amendment is an in-place edit, the vouchers already posted stay referenced |
| 0, 1 | delete | — | `-delete` permission; cascades containers, docs, invoices, cost lines |

Editing is blocked at status ≥ 2 until amended. A bill reaching status 3 does **not** change its
provision's status; the provision moves to 4 (`CLOSED`) when it has no unbilled cost line left —
computed by `ShipProvisionService::closeIfFullySettled()` after every bill confirm. **CHANGE** — legacy
set `sah_status = 4` "outside these screens", i.e. nowhere reliably.

Every transition writes an activity-log entry: description `Provision No. {transNo}` /
`Settlement No. {transNo}`, with the old and new status and the user.

---

## 3. Field applicability and validation

### 3.1 Which header columns apply

| Column | EXPORT | IMPORT | Required when |
|---|---|---|---|
| `spv_customer_code` | ✔ | — | export, always |
| `spv_supplier_code` | — | ✔ | import, always |
| `spv_vendor_code` | optional | ✔ | import unless `shc_requires_vendor = 0` (PIB) |
| vendor bank ×3 | optional | ✔ | import unless `shc_requires_vendor_bank = 0` (EMKL) |
| `spv_si_no`, `spv_peb_type`, `spv_destination`, `spv_inco_term` | ✔ | — | SI no. required to submit |
| `spv_ein_no` | ✔ | — | required on the bill unless source = `DIRECT` |
| `spv_port_code` (Pabean code) | — | ✔ | required when cost type = `PIB`; required on `EMKL` too, because the tariff is priced per port (§4.1) — defaulted from the PIB provision (§5.5) |
| `spv_port` (Pelabuhan, free text) | — | ✔ | required when cost type = `PIB`; descriptive, never read by the tariff |
| `spv_sptnp_no` | — | ✔ | never |
| `spv_aju_no` | — | ✔ | required, **exactly 14 characters** |
| `spv_pib_no` (≤ 6), `spv_pib_date` | — | ✔ | required to submit when cost type = `EMKL` (the EMKL provision records the customs document it belongs to) |
| `spv_sppb_no`, `spv_pl_no` | — | ✔ | never |
| `spv_bl_no` | optional | ✔ | import, always |
| `spv_invoice_no`, `spv_invoice_date` | — | ✔ | import, always |

### 3.2 Validation cases

Named cases, as legacy `validateData($case)`, so a screen can validate a slice:

| Case | Rules |
|---|---|
| `header` | §3.1 per direction and cost type; currency + exchange rate > 0; bank currency; bank code |
| `containers` | at least one; type from master; qty ≥ 1 unless `shk_requires_qty = 0` (`LCL`). **Proposed:** when the provision's port is an `AIR` port (`050100` Soekarno-Hatta), containers are optional — air freight has no container to declare. Legacy required them everywhere, so this is a change; confirm before implementing (`gap-analysis.md` §6, Q9) |
| `docs` | export: at least one EDN. import: at least one PO (`SAMPLE` counts) |
| `invoices` | invoice no. required and **distinct within the document**; invoice date; vendor |
| `costs` | per line: activity, currency, qty ≥ 1, rate ≥ 0, FC amount, expense main account. Skipped entirely when `shc_uses_duty_columns` (PIB) — then only the invoice no. + date and at least one duty amount are checked |
| `submit` | `header` + `containers` + `docs` + `invoices` + `costs` |
| `confirm` | `submit` + every line has expense main **and** provision account (bill: main **and** sub) + PPN account when PPN > 0 + PPh account when PPh > 0 + accounts exist in the ERP (§7.1) + Faktur Pajak no. and date on every bill line carrying PPN |
| `voucherDate` | provision: `spv_prov_date` + bank code. Payment: `spv_pay_date` / `stl_pay_date` required **only** when the cost type raises a payment voucher |
| `payment` | bill: due date ≥ trans date, bank currency, company bank, vendor bank ×3, exchange rate |

**CHANGE** — the pay date is never defaulted on a journal-only path. Legacy stamped it on the confirm
path and every report that read it believed the bill was paid; both legacy docs record the cleanup.
Here `hasPaymentVoucher()` is `ship_cost_type.shc_payment_voucher IS NOT NULL`, and the date is asked
for by the payment modal only.

### 3.3 Duplicate guards

| Guard | Where |
|---|---|
| Vendor bill number unique per vendor across settlements | unique index on (`sti_vendor_code`, `UPPER(sti_invoice_no)`) + a friendly pre-save check that names the other bill |
| One bill line per provision line | unique index on `stc_spc_sys_id` |
| Invoice numbers distinct within one document | validation |
| An EDN attached to only one provision | query at pick time (`spd_doc_no` not on another provision), warning not a hard block |

**CHANGE** — legacy's import screen also matched bill numbers against
`mgtdat.ft_payment_header.ph_flex_01/_04`. That leg is dropped: it exists because legacy created payment
rows with the bill number in a flex field, which the new payment path does not do (it stores the
document reference in `poad_config_fld_01`). If Finance still needs the cross-check, it becomes a
warning fed by a repository method, not a save-blocking union query.

---

## 4. Calculations

### 4.1 Tariff resolution — `ShipTariffService`

Input: direction, vendor code, **port code**, container type, container quantity, effective date.
Rows: `ship_tariff` where direction + vendor (or `ALL` for import) + container type match, active, and
the date falls in `[sht_eff_from, sht_eff_to]` (nulls = open-ended), **and the port matches**:

```
sht_port_code = :portCode   OR   sht_port_code IS NULL
```

A row naming the port beats a row that leaves it null, for the same
(vendor, container type, activity / tier group) — the null row is the "any port" fallback, so a vendor
needs a per-port row only where its rate actually differs. When the caller passes no port code, only
the null rows are considered, and the service reports "no tariff for this port" rather than quietly
pricing at the wrong office.

Grouped by `sht_tier_group`
(falling back to the activity code), then per group:

| `sht_shape` | Rule |
|---|---|
| `TIER` | Rows ordered by `sht_tier_seq` (fallback `sht_min_qty`). The container quantity is consumed band by band; band capacity = `(max − min) + 1`. Each consumed band produces one cost line with the quantity actually consumed. |
| `FIX` | The single row whose `[min, max]` band contains the quantity. Line quantity forced to **1**. |
| `FLAT` | Single row; line quantity = `min(containerQty, sht_max_qty)` (`sht_max_qty` null = no cap). |

**CHANGE** — legacy inferred `TIER` from activity names ending `-I`, `-II`, … and treated everything
else as the default shape. The shape is now an explicit column, and the tier grouping is explicit too,
so renaming an activity cannot silently change pricing.

Each produced line inherits the tariff's currency and its three accounts, and records
`spc_tariff_sys_id`. Accounts still go through `ShipAccountResolver` (§5.3) so a posting-account
override wins where one is configured.

### 4.2 Line amounts — `ShipCostCalculator::line()`

Needs activity, quantity, rate — otherwise every amount is zeroed (legacy did the same).

```
base = qty × rate
ppn  = base × ppn_pct / 100
pph  = base × pph_pct / 100

fc   = base + ppn − (pph_advanced ? 0 : pph)          # net payable, in the LINE currency
lc   = currency == 'USD' ? fc : fc / rate             # USD
cc   = is_cross_currency
         ? (currency == 'USD' ? fc × rate : fc / rate)
         : fc                                        # bank currency
```

- `ppn` and `pph` amounts are **always stored in IDR** — converted with the header rate when the line
  currency is USD.
- Rounding: 2 decimals when the line currency is USD, 0 decimals when IDR.
- `is_cross_currency` = line currency ≠ the document's bank currency (`spv_bank_currency` /
  `stl_bank_currency`), evaluated per line.

### 4.3 PIB duty lines

When the cost type has `shc_uses_duty_columns` there is no quantity or rate:

```
fc = duty_bm + duty_ppn + duty_pph
lc = fc / exchange_rate          # 4 decimals
```

Duty inputs strip spaces, dots and commas as they are typed (legacy
`preg_replace('/[ \.,]+/', '', …)`), so pasted thousands separators work.

### 4.4 Rollups

Invoice: `total_base`, `total_ppn` (IDR), `total_pph` (IDR), `total_fc`, `total_lc` (USD), `total_cc`
are the sums of their lines. Header: the sums of its invoices. Bill invoice and bill header additionally
carry `total_diff`.

- On a **cross-currency bill**, `sti_total_fc` accumulates the **cross-currency** line amount, because
  that is what the bank pays (legacy `sbi_fc_amt` behaviour — keep it, and keep the note that
  `AMT_IDR` in the Bill report is built from it).
- `stc_provision_amount` = the linked provision line's `spc_fc_amount`;
  `stc_diff_amount = stc_provision_amount − stc_fc_amount`.
- **CHANGE** — legacy's import settlement made its headline figure the sum of the *differences* while
  export summed the invoice totals. Both now show the invoice total as the headline and the variance as
  a second, labelled figure.

### 4.5 Insurance (`INS`, export) — `ShipInsuranceService`

```
value = Σ invi_fc_val over the EDNs attached to the provision   (mgtdat.ot_invoice_head/_item)
rate  = value × INSURANCE_UPLIFT_PCT/100 × INSURANCE_RATE_PCT/100
        + (inco_term == 'CIF' ? INSURANCE_CIF_ADDON : 0)
```

Parameters default to `110`, `0.0275` and `2` (§6) — the legacy hardcoded formula. The activity chosen
must match the Inco Term: the `INSURANCE` vs `INSURANCE-INL` pair is validated, not silently accepted.

### 4.6 Commission (`COMM`, export) — `ShipCommissionService`

Reads the ESC agent terms (`ot_so_item_ted` where `ited_ted_type_code = 'AGENT'`) against the attached
EDN quantity and value, honouring `ited_ted_basis`: `Q` = amount per quantity, `R` = rate percent. Uses
the term's currency. The agent found there also becomes the invoice's vendor (via `om_expense` →
supplier code), replacing whatever was picked.

---

## 5. Cost-type behaviour and accounts

### 5.1 What the cost-type master drives

| Master flag | Legacy equivalent |
|---|---|
| `shc_force_currency` / `shc_force_bank_code` | `isPib` forcing IDR + `KAS NEGARA` / `MANDIRI` |
| `shc_requires_vendor` / `shc_requires_vendor_bank` | "vendor required unless PIB", "vendor bank required unless EMKL" |
| `shc_uses_duty_columns` | the PIB grid switch |
| `shc_auto_pull_tariff` | EMKL pulling the tariff on cost-type / vendor change |
| `shc_journal_voucher` / `shc_bill_voucher` / `shc_payment_voucher` | the `match` on cost type in `approveTrans()` |
| `shc_closes_on_confirm` | "PIB and SHIP are pushed to status 4" |

### 5.2 What stays in code (and why)

- Which **document** the insurance / commission maths reads (EDN value, ESC agent terms) — a formula,
  not a value.
- The EMKL → PIB back-fill (§5.4).
- Facility auto-registration.
- The tariff shapes.

These are documented here so the next reader does not go looking for a master row that does not exist.

### 5.3 Posting-account resolution — `ShipAccountResolver`

Ordered lookup in `ship_posting_account`, first match wins (ties broken by `spa_priority` ascending):

```
1. direction + document + cost_type + activity + condition
2. direction + document + cost_type + activity
3. direction + document + cost_type
4. direction + document
5. direction
6. (all null — the global default)
```

Then, only if nothing matched:

```
7. the activity master's sha_expense_* / sha_provision_acnt
8. import only: the PO's spd_main_acnt (legacy "a PO that carries its own main_acc overrides")
9. null → validation error naming the purpose and the line
```

`spa_condition` carries the two legacy conditional rules: `PPN_1_1` (PPN account 108005 when the PPN
percentage is 1.1, else 108004) and `HAS_PPH` / `NO_PPH` (the 401130/401134 vs 401130/401141 split).
The legacy numbers are **seed data**, listed in `data-migration.md` §7 — not constants.

**Import PO-account override:** legacy priority was PIB → PO account → EMKL → null. That is expressed
as: PIB has a `cost_type = PIB` row (priority 10), the PO override is step 8, EMKL has a
`cost_type = EMKL` row (priority 50). Confirm with Finance that a configured PIB row should still beat a
PO account (`gap-analysis.md` §6, Q4).

### 5.4 EMKL back-fills its PIB provision

After a successful EMKL provision save (import), find the `IMPORT` provision with the same
`spv_aju_no` and cost type `PIB`, and copy `spv_pib_no`, `spv_pib_date`, `spv_sppb_no` into it. Logged
against the PIB provision as a `pib` change. Carried over unchanged from legacy §2.7.

### 5.5 EMKL takes its port from the PIB provision

The EMKL tariff is priced per port (§4.1), and the port is a fact of the customs entry, which the PIB
provision owns. So `spv_port_code` is a column on **every** import provision, filled like this:

1. When the Aju no. is entered (or changed) on an import provision whose cost type is not `PIB`, look up
   the `IMPORT` / `PIB` provision with that Aju no. and copy its `spv_port_code` and `spv_port` in.
2. The user may override both — the field stays editable, so an EMKL provision entered before its PIB
   one can still be priced.
3. `spv_port_code` is required before an EMKL provision can pull a tariff or be submitted. Missing PIB
   provision + empty port = a validation message naming the Aju no., not a silent empty tariff.
4. The same copy runs the other way in §5.4: when the PIB provision is saved later, its Aju-matched EMKL
   provisions are **not** rewritten — an EMKL provision already priced keeps the port it was priced
   from. The screen shows a notice when the two disagree.

This pairing is the one place a mismatch is expensive (the whole EMKL invoice re-prices), so both the
copy and the mismatch notice are covered by feature tests.

---

## 6. Parameters (`ship_parameter` seeds)

| Key | Default | Used by |
|---|---|---|
| `INSURANCE_UPLIFT_PCT` | `110` | §4.5 |
| `INSURANCE_RATE_PCT` | `0.0275` | §4.5 |
| `INSURANCE_CIF_ADDON` | `2` | §4.5 |
| `EDN_START_NO` | *(from legacy `ShpMasters`)* | export EDN list floor |
| `DEFAULT_BANK_CODE` | `100343` | import provision default company bank |
| `PIB_BANK_NAME` | `KAS NEGARA` | PIB forced bank display |
| `AJU_NO_LENGTH` | `14` | validation |
| `PIB_NO_MAX_LENGTH` | `6` | validation |
| `PPN_RATE_FOR_108005` | `1.1` | the `PPN_1_1` condition |
| `EXCHANGE_RATE_TYPE` | `B` | which `fm_exchange_rate` type to read |
| `OVERDUE_GRACE_DAYS` | `0` | Phase 2 Bill report |

Read through `ShipParameterRepositoryInterface` with a per-request cache; a master page edits them.

---

## 7. ERP contracts

### 7.1 Account checks (read)

- Existence: `mgtdat.fm_acnt_comp` for every expense, provision, PPN and PPh account on every line —
  legacy `checkInvNo()`'s account leg.
- Combination: `(main, sub, currency)` against `fm_acnt_curr` before posting, so a bad combination
  surfaces as a validation message instead of an FK violation on the detail insert (LcControl learned
  this the hard way — keep it).

### 7.2 Journal voucher posting (EPJV / EBJV / IPJV / IBJV)

`EloquentShipJournalVoucherRepository::store($header, $lines, $tranCode, $deptCode, $orionUser)`:

1. Resolve the accounting period from `fm_acnt_period` for the voucher date — no period is a
   `DomainException` naming the date, never a silent fallback to today.
2. Lock (`lockForUpdate`) and advance `fm_tran_doc_no` for (`comp`, `tranCode`, month, calYear,
   acntYear). Doc no = `calYear + MM + 4-digit sequence`.
3. Insert `ft_unposted_trans_header` (sys id from the `TH_SYS_ID` sequence) and one
   `ft_unposted_trans_detail` per line (`TD_SYS_ID`), with `td_doc_amt` = USD base and `td_fc_amt` = the
   entered foreign-currency amount.
4. Assert DR = CR in USD base before inserting; refuse to post otherwise.
5. Return `{tranCode}-{docNo}`.

Voucher content per type:

| Type | Document | Debit | Credit |
|---|---|---|---|
| `EPJV` / `IPJV` | provision | expense account per line (+ PPN account for the PPN amount) | provision account per line (+ PPh account for the PPh amount) |
| `EBJV` / `IBJV` | bill | provision account per line (reversing the accrual) + any variance to the expense account | vendor / AP account per invoice |

The exact leg composition per type is the one thing to verify line-for-line against
`pkg_gen_voucher_ship` on a sample before go-live (PRD §7, S3). Do not guess it from this table.

### 7.3 Payment voucher posting (ADVP / ADVP-EXP / BPS)

`EloquentShipPaymentVoucherRepository::store(...)`: same period + doc-no plumbing, tran code resolved by
the user's location (`menu_user.user_field_01` — `BPJ` for Jakarta else `BPS`, as LcControl does), then
`ft_payment_header` (bank account = the implicit credit, `ph_fc_amt` = the sum of the debit lines) plus,
per line, `fs_payment` and `ft_payment_oth_acnt_detail`. No DR = CR assertion. The document reference
goes in `poad_config_fld_01`.

### 7.4 Voucher print

Journal vouchers render from `ft_unposted_trans_header`, payment vouchers from `ft_payment_header`, via
a controller under `Http/Controllers/Shipment/` and the existing PDF stack
(`barryvdh/laravel-dompdf`). MinIO is used for stored PDFs, as legacy's `getVoucherPdf()` did.

### 7.5 Guards

- Posting is refused when the document's voucher column is already set (one voucher per slot). Re-posting
  requires clearing it, which needs the `-generate` permission and is logged.
- Legacy's admin-only "missing voucher repair" (`checkVoucher()` / `updateMissingVoucher()`: a
  `sah_vcr_adv` with no matching `ft_payment_header` row) is carried over as an artisan command plus a
  panel on the dashboard, not a hidden button.

---

## 8. Reports (Phase 2 contracts)

Specified now because the schema must support them. Each is a Livewire filter modal + a queued job +
a Maatwebsite export class, following the module's existing import/export pattern
(`Jobs/Reports/…` → `ReportStatusNotification` with a download link). **Queries live in PHP**, not in
`MST_PARAMS` — the legacy `StoredReportQuery` trait is not carried over. Any NIK-like identifier column
follows the `FormatsNikForExport` rule from the root `CLAUDE.md`; all-digit document numbers (SI, EIN,
Aju, Faktur Pajak) are written as text through a custom value binder so Excel does not round them.

| Report | Grain | Filters | Notes |
|---|---|---|---|
| **Shipment / EMKL** | one row per provision invoice, cost type `SHIP` or `EMKL` | none | 22 columns; ESC/STA aggregated from `ship_provision_doc` |
| **Custom (provision vs settlement)** | provision × cost type × activity, full-outer-joined to the bill side | from, to, si, bl, po, customer, vendor, status[], ein | ~90 columns. The legacy full outer join of two unions collapses to a left/right join over `stc_spc_sys_id`, which is what the link column exists for |
| **Bill** | one row per bill invoice | from, to, vendor, invoice no., si, ein/aju, bl, fp no., pay status (PAID/UNPAID/OVERDUE), status[] | 13 columns; period on `NVL(stl_bill_date, stl_trans_date)`; `OVERDUE` = no pay date and due date in the past; `overdueDays` signed, filled only for unpaid rows |
| **Pending Bill (Exim)** | provision × cost type × vendor, both directions | from, to, source (EXPORT/IMPORT/both), cost type, prov voucher, doc no., vendor, pay status (default UNPAID), bill status (BILLED/UNBILLED), status[] | 10 columns. The legacy `NVL`/`CASE` columns (`COST_TYPE`, `DOC_NO`, `VND_NAME`, `VCR_PAY`, `PAY_DATE`, `PROV_DATE`) mostly disappear: cost type and vendor are always on the invoice, and `DOC_NO` is `spv_si_no` for export / `spv_aju_no` for import |
| **Import Details** | provision, padded against its settlement lines | from, to, cost type[], status[], po, supplier, vendor, aju, bl, + "include direct bills" | columns A→CV, banded styling, PIB rows show `KAS NEGARA` for the empty vendor |
| **GRN Details** | GRN header × item, straight from `mgtdat` | supplier, bl, aju, pib, po, grn, date range (defaults to the current year) | 29 columns; no link to the provision — a customs cross-check |

Vendor NPWP and SKB expiry for the wide reports come from `v_ship_vendor` (`supp_flex_07..10`), looked
up once per run and merged per row.

---

## 9. Things legacy did that we deliberately do not

| Legacy behaviour | Why not |
|---|---|
| `?transno=` with "exactly 10 digits else abort(400)" | route key + repository 404 |
| Report SQL in `MST_PARAMS.PARAM_VALUE_LONG` | business logic outside version control; the reason it existed (avoid deployments) is now covered by `ship_parameter` for values and by a normal release for queries |
| `ImportShipSettlement` not using the shared trait, with its own boolean `$approved` | one status enum, one service, one set of guards |
| `Merge PDF` (`Clegginabox\PDFMerger`, Windows temp paths) | dead code in legacy; if it is wanted, it gets its own task |
| `IBJV-EMKL` wired but unreachable | not carried over until someone asks for it |
| `sai_total` left null on purpose | replaced by an explicit "base total is only meaningful for a single-currency invoice" rule (§4.4) |
| Flex columns | named columns |
