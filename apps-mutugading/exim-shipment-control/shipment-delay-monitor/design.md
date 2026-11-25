# Export Shipment Delay Monitor — Design

Date: 2026-11-24 · Status: approved · Module: Finance (Shipment)

## Why

The exim team tracks every export shipment in an Excel workbook
(`.docs-me/Shipment_Delay_Monitor_Auto_Link_Excel_2019.xlsx`): one typed sheet
(`APR-OKT`, ~330 rows) plus a formula sheet (`DELAY_MONITOR`) that works out delay
days and a status. The data is retyped from the ERP, dates are often free text, only
one ETA update is kept, and there is no column for *why* a shipment is late. Moving it
into the Export SI puts the tracking next to the data it already copies.

## Goals

- Record per Export SI: latest ETA, actual arrival (ATA), the transhipment leg
  (port, ETA, ETD, ATD) and a free-text delay reason.
- Show where each shipment is now and whether it is late, computed, never typed.
- One monitor page with filters and a queued Excel export; sidebar entry.

## Non-goals

- Live vessel/carrier tracking (paid API; the sheet never did it either).
- Importing the old workbook — its dates (`CN- 27 Apr`, no year) don't parse reliably.
- A reason list / enum — reason is free text by decision.
- Multiple transhipment legs — one leg covers the workbook's data.

## Approaches considered

1. **Columns on `ship_export_si` + computed status (picked).** Smallest change, the SI
   is already the shipment record; history comes from the activity log.
2. Separate tracking table (one row per ETA update). Full history as data, but the
   activity log already gives that; extra table, repository and joins for nothing yet.
3. Stored status column updated by a job. Can go stale; a nightly job for a value that
   is a two-line date compare.

## Decisions (user)

- Delay reason: free text.
- Late = 1 day or more past the original ETA (no tolerance).
- Transhipment leg is included.
- Excel export runs as a background job.

## Design

### 1. Data — one new migration on `ship_export_si` (oracle_mgthris, SQLite-safe)

| Column | Type | Meaning |
|---|---|---|
| `ses_eta_latest` | date, null | latest ETA received from the line/forwarder |
| `ses_ata` | date, null | actual arrival at destination |
| `ses_ts_port_code` | string(10), null | transhipment port (same code set as `ses_pol_code`/`ses_pod_code`) |
| `ses_ts_eta` | date, null | ETA at transhipment port |
| `ses_ts_etd` | date, null | ETD from transhipment port |
| `ses_ts_atd` | date, null | actual departure from transhipment port |
| `ses_delay_reason` | string(500), null | free text |

Existing `ses_etd`, `ses_eta` (= original ETA), `ses_atd` are reused unchanged.
Add all seven to `$fillable`, date casts, and `$logAttributes` so every change
(i.e. ETA history) lands in the activity log. Add a `transhipmentPort()` BelongsTo
like `loadingPort()`.

### 2. Computed values — on the model (or a small readonly class if it grows)

- **Effective ETA** = `ses_eta_latest ?? ses_eta`.
- **Position**: `ses_ata` → *Arrived*; else `ses_ts_atd` → *To destination*;
  else `ses_atd` and a ts port → *To transhipment port*; else `ses_atd` → *To destination*;
  else *Not sailed*.
- **Delay days** (null when `ses_eta` is null):
  `max(0, (ses_ata ?? max(effective ETA, today if past it)) − ses_eta)`.
- **Status** (enum `ShipDelayStatusEnum`, with `label()`/`badgeVariant()`/`options()`):
  - `NO_ETA` — `ses_eta` empty
  - `ARRIVED_LATE` / `ARRIVED_ON_TIME` — `ses_ata` set, delay ≥ 1 / = 0
  - `DELAYED` — not arrived, delay ≥ 1 (includes "today is past the ETA, nobody updated it")
  - `ON_SCHEDULE` — not arrived, delay 0 (an earlier ETA counts here)
- **Transit days**: planned = `ses_eta − ses_etd`, actual = `ses_ata − ses_atd`.

Status is not stored, so filtering by status happens in the query with the same
rules expressed as date conditions (repository method), and one shared test checks the
PHP rule and the query agree.

### 3. Tracking box on the SI detail page (`ShipExportSiDetail`)

The SI edit form locks at `CLOSED`, which often happens while the vessel is still at
sea, so tracking gets its own small form on the detail page:
- the seven fields; editable in every status except `CANCELLED`;
- gated by `finance-shipment-si-edit`; validated (dates, ATA ≥ ATD, port exists, reason ≤ 500);
- transhipment port prefilled from the linked BIM's `sbm_via_port_code` when empty;
- saved through the SI service/repository with the audit fields, toast on success.

### 4. Monitor page — `ShipExportMonitor`

- Route `finance…/shipment/export-monitor`, name alongside `export-si.*`,
  `can:finance-shipment-si-view`. Breadcrumb "Export Shipment Monitor".
- Columns: SI no. (link to detail), customer, shipping line, container no(s),
  route POL → TS → POD, ETD, original ETA, latest ETA, ATA, delay days, status badge,
  position, delay reason.
- Filters: status, ETD month, customer, destination port, search. Default: not yet
  arrived; `CANCELLED` SIs always excluded. Paginated, eager-loaded containers/ports.
- Org scope: same visibility the SI list applies.
- **Export**: button dispatches a job extending `Jobs/Reports/Shipment/ShipReportExport`
  with a new export class under `Exports/Reports/Shipment/`, same filters as the
  screen; delivered via `ReportStatusNotification`. No NIK column, so no NIK handling.

### 5. Sidebar

Idempotent menu migration (pattern: Core `2026_09_16_090000_sync_sidebar_menus_with_permissions.php`):
item under the Shipment group next to Export SI, code e.g. `finance.shipment.export-monitor`,
gated on `finance-shipment-si-view` (logic `or`), clear menu cache at the end.

## Tests (Pest, SQLite)

- Status/delay/position rules table-tested (incl. 1-day late, past-ETA-no-update, no ETA, earlier ETA).
- Query status filter returns the same set as the PHP rule.
- Tracking save: permission, CANCELLED blocked, CLOSED allowed, validation.
- Monitor page renders + filters; export dispatches the job.

## Left to the builder

Exact column order and widths, the ETD-month filter widget, file names, whether the
status rules live on the model or a tiny helper class.
