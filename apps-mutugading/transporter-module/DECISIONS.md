# DECISIONS.md — Transporter v1

Setiap keputusan teknis yang **tidak ada di PRD** dicatat di sini, **sebelum kodenya
ditulis**. Termasuk keputusan yang menyelesaikan pertentangan di dalam PRD sendiri.

Kenapa berkas ini ada: keputusan diam-diam yang baru ketahuan saat review adalah
sumber pekerjaan ulang yang paling mahal. Menuliskannya butuh dua menit; menemukannya
enam minggu kemudian butuh sehari.

## Kapan wajib menulis entri

| Situasi | Wajib? |
|---|---|
| PRD tidak menyebutkan hal ini sama sekali, dan pilihannya berpengaruh ke skema, angka, atau alur | **Ya** |
| PRD bertentangan dengan dirinya sendiri, atau dengan codebase | **Ya** |
| Menyimpang dari PRD karena alasan teknis | **Ya — dan tanyakan dulu ke Indra** |
| Keputusan mengubah nilai yang masuk ke GL | **Ya — berhenti dan tanya. Jangan putuskan sendiri** |
| Pilihan gaya kode dalam batas yang sudah diatur `CLAUDE.md` / `RULES.md` | Tidak |
| Nama variabel, urutan method, susunan folder yang sudah ditentukan `design.md` | Tidak |

## Format

```
### D-xx — <judul singkat>
**Tanggal:** · **Task:** · **Status:** Berlaku | Dicabut | Menunggu konfirmasi
**Konteks:** apa yang memaksa keputusan ini diambil
**Keputusan:** apa yang diputuskan, satu kalimat
**Alternatif yang ditolak:** apa lagi yang dipertimbangkan, dan kenapa tidak dipilih
**Konsekuensi:** apa yang jadi berbeda karenanya
**Perlu konfirmasi:** siapa, tentang apa — atau `—`
```

---

### D-01 — `FT_TXN_AUTH` tidak ditulis — itu ranah Orion
**Tanggal:** 2026-09-17 · **Task:** T012, T044, T053 · **Status:** Berlaku —
**dikonfirmasi Indra 2026-09-17**

**Konteks:** PRD bertentangan dengan dirinya sendiri. §4.4 butir 1 menyebut penulisan
baris `FT_TXN_AUTH` sebagai *"dikonfirmasi wajib"* untuk `TPJV` dan `TJV`, sementara
§3.7.4a, F-05.9, T-27, dan Q-29 menyatakan sebaliknya.

**Keputusan:** **`FT_TXN_AUTH` tidak ditulis. Pembuatan baris otorisasi adalah ranah
Orion, bukan ranah modul ini.** Modul hanya menulis `FT_UNPOSTED_TRANS_HEADER` +
`FT_UNPOSTED_TRANS_DETAIL` dan memajukan `FM_TRAN_DOC_NO`. Titik.

**Dasarnya — pembagian tanggung jawab, bukan sekadar pilihan yang aman:**

| Siapa | Melakukan apa |
|---|---|
| **Orion** — `STP_DINSERT_APPR_RECS` / `STP_DINSERT_APPR_RECS_NEW` | Membuat baris `FT_TXN_AUTH`. Dipanggil paket keuangannya sendiri (`FINPKG_FT2502`, `FINPKG_FT2504`, `ORNDBPKG_ADJUSTMENT`) saat transaksi dibuat lewat layar Orion |
| **Aplikasi luar** — modul LC, dan modul ini | Hanya menulis header + detail, lalu memajukan `FM_TRAN_DOC_NO` |

Bukti pendukungnya konsisten dengan pembagian itu: dari 477 dokumen `JV` 2026 yang
sudah berpindah ke `FT_CUR_TRANS_HEADER`, **182 tidak punya baris `FT_TXN_AUTH`** dan
tetap terotorisasi serta terposting normal.

Artinya modul LC sudah benar sejak awal, dan **`PKG_TRANSPORTER` yang menyimpang** —
ia menulis `FT_TXN_AUTH` sendiri, melewati prosedur standar Orion, dan bahkan tidak
konsisten: hanya di `jv_provision`, `jv_provision_chp`, dan `tjv_bill`; tidak sama
sekali di `tjv_bill_chp`, `tjv_bill_add`, dan `tjv_bill_chp_add` (T-27). Penyimpangan
itu **tidak ditiru**.

**Alternatif yang ditolak:** meniru `PKG_TRANSPORTER`. Ditolak karena menduplikasi
logika milik ERP, tidak konsisten antar prosedur di sistem lama, dan akan patah untuk
`TPJV` yang belum punya dokumen acuan — lookup `tauth_tbl_identifier` dari dokumen
`FT_TXN_AUTH` yang sudah ada akan melempar `NO_DATA_FOUND` pada posting `TPJV` pertama.

**Konsekuensi:**
- `EloquentJournalVoucherRepository` yang ada sudah benar — **tidak ada pekerjaan
  tambahan untuk hal ini di T012.**
- Acceptance P3 butir 7 dan P4: **nol** `INSERT` ke `FT_TXN_AUTH`, dibuktikan `grep`.
- Kalau suatu saat Orion ternyata membutuhkannya, nilainya diambil dari **konfigurasi**
  (konstan `'GL'` untuk `JV` maupun `TJV`), bukan dari lookup ke data dokumen.
- §4.4 butir 1 PRD **salah dan perlu dikoreksi** di revisi berikutnya — ia sisa dari
  v1.8, sebelum §3.7.4a ditulis ulang di v2.1.

**Perlu konfirmasi:** — *(selesai: dikonfirmasi Indra 2026-09-17. Sisa pekerjaan
administratif: koreksi §4.4 PRD.)*

---

### D-02 — Prefix `TST_` dan `TDC_` menggantikan `tds_` ganda
**Tanggal:** 2026-09-17 · **Task:** T003, T006 · **Status:** Berlaku

**Konteks:** PRD §4.2 memberi prefix `tds_` kepada **dua** tabel berbeda:
`transp_dn_stage` dan `transp_document_scan`. Konvensi prefix kolom unik seluruh
database jadi rusak, dan dua model akan punya kolom `tds_sys_id` yang berbeda arti.

**Keputusan:** `transp_dn_stage` → **`TST_`**, `transp_document_scan` → **`TDC_`**.

**Alternatif yang ditolak:** mengganti nama salah satu tabel. Ditolak karena kedua nama
tabelnya sendiri sudah tepat dan sudah dipakai di banyak tempat di PRD; yang salah hanya
prefiksnya.

**Konsekuensi:** seluruh kolom kedua tabel memakai prefix baru. `spec.md` §1 dan §2
sudah memakainya.

**Perlu konfirmasi:** Indra — kalau ada pasangan prefix lain yang lebih disukai, ubah di
sini **dan** di `spec.md`, jangan di satu tempat saja.

---

### D-03 — Baris `_ADD` legacy dimigrasi jadi `EXPENSE_DIRECT`, bukan provisi
**Tanggal:** 2026-09-17 · **Task:** T070 · **Status:** Berlaku

**Konteks:** §6.2 PRD memetakan `MGT_TP_PROVISION_ADD` (42 baris) ke
`transp_provision (kind = ADDON)` dan `MGT_TP_PROVISION_BILL_ADD` (5 baris) ke
`transp_bill (kind = ADDON)`. Kolom `kind` itu dihapus di PRD v1.7 ketika §5.6.1
menetapkan **satu induk = satu provisi** dan melarang provisi susulan. §6.2 tidak ikut
diperbarui.

**Keputusan:** 42 + 5 baris add-on dimigrasi jadi baris `transp_bill_line` bertipe
`EXPENSE_DIRECT` dengan `TBL_DIFFERENCE_TYPE = 'ADDITIONAL'`.

**Alternatif yang ditolak:** menghidupkan kembali kolom `kind`. Ditolak karena akan
membawa kembali struktur yang justru dihapus, demi 47 baris yang terakhir dipakai
Desember 2022.

**Konsekuensi:** nilai GL tidak berubah, hanya tempat barisnya. Rekonsiliasi R-03
(`SUM(tpb_total)` per vendor per bulan) tetap harus selisih 0.

**Perlu konfirmasi:** Finance — pastikan 47 baris ini tidak dirujuk laporan mana pun
dengan asumsi bentuk lamanya.

---

### D-04 — Test Transporter di `tests/`, bukan `Modules/Transporter/tests/`
**Tanggal:** 2026-09-17 · **Task:** semua · **Status:** Berlaku

**Konteks:** `CLAUDE.md` menyebut `Modules/*/tests` sebagai lokasi feature test. Semua
folder itu kosong, `Modules/LcControl` tidak punya sama sekali, dan `phpunit.xml` hanya
memindai `tests/Unit`, `tests/Feature`, `tests/Integration`.

**Keputusan:** test Transporter di `tests/Unit/Transporter/`, `tests/Feature/Transporter/`,
`tests/Integration/Transporter/`.

**Alternatif yang ditolak:** menambah `Modules/*/tests` ke `phpunit.xml`. Ditolak karena
mengubah cakupan test seluruh repo di tengah proyek ini — perubahan yang berdiri sendiri
dan pantas jadi PR-nya sendiri, bukan efek samping modul Transporter.

**Konsekuensi:** struktur test seperti `spec.md` §10. Kalau nanti `phpunit.xml` diperluas,
test bisa dipindah tanpa menulis ulang.

**Perlu konfirmasi:** —

---

### D-05 — ~~Usulan~~ (DICABUT, lihat D-09) — Satu branch untuk seluruh proyek; T012 diusulkan jadi pengecualian
**Tanggal:** 2026-09-17 · **Task:** semua, khususnya T012 · **Status:**
Bagian branch **Berlaku**; bagian T012 **Menunggu keputusan**

**Konteks:** Praktik tim adalah commit & push ke branch kapan saja, tapi **PR sekali
saja di akhir**, setelah testing selesai dan aplikasi jalan; koreksi setelah merge
ditangani lewat PR baru. Untuk modul baru yang berdiri sendiri seperti ini, itu wajar —
hampir seluruh pekerjaan ada di `Modules/Transporter/` yang tidak disentuh orang lain.

**Keputusan (berlaku):** satu branch `feat/Transporter` dari `develop`, satu PR di akhir,
**rebase ke `develop` di setiap gerbang `ACCEPTANCE.md`** (`plan.md` §Rebase).

**Konsekuensi yang sudah ditangani:**

| Konsekuensi | Penanganan |
|---|---|
| CI tidak pernah jalan — `tests.yml` hanya terpicu `pull_request` ke `main`/`develop` dan `push` ke `main`; `lint.yml` hanya `pull_request` | `preflight.sh` blocker 13/14 + warning 4 menjalankan perintah yang persis sama. Wajib tiap awal sesi |
| `develop` bergerak jauh selama 23 minggu | Rebase di 8 gerbang fase, bukan sekali di akhir |
| PR akhir berisi ~200 berkas, squash jadi satu commit — praktis tidak bisa direview baris per baris | Diterima sebagai konsekuensi yang melekat. `PROGRESS.md` dan 6 checkpoint review menggantikan fungsi review-per-PR |

**Bagian yang menunggu keputusan — T012.**

T012 memindahkan `JournalVoucherPostingService`, `EloquentJournalVoucherRepository`,
`JournalVoucherRepositoryInterface`, `JournalVoucherData`, dan `JournalVoucherLineData`
**keluar dari `Modules/LcControl` ke `Modules/Core`**. Ini satu-satunya task yang
menyentuh kode milik modul lain yang sedang aktif dikerjakan — `origin` punya
`feat/LcControl/new-journal-voucher-and-payment-posting` dan
`feat/LcControl/refactor-lc-flow-process`.

**Usulan:** kerjakan T012 sebagai potongan pekerjaan tersendiri dan PR-kan lebih dulu.

| Alasan | Rinciannya |
|---|---|
| Risiko konflik | Kalau pemindahan ini mengendap 23 minggu sementara orang lain terus mengedit `Modules/LcControl/app/Services/Erp/`, merge-nya jadi yang paling menyakitkan di seluruh proyek |
| Sudah punya entri risiko sendiri | RK-06 — *"Refactor `JournalVoucherPostingService` ke Core merusak LcControl"* |
| Cocok dengan praktik tim, bukan pengecualian terhadapnya | 5 berkas dipindah tanpa mengubah perilaku. "Testing selesai dan aplikasi jalan" untuk potongan ini = test suite hijau + dua halaman LC dibuka manual. Selesai dalam sehari, bukan 23 minggu |
| Menguntungkan orang lain | Setelah masuk, semua orang membangun di atas service `Core` yang sama — tidak ada dua versi yang harus disatukan belakangan |

**Alternatif:** biarkan T012 di dalam branch besar. Konsekuensinya bukan kegagalan,
hanya merge yang mahal di titik yang paling tidak diinginkan — dan risiko itu tumbuh
seiring lamanya branch hidup.

**Perlu konfirmasi:** —

> 🔴 **DICABUT 2026-09-17 oleh D-09.** Pertanyaannya jadi tidak relevan: tidak ada
> kode yang dipindah dari `Modules/LcControl`, jadi tidak ada yang perlu di-PR lebih
> dulu dan tidak ada risiko merge. Lihat D-09.

---

### D-06 — Hubungan biaya tambahan ↔ baris tagihan disimpan satu arah
**Tanggal:** 2026-09-17 · **Task:** T005, T007, T051 · **Status:** Berlaku —
**diputuskan Indra 2026-09-17**

**Konteks:** `spec.md` §2.3 dan §2.4 memberi kolom kepada kedua sisi hubungan yang
sama: `TAE_BILL_LINE_ID` di `transp_additional_expense`, dan
`TBL_ADDITIONAL_EXPENSE_ID` di `transp_bill_line`. Keduanya nullable FK. Ketahuan
saat T007 memasang FK-nya: pasangan itu membentuk siklus, dan `TAE_FK02` harus
ditaruh paling akhir supaya tabel lawannya sudah ada.

Dua kolom untuk satu hubungan berarti dua tempat yang bisa berbeda isi. Tidak ada
yang menjaga keduanya sinkron selain kode aplikasi, dan kalau melenceng, tidak ada
cara memutuskan mana yang benar.

**Keputusan:** **`TAE_BILL_LINE_ID` yang dipertahankan. Kolom
`TBL_ADDITIONAL_EXPENSE_ID` dan FK `TBL_FK03` dibuang.**

**Kenapa arah itu yang dipilih — bukan sekadar membuang salah satu:**

| Alasan | Rinciannya |
|---|---|
| Kardinalitasnya lebih longgar | `TAE_BILL_LINE_ID` membolehkan beberapa biaya tambahan menunjuk satu baris tagihan. `TBL_ADDITIONAL_EXPENSE_ID` mengunci satu baris ke paling banyak satu biaya. Arah yang dipilih benar di kedua pembacaan; arah sebaliknya hanya benar di salah satunya |
| State-nya memang tinggal di sisi expense | T051: *"Ditarik → status `Billed` + terkunci; dilepas → kembali `Approved`"*. Yang berubah saat penarikan adalah `TAE_STATUS`, jadi wajar penunjuknya ada di baris yang sama |
| Menghapus siklus FK | Tanpa `TBL_FK03`, tidak ada lagi pasangan FK yang saling menunjuk — urutan pemasangan dan penghapusan jadi tidak punya jebakan |

**Alternatif yang ditolak:** membiarkan kedua kolom sesuai spec, dan menjaga
sinkronisasinya di `BillMatchingService`. Ditolak karena itu memindahkan
integritas data ke kode, persis hal yang FK ada untuk mencegahnya.

**Konsekuensi:** T051 membaca dan menulis `TAE_BILL_LINE_ID` saja. Query dari arah
tagihan (*"biaya tambahan apa saja yang masuk ke baris ini"*) jadi lewat
`transp_additional_expense` — sudah terlayani index `TAE_NX01`/`TAE_NX02`, dan
`TAE_BILL_LINE_ID` mendapat index sendiri di T011 kalau profil query menuntutnya.

Migration T005 dan T007 diedit langsung, bukan ditambahi migration `drop column`.
Keduanya belum pernah dijalankan di mana pun — tabelnya belum ada di Oracle, dan
`DISABLE_MIGRATIONS` tidak relevan di sini. Larangan "jangan edit migration yang
sudah ter-deploy" berlaku untuk yang sudah jalan, dan ini belum.

**Perlu konfirmasi:** —

---

### D-07 — SysId memakai 8 digit, nomor transaksi tetap 6
**Tanggal:** 2026-09-17 · **Task:** T008 · **Status:** Berlaku

**Konteks:** `spec.md` §5 menetapkan lebar angka hanya untuk nomor transaksi —
`{YYYY}{6}`. Untuk tiga sequence SysId ia cuma menulis `{prefix}{Ymd}{n}`, tanpa
menyebut lebar `n`. Modul lain memakai `fm000000` (6 digit) untuk semuanya.

`HMMS_LAST_VALUE` tidak direset harian — yang berganti tiap hari hanya bagian
tanggalnya. Jadi 6 digit berarti **999.999 baris per sequence sampai reset
tahunan**, dan `SysIdHelper` melempar `RuntimeException` begitu batas itu lewat.

`TRANSP_TRX_SYS_ID_SEQ` memasok PK untuk seluruh tabel detail: `transp_order_dn`
saja 92.000 baris (RK-08), ditambah `transp_provision_dn`, `transp_order_cost`,
`transp_bill_line`, dan `transp_document_scan`. Migrasi P6 membangkitkan semuanya
dalam satu jendela. Totalnya masih di bawah sejuta, tapi hanya dua sampai tiga
kali lipat jaraknya dari batas — dan kalau batas itu kena, yang gagal adalah
migrasi produksi di tengah jalan.

**Keputusan:** **tiga sequence SysId memakai `fm00000000` (8 digit,
`HMMS_MAX_VALUE` 99.999.999). Lima sequence nomor transaksi tetap 6 digit**
sesuai `spec.md` §5.

**Kenapa dibedakan, bukan disamakan:** SysId adalah kunci pengganti yang tidak
pernah muncul di laporan atau di layar — lebarnya tidak berarti apa-apa bagi
pengguna, dan menaikkannya tidak berbiaya. Nomor transaksi sebaliknya: ia dibaca
Finance, dicetak, dan dicocokkan dengan dokumen lama, jadi lebarnya mengikuti
spec apa adanya. Kolomnya muat — `{Ymd}{8}` = 16 karakter di `varchar(30)`.

**Alternatif yang ditolak:** menaikkan `HMMS_MAX_VALUE` saja dan membiarkan
`HMMS_NUMBER_FORMAT` di 6 digit. Ditolak karena `str_pad()` hanya menambah, tidak
memotong — nilai ke-1.000.000 akan keluar sebagai 7 digit tanpa peringatan, dan
lebar SysId berubah di tengah jalan.

**Konsekuensi:** SysId Transporter tidak sama lebar dengan modul lain. Itu tidak
mengganggu apa pun — tidak ada kode yang mengasumsikan panjangnya — tapi perlu
diketahui saat membaca data lintas modul.

**Perlu konfirmasi:** —

---

### D-08 — Nama kolom di migration ditulis lowercase, bukan UPPERCASE
**Tanggal:** 2026-09-17 · **Task:** T002–T007, T010 · **Status:** Berlaku

**Konteks:** `spec.md` §1 dan `gap.md` R-13 menetapkan nama kolom ditulis
**UPPERCASE** di migration, mengikuti `Modules/LcControl`. T002–T007 menulis
ke-22 tabel seperti itu. Ketahuan di T010, saat test pertama yang benar-benar
membaca atribut model gagal:

```
Failed asserting that null is identical to 'M-TCA'.
```

**Sebabnya:** SQLite mengembalikan nama kolom persis seperti dideklarasikan.
Kolom yang dideklarasikan `TCA_SYS_ID` kembali sebagai `TCA_SYS_ID`, sementara
model — mengikuti `spec.md` §1 — membacanya sebagai `tca_sys_id`. Hasilnya
`null`. Diverifikasi langsung:

```
RAW KEYS:        TCA_SYS_ID,TCA_TYPE,...
lowercase read:  NULL
UPPERCASE read:  'P1'
```

Ini bukan soal satu test. **Setiap** pembacaan atribut, **setiap** `casts()`, dan
**setiap** enum cast di seluruh modul akan diam-diam bernilai null di CI —
sementara di Oracle semuanya bekerja, karena `yajra/laravel-oci8` mengembalikan
nama kolom lowercase. Artinya test suite tidak akan pernah menguji perilaku yang
sebenarnya berjalan di produksi.

Jejaknya sudah ada sebelum modul ini: `SysIdHelper::column()` punya method
pembaca dua-kasus dengan komentar panjang yang menjelaskan persis masalah ini,
dan `HM_MST_SEQUENCES` memang dideklarasikan UPPERCASE.

**Keputusan:** **seluruh nama kolom di 23 migration Transporter ditulis
lowercase.** Nama constraint dan index tetap UPPERCASE (`TCA_PK01`, `TRC_UK01`).

**Kenapa ini aman — bukan trade-off, melainkan perbaikan tanpa biaya:** grammar
oci8 meng-uppercase setiap identifier saat membungkusnya, apa pun cara ia
ditulis. Dibuktikan:

```sql
-- keduanya dideklarasikan berbeda kasus, keluarannya sama
create table "ZZ_CASE_PROBE" ( "ZZZ_LOWER_COL" varchar2(10), "ZZZ_UPPER_COL" varchar2(10) )
```

DDL Oracle sebelum dan sesudah perubahan dibandingkan baris per baris: **103
pernyataan, nol perbedaan.** Yang berubah hanya teks sumber migration; skema
Oracle yang dihasilkan identik, jadi tidak ada dampak ke migrasi data, ke report
Orion, maupun ke SQL yang ditulis tangan.

**Alternatif yang ditolak:**

| Alternatif | Kenapa tidak |
|---|---|
| Biarkan UPPERCASE, tambahkan trait penormal kasus di model | Menambah lapisan akal-akalan permanen di 21 model untuk menutupi masalah yang bisa dihapus. `SysIdHelper::column()` adalah contoh biaya perawatannya |
| Biarkan UPPERCASE, tulis test dengan atribut UPPERCASE | Test jadi menguji jalur yang tidak pernah dipakai produksi. Lebih buruk daripada tidak ada test |

**Konsekuensi:** `Modules/Finance` (CiProject, Agustus–September 2026) sudah
memakai lowercase, jadi ini menyelaraskan modul baru dengan praktik terbaru,
bukan menciptakan gaya ketiga. `spec.md` §1 dan `gap.md` R-13 dikoreksi.
`Modules/LcControl` tidak disentuh.

**Perlu konfirmasi:** — (tidak mengubah skema Oracle sama sekali)

---

### D-09 — Transporter menulis mesin posting GL-nya sendiri; LcControl tidak disentuh
**Tanggal:** 2026-09-17 · **Task:** T012, T044, T053 · **Status:** Berlaku —
**diputuskan Indra 2026-09-17**

**Konteks:** T012 semula memindahkan `JournalVoucherPostingService` dan empat berkas
pendukungnya dari `Modules/LcControl` ke `Modules/Core`, supaya dua modul memakai satu
service GL bersama (`gap.md` R-02/R-03/R-04, `design.md` §6).

Survei sebelum pemindahan menemukan penghalang yang tidak tercatat di dokumen mana pun:
`JournalVoucherPostingService` mengimpor dua kelas LcControl —
`ExchangeRateService` (baris 163) dan `LcMasterService::toOrionCurrency()`
(baris 59, 179). Memindahkannya apa adanya membuat **`Core` bergantung pada
`LcControl`**: modul dasar tanpa `requires` jadi bergantung pada modul yang
`requires: ["Finance"]`. Itu membalik graf dependensi dan melanggar aturan modul di
`CLAUDE.md`.

**Keputusan Indra:** **jangan pindahkan apa pun. Modul Transporter mendefinisikan
mesin posting GL-nya sendiri.** Alasannya: mengambil dari LcControl menimbulkan terlalu
banyak permintaan koordinasi lintas tim. **Yang dipakai bersama adalah tabelnya, bukan
kodenya** — `FT_UNPOSTED_TRANS_HEADER`, `FT_UNPOSTED_TRANS_DETAIL`, dan
`FM_TRAN_DOC_NO`. Mekanisme insert-nya milik Transporter sendiri.

**Yang berubah:**

| Semula | Jadi |
|---|---|
| 5 berkas pindah `LcControl` → `Core` | **Nol berkas dipindah.** `Modules/LcControl` tidak disentuh sama sekali |
| `Modules/Core/app/{Services,…}/Erp/` dibuat | Tidak dibuat. `Core` tidak berubah |
| `TransporterPostingService` tipis, membungkus Core (`design.md` §6) | Implementasi penuh di `Modules/Transporter/app/Services/Erp/` |
| `gap.md` R-02/R-03/R-04 — pakai ulang service, repository, dan dukungan flex LcControl | Dibaca sebagai **acuan perilaku**, bukan kode yang dipakai ulang. Tetap berguna: ia memberi tahu bentuk yang sudah terbukti jalan di produksi |
| `gap.md` R-05 — pakai ulang `ExchangeRateService` LcControl | Transporter membaca `fm_exchange_rate` lewat pembacanya sendiri |

**Apa yang dibeli dan apa yang dibayar:**

| Dibeli | Dibayar |
|---|---|
| Nol risiko RK-06 — LcControl yang sudah jalan di produksi tidak tersentuh | Mekanisme insert GL jadi ada di dua tempat |
| D-05 gugur — tidak ada yang perlu di-PR terpisah, tidak ada konflik merge selama 23 minggu | Kalau Orion mengubah struktur `FT_*`, dua tempat yang harus diperbaiki |
| Graf dependensi tetap benar; `Core` tidak berubah | — |
| Transporter bebas menyimpang dari LcControl di tempat yang memang harus berbeda — `TPJV` bukan `JV`/`JJV`, dan pengecekan idempotensi yang LcControl tidak punya | — |

**Yang TIDAK berubah karena keputusan ini:** angka yang masuk GL tetap harus identik
dengan sistem lama — itu kriteria go/no-go (`ACCEPTANCE.md` §P3). Implementasi sendiri
bukan izin untuk perilaku sendiri. Konstanta `COMP_CODE='002'`, `DIVN_CODE='001'`,
`HEAD_NO_1=1`, `HEAD_NO_2=2`, urutan ambil-periode → kunci `FM_TRAN_DOC_NO` → insert
header → insert detail → majukan `tdoc_cur_no`, dan validasi balance DR=CR dalam nilai
USD: semuanya mengikuti bentuk yang sudah terbukti di `EloquentJournalVoucherRepository`.
`FT_TXN_AUTH` tetap tidak ditulis (D-01).

**Alternatif yang ditolak:** mengangkat `ExchangeRateService` + helper mata uang ke
`Core` bersama service-nya. Secara arsitektur paling rapi, tapi menyentuh `LcControl`
lebih luas daripada yang diizinkan `README.md`, dan tetap memaksa koordinasi lintas tim
yang justru ingin dihindari.

**Perlu konfirmasi:** —

---

### D-10 — Kunci idempotensi: `TBILL-` untuk tagihan, `TPROV-` untuk provisi
**Tanggal:** 2026-09-17 · **Task:** T012, T044, T053 · **Status:** Berlaku —
**`TPROV-` menunggu konfirmasi Indra**

**Konteks:** `assertNotAlreadyPosted()` mencocokkan `TRIM(th_flex_10)`. Survei terhadap
produksi menemukan kolom itu **sudah dipakai sistem transporter lama**: 412 dari 412
baris `TJV` di `FT_CUR_TRANS_HEADER` terisi, formatnya `TBILL-{nomor tagihan}`.
`spec.md` §7 ternyata mendokumentasikan yang sudah berjalan, bukan mengusulkan sesuatu
yang baru.

**Keputusan:**

| Jalur | `th_flex_10` = `tpl_idempotency_key` |
|---|---|
| Tagihan (`TJV`) | **`TBILL-{TRB_TRX_NO}`** — format sistem lama, **tidak diubah** |
| Provisi (`TPJV`) | **`TPROV-{nomor provisi}`** — baru; `TPJV` masih nol baris di ketiga tabel |

**Kenapa format lama dipertahankan, bukan diseragamkan:** memakai ejaan yang sama berarti
pengecekan idempotensi modul baru **ikut menangkap dokumen yang diposting sistem lama**.
Tanpa itu ada jendela buta persis di masa cutover — saat dua sistem hidup berdampingan
dan justru paling rawan posting ganda. Keseragaman kosmetik tidak sebanding dengan itu.

`TRB_TRX_NO` sendiri sudah berformat `TBILL-{YYYY}{6}` (`spec.md` §5), jadi keduanya
bertemu di nilai yang sama; named constructor DTO-nya idempoten terhadap prefix sehingga
`TBILL-TBILL-…` tidak mungkin lahir dari arah mana pun.

**Yang perlu dikonfirmasi:** ejaan `TPROV-` dipilih agen, bukan diambil dari dokumen mana
pun. Sekali dipakai ia masuk ke data GL dan sulit diubah — ganti sekarang kalau Finance
punya konvensi lain. `TBILL-` tidak bisa diganti.

**Konsekuensi:** panjang efektif dibatasi `tpl_idempotency_key varchar(80)`, bukan
`TH_FLEX_10 VARCHAR2(240)`. `TBILL-2026000412` = 16 karakter — lapang.

**Perlu konfirmasi:** Indra — hanya soal ejaan `TPROV-`.

---

<!-- Entri baru ditambahkan di bawah ini. Jangan menyisipkan di tengah — urutan kronologis. -->

---

### D-11 — Nama permission seragam `-`, dua role operasional diberi awalan modul
**Tanggal:** 2026-09-22 · **Task:** T014 · **Status:** Berlaku — dikonfirmasi Indra

**Konteks:** T014 menyalin 25 permission dari `spec.md` §8 apa adanya, termasuk
ketidakkonsistenannya: 16 nama memakai `-`, sembilan memakai `.`
(`transporter.approve-transaction`, `.override-cost`, `.pull-grn`,
`.approve-additional-expense`, `.pull-additional-expense`, `.post-jv`, `.post-tjv`,
`.waive-document`, `.post-payment`). Seluruh modul lain di repo seragam `-`.
Dicatat sebagai C-13 dan diputuskan sebelum P1 mulai membagikannya.

**Keputusan:**

| | Sebelum | Sesudah |
|---|---|---|
| Sembilan permission | `transporter.post-jv` dst | `transporter-post-jv` dst |
| Dua role operasional | `Despatch`, `Stores` | `Transporter - Despatch`, `Transporter - Stores` |
| Pagar role di route | 5 nama (`spec.md` §8) | 7 nama — dua di atas ditambahkan |

**Kenapa diseragamkan sekarang:** titik itu tidak berarti apa-apa bagi Spatie — nama
permission diperlakukan sebagai string biasa — jadi tidak ada yang hilang dengan
menggantinya. Biayanya sekarang satu seeder, satu migration, satu test, dan belum ada
satu pun konsumen. Biayanya setelah P1 berjalan di produksi: setiap baris
`model_has_permissions` yang terlanjur melekat harus dipindahkan, dan yang terlewat
menjadi halaman yang **diam-diam tidak bisa dibuka** — gagal senyap, yang paling mahal
dicari.

**Kenapa dua role masuk pagar:** keduanya dibuat di T014 dan memegang permission halaman
(`Despatch` → order create/edit/delete + expense create; `Stores` → pull GRN). Tanpa
masuk pagar, pagar role menolak mereka sebelum permission itu sempat diperiksa — mereka
memegang kunci ruangan yang pintunya dikunci untuk mereka. Pilihannya bukan "ikut spec
atau menyimpang", melainkan memperluas pagar atau mencabut permission yang task yang sama
perintahkan memberikan.

**Kenapa diberi awalan modul:** pola repo (`Lc Control - Admin`, `CI Project - Viewer`).
`Stores` polos cepat atau lambat bertabrakan dengan modul gudang yang menginginkan kata
yang sama; sesudah melekat ke user di produksi, tidak lagi murah diganti.

**Dijaga oleh:** `it names every permission the way the rest of the repo does, with no dots`
di `MenuRouteTest` — diverifikasi menggigit dengan sengaja mengembalikan satu titik.
`spec.md` §8 dikoreksi di perubahan yang sama, jadi dokumen tidak tertinggal dari kode.

---

### D-12 — Kalkulator meniru pembulatan yang berlaku sekarang, bukan yang ditinggalkan
**Tanggal:** 2026-09-23 · **Task:** T015, T068 · **Status:** Berlaku — dikonfirmasi Indra 2026-09-23

**Konteks:** menguji `YarnRateCalculator` terhadap `MGT_TRANSP_DETAIL_COST` produksi
mengungkap bahwa sistem lama **mengubah cara membulatkan** di pertengahan 2024. Pada
baris `Q` yang hasil kalinya bukan bilangan bulat:

| Periode | Tersimpan penuh | Dibulatkan |
|---|---|---|
| s.d. Juli 2024 | 100% | 0% |
| Agustus 2024 | 18 | 5 |
| Sept 2024 – Agt 2026 | 10 | 1.396 |

Contoh nyata: `MTDC_SYS_ID` 6466 menyimpan `584158,548` untuk 2440,4 × 239,37.

**Keputusan:** kalkulator membulatkan (`ROUND()`, setengah menjauhi nol — sama dengan
Oracle), yaitu perilaku mayoritas yang berlaku sekarang. Nilai pecahan tidak
dipertahankan.

**Konsekuensi:** provisi ulang atas data sebelum Agustus 2024 akan berbeda dari angka
GL lama, **selalu di bawah 1 rupiah per baris** dan selalu ke arah yang sama. Selisih ini
harus diketahui saat rekonsiliasi R-12, bukan ditemukan di sana.

**Diputuskan Indra (2026-09-23): nilai lama dipertahankan saat migrasi.** Sepuluh baris
era 2025–2026 (user `DES5` dan `DESH1`) yang menyimpan hasil kali penuh — begitu pula
seluruh baris pra-Agustus 2024 — **tidak dihitung ulang**. T068 menyalin
`MTDC_TOTAL_RATE` apa adanya ke `transp_order_cost`, pecahannya ikut. Kalkulator hanya
dipakai untuk transaksi yang dibuat di sistem baru.

Akibatnya tidak ada pergeseran apa pun pada data historis, dan tidak perlu persetujuan
Finance atas selisih pecahan. Yang harus dijaga: **tidak ada jalur di sistem baru yang
menghitung ulang biaya order legacy** (`TRO_IS_LEGACY = 1`) — provisi dan tagihan atas
order lama membaca biaya tersimpan, bukan memanggil kalkulator.

**Dijaga oleh:** `YarnRateCalculatorTest` — transaksi yang menyimpan hasil kali penuh
ditandai `stores_raw_product` di fixture, dihitung terpisah dari yang cocok persis, dan
selisihnya dipaku `< 1.0`. Jumlahnya (`1` dari 500) juga dipaku, sehingga fixture yang
diperbarui dengan komposisi berbeda akan menggagalkan test alih-alih lolos diam-diam.

---

### D-13 — Uji reproduksi memakai tarif dari baris historisnya, bukan dari master tarif
**Tanggal:** 2026-09-23 · **Task:** T015 · **Status:** Berlaku

**Konteks:** acceptance T015 meminta 500 transaksi historis direproduksi dengan selisih 0.
Percobaan pertama memakai tarif dari `MGT_TRANSP_RATE` dan hanya 27% yang cocok. Sebabnya
bukan kalkulatornya: **`MGT_TRANSP_RATE` tidak berversi tanggal** — ia menyimpan satu baris
per kombinasi, ditimpa setiap kali harga berubah. Dari 32.604 transaksi historis, **22.307
(68%)** punya tarif yang sudah berbeda dari yang tersimpan hari ini.

**Keputusan:** uji reproduksi mengambil nilai tarif dari baris `MGT_TRANSP_DETAIL_COST`
transaksinya sendiri, dan menguji **algoritmanya** — baris mana yang berlaku, berapa
kuantitas tiap baris, bagaimana perkalian dibulatkan.

**Kenapa ini bukan melonggarkan test:** membandingkan tarif hari ini dengan tarif tahun
lalu tidak menguji kalkulator, ia hanya membuktikan harga naik. Yang bisa salah di
kalkulator — pemilihan baris, aturan kelebihan muatan, sumber kuantitas baris `W`,
pembulatan — semuanya tetap diuji penuh, dan justru di situlah dua penemuan lain lahir
(kuantitas baris `W` adalah kapasitas truk, bukan berat muatan).

**Konsekuensi:** justru inilah pembenaran `TRC_VALID_FROM`/`TRC_VALID_TO` (F-01.5) dan
`RateCardResolver`. Angka 68% itu ukuran seberapa besar masalah yang diperbaiki, dan
layak dikutip saat menjelaskan kenapa kartu tarif berversi tanggal bukan kemewahan.

---

### D-14 — Sumbu matriks tarif chip: baris dari rate card, kolom dari rate card + GRN
**Tanggal:** 2026-09-24 · **Task:** T019 · **Status:** Berlaku

**Konteks:** F-01.14 meminta matriks vendor angkutan × vendor chip tetapi tidak menyebut
dari mana kedua sumbunya diambil. Pilihan ini menentukan apakah "sel kosong" benar-benar
berarti *tarif yang dibutuhkan tapi belum ada*.

**Keputusan:**
- **Baris** = pengangkut aktif yang punya minimal satu rate card `CHIP` (versi apa pun),
  dengan sakelar "tampilkan semua pengangkut aktif". Satu dari ± 50 pengangkut yang
  tidak pernah mengangkut chip tidak dijadikan 7 sel merah.
- **Kolom** = gabungan `TRC_CHIP_VENDOR_CODE` dari rate card `CHIP` **dan**
  `GH_SUPP_CODE` dari GRN chip (`chipReceipts()`) dalam 12 bulan terakhir
  (`transporter.chip.matrix_grn_lookback_months`). Vendor yang baru mulai mengirim chip
  langsung muncul sebagai kolom kosong **sebelum** generate pertamanya gagal — itu inti
  F-01.14. Vendor chip yang terakhir mengirim 2020 tidak memenuhi layar.
- **Sel** dinilai pada satu tanggal (default hari ini): *tersedia* = ada kartu aktif yang
  berlaku pada tanggal itu, per jenis truk; *belum aktif* = hanya ada kartu nonaktif /
  belum berlaku; *kosong* = tidak ada kartu sama sekali.
- **Pencocokan murni kode** (`TCA_SYS_ID` × `SUPP_CODE`). Nama dari `OM_SUPPLIER` hanya
  untuk label. **GRN tidak dipakai untuk menentukan pengangkut** — satu-satunya penunjuk
  pengangkut di GRN adalah `GH_FLEX_05`, yang berisi **nama** (T-25), jadi memakainya
  justru mengulang bug yang F-01.15 perbaiki.

**Alternatif yang ditolak:** kolom dari rate card saja (vendor baru tidak pernah tampak
kosong — matriks tidak menjawab pertanyaannya); baris dari GRN (butuh `GH_FLEX_05` = nama).

**Konsekuensi:** sel kosong membuka form rate card lewat query string
(`?create=1&carrier=…&service=CHIP&chip_vendor=…`), jadi `RateCardPage` belajar membuka
form terisi awal dari URL. Jenis truk tidak diisi awal — itu pilihan user.

**Perlu konfirmasi:** —

---

### D-15 — Duplikasi rate card: salinan mengikuti status aktif sumbernya
**Tanggal:** 2026-09-24 · **Task:** T020 · **Status:** Berlaku

**Konteks:** F-01.10 hanya berkata "salin satu rate card ke destinasi/jenis truk lain".
Acceptance T020 menuntut salinan melewati cek tumpang tindih T017. Padahal sejak T018
kartu baru lahir **nonaktif**, dan F-01.7 hanya memeriksa kartu **aktif** — salinan
nonaktif tidak akan pernah dicek saat disalin, sehingga acceptance-nya kosong.

**Keputusan:**
- Salinan **aktif bila sumbernya aktif**, nonaktif bila sumbernya nonaktif. Karena
  rate line ikut tersalin, salinan aktif langsung memenuhi F-01.8 — dicek atas baris
  yang akan tersalin, sama dengan Naikkan tarif.
- Cek tumpang tindih F-01.7 **selalu** dijalankan atas kombinasi tujuan, apa pun status
  salinannya. Kartu tujuan yang bentrok ditolak dengan pesan yang menyebut kartu lawannya.
- Kombinasi yang **sama dengan sumber** ditolak — itu Naikkan tarif, bukan duplikasi.
- Tabrakan `TRC_UK01` (kombinasi + `VALID_FROM` sama dengan kartu apa pun, termasuk
  nonaktif) ditolak dengan pesan jelas sebelum sampai ke DB.
- Yang boleh diganti: destinasi (Despatch) / vendor chip (Chip), jenis truk, kapasitas
  truk, `VALID_FROM`, `VALID_TO`. Pengangkut dan jenis jasa **tetap**. Default semua
  kolom = nilai sumber. Kapasitas bisa diganti karena baris `W` dikalikan kapasitas truk
  (T015); jenis truk lain biasanya berkapasitas lain.
- Baris tarif disalin apa adanya (prioritas, tipe, batas muatan, overflow, tarif) —
  `trl_max_cap` tidak disesuaikan otomatis ke kapasitas baru; itu disunting di editor.
- Sumber tidak diubah sama sekali, jadi kartu yang sudah terpakai transaksi boleh disalin.

**Alternatif yang ditolak:** salinan selalu nonaktif (acceptance tidak teruji; cek
tumpang tindih terjadi belakangan saat aktivasi); menyalin ke banyak tujuan sekaligus
(di luar F-01.10; bisa diulang satu per satu).

**Konsekuensi:** tidak ada angka GL yang berubah — tarif disalin, tidak dihitung.

**Perlu konfirmasi:** —

---

### D-16 — Seeder jenis biaya: sisip yang belum ada, jangan timpa suntingan user
**Tanggal:** 2026-09-24 · **Task:** T021, T068 · **Status:** Berlaku — dikonfirmasi Indra 2026-09-24

**Konteks:** `spec.md` §2.1 menyebut enam kode seed tapi tidak menyebut `TCT_REQUIRES_ATTACHMENT`
per jenis, dan tidak menyebut bagaimana seeder berperilaku terhadap baris yang sudah
disunting Finance. Data lama `MGT_TRANSP_DETAIL_OTHCHG.MTDO_OTHCHG_ID` (dicek 2026-09-24):
`NULL` 472, `BIAYA TOL` 174, `JLNDITUTUP` 85, `INAP` 75, **`ALAMAT` 55**, `KAWAL` 1.
`SOLAR` tidak pernah muncul.

**Keputusan:**
- Seeder dikunci pada `TCT_CODE`. Kode yang belum ada disisipkan (PK lewat
  `SysIdHelper`, `TRANSP_MASTER_SYS_ID_SEQ`); kode yang sudah ada **tidak diubah sama sekali**
  — nama, lampiran wajib, dan status aktif adalah milik halaman master begitu barisnya ada.
  Menjalankannya dua kali = nol perubahan. Pola yang sama dengan migration menu (kunci `code`).
- `TCT_LEGACY_ID` diisi nilai `MTDO_OTHCHG_ID` lama agar T068/C-23 memetakan lewat kolom,
  bukan tabel di kepala: `TOL`←`BIAYA TOL`, `JALAN_DITUTUP`←`JLNDITUTUP`, `INAP`←`INAP`,
  `KAWAL`←`KAWAL`. `AMBIL_BARANG` dan `SOLAR` kosong (padanan lamanya NULL / tidak ada).
- `TCT_REQUIRES_ATTACHMENT` = **false** untuk keenamnya. PRD tidak mewajibkan; menyalakannya
  cukup satu klik di halaman master, sedangkan salah menyalakan akan memblokir entri biaya.
- Jenis biaya yang dirujuk `transp_additional_expense_line` tidak bisa dihapus (dinonaktifkan
  saja) dan **kodenya dibekukan** — kode dipakai sebagai kunci seeder dan pemetaan migrasi.

**Alternatif yang ditolak:** `updateOrCreate` pada setiap run (menimpa suntingan Finance
setiap deploy); menambah jenis ke-7 `ALAMAT` diam-diam (di luar F-04.1).

**`ALAMAT` (55 baris lama) tidak punya padanan** di enam jenis F-04.1.
**Diputuskan Indra (2026-09-24): jadi jenis baru.** Seeder berisi **tujuh** baris — enam
F-04.1 ditambah `ALAMAT` (nama "Alamat", `TCT_LEGACY_ID` = `ALAMAT`). Ini menambah daftar
F-04.1; PRD perlu dikoreksi di revisi berikutnya. T068 memetakan ke-55 baris itu lewat
`TCT_LEGACY_ID` seperti yang lain, tanpa exception.

**Perlu konfirmasi:** —

---

### D-17 — Seeder kategori jasa: pola D-16, dan 150 baris `OTHERS` tanpa kategori
**Tanggal:** 2026-09-24 · **Task:** T022, T068 · **Status:** Berlaku — dikonfirmasi Indra 2026-09-24

**Konteks:** F-02.12 menyebut empat kategori (Ambil Barang, Retur Benang, Pallet, Trucking ke
Bandara). C-06 menyebut **328** baris `TP_NO` tanpa tanda hubung yang jadi `TPSVC`. Dicek di
`MGT_TP_PROVISION` (2026-09-24): `PALLET` 132, `AMBIL BARANG` 24, `RETUR BENANG` 22 —
jumlahnya 178; **150 sisanya `OTHERS`**, yang tidak punya padanan di F-02.12.

**Keputusan:**
- Aturan seeder dan halaman **sama persis dengan D-16**: kunci `TSC_CODE`, sisip yang belum
  ada, tidak pernah menimpa; kategori yang dirujuk `transp_order` tidak bisa dihapus dan
  kodenya dibekukan.
- `TSC_LEGACY_ID` memuat teks `TP_NO` lama: `AMBIL_BARANG`←`AMBIL BARANG`,
  `RETUR_BENANG`←`RETUR BENANG`, `PALLET`←`PALLET`. `TRUCKING_BANDARA` kosong (kategori baru).

**`OTHERS` (150 baris)** tidak punya padanan di F-02.12. **Diputuskan Indra (2026-09-24):
jadi kategori baru.** Seeder berisi **lima** baris — empat F-02.12 ditambah `OTHERS` (nama
"Lain-lain", `TSC_LEGACY_ID` = `OTHERS`). Ini menambah daftar F-02.12; PRD perlu dikoreksi.
Ke-328 baris C-06 seluruhnya terpetakan lewat `TSC_LEGACY_ID`, tanpa exception.

**Perlu konfirmasi:** —

---

### D-18 — Seed akun posting mengikuti jurnal Orion yang benar-benar terposting, bukan Appendix B
**Tanggal:** 2026-09-24 · **Task:** T023 · **Status:** Berlaku — **menunggu konfirmasi Finance**
(arah disetujui Indra 2026-09-24; nomor akun wajib dikonfirmasi Finance sebelum go-live)

**Konteks:** `spec.md` §2.1 (dari PRD Appendix B) men-seed `BILL_REVERSAL` DESPATCH = Dr `404001`
dan CHIP = Dr `401001`. Dibandingkan dengan GL Orion (`FT_CUR_` + `FT_PRV_TRANS_DETAIL`,
dicek 2026-09-24):

| Jurnal | Akun · sisi | Baris | Arti |
|---|---|---|---|
| `JV` provisi | `404001` D · `208027` C | 3.168 · 17.777 | beban yarn / hutang provisi yarn |
| `JV` provisi | `401001` D · `208026` C | 1.856 · 4.529 | beban chip / hutang provisi chip |
| `TJV` tagihan | **`208027` D** | **18.712** | **menutup hutang provisi yarn** |
| `TJV` tagihan | **`208026` D** | **4.971** | **menutup hutang provisi chip** |
| `TJV` tagihan | `203001` C · `206005` C · `108004`/`108005` D | — | AP, PPh, PPN — cocok dengan seed |
| `TJV` tagihan | `401009` D · `401011` D · `401001` D · `404001` D | 262 · 91 · 272 · 293 | beban langsung jasa tanpa provisi (TPSVC) |

Sistem lama mendebit **akun hutang provisi** saat tagihan, bukan akun beban. Seed spec akan
membebankan biaya angkut **dua kali** dan tidak pernah menutup `208027`/`208026`.
Jasa tanpa surat jalan (`TP_NO` = `PALLET`/`RETUR BENANG`/`AMBIL BARANG`/`OTHERS`) **tidak
diprovisi** (`TP_JV_NO = 'NON TPDN'`) dan langsung dibebankan saat TJV ke akun per kategori
(`MGT_TP_PROVISION.TP_MAIN_ACNT`): `PALLET` `401009` (132), `RETUR BENANG` `401011` (19),
`AMBIL BARANG` `401001` (24), `OTHERS` `401001` s.d. Jan 2024 (101) lalu `404001` sejak Feb 2024 (12).

**Keputusan:**
- `BILL_REVERSAL` DESPATCH = **Dr `208027`**, CHIP = **Dr `208026`**.
- Jenis posting baru **`BILL_EXPENSE_SERVICE`** (beban langsung `TPSVC`), dibedakan per kategori
  jasa lewat kolom baru **`TPA_SERVICE_CATEGORY_CODE`** (nullable, → `TSC_CODE`):
  `PALLET` Dr `401009`, `RETUR_BENANG` Dr `401011`, `AMBIL_BARANG` Dr `401001`,
  `OTHERS` Dr `404001`; ditambah satu baris tanpa kategori Dr `404001` sebagai jaring
  pengaman untuk kategori baru (`TRUCKING_BANDARA`, dan yang ditambah lewat master).
  Semua `VALID_FROM` = `2020-01-01`. Riwayat `OTHERS` → `401001` sebelum Feb 2024 **tidak**
  di-seed: tidak ada transaksi baru bertanggal lampau, dan data historis dimigrasi dengan
  jurnal yang sudah ada (D-12).
- Pencari akun: kategori persis → jenis jasa persis → baris umum, selalu pada **tanggal dokumen**.
- Seeder mengikuti pola D-16/D-17: kunci (`POSTING_TYPE`, `SERVICE_TYPE`, `SERVICE_CATEGORY_CODE`,
  `VALID_FROM`), sisip yang belum ada, tidak pernah menimpa.
- Setiap baris seed dan halaman master menandai **"Nomor & nama akun menunggu konfirmasi Finance"**.

**Alternatif yang ditolak:** mengikuti spec apa adanya (beban ganda); menyimpan akun per
kategori di `transp_service_category` (mencampur master jasa dengan bagan akun, dan tidak berversi).

**Konsekuensi:** migration kolom baru `TPA_SERVICE_CATEGORY_CODE`; `spec.md` §2.1 dan PRD
Appendix B perlu dikoreksi; T043 (preview TPJV vs FT_*) dan T053 (TJV) memakai tabel ini.

**Perlu konfirmasi:** **Finance** — seluruh nomor akun di atas, terutama `BILL_REVERSAL`
dan akun beban per kategori `TPSVC`. Sampai dikonfirmasi, gerbang P3/P4 di `ACCEPTANCE.md`
tidak boleh dicentang.

---

### D-19 — Seed jenis dokumen: pola folder relatif, placeholder `{year}`/`{number}`
**Tanggal:** 2026-09-24 · **Task:** T024 · **Status:** Berlaku

**Konteks:** `spec.md` §2.1 memberi kolom `TDT_FILE_PREFIX` dan `TDT_FOLDER_PATTERN` tapi tidak
isinya. Legacy (`LEGACY_REFERENCE.md` §Integrasi e-Filling): berkas diberi nama
`{TYPE}-{nomor}.pdf` dan dipindah ke `Doc_Folder/{TYPE}/{TAHUN}/`. Akar `Doc_Folder` sudah
jadi konfigurasi (`transporter.document_scan.root`, F-10.3c) dan berbeda antar lingkungan.

**Keputusan:**
- `TDT_FILE_PREFIX` = kode jenis (`LDN`, `PDN`, `JWDN`).
- `TDT_FOLDER_PATTERN` = **relatif terhadap akar**, `{prefix}/{year}` — akar tidak disimpan di
  data supaya satu seed berlaku di staging dan produksi. Placeholder yang dikenal hanya
  `{prefix}` dan `{year}`; yang lain ditolak saat simpan, dan pola absolut (diawali `/`) atau
  berisi `..` ditolak (job T055 membaca mount read-only; pola tidak boleh keluar dari akar).
- Seed tiga baris, semua `TDT_IS_CONTROLLED = 1`, aktif. `CHPGRN` tidak diseed (F-10.6), dan
  menambahkan `CHPGRN` lewat halaman master **ditolak** — F-10.6 adalah aturan bisnis, bukan
  default yang bisa diubah lewat data.
- Pola seeder D-16: kunci `TDT_CODE`, sisip yang belum ada, tidak pernah menimpa.
- Hapus: diizinkan hanya bila belum ada `transp_document_scan` dengan `TDC_DN_TXN_CODE` = kode
  itu; selain itu nonaktifkan / matikan kontrolnya. Kode dibekukan dengan aturan yang sama.

**Alternatif yang ditolak:** menyimpan path absolut per baris (harus disunting ulang tiap
lingkungan dan rawan keluar dari mount).

**Perlu konfirmasi:** —

---

### D-20 — Pengangkut GRN chip lewat alias nama eksplisit (`transp_carrier_alias`), tabel ke-23
**Tanggal:** 2026-09-24 · **Task:** T025, T037, T038, T067 · **Status:** Berlaku — disetujui Indra 2026-09-24

**Konteks:** F-01.15 mengandaikan GRN chip dicocokkan ke rate card lewat **kode** vendor.
Nyatanya `OT_GR_HEAD` **tidak menyimpan kode pengangkut**: satu-satunya penunjuk adalah
`GH_FLEX_05`, teks nama yang diketik stores (`GH_SUPP_CODE` = vendor chip). Dari 1.840 GRN
chip sejak 2024: nama persis di `OM_SUPPLIER` hanya **815 (44%)** ("PUTRA JAYA TRANSINDO" vs
"PUTRA JAYA TRANSINDO, PT."); nama persis di `MGT_TRANSP_MASTER` **1.529 (83%)**, karena master
lama menyimpan beberapa ejaan untuk satu kode ("DWI KARYA" & "DWIKARYA" → `LS00481`).
TRNSP005 lama mencocokkan `MTM_TRANSP_NAME = GH_FLEX_05` dan `MTM_CUST_SUPP = GH_SUPP_CODE`.

**Keputusan:**
- Tabel baru **`transp_carrier_alias`** (`TCL_`, schema `MGTHRIS` — tabel modul ke-23):
  `TCL_SYS_ID` PK, `TCL_ALIAS` varchar(240) — teks `GH_FLEX_05` dinormalisasi
  (trim, huruf besar, spasi ganda dirapatkan), **UNIQUE**; `TCL_CARRIER_ID` FK → `transp_carrier`;
  `TCL_SOURCE` varchar(10) `MIGRATION` / `MANUAL`; `TCL_IS_ACTIVE`; legacy id + 4 kolom audit.
- Pencocokan **persis** atas teks ternormalisasi. **Tidak ada fuzzy match**: nama hanya
  diterjemahkan lewat pemetaan yang dibuat manusia, tercatat, dan bisa diaudit. Sesudah alias
  memberi `TCA_SYS_ID`, rate card dicari murni lewat kode (carrier × `GH_SUPP_CODE` × jenis truk).
- **T025 = "Monitoring GRN Chip"**: GRN `CHPGRN` sejak 1 April 2021 yang belum punya transaksi
  angkutan dan belum siap, dikelompokkan per **nama pengangkut × vendor chip**, status:
  `ALIAS BELUM ADA` (nama belum dipetakan) · `TARIF BELUM ADA` (pengangkut dikenal, rate card
  CHIP untuk vendor chip itu belum ada) · `GRN BELUM APPROVE` (`GH_APPR_STATUS` ≠ 3).
  GRN yang aliasnya ada dan tarifnya ada tidak tampil (ia milik T037/T038).
  Aksi F-07.2 dalam **satu transaksi**: petakan nama ke pengangkut yang ada **atau** buat pengangkut
  `VENDOR` baru (kode supplier dipilih user), lalu — bila belum ada — rate card `CHIP`
  (pengangkut × vendor chip × `TRAILER`, kapasitas 20.000, `VALID_FROM` = tanggal GRN tertua di
  kelompok, **aktif**) + satu rate line `Q` prioritas 1 dengan tarif yang **diisi user**
  (tidak ada tarif default). Semua aturan T016/T017/T018 dilewati lewat service yang sama.
- Filter `GH_SUPP_CODE` tidak berawalan `IS` (impor) dipertahankan dari TRNSP005, begitu pula
  `GH_TXN_CODE = 'CHPGRN'`. Ref `JPO` **tidak** disaring di sini — halaman ini menampilkan
  apa yang perlu disetup; F-09.1 (hanya `JPO`) berlaku di halaman generate.
- **T067 (M-1)** mengisi alias dari pasangan (`MTM_TRANSP_NAME`, `MTM_TRANSP_CODE`) `MTM_TYPE = 'STO'`,
  `TCL_SOURCE = 'MIGRATION'`. Nama yang menunjuk lebih dari satu kode masuk exception.
- Halaman manajemen alias (lihat, nonaktifkan, pindahkan) menyatu di halaman monitoring ini;
  tanpa route baru.

**Alternatif yang ditolak:** meminta Orion menambah kode pengangkut di GRN (di luar kendali
modul, tidak menolong data historis); user memilih pengangkut setiap kali generate (rawan
salah, tidak tercatat); fuzzy match nama (mengulang T-25 dengan cara lain).

**Konsekuensi:** tabel ke-23 — `spec.md` §0/§2, `design.md`, `plan.md`, `gap.md` M-02,
`ACCEPTANCE.md` P0-1, dan `SchemaTest` yang menyebut "22" diperbarui. F-01.15 PRD perlu
dikoreksi: pencocokan rate card memakai kode, **penentuan pengangkut GRN** memakai alias.

**Perlu konfirmasi:** —
