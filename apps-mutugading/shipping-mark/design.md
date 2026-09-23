# design.md — ShipMark Module (Phase 1)

Stack: Laravel 12, Livewire 3 + Volt + Flux UI Pro 2, Oracle 11g (yajra/laravel-oci8),
nwidart/laravel-modules, Spatie Permission v7, Pest 4.
Pattern: **Repository Interface → Eloquent Impl → Service → Livewire Component**.
 
---

## 1. Domain Entities (Eloquent models)

Semua model `protected $connection = 'oracle';`, PK non-increment (di-set trigger),
tanpa Laravel timestamps (pakai kolom audit sendiri).

| Model | Tabel | Catatan |
|---|---|---|
| `ShipMarkTemplate` | `MGTAPPS.SHIP_MARK_TEMPLATE` | `$primaryKey='SMT_TEMPLATE_ID'`, hasMany lines |
| `ShipMarkTemplateLine` | `MGTAPPS.SHIP_MARK_TEMPLATE_LINE` | belongsTo template, order by `SMTL_SORT_ORDER` |
| `ShipMarkCustMap` | `MGTAPPS.SHIP_MARK_CUST_MAP` | unique (cust, attr_type, mgt_key) |
| `ShipMarkPrintLog` | `MGTAPPS.SHIP_MARK_PRINT_LOG` | `SMPL_MANUAL_VALUES` → cast `ClobJson` |

Enum value:
- `SMT_GRAIN` ∈ `UNIFORM | ITEM | ITEM_LOT | PALLET | CARTON`
- `SMTL_VALUE_TYPE` ∈ `STATIC | SYSTEM | MASTER | MANUAL | COMPUTED`
- `SMT_PARTY_ROLE` ∈ `BUYER | CONSIGNEE | NOTIFY | SHIPPER | RECEIVER`
  Read-model (bukan tabel) untuk data Orion:
- `SscLabelRow` — satu unit label hasil GrainResolver: `{item, lot_no, net_wt, gross_wt, tare_wt, color_mgt, tube_color, bobbin_qty, box_wt, pallet_no, carton_no, unit_count, unit_seq}`.
---

## 2. Repository Interfaces

```
App\...\ShipMark\Repositories\Contracts\
  TemplateRepositoryInterface
    findForCustomer(string $custCode): Collection      // template aktif utk customer
    findWithLines(int $templateId): ShipMarkTemplate
    save(array $attrs): ShipMarkTemplate
    saveLines(int $templateId, array $lines): void
    clone(int $templateId, string $newCode): ShipMarkTemplate
 
  CustMapRepositoryInterface
    resolve(string $cust, string $attrType, string $mgtKey): ?string
    upsert(string $cust, string $attrType, string $mgtKey, string $value): void
 
  SscScanRepositoryInterface
    header(string $txnCode, int $no): ?object           // sohm + cust
    baseRows(string $txnCode, int $no): Collection       // per-box join scan+ALTHARA
    distinctItems(string $txnCode, int $no): Collection  // item + jumlah unit
 
  PrintLogRepositoryInterface
    log(array $attrs): ShipMarkPrintLog
    find(int $logId): ?ShipMarkPrintLog
```

Implementasi Eloquent di `.../ShipMark/Repositories/Eloquent/`.
 
---

## 3. Services (Application layer)

```
EscResolver::forSsc(string $txnCode, int $no): ?string
    → subquery SOHM_REF_SYS_ID = SOH_SYS_ID (spec.md §2.3)
 
GrainResolver::resolve(Collection $baseRows, string $grain): Collection<SscLabelRow>
    UNIFORM   → 1 row: SUM(net), SUM(gross), SUM(bobbin), COUNT unit
    ITEM      → group item
    ITEM_LOT  → group item+lot
    PALLET    → group pallet_no
    CARTON    → row-level (per box)
    lot: COUNT(DISTINCT lot)>1 ? 'MULTI' : lot
    berat/bobbin (bila diminta) = box_wt / NULLIF(bobbin_qty,0)
 
MasterResolver::value(string $cust, string $valueRef, string $mgtKey): ?string
    map valueRef→attr_type (CUST_MATERIAL_NO→MATERIAL_NO, COLOR_CUST→COLOR_NAME, dst)
    return CustMapRepository::resolve(...); null = belum ke-mapping (jadi input di entry)
 
TemplateService  → create/update/clone template, reorder & validate lines
GenerateService::build(string $txnCode, int $no, int $templateId, string $grain,
                       int $copies, array $manualValues): GenerateResult
    langkah:
      1. header + template(+lines)
      2. baseRows → GrainResolver(grain)
      3. per item: resolve tiap line
         STATIC   → SMTL_STATIC_VALUE
         SYSTEM   → field dari SscLabelRow (+ EscResolver utk CONTRACT_NO)
         COMPUTED → TARE / berat-per-bobbin
         MASTER   → MasterResolver (+ simpan bila user isi baru → learn-as-you-go)
         MANUAL   → dari $manualValues[item][valueRef]
      4. explode: N label = (jumlah unit di grain) × copies, dikelompokkan per item
      5. PrintLogRepository::log(manual snapshot JSON)
    return GenerateResult { labels[], pdfPath }
```

Error wrapping konsisten: `throw new ShipMarkException("GenerateService::build: ...")`.
 
---

## 4. Delivery (Livewire Volt + Flux)

**a. Template Manager** (`shipmark::templates`)
- List template (filter by customer), tombol New / Clone.
- Form header: customer (select OM_CUSTOMER), ship-to, party role, grain, paper size (+ w/h utk CUSTOM), copies, is_default, is_supp.
- Line editor: tabel baris drag-reorder; per baris pilih line_type, label, value_type, value_ref (dropdown key dari kamus), static_value.
  **b. Generate / Entry** (`shipmark::generate`)
- Pilih SSC (txn+no) → tampil customer + template auto-suggest (preselect bila 1).
- Pilih grain (default dari template, bisa override) + copies.
- **Entry grid** (auto dari metadata template):
    - kolom = baris `MANUAL` + `MASTER` yang belum ke-mapping
    - baris = item hasil `distinctItems`
    - MASTER ke-mapping → tampil nilai + badge "auto"; MANUAL → input
    - fill-down utk field yang sama semua item (mis. PO)
    - simpan MASTER baru → CustMapRepository::upsert (learn-as-you-go)
- Preview + Generate & Print → PDF.
  **c. Print Log** (`shipmark::logs`)
- List generate, reprint (regenerate dari snapshot `SMPL_MANUAL_VALUES`).
---

## 5. File Structure (module)

```
Modules/ShipMark/
  app/
    Models/{ShipMarkTemplate,ShipMarkTemplateLine,ShipMarkCustMap,ShipMarkPrintLog}.php
    Repositories/Contracts/*Interface.php
    Repositories/Eloquent/*Repository.php
    Services/{EscResolver,GrainResolver,MasterResolver,TemplateService,GenerateService}.php
    Support/{SscLabelRow,GenerateResult,FieldDictionary}.php
    Exceptions/ShipMarkException.php
    Livewire/{TemplateManager,GenerateMark,PrintLogList}.php   (atau Volt di resources)
    Providers/ShipMarkServiceProvider.php  (bind interface→impl, permissions)
  resources/views/
    livewire/{templates,generate,logs}.blade.php
    pdf/label.blade.php                    ← render 1 label dari lines
  database/migrations/                     ← wrapper raw ship_mark_schema.sql
  routes/web.php
  config/config.php
```
 
---

## 6. Error Handling & Config
- CLOB JSON via `ClobJson` cast (reuse). Angka berat pakai `number_format` sesuai preferensi customer di render.
- Grain UNIFORM/ITEM yang menampilkan `net/gross` = agregat; kalau template minta PALLET_NO/CARTON_NO di grain kasar → tampilkan '-' (tidak berlaku).
- Config: `paper_sizes` (A4/A5/CUSTOM mm), `pdf_engine` (mpdf), `chunk_size` render (default 200 label/PDF pass).
---

## 7. Render Notes (PDF)
- `pdf/label.blade.php`: loop `SMTL` lines → render `LABEL : VALUE`, hormati `SMTL_COL_POS` (2 kolom: GW+NW), `SMTL_INDENT`, `SMTL_IS_BOLD`, `SMTL_FONT_SIZE`.
- Page size dari `SMT_PAPER_SIZE` (mpdf: `A4`/`A5`/`[w,h]` mm), orientation `SMT_ORIENTATION`.
- N besar → render bertahap `chunk_size`, gabung, stream download (hindari OOM).
 
