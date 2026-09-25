# Data Mapping Spec — Modul Integrasi ERP (v0.3.1)

Pendamping `PRD_ERP_COST_INTEGRATION.md` v0.3.
- Nama kolom ERP diambil dari ALTHARADEV (Oracle 11.2.0.4).
- Nama tabel/kolom goapps yang baru adalah usulan (migration 000392+, lihat `GOAPPS_MODIFICATIONS.md`).
- v0.2 ada di `archive_v0.2/`.

Perubahan dari v0.2:
- Aturan jadi master goapps (§2.2, seed `goapps_000392_erp_rules_seed.sql`, tanpa ETL).
- Mapping item+shade wajib, tanpa fallback `cpm_flex_01` (§1).
- Produk tanpa grade = AX.
- Ada tabel duplikat Oracle `CST_GOAPPS_STD_COST` beserta view kompatibel legacy (§5).
- `FG_CONVER_COST1/2/4/5` ikut dibawa (§4, §5.2).

Revisi 0.3.1:
- MB dihitung di goapps dan ikut alur yang sama (§6). `VALUATE_ADJ` mencakup `MBINVADJ`/`MBINVADJRP`.
- Link item ERP + shade dibuat Costing di aplikasi (field wajib), tanpa backfill `cpm_flex_01` (§1, §2.3).

## 1. Kunci dan granularitas

| Konsep | goapps | ERP |
|---|---|---|
| Produk costing | `cost_product_master.cpm_product_sys_id`. Grade `cpm_grade_code`, dengan NULL/'' diperlakukan sebagai `AX`. | — |
| **Link ERP ↔ goapps** | `cpm_erp_item_code` + `cpm_erp_grade_code_2`. Keduanya **wajib** di aplikasi costing, dan unik per (item, shade) di antara produk aktif grade AX. Produk tetap punya kode costing sendiri (`cpm_product_code`). | `OM_ITEM.ITEM_CODE` + `OM_GRADE_CODE_2.GRADE_CODE` = `ADJI_ITEM_CODE` + `ADJI_GRADE_CODE_2` |
| Kunci coverage | `(cpm_erp_item_code, cpm_erp_grade_code_2)` | `(ADJI_ITEM_CODE, ADJI_GRADE_CODE_2)` |
| Kunci valuasi | `cst_erp_std_cost (batch, item, grade, shade)` | `(ADJI_ITEM_CODE, ADJI_GRADE_CODE_1, ADJI_GRADE_CODE_2)`, dan `CST_GOAPPS_STD_COST (GSC_BATCH_ID, item, grade, shade)` |
| Periode | `YYYYMM` | `TRUNC(ADJH_DT)` dalam bulan |
| Cost AX | `cst_product_cost`: `cpc_calculation_type='ACTUAL'`, `cpc_period=P`, `cpc_status='APPROVED'`, `cpc_currency_code='USD'` | — |

Tidak ada fallback `split_part(cpm_flex_01,' ',1)` / `cpm_shade_code` seperti di v0.2, dan tidak ada backfill link dari kolom itu.

Alur resolusi pasangan ETL (item ERP, shade) yang tidak ditemukan di produk cost (`NO_MAPPING`):

| Kondisi | Tindakan Costing | Hasil |
|---|---|---|
| Produk belum ada | Buat produk cost baru (grade AX), isi `cpm_erp_item_code` + `cpm_erp_grade_code_2` + FG type, hitung ACTUAL, approve | `OK` setelah approve |
| Produk sudah ada, kode item ERP belum didefinisikan | Definisikan `cpm_erp_item_code` + `cpm_erp_grade_code_2` di produk itu (`LinkErp`) | `OK` bila cost ACTUAL P sudah APPROVED; bila belum → `NO_COST`/`NOT_APPROVED` |

Setelah semua pasangan ETL `OK`, cost AX tersedia untuk semua item+shade dan menjadi acuan derivasi grade non-AX (§4).

## 2. Data ERP → goapps

### 2.1 Demand ADJ → `cst_erp_adj_demand` (ETL per run)

```sql
-- Oracle (GOAPPS_IF), bind :P
SELECT h.ADJH_TXN_CODE, i.ADJI_ITEM_CODE, MAX(it.ITEM_NAME) item_name, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2,
       COUNT(*) item_count, SUM(i.ADJI_QTY_BU)/1000 qty_kg,
       MIN(i.ADJI_RATE) min_rate, MAX(i.ADJI_RATE) max_rate,
       SUM(CASE WHEN h.ADJH_APPR_STATUS = 3 THEN 1 ELSE 0 END) approved_items,
       SUM(CASE WHEN h.ADJH_POST_STATUS IS NOT NULL THEN 1 ELSE 0 END) posted_items
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
  LEFT JOIN OM_ITEM it ON it.ITEM_CODE = i.ADJI_ITEM_CODE
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
 GROUP BY h.ADJH_TXN_CODE, i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2;
```

| Kolom PG | Sumber |
|---|---|
| `ced_run_id` | run ETL (1 run = 1 snapshot) |
| `ced_period` | :P |
| `ced_txn_code` | `ADJH_TXN_CODE` |
| `ced_item_kind` | `INVADJ` → `YARN`, `MBINVADJ`/`MBINVADJRP` → `MB` |
| `ced_item_name` (baru) | `OM_ITEM.ITEM_NAME` (join di ETL). Dipakai untuk form "buat produk baru" dan pencarian kandidat "link ke produk existing". |
| `ced_item_code`, `ced_grade_code`, `ced_shade_code` | `ADJI_ITEM_CODE`, `ADJI_GRADE_CODE_1`, `ADJI_GRADE_CODE_2` |
| `ced_item_count`, `ced_qty_kg` | agregat |
| `ced_erp_min_rate`, `ced_erp_max_rate` | rate saat ETL (rate awal WMS / batch sebelumnya) |
| `ced_approved_items`, `ced_posted_items` | `posted_items > 0` → periode ditolak (V-10) |

### 2.2 Master aturan — milik Finance, di goapps (OQ-C)

Tabel dibuat, lalu di-seed **sekali** oleh `goapps_000392_erp_rules_seed.sql`. Isi seed berasal dari ALTHARADEV 2026-09-25; bandingkan dengan PROD lewat recon §0 sebelum deploy. Setelah itu Finance memelihara aturan lewat UI goapps. Tidak ada ETL berkala, dan tabel ERP lama tidak lagi dibaca.

| Asal seed (ERP) | Tabel goapps | Mapping |
|---|---|---|
| `MGT_ITEM_COST_VAL_LOSS` (115 baris) | `cst_erp_valloss_rule` | `MICVL_TYPE` → `cevr_fg_type`; `MICVL_PROD_TYPE` → `cevr_prod_type`; `MICVL_GRADE_GROUP` → `cevr_grade_group`; `MICVL_BASIS` → `cevr_basis`; `MICVL_VAL_LOSS` → `cevr_val_loss`. Unik (fg_type, prod_type, grade_group). Basis: COST 47, SPPTY 62, SPITY 3, SPBSD 3. |
| `IM_VS_STATIC_VALUE` `ITEMSELLPRIC` | `cst_erp_sell_price` | `VSSV_CODE` → `cesp_basis`; `VSSV_FIELD_01` → `cesp_price` (SPBSD 1,4 / SPITY 1,5 / SPPTY 1,3) |
| `OM_GRADE_CODE_1.GRADE_BL_SHORT_NAME` | `cost_erp_grade.ceg_grade_group` (kolom baru) | 22 grade. Sync `cost_erp_grade` dari ERP **tidak boleh** menimpa kolom ini. |

Replika existing yang tetap disinkron dari ERP (bukan aturan):
- `cost_erp_item` (OM_ITEM, dipakai untuk nama item dan validasi V-06);
- `cost_erp_shade` (OM_GRADE_CODE_2);
- `cost_erp_grade` (kode grade).

Setiap batch integrasi menyimpan **snapshot** aturan (`ceib_rule_snapshot` JSONB + `ceib_rule_hash`). Perhitungan ulang periode lama memakai snapshot tersebut.

### 2.3 Atribut ERP produk (master produk goapps, OQ-D)

| Atribut | Kolom PG | Isi awal | Setelahnya |
|---|---|---|---|
| Kode item ERP | `cpm_erp_item_code` (sudah ada, wajib di aplikasi) | Kolom existing. Bila kosong: diisi Costing lewat coverage ("link ke produk existing"). | Wajib |
| Shade ERP | `cpm_erp_grade_code_2` (sudah ada, wajib di aplikasi) | Kolom existing. Idem. | Wajib |
| Grade | `cpm_grade_code` (`NOT NULL DEFAULT 'AX'`) | Sudah terisi | Produk integrasi = AX |
| FG type (`Type 1..13`) | `cpm_erp_fg_type` (baru) | `OT_STD_COST_PRODUCTS_MGT.FG_TYPE` baris AX per item+shade | Wajib (V-08) |
| Chips item | `cpm_erp_chp_item_code` (baru) | `FG_CHP_ITEM_CODE` baris AX | Opsional. Dipakai `FUNC_CHIP_WAC_MGT` / flex 04. |
| MB item | `cpm_erp_ms_batch_item` (baru) | `FG_MS_BATCH_ITEM` baris AX | Opsional |
| Item type, prod/hari | `cpm_erp_item_type`, `cpm_erp_prd_per_day` (baru) | `FG_ITEM_TYPE`, `FG_PRD_PER_DAY` baris AX | Opsional. Hanya dibawa ke tabel Oracle agar kompatibel legacy. |

Backfill atribut (FG type, chips, MB item, item type, prod/hari) adalah satu-satunya saat `OT_STD_COST_PRODUCTS_MGT` dibaca oleh goapps. Backfill ini hanya mengisi kolom kosong pada produk yang **sudah** ter-link item ERP + shade; link-nya sendiri tidak di-backfill.

## 3. Komponen AX dari goapps

| Komponen (nama ERP) | Nilai | Catatan |
|---|---|---|
| `chp_cost` | `cpc_total_rm_cost` | per kg produk jadi |
| `chp_con_kg` | `1` | sama dengan legacy |
| `conv` (`AX_CONV`) | `cpc_cost_per_unit − cpc_total_rm_cost` | Residu, supaya `chp + conv = total` |
| `ax_cost` | `ROUND(chp_cost × 1 + conv, 5)` = `ROUND(cpc_cost_per_unit, 5)` | |
| `conv_tier[1,2,4,5]` | T-4 (PRD §13). Sementara `= conv`. | Hanya untuk laporan margin |
| `chp_item`, `fg_type`, `ms_batch` | `cpm_erp_*` | §2.3 |

## 4. Derivasi grade (port `STD_FG_VALUE_INSERT` + `ODBTRG_FG_STD_COST`)

Input per kombinasi demand `(item, grade, shade)`, dengan AX `(item, shade)` dari §3:

```
prod_type   = CASE LEFT(item,3) WHEN 'POY' THEN 'POY' WHEN 'ITY' THEN 'ITY' ELSE 'PTY' END
grade_group = cost_erp_grade.ceg_grade_group WHERE ceg_grade_code = grade

IF grade = 'AX' OR (item_kind = 'MB' AND grade = 'A'):   -- MB: grade A ERP = grade AX goapps (§6)
    basis='COST'; val_loss=0; selling=0
    conv = ROUND(ax_conv,5)
    std  = ROUND(chp_cost*chp_con_kg + conv, 5)
ELSE:
    rule = cst_erp_valloss_rule(fg_type, prod_type, grade_group) aktif
    IF grade_group NULL or rule not found → status NO_GRADE_GROUP / NO_RULE (V-08, blok push)
    basis=rule.basis; val_loss=rule.val_loss
    selling = cst_erp_sell_price(basis).price  (wajib ada untuk basis non-COST)
    IF basis = 'COST':
        conv = ROUND(ax_conv − val_loss, 5)
        std  = ROUND(chp_cost*chp_con_kg + conv, 5)
    ELSE:
        conv = 0
        std  = ROUND(selling − val_loss, 5)

ax_cost  = ROUND(chp_cost*chp_con_kg + ROUND(ax_conv,5), 5)
prod_vl  = ROUND(std − ax_cost, 5)

-- kolom laporan margin (sama pola legacy STD_FG_VALUE_INSERT)
conv_n   = basis='COST' ? ROUND(NVL(conv_tier_n − val_loss, 0), 5) : 0      -- n = 1,2,4,5
```

Catatan:
- Basis non-COST tidak bergantung pada cost goapps, tetapi tetap butuh AX untuk `ax_cost` dan `prod_vl` (flex 08/09/11). Kombinasi tanpa AX goapps = `NO_AX`, dan wajib dicover dengan membuat produk AX (OQ-F).
- Pembulatan mengikuti Oracle `ROUND` (half away from zero). Di Go pakai `shopspring/decimal` `Round(5)`.
- Rumus sudah diverifikasi terhadap std ERP DEV: 9.602/9.602 cocok (recon §3). Karena itu uji penerimaan tinggal menghitung manual **cost AX** sampel dan menurunkannya (PRD §11).

## 5. goapps → Oracle

### 5.1 Push: `cst_erp_std_cost` → `MGTDAT.CST_GOAPPS_STD_COST` (duplikat)

Satu baris per `(batch, item, grade, shade)` hasil derivasi (status `OK`). Header batch masuk ke `CST_GOAPPS_STD_BATCH`. Tabel hanya ditulis `GOAPPS_IF` (D-1).

| Kolom Oracle | goapps | Setara legacy `OT_STD_COST_PRODUCTS_MGT` |
|---|---|---|
| `GSC_BATCH_ID`, `GSC_PERIOD` | `cesc_batch_id`, `cesc_period` | — |
| `GSC_ITEM_CODE` / `GSC_GRADE_CODE` / `GSC_SHADE_CODE` | item / grade / shade | `FG_ITEM_CODE` / `FG_ITEM_GRADE` / `FG_ITEM_SHADE` |
| `GSC_ITEM_NAME`, `GSC_SHADE_NAME` | `cost_erp_item` / `cost_erp_shade` | nama item/shade |
| `GSC_SOURCE` | `GOAPPS_AX` / `GOAPPS_DERIVED` | — |
| `GSC_STD_COST` | `std` | `FG_COST_PER_KG` |
| `GSC_CONV_COST` | `conv` | `FG_CONVER_COST` |
| `GSC_CONV_COST1/2/4/5` | `conv_n` | `FG_CONVER_COST1/2/4/5` |
| `GSC_CHP_ITEM_CODE`, `GSC_CHP_CON_KG`, `GSC_CHP_COST` | chips | `FG_CHP_ITEM_CODE`, `FG_CHP_CON_KG`, `FG_CHP_COST` |
| `GSC_FG_TYPE`, `GSC_BASIS` | | `FG_TYPE`, `FG_BASIS` |
| `GSC_SELLING_PRICE`, `GSC_AX_COST`, `GSC_AX_CONV_COST`, `GSC_VALUE_LOSS`, `GSC_PROD_VAL_LOSS` | | kolom legacy padanannya (`FG_PROD_VALUE_LOSS`, …) |
| `GSC_MS_BATCH_ITEM`, `GSC_ITEM_TYPE`, `GSC_PRD_PER_DAY` | `cpm_erp_*` | `FG_MS_BATCH_ITEM`, `FG_ITEM_TYPE`, `FG_PRD_PER_DAY` |
| `GSC_AX_COST_SYS_ID`, `GSC_AX_COST_VERSION` | `cst_product_cost` sumber | — (audit) |

Konsistensi: jumlah baris, `SUM(GSC_STD_COST)`, dan checksum batch Oracle harus sama dengan goapps (PRD §11.4, recon §4a).

### 5.2 View kompatibel `MGTDAT.V_GOAPPS_STD_COST_CUR`

View berisi baris dari batch terbaru berstatus `VALUATED|APPROVED|LOCKED`, per (item, grade, shade), sehingga kombinasi lama ikut carry-forward. Nama kolom mengikuti tabel legacy supaya repoint cukup dengan mengganti nama objek:
- `FG_ITEM_CODE`, `FG_ITEM_GRADE`, `FG_ITEM_SHADE`
- `FG_COST_PER_KG`
- `FG_CONVER_COST`
- `FG_CONVER_COST1/2/4/5` = `NVL(GSC_CONV_COSTn, GSC_CONV_COST)`
- `FG_CHP_ITEM_CODE`, `FG_CHP_CON_KG`, `FG_CHP_COST`
- `FG_TYPE`, `FG_BASIS`
- `FG_MS_BATCH_ITEM`, `FG_ITEM_TYPE`, `FG_PRD_PER_DAY`
- `FG_PROD_VALUE_LOSS`
- ditambah `SOURCE`, `PERIOD`, `BATCH_ID`

Untuk laporan saja, baris `OT_STD_COST_PRODUCTS_MGT` yang **tidak** ada di goapps ditambahkan dengan `SOURCE='LEGACY_FROZEN'` (UNION ALL … NOT EXISTS). Baris ini hanya produk lama yang belum pernah di-push goapps. `PKG_GOAPPS_ADJ.VALUATE_ADJ` **tidak** memakai view ini; package membaca `CST_GOAPPS_STD_COST` batch-nya sendiri.

| Objek yang di-repoint | Kolom yang dipakai |
|---|---|
| `O_DGET_FG_ITEM_RATE_MGT` | `FG_COST_PER_KG` by item/grade/shade. Mode 'E' raise 220079 bila tidak ada. |
| `FUNC_CHIP_WAC_MGT` (cursor C1) | `FG_CONVER_COST`, `FG_CHP_ITEM_CODE`, `FG_CHP_CON_KG`, `TO_NUMBER(NVL(FG_MS_BATCH_ITEM,0))` |
| `VIEW_MARGIN_REPORT*` | `FG_CHP_CON_KG`, `FG_CONVER_COST`, `FG_CONVER_COST1/2/4/5` (÷100, tier qty SO ≤1499 / 1500–2999 / 6000–11999 / ≥12000), join `soi_item_code = FG_ITEM_CODE(+)` + grade/shade |
| `PRODUCTS_LIST_COST` | kunci item/grade/shade |

**Satuan tier.** Laporan margin menghitung `FG_CONVER_COSTn/100`. Sebelum repoint, cek dulu satuan `FG_CONVER_COST1..5` di tabel legacy dibanding `FG_CONVER_COST` (recon §10). Isi `GSC_CONV_COSTn` harus memakai satuan yang sama dengan legacy.

### 5.3 `CST_GOAPPS_STD_COST` → `OT_ADJ_ITEM` (`PKG_GOAPPS_ADJ.VALUATE_ADJ`)

| Kolom ADJ | Nilai | Format |
|---|---|---|
| `ADJI_ITEM_DESC` | `OM_ITEM.ITEM_NAME` | |
| `ADJI_RATE` | `GSC_STD_COST` | 5 desimal |
| `ADJI_VAL` | `ROUND(ADJI_QTY_BU/1000 × rate, 7)` | QTY_BU gram |
| `ADJI_FLEX_01` | `GSC_CONV_COST` | `FM990D00000` |
| `ADJI_FLEX_02` | `GSC_CHP_CON_KG` | idem |
| `ADJI_FLEX_03` | `GSC_CHP_COST` | idem |
| `ADJI_FLEX_04` | `GSC_CHP_ITEM_CODE` | |
| `ADJI_FLEX_05` | `GSC_FG_TYPE` | |
| `ADJI_FLEX_06` | `GSC_BASIS` | |
| `ADJI_FLEX_07` | `GSC_SELLING_PRICE` | `FM990D00000` |
| `ADJI_FLEX_08` | `GSC_AX_COST` | idem |
| `ADJI_FLEX_09` | `GSC_AX_CONV_COST` | idem |
| `ADJI_FLEX_10` | `GSC_VALUE_LOSS` | idem |
| `ADJI_FLEX_11` | `GSC_PROD_VAL_LOSS` | idem (boleh negatif) |
| `ADJI_FLEX_12` | `GSC_MS_BATCH_ITEM` | |
| `ADJI_FLEX_13` | batch id | D-6 (OQ-E disetujui) |
| `ADJI_FLEX_14` | `GSC_SOURCE` | D-6 |

Aturan:
- Filter ADJ: txn `INVADJ`, `MBINVADJ`, `MBINVADJRP`, dalam P, `APPR_STATUS != 3`, `POST_STATUS IS NULL`.
- ADJ tanpa baris std **tidak** di-reset. Baris itu dilaporkan `NOT_COVERED`.
- Update ADJ memicu `ODBTRG067`, yang menjalankan proc stok dan audit flex bila head ada di `OT_ADJ_HEAD_HIST`. Perilaku ini sama dengan update legacy langkah 7.
- `FM990D00000` hanya muat sampai 999,99999 (V-03).

## 6. Masterbatch (goapps, OQ-5)

Cost MB sudah dihitung di goapps. `MBINVADJ`/`MBINVADJRP` ikut alur yang sama dengan yarn: ETL → coverage → std → push → `VALUATE_ADJ` → rekon.

Pola ADJ MB (DEV, 2026):

| Txn | Grade 1 | Shade | Item | Baris |
|---|---|---|---|---|
| `MBINVADJ` | `A` | `NL` | 467 CMB | 1.310 |
| `MBINVADJ` | `A` | `RS` | 1 CMB | 1 |
| `MBINVADJRP` | `A` | `NL` | 318 CMB | 693 |

Mapping:
- Produk cost MB goapps: tipe produk `MB` (`cpt_type_id = 29`, `cpt_type_code = 'MB'`, "Master Batch"), kode costing (`cpm_product_code`) berprefix `CSTMB`, grade AX (dikonfirmasi Costing).
- ACTUAL MB dihitung per item. Satu produk `CSTMB` di-link ke satu item ERP `CMB…` + shade lewat `cpm_erp_item_code` + `cpm_erp_grade_code_2`, sama seperti yarn.
- MB hanya punya satu grade ERP, yaitu `A`. Grade `A` ERP pada MB = grade AX goapps (dikonfirmasi Costing): `std = cost MB ACTUAL APPROVED USD`, tanpa derivasi value loss.
- V-12: demand MB (`CMB…`) hanya boleh terpetakan ke produk tipe `MB`/`CSTMB`, dan demand yarn tidak boleh terpetakan ke produk tipe `MB`.
- Komponen: `conv = cost − rm`, `chp`/`kg` dari komponen RM cost MB (sama dengan §3). `GSC_BASIS='COST'`, `GSC_PROD_VAL_LOSS=0`.
- `GSC_SOURCE='GOAPPS_MB'`, `cesc_item_kind='MB'`.
- Bila item MB muncul dengan grade selain `A`, status `NO_RULE` (V-08) sampai Finance menambah aturan.
- MB tidak termasuk prefix V-07 (POY/PTY/ACY/ITY/MMK/TTY/HOY), jadi tidak kena batas rate 20.
- `mst_mb_head` (000388) adalah master melange batch untuk param `MB_RATE_MKT` yarn, bukan produk cost MB.

## 7. Rekon (ERP → goapps)

Setelah `VALUATE_ADJ`, goapps membaca per kombinasi: `COUNT(DISTINCT ADJI_RATE)`, `MAX(ADJI_RATE)`, `SUM(QTY_BU)/1000`, `SUM(ADJI_VAL)`, `MAX(ADJI_FLEX_13)`. Hasilnya disimpan di `cesc_erp_*` / `cesc_recon_status`:
- `MATCH`: 1 rate, sama dengan `std`, dan flex_13 = batch.
- `DIFF`: selain itu.
- `NOT_IN_ADJ`: kombinasi ada di std tapi tidak ada di ADJ.

## 8. Sizing (DEV, Agustus 2026)

- INVADJ 7.430 item; 961 item+shade; 1.933 item+grade+shade.
- Non-AX: COST 661, SPPTY 650, NO_AX 128, SPBSD 3, SPITY 3.
- Aturan seed: MICVL 115, ITEMSELLPRIC 3, grade group 22.
- Std ERP legacy: 16.236 baris, yang menjadi sumber backfill atribut FG_TYPE/chips/MB item sekaligus baris `LEGACY_FROZEN` di view.
- MB 2026: `MBINVADJ` 468 item, `MBINVADJRP` 318 item, semuanya grade `A` (§6). Agustus 2026: 240 item.
