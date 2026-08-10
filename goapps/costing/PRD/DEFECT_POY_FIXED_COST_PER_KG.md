# Defect & Fix Spec — POY Fixed Cost Per-Kg Model Mismatch

| | |
|---|---|
| **Versi** | 1.0 |
| **Status** | Draft — untuk review Tech Lead + dev (Ilham) |
| **Author** | Indra (IT Lead) — investigasi costing migration |
| **Last Updated** | 2026-08-10 |
| **Scope** | Yarn costing engine (goapps-backend, service `finance/costcalc`) |
| **Track** | RM → MB → **FG Yarn (POY)** — reconciliation Oracle (MGTAPPS) → Postgres (goapps) |
| **Related** | `mst_formula` seed `000382`/`000408`; migration data `000474`; legacy `PkgFormulaYarn` |

---

## 1. Ringkasan (TL;DR)

Reproses 1 produk POY (`POY 250/48/RND/SD/SIM/NS/1/O`) menghasilkan cost final **~4.2× lebih tinggi** di sistem baru (5.5434) dibanding legacy (1.3206). Root cause: **model fixed cost per-kg POY diimplementasikan dengan arsitektur yang salah**. Legacy memakai **alokasi pool biaya bulanan (spin fixed cost) yang dibagi rata**; sistem baru memakai **bottom-up per-mesin** (`*_PER_DAY / NET_PRODUCTION`). Keduanya konsep berbeda — bukan sekadar salah faktor.

Ini **engine/model defect + master data yang belum dimigrasi** (6 param global spin fixed cost). Efeknya **global ke seluruh produk POY** (param & formula seragam), jadi satu fix + recompute mestinya merekonsiliasi track POY secara serempak.

---

## 2. Kasus Uji & Gejala

| Atribut | Nilai |
|---|---|
| Produk | `POY 250/48/RND/SD/SIM/NS/1/O` |
| Legacy LEFT_NO (valuation, prs `20210800120`) | 83 |
| New `cpc_product_sys_id` | 1558 (`cpm_flex_02 = '83'` ✓) |
| New `cpc_cost_id` / calc_type / period | 245600 / ACTUAL / 202607 |
| **Final cost — legacy** (CYCC TOP 118–122 / DELIVERY_COST_QLTY_LOSS) | **1.3206** |
| **Final cost — baru** (`cpc_cost_per_unit`) | **5.543402** |

Alignment: legacy **valuation (prs120)** ↔ new **ACTUAL** (valuation cost = `cost_val`; SELLING = marketing/`cost_mark`). Perbandingan valid.

---

## 3. Metode Investigasi

Diagnosis memakai disiplin berlapis (sempit → lebar), tanpa asumsi:

1. **Stage legacy** ke Postgres: `CST_YARN_CALCULATION` (long, bawa `CYC_FORMULA_TYPE`) + `CST_YARN_CALCULATION_CUR` (pivoted snapshot). Cross-check CYC↔CYCC untuk produk 83 = **0 mismatch** (snapshot konsisten).
2. **Belah 130 sel** produk 83 by `formula_type`: **10 INPUT** (`Initial_Value`) vs **120 DERIVED**. Input mayoritas 0/trivial → final yang meleset 4× **tidak mungkin** dari input. Fokus ke DERIVED.
3. **Belah DERIVED** jadi GET-DATA (lookup master) vs COMPUTE (formula). Semua `*_PER_DAY` (GET-DATA) **cocok** legacy; yang meledak hanya `*_PER_KG` (COMPUTE).
4. **Sumber nilai per-TOP sistem baru** = `cst_product_cost.cpc_formula_trace` + `cpc_param_snapshot` (JSONB, per `param_code`). Diff per-TOP jadi mungkin tanpa mode trace khusus.

---

## 4. Root Cause

### 4.1 Bukti angka (produk 83)

| Komponen | Legacy (TOP) | Baru | Rasio |
|---|---|---|---|
| POWER_PER_DAY | 1452.0596 (83) | 1452.0596 | 1.00 ✓ (GET-DATA benar) |
| MANPOWER_PER_DAY | 2031.0462 (84) | 2031.0462 | 1.00 ✓ |
| NET_PRODUCTION | 19909.8368 (82) | 1996.8 | ~10× |
| **POWER_PER_KG** | **0.0865 (87)** | **0.7272** | **8.4×** ✗ |
| **MANPOWER_PER_KG** | **0.1201 (88)** | **1.0172** | **8.5×** ✗ |
| **OVERHEAD_PER_KG** | **0.0203 (89)** | **2.4559** | ✗ |
| **SPARES_PER_KG** | **0.0236 (90)** | **0.1768** | ✗ |

Input harian identik; hanya konversi ke per-kg yang meledak → menggelembungkan `TOTAL_FIXEDCOST_PER_KG` (4.377 vs seharusnya ~0.52) → `conversion` (4.505 vs ~0.28) → final (5.5434 vs 1.3206).

### 4.2 Formula legacy (dari `PkgFormulaYarn`, otoritatif)

`fPoyPower_87 / fPoyManPower_88 / fPoyOverheads_89 / fPoyConsSprs_90` — pola identik:

```
*_PER_KG = spin_xxx_month / poy_production * common_poy_denier
                          / ACT_DENIER(TOP 14) * mc_weightage
```

- `POWER_PER_DAY` (TOP 83) dan `NET_PRODUCTION` (TOP 82) **tidak dipakai** untuk per-kg.
- `ACT_DENIER` = legacy TOP 14 (dikonfirmasi: package baca `CYC_top_no = 14`, komentar "13 → 14 sejak 2022-07-12"). **Bukan** TOP 13 / nominal DENIER.
- `mc_weightage` = `CST_MST_MACHINE.CMM_WEIGHTAGE` (per mesin; produk 83 ≈ 1.0).
- Overhead legacy **tidak** memakai `NO_OF_END` (sistem baru keliru menambahkannya).

### 4.3 Param global (legacy `MST_PARAM_DATA`, grup `MST_SPIN_FIXED_COST_4_AVG`)

Sumber: `fGetParamDataVal(MPD_SYS_ID)` → `MST_PARAM_DATA.MPD_VALUE` (current-only, di-maintain Finance/SINTIA).

| var | MPD_SYS_ID | MPD_NAME | MPD_VALUE (202607) |
|---|---|---|---|
| common_poy_denier | 20210900147 | Common POY Denier | 329.712 |
| poy_production | 20210900149 | POY production | 3,027,153 |
| spin_power_month | 20210900151 | Spinning power / month | 198,634 |
| spin_manpower_month | 20210900152 | Spinning manpower / month | 275,561 |
| spin_overheads_month | 20210900153 | Spinning Overheads / month | 46,600 |
| spin_conssprs_month | 20210900154 | Spinning Cons,Spares / month | 54,100 |

### 4.4 Verifikasi (produk 83, mc_weightage ≈ 1, ACT_DENIER = 250)

| per-kg | `spin_month / 3027153 * 329.712 / 250` | legacy | match |
|---|---|---|---|
| POWER | 0.08655 | 0.0865 | ✓ |
| MANPOWER | 0.12007 | 0.1201 | ✓ |
| OVERHEAD | 0.02030 | 0.0203 | ✓ |
| SPARES | 0.02357 | 0.0236 | ✓ |

**Keempat cocok sampai 4 desimal — model + nilai terkunci.**

---

## 5. Klasifikasi & Blast Radius

- **Engine/model defect**: formula `F_YARN_POWER_KG/MANPOWER_KG/OVERHEAD_KG/SPARES_KG` (seed `000382`/`000408`) salah model.
- **Data gap**: 6 param global spin fixed cost belum ada di sistem baru. `mc_weightage` **sudah** ada (`000423`/`000424`) tapi belum di-wire ke scope per-kg.
- **Blast radius**: **seluruh produk POY** (~ribuan) — param global & formula seragam. Fixed cost POY ter-inflasi ~8.4× menyeluruh.

---

## 6. Desain Fix

### Bagian A — Data (SIAP, migration `000474`)

`mst_spin_fixed_cost` (period-scoped, Finance-editable) + seed 6 nilai 202607. Data input, bukan calculated → tidak melanggar input-only rule. **Sudah ditulis** (`000474_create_mst_spin_fixed_cost.up.sql`).

### Bagian B — Engine (keputusan Tech Lead + implementasi Ilham)

Engine bangun scope dari CAPP param per-produk + built-in Go (`marketing_result()`, `mst_rm_cost()`). 6 global + `mc_weightage` belum bisa "dilihat" expression. Dua opsi inject:

| Opsi | Cara | Trade-off |
|---|---|---|
| **B1 (rekomendasi): built-in function** | Tambah `spin_fixed(key, period)` di Go (pola `marketing_result`), baca `mst_spin_fixed_cost`. Tambah param `MC_WEIGHTAGE` lookup-fill dari `mc_weightage`. | Konsisten dgn built-in eksisting; global terpusat 1 sumber; trace eksplisit |
| **B2: global param injection** | Load 6 global sbg param bervalue ke setiap scope produk saat compute + param `MC_WEIGHTAGE`. | Expression paling bersih; scope tiap produk "tercemar" global |

### 6.1 Target expression (B1)

```
F_YARN_POWER_KG =
  ACT_DENIER > 0 && spin_fixed('POY_PRODUCTION',period) > 0
    ? spin_fixed('SPIN_POWER_MONTH',period) / spin_fixed('POY_PRODUCTION',period)
      * spin_fixed('COMMON_POY_DENIER',period) / ACT_DENIER * MC_WEIGHTAGE
    : 0
-- MANPOWER / OVERHEAD / SPARES identik, ganti pool: SPIN_MANPOWER_MONTH /
-- SPIN_OVERHEADS_MONTH / SPIN_CONSSPARES_MONTH.
```

Guard `> 0` menggantikan `nvl(...,0)` legacy (legacy akan error kalau denier/produksi 0; engine baru harus aman).

### 6.2 Yang di-decouple

- Lepas `NET_PRODUCTION`, `*_PER_DAY`, `NO_OF_END` dari jalur per-kg (`formula_param` keempat formula).
- `NET_PRODUCTION` (`F_YARN_NET_PROD`) & `*_PER_DAY` tetap ada untuk display, tapi **tidak lagi** feed fixed cost.

---

## 7. Rencana Eksekusi & Verifikasi

1. **Preflight**: apply `000474`; verify seed (`SELECT * FROM mst_spin_fixed_cost WHERE msfc_period='202607'`).
2. **Bagian B** (dev): implement B1 (atau B2), + param `MC_WEIGHTAGE`.
3. **Reformulasi** 4 `mst_formula` + `formula_param` (migration terpisah — ditulis setelah B1/B2 dipilih).
4. **Dry-run → guarded txn → recompute** produk 83 (`cpc_cost_id` baru).
5. **Verify** trace vs legacy CYCC:

| param | target |
|---|---|
| POWER_PER_KG | 0.0865 |
| MANPOWER_PER_KG | 0.1201 |
| OVERHEAD_PER_KG | 0.0203 |
| SPARES_PER_KG | 0.0236 |
| TOTAL_FIXEDCOST_PER_KG | ~0.522 |
| DELIVERY_COST_QLTY_LOSS (final) | ~1.3206 |

6. **Column-level EXCEPT** 129 TOP produk 83 (trace/snapshot vs `v_stg_cycc_long`) → target 0 mismatch.
7. **Gulung ke seluruh POY** + rollup match/mismatch.

---

## 8. Temuan Sekunder / Laten (catat, prioritas lebih rendah)

- **`NET_PRODUCTION` sendiri masih salah** (baru 1996.8 vs legacy 19909.84, ~10× ≈ NO_OF_END). Setelah refactor per-kg ini **display-only** (tak lagi feed cost) — tapi untuk rekonsiliasi 129 TOP penuh, formula `F_YARN_NET_PROD` perlu diselaraskan terpisah (butuh `CYC_FORMULA_SCRIPT` legacy TOP 82). Pastikan dulu tak dipakai di jalur cost lain.
- **Seed formula inkonsisten**: `000382` pakai input `YARN_DENIER`, `000408` pakai `DENIER` (di produk ini dua-duanya 250 → laten).
- **Doc drift**: `000392` menuliskan spec NET_PRODUCTION beda lagi (`÷(9000×DENIER/1000)`) dari yang dieksekusi — rapikan.
- **Loss formula** pakai `CHANGE_OVER_QLTY_LOSS` (bug tercatat, seharusnya `VOLUME_BUCKET_x_LOSS`). Inert di produk 83 (semua `VOLUME_BUCKET_x_QTY = 0`), tapi tetap salah untuk produk ber-bucket.
- **MC_EFFICIENCY** 97.5 (master baru) vs ~97.2 (implied legacy) — selisih ~0.3%, cek master mesin setelah struktur beres.

---

## 9. Keputusan yang Dibutuhkan

1. **B1 vs B2** — arsitektur inject global/weightage (Tech Lead).
2. **Period-scoping global**: `mst_spin_fixed_cost` per periode (disarankan) — konfirmasi bahwa recompute periode lain nanti butuh nilai historis (legacy `MST_PARAM_DATA` current-only, jadi hanya 202607 yang tersedia sekarang).
3. **Cakupan cyl_type**: apakah pola pool spin ini khusus POY, atau tipe lain (PTY/DTY) punya fungsi `fXxx` sendiri (perlu cek package untuk tipe lain sebelum generalisasi).

---

## Appendix — Referensi Kode

- Legacy formula: `PkgFormulaYarn.fPoyPower_87` (dst) — dispatch dari `PKG_YARN_CALCULATION.pkb` (~baris 2264–2290).
- Global source: `MGTAPPS.fGetParamDataVal` → `MST_PARAM_DATA`.
- New formula seed: `000382_seed_oracle_yarn_formulas.up.sql`, `000408_seed_oracle_formulas.up.sql`.
- New trace source: `cst_product_cost.cpc_formula_trace` / `cpc_param_snapshot`; insert di `cost_result_repository.go` (`insertNewResult`); struktur di `compute.go` (`FormulaEvalTrace`).
- Machine weightage: `mst_machine.mc_weightage` (`000423`/`000424`), lookup reg `000425`.
