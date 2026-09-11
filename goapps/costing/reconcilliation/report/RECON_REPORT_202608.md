# Laporan Rekonsiliasi Costing — Legacy vs goapps
## Periode Agustus 2026 (202608)

| Field | Value |
|---|---|
| **Versi dokumen** | `v1.0` |
| **Status** | `Draft — menunggu review IT Lead sebelum ke Finance/R&D` |
| **Author** | Indra (IT Lead) |
| **Last Updated** | `2026-09-09` |
| **Periode dibandingkan** | 202608 (Agustus 2026) |
| **Snapshot diambil** | `2026-09-09 09:51 WIB` (diverifikasi ulang `16:28 WIB`) |
| **Status periode** | Closed di legacy · Closed di goapps (reprocess masih dimungkinkan) |
| **Mode eksekusi** | READ-ONLY — nol write, nol recompute |
| **Related** | `PRD_MB_Costing_Suite v1.2`, `gap_repo_vs_live_202608.md`, `snapshot_stamp_202608.md`, `phase0`–`phase8_findings_202608.md` |

> **Cara baca angka di dokumen ini.** Semua angka sisi goapps adalah snapshot
> per tanggal di atas. Sistem baru masih bisa di-reprocess, jadi angka dapat
> berubah bila ada koreksi calculation setelah tanggal tersebut. Sisi legacy
> sudah beku.
>
> ⚠️ **Koreksi atas premis di atas:** verifikasi ulang di §3.4 membuktikan sisi
> **goapps stabil** sepanjang sesi, tapi sisi **legacy TIDAK beku** — track
> MARKETING/SELLING dan master MB/spin masih berubah. Rinciannya di §1.5.

---

## 1. Executive Summary

### 1.1 Kesimpulan singkat

Sistem baru **belum sepadan dengan legacy untuk 202608**, dan penyebabnya sudah
terlokalisasi — bukan sebaran selisih yang acak. Satu defect engine tunggal
(cost produk antara tidak dipropagasi, menyentuh **8.386 produk / 90%**)
menjelaskan sebagian besar selisih FG, dan dampaknya searah: **FG cost sisi baru
sekitar 23% lebih rendah dari legacy** pada kasus yang selisih karena RM.

Yang sudah sehat: struktur route DAG, recipe MB (21.861 dari 21.863 baris
setara), LDR aktual (96,1% setara), dan parameter produk (89–100% setara).

Track **ACTUAL layak dilanjutkan ke perbaikan lalu recompute**; track SELLING
belum layak dinilai karena RM SELLING nol di sisi baru **dan** pembandingnya di
legacy masih bergerak.

### 1.2 Angka utama

| Scope | Objek dibandingkan | Match dalam toleransi | Selisih material | Tidak bisa dibandingkan |
|---|---:|---:|---:|---:|
| Raw Material cost (ACTUAL) | 350 group | 247 | 78 | 25 |
| Masterbatch recipe | 22.589 line | 21.861 | 2 | 726 |
| Masterbatch LDR % (Aktual) | 2.802 spin | 2.582 | 104 | 116 |
| FG product cost (ACTUAL) | 6.978 produk | 1 | 6.925 | 52 |
| Intermediate product | 9.780 line route | 1.062 | 8.718 | — |
| **Total** | **tidak dijumlahkan** | — | — | — |

Baris **Total sengaja tidak dijumlahkan**: satuan tiap scope berbeda (group,
baris komposisi, spin, produk, baris route), jadi angka gabungannya tidak punya
arti. Menjumlahkannya akan memberi kesan satu populasi tunggal yang sebenarnya
tidak ada. Rekapitulasi lintas scope yang bermakna ada di §7 (per kategori
selisih, bukan per objek).

Toleransi yang dipakai: **ACTUAL < 0.1%**. SELLING & FORECAST dilaporkan
terpisah (lihat §5).

> Catatan pembacaan: baris FG **tidak boleh dibaca sebagai 6.925 defect
> terpisah**. Sesuai §4.5 dan §4.6, mayoritasnya adalah hilir dari satu akar
> penyebab. Nol kasus "semua input setara tapi output berbeda".

### 1.3 Tiga temuan terbesar

| # | Temuan | Dampak | Kategori | Perlu keputusan siapa |
|---|---|---|---|---|
| 1 | **Cost produk antara tidak dipropagasi** — engine memakai `unit_cost = 1` di 8.718 dari 9.780 baris route bertipe PRODUCT (89,1%), padahal cost produk hulu tersedia (rata-rata 2,41) | 8.386 produk (90%); understatement total 12.285,76 satuan cost; RM produk terdampak understated s/d 73% | `ENGINE` | Engineering (Ilham) |
| 2 | **Packing cost sisi baru ~2× legacy** — 349 dari 349 kasus lebih tinggi, median rasio 1,975 (p25 1,957 / p75 1,991) | 27,5% penyebab selisih FG in-scope | `ENGINE` (signature double-count) | Engineering (Ilham) |
| 3 | **RM cost SELLING nol** di 13.421 dari 13.422 produk, dan **25 group ACTUAL nol** karena tier tidak resolve (`flag_*_used='NONE'`) padahal legacy punya rate riil | seluruh track SELLING tidak bisa dinilai | `DATA` | Finance |

### 1.4 Rekomendasi tindak lanjut

- **Perbaiki dulu, baru nilai ulang.** Temuan #1 dan #2 adalah defect engine yang
  harus selesai sebelum angka FG punya arti. Setelah itu 202608 **wajib
  di-recompute**, lalu Fase 5–7 diulang. Sebelum itu, jangan bawa angka FG ke
  Finance.
- **Track ACTUAL dan RM 202608 bisa dijadikan baseline** — keduanya terbukti
  stabil di kedua sisi (§3.4), jadi perbaikan bisa diukur terhadap angka ini.
- **Jalankan paket remediasi data yang sudah disiapkan** (`remediation/`, 5
  script) untuk 32 group fixed-value, 8 remap group code, dan 164 baris resep MB
  yang hilang.
- **Tetapkan tanggal cut-off legacy** sebelum menilai SELLING — pembandingnya
  masih berubah (§1.5).
- **Tindak lanjuti 195 produk legacy yang hilang** di sisi baru (194 yarn
  in-scope 202608 + 1 MB berstatus `Current`).

### 1.5 Peringatan integritas snapshot

**Sisi goapps: nol recompute.** Diverifikasi 6,6 jam setelah baseline — seluruh
metrik identik (52.833 baris aktif, `MAX_CALC_AT` tetap 09:13:10, job 102–105
tidak berubah, **nol** baris dihitung setelah 09:13). Angka goapps di laporan ini
berasal dari satu run yang konsisten.

**Sisi legacy: BERGERAK.** Premis prompt "legacy tidak bergerak lagi" tidak
akurat:

| Tabel legacy | Awal (09:46) | Akhir (16:29) | Drift |
|---|---:|---:|---|
| `_CUR` MARKETING (→ SELLING) | 14.635 | **14.667** | **+32**, terakhir diubah **15:25:01** |
| `_CUR` VALUATION (→ ACTUAL) | 7.505 | 7.505 | **0** — stabil sejak 2026-09-02 |
| `CST_MST_BATCH_HEAD` | 4.325 | **4.328** | +3 |
| `CST_MST_BATCH_ITEM` | 22.589 | **22.604** | +15 |
| `CST_MST_BATCH_SPIN` | 2.791 | **2.799** | +8 |
| `CST_GRP_CONSUMP_HEAD` @202608 | 924 | 924 | **0** |

**Yang penting: track ACTUAL dan RM 202608 — sumber angka utama laporan —
stabil di kedua sisi.** Yang bergerak adalah track SELLING dan master MB/spin,
dan ketiganya sudah dilaporkan terpisah (§4.2, §4.3, §5), tidak digabung ke
angka utama.

---

## 2. Scope & Metodologi

### 2.1 Yang dibandingkan

| Scope | Sumber legacy (Oracle MGTAPPS) | Sumber baru (PostgreSQL goapps) | File query |
|---|---|---|---|
| Raw Material cost | **`CST_GRP_CONSUMP_HEAD`** @ (2026, 8) | `cst_rm_cost` + **`cst_rm_group_head_period`** @202608 | `cmp_01_raw_material_cost_202608.sql` |
| MB recipe | `CST_MST_BATCH_HEAD` + `CST_MST_BATCH_ITEM` | `mst_mb_composition_version` (`mbcv_version = mbh_current_version`) | `cmp_02_mb_recipe_202608.sql` |
| MB LDR % | `CST_MST_BATCH_SPIN` (`CMBS_RUN_LDR_PRSN`) | `mst_mb_spin.**mbs_run_ldr_pct**` — bukan `mbs_dozing`, dan `mbs_check_status` **tidak ada** (§2.4 #5) | `cmp_03_mb_ldr_dosing_202608.sql` |
| FG product cost | `CST_YARN_CALCULATION_CUR` | `cst_product_cost` (`<> 'SUPERSEDED'`), **via `cpc_param_snapshot`** | `cmp_04_fg_product_cost_202608.sql` |
| FG breakdown | **TOP 21/22/42/48/55–57/61/73/87–95/103–106/118–122/127–128** di `_CUR` | `cpc_param_snapshot` + `cpc_rm_cost_detail` + **`cpc_formula_trace`** + **`cpc_cost_by_level`** | `cmp_05_fg_calculation_breakdown_202608.sql` |
| Intermediate product | **`CST_LVL_LEFT_PROD`** (subset ber-`CLLP_LEFT_NO`) | `cost_route_head`/`seq`/`rm` + `cpc_cost_by_level` | `cmp_06_intermediate_product_202608.sql` |
| Produk baru | `CST_YARN_LEFT` + `CST_MST_BATCH_HEAD` | `cost_product_master` (+ `mst_mb_head` untuk provenance kedua) | `cmp_07_new_products_202608.sql` |

### 2.2 Yang TIDAK dicakup

- **FORECAST sebagai angka utama** — dikeluarkan atas keputusan IT Lead: legacy
  tidak punya track forecast, hanya selling. Tetap dilaporkan di §5.
- **Cost intermediate per level** — diblokir oleh defect §4.6, bukan dilewatkan.
  Struktur DAG tetap dibandingkan.
- **Produk MB di Fase 5** — `cpm_flex_02` untuk MB berisi `CMBH_SYS_ID`, bukan
  `CYL_LEFT_NO`, jadi MB ditangani di §4.2/§4.3.
- **Aparatus recon yang sudah ada di live DB** (9 view `vw_recon_*` + 15 tabel
  `stg_leg_*`) — tidak dipakai atas keputusan IT Lead; seluruh query ditulis dari
  dua file basis legacy.
- **Diff kode antar rilis** — `git clone --depth 1` hanya membawa 1 commit, jadi
  "perubahan sejak rilis terakhir" tidak bisa dijawab. Yang dilakukan: membaca
  kondisi HEAD (2026-09-08) dan mencocokkannya dengan struktur live DB.

### 2.3 Toleransi & aturan klasifikasi

| Kategori | Definisi | Warna di tabel |
|---|---|---|
| `MATCH` | selisih relatif < 0.1% (ACTUAL) | — |
| `MINOR` | 0.1% – 1% | ⚠️ |
| `MATERIAL` | > 1% | 🔴 |
| `SCOPE` | baris hanya ada di satu sisi | ◻️ |
| `KNOWN-GAP` | masuk kategori gap struktural yang sudah diterima | ✅ diketahui |
| `UNKNOWN` | belum bisa dijelaskan | ❓ |

**Catatan konsistensi ambang.** Badan prompt memakai dua band (*ACTUAL target
<0,1%; di atas itu material*), sementara tabel di atas memakai tiga band. Angka
di §1.2 memakai aturan badan prompt (**"material" = ≥0,1%**), sedangkan §4.1 dan
§4.4 memecahnya jadi tiga band sesuai tabel ini. Karena itu jumlah "material" di
§1.2 lebih besar dari §4.1 — bukan inkonsistensi data, melainkan dua ambang yang
berbeda. Perbedaannya kecil: di RM ACTUAL hanya 3 group yang jatuh di band
0,1–1%.

**Kelas tambahan yang dipakai dan kenapa:**

| Kelas | Definisi | Alasan wajib dipisah |
|---|---|---|
| `match-rounding` | \|selisih\| ≤ 5e-7 (RM) / ≤ 5e-5 (FG) | Legacy menyimpan ~30 angka signifikan (RM) dan 4 desimal (FG); sisi baru `numeric` 6 desimal / presisi penuh. Tanpa kelas ini, **124 group RM dan 548 produk FG** akan salah dilaporkan sebagai selisih data. |
| `new-zero-flag-none` | sisi baru 0 karena `flag_*_used='NONE'` | kategori bisnis (tier tidak resolve), bukan selisih numerik |
| `nonnumeric-source` | nilai legacy bukan angka | `CMBS_LDR_PRSN` bertipe `VARCHAR2` dan memuat `2.00%` serta `3.00POY 445/96/RND/DSD` |
| `BLOCKED-BY-DEFECT` | perbandingan tidak bermakna sampai defect diperbaiki | dipakai untuk cost intermediate per level (§4.6) |

### 2.4 Asumsi yang dipakai

| # | Asumsi | Dasar | Risiko bila salah |
|---|---|---|---|
| 1 | Kolom periode legacy berformat **dua kolom `NUMBER`** (`PERIOD_YEAR` + `PERIOD_MONTH`), bukan `varchar(6)` `'YYYYMM'` | Dibuktikan Fase 0 poin C: `CST_MST_ORION_REFF_HDR` = (2026, 8); `CST_GRP_CONSUMP_HEAD` = (`CGCH_PERIOD_YEAR`, `CGCH_PERIOD_MONTH`). Bulan **tidak** zero-padded → concat mentah menghasilkan `'20268'` | filter periode salah → seluruh laporan invalid |
| 2 | `CST_YARN_CALCULATION_CUR` memuat 202608 | **Dikonfirmasi IT Lead**: `_CUR` adalah periode 202608. Bukti pendukung: `_CUR` tidak punya kolom periode; 202608 adalah periode terbuka terakhir di ORION; **seluruh 1.546/1.545 baris in-scope bervintage ≥ 2026-08-01** | membandingkan periode yang salah |
| 3 | Crosswalk FG valid 1:1 | Dibuktikan Fase 5: `CYCC_LEFT_NO` **unik per `prs_type`** di `_CUR` (14.665/14.665 dan 7.505/7.505, 0 null); duplikasi `CYL_LEFT_NO` di `CST_YARN_LEFT` = **dimensi cost type** (tepat 2 baris/left_no); `CYCC_CYL_SYS_ID` resolve 100% | fan-out → double counting |
| 4 | Crosswalk RM group = **`CGCH_CGH_SYS_ID` ↔ `group_code`** | Arti nama kolom **bertukar**: legacy `CGH_SYS_ID`→`group_code`, `CGH_GROUP_CODE`→`group_name`. Dengan kunci benar: **350/350 match, nol ambigu** | join `group_code=group_code` menghasilkan **0 match** |
| 5 | LDR sisi baru = **`mbs_run_ldr_pct`**, bukan `mbs_dozing` | Keputusan **D30 (2026-08-26)** di kode engine: `mbs_dozing` "retired, contaminated … mixed across heads (oil-rate vs run_ldr scale)". Data: `mbs_dozing` terisi **13 dari 2.799** | membandingkan dua kolom yang sama-sama kosong lalu menyimpulkan "match 100%" dari 5 baris |
| 6 | Pembanding FG = **`cpc_param_snapshot`**, bukan kolom ringkas | Kolom ringkas (`cpc_total_conversion` dll) adalah agregat definisi baru; diuji lawan **25 TOP kandidat**, kecocokan tertinggi **2,5%** | selisih definisi salah dilaporkan sebagai selisih data (tampak seperti gap 7–8×) |
| 7 | ACTUAL ↔ VALUATION (`20210800120`), SELLING ↔ MARKETING (`20210800119`) | **Dikonfirmasi IT Lead**. `CYC_PRS_NAME` di legacy: "VALUATION VALUE" / "MARKETING VALUE" | seluruh pemetaan cost type tertukar |

---

## 3. Preflight & Kesiapan Data

### 3.1 Hasil schema discovery

| Pertanyaan Fase 0 | Jawaban | Bukti |
|---|---|---|
| Semantik suffix `_CUR` | **Current-only snapshot, di-overwrite per baris.** Wide/pivot 158 kolom (150 × `TOP_n_DATA_VALUE`), **tanpa kolom periode**. Satu-satunya dimensi selain produk = `CYCC_PRS_TYPE`, yang isinya **cost type** (2 nilai), bukan periode | `00_preflight...sql` A2–A4, A7. Sebaran vintage: VALUATION memuat baris dari 2022-12 s/d 2026-09 |
| Tabel history FG cost legacy (bila ada) | **Tidak ada.** `CST_YARN_CALCULATION_HIST` bukan history periode — struktur & `PRS_TYPE` identik dengan tabel live, tanpa kolom periode. Pola `_ARCH`/`_LOG` tidak ada | A6 |
| Sumber RM cost legacy | **`CST_GRP_CONSUMP_HEAD`** — satu-satunya kandidat dengan periode + group + rate. 202608 = 924 baris/924 group. **`CGCH_COST` kosong total (0 non-null)** → ACTUAL dari `CGCH_LANDED_COST` | A12–A13, A2. 8 kandidat lain ditolak dengan alasan tertulis |
| Format kolom periode legacy | Dua kolom `NUMBER` (year, month), bulan tidak zero-padded | A8 |
| Tabel migration tracking Postgres | **`schema_migrations_finance`** (relevan), plus `schema_migrations` dan `schema_migrations_iam` | B3 |

### 3.2 Row count 202608

| Tabel | Sisi | Row count | Catatan |
|---|---|---:|---|
| `CST_GRP_CONSUMP_HEAD` @(2026,8) | Legacy | 924 | 584 tanpa item aktif (placeholder spec POY), 340 dengan item aktif |
| `cst_rm_cost` @202608 | goapps | 350 | seluruhnya `rm_type='GROUP'`, `rm_code = group_code` |
| `CST_MST_BATCH_HEAD` | Legacy | 4.325 | Waiting 3.478 · Current 477 · Boughtout 137 · Approved 41 |
| `mst_mb_head` | goapps | 4.226 | 99 legacy-only, semuanya `Waiting` |
| `CST_MST_BATCH_ITEM` | Legacy | 22.589 | |
| `mst_mb_composition_version` @current | goapps | 21.863 | |
| `CST_MST_BATCH_SPIN` | Legacy | 2.791 | |
| `mst_mb_spin` | goapps | 2.799 | 2.700 ber-oracle id, 99 lahir di sistem baru |
| `CST_YARN_CALCULATION_CUR` | Legacy | 22.170 | MARKETING 14.665 + VALUATION 7.505 |
| `cst_product_cost` @202608 aktif | goapps | **52.833** | ACTUAL/SELLING/FORECAST × (APPROVED 4.189 + CALCULATED 13.422) |
| `cst_product_cost` @202608 SUPERSEDED | goapps | 25.989 | dari run 2026-09-05 |
| `cost_product_master` | goapps | 17.937 | 17.695 ber-`cpm_flex_02` |
| `cst_mb_cost` @202608 | goapps | 12.567 | 4.189 × 3 cost type |

**Nuansa penting:** ada **dua status aktif** (`APPROVED` 4.189 dan `CALCULATED`
13.422), bukan satu. Sudah diuji: himpunan produknya **disjoint** (overlap 0),
jadi filter `cpc_status <> 'SUPERSEDED'` benar dan tidak menyebabkan fan-out.
Total produk ter-cost 202608 = **17.611**. Laporan mencakup **keduanya** atas
keputusan IT Lead.

### 3.3 Status migration repo vs live DB

| Migration | Ada di repo | Applied di live | Menyentuh tabel costing | Dampak ke laporan |
|---|---|---|---|---|
| `000468_wire_group_c_master_data` | ✅ | ✅ (< 508) | ya | nihil — sudah applied |
| `000469_seed_derived_cost_params` | ✅ | ✅ | ya | nihil |
| `000470_backfill_group_c_capp` | ✅ | ✅ | ya | nihil |
| `000477_register_mb_spin_reader_columns` | ✅ | ✅ | ya | nihil (tapi lihat D30: `mbs_dozing` ditarik keluar dari migration ini) |
| `000479_create_mst_mb_cross_section` | ✅ | ✅ | ya | nihil |

Ringkasan: **tidak ada migration pending.** Repo tertinggi `000508`, live
`schema_migrations_finance` = **508**, `dirty = false`, dan **tidak ada file
bernomor > 508**. Jadi **tidak ada selisih di laporan ini yang bisa dijelaskan
sebagai artefak migration.**

Tapi ada migration yang **mengubah desain Fase 2** dan belum masuk pemahaman
awal: `000503`–`000505` menambahkan *RM group period versioning*
(`cst_rm_group_head_period` / `cst_rm_group_detail_period`). Sumber konfigurasi
yang benar untuk 202608 adalah **snapshot periode**, bukan anchor. Sudah
diverifikasi: untuk 202608 snapshot **identik** dengan anchor (selisih 0 di 16
kolom), jadi angkanya sama — tapi snapshot yang dipakai karena itu yang dibaca
engine. Detail: `gap_repo_vs_live_202608.md` §2.1.

### 3.4 Calc snapshot stamp

| Metrik | Awal ekstraksi (09:51) | Akhir ekstraksi (16:28) | Berubah? |
|---|---|---|---|
| `NOW()` server | 2026-09-09 09:51:43 | 2026-09-09 16:28:41 | — |
| Jumlah baris aktif `cst_product_cost` 202608 (ACTUAL) | 17.611 | **17.611** | **Tidak** |
| Jumlah baris aktif (SELLING) | 17.611 | **17.611** | **Tidak** |
| Jumlah baris aktif (FORECAST) | 17.611 | **17.611** | **Tidak** |
| Jumlah `cpc_cost_id` baru muncul | — | **0** | **Tidak** |
| Jumlah berubah jadi `SUPERSEDED` | — | **0** (tetap 25.989) | **Tidak** |

**Sisi goapps: nol recompute selama 6,6 jam sesi recon.** `MAX_CALC_AT` tetap
`2026-09-09 09:13:10.407045`, job aktif tetap 102–105, dan **nol** baris dihitung
setelah 09:13.

**Sisi legacy: bergerak** — `_CUR` MARKETING +32 baris (terakhir diubah 15:25:01,
40 baris diubah hari ini), MB head +3, MB item +15, spin +8. Track VALUATION dan
RM 202608 stabil. Rincian dan konsekuensinya di §1.5.

Detail lengkap: `snapshot_stamp_202608.md`

### 3.5 Crosswalk legacy ↔ goapps

Ada **tiga crosswalk terpisah**, masing-masing dengan kunci berbeda. Semuanya
dibuktikan, tidak diasumsikan.

**(a) RM group** — `CST_GRP_CONSUMP_HEAD.CGCH_CGH_SYS_ID` ↔ `cst_rm_group_head.group_code`

| Metrik | Nilai |
|---|---:|
| Produk legacy dalam scope | 924 |
| Produk goapps dalam scope | 350 |
| **Ter-match 1:1** | **350 (100% sisi baru)** |
| Fan-out (1 legacy → banyak baru) | 0 |
| Fan-in (banyak legacy → 1 baru) | 0 |
| Legacy tanpa pasangan | 574 (561 placeholder + 13 kandidat gap) |
| goapps tanpa pasangan | **0** |

**(b) MB** — `mst_mb_head.mbh_oracle_sys_id` ↔ `CMBH_SYS_ID`; line via `mbcm_legacy_sys_id` ↔ `CMBI_SYS_ID`

| Metrik | Nilai |
|---|---:|
| Produk legacy dalam scope | 4.325 head / 22.589 line |
| Produk goapps dalam scope | 4.226 head / 21.863 line |
| **Ter-match 1:1** | **4.226 head (100%) / 21.863 line (100%)** |
| Fan-out / Fan-in | 0 / 0 — pointer 100% distinct, 0 null |
| Legacy tanpa pasangan | 99 head (semuanya `Waiting`) / 726 line |
| goapps tanpa pasangan | **0** |

**(c) FG yarn** — `cost_product_master.cpm_flex_02` ↔ `CYCC_LEFT_NO`, difilter `prs_type`

| Metrik | Nilai |
|---|---:|
| Produk legacy dalam scope | 22.170 (ACTUAL 7.505 / SELLING 14.665) |
| Produk goapps dalam scope | 26.844 (13.422 × 2 cost type) |
| **Ter-match 1:1** | **20.400 (ACTUAL 6.978 / SELLING 13.422)** |
| Fan-out | **0 setelah filter `prs_type`** — duplikasi `CYL_LEFT_NO` (7.530) adalah **dimensi cost type** |
| Fan-in | 0 |
| Legacy tanpa pasangan | 1.770 |
| goapps tanpa pasangan | 6.444 |

⚠️ **`cpm_flex_02` polimorfik**: produk MB berisi `CMBH_SYS_ID` (11 digit,
4.071 baris), produk yarn berisi `CYL_LEFT_NO` (≤6 digit). Prompt menyebut
`cpm_flex_02 = CYL_LEFT_NO` — itu **hanya benar untuk yarn**.

File: `out/cmp_04_fg_crosswalk_202608.csv` (crosswalk FG, dipakai ulang Fase 6 & 7)

---

## 4. Hasil per Scope

*Untuk setiap scope: tabel ringkas di sini, detail baris-per-baris di CSV.*

### 4.1 Raw Material Cost

**Ringkasan**

| Cost type | Group dibandingkan | MATCH | MINOR | MATERIAL | SCOPE | Rata-rata \|selisih %\| | Selisih % terbesar |
|---|---:|---:|---:|---:|---:|---:|---:|
| ACTUAL | 350 | 247 | 3 | 🔴 75 | 25 | 11,52% | 85,78% |
| SELLING | 350 | 152 | 30 | 🔴 126 | 42 | 21,57% | 574,33% |
| FORECAST | 350 | 0 | 0 | 11 | ◻️ 339 | 100,00% | 100,00% |

Dari 247 `MATCH` ACTUAL: 89 selisih **persis 0**, **124 hanya beda pembulatan**
(legacy ~30 angka signifikan vs `numeric` 6 desimal), 32 nol di dua sisi, 2 riil
<0,1%.

**Selisih material (top N)**

| group_code | group_name | Cost type | Legacy rate | goapps rate | Selisih | Selisih % | Kategori | Dugaan penyebab |
|---|---|---|---:|---:|---:|---:|---|---|
| 202301661 | RMT0000004 | ACTUAL | 9,491327 | 1,350000 | −8,141327 | 85,78% | `DATA`/`ENGINE` | tier `PR` (PO rate) |
| 202007171 | GREEN MGTS-4038 | ACTUAL | 144,450000 | 39,900000 | −104,55 | 72,38% | `DATA`/`ENGINE` | tier `PR` |
| 202007619 | LICOWAX-E | ACTUAL | 14,405066 | 7,000000 | −7,405066 | 51,41% | `DATA`/`ENGINE` | tier `FL` |
| 202006072 | PIG0000028 | ACTUAL | 1,762500 | 0,950000 | −0,8125 | 46,10% | `DATA`/`ENGINE` | tier `PR` |
| 202007618 | HOMBITAN LCS | ACTUAL | 3,777396 | 5,453425 | +1,676029 | 44,37% | `DATA` | re-grouping `PIG0000032` (51 resep MB) |
| 202403759 | RMP0000072 | ACTUAL | 5,221250 | 3,200000 | −2,02125 | 38,71% | `DATA`/`ENGINE` | tier `PR` |
| 202412825 | MBC0000292 | ACTUAL | 57,744939 | 38,192967 | −19,551972 | 33,86% | `DATA`/`ENGINE` | tier `PR` |
| 202007157 | BLUE MGTP-5040 | ACTUAL | 2,762500 | 1,954545 | −0,807955 | 29,25% | `DATA`/`ENGINE` | tier `PR` |
| 202007209 | YELLOW MGTS-2038 | ACTUAL | 15,419130 | 19,042857 | +3,623727 | 23,50% | `DATA`/`ENGINE` | tier `PR` |
| 202601913 | RMP0000091 | ACTUAL | 9,471875 | 7,500000 | −1,971875 | 20,82% | `DATA`/`ENGINE` | tier `PR` |

**Catatan tier valuation** — distribusi `flag_valuation_used` dan kaitannya dengan selisih:

| flag_valuation_used | Jumlah group | Jumlah selisih material |
|---|---:|---:|
| **`PR`** (PO rate) | 106 | **65 (61,3%)** 🔴 |
| `SL` (stores-landed) | 127 | 6 (4,7%) |
| `CL` (cons-landed) | 59 | 6 (10,2%) |
| `FL` | 1 | 1 (100%) |
| `NONE` (tier tidak resolve) | 57 | 0 — tapi **25 nol padahal legacy punya rate** |

**Temuan kunci:** **65 dari 78 selisih material ACTUAL (83%) ada di group
ber-tier `PR`**, dan tier `PR` sendiri **61,3% material** — sepuluh kali lipat
`SL` (4,7%). Tier `CL`/`SL` berbasis landed cost consumption/stock, sejalan
dengan `CGCH_LANDED_COST` legacy, dan memang sangat cocok.

**Mekanisme fixed value belum dipakai sama sekali:** seluruh 350 group
ber-`flag_valuation`/`marketing`/`simulation` = **`'CONS'`**, dan
`init_val_valuation`/`marketing`/`simulation` **NULL untuk seluruh 350**.
Padahal CHECK constraint (000503) mengizinkan `'INIT'`. Itulah akar 25 group
ACTUAL / 42 SELLING yang nol.

CSV: `out/cmp_01_rm_cost_matrix_202608.csv` · `out/cmp_01_rm_legacy_orphan_202608.csv`

---

### 4.2 Masterbatch Recipe

**Ringkasan**

| Metrik | Legacy | goapps | Selisih |
|---|---:|---:|---:|
| Jumlah MB total | 4.325 | 4.226 | **99** (semuanya `Waiting`) |
| MB own production (punya recipe) | 4.320 | 4.192 | 128 |
| MB boughtout (tanpa recipe) | — | **1 dari 137** | lihat catatan |
| Total baris komposisi | 22.589 | 21.863 | **726** |

⚠️ **Klaim prompt "boughtout MB tidak punya recipe" TIDAK berlaku:**
**136 dari 137** head ber-`check_status='Boughtout'` **justru punya recipe**.
Saran LEFT JOIN tetap benar, tapi yang hilang dengan INNER JOIN bukan boughtout —
melainkan **34 head tanpa komposisi versi current** (33 di antaranya
`mbh_current_version = 0`).

Temuan terkait: **dua penanda boughtout tidak sinkron** —
`mbh_is_boughtout`=true hanya **7** head vs `check_status='Boughtout'` **137**.
SSOT = `mbh_is_boughtout` (keputusan IT Lead), berarti **130 head boughtout
belum ter-flag** — dan itu punya konsekuensi formula (§4.5).

**Validasi komposisi**

| Cek | Jumlah gagal | Keterangan |
|---|---:|---|
| Sum komposisi ≠ 100.000% (±0.001) di goapps | **4** | 🔴 sum 75,000 / 75,000 / 65,000 / 40,420 — **satu akar penyebab** |
| Sum komposisi ≠ 100.000% di legacy | **0** | 4.197 tepat 100 + 123 beda pembulatan ≤0,01 |
| MB tanpa carrier (PBT) | **21.863 (semua)** | `mbcv_is_carrier` **tidak pernah di-set** — 0 dari 21.863. Kolom tidak bisa dipakai mengidentifikasi carrier; identifikasi harus lewat nama komponen (`PBT`) |

**Akar penyebab 4 resep rusak: komponen `SPC 3221` gagal termigrasi.** Selisih
sum-nya persis sebesar persentase komponen yang hilang (100−25=75, 100−35=65,
100−59,58=40,42). Legacy group `202208630` (SPC 3221) **tidak ada** di
`cst_rm_group_head`. Bukan master hilang — sisi baru punya `RED MGTP-3221`
(`202007188`) + varian BASF/CLA, tapi tidak ada `item_code` yang cocok dengan
`SPC 3221`. **Kegagalan resolusi nama saat migrasi.**

Ada **head SPC 3221 kelima** (`20250904908` MGT AKOVA BN 6785, pct 41,28) yang
tidak masuk daftar ini karena `current_version = 0` — seluruh resepnya hilang.

**Selisih recipe**

| mbh_id / MB code | Nama MB | Jenis selisih | Legacy | goapps | Kategori |
|---|---|---|---|---|---|
| `20251004943` seq 2 | MGT DESTINY BK 7766 — PIG0000067 | `pct beda` | 36,45 | 18,500 | `DATA` |
| `20251004943` seq 3 | MGT DESTINY BK 7766 — PBT (carrier) | `pct beda` | 2,05 | 20,000 | `DATA` |
| `20251004943` seq 4 | MGT DESTINY BK 7766 — MBB0000087 | `source_type beda` | `GROUP` | `MB` | `DATA` |
| 4 head `SPC 3221` | NEWRY BN ×2, IXORA RD, ZENBU RD | `hanya di satu sisi` | ada (25/25/35/59,58) | **hilang** | `SCOPE`→`DATA` |
| 17 baris | berbagai head `comparable` | `hanya di satu sisi` | baris filler kosong | tidak dimigrasikan | benign |
| 7 baris | — | orphan | `CMBI_CMBH_SYS_ID` **kosong** | — | kualitas data **legacy** |

**Hanya 2 dari 21.863 baris yang persentasenya berbeda**, dan keduanya di head
yang sama dengan selisih **saling meniadakan tepat ±17,950** — satu
restrukturisasi resep yang koheren (MBB0000087 diubah jadi nested MB, selisihnya
diserap carrier PBT), bukan tiga defect terpisah. Sum tetap 100 di dua sisi.

**Nested MB (known-gap)** — dipisah agar tidak tercampur:

| MB induk | MB referensi | Legacy | goapps | Catatan |
|---|---|---|---|---|
| 250 head (353 line) | berbagai | 265 head / 373 line | 250 head / 353 line | `mbcv_mb_ref_mbh_id` terisi **100%** (0 null) |
| 4 baris di 30 resep hilang | head `20220803365` (SPC 3221 sbg **MB**) | ada | ada & sehat | ⚠️ SPC 3221 punya **dua bentuk**: RM group `202208630` (hilang) dan MB head `20220803365` (**ada, 4 baris, sum 100, dirujuk 137 referensi nested**). Hanya group-nya yang di-remap |

**Drift working set vs engine — arahnya berlawanan dari dugaan.**
`composition_pct` dan `source_type`: **nol selisih**. Tapi `group_head_id`
berbeda di **701 baris** — dan dari 701 itu, **engine sejalan dengan legacy di
690**, working set hanya 5. Jadi yang menyimpang adalah **working set**
(`mst_mb_composition`), bukan snapshot. Angka 202608 konsisten sekarang;
risikonya muncul kalau ada yang me-re-version. **Instruksi prompt memakai
`mst_mb_composition_version` terbukti benar** — kalau working set dipakai, 701
baris akan salah dilaporkan sebagai selisih.

**Re-grouping lintas fase:** dari 90 baris yang group-nya berbeda, 14 beda
representasi (nested MB) dan 76 re-assignment riil. **51 dari 76 adalah satu
perpindahan**: `202007618` → `202504840`, komponen `PIG0000032` (HOMBITAN LCS) —
yang juga muncul sebagai selisih material 44,37% di §4.1. Dampaknya sudah
disetujui (keputusan IT Lead).

CSV: `out/cmp_02_mb_recipe_head_202608.csv` · `out/cmp_02_mb_recipe_line_202608.csv`

---

### 4.3 Masterbatch LDR % (Dozing)

**Ringkasan**

| Metrik | Nilai |
|---|---:|
| Baris spin legacy | 2.791 |
| Baris spin goapps (`Current`) | **N/A — lihat catatan** |
| Baris spin goapps (semua) | 2.799 |
| Ter-match (`CMBS_SYS_ID` = `mbs_oracle_sys_id`) | **2.689** |
| LDR identik (LDR Aktual, toleransi 0,0001) | **2.582 (96,1%)** |
| LDR berbeda | **104 (3,9%)** — 18 di antaranya `Current` |
| Hanya di legacy | 102 (101 `Waiting` + 1 `Boughtout`) |
| Hanya di goapps | 11 oracle id **menggantung** + 99 lahir di sistem baru |

⚠️ **`mbs_check_status` TIDAK ADA** di `mst_mb_spin`. Yang ada `mbs_status`
∈ {R and D, Spinning, Boughtout} — nilai `'Current'` **tidak pernah muncul**.
Legacy `CMBS_CHECK_STATUS` (yang memuat `Current` = 644) **tidak punya kolom
padanan** di sisi baru; yang termigrasi adalah `CMBS_STATUS` → `mbs_status`.
Konsekuensi: **tidak ada cara memfilter "spin Current" di sisi baru**, dan angka
"18 selisih produksi" hanya bisa ditentukan dari sisi legacy. Keputusan IT Lead:
biarkan, karena mekanismenya berbeda.

**Selisih LDR** (18 yang berstatus `Current` — nilai produksi, semuanya aktif)

| POY spec (denier/filament) | Shade | MB | LDR legacy | LDR goapps | Selisih | Kategori |
|---|---|---|---:|---:|---:|---|
| `500/96/TBL/DBR` → `250/48/RND/DSD` | — | SINAR GN BR (`202211045`) | 4,91 | 3,68 | **−1,23** | `DATA` — **spec POY berubah** (disengaja) |
| — | — | RADIO BL (`202104226`) | 2,19 | 2,37 | +0,18 | `DATA` |
| — | — | LARK BEIGE (`202104351`) | 1,34 | 1,52 | +0,18 | `DATA` |
| — | — | SINAR GREEN (`202202735`) | 3,77 | 3,59 | −0,18 | `DATA` |
| — | — | GLADSOME BR (`202104292`) | 1,60 | 1,77 | +0,17 | `DATA` |
| — | — | RUBY MAROON (`202305257`) | 2,22 | 2,35 | +0,13 | `DATA` |
| *(12 lainnya)* | — | — | — | — | ±0,01–0,10 | `DATA` |

17 dari 18 selisihnya kecil (±0,01–0,18) — konsisten dengan pembaruan nilai
setelah migrasi. Satu outlier besar **sudah terjelaskan**: spesifikasi POY-nya
berubah dari `500/96/TBL/DBR` ke `250/48/RND/DSD`, jadi LDR berbeda adalah
konsekuensi wajar. Keputusan IT Lead: **disengaja**.

**Anomali range** (LDR di luar 1.0–5.0):

| Sumber | Produk | Nilai LDR | Catatan |
|---|---|---:|---|
| Legacy `CMBS_RUN_LDR_PRSN` | 125 spin | > 5,0 | |
| Legacy | 38 spin | < 1,0 | |
| goapps `mbs_run_ldr_pct` | 122 spin | > 5,0 | |
| goapps | 37 spin | < 1,0 | |
| **Out-of-range di DUA sisi** | **152 spin** | — | **diwarisi legacy**, bukan cacat sistem baru |
| Out-of-range **hanya di goapps** | **5 spin** | — | 🔴 ini yang layak diperiksa |
| Out-of-range hanya di legacy | 2 spin | — | |

Pertanyaan yang benar bukan "berapa yang di luar range" tapi **"diwarisi atau
baru muncul"** — dan **152 dari ~159 diwarisi**. Keputusan IT Lead: pembersihan
master data R&D, bukan isu migrasi.

**Multi-varian per POY spec** (legacy punya `-01`/`-02`):

| POY spec | Jumlah varian legacy | Bagaimana di-resolve di goapps | Risiko |
|---|---:|---|---|
| `POY 250/48/RND/DSD` | **530 spin** | tidak diagregasi — dibandingkan per spin | agregasi per spec akan **meratakan** LDR 1,00–1,2+ yang memang berbeda |
| `POY 250/36/RND/DSD` | 200 spin | idem | idem |
| `250/48/RND/DSD` | 187 spin | idem | idem |
| **Total** | **140 spec punya >1 spin (2.151 spin)**; **121 spec LDR-nya berbeda antar varian** | perbandingan **per spin** (`CMBS_SYS_ID`) | instruksi prompt "bandingkan per POY spec" **tidak valid** untuk data ini |

Suffix `-01`/`-02` **bukan** hirarki: `mbs_parent_spin_id` NULL untuk seluruh
2.799 baris. Suffix hidup di `mbs_shade_code` (1.776 baris).

**Tambahan — keputusan #4 yang diminta IT Lead:** filter cost type di query
dosing **tidak aman dihilangkan**. Dari 1.959 produk yang punya nilai TOP 71 di
kedua cost type, **342 (17,5%) berbeda** — tanpa filter, `MAX()` diam-diam
memilih nilai yang lebih besar.

Dua nilai `CMBS_LDR_PRSN` legacy **tidak numerik** (`2.00%` dan
`3.00POY 445/96/RND/DSD`) — `TO_NUMBER()` langsung akan gagal ORA-01722.
Keputusan IT Lead: nol-kan di query.

CSV: `out/cmp_03_mb_ldr_dosing_202608.csv`

---

### 4.4 FG Product Cost

**Ringkasan per cost type**

| Cost type | Produk dibandingkan | MATCH (<0.1%) | MINOR | MATERIAL | SCOPE | Rata-rata \|selisih %\| |
|---|---:|---:|---:|---:|---:|---:|
| ACTUAL | 6.978 | 1 | ⚠️ 57 | 🔴 6.920 | ◻️ 52 | *tidak dilaporkan — lihat catatan* |
| SELLING | 13.422 | 0 | 0 | 🔴 13.422 | 0 | *idem* |
| FORECAST | — | — | — | — | ◻️ **seluruhnya** | legacy tidak punya track forecast |

Angka di atas untuk metric final **`CAP_WITH_QLOSS`** (legacy TOP 105 ↔
`CAPTIVE_COST_QLTY_LOSS`). Rata-rata selisih % **sengaja tidak dilaporkan**
karena akan menyesatkan: mayoritas selisih adalah hilir dari satu defect (§4.6),
bukan sebaran independen.

**Perbandingan berlapis** — ini yang menentukan kesimpulan:

| Layer | Metric | ACTUAL setara | SELLING setara |
|---|---|---:|---:|
| **1 — Parameter input** | `OPU` (TOP 22) | **99,9%** | 99,8% |
| | `WASTE_PCT` (TOP 21) | **96,2%** | 95,4% |
| | `RM_NORM` (TOP 57) | **88,9%** | 82,2% |
| **2 — Fixed cost / kg** | `OVERHEAD_PER_KG` (TOP 89) | 24,1% | 47,7% |
| | `SPARES_PER_KG` (TOP 90) | 20,7% | 44,7% |
| | `POWER_PER_KG` (TOP 87) | 15,3% | 40,5% |
| | `FIXED_TOTAL_KG` (TOP 91) | 13,9% | 34,1% |
| | `RM_LANDED_COST` (TOP 56) | 8,5% | **0,0%** |
| **3 — Cost turunan** | `CAP/DEL_WITH_QLOSS`, VB1–5, conversion | **0,1–0,6%** | **0,0%** |

**Parameter produk termigrasi dengan baik** (88,9–99,9%). Yang tidak sebanding
adalah cost turunan — dan §4.5/§4.6 menunjukkan itu hilir, bukan sebab.

**Distribusi selisih (ACTUAL)** — populasi in-scope 202608, metric `CAP_WITH_QLOSS`

| Bucket selisih % | Jumlah produk | % dari total |
|---|---:|---:|
| < 0.1% | 1 | 0,1% |
| 0.1% – 0.5% | 15 | 1,2% |
| 0.5% – 1% | 46 | 3,6% |
| 1% – 5% | 139 | 11,0% |
| > 5% | **1.068** | **84,2%** |
| **Total** | **1.269** | 100% |

**Pola in-scope vs out-of-scope ORION** — penting untuk membaca angka dengan benar:

| ACTUAL metric | In-scope 202608 (1.269) | Di luar scope (5.709) |
|---|---:|---:|
| `RM_LANDED_COST` | **42,5%** | **0,9%** |
| `OVERHEAD_PER_KG` | 50,5% | 18,3% |
| `FIXED_TOTAL_KG` | 41,8% | 7,7% |
| `OPU` / `WASTE_PCT` / `RM_NORM` | 86–100% | 90–100% |

Parameter master setara di dua populasi (wajar — tidak bergantung periode).
Tapi **rate & fixed cost setara ~42–50% untuk produk yang benar-benar
bertransaksi di 202608, dan hanya ~1–18% untuk yang tidak** — jadi sebagian
besar selisih di luar scope adalah **umur data legacy**, bukan defect.

**Selisih material (top N, ACTUAL)**

| CYL_SYS_ID | product_sys_id | Nama produk | Legacy cost/unit | goapps cost/unit | Selisih | Selisih % | Layer penyebab | Kategori |
|---|---|---|---:|---:|---:|---:|---|---|
| — | CSTATY2606000188 | ATY 1764/960/RND/RSDFR-DSD | — | RM 3,0000 | **+7,9855** *(seharusnya)* | **73%** understated | Intermediate | `ENGINE` |
| — | CSTPTY2606002610 | PTY 1800/576/RND/DSD-DSD/HIM | — | RM 4,0000 | +7,8897 | ~66% | Intermediate | `ENGINE` |
| — | CSTPTY2606002616 | PTY 1800/576/RND/DSD-DSD/IM | — | RM 4,0000 | +7,8704 | ~66% | Intermediate | `ENGINE` |
| — | CSTATY2606000237 | ATY 370/108/RND/DSD/NIM/DH | — | RM 3,0000 | +7,2724 | ~71% | Intermediate | `ENGINE` |
| — | CSTATY2606000187 | ATY 1764/960/RND/RSDFR-BSD | — | RM 3,0000 | +6,2898 | ~68% | Intermediate | `ENGINE` |

*(Nilai `Legacy cost/unit` tidak dicantumkan untuk baris ini karena
perbandingannya diblokir defect §4.6 — yang dilaporkan adalah understatement
internal sisi baru, yang terukur pasti.)*

CSV: `out/cmp_04_fg_product_cost_202608_summary.csv` · `out/cmp_04_fg_product_cost_202608_diff.csv`

---

### 4.5 FG Calculation Breakdown

**Selisih per layer** — menjawab "beda di mana", bukan cuma "beda berapa".
Populasi: ACTUAL in-scope 202608 (1.269 produk), metric final `CAP_WITH_QLOSS`.

| Layer | Produk terdampak | Kontribusi ke total selisih | Kategori dominan |
|---|---:|---:|---|
| **RM cost** | **730** | **57,5%** | `DATA` + `ENGINE` |
| Komposisi / BOM | 0 | 0% | — (recipe sehat, §4.2) |
| LDR / MB consumption | 0 | 0% | — (LDR 96,1% setara, §4.3) |
| Parameter produk | 177 (waste 71 + RM norm 106) | 14,0% | `DATA` |
| Conversion — FIXED_TOTAL | 12 | 0,9% | `DATA` |
| **Conversion — PACKING** | **349** | **27,5%** | `ENGINE` — signature ~2× |
| Conversion — COST_OTHERS | 0 | 0% | — |
| Setara | 1 | 0,1% | — |
| **DEFINISI_AGREGAT** (semua input setara, output beda) | **0** | **0%** | — |

**Nol kasus `DEFINISI_AGREGAT` adalah temuan terpenting fase ini**: tidak ada
satupun produk di mana semua input setara tapi hasil akhirnya berbeda. **Formula
engine konsisten; yang berbeda adalah input.** Ini membalik kekhawatiran bahwa
layer cost turunan "rusak" — ia berbeda semata karena mewarisi selisih dari hulu.
Dan **85% selisih in-scope terjelaskan oleh dua layer saja.**

**Arah dampak** — rasio `CAP_WITH_QLOSS` goapps/legacy per layer penyebab:

| Layer | n | p10 | median | p90 |
|---|---:|---:|---:|---:|
| RM_COST | 730 | 0,610 | **0,774** | 0,853 |
| PACKING | 349 | 0,843 | 0,940 | 1,005 |
| PARAMETER_NORM | 106 | 0,726 | 0,885 | 0,953 |
| PARAMETER | 71 | 0,779 | 0,868 | 0,972 |
| FIXED_COST | 12 | 1,008 | 1,025 | 1,031 |

**Sistem baru menghasilkan FG cost lebih rendah dari legacy di hampir semua
layer — sekitar 23% lebih rendah pada kasus RM.** Ini yang paling perlu
disampaikan ke Finance sebelum cutover.

**Verifikasi identitas dekomposisi di goapps**

| Identitas | Produk dicek | Konsisten | Tidak konsisten |
|---|---:|---:|---:|
| `total = rm_cost + conversion` | 17.611 | 16.566 (94,1%) | 🔴 **1.045** |
| `conversion = FIXED + WASTE + OTHERS` | — | **N/A** | **N/A** |
| `MB_FINAL = MB_RM + MB_CONV` | 4.189 | *(lihat catatan)* | *(lihat catatan)* |

**`conversion = FIXED + WASTE + OTHERS` — N/A untuk yarn.** Bentuk sebenarnya
dari `cpc_formula_trace`:
`ONLY_CONV_CAP_PACK_EXCL_MB = TOTAL_FIXEDCOST_PER_KG + CAPTIVE_PACK_COST +
OIL_COST + INTERMINGLING + SPECIAL_COST_1`, dan waste masuk lewat `RM_NORMS`,
bukan sebagai suku terpisah. Dekomposisi yang disebut prompt cocok untuk **MB**.

**`MB_FINAL = MB_RM + MB_CONV` — tidak bisa diverifikasi dari `cst_mb_cost`**:
tabel itu hanya menyimpan hasil akhir per cost type (`mbc_cost_type` ∈
{ACTUAL, SELLING, FORECAST}), **bukan** komponen. Komponennya hidup di
`cpc_formula_trace` produk MB, dan formulanya **terverifikasi dengan dua koreksi
atas prompt**:

| Prompt | Engine sebenarnya |
|---|---|
| `MB_FINAL_COST = MB_RM_COST + MB_CONV_COST` | `IS_BOUGHTOUT == 1 ? MB_RM_COST : MB_RM_COST + MB_CONV_COST` — **ada cabang boughtout** |
| `MB_CONV_COST = MB_FIXED_TOTAL + MB_WASTE_VAL + MB_COST_OTHERS` | `(MB_NO_PROCESS * MB_FIXED_TOTAL) + MB_COST_OTHERS + MB_WASTE_VAL` — **ada pengali `MB_NO_PROCESS`** |
| `MB_FIXED_TOTAL = MACHINE_MB_FIXED_TOTAL / MB_NET_PROD` | ✅ sesuai (+ guard `MB_NET_PROD > 0`) |
| `MB_NET_PROD = MB_THROUGHPUT * (MB_EFFICIENCY/100) * MB_PROD_PER_DAY` | ✅ **persis** |
| `MB_WASTE_VAL = (MB_RM_COST/(1−MB_WASTE/100)) * (MB_WASTE/100)` | ✅ **persis** |

⚠️ Cabang `IS_BOUGHTOUT` bertaut dengan §4.2: kalau formula membaca
`mbh_is_boughtout` (SSOT per keputusan IT Lead), maka **130 head boughtout yang
belum ter-flag salah jalur formula** — dapat `RM + CONV` padahal semestinya `RM`
saja.

**`cpc_rm_cost_detail` (match-UI) vs `cst_rm_cost` (rate saat ini)**

| Metrik | Nilai |
|---|---:|
| Produk yang rate-nya identik di dua sumber | **25.726 baris (100%)** |
| Produk yang rate-nya sudah berbeda | **0** |
| Selisih % terbesar akibat perbedaan ini | **— (tidak ada)** |

Peringatan prompt **tidak terbukti untuk 202608**, dan sebabnya jelas: 202608
di-recompute **2026-09-09 09:01–09:13**, setelah rate RM ditetapkan. Jadi "apa
yang user lihat" == "apa yang seharusnya". Ini temuan negatif yang tetap penting:
kekhawatiran itu **akan** materialisasi kalau rate diubah tanpa recompute —
misalnya setelah paket remediasi `01`/`02` dijalankan.

**Dua perbedaan DEFINISI (bukan perbedaan data):**

1. **Landed cost kolaps jadi rate.** `F_YARN_RM_LANDED` isinya literal
   `RM_LANDED_COST = RM_RATE`. Legacy membedakan TOP 55 (rate) dan TOP 56
   (landed), dan menerapkan uplift ke **2.275/7.460 ACTUAL (30,5%)** dan
   **4.603/14.622 SELLING (31,5%)**. Di goapps `landed == rate` di **13.421 dari
   13.422 (100%)** → **uplift duty/clearing/freight hilang.** Kategori `ENGINE`.
2. **MB cost dikeluarkan dari conversion yarn** (`ONLY_CONV_CAP_PACK_EXCL_MB`),
   sementara legacy memasukkannya. Rekonstruksi TOP 94 cocok **45,8% dengan MB**
   vs **18,4% tanpa** — 2,5× lebih baik. Kategori `ENGINE`.

**Contoh telusur end-to-end** (produk uji `left_no 79`, MARKETING)

| Langkah | Legacy | goapps | Selisih |
|---|---:|---:|---:|
| RM rate (TOP 55 / `RM_RATE`) | 1,1720 | 1,054714 | −0,117286 |
| RM landed (TOP 56 / `RM_LANDED_COST`) | 1,1908 | 1,054714 | −0,136086 |
| RM norm (TOP 57 / `RM_NORMS`) | 0,9759 | 0,976300 | +0,000400 |
| = RM cost (norm × landed) | 1,16214 | 1,029716 | −0,132424 |
| + Fixed total (TOP 91) | 0,2452 | 0,245046 | −0,000154 |
| + Packing (TOP 42) | 0,0078 | 0,015777 | **+0,007977** |
| + Oil (TOP 61) | 0,0115 | 0,009988 | −0,001512 |
| + MB cost (TOP 73) | 0,0748 | **tidak termasuk** | **−0,074800** |
| = Cost before Q.Loss (TOP 94) | 1,4992 | 1,300528 | −0,198672 |
| + Quality loss (TOP 103) | 0,0350 | 0,011054 | −0,023946 |
| = **Total (TOP 105)** | **1,5342** | **1,311583** | **−0,222617 (−14,5%)** |

Telusur ini memperlihatkan keempat penyebab sekaligus dalam satu produk: RM
landed lebih rendah, packing 2× lebih tinggi, MB cost hilang, dan quality loss
lebih rendah.

CSV: `out/cmp_05_fg_breakdown_202608.csv`

---

### 4.6 Intermediate Product

**Ringkasan**

| Level | Produk dibandingkan | MATCH | MATERIAL | SCOPE |
|---|---:|---:|---:|---:|
| Level 1 | 17.386 (goapps) vs 1.032 (legacy) | **N/A** | **N/A** | beda representasi |
| Level 2 | 9.065 vs 681 | N/A | N/A | idem |
| Level 3–9 | 2.975…52 vs 450…18 | N/A | N/A | idem |
| **Level 10–13** | **11/5/2/1 vs 9/4/1/1** | ✅ **konvergen** | — | — |

**Cost per level: `BLOCKED-BY-DEFECT`, bukan `UNKNOWN`.** Selama `unit_cost`
produk hulu masih 1 di 89,1% baris, setiap perbandingan cost intermediate hanya
akan mengukur defect di bawah — bukan selisih antar sistem. Angkanya akan valid
secara aritmetika tapi menyesatkan sebagai kesimpulan recon.

**Struktur DAG: termigrasi benar.** Max depth **13 di kedua sisi**, dan di level
dalam (≥10) angkanya nyaris identik. Selisih besar di level 1–3 adalah **beda
representasi**: legacy hanya menyimpan baris DAG untuk produk yang punya produk
hulu; goapps menulis entri level 1 untuk **setiap** produk.

**Selisih per level → digantikan oleh tabel defect propagasi:**

| Metrik | Nilai |
|---|---:|
| Baris route bertipe `PRODUCT` | 9.780 |
| **`unit_cost` tepat 1 (placeholder)** | **8.718 (89,1%)** 🔴 |
| …yang produk hulunya **punya cost 202608 riil** | **8.718 (100%)** |
| Rata-rata cost hulu yang tersedia tapi tidak dipakai | **2,4092** |
| **Parent product terdampak** | **8.386 dari 9.321 (90,0%)** |
| Median understatement per baris | **1,9859** |
| **Total understatement** | **12.285,76** satuan cost |

Pembanding yang membuktikan 1 adalah placeholder: tipe `GROUP` (25.726 baris)
**tidak pernah** menghasilkan tepat 1; tipe `PRODUCT` menghasilkannya 89,1%.
Dan formula engine jelas berniat resolve:
`CASE rm_type WHEN 'PRODUCT' THEN upstream_product(...).COST_CAP_FINAL …`.

**Ini menjelaskan gejala di §4.4 sebagai satu akar penyebab:** `total_rm` tepat
1,000000 di 678 produk · nilai **bulat** 3,0/4,0 (menjumlahkan `1 × ratio` per
produk hulu) · `captive` 1,000000 di 96,7% · hanya 24 nilai distinct vs 488 di
legacy · dan arah "FG cost ~23% lebih rendah".

**Anomali struktur route**

| Jenis anomali | Jumlah | Contoh |
|---|---:|---|
| Route tanpa sequence | **0** | — |
| Komponen polymorphic tidak konsisten | **0** | aturan "tepat satu kolom terisi" berlaku **100%**: GROUP 35.672 → group_code, PRODUCT 17.433 → product_sys_id, nol silang. ⚠️ tipe **`ITEM` nol baris** padahal ada `idx_crm_rm_item` |
| Cycle terdeteksi (goapps) | **0** self-reference | fan-out rata-rata 1,84, **maksimum 117** |
| Cycle terdeteksi (**legacy**) | 🔴 **16.349 baris** | `CST_LVL_LEFT_PROD` ber-`CLLP_LEFT_NO` NULL: 82% tabel, hanya 198 produk (~82 baris/produk), **level sampai 53** → runaway/cycle. Subset bersih (3.488 baris/2.593 produk) max level 13 |
| Sum ratio ≠ 1.0 | *tidak diuji* | tidak bermakna sebelum defect propagasi diperbaiki |

**Temuan performa:** `EXPLAIN` menunjukkan `Seq Scan on cost_route_rm` di
**setiap** langkah rekursi. Index ada untuk kolom **hulu**
(`crm_rm_group_code`, `crm_rm_item_code`, `crm_rm_product_sys_id`, `crm_seq_id`)
tapi **tidak ada untuk `crm_parent_product_sys_id`** — justru kolom filter "route
milik produk X" dan kolom join traversal. Traversal produk terdalam tidak selesai
dalam waktu wajar dan **query dihentikan** (aturan prompt soal query berat di
production). Guard depth pada recursive CTE karena itu **wajib**.

CSV: `out/cmp_06_intermediate_product_202608.csv` (8.718 baris terdampak, siap
diserahkan ke dev)

---

## 5. Cost Type SELLING & FORECAST

*Dilaporkan terpisah karena toleransi dan tingkat kematangannya berbeda dari ACTUAL.*

| Cost type | Produk dibandingkan | MATCH | MATERIAL | Catatan / known-gap |
|---|---:|---:|---:|---|
| SELLING — RM | 350 group | 152 | 🔴 126 | KG-05: group marketing rate resolve ke 0 — **42 group** nol karena tier `NONE` |
| SELLING — FG | 13.422 produk | 0 | 🔴 13.422 | **RM SELLING nol di 13.421 dari 13.422** → tidak ada layer lain yang bisa dinilai |
| SELLING — LDR Rencana | 2.802 spin | 1.952 | 2 | ✅ justru **paling sehat** dari semua metric |
| FORECAST — RM | 350 group | 0 | 11 | ◻️ legacy `CGCH_SIMULATION_RATE` hanya **11 non-null dari 924**; goapps `cost_sim` **0 untuk seluruh 350** |
| FORECAST — FG | — | — | — | ◻️ legacy tidak punya track forecast |
| FORECAST — MB | 4.189 | — | — | ⚠️ **MB FORECAST TERISI** (4.174 non-nol) — berbeda dari RM/FG yang nol |

**Rekomendasi:**

- **SELLING belum layak untuk sign-off.** Dua alasan yang berdiri sendiri:
  (a) RM SELLING nol di sisi baru sehingga seluruh FG SELLING tidak punya dasar;
  (b) **pembandingnya di legacy masih bergerak** (`_CUR` MARKETING +32 baris,
  terakhir diubah 15:25:01) — jadi bahkan kalau (a) beres, angkanya belum stabil.
  **Perlu tanggal cut-off legacy.**
- **FORECAST dikeluarkan dari angka utama** (keputusan IT Lead) karena legacy
  tidak punya pembanding. Tapi catat asimetrinya: **MB FORECAST terisi**
  sementara RM/FG nol — jangan disalahbaca sebagai "forecast sudah jalan".
- **Batasi sign-off ke ACTUAL saja** untuk sekarang.
- Satu hal yang **sudah sehat di track SELLING**: LDR Rencana
  (`CMBS_LDR_PRSN` ↔ `mbs_ldr_prsn`) hanya 2 selisih dari 1.954 — itu bisa
  dinyatakan reconciled.

---

## 6. Produk Baru & Gap Identitas

### 6.1 Ringkasan bucket

| Bucket | Jumlah | Nilai cost 202608 terdampak |
|---|---:|---:|
| Ada di goapps, tidak ada di legacy (produk baru) | **26** | **0 — tidak satupun punya cost 202608** |
| Ada di legacy, tidak ada di goapps (gap migrasi) | **1.297** | 194 yarn in-scope + 1 MB `Current` = **195 mendesak** |
| Ada di keduanya, identitas tidak match (masalah crosswalk) | 1.719 | **0 — bukan crosswalk salah**, lihat catatan |

⚠️ **Koreksi penting.** Bucketing pertama memakai `cpm_flex_02` sebagai
satu-satunya pointer legacy dan menghasilkan **242** kandidat produk baru. Itu
salah: untuk MB, provenance juga ada di head lewat `mbh_cost_product_id` →
`mbh_oracle_sys_id`. **216 dari 220 produk MB tanpa `flex_02` ternyata head-nya
PUNYA oracle id** — mereka produk lama yang pointernya tidak diteruskan. Bucket A
yang benar = **26**.

**Bucket C bukan masalah crosswalk**, dan penting untuk tidak melaporkannya
begitu:

- **Nama produk tidak bisa dipakai sebagai kunci identitas**: 17.937 produk
  hanya punya **8.642 nama distinct**, dan satu nama
  (`POY 250/48/RND/DSD/SIM/NS/1/O`) dipakai **1.016 produk**. Nama yarn adalah
  **deskriptor spec**, bukan identitas.
- **Identitas dijamin pointer eksplisit** yang sudah dibuktikan: MB lewat
  `mbh_oracle_sys_id` (4.226/4.226 distinct), yarn lewat `(left_no, prs_type)`
  (1:1 terbukti).
- Yang sebenarnya terjadi: **1.636 nama MB ditulis ulang** (`Light Fann` →
  `LIGHT FAWN` perbaikan typo, `Gris Gray` → `GRIS GREY` ejaan,
  `Buttercup Yellow BR` → `BUTTER YELLOW TR-1027-C` disingkat) dan **83 yarn
  beda konvensi suffix**.
- **Uji identitas yang sah — kecocokan tipe — memberi 82,5% cocok**, dan 17,5%
  ketidakcocokannya **sistematis**: remapping taksonomi `PLY`→PTY (554),
  `SUPERBA`→TTS/TCS (615), `MEEREBAH`→TCM/TTM/TCY (242), `MELANGE`→PTY (106).
  Sebagian dari 17,5% itu keterbatasan peta alias kami sendiri, jadi angka
  sebenarnya **lebih baik** dari 82,5%.

### 6.2 Definisi produk baru

*Satu blok per produk. Duplikasi tabel ini sebanyak yang diperlukan.*
Lengkapnya 26 baris di `out/cmp_07_produk_baru_definisi_202608.csv`. Tiga blok
representatif:

| Field | Isi |
|---|---|
| `product_sys_id` | 40909 |
| Nama produk | `CABLING` |
| `cpm_product_type_id` (MB = 29) | **31 (KYP)** |
| ERP item code | belum ter-link |
| Dibuat kapan / oleh siapa | 2026-08-28 09:20 / `c416905f-8c01-4414-9fd6-3d973716a261` |
| Punya route? | **Tidak** |
| Punya parameter lengkap? | **Tidak (0)** |
| Punya cost 202608? (ACTUAL/SELLING/FORECAST) | **Tidak (0)** |
| Alasan tidak ada di legacy | **entri kategori proses, bukan produk yang di-cost** |
| Perlu tindak lanjut? | Ya — pastikan apakah entri kategori memang seharusnya tinggal di `cost_product_master` |

| Field | Isi |
|---|---|
| `product_sys_id` | *(kode `FORK1`)* |
| Nama produk | `POY 250/48/RND/DSD/SIM/NS/1/O` |
| `cpm_product_type_id` (MB = 29) | 1 (POY) |
| ERP item code | belum ter-link |
| Dibuat kapan / oleh siapa | 2026-06-30 / `cfd19f92-77c0-4eb1-a02c-9ffcbf774eb0` |
| Punya route? | **Tidak** |
| Punya parameter lengkap? | Ya — **119 parameter** |
| Punya cost 202608? | **Tidak** |
| Alasan tidak ada di legacy | **artefak fork/test di database production** |
| Perlu tindak lanjut? | Ya — **dibersihkan**. Ada 4: `FORK1`, `FORK2`, `FORK3`, `CSTPTY2606002798_F1` |

| Field | Isi |
|---|---|
| `product_sys_id` | *(kode `CSTMB2608000001`)* |
| Nama produk | `MGT ALPAKA CM 2212-CS-D-05065-B` |
| `cpm_product_type_id` (MB = 29) | **29 (MB)** |
| ERP item code | belum ter-link |
| Dibuat kapan / oleh siapa | 2026-08-11 / **`backfill-mb-validate`** |
| Punya route? | **Ya (1)** |
| Punya parameter lengkap? | Ya — 11 parameter |
| Punya cost 202608? | **Tidak** |
| Alasan tidak ada di legacy | **MB dibuat oleh proses backfill di sistem baru** |
| Perlu tindak lanjut? | Tidak mendesak — belum di-cost |

Komposisi 26 produk: **KYP 9** (entri kategori proses) · **TTY 6** + TTH 1 +
PTY 1 (spec trial denier 6000) · **MB 4** (`backfill-mb-validate`) ·
**4 artefak fork/test** · POY 2.

> Catatan: **product name adalah single source of truth** untuk resolusi tipe produk.
> ERP item code kosong TIDAK berarti produk invalid — produk trial/sample bisa
> eksis di goapps sebelum terdaftar di ERP.

**Peringatan itu dipatuhi, dan klaimnya dievaluasi.** Seluruh 26 produk ber-ERP
kosong, dan **nol** disimpulkan sebagai produk hantu — penentuannya lewat
route/parameter/cost/`cpm_source`/pembuat. Soal klaim SSOT: prefix nama == tipe
berlaku di **13.636 dari 17.937 (76,0%)**, dan **4.290 dari 4.301 ketidakcocokan
adalah MB** yang namanya nama shade tanpa token tipe. Jadi klaim **berlaku untuk
yarn, tidak berlaku untuk MB**. Satu inkonsistensi nyata ditemukan: ada produk
bertipe **POY** yang bernama `FDY 50/36/RND/SD/SIM/NS/1/O`.

### 6.3 Gap migrasi (ada di legacy, hilang di goapps)

| Legacy key | Nama | Cost legacy 202608 | Status di goapps | Dugaan penyebab | Prioritas |
|---|---|---:|---|---|---|
| **194 yarn `left_no`** | berbagai (PTY 418 / POY 209 / TTY 119 dari total 1.043) | **ada di `_CUR`** | tidak ada | **bertransaksi di 202608 menurut ORION** | 🔴 **Tinggi** |
| **1 MB head** | — | — | tidak ada | berstatus **`Current`** (produksi) | 🔴 **Tinggi** |
| 849 yarn `left_no` | berbagai | ada di `_CUR` | tidak ada | di-cost legacy tapi **tidak** bertransaksi 202608 | Sedang |
| 2 yarn `left_no` | — | **tidak ada** | tidak ada | tidak di-cost legacy | Rendah |
| 253 MB head | — | — | tidak ada | 162 `Waiting` + 90 `Boughtout` + 1 null → draft | Rendah |
| 13 RM group | `NOVANIK-1010`, `TIO2 LCS`, `PIG0000085`, 10 spec yarn | landed cost ada di 5 | tidak ada | **12 dari 13 tidak dipakai satu resep legacy pun**; 10 dari 13 adalah **spec yarn** yang semestinya PRODUCT, bukan RM group | Rendah |

**Prioritas yang benar: 195 objek**, bukan 1.297. Kriteria pemisahnya objektif
(punya cost `_CUR` **dan** masuk scope transaksi ORION 202608).

CSV: `out/cmp_07_legacy_only_products_202608.csv` · `out/cmp_01_rm_legacy_orphan_202608.csv` · `out/cmp_07_new_products_202608.csv`

---

## 7. Klasifikasi Selisih (Pivot)

| Kategori | Jumlah objek | Nilai impact (Rp / unit) | % dari total selisih | Owner tindak lanjut |
|---|---:|---:|---:|---|
| `DATA` | **1.086** | — *(lihat catatan)* | ~11% | Indra (SQL) |
| `ENGINE` | **8.749** | **12.285,76** satuan cost (understatement RM) | ~87% | Ilham (spec `ENG-*`) |
| `SCOPE` | **2.253** | — | ~2% | Indra + R&D |
| `KNOWN-GAP` | **619** | — | — (sudah diterima) | — |
| `UNKNOWN` | **1.045** | — | belum terkuantifikasi | perlu investigasi lanjutan |
| **Total** | **13.752** | — | 100% | |

**Rincian per kategori:**

| Kategori | Isi |
|---|---|
| `DATA` | 78 group RM material + 104 spin LDR + 177 produk parameter FG + 727 baris resep/komposisi |
| `ENGINE` | **8.718 baris propagasi intermediate** (§4.6) + 349 packing 2× (§4.5) + landed-cost kolaps (§4.5) + MB-cost-excl (§4.5) |
| `SCOPE` | 195 gap migrasi mendesak + 849 gap prioritas sedang + 726 line resep + 339 FORECAST + 26 produk baru + 102 spin legacy-only |
| `KNOWN-GAP` | 561 placeholder spec POY + 5 item multi-group + 250 nested MB head + 3 lain |
| `UNKNOWN` | **1.045 baris `total_cost ≠ total_rm + total_conversion`** (§4.5) — satu-satunya yang belum bisa dijelaskan |

**Catatan nilai impact.** Kolom Rp/unit **sengaja tidak diisi selain satu angka
yang terukur pasti** (understatement 12.285,76 satuan cost dari §4.6). Alasannya:
konversi ke rupiah butuh volume produksi 202608 per produk, yang di luar cakupan
recon ini. Mengisi kolom itu dengan estimasi akan memberi kesan presisi yang
tidak dimiliki datanya. Yang bisa dinyatakan dengan yakin: **arah dampaknya
searah dan besar — FG cost sisi baru ~23% lebih rendah pada kasus RM.**

---

## 8. Known-Gap (Sudah Diterima Sebelumnya)

*Dipisah tegas dari temuan baru supaya stakeholder tidak menganggap ini regresi.*

| ID | Kategori | Deskripsi singkat | Objek terdampak periode ini | Status |
|---|---|---|---:|---|
| KG-01 | MB | Item multi-group constraint conflict | **5 item riil** (`CHP0000036`, `PIG0000024`, `CHP0000040`, `PIG0000005`, `PIG0000016`) + 1 string kosong di 23 baris dummy | Diterima — **tidak lagi memblokir recon** karena kunci join tidak melewatinya |
| KG-02 | MB | Finance-blocked group-0 rate | **25 group ACTUAL** + 42 SELLING nol karena `flag_*_used='NONE'` | Menunggu Finance — **keputusan IT Lead: lengkapi tier config, bisa fixed value.** Script siap: `remediation/02` |
| KG-03 | MB | Nested-MB compound case | **250 head / 353 line** (`mbcv_source_type='MB'`), `mb_ref` terisi 100% | Diterima |
| KG-04 | RM | FL rate backfill genuinely data-gap | **1 group** ber-tier `FL` (`202007619` LICOWAX-E), selisih 51,41% | Menunggu Finance |
| KG-05 | SELLING | Group marketing rate resolve ke 0 | **42 group** RM SELLING + merambat ke **13.421 produk FG SELLING** | Menunggu keputusan |
| KG-06 | RM | Placeholder spec POY di `CST_GRP_CONSUMP_HEAD` | **561 group** legacy tanpa item aktif (mis. `160/36 RSD`) | Diterima — bukan gap migrasi |
| KG-07 | MB | `mbcv_is_carrier` tidak pernah di-set | 21.863 baris | **Diterima — keputusan IT Lead: tinggalkan** |
| KG-08 | MB spin | `CMBS_CHECK_STATUS` tidak dimigrasikan | konsep `Current` (644 spin legacy) hilang | **Diterima — keputusan IT Lead: biarkan, mekanismenya berbeda** |
| KG-09 | MB spin | LDR out-of-range warisan legacy | **152 spin** out-of-range di dua sisi | Diterima — pembersihan master data R&D |

---

## 9. Temuan Baru & Rekomendasi

### 9.1 Perlu perbaikan data (owner: Indra, via SQL)

| # | Temuan | Objek terdampak | Usulan perbaikan | Prasyarat |
|---|---|---:|---|---|
| D-1 | Tier RM tidak resolve → rate 0 padahal legacy punya nilai | **32 group** | `flag_*='INIT'` + `init_val_*` dari rate legacy 202608. **Script siap: `remediation/02_rm_fixed_value_init.sql`** | Konfirmasi Finance (F-1) |
| D-2 | Group code salah dipetakan saat migrasi | **8 remap aktif**, dampak **259 route + 258 resep** pada satu group | **Script siap: `remediation/01_rm_group_code_remap.sql`**. 6 dari 11 target punya `cost_val` **persis sama** dengan rate legacy sumbernya — bukti klarifikasi benar | Re-Validate MB lewat UI setelahnya |
| D-3 | Komponen `SPC 3221` gagal termigrasi → 4 resep sum ≠ 100 | **4 resep** (+1 di D-4) | Insert baris komposisi ke `RED MGTP-3221` (`202007188`). **Script siap: `remediation/05`** | — |
| D-4 | Komposisi 30 resep MB tidak termigrasi | **30 head / 164 baris** | **Script siap: `remediation/04`**. 156 baris GROUP + 4 nested MB (target `mbcm_mb_ref_mbh_id`, bukan group) | Validate lewat UI supaya versi terbentuk |
| D-5 | `mbs_ldr_adjustment_pct` NULL di seluruh 2.799 baris | **923 spin** | `adjustment = run_ldr_pct − calculated_pct`. **Script siap: `remediation/06`** | — |
| D-6 | Pointer legacy MB tidak diteruskan ke `cpm_flex_02` | **216 produk** | Backfill dari `mst_mb_head.mbh_oracle_sys_id` | — |
| D-7 | 130 head `check_status='Boughtout'` tapi `mbh_is_boughtout=false` | **130 head** | Flag sesuai SSOT. **Bertaut dengan E-4** — bisa mengubah jalur formula | Konfirmasi dev (E-4) |
| D-8 | 195 produk legacy hilang di goapps | **194 yarn + 1 MB `Current`** | Migrasikan atau nyatakan retired | Keputusan R&D (R-1) |
| D-9 | Artefak fork/test di production | **4 produk** (`FORK1`–`3`, `..._F1`) | Hapus | — |
| D-10 | 5 spin LDR out-of-range **hanya** di goapps | 5 spin | Periksa dan koreksi | — |

### 9.2 Perlu perbaikan engine (owner: Ilham, via spec `ENG-*`)

| # | Temuan | Lokasi kode terduga | Calon ID spec | Prioritas |
|---|---|---|---|---|
| **E-1** | **Cost produk antara tidak dipropagasi — `unit_cost=1` di 8.718 baris (89,1%) padahal nilai hulu tersedia (rata-rata 2,41)** | resolusi `upstream_product(rm_product_legacy_id).COST_CAP_FINAL` di jalur `costcalc` | `ENG-INTERMEDIATE-PROP` | 🔴 **P0 — blocking sign-off** |
| **E-2** | **Packing cost ~2× legacy, 349 dari 349 kasus** (median rasio 1,975; p25 1,957 / p75 1,991) | `F_YARN_CAP_PACK` / `F_YARN_DEL_PACK` — periksa `CAPTIVE_NO_OF_BOB`, `CAPTIVE_BOX_WT`, `DELIVERY_*` | `ENG-PACK-DOUBLE` | 🔴 **P0** |
| E-3 | Landed cost kolaps jadi rate — uplift duty/clearing/freight hilang di ~31% produk | `F_YARN_RM_LANDED` (`RM_LANDED_COST = RM_RATE`) | `ENG-RM-LANDED` | 🟠 P1 |
| E-4 | Cabang `IS_BOUGHTOUT` × 130 head yang flag-nya belum di-set | `F_MB_FINAL_COST` | `ENG-MB-BOUGHTOUT` | 🟠 P1 |
| E-5 | `total_cost ≠ total_rm + total_conversion` di 1.045 baris | agregasi `cst_product_cost` | `ENG-DECOMP-BREAK` | 🟠 P1 |
| E-6 | Tier `PR` fallback: 61,3% group ber-tier `PR` selisih material | tier resolution RM cost | `ENG-TIER-PR` | 🟡 P2 — perlu keputusan Finance dulu |
| E-7 | Index hilang: `crm_parent_product_sys_id` tidak ter-index → traversal DAG seq-scan tiap level | `CREATE INDEX idx_crm_parent_product ON cost_route_rm (crm_parent_product_sys_id);` | `ENG-ROUTE-INDEX` | 🟡 P2 — prasyarat praktis untuk E-1 |
| E-8 | `mst_spin_fixed_cost` tanpa baris 202608, dan nilainya identik di semua periode | loader spin fixed cost | `ENG-SPIN-PERIOD` | 🟡 P2 |
| E-9 | `mbh_machine_fixed_total` seragam 939,33 di seluruh 4.226 head; legacy membedakan 63 mesin (29,85–5.712,50) | model biaya mesin MB | `ENG-MACHINE-FIXED` | 🟡 P2 |
| E-10 | Tipe route `ITEM` nol baris padahal `idx_crm_rm_item` ada | `cost_route_rm` | `ENG-ROUTE-ITEM` | 🟢 P3 |

### 9.3 Perlu keputusan Finance / R&D

| # | Pertanyaan | Kenapa blocking | Owner | Target jawaban |
|---|---|---|---|---|
| F-1 | 32 group RM: lengkapi tier config dengan **fixed value** dari legacy, atau memang di-block? | Menentukan apakah 25 group ACTUAL / 42 SELLING nol itu benar; D-1 tidak bisa dijalankan tanpa ini | Finance | sebelum recompute |
| F-2 | Tier `PR` fallback ke PO rate: perilaku yang diinginkan ketika data konsumsi tidak ada? | 61,3% group `PR` selisih material; menentukan apakah E-6 defect atau by-design | Finance + Engineering | sebelum recompute |
| F-3 | **MB cost masuk conversion atau tidak?** Legacy memasukkan, goapps mengeluarkan (`EXCL_MB`) | Definisi berbeda → angka berbeda secara sah; menentukan mana yang benar untuk pelaporan | Finance | sebelum sign-off |
| F-4 | **Uplift landed cost** (duty/clearing/freight): dihapus dari model, atau belum diimplementasikan? | ~31% produk terdampak | Finance | sebelum sign-off |
| F-5 | Terima **arah dampak −23%** pada FG cost kasus RM? | Dampak material ke laporan keuangan | Finance | sebelum cutover |
| F-6 | **Tanggal cut-off legacy** untuk track SELLING | Pembandingnya masih bergerak (+32 baris, terakhir 15:25) | Finance + IT | sebelum menilai SELLING |
| R-1 | 195 produk legacy hilang: migrasikan atau retired? | Gap migrasi mendesak (194 bertransaksi 202608) | R&D | 2 minggu |
| R-2 | `SPC 3221` → `RED MGTP-3221` (`202007188`) atau varian BASF/CLA? | 5 resep tidak bisa di-approve | R&D | **sudah dijawab: RED MGTP-3221** |
| R-3 | 701 drift group di working set: disengaja (perlu re-version) atau dikembalikan? | Re-version akan mengubah cost | R&D + Finance | sebelum recompute |
| R-4 | 9 entri KYP di `cost_product_master`: memang desainnya? | Kebersihan master data | R&D | rendah |
| R-5 | Produk bertipe POY bernama `FDY 50/36/...`: koreksi tipe atau nama? | Inkonsistensi 1 produk | R&D | rendah |

### 9.4 Open question / `UNKNOWN`

| # | Selisih yang belum bisa dijelaskan | Objek | Langkah investigasi berikutnya |
|---|---|---:|---|
| U-1 | `total_cost ≠ total_rm + total_conversion` | **1.045 baris** ACTUAL | Bandingkan `cpc_formula_trace` baris yang gagal vs yang berlaku; cari suku yang tidak ikut terjumlah |
| U-2 | `flag_simulation_used = 'CONS'` di seluruh 350 group RM tapi `cost_sim = 0` semuanya | 350 group | Telusuri apakah `cons_rate` untuk simulation memang nol, atau perhitungan FORECAST tidak dijalankan |
| U-3 | `202007601` dan `202007602` selisih SELLING **tepat +0,028125** keduanya | 2 group | Kesamaan persis mengindikasikan satu konstanta/parameter bersama — cari sumbernya |
| U-4 | 11 `mbs_oracle_sys_id` menggantung (6 produksi aktif) | 11 spin | Pastikan baris legacy-nya dihapus atau id-nya salah tulis. **Catatan: pembacaan IT Lead ("item baru perlu di-insert") berbeda — baris ini sudah ada di goapps** |
| U-5 | 54% sisa ketidakcocokan rekonstruksi TOP 94 setelah MB cost dimasukkan | 6.773 produk | Rekonstruksi ulang dengan menambahkan intermingling, special cost, steam/CNG, softner, drying |

---

## 10. Kesimpulan Kelayakan Sign-Off

| Track | Layak sign-off? | Syarat / catatan |
|---|---|---|
| **ACTUAL** | **Belum** | Blocking: **E-1** (propagasi intermediate, 8.386 produk) dan **E-2** (packing 2×). Keduanya defect engine dengan akar penyebab yang sudah terbukti dan terukur. Setelah diperbaiki → **recompute 202608** → ulangi Fase 5–7. Baseline untuk mengukur perbaikan sudah stabil dan tersedia. |
| **SELLING** | **Belum** | Dua blocking independen: (a) RM SELLING nol di 13.421 dari 13.422 produk (KG-05 / F-1); (b) pembanding legacy **masih bergerak** — perlu tanggal cut-off (F-6). Satu bagian **sudah reconciled**: LDR Rencana (2 selisih dari 1.954). |
| **FORECAST** | **N/A** | Dikeluarkan dari cakupan atas keputusan IT Lead — legacy tidak punya track forecast, hanya selling. Catat asimetri: **MB FORECAST terisi** sementara RM/FG nol. |

**Penutup.**

Periode 202608 **belum bisa dinyatakan reconciled**, tapi posisinya jauh lebih
baik daripada sekadar "banyak selisih". Yang dicapai recon ini: **penyebab
selisih sudah terlokalisasi ke sejumlah kecil akar yang bisa diperbaiki**, bukan
tersebar acak. Tiga bukti bahwa lokalisasinya nyata:

1. **Nol kasus di mana semua input setara tapi output berbeda** — formula engine
   konsisten; yang salah adalah input.
2. **85% selisih FG in-scope terjelaskan oleh dua layer saja** (RM cost 57,5% +
   packing 27,5%).
3. **Satu defect (E-1) menjelaskan gejala di tiga fase sekaligus** — `total_rm`
   bernilai bulat, `captive` = 1, dan arah −23%.

Dan area yang **sudah sehat** cukup luas: struktur DAG (max depth 13 konvergen di
kedua sisi), recipe MB (21.861 dari 21.863 baris setara, legacy nol resep rusak),
LDR aktual (96,1%), parameter produk (89–100%), crosswalk (ketiganya terbukti
1:1), dan **nol migration pending**.

Jalur ke sign-off jelas dan berurutan:

1. **Finance jawab F-1 s/d F-4** (definisi: fixed value, tier PR, MB cost dalam
   conversion, uplift landed).
2. **Engineering perbaiki E-1 dan E-2** (P0).
3. **Jalankan paket remediasi data** D-1 s/d D-5 (script sudah siap).
4. **Recompute 202608**, lalu ulangi Fase 5–7 terhadap baseline yang sudah
   tercatat di dokumen ini.
5. **Tetapkan cut-off legacy** sebelum menilai SELLING.

Estimasi: langkah 1–3 adalah jalur kritis. Angka ACTUAL punya peluang baik untuk
konvergen setelah E-1 dan E-2 selesai, karena layer di bawahnya (parameter, RM
norm, fixed cost per kg) sudah terbukti sebanding.

Yang **tidak** boleh dilakukan sekarang: membawa angka FG ke Finance sebagai
hasil final. Selisihnya bukan cerminan kondisi bisnis — ia cerminan dua defect
engine yang sudah teridentifikasi.

---

## 11. Lampiran

### 11.1 Daftar file query

| File | DB target | Scope | Terakhir dijalankan |
|---|---|---|---|
| `00_preflight_schema_discovery.sql` | Oracle + Postgres | Fase 0 | 2026-09-09 09:46–09:54 |
| `cmp_01_raw_material_cost_202608.sql` | Oracle + Postgres | Fase 2 — RM cost | 2026-09-09 10:20–10:28 |
| `cmp_02_mb_recipe_202608.sql` | Oracle + Postgres | Fase 3 — MB recipe | 2026-09-09 11:15–11:40 |
| `cmp_03_mb_ldr_dosing_202608.sql` | Oracle + Postgres | Fase 4 — MB LDR % | 2026-09-09 12:10–12:45 |
| `cmp_04_fg_product_cost_202608.sql` | Oracle + Postgres | Fase 5 — FG cost | 2026-09-09 14:00–14:40 |
| `cmp_05_fg_calculation_breakdown_202608.sql` | Oracle + Postgres | Fase 6 — breakdown | 2026-09-09 15:05–15:35 |
| `cmp_06_intermediate_product_202608.sql` | Oracle + Postgres | Fase 7 — intermediate | 2026-09-09 15:50–16:10 |
| `cmp_07_new_products_202608.sql` | Oracle + Postgres | Fase 8 — produk baru | 2026-09-09 16:10–16:25 |

Script pendukung (hanya membaca file hasil ekspor, **tidak menyentuh DB**):
`build_phase2_v2.py`, `build_phase3.py`, `build_phase4.py`, `build_phase5b.py`,
`build_phase6.py`, `build_phase8.py`, `build_remediation.py`.

### 11.2 Daftar file output

| File CSV | Isi | Jumlah baris |
|---|---|---:|
| `cmp_01_rm_cost_matrix_202608.csv` | Matrix RM per group × cost type | 1.050 |
| `cmp_01_rm_legacy_orphan_202608.csv` | 574 legacy group tanpa padanan + bucket | 574 |
| `cmp_02_mb_recipe_head_202608.csv` | Head MB + bucket + validasi sum | 4.325 |
| `cmp_02_mb_recipe_line_202608.csv` | Line komposisi + klasifikasi | 22.589 |
| `cmp_03_mb_ldr_dosing_202608.csv` | 2.802 spin × 3 metric LDR | 8.406 |
| `cmp_04_fg_crosswalk_202608.csv` | **Crosswalk FG — dipakai ulang Fase 6 & 7** | 20.400 |
| `cmp_04_fg_product_cost_202608_summary.csv` | Ringkasan per metric + split scope ORION | 54 |
| `cmp_04_fg_product_cost_202608_diff.csv` | Selisih FG in-scope 202608 | 55.419 |
| `cmp_05_fg_breakdown_202608.csv` | Atribusi layer penyebab + nilai kedua sisi | 20.400 |
| `cmp_06_intermediate_product_202608.csv` | Baris route terdampak defect propagasi | 8.718 |
| `cmp_07_new_products_202608.csv` | Bucket A + C | 1.961 |
| `cmp_07_produk_baru_definisi_202608.csv` | **26 produk baru, definisi lengkap** | 26 |
| `cmp_07_legacy_only_products_202608.csv` | Bucket B — gap migrasi | 1.297 |

Matrix FG penuh (550.800 baris / ~121 MB) **tidak ditulis** karena tidak praktis
dibuka di Excel; bisa dihasilkan ulang dari `build_phase5b.py`.

Paket remediasi di `remediation/` (5 SQL + 6 CSV + `README.md`) — **belum
dijalankan**, disiapkan untuk dieksekusi manual oleh IT Lead setelah review.

### 11.3 Cara reproduce

```
1. Kredensial dibaca dari file `env` di root repo (D:\DataGripQuery\env),
   BUKAN dari environment variable — tidak ada PG_HOST/PG_PORT/PG_DB/PG_USER/
   PG_PASS di sana. Postgres diakses lewat SSH tunnel ke localhost:25432
   (DB/user `goapps`). Oracle: MGTAPPS @ ALTHARA (production) — perhatikan
   `oracle_service_name` default di file env adalah ALTHARADEV.
2. Jalankan 00_preflight_schema_discovery.sql, konfirmasi hasil Fase 0 masih valid
3. Jalankan cmp_01 s/d cmp_07 berurutan
4. Bandingkan calc snapshot stamp dengan §3.4 — kalau berbeda, angka laporan sudah kedaluwarsa
5. Untuk sisi legacy, cek juga drift `_CUR` MARKETING (§1.5) — track itu
   masih bergerak, jadi angka SELLING bisa berubah tanpa ada recompute di goapps
```

### 11.4 Pernyataan read-only

**Seluruh pekerjaan dilakukan dalam mode read-only.** Nol statement
`INSERT`/`UPDATE`/`DELETE`/`MERGE`/`TRUNCATE`/`DROP`/`ALTER`/`CREATE`. Nol
`GRANT`/`COMMIT`, nol `CALL`/`EXEC` procedure, nol `DO $$`, nol `VACUUM`/
`ANALYZE`/`REFRESH MATERIALIZED VIEW`. **Nol trigger recompute** — tidak lewat
SQL, tidak lewat gRPC (`finance.v1.CostCalcService/TriggerCalcJob`), tidak lewat
endpoint apa pun.

Session PostgreSQL di-set read-only di **dua level** dan diverifikasi:
`default_transaction_read_only = on` **dan** `transaction_read_only = on`
(dipaksa lewat `PGOPTIONS` pada setiap koneksi, sehingga berlaku juga untuk
setiap invocation `psql` baru).

Bukti tambahan: verifikasi ulang §3.4 menunjukkan **nol** baris `cst_product_cost`
berubah selama 6,6 jam sesi — kalau ada write dari sesi ini, angka itu akan
bergerak.

Semua perubahan yang diusulkan ditulis sebagai **file CSV/SQL di disk**
(`remediation/`) untuk dieksekusi manual oleh IT Lead, **tidak dijalankan oleh
sesi recon**. Satu query berat (traversal DAG rekursif) yang tidak selesai dalam
waktu wajar **dihentikan**, tidak dibiarkan menggantung di production.

Tidak ada file yang ditulis di luar folder kerja
`D:\DataGripQuery\costing compare`, kecuali file sementara di scratchpad sesi.

### 11.5 Riwayat revisi dokumen

| Versi | Tanggal | Perubahan | Author |
|---|---|---|---|
| 1.0 | 2026-09-09 | Versi awal — Fase 0–8 lengkap, snapshot diverifikasi ulang di akhir | Indra (IT Lead) |
