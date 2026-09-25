# Modifikasi yang Diperlukan — goapps dan ERP (v0.3.1)

Pendamping `PRD_ERP_COST_INTEGRATION.md` v0.3.1. v0.2 ada di `archive_v0.2/`.

Ringkas:
- Engine kalkulasi tidak dirombak.
- goapps: **modul integrasi ERP** baru (ETL demand, coverage, derivasi grade, std per periode, push duplikat ke Oracle, valuasi ADJ, rekon, lock), **master aturan milik Finance**, atribut ERP produk yang wajib, approved_at/by, period lock, currency USD.
- ERP: tabel duplikat std (`CST_GOAPPS_STD_BATCH/COST`) + trigger guard, package `PKG_GOAPPS_ADJ`, view kompatibel `V_GOAPPS_STD_COST_CUR`, repoint objek pembaca `OT_STD_COST_PRODUCTS_MGT`, dan grant.

Perubahan dari v0.2:
- Aturan tidak lagi di-ETL; di-seed sekali lalu CRUD oleh Finance (M-1, M-12).
- Push ke `CST_GOAPPS_STD_COST` (duplikat, single source goapps), bukan `CST_GOAPPS_ADJ_RATE` (M-2).
- Mapping item ERP + shade wajib dan unik, tanpa fallback; produk tanpa grade = AX (M-7).
- M-9 bukan lagi rekon gap −5,13%, melainkan sign-off hitung manual sampel.
- Mirror (M-11 lama, M-ERP-5 lama) dihapus; diganti repoint (M-ERP-4).

Revisi 0.3.1:
- MB ikut alur goapps (M-6 jadi P0, bukan menunggu). Tidak ada jalur MB legacy.
- Link item ERP + shade dibuat Costing di aplikasi. Field `cpm_erp_item_code` / `cpm_erp_grade_code_2` sudah wajib; backfill link dari `cpm_flex_01` dan UPDATE grade dihapus (M-7).
- Coverage `NO_MAPPING` punya dua tindakan: buat produk baru, atau link ke produk existing (M-3, M-8).

Prioritas: **P0** = blocker go-live, **P1** = wajib sebelum parallel run, **P2** = menyusul.

Repo: `D:\IT Project\goapps\goapps-backend\services\finance`. Migration terakhir 000391, jadi yang baru mulai **000392**.

## A. goapps — modul integrasi (`internal/application/erpintegration/`)

### M-1 (P0) Migration

| File | Isi |
|---|---|
| `000392_erp_rules_seed.up.sql` | = `goapps_000392_erp_rules_seed.sql` (sudah ditulis): `cost_erp_grade.ceg_grade_group` + isi 22 grade, `cst_erp_sell_price` (3), `cst_erp_valloss_rule` (115, unik fg_type/prod_type/grade_group, kolom audit), DO block assert. |
| `000393_create_erp_integration.up.sql` | batch, demand, std cost, period lock (di bawah) |
| `000394_add_cpm_erp_attributes.up.sql` | atribut ERP produk + index unik (M-7) |

`000393_create_erp_integration.up.sql`:

```sql
-- batch integrasi per periode
CREATE TABLE cst_erp_int_batch (
  ceib_batch_id      BIGSERIAL PRIMARY KEY,           -- = Oracle GSB_BATCH_ID
  ceib_period        VARCHAR(6)  NOT NULL,
  ceib_seq           INT         NOT NULL,
  ceib_status        VARCHAR(20) NOT NULL,            -- DRAFT|DEMAND_LOADED|COVERED|DERIVED|VALIDATED|PUSHED|VALUATED|RECONCILED|LOCKED|FAILED|SUPERSEDED
  ceib_job_id        UUID,                            -- job_execution
  ceib_demand_run_id BIGINT,
  ceib_rule_snapshot JSONB,                           -- isi cst_erp_valloss_rule / sell_price / grade group saat derive
  ceib_rule_hash     CHAR(64),
  ceib_row_count     INT,                             -- total kontrol push (= GSB_ROW_COUNT)
  ceib_sum_std       NUMERIC(24,5),                   -- = GSB_SUM_STD
  ceib_sum_conv      NUMERIC(24,5),                   -- = GSB_SUM_CONV
  ceib_sum_pvl       NUMERIC(24,5),                   -- = GSB_SUM_PVL
  ceib_summary       JSONB,                           -- coverage, validasi, ringkasan ERP
  ceib_error         TEXT,
  created_at TIMESTAMPTZ NOT NULL DEFAULT now(), created_by VARCHAR(64) NOT NULL,
  pushed_at TIMESTAMPTZ, pushed_by VARCHAR(64),
  locked_at TIMESTAMPTZ, locked_by VARCHAR(64),
  CONSTRAINT uq_ceib UNIQUE (ceib_period, ceib_seq)
);
CREATE UNIQUE INDEX uix_ceib_active ON cst_erp_int_batch (ceib_period)
  WHERE ceib_status NOT IN ('SUPERSEDED','FAILED');

-- demand ADJ (snapshot ETL) — sama dengan v0.2
CREATE TABLE cst_erp_adj_demand (
  ced_id             BIGSERIAL PRIMARY KEY,
  ced_run_id         BIGINT      NOT NULL,
  ced_period         VARCHAR(6)  NOT NULL,
  ced_txn_code       VARCHAR(12) NOT NULL,
  ced_item_kind      VARCHAR(4)  NOT NULL,            -- YARN|MB
  ced_item_code      VARCHAR(20) NOT NULL,
  ced_item_name      VARCHAR(500),                   -- OM_ITEM.ITEM_NAME (form produk baru / cari kandidat link)
  ced_grade_code     VARCHAR(20) NOT NULL,
  ced_shade_code     VARCHAR(20) NOT NULL,
  ced_item_count     INT         NOT NULL,
  ced_qty_kg         NUMERIC(20,4),
  ced_erp_min_rate   NUMERIC(20,7), ced_erp_max_rate NUMERIC(20,7),
  ced_approved_items INT, ced_posted_items INT,
  ced_loaded_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
  CONSTRAINT uq_ced UNIQUE (ced_run_id, ced_txn_code, ced_item_code, ced_grade_code, ced_shade_code)
);
CREATE INDEX ix_ced_period ON cst_erp_adj_demand (ced_period, ced_run_id);

-- std per periode + batch (sumber tunggal; diduplikasi ke MGTDAT.CST_GOAPPS_STD_COST)
CREATE TABLE cst_erp_std_cost (
  cesc_id              BIGSERIAL PRIMARY KEY,
  cesc_batch_id        BIGINT      NOT NULL REFERENCES cst_erp_int_batch,
  cesc_period          VARCHAR(6)  NOT NULL,
  cesc_item_code       VARCHAR(20) NOT NULL,
  cesc_grade_code      VARCHAR(20) NOT NULL,
  cesc_shade_code      VARCHAR(20) NOT NULL,
  cesc_item_kind       VARCHAR(4)  NOT NULL,
  cesc_source          VARCHAR(16) NOT NULL,          -- GOAPPS_AX|GOAPPS_DERIVED|GOAPPS_MB
  cesc_ax_cost_sys_id  BIGINT,                        -- cst_product_cost AX sumber
  cesc_ax_cost_version INT,
  cesc_product_sys_id  BIGINT,
  cesc_prod_type       VARCHAR(3),
  cesc_grade_group     VARCHAR(20),
  cesc_fg_type         VARCHAR(15),
  cesc_basis           VARCHAR(15),
  cesc_chp_item_code   VARCHAR(50),
  cesc_chp_con_kg      NUMERIC(20,5),
  cesc_chp_cost        NUMERIC(20,5),
  cesc_ax_conv_cost    NUMERIC(20,5),
  cesc_conv_cost       NUMERIC(20,5),
  cesc_conv_cost1      NUMERIC(20,5),                 -- tier laporan margin (T-4), satuan = legacy
  cesc_conv_cost2      NUMERIC(20,5),
  cesc_conv_cost4      NUMERIC(20,5),
  cesc_conv_cost5      NUMERIC(20,5),
  cesc_selling_price   NUMERIC(20,5),
  cesc_value_loss      NUMERIC(20,5),
  cesc_ax_cost         NUMERIC(20,5),
  cesc_std_cost        NUMERIC(20,5),                 -- = ADJI_RATE
  cesc_prod_value_loss NUMERIC(20,5),
  cesc_ms_batch_item   VARCHAR(20),
  cesc_item_type       VARCHAR(20),
  cesc_prd_per_day     NUMERIC(20,5),
  cesc_status          VARCHAR(16) NOT NULL,          -- OK|NO_AX|NO_RULE|NO_FG_TYPE|NO_GRADE_GROUP|NO_SELL_PRICE|INVALID
  cesc_validation      JSONB,
  cesc_erp_rate        NUMERIC(20,7), cesc_erp_rate_variants INT,
  cesc_erp_qty_kg      NUMERIC(20,4), cesc_erp_value NUMERIC(20,7),
  cesc_recon_status    VARCHAR(16),                   -- MATCH|DIFF|NOT_IN_ADJ
  CONSTRAINT uq_cesc UNIQUE (cesc_batch_id, cesc_item_code, cesc_grade_code, cesc_shade_code)
);
CREATE INDEX ix_cesc_period ON cst_erp_std_cost (cesc_period, cesc_item_code, cesc_shade_code);

-- lock periode (dipakai modul costing juga, M-5)
CREATE TABLE cst_period_lock (
  cpl_period       VARCHAR(6)  NOT NULL,
  cpl_calc_type    VARCHAR(10) NOT NULL,
  cpl_locked_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
  cpl_locked_by    VARCHAR(64) NOT NULL,
  cpl_reason       TEXT,
  cpl_erp_batch_id BIGINT REFERENCES cst_erp_int_batch,
  PRIMARY KEY (cpl_period, cpl_calc_type)
);
```

### M-2 (P0) Oracle client: tulis + read ETL — `internal/infrastructure/oracle/`

`client.go` saat ini: read + `ExecuteProcedure` tanpa parameter. Tambah:
- `BeginTx`.
- Array-bind insert (go-ora) ke `CST_GOAPPS_STD_BATCH` lalu `CST_GOAPPS_STD_COST`.
- Call dengan OUT param: `BEGIN MGTDAT.PKG_GOAPPS_ADJ.VALUATE_ADJ(:1, :2); END;` (juga `APPROVE_ADJ`, `RESTORE_ADJ`, `LOCK_BATCH`).

Koneksi memakai user `GOAPPS_IF` (env `ORACLE_IF_USER`/`ORACLE_IF_PASSWORD`), terpisah dari user sync existing. Trigger guard Oracle menolak DML dari user lain.

Repository `erp_integration_repository.go`:
- `LoadDemand(period)`
- `PushStd(batch, header, rows)` — satu transaksi: header status `PUSHED` + baris + total kontrol; commit; lalu `Valuate`.
- `Valuate(batch)`, `Approve(batch, uid)`, `Restore(batch)`, `LockBatch(batch)`
- `FetchRecon(period, batch)`
- `FetchOracleBatchTotals(batch)` — hitung ulang count/Σ dari `CST_GOAPPS_STD_COST` (PRD §11.4)
- `GetPostStatus(period)`
- `LoadLegacyStdForBackfill()` — **sekali**, untuk backfill atribut M-7 (bukan link)

`LoadRules()` (v0.2) dihapus: aturan dibaca dari Postgres.

Urutan push: bila `VALUATE_ADJ` gagal, batch Oracle tetap `PUSHED` (baris sudah commit) dan tidak tampil di `V_GOAPPS_STD_COST_CUR`; goapps menandai `FAILED` dan re-push memakai batch baru.

### M-3 (P0) Application handler `application/erpintegration/`

Mengikuti pola `oraclesync/sync_handler.go` (job_execution, progress, resume).

| Langkah | Handler | Progress |
|---|---|---|
| 1 | `LoadDemandHandler`: ETL demand + cek posted (V-10) | 10% |
| 2 | `CoverageHandler`: item+shade YARN **dan MB** vs produk aktif AX (`cpm_erp_item_code` + `cpm_erp_grade_code_2`) vs cost ACTUAL APPROVED USD → status per baris. Untuk `NO_MAPPING` sertakan kandidat produk existing yang belum ter-link (nama/shade mirip). Export Excel. | 25% |
| 3 | `DeriveHandler`: Mapping §4 ke `cst_erp_std_cost`, snapshot aturan + hash (pakai `shopspring/decimal`, ROUND half away from zero 5) | 45% |
| 4 | `ValidateHandler`: V-01..V-12 | 55% |
| 5 | `PushHandler`: `PushStd` + `VALUATE_ADJ` (dry_run = stop sebelum langkah ini) + cek total kontrol Oracle = goapps | 80% |
| 6 | `ReconHandler`: baca balik ADJ → recon_status | 100% |
| — | `LockHandler` / `UnlockHandler`: `cst_period_lock` + `LOCK_BATCH`; unlock ditolak bila ADJ sudah posted | — |

- Domain murni `domain/erpintegration/derive.go`: `Derive(ax AXComponents, grade GradeInfo, rules RuleSet) StdCost`. Diuji unit dengan golden cases dari std ERP DEV (recon §3; DEV 2026-09-25: 9.602/9.602 cocok).
- Coverage tidak punya fallback: kombinasi tanpa produk AX = `NO_MAPPING`, dan tidak ada pencarian std ERP lama (D-10).
- `NO_MAPPING` diselesaikan lewat dua endpoint:
  - `CreateCostProductFromDemand(period, item, shade)` — buat produk cost baru (grade AX) dengan item/shade/nama terisi dari demand;
  - `LinkErpToProduct(product_sys_id, item, shade)` — memanggil `CostProductMaster.LinkErp` yang sudah ada, dengan validasi V-04 (unik) dan V-06 (item/shade ada di replika).
- Derivasi MB: grade `A` ERP pada item kind `MB` = grade AX goapps, `std = cost AX`, `source = GOAPPS_MB` (DATA_MAPPING_SPEC §6).
- gRPC/HTTP:
  - `LoadErpDemand(period)`
  - `GetErpCoverage(period)`
  - `RunErpIntegration(period, dry_run)`
  - `GetErpIntegrationBatch(id)`
  - `ListErpStdCost(period, filter)`
  - `LockErpPeriod(period)`
  - `GetErpItemLinkReport()` (FR-10)

### M-4 (P1) Jejak approve terpisah

- `MarkVerified` dan `MarkApproved` saat ini sama-sama menulis `cpc_verified_at/by`.
- Tambah `cpc_approved_at/by` (di 000394), isi di transisi APPROVED, backfill dari `aud_cost_history`.

### M-5 (P0) Period lock

- Guard `ErrPeriodLocked` di: submit/recompute calc job, `MarkVerified`, `MarkApproved`, supersede, dan perubahan param/master yang memicu recompute periode ter-lock.

### M-6 (P0) MB lewat modul integrasi

Cost MB sudah dihitung di goapps (OQ-5). Yang diperlukan hanya integrasinya:
- ETL dan coverage mencakup `MBINVADJ`/`MBINVADJRP` (`ced_item_kind='MB'`).
- Produk cost MB di-link ke item CMB + shade (`NL`, sebagian `RS`) seperti yarn.
- Derivasi: grade ERP `A` = grade AX goapps, tanpa aturan value loss (DATA_MAPPING_SPEC §6).
- `VALUATE_ADJ` / `APPROVE_ADJ` mencakup txn MB (ddl §6).
- Produk MB = produk cost MB goapps = tipe produk `MB` (`cost_product_type.cpt_type_id = 29`, "Master Batch"), kode costing berprefix `CSTMB`. ACTUAL MB dihitung per item: 1 produk `CSTMB` di-link ke 1 item ERP `CMB…` (+ shade). Grade `A` ERP pada MB = grade AX goapps, sama seperti yarn.
- `CreateCostProductFromDemand` untuk item `CMB…` membuat produk tipe `MB` (id 29) dengan kode `CSTMB…`. `LinkErpToProduct` menolak pasangan tipe yang tidak konsisten (V-12).
- (`mst_mb_head` 000388 adalah master melange batch untuk param `MB_RATE_MKT`, bukan produk cost MB.)

### M-7 (P0) Atribut ERP produk + link kode item ERP

`000394_add_cpm_erp_attributes.up.sql`:

```sql
ALTER TABLE cost_product_master
  ADD COLUMN IF NOT EXISTS cpm_erp_fg_type        VARCHAR(15),
  ADD COLUMN IF NOT EXISTS cpm_erp_chp_item_code  VARCHAR(50),
  ADD COLUMN IF NOT EXISTS cpm_erp_ms_batch_item  VARCHAR(20),
  ADD COLUMN IF NOT EXISTS cpm_erp_item_type      VARCHAR(20),
  ADD COLUMN IF NOT EXISTS cpm_erp_prd_per_day    NUMERIC(20,5);
ALTER TABLE cst_product_cost
  ADD COLUMN IF NOT EXISTS cpc_approved_at TIMESTAMPTZ,
  ADD COLUMN IF NOT EXISTS cpc_approved_by VARCHAR(64);

-- produk tanpa grade = AX (OQ-F): cpm_grade_code sudah NOT NULL DEFAULT 'AX' (000106), tidak perlu UPDATE.

-- V-04: 1 item ERP + shade = maksimal 1 produk aktif AX
-- Cek duplikat dulu (recon §9e); bila masih ada, Costing membereskannya lalu index dibuat di migration terpisah.
CREATE UNIQUE INDEX uix_cpm_erp_item_shade
  ON cost_product_master (cpm_erp_item_code, cpm_erp_grade_code_2)
  WHERE cpm_is_active AND cpm_grade_code = 'AX';
```

**Link kode item ERP tidak di-backfill.** Field sudah wajib di aplikasi costing. Produk yang belum ter-link diselesaikan Costing lewat coverage (M-3: buat produk baru atau link ke produk existing). `cpm_flex_01` tidak dibaca modul integrasi.

Backfill atribut sekali (job `BackfillErpAttributes`, idempoten, dry-run → laporan → apply):
- `FG_TYPE`, `FG_CHP_ITEM_CODE`, `FG_MS_BATCH_ITEM`, `FG_ITEM_TYPE`, `FG_PRD_PER_DAY` dari baris AX `OT_STD_COST_PRODUCTS_MGT` per item+shade, untuk produk yang **sudah** ter-link.
- Hanya mengisi kolom kosong. Produk yang di-link setelah backfill mendapat atribut dari form (FG type wajib) atau dari job yang dijalankan ulang.

Validasi form produk (V-11, V-08):
- `cpm_erp_item_code`, `cpm_erp_grade_code_2`, `cpm_erp_fg_type` wajib untuk produk aktif yang dipakai integrasi.
- Kode item/shade harus ada di replika `cost_erp_item` / `cost_erp_shade` (V-06).
- Grade default `AX`.

### M-8 (P1) UI dan permission

Halaman "Integrasi ERP" per periode:
1. Status batch dan tombol Load demand.
2. Tab **Coverage** (yarn + MB): item+shade tanpa cost, qty kg, grade terdampak, download. Per baris `NO_MAPPING` dua tombol:
   - "Buat produk cost baru" (form terisi item, shade, nama dari demand/`cost_erp_item`);
   - "Link ke produk existing" (pilih dari kandidat produk aktif AX yang belum punya kode item ERP).
3. Tab **Std cost** hasil derivasi: filter basis/status, Δ vs periode lalu.
4. Tab Validasi.
5. Tombol Dry-run / Push / Re-push / Lock.
6. Tab Rekon (termasuk total kontrol goapps vs Oracle).
7. Tab **Link item ERP** (FR-10).

Permission baru: `finance.cost.erp_integration.view`, `.run`, `.push`, `.lock`, `.unlock`.

### M-9 (P0) Sign-off hitung manual sampel (pengganti rekon gap v0.2)

- Gap ACTUAL −5,13% vs legacy **tidak** dicek lagi (rumus sengaja dikoreksi).
- Finance memilih ≥ 30 kombinasi (PRD §11.1) dan menghitung manual cost AX + std turunan. Ekspor sampel: recon §11.
- Sistem harus sama sampai 5 desimal. Produk yang gagal dianalisis; defect engine terbuka dari rekon 202608 (E-8, E-6, E-7 P0; E-5 P3) kemungkinan terlihat di sini dan harus ditutup atau produk itu dikeluarkan dari go-live dengan persetujuan Finance.

### M-10 (P1) Currency

- `cpc_currency_code` default `'IDR'`. Untuk ACTUAL wajib `'USD'` (OQ-1).
- Perbaiki default/penulisan di calc job. V-09 menolak selain USD.

### M-11 (P2) Monitoring

- Metric: durasi job, jumlah coverage gap, Δ total nilai, selisih total kontrol goapps vs Oracle.
- (Mirror v0.2 dihapus.)

### M-12 (P1) Master aturan milik Finance (OQ-C)

- CRUD `cst_erp_valloss_rule`, `cst_erp_sell_price`, `cost_erp_grade.ceg_grade_group`.
- Permission `finance.cost.erp_rule.view`, `finance.cost.erp_rule.manage`.
- Audit per perubahan (pola `aud_*` existing). Batch menyimpan snapshot + hash; UI menampilkan diff aturan vs snapshot periode lalu.
- Sync `cost_erp_grade` dari ERP diubah agar **tidak menimpa** `ceg_grade_group`; grade baru masuk dengan group kosong dan muncul sebagai tugas Finance (V-08).
- Tidak ada ETL aturan dari ERP. Setelah go-live, `MGT_ITEM_COST_VAL_LOSS` / `ITEMSELLPRIC` di ERP tidak lagi dipakai untuk INVADJ.

## B. ERP Oracle MGTDAT (perlu persetujuan Tim ERP/DBA)

DDL di `ddl_proposal_mgtdat_interface.sql` v0.3.1.

| ID | Prioritas | Perubahan |
|---|---|---|
| M-ERP-1 | P0 | Tabel duplikat `CST_GOAPPS_STD_BATCH` + `CST_GOAPPS_STD_COST` (§1–2), trigger guard `TRG_GSB_GUARD` / `TRG_GSC_GUARD` (§3: hanya `GOAPPS_IF`, `CST_GOAPPS_STD_COST` insert-only), log `CST_GOAPPS_ADJ_LOG` (§4), view `V_GOAPPS_ADJ_DEMAND`, `V_GOAPPS_STD_COST_LATEST`, `V_GOAPPS_STD_COST_CUR` (§5), package `PKG_GOAPPS_ADJ` (VALUATE_ADJ, APPROVE_ADJ opsional, RESTORE_ADJ, LOCK_BATCH) (§6). |
| M-ERP-2 | P0 | User `GOAPPS_IF` (§8): SELECT sumber ETL (`OT_ADJ_HEAD/ITEM`, `OM_ITEM`, `OM_GRADE_CODE_1/2`); SELECT `OT_STD_COST_PRODUCTS_MGT`, `MGT_ITEM_COST_VAL_LOSS`, `IM_VS_STATIC_VALUE` hanya untuk backfill atribut/cek seed/parallel run (boleh di-REVOKE setelah go-live); INSERT/SELECT (+UPDATE batch) `CST_GOAPPS_STD_*`; EXECUTE package. SELECT tabel/view std untuk role laporan (T-1). |
| M-ERP-3 | P0 | Pemakaian `ADJI_FLEX_13/14` (D-6) — disetujui (OQ-E). |
| M-ERP-4 | P1 | Cutover: **repoint** `O_DGET_FG_ITEM_RATE_MGT`, `FUNC_CHIP_WAC_MGT` (C1), `VIEW_MARGIN_REPORT`, `VIEW_MARGIN_REPORT_TODAY` (+ `_DEV*`, T-3), opsional `PRODUCTS_LIST_COST` → `V_GOAPPS_STD_COST_CUR` (§7). Backup DDL dulu; uji output lama vs baru (recon §10, §12). Hentikan langkah legacy 1–7 untuk INVADJ dan langkah MB legacy (insert AX, `CHP_WAC_UPD`, `STD_FG_COST_UPD_PRD`, `STD_FG_VALUE_INSERT`, std CMB dari `CST_MB_CONSUMP_HEAD`, valuasi langkah 7). `OT_STD_COST_PRODUCTS_MGT` dibekukan. |

**Tidak diperlukan lagi:**
- `CST_GOAPPS_ADJ_BATCH` / `CST_GOAPPS_ADJ_RATE` (v0.2) — diganti `CST_GOAPPS_STD_*`.
- `MIRROR_STD` / mirror ke `OT_STD_COST_PRODUCTS_MGT` (v0.2 M-11, M-ERP-5).
- ETL aturan MICVL / ITEMSELLPRIC (v0.2 `LoadRules`).
- (Sejak v0.2) staging `CST_GOAPPS_COST_IF`, `APPLY_STD` / `REFRESH_DERIVED`, patch `CHP_WAC_UPD`, penyesuaian trigger `ODBTRG_FG_STD_COST`.

## C. Tidak perlu diubah

- Engine kalkulasi, route, CAPP param, chunked calc (`costcalc`).
- Status model (CALCULATED → VERIFIED → APPROVED → SUPERSEDED). Hanya ditambah lock dan approved_at/by.
- Pola sync Oracle existing (kecuali sync `cost_erp_grade` tidak menimpa `ceg_grade_group`, M-12).
- `mst_product_grade` (000387) adalah konsep grade costing (STD_A, bc_perc) dan **bukan** grade ERP. Grade ERP tetap di `cost_erp_grade`.
