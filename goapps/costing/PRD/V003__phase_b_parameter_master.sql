-- ============================================================================
-- Costing Workflow Suite — Phase B Addendum v1.4
-- Parameter Master, Generic Master Pattern, Static Product Parameters
-- DDL — PostgreSQL 14+
-- Version: 1.4 | May 2026 | Incremental to v1.3
-- ============================================================================

-- ============================================================================
-- PART 8: PARAMETER MASTER (definition layer)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 13. cost_parameter_master (CPRM_)
-- Definisi 125+ parameter yang digunakan dalam costing calculation.
-- Setiap param punya function_type yang menentukan cara value-nya didapat.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_parameter_master (
  CPRM_param_id                 SERIAL          PRIMARY KEY,
  CPRM_param_code               VARCHAR(50)     NOT NULL UNIQUE,    -- "YARN_TYPE", "DENIER"
  CPRM_param_name               VARCHAR(200)    NOT NULL,           -- "Yarn Type"
  CPRM_description              TEXT,

  -- Classification
  CPRM_function_type            VARCHAR(20)     NOT NULL,           -- ENTRY / CALCULATION / LOOKUP / JSONB
  CPRM_data_type                VARCHAR(20)     NOT NULL,           -- NUMERIC / TEXT / FLAG / JSON
  CPRM_unit_of_measure          VARCHAR(20),                        -- kg, mm, %, USD/kg

  -- Ownership & Required
  CPRM_owner_department         VARCHAR(30),                        -- Engineering / Production / Finance / RND
  CPRM_is_required_for_costing  BOOLEAN         NOT NULL DEFAULT false,
  CPRM_required_for_yarn_types  JSONB,                              -- ["PTY","TTY"] atau NULL = all

  -- Period dimension
  CPRM_is_period_dependent      BOOLEAN         NOT NULL DEFAULT false,
  -- false → value disimpan di cost_product_parameter (Phase B, static)
  -- true  → value disimpan di cost_product_parameter_period (Phase C, dynamic)

  -- Calculation reference (untuk CALCULATION type)
  CPRM_formula_doc              TEXT,                               -- human-readable formula doc
  CPRM_calc_function_key        VARCHAR(100),                       -- "calcNetBobbinWeight" → Go function

  -- Lookup reference (untuk LOOKUP type)
  CPRM_lookup_master_code       VARCHAR(30),                        -- FK ke cost_master_definition.master_code
  CPRM_lookup_condition_doc     TEXT,                               -- human-readable condition

  -- Override capability
  -- (removed v1.4.1: design decision — formula always at param level, no per-product override)

  -- Display & ordering
  CPRM_display_order            INT,                                -- urutan tampil di UI
  CPRM_display_group            VARCHAR(50),                        -- grouping di UI: "Spec","Machine","Cost"

  -- Audit
  CPRM_is_active                BOOLEAN         NOT NULL DEFAULT true,
  CPRM_created_at               TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPRM_updated_at               TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_cprm_function_type
    CHECK (CPRM_function_type IN ('ENTRY', 'CALCULATION', 'LOOKUP', 'JSONB', 'NOT_USED')),

  CONSTRAINT chk_cprm_data_type
    CHECK (CPRM_data_type IN ('NUMERIC', 'TEXT', 'FLAG', 'JSON')),

  CONSTRAINT chk_cprm_owner_dept
    CHECK (CPRM_owner_department IS NULL OR CPRM_owner_department IN
      ('Engineering', 'Production', 'Finance', 'RND', 'Marketing', 'Admin')),

  -- Calculation param wajib punya function_key
  CONSTRAINT chk_cprm_calc_function
    CHECK (
      CPRM_function_type <> 'CALCULATION'
      OR CPRM_calc_function_key IS NOT NULL
    ),

  -- Lookup param wajib punya master_code
  CONSTRAINT chk_cprm_lookup_master
    CHECK (
      CPRM_function_type <> 'LOOKUP'
      OR CPRM_lookup_master_code IS NOT NULL
    )
);

COMMENT ON TABLE cost_parameter_master
  IS 'Definisi 125+ parameter. Single source of truth metadata parameter. Engine pakai ini untuk routing & validation.';


-- ============================================================================
-- PART 9: GENERIC MASTER PATTERN
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 14. cost_master_definition (CMD_)
-- Type registry: master apa saja yang ada di sistem.
-- Replace banyak tabel master spesifik dengan satu generic pattern.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_master_definition (
  CMD_master_id                 SERIAL          PRIMARY KEY,
  CMD_master_code               VARCHAR(30)     NOT NULL UNIQUE,    -- "MACHINE","BOX_BOBBIN","PARAM_DATA"
  CMD_master_name               VARCHAR(100)    NOT NULL,
  CMD_description               TEXT,

  -- Schema validation (optional JSONB schema untuk attributes field di CMSD)
  CMD_attributes_schema         JSONB,                              -- JSON schema definition

  -- Period behavior
  CMD_is_period_dependent       BOOLEAN         NOT NULL DEFAULT false,
  -- true → value berubah per period (e.g. BOX_BOBBIN_COST)
  -- false → value static (e.g. MACHINE specs)

  -- Status
  CMD_is_active                 BOOLEAN         NOT NULL DEFAULT true,
  CMD_created_at                TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CMD_updated_at                TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_master_definition
  IS 'Type registry untuk generic master pattern. Define master apa saja yang ada (MACHINE, BOX_BOBBIN, dll).';


-- ----------------------------------------------------------------------------
-- 15. cost_master_data (CMSD_)
-- Actual master data rows. Attributes disimpan sebagai JSONB.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_master_data (
  CMSD_data_id                  BIGSERIAL       PRIMARY KEY,
  CMSD_master_id                INT             NOT NULL
                                REFERENCES cost_master_definition(CMD_master_id),

  -- Identification
  CMSD_data_code                VARCHAR(100)    NOT NULL,           -- machine code, box code, dll
  CMSD_data_name                VARCHAR(255),

  -- Period (NULL untuk non-period-dependent master)
  CMSD_period                   VARCHAR(6),                         -- "202605"

  -- Effective dating (alternative untuk audit non-period master)
  CMSD_effective_from           TIMESTAMPTZ,
  CMSD_effective_to             TIMESTAMPTZ,                        -- NULL = current

  -- Attributes (the actual data)
  CMSD_attributes               JSONB           NOT NULL,
  -- Contoh untuk MACHINE: {"power_per_day":120, "manpower":3, "overhead":50, "spares":12}
  -- Contoh untuk BOX_BOBBIN: {"bobbin_rate":0.45, "box_rate":1.20, "weight":18.5}

  -- Status
  CMSD_is_active                BOOLEAN         NOT NULL DEFAULT true,

  -- Audit
  CMSD_created_at               TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CMSD_created_by               VARCHAR(64)     NOT NULL,
  CMSD_updated_at               TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CMSD_updated_by               VARCHAR(64)     NOT NULL,

  -- Unique per (master_id, data_code, period)
  CONSTRAINT uq_cmsd_master_code_period
    UNIQUE (CMSD_master_id, CMSD_data_code, CMSD_period)
);

COMMENT ON TABLE cost_master_data
  IS 'Master data rows. Attributes sebagai JSONB. Period nullable: NULL = non-period, "202605" = period-specific.';


-- ============================================================================
-- PART 10: STATIC PRODUCT PARAMETERS (Phase B value layer)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 16. cost_product_parameter (CPP_)
-- Nilai parameter STATIC per product (tidak berubah per period).
-- Contoh: Denier=150, Yarn Type=PTY, Machine Name=MC-PTY-001
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_parameter (
  CPP_value_id                  BIGSERIAL       PRIMARY KEY,
  CPP_product_sys_id            BIGINT          NOT NULL
                                REFERENCES cost_product_master(CPM_product_sys_id),
  CPP_param_id                  INT             NOT NULL
                                REFERENCES cost_parameter_master(CPRM_param_id),

  -- Value (typed by data_type at parameter master)
  CPP_value_numeric             DECIMAL(20,6),
  CPP_value_text                TEXT,
  CPP_value_flag                BOOLEAN,
  CPP_value_json                JSONB,

  -- Audit
  CPP_filled_at                 TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPP_filled_by                 VARCHAR(64)     NOT NULL,
  CPP_updated_at                TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPP_updated_by                VARCHAR(64)     NOT NULL,

  -- One value per (product, param) for static params
  CONSTRAINT uq_cpp_product_param
    UNIQUE (CPP_product_sys_id, CPP_param_id)
);

COMMENT ON TABLE cost_product_parameter
  IS 'Static parameter values per product. is_period_dependent=false params disimpan di sini.';


-- ============================================================================
-- PART 11: PARAMETER DEPENDENCY GRAPH (visualization only)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 17. cost_parameter_dependency (CPRD_)
-- Dependency graph antar parameter. Auto-synced dari Go calc registry.
-- READ-ONLY untuk user — source of truth ada di Go code.
-- Digunakan untuk visualization, impact analysis, dan dokumentasi.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_parameter_dependency (
  CPRD_dep_id                   BIGSERIAL       PRIMARY KEY,
  CPRD_param_id                 INT             NOT NULL
                                REFERENCES cost_parameter_master(CPRM_param_id),
  CPRD_depends_on_param_id      INT             NOT NULL
                                REFERENCES cost_parameter_master(CPRM_param_id),

  CPRD_synced_at                TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPRD_git_commit               VARCHAR(40),                        -- commit dari mana auto-sync

  CONSTRAINT uq_cprd_dep
    UNIQUE (CPRD_param_id, CPRD_depends_on_param_id),

  -- Param tidak bisa depend on dirinya sendiri
  CONSTRAINT chk_cprd_no_self_dep
    CHECK (CPRD_param_id <> CPRD_depends_on_param_id)
);

COMMENT ON TABLE cost_parameter_dependency
  IS 'Read-only dependency graph for visualization. Auto-synced from Go calc registry on deployment.';


-- ============================================================================
-- PART 12: INDEXES
-- ============================================================================

-- cost_parameter_master
CREATE INDEX idx_cprm_function_type
  ON cost_parameter_master(CPRM_function_type);

CREATE INDEX idx_cprm_period_dependent
  ON cost_parameter_master(CPRM_is_period_dependent)
  WHERE CPRM_is_active = true;

CREATE INDEX idx_cprm_required
  ON cost_parameter_master(CPRM_is_required_for_costing)
  WHERE CPRM_is_required_for_costing = true AND CPRM_is_active = true;

CREATE INDEX idx_cprm_owner_dept
  ON cost_parameter_master(CPRM_owner_department)
  WHERE CPRM_is_active = true;

CREATE INDEX idx_cprm_lookup_master
  ON cost_parameter_master(CPRM_lookup_master_code)
  WHERE CPRM_function_type = 'LOOKUP';

-- cost_master_definition
CREATE INDEX idx_cmd_period_dependent
  ON cost_master_definition(CMD_is_period_dependent)
  WHERE CMD_is_active = true;

-- cost_master_data
CREATE INDEX idx_cmsd_master_active
  ON cost_master_data(CMSD_master_id, CMSD_data_code)
  WHERE CMSD_is_active = true;

CREATE INDEX idx_cmsd_period
  ON cost_master_data(CMSD_master_id, CMSD_period)
  WHERE CMSD_period IS NOT NULL;

-- 🚀 PERF: GIN index untuk query JSONB attributes
CREATE INDEX idx_cmsd_attributes_gin
  ON cost_master_data
  USING gin(CMSD_attributes);

-- Effective dating index (for non-period masters with versioning)
CREATE INDEX idx_cmsd_effective_dating
  ON cost_master_data(CMSD_master_id, CMSD_effective_from, CMSD_effective_to)
  WHERE CMSD_effective_to IS NULL;

-- cost_product_parameter
CREATE INDEX idx_cpp_product
  ON cost_product_parameter(CPP_product_sys_id);

CREATE INDEX idx_cpp_param
  ON cost_product_parameter(CPP_param_id);

-- 🚀 PERF: composite untuk completeness query
CREATE INDEX idx_cpp_product_param_filled
  ON cost_product_parameter(CPP_product_sys_id, CPP_param_id, CPP_filled_at);

-- cost_parameter_dependency
CREATE INDEX idx_cprd_param
  ON cost_parameter_dependency(CPRD_param_id);

CREATE INDEX idx_cprd_depends_on
  ON cost_parameter_dependency(CPRD_depends_on_param_id);


-- ============================================================================
-- PART 13: SEED DATA — Master Definitions
-- ============================================================================

INSERT INTO cost_master_definition (CMD_master_code, CMD_master_name, CMD_is_period_dependent, CMD_description) VALUES
  ('MACHINE',          'Master Mesin',                       false, 'Atribut mesin: power, manpower, overhead, spares per day'),
  ('BOX_BOBBIN_COST',  'Master Box & Bobbin Cost',           true,  'Cost box dan bobbin per pack code, per period'),
  ('INTERMINGLING',    'Master Intermingling',               true,  'Cost intermingling per type, per period'),
  ('PARAM_DATA',       'Master Generic Param Data',          true,  'Generic master untuk steam, softner, washing cost'),
  ('PRODUCT_GRADE',    'Master Product Grade Loss',          false, 'Grade loss configuration (POY_B_C_GRADE, LOSS)'),
  ('VOLUME_BUCKET',    'Master Volume Bucket',               false, 'Volume bucket configuration per machine'),
  ('CHANGEOVER_LOSS',  'Master Changeover Loss',             false, 'KGS lost due to changeover per machine'),
  ('YARN_TYPE',        'Master Yarn Type',                   false, 'POY, PTY, TTY, ATY, ITY, dll — definisi yarn type');


-- ============================================================================
-- PART 14: SEED DATA — Parameter Master (125 params dari Excel sistem lama)
-- ============================================================================
-- Note: Display order & group bisa disesuaikan UI design
-- "Ask" params di-flag NOT_USED sementara, akan revisit nanti

INSERT INTO cost_parameter_master (
  CPRM_param_id, CPRM_param_code, CPRM_param_name,
  CPRM_function_type, CPRM_data_type, CPRM_unit_of_measure,
  CPRM_owner_department, CPRM_is_required_for_costing, CPRM_is_period_dependent,
  CPRM_formula_doc, CPRM_calc_function_key,
  CPRM_lookup_master_code,
  CPRM_display_group, CPRM_display_order
) VALUES
-- ENTRY params (Engineering)
(1,  'YARN_TYPE',          'Yarn Type',                    'ENTRY',       'TEXT',    NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 1),
(2,  'MACHINE_NAME',       'Machine Name',                 'ENTRY',       'TEXT',    NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Machine', 2),
(3,  'NO_OF_POSITION',     'No. Of Position',              'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Machine', 3),
(4,  'NO_OF_END',          'No. Of End',                   'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Machine', 4),
(5,  'MACHINE_EFFICIENCY', 'Machine Efficiency',           'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Machine', 5),
(6,  'MACHINE_SPEED',      'Machine Speed',                'ENTRY',       'NUMERIC', NULL,    'Production',  true,  false, NULL, NULL, NULL, 'Machine', 6),
(7,  'TPM',                'TPM',                          'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 7),
(8,  'DENIER',             'Denier',                       'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 8),
(9,  'ACTUAL_DENIER',      'Actual Denier',                'ENTRY',       'NUMERIC', NULL,    'Production',  true,  false, NULL, NULL, NULL, 'Spec', 9),
(10, 'NO_OF_PLY',          'No. Of Ply',                   'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 10),
(11, 'NO_OF_FILAMENTS',    'No. Of Filaments',             'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 11),
(12, 'CROSS_SECTION',      'Cross Section',                'ENTRY',       'TEXT',    NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Spec', 12),
(13, 'INTERMINGLE',        'Intermingle',                  'ENTRY',       'TEXT',    NULL,    'Engineering', false, false, NULL, NULL, NULL, 'Spec', 13),
(14, 'RM_TABLE_TYPE',      'RM Table Type',                'NOT_USED',    'TEXT',    NULL,    NULL,          false, false, NULL, NULL, NULL, 'Misc', 14),
(15, 'RAW_MATERIAL',       'Raw Material',                 'JSONB',       'JSON',    NULL,    'Engineering', true,  false, 'Raw Material JSONB field', NULL, NULL, 'RM', 15),
(16, 'WASTE_PERCENTAGE',   'Waste Percentage',             'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Waste', 16),
(17, 'OPU',                'OPU',                          'ENTRY',       'NUMERIC', NULL,    'Production',  true,  false, NULL, NULL, NULL, 'Waste', 17),

-- Grade percentages (Production)
(18, 'GRADE_AX',           'AX',                           'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 18),
(19, 'GRADE_AE',           'AE',                           'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 19),
(20, 'GRADE_A9',           'A9',                           'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 20),
(21, 'GRADE_A',            'A',                            'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 21),
(22, 'GRADE_B',            'B',                            'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 22),
(23, 'GRADE_C',            'C',                            'ENTRY',       'NUMERIC', '%',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 23),
(24, 'Y_TYPE',             'Y - Type',                     'NOT_USED',    'TEXT',    NULL,    NULL,          false, false, NULL, NULL, NULL, 'Misc', 24),

-- Grade weights
(25, 'AX_WEIGHT',          'AX - Weight',                  'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 25),
(26, 'AE_WEIGHT',          'AE - Weight',                  'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 26),
(27, 'A9_WEIGHT',          'A9 - Weight',                  'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 27),
(28, 'A_WEIGHT',           'A - Weight',                   'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 28),
(29, 'B_WEIGHT',           'B - Weight',                   'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 29),
(30, 'C_WEIGHT',           'C - Weight',                   'ENTRY',       'NUMERIC', 'g',     'Production',  true,  false, NULL, NULL, NULL, 'Grade', 30),

-- Calculated
(31, 'NET_BOBBIN_WEIGHT',  'Net Bobbin Weight',            'CALCULATION', 'NUMERIC', 'kg',    NULL,          false, false,
  '(PARAM(18)*PARAM(25) + PARAM(19)*PARAM(26) + PARAM(20)*PARAM(27) + PARAM(21)*PARAM(28) + PARAM(22)*PARAM(29) + PARAM(23)*PARAM(30)) / 1000',
  'calcNetBobbinWeight', NULL, 'Packing', 31),

-- Captive Packing (Lookup + Calc)
(32, 'CAPTIVE_PACK_CODE',  'Captive Pack Code',            'LOOKUP',      'TEXT',    NULL,    'Engineering', true,  false, NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 32),
(33, 'CAPTIVE_NO_BOBBINS', 'Captive No of Bobbins',        'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Packing', 33),
(34, 'CAPTIVE_BOX_WEIGHT', 'Captive Box Weight',           'CALCULATION', 'NUMERIC', 'kg',    NULL,          false, false,
  'PARAM(31) * PARAM(33)', 'calcCaptiveBoxWeight', NULL, 'Packing', 34),
(35, 'CAPTIVE_BOBBIN_RATE','Captive Bobbin Rate',          'LOOKUP',      'NUMERIC', 'USD',   NULL,          false, true,  NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 35),
(36, 'CAPTIVE_BOX_RATE',   'Captive Box Rate',             'LOOKUP',      'NUMERIC', 'USD',   NULL,          false, true,  NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 36),
(37, 'CAPTIVE_PACK_COST',  'Captive Packing Cost',         'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  '(PARAM(35) * PARAM(33) + PARAM(36)) / PARAM(34)', 'calcCaptivePackCost', NULL, 'Packing', 37),

-- Delivery Packing
(38, 'DELIVERY_PACK_CODE', 'Delivery Pack Code',           'LOOKUP',      'TEXT',    NULL,    'Engineering', true,  false, NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 38),
(39, 'DELIVERY_NO_BOBBINS','Delivery No of Bobbins',       'ENTRY',       'NUMERIC', NULL,    'Engineering', true,  false, NULL, NULL, NULL, 'Packing', 39),
(40, 'DELIVERY_BOX_WEIGHT','Delivery Box Weight',          'CALCULATION', 'NUMERIC', 'kg',    NULL,          false, false,
  'PARAM(31) * PARAM(39)', 'calcDeliveryBoxWeight', NULL, 'Packing', 40),
(41, 'DELIVERY_BOBBIN_RATE','Delivery Bobbin Rate',        'LOOKUP',      'NUMERIC', 'USD',   NULL,          false, true,  NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 41),
(42, 'DELIVERY_BOX_RATE',  'Delivery Box Rate',            'LOOKUP',      'NUMERIC', 'USD',   NULL,          false, true,  NULL, NULL, 'BOX_BOBBIN_COST', 'Packing', 42),
(43, 'DELIVERY_PACK_COST', 'Delivery Packing Cost',        'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  '(PARAM(41) * PARAM(39) + PARAM(42)) / PARAM(40)', 'calcDeliveryPackCost', NULL, 'Packing', 43),

-- Heatset (conditional)
(44, 'HEATSET_CODE',       'Heatset Code',                 'ENTRY',       'TEXT',    NULL,    'Engineering', false, false, NULL, NULL, NULL, 'Heatset', 44),
(45, 'NO_OF_TROLLIES',     'No. Of Trollies',              'ENTRY',       'NUMERIC', NULL,    'Production',  false, false, NULL, NULL, NULL, 'Heatset', 45),
(46, 'NO_BOBBINS_TROLLEY', 'No. Of Bobbins Per Trolley',   'ENTRY',       'NUMERIC', NULL,    'Production',  false, false, NULL, NULL, NULL, 'Heatset', 46),
(47, 'BATCH_WEIGHT',       'Batch Weight',                 'CALCULATION', 'NUMERIC', 'kg',    NULL,          false, false,
  'PARAM(45) * PARAM(46) * PARAM(31) / 0.95', 'calcBatchWeight', NULL, 'Heatset', 47),
(48, 'HEATSET_COST_BATCH', 'Heatset Cost per Batch',       'ENTRY',       'NUMERIC', 'USD',   'Finance',     false, true,  NULL, NULL, NULL, 'Heatset', 48),
(49, 'HEATSET_COST_KG',    'Heatset Cost per Kg',          'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'IF PARAM(44) IS NOT NULL THEN PARAM(48) / PARAM(47)', 'calcHeatsetCostKg', NULL, 'Heatset', 49),

-- Raw Material chain (from JSONB)
(50, 'RM_FROM_PREVIOUS',   'Raw Material From Previous',   'JSONB',       'TEXT',    NULL,    NULL,          true,  false, 'From Raw Material JSONB', NULL, NULL, 'RM', 50),
(51, 'RM_RATE',            'Raw Material Rate',            'JSONB',       'NUMERIC', 'USD/kg', NULL,         true,  true,  'From Raw Material JSONB', NULL, NULL, 'RM', 51),
(52, 'RM_LANDED_COST',     'Raw Material Landed Cost',     'JSONB',       'NUMERIC', 'USD/kg', NULL,         true,  true,  'From Raw Material JSONB', NULL, NULL, 'RM', 52),
(53, 'RM_NORMS',           'Raw Material Norms',           'CALCULATION', 'NUMERIC', NULL,    NULL,          false, false,
  '(1 + PARAM(16)) * (100 - PARAM(67)) / 100', 'calcRMNorms', NULL, 'RM', 53),
(54, 'WASTE_LESS_MB',      'Waste Less MB dozing, OPU',    'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(53) * PARAM(52)', 'calcWasteLessMB', NULL, 'RM', 54),

-- Oil
(55, 'OIL_NAME',           'Oil Name',                     'JSONB',       'TEXT',    NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'Oil', 55),
(56, 'OIL_RATE',           'Oil Rate',                     'JSONB',       'NUMERIC', 'USD',   NULL,          false, true,  'From Raw Material JSONB', NULL, NULL, 'Oil', 56),
(57, 'OIL_COST',           'Oil Cost',                     'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(56) * PARAM(17) / 100', 'calcOilCost', NULL, 'Oil', 57),

-- Masterbatch (deprecated/not used)
(58, 'MASTERBATCH_FLAG',   'Masterbatch Flag',             'NOT_USED',    'FLAG',    NULL,    NULL,          false, false, NULL, NULL, NULL, 'MB', 58),

-- MB / SP
(59, 'MB_SP_CODE',         'MB / SP Code',                 'JSONB',       'TEXT',    NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 59),
(60, 'MB_SP_DYE_NAME',     'MB / SP Dye Name',             'JSONB',       'TEXT',    NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 60),
(61, 'MB_SP_DENIER',       'MB / SP - Denier',             'JSONB',       'NUMERIC', NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 61),
(62, 'MB_SP_FILAMENT',     'MB / SP - Filament',           'JSONB',       'NUMERIC', NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 62),
(63, 'MB_SP_CC',           'MB / SP - CC',                 'JSONB',       'TEXT',    NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 63),
(64, 'MB_SP_DOZING',       'MB / SP - Dozing',             'JSONB',       'NUMERIC', NULL,    NULL,          false, false, 'From Raw Material JSONB', NULL, NULL, 'MB', 64),
(65, 'CONVERSION_FACTOR',  'Conversion Factor',            'CALCULATION', 'NUMERIC', NULL,    NULL,          false, false,
  'sqrt(PARAM(61) / PARAM(62)) * PARAM(64) / sqrt(PARAM(8) / PARAM(11))', 'calcConversionFactor', NULL, 'MB', 65),
(66, 'RP_CC',              'RP-CC',                        'NOT_USED',    'TEXT',    NULL,    NULL,          false, false, NULL, NULL, NULL, 'MB', 66),
(67, 'RP_DOZING',          'RP-Dozing',                    'CALCULATION', 'NUMERIC', NULL,    NULL,          false, false,
  'See PARAM(67) complex ELSIF logic — Go function only', 'calcRPDozing', NULL, 'MB', 67),
(68, 'MB_RATE_MARKETING',  'MB Rate Marketing',            'JSONB',       'NUMERIC', 'USD',   NULL,          false, true,  'From Raw Material JSONB', NULL, NULL, 'MB', 68),
(69, 'MB_COST_MARKETING',  'MB Cost Marketing',            'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(68) / PARAM(67) / 100', 'calcMBCostMarketing', NULL, 'MB', 69),

-- Intermingling
(70, 'INTERMINGLING_COST', 'Intermingling',                'LOOKUP',      'NUMERIC', 'USD/kg', NULL,         false, true,  NULL, NULL, 'INTERMINGLING', 'Special', 70),

-- Special Cost
(71, 'SPECIAL_COST_FLAG',  'Special Cost Flag',            'ENTRY',       'FLAG',    NULL,    'Engineering', false, false, NULL, NULL, NULL, 'Special', 71),
(72, 'SPECIAL_COST_1',     'Special Cost 1',               'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, false,
  'IF PARAM(1)=PTY AND PARAM(8)>300 THEN 0.01 ELSIF PTY AND PARAM(8)<=300 THEN 0.03', 'calcSpecialCost1', NULL, 'Special', 72),
(73, 'SPECIAL_COST_2',     'Special Cost 2',               'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, false,
  'IF PARAM(1)=PTY AND PARAM(71)=RWDH THEN 0.05', 'calcSpecialCost2', NULL, 'Special', 73),

-- Steam/Softner/Washing (Superba)
(74, 'STEAM_COST_CNG',     'Steam Cost (CNG)',             'LOOKUP',      'NUMERIC', 'USD/kg', NULL,         false, true,  NULL, NULL, 'PARAM_DATA', 'Process', 74),
(75, 'SOFTNER_COST',       'Softner Cost',                 'LOOKUP',      'NUMERIC', 'USD/kg', NULL,         false, true,  NULL, NULL, 'PARAM_DATA', 'Process', 75),
(76, 'WASHING_COST',       'Washing Cost',                 'LOOKUP',      'NUMERIC', 'USD/kg', NULL,         false, true,  NULL, NULL, 'PARAM_DATA', 'Process', 76),

-- Production
(77, 'PRODUCTION_INDEX',   'Production Index',             'NOT_USED',    'NUMERIC', NULL,    NULL,          false, false, NULL, NULL, NULL, 'Production', 77),
(78, 'NET_PRODUCTION',     'Net Production',               'CALCULATION', 'NUMERIC', 'kg/day', NULL,         false, false,
  'PARAM(3)*PARAM(4)*PARAM(5)/100*PARAM(6)*PARAM(9)*24*60/9000000*(1-PARAM(16))', 'calcNetProduction', NULL, 'Production', 78),

-- Per-day costs (from MACHINE master)
(79, 'POWER_PER_DAY',      'Power Per Day',                'LOOKUP',      'NUMERIC', 'USD/day', NULL,        false, true,  NULL, NULL, 'MACHINE', 'Machine Cost', 79),
(80, 'MANPOWER_PER_DAY',   'Manpower Per Day',             'LOOKUP',      'NUMERIC', 'USD/day', NULL,        false, true,  NULL, NULL, 'MACHINE', 'Machine Cost', 80),
(81, 'OVERHEAD_PER_DAY',   'Overhead Per Day',             'LOOKUP',      'NUMERIC', 'USD/day', NULL,        false, true,  NULL, NULL, 'MACHINE', 'Machine Cost', 81),
(82, 'SPARES_PER_DAY',     'Spares Cost / Day',            'LOOKUP',      'NUMERIC', 'USD/day', NULL,        false, true,  NULL, NULL, 'MACHINE', 'Machine Cost', 82),

-- Per-kg conversions
(83, 'POWER_PER_KG',       'Power Per Kgs',                'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'IF PARAM(1) NOT IN POY THEN PARAM(79)/PARAM(78) ELSE POY-specific formula', 'calcPowerPerKg', NULL, 'Machine Cost', 83),
(84, 'MANPOWER_PER_KG',    'Manpower Per Kgs',             'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'IF PARAM(1) NOT IN POY THEN PARAM(80)/PARAM(78) ELSE POY-specific formula', 'calcManpowerPerKg', NULL, 'Machine Cost', 84),
(85, 'OVERHEAD_PER_KG',    'Overhead Per Kgs',             'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'IF PARAM(1) NOT IN POY THEN PARAM(81)/PARAM(78) ELSE POY-specific formula', 'calcOverheadPerKg', NULL, 'Machine Cost', 85),
(86, 'CONS_SPARES_PER_KG', 'Consumables & Spares Per Kgs', 'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'IF PARAM(1) NOT IN POY THEN PARAM(82)/PARAM(78) ELSE POY-specific formula', 'calcConsSparesPerKg', NULL, 'Machine Cost', 86),
(87, 'TOTAL_CONVERSION',   'Total',                        'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(83) + PARAM(84) + PARAM(85) + PARAM(86)', 'calcTotalConversion', NULL, 'Machine Cost', 87),

-- Conversion totals
(88, 'CAPTIVE_CONV_EX_MB', 'Only Conv Captive Packing ex MB', 'CALCULATION', 'NUMERIC', 'USD/kg', NULL,      false, true,
  'PARAM(37)+PARAM(54)+PARAM(57)+PARAM(72)+PARAM(87)+PARAM(49)+PARAM(73)+PARAM(74)+PARAM(75)+PARAM(76)+PARAM(121)',
  'calcCaptiveConvExMB', NULL, 'Cost', 88),
(89, 'DELIVERY_CONV_EX_MB','Only Conv Delivery Packing ex MB','CALCULATION', 'NUMERIC', 'USD/kg', NULL,      false, true,
  'PARAM(43)+PARAM(54)+PARAM(57)+PARAM(72)+PARAM(87)+PARAM(49)+PARAM(73)+PARAM(74)+PARAM(75)+PARAM(76)+PARAM(121)',
  'calcDeliveryConvExMB', NULL, 'Cost', 89),
(90, 'CAPTIVE_BEFORE_QL',  'Captive Cost Before Quality Loss','CALCULATION', 'NUMERIC', 'USD/kg', NULL,     false, true,
  'PARAM(51) + PARAM(69) + PARAM(88)', 'calcCaptiveBeforeQL', NULL, 'Cost', 90),
(91, 'DELIVERY_BEFORE_QL', 'Delivery Cost Before Quality Loss','CALCULATION', 'NUMERIC', 'USD/kg', NULL,    false, true,
  'PARAM(51) + PARAM(69) + PARAM(89)', 'calcDeliveryBeforeQL', NULL, 'Cost', 91),

-- Quality loss
(92, 'STD_VALUE_LOSS',     'Standard Value Loss',          'LOOKUP',      'NUMERIC', NULL,    NULL,          false, false, NULL, NULL, 'PRODUCT_GRADE', 'Quality', 92),
(93, 'VALUE_LOSS',         'Value loss',                   'LOOKUP',      'NUMERIC', NULL,    NULL,          false, false, NULL, NULL, 'PRODUCT_GRADE', 'Quality', 93),
(94, 'NON_STD_SPECIAL',    'Non Standard Special Product', 'LOOKUP',      'NUMERIC', NULL,    NULL,          false, false, NULL, NULL, 'PRODUCT_GRADE', 'Quality', 94),
(95, 'BC_SPECIAL',         'BC Special Product',           'LOOKUP',      'NUMERIC', NULL,    NULL,          false, false, NULL, NULL, 'PRODUCT_GRADE', 'Quality', 95),
(96, 'NON_STD_VALUE_LOSS', 'Non Standard Value Loss',      'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  '(PARAM(19)+PARAM(20)+PARAM(21))/100*PARAM(94)', 'calcNonStdValueLoss', NULL, 'Quality', 96),
(97, 'BC_LOSS_CAPTIVE',    'BC Value Loss (Captive)',      'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  '(PARAM(22)+PARAM(23))/100*(PARAM(90)-PARAM(95))', 'calcBCLossCaptive', NULL, 'Quality', 97),
(98, 'BC_LOSS_DELIVERY',   'BC Value Loss (Delivery)',     'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  '(PARAM(22)+PARAM(23))/100*(PARAM(91)-PARAM(95))', 'calcBCLossDelivery', NULL, 'Quality', 98),
(99, 'QL_CAPTIVE',         'Quality Loss Captive Cost',    'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(96) + PARAM(97)', 'calcQLCaptive', NULL, 'Quality', 99),
(100,'QL_DELIVERY',        'Quality Loss Delivery Cost',   'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(96) + PARAM(98)', 'calcQLDelivery', NULL, 'Quality', 100),

-- FINAL COSTS
(101,'CAPTIVE_FINAL',      'Captive Cost with Quality Loss','CALCULATION', 'NUMERIC', 'USD/kg', NULL,        true,  true,
  'PARAM(90) + PARAM(99)', 'calcCaptiveFinal', NULL, 'Cost', 101),
(102,'DELIVERY_FINAL',     'Delivery Cost with Quality Loss','CALCULATION','NUMERIC', 'USD/kg', NULL,        true,  true,
  'PARAM(91) + PARAM(100)', 'calcDeliveryFinal', NULL, 'Cost', 102),

-- Volume bucket info
(103,'CHANGEOVER_LOSS',    'Change Over Quantity Loss (Info)','LOOKUP',    'NUMERIC', 'kg',    NULL,         false, false, NULL, NULL, 'CHANGEOVER_LOSS', 'Volume', 103),
(104,'VB1_QTY',            'Volume Bucket 1 - Quantity',   'LOOKUP',      'NUMERIC', 'kg',    NULL,          false, false, NULL, NULL, 'VOLUME_BUCKET', 'Volume', 104),
(105,'VB2_QTY',            'Volume Bucket 2 - Quantity',   'LOOKUP',      'NUMERIC', 'kg',    NULL,          false, false, NULL, NULL, 'VOLUME_BUCKET', 'Volume', 105),
(106,'VB3_QTY',            'Volume Bucket 3 - Quantity',   'LOOKUP',      'NUMERIC', 'kg',    NULL,          false, false, NULL, NULL, 'VOLUME_BUCKET', 'Volume', 106),
(107,'VB4_QTY',            'Volume Bucket 4 - Quantity',   'LOOKUP',      'NUMERIC', 'kg',    NULL,          false, false, NULL, NULL, 'VOLUME_BUCKET', 'Volume', 107),
(108,'VB5_QTY',            'Volume Bucket 5 - Quantity',   'LOOKUP',      'NUMERIC', 'kg',    NULL,          false, false, NULL, NULL, 'VOLUME_BUCKET', 'Volume', 108),
(109,'VB1_LOSS',           'Volume Bucket 1 - Loss',       'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Volume', 109),
(110,'VB2_LOSS',           'Volume Bucket 2 - Loss',       'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Volume', 110),
(111,'VB3_LOSS',           'Volume Bucket 3 - Loss',       'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Volume', 111),
(112,'VB4_LOSS',           'Volume Bucket 4 - Loss',       'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Volume', 112),
(113,'VB5_LOSS',           'Volume Bucket 5 - Loss',       'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Volume', 113),
(114,'VB1_DEL_COST',       'Volume Bucket 1 - Delivery Cost','CALCULATION','NUMERIC','USD/kg', NULL,         false, true,
  'PARAM(102) + PARAM(109)', 'calcVB1DelCost', NULL, 'Volume', 114),
(115,'VB2_DEL_COST',       'Volume Bucket 2 - Delivery Cost','CALCULATION','NUMERIC','USD/kg', NULL,         false, true,
  'PARAM(102) + PARAM(110)', 'calcVB2DelCost', NULL, 'Volume', 115),
(116,'VB3_DEL_COST',       'Volume Bucket 3 - Delivery Cost','CALCULATION','NUMERIC','USD/kg', NULL,         false, true,
  'PARAM(102) + PARAM(111)', 'calcVB3DelCost', NULL, 'Volume', 116),
(117,'VB4_DEL_COST',       'Volume Bucket 4 - Delivery Cost','CALCULATION','NUMERIC','USD/kg', NULL,         false, true,
  'PARAM(102) + PARAM(112)', 'calcVB4DelCost', NULL, 'Volume', 117),
(118,'VB5_DEL_COST',       'Volume Bucket 5 - Delivery Cost','CALCULATION','NUMERIC','USD/kg', NULL,         false, true,
  'PARAM(102) + PARAM(113)', 'calcVB5DelCost', NULL, 'Volume', 118),

-- Additional
(119,'CUSTOMER',           'Customer',                     'ENTRY',       'TEXT',    NULL,    'Marketing',   false, false, NULL, NULL, NULL, 'Sales', 119),
(120,'VALUATION',          'Valuation',                    'ENTRY',       'NUMERIC', 'USD',   'Marketing',   false, true,  NULL, NULL, NULL, 'Sales', 120),
(121,'OIL_GAIN',           'Oil Gain',                     'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(17) * 1.65', 'calcOilGain', NULL, 'Oil', 121),
(122,'DOZING_ADJUST',      'Dozing Adjust',                'ENTRY',       'NUMERIC', NULL,    'Engineering', false, false, NULL, NULL, NULL, 'MB', 122),
(123,'PCT_ADD_TOP_95',     '% Add Top 95',                 'ENTRY',       'NUMERIC', '%',     'Finance',     false, true,  NULL, NULL, NULL, 'Top95', 123),
(124,'VALUE_TOP_95',       'Value Top 95 before Process',  'ENTRY',       'NUMERIC', 'USD/kg', 'Finance',    false, true,  NULL, NULL, NULL, 'Top95', 124),
(125,'TOP_95_X_PCT_ADD',   'Top 95 X % Add',               'CALCULATION', 'NUMERIC', 'USD/kg', NULL,         false, true,
  'PARAM(124) * PARAM(123) / 100', 'calcTop95XPctAdd', NULL, 'Top95', 125);

-- Sync serial sequence to match inserted IDs
SELECT setval('cost_parameter_master_CPRM_param_id_seq', 125);


-- ============================================================================
-- VERIFICATION QUERIES
-- ============================================================================

-- Count params by function type
-- SELECT CPRM_function_type, COUNT(*) FROM cost_parameter_master GROUP BY 1;

-- Count required params
-- SELECT COUNT(*) FROM cost_parameter_master WHERE CPRM_is_required_for_costing = true;

-- Count period-dependent params
-- SELECT COUNT(*) FROM cost_parameter_master WHERE CPRM_is_period_dependent = true;

-- Count lookup params by master
-- SELECT CPRM_lookup_master_code, COUNT(*) FROM cost_parameter_master
-- WHERE CPRM_function_type = 'LOOKUP' GROUP BY 1;
