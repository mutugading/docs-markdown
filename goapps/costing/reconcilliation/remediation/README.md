# Paket Remediasi — hasil `clarification_1.txt`

| Field | Value |
|---|---|
| Dibuat | 2026-09-09 |
| Sumber | Recon Fase 0–4 + `clarification/clarification_1.txt` + `clarification/cmp_01_rm_cost_matrix_202608_clarification.xlsx` |
| Generator | `build_remediation.py` (hanya membaca file ekspor; tidak menyentuh DB) |
| Status eksekusi | **SUDAH DIJALANKAN 2026-09-10** atas instruksi IT Lead. Semua paket ter-COMMIT ke Postgres goapps. Lihat `EXECUTION_LOG_202608.md` untuk hasil, verifikasi, dan dua penyimpangan yang dicatat. |

> ⚠️ **Semua file `.sql` di folder ini MENULIS ke database** — ke Postgres
> **goapps (sistem baru) saja**. Legacy Oracle tidak pernah ditulis.
>
> **Sudah dieksekusi 2026-09-10.** Jangan jalankan ulang tanpa memeriksa
> idempotency: paket 04, 05 dan 02 akan menduplikasi baris kalau diulang.
> Runner yang dipakai: `pgw.sh`. Log lengkap: `EXECUTION_LOG_202608.md`.

---

## Urutan eksekusi yang disarankan

| # | File | Isi | Baris | Risiko |
|---|---|---|---:|---|
| 1 | `02_rm_fixed_value_init.sql` | Set `flag_*='INIT'` + `init_val_*` dari rate legacy | 32 group | Sedang — mengubah cara tier resolve |
| 2 | `01_rm_group_code_remap.sql` | Remap group code hasil klarifikasi | 8 remap aktif | **Tinggi** — 259 route pada satu group |
| 3 | `05_mb_spc3221_missing_lines.sql` | Insert line SPC 3221 yang hilang | 4 line | Rendah |
| 4 | `04_mb_recipe_missing_insert.sql` | Insert komposisi 30 resep yang hilang | 160 line | Rendah — head-nya semua draft |
| 5 | `07_mb_ldr_set_from_legacy.sql` | Set LDR sisi baru := LDR legacy (patokan user) | 104 spin | Sedang |
| 6 | `06_mb_ldr_adjustment.sql` | Isi `mbs_ldr_adjustment_pct` | 955 spin | Rendah — kolom masih NULL semua |

> Urutan aktual yang dijalankan: **01 → 05 → 04 → 07 → 06**. Paket 07 **wajib**
> sebelum 06, karena 06 menghitung adjustment dari `mbs_run_ldr_pct` yang
> disetel oleh 07. Angka 06 menjadi 955 (bukan 923) justru karena urutan ini —
> rekonsiliasinya ada di `EXECUTION_LOG_202608.md` §3.

Paket `03` hanya analisis (CSV), tidak ada script.

**Setelah paket 1–4:** MB dan produk terkait **harus di-recalculate** lewat
aplikasi supaya `cst_product_cost` mencerminkan perubahan. Paket 01 dan 04
menyentuh `mst_mb_composition` (working set) — MB-nya harus di-**Validate**
lewat UI supaya `mst_mb_composition_version` terbentuk; tanpa itu engine tetap
memakai snapshot lama. Jangan `UPDATE` tabel `_version` langsung.

---

## Paket 01 — Remap group code

**Dasar:** kolom `KLARIFIKASI` berisi angka → group code lama salah dipetakan
saat migrasi dan harus menunjuk group code hasil klarifikasi.

**Bukti bahwa klarifikasi ini benar:** 6 dari 11 target punya `cost_val` 202608
yang **persis sama** dengan rate legacy group sumbernya:

| src | rate legacy src | dst | `cost_val` dst | |
|---|---:|---|---:|---|
| 202006002 | 29,192788 | 202007204 | 29,192788 | **persis** |
| 202006014 | 62,432132 | 202007161 | 62,432132 | **persis** |
| 202006023 | 9,358111 | 202007600 | 9,358111 | **persis** |
| 202006051 | 12,347779 | 202007176 | 12,347779 | **persis** |
| 202006084 | 63,450487 | 202007179 | 63,450487 | **persis** |
| 202007182 | 32,900000 | 202007191 | 32,900000 | **persis** |
| 202006010 | 8,041407 | 202007167 | 8,222665 | dekat (tier PR) |
| 202006011 | 14,405066 | 202007619 | 7,000000 | beda — tapi `cost_mark` 14,405066 persis |
| 202006054 | 9,313075 | 202007594 | 10,063075 | beda 0,75 |
| 202007173 | 3,777396 | 202007618 | 5,453425 | beda — ini temuan Fase 2 |

**Dampak per remap** (sudah diverifikasi lewat query, bukan estimasi):

| src → dst | `cost_route_rm` | resep MB | status di script |
|---|---:|---:|---|
| 202006002 → 202007204 | **259** | **258** | remap |
| 202006011 → 202007619 | 98 | 98 | remap |
| 202007173 → 202007618 | 54 | 54 | remap |
| 202006051 → 202007176 | 22 | 21 | remap |
| 202006084 → 202007179 | 20 | 20 | remap |
| 202006014 → 202007161 | 13 | 13 | remap |
| 202007182 → 202007191 | 13 | 13 | remap |
| 202007184 → 202007184 | 3 | 3 | **SKIP — self-map** |
| 202006010 → 202007167 | 1 | 1 | remap |
| 202006023 → 202007600 | 0 | 0 | SKIP — tanpa referensi |
| 202006054 → 202007594 | 0 | 0 | SKIP — tanpa referensi |

⚠️ **`202007184` perlu klarifikasi ulang.** KLARIFIKASI-nya `202007184`, yaitu
group code-nya sendiri, dan rate-nya di sistem baru tetap `0` dengan
`flag_used='NONE'`. Legacy punya rate 16,12. Jadi remap ke dirinya sendiri tidak
menyelesaikan apa pun. Sementara ini saya perlakukan sebagai kandidat *fixed
value* di Paket 02 (nilai 16,12), tapi mohon dikonfirmasi apakah maksudnya
group code lain.

⚠️ **`202007173 → 202007618` (HOMBITON → HOMBITAN LCS)** bertumpuk dengan dua
temuan lain: re-grouping `PIG0000032` yang menyentuh 51 resep MB (Fase 3 §7) dan
selisih material ACTUAL 44,37% pada `202007618` (Fase 2). Anda sudah
mengonfirmasi dampaknya disetujui, tapi setelah remap ini 54 resep tambahan akan
memakai rate 5,453425 — bukan 3,777396 seperti legacy. Selisih itu **tidak**
hilang dengan remap.

---

## Paket 02 — Fixed value (`flag_*='INIT'`)

**Hipotesis Anda terbukti benar.** Mekanismenya sudah ada tapi belum pernah
dipakai:

- CHECK constraint (migration 000503) mengizinkan
  `flag_valuation IN ('CONS','STORES','DEPT','PO_1','PO_2','PO_3','INIT')`
- **Seluruh 350 group** saat ini `flag_valuation` / `flag_marketing` /
  `flag_simulation` = **`'CONS'`**
- **`init_val_valuation` / `init_val_marketing` / `init_val_simulation` NULL
  untuk seluruh 350 group** (0 non-null)

Karena tidak ada data konsumsi 202608 untuk group-group ini, tier `CONS` tidak
resolve → `flag_*_used = 'NONE'` → rate 0. Itulah 25 ACTUAL / 42 SELLING yang
Anda lihat.

**Isi paket:** 32 group ber-KLARIFIKASI `NO COST` (25), `ITEM NOT FOUND` (5),
`ERROR` (1), plus `202007184` (self-map). Nilai `init_val_*` diambil dari legacy
periode 202608 — `CGCH_LANDED_COST` untuk ACTUAL, `CGCH_MARKET_RATE1` untuk
SELLING.

Script meng-update **anchor** (`cst_rm_group_head`) **dan snapshot 202608**
(`cst_rm_group_head_period`), karena engine membaca snapshot periode.

> Jalur yang lebih aman: lakukan lewat UI aplikasi, supaya write-through
> anchor↔snapshot terjaga sesuai desain `update_handler.go`. SQL di sini untuk
> kalau memang mau langsung ke DB.

`202411808` PALIOGEN RED K3911 ber-KLARIFIKASI **"ERROR, HAS TO CHECK BY DEV"**
— tetap saya masukkan dengan nilai legacy 209,00, tapi jangan dijalankan sebelum
dev memeriksa. Dia direferensikan 4 route / 4 resep.

---

## Paket 03 — Analisis 13 legacy group tanpa padanan (CSV saja)

Pertanyaan Anda: *"bisa kamu cek ini dipake di product yang mana."*

**Jawaban: 12 dari 13 tidak dipakai satu resep MB pun di legacy.**

| Kesimpulan | Group | Aksi |
|---|---:|---|
| Tidak dipakai di resep legacy | **12** | aman di-retire, **tidak perlu dibuat** |
| Dipakai 1 resep legacy | **1** | bersyarat — lihat di bawah |

Satu yang dipakai: **`202608943` PIG0000085 / TEXTTONE VIOLET 5830 DDR**
(landed 39,135), dipakai 1 baris di head `20260805400` **TAWANG BL**. Tapi head
itu sendiri **tidak ada di sistem baru** — dia salah satu dari 99 head
legacy-only yang berstatus `Waiting`. Jadi group ini hanya perlu dibuat kalau
head TAWANG BL juga dimigrasikan.

**Catatan bentuk data — ini penting sebelum membuat group baru.** Dari 13 itu,
**10 adalah spesifikasi yarn**, bukan raw material:

`SPD 20/1`, `SPD 44/1`, `SPD 78/1`, `POY 100/24`, `FDY 75/36`, `POY 80/72`,
`IDY 150/48 BR`, `POY 310/288`, `IDY 300D / GPD-7`, `PTY 300/288 CATIONIC`

Di sistem baru, spec yarn semestinya berupa **PRODUCT** (`cost_product_master`,
dirujuk route lewat `crm_rm_type='PRODUCT'`), **bukan** RM group. Bukti:
`202505854` (POY 310/288/RND/SD/SIM/NS/1/O) **sudah ada sebagai product** dengan
nama yang persis sama.

Jadi saya **tidak** membuat script insert group untuk 13 ini — membuat RM group
dari spec yarn akan salah bentuk secara struktural. Hanya 3 yang benar-benar RM
(`NOVANIK-1010`, `TIO2 LCS`, `PIG0000085`), dan ketiganya tidak dipakai kecuali
kasus bersyarat di atas.

Kalau Anda tetap ingin group dibuat setelah membaca ini, sebutkan yang mana dan
saya siapkan scriptnya.

---

## Paket 04 — Insert komposisi 30 resep yang hilang

30 head bucket `recipe-missing-in-new`: ada di dua sisi, tapi komposisinya tidak
termigrasi. Semuanya `check_status='Waiting'`, `mbh_current_version = 0`,
`is_active = false`. Total **160 baris komposisi legacy**, semuanya sekarang bisa
di-insert.

Dua bentuk baris ditangani berbeda:

| `source_type` | Target kolom | Baris |
|---|---|---:|
| `GROUP` | `mbcm_group_head_id` | 156 |
| `MB` (nested) | `mbcm_mb_ref_mbh_id`, group NULL | 4 |

Empat baris nested MB itu menunjuk head legacy `20220803365`
(*MGT SPC RD 3221/30-D-02464 FOR HERON*) — dan head itu **ADA dan sehat** di
sistem baru (4 baris komposisi, sum 100,000, sudah dirujuk 137 referensi nested
lain). Jadi nested MB **tidak** di-remap.

`mbcm_legacy_sys_id` diisi `CMBI_SYS_ID` legacy supaya jejak provenance ada dan
insert bisa diperiksa idempotensinya.

**Termasuk head `20250904908` MGT AKOVA BN 6785** — head SPC 3221 kelima. Lihat
Paket 05.

---

## Paket 05 — Line SPC 3221 yang hilang pada 4 resep

**Akar penyebab pasti:** legacy group `202208630` (SPC 3221) **tidak ada** di
`cst_rm_group_head`. Baris komposisinya gagal termigrasi, sehingga sum resep
jadi 75 / 75 / 65 / 40,420 bukan 100.

Keputusan Anda: SPC 3221 retired, yang mereferensikannya diarahkan ke
**RED MGTP-3221** (`202007188`).

| head | nama | sum sekarang | + pct | sum setelah |
|---|---|---:|---:|---:|
| 20241004279 | MGT NEWRY BN 6628 N-D-03671-B | 75,000 | 25,00 | **100,000** |
| 20241204383 | MGT NEWRY BN 6628-D-03671-B | 75,000 | 25,00 | **100,000** |
| 20250804838 | MGT IXORA RD 3431--B | 65,000 | 35,00 | **100,000** |
| 20250804886 | MGT ZENBU RD 3426-D-04141-B | 40,420 | 59,58 | **100,000** |

⚠️ **Ada head SPC 3221 kelima yang luput dari Fase 3**: `20250904908`
**MGT AKOVA BN 6785** (pct 41,28). Dia tidak masuk daftar "materially off" Fase 3
karena `mbh_current_version = 0` dan **0 baris komposisi** — seluruh resepnya
hilang, bukan hanya baris SPC-nya. Ditangani di Paket 04.

Perbedaan penting: SPC 3221 ada **dua bentuk** di legacy —
- RM group `202208630` → **tidak ada** di sistem baru → ini yang di-remap
- MB head `20220803365` → **ada dan sehat** → **tidak** di-remap

---

## Paket 06 — LDR adjustment

Keputusan Anda: selisih `mbs_ldr_calculated_pct` vs `mbs_run_ldr_pct` dicatat
sebagai adjustment, karena bisa jadi ada adjustment user atas nilai calculated.

**Rumus:** `adjustment = run_ldr_pct − calculated_pct`, sehingga
`calculated + adjustment = run_ldr` (LDR aktual).

- **923 spin** terdampak
- `mbs_ldr_adjustment_pct` saat ini **NULL untuk seluruh 2.799 baris**
- `mbs_ldr_is_actual` juga `false` untuk seluruh baris — jadi tidak ada spin yang
  LDR-nya dikunci, semuanya masih terbuka ditimpa `syncRootSpinLDRFromHead`

Script memakai **satu statement set-based**, bukan 923 UPDATE terpisah.

### Yang belum saya jalankan — perlu keputusan Anda

Instruksi kedua Anda: *"17 selisih kecil pada spin Current — selisihnya masukkan
saja di adjustment value."* Itu selisih **legacy vs sistem baru**, bukan
`calculated` vs `run_ldr`. Satu kolom `mbs_ldr_adjustment_pct` tidak bisa memuat
dua definisi sekaligus.

Saya jalankan rumus yang terdefinisi jelas (`run − calculated`) dan **tidak**
menimpanya dengan selisih legacy. Kalau yang Anda maksud adalah
`adjustment = run_ldr_baru − LDR_legacy` untuk 104 spin yang berbeda (18 di
antaranya `Current`), sebutkan dan saya siapkan varian keduanya —
daftar 104 spin itu ada di `out/cmp_03_mb_ldr_dosing_202608.csv`
(`metric = LDR_AKTUAL`, `classification = differs`).

---

## Item lain dari `clarification_1.txt` — tanpa script

| Item | Keputusan Anda | Status |
|---|---|---|
| 2 nilai `CMBS_LDR_PRSN` non-numerik (`2.00%`, `3.00POY 445/96/...`) | "di-nol-kan saja biar tidak error" | **Aturan query, bukan update DB.** Legacy beku. Sudah diterapkan di Fase 4 sebagai kelas `nonnumeric-source`; di query manapun pakai penjagaan `REGEXP_LIKE` sebelum `TO_NUMBER` lalu jadikan 0. Contoh ada di `cmp_03_mb_ldr_dosing_202608.sql` A4/A6. |
| 7 line orphan di legacy | "pastikan dulu, kalo sudah tidak ada yang refer berarti bekukan saja" | **Sudah dipastikan.** Ketujuhnya baris anak di `CST_MST_BATCH_ITEM` dengan `CMBI_CMBH_SYS_ID` **kosong** dan `CMBI_COMPOSITION` NULL. Tidak ada tabel yang bisa mereferensikan baris komposisi (dia leaf), dan parent head-nya tidak ada. **Aman dibekukan, tidak ada aksi.** |
| `mbcv_is_carrier` tidak pernah di-set | "tinggalkan saja" | Tidak ada aksi. Dicatat di Fase 3 §8.3 bahwa kolom ini tidak bisa dipakai mengidentifikasi carrier. |
| `CMBS_CHECK_STATUS` tidak dimigrasikan | "biarkan saja, karena mekanismenya berbeda" | Tidak ada aksi. Konsekuensi: angka "18 selisih pada spin produksi" hanya bisa ditentukan dari sisi legacy. |
| SSOT boughtout | "`mbh_is_boughtout`" | Tidak ada aksi. Tercatat: `mbh_is_boughtout`=true hanya 7 head, sementara `mbh_check_status='Boughtout'` 137 head — jadi **130 head Boughtout tidak ter-flag**. Kalau `mbh_is_boughtout` memang SSOT, 130 head itu perlu di-flag. Sebutkan kalau mau script-nya. |
| 3 spec POY berubah | "iya, disengaja" | Tidak ada aksi. |
| 152 spin out-of-range warisan legacy | "ok" | Tidak ada aksi. 5 spin yang out-of-range hanya di sisi baru tetap layak diperiksa. |
| Re-grouping HOMBITAN | "dampak sudah disetujui" | Tidak ada aksi, tapi lihat catatan di Paket 01. |
| 11 oracle id spin menggantung | "bisa jadi ini item baru yang perlu di-insert ke system baru" | **Perlu klarifikasi ulang** — lihat di bawah. |

### 11 oracle id spin menggantung — pembacaan saya berbeda

Anda menyimpulkan ini "item baru yang perlu di-insert ke sistem baru". Tapi
kesebelas baris itu **sudah ada di sistem baru** — justru `mbs_oracle_sys_id`-nya
menunjuk `CMBS_SYS_ID` yang **tidak ada di legacy**. Jadi bukan data yang perlu
di-insert.

Dua kemungkinan, dan keduanya butuh keputusan:
1. Baris legacy-nya dihapus setelah migrasi → id-nya sekarang menggantung.
   Perbaikan: `NULL`-kan `mbs_oracle_sys_id` supaya statusnya jujur sebagai
   spin yang lahir di sistem baru (seperti 99 baris lain yang memang NULL).
2. Id-nya salah tulis saat pembuatan → perlu dikoreksi ke id legacy yang benar.

Enam dari 11 berstatus `Spinning` + `is_active = true`, jadi ini baris produksi.
Daftarnya ada di laporan Fase 4 §5. Sebutkan mana yang benar dan saya siapkan
scriptnya.

Kalau yang Anda maksud sebenarnya **102 spin legacy-only** (yang ada di legacy
tapi tidak di sistem baru — 101 `Waiting` + 1 `Boughtout`), itu memang kandidat
insert dan saya bisa buatkan scriptnya.

---

## Keputusan yang sudah tercatat dan mengubah fase berikutnya

- **`_CUR` = periode 202608.** Jadi perbandingan 202608 lawan `_CUR` sah, dan
  kekhawatiran vintage saya di Fase 0 gugur. Guard `vintage ≥ 2026-08-01` tetap
  dipasang (tidak membuang satu produk pun).
- **MARKETING VALUE ↔ SELLING, VALUATION VALUE ↔ ACTUAL** dikonfirmasi.
- **FORECAST di-exclude** dari angka utama — legacy tidak punya forecast, hanya
  selling.
- **Laporan mencakup APPROVED dan CALCULATED** (17.611 produk), bukan hanya
  APPROVED.
- **Query dari legacy saja**, aparatus recon yang sudah ada di live DB tidak
  dipakai.
- **Dosing: bandingkan kedua cost type.** `20210800120` → ACTUAL,
  `20210800119` → SELLING. Untuk kasus 342 produk yang nilainya berbeda antar
  cost type, pakai `MAX()`.
