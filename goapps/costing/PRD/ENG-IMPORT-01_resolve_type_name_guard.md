# ENG-IMPORT-01 — Resolve type dari NAMA (hybrid: auto-generate + reject konflik)

**Versi:** 1.1
**Status:** Proposed — untuk Ilham (engine/Go)
**Author:** Indra
**Last Updated:** 2026-08-15
**Related:** `cost_import_resolve.go` → `ResolveLayer1Products`
**Klasifikasi:** Data-defect prevention di import (bukan engine-calc defect)
**Perubahan dari v1.0:** dari "reject-only" → **hybrid** (auto-generate type & code dari nama bila aman, reject bila konflik eksplisit).

---

## 1. Konteks & bukti

Import Layer-1 (`stg_import_product_master` → `cost_product_master`) me-resolve `product_type_code` lewat exact-join tanpa validasi terhadap identitas produk. `product_type_code` salah tetap type valid → resolve diam-diam, dan `cpm_product_code` ikut ter-generate dari type salah (`CST + wrong_type + YYMM + seq`).

Dampak terkoreksi manual pada 202606 (opening balance): **12.423 baris** salah type dari satu akar (4001 HOY→POY + 5951 batch + 2471 name-truth), lintas 19+ pasangan type.

## 2. Keputusan desain: truth = NAMA, dengan pengaman konflik

- **Sinyal kebenaran = token pertama `product_name`** (uppercase, trim). `erp_item_code` **tidak** dipakai — produk trial/sample bisa ada di sistem ini sebelum di ERP (`erp_item_code` kosong / `TRIAL-…`).
- **Auto-generate**: bila nama menunjuk type valid dan `product_type_code` tidak diisi (atau sama), type & code mengikuti nama.
- **Reject**: bila nama menunjuk type valid **dan** `product_type_code` diisi **berbeda** → dua field eksplisit yang bertentangan → dilihat manusia, jangan ditebak. (Ini menangkap kasus seperti sys_id 23502: nama `FDY` tapi produk aslinya POY — akan ke-reject di import, bukan lolos.)

## 3. Decision table

Notasi: `NAME` = type dari prefix nama (NULL bila prefix bukan type valid & aktif). `PTC` = `product_type_code` (kosong/valid/invalid).

| NAME | PTC | Hasil | Type & code yang dipakai |
|---|---|---|---|
| valid | kosong | **RESOLVE (auto)** | NAME |
| valid | = NAME | **RESOLVE** | NAME |
| valid | valid & ≠ NAME | **REJECT: konflik** | — |
| valid | invalid (non-kosong) | **REJECT: konflik** | — |
| NULL | valid | **RESOLVE** (fallback) | PTC |
| NULL | kosong/invalid | **REJECT: unknown** | — |

Effective type = `COALESCE(NAME_type_id, PTC_type_id)`. Product code di-generate dari effective type → **code ikut nama** pada jalur auto/consistent.

## 4. Perubahan di `ResolveLayer1Products`

Derive dua lookup per baris staging (keduanya `cpt_is_active = TRUE`):

```sql
LEFT JOIN cost_product_type nt
       ON nt.cpt_type_code = upper(split_part(btrim(s.product_name), ' ', 1))
      AND nt.cpt_is_active = TRUE
LEFT JOIN cost_product_type pt
       ON pt.cpt_type_code = upper(btrim(s.product_type_code))
      AND pt.cpt_is_active = TRUE
```

### 4a. Ganti error capture lama → dua kategori

**Catatan:** `errSQL` v1.0 ("product_type_code tidak dikenal" saat `pt` NULL) **harus dihapus** — di hybrid, `pt` NULL tetap sah bila `nt` valid (auto-generate). Ganti dengan:

```sql
-- (i) KONFLIK: nama = type valid, product_type_code diisi tapi != nama
INSERT INTO stg_import_error (job_id, sheet, row_num, key_info, error_message)
SELECT s.job_id, '<errSheetProductMaster>', s.row_num, s.legacy_oracle_sys_id,
       'KONFLIK type: nama (' || nt.cpt_type_code ||
       ') != product_type_code (' || COALESCE(s.product_type_code,'') || ')'
FROM stg_import_product_master s
JOIN cost_product_type nt
     ON nt.cpt_type_code = upper(split_part(btrim(s.product_name),' ',1)) AND nt.cpt_is_active
LEFT JOIN cost_product_type pt
     ON pt.cpt_type_code = upper(btrim(s.product_type_code)) AND pt.cpt_is_active
WHERE s.job_id = $1
  AND btrim(COALESCE(s.product_type_code,'')) <> ''
  AND pt.cpt_type_id IS DISTINCT FROM nt.cpt_type_id;

-- (ii) UNKNOWN: nama & product_type_code sama-sama tak dikenal
INSERT INTO stg_import_error (job_id, sheet, row_num, key_info, error_message)
SELECT s.job_id, '<errSheetProductMaster>', s.row_num, s.legacy_oracle_sys_id,
       'type tidak dapat ditentukan (nama & product_type_code tidak dikenal)'
FROM stg_import_product_master s
LEFT JOIN cost_product_type nt
     ON nt.cpt_type_code = upper(split_part(btrim(s.product_name),' ',1)) AND nt.cpt_is_active
LEFT JOIN cost_product_type pt
     ON pt.cpt_type_code = upper(btrim(s.product_type_code)) AND pt.cpt_is_active
WHERE s.job_id = $1 AND nt.cpt_type_id IS NULL AND pt.cpt_type_id IS NULL;
```

### 4b. Upsert: pakai effective type & filter baris valid

- Ganti `pt.cpt_type_id` (sebagai type yang di-insert) → `COALESCE(nt.cpt_type_id, pt.cpt_type_id)`.
- Ganti `generate_cost_product_code(pt.cpt_type_id, now())` → `generate_cost_product_code(COALESCE(nt.cpt_type_id, pt.cpt_type_id), now())` (code ikut nama).
- Ganti `JOIN cost_product_type pt ON …` menjadi dua `LEFT JOIN nt/pt` di atas.
- Tambahkan filter baris yang boleh di-resolve:

```sql
WHERE s.job_id = $1
  AND (
        ( nt.cpt_type_id IS NOT NULL
          AND ( btrim(COALESCE(s.product_type_code,'')) = ''
                OR pt.cpt_type_id = nt.cpt_type_id ) )
     OR ( nt.cpt_type_id IS NULL AND pt.cpt_type_id IS NOT NULL )
      )
```

## 5. Test cases (WAJIB di PR)

| # | product_name | product_type_code | erp | Hasil | Type/code |
|---|---|---|---|---|---|
| 1 | `POY 250/...` | `POY` | POY0000289 | RESOLVE | POY |
| 2 | `POY 250/...` | *(kosong)* | *(kosong)* | RESOLVE (auto, trial/sample) | POY |
| 3 | `HOY 120/...` | `HOY` | HOY0000002 | RESOLVE (HOY genuine) | HOY |
| 4 | `POY 250/...` | `HOY` | POY0000289 | **REJECT konflik** | — |
| 5 | `TCY 1200/...` | `TTS` | TCY0000054 | **REJECT konflik** | — |
| 6 | `FDY 50/...` | `POY` | POY0000555 | **REJECT konflik** (kasus 23502-style) | — |
| 7 | `poy 250/...` | `POY` | — | RESOLVE (upper/trim) | POY |
| 8 | `SUPERYARN X` | `PTY` | — | RESOLVE (nama bukan type → fallback PTC) | PTY |
| 9 | `SUPERYARN X` | *(kosong)* | — | **REJECT unknown** | — |
| 10 | `PTY BO-PTY 225/...` | *(kosong)* | (BO POY)... | RESOLVE (auto, prefix `PTY`) | PTY |

## 6. Catatan

- Auto-generate hanya untuk jalur aman (nama valid, tak ada type-code yang bertentangan). Setiap pertentangan eksplisit → reject → manusia review. Ini menyeimbangkan "code & type ikut nama" dengan perlindungan dari typo nama.
- Tidak mengubah kontrak API; hanya validasi + baris reject + sumber type. Tidak perlu migration (perubahan hanya di kode resolve, bukan schema).
- (Hardening opsional) normalisasi `upper(btrim())` sudah diterapkan pada kedua sisi lookup; konsisten untuk casing/spasi.
