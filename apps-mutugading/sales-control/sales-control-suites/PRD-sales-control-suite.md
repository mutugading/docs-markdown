# PRD — Sales Control Suite

**Owner:** Indra Kurniawan (IT Lead)
**Developer:** Mike
**Repo:** `mutugading/apps-mutugading` (Laravel 12, module `Finance`)
**Schema:** `MGTDAT` (Oracle 11g)
**Status:** Phase 1 siap dikerjakan. Phase 2 dan 3 outline saja.

---

## 1. Latar belakang

Tiga kontrol di ERP Orion memblokir transaksi berdasarkan kebijakan manajemen. Ketiganya sudah berjalan, tapi **mekanisme pengecualiannya belum tercatat**:

| Kontrol | Cara bypass sekarang | Masalahnya |
|---|---|---|
| Harga minimum | Belum ada kontrolnya | — |
| Overdue delivery | `OM_CUSTOMER.CUST_FLEX_18 = 'Y'` | Flag permanen per customer, tanpa jejak dan tanpa masa berlaku |
| Credit limit | Naikkan `OM_CUST_COMP.CCO_CREDIT_LIMIT` | Kenaikan sementara jadi permanen karena tidak pernah dikembalikan |

Aplikasi ini menyediakan **satu alur pengajuan-persetujuan bersama** untuk ketiganya: request → cetak formulir → tanda tangan BOD → upload → aktif.

**Non-goal:** kontrol CBD (`ODBTRG_CBD_CONT_VAL_MGT`) tidak masuk. Itu kontrol saldo, bukan kebijakan, dan mekanisme pengecualiannya sudah ada lewat transfer saldo antar kontrak (`MGT_SO_CBD_TRF_HEAD`).

---

## 2. Prinsip desain

**Yang dibagi bersama** — alur kerja dan tabel pengajuan:
nomor + revisi, kunci setelah cetak, upload dokumen, status, masa berlaku, log, laporan.

**Yang tetap terpisah** — cara pemeriksaan tiap kontrol:

| Modul | Pengecualian melekat pada | Nilai yang diikat | Sifat |
|---|---|---|---|
| Harga minimum | Baris SO (`SOI_SYS_ID`) | Rate + qty | Sekali pakai |
| Overdue | Customer | — | Jendela waktu |
| Credit limit | Customer | Nominal tambahan | Nominal + waktu |

**JANGAN** membuat rules engine generik yang menyatukan ketiga pemeriksaan. Ketiganya berbeda secara mendasar; menyatukannya hanya menambah abstraksi tanpa menghemat apa pun. Yang generik hanya tabel pengajuan dan UI-nya.

---

## 3. Skema database

Semua di `MGTDAT`. Prefix kolom mengikuti konvensi (lihat registry).

```
PREFIX REGISTRY (tambahan)
MAR_   → MGT_APPROVAL_REQUEST
MAL_   → MGT_APPROVAL_LINE
MMP_   → MGT_MIN_PRICE
MPCL_  → MGT_PRICE_CHECK_LOG
```

DDL lengkap ada di `minimum-price-control.sql`.

### 3.1 MGT_APPROVAL_REQUEST — dipakai ketiga modul

Kolom kunci:

| Kolom | Isi |
|---|---|
| `MAR_REQ_NO` | `MPO-2026-0001` (min price), `MPL-…` (price list), `MOV-…` (overdue), `MCL-…` (credit limit) |
| `MAR_REVISION` | Naik tiap kali direvisi setelah dicetak |
| `MAR_CTRL_TYPE` | `PRICELIST` \| `MINPRICE` \| `OVERDUE` \| `CRLIMIT` |
| `MAR_STATUS` | `DRAFT` → `PRINTED` → `APPROVED` / `REJECTED` / `CANCELLED` / `VOID` |
| `MAR_VALID_FROM/TO` | Masa berlaku pengecualian |
| `MAR_AMOUNT` | Hanya untuk `CRLIMIT` (nominal tambahan) |
| `MAR_BOD_DOC_NO/DT/SIGNER` | Identitas dokumen kertas |
| `MAR_ATTACH_PATH/HASH` | File scan; simpan path + hash, bukan blob |
| `MAR_PRINT_COUNT` | Berapa kali dicetak — angka tinggi = sinyal |

### 3.2 MGT_APPROVAL_LINE — baris pengecualian

Phase 1 hanya dipakai `MINPRICE`. `MAL_APPROVED_RATE` dan `MAL_APPROVED_QTY_BU` adalah pengikatnya: kalau rate di SO berubah atau qty naik melebihi ini, pengecualian tidak cocok lagi.

### 3.3 MGT_MIN_PRICE — master harga minimum

Resolusi wildcard. Tiga kolom kunci bisa berisi nilai spesifik atau `*`:

| Pola | `SCOPE_LEVEL` | `SCOPE_VALUE` | `GRADE_1` | `GRADE_2` |
|---|---|---|---|---|
| Item + grade1 + grade2 | `ITEM` | kode item | `A` | `SD` |
| Item + grade2 | `ITEM` | kode item | `*` | `SD` |
| Item saja | `ITEM` | kode item | `*` | `*` |
| Group | `GROUP` | kode group | `*` | `*` |
| Global | `ALL` | `*` | `*` | `*` |

Ranking (paling besar menang):

```
CASE MMP_SCOPE_LEVEL WHEN 'ITEM' THEN 100 WHEN 'GROUP' THEN 50 ELSE 0 END
+ CASE WHEN MMP_GRADE_CODE_2 <> '*' THEN 2 ELSE 0 END
+ CASE WHEN MMP_GRADE_CODE_1 <> '*' THEN 1 ELSE 0 END
```

`GRADE_2` sengaja diberi bobot lebih tinggi dari `GRADE_1` — kalau untuk satu item ada rule grade_2 dan rule grade_1 sekaligus, grade_2 yang menang.

**Level `GROUP` belum dipakai di Phase 1** karena kolom grouping FG di `OM_ITEM` belum didefinisikan. Resolver sudah siap; `F_GET_ITEM_GROUP` mengembalikan NULL sampai kolomnya ada. Jangan bangun UI master group dulu.

### 3.4 MGT_PRICE_CHECK_LOG — bukti audit

Ditulis **setiap** pemeriksaan, lolos maupun gagal. Ini bukan log debugging — ini yang dipakai laporan spot-check ke BOD, dan satu-satunya cara merekonstruksi keputusan lama kalau `MST_EXC_RATE_SAL` diedit belakangan.

Wajib tersimpan: kurs yang dipakai, **tanggal kurs**, sumbernya (BCA / Orion), `SOH_DT`, dan tanggal approve.

`MPCL_NET_RATE_USD` dan `MPCL_HAS_DISCOUNT` diisi untuk monitoring — validasi memakai gross, tapi selisihnya dicatat supaya nanti ada dasar kalau mau pindah ke net.

---

## 4. Phase 1 — Kontrol harga minimum

### 4.1 Scope

Berlaku untuk `SOH_TXN_CODE` in **ESC, LSC, STA**. Daftar ini disimpan di `IM_VS_STATIC_VALUE` dengan `VSSV_VS_CODE = 'MINPRC_MGT'`, mengikuti pola `TOL_SO_MGT` yang sudah ada — bukan hardcode.

Titik pemeriksaan: `BEFORE UPDATE ON OT_SO_HEAD`, saat `SOH_APPR_STATUS` berubah dari bukan-3 menjadi 3.

`BEFORE`, bukan `AFTER`, supaya error muncul sebelum trigger side-effect lain jalan (`ODBTRG_SO_CBD_MGT`, `ODBTRG_QRCODE_SO`, summary WMS).

### 4.2 Baris yang dilewati

- `NVL(SOI_SHORT_CLO_STATUS,2) <> 2` — short-closed
- `NVL(SOI_FOC_YN,'N') = 'Y'` — free of charge
- `NVL(SOI_RATE,0) = 0` — sudah diblok duluan oleh `ODBTRG_CONTRACT_VAL`
- Item tanpa rule yang cocok — lewati tanpa komentar

### 4.3 Konversi kurs

Harga minimum dalam USD. Semua kurs dinormalkan ke **IDR per USD** (angka ribuan), lalu:

```
USD_rate = SOI_RATE / divisor
```

Cascade divisor untuk IDR:

1. Kurs BCA pada `SOH_DT` → `MST_EXC_RATE_SAL.MERS_VALUE`
2. Kurs BCA terakhir sebelum `SOH_DT`, maksimal mundur 7 hari
3. Kurs Orion `FM_EXCHANGE_RATE` untuk periode `SOH_DT` → `1 / CER_EXG_RATE`
4. Tidak ketemu → **raise error**, jangan lolos

`SOH_DT` dipakai untuk mencari kurs **dan** untuk mencari rule yang berlaku. Satu tanggal acuan, jangan campur dengan `SYSDATE`.

> **⚠️ VERIFIKASI SEBELUM DEPLOY**
> Arah `MERS_VALUE` (BCA) dan `CER_EXG_RATE` (Orion) diasumsikan berbeda: BCA ~17250, Orion ~0.000058. Cek di data riil. Package sudah punya sanity check (divisor harus 1.000–100.000) yang akan raise kalau asumsinya salah — jadi kalau salah, gagalnya jelas, bukan diam-diam.

Currency selain USD dan IDR → raise error. `MST_EXC_RATE_SAL` hanya punya USD/IDR.

**Jangan pakai `mgt_get_exg_rate_bca` atau `mgt_get_exg_rate` apa adanya.** Keduanya mengembalikan `v_rate := 1` kalau tidak ketemu — untuk booking order aman, untuk validasi floor fatal (harga IDR dibagi 1 lolos apa pun). Fungsi existing jangan diubah karena dipakai tim sales; wrapper baru ada di package.

### 4.4 Perilaku pemeriksaan

Semua baris diperiksa, pelanggaran dikumpulkan, lalu **satu error** menyebut semua baris yang melanggar. Jangan raise di baris pertama — sales akan approve-fail-fix berulang kali.

Contoh: 5 item, 3 tanpa rule, 2 melanggar → satu error menyebut kedua baris, dokumen tidak bisa approve.

Toleransi: `MMP_TOLERANCE_PCT` (default 0). Lolos kalau `USD_rate >= min_price * (1 - tol/100)`.

### 4.5 Pewarisan STA → ESC

Urutan pemeriksaan per baris:

1. `USD_rate >= min` → lolos
2. Ada override approved untuk `SOI_SYS_ID` ini dengan rate sama persis dan qty ≤ approved → lolos
3. Dokumen punya `SOH_REF_SYS_ID`, dan baris induknya punya override approved dengan rate sama persis → lolos (warisan)
4. Selain itu → blok

> **⚠️ VERIFIKASI**
> Pemetaan baris STA → baris ESC diasumsikan lewat `SOI_SOI_SYS_ID` (pola yang dipakai `ODBTRG_SOI_TOL_MGT`). Cek dengan query di data STA riil. Ada juga `SOI_SOR_SYS_ID` dan `SOI_BSOI_SYS_ID` untuk jalur blanket SO. Kalau ternyata beda, ganti di `F_HAS_PARENT_OVERRIDE`.

### 4.6 Pesan error

Pakai `RAISE_APPLICATION('CUST', <no>, ...)` — mekanisme message registry Orion, tampil native di Forms. **Jangan** `RAISE_APPLICATION_ERROR(-20xxx)`, hasilnya FRM-40509 mentah.

Nomor pesan yang didaftarkan:

| No | Isi |
|---|---|
| 1012110 | Ada baris di bawah harga minimum (daftar baris di parameter 1) |
| 1012111 | Kurs tidak ditemukan untuk tanggal dokumen |
| 1012112 | Currency tidak didukung kontrol harga minimum |

### 4.7 Lingkup aplikasi Laravel

**Master harga minimum (maker-checker):**
- Finance input sebagai `DRAFT` — form atau import Excel (`Maatwebsite\Excel` sudah ada)
- Cetak formulir persetujuan bernomor → status `PRINTED`, isi terkunci
- Upload dokumen bertanda tangan + isi nomor/tanggal/penanda tangan → status `APPROVED`
- Rule aktif hanya kalau `MMP_STATUS = 'APPROVED'`
- Perubahan harga = baris versi baru, bukan update in place

**Pengajuan pengecualian:**
- Marketing pilih nomor SO → **sistem menampilkan sendiri baris mana yang di bawah minimum**, lengkap dengan harga, minimum, dan selisihnya
- Marketing hanya menandai baris dan mengisi alasan — **jangan sediakan input harga manual**, harga diambil dari SO supaya dijamin sama
- Cetak → tanda tangan BOD → Finance upload + approve

**Penting:** halaman "cek harga" ini harus memanggil `PKG_MGT_PRICE_CTRL.F_GET_MIN_PRICE` dan `F_GET_USD_DIVISOR` yang sama dengan trigger, bukan menghitung ulang di PHP. Dua implementasi pasti akan beda suatu saat.

**Validasi di Laravel (Oracle 11g tidak bisa menegakkannya):**
- Periode tumpang tindih untuk kunci rule yang sama → tolak saat simpan
- Isi tidak bisa diubah kalau `MAR_STATUS <> 'DRAFT'`
- Attachment wajib sebelum status jadi `APPROVED`

**Laporan:**
- Daftar rule aktif
- Semua pengecualian yang diberikan + nomor pengajuan + link attachment
- Rekap penolakan dari `MGT_PRICE_CHECK_LOG` (siapa, produk, harga, minimum, selisih)
- Monitoring gross vs net (kolom `MPCL_NET_RATE_USD`) — untuk review basis validasi nanti

**Permission (Spatie):**
`min-price.draft`, `min-price.print`, `min-price.approve`, `min-price.report`.
Approver adalah **role**, bukan user tertentu — kalau di-hardcode ke satu orang, semua SO berhenti saat dia cuti.

### 4.8 Yang perlu dicek sebelum deploy

| # | Item | Cara cek |
|---|---|---|
| 1 | Arah `MERS_VALUE` dan `CER_EXG_RATE` | Query data riil, cocokkan dengan SO yang sudah closed |
| 2 | Transaksi USD menyimpan `SOH_EXGE_RATE = 1` (bukan null/0) | Query `OT_SO_HEAD` |
| 3 | Pemetaan baris STA → ESC | Query STA yang refer ke ESC |
| 4 | ESC/LSC/STA semuanya wajib approval (tidak ada yang `APPR_STATUS = 0`) | `OM_TXN` + data historis |
| 5 | User Oracle Laravel punya DML di 4 tabel baru | `USER_TAB_PRIVS` |
| 6 | Kolom `IM_APP_ERROR_MESSAGE` sesuai skrip | `DESC IM_APP_ERROR_MESSAGE` |

Kalau nomor 4 ternyata ada TXN_CODE yang approval-nya tidak wajib, perlu trigger tambahan di `OT_SO_ITEM` (cek header parent sudah approved → validasi baris itu saja). Jangan dibangun sebelum terbukti perlu.

---

## 5. Phase 2 — Open overdue delivery (outline)

Trigger existing: `ODBTRG_WAVE_CUST_OVERDUE` di `OT_WMS_WAVE_REF`.

**Tidak perlu trigger baru.** Cukup tambah `AND NOT EXISTS (pengecualian aktif)` di kondisi `raise_application` yang sudah ada.

Karena trigger ini `BEFORE INSERT OR UPDATE` tanpa filter status, wave-nya tidak bisa dibuat sama sekali — jadi pengecualian tidak bisa dikaitkan ke wave. Melekat ke **customer**, diajukan sebelum pengiriman, berupa **jendela waktu** (`MAR_VALID_FROM/TO`), bukan sekali pakai.

Migrasi: kurang dari 5 customer punya `CUST_FLEX_18 = 'Y'`. Konversi manual jadi pengecualian bertempo, lalu flag dikembalikan ke `'N'`. Ini perubahan kebijakan — perlu persetujuan Finance dulu.

Catatan: `ost_comp_code` di-hardcode `'002'`, dan tiga cursor dijalankan di setiap insert/update wave ref tanpa filter status. Cursor `C1` berat. Kalau trigger ini dibuka, beri guard di depan.

## 6. Phase 3 — Credit limit increase (outline)

Trigger existing: `ODBTRG_WAVE_CRLIMIT_CHK_MGT`.

Bentuk pengecualian: **tambahan nilai bertempo**, bukan lolos per dokumen.

```
Trigger membandingkan terhadap:  CCO_CREDIT_LIMIT + SUM(MAR_AMOUNT pengecualian aktif)
```

Begitu lewat tanggal, limit kembali normal sendiri. Master ERP tidak pernah disentuh. Masa berlaku pendek — 7–14 hari, karena alasannya pembayaran yang sedang berjalan.

Yang perlu disadari: tambahan berlaku untuk **semua pengiriman** customer itu selama jendela waktunya, bukan satu pengiriman. Yang membatasi nominal dan tanggalnya.

**Prasyarat: migrasi `SOH_FLEX_18` → `SOH_CUST_CODE`.**
Kolom itu dipakai `CUST_DTLS` lewat `Nvl(SOH_FLEX_18, SOH_CUST_CODE)`, dan cursor `GET_OS_AMT` di trigger yang sama memakai `INVH_FLEX_18` di `OT_INVOICE_HEAD`. Jadi migrasinya menyentuh dua tabel transaksi berikut data historisnya. Kerjakan sebagai perubahan tersendiri dengan verifikasi data sendiri, bukan disisipkan ke pekerjaan lain. Salah sedikit = perhitungan outstanding customer meleset = pengiriman terblok atau lolos keliru.

## 7. Catatan lintas phase

Setelah Phase 2 dan 3 jalan, ada **tiga trigger** yang bereaksi pada `WWR_STATUS = 3` di `OT_WMS_WAVE_REF` (CBD, credit limit, overdue). Urutan eksekusi trigger sejenis tidak dijamin di Oracle 11g — kalau satu wave melanggar dua kontrol sekaligus, pesan yang muncul bisa berbeda-beda antar percobaan. Bukan bug, tapi perlu diketahui saat menjelaskan ke user.

Ikuti pola rumah: panggil `ODBPROC_PRAGMA_DEL_WAVE_MGT(:NEW.WWR_WWH_SYS_ID)` sebelum raise, supaya wave tidak tertinggal menggantung.

---

## 8. Urutan pengerjaan Phase 1

1. Jalankan verifikasi bagian 4.8 nomor 1–6
2. Deploy DDL + sequence + message registry (`minimum-script-control.sql` bagian A–C)
3. Deploy package, compile, unit test manual lewat SQL
4. Bangun UI master harga minimum (maker-checker) + import Excel
5. Bangun halaman cek harga (panggil package)
6. Bangun pengajuan pengecualian + cetak + upload
7. Deploy trigger di lingkungan test, uji dengan SO nyata
8. Laporan
9. UAT bersama Finance dan Marketing
10. Deploy trigger ke produksi

Trigger dipasang **paling akhir**. Sebelum UI-nya siap, memasang trigger berarti memblok approval tanpa ada jalan keluar buat sales.
