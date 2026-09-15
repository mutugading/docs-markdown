# Jawaban TOP 129 — dan Cara Kerja Engine Kalkulasi Legacy

**Kepada:** Ilham
**Dari:** Indra Putro
**Tanggal:** 15 September 2026
**Sumber:** `PKG_YARN_CALCULATION.pkb` (298 KB) + `.pks`
**Melengkapi:** `jawaban-kalkulasi-baris-81-95-dari-source-oracle.md` §5.2

---

## 0. Jawaban singkat

**Rumus TOP 129 tidak ada di dalam kode — dan memang tidak akan pernah ada.**

Saya sudah grep seluruh package: **string `129` tidak muncul satu kali pun.** Itu bukan
karena filenya tidak lengkap. Itu karena **engine kalkulasi legacy sepenuhnya data-driven**:
tidak ada satu pun TOP yang rumusnya ditulis di PL/SQL. Semua rumus — untuk semua 140 TOP —
disimpan sebagai **baris tabel**, lalu dieksekusi oleh satu interpreter generik.

Jadi pertanyaan "apa rumus TOP 129?" berubah bentuk menjadi: **"apa isi baris
`CST_YARN_FORMULA_CALC` untuk TOP 129?"** — dan itu satu query, ada di §4.

Kabar bagusnya jauh lebih besar dari sekadar TOP 129: **begitu kamu bisa membaca dan
menjalankan struktur ini, kamu dapat rumus SELURUH cost sheet sekaligus**, bukan cuma baris
81–95. Ini yang seharusnya jadi fondasi engine di sistem baru.

> ⚠️ Konsekuensi arsitektural yang perlu diputuskan tim: rumus di legacy adalah **data per
> produk**, bukan kode. Detailnya di §6 — ini mempengaruhi desain, bukan cuma satu baris.

---

## 1. Arsitektur engine — tiga kolom yang menjalankan semuanya

Setiap baris di `CST_YARN_CALCULATION` (satu baris = satu produk × satu TOP) punya:

| Kolom | Isi |
|---|---|
| `CYC_FORMULA_TYPE` | **jenis handler** — menentukan cara menghitung |
| `CYC_FORMULA_SCRIPT` | parameter/script untuk handler tertentu |
| `CYC_PROCESS_SEQ` | **urutan eksekusi** (dependency order) |
| `CYC_DATA_VALUE` | hasil hitungan, ditulis balik ke baris yang sama |
| `CYC_UPD_YARN_LEFT` | opsional: nama kolom di `CST_YARN_LEFT` yang ikut di-update |

Prosedur `process()` (baris ±1418) melakukan satu loop besar:

```sql
for recYarn in (
    select * from CST_YARN_CALCULATION t
    where CYC_CYL_SYS_ID in (select CYF_CYL_SYS_ID from CST_YARN_FILTER where ...)
      and CYC_PRS_TYPE = pPRS_TYPE
    order by CYC_PROCESS_SEQ, CYC_LEFT_NO, CYC_TOP_NO     -- ⬅ urutan dari data
) loop
    if  upper(recYarn.CYC_FORMULA_TYPE) = 'LOV_DATA'      then ...
    elsif ... = 'INITIAL_VALUE'                           then ...
    elsif ... = 'FROM_DATA'                               then ...
    elsif ... = 'FORMULA_DATA'                            then ...   -- ⬅ yang kita butuhkan
    elsif ... = 'IF_CONDITION'                            then ...
    ...
```

### Daftar lengkap handler

| # | `CYC_FORMULA_TYPE` | Sumber nilai |
|---|---|---|
| 1 | `LOV_DATA` | list of values |
| 2 | `INITIAL_VALUE` | konstanta di `CYC_FORMULA_SCRIPT` |
| 3 | `FROM_DATA` | salin dari TOP lain via `CST_YARN_SAME_ROWS` |
| 4 | **`FORMULA_DATA`** | **aritmatika via `CST_YARN_FORMULA_CALC`** |
| 5 | `IF_CONDITION` | percabangan via `CST_YARN_IFCOND_HDR` |
| 6 | `FROM_MASTER_YARN` | master yarn |
| 7 | `FROM_MASTER_MACHINE` | master mesin |
| 8 | `FROM_BOX_BOBIN_COST` | master packing |
| 10 | `RAW_MATERIAL` | struktur RM |
| 11 | `FROM_PRODUCT_GRADE` | `cst_mst_product_grade` |
| 12 | `FROM_MASTER_BATCH_SPINNING` / `FROM_MASTER_BATCH_DATA` | master batch/MB |
| — | `SHADE_CODE` | shade |

**TOP 129 hampir pasti `FORMULA_DATA`** (karena "Top 95 X % Add" adalah perkalian murni) —
tapi jangan diasumsikan, query di §4 sekalian mengonfirmasi.

---

## 2. Cara `FORMULA_DATA` dieksekusi (handler #4)

Ini yang perlu kamu port. Rumus disimpan di **`CST_YARN_FORMULA_CALC`** (`CYFC_*`),
satu-ke-banyak terhadap satu baris kalkulasi:

```sql
select distinct * from MGTAPPS.CST_YARN_FORMULA_CALC
where CYFC_CYC_SYS_ID = recYarn.CYC_SYS_ID
order by CYFC_SEQ_NO
```

Tiap baris `CYFC` = satu suku, dengan **maksimal 3 operand**:

| Slot | Kolom |
|---|---|
| operand 1 | `CYFC_TYPE_1`, `CYFC_VALUE_1`, `CYFC_TOP_NO_1` |
| operand 2 | `CYFC_TYPE_2`, `CYFC_VALUE_2`, `CYFC_TOP_NO_2` |
| operand 3 | `CYFC_TYPE_3`, `CYFC_VALUE_3`, `CYFC_TOP_NO_3` |
| operator dalam-suku | `CYFC_OPERATOR_2`, `CYFC_OPERATOR_3` |
| operator antar-suku | `CYFC_OPERATOR_1` |

### Pseudocode ekuivalen

```
vDataValue := 0
FOR setiap baris CYFC (urut CYFC_SEQ_NO):

    f1 := resolve(TYPE_1, VALUE_1, TOP_NO_1)
    f2 := resolve(TYPE_2, VALUE_2, TOP_NO_2)   -- hanya jika VALUE_2 atau TOP_NO_2 tidak null
    f3 := resolve(TYPE_3, VALUE_3, TOP_NO_3)   -- hanya jika VALUE_3 atau TOP_NO_3 tidak null

    -- gabung DALAM suku
    IF OPERATOR_2 IS NOT NULL AND OPERATOR_3 IS NOT NULL THEN
        vDataFormula := eval("f1 <OP2> f2 <OP3> f3")      -- ⚠ presedensi SQL
    ELSIF OPERATOR_2 IS NOT NULL THEN
        vDataFormula := eval("f1 <OP2> f2")
    ELSE
        vDataFormula := f1
    END IF

    -- akumulasi ANTAR suku
    vDataValue := eval("vDataValue <OPERATOR_1> vDataFormula")

END FOR

CYC_DATA_VALUE := round(vDataValue, 4)
```

### `resolve()` — cara tiap operand diambil

| `CYFC_TYPE_n` | Cara resolve |
|---|---|
| `INITIAL_VALUE` | konstanta `CYFC_VALUE_n` apa adanya |
| `FROM_DATA` | `SELECT nvl(trim(CYC_DATA_VALUE),0) FROM CST_YARN_CALCULATION WHERE CYC_TOP_NO = CYFC_TOP_NO_n AND CYC_PRS_TYPE = <prs> AND CYC_LEFT_NO = <left_no produk ini>` |
| `Lov Data` (+ `CYFC_VALUE_LOV_1 = 'INTERMINGLING DATA'`) | `CST_YARN_MST_INTERMINGLING.CYMI_VALUE @ CYMI_SYS_ID = CYFC_SYS_ID_LOV_1` |

Dua tipe khusus dicek **di `CYFC_TYPE_1` sebelum jalur 3-operand**, dan menggantikan seluruh suku:

| Tipe khusus | Hasil |
|---|---|
| `SQRT` | `SQRT( val(CYFC_VALUE_1) / val(CYFC_VALUE_2) )` — di sini `VALUE_1`/`VALUE_2` adalah **`CYC_SYS_ID`**, bukan TOP no |
| `FROM_TX_WEIGHT` | `pkg_mst_value.fYARN_TX_WEIGHT(CYFC_VALUE_1)` |

> 📌 Operand `FROM_DATA` selalu diambil dari **produk yang sama** (`CYC_LEFT_NO` yang sedang
> diproses). Tidak ada referensi silang antar produk di level ini. `LEFT_NO` = nomor produk —
> yang di layar legacy tampil sebagai **"Left No"** (mis. `3126` di file Evidence).

---

## 3. Enam jebakan porting — wajib dibaca sebelum implementasi

Ini bagian yang paling menentukan apakah angka sistem baru akan cocok atau meleset tipis.

### 3.1 ⚠️ Presedensi operator campur aduk

Ekspresi **dalam satu suku** dirakit jadi string lalu dievaluasi Oracle SQL:

```sql
vDataSql := 'select '||f1||' '||OP2||' '||f2||' '||OP3||' '||f3||' from dual';
```

**Tanpa tanda kurung.** Jadi `2 + 3 * 4` = **14**, bukan 20 — presedensi SQL berlaku (`*`,`/`
lebih kuat dari `+`,`-`).

Tapi **antar suku**, akumulasinya dievaluasi satu per satu:

```sql
vDataSql := 'select '||vDataValue||' '||OP1||' '||vDataFormula||' from dual';
```

→ **murni kiri-ke-kanan**, presedensi tidak berlaku lintas suku.

**Artinya satu rumus memakai dua aturan berbeda sekaligus.** Kalau kamu port dengan
parser ekspresi biasa (semua presedensi) atau dengan reduce kiri-ke-kanan (semua sekuensial),
**dua-duanya akan salah** untuk sebagian rumus. Harus persis: presedensi di dalam suku,
sekuensial antar suku.

### 3.2 ⚠️ Pembulatan 4 desimal di setiap penyimpanan

```sql
vDataValue := round(to_number(vDataValue), 4);
update CST_YARN_CALCULATION set CYC_DATA_VALUE = vDataValue where CYC_SYS_ID = ...;
```

Tiap TOP dibulatkan ke **4 desimal saat disimpan**, lalu TOP berikutnya memakai nilai yang
**sudah dibulatkan** itu. Jadi pembulatannya **berantai**, bukan sekali di akhir.

Kalau sistem baru menghitung full precision lalu membulatkan di akhir, hasilnya akan berbeda
tipis dan makin melebar di TOP yang dalam. **Ini kandidat kuat penyebab selisih kecil
(-0,016) yang kamu lihat di baris 81** — selain sebab utamanya (rumusnya memang salah).

### 3.3 ⚠️ Error ditelan, operand jadi 0

Setiap resolve dibungkus:

```sql
exception when others then
    insert into CST_YARN_ERR_LOG(...) values(vErrPrs, pUserId, sysdate, pPRS_TYPE);
```

lalu operand tetap dipakai sebagai `nvl(...,0)` → **0**. Perhitungan **tetap jalan** dan
menghasilkan angka yang terlihat wajar.

**Aksi konkret:** sebelum menganggap nilai legacy mana pun sebagai *golden reference*,
cek dulu:

```sql
SELECT CYEL_PROCESS_TIME, CYEL_PRS_TYPE, CYEL_ERR_LOG
  FROM mgtapps.cst_yarn_err_log
 ORDER BY CYEL_PROCESS_TIME DESC
 FETCH FIRST 200 ROWS ONLY;
```

Kalau tabel ini ramai, sebagian angka legacy dihitung dengan operand 0 yang seharusnya bukan 0.

### 3.4 ⚠️ Urutan eksekusi juga data

`order by CYC_PROCESS_SEQ, CYC_LEFT_NO, CYC_TOP_NO`. Kalau `CYC_PROCESS_SEQ` salah set,
sebuah TOP bisa membaca nilai TOP lain yang **belum dihitung ulang** di run itu — dan dapat
nilai run sebelumnya. Legacy tidak punya deteksi siklus.

Ini persis **Gap 1** yang sudah kamu catat di `PHASE_C_BRAINSTORM.md`
(`CPRM_formula_type` dan `CPRM_calc_seq` belum ada di ERD). Sekarang ada konfirmasinya dari
source: tanpa dua kolom itu, engine baru tidak bisa mereplikasi legacy.

### 3.5 ⚠️ Efek samping ke `CST_YARN_LEFT`

```sql
if recYarn.CYC_UPD_YARN_LEFT is not null then
    'update CST_YARN_LEFT set '||recYarn.CYC_UPD_YARN_LEFT||' = '''||vDataValue||''' where ...'
end if;
```

Sebagian TOP **menulis balik ke kolom master produk**. Jadi menghitung ulang bisa mengubah
data master. Perlu diinventarisasi:

```sql
SELECT DISTINCT cyc_top_no, cyc_upd_yarn_left
  FROM mgtapps.cst_yarn_calculation
 WHERE cyc_upd_yarn_left IS NOT NULL
 ORDER BY cyc_top_no;
```

### 3.6 ⚠️ Update hanya kalau nilainya berubah

```sql
if nvl(recYarn.CYC_DATA_VALUE,'NULL') <> nvl(vDataValue,'NULL') then ... update ...
```

Perbandingannya **string**, bukan angka. `'1.5'` vs `'1.50'` dianggap berbeda;
`CYC_MODIFIED_TIMESTAMP` karena itu bukan penanda andal "kapan nilai ini terakhir berubah".

---

## 4. Query untuk mengambil rumus TOP 129 (dan yang lain)

### 4.1 Rumus TOP 129 per produk

```sql
SELECT l.cyl_left_no,
       c.cyc_top_no,
       c.cyc_formula_type,
       c.cyc_process_seq,
       c.cyc_formula_script,
       f.cyfc_seq_no,
       f.cyfc_operator_1,
       f.cyfc_type_1, f.cyfc_top_no_1, f.cyfc_value_1,
       f.cyfc_operator_2,
       f.cyfc_type_2, f.cyfc_top_no_2, f.cyfc_value_2,
       f.cyfc_operator_3,
       f.cyfc_type_3, f.cyfc_top_no_3, f.cyfc_value_3
  FROM mgtapps.cst_yarn_calculation c
  JOIN mgtapps.cst_yarn_left l
    ON l.cyl_sys_id = c.cyc_cyl_sys_id
  LEFT JOIN mgtapps.cst_yarn_formula_calc f
    ON f.cyfc_cyc_sys_id = c.cyc_sys_id
 WHERE c.cyc_top_no IN (95, 127, 128, 129)
   AND c.cyc_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
 ORDER BY l.cyl_left_no, c.cyc_top_no, NVL(f.cyfc_seq_no, 0);
```

### 4.2 ⭐ Query paling penting — apakah rumusnya seragam?

Ini yang menentukan desain sistem baru. Jalankan duluan:

```sql
SELECT c.cyc_formula_type,
       f.cyfc_seq_no,  f.cyfc_operator_1,
       f.cyfc_type_1,  f.cyfc_top_no_1, f.cyfc_value_1,
       f.cyfc_operator_2,
       f.cyfc_type_2,  f.cyfc_top_no_2, f.cyfc_value_2,
       f.cyfc_operator_3,
       f.cyfc_type_3,  f.cyfc_top_no_3, f.cyfc_value_3,
       COUNT(DISTINCT c.cyc_cyl_sys_id) AS jml_produk
  FROM mgtapps.cst_yarn_calculation c
  LEFT JOIN mgtapps.cst_yarn_formula_calc f
    ON f.cyfc_cyc_sys_id = c.cyc_sys_id
 WHERE c.cyc_top_no = 129
   AND c.cyc_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
 GROUP BY c.cyc_formula_type, f.cyfc_seq_no, f.cyfc_operator_1,
          f.cyfc_type_1, f.cyfc_top_no_1, f.cyfc_value_1,
          f.cyfc_operator_2, f.cyfc_type_2, f.cyfc_top_no_2, f.cyfc_value_2,
          f.cyfc_operator_3, f.cyfc_type_3, f.cyfc_top_no_3, f.cyfc_value_3
 ORDER BY jml_produk DESC;
```

**Cara membaca hasilnya:**

- **1 baris** → semua produk pakai rumus sama → aman di-hardcode di Go. 🎉
- **2–5 baris** → ada beberapa varian (mungkin per tipe produk) → jadikan aturan bercabang.
- **puluhan/ratusan baris** → rumus benar-benar per produk → **harus** jadi data di sistem
  baru, tidak bisa di-hardcode. Ini yang mengubah desain (§6).

### 4.3 Menjawab TOP 95 vs TOP 128 (`§5.2` dokumen sebelumnya)

Lihat `cyfc_top_no_1` di hasil 4.1/4.2:

- `cyfc_top_no_1 = 95` → rumus **Ilham benar** (baris 81 × % Add ÷ 100)
- `cyfc_top_no_1 = 128` → **migration kita benar** (`PARAM(124) × PARAM(123) / 100`)

Ekspektasi bentuknya: `TOP_NO_1 = 95 atau 128`, `OPERATOR_2 = '*'`, `TOP_NO_2 = 127`,
`OPERATOR_3 = '/'`, `TYPE_3 = INITIAL_VALUE`, `VALUE_3 = 100`.

Validasi silang dengan data jadi (tanpa perlu paham rumusnya):

```sql
SELECT cyc_cyl_sys_id,
       MAX(CASE WHEN cyc_top_no =  95 THEN to_number(cyc_data_value) END) AS top_95,
       MAX(CASE WHEN cyc_top_no = 127 THEN to_number(cyc_data_value) END) AS pct_add,
       MAX(CASE WHEN cyc_top_no = 128 THEN to_number(cyc_data_value) END) AS top_128,
       MAX(CASE WHEN cyc_top_no = 129 THEN to_number(cyc_data_value) END) AS top_129
  FROM mgtapps.cst_yarn_calculation
 WHERE cyc_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
 GROUP BY cyc_cyl_sys_id
HAVING MAX(CASE WHEN cyc_top_no = 127 THEN to_number(cyc_data_value) END) <> 0
 FETCH FIRST 50 ROWS ONLY;
```

Bandingkan `top_129` terhadap `top_95 * pct_add/100` dan `top_128 * pct_add/100`.

### 4.4 Bonus — seluruh cost sheet sekaligus

Ganti `c.cyc_top_no IN (95,127,128,129)` di query 4.1 dengan satu produk:

```sql
   AND c.cyc_cyl_sys_id = '<cyl_sys_id produk contoh>'
```

Hasilnya: **rumus lengkap seluruh 140 TOP** untuk produk itu, urut `cyc_process_seq`.
Ini spesifikasi engine costing kamu — seluruhnya, bukan cuma baris 81–95.

Lengkapi dengan nama TOP (sekalian menjawab TOP 23 dari §5.1 dokumen sebelumnya):

```sql
SELECT cyt_top_no, cyt_description
  FROM mgtapps.cst_yarn_top
 WHERE cyt_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
 ORDER BY cyt_top_no;
```

---

## 5. Status setelah temuan ini

| Item | Status |
|---|---|
| Baris 81, 82, 83, 84, 95 | ✅ Tuntas — rumus eksak di dokumen sebelumnya |
| `% Add Top 95` (TOP 127) | ✅ Passthrough, data per produk |
| **`Top 95 X % Add` (TOP 129)** | 🔓 **Terbuka** — rumusnya data, ambil via query §4.1/4.2 |
| Nama TOP 23 | 🔓 Query §4.4 |
| Baris 85–92, 94 | 🔓 `CYCRL_SOURCE_QUERY` + TOP 130–140 |
| **Seluruh cost sheet (140 TOP)** | 🎁 **Bisa diambil sekaligus** — §4.4 |

**Tidak ada satu pun yang perlu ditanyakan ke tim Finance/Costing.**

---

## 6. Yang perlu diputuskan tim — bukan keputusan saya atau kamu sendiri

Temuan ini melampaui baris 81–95, jadi saya sampaikan sebagai bahan diskusi, bukan rekomendasi
yang langsung dieksekusi.

**Di legacy, rumus adalah data per produk.** Tiap produk punya baris `CST_YARN_FORMULA_CALC`
sendiri. Secara teori 12.567 produk bisa punya 12.567 rumus berbeda untuk TOP yang sama.
Praktiknya hampir pasti tidak — tapi query §4.2 yang akan membuktikan.

Tiga opsi, tergantung hasil §4.2:

| Opsi | Kapan cocok | Konsekuensi |
|---|---|---|
| **A — Hardcode di Go** | §4.2 hasilnya 1–5 varian | Paling sederhana & cepat. Tapi perubahan rumus butuh deploy — padahal di legacy user bisa ubah sendiri lewat form. **Ada kapabilitas yang hilang.** |
| **B — Tiru penuh (formula sebagai data)** | §4.2 hasilnya banyak varian | Setia ke legacy, user tetap bisa ubah rumus. Tapi harus bangun interpreter + replikasi 6 jebakan di §3. Mahal. |
| **C — Hibrida** | kompromi | Rumus jadi data, tapi **dinormalisasi**: satu definisi per (TOP × tipe produk), bukan per produk. Perlu migrasi + validasi bahwa normalisasi tidak mengubah angka. |

Pertanyaan yang menurut saya harus dijawab dulu sebelum memilih:
**apakah tim costing masih aktif mengubah rumus lewat form Oracle?** Kalau ya, opsi A
menghapus cara kerja mereka tanpa pengganti.

Ini di luar scope baris 81–95, tapi menurut saya lebih penting — dan sebaiknya diangkat ke
diskusi arsitektur Phase C, bukan diputuskan sambil mengerjakan baris 83.

---

## 7. Urutan kerja yang saya sarankan

1. **Query §4.2** — satu query, langsung menentukan opsi A/B/C.
2. **Query §4.4** untuk satu produk contoh — dapat rumus seluruh cost sheet + nama semua TOP
   (sekalian menutup TOP 23).
3. **Cek `CST_YARN_ERR_LOG`** (§3.3) — pastikan angka legacy yang jadi pembanding memang bersih.
4. Implementasi baris 81 → 82 → 83 → 84 → 95 sesuai dokumen sebelumnya, dengan
   **pembulatan 4 desimal berantai** (§3.2).
5. Angkat temuan §6 ke diskusi arsitektur Phase C.

---

## Lampiran — catatan kecil, belum terverifikasi

Di `PKG_YARN_CALCULATION.pkb` ±baris 4626 (rutin **copy TOP 127–128** untuk produk
**VALUATION** baru) ada yang terlihat janggal:

```sql
for recDtLp in 127..128 loop
    SELECT * INTO vDtYarn_Lp FROM cst_yarn_calculation a
     WHERE cyc_left_no = recDtLp                          -- ⬅ 127/128 dipakai sbg LEFT_NO
       AND cyc_top_no  IN (SELECT MIN(cyl_left_no) FROM cst_yarn_left WHERE cyl_type = ...)
       AND cyc_prs_type = vidval;
```

Komentar di atasnya menulis `-- copy Top 127`, dan `recDtLp` dipakai sebagai TOP di
`get_CYT_SYS_ID(recDtLp, vIdVal)` — tapi di WHERE dipakai sebagai `cyc_left_no`, sementara
`cyc_top_no` justru diisi `MIN(cyl_left_no)`. **Terlihat seperti dua kolom tertukar.**

Saya **tidak** mengklaim ini bug — bisa saja ada konvensi yang belum saya pahami. Dan
dampaknya di jalur **VALUATION**, bukan MARKETING, jadi tidak mempengaruhi cost sheet yang
sedang kamu kerjakan. Saya catat supaya tidak hilang; layak dicek kalau nanti valuation
bermasalah di TOP 127/128.
