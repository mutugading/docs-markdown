# 7. Packing & Grading

## Grade Hierarchy TXT/TWT

Grade di-assign saat box di-seal di packing (bukan saat produksi).

| Grade | Berat Bobbin | Keterangan |
|---|---|---|
| AX | Full bobbin ± tolerance | Standar tertinggi |
| AE | Full bobbin, toleransi lebih longgar | Defect sangat terbatas |
| A9 | 2.00 kg – <std weight | Unfull bobbin |
| A | 1.00 – <2.00 kg | Beberapa defect ringan |
| APQ | 2.00 kg – std | Khusus dyeing variation |
| B | >0.50 – <1.00 kg | Defect lebih banyak |
| C / BB | >0.50 kg | Terendah yang di-pack. BB untuk benang berwarna |
| JLT | >0.50 kg | Job Lot — Semidull, BSD, DSD Colors |
| WASTE | <0.50 kg atau Low Knots ≥72f | Tidak di-pack |

**AX tolerance:** SD/NI: ±100g · Denier ≥300: ±150g · NIM: ±150g · DTY ≥1250D: ±500g

**Grade AM:** Bukan grade produksi — grouping warehouse A9+A dalam 1 pallet untuk delivery.

**Grade SPG:** Tidak ada. POY adalah intermediate captive.

---

## Packing Data Source (Oracle → PostgreSQL)

| Tabel Oracle | Schema | Scope |
|---|---|---|
| `PAKPKGDUP` | `ASPAK` | Semua data packing (utama) |
| `PAKPKG` | `ASMAR` | Breakup original lot — **hanya grade B/BB** |
| `PAKPKGDUPAM` | `ASPAK` | Breakup grade AM → A9 + A |

**Relasi penting:**
- Grade B/BB: `PKG_MERGE_NO` di PAKPKGDUP = COMMON LOT → original dari PAKPKG (outer join)
- Grade AM: tersimpan sebagai AM → breakup di PAKPKGDUPAM (outer join)
- Grade lain: `PKG_MERGE_NO` sudah = original lot

**Matching ke WO:** Pakai `original_lot_no`, bukan common lot.

---

## Common Lot

Bobbin sisa yang tidak cukup untuk 1 box sendiri dapat digabung (common lot):
- Boleh lintas shade code
- Common lot mendapat item code dan shade code baru di ERP Orion
- Original lot tetap tercatat sebagai component

---

## Schema

```sql
-- PostgreSQL: agregasi grade per lot (dari ETL PPC_GRADE_ACTUAL Oracle)
WO_GRADE_ACTUAL
  wga_id                   BIGSERIAL PK
  wga_wo_id                BIGINT
  wga_lot_no               VARCHAR(30)   -- original lot
  wga_grade                VARCHAR(5)    -- AX/AE/A9/A/AM/APQ/B/BB/C/JLT
  wga_dept                 CHAR(3)
  wga_total_qty_kg         DECIMAL(14,3)
  wga_bobbin_count         INT
  wga_last_packing_date    DATE
  wga_synced_at            TIMESTAMPTZ

COMMON_LOT
  cl_id                    BIGSERIAL PK
  cl_lot_no                VARCHAR(30)   NOT NULL UNIQUE
  cl_item_code             VARCHAR(30)
  cl_shade_code            VARCHAR(20)
  cl_erp_grade_code        VARCHAR(5)    -- B / C
  cl_created_at            TIMESTAMPTZ   DEFAULT NOW()

COMMON_LOT_COMPONENT
  clc_id                   BIGSERIAL PK
  clc_common_lot_id        BIGINT        NOT NULL
  clc_original_lot_no      VARCHAR(30)   NOT NULL
  clc_original_shade_code  VARCHAR(20)
  clc_bobbin_count         INT
  clc_qty_kg               DECIMAL(10,3)
```
