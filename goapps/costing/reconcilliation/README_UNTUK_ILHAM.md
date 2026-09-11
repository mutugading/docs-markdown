# Paket Serah-Terima Defect Engine — Costing FG/Yarn, Periode 202608

**Dari:** Indra (IT Lead) · **Untuk:** Ilham (Engineering) · **Tanggal:** 2026-09-10
**Basis data:** `cst_product_cost` periode 202608, `cpc_version = 3`, ACTUAL,
hasil reproses 2026-09-10 16:07 (MB) dan 16:19 (yarn/FG).

> Semua angka di dokumen ini **diukur ulang setelah reproses**, bukan warisan
> laporan 9 September. Defect-nya utuh, angkanya identik — jadi laporan lama
> masih berlaku.

---

## Konteks singkat

Remediasi data sisi MB sudah dijalankan dan tuntas (8 paket, lihat
`remediation/EXECUTION_LOG_202608.md`). Efeknya: **678 produk MB naik total
+609,74 satuan cost**. Efek ke FG/yarn: **NOL, tepat 0,0000 pada 13.422
produk** — dibandingkan v2 vs v3 untuk `total_cost`, `total_rm_cost`,
`total_conversion`, `cpc_param_snapshot`, `cpc_rm_cost_detail`,
`cpc_cost_by_level`, dan kolom packing.

Artinya sisa selisih legacy vs sistem baru di FG **bukan masalah data** dan
tidak bisa ditutup lewat SQL. Dua defect di bawah ini yang memegangnya.

---

## E-1 · `ENG-INTERMEDIATE-PROP` — cost produk antara tidak dipropagasi 🔴 P0

### Gejala
Pada baris `cpc_rm_cost_detail` bertipe `PRODUCT`, engine memakai
`unit_cost = 1` alih-alih cost produk hulu, padahal cost hulunya **ada**.

### Temuan baru yang paling menentukan: jalur MB SUDAH BENAR

| Jalur | Baris input `PRODUCT` | `unit_cost = 1` | Wajar | % defect |
|---|---:|---:|---:|---:|
| **MB → MB (nested MB)** | 349 | **0** | 349 | **0,0%** |
| **YARN/FG (POY→PTY→TTY dst.)** | 9.431 | **8.718** | 713 | **92,4%** |

Jalur MB me-resolve cost hulu dengan benar (rata-rata `unit_cost` 11,93).
**Jadi ada implementasi yang bekerja di repo ini** — bandingkan kedua code
path-nya, jangan mulai dari nol.

### Dampak terukur

| Metrik | Nilai |
|---|---:|
| Baris route terdampak | **8.718** |
| Produk FG terdampak | **8.386** |
| Produk hulu yang cost-nya diabaikan | **5.206** |
| Rata-rata cost hulu yang tersedia tapi tidak dipakai | **2,4092** |
| **Understatement total** | **12.285,76** satuan cost |

### Fakta yang mempersempit pencarian

1. **Bukan data hilang.** 100% produk input punya baris cost 202608 dengan
   `cpc_status = 'CALCULATED'` dan `cpc_cost_per_unit` terisi (rentang
   1,02–4,20 untuk yang gagal). Nilainya ada, hanya tidak diambil.
2. **Bukan bergantung parent.** Tidak ada satupun produk input yang
   ter-resolve benar di satu parent dan jadi 1 di parent lain — nol kasus.
3. **Melekat pada produk INPUT, biner sempurna.** Dari 5.720 produk input
   distinct: **5.206 selalu `unit_cost=1`**, **514 selalu benar**, **0 campuran**.
   → cari atribut yang membedakan kedua himpunan ini. Ini kunci defect-nya.
4. **Bukan `route_level`.** Semua baris `route_level = 1`.
5. **Bukan tipe produk.** Setiap tipe input (POY, PTY, TTY, TCY, IDY, …) punya
   anggota di kedua kelompok. Contoh POY: 6.062 gagal / 31 benar.

### Bukti terlampir
- `E1_unit_cost_1_evidence_202608.csv` — 8.718 baris: produk FG, produk input,
  ratio, `unit_cost` yang dipakai engine, `unit_cost` yang seharusnya, dan
  understatement per baris. Diurutkan dari dampak terbesar.
- `E1_input_product_classification.csv` — 5.720 produk input dengan label
  GAGAL/OK. **Mulai dari sini**: diff atribut antara 5.206 GAGAL dan 514 OK.

### Repro tercepat
Produk FG `CSTPTY2606006084` (sys_id 34149) mengonsumsi POY
`CSTPOY2606003900` (sys_id 27257) dengan ratio 1. Engine memakai
`unit_cost = 1`; nilai yang tersedia `4,202778`. Selisih satu baris ini saja
3,202778.

---

## E-2 · `ENG-PACK-DOUBLE` — packing cost ~2× legacy 🔴 P0

349 dari 349 kasus lebih tinggi dari legacy, median rasio **1,975**
(p25 1,957 / p75 1,991). Konsistensi 349/349 dan rasio yang mepet 2,0 adalah
signature double-count, bukan beda parameter.

Titik periksa: `F_YARN_CAP_PACK` / `F_YARN_DEL_PACK` — cek
`CAPTIVE_NO_OF_BOB`, `CAPTIVE_BOX_WT`, `DELIVERY_*`.

Kolom packing **tidak berubah sama sekali** antara v2 dan v3 (0 dari 17.611
produk), jadi angka di laporan 9 September masih persis berlaku.

Bukti: `cmp_05_fg_breakdown_202608.csv` (kolom `param_key` packing) dan
`cmp_04_fg_product_cost_202608_summary.csv`.

---

## E-5 · `ENG-DECOMP-BREAK` — dekomposisi tidak menutup 🟠 P1

**1.045 baris** dengan `total_cost ≠ total_rm + total_conversion`
(16.566 dari 17.611 menutup, 94,1%). Ini satu-satunya temuan yang belum bisa
dijelaskan di recon. Saran: bandingkan `cpc_formula_trace` baris yang gagal vs
yang berlaku, cari suku yang tidak ikut terjumlah.

---

## Isi paket

| File | Isi | Ukuran |
|---|---|---|
| `README_UNTUK_ILHAM.md` | dokumen ini | — |
| `E1_unit_cost_1_evidence_202608.csv` | 8.718 baris bukti E-1 | ~1 MB |
| `E1_input_product_classification.csv` | 5.720 produk input, label GAGAL/OK | ~400 KB |
| `../out/cmp_04_fg_product_cost_202608_summary.csv` | ringkasan match-rate per metrik | 5 KB |
| `../out/cmp_05_fg_breakdown_202608.csv` | breakdown per `param_key` (bukti E-2) | 6,5 MB |
| `../report/RECON_REPORT_202608.md` | laporan lengkap; §4.5, §4.6, §9.2 relevan | — |

**Tidak disertakan** (sengaja):
- `cmp_04_fg_product_cost_202608_diff.csv` (12 MB) — data gejala mentah, terlalu
  besar untuk Excel dan tidak menunjuk akar masalah. Ringkasannya sudah cukup;
  minta file ini kalau perlu telusur per produk.
- `cmp_04_fg_crosswalk_202608.csv` (2,7 MB) — peta identitas legacy↔baru. Perlu
  hanya kalau Ilham mau memverifikasi ulang terhadap legacy, bukan untuk
  memperbaiki defect.

## Catatan validitas

Angka E-1 dan E-2 di atas diukur pada `cpc_version = 3` hasil reproses hari
ini, dan **identik** dengan hasil recon 9 September. Reproses tidak
menggesernya sedikit pun — itu justru konfirmasi bahwa keduanya defect kode,
bukan artefak data periode.
