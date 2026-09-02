# PRD — Exim Front Office (BIM · Import BL · Export SI)

| | |
|---|---|
| **Target** | `Modules/Finance` → `Shipment` domain (modul hulu) |
| **Schema** | Write: MGTHRIS (`ship_*`) · Read: MGTDAT (Orion ERP) |
| **Posisi** | Modul **baru** di depan Shipment Control. Bukan migrasi. |
| **Status** | Draft v2.0 — jawaban tim import/export/sales/finance/pajak sudah masuk |
| **Referensi** | Doc set Shipment Control: `PRD.md`, `spec.md`, `schema.md`, `plan.md` |
| **Pertanyaan terbuka** | `open-questions.md` |

---

## 1. Latar

Doc set Shipment Control yang sudah ada memindahkan **provision → settlement** dari legacy
`mgthris` ke `apps-mutugading`. Itu sisi akuntansi: biaya yang sudah diketahui, di-accrue, lalu
dicocokkan dengan tagihan vendor.

Yang tidak ada di mana pun adalah **sisi operasional di depannya**: dokumen apa yang memicu biaya,
berapa perkiraannya, dan siapa yang meminta uang ke Finance. Hari ini pekerjaan itu hidup di Excel
dan email:

- **Import** — budget PIB dihitung di spreadsheet per shipment, kurs diketik manual, tarif BM
  diketik rata satu shipment. Tidak ada baseline, tidak ada perbandingan estimasi vs realisasi.
- **Export** — Shipping Instruction masih manual. Harga yang disepakati dengan pelayaran dan EMKL
  tidak tersimpan sebagai angka, jadi Finance tidak punya patokan saat tagihan datang.
- **BIM** (Booking Instruction of Marketing) — di-entry sales sebagai **teks bebas** di ESC, karena
  SI-nya manual dan komponennya variatif. Incoterm, free time, nominated forwarder, dan pembatasan
  pelayaran mengendap di prosa yang tidak bisa divalidasi atau dihitung.

Modul ini mengisi ketiga celah itu dan **feed into** provision yang sudah dirancang — arah
dependensinya satu arah, jadi Shipment Control tidak perlu menunggu modul ini selesai.

### Kenapa terpisah dari PRD Shipment Control

PRD itu proyek migrasi dengan **feature parity sebagai goal #1** dan cut-over yang tidak boleh
tergeser. Modul ini pengembangan baru dengan angka baru — kalau digabung, parity jadi tidak bisa
diverifikasi apple-to-apple. Yang perlu disiapkan sekarang di sisi Shipment Control hanya **FK
nullable** dan beberapa flag (§11).

---

## 2. Goals

1. **Satu dokumen induk per shipment.** Import di-anchor ke **BL**, export ke **SI**.
2. **BIM jadi data terstruktur.** Field yang dipakai untuk menghitung, memvalidasi, atau memicu
   biaya jadi kolom. Teks bebas hanya untuk instruksi yang memang variatif.
3. **PIB estimation yang bisa ditelusuri.** Formula, kurs, dan tarif tersimpan sebagai data.
   Estimasi dibandingkan dengan PIB aktual, dan selisihnya terpisah antara kurs, tarif, dan qty.
4. **Incoterm dan transport mode sebagai penggerak**, bukan label.
5. **Rantai variance empat titik** di export: rate contract → nilai SI → provisi → tagihan.
6. **Configuration, not code.** Tarif, kurs, transit time, checklist dokumen, dan cost type
   behaviour adalah master data yang bisa diedit user berwenang.

### Non-goals

- Tidak mengubah logika provision/settlement/voucher yang sudah dirancang di Shipment Control.
- Tidak mengubah modul sales/ESC di ERP. BIM baru **menggantikan** BIM lama sebagai sumber yang
  dirujuk aplikasi ini; BIM lama tetap ada di ERP tapi tidak dibaca (§9).
- Tidak menangani penetapan bea cukai (NHI / SPTNP) — backlog.
- Tidak membangun tracking kontainer real-time atau integrasi carrier API.

---

## 3. Users

| Role | Melakukan |
|---|---|
| **Sales / Marketing** | Entry BIM per contract (ESC), maintain template BIM per customer. Rate freight di-pull dari ESC. |
| **Export staff** | Membuat SI dari satu atau beberapa BIM (termasuk memutuskan combine). Mengisi harga aktual freight & EMKL, tanggal kontainer, free time. Confirm, cancel, input additional cost. |
| **Import staff** | Membuat Import File dari BL. Pull item + HS code dari PO. Entry BM% dan hitung PIB estimation. Upload dokumen. Membuat payment request. |
| **Purchase** | Menerima notifikasi ETA−N, mengejar original document dari supplier. |
| **Finance (AP)** | Menerima payment request, membayar, mencatat variance. Menerima provisi dari modul ini. |
| **Master data owner** | Maintain tarif HS (opsional), kurs pajak, transit time, doc type, SKB, master cost + port. |

---

## 4. Modul BIM

### 4.1 Kenapa BIM pindah ke modul ini

BIM lama adalah teks bebas 8–14 baris per blok. Dari sampel data produksi:

- **Incoterm tidak punya rumah.** Tersebar di `Shipping Line Restriction` (`FOB SHIPMENT`),
  `Free Time at Destinations` (`NO - FOB. FORWARDER BEST LINK`), dan `Specific Instruction`
  (`TERM : DAP 2020 INCOTERMS TO DELFINGEN PLANT`).
- **Field terisi hal yang salah.** `Shipment Type` berisi `8 JUNE 2026`, `WK 27`, `LC 90`.
- **Free time bukan angka.** `14`, `21`, `0`, `Regular`, `Normal`, `FOB`, `Min 10 days`.
- **Tanggal salah ketik.** `8 JUN 206` — tahun kurang satu digit.
- **Label sendiri tidak konsisten** — `Specifil Instruction`, `HBL/MBL` tanpa spasi. Label tidak
  bisa dipakai sebagai kunci mapping yang aman.

Semuanya konsekuensi dari tidak adanya kontrol input. Modul baru memberikan kontrol itu.

### 4.2 Aturan pembagian kolom vs flex

> **Kalau nilainya dipakai untuk menghitung, memvalidasi, memicu notifikasi, atau memunculkan baris
> biaya — dia jadi kolom. Sisanya boleh teks bebas.**

Itu sebabnya `free_time` jadi kolom walaupun sekarang isinya `Regular`, dan `net_weight_max` jadi
kolom karena kelebihan berat kena penalti.

### 4.3 Standing terms vs fakta pengiriman

Sales entry BIM **per contract**. Satu contract bisa menghasilkan beberapa SI — ESC-2026000107 satu
blok BIM berisi `6*40 HFCL` dengan `WK 20 - 2*40 ; WK 22 - 2*40 ; WK 23 - 2*40`, yaitu tiga
pengiriman. Jadi field dibelah menurut umurnya:

| Milik BIM (stabil sepanjang contract) | Milik SI (berbeda per pengiriman) |
|---|---|
| incoterm, payment term, LC, currency | container type + qty **aktual** |
| consignee, notify party | ETD/ETA aktual, vessel/voyage |
| nominated forwarder, BL type request | EIN no + date, PEB no + date, EDN |
| carrier rule, partial shipment, fumigation | tanggal stuffing |
| free time, sample approval, net weight max | tanggal kontainer keluar & kembali |
| POD, final destination, transport mode | harga **aktual** freight & EMKL |
| rate freight contract (baseline, dari ESC) | |
| rencana total kontainer & jumlah pengiriman | |

`planned_container_total` dan `planned_shipment_count` jadi kontrol: "contract ini rencananya 6
kontainer, sudah ter-SI 4, sisa 2".

### 4.4 Template BIM per customer

BEKAERT DESLEE punya puluhan ESC setahun dengan BIM nyaris identik. Duplicate per-record melahirkan
drift: puluhan salinan yang perlahan berbeda.

```
ship_bim_template (per customer)  ← standing terms default, di-maintain sales
        │ inherit
ship_bim (per ESC)                ← selalu editable, tercatat mana yang di-override
        │ pull
ship_export_si                    ← snapshot
```

Di layar tombolnya tetap **"salin dari BIM sebelumnya"** — familiar untuk sales. Bedanya di
belakang: yang tersalin template, bukan record lepas. Customer ganti consignee → ubah template
sekali, BIM yang belum ter-SI ikut; yang sudah ter-SI tidak berubah karena SI sudah snapshot.

### 4.5 Rate freight contract sebagai baseline

Rate ini **sudah ada di ESC**, jadi di-pull ke BIM saat BIM dibuat — bukan diketik ulang sales.
Field mana di ESC → `open-questions.md` S3a.

Sumbernya `OT_SO_ITEM.SOI_FLEX_10`, satuannya **per kontainer dalam USD** (incoterm ada di
`OT_SO_HEAD.SOH_FLEX_01`).

Field: `sbm_contract_freight_rate`, `_currency`, `_uom` (default `PER_CONTAINER`), `_quoted_by`,
`_quoted_date`, `_valid_until`.

> Rate di ESC berada di level **item**, sementara BIM di level contract. Kalau satu ESC punya
> beberapa baris item dengan rate berbeda, ambil yang pertama dan tandai untuk direview — jangan
> merata-ratakan. Laporan variance nanti yang akan menunjukkan kalau ada baris yang ternyata berisi
> total shipment, bukan rate per kontainer.

**Rate ini di-freeze dan tidak pernah di-update.** Rate aktual saat booking masuk ke SI. Selisihnya
bukan cost control — itu **margin penjualan yang tergerus**, dan sekarang tidak ada yang
mengukurnya.

Ini juga menjawab isu incoterm yang bolak-balik CIF↔FOB: penyebabnya rate fluktuatif. Dengan rate
contract tercatat, keputusan flip punya jejak.

**Pengecualian:** kalau contract benar-benar dinegosiasi ulang dengan customer, baseline
di-**supersede dengan riwayat** (`ship_bim_freight_history`), bukan ditimpa.

### 4.6 Migrasi BIM lama — upload + mapping berbantu

Contract yang masih berjalan saat go-live di-upload dari BIM lama, dengan mapping otomatis untuk
field yang polanya jelas dan review manual untuk sisanya.

| Bisa dipetakan otomatis | Wajib review sales |
|---|---|
| Consignee, Notify Party (teks langsung) | `Shipment Type` — sering berisi tanggal atau kode LC |
| LC Number (kalau formatnya nomor) | `Free Time` — `Regular` / `Normal` / `FOB` tidak punya angka |
| Partial Shipment, Container Fumigation (YES/NO) | `BIM Date` — format campur, ada salah ketik tahun |
| Container config (`2*40 HFCL`) | `Shipping Line Restriction` — ALLOW vs EXCLUDE tidak eksplisit |
| Sample Approval (YES/NO/APPROVED) | Incoterm — tersebar di tiga field berbeda |

Aturan yang tidak boleh dilanggar: **field yang tidak bisa dipetakan dengan yakin dibiarkan kosong
dan ditandai, bukan ditebak.** Data terstruktur yang isinya salah lebih berbahaya daripada teks
bebas, karena sekarang terlihat resmi.

Teks BIM asli tetap disimpan di `SBM_LEGACY_BODY` dan ditampilkan berdampingan saat review. BIM
historis dari contract yang sudah selesai tidak dimigrasi.

### 4.7 Pengaman supaya flex tidak jadi tempat sampah lagi

Saat simpan, aplikasi memindai `special_instruction` untuk kata kunci yang seharusnya ada di kolom
terstruktur — nama incoterm, "free time", "demurrage", "forwarder", "transhipment", nama pelayaran.
Kalau ketemu, muncul **peringatan lunak**: "sepertinya ini termasuk field X, mau diisi di sana?"
Bukan blokir, cuma nudge.

---

## 5. Incoterm & transport mode sebagai penggerak

### 5.1 Incoterm menentukan cost type

**Untuk biaya yang muncul di SI (export):**

| Incoterm | Freight | Asuransi | EMKL / local | Biaya tujuan |
|---|---|---|---|---|
| `EXW` | — | — | — | — |
| `FOB` / `FCA` | — | — | ada | — |
| `CFR` | ada | — | ada | — |
| `CIF` | ada | ada | ada | — |
| `CPT` / `CIP` | ada | CIP saja | ada | — |
| `DAP` / `DDP` | ada | ada | ada | **ada** |

**Untuk perhitungan Nilai Pabean (import)** — komponen mana yang **ditambahkan** ke nilai invoice.
Diverifikasi terhadap 10 PIB aktual:

| Incoterm | Asuransi ditambahkan | Freight ditambahkan | Contoh PIB |
|---|---|---|---|
| `CIF`, `CIP` | — | — | 294, 304, 307, 310, 311, 292, 259 |
| `CPT`, `CFR` | **ya** | — | 285 (asuransi EUR 5,16, freight 0) |
| `FOB`, `FCA`, `EXW` | **ya** | **ya** | 309, 293 |
| `DAP`, `DDP` | — | — | — |

> `CPT` sudah mencakup freight tapi **belum** asuransi — jadi asuransi tetap ditambahkan. Ini lebih
> halus dari model dua-mode; yang benar adalah tabel per incoterm.

Aturan:

- Incoterm di-snapshot ke SI saat pull dari BIM, **tetap editable**, setiap perubahan tercatat
  dengan alasan.
- Kalau berubah **setelah** ada baris biaya, aplikasi **menahan dan minta review**.
- Indikator kalau incoterm di SI berbeda dari BIM/ESC.
- `FOB` / `EXW` → `nominated_forwarder` **wajib**.
- `DAP` / `DDP` → `final_destination` **wajib**. Biaya sisi tujuan yang muncul belakangan masuk
  lewat **additional cost** (§7.5), bukan baseline.

`CNF` dipetakan ke `CFR` saat pull. `FOBEXWORKS` ditolak — user harus memilih.

### 5.2 Transport mode menentukan bentuk form

| | `SEA_FCL` | `SEA_LCL` | `AIR` | `COURIER` |
|---|---|---|---|---|
| Basis qty | kontainer | CBM | chargeable kg | kg |
| Dokumen | B/L | HBL | AWB | tracking no |
| EMKL | ada | ada | — | — |
| Demurrage / detention | ada | — | — | — |
| Free time | relevan | — | — | — |
| **SI dibuat** | ya | ya | ya | **ya** |
| **Lewat provisi** | ya | ya | ya | **ya** |

`COURIER` (DHL, FedEx) **tetap dibuatkan SI dan tetap lewat provisi** — tidak dikecualikan jadi
direct expense. Yang berbeda hanya struktur biayanya (cenderung satu baris all-in).

Vendor `AIR` berada di **group yang sama** dengan forwarder/EMKL; yang membedakan hanya activity.
Jadi tidak perlu master vendor terpisah — cukup activity code baru dengan UOM yang sesuai.

**Chargeable weight** (mode `AIR`):

```
volumetric_kg  = (L × W × H cm) / AIR_VOLUMETRIC_DIVISOR     -- parameter, default 6000
chargeable_kg  = MAX(gross_weight_kg, volumetric_kg)
```

Dihitung sistem, **jangan diketik manual** — angka ini dasar tagihan dan sering jadi sengketa
dengan forwarder. Presedennya sudah ada di master cost: JASCO LOGISTICS punya `AIRWAYBILL`,
`STORG-HANDL-FEE`, `STORG-CARGO`, `STORG-AIRPORT-CHG` dengan UOM `KGS`. Cukup tambah UOM
`CHARGEABLE_KG`.

---

## 6. Sisi Import

### 6.1 BL sebagai anchor

Transaksi berbasis **BL**. Dikonfirmasi tim import:

- **1 BL = 1 AJU = 1 PIB**
- **1 BL = 1 supplier**, tanpa pengecualian. Untuk LCL, yang di-entry adalah **House BL**, jadi
  aturan ini tetap konsisten.
- 1 BL bisa beberapa PO; 1 PO bisa masuk beberapa BL.

Konfirmasi 1 BL = 1 AJU = 1 PIB memperbaiki cacat struktural di Shipment Control. Anchor import
sekarang adalah **Aju No.**, dan karena Aju milik dokumen PIB, `spec.md` §5.4–5.5 harus punya
mekanisme tambal sulam: provision EMKL menyalin PIB No./SPPB ke provision PIB, lalu port jalan
balik. **Dengan BL sebagai induk, Aju/PIB/SPPB/port semuanya kolom di header BL dan kedua aturan itu
dihapus, berikut test-nya.**

Karena 1 BL = 1 supplier, **supplier ada di header BL** (bukan di level invoice). Invoice mewarisi
supplier dari induknya.

### 6.2 ETA dari ETD + transit time

```
eta_calculated = etd + transit_days
```

`transit_days` di-resolve dari `ship_transit_time` dengan urutan:

1. `(origin port, destination port)`
2. fallback `origin country`

Master mendukung keduanya. Kalau baris yang ada baru per country, sistem tetap jalan; saat butuh
per port, tinggal tambah baris tanpa ubah kode.

Dua aturan:

- `eta_calculated` dan `eta_actual` **terpisah**. Arrival notice dari shipping line yang benar.
  Notifikasi pakai yang terbaik yang tersedia.
- `transit_days` **di-snapshot** ke record BL. Master berubah, ETA transaksi lama tidak bergeser.

### 6.3 Struktur tiga level

Satu BL bisa punya beberapa invoice dari supplier yang sama (contoh Oerlikon: 6 invoice). Freight
dan diskon melekat di invoice; premi asuransi melekat di BL. Jadi item tidak bisa jadi anak langsung
dari BL:

```
ship_import_file (BL)
  supplier, kurs pajak + periode, premi asuransi + currency, SKB ref,
  aju/pib/sppb, port, ETD/ETA, gross weight, incoterm, bl_type
  │
  ├── ship_import_invoice   invoice no + date, currency,
  │     │                   freight_total, discount_total
  │     └── ship_import_item  po_no + po_line, item_code, item_name_po,
  │                           item_name_doc, hs_code, coo, bm_pct,
  │                           shipped_qty, rate, value_fob, alokasi, CIF, duty
  ├── ship_import_container
  └── ship_import_doc         checklist + file upload
```

### 6.4 Item: tiga kontrol yang wajib

**Nama barang dua versi.** `item_name_po` dari PO, `item_name_doc` sesuai deskripsi invoice dan
packing list. Yang kedua yang jadi acuan bea cukai.

**Sisa qty per PO line.** Saat pull PO ke BL baru, yang tampil harus **sisa**, bukan qty PO penuh:

```
sisa = po_qty − Σ shipped_qty di semua BL untuk PO line itu
```

Tanpa ini, satu PO yang dikirim dua kali menghasilkan estimasi BM dua kali penuh. Legacy sudah punya
konsep ini di PO picker — tinggal dipindahkan ke level item.

**HS code dari PO sebagai referensi.** HS code di PO sudah mandatory tapi **formatnya tidak
seragam**, jadi dia di-pull sebagai informasi, bukan sebagai kunci lookup yang dipercaya. Saat
normalisasi berhasil (hilangkan titik/spasi, ambil 8 digit) dan HS-nya ada di `ship_hs_tariff`,
BM% terisi otomatis sebagai default. Kalau tidak, user mengetik BM% manual. Lihat §6.6.

### 6.5 PIB Estimation

Formula diambil dari praktik aktual (`CARA_PERHITUNGAN_UNTUK_BUDGET_PIB.xlsx`), diverifikasi
angkanya.

**Alokasi selalu proporsional by value.** Dikonfirmasi tim import — tidak ada basis lain.

**Komponen yang ditambahkan ditentukan incoterm** (§5.1). Urutan hitung per item:

**Mode A — nilai PO sudah CIF** (incoterm CIF/CIP/CFR/DAP/DDP)

Kolom freight dan asuransi **tetap editable**, tapi perannya **rincian, bukan tambahan**:

```
cif_idr     = nilai_po × kurs_pajak            -- CIF = nilai PO, apa pun isian freight
fob_display = nilai_po − freight − asuransi    -- untuk isian form PIB saja
bm          = cif_idr × bm_pct
nilai_impor = cif_idr + bm
ppn         = nilai_impor × PPN_RATE_EFFECTIVE   -- 11%, bukan 12% (§6.9a)
pph         = nilai_impor × pph_pct            -- 0 kalau SKB berlaku
budget      = bm + ppn + ppnbm + pph
```

**Mode B — nilai PO masih FOB/EXW.** Freight dan asuransi **menambah**:

```
-- per invoice, dalam mata uang invoice
diskon_i    = nilai_i / Σ nilai_invoice × diskon_total_invoice
freight_i   = nilai_i / Σ nilai_invoice × freight_total_invoice
nilai_idr_i = (nilai_i − diskon_i + freight_i) × kurs_pajak(mata uang invoice)

-- per BL, dalam IDR
premi_idr   = premi × kurs_pajak(mata uang premi)
asuransi_i  = nilai_idr_i / Σ nilai_idr_semua_item × premi_idr

-- per item
cif_idr_i   = nilai_idr_i + asuransi_i
bm_i        = cif_idr_i × bm_pct_i
… lanjut seperti Mode A, dijumlahkan per invoice lalu per BL
```

> **Kalau kedua mode disatukan, shipment CIF yang diisi freight akan menghasilkan BM over.** Mode
> dipilih otomatis dari incoterm dan tidak boleh bisa diakali.

Dua hal halus yang wajib ditiru persis:

- **Diskon dan freight dialokasi per invoice; premi asuransi dialokasi lintas seluruh BL.** Polis
  terbit satu untuk satu pengiriman; freight dan diskon melekat ke invoice masing-masing.
- **Alokasi asuransi dilakukan di IDR**, karena mata uang premi bisa berbeda dari mata uang invoice.
  Bonus: satu BL boleh berisi invoice dengan mata uang berbeda-beda.

**Verifikasi formula** terhadap contoh Oerlikon: CIF total EUR 5.290,26 × kurs 20.710,98 → BM 5% =
5.478.323, PPN 11% dari (CIF+BM) = 12.654.927, total **18.133.251**. Cocok dengan sel `I43`.

### 6.6 Resolusi BM% per item

Satu BL bisa berisi barang dengan tarif BM berbeda, jadi `bm_pct` ada di level **item** dan wajib
terisi sebelum estimasi dihitung.

**Fasilitas tidak mengubah BM%** — dikonfirmasi tim pajak, dan cocok dengan PIB. Tapi PIB
menunjukkan hal lain yang penting: **`BM KITE` adalah baris pungutan terpisah dari `BM`.**

Di PIB 000309 dan 000310, baris `BM` = 0 dan `BM KITE` yang terisi (4% dan 5%), keduanya di kolom
**Dibayar** — jadi kasnya tetap keluar saat impor dan **tetap masuk budget PIB**.

**Perlakuan akuntansinya sama dengan BM biasa** (dikonfirmasi Finance): tidak dicatat sebagai
piutang dan tidak diposting terpisah. Kalau nanti muncul kewajiban BM KITE dari hasil audit, itu
diposting sebagai biaya pada saat itu — peristiwa terpisah yang di luar scope modul ini.

Jadi `SIT_DUTY_TYPE` = `BM` / `BM_KITE` **tidak menggerakkan akun** di v1. Kolomnya tetap ada karena
dua alasan murah: estimasi harus sebanding baris-per-baris dengan PIB aktual, dan Finance dapat
laporan total BM KITE yang dibayar per periode — angka yang persis dibutuhkan saat audit KITE
datang.

Urutan resolusi **tarif** tinggal dua langkah:

| Urutan | Sumber | `bm_source` |
|---|---|---|
| 1 | **Tarif preferensi** — kalau skema dipilih *dan* COO/DAB ada dan valid | `PREFERENCE` |
| 2 | **MFN** dari `ship_hs_tariff`, kalau HS-nya ada di master | `MFN` |
| 3 | **Manual**, wajib isi alasan | `MANUAL` |

**Empat skema preferensi aktif dipakai**, bukan hanya Form E:

| Skema | Asal | Dokumen | Contoh PIB |
|---|---|---|---|
| ACFTA (ASEAN-China) | China | ECO / Form E | 294, 307 |
| AIFTA (ASEAN-India) | India | CO | 309, 311 |
| ATIGA (ASEAN) | Vietnam | ECO Form D | 292 |
| RCEP | China | DAB (Deklarasi Asal Barang) | 304 |

> PIB 000304 dan 000307 sama-sama dari China tapi pakai skema berbeda (RCEP vs ACFTA). Jadi
> **skema dipilih user, bukan disimpulkan dari negara asal.** `ship_hs_preference` di-key pada
> (HS code, negara asal, skema).

**Scope v1: empat skema ini saja.** Skema lain (IJEPA, IK-CEPA, IA-CEPA) ditambahkan sebagai baris
master saat dibutuhkan, tanpa perubahan kode.

**Master tarif adalah akselerator, bukan syarat.** `ship_hs_tariff` diisi manual per HS yang
benar-benar dipakai (bukan seluruh BTKI), sambil jalan. Hari pertama boleh kosong — semua BM%
diketik manual dan modul tetap berfungsi. Semakin banyak HS terisi, semakin sedikit yang diketik.

Item tanpa `bm_pct` adalah **blocker sebelum hitung**, bukan diam-diam dihitung nol.

### 6.7 Eksposur preferensi — dua angka di sisi budget

BM 0% bergantung pada Form E yang terbit dan valid. Kalau Form E tidak datang atau ditolak, BM
melompat ke MFN.

Untuk BL Zhejiang Hengyi (nilai impor 1,4 miliar), selisih 0% vs MFN 5% adalah **±70 juta bea masuk
plus PPN di atasnya — total eksposur ±78 juta yang tidak dianggarkan sama sekali.**

Aplikasi menampilkan **dua angka**: nilai dengan preferensi, dan nilai kalau preferensi gagal.

**Batasannya:** angka kedua hanya bisa dihitung kalau tarif MFN diketahui. Untuk item dengan
`bm_source = MANUAL` dan HS-nya belum ada di master, kolom `bm_pct_fallback` **opsional** — kalau
diisi user, eksposur ditampilkan; kalau kosong, eksposur ditandai "tidak lengkap" untuk BL itu.
Jangan menampilkan angka eksposur parsial seolah-olah utuh.

### 6.8 Kurs pajak (NDPBM)

NDPBM memakai **kurs pajak KMK, berlaku mingguan**, dan kurs yang sama dipakai untuk BM, PPN, dan
PPh 22 impor. **Satu master cukup** (dikonfirmasi).

**Ini bukan `fm_exchange_rate` harian dari ERP.** Kalau aplikasi menarik kurs ERP, estimasi akan
selalu meleset dan tidak akan pernah cocok dengan PIB.

Tiga aturan:

- Kurs **di-snapshot** ke record BL bersama periode KMK-nya.
- **Kurs mana yang berlaku ditentukan oleh tanggal pendaftaran PIB** — bukan tanggal BL, bukan ETA.
  Estimasi dibuat berminggu-minggu sebelumnya, jadi kurs saat estimasi hampir pasti bukan kurs
  final. Sediakan tombol **hitung ulang** dengan riwayat — jangan menimpa angka lama.
- Laporan variance **memisahkan selisih kurs dari selisih tarif dan kuantitas**. Tanpa itu, Finance
  tidak bisa membedakan estimasi yang keliru dari kurs yang bergerak.

### 6.9 SKB, PPh, dan pembulatan

**PPh 22 = 0 selama SKB masih berlaku.** Di luar itu memakai parameter `PPH22_PCT_*`.

**SKB** butuh record sendiri (`ship_skb`): nomor, tanggal, masa berlaku, cakupan. BL menunjuk ke SKB
yang dipakai. Aplikasi **menolak** menandai PPh bebas kalau SKB sudah lewat masa berlaku di tanggal
perkiraan PIB, dan **memperingatkan** kalau habis dalam `SKB_EXPIRY_WARNING_DAYS`. SKB masuk daftar
dokumen yang di-upload.

Kalau SKB lewat tanpa terdeteksi, budget kurang sebesar tarif PPh × Nilai Impor — untuk BL 1,4
miliar dengan tarif 2,5% itu ±35 juta.

**SKB diperbarui setiap kuartal.** Dari sampel PIB: `KET-00014/PPUT-CT/KPP.3213/2026` (04-05-2026)
dipakai di PIB Juli, lalu `KET-00025/PPUT-CT/KPP.3213/2026` (04-08-2026) di PIB Agustus — berjarak
tepat tiga bulan. Jadi validasi masa berlaku bukan pengaman teoretis; dia akan aktif empat kali
setahun.

**Pembulatan pungutan — tiga aturan berbeda per jenis.** Diverifikasi terhadap 10 PIB aktual
(28 dari 30 angka cocok persis sampai rupiah; 2 meleset ≤4 rupiah karena nilai pabean per item di
PDF dicetak 3 desimal).

| Pungutan | Aturan | Terverifikasi |
|---|---|---|
| **BM / BM KITE** | `Nilai Impor × tarif`, lalu **dibulatkan ke ATAS ke ribuan penuh** | 4/4 PIB ber-BM |
| **PPN** | `Nilai Impor penuh × 11%`, lalu **dipotong (truncate) ke rupiah** | 8/10 persis |
| **PPh 22** | `Nilai Impor dibulatkan ke BAWAH ke ribuan`, lalu `× tarif` | 10/10 persis |

Dua hal yang mudah terlewat:

- **PPN memakai BM yang belum dibulatkan**, sementara yang dibayar ke kas negara adalah BM yang
  sudah dibulatkan ke atas. Jadi ada dua nilai BM dalam satu perhitungan.
- Perhitungan dilakukan **per baris item lalu dijumlahkan**, bukan sekali di total.

Pseudocode:

```
per item:
  cif_idr = nilai_pabean_item × kurs_pajak
  bm_raw  = cif_idr × bm_pct
  ni      = cif_idr + bm_raw                      -- pakai bm_raw, bukan yang dibulatkan
  ppn    += TRUNC( ni × PPN_RATE_EFFECTIVE )
  pph    += FLOOR_1000( ni ) × pph_pct

per BL:
  bm = CEIL_1000( Σ bm_raw )
  total = bm + ppn + ppnbm + pph
```

**Pembulatan mata uang umum** (di luar pungutan): IDR → satuan tanpa desimal, non-IDR → 2 desimal.

### 6.10 Checklist dokumen dan notifikasi

Daftar dokumen dikonfirmasi sesuai. Kewajibannya **kondisional**, bukan fixed:

| Dokumen | Kapan wajib |
|---|---|
| Bill of Lading (original / telex release) | Selalu |
| Commercial Invoice | Selalu |
| Packing List | Selalu |
| Purchase Order / Sales Contract | Selalu |
| Certificate of Origin — Form E / D / AK / AI / IJEPA | Kalau klaim tarif preferensi; tergantung origin country |
| Insurance policy / certificate | Kalau incoterm CIF atau CIP |
| Certificate of Analysis (COA) | Bahan kimia / raw material |
| MSDS | Bahan berbahaya |
| Persetujuan Impor / izin Lartas | Tergantung HS code |
| Laporan Surveyor | Tergantung HS code |
| Surat Kuasa PPJK | Kalau pakai broker |
| Phytosanitary / fumigation certificate | Kemasan kayu atau barang organik |
| SKB PPh 22 | Kalau klaim pembebasan PPh |
| Delivery Order | Terbit **setelah** bayar DO — hasil, bukan syarat |

**Kode dokumen memakai kode resmi bea cukai**, bukan kode buatan sendiri — supaya checklist
sebanding langsung dengan lampiran PIB:

| Kode | Dokumen | | Kode | Dokumen |
|---|---|---|---|---|
| `380` | Invoice | | `740` | AWB |
| `457` | Surat Keterangan Bebas (SKB) PPh | | `741` | Master AWB |
| `465` | L/C | | `860` | Electronic Certificate of Origin (ECO) |
| `705` | B/L | | `861` | Certificate of Origin (CO) |
| `959` | Surat Persetujuan Impor Dep.Dag | | `864` | Deklarasi Asal Barang (DAB) |
| `998` | SKEP Fasilitas Kemudahan Ekspor (KITE) | | | |

Master `ship_doc_type` menyimpan kondisinya (`applies_origin_country`, `applies_hs_prefix`,
`applies_incoterm`, `applies_facility`, `applies_transport_mode`; null = semua). Saat BL dibuat,
aplikasi generate checklist dengan mengevaluasi master terhadap data BL. `doc_complete_flag` = semua
baris mandatory sudah ada file-nya. FTA atau regulasi baru = tambah baris master, bukan deploy.

**Notifikasi:**

| # | Pemicu | Ke | Catatan |
|---|---|---|---|
| 1 | ETA − N hari | Purchase | **N per origin country** (`STT_NOTIFY_LEAD_DAYS`), fallback parameter global. China transit pendek, Eropa panjang. |
| 2 | Checklist berubah | Import | Sudah/belum komplit |
| 3 | ETA − 1 hari, dokumen belum komplit | Import + atasan | **Eskalasi**, sertakan nilai eksposur §6.7 |
| 4 | Estimasi vs aktual PIB selisih > threshold | Finance | Default **5%**, editable |

Notifikasi 3 penting karena dokumen telat adalah penyebab langsung demurrage dan storage — biaya
yang paling mahal dan paling tidak kelihatan sampai tagihan datang.

### 6.11 Payment request

Budget adalah **informasi ke Finance, tanpa approval dan tanpa reject**. Status:
`DRAFT → SENT → PAID`. Tanpa jurnal, tanpa voucher.

| Cost type | Jalur |
|---|---|
| `DO` | Payment request, nominal **entry manual** |
| `PIB` | Payment request, nominal dari PIB estimation |
| `EMKL` | **Tidak lewat payment request.** Langsung komitmen → provisi (§6.12) |

**Bantuan untuk DO.** Tim import sekarang melihat transaksi terakhir untuk menentukan tarif DO. Jadi
saat membuat payment request DO, aplikasi menampilkan **3 pembayaran DO terakhir untuk shipping line
itu** (tanggal, nominal, BL). Bukan master, cuma riwayat — tapi menghilangkan pekerjaan mencari
manual. Kalau setelah beberapa bulan polanya stabil, master DO bisa diisi dari data yang sama.

Finance bayar dengan **catatan variance**: tiga angka berdampingan (budget, tagihan, selisih) plus
kolom alasan. Selisih disimpan sebagai data, bukan cuma ditampilkan.

### 6.12 EMKL dan storage

**EMKL — komitmen langsung jadi provisi.**

1. Komitmen EMKL → generate baris biaya dari master cost (breakdown per activity)
2. Provisi diposting per **BL**, **bertanggal komitmen/booking** — biasanya sama dengan tanggal
   pembayaran PIB
3. Tagihan datang → dibandingkan per activity dengan provisi BL yang sama

| Kasus | Perlakuan |
|---|---|
| Tagihan = provisi | Reverse provisi penuh |
| Tagihan > provisi | Reverse provisi, selisih ke expense |
| Tagihan < provisi | Reverse provisi, selisih kredit ke expense |
| Diprovisi tapi **tidak ditagih** | Provisi di-**reverse** supaya tidak ada yang ketinggalan |
| Ditagih tapi **tidak diprovisi** | **Direct expense** tanpa referensi provisi (`stl_source = DIRECT`) |

Bonus dari BL sebagai anchor: tarif EMKL di-price per port, dan port sekarang kolom di header BL —
provisi EMKL tinggal baca dari induknya.

**Storage / penumpukan import.** Dicatat **setelah tagihan diterima**, biasanya datang bersama
invoice EMKL. Tarifnya bertingkat per hari. Karena baru diketahui saat tagihan, storage **tidak
diprovisi di awal** — dia masuk sebagai baris tagihan tanpa provisi (`DIRECT`) dengan referensi BL
yang sama. Konsekuensinya: **tanggal masuk/keluar penumpukan tidak perlu diinput di modul ini** —
jumlah harinya datang dari tagihan. Master cost `DAYS` tetap dipakai untuk memvalidasi nominal
tagihan terhadap tarif bertingkat yang berlaku.

---

## 7. Sisi Export

### 7.1 Kenapa SI berbeda dari BL

**BL itu dokumen fakta; SI itu dokumen komitmen.** BL memberi tahu barang apa yang datang; biayanya
menyusul. SI sebaliknya — dia *berisi harga yang disepakati*, dan harga itulah patokan Finance saat
tagihan datang. Jadi SI membawa baris biaya sejak lahir.

Empat titik waktu yang tidak bisa disatukan:

| Kapan | Apa | Sumber angka |
|---|---|---|
| Saat contract | BIM dibuat, rate freight disepakati | ESC |
| Sebelum kapal | SI dibuat, harga booking disepakati | negosiasi booking, default master cost |
| Setelah kirim, setelah invoice terbit | provisi diposting | SI + EIN |
| Bulan berikutnya | tagihan datang, dibandingkan, diposting | invoice vendor |

### 7.2 Rantai variance empat titik

```
rate contract (BIM) → harga SI → provisi → tagihan
       └── erosi margin ──┘  └─ selisih 1 ─┘ └─ selisih 2 ─┘
```

- **Erosi margin** — rate pasar bergerak antara deal contract dan booking. Angka penjualan, bukan
  cost control.
- **Selisih 1** — apakah yang kita provisi sesuai kesepakatan booking.
- **Selisih 2** — apakah vendor menagih sesuai kesepakatan.

Legacy hanya punya selisih 2.

**SI menarik default dari master cost, tapi harga yang tersimpan di SI yang menang** — dan itu, bukan
master, yang jadi baseline provisi. Efek sampingnya bagus: kalau harga SI sering menyimpang dari
master, itu sinyal rate card perlu diperbarui, dan sekarang terukur.

### 7.3 Status SI

Kata **"draft" dan "final" dibuang**, memakai `CONFIRMED` untuk membedakan dari mekanisme lama.

```
BOOKED ──────→ CONFIRMED ──────→ SHIPPED ──────→ CLOSED
(SI dibuat)    (DO terbit)      (kapal jalan)   (tagihan lunas)
   │               │
   └───────────────┴──────→ CANCELLED
```

- `CANCELLED` **hanya** dari `BOOKED` atau `CONFIRMED`. Setelah kapal berangkat dan B/L terbit,
  pembatalan tidak mungkin secara fisik.
- Cancel **tidak butuh permission terpisah** — user yang confirm juga bisa cancel. Diatur lewat hak
  akses biasa.
- SI punya **enum sendiri**, jangan dipaksa masuk enum provision/bill.
- **`CLOSED` dihitung, bukan diisi.** Baris biaya baru bisa muncul kapan saja, jadi status manual
  akan selalu ketinggalan:

```
cost_complete = tidak ada baris berstatus draft
            AND tidak ada baris approved yang belum diprovisi
            AND tidak ada baris provisi yang belum di-settle
```

Muncul demurrage baru → flag otomatis terbuka lagi. Tidak perlu tombol reopen.

**Roll-over kapal** (mundur ke vessel berikutnya, bukan cancel): **SI diedit**, tidak dibuat baru.
`SES_VESSEL` / `SES_ETD` berubah dan tercatat di activity log. Tidak ada status tambahan.

### 7.4 Cancel adalah layar, bukan tombol

SI yang cancel bisa sudah punya biaya EMKL yang benar-benar terjadi. Saat cancel, user menentukan:

1. Baris biaya mana yang **tetap terutang**
2. Baris mana yang **hangus** (`status = void`)
3. **Cancellation fee** kalau ada — entry manual sebagai baris `is_additional = 1` dengan cost type
   `CANCELLATION_FEE`, karena ada SI yang cancel tanpa fee sama sekali

SI yang cancel tetap menghasilkan provisi, isinya sebagian.

### 7.5 Satu tabel biaya, status per baris

Semua biaya SI hidup di satu tabel: freight, EMKL, cancellation fee, demurrage, detention, early
pickup, biaya tujuan DAP. Yang membedakan bukan tabelnya, tapi kolom di barisnya.

**Approval per baris ≠ dokumen terpisah.** Kalau additional cost jadi dokumen sendiri, biayanya
terpisah dari biaya lain dan monitoring per SI jadi pecah.

```
ship_export_si_cost
  si_sys_id, vendor, cost_type, activity
  qty, rate, currency, ppn/pph, akun
  is_additional      0 = baseline (freight, EMKL) · 1 = tentative
  status             draft → approved → void
  approved_by, approved_at, approval_note
  approval_ref, approval_offline_by     ← approver sebenarnya di luar aplikasi
  provision_sys_id   null = belum diprovisi
```

- Baris **baseline** di-approve berbarengan saat SI jadi `CONFIRMED`.
- Baris **additional** di-approve satu per satu, kapan saja, termasuk saat SI sudah `SHIPPED`
  berbulan-bulan.
- Approver sebenarnya ada di level atas dan tidak pakai aplikasi — ada kolom untuk nama approver
  offline dan nomor referensi persetujuannya.

**`provision_sys_id` menyelesaikan masalah periode.** Demurrage yang muncul tiga bulan setelah kirim
harus masuk periode saat biaya itu terjadi, bukan periode EIN. Dengan kolom ini, provisi jadi
**peristiwa posting**, bukan wadah:

```
provisi = ambil semua baris dengan status = approved
                          dan provision_sys_id IS NULL
```

Jalankan saat EIN terbit → terambil baris baseline, diposting di tanggal EIN. Jalankan lagi tiga
bulan kemudian setelah demurrage di-approve → terambil baris itu saja, di periodenya sendiri.

Polanya identik dengan `stc_spc_sys_id` di Shipment Control — sekarang berlaku satu tingkat lebih
awal.

### 7.6 Master cost menampung additional cost

Demurrage dan detention punya tarif bertingkat per hari dari pelayaran — bentuknya `TIER` dengan UOM
`DAYS`, dan presedennya sudah ada (`STORG-REG-MASA`). Tidak perlu mekanisme tarif baru.

Yang ditambah di master cost type cuma dua flag:

| Flag | Freight, EMKL | Demurrage, detention, early pickup, cancellation fee, biaya tujuan |
|---|---|---|
| `is_additional` | 0 | 1 |
| `requires_line_approval` | 0 | 1 |

**Data baru yang harus diinput tim export** (dikonfirmasi: datanya ada): free time yang disepakati,
tanggal kontainer keluar depo, tanggal kembali. Tanpa itu qty demurrage hanya bisa diketik manual
dan tidak bisa diverifikasi terhadap tagihan pelayaran.

Cancellation fee **selalu entry manual** — tidak masuk master cost.

### 7.7 Kardinalitas dan multi-BIM

Dikonfirmasi: **1 SI = 1 EIN = 1 PEB = 1 vessel/voyage = 1 POD.** Tapi:

- **1 EIN bisa beberapa ESC** (`COMBINE WITH ESC-…`)
- **1 EIN bisa beberapa EDN** (delivery note)

Karena BIM per ESC, SI menarik dari **beberapa BIM**. **Tim export yang memutuskan BIM mana saja
di-combine**, saat membuat SI. Aturan merge kalau isinya berbeda:

| Field | Kalau berbeda antar BIM |
|---|---|
| Incoterm, POD, transport mode, currency | **Tolak** — tidak boleh digabung dalam satu SI |
| Consignee, notify party | **Tolak** — satu BL satu consignee |
| Payment term / LC | **Peringatan** — dua skema pembayaran dalam satu invoice perlu konfirmasi Finance |
| Carrier rule | Ambil **irisan** dari yang diizinkan |
| Free time | Ambil yang **paling kecil** |
| Net weight max | Ambil yang **paling kecil** |
| Fumigation | Kalau salah satu wajib → **wajib** |
| Partial shipment | Ambil yang **paling ketat** |
| Special instruction | Gabung, tandai sumber ESC-nya |

Prinsipnya: yang menentukan **identitas dokumen** harus sama; yang berupa **batasan** diambil paling
ketat.

### 7.8 Provisi export

- Diposting **per SI**.
- **Tanggal provisi = tanggal invoice penjualan (EIN)**, biasanya backdated. EIN no + date jadi field
  di SI, diisi setelah invoice terbit, dan aplikasi **memblokir posting provisi selama EIN masih
  kosong**.
- **Backdated ke periode yang sudah closed → ditolak.** Provisi hanya bisa diposting saat periodenya
  masih open, walaupun tanggalnya backdated. Aplikasi cek status periode di ERP **sebelum** posting,
  bukan gagal di tengah jalan.
- Laporan **"SI sudah shipped tapi belum ada EIN lebih dari X hari"** — itu penyebab langsung provisi
  kelewat periode dan sekarang jadi tertolak, bukan sekadar telat.

**Komisi dan asuransi dihitung per EIN**, bukan per ESC — satu baris komisi dan satu baris asuransi
per SI, dihitung atas nilai invoice. Karena 1 SI = 1 EIN, granularitasnya sama dengan SI. Keduanya
ikut di provisi yang sama dengan freight dan EMKL; provision dapat kolom `source_type` = `SI` /
`ESC` / `MANUAL` untuk membedakan dari mana angkanya ditarik. Tidak perlu sub-menu.

> **Sisa pertanyaan:** kalau beberapa ESC dengan agent/rate komisi berbeda digabung dalam satu EIN,
> bagaimana komisinya dihitung? → `open-questions.md` F8a.

---

## 8. Aturan bersama kedua arah

### 8.1 Pull provisi ke tagihan

```
kandidat provisi = vendor + referensi dokumen
   import : BL   (= AJU = PIB)
   export : SI
```

Baris additional cost ikut aturan yang sama — referensinya tetap SI, jadi demurrage dari vendor yang
sama ikut terambil saat tagihan vendor itu masuk. Mekanisme reverse untuk baris yang tidak ditagih
berlaku sama di kedua sisi. **Satu service, dua arah.**

### 8.2 Master cost: type, band, port

Semantik yang dikonfirmasi tim — berbeda dari yang tertulis di doc set lama, wajib eksplisit di spec:

| Type lama | Nama baru | Perhitungan | MIN/MAX artinya |
|---|---|---|---|
| `X` | **`RATE`** | `rate × qty` | pembatas wajar, bukan penentu harga |
| `FIX` | `FIX` | `rate`, qty dipaksa 1 | rentang **pemilihan** — band mana yang berlaku |
| `TIER` | `TIER` | Σ per band, **konsumtif/progresif** | rentang **konsumsi** — berapa unit masuk band ini |

**`TIER` progresif menghasilkan beberapa baris biaya, satu per activity.** Contoh Jasindo, 10
kontainer 40FCL:

| Activity | Band | Kontainer di band | Rate | Jumlah |
|---|---|---|---|---|
| `JASA-IMP-I` | 1–1 | 1 | 450.000 | 450.000 |
| `JASA-IMP-II` | 2–4 | 3 | 400.000 | 1.200.000 |
| `JASA-IMP-III` | 5–999 | 6 | 350.000 | 2.100.000 |
| | | | | **3.750.000** |

Kalau developer salah paham dan hanya mengambil band yang cocok, 10 kontainer dihitung
10 × 350.000 = 3.500.000 dan selisihnya diam-diam.

**Band `FIX` boleh tumpang tindih di data** — tidak perlu dirapikan. Yang menyelesaikan ambiguitas
adalah aturan resolusinya:

```
band terpilih = MIN ≤ qty < MAX      (first match by MIN ascending)
```

Dengan Jasco TRUCKING LCL (1–50, 50–100, 100–500 KGS): qty 50,5 → **tier 2**, sesuai yang
diinginkan. Qty tepat 50 juga masuk tier 2 — kalau maunya tier 1, aturannya berubah jadi
`MIN < qty ≤ MAX`. → `open-questions.md` D14a.

**Kalau tidak ada band yang cocok** (Andalan berhenti di 22 CBM, kiriman 25 CBM): amount = **0**,
transaksi **tidak ditolak**. Tapi barisnya di-flag `SEC_NO_TARIFF_MATCH = 1` dan masuk laporan
**"baris biaya nol karena tidak ada band yang cocok"**. Tanpa flag ini, tagihannya nanti muncul
sebagai direct expense tanpa provisi dan terlihat seperti vendor menagih sesuatu yang tidak
dikomit — padahal master-nya yang kurang satu baris.

**Qty diambil dari mana ditentukan UOM:** `CONT` → jumlah kontainer, `CBM` → volume, `KGS` → gross
weight, `CHARGEABLE_KG` → §5.2, `DAYS` → jumlah hari (dari tagihan untuk storage import, dari
tanggal kontainer untuk demurrage export), `UNIT` → selalu 1.

**Port** ditambahkan sebagai dimensi untuk kedua arah. Karena sekarang baru satu port: **biarkan
semua baris yang ada ber-`port = null`** = berlaku di semua port. Baris port-spesifik ditambahkan
hanya saat memang ada perbedaan harga. Tidak ada backfill.

Urutan pencarian tarif:

1. vendor + port
2. vendor + port null
3. `ALL` + port
4. `ALL` + port null

Langkah 3–4 hanya relevan di import (baris `ALL EMKL`: `LIFTON`, `LIFTOFF`, `ADM-TRUCK`).

### 8.3 Pembulatan mata uang

| Mata uang | Aturan |
|---|---|
| IDR | Bulatkan ke satuan, tanpa desimal |
| Selain IDR | 2 desimal |

Berlaku untuk semua nilai di modul ini. Pembulatan pungutan bea cukai adalah aturan terpisah —
§6.9.

### 8.4 Akun di master cost

Akun yang tersimpan di setiap baris tarif adalah **default saja**. Yang menentukan akun final adalah
resolver di Shipment Control (`ship_posting_account`), dan Finance yang mengisinya. Inkonsistensi di
data lama (`EMKLSTORAGE` 404033 vs 404004) tidak perlu dirapikan di sumber.

---

## 9. Dampak organisasi

**Sales jadi user modul ini** karena BIM pindah ke sini. Pekerjaannya sama, platformnya berbeda.
BIM baru tetap merujuk ke **ESC sebagai contract induk**; yang berubah adalah tempat entry dan
bentuk datanya.

> **Risiko yang tersisa:** blok BIM lama di ERP tidak dihapus. Aplikasi ini tidak akan membacanya,
> tapi selama form-nya masih bisa diisi, ada dua tempat yang bisa berbeda isinya. Minimal yang
> disarankan: jadikan blok itu read-only atau beri catatan "tidak dipakai lagi" di form ERP.

**Purchase** jadi penerima notifikasi ETA−N dan eskalasi ETA−1. Mereka tidak entry data, tapi perlu
akun dan perlu tahu konsekuensi kalau notifikasi diabaikan.

---

## 10. Schema

### 10.1 Prefix registry

Diperiksa tidak bertabrakan dengan prefix Shipment Control (`spv_ spi_ spc_ spk_ spd_ stl_ sti_
stc_ stk_ std_ shc_ shk_ shp_ sha_ sht_ shf_ spa_ shr_`).

```
PREFIX REGISTRY — Exim Front Office
===================================
SBT_  → SHIP_BIM_TEMPLATE
SBM_  → SHIP_BIM
SBN_  → SHIP_BIM_NOTIFY
SBC_  → SHIP_BIM_CARRIER_RULE
SBF_  → SHIP_BIM_FREIGHT_HISTORY
SIF_  → SHIP_IMPORT_FILE
SII_  → SHIP_IMPORT_INVOICE
SIT_  → SHIP_IMPORT_ITEM
SIC_  → SHIP_IMPORT_CONTAINER
SID_  → SHIP_IMPORT_DOC
SES_  → SHIP_EXPORT_SI
SEB_  → SHIP_EXPORT_SI_BIM
SEC_  → SHIP_EXPORT_SI_COST
SEK_  → SHIP_EXPORT_SI_CONTAINER
SEN_  → SHIP_EXPORT_SI_EDN
SED_  → SHIP_EXPORT_SI_DOC
SPR_  → SHIP_PAYMENT_REQUEST
SHH_  → SHIP_HS_TARIFF
SHR2_ → SHIP_HS_PREFERENCE
SKP_  → SHIP_KURS_PAJAK
SDT_  → SHIP_DOC_TYPE
STT_  → SHIP_TRANSIT_TIME
SSK_  → SHIP_SKB
```

Setiap kolom memakai prefix tabelnya tanpa kecuali, termasuk PK, FK, dan audit
(`{PREFIX}_CREATED_BY`, `_CREATED_DATE`, `_UPDATED_BY`, `_UPDATED_DATE`). FK memakai prefix tabel
sendiri + name stem tabel target.

### 10.2 `ship_bim_template` (`sbt_`)

Default standing terms per customer, di-maintain sales.

`SBT_SYS_ID` PK · `SBT_CUSTOMER_CODE` (unique) · `SBT_INCOTERM` · `SBT_PAYMENT_TERM` ·
`SBT_CURRENCY` · `SBT_CONSIGNEE_NAME` · `SBT_CONSIGNEE_ADDRESS` · `SBT_NOMINATED_FORWARDER` ·
`SBT_BL_TYPE_REQUEST` · `SBT_TRANSPORT_MODE` · `SBT_POD_CODE` · `SBT_FINAL_DESTINATION` ·
`SBT_PARTIAL_SHIPMENT` · `SBT_FUMIGATION_REQUIRED` · `SBT_FREE_TIME_BASIS` · `SBT_FREE_TIME_DAYS` ·
`SBT_NET_WEIGHT_MAX_KG` · `SBT_SAMPLE_APPROVAL_DEFAULT` · `SBT_SPECIAL_INSTRUCTION` ·
`SBT_ACTIVE` · audit ×4

### 10.3 `ship_bim` (`sbm_`)

Satu baris per ESC. Semua field **selalu editable** meski di-inherit dari template.

**Identitas** — `SBM_SYS_ID` PK · `SBM_ESC_NO` · `SBM_ESC_DATE` · `SBM_CUSTOMER_CODE` ·
`SBM_SBT_SYS_ID` FK · `SBM_BIM_DATE` · `SBM_STATUS` (`ACTIVE` / `SUPERSEDED` / `CLOSED`) ·
`SBM_SOURCE` (`NEW` / `MIGRATED`)

**Terms** — `SBM_INCOTERM` · `SBM_INCOTERM_FROM_ESC` (snapshot untuk deteksi beda) ·
`SBM_PAYMENT_TERM` (`LC` / `TT_CAD` / `TT_ADVANCE` / `TT_OPEN`) · `SBM_LC_NO` · `SBM_LC_DATE` ·
`SBM_ADVANCE_PCT` · `SBM_CURRENCY`

**Routing** — `SBM_TRANSPORT_MODE` (`SEA_FCL` / `SEA_LCL` / `AIR` / `COURIER`) · `SBM_POL_CODE` ·
`SBM_POD_CODE` · `SBM_FINAL_DESTINATION` · `SBM_VIA_PORT_CODE` · `SBM_TRANSHIPMENT_MAX` ·
`SBM_TRANSIT_DAYS_MAX`

**Pihak & dokumen** — `SBM_CONSIGNEE_NAME` · `SBM_CONSIGNEE_ADDRESS` ·
`SBM_NOMINATED_FORWARDER` (wajib kalau FOB/EXW) · `SBM_BL_TYPE_REQUEST` (`MBL` / `HBL` / `BOTH` /
`NO_REQUEST`)

**Kargo & operasional** — `SBM_PARTIAL_SHIPMENT` (`ALLOWED` / `NOT_ALLOWED`) ·
`SBM_FUMIGATION_REQUIRED` · `SBM_NET_WEIGHT_MAX_KG` · `SBM_FREE_TIME_BASIS` (`DAYS` /
`CARRIER_STANDARD` / `NOT_APPLICABLE`) · `SBM_FREE_TIME_DAYS` · `SBM_SAMPLE_APPROVAL`
(`NOT_REQUIRED` / `PENDING` / `APPROVED`) · `SBM_SAMPLE_APPROVED_DATE`

**Rencana** — `SBM_PLANNED_CONTAINER_TOTAL` · `SBM_PLANNED_SHIPMENT_COUNT`

**Rate baseline (di-pull dari ESC)** — `SBM_CONTRACT_FREIGHT_RATE` · `_CURRENCY` · `_UOM` ·
`_QUOTED_BY` · `_QUOTED_DATE` · `_VALID_UNTIL`

**Flex** — `SBM_SPECIAL_INSTRUCTION` (CLOB) · `SBM_INTERNAL_NOTE` (CLOB) ·
`SBM_LEGACY_BODY` (CLOB, hasil migrasi) · `SBM_REVIEW_PENDING` (flag field yang belum direview)

`FREE_TIME_BASIS` menyelesaikan kekacauan field itu: `14` → `DAYS` + 14; `Regular` / `Normal` /
`Standard` → `CARRIER_STANDARD`; `FOB` → `NOT_APPLICABLE`, bisa di-default dari incoterm.

### 10.4 Tabel anak BIM

**`ship_bim_notify` (`sbn_`)** — `SBN_SYS_ID` PK · `SBN_SBM_SYS_ID` FK · `SBN_SORT_ORDER` ·
`SBN_PARTY_NAME` · `SBN_PARTY_ADDRESS`. Data menunjukkan ada urutan (`1ST. : MERCURY FREIGHT`).

**`ship_bim_carrier_rule` (`sbc_`)** — `SBC_SYS_ID` PK · `SBC_SBM_SYS_ID` FK · `SBC_RULE_TYPE`
(`ALLOW_ONLY` / `EXCLUDE`) · `SBC_CARRIER_CODE`. Enum wajib karena maknanya berlawanan:
`Do not use Maersk` itu larangan; `USE MAERSK / MSC / EVERGREEN / …` itu daftar yang diizinkan.

**`ship_bim_freight_history` (`sbf_`)** — `SBF_SYS_ID` PK · `SBF_SBM_SYS_ID` FK · `SBF_RATE` ·
`SBF_CURRENCY` · `SBF_UOM` · `SBF_EFFECTIVE_DATE` · `SBF_REASON` · `SBF_SUPERSEDED_AT` · audit.
Hanya terisi saat contract dinegosiasi ulang (§4.5).

### 10.5 `ship_import_file` (`sif_`)

**Identitas** — `SIF_SYS_ID` PK · `SIF_TRANS_NO` · `SIF_TRANS_CODE` (seri import) ·
`SIF_TRANS_DATE` · `SIF_STATUS` · `SIF_BL_NO` · `SIF_BL_DATE` · `SIF_BL_TYPE` (`MBL` / `HBL`) ·
`SIF_MASTER_BL_NO`

**Pihak & rute** — `SIF_SUPPLIER_CODE` (1 BL = 1 supplier) · `SIF_SUPPLIER_NAME` ·
`SIF_ORIGIN_COUNTRY` · `SIF_ORIGIN_PORT_CODE` · `SIF_DEST_PORT_CODE` · `SIF_PORT_CODE` (Pabean) ·
`SIF_VESSEL` · `SIF_VOYAGE`

**Waktu** — `SIF_ETD` · `SIF_TRANSIT_DAYS` (snapshot) · `SIF_ETA_CALCULATED` · `SIF_ETA_ACTUAL`

**Kargo** — `SIF_GROSS_WEIGHT_KG` · `SIF_VOLUME_CBM` · `SIF_TRANSPORT_MODE` · `SIF_INCOTERM` ·
`SIF_CIF_MODE` (`A` = nilai sudah CIF / `B` = FOB, diturunkan dari incoterm)

**Pajak & pabean** — `SIF_KURS_PAJAK` · `SIF_KURS_PERIOD_FROM` · `SIF_KURS_PERIOD_TO` ·
`SIF_INSURANCE_PREMIUM` · `SIF_INSURANCE_CURRENCY` · `SIF_FACILITY_CODE` (label, tidak mengubah BM) ·
`SIF_SSK_SYS_ID` FK · `SIF_PPH_EXEMPT` · `SIF_AJU_NO` · `SIF_PIB_NO` · `SIF_PIB_DATE` ·
`SIF_SPPB_NO`

**Rollup** — `SIF_EST_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · `_TOTAL` ·
`SIF_EST_TOTAL_NO_PREFERENCE` · `SIF_EXPOSURE_COMPLETE` (flag §6.7) ·
`SIF_ACT_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · `_TOTAL` ·
`SIF_VAR_KURS` · `SIF_VAR_TARIFF` · `SIF_VAR_QTY`

**Kontrol** — `SIF_DOC_COMPLETE_FLAG` · `SIF_RECOMPUTE_COUNT` · audit ×4

Index: `UQ` (`SIF_BL_NO`) · `UQ` (`SIF_AJU_NO`) where not null · `IX` (`SIF_ETA_CALCULATED`) ·
`IX` (`SIF_SUPPLIER_CODE`)

### 10.6 `ship_import_invoice` (`sii_`) dan `ship_import_item` (`sit_`)

**`sii_`** — `SII_SYS_ID` PK · `SII_SIF_SYS_ID` FK cascade · `SII_INVOICE_NO` · `SII_INVOICE_DATE` ·
`SII_CURRENCY` · `SII_FREIGHT_TOTAL` · `SII_DISCOUNT_TOTAL` · `SII_VALUE_TOTAL` · rollup duty ·
audit ×4. Supplier diwarisi dari `SIF_SUPPLIER_CODE`, tidak disimpan ulang.

**`sit_`** — `SIT_SYS_ID` PK · `SIT_SII_SYS_ID` FK cascade · `SIT_PO_NO` · `SIT_PO_LINE` ·
`SIT_ITEM_CODE` · `SIT_ITEM_NAME_PO` · `SIT_ITEM_NAME_DOC` · `SIT_HS_CODE_PO` (mentah dari PO) ·
`SIT_HS_CODE` (hasil normalisasi / edit) · `SIT_COUNTRY_OF_ORIGIN` · `SIT_BM_PCT` ·
`SIT_BM_SOURCE` (`PREFERENCE` / `MFN` / `MANUAL`) · `SIT_BM_PCT_FALLBACK` (opsional, §6.7) ·
`SIT_BM_OVERRIDE_REASON` · `SIT_DUTY_TYPE` (`BM` / `BM_KITE`) · `SIT_PO_QTY` · `SIT_SHIPPED_QTY` · `SIT_UOM` · `SIT_RATE` ·
`SIT_VALUE_FOB` · `SIT_DISCOUNT_ALLOC` · `SIT_FREIGHT_ALLOC` · `SIT_INSURANCE_ALLOC_IDR` ·
`SIT_VALUE_CIF` · `SIT_VALUE_CIF_IDR` · `SIT_NILAI_IMPOR` · `SIT_DUTY_BM` · `_PPN` · `_PPNBM` ·
`_PPH` · `SIT_ACT_DUTY_BM` · `_PPN` · `_PPNBM` · `_PPH` · audit ×4

Index: `IX` (`SIT_SII_SYS_ID`) · `IX` (`SIT_PO_NO`, `SIT_PO_LINE`) · `IX` (`SIT_HS_CODE`)

### 10.7 `ship_import_container` (`sic_`) dan `ship_import_doc` (`sid_`)

**`sic_`** — `SIC_SYS_ID` PK · `SIC_SIF_SYS_ID` FK · `SIC_CONTAINER_TYPE` · `SIC_CONTAINER_NO` ·
`SIC_QTY` · `SIC_SEAL_NO` · audit

**`sid_`** — `SID_SYS_ID` PK · `SID_SIF_SYS_ID` FK · `SID_SDT_CODE` FK · `SID_IS_MANDATORY`
(snapshot hasil evaluasi) · `SID_FILE_PATH` · `SID_FILE_NAME` · `SID_FILE_SIZE` ·
`SID_UPLOADED_BY` · `SID_UPLOADED_AT` · `SID_VERSION` · audit

File di MinIO. Versi lama disimpan, tidak ditimpa.

### 10.8 `ship_export_si` (`ses_`)

**Identitas** — `SES_SYS_ID` PK · `SES_TRANS_NO` · `SES_TRANS_CODE` (seri export) ·
`SES_TRANS_DATE` · `SES_STATUS` (`BOOKED` / `CONFIRMED` / `SHIPPED` / `CLOSED` / `CANCELLED`) ·
`SES_CUSTOMER_CODE`

**Terms snapshot dari BIM** — seluruh standing terms §10.3 dengan prefix `SES_`, plus
`SES_INCOTERM_FROM_BIM` dan `SES_INCOTERM_CHANGE_REASON`

**Fakta pengiriman** — `SES_TRANSPORT_MODE` · `SES_POL_CODE` · `SES_POD_CODE` · `SES_VESSEL` ·
`SES_VOYAGE` · `SES_ETD` · `SES_ETA` · `SES_ATD` · `SES_STUFFING_DATE` ·
`SES_CONTAINER_OUT_DATE` · `SES_CONTAINER_RETURN_DATE` · `SES_FREE_TIME_DAYS` ·
`SES_GROSS_WEIGHT_KG` · `SES_NET_WEIGHT_KG` · `SES_VOLUME_CBM` · `SES_DIM_L` · `SES_DIM_W` ·
`SES_DIM_H` · `SES_CHARGEABLE_KG` (dihitung)

**Dokumen** — `SES_DO_NO` · `SES_DO_DATE` · `SES_BL_NO` · `SES_BL_DATE` · `SES_AWB_NO` ·
`SES_TRACKING_NO` · `SES_EIN_NO` · `SES_EIN_DATE` · `SES_PEB_NO` · `SES_PEB_DATE` · `SES_PEB_TYPE`

**Cancel** — `SES_CANCEL_DATE` · `SES_CANCEL_REASON` · `SES_CANCEL_BY`

**Kontrol** — `SES_COST_COMPLETE` (dihitung, §7.3) · audit ×4

Index: `UQ` (`SES_TRANS_NO`) · `UQ` (`SES_EIN_NO`) where not null · `IX` (`SES_STATUS`) ·
`IX` (`SES_CUSTOMER_CODE`)

**`ship_export_si_bim` (`seb_`)** — penghubung many-to-many SI ↔ BIM (1 EIN bisa beberapa ESC):
`SEB_SYS_ID` PK · `SEB_SES_SYS_ID` FK · `SEB_SBM_SYS_ID` FK · `SEB_ESC_NO` (snapshot) · audit.
`UQ` (`SEB_SES_SYS_ID`, `SEB_SBM_SYS_ID`).

**`ship_export_si_edn` (`sen_`)** — delivery note di bawah EIN (1 EIN bisa beberapa EDN):
`SEN_SYS_ID` PK · `SEN_SES_SYS_ID` FK · `SEN_EDN_NO` · `SEN_EDN_DATE` · `SEN_VALUE` ·
`SEN_CURRENCY` · audit.

### 10.9 `ship_export_si_cost` (`sec_`)

`SEC_SYS_ID` PK · `SEC_SES_SYS_ID` FK cascade · `SEC_VENDOR_CODE` · `SEC_COST_TYPE` ·
`SEC_ACTIVITY_CODE` · `SEC_ACTIVITY_NAME` (snapshot) · `SEC_CONTAINER_TYPE` · `SEC_QTY` ·
`SEC_RATE` · `SEC_RATE_FROM_MASTER` · `SEC_CURRENCY` · `SEC_PPN_PCT` · `SEC_PPH_PCT` ·
`SEC_PPH_ADVANCED` · `SEC_BASE_AMOUNT` · `SEC_PPN_AMOUNT` · `SEC_PPH_AMOUNT` · `SEC_FC_AMOUNT` ·
`SEC_LC_AMOUNT` · `SEC_EXPENSE_MAIN_ACNT` · `SEC_EXPENSE_SUB_ACNT` · `SEC_PROVISION_ACNT` ·
`SEC_PPN_ACNT` · `SEC_PPH_ACNT` · `SEC_TARIFF_SYS_ID` (provenance) ·
**`SEC_NO_TARIFF_MATCH`** (§8.2 — nol karena tidak ada band cocok) · `SEC_IS_ADDITIONAL` ·
`SEC_STATUS` (`DRAFT` / `APPROVED` / `VOID`) · `SEC_APPROVED_BY` · `SEC_APPROVED_AT` ·
`SEC_APPROVAL_NOTE` · `SEC_APPROVAL_REF` · `SEC_APPROVAL_OFFLINE_BY` · `SEC_VOID_REASON` ·
`SEC_SPV_SYS_ID` (FK provision, null = belum diprovisi) · audit ×4

Index: `IX` (`SEC_SES_SYS_ID`) · `IX` (`SEC_VENDOR_CODE`, `SEC_STATUS`) ·
`IX` (`SEC_SPV_SYS_ID`) — melayani query pull provisi §7.5 ·
`IX` (`SEC_NO_TARIFF_MATCH`) where = 1 — melayani laporan §8.2

`ship_export_si_container` (`sek_`) dan `ship_export_si_doc` (`sed_`) mengikuti bentuk `sic_` /
`sid_`.

### 10.10 `ship_payment_request` (`spr_`)

`SPR_SYS_ID` PK · `SPR_TRANS_NO` · `SPR_TRANS_DATE` · `SPR_DIRECTION` · `SPR_SIF_SYS_ID` FK
(nullable) · `SPR_SES_SYS_ID` FK (nullable) · `SPR_COST_TYPE` · `SPR_VENDOR_CODE` ·
`SPR_REQUESTED_AMOUNT` · `SPR_CURRENCY` · `SPR_NEEDED_BY` · `SPR_STATUS` (`DRAFT` / `SENT` /
`PAID`) · `SPR_SENT_AT` · `SPR_PAID_AMOUNT` · `SPR_PAID_DATE` · `SPR_VARIANCE_AMOUNT` ·
`SPR_VARIANCE_NOTE` · `SPR_PAID_BY` · audit ×4

Check constraint: tepat satu dari `SPR_SIF_SYS_ID` / `SPR_SES_SYS_ID` tidak null.
Tidak ada status `REJECTED`.

### 10.11 Master baru

**`ship_hs_tariff` (`shh_`)** — akselerator opsional, diisi manual per HS yang dipakai.
`SHH_SYS_ID` PK · `SHH_HS_CODE` · `SHH_DESCRIPTION` · `SHH_BM_PCT_MFN` · `SHH_PPN_PCT` ·
`SHH_PPNBM_PCT` · `SHH_PPH_PCT` (2,5% umumnya, 7,5% untuk HS tertentu — lihat PIB 000310, HS 27101945) · `SHH_VALID_FROM` · `SHH_VALID_TO` · `SHH_ACTIVE` · audit.
`UQ` (`SHH_HS_CODE`, `SHH_VALID_FROM`).

**`ship_hs_preference` (`shr2_`)** — HS code × origin country × jenis form (`E` / `D` / `AK` /
`AI` / `IJEPA`) → `BM_PCT`, masa berlaku. Saat ini hanya Form E yang terkonfirmasi dipakai.

**`ship_kurs_pajak` (`skp_`)** — `SKP_SYS_ID` PK · `SKP_CURRENCY` · `SKP_RATE` ·
`SKP_VALID_FROM` · `SKP_VALID_TO` · `SKP_KMK_NO` · audit. `UQ` (`SKP_CURRENCY`, `SKP_VALID_FROM`).

**`ship_doc_type` (`sdt_`)** — `SDT_CODE` PK · `SDT_NAME` · `SDT_DIRECTION` · `SDT_IS_MANDATORY` ·
`SDT_APPLIES_ORIGIN_COUNTRY` · `SDT_APPLIES_HS_PREFIX` · `SDT_APPLIES_INCOTERM` ·
`SDT_APPLIES_FACILITY` · `SDT_APPLIES_TRANSPORT_MODE` · `SDT_SORT_ORDER` · `SDT_ACTIVE` · audit.
Null pada kolom `APPLIES_*` = berlaku untuk semua.

**`ship_transit_time` (`stt_`)** — `STT_SYS_ID` PK · `STT_ORIGIN_COUNTRY` · `STT_ORIGIN_PORT_CODE`
(nullable) · `STT_DEST_PORT_CODE` (nullable) · `STT_TRANSIT_DAYS` · **`STT_NOTIFY_LEAD_DAYS`** ·
`STT_ACTIVE` · audit. Resolve: port-pair dulu, fallback country.

**`ship_skb` (`ssk_`)** — `SSK_SYS_ID` PK · `SSK_SKB_NO` · `SSK_SKB_DATE` · `SSK_VALID_FROM` ·
`SSK_VALID_TO` · `SSK_SCOPE` · `SSK_TAX_TYPE` · `SSK_FILE_PATH` · `SSK_ACTIVE` · audit.

### 10.12 Parameter (`ship_parameter`)

| Key | Default | Dipakai |
|---|---|---|
| `AIR_VOLUMETRIC_DIVISOR` | `6000` | §5.2 chargeable weight |
| `CURRENCY_ROUNDING_IDR` | `0` desimal | §8.3 |
| `CURRENCY_ROUNDING_FCY` | `2` desimal | §8.3 |
| `DUTY_ROUNDING_BM` | `CEIL_1000` | §6.9 — BM dibulatkan ke atas ke ribuan |
| `DUTY_ROUNDING_PPN` | `TRUNC_1` | §6.9 — PPN dipotong ke rupiah |
| `DUTY_ROUNDING_PPH_BASE` | `FLOOR_1000` | §6.9 — basis PPh dibulatkan ke bawah ke ribuan |
| `DOC_REMINDER_LEAD_DAYS` | `3` | §6.10 — fallback kalau master transit time kosong |
| `DOC_ESCALATION_LEAD_DAYS` | `1` | §6.10 notifikasi 3 |
| `PIB_VARIANCE_THRESHOLD_PCT` | `5%` | §6.10 notifikasi 4 — editable |
| `SKB_EXPIRY_WARNING_DAYS` | `30` | §6.9 |
| `PPN_RATE_NOMINAL` | `12%` | Dicetak di dokumen |
| `PPN_RATE_EFFECTIVE` | `11%` | **Dipakai menghitung** — DPP nilai lain 11/12 |
| `PPH22_PCT_DEFAULT` | `2.5%` | Default; override per HS di `SHH_PPH_PCT` |
| `FIX_BAND_BOUNDARY` | `MIN_INCLUSIVE` | §8.2 — pending D14a |

Tarif PPN dan pembulatan **diverifikasi dari 10 PIB aktual**, bukan diasumsikan dari spreadsheet.
Tetap sebagai parameter karena bisa berubah.

> **Jebakan terbesar di modul ini:** PPN tertulis `12%` di setiap PIB tapi angkanya 11% dari Nilai
> Impor. Kalau developer memakai 12%, estimasi akan lebih tinggi 9% dari aktual — pada BL 7,5
> miliar itu selisih 75 juta yang muncul di setiap shipment.

---

## 11. Perubahan yang dibutuhkan di Shipment Control

Semuanya additive dan bisa disiapkan sekarang.

| # | Perubahan | Kenapa |
|---|---|---|
| 1 | `spv_sif_sys_id` dan `spv_ses_sys_id` nullable di `ship_provision` | FK ke dokumen induk (§1) |
| 2 | `spv_source_type` = `SI` / `ESC` / `MANUAL` / `FILE` | §7.8 |
| 3 | `shc_is_additional` dan `shc_requires_line_approval` di `ship_cost_type` | §7.6 |
| 4 | Cost type baru `CANCELLATION_FEE` + posting account-nya | §7.4, F2 |
| 5 | `sht_port_code` nullable di `ship_tariff` | §8.2 — null = semua port, tanpa backfill |
| 6 | `sht_uom` menerima `CHARGEABLE_KG` | §5.2 |
| 7 | Rename `SMC_TYPE` `X` → **`RATE`** saat backfill | §8.2 — `X` tidak berarti apa-apa |
| 8 | Definisi `TIER` diperbaiki di `spec.md`: **progresif**, menghasilkan beberapa baris | §8.2 |
| 9 | Aturan band `FIX`: `MIN ≤ qty < MAX`, overlap di data **dibiarkan** | §8.2 |
| 10 | Flag `no_tariff_match` + laporan baris biaya nol | §8.2 |
| 11 | **Hapus** aturan EMKL↔PIB backfill (`spec.md` §5.4–5.5) berikut test-nya | §6.1 |
| 12 | Cek status periode ERP sebelum posting; **tolak** kalau closed | §7.8, F1 |
| 13 | Aksi **release sisa provisi** + laporan provisi menggantung | §6.12 |
| 14 | Laporan **BM KITE dibayar per periode** (akun sama dengan BM, dipisah untuk pelaporan) | §6.6 |

---

## 12. Kriteria sukses

1. PIB estimation cocok **sampai rupiah** dengan 10 PIB aktual yang sudah terbit (BM, PPN, PPh),
   bukan hanya dengan spreadsheet. Ini test regresi utama modul: tiga aturan pembulatan §6.9 dan
   PPN efektif 11% harus reproduce angka dokumen resmi.
2. Shipment CIF yang kolom freight-nya diisi **tidak** menghasilkan BM over (§6.5 Mode A).
3. Estimasi vs aktual PIB tampil dengan variance terpisah antara kurs, tarif, dan kuantitas.
4. Modul berfungsi penuh dengan `ship_hs_tariff` **kosong** — semua BM% manual, tidak ada blocker.
5. Checklist dokumen tergenerate otomatis sesuai origin country, HS, incoterm, dan fasilitas.
6. Satu SI bisa dibuat dari beberapa BIM; konflik identitas ditolak dengan pesan yang menyebut field
   mana yang berbeda.
7. Satu SI menghasilkan beberapa dokumen provisi di periode berbeda lewat `provision_sys_id`;
   additional cost yang di-approve enam bulan kemudian masuk periodenya sendiri.
8. Provisi backdated ke periode closed **ditolak sebelum posting**, dengan pesan yang jelas.
9. `TIER` progresif diverifikasi dengan Pest terhadap data master produksi (10 kontainer Jasindo →
   tiga baris, total 3.750.000).
10. Band `FIX` yang overlap resolve deterministik: 50,5 KGS → tier 2.
11. Qty tanpa band yang cocok menghasilkan nol **yang ter-flag dan muncul di laporan**, bukan nol
    yang diam.
12. Cost sheet per BL dan per SI menampilkan seluruh biaya dari satu query, termasuk additional cost.
13. BM KITE masuk budget PIB dan bisa dilaporkan terpisah per periode, walaupun akunnya sama
    dengan BM biasa.
14. Empat skema preferensi (ACFTA, AIFTA, ATIGA, RCEP) bisa dipilih user per item, tidak
    disimpulkan dari negara asal.
15. Tidak ada satu pun tarif, akun, kurs, atau tarif pajak yang di-hardcode di kode.
