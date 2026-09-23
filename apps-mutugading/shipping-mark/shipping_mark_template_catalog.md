# Shipping Mark — Template Catalog (draft v0.1)

Dipetakan dari 30 contoh shipping mark tim despatch (email 11 Jul 2026).
Tujuan: bukti bahwa desain data-driven bisa handle semua case, sekaligus jadi
bahan awal isi tabel `SHIP_MARK_TEMPLATE` + `SHIP_MARK_TEMPLATE_LINE`.
 
---

## 1. Cara baca & model "customer baru"

Temuan inti: **30 file → hanya ~14 bentuk template distinct**, dan semuanya
tersusun dari **satu kosakata field yang sama** (bagian 2). Perbedaan antar
customer cuma: field mana yang dipakai, urutannya, teks static-nya, label party,
grain, dan ukuran.

Konsekuensi untuk **customer/format baru** (yang kamu khawatirkan):
- 95% kasus → cukup bikin 1 baris template baru + pilih field dari kamus yang
  sudah ada. **Tanpa ngoding, tanpa deploy.** Ini kerjaan config, bukan dev.
- Kalau ada field yang benar-benar baru (jarang) → tambah **1 baris** ke kamus
  field (bagian 2), sekali, lalu tersedia untuk semua template berikutnya.
- Fitur "clone template" → customer baru yang mirip customer lama tinggal
  duplikat lalu edit.
  Jadi katalog ini bukan daftar tertutup — ini kamus + contoh awal. Sistemnya
  tetap terbuka untuk format baru.

---

## 2. Kamus Field (reusable vocabulary)

Setiap `line` di template menunjuk ke salah satu key ini. `TYPE` menentukan
dari mana value-nya datang.

| Key | Muncul sebagai (variasi label di sampel) | Type | Sumber / aturan |
|---|---|---|---|
| `MGT_SHIPPER` | SHIPPER, EXPORTER, SUPPLIER, Name Of Manufacturer | STATIC | "PT. MUTU GADING TEKSTIL" |
| `MGT_ADDRESS` | (alamat pabrik) | STATIC | JL. RAYA SOLO PURWODADI KM 11, GONDANGREJO, KARANGANYAR 57773 |
| `MGT_TAX_ID` | (Argentina: MGT / 316/1, TAX ID) | STATIC | ID pajak/eksportir MGT, per template yang butuh |
| `ORIGIN` | COUNTRY ORIGIN, ORIGIN | STATIC | "INDONESIA" |
| `MADE_IN` | MADE IN INDONESIA | STATIC | footer tetap |
| `CUST_PARTY_NAME` | BUYER, CONSIGNEE, NOTIFY, RECEIVER, Name Of Consignee | STATIC/customer | nama customer; **role = atribut template** |
| `CUST_PARTY_ADDR` | (alamat customer) | STATIC/customer | alamat + kode negara |
| `CUST_TAX_ID` | CNPJ (Brasil), CUIT (Argentina), RFC | STATIC/customer | id pajak customer per negara |
| `CUST_SPEC_TEXT` | 100% POLYESTER YARN, Oeko-Tex line, Article, Tex | STATIC/customer | teks spesifikasi tambahan per customer/item |
| `ITEM` | ITEM, PRODUCT, PRODUCT DESCRIPTION, DESCRIPTION OF GOODS | SYSTEM | item code (`soim_item_code`) |
| `LOT_NO` | LOT NO, LOT NR, LOT NUMBER, MERGE, Merge, MERGE/Lot | SYSTEM | `ss_batch_no` (single, atau 'MULTI' di grain kasar) |
| `NET_WT` | NET WEIGHT, Net Weight, NET WT | SYSTEM | `ss_qty` (SUM di grain kasar) |
| `GROSS_WT` | GROSS WEIGHT, TOTAL GROSS WEIGHT, GROSS WT, GW | SYSTEM | `ss_gross_wgt` (SUM) |
| `PALLET_NO` | PALLET NO, PALLET NO. | SYSTEM | `ss_pallet_no` diformat + `n/total` |
| `CARTON_NO` | CARTON NO, CARTON NR, CARTON #, CARTON/PALLET NO | SYSTEM | `ss_cart_no` + `n/total` |
| `BOBBIN_QTY` | NUMBER OF BOBBIN PER CARTON/PALLET, Number of Bobbins Per Pallet | SYSTEM | `ss_no_bobbin` |
| `INVOICE_NO` | INVOICE NO. | SYSTEM | `sohm_invh_txn_code`-`sohm_invh_no` |
| `SSC_NO` | (ref transaksi) | SYSTEM | `sohm_txn_code`-`sohm_no` |
| `COLOR_MGT` | COLOR (nama shade MGT: ORINOCO GY, GECKO GREEN) | SYSTEM | shade dari scan (`ss_invi_shade`) |
| `TARE` | TARE | COMPUTED | `GROSS_WT − NET_WT` |
| `CUST_MATERIAL_NO` | CUST MATERIAL NO, CUSTOMER MATERIAL NO, BD NO, PRODUCT CODE, GTA Part Number | MASTER | SKU customer per item MGT |
| `CUST_ITEM_CODE` | CUSTOMER ITEM CODE, Article No | MASTER | kode item/article customer |
| `COLOR_CUST` | COLOR (nama customer: NATURAL, ALIMA GY, SAILOR BLUE, BLOOD RED) | MASTER | mapping warna customer per item |
| `NCM` | NCM | MASTER | kode tarif Brazil per item/destination |
| `BRAND_LABEL` | BRAND LABEL | MASTER | merek (mis. GREENTEX) per customer |
| `TUBE_COLOR` | COLOUR OF TUBE, Tube Color | MASTER? | warna tube — **confirm** apakah ada di data box/scan |
| `CUST_PO_NO` | CUSTOMER PO NO, PO NO, BD PO NO, Customer P.O # | MANUAL* | *SYSTEM kalau tersimpan di SO — **confirm** |
| `LC_NO` | L/C NO. | SYSTEM* | dari modul export/LC — **confirm** join key |
| `CONTRACT_NO` | CONTRACT NO. (ESC/2023000406) | SYSTEM* | *kemungkinan = no. ESC di SO — **confirm** |
| `POS_NO` | POS NO | MANUAL | nomor posisi (ESC335) |
| `MID_CUSTOMER` | MID CUSTOMER | MASTER/MANUAL | per customer, sering "-" |

\* = kemungkinan besar SYSTEM begitu sumbernya dikonfirmasi; sementara diperlakukan MANUAL.
 
---

## 3. Blok baris reusable (biar template DRY)

| Blok | Isi baris |
|---|---|
| `BLK_MGT` | `MGT_SHIPPER` (+ `MGT_ADDRESS` opsional) |
| `BLK_PERUNIT_PALLET` | `LOT_NO`, `NET_WT`, `GROSS_WT`, `PALLET_NO` |
| `BLK_PERUNIT_CARTON` | `LOT_NO`, `NET_WT`, `GROSS_WT`, `CARTON_NO` |
| `FOOT_MADE_IN` | `MADE_IN` |

Grain default (bagian 4) menentukan blok per-unit mana yang dipakai; di grain
UNIFORM/ITEM, field per-unit jadi agregat.
 
---

## 4. Katalog Template

Format tiap template: `urutan. Label → KEY (type)`. Blok reusable ditulis
sebagai nama bloknya.

### 4.1 TPL_BEKAERT_AU — Bekaert Australia
File: `BEKAERT AUS 209` · Party: **BUYER** · Grain default: **PALLET** · Size: A5
1. BUYER → `CUST_PARTY_NAME` (STATIC)
2. CUSTOMER PO NO → `CUST_PO_NO` (MANUAL*)
3. ITEM → `ITEM` (SYSTEM)
4. COLOR → `COLOR_CUST` (MASTER)
5. CUST. MATERIAL NO → `CUST_MATERIAL_NO` (MASTER)
6. `BLK_PERUNIT_PALLET` (SYSTEM)
7. `FOOT_MADE_IN`
### 4.2 TPL_BEKAERT_SHIPPER — Bekaert (CPT / US), Brown Coffee
File: `SHIPPER BEKAERT 147`, `BROWN COFFE 1` (varian lean) · Party: **SHIPPER=MGT** · Grain: **PALLET/CARTON** · Size: A5
1. SHIPPER → `MGT_SHIPPER` (STATIC)
2. PRODUCT DESCRIPTION → `ITEM` (SYSTEM)
3. COLOR → `COLOR_CUST` (MASTER)  *(Brown Coffee: static `COL. …`)*
4. CUST MATERIAL NO → `CUST_MATERIAL_NO` (MASTER)  *(hilang di Brown Coffee)*
5. `BLK_PERUNIT_PALLET` (tanpa PALLET_NO di Brown Coffee)
6. CUSTOMER PO NO → `CUST_PO_NO` (MANUAL*)  *(hilang di Brown Coffee)*
7. `FOOT_MADE_IN`
### 4.3 TPL_HAMILTON_MINIMAL — Hamilton, Novalfa, GTA USA, Innofa Mexico
File: `Hamilton International 39`, `NOVALFA GROUP SRL`, `GTA USA2`, `INNOFA MEXICO 061 JUMBO` · Party: nama static · Grain: **PALLET** · Size: A5
1. (party) → `CUST_PARTY_NAME` (STATIC)
2. PRODUCT DESCRIPTION / ITEM → `ITEM` (SYSTEM)
3. `BLK_PERUNIT_PALLET` (SYSTEM)  *(Novalfa: tanpa PALLET_NO)*
- Varian: GTA & Innofa menambah `COLOR` (`COLOR_CUST`, MASTER); GTA menaruh sebagai catatan static.
### 4.4 TPL_BEKAERT_BRASIL — Bekaert Brasil (export/customs)
File: `BEKAERT BRASIL 19` · Party: **NOTIFY + EXPORTER** · Grain: **CARTON** · Size: A5
1. NOTIFY → `CUST_PARTY_NAME` + `CUST_PARTY_ADDR` (STATIC)
2. CNPJ → `CUST_TAX_ID` (STATIC)
3. COUNTRY → (STATIC "BRASIL")
4. ORIGIN → `ORIGIN` (STATIC)
5. EXPORTER → `MGT_SHIPPER` (STATIC)
6. DESCRIPTION OF GOODS → `ITEM` (SYSTEM) (+ static `COL. …`)
7. MATERIAL NO. → `CUST_MATERIAL_NO` (MASTER)
8. NCM → `NCM` (MASTER)
9. GROSS WEIGHT / NET WEIGHT → `GROSS_WT`, `NET_WT` (SYSTEM)
10. CARTON NR → `CARTON_NO` (SYSTEM)
11. LOT NR → `LOT_NO` (SYSTEM)
12. PO NO → `CUST_PO_NO` (MANUAL*)
### 4.5 TPL_LC_CARTON — shipment berbasis L/C
File: `194 AM`, `MM CORP AM` · Party: — · Grain: **CARTON** · Size: A5
1. L/C NO. → `LC_NO` (SYSTEM*)
2. INVOICE NO. → `INVOICE_NO` (SYSTEM)
3. ITEM → `ITEM` (SYSTEM)
4. WEIGHT → (label header static)
5. Net Weight / Gross Weight → `NET_WT`, `GROSS_WT` (SYSTEM)
6. CARTON NO. → `CARTON_NO` (SYSTEM)  *(skala besar: 680/704, 574/659)*
7. `FOOT_MADE_IN`
### 4.6 TPL_GREENTEX_BRAND — Greentex (brand label detail)
File: `ESC335` · Grain: **CARTON/PALLET** · Size: A5
1. BRAND LABEL → `BRAND_LABEL` (MASTER)
2. POS NO → `POS_NO` (MANUAL)
3. PRODUCT CODE → `CUST_MATERIAL_NO` (MASTER)
4. MID CUSTOMER → `MID_CUSTOMER` (MASTER/MANUAL)
5. PRODUCT → `ITEM` (SYSTEM) + `CUST_SPEC_TEXT` (Oeko-Tex, STATIC)
6. CARTON/PALLET NO. → `CARTON_NO` (SYSTEM)
7. TOTAL GROSS WEIGHT / NET WEIGHT → `GROSS_WT`, `NET_WT` (SYSTEM)
8. TARE → `TARE` (COMPUTED)
9. LOT NUMBER → `LOT_NO` (SYSTEM)
10. NUMBER OF BOBBIN → `BOBBIN_QTY` (SYSTEM)
11. COLOUR OF TUBE → `TUBE_COLOR` (MASTER?)
### 4.7 TPL_CONSIGNEE_ID — Politel, Bekaert Canada/US, Buyer Bekaert
File: `CONSIGNEE POLITEL 199`, `BEKAERT CANADA`, `BUYER BEKAERT 144`, `BEKAERT DESLEE CTR 272` · Party: **BUYER/CONSIGNEE (+NOTIFY)** · Grain: varies
Blok identitas (per-unit menyusul sesuai grain):
1. (party) → `CUST_PARTY_NAME` (STATIC) [+ NOTIFY kedua di Bekaert Canada]
2. PRODUCT → `ITEM` (SYSTEM)
3. CUSTOMER MATERIAL NO → `CUST_MATERIAL_NO` (MASTER)
4. COLOR → `COLOR_CUST` (MASTER) *(ada di Politel/Canada)*
5. CUSTOMER PO NO → `CUST_PO_NO` (MANUAL*)
6. + `BLK_PERUNIT_*` sesuai grain
### 4.8 TPL_FURNIWEB — Furniweb (per carton)
File: `TEMPLATE FURNIWEB` · Party: **RECEIVER** · Grain: **CARTON** · Size: A5
1. PRODUCT → static "POLYESTER TEXTURED YARN" (STATIC) *(atau `ITEM`)*
2. CARTON NO. → `CARTON_NO` (SYSTEM)
3. NET WT / GROSS WT → `NET_WT`, `GROSS_WT` (SYSTEM)
4. SUPPLIER → `MGT_SHIPPER` + `MGT_ADDRESS` (STATIC)
5. COUNTRY ORIGIN → `ORIGIN` (STATIC)
6. RECEIVER → `CUST_PARTY_NAME` + `CUST_PARTY_ADDR` (STATIC)
### 4.9 TPL_ORIENTAL — Oriental (per carton)
File: `TEMPLATE ORIENTAL` (sudah mail-merge) · Grain: **CARTON** · Size: A5
1. PRODUCT DESCRIPTION → `ITEM` (SYSTEM) + static "POLYESTER TEXTURED YARN"
2. COLOR → `COLOR_CUST`/`COLOR_MGT`
3. LOT NO → `LOT_NO` (SYSTEM)  *(field «Merge»)*
4. NET / GROSS → `NET_WT`, `GROSS_WT` (SYSTEM)
5. CARTON NO. → `CARTON_NO` (SYSTEM)
6. `FOOT_MADE_IN`
### 4.10 TPL_TENCATE — Tencate (per pallet)
File: `TEMPLATE TENCATE` (sudah mail-merge «PALLET») · Party: **Consignee/Manufacturer** · Grain: **PALLET** · Size: A5
1. Name Of Manufacturer → `MGT_SHIPPER` (STATIC)
2. Name Of Consignee → `CUST_PARTY_NAME` (STATIC)
3. Description Of Goods → `ITEM` (SYSTEM)
4. Color → `COLOR_CUST` (MASTER)
5. Lot Number → `LOT_NO` (SYSTEM)
6. PALLET NO → `PALLET_NO` (SYSTEM)
7. `FOOT_MADE_IN`
### 4.11 TPL_MARIONETTE_A4 — Marionette / GTA (A4)
File: `TEMPLATE MARIONETTE A4` · Grain: **PALLET** · Size: **A4**
1. Product → `ITEM` (SYSTEM)
2. Description → static "100% POLYESTER TEXTURED YARN" (STATIC)
3. Merge → `LOT_NO` (SYSTEM)
4. Tube Color → `TUBE_COLOR` (MASTER?)
5. Marionette Lot # → `CUST_ITEM_CODE` (MASTER) *(lot ref versi customer)*
6. Number of Bobbins Per Pallet → `BOBBIN_QTY` (SYSTEM)
7. Customer P.O # → `CUST_PO_NO` (MANUAL*)
8. GTA Part Number → `CUST_MATERIAL_NO` (MASTER, static)
### 4.12 TPL_BEKAERT_ARGENTINA — Bekaert Argentina (customs-heavy)
File: `BEKAERT ARGENTINA 195` · Grain: — · Size: A5
1. (party) → `CUST_PARTY_NAME` (STATIC)
2. CUIT → `CUST_TAX_ID` (STATIC)
3. static "100 % POLYESTER YARN" → `CUST_SPEC_TEXT`
4. Article No → `CUST_ITEM_CODE` (MASTER)
5. (item) → `ITEM` (SYSTEM)
6. COL. → `COLOR_CUST` (MASTER, static)
7. BD NO. → `CUST_MATERIAL_NO` (MASTER)
8. BD PO. NO. → `CUST_PO_NO` (MANUAL*)
9. Tex → `CUST_SPEC_TEXT` (STATIC per item)
10. MGT + TAX ID → `MGT_SHIPPER` + `MGT_TAX_ID` (STATIC)
11. `FOOT_MADE_IN`
### 4.13 TPL_SCOBIE — Scobie & Junor (contract)
File: `SCOBIE JUNOR 406 PER PALLET` · Party: **BUYER** · Grain: **PALLET** · Size: A5
1. BUYER → `CUST_PARTY_NAME` (STATIC)
2. CONTRACT NO. → `CONTRACT_NO` (SYSTEM*)
3. PO NO. → `CUST_PO_NO` (MANUAL*)
4. ITEM → `ITEM` (SYSTEM)
5. COLOR → `COLOR_CUST` (MASTER)
6. CUSTOMER ITEM CODE → `CUST_ITEM_CODE` (MASTER)
7. `BLK_PERUNIT_PALLET` (SYSTEM)
8. `FOOT_MADE_IN`
### 4.14 TPL_BOXH — carton label warna-MGT
File: `BOX H` · Grain: **CARTON** · Size: A5
1. ITEM + `COLOR_MGT` inline → `ITEM` (SYSTEM) + `COLOR_MGT` (SYSTEM)  *(mis. "( ORINOCO GY )")*
2. MADE IN → `ORIGIN` (STATIC)
3. MERGE/Lot no. → `LOT_NO` (SYSTEM)
4. CARTON # → `CARTON_NO` (SYSTEM)
5. GW + NW satu baris → `GROSS_WT`, `NET_WT` (SYSTEM) *(butuh line 2-kolom)*
### 4.15 Stub / party-only
File: `J-HUB`, `JACQUARD`, `KC TEX INC`, `TITAN`, `SKYTEX`, `LAVA ACY`
Baru berisi header party (kemungkinan prototipe). Perlakukan sebagai varian
`TPL_CONSIGNEE_ID` — tambahkan `ITEM` + `BLK_PERUNIT_*` sesuai grain saat
customer-nya aktif. (SKYTEX item `ITY …`, LAVA item `ACY …` sudah tercatat.)
 
---

## 5. Catatan layout yang perlu didukung engine

- **Line 2-kolom** (dua field satu baris): `BOX H` (GW…NW), `194 AM`/`MM CORP` (WEIGHT → Net/Gross bertingkat). → `SMTL` perlu opsi `col_span`/`sub_indent`.
- **Product multi-baris**: ESC335, Marionette (spesifikasi panjang). → line boleh wrap.
- **Party > 1**: Bekaert Brasil (NOTIFY+EXPORTER), Bekaert Canada (BUYER+NOTIFY).
- **Ukuran**: mayoritas A5; Marionette A4. Bikin `SMT_PAPER_SIZE` per template.
- **Skala besar**: 194 AM = 704 carton → generate wajib batch/stream.
- **Tidak ada logo** di 30 sampel → slot gambar ditunda (YAGNI).
---

## 6. Yang perlu dikonfirmasi (biar mapping final)

1. **`CUST_PO_NO`** — tersimpan di SO/SSC? (SYSTEM) atau selalu diketik? (MANUAL)
2. **`LC_NO` & `INVOICE_NO`** — INVOICE sudah di query (`SOHM_INVH_NO`). L/C dari
   modul export/LC — apa join key-nya ke SSC?
3. **`CONTRACT_NO`** (ESC) — apakah sama dengan nomor SO/ESC yang sudah ada?
4. **`COLOR`** — kapan pakai nama warna MGT (`COLOR_MGT`, dari shade scan) vs nama
   customer (`COLOR_CUST`, dari mapping)? Perlu aturan per customer.
5. **`CUST_MATERIAL_NO` + `COLOR_CUST`** — sudah ada master mapping di suatu tempat,
   atau `SHIP_MARK_CUST_MAP` dibangun dari nol (learn-as-you-go)?
6. **`TUBE_COLOR`, `BOBBIN_QTY`** — `ss_no_bobbin` untuk bobbin sudah ada; tube color
   ada di data box/scan atau master?
---

*Next: setelah konfirmasi bagian 6, finalisasi DDL `SHIP_MARK_*` + isi seed
template dari katalog ini, lalu Claude Code bisa generate module-nya.*
 
