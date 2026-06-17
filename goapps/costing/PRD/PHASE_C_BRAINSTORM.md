# Phase C — Calculation Engine: Migration Notes & Gap Analysis

> **Dokumen ini:** Suplemen diskusi arsitektur migrasi `pkg_yarn_calculation` Oracle → Go.
> Dibuat sebagai bahan alignment developer sebelum mulai implementasi engine.
>
> **Baca dulu (wajib sebelum baca ini):**
> - [`CALCULATION_ENGINE_BLUEPRINT.md`](./CALCULATION_ENGINE_BLUEPRINT.md) — arsitektur resmi engine (6-stage pipeline)
> - [`PRD_PhaseCProductCalculation.md`](./PRD_PhaseCProductCalculation.md) — PRD lengkap Phase C
> - [`ERD_Master.md`](./ERD_Master.md) — 34 tabel, prefix registry
>
> **Status:** 🟡 Dua gap di ERD perlu diselesaikan sebelum coding Stage 3 & 4

---

## 1. Keputusan Desain yang Sudah Terkunci

Dari PRD §3.2 Non-Goals dan Blueprint Design Goals — tidak perlu didiskusikan lagi:

| Keputusan | Status | Referensi |
|---|---|---|
| Formula **hardcode di Go**, bukan data-driven/DSL | ✅ Terkunci | PRD §3.2 Non-Goals |
| Tidak ada custom formula editor untuk end-user | ✅ Terkunci | PRD §3.2 Non-Goals |
| Tidak ada per-product formula override | ✅ Terkunci | PRD §3.2 Non-Goals |
| Target performa: 12.000 products × 125 params < 2 menit | ✅ Terkunci | Blueprint §1 |
| Arsitektur 6-stage pipeline | ✅ Terkunci | Blueprint §2 |
| Trigger: Cron + Admin manual button | ✅ Terkunci | Blueprint §3.1 |

---

## 2. Rekonsiliasi Nama Tabel

> ⚠️ Dokumen sebelumnya (`design-master-data-costing.md`) pakai nama tabel dari sistem
> Laravel (`mst_parameter`, `cost_product_applicable_param`, dll). Nama yang benar untuk
> Costing Suite ada di `ERD_Master.md`.

| Nama di brainstorm sebelumnya | Nama yang benar (ERD) | Prefix |
|---|---|---|
| `mst_parameter` | `cost_parameter_master` | `CPRM_` |
| `cost_product_applicable_param` | tidak ada — tidak dipakai di Costing Suite | — |
| `cost_product_parameter` | `cost_product_parameter` | `CPP_` ✅ sama |
| `cost_calculation_result` | `cost_calculation_result` | `CCRE_` ✅ sama |
| `cost_calculation_run` | `cost_calculation_run` | `CCR_` ✅ sama |

---

## 3. Mapping Oracle → Sistem Baru (nama yang benar)

| Konsep Oracle | Tabel / Layer Baru | Kolom | Keterangan |
|---|---|---|---|
| `CST_YARN_LEFT` | `cost_product_master` | `CPM_*` | Master produk |
| `CST_YARN_TOP` (param def) | `cost_parameter_master` | `CPRM_*` | Definisi parameter |
| `CYC_FORMULA_TYPE` | `cost_parameter_master` | `CPRM_formula_type` ← **GAP** | Tipe handler Go |
| `CYC_PROCESS_SEQ` | `cost_parameter_master` | `CPRM_calc_seq` ← **GAP** | Urutan eksekusi |
| `CYC_DATA_VALUE` (static) | `cost_product_parameter` | `CPP_value_numeric/text` | Nilai static per produk |
| `CYC_DATA_VALUE` (period) | `cost_product_parameter_period` | `CPPP_value_numeric/text` | Nilai dynamic per period |
| `CYC_DATA_VALUE` (hasil) | `cost_calculation_result` | `CCRE_param_values JSONB` | Snapshot hasil |
| `CST_YARN_FILTER` | Request context | `[]productSysID` | Scope per run |
| `vIdMkt / vIdVal` | Go enum | `PricingType` | `MARKETING \| VALUATION` |
| `vPeriodCostingGrpItem` | Run parameter | `period string` (YYYYMM) | Sudah ada di PRD |

---

## 4. Gap yang Perlu Diselesaikan

### Gap 1 — `CPRM_formula_type` dan `CPRM_calc_seq` belum ada di ERD

ERD saat ini tidak mendefinisikan dua kolom ini di `cost_parameter_master`. Tanpa ini:
- Engine tidak tahu handler mana yang dipanggil per param
- Engine tidak tahu urutan eksekusi (dependency order per param)

**Migration yang perlu ditambah:**

```sql
-- Migration: add formula_type and calc_seq to cost_parameter_master
ALTER TABLE cost_parameter_master
    ADD COLUMN CPRM_formula_type VARCHAR(30) NOT NULL DEFAULT 'INITIAL_VALUE',
    ADD COLUMN CPRM_calc_seq     INTEGER     NOT NULL DEFAULT 0;

COMMENT ON COLUMN cost_parameter_master.CPRM_formula_type IS
    'Go handler type. Values: INITIAL_VALUE, ARITHMETIC, RM_RATE, MASTER_LOOKUP, INTERMINGLING, IF_CONDITION, FROM_MARKETING';

COMMENT ON COLUMN cost_parameter_master.CPRM_calc_seq IS
    'Execution order within a product calculation. Lower = runs first. Must respect dependency chain.';

-- Index untuk Stage 3 Dispatcher loop
CREATE INDEX idx_cprm_calc_seq
    ON cost_parameter_master(CPRM_calc_seq, CPRM_formula_type)
    WHERE CPRM_param_category = 'CALCULATED' AND CPRM_is_active = TRUE;
```

**Action item:** Tambahkan ke `phase_c_ddl.sql` dan update `ERD_Master.md` §2 (prefix registry CPRM_).

---

### Gap 2 — Struktur `cost_calculation_result` belum jelas

ERD menyebut `CCRE_param_values JSONB` di Flow 4 dan tiga kolom agregat di index hint:
`CCRE_captive_cost`, `CCRE_delivery_cost`, `CCRE_calc_status`. Tapi struktur lengkapnya
belum terdefinisi.

**Pertanyaan yang perlu dijawab sebelum coding Stage 5 (Batch Writer):**

> Apakah `cost_calculation_result` menyimpan **1 row per product per run** (hasil agregat
> + JSONB snapshot semua 125 param), atau **1 row per param per product per run** seperti
> Oracle `CST_YARN_CALCULATION`?

Implikasinya berbeda:

| Opsi | Struktur | Pro | Con |
|---|---|---|---|
| **A — 1 row per product** | `CCRE_param_values JSONB {param_code: value, ...}` | Simple, 1 write per product | Sulit query individual param, JSONB query lambat |
| **B — 1 row per param** | `(run_id, product_id, param_code, value)` | Mudah query per param, debuggable | 12K × 125 = 1.5M rows per run |
| **C — Hybrid** | 1 row per product + JSONB snapshot (sesuai hint di ERD Flow 4) | Balance antara query speed dan storage | JSONB sebagai audit snapshot, agregat untuk dashboard |

**Rekomendasi:** Opsi C — sesuai hint yang sudah ada di ERD. Struktur yang disarankan:

```sql
-- Proposed: cost_calculation_result (CCRE_)
CREATE TABLE cost_calculation_result (
    CCRE_result_id       BIGSERIAL    PRIMARY KEY,
    CCRE_run_id          BIGINT       NOT NULL REFERENCES cost_calculation_run(CCR_run_id),
    CCRE_product_sys_id  BIGINT       NOT NULL REFERENCES cost_product_master(CPM_product_sys_id),
    CCRE_period          VARCHAR(6)   NOT NULL,            -- YYYYMM

    -- Agregat outputs (fast query untuk dashboard & Phase A)
    CCRE_captive_cost    NUMERIC(20,6),
    CCRE_delivery_cost   NUMERIC(20,6),
    CCRE_vb1_cost        NUMERIC(20,6),                   -- Volume Bucket 1
    CCRE_vb2_cost        NUMERIC(20,6),
    CCRE_vb3_cost        NUMERIC(20,6),
    CCRE_vb4_cost        NUMERIC(20,6),
    CCRE_vb5_cost        NUMERIC(20,6),

    -- Status & audit
    CCRE_calc_status     VARCHAR(20)  NOT NULL,            -- SUCCESS, PARTIAL, FAILED
    CCRE_error_message   TEXT,
    CCRE_calc_duration_ms INTEGER,

    -- Snapshot semua 125 param values (audit trail, debugging)
    CCRE_param_values    JSONB        NOT NULL DEFAULT '{}',

    CCRE_is_active       BOOLEAN      NOT NULL DEFAULT FALSE,
    CCRE_created_at      TIMESTAMPTZ  NOT NULL DEFAULT now(),

    UNIQUE (CCRE_run_id, CCRE_product_sys_id)
);
```

**Action item:** Konfirmasi dengan arsitek, lalu update `phase_c_ddl.sql` dan `ERD_Master.md`.

---

## 5. Cara Kerja Oracle `process()` → Pemetaan ke Pipeline

Oracle menjalankan ini per produk (sequential):

```
[Pre-process]
  pRefrshValParam()      ← sync Yarn Rate / captive chain SEBELUM loop utama
  pCkYarnWithoutLoss()   ← null-kan loss formula untuk produk exempt

[Main loop — ordered by PROCESS_SEQ]
  FOR EACH param IN cost_parameter_master
      WHERE product applicable
      ORDER BY CPRM_calc_seq ASC:

    dispatch ke Go handler berdasarkan CPRM_formula_type
    accumulate result ke values map
    END

[Post-process]
  aggregate totals → CCRE_captive_cost, CCRE_delivery_cost, dll
  batch write → cost_calculation_result
```

Pemetaan ke Blueprint 6-stage pipeline:

| Blueprint Stage | Oracle Equivalent |
|---|---|
| Stage 1: Data Loader | Load `cost_parameter_master`, `cost_product_parameter`, `cost_product_parameter_period`, master rates |
| Stage 2: Dep Resolver | Topological sort products (POY→PTY→TTY→MEL) + sort params by `CPRM_calc_seq` |
| Stage 3: Dispatcher | `map[FormulaType]HandlerFunc` — dispatch berdasarkan `CPRM_formula_type` |
| Stage 4: Calculator | Go handlers — mirror logic dari `pkg_yarn_calculation.process()` |
| Stage 5: Batch Writer | Bulk upsert ke `cost_calculation_result` |
| Stage 6: Audit & Status | Update `CCR_status`, write `cost_audit_log` |

Pre-process (`pRefrshValParam`, `pCkYarnWithoutLoss`) → masuk sebelum Stage 1 atau bagian awal Stage 2.

---

## 6. Formula Types → Go Handlers

Semua ini adalah Go handler functions di `internal/finance/costing/handlers/`.
Tidak ada formula editor, tidak ada DSL — sesuai PRD Non-Goals.

| `CPRM_formula_type` | Go Handler | File | Params yang pakai |
|---|---|---|---|
| `ARITHMETIC` | `handleArithmetic` | `handlers/arithmetic.go` | Grade weights, packing cost, utility/kg, RM norms |
| `RM_RATE` | `handleRawMaterial` | `handlers/raw_material.go` | `RM_RATE`, `RM_LANDED_COST` |
| `MASTER_LOOKUP` | `handleMasterLookup` | `handlers/master_lookup.go` | `MC_NAME`, `MC_SPEED`, packing codes |
| `INTERMINGLING` | `handleIntermingling` | `handlers/intermingling.go` | `INTERMINGLE_COST` |
| `IF_CONDITION` | `handleConditional` | `handlers/conditional.go` | `RP_DOZING`, `MB_COST` |
| `FROM_MARKETING` | `handleFromMarketing` | `handlers/from_marketing.go` | Semua param Valuation yang copy dari Marketing |
| `INITIAL_VALUE` | `handleInitialValue` | `handlers/initial_value.go` | Pass-through nilai INPUT/RATE |

### Handler paling kompleks: `RM_RATE`

```go
// handlers/raw_material.go
func (h *RawMaterialHandler) Handle(ctx context.Context, p Param, vals Values) (float64, error) {
    component := h.resolver.GetComponent(ctx, p.ProductSysID) // dari cost_product_order_component

    switch component.RMType {
    case "Store Rate":
        // Lookup dari cost_master_data untuk period aktif
        // MARKETING → CMSD_market_rate
        // VALUATION → CMSD_landed_cost
        return h.masterData.GetRate(ctx, component.ERPItemID, p.Period, p.PricingType)

    case "Captive Cost":
        // Fetch CCRE_delivery_cost dari upstream product
        // ← inilah yang butuh topological order: upstream harus selesai dulu
        return h.results.GetDeliveryCost(ctx, component.RMProductSysID, p.Period)

    case "Multi-Yarn":
        // Weighted average dari beberapa komponen upstream
        total := 0.0
        for _, comp := range component.Components {
            cost, err := h.results.GetDeliveryCost(ctx, comp.ProductSysID, p.Period)
            if err != nil { return 0, err }
            total += cost * comp.SharePct / 100
        }
        return total, nil
    }
}
```

---

## 7. Topological Order untuk Batch Run (Stage 2)

Urutan yang wajib diikuti — upstream harus selesai sebelum downstream:

```
Level 1 (no upstream): POY, FDY, SDY
Level 2 (depends POY): PTY, DTY, ATY
Level 3 (depends PTY): TTY, TCM, TCY
Level 4 (depends PTY): MEL
```

Di dalam satu level, produk bisa diproses **parallel** via goroutines (sesuai Blueprint).
Antar level harus **sequential** (tunggu level sebelumnya selesai).

---

## 8. Suggested File Structure (Detail Stage 3 & 4)

Suplemen dari Blueprint — detail untuk Dispatcher dan Calculator:

```
internal/finance/costing/
├── engine/
│   ├── run_manager.go         ← sudah di Blueprint
│   ├── pipeline.go            ← orchestrate 6 stages
│   ├── dep_resolver.go        ← Stage 2: topological sort products + params
│   ├── dispatcher.go          ← Stage 3: map[FormulaType]HandlerFunc
│   └── batch_writer.go        ← Stage 5: bulk upsert cost_calculation_result
│
├── handlers/                  ← Stage 4: satu file per formula type
│   ├── arithmetic.go
│   ├── raw_material.go        ← paling kompleks, 3 sub-mode
│   ├── master_lookup.go
│   ├── intermingling.go
│   ├── conditional.go
│   ├── from_marketing.go
│   └── initial_value.go
│
├── pre_process/
│   ├── refresh_val_param.go   ← mirror pRefrshValParam(): sync captive chain
│   └── check_no_loss.go       ← mirror pCkYarnWithoutLoss()
│
└── engine_test.go             ← unit tests per handler (reference values dari Oracle)
```

---

## 9. Checklist Sebelum Mulai Coding

> Lihat **§12** untuk checklist lengkap yang sudah diupdate termasuk master tables.

---

## 10. Files dari Sesi Sebelumnya

| File | Gunakan untuk | Catatan |
|---|---|---|
| `formula_engine.go` | Referensi logic kalkulasi | Pindahkan logic ke `handlers/`, rename `mst_parameter` → `cost_parameter_master` |
| `formula_engine_test.go` | Test cases dengan reference values PTY MELANGE | Keep, adjust struct names |
| `01_seed_mst_parameter.sql` | Seed 120 params | Rename kolom ke prefix `CPRM_`, tambah `CPRM_formula_type` + `CPRM_calc_seq` |
| `costing_phase_c_master_data.xlsx` | Template input data master | Masih valid |

---



---

## 11. Master Tables yang Dibutuhkan untuk Calculation Engine

> Hasil analisis dari `costing_parameter_product.xlsx` (data Oracle) dan `cst_rm_cost` (data sistem baru).
> Setiap tabel diklasifikasikan: **WAJIB DIBUAT**, **PARTIAL**, **EMBEDDED**, atau **TIDAK PERLU**.

### 11.1 Tabel `cst_rm_cost` — Sudah Ada ✅

Tabel ini sudah ada di sistem baru dan merupakan pengganti `CST_GRP_CONSUMP_HEAD` Oracle.
Dipakai oleh handler `RM_RATE` (Store Rate) dan `OIL_RATE`.

**Struktur kolom yang relevan untuk engine:**

| Kolom | Tipe | Keterangan | Dipakai Engine |
|---|---|---|---|
| `rm_cost_id` | UUID | Primary key | — |
| `period` | VARCHAR(6) | Format YYYYMM | ✅ Filter by period |
| `rm_code` | VARCHAR | Kode grup item (Oracle format `202006xxx`) | ✅ Join key dari BOM component |
| `group_head_id` | UUID | Link ke group master | ⚠️ Perlu konfirmasi |
| `rm_name` | VARCHAR | Nama item (e.g. `DYE0000012`) | Info only |
| `cost_val` | NUMERIC | **Valuation rate** — setara `CGCH_MARKET_RATE2_FIX` Oracle | ✅ `RM_RATE` untuk VALUATION |
| `cost_mark` | NUMERIC | **Marketing rate** — setara `CGCH_MARKET_RATE1_FIX` Oracle | ✅ `RM_RATE` untuk MARKETING |
| `cost_sim` | NUMERIC | Simulation rate — semua 0 saat ini | Future use |
| `uom_code` | VARCHAR | Unit of measure | Info only |

**Kolom lain yang ada tapi tidak dipakai engine saat ini:**
`cons_rate`, `stores_rate`, `dept_rate`, `po_rate_1/2/3` — semua 0, belum populated.
`sl_rate`, `sp_rate`, `sr_rate`, `pp_rate`, `pr_rate`, `cl_rate`, `cr_rate` — duplikat/variant dari `cost_val`/`cost_mark`, tidak diperlukan engine.
`marketing_freight_rate`, `marketing_duty_pct`, `marketing_transport_rate` — komponen breakdown, 0-1 rows non-null.

**Cara engine menggunakan `cst_rm_cost`:**

```go
// handlers/raw_material.go — Store Rate subtype
func (h *RMHandler) getStoreRate(ctx context.Context,
    rmCode string, period string, pricingType PricingType) (float64, error) {

    var costVal, costMark float64
    err := h.db.QueryRow(ctx,
        `SELECT cost_val, cost_mark
         FROM cst_rm_cost
         WHERE rm_code = $1 AND period = $2`,
        rmCode, period,
    ).Scan(&costVal, &costMark)

    if err != nil {
        return 0, fmt.Errorf("cst_rm_cost: rm_code=%s period=%s: %w", rmCode, period, err)
    }

    if pricingType == PricingTypeValuation {
        return costVal, nil   // cost_val → VALUATION
    }
    return costMark, nil      // cost_mark → MARKETING
}
```

**⚠️ Pertanyaan yang perlu dikonfirmasi developer:**

1. **`rm_code` format** — masih pakai kode numerik Oracle (`202006xxx`). Di BOM Phase B (`cost_product_order_component`), komponen RM menyimpan key format apa? Harus sama agar join bisa dilakukan.
2. **`cost_val = 0` untuk 128 dari 320 item** — ini normal (item yang belum ada valuation rate) atau data belum lengkap? Engine perlu tahu policy: fallback ke `cost_mark`, atau produk yang depend ke RM ini otomatis `PARTIAL`?
3. **`group_head_id`** — UUID ini link ke tabel mana? Di Oracle ini `CGH_SYS_ID` dari `CST_GRP_HEAD`. Apakah ada tabel `cst_rm_group` atau sejenisnya di sistem baru?
4. **`sl_rate` vs `cost_val`** — berbeda di 2 item (max diff 0.046 untuk item SD). Mana source of truth untuk VALUATION? → Gunakan `cost_val`.

---

### 11.2 Master Tables yang WAJIB Dibuat (Belum Ada di ERD)

Empat tabel ini langsung dipanggil oleh formula handlers. Tanpa ini engine tidak bisa jalan.

---

#### `cost_master_machine` (CMM_)

**Pengganti:** `CST_MST_MACHINE` Oracle (103 rows, 25 cols)
**Dipakai oleh:** formula type `From_Master_Machine` → 12 params: `NO_OF_POSITION`, `NO_OF_END`, `POWER_PER_DAY`, `MANPOWER_PER_DAY`, `OVERHEAD_PER_HEAD`, `SPARESCOST_PER_DAY`, `CHANGE_OVER_QLTY_LOSS`, `VOLUME_BUCKET_1_QTY` s/d `VOLUME_BUCKET_5_QTY`

```sql
CREATE TABLE cost_master_machine (
    CMM_machine_id     UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMM_machine_code   VARCHAR(30)  NOT NULL UNIQUE,   -- e.g. 'BT-D', 'A2-8-S'
    CMM_machine_desc   VARCHAR(100),
    CMM_ends           INTEGER      NOT NULL,           -- No. of ends
    CMM_positions      INTEGER      NOT NULL,           -- No. of positions
    CMM_power          NUMERIC(20,6),                   -- Power cost/day (USD)
    CMM_manpower       NUMERIC(20,6),                   -- Manpower cost/day (USD)
    CMM_overhead       NUMERIC(20,6),                   -- Overhead cost/day (USD)
    CMM_spares         NUMERIC(20,6),                   -- Spares cost/day (USD)
    CMM_kgs_lost_change NUMERIC(20,6),                  -- Kg lost per machine changeover
    CMM_vb1            NUMERIC(20,6),                   -- Volume bucket 1 qty threshold
    CMM_vb2            NUMERIC(20,6),
    CMM_vb3            NUMERIC(20,6),
    CMM_vb4            NUMERIC(20,6),
    CMM_vb5            NUMERIC(20,6),
    CMM_is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    CMM_created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMM_created_by     VARCHAR(50),
    CMM_updated_at     TIMESTAMPTZ,
    CMM_updated_by     VARCHAR(50)
);
```

> **Kolom Oracle yang TIDAK perlu dimigrate:** `CMM_POY_BOBBIN_WEIGHT`, `CMM_BOX_COST`, `CMM_CAPTIVE_PER_BOBBIN`, `CMM_BOBBIN_PER_TROLLY`, `CMM_WEIGHTAGE` — tidak dipakai di calculation engine.

---

#### `cost_master_packing` (CMP_)

**Pengganti:** `CST_MST_BOX_BOBIN_COST` Oracle (68 rows, 14 cols)
**Dipakai oleh:** formula type `From_Box_Bobin_Cost` → 6 params: `CAPTIVE_PACK_CODE`, `CAPTIVE_BOB_RATE`, `CAPTIVE_BOX_RATE`, `DELIVERY_PACK_CODE`, `DELIVERY_BOB_RATE`, `DELIVERY_BOX_RATE`

```sql
CREATE TABLE cost_master_packing (
    CMP_packing_id     UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMP_type_code      VARCHAR(50)  NOT NULL UNIQUE,    -- e.g. 'T-H', 'T-H CAP', 'N-Jumbo Box'
    CMP_bbn_reuse      NUMERIC(10,4),                   -- Bobbins per box (reuse count)
    CMP_box_reuse      NUMERIC(10,4),                   -- Box reuse count
    -- Marketing rates
    CMP_box_cost_mkt   NUMERIC(20,6),                   -- Box cost (USD) — Marketing
    CMP_bobin_cost_mkt NUMERIC(20,6),                   -- Bobbin cost (USD) — Marketing
    -- Valuation rates (dari kolom _VAL Oracle — mayoritas null, perlu dikonfirmasi)
    CMP_box_cost_val   NUMERIC(20,6),                   -- Box cost (USD) — Valuation
    CMP_bobin_cost_val NUMERIC(20,6),                   -- Bobbin cost (USD) — Valuation
    CMP_is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    CMP_created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMP_created_by     VARCHAR(50),
    CMP_updated_at     TIMESTAMPTZ,
    CMP_updated_by     VARCHAR(50)
);
```

> **⚠️ Perlu konfirmasi:** Di Oracle ada kolom `CMBBC_BOBIN_COST_VAL` dan `CMBBC_BOX_COST_VAL` tapi mayoritas NULL. Apakah packing rate juga berbeda MKT vs VAL? Jika tidak, `CMP_box_cost_val` dan `CMP_bobin_cost_val` bisa dihapus dan cukup pakai satu rate saja.

---

#### `cost_master_intermingling` (CMI_)

**Pengganti:** `CST_YARN_MST_INTERMINGLING` Oracle (19 rows, 7 cols)
**Dipakai oleh:** formula type `Intermengling Data` → param `INTERMINGLING`
**Logic engine:** `INTERMINGLING = lookup(CMI_value) / 100`

```sql
CREATE TABLE cost_master_intermingling (
    CMI_id             UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMI_type_code      VARCHAR(30)  NOT NULL UNIQUE,    -- e.g. 'HIM', 'SIM', 'LIM', 'IM', 'NIM'
    CMI_description    VARCHAR(100) NOT NULL,            -- Full description
    CMI_value          NUMERIC(10,4) NOT NULL,           -- Value in % (e.g. 6.8 for HIM)
                                                         -- Engine uses: CMI_value / 100
    CMI_is_active      BOOLEAN      NOT NULL DEFAULT TRUE,
    CMI_created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMI_created_by     VARCHAR(50),
    CMI_updated_at     TIMESTAMPTZ,
    CMI_updated_by     VARCHAR(50)
);
```

**Seed data (19 rows dari Oracle):**

```sql
INSERT INTO cost_master_intermingling (CMI_type_code, CMI_description, CMI_value) VALUES
('HIM',           'HIM',                6.80),
('HTDH_HIM',      'HTDH HIM',           7.80),
('HCDH_HIM',      'HCDH HIM',           7.80),
('HT_HIM',        'HT HIM',             7.80),
('LTY_HIM',       'LTY HIM',            7.80),
('LTH',           'LTH',                7.80),
('HCSH_NIM',      'HCSH NIM',           1.00),
('LIM',           'LIM',                2.40),
('IM',            'IM',                 5.44),
('HIMD',          'HIMD',               7.50),
('HT_IM',         'HT IM',              6.44),
('IM_BSY',        'IM BSY',             6.80),
('BSY',           'BSY',                6.80),
('NIM',           'NIM',                0.00),  -- No Intermingling
('SIM',           'SIM',                3.00),
('ANN',           'ANN',                7.00),
('ACD_CA',        'ACD-CA(1100-2500)',  10.72),
('ARD_CA',        'ARD-CA(600-1100)',   10.13),
('AMD_CA',        'AMD-CA(250-600)',     8.26);
```

---

#### `cost_master_product_grade` (CMPG_)

**Pengganti:** `CST_MST_PRODUCT_GRADE` Oracle (40 rows, 11 cols)
**Dipakai oleh:** formula type `From_Product_Grade` → 4 params: `STD_VALUE_LOSS`, `VALUE_LOSS`, `NON_STD_SPECIAL_PROD`, `BC_SPECIAL_PROD`
**Logic engine:** lookup berdasarkan yarn type/description → dapatkan BC%, NS loss%, recovery rate

```sql
CREATE TABLE cost_master_product_grade (
    CMPG_grade_id         UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMPG_grade_code       VARCHAR(30)  NOT NULL UNIQUE,  -- e.g. 'Type 5 NS', 'Type 5 BC'
    CMPG_detail_product   VARCHAR(100),                   -- Product description match
    CMPG_grade_type       VARCHAR(10)  NOT NULL,          -- 'NS' | 'BC' | 'POY_BC' | 'SPCL'
    CMPG_std_selling_price NUMERIC(10,4),                 -- Standard selling price (USD/kg)
    CMPG_loss_pct         NUMERIC(10,4),                  -- Loss % (e.g. 0.05 = 5%)
    CMPG_sp_value         NUMERIC(10,4),                  -- SP value / recovery factor
    CMPG_seq_no           INTEGER,
    CMPG_is_active        BOOLEAN      NOT NULL DEFAULT TRUE,
    CMPG_created_at       TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMPG_created_by       VARCHAR(50),
    CMPG_updated_at       TIMESTAMPTZ,
    CMPG_updated_by       VARCHAR(50)
);

-- Index untuk lookup by product description
CREATE INDEX idx_cmpg_grade_type ON cost_master_product_grade(CMPG_grade_type, CMPG_is_active);
```

---

### 11.3 Master Tables PARTIAL (Dibuat tapi Hanya Kolom yang Dibutuhkan)

---

#### `cost_master_mb_head` (CMBH_)

**Pengganti:** `CST_MST_BATCH_HEAD` Oracle (4.169 rows, 50 cols — hanya butuh 6 kolom)
**Dipakai oleh:** formula type `From_Master_Batch_Data` → param `MB_RATE_MKT`
**Relevan untuk:** produk type `MEL` (Melange) saja

```sql
CREATE TABLE cost_master_mb_head (
    CMBH_mb_id         UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMBH_oracle_sys_id VARCHAR(30)  UNIQUE,              -- Original Oracle CMBH_SYS_ID (untuk migration)
    CMBH_mb_costing    VARCHAR(100) NOT NULL,             -- MB costing name (key lookup)
    CMBH_mgt_name      VARCHAR(100),                      -- MGT name / shade name
    CMBH_denier        NUMERIC(10,2),
    CMBH_filament      INTEGER,
    CMBH_dozing        NUMERIC(10,4),                     -- Dozing rate %
    CMBH_is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    CMBH_created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMBH_created_by    VARCHAR(50),
    CMBH_updated_at    TIMESTAMPTZ,
    CMBH_updated_by    VARCHAR(50)
);
```

> Dari 50 kolom Oracle, hanya 6 yang dibutuhkan engine. Kolom operasional (lot_no, date_run, approve_by, entry_status, dll) tidak perlu dimigrate.

---

#### `cost_master_mb_spin` (CMBS_)

**Pengganti:** `CST_MST_BATCH_SPIN` Oracle (2.679 rows, 38 cols — hanya butuh 7 kolom)
**Dipakai oleh:** formula type `From_Master_Batch_Spinning` → 6 params: `MB_SP_CODE`, `MB_SP_DYE`, `MB_SP_DENIER`, `MB_SP_FILAMENT`, `MB_SP_CC`, `MB_SP_DOZING`
**Relevan untuk:** produk type `MEL` (Melange) saja

```sql
CREATE TABLE cost_master_mb_spin (
    CMBS_spin_id       UUID         PRIMARY KEY DEFAULT gen_random_uuid(),
    CMBS_oracle_sys_id VARCHAR(30)  UNIQUE,              -- Original Oracle CMBS_SYS_ID
    CMBS_mb_head_id    UUID         NOT NULL REFERENCES cost_master_mb_head(CMBH_mb_id),
    CMBS_mgt_name      VARCHAR(100) NOT NULL,             -- Match ke CYL_SHADE_NAME produk
    CMBS_denier        NUMERIC(10,2),
    CMBS_filament      INTEGER,
    CMBS_dozing        NUMERIC(10,4),                     -- Dozing rate %
    CMBS_mb_costing    VARCHAR(100),                      -- MB costing reference
    CMBS_is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    CMBS_created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CMBS_created_by    VARCHAR(50),
    CMBS_updated_at    TIMESTAMPTZ,
    CMBS_updated_by    VARCHAR(50)
);

-- Index untuk lookup by mgt_name (dipakai engine saat match ke shade_name produk)
CREATE INDEX idx_cmbs_mgt_name ON cost_master_mb_spin(CMBS_mgt_name) WHERE CMBS_is_active = TRUE;
```

---

### 11.4 Tabel Oracle yang TIDAK Perlu Dibuat di Sistem Baru

| Oracle Table | Alasan Tidak Perlu |
|---|---|
| `CST_YARN_RM_HDR` | Konfigurasi RM per produk — sudah di-handle di BOM Phase B (`cost_product_order_component`) |
| `CST_YARN_RM_DTL` | Detail Store Rate per produk — engine langsung query `cst_rm_cost` via rm_code dari BOM |
| `CST_YARN_RM_CAPTIVE` | Captive linkage — sudah di-handle via upstream product di Phase B BOM |
| `CST_YARN_RM_MULTI` | Multi-yarn composition — sudah di-handle via BOM component dengan share_pct |
| `CST_YARN_FORMULA_CALC` | **9.557 rows** formula definition — digantikan sepenuhnya oleh Go handler functions. Ini saving migrasi terbesar. |

---

### 11.5 Summary Master Tables

| Tabel Baru | Pengganti Oracle | Rows | Status | Perlu Migration Data |
|---|---|---|---|---|
| `cst_rm_cost` | `CST_GRP_CONSUMP_HEAD` | 320+ | ✅ Sudah ada | — |
| `cost_master_machine` | `CST_MST_MACHINE` | 103 | ❌ Belum ada | ✅ Ya, semua 103 rows |
| `cost_master_packing` | `CST_MST_BOX_BOBIN_COST` | 68 | ❌ Belum ada | ✅ Ya, semua 68 rows |
| `cost_master_intermingling` | `CST_YARN_MST_INTERMINGLING` | 19 | ❌ Belum ada | ✅ Ya, seed SQL sudah ada di §11.2 |
| `cost_master_product_grade` | `CST_MST_PRODUCT_GRADE` | 40 | ❌ Belum ada | ✅ Ya, semua 40 rows |
| `cost_master_mb_head` | `CST_MST_BATCH_HEAD` | 4.169 | ❌ Belum ada | ⚠️ Partial — hanya 6 dari 50 kolom, hanya MEL type |
| `cost_master_mb_spin` | `CST_MST_BATCH_SPIN` | 2.679 | ❌ Belum ada | ⚠️ Partial — hanya 7 dari 38 kolom, hanya MEL type |

---

## 12. Updated Checklist Sebelum Mulai Coding

- [ ] **Gap 1:** Tambah `CPRM_formula_type` dan `CPRM_calc_seq` ke migration DDL
- [ ] **Gap 1:** Update `ERD_Master.md` — tambah dua kolom baru di `cost_parameter_master`
- [ ] **Gap 2:** Confirm struktur `cost_calculation_result` — Opsi C (hybrid) disarankan
- [ ] **cst_rm_cost:** Konfirmasi 4 pertanyaan di §11.1 dengan developer (rm_code format, cost_val=0 policy, group_head_id, sl_rate vs cost_val)
- [ ] **cost_master_machine:** Buat DDL + migration script dari `CST_MST_MACHINE`
- [ ] **cost_master_packing:** Buat DDL + konfirmasi apakah rate MKT/VAL berbeda
- [ ] **cost_master_intermingling:** Buat DDL + jalankan seed SQL di §11.2 (19 rows)
- [ ] **cost_master_product_grade:** Buat DDL + migration script dari `CST_MST_PRODUCT_GRADE`
- [ ] **cost_master_mb_head / mb_spin:** Buat DDL + partial migration (hanya kolom relevan, hanya MEL type products)
- [ ] Update `ERD_Master.md` — tambah 6 tabel baru ke prefix registry
- [ ] Seed data `CPRM_formula_type` + `CPRM_calc_seq` untuk 140 params

---

*Update: Juni 2026 — setelah review master data Oracle + tabel cst_rm_cost sistem baru*
