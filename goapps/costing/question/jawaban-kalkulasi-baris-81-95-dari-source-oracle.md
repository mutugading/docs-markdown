# Jawaban Lengkap — Kalkulasi Baris 81–95 (dari source `PKG_YARN_MARKETING`)

**Kepada:** Ilham
**Dari:** Indra Putro
**Tanggal:** 15 September 2026
**Status:** ✅ **Selesai — tidak perlu bertanya ke tim Finance/Costing sama sekali**
**Sumber:** `PKG_YARN_MARKETING.pkb` baris 2475–2681 (body asli dari server produksi)

---

## 0. Ringkasan eksekutif

Package body-nya sudah di tangan. **Ketujuh fungsi ada, lengkap, dan tidak di-wrap.**
Semua pertanyaan di dokumen `pertanyaan-tim-costing dari ilham.md` untuk baris **81, 82, 83,
84, 95, "% Add Top 95" dan "Top 95 X % Add" terjawab eksak** — bukan dugaan, bukan
reverse-engineering, tapi source aslinya.

Tiga hal yang perlu kamu tahu duluan karena mengubah cara kerja:

1. **Baris 81 bukan hasil pengurangan apa pun.** Namanya menyesatkan. Dia cuma membaca nilai
   parameter **TOP 95** apa adanya. Rumus yang kamu bangun dari label ("cost dikurangi QL, CO,
   Forwarding") memang tidak akan pernah cocok — itu sebabnya selisihnya -0,016 s/d +0,68.
2. **Kolom kosong di belakang baris 82 & 83 itu BY DESIGN, bukan input manual.** Semua fungsi
   ini diawali guard `if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs`. Kolom deep-material (bahan baku)
   pasti tidak memenuhi syarat itu, jadi pasti `NULL`. Bukti yang dipakai dokumen pertanyaan
   untuk menyimpulkan "diketik belakangan secara manual" **justru dihasilkan oleh kode**.
3. **Dugaan "tarif recovery 60%" tidak ada di kode.** Rumus baris 83 sama sekali bukan
   gross-up. Angka `r ≈ 0,599` yang muncul dari dua sampel itu kebetulan — persis jenis
   "cocok tapi salah" yang kamu khawatirkan sendiri di bagian 4. Bagus kamu berhenti di situ.

Sisa pekerjaan: **satu lookup nama parameter (TOP 23)** dan **satu pilihan basis untuk TOP 129**.
Keduanya di bagian 5.

---

## 1. Peta parameter — nomor TOP vs nomor baris sheet

Ini yang bikin kita muter-muter kemarin. **Nomor baris cost sheet (`CYCRL_SEQ_NO`) dan nomor
parameter (`CYC_TOP_NO`) adalah dua sistem penomoran berbeda.** Kebetulan rentangnya mirip,
jadi gampang tertukar.

Mapping berikut **terbukti dari kode**, bukan tebakan:

| TOP | Arti | Bukti di source |
|---|---|---|
| **TOP 55** | RM Rate | komentar `-- POY 6.RM Rate.` → `cyc_top_55` (`fGet_Final_Conversion`) |
| **TOP 73** | MB Cost | komentar `-- POY 39.MB Cost.` → `cyc_top_73` (`fGet_Final_Conversion`) |
| **TOP 95** | basis "Top 95" | dibaca `fGet_val95`, dipakai sbg nilai baris 81 |
| **TOP 104** | **Quality Loss** | `fgetQualityLoss()` membaca `CYCC_TOP_104_DATA_VALUE` |
| **TOP 121** | **Final Ex-Factory Cost** / Domestic Cost | `fgetFinalExFactoryCost()` membaca `CYCC_TOP_121_DATA_VALUE`; komentar `-- 64.Domestic Cost.` |
| **TOP 127** | % Add Top 95 | `fGet_Extra_Yarn_Persen` |
| **TOP 128** | Value Top 95 before Process | `fGet_val128`, dipakai sbg basis alternatif di baris 83 |
| **TOP 129** | Top 95 X % Add | `fGet_Cost_Of_Extra_Yarn_Persen` |
| **TOP 23** | ❓ persentase — **belum terkonfirmasi namanya** | lihat §5.1 |

> **"Top 95" = parameter TOP nomor 95.** Bukan "95% tertinggi dari sesuatu", bukan baris 95 di
> sheet. Dan karena baris 81 = TOP 95, maka **baris 81 ITU SENDIRI adalah "Top 95"**. Pertanyaan
> di dokumenmu bagian "Tambahan" no.1 terjawab di sini.

Konstanta tambahan:

| Simbol | Sumber |
|---|---|
| `getPrdFowarding` | `MST_PARAMS.PARAM_VALUE WHERE PARAM_ID = 'CST_PRODUCT_FOWARDING'` — satu nilai global, bukan per produk |

---

## 2. Aturan yang berlaku untuk SEMUA fungsi di blok ini

Sebelum masuk per baris. Tiga aturan ini wajib kamu replikasi, kalau tidak angkanya akan beda
walaupun rumusnya benar.

### 2.1 Guard "hanya kolom produk sendiri"

Setiap fungsi dibuka dengan:

```sql
if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then ... end if;
return null;   -- atau 0
```

Pemanggilnya (`CST_FIND_PRODUCT_SQL.php`):
`fGet_Addl_NSBC_Loss('$P_CYL_SYS_ID_DTL', '$CylSysId_dt')` — argumen 1 = produk final,
argumen 2 = produk pada kolom yang sedang dirender.

Cost sheet legacy punya **beberapa kolom** (produk final + tiap level bahan baku). Guard ini
membuat baris 81–84 & 95 **hanya terisi di kolom produk final**, dan **selalu kosong di kolom
deep-material**. Itulah penjelasan "kolom di belakang baris 82 dan 83 kosong untuk semua produk".

### 2.2 Nol ditampilkan sebagai strip

Di `CST_FIND_PRODUCT_SQL.php` (`getDtVal`, ±baris 579):

```php
if ($dtVal==="" || $dtVal==="0"){ $dtVal="-"; }
```

Jadi **nilai 0 yang sah pun tampil sebagai `-`**, tidak dibedakan dari NULL. Kalau sistem baru
menampilkan `0,000` di tempat legacy menampilkan `-`, itu beda tampilan — bukan beda hitungan.

### 2.3 Exception ditelan diam-diam

`fGet_Dom_Cost_AX_Grd_Only` dan `fGetDomCost_AXGrdOnly_WExtrCst` ditutup dengan:

```sql
exception when others then return null;
```

Kalau TOP 121 tidak ada / tidak bisa di-cast ke number, baris 84 & 95 **diam-diam jadi kosong**,
tanpa error. Silakan putuskan sendiri apakah perilaku ini mau ditiru atau diganti error yang
terlihat — tapi **sadari ada bedanya** saat rekonsiliasi: kosong di legacy belum tentu berarti
"tidak ada data", bisa jadi "perhitungannya gagal diam-diam".

---

## 3. Rumus per baris — lengkap

### Baris 81 — `81.Cost lessQL,CO,Frwd.`

```sql
function fGet_CostLess_QL_CO_Frwd(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs) return varchar2 is
    vRtn varchar2(300) := null;
begin
    if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
        --vRtn := fGet_val128(pCyl_Sys_Id_Dt);
        vRtn := fGet_val95(pCyl_Sys_Id_Dt);
    end if;
    return vRtn;
end;
```

dengan

```sql
function fGet_val95(pCyl_Sys_Id_Dt) is
begin
    for recDt in (select * from cst_yarn_calculation
                   where cyc_cyl_sys_id = pCyl_Sys_Id_Dt and cyc_top_no = 95) loop
        return recDt.cyc_data_value;
    end loop;
    return null;
end;
```

> ### **Baris 81 = nilai TOP 95, apa adanya.**

**Jawaban untuk 5 pertanyaanmu di bagian 3:**

| # | Pertanyaanmu | Jawaban |
|---|---|---|
| 1 | "= biaya dikurangi QL, CO, Forwarding, betul?" | **Tidak.** Tidak ada operasi pengurangan apa pun. Labelnya peninggalan historis. |
| 2 | "angka awal diambil dari baris berapa?" | Bukan dari baris sheet mana pun — dari parameter **TOP 95**. |
| 3 | "komponen mana yang dikurangi?" | Tidak ada. |
| 4 | "QL itu baris 76+77 atau salah satu?" | Tidak relevan — QL tidak ikut di sini sama sekali. (QL = TOP 104, dipakainya di **baris 83**.) |
| 5 | "ada yang ditambahkan kembali?" | Tidak ada. |

**Aksi:** hapus rumus baris 81 di sistem baru, ganti jadi *passthrough* nilai TOP 95.
Ini juga menutup risiko "angka salah yang terlihat wajar" yang kamu jadikan Prioritas 2 —
dan menutupnya secara tuntas, bukan dengan tebakan.

> 📌 Perhatikan baris yang di-*comment*: `--vRtn := fGet_val128(...)`. Dulu baris 81 memakai
> TOP 128, lalu diubah ke TOP 95. Kalau menemukan data historis lama yang tidak cocok, kemungkinan
> itu sebabnya. Relevan juga untuk §5.2.

---

### Baris 82 — `82.NSBC SP.`

```sql
function fGet_NSBC_SP(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs) return varchar2 is
begin
    if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
        for recDt in (select * from cst_mst_product_grade
                       where cmpg_sys_id = 20230641) loop
            return nvl(recDt.CMPG_STD_SELLING_PRICE, 0);
        end loop;
    end if;
    return null;
end;
```

> ### **Baris 82 = `CMPG_STD_SELLING_PRICE` dari satu baris master yang di-hardcode: `cmpg_sys_id = 20230641`.**

**Jawaban untuk 5 pertanyaanmu:**

| # | Pertanyaanmu | Jawaban |
|---|---|---|
| 1 | hitungan / tarif tetap / manual? | **Tarif tetap**, dari master `cst_mst_product_grade`. |
| 2 | selalu 1, atau beda per grade/tipe? | **Sama untuk semua produk** — sys_id-nya di-hardcode, tidak ada lookup per grade/tipe. Nilainya "1" karena isi master saat ini 1. |
| 3 | kalau hitungan, dari baris berapa? | Bukan hitungan. |
| 4 | berhubungan dengan `74.STD SP BC.` / `73.STD SP AX.`? | **Ya, satu tabel dan satu kolom** (`cst_mst_product_grade.CMPG_STD_SELLING_PRICE`). Bedanya: baris 73/74 *lookup by grade* (lewat TOP 97), baris 82 **mengunci satu baris master spesifik**. |
| 5 | "SP" artinya apa? | **Std Selling Price** — dari nama kolomnya. |

**Aksi:** di sistem baru jadikan **parameter master**, bukan input per produk. Tapi jangan
di-hardcode angka `1`-nya — hardcode *referensinya* ke baris master itu, supaya kalau Finance
mengubah harga di master, sistem baru ikut berubah seperti legacy.

> ⚠️ `cmpg_sys_id = 20230641` adalah magic number di source produksi. Wajib dicatat di migration,
> karena tidak ada satu pun dokumen yang menjelaskan kenapa baris master itu yang dipilih.

---

### Baris 83 — `83.Addl. NSBC Loss.` ← prioritas 1 kamu

```sql
function fGet_Addl_NSBC_Loss(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs) return varchar2 is
    vNSBC_SP varchar2(30) := null;
begin
    if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
        for recDt in ( /* ambil val23, val95, val104, val127, val128 dari cst_yarn_calculation */ ) loop

            vNSBC_SP := fGet_NSBC_SP(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs);

            if recDt.val127 <> 0 then          -- add 10-04-2026 req by mbak sintia
                return (((100 - recDt.val23) / 100) * (recDt.val128 - vNSBC_SP)) - recDt.val104;
            else
                return (((100 - recDt.val23) / 100) * (recDt.val95  - vNSBC_SP)) - recDt.val104;
            end if;

        end loop;
    end if;
    return null;
end;
```

> ### Rumus final
>
> ```
> basis  = (TOP 127 ≠ 0) ? TOP 128 : TOP 95
> Baris83 = ((100 − TOP 23) / 100) × (basis − Baris82) − TOP 104
> ```
>
> — di mana **TOP 104 = Quality Loss** dan **Baris 82 = NSBC SP** (§3 di atas).

Semua operand di-`nvl(...,0)` di subquery, jadi parameter yang hilang diperlakukan sebagai 0.

**Jawaban untuk 4 pertanyaanmu + bagian 4 dokumen:**

| # | Pertanyaanmu | Jawaban |
|---|---|---|
| 1 | hitungan / tarif tetap / manual? | **Hitungan penuh.** Tidak ada input manual. |
| 2 | dari baris berapa? `67.Domestic Cost.` ikut dipakai? | **Tidak.** Domestic Cost (TOP 121) **tidak** dipakai di baris 83. Yang dipakai: TOP 23, TOP 95/128, TOP 104, dan baris 82. |
| 3 | persentase grade (baris 25 `AX.` – 30 `C.`) ikut? | **Tidak langsung.** Yang masuk hanya **satu** persentase: TOP 23 (lihat §5.1). Tidak ada penjumlahan per grade AE/A9/A/B/C. |
| 4 | beda per grade / tipe produk? | **Tidak.** Satu rumus untuk semua. Yang berbeda hanya nilai parameternya per produk. |

**Tentang hipotesis "gross-up ke basis 100% AX" dan tarif 60% di bagian 4 dokumen:**

❌ **Tidak benar.** Rumus aslinya adalah **perkalian linear**, bukan pembagian/gross-up. Tidak
ada konstanta 0,6 di mana pun dalam package. Bentuk `(100 − TOP23)/100 × (selisih harga)` secara
bisnis berarti: *"porsi produksi yang bukan [TOP 23] × selisih nilai per kg, dikurangi Quality
Loss"* — sebuah **kerugian nilai**, bukan pembebanan ulang biaya.

Kenapa `r ≈ 0,599` bisa muncul konsisten di dua sampel? Karena modelmu punya satu parameter bebas
(`r`) dan kamu mencocokkannya ke dua titik yang **dua-duanya %AX tinggi**. Dua model yang berbeda
secara struktural bisa berhimpit di rentang sempit. Itu persis alasan kamu minta sampel %AX 48–60%
— instingnya benar, cuma sekarang tidak perlu lagi.

**Catatan cabang `val127 ≠ 0`:** ditambahkan **10 April 2026 atas permintaan user** (komentar
`req by mbak sintia` ada di source). Artinya produk yang punya "% Add Top 95" memakai basis
TOP 128, sisanya TOP 95. Kalau sistem baru mengabaikan cabang ini, produk dengan extra-yarn akan
salah hitung. **Ini logika bisnis yang baru berumur 5 bulan** — data sebelum April 2026 dihitung
dengan cabang `else` untuk semua produk. Penting untuk rekonsiliasi historis.

> ⚠️ **Jangan percaya komentar lama di atas rumusnya.** Baris komentar
> `--(((100-CYCC_TOP_23_DATA_VALUE))%*128)-CYCC_TOP_104_DATA_VALUE` dan
> `--return ((100-nvl(recDt.CYCC_TOP_23_DATA_VALUE)/100) * ...` memakai **kurung yang berbeda**
> (`100 − val23/100`, bukan `(100 − val23)/100`) dan **tidak mengurangi NSBC SP**. Itu versi usang.
> Pakai kode yang aktif.

---

### Baris 84 — `84.Domestic Cost AX grd only.`

```sql
function fGet_Dom_Cost_AX_Grd_Only(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs) return number is
begin
    if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
        for recDt in (
            select nvl(CYCC_TOP_121_DATA_VALUE,0)
                 + nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) GET_DATA
            from mgtapps.cst_yarn_calculation_cur
            where CYCC_CYL_SYS_ID = pCyl_Sys_Id_Prs
              and CYCC_PRS_TYPE   = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
        ) loop
            vDomestic_Cost := recDt.GET_DATA;
        end loop;

        vAddl_NSBC_Loss := fGet_Addl_NSBC_Loss(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs);
        vRtn := vDomestic_Cost + vAddl_NSBC_Loss;
    end if;
    return vRtn;
exception when others then return null;
end;
```

> ### **Baris 84 = (TOP 121 + Forwarding) + Baris 83**

✅ **Temuanmu benar dan sekarang terbukti dari source.** Kamu menyimpulkan `baris 84 = baris 67 +
baris 83` dari dua produk — cocok persis. Source mengonfirmasi, sekaligus memberi bonus:
**baris 67 (`Domestic Cost. (AX~AM)`) = TOP 121 + `CST_PRODUCT_FOWARDING`**.

Perhatikan filter **`CYCC_PRS_TYPE = fPrsIDMkt`** — cost type **MARKETING**, bukan VALUATION.
Ini konsisten dengan trap TOP-127-ke-atas yang sudah kita catat di
`phase5_findings_202608.md` §3.2.

---

### Baris 95 — `95.Domestic cost with uneven packing.`

```sql
function fGetDomCost_AXGrdOnly_WExtrCst(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs) return number is
begin
    if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
        vDom_Cost_AX_Grd_Only      := fGet_Dom_Cost_AX_Grd_Only(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs);
        vCost_Of_Extra_Yarn_Persen := fGet_Cost_Of_Extra_Yarn_Persen(pCyl_Sys_Id_Dt, pCyl_Sys_Id_Prs);
        vRtn := vDom_Cost_AX_Grd_Only + vCost_Of_Extra_Yarn_Persen;
    end if;
    return vRtn;
exception when others then return null;
end;
```

> ### **Baris 95 = Baris 84 + TOP 129 ("Top 95 X % Add")**

Kamu menandai baris 95 "secara rumus sudah siap, tinggal deploy". **Mohon dicek ulang** — kalau
rumus di sistem baru bukan `baris 84 + TOP 129`, berarti belum siap.

---

### `% Add Top 95` dan `Top 95 X % Add`

```sql
function fGet_Extra_Yarn_Persen(...)        -- baca cst_yarn_calculation, cyc_top_no = 127
function fGet_Cost_Of_Extra_Yarn_Persen(...) -- baca cst_yarn_calculation, cyc_top_no = 129
```

> ### **`% Add Top 95` = TOP 127 (dibaca apa adanya)**
> ### **`Top 95 X % Add` = TOP 129 (dibaca apa adanya)**

**Keduanya cuma passthrough — tidak ada perkalian di package ini.** Perkalian
`Top95 × %Add ÷ 100` dilakukan di **engine kalkulasi** (`PKG_YARN_CALCULATION`, yang belum kita
punya), lalu hasilnya disimpan sebagai TOP 129.

**Jawaban untuk 4 pertanyaanmu di bagian "Tambahan":**

| # | Pertanyaanmu | Jawaban |
|---|---|---|
| 1 | "Top 95" maksudnya apa? | **Parameter TOP nomor 95** — dan itu sama dengan **baris 81**. Bukan "95% tertinggi", bukan baris 95. |
| 2 | berapa persen angkanya? | **Tidak ada satu angka tetap.** TOP 127 tersimpan **per produk** di `cst_yarn_calculation`. Tinggal di-extract, tidak perlu ditanya. |
| 3 | sama untuk semua produk? | **Tidak** — per produk. |
| 4 | siapa yang menentukan? | Ini **satu-satunya pertanyaan yang masih sah** ke user, dan sifatnya tata kelola (siapa input, seberapa sering), bukan rumus. Nilai historisnya sudah ada di TOP 127. |

> **Koreksi atas dokumenmu:** kamu menulis *"begitu satu angka (% Add Top 95) ditentukan, keduanya
> langsung jalan"*. Tidak begitu — **TOP 127 sudah terisi per produk di legacy**. Tidak ada angka
> yang perlu "ditentukan"; yang perlu adalah **memigrasikan datanya**.

---

## 4. Ringkasan untuk diimplementasikan

Urutan hitung (dependency order):

```
Baris 82 = cst_mst_product_grade.CMPG_STD_SELLING_PRICE  @ cmpg_sys_id = 20230641
Baris 81 = TOP 95
%AddTop95 = TOP 127
Top95X%Add = TOP 129
Baris 67 = TOP 121 + MST_PARAMS['CST_PRODUCT_FOWARDING']

Baris 83 = ((100 − TOP 23)/100) × ( (TOP 127 ≠ 0 ? TOP 128 : TOP 95) − Baris 82 ) − TOP 104
Baris 84 = Baris 67 + Baris 83
Baris 95 = Baris 84 + TOP 129
```

Aturan lintas-baris (§2): guard kolom-produk-sendiri, `0` → `-`, exception → NULL diam-diam.

| Baris | Status | Perlu tanya user? |
|---|---|---|
| 81 | ✅ Tuntas | Tidak |
| 82 | ✅ Tuntas | Tidak |
| 83 | ✅ Tuntas (kecuali nama TOP 23, §5.1) | Tidak |
| 84 | ✅ Tuntas | Tidak |
| 95 | ✅ Tuntas | Tidak |
| % Add Top 95 | ✅ Tuntas — migrasikan TOP 127 | Hanya tata kelola |
| Top 95 X % Add | ⚠️ Passthrough TOP 129 jelas; **rumus asalnya** di `PKG_YARN_CALCULATION` (§5.2) | Tidak |
| 85–92, 94 | ⛔ Tidak ada di package ini — lihat §6 | Tidak |

---

## 5. Dua hal yang masih terbuka (dua query, bukan dua pertanyaan)

### 5.1 Nama TOP 23

TOP 23 **hanya muncul sekali** di seluruh package — di `fGet_Addl_NSBC_Loss`. Jadi namanya tidak
bisa disimpulkan dari source ini. Dari bentuk `(100 − TOP23)/100`, dia **pasti persentase**, dan
dari konteks bisnis besar kemungkinan **%AX**. Tapi jangan diasumsikan — sekali query selesai:

```sql
SELECT cyt_top_no, cyt_description
  FROM mgtapps.cst_yarn_top
 WHERE cyt_top_no IN (23, 95, 104, 121, 127, 128, 129)
 ORDER BY cyt_top_no;
```

*(Nama kolom `cst_yarn_top` belum saya verifikasi — sesuaikan kalau berbeda.)*

Sekalian validasi silang dengan data nyata:

```sql
SELECT cyc_cyl_sys_id,
       MAX(CASE WHEN cyc_top_no = 23 THEN cyc_data_value END) AS top_23
  FROM mgtapps.cst_yarn_calculation
 WHERE cyc_cyl_sys_id IN ( /* produk yang %AX-nya kamu tahu dari sheet */ )
 GROUP BY cyc_cyl_sys_id;
```

Kalau `top_23` sama dengan nilai baris `25.AX.` di sheet → terkonfirmasi %AX.

### 5.2 Basis untuk TOP 129 — ini yang mengoreksi dokumen saya sebelumnya

Di dokumen `klarifikasi-pertanyaan-ilham-baris-81-95.md` §4.1 saya menyebut rumusmu
(`Top 95 X % Add = Baris 81 × % Add ÷ 100`) **"keliru"**. **Vonis itu terlalu keras — saya tarik.**

Alasannya: sekarang terbukti **baris 81 = TOP 95**, jadi rumusmu setara `TOP 95 × TOP 127 / 100`.
Sementara migration kita memakai `PARAM(124) × PARAM(123)/100` = `TOP 128 × TOP 127 / 100`.
Package ini **tidak menghitung TOP 129** — cuma membacanya — jadi **tidak bisa memutuskan mana
yang benar.** Dua-duanya masih hipotesis.

Yang menarik: `fGet_Addl_NSBC_Loss` memperlakukan TOP 95 dan TOP 128 sebagai **dua basis yang
berbeda dan saling menggantikan** (`val127 ≠ 0 ? val128 : val95`). Jadi keduanya nyata-nyata
bukan angka yang sama.

> 🔄 **UPDATE (setelah `PKG_YARN_CALCULATION.pkb` diterima).** Sudah saya cek: **rumus TOP 129
> tidak ada di kode mana pun** — engine kalkulasi legacy sepenuhnya data-driven, rumusnya
> tersimpan di tabel `CST_YARN_FORMULA_CALC`. Jadi pertanyaan TOP 95 vs TOP 128 dijawab oleh
> **satu query**, bukan analisis. Lihat
> **[`jawaban-top-129-dan-engine-kalkulasi-legacy.md`](./jawaban-top-129-dan-engine-kalkulasi-legacy.md)** §4.3.
> Dokumen itu juga memuat 6 jebakan porting yang mempengaruhi **semua** baris di bawah —
> terutama **pembulatan 4 desimal berantai**, yang bisa menjelaskan sisa selisih kecil.

Settle-nya pakai data, bukan diskusi:

```sql
SELECT cyc_cyl_sys_id,
       MAX(CASE WHEN cyc_top_no =  95 THEN to_number(cyc_data_value) END) AS top_95,
       MAX(CASE WHEN cyc_top_no = 127 THEN to_number(cyc_data_value) END) AS pct_add,
       MAX(CASE WHEN cyc_top_no = 128 THEN to_number(cyc_data_value) END) AS top_128,
       MAX(CASE WHEN cyc_top_no = 129 THEN to_number(cyc_data_value) END) AS top_129
  FROM mgtapps.cst_yarn_calculation
 GROUP BY cyc_cyl_sys_id
HAVING MAX(CASE WHEN cyc_top_no = 127 THEN to_number(cyc_data_value) END) <> 0
 FETCH FIRST 50 ROWS ONLY;
```

Lalu cek: `top_129` lebih dekat ke `top_95 × pct_add/100` atau `top_128 × pct_add/100`?
Yang cocok, itu yang benar. Kalau tidak ada yang cocok, ambil `PKG_YARN_CALCULATION` dan baca
handler TOP 129-nya.

---

## 6. Baris 85–92 (`R-*`) dan 94 — tetap di luar package ini

Saya cek spec (`PKG_YARN_MARKETING.pks`): **tidak ada fungsi apa pun** untuk blok `R-*` atau
`Addl Val Loss`. Jadi analisis di dokumen sebelumnya tetap berlaku — definisinya ada di
`CYCRL_SOURCE_QUERY` pada tabel label, dan datanya di TOP 130–140 (cost type MARKETING).

Dua query dari dokumen sebelumnya masih perlu dijalankan:

```sql
-- definisi baris
SELECT cycrl_seq_no, cycrl_description, cycrl_source_type, cycrl_source_query,
       cycrl_disp_in_final_product
  FROM mgtapps.cst_yarn_calc_rpt_lable
 WHERE cycrl_cycrm_sys_id = '20211206'
   AND to_number(cycrl_seq_no) BETWEEN 80 AND 95
 ORDER BY to_number(cycrl_seq_no);

-- isi data, pastikan cost type MARKETING (fPrsIDMkt), BUKAN fPrsIDVal
SELECT cyc_top_no, COUNT(*) jml, COUNT(cyc_data_value) terisi
  FROM mgtapps.cst_yarn_calculation
 WHERE cyc_top_no BETWEEN 127 AND 140
 GROUP BY cyc_top_no ORDER BY cyc_top_no;
```

Sesuai prioritas 5 di dokumenmu — boleh belakangan, karena tidak ikut tercetak di sheet final
(flag `CYCRL_DISP_IN_FINAL_PRODUCT`).

---

## 7. Rekomendasi penutup

1. **Batalkan pengiriman dokumen pertanyaan** ke tim Finance/Costing. Dari ~30 pertanyaan,
   yang tersisa hanya satu soal tata kelola (§3 "% Add Top 95" no.4), dan itu tidak mendesak.
2. **Jangan minta sampel Excel %AX 48–60%.** Tidak perlu lagi — dan kalaupun mau verifikasi,
   sampelnya bisa kita generate sendiri dari legacy.
3. **Prioritas kerja sekarang:** baris 81 (passthrough TOP 95 — menghilangkan angka salah yang
   sekarang tampil), lalu 82 → 83 → 84 → 95 mengikuti urutan dependency di §4.
4. **Jalankan dua query di §5** sebelum implementasi baris 83 dan TOP 129 dikunci.
5. **Catat di migration:** magic number `cmpg_sys_id = 20230641`, konstanta
   `MST_PARAMS['CST_PRODUCT_FOWARDING']`, dan cabang `val127 ≠ 0` (berlaku sejak 10 April 2026).
6. **Simpan `PKG_YARN_MARKETING.pkb`/`.pks` di repo** sebagai lampiran referensi, supaya tidak
   hilang lagi.

### Catatan untuk retrospektif

Yang sebenarnya terjadi: cost sheet legacy menyimpan logic di **tiga** lapis —
tabel data (`CST_YARN_CALCULATION`), tabel metadata report (`CST_YARN_CALC_RPT_LABLE` /
`_FORMULA`), dan **package PL/SQL**. Kemarin kita hanya menyisir lapis pertama, lalu
menyimpulkan "tidak ada di sistem lama". Untuk baris-baris berikutnya, cek ketiga lapis dulu
sebelum menyimpulkan sesuatu hilang.

Di luar itu: cara kamu menahan diri untuk tidak memakai rumus yang "cocok di dua sampel" itu
keputusan yang tepat, dan terbukti menyelamatkan — kandidat 60% itu memang salah.
