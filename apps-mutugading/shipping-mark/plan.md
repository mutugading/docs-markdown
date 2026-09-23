# plan.md — ShipMark Module (Phase 1)

> Implementation plan for the **Shipping Mark** engine, derived from `PRD_shipmark.md`,
> `design.md`, `spec.md`, `ship_mark_schema.sql`, `shipping_mark_template_catalog.md`, and `tasks.md`.
> Aligned to Apps Mutu Gading conventions (see `CLAUDE.md`): modular monolith,
> Repository → Service → Livewire, Oracle via `yajra/laravel-oci8`, Spatie Permission, Pest 4.

---

## 0. Goal & scope

Build the **ShipMark** feature inside `Modules/MaterialControl` — a **data-driven, customer-configurable** shipping-mark generator. All ShipMark files live under a `ShipMark/` subfolder within each `app/` layer (see §3).
A template (layout + grain + size + party role) is authored once per customer, then bound at
generate-time to real scan/pack data from Orion (MGTDAT, read-only) and rendered to PDF.

**Phase 1 deliverables (T001–T015):** template CRUD + clone, 5-grain resolver, dynamic entry
screen, learn-as-you-go customer master mapping, PDF render (A4/A5/A6/custom mm + P/L orientation), print log + reprint,
Spatie permission gating.

**Out of scope (Phase 2):** SO integration (PO/LC/invoice/color), QR verify, logo slots, supplementary label types.

---

## 1. Architectural decisions & deviations to confirm

These reconcile the source docs with **this repo's** conventions. Confirm before coding.

| # | Topic | Decision |
|---|---|---|
| D1 | **Config connection** | Docs say `protected $connection = 'oracle'` and name MGTAPPS, but the **default/config schema for this build is MGTHRIS** (per confirmation) → use **`oracle_mgthris`** on all 4 ShipMark models (`app/Models/ShipMark/`). The `SHIP_MARK_*` config tables are created in MGTHRIS. Update the DDL file's hardcoded `MGTAPPS.` prefixes to MGTHRIS (or drop the qualifier and let the connection's schema prefix apply). |
| D2 | **Orion read connection** | ✅ Resolved — `oracle_mgtdat` **already exists** in `config/database.php` (`DB_MGTDAT_*` env). Orion-read repositories use `oracle_mgtdat`. Just confirm the env vars are populated in prod/staging; no config change needed. |
| D3 | **PK generation** | Schema uses Oracle **SEQUENCE + BEFORE INSERT trigger** (11g, no IDENTITY). This differs from the repo's usual `SysIdHelper` string PKs. Models: `$incrementing = false`, `$keyType = 'int'` (NUMBER PK), `$timestamps = false`. PK is trigger-populated — do **not** set it in `creating()`. Audit columns are `SMx_CR_UID/CR_DT/UPD_UID/UPD_DT` (not the standard `*_created_by` names) — keep the schema's names, set them in the **Service**. |
| D4 | **Migration strategy** | The scaffold convention prefers Blueprint (SQLite-compatible). But sequences + triggers are Oracle-only. Use a **hybrid**: (a) a Blueprint migration that builds the 4 tables (SQLite-safe, so CI/Pest pass), guarded by `migrationDisabled()`; (b) a **separate Oracle-only** raw-SQL migration for SEQUENCEs + TRIGGERs that no-ops when the connection driver is not `oci8`. See §4 T002. This keeps `php artisan test` (SQLite in-memory) green while production Oracle gets triggers. |
| D5 | **JSON column** | `SMPL_MANUAL_VALUES` is CLOB. ⚠️ **No `ClobJson` cast exists in the repo** — design.md assumed one. **Create `Modules/MaterialControl/app/Casts/ShipMark/ClobJson.php`** (`CastsAttributes`: decode CLOB→array on get, `json_encode`→string on set). Works on both Oracle CLOB and SQLite text, so tests pass. |
| D6 | **PDF engine** | Repo already ships `barryvdh/laravel-dompdf`. Docs suggest `mpdf` for arbitrary mm sizes. **Prefer dompdf** (already installed) with custom paper `[0,0,w_pt,h_pt]`; only add `mpdf/mpdf` if dompdf can't honor custom mm cleanly. Decide in T013. |
| D7 | **Module name** | **`MaterialControl`** (alias `materialcontrol`). Standalone module; declares no `requires` (writes config to MGTHRIS + reads Orion/MGTDAT directly via repositories). ShipMark is the first feature inside it — namespaced via `ShipMark/` subfolders + `ShipMark` filename prefix so future features (other material-control tools) can coexist. |
| D8 | **Not a Master/Transaction CRUD** | This is a bespoke engine, so `mutugading-crud`/`mutugading-transaction` generators do not fit wholesale. We reuse the **layer conventions** (Repository → Service → Livewire) via `mutugading-scaffold` patterns, but hand-build the grain/generate/render pieces. |

---

## 2. Data sources (read-only, MGTDAT)

Tables: `OT_SO_HEAD_MGT`, `OT_SO_ITEM_MGT`, `OT_SO_SCAN_MGT`, `OT_WMS_PACK_TABLE_ALTHARA`,
`OT_SO_HEAD`, `OM_CUSTOMER`, `OM_GRADE_CODE_2`.

- **Anchor:** SSC = `sohm_txn_code`-`sohm_no`; customer via `sohm_cust_code`.
- **Join scan↔pack:** `ss.SS_SCAN_CODE = prd.PRD_SCAN_CODE` (LEFT JOIN; ALTHARA = per-box enrichment).
- **Base per-box query:** `spec.md §2.1`.
- **ESC/CONTRACT_NO:** `sohm_ref_sys_id = soh_sys_id` → `NVL(soh_ref_txn_code,soh_txn_code)||'-'||NVL(soh_ref_no,soh_no)` (`spec.md §2.3`).
- **Bobbin/weight:** `PRD_SUB_UNITS` = bobbins/box, `PRD_QTY` = box weight (=`PRD_NET_WT`), weight/bobbin = `PRD_QTY/NULLIF(PRD_SUB_UNITS,0)`.

**Open confirmations** (from `catalog §6`) — do not block Phase 1, treat unresolved fields as MANUAL:
`CUST_PO_NO`, `LC_NO`, `INVOICE_NO` vs `SOHM_INVH_NO`, `CONTRACT_NO`=ESC?, COLOR rule per customer, `TUBE_COLOR` source.

---

## 3. Target file layout

**Naming rule applied everywhere under `app/` and `resources/`:** each file lives in a `ShipMark/`
subfolder within its layer (no filename prefix), so the feature is grouped under one folder in every
layer. **Exceptions:** `database/` and `routes/` keep their standard names; the module's `Providers/`
also keep standard nwidart names (module-level infra, not ShipMark-specific).

```
Modules/MaterialControl/
├── module.json                         # name MaterialControl, alias materialcontrol, providers
├── composer.json
├── app/
│   ├── Casts/ShipMark/
│   │   └── ClobJson.php                 # CLOB↔array cast (D5)
│   ├── Models/ShipMark/                 # oracle_mgthris connection
│   │   ├── ShipMarkTemplate.php         # PK SMT_TEMPLATE_ID, hasMany lines
│   │   ├── ShipMarkTemplateLine.php     # belongsTo template, order SMTL_SORT_ORDER
│   │   ├── ShipMarkCustMap.php          # unique(cust, attr_type, mgt_key)
│   │   └── ShipMarkPrintLog.php         # SMPL_MANUAL_VALUES → ClobJson cast
│   ├── Enums/ShipMark/
│   │   ├── GrainEnum.php                # UNIFORM|ITEM|ITEM_LOT|PALLET|CARTON
│   │   ├── ValueTypeEnum.php            # STATIC|SYSTEM|MASTER|MANUAL|COMPUTED
│   │   ├── PartyRoleEnum.php            # BUYER|CONSIGNEE|NOTIFY|SHIPPER|RECEIVER
│   │   ├── LineTypeEnum.php             # FIELD|TITLE|FOOTER|STATIC|SPACER
│   │   ├── PaperSizeEnum.php            # A4|A5|A6|CUSTOM
│   │   └── OrientationEnum.php          # P (Portrait) | L (Landscape) — maps SMT_ORIENTATION
│   ├── Interfaces/ShipMark/             # repository contracts
│   │   ├── TemplateRepositoryInterface.php
│   │   ├── CustMapRepositoryInterface.php
│   │   ├── SscScanRepositoryInterface.php
│   │   └── PrintLogRepositoryInterface.php
│   ├── Repositories/ShipMark/
│   │   ├── EloquentTemplateRepository.php
│   │   ├── EloquentCustMapRepository.php
│   │   ├── EloquentSscScanRepository.php   # reads oracle_mgtdat
│   │   └── EloquentPrintLogRepository.php
│   ├── Services/ShipMark/
│   │   ├── EscResolver.php
│   │   ├── GrainResolver.php
│   │   ├── MasterResolver.php
│   │   ├── TemplateService.php
│   │   └── GenerateService.php
│   ├── Data/ShipMark/                   # DTOs (Spatie Data + Wireable)
│   │   ├── TemplateData.php
│   │   ├── TemplateLineData.php
│   │   └── GenerateRequestData.php
│   ├── Support/ShipMark/
│   │   ├── SscLabelRow.php              # readonly DTO — one resolved label unit
│   │   ├── GenerateResult.php           # { labels[], pdfPath }
│   │   └── FieldDictionary.php          # canonical VALUE_REF vocabulary + type map
│   ├── Exceptions/ShipMark/
│   │   └── ShipMarkException.php
│   ├── Livewire/ShipMark/
│   │   ├── TemplateManager.php          # list + header form + line editor (reorder)
│   │   ├── GenerateMark.php             # SSC pick → template → grain/copies → entry → preview → print
│   │   └── PrintLogList.php             # list + reprint from snapshot
│   └── Providers/                       # standard nwidart names (NOT subfoldered)
│       ├── MaterialControlServiceProvider.php  # routes, views, migrations, permissions
│       ├── RepositoryServiceProvider.php       # bind interface→impl
│       └── RouteServiceProvider.php
├── config/config.php                    # paper_sizes, pdf.chunk_size, pdf.engine
├── database/                            # UNCHANGED — no ShipMark subfolder
│   ├── sql/ship_mark_schema.sql         # copied raw DDL (Oracle sequences+triggers+seed)
│   └── migrations/
│       ├── 2026_07_13_000001_create_ship_mark_tables.php        # Blueprint (SQLite-safe)
│       └── 2026_07_13_000002_create_ship_mark_oracle_objects.php# raw seq+trigger (oci8-only)
├── resources/views/
│   ├── livewire/ship-mark/             # kebab-case: {template-manager,generate-mark,print-log-list}.blade.php
│   └── pdf/ship-mark/label.blade.php   # renders one label from lines
├── routes/                             # UNCHANGED
│   ├── web.php
│   └── breadcrumbs.php
└── tests/ShipMark/                     # Pest: Grain, Master, Generate, Livewire
```

**Namespaces** follow the folders, e.g. `Modules\MaterialControl\Services\ShipMark\GrainResolver`,
`Modules\MaterialControl\Models\ShipMark\ShipMarkTemplate` (model names keep their own `ShipMark…`
identity from the source docs; other layers do not). View namespace is `materialcontrol::livewire.ship-mark.template-manager`.

---

## 4. Task breakdown (maps tasks.md T001–T015)

Each task ends with: `vendor/bin/pint --dirty`, `php artisan test` green, commit `feat(shipmark): [Txxx] title`.

### GROUP 1 — Foundation

#### T001 — Scaffold module
- `php artisan module:make MaterialControl`.
- Set `module.json` (alias `materialcontrol`, providers list).
- Create `MaterialControlServiceProvider` (routes, views namespace `materialcontrol::`, config merge, migrations path, permission registration), `RepositoryServiceProvider`, `RouteServiceProvider`. Register all in `module.json` providers.
- Create the `app/{Layer}/ShipMark/` subfolders per §3.
- **Accept:** `module:list` shows MaterialControl enabled; `module:route-list MaterialControl` runs; `pint --test` clean.

#### T002 — Deploy schema (D4 hybrid)
- Copy `ship_mark_schema.sql` → `Modules/MaterialControl/database/sql/` (database/ keeps standard names — no prefix).
- **Migration A** `create_ship_mark_tables` — Blueprint on `oracle_mgthris`, 4 tables with exact `SMT_/SMTL_/SMCM_/SMPL_` columns, `migrationKey` guard + `hasTable` check, `down()` drops. SQLite-compatible types (NUMBER→integer, VARCHAR2→string, CLOB→text, DATE→dateTime). No `->timestamps()`.
- **Migration B** `create_ship_mark_oracle_objects` — runs the sequence+trigger DDL from the SQL file via `unprepared`, **only when** `DB::connection('oracle_mgthris')->getDriverName() === 'oci8'` (no-op on SQLite so CI passes). Seed `TPL_BEKAERT_AU` + 10 lines here (or a separate SQLite-safe seeder for tests).
- **Accept:** `migrate` succeeds on Oracle; on SQLite the 4 tables exist; seed row present; 4 seq + 4 triggers exist on Oracle.

#### T003 — Eloquent models
- 4 models under `Models/ShipMark/`, `$connection='oracle_mgthris'`, `$incrementing=false`, `$keyType='int'`, `$timestamps=false`, explicit `$primaryKey`, `$fillable`.
- `ShipMarkTemplate::lines()` hasMany ordered by `SMTL_SORT_ORDER`; `ShipMarkTemplateLine::template()` belongsTo.
- Create `app/Casts/ShipMark/ClobJson.php` (D5); `ShipMarkPrintLog` casts `SMPL_MANUAL_VALUES` → `ClobJson`.
- Use `Searchable` + `LogsActivityWithDescription` traits where useful (templates).
- **Accept:** tinker `ShipMarkTemplate::with('lines')->first()` returns seed + 10 lines; PrintLog JSON round-trips.

### GROUP 2 — Orion read + grain

#### T004 — SscScanRepository
- Reads `oracle_mgtdat` (D2). Implement `header()`, `baseRows()` (spec §2.1 join), `distinctItems()`.
- Map rows into `SscLabelRow` support DTO.
- **Accept:** `baseRows()` returns >0 rows with `tube_color`/`bobbin_qty` filled; Pest asserts DTO shape (fixture-backed for SQLite).

#### T005 — EscResolver
- `forSsc($txn,$no)` → contract string via `sohm_ref_sys_id=soh_sys_id` (spec §2.3); null-safe.
- **Accept:** SSC with ref → `TXN-NO`; without ref → null (no error).

#### T006 — GrainResolver
- `resolve(Collection $baseRows, GrainEnum $grain): Collection<SscLabelRow>`. 5 grains, SUM/COUNT aggregation, lot MULTI-aware (`COUNT(DISTINCT lot)>1 → 'MULTI'`), `unit_seq` = `n/total`, weight/bobbin computed. **Do the grouping in PHP over `baseRows`** (not SQL) so it's testable on SQLite fixtures.
- **Accept:** fixture (2 items, item A 2 lots 9 pallets) → ITEM=2, ITEM_LOT=3, PALLET=9; CARTON=row count; UNIFORM=1; MULTI when >1 lot.

### GROUP 3 — Template management

#### T007 — Repository interfaces + impl + binding
- 4 interfaces + Eloquent impls (`TemplateRepositoryInterface.findForCustomer/findWithLines/save/saveLines/clone`, `CustMapRepositoryInterface.resolve/upsert`, `PrintLogRepositoryInterface.log/find`, plus SscScan from T004).
- Bind interface→impl in `RepositoryServiceProvider` (mandatory).
- **Accept:** `app(TemplateRepositoryInterface::class)` resolves; `findForCustomer('BEKAUS')` returns seed.

#### T008 — TemplateService + DTOs
- `TemplateData`/`TemplateLineData` (Spatie Data + `Wireable`/`WireableData`, `default()`, `attributes()`).
- create/update/clone; `saveLines` reorder; validation from `spec §4` (unique code, grain enum, paper size in `PaperSizeEnum` A4/A5/A6/CUSTOM, orientation in `OrientationEnum` P/L, custom size w×h>0, STATIC needs static_value, value_ref must exist in `FieldDictionary`).
- **A6 & orientation need no DDL change:** `SMT_PAPER_SIZE` has no CHECK constraint (A6 is a code-side enum/config value), and `SMT_ORIENTATION` already exists in the schema. Add A6 dimensions to `config.paper_sizes` and map `SMT_ORIENTATION` via `OrientationEnum` (model cast in T003, applied in the PDF page setup in T013).
- Sets audit UID/DT fields; error wrapping via `ShipMarkException`.
- **Accept:** Pest — clone yields new code + identical lines; validation rejects STATIC w/o value and unknown value_ref.

#### T009 — Livewire Template Manager
- List (filter by customer) + New/Clone; header form (customer select from `OM_CUSTOMER`, ship-to, party role, grain, paper size + custom w/h, copies, is_default, is_supp); line editor with drag-reorder, per-line type/label/value_type/value_ref dropdown (from dictionary)/static_value. Flux UI + `<x-ui::*>` components first.
- **Accept:** create template + lines via UI persists; reorder updates `SMTL_SORT_ORDER`; Livewire test valid + invalid submit.

### GROUP 4 — Generate + entry + PDF

#### T010 — MasterResolver + learn-as-you-go
- `value($cust,$valueRef,$mgtKey)`: map value_ref→attr_type, return CustMap value or null.
- `upsert` via MERGE (spec §2.5), idempotent; save only when previously null.
- **Accept:** miss→null then upsert→value; MERGE run twice → no duplicate (unique holds).

#### T011 — GenerateService
- `build($txn,$no,$templateId,$grain,$copies,$manualValues): GenerateResult`: header+template+lines → baseRows → GrainResolver → per line resolve (STATIC/SYSTEM/COMPUTED/MASTER/MANUAL, CONTRACT_NO via EscResolver) → explode N labels = units(grain) × copies, grouped per item → `PrintLogRepository::log()` with manual snapshot JSON.
- Save new MASTER entries entered at generate-time (learn-as-you-go).
- **Accept:** Pest — label count = units×copies; PrintLog JSON correct; CONTRACT_NO filled when template uses it.

#### T012 — Entry Screen (Livewire GenerateMark)
- SSC picker → customer + auto-suggest template (preselect if 1) → grain (default from template, overridable) + copies.
- Dynamic entry grid: columns = MANUAL + unmapped MASTER lines; rows = `distinctItems`; per-unit fields auto (not input); fill-down; mapped MASTER shows value + "auto" badge; new MASTER value saved via upsert.
- Preview → Generate & Print → PDF (Storage/stream).
- **Accept:** all-SYSTEM/STATIC template → empty grid, direct generate; new MASTER persists; fill-down copies to all items.

#### T013 — PDF Render
- `resources/views/pdf/ship-mark/label.blade.php`: loop lines → `LABEL : VALUE`, honor `SMTL_COL_POS` (2-col GW/NW), `SMTL_INDENT`, `SMTL_IS_BOLD`, `SMTL_FONT_SIZE`. Page size from `SMT_PAPER_SIZE` + orientation. Engine per D6 (dompdf first).
- Large SSC → render in `config('shipmark.pdf.chunk_size')` batches, merge, stream (avoid OOM).
- **Accept:** PDF for grain PALLET (20+ labels); A4/A5/A6 sizes + P/L orientation honored; 2-col line renders; ≥300 labels no OOM.

### GROUP 5 — Permissions + polish

#### T014 — Spatie permissions + gating
- Register `shipmark.template.manage`, `shipmark.generate`; gate routes + menu links.
- **Accept:** no-permission user → 403; menu hidden without permission.

#### T015 — Print Log + Reprint
- `PrintLogList`: list (filter per SSC) + reprint (regenerate from `SMPL_MANUAL_VALUES` snapshot → identical output).
- **Accept:** reprint reproduces PDF from snapshot; list filters per SSC.

---

## 5. Routes, breadcrumbs, navigation

- `routes/web.php`: `shipmark.templates.index`, `shipmark.generate`, `shipmark.logs.index`, guarded by `permission:` middleware.
- `routes/breadcrumbs.php`: entries for each page.
- Add nav links (permission-gated) to the app sidebar/dashboard.

---

## 6. Testing (Pest 4, SQLite in-memory)

- **Unit:** `GrainResolver` (all 5 grains, MULTI, n/total), `MasterResolver` (resolve/upsert idempotency), `EscResolver`, `FieldDictionary` integrity, DTO validation.
- **Feature/Livewire:** TemplateManager (CRUD, reorder, clone, validation), GenerateMark (dynamic grid, fill-down, learn-as-you-go), PrintLogList (reprint identity), permission gating (403).
- Orion reads are fixture-backed for SQLite (D4 keeps tables SQLite-safe); Oracle-only objects skipped in CI.
- **Definition of done:** all tasks DONE + `php artisan test --parallel` green + `pint --test` clean.

---

## 7. Dependencies & config

- **composer:** confirm `barryvdh/laravel-dompdf` (present). Add `mpdf/mpdf ^8.2` **only if** T013 needs it. Reuse `yajra/laravel-oci8`, `livewire/*`, `flux` Pro, `spatie/laravel-permission`, `maatwebsite/excel`, `nwidart/laravel-modules`.
- **config/database.php + config/oracle.php:** add read-only `oracle_mgtdat` connection (D2).
- **`.env` reference (config-only):** `DB_MGTDAT_*` (host/port/service/username/password/schema_prefix).
- **Modules/MaterialControl/config/config.php:** `paper_sizes` (A4/A5/A6/CUSTOM mm), `orientations` (P/L), `pdf.engine`, `pdf.chunk_size` (default 200).

---

## 8. Acceptance (PRD §13)

- 14 catalog templates representable via config only (no code).
- Real SSC generate correct per grain/size: Hamilton (40 pallet, single lot), Bekaert AUS (20 pallet, 2 lot), `194 AM` (704 carton, batched).
- New customer/format via UI < 15 min, no developer.
- Repeat-customer manual entry → near zero (learn-as-you-go).

---

## 9. Suggested branch & commit flow

- Branch: `feat/shipmark-phase-1` from `develop`.
- One commit per task: `feat(shipmark): [Txxx] title`.
- Screenshots for the 3 Livewire pages in the PR.
- PR `feat/shipmark-phase-1` → `develop`; CI (tests + lint) must pass.

---

## 10. Immediate next actions

1. **Confirm D1–D8** (esp. D2 MGTDAT env vars populated in prod/staging, D4 hybrid migration, D6 PDF engine). D2 & D5 already resolved above.
2. Resolve `catalog §6` field-source questions with sales/export (non-blocking; MANUAL fallback).
3. Start T001 (module scaffold) once confirmations land.
