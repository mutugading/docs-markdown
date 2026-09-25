# PRD — Modul Integrasi Cost goapps → ERP (Valuasi Stok Bulanan)

| | |
|---|---|
| Versi | **0.3.1** (2026-09-25). Revisi v0.3: MB ikut goapps, alur coverage ETL → produk cost. Menggantikan v0.2 (`archive_v0.2/`) dan v0.1 (`archive_v0.1/`). |
| Status | Draft untuk review Finance, Tim ERP, IT. Semua open question sudah dijawab (§13). |
| Pendamping | `DATA_MAPPING_SPEC.md`, `GOAPPS_MODIFICATIONS.md`, `goapps_000392_erp_rules_seed.sql`, `ddl_proposal_mgtdat_interface.sql`, `recon_queries.sql`, `RUNBOOK_CUTOVER.md` |

**Perubahan utama dari v0.2** (mengikuti jawaban OQ):

- **Tabel std duplikat di Oracle** (OQ-A): `MGTDAT.CST_GOAPPS_STD_COST`.
  - Isinya salinan std cost goapps per periode dan batch, supaya costing bisa di-query di Oracle.
  - Tabel ini hanya diubah oleh goapps (single source).
  - Tabel ini menggantikan `CST_GOAPPS_ADJ_RATE`: `VALUATE_ADJ` membaca rate dari sini.
- **`OT_STD_COST_PRODUCTS_MGT` dibiarkan usang** (OQ-B = c).
  - Objek yang membacanya di-repoint ke view `V_GOAPPS_STD_COST_CUR`.
  - Mirror dihapus dari desain.
- **Aturan valuasi adalah master data goapps milik Finance** (OQ-C).
  - Isinya MICVL, harga jual, dan grade group.
  - Dibuat lewat DDL dan seed langsung dari `MGT_ITEM_COST_VAL_LOSS`, tanpa ETL.
- **FG Type** dikelola di master produk goapps (OQ-D). **FLEX_13/14** disetujui (OQ-E).
- **Tanpa fallback ke std ERP lama** (OQ-F).
  - Kombinasi tanpa cost wajib dibuat produknya di costing dengan grade AX.
  - Produk tanpa grade dianggap AX.
- **`CHP_WAC_UPD` tidak relevan** (OQ-2). Harga chips/RM punya mekanisme sendiri di costing.
- **Kriteria penerimaan diganti.**
  - Perbandingan gap ACTUAL vs legacy (−5,13%) tidak dipakai, karena rumus sudah dikoreksi bersama user.
  - Gantinya: user menghitung manual sampel produk, dan sistem harus sama.
  - Yang harus terhubung legacy ↔ baru hanyalah **kode item ERP**, karena kode itu menentukan valuasi FG di ERP.

**Revisi 0.3.1** (koreksi user atas OQ-5):

- **Cost MB sudah ada di goapps.** MB (`MBINVADJ`/`MBINVADJRP`) diproses lewat alur yang sama dengan yarn: coverage, derivasi, push, valuasi. Tidak ada lagi jalur MB legacy.
- **Produk MB** = produk cost MB goapps = tipe produk `MB` (`cost_product_type.cpt_type_id = 29`, "Master Batch"), kode costing berprefix `CSTMB`. ACTUAL MB dihitung per item: 1 produk `CSTMB` di-link ke 1 item ERP `CMB…` (+ shade). Grade `A` ERP pada MB = grade AX goapps, sama seperti yarn.
- **Alur coverage** (§5): ETL membaca dari `OT_ADJ_ITEM` kode item ERP, nama item, grade code 1, dan grade code 2 (shade). Pasangan item+shade dicari di produk cost goapps lewat `cpm_erp_item_code` + `cpm_erp_grade_code_2`, dua field yang sudah wajib di aplikasi costing. Bila pasangan itu tidak ditemukan, ada dua tindakan:
  - produk belum ada: buat produk cost baru;
  - produk sudah ada tetapi kode item ERP-nya belum didefinisikan: isi kode item ERP + shade di produk itu.
- Setelah semua item+shade ETL ada di cost, grade AX tersedia dan menjadi acuan menghitung grade non-AX dengan aturan integrasi.
- Backfill link kode item dari `cpm_flex_01` dihapus. Link dibuat Costing lewat aplikasi.

## 1. Latar belakang

Valuasi stok FG bulanan di ERP (Oracle MGTDAT) saat ini dijalankan manual lewat script `costing process in ERP and costing legacy.sql`:

1. Legacy costing (`mgtapps.CST_YARN_CALCULATION_CUR`) menghasilkan cost AX.
2. Cost AX di-insert ke `OT_STD_COST_PRODUCTS_MGT`.
3. `CHP_WAC_UPD` memperbarui harga chips.
4. `STD_FG_COST_UPD_PRD` dan `STD_FG_VALUE_INSERT` membuat baris grade turunan.
5. Rate, value, dan `FLEX_01..12` di `OT_ADJ_ITEM` diisi.
6. ADJ di-approve lalu diposting.

goapps (service `finance`, Postgres) sudah menghitung cost ACTUAL per produk, tetapi hanya untuk grade **AX** (OQ-4). Padahal ERP butuh rate untuk semua grade yang muncul di ADJ.

## 2. Tujuan dan non-tujuan

**Tujuan**
- G-1: Semua cost valuasi, baik AX maupun grade turunan, dihitung dan dikelola di goapps. ERP tidak menghitung apa pun.
- G-2: Sebelum menulis ke ERP, modul menunjukkan **coverage**, yaitu kombinasi ADJ yang belum punya cost.
- G-3: Grade turunan dihitung di goapps dari cost AX dengan aturan milik Finance, yang dikelola di goapps.
- G-4: Hasil std per periode tersalin ke Oracle (`CST_GOAPPS_STD_COST`), sehingga ERP (valuasi ADJ, WMS, laporan margin) dan query Oracle memakai angka yang sama.
- G-5: Satu-satunya perubahan pada tabel inti ERP adalah valuasi `OT_ADJ_ITEM`. Perubahan ini idempoten dan ditolak bila periode sudah posted.
- G-6: Jejak audit lengkap: versi cost, snapshot aturan, batch, pelaku, dan nilai ADJ sebelum/sesudah.

**Non-tujuan**
- Mengubah engine kalkulasi costing (`costcalc`).
- Menghitung cost grade non-AX di engine. Grade ini tetap diturunkan dari AX.
- Posting ADJ otomatis.
- Harga chips WAC / `CHP_WAC_UPD` (OQ-2).
- Menyamakan angka ACTUAL goapps dengan legacy.

## 3. Aturan ERP yang harus dipertahankan

| Aturan | Sumber | Dampak |
|---|---|---|
| Rate ≤ 0 atau > 20 untuk prefix POY/PTY/ACY/ITY/MMK/TTY/HOY menimbulkan error 241441 saat approve INVADJ | trigger `ODBTRG_COST_VAL_MGT` | Validasi di goapps (V-07) dan di `VALUATE_ADJ` |
| `ADJI_VAL = ROUND(QTY_BU/1000 × RATE, 7)`, dengan QTY_BU dalam gram | legacy langkah 7 | Rumus sama |
| `FLEX_01..12` berisi komponen cost dengan format `FM990D00000` | legacy langkah 7 | String sama |
| Hanya ADJ dengan `APPR_STATUS != 3` dan `POST_STATUS IS NULL` yang diubah | legacy + freeze | `assert_not_posted` |
| Derivasi grade: basis COST → `ROUND(CHP×KG + ROUND(AX_CONV − loss,5),5)`; basis lain → `ROUND(SELLING − loss,5)` | `STD_FG_VALUE_INSERT` + `ODBTRG_FG_STD_COST` | Diport ke goapps (FR-4). Terverifikasi 9.602/9.602 di DEV. |

## 4. Volume (DEV, Agustus 2026)

- INVADJ: 7.430 item.
  - 961 kombinasi item+shade (level AX).
  - 1.933 kombinasi item+grade+shade.
- Kombinasi non-AX:

  | Resolusi | Kombinasi |
  |---|---|
  | COST | 661 |
  | SPPTY | 650 |
  | NO_AX | 128 |
  | SPBSD | 3 |
  | SPITY | 3 |

- Aturan: MICVL 115 baris dan ITEMSELLPRIC 3 baris.
- MB (`MBINVADJ`/`MBINVADJRP`): 240 item. Diproses goapps seperti yarn (OQ-5).
- `ADJI_FLEX_13..20` kosong di semua ADJ 2026.

## 5. Arsitektur to-be

```
 goapps (Postgres, finance)                                           ERP (Oracle MGTDAT)
 ┌─────────────────────────────┐
 │ Modul costing (existing)    │ cst_product_cost ACTUAL APPROVED USD (grade AX)
 │ Master produk: item ERP +   │ cost_product_master (cpm_erp_item_code, cpm_erp_grade_code_2,
 │ shade + FG type (wajib)     │                      cpm_erp_fg_type, chips, MB)
 │ Master aturan (Finance)     │ cst_erp_valloss_rule, cst_erp_sell_price, cost_erp_grade.group
 └──────────────┬──────────────┘
                │ baca
 ┌──────────────▼───────────────────────────────────────┐  ETL read   ┌──────────────────────────┐
 │ Modul Integrasi ERP (application/erpintegration)      │◄────────────│ OT_ADJ_HEAD/ITEM (demand)│
 │ 1. ETL demand ADJ periode → cst_erp_adj_demand        │             │ OM_ITEM, OM_GRADE_CODE_2 │
 │ 2. Coverage item+shade (YARN+MB) vs produk cost AX    │             └──────────────────────────┘
 │ 3. Derivasi grade → cst_erp_std_cost (+ snapshot)     │  push       ┌──────────────────────────┐
 │ 4. Validasi V-01..V-12                                │────────────►│ CST_GOAPPS_STD_BATCH     │
 │ 5. Push std → Oracle (duplikat, single source goapps) │             │ CST_GOAPPS_STD_COST      │
 │ 6. VALUATE_ADJ ───────────────────────────────────────┼────────────►│ → OT_ADJ_ITEM rate/val/  │
 │ 7. Rekon (baca balik)                                 │◄────────────│   flex (PKG_GOAPPS_ADJ)  │
 │ 8. Lock periode                                       │             │ V_GOAPPS_STD_COST_CUR ◄──┼─ WMS rate awal,
 └───────────────────────────────────────────────────────┘             └──────────────────────────┘  margin report
                                                           OT_STD_COST_PRODUCTS_MGT: dibekukan (tidak diubah)
```

Alur Finance (yarn dan MB sama):
1. Hitung ACTUAL lalu approve.
2. Di modul integrasi periode P: ETL demand. ETL membaca dari `OT_ADJ_ITEM` kode item ERP, nama item, grade code 1, dan grade code 2 (shade).
3. Coverage mencocokkan setiap pasangan (item ERP, shade) ke produk cost goapps lewat `cpm_erp_item_code` + `cpm_erp_grade_code_2`. Produk cost punya kode costing sendiri (`cpm_product_code`) dan grade AX.
4. Pasangan yang belum ditemukan (`NO_MAPPING`) diselesaikan Costing dengan salah satu dari dua cara:
   - **Produk belum ada:** buat produk cost baru (grade AX), isi kode item ERP + shade + FG type, hitung ACTUAL, lalu approve.
   - **Produk sudah ada, tetapi kode item ERP-nya belum didefinisikan:** isi kode item ERP + shade di produk itu (`LinkErp`). Bila cost ACTUAL-nya sudah approved, pasangan langsung `OK`.

   Setelah itu ulangi langkah 3.
5. Setelah semua pasangan ETL ada di cost (coverage 100%), cost AX tersedia untuk semua item+shade. Cost AX ini menjadi acuan untuk menghitung grade non-AX sesuai aturan integrasi (FR-4).
6. derive → validasi → push → valuasi ADJ → rekon → approve ADJ di ERP → posting → lock.

## 6. Keputusan desain

| ID | Keputusan | Dasar |
|---|---|---|
| D-1 | Std per periode dikelola di goapps (`cst_erp_std_cost`) dan **diduplikasi ke Oracle** `MGTDAT.CST_GOAPPS_STD_COST` per batch. Tabel Oracle hanya ditulis goapps (lewat user `GOAPPS_IF`). Tidak ada user/proses ERP yang boleh DML ke tabel ini. | OQ-A |
| D-2 | Coverage dicek pada **item ERP + shade (grade code 2)** terhadap cost AX. Produk dengan `cpm_grade_code` kosong dianggap AX. | OQ-4, OQ-F |
| D-3 | Aturan (value loss, harga jual, grade group) adalah master data goapps milik Finance. Seed dari `MGT_ITEM_COST_VAL_LOSS` / `ITEMSELLPRIC` / `OM_GRADE_CODE_1` sekali saat migration, tanpa ETL berkala. Snapshot aturan disimpan per batch. | OQ-C |
| D-4 | Unit **USD/kg**. Cost non-USD ditolak (V-09). | OQ-1 |
| D-5 | `PKG_GOAPPS_ADJ.VALUATE_ADJ` membaca rate dan komponen dari `CST_GOAPPS_STD_COST` batch terkait, lalu menilai `OT_ADJ_ITEM`. goapps tidak diberi UPDATE langsung ke `OT_ADJ_ITEM`. | OQ-A (satu environment Oracle) |
| D-6 | `ADJI_FLEX_13` = batch id goapps. `FLEX_14` = sumber (`GOAPPS_AX` / `GOAPPS_DERIVED`). | OQ-E |
| D-7 | MB dihitung di goapps dan diproses seperti yarn. `VALUATE_ADJ` mencakup `INVADJ`, `MBINVADJ`, `MBINVADJRP`. | OQ-5 (koreksi user) |
| D-8 | Approve ADJ tetap manual. `APPROVE_ADJ` bersifat opsional. | Kontrol Finance |
| D-9 | `OT_STD_COST_PRODUCTS_MGT` tidak diubah lagi (dibekukan). Objek yang membacanya di-repoint ke `V_GOAPPS_STD_COST_CUR` (§10). | OQ-B = c |
| D-10 | Tidak ada fallback ke std ERP lama untuk valuasi. Kombinasi tanpa cost AX goapps = error coverage. | OQ-F |
| D-11 | Kunci penghubung ERP ↔ goapps hanya **kode item ERP + shade** (`cpm_erp_item_code`, `cpm_erp_grade_code_2`). Keduanya sudah wajib di aplikasi costing, dan harus unik untuk produk aktif AX. Link dibuat Costing di aplikasi (buat produk baru atau definisikan di produk existing), bukan lewat backfill. | Arahan user |

## 7. Kebutuhan fungsional

- **FR-1 ETL demand.**
  - Tarik kombinasi `(txn, item, nama item, grade1, grade2/shade)` dari ADJ `INVADJ/MBINVADJ/MBINVADJRP` untuk periode P, beserta qty kg, rate saat ini, dan status approve/post.
  - Bisa diulang: setiap run adalah satu snapshot.
  - Ditolak bila ada head yang sudah posted.
- **FR-2 Master aturan (Finance).**
  - CRUD `cst_erp_valloss_rule`, `cst_erp_sell_price`, dan `cost_erp_grade.ceg_grade_group` dengan permission `finance.cost.erp_rule.*`.
  - Setiap perubahan tercatat di audit.
  - Batch menyimpan snapshot dan hash aturan. Perbedaan dengan snapshot periode lalu ditampilkan.
  - Sync `cost_erp_grade` dari ERP tidak boleh menimpa `ceg_grade_group`. Grade baru dari sync masuk dengan group kosong, lalu muncul sebagai tugas Finance (V-08).
- **FR-3 Coverage.**
  - Setiap item+shade demand (YARN dan MB) dicocokkan ke produk aktif grade AX, lewat `cpm_erp_item_code` + `cpm_erp_grade_code_2`, lalu ke cost ACTUAL APPROVED USD periode P.
  - Status: `OK`, `NO_MAPPING`, `NO_COST`, `NOT_APPROVED`, `INVALID`.
  - `NO_MAPPING` punya dua tindakan di UI:
    - **Buat produk cost baru.** Form terisi item, shade, dan nama item dari demand/`cost_erp_item`.
    - **Link ke produk existing.** Pilih produk aktif AX yang belum punya kode item ERP (kandidat dicari dari nama/shade), lalu isi `cpm_erp_item_code` + `cpm_erp_grade_code_2` (`LinkErp`).
  - Hasil bisa diunduh ke Excel.
- **FR-4 Derivasi grade** (Mapping §4).
  - Grade AX mengambil komponen dari cost AX.
  - Grade lain melalui lookup grade group, prod type, dan FG type, lalu aturan.
  - Hasilnya masuk `cst_erp_std_cost`, termasuk kolom yang dibutuhkan laporan margin dan WMS.
- **FR-5 Validasi.** V-01..V-12 (§8). Error memblokir push. Warning harus di-acknowledge.
- **FR-6 Push dan valuasi.**
  1. Insert header batch dan baris std ke Oracle (`CST_GOAPPS_STD_BATCH` / `CST_GOAPPS_STD_COST`).
  2. Panggil `PKG_GOAPPS_ADJ.VALUATE_ADJ(batch)`.
  3. Baca ringkasan.
  - Dry-run tersedia.
  - Re-push periode yang sama men-supersede batch lama. Bila data tidak berubah, hasilnya identik.
- **FR-7 Rekon.** Baca balik ADJ per kombinasi. Status `MATCH` / `DIFF` / `NOT_IN_ADJ`, ditambah total nilai.
- **FR-8 Lock.**
  - Setelah posting, batch dan periode ACTUAL di-lock (`cst_period_lock`), dan batch Oracle berstatus `LOCKED`.
  - `V_GOAPPS_STD_COST_CUR` hanya memakai batch `VALUATED`/`APPROVED`/`LOCKED`.
- **FR-9 Laporan.** Nilai valuasi per periode per grade, prod type, dan basis, dibandingkan dengan periode lalu.
- **FR-10 Link kode item ERP.**
  - Laporan kesiapan mapping: item+shade yang punya stok atau ADJ di ERP (juga std legacy aktif) tetapi belum punya produk goapps dengan `cpm_erp_item_code` + shade.
  - Produk aktif AX yang kode item ERP-nya belum didefinisikan (kandidat tindakan "link ke produk existing").
  - Produk goapps yang kode item ERP-nya tidak ada di `OM_ITEM`.
  - Lihat recon §9.

## 8. Validasi

| ID | Aturan | Level |
|---|---|---|
| V-01 | Cost AX > 0 | Error |
| V-02 | Rate hasil derivasi > 0 | Error |
| V-03 | `conv = cost − rm ≥ 0` untuk AX. Komponen berada dalam rentang `FM990D00000` (0..999,99999). | Error |
| V-04 | Item+shade dipetakan ke lebih dari 1 produk aktif (juga dijaga unique index) | Error |
| V-05 | Δ rate vs periode lalu > ±20% | Warning |
| V-06 | Item/shade ada di replika `cost_erp_item/shade` (OM_ITEM / OM_GRADE_CODE_2) | Error |
| V-07 | Rate ≤ 20 untuk prefix POY/PTY/ACY/ITY/MMK/TTY/HOY | Error |
| V-08 | Grade non-AX tanpa aturan / tanpa grade group / produk tanpa FG type / basis tanpa harga jual | Error |
| V-09 | Currency = USD | Error |
| V-10 | Periode ERP belum posted, dan snapshot demand ≤ N jam | Error |
| V-11 | Produk aktif tanpa `cpm_erp_item_code` atau `cpm_erp_grade_code_2` | Error (form) / Warning (laporan) |
| V-12 | Konsistensi tipe: item ERP `CMB…` hanya ke produk tipe `MB` (`CSTMB…`), item yarn tidak ke produk tipe `MB` | Error |

## 9. Kebutuhan non-fungsional

- Performa: ±2.000 kombinasi / ±8.000 item ADJ diproses end-to-end dalam < 5 menit.
- Idempoten dan resumable per langkah (pola `job_execution`).
- Keamanan: user Oracle `GOAPPS_IF` hanya punya:
  - SELECT pada sumber ETL dan objek referensi;
  - INSERT/UPDATE pada `CST_GOAPPS_STD_*`;
  - EXECUTE pada package.
  - Tidak ada DML langsung ke tabel inti ERP.
  - Hanya `GOAPPS_IF` yang boleh menulis `CST_GOAPPS_STD_*`, dijaga oleh trigger guard.
- Audit: versi cost, hash aturan, pelaku, waktu, ringkasan ERP, dan log nilai ADJ sebelum diubah.

## 10. Objek ERP yang membaca `OT_STD_COST_PRODUCTS_MGT` → repoint

Tabel lama dibekukan (D-9). Repoint dilakukan saat cutover (M-ERP-4), dan Langkahnya ada di `ddl_proposal_mgtdat_interface.sql` §7.

| Objek | Pemakaian | Tindakan |
|---|---|---|
| `O_DGET_FG_ITEM_RATE_MGT` (dipanggil `O_DINSERT_WMS_INV_ADJ` / `ODBTRG_WMS_INT`) | Rate awal ADJ dari WMS (`FG_COST_PER_KG`). Mode 'E' raise 220079. | Cursor diganti ke `V_GOAPPS_STD_COST_CUR` |
| `VIEW_MARGIN_REPORT`, `VIEW_MARGIN_REPORT_TODAY` (+ varian `_DEV*`) | `FG_CHP_CON_KG`, `FG_CONVER_COST`, `FG_CONVER_COST1/2/4/5`, `FG_MS_BATCH_ITEM` (outer join per item/grade/shade) | Ganti sumber join ke view. View mengisi `FG_CONVER_COST1/2/4/5` dari goapps; bila goapps belum punya tier, nilainya `= FG_CONVER_COST` (NVL). Tanpa NVL, "FG Cost" di laporan akan NULL. Sumber tier: T-4. |
| `FUNC_CHIP_WAC_MGT` | `FG_CONVER_COST`, `FG_CHP_ITEM_CODE`, `FG_CHP_CON_KG`, `FG_MS_BATCH_ITEM` | Cursor C1 diganti ke view. Fallback C2 ke `CST_YARN_CALCULATION_CUR` tetap ada. |
| `PRODUCTS_LIST_COST` (view: ADJ tanpa std) | Daftar gap | Diganti coverage goapps. Opsional repoint ke view. |
| `STD_FG_VALUE_INSERT`, `STD_FG_COST_UPD_PRD`, `CHP_WAC_UPD`, trigger `ODBTRG_FG_STD_COST`, `ODBTRG_ITEMCOSTVALLOSS_MGT`, `ODBTRG_SELLPRICECOST_MGT` | Proses legacy | Tidak dipanggil lagi untuk INVADJ maupun MB. Objek dibiarkan ada (tidak di-drop) sampai 1 periode setelah cutover sebagai jalur rollback. |

`V_GOAPPS_STD_COST_CUR` berisi baris terbaru per (item, grade, shade) dari batch valid. Kombinasi yang tidak diproduksi bulan ini tetap memakai std terakhirnya (carry-forward), sama dengan perilaku tabel lama.

Untuk **laporan margin dan WMS saja** (bukan valuasi), view juga menampilkan baris `OT_STD_COST_PRODUCTS_MGT` yang belum pernah ada di goapps, dengan `SOURCE='LEGACY_FROZEN'`. Tujuannya supaya produk lama yang belum pernah diproduksi ulang tidak hilang dari laporan. Valuasi ADJ tetap hanya memakai batch goapps (D-10).

## 11. Kriteria penerimaan

1. **Sign-off hitung manual sampel.**
   - Finance memilih sampel minimal 30 kombinasi:
     - POY, PTY, dan ITY;
     - grade AX dan turunan;
     - setiap basis COST, SPPTY, SPITY, SPBSD;
     - produk dengan MB dan tanpa MB;
     - item MB (`MBINVADJ`/`MBINVADJRP`);
     - produk baru dari coverage.
   - Finance menghitung manual cost AX (dari parameter costing) dan std turunan (dari aturan).
   - Sistem harus sama sampai 5 desimal untuk seluruh sampel.
   - Bila sampel lolos, produk lain dianggap mengikuti aturan yang sama.
2. **Link kode item ERP.**
   - 100% item+shade yang ada di demand ADJ periode parallel run terpetakan ke tepat satu produk goapps.
   - Tidak ada `cpm_erp_item_code` yang tidak ada di `OM_ITEM` (recon §9).
3. **Mekanik ERP.**
   - Coverage 100% sebelum push.
   - `adj_items_not_covered = 0`.
   - `val_inconsistent = 0`.
   - Σ nilai ADJ = Σ goapps.
   - 0 error 241441 saat approve.
4. **Duplikat Oracle konsisten.** `CST_GOAPPS_STD_COST` batch = `cst_erp_std_cost` batch: jumlah baris, Σ std, dan checksum sama.
5. **Idempoten.** Dua push berturut-turut dengan data sama menghasilkan ADJ identik.
6. Push ke periode posted ditolak (ORA-20901).
7. **Parallel run** 202609–202610.
   - Basis SPPTY/SPITY/SPBSD sama dengan legacy.
   - Basis COST/AX **tidak** dibandingkan dengan legacy, karena rumus sengaja dikoreksi. Yang dinilai adalah butir 1–6.

## 12. Risiko

| Risiko | Mitigasi |
|---|---|
| Mapping item ERP + shade belum lengkap atau salah, sehingga valuasi FG salah | Field wajib di aplikasi, V-04/V-11, unique index, coverage per periode, laporan link §9 |
| Aturan diubah Finance di tengah periode | Snapshot aturan per batch, diff vs periode lalu, audit |
| ADJ baru masuk setelah ETL | V-10, ETL ulang, dan laporan NOT_COVERED (§7 recon) |
| Repoint view margin/WMS mengubah angka laporan | Uji di DEV. Bandingkan output view lama vs baru untuk SO sampel sebelum cutover. |
| Duplikat Oracle diubah di luar goapps | Grant hanya ke `GOAPPS_IF` dan trigger guard pada tabel |
| Grade baru di ERP tanpa grade group | V-08 dan tugas Finance di UI aturan |
| Defect engine E-5..E-8 | Tidak memblokir secara formal, tetapi akan terlihat di sign-off sampel. Produk yang terdampak defect akan gagal sampel. |

## 13. Open questions

| ID | Pertanyaan | Jawaban |
|---|---|---|
| OQ-1 | Currency/unit | USD/kg |
| OQ-2 | `CHP_WAC_UPD` | Tidak relevan. Chips/RM punya mekanisme sendiri di costing. |
| OQ-4 | Grade yang dihitung | Hanya AX |
| OQ-5 | Cost MB | Sudah ada di goapps. MB diproses sama dengan yarn (D-7). Jawaban v0.3 "menyusul, sementara legacy" adalah asumsi lama dan sudah dikoreksi. Produk MB = tipe `MB` (`cpt_type_id` 29), kode `CSTMB…`, 1 `CSTMB` : 1 `CMB`; grade `A` ERP = AX goapps. |
| OQ-A | Mekanisme tulis | Langsung dari goapps ke tabel duplikat di Oracle (single source goapps). Rate ADJ membaca tabel itu (D-1, D-5). |
| OQ-B | `OT_STD_COST_PRODUCTS_MGT` | (c) dibiarkan usang, dan objek yang membacanya di-repoint (§10) |
| OQ-C | Pemilik aturan | Finance. DDL + seed di goapps, tanpa ETL (D-3). |
| OQ-D | FG Type | Di master produk goapps |
| OQ-E | FLEX_13/14 | Disetujui |
| OQ-F | NO_AX | Tanpa fallback. Wajib buat produk AX; produk tanpa grade = AX. |
| OQ-9 | Go-live | Parallel run 202609–202610 |

Sisa keputusan teknis untuk Tim ERP/DBA (bukan blocker desain):
- T-1: nama tablespace / grant.
- T-2: jadwal repoint view margin (bersamaan cutover atau H+1).
- T-3: apakah varian `VIEW_MARGIN_REPORT_*_DEV*` ikut di-repoint atau dihapus.
- T-4 (Finance + IT): **sumber `FG_CONVER_COST1/2/4/5`** (conv per tier qty SO untuk laporan margin).
  - Legacy menurunkannya dari conv tier di `CST_YARN_CALCULATION_CUR` dikurangi loss.
  - goapps saat ini hanya punya satu conv per produk.
  - Default sementara: semua tier = conv (NVL di view). Satuan dijaga sama dengan legacy, karena laporan membagi 100.
  - Ini tidak memengaruhi valuasi ADJ.
