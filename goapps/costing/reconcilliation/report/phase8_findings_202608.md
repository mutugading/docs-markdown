# Fase 8 — Produk Baru (Definisi): Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Snapshot | 2026-09-09 |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_07_new_products_202608.sql` |
| Script | `build_phase8.py` |
| Output | `out/cmp_07_produk_baru_definisi_202608.csv` (26 produk, definisi lengkap) · `out/cmp_07_new_products_202608.csv` (bucket A+C, 1.961) · `out/cmp_07_legacy_only_products_202608.csv` (bucket B, 1.297) |

---

## 1. Hasil bucketing

| Bucket | Jumlah | Arti |
|---|---:|---|
| `match` | 15.976 | pointer legacy resolve, nama cocok |
| `C_nama_ditulis_ulang` | 1.636 | MB — nama ditulis ulang, **identitas tetap terjamin** |
| `C_konvensi_nama_berbeda` | 83 | yarn — beda konvensi nama spec |
| `A_baru_tanpa_pointer_legacy` | 242 → **26** | lihat koreksi §2 |
| `B_legacy_tanpa_padanan_baru` | **1.297** | MB 254 + yarn 1.043 |

---

## 2. Koreksi penting: bucket A bukan 242, tapi 26

Bucketing pertama saya memakai `cpm_flex_02` sebagai satu-satunya pointer
legacy, dan menghasilkan 242 kandidat produk baru. **Itu salah.**

Untuk produk MB, provenance legacy juga bisa berada di **head**, lewat
`mst_mb_head.mbh_cost_product_id` → `mbh_oracle_sys_id`:

| | Nilai |
|---|---:|
| Produk MB dengan `cpm_flex_02` kosong | 220 |
| …yang head-nya ada | 216 |
| …**yang head-nya PUNYA `mbh_oracle_sys_id`** | **216** |

**216 dari 220 produk MB itu punya provenance legacy — pointernya saja tidak
diteruskan ke `cost_product_master`.** Mereka bukan produk baru.

Jadi bucket A yang benar = **26 produk** (242 − 216). Tanpa koreksi ini saya
akan melaporkan 216 produk lama sebagai "produk baru" — kesalahan yang cukup
besar untuk mengubah kesimpulan.

Temuan turunannya: **linkage head ↔ cost product tidak lengkap.** 216 kasus
pointer tidak diteruskan, plus 33 head (dari 4.226) tanpa `mbh_cost_product_id`
sama sekali.

---

## 3. Bucket A — 26 produk baru, dan tidak satupun berdampak ke 202608

| Tipe | Jumlah | Karakter |
|---|---:|---|
| KYP | 9 | entri **kategori proses**, bukan produk |
| TTY | 6 | spec trial denier 6000 |
| MB | 4 | dibuat `backfill-mb-validate` |
| PTY | 4 | 2 di antaranya artefak fork |
| POY | 2 | keduanya artefak fork |
| TTH | 1 | spec trial denier 6000 |

**Nol dari 26 punya `cost 202608`.** Jadi produk baru **tidak memengaruhi angka
rekonsiliasi periode ini sama sekali** — itu kesimpulan yang melegakan dan perlu
disampaikan eksplisit ke Finance.

### 3.1 Definisi lengkap (format yang diminta prompt)

Contoh tiga baris; lengkapnya 26 baris di
`out/cmp_07_produk_baru_definisi_202608.csv`:

| Field | `CSTKYP2608000004` | `FORK1` | `CSTMB2608000001` |
|---|---|---|---|
| product_sys_id | 40909 | — | — |
| Nama produk | `CABLING` | `POY 250/48/RND/DSD/SIM/NS/1/O` | `MGT ALPAKA CM 2212-CS-D-05065-B` |
| product_type_id | 31 (KYP) | 1 (POY) | **29 (MB)** |
| ERP item code | **belum ter-link** | **belum ter-link** | **belum ter-link** |
| Dibuat kapan | 2026-08-28 09:20 | 2026-06-30 | 2026-08-11 |
| Dibuat oleh | `c416905f-…` | `cfd19f92-…` | `backfill-mb-validate` |
| Punya route? | tidak | tidak | **ya** |
| Punya parameter? | tidak (0) | **ya (119)** | **ya (11)** |
| Cost 202608? | **tidak** | **tidak** | **tidak** |
| Kenapa tidak ada di legacy | entri kategori proses, bukan produk yang di-cost | artefak fork/test | MB dibuat ulang oleh proses backfill |

### 3.2 Empat artefak fork/test di production

| code | tipe | nama | param | route | cost |
|---|---|---|---:|---:|---:|
| `FORK1` | POY | POY 250/48/RND/DSD/SIM/NS/1/O | 119 | 0 | 0 |
| `FORK2` | PTY | PTY 300/144/TBL/BR/NI/SH/N/1/S | 98 | 0 | 0 |
| `FORK3` | POY | POY 500/144/TBL/BR/SIM/NS/1/O | 105 | 0 | 0 |
| `CSTPTY2606002798_F1` | PTY | PTY 1200/576/TBL/BR/LIM/SH/N/1/S | 100 | 0 | 0 |

Nama `FORK1`/`FORK2`/`FORK3` dan suffix `_F1` menandakan **scratch/test data di
database production**. Mereka membawa 98–119 parameter tapi tanpa route dan
tanpa cost. Tidak berbahaya untuk 202608, tapi layak dibersihkan.

### 3.3 Sembilan entri KYP bukan produk

`POY`, `DTY`, `PLY`, `CABLING`, `HANK WINDER`, dst — nama-nama **proses**, bukan
produk. Semuanya 0 route, 0 parameter, 0 cost. `KYP` = "Know Your Product"
(`cpt_type_id` 31). Ini entri referensi/kategori yang kebetulan tinggal di
`cost_product_master`.

### 3.4 Peringatan prompt dipatuhi

Prompt: *"ERP item code TIDAK reliable … Jangan simpulkan 'produk hantu' hanya
karena ERP code kosong."*

**Seluruh 26 produk bucket A ber-ERP kosong**, dan tidak satupun saya simpulkan
sebagai produk hantu. Pemeriksaan route/parameter/cost/`cpm_source`/pembuat
menunjukkan mereka campuran yang sah: entri kategori, spec trial, artefak fork,
dan hasil backfill. ERP kosong dilaporkan sebagai **atribut**, bukan dasar
kesimpulan.

---

## 4. Bucket B — 1.297 legacy tanpa padanan, tapi hanya 194 yang mendesak

### 4.1 MB: 254

| `CMBH_CHECK_STATUS` | Jumlah |
|---|---:|
| `Waiting` | 162 |
| `Boughtout` | 90 |
| *(null)* | 1 |
| **`Current`** | **1** |

252 dari 254 adalah draft atau boughtout. **Hanya 1 berstatus `Current`** — itu
satu-satunya yang benar-benar perlu ditindaklanjuti di sisi MB.

Catatan angka: Fase 3 melaporkan 99 head MB legacy-only (perbandingan
head↔head). Di sini 254 karena pembandingnya pointer di `cost_product_master`.
Selisihnya adalah konsekuensi langsung dari linkage yang tidak diteruskan (§2) —
bukan dua angka yang bertentangan.

### 4.2 Yarn: 1.043

| `CYL_TYPE` | Jumlah |
|---|---:|
| PTY | 418 |
| POY | 209 |
| TTY | 119 |
| ATY | 52 |
| PLY | 50 |
| SUPERBA | 28 |
| PTY BO | 27 |
| MELANGE | 22 |
| ST (TTY) | 19 |
| MEEREBAH | 15 |

Yang menentukan prioritas:

| | Jumlah |
|---|---:|
| Punya baris cost di `_CUR` (legacy memang menghitungnya) | **1.041 dari 1.043** |
| Tidak punya baris `_CUR` | 2 |
| **Masuk scope transaksi ORION 202608** | **194** |

Jadi **194 produk yarn yang legacy hitung costnya DAN bertransaksi di 202608
tidak ada di sistem baru.** Itu subset gap migrasi yang nyata dan mendesak.
Sisanya (849) di-cost legacy tapi tidak bertransaksi di 202608 — prioritas lebih
rendah.

---

## 5. Bucket C — bukan masalah crosswalk

Prompt mendefinisikan bucket C sebagai *"ada di dua-duanya tapi identitas tidak
match → masalah crosswalk"*. Setelah diperiksa, **1.719 kasus yang saya temukan
bukan masalah crosswalk**, dan penting untuk tidak melaporkannya begitu.

### 5.1 Nama produk tidak bisa dipakai sebagai kunci identitas

| | Nilai |
|---|---:|
| Produk | 17.937 |
| Nama distinct | **8.642** |
| Nama dipakai >1 produk | **1.047** |
| **Produk terbanyak yang berbagi satu nama** | **1.016** |

`POY 250/48/RND/DSD/SIM/NS/1/O` dipakai **1.016 produk**;
`PTY 150/48/RND/DSD/NI/DH/N/1/Z` dipakai 602. Nama yarn adalah **deskriptor
spec**, bukan identitas — produk berbeda shade/customer berbagi spec yang sama.

Jadi uji "nama legacy ≠ nama baru" tidak membuktikan apa-apa soal identitas.

### 5.2 Identitas dijamin pointer eksplisit, dan itu sudah dibuktikan

| Sisi | Pointer | Bukti |
|---|---|---|
| MB | `mbh_oracle_sys_id` = `CMBH_SYS_ID` | Fase 3: 4.226 baris, **4.226 distinct, 0 null** |
| Yarn | `cpm_flex_02` + `prs_type` = `CYCC_LEFT_NO` | Fase 5: **1:1 terbukti**, fan-out terjelaskan sebagai dimensi cost type |

### 5.3 1.636 kasus MB = nama ditulis ulang

Pola dari data: nama MB **di-authoring ulang** di sistem baru — perbaikan ejaan,
singkatan, ekspansi, penambahan kode TR:

| legacy | baru |
|---|---|
| `Alloy Grey BR` | `ALLOY GREY TR-712-A` |
| `Light Fann` | `LIGHT FAWN TR-34-A` *(typo diperbaiki)* |
| `Gris Gray` | `GRIS GREY TR-218-A` *(ejaan)* |
| `Buttercup Yellow BR` | `BUTTER YELLOW TR-1027-C` *(disingkat)* |
| `THERMO BK` | `L2 THERMO BLACK, PBT MASTERBATCH` *(diperluas)* |
| `Cobalt Blue BR` | `COBALTBLUETR-1004-B` *(spasi hilang)* |

Tidak ada aturan mekanis tunggal, jadi mapping nama **tidak bisa divalidasi
otomatis**. Tapi identitasnya tidak bergantung nama.

### 5.4 83 kasus yarn = beda konvensi nama

Contoh: `ATY 1400/288/TBL/DBR/N/1/O` (legacy) → `ATY 1400/288/TBL/DBR/NIM/DH/N/1/Z`
(baru). Denier, filament, cross-section, dan shade sama; suffix proses berubah
konvensi.

### 5.5 Uji identitas yang bermakna: kecocokan tipe

Karena nama tidak bisa dipakai, saya uji **tipe produk** — itu uji identitas yang
sah.

| | Nilai |
|---|---:|
| Bisa diuji (yarn) | 13.624 |
| **Tipe cocok** | **11.243 (82,5%)** |
| Tipe berbeda | 2.381 (17,5%) |

Dan 17,5% itu **sistematis, bukan acak** — ia adalah **remapping taksonomi**:

| tipe baru | ← tipe legacy | Jumlah |
|---|---|---:|
| PTY | ← PLY | 494 |
| TTS | ← SUPERBA | 408 |
| TCY | ← TTY | 266 |
| TCS | ← SUPERBA | 207 |
| TCM | ← MEEREBAH | 135 |
| PTY | ← MELANGE | 106 |
| TCY | ← SUPERBA | 70 |
| TTM | ← MEEREBAH | 62 |
| PTY | ← COPS WNDR (PLY) | 60 |
| TCY | ← MEEREBAH | 45 |

Kategori legacy `SUPERBA` / `MEEREBAH` / `PLY` / `MELANGE` / `COPS WNDR` adalah
kategori berbasis **proses/mesin**, yang diklasifikasi ulang ke taksonomi
**produk** di sistem baru. Nama produknya tetap sama di kedua sisi.

Catatan kejujuran: sebagian dari 17,5% itu adalah keterbatasan peta alias saya
sendiri — taksonomi legacy punya kategori tanpa padanan 1:1 di sisi baru, jadi
uji kesetaraan tipe yang ketat **melebih-lebihkan** ketidakcocokan. Angka
sebenarnya lebih baik dari 82,5%.

**Kesimpulan bucket C: tidak ada bukti crosswalk salah.** Yang ada adalah beda
representasi (nama ditulis ulang, taksonomi diremap) di atas identitas yang
pointernya sudah terbukti benar.

---

## 6. Evaluasi klaim prompt: "product name = SSOT untuk resolusi tipe"

| | Nilai |
|---|---:|
| Prefix nama == `cpt_type_code` | **13.636 dari 17.937 (76,0%)** |
| Prefix berbeda | 4.301 |
| …di antaranya **MB** | **4.290** |
| …KYP | 9 · ITY 1 · POY 1 |

**Klaim BERLAKU untuk yarn** — nama yarn selalu diawali token tipe
(`POY …`, `PTY …`, `ATY …`), jadi tipe memang bisa diresolusi dari nama.

**Klaim TIDAK berlaku untuk MB** — nama MB adalah nama shade
(`ALLOY GREY TR-712-A`), tanpa token tipe sama sekali. Jadi 4.290 MB tidak bisa
diresolusi tipenya dari nama.

Satu inkonsistensi nyata ditemukan: ada produk bertipe **POY** yang namanya
`FDY 50/36/RND/SD/SIM/NS/1/O` — nama bilang FDY, tipe bilang POY.

Dan penting: klaim prompt adalah tentang **resolusi tipe**, bukan keunikan. Nama
memang menyandikan spec (karenanya tipe), tapi satu spec dipakai banyak produk
(§5.1) — jadi nama **tidak** bisa dipakai sebagai kunci identitas. Dua hal itu
tidak bertentangan, dan keduanya perlu dipahami bersama.

---

## 7. Ringkasan untuk Executive Summary

| Bucket | Jumlah | Dampak ke angka 202608 |
|---|---:|---|
| **A — produk baru sejati** | **26** | **nol** (tidak satupun punya cost 202608) |
| B — legacy tanpa padanan (mendesak) | **194 yarn + 1 MB `Current`** | gap migrasi nyata |
| B — legacy tanpa padanan (prioritas rendah) | 849 yarn + 253 MB draft/boughtout | rendah |
| C — identitas "tidak match" | 1.719 | **nol** — beda representasi, bukan crosswalk salah |
| Linkage pointer tidak diteruskan | 216 MB | risiko salah baca sebagai produk baru |

**Kesimpulan Fase 8: produk baru bukan sumber selisih.** Yang perlu
ditindaklanjuti adalah **195 produk legacy yang hilang di sistem baru** (194 yarn
in-scope + 1 MB `Current`) dan **216 linkage pointer** yang tidak diteruskan.

---

## 8. Temuan yang perlu keputusan

1. **194 produk yarn legacy hilang di sistem baru** (§4.2) — di-cost legacy dan
   bertransaksi di 202608. Ini gap migrasi paling konkret dari fase ini. Perlu
   keputusan: migrasikan, atau memang di-retire?
2. **1 MB legacy berstatus `Current` tidak ada di sistem baru** (§4.1). Satu
   baris, tapi statusnya produksi.
3. **216 produk MB dengan pointer legacy tidak diteruskan** ke
   `cpm_flex_02` (§2). Perlu di-backfill supaya crosswalk tidak menyesatkan
   di recon berikutnya. Nilainya tersedia di
   `mst_mb_head.mbh_oracle_sys_id` — script bisa saya siapkan kalau diminta.
4. **33 MB head tanpa `mbh_cost_product_id`** (§B9). Perlu dipastikan apakah
   itu head yang memang belum di-generate cost product-nya.
5. **4 artefak fork/test di production** (`FORK1`, `FORK2`, `FORK3`,
   `CSTPTY2606002798_F1`) (§3.2). Layak dibersihkan.
6. **9 entri KYP bukan produk** (§3.3) tinggal di `cost_product_master`. Perlu
   dipastikan apakah itu memang desainnya, atau salah tempat.
7. **1 produk bertipe POY tapi bernama `FDY 50/36/...`** (§6). Inkonsistensi
   tipe vs nama; perlu dikoreksi salah satunya.
8. **Nama produk tidak unik (1.016 produk berbagi satu nama)** (§5.1). Perlu
   dipastikan tidak ada proses yang memakai nama sebagai kunci.

---

## 9. Definition of Done — Fase 8

- [x] Tiga bucket dibentuk sesuai prompt
- [x] Definisi lengkap tiap produk baru: product_sys_id, nama, product_type_id,
      ERP item code, dibuat kapan/oleh siapa, punya route/parameter/cost 202608,
      dan alasan tidak ada di legacy
- [x] Peringatan "jangan simpulkan produk hantu dari ERP kosong" dipatuhi —
      seluruh 26 ber-ERP kosong, nol disimpulkan sebagai hantu
- [x] Klaim "product name = SSOT untuk resolusi tipe" dievaluasi: berlaku untuk
      yarn (76% keseluruhan), tidak berlaku untuk MB
- [x] Bucket A dikoreksi dari 242 ke 26 setelah menemukan jalur provenance kedua
- [x] Bucket C diperiksa dan **tidak** dilaporkan sebagai crosswalk salah,
      dengan bukti mengapa uji nama tidak sah
- [x] Bucket B diprioritaskan pakai kriteria objektif (cost `_CUR` + scope ORION)
- [x] Nol statement write, nol trigger recompute
