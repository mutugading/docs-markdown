# spec.md — Shipment Control & Exim Front Office behaviour contracts

The reference you keep open while coding. Every rule here is either carried over from the legacy
implementation (with the legacy source named) or an explicit change (marked **CHANGE**).

**Two modules, one spec.** §1–§9 are **Shipment Control** — the provision → settlement accounting
migrated out of legacy `mgthris` (`PRD.md`). §10–§13 are the **Exim Front Office** — the operational
documents in front of it: BIM, the import BL file, the export Shipping Instruction, and the payment
request (`PRD EXIM.md`). They are separate deliveries with a one-way dependency: the front office
feeds provisions, so Shipment Control never waits on it. Rules that changed in §1–§9 *because* of the
front office are marked **REVISED (front office)** and name the PRD EXIM section that drove them.

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

Every transition writes an activity-log entry naming the document (`Provision No. {transNo}` /
`Settlement No. {transNo}`), the action, the old and new status and the user.

**`confirm → close` is one step, not two** (settled in T016). The table above reads as though a PIB
passes through 3 on its way to 4; it does not. A cost type with `shc_closes_on_confirm` has nothing
left to settle the moment it is confirmed, so `ShipStatusEnum` admits CLOSED directly from DRAFT,
AMENDED and SUBMITTED, and confirming is one write, one log entry and one transaction with the
vouchers. The `3 → 4` row still exists for the other path: `closeIfFullySettled()` after the last bill.

**An import settlement adds one rung to the table** (settled in T021). Legacy's `ImportShipSettlement`
carried its own boolean `$approved`, and §9 lists it as *replaced* by the shared status enum rather than
dropped — which it only is if the import side keeps the step. So the table above is the floor: an
export settlement may be confirmed straight from Draft, because it is typed off the vendor's invoice by
the person who will confirm it, and an **import settlement must be Submitted first**.
`ShipBillWorkflowService::confirm()` is where that lives.

**A settlement never reaches status 4 on its own.** The table labels 4 "Settled" for a bill and names
nothing that moves it there; a paid settlement is identified by `stl_pay_voucher` and `stl_pay_date`,
which is what the pay-status filter reads. The status is left unused rather than given an invented
transition.

**Confirming is not transactional across the two databases**, and cannot be: the vouchers land on
`MGTDAT` and the document on `MGTHRIS`. The order is validate → post → record, so a failure between the
last two leaves a voucher no document points at — which is exactly what §7.5's repair sweep looks for.
The reverse order would be worse: a Confirmed document whose voucher never posted looks correct.

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
| `containers` | at least one; type from master; qty ≥ 1 unless `shk_requires_qty = 0` (`LCL`). **Proposed:** when the provision's port is an `AIR` port (`050100` Soekarno-Hatta), containers are optional — air freight has no container to declare. Legacy required them everywhere, so this is a change; confirm before implementing (`open-questions.md` Q9) |
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

**KEPT, as a warning** (Q5, answered 2026-09-10) — legacy's import screen also matched bill numbers
against `mgtdat.ft_payment_header.ph_flex_01/_04` and refused the save. Finance asked for the
cross-check to stay, so it is a **warning fed by a repository method**, not a save-blocking union
query: `ShipPaymentVoucherRepositoryInterface::referencesCarryingBillNo()` asks both flex columns for
the whole document in one query, and the settlement screen shows the voucher's reference under the
invoice number it matched.

Two things about it are deliberate. It **does not block** — the flex fields are free text, so a match
is evidence and not proof — and it lives in its own list on the screen (`erpPaymentNotices`) rather
than among the blocking duplicates, because merging them would have made a caution silently refuse a
save. And it only ever finds a **legacy** payment, since the new payment path keeps its reference in
`poad_config_fld_01`: that is the point of keeping it, because for years after cut-over a forwarder's
invoice may already have been paid through the old screen and nothing else in the new module can see
that.

---

## 4. Calculations

### 4.1 Tariff resolution — `ShipTariffService`

**REVISED (front office)** — `PRD EXIM.md` §8.2 re-confirmed the three shapes with the team and two
of them did not mean what the earlier draft of this section said. Read the shape table below carefully;
getting `TIER` wrong understates a ten-container EMKL invoice by ~7%, silently.

Input: direction, vendor code, **port code**, container type, **quantity + its UOM**, effective date.
Rows: `ship_tariff` where direction + vendor (or `ALL` for import) + container type match, active, and
the date falls in `[sht_eff_from, sht_eff_to]` (nulls = open-ended), and the port matches.

**Port lookup order** — first non-empty result wins, so a vendor needs a per-port row only where its
rate actually differs:

```
1. sht_vendor_code = :vendor  AND  sht_port_code = :portCode
2. sht_vendor_code = :vendor  AND  sht_port_code IS NULL      -- "any port"
3. sht_vendor_code = 'ALL'    AND  sht_port_code = :portCode
4. sht_vendor_code = 'ALL'    AND  sht_port_code IS NULL
```

Steps 3–4 are import-only in practice (the `ALL EMKL` rows: `LIFTON`, `LIFTOFF`, `ADM-TRUCK`). Null
is not backfilled to a port code anywhere: every existing row stays "any port", and port-specific rows
are added only where a real price difference exists. When the caller passes no port code at all, only
the null rows are considered and the service reports "no tariff for this port" rather than quietly
pricing at the wrong customs office.

**Where the quantity comes from is decided by `sht_uom`**, not by the caller:

| `sht_uom` | Quantity |
|---|---|
| `CONT` | number of containers |
| `CBM` | volume in m³ |
| `KGS` | gross weight |
| `CHARGEABLE_KG` | computed chargeable weight (§11.2) — never hand-typed |
| `DAYS` | day count: from the vendor invoice for import storage, from the container out / return dates for export demurrage (§12.6) |
| `UNIT` | always 1 |

Rows are grouped by `sht_tier_group` (falling back to the activity code), then per group:

| `sht_type` | Calculation | What MIN/MAX mean |
|---|---|---|
| `RATE` | `rate × qty` — one line | A sanity range, **not** a price selector. A quantity outside it is a warning, not a different rate |
| `FIX` | `rate`, line quantity forced to **1** — one line | The **selection** range: which single band applies |
| `TIER` | **progressive**: the quantity is consumed band by band, and **each consumed band produces its own cost line** with its own activity | The **consumption** range: how many units fall in this band |

**`TIER` is progressive, not a lookup.** Worked example — Jasindo, 10 × 40FCL:

| Activity | Band | Units in band | Rate | Amount |
|---|---|---|---|---|
| `JASA-IMP-I` | 1–1 | 1 | 450,000 | 450,000 |
| `JASA-IMP-II` | 2–4 | 3 | 400,000 | 1,200,000 |
| `JASA-IMP-III` | 5–999 | 6 | 350,000 | 2,100,000 |
| | | | | **3,750,000** |

Take only the matching band and you get 10 × 350,000 = 3,500,000 — 250,000 short, with nothing on
screen to say so. This example is a required unit test (`PRD EXIM.md` §12.9).

**`FIX` bands may overlap in the data and are not cleaned up.** The resolution rule settles the
ambiguity instead:

```
selected band = first row, ordered by sht_min_qty ascending, where  MIN ≤ qty < MAX
```

So Jasco TRUCKING LCL (1–50, 50–100, 100–500 KGS) prices 50.5 KGS in tier 2 — and prices exactly 50 in
tier 2 as well. If Finance wants 50 in tier 1 the boundary flips to `MIN < qty ≤ MAX`, which is what
the parameter `FIX_BAND_BOUNDARY` is for.

**The default stands, and nothing historical rides on it** (D14a, measured 2026-09-10). The ambiguity is
real in the master data — every ladder shares its edges, `1–50, 50–100, …` and Andalan's `1–3, 3–5, …` —
but **nought priced legacy lines sit on an edge**, and the two laddered activities were keyed and never
invoiced through at all. So the first line this rule ever decides will be a new one, and the parameter
is there to flip when a real invoice disagrees.

**No band matches → zero, flagged, and reported.** Andalan's bands stop at 22 CBM; a 25 CBM shipment
matches nothing. The line is produced with **amount 0** and the transaction is **not** refused, but:

- the line carries `spc_no_tariff_match = 1` (`sec_no_tariff_match` on the SI side), and
- it appears in the **"cost lines priced at zero because no band matched"** report.

Without the flag, the vendor's eventual bill arrives as a `DIRECT` settlement with no provision behind
it and reads as a vendor billing for something never committed — when in truth the master was one row
short.

**CHANGE** — legacy inferred `TIER` from activity names ending `-I`, `-II`, … and treated everything
else as the default shape, which it called `X`. The shape is now an explicit column and `X` is renamed
**`RATE`** at backfill (`X` says nothing to the next reader), and the tier grouping is explicit too, so
renaming an activity cannot silently change pricing.

Each produced line inherits the tariff's currency and its three accounts, and records
`spc_tariff_sys_id`. Accounts still go through `ShipAccountResolver` (§5.3) so a posting-account
override wins where one is configured; the accounts on a tariff row are **defaults only**, which is why
the inconsistency in the legacy data (`EMKLSTORAGE` 404033 vs 404004) does not need cleaning at source.

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
- `stc_provision_amount` = the linked provision line's **`spc_base_amount`**;
  `stc_diff_amount = stc_provision_amount − stc_base_amount`. **Base against base** — corrected
  2026-09-10 from the FC reading. Legacy's `sbd_prv_amt` is the provision line's `sad_amt` on 200 of 200
  sampled taxed lines, never its `sad_fc_amt`, and `sbd_diff` is `sbd_prv_amt − sbd_amt`; it is also the
  figure the provision voucher credited the accrual with (§7.2). Measuring against the taxed FC would
  report every taxed line as under-provisioned by its own PPN. A duty line's base is its duty total, so
  PIB is unaffected either way.
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

**Which term implies which activity was not documented anywhere** — not here, not in the legacy
sources. The export team answered it on 2026-09-10 (`open-questions.md` F-INS1):

| Inco Term | Activity | Why |
|---|---|---|
| `FOB`, `CFR`, `CNF` | `INSURANCE-INL` | Cover stops at the port, so the only premium we pay is the run to it |
| everything else, `CIF` and `CIP` included | `INSURANCE` | Cover is carried to destination |

It is still a parameter — `INSURANCE_INLAND_TERMS` (§6), defaulting to `FOB,CFR,CNF` — so the export
team can correct the list on the master page rather than waiting for a release. **Note the sense: it
lists the inland terms.** It used to list the international ones, under the key
`INSURANCE_INTERNATIONAL_TERMS` defaulting to `CIF,CIP`, which was a reading of the formula and was
wrong. A term nobody has classified now falls on the international side.

**A blank Inco Term is not a term.** The screen is defaulted to the inland activity, and the pair rule
holds no opinion — either activity is accepted — because a document with no term keyed is unfinished
rather than international.

**The addon and the activity are separate rules.** The addon follows the Inco Term being exactly `CIF`
and is not configurable; the activity follows the list. A `CIF` shipment with `CIF` *on* the inland
list still earns the addon, on the inland activity.

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

Ordered lookup, first match wins (ties broken by `spa_priority` ascending). **Step 0 is not in
`ship_posting_account` and comes before all of it:**

```
0. import + expense purpose only: the PO's own spd_main_acnt
```

Then `ship_posting_account`:

```
1. direction + document + cost_type + activity + condition
2. direction + document + cost_type + activity
3. direction + document + cost_type
4. direction + document
5. direction
6. (all null — the global default)
```

**A blank discriminator is a wildcard.** The six rungs describe rules whose filled columns happen to
form a prefix of that list, and the master data in §7 of `data-migration.md` is mostly *not* shaped
that way — `PPN` + `PPN_1_1` carries no direction, `EXPENSE` + `IMPORT` + `PIB` carries no document. So
the rule is: a rule applies when every column it fills equals the line's value, and among the matching
rules the most specific wins. For prefix-shaped rules that is exactly the ladder above, and the `step`
the resolver reports still names the rung (five columns filled → step 1, none → step 6).

Read as exact per-rung signatures — which `ShipAccountResolver` did until 2026-09-03 — eight of those
thirteen rules matched nothing at all, and the two PPN rules collapsed onto the 11% account. Where two
matching rules fill the same number of columns, the one filling the broader column wins (cost type over
condition) and `spa_priority` settles an exact tie; the EMKL / `HAS_PPH` pair in §7 both match an EMKL
line that withholds PPh, so the seeder sets their priorities deliberately — **EMKL wins**, confirmed by
Finance on 2026-09-10 (`open-questions.md` Q14).

Then, only if nothing matched:

```
7. the activity master's sha_expense_* / sha_provision_acnt
9. null → validation error naming the purpose and the line
```

There is no step 8 any more: the PO override moved to the top as step 0.

`spa_condition` carries the two legacy conditional rules: `PPN_1_1` (PPN account 108005 when the PPN
percentage is 1.1, else 108004) and `HAS_PPH` / `NO_PPH` (the 401130/401134 vs 401130/401141 split).
The legacy numbers are **seed data**, listed in `data-migration.md` §7 — not constants.

**Import PO-account override — the PO wins** (Q4, answered 2026-09-10 by Finance). A purchase order
carrying its own main account beats every configured rule, however specific, and not merely the ones
that failed to match: the buyer chose that account for that purchase, and a configured rule is the
default for a purchase that chose none. That is the reverse of what shipped, where the override sat at
the bottom as step 8 and any `direction`-only row was enough to bury it.

Two guards keep step 0 narrow, and both are load-bearing:

- **import only** — export has no PO to carry an account;
- **the `EXPENSE` purpose only** — the PO carries an expense account and nothing else, so letting it
  answer a `PPN`, `PPH` or `PROVISION` lookup would post tax to a freight account. It used to, as a
  last resort, which was a latent bug rather than a rule.

Legacy's stated priority was PIB → PO account → EMKL → null; with step 0 the PO now precedes the PIB
row as well. The `cost_type = PIB` row (priority 10) and `cost_type = EMKL` row (priority 50) still
order everything a PO does not answer.

### 5.4 EMKL back-fills its PIB provision — **transitional**

> **This rule and §5.5 exist only while an import provision has no parent document.** They are the
> patch legacy needed because the Aju no. belongs to the PIB document while EMKL needs it too, so one
> provision had to copy fields off another. Once the import BL file is live (§11), Aju no., PIB no.,
> SPPB no. and the port are **columns on the BL header** and every import provision reads them from its
> parent — `PRD EXIM.md` §6.1 asks for both rules and their tests to be deleted outright.
>
> They are kept here, scoped, rather than deleted now, because Shipment Control ships **first** and
> standalone: until the BL module is live, `spv_sif_sys_id` is null on every provision and without
> these rules an EMKL provision has no port and cannot be priced. So:
>
> - `spv_sif_sys_id IS NULL` → §5.4 and §5.5 apply, exactly as written.
> - `spv_sif_sys_id IS NOT NULL` → **both are skipped**; the header fields are read from the BL and are
>   read-only on the provision screen.
>
> Retiring them is a task of its own (`tasks.md` T056), not a side effect of the BL work: it runs when
> no import provision without a BL parent is still being created, and it deletes the copy logic, the
> mismatch notice and the two feature tests together.

After a successful EMKL provision save (import) **on a provision with no BL parent**, find the `IMPORT`
provision with the same `spv_aju_no` and cost type `PIB`, and copy `spv_pib_no`, `spv_pib_date`,
`spv_sppb_no` into it. Logged against the PIB provision as a `pib` change. Carried over unchanged from
legacy §2.7.

### 5.5 EMKL takes its port from the PIB provision — **transitional**

Same scope as §5.4: applies only while `spv_sif_sys_id IS NULL`. With a BL parent the port is
`sif_port_code` and this whole ladder collapses to one read.

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

One table, two groups (`SHR_GROUP`), because both modules read it through the same repository and the
same master page.

**Group `SHIPMENT_CONTROL`:**

| Key | Default | Used by |
|---|---|---|
| `INSURANCE_UPLIFT_PCT` | `110` | §4.5 |
| `INSURANCE_RATE_PCT` | `0.0275` | §4.5 |
| `INSURANCE_CIF_ADDON` | `2` | §4.5 |
| `INSURANCE_INLAND_TERMS` | `FOB,CFR,CNF` | §4.5 — which Inco Terms mean `INSURANCE-INL` rather than `INSURANCE`; everything not on the list is `INSURANCE`. **A parameter because nothing documents the mapping**, but the list itself is now the export team's answer (`open-questions.md` F-INS1, 2026-09-10) and not a reading. Replaces `INSURANCE_INTERNATIONAL_TERMS`, which listed the other side |
| `INSURANCE_VENDOR_CODE` | *(empty)* | §12.8 — the insurer an automatically posted premium is accrued against. Nothing in the shipment's data or the ERP names one, and `spi_vendor_code` is part of the invoice's unique key, so a premium is **reported rather than posted** while this is empty. `open-questions.md` F-INS2 |
| `EDN_START_NO` | *(from legacy `ShpMasters`)* | export EDN list floor |
| `DEFAULT_BANK_CODE` | `100343` | import provision default company bank |
| `PIB_BANK_NAME` | `KAS NEGARA` | PIB forced bank display |
| `AJU_NO_LENGTH` | `14` | validation |
| `PIB_NO_MAX_LENGTH` | `6` | validation |
| `PPN_RATE_FOR_108005` | `1.1` | the `PPN_1_1` condition |
| `EXCHANGE_RATE_TYPE` | `B` | which `fm_exchange_rate` type to read |
| `OVERDUE_GRACE_DAYS` | `0` | Phase 2 Bill report |
| `FIX_BAND_BOUNDARY` | `MIN_INCLUSIVE` | §4.1 `FIX` band selection. Settled 2026-09-10 (D14a): the default stands, and no legacy line ever landed on a band edge |

**Group `EXIM_FRONT_OFFICE`:**

| Key | Default | Used by |
|---|---|---|
| `AIR_VOLUMETRIC_DIVISOR` | `6000` | §11.2 chargeable weight |
| `CURRENCY_ROUNDING_IDR` | `0` | §13.1 — decimals for IDR |
| `CURRENCY_ROUNDING_FCY` | `2` | §13.1 — decimals for every other currency |
| `DUTY_ROUNDING_BM` | `CEIL_1000` | §11.7 — BM rounded **up** to whole thousands |
| `DUTY_ROUNDING_PPN` | `TRUNC_1` | §11.7 — PPN truncated to the rupiah |
| `DUTY_ROUNDING_PPH_BASE` | `FLOOR_1000` | §11.7 — PPh base rounded **down** to thousands |
| `PPN_RATE_NOMINAL` | `12` | printed on documents only |
| `PPN_RATE_EFFECTIVE` | `11` | **what the calculation uses** — DPP nilai lain 11/12 (§11.7) |
| `PPH22_PCT_DEFAULT` | `2.5` | §11.8; overridden per HS by `shh_pph_pct` |
| `SKB_EXPIRY_WARNING_DAYS` | `30` | §11.8 |
| `DOC_REMINDER_LEAD_DAYS` | `3` | §11.6 — fallback when transit-time master has no lead days |
| `DOC_ESCALATION_LEAD_DAYS` | `1` | §11.6 notification 3 |
| `EXIM_ESCALATION_ROLES` | `Super Admin` | §11.6 notification 3 — who the escalation goes to **besides** Import, comma separated. A parameter because the spec says "their supervisor" and the application has no supervisor hierarchy to read one from: `HmEmpData` names a department, never a manager. `open-questions.md` O6 |
| `PIB_VARIANCE_THRESHOLD_PCT` | `5` | §11.6 notification 4 |
| `EIN_MISSING_WARNING_DAYS` | `14` | §12.8 — "shipped but no EIN" report threshold |

Read through `ShipParameterRepositoryInterface` with a per-request cache; a master page edits them.

> **The single most expensive parameter in this module is `PPN_RATE_EFFECTIVE`.** Every PIB prints
> `12%` while the amount on it is 11% of Nilai Impor (the DPP *nilai lain* 11/12 rule). A developer who
> reads the document and hardcodes 12% overstates every import estimate by ~9% — on a Rp 7.5 billion BL
> that is Rp 75 million, on every shipment. Both figures are parameters so the printed and the
> calculated rate can never be assumed equal.

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
| `EPJV` / `IPJV` | provision | expense account per line, at the **base** | provision account per line, at the **base**. **No tax legs** |
| `EBJV` / `IBJV` | bill | provision account per line (reversing the accrual at the base) + any base-to-base variance to the expense account + PPN per line + the **advanced** PPh to the vendor account | vendor / AP account per invoice, at the sum of the lines' `fc` + the whole PPh per line |

**This table was verified, not guessed — and the verification changed it.** The composition is the one
thing PRD §7 / S3 asks to check line-for-line against what the ERP actually holds before go-live, and
that was done on 2026-09-10 against `MGTDAT`: **970 `EPJV` / `IPJV` vouchers, not one carrying a PPN or
PPh leg**, with the accrual credited at the base even on taxed lines (8 of 8 sampled, e.g. a 1,989,020
credit against a 1,989,020 base where the fc was 1,971,118); and **1,502 of 1,627 `EBJV` / `IBJV`
vouchers carrying a tax leg**. The earlier reading — tax on the provision, none on the settlement —
produced a different document from the one legacy produced for the same shipment, on both sides.

It also makes sense of the flow. At provision time the vendor has not billed: there is no invoice, no
Faktur Pajak and nothing withheld, so the accrual carries the cost alone. The bill brings all three, so
the accrual reversal and both taxes meet on the settlement.

**The settlement's credit account is `ShipPostingPurposeEnum::VENDOR`**, resolved through
`ship_posting_account` like every other account this module posts to — there is no AP number in PHP,
and a settlement whose vendor account does not resolve refuses to confirm and says which rule is
missing. **F9 is answered (2026-09-10): `203001`, SUPPLIER (SERVICE) CONTROL ACCOUNT – LOCAL**, read off
226 payable credits across 131 recent settlement vouchers.

**The payable carries the vendor code as its sub account.** `203001` has 696 sub rows in
`FM_ACNT_COMP` and no row without one, so a payable posted against the main account alone is refused by
`fm_acnt_curr` before it reaches the ledger. That applies to the payment voucher's debit too (§7.3).

Three details the table cannot express (T016, revised T021):

- **advanced PPh is credited to the tax account and debited straight back to the vendor** — legacy's
  "Adv PPH23" leg, on 46 of the 131 most recent settlement vouchers. PPh *dibayar di muka* is not
  withheld (§4.2), so the payable is the gross and the document says so rather than netting silently.
  Where the PPh **is** withheld there is no vendor debit and the payable is already net of it.
- **the PPh credit on a rupiah line is `base + ppn − fc`, not `stc_pph_amount`.** The stored PPN and
  PPh are each rounded to the rupiah on their own, and `round(p − q)` is not always
  `round(p) − round(q)` — so copying both leaves a voucher that can be a rupiah out. The step lands on
  the withholding account, which the tax office reconciles by document. On a line in another currency
  the two taxes are stored in IDR while the base is not, so there is nothing to derive from and the
  stored figure is used.
- **the payable's USD base is the residual of that invoice's other legs.** A USD expense against a
  rupiah PPN can only balance on the USD base, and converting each leg independently leaves rounding on
  the floor. The payable is the balancing account, so it absorbs it. The provision journal needs no such
  rule any more: its two legs are one amount in one currency, and the credit takes the debit's converted
  figure rather than converting the same number twice.

**What a payment voucher debits is §7.3's, and it is neither of the accounts this paragraph used to
name.** The rule here was that a cost type with a journal relieves the accrual and one without debits
the expense account; the ERP does neither. See §7.3.

### 7.3 Payment voucher posting (ADVP / BPS, and BPJ from Jakarta)

`EloquentShipPaymentVoucherRepository::store(...)`: same period + doc-no plumbing, tran code resolved by
the user's location (`menu_user.user_field_01` — `BPJ` for Jakarta else `BPS`, as LcControl does), then
`ft_payment_header` (bank account = the implicit credit, `ph_fc_amt` = the sum of the debit lines) plus,
per line, `fs_payment` and `ft_payment_oth_acnt_detail`. No DR = CR assertion. The document reference
goes in `poad_config_fld_01`.

**What is debited has two shapes, and the cost type picks** — verified 2026-09-10 against the ERP, the
same way §7.2 was, after the first reading turned out to match neither (`open-questions.md` F-PAY1):

| Document | Debit |
|---|---|
| **Supplier advance** (ADVP, and the settlement's payment) | the **vendor / AP account, one leg per invoice**, at that invoice's total, with the **vendor code as the sub account** — 39 of 39 ADVP vouchers sampled, all on `203001` |
| **PIB** (BPS) | **one leg per non-zero duty component** — BM, PPN and PPh to `DUTY_BM` / `DUTY_PPN` / `DUTY_PPH`, each at its own amount, always IDR — 35 of 35 sampled |

**Neither debits the provision account.** The accrual raised by §7.2's journal is relieved by the
**settlement**, and debiting it here as well would relieve it twice while leaving the payable standing.
The earlier rule — provision account where a journal was raised, expense account where none was — was a
reading of "a payment relieves what was accrued" that the ERP does not follow; a PIB in particular
raises no accrual at all, and legacy pays its three duty figures to three accounts rather than netting
them onto one expense line.

**There are two payment tran codes, not three** (F-PAY2, measured 2026-09-10). `ADVP-EXP` was on this
list and in the cost-type seed for EDN, INS and COMM, and **the ERP has never held such a code**: nought
of its 276 `fm_tran_doc_no` rows, nought of its 65,434 payment vouchers, and absent from its tran-code
list altogether. The first export advance would have been refused at confirm with "Konfigurasi nomor
dokumen tidak ditemukan". What legacy raised is `ADVP` (478 import SHIP provisions) or `BPS` (510 PIB
provisions) — and **no export provision ever raised an advance at all**, which is why nobody noticed.
The seeded export cost types now use `ADVP`, and `ShipVoucherTypeEnum` no longer offers the code the
master page could have been configured with.

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
| **Bill** | one row per bill invoice | from, to, vendor, invoice no., si, ein/aju, bl, fp no., pay status (PAID/UNPAID/OVERDUE), status[] | 13 columns; period on `COALESCE(sti_invoice_date, stl_bill_date, stl_trans_date)` — **corrected 2026-09-09**: this line said `NVL(stl_bill_date, stl_trans_date)`, but the legacy query still in `MST_PARAMS` filters and orders on the invoice date first, and that is the date the vendor is owed from; `OVERDUE` = no pay date and due date in the past; `overdueDays` signed, filled only for unpaid rows |
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

---
---

# Part B — Exim Front Office

§10–§13 specify the module in front of Shipment Control: the operational documents that *cause* the
cost. Source: `PRD EXIM.md`. Everything here is new development, not a migration, so there is no
legacy behaviour to preserve and no parity to prove — the acceptance test is agreement with **real
customs documents** instead (§11.7).

---

## 10. Front office: scope, numbers and lifecycles

### 10.1 The three documents

| Document | Anchors | Feeds |
|---|---|---|
| **BIM** (`ship_bim`) | one sales contract (ESC) | the export SI, as standing terms |
| **Import file** (`ship_import_file`) | one **BL** | PIB estimation, payment requests, import provisions |
| **Export SI** (`ship_export_si`) | one shipping instruction | export provisions, through its cost lines |

The dependency runs one way only: front office → provision → settlement. Nothing in Shipment Control
reads a front-office table; it only gains **nullable** parent FKs so a provision can say where it came
from (`spv_sif_sys_id`, `spv_ses_sys_id`, `spv_source_type`).

### 10.2 Document numbers

`SysIdHelper::generate()` on new `HmMstSequences` rows, same `YYYY` + 6 digits shape as the rest of
the domain:

| Sequence | Column |
|---|---|
| `SHIP_IMPORT_FILE` | `sif_trans_no` |
| `SHIP_EXPORT_SI` | `ses_trans_no` |
| `SHIP_PAYMENT_REQUEST` | `spr_trans_no` |

BIM has **no** transaction number: it is keyed by `sbm_esc_no`, because the contract number is what
sales, the customer and the ERP all already use. Inventing a second identifier for it would be a
number nobody quotes.

### 10.3 Import file lifecycle

The BL file is a working document, not an approval chain. It has no submit and no confirm:

```
DRAFT ──→ ESTIMATED ──→ SUBMITTED_PIB ──→ CLEARED ──→ CLOSED
```

| Status | Means | Entered when |
|---|---|---|
| `DRAFT` | header + items being entered | created |
| `ESTIMATED` | PIB estimation computed and stored | estimation runs with every item carrying a `bm_pct` |
| `SUBMITTED_PIB` | Aju no. registered with customs | Aju no. + PIB date filled |
| `CLEARED` | SPPB issued, goods released | SPPB no. filled |
| `CLOSED` | actual duty recorded and variance computed | actual PIB figures entered |

Backwards moves are allowed and expected (a re-estimation after a kurs change drops `CLEARED` back to
nothing — it does not); what is *not* allowed is `SUBMITTED_PIB` without an Aju no. Recomputing an
estimate never overwrites the previous one: `sif_recompute_count` increments and the superseded figures
are kept (§11.8).

### 10.4 Export SI lifecycle

`ShipSiStatusEnum` — **its own enum**. Do not reuse `ShipStatusEnum`: the codes mean different things
and a shared enum would make "Confirmed" ambiguous on screen.

```
BOOKED ────→ CONFIRMED ────→ SHIPPED ────→ CLOSED
(SI raised)   (DO issued)     (vessel sailed)  (all cost settled)
   │              │
   └──────────────┴──────→ CANCELLED
```

| Rule | |
|---|---|
| `CANCELLED` | only from `BOOKED` or `CONFIRMED`. After the vessel sails and the B/L is issued, cancellation is not physically possible, so the transition does not exist |
| Cancel permission | none of its own — whoever may confirm may cancel. Ordinary `-edit` / `-confirm` access governs it |
| Roll-over to a later vessel | **edit the SI**, do not raise a new one. `ses_vessel` / `ses_etd` change and the activity log carries the history. No extra status |
| `CLOSED` | **computed, never set by hand** (§10.5) |

### 10.5 `CLOSED` is a computation

A cost line can appear at any time — demurrage three months after the vessel sailed — so a manually
set closing status is wrong the moment someone raises one:

```
ses_cost_complete =  no cost line with status = DRAFT
                 AND no cost line with status = APPROVED and sec_spv_sys_id IS NULL
                 AND no provisioned line without a settlement line pointing at it
```

Recomputed after every cost-line change, every provision posting and every settlement confirm. New
demurrage flips it back to false on its own; there is no reopen button and there does not need to be.

### 10.6 BIM: structured data with a template behind it

Sales enter BIM **per contract**, and one contract can produce several shipments — so the fields split
by lifetime:

| Belongs to the BIM (stable for the contract) | Belongs to the SI (differs per shipment) |
|---|---|
| incoterm, payment term, LC, currency | container type + **actual** quantity |
| consignee, notify party | actual ETD/ETA, vessel, voyage |
| nominated forwarder, BL type requested | EIN no + date, PEB no + date, EDN rows |
| carrier rule, partial shipment, fumigation | stuffing date |
| free time, sample approval, max net weight | container out / return dates |
| POD, final destination, transport mode | **actual** freight and EMKL prices |
| contract freight rate (baseline, **keyed at vendor booking**) | |
| planned container total and shipment count | |

`sbm_planned_container_total` / `sbm_planned_shipment_count` are the control that answers "this
contract planned 6 containers, 4 are on SIs, 2 to go".

**A BIM may only be raised against a confirmed ESC** — `soh_appr_status = 3` (S6, answered 2026-09-10 by
sales). That is 5,848 of the 6,134 ESCs in the ERP; the 286 unapproved ones are not offered by the
picker, some of them dating to 2016. `soh_clo_status` is deliberately **not** part of the filter: it is
set on 106 rows in fourteen years, so "closed" is not a state that table keeps.

**The column-vs-free-text rule:**

> If a value is used to **calculate**, **validate**, **trigger a notification**, or **raise a cost
> line**, it is a column. Everything else may stay free text.

That is why `free_time` is a column even though today's data contains the word `Regular`
(`sbm_free_time_basis` = `DAYS` / `CARRIER_STANDARD` / `NOT_APPLICABLE` carries the difference), and why
`net_weight_max` is a column — exceeding it costs a penalty.

**Template inheritance.** A customer with dozens of near-identical contracts must not produce dozens of
drifting copies:

```
ship_bim_template (per customer)   standing-term defaults, maintained by sales
        │ inherit at create
ship_bim (per ESC)                 always editable; overrides are recorded
        │ pull at SI create
ship_export_si                     snapshot — never changes afterwards
```

On screen the button still reads **"copy from previous BIM"**, because that is what sales know. What it
copies is the template, not a loose record. Change the customer's consignee once and every BIM not yet
pulled into an SI follows; the ones already pulled do not move, because the SI holds a snapshot.

**The contract freight rate is typed, and the ESC value is only shown beside it** (S3a, answered
2026-09-10 by the export team). It is **keyed by the export team when they book the vendor** — that is
the moment the rate is actually agreed, and no earlier record of it is authoritative. The ESC does carry
a number, `OT_SO_ITEM.SOI_FLEX_10` per container in USD with the incoterm in `OT_SO_HEAD.SOH_FLEX_01`
(measured: on 4,895 of 19,926 ESC items, 739 distinct values, mostly 800–3,200), and it is worth reading
— **as a reference shown next to the field, never as the value written**. This reverses the earlier
"pulled, not typed": pulling would have stamped a pre-booking figure onto the baseline every margin
comparison is then made against.

Once keyed it is **frozen** and never updated; the actual booking price goes on the SI, and the gap
between them is **margin erosion, not cost variance** — a sales number nobody measures today. If a
contract is genuinely renegotiated the baseline is **superseded with history**
(`ship_bim_freight_history`), never overwritten.

Where one ESC carries several item rows with different rates, **warn and carry on** (S4, same date): show
the disagreement with the rates it found and set `sbm_review_pending`, but never block the SI and never
average them — an averaged rate hides the case where one row was actually a shipment total. The export
team clears the flag by keying the rate they booked, which is the same action the field asks for anyway.
Measured, this fires on 25 of 4,717 rate-carrying ESCs: roughly twice a year.

**The anti-dumping-ground guard.** On save, `sbm_special_instruction` is scanned for terms that belong
in structured columns — incoterm names, "free time", "demurrage", "forwarder", "transhipment", carrier
names. A hit raises a **soft warning** ("this looks like field X — fill it in there?"). It never blocks
the save. The point is to stop the new flex field becoming the old one.

---

## 11. Import: the BL file

### 11.1 The BL is the anchor

Confirmed with the import team, and it fixes a structural weakness in Shipment Control:

- **1 BL = 1 AJU = 1 PIB**
- **1 BL = 1 supplier**, no exceptions — for LCL what gets entered is the **House BL**, so the rule
  holds
- 1 BL may cover several POs; 1 PO may be split across several BLs

Because 1 BL = 1 supplier, the supplier lives on the **BL header**, not on the invoice; invoices inherit
it. Because 1 BL = 1 AJU = 1 PIB, Aju no., PIB no./date, SPPB no. and the customs port are all header
columns — which is what retires §5.4–§5.5.

### 11.2 Transport mode decides the form

| | `SEA_FCL` | `SEA_LCL` | `AIR` | `COURIER` |
|---|---|---|---|---|
| Quantity basis | container | CBM | chargeable kg | kg |
| Transport document | B/L | House B/L | AWB | tracking no. |
| EMKL applies | yes | yes | — | — |
| Demurrage / detention | yes | — | — | — |
| Free time relevant | yes | — | — | — |
| SI raised (export) | yes | yes | yes | **yes** |
| Goes through a provision | yes | yes | yes | **yes** |

`COURIER` (DHL, FedEx) is **not** an exception: it gets an SI and a provision like everything else. Only
the cost structure differs — usually one all-in line.

Air vendors sit in the **same vendor group** as forwarders and EMKL; only the activity differs. No
separate vendor master is needed — a new activity code with the right UOM covers it. The precedent is
already in the data: JASCO LOGISTICS carries `AIRWAYBILL`, `STORG-HANDL-FEE`, `STORG-CARGO`,
`STORG-AIRPORT-CHG` with UOM `KGS`.

**Chargeable weight** (`AIR`) is computed, never typed — it is the basis of the bill and the thing most
often disputed with the forwarder:

```
volumetric_kg = (L × W × H in cm) / AIR_VOLUMETRIC_DIVISOR      -- parameter, default 6000
chargeable_kg = MAX(gross_weight_kg, volumetric_kg)
```

`sht_uom` gains `CHARGEABLE_KG` so the tariff can key on it (§4.1).

### 11.3 ETA comes from ETD plus transit time

```
eta_calculated = etd + transit_days
```

`transit_days` resolves from `ship_transit_time`, most specific first:

1. (origin port, destination port)
2. fall back to origin country

The master supports both, so a country-level row works on day one and a port-pair row can be added later
without touching code.

Two rules:

- `sif_eta_calculated` and `sif_eta_actual` are **separate columns**. The arrival notice from the
  shipping line is the truth; notifications use the best value available.
- `transit_days` is **snapshotted** onto the BL. Editing the master must not move the ETA of a shipment
  already in flight.

### 11.4 Three levels, and the item controls

One BL can carry several invoices from the same supplier (Oerlikon: six). Freight and discount attach to
the **invoice**; the insurance premium attaches to the **BL**. So items cannot hang directly off the BL:

```
ship_import_file (BL)
  ├── ship_import_invoice   invoice no + date, currency, freight_total, discount_total
  │     └── ship_import_item   po_no + po_line, item codes, hs_code, coo, bm_pct,
  │                            shipped_qty, rate, FOB value, allocations, CIF, duty
  ├── ship_import_container
  └── ship_import_doc         checklist row + uploaded file
```

Three item-level controls are mandatory:

**Two goods names.** `sit_item_name_po` from the PO, `sit_item_name_doc` as the invoice and packing list
describe it. The second is what customs goes by, and they are routinely different.

**Remaining quantity per PO line.** When pulling a PO into a new BL, the quantity offered is the
**remainder**, not the full PO quantity:

```
remaining = po_qty − Σ shipped_qty across every BL for that PO line
```

Without this, a PO shipped in two parts produces the full duty estimate twice. Legacy already had the
idea in its PO picker; it moves down to item level.

**HS code from the PO is a reference, not a key.** HS is mandatory on the PO but its **format is not
consistent**, so it is pulled into `sit_hs_code_po` as-is. Normalisation (strip dots and spaces, take 8
digits) writes `sit_hs_code`; if that value exists in `ship_hs_tariff`, `bm_pct` is defaulted from it.
Otherwise the user types the rate. Never treat the PO's HS string as a trusted lookup key.

### 11.5 Validation gates

| Gate | Rules |
|---|---|
| `header` | BL no. (unique) + date; supplier; origin country; incoterm; transport mode; port; ETD; kurs pajak resolved for the estimated PIB date |
| `invoices` | at least one; invoice no. distinct within the BL; currency; date |
| `items` | at least one per invoice; PO no. + line; `shipped_qty` > 0 and ≤ remaining; UOM; rate |
| `estimate` | **every** item has a `bm_pct` — an item without one is a blocker, never silently zero (§11.8); kurs pajak present; insurance premium currency set when a premium is entered |
| `submit_pib` | `estimate` + Aju no. (14 chars) + PIB date; SKB still valid at the PIB date when `sif_pph_exempt = 1` (§11.8) |
| `clear` | SPPB no.; `sif_doc_complete_flag` — or an explicit override with a reason, per `open-questions.md` O4 |

### 11.6 Document checklist and notifications

The checklist is **generated**, not a fixed list. `ship_doc_type` holds the conditions
(`applies_origin_country`, `applies_hs_prefix`, `applies_incoterm`, `applies_facility`,
`applies_transport_mode`; null = applies always) and the app evaluates them against the BL when it is
created. `sif_doc_complete_flag` = every mandatory row has a file. A new FTA or a new regulation is a
**master row**, not a deployment.

| Document | Mandatory when |
|---|---|
| Bill of Lading (original or telex release) | always |
| Commercial Invoice | always |
| Packing List | always |
| Purchase Order / Sales Contract | always |
| Certificate of Origin (Form E / D / AK / AI / IJEPA) | claiming a preference tariff; which form depends on origin country |
| Insurance policy / certificate | incoterm CIF or CIP |
| Certificate of Analysis | chemicals / raw material |
| MSDS | hazardous goods |
| Import approval / Lartas permit | depends on HS code |
| Surveyor report | depends on HS code |
| Surat Kuasa PPJK | a broker is used |
| Phytosanitary / fumigation certificate | wooden packing or organic goods |
| SKB PPh 22 | claiming PPh exemption |
| Delivery Order | never — the DO is **issued after** the DO payment. It is an outcome, not a prerequisite |

**Document codes are the official customs codes**, not invented ones, so the checklist compares directly
against the PIB attachment list:

| Code | Document | | Code | Document |
|---|---|---|---|---|
| `380` | Invoice | | `740` | AWB |
| `457` | Surat Keterangan Bebas (SKB) PPh | | `741` | Master AWB |
| `465` | L/C | | `860` | Electronic Certificate of Origin (ECO) |
| `705` | B/L | | `861` | Certificate of Origin (CO) |
| `959` | Surat Persetujuan Impor Dep. Dag. | | `864` | Deklarasi Asal Barang (DAB) |
| `998` | SKEP Fasilitas Kemudahan Ekspor (KITE) | | | |

**Notifications:**

| # | Trigger | To | Notes |
|---|---|---|---|
| 1 | ETA − N days | Purchase | **N per origin country** (`stt_notify_lead_days`), falling back to `DOC_REMINDER_LEAD_DAYS`. China's transit is short, Europe's long — one global number is wrong for both |
| 2 | checklist completeness changes | Import | complete / not complete |
| 3 | ETA − 1 day with documents still incomplete | Import **and their supervisor** | **escalation**; includes the preference exposure figure (§11.8) |
| 4 | estimate vs actual PIB differs by more than the threshold | Finance | `PIB_VARIANCE_THRESHOLD_PCT`, default 5% |

Notification 3 earns its keep: late documents are the direct cause of demurrage and storage — the most
expensive and least visible cost in the module, invisible until the invoice arrives.

### 11.7 PIB estimation and duty rounding

Taken from actual practice (`CARA_PERHITUNGAN_UNTUK_BUDGET_PIB.xlsx`) and **verified against 10 issued
PIBs**.

**Allocation is always proportional by value.** Confirmed with the import team; there is no other basis.

**Which components are added is decided by the incoterm** (§11.8 table). Two modes, and the mode is
derived from the incoterm — the user cannot pick it:

**Mode A — the PO value is already CIF** (`CIF`, `CIP`, `CFR`, `DAP`, `DDP`). The freight and insurance
fields stay editable, but they are a **breakdown, not an addition**:

```
cif_idr     = po_value × kurs_pajak            -- CIF is the PO value, whatever freight says
fob_display = po_value − freight − insurance   -- for filling the PIB form only
bm          = cif_idr × bm_pct
nilai_impor = cif_idr + bm
ppn         = nilai_impor × PPN_RATE_EFFECTIVE
pph         = nilai_impor × pph_pct            -- 0 while an SKB applies
budget      = bm + ppn + ppnbm + pph
```

**Mode B — the PO value is still FOB/EXW** (`FOB`, `FCA`, `EXW`). Freight and insurance **add**:

> `CFR` and `CPT` are missing from both lists above, and that is not an oversight in the modes — it is
> why the mode alone is not enough. Both are Mode A for **freight** (the seller pays the carriage, so
> nothing is added) and still **add the insurance premium**, per the §11.8 table. `ShipIncotermEnum`
> therefore answers two questions, `addsFreight()` and `addsInsurance()`, and `cifMode()` is derived
> from the first. Read the mode letter alone on a `CPT` shipment and the premium is dropped.

```
-- per invoice, in the invoice currency
discount_i  = value_i / Σ invoice_value × invoice_discount_total
freight_i   = value_i / Σ invoice_value × invoice_freight_total
value_idr_i = (value_i − discount_i + freight_i) × kurs_pajak(invoice currency)

-- per BL, in IDR
premium_idr   = premium × kurs_pajak(premium currency)
insurance_i   = value_idr_i / Σ value_idr(all items on the BL) × premium_idr

-- per item
cif_idr_i = value_idr_i + insurance_i
bm_i      = cif_idr_i × bm_pct_i
… continues as Mode A, summed per invoice then per BL
```

> **Merge the two modes and every CIF shipment with a freight figure entered over-declares BM.** The
> mode follows from the incoterm and must not be overridable.

Two subtleties to copy exactly:

- **Discount and freight allocate per invoice; the insurance premium allocates across the whole BL.**
  One policy is issued per shipment, while freight and discount belong to their own invoice.
- **Insurance allocates in IDR**, because the premium currency can differ from the invoice currency.
  A useful side effect: one BL may hold invoices in several currencies.

**Rounding — three different rules, one per levy.** Verified against 10 PIBs: 28 of 30 figures matched
to the rupiah; the 2 misses were ≤ Rp 4, caused by the PDF printing per-item customs value to 3 decimals.

| Levy | Rule | Verified |
|---|---|---|
| **BM / BM KITE** | `Nilai Impor × rate`, then **rounded UP to whole thousands** | 4/4 PIBs with BM |
| **PPN** | `full Nilai Impor × 11%`, then **truncated to the rupiah** | 8/10 exact |
| **PPh 22** | `Nilai Impor rounded DOWN to thousands`, then `× rate` | 10/10 exact |

Two traps:

- **PPN uses the *unrounded* BM**, while what is actually paid to the state is the rounded-up BM. Two
  different BM figures exist inside one calculation.
- The calculation runs **per item line and is then summed**, not once on the total.

```
per item:
  cif_idr = item_customs_value × kurs_pajak
  bm_raw  = cif_idr × bm_pct
  ni      = cif_idr + bm_raw                    -- bm_raw, NOT the rounded BM
  ppn    += TRUNC( ni × PPN_RATE_EFFECTIVE )
  pph    += FLOOR_1000( ni ) × pph_pct

per BL:
  bm    = CEIL_1000( Σ bm_raw )
  total = bm + ppn + ppnbm + pph
```

Verification anchor — Oerlikon: CIF EUR 5,290.26 × 20,710.98 → BM 5% = 5,478,323; PPN 11% of (CIF+BM) =
12,654,927; total **18,133,251**, matching cell `I43` of the working spreadsheet.

Rounding **outside** the levies follows the ordinary currency rule (§13.1).

### 11.8 BM%, preference schemes, kurs pajak and SKB

**Which components the incoterm adds to the customs value** — verified against 10 actual PIBs:

| Incoterm | Insurance added | Freight added | Example PIB |
|---|---|---|---|
| `CIF`, `CIP` | — | — | 294, 304, 307, 310, 311, 292, 259 |
| `CPT`, `CFR` | **yes** | — | 285 (insurance EUR 5.16, freight 0) |
| `FOB`, `FCA`, `EXW` | **yes** | **yes** | 309, 293 |
| `DAP`, `DDP` | — | — | — |

`CPT` covers freight but **not** insurance, so insurance is still added — which is why this is a table
per incoterm and not a two-mode switch.

**BM% lives on the item**, because one BL can carry goods at different rates, and it must be filled
before the estimate can run.

**A facility does not change BM%** — confirmed by the tax team and consistent with the PIBs. But the
PIBs show something else that matters: **`BM KITE` is a separate levy line from `BM`**. On PIB 000309
and 000310, `BM` is 0 and `BM KITE` carries the rate (4% and 5%), both in the **Dibayar** column — so
cash leaves at import and it **belongs in the PIB budget**.

Its accounting treatment is the same as ordinary BM (confirmed with Finance): not booked as a
receivable, not posted separately. A BM KITE liability arising later from an audit is posted as an
expense at that time — a separate event, outside this module. So `sit_duty_type` = `BM` / `BM_KITE`
**does not drive an account** in v1. The column exists for two cheap reasons: the estimate has to be
comparable line-for-line with the actual PIB, and Finance gets a "BM KITE paid per period" figure —
exactly what a KITE audit asks for.

**Rate resolution, in order:**

| Order | Source | `sit_bm_source` |
|---|---|---|
| 1 | **Preference tariff** — a scheme is selected *and* the COO/DAB exists and is valid | `PREFERENCE` |
| 2 | **MFN** from `ship_hs_tariff`, if the HS is in the master | `MFN` |
| 3 | **Manual**, with a mandatory reason | `MANUAL` |

**Four preference schemes are in active use**, not just Form E:

| Scheme | Origin | Document | Example PIB |
|---|---|---|---|
| ACFTA (ASEAN–China) | China | ECO / Form E | 294, 307 |
| AIFTA (ASEAN–India) | India | CO | 309, 311 |
| ATIGA (ASEAN) | Vietnam | ECO Form D | 292 |
| RCEP | China | DAB (Deklarasi Asal Barang) | 304 |

> PIB 000304 and 000307 are both from China under **different** schemes. So the scheme is **chosen by
> the user, never inferred from the origin country**. `ship_hs_preference` is keyed on
> (HS code, origin country, scheme).

Scope for v1 is these four; IJEPA, IK-CEPA and IA-CEPA are added as master rows when needed, with no
code change.

**The tariff master is an accelerator, not a prerequisite.** `ship_hs_tariff` is filled by hand for the
HS codes actually used — not the whole BTKI — as work goes along. Day one it may be empty: every BM% is
typed and the module works. The more rows exist, the less typing.

**Preference exposure — two figures, not one.** A 0% BM depends on a Form E being issued and accepted.
If it is not, BM jumps to MFN. For the Zhejiang Hengyi BL (import value Rp 1.4 billion) the difference
between 0% and MFN 5% is ±Rp 70 million of duty plus PPN on top — **±Rp 78 million of completely
unbudgeted exposure.** So the screen shows the value with the preference *and* the value if the
preference fails.

Its limit is honest: the second figure needs a known MFN rate. For an item with
`sit_bm_source = MANUAL` whose HS is not in the master, `sit_bm_pct_fallback` is **optional** — filled,
the exposure shows; empty, the BL is marked `sif_exposure_complete = 0` and the exposure is labelled
incomplete. **Never present a partial exposure figure as if it were whole.**

**Kurs pajak (NDPBM)** is the weekly **KMK** rate, and the same rate applies to BM, PPN and PPh 22
import — one master is enough (confirmed).

> This is **not** the ERP's daily `fm_exchange_rate` **type `B`**. Pull the bank rate and the estimate
> will never reconcile with a PIB.

**But the ERP does hold the KMK rate, and nobody should be re-keying it** (D17, measured 2026-09-10):

| Where | What it holds |
|---|---|
| `MGTDAT.FM_EXCHANGE_RATE_KMK_MGT` | one row per KMK week — 669 of them, 2013-04-24 onward, no gaps — with the **decree number** in `kmk_flex_01` (`39/MK/EF.2/2026`) and its date in `kmk_flex_02` |
| `MGTDAT.FM_EXCHANGE_RATE`, type **`T`** | the **rate** for those same weekly windows. 597 of the 669 KMK weeks match a `T` row exactly; the week of 2026-08-26 reads 17,796 |

So the front office **reads type `T` behind a repository**, exactly as `ShipExchangeRateRepositoryInterface`
already reads type `B` for the provision's USD base, and prints the decree number from the KMK table onto
the PIB. Two consequences: `ship_kurs_pajak` is **not** a weekly data-entry chore, and it seeds **empty**
like `ship_hs_tariff` — its remaining job is any currency the ERP's KMK table does not carry, which today
is every currency except **USD**.

Three rules:

- The rate is **snapshotted** onto the BL together with its KMK period.
- **Which rate applies is decided by the PIB registration date** — not the BL date, not the ETA. The
  estimate is made weeks earlier, so the rate used at estimation time is almost never the final one.
  Provide a **recompute** button that keeps history; never overwrite the earlier figures.
- The variance report **separates the exchange-rate difference from the tariff and quantity
  differences** (`sif_var_kurs`, `sif_var_tariff`, `sif_var_qty`). Without that split Finance cannot
  tell a bad estimate from a moving rate.

**How the split is actually computed (T058).** `actual − estimate = kurs + tariff + qty`, and the three
always reconcile:

| Leg | Where it comes from |
|---|---|
| `kurs` | The estimate re-priced at the rate the PIB registered under, less the estimate as it was made. **Measured**, from `sif_est_kurs_pajak` against `sif_kurs_pajak` — both apply to the same base, so the leg is the estimate scaled by their ratio |
| `tariff` | The BM customs assessed against the BM estimated, **per item**, at the PIB's rate — so one HS code reclassified reads as itself rather than as "the estimate was out" |
| `qty` | The **residual**: quantity, declared value, freight and insurance allocation, and the rounding the duty rules apply at every step. Labelled as a remainder, because nobody records what customs thought the quantity was and a third measured-looking figure would be believed |

`sif_est_kurs_pajak` exists for this and nothing else: the recompute button moves `sif_kurs_pajak` to the
PIB date's KMK week, so without a second column the rate leg would be a guess. A BL estimated before that
column existed reports the leg as **unmeasurable** rather than as zero — the sheet's `kurs_measurable`
column says which — because calling an unknown zero pushes the whole difference into the residual and
makes the estimate look worse than it was.

**PPh 22 and SKB.** PPh 22 is **0 while the SKB is valid**; otherwise `PPH22_PCT_DEFAULT`, overridden per
HS by `shh_pph_pct` (2.5% generally, 7.5% for some HS — see PIB 000310, HS 27101945).

The SKB is its own record (`ship_skb`): number, date, validity period, scope. The BL points at the SKB it
relies on. The app **refuses** to mark PPh exempt when the SKB has expired as at the estimated PIB date,
and **warns** when it expires within `SKB_EXPIRY_WARNING_DAYS`. The SKB is also one of the uploaded
documents (code `457`).

Miss an expiry and the budget is short by rate × Nilai Impor — on a Rp 1.4 billion BL at 2.5%, ±Rp 35
million. **The SKB is renewed quarterly**: `KET-00014/PPUT-CT/KPP.3213/2026` (2026-05-04) was used on
July PIBs, `KET-00025/…` (2026-08-04) on August ones — exactly three months apart. The validity check is
not a theoretical guard; it fires four times a year.

### 11.9 Payment requests

The budget is **information for Finance**. No approval, no reject:

```
DRAFT ──→ SENT ──→ PAID
```

No journal, no voucher. There is deliberately **no `REJECTED` status** — Finance does not refuse a
customs payment, they pay it and record the difference.

| Cost type | Route |
|---|---|
| `DO` | payment request, amount **entered by hand** |
| `PIB` | payment request, amount from the PIB estimation |
| `EMKL` | **no payment request** — the commitment goes straight to a provision (§11.10) |

**A hand for the DO amount.** The import team currently looks up the last transaction to decide the DO
rate. So when a DO payment request is created, the screen shows the **last 3 DO payments for that
shipping line** (date, amount, BL). Not a master, just history — but it removes the manual hunt. If the
pattern proves stable after a few months, a DO master can be seeded from the same data.

Finance pays and **records the variance**: budget, invoice and difference side by side, plus a reason
column. The difference is stored (`spr_variance_amount`, `spr_variance_note`), not merely displayed.

### 11.10 EMKL and storage

**EMKL — the commitment becomes the provision directly.**

1. EMKL commitment → cost lines generated from the tariff master (breakdown per activity)
2. Provision posted per **BL**, dated at the commitment/booking date — usually the same day the PIB is
   paid
3. The invoice arrives → compared per activity against the provision for the same BL

| Case | Treatment |
|---|---|
| invoice = provision | reverse the provision in full |
| invoice > provision | reverse the provision, the excess to expense |
| invoice < provision | reverse the provision, the shortfall credited to expense |
| provisioned but **never billed** | provision is **reversed**, so nothing is left hanging (`open-questions.md` F6 decides the period it reverses in) |
| billed but **never provisioned** | **direct expense** with no provision reference (`stl_source = DIRECT`) |

The BL anchor pays off here too: the EMKL tariff is priced per port, and the port is now a column on the
BL header, so the EMKL provision reads it from its parent.

**Import storage (penumpukan)** is recorded **after the invoice arrives**, usually together with the EMKL
invoice, and its rate is tiered per day. Because it is only known at invoicing, storage is **not
provisioned up front**: it comes in as a settlement line with no provision (`DIRECT`) against the same
BL. The consequence is that **storage in/out dates are not entered in this module** — the day count
comes from the invoice. The `DAYS` tariff bands are still used, to **validate** the invoiced amount
against the applicable tiered rate (block or warn: `open-questions.md` D16).

**Both halves were answered 2026-09-15, and both are parameters.** F6 → `PROVISION_REVERSAL_PERIOD` =
**`CURRENT`**: an unbilled accrual reverses in the period it is noticed in, because a back-dated one is
refused outright once the ERP period closes — which is months after anyone can act on it. The month that
carried the accrual therefore stays overstated, and what closes that gap is reviewing the hanging list
monthly, not the parameter. D16 → `STORAGE_TARIFF_ENFORCEMENT` = **`WARN`**: the screen names the banded
figure beside the invoiced one and the settlement still saves, because blocking would assert the tariff
master is always current and leave the settlement desk stuck when a band goes stale. Either can be
revisited without a release. A line is recognised as per-day by its **tariff**, not by its activity code
— the master already knows which activities are billed per day, and the same check covers export
demurrage.

---

## 12. Export: the Shipping Instruction

### 12.1 Why the SI is not shaped like a BL

**A BL is a document of fact; an SI is a document of commitment.** A BL says what is arriving and its
cost follows later. An SI *contains the agreed prices*, and those prices are Finance's benchmark when
the invoice arrives. So an SI carries cost lines from birth.

Four moments that cannot be collapsed into one:

| When | What | Where the number comes from |
|---|---|---|
| at contract | BIM created, freight rate agreed | ESC |
| before the vessel | SI created, booking price agreed | booking negotiation, tariff master as default |
| after shipping, once the invoice is issued | provision posted | SI + EIN |
| the following month | vendor bill arrives, compared, posted | vendor invoice |

**The four-point variance chain:**

```
contract rate (BIM) → SI price → provision → vendor bill
        └─ margin erosion ─┘ └─ variance 1 ─┘ └─ variance 2 ─┘
```

- **Margin erosion** — the market moved between the contract deal and the booking. A sales number, not
  cost control, and nothing measures it today.
- **Variance 1** — did we provision what we agreed at booking?
- **Variance 2** — did the vendor bill what was agreed?

Legacy had variance 2 only.

**The SI pulls defaults from the tariff master, but the price stored on the SI wins** — and it, not the
master, is the provision's baseline. Useful side effect: when SI prices routinely diverge from the
master, that is a measurable signal the rate card needs updating.

### 12.2 One cost table, status per line

Every SI cost lives in one table — freight, EMKL, cancellation fee, demurrage, detention, early pickup,
DAP destination charges. What separates them is columns on the row, not separate tables.

**Per-line approval is not a separate document.** Making additional cost its own document splits an SI's
cost across two places and breaks per-SI monitoring.

```
ship_export_si_cost
  ses_sys_id, vendor, cost_type, activity
  qty, rate, currency, ppn/pph, accounts
  sec_is_additional   0 = baseline (freight, EMKL) · 1 = tentative
  sec_status          DRAFT → APPROVED → VOID
  sec_approved_by, sec_approved_at, sec_approval_note
  sec_approval_ref, sec_approval_offline_by     ← the real approver works outside the app
  sec_spv_sys_id      null = not yet provisioned
```

- **Baseline** lines are approved together when the SI moves to `CONFIRMED`.
- **Additional** lines are approved one at a time, whenever — including months after the SI is
  `SHIPPED`.
- The genuine approver is senior and does not use the application, so the row carries the **offline
  approver's name and the approval reference**. Pretending otherwise would mean either a fake in-app
  approval or no record at all.

### 12.3 `sec_spv_sys_id` is what solves the period problem

Demurrage surfacing three months after shipping belongs in the period the cost was incurred, not the EIN
period. With this column the provision becomes a **posting event, not a container**:

```
provision = every cost line where sec_status = APPROVED and sec_spv_sys_id IS NULL
```

Run it when the EIN is issued → the baseline lines are taken and posted at the EIN date. Run it again
three months later once demurrage is approved → only those lines are taken, into their own period.

The pattern is identical to `stc_spc_sys_id` in Shipment Control (§4.4), one level earlier in the chain.

### 12.4 Cancellation is a screen, not a button

A cancelled SI may already carry EMKL cost that genuinely happened. On cancel the user decides, line by
line:

1. which lines **remain payable**
2. which lines are **written off** (`sec_status = VOID`, with `sec_void_reason`)
3. whether a **cancellation fee** applies — entered by hand as a line with `sec_is_additional = 1` and
   cost type `CANCELLATION_FEE`, because plenty of SIs are cancelled with no fee at all

A cancelled SI still produces a provision; it just contains part of the cost. The cancellation fee never
comes from the tariff master, and its posting accounts are the only configuration it has
(`open-questions.md` F2).

### 12.5 Additional cost fits in the existing tariff master

Demurrage and detention carry tiered per-day rates from the carrier — that is `TIER` shape with UOM
`DAYS`, and the precedent already exists in the data (`STORG-REG-MASA`). No new tariff mechanism.

The cost-type master gains exactly two flags:

| Flag | Freight, EMKL | Demurrage, detention, early pickup, cancellation fee, destination charges |
|---|---|---|
| `shc_is_additional` | 0 | 1 |
| `shc_requires_line_approval` | 0 | 1 |

### 12.6 New data the export team must enter

Confirmed available: **the agreed free time, the date the container left the depot, and the date it came
back.** Without those three, a demurrage quantity can only be typed by hand and cannot be checked against
the carrier's invoice — which is the whole point of holding the rate. They are columns on the SI
(`ses_free_time_days`, `ses_container_out_date`, `ses_container_return_date`) and they feed the `DAYS`
quantity in §4.1.

### 12.7 Cardinality and multi-BIM SIs

Confirmed: **1 SI = 1 EIN = 1 PEB = 1 vessel/voyage = 1 POD.** But:

- 1 EIN may cover several ESCs (`COMBINE WITH ESC-…`)
- 1 EIN may cover several EDNs

Since a BIM is per ESC, an SI pulls from **several BIMs**, and **the export team decides which BIMs to
combine** when creating the SI. Merge rules when the BIMs disagree:

| Field | If they differ |
|---|---|
| Incoterm, POD, transport mode, currency | **Refuse** — these cannot be combined into one SI |
| Consignee, notify party | **Refuse** — one B/L, one consignee |
| Payment term / LC | **Warn** — two payment schemes on one invoice needs Finance to confirm |
| Carrier rule | take the **intersection** of what is allowed |
| Free time | take the **smallest** |
| Max net weight | take the **smallest** |
| Fumigation | if any BIM requires it → **required** |
| Partial shipment | take the **strictest** |
| Special instruction | concatenate, tagging each with its ESC |

The principle: whatever defines **document identity** must match; whatever is a **constraint** is taken at
its strictest. A refusal must name the field and the two BIMs that disagree — "cannot combine" alone
sends the user hunting.

### 12.8 The export provision

- Posted **per SI**.
- **The provision date is the sales invoice (EIN) date**, normally backdated. EIN no. and date are fields
  on the SI, filled once the invoice is issued, and the app **blocks provision posting while the EIN is
  empty**.
- **Backdating into a closed period is refused.** A provision may only post while its period is open,
  even though the date is in the past. The app checks the ERP period **before** posting rather than
  failing halfway (§7.2; policy for what Finance then does: `open-questions.md` F1).
- Report **"shipped more than `EIN_MISSING_WARNING_DAYS` ago with no EIN"** — that gap is the direct cause
  of a provision missing its period, which is now a refusal rather than merely late.

**Commission and insurance are computed per EIN**, not per ESC — one commission line and one insurance
line per SI, on the invoice value. Since 1 SI = 1 EIN the granularity matches the SI exactly.
`spv_source_type` (`SI` / `ESC` / `MANUAL` / `FILE`) records where the figures were drawn from. No
sub-menu needed. Where several ESCs with different agent rates are combined into one EIN, the commission
basis is open — `open-questions.md` F8a.

**They cannot literally sit in the same provision, and the schema is why.** `spv_vendor_code`,
`spv_cost_type` and `spv_currency` are **header** columns, so the shipping line, the forwarder, the agent
and the insurer are four documents from one posting run — one provision per (vendor, cost type,
currency). Currency belongs in that key for its own reason: a forwarder billing part in rupiah and part
in dollars is two accruals, and one header rate would convert one of them wrongly.

**An accrual has to owe somebody.** `spi_vendor_code` is NOT NULL and part of the invoice's unique key,
so a premium with no insurer cannot be stored at all. The insurer is the `INSURANCE_VENDOR_CODE`
parameter (§6), **seeded empty**; while it is empty the premium is computed, reported with its figure and
**not** posted, and everything else on the run posts as normal. The same rule holds for a commission
whose agent matched no supplier — the quote is reported and the document is raised by hand against the
right vendor. See `open-questions.md` F-INS2.

---

## 13. Rules shared by both directions

### 13.1 Currency rounding

| Currency | Rule |
|---|---|
| IDR | round to the unit, no decimals (`CURRENCY_ROUNDING_IDR`) |
| anything else | 2 decimals (`CURRENCY_ROUNDING_FCY`) |

Applies to every value in the front office. **Customs levy rounding is a separate set of rules** —
§11.7.

### 13.2 Pulling a provision into a bill

```
candidate provisions = vendor + parent document reference
   import : BL   (= AJU = PIB)
   export : SI
```

Additional-cost lines follow the same rule — their reference is still the SI, so demurrage from the same
vendor is picked up when that vendor's invoice arrives. The reversal mechanism for provisioned-but-unbilled
lines (§11.10) works identically on both sides. **One service, two directions** — do not fork it.

**The candidate is a provision *invoice*, not a provision** (settled in T018). A provision carries one
invoice per vendor and a settlement is one vendor's bill, so a provision that names two vendors is two
candidates, not one — `ShipBillService::startFromProvisionInvoice()` is the entry point and
`startFromProvisionLines()` is the same thing for a selection spanning several.

**And one vendor invoice is one settlement invoice, even across two shipments** (Q16, answered
2026-09-10 by Finance). Picking a provision invoice seeds the draft with every unbilled line that
vendor's *number* covers, wherever it was provisioned — same vendor, same direction, settleable
provisions only. A forwarder that bills two shipments on one invoice is exactly what the merged
settlement exists for, and settling those provisions one at a time produced two `ship_bill_invoice`
rows carrying the same (vendor, number), which T028's unique guard refuses — correctly, since the same
invoice number settled twice is how a vendor gets paid twice. The alternative Finance rejected was
making the user renumber, which puts a number on the document that the vendor never issued.

What a merge does to the header, and why each is not a choice:

| Field | Merged how | Forced by |
|---|---|---|
| `stl_si_no`, `stl_ein_no` | joined, distinct | 500-character columns exist for it |
| containers | unioned and **summed per type** | `uq_ship_bill_cont` is (bill, type) |
| docs | unioned, deduped on (type, number) | `uq_ship_bill_doc` |
| invoices | grouped by invoice number | `uq_ship_bill_inv` is (bill, invoice no.) |
| `stl_aju_no`, `stl_pib_no` | the primary's, **not** joined | single-valued columns, and a merge is within one BL |

Two selections are refused rather than merged: more than one vendor, and export mixed with import. A
line another settlement already claims is dropped from the selection — `uq` on `stc_spc_sys_id` would
reject it at insert time, which is far too late to explain.

### 13.3 Accounts on tariff rows are defaults

The accounts stored on a tariff row are **defaults only**. The final account is decided by
`ShipAccountResolver` against `ship_posting_account` (§5.3), which Finance owns. Inconsistencies in the
legacy data (`EMKLSTORAGE` 404033 vs 404004) therefore need no cleanup at source.
