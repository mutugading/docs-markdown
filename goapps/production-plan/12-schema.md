# 12. Schema Lengkap (ERD Reference)

PostgreSQL DDL semua tabel — siap untuk developer.

> ⚠️ **TRN_STS TXT/TWT:** 0=Full bobbin, 1=Unfull bobbin (KEBALIKAN dari SPG DOFF_OPTION: 1=Full)

---

## Core Planning Tables

```sql
-- ─────────────────────────────────────────────
-- SALES ORDER STAGING (ETL dari MGT_SO_PENDING_WEB Oracle)
-- ─────────────────────────────────────────────
CREATE TABLE sales_order_staging (
    sos_id                   BIGSERIAL PRIMARY KEY,
    sos_contract_no          VARCHAR(50),
    sos_contract_date        DATE,
    sos_contract_sys_id      BIGINT,
    sos_customer_code        VARCHAR(20),
    sos_customer_name        VARCHAR(100),
    sos_item_code            VARCHAR(30),
    sos_item_desc            VARCHAR(100),
    sos_grade_code           VARCHAR(20),
    sos_shade_code           VARCHAR(20),
    sos_shade_name           VARCHAR(100),
    sos_qty_ordered          DECIMAL(18,3),
    sos_qty_delivered        DECIMAL(18,3),
    sos_qty_remaining        DECIMAL(18,3),
    sos_deadline             DATE,
    sos_ship_date            VARCHAR(20),
    sos_merge_no             VARCHAR(20),
    sos_term                 VARCHAR(20),
    sos_rate                 DECIMAL(12,4),
    sos_currency             VARCHAR(5),
    sos_blocked_status       VARCHAR(50),
    sos_outstanding_ar       DECIMAL(18,2),
    sos_pallet_type          VARCHAR(20),
    sos_end_use              VARCHAR(50),
    sos_mix_flag             VARCHAR(1),
    sos_annotation           VARCHAR(200),
    sos_remarks              VARCHAR(200),
    sos_etl_synced_at        TIMESTAMPTZ,
    sos_pulled_to_demand_id  BIGINT        -- NULL=available di LOV, FK=sudah di-pull
);

-- ─────────────────────────────────────────────
-- LAYER 1: PRODUCTION DEMAND
-- ─────────────────────────────────────────────
CREATE TABLE production_demand (
    pd_id                    BIGSERIAL PRIMARY KEY,
    pd_type                  VARCHAR(20)   NOT NULL,  -- CONTRACT / MTS / SAMPLE
    pd_sub_type              VARCHAR(20),              -- CF_EXPORT / NEW_EXPORT / LOCAL / INTERNAL
    pd_source                VARCHAR(20)   NOT NULL,  -- ORION_PULL / MANUAL / MTS_APPROVED / CARRY_FORWARD
    pd_carry_action          VARCHAR(20),              -- CARRY_AS_IS / SPLIT / DEFER / PARTIAL_CARRY
    pd_cpm_product_sys_id    BIGINT        NOT NULL,
    pd_qty_original          DECIMAL(18,3) NOT NULL,
    pd_qty_remaining         DECIMAL(18,3) NOT NULL,
    pd_deadline              DATE          NOT NULL,
    pd_customer_id           BIGINT,
    pd_contract_no           VARCHAR(50),
    pd_contract_date         DATE,
    pd_stuff_advance_no      VARCHAR(50),
    pd_incoterm              VARCHAR(10),
    pd_lc_status             VARCHAR(30),
    pd_grade_requirement     VARCHAR(20),              -- AX_ONLY / AX_AM_CLAUSE / NONE
    pd_ax_min_pct            DECIMAL(5,2),
    pd_am_max_pct            DECIMAL(5,2),
    pd_carry_from_id         BIGINT        REFERENCES production_demand(pd_id),
    pd_sos_ref               BIGINT        REFERENCES sales_order_staging(sos_id),
    pd_status                VARCHAR(30)   NOT NULL,
    pd_month                 CHAR(7)       NOT NULL,  -- YYYY-MM
    pd_confirmed_by          BIGINT,
    pd_confirmed_at          TIMESTAMPTZ,
    pd_created_by            BIGINT        NOT NULL,
    pd_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    pd_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- LAYER 2: PRODUCTION PLAN ITEM
-- ─────────────────────────────────────────────
CREATE TABLE production_plan_item (
    ppi_id                    BIGSERIAL PRIMARY KEY,
    ppi_cpm_product_sys_id    BIGINT        NOT NULL,
    ppi_type                  VARCHAR(20)   NOT NULL,  -- FG_DELIVERY / INTERMEDIATE / MTS
    ppi_demand_id             BIGINT        REFERENCES production_demand(pd_id),
    ppi_parent_item_id        BIGINT        REFERENCES production_plan_item(ppi_id),
    -- CHECK: ppi_demand_id IS NOT NULL OR ppi_parent_item_id IS NOT NULL
    ppi_qty_target            DECIMAL(18,3) NOT NULL,
    ppi_deadline              DATE          NOT NULL,
    ppi_rm_source             VARCHAR(10),              -- STORE / CAPTIVE / MIXED
    ppi_sequence              INT           NOT NULL DEFAULT 0,
    ppi_status                VARCHAR(20)   NOT NULL,
    ppi_machine_group_id      BIGINT        NOT NULL,
    ppi_preferred_machine_id  BIGINT,
    ppi_month                 CHAR(7)       NOT NULL,  -- YYYY-MM
    ppi_notes                 TEXT,
    ppi_created_by            BIGINT        NOT NULL,
    ppi_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    ppi_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE production_plan_log (
    ppl_id                    BIGSERIAL PRIMARY KEY,
    ppl_plan_item_id          BIGINT        NOT NULL,
    ppl_field_changed         VARCHAR(50)   NOT NULL,
    ppl_value_before          TEXT,
    ppl_value_after           TEXT,
    ppl_changed_by            BIGINT        NOT NULL,
    ppl_changed_at            TIMESTAMPTZ   DEFAULT NOW(),
    ppl_reason                TEXT
);

-- ─────────────────────────────────────────────
-- LAYER 3: WORK ORDER
-- ─────────────────────────────────────────────
CREATE TABLE work_order (
    wo_id                    BIGSERIAL PRIMARY KEY,
    wo_area_code             CHAR(3)       NOT NULL,  -- TXT / SPG / TWT
    wo_trans_no              VARCHAR(30)   NOT NULL UNIQUE,
    wo_plan_item_id          BIGINT        NOT NULL,
    wo_machine_id            BIGINT        NOT NULL,
    wo_lot_no                VARCHAR(30)   NOT NULL,
    wo_lot_remark            CHAR(3),                  -- NEW / OLD
    wo_lot_ref               VARCHAR(30),
    wo_qty_target            DECIMAL(18,3) NOT NULL,
    wo_deadline              DATE          NOT NULL,
    wo_grade_req_ref         BIGINT,
    wo_packing_box_type      VARCHAR(10),
    wo_packing_pallet_type   VARCHAR(10),
    wo_status                VARCHAR(20)   NOT NULL,
    wo_ref_id                BIGINT        REFERENCES work_order(wo_id),
    wo_revision_no           SMALLINT      NOT NULL DEFAULT 0,
    wo_pc_approved_at        TIMESTAMPTZ,
    wo_pc_approved_by        BIGINT,
    wo_pm_approved_at        TIMESTAMPTZ,
    wo_pm_approved_by        BIGINT,
    wo_qty_actual            DECIMAL(18,3),
    wo_qty_source            VARCHAR(20),              -- CALCULATED / MANUAL_OVERRIDE
    wo_qty_calculated        DECIMAL(18,3),
    wo_qty_manual_reason     TEXT,
    wo_qty_final_locked_at   TIMESTAMPTZ,
    wo_bobbin_sync_status    VARCHAR(20),              -- OK / SYNC_FAILED / PENDING
    wo_bobbin_sync_at        TIMESTAMPTZ,
    wo_plan_change_flag      BOOLEAN       DEFAULT FALSE,
    wo_plan_change_note      TEXT,
    wo_created_by            BIGINT        NOT NULL,
    wo_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    wo_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

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
    wop_pc_approved_by       BIGINT,
    wop_pc_approved_at       TIMESTAMPTZ
);

CREATE TABLE wo_execution (
    woe_id                   BIGSERIAL PRIMARY KEY,
    woe_wo_id                BIGINT        NOT NULL,
    woe_shift                CHAR(1)       NOT NULL,  -- 1 / 2 / 3
    woe_executed_by          BIGINT        NOT NULL,
    woe_speed_actual         DECIMAL(8,2),
    woe_nozzle_actual        VARCHAR(20),
    woe_oil_actual           VARCHAR(30),
    woe_disc_actual          VARCHAR(20),
    woe_bar_actual           DECIMAL(5,2),
    woe_air_actual           VARCHAR(20),
    woe_opu_actual           DECIMAL(6,3),
    woe_twist_actual         VARCHAR(20),
    woe_notes                TEXT,
    woe_input_at             TIMESTAMPTZ   DEFAULT NOW()
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

CREATE TABLE wo_plan_item_link (
    wpl_id                   BIGSERIAL PRIMARY KEY,
    wpl_wo_id                BIGINT        NOT NULL,
    wpl_plan_item_id         BIGINT        NOT NULL,
    wpl_qty_contribution     DECIMAL(18,3),
    UNIQUE (wpl_wo_id, wpl_plan_item_id)
);

CREATE TABLE wo_actual_log (
    wal_id                   BIGSERIAL PRIMARY KEY,
    wal_wo_id                BIGINT        NOT NULL,
    wal_qty_before           DECIMAL(18,3),
    wal_qty_after            DECIMAL(18,3),
    wal_source_before        VARCHAR(20),
    wal_source_after         VARCHAR(20),
    wal_reason               TEXT,
    wal_edited_by            BIGINT        NOT NULL,
    wal_edited_at            TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- WO_PRODUCTION_ACTUAL (1:N per date+shift)
-- Qty aktual produksi — suggest dari ETL Oracle, editable
-- Granularity: per date + shift (agregasi, TIDAK per doff)
-- Duplikasi kolom dari Oracle summary tables untuk TXT/TWT dan SPG
-- ─────────────────────────────────────────────
CREATE TABLE wo_production_actual (
    wpa_id                   BIGSERIAL PRIMARY KEY,
    wpa_wo_id                BIGINT        NOT NULL,
    wpa_date                 DATE          NOT NULL,   -- tanggal produksi
    wpa_shift                CHAR(1)       NOT NULL,   -- 1 / 2 / 3
    wpa_area                 CHAR(3)       NOT NULL,   -- TXT / SPG / TWT

    -- TXT/TWT columns (dari PPC_TXT_PRODUCTION Oracle, agregasi per date+shift)
    -- ⚠️ TRN_STS: 0=Full, 1=Unfull (KEBALIKAN dari SPG DOFF_OPTION)
    wpa_total_bobbins        INT,
    wpa_full_bobbins         INT,           -- TRN_STS=0 (Full)
    wpa_unfull_bobbins       INT,           -- TRN_STS=1 (Unfull)
    wpa_normal_bobs          INT,           -- TQM lulus (FINAL_TYPE!=7, APP_REL=2)
    wpa_downgrade_bobs       INT,           -- TQM final defect (TYPE=7)
    wpa_pending_bobs         INT,           -- masih di-hold TQM
    wpa_pack_cek_bobs        INT,           -- handover ke packing

    -- SPG columns (dari PPC_SPG_PRODUCTION Oracle, agregasi per date+shift)
    wpa_gross_bobbins        INT,           -- semua keluar mesin (DOFFCONT)
    wpa_transferred_bobs     INT,           -- TRN_TYPE!=4, TRN_STATUS=2
    wpa_cut_bobbins          INT,           -- TRN_TYPE=4 (dipotong)
    wpa_not_transfer         INT,           -- belum ada di TRANSFER
    wpa_normal_bobs_spg      INT,           -- TQM_GRADE=1
    wpa_downgrade_bobs_spg   INT,           -- TQM_GRADE=0
    wpa_not_checked_bobs     INT,           -- TRN_APP_REL_DT IS NULL
    wpa_weight_per_bob       DECIMAL(10,4), -- DOFF_WT (rata-rata shift)

    -- Calculated qty (hasil kalkulasi dari bobbin count × std_weight)
    wpa_calculated_qty_kg    DECIMAL(18,3), -- auto-calculated
    wpa_qty_source           VARCHAR(20),   -- ETL_SUGGEST / MANUAL_OVERRIDE
    wpa_manual_reason        TEXT,          -- alasan jika MANUAL_OVERRIDE

    -- ETL metadata
    wpa_sync_status          VARCHAR(20)    DEFAULT 'OK',  -- OK / SYNC_FAILED / PENDING
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
    wga_lot_no               VARCHAR(30)   NOT NULL,   -- original lot
    wga_grade                VARCHAR(5)    NOT NULL,   -- AX/AE/A9/A/AM/APQ/B/BB/C/JLT
    wga_dept                 CHAR(3),                   -- TXT / TWT
    wga_total_qty_kg         DECIMAL(14,3),
    wga_bobbin_count         INT,
    wga_last_packing_date    DATE,
    wga_synced_at            TIMESTAMPTZ   DEFAULT NOW(),

    UNIQUE (wga_wo_id, wga_lot_no, wga_grade)
);

-- ─────────────────────────────────────────────
-- CHANGEOVER
-- ─────────────────────────────────────────────
CREATE TABLE changeover_event (
    ce_id                    BIGSERIAL PRIMARY KEY,
    ce_from_wo_id            BIGINT        NOT NULL,
    ce_to_wo_id              BIGINT        NOT NULL,
    ce_machine_id            BIGINT        NOT NULL,
    ce_duration_estimated    INT,                      -- menit
    ce_waste_estimated       DECIMAL(10,3),            -- kg
    ce_group                 VARCHAR(10),              -- MINOR/MEDIUM/MAJOR/DEEP
    ce_duration_actual       INT,
    ce_waste_actual          DECIMAL(10,3),
    ce_status                VARCHAR(20),              -- PLANNED/IN_PROGRESS/DONE
    ce_started_at            TIMESTAMPTZ,
    ce_completed_at          TIMESTAMPTZ,
    ce_notes                 TEXT
);

CREATE TABLE changeover_component (
    cc_id                    BIGSERIAL PRIMARY KEY,
    cc_event_id              BIGINT        NOT NULL,
    cc_component_code        CHAR(5)       NOT NULL,   -- BASE/C1–C7
    cc_duration_applied      INT           NOT NULL,
    cc_waste_applied         DECIMAL(10,3) NOT NULL,
    cc_is_auto_detected      BOOLEAN       DEFAULT TRUE,
    cc_override_by           BIGINT,
    cc_override_at           TIMESTAMPTZ
);

-- ─────────────────────────────────────────────
-- MASTER DATA
-- ─────────────────────────────────────────────
CREATE TABLE product_ppc_config (
    ppc_id                   BIGSERIAL PRIMARY KEY,
    ppc_cpm_product_sys_id   BIGINT        NOT NULL UNIQUE,
    ppc_is_commodity_watch   BOOLEAN       DEFAULT FALSE,
    ppc_price_sell           DECIMAL(12,2),
    ppc_machine_group_id     BIGINT,
    ppc_yield_std            DECIMAL(5,3),
    ppc_buffer_rm_pct        DECIMAL(5,3),
    ppc_ax_yield_pct         DECIMAL(5,3),             -- historical %AX (0.75–0.84)
    ppc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE product_machine_capacity (
    pmc_id                   BIGSERIAL PRIMARY KEY,
    pmc_cpm_product_sys_id   BIGINT        NOT NULL,
    pmc_machine_id           BIGINT        NOT NULL,
    pmc_speed                DECIMAL(8,2),
    pmc_no_of_positions      INT,
    pmc_prod_per_day         DECIMAL(10,3),
    pmc_efficiency_pct       DECIMAL(5,2),
    pmc_draw_ratio           DECIMAL(6,3),
    pmc_updated_at           TIMESTAMPTZ   DEFAULT NOW(),
    UNIQUE (pmc_cpm_product_sys_id, pmc_machine_id)
);

CREATE TABLE machine_group (
    group_id                 BIGSERIAL PRIMARY KEY,
    group_name               VARCHAR(50)   NOT NULL,
    group_area               CHAR(3)       NOT NULL
);

CREATE TABLE machine_master (
    machine_id               BIGSERIAL PRIMARY KEY,
    machine_no               VARCHAR(10)   NOT NULL UNIQUE,
    machine_area             CHAR(3)       NOT NULL,   -- TXT / SPG / TWT
    machine_line             VARCHAR(20),
    machine_group_id         BIGINT,
    machine_doff_weight_kg   DECIMAL(8,3),
    machine_is_active        BOOLEAN       DEFAULT TRUE,
    machine_orion_code       VARCHAR(30)
);

CREATE TABLE lot_master (
    lm_lot_no                VARCHAR(30)   PRIMARY KEY,
    lm_item_code             VARCHAR(30)   NOT NULL,
    lm_shade_code            VARCHAR(20)   NOT NULL,
    lm_std_weight_full       DECIMAL(8,4)  NOT NULL,   -- TXT: TRN_STS=0
    lm_std_weight_unfull     DECIMAL(8,4)  NOT NULL,   -- TXT: TRN_STS=1
    lm_notes                 TEXT,
    lm_created_by_ppc        BIGINT        NOT NULL,
    lm_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    lm_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE overrun_threshold_config (
    otc_id                   BIGSERIAL PRIMARY KEY,
    otc_level                VARCHAR(20)   NOT NULL,   -- SYSTEM/MACHINE_GROUP/PRODUCT_TYPE/PRODUCT/WO
    otc_ref_id               BIGINT,
    otc_threshold_unit       CHAR(4)       NOT NULL,   -- PCT / DOFF
    otc_warning_value        DECIMAL(10,3) NOT NULL,
    otc_block_value          DECIMAL(10,3) NOT NULL,
    otc_notes                TEXT,
    otc_is_active            BOOLEAN       DEFAULT TRUE,
    otc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE common_lot (
    cl_id                    BIGSERIAL PRIMARY KEY,
    cl_lot_no                VARCHAR(30)   NOT NULL UNIQUE,
    cl_item_code             VARCHAR(30),
    cl_shade_code            VARCHAR(20),
    cl_erp_grade_code        VARCHAR(5),
    cl_created_at            TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE common_lot_component (
    clc_id                   BIGSERIAL PRIMARY KEY,
    clc_common_lot_id        BIGINT        NOT NULL,
    clc_original_lot_no      VARCHAR(30)   NOT NULL,
    clc_original_shade_code  VARCHAR(20),
    clc_bobbin_count         INT,
    clc_qty_kg               DECIMAL(10,3)
);
```

---

## Mapping Excel Sheet → Tabel Sistem

| Sheet Excel | Digantikan oleh |
|---|---|
| `00_SUMMARY` | Dashboard + `product_ppc_config` + agregasi dari `wo_production_actual` |
| `SUMMARY vs ORDER` | `production_demand` All Demands tab |
| `02_Sales_Plan` | `production_demand` |
| `CF_EX`, `NEW_EX`, `LOCAL` | `production_demand` carry-forward flow |
| `01_PROD` | `product_ppc_config` + `product_machine_capacity` |
| `04_datewise` | `production_plan_item` + `work_order` (Gantt view) |
| `03_COPlan` | `changeover_event` + `changeover_component` |
| `PREV_PLAN` | Start New Month workflow (carry-forward candidates) |
| `STOCK` | Inventory sync ETL dari Orion (read-only) |

---

*PRD dibuat Juni 2026 via sesi brainstorming di Claude AI*
*Version: Draft v1.0*
