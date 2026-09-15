# Klarifikasi Teknis — Baris 81–95 "For sale of AX Grade only"

**Kepada:** Ilham (dan tim IT Costing)
**Dari:** Indra Putro
**Perihal:** Koreksi premis dokumen *"Permintaan Konfirmasi Perhitungan — Blok For sale of AX Grade only"*
**Tanggal:** 15 September 2026
**Dasar:** file `54. Evidence (Crosscek data file costing).xlsx` (klarifikasi developer lama) + source code legacy di folder `Costing_File/` dan `CST_M_010.fmb`

> ### 📌 Status dokumen ini
> Ditulis **sebelum** `PKG_YARN_MARKETING.pkb` diterima. Analisis strukturnya terbukti benar,
> dan rumus lengkap tiap baris sekarang sudah tersedia di
> **[`jawaban-kalkulasi-baris-81-95-dari-source-oracle.md`](./jawaban-kalkulasi-baris-81-95-dari-source-oracle.md)** —
> **baca dokumen itu untuk implementasi.** Dokumen ini disimpan sebagai rekam jejak analisis,
> dengan satu koreksi di §4.1.

---

## 0. Ringkasan satu paragraf

**Jangan kirim dokumen pertanyaan itu ke tim Finance/Costing dulu.** Premis utamanya —
bahwa perhitungan baris 81–94 "tidak pernah tercatat di mana pun" dan "dulu dihitung
langsung di dalam file Excel atau diisi manual" — **tidak benar**. Perhitungan itu ada,
tersimpan di sistem lama, dan **bisa kita ambil sendiri tanpa bertanya ke user sama sekali**.
Yang terjadi adalah kita mencarinya di tempat yang salah: kita mencari angka di tabel data,
padahal baris-baris ini **tidak disimpan sebagai data** — mereka **dihitung on-the-fly saat
render** oleh fungsi PL/SQL bernama di package `MGTAPPS.PKG_YARN_MARKETING`.

Konsekuensinya: dari ~30 pertanyaan di dokumen itu, **mayoritas tidak perlu ditanyakan**.
Ada 1 hal yang memang perlu konfirmasi bisnis, dan itu pun bukan yang diprioritaskan.

---

## 1. Bagaimana cost sheet legacy sebenarnya dibentuk

Ini inti dari file Evidence yang dikirim developer lama. Cost sheet **bukan report statis** —
dia *metadata-driven*. Tiap baris didefinisikan sebagai row di tabel, bukan di-hardcode:

| Objek | Fungsi |
|---|---|
| `MGTAPPS.CST_YARN_CALC_RPT_MODEL` (`CYCRM_*`) | Daftar "model report". Cost sheet yang kita rekonsiliasi = **`CYCRM_SYS_ID = 20211206`** ("Find Marketing Product") |
| `MGTAPPS.CST_YARN_CALC_RPT_LABLE` (`CYCRL_*`) | **Satu row = satu baris cost sheet.** Isinya `CYCRL_SEQ_NO` (nomor baris 1–95), `CYCRL_DESCRIPTION` (label), dan yang terpenting: **`CYCRL_SOURCE_TYPE` + `CYCRL_SOURCE_QUERY`** |
| `MGTAPPS.CST_YARN_CALC_RPT_FORMULA` (`CYCRF_*`) | Formula terstruktur (sampai 3 operand + operator) untuk baris ber-`SOURCE_TYPE = 'From Formula'` |
| Form `CST_M_010.fmb` | Layar maintenance Oracle Forms untuk ketiga tabel di atas (menu: *MGT Costing → YARN Process → Setup → Report Parameter*) |

Saat cost sheet dirender (`CST_FIND_PRODUCT_VIEW_DETAIL.php` → `CST_FIND_PRODUCT_SQL.php`),
engine loop tiap row label, lalu `getDtVal()` men-*dispatch* berdasarkan `CYCRL_SOURCE_TYPE`.

Bukti di `CST_FIND_PRODUCT_VIEW_DETAIL.php` (query label):

```sql
SELECT CYCRL_SEQ_NO, CYCRL_DESCRIPTION, ...
      ,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
      ,nvl(CYCRL_SOURCE_TYPE ,'NULL') CYCRL_SOURCE_TYPE
      ,CYCRL_DISP_IN_FINAL_PRODUCT, CYCRL_HIDE_SEQ_NO
  FROM mgtapps.cst_yarn_calc_rpt_lable b
 WHERE b.cycrl_cycrm_sys_id = '20211206'
 ORDER BY to_number(cycrl_seq_no)
```

Catatan kecil: kolom **`CYCRL_DISP_IN_FINAL_PRODUCT`** itulah yang menjelaskan kenapa baris
85–92 & 94 "tidak ikut tercetak di sheet final" — itu flag tampilan, bukan tanda datanya kosong.

---

## 2. Temuan utama — formulanya ADA, dan bernama

`Costing_File/CST_FIND_PRODUCT_SQL.php` baris 511–591 (fungsi `getDtVal`) berisi daftar
lengkap `SOURCE_TYPE` yang dikenal engine. **Enam di antaranya persis baris yang kita
pertanyakan**, dan masing-masing memanggil fungsi PL/SQL tersendiri:

| Baris sheet | Label | `CYCRL_SOURCE_TYPE` | Fungsi Oracle yang dipanggil |
|---|---|---|---|
| **81** | `81.Cost lessQL,CO,Frwd.` | `CostLess_QL_CO_Frwd` | `pkg_yarn_marketing.fGet_CostLess_QL_CO_Frwd(:cyl_sys_id_dtl, :cyl_sys_id)` |
| **82** | `82.NSBC SP.` | `NSBC_SP` | `pkg_yarn_marketing.fGet_NSBC_SP(...)` |
| **83** | `83.Addl. NSBC Loss.` | `Addl_NSBC_Loss` | `pkg_yarn_marketing.fGet_Addl_NSBC_Loss(...)` |
| **84** | `84.Domestic Cost AX grd only.` | `Dom_Cost_AX_Grd_Only` | `pkg_yarn_marketing.fGet_Dom_Cost_AX_Grd_Only(...)` |
| **95** | `95.Domestic cost with uneven packing.` | `D_C_A_G_O_W_E_C` | `pkg_yarn_marketing.fGetDomCost_AXGrdOnly_WExtrCst(...)` |
| — | `% Add Top 95` | `Extra_Yarn_Persen` | `pkg_yarn_marketing.fGet_Extra_Yarn_Persen(...)` |
| — | `Top 95 X % Add` | `Cost_Of_Extra_Yarn_Persen` | `pkg_yarn_marketing.fGet_Cost_Of_Extra_Yarn_Persen(...)` |

Kutipan aslinya (`CST_FIND_PRODUCT_SQL.php:545-565`):

```php
} else if ($pCYCRL_SOURCE_TYPE === "CostLess_QL_CO_Frwd"){
    $sqlDtDtl = "select pkg_yarn_marketing.fGet_CostLess_QL_CO_Frwd('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
} else if ($pCYCRL_SOURCE_TYPE === "Addl_NSBC_Loss"){
    $sqlDtDtl = "select pkg_yarn_marketing.fGet_Addl_NSBC_Loss('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
} else if ($pCYCRL_SOURCE_TYPE === "NSBC_SP"){
    $sqlDtDtl = "select pkg_yarn_marketing.fGet_NSBC_SP('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
} else if ($pCYCRL_SOURCE_TYPE === "Dom_Cost_AX_Grd_Only"){
    $sqlDtDtl = "select pkg_yarn_marketing.fGet_Dom_Cost_AX_Grd_Only('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
}
```

Pola dispatch yang sama diulang di **fungsi lain** di file yang sama (`getDtVal_Usr`,
`getDtVal_RM55`) dan di **`PROD_COMPARATION_SQL.php`** (21 kemunculan). Ini bukan sisa kode
mati — ini jalur produksi.

### Kenapa kita tidak menemukannya

Karena kita menelusuri **tabel data**, sementara baris-baris ini **tidak pernah disimpan
sebagai data**. Nilainya dihitung *saat report dibuka*, lalu dibuang. Jadi:

- kolom di belakang baris 82 & 83 kosong di sheet sumber → **bukan** karena "diketik manual",
  tapi karena memang tidak ada nilai persisted untuk di-*dump*;
- 12.567 baris biaya tanpa nilai di baris 85–92 & 94 → **bukan** bukti "belum pernah terisi
  sejak awal", tapi konsekuensi wajar dari desain compute-on-render.

Dua "bukti" yang dipakai dokumen pertanyaan untuk memperkuat dugaan manual, keduanya justru
konsisten dengan penjelasan ini. **Tidak ada satu pun bukti positif** bahwa angkanya diketik tangan.

---

## 3. Baris 85–92 dan 94 — tersimpan sebagai TOP 130–140

Berbeda dari baris 81–84, blok `R-*` (85–92) dan `94.Addl Val Loss.` **tidak** punya
`SOURCE_TYPE` khusus. Artinya engine jatuh ke dua jalur fallback: `SOURCE_TYPE = 'NULL'`
(kolom diambil dari query data utama) atau raw SQL di `CYCRL_SOURCE_QUERY`. Keduanya berarti
**definisinya tersimpan di kolom `CYCRL_SOURCE_QUERY` — tinggal di-SELECT.**

Dan sisi datanya sudah pernah kita temukan sendiri. Dari
`reconcilliation/report/phase5_findings_202608.md` §3.2:

| TOP | VALUATION (131 TOP) | MARKETING (140 TOP) |
|---|---|---|
| 127 | CAPTIVE COVERSION | **% Add Top 95** |
| 128 | DEL. CONVERTION | **Value Top 95 before Process** |
| 129 | % Add Top 95 | **Top 95 X % Add** |
| 130–140 | — | **NSBC / R-AX / loss fields** |

Cost type **MARKETING** punya 140 TOP, dan TOP 130–140 persis berisi *"NSBC / R-AX / loss
fields"* — yaitu blok baris 85–92 & 94. Datanya ada di `CST_YARN_CALCULATION` (bentuk tall)
dan `CST_YARN_CALCULATION_CUR` (pivot, kolom `CYCC_TOP_130_DATA_VALUE` … `CYCC_TOP_140_DATA_VALUE`).

> ⚠️ **Trap yang sudah dicatat di phase5 dan masih relevan:** penomoran TOP **berbeda antar
> cost type mulai TOP 127**. Query yang dipakai untuk VALUATION (`prs_type = 20210800120`)
> akan mengembalikan arti yang berbeda kalau dipakai ulang untuk MARKETING. Kalau pengecekan
> "12.567 baris tidak ada isinya" kemarin dijalankan dengan filter cost type VALUATION, maka
> TOP 130–140 **memang** tidak ada di sana — bukan karena kosong, tapi karena tidak berlaku
> untuk cost type itu. Ini perlu diverifikasi ulang sebelum dijadikan kesimpulan.

---

## 4. Koreksi spesifik atas isi dokumen pertanyaan

### 4.1 ⚠️ Rumus "Top 95 X % Add" — **vonis ini sudah dikoreksi, lihat catatan di bawah**

> 🔄 **KOREKSI (15 Sep 2026, setelah `PKG_YARN_MARKETING.pkb` diterima).**
> Vonis "keliru" di bagian ini **terlalu keras dan saya tarik**. Source membuktikan
> **baris 81 = TOP 95**, sehingga rumus Ilham setara `TOP 95 × TOP 127 / 100` — sementara
> migration kita memakai `TOP 128 × TOP 127 / 100`. Package hanya *membaca* TOP 129, tidak
> menghitungnya, jadi **keduanya masih hipotesis** dan belum bisa diputuskan dari source.
> Cara menyelesaikannya (query pembanding) ada di
> `jawaban-kalkulasi-baris-81-95-dari-source-oracle.md` §5.2.
> Yang tetap berlaku dari bagian ini: `Value Top 95 before Process` (TOP 128) memang parameter
> tersendiri yang nyata dan berbeda dari TOP 95 — `fGet_Addl_NSBC_Loss` memperlakukan keduanya
> sebagai basis yang saling menggantikan.

Dokumen menulis:

> **Top 95 X % Add = Baris 81 × % Add Top 95 ÷ 100**

Ini **keliru**, dan menghilangkan satu parameter. Migration kita sendiri
(`migrations/V003__phase_b_parameter_master.sql:533-537`) sudah mendefinisikannya dengan benar:

```sql
(123,'PCT_ADD_TOP_95',   '% Add Top 95',                'ENTRY',       ... 'Top95', 123),
(124,'VALUE_TOP_95',     'Value Top 95 before Process', 'ENTRY',       ... 'Top95', 124),
(125,'TOP_95_X_PCT_ADD', 'Top 95 X % Add',              'CALCULATION', ...
  'PARAM(124) * PARAM(123) / 100', 'calcTop95XPctAdd', NULL, 'Top95', 125);
```

Yang dikalikan adalah **PARAM(124) = "Value Top 95 before Process"**, bukan baris 81. Cocok
persis dengan trio legacy MARKETING TOP 127/128/129 di tabel §3.

**Implikasi:** ini bukan "satu angka lalu dua isian langsung jalan". Ada **tiga** field, dan
field ketiga (`Value Top 95 before Process` / TOP 128) tidak disebut sama sekali di dokumen
pertanyaan. Kalau dokumen ini terkirim apa adanya, tim Finance akan menjawab pertanyaan yang
salah — dan kita akan mengimplementasikan rumus yang salah dengan restu mereka.

### 4.2 ❌ Pertanyaan "Istilah Top 95 maksudnya apa?"

Tidak perlu ditanyakan. **"Top 95" = parameter TOP nomor 95 di `CST_YARN_TOP`** — penomoran
internal parameter sistem lama, bukan "95% tertinggi dari sesuatu" dan bukan baris 95 pada
sheet. Nomor baris sheet (`CYCRL_SEQ_NO`) dan nomor parameter (`CYC_TOP_NO`) adalah dua
sistem penomoran yang berbeda dan kebetulan beririsan rentangnya. Kita sudah tahu ini dari
phase5; menanyakannya ke user justru membuat kita terlihat belum membaca temuan sendiri.

### 4.3 ❌ Dugaan "tarif recovery 60% untuk non-AX" (bagian 4 dokumen)

Analisis reverse-engineering-nya rapi dan kehati-hatiannya benar (2 sampel, 4 kandidat rumus,
tidak mau menebak — itu sikap yang tepat). Tapi seluruh latihan itu **tidak perlu**: rumus
baris 83 ada di `fGet_Addl_NSBC_Loss`. Membaca body fungsinya memberi jawaban **eksak**, bukan
`r ≈ 0,599` yang "sangat dekat ke 0,60".

Dan risikonya nyata: kalau ternyata fungsi itu memakai, misalnya, tarif per grade dari
`cst_mst_product_grade` (bukan satu konstanta 60%), maka pada produk %AX tinggi hasilnya akan
tetap terlihat ≈0,599 — persis jenis kesalahan "terlihat wajar tetapi salah" yang dokumen itu
sendiri khawatirkan di bagian 4.

### 4.4 ❌ Permintaan sampel Excel tambahan (bagian 5 dokumen)

Meminta tim Finance menyiapkan 2–3 cost sheet tambahan dengan %AX 48–60% adalah **kerja manual
yang tidak perlu untuk mereka**, sekaligus **metode yang lebih lemah** daripada membaca source.
Kalau nanti tetap ingin sampel, gunakan sebagai **verifikasi hasil**, bukan sebagai cara
menemukan rumus — dan sampelnya bisa kita generate sendiri dari legacy.

### 4.5 ✅ Yang sudah benar dan tetap dipakai

- **Baris 84 = baris 67 + baris 83** — pengecekan dua produk cocok persis. Ini konsisten
  dengan keberadaan `fGet_Dom_Cost_AX_Grd_Only` sebagai fungsi terpisah; tinggal dikonfirmasi
  ke body fungsinya.
- **Prioritas 81 di atas 85–94** karena 81 mengeluarkan angka salah (bukan strip) — penalaran
  risikonya tepat. Yang berubah hanya *cara* menyelesaikannya.
- **Nada dan sikap dokumen** ("tidak ada yang salah dengan cara kerja selama ini") — bagus.
  Simpan untuk dokumen berikutnya, yang isinya jauh lebih pendek.

---

## 5. Yang harus dilakukan — urutan konkret

Semua langkah di bawah dijalankan **oleh tim IT**, tanpa melibatkan user.

### Langkah 1 — Ambil body package (ini menyelesaikan baris 81, 82, 83, 84, 95, dan Top 95)

```sql
SELECT line, text
  FROM all_source
 WHERE owner = 'MGTAPPS'
   AND name  = 'PKG_YARN_MARKETING'
   AND type  = 'PACKAGE BODY'
 ORDER BY line;
```

Fungsi yang dicari: `fGet_CostLess_QL_CO_Frwd`, `fGet_NSBC_SP`, `fGet_Addl_NSBC_Loss`,
`fGet_Dom_Cost_AX_Grd_Only`, `fGetDomCost_AXGrdOnly_WExtrCst`, `fGet_Extra_Yarn_Persen`,
`fGet_Cost_Of_Extra_Yarn_Persen`.

Kalau package di-*wrap*, ambil dari file source-nya langsung di server `192.168.0.3`
(lihat pointer path di file Evidence), atau dari backup DDL.

### Langkah 2 — Ambil definisi tiap baris sheet

```sql
SELECT cycrl_seq_no,
       cycrl_description,
       cycrl_source_type,
       cycrl_source_query,
       cycrl_disp_in_final_product,
       cycrl_format_data,
       cycrl_length_decimal
  FROM mgtapps.cst_yarn_calc_rpt_lable
 WHERE cycrl_cycrm_sys_id = '20211206'
   AND to_number(cycrl_seq_no) BETWEEN 80 AND 95
 ORDER BY to_number(cycrl_seq_no);
```

Ini **menyelesaikan baris 85–92 dan 94** — `cycrl_source_query` berisi ekspresi/SQL-nya.

### Langkah 3 — Ambil formula terstruktur (untuk baris ber-`SOURCE_TYPE = 'From Formula'`)

```sql
SELECT l.cycrl_seq_no, l.cycrl_description,
       f.cycrf_seq_no,
       f.cycrf_top_no_1, f.cycrf_operator_1,
       f.cycrf_top_no_2, f.cycrf_operator_2,
       f.cycrf_top_no_3, f.cycrf_operator_3,
       f.cycrf_src_type_1, f.cycrf_src_type_2, f.cycrf_src_type_3
  FROM mgtapps.cst_yarn_calc_rpt_lable   l
  JOIN mgtapps.cst_yarn_calc_rpt_formula f ON f.cycrf_cycrl_sys_id = l.cycrl_sys_id
 WHERE l.cycrl_cycrm_sys_id = '20211206'
   AND to_number(l.cycrl_seq_no) BETWEEN 80 AND 95
 ORDER BY to_number(l.cycrl_seq_no), nvl(f.cycrf_seq_no,0);
```

Formula disimpan sebagai maksimal 3 operand + operator. Bisa juga dilihat visual lewat
form `CST_M_010` (tombol *Formula* pada baris terkait).

### Langkah 4 — Verifikasi ulang klaim "tidak ada datanya", kali ini dengan cost type MARKETING

```sql
SELECT cyc_top_no,
       COUNT(*)               AS jml_baris,
       COUNT(cyc_data_value)  AS jml_terisi
  FROM mgtapps.cst_yarn_calculation
 WHERE cyc_top_no BETWEEN 127 AND 140
 GROUP BY cyc_top_no
 ORDER BY cyc_top_no;
```

Pastikan filter cost type = **MARKETING** (`MGTAPPS.pkg_yarn_calculation.fPrsIDMkt`), **bukan**
`fPrsIDVal`. Kalau hasilnya terisi, kesimpulan "belum pernah terisi sejak awal" gugur dan
temuan ini harus dicabut dari dokumen pertanyaan sebelum dikirim ke siapa pun.

### Langkah 5 — Cross-check angka

Bandingkan output `pkg_yarn_marketing.fGet_*` untuk produk contoh di file Evidence
(**Left No 3126**, PTY TRIAL-FOR COMPARE, dan POY(83)) terhadap angka di cost sheet Excel.
Baru setelah cocok, port ke Go.

---

## 6. Apa yang benar-benar masih perlu ditanyakan ke user

Setelah Langkah 1–4, kemungkinan besar **tersisa satu hal**, dan itu bukan soal rumus:

> **`% Add Top 95` (legacy MARKETING TOP 127) dan `Value Top 95 before Process` (TOP 128)
> bertipe `ENTRY` — artinya nilainya memang diinput, bukan dihitung.**

Di migration kita, keduanya sudah ditandai `'ENTRY'` dengan owner `'Finance'`. Jadi
pertanyaan yang sah adalah: **siapa yang mengisi kedua angka ini, seberapa sering diperbarui,
dan apakah berbeda per produk/periode.** Nilai historisnya sendiri **tidak perlu ditanyakan**
— tinggal di-extract dari TOP 127/128 per produk.

Itu pun sebaiknya ditunda sampai Langkah 1–4 selesai, karena membaca body
`fGet_Extra_Yarn_Persen` mungkin sudah menjawabnya (bisa jadi fungsi itu membaca dari master
parameter, bukan dari input manual sama sekali).

**Semua pertanyaan lain di dokumen — baris 81, 82, 83, 84, 85–92, 94, arti "R", arti "SP",
arti "Top 95", tarif recovery 60%, permintaan sampel Excel — dijawab oleh source code.**

---

## 7. Rekomendasi

1. **Tahan pengiriman** `pertanyaan-tim-costing dari ilham.md` ke tim Finance/Costing.
2. Jalankan Langkah 1–4 di atas. Perkiraan: beberapa jam, bukan beberapa hari.
3. Tulis ulang dokumen pertanyaan → kemungkinan menyusut jadi **satu pertanyaan** (bagian 6),
   atau nol.
4. Simpan hasil ekstraksi (`fGet_*` + `cycrl_source_query` baris 80–95) sebagai dokumen
   referensi di repo, supaya tidak hilang lagi.
5. Catatan proses untuk ke depan: sebelum menyimpulkan "logic tidak ada di sistem lama",
   cek dulu **package PL/SQL** dan **tabel metadata report**, bukan hanya tabel data. Legacy
   ini menyimpan sebagian besar logic di dua tempat itu.

---

## Lampiran — daftar lengkap `CYCRL_SOURCE_TYPE` yang dikenal engine

Diekstrak dari `Costing_File/CST_FIND_PRODUCT_SQL.php` dan `PROD_COMPARATION_SQL.php`:

| `SOURCE_TYPE` | Perilaku |
|---|---|
| `NULL` | `CYCRL_SOURCE_QUERY` dipakai sebagai ekspresi kolom terhadap query data utama |
| `Source Query` / lainnya | `CYCRL_SOURCE_QUERY` dieksekusi sebagai SQL mentah, `:P_CYL_SYS_ID_DTL` di-substitusi |
| `Default Value` | `select <source_query> from dual` |
| `MB Name` | `pkg_yarn_marketing.fgetmbname` |
| `Formula STD SP AX` | `cst_mst_product_grade.CMPG_STD_SELLING_PRICE` via `cyc_top_no = 97` |
| `Final Conversion` | `fGet_Final_Conversion` |
| `CostLess_QL_CO_Frwd` | `fGet_CostLess_QL_CO_Frwd` → **baris 81** |
| `NSBC_SP` | `fGet_NSBC_SP` → **baris 82** |
| `Addl_NSBC_Loss` | `fGet_Addl_NSBC_Loss` → **baris 83** |
| `Dom_Cost_AX_Grd_Only` | `fGet_Dom_Cost_AX_Grd_Only` → **baris 84** |
| `D_C_A_G_O_W_E_C` | `fGetDomCost_AXGrdOnly_WExtrCst` → **baris 95** |
| `Extra_Yarn_Persen` | `fGet_Extra_Yarn_Persen` → **% Add Top 95** |
| `Cost_Of_Extra_Yarn_Persen` | `fGet_Cost_Of_Extra_Yarn_Persen` → **Top 95 X % Add** |

Query data utama (`CST_FIND_PRODUCT_SQL.php:9`), untuk referensi `SOURCE_TYPE = 'NULL'`:

```sql
SELECT * FROM mgtapps.cst_mst_yarn y, mgtapps.cst_yarn_left l, mgtapps.cst_yarn_calculation_cur cc
 WHERE cycc_cyl_sys_id = cyl_sys_id
   AND cyl_cmy_sys_id  = cmy_sys_id
   AND cyl_prs_type    = cycc_prs_type
   AND CYL_PRS_TYPE    = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt   -- MARKETING
```

### Lokasi source legacy (dari file Evidence)

| Apa | Di mana |
|---|---|
| Form maintenance report parameter | `192.168.0.3` → `D:\MGTAPPS\Forms\CST\CST_M_010.fmb` |
| Aplikasi web costing | `192.168.0.3` → `D:\XAMPP\htdocs\webapps\Costing_File` |
| Entry point cost sheet | `CST_T_008.php` (`$P_CYCRM_SYS_ID = "20211206"`) |
| Dispatch nilai per baris | `CST_FIND_PRODUCT_SQL.php` → `getDtVal()` (baris 511–591) |
| Render baris | `CST_FIND_PRODUCT_VIEW_DETAIL.php` |
