# Fase 0 — Preflight & Schema Discovery: Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Tanggal | 2026-09-09 |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query pendukung | `00_preflight_schema_discovery.sql` (blok A = Oracle, blok B = Postgres) |
| Snapshot stamp | `report/snapshot_stamp_202608.md` |

---

## 0. Preflight koneksi

| Sisi | Hasil |
|---|---|
| Oracle | ✅ `MGTAPPS@ALTHARA` (**production**), Oracle Database 11g 11.2.0.4.0 64-bit |
| Postgres | ✅ `goapps@goapps` localhost:25432 (SSH tunnel), PostgreSQL 18.1, 183 tabel di `public` |
| Read-only Postgres | ✅ `default_transaction_read_only=on` **dan** `transaction_read_only=on` |
| Client | `sqlplus` 11.2 (Python `oracledb` tidak terinstall — dipakai fallback sesuai prompt), `psql` 18 |

Catatan: file `env` di root repo default-nya `oracle_service_name=ALTHARADEV`. Untuk
recon ini `ALTHARA` (production) dipakai secara eksplisit atas keputusan IT Lead.

---

## A. Semantik suffix `_CUR` — **BLOCKING, perlu keputusan**

### A.1 Yang dibuktikan

`CST_YARN_CALCULATION_CUR` **tidak punya kolom periode sama sekali.**

Strukturnya 158 kolom: 8 kolom kunci (`CYCC_SYS_ID`, `CYCC_CYL_SYS_ID`,
`CYCC_LEFT_NO`, `CYCC_PRS_TYPE`, + 4 audit) dan 150 kolom
`CYCC_TOP_n_DATA_VALUE`. Tidak ada `PERIOD`, `YEAR`, `MONTH`, maupun turunannya.

Ini versi **wide/pivot** dari `CST_YARN_CALCULATION` yang berbentuk
**tall/EAV** (18 kolom: `CYC_TOP_NO` + `CYC_DATA_VALUE`). Dua file basis di
folder ini memang memakai dua bentuk berbeda: query dosing pakai bentuk tall,
query FG cost pakai bentuk wide.

### A.2 `PRS_TYPE` bukan periode — koreksi asumsi

Satu-satunya dimensi selain produk adalah `CYCC_PRS_TYPE`, dan isinya hanya
**dua nilai**:

| `PRS_TYPE` | `CYC_PRS_NAME` | Baris `_CUR` | Produk | Padanan di goapps |
|---|---|---:|---:|---|
| `20210800119` | **MARKETING VALUE** | 14.635 | 14.635 | `SELLING` |
| `20210800120` | **VALUATION VALUE** | 7.505 | 7.505 | `ACTUAL` |

Jadi `cycc_prs_type = 20210800120` di `fg cost product from legacy system.txt`
adalah filter **cost type = VALUATION VALUE**, bukan periode 202108. Angka
`202108...` itu sys_id yang kebetulan digenerate Agustus 2021.

Konsekuensi: `_CUR` **tidak punya jalur ke FORECAST**. Legacy hanya mengenal 2
cost type, sisi baru punya 3 (ACTUAL / SELLING / FORECAST). FORECAST di goapps
tidak punya pembanding legacy → kategori `SCOPE`.

### A.3 `_CUR` adalah snapshot overwrite per baris, bukan per periode

Sebaran bulan kalkulasi di dalam `_CUR` (`CYCC_MODIFIED_TIMESTAMP`):

| Cost type | Contoh sebaran vintage |
|---|---|
| `20210800119` MARKETING | 10.082 baris @ 2026-09-06, 4.532 baris @ 2026-09-05 → **refresh massal 5–6 Sept** |
| `20210800120` VALUATION | 1.498 @ 2026-09-01, 475 @ 2026-08-02, 343 @ 2026-07-01, 214 @ 2026-06-01, 206 @ 2026-05-02, 202 @ 2026-03-03, 175 @ 2026-01-02, 170 @ 2026-04-01, 170 @ 2025-12-01, **634 @ 2022-12-01** |

Satu tabel memuat baris dari belasan tanggal kalkulasi berbeda. Artinya baris
di-overwrite **satu per satu saat produknya dihitung ulang**, dan tabel ini
adalah kumpulan vintage campur — bukan snapshot satu periode.

### A.4 Tidak ada tabel history periode

| Tabel | Baris | Punya kolom periode? | Kesimpulan |
|---|---:|---|---|
| `CST_YARN_CALCULATION` | 2.996.325 | ❌ | live, tall, 2 cost type yang sama |
| `CST_YARN_CALCULATION_HIST` | 2.996.325 | ❌ | **bukan history periode** — mirror/backup, `PRS_TYPE` & row count identik |
| `CST_YARN_CALCULATION_BCP` / `_BCP1` / `_BCP2` | 2,16 / 2,51 / 2,56 jt | ❌ (perlu cek lanjut) | backup bertanggal, bukan dimensi periode |

Pola `..._ARCH` / `..._LOG` untuk tabel calculation: **tidak ada**.

### A.5 Tapi: 202608 bisa dijangkau lewat scope produk

Periode di legacy hidup di master `CST_MST_ORION_REFF_HDR` — 57 baris,
`CMORH_PERIOD_YEAR` + `CMORH_PERIOD_MONTH` (dua kolom `NUMBER`). Periode
**2026/08 ADA**, sys_id `2026090101403`, dan merupakan **periode terbaru**
(202609 belum dibuka).

Query basis FG memakai periode hanya untuk menentukan **produk mana yang
in-scope**, lalu mengambil angkanya dari snapshot current:

| Cek | Hasil |
|---|---|
| `cyl_left_no` in-scope 202608 | **1.546** |
| Punya baris `_CUR` MARKETING | 1.546 (100%) |
| Punya baris `_CUR` VALUATION | 1.545 (99,94%) |

### A.6 Kesimpulan poin A

202608 **tidak** ada sebagai periode di `_CUR` — tapi produk 202608 **ada**, dan
karena 202608 adalah periode terbuka terakhir di legacy, snapshot current
adalah aproksimasi terdekat yang legacy punya.

> ⛔ **Perlu keputusan Anda sebelum Fase 5.** Membandingkan goapps-202608 lawan
> `_CUR` berarti membandingkan angka periode-spesifik lawan angka yang
> vintage-nya campur — untuk VALUATION, 634 baris terakhir dihitung
> **Desember 2022**. Selisih pada produk-produk itu adalah artefak "legacy belum
> pernah dihitung ulang", bukan defect engine. Sesuai aturan prompt saya tidak
> mengganti sumber sendiri. Opsi ada di bagian **Keputusan yang dibutuhkan**.

---

## B. Sumber RM cost legacy per periode — **TERJAWAB**

**`CST_GRP_CONSUMP_HEAD`** (55.765 baris). Alasan pemilihan:

1. Satu-satunya kandidat yang punya **periode + group code + rate** sekaligus:
   `CGCH_PERIOD_YEAR`, `CGCH_PERIOD_MONTH`, `CGCH_GROUP_CODE`, `CGCH_GROUP_NAME`.
2. Granularitasnya tepat 1 baris per (periode, group): 2026-08 → **924 baris,
   924 group distinct**.
3. Kolom rate-nya memetakan langsung ke tiga cost type di sisi baru:

| Legacy `CST_GRP_CONSUMP_HEAD` | Padanan `cst_rm_cost` | Cost type |
|---|---|---|
| `CGCH_LANDED_COST` / `CGCH_COST` | `cost_val` | ACTUAL |
| `CGCH_MARKET_RATE1` (+ `_FIX`) | `cost_mark` | SELLING |
| `CGCH_SIMULATION_RATE` | `cost_sim` | FORECAST |
| `CGCH_STOCK_RATE`, `CGCH_LAST_PURC_PRICE`, `CGCH_DUTY_CLEARING_COST` | — | input valuation tier |

Kandidat yang **ditolak**, dengan alasan:

| Tabel | Ditolak karena |
|---|---|
| `CST_GRP_CONSUMP_ITEM` | granularitas item, bukan group — dipakai nanti untuk drill-down Fase 6, bukan matrix Fase 2 |
| `CST_GRP_CONSUMP_HEAD_DFLT` | tanpa kolom periode — tabel default/fallback |
| `CST_GRP_CONSUMP_HEAD_FIX` | hanya `MARKET_RATE1_FIX`/`RATE2_FIX`, subset override |
| `CST_GRP_HEAD` / `CST_GRP_ITEM` | master group, hanya `FIXVAL_DUTY`, tanpa periode |
| `RM_AVG_GROUP` / `RM_AVG_ITEMS` | 17 / 108 baris, tanpa periode — terlalu kecil |

⚠️ **Selisih scope yang langsung terlihat**: legacy 924 group untuk 202608 vs
`cst_rm_cost` 202608 hanya **350 baris**. Beda 574. Ini materi utama Fase 2.

---

## C. Format kolom periode — **TERJAWAB, dan berbeda di dua sisi**

| Sisi | Representasi | Tipe | Contoh |
|---|---|---|---|
| Legacy | `CMORH_PERIOD_YEAR` + `CMORH_PERIOD_MONTH` (dua kolom) | `NUMBER` | `2026`, `8` |
| Legacy (RM) | `CGCH_PERIOD_YEAR` + `CGCH_PERIOD_MONTH` | `NUMBER` | `2026`, `8` |
| goapps | `cst_rm_cost.period`, `cst_product_cost.cpc_period` | `varchar(6)` | `'202608'` |

Jadi join periode **wajib** normalisasi:
`LPAD(year,4,'0')||LPAD(month,2,'0')` di sisi Oracle. Bulan legacy tidak
zero-padded (`8`, bukan `08`) — kalau di-concat mentah jadi `'20268'` dan tidak
akan match apa pun. Tidak ada tabel legacy dengan periode format `YYYYMM`
tunggal di scope yang diperiksa.

---

## D. Row count 202608 di dua sisi — **TERJAWAB, dua sisi terisi**

### Sisi baru

`cst_product_cost` periode 202608:

| calc type | APPROVED | CALCULATED | SUPERSEDED |
|---|---:|---:|---:|
| ACTUAL | 4.189 | 13.422 | 17.611 |
| SELLING | 4.189 | 13.422 | 4.189 |
| FORECAST | 4.189 | 13.422 | 4.189 |

Total baris aktif (`cpc_status <> 'SUPERSEDED'`) = **52.833**.

**Temuan struktural**: ada **dua** status aktif, bukan satu. Sudah diuji apakah
keduanya bersaing untuk produk yang sama — **tidak**: himpunan produknya
disjoint (overlap = 0; APPROVED-only 4.189, CALCULATED-only 13.422). Jadi filter
`cpc_status <> 'SUPERSEDED'` benar dan tidak menyebabkan fan-out. Total produk
ter-cost 202608 = **17.611**.

Tabel lain:

| Tabel | 202606 | 202607 | 202608 | 202609 |
|---|---:|---:|---:|---:|
| `cst_rm_cost` | 350 | 350 | 350 | — |
| `cst_mb_cost` | 12.576 | 12.579 | 12.567 | **12.567** |

`cst_mb_cost` sudah punya baris 202609 → sisi baru sudah bergerak ke periode
berikutnya.

### Sisi legacy

| Scope | 202608 |
|---|---:|
| ORION reff detail | 1.951 baris, 557 item |
| FG (`cyl_left_no`) in-scope | 1.546 |
| RM group (`CST_GRP_CONSUMP_HEAD`) | 924 |

Tidak ada sisi yang kosong → **boleh lanjut**, tapi lihat peringatan asimetri
scope di bagian Risiko.

---

## E. Migration tracking Postgres — **TERJAWAB**

Tiga tabel: `schema_migrations`, `schema_migrations_finance`,
`schema_migrations_iam`. Yang relevan untuk costing = **`schema_migrations_finance`**.

| Field | Value |
|---|---|
| `version` | **508** |
| `dirty` | `false` |

Migration yang prompt curigai pending — `000468`, `000469`, `000470`, `000477`,
`000479` — semuanya **di bawah 508**, jadi sudah applied dan tidak dirty.
Verifikasi lawan daftar file di repo tetap dilakukan di Fase 1.

---

## Temuan tambahan di luar checklist Fase 0

### T1. 202608 di goapps di-recompute **hari ini**, 45 menit sebelum snapshot

| job_id | calc type | status | rows | calculated_at |
|---|---|---|---:|---|
| 102 | ACTUAL / SELLING / FORECAST | APPROVED | 4.189 ×3 | 2026-09-09 08:54:09 |
| 103 | ACTUAL | CALCULATED | 13.422 | 2026-09-09 09:01:29 – 09:04:39 |
| 104 | FORECAST | CALCULATED | 13.422 | 2026-09-09 09:05:31 – 09:07:48 |
| 105 | SELLING | CALCULATED | 13.422 | 2026-09-09 09:10:59 – 09:13:10 |

Versi sebelumnya (SUPERSEDED, 25.989 baris) berasal dari run 2026-09-05.
Premis "periode 202608 closed di kedua sisi, legacy tidak bergerak lagi" **tidak
akurat di dua-duanya**: goapps di-recompute hari ini (job 102–105), legacy
`_CUR` MARKETING di-refresh massal 5–6 September. Baseline untuk verifikasi
ulang di akhir kerja sudah disimpan di `report/snapshot_stamp_202608.md`.

### T2. Aparatus recon sudah ada di live DB — tidak disebut di prompt

Live Postgres sudah memuat 9 view recon dan 15 tabel staging legacy:

**View**: `vw_recon_all`, `vw_recon_fg`, `vw_recon_mb`, `vw_recon_l1_setup`,
`vw_recon_l2_input`, `vw_recon_l3_rmcost`, `vw_fg_cost_check`, `vw_mb_bridge`,
`v_stg_cycc_long`

**Staging legacy**: `stg_leg_yarn_calc`, `stg_leg_yarn_calc_cur` (7.333 baris),
`stg_leg_yarn_cost` (616), `stg_leg_yarn_fg`, `stg_leg_yarn_param`,
`stg_leg_yarn_product`, `stg_leg_yarn_route`, `stg_leg_grp_head`,
`stg_leg_grp_item`, `stg_leg_item_cons`, `stg_leg_mst_yarn`, `stg_leg_mb_head`,
plus tiga bertanda `_202607`: `stg_leg_mb_head_202607`,
`stg_leg_mb_batch_item_202607`, `stg_leg_mb_consump_item_202607`.

Ada juga CSV hasil ekspor di root repo dengan pola nama yang sama
(`stg_leg_yarn_cos.csv`, `stg_leg_yarn_route_202607.csv`, dst).

Artinya recon 202607 sudah pernah dijalankan lewat jalur staging-into-Postgres,
dan `v_stg_cycc_long` sudah melakukan unpivot `CYCC_TOP_n` yang sama seperti
yang akan kita tulis. **Perlu diperiksa dulu sebelum Fase 2** supaya tidak
menduplikasi kerja — dan `_202607` menandakan konvensinya per-periode, jadi
padanan 202608 belum dibuat.

⚠️ Catatan read-only: memakai staging table di Postgres untuk data legacy 202608
akan **butuh write** (load ulang). Itu dilarang oleh aturan keras prompt. Jadi
view/staging yang ada hanya bisa dipakai untuk **membaca logika unpivot dan hasil
202607**, bukan untuk memuat 202608.

### T3. Placeholder machine fixed cost 384.000 terkonfirmasi salah

`CST_MST_MACHINE.CMM_TOT_FXD_CST` — 63 mesin terisi, **range 29,85 s/d
5.712,50**. Nilai 384.000 berada dua–tiga orde di luar range legacy. Konfirmasi
bahwa 384.000 adalah placeholder, bukan baseline.

### T4. Query dosing basis tidak punya filter periode maupun cost type

Di `masterbatch dozing.txt`, baris `AND c.cyc_prs_type = :vPrsType`
**dikomentari**. Apa adanya, CTE `doz` meng-agregat `MAX(...)` lintas
**dua** cost type sekaligus, dengan `GROUP BY CYC_LEFT_NO`. Untuk LDR (TOP 71)
nilainya mungkin sama di kedua cost type, tapi itu **belum dibuktikan**. Sesuai
aturan prompt, dicatat sebagai temuan dan tidak diperbaiki sendiri — pertanyaan
untuk Fase 4 ada di bagian Keputusan.

---

## Risiko yang harus disadari sebelum Fase 2

| # | Risiko | Dampak |
|---|---|---|
| R1 | `_CUR` vintage campur (VALUATION: 634 baris dari 2022-12) | selisih FG sebagian adalah artefak umur data legacy, bukan defect |
| R2 | Legacy hanya 2 cost type, goapps 3 | FORECAST tanpa pembanding → `SCOPE` |
| R3 | Asimetri scope: legacy 1.546 FG vs goapps 17.611 produk ter-cost | mayoritas produk goapps tidak punya lawan di legacy — perlu dipisah bucket, bukan dihitung sebagai selisih |
| R4 | RM: legacy 924 group vs goapps 350 baris | beda scope 574, harus dijelaskan sebelum matrix rate dibaca |
| R5 | Dua sisi masih bergerak (T1) | laporan wajib berlabel snapshot; verifikasi ulang di akhir |

---

## Keputusan yang sudah diambil (IT Lead, 2026-09-09)

| # | Topik | Keputusan |
|---|---|---|
| 1 | Sumber legacy FG cost | **Batasi ke vintage `_CUR` ≥ 2026-08-01.** Lihat catatan di bawah — ternyata tidak membuang satu produk pun. |
| 2 | Basis pembanding sisi baru | **Keduanya** (APPROVED + CALCULATED), status ditampilkan sebagai kolom supaya Finance bisa memfilter. Populasi 17.611 produk. |
| 3 | Aparatus recon yang sudah ada | **Tidak dipakai.** Kerja bersih dari dua file basis di folder ini. |
| 4 | Filter cost type di query dosing | **Buktikan dulu** apakah LDR (TOP 71) identik di kedua cost type, baru lapor. |
| 5 | Target Oracle | `ALTHARA` production (bukan `ALTHARADEV` yang jadi default di file `env`). |

### Catatan penting atas keputusan #1

Setelah dikuantifikasi, batasan vintage ≥ 2026-08-01 **tidak mengurangi
populasi sama sekali** di dalam scope 202608:

| Cost type | Total `_CUR` | Vintage ≥ 2026-08-01 | In-scope 202608 | In-scope **dan** vintage ≥ 2026-08-01 |
|---|---:|---:|---:|---:|
| `20210800119` MARKETING | 14.635 | 14.635 (100%) | 1.546 | **1.546 (100%)** |
| `20210800120` VALUATION | 7.505 | 2.085 (27,8%) | 1.545 | **1.545 (100%)** |

Jadi 634 baris VALUATION bervintage 2022-12 (dan sisa baris lama lainnya) semua
berada di produk yang **di luar** scope 202608 — produk yang tidak lagi
diproduksi/dijual di periode itu. Risiko **R1 gugur**: seluruh angka legacy yang
akan dibandingkan berasal dari kalkulasi Agustus 2026 atau lebih baru.

Praktisnya filter vintage tetap dipasang di query Fase 5 sebagai **guard**, dan
kolom `CYCC_MODIFIED_TIMESTAMP` tetap dibawa ke output supaya bisa diaudit.

---

## Keputusan yang masih dibutuhkan dari Finance / R&D

1. **Sumber legacy FG cost (blocking Fase 5).** `_CUR` tidak punya periode.
   Pilihan: (a) tetap pakai `_CUR` sebagai "angka legacy yang berlaku sekarang",
   dengan kolom vintage `CYCC_MODIFIED_TIMESTAMP` dibawa ke laporan supaya
   pembaca bisa memisahkan artefak umur data; (b) batasi populasi hanya produk
   yang `_CUR`-nya dihitung ulang pada/setelah 2026-08-01 (perbandingan lebih
   bersih, populasi jauh lebih kecil); (c) telusuri `_BCP`/`_BCP1`/`_BCP2` dulu
   untuk mencari snapshot bertanggal yang lebih dekat ke akhir 202608.
2. **Padanan cost type.** Konfirmasi MARKETING VALUE ↔ SELLING dan VALUATION
   VALUE ↔ ACTUAL, serta setuju bahwa FORECAST dilaporkan sebagai `SCOPE`
   (tanpa pembanding legacy).
3. **Basis pembanding sisi baru.** APPROVED (4.189) dan CALCULATED (13.422)
   adalah dua populasi disjoint. Apakah laporan mencakup keduanya, atau hanya
   APPROVED (angka yang sudah disetujui Finance)?
4. **Aparatus recon yang sudah ada (T2).** Apakah saya periksa isi
   `vw_recon_fg` / `vw_recon_l3_rmcost` / `v_stg_cycc_long` dan me-reuse
   logikanya, atau kerjakan bersih dari query legacy di folder ini saja?
5. **Filter cost type di query dosing (T4).** Perlu diaktifkan
   (`cyc_prs_type = 20210800120`) atau memang sengaja lintas cost type?
