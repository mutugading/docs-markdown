# PRD — Calculator LDR / Dosing Masterbatch POY

| | |
|---|---|
| **Dokumen** | Product Requirements Document |
| **Modul** | Calculator LDR (Let Down Ratio) — Mass Coloring POY |
| **Versi** | 1.0 (Draft) |
| **Tanggal** | 21 Agustus 2026 |
| **Basis** | `FORMULA.xlsx` + hasil klarifikasi user |
| **Status** | Untuk direview tim |

---

## 1. Latar Belakang

Pewarnaan benang POY di pabrik dilakukan dengan **mass coloring** — masterbatch pigmen diinjeksikan langsung ke polimer saat spinning, bukan dengan pencelupan. Takaran masterbatch dinyatakan sebagai **LDR (Let Down Ratio)** dalam persen terhadap berat polimer.

Untuk menentukan LDR sebuah warna baru, R&D melakukan **trial**: membuat masterbatch, menjalankan spinning, mengukur hasil warna, sampai ketemu LDR yang tepat. Trial ini mahal dan makan waktu, dan hanya dilakukan untuk **satu jenis produk saja**.

Masalahnya, satu warna biasanya harus tersedia di banyak varian produk — denier berbeda, jumlah filament berbeda, cross section berbeda. Tanpa alat bantu, tiap varian butuh trial sendiri.

Saat ini sudah ada perhitungan manual dalam bentuk Excel (`FORMULA.xlsx`) yang bisa mengkonversi LDR antar produk, tapi masih terbatas: faktor cross section tertanam sebagai angka mati di dalam formula, hanya bisa menangani RND dan TBL, dan tidak ada pencatatan asal-usul angka.

## 2. Tujuan

**Tujuan utama:** dari **satu trial R&D**, menghasilkan estimasi LDR untuk **seluruh produk lain** dalam portfolio, tanpa trial tambahan.

**Tujuan turunan:**

| No | Tujuan | Ukuran keberhasilan |
|---|---|---|
| T-01 | Menghilangkan perhitungan manual di Excel per orang | Semua estimasi LDR keluar dari satu sistem |
| T-02 | Menjamin konsistensi angka | Produk dan warna yang sama selalu menghasilkan LDR yang sama, siapa pun yang menghitung |
| T-03 | Membuat asal-usul angka bisa ditelusuri | Tiap LDR estimasi bisa ditarik balik ke nomor trial R&D-nya |
| T-04 | Menambah cross section baru tanpa ubah program | Cukup insert satu baris di tabel master |
| T-05 | Membedakan angka hasil trial dan angka hasil estimasi | Ada penanda status yang eksplisit di setiap record |

## 3. Ruang Lingkup

### 3.1 Termasuk (In Scope)

- Perhitungan LDR target berdasarkan satu produk sumber yang LDR-nya sudah diketahui dari trial
- Variabel perhitungan: **denier**, **jumlah filament**, **cross section**
- Level produk: **POY saja**
- Cross section: **RND** dan **TBL**, dengan struktur yang siap ditambah
- Perhitungan tunggal (1 sumber → 1 target)
- Perhitungan matriks (1 trial → seluruh produk aktif sekaligus)
- Master data cross section dan produk yang bisa dimaintain user
- Pencatatan trial R&D sebagai sumber acuan

### 3.2 Tidak Termasuk (Out of Scope)

| Item | Alasan |
|---|---|
| Koreksi LDR berdasarkan Strength (%) | Belum digunakan untuk calculator ini |
| Faktor luster / dyeability (DBR, FSD, SIM, NI) | Dikonfirmasi tidak berpengaruh terhadap LDR |
| Level DTY dan konversi otomatis POY↔DTY | Coloring hanya di level POY |
| Validasi rentang dpf / denier / LDR | Belum ada rentang yang ditetapkan |
| Perhitungan pencelupan (dyeing) | Ini mass coloring, bukan dyeing |
| Kalibrasi otomatis faktor dari data historis | Faktor ditetapkan manual oleh user di master |

---

## 4. Konsep Perhitungan

### 4.1 Prinsip

Warna yang tampak pada benang tidak hanya ditentukan oleh jumlah pigmen, tapi juga oleh **seberapa banyak cahaya dihamburkan** oleh permukaan filament.

- **Filament makin halus** (dpf kecil) → jumlah permukaan pemantul per kg makin banyak → warna tampak lebih muda → butuh pigmen lebih banyak.
- **Cross section makin kompleks** (TBL vs RND) → permukaan pemantul lebih banyak → warna tampak lebih muda → butuh pigmen lebih banyak.

Kedua efek ini yang dikompensasi oleh calculator.

### 4.2 Rumus Inti

```
dpf              = denier ÷ filament

LDR_target       = LDR_source × ( dpf_source ÷ dpf_target ) ^ n × ( S_target ÷ S_source )
```

| Simbol | Arti | Nilai |
|---|---|---|
| `dpf` | Denier per Filament | dihitung |
| `n` | Eksponen kehalusan | **0.5** (parameter sistem) |
| `S` | Faktor cross section relatif terhadap RND | dari tabel master |

Eksponen `n = 0.5` adalah bentuk lain dari akar kuadrat, yaitu operasi `√(Den/Fil)` yang sudah dipakai di `FORMULA.xlsx`. Nilai ini **tidak ditampilkan di layar user** dan hanya bisa diubah oleh admin lewat tabel parameter.

### 4.3 Bentuk Alternatif — Index E (rekomendasi implementasi)

Kedua faktor di atas dapat digabung menjadi **satu angka per produk**:

```
E  =  S ÷ dpf ^ n

LDR_target  =  LDR_source × ( E_target ÷ E_source )
```

Keuntungan bentuk ini:

- `E` dapat dihitung sekali dan disimpan sebagai kolom di master produk
- Perhitungan runtime menjadi satu pembagian saja
- Menambah cross section atau produk baru = menambah baris master, tanpa perubahan logika
- Menghindari tabel berpasangan. Jika faktor disimpan sebagai pasangan (`RND→TBL = 0.82`), maka 5 cross section membutuhkan 20 entri dan rawan tidak konsisten. Dengan `E`, 5 cross section cukup 5 entri.

Kedua bentuk menghasilkan angka yang identik secara matematis.

### 4.4 Faktor Cross Section

| Kode | Nama | Faktor S | Asal Angka |
|---|---|---|---|
| RND | Round | 1.000000 | Basis referensi |
| TBL | Trilobal | 1.219512 | Dari `FORMULA.xlsx`: RND→TBL dibagi 0.82, maka S = 1 ÷ 0.82 |

Cross section selain kedua ini **belum ada** di pabrik. Struktur tabel sudah disiapkan agar penambahan di masa depan cukup dilakukan lewat master data.

### 4.5 Pembulatan

- Hasil akhir dibulatkan ke **6 desimal**
- Pembulatan dilakukan **hanya pada output akhir**, tidak pada langkah antara
- Nilai antara disimpan dengan presisi penuh

### 4.6 Catatan tentang Ply

Untuk produk doubling (contoh `160/96/*2`), user memasukkan denier dan filament yang **sudah dikalikan ply**, yaitu `320/192`.

Perlu dicatat: **ply tidak mempengaruhi hasil perhitungan**. dpf dari `160/96` = 1.667 dan dpf dari `320/192` juga = 1.667. Jadi LDR yang dihasilkan identik, dikalikan ataupun tidak. Kolom ply tetap disimpan untuk keperluan penelusuran dan pelaporan.

---

## 5. Aturan Bisnis

| ID | Aturan | Alasan |
|---|---|---|
| **BR-01** | Produk sumber **wajib** berstatus `TRIAL`. Hasil estimasi **tidak boleh** dijadikan sumber perhitungan berikutnya. | Secara matematis hasil berantai memang sama, tetapi status data berubah diam-diam dari "hasil trial" menjadi "estimasi dari estimasi". Penelusuran jadi hilang. |
| **BR-02** | RND adalah cross section basis dengan S = 1.000000 dan tidak boleh diubah atau dinonaktifkan. | Semua faktor lain didefinisikan relatif terhadap RND. |
| **BR-03** | Denier dan filament yang diinput adalah nilai efektif (sudah dikali ply). | Sesuai kesepakatan user. |
| **BR-04** | Satu anchor trial berlaku untuk kombinasi **warna + kode masterbatch**. | Jika masterbatch direformulasi, konsentrasi pigmennya berubah, sehingga LDR lama tidak berlaku lagi. |
| **BR-05** | Revisi masterbatch mewajibkan trial baru. Anchor lama otomatis berstatus `OBSOLETE`. | Mencegah estimasi dihitung dari formulasi yang sudah tidak dipakai. |
| **BR-06** | Setiap record estimasi menyimpan **snapshot** nilai S dan eksponen yang dipakai saat perhitungan. | Jika user mengubah faktor S di master, estimasi lama tetap dapat dijelaskan angkanya. |
| **BR-07** | LDR disimpan dan ditampilkan dalam satuan **persen** (contoh: `0.900000` berarti 0.9%). | Konsisten dengan `FORMULA.xlsx`. |
| **BR-08** | Menambah cross section baru dilakukan **hanya lewat master data**, tanpa perubahan program. | Persyaratan T-04. |
| **BR-09** | Perubahan nilai faktor S harus dicatat siapa dan kapan. | Angka ini mempengaruhi seluruh estimasi yang dihasilkan setelahnya. |

---

## 6. Data Model

### 6.1 Ringkasan Tabel

| Tabel | Jenis | Fungsi |
|---|---|---|
| `MST_PARAMETER` | Master | Parameter sistem (eksponen, pembulatan) |
| `MST_CROSS_SECTION` | Master | Faktor S per cross section |
| `MST_PRODUCT` | Master | Katalog produk POY + index E |
| `MST_COLOR` | Master | Katalog warna |
| `MST_MASTERBATCH` | Master | Katalog masterbatch |
| `TRX_TRIAL` | Transaksi | Anchor — LDR hasil trial R&D |
| `TRX_LDR_ESTIMATE` | Transaksi | Hasil perhitungan calculator |
| `TRX_PRODUCTION_ACTUAL` | Transaksi | LDR aktual produksi (Fase 2) |
| `LOG_MASTER_CHANGE` | Log | Riwayat perubahan faktor |

### 6.2 `MST_PARAMETER`

| Kolom | Tipe | Ket |
|---|---|---|
| `param_code` | VARCHAR(30) | **PK** |
| `param_name` | VARCHAR(100) | |
| `param_value` | VARCHAR(50) | |
| `data_type` | VARCHAR(20) | DECIMAL / INT / VARCHAR |
| `description` | VARCHAR(255) | |
| `updated_by` | VARCHAR(50) | |
| `updated_at` | DATETIME | |

**Data awal:**

| param_code | param_value | description |
|---|---|---|
| `FINENESS_EXPONENT` | 0.5 | Eksponen kehalusan. 0.5 = akar kuadrat, setara `√(Den/Fil)` |
| `ROUNDING_DECIMAL` | 6 | Jumlah desimal hasil akhir |
| `BASE_CROSS_SECTION` | RND | Cross section basis, S = 1 |
| `LDR_UNIT` | PERCENT | Satuan penyimpanan LDR |

### 6.3 `MST_CROSS_SECTION`

| Kolom | Tipe | Ket |
|---|---|---|
| `cs_code` | VARCHAR(10) | **PK** — RND, TBL |
| `cs_name` | VARCHAR(50) | Round, Trilobal |
| `surface_factor` | DECIMAL(12,6) | Faktor S |
| `is_base` | BOOLEAN | TRUE hanya untuk RND |
| `legacy_divisor` | DECIMAL(12,6) | = 1 ÷ S. Referensi ke cara lama (TBL = 0.82) |
| `status` | VARCHAR(20) | `CONFIRMED` / `DRAFT` |
| `source_reference` | VARCHAR(255) | Asal angka |
| `is_active` | BOOLEAN | |
| `created_by` / `created_at` | | |
| `updated_by` / `updated_at` | | |

**Data awal:**

| cs_code | cs_name | surface_factor | is_base | legacy_divisor | status | source_reference |
|---|---|---|---|---|---|---|
| RND | Round | 1.000000 | TRUE | 1.000000 | CONFIRMED | Basis referensi |
| TBL | Trilobal | 1.219512 | FALSE | 0.820000 | CONFIRMED | FORMULA.xlsx — RND→TBL dibagi 0.82 |

### 6.4 `MST_PRODUCT`

| Kolom | Tipe | Ket |
|---|---|---|
| `product_id` | BIGINT | **PK** |
| `product_code` | VARCHAR(50) | **Unique** — contoh `POY-380/108-RND` |
| `product_name` | VARCHAR(150) | Nama penuh |
| `denier` | DECIMAL(10,2) | Nilai efektif (sudah dikali ply) |
| `filament` | INT | Nilai efektif (sudah dikali ply) |
| `ply` | INT | Default 1. Informatif, tidak mempengaruhi dpf |
| `cs_code` | VARCHAR(10) | **FK** → `MST_CROSS_SECTION` |
| `luster_code` | VARCHAR(10) | Informatif saja, tidak masuk perhitungan |
| `dpf` | DECIMAL(12,6) | *Computed* = `denier ÷ filament` |
| `index_e` | DECIMAL(16,10) | *Computed* = `S ÷ dpf ^ n` |
| `is_active` | BOOLEAN | |
| `created_by` / `created_at` | | |
| `updated_by` / `updated_at` | | |

> `dpf` dan `index_e` sebaiknya berupa *generated column* atau di-update lewat trigger, agar tidak pernah tidak sinkron dengan denier/filament/cs_code.
>
> Jika nilai `surface_factor` atau `FINENESS_EXPONENT` diubah, seluruh `index_e` harus dihitung ulang.

### 6.5 `MST_COLOR`

| Kolom | Tipe | Ket |
|---|---|---|
| `color_id` | BIGINT | **PK** |
| `color_code` | VARCHAR(30) | **Unique** |
| `color_name` | VARCHAR(100) | |
| `color_reference` | VARCHAR(50) | Pantone / standar internal |
| `is_active` | BOOLEAN | |

### 6.6 `MST_MASTERBATCH`

| Kolom | Tipe | Ket |
|---|---|---|
| `mb_id` | BIGINT | **PK** |
| `mb_code` | VARCHAR(30) | **Unique** |
| `mb_name` | VARCHAR(100) | |
| `color_id` | BIGINT | **FK** → `MST_COLOR` |
| `mb_version` | VARCHAR(20) | Versi formulasi |
| `pigment_load_pct` | DECIMAL(8,4) | Kadar pigmen |
| `carrier_resin` | VARCHAR(50) | |
| `supplier` | VARCHAR(100) | |
| `status` | VARCHAR(20) | `ACTIVE` / `OBSOLETE` |
| `is_active` | BOOLEAN | |

### 6.7 `TRX_TRIAL` — Anchor

Tabel paling penting. Ini satu-satunya sumber angka LDR yang sah.

| Kolom | Tipe | Ket |
|---|---|---|
| `trial_id` | BIGINT | **PK** |
| `trial_no` | VARCHAR(30) | **Unique** — contoh `RND-2026-014` |
| `trial_date` | DATE | |
| `color_id` | BIGINT | **FK** → `MST_COLOR` |
| `mb_id` | BIGINT | **FK** → `MST_MASTERBATCH` |
| `product_id` | BIGINT | **FK** → `MST_PRODUCT` — produk yang di-trial |
| `ldr_percent` | DECIMAL(12,6) | LDR hasil trial |
| `data_status` | VARCHAR(20) | Selalu `TRIAL` |
| `approval_status` | VARCHAR(20) | `DRAFT` / `APPROVED` / `OBSOLETE` |
| `approved_by` / `approved_at` | | |
| `remarks` | VARCHAR(500) | |
| `created_by` / `created_at` | | |

**Constraint:** hanya boleh ada **satu** record `APPROVED` per kombinasi `color_id` + `mb_id`. Jika ada trial baru yang di-approve, record lama otomatis menjadi `OBSOLETE`.

### 6.8 `TRX_LDR_ESTIMATE` — Output Calculator

| Kolom | Tipe | Ket |
|---|---|---|
| `estimate_id` | BIGINT | **PK** |
| `trial_id` | BIGINT | **FK** → `TRX_TRIAL` — anchor asal |
| `source_product_id` | BIGINT | **FK** → `MST_PRODUCT` |
| `target_product_id` | BIGINT | **FK** → `MST_PRODUCT` |
| `ldr_source` | DECIMAL(12,6) | |
| `ldr_target` | DECIMAL(12,6) | **Hasil** |
| `dpf_source` | DECIMAL(12,6) | Snapshot |
| `dpf_target` | DECIMAL(12,6) | Snapshot |
| `s_source` | DECIMAL(12,6) | Snapshot faktor S |
| `s_target` | DECIMAL(12,6) | Snapshot faktor S |
| `factor_fineness` | DECIMAL(16,10) | `(dpf_src ÷ dpf_tgt) ^ n` |
| `factor_cross_section` | DECIMAL(16,10) | `S_tgt ÷ S_src` |
| `factor_total` | DECIMAL(16,10) | Perkalian keduanya |
| `calc_exponent` | DECIMAL(8,4) | Snapshot `n` |
| `calc_rounding` | INT | Snapshot pembulatan |
| `data_status` | VARCHAR(20) | Selalu `ESTIMATE` |
| `generated_by` / `generated_at` | | |

Kolom snapshot membuat tiap baris **dapat dijelaskan sendiri** — tidak perlu menebak nilai master pada saat perhitungan dilakukan.

### 6.9 `TRX_PRODUCTION_ACTUAL` — Fase 2

| Kolom | Tipe | Ket |
|---|---|---|
| `actual_id` | BIGINT | **PK** |
| `estimate_id` | BIGINT | **FK** → `TRX_LDR_ESTIMATE` |
| `product_id` | BIGINT | **FK** → `MST_PRODUCT` |
| `ldr_estimate` | DECIMAL(12,6) | Nilai estimasi |
| `ldr_actual` | DECIMAL(12,6) | Nilai yang benar-benar dipakai produksi |
| `deviation_pct` | DECIMAL(8,4) | *Computed* |
| `production_date` | DATE | |
| `remarks` | VARCHAR(500) | |

Setiap kali produk hasil estimasi benar-benar diproduksi dan LDR-nya perlu disesuaikan, itu adalah **data kalibrasi gratis** tanpa trial tambahan. Setelah terkumpul cukup banyak, angka 0.82 dan eksponen 0.5 bisa diverifikasi dari data pabrik sendiri.

### 6.10 `LOG_MASTER_CHANGE`

| Kolom | Tipe |
|---|---|
| `log_id` | BIGINT **PK** |
| `table_name` | VARCHAR(50) |
| `record_key` | VARCHAR(50) |
| `field_name` | VARCHAR(50) |
| `old_value` | VARCHAR(100) |
| `new_value` | VARCHAR(100) |
| `changed_by` / `changed_at` | |
| `reason` | VARCHAR(255) |

### 6.11 Relasi

```
MST_COLOR ──< MST_MASTERBATCH ──< TRX_TRIAL >── MST_PRODUCT >── MST_CROSS_SECTION
                                       │
                                       └──< TRX_LDR_ESTIMATE ──< TRX_PRODUCTION_ACTUAL
                                                  │
                                    (source_product_id, target_product_id) → MST_PRODUCT
```

---

## 7. Spesifikasi Fungsi

### 7.1 Fungsi Inti — Perhitungan Tunggal

```
FUNCTION calc_ldr (
    p_ldr_source        DECIMAL(12,6),
    p_denier_source     DECIMAL(10,2),
    p_filament_source   INT,
    p_cs_source         VARCHAR(10),
    p_denier_target     DECIMAL(10,2),
    p_filament_target   INT,
    p_cs_target         VARCHAR(10)
) RETURNS DECIMAL(12,6)
```

**Langkah:**

```
1.  n          := MST_PARAMETER['FINENESS_EXPONENT']
2.  d          := MST_PARAMETER['ROUNDING_DECIMAL']
3.  dpf_src    := p_denier_source / p_filament_source
4.  dpf_tgt    := p_denier_target / p_filament_target
5.  S_src      := MST_CROSS_SECTION[p_cs_source].surface_factor
6.  S_tgt      := MST_CROSS_SECTION[p_cs_target].surface_factor
7.  f_fineness := POWER(dpf_src / dpf_tgt, n)
8.  f_cs       := S_tgt / S_src
9.  f_total    := f_fineness * f_cs
10. RETURN ROUND(p_ldr_source * f_total, d)
```

### 7.2 Fungsi Matriks — Generate Seluruh Portfolio

```
PROCEDURE generate_ldr_matrix (
    p_trial_id      BIGINT,
    p_generated_by  VARCHAR(50)
)
```

**Langkah:**

```
1. Ambil anchor dari TRX_TRIAL. Tolak jika approval_status <> 'APPROVED'
2. Ambil source_product dari anchor
3. Loop seluruh MST_PRODUCT dengan is_active = TRUE
4. Untuk tiap produk target, panggil calc_ldr
5. Insert ke TRX_LDR_ESTIMATE beserta seluruh kolom snapshot
6. Produk target yang sama dengan produk sumber ditandai data_status = 'TRIAL'
```

### 7.3 Error Handling

| Kode | Kondisi | Pesan |
|---|---|---|
| `ERR-01` | `filament <= 0` | Jumlah filament harus lebih besar dari 0 |
| `ERR-02` | `denier <= 0` | Denier harus lebih besar dari 0 |
| `ERR-03` | `ldr_source <= 0` | LDR sumber harus lebih besar dari 0 |
| `ERR-04` | `cs_code` tidak ditemukan | Kode cross section tidak terdaftar di master |
| `ERR-05` | `cs_code` tidak aktif | Cross section sudah tidak aktif |
| `ERR-06` | Anchor belum approved | Trial belum disetujui, tidak dapat dijadikan sumber |
| `ERR-07` | Sumber berstatus `ESTIMATE` | Hasil estimasi tidak dapat dijadikan sumber perhitungan (BR-01) |
| `ERR-08` | Masterbatch berstatus `OBSOLETE` | Masterbatch sudah direvisi, diperlukan trial baru |

---

## 8. Alur Pengguna

### 8.1 Alur Utama

```
R&D menjalankan trial pewarnaan
        │
        ▼
Input hasil trial ke TRX_TRIAL
  (warna, masterbatch, produk, LDR)
        │
        ▼
Approval trial oleh penanggung jawab
        │
        ▼
Generate matriks LDR  ──►  Estimasi LDR untuk SELURUH produk aktif
        │
        ▼
Review dan export (Excel / PDF)
        │
        ▼
Distribusi ke Produksi
        │
        ▼
[Fase 2] Catat LDR aktual produksi sebagai data kalibrasi
```

### 8.2 Contoh Output Matriks

```
Trial No.  : RND-2026-014
Warna      : Navy Blue (NV-0207)
Masterbatch: MB-NV-207 v1.0
Produk trial: POY 380/108 RND — LDR 0.900000%
```

| Produk | Denier | Filament | dpf | CS | LDR (%) | Status |
|---|---|---|---|---|---|---|
| POY-380/108-RND | 380 | 108 | 3.518519 | RND | **0.900000** | TRIAL |
| POY-500/96-RND | 500 | 96 | 5.208333 | RND | 0.739730 | ESTIMATE |
| POY-500/96-TBL | 500 | 96 | 5.208333 | TBL | 0.902109 | ESTIMATE |
| POY-480/216-RND | 480 | 216 | 2.222222 | RND | 1.132475 | ESTIMATE |
| POY-270/72-TBL | 270 | 72 | 3.750000 | TBL | 1.063146 | ESTIMATE |

---

## 9. Kebutuhan Antarmuka

| ID | Layar | Fungsi | Prioritas |
|---|---|---|---|
| **UI-01** | Master Cross Section | CRUD faktor S. RND terkunci. Perubahan tercatat di log | Wajib |
| **UI-02** | Master Produk | CRUD produk POY. dpf dan index E tampil otomatis (read only) | Wajib |
| **UI-03** | Master Warna & Masterbatch | CRUD, termasuk versi masterbatch | Wajib |
| **UI-04** | Input Trial | Entry hasil trial R&D + approval | Wajib |
| **UI-05** | Calculator Tunggal | 1 sumber → 1 target, menampilkan rincian faktor | Wajib |
| **UI-06** | Generate Matriks | Pilih trial → tampilkan seluruh estimasi → export | Wajib |
| **UI-07** | Riwayat Estimasi | Cari estimasi berdasarkan warna / produk / nomor trial | Sebaiknya ada |
| **UI-08** | Input LDR Aktual | Pencatatan hasil produksi untuk kalibrasi | Fase 2 |

**Ketentuan tampilan:**

- LDR ditampilkan **6 desimal** dengan simbol `%`
- Baris berstatus `TRIAL` dibedakan secara visual dari `ESTIMATE`
- Layar calculator menampilkan rincian faktor (kehalusan, cross section, total), bukan hanya hasil akhir — agar user dapat memverifikasi
- Nomor trial asal selalu ditampilkan pada setiap hasil estimasi

---

## 10. Kriteria Penerimaan

Ketiga kasus berikut diambil langsung dari `FORMULA.xlsx` dan **wajib** direproduksi persis.

| No | Sumber | LDR Sumber | Target | Hasil yang Diharapkan | Referensi |
|---|---|---|---|---|---|
| **TC-01** | 380/108 RND | 0.900000 | 500/96 RND | `0.739730` | Sel D9 |
| **TC-02** | 380/108 RND | 0.900000 | 500/96 TBL | `0.902109` | Sel D11 |
| **TC-03** | 380/108 TBL | 0.900000 | 500/96 RND | `0.606578` | Sel D12 |

**Kasus uji tambahan:**

| No | Skenario | Hasil yang Diharapkan |
|---|---|---|
| **TC-04** | Sumber = target (produk identik) | LDR keluar sama persis dengan input |
| **TC-05** | Ply: `160/96` vs `320/192` sebagai target | Kedua hasil identik |
| **TC-06** | Filament = 0 | Error `ERR-01` |
| **TC-07** | Cross section `XYZ` tidak terdaftar | Error `ERR-04` |
| **TC-08** | Sumber berstatus `ESTIMATE` | Error `ERR-07` |
| **TC-09** | Insert cross section baru di master, lalu hitung | Berhasil tanpa perubahan program |
| **TC-10** | Ubah faktor S TBL, lalu buka estimasi lama | Estimasi lama tetap menampilkan angka lamanya (snapshot) |

---

## 11. Keputusan yang Sudah Diambil

Hasil klarifikasi, dicatat agar tidak dibahas ulang.

| No | Topik | Keputusan |
|---|---|---|
| 1 | Cross section yang ada | Hanya RND dan TBL. Struktur disiapkan untuk penambahan |
| 2 | Sumber faktor S | Satu nilai tetap di master, dimaintain user. Tidak diturunkan dari data empiris |
| 3 | Level produk | POY saja. Coloring dilakukan di level POY |
| 4 | Ply | User memasukkan denier dan filament yang sudah dikalikan ply |
| 5 | Luster | Tidak berpengaruh terhadap LDR |
| 6 | Eksponen kehalusan | 0.5, tidak ditampilkan ke user, tersimpan sebagai parameter |
| 7 | Linearitas | Hubungan dianggap linear untuk seluruh shade |
| 8 | Koreksi strength | Tidak termasuk dalam calculator ini |
| 9 | Validasi rentang | Belum ada rentang yang ditetapkan, tidak divalidasi |
| 10 | Pembulatan | 6 desimal |
| 11 | Metode pewarnaan | Mass coloring dengan injeksi masterbatch, bukan pencelupan |

---

## 12. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Hasil estimasi dipakai sebagai sumber perhitungan berikutnya | Penelusuran hilang, angka tidak dapat dipertanggungjawabkan | BR-01 ditegakkan di level aplikasi (ERR-07), bukan sekadar SOP |
| Faktor S diubah tanpa dasar | Seluruh estimasi baru ikut berubah | Wajib isi alasan, tercatat di `LOG_MASTER_CHANGE`, dibatasi role admin |
| Masterbatch direvisi tapi anchor tidak diperbarui | Estimasi dihitung dari formulasi yang sudah tidak dipakai | BR-05 — status `OBSOLETE` otomatis, ditolak oleh ERR-08 |
| Ekstrapolasi terlalu jauh dari titik trial | Akurasi menurun | Produk trial sebaiknya dipilih di **tengah** rentang portfolio, bukan di ujung, agar jarak ekstrapolasi ke dua arah sama-sama pendek |
| Presisi 6 desimal memberi kesan akurasi yang tidak nyata | Operator mengejar angka yang tidak mungkin ditakar | Perlu dipastikan resolusi timbangan dosing di lapangan |

---

## 13. Backlog Fase Berikutnya

| No | Item | Pemicu |
|---|---|---|
| 1 | Modul pencatatan LDR aktual produksi | Setelah calculator berjalan stabil |
| 2 | Kalibrasi faktor S dan eksponen dari data aktual | Setelah terkumpul cukup titik data |
| 3 | Penyesuaian eksponen untuk rentang dpf ekstrem | Bila muncul produk microfiber (dpf < 1) |
| 4 | Faktor luster / kadar TiO₂ | Bila muncul selisih yang tidak dapat dijelaskan oleh denier dan cross section |
| 5 | Koreksi strength | Bila diputuskan masuk lingkup |
| 6 | Validasi rentang dpf dan LDR | Setelah rentang operasional ditetapkan |
| 7 | Dukungan resep multi-masterbatch | Bila satu warna menggunakan lebih dari satu masterbatch |

---

## 14. Pertanyaan Terbuka

| No | Pertanyaan | Untuk |
|---|---|---|
| 1 | Jika satu resep warna menggunakan **lebih dari satu masterbatch**, apakah seluruh komponen dikalikan faktor yang sama, atau hanya komponen berpigmen? Ini menentukan apakah fungsi menerima satu angka LDR atau satu daftar komponen | R&D |
| 2 | Berapa resolusi timbangan dosing di lapangan? Untuk memastikan 6 desimal bukan presisi semu | Produksi |
| 3 | Siapa yang berwenang mengubah faktor S di master? | Manajemen |
| 4 | Apakah master produk sudah tersedia dalam bentuk terstruktur, atau perlu didata ulang? | IT / PPIC |
| 5 | Apakah perlu integrasi ke sistem produksi, atau cukup export manual? | IT |

---

*Dokumen ini disusun berdasarkan `FORMULA.xlsx` dan hasil klarifikasi. Rumus inti merupakan generalisasi murni dari perhitungan yang sudah berjalan — ketiga angka pada file asli direproduksi tanpa selisih.*
