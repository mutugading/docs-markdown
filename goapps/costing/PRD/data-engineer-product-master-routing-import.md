# Data Engineering Guide: Product Master & Routing Import

> **Dokumen ini ditujukan untuk Data Engineer** yang akan menyiapkan data Excel dari Oracle lama
> untuk di-import ke sistem Product Costing baru (GoApps Finance).
>
> Buat pertanyaan teknis, hubungi tim backend.
> **Versi:** 1.0 — Juni 2026

---

## Daftar Isi

1. [Ringkasan Alur Import](#1-ringkasan-alur-import)
2. [Prerequisite: Data yang Harus Ada Duluan](#2-prerequisite-data-yang-harus-ada-duluan)
3. [ERD: Peta Relasi Tabel](#3-erd-peta-relasi-tabel)
4. [Sheet 1 — Product Master](#4-sheet-1--product-master)
5. [Sheet 2 — Product Parameters (Nilai Parameter per Produk)](#5-sheet-2--product-parameters)
6. [Sheet 3 — Applicable Parameters (Param yang Berlaku per Produk)](#6-sheet-3--applicable-parameters)
7. [Sheet 4 — Route Head (Header Routing per Produk)](#7-sheet-4--route-head)
8. [Sheet 5 — Route Sequences (Node/Level dalam Routing)](#8-sheet-5--route-sequences)
9. [Sheet 6 — Route RMs (Komponen Bahan Baku per Sequence)](#9-sheet-6--route-rms)
10. [Business Rules & Validasi Wajib](#10-business-rules--validasi-wajib)
11. [Gap Analysis: Hal yang Perlu Diselesaikan Sebelum Import](#11-gap-analysis)
12. [Urutan Loading Data](#12-urutan-loading-data)
13. [Referensi: Kode-kode Valid](#13-referensi-kode-kode-valid)

---

## 1. Ringkasan Alur Import

### Gambaran Besar

```
ORACLE (Lama)                    SISTEM BARU (GoApps Finance)
─────────────                    ────────────────────────────
Product Master Data ────────────► cost_product_master
                                    ↓ CASCADE
Product Parameter Values ────────► cost_product_parameter
                                    (mana param yang berlaku)
                   ─────────────► cost_product_applicable_param

Product Routing (multi-level) ──► cost_route_head
                                    ↓ CASCADE
                        ────────► cost_route_seq  (per level/node)
                                    ↓ CASCADE
                        ────────► cost_route_rm   (RM per node)
                                    ↕ FK lookup
                        ────────► cost_erp_item   (ERP items — harus ada duluan)
                        ────────► cst_rm_group_head (RM Groups — harus ada duluan)
```

### Yang TIDAK perlu data engineer isi:

| Kolom | Alasan |
|-------|--------|
| `cpm_product_code` | **Auto-generate** oleh sistem (`CSTxxx2506xxxxxx`). Legacy Oracle code cukup taruh di `cpm_flex_02`. |
| Semua `*_id` internal | Primary key auto-generate (BIGSERIAL / UUID). |
| `created_at`, `updated_at` | Sistem isi otomatis saat import. |
| `cpm_erp_linked_at/by` | Isi sistem saat ERP linking, bukan saat import awal. |

---

## 2. Prerequisite: Data yang Harus Ada Duluan

Sebelum data product master & routing bisa di-import, pastikan tabel-tabel berikut sudah berisi data:

### 2.1 Tabel yang Sudah Ada (Seeded oleh Sistem)

Tabel ini sudah di-seed otomatis — **data engineer tidak perlu mengisi**:

| Tabel | Isi | Cara Cek |
|-------|-----|----------|
| `cost_product_type` | 16 tipe produk (ACY, ITY, dll) | Lihat [§13.1](#131-product-type-codes) |
| `mst_parameter` | Semua parameter (DENIER, MC_SPEED, dll) | Lihat di UI: Finance → Parameters |
| `mst_uom` | Satuan (KG, METER, dll) | Lihat di UI: Finance → UOM |

### 2.2 Tabel yang HARUS Diisi Terlebih Dahulu

> ⚠️ Jika data berikut belum ada, **import bisa berhasil** tapi **kalkulasi biaya akan gagal** karena engine tidak bisa menemukan rate RM.

#### A. ERP Item Rates (`cst_item_cons_stk_po`)

> **Klarifikasi penting**: Kolom `crm_rm_item_code` di `cost_route_rm` adalah plain `VARCHAR` — **tidak ada FK constraint** ke tabel apapun. Jadi import routing dengan `rm_type = ITEM` **tidak akan gagal** meskipun item belum ada di DB.
>
> Namun agar **calc engine bisa menghitung harga** komponen bertipe ITEM, item_code harus ada di `cst_item_cons_stk_po` (tabel sync dari Oracle yang digunakan untuk lookup rate). Tabel ini sudah diisi secara rutin oleh sync Oracle (bisa dilihat di halaman Finance → Item Cons Stock PO).

Cara cek item yang sudah ada:
```sql
SELECT DISTINCT item_code, item_name
FROM cst_item_cons_stk_po
WHERE item_code IN ('ITEM-CODE-1', 'ITEM-CODE-2', ...)
ORDER BY item_code;
```

> **Catatan**: `cost_erp_item` adalah tabel terpisah (rencana future sync) yang saat ini **kosong dan tidak dipakai** oleh import maupun calc engine. Abaikan tabel ini.

#### B. RM Groups (`cst_rm_group_head`)

Dibutuhkan jika routing menggunakan tipe RM = `GROUP` (misalnya: kelompok polyester dye, polyester chip).

Data ini harus sudah dikonfigurasi di UI: **Finance → RM Pricing → RM Groups**.

Cara cek:
```sql
SELECT group_code, group_name FROM cst_rm_group_head WHERE deleted_at IS NULL;
```

Catat semua `group_code` yang ada — dibutuhkan di Sheet 6.

#### C. Produk Lain yang Dijadikan RM

Untuk routing **multi-level**, produk yang digunakan sebagai bahan baku (tipe RM = `PRODUCT`) harus sudah ada di `cost_product_master` sebelum produk yang menggunakannya bisa di-import.

**Urutan import produk sangat penting** — lihat [§12. Urutan Loading Data](#12-urutan-loading-data).

---

## 3. ERD: Peta Relasi Tabel

### Diagram Relasi

```
cost_product_type (1)
    │
    ├── cpt_type_id  ◄─────────────────────────────────────────┐
    │                                                           │
    └──(1:N)──► cost_product_master (CPM_)                     │ FK
                    │  cpm_product_sys_id (PK, auto)           │
                    │  cpm_product_code   (auto-generate)       │
                    │  cpm_product_type_id ──────────────────►──┘
                    │  cpm_product_name
                    │  cpm_shade_code / cpm_shade_name
                    │  cpm_grade_code
                    │  cpm_erp_item_code  (soft-link ke ERP)
                    │  cpm_flex_02        ◄── simpan Oracle sys_id di sini
                    │
                    ├──(1:N CASCADE)──► cost_product_parameter (CPP_)
                    │                      cpp_param_id  ──────────► mst_parameter
                    │                      cpp_value_numeric / _text / _flag
                    │
                    ├──(1:N CASCADE)──► cost_product_applicable_param (CAPP_)
                    │                      capp_param_id ──────────► mst_parameter
                    │                      capp_is_required
                    │
                    └──(1:1 RESTRICT)─► cost_route_head (CRH_)
                                            crh_head_id (PK, auto)
                                            crh_routing_status
                                            │
                                            └──(1:N CASCADE)──► cost_route_seq (CRS_)
                                                                    crs_route_level  (1,2,3,...)
                                                                    crs_route_seq    (1,2,3,...)
                                                                    crs_product_sys_id ──► CPM_
                                                                    │
                                                                    └──(1:N CASCADE)─► cost_route_rm (CRM_)
                                                                                            crm_rm_type = 'PRODUCT' → cpm_product_sys_id
                                                                                            crm_rm_type = 'ITEM'    → cost_erp_item
                                                                                            crm_rm_type = 'GROUP'   → cst_rm_group_head
                                                                                            crm_route_rm_ratio
```

### Penjelasan Multi-Level Routing (DAG)

Routing disusun dalam **level** dan **sequence**:

```
Produk FG: FG-POLYESTER-150D (route_head)
│
├── Level 1, Seq 1: FG-POLYESTER-150D  ← node utama (produk FG itu sendiri)
│   │
│   ├── RM: POLYESTER-POY-100D  [tipe: PRODUCT]  ratio: 1.05
│   ├── RM: DYE-DISPERSE        [tipe: GROUP]     ratio: 0.02
│   └── RM: ADDITIVE-XYZ        [tipe: ITEM]      ratio: 0.005
│
└── Level 2, Seq 1: POLYESTER-POY-100D  ← produk intermediate (harus ada di product_master)
    │
    ├── RM: POLYESTER-CHIP-BRIGHT  [tipe: GROUP]   ratio: 1.10
    └── RM: CATALYST-ABC           [tipe: ITEM]    ratio: 0.001
```

> **Aturan**: Setiap `cost_route_seq` mewakili satu **node produk** dalam DAG. Satu node berisi daftar RM yang dibutuhkan untuk membuat produk tersebut.
>
> `crs_product_sys_id` pada route_seq adalah **produk yang dibuat** di node tersebut — bukan RM-nya.

---

## 4. Sheet 1 — Product Master

**Tabel DB:** `cost_product_master`
**Nama sheet Excel:** `product_master`

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `product_type_code` | → lookup `cost_product_type.cpt_type_code` | VARCHAR(5) | ✅ | Contoh: `ITY`, `ACY`, `TPY`. Lihat [§13.1](#131-product-type-codes) |
| `product_name` | `cpm_product_name` | TEXT | ✅ | Nama lengkap produk |
| `shade_code` | `cpm_shade_code` | VARCHAR(50) | ❌ | Kode shade dari Oracle (contoh: `Z114S`, `NL`) |
| `shade_name` | `cpm_shade_name` | VARCHAR(100) | ❌ | Nama shade (contoh: `JET BLACK`, `NATURAL`) |
| `grade_code` | `cpm_grade_code` | VARCHAR(20) | ❌ | Default: `AX`. Contoh: `AX`, `AM`, `B`, `C` |
| `description` | `cpm_description` | TEXT | ❌ | Deskripsi tambahan |
| `erp_item_code` | `cpm_erp_item_code` | VARCHAR(20) | ❌ | Item code di Oracle ERP (soft-link, tidak ada FK constraint) |
| `legacy_oracle_sys_id` | `cpm_flex_02` | VARCHAR(20) | ❌ | **Isi dengan sys_id Oracle lama** untuk trace-back |
| `legacy_erp_compound_key` | `cpm_flex_01` | VARCHAR(100) | ❌ | Format Oracle: `{item_code}-{shade_code}-{type}` |
| `legacy_type_label` | `cpm_flex_03` | VARCHAR(20) | ❌ | Label tipe produk dari sistem Oracle lama |
| `is_active` | `cpm_is_active` | BOOLEAN | ❌ | Default: `TRUE`. Isi `FALSE` untuk produk yang sudah tidak aktif |

### Kolom yang TIDAK boleh diisi oleh data engineer

| Kolom DB | Alasan |
|----------|--------|
| `cpm_product_code` | Auto-generate oleh sistem saat import |
| `cpm_product_sys_id` | Auto-increment (Primary Key) |
| `cpm_created_at`, `cpm_updated_at` | Auto-fill oleh sistem |
| `cpm_erp_linked_at`, `cpm_erp_linked_by` | Diisi saat ERP linking setelah import |

### Contoh Baris Excel

| product_type_code | product_name | shade_code | shade_name | grade_code | erp_item_code | legacy_oracle_sys_id | is_active |
|---|---|---|---|---|---|---|---|
| `ITY` | Polyester ITY 150D/48F Z114S | Z114S | JET BLACK | AX | PE-DTY-150-48 | 10023456 | TRUE |
| `ITY` | Polyester ITY 150D/48F NL | NL | NATURAL | AX | PE-DTY-150-48 | 10023457 | TRUE |
| `ACY` | Nylon ACY 20D/1F | NL | NATURAL | AX | NY-ACY-020-01 | 10023458 | TRUE |
| `TPY` | Polyester Twisted 300D/96F | Z108S | DARK NAVY | B | PE-TW-300-96 | 10023460 | FALSE |

### Catatan Penting

> 💡 Setiap baris di sheet ini akan menghasilkan **1 produk baru** dengan kode CST yang di-generate otomatis.
> Kode yang di-generate akan punya format: `CST` + type_code + YYMM + 6 digit urutan.
> Contoh: `CSTITy2606000001`
>
> Kode asli Oracle tersimpan aman di kolom `cpm_flex_02` untuk keperluan referensi silang.

---

## 5. Sheet 2 — Product Parameters

**Tabel DB:** `cost_product_parameter`
**Nama sheet Excel:** `product_parameters`

### Penjelasan

Setiap baris mewakili **satu nilai parameter untuk satu produk**.
Jika produk A punya 20 parameter, maka ada 20 baris untuk produk A.

### Jenis Data Parameter

| `param_category` | Penjelasan | Kolom nilai yang diisi |
|-----------------|------------|----------------------|
| `INPUT` | Nilai yang diinput manual (denier, filamen count, dll) | `value_numeric` atau `value_text` |
| `RATE` | Rate biaya (electricity rate, labor rate) | `value_numeric` |
| `MASTER_LOOKUP` | Kode yang akan di-lookup ke master tabel lain | `value_text` (isi kodenya, bukan nilai) |
| `CALCULATED` | Dihitung otomatis oleh formula — **TIDAK perlu diisi** | — |

> ⚠️ Untuk param dengan `param_category = CALCULATED`, **jangan isi di sheet ini**. Nilai dihitung otomatis oleh engine.

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `legacy_oracle_sys_id` | — | VARCHAR | ✅ | Untuk join ke Sheet 1. Harus sama persis dengan kolom `legacy_oracle_sys_id` di Sheet 1 |
| `param_code` | → lookup `mst_parameter.param_code` | VARCHAR(20) | ✅ | Contoh: `DENIER`, `MC_SPEED`, `MACHINE_RPM`. Lihat [§13.2](#132-daftar-param-code) |
| `data_type` | ← dari `mst_parameter.data_type` | VARCHAR(10) | ✅ | `NUMBER`, `TEXT`, atau `BOOLEAN`. Harus sesuai definisi param |
| `value_numeric` | `cpp_value_numeric` | DECIMAL(20,6) | ❌* | Isi jika `data_type = NUMBER` |
| `value_text` | `cpp_value_text` | TEXT | ❌* | Isi jika `data_type = TEXT` atau `MASTER_LOOKUP` |
| `value_flag` | `cpp_value_flag` | BOOLEAN | ❌* | Isi jika `data_type = BOOLEAN` (TRUE/FALSE) |

> ⚠️ `*` **Tepat SATU kolom nilai harus diisi** per baris. Dua atau lebih kolom nilai terisi = error.

### Aturan Pengisian Nilai

```
Jika data_type = NUMBER  → isi value_numeric saja. value_text dan value_flag dikosongkan.
Jika data_type = TEXT    → isi value_text saja.   value_numeric dan value_flag dikosongkan.
Jika data_type = BOOLEAN → isi value_flag saja.   value_numeric dan value_text dikosongkan.

Untuk MASTER_LOOKUP params → isi value_text dengan KODE (bukan nilai numerik!).
   Contoh: param MC_NAME → value_text = "MC001" (kode mesin, bukan nilai kecepatan)
```

### Contoh Baris Excel

| legacy_oracle_sys_id | param_code | data_type | value_numeric | value_text | value_flag |
|---|---|---|---|---|---|
| 10023456 | DENIER | NUMBER | 150 | | |
| 10023456 | FILAMENT_COUNT | NUMBER | 48 | | |
| 10023456 | MC_SPEED | NUMBER | 850 | | |
| 10023456 | MC_NAME | TEXT | | MC001 | |
| 10023456 | INTERMINGLE | TEXT | | INTM-A | |
| 10023456 | STD_LOSS_GRADE | TEXT | | GRADE-AX | |
| 10023457 | DENIER | NUMBER | 150 | | |
| 10023457 | FILAMENT_COUNT | NUMBER | 48 | | |

> 💡 Jika tidak tahu nilai suatu parameter untuk suatu produk, **jangan isi baris itu**. Data engineer bisa mengosongkan parameter yang tidak ada datanya — tim costing bisa mengisi nanti via UI.

### Parameter MASTER_LOOKUP — Penjelasan Lebih Lanjut

Beberapa parameter bersifat **lookup ke tabel master** (mesin, intermingling, dll). Untuk param-param ini, nilai yang diisi di `value_text` adalah **kode dari tabel master** yang bersangkutan:

| `param_code` | Kode yang diisi di `value_text` | Tabel master tujuan |
|---|---|---|
| `MC_NAME` | Kode mesin (contoh: `MC001`) | `mst_machine` |
| `INTERMINGLE` | Kode intermingling (contoh: `INTM-A`) | `mst_intermingling` |
| `STD_LOSS_GRADE` | Kode product grade (contoh: `GRADE-AX`) | `mst_product_grade` |
| `MB_CODE` | Kode MB Head (contoh: `MB-001`) | `mst_mb_head` |
| `CAP_PACK_CODE` | Kode box/bobbin cap (contoh: `CAP-STD`) | `mst_box_bobbin_cost` |
| `DEL_PACK_CODE` | Kode box/bobbin delivery | `mst_box_bobbin_cost` |

> ⚠️ Kode-kode ini harus sudah ada di tabel master masing-masing sebelum import dilakukan. Tanyakan ke tim backend untuk mendapatkan daftar kode yang sudah ada.

---

## 6. Sheet 3 — Applicable Parameters

**Tabel DB:** `cost_product_applicable_param`
**Nama sheet Excel:** `product_applicable_params`

### Penjelasan

Tabel ini mendefinisikan **subset parameter yang berlaku untuk setiap produk**. Ini seperti "checklist" — parameter mana yang relevan untuk produk tertentu.

Contoh: Produk ITY butuh parameter `MC_SPEED`, `DENIER`, `FILAMENT_COUNT`. Produk ACY tidak butuh `MC_SPEED` tapi butuh `TENSION`.

> ⚠️ **Jika sebuah produk tidak punya entry di tabel ini, parameter tidak bisa diisi via UI** (fitur fill-task akan mengabaikan parameter yang tidak ada di CAPP). **Sangat penting untuk mengisi sheet ini dengan benar.**

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `legacy_oracle_sys_id` | — | VARCHAR | ✅ | Untuk join ke Sheet 1 |
| `param_code` | → lookup `mst_parameter.param_code` | VARCHAR(20) | ✅ | Parameter yang berlaku untuk produk ini |
| `is_required` | `capp_is_required` | BOOLEAN | ✅ | `TRUE` jika parameter WAJIB diisi sebelum kalkulasi. `FALSE` jika opsional |
| `display_order` | `capp_display_order` | INTEGER | ❌ | Urutan tampil di UI. Jika kosong, urutan dari `mst_parameter` yang digunakan |

### Contoh Baris Excel

| legacy_oracle_sys_id | param_code | is_required | display_order |
|---|---|---|---|
| 10023456 | DENIER | TRUE | 1 |
| 10023456 | FILAMENT_COUNT | TRUE | 2 |
| 10023456 | MC_NAME | TRUE | 3 |
| 10023456 | MC_SPEED | FALSE | 4 |
| 10023456 | MACHINE_RPM | FALSE | 5 |
| 10023456 | INTERMINGLE | FALSE | 6 |
| 10023457 | DENIER | TRUE | 1 |
| 10023457 | FILAMENT_COUNT | TRUE | 2 |

> 💡 **Tip**: Sheet 2 (product_parameters) dan Sheet 3 (applicable_params) sebaiknya punya set param_code yang **sama** untuk setiap produk. Jika suatu param ada nilainya di Sheet 2 tapi tidak ada di Sheet 3, tim backend perlu memutuskan apakah auto-add ke CAPP atau reject.

---

## 7. Sheet 4 — Route Head

**Tabel DB:** `cost_route_head`
**Nama sheet Excel:** `route_head`

### Penjelasan

Satu baris di sheet ini = **satu routing untuk satu produk**. Setiap produk maksimal punya **1 routing aktif** (constraint unik di DB).

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `legacy_oracle_sys_id` | — | VARCHAR | ✅ | Untuk join ke Sheet 1 (produk mana yang punya routing ini) |
| `routing_status` | `crh_routing_status` | VARCHAR(20) | ❌ | Default: `DRAFT`. Nilai: `DRAFT`, `COMPLETE`, `LOCKED` |
| `notes` | `crh_notes` | TEXT | ❌ | Catatan tentang routing ini |

### Contoh Baris Excel

| legacy_oracle_sys_id | routing_status | notes |
|---|---|---|
| 10023456 | COMPLETE | Routing dari Oracle, diverifikasi Juni 2026 |
| 10023457 | COMPLETE | |
| 10023458 | DRAFT | Routing belum final, perlu review |

> 💡 Setelah import, status bisa diubah via UI. Untuk data production, disarankan import dengan status `DRAFT` dulu, lalu verifikasi routing di UI sebelum diubah ke `COMPLETE`.

---

## 8. Sheet 5 — Route Sequences

**Tabel DB:** `cost_route_seq`
**Nama sheet Excel:** `route_sequences`

### Penjelasan

Setiap baris = **satu node dalam routing DAG**. Satu node = satu produk di satu level tertentu.

Untuk routing multi-level:
- Level 1 = produk FG (Final Good) — selalu ada
- Level 2 = produk intermediate yang dipakai oleh Level 1
- Level 3 = produk intermediate yang dipakai oleh Level 2
- dst.

Satu level bisa punya **lebih dari satu sequence** jika ada beberapa jalur paralel.

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `route_head_legacy_product_id` | — | VARCHAR | ✅ | `legacy_oracle_sys_id` dari produk pemilik routing (join ke Sheet 4) |
| `node_product_legacy_id` | — | VARCHAR | ✅ | `legacy_oracle_sys_id` dari produk yang ada di node ini. Bisa sama dengan `route_head_legacy_product_id` untuk Level 1 |
| `route_level` | `crs_route_level` | INTEGER | ✅ | Nomor level: `1`, `2`, `3`, ... Minimal 1 |
| `route_seq` | `crs_route_seq` | INTEGER | ✅ | Nomor sequence dalam level: `1`, `2`, ... Minimal 1 |
| `route_name` | `crs_route_name` | VARCHAR(200) | ❌ | Nama deskriptif node ini |
| `route_item_code` | `crs_route_item_code` | VARCHAR(30) | ❌ | Item code ERP untuk node ini |
| `route_shade_code` | `crs_route_shade_code` | VARCHAR(30) | ❌ | Shade code untuk node ini |
| `route_shade_name` | `crs_route_shade_name` | VARCHAR(100) | ❌ | Nama shade untuk node ini |

### Contoh Multi-Level Routing

Skenario: `FG-POLYESTER-150D` (Level 1) menggunakan `POY-100D` sebagai intermediate (Level 2).

| route_head_legacy_product_id | node_product_legacy_id | route_level | route_seq | route_name |
|---|---|---|---|---|
| 10023456 | 10023456 | 1 | 1 | FG Polyester 150D/48F Z114S |
| 10023456 | 10099001 | 2 | 1 | POY Polyester 100D/36F |

Artinya:
- Produk `10023456` punya routing dengan 2 level.
- Level 1: node = produk itu sendiri (`10023456`)
- Level 2: node = produk POY `10099001` (yang jadi bahan baku intermediate)

> ⚠️ **Penting**: `node_product_legacy_id` di Level 2+ harus merujuk ke produk yang juga ada di **Sheet 1** (product_master). Jika produk intermediate tidak diimport di Sheet 1, import akan gagal.

### Contoh Routing Flat (1 Level)

Jika produk hanya punya 1 level (langsung pakai RM, tidak ada intermediate):

| route_head_legacy_product_id | node_product_legacy_id | route_level | route_seq | route_name |
|---|---|---|---|---|
| 10023458 | 10023458 | 1 | 1 | Nylon ACY 20D/1F |

---

## 9. Sheet 6 — Route RMs

**Tabel DB:** `cost_route_rm`
**Nama sheet Excel:** `route_rms`

### Penjelasan

Setiap baris = **satu komponen bahan baku** dalam sebuah node routing.
Satu node (route_seq) bisa punya banyak RM.

### Tipe RM

Ada 3 tipe RM yang harus dipilih per baris:

| `rm_type` | Artinya | Kolom yang diisi |
|-----------|---------|-----------------|
| `PRODUCT` | RM-nya adalah produk lain yang juga ada di product_master | `rm_product_legacy_id` |
| `ITEM` | RM-nya adalah ERP item dari Oracle | `rm_item_code` |
| `GROUP` | RM-nya adalah RM group yang sudah dikonfigurasi | `rm_group_code` |

> ⚠️ **Hanya satu** dari kolom `rm_product_legacy_id`, `rm_item_code`, atau `rm_group_code` yang boleh diisi per baris. Sistem akan reject jika lebih dari satu diisi, atau tidak ada yang diisi.

### Kolom yang Diperlukan

| Kolom Excel | Kolom DB | Tipe | Wajib | Keterangan |
|-------------|----------|------|-------|------------|
| `route_head_legacy_product_id` | — | VARCHAR | ✅ | `legacy_oracle_sys_id` produk pemilik routing |
| `route_level` | — | INTEGER | ✅ | Level node yang punya RM ini (untuk join ke Sheet 5) |
| `route_seq` | — | INTEGER | ✅ | Sequence dalam level (untuk join ke Sheet 5) |
| `rm_type` | `crm_rm_type` | VARCHAR(20) | ✅ | `PRODUCT`, `ITEM`, atau `GROUP` |
| `rm_product_legacy_id` | → lookup `cpm_product_sys_id` | VARCHAR | ❌* | Isi jika `rm_type = PRODUCT`. `legacy_oracle_sys_id` dari produk RM |
| `rm_item_code` | `crm_rm_item_code` | VARCHAR(30) | ❌* | Isi jika `rm_type = ITEM`. Harus ada di `cost_erp_item` |
| `rm_group_code` | `crm_rm_group_code` | VARCHAR(30) | ❌* | Isi jika `rm_type = GROUP`. Harus ada di `cst_rm_group_head` |
| `ratio` | `crm_route_rm_ratio` | DECIMAL(10,6) | ✅ | Rasio konsumsi. Contoh: `1.05` = butuh 1.05 kg RM per 1 kg produk. Harus > 0 |
| `rm_name` | `crm_route_rm_name` | VARCHAR(200) | ❌ | Nama deskriptif RM ini |
| `rm_item_code_display` | `crm_route_rm_item_code` | VARCHAR(30) | ❌ | Item code untuk tampilan (bisa sama dengan `rm_item_code`) |
| `rm_shade_code` | `crm_route_rm_shade_code` | VARCHAR(30) | ❌ | Shade code RM |
| `rm_shade_name` | `crm_route_rm_shade_name` | VARCHAR(100) | ❌ | Nama shade RM |
| `sub_type` | `crm_sub_type` | VARCHAR(30) | ❌ | Sub-tipe klasifikasi RM |
| `notes` | `crm_notes` | TEXT | ❌ | Catatan |

> ⚠️ `*` Tepat **SATU** kolom RM reference yang boleh diisi: `rm_product_legacy_id`, `rm_item_code`, atau `rm_group_code`.

### Contoh Baris Excel

| route_head_legacy_product_id | route_level | route_seq | rm_type | rm_product_legacy_id | rm_item_code | rm_group_code | ratio | rm_name |
|---|---|---|---|---|---|---|---|---|
| 10023456 | 1 | 1 | PRODUCT | 10099001 | | | 1.000000 | POY Intermediate |
| 10023456 | 1 | 1 | GROUP | | | POLYESTER-DYE | 0.020000 | Disperse Dye |
| 10023456 | 1 | 1 | ITEM | | PE-ADD-001 | | 0.005000 | Additive XYZ |
| 10023456 | 2 | 1 | GROUP | | | POLYESTER-CHIP | 1.080000 | Polyester Chip Bright |
| 10023456 | 2 | 1 | ITEM | | CATA-001 | | 0.001000 | Catalyst |
| 10023458 | 1 | 1 | ITEM | | NY-CHIP-001 | | 1.050000 | Nylon Chip |
| 10023458 | 1 | 1 | GROUP | | | NYLON-DYE | 0.030000 | Acid Dye |

---

## 10. Business Rules & Validasi Wajib

Berikut aturan yang **wajib dipenuhi** di data Excel sebelum import. Jika tidak, sistem akan reject.

### 10.1 Aturan di Sheet 1 (Product Master)

| # | Aturan | Contoh BENAR | Contoh SALAH |
|---|--------|-------------|-------------|
| 1 | `product_type_code` harus salah satu dari 16 kode valid | `ITY` | `POLYESTER` (terlalu panjang/salah) |
| 2 | `product_name` tidak boleh kosong | `Polyester ITY 150D/48F` | *(kosong)* |
| 3 | `grade_code` default `AX` jika tidak diisi | | |
| 4 | Tidak ada dua baris dengan `legacy_oracle_sys_id` yang sama | | |

### 10.2 Aturan di Sheet 2 (Product Parameters)

| # | Aturan | Contoh BENAR | Contoh SALAH |
|---|--------|-------------|-------------|
| 1 | `legacy_oracle_sys_id` harus ada di Sheet 1 | `10023456` (ada di S1) | `99999` (tidak ada di S1) |
| 2 | `param_code` harus ada di `mst_parameter` | `DENIER` | `DIAMETER` (tidak ada di sistem) |
| 3 | Tepat satu kolom nilai terisi | `value_numeric = 150` | `value_numeric = 150, value_text = hello` |
| 4 | `data_type` harus sesuai dengan definisi param di DB | DENIER → NUMBER | DENIER → TEXT |
| 5 | `param_category = CALCULATED` — **jangan diisi** | | |
| 6 | Tidak ada dua baris dengan kombinasi `legacy_oracle_sys_id + param_code` yang sama | | |

### 10.3 Aturan di Sheet 3 (Applicable Params)

| # | Aturan | Contoh BENAR | Contoh SALAH |
|---|--------|-------------|-------------|
| 1 | `legacy_oracle_sys_id` harus ada di Sheet 1 | | |
| 2 | `param_code` harus ada di `mst_parameter` | | |
| 3 | `is_required` harus `TRUE` atau `FALSE` | `TRUE` | `Yes` atau `1` |
| 4 | Tidak ada duplikat `legacy_oracle_sys_id + param_code` | | |

### 10.4 Aturan di Sheet 5 (Route Sequences)

| # | Aturan | Contoh BENAR | Contoh SALAH |
|---|--------|-------------|-------------|
| 1 | `route_head_legacy_product_id` harus ada di Sheet 4 | | |
| 2 | `node_product_legacy_id` harus ada di Sheet 1 | `10023456` | `88888` (tidak ada di S1) |
| 3 | `route_level` dan `route_seq` harus ≥ 1 | | `0` atau negatif |
| 4 | Kombinasi `(route_head_legacy_product_id, route_level, route_seq)` harus unik | | |
| 5 | Setiap routing **wajib** punya Level 1, Seq 1 | | Routing mulai dari Level 2 |

### 10.5 Aturan di Sheet 6 (Route RMs)

| # | Aturan | Contoh BENAR | Contoh SALAH |
|---|--------|-------------|-------------|
| 1 | `rm_type` harus `PRODUCT`, `ITEM`, atau `GROUP` | `PRODUCT` | `product` (case-sensitive!) |
| 2 | Tepat satu kolom RM reference terisi sesuai `rm_type` | `rm_type=GROUP, rm_group_code=POLY-DYE` | `rm_type=GROUP, rm_item_code=001` |
| 3 | `ratio` harus > 0 | `1.050000` | `0` atau negatif |
| 4 | Jika `rm_type = PRODUCT`, `rm_product_legacy_id` harus ada di Sheet 1 | | |
| 5 | Jika `rm_type = ITEM`, `rm_item_code` harus ada di `cost_erp_item` | | |
| 6 | Jika `rm_type = GROUP`, `rm_group_code` harus ada di `cst_rm_group_head` | | |
| 7 | Kombinasi `(route_head, level, seq)` harus ada di Sheet 5 | | |

---

## 11. Gap Analysis

Berikut hal-hal yang **perlu dikonfirmasi atau diselesaikan sebelum implementasi import** dapat dimulai.

### 11.1 ⚠️ Circular Dependency pada Multi-Level Routing

**Masalah**: Jika FG Product A menggunakan Intermediate Product B sebagai RM (tipe PRODUCT), maka B harus diimport **sebelum** A. Tapi B mungkin juga punya routing yang menggunakan produk C, dst.

**Implikasi untuk data engineer**: Urutkan data di Sheet 1 dari produk **paling dalam** (raw/intermediate) ke produk **paling luar** (FG). Atau, tim import perlu memproses dalam beberapa pass.

**Pertanyaan ke data engineer**: Apakah di data Oracle ada produk yang saling referensi (A pakai B, B pakai A)? Jika ada, ini perlu dihandle secara khusus.

### 11.2 ℹ️ ERP Item Rates — Import Aman, Kalkulasi Perlu Dicek

**Fakta**: `crm_rm_item_code` di `cost_route_rm` adalah VARCHAR tanpa FK constraint — **import tidak akan gagal** hanya karena item_code belum ada di DB.

**Yang perlu diperhatikan**: Agar **calc engine bisa menghitung biaya** komponen bertipe ITEM, item_code yang diisi di Sheet 6 harus ada di tabel `cst_item_cons_stk_po` (bukan `cost_erp_item`) untuk periode yang akan dikalkulasi. Tabel ini diisi otomatis dari sync Oracle yang sudah berjalan rutin.

**Solusi**: Setelah menyiapkan Sheet 6, data engineer menyerahkan daftar semua `rm_item_code` ke tim backend untuk diverifikasi keberadaannya di `cst_item_cons_stk_po`.

```sql
-- Tim backend bisa cek ini:
SELECT DISTINCT item_code FROM cst_item_cons_stk_po
WHERE item_code IN (/* list dari Sheet 6 */);
```

**Deliverable data engineer**: List semua `rm_item_code` unik yang digunakan di Sheet 6.

### 11.3 ⚠️ RM Groups Belum Dikonfigurasi

**Masalah**: Jika di Sheet 6 ada `rm_type = GROUP` dengan group_code yang belum ada di `cst_rm_group_head`, import akan gagal.

**Solusi**: Semua RM group yang akan digunakan harus sudah dibuat di UI (**Finance → RM Pricing → RM Groups**) sebelum import.

**Deliverable data engineer**: List semua `rm_group_code` yang akan dipakai di Sheet 6.

### 11.4 ⚠️ MASTER_LOOKUP Codes Belum Dikonfigurasi

**Masalah**: Parameter dengan kategori `MASTER_LOOKUP` (seperti `MC_NAME`, `INTERMINGLE`, dll) di Sheet 2 menyimpan **kode dari tabel master lain**. Kode-kode ini harus sudah ada di tabel master-nya (`mst_machine`, `mst_intermingling`, dll).

**Solusi**: Import master data mesin, intermingling, product grade terlebih dahulu, atau kosongkan parameter MASTER_LOOKUP di Sheet 2 dan isi nanti via UI.

**Deliverable data engineer**: List semua kode machine, intermingling, product grade yang digunakan.

### 11.5 ℹ️ Keputusan: cpm_erp_item_code

`cpm_erp_item_code` di product_master adalah **denormalized soft-link** (tidak ada FK constraint ke `cost_erp_item`). Artinya bisa diisi meskipun item belum ada di sistem.

**Keputusan yang perlu dibuat**: Apakah kolom ini diisi saat import atau dibiarkan kosong dan diisi nanti saat ERP linking?

### 11.6 ℹ️ Routing Status

Apakah semua routing diimport dengan status `DRAFT` (lebih aman, bisa direview) atau langsung `COMPLETE`? Disarankan `DRAFT` untuk verifikasi pertama.

---

## 12. Urutan Loading Data

Import harus dilakukan **dalam urutan berikut** untuk menghindari FK violation:

```
TAHAP 1 — Prerequisite (Harus ada sebelum import dimulai)
├── cst_rm_group_head    (RM Groups — konfigurasi via UI)
├── cost_erp_item        (ERP Items — sync dari Oracle)
├── mst_machine          (Master Mesin — jika pakai MASTER_LOOKUP params)
├── mst_intermingling    (Master Intermingling)
├── mst_product_grade    (Master Product Grade)
└── mst_mb_head          (Master MB Head)

TAHAP 2 — Produk Terdalam (Bottom-Up)
└── cost_product_master  (Sheet 1 — import intermediate products DULU)
    Contoh urutan: POY → DTY → FG Yarn

TAHAP 3 — Detail Produk
├── cost_product_applicable_param  (Sheet 3 — sebelum parameter!)
└── cost_product_parameter         (Sheet 2)

TAHAP 4 — Routing
├── cost_route_head  (Sheet 4)
├── cost_route_seq   (Sheet 5)
└── cost_route_rm    (Sheet 6)
```

> ⚠️ **Penting**: Sheet 3 (applicable_params) harus diimport **sebelum** Sheet 2 (product_parameters). Sistem mengacu ke CAPP untuk menentukan parameter mana yang valid untuk sebuah produk.

---

## 13. Referensi: Kode-kode Valid

### 13.1 Product Type Codes

| Kode | Nama Lengkap |
|------|-------------|
| `ACY` | Air Covered Yarn |
| `ATT` | Attached Type Yarn |
| `HOY` | High Oriented Yarn |
| `IDY` | Intermingled Draw Yarn |
| `ITY` | Interlaced Textured Yarn |
| `NTY` | Nylon Textured Yarn |
| `OTH` | Other |
| `PTS` | Polyester Textured Slub |
| `TCH` | Twisted Compound Hybrid |
| `TCS` | Twisted Compound Slub |
| `TFY` | Twisted Filament Yarn |
| `TPM` | Twisted Polyester Multifilament |
| `TPS` | Twisted Polyester Slub |
| `TPY` | Twisted Polyester Yarn |
| `TTM` | Twisted Textured Multifilament |
| `TTS` | Twisted Textured Slub |

### 13.2 Daftar Param Code

> Param code yang tersedia bisa dilihat langsung di UI: **Finance → Parameters**.
>
> Untuk keperluan mapping, minta tim backend export tabel `mst_parameter` dengan kolom:
> `param_code`, `param_name`, `data_type`, `param_category`, `uom_code`
>
> Contoh query:
> ```sql
> SELECT p.param_code, p.param_name, p.data_type, p.param_category,
>        u.uom_code
> FROM mst_parameter p
> LEFT JOIN mst_uom u ON u.uom_id = p.uom_id
> WHERE p.deleted_at IS NULL
>   AND p.is_active = true
>   AND p.param_category NOT IN ('CALCULATED')
> ORDER BY p.param_category, p.param_code;
> ```

### 13.3 Tipe RM yang Valid

| Nilai | Artinya | Kolom yang harus diisi |
|-------|---------|----------------------|
| `PRODUCT` | Produk lain di product_master | `rm_product_legacy_id` |
| `ITEM` | ERP item dari Oracle | `rm_item_code` |
| `GROUP` | RM group (kumpulan items dengan shared cost) | `rm_group_code` |

### 13.4 Routing Status yang Valid

| Nilai | Artinya |
|-------|---------|
| `DRAFT` | Routing masih dalam proses. Bisa diedit. Direkomendasikan saat import |
| `COMPLETE` | Routing sudah diverifikasi dan siap digunakan untuk kalkulasi |
| `LOCKED` | Routing terkunci (sudah ada hasil kalkulasi yang menggunakannya) |

### 13.5 Parameter Category

| Nilai | Artinya | Perlu diisi di Sheet 2? |
|-------|---------|------------------------|
| `INPUT` | Nilai yang diinput manual per produk | ✅ Ya |
| `RATE` | Rate biaya yang berlaku untuk produk | ✅ Ya |
| `MASTER_LOOKUP` | Kode yang dilookup ke tabel master | ✅ Ya (isi kodenya di `value_text`) |
| `CALCULATED` | Dihitung otomatis oleh formula engine | ❌ Tidak (sistem hitung sendiri) |

---

## Lampiran: Daftar Checklist Data Engineer

Sebelum menyerahkan file Excel ke tim backend, pastikan:

### Checklist Umum
- [ ] Semua sheet sudah ada dalam satu file `.xlsx`
- [ ] Nama sheet persis: `product_master`, `product_parameters`, `product_applicable_params`, `route_head`, `route_sequences`, `route_rms`
- [ ] Header kolom persis sama dengan yang tertera di dokumen ini (case-sensitive)
- [ ] Tidak ada baris header duplikat di tengah data
- [ ] Kolom `legacy_oracle_sys_id` konsisten di semua sheet (nilai yang sama merujuk ke produk yang sama)

### Checklist Sheet 1 (Product Master)
- [ ] `product_type_code` semua valid (lihat §13.1)
- [ ] `product_name` tidak ada yang kosong
- [ ] `legacy_oracle_sys_id` unik, tidak ada duplikat
- [ ] Produk intermediate (yang digunakan sebagai RM tipe PRODUCT) sudah ada di sheet ini

### Checklist Sheet 2 (Product Parameters)
- [ ] Semua `param_code` valid (bisa dicek via query §13.2)
- [ ] Tidak ada `CALCULATED` params yang diisi
- [ ] Setiap baris hanya punya tepat 1 kolom nilai terisi
- [ ] `data_type` sesuai dengan definisi param di database

### Checklist Sheet 3 (Applicable Params)
- [ ] Semua produk di Sheet 2 juga ada entry di Sheet 3
- [ ] `is_required` terisi semua (TRUE/FALSE)

### Checklist Sheet 6 (Route RMs)
- [ ] Tidak ada baris yang mengisi lebih dari 1 kolom RM reference
- [ ] `rm_type` sesuai dengan kolom yang diisi (`PRODUCT`/`ITEM`/`GROUP`)
- [ ] Semua `rm_item_code` sudah dikonfirmasi ke tim backend bahwa ada di `cost_erp_item`
- [ ] Semua `rm_group_code` sudah dikonfirmasi ke tim backend bahwa ada di `cst_rm_group_head`
- [ ] `ratio` tidak ada yang 0 atau kosong

### Deliverable ke Tim Backend (sebelum import)
- [ ] Daftar semua `rm_item_code` yang digunakan di Sheet 6
- [ ] Daftar semua `rm_group_code` yang digunakan di Sheet 6
- [ ] Daftar semua machine code, intermingling code, grade code yang digunakan di Sheet 2 (MASTER_LOOKUP params)
- [ ] Konfirmasi urutan import (produk mana yang intermediate, mana yang FG)

---

*Dokumen ini dibuat berdasarkan state database GoApps Finance per Juni 2026.*
*Jika ada perubahan struktur tabel, dokumen ini perlu diperbarui.*
