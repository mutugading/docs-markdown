# TASKS.md — Transporter v1

**82 task · 8 fase · ± 23 minggu.** Dibuat 2026-09-17.

## Cara memakai berkas ini

1. Jalankan `bash .ai/transporter/preflight.sh`. Ada `FAIL` → berhenti, laporkan.
2. Ambil task `[TODO]` paling atas yang blocker-nya sudah terpenuhi.
3. Baca `Refer` sebelum menulis kode. Kalau `Refer` menunjuk PRD, buka PRD-nya —
   jangan menebak dari ringkasan di sini.
4. Kerjakan. Commit: `feat(transporter): [T0xx] judul task`.
5. Ubah `[TODO]` → `[DONE]` di sini, isi satu baris di `PROGRESS.md`.
6. Task tidak bisa dikerjakan → `[BLOCKED]` + alasan di `PROGRESS.md`, lanjut ke
   task lain yang tidak bergantung padanya. **Jangan diakali.**

**Kedalaman task tidak rata, dan itu disengaja.** P0–P2 ditulis rinci karena akan
dikerjakan lebih dulu. P3–P7 sengaja lebih kasar — keputusan di fase itu baru
kelihatan setelah fase awal jadi. Sebelum masuk P3, regenerate bagian P3–P7
dengan kode nyata di tangan.

**Status:** `[TODO]` · `[WIP]` · `[DONE]` · `[BLOCKED]`

---

# P0 — Fondasi (T001–T015) · 2 minggu

### [DONE] T001 — Scaffold modul Transporter
**Refer:** `design.md` §1 · `gap.md` C-7
**Blocker:** —

Buat `Modules/Transporter` dengan `php artisan module:make Transporter`, lalu
rapikan supaya sama persis dengan struktur `design.md` §1. Termasuk:
`module.json` (`requires: ["Core","Auth","UI"]`), tiga provider
(`TransporterServiceProvider`, `RepositoryServiceProvider`, `RouteServiceProvider`),
`vite.config.js`, `config/config.php` kosong, `routes/web.php` + `breadcrumbs.php` kosong.
Daftarkan `"Transporter": true` di `modules_statuses.json`.

> Scope commit `transporter` **sudah didaftarkan** di `.github/COMMIT_CONVENTION.md`
> dan `CLAUDE.md` bersama commit berkas konteks ini — tidak perlu diulang.

**Acceptance:**
- [x] `php artisan module:list` menampilkan Transporter enabled
- [x] `php artisan route:list | grep transporter` jalan tanpa error (boleh kosong)
- [x] `vendor/bin/pint --test` bersih
- [x] `php artisan test --parallel` tetap hijau

---

### [DONE] T002 — Migration master (7 tabel)
**Refer:** `spec.md` §2.1 · `gap.md` catatan kritis 1–4
**Blocker:** T001

`transp_carrier`, `transp_rate_card`, `transp_rate_line`, `transp_charge_type`,
`transp_service_category`, `transp_posting_account`, `transp_document_type`.
Mulai dari `2026_09_18_000000`, naik per 1000. Salin bentuk dari
`Modules/LcControl/database/migrations/2026_05_16_100100_create_lc_posting_account_table.php`:
`protected $connection = 'oracle_mgthris'`, guard `migrationDisabled()`, `down()`.
Nama kolom UPPERCASE. Jangan pasang FK di sini — FK dipasang T007.

**Acceptance:**
- [x] **Setiap migration memakai `$connection = 'oracle_mgthris'`** — tidak ada satu pun tabel modul ini yang dibuat di `MGTDAT` (`spec.md` §0)
- [x] `php artisan migrate --pretend` jalan tanpa error
- [x] `php artisan test --parallel` hijau (SQLite membuat semua 7 tabel)
- [x] Setiap tabel punya 4 kolom audit + `_LEGACY_ID`
- [x] Setiap kolom uang `decimal(18,2)`, tidak ada `NUMBER` polos

---

### [DONE] T003 — Migration transaksi (5 tabel)
**Refer:** `spec.md` §2.2
**Blocker:** T001

`transp_order`, `transp_order_dn`, `transp_order_cost`, `transp_dn_stage` (prefix `TST_`),
`transp_grn_pull_attempt`. Perhatikan `transp_order` — 30+ kolom, termasuk kolom
gerbang yang nullable dan tidak dipakai v1 (`TRO_GATE_*`) dan penanda multi-truk.

**Acceptance:**
- [x] `TRO_IS_LEGACY` ada dan default 0 — dibutuhkan T007
- [x] `TST_DN_SYS_ID` unique
- [x] Index `(TOD_DN_TXN_CODE, TOD_DN_NO)` terpasang — 92k baris (RK-08)
- [x] Test hijau

---

### [DONE] T004 — Migration additional expense (2 tabel)
**Refer:** `spec.md` §2.3 · PRD §5.4
**Blocker:** T001

`transp_additional_expense` (`TAE_ORDER_ID` **NOT NULL** — F-04.3), `transp_additional_expense_line`.

**Acceptance:**
- [x] `TAE_ORDER_ID` not-nullable
- [x] Kolom approval lengkap: submitted/approved/rejected + reason
- [x] Test hijau

---

### [DONE] T005 — Migration provisi & tagihan (4 tabel)
**Refer:** `spec.md` §2.4 · PRD §5.6.1
**Blocker:** T001

`transp_provision` (**`TRP_ORDER_ID` UNIQUE, nullable**), `transp_provision_dn`,
`transp_bill`, `transp_bill_line`.

**Acceptance:**
- [x] `TRP_ORDER_ID` unique dan nullable — menegakkan F-06.9 tanpa menolak 331 baris yatim C-04
- [x] `TBL_LINE_TYPE` punya dua nilai saja (`PROVISION`, `EXPENSE_DIRECT`)
- [x] Test hijau

---

### [DONE] T006 — Migration infrastruktur (4 tabel)
**Refer:** `spec.md` §2.5 · `gap.md` C-9
**Blocker:** T001

`transp_document_scan` (prefix `TDC_`), `transp_posting_log`, `transp_payment`,
`transp_migration_exception`.
`transp_payment` dibuat tapi **tidak dipakai v1** — tulis di docblock migration
`-- reserved for the BPS payment-voucher release (PRD §5.11)` supaya tidak dikira lupa.

**Acceptance:**
- [x] `TPL_IDEMPOTENCY_KEY` unique
- [x] `(TDC_DN_TXN_CODE, TDC_DN_NO)` unique
- [x] Test hijau

---

### [DONE] T007 — Foreign key, index, dan unique index fungsional
**Refer:** `spec.md` §4 · `gap.md` C-5 · PRD C-08, RK-08
**Blocker:** T002, T003, T004, T005, T006

Satu migration terpisah yang memasang seluruh FK antar tabel baru.
`transp_rate_line` dan `transp_additional_expense_line` pakai
`ON DELETE CASCADE`; sisanya `RESTRICT`.
Unique index fungsional `transp_order_no_uk` dengan percabangan driver seperti di
`spec.md` §4 — **wajib**, kalau tidak CI (SQLite) merah.

> ⚠️ **Sudah tidak perlu: UNIQUE dan index per-tabel.** Versi pertama task ini
> juga memikul "seluruh index pendukung". Sejak T002/T003, setiap UNIQUE dan index
> yang hanya menyentuh satu tabel dipasang di migration `create` tabel itu sendiri —
> `TCA_UK01`, `TRC_UK01`, `TRL_UK01`, `TCT_UK01`, `TSC_UK01`, `TDT_UK01`,
> `TOD_UK01`, `TOD_NX01`, `TST_UK01`, `TRO_NX01`–`TRO_NX04`. Alasannya: index adalah
> bagian dari definisi tabel, dan memisahkannya berarti sebuah tabel hidup tanpa
> index-nya di antara dua migration.
>
> **Jangan pasang ulang** — Oracle menolak nama index ganda. T007 tinggal FK,
> unique index fungsional, dan index yang memang lintas tabel.

**Acceptance:**
- [x] `php artisan test --parallel` hijau di SQLite
- [ ] Cabang Oracle diuji manual di staging, bukan di CI — **belum, butuh staging.**
      DDL-nya sudah diverifikasi lewat `migrate --pretend` terhadap koneksi Oracle
      nyata dan menghasilkan `CREATE UNIQUE INDEX … CASE WHEN` yang benar
- [x] Tidak ada FK ke tabel `MGTDAT` — lintas schema, tidak dipasang
- [x] **Checkpoint review dengan Indra sebelum lanjut** (`plan.md` §Checkpoint) — OK 2026-09-17, dengan satu koreksi: `DECISIONS.md` D-06. Bagian FK-nya diverifikasi ulang saat staging Oracle, lihat baris di atas

---

### [DONE] T008 — Seeder sequence di `HM_MST_SEQUENCES`
**Refer:** `spec.md` §5 · `app/Helpers/SysIdHelper.php`
**Blocker:** T007

8 baris sequence. Seeder idempoten (`updateOrCreate` pada `hmms_seq_name`).

**Acceptance:**
- [x] `SysIdHelper::generate('TRANSP_ORDER_SYS_ID_SEQ', 'TEST')` mengembalikan string berformat benar
- [x] Seeder dijalankan dua kali tidak menduplikasi baris — **dan tidak memundurkan `HMMS_LAST_VALUE`**
- [x] Feature test membuktikan dua generate berturut-turut tidak menghasilkan nilai sama

---

### [DONE] T009 — Enum (13 enum)
**Refer:** `spec.md` §3
**Blocker:** T001

13 backed enum di `app/Enums/Master/` dan `app/Enums/Transaction/`. Setiap enum wajib
`label()`, `badgeVariant()`, `options()`. `TransactionStatusEnum` tambah
`nextActionLabel()` dan `timelineSteps()`.
Tabel transisi `spec.md` §3 diterjemahkan jadi method
`TransactionStatusEnum::canTransitionTo(self $to): bool` — dipakai T032, bukan
disalin ulang di service.

**Acceptance:**
- [x] Unit test menutup seluruh tabel transisi, termasuk yang **tidak** sah
- [x] Tidak ada nilai enum yang tidak punya `label()`

---

### [DONE] T010 — Model
**Refer:** `design.md` §3 · `spec.md` §7
**Blocker:** T009

**21** model di `Models/MgtHris/` (`$connection = 'oracle_mgthris'`) dan 10 model
read-only di `Models/MgtDat/` (`$connection = 'oracle_mgtdat'`).
Setiap model owned: `$fillable`, `$searchable`, `casts()` sebagai method,
`const CREATED_AT`/`UPDATED_AT` menunjuk kolom ber-prefix, trait `Searchable` dan
`LogsActivityWithDescription`.
Model `MgtDat/`: `$timestamps = false`, tidak ada `$fillable` (read-only), tidak ada
trait activity log.

**Acceptance:**
- [x] Setiap relasi didefinisikan dua arah
- [x] Tidak ada model `MgtDat/` yang punya method menulis — trait `ReadsOrionOnly` menutup tujuh jalur tulis
- [x] Feature test memuat satu baris tiap model owned tanpa error

> **21, bukan 22.** `transp_payment` punya migration tapi sengaja tanpa model —
> `gap.md` C-9. Ada testnya: kelasnya harus tidak ada.

---

### [DONE] T011 — Repository interface + Eloquent + binding
**Refer:** `design.md` §2 · `Modules/LcControl/app/Providers/RepositoryServiceProvider.php`
**Blocker:** T010

± 18 pasang interface + `Eloquent*`, dibinding di `RepositoryServiceProvider`.
Repository memakai `->search()` dari trait `Searchable` untuk filter.
**Pakai skill `mutugading-scaffold`** — jangan tulis boilerplate manual.

**Acceptance:**
- [x] Setiap interface terbinding; `php artisan about` jalan tanpa error resolusi
- [x] Tidak ada `DB::` facade di repository kecuali untuk query lintas schema ke `MGTDAT` — nol, tidak ada satu pun repository yang menyentuh `MGTDAT`
- [x] Setiap repository punya `paginate()`, bukan `all()` (NF-01) — tipe baliknya ikut diperiksa, `paginate()` yang diam-diam mengembalikan `Collection` tidak lolos

> **21 pasang, bukan 18.** Satu repository per model owned. `transp_payment`
> tidak punya model (C-9), jadi tidak punya repository.

---

### [DONE] T012 — Mesin posting GL milik Transporter
**Refer:** `DECISIONS.md` D-09 (yang mendefinisikan ulang task ini) · D-01 · `spec.md` §0, §7 ·
`gap.md` R-02, R-03, R-04, C-2 · PRD §4.4
**Blocker:** T011

> **Task ini ditulis ulang 2026-09-17.** Versi semula memindahkan 5 berkas dari
> `Modules/LcControl` ke `Modules/Core`. **Tidak jadi.** Indra memutuskan Transporter
> mendefinisikan mesin posting GL-nya sendiri — yang dipakai bersama adalah tabelnya,
> bukan kodenya. Alasan lengkap di `DECISIONS.md` D-09.
>
> **`Modules/LcControl` dan `Modules/Core` TIDAK DISENTUH SAMA SEKALI.** Nol berkas
> dipindah, nol berkas diedit. Kalau sebuah perubahan menyentuh salah satu dari keduanya,
> itu tanda ada yang salah.

Bangun di `Modules/Transporter/`:

| Lapisan | Berkas |
|---|---|
| `app/Data/Erp/` | `JournalVoucherData`, `JournalVoucherLineData` |
| `app/Interfaces/Erp/` | `JournalVoucherRepositoryInterface` |
| `app/Repositories/Erp/` | `EloquentJournalVoucherRepository` |
| `app/Services/Erp/` | `TransporterPostingService`, `ExchangeRateReader` |
| `app/Models/MgtDat/` | `FtUnpostedTransHeader`, `FtUnpostedTransDetail`, `FmTranDocNo` — **tiga-tiganya boleh tulis**, jadi TIDAK memakai trait `ReadsOrionOnly`; plus `FmAcntPeriod` dan `FmExchangeRate` yang read-only |
| `app/Exceptions/Erp/` | `DocumentAlreadyPostedException` |

**Perilakunya mengikuti `EloquentJournalVoucherRepository` LcControl** — itu bentuk yang
sudah terbukti jalan di produksi, dan angka GL wajib identik dengan sistem lama
(`ACCEPTANCE.md` §P3). Konstanta `COMP_CODE='002'`, `DIVN_CODE='001'`, `HEAD_NO_1=1`,
`HEAD_NO_2=2`. Urutan: ambil periode → kunci `FM_TRAN_DOC_NO` dengan `lockForUpdate()` →
insert header → insert detail → majukan `tdoc_cur_no`, semuanya dalam satu transaksi.
Balance DR=CR divalidasi di service dalam nilai **USD**, bukan di repository.

Dua hal yang **berbeda** dari LcControl, dan memang harus berbeda:

1. **`tranCode` parameter eksplisit.** LcControl menghitungnya sendiri lewat
   `tranCodeForLocation()` yang hanya bisa mengembalikan `JV`/`JJV`. Transporter
   mengirim `TPJV` (provisi) atau `TJV` (tagihan).
2. **`assertNotAlreadyPosted(string $tranCode, string $idempotencyKey)`** — LcControl
   tidak punya pengecekan idempotensi sama sekali. Rinciannya di bawah.

**Jangan** menulis `FT_TXN_AUTH` (D-01). **Jangan** membuat tabel apa pun di `MGTDAT`;
tiga tabel di atas adalah satu-satunya yang boleh ditulis (`spec.md` §0).

**`assertNotAlreadyPosted()` — sudah diriset terhadap produksi:**

- Satu query `UNION ALL` atas `FT_UNPOSTED_`/`FT_CUR_`/`FT_PRV_TRANS_HEADER`, bukan tiga
  query terpisah: satu snapshot read-consistent menutup jendela di mana Orion memindahkan
  dokumen antar tabel di tengah pengecekan.
- Predikat: `th_comp_code` **wajib** (mengubah SKIP SCAN jadi RANGE SCAN, cost 6.656 →
  1.149), `th_tran_code`, dan `TRIM(th_flex_10)`. `TRIM` wajib — ada baris historis
  ber-spasi depan.
- Dipanggil **sebelum** `lockForUpdate()` pada `FM_TRAN_DOC_NO`, supaya posting yang akan
  ditolak tidak sempat memegang kunci nomor dokumen.
- `th_flex_10` **sudah dipakai sistem transporter lama** dengan format `TBILL-{TRB_TRX_NO}`
  — 412 dari 412 baris `TJV` di `FT_CUR_` terisi. Formatnya **jangan diubah**: memakai
  yang sama berarti pengecekan ini menangkap dokumen sistem lama juga, tanpa jendela buta
  saat cutover. Untuk `TPJV` (0 baris, bebas) pakai prefix sendiri.
- Ini **advisory**, bukan jaminan: `MGTDAT` tidak boleh diberi constraint (`spec.md` §0).
  Jaminan sebenarnya ada di `TPL_UK01` pada `transp_posting_log`. Keduanya perlu, dan
  `QueryException` unique-violation dari `TPL_UK01` harus diterjemahkan ke pesan yang
  sama — jangan biarkan ORA-00001 mentah sampai ke pengguna.

**Acceptance:**
- [x] `git diff --stat` menunjukkan **nol** perubahan di `Modules/LcControl/` dan `Modules/Core/`
- [x] `php artisan test --parallel` hijau — 144 passed (dari 133)
- [x] `assertNotAlreadyPosted()` punya test dengan ketiga tabel, termasuk kasus nilai
      ber-spasi depan — **tabelnya dibuat sungguhan di SQLite, bukan di-mock**: yang paling
      mungkin salah ada di SQL-nya sendiri (TRIM, predikat comp code, filter tran code),
      dan mock hanya membuktikan query dipanggil, bukan bahwa jawabannya benar
- [x] Tidak ada `FT_TXN_AUTH` di kode mana pun — nol, yang tersisa hanya docblock yang
      menyatakan ia sengaja tidak ditulis
- [x] Tidak ada tabel `MGTDAT` yang ditulis selain tiga yang disebut di atas — dijaga
      assertion di `ModelTest`, bukan hanya konvensi

---

### [DONE] T013 — Log channel + config modul
**Refer:** `design.md` §9 · PRD §4.6 · `config/logging.php`
**Blocker:** T001

4 channel di `config/logging.php`: `transporter_dn_pull`, `transporter_generate`,
`transporter_posting`, `transporter_doc_scan`. Bentuknya sama dengan `lc_auto_mature`
(driver `single`, level `info`, `replace_placeholders`).
Isi `Modules/Transporter/config/config.php` persis seperti `design.md` §9, dan tambahkan
variabel `TRANSPORTER_*` ke `.env.example`.

**Acceptance:**
- [ ] `Log::channel('transporter_posting')->info('ping')` menulis ke `storage/logs/transporter_posting.log`
- [ ] `config('transporter.chip.max_gross_weight_kg')` mengembalikan 60000
- [ ] `.env.example` lengkap; tidak ada `env()` di luar berkas config

---

### [DONE] T014 — Menu, permission, route, breadcrumb
**Refer:** `spec.md` §8 · `Modules/Core/database/seeders/CmMenuSeeder.php`
**Blocker:** T011, T013

`TransporterPermissionSeeder` (25 permission), entri `cm_menus` lewat **migration
idempoten** (bukan seeder — keputusan Indra di T014: seeder tidak ikut jalan saat deploy),
kerangka `routes/web.php` dan `routes/breadcrumbs.php` untuk seluruh halaman yang akan
dibuat — route boleh sementara menunjuk placeholder, **breadcrumb-nya harus benar sejak
sekarang** karena `PageTitleHelper` mengambil judul tab dari situ.
Role baru: `Transporter Admin`, `Transporter Approver`, `Transporter Viewer`,
`Despatch`, `Stores`.

**Acceptance:**
- [ ] Menu Transporter muncul di sidebar untuk user ber-permission
- [ ] `php artisan route:list --name=transporter` menampilkan seluruh route rencana
- [ ] Setiap route punya breadcrumb; judul tab benar tanpa `#[Title]`
- [ ] Seeder idempoten

---

### [DONE] T015 — Helper + kalkulator tarif + unit test
**Refer:** `spec.md` §6.1–6.5 · `design.md` §5 · PRD NF-07, RK-04, RK-15
**Blocker:** T010

`DueDateHelper`, `GrossUpHelper`, `RateCardResolver`, `YarnRateCalculator`,
`ChipRateCalculator` — keduanya mengimplementasi `RateCalculatorInterface` dan
**tetap terpisah** (`design.md` §5).
Konversi kurs: helper tipis yang membalik `ExchangeRateService::getExchangeRate()`
(`spec.md` §6.5) — jangan panggil langsung dari service lain.

Ini task paling penting di P0. Semua yang disebut NF-07 sebagai area paling berisiko
lahir di sini.

**Acceptance:**
- [ ] `DueDateHelperTest` menutup tanggal 1–31, dua cabang
- [ ] `YarnRateCalculatorTest` mereproduksi `MTDC_TOTAL_RATE` untuk **500 transaksi
      historis acak** dengan selisih 0 — ini uji R-12 versi dini (RK-15)
- [ ] `ChipRateCalculatorTest` membuktikan tidak ada logika prioritas/kelebihan muatan
- [ ] `ExchangeRateParityTest` membandingkan hasil dengan `curs_usd_b` untuk 24 bulan terakhir (RK-04)
- [ ] `RateCardResolver` memilih kartu berdasarkan tanggal transaksi, dibuktikan test dengan 2 kartu bertumpang waktu
- [ ] **Checkpoint review dengan Indra sebelum lanjut**

---

# P1 — Master (T016–T026) · 2,5 minggu

### [DONE] T016 — Halaman master pengangkut (carrier)
**Refer:** PRD F-01.1, F-01.2, F-01.3, F-01.3a, F-01.16–F-01.21 · `spec.md` §2.1
**Blocker:** T011, T014

CRUD `transp_carrier` memakai skill `mutugading-crud`. Yang tidak boleh dilewatkan:
- `TCA_TYPE` menentukan validasi: `VENDOR`/`INTERNAL` **wajib** `TCA_SUPP_CODE`
  (dipilih lewat pencarian `OM_SUPPLIER`, bukan diketik); `BUYER_BORNE` **harus**
  `TCA_SUPP_CODE` NULL dan `TCA_LABEL` terisi.
- Nama, NPWP, alamat **dibaca dari `OM_SUPPLIER` saat ditampilkan**, tidak disimpan.
- Profil pajak dari `IM_VS_STATIC_VALUE` ditampilkan read-only.
- `TCA_IS_GROSS_UP` default mati, ditandai *deprecated* di UI, mengaktifkannya
  memunculkan konfirmasi dan tercatat di activity log.

**Acceptance:**
- [ ] Feature test: `BUYER_BORNE` dengan `supp_code` ditolak; `VENDOR` tanpa `supp_code` ditolak
- [ ] Kolom nama di daftar berasal dari `OM_SUPPLIER`, bukan dari tabel sendiri
- [ ] Pengangkut yang sudah dipakai transaksi tidak bisa dihapus (F-01.12)
- [ ] Descriptor e2e `e2e/pages/transporter/carrier.js` + doc dibuat (baca `e2e/CLAUDE.md` dulu)

---

### [DONE] T017 — Halaman rate card + validasi tumpang tindih
**Refer:** PRD F-01.4–F-01.7, F-01.12 · `spec.md` §2.1
**Blocker:** T016

CRUD `transp_rate_card` dengan masa berlaku. Aturan yang ditegakkan **di service**:
- F-01.7: tidak boleh dua rate card aktif dengan kombinasi dan periode bertumpang tindih.
  Ini yang memperbaiki 23 kombinasi ganda (T-26).
- F-01.6: menaikkan tarif = tutup `TRC_VALID_TO` kartu lama + buat kartu baru.
  Sediakan aksi *Naikkan tarif* yang melakukan keduanya dalam satu transaksi.
- Lookup destinasi & jenis truk dari `IM_VS_STATIC_VALUE`.

**Acceptance:**
- [ ] `RateCardOverlapTest` membuktikan penolakan untuk periode bertumpang, termasuk
      kasus `VALID_TO` NULL di kedua sisi
- [ ] Aksi *Naikkan tarif* menghasilkan tepat 2 baris, yang lama tertutup
- [ ] Riwayat tarif per pengangkut bisa dilihat

---

### [DONE] T018 — Editor rate line
**Refer:** PRD F-01.8 · `spec.md` §6.1
**Blocker:** T017

Panel baris tarif di dalam halaman rate card. `TRL_IS_OVERFLOW` sebagai checkbox
berlabel jelas ("pakai kelebihan muatan aktual") — bukan angka sentinel.
F-01.8: rate card tidak bisa diaktifkan tanpa minimal satu baris prioritas 1
(`W` untuk despatch, `Q` untuk chip).

**Acceptance:**
- [ ] Aktivasi tanpa baris prioritas 1 ditolak dengan pesan yang menyebut tipe yang dibutuhkan
- [ ] Preview perhitungan: masukkan qty, lihat baris mana yang kena dan berapa totalnya

---

### [DONE] T019 — Matriks tarif chip
**Refer:** PRD F-01.13, F-01.14, F-01.15 · §5.1.1
**Blocker:** T017

Halaman matriks **vendor angkutan × vendor chip** (20 × 7 = 90 kombinasi saat ini),
menunjukkan kombinasi mana yang belum punya tarif. Ini penyebab paling sering gagalnya
generate transaksi chip (F-09.9), jadi sel kosong harus mencolok dan bisa diklik
untuk membuat rate card-nya.
Pencocokan memakai **kode vendor**, bukan nama (F-01.15) — memperbaiki T-25.

**Acceptance:**
- [x] Sel kosong terlihat berbeda dan tautannya membuka form rate card terisi awal
- [x] Tidak ada satu pun perbandingan nama vendor di seluruh jalur ini

---

### [DONE] T020 — Duplikasi rate card
**Refer:** PRD F-01.10
**Blocker:** T018

Aksi salin satu rate card (beserta rate line-nya) ke destinasi / jenis truk lain.

**Acceptance:**
- [x] Hasil salinan melewati validasi tumpang tindih T017; kalau bentrok, ditolak dengan jelas
- [x] Rate line ikut tersalin dengan prioritas yang sama

---

### [DONE] T021 — Master jenis biaya tambahan
**Refer:** PRD F-04.1, C-11 · `spec.md` §2.1
**Blocker:** T011, T014

CRUD `transp_charge_type` + seeder ~~6~~ **7** baris (`ALAMAT` ditambahkan — D-16).
**Acceptance:** CRUD jalan · seeder idempoten · jenis yang sudah dipakai tidak bisa dihapus

---

### [DONE] T022 — Master kategori jasa
**Refer:** PRD F-02.12, F-02.12a
**Blocker:** T011, T014

CRUD `transp_service_category` + seeder ~~4~~ **5** baris (termasuk `TRUCKING_BANDARA`; `OTHERS` ditambahkan — D-17).
**Acceptance:** CRUD jalan · seeder idempoten

---

### [DONE] T023 — Master akun posting
**Refer:** PRD F-05.7, Appendix B · `spec.md` §2.1
**Blocker:** T011, T014

CRUD `transp_posting_account` dengan masa berlaku + seeder ~~8 baris dari Appendix B~~ **13 baris dari jurnal Orion (D-18)**.
Beri catatan di UI: **nama akun masih asumsi, menunggu konfirmasi Finance**.

**Acceptance:**
- [x] Seeder ~~8~~ 13 baris terisi (D-18 — **nomor akun menunggu konfirmasi Finance**)
- [x] Service pencari akun memilih berdasarkan tanggal dokumen, bukan tanggal hari ini
- [x] Tidak ada satu pun nomor akun literal di kode Transporter (`grep -rn "'404001'\|'208027'"` kosong)

---

### [DONE] T024 — Master jenis dokumen surat jalan
**Refer:** PRD F-10.5, F-10.6, F-10.7
**Blocker:** T011, T014

CRUD `transp_document_type` + seeder `LDN`, `PDN`, `JWDN` (`TDT_IS_CONTROLLED = 1`).
`CHPGRN` **tidak** diseed (F-10.6).

**Acceptance:**
- [x] Tiga baris terseed, tidak ada `CHPGRN`
- [x] `JWDN` ikut terkontrol — ini yang memperbaiki T-20

---

### [TODO] T025 — Monitoring GRN chip (pengganti TRNSP005)
**Refer:** PRD F-07.1, F-07.2
**Blocker:** T019

Daftar GRN `CHPGRN` sejak 1 April 2021 yang pengangkutnya belum ter-setup, dengan
status `BELUM DI SETUP` / `GRN BELUM APPROVE`. Aksi buat rate card `CHIP` otomatis
(jenis truk `TRAILER`, kapasitas 20.000, satu rate line tipe `Q` prioritas 1).

**Acceptance:**
- [ ] Daftar terpaginasi (NF-01)
- [ ] Aksi auto-create menghasilkan carrier + rate card + rate line dalam satu transaksi
- [ ] Baris yang sudah ter-setup hilang dari daftar setelah aksi

---

### [TODO] T026 — Export master
**Refer:** PRD F-01.11 · `app/Traits/FormatsNikForExport.php`
**Blocker:** T017

Dua export: daftar pengangkut, dan matriks tarif pengangkut × destinasi × jenis truk.
Keduanya sebagai queued job + `ReportStatusNotification`.

**Acceptance:**
- [ ] Export jalan sebagai job di queue `low`
- [ ] Notifikasi berisi tautan unduh
- [ ] Tidak ada kolom NIK di export ini; kalau nanti ada, wajib `FormatsNikForExport`

---

# P2 — Transaksi (T027–T041) · 5 minggu

### [TODO] T027 — Order service + penomoran
**Refer:** PRD F-02.1, F-02.2 · `spec.md` §5 · T-04
**Blocker:** T015, T017

`OrderService` + `OrderNumberService`. Nomor dari sequence per `(txn_code, tahun)`,
**bukan `MAX()+1`**. Format `{YYYY}{6 digit}`.

**Acceptance:**
- [ ] Feature test: 50 order dibuat berbarengan, tidak ada nomor duplikat
- [ ] Nomor mereset di pergantian tahun
- [ ] `TRO_IS_LEGACY = 0` untuk semua order baru

---

### [TODO] T028 — Halaman daftar transaksi angkutan
**Refer:** PRD F-02.10 · §5.2 catatan "Satu menu untuk tiga jalur"
**Blocker:** T027

**Satu** halaman daftar untuk ketiga jalur, dibedakan hanya oleh kolom `TRO_SOURCE`.
Filter: rentang tanggal, jenis, pengangkut, status, sumber. Terpaginasi (NF-01, 33k baris).

**Acceptance:**
- [ ] Satu route, satu komponen — bukan tiga halaman
- [ ] Query terpaginasi, eager load relasi carrier & rate card (tidak ada N+1)
- [ ] Badge tipe pengangkut mencolok (F-01.20)
- [ ] Descriptor + doc e2e dibuat

---

### [TODO] T029 — Form transaksi manual + tarik surat jalan
**Refer:** PRD F-02.1, F-02.3 · `spec.md` §7
**Blocker:** T028

Form entri `TPDN`. Pencarian surat jalan: `LDN`/`JWDN`/`PDN` dengan
`INVH_APPR_STATUS = 3` yang belum dipakai transaksi lain, menampilkan berat kotor
(WMS untuk LDN/JWDN, `INVI_FLEX_01` untuk PDN).

**Acceptance:**
- [ ] Surat jalan yang sudah dipakai transaksi lain tidak muncul di pencarian
- [ ] Berat kotor benar untuk ketiga jenis — feature test per jenis
- [ ] Menyimpan dua kali surat jalan yang sama ditolak oleh unique `(order_id, dn_txn_code, dn_no)`

---

### [TODO] T030 — Kalkulasi biaya + override
**Refer:** PRD F-02.4, F-02.5 · `spec.md` §6.1
**Blocker:** T029, T015

Hitung biaya otomatis lewat `YarnRateCalculator` begitu pengangkut + tujuan + jenis truk
+ qty lengkap. Hasil ditampilkan per baris (prioritas, tipe, qty, rate, total).
Override manual hanya untuk pemegang `transporter.override-cost`, **wajib alasan**,
menandai `TRO_IS_COST_OVERRIDDEN`.

**Acceptance:**
- [ ] Rate card yang dipakai adalah yang berlaku pada `TRO_ORDER_DATE`
- [ ] Override tanpa alasan ditolak
- [ ] Override tanpa permission tidak muncul di UI **dan** ditolak service

---

### [TODO] T031 — Jenis transaksi `TPSVC`
**Refer:** PRD §5.2.1, F-02.11–F-02.15
**Blocker:** T030, T022

`TPSVC` tanpa baris surat jalan. Wajib: pengangkut, tanggal, tujuan, nopol, sopir,
kategori jasa, keterangan. Biaya dari rate card bila ada; kalau tidak, manual + alasan.
Kategori `TRUCKING_BANDARA` boleh mencantumkan nomor `EDN` sebagai **referensi bebas**,
tidak ditarik sebagai baris surat jalan (F-02.12a).

**Acceptance:**
- [ ] `TPSVC` tanpa kategori ditolak
- [ ] `TPSVC` tidak bisa punya baris `transp_order_dn`
- [ ] Nomor berbentuk `TPSVC-{YYYY}{6}` — tidak ada lagi `TP_NO` berisi teks bebas

---

### [TODO] T032 — Approval maker-checker + inbox + approval massal
**Refer:** PRD §4.3, F-02.6, F-02.6a, F-02.6b, F-02.6c, §8 · `design.md` §4
**Blocker:** T028

Trait `EnforcesMakerChecker` + `OrderApprovalService`. Berlaku untuk **ketiga jalur**,
tanpa pengecualian. Halaman *approval inbox*. Approval massal tetap mencatat satu per
satu di activity log.
Unapprove: `Approved → Submitted`, hanya oleh approver, hanya selama belum diprovisi.

**Acceptance:**
- [ ] `OrderApprovalTest`: pembuat mencoba approve → `AuthorizationException`
- [ ] User tanpa `transporter.approve-transaction` → ditolak
- [ ] Reject tanpa alasan → ditolak; setelah reject status kembali `Draft`
- [ ] Approval massal 10 baris → 10 entri activity log terpisah
- [ ] Unapprove atas order yang sudah `Provisioned` → ditolak
- [ ] **Checkpoint review dengan Indra sebelum lanjut**

---

### [TODO] T033 — Soft delete + activity log
**Refer:** PRD F-02.8, F-02.9 · T-14
**Blocker:** T032

Soft delete dengan kolom eksplisit dan **alasan wajib**. Baris terhapus tetap terlihat
lewat filter khusus, tidak dipindah ke tabel arsip.

**Acceptance:**
- [ ] Hapus tanpa alasan ditolak
- [ ] Baris terhapus tidak muncul di daftar biasa, muncul di filter "Dibatalkan"
- [ ] Setiap perubahan nilai terekam `LogsActivityWithDescription` dengan deskripsi terbaca

---

### [TODO] T034 — Staging tarik surat jalan
**Refer:** PRD F-03.1, F-03.2, F-03.9, F-03.10
**Blocker:** T027

Halaman dashboard harian: daftar surat jalan tanggal tertentu dari `OT_INVOICE_HEAD`
beserta status (sudah/belum jadi transaksi). Tombol tarik ke `transp_dn_stage`.
Jenis yang ditarik dari `config('transporter.delivery_note.pullable_types')` —
`LDN`, `JWDN`, `PDN`. **`EDN` dan `WDN` tidak ditarik.**

**Acceptance:**
- [ ] Jenis yang ditarik datang dari config, tidak ada literal di kode
- [ ] Tarik dua kali surat jalan yang sama tidak menduplikasi (unique `TST_DN_SYS_ID`)
- [ ] `EDN`/`WDN` tidak pernah muncul, dibuktikan feature test

---

### [TODO] T035 — Generate TPDN sebagai job
**Refer:** PRD F-03.3, F-03.4, F-03.6, F-03.8 · logika `pkg_transporter.insert_data`
**Blocker:** T034, T030

`GenerateTransportOrders` mengelompokkan baris staging per
**(pengangkut, jenis truk, kapasitas, nopol, sopir, tujuan)** → satu order + detail DN +
detail biaya per kelompok. Queued job dengan progress & notifikasi.
Hasil masuk status **`Submitted`**, bukan `Approved` (F-03.8).
Sumber kebenaran pengelompokan: `legacy-source/plsql/PKG_TRANSPORTER.pkb`, prosedur
`insert_data` — **baca sebelum menulis**, jangan menebak dari PRD.

**Acceptance:**
- [ ] Pengelompokan diuji dengan fixture yang mereproduksi satu hari data nyata
- [ ] Baris staging ber-`TST_ORDER_ID` tidak diproses ulang
- [ ] Semua hasil berstatus `Submitted`
- [ ] Job gagal di tengah → rollback penuh, tidak ada order setengah jadi

---

### [TODO] T036 — Command terjadwal tarik harian
**Refer:** PRD F-03.5 · `design.md` §7
**Blocker:** T034

`transporter:pull-delivery-notes`, log channel `transporter_dn_pull`, mass insert
(bukan loop), ringkasan tiap eksekusi.

**Acceptance:**
- [ ] Terdaftar di scheduler modul
- [ ] Idempoten: jalan dua kali berturut-turut tidak menduplikasi
- [ ] Ringkasan (dicek / ditarik / dilewati) tercatat di log channel

---

### [TODO] T037 — Halaman "GRN Chip Siap Digenerate"
**Refer:** PRD §5.8, F-09.1–F-09.6, F-09.12–F-09.14 · §5.8.1
**Blocker:** T019, T027

Daftar GRN `CHPGRN` ref `JPO` yang sudah approve dan belum punya transaksi, **read-only
dari `OT_GR_HEAD`**. Tidak ada job otomatis (F-09.2), tidak ada tulis balik ke Orion (F-09.3).
Koreksi di aplikasi baru sebelum generate: jenis truk, kapasitas, nopol, sopir, tujuan,
berat kotor — nilai asli GRN disimpan berdampingan (F-09.4).
Validasi sebagai **peringatan di layar sebelum generate** (F-09.5), ambang dari config
dan pesannya mengutip nilai yang berlaku (F-09.6, memperbaiki T-21).
Berat melebihi kapasitas truk = **peringatan, bukan penolakan** (F-09.13) — 96,5% baris
historis seperti itu.

**Acceptance:**
- [ ] Tidak ada satu pun `INSERT`/`UPDATE` ke schema `MGTDAT` di jalur ini
- [ ] Kelima validasi F-09.5 muncul sebagai peringatan sebelum generate
- [ ] Pesan ambang mengutip `config('transporter.chip.max_gross_weight_kg')`
- [ ] Berat > kapasitas truk → peringatan, tombol generate tetap aktif
- [ ] Berat > ambang → tombol generate mati untuk baris itu

---

### [TODO] T038 — Generate TPCHP + jejak percobaan
**Refer:** PRD F-09.7–F-09.11 · `spec.md` §6.2
**Blocker:** T037

`GenerateChipOrders`, per baris maupun massal, idempoten. Biaya dari
`ChipRateCalculator` (`gross_weight × rate`, titik). Hasil berstatus **`Submitted`**.
Setiap percobaan tercatat di `transp_grn_pull_attempt`: pelaku, waktu, payload asli,
payload setelah koreksi, hasil.

**Acceptance:**
- [ ] GRN yang sudah punya order tidak diproses dua kali
- [ ] Percobaan gagal tetap tercatat dengan alasannya — inilah yang dulu hilang
- [ ] `ChipRateCalculator` yang dipakai, bukan `YarnRateCalculator`
- [ ] Daftar GRN lama yang belum digenerate beserta umurnya tersedia (F-09.10)

---

### [TODO] T039 — Additional expense + approval
**Refer:** PRD §5.4, F-04.1–F-04.12
**Blocker:** T032, T021

CRUD `transp_additional_expense` + `_line`. Wajib menempel ke satu transaksi angkutan
(F-04.3). Vendor, tanggal, tujuan **diturunkan dari induk**, tidak diketik ulang.
Approval memakai `EnforcesMakerChecker` yang sama dengan T032, permission
`transporter.approve-additional-expense`, penyetuju user lain **di tim yang sama**.
F-04.10: bila induk belum diprovisi saat disetujui, nilainya ikut ke provisi.
F-04.11: bila induk sudah diprovisi, **tidak ada provisi susulan** — ditarik saat menagih.

**Acceptance:**
- [ ] Additional expense tanpa `TAE_ORDER_ID` ditolak di service, bukan hanya di UI
- [ ] Pembuat tidak bisa menyetujui miliknya sendiri
- [ ] Lampiran bukti tersimpan dan bisa dibuka
- [ ] Feature test F-04.10 dan F-04.11 sebagai dua kasus terpisah

---

### [TODO] T040 — Halaman monitoring additional expense
**Refer:** PRD F-04.13, F-04.14, F-04.16
**Blocker:** T039

Tiga tampilan: *Menunggu persetujuan* (dikelompokkan per tim, dengan umur),
*Disetujui tapi belum tertagih* (dengan umur sejak disetujui — ini yang dilihat Finance
saat menyusun tagihan), dan *Rekap per vendor per bulan: dicatat vs tertagih*.

**Acceptance:**
- [ ] Umur dihitung benar dan terlihat
- [ ] Daftar "belum tertagih" hanya berisi status `Approved`
- [ ] Ketiganya terpaginasi

---

### [TODO] T041 — Notifikasi
**Refer:** PRD F-04.12, F-09.10 · `app/Notifications/BaseNotification.php`
**Blocker:** T032, T039

Notifikasi ke approver saat ada yang menunggu; ke pembuat saat disetujui/ditolak;
berkala ke tim stores bila GRN chip menumpuk.

**Acceptance:**
- [ ] Notifikasi masuk channel `database` + `broadcast`, queue `high`
- [ ] Tautan di notifikasi membuka halaman yang benar
- [ ] Tidak ada notifikasi yang terkirim ke pembuatnya sendiri untuk aksi yang ia lakukan

---

# P3 — Provisi (T042–T047) · 2,5 minggu

> Mulai dari sini kedalaman task menurun. **Regenerate P3–P7 dengan kode P0–P2 di
> tangan sebelum mengerjakannya.**

### [TODO] T042 — Halaman provisi
**Refer:** PRD F-05.1, F-05.2, F-05.3, F-06.9 · `spec.md` §6.3, §6.4
**Blocker:** T032, T023

Filter rentang tanggal + jenis; tampilkan transaksi `Approved` yang belum diprovisi.
Buat baris provisi: nilai = biaya angkutan + additional expense yang terbawa,
gross-up bila `TCA_IS_GROSS_UP` (default mati). Due date lewat `DueDateHelper`.
**Pengangkut `BUYER_BORNE` dan `INTERNAL` tidak pernah muncul di sini** (F-01.17) —
ditegakkan di `ProvisionService::eligibleOrders()`, bukan disaring di UI.
Satu order = satu provisi (F-06.9), ditegakkan unique `TRP_ORDER_ID`.

**Acceptance:**
- [ ] `ProvisionEligibilityTest`: order `BUYER_BORNE`/`INTERNAL` tidak pernah lolos
- [ ] `OneParentOneProvisionTest`: provisi kedua atas order yang sama ditolak
- [ ] Due date benar untuk tanggal 15 dan 16 (batas aturan)

---

### [TODO] T043 — Preview jurnal `TPJV`
**Refer:** PRD F-05.4 · `design.md` §6 · `spec.md` §6.5, §2.1 (akun)
**Blocker:** T042, T012

`ProvisionJournalBuilder` menghasilkan `JournalPreviewData`: baris Dr/Cr, akun (dari
`transp_posting_account`, berlaku pada tanggal dokumen), nilai IDR & USD, dan **kurs
yang dipakai beserta tanggal pengambilannya**. Preview tidak menulis apa pun.

**Acceptance:**
- [ ] `ProvisionJournalBuilderTest` menutup `DESPATCH` (404001/208027) dan `CHIP` (401001/208026)
- [ ] Kurs diambil dari akhir bulan periode JV, dibalik sesuai `spec.md` §6.5
- [ ] Tidak ada nomor akun literal di kode
- [ ] **Shadow-run: bandingkan preview dengan `FT_*` untuk 3 periode historis (RK-01).
      Checkpoint review dengan Indra — ini gerbang go/no-go sebenarnya**

---

### [TODO] T044 — Posting `TPJV` sebagai job
**Refer:** PRD F-05.5, F-05.6, F-05.8, F-05.9, F-05.10, F-05.11 · `design.md` §6
**Blocker:** T043

`PostProvisionJournal` mengikuti tujuh langkah di `design.md` §6.
Kode transaksi `TPJV` dari config (F-05.8). `FT_TXN_AUTH` **tidak ditulis** (F-05.9).
`th_cr_uid` dari `HMEMD_USER_ORION`; **posting ditolak dengan pesan yang menyebut nama
user dan langkah perbaikannya bila pemetaan kosong** (F-05.10) — jangan pernah menulis
string kosong ke GL.

**Acceptance:**
- [ ] Posting tanpa `HMEMD_USER_ORION` ditolak, pesannya menyebut nama user
- [ ] Job gagal di tengah → rollback penuh, tidak ada baris `FT_UNPOSTED_*` tersisa
- [ ] Tidak ada satu pun `INSERT` ke `FT_TXN_AUTH`
- [ ] Uji di staging: posting satu `TPJV`, pastikan bisa diotorisasi user lain di Orion
      (`ODBTRG_APPR_VOUCHER`, error 2441465 — F-05.11)

---

### [TODO] T045 — `transp_posting_log` + idempotensi
**Refer:** PRD F-05.6 · `design.md` §6 · `spec.md` §2.5
**Blocker:** T044

Kunci idempotensi ada di `TPL_IDEMPOTENCY_KEY`, bukan `th_flex_10`.
`th_flex_10` tetap diisi untuk kompatibilitas laporan.

**Acceptance:**
- [ ] Posting batch yang sama dua kali → yang kedua ditolak sebelum menyentuh Oracle
- [ ] Baris `Failed` menyimpan pesan error yang bisa dibaca operator, bukan stack trace
- [ ] Payload tersimpan utuh, cukup untuk mereproduksi posting

---

### [TODO] T046 — Halaman riwayat posting
**Refer:** `spec.md` §2.5
**Blocker:** T045

Daftar `transp_posting_log` dengan filter jenis, status, tanggal, pemosting.
Baris `Failed` bisa dicoba ulang setelah penyebabnya diperbaiki.

**Acceptance:** terpaginasi · retry hanya untuk status `Failed` · retry menghasilkan baris log baru, tidak menimpa

---

### [TODO] T047 — Test integrasi posting
**Refer:** `spec.md` §10 · `phpunit.xml` testsuite `Integration`
**Blocker:** T045

`tests/Integration/Transporter/PostingIntegrationTest.php` yang **skip sendiri** bila
variabel koneksi Oracle tidak diset — supaya CI tetap hijau.

**Acceptance:** skip bersih di CI · jalan dan hijau di staging dengan variabel diset

---

# P4 — Tagihan (T048–T058) · 4 minggu

### [TODO] T048 — Entri tagihan + profil pajak
**Refer:** PRD F-06.1, F-06.2 · `spec.md` §6.6
**Blocker:** T045

Header tagihan: vendor, invoice, tanggal, mata uang, faktur pajak, DPP, PPN%, PPh%, total.
PPN & PPh dihitung dari `IM_VS_STATIC_VALUE` profil vendor, termasuk **validasi masa
berlaku `PPH 0.5`** (`VSSV_FIELD_03`).

**Acceptance:**
- [ ] Profil pajak kedaluwarsa → peringatan, bukan diam-diam dipakai
- [ ] Nomor tagihan `TBILL-{YYYY}{6}` dari sequence
- [ ] Vendor `BUYER_BORNE`/`INTERNAL` tidak bisa dipilih

---

### [TODO] T049 — Matching provisi
**Refer:** PRD F-06.3, F-06.8
**Blocker:** T048

Pilih baris provisi ber-JV milik vendor tersebut → baris tagihan `PROVISION`.
Tampilkan selisih. Pembatalan matching mengembalikan provisi ke status sebelumnya —
**tidak dipindah ke tabel arsip**, cukup tercatat di activity log.

**Acceptance:**
- [ ] Provisi yang sudah ber-TJV tidak bisa dilepas
- [ ] Pelepasan tercatat di activity log dengan nilai sebelum/sesudah

---

### [TODO] T050 — Baris `EXPENSE_DIRECT` + selisih
**Refer:** PRD §5.6.1, F-06.7, F-06.7a, F-06.9–F-06.11
**Blocker:** T049

Baris `EXPENSE_DIRECT` untuk selisih dan tagihan tanpa provisi. Wajib akun beban,
`TBL_DIFFERENCE_TYPE`, dan alasan. `CANCELLATION` dan `DEDUCTION` bernilai **negatif**
supaya jumlah baris selalu sama dengan nilai tagihan vendor.

**Acceptance:**
- [ ] `EXPENSE_DIRECT` tanpa akun/jenis/alasan ditolak
- [ ] `CANCELLATION`/`DEDUCTION` bernilai positif ditolak
- [ ] Feature test: satu tagihan berisi campuran `PROVISION` + `EXPENSE_DIRECT`

---

### [TODO] T051 — Tarik additional expense ke tagihan
**Refer:** PRD F-04.6–F-04.9, F-06.7b
**Blocker:** T050, T039

Saat menyusun baris `EXPENSE_DIRECT`, tawarkan additional expense berstatus `Approved`
milik vendor tersebut. **Menarik bersifat pilihan** — yang tidak ditarik dibiarkan
apa adanya, tidak perlu dibatalkan. Selisih nominal catatan vs tagihan ditampilkan.

**Acceptance:**
- [ ] Hanya `Approved` yang ditawarkan
- [ ] Ditarik → status `Billed` + terkunci; dilepas → kembali `Approved`
- [ ] Selisih terlihat sebelum disimpan

---

### [TODO] T052 — Validasi total tagihan
**Refer:** PRD F-06.7c
**Blocker:** T051

Total header harus sama dengan jumlah barisnya sebelum TJV boleh dibuat.

**Acceptance:** selisih ≠ 0 → tombol Generate TJV mati, selisihnya ditampilkan

---

### [TODO] T053 — Preview + posting `TJV`
**Refer:** PRD F-06.4, F-06.5, F-06.6 · `spec.md` §6.6
**Blocker:** T052, T054

`BillJournalBuilder` + `PostBillJournal`. Jurnal: AP `203001` (sub-account = kode
supplier), PPh `206005`, PPN masukan `108004`/`108005`, reversal provisi.
**Baris PPN tidak dibuat bila nomor FP diawali `08`.**
Gerbang dokumen (F-06.4) menahan posting; yang ditampilkan adalah **daftar surat jalan
yang menahan**, bukan pesan "Document Belum Lengkap".

**Acceptance:**
- [ ] `BillJournalBuilderTest` menutup cabang FP `05`, FP `08`, dan lainnya
- [ ] Posting terblokir menampilkan daftar LDN beserta nomornya
- [ ] Idempoten lewat `transp_posting_log`
- [ ] Shadow-run 3 periode historis vs `FT_*`

---

### [TODO] T054 — Kontrol dokumen surat jalan
**Refer:** PRD F-10.1, F-10.2, F-10.8, F-10.9, F-10.10 · `spec.md` §2.5
**Blocker:** T048, T024

`transp_document_scan` + `DocumentGateService`. Status `Pending`/`Scanned`/`Waived`,
setiap perubahan mencatat waktu, pelaku, sumber (`JOB`/`MANUAL`), dan berkasnya.
Waive boleh oleh **pembuat tagihan itu sendiri** tanpa persetujuan berjenjang, tapi
**wajib alasan** + activity log (F-10.8).
Halaman monitoring: surat jalan yang menahan tagihan, dikelompokkan per transporter
dan per bulan, dengan umur hold.

**Acceptance:**
- [ ] `DocumentGateTest`: tagihan dengan satu DN `Pending` terblokir, daftarnya benar
- [ ] Waive tanpa alasan ditolak
- [ ] Tautan ke PDF hasil scan bisa dibuka dari halaman tagihan (F-10.10)
- [ ] `CHPGRN` tidak pernah punya baris `transp_document_scan` (F-10.6)

---

### [TODO] T055 — Job scan dokumen
**Refer:** PRD F-10.3, F-10.3a, F-10.3b, F-10.3c, F-10.4 · `design.md` §7, §9
**Blocker:** T054

`transporter:scan-delivery-documents`. Mencocokkan berkas di `Doc_Folder` (network share
read-only, path dari config) dengan surat jalan berstatus `Pending`, pola
`{TYPE}-{nomor}.pdf` di `{root}/{TYPE}/{tahun}/`.

**Aturan yang tidak boleh dilanggar (F-10.3b):** mount tidak tersedia → **gagal bersih
tanpa mengubah status apa pun**, catat ke log channel, kirim notifikasi. Jangan pernah
menandai berkas sebagai hilang karena mount sedang down — itu menahan tagihan secara keliru.

**Acceptance:**
- [ ] Feature test dengan mount tidak tersedia: nol baris berubah status
- [ ] Ringkasan tiap eksekusi (dicek / ketemu / masih pending) tercatat
- [ ] Idempoten; hanya baris `Pending` yang diperiksa
- [ ] Mode *shadow* (F-10.12) bisa dinyalakan lewat config

---

### [TODO] T056 — Laporan selisih provisi vs tagihan
**Refer:** PRD F-06.12
**Blocker:** T053

Per vendor per periode, dipecah per jenis selisih.
**Acceptance:** angka cocok dengan jumlah `transp_bill_line` · terpaginasi · bisa diekspor

---

### [TODO] T057 — Halaman monitoring hold dokumen
**Refer:** PRD F-10.9
**Blocker:** T054

Sudah sebagian di T054; task ini menyelesaikan tampilan per transporter & per bulan
dengan umur hold.
**Acceptance:** backlog Agustus 2026 (712 LDN / 189 TPDN) tampil benar di staging

---

### [TODO] T058 — e2e alur tagihan
**Refer:** `e2e/CLAUDE.md`
**Blocker:** T053

Descriptor + spec + doc untuk alur tagihan sampai TJV. **Baca `e2e/CLAUDE.md` seluruhnya
dulu** — aturannya ketat dan sebagian tidak bisa ditawar.
**Acceptance:** `npm run e2e:check` hijau · spec `@write` punya `marker()` dan cleanup

---

# P5 — Report (T059–T064) · 1,5 minggu

Semua report: queued job + `ReportStatusNotification` + tautan unduh. Filter periode
**tidak boleh dibatasi N bulan terakhir** dan tidak boleh ada batas tanggal hardcode
(NF-10, T-11) — data terentang 2014 sampai sekarang.

### [TODO] T059 — Transporter Register (F-08.1)
Menggantikan TRNSP001 + TRNSP003. Excel + PDF. Rujuk susunan kolom di
`legacy-source/reports/TRNSP001.sql` dan `TRNSP003.sql` (blok `RPT2XLS.put_cell` —
itu susunan yang benar-benar diterima user).

### [TODO] T060 — Delivery Note Transporter Value (F-08.2)
Menggantikan TRNSP002 — report paling kompleks. Nilai angkutan per surat jalan +
status provisi + status bill + outstanding AP. Rujuk `legacy-source/reports/TRNSP002.sql`.

### [TODO] T061 — Freight Cost per Kg (F-08.3)
Menggantikan TRNSP004. Total Cost Amount & Total Cost per Kg.

### [TODO] T062 — Transporter Provision & Bill, parametrik (F-08.4)
Menggantikan TRNSP005, 006, 007, 008 sekaligus — keempatnya nyaris identik.
Satu report dengan parameter `Provision` / `Bill` / `Not Yet Provision`.

### [TODO] T063 — Transporter Budget / Outstanding AP (F-08.5)
**Mengacu ke logika `TRANSPORTER_BUDGET_V`, bukan ke TRNSP009.** Dua bagian
`UNION ALL` (provisi belum ter-bill + tagihan belum lunas), sub total per supplier,
grand total. **Wajib punya filter** (tanggal, supplier, tipe, status) — sistem lama
tidak punya sama sekali.
Dua nomor tagihan yang dulu dikecualikan hardcode (`TBILL-2022000696`,
`TBILL-2022000718`, T-12) **dipindah ke data**, bukan ke kode.

### [TODO] T064 — e2e report
Descriptor + spec untuk lima halaman parameter report.

**Acceptance untuk T059–T064:**
- [ ] Setiap report jalan sebagai job, notifikasi berisi tautan
- [ ] Filter periode menjangkau 2014–sekarang
- [ ] Tidak ada batas tanggal hardcode di query mana pun
- [ ] Angka dibandingkan dengan report lama untuk 3 periode berbeda, selisih 0 (R-08)

---

# P6 — Migrasi (T065–T076) · 3,5 minggu

> **Seluruh fase ini dijalankan di staging.** Produksi hanya di P7, dengan sign-off.

### [TODO] T065 — Kerangka script migrasi
**Refer:** PRD §6.1
Command artisan `transporter:migrate-legacy {--phase=} {--dry-run}`, idempoten dan
re-runnable, setiap baris yang diperbaiki/dibuang masuk `transp_migration_exception`.
**Acceptance:** dry-run tidak menulis apa pun · dijalankan dua kali tidak menduplikasi

### [TODO] T066 — M-0 snapshot
Snapshot 14 tabel ke schema arsip + export CSV. **Acceptance:** checksum baris & jumlah cocok

### [TODO] T067 — M-1 master: 739 baris → ± 50 carrier + ± 716 rate card
**Refer:** C-19, C-20, C-21, C-22, C-09
Yang paling berisiko di seluruh fase migrasi (RK-15).
**Acceptance:**
- [ ] 12 baris tanpa `MTM_TRANSP_CODE` masuk exception (C-20)
- [ ] 59 baris nama melenceng dilaporkan, namanya **tidak** ikut dimigrasi (C-21)
- [ ] 90 baris `STO` jadi rate card `CHIP`; 3 baris tanpa `MTM_CUST_SUPP` masuk exception (C-22)
- [ ] 23 kombinasi ganda diselesaikan manual (C-09) — **wajib sign-off Head of Despatch
      & Head of Finance sebelum lanjut** (Q-18)
- [ ] **`gap.md` C-18:** `MGT_TRANSP_RATE` hanya menyimpan tarif hari ini — 68% transaksi
      historis memakai tarif yang sudah berubah. Rate card hasil migrasi **tidak** boleh
      diberi satu `TRC_VALID_FROM` seragam seolah tarif sekarang berlaku sejak awal.
      Kartu migrasi berlaku mulai tanggal cutover; order lama tidak di-resolve ulang ke
      kartu (lihat T068)
- [ ] **D-20 — alias pengangkut GRN chip:** isi `transp_carrier_alias` dari pasangan
      (`MTM_TRANSP_NAME`, `MTM_TRANSP_CODE`) `MTM_TYPE = 'STO'`, dinormalisasi
      (`TranspCarrierAlias::normalize()`), `TCL_SOURCE = 'MIGRATION'`. Nama yang menunjuk lebih
      dari satu kode → exception. Target: ≥ 83% GRN chip sejak 2024 terpetakan (1.529 / 1.840,
      diukur 2026-09-24); sisanya tampil di Monitoring GRN Chip (T025)
- [ ] **Checkpoint review dengan Indra**

### [TODO] T068 — M-2 transaksi
**Refer:** C-01, C-02, C-03, C-06, C-08, C-14, C-17, C-18
**Acceptance:** 126 + 37 + 2 baris yatim masuk exception · 328 baris `TP_NO` non-standar
jadi `TPSVC` · semua baris `TRO_IS_LEGACY = 1` · total biaya per bulan cocok
· **`MTDC_TOTAL_RATE` disalin apa adanya ke `transp_order_cost`, tidak dihitung ulang
dengan kalkulator** — pecahan pra-Agustus 2024 dan 10 baris `DES5`/`DESH1` ikut (D-12)
· **Rate card migrasi yang tidak punya baris prioritas 1 bertipe benar (F-01.8, T018)** masuk
exception atau dibetulkan saat migrasi — setelah kartu dipakai order, barisnya dibekukan dan
kartu itu tidak bisa di-Naikkan tarif
· **Additional expense lama (C-23, 862 baris `MGT_TRANSP_DETAIL_OTHCHG`)** dipetakan ke jenis biaya
lewat `TCT_LEGACY_ID` (D-16): `BIAYA TOL`→`TOL`, `JLNDITUTUP`→`JALAN_DITUTUP`, `INAP`, `KAWAL`,
`ALAMAT` (55 baris, jenis baru), `NULL` "Ambil Barang"→`AMBIL_BARANG` (C-11)
· **328 `TPSVC` (C-06)** dipetakan ke kategori jasa lewat `TSC_LEGACY_ID` (D-17): `PALLET` 132,
`AMBIL BARANG` 24, `RETUR BENANG` 22, `OTHERS` 150 (kategori baru). Belum ada task
eksplisit untuk C-23 — dicatat di sini sampai dipecah

### [TODO] T069 — M-3 provisi
**Refer:** C-04, C-05
**Acceptance:** 331 baris yatim masuk dengan `TRP_IS_ORPHAN = 1` dan `TRP_ORDER_ID` NULL ·
`SUM(tp_amt + tp_oth_amt)` per bulan per `txn_code` selisih 0 (R-02)

### [TODO] T070 — M-4 tagihan
**Refer:** `spec.md` §9 · `DECISIONS.md` D-03
42 baris `_ADD` dan 5 baris `_BILL_ADD` jadi baris `EXPENSE_DIRECT`, **bukan** provisi.
**Acceptance:** `SUM(tpb_total)`, `SUM(ppn)`, `SUM(pph)` per vendor per bulan selisih 0 (R-03)

### [TODO] T071 — M-5 staging + M-6 sinkronisasi sequence
**Acceptance:** insert uji setelah migrasi tidak bentrok dengan nomor ter-migrasi

### [TODO] T072 — M-6b pemetaan `HMEMD_USER_ORION`
**Refer:** PRD §8.1, RK-17
Lengkapi untuk seluruh user Finance yang akan memposting, satu Orion id per individu
(bukan akun bersama — Orion menolak dokumen yang pembuat & penyetujunya sama).
Bersihkan nilai `2462` yang tidak terdaftar di `MENU_USER`.
**Acceptance:** setiap pemosting punya id yang sah · uji posting satu `TPJV` per user di staging ·
**checkpoint review dengan Indra**

### [TODO] T073 — Migrasi status dokumen
**Refer:** C-13, C-15, C-16
**Acceptance:** `NULL` pra-2022 → `Waived` beralasan · 399 baris `CHPGRN` `'N'` **tidak**
dibuatkan baris sama sekali · 14 baris `JWDN` dibuatkan `Pending` lalu job scan dijalankan sekali

### [TODO] T074 — M-7 compatibility view di `MGTDAT`
**Refer:** PRD §4.5, CL-1..CL-6, RK-11, RK-12
**Aksi paling berisiko di seluruh proyek** — ± 25 objek + 12 report Orion bergantung padanya.
Rename tabel lama jadi `Z_*_ARCH`, buat view bernama sama dengan bentuk kolom identik,
`WITH READ ONLY`, grant ke seluruh grantee sebelumnya.
**Ini satu-satunya migration modul ini yang memakai `oracle_mgtdat`** — dan yang dibuat
adalah *view*, bukan tabel (`spec.md` §0).
**Acceptance:**
- [ ] Tidak ada objek `INVALID` setelah recompile
- [ ] Uji regresi CL-5 lolos: `PRC_DELVRY_MARGIN_MGT`, 10 report `SALR*`, `OPD106_MGT_1`,
      `SALATTREKAP`, `FV_TRANS_DETAILS_PPH23_V`, `VIEW_SUPP_OUTSTANDING`, `UPDATE_FSFC_VCH`
- [ ] `UPDATE_FSFC_VCH` ditambahi `TPJV` di daftar `th_tran_code` (RK-18)
- [ ] Performa diukur; materialized view disiapkan sebagai cadangan bila terlalu lambat (RK-12)

### [TODO] T075 — M-8 shadow-run job scan
**Refer:** F-10.12, RK-13
Jalankan berdampingan `EFILL_009` selama 1 minggu.
**Acceptance:** selisih 0 selama 5 hari kerja berturut-turut (R-11)

### [TODO] T076 — M-9 rekonsiliasi R-01..R-12
**Refer:** PRD §6.5
Command `transporter:reconcile` yang menjalankan kedua belas uji dan menghasilkan laporan.
**Acceptance:**
- [ ] Kedua belas uji punya implementasi, tidak ada yang dilewati
- [ ] R-12 (hitung ulang 500 transaksi vs `MTDC_TOTAL_RATE`) selisih 0 — **go/no-go**.
      Tarif diambil dari baris `MTDC_RATE` transaksinya sendiri, **bukan** dari master
      tarif atau rate card hasil migrasi — master tidak berversi tanggal (`gap.md` C-18,
      D-13). Yang diuji algoritmanya; pola yang sama dengan `YarnRateCalculatorTest`
- [ ] Laporan bisa diserahkan ke Finance untuk sign-off

---

# P7 — UAT & Cutover (T077–T082) · 2 minggu

> **Serial. Tidak boleh diparalelkan.** Lihat PRD §6.6.

### [TODO] T077 — UAT Finance
Skenario UAT untuk seluruh alur: master → transaksi → approval → provisi → JV → tagihan
→ TJV → kontrol dokumen → report. **Acceptance:** sign-off tertulis Finance & Despatch

### [TODO] T078 — H-14 dry-run migrasi produksi (baca saja)
**Acceptance:** rekonsiliasi R-01..R-12 diulang atas data produksi, hasilnya lolos

### [TODO] T079 — H-7 freeze + H-1 tutup periode JV
**Acceptance:** tidak ada perubahan Forms · periode JV bulan berjalan ditutup di sistem lama

### [TODO] T080 — H cutover
Jendela ± 4 jam di luar jam kerja: migrasi produksi → pasang compatibility view +
recompile → matikan `EFILL_009`, aktifkan job scan baru → verifikasi R-01..R-11 →
cabut hak tulis user pada tabel `MGTDAT` transporter.
**Acceptance:** seluruh langkah terverifikasi sebelum go-live diumumkan

### [TODO] T081 — Rollback plan
**Refer:** PRD §6.6
Script balikan disiapkan **bersama script maju**, bukan belakangan. Snapshot M-0
dipertahankan 30 hari.
**Acceptance:** script balikan diuji di staging, bukan hanya ditulis

### [TODO] T082 — H+1..H+30 hypercare
Monitoring paralel, sistem lama tetap terbaca untuk report historis.
H+90: arsipkan objek lama dengan prefix `Z_`.
**Acceptance:** tidak ada temuan terbuka saat H+30
