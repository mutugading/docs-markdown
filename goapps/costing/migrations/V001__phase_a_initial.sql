-- ============================================================================
-- Costing Workflow Suite — Phase A: Product Request Module
-- DDL — PostgreSQL 14+
-- Version: 1.1 | May 2026
-- Convention: Column Prefix Naming (setiap kolom punya prefix inisial table)
-- ============================================================================

-- ============================================================================
-- PREFIX REGISTRY — PHASE A
-- ============================================================================
-- CPR_   → cost_product_request
-- CPS_   → cost_product_spec
-- CRT_   → cost_request_type
-- CRR_   → cost_routing_rule
-- CRD_   → cost_routing_draft
-- CRDC_  → cost_routing_draft_component
-- CRC_   → cost_request_comment
-- CCEH_  → cost_comment_edit_history
-- CRM_   → cost_request_mention
-- CA_    → cost_attachment
-- CURM_  → cost_user_role_mapping
-- CN_    → cost_notification
-- CNP_   → cost_notification_preference
-- CAL_   → cost_audit_log
-- ============================================================================


-- ----------------------------------------------------------------------------
-- 1. cost_request_type (CRT_)
-- Configurable request types — determines workflow variant.
-- Initial seed: QUOTE, DEVELOPMENT
-- ----------------------------------------------------------------------------
CREATE TABLE cost_request_type (
  CRT_type_id               SERIAL          PRIMARY KEY,
  CRT_code                  VARCHAR(30)     NOT NULL UNIQUE,   -- QUOTE / DEVELOPMENT
  CRT_display_name          VARCHAR(80)     NOT NULL,
  CRT_state_machine_variant VARCHAR(30)     NOT NULL,          -- FULL / SHORTCUT_CAPABLE
  CRT_required_field_config JSONB           NOT NULL DEFAULT '{}',
  CRT_default_urgency       VARCHAR(10)     DEFAULT 'medium',  -- low / medium / high
  CRT_is_active             BOOLEAN         NOT NULL DEFAULT true,

  CONSTRAINT chk_crt_variant
    CHECK (CRT_state_machine_variant IN ('FULL', 'SHORTCUT_CAPABLE'))
);

COMMENT ON TABLE cost_request_type
  IS 'Configurable request types (Quote inquiry, Development/Sample). Determines workflow variant.';


-- ----------------------------------------------------------------------------
-- 2. cost_product_request (CPR_)
-- Entry-point ticket: satu request dari Marketing terkait product costing.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_request (
  -- PK
  CPR_request_id              BIGSERIAL       PRIMARY KEY,
  CPR_request_no              VARCHAR(30)     NOT NULL UNIQUE,  -- REQ-YYYYMM-NNNN

  -- Request Info (Section 1 form)
  CPR_request_type_id         INT             NOT NULL
                              REFERENCES cost_request_type(CRT_type_id),
  CPR_title                   VARCHAR(255)    NOT NULL,
  CPR_description             TEXT,
  CPR_customer_name           VARCHAR(255)    NOT NULL,         -- free-text
  CPR_customer_code           VARCHAR(50),                      -- optional FK master customer

  -- Classification — dual-confirmation pattern
  CPR_product_classification  VARCHAR(20)     NOT NULL,         -- existing / new
  CPR_verified_classification VARCHAR(20),                      -- diisi PIC Engineering
  CPR_classification_override_reason TEXT,

  -- Urgency & Timeline (Section 3 form)
  CPR_urgency_level           VARCHAR(10)     NOT NULL,         -- low / medium / high
  CPR_needed_by_date          DATE,
  CPR_target_volume           DECIMAL(18,4),
  CPR_target_price_range      VARCHAR(50),

  -- Status & Workflow
  CPR_status                  VARCHAR(30)     NOT NULL DEFAULT 'DRAFT',
  CPR_closed_substatus        VARCHAR(20),                      -- won / lost / cancelled / on_hold

  -- Feasibility Gate (baru v1.1 — diisi PIC saat UNDER_REVIEW)
  CPR_feasibility_decision    VARCHAR(20),                      -- FEASIBLE / NOT_FEASIBLE
  CPR_feasibility_note        TEXT,
  CPR_feasibility_by          VARCHAR(64),
  CPR_feasibility_at          TIMESTAMPTZ,

  -- Assignment
  CPR_assigned_to_user_id     VARCHAR(64),
  CPR_requester_user_id       VARCHAR(64)     NOT NULL,

  -- Audit
  CPR_created_at              TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPR_updated_at              TIMESTAMPTZ     NOT NULL DEFAULT now(),

  -- Constraints
  CONSTRAINT chk_cpr_classification
    CHECK (CPR_product_classification IN ('existing', 'new')),

  CONSTRAINT chk_cpr_urgency
    CHECK (CPR_urgency_level IN ('low', 'medium', 'high')),

  CONSTRAINT chk_cpr_status
    CHECK (CPR_status IN (
      'DRAFT', 'SUBMITTED', 'UNDER_REVIEW',
      'ROUTING_DEFINED', 'PARAMETER_PENDING', 'PARAMETER_COMPLETE',
      'COSTING_DONE', 'QUOTE_READY', 'QUOTED', 'CLOSED', 'REJECTED'
    )),

  CONSTRAINT chk_cpr_closed_substatus
    CHECK (
      CPR_status <> 'CLOSED'
      OR CPR_closed_substatus IN ('won', 'lost', 'cancelled', 'on_hold')
    ),

  CONSTRAINT chk_cpr_feasibility_decision
    CHECK (
      CPR_feasibility_decision IS NULL
      OR CPR_feasibility_decision IN ('FEASIBLE', 'NOT_FEASIBLE')
    ),

  -- ⚠️ Note wajib jika NOT_FEASIBLE
  CONSTRAINT chk_cpr_feasibility_note
    CHECK (
      CPR_feasibility_decision <> 'NOT_FEASIBLE'
      OR CPR_feasibility_note IS NOT NULL
    ),

  -- ⚠️ Override reason wajib jika verified ≠ marketing
  CONSTRAINT chk_cpr_override_reason
    CHECK (
      CPR_verified_classification IS NULL
      OR CPR_verified_classification = CPR_product_classification
      OR CPR_classification_override_reason IS NOT NULL
    )
);

COMMENT ON TABLE cost_product_request
  IS 'Entry-point ticket dari Marketing. Satu record = satu product request.';


-- ----------------------------------------------------------------------------
-- 3. cost_product_spec (CPS_)
-- Product specification — conditional 1:1 dengan product_request.
-- Diisi jika CPR_product_classification = new.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_product_spec (
  CPS_spec_id               BIGSERIAL       PRIMARY KEY,
  CPS_request_id            BIGINT          NOT NULL UNIQUE
                            REFERENCES cost_product_request(CPR_request_id),

  -- Raw Material Starting Point (single select)
  CPS_raw_material_type     VARCHAR(50)     NOT NULL,
  -- ⚠️ Hint dari Marketing — bukan constraint hard untuk Engineering
  CONSTRAINT chk_cps_rm_type
    CHECK (CPS_raw_material_type IN (
      'POY_BOUGHTOUT', 'CHIPS_SD', 'CHIPS_BRT', 'CHIPS_RECYCLE'
    )),

  -- Product Description (full spec, bukan code)
  CPS_product_description   TEXT            NOT NULL,

  -- Shade — hybrid: master FK atau free-text (termasuk 'natural')
  CPS_shade_id              INT,            -- FK master_shade_color(shade_id) saat master tersedia
  CPS_shade_custom_text     VARCHAR(100),
  CONSTRAINT chk_cps_shade_at_least_one
    CHECK (CPS_shade_id IS NOT NULL OR CPS_shade_custom_text IS NOT NULL),

  -- Physical Spec
  CPS_paper_tube_type_id    INT             NOT NULL,  -- FK master_paper_tube(paper_tube_id)
  CPS_weight_per_bobbin_kg  DECIMAL(10,3)   NOT NULL,
  CPS_box_type              VARCHAR(20)     NOT NULL,
  CONSTRAINT chk_cps_box_type
    CHECK (CPS_box_type IN ('JUMBO', 'NORMAL', 'PALLET')),

  -- Audit
  CPS_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CPS_created_by            VARCHAR(64)     NOT NULL
);

COMMENT ON TABLE cost_product_spec
  IS 'Product specification (Section 2 form). Conditional: hanya ada jika product_classification = new.';


-- ----------------------------------------------------------------------------
-- 4. cost_routing_rule (CRR_)
-- Admin-configurable routing rules — first-match evaluation.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_routing_rule (
  CRR_rule_id               SERIAL          PRIMARY KEY,
  CRR_priority              INT             NOT NULL,          -- lower = evaluated earlier
  CRR_condition             JSONB           NOT NULL,          -- predicate tree: AND/OR over fields
  CRR_action_type           VARCHAR(20)     NOT NULL,          -- AUTO_ASSIGN / TO_TRIAGE
  CRR_action_target         VARCHAR(100),                      -- user_id atau role (jika AUTO_ASSIGN)
  CRR_is_active             BOOLEAN         NOT NULL DEFAULT true,
  CRR_created_by            VARCHAR(64)     NOT NULL,
  CRR_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_crr_action_type
    CHECK (CRR_action_type IN ('AUTO_ASSIGN', 'TO_TRIAGE'))
);

COMMENT ON TABLE cost_routing_rule
  IS 'Admin-configurable routing rules. Evaluated first-match saat request submitted.';


-- ----------------------------------------------------------------------------
-- 5. cost_routing_draft (CRD_)
-- Shadow entity untuk Phase B Product Order.
-- Schema mirror Phase B tapi lebih lenient (free-text allowed).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_routing_draft (
  CRD_draft_id              BIGSERIAL       PRIMARY KEY,
  CRD_request_id            BIGINT          NOT NULL
                            REFERENCES cost_product_request(CPR_request_id),
  CRD_product_top_2         VARCHAR(100),                      -- placeholder, bisa diisi nanti
  CRD_item_code             VARCHAR(50),                       -- FK master item bila ada
  CRD_cyl_type_id           INT,                               -- FK master cyl_type
  CRD_shade_code            VARCHAR(50),                       -- seeded dari CPS_shade_id/custom_text
  CRD_raw_material_type     VARCHAR(50),                       -- seeded dari CPS_raw_material_type
  CRD_status                VARCHAR(20)     NOT NULL DEFAULT 'DRAFT',
  CRD_linked_product_order_id BIGINT,                          -- diisi saat promote ke Phase B
  CRD_created_by            VARCHAR(64)     NOT NULL,
  CRD_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CRD_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_crd_status
    CHECK (CRD_status IN ('DRAFT', 'LOCKED', 'PROMOTED'))
);

COMMENT ON TABLE cost_routing_draft
  IS 'Shadow entity Phase B product_order. shade_code dan raw_material_type di-seed dari product_spec.';


-- ----------------------------------------------------------------------------
-- 6. cost_routing_draft_component (CRDC_)
-- Komponen langsung dari routing draft — mirror Phase B product_order_component.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_routing_draft_component (
  CRDC_component_id         BIGSERIAL       PRIMARY KEY,
  CRDC_draft_id             BIGINT          NOT NULL
                            REFERENCES cost_routing_draft(CRD_draft_id)
                            ON DELETE CASCADE,
  CRDC_sequence_no          INT             NOT NULL,
  CRDC_sub_sequence         INT,                               -- untuk Multi Yarn
  CRDC_sub_type             VARCHAR(30),                       -- Yarn-Cap / Stores / PTY / REWINDING
  CRDC_rm_type              VARCHAR(30)     NOT NULL,
  CRDC_rm_ref_text          VARCHAR(255)    NOT NULL,          -- free-text reference
  CRDC_rm_ref_resolved_id   BIGINT,                            -- resolved bila ada master / Phase B product
  CRDC_notes                TEXT,

  CONSTRAINT chk_crdc_rm_type
    CHECK (CRDC_rm_type IN ('Store Rate', 'Captive Cost', 'Multi Yarn', 'Uneven Packing')),

  -- sequence_no harus unique per draft (kecuali sub_sequence untuk Multi Yarn)
  CONSTRAINT uq_crdc_sequence
    UNIQUE (CRDC_draft_id, CRDC_sequence_no, CRDC_sub_sequence)
);

COMMENT ON TABLE cost_routing_draft_component
  IS 'Komponen langsung routing draft. Mirror Phase B product_order_component (lenient).';


-- ----------------------------------------------------------------------------
-- 7. cost_request_comment (CRC_)
-- Thread comment per request — rich-text + plaintext copy.
-- Immutable: user tidak boleh hapus, hanya edit (history tersimpan).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_request_comment (
  CRC_comment_id            BIGSERIAL       PRIMARY KEY,
  CRC_request_id            BIGINT          NOT NULL
                            REFERENCES cost_product_request(CPR_request_id),
  CRC_parent_comment_id     BIGINT          -- reserved untuk threading (future)
                            REFERENCES cost_request_comment(CRC_comment_id),
  CRC_author_user_id        VARCHAR(64)     NOT NULL,
  CRC_body_richtext         JSONB           NOT NULL,          -- Tiptap/Lexical JSON tree
  CRC_body_plaintext        TEXT            NOT NULL,          -- plaintext copy untuk search & notif
  CRC_is_edited             BOOLEAN         NOT NULL DEFAULT false,
  CRC_is_hidden             BOOLEAN         NOT NULL DEFAULT false,  -- admin moderation
  CRC_hidden_reason         TEXT,
  CRC_created_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CRC_updated_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),

  CONSTRAINT chk_crc_hidden_reason
    CHECK (CRC_is_hidden = false OR CRC_hidden_reason IS NOT NULL)
);

COMMENT ON TABLE cost_request_comment
  IS 'Thread comment per request. body_richtext (JSONB) + body_plaintext (search/notif).';


-- ----------------------------------------------------------------------------
-- 8. cost_comment_edit_history (CCEH_)
-- Snapshot setiap kali comment di-edit — transparansi.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_comment_edit_history (
  CCEH_edit_id              BIGSERIAL       PRIMARY KEY,
  CCEH_comment_id           BIGINT          NOT NULL
                            REFERENCES cost_request_comment(CRC_comment_id),
  CCEH_body_richtext        JSONB           NOT NULL,          -- snapshot sebelum edit
  CCEH_body_plaintext       TEXT            NOT NULL,
  CCEH_edited_by            VARCHAR(64)     NOT NULL,
  CCEH_edited_at            TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_comment_edit_history
  IS 'Versi historis comment yang di-edit. Append-only.';


-- ----------------------------------------------------------------------------
-- 9. cost_request_mention (CRM_)
-- @mention lookup table — trigger notifikasi.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_request_mention (
  CRM_mention_id            BIGSERIAL       PRIMARY KEY,
  CRM_comment_id            BIGINT          NOT NULL
                            REFERENCES cost_request_comment(CRC_comment_id),
  CRM_mentioned_user_id     VARCHAR(64)     NOT NULL,
  CRM_is_notified           BOOLEAN         NOT NULL DEFAULT false,
  CRM_notified_at           TIMESTAMPTZ
);

COMMENT ON TABLE cost_request_mention
  IS '@mention lookup. Digunakan untuk trigger notifikasi ke user yang di-mention.';


-- ----------------------------------------------------------------------------
-- 10. cost_attachment (CA_)
-- Generic attachment: bisa di-attach ke request ATAU ke comment (mutually exclusive).
-- File disimpan di object storage, hanya metadata di DB.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_attachment (
  CA_attachment_id          BIGSERIAL       PRIMARY KEY,
  CA_request_id             BIGINT
                            REFERENCES cost_product_request(CPR_request_id),
  CA_comment_id             BIGINT
                            REFERENCES cost_request_comment(CRC_comment_id),
  CA_filename               VARCHAR(255)    NOT NULL,
  CA_mime_type              VARCHAR(100)    NOT NULL,
  CA_size_bytes             BIGINT          NOT NULL,
  CA_storage_key            VARCHAR(500)    NOT NULL,          -- path/key di object storage
  CA_uploaded_by            VARCHAR(64)     NOT NULL,
  CA_uploaded_at            TIMESTAMPTZ     NOT NULL DEFAULT now(),

  -- Exactly one of (request_id, comment_id) must be non-null
  CONSTRAINT chk_ca_parent_exclusive
    CHECK (
      (CA_request_id IS NOT NULL AND CA_comment_id IS NULL)
      OR
      (CA_request_id IS NULL AND CA_comment_id IS NOT NULL)
    )
);

COMMENT ON TABLE cost_attachment
  IS 'Generic attachment (request-level atau comment-level). File di object storage, metadata di sini.';


-- ----------------------------------------------------------------------------
-- 11. cost_user_role_mapping (CURM_)
-- Mapping SSO user → Tier + Functional Role.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_user_role_mapping (
  CURM_mapping_id           BIGSERIAL       PRIMARY KEY,
  CURM_user_id              VARCHAR(64)     NOT NULL,
  CURM_tier                 VARCHAR(20)     NOT NULL,
  CURM_functional_role      VARCHAR(30)     NOT NULL,
  CURM_is_active            BOOLEAN         NOT NULL DEFAULT true,
  CURM_effective_from       TIMESTAMPTZ     NOT NULL DEFAULT now(),
  CURM_effective_to         TIMESTAMPTZ,

  CONSTRAINT chk_curm_tier
    CHECK (CURM_tier IN ('User', 'Dept Lead', 'Manager', 'Admin')),

  CONSTRAINT chk_curm_functional_role
    CHECK (CURM_functional_role IN (
      'Marketing', 'Engineering', 'Produksi', 'RND', 'Finance', 'Admin'
    ))
);

COMMENT ON TABLE cost_user_role_mapping
  IS 'Mapping SSO user ke Tier (otoritas) + Functional Role (konteks fungsional).';


-- ----------------------------------------------------------------------------
-- 12. cost_notification (CN_)
-- Record in-app notification + status pengiriman email.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_notification (
  CN_notification_id        BIGSERIAL       PRIMARY KEY,
  CN_recipient_user_id      VARCHAR(64)     NOT NULL,
  CN_trigger_type           VARCHAR(50)     NOT NULL,          -- STATUS_CHANGE / MENTION / ASSIGNED / etc
  CN_request_id             BIGINT
                            REFERENCES cost_product_request(CPR_request_id),
  CN_payload                JSONB           NOT NULL DEFAULT '{}',
  CN_is_read                BOOLEAN         NOT NULL DEFAULT false,
  CN_email_sent_at          TIMESTAMPTZ,
  CN_created_at             TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_notification
  IS 'In-app notification + email delivery tracking.';


-- ----------------------------------------------------------------------------
-- 13. cost_notification_preference (CNP_)
-- Per-user per-trigger notification preferences.
-- Default: both channels active, immediate delivery.
-- ----------------------------------------------------------------------------
CREATE TABLE cost_notification_preference (
  CNP_pref_id               BIGSERIAL       PRIMARY KEY,
  CNP_user_id               VARCHAR(64)     NOT NULL,
  CNP_trigger_type           VARCHAR(50)     NOT NULL,
  CNP_channel_email         BOOLEAN         NOT NULL DEFAULT true,
  CNP_channel_in_app        BOOLEAN         NOT NULL DEFAULT true,
  CNP_digest_mode           VARCHAR(20)     NOT NULL DEFAULT 'immediate',

  CONSTRAINT uq_cnp_user_trigger
    UNIQUE (CNP_user_id, CNP_trigger_type),

  CONSTRAINT chk_cnp_digest_mode
    CHECK (CNP_digest_mode IN ('immediate', 'daily'))
);

COMMENT ON TABLE cost_notification_preference
  IS 'Per-user notification preferences per trigger type. Default: both channels, immediate.';


-- ----------------------------------------------------------------------------
-- 14. cost_audit_log (CAL_)
-- Cross-cutting audit log. Append-only, immutable.
-- Retention: 5 tahun (TBD dengan legal).
-- ----------------------------------------------------------------------------
CREATE TABLE cost_audit_log (
  CAL_log_id                BIGSERIAL       PRIMARY KEY,
  CAL_entity_type           VARCHAR(50)     NOT NULL,          -- cost_product_request / cost_routing_draft / etc
  CAL_entity_id             BIGINT          NOT NULL,
  CAL_operation             VARCHAR(20)     NOT NULL,          -- INSERT / UPDATE / DELETE / STATUS_CHANGE
  CAL_before_data           JSONB,                             -- snapshot sebelum perubahan
  CAL_after_data            JSONB,                             -- snapshot setelah perubahan
  CAL_user_id               VARCHAR(64)     NOT NULL,
  CAL_performed_at          TIMESTAMPTZ     NOT NULL DEFAULT now()
);

COMMENT ON TABLE cost_audit_log
  IS 'Immutable audit log. Setiap mutasi data tercatat. Retention 5 tahun.';


-- ============================================================================
-- INDEXES
-- ============================================================================

-- cost_product_request
CREATE INDEX idx_cpr_status_requester
  ON cost_product_request(CPR_status, CPR_requester_user_id);

CREATE INDEX idx_cpr_assigned
  ON cost_product_request(CPR_assigned_to_user_id)
  WHERE CPR_assigned_to_user_id IS NOT NULL;

CREATE INDEX idx_cpr_customer_name
  ON cost_product_request(CPR_customer_name);

CREATE INDEX idx_cpr_request_type
  ON cost_product_request(CPR_request_type_id);

-- 🚀 PERF: partial index untuk triage queue (UNDER_REVIEW + belum feasibility)
CREATE INDEX idx_cpr_triage_queue
  ON cost_product_request(CPR_status, CPR_urgency_level DESC, CPR_needed_by_date ASC)
  WHERE CPR_status = 'UNDER_REVIEW'
    AND CPR_feasibility_decision IS NULL;

-- 🚀 PERF: partial index untuk active requests (dashboard)
CREATE INDEX idx_cpr_active
  ON cost_product_request(CPR_status)
  WHERE CPR_status NOT IN ('CLOSED', 'REJECTED', 'DRAFT');

-- cost_product_spec
CREATE INDEX idx_cps_raw_material
  ON cost_product_spec(CPS_raw_material_type);

CREATE INDEX idx_cps_shade
  ON cost_product_spec(CPS_shade_id)
  WHERE CPS_shade_id IS NOT NULL;

-- cost_routing_rule
CREATE INDEX idx_crr_priority_active
  ON cost_routing_rule(CRR_priority ASC)
  WHERE CRR_is_active = true;

-- cost_routing_draft
CREATE INDEX idx_crd_request
  ON cost_routing_draft(CRD_request_id);

CREATE INDEX idx_crd_status
  ON cost_routing_draft(CRD_status)
  WHERE CRD_status IN ('DRAFT', 'LOCKED');

-- cost_routing_draft_component
CREATE INDEX idx_crdc_draft
  ON cost_routing_draft_component(CRDC_draft_id, CRDC_sequence_no);

-- cost_request_comment
CREATE INDEX idx_crc_request
  ON cost_request_comment(CRC_request_id, CRC_created_at ASC);

-- 🚀 PERF: full-text search pada plaintext comment
CREATE INDEX idx_crc_plaintext_search
  ON cost_request_comment
  USING gin(to_tsvector('indonesian', CRC_body_plaintext));

-- cost_comment_edit_history
CREATE INDEX idx_cceh_comment
  ON cost_comment_edit_history(CCEH_comment_id, CCEH_edited_at DESC);

-- cost_request_mention
CREATE INDEX idx_crm_mentioned_user
  ON cost_request_mention(CRM_mentioned_user_id)
  WHERE CRM_is_notified = false;

CREATE INDEX idx_crm_comment
  ON cost_request_mention(CRM_comment_id);

-- cost_attachment
CREATE INDEX idx_ca_request
  ON cost_attachment(CA_request_id)
  WHERE CA_request_id IS NOT NULL;

CREATE INDEX idx_ca_comment
  ON cost_attachment(CA_comment_id)
  WHERE CA_comment_id IS NOT NULL;

-- cost_user_role_mapping
CREATE INDEX idx_curm_user_active
  ON cost_user_role_mapping(CURM_user_id)
  WHERE CURM_is_active = true;

CREATE INDEX idx_curm_functional_role
  ON cost_user_role_mapping(CURM_functional_role, CURM_tier)
  WHERE CURM_is_active = true;

-- cost_notification
CREATE INDEX idx_cn_recipient_unread
  ON cost_notification(CN_recipient_user_id, CN_created_at DESC)
  WHERE CN_is_read = false;

CREATE INDEX idx_cn_request
  ON cost_notification(CN_request_id)
  WHERE CN_request_id IS NOT NULL;

-- cost_audit_log
CREATE INDEX idx_cal_entity
  ON cost_audit_log(CAL_entity_type, CAL_entity_id);

CREATE INDEX idx_cal_user
  ON cost_audit_log(CAL_user_id, CAL_performed_at DESC);

CREATE INDEX idx_cal_performed_at
  ON cost_audit_log(CAL_performed_at DESC);


-- ============================================================================
-- SEED DATA — Request Types (Initial)
-- ============================================================================
INSERT INTO cost_request_type (CRT_code, CRT_display_name, CRT_state_machine_variant, CRT_required_field_config, CRT_default_urgency)
VALUES
  ('QUOTE', 'Quote Inquiry', 'SHORTCUT_CAPABLE',
   '{"required": ["title", "customer_name", "product_classification", "urgency_level"]}'::jsonb,
   'medium'),
  ('DEVELOPMENT', 'Development / Sample', 'FULL',
   '{"required": ["title", "customer_name", "product_classification", "urgency_level", "product_spec"]}'::jsonb,
   'medium');
