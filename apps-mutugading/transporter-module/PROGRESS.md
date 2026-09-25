# PROGRESS.md — Transporter v1

Diisi **setiap kali satu task selesai, sebelum lanjut ke task berikutnya.**
Kalau baris ini tidak terisi, task itu belum dianggap selesai — apa pun kata commit-nya.

| Kolom | Isinya |
|---|---|
| **Status** | `TODO` · `WIP` · `DONE` · `BLOCKED` |
| **Tgl** | Tanggal selesai, `YYYY-MM-DD` |
| **Commit** | SHA pendek commit yang menyelesaikannya |
| **Review** | Kosong kalau tidak butuh review · `menunggu` · `OK <tanggal>` · `revisi: <apa>` |
| **Catatan** | Hanya diisi kalau ada yang perlu diketahui: keputusan, kompromi, alasan BLOCKED |

Lima task bertanda **⭑** wajib direview Indra sebelum task berikutnya dimulai —
lihat `plan.md` §Checkpoint. Task setelahnya tidak boleh dimulai selama review
masih `menunggu`.

**Ringkasan:** 18 / 82 selesai · fase aktif: **P1 — Master**

---

## P0 — Fondasi (T001–T015) · 2 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T001 | Scaffold modul Transporter | DONE | 2026-09-17 | e839ab13 | | Scaffold nwidart dirapikan ke `design.md` §1: `EventServiceProvider`, `routes/api.php`, controller & view demo dibuang; `RepositoryServiceProvider` ditambahkan; folder `tests/` tidak dibuat (C-6). Empat jebakan lingkungan dicatat di `gap.md` C-10. |
| T002 | Migration master (7 tabel) | DONE | 2026-09-17 | 6fb16e18 | | 7 migration `2026_09_18_000000`–`_006000`. Constraint dinamai eksplisit (`TCA_PK01`/`TCA_UK01`) — konvensi baru di `spec.md` §1. FK ditunda ke T007 sesuai task. `tests/Feature/Transporter/SchemaTest.php` ditambahkan sebagai pengganti CI yang tidak pernah jalan; tumbuh mengikuti T003–T006. Dua koreksi dokumen: `boolean` → `CHAR(1)` bukan `NUMBER(1)`, dan `tests/Integration` ternyata bukan testsuite (`gap.md` C-6). |
| T003 | Migration transaksi (5 tabel) | DONE | 2026-09-17 | 1c31b82a | | `_007000`–`_011000`. UNIQUE + index per-tabel dipasang di migration `create`-nya, bukan ditunda ke T007 — catatan peringatan sudah ditaruh di T007 supaya tidak dipasang dua kali. Kolom turunan flex field Orion di `transp_dn_stage` dibuat nullable (teks bebas, kerap kosong). SchemaTest naik jadi 5 test, 12 tabel. |
| T004 | Migration additional expense (2 tabel) | DONE | 2026-09-17 | 0b4c4fc6 | | `_012000`–`_013000`. SchemaTest naik jadi 6 test, 14 tabel. |
| T005 | Migration provisi & tagihan (4 tabel) | DONE | 2026-09-17 | c215bce2 | | `_014000`–`_017000`. `TBL_LINE_TYPE` dibatasi enum (T009), bukan check constraint — konsisten dengan seluruh repo yang tidak memakai check constraint di mana pun. |
| T006 | Migration infrastruktur (4 tabel) | DONE | 2026-09-17 | 9e724a53 | | `_018000`–`_021000`. **22 tabel lengkap.** SchemaTest 10 test, dengan assertion jumlah tabel = 22 supaya tabel ke-23 gagal di sini dulu. |
| T007 ⭑ | Foreign key, index, dan unique index fungsional | DONE | 2026-09-17 | 468b8a75 | OK 2026-09-17, log menyusul | 22 FK, `_022000`. `TBL_FK03` + kolom `TBL_ADDITIONAL_EXPENSE_ID` dibuang atas keputusan Indra (D-06) — siklus FK hilang. **Bug spec ditemukan dan diperbaiki:** guard driver `'oci8'` tidak pernah cocok (nama drivernya `oracle`) — produksi akan diam-diam dapat unique polos. Guard dibalik ke sqlite. Cabang Oracle belum dijalankan di staging — **log checkpoint diisi setelah staging Oracle,** karena FK tidak bisa dibuktikan di SQLite. |
| T008 | Seeder sequence di `HM_MST_SEQUENCES` | DONE | 2026-09-17 | f685bd73 | | 8 sequence. **Tidak memakai `updateOrCreate`** seperti bunyi task — itu akan menimpa `HMMS_LAST_VALUE` dan memundurkan penghitung saat seeder dijalankan ulang. SysId dinaikkan ke 8 digit (D-07). 7 test baru. |
| T009 | Enum (13 enum) | DONE | 2026-09-17 | 03661fe7 | | 4 di `Enums/Master/`, 9 di `Enums/Transaction/`. Tabel transisi `spec.md` §3 dikodekan; aturan maker ≠ approver tetap di service (T032). **Temuan: `TRB_STATUS` dan `TRB_TYPE` tidak punya enum di spec** — dicatat `gap.md` C-11, dibuat di T048. 18 test / 560 assertion, termasuk matriks transisi 6x6 penuh. |
| T010 | Model | DONE | 2026-09-17 | bcb4c1d9 | | 21 owned + 10 read-only Orion (21, bukan 22 — C-9). **Temuan besar: nama kolom UPPERCASE membuat setiap pembacaan atribut bernilai null di SQLite.** Ke-23 migration diubah ke lowercase; DDL Oracle dibandingkan baris per baris, 103 pernyataan nol perbedaan (D-08). Kolom asli 10 tabel Orion diambil langsung dari `all_tab_columns`, tidak dikarang. |
| T011 | Repository interface + Eloquent + binding | DONE | 2026-09-17 | 53082e54 | | 21 pasang (bukan 18) dikerjakan 4 subagen paralel per domain; binding, test, dan verifikasi dipegang terpusat. `RepositoryServiceProvider` jadi `DeferrableProvider`. 8 test kontrak — termasuk eksekusi nyata ke-21 `paginate()` dan setiap finder terhadap skema, bukan sekadar refleksi. Temuan subagen: `TGP_RESULT`/`TGP_TRIGGER` tanpa enum (C-11), tiga aturan bisnis tanpa constraint (C-12). |
| T012 | Mesin posting GL milik Transporter | DONE | 2026-09-17 | 291c280c | | **Task ditulis ulang** — tidak jadi memindahkan kode dari LcControl; Transporter menulis mesinnya sendiri (D-09). LcControl & Core nol sentuhan. 4 subagen: model MGTDAT, DTO+exception, repository+service, plus 2 survei. `assertNotAlreadyPosted()` memakai `th_flex_10` dengan format sistem lama supaya ikut menangkap dokumen lama saat cutover (D-10). Prefix `TPROV-` menunggu konfirmasi. |
| T013 | Log channel + config modul | DONE | 2026-09-22 | 4c087e97 | | 4 channel `single`/`info` mengikuti `lc_auto_mature`; config modul persis `design.md` §9. `ConfigTest` 9 test — channel dibuktikan dengan menulis lalu membaca berkasnya, dan daftar `TRANSPORTER_*` dibandingkan dua arah antara config modul dan `.env.example` supaya setting baru tidak lolos tanpa didaftarkan. **Catatan lingkungan:** berpindah branch membuat `vendor/composer/autoload_*` kehilangan namespace Transporter — seluruh suite gagal dengan `TransporterServiceProvider not found` sampai `composer dump-autoload -o`. Dicatat di `gap.md` C-10. |
| T014 | Menu, permission, route, breadcrumb | DONE | 2026-09-22 | 5690a875 | | 33 route + 33 breadcrumb; halaman yang belum jadi menunjuk `PlannedPageController` (501, menyebut nama route). **Menu lewat migration idempoten, bukan seeder** (keputusan Indra) — seeder tidak jalan saat deploy. Migration membuat ke-25 permission lebih dulu (`firstOrCreate`, daftar dibaca dari seeder = satu sumber kebenaran): melewati permission yang belum ada seperti contoh Core menghasilkan menu **tanpa gate**, dan menu tanpa gate terlihat semua user. 21 test; test gate diverifikasi menggigit dengan sengaja melonggarkan satu gate. **Dikonfirmasi Indra (D-11):** pagar role ditambah `Transporter - Despatch` + `Transporter - Stores` (diberi awalan modul mengikuti pola repo), dan ke-9 permission bertitik diseragamkan ke `-` sebelum P1 membagikannya — `spec.md` §8 dikoreksi di perubahan yang sama. Temuan `gap.md`: C-13 **selesai**, C-14 (deploy tidak menjalankan `migrate`) dan C-15 (branch tertinggal 53 commit dari develop) **masih terbuka**. |
| T015 ⭑ | Helper + kalkulator tarif + unit test | DONE | 2026-09-23 | e6e77504 | OK 2026-09-23 | `DueDateHelper`, `GrossUpHelper`, `CurrencyConverter`, `RateCardResolver`, `YarnRateCalculator`, `ChipRateCalculator`, plus `OrderCostData`, `RateCalculatorInterface` dan `RateCalculatorRegistry`. 107 test baru (suite 452→559, skip tetap 10). Nama tabel sumber `MGT_TRANSP_*` di `MGTDAT` dikonfirmasi cocok dengan `spec.md` §9 (catatan C-17 yang menyebut sebaliknya ternyata salah dasar, sudah ditutup). **Temuan besar 1 — `MGT_TRANSP_RATE` tidak berversi tanggal:** dari 32.604 transaksi historis, **22.307 (68%)** punya tarif yang sudah berbeda dari yang tersimpan hari ini. Itu justru penyakit yang `TRC_VALID_FROM/TO` ada untuk menyembuhkan (F-01.5), dan alasan uji reproduksi memakai tarif dari baris historisnya sendiri — yang diuji algoritmanya, bukan harga. **Temuan besar 2 — pembulatan berubah Agustus 2024:** sistem lama menyimpan hasil kali penuh (`584158,548`) sampai Juli 2024 dan `ROUND()` sejak September 2024; Agustus 2024 bulan campuran. Kalkulator meniru perilaku yang berlaku sekarang (ROUND). **Hasil reproduksi 500 transaksi TPDN (era ≥2024-09): 499 cocok persis, 1 selisih < 1 rupiah** — transaksi itu dari jalur entri minoritas (10 dari 1.406 baris Q era modern, semuanya user DES5/DESH1) yang masih menyimpan hasil kali penuh. **Tidak dibuang dan toleransinya tidak dilonggarkan**: ditandai `stores_raw_product` di fixture, dihitung terpisah, dan selisihnya dipaku < 1 rupiah. Chip **120/120 cocok**. Paritas kurs **24/24 bulan** cocok dengan `CURS_USD_B` (relatif < 1e-6). **Dua bug ditemukan test:** (a) `TranspRateCard::effectiveOn()` memakai `where()` atas kolom DATE yang tersimpan berjam (`2025-03-01 00:00:00`), membuat kartu yang mulai **tepat** pada tanggal transaksi tidak terpilih — batas awal diam-diam eksklusif; diperbaiki jadi `whereDate()`. (b) `GrossUpHelper::rate()` memakai argumen default `config()` yang tidak menyala saat kuncinya bernilai null, membuat gross-up diam-diam jadi 0%. **Keputusan D-12 & D-13** dicatat di `DECISIONS.md`.

## P1 — Master (T016–T026) · 2,5 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T016 | Halaman master pengangkut (carrier) | DONE | 2026-09-23 | 05b47b07 | | CRUD `transp_carrier`; aturan tipe ditegakkan di DTO **dan** service. Nama/NPWP/alamat dibaca dari Orion, tidak disimpan — alamat ternyata di `OM_ADDRESS` lewat `OM_SUPPLIER_ADDRESS` default (2 model read-only baru), NPWP dari `SUPP_FLEX_08`/`FLEX_07` (`SUPP_TAX_REGN_NO` kosong untuk ke-48 transporter). Hapus ditolak bila dirujuk salah satu dari 4 FK. 20 test; tiga sabotase (aturan DTO, `usages()`, aturan service) masing-masing menggagalkan test-nya. **e2e: descriptor + doc dibuat, spec belum dijalankan** — tabel `transp_*` belum ada di Oracle dan app lokal terhubung ke produksi (`gap.md` C-20). Temuan: `TranspCarrier` tanpa audit hook (service mengisi `*_created_by` sendiri); `SUPP_FLEX_06` (flag gross-up Orion) belum direkonsiliasi dengan `TCA_IS_GROSS_UP`. |
| T017 | Halaman rate card + validasi tumpang tindih | DONE | 2026-09-23 | fce53dc4 | | F-01.7 di service: kombinasi NULL-aware, batas inklusif, nonaktif diabaikan tapi reaktivasi dicek. **`overlapping()` (T011) masih pakai `where()` untuk tanggal** — varian kedua bug `gap.md` C-19, diperbaiki ke `whereDate()`. Naikkan tarif: tutup lama H-1 + kartu baru dalam satu transaksi, rate line ikut disalin, `VALID_TO` diwarisi. Kartu terpakai order tidak bisa dihapus dan kombinasi/kapasitas/tanggal mulainya dibekukan. Riwayat via filter `?carrier=`. 29 test; 5 sabotase ditangkap. Sabotase *NULL dilewati* awalnya **lolos** (DTO selalu mengosongkan kolom pasangan) — ditambah test kartu yang melanggar pasangan destinasi/vendor sehingga tertangkap. Belum: browser/e2e (`gap.md` C-20); perilaku `TRC_UK01` di Oracle terdokumentasi, belum diverifikasi; race condition cek-lalu-sisip (`gap.md` C-12). |
| T018 | Editor rate line | DONE | 2026-09-23 | c2b0dd57 | | **Keputusan Indra:** kartu baru lahir nonaktif, diaktifkan setelah baris diisi. F-01.8 di service, termasuk jalur samping (hapus/ubah baris yang membuat kartu aktif kehilangan baris prioritas 1 ditolak) dan Naikkan tarif (baris salinan dicek). Baris kartu terpakai order dibekukan; Chip hanya satu baris Q (F-09.9). Pratinjau lewat `RateCalculatorRegistry`, rumus tidak ditulis ulang. Test T017 disesuaikan lewat helper `activeCard()` tanpa menghapus assertion. 21 test; 8 sabotase subagen + 3 ulang di sesi utama tertangkap. **Untuk T068:** kartu migrasi tanpa baris prioritas 1 yang benar tidak bisa di-Naikkan tarif, dan barisnya tak bisa dibetulkan bila kartunya sudah terpakai — jalurnya nonaktifkan + kartu baru, atau dibersihkan saat migrasi. F-01.8 hanya dicek saat kartu menyala, supaya kartu migrasi tetap bisa ditutup. |
| T019 | Matriks tarif chip | DONE | 2026-09-24 | 0bbe80fc | | Sumbu matriks diputuskan di **D-14**: baris = pengangkut aktif ber-kartu CHIP (sakelar semua pengangkut), kolom = kode vendor chip dari kartu **plus** `GH_SUPP_CODE` GRN chip 12 bulan terakhir (`transporter.chip.matrix_grn_lookback_months`), jadi vendor chip baru tampak kosong sebelum generate-nya gagal. Sel dinilai pada satu tanggal: tersedia / belum aktif (nonaktif, belum berlaku, berakhir) / kosong. Sel kosong = `<a href>` ke `rate-cards?create=1&carrier=&service=CHIP&chip_vendor=`; `RateCardPage` kini membuka form terisi awal dari URL (pengangkut tak dikenal diabaikan, jenis truk tidak diisi). GRN dibaca lewat `ChipReceiptReader` (bukan repository — `RepositoryTest` menuntut `paginate()` di SQLite untuk tiap interface). Pencocokan murni kode; `GH_FLEX_05` tak dibaca, dipaku test yang memindai token kode. 12 test; 8 sabotase tertangkap (termasuk batas bawah jendela GRN inklusif walau `GH_DT` berjam). Data lokal: 12 bulan terakhir hanya `LS02128` yang ber-GRN chip; sepanjang masa 8 vendor. Belum: browser/e2e (`gap.md` C-20). Sampingan: preflight blocker 7/8 FAIL palsu karena container dev baru jalan sebagai uid 1000 dengan `HOME=/` — diperbaiki `a5c75d06`, `gap.md` C-10 butir 6. |
| T020 | Duplikasi rate card | DONE | 2026-09-24 | f37f8ce1 | | Aksi baris **Duplikat** + modal di halaman rate card. **D-15:** salinan mengikuti status aktif sumber (kalau selalu nonaktif, acceptance 1 tidak pernah teruji karena F-01.7 hanya memeriksa kartu aktif); cek tumpang tindih selalu jalan atas kombinasi tujuan; kembaran `TRC_UK01` — termasuk kartu **nonaktif** yang tak dilihat `overlapping()` — ditolak lewat `findByUniqueKey()` sebelum sampai DB; kombinasi sama dengan sumber ditolak dan diarahkan ke Naikkan tarif. Aturan kolom/referensi lewat `validated()` yang sama dengan create, tidak disalin. Yang boleh diganti: destinasi / vendor chip, jenis truk, kapasitas, masa berlaku; pengangkut dan jenis jasa tetap. Baris tarif disalin apa adanya, `trl_max_cap` tidak diskalakan ke kapasitas baru. Sumber tidak disentuh, jadi kartu terpakai order boleh disalin. 15 test; 8 sabotase tertangkap (+1 kontrol no-op lolos sebagaimana mestinya). Belum: browser/e2e (`gap.md` C-20); race cek-lalu-sisip `TRC_UK01` sama dengan `gap.md` C-12. |
| T021 | Master jenis biaya tambahan | DONE | 2026-09-24 | 6cfe9fcb | | CRUD `transp_charge_type` + `TransporterChargeTypeSeeder` (dipanggil `TransporterDatabaseSeeder`). **D-16:** seeder mengunci pada `TCT_CODE` — sisip kode yang belum ada, **tidak pernah menimpa** baris yang ada (suntingan Finance selamat antar deploy; kode yang dihapus kembali). Seed **7 jenis**: enam F-04.1 + **`ALAMAT`** (55 baris lama `MTDO_OTHCHG_ID`, dikonfirmasi Indra 2026-09-24 — PRD perlu dikoreksi). `TCT_LEGACY_ID` memuat nilai lama (`BIAYA TOL`, `JLNDITUTUP`, `INAP`, `KAWAL`, `ALAMAT`) untuk pemetaan C-23 — dicatat di T068. Lampiran wajib mati untuk semua. Jenis yang dipakai baris biaya: tidak bisa dihapus (disarankan nonaktifkan) dan **kodenya dibekukan**; nama/lampiran/status tetap bisa diubah. Kode dinormalisasi huruf besar `A-Z0-9_`. 13 test; 8 dari 9 sabotase tertangkap — yang lolos (normalisasi kode di `save()` Livewire dihapus) memang redundan: `ChargeTypeData` sudah menormalisasi di konstruktor. Belum: browser/e2e (`gap.md` C-20). |
| T022 | Master kategori jasa | DONE | 2026-09-24 | e02cdf52 | | Kembar T021 (service ber-*diff* nol setelah penggantian nama). **D-17:** seed **5 kategori** — empat F-02.12 + **`OTHERS`** "Lain-lain" (dikonfirmasi Indra 2026-09-24 — PRD perlu dikoreksi). `TSC_LEGACY_ID` = teks `TP_NO` lama; dicek di `MGT_TP_PROVISION`: `PALLET` 132, `AMBIL BARANG` 24, `RETUR BENANG` 22, `OTHERS` 150 = **328** (cocok C-06), dicatat di T068. Kategori yang dirujuk `transp_order` tidak bisa dihapus dan kodenya dibekukan; hitungan pakai termasuk order soft-delete karena FK Oracle tetap menunjuknya. 14 test; 7 sabotase tertangkap (+1 kontrol no-op). Belum: browser/e2e (`gap.md` C-20). |
| T023 | Master akun posting | DONE | 2026-09-24 | 4072a093 | | **Seed menyimpang dari PRD Appendix B — D-18, arah disetujui Indra, nomor akun MENUNGGU KONFIRMASI FINANCE.** GL Orion (`FT_CUR_`+`FT_PRV_TRANS_DETAIL`): TJV mendebit **akun hutang provisi** `208027` (18.712 baris) / `208026` (4.971), bukan akun beban seperti Appendix B — seed spec akan membebankan biaya angkut dua kali dan tak pernah menutup hutang provisi. Jasa tanpa surat jalan tidak diprovisi (`TP_JV_NO='NON TPDN'`) dan dibebankan langsung per kategori: `PALLET` `401009`, `RETUR BENANG` `401011`, `AMBIL BARANG` `401001`, `OTHERS` `401001`→`404001` sejak Feb 2024. Jenis posting baru `BILL_EXPENSE_SERVICE` + kolom baru `TPA_SERVICE_CATEGORY_CODE` (migration `2026_09_24_000000`), jaring pengaman `404001` untuk kategori tanpa akun. 13 baris seed, pola D-16. `resolve()` wajib tanggal dokumen (dipaku lewat refleksi), urutan kategori → jenis jasa → umum. Callout peringatan Finance di halaman. Nomor akun hanya di seeder — test memindai token string di seluruh `Modules/Transporter`. `effectiveOn()` diperbaiki ke `whereDate()` (varian ketiga `gap.md` C-19; yang benar-benar menggigit batas **awal**, batas akhir tidak terkena perbandingan teks). `ACCEPTANCE.md` P3-4a & P4-11a: gerbang tidak boleh dicentang sebelum Finance konfirmasi. 21 test; 8 dari 9 sabotase tertangkap — yang lolos (batas akhir `where()`) memang tidak salah di data yang tersimpan tengah malam, diterangkan di test. Label `BILL_PPN_IN_05` dikoreksi (`05` = awalan faktur pajak, bukan tarif 0,5%). Belum: browser/e2e (`gap.md` C-20). |
| T024 | Master jenis dokumen surat jalan | DONE | 2026-09-24 | 33cac9d7 | | Seed `LDN`/`PDN`/`JWDN` terkontrol (T-20: `JWDN` terlewat di `EFILL_009`), pola seeder D-16. **D-19:** `CHPGRN` ditolak juga lewat halaman master (F-10.6 aturan, bukan data; huruf kecil/berspasi pun ditolak). Pola folder **relatif** terhadap `transporter.document_scan.root` — satu seed untuk staging & produksi, mengikuti legacy `Doc_Folder/{TYPE}/{TAHUN}/`; hanya `{prefix}`/`{year}`, pola absolut/`..`/backslash/placeholder asing/kurung tak berpasangan ditolak agar job T055 tidak keluar dari mount. Service menyediakan `controlledCodes()` dan `folderFor()` untuk T055 dan tagihan. Tanpa FK dari `transp_document_scan` (kode disimpan sebagai teks), jadi jenis yang sudah punya hasil scan ditolak dihapus di service — tanpa itu baris scan jadi yatim — dan kodenya dibekukan. 19 test; 9 sabotase tertangkap. Belum: browser/e2e (`gap.md` C-20). |
| T025 | Monitoring GRN chip (pengganti TRNSP005) | TODO | | | | |
| T026 | Export master | TODO | | | | |

## P2 — Transaksi (T027–T041) · 5 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T027 | Order service + penomoran | TODO | | | | |
| T028 | Halaman daftar transaksi angkutan | TODO | | | | |
| T029 | Form transaksi manual + tarik surat jalan | TODO | | | | |
| T030 | Kalkulasi biaya + override | TODO | | | | |
| T031 | Jenis transaksi `TPSVC` | TODO | | | | |
| T032 ⭑ | Approval maker-checker + inbox + approval massal | TODO | | | | |
| T033 | Soft delete + activity log | TODO | | | | |
| T034 | Staging tarik surat jalan | TODO | | | | |
| T035 | Generate TPDN sebagai job | TODO | | | | |
| T036 | Command terjadwal tarik harian | TODO | | | | |
| T037 | Halaman "GRN Chip Siap Digenerate" | TODO | | | | |
| T038 | Generate TPCHP + jejak percobaan | TODO | | | | |
| T039 | Additional expense + approval | TODO | | | | |
| T040 | Halaman monitoring additional expense | TODO | | | | |
| T041 | Notifikasi | TODO | | | | |

## P3 — Provisi (T042–T047) · 2,5 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T042 | Halaman provisi | TODO | | | | |
| T043 ⭑ | Preview jurnal `TPJV` | TODO | | | | |
| T044 | Posting `TPJV` sebagai job | TODO | | | | |
| T045 | `transp_posting_log` + idempotensi | TODO | | | | |
| T046 | Halaman riwayat posting | TODO | | | | |
| T047 | Test integrasi posting | TODO | | | | |

## P4 — Tagihan (T048–T058) · 4 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T048 | Entri tagihan + profil pajak | TODO | | | | |
| T049 | Matching provisi | TODO | | | | |
| T050 | Baris `EXPENSE_DIRECT` + selisih | TODO | | | | |
| T051 | Tarik additional expense ke tagihan | TODO | | | | |
| T052 | Validasi total tagihan | TODO | | | | |
| T053 | Preview + posting `TJV` | TODO | | | | |
| T054 | Kontrol dokumen surat jalan | TODO | | | | |
| T055 | Job scan dokumen | TODO | | | | |
| T056 | Laporan selisih provisi vs tagihan | TODO | | | | |
| T057 | Halaman monitoring hold dokumen | TODO | | | | |
| T058 | e2e alur tagihan | TODO | | | | |

## P5 — Report (T059–T064) · 1,5 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T059 | Transporter Register (F-08.1) | TODO | | | | |
| T060 | Delivery Note Transporter Value (F-08.2) | TODO | | | | |
| T061 | Freight Cost per Kg (F-08.3) | TODO | | | | |
| T062 | Transporter Provision & Bill, parametrik (F-08.4) | TODO | | | | |
| T063 | Transporter Budget / Outstanding AP (F-08.5) | TODO | | | | |
| T064 | e2e report | TODO | | | | |

## P6 — Migrasi (T065–T076) · 3,5 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T065 | Kerangka script migrasi | TODO | | | | |
| T066 | M-0 snapshot | TODO | | | | |
| T067 ⭑ | M-1 master: 739 baris → ± 50 carrier + ± 716 rate card | TODO | | | | |
| T068 | M-2 transaksi | TODO | | | | |
| T069 | M-3 provisi | TODO | | | | |
| T070 | M-4 tagihan | TODO | | | | |
| T071 | M-5 staging + M-6 sinkronisasi sequence | TODO | | | | |
| T072 ⭑ | M-6b pemetaan `HMEMD_USER_ORION` | TODO | | | | |
| T073 | Migrasi status dokumen | TODO | | | | |
| T074 | M-7 compatibility view di `MGTDAT` | TODO | | | | |
| T075 | M-8 shadow-run job scan | TODO | | | | |
| T076 | M-9 rekonsiliasi R-01..R-12 | TODO | | | | |

## P7 — UAT & Cutover (T077–T082) · 2 minggu

| Task | Judul | Status | Tgl | Commit | Review | Catatan |
|---|---|---|---|---|---|---|
| T077 | UAT Finance | TODO | | | | |
| T078 | H-14 dry-run migrasi produksi (baca saja) | TODO | | | | |
| T079 | H-7 freeze + H-1 tutup periode JV | TODO | | | | |
| T080 | H cutover | TODO | | | | |
| T081 | Rollback plan | TODO | | | | |
| T082 | H+1..H+30 hypercare | TODO | | | | |

---

## Log checkpoint

Diisi setiap kali salah satu dari enam checkpoint direview. Tulis apa yang dilihat dan
apa keputusannya — bukan sekadar "OK".

| Tgl | Checkpoint | Yang direview | Keputusan |
|---|---|---|---|
| 2026-09-17 | T007 — DDL 22 tabel | Indra membaca langsung 22 berkas migration T002–T006, lalu FK T007. Satu koreksi: hubungan biaya tambahan ↔ baris tagihan disimpan satu arah saja (D-06) | **Lanjut ke T008.** Bagian FK belum diverifikasi berjalan — SQLite membuang FK yang ditambahkan ke tabel yang sudah ada, jadi pembuktiannya menunggu staging Oracle. Baris kedua di bawah diisi saat itu |
| _(menunggu)_ | T007 — FK + `transp_order_no_uk` di Oracle | | Diisi setelah migration dijalankan di staging Oracle |
| 2026-09-23 | T015 — kalkulator tarif vs 500 transaksi historis | 499/500 TPDN cocok persis, 1 selisih pecahan < 1 rupiah dari jalur entri minoritas; 120/120 chip; 24/24 bulan paritas kurs. Test reproduksi diverifikasi menggigit (`round`→`floor` menggagalkannya); kedua bug fix diverifikasi dengan mengembalikan kode lama | **Lanjut ke P1.** Pembulatan: nilai lama dipertahankan, T068 menyalin `MTDC_TOTAL_RATE` apa adanya tanpa hitung ulang (D-12). Tarif tak berversi: R-12/T076 memakai tarif dari baris historis, rate card migrasi tidak diberi `VALID_FROM` seragam (`gap.md` C-18, dicatat di T067/T068/T076) |
| | T032 — maker ≠ approver + perlakuan data migrasi | | |
| | T043 — preview jurnal TPJV vs FT_* untuk 3 periode | | |
| | T067 — pemetaan 739 master → 50 vendor | | |
| | T072 — pemetaan HMEMD_USER_ORION | | |

## Log rebase

Branch ini hidup lama di samping `develop`; setiap penyatuan dicatat di sini.

| Tgl | Ke | Commit dipindah | Konflik | Catatan |
|---|---|---|---|---|
| 2026-09-22 | `develop` @ `b4b1a477` | 30 | 3 | `CLAUDE.md` (scope `report` vs `transporter`), `modules_statuses.json` (modul `Report` vs `Transporter`), `config/logging.php` (3 channel report vs 4 channel transporter) — ketiganya penambahan dua sisi, digabung tanpa membuang apa pun. Satu test gagal sesudahnya: `develop` kini memaksa SQLite mengembalikan kolom lowercase seperti `oci8` di produksi, membongkar `pluck('HMMS_SEQ_NAME')` di `SequenceSeederTest` yang ejaannya memang tidak pernah benar di produksi (C-16, diperbaiki `7b0bf1aa`). Cadangan: `backup/pre-rebase-transporter` |

## Log hambatan

Task yang `BLOCKED` beserta apa yang dibutuhkan untuk membukanya.

| Tgl | Task | Terhambat oleh | Butuh siapa | Dibuka tgl |
|---|---|---|---|---|
| | | | | |
