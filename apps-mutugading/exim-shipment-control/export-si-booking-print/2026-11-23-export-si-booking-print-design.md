# Export SI — booking parties, pickers, BIM drawer, print

## Why
Export books the shipment with a Forwarder and an EMKL, then sends them the
Shipping Instruction sheet (today: hand-made, see `.docs-me/SI-579.pdf`). The SI
screen should hold everything that sheet needs and print it.

## Goals
1. Forwarder: vendor code (hidden, optional), name, booking currency + amount.
2. EMKL: vendor code (hidden, optional), name, booking currency + amount.
3. Supplier picker (modal) for both; the name stays typeable by hand.
4. Booking Container no. (BC no.).
5. Customer name shown as a read-only field.
6. EDN picker: modal, multi-checkbox, local search. "Add an EDN" stays.
7. BIM detail in a drawer from the contracts card, loaded only when opened.
8. Print the SI as a PDF shaped like SI-579.

## Non-goals
- No new cost entry on the SI form. Prices are edited on the Cost lines page.
- No change to cost approval, provisions or the cost-type master.
- Additional cost: already supported (SI cost lines, `shc_is_additional`,
  per-line approval). Nothing to build.

## Approaches (picked: A)
- **A. Booking amount read from cost lines** — single record of money, no drift.
  Picked.
- B. Typed on the SI, save writes the cost line — has to guess tax, accounts and
  approval state. Dropped.
- C. Stored on the SI, checked against cost lines — two copies. Dropped.

Price editing: on the existing Cost lines page (gate `finance-shipment-si-edit`).
The SI form shows the amount read-only, with a link to Cost lines.

## Design

### Data — one new migration on `ship_export_si`
`ses_forwarder_vendor_code` (30), `ses_emkl_vendor_code` (30),
`ses_emkl_name` (200), `ses_bc_no` (50), `ses_goods_description` (500),
`ses_hs_code` (20). All nullable. Add to model `$fillable`,
`ShipExportSiData`, `toFormState`, save. `ses_nominated_forwarder` stays the
forwarder name.

### Booking amount (read-only)
`ShipExportSiService::bookingAmounts(ShipExportSi)` returns
`['SHIP' => [currency => amount], 'EMKL' => [...]]`: the sum of
`sec_base_amount` over cost lines that are not VOID and not additional,
grouped by currency. If there are several currencies, show each, e.g.
`USD 550 + IDR 1,250,000`. A new SI shows "— (no cost line yet)".

### Supplier picker
Copy `_import-supplier-picker` into one modal with a `$supplierTarget`
(`forwarder` | `emkl`). Choosing fills the code + name. Typing in the name field
clears the hidden code, so a hand-typed name never keeps a stale code.

### Customer
Read-only input with the customer name next to the code (the code is already
fixed by the BIM).

### EDN picker
On open, load once: `ShipSalesDocRepository::deliveryNotes(['customer_code' =>
…, 'limit' => 200])`. Search is Alpine, filtering that list on the client.
Checkboxes; "Add selected" appends rows (`edn_no`, `edn_date`, `value`,
`currency`). EDNs already on this SI are checked and disabled. EDNs on another
SI are shown disabled with that SI number (the DB has `uq_ship_si_edn_no`).

### BIM drawer
Eye button per contract row → `x-ui::drawer`. A `$drawerBimId` is set on click,
and the drawer body renders only while it is set (one query on open). Shows
terms, consignee, notify parties, freight, planned containers, special
instruction. Read-only.

### Print
Route `export-si/{transNo}/print` (`can:finance-shipment-si-view`) →
`ShipPrintService::exportSi()` → `finance::pdf.shipment.export-si`, A4
portrait, streamed inline. Print button on the SI input (once saved) and on the
detail page.

Field mapping from SI-579:
| Sheet | Source |
|---|---|
| TO | forwarder name |
| SI No / Date | `ses_trans_no` / `ses_trans_date` |
| ESC No | linked BIMs' ESC nos, comma-joined |
| Shipper | fixed company address (constant in the view) |
| POL / POD | port names |
| Stuffing date | `ses_stuffing_date` |
| Shipment type | containers, e.g. `1X40 HI CUBE FCL` |
| Freight | PREPAID if the incoterm adds freight (CFR/CIF/CPT/CIP/DAP/DDP), else COLLECT |
| Ocean Freight | forwarder booking amount |
| BC no. | `ses_bc_no` (new row) |
| 1 Free time | `ses_free_time_days` + basis |
| 2 PEB type | `ses_peb_type` |
| 3 Shipping line restriction | BIM via port / transhipment max |
| 4 HBL/MBL | `ses_bl_type_request` |
| 5 Consignee | name + address |
| 6 Notify party | BIM notify parties, else "SAME AS CONSIGNEE" |
| 7 Others | `ses_special_instruction` |
| 8 Vessel schedule | vessel / voyage / ETD |
| 9 Fumigation | yes / no |
| 10 Goods description | `ses_goods_description` |
| 11 HS code | `ses_hs_code` |
| 12 / 13 Gross / Net | `ses_gross_weight_kg` / `ses_net_weight_kg` |
| Footer instructions | fixed text from SI-579 (despatch, forwarder, EMKL) |
| EMKL | EMKL name |

### Test
One Pest file: booking amount ignores void/additional lines and groups by
currency; the new fields save and reload; print returns 200 `application/pdf`.

## Calls I made (check these)
- Booking amount = `sec_base_amount` (before tax), the way SI-579 shows freight.
- Goods description and HS code are typed on the SI. Nothing in the ERP feeds
  them today.
- The drawer is gated on `si-view`, not `bim-view`, because it sits inside the SI.
- The footer instructions are fixed text in the PDF view, not a master table.
- The SI number prints as the system number, not the legacy `579/SI/MGT-10/2026`.
- Sidebar: no new page (print is an action), so no menu row.
