# Runbook — Valuasi Stok Bulanan via Modul Integrasi ERP & Cutover (v0.3.1)

Pendamping `PRD_ERP_COST_INTEGRATION.md` v0.3.1.
- `§n` = bagian di `recon_queries.sql`.
- DDL/package ada di `ddl_proposal_mgtdat_interface.sql`.
- Migration goapps diuraikan di `GOAPPS_MODIFICATIONS.md`.
- v0.2 ada di `archive_v0.2/`.

Prinsip:
- Semua cost, baik AX maupun grade turunan, dan semua aturan valuasi ada di goapps.
- goapps menyalin std per periode ke `MGTDAT.CST_GOAPPS_STD_COST` (single source goapps). ERP menilai `OT_ADJ_ITEM` INVADJ, MBINVADJ, dan MBINVADJRP dari tabel itu.
- `OT_STD_COST_PRODUCTS_MGT` dibekukan. Pembacanya di-repoint ke `V_GOAPPS_STD_COST_CUR`.
- MB (item CMB) memakai alur yang sama dengan yarn. Cost MB sudah dihitung di goapps per item: 1 produk `CSTMB…` (tipe `MB`, id 29) ↔ 1 item `CMB…`. Grade ERP `A` = grade AX goapps.
- Link item ERP + shade dibuat Costing di aplikasi (field wajib), bukan backfill.

Perubahan dari v0.2:
- Tidak ada mirror (langkah 12 lama dihapus).
- Tidak ada ETL aturan.
- Tidak ada fallback NO_AX.
- Parallel run tidak lagi menilai gap COST/AX.
- Penerimaan memakai sign-off hitung manual sampel.
- Cutover mencakup repoint objek pembaca tabel std lama.

Perubahan v0.3.1:
- Jalur MB legacy dihapus (langkah 9 lama); langkah bulanan dinomori ulang.
- Prasyarat link: tidak ada backfill link dari `cpm_flex_01`; gap diselesaikan lewat coverage.
- Gap `NO_MAPPING` punya dua tindakan: buat produk baru atau link ke produk existing.

## 1. Peran

| Peran | Tanggung jawab |
|---|---|
| Costing (Finance) | Hitung dan approve ACTUAL (AX), selesaikan gap coverage (buat produk baru atau link item ERP ke produk existing), pelihara link item ERP + FG type, review std dan rekon, sign-off lock |
| Finance (pemilik aturan) | Pelihara aturan value loss, harga jual, dan grade group di goapps; hitung manual sampel |
| Finance Approver | Push ke ERP, approve ADJ |
| IT | Deploy goapps, jalankan backfill atribut, pantau job, rollback |
| Tim ERP / DBA | Deploy DDL/package/grant/trigger, repoint objek, posting ADJ |

## 2. Prasyarat (sekali, sebelum parallel run)

1. **ERP (ALTHARADEV).**
   - Deploy M-ERP-1..3:
     - `CST_GOAPPS_STD_BATCH/COST`, trigger guard, dan `CST_GOAPPS_ADJ_LOG`;
     - 3 view dan `PKG_GOAPPS_ADJ`;
     - user `GOAPPS_IF` dan grant.
   - §0: semua objek `VALID`, trigger `ENABLED`, struktur DEV = PROD.
   - Uji guard: insert dari user lain harus ditolak ORA-20910, dan update baris std harus ditolak ORA-20911.
2. **goapps.**
   - Migration 000392 (seed aturan), 000393 (tabel integrasi), 000394 (atribut ERP produk, approved_at/by; grade sudah default AX).
   - M-2..M-5, M-8, M-10, M-12 ter-deploy.
   - Sebelum 000392 dijalankan di PROD, bandingkan isi seed dengan MICVL/ITEMSELLPRIC/grade PROD (§0). Bila ada beda, perbarui seed dulu.
3. **Link kode item ERP** (D-11), yarn dan MB.
   - Link tidak di-backfill. `cpm_erp_item_code` dan `cpm_erp_grade_code_2` wajib di aplikasi costing.
   - §9a → §9b: item+shade ber-ADJ tanpa produk. Untuk tiap baris, §9d memberi kandidat:
     - `LINK_CANDIDATE`: produk sudah ada tetapi kode item ERP-nya belum didefinisikan → Costing mendefinisikannya di produk cost;
     - `CREATE_NEW`: Costing membuat produk cost baru (grade AX), lalu menghitung dan meng-approve ACTUAL.
   - Jalankan `BackfillErpAttributes` (dry-run → laporan → apply) untuk atribut FG type, chips, MB, item type, dan prod/hari pada produk yang sudah ter-link.
   - Lolos bila:
     - §9b = 0 baris untuk item+shade ber-ADJ;
     - §9c = 0 baris;
     - §9e = 0 duplikat;
     - §9f = 0 (V-12: CMB hanya ke produk tipe MB `CSTMB…`).
   - Setelah itu buat index unik `uix_cpm_erp_item_shade`.
4. **Golden test derivasi** dari §3 lolos. Hasil DEV 2026-09-25: 9.602/9.602 std cocok.
5. **Satuan tier margin (T-4)**: jalankan §10 dan putuskan isi `GSC_CONV_COSTn` serta NVL di view sebelum repoint.
6. **Sign-off hitung manual sampel** (M-9, PRD §11.1).
   - Finance memilih minimal 30 kombinasi (`tmp_manual_sample`).
   - Ekspor dengan §11, hitung manual, lalu bandingkan sampai 5 desimal.
   - Produk yang gagal dianalisis. Defect E-8/E-6/E-7/E-5 bisa terlihat di sini.

## 3. Proses bulanan (periode P)

Coverage boleh dijalankan kapan saja di bulan berjalan untuk mencicil gap produk. Langkah 4 dan seterusnya dijalankan setelah produksi ditutup.

| # | Langkah | Pelaku | Cek lolos |
|---|---|---|---|
| 1 | **Load demand**: ETL ADJ P (aturan tidak di-ETL; dibaca dari master goapps) | Costing | Batch `DEMAND_LOADED`; 0 head posted (V-10); §1 cocok dengan ringkasan goapps |
| 2 | **Coverage** item ERP + shade (yarn + MB) vs produk AX aktif vs ACTUAL APPROVED USD | Costing | Semua `OK` (§2). Tidak ada fallback ke std lama. |
| 3 | Gap `NO_MAPPING`: (a) produk sudah ada tetapi kode item ERP belum didefinisikan → "Link ke produk existing"; (b) produk belum ada → "Buat produk cost baru" (isi item ERP, shade, FG type, chips/MB), hitung ACTUAL, verify, approve. Gap `NO_COST`/`NOT_APPROVED`: selesaikan cost. Ulangi langkah 1–2. | Costing | Coverage 100% |
| 4 | **Derive** grade turunan ke `cst_erp_std_cost` (snapshot + hash aturan) | sistem | §2b hanya `OK`. Status lain (V-08) diselesaikan di master aturan / grade group / FG type. |
| 5 | **Validate** (V-01..V-12) dan **dry-run** | Finance Approver | 0 error; warning V-05 (Δ > 20%) dan diff aturan vs periode lalu di-acknowledge |
| 6 | **Push**: insert `CST_GOAPPS_STD_BATCH` (`PUSHED`) + `CST_GOAPPS_STD_COST` → `VALUATE_ADJ` | Finance Approver | Batch `VALUATED` (batch lain P → `SUPERSEDED`); §4a total kontrol + checksum Oracle = goapps; `adj_items_not_covered = 0` |
| 7 | **Rekon** | Costing | §4 semua `MATCH` dan `NOT_IN_ADJ` kosong; §5 `val_inconsistent = 0`, Σ val = Σ goapps; §7 kosong |
| 8 | Cek pra-approve lalu **approve ADJ** (manual atau `APPROVE_ADJ`) | Finance Approver | §6 = 0 baris; tidak ada error 241441; batch `APPROVED` |
| 9 | Posting ADJ | Tim ERP | `POST_STATUS` terisi, freeze otomatis (ORA-20901) |
| 10 | **Lock** P di goapps (`cst_period_lock`) + `LOCK_BATCH` | Costing | Batch goapps dan Oracle `LOCKED`; recompute P ditolak |

Tidak ada langkah mirror. Setelah langkah 6, `V_GOAPPS_STD_COST_CUR` otomatis memakai batch P, sehingga WMS dan laporan margin langsung membaca std baru.

**Koreksi sebelum posting:**
1. Perbaiki cost atau aturan di goapps, lalu approve versi baru.
2. Ulang langkah 1 dan 4–8. Ini membuat batch baru; batch lama otomatis `SUPERSEDED`. Baris std Oracle tidak pernah di-update, karena tabelnya insert-only.
3. Bila data tidak berubah, push kedua harus menghasilkan ADJ identik (uji idempotensi).

**ADJ baru setelah ETL** (§7 tidak kosong):
- ETL ulang, buat produk AX bila perlu, derive, lalu push batch baru.
- Baris tanpa std **tidak** di-nol-kan. Rate awalnya dari WMS tetap ada sampai dinilai.

**Perubahan aturan oleh Finance:**
- Lewat UI master aturan (audit tercatat).
- Berlaku untuk batch berikutnya. Batch lama tetap memakai snapshot-nya.
- Grade baru dari sync ERP muncul tanpa grade group dan memblokir derive (V-08) sampai Finance mengisinya.

## 4. Parallel run 202609–202610 (OQ-9)

1. Legacy tetap berjalan penuh di **PROD** dan diposting seperti biasa.
2. Modul integrasi dijalankan ke **ALTHARADEV**, dengan ADJ yang di-refresh dari PROD pada cut-off yang sama.
3. §8: bandingkan rate legacy PROD vs goapps DEV per (item, grade, shade), dikelompokkan per basis.
   - `SPPTY`/`SPITY`/`SPBSD` harus identik.
   - `COST`/`AX` hanya informasi. Rumus sengaja dikoreksi, jadi gap vs legacy **tidak dinilai**.
4. §9b per periode: semua item+shade ber-ADJ terpetakan ke tepat satu produk.
5. §12 di DEV: uji repoint.
   - Isi view dan tidak ada duplikat.
   - `LEGACY_FROZEN` identik dengan tabel lama.
   - Laporan margin dan `O_DGET_FG_ITEM_RATE_MGT` lama vs baru.
6. Kriteria lolos: PRD §11 (sampel manual, link item, mekanik ERP, duplikat konsisten, idempoten). Dua periode lolos berarti go-live di PROD.

## 5. Cutover (periode pertama di PROD)

1. **H-5:**
   - Deploy M-ERP-1..3 di PROD, lalu §0 (DEV = PROD, objek `VALID`).
   - Deploy migration goapps PROD, jalankan backfill atribut, dan selesaikan gap link §9b/§9d (link atau produk baru).
   - Freeze perubahan link item ERP / FG type kecuali lewat Costing.
2. **H-1:** backup:
   ```sql
   CREATE TABLE OT_STD_COST_PRODUCTS_MGT_BK_<P> AS SELECT * FROM OT_STD_COST_PRODUCTS_MGT;
   CREATE TABLE OT_ADJ_ITEM_BK_<P> AS
     SELECT i.* FROM OT_ADJ_ITEM i JOIN OT_ADJ_HEAD h ON h.ADJH_SYS_ID = i.ADJI_ADJH_SYS_ID
      WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
        AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE('<P>01','YYYYMMDD') AND LAST_DAY(TO_DATE('<P>01','YYYYMMDD'));
   ```
   Simpan juga DDL objek yang akan di-repoint (`DBMS_METADATA.GET_DDL` / export dari DataGrip):
   - `O_DGET_FG_ITEM_RATE_MGT`, `FUNC_CHIP_WAC_MGT`;
   - `VIEW_MARGIN_REPORT`, `VIEW_MARGIN_REPORT_TODAY` (+ `_DEV*` sesuai T-3);
   - `PRODUCTS_LIST_COST`.
3. **Hari-H (M-ERP-4):**
   1. Hentikan langkah legacy untuk INVADJ dan MB:
      - insert AX dari `CST_YARN_CALCULATION_CUR`;
      - `CHP_WAC_UPD`, `STD_FG_COST_UPD_PRD`, `STD_FG_VALUE_INSERT`;
      - std CMB dari `CST_MB_CONSUMP_HEAD`;
      - langkah 7 untuk INVADJ, MBINVADJ, dan MBINVADJRP.
   2. Jalankan proses bulanan §3 sampai langkah 6. Batch `VALUATED` berarti view sudah berisi std P.
   3. **Repoint** objek (ddl §7) ke `V_GOAPPS_STD_COST_CUR`. Jadwal mengikuti T-2: bersamaan, atau H+1 untuk view margin.
      - Recompile dan pastikan statusnya `VALID`.
      - Ulang §12a–c di PROD.
4. Legacy costing tetap dihitung 1 periode lagi sebagai pembanding, tanpa diterapkan ke ERP.
5. `OT_STD_COST_PRODUCTS_MGT` tidak lagi diperbarui untuk yarn maupun MB.
   - Baris lama tetap tampil di view sebagai `LEGACY_FROZEN` untuk laporan.
   - Produk baru goapps mendapat rate WMS dari view, sehingga tidak ada lagi 220079 bagi produk yang sudah ter-cover.

## 6. Rollback

Hanya berlaku selama ADJ periode P **belum posted**.

| Situasi | Tindakan |
|---|---|
| Hasil batch salah, goapps benar setelah koreksi | Push batch baru (§3 koreksi) |
| Kembali ke nilai ADJ sebelum batch B | `PKG_GOAPPS_ADJ.RESTORE_ADJ(:B)`. Mengembalikan rate/val/flex dari `CST_GOAPPS_ADJ_LOG`, hanya untuk baris yang masih FLEX_13 = B. Batch menjadi `FAILED` (`GSB_ERROR='RESTORED'`) dan keluar dari view. |
| Repoint menimbulkan masalah laporan/WMS | Compile ulang DDL backup H-1. Tabel lama masih utuh (dibekukan, tidak pernah diubah integrasi). |
| Kembali ke jalur legacy untuk periode P | `RESTORE_ADJ`, compile ulang DDL backup, lalu jalankan ulang langkah legacy (insert AX, derivasi, langkah 7). `OT_STD_COST_PRODUCTS_MGT` masih utuh; `_BK_<P>` hanya cadangan. |
| Sudah posted | Tidak ada rollback teknis. Koreksi lewat ADJ/jurnal koreksi di ERP. Unlock goapps ditolak sistem bila ADJ sudah posted. |

## 7. Log dan error umum

- goapps: `job_execution`, `cst_erp_int_batch.ceib_summary / ceib_error`, audit master aturan, halaman Integrasi ERP.
- Oracle:
  - `CST_GOAPPS_STD_BATCH.GSB_STATUS / GSB_SUMMARY / GSB_ERROR` dan total kontrol `GSB_ROW_COUNT / GSB_SUM_*`;
  - `CST_GOAPPS_STD_COST` (isi std per batch);
  - `CST_GOAPPS_ADJ_LOG` (nilai sebelum);
  - `ADJI_FLEX_13/14` di baris ADJ.
- Error:

  | Kode | Arti | Tindakan |
  |---|---|---|
  | ORA-20901 | Periode sudah ada ADJ posted | Tidak boleh diubah |
  | ORA-20902 | Rate > 20 untuk prefix yang dijaga | Cek V-07 / cost produk |
  | ORA-20903 / 20905 | Batch tidak lengkap: status bukan `PUSHED` atau total kontrol ≠ isi tabel | Push ulang dengan batch baru; cek §4a |
  | ORA-20904 | `LOCK_BATCH` sebelum periode posted | Posting dulu (langkah 9) |
  | ORA-20910 | DML ke `CST_GOAPPS_STD_*` bukan dari `GOAPPS_IF` | Tidak boleh; semua perubahan lewat goapps |
  | ORA-20911 | Update/delete baris `CST_GOAPPS_STD_COST` | Tabel insert-only; koreksi = batch baru |
  | ORA-20912 | Insert std ke batch yang sudah bukan `PUSHED` | Buat batch baru |
  | 241441 | Rate ≤ 0 atau > 20 saat approve | Jalankan §6 dan §7 |
  | 220079 | WMS: std tidak ditemukan di view (mode 'E') | Item+shade belum ter-cover goapps dan tidak ada di `LEGACY_FROZEN`: buat produk baru atau link item ERP ke produk existing, lalu push batch (§3 langkah 2–6) |
