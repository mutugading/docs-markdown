# gap.md — Transporter v1: PRD vs Codebase

**Dibuat:** 2026-09-17 · **PRD:** v2.2 Draft (2026-09-15) · **Branch acuan:** `local/e2e` @ `14b685b2`

Hasil pembacaan PRD lengkap dibandingkan dengan **kondisi codebase yang benar-benar
ada hari ini**, bukan dengan apa yang tertulis di `CLAUDE.md`. Beberapa di antaranya
berbeda — itu justru bagian yang paling perlu diketahui.

## Ringkasan

| Kategori | Jumlah |
|---|---:|
| **REUSE** — sudah ada, dipakai apa adanya | 14 |
| **CONFLICT** — PRD dan codebase/PRD-sendiri bertentangan | 10 |
| **MISSING** — belum ada sama sekali | 21 kelompok |

> ⚠️ **Penomoran bertabrakan.** Kode `C-NN` di berkas ini **bukan** kode `C-NN` di `spec.md`,
> `TASKS.md` dan komentar migration. Yang di sana adalah **aturan cleansing migrasi PRD**
> `C-01`…`C-23` (nilai `TME_RULE_CODE`); yang di sini adalah catatan gap. Untuk `C-10` ke
> atas keduanya tertulis sama persis — `C-17` PRD berarti "`TRO_APPROVED_BY = 'MIGRATION'`",
> `C-17` berkas ini berarti hal lain sama sekali. Selalu tulis **`gap.md C-NN`** saat merujuk
> catatan di sini; `C-NN` polos berarti aturan PRD.

Yang paling penting dibaca sebelum menulis baris pertama: **C-1** (PRD bertentangan
dengan dirinya sendiri soal `FT_TXN_AUTH`), **C-4** (dua tabel diberi prefix yang sama),
dan **C-6** (test tidak diletakkan di tempat yang disebut `CLAUDE.md`).

---

## REUSE — sudah ada, jangan dibangun ulang

| # | Yang dipakai | Lokasi | Catatan |
|---|---|---|---|
| R-01 | Koneksi `oracle_mgtdat` | `config/database.php:35` | Sudah terdaftar. PRD menganggapnya perlu disiapkan — tidak perlu |
| R-02 | `JournalVoucherPostingService` | `Modules/LcControl/app/Services/Erp/JournalVoucherPostingService.php` | Validasi balance DR=CR di nilai USD, resolusi `menu_user.user_field_01` → tran/dept code, konversi kurs. **Diangkat ke Core di T012** |
| R-03 | `EloquentJournalVoucherRepository` | `Modules/LcControl/app/Repositories/Erp/EloquentJournalVoucherRepository.php` | Resolusi `fm_acnt_period`, nomor dari `fm_tran_doc_no` dengan `lockForUpdate()`, insert header + detail, majukan `tdoc_cur_no`. Konstanta `COMP_CODE=002`, `DIVN_CODE=001`, `HEAD_NO_1=1`, `HEAD_NO_2=2` — **persis yang dipakai `PKG_TRANSPORTER`** |
| R-04 | Dukungan flex field GL | `Modules/LcControl/app/Data/Erp/JournalVoucherData.php` + `JournalVoucherLineData.php` | `flex_01..flex_20` sudah dipetakan 1:1 ke `th_flex_*` dan `td_flex_*` lewat `columnMap()`. **PRD §4.4 butir 2 menyebut ini sebagai pekerjaan yang harus ditambahkan — ternyata sudah ada.** `td_flex_15..20` dan `th_flex_10` tinggal diisi |
| R-05 | `ExchangeRateService::getExchangeRate($date, $from, $to, $type)` | `Modules/LcControl/app/Services/ExchangeRateService.php:20` | Baca `fm_exchange_rate`. Untuk menyamai `curs_usd_b` perlu dibalik — lihat `spec.md` §Kurs |
| R-06 | `SysIdHelper::generate()` / `generateBatch()` | `app/Helpers/SysIdHelper.php` | Baca `HM_MST_SEQUENCES`, `lockForUpdate()`, format `{prefix}{tanggal}{nomor}`. Menggantikan `MAX()+1` sistem lama |
| R-07 | `Searchable` trait | `app/Traits/Searchable.php` | `scopeSearch()` multi-kolom, sadar relasi |
| R-08 | `LogsActivityWithDescription` trait | `app/Traits/LogsActivityWithDescription.php` | Audit trail — memperbaiki T-14 |
| R-09 | `AppliesTransactionAuthorization` trait | `app/Traits/AppliesTransactionAuthorization.php` | Filter transaksi per departemen |
| R-10 | `FormatsNikForExport` trait | `app/Traits/FormatsNikForExport.php` | Wajib untuk kolom NIK di export |
| R-11 | `WorkflowPermissionService::ACTION_HIERARCHY` | `Modules/Core/app/Services/WorkflowPermissionService.php` | Tangga `draft/confirm/submit/approve/release` + pemetaan kolom audit `{prefix}_{suffix}_uid`/`_dt`. **Tidak punya aturan maker ≠ approver** — itu ditambahkan di service Transporter (T032) |
| R-12 | `BaseNotification` + `ReportStatusNotification` | `app/Notifications/` | Channel `database` + `broadcast`, queue `high` |
| R-13 | Pola migration ber-`DISABLE_MIGRATIONS` | `Modules/LcControl/database/migrations/2026_05_16_100100_create_lc_posting_account_table.php` | Template yang disalin untuk 22 tabel baru. ⚠️ **Koreksi (T010):** catatan asli menyuruh menulis nama kolom UPPERCASE mengikuti LcControl. **Jangan** — itu membuat setiap pembacaan atribut model bernilai null di SQLite/CI. Tulis lowercase; DDL Oracle-nya identik. Lihat `DECISIONS.md` D-08. Nama tabel tetap lowercase, nama constraint tetap UPPERCASE |
| R-14 | Harness e2e | `e2e/` + `e2e/CLAUDE.md` | `npm run e2e:check` sudah jalan di CI (`.github/workflows/lint.yml:49`) |

---

## CONFLICT — harus diselesaikan sebelum kodenya ditulis

### C-1 — PRD bertentangan dengan dirinya sendiri soal `FT_TXN_AUTH` ⚠️

| Di mana | Bunyinya |
|---|---|
| §4.4 butir 1 | *"Yang perlu ditambahkan … Insert baris `FT_TXN_AUTH` … **Dikonfirmasi wajib** untuk setiap posting yang menghasilkan dokumen ERP — `TPJV` dan `TJV`"* |
| §3.7.4a, F-05.9, T-27, Q-29 | *"**Baris `FT_TXN_AUTH` tidak ditulis.** Modul hanya menulis header + detail … pembuatan baris otorisasi tetap urusan Orion"* |

§4.4 adalah sisa dari PRD v1.8; §3.7.4a ditulis ulang di v2.1 setelah penelusuran
`STP_DINSERT_APPR_RECS_NEW` dan bukti 182 dokumen `JV` 2026 yang terposting tanpa
baris itu.

> **Resolusi: ikuti F-05.9 — `FT_TXN_AUTH` TIDAK ditulis.** Keputusan yang lebih baru
> dan yang punya bukti. `EloquentJournalVoucherRepository` yang ada memang sudah tidak
> menulisnya, jadi tidak ada pekerjaan tambahan. Dicatat di `DECISIONS.md` D-01.
> §4.4 PRD perlu dikoreksi di revisi berikutnya.

### C-2 — PRD §4.4 butir 2 menyuruh menambahkan sesuatu yang sudah ada

*"Yang perlu ditambahkan … Dukungan `th_flex_10` dan `td_flex_15..20`."*
Sudah ada sejak awal lewat `columnMap()` (R-04). Sisa pekerjaan nyatanya hanya
**butir 3: pengecekan idempotensi lintas `FT_UNPOSTED_` / `FT_CUR_` / `FT_PRV_TRANS_HEADER`** —
dan di sistem baru itu dipindah ke `transp_posting_log` (§4.2), bukan ke `th_flex_10`.

> **Resolusi:** T012 hanya memindahkan service ke Core + menambah pengecekan idempotensi.
> Dukungan flex tidak perlu disentuh.

### C-3 — §6.2 memakai nama tabel versi lama

Tabel di §6.2 (*Ruang Lingkup Data*) masih menyebut `transp_master`, `transp_rate`,
`transp_other_charge`, dan `transp_provision (kind = MAIN/ADDON)`. Nama-nama itu
dibuang di PRD v1.6/v1.7 ketika master dinormalisasi dan tabel `_ADD` digabung.

> **Resolusi: §4.2 yang berlaku.** `transp_carrier` / `transp_rate_card` /
> `transp_rate_line` / `transp_additional_expense`, dan `transp_provision` **tanpa**
> kolom `kind` (§5.6.1: satu induk = satu provisi). Pemetaan yang benar ada di
> `spec.md` §Pemetaan Migrasi.

### C-4 — Dua tabel diberi prefix yang sama: `tds_` ⚠️

§4.2 memberi prefix `tds_` kepada **`transp_dn_stage`** dan **`transp_document_scan`**.
Konvensi prefix kolom unik seluruh database jadi rusak, dan dua model akan punya
kolom `tds_sys_id` yang berbeda arti.

> **Resolusi:**
> | Tabel | Prefix |
> |---|---|
> | `transp_dn_stage` | **`tst_`** (transporter stage) |
> | `transp_document_scan` | **`tdc_`** (transporter document scan) |
>
> Dicatat di `DECISIONS.md` D-02. Kalau Anda lebih suka pasangan lain, ubah di sana
> dan di `spec.md` — jangan di satu tempat saja.

### C-5 — Unique index fungsional C-08 tidak SQLite-compatible

`spec.md` memakai `CREATE UNIQUE INDEX … (CASE WHEN tro_is_legacy = 0 THEN … END)`.
Laravel Schema builder tidak bisa mengungkapkannya, dan NF-06 mewajibkan migration
lolos di CI yang memakai SQLite.

> **Resolusi:** migration T007 menjalankan raw DDL **kecuali bila drivernya
> `sqlite`**; di SQLite dilewati dengan `$table->unique(['TRO_TXN_CODE','TRO_TRANSP_NO'])`
> biasa — data legacy tidak pernah ada di CI, jadi aman.
>
> ⚠️ **Koreksi (T007).** Versi pertama resolusi ini menulis guard-nya sebagai
> `getDriverName() === 'oci8'`. **Nama driver yang sebenarnya adalah `oracle`**
> — `'oci8'` adalah nama paketnya, bukan nama drivernya. Guard yang salah itu
> akan membuat produksi diam-diam memakai cabang SQLite. Karena itu guard-nya
> dibalik: deteksi SQLite, jangan deteksi Oracle. Lihat `spec.md` §4.

### C-6 — Test tidak diletakkan di `Modules/*/tests` ⚠️

`CLAUDE.md` menyebut *"Feature tests: `tests/Feature/` dan `Modules/*/tests`"*.
Kenyataannya:

| Fakta | Bukti |
|---|---|
| Semua `Modules/*/tests` **kosong** (0 file `.php`) | 10 folder diperiksa |
| `Modules/LcControl` **tidak punya folder tests sama sekali** | — |
| `phpunit.xml` hanya memindai `tests/Unit` dan `tests/Feature` | `phpunit.xml` blok `<testsuites>` |
| **`tests/Integration` tidak terdaftar sebagai testsuite — dan foldernya belum ada** | diverifikasi T002, 2026-09-17 |

> **Resolusi:** test Transporter ditulis di **`tests/Unit/Transporter/`** dan
> **`tests/Feature/Transporter/`**. Test yang ditaruh di `Modules/Transporter/tests`
> tidak akan pernah dijalankan CI.
>
> ⚠️ **Koreksi (T002).** Versi pertama catatan ini menyebut `tests/Integration/`
> sebagai testsuite yang sudah ada. **Tidak ada.** `phpunit.xml` hanya mendaftarkan
> `Unit` dan `Feature`, dan folder `tests/Integration/` tidak ada di repo. Artinya
> `tests/Integration/Transporter/PostingIntegrationTest.php` yang diminta `spec.md`
> §10 dan T047 **tidak akan pernah jalan** kalau ditulis apa adanya.
>
> Keputusannya diambil **saat T047 tiba**, bukan sekarang — dua pilihan:
> menambahkan testsuite `Integration` ke `phpunit.xml` (berkas bersama, kena semua
> modul), atau menaruh test posting di `tests/Feature/Transporter/` dengan guard
> skip sendiri. Catat hasilnya di `DECISIONS.md`.

### C-7 — Scope commit `transporter` belum terdaftar — ✅ selesai

`.github/COMMIT_CONVENTION.md:252` mendaftar `auth | core | finance | hr | lc-control |
material-control | mis | public | ui`. Tidak ada `transporter` (juga tidak ada
`self-service` dan `report`, tapi itu bukan urusan kita).

> **Resolusi: sudah dikerjakan** bersama commit berkas konteks ini —
> `.github/COMMIT_CONVENTION.md` (tabel *Module Scopes* + baris ringkasan) dan
> `CLAUDE.md` §Conventional Commits. T001 tidak perlu mengulanginya.

### C-8 — `.docs-me/` yang dirujuk `CLAUDE.md` tidak ada di repo

`CLAUDE.md` merujuk `.docs-me/RepositoryPattern.md`,
`.docs-me/transaction-page-creation-guide.md`, dan lima berkas lain. Folder itu tidak
ada di working tree.

> **Resolusi:** jangan mencari berkas itu. Panduan pola yang setara ada sebagai
> **skill**: `.claude/skills/mutugading-crud`, `mutugading-transaction`,
> `mutugading-scaffold`, `mutugading-e2e`. **Pakai skill itu untuk scaffolding**,
> jangan menulis boilerplate dari nol.

### C-9 — `transp_payment` dibuat di v1 tapi tidak dipakai

§4.2 dan §5.11: tabelnya dibuat sekarang supaya skema tidak berubah lagi, tapi payment
voucher rilis terpisah.

> **Resolusi:** migration dibuat (T006), **model dan service tidak dibuat.** Tabel
> kosong tanpa kode yang menyentuhnya. Catatan `-- reserved for BPS release` masuk ke
> deskripsi migration supaya tidak ada yang mengira lupa dikerjakan.

### C-12 — Tiga aturan bisnis tanpa jaring pengaman di database

Ditemukan di T011 saat repository ditulis. Ketiganya sah secara skema, tapi
penegakannya **sepenuhnya** bergantung pada service — tidak ada constraint yang
menangkapnya kalau service keliru atau ada dua request bersamaan.

| Aturan | Apa yang menjaganya sekarang | Risiko |
|---|---|---|
| Satu tagihan per (pengangkut, nomor invoice) | `BillRepository::findByInvoice()` | Rawan balapan: dua request bersamaan sama-sama lolos pengecekan lalu sama-sama menyisip |
| Satu provisi hanya muncul sekali dalam satu tagihan | `BillService` | Tidak ada UNIQUE `(tbl_bill_id, tbl_provision_id)` |
| F-01.7 tumpang tindih rate card | `RateCardRepository::overlapping()` + validasi T017 | UNIQUE `TRC_UK01` mengandung kolom nullable; perilaku Oracle untuk kunci komposit yang sebagian NULL **belum diverifikasi** |

> **Resolusi: belum diputuskan.** Menambah UNIQUE pada dua yang pertama murah dan
> jelas menguntungkan, tapi keduanya menyentuh tabel yang skemanya sudah lewat
> checkpoint T007 — jadi ia masuk lewat migration baru, bukan mengedit yang lama.
> Diputuskan saat T048/T052 (tagihan) dan T017 (rate card) dikerjakan.
>
> Baris ketiga butuh verifikasi dulu, bukan keputusan: **jalankan di Oracle
> staging** apakah dua baris dengan bagian non-null identik dan NULL di posisi
> yang sama ditolak `TRC_UK01`. Kalau ditolak, tidak ada yang perlu dikerjakan.
> Jangan menyimpulkan dari SQLite — perilakunya berbeda.
>
> **Status T017 (2026-09-23):** validasi F-01.7 kini ada di `RateCardService` dan NULL-aware.
> Dua hal masih terbuka:
>
> 1. **`TRC_UK01` di Oracle — terdokumentasi, belum diverifikasi.** Dokumentasi Oracle
>    menyatakan unique komposit menolak dua baris dengan bagian non-null identik dan NULL di
>    posisi yang sama (baris yang seluruh kolom kuncinya NULL tidak diindeks). Tidak ada
>    staging, dan tidak dicoba di produksi.
> 2. **Race condition cek-lalu-sisip.** Dua request bersamaan untuk kombinasi sama dengan
>    periode bertumpang tapi `VALID_FROM` berbeda sama-sama lolos pengecekan; `TRC_UK01` hanya
>    menangkap `VALID_FROM` yang identik. Pilihan: kunci baris carrier (`SELECT … FOR UPDATE`)
>    di dalam transaksi, atau trigger di Oracle. Risikonya kecil untuk halaman master yang
>    diisi segelintir orang, tapi nyata.
>
> **T018 menambah dua jalur cek-lalu-tulis yang sama:** F-01.8 saat hapus/ubah baris, dan
> prioritas ganda. `TRL_UK01` menangkap prioritas ganda, tapi dua request bersamaan yang
> masing-masing menghapus satu baris bisa meninggalkan kartu aktif tanpa baris prioritas 1.
> Obatnya sama: kunci baris rate card di dalam transaksi.

---

### C-13 — Penamaan 25 permission tidak konsisten ✅ SELESAI

`spec.md` §8 menulis 16 permission dengan pemisah `-` (`transporter-order-view`) dan
9 dengan `.` (`transporter.approve-transaction`, `.override-cost`, `.pull-grn`,
`.approve-additional-expense`, `.pull-additional-expense`, `.post-jv`, `.post-tjv`,
`.waive-document`, `.post-payment`). Seluruh modul lain di repo (`core-`, `lc_control-`,
`mis-`) seragam memakai `-`.

T014 **menyalinnya persis apa adanya** dan memaku ketidakkonsistenan itu dalam test
(`it keeps the permission names exactly as spec.md §8 writes them, dots and all`),
supaya tidak ada yang "merapikan" satu nama diam-diam lalu membuka gate route yang
memakainya.

> **Diputuskan 2026-09-22 (D-11): diseragamkan ke `-`.** Kesembilan nama bertitik diganti
> di seeder, migration menu, route middleware dan test; `spec.md` §8 dikoreksi di perubahan
> yang sama. Test `it names every permission the way the rest of the repo does, with no dots`
> menjaganya, dan diverifikasi menggigit. Dikerjakan sebelum P1 karena setelah permission
> melekat ke role di produksi, mengganti namanya berarti memindahkan setiap pivot
> `model_has_permissions` — dan yang terlewat jadi halaman yang diam-diam tak bisa dibuka.

---

### C-14 — Deploy tidak menjalankan `migrate` maupun `db:seed` ⚠️

`.github/scripts/deploy.sh` baris 40: `# php artisan migrate --force` — **dikomentari.**
Tidak ada `db:seed` sama sekali. Skrip hanya `changelog:sync` lalu serangkaian `*:cache`.

Memindahkan entri menu dari seeder ke migration (keputusan Indra di T014) benar arahnya,
tapi **tidak cukup**: dengan skrip apa adanya, migration menu pun tidak akan jalan di
produksi. Ke-23 migration tabel Transporter juga belum pernah jalan di sana.

> Ini gap rilis, bukan gap modul. Perlu diputuskan sebelum cutover (P7): apakah deploy
> mulai menjalankan `migrate --force`, atau migration Transporter dijalankan manual dan
> tercatat di runbook. Jangan diselesaikan sambil lalu di dalam task modul — menyalakan
> `migrate` di deploy berdampak ke seluruh repo, bukan cuma Transporter.

---

### C-15 — Rujukan dokumen T014 hanya ada di `develop` ✅ SELESAI (rebase 2026-09-22)

Instruksi T014 menunjuk `Modules/Core/database/migrations/2026_09_16_090000_sync_sidebar_menus_with_permissions.php`
dan bagian "Sidebar & Menu Registration" di `CLAUDE.md`. **Keduanya tidak ada di
`feat/Transporter`** — ada di `develop` (dan cabang-cabang barunya), yang sudah 53 commit
di depan titik cabang modul ini.

Akibatnya bagi T014: bentuk migration menu diturunkan dari
`2026_08_22_100000_add_core_module_permissions.php` + `create_cm_menus_table.php` +
semantik `MenuTreeService`, bukan dari contoh yang dimaksud. Hasilnya sepadan, tapi
belum pernah dibandingkan langsung dengan contoh itu.

> **Diselesaikan 2026-09-22: branch di-rebase ke `develop`** (30 commit, tiga konflik —
> `CLAUDE.md` scope, `modules_statuses.json`, `config/logging.php` — ketiganya penambahan
> dua sisi, digabung tanpa membuang apa pun). Divergensi kini 0. Berkas rujukan T014 sudah
> ada di branch ini.
>
> **Pembandingan dengan contoh Core: selesai 2026-09-22.** Enam titik diperiksa. Lima sepadan
> — guard `migrationDisabled()` + `tablesReady()`, parent diresolusi lewat `code` bukan id,
> upsert on `code`, gate ditulis ke `model_has_permissions` dengan `model_type` CmMenu, dan
> `MenuTreeService::clearAllCache()` dalam `try/catch` di akhir. Empat perbedaan, semuanya
> disengaja dan tidak satu pun merugikan:
>
> | | Core | Transporter | Penilaian |
> |---|---|---|---|
> | Permission belum ada | dilewati (`continue`) | dibuat (`firstOrCreate`) | **Transporter lebih benar.** Melewatinya menghasilkan menu tanpa gate, dan menu tanpa gate terlihat semua user yang login |
> | Parent tidak ketemu | node dilewati, `return null` | grup dibangun ulang, anak tersambung lagi | **Sepadan, beda maksud.** Core menambal pohon milik modul lain yang mungkin diubah tangan; Transporter memiliki seluruh subtree-nya, jadi memulihkannya benar. Diuji: pohon dihapus total lalu `up()` → 36 baris pulih, hanya node modul tanpa parent |
> | Akses DB | `DB::table()` + helper `permissionTable()` | Eloquent (`CmMenu`, `Permission`, `syncPermissions`) | **Sepadan.** Model sudah memaku `oracle_mgthris`; `syncPermissions` juga menyempitkan gate saat direvisi, sementara Core harus `clearGate()` manual dulu |
> | Pivot yang dibersihkan | `model_has_permissions` **dan** `model_has_roles` | hanya permissions | **Sepadan di sini.** Core sedang mencabut gate role lama yang salah; menu Transporter tidak pernah digate role |
>
> Satu hal yang dikonfirmasi bukan bug: `down()` memanggil `forceDelete()` padahal `CmMenu`
> tidak memakai `SoftDeletes` — Eloquent memperlakukannya sebagai delete biasa, dan test
> `down()` membuktikannya.

---

### C-16 — Kolom kembali lowercase di test, meniru `oci8` di produksi

`yajra/laravel-oci8` memaku `PDO::ATTR_CASE => PDO::CASE_LOWER`, jadi **di produksi setiap
kolom kembali lowercase apa pun ejaannya di migration**. `develop` kini menirukan itu untuk
SQLite lewat `TestCase::matchOracleColumnCasing()` (commit `743cadb2`), sehingga lingkungan
test berhenti memaafkan ejaan yang tidak pernah benar di produksi.

Terbukti saat rebase: `SequenceSeederTest` memakai `pluck('HMMS_SEQ_NAME')` mengikuti ejaan
migration Core, lulus sebelum rebase, lalu gagal dengan *Undefined property* sesudahnya.

> **Aturannya:** nama tabel dan klausa `WHERE`/`whereIn` boleh UPPERCASE — kedua database
> tidak peka huruf di sana. Apa pun yang **membaca balik hasil** (`pluck`, `value`, akses
> properti `stdClass`, atribut model) harus lowercase. Ini melengkapi temuan T010 (D-08),
> yang menyelesaikan sisi migration; ini sisi pembacaannya.

> Pengecualian: koneksi yang menyatakan `ATTR_CASE` sendiri dibiarkan — `oracle_report`
> memakai `CASE_NATURAL` supaya alias kolom laporan bertahan sebagaimana ditulis.

---

### C-11 — `transp_bill` punya dua kolom tanpa enum ⚠️

`spec.md` §3 mendaftar **13 enum**, dan T009 membuat ketigabelasnya. Tapi
`spec.md` §2.4 memberi `transp_bill` dua kolom yang jelas-jelas berdomain
terbatas dan tidak ada enumnya:

| Kolom | Tabel | Tipe | Nilai yang disebut spec |
|---|---|---|---|
| `TRB_STATUS` | `transp_bill` | `number(1)` | **tidak disebut sama sekali** |
| `TRB_TYPE` | `transp_bill` | `varchar(10)` | `YARNS` / `CHIPS` / `OTHERS` |
| `TGP_RESULT` | `transp_grn_pull_attempt` | `varchar(10)` | `SUCCESS` / `FAILED` |
| `TGP_TRIGGER` | `transp_grn_pull_attempt` | `varchar(10)` | `MANUAL` |

Bandingkan dengan tabel sejenis: `TRO_STATUS` punya `TransactionStatusEnum`,
`TAE_STATUS` punya `AdditionalExpenseStatusEnum`, `TRP_STATUS` punya
`ProvisionStatusEnum`. Keempat kolom di atas sendirian tanpa pasangan.

`TGP_RESULT` dan `TGP_TRIGGER` ditemukan belakangan, di T011: scope
`TranspGrnPullAttempt::failed()` terpaksa membandingkan literal `'FAILED'`,
satu-satunya perbandingan status berbasis literal di seluruh modul.

> **Resolusi: tidak diarang sekarang.** `TRB_TYPE` sebenarnya bisa langsung
> dibuat — nilainya sudah tertulis. `TRB_STATUS` tidak: keadaan alur tagihan
> menentukan kapan `TJV` boleh diposting dan kapan tagihan terkunci, dan menebaknya
> berarti menebak alur akuntansi.
>
> **`TRB_*` dibuat di T048**, saat alur tagihan dirancang dengan kode di tangan;
> **`TGP_*` di T038**, bersama generate TPCHP yang menuliskannya. Saat itu
> `spec.md` §3 berubah dari 13 menjadi 17 enum, dan
> `tests/Unit/Transporter/EnumTest.php` — yang meng-assert jumlahnya — ikut
> diperbarui. Assertion itu memang ada supaya penambahan enum jadi keputusan
> sadar, bukan kelalaian.

---

### C-10 — Lingkungan lokal: PHP & node hanya ada di container

`php` dan `node` **tidak terpasang di host WSL**. Keduanya jalan di dalam container:

| Container | Isi |
|---|---|
| `apps-mutugading-app-1` | php 8.3 + node 22 — di sinilah `artisan`, `pint`, `test` dijalankan |
| `apps-mutugading-vite-1` | dev server Vite |
| `apps-mutugading-queue-1` | queue worker |

Nama container di `e2e/bin/state.sh` dan `e2e/CLAUDE.md` masih `laravel-app` — tidak
cocok dengan yang sedang jalan. Itu drift yang sudah ada sebelum proyek ini dan **bukan
urusan modul Transporter**; jangan diperbaiki sambil lalu.

> **Resolusi:** `preflight.sh` mencari runner-nya sendiri (`php` host → `laravel-app` →
> container ber-nama `app`), dan bisa dipaksa lewat `TRANSPORTER_APP_CONTAINER=<nama>`.
> Setiap perintah `artisan` dijalankan dengan
> `docker exec -w /var/www/html <container> bash -lc '...'`.

**Jebakan yang sudah terbukti:**

1. `php artisan tinker --execute="exit(0)"` **selalu** mengembalikan kode keluar ≠ 0 —
   psysh melempar `BreakException` apa pun statusnya. Cek apa pun lewat tinker harus
   memakai pola `echo "TANDA:..."` lalu `grep -q`.
2. **Container jalan sebagai `root`, host sebagai uid 1000.** Setiap berkas yang dibuat
   generator (`module:make`, `make:model`, …) maupun ditulis ulang `pint` jadi milik
   `root` dan **tidak bisa dihapus/diedit dari host.** Terbukti di T001. Sesudah setiap
   perintah yang menulis berkas, jalankan:
   ```bash
   docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'chown -R 1000:1000 Modules/Transporter'
   ```
3. **`pint --dirty` tidak melihat berkas untracked.** Modul yang baru di-scaffold
   dilaporkan `0 files` — hijau palsu. Sebelum commit modul baru, pakai
   `vendor/bin/pint Modules/Transporter` atau `pint --test` penuh (yang dipakai CI).
4. **`$bindings` adalah nama properti milik Laravel.** `Application::register()` membaca
   `property_exists($provider, 'bindings')` dan mengaksesnya dari luar, jadi properti
   `private array $bindings` di sebuah ServiceProvider melempar
   *Cannot access private property* saat boot. `RepositoryServiceProvider` Transporter
   memakai `$repositories`.

5. **Berpindah branch membuat autoload kehilangan namespace Transporter.** `Modules/Transporter`
   tidak ada di `develop` maupun cabang-cabangnya, jadi `composer dump-autoload` yang jalan
   selagi working tree berada di branch lain menulis `vendor/composer/autoload_psr4.php` tanpa
   namespace itu. `vendor/` adalah Docker volume yang dipakai bersama semua branch, sehingga
   keadaan itu ikut terbawa saat kembali ke `feat/Transporter`: **seluruh suite gagal dengan
   `Class "Modules\Transporter\Providers\TransporterServiceProvider" not found`**, dan
   kegagalannya tidak ada hubungannya dengan kode yang baru ditulis. Terbukti di T013.
   Sesudah kembali dari branch lain, jalankan lebih dulu:
   ```bash
   docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'composer dump-autoload -o'
   ```
6. **Stack dev berganti nama dan user (2026-09-24).** Container kini
   `apps-mutugading-dev-app-1` dan jalan sebagai **uid 1000 dengan `HOME=/`** — jebakan 2
   di atas tidak lagi berlaku di stack ini. Akibatnya psysh tidak bisa menulis
   `/.config/psysh`, **setiap `tinker` gagal**, dan blocker 7/8 `preflight.sh` jatuh FAIL
   palsu padahal koneksinya sehat. `php_run` di `preflight.sh` kini memasang `HOME=/tmp`
   bila `$HOME` tidak bisa ditulis. Untuk tinker manual:
   `docker exec -e HOME=/tmp -w /var/www/html apps-mutugading-dev-app-1 bash -lc '...'`.

---

## MISSING — belum ada sama sekali

| # | Kelompok | Volume | Fase |
|---|---|---|---|
| M-01 | Modul `Modules/Transporter` beserta 3 provider, `module.json`, `modules_statuses.json`, `vite.config.js`, route, breadcrumb | 1 modul | P0 |
| M-02 | 22 migration tabel baru di `MGTHRIS` (+1 `transp_carrier_alias`, D-20, T025) | 23 tabel | P0 |
| M-03 | Unique index fungsional + ± 14 index pendukung | — | P0 |
| M-04 | Baris sequence di `HM_MST_SEQUENCES` untuk `TPDN`/`TPCHP`/`TPSVC`/`TBILL` + `*_SYS_ID` | 8 sequence | P0 |
| M-05 | 8 enum + state machine | 8 file | P0 |
| M-06 | Model `MgtHris/` (20) + `MgtDat/` read-only (± 10: `OtInvoiceHead`, `OtInvoiceItem`, `OtWmsPackTableAlthara`, `OtGrHead`, `OtGrBatch`, `OmSupplier`, `ImVsStaticValue`, `MenuUser`, `FtOs`, `FvOsMatch`) | ± 30 | P0 |
| M-07 | Repository interface + Eloquent + binding | ± 18 pasang | P0 |
| M-08 | `Modules/Core/app/Services/Erp/` — folder belum ada; service GL diangkat ke sini | 4 file | P0 |
| M-09 | 4 log channel di `config/logging.php` | 4 | P0 |
| M-10 | `Modules/Transporter/config/config.php` → `config('transporter.*')` | 1 | P0 |
| M-11 | Menu (`cm_menus`) + permission Spatie + seeder | ± 20 permission | P0 |
| M-12 | Helper due date 10/25 + kalkulator tarif W/Q + kalkulator chip | 3 | P0 |
| M-13 | Master carrier / rate card / rate line / charge type / service category / posting account / document type | 7 halaman | P1 |
| M-14 | Monitoring GRN chip (F-07) | 1 halaman | P1 |
| M-15 | Transaksi angkutan + approval maker-checker + inbox | 4 halaman | P2 |
| M-16 | Staging & generate otomatis surat jalan | 2 halaman + 1 job + 1 command | P2 |
| M-17 | GRN Chip Siap Digenerate + `transp_grn_pull_attempt` | 1 halaman + 1 job | P2 |
| M-18 | Additional expense + 3 halaman monitoring | 4 halaman | P2 |
| M-19 | Provisi + preview jurnal + posting `TPJV` + `transp_posting_log` | 2 halaman + 1 job | P3 |
| M-20 | Tagihan + pajak + matching + `EXPENSE_DIRECT` + posting `TJV` + kontrol dokumen surat jalan + job scan | 5 halaman + 2 job + 1 command | P4 |
| M-21 | 5 report + script migrasi + compatibility view | 5 + ± 15 script | P5–P6 |

---

## Catatan kritis untuk agen

| # | Hal | Nilai |
|---|---|---|
| 1 | **Migration terakhir di repo** | `Modules/LcControl/…/2026_09_09_000000_add_interest_rate_to_lc_limit_calc_detail_table.php`. Transporter mulai dari **`2026_09_18_000000`** dan naik per 1000 detik (`_000000`, `_001000`, …) |
| 2 | **Gaya migration** | Nama tabel `snake_case` **lowercase**, nama kolom **UPPERCASE** (`TRO_SYS_ID`). Selalu `protected $connection = 'oracle_mgthris'` + guard `migrationDisabled()` + `down()` |
| 3 | **Uang** | `NUMBER(18,2)` — di Laravel: `$table->decimal('TRO_TOTAL_AMOUNT', 18, 2)`. Jangan `NUMBER` polos (T-16) |
| 4 | **PK** | `{prefix}_SYS_ID` `varchar(30)`, diisi `SysIdHelper::generate()`. Bukan `autoIncrement()` — LcControl memakai autoIncrement untuk master kecil, Transporter tidak, karena nomornya perlu bisa dilacak |
| 5 | **Dilarang** | `wire:navigate`, `navigate: true`, `DB::` facade di Livewire, `env()` di luar config, `dd()`/`dump()`, `{!! !!}` tanpa Purifier |
| 6 | **Sebelum commit** | `vendor/bin/pint --dirty` lalu `php artisan test --parallel` |
| 7 | **Scaffolding** | Pakai skill `mutugading-crud` (master), `mutugading-transaction` (halaman ber-approval), `mutugading-scaffold` (satu layer). Jangan tulis boilerplate manual |
| 8 | **e2e** | Descriptor baru wajib di `e2e/pages/transporter/`, doc di `e2e/docs/modules/transporter/`. Baca `e2e/CLAUDE.md` dulu — aturannya ketat dan sebagian tidak bisa ditawar |
| 9 | **Schema pemilik = `MGTHRIS`** | Seluruh 23 tabel modul (22 + `transp_carrier_alias`, D-20) dibuat lewat koneksi `oracle_mgthris`. **Tidak ada tabel baru di `MGTDAT`** — lihat `spec.md` §0 |
| 9b | **`MGTDAT` read-only** | Kecuali `FT_UNPOSTED_TRANS_HEADER`, `FT_UNPOSTED_TRANS_DETAIL`, dan `FM_TRAN_DOC_NO`. Tidak ada yang lain, tidak pada fase mana pun sebelum cutover. Compatibility view T074 membuat *view*, bukan tabel |
| 10 | **Menjalankan perintah** | `docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'php artisan …'`. Tidak ada `php` maupun `node` di host — lihat C-10 |
| 11 | **`HMEMD_USER_ORION`** | Baru terisi 20 dari 2.838 karyawan, 18 nilai unik, satu (`2462`) tidak valid. Posting GL **harus menolak** kalau kosong (F-05.10) — jangan menulis string kosong ke `th_cr_uid` |

---

### C-17 — ~~Nama tabel sistem lama bukan `MT_*`~~ — bukan gap ✅

Dicatat saat T015 dengan anggapan `spec.md` §9 menyebut tabel sumber berawalan `MT_*`.
**Anggapan itu keliru:** §9 sejak awal memakai nama yang benar (`MGT_TRANSP_HEAD`,
`MGT_TRANSP_DETAIL_COST`, `MGT_TRANSP_MASTER`, `MGT_TRANSP_RATE`, dst, schema `MGTDAT`),
dan tidak ada dokumen di `.ai/` yang merujuk `MT_HEAD`. Anggapan `MT_*` lahir dari tebakan
nama tabel saat menyelidiki akses Oracle, lalu terbawa ke pembekalan subagen T015.

> Tidak ada yang perlu dikoreksi di `spec.md` maupun `plan.md`. Kode dan test T015 sudah
> memakai nama yang benar. Nomor ini juga bertabrakan dengan aturan cleansing C-17 PRD —
> lihat catatan penomoran di kepala berkas.

---

### C-18 — `MGT_TRANSP_RATE` tidak berversi tanggal: 68% tarif historis sudah berubah ✅ diputuskan

Diukur 2026-09-23: dari 32.604 transaksi historis yang punya baris biaya dan
kartu tarif, **22.307 (68%)** memakai tarif yang sudah berbeda dari yang tersimpan
di `MGT_TRANSP_RATE` hari ini. Tabel itu menyimpan satu baris per kombinasi dan
menimpanya setiap kali harga berubah — tidak ada `VALID_FROM`/`VALID_TO`.

Akibatnya di sistem lama: **menghitung ulang provisi periode lampau menghasilkan
angka yang tidak akan pernah cocok lagi dengan yang sudah masuk GL.**

> **Resolusi:** ini justru pembenaran `TRC_VALID_FROM`/`TRC_VALID_TO` (F-01.5) dan
> `RateCardResolver`. Dua konsekuensi yang harus diingat:
>
> 1. **Migrasi master (T067)** tidak bisa membuat satu kartu per kombinasi dengan
>    `VALID_FROM` seragam — itu akan mewarisi persis masalahnya. Riwayat tarif yang
>    sebenarnya hanya bisa direkonstruksi dari `MGT_TRANSP_DETAIL_COST`, yang
>    menyimpan tarif yang **benar-benar dipakai** tiap transaksi. Perlu keputusan
>    Indra saat T067 tiba.
> 2. **Rekonsiliasi R-12 (T076)** tidak boleh membandingkan angka baru terhadap
>    perhitungan ulang memakai master tarif — pembandingnya harus `MTDC_TOTAL_RATE`
>    yang tersimpan.

---

### C-19 — Dua bug ditemukan test T015, keduanya gagal senyap ✅ SELESAI

Keduanya sudah ada di kode sebelum T015 dan tidak ada test yang menyentuhnya.

**(a) `TranspRateCard::effectiveOn()` — batas awal diam-diam eksklusif.**
Scope-nya memakai `where('trc_valid_from', '<=', $date)`. Kolomnya `DATE`, tapi
baik Oracle maupun SQLite menyimpannya berjam (`2025-03-01 00:00:00`), sedangkan
`$date` datang sebagai `Y-m-d` telanjang — dan `'2025-03-01 00:00:00' <= '2025-03-01'`
bernilai FALSE. Kartu yang mulai berlaku **tepat** pada tanggal transaksi karena itu
tidak pernah terpilih. Order tanggal 1 setiap kali tarif baru mulai berlaku akan jatuh
ke kartu lama, atau gagal sama sekali kalau tidak ada kartu lama. Diperbaiki jadi
`whereDate()`/`orWhereDate()`.

**(b) `GrossUpHelper::rate()` — gross-up diam-diam jadi 0%.**
Versi pertama memakai `config('transporter.gross_up.rate', 0.02)`. Argumen default
`config()` hanya berlaku saat kuncinya **tidak ada**; kunci yang ada tapi bernilai
`null` (config ter-publish setengah, atau env kosong) dikembalikan sebagai `null` dan
di-cast jadi `0.0` — carrier bertanda gross-up menyala tidak menambahkan apa pun.
Diperbaiki jadi `?? self::DEFAULT_RATE`.

> Keduanya ditemukan oleh test yang menguji **batas** dan **cabang config**, bukan
> jalur bahagianya. Pola yang layak ditiru di task berikutnya.

---

### C-20 — Aplikasi lokal terhubung ke Oracle produksi; tabel `transp_*` belum ada di sana ⚠️

`.env` lokal memakai `DB_CONNECTION=oracle_mgthris` yang menunjuk **Oracle produksi**
(51 schema, data hidup). Dicek 2026-09-23: **0 tabel `TRANSP%`** di `MGTHRIS`.

Akibatnya:

1. **Halaman Transporter tidak bisa dibuka di browser lokal.** Setiap halaman yang membaca
   `transp_*` akan error, dan satu-satunya cara membuat tabelnya adalah `migrate` — DDL ke
   produksi. Verifikasi P1 ke atas hanya lewat feature test SQLite; spec e2e (descriptor
   sudah dibuat mulai T016) belum bisa dijalankan.
2. **Risiko:** siapa pun yang menjalankan `php artisan migrate` atau `composer run setup`
   di mesin ini membuat 22 tabel Transporter langsung di produksi. Guard `DISABLE_MIGRATIONS`
   ada per migration, tapi tidak disetel di `.env`.

> **Perlu diputuskan sebelum ada yang mencoba halaman di browser:** Oracle staging / skema
> dev terpisah yang boleh diberi `transp_*`, atau aplikasi lokal dijalankan di SQLite untuk
> mencoba halaman Transporter. Sampai itu, jangan menjalankan migrate di mesin ini.
