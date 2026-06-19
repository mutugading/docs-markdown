# 5. Layer 3 — Work Order

## Konsep

Work Order (WO) adalah **instruksi produksi konkret per mesin per lot** yang dikeluarkan PPC.
Menggantikan MLR (`PRD_TXT_MLR_ENTRY`).

```
WORK_ORDER (header)
    ├── WO_PARAMETER      (1:1)  parameter planned, diapprove PC
    ├── WO_EXECUTION      (1:N per date+shift)  parameter actual saat running
    ├── WO_PRODUCTION_ACTUAL (1:N per date+shift)  qty aktual dari ETL, editable
    └── WO_GRADE_ACTUAL   (1:N per grade)  packing aktual dari ETL
```

---

## WO Area

| Area | Kode | Parameter |
|---|---|---|
| Texturising | `TXT` | speed, nozzle, oil, disc, bar, OPU |
| Spinning | `SPG` | speed, spin pump |
| Twisting | `TWT` | speed, twist direction |

---

## WO Lifecycle

```
DRAFT → SUBMITTED → APPROVED → SCHEDULED → CHANGEOVER → RUNNING → COMPLETED → CLOSED
```

- **APPROVED** = PC + PM keduanya approve (atau auto-approve 4 jam)
- DRAFT: bisa auto-update/cancel jika plan berubah
- RUNNING+: read-only, perlu PM untuk cancel

---

## Dual Approval — Paralel

```
WO SUBMITTED
    ├── PC: review parameter teknis → APPROVE/REJECT (auto 4 jam)
    └── PM: review WO overall → APPROVE/REJECT (auto 4 jam)
WO APPROVED = keduanya selesai
```

---

## WO_PARAMETER — Parameter Planned (1:1 dengan WO)

Input oleh PPC berdasarkan historical data. Diapprove oleh PC.

| Field | Deskripsi | Area |
|---|---|---|
| `wop_speed` | Kecepatan (m/min) | TXT, TWT |
| `wop_nozzle` | Tipe nozzle | TXT |
| `wop_oil` | Tipe oli / OPU setting | TXT |
| `wop_disc` | Disc setting | TXT |
| `wop_bar` | Air pressure (bar) | TXT |
| `wop_opu` | Oil pick-up % | TXT |
| `wop_twist` | Twist direction (S/Z) dan level | TWT |

---

## WO_EXECUTION — Parameter Actual (1:N per date+shift)

Input oleh operator. Bisa berubah selama WO berlangsung — setiap ada perubahan
parameter, operator buat entry baru untuk date + shift tersebut.

```
Contoh: WO berjalan 5 hari
  Day 1 Shift 1: speed=750 (entry pertama)
  Day 2 Shift 2: speed=760 (speed naik → entry baru)
  Day 4 Shift 1: speed=750 (turun lagi → entry baru)
→ 3 records WO_EXECUTION untuk 1 WO
```

---

## WO_PRODUCTION_ACTUAL — Qty Aktual (1:N per date+shift)

**Source:** ETL dari Oracle summary tables (suggest otomatis)
**Editable:** Ya — operator atau PPC bisa koreksi dengan reason
**Granularity:** Per date + per shift (tidak sampai per doff)

```
Mirror struktur Oracle PPC_TXT_PRODUCTION / PPC_SPG_PRODUCTION
tapi sudah diagregasi ke level date + shift.

Suggest logic (priority):
  P1: WO_GRADE_ACTUAL ada (packing selesai) → qty dari total packing
  P2: NORMAL_BOBS dari PPC_TXT_PRODUCTION (QC released)
  P3: TRANSFERRED_BOBS dari PPC_SPG_PRODUCTION
  P4: TOTAL_BOBBINS dari PPC_TXT_PRODUCTION (semua transferred)
  P5: INCLUDE_IN_SUGGEST dari PPC_SPG_PRODUCTION (doff estimate)
```

**Kalkulasi qty TXT/TWT:**
```
⚠️ TRN_STS: 0=Full, 1=Unfull (KEBALIKAN dari SPG)
  Full (TRN_STS=0)  : count × lm_std_weight_full
  Unfull (TRN_STS=1): count × lm_std_weight_unfull
  calculated_qty_kg = (full_bobbins × std_full) + (unfull_bobbins × std_unfull)
```

**Kalkulasi qty SPG:**
```
calculated_qty_kg = transferred_bobs × weight_per_bob (DOFF_WT)
```

---

## WO_GRADE_ACTUAL — Packing Aktual (1:N per grade)

**Source:** ETL dari Oracle `PPC_GRADE_ACTUAL` → `MGTDAT.PPC_GRADE_ACTUAL`
**Granularity:** Per original_lot_no + grade
**Editable:** Tidak — ini data factual dari packing system Oracle

Grade AX/AE/A9/A/AM/APQ/B/BB/C/JLT dengan total qty kg dan bobbin count.

---

## RM Allocation

### Phase 1 — Manual Input PPC
```
Baris 1: POY 250/48 RND · Lot A2-16 · 1,000 kg · STORE · FIFO
Baris 2: POY 250/48 RND-S · Lot B1-22 · 500 kg · STORE · STRICT
```

### Phase 2 — Connect BOM Phase B
Auto-populate dari product route. PPC review dan adjust.

**RM Fence:** Warning 85%, Block > limit + 1 doff (TXT).

---

## TQM Logic TXT/TWT

TQM embedded di TXTTRANSFER — tidak ada tabel terpisah.
Status final = TYPE dan APP_REL dari **TRN_NO terbesar** per posisi.

```
TYPE=1 → APP_REL=2          → NORMAL
TYPE=1 → APP_REL=1          → TYPE=6 → APP_REL=2 → NORMAL (lulus retest)
TYPE=1 → APP_REL=1          → TYPE=6 → APP_REL=1 → TYPE=7 → DOWNGRADE FINAL
APP_REL=1 tanpa lanjutan    → PENDING (di-hold TQM)
```

---

## Over-Production Threshold

**5-level:** WO override → Product → Product type → Machine group → System default

- Default: Warning 3%, Block 6%
- TXT: absolut 1 doff (TXT-1: 1,200 kg · TXT-2: 600 kg)

---

## Schema

```sql
-- ─────────────────────────────────────────────
-- WORK ORDER (header)
-- ─────────────────────────────────────────────
CREATE TABLE work_order (
    wo_id                    BIGSERIAL PRIMARY KEY,
    wo_area_code             CHAR(3)       NOT NULL,  -- TXT / SPG / TWT
    wo_trans_no              VARCHAR(30)   NOT NULL UNIQUE,
    wo_plan_item_id          BIGINT        NOT NULL,
    wo_machine_id            BIGINT        NOT NULL,
    wo_lot_no                VARCHAR(30)   NOT NULL,
    wo_lot_remark            CHAR(3),                  -- NEW / OLD
    wo_qty_target            DECIMAL(18,3) NOT NULL,
    wo_deadline              DATE          NOT NULL,
    wo_grade_req_ref         BIGINT,                   -- FK ke PRODUCTION_DEMAND
    wo_packing_box_type      VARCHAR(10),
    wo_packing_pallet_type   VARCHAR(10),
    wo_status                VARCHAR(20)   NOT NULL,
    wo_ref_id                BIGINT,                   -- FK ke WO sebelumnya (revision)
    wo_revision_no           SMALLINT      NOT NULL DEFAULT 0,
    wo_pc_approved_at        TIMESTAMPTZ,
    wo_pc_approved_by        BIGINT,
    wo_pm_approved_at        TIMESTAMPTZ,
    wo_pm_approved_by        BIGINT,
    wo_qty_final             DECIMAL(18,3),            -- qty final (sum dari WO_PRODUCTION_ACTUAL)
    wo_qty_final_locked_at   TIMESTAMPTZ,
    wo_plan_change_flag      BOOLEAN       DEFAULT FALSE,
    wo_plan_change_note      TEXT,
    wo_created_by            BIGINT        NOT NULL,
    wo_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    wo_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- WO_PARAMETER (1:1 dengan WO)
-- Parameter planned, diinput PPC, diapprove PC
-- ─────────────────────────────────────────────
CREATE TABLE wo_parameter (
    wop_id                   BIGSERIAL PRIMARY KEY,
    wop_wo_id                BIGINT        NOT NULL UNIQUE,
    wop_speed                DECIMAL(8,2),
    wop_nozzle               VARCHAR(20),
    wop_oil                  VARCHAR(30),
    wop_disc                 VARCHAR(20),
    wop_bar                  DECIMAL(5,2),
    wop_air                  VARCHAR(20),
    wop_opu                  DECIMAL(6,3),
    wop_twist                VARCHAR(20),
    wop_notes                TEXT,
    wop_pc_approved_by       BIGINT,
    wop_pc_approved_at       TIMESTAMPTZ
);

-- ─────────────────────────────────────────────
-- WO_EXECUTION (1:N per date+shift)
-- Parameter actual saat running, input operator
-- Bisa ada multiple records per WO jika parameter berubah
-- ─────────────────────────────────────────────
CREATE TABLE wo_execution (
    woe_id                   BIGSERIAL PRIMARY KEY,
    woe_wo_id                BIGINT        NOT NULL,
    woe_date                 DATE          NOT NULL,  -- tanggal parameter berlaku
    woe_shift                CHAR(1)       NOT NULL,  -- 1 / 2 / 3
    woe_speed_actual         DECIMAL(8,2),
    woe_nozzle_actual        VARCHAR(20),
    woe_oil_actual           VARCHAR(30),
    woe_disc_actual          VARCHAR(20),
    woe_bar_actual           DECIMAL(5,2),
    woe_air_actual           VARCHAR(20),
    woe_opu_actual           DECIMAL(6,3),
    woe_twist_actual         VARCHAR(20),
    woe_notes                TEXT,
    woe_input_by             BIGINT,
    woe_input_at             TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- WO_PRODUCTION_ACTUAL (1:N per date+shift)
-- Qty aktual produksi — suggest dari ETL Oracle, editable
-- Granularity: per date + shift (agregasi, tidak per doff)
-- ─────────────────────────────────────────────
CREATE TABLE wo_production_actual (
    wpa_id                   BIGSERIAL PRIMARY KEY,
    wpa_wo_id                BIGINT        NOT NULL,
    wpa_date                 DATE          NOT NULL,  -- tanggal produksi
    wpa_shift                CHAR(1)       NOT NULL,  -- 1 / 2 / 3
    wpa_area                 CHAR(3)       NOT NULL,  -- TXT / SPG / TWT

    -- TXT/TWT columns
    -- ⚠️ TRN_STS: 0=Full, 1=Unfull (KEBALIKAN dari SPG DOFF_OPTION)
    wpa_total_bobbins        INT,
    wpa_full_bobbins         INT,          -- TRN_STS=0
    wpa_unfull_bobbins       INT,          -- TRN_STS=1
    wpa_normal_bobs          INT,          -- TQM lulus (TYPE!=7, APP_REL=2)
    wpa_downgrade_bobs       INT,          -- TQM final defect (TYPE=7)
    wpa_pending_bobs         INT,          -- masih di-hold TQM
    wpa_pack_cek_bobs        INT,          -- handover ke packing

    -- SPG columns
    wpa_gross_bobbins        INT,          -- dari DOFFCONT
    wpa_transferred_bobs     INT,          -- TRN_TYPE!=4, TRN_STATUS=2
    wpa_cut_bobbins          INT,          -- TRN_TYPE=4
    wpa_not_transfer         INT,          -- belum ada di TRANSFER
    wpa_normal_bobs_spg      INT,          -- TQM_GRADE=1
    wpa_downgrade_bobs_spg   INT,          -- TQM_GRADE=0
    wpa_not_checked_bobs     INT,          -- TRN_APP_REL_DT IS NULL
    wpa_weight_per_bob       DECIMAL(10,4), -- DOFF_WT dari DOFFCONT

    -- Calculated qty (hasil kalkulasi dari bobbin count × std_weight)
    wpa_calculated_qty_kg    DECIMAL(18,3), -- auto-calculated dari bobbin count
    wpa_qty_source           VARCHAR(20),   -- ETL_SUGGEST / MANUAL_OVERRIDE
    wpa_manual_reason        TEXT,          -- alasan jika MANUAL_OVERRIDE

    -- ETL metadata
    wpa_sync_status          VARCHAR(20)   DEFAULT 'OK', -- OK / SYNC_FAILED / PENDING
    wpa_synced_at            TIMESTAMPTZ,
    wpa_last_edited_by       BIGINT,
    wpa_last_edited_at       TIMESTAMPTZ,

    UNIQUE (wpa_wo_id, wpa_date, wpa_shift)
);

-- ─────────────────────────────────────────────
-- WO_GRADE_ACTUAL (1:N per grade)
-- Packing aktual dari ETL PPC_GRADE_ACTUAL Oracle
-- ─────────────────────────────────────────────
CREATE TABLE wo_grade_actual (
    wga_id                   BIGSERIAL PRIMARY KEY,
    wga_wo_id                BIGINT        NOT NULL,
    wga_lot_no               VARCHAR(30)   NOT NULL,  -- original lot
    wga_grade                VARCHAR(5)    NOT NULL,  -- AX/AE/A9/A/AM/APQ/B/BB/C/JLT
    wga_dept                 CHAR(3),                  -- TXT / TWT
    wga_total_qty_kg         DECIMAL(14,3),
    wga_bobbin_count         INT,
    wga_last_packing_date    DATE,
    wga_synced_at            TIMESTAMPTZ   DEFAULT NOW(),

    UNIQUE (wga_wo_id, wga_lot_no, wga_grade)
);

-- Supporting tables
CREATE TABLE wo_plan_item_link (
    wpl_id                   BIGSERIAL PRIMARY KEY,
    wpl_wo_id                BIGINT        NOT NULL,
    wpl_plan_item_id         BIGINT        NOT NULL,
    wpl_qty_contribution     DECIMAL(18,3),
    UNIQUE (wpl_wo_id, wpl_plan_item_id)
);

CREATE TABLE wo_rm_allocation (
    wra_id                   BIGSERIAL PRIMARY KEY,
    wra_wo_id                BIGINT        NOT NULL,
    wra_bom_component_id     BIGINT,                  -- FK ke BOM Phase B (nullable Phase 1)
    wra_lot_no               VARCHAR(30)   NOT NULL,
    wra_qty_allocated        DECIMAL(18,3) NOT NULL,
    wra_lot_picking_mode     VARCHAR(10),              -- STRICT / FLEXIBLE / FIFO
    wra_rm_source            VARCHAR(10),              -- STORE / CAPTIVE / MIXED
    wra_notes                TEXT
);

CREATE TABLE wo_actual_log (
    wal_id                   BIGSERIAL PRIMARY KEY,
    wal_wo_id                BIGINT        NOT NULL,
    wal_wpa_id               BIGINT,                  -- FK ke WO_PRODUCTION_ACTUAL (nullable)
    wal_qty_before           DECIMAL(18,3),
    wal_qty_after            DECIMAL(18,3),
    wal_source_before        VARCHAR(20),
    wal_source_after         VARCHAR(20),
    wal_reason               TEXT,
    wal_edited_by            BIGINT        NOT NULL,
    wal_edited_at            TIMESTAMPTZ   DEFAULT NOW()
);
```

---

## Mapping MLR → Sistem Baru

| MLR Entity | Sistem Baru | Keterangan |
|---|---|---|
| Header MLR (lot, mesin) | `WORK_ORDER` | WO = header instruksi |
| Parameter mesin (speed, nozzle, dll) | `WO_PARAMETER` + `WO_EXECUTION` | Planned vs actual |
| Qty produksi per shift | `WO_PRODUCTION_ACTUAL` | Suggest dari ETL, editable |
| Packing data | `WO_GRADE_ACTUAL` | Dari ETL packing Oracle |

**Phase 1:** MLR Oracle tetap jalan paralel. Sistem PPC menggunakan data yang
sama (dari ETL Oracle summary tables) sebagai suggest. User bisa koreksi di PPC.

**Phase 2+:** MLR bisa diretire secara bertahap setelah adoption stabil.
