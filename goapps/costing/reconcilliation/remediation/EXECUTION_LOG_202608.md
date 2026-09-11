# Log Eksekusi Paket Remediasi — Periode 202608

**Tanggal eksekusi:** 2026-09-10
**Dijalankan oleh:** sesi Claude Code, atas instruksi IT Lead
("jalankan paket remediasi" / "kamu saja yang lanjutkan")
**Target:** Postgres **goapps** `localhost:25432/goapps` — **SISTEM BARU SAJA**
**Legacy Oracle (ALTHARA):** tidak disentuh sama sekali selain `SELECT`. Nol statement tulis.
**Recompute:** tidak di-trigger. Diverifikasi di §4.

Runner: `pgw.sh` (host/port/db dipatok keras, kredensial dibaca dari `D:\DataGripQuery\env`,
`ON_ERROR_STOP=1`, transaksi disediakan runner). Rollback state ada di `_before_state/`.

---

## 1. Ringkasan hasil

| Paket | Isi | Statement | Baris terpengaruh | Status |
|---|---|---|---|---|
| 01 | Remap group code (klarifikasi) | 16 UPDATE | 480 `cost_route_rm` + 112 `mst_mb_composition` | ✅ COMMIT |
| 02 | Init fixed value RM group | 64 UPDATE | 32 `cst_rm_group_head` + 32 `cst_rm_group_head_period` | ✅ COMMIT (sesi sebelumnya) |
| 05 | Line SPC 3221 yang hilang | 4 INSERT | 4 line, 4 resep → sum 100.000 | ✅ COMMIT |
| 04 | Resep MB yang hilang | 160 INSERT | 160 line atas 30 head, semua sum 100.000 | ✅ COMMIT |
| 07 | LDR sisi baru := LDR legacy | 106 UPDATE | 104 spin (2 spin dua kolom) | ✅ COMMIT |
| 06 | LDR adjustment set-based | 1 UPDATE | 955 spin | ✅ COMMIT |

Urutan dijalankan **01 → 05 → 04 → 07 → 06**. Paket 07 wajib sebelum 06 supaya
`calculated + adjustment = run_ldr = legacy`; kalau dibalik adjustment-nya kedaluwarsa.

## 2. Dua penyimpangan yang perlu dicatat

**(a) Paket 01 ter-COMMIT pada langkah yang dimaksudkan sebagai dry-run.**
Versi pertama `pgw.sh` mengandalkan `BEGIN;`/`COMMIT;` di dalam file SQL. File
paket 01–06 **tidak** punya keduanya (headernya justru meminta pemanggil
menyediakan transaksi), jadi psql jalan autocommit dan penggantian
`COMMIT`→`ROLLBACK` tidak berefek. Akibatnya paket 01 langsung permanen tanpa
melewati gerbang verifikasi.

Dampak ke data: **tidak ada**. Statement yang jalan identik dengan yang
direncanakan, dan hasilnya diverifikasi sesudahnya:
480/112 baris tepat sesuai rencana, 0 baris kode lama tersisa, 8 kode baru terisi.

Perbaikan: `pgw.sh` sekarang membungkus sendiri (`BEGIN;` … `COMMIT|ROLLBACK;`)
dan membuang baris transaksi milik file supaya tidak dobel. Mode `--rollback`
dibuktikan benar-benar rollback sebelum dipakai lagi (paket 05: 4 INSERT jalan,
state tetap 0).

**(b) Bug sintaks di paket 04, ketangkap dry-run.**
Baris 70 punya anotasi `[SPC 3221 group retired -> RED MGTP-3221 …]` yang
ditempel setelah `;` tanpa penanda `--` — bug generator. Dry-run gagal di
statement ke-9 dan ter-rollback bersih. Diperbaiki menjadi komentar; dry-run
ulang: 160 INSERT, 0 error. File `04_mb_recipe_missing_insert.sql` di repo
sudah memuat perbaikan ini.

## 3. Catatan angka yang berbeda dari rencana

**Paket 06: 955 baris, bukan 923.** 923 dihitung *sebelum* paket 07 mengubah
`mbs_run_ldr_pct` pada 104 spin. Rekonsiliasi:

```
923 (pra-07) − 67 (dari 104 spin, memenuhi syarat SEBELUM 07)
            + 99 (dari 104 spin, memenuhi syarat SESUDAH 07)  = 955
```
Silang-periksa dari sisi DB: 856 (tak disentuh 07) + 99 (disentuh 07) = 955. ✔

**Penanda `updated_by` paket 07 tinggal 5 baris.** Paket 06 jalan sesudahnya dan
menimpa `updated_by` menjadi `recon-202608-ldr-adj` pada 99 spin yang memenuhi
syarat (104 − 99 = 5). Hanya kolom penandanya yang tertimpa — **nilainya** utuh:
diperiksa ulang setelah paket 06, 104/104 `mbs_run_ldr_pct` dan 2/2
`mbs_ldr_prsn` masih sama persis dengan nilai legacy.

## 4. Verifikasi akhir

| Cek | Hasil |
|---|---|
| Resep 30 head paket 04 | 30/30 sum = 100.000, 160 line |
| Resep 4 head paket 05 | 4/4 sum = 100.000 |
| LDR = nilai legacy | 104/104 + 2/2 cocok, 0 tidak cocok |
| `calculated + adjustment = run_ldr` | 955 terisi, **0 tidak konsisten** (dari 2.623 baris) |
| Kode group lama tersisa | 0 |
| **Recompute** | `cst_product_cost` 202608: 78.822 baris, **0** dihitung setelah stempel snapshot `2026-09-09 09:13:10.407045+07`; `max(cpc_calculated_at)` tepat = stempel itu |

## 5. Tindakan lanjutan yang TIDAK bisa dilakukan lewat SQL

Setelah paket 01, 04 dan 05, record MB terkait harus **di-Validate ulang lewat UI
aplikasi** supaya `mst_mb_composition_version` ter-generate (`mbcv_version` naik
dari 0). Tabel version adalah snapshot immutable yang dikonsumsi engine —
jangan di-UPDATE langsung. Tanpa Validate, engine tetap tidak melihat resep dan
remap yang baru dimasukkan.

Head yang perlu di-Validate: 30 head dari paket 04, 4 head dari paket 05, dan
MB yang komposisinya ikut ter-remap di paket 01 (112 line).

---

## 6. Addendum 2026-09-10 (setelah IT Lead Validate lewat UI)

### 6.1 Paket 08 — koreksi defect urutan eksekusi saya sendiri

Pemeriksaan pasca-Validate menemukan 4 baris komposisi yang menunjuk group
**pra-remap**. Penyebabnya urutan eksekusi saya: paket 01 me-remap pukul 10:56,
paket 04 jalan pukul 11:00 dan meng-INSERT baris dengan `group_head_id` hasil
resolusi 2026-09-09 — yaitu sebelum remap. Jadi 4 baris baru menunjuk
`202006002` (PIG0000005) dan `202006051` (DYE0000001) yang justru sudah di-retire.

| Head | Group salah | Dikoreksi ke |
|---|---|---|
| 20260105082, 20260205129, 20260605323 | 202006002 PIG0000005 | 202007204 YELLOW MGTP-2159 |
| 20260205137 | 202006051 DYE0000001 | 202007176 OPTICAL BRIGHTNER-1 |

`08_fix_paket04_group_lama.sql` — 4 UPDATE, ter-COMMIT. Target uuid di-resolve
lewat `group_code` (tidak di-hardcode). Verifikasi: **0** baris working set
tersisa menunjuk 8 group pra-remap; sum 4 resep tetap 100.000.

Catatan: 3 dari 4 head ini **sudah ter-Validate pukul 15:03 dengan pointer yang
salah**, jadi versi aktifnya harus diganti versi baru.

### 6.2 Status Validate: baru sebagian

| Kelompok | Head | Sudah Validate pasca-tulisan | Belum |
|---|---:|---:|---:|
| Paket 04 (resep) | 30 | 26 | 4 |
| Paket 05 (SPC 3221) | 4 | 0 | 4 |
| Paket 01 (remap) | 112 | 0 | 112 |

26 head paket 04 yang sudah di-Validate: snapshot version **identik dengan
working set** dan sum 100.000 (26/26). Mekanismenya benar — cakupannya saja
yang belum penuh.

Bukti dampaknya di lapisan yang dibaca engine:

| Lapisan | Baris pakai group LAMA | Baris pakai group BARU |
|---|---:|---:|
| Working set `mst_mb_composition` | **0** | 1.105 |
| Snapshot versi aktif (dibaca engine) | **481** | 998 |

### 6.3 Daftar yang masih perlu Validate

`out/perlu_validate_ulang_202608.csv` — **123 head** yang snapshot versi
aktifnya berbeda dari working set (uji md5 atas pasangan
`group_head_id:composition_pct`, jadi menangkap semua penyebab, bukan hanya
paket tertentu).

**Prioritas: 8 head `Current` + `is_active = true`** — ini satu-satunya yang
memberi dampak ke costing produksi. 115 sisanya `Waiting`/tidak aktif.

| legacy_sys_id | versi aktif | validated_at versi aktif |
|---|---:|---|
| 20200701531 (Z-204-S), 20201202842, 20201202845, 20210102884, 20210102906, 20210202920, 20210302923, 20220203205 | 7 | 2026-09-09 08:52–08:53 — sebelum remap |

**Selama 8 head ini belum di-Validate ulang, reproses costing tidak akan
mencerminkan remap paket 01.**
