# Handoff — POY Yarn Reconciliation: RM Rate Chain (RM V2 → D6 → D5)

| | |
|---|---|
| **Versi** | 1.0 |
| **Status** | Handoff — untuk Ilham (dev/Tech Lead) |
| **Author** | Indra (IT Lead) |
| **Last Updated** | 2026-08-12 |
| **Test case** | `POY 250/48/RND/SD/SIM/NS/1/O` — product_sys_id 1558, RM group 202006078, period 202607, ACTUAL |
| **Artefak terkait** | `ENG-YARN-02_rm_rate_base_binding.md`, `PREFLIGHT_RM_V2_202607.sql`, `000477_*` (applied), `000478_*` (held) |

---

## 1. TL;DR — apa yang aku minta

Reconciliation POY 1558 sudah menutup semua defect **sisi data** yang bisa kukerjakan. Sisa gap ada di **satu critical path engine** yang urutannya kaku:

**RM V2 wiring + RM recompute → D6 (ENG-YARN-02) → apply D5 (000478) → verify 1558.**

Tidak bisa dikerjakan paralel/loncat — tiap langkah mengisi input untuk langkah berikutnya. Detail di §4.

---

## 2. Sudah SELESAI (jangan diulang)

| Item | Aksi | Bukti |
|---|---|---|
| **D4** RM_NORMS | Migration `000477` applied: `F_YARN_RM_NORMS` → `1 + WASTE_PERC - RP_DOZING / 100.0`, + RP_DOZING sebagai formula_param | Recompute 1558 → RM_NORMS = **1.008** (cpc #295780, SUPERSEDED yang lama). ✅ |
| **D2** input drift | cpp refresh ke legacy-202607 (sesi lalu) | RM_NORMS lama 1.00016 (WASTE 0.016) → 1.00008 (WASTE 0.008) terlihat di jejak supersede. ✅ |
| **P3** freight (input landed) | Backfill `cst_rm_group_detail.valuation_freight_rate ← cst_rm_group_head.marketing_freight_rate` untuk 146 grup ber-marketing-freight (16 pilot + 333 rollout = **349 detail**) | Verify: 0 detail NULL tersisa di 146 grup itu. ✅ |
| **D5** formula | Migration `000478` **DITULIS tapi DI-HOLD** (belum apply) — nunggu D6 | Draft siap, banner ⛔ di header. |

**Catatan loop:** edit `mst_formula` → klik **Recalculate** di frontend → baris `cpc` baru (loader baca formula live, tanpa restart). Sudah terbukti end-to-end di D4.

---

## 3. Temuan inti yang mengubah scope (baca sebelum coding)

### 3.1 Base rate (RM_RATE = 1.0193) belum ada di mana pun yang terpakai
`cst_rm_cost` head: `cost_val` (landed) = 1.038193 benar, TAPI semua kolom stage rate base (`cons_rate`/`stores_rate`/`dept_rate`/`po_rate_N`) = **0**. Base hanya ada di `cst_rm_cost_detail.<stage>_rate_based`, dan itu tidak naik ke head karena **RM V2 (CL→SL→FL cascade) belum di-wire di `main.go`**. → **Ini prasyarat D6.** Kalau D6 dikerjakan sekarang, RM_RATE ke-bind ke 0.

### 3.2 Freight: dua input terpisah, track ACTUAL-nya sempat kosong
- `marketing_freight_rate` (di group **HEAD**, migration 000019) → drive **marketing/forecast/selling** (`cost_mark`/`cost_sim`). Terisi (0.01875 utk 202006078).
- `valuation_freight_rate` (di group **DETAIL**, migration 000020) → drive **ACTUAL** (`cost_val`) — yang direkonsiliasi. **NULL semua sebelum hari ini.**
- Bukti kesamaan: legacy ACTUAL landed − base = 1.0381 − 1.0193 = 0.0188 ≈ 0.01875 = marketing freight. Aku sudah backfill ACTUAL dari marketing (§2). **Finance perlu konfirmasi kesamaan ini berlaku umum**, bukan cuma 202006078.
- Backfill valuation-input sebelumnya **setengah jalan**: `valuation_duty_pct` (516) & `valuation_transport_rate` (520) terisi, `valuation_freight_rate` & `valuation_anti_dumping_pct` kelewat.

### 3.3 Grain tidak konsisten (arsitektur — mohon pertimbangkan)
Marketing freight per-**grup** (head), valuation freight per-**item** (detail). Kalau freight inheren per-grup, mungkin lebih tepat valuation freight juga di head, atau detail sistematis inherit dari head. Sekarang aku isi per-detail dari nilai head.

### 3.4 166 grup tanpa marketing freight = track MB/pigment, BUKAN blocker POY
Grup tanpa freight isinya masterbatch/pigment/dye/chemical/PE-colorant (bukan RM polyester chip). Ini urusan **track MB**, bukan RM chip reconciliation. Biarkan `valuation_freight_rate` NULL (freight 0) sampai track MB digarap. Bukan "data entry gap" untuk POY.

### 3.5 Inkonsistensi FL yang perlu kamu cek
Output 202607 punya **158 grup `flag_valuation_used = FL`**, tapi `cst_rm_cost_detail.fix_rate` (FL manual input) **semua 0/NULL** untuk 202607. FL resolve tanpa fix_rate itu janggal — cek logika populate. FL 202607 juga belum di-backfill (P4).

---

## 4. Action items (urut, tidak bisa loncat)

### Langkah 1 — RM V2 wiring + RM recompute  ⬅️ CRITICAL PATH, mulai di sini
- Wire handler V2 (`CalculateHandlerV2` / `execute_handler_v2`) di `main.go`, lalu RM recompute period 202607.
- **GATE dulu dengan `PREFLIGHT_RM_V2_202607.sql`** (7 check). Yang sudah kuverifikasi:
  - P0/P5: staging `cst_item_cons_stk_po` 202607 loaded (3876 rows, ≈ 202606). ✅
  - P2 coverage: **3 item** tanpa staging → akan landed 0: `MBC0000116`, `MBC0000302`, `PIG0000041`. Putuskan load / non-aktifkan.
  - P3 freight: sudah kubackfill (§2). ✅ untuk track RM chip.
  - P4: fix_rate 202607 = 0 (tak ada risiko wipe, tapi FL backfill pending — lihat §3.5).
- **Hasil yang diharapkan:** head stage rate base (`cons_rate`/`stores_rate`/`dept_rate`) terisi (sekarang 0). Ini yang membuka D6.
- Catatan sumber: `cst_item_cons_stk_po` dari legacy `PRC_CST_CONSSTKPO_MGT` — TED per-line PO correction masih pending Finance; konfirmasi staging versi terkoreksi.

### Langkah 2 — D6 (ENG-YARN-02): RM_RATE bind ke base
Spec lengkap di `ENG-YARN-02_rm_rate_base_binding.md`. Ringkas:
- `LoadRMCosts` return **dua** nilai (base + landed), bukan satu `cost_val`.
- `F_YARN_RM_RATE` (RM_LOOKUP) alias **base**; `F_YARN_RM_LANDED` **berhenti** pass-through `RM_RATE`, di-source independen dari **landed** (`cost_val`).
- **Open question (butuh kamu konfirmasi ke logika populate V2):** mapping `flag_valuation_used` (CL/SL/FL) → kolom head base mana. Ada mismatch penamaan head↔detail (head: cons/stores/dept/po; detail: cons/stock/po/fix), dan **FL tidak punya kolom head yang jelas** — ini yang paling perlu diselesaikan.

### Langkah 3 — Apply D5 (`000478`)
- Setelah D6 merge. `F_YARN_WASTE_LESS_MB_OPU` → `RM_NORMS * RM_LANDED_COST - RM_RATE` (+ tukar formula_params ke RM_NORMS/RM_LANDED_COST/RM_RATE).
- Jangan apply sebelum D6, atau hasilnya 0.0083 (RM_RATE masih = landed). File punya banner ⛔ + preflight.

### Langkah 4 — Verify 1558
Recompute yarn 1558 → EXCEPT vs `v_stg_cycc_long`:
- RM_NORMS = **1.008** (sudah ✅)
- RM_RATE = **1.0193** (base)
- RM_LANDED_COST = **1.0381** (landed)
- WASTE_LESS_MB_OPU = **0.0271**

---

## 5. Defect engine lain yang masih di pihak kamu (di luar critical path ini)

| Defect | Inti | Sifat |
|---|---|---|
| **D1** fixed cost per-kg | `000476` (spin pool) sudah ada — verify hasilnya | Sebagian besar done, verify |
| **D3** pack `_VAL` | Engine baca kolom base, harusnya `_VAL` (valuation). Utk POY-CAP-E/DEL-C `_VAL` kosong | Engine + **keputusan Finance** (`_VAL` kosong = pack ~0?) |
| **D7** CAP/DEL_CONVERSION | RM_LOOKUP → engine ignore expression, alias `totalRM` (balikin RM cost). Plus gap C10/C11: CAP pakai `COST_CAP_FINAL`, DEL pakai `COST_DEL_FINAL`, engine cuma bawa satu | Engine (bukan data — UPDATE expression zero-effect) |
| **D8** sekunder | `NET_BOB_WT` (butuh param persen grade), `CONV_FACTOR` (butuh `sqrt()` di evaluator; POY=0 jadi inert), `OIL_GAIN` (ubah derived→input) | Campuran; masing-masing keputusan sendiri |

---

## 6. Keputusan yang butuh diketok

1. **Finance** — valuation freight = marketing freight berlaku umum (untuk rollout 146 grup sudah kuisi; konfirmasi retro)? Dan D3 `_VAL` kosong = pack valuation ~0 benar?
2. **Ilham (Tech Lead)** — D6 §5 mapping flag→kolom head base (khususnya FL); D1 inject global B1 vs B2; governance input as-of-period (legacy current-only).
3. **Arsitektur** — grain freight per-grup vs per-item (§3.3).

---

## 7. Referensi
- Register defect lengkap: `DEFECT_REGISTER_POY_YARN_202607.md` (D1–D10).
- Engine: `costcalc/loader.go` (`LoadRMCosts`, `LoadFormulas`), `compute.go` (`FormulaTypeRMLookup` switch ~369, `inputHash` ~804, `aggregateRMCost`), `formula_repository.go`.
- RM V2: `rmcost/calculate_handler_v2.go`, `execute_handler_v2.go`, `syncdata_v2_source.go` (baca `cst_item_cons_stk_po`, stock=stores+dept, PO=last_po_1).
- Recompute yarn: gRPC `finance.v1.CostCalcService/TriggerCalcJob` (SINGLE_PRODUCT) — atau tombol Recalculate frontend.
