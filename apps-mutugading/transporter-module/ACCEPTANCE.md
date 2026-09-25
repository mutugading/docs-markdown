# ACCEPTANCE.md — Transporter v1

Gerbang per fase. **Fase berikutnya tidak dimulai sebelum seluruh kotak fase ini
tercentang.** Ini gerbang, bukan catatan — kotak yang dicentang tanpa dibuktikan
membuat berkas ini tidak berguna.

Cara mencentang: tulis tanggal dan bukti (nomor commit, nama berkas laporan, atau
"diverifikasi bersama <siapa>"). Kotak tanpa bukti dianggap kosong.

---

## P0 — Fondasi

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | ~~22~~ 23 tabel ter-migrasi bersih di SQLite (CI) dan Oracle (staging) — tabel ke-23 `transp_carrier_alias` (D-20) | | |
| 1b | **Seluruhnya di schema `MGTHRIS`**: `grep -rn "oracle_mgtdat" Modules/Transporter/database/migrations/` **kosong** | | |
| 2 | Setiap tabel punya 4 kolom audit + `_LEGACY_ID`; setiap kolom uang `decimal(18,2)` | | |
| 3 | Unique index fungsional `transp_order_no_uk` terpasang di Oracle, dilewati di SQLite | | |
| 4 | 8 sequence terdaftar; 50 generate berbarengan tidak menghasilkan nomor duplikat | | |
| 5 | 13 enum lengkap dengan `label()`, `badgeVariant()`, `options()`; tabel transisi tertutup unit test | | |
| 6 | Service GL pindah ke Core; **test suite LcControl hijau sebelum dan sesudah** (RK-06) | | |
| 7 | Halaman LC journal-voucher & payment-voucher dibuka manual, masih berfungsi | | |
| 8 | `YarnRateCalculator` mereproduksi `MTDC_TOTAL_RATE` untuk 500 transaksi historis, **selisih 0** | | |
| 9 | `ExchangeRateParityTest` cocok dengan `curs_usd_b` untuk 24 bulan terakhir (RK-04) | | |
| 10 | `DueDateHelper` benar untuk tanggal 1–31, keduanya cabang | | |
| 11 | 4 log channel menulis ke berkas masing-masing | | |
| 12 | Menu muncul di sidebar; setiap route punya breadcrumb; judul tab benar | | |
| 13 | ⭑ Checkpoint T007 (DDL) dan T015 (kalkulator) direview Indra | | |

> **Butir 8 adalah yang menentukan.** Kalau tarif hasil normalisasi master tidak sama
> dengan yang tersimpan di sistem lama, seluruh rencana migrasi master salah (RK-15) —
> dan ketahuannya di sini masih murah.

---

## P1 — Master

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | `VENDOR`/`INTERNAL` wajib `TCA_SUPP_CODE`; `BUYER_BORNE` wajib tanpa — ditegakkan di service | | |
| 2 | Nama/NPWP/alamat vendor dibaca dari `OM_SUPPLIER`, tidak disimpan ulang | | |
| 3 | Validasi tumpang tindih rate card menolak periode bertumpang, termasuk `VALID_TO` NULL | | |
| 4 | Aksi *Naikkan tarif* menutup kartu lama dan membuat yang baru dalam satu transaksi | | |
| 5 | Rate card tidak bisa diaktifkan tanpa rate line prioritas 1 bertipe yang benar | | |
| 6 | Matriks tarif chip menunjukkan kombinasi kosong; pencocokan memakai **kode**, bukan nama | | |
| 7 | Seeder 6 charge type, 4 service category, 8 posting account, 3 document type — semuanya idempoten | | |
| 8 | `grep -rn "'404001'\|'208027'\|'401001'\|'208026'" Modules/Transporter` **kosong** | | |
| 9 | Monitoring GRN chip menampilkan daftar yang benar; auto-create menghasilkan carrier + rate card + rate line | | |
| 10 | Export vendor & matriks tarif jalan sebagai job dengan notifikasi | | |

---

## P2 — Transaksi

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | **Satu** halaman daftar untuk ketiga jalur, dibedakan hanya oleh `TRO_SOURCE` | | |
| 2 | Pembuat tidak bisa menyetujui transaksinya sendiri — ditolak **di service**, bukan hanya disembunyikan di UI | | |
| 3 | Reject tanpa alasan ditolak; setelah reject status kembali `Draft` dan bisa diperbaiki | | |
| 4 | Approval massal 10 baris menghasilkan 10 entri activity log terpisah | | |
| 5 | Unapprove ditolak untuk order yang sudah `Provisioned` | | |
| 6 | Hasil generate otomatis (yarn **dan** chip) berstatus `Submitted`, tidak pernah `Approved` | | |
| 7 | Soft delete wajib alasan; baris terhapus tetap tersimpan, tidak dipindah ke tabel arsip | | |
| 8 | `EDN` dan `WDN` tidak pernah muncul di staging surat jalan | | |
| 9 | Pengelompokan generate TPDN diuji terhadap satu hari data nyata | | |
| 10 | Jalur chip: **nol** `INSERT`/`UPDATE` ke schema `MGTDAT` (`grep` seluruh jalur) | | |
| 11 | Kelima validasi F-09.5 tampil sebagai peringatan **sebelum** generate; ambang mengutip config | | |
| 12 | Berat > kapasitas truk = peringatan (tombol tetap aktif); berat > ambang = tombol mati | | |
| 13 | Setiap percobaan generate chip tercatat di `transp_grn_pull_attempt`, termasuk yang gagal | | |
| 14 | `TPSVC` tanpa kategori ditolak; tidak bisa punya baris surat jalan; nomor berbentuk `TPSVC-{YYYY}{6}` | | |
| 15 | Additional expense tanpa induk ditolak di service; pembuat tidak bisa menyetujui miliknya | | |
| 16 | F-04.10 (induk belum diprovisi → ikut terbawa) dan F-04.11 (sudah diprovisi → tidak ada susulan) diuji terpisah | | |
| 17 | ⭑ Checkpoint T032 direview Indra | | |

---

## P3 — Provisi

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | Order `BUYER_BORNE` dan `INTERNAL` **tidak pernah** lolos ke provisi — ditegakkan di service | | |
| 2 | Provisi kedua atas order yang sama ditolak (unique `TRP_ORDER_ID` + service) | | |
| 3 | Preview jurnal menampilkan Dr/Cr, akun, IDR, USD, **dan kurs yang dipakai beserta tanggalnya** | | |
| 4 | Akun diambil dari `transp_posting_account` yang berlaku pada tanggal dokumen | | |
| 4a | **Nomor akun `transp_posting_account` dikonfirmasi Finance** (D-18 — seed mengikuti jurnal Orion terposting, menyimpang dari Appendix B) | | |
| 5 | Posting tanpa `HMEMD_USER_ORION` **ditolak**, pesannya menyebut nama user dan langkah perbaikannya | | |
| 6 | Job gagal di tengah → rollback penuh; nol baris `FT_UNPOSTED_*` tersisa | | |
| 7 | **Nol** `INSERT` ke `FT_TXN_AUTH` (D-01) | | |
| 8 | Posting batch yang sama dua kali ditolak sebelum menyentuh Oracle | | |
| 9 | Staging: satu `TPJV` diposting per user Finance, bisa diotorisasi user lain di Orion | | |
| 10 | ⭑ **Shadow-run 3 periode historis: preview `TPJV` vs `FT_*`, baris per baris, selisih 0** (RK-01) | | |

> **Butir 10 adalah gerbang go/no-go sebenarnya.** Kalau jurnal sistem baru berbeda
> dengan sistem lama, tidak ada gunanya melanjutkan ke tagihan.

---

## P4 — Tagihan

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | PPN & PPh dihitung dari profil vendor; masa berlaku `PPH 0.5` divalidasi | | |
| 2 | Baris PPN **tidak dibuat** bila nomor FP diawali `08`; memakai `108005` bila diawali `05` | | |
| 3 | `EXPENSE_DIRECT` tanpa akun beban / jenis selisih / alasan ditolak | | |
| 4 | `CANCELLATION` dan `DEDUCTION` wajib negatif; jumlah baris selalu = nilai tagihan | | |
| 5 | Total header ≠ jumlah baris → Generate TJV mati, selisihnya ditampilkan | | |
| 6 | Tagihan terblokir menampilkan **daftar surat jalan yang menahan**, bukan pesan generik | | |
| 7 | `CHPGRN` tidak pernah punya baris `transp_document_scan` — tagihan chip tidak bisa ter-hold | | |
| 8 | Waive wajib alasan, tercatat di activity log, boleh oleh pembuat tagihan sendiri | | |
| 9 | **Job scan dengan mount tidak tersedia: nol baris berubah status** (F-10.3b) | | |
| 10 | Menarik additional expense bersifat pilihan; yang tidak ditarik tidak berubah apa pun | | |
| 11 | Shadow-run TJV 3 periode historis vs `FT_*`, selisih 0 | | |
| 11a | `BILL_REVERSAL` mendebit akun hutang provisi (`208027`/`208026`), dan `TPSVC` dibebankan ke akun per kategori — dikonfirmasi Finance (D-18) | | |
| 12 | Tautan ke PDF scan bisa dibuka dari halaman tagihan | | |

> **Butir 9 penting justru karena membosankan.** Menandai berkas sebagai hilang saat
> mount sedang down akan menahan seluruh tagihan secara keliru — kegagalan yang terlihat
> seperti masalah bisnis, bukan masalah infrastruktur.

---

## P5 — Report

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | Lima report jalan sebagai queued job dengan notifikasi bertautan unduh | | |
| 2 | Filter periode menjangkau 2014–sekarang; tidak dibatasi N bulan terakhir (NF-10) | | |
| 3 | `grep -rn "TO_DATE('01-JAN" Modules/Transporter` **kosong** — tidak ada batas tanggal hardcode (T-11) | | |
| 4 | Dua nomor tagihan yang dulu dikecualikan hardcode dipindah ke data (T-12) | | |
| 5 | Angka dibandingkan dengan report lama untuk **3 periode berbeda**, selisih 0 (R-08) | | |
| 6 | Susunan kolom Excel cocok dengan `RPT2XLS.put_cell` sistem lama — itu yang diterima user | | |

---

## P6 — Migrasi (staging)

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | Snapshot M-0 selesai; checksum baris & jumlah cocok | | |
| 2 | Script migrasi idempoten — dijalankan dua kali tidak menduplikasi | | |
| 3 | Setiap baris yang dibuang/diperbaiki masuk `transp_migration_exception` dengan kode aturannya | | |
| 4 | ⭑ Pemetaan 739 master → ± 50 vendor + ± 716 rate card **di-sign-off Head of Despatch & Head of Finance** (Q-18) | | |
| 5 | 23 kombinasi ganda diselesaikan manual dan disetujui | | |
| 6 | ⭑ `HMEMD_USER_ORION` lengkap untuk seluruh pemosting Finance; `2462` dibersihkan | | |
| 7 | R-01 jumlah baris per tabel — selisih 0 | | |
| 8 | R-02 `SUM(tp_amt + tp_oth_amt)` per bulan per `txn_code` — selisih 0 | | |
| 9 | R-03 `SUM(tpb_total)`, PPN, PPh per vendor per bulan — selisih 0 | | |
| 10 | R-04 daftar nomor JV & TJV — identik | | |
| 11 | R-05 jumlah provisi per status — selisih 0 | | |
| 12 | R-06 outstanding AP per vendor vs `TRANSPORTER_BUDGET_V` — selisih 0 | | |
| 13 | R-07 20 transaksi acak field-per-field — selisih 0 | | |
| 14 | R-08 report baru vs lama, 3 periode — selisih 0 | | |
| 15 | R-09 `PRC_DELVRY_MARGIN_MGT`, 10 report `SALR*`, `OPD106_MGT_1`, `SALATTREKAP` sebelum vs sesudah view — selisih 0 | | |
| 16 | R-10 `FV_TRANS_DETAILS_PPH23_V`, `VIEW_SUPP_OUTSTANDING`, `UPDATE_FSFC_VCH` — selisih 0 | | |
| 17 | R-11 job scan vs `EFILL_009` — selisih 0 selama **5 hari kerja berturut-turut** | | |
| 18 | **R-12 hitung ulang 500 transaksi vs `MTDC_TOTAL_RATE` — selisih 0** | | |
| 19 | Tidak ada objek `INVALID` di `MGTDAT` setelah compatibility view dipasang & recompile | | |
| 20 | `UPDATE_FSFC_VCH` sudah menerima `TPJV` di daftar `th_tran_code` (RK-18) | | |
| 21 | Performa view lintas-schema diukur; materialized view disiapkan bila perlu (RK-12) | | |
| 22 | Script rollback **diuji di staging**, bukan hanya ditulis | | |

> **R-12 dan butir 15 adalah go/no-go.** R-12 membuktikan pemecahan master tidak
> mengubah tarif; butir 15 membuktikan ± 25 objek dan 12 report Orion tidak patah.

---

## P7 — Cutover

| # | Kriteria | Bukti | Tgl |
|---|---|---|---|
| 1 | UAT Finance & Despatch selesai, **sign-off tertulis** | | |
| 2 | H-14 dry-run di produksi (baca saja) lolos rekonsiliasi ulang | | |
| 3 | H-7 freeze Forms berlaku; H-1 periode JV bulan berjalan ditutup | | |
| 4 | Prasyarat `TPJV` di Orion terverifikasi untuk tahun berjalan (`IM_TXN_AUTH`, `FM_TRAN_DOC_NO`) | | |
| 5 | Grant eksplisit `MGTDAT` dari DBA sudah diberikan; aplikasi tidak lagi bersandar pada role `DBA` | | |
| 6 | Mount `Doc_Folder` tersedia read-only di server produksi | | |
| 7 | Jendela cutover ± 4 jam: seluruh langkah §6.6 diverifikasi satu per satu | | |
| 8 | `EFILL_009` dimatikan; job scan baru aktif dan sudah terbukti di mode shadow | | |
| 9 | Hak tulis user pada tabel `MGTDAT` transporter dicabut **di level database**, bukan sekadar himbauan (RK-05) | | |
| 10 | Snapshot M-0 dipertahankan 30 hari | | |
| 11 | H+30: tidak ada temuan terbuka | | |
| 12 | H+90: objek lama diarsipkan dengan prefix `Z_` | | |
