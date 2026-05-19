-- ============================================================================
-- Costing Workflow Suite — Phase C
-- Calculation Engine, Period Management, Dynamic Parameters, Cost Results
-- DDL — PostgreSQL 14+
-- Version: 1.0 | May 2026
-- Convention: Column Prefix Naming
-- ============================================================================

-- ============================================================================
-- PREFIX REGISTRY — PHASE C
-- ============================================================================
-- CCP_   → cost_calculation_period
-- CCR_   → cost_calculation_run
-- CCRE_  → cost_calculation_result
-- CPPP_  → cost_product_parameter_period
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. cost_calculation_period (CCP_)
-- Period lifecycle management. Only 1 OPEN period at a time (enforced at app level).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_calculation_period (
  CCP_period_id             SERIAL          PRIMARY KEY,
  CCP_period                VARCHAR(6)      NOT NULL UNIQUE,    -- "202605"
  CCP_status                VARCHAR(20)     NOT NULL DEFAULT 'OPEN',

  -- Audit
  CCP_opened_at             TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CCP_opened_by             VARCHAR(64)     NOT NULL,
  CCP_closed_at             TIMESTAMPTZ,
  CCP_closed_by             VARCHAR(64),
  CCP_close_reason          TEXT,

  CONSTRAINT chk_ccp_status
    CHECK (CCP_status IN ('OPEN', 'CLOSED')),

  -- Period format validation: YYYYMM
  CONSTRAINT chk_ccp_period_format
    CHECK (CCP_period ~ '^[0-9]{4}(0[1-9]|1[0-2])$'),

  -- Close fields wajib jika status CLOSED
  CONSTRAINT chk_ccp_close_fields
    CHECK (
      CCP_status <> 'CLOSED'
      OR (CCP_closed_at IS NOT NULL AND CCP_closed_by IS NOT NULL)
    )
);

COMMENT ON TABLE cost_calculation_period
  IS 'Period lifecycle (YYYYMM). Manual create by Admin. Only 1 OPEN at a time.';

-- Partial unique index: hanya 1 OPEN period
CREATE UNIQUE INDEX idx_ccp_one_open
  ON cost_calculation_period(CCP_status)
  WHERE CCP_status = 'OPEN';


-- ----------------------------------------------------------------------------
-- 2. cost_calculation_run (CCR_)
-- Execution log. Multiple runs per OPEN period; only 1 is "active" at a time.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_calculation_run (
  CCR_run_id                BIGSERIAL       PRIMARY KEY,
  CCR_period                VARCHAR(6)      NOT NULL
                            REFERENCES cost_calculation_period(CCP_period),
  CCR_run_seq               INT             NOT NULL,           -- 1, 2, 3, ... per period
  CCR_is_active             BOOLEAN         NOT NULL DEFAULT false,

  -- Trigger info
  CCR_trigger_type          VARCHAR(20)     NOT NULL,           -- MANUAL / SCHEDULED
  CCR_triggered_by          VARCHAR(64)     NOT NULL,           -- user_id atau "system"

  -- Status
  CCR_status                VARCHAR(20)     NOT NULL DEFAULT 'PENDING',
  -- PENDING → RUNNING → SUCCESS / FAILED / PARTIAL / CANCELLED

  -- Counters
  CCR_total_products        INT             NOT NULL DEFAULT 0,
  CCR_success_count         INT             NOT NULL DEFAULT 0,
  CCR_partial_count         INT             NOT NULL DEFAULT 0,
  CCR_failed_count          INT             NOT NULL DEFAULT 0,

  -- Timing
  CCR_started_at            TIMESTAMPTZ,
  CCR_ended_at              TIMESTAMPTZ,
  CCR_duration_ms           INT,

  -- Version tracking (untuk audit "formula apa yang dipakai")
  CCR_git_commit            VARCHAR(40),                        -- Go code commit
  CCR_param_master_snapshot JSONB,                              -- param_id → calc_function_key snapshot

  -- Error summary
  CCR_error_summary         JSONB,                              -- aggregated errors

  CONSTRAINT chk_ccr_trigger_type
    CHECK (CCR_trigger_type IN ('MANUAL', 'SCHEDULED')),

  CONSTRAINT chk_ccr_status
    CHECK (CCR_status IN ('PENDING', 'RUNNING', 'SUCCESS', 'FAILED', 'PARTIAL', 'CANCELLED')),

  CONSTRAINT uq_ccr_period_seq
    UNIQUE (CCR_period, CCR_run_seq)
);

COMMENT ON TABLE cost_calculation_run
  IS 'Calculation execution log. Multiple runs per period. is_active = current authoritative result.';

-- Partial unique: hanya 1 active per period
CREATE UNIQUE INDEX idx_ccr_one_active_per_period
  ON cost_calculation_run(CCR_period)
  WHERE CCR_is_active = true;


-- ----------------------------------------------------------------------------
-- 3. cost_calculation_result (CCRE_)
-- Hasil calculation per product per run. Append-only — setiap run new records.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_calculation_result (
  CCRE_result_id            BIGSERIAL       PRIMARY KEY,
  CCRE_run_id               BIGINT          NOT NULL
                            REFERENCES cost_calculation_run(CCR_run_id),
  CCRE_period               VARCHAR(6)      NOT NULL,           -- denormalized untuk query
  CCRE_product_sys_id       BIGINT          NOT NULL
                            REFERENCES cost_product_master(CPM_product_sys_id),

  -- Status
  CCRE_calc_status          VARCHAR(20)     NOT NULL,
  -- SUCCESS = semua param berhasil dihitung
  -- PARTIAL = ada missing/failed dependency, hasil tetap ada tapi degraded
  -- FAILED  = required param missing, tidak bisa hitung sama sekali

  -- Cascade tracking
  CCRE_failed_param_ids     JSONB,                              -- [51, 79] param yang gagal
  CCRE_failed_dep_products  JSONB,                              -- [12345, 67890] product gagal yang ter-cascade
  CCRE_partial_reasons      JSONB,                              -- structured reasons

  -- Param values snapshot (semua 125 params untuk audit absolute)
  CCRE_param_values         JSONB           NOT NULL DEFAULT '{}',
  -- Format: {"1": "PTY", "8": 150, "31": 1.6, ..., "101": 1.45}

  -- Quick-access final costs (denormalized untuk fast query/reporting)
  CCRE_captive_cost         DECIMAL(18,4),                      -- PARAM 101
  CCRE_delivery_cost        DECIMAL(18,4),                      -- PARAM 102

  -- Audit
  CCRE_calculated_at        TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CCRE_duration_us          INT,                                -- microseconds for this product

  CONSTRAINT chk_ccre_status
    CHECK (CCRE_calc_status IN ('SUCCESS', 'PARTIAL', 'FAILED')),

  -- Unique: 1 result per (run, product)
  CONSTRAINT uq_ccre_run_product
    UNIQUE (CCRE_run_id, CCRE_product_sys_id)
);

COMMENT ON TABLE cost_calculation_result
  IS 'Calculation result per product per run. JSONB snapshot 125 params untuk audit absolute.';


-- ----------------------------------------------------------------------------
-- 4. cost_product_parameter_period (CPPP_)
-- Dynamic parameter values per product per period.
-- is_period_dependent = true di parameter_master → value disimpan di sini.
-- Contoh: Raw Material Rate, Heatset Cost per Batch (berubah per bulan).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_parameter_period (
  CPPP_value_id             BIGSERIAL       PRIMARY KEY,
  CPPP_product_sys_id       BIGINT          NOT NULL
                            REFERENCES cost_product_master(CPM_product_sys_id),
  CPPP_param_id             INT             NOT NULL
                            REFERENCES cost_parameter_master(CPRM_param_id),
  CPPP_period               VARCHAR(6)      NOT NULL,           -- "202605"

  -- Value (typed by data_type at parameter master)
  CPPP_value_numeric        DECIMAL(20,6),
  CPPP_value_text           TEXT,
  CPPP_value_flag           BOOLEAN,
  CPPP_value_json           JSONB,

  -- Audit
  CPPP_filled_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPPP_filled_by            VARCHAR(64)     NOT NULL,
  CPPP_updated_at           TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPPP_updated_by           VARCHAR(64)     NOT NULL,

  -- Unique per (product, param, period)
  CONSTRAINT uq_cppp_product_param_period
    UNIQUE (CPPP_product_sys_id, CPPP_param_id, CPPP_period),

  -- Period format
  CONSTRAINT chk_cppp_period_format
    CHECK (CPPP_period ~ '^[0-9]{4}(0[1-9]|1[0-2])$')
);

COMMENT ON TABLE cost_product_parameter_period
  IS 'Dynamic parameter values per product per period. is_period_dependent=true params disimpan di sini.';


-- ============================================================================
-- INDEXES
-- ============================================================================

-- cost_calculation_period
CREATE INDEX idx_ccp_status_period
  ON cost_calculation_period(CCP_status, CCP_period DESC);

-- cost_calculation_run
CREATE INDEX idx_ccr_period_status
  ON cost_calculation_run(CCR_period, CCR_status);

CREATE INDEX idx_ccr_period_active
  ON cost_calculation_run(CCR_period)
  WHERE CCR_is_active = true;

CREATE INDEX idx_ccr_running
  ON cost_calculation_run(CCR_started_at DESC)
  WHERE CCR_status = 'RUNNING';

-- cost_calculation_result
CREATE INDEX idx_ccre_product_period
  ON cost_calculation_result(CCRE_product_sys_id, CCRE_period DESC);

CREATE INDEX idx_ccre_run_status
  ON cost_calculation_result(CCRE_run_id, CCRE_calc_status);

-- 🚀 PERF: query result for active run of a period
CREATE INDEX idx_ccre_period_status
  ON cost_calculation_result(CCRE_period, CCRE_calc_status);

CREATE INDEX idx_ccre_failed_partial
  ON cost_calculation_result(CCRE_run_id, CCRE_product_sys_id)
  WHERE CCRE_calc_status IN ('FAILED', 'PARTIAL');

-- GIN index untuk query JSONB param values (rare but useful for audit)
CREATE INDEX idx_ccre_param_values_gin
  ON cost_calculation_result
  USING gin(CCRE_param_values);

-- cost_product_parameter_period
CREATE INDEX idx_cppp_product_period
  ON cost_product_parameter_period(CPPP_product_sys_id, CPPP_period);

CREATE INDEX idx_cppp_period
  ON cost_product_parameter_period(CPPP_period);

CREATE INDEX idx_cppp_param
  ON cost_product_parameter_period(CPPP_param_id);


-- ============================================================================
-- HELPER VIEW: Latest Cost per Product
-- ============================================================================
-- View untuk get cost terkini (dari run yang is_active=true) per product per period
CREATE OR REPLACE VIEW v_cost_latest AS
SELECT
  CCRE_product_sys_id,
  CCRE_period,
  CCRE_calc_status,
  CCRE_captive_cost,
  CCRE_delivery_cost,
  CCRE_param_values,
  CCRE_calculated_at,
  CCR_run_id
FROM cost_calculation_result
JOIN cost_calculation_run
  ON CCRE_run_id = CCR_run_id
WHERE CCR_is_active = true;

COMMENT ON VIEW v_cost_latest
  IS 'Latest cost per product per period (from active run only).';


-- ============================================================================
-- HELPER FUNCTION: Open New Period
-- ============================================================================
CREATE OR REPLACE FUNCTION open_calculation_period(
  p_period VARCHAR(6),
  p_user_id VARCHAR(64)
) RETURNS INT AS $$
DECLARE
  v_period_id INT;
BEGIN
  -- Check no other open period (enforced by unique partial index, but explicit check is clearer)
  IF EXISTS (SELECT 1 FROM cost_calculation_period WHERE CCP_status = 'OPEN') THEN
    RAISE EXCEPTION 'Cannot open new period — another period is still OPEN. Close it first.';
  END IF;

  INSERT INTO cost_calculation_period (CCP_period, CCP_status, CCP_opened_by)
  VALUES (p_period, 'OPEN', p_user_id)
  RETURNING CCP_period_id INTO v_period_id;

  RETURN v_period_id;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION open_calculation_period IS 'Open new calculation period. Fails if another period is OPEN.';


-- ============================================================================
-- HELPER FUNCTION: Close Period
-- ============================================================================
CREATE OR REPLACE FUNCTION close_calculation_period(
  p_period VARCHAR(6),
  p_user_id VARCHAR(64),
  p_reason TEXT DEFAULT NULL
) RETURNS BOOLEAN AS $$
DECLARE
  v_updated INT;
BEGIN
  UPDATE cost_calculation_period
  SET CCP_status = 'CLOSED',
      CCP_closed_at = now(),
      CCP_closed_by = p_user_id,
      CCP_close_reason = p_reason
  WHERE CCP_period = p_period AND CCP_status = 'OPEN';

  GET DIAGNOSTICS v_updated = ROW_COUNT;
  RETURN v_updated > 0;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION close_calculation_period IS 'Close an OPEN period. After close, no more runs allowed.';


-- ============================================================================
-- HELPER FUNCTION: Get Missing Required Params for Product
-- ============================================================================
-- Returns param yang required tapi belum diisi untuk product tertentu.
-- Used by Phase A untuk cek state transition PARAMETER_PENDING → PARAMETER_COMPLETE.
CREATE OR REPLACE FUNCTION get_missing_required_params(
  p_product_sys_id BIGINT,
  p_period VARCHAR(6) DEFAULT NULL
) RETURNS TABLE (
  param_id INT,
  param_code VARCHAR,
  param_name VARCHAR,
  owner_dept VARCHAR,
  is_period_dependent BOOLEAN
) AS $$
DECLARE
  v_yarn_type TEXT;
BEGIN
  -- Get product's yarn type (PARAM 1)
  SELECT CPP_value_text INTO v_yarn_type
  FROM cost_product_parameter
  WHERE CPP_product_sys_id = p_product_sys_id AND CPP_param_id = 1;

  RETURN QUERY
  SELECT
    cprm.CPRM_param_id,
    cprm.CPRM_param_code,
    cprm.CPRM_param_name,
    cprm.CPRM_owner_department,
    cprm.CPRM_is_period_dependent
  FROM cost_parameter_master cprm
  WHERE cprm.CPRM_is_required_for_costing = true
    AND cprm.CPRM_is_active = true
    AND cprm.CPRM_function_type IN ('ENTRY', 'JSONB')  -- only fillable
    -- Yarn type filter (Level 1)
    AND (
      cprm.CPRM_required_for_yarn_types IS NULL
      OR v_yarn_type IS NULL
      OR cprm.CPRM_required_for_yarn_types ? v_yarn_type
    )
    -- Not yet filled
    AND (
      -- Static param
      (cprm.CPRM_is_period_dependent = false AND NOT EXISTS (
        SELECT 1 FROM cost_product_parameter cpp
        WHERE cpp.CPP_product_sys_id = p_product_sys_id
          AND cpp.CPP_param_id = cprm.CPRM_param_id
          AND (cpp.CPP_value_numeric IS NOT NULL
               OR cpp.CPP_value_text IS NOT NULL
               OR cpp.CPP_value_flag IS NOT NULL
               OR cpp.CPP_value_json IS NOT NULL)
      ))
      OR
      -- Period param
      (cprm.CPRM_is_period_dependent = true AND p_period IS NOT NULL AND NOT EXISTS (
        SELECT 1 FROM cost_product_parameter_period cppp
        WHERE cppp.CPPP_product_sys_id = p_product_sys_id
          AND cppp.CPPP_param_id = cprm.CPRM_param_id
          AND cppp.CPPP_period = p_period
          AND (cppp.CPPP_value_numeric IS NOT NULL
               OR cppp.CPPP_value_text IS NOT NULL
               OR cppp.CPPP_value_flag IS NOT NULL
               OR cppp.CPPP_value_json IS NOT NULL)
      ))
    )
  ORDER BY cprm.CPRM_display_order;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION get_missing_required_params
  IS 'Returns missing required params untuk product. Phase A pakai untuk cek PARAMETER_COMPLETE transition.';


-- ============================================================================
-- HELPER FUNCTION: Set Run As Active
-- ============================================================================
-- Atomic transition: deactivate previous active run, activate the given run
CREATE OR REPLACE FUNCTION set_run_active(p_run_id BIGINT) RETURNS BOOLEAN AS $$
DECLARE
  v_period VARCHAR(6);
BEGIN
  -- Get period from this run
  SELECT CCR_period INTO v_period FROM cost_calculation_run WHERE CCR_run_id = p_run_id;
  IF v_period IS NULL THEN RETURN false; END IF;

  -- Deactivate previous active runs in same period
  UPDATE cost_calculation_run
  SET CCR_is_active = false
  WHERE CCR_period = v_period AND CCR_is_active = true AND CCR_run_id <> p_run_id;

  -- Activate this run
  UPDATE cost_calculation_run
  SET CCR_is_active = true
  WHERE CCR_run_id = p_run_id;

  RETURN true;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION set_run_active
  IS 'Atomic: mark run as active, deactivate previous active run in same period.';
