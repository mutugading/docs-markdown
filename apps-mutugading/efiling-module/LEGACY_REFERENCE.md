# E-Filing Legacy — Referensi Sistem Lama

> Hasil analisa read-only atas aplikasi PHP native e-Filing, 2026-10-14.
> Dipakai sebagai lampiran `PRD_Efiling_Module.md`; PRD merujuk ke heading di sini (`§L-x`).

| Apa | Di mana |
|---|---|
| Source asli | `\\192.168.0.3\d$\XAMPP\htdocs\webapps\Efilling_File` |
| Salinan source (kredensial sudah di-*redact*) | `efiling-module/legacy-source/` — tanpa `fpdf*/`, `bcp/`, file `_BCP` / `_TEST` |
| Repositori PDF | `\\192.168.0.3\d$\XAMPP\htdocs\webapps\Doc_Folder` — di server app di-mount read-only ke `/mnt/efilling/Doc_Folder` (dipakai Transporter) |
| URL web lama | `https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/...` (static, **tanpa login**) |

Include dari folder induk yang **tidak ikut tersalin**: `../form_header_main.php`, `../head_component.php`,
`../common_js.php`, `PHPMailer/`, `mailSales.php`. Source package `pkg_effil_prs` / `pkg_efilling` ada di
database, belum diekstrak.

---

## §L-1 Kerangka bersama

- Tiap layar = satu file `EFILL_xxx.php` yang post-back ke dirinya sendiri (hidden `STSPRS` sebagai saklar aksi).
  Pendamping: `_JS`, `_LOV*`/`_SHOW*` (fragmen AJAX GET), `_DB` (tulis AJAX POST), `_SQL`, `_PARAM`, `_XLS`, `_PDF`.
- `check_login.php` hanya memeriksa `$_SESSION['username']` ada. **Tidak ada cek hak per layar** — akses hanya
  diatur lewat tampil/tidaknya menu (`MST_MENUS` × `MST_GROUP_MENUS`/`MST_GROUP_USERS` ∪ `MST_USER_MENUS`).
- Path fisik `D:/XAMPP/htdocs/webapps/Doc_Folder/` dan URL web di-*hardcode* berulang di hampir semua file.

## §L-2 Inventaris layar

### Intake scan (upload → rename → arsip)

| Layar | Fungsi |
|---|---|
| **EFILL_008** Upload | Upload banyak PDF ke inbox pribadi `SCAN_DOC/{USER}` (`mkdir 0777`). Validasi hanya client-side (total ≤ 40 MB, `accept=pdf`). Nama sama → ditimpa diam-diam. |
| **EFILL_001** Rename | Tampilkan file inbox satu per satu (preview iframe); user ketik nama baru → `rename()`. Format nama dicek `pkg_effil_prs.fCekFormatFile`. Tidak dicatat di log. |
| **EFILL_002** Move | Pilih jenis dokumen + folder tujuan (kaskade s.d. 5 level dari `EFILL_DATA_FOLDER`) → **semua** file inbox yang berawalan `ETD_FORMAT_FILE` dipindah ke `Doc_Folder/{lvl1}/{lvl2}/…`. Aturan: nama harus diawali `{lvl1}-{lvl2}` (lvl2 = tahun); pengecualian prefix `LDN SALES→LDN-`, `PDN SALES→PDN-`, `FP SALES→FP-`, `JWDN SALES→JWDN-`; dilewati untuk jenis di `MST_PARAM_DATA` `EXCLUDE_DOC_CHECK_EFILL`. |

### Browse arsip per folder

| Layar | Fungsi |
|---|---|
| **EFILL_003** | Pilih jenis → folder → cari substring; grid PDF (`readdir`), klik = view di iframe + log. Tombol email bila `ETD_EMAIL_ACTIVE='Y'` (§L-5). |
| **EFILL_005** / **EFILL_005_NEW** | Sama dengan 003 tanpa email. Tahun dibatasi `pkg_efilling.fGetQryUserYear(user)`. `_NEW` (layout Bootstrap, versi aktif) mengecualikan `MBRCP`. |
| **EFILL_2CG** | Hanya jenis `MBRCP`. Viewer PDF.js ke canvas; print, klik kanan, PrintScreen, dan perangkat mobile diblokir (semuanya kosmetik di sisi klien). |
| **EFILL_014** | Browse + tombol **Del** (wajib remark) → `unlink()` fisik + log. Tidak ada soft delete. |

### Lookup dokumen berbasis transaksi ERP (MGTDAT, read-only)

| Layar | Tampilan bisnis | Sumber | PDF yang ditautkan per baris |
|---|---|---|---|
| **EFILL_007** | Payment supporting docs | `ft_payment_header/detail`, `ft_payment_oth_acnt_detail`, `om_supplier`, `fm_bank` | Payment `{TRAN_CODE}/{yyyy}/{no}.pdf`, `MCM/{yyyy}/MCM-{no}.pdf`, dokumen referensi; filter "Doc N/A", "MCM N/A"; filter area `MST_USERS.USER_AREA` |
| **EFILL_009** | Tagihan provisi transporter → TP → DN | `mgt_tp_provision(_bill)`, `mgt_transp_head`, `mgt_transp_detail_dn` | DN `{code}/{yyyy}/{dn}.PDF`. **Kontrol status dokumen sudah diambil alih modul Transporter** (PRD Transporter §5.9, F-10.x) |
| **EFILL_010** | Invoice breakdown | `ot_invoice_head`, `ot_tax_invoice_mgt`, `ot_invoice_ref`, `pakkwi`, `ft_os` | Invoice, Faktur Pajak (`FP SALES/{yyyy}/FP-{17 digit}.pdf`), DN, Kwitansi; **Merge File** (§L-5). Grup `INVOICE BREAKDOWN` + `IS_READ_ONLY='Y'` = lihat nomor saja |
| **EFILL_012** | PO → Bill | `ot_po_head`, `ot_bill_head/ref`, `ot_gr_ref` | PO dan Bill |
| **EFILL_013** | MCM / payment matching (akun utama `203*`) | `ft_os`, `fv_os_match`, `ft_payment_header`, `ot_bill_head`, `ot_gr_head` | MCM, transaksi yang di-*match*, PO |

### Laporan & log

| Layar | Fungsi |
|---|---|
| **EFILL_011** | Kelengkapan scan: pilih jenis (dari `MST_PARAM_DATA` `EFIL_SCAN_DOCS_TYPE`) + rentang tanggal + status Exist/Not Exist. UNION besar transaksi ERP lalu `file_exists()` per baris (`glob(prefix*)` untuk jenis di `EFIL_CK_SCAN_DOC_UNNORMAL`). Export XLS (tabel HTML). |
| **EFILL_015** | Viewer `EFILL_LOG_ACCESS`, 30 baris/halaman. Grup `ADMIN` melihat semua user, selain itu hanya miliknya. |
| Export | `EFILL_007_XLS`, `EFILL_010_XLS`, `EFILL_011_XLS(_2)` — tabel HTML berekstensi `.xls`. `_PDF` (FPDF) ada tapi menunya di-*comment*. |

Tidak ada UI untuk master (`EFILL_TYPE_DATA`, `EFILL_DATA_FOLDER`, `EFILL_USER_ACCESS`, `EFILL_ADMIN_USER`,
`MST_PARAM_DATA`) — dirawat langsung di database. **Catatan:** di Laravel sudah ada
`Modules/Core/…/Efiling/EfilingAccessManager` (commit `feat(core): manage legacy e-filing document access per user`)
yang mengelola `EFILL_USER_ACCESS` per user.

Nomor layar 004 dan 006 tidak ada di folder.

## §L-3 Objek Oracle

### MGTAPPS (milik e-Filing)

| Objek | Kolom | Arti |
|---|---|---|
| `EFILL_TYPE_DATA` | `ETD_SYS_ID` (kode jenis), `ETD_DESCRIPTION`, `ETD_SEQ_NO`, `ETD_FORMAT_FILE` (prefix nama), `ETD_TOT_DISPLAY`, `ETD_EMAIL_ACTIVE` | Master jenis dokumen (sudah ada model `Core\…\EfillTypeData`) |
| `EFILL_DATA_FOLDER` | `EDF_SYS_ID`, `EDF_SYS_ID_PARENT`, `EDF_ETD_SYS_ID`, `EDF_FOLDER` | Pohon folder per jenis (`CONNECT BY`), praktis = `{TYPE}/{YYYY}` |
| `EFILL_USER_ACCESS` | `EUA_USER_ID`, `EUA_ETD_SYS_ID` | Jenis yang boleh dilihat user (model `Core\…\EfillUserAccess`) |
| `EFILL_ADMIN_USER` | `EAU_USER_ID` | Admin e-Filing → lihat semua jenis |
| `EFILL_LOG_ACCESS` | `ELA_SYS_ID` (**selalu '123'**), `ELA_CREATED_BY`, `ELA_CREATED_TIMESTAMP`, `ELA_DETAIL_ACCESS` (teks bebas), `ELA_USER_NAME` | Jejak akses — satu-satunya tabel yang ditulis aplikasi |
| `MST_PARAMS` | `EFILL_FOLDER_SCAN` | Nama folder inbox |
| `MST_PARAM_DATA` | kunci `EXCLUDE_DOC_CHECK_EFILL`, `EFIL_SCAN_DOCS_TYPE`, `EFIL_CK_SCAN_DOC_UNNORMAL`, `USER_AREA` | Daftar konfigurasi |
| `MST_USERS` / `MST_GROUPS` / `MST_GROUP_USERS` | `USER_AREA`, `GROUPS_NAME`, `IS_READ_ONLY` | Grup `ADMIN`, `INVOICE BREAKDOWN`, `GROUPS_ID=1` |
| Package | `pkg_effil_prs.fCekFormatFile/fListFormatScan/fGetFormatFile`, `pkg_efilling.fGetQryUserYear` | **Source belum diekstrak** |

`MST_EMAIL_CUST`, `ALRT_M_SCDLR_WEB*` hanya muncul di kode mati (salinan dari modul lain).

### MGTDAT (ERP, hanya dibaca)

`fm_bank, ft_cur_trans_header, ft_prv_trans_header, ft_os, fv_os_match, ft_payment_header, ft_payment_detail,
ft_payment_oth_acnt_detail, ft_payment_gen_doc_nos, ft_receipt_header, im_vs_static_value, menu_user,
mgt_tp_provision, mgt_tp_provision_bill, mgt_transp_head, mgt_transp_detail_dn, om_customer, om_supplier,
om_supplier_bank_cont_detail, ot_adj_head, ot_bill_head, ot_bill_ref, ot_gr_head, ot_gr_ref, ot_invoice_head,
ot_invoice_item, ot_invoice_item_ted, ot_invoice_ref, ot_po_head, ot_tax_invoice_mgt, pakkwi`

Ekspresi nomor penting: `TRAN_CODE||'-'||DOC_NO` (tahun = `SUBSTR(DOC_NO,1,4)`);
FP `'FP-'||REPLACE(REPLACE(INVH_FP_NO,'.',''),'-','')`;
KWI `'KWI-'||LPAD(YY,2,0)||LPAD(MM,2,0)||LPAD(SLNO,5,0)` (tahun folder = `TO_CHAR(KWI_DATE,'YYYY')`).

## §L-4 Pemetaan jenis dokumen → transaksi sumber

| Kelompok | Jenis | Sumber nomor |
|---|---|---|
| Invoice | LIN, CIN, SIN, JWIN, EIN … | `ot_invoice_head.INVH_TXN_CODE/INVH_NO` |
| Surat jalan | LDN, PDN, JWDN (+ `… SALES`) | `ot_invoice_ref.INVR_REF_*` / `mgt_transp_detail_dn` |
| Faktur pajak | FP SALES | `ot_invoice_head.INVH_FP_NO` |
| Kwitansi | KWI | `pakkwi` |
| Payment | BPS, BPJ, CPS, CPJ, ADVP … | `ft_payment_header.PH_TRAN_CODE/PH_DOC_NO` |
| Dok. yang dibayar | NSBR, CSBR, SSBR, JSBR, JJV, JV, TJV, LPO, IPO, LSPO, LCPO, ICPO, JPO, SCSR, CN | `ft_payment_detail.PD_REF_*` |
| Receipt | BR, BRCC, ADVBRCC | `ft_receipt_header` |
| PO / Bill | LPO, IPO, … | `ot_po_head`, `ot_bill_head` |
| Jurnal GL | JV, RJV, … | `ft_cur/prv_trans_header.TH_TRAN_CODE/TH_DOC_NO` |
| Adjustment | AADJ … | `ot_adj_head` |
| MCM | MCM | payment akun `203*` |
| **Tanpa pemetaan (arsip manual)** | BUPOT, PL, DGSN, MIS, CARPET PRODUCT, ITEM_CODE, DESPMARGIN, DONATION, SPTNP, PIB, BC, … | — |

E-Filing tidak punya workflow/status — satu-satunya "state" adalah berkasnya ada atau tidak.

## §L-5 Integrasi

- **Email (EFILL_003):** PHPMailer SMTP SSL `mail.mutugading.com:465`, From `mgtsales@mutugading.com`.
  To/Cc diketik bebas (Cc justru dipasang sebagai Reply-To), subject *hardcoded* "PHPMailer Test Subject…",
  `SMTPDebug=2` tampil ke user. **Fitur setengah jadi.**
- **Merge PDF (EFILL_010_MERGE):** `exec()` PDFtk Server di host Windows → `Doc_Folder/MERGE_FILE/{INV}_MERGE.pdf`,
  disimpan permanen. Urutan: Invoice, FP, DN.
- **`GRN_MERGE/`** (43 ribu berkas) **tidak dibuat oleh kode e-Filing** — ada proses lain yang menulisnya.
- **Transporter (Laravel):** job `transporter:scan-delivery-documents` membaca mount Doc_Folder (D-19, F-10.3).
- Ada indikasi deep-link dari aplikasi lain ke URL `…/webapps/Doc_Folder/...`.

## §L-6 Kondisi Doc_Folder (diukur 2026-10-14)

- **577.686 berkas, ±461,6 GB**; 577.611 PDF. 21.689 berekstensi `.PDF` (huruf besar), 2.685 nama mengandung spasi.
- Struktur `{TYPE}/{YYYY}/{file}.pdf`, tahun 2018–2026. Pengecualian: `CARPET PRODUCT/{…}`, `ITEM_CODE/IND`,
  `KWITANSI_BREAKDOWN/` dan `MERGE_FILE/` (berkas langsung di level 2).
- 97 entri level-1 (3 berspasi: `CARPET PRODUCT`, `FP SALES`, `LDN SALES`, `PDN SALES`, `JWDN SALES`; `NULL` kosong).
- Terbesar (GB): NSBR 64,5 · GRN_MERGE 31,1 · SJGRN 25,8 · LDN 25,6 · BPJ 25,3 · LGRN 21,9 · JJV 20,9 · LDN SALES 20,1.
- Terbanyak (berkas): FP SALES 58,6 rb · LDN 57,5 rb · LDN SALES 53,8 rb · LIN 48,1 rb · GRN_MERGE 43,2 rb.
- Contoh nama tidak baku: `MCM- BPS-2025120282.pdf` (spasi), `FP-.pdf` (nomor kosong),
  `ADVBRCC-2022010027 S.D 028.pdf` / `ADVBRCC-2023100049-52.pdf` (satu scan untuk rentang nomor),
  `ITEM_CODE-IND0002932_Tee GI 4 Inc….pdf`, `SJGRN-CGRN-20025000031.pdf`, `KWI-250100095_MERGE.pdf`.

## §L-7 Temuan keamanan & kualitas

1. **SQL injection di semua query** — tidak ada bind variable; endpoint AJAX menerima `USER_NAME` dari request.
2. **Hapus berkas tanpa autentikasi** — `EFILL_014_DB.php` `unlink()` path dari klien, termasuk `../`.
3. Path traversal di move (002) dan rename (001); potensi command injection di `exec()` PDFtk.
4. **Seluruh PDF dapat diunduh tanpa login** lewat URL static; proteksi "no print" MBRCP hanya JS.
5. **Kredensial Oracle & SMTP tertulis polos** di `conOraOci.php` dan `EFILL_003.php` → perlu dirotasi.
6. XSS (nilai POST di-echo ke HTML/JS), upload tanpa validasi server, PK log konstan `'123'`,
   debug echo untuk user ID tertentu (`1949`, `RCS1283`, `AUDITOR`), kode duplikat/mati.
7. Kinerja: `file_exists()` per baris laporan dan `readdir()` folder berisi ≤ 15 ribu berkas tiap request.

---

## §L-8 Source package MGTAPPS (diekstrak 2026-10-14 dari `ALL_SOURCE`, DB dev 192.168.0.7)

| Objek | Isi sebenarnya |
|---|---|
| `pkg_effil_prs.fCekFormatFile(name)` | `'Y'` bila awalan nama berkas cocok salah satu `EFILL_TYPE_DATA_FORMAT.ETDF_FORMAT_FILE` (69 baris, mis. `BR_`, `CGRN_`, `FP-`). Bug: `WHEN OTHERS → 'Y'` (lebih dari satu kecocokan dianggap lolos) |
| `pkg_effil_prs.fGetFormatFile(type)` | `ETD_FORMAT_FILE` tanpa tanda kutip |
| `pkg_effil_prs.fListFormatScan` | Daftar semua format untuk ditampilkan ke user |
| `pkg_effil_prs.pInsLog(row)` | `INSERT EFILL_LOG_ACCESS` |
| `pkg_efilling.fCkDestFolderFile(folder, file)` | `TRUE` bila 4 digit sesudah `-` terakhir di nama berkas = nama folder terakhir (tahun). Dikecualikan: `FP SALES`, `KWI`, `ITEM_CODE` |
| `pkg_efilling.fGetQryUserYear(user)` | Mengembalikan **potongan SQL** `select EUY_DATA … where EUY_USER_ID='…'` dari `EFILL_USER_YEAR` (rawan injeksi). Isi tabel: hanya user `AUDITOR`, tahun 2023–2025 → praktis = **akses auditor terbatas tahun** |
| `pkg_efilling.fGetPoReff(trn)` | Mencari PO referensi dari match OS / payment (`PH_FLEX_05`); logika exception-nya terbalik (hanya mengisi bila `TOO_MANY_ROWS`) |

Artinya: format "scanner" memakai garis bawah (`BR_2026…`) dan di-rename manual menjadi `BR-2026…` di EFILL_001.

## §L-9 Pemakaian nyata (dari `EFILL_LOG_ACCESS`, 12 bulan s.d. 2026-10-14)

| Aksi | Jumlah | User |
|---|---:|---:|
| Move File (filing ke Doc_Folder) | 133.285 | 26 |
| Upload File | 101.108 | 25 |
| View Document | 25.875 | 47 |
| Invoice Breakdown | 3.039 | 11 |
| Delete Document | 70 | 4 |

Upload per bulan 2026: Apr 4.963 · Mei 8.818 · Jun 8.331 · Jul 10.030 · Agu 8.191.

**View per aplikasi asal** — dokumen e-Filing lebih banyak dibuka dari aplikasi lain daripada dari e-Filing sendiri:

| Aplikasi asal | View | User | Jenis yang dibuka |
|---|---:|---:|---|
| Shipping | 17.947 | 15 | NSBR, SSBR, EBJV, CSBR, IBJV, LPO |
| GRN | 3.228 | 6 | LGRN, NSGRN, CGRN, CHPGRN, SJGRN, LPO |
| Efilling Document | 1.887 | 8 | BPJ, LDN, JJV, LPO, EBJV, NSBR |
| Reports | 1.561 | 9 | `MERGE_FILE/LIN-…_MERGE.pdf`, CSBR, BPSCAP, SSBR |
| Master Batch | 669 | 4 | BPS, JV, MCM, SSBR, LPO, CASV |
| Finance Taxation | 260 | 2 | JJV, EBJV, PIB, BPJ, JSBR |
| Sending Document | 145 | 2 | MERGE_FILE, BPS, NSBR, CHPGRN |
| Costing, Sales, Event, TWT Process, KYP | < 100 | — | BPS/IBJV, LDN, CARPET PRODUCT |

Menu legacy aktif dan jumlah user yang mendapat menu (via grup):
Find Document `EFILL_005_NEW` 100 · Move `EFILL_002` 70 · Upload `EFILL_008` 70 · Log `EFILL_015` 57 ·
Invoice Breakdown `EFILL_010` 41 · Check Scanned `EFILL_011` 39 · Rename `EFILL_001` 33 · Proof of Payment
`EFILL_013` 31 · Payment Supporting `EFILL_007` 28 · Find (Costing) 16 · Transporter Comparation 13 ·
Delete 11 (+3 Costing) · Setup e-Filing (User Admin / User Folder / Folder Data / List User Folders) 8 · MB Find 5.
`EFILL_003`, `EFILL_005` lama, `EFILL_012` (PO listing) **tidak punya menu aktif**.

Master: 91 jenis dokumen, 69 format nama, 1.843 baris akses user, 12 admin, 591 folder.

## §L-10 Aplikasi legacy lain yang menulis/memakai Doc_Folder

Source disalin ke `efiling-module/legacy-related/` (kredensial di-*redact*).

| Aplikasi / menu | Source | Perilaku |
|---|---|---|
| **GRN — Merging Document** (`2T90000000`) + **List Merging Document Process** (`2T9A000000`) | `GRN_File/2T_0009*`, `2T_0010*` | Kandidat: GRN yang sudah di-approve HOD MC (`mgtdat.mgt_grn_scan` ⨝ `ot_gr_head`) dan belum ada di `MGTAPPS.GRN_MERGE_DOCS_DATA`. Proses: salin `{GRN}/YYYY/{GRN}.pdf` + `SJGRN/YYYY/SJGRN-{GRN}.pdf` ke `GRN_MERGE/YYYY/`, gabung via PDFtk `exec()`, **lalu menimpa berkas GRN asli dengan hasil gabungan** (`copy($FileMerge_Src,$GRN_SRC)`). Log `GRN_MERGE_DOCS_DATA` (PK konstan `'123'`). 300–700 GRN/bulan. **Inilah sumber folder `GRN_MERGE/`** |
| **Costing — Upload / Find / Delete Document** (`2G9B/2G9A/2G9C`) | `Costing_File/CST_EFILL_002*`, `CST_EFILL_014` | Salinan EFILL_008+002 dengan inbox terpisah `SCAN_DOC_CST/{USER}` dan salinan EFILL_014 (hapus fisik) |
| **Payment Supporting Document** (`2310000000`) | `Payment_File/231_0001*` | **Identik** dengan EFILL_007 (berkas sama persis) |
| **Sending Document — Faktur Pajak Advance** (`2J30000000`) | `Sending_Document_File/2J1_0003*` | Daftar faktur pajak uang muka per customer (`ot_tax_invoice_mgt` + `ft_cur/prv_trans_detail`, tidak di-reversal) → email ke customer dengan lampiran `FP SALES/{YYYY}/FP-{no}.pdf`. Penerima dari `MGTAPPS.MST_EMAIL_CUST` (customer) + `ALRT_M_GRP_MAIL_TO` grup `202208016` (internal). Subject "Finance PT. Mutu Gading Tekstil (Dokumen Faktur Pajak)". Log `MGTAPPS.LOG_SENDING_MAIL_CUST` — 4.586 baris, terakhir 2026-08-28 |
| **Check Document DN** (`2O10000000`) | `2O1_0001*` | Mencocokkan `MGT_TRANSP_DETAIL_DN` dengan Doc_Folder — tumpang tindih dengan EFILL_009 (sudah diambil alih modul Transporter) |
| Shipping, Reports, Master Batch, Finance Taxation, Event, TWT, KYP | belum ditelusuri | Hanya **membuka** dokumen lewat URL Doc_Folder (deep-link) dan mencatat `View Document -> {app}` |
| Form Oracle `eFile\EFILE_T_001.fmx` "Log Access Document" | Forms | Viewer log akses di Oracle Forms |

## §L-11 Profil hak akses per kode transaksi (`EFILL_USER_ACCESS`, 2026-10-14)

- 113 user, 1.843 baris grant, 0 grant yatim; 12 user di `EFILL_ADMIN_USER` (semua jenis).
- Hanya 49 dari 113 user tercatat beraktivitas dalam 12 bulan terakhir → ±60 user pemegang grant tidak aktif.
- 72 kombinasi jenis berbeda. Terbanyak: `CARPET PRODUCT` saja (8 user), `ITEM CODE` saja (8),
  GRN `CGRN,CHPGRN,LGRN,NSGRN,SGRN` (5), dokumen sales `CIN,CSR,FP SALES,JWDN…,KWI,LDN…,LIN,PDN…,SIN,SRC,WDN` (4),
  PO `LCPO,LPO,LSPO` (3), Finance hampir semua jenis (beberapa varian, 2–3 user tiap varian), `MBRCP` (3).
- Sebaran: 72 user memegang 1–9 jenis, 17 user 10–29 jenis, 24 user 30–79 jenis.
- Penegakan legacy: grant hanya menyaring daftar jenis di layar browse (003/005/014/2CG). Layar 007/010/013
  dan URL static Doc_Folder tidak memeriksa grant.
