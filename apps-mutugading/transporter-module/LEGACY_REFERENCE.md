# Transporter — Referensi Sistem Lama (Oracle Forms & Reports 6i)

**Tanggal ekstraksi:** 2026-09-15
**Sumber:** `TRNSP001–008.fmb`, `TRNSP001–009.rdf`, `ODBTRG_TPCHP.trg`, dan data dictionary schema `MGTDAT` / `MGTAPPS` di database produksi (`althara`).
**Dokumen utama:** [`PRD_Transporter_Module.md`](PRD_Transporter_Module.md)

---

## Untuk apa berkas ini

PRD memuat **analisa dan keputusan**: apa yang dikerjakan sistem lama, kelemahannya, dan bagaimana sistem barunya dirancang. Yang **tidak** dimuat PRD adalah kode sumber mentahnya — query lengkap, isi prosedur, definisi view, konfigurasi lookup.

Berkas-berkas di `legacy-source/` mengisi celah itu. Fungsinya satu: **ketika pertanyaan detail muncul di tengah pengembangan** — "persisnya `tjv_bill_chp` menghitung PPN bagaimana?", "kolom apa saja yang ditarik `MGT_TRANSP_VIEW`?" — jawabannya bisa dibaca tanpa perlu Oracle Developer Suite dan tanpa menebak.

Semua isinya teks biasa, bisa di-`grep`, bisa di-*diff*, dan masuk ke version control dengan wajar — tidak seperti `.fmb` dan `.rdf` yang biner dan hanya bisa dibuka oleh perangkat yang sudah tidak dipasang di mana pun.

---

## Isi `legacy-source/`

### `plsql/` — kode yang masih hidup di database

| Berkas | Isi | Baris |
|---|---|---|
| `PKG_TRANSPORTER.pks` | Spesifikasi package — 7 prosedur | 11 |
| `PKG_TRANSPORTER.pkb` | **Body lengkap.** Inti akuntansi modul: `jv_provision`, `jv_provision_chp`, `tjv_bill`, `tjv_bill_chp`, `tjv_bill_add`, `tjv_bill_chp_add`, `insert_data` | 4.000 |
| `ODBTRG_TPCHP.trg` | Trigger di `OT_GR_HEAD` yang membuat transaksi `TPCHP` otomatis saat GRN chip di-approve | ~200 |
| `FNC_TRANSP_AMT_INV_MGT.sql` | Hitung nilai angkutan per invoice (tarif per kg) | 73 |
| `EV_DN_TRANSP_MGT.sql`, `O_VAL_TRANSPORTER.sql` | Prosedur pendukung | 34 / 45 |
| `views.sql` | Definisi 10 view: `MGT_TRANSP_VIEW`, `MGT_TRANSP_TRX_VIEW`, `TRANSP_LDN_PN`, `TRANSP_LDN_PN_AUTO`, `TRANSP_DESTI_V`, `TRANSP_LIST_V`, `TRANSPORTER_BUDGET_V`, `EXPORT_COST_TRANSPORTER_V`, `MGT_PEND_LDN_TO_TPDN_V`, `MGT_PEND_TPDN_TO_PROVISION_V` | 568 |

> Objek-objek ini **masih ada dan aktif di database**. Salinan di sini adalah potret per 2026-09-15, berguna sebagai pembanding bila nanti ada yang berubah sebelum cutover.

### `schema/` — struktur & konfigurasi

| Berkas | Isi |
|---|---|
| `tables.sql` | Kolom, constraint, index, dan sequence untuk 14 tabel aktif. Tabel backup manual sengaja tidak disertakan |
| `config-data.sql` | Isi lookup yang menggerakkan modul: 45 `DESTINATION`, 8 `TYPE_TRUCK`, `TRANSP_TYPE`, `TRANSPORT`, **48 profil pajak vendor** (`TRANSPORTER`, dengan skema PPN/PPh dan masa berlakunya), 6 pesan error trigger, serta jenis dokumen & folder e-Filling |

### `forms/` — 8 form

Tiap berkas memuat, untuk satu form: daftar trigger yang ada beserta jumlahnya, seluruh **statement SQL yang tertanam** (Forms menyimpannya dalam bentuk terkompilasi tetapi teksnya utuh), dan blok PL/SQL yang teksnya masih terbaca.

| Form | Fungsi |
|---|---|
| `TRNSP001.sql` | Master transporter & tarif |
| `TRNSP002.sql` | Transaksi angkutan manual — **berisi logika pemilihan tarif `W`/`Q`** |
| `TRNSP003.sql` | Provisi & generate JV |
| `TRNSP004.sql` | Tagihan & generate TJV — **berisi logika pajak PPN/PPh dan gerbang kelengkapan dokumen** |
| `TRNSP005.sql` | Monitoring GRN chip |
| `TRNSP006.sql` | Biaya lain-lain |
| `TRNSP007.sql` | Tagihan add-on |
| `TRNSP008.sql` | Tarik surat jalan & generate TPDN |

> **Batas ekstraksi.** Forms menyimpan sebagian kode sebagai p-code terkompilasi, sehingga tidak setiap baris PL/SQL bisa dipulihkan. Yang pasti utuh adalah **seluruh statement SQL** — dan di situlah hampir semua aturan bisnisnya berada. Yang hilang: tata letak kanvas, koordinat item, properti visual, dan definisi LOV. Tidak ada satu pun dari itu yang dipakai ulang di sistem baru.

### `reports/` — 9 report

Tiap berkas memuat tabel/view yang dibaca, judul & label kolom, query, dan blok PL/SQL-nya (termasuk `RPT2XLS.put_cell` yang menunjukkan **susunan kolom Excel persis seperti yang diterima user**).

`TRNSP009.sql` adalah Transporter Budget — sudah dinyatakan tidak relevan (digantikan `TRANSPORTER_BUDGET_V`), tetapi tetap disimpan karena strukturnya jadi acuan report F-08.5.

---

## Pengetahuan yang tidak tersimpan di kode mana pun

Hal-hal berikut hanya ada sebagai pengetahuan operasional. Tanpa dicatat di sini, ia hilang bersama berkas binernya.

### Cara sistem lama dijalankan

- Form dijalankan dari Oracle Forms Runtime 6i di PC yang terpasang Oracle Developer Suite. Tidak ada versi web.
- Report dipanggil dari form lewat `RUN_PRODUCT` ke Reports Runtime.
- Ekspor Excel memakai library **`RPT2XLS`** milik Orion, diaktifkan bila parameter `M_DEST_TYPE = 'E'`. Susunan kolomnya ditulis manual satu per satu lewat `RPT2XLS.put_cell` — bukan hasil layout report, sehingga urutan kolom Excel bisa berbeda dari tampilan PDF.
- Form bergantung pada library Orion: `Finreppl`, `ROSATTRIBS`, `ROSLFDESC`, `ROSSTRINGS`, `ROSSTRUCTS`, dan template `TEMPLATE_MASTER` / `TEMPLATE_TRAN`.
- Identitas user diambil dari `MGTDAT.MENU_USER` (Orion), bukan dari aplikasi Laravel.

### Pembagian tanggung jawab dengan Orion

- Baris otorisasi `FT_TXN_AUTH` **dibuat Orion sendiri** lewat prosedur standar `STP_DINSERT_APPR_RECS` / `STP_DINSERT_APPR_RECS_NEW`, yang dipanggil paket keuangannya (`FINPKG_FT2502`, `FINPKG_FT2504`, `ORNDBPKG_*`). `PKG_TRANSPORTER` menulisnya sendiri — dan itu penyimpangan, bukan pola yang harus ditiru.
- `ODBTRG_APPR_VOUCHER` pada `FT_UNPOSTED_TRANS_HEADER` menegakkan **maker ≠ approver** di level database (error 2441465).
- `OT_GR_HEAD` memikul **18 trigger**. `ODBTRG_TPCHP` hanya salah satunya — perubahan apa pun di tabel itu berisiko tinggi.

### Integrasi e-Filling

Status kelengkapan hard copy surat jalan (`MTDD_STS_DOC` / `MTDD_STS_PRS`) **tidak ditulis oleh objek database mana pun**, melainkan oleh job aplikasi web e-Filling (PHP di XAMPP), menu *Transporter Comparation* (`MST_MENUS.MENU_ID = 2C90000000`, `Efilling_File\EFILL_009`).

```
Scanner → D:/XAMPP/htdocs/webapps/SCAN_DOC/<workstation>/
  → EFILL_001  rename menjadi {TYPE}-{nomor}.pdf
  → EFILL_002  pindah ke Doc_Folder/{TYPE}/{TAHUN}/
  → EFILL_009  cocokkan dengan MGT_TRANSP_DETAIL_DN, set STS_DOC = Y/N
```

### Potret data saat ekstraksi

| Ukuran | Nilai |
|---|---|
| Rentang transaksi | Februari 2014 – Agustus 2026 |
| Baris pada 14 tabel aktif | ± 227.000 |
| Tabel backup manual | 30+ tabel, ± 300.000 baris |
| Vendor nyata vs baris master | 50 vendor tersebar di 739 baris master |
| Transaksi `TPDN` / `TPCHP` | 29.189 / 3.937 |
| Provisi / tagihan | 21.411 / 3.809 |

---

## Yang sengaja tidak disimpan

Berkas `.fmb` dan `.rdf` asli **tidak diperlukan lagi** setelah ekstraksi ini, karena yang hanya ada di sana adalah hal yang tidak dipakai ulang:

| Tidak disimpan | Kenapa tidak apa-apa |
|---|---|
| Tata letak kanvas, koordinat item, warna, font | Sistem baru memakai Flux UI dan komponen modul UI — tidak ada satu pun yang diwarisi |
| Definisi LOV & record group | Query-nya sudah ikut terekstrak sebagai statement SQL |
| Properti blok & item Forms | Perilakunya sudah dijelaskan di PRD sebagai requirement |
| Layout report (frame, repeating frame, posisi field) | Susunan kolom Excel — yang justru dipakai user — sudah terekam lewat `RPT2XLS.put_cell` |
| Sebagian PL/SQL yang tersimpan sebagai p-code | Seluruh statement SQL tetap utuh, dan di situlah aturan bisnisnya |

Berkas `.fmb` dan `.rdf` asli **sudah dihapus dari folder ini** pada 15 September 2026, setelah ekstraksi di atas selesai dan diverifikasi. Kalau suatu saat tetap dibutuhkan, salinannya masih ada di drive kerja developer dan versi terpasangnya di Orion Reports server:

| Berkas | Salinan lain di drive D |
|---|---:|
| TRNSP001 | 9 |
| TRNSP002 | 42 |
| TRNSP003 | 7 |
| TRNSP004 | 27 |
| TRNSP005 | 3 |
| TRNSP006 | 2 |
| TRNSP007 | 2 |
| TRNSP008 | 5 |
| TRNSP009 | 3 |

Lokasinya: `D:\DataHdd80Gb\DriveD\kerjaan\<tahun>\<periode>\<tanggal>\` dan `D:\BACKUP DATA D LAPTOP AAM\HDD Old\G\Aam\Kerjaan\Transporter\`.

`ODBTRG_TPCHP.trg` tetap disimpan di folder ini — berkas teks, bukan biner, dan aslinya memang sudah bisa dibaca langsung.
