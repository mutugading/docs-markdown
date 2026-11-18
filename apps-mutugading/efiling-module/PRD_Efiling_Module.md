---
title: "PRD – E-Filing Module (Arsip Dokumen Transaksi Digital)"
project: "Apps Mutu Gading"
repo: "apps-mutugading"
stack: "Laravel 12 + Livewire 3 + Oracle (MGTHRIS) + MinIO"
version: "0.3"
status: "Draft"
author: "Indra Putro"
date: "2026-10-14"
last_updated: "2026-10-14"
related_prd: ["transporter-module/PRD_Transporter_Module.md"]
related_erd: ""
---

# PRD – E-Filing Module (Arsip Dokumen Transaksi Digital)
## PT Mutu Gading Tekstil

> Lampiran: [`LEGACY_REFERENCE.md`](LEGACY_REFERENCE.md) — analisa sistem lama (`§L-x`).
> Legacy dipakai **hanya sebagai referensi perilaku dan sumber berkas yang dimigrasi**. Modul baru
> **tidak memakai satu pun tabel legacy** (`MGTAPPS.EFILL_*`, `GRN_MERGE_DOCS_DATA`, `MST_EMAIL_CUST`,
> `LOG_SENDING_MAIL_CUST`, `MST_PARAM_DATA`). Semua tabel baru di schema **MGTHRIS** (`oracle_mgthris`).
>
> **Status checklist template**
> - [x] Stack ditentukan (Laravel modular monolith, Oracle MGTHRIS, MinIO)
> - [x] Integrasi dipetakan (§8), termasuk aplikasi legacy lain yang memakai Doc_Folder (§L-10)
> - [x] Pemakaian nyata diukur dari log 12 bulan (§L-9)
> - [ ] Stakeholder per kategori dokumen divalidasi (OI-07 — bahan: `EFILING_USER_FOLDER_ACCESS.xlsx`)
> - [ ] Open item *Blocking* di §11 terjawab

---

## Revisi v0.2 (ringkas)

| Topik | v0.1 | v0.2 |
|---|---|---|
| Schema | MGTAPPS, pakai ulang `EFILL_TYPE_DATA` / `EFILL_USER_ACCESS` | **MGTHRIS, semua tabel baru** (`efl_*`); legacy tidak dipakai |
| Hak akses | Permission + `EFILL_USER_ACCESS` | Permission Spatie + **grant per jenis ke Role/User** + jendela tahun + tingkat kerahasiaan |
| Versi & hapus | Prefix `versions/`, `trash/` buatan aplikasi | **MinIO bucket versioning + Object Lock (retensi WORM per jenis)** + enkripsi at-rest |
| Intake | Upload + inbox manual | **Pipeline intake** berbasis job: validasi → sidik → auto-match ERP → filed / perlu review |
| Pemetaan ERP | Daftar `source` di jenis | **Source Adapter** per keluarga transaksi: validasi nomor, tahun, relasi dokumen |
| Kelengkapan | Laporan per jenis | **Coverage**: aturan kewajiban scan per jenis, snapshot harian, aging, notifikasi ke pemilik |
| Modul terkait | Disebut, di luar MVP | Dibangun: **Dossier** (Payment, Proof of Payment, Invoice), **Bundle** (GRN Merge, Invoice Merge), **Dispatch** (kirim Faktur Pajak Advance ke customer), integrasi Transporter & modul lain |

v0.1 tersimpan di riwayat (lihat §14).

---

## 1. Executive Summary

E-Filing adalah arsip digital dokumen transaksi hasil scan (invoice, surat jalan, faktur pajak, payment,
PO, GRN, jurnal, dll.). Saat ini berjalan sebagai aplikasi PHP native dengan **577 ribu PDF (±462 GB)** di
folder jaringan, ±9 ribu upload per bulan, dan dokumen-dokumennya dibuka dari setidaknya 8 aplikasi lain
(§L-9). Modul baru `Modules/Efiling` membangun ulang arsip ini di platform Apps Mutu Gading dengan
**MinIO** sebagai penyimpanan objek terenkripsi, versioned, dan terkunci retensinya; **indeks dokumen**
di Oracle MGTHRIS; **intake otomatis** yang mencocokkan berkas dengan transaksi ERP; **hak akses berlapis**;
dan **audit lengkap**. Fitur-fitur legacy yang terpencar di aplikasi lain — merge GRN, payment supporting
document, invoice breakdown, kirim faktur pajak ke customer — disatukan sebagai bagian modul ini.
Dampaknya: tidak ada lagi dokumen keuangan yang bisa diunduh tanpa login, arsip tidak bisa hilang karena
salah hapus, dan setiap transaksi ERP bisa dilacak kelengkapan dokumennya.

---

## 2. Background & Problem Statement

### 2.1 Konteks Bisnis

```
Admin scan per dept (26 orang aktif)
   │ scanner → PDF bernama BR_2026…, CGRN_2026… (format scanner)
   ▼
EFILL_008 upload ke SCAN_DOC/{user}  →  EFILL_001 rename manual  →  EFILL_002 pindah ke Doc_Folder/{TYPE}/{YYYY}/
   (Costing punya salinan sendiri: SCAN_DOC_CST)
   ▼
Doc_Folder (SMB \\192.168.0.3, URL static tanpa login)
   ├─ e-Filing: Find Document, Payment Supporting, Proof of Payment, Invoice Breakdown, Check Scanned, Delete, Log
   ├─ GRN Merging Document: gabung GRN + SJGRN, menimpa berkas GRN asli
   ├─ Sending Document: email faktur pajak advance ke customer
   ├─ Shipping, Reports, Master Batch, Taxation, Costing, Sales, Event, TWT: buka dokumen via URL
   └─ Transporter (Laravel): job scan surat jalan membaca mount
```

### 2.2 Masalah yang Diselesaikan

| # | Masalah | Dampak |
|---|---------|--------|
| 1 | Semua PDF disajikan sebagai URL static **tanpa login** (§L-7) | Dokumen keuangan & pajak bisa diunduh siapa saja yang tahu/menebak URL |
| 2 | Hapus berkas fisik tanpa autentikasi, path dari klien (§L-7) | Arsip bisa hilang permanen |
| 3 | SQL injection di semua query; kredensial Oracle & SMTP tertulis polos | Risiko kebocoran / perusakan data |
| 4 | 133 ribu operasi "move" + 101 ribu upload per tahun dikerjakan manual, nama diketik tangan | Beban kerja admin; dokumen salah nama "ada tapi tidak ketemu" |
| 5 | Tidak ada indeks: keberadaan dicek `file_exists` per baris | Laporan kelengkapan lambat, tidak bisa lintas jenis |
| 6 | GRN Merge **menimpa** PDF GRN asli dengan hasil gabungan (§L-10) | Berkas sumber hilang; tidak bisa diulang |
| 7 | Fitur sama diduplikasi di beberapa aplikasi (Payment Supporting = EFILL_007; Costing = salinan EFILL_002/014) | Perbaikan harus dilakukan berkali-kali; perilaku menyimpang |
| 8 | Hak akses hanya lewat tampil/tidaknya menu; pembatasan tahun untuk auditor dibangun sebagai potongan SQL | Endpoint bisa dipanggil di luar hak |
| 9 | Penyimpanan di disk satu server Windows, tanpa versioning, tanpa enkripsi | Single point of failure; tidak memenuhi kewajiban retensi dokumen pajak |

### 2.3 Stakeholders

> ⚠️ Asumsi dari data akses legacy — validasi di OI-07.

| Stakeholder | Dept | Kebutuhan Utama | Akses |
|-------------|------|-----------------|-------|
| Admin scan / filing | Finance, Accounting, Purchasing, Warehouse (GRN), Sales Admin, Despatch, Costing | Upload batch cepat, nama & folder otomatis | Upload, inbox, lihat jenis miliknya |
| Pengguna verifikasi | Finance AP/AR, Tax, Shipping, Purchasing | Temukan dokumen dari nomor transaksi; lihat dossier transaksi | Lihat jenis yang di-grant |
| Auditor | Internal/eksternal | Read-only terbatas jenis & tahun, jejak akses | Grant baca dengan jendela tahun |
| Finance AR | Finance | Kirim faktur pajak advance ke customer | Dispatch |
| Warehouse / MC | Material Control | Bundle GRN + surat jalan supplier | Bundle |
| Admin e-Filing | IT / Accounting Head | Kelola jenis, grant, retensi, pemulihan | Semua |
| Modul Transporter, Finance, MaterialControl | Sistem | Cek & tautkan dokumen dari modul sendiri | Kontrak service |
| IT Infrastruktur | IT | Kapasitas, enkripsi, backup MinIO | — |

---

## 3. Goals & Non-Goals

### 3.1 Goals (In-Scope)

1. `Modules/Efiling` dengan sub-domain **Archive, Intake, Coverage, Dossier, Bundle, Dispatch, Admin**,
   mengikuti pola repo (Repository → Service → Livewire, DTO, Enum, RepositoryServiceProvider, menu
   sidebar via migrasi idempoten, log channel per job).
2. **Data model baru di MGTHRIS** (§7) — master jenis, kategori, alias nama scanner, grant akses,
   aturan coverage, profil bundle & dispatch, kontak penerima; transaksi dokumen, versi berkas,
   referensi nomor, inbox, log akses.
3. **MinIO aman**: bucket privat khusus, enkripsi at-rest (SSE-KMS), versioning, **Object Lock** retensi
   per jenis, tidak ada URL publik; semua akses lewat aplikasi atau URL bertanda tangan sangat singkat.
4. **Intake pipeline** otomatis: upload batch → validasi isi PDF → hash & deduplikasi → auto-match
   jenis + nomor dari nama berkas dan ERP → *filed* atau *perlu review*.
5. **Source Adapter ERP** (read-only MGTDAT) per keluarga transaksi: validasi nomor, penentu tahun,
   metadata (tanggal, mitra, nilai), dan **relasi** antar dokumen untuk Dossier.
6. **Hak akses berlapis**: permission fitur + grant jenis (Role/User) + jendela tahun + kerahasiaan.
7. **Coverage**: transaksi ERP mana yang wajib ada scan-nya, persentase kelengkapan, aging, notifikasi.
8. **Modul terkait dibangun di project ini** (bukan diserahkan ke legacy):
   - **Dossier** — Payment Supporting Document, Proof of Payment (MCM), Invoice Breakdown.
   - **Bundle** — GRN + SJGRN (pengganti GRN Merging Document), Invoice + FP + DN + KWI; generik via profil.
   - **Dispatch** — kirim dokumen ke customer via email (pengganti Sending Document — Faktur Pajak Advance).
   - **Integrasi** — kontrak `EfilingDocuments` dipakai Transporter (pengganti baca mount), komponen
     Blade `<x-efiling::document-link>` untuk Finance Shipment, MaterialControl, dll.
9. **Migrasi** 577 ribu berkas Doc_Folder ke MinIO + indeks, resumable, terverifikasi checksum.
10. **Audit** mutasi (activity log) dan akses baca (access log) + deteksi unduhan massal.

### 3.2 Non-Goals (Out-of-Scope)

- Memakai, memigrasi, atau menyinkronkan **tabel** legacy. Hanya **berkas** Doc_Folder yang dimigrasi.
  Grant akses awal diisi lewat import Excel di modul baru (FR-02).
- OCR / pencarian full-text isi PDF (Phase 3; desain tabel sudah menyisakan ruang, §7 catatan).
- Integrasi driver scanner (TWAIN). Scanner tetap menghasilkan PDF di PC.
- Tanda tangan digital / e-Meterai.
- Approval workflow atas dokumen — e-Filing adalah arsip, bukan transaksi.
- Menulis ke MGTDAT. Seluruh akses ERP read-only.
- Membangun ulang aplikasi Shipping, Reports, Master Batch, Taxation, Event, TWT, KYP legacy — aplikasi itu
  cukup diarahkan lewat **legacy link bridge** (FR-16).
- Kontrol status dokumen surat jalan tetap milik modul Transporter (PRD Transporter §5.9); E-Filing hanya
  menyediakan sumber berkasnya.

### 3.3 Success Metrics

| Metrik | Baseline | Target |
|--------|----------|--------|
| Berkas yang bisa diakses tanpa login | 100% | 0% |
| Berkas yang otomatis terklasifikasi saat intake (tanpa ketik nama) | 0% | ≥ 85% |
| Langkah manual per dokumen (upload → arsip) | 3 layar (upload, rename, move) | 1 (upload batch, konfirmasi hanya bila perlu review) |
| Laporan coverage 1 jenis × 1 bulan | `file_exists` per baris | < 3 detik |
| Berkas termigrasi & terverifikasi checksum | 0 | 100% PDF; selisih 0 |
| Hapus yang bisa dipulihkan | 0% | 100% selama retensi; 0% bisa dihapus sebelum masa Object Lock |
| Aplikasi yang menduplikasi fitur e-Filing | 4 (Costing, Payment, GRN, Sending) | 0 — semua lewat modul ini |

---

## 4. User Stories

| ID | Persona | User Story | Prioritas |
|----|---------|-----------|-----------|
| US-01 | Admin scan | Sebagai admin scan, saya ingin men-*drop* puluhan PDF hasil scanner sekaligus dan sistem otomatis mengenali jenis & nomornya, agar saya tidak perlu rename dan pindah satu per satu | P0 |
| US-02 | Admin scan | Sebagai admin scan, saya ingin melihat hanya berkas yang gagal dikenali, dengan preview dan saran nomor dari ERP, agar koreksi cepat | P0 |
| US-03 | Admin scan | Sebagai admin scan, saya ingin upload langsung dari nomor transaksi tertentu, agar dokumen susulan mudah diarsip | P0 |
| US-04 | Pengguna verifikasi | Sebagai staf Finance, saya ingin mencari dokumen berdasar jenis, nomor, tahun, mitra, atau tanggal transaksi, lalu melihatnya di aplikasi | P0 |
| US-05 | Staf AP | Sebagai staf AP, saya ingin membuka satu payment dan melihat semua dokumen pendukungnya (bill, GRN, PO, MCM) beserta mana yang belum ada | P0 |
| US-06 | Staf AR | Sebagai staf AR, saya ingin melihat invoice beserta faktur pajak, surat jalan, dan kwitansinya, lalu menggabungkannya jadi satu PDF | P0 |
| US-07 | Supervisor dept | Sebagai supervisor, saya ingin melihat persentase kelengkapan scan departemen saya dan daftar transaksi yang belum di-scan beserta umurnya | P0 |
| US-08 | Admin e-Filing | Sebagai admin, saya ingin mengatur jenis dokumen, alias nama scanner, retensi, dan grant akses per Role/User dari UI | P0 |
| US-09 | Admin e-Filing | Sebagai admin, saya ingin memulihkan dokumen yang terhapus atau versi lama yang tertimpa | P0 |
| US-10 | Auditor | Sebagai auditor, saya ingin akses baca hanya ke jenis dan tahun yang ditugaskan, dan setiap akses saya tercatat | P0 |
| US-11 | Pemegang dokumen rahasia | Sebagai pemegang akses jenis rahasia, saya ingin melihat dokumen dengan watermark nama saya dan tanpa tombol unduh | P1 |
| US-12 | Staf Warehouse/MC | Sebagai staf MC, saya ingin GRN yang sudah di-approve HOD otomatis dibundel dengan surat jalan suppliernya, tanpa menimpa berkas asli | P0 |
| US-13 | Staf AR | Sebagai staf AR, saya ingin mengirim faktur pajak advance ke email customer terdaftar dalam satu klik per customer, dengan log pengiriman | P1 |
| US-14 | Modul lain | Sebagai modul Transporter/Finance, saya ingin menanyakan dan menautkan dokumen e-Filing lewat kontrak, tanpa tahu lokasi penyimpanan | P0 |
| US-15 | Pengguna aplikasi legacy | Sebagai pengguna aplikasi Shipping lama, saya ingin tautan dokumen yang biasa saya klik tetap bekerja sesudah Doc_Folder dimatikan (dengan login) | P0 |
| US-16 | Admin IT | Sebagai admin IT, saya ingin memigrasi Doc_Folder ke MinIO bertahap, bisa dilanjutkan, dan terverifikasi | P0 |
| US-17 | Admin e-Filing | Sebagai admin, saya ingin diberi tahu bila ada user mengunduh dokumen dalam jumlah tidak wajar | P1 |

---

## 5. Functional Requirements

### 5A. Admin & Master

#### FR-01: Kategori & Jenis Dokumen

- **User Story:** US-08
- **Deskripsi:** Master `efl_doc_category` (mis. Payable, Receivable, Tax, Purchasing, Inventory/GRN,
  Shipping, Costing, Delivery, General Ledger, Other) dan `efl_doc_type` (91 jenis legacy menjadi seed
  awal, diperiksa ulang bersama pemilik — OI-17).
- **Input jenis:** kode (`NSBR`, `LDN`, `FP_SALES`), nama, kategori, **source adapter** (§5C), pola nomor
  (regex), penentu tahun (`DocNumber` / `DocDate`), kerahasiaan (`Normal` / `Confidential` / `Restricted`),
  **retensi** (tahun), mode retensi (`Governance` / `Compliance`), ukuran maksimum, ikut coverage atau tidak,
  aktif.
- **Business Rules:**
  - BR-1: Kode unik `[A-Z0-9_]{2,20}`, dibekukan sesudah ada dokumen. Nama folder legacy berspasi
    (`LDN SALES`) dipetakan lewat alias (FR-01b), bukan dipakai sebagai kode.
  - BR-2: Kunci objek dibentuk sistem: `{category}/{type}/{year}/{type}-{number}/{file_sys_id}.pdf` —
    tidak pernah dari input user (§7.3).
  - BR-3: Retensi default per kategori (usulan: dokumen pajak & pembukuan **10 tahun** — UU KUP Pasal 28
    ayat 11; lainnya 5 tahun) — dikonfirmasi Accounting & Tax (OI-09). Retensi diterapkan sebagai Object Lock.
    Retensi = masa minimum setiap versi berkas (termasuk versi lama hasil ganti dan berkas yang di-*hapus*)
    wajib tersimpan dan tidak bisa dihapus permanen. Berbeda dengan legacy, upload ulang **tidak menimpa**:
    berkas lama menjadi versi `Superseded` (FR-09). Mode default `Governance` (admin berwenang masih bisa
    membuka kunci); `Compliance` hanya untuk kategori yang disetujui tertulis (R-4).
  - BR-5: Batas ukuran default **50 MB per berkas** dan **500 MB per batch** (dapat diubah per jenis).
    Berkas disimpan **apa adanya** — tidak ada kompresi *lossy* otomatis (risiko teks kecil, stempel, tanda
    tangan tidak terbaca). Optimasi *lossless* boleh menyusul (P2). Ukuran dikendalikan di sumber lewat
    standar setelan scanner (200–300 dpi, grayscale/hitam-putih untuk dokumen teks) — rata-rata legacy
    ±0,8 MB/berkas (§L-6).
  - BR-4: Jenis nonaktif tidak muncul di intake; dokumennya tetap terbaca.
- **Kondisi Error:** kode duplikat / pola regex tidak valid → error validasi; ubah kode terpakai → ditolak.
- **Prioritas:** P0

#### FR-01b: Alias Nama Berkas

- **Deskripsi:** `efl_doc_type_alias` — awalan nama berkas yang dikenali sebagai jenis tertentu:
  format scanner (`BR_`, `CGRN_`), format arsip (`BR-`), dan nama folder legacy (`LDN SALES/` → `LDN`
  dengan sub-jenis Sales, `FP SALES/` → `FP_SALES`). Dipakai auto-match intake (FR-04) dan migrasi (FR-15).
- **Business Rules:** satu alias hanya boleh menunjuk satu jenis (unik); alias terpanjang menang
  (memperbaiki bug legacy `WHEN OTHERS → 'Y'`, §L-8).
- **Prioritas:** P0

#### FR-02: Grant Akses per Jenis

- **User Story:** US-08, US-10
- **Deskripsi:** `efl_type_grant` — memberi **Role (Spatie) atau User** kemampuan atas jenis atau kategori.
- **Input:** penerima (Role / User), cakupan (jenis atau seluruh kategori), kemampuan (`View`, `Upload`,
  `Replace`, `Delete`), jendela tahun opsional (`year_from`, `year_to`), berlaku sampai (opsional).
- **Business Rules:**
  - BR-1: Efektif = gabungan grant via semua role user + grant langsung user.
  - BR-2: `efiling-admin` melihat semua jenis kecuali `Restricted` (harus di-grant eksplisit).
  - BR-3: Jendela tahun dan masa berlaku menggantikan `EFILL_USER_YEAR` (auditor) secara terstruktur;
    grant kedaluwarsa otomatis tidak berlaku.
  - BR-4: Import/Export Excel grant (pola repo: job + `ReportStatusNotification`). Ini cara mengisi grant
    awal; isi file disiapkan admin, boleh disusun dari data legacy, tetapi modul tidak membaca tabel legacy.
  - BR-5: Hasil resolusi grant di-cache per user (10 menit, dibersihkan saat grant berubah) — pola
    `MenuTreeService`.
  - BR-6 **Granularitas = kode transaksi.** Satu `efl_doc_type` = satu kode transaksi legacy (`NSBR`,
    `LPO`, `BPJ`, …), sehingga grant `scope_type = Type` setara satu baris `EFILL_USER_ACCESS`. Grant
    `Category` hanya penyingkat; keduanya bisa dicampur. Varian folder yang berbagi kode transaksi
    (`LDN` / `LDN SALES`, `FP SALES`) tetap jenis terpisah supaya bisa di-grant terpisah seperti di legacy.
  - BR-7 **Grant lewat Role sebagai cara utama.** Data legacy (§L-11): 113 user, 1.843 baris, 72
    kombinasi — hampir semuanya kombinasi standar per fungsi kerja. Usulan: role Viewer per keluarga
    dokumen (`EFL-SALES`, `EFL-PURCHASE`, `EFL-GRN`, `EFL-AP`, `EFL-AR`, `EFL-GL`, `EFL-PRODUCT`,
    komposit `EFL-FINANCE`, `EFL-BOARD`) + role `EFL-FILER-*` untuk upload; grant langsung hanya untuk
    jenis sensitif/jarang. Rincian dan pemetaan 72 kombinasi: [`ROLE_MAPPING_PROPOSAL.md`](ROLE_MAPPING_PROPOSAL.md).
  - BR-8 **Ditegakkan di semua jalur, bukan hanya daftar pilihan.** Legacy hanya menyaring dropdown jenis
    di layar browse, sementara layar Payment/Invoice dan URL berkas tidak dicek. Di sistem baru satu
    `EfilingAccessPolicy` dipanggil oleh pencarian, viewer, Dossier (dokumen terkait yang tidak di-grant
    tampil sebagai "ada — tanpa akses"), intake, bundle (hasil hanya terlihat bila user punya akses ke
    jenis hasil), dispatch, bridge, dan kontrak `EfilingDocuments` (memakai user yang sedang login).
- **Prioritas:** P0

#### FR-03: Aturan Coverage

- **User Story:** US-07
- **Deskripsi:** `efl_coverage_rule` — per jenis: transaksi ERP mana yang **wajib** punya scan.
- **Input:** jenis, berlaku mulai tanggal, filter adapter (mis. hanya invoice `appr_status = 3`),
  SLA hari (batas wajar sejak tanggal transaksi), departemen pemilik (`hmdept` via Core), aktif.
- **Business Rules:** jenis tanpa aturan tidak ikut coverage (arsip manual: BUPOT, PIB, ITEM_CODE, dll.).
- **Prioritas:** P0

### 5B. Intake

#### FR-04: Upload Batch & Pipeline Intake

- **User Story:** US-01, US-02
- **Deskripsi:** Pengganti EFILL_008 + 001 + 002 dan salinan Costing. User men-*drop* banyak PDF; tiap
  berkas menjadi `efl_inbox_item` dan diproses job antrean `ProcessInboxItem`:

```
Uploaded ─▶ Validating ─▶ Matching ─┬─▶ Filed          (otomatis, bila semua syarat lolos)
                │                   ├─▶ NeedsReview    (jenis/nomor tidak yakin, duplikat, nomor tidak ada di ERP)
                └─▶ Rejected        └─▶ (user memperbaiki) ─▶ Filed / Discarded
```

- **Business Rules:**
  - BR-1 **Validasi isi:** magic bytes `%PDF`, bisa di-*parse*, tidak terenkripsi, jumlah halaman ≥ 1,
    ukuran ≤ batas jenis; tandai (bukan tolak) PDF ber-`/JavaScript`, `/Launch`, `/EmbeddedFile`
    — dikarantina ke `NeedsReview` dengan alasan keamanan. Antivirus (ClamAV) **opsional**, tidak termasuk MVP:
    sumber berkas adalah scanner internal; dipasang sebagai langkah tambahan bila IT Infra menyediakan (OI-18).
  - BR-2 **Sidik:** SHA-256; hash yang sudah terarsip → `NeedsReview` alasan *duplikat* (menunjuk dokumen aslinya).
  - BR-3 **Auto-match:** nama berkas di-*parse* lewat alias (FR-01b) → jenis + nomor; nomor divalidasi ke
    adapter ERP; tahun dari adapter (bukan dari nama). Lolos semua → **Filed** otomatis.
  - BR-4 **Grant:** user hanya bisa mem-*file* ke jenis yang ia punya `Upload`; berkas yang cocok ke jenis
    lain → `NeedsReview` alasan *tanpa hak*.
  - BR-5 **Bentrok:** jenis + nomor sudah punya dokumen aktif → `NeedsReview` dengan pilihan *Jadikan
    versi baru* (butuh `Replace`), *Lampirkan sebagai halaman tambahan* (P2), atau *Buang*.
  - BR-5a **Satu berkas = satu nomor:** upload baru tidak boleh berupa satu scan untuk rentang nomor
    (`…-49-52`, `S.D 028`); nama berpola rentang → `Rejected` dengan pesan untuk memecah scan per nomor
    (OI-10). Rentang hanya dikenali untuk berkas hasil migrasi (FR-15 BR-3).
  - BR-6 **Batch:** satu `efl_upload_batch` per sesi upload; ringkasan (filed / review / rejected) dan
    notifikasi saat selesai.
  - BR-7 Inbox hanya terlihat oleh pemiliknya dan supervisor intake (`efiling-intake-supervise`);
    item `NeedsReview` > 14 hari diingatkan harian; tidak dihapus otomatis.
- **Kondisi Error:** MinIO / ERP tidak tersedia → item tetap `Validating`/`Matching`, job di-*retry* dengan
  *backoff*; tidak pernah jatuh ke *Filed* tanpa validasi ERP.
- **Prioritas:** P0

#### FR-05: Upload Langsung dari Nomor Transaksi

- **User Story:** US-03
- **Deskripsi:** Dari halaman pencarian, Dossier, atau modul lain: pilih jenis + nomor (combobox ERP) +
  PDF → melewati pipeline yang sama (FR-04) dengan jenis/nomor sudah pasti.
- **Prioritas:** P0

### 5C. Source Adapter ERP

#### FR-06: Kontrak Adapter

- **Deskripsi:** Interface `ErpSourceAdapter` dengan implementasi per keluarga (§L-4): `Invoice`,
  `DeliveryNote`, `TaxInvoice`, `Kwitansi`, `Payment`, `PaymentReference`, `Receipt`, `PurchaseOrder`,
  `Bill`, `GoodsReceipt`, `GlJournal`, `Adjustment`, `Mcm`, dan `Manual` (tanpa ERP).
- **Operasi:** `find(type, number)` → metadata (tanggal, tahun, mitra, nilai, departemen, status);
  `search(type, term)` untuk combobox; `listExpected(rule, period)` untuk coverage;
  `related(type, number)` → daftar (jenis, nomor) terkait untuk Dossier.
- **Business Rules:**
  - BR-1: Query dari legacy (§L-2, §L-10) diterjemahkan ulang dengan binding parameter; tiap adapter punya
    test dengan fixture SQLite.
  - BR-2: Metadata hasil `find` disalin ke dokumen saat *filed* (tanggal, mitra, nilai) supaya pencarian
    tidak bergantung pada ERP; disegarkan oleh job harian bila berubah.
  - BR-3: Dibaca lewat koneksi **`oracle_mgthris`** dengan nama tabel ber-schema `MGTDAT.<tabel>` (prefix dari
    env, tidak di-*hardcode*); DBA memberi grant **SELECT** ke MGTHRIS untuk tabel §L-3/§L-10 (OI-05). Koneksi
    `oracle_mgtdat` tidak dipakai karena login sebagai pemilik schema (bisa menulis). Model ERP yang sudah ada
    di modul lain dipakai lewat kontraknya bila tersedia; sisanya model read-only baru di
    `Modules/Efiling/app/Models/MgtDat`.
- **Prioritas:** P0

### 5D. Archive

#### FR-07: Pencarian & Browse

- **User Story:** US-04
- **Filter:** kategori, jenis, nomor, tahun, tanggal transaksi, mitra (supplier/customer), tanggal upload,
  pengunggah, status, "perlu review".
- **Business Rules:** query hanya ke indeks Oracle (tidak pernah *list* bucket); hanya jenis & tahun yang
  di-grant; paginasi wajib.
- **Prioritas:** P0

#### FR-08: Viewer & Unduh

- **User Story:** US-04, US-11
- **Business Rules:**
  - BR-1: Route menerima **ID dokumen**, tidak pernah path. Cek permission + grant + jendela tahun di
    service; tidak berhak / tidak ada → **404** yang sama (tidak membocorkan keberadaan).
  - BR-2: Penyajian: stream dari MinIO lewat aplikasi dengan dukungan HTTP Range (PDF besar dibuka
    bertahap di PDF.js). Alternatif `temporaryUrl` ≤ 60 detik hanya bila endpoint MinIO terjangkau user (OI-03).
  - BR-3: Header `Content-Disposition: inline`, `X-Content-Type-Options: nosniff`,
    `Cache-Control: private, no-store`, CSP `sandbox` untuk respons PDF.
  - BR-4: `Confidential`: watermark dinamis (nama, NIK, waktu) dicetak server-side ke setiap halaman
    (FPDI) sebelum dikirim; unduh = berkas ber-watermark. `Restricted`: hanya viewer, tanpa unduh, dan
    akses memerlukan alasan yang dicatat.
  - BR-5: Setiap view/unduh → `efl_access_log`.
  - BR-6: Rate limit per user (mis. 300 dokumen/jam, dapat dikonfigurasi); melewati ambang → notifikasi ke
    `efiling-admin` (US-17), bukan blokir diam-diam.
- **Prioritas:** P0 (BR-1–3, 5), P1 (BR-4, 6)

#### FR-09: Versi & Ganti Berkas

- **Business Rules:** dokumen = entitas logis (jenis + nomor); berkas = versi (`efl_document_file`).
  Ganti → versi baru `Current`, versi lama `Superseded` (objek tetap ada, Object Lock tetap berlaku).
  Wajib alasan; kemampuan `Replace`.
- **Prioritas:** P0

#### FR-10: Hapus, Pulihkan, Retensi

- **Business Rules:**
  - BR-1: Hapus = status `Deleted` + alasan + pelaku; tidak tampil di pencarian. Objek **tidak dihapus**.
  - BR-2: Pulihkan oleh `efiling-admin`.
  - BR-3: Objek hanya bisa dihapus permanen sesudah masa Object Lock lewat, lewat job
    `efiling:apply-retention` yang mencantumkan daftar kandidat dan **memerlukan persetujuan** admin
    sebelum eksekusi; hasilnya `Purged` (baris indeks tetap sebagai jejak).
  - BR-4: Jenis dengan mode `Compliance` tidak bisa dihapus permanen oleh siapa pun sebelum retensi
    (dijamin MinIO, bukan hanya aplikasi).
- **Prioritas:** P0

### 5E. Coverage

#### FR-11: Dashboard & Laporan Kelengkapan

- **User Story:** US-07
- **Deskripsi:** Pengganti EFILL_011 "Check Scanned Documents".
- **Business Rules:**
  - BR-1: Job harian `efiling:snapshot-coverage` menghitung per aturan × bulan: jumlah transaksi wajib,
    sudah ada scan, belum, melewati SLA → `efl_coverage_snapshot`. Dashboard membaca snapshot (cepat);
    drill-down ke daftar transaksi dihitung langsung (adapter + indeks).
  - BR-2: Dokumen yang mencakup banyak nomor (`efl_document_ref`) menghitung semua nomornya.
  - BR-3: Notifikasi mingguan ke pemilik departemen aturan: jumlah yang melewati SLA.
  - BR-4: Export Excel via job; kolom nomor dipaksa teks.
- **Prioritas:** P0

### 5F. Dossier (modul terkait)

#### FR-12: Dossier Transaksi

- **User Story:** US-05, US-06
- **Deskripsi:** Satu halaman transaksi → dokumen utama + dokumen terkait (dari `related()` adapter)
  dengan status ada / belum dan tombol lihat / upload. Tiga daftar kerja pengganti layar legacy:

| Halaman | Pengganti | Isi |
|---|---|---|
| **Payment Supporting Document** | EFILL_007 + `Payment_File/231_0001` (identik) | Payment (BPS/BPJ/CPS/…) → dokumen yang dibayar (NSBR/SSBR/LPO/…) + MCM; filter "hanya yang belum lengkap" |
| **Proof of Payment** | EFILL_013 | Payment akun 203* → MCM + item yang di-*match* + PO |
| **Invoice Breakdown** | EFILL_010 | Invoice → FP, DN, KWI; tombol Bundle (FR-13) |

- **Business Rules:**
  - BR-1: Mode hanya-nomor (`efiling-dossier-numbers-only`) untuk grup yang di legacy berstatus
    `IS_READ_ONLY` — melihat daftar & status tanpa membuka PDF.
  - BR-2: Filter area user (legacy `USER_AREA`) **tidak dibangun** (OI-04); akses dibatasi oleh grant jenis saja.
  - BR-3: Default periode: **bulan kalender** berjalan, bukan periode tgl 26 (OI-16).
- **Prioritas:** P0 (Payment, Invoice), P1 (Proof of Payment)

### 5G. Bundle (modul terkait)

#### FR-13: Profil Bundle & Eksekusi

- **User Story:** US-06, US-12
- **Deskripsi:** Penggabungan beberapa dokumen menjadi satu PDF, generik lewat profil:
  `efl_bundle_profile` (kode, nama, jenis hasil, pemicu) + `efl_bundle_profile_part` (urutan, jenis
  sumber, cara menemukan nomor dari dokumen induk, wajib/opsional).
- **Profil awal:**

| Profil | Pengganti | Bagian | Pemicu |
|---|---|---|---|
| `GRN_SJ` | GRN Merging Document `2T_0009/0010` | GRN (LGRN/CGRN/NSGRN/…) + SJGRN | Otomatis: job harian untuk GRN yang sudah di-approve HOD MC dan kedua dokumennya ada; manual dari daftar |
| `INV_PACK` | EFILL_010_MERGE | Invoice + FP + DN + KWI | Manual dari Invoice Breakdown |

- **Business Rules:**
  - BR-1: Hasil bundle disimpan sebagai **dokumen baru** jenis hasil (mis. `GRN_BUNDLE`) yang
    mereferensikan sumbernya; **berkas sumber tidak pernah ditimpa** (memperbaiki §L-10).
  - BR-2: Mesin merge = library PHP (usulan `setasign/fpdi`, MIT) di job antrean; tidak ada `exec()`.
  - BR-3: Bila salah satu sumber diganti versinya, bundle ditandai *usang* dan dibangun ulang otomatis
    (profil otomatis) atau ditawarkan (manual).
  - BR-4: Bundle manual ad-hoc (pilih beberapa dokumen) → hasil sementara di `minio_private/merges/`
    TTL 7 hari, tidak diarsip.
  - BR-5: Riwayat eksekusi di `efl_bundle_run` (pengganti `GRN_MERGE_DOCS_DATA`).
- **Prioritas:** P0 (`GRN_SJ`), P1 (`INV_PACK`, ad-hoc)

### 5H. Dispatch (modul terkait)

#### FR-14: Kirim Dokumen ke Pihak Luar

- **User Story:** US-13
- **Deskripsi:** Pengganti Sending Document — Faktur Pajak Advance (`2J1_0003`). Generik via
  `efl_dispatch_profile` (kode, nama, adapter daftar kandidat, jenis lampiran, template email, grup
  penerima internal CC).
- **Profil awal:** `FP_ADVANCE` — faktur pajak uang muka per customer (query §L-10), lampiran
  `FP_SALES` per nomor.
- **Business Rules:**
  - BR-1: Penerima customer dari `efl_dispatch_contact` (customer code MGTDAT + email + aktif), dikelola
    di UI dengan import Excel. Penerima internal = Role atau daftar email di profil.
  - BR-2: Email dikirim lewat Mailer Laravel (kredensial `.env`) di job antrean; subject & isi dari
    template Blade profil.
  - BR-3: Usulan keamanan (OI-14, menunggu Finance AR): kirim **tautan unduh bertanda tangan** berlaku N hari (default) alih-alih
    lampiran, dengan opsi lampiran untuk customer yang mensyaratkan.
  - BR-4: Setiap pengiriman → `efl_dispatch` + `efl_dispatch_item` (siapa, ke mana, dokumen mana, status
    terkirim/gagal); dokumen yang sudah pernah terkirim ditandai di daftar (pengganti `LOG_SENDING_MAIL_CUST`).
  - BR-5: Dokumen yang belum ada di arsip tidak bisa dikirim; ditampilkan sebagai kekurangan.
- **Prioritas:** P1

### 5I. Integrasi & Migrasi

#### FR-15: Migrasi Doc_Folder → MinIO

- **User Story:** US-16
- **Deskripsi:** Command `efiling:migrate-legacy {--folder=} {--year=}` membaca mount read-only
  (`efiling.legacy.root`, default `/mnt/efilling/Doc_Folder`).
- **Business Rules:**
  - BR-1: Resumable & idempoten per folder/tahun; kunci idempoten = path relatif legacy + ukuran + SHA-256.
  - BR-2: Folder legacy → jenis lewat alias (FR-01b); nama berkas → nomor lewat parser; nomor divalidasi
    ke adapter (batch). Tidak terurai / tidak ada di ERP → dokumen tetap dibuat dengan `needs_review`
    + alasan (berkas tetap bisa dilihat), masuk antrean review.
  - BR-3: Kasus khusus dari §L-6/§L-10: nama rentang (`…-49-52`, `S.D 028`) → satu dokumen banyak ref;
    `MERGE_FILE/`, `KWITANSI_BREAKDOWN/…_MERGE`, `GRN_MERGE/` → dokumen bundle; berkas GRN yang sudah
    tertimpa hasil merge legacy **cukup diberi tanda** `LegacyMergedOverwrite` (tidak ada re-scan sumber, OI-11).
  - BR-3a: Nama folder legacy yang mirip (`LDN` vs `LDN SALES`, `SJWDN` vs `WDN`) **tetap jenis terpisah** —
    isi dokumennya berbeda (OI-17); tidak ada penggabungan jenis saat migrasi.
  - BR-4: Metadata tanggal upload diambil dari *mtime* berkas; pengunggah = `MIGRATION`.
  - BR-5: Verifikasi sesudah unggah (ukuran + checksum MinIO); laporan rekonsiliasi per folder di
    `efl_migration_run`; selisih harus 0 sebelum cutover.
  - BR-6: Throttle & jadwal di luar jam kerja (host SMB adalah server produksi legacy).
  - BR-7: **Delta sync** `efiling:sync-legacy` berjalan selama masa transisi; dimatikan saat cutover.
  - BR-8: Retensi Object Lock diterapkan pada objek hasil migrasi dihitung dari **tanggal transaksi**,
    bukan tanggal migrasi.
  - BR-9: Target **migrasi penuh** seluruh Doc_Folder (OI-15). Perkiraan kasar: salin 462 GB di LAN ±beberapa
    jam; beban utama adalah pemrosesan 577 ribu berkas (checksum, parser, validasi ERP batch) → beberapa
    malam di luar jam kerja, bertahap per jenis/tahun. Angka pasti dari uji throughput Fase 0. Bila uji
    menunjukkan tidak layak, *fallback*: legacy tetap read-only sebagai arsip lama, sistem baru hanya
    menerima upload baru (bridge FR-16 dan coverage historis tidak tersedia untuk data lama).
- **Prioritas:** P0

#### FR-16: Legacy Link Bridge

- **User Story:** US-15
- **Deskripsi:** Aplikasi legacy (Shipping, Reports, Master Batch, Taxation, Costing, Sales, Event, TWT —
  25 ribu view/tahun, §L-9) menautkan `…/webapps/Doc_Folder/{path}`.
- **Business Rules:**
  - BR-1: Route `efiling/legacy/{path}` (login wajib) → cari dokumen berdasar `legacy_path`; bila tidak
    ada (dokumen baru yang tidak pernah ada di Doc_Folder), path diurai lewat alias (FR-01b) menjadi jenis +
    nomor → cek grant → redirect ke viewer. Tidak ditemukan / tidak berhak → 404.
  - BR-2: Satu-satunya perubahan di sisi legacy adalah aturan *rewrite* di Apache `mgtapps:4433` (OI-06),
    sehingga aplikasi legacy tidak perlu diubah satu per satu. Diterapkan **dua tahap**:
    - **Tahap 1 — saat cutover (Fase 3): redirect hanya bila berkas tidak ada di disk.**
      `RewriteCond %{REQUEST_FILENAME} !-f` sebelum aturan redirect. Berkas lama tetap dilayani Apache persis
      seperti sekarang (iframe & tautan legacy tidak berubah); hanya dokumen baru — yang sesudah
      write-freeze hanya ada di MinIO — diarahkan ke bridge.
    - **Tahap 2 — redirect semua (Fase 5),** sesudah layar legacy yang menampilkan PDF dalam `<iframe>`
      sudah diganti atau dimatikan (BR-5). Baru di tahap ini akses statis tanpa login tertutup.
  - BR-2a: Yang **tidak** terpengaruh aturan ini: aplikasi lain di server yang sama (aturan hanya menangkap
    prefix `/webapps/Doc_Folder/`) dan kode PHP legacy yang membaca berkas langsung dari disk
    (`file_exists`, `copy`, merge PDFtk di EFILL_007/010/011, GRN Merge) — tidak lewat Apache.
  - BR-2b **Prosedur perubahan:** uji dulu di Apache staging legacy atau dibatasi ke IP penguji
    (`RewriteCond %{REMOTE_ADDR}`); `apachectl configtest` sebelum reload; reload *graceful* (koneksi
    berjalan tidak diputus). **Rollback** = hapus aturan + reload graceful; berkas Doc_Folder tidak dihapus
    atau dipindah (hanya write-freeze), sehingga legacy langsung kembali seperti semula.
  - BR-2c Hanya relevan bila migrasi penuh berjalan (FR-15 BR-9).
  - BR-3: Akses lewat bridge dicatat dengan aplikasi asal (header `Referer`) untuk mengukur kapan bridge
    bisa dimatikan.
  - BR-4: Sesudah write-freeze, layar legacy yang mengecek keberadaan berkas di disk (`file_exists`) akan
    melaporkan dokumen baru sebagai "belum ada" — terlepas dari aturan Apache. Layar e-Filing/GRN Merge/Costing/
    Payment dimatikan di cutover; **sebelum Fase 3, Shipping, Reports, Master Batch, Taxation, Costing, Sales,
    Event, TWT, KYP ditelusuri** (§L-10 "belum ditelusuri"): apakah mengecek disk dan/atau memakai iframe.
    Yang mengecek disk perlu diganti tautannya ke kontrak FR-17 atau diterima sebagai keterbatasan transisi.
  - BR-5: Layar yang menampilkan PDF dalam `<iframe>` (EFILL_003/005/010 terbukti; aplikasi lain per BR-4)
    tidak bisa memakai bridge: sistem baru beda origin (`apps.mutugading.com:15039`) dengan
    `X-Frame-Options: SAMEORIGIN`, dan halaman login tidak bisa dipakai di dalam iframe. Karena itu iframe
    hanya dilayani Tahap 1 (berkas lama dari disk); Tahap 2 menunggu layar tersebut diganti.
- **Prioritas:** P0 (syarat cutover)

#### FR-17: Kontrak untuk Modul Lain

- **User Story:** US-14
- **Deskripsi:** `Modules\Efiling\Contracts\EfilingDocuments` (bind di provider) + komponen Blade:
  - `exists(type, number)`, `existsMany(type, numbers)`, `find(type, number)`, `viewUrl(document)`,
    `attach(type, number, UploadedFile, actor)` (memakai pipeline FR-05).
  - `<x-efiling::document-link :type :number />` — badge "Lihat dokumen" / "Belum ada" / tombol upload
    sesuai grant.
- **Konsumen pertama:**
  - **Transporter** — `DocumentFileLocator` / job `transporter:scan-delivery-documents` beralih dari mount
    ke `existsMany('LDN', …)`; uji shadow seperti F-10.12 PRD Transporter; dicatat di DECISIONS Transporter.
  - **Finance Shipment** — tautan dokumen NSBR/SSBR/EBJV/CSBR/IBJV/LPO (pengganti 17.947 view/tahun, 15 user,
    dari Shipping legacy). Kandidat halaman: Export SI (list/detail), Import Files, BIM, Shipment Control /
    Reconciliation — pemetaan jenis → halaman dikonfirmasi pemilik modul (OI-19).
  - **MaterialControl / GRN** — status bundle GRN.
- **Business Rules:** modul lain tidak boleh menyentuh model/tabel `efl_*` langsung (Module Rule 1–2).
- **Prioritas:** P0 (kontrak + Transporter), P1 (Shipment, MC)

#### FR-18: Audit

- **User Story:** US-10, US-17
- **Business Rules:**
  - BR-1: Mutasi (filed, ganti, hapus, pulihkan, master, grant, bundle, dispatch) → Spatie Activitylog
    (`LogsActivityWithDescription`).
  - BR-2: Akses baca → `efl_access_log` (dokumen, aksi, user, IP, asal: viewer/bridge/kontrak/dispatch).
  - BR-3: Halaman Audit: `efiling-audit-view` semua; user lain hanya miliknya. Export via job.
  - BR-4: Retensi `efl_access_log` (jejak siapa membuka/mengunduh dokumen apa, kapan, dari mana; pengganti
    `EFILL_LOG_ACCESS`). Volume kecil (±25 ribu baris/tahun) → usulan **disimpan 10 tahun** mengikuti retensi
    dokumen pajak, purge lewat job yang sama — OI-12.
- **Prioritas:** P0

---

## 6. State Machine

### 6.1 Inbox item (`EflInboxStatusEnum`)

| State | Deskripsi |
|-------|-----------|
| `Uploaded` (0) | Objek di `inbox/`, menunggu job |
| `Validating` (1) | Cek isi PDF, hash |
| `Matching` (2) | Parse nama, validasi ERP, cek bentrok |
| `NeedsReview` (3) | Butuh keputusan user (alasan tercatat) |
| `Filed` (4) | Menjadi berkas dokumen; selesai |
| `Rejected` (5) | Gagal validasi isi |
| `Discarded` (6) | Dibuang user |

| Dari | Ke | Trigger | Actor |
|------|----|---------|-------|
| Uploaded | Validating | Job | Sistem |
| Validating | Matching / Rejected / NeedsReview (keamanan) | Job | Sistem |
| Matching | Filed / NeedsReview | Job | Sistem |
| NeedsReview | Filed | Pilih jenis + nomor / versi baru | Pemilik / supervisor |
| NeedsReview, Rejected | Discarded | Buang | Pemilik / supervisor |

### 6.2 Dokumen (`EflDocumentStatusEnum`) dan berkas (`EflFileStatusEnum`)

| Dokumen | Deskripsi | | Berkas | Deskripsi |
|---|---|---|---|---|
| `Active` | Ada berkas `Current` | | `Current` | Versi berlaku |
| `Deleted` | Disembunyikan, bisa dipulihkan | | `Superseded` | Versi lama |
| `Purged` | Objek dihapus sesudah retensi | | `Purged` | Objek dihapus sesudah retensi |

```
Active ──hapus──▶ Deleted ──pulihkan──▶ Active
   │                 │
   │                 └──retensi lewat + disetujui──▶ Purged
   └──ganti berkas: Current ▶ Superseded (dokumen tetap Active)
```

### 6.3 Bundle run / Dispatch: `Queued → Running → Succeeded | Failed` (retry dari `Failed`).

---

## 7. Data Model

> Schema **MGTHRIS**, koneksi `oracle_mgthris`. Tidak ada tabel legacy yang dipakai.
> Tabel baru berawalan `efl_` (pola modul: `transp_`, `ship_`, `rpt_`); kolom berawalan singkatan tabel.
> PK `VARCHAR2(30)` dari `SysIdHelper` (sequence baru di `HmMstSequences`: `EFL_DOC`, `EFL_FILE`, `EFL_INBOX`, …);
> migrasi massal memakai `generateBatch`. Boolean `NUMBER(1)`. Audit `*_created_by/_timestamp`,
> `*_modified_by/_timestamp` di setiap tabel master & transaksi. Migrasi idempoten dengan `migrationKey`
> dan harus jalan di SQLite (CI).
>
> **Dipakai ulang dari environment Laravel:** `roles` / `permissions` / `model_has_*` (Spatie),
> `hm_mst_sequences` (SysIdHelper), `activity_log`, `notifications`, `cm_menus` (sidebar), master
> departemen Core (pemilik aturan coverage), `HmEmpData` (user), disk `minio_private` (export/merge sementara).

### 7.1 Prefix Registry

| Prefix | Tabel | Kategori |
|--------|-------|----------|
| `edc_` | `efl_doc_category` | Master |
| `edt_` | `efl_doc_type` | Master |
| `eda_` | `efl_doc_type_alias` | Master |
| `etg_` | `efl_type_grant` | Config |
| `ecr_` | `efl_coverage_rule` | Config |
| `ebp_` | `efl_bundle_profile` | Config |
| `ebq_` | `efl_bundle_profile_part` | Config |
| `edp_` | `efl_dispatch_profile` | Config |
| `ect_` | `efl_dispatch_contact` | Master |
| `edoc_` | `efl_document` | Core |
| `efil_` | `efl_document_file` | Core |
| `edr_` | `efl_document_ref` | Core |
| `eub_` | `efl_upload_batch` | Transaksi |
| `eib_` | `efl_inbox_item` | Transaksi |
| `ebr_` | `efl_bundle_run` | Transaksi |
| `eds_` | `efl_dispatch` | Transaksi |
| `edi_` | `efl_dispatch_item` | Transaksi |
| `ecs_` | `efl_coverage_snapshot` | Laporan |
| `eal_` | `efl_access_log` | Audit |
| `emr_` | `efl_migration_run` | Audit |

### 7.2 Tabel

#### Master & konfigurasi

**`efl_doc_category`** — `edc_code` PK (VARCHAR2 20), `edc_name`, `edc_default_retention_years` NUMBER(3),
`edc_sort_order`, `edc_is_active`, audit.

**`efl_doc_type`**

| Column | Type | Notes |
|--------|------|-------|
| `edt_code` | VARCHAR2(20) PK | `NSBR`, `FP_SALES` |
| `edt_name` | VARCHAR2(100) | |
| `edt_edc_code` | VARCHAR2(20) FK | kategori |
| `edt_source_adapter` | VARCHAR2(30) | `EflSourceAdapterEnum` |
| `edt_erp_txn_code` | VARCHAR2(20) | kode transaksi ERP bila beda dari kode jenis |
| `edt_number_pattern` | VARCHAR2(200) | regex nomor |
| `edt_year_basis` | VARCHAR2(10) | `DocNumber` / `DocDate` |
| `edt_confidentiality` | NUMBER(1) | 0 Normal, 1 Confidential, 2 Restricted |
| `edt_retention_years` | NUMBER(3) | null = ikut kategori |
| `edt_retention_mode` | VARCHAR2(10) | `Governance` / `Compliance` |
| `edt_max_size_kb` | NUMBER(8) | |
| `edt_is_bundle_output` | NUMBER(1) | jenis hasil bundle |
| `edt_sort_order`, `edt_is_active` | | |

**`efl_doc_type_alias`** — `eda_sys_id` PK, `eda_edt_code` FK, `eda_alias` VARCHAR2(60) **unik**,
`eda_kind` (`ScannerPrefix` / `ArchivePrefix` / `LegacyFolder`), `eda_sub_variant` (mis. `SALES`), aktif.

**`efl_type_grant`**

| Column | Type | Notes |
|--------|------|-------|
| `etg_sys_id` | VARCHAR2(30) PK | |
| `etg_grantee_type` | VARCHAR2(10) | `Role` / `User` |
| `etg_grantee_id` | VARCHAR2(60) | role id / user id |
| `etg_scope_type` | VARCHAR2(10) | `Type` / `Category` |
| `etg_scope_code` | VARCHAR2(20) | |
| `etg_can_view`, `etg_can_upload`, `etg_can_replace`, `etg_can_delete` | NUMBER(1) | |
| `etg_year_from`, `etg_year_to` | NUMBER(4) | opsional |
| `etg_valid_until` | DATE | opsional |
| `etg_remark` | VARCHAR2(200) | |

Index `(etg_grantee_type, etg_grantee_id)`, `(etg_scope_type, etg_scope_code)`.

**`efl_coverage_rule`** — `ecr_sys_id` PK, `ecr_edt_code` FK, `ecr_effective_from` DATE,
`ecr_filter_json` CLOB (parameter adapter), `ecr_sla_days` NUMBER(4), `ecr_owner_dept_id`, aktif.

**`efl_bundle_profile`** — `ebp_code` PK, `ebp_name`, `ebp_output_edt_code` FK, `ebp_trigger`
(`Auto` / `Manual`), `ebp_candidate_adapter`, aktif.
**`efl_bundle_profile_part`** — `ebq_sys_id` PK, `ebq_ebp_code` FK, `ebq_seq`, `ebq_edt_code`,
`ebq_number_resolver` (`Self` / `Prefixed` / `AdapterRelated`), `ebq_is_required`.

**`efl_dispatch_profile`** — `edp_code` PK, `edp_name`, `edp_candidate_adapter`, `edp_attach_edt_code`,
`edp_mail_template`, `edp_delivery_mode` (`Link` / `Attachment`), `edp_link_ttl_days`,
`edp_internal_cc_role`, aktif.
**`efl_dispatch_contact`** — `ect_sys_id` PK, `ect_party_type` (`Customer` / `Supplier`), `ect_party_code`,
`ect_email`, `ect_name`, `ect_edp_code` (null = semua profil), aktif. Unik `(party_type, party_code, email, edp_code)`.

#### Inti dokumen

**`efl_document`** — entitas logis (jenis + nomor)

| Column | Type | Notes |
|--------|------|-------|
| `edoc_sys_id` | VARCHAR2(30) PK | |
| `edoc_edt_code` | VARCHAR2(20) FK | |
| `edoc_doc_number` | VARCHAR2(60) | tanpa prefix jenis; null bila belum terurai (migrasi) |
| `edoc_doc_year` | NUMBER(4) | |
| `edoc_doc_date` | DATE | dari ERP |
| `edoc_party_code` / `edoc_party_name` | VARCHAR2 | supplier/customer dari ERP |
| `edoc_amount` | NUMBER(18,2) | dari ERP, opsional |
| `edoc_status` | NUMBER(1) | `EflDocumentStatusEnum` |
| `edoc_current_file_id` | VARCHAR2(30) | |
| `edoc_erp_verified` | NUMBER(1) | |
| `edoc_needs_review` / `edoc_review_reason` | | |
| `edoc_legacy_path` | VARCHAR2(500) | path relatif Doc_Folder, untuk bridge FR-16 |
| `edoc_retain_until` | DATE | dari jenis + tanggal transaksi |
| `edoc_deleted_by` / `_timestamp` / `_reason` | | |
| audit | | |

Index: unik `(edoc_edt_code, edoc_doc_number)` untuk dokumen bernomor (dijaga service untuk baris
null — Oracle 11g tanpa partial index), `(edoc_edt_code, edoc_doc_year)`, `edoc_party_code`,
`edoc_legacy_path`, `edoc_needs_review`.

**`efl_document_file`** — versi berkas

| Column | Type | Notes |
|--------|------|-------|
| `efil_sys_id` | VARCHAR2(30) PK | |
| `efil_edoc_sys_id` | VARCHAR2(30) FK | |
| `efil_version` | NUMBER(5) | |
| `efil_status` | NUMBER(1) | `Current` / `Superseded` / `Purged` |
| `efil_disk` | VARCHAR2(30) | `efiling` |
| `efil_object_key` | VARCHAR2(500) | unik |
| `efil_object_version_id` | VARCHAR2(100) | version id MinIO |
| `efil_original_name` | VARCHAR2(255) | |
| `efil_size_bytes` | NUMBER(15) | |
| `efil_sha256` | CHAR(64) | index |
| `efil_page_count` | NUMBER(5) | |
| `efil_security_flags` | VARCHAR2(100) | JS / Launch / Embedded |
| `efil_origin` | VARCHAR2(20) | `Intake` / `Direct` / `Bundle` / `Migration` / `Contract` |
| `efil_eib_sys_id` | VARCHAR2(30) | asal inbox |
| `efil_replace_reason` | VARCHAR2(500) | |
| audit (`created_by` = pengunggah) | | |

**`efl_document_ref`** — satu dokumen → banyak (jenis, nomor): rentang nomor, bundle → sumbernya.
`edr_sys_id` PK, `edr_edoc_sys_id` FK, `edr_edt_code`, `edr_doc_number`, `edr_role` (`Primary` / `Covers` /
`BundleSource`). Index `(edr_edt_code, edr_doc_number)` — dipakai coverage, Dossier, kontrak.

#### Intake

**`efl_upload_batch`** — `eub_sys_id` PK, `eub_user_id`, `eub_total`, `eub_filed`, `eub_review`,
`eub_rejected`, `eub_status`, audit.

**`efl_inbox_item`** — `eib_sys_id` PK, `eib_eub_sys_id` FK, `eib_owner_id`, `eib_status`,
`eib_object_key`, `eib_original_name`, `eib_size_bytes`, `eib_sha256`, `eib_page_count`,
`eib_guess_edt_code`, `eib_guess_number`, `eib_reason_code` (`EflReviewReasonEnum`: `UnknownType`,
`NumberNotInErp`, `Duplicate`, `Conflict`, `NoGrant`, `SecurityFlag`, `ParseFailed`), `eib_reason_detail`,
`eib_edoc_sys_id` (setelah filed), audit.

#### Bundle, Dispatch, Coverage, Audit

**`efl_bundle_run`** — `ebr_sys_id`, `ebr_ebp_code`, `ebr_anchor_edt_code`, `ebr_anchor_number`,
`ebr_status`, `ebr_output_edoc_sys_id`, `ebr_error`, `ebr_is_stale`, audit.

**`efl_dispatch`** — `eds_sys_id`, `eds_edp_code`, `eds_party_code`, `eds_to`, `eds_cc` (CLOB),
`eds_status`, `eds_sent_timestamp`, `eds_error`, audit.
**`efl_dispatch_item`** — `edi_sys_id`, `edi_eds_sys_id`, `edi_edoc_sys_id`, `edi_efil_sys_id`,
`edi_link_expires_at`, `edi_downloaded_at`.

**`efl_coverage_snapshot`** — `ecs_sys_id`, `ecs_ecr_sys_id`, `ecs_period` (YYYYMM), `ecs_expected`,
`ecs_present`, `ecs_missing`, `ecs_overdue`, `ecs_taken_at`. Unik `(ecs_ecr_sys_id, ecs_period, ecs_taken_at)`.

**`efl_access_log`** — `eal_sys_id`, `eal_edoc_sys_id`, `eal_efil_sys_id`, `eal_action` (`View` /
`Download` / `Bundle` / `Dispatch` / `ContractRead`), `eal_channel` (`Viewer` / `Bridge` / `Contract`),
`eal_user_id`, `eal_ip`, `eal_referer` (VARCHAR2 300), `eal_timestamp`. Index `(eal_user_id, eal_timestamp)`,
`eal_edoc_sys_id`.

**`efl_migration_run`** — `emr_sys_id`, `emr_folder`, `emr_year`, `emr_files_seen`, `emr_bytes_seen`,
`emr_uploaded`, `emr_skipped`, `emr_failed`, `emr_needs_review`, `emr_started_at`, `emr_finished_at`, `emr_status`.

> Ruang OCR (Phase 3): tabel `efl_document_text` (`edoc` + CLOB teks) dapat ditambahkan tanpa mengubah
> tabel di atas.

### 7.3 MinIO

| Hal | Keputusan |
|---|---|
| Bucket | `MINIO_EFILING_BUCKET` khusus, disk Laravel baru `efiling` (driver `s3`, `visibility=private`, `throw=true`) |
| Akses | Service account khusus aplikasi dengan policy hanya untuk bucket ini; tidak ada akses anonim; console MinIO tidak dibuka ke jaringan user |
| Enkripsi | SSE-KMS (MinIO KES) atau SSE-S3 minimal — OI-02 |
| Versioning | **Aktif** — ganti berkas tidak pernah menghilangkan objek lama |
| Object Lock | **Aktif sejak bucket dibuat** (tidak bisa diaktifkan belakangan). Retensi per objek dari `edoc_retain_until`; mode per jenis |
| Kunci objek | `docs/{category}/{type}/{year}/{type}-{number}/{efil_sys_id}.pdf`; dokumen tanpa nomor: `docs/{category}/{type}/{year}/_unassigned/{efil_sys_id}.pdf`; inbox: `inbox/{yyyy}/{mm}/{eib_sys_id}.pdf` (lifecycle: hapus 90 hari sesudah `Filed`/`Discarded`) |
| Integritas | `efil_sha256` + checksum MinIO; job mingguan `efiling:verify-storage` sampling objek vs indeks |
| Backup | Replikasi bucket ke site kedua (MinIO site replication) — OI-02 |
| Sementara | Merge ad-hoc & export di `minio_private` (`merges/`, `exports/`) dengan TTL |

---

## 8. Integration Points

| # | Sistem | Arah | Pattern | Catatan |
|---|--------|------|---------|---------|
| INT-1 | Oracle ERP MGTDAT | Read | Source Adapter via `oracle_mgthris` + `MGTDAT.<tabel>` | Tabel di §L-3 & §L-10; grant SELECT ke MGTHRIS (OI-05) |
| INT-2 | MinIO | Read/Write | Disk `efiling` | §7.3 |
| INT-3 | Modul Transporter | Read | Kontrak `EfilingDocuments` | Mengganti baca mount; shadow test |
| INT-4 | Finance Shipment, MaterialControl | Read | Kontrak + komponen Blade | FR-17 |
| INT-5 | Core | Read | Departemen, menu sidebar, user | |
| INT-6 | Doc_Folder SMB | Read | Mount read-only | Migrasi & delta sync saja; dicabut sesudah cutover |
| INT-7 | Apache legacy (`mgtapps:4433`) | Redirect | Rewrite `/webapps/Doc_Folder/*` → bridge; Tahap 1 hanya berkas tak ada di disk, Tahap 2 semua | FR-16 BR-2 |
| INT-8 | SMTP | Write | Mailer Laravel | Dispatch FR-14 |
| INT-9 | ClamAV (opsional, di luar MVP) | Call | Socket/HTTP | FR-04 BR-1 (OI-18) |

**Error handling umum:** objek MinIO ditulis lebih dulu, lalu indeks di-*commit*; bila commit gagal, objek
dikompensasi (dihapus selama belum terkunci retensi — inbox tidak dikunci). ERP tidak tersedia → intake
berhenti di `Matching` dan di-*retry*; tidak pernah *Filed* tanpa validasi. Transporter mempertahankan
perilaku F-10.3b-nya (gagal bersih, tidak menandai hilang).

---

## 9. Non-Functional Requirements

| Kategori | Requirement | Target |
|----------|-------------|--------|
| Performance | Pencarian (paginasi indeks) | < 1 detik |
| Performance | Time-to-first-byte viewer | < 1,5 detik |
| Performance | Intake: dari upload sampai *Filed* | < 30 detik per berkas pada beban normal |
| Performance | Dashboard coverage (snapshot) | < 1 detik |
| Scalability | Volume | 580 ribu berkas awal + ±110 ribu/tahun (±9 ribu/bulan, §L-9); 0,5 TB + ±90 GB/tahun |
| Scalability | Upload batch | ≥ 100 berkas per batch; maks 50 MB/berkas, 500 MB/batch (FR-01 BR-5) |
| Security | Autentikasi | Session aplikasi; setiap route ber-`permission:` + grant jenis dicek di service (bukan hanya di menu) |
| Security | Penyimpanan | Bucket privat, terenkripsi, versioning, Object Lock; kredensial hanya di `.env` |
| Security | Input | Tidak ada path dari request; nama & kunci dibentuk sistem; validasi isi PDF; tanpa `exec()` |
| Security | Respons | `nosniff`, `no-store`, CSP sandbox, Range; watermark untuk Confidential |
| Security | Deteksi | Rate limit & alarm unduhan massal |
| Audit | Mutasi + akses baca | 100% tercatat |
| Availability | Uptime | 99,5%; MinIO down → pesan jelas, intake menunggu, tidak ada perubahan indeks parsial |
| Observability | Log channel | `efiling`, `efiling_intake`, `efiling_migration`, `efiling_coverage`, `efiling_bundle`, `efiling_dispatch`, `efiling_retention` |
| Queue | Antrean | Intake & bundle di `default`; migrasi di antrean khusus `efiling-migration` agar tidak menahan job lain |
| Testing | CI | Pest + SQLite + `Storage::fake('efiling')`; adapter ERP dengan fixture; e2e Playwright untuk intake & viewer |

---

## 10. MVP Scope

| Fitur | Prioritas | Fase |
|-------|-----------|------|
| FR-01/01b Master kategori, jenis, alias | P0 | 1 |
| FR-02 Grant akses + import Excel | P0 | 1 |
| FR-04/05 Intake pipeline & upload langsung | P0 | 1 |
| FR-06 Source adapter (Invoice, DN, FP, KWI, Payment, PaymentRef, Bill, GRN, PO, GL, Manual) | P0 | 1 |
| FR-07/08 Pencarian & viewer (BR-1–3, 5) | P0 | 1 |
| FR-09/10 Versi, hapus, pulihkan | P0 | 1 |
| FR-18 Audit | P0 | 1 |
| FR-15 Migrasi + delta sync | P0 | 2 |
| FR-17 Kontrak + komponen; konsumen Transporter | P0 | 2 |
| FR-16 Legacy link bridge | P0 | 3 (cutover) |
| FR-03/11 Coverage | P0 | 3 |
| FR-12 Dossier Payment & Invoice | P0 | 3 |
| FR-13 Bundle `GRN_SJ` | P0 | 3 |
| FR-08 BR-4/6 Watermark, rate limit | P1 | 3 |
| FR-12 Proof of Payment | P1 | 4 |
| FR-13 `INV_PACK` & ad-hoc | P1 | 4 |
| FR-14 Dispatch `FP_ADVANCE` | P1 | 4 |
| FR-17 Konsumen Finance Shipment & MC | P1 | 4 |
| FR-10 BR-3 Purge retensi | P1 | 4 (pertama kali relevan bertahun-tahun ke depan) |
| OCR / full-text | — | Phase berikutnya |

### Rencana fase

| Fase | Isi | Keluar fase bila |
|------|-----|------------------|
| **0. Persiapan** | Jawab OI *Blocking*; buat bucket (Object Lock + versioning + enkripsi); uji throughput SMB→MinIO; seed jenis & alias bersama pemilik | OI-02, 05, 06, 09 tertutup |
| **1. Fondasi** | Modul, migrasi tabel, disk, master, grant, intake, adapter inti, pencarian, viewer, versi/hapus, audit, menu sidebar | UAT admin scan & Finance di staging |
| **2. Migrasi & kontrak** | Migrasi per jenis (mulai jenis kecil), antrean review, delta sync; kontrak + Transporter shadow | Rekonsiliasi 100%; shadow Transporter identik |
| **3. Cutover inti** | Coverage, Dossier Payment/Invoice, Bundle GRN, watermark; **write-freeze legacy**, delta terakhir, rewrite Apache Tahap 1 (hanya berkas tak ada di disk, FR-16 BR-2), matikan menu e-Filing/GRN Merge/Costing/Payment legacy | Sign-off Finance, MC, IT |
| **4. Lanjutan** | Proof of Payment, Invoice pack, Dispatch FP Advance (menggantikan Sending Document), konsumen Shipment/MC | per fitur |
| **5. Pensiun legacy** | Rewrite Apache Tahap 2 (semua) sesudah layar iframe legacy diganti (FR-16 BR-5); matikan bridge bila akses via bridge ≈ 0; lepas mount; hapus `Core\EfilingAccessManager` dan model `EfillTypeData`/`EfillUserAccess` (hanya melayani aplikasi legacy) | Tidak ada konsumen legacy |

**Quick win keamanan (tidak menunggu proyek):** rotasi kredensial Oracle & SMTP yang tertulis di source
legacy; nonaktifkan `EFILL_014_DB.php` dan `CST_EFILL_014`; batasi akses `/webapps/Doc_Folder/` ke jaringan
internal (R-8, R-9).

---

## 11. Open Items

| # | Pertanyaan / Open Item | Owner | Status |
|---|------------------------|-------|--------|
| OI-01 | **Blocking.** Konfirmasi daftar layar/fitur yang dipertahankan | Product owner + Finance | **Closed** — `EFILL_003/005/012` tidak dibangun; layar lain sesuai §5 |
| OI-02 | **Blocking.** MinIO: kapasitas ≥ 1 TB, KMS untuk SSE, Object Lock & versioning di bucket baru, replikasi/backup site kedua | IT Infra | Open |
| OI-03 | Endpoint MinIO terjangkau dari jaringan user? (menentukan stream vs `temporaryUrl`) | IT Infra | Open |
| OI-04 | Filter area user (`USER_AREA`) di Payment/Invoice masih dibutuhkan? | Finance | **Closed** — tidak dibutuhkan (FR-12 BR-2) |
| OI-05 | **Blocking.** Akses read-only ke tabel MGTDAT di §L-3/§L-10 (termasuk `mgt_grn_scan`, `ft_reversal_jv`, `ot_tax_invoice_mgt`) | DBA | **Answered** — product owner memberi grant SELECT ke MGTHRIS; adapter pakai `oracle_mgthris` (FR-06 BR-3). Tutup sesudah grant diverifikasi |
| OI-06 | **Blocking.** Persetujuan menambah satu aturan *rewrite* di Apache legacy (`mgtapps:4433`) agar `/webapps/Doc_Folder/*` diarahkan ke bridge (FR-16); daftar aplikasi lain yang menautkan (§L-9) | IT | Open — desain dua tahap + prosedur uji/rollback disetujui product owner (FR-16 BR-2–5); menunggu persetujuan IT dan penelusuran aplikasi legacy (BR-4) |
| OI-07 | Validasi pemilik (departemen) per kategori/jenis dokumen | Product owner | Open — bahan analisa: `EFILING_USER_FOLDER_ACCESS.xlsx` (user × folder, aktivitas 12 bln, admin) |
| OI-08 | Jenis mana yang `Confidential` / `Restricted` (mis. `MBRCP`, BUPOT, PIB)? | Pemilik dokumen | Open — diisi di kolom "Confidential?" pada berkas yang sama |
| OI-09 | **Blocking.** Retensi per kategori & mode (`Compliance` tak bisa diubah sesudah objek terkunci) | Accounting + Tax | Open — pengertian retensi & mode dijelaskan di FR-01 BR-3; usulan 10 th pajak/pembukuan, 5 th lainnya, mode `Governance` |
| OI-10 | Upload baru: masih boleh satu scan untuk rentang nomor? | Finance | **Closed** — tidak boleh (FR-04 BR-5a) |
| OI-11 | GRN yang PDF aslinya sudah tertimpa hasil merge legacy: cukup ditandai, atau perlu re-scan sumber? | MC | **Closed** — cukup ditandai (FR-15 BR-3) |
| OI-12 | Retensi `efl_access_log` | Audit | Open — usulan 10 tahun (FR-18 BR-4) |
| OI-13 | Batas ukuran per berkas & per batch | Finance + IT | Open — usulan 50 MB/berkas, 500 MB/batch, tanpa kompresi lossy; standar setelan scanner (FR-01 BR-5) |
| OI-14 | Dispatch (pengganti Sending Document — Faktur Pajak Advance `2J30000000`): tautan bertanda tangan vs lampiran; template; customer yang wajib lampiran | Finance AR | Open |
| OI-15 | Migrasi penuh SMB→MinIO atau hanya upload baru? Throughput 462 GB | IT | **Answered** — target migrasi penuh; fallback hanya upload baru bila uji throughput Fase 0 tidak layak (FR-15 BR-9) |
| OI-16 | Periode default Dossier: bulan kalender atau periode tgl 26? | Finance | **Closed** — bulan kalender |
| OI-17 | Review 91 jenis legacy: mana yang aktif atau dipensiunkan | Pemilik dokumen | Open — jenis bernama mirip **tetap terpisah** (isi berbeda, FR-15 BR-3a); tersisa: daftar jenis yang dipensiunkan |
| OI-18 | Tersedia ClamAV / antivirus scanning untuk intake? | IT Infra | **Closed** — belum ada; opsional, di luar MVP (FR-04 BR-1) |
| OI-19 | Konsumen di Finance Shipment: jenis dokumen mana tampil di halaman mana? | Pemilik modul Finance | Open — jenis dari log Shipping: NSBR, SSBR, EBJV, CSBR, IBJV, LPO; kandidat halaman di FR-17 |

---

## 12. Risks & Mitigasi

| ID | Risiko | Likelihood | Impact | Mitigasi |
|----|--------|-----------|--------|---------|
| R-1 | Migrasi 462 GB membebani server legacy & jaringan | Tinggi | Sedang | Throttle, jadwal malam, resumable, antrean khusus |
| R-2 | Selisih berkas saat cutover (legacy masih menulis) | Sedang | Tinggi | Delta sync + write-freeze + rekonsiliasi wajib 0 |
| R-3 | Auto-match gagal pada nama tidak baku → beban review besar | Tinggi | Sedang | Alias lengkap sebelum migrasi; migrasi per jenis dengan ukur rasio review; perbaikan alias iteratif |
| R-4 | Object Lock `Compliance` salah set → berkas tidak bisa dihapus bertahun-tahun | Rendah | Tinggi | Default `Governance`; `Compliance` hanya sesudah OI-09 disetujui tertulis |
| R-5 | Transporter kehilangan sumber scan bila mount dicabut terlalu cepat | Sedang | Tinggi | Mount dicabut hanya sesudah shadow kontrak identik (Fase 2) |
| R-6 | Aplikasi legacy rusak saat Doc_Folder static dimatikan | Tinggi | Sedang | Bridge + rewrite Apache dua tahap (FR-16 BR-2), log `Referer` untuk inventaris |
| R-6a | PDF dalam `<iframe>` di layar legacy kosong bila diarahkan ke sistem baru (beda origin, `X-Frame-Options`, login tak bisa di iframe) | Tinggi bila redirect penuh | Sedang | Tahap 1 hanya redirect berkas yang tak ada di disk; Tahap 2 sesudah layar iframe diganti (FR-16 BR-5) |
| R-6b | Salah konfigurasi Apache membuat seluruh aplikasi legacy tidak bisa diakses | Rendah | Tinggi | Uji di staging / batasi IP, `apachectl configtest`, reload graceful, rollback satu baris (FR-16 BR-2b) |
| R-6c | Layar legacy yang mengecek disk menganggap dokumen baru belum ada sesudah write-freeze | Sedang | Sedang | Telusuri aplikasi legacy sebelum Fase 3 (FR-16 BR-4); ganti ke kontrak FR-17 atau terima sebagai keterbatasan transisi |
| R-7 | Kapasitas/backup MinIO kurang | Sedang | Tinggi | OI-02 blocking |
| R-8 | Kredensial legacy sudah terbaca di source | Tinggi | Tinggi | Rotasi segera |
| R-9 | Lubang keamanan legacy terbuka selama transisi | Tinggi | Tinggi | Quick win §10 |
| R-10 | Adapter ERP salah menerjemahkan query legacy → coverage keliru | Sedang | Sedang | Test fixture per adapter + perbandingan hasil dengan layar legacy di staging (pola shadow) |

---

## 13. Acceptance Criteria

| FR | Skenario | Given | When | Then |
|----|---------|-------|------|------|
| FR-04 | Auto-file | User punya grant `Upload` `NSBR`; `NSBR-2026000123` ada di ERP | Upload `NSBR_2026000123.pdf` | Item `Filed`, dokumen `NSBR/2026000123` aktif, objek di `docs/…/NSBR-2026000123/…`, retensi terpasang |
| FR-04 | Nomor tidak ada | Nama berkas valid, nomor tidak ada di ERP | Upload | `NeedsReview` alasan `NumberNotInErp` |
| FR-04 | Duplikat | Hash sama sudah terarsip | Upload | `NeedsReview` alasan `Duplicate` menunjuk dokumen asal |
| FR-04 | Bukan PDF | `.exe` berganti nama `.pdf` | Upload | `Rejected` |
| FR-04 | PDF berskrip | PDF berisi `/JavaScript` | Upload | `NeedsReview` alasan `SecurityFlag` |
| FR-04 | Tanpa hak | Cocok ke `BPJ`, user tak punya grant `BPJ` | Upload | `NeedsReview` alasan `NoGrant` |
| FR-02 | Jendela tahun | Auditor grant `View` kategori Payable 2023–2025 | Buka dokumen BPS 2026 | 404, tercatat |
| FR-08 | Path traversal | Kirim path/`../` ke route viewer | Request | Route hanya menerima ID → 404 |
| FR-08 | Confidential | Jenis `Confidential` | Buka | Setiap halaman ber-watermark nama/NIK/waktu |
| FR-09 | Ganti | Dokumen aktif v1 | Ganti berkas dengan alasan | v2 `Current`, v1 `Superseded` dan masih bisa dibuka admin |
| FR-10 | Hapus & pulihkan | Dokumen aktif | Hapus lalu admin pulihkan | `Deleted` → `Active`; objek tak pernah hilang; 2 entri activity log |
| FR-10 | Object Lock | Dokumen `Compliance` dalam retensi | Coba hapus objek langsung di MinIO | Ditolak MinIO |
| FR-11 | Coverage | Aturan LDN; 10 LDN Oktober, 7 terarsip | Snapshot | expected 10, present 7, missing 3 |
| FR-11 | Rentang nomor | 1 dokumen ADVBRCC `Covers` 4 nomor | Coverage | 4 nomor terhitung ada |
| FR-13 | Bundle GRN | GRN approved HOD, GRN & SJGRN ada | Job harian | Dokumen `GRN_BUNDLE` baru; PDF GRN & SJGRN asli tidak berubah (hash sama) |
| FR-13 | Sumber diganti | SJGRN diganti versi baru | Sesudah ganti | Bundle ditandai usang dan dibangun ulang |
| FR-14 | Dispatch | Customer punya kontak aktif; 3 FP advance terarsip | Kirim | 1 email, 3 item, status terkirim, akses tercatat |
| FR-15 | Idempoten | `LIN/2025` sudah dimigrasi | Command ulang | 0 unggah, 0 baris baru |
| FR-15 | Rekonsiliasi | Migrasi selesai | Laporan | Jumlah & byte per folder legacy = indeks |
| FR-15 | Mount down | Mount tidak terbaca | Command | Gagal bersih, tidak ada perubahan indeks, notifikasi |
| FR-16 | Bridge | Dokumen `NSBR/2025/NSBR-2025000777.pdf` termigrasi | User login buka URL legacy | Redirect ke viewer; tercatat `channel=Bridge` |
| FR-16 | Tahap 1, berkas lama | Rewrite Tahap 1 aktif; berkas ada di Doc_Folder | Layar legacy membuka PDF dalam iframe | Dilayani Apache dari disk seperti sebelumnya, tanpa redirect |
| FR-16 | Tahap 1, dokumen baru | Rewrite Tahap 1 aktif; dokumen di-*file* di sistem baru sesudah write-freeze | Buka URL legacy dokumen itu | Redirect ke bridge → viewer (path diurai lewat alias) |
| FR-16 | Rollback | Aturan rewrite dihapus + reload graceful | Buka URL legacy berkas lama | Dilayani dari disk seperti sebelum cutover |
| FR-17 | Transporter | 5 LDN, 3 terarsip | `existsMany('LDN', 5 nomor)` | 3 true, 2 false; hasil identik dengan job scan berbasis mount |

---

## 14. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1 | 2026-10-14 | Indra Putro (dibantu Claude) | Draft awal: migrasi e-Filing ke Laravel + MinIO, pakai ulang tabel MGTAPPS `EFILL_*` |
| 0.2 | 2026-10-14 | Indra Putro (dibantu Claude) | Schema MGTHRIS, semua tabel baru `efl_*`; MinIO versioning + Object Lock + enkripsi; intake pipeline otomatis; source adapter ERP; grant per Role/User + jendela tahun; coverage; modul terkait (Dossier, Bundle GRN/Invoice, Dispatch FP Advance, kontrak & bridge legacy); data pemakaian 12 bulan dan source package MGTAPPS (§L-8–§L-10); granularitas grant per kode transaksi (FR-02 BR-6–8, §L-11) |
| 0.3 | 2026-10-14 | Indra Putro (dibantu Claude) | Jawaban open item: OI-01/04/10/11/16/18 ditutup, OI-05/15 terjawab; adapter ERP via `oracle_mgthris` + grant SELECT; retensi & versi dijelaskan (FR-01 BR-3); batas ukuran tanpa kompresi lossy (FR-01 BR-5); larangan scan rentang (FR-04 BR-5a); migrasi penuh + fallback (FR-15 BR-9); jenis mirip tetap terpisah; bahan OI-07/08 `EFILING_USER_FOLDER_ACCESS.xlsx`; bridge legacy dua tahap dengan prosedur uji & rollback, risiko iframe/cek disk (FR-16 BR-1–5, R-6a–c) |

---

*Template: github.com/mutugading/docs-markdown/templates/PRD-template-mutugading.md*
