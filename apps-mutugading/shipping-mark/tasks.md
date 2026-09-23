# TASKS.md — ShipMark Module (Phase 1)

> Jalankan `bash scripts/preflight.sh` DULU sebelum mulai.
> Update [TODO] → [DONE] setelah setiap task selesai + commit.
> Commit: `feat(shipmark): [TXXX] judul`
> ClickUp List ID: **[ISI_SAAT_GENERATE_CLICKUP]**
 
---

## PROGRESS
```
[DONE] 0 / 15 tasks
```
 
---

## GROUP 1 — Foundation

### [TODO] T001 — Scaffold module ShipMark
**Refer ke:** design.md §5 · **Blocker:** tidak ada
Buat module nwidart + service provider (bind interface→impl, register permissions, route, view namespace).
```
php artisan module:make ShipMark
Modules/ShipMark/app/Providers/ShipMarkServiceProvider.php
Modules/ShipMark/routes/web.php, config/config.php
```
**Acceptance:**
- [ ] `php artisan module:list` menampilkan ShipMark (enabled)
- [ ] `php artisan module:route-list ShipMark` jalan tanpa error
- [ ] `./vendor/bin/pint --test` clean
### [TODO] T002 — Deploy DDL SHIP_MARK_* (raw migration)
**Refer ke:** spec.md §1 · **Blocker:** T001
Copy `ship_mark_schema.sql` ke `Modules/ShipMark/database/sql/`, buat migration `unprepared`.
**Acceptance:**
- [ ] `php artisan migrate` sukses
- [ ] `SELECT COUNT(*) FROM MGTAPPS.SHIP_MARK_TEMPLATE` = 1 (seed Bekaert AU)
- [ ] 4 tabel + 4 sequence + 4 trigger ada
### [TODO] T003 — Eloquent models
**Refer ke:** design.md §1 · **Blocker:** T002
4 model, `$connection='oracle'`, PK di-set trigger (`$incrementing=false`, `public $timestamps=false`), relasi, cast `ClobJson` di `ShipMarkPrintLog::$casts['SMPL_MANUAL_VALUES']`.
**Acceptance:**
- [ ] `tinker`: `ShipMarkTemplate::with('lines')->first()` mengembalikan seed + 10 lines
- [ ] cast JSON PrintLog round-trip (set array → simpan → baca array)
---

## GROUP 2 — Orion Read + Grain

### [TODO] T004 — SscScanRepository (join scan + ALTHARA)
**Refer ke:** spec.md §2.1 · **Blocker:** T003
Implementasi `baseRows`, `header`, `distinctItems`. Join `SS_SCAN_CODE = PRD_SCAN_CODE` (LEFT JOIN).
**Acceptance:**
- [ ] `baseRows($txn,$no)` untuk 1 SSC test mengembalikan >0 baris dgn kolom tube_color & bobbin_qty terisi
- [ ] Pest: struktur `SscLabelRow` sesuai
### [TODO] T005 — EscResolver
**Refer ke:** spec.md §2.3 · **Blocker:** T003
`EscResolver::forSsc($txn,$no)` → SO_NO string via `SOHM_REF_SYS_ID = SOH_SYS_ID`.
**Acceptance:**
- [ ] Untuk SSC test yg punya ref SO → return format `TXN-NO`
- [ ] SSC tanpa ref → return null (tidak error)
### [TODO] T006 — GrainResolver
**Refer ke:** spec.md §2.2, design.md §3 · **Blocker:** T004
5 grain, agregasi SUM/COUNT, lot MULTI-aware, `unit_seq`=`n/total`.
**Acceptance:**
- [ ] Pest data fixture: 2 item, item A 2 lot 9 pallet → ITEM=2, ITEM_LOT=3, PALLET=9 label set
- [ ] grain CARTON = jumlah baris base; UNIFORM = 1
- [ ] lot='MULTI' saat >1 lot di grup
---

## GROUP 3 — Template Management

### [TODO] T007 — Repository interfaces + Eloquent impl
**Refer ke:** design.md §2 · **Blocker:** T003
`TemplateRepositoryInterface`, `CustMapRepositoryInterface`, `PrintLogRepositoryInterface` + impl; bind di provider.
**Acceptance:**
- [ ] `app(TemplateRepositoryInterface::class)` resolve
- [ ] `findForCustomer('BEKAUS')` mengembalikan seed
### [TODO] T008 — TemplateService
**Refer ke:** design.md §3, spec.md §4 · **Blocker:** T007
create/update/clone template, saveLines + validasi (§4).
**Acceptance:**
- [ ] Pest: clone template menghasilkan kode baru + lines identik
- [ ] Validasi tolak STATIC tanpa static_value, value_ref di luar kamus
### [TODO] T009 — Livewire Template Manager
**Refer ke:** design.md §4a · **Blocker:** T008
List + form header + line editor (reorder), Flux UI.
**Acceptance:**
- [ ] Buat template baru + baris lewat UI → tersimpan
- [ ] Reorder baris mengubah `SMTL_SORT_ORDER`
- [ ] Pest/Livewire test: submit valid & invalid
---

## GROUP 4 — Generate + Entry + PDF

### [TODO] T010 — MasterResolver + learn-as-you-go
**Refer ke:** spec.md §2.4-2.5 · **Blocker:** T007
resolve dari CustMap; `upsert` (MERGE) saat nilai baru.
**Acceptance:**
- [ ] Pest: resolve miss → null; setelah upsert → nilai
- [ ] MERGE idempotent (upsert 2x tidak duplikat, unique terjaga)
### [TODO] T011 — GenerateService
**Refer ke:** design.md §3 · **Blocker:** T006, T010
Rakit template+grain+MASTER+MANUAL → label set (explode ×copies, group per item) + tulis PrintLog snapshot.
**Acceptance:**
- [ ] Pest fixture SSC → jumlah label = unit(grain) × copies
- [ ] PrintLog tersimpan dgn `SMPL_MANUAL_VALUES` JSON benar
- [ ] CONTRACT_NO terisi dari EscResolver bila template memakainya
### [TODO] T012 — Entry Screen (Livewire)
**Refer ke:** design.md §4b · **Blocker:** T011
Kolom dari baris MANUAL+MASTER-unmapped; baris = item; fill-down; auto-fill MASTER + prompt simpan.
**Acceptance:**
- [ ] Template tanpa MANUAL/MASTER → grid kosong, langsung generate
- [ ] Isi MASTER baru → tersimpan ke CustMap (auto next time)
- [ ] fill-down menyalin nilai ke semua item
### [TODO] T013 — PDF Render
**Refer ke:** design.md §7, spec.md §6 · **Blocker:** T011
`pdf/label.blade.php` + mpdf, ukuran dari `SMT_PAPER_SIZE`, chunk utk N besar.
**Acceptance:**
- [ ] PDF tergenerate utk SSC test grain PALLET (mis. 20+ label)
- [ ] Ukuran A4 vs A5 terhormati; 2-kolom (GW/NW) tampil bila `SMTL_COL_POS=2`
- [ ] Uji N besar (≥300 label) tidak OOM (chunk jalan)
---

## GROUP 5 — Permissions + Polish

### [TODO] T014 — Spatie permissions + gating
**Refer ke:** spec.md §4 · **Blocker:** T009, T013
Permission `shipmark.template.manage`, `shipmark.generate`; gate route + menu.
**Acceptance:**
- [ ] User tanpa permission → 403 di route terkait
- [ ] Menu hanya muncul sesuai permission
### [TODO] T015 — Print Log + Reprint
**Refer ke:** design.md §4c · **Blocker:** T011
List log + reprint (regenerate dari snapshot).
**Acceptance:**
- [ ] Reprint menghasilkan PDF identik dari `SMPL_MANUAL_VALUES`
- [ ] List terfilter per SSC
---

## COMPLETION CRITERIA
Semua [DONE] + `php artisan test` (Pest) hijau + `pint --test` clean = Phase 1 selesai.
PR: `feature/shipmark-phase-1` → `main`. Update semua ClickUp tasks ke DONE.
 
