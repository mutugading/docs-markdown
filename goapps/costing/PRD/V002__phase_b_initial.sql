-- ============================================================================
-- Costing Workflow Suite — Phase B: Product Order & BOM Management
-- DDL — PostgreSQL 14+
-- Version: 1.3 | May 2026
-- Convention: Column Prefix Naming (setiap kolom punya prefix inisial table)
-- ============================================================================

-- ============================================================================
-- PREFIX REGISTRY — PHASE B
-- ============================================================================
-- CPT_   → cost_product_type
-- CPCC_  → cost_product_code_counter
-- CPM_   → cost_product_master
-- CRMT_  → cost_rm_type
-- CEI_   → cost_erp_item
-- CEG_   → cost_erp_grade
-- CES_   → cost_erp_shade
-- CPO_   → cost_product_order
-- CPOV_  → cost_product_order_version
-- CPOC_  → cost_product_order_component
-- CPOE_  → cost_product_order_exploded  (materialized view)
-- CBL_   → cost_bom_layout
-- CAL_   → cost_audit_log               (shared with Phase A)
-- ============================================================================


-- ============================================================================
-- PART 1: MASTER / REFERENCE TABLES
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. cost_product_type (CPT_)
-- Master jenis product: POY, PTY, TTY, TTS, ATY, ITY, TCH, TTM, dll.
-- Dikelola Admin. Dipakai untuk product code generation.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_type (
  CPT_type_id               SERIAL          PRIMARY KEY,
  CPT_type_code             VARCHAR(5)      NOT NULL UNIQUE,   -- "POY","PTY","TTY","ATY","ITY","TCH","TTM"
  CPT_type_name             VARCHAR(100)    NOT NULL,          -- "Partially Oriented Yarn"
  CPT_is_active             BOOLEAN         NOT NULL DEFAULT true,
  CPT_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPT_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_product_type
  IS 'Master jenis product (POY/PTY/TTY/dll). Dipakai untuk product code prefix dan klasifikasi.';


-- ----------------------------------------------------------------------------
-- 2. cost_product_code_counter (CPCC_)
-- Atomic auto-increment counter per (product_type + YYMM).
-- Dipakai saat generate CPM_product_code.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_code_counter (
  CPCC_counter_id           SERIAL          PRIMARY KEY,
  CPCC_product_type_id      INT             NOT NULL
                            REFERENCES cost_product_type(CPT_type_id),
  CPCC_year_month           VARCHAR(4)      NOT NULL,          -- "2605" = Jun 2026
  CPCC_last_number          INT             NOT NULL DEFAULT 0,

  CONSTRAINT uq_cpcc_type_month
    UNIQUE (CPCC_product_type_id, CPCC_year_month)
);

COMMENT ON TABLE cost_product_code_counter
  IS 'Auto-increment counter per (product_type + YYMM). Atomic update saat product code generation.';


-- ----------------------------------------------------------------------------
-- 3. cost_rm_type (CRMT_)
-- Master RM type classification. User-definable.
-- reference_target menentukan FK mana yang dipakai di component.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_rm_type (
  CRMT_type_id              SERIAL          PRIMARY KEY,
  CRMT_type_code            VARCHAR(30)     NOT NULL UNIQUE,
  CRMT_type_name            VARCHAR(100)    NOT NULL,
  CRMT_reference_target     VARCHAR(10)     NOT NULL,          -- PRODUCT / MASTER
  CRMT_allow_sub_sequence   BOOLEAN         NOT NULL DEFAULT false,
  CRMT_is_active            BOOLEAN         NOT NULL DEFAULT true,
  CRMT_created_at           TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_crmt_reference_target
    CHECK (CRMT_reference_target IN ('PRODUCT', 'MASTER'))
);

COMMENT ON TABLE cost_rm_type
  IS 'Master RM type (user-definable). reference_target = PRODUCT → FK ke product_master; MASTER → FK ke ERP item.';


-- ============================================================================
-- PART 2: ERP REPLICA TABLES (read-only, synced from Oracle)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 4. cost_erp_item (CEI_)
-- Replica master item dari ERP Oracle. Read-only di costing.
-- Sync: scheduled job (CDC / periodic) — TBD.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_erp_item (
  CEI_item_id               BIGSERIAL       PRIMARY KEY,
  CEI_item_code             VARCHAR(20)     NOT NULL UNIQUE,   -- "PTY0000001"
  CEI_item_name             VARCHAR(255),                      -- "PTY 150/36/RND/DSD/NI/DH/N/1/Z"
  CEI_item_type             VARCHAR(10),                       -- "POY","PTY","TTY" etc
  CEI_is_active             BOOLEAN         NOT NULL DEFAULT true,
  CEI_synced_at             TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_erp_item
  IS 'Replica master item dari Oracle ERP. Read-only. Sync via scheduled job.';


-- ----------------------------------------------------------------------------
-- 5. cost_erp_grade (CEG_)
-- Replica master grade (item_grade_code_1) dari ERP Oracle.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_erp_grade (
  CEG_grade_id              SERIAL          PRIMARY KEY,
  CEG_grade_code            VARCHAR(20)     NOT NULL UNIQUE,   -- "AX","AM","B","C"
  CEG_grade_name            VARCHAR(100),
  CEG_is_active             BOOLEAN         NOT NULL DEFAULT true,
  CEG_synced_at             TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_erp_grade
  IS 'Replica master grade (item_grade_code_1) dari Oracle ERP.';


-- ----------------------------------------------------------------------------
-- 6. cost_erp_shade (CES_)
-- Replica master shade (item_grade_code_2) dari ERP Oracle.
-- Dipakai di Phase A untuk autocomplete shade.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_erp_shade (
  CES_shade_id              SERIAL          PRIMARY KEY,
  CES_shade_code            VARCHAR(20)     NOT NULL UNIQUE,   -- "NL","Z114S","Z108S"
  CES_shade_name            VARCHAR(100),
  CES_is_active             BOOLEAN         NOT NULL DEFAULT true,
  CES_synced_at             TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_erp_shade
  IS 'Replica master shade (item_grade_code_2) dari Oracle ERP. Juga dipakai Phase A.';


-- ============================================================================
-- PART 3: PRODUCT MASTER
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 7. cost_product_master (CPM_)
-- Identitas product di costing system. Setiap kombinasi item+shade = 1 record.
-- ERP fields = attribut informational, bukan FK.
-- Product code format: CST + TYPE(3) + YYMM(4) + AUTO(6)
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_master (
  -- PK
  CPM_product_sys_id        BIGSERIAL       PRIMARY KEY,

  -- Business Key (generated)
  CPM_product_code          VARCHAR(20)     NOT NULL UNIQUE,
  -- Format: CSTPTY2605000001
  -- CST(3) + product_type_code(3) + YYMM(4) + auto_number(6)

  -- Product Identity
  CPM_product_type_id       INT             NOT NULL
                            REFERENCES cost_product_type(CPT_type_id),
  CPM_product_name          TEXT            NOT NULL,
  -- Contoh: "PTY 150/36/RND/DSD/NI/DH/N/1/Z"

  -- Product Attributes
  CPM_shade_code            VARCHAR(50),                       -- free-text introductory
  CPM_grade_code            VARCHAR(20)     NOT NULL DEFAULT 'AX',
  -- ⚠️ Default top grade. Editable jika differentiation diperlukan.
  CPM_description           TEXT,

  -- ERP Linkage (semua attribut informational, nullable)
  CPM_erp_item_code         VARCHAR(20),                       -- "PTY0000001"
  CPM_erp_grade_code_1      VARCHAR(20),                       -- "AX"
  CPM_erp_grade_code_2      VARCHAR(20),                       -- "Z108S"
  CPM_erp_linked_at         TIMESTAMPTZ,
  CPM_erp_linked_by         VARCHAR(64),

  -- Status
  CPM_is_active             BOOLEAN         NOT NULL DEFAULT true,

  -- Audit
  CPM_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPM_created_by            VARCHAR(64)     NOT NULL,
  CPM_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPM_updated_by            VARCHAR(64)     NOT NULL
);

COMMENT ON TABLE cost_product_master
  IS 'Product identity di costing. 1 record = 1 product (item+shade). ERP fields = informational attribute.';


-- ============================================================================
-- PART 4: PRODUCT ORDER & BOM
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 8. cost_product_order (CPO_)
-- BOM definition: HOW to make a product. References product_master (WHAT).
-- Menggantikan product_order di PRD v1.2 — field identity pindah ke CPM.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_order (
  -- PK
  CPO_order_id              BIGSERIAL       PRIMARY KEY,

  -- Product Reference
  CPO_product_sys_id        BIGINT          NOT NULL
                            REFERENCES cost_product_master(CPM_product_sys_id),

  -- Manufacturing Config
  CPO_cyl_type_id           INT,                               -- FK master cyl_type (ERP)

  -- Version Tracking
  CPO_current_version_id    BIGINT,                            -- FK cost_product_order_version (set after first commit)

  -- Status
  CPO_is_active             BOOLEAN         NOT NULL DEFAULT true,  -- soft-delete

  -- Audit
  CPO_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPO_created_by            VARCHAR(64)     NOT NULL,
  CPO_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPO_updated_by            VARCHAR(64)     NOT NULL
);

COMMENT ON TABLE cost_product_order
  IS 'BOM definition untuk 1 product. References product_master. Versioned via product_order_version.';


-- ----------------------------------------------------------------------------
-- 9. cost_product_order_version (CPOV_)
-- Snapshot BOM pada 1 titik waktu. Setiap commit = version baru.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_order_version (
  CPOV_version_id           BIGSERIAL       PRIMARY KEY,
  CPOV_order_id             BIGINT          NOT NULL
                            REFERENCES cost_product_order(CPO_order_id),
  CPOV_version_no           INT             NOT NULL,          -- increment per order (1,2,3...)
  CPOV_status               VARCHAR(20)     NOT NULL DEFAULT 'draft',
  CPOV_effective_from       TIMESTAMPTZ,                       -- kapan version aktif
  CPOV_effective_to         TIMESTAMPTZ,                       -- kapan superseded (NULL = masih aktif)
  CPOV_cycle_override       BOOLEAN         NOT NULL DEFAULT false,
  CPOV_created_at           TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPOV_created_by           VARCHAR(64)     NOT NULL,

  CONSTRAINT chk_cpov_status
    CHECK (CPOV_status IN ('draft', 'active', 'superseded')),

  CONSTRAINT uq_cpov_order_version
    UNIQUE (CPOV_order_id, CPOV_version_no)
);

-- Hanya 1 version active per order
CREATE UNIQUE INDEX idx_cpov_one_active_per_order
  ON cost_product_order_version(CPOV_order_id)
  WHERE CPOV_status = 'active';

COMMENT ON TABLE cost_product_order_version
  IS 'BOM version snapshot. Setiap commit perubahan komponen = version baru. Max 1 active per order.';

-- Add FK from product_order back to version (deferred, circular)
ALTER TABLE cost_product_order
  ADD CONSTRAINT fk_cpo_current_version
  FOREIGN KEY (CPO_current_version_id)
  REFERENCES cost_product_order_version(CPOV_version_id);


-- ----------------------------------------------------------------------------
-- 10. cost_product_order_component (CPOC_)
-- Komponen langsung sebuah version. Normalized single-level BOM.
-- Dual FK: rm_product_sys_id (Captive) ATAU rm_master_item_id (Store Rate).
-- rm_type dari master cost_rm_type (user-definable).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_order_component (
  CPOC_component_id         BIGSERIAL       PRIMARY KEY,
  CPOC_version_id           BIGINT          NOT NULL
                            REFERENCES cost_product_order_version(CPOV_version_id),
  CPOC_sequence_no          INT             NOT NULL,
  CPOC_sub_sequence         INT,                               -- untuk Multi Yarn-like types
  CPOC_sub_type             VARCHAR(30),                       -- Yarn-Cap / Stores / PTY / REWINDING

  -- RM Type (user-definable via master)
  CPOC_rm_type_id           INT             NOT NULL
                            REFERENCES cost_rm_type(CRMT_type_id),

  -- Dual FK: mutually exclusive berdasarkan CRMT_reference_target
  CPOC_rm_product_sys_id    BIGINT
                            REFERENCES cost_product_master(CPM_product_sys_id),
  CPOC_rm_master_item_id    BIGINT
                            REFERENCES cost_erp_item(CEI_item_id),

  -- Fallback description (untuk RM yang belum ada di master)
  CPOC_rm_description       VARCHAR(255),

  -- Mutually exclusive FK
  CONSTRAINT chk_cpoc_rm_ref_exclusive
    CHECK (
      (CPOC_rm_product_sys_id IS NOT NULL AND CPOC_rm_master_item_id IS NULL)
      OR
      (CPOC_rm_product_sys_id IS NULL AND CPOC_rm_master_item_id IS NOT NULL)
      OR
      -- Fallback: kedua NULL + description diisi (RM belum di-master)
      (CPOC_rm_product_sys_id IS NULL AND CPOC_rm_master_item_id IS NULL
       AND CPOC_rm_description IS NOT NULL)
    ),

  -- Sequence unique per version (kecuali sub_sequence)
  CONSTRAINT uq_cpoc_sequence
    UNIQUE (CPOC_version_id, CPOC_sequence_no, CPOC_sub_sequence)
);

COMMENT ON TABLE cost_product_order_component
  IS 'Komponen langsung BOM (single-level). Dual FK: product_master (Captive) atau ERP item (Store Rate).';


-- ----------------------------------------------------------------------------
-- 11. cost_bom_layout (CBL_)
-- Posisi visual node pada Flow Editor per version.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_bom_layout (
  CBL_layout_id             BIGSERIAL       PRIMARY KEY,
  CBL_version_id            BIGINT          NOT NULL
                            REFERENCES cost_product_order_version(CPOV_version_id),
  CBL_node_ref_type         VARCHAR(10)     NOT NULL,          -- PRODUCT / MASTER
  CBL_node_ref_id           BIGINT          NOT NULL,
  CBL_pos_x                 INT             NOT NULL,
  CBL_pos_y                 INT             NOT NULL,
  CBL_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_cbl_node_ref_type
    CHECK (CBL_node_ref_type IN ('PRODUCT', 'MASTER')),

  CONSTRAINT uq_cbl_version_node
    UNIQUE (CBL_version_id, CBL_node_ref_type, CBL_node_ref_id)
);

COMMENT ON TABLE cost_bom_layout
  IS 'Posisi visual node pada Flow Editor. 1 record = 1 node position per version.';


-- ============================================================================
-- PART 5: MATERIALIZED VIEW — BOM EXPLOSION
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 12. cost_product_order_exploded (CPOE_) — materialized view
-- Flatten rekursif dari FG ke RM terbawah.
-- Refresh: trigger on version commit + scheduled daily safety net.
-- ----------------------------------------------------------------------------
CREATE MATERIALIZED VIEW cost_product_order_exploded AS
WITH RECURSIVE bom_tree AS (
  -- Base: direct children of each active version
  SELECT
    cpo.CPO_order_id,
    cpo.CPO_product_sys_id      AS CPOE_root_product_sys_id,
    cpov.CPOV_version_id        AS CPOE_version_id,
    cpoc.CPOC_sequence_no       AS CPOE_sequence_no,
    cpoc.CPOC_sub_sequence      AS CPOE_sub_sequence,
    cpoc.CPOC_sub_type          AS CPOE_sub_type,
    cpoc.CPOC_rm_type_id        AS CPOE_rm_type_id,
    cpoc.CPOC_rm_product_sys_id AS CPOE_rm_product_sys_id,
    cpoc.CPOC_rm_master_item_id AS CPOE_rm_master_item_id,
    cpoc.CPOC_rm_description    AS CPOE_rm_description,
    1                           AS CPOE_level,
    CAST(cpoc.CPOC_sequence_no AS TEXT) AS CPOE_sequence_path
  FROM cost_product_order cpo
  JOIN cost_product_order_version cpov
    ON cpo.CPO_current_version_id = cpov.CPOV_version_id
  JOIN cost_product_order_component cpoc
    ON cpov.CPOV_version_id = cpoc.CPOC_version_id
  WHERE cpo.CPO_is_active = true

  UNION ALL

  -- Recursive: expand Captive Cost children
  SELECT
    bt.CPO_order_id,
    bt.CPOE_root_product_sys_id,
    child_ver.CPOV_version_id,
    child_comp.CPOC_sequence_no,
    child_comp.CPOC_sub_sequence,
    child_comp.CPOC_sub_type,
    child_comp.CPOC_rm_type_id,
    child_comp.CPOC_rm_product_sys_id,
    child_comp.CPOC_rm_master_item_id,
    child_comp.CPOC_rm_description,
    bt.CPOE_level + 1,
    bt.CPOE_sequence_path || ' > ' || child_comp.CPOC_sequence_no
  FROM bom_tree bt
  -- Only expand nodes that reference another product (Captive Cost-like)
  JOIN cost_product_order child_order
    ON bt.CPOE_rm_product_sys_id = child_order.CPO_product_sys_id
    AND child_order.CPO_is_active = true
  JOIN cost_product_order_version child_ver
    ON child_order.CPO_current_version_id = child_ver.CPOV_version_id
  JOIN cost_product_order_component child_comp
    ON child_ver.CPOV_version_id = child_comp.CPOC_version_id
  WHERE bt.CPOE_rm_product_sys_id IS NOT NULL
    AND bt.CPOE_level < 20  -- safety limit
)
SELECT
  CPOE_root_product_sys_id,
  CPOE_version_id,
  CPOE_level,
  CPOE_sequence_no,
  CPOE_sub_sequence,
  CPOE_sub_type,
  CPOE_rm_type_id,
  CPOE_rm_product_sys_id,
  CPOE_rm_master_item_id,
  CPOE_rm_description,
  CPOE_sequence_path
FROM bom_tree;

-- Unique index required for REFRESH CONCURRENTLY
CREATE UNIQUE INDEX idx_cpoe_unique
  ON cost_product_order_exploded(
    CPOE_root_product_sys_id,
    CPOE_version_id,
    CPOE_level,
    CPOE_sequence_path
  );

COMMENT ON MATERIALIZED VIEW cost_product_order_exploded
  IS 'Flatten BOM explosion. Refresh on version commit + daily safety net. Max depth 20.';


-- ============================================================================
-- PART 6: INDEXES
-- ============================================================================

-- cost_product_master
CREATE INDEX idx_cpm_product_type
  ON cost_product_master(CPM_product_type_id);

CREATE INDEX idx_cpm_erp_item_code
  ON cost_product_master(CPM_erp_item_code)
  WHERE CPM_erp_item_code IS NOT NULL;

CREATE INDEX idx_cpm_active
  ON cost_product_master(CPM_is_active)
  WHERE CPM_is_active = true;

-- 🚀 PERF: full-text search pada product name
CREATE INDEX idx_cpm_product_name_search
  ON cost_product_master
  USING gin(to_tsvector('simple', CPM_product_name));

-- cost_product_order
CREATE INDEX idx_cpo_product_sys
  ON cost_product_order(CPO_product_sys_id);

CREATE INDEX idx_cpo_active
  ON cost_product_order(CPO_is_active)
  WHERE CPO_is_active = true;

-- cost_product_order_version
CREATE INDEX idx_cpov_order_status
  ON cost_product_order_version(CPOV_order_id, CPOV_status);

-- cost_product_order_component
CREATE INDEX idx_cpoc_version_seq
  ON cost_product_order_component(CPOC_version_id, CPOC_sequence_no);

-- 🚀 PERF: where-used query — "product X dipakai di mana saja?"
CREATE INDEX idx_cpoc_rm_product
  ON cost_product_order_component(CPOC_rm_product_sys_id)
  WHERE CPOC_rm_product_sys_id IS NOT NULL;

CREATE INDEX idx_cpoc_rm_master_item
  ON cost_product_order_component(CPOC_rm_master_item_id)
  WHERE CPOC_rm_master_item_id IS NOT NULL;

CREATE INDEX idx_cpoc_rm_type
  ON cost_product_order_component(CPOC_rm_type_id);

-- cost_product_order_exploded (materialized view)
CREATE INDEX idx_cpoe_root
  ON cost_product_order_exploded(CPOE_root_product_sys_id, CPOE_level);

CREATE INDEX idx_cpoe_rm_product
  ON cost_product_order_exploded(CPOE_rm_product_sys_id)
  WHERE CPOE_rm_product_sys_id IS NOT NULL;

CREATE INDEX idx_cpoe_rm_master
  ON cost_product_order_exploded(CPOE_rm_master_item_id)
  WHERE CPOE_rm_master_item_id IS NOT NULL;

-- cost_bom_layout
CREATE INDEX idx_cbl_version
  ON cost_bom_layout(CBL_version_id);

-- cost_erp_item
CREATE INDEX idx_cei_item_type
  ON cost_erp_item(CEI_item_type);

-- cost_rm_type
CREATE INDEX idx_crmt_active
  ON cost_rm_type(CRMT_is_active)
  WHERE CRMT_is_active = true;


-- ============================================================================
-- PART 7: SEED DATA
-- ============================================================================

-- Product Types (initial)
INSERT INTO cost_product_type (CPT_type_code, CPT_type_name) VALUES
  ('POY', 'Partially Oriented Yarn'),
  ('PTY', 'Polyester Textured Yarn'),
  ('TTY', 'Twisted Textured Yarn'),
  ('TTS', 'Twisted Textured Yarn - Special'),
  ('ATY', 'Air Textured Yarn'),
  ('ITY', 'Intermingled Textured Yarn'),
  ('TCH', 'Textured Yarn - TCH Series'),
  ('TTM', 'Textured Yarn - TTM Series');

-- RM Types (initial — user-definable)
INSERT INTO cost_rm_type (CRMT_type_code, CRMT_type_name, CRMT_reference_target, CRMT_allow_sub_sequence) VALUES
  ('STORE_RATE',    'Store Rate',     'MASTER',  false),
  ('CAPTIVE_COST',  'Captive Cost',   'PRODUCT', false),
  ('MULTI_YARN',    'Multi Yarn',     'PRODUCT', true),
  ('UNEVEN_PACK',   'Uneven Packing', 'PRODUCT', false);


-- ============================================================================
-- HELPER: Product Code Generation Function
-- ============================================================================

-- 📌 Fungsi untuk atomic product code generation
-- Usage: SELECT generate_product_code(type_id, '2605');
CREATE OR REPLACE FUNCTION generate_product_code(
  p_type_id INT,
  p_year_month VARCHAR(4)
) RETURNS VARCHAR(20) AS $$
DECLARE
  v_type_code VARCHAR(5);
  v_next_number INT;
  v_product_code VARCHAR(20);
BEGIN
  -- Get type code
  SELECT CPT_type_code INTO v_type_code
  FROM cost_product_type
  WHERE CPT_type_id = p_type_id AND CPT_is_active = true;

  IF v_type_code IS NULL THEN
    RAISE EXCEPTION 'Product type ID % not found or inactive', p_type_id;
  END IF;

  -- Atomic upsert counter
  INSERT INTO cost_product_code_counter (CPCC_product_type_id, CPCC_year_month, CPCC_last_number)
  VALUES (p_type_id, p_year_month, 1)
  ON CONFLICT (CPCC_product_type_id, CPCC_year_month)
  DO UPDATE SET CPCC_last_number = cost_product_code_counter.CPCC_last_number + 1
  RETURNING CPCC_last_number INTO v_next_number;

  -- Build code: CST + TYPE(3) + YYMM(4) + AUTO(6)
  v_product_code := 'CST' || v_type_code || p_year_month || LPAD(v_next_number::TEXT, 6, '0');

  RETURN v_product_code;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION generate_product_code
  IS 'Atomic product code generation. Format: CSTPTY2605000001. Counter per (type + YYMM).';


-- ============================================================================
-- HELPER: Refresh Materialized View
-- ============================================================================

CREATE OR REPLACE FUNCTION refresh_bom_exploded()
RETURNS void AS $$
BEGIN
  REFRESH MATERIALIZED VIEW CONCURRENTLY cost_product_order_exploded;
END;
$$ LANGUAGE plpgsql;

COMMENT ON FUNCTION refresh_bom_exploded
  IS 'Refresh BOM explosion mat. view. Call after version commit or via scheduled job.';
