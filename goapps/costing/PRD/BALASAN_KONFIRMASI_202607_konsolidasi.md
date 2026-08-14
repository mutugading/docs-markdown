# Balasan Konsolidasi — Costing 202607 (KONFIRMASI + Finance + Recompute)

| | |
|---|---|
| **Dari** | Indra (IT Lead) |
| **Kepada** | Tim Dev (Ilham) |
| **Tanggal** | 14 Agustus 2026 |
| **Menggantikan** | `BALASAN_KONFIRMASI_202607.md` (rev 1) — dokumen ini yang berlaku |
| **Cakupan** | Jawaban KONFIRMASI rev 2 (T-1…T-9), jawaban Finance (F-1…F-8), eksekusi & verifikasi de-dup, blocker recompute aktif, spec menyusul (F-7, F-4) |

---

## 0. Ringkasan eksekutif

1. **ENG-MB-03 (order-of-operations) ditarik** untuk diagnosisnya — terbukti menarget dead code; jalur hidup V2 sudah benar. **Tidak ada perubahan engine calc yang diperlukan.**
2. Akar mismatch 202607 = **data defect double-count** (`valuation_freight_rate` **dan** `valuation_transport_rate` dua-duanya berisi charge legacy yang sama). Bukan engine, bukan urutan operasi.
3. **Fix sudah dieksekusi (data-only): de-dup** — `valuation_freight_rate → 0` untuk 335 baris / 141 grup, + 4 baris NULL-duty → 0. **Sudah COMMIT & terverifikasi** (202007214 = 8.3689; produk 1558 = 1.038193).
4. **`000479` (kolom baru `valuation_legacy_charge_rate` + reader) DIBATALKAN** — tidak diperlukan; V2 sudah baca `valuation_transport_rate` di posisi post-duty.
5. **Blocker aktif sekarang:** recompute group rate RM **tidak cascade** ke `cst_product_cost` — produk belum dihitung ulang. Butuh mekanisme recompute level produk dari dev. **(Bagian 4.)**
6. **Menyusul sebagai spec terpisah** (bukan cycle ini): **F-7** (3 calc type + FL ke header + SELLING-zero) dan **F-4** (harga PO → base-rate pipeline landed). **(Bagian 5.)**

---

## 1. Jawaban KONFIRMASI rev 2 (Bagian B — teknis)

### T-1 — Hand-apply migrasi terlewat
Setuju, **langsung dijalankan tanpa jendela maintenance** (fase opening-balance/rekonsiliasi, belum ada trafik produksi yang perlu dilindungi). Himpunan hilang terverifikasi = **{000468, 000469, 000470}** + `000477` (manual out-of-band). `000464` & `000465` sempat commit out-of-order **tetapi probe efek membuktikan sudah ter-apply** — JANGAN di-apply ulang. Urutan `468 → 470` (470 repair 468), `469` bebas; `468` bungkus transaksi manual; `471`/`473` & tracker jangan disentuh.

### T-2 — Migrasi manual out-of-band lain?
**Bukan hanya 477.** Mekanisme high-water-mark yang membuat 468-470 terlewat **berulang 3×** (464, 465, 468-470 semua commit out-of-order). Namun setelah probe efek, daftar yang **benar-benar hilang** = **{468, 469, 470} + 477**; 464 & 465 ter-apply. Karena golang-migrate hanya simpan **satu integer high-water mark**, `schema_migrations_finance` tak bisa dipercaya sendiri — verifikasi via efek/marker. **Setuju rekomendasi: tambah CI check monotonicity nomor migrasi.**

### T-3 — Staging kena gejala sama?
**Tidak.** Staging bersih dari gejala skipped-migration.

### T-4 — Owner scope PO_2/PO_3
Setuju **PR-only** (tiga tier literal menyelamatkan 0 baris tambahan). **Eksekusi ditahan sampai F-4 dijawab** — lihat Bagian 2 & 5.

### T-5 — ENG-MB-03 menarget dead code
**Setuju, diagnosisnya ditarik.** Verifikasi repo: `CalculateCost` nol pemanggil produksi; `calculate_handler.go` sudah dihapus (`78f631b`); jalur hidup V2 (`calculate_handler_v2.go` → `ComputeDetail` → `AggregateGroupTotals`). **Dua aset ENG-MB-03 diselamatkan:** (a) ground-truth legacy per-item (8.1370 & 8.3974) — menguatkan F-1/F-6; (b) temuan anggota grup hilang — masuk F-8 (ditutup, lihat Bagian 2).

### T-6 — V2 sudah ekuivalen; AC1 lulus tanpa perubahan kode?
**Setuju sebagian, satu koreksi:**
- ✅ **Tidak perlu perubahan engine.** `AggregateGroupTotals` sudah landed-per-item→weighted-average; komponen per-kg seragam → urutan operasi no-op.
- ❌ **AC1 TIDAK lulus di data produksi (saat itu).** Penyebab **data** (double-count), bukan engine. AC1 lulus **setelah** de-dup + recompute — dan memang **sekarang sudah lulus di level group rate & produk 1558** (lihat Bagian 3), tinggal produk MB lain menunggu recompute (Bagian 4).

### T-7 — Angka 7.4779: sumber & reproduksi
**Nyata di produksi & reproducible** — `cst_rm_cost`, flag **CL** (bukan cascade SL/FL). Bukan order-of-operations, bukan engine. Ia adalah nilai **stale** dari state saat komponen antar-item belum seragam (double-count di sebagian item + item dominan belum lengkap valuation-nya saat rate dihitung); begitu semua item double-count, recompute atas input **saat ini** menghasilkan 9.2139. Setelah de-dup: 8.3689. Ringkasnya, tiga state konsisten:

| State input | CL 202007214 |
|---|---|
| Benar (freight 0, transport=charge 0.8125, duty 0.04) | **8.3689** (legacy) |
| Double-count seragam (freight+transport 0.8125) | 9.2139 |
| Stale (tersimpan pra-fix) | 7.4779 |

### T-8 — Premis: per-item vs satu charge per grup
**Konfirmasi premis satu charge gabungan per grup, POST-duty.** Diverifikasi dari legacy Oracle (202007214): charge 0.8125/kg **seragam** di semua grade (termasuk qty=0) → atribut grup; `implied_charge_post_duty = 0.8125` bersih, `pre_duty = 0.7813` kotor → **post-duty** (rumah = suku transport), bukan freight (pre-duty). Premis "per item" di ENG-MB-03 ditarik.

**PERUBAHAN RENCANA PENTING:** `valuation_legacy_charge_rate` (field baru) + reader **DIBATALKAN**. Fix cukup **data-only**: nol-kan `valuation_freight_rate` (yang diduplikat backfill), charge tetap di `valuation_transport_rate` yang **sudah** dibaca V2 di posisi post-duty. Tidak ada DDL, tidak ada reader baru. *(Catatan: "di mana idealnya fixed charge disimpan" masuk ke redesign F-7 — Bagian 5 — bukan cycle ini.)*

### T-9 — Cabut freeze recalc
**Freeze dicabut.** Guard "reader-sebelum-data" **gugur** (tak ada reader/field baru). Namun muncul isu urutan baru: **recompute group rate tidak cascade ke produk** — lihat Bagian 4.

### U-3 — Kolom snapshot input di tabel detail hasil
Ranah kalian. **Steer: bernilai tinggi** — insiden 7.4779 (input berubah setelah engine jalan) persis yang akan tertangkap snapshot input-at-compute-time. Putuskan sebelum recompute massal 202607.

### U-P — Packaging migrasi
Ranah kalian. Karena `000479` batal, ini sebagian besar moot untuk cycle ini. Prinsip tetap: urutan apply ditegakkan lewat runbook, bukan penomoran file.

---

## 2. Jawaban Finance (Bagian A — F-1…F-8)

| # | Jawaban Finance | Status / dampak |
|---|---|---|
| **F-1 / F-6** | Biaya tambahan **tidak dikenai bea masuk**; duty dari original rate | ✅ **Post-duty dikonfirmasi.** Mengunci desain: charge di suku transport (post-duty), freight = 0. Konsisten dengan de-dup. |
| **F-2** | Legacy 202607 **final, dipakai stock valuation & dispatch margin, sudah dilapor**; new system menyamakan ke legacy | ✅ **Ini rekonsiliasi, BUKAN restatement.** Legacy tetap book-of-record. Recompute **aman**, tanpa dampak bisnis. Menutup kekhawatiran restatement (mempengaruhi T-4/T-9). |
| **F-3** | POY 250/48 (produk 1558): consumption 1.019443 + freight 0.01875; acuan legacy | ✅ **Terverifikasi pasca-fix**: `1.019443 + 0.01875 = 1.038193` = hasil de-dup. Double-count = +0.01875 lagi (~1.0572). |
| **F-4** | Harga PO dari ETL Orion = **harga − diskon (RAW)**; untuk RM cost harus **consider biaya tambahan** (freight/duty/anti/transport) di modul | ⚠️ **Requirement engine baru.** Fallback PO = **base rate → pipeline landed**, bukan raw ke kolom landed. **Spec terpisah — Bagian 5.** (Menutup T-4: baru eksekusi setelah ini.) |
| **F-5** | Nilai 60 **wajar** (jenis bahan beragam) | ✅ Ditutup — bukan masalah satuan. |
| **F-7** | **3 calc type:** ACTUAL per-**item**; SELLING & PROJECTION per-**grup**. **Fixed rate (FL/FP) harus di group HEADER per periode.** FL untuk actual **saat ini salah di item level** → perlu koreksi | ⚠️ **Redesign besar.** Juga menjelaskan **SELLING-zero**. Terverifikasi di schema (valuation FL = `ValuationDefaultValue` per detail; marketing FP di header). **Spec terpisah — Bagian 5.** Keputusan IT Lead: **de-dup dulu, F-7 menyusul.** |
| **F-8** | Tidak masalah — inkonsistensi grup RM legacy diterima | ✅ Ditutup — anggota hilang jadi known-gap, tak dilengkapi. |

---

## 3. Yang SUDAH dieksekusi & terverifikasi (de-dup data-only)

**Data fix (COMMIT):**
- `UPDATE cst_rm_group_detail SET valuation_freight_rate = 0` — **335 baris / 141 grup** (signature `freight = transport > 0`, tag `dedup-freight-202607`). Signature-based agar menjangkau baris manual-insert tak bertag (DYE0000057) & admin/indraputro.
- `UPDATE ... SET valuation_duty_pct = 0` — **4 baris** NULL-duty (justified: sibling grade item sama = 0.00).

**Verifikasi (lolos di 2 level):**
- Group rate **202007214** → `cost_val = 8.3689`, flag CL = **cocok legacy** ✓
- Produk **1558** (POY 250/48, SINGLE_PRODUCT) → RM `1.038193` = **cocok acuan Finance F-3** ✓

Kesimpulan: engine benar, data benar. Sisa hanya **recompute produk** (Bagian 4).

---

## 4. ⛔ BLOCKER AKTIF — Recompute RM group tidak cascade ke product cost

**Butuh dev sekarang.** Setelah klik "Recalculate raw material" di frontend:

| Cek | Hasil |
|---|---|
| `cst_rm_cost` (group rate), 141 grup ter-de-dup | **335/335 recompute pasca-fix**, `calculated_at = 2026-08-14 06:59 UTC` ✓ |
| `cst_product_cost` ACTUAL 202607, type 29 | **0 versi baru**; `cpc_calculated_at` terbaru `2026-08-12 09:04 UTC` (pra-fix) |
| Rollup MB | byte-identik baseline (match_ok 631) — produk belum dihitung ulang |

**Diagnosis (dari kode):** RM group recalc meng-update `cst_rm_cost` tapi **tidak fan-out** ke produk. `CalcJobScope = {ALL, FILTERED, SINGLE_PRODUCT, SINGLE_ROUTE}`; `SINGLE_PRODUCT` inline, scope ALL/FILTERED lewat orchestrator S8c (`trigger_handler.go`). RM recalc ≠ product calc job.

**Pertanyaan/ask ke dev:**
1. Cara memicu recompute level produk 202607 — `CALC_JOB_SCOPE_ALL`/`FILTERED` dari UI, atau orchestrator/API? (kalau API: endpoint + contoh payload).
2. Seharusnya group rate berubah → cascade otomatis ke produk dependan? Kalau manual, apa prosedur bakunya agar tak terlewat (pola staleness berulang — layak dijadikan guard).
3. Untuk 202607: `FILTERED` (produk konsumsi 141 grup) atau `ALL`? Aku bisa sediakan daftar grup/produk kalau FILTERED butuh input eksplisit. **202606 jangan** ikut (register final).

---

## 5. Spec MENYUSUL (terpisah dari cycle de-dup)

### Spec F-7 — Tiga calc type + fixed rate ke header + SELLING-zero *(prioritas tinggi)*
- **ACTUAL/valuation:** freight/duty/anti/transport **per item** (model detail sudah benar).
- **SELLING & PROJECTION:** komponen **per grup** (header). ← ini yang membuat **SELLING resolve ke 0** (rate grup + FP belum ter-wire).
- **Fixed rate (FL actual, FP selling/projection): pindah ke GROUP HEADER per periode.** FP sudah di header (benar); **FL masih di item level** (`ValuationDefaultValue` per detail) → defect yang diminta Finance dikoreksi. Sisi valuation belum punya slot fixed-rate header per-periode → gap yang harus dibuat.
- Butuh: kolom/tabel header-level per-periode untuk FL valuation + wiring reader V2 + fix SELLING/PROJECTION path.

### Spec F-4 — Fallback harga PO ke pipeline landed *(setelah F-7 atau paralel)*
- Harga PO (Orion ETL) = **raw (harga − diskon)**. Harus masuk sebagai **base rate** lalu modul menerapkan freight/duty/anti/transport — **bukan** raw langsung ke kolom landed (akan undervalue diam-diam).
- 202607: 158 baris tanpa harga; ~100 tertolong PO. Karena kartu diarsip per-versi, salah taruh = restatement → wiring harus benar sejak awal.
- Terkait T-4 (scope PR-only) — eksekusi keduanya setelah spec ini fix.

---

## 6. Yang aku butuh dari dev (ringkas)

| Prioritas | Item | Ref |
|---|---|---|
| 🔴 Sekarang | Mekanisme recompute produk 202607 (cascade / scope ALL-FILTERED) | Bagian 4 |
| 🟡 Berikutnya | Spec F-7 (3 calc type + FL header + SELLING-zero) | Bagian 5 |
| 🟡 Berikutnya | Spec F-4 (PO fallback → pipeline landed) + scope PO_2/PO_3 (T-4) | Bagian 5 |
| 🟢 Housekeeping | CI check monotonicity migrasi (T-2); snapshot input di detail hasil (U-3) | Bagian 1 |

**Yang TIDAK diperlukan lagi:** `000479` (kolom `valuation_legacy_charge_rate` + reader) — dibatalkan; double-count sudah beres via de-dup data-only.
