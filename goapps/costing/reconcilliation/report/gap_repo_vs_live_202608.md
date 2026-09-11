# Fase 1 — Gap Repo goapps-backend vs Live DB

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Tanggal | 2026-09-09 |
| Repo | `github.com/mutugading/goapps-backend` (private) |
| Clone | `git clone --depth 1` — HEAD `83eb0c4e`, 2026-09-08 08:28 +0700 |
| HEAD subject | `fix(finance): backfill mst_mb_head shade code/name for legacy Oracle-imported recipes (#184)` |
| Live DB | `goapps` @ localhost:25432, PostgreSQL 18.1 |

---

## 1. Status migration — **NOL PENDING, tidak blocking**

| Sisi | Versi finance |
|---|---|
| Repo `services/finance/migrations/postgres/` — versi tertinggi | **000508** |
| Live `schema_migrations_finance.version` | **508** |
| Live `dirty` | `false` |
| Migration di repo dengan nomor > 508 | **tidak ada** |

Repo dan live DB **sinkron penuh**. Tidak ada migration pending yang menyentuh
tabel costing, jadi tidak ada selisih Fase 2–8 yang bisa dijelaskan sebagai
artefak migration. **Fase 2 boleh jalan.**

### 1.1 Migration yang prompt curigai pending

Semuanya ada di repo dan bernomor di bawah 508 → sudah applied:

| Versi | File |
|---|---|
| 000468 | `000468_wire_group_c_master_data.up.sql` |
| 000469 | `000469_seed_derived_cost_params.up.sql` |
| 000470 | `000470_backfill_group_c_capp.up.sql` |
| 000477 | `000477_register_mb_spin_reader_columns.up.sql` |
| 000479 | `000479_create_mst_mb_cross_section.up.sql` |

### 1.2 Catatan penomoran

Repo punya 289 versi distinct dengan nomor tertinggi 508 — jadi 219 nomor
"hilang" dari urutan. Semua nomor yang hilang berada **di bawah 400**; rentang
400–508 utuh tanpa celah. Ini konsisten dengan renumbering/squash riwayat awal,
bukan indikasi migration yang tercecer.

Keterbatasan yang perlu disadari: `golang-migrate` hanya menyimpan **versi
terakhir**, bukan daftar setiap migration yang pernah dijalankan. Jadi
kesimpulan "semua applied" bersandar pada asumsi penerapan berurutan. Tidak ada
cara memverifikasi per-migration dari `schema_migrations_finance` saja.

---

## 2. Yang ada di kode tapi belum masuk pemahaman prompt

### 2.1 RM group period versioning (000503–000505) — **mengubah desain Fase 2**

Tiga migration terbaru menambahkan mekanisme yang tidak ada di prompt:

| Versi | Isi |
|---|---|
| 000503 | `CREATE TABLE cst_rm_group_head_period` — snapshot per `(period, group_head_id)` |
| 000504 | `CREATE TABLE cst_rm_group_detail_period` — snapshot per `(period, group_detail_id)` |
| 000505 | Backfill data-only dari riwayat `cst_rm_cost` / `cst_rm_cost_detail` |

Aturan resolusinya, dari komentar `000503` dan
`services/finance/internal/application/rmcost/calculate_handler_v2.go:145-175`:

- `cst_rm_group_head` tetap **anchor row** = konfigurasi "current/latest".
  Identitasnya (`group_head_id`, `group_code`) yang dirujuk
  `cst_rm_cost.group_head_id`.
- `cst_rm_group_head_period` = snapshot konfigurasi **untuk satu periode**, ada
  hanya jika periode itu pernah di-edit eksplisit atau di-backfill.
- Engine (`loadHeadAndDetails` → `overlayHeadForPeriod`) melakukan **period-aware
  read**: coba snapshot periode dulu, fallback ke anchor kalau snapshot tidak
  ada. Anchor entity tidak pernah dimutasi — di-overlay ke value baru.
- `is_backfilled` membedakan edit user asli dari salinan backfill.

> **Konsekuensi untuk Fase 2.** Instruksi prompt ("Master group:
> `cst_rm_group_head`") sudah **usang** sejak 000503. Sumber yang benar untuk
> "konfigurasi apa yang dipakai menghitung 202608" adalah
> `cst_rm_group_head_period WHERE period='202608'`, dengan fallback ke anchor.
> Yang tetap benar dari prompt: join identitas via `group_code` (bukan
> `group_head_id`), karena `group_code` hanya ada di anchor.

**Coverage 202608 di live DB:**

| Tabel | 202604 | 202605 | 202606 | 202607 | 202608 | 202609 |
|---|---:|---:|---:|---:|---:|---:|
| `cst_rm_group_head_period` | 346 | 320 | 350 | 350 | **350** | 350 |
| `cst_rm_group_detail_period` | 512 | 509 | 524 | 525 | **525** | 525 |

Untuk 202608: 350 snapshot head, **semuanya `is_backfilled = true`, nol edit
per-periode asli**. Anchor `cst_rm_group_head` juga 350 baris (semua aktif), dan
ke-350 anchor punya snapshot 202608.

**Sudah diverifikasi**: snapshot 202608 **identik** dengan anchor di seluruh 16
kolom yang di-snapshot (`differs = 0`; diuji per kolom: name, cost_percentage,
cost_per_kg, flag_valuation, flag_marketing, flag_simulation, init_val_*,
marketing_*, valuation_flag_v2, marketing_flag_v2 — semua 0 selisih). Jadi untuk
202608 kedua sumber memberi angka sama. Fase 2 tetap akan membaca dari snapshot
periode karena itu yang dipakai engine, dan kesetaraan ini dicatat sebagai
terbukti, bukan diasumsikan.

### 2.2 Trap penamaan kolom antar dua tabel itu

Kolom yang sama diberi nama berbeda di anchor vs snapshot. Ini sumber error
diam-diam kalau query ditulis dari asumsi:

| Makna | `cst_rm_group_head` (anchor) | `cst_rm_group_head_period` (snapshot) |
|---|---|---|
| Nama group | `group_name` | `name` |
| Colorant | `colourant` (ejaan British) | `colorant` (ejaan American) |
| Valuation tier V2 | `valuation_flag` | `valuation_flag_v2` |
| Marketing tier V2 | `marketing_flag` | `marketing_flag_v2` |
| Kode group | `group_code` | **tidak ada** — ambil dari anchor |

Nilai `valuation_flag_v2` yang sah (dari CHECK constraint 000503):
`AUTO`, `CR`, `SR`, `PR`, `CL`, `SL`, `FL`.
`marketing_flag_v2`: `AUTO`, `SP`, `PP`, `FP`.
`flag_valuation` / `flag_marketing` / `flag_simulation` (V1):
`CONS`, `STORES`, `DEPT`, `PO_1`, `PO_2`, `PO_3`, `INIT`.

Catat: daftar tier di prompt (`CL/SL/FL/CR/SR/PR`) benar untuk V2 tapi
**kehilangan `AUTO`**, yang justru nilai default.

### 2.3 Migration lain yang relevan (497–508)

| Versi | Judul | Relevan ke |
|---|---|---|
| 000497 | `add_idx_mbs_ldr_type` | Fase 4 (LDR) |
| 000498 | `add_mbs_lusture_code` | Fase 3/4 |
| 000499 | `relabel_mbs_cc_display_name` | Fase 4 (cross section) |
| 000500 | `extend_job_type_for_mb_bulk_transition` | job/calc |
| 000501 | `allow_none_rm_cost_flag_valuation_used` | Fase 2 — `flag_valuation_used` kini boleh `NONE` |
| 000502 | `allow_none_rm_cost_flag_marketing_used` | Fase 2 — idem untuk marketing |
| 000503–000505 | RM group period versioning | Fase 2 (lihat §2.1) |
| 000506 | `backfill_root_spin_ldr_from_head` | Fase 4 — LDR root spin di-backfill dari head |
| 000507 | `backfill_root_spin_and_cpm_shade_from_head` | Fase 4/5 |
| 000508 | `backfill_mbh_shade_from_legacy_code` | Fase 3 — shade MB dari kode legacy |

000501/000502 berarti `flag_valuation_used` bisa bernilai `NONE`. Fase 2 harus
memperlakukan `NONE` sebagai kategori tersendiri, bukan data hilang.

000506–000508 adalah backfill yang menyentuh LDR root spin dan shade MB.
Semuanya sudah applied, jadi Fase 3/4 membandingkan kondisi **pasca-backfill**.

### 2.4 `cost_product_type` — 32 tipe, MB = 29 terkonfirmasi

Gotcha prompt bahwa MB = `29` **benar**. Nama kolom PK-nya `cpt_type_id`
(bukan `product_type_id`), dan tabelnya berkolom `cpt_type_code`,
`cpt_type_name`, `cpt_is_active`.

32 tipe aktif semuanya. Yang perlu diperhatikan untuk Fase 8 karena tidak
punya padanan jelas di legacy yarn DAG: `30 PLY` (Ply Product),
`31 KYP` (Know Your Product), `32 TTH` (Twisted Textured Hank Dyeing),
`19 OTH` (Other).

---

## 3. Yang ada di DB tapi tidak di repo

Tidak ditemukan tabel costing di live DB yang tidak dijelaskan oleh migration di
repo, **kecuali** tabel kerja manual yang jelas bukan hasil migration:

| Pola | Contoh | Dugaan |
|---|---|---|
| Backup bertanggal | `cost_product_master_bkp_mblink_20260730`, `cst_rm_group_detail_bkp_l1sync_20260730`, `cst_rm_group_detail_bkp_setup_20260722`, `cst_rm_group_head_bkp_mktfreight_20260722`, `mst_mb_head_bkp_params_20260730` | snapshot manual sebelum operasi data |
| Staging recon/migrasi | `stg_leg_*` (15 tabel), `stg_l1_*`, `stg_mb_*`, `stg_rm_*`, `stg_mkt_freight_20260722` | kerja recon/onboarding manual |
| View recon | `vw_recon_all`, `vw_recon_fg`, `vw_recon_mb`, `vw_recon_l1_setup`, `vw_recon_l2_input`, `vw_recon_l3_rmcost`, `vw_fg_cost_check`, `vw_mb_bridge`, `v_stg_cycc_long` | dibuat manual di luar migration |
| Backup route | `bak_route_rm_hombitan_20260806` | idem |

Sesuai keputusan IT Lead, aparatus recon ini **tidak dipakai** — Fase 2 ke atas
ditulis bersih dari query legacy di folder kerja. Dicatat di sini hanya supaya
keberadaannya terdokumentasi dan tidak mengagetkan pembaca laporan.

---

## 4. Keterbatasan pemeriksaan ini

`git clone --depth 1` (sesuai instruksi prompt) hanya membawa **satu commit**.
Akibatnya:

- Tidak bisa `git log` / `git diff` untuk melihat "perubahan pada `compute.go`,
  `calculate_handler_v2.go`, modul RM/MB **sejak rilis terakhir**" seperti
  diminta prompt. Riwayat dan tag rilis tidak ada di clone ini.
- Yang bisa dilakukan, dan sudah dilakukan: membaca **kondisi kode saat ini**
  (HEAD 2026-09-08) dan mencocokkannya dengan struktur live DB.

Kalau analisis diff antar rilis memang dibutuhkan, perlu `git fetch --unshallow`
atau clone penuh — konfirmasi dulu karena repo ini 2.634 file.

Satu koreksi terhadap peta mental prompt: `calculate_handler_v2.go` **tidak**
berada di `internal/application/costcalc/`. Lokasi sebenarnya:

- `services/finance/internal/application/rmcost/calculate_handler_v2.go` — RM cost
- `services/finance/internal/application/mbdozing/calculate_handler.go` — MB dozing

Di `internal/application/costcalc/` yang ada adalah `compute.go`, `loader.go`,
`process_chunk.go`, `formula.go`, `mb_cost_row_guard.go`, `periods_handler.go`,
dan handler read/approve. Tidak ada file bernama `calculate_handler.go` di
`costcalc` — jadi klaim prompt "`calculate_handler.go` (V1) = dead code" tidak
bisa diverifikasi di lokasi yang disebut; file V1 tampaknya sudah dihapus, bukan
sekadar tidak dipakai.

---

## 5. Kesimpulan Fase 1

| Pertanyaan | Jawaban |
|---|---|
| Ada migration pending yang menyentuh tabel costing? | **Tidak.** Repo 508 = live 508, `dirty=false` |
| Fase 2 boleh jalan? | **Ya** — tidak ada selisih yang akan jadi artefak migration |
| Ada perubahan kode yang mengubah rencana recon? | **Ya** — RM group period versioning (§2.1) mengubah sumber data Fase 2 |
| Ada hal di DB yang tidak dijelaskan repo? | Hanya tabel/view kerja manual (§3), bukan struktur produk |
