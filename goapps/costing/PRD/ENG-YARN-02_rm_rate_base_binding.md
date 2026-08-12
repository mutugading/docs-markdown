# ENG-YARN-02 — RM_RATE binds to landed cost instead of base stage rate (D6)

| | |
|---|---|
| **Versi** | 1.0 |
| **Status** | Draft — untuk Ilham (dev/Tech Lead). Berisi 1 OPEN QUESTION yang butuh konfirmasi sebelum implementasi. |
| **Author** | Indra (IT Lead) |
| **Last Updated** | 2026-08-12 |
| **Defect** | D6 (Defect Register POY Yarn 202607) |
| **Classification** | ENGINE (Go) — bukan data. Tidak ada `UPDATE`/migration yang menyelesaikan ini. |
| **Test case** | `POY 250/48/RND/SD/SIM/NS/1/O` — product_sys_id 1558, period 202607, ACTUAL |
| **Cost-bearing?** | YA — feed `WASTE_LESS_MB_OPU` (D5). Bukan display. |
| **⛔ BLOCKED BY** | **RM V2 handler wiring + RM recompute.** Lihat §0. Jangan mulai D6 sebelum ini beres. |

---

## 0. ⛔ HARD PREREQUISITE — RM V2 head base belum terisi

Diverifikasi di data 202607 (RM group `202006078`, input 1558):

- Head `cst_rm_cost`: `cost_val` = 1.038193 (landed, benar) TAPI **semua kolom base stage rate (`cons_rate`/`stores_rate`/`dept_rate`/`po_rate_N`) = 0**.
- Detail `cst_rm_cost_detail`: base ADA sebagai `<stage>_rate_based`; `landed = rate_based + freight_rate` (freight 0.01875). Base head seharusnya = agregat `_rate_based`, tapi jalur populate sekarang (V1) tidak mengisinya → tetap 0.
- Verify konsep: `cost_val − freight` = `1.038193 − 0.01875` ≈ **1.0194** ≈ legacy base 1.0193. ✓

**Artinya:** base rate yang dibutuhkan RM_RATE **belum tersimpan di head**. Kalau D6 di-bind ke kolom head base sekarang, hasilnya **0** (lebih buruk dari kondisi sekarang yang landed).

Urutan wajib:
1. Wire **RM V2 handler** (CL→SL→FL cascade) di `main.go` + RM recompute → head base stage rates (`cons_rate`/`stores_rate`/`dept_rate`) terisi.
2. Konfirmasi §5 (mapping `flag_valuation_used` → kolom head base).
3. Baru implement D6 (§4).

Jangan lanjut ke §4 sebelum §0.1 dan §0.2 selesai.

---

## 1. Ringkasan

`RM_RATE` (cost sheet row 6) dan `RM_LANDED_COST` (row 7) saat ini **bernilai sama = landed cost** (`cost_val`). Legacy membedakan keduanya:

- **RM_RATE** = stage rate **base** (pre-FL/pre-landed) → legacy 1558 = **1.0193**
- **RM_LANDED_COST** = landed → legacy 1558 = **1.0381**
- Selisih ~0.0188 = komponen FL/landed adder.

Karena engine hanya menarik satu nilai (`cost_val`) dan memakainya untuk keduanya, RM_RATE salah (kelebihan komponen landed).

---

## 2. Perilaku saat ini (dengan referensi kode)

**a. `F_YARN_RM_RATE`** — `mst_formula`, type `RM_LOOKUP` (seed 000408):
> engine mengabaikan expression DSL, meng-alias `totalRM` (lihat `compute.go` switch `FormulaTypeRMLookup`, ~baris 369–402).

**b. `totalRM`** dibangun di `aggregateRMCost` / `resolveRMUnitCost` (`compute.go` ~463 & ~507), yang untuk GROUP-type RM memakai nilai dari `LoadRMCosts`.

**c. `LoadRMCosts`** (`loader.go` ~827) hanya me-return **satu** kolom landed:
```sql
CASE $3
    WHEN 'ACTUAL'   THEN COALESCE(cost_val,  0)
    WHEN 'FORECAST' THEN COALESCE(cost_mark, 0)
    WHEN 'SELLING'  THEN COALESCE(cost_sim,  0)
    ELSE COALESCE(cost_val, 0)
END
FROM cst_rm_cost ...
```
→ tidak pernah membaca stage rate base (`cons_rate`/`stores_rate`/`dept_rate`/`po_rate_N`).

**d. `F_YARN_RM_LANDED`** — `mst_formula`, type `CALCULATION`, expression harfiah **`RM_RATE`** (pass-through). Artinya `RM_LANDED_COST := RM_RATE`.

**Konsekuensi:** RM_RATE = `totalRM` = `cost_val` (landed); RM_LANDED = RM_RATE = landed. Keduanya kolaps ke landed.

---

## 3. Perilaku yang benar (legacy) + verify

- `RM_RATE` = base stage rate terpilih (pre-FL) = **1.0193**
- `RM_LANDED_COST` = `cost_val` (landed) = **1.0381**
- Downstream D5: `WASTE_LESS_MB_OPU = RM_NORMS × RM_LANDED_COST − RM_RATE`
  = `1.008 × 1.0381 − 1.0193` = **0.0271** ✓

---

## 4. Fix (tiga bagian — semua Go/engine kecuali 4.3)

**4.1 — `LoadRMCosts` return dua nilai, bukan satu.**
Tambahkan kolom base stage rate ke SELECT dan bawa keduanya (base + landed) ke pemanggil. Mis. return `map[string]struct{ Base, Landed float64 }` atau dua map. `resolveRMUnitCost`/`aggregateRMCost` menyesuaikan agar bisa menghasilkan dua agregat.

**4.2 — RM_RATE alias BASE; sediakan LANDED terpisah.**
`totalRM` untuk RM_RATE (RM_LOOKUP) = agregat **base**. Nilai **landed** disediakan sebagai sumber terpisah untuk RM_LANDED (lihat 4.3).

**4.3 — `F_YARN_RM_LANDED` tidak lagi pass-through `RM_RATE`.**
Karena RM_RATE kini = base, RM_LANDED tidak boleh `= RM_RATE`. Dua opsi (pilih bersama Ilham):
- (A) Ubah `F_YARN_RM_LANDED` jadi `RM_LOOKUP` yang di-alias ke agregat **landed** (analog RM_RATE) → butuh case baru di switch `compute.go`. Ini perubahan formula_type (migration `mst_formula`) + engine.
- (B) Engine inject landed sebagai scope var (mis. `RM_LANDED_RAW`) dan `F_YARN_RM_LANDED` (tetap CALCULATION) = `RM_LANDED_RAW`. Migration `mst_formula` ringan + engine inject.

> Catatan: PRODUCT-type RM (upstream hand-off via `LoadUpstreamCosts` → `COST_CAP_FINAL`) tidak punya konsep base-vs-landed yang sama. Konfirmasi apakah split base/landed hanya berlaku untuk GROUP/ITEM-type RM, dan PRODUCT-type tetap satu nilai. Kemungkinan besar YA (base/landed adalah konsep stage rate `cst_rm_cost`, bukan captive hand-off).

---

## 5. Mapping `flag_valuation_used` → kolom head base (SEBAGIAN teridentifikasi)

Sumber base = kolom head stage rate yang sesuai stage hasil cascade (`flag_valuation_used`), yang akan terisi setelah §0. Nilai `flag_valuation_used` yang teramati di 202607: **CL** (75), **SL** (117), **FL** (158) = Consumption/Stock/Fix-Landed.

**Ketidakcocokan penamaan head ↔ detail yang HARUS Ilham reconcile:**
- Detail stages: `cons_*`, `stock_*`, `po_*`, `fix_*`
- Head stages: `cons_rate`, `stores_rate`, `dept_rate`, `po_rate_1/2/3` (tidak ada kolom head `fix_*`)

Dugaan mapping (konfirmasi ke kode populate V2):
- `CL` (Consumption-Landed) → head `cons_rate` (base = detail `cons_rate_based` teragregasi)
- `SL` (Stock-Landed) → head `stores_rate`? (detail `stock_rate_based`)
- `FL` (Fix-Landed, input manual Finance) → **tidak ada kolom head yang jelas** — kemungkinan `dept_rate` atau butuh kolom baru. INI YANG PALING PERLU DIKONFIRMASI.

Base = `<stage>_landed − freight_rate` secara konsep (di 1558: `1.038193 − 0.01875 ≈ 1.0193`), tapi ambil dari kolom base teragregasi yang diisi V2, **bukan** dihitung ulang di yarn loader (hindari duplikasi logika cascade + qty-weighting ke layer yarn).

---

## 6. Test & verify

1. Unit test `LoadRMCosts`: return base + landed dua-duanya untuk satu RM fixture (ACTUAL/FORECAST/SELLING).
2. Unit test `aggregateRMCost`: agregat base vs landed benar untuk multi-RM route.
3. Integration/pilot 1558 (202607/ACTUAL) → recalculate → EXCEPT vs `v_stg_cycc_long`:
   - `RM_RATE` (TOP 55) = **1.0193**
   - `RM_LANDED_COST` (TOP ~56) = **1.0381**
   - `WASTE_LESS_MB_OPU` (TOP 58) = **0.0271** (unblock D5 verify)
4. Regression: pastikan RM cost total & downstream captive/delivery tidak bergeser tak terduga untuk produk yang base=landed (RM tanpa FL adder).

---

## 7. Dependencies

- **Unblocks D5** (`F_YARN_WASTE_LESS_MB_OPU`): formula D5 baru bisa di-verify setelah RM_RATE=base tersedia. D5 expression boleh ditulis paralel (`RM_NORMS * RM_LANDED_COST - RM_RATE`) tapi jangan divalidasi sebelum ENG-YARN-02 merge.
- **Tidak bergantung** ke D3 (pack `_VAL`) maupun D1 (spin pool). Bisa dikerjakan independen.
- Bersinggungan dengan RM cost workstream (populasi `cst_rm_cost` stage rates). Konfirmasi §5 kemungkinan butuh input dari sisi RM calc.
