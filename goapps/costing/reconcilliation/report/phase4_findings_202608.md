# Fase 4 — Masterbatch LDR % (Dozing): Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) — lihat §1 soal keterbatasan periode |
| Snapshot | 2026-09-09 (lihat `snapshot_stamp_202608.md`) |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_03_mb_ldr_dosing_202608.sql` |
| Script join | `build_phase4.py` |
| Output | `out/cmp_03_mb_ldr_dosing_202608.csv` (8.406 baris = 2.802 spin × 3 metric) |
| Sumber legacy | `CST_MST_BATCH_SPIN` — 2.791 baris |
| Sumber baru | `mst_mb_spin` — 2.799 baris |

---

## 1. Keterbatasan periode

Sama seperti Fase 3: spin master tidak punya kolom periode di kedua sisi. Ini
perbandingan master **per 2026-09-09**, bukan per 202608.

---

## 2. Tuntasan keputusan #4 — filter cost type di query dosing

Ini yang Anda minta dibuktikan di akhir Fase 0. Pertanyaannya: query basis
`masterbatch dozing.txt` mengomentari `AND c.cyc_prs_type = :vPrsType`,
sehingga CTE `doz` meng-`MAX()` lintas dua cost type. Apakah aman?

**Tidak aman.** Untuk TOP 71 (`ldr_dozing`) di `CST_YARN_CALCULATION`:

| Kondisi per `cyc_left_no` | Jumlah |
|---|---:|
| Ada nilai di dua cost type, **identik** | 1.617 |
| Ada nilai di dua cost type, **BERBEDA** | **342** |
| Hanya ada di VALUATION VALUE | 5 |
| Hanya ada di MARKETING VALUE | 1.978 |
| Baris ada tapi nilainya NULL di dua sisi | 10.690 |

Dari 1.959 produk yang punya nilai di kedua cost type, **342 (17,5%) berbeda**.
Tanpa filter, `MAX()` diam-diam memilih nilai yang lebih besar pada 342 produk
itu.

> **Rekomendasi:** aktifkan filter `cyc_prs_type` di query dosing. Untuk track
> ACTUAL gunakan `20210800120` (VALUATION VALUE). Ini deviasi dari query basis,
> dan saya catat sebagai temuan — bukan diperbaiki diam-diam.

Catatan tambahan: mayoritas produk (10.690) punya baris TOP 71 dengan nilai
NULL, jadi jalur "harvest LDR dari calculation" ini jauh lebih sepi daripada
yang tersirat di query basis.

---

## 3. Koreksi instruksi prompt — dua-duanya tidak bisa dijalankan

Prompt: *"Baru: `mst_mb_spin.mbs_dozing` (= LDR%), filter
`mbs_check_status='Current'`."*

### 3.1 `mbs_check_status` tidak ada

Kolom itu **tidak ada** di `mst_mb_spin`. Yang ada `mbs_status`, dan nilai
`'Current'` tidak pernah muncul di sana:

| Sisi | Kolom | Nilai |
|---|---|---|
| Legacy | `CMBS_CHECK_STATUS` | **Current 644**, Waiting 2.043, Boughtout 61, Approved 34, Outdated 1, null 8 |
| Legacy | `CMBS_STATUS` | R and D 1.743, Spinning 985, Boughtout 62, null 1 |
| goapps | `mbs_status` | R and D 1.761, Spinning 975, Boughtout 62 |

Jadi yang termigrasi adalah `CMBS_STATUS` → `mbs_status` (angkanya nyaris
identik). **`CMBS_CHECK_STATUS` — kolom yang justru memuat `Current` — tidak
punya kolom padanan sama sekali di sisi baru.**

Konsekuensinya: **tidak ada cara memfilter "spin Current" di sisi baru.**
Konsep siklus hidup Waiting → Current → Outdated hilang dalam migrasi. Itu
temuan struktural, bukan sekadar salah nama kolom.

### 3.2 `mbs_dozing` sudah di-retire — keputusan D30

Kode engine mendokumentasikannya eksplisit:

> `mbs_dozing` is the retired, contaminated legacy column … its units are mixed
> across heads (oil-rate vs run_ldr scale)
>
> Dozing was withdrawn by explicit user decision on **2026-08-26 (D30
> contamination)** and replaced by LdrPrsn ("LDR Rencana (%)", from
> `mbs_ldr_prsn`) and RunLdrPct ("**LDR Aktual (%)**", from `mbs_run_ldr_pct`) —
> both unambiguous, uncontaminated columns.

Sumber: `services/finance/internal/delivery/grpc/yarn_lookup_fill_handler.go`
(`mbSpinNumericReaders`) dan `services/finance/internal/domain/lookupmaster/entity.go`.
Reader-nya sengaja dipertahankan hanya supaya fill yang sudah live tidak kosong,
dan sengaja **tidak** didaftarkan di `mst_lookup_master_column` (ditarik keluar
dari migration 000477).

Datanya mengonfirmasi: `mbs_dozing` terisi **13 dari 2.799 baris**, rentang
**0,0120 – 3,6500** — persis pencampuran skala yang dijelaskan D30. Di legacy
pun `CMBS_DOZIN` hanya terisi **5 dari 2.791**.

**Kalau prompt diikuti apa adanya, Fase 4 akan membandingkan dua kolom yang
sama-sama kosong dan menyimpulkan "match 100%" dari 5 baris.**

### 3.3 Pemetaan yang dipakai

| Metric | Legacy | goapps | Arti |
|---|---|---|---|
| **LDR_AKTUAL** | `CMBS_RUN_LDR_PRSN` | `mbs_run_ldr_pct` | LDR Aktual — **nilai yang benar untuk costing** |
| LDR_RENCANA | `CMBS_LDR_PRSN` | `mbs_ldr_prsn` | LDR Rencana (produk masih baru) |
| DOZING | `CMBS_DOZIN` | `mbs_dozing` | retired, dilaporkan sebagai informasi saja |
| *(turunan)* | — | `mbs_ldr_calculated_pct` | disinkronkan dari MB Head oleh `syncRootSpinLDRFromHead` |

Fill rate mendukung pemetaan itu: `CMBS_RUN_LDR_PRSN` 2.788 ↔ `mbs_run_ldr_pct`
2.697; `CMBS_LDR_PRSN` 2.056 ↔ `mbs_ldr_prsn` 1.959.

---

## 4. Granularitas — peringatan prompt terbukti, dan lebih keras

Prompt menyuruh *"bandingkan per POY spec (denier/filament/shade)"* dan
memperingatkan legacy boleh punya beberapa varian per spec. Peringatan itu
benar, dan konsekuensinya membatalkan instruksi agregasinya:

| Metrik | Nilai |
|---|---:|
| POY spec distinct (`CMBS_D_F`, whitespace dinormalisasi) | 256 |
| Spec dengan lebih dari satu spin | **140** |
| Spin di dalam spec multi-varian | **2.151** |
| Spec yang nilai LDR legacy-nya **berbeda antar varian** | **121** |

Contoh: `POY 250/48/RND/DSD` memuat **530 spin** dengan LDR legacy
1,00 / 1,05 / 1,09 / 1,10 / 1,12 / 1,15 / 1,2 … `POY 250/36/RND/DSD` memuat 200
spin. Agregasi per spec akan meratakan nilai yang memang berbeda secara sah.

**Jadi perbandingan dilakukan per spin (`CMBS_SYS_ID`)**, dan spec dipakai
sebagai atribut saja.

Soal suffix varian `-01`/`-02`: di sisi baru **tidak** direpresentasikan sebagai
hirarki — `mbs_parent_spin_id` NULL untuk seluruh 2.799 baris. Suffix-nya hidup
di `mbs_shade_code` (1.776 dari 2.799 baris, mis. `7177-01`, `2150-01`).

---

## 5. Populasi spin

| Metrik | Nilai |
|---|---:|
| Legacy `CST_MST_BATCH_SPIN` | 2.791 |
| goapps `mst_mb_spin` | 2.799 |
| **Matched** (`CMBS_SYS_ID` = `mbs_oracle_sys_id`) | **2.689** |
| Legacy-only | 102 |
| goapps punya oracle id **yang tidak ada di legacy** | **11** |
| goapps tanpa oracle id (spin lahir di sistem baru) | 99 |

**102 legacy-only**: 101 `check_status='Waiting'` + 1 `Boughtout`. Draft yang
tidak dimigrasikan → `SCOPE`, konsisten dengan pola Fase 3.

**11 oracle id menggantung** — ini temuan baru. Sudah diverifikasi bahwa
kesebelas `CMBS_SYS_ID` itu **tidak ada** di `CST_MST_BATCH_SPIN` (query
langsung mengembalikan 0). Enam di antaranya `Spinning` dan `is_active = true`
— baris produksi dengan provenance rusak:

| `mbs_oracle_sys_id` | nama | `mbs_run_ldr_pct` | status | aktif |
|---|---|---:|---|---|
| 202104173 | CLASSIC BLUE | 3,7800 | Spinning | **Y** |
| 202104327 | DARK VERMELHO HBR | 3,1000 | Spinning | **Y** |
| 202302149 | COBALT BLUE BR | 4,1700 | Spinning | **Y** |
| 202306311 | COBALT BLUE | 2,6020 | Spinning | **Y** |
| 202307348 | COBALT BLUE BR | 2,6200 | Spinning | **Y** |
| 202311545 | DARK WINE | 3,0600 | Spinning | **Y** |
| 202307346 | PAPRIKA MN | 3,5000 | R and D | N |
| 202310516 | PAPRIKA MN BR | 3,4500 | R and D | N |
| 202512616 | SPLATTER BK | 6,0000 | Boughtout | N |
| 202601695 | PALMER BN | 2,3700 | Spinning | N |
| 202605871 | YODA BK | 6,0000 | R and D | N |

Kemungkinan baris legacy-nya dihapus setelah migrasi. Perlu dipastikan bukan
salah-tulis id.

---

## 6. Hasil perbandingan

| Metric | comparable | setara | **berbeda** | both-empty | non-numerik | legacy-only | new-only |
|---|---:|---:|---:|---:|---:|---:|---:|
| **LDR_AKTUAL** | 2.686 | **2.582** | **104** | 3 | 0 | 102 | 11 |
| LDR_RENCANA | 1.954 | 1.952 | **2** | 732 | 2 | 102 | 11 |
| DOZING (retired) | 5 | 5 | 0 | 2.682 | 0 | 102 | 11 |

### 6.1 LDR_AKTUAL — 96,1% setara

**2.582 dari 2.686 (96,1%) setara**, 104 (3,9%) berbeda. Toleransi 0,0001
(legacy 2 desimal, sisi baru 4 desimal).

104 selisih itu menurut status legacy:

| `CMBS_CHECK_STATUS` | Spin |
|---|---:|
| `Waiting` (draft) | 84 |
| **`Current`** (produksi) | **18** |
| `Approved` | 2 |

Arah: 69 naik, 35 turun. Pada **37 dari 104**, nilai baru sama dengan
`mbs_ldr_calculated_pct` — indikasi LDR-nya sudah ditimpa oleh sinkronisasi
dari MB Head.

### 6.2 Delapan belas yang berstatus `Current` — ini yang perlu perhatian

Semuanya `is_active = true`, jadi ini nilai produksi:

| `CMBS_SYS_ID` | nama | legacy | baru | delta | `calc_pct` |
|---|---|---:|---:|---:|---:|
| **202211045** | **SINAR GN BR** | **4,91** | **3,68** | **−1,23** | 3,67 |
| 202104226 | RADIO BL | 2,19 | 2,37 | +0,18 | 2,06 |
| 202104351 | LARK BEIGE | 1,34 | 1,52 | +0,18 | 1,47 |
| 202202735 | SINAR GREEN | 3,77 | 3,59 | −0,18 | 3,67 |
| 202104292 | GLADSOME BR | 1,60 | 1,77 | +0,17 | 1,67 |
| 202305257 | RUBY MAROON | 2,22 | 2,35 | +0,13 | 2,30 |
| 202110604 | ARSENE RD | 2,19 | 2,29 | +0,10 | 2,30 |
| 202104399 | DISTAN GY | 1,76 | 1,67 | −0,09 | 1,53 |
| 202104513 | SURF BLUE | 2,07 | 2,16 | +0,09 | 2,10 |
| 202104068 | VENUS RD BR | 2,01 | 2,08 | +0,07 | 2,08 |
| 202104368 | MULTI BLUE BR | 1,96 | 1,89 | −0,07 | 1,50 |
| 202104307 | EARTH | 1,92 | 1,86 | −0,06 | 1,92 |
| 202310530 | MULTI BLUE BR | 1,79 | 1,73 | −0,06 | 1,50 |
| 202104293 | CAFE LEON | 3,75 | 3,70 | −0,05 | 3,65 |
| 202104300 | BEIGE | 1,20 | 1,25 | +0,05 | 1,48 |
| 202201706 | TURMERIC YW | 2,71 | 2,76 | +0,05 | 3,00 |
| 202104393 | LOMO BROWN BR | 3,41 | 3,45 | +0,04 | 3,45 |
| 202104296 | DARK WINE | 2,35 | 2,34 | −0,01 | 2,34 |

Tujuh belas di antaranya selisihnya kecil (±0,01 – 0,18) — konsisten dengan
pembaruan nilai LDR setelah migrasi, bukan kesalahan konversi.

**`202211045` SINAR GN BR adalah pengecualian dan penyebabnya sudah ketemu
(§7): spesifikasi POY-nya berubah.**

### 6.3 LDR_RENCANA — hanya 2 selisih

| `CMBS_SYS_ID` | nama | legacy | baru | delta |
|---|---|---:|---:|---:|
| 202507373 | HUSK GY | 1,5 | 2,00 | +0,50 |
| 202212094 | PAPRIKA MN BR | 3 | 3,15 | +0,15 |

`202507373` juga punya spec berubah (§7) — jadi dua dari tiga anomali terbesar
di fase ini berakar pada perubahan spec, bukan perubahan LDR.

### 6.4 Dua nilai legacy tidak numerik

`CMBS_LDR_PRSN` bertipe `VARCHAR2`, dan isinya memang tercemar:

| `CMBS_SYS_ID` | nama | `CMBS_LDR_PRSN` mentah |
|---|---|---|
| 202406744 | ROCCIA BN | `2.00%` |
| 202409870 | ESTONIA BN | `3.00POY 445/96/RND/DSD` |

Yang kedua adalah string spesifikasi yang tersambung ke dalam field LDR.
Keduanya akan membuat `TO_NUMBER()` melempar **ORA-01722** kalau dipanggil
tanpa penjagaan — dan query basis dosing memang memakai `TO_NUMBER()` langsung.
Diklasifikasikan `nonnumeric-source`, tidak dipaksa jadi angka dan tidak
dibuang diam-diam.

---

## 7. `CMBS_D_F` — enam spec berbeda, dan itu menjelaskan selisih terbesar

Dari 2.689 pasangan matched: **2.682 spec identik**, 6 berbeda (sisanya kosong
di salah satu sisi).

| `CMBS_SYS_ID` | nama | legacy `D_F` | baru `d_f` | jenis | delta LDR | status |
|---|---|---|---|---|---:|---|
| **202211045** | SINAR GN BR | `500/96/TBL/DBR` | `250/48/RND/DSD` | **spec berbeda** | **−1,23** | **Current** |
| 202110596 | CLASSIC BL BR | `380/108/2/TBL/DBR` | `250/48/TBL/DBR` | **spec berbeda** | −0,55 | Waiting |
| **202507373** | HUSK GY | `POY 250/48/RND/DSD` | `POY 375/72/RND/DSD` | **spec berbeda** | +0,71 | Waiting |
| 202602722 | WORK GY | `<byte non-ASCII>250/36/RND/DSD` | `250/36/RND/DSD` | encoding | 0 | Waiting |
| 202111630 | LILAC PL BR | `380/108/2 TBL/DBR` | `380/108/2 TBL/DBR` | hanya whitespace | 0 | Boughtout |
| 202301110 | LILY WHITE | `250/48 RND/OSD` | `250/48 RND/OSD` | hanya whitespace | 0 | Boughtout |

**Ini menutup selisih terbesar di fase ini.** `202211045` (SINAR GN BR) bukan
kasus "LDR salah dikonversi" — spesifikasi POY-nya sendiri berubah dari
`500/96/TBL/DBR` ke `250/48/RND/DSD`. Denier turun dari 500 ke 250, jadi LDR
yang berbeda (4,91 → 3,68) memang **konsekuensi wajar**, bukan defect. Yang
perlu dikonfirmasi adalah apakah perubahan spec itu disengaja.

Hal yang sama untuk `202507373` HUSK GY (`250/48` → `375/72`) dan `202110596`
CLASSIC BL BR.

Catatan `202602722` WORK GY: nilai legacy memuat satu byte non-ASCII di depan
`250/36/RND/DSD` yang tidak bisa didekode lewat jalur ekspor saya, jadi karakter
persisnya tidak diketahui. Sisi baru sudah bersih. LDR-nya tidak terpengaruh.

---

## 8. Konsistensi internal sisi baru — `mbs_ldr_calculated_pct` vs `mbs_run_ldr_pct`

Ini perbandingan **di dalam sisi baru**, bukan lawan legacy:

| | Spin |
|---|---:|
| Identik (≤ 0,0001) | 1.700 |
| **Berbeda** | **923** |
| Hanya `calculated_pct` terisi | 83 |
| Hanya `run_ldr_pct` terisi | 74 |
| Dua-duanya kosong | 19 |
| **Total** | **2.799** |

**923 spin punya LDR turunan yang berbeda dari LDR aktualnya.** Menurut
`syncRootSpinLDRFromHead`, `mbs_ldr_calculated_pct` disinkronkan dari MB Head
setiap kali resep di-(re)validate, dijaga oleh `mbs_ldr_is_actual = FALSE`.
Dan `mbs_ldr_is_actual = true` untuk **0 baris** — jadi tidak ada satupun spin
yang LDR-nya dikunci sebagai actual oleh manusia.

Artinya seluruh 923 spin itu terbuka untuk ditimpa oleh sinkronisasi berikutnya.
Ini paralel dengan drift working-vs-engine di Fase 3: **risiko ke depan**, bukan
selisih hari ini. Yang mana yang dipakai costing perlu ditelusuri di Fase 6.

---

## 9. Sanity range LDR 1,0 – 5,0

| Sisi | in range | > 5,0 | < 1,0 |
|---|---:|---:|---:|
| Legacy (`CMBS_RUN_LDR_PRSN`) | 2.625 | **125** | **38** |
| goapps (`mbs_run_ldr_pct`) | 2.538 | **122** | **37** |

Prompt menyuruh flag nilai di luar 1,0–5,0. Ada 159–163 per sisi — tapi
pertanyaan yang benar bukan "berapa yang di luar range", melainkan **apakah
diwarisi atau baru muncul**:

| | Spin |
|---|---:|
| Out-of-range di **dua** sisi | **152** |
| Out-of-range hanya di sisi baru | **5** |
| Out-of-range hanya di legacy | 2 |

**152 dari 159 adalah data legacy yang diwarisi apa adanya.** Jadi ini bukan
cacat yang diperkenalkan sistem baru; ini kondisi master data legacy yang perlu
dibersihkan R&D. Hanya **5 spin** yang keluar dari range akibat perubahan di
sisi baru — itu yang layak diperiksa.

---

## 10. Ringkasan untuk Executive Summary

| Scope | Objek | Setara | Selisih material | Tidak bisa dibandingkan |
|---|---:|---:|---:|---:|
| MB LDR Aktual | 2.802 spin | 2.582 | **104** (18 produksi) | 116 |
| MB LDR Rencana | 2.802 spin | 1.952 | **2** | 848 |
| MB Dozing (retired) | 2.802 spin | 5 | 0 | 2.797 |

"Tidak bisa dibandingkan" = legacy-only 102 + new-only 11 + both-empty +
non-numerik. Di luar itu, **99 spin sisi baru tanpa oracle id** tidak punya
kunci pembanding sama sekali dan dilaporkan terpisah.

**Kesimpulan Fase 4: LDR Aktual 96,1% setara.** Dari 104 selisih, hanya 18
menyentuh spin produksi (`Current`), 17 di antaranya selisih kecil ±0,01–0,18,
dan satu outlier besar sudah terjelaskan oleh perubahan spesifikasi POY.

---

## 11. Temuan yang perlu keputusan

1. **`CMBS_CHECK_STATUS` tidak dimigrasikan** (§3.1). Siklus hidup
   Waiting → Current → Outdated hilang di sisi baru, jadi tidak ada cara
   memfilter "spin Current". Perlu keputusan: apakah konsep ini perlu
   dikembalikan? Ini juga berarti angka "18 selisih produksi" hanya bisa
   ditentukan dari sisi legacy.
2. **Aktifkan filter `cyc_prs_type` di query dosing** (§2). 342 produk terdampak.
3. **`202211045` SINAR GN BR, `202507373` HUSK GY, `202110596` CLASSIC BL BR**
   — spesifikasi POY berubah antar sistem (§7). Perlu konfirmasi R&D apakah
   disengaja. `202211045` berstatus produksi.
4. **11 oracle id menggantung** (§5), 6 di antaranya produksi aktif. Perlu
   dipastikan apakah baris legacy-nya dihapus atau id-nya salah tulis.
5. **923 spin dengan `calculated_pct` ≠ `run_ldr_pct`** (§8), dan
   `mbs_ldr_is_actual = false` di semua baris sehingga semuanya terbuka
   ditimpa sinkronisasi. Perlu diputuskan mana yang otoritatif untuk costing,
   dan apakah nilai produksi perlu dikunci.
6. **152 spin out-of-range yang diwarisi legacy** (§9). Pembersihan master data
   R&D — bukan isu migrasi. **5 spin** yang out-of-range hanya di sisi baru
   perlu diperiksa.
7. **2 nilai `CMBS_LDR_PRSN` tidak numerik** (§6.4). Legacy sudah beku;
   informasi saja, tapi query manapun yang memakai `TO_NUMBER()` langsung akan
   gagal.
8. **17 selisih kecil pada spin `Current`** (§6.2). Perlu konfirmasi R&D bahwa
   nilai baru memang pembaruan yang disengaja, bukan artefak.

---

## 12. Definition of Done — Fase 4

- [x] Query dosing legacy dipakai sebagai basis, dan pertanyaan cost type-nya
      dijawab dengan bukti (keputusan #4 tuntas)
- [x] Kolom LDR yang benar diidentifikasi dari kode engine (D30), bukan dari
      asumsi prompt; `mbs_dozing` dilaporkan sebagai retired
- [x] `mbs_check_status` yang tidak ada dilaporkan sebagai temuan struktural
- [x] Perbandingan per spin, bukan per POY spec — dengan bukti mengapa agregasi
      per spec tidak valid (121 spec multi-LDR)
- [x] Varian `-01`/`-02` tidak diasumsikan 1:1; lokasi sebenarnya diidentifikasi
      (`mbs_shade_code`)
- [x] `CMBS_D_F` dibandingkan, dan 6 selisih spec ditemukan — menjelaskan
      selisih LDR terbesar
- [x] Sanity range 1,0–5,0 diterapkan di kedua sisi dan dipisahkan
      diwarisi vs baru
- [x] Nilai non-numerik legacy ditangani eksplisit, tidak dibuang diam-diam
- [x] Konsistensi internal sisi baru diukur (923 drift)
- [x] Seluruh baris terklasifikasi; CSV tersimpan, query rerunnable
- [x] Nol statement write, nol trigger recompute
