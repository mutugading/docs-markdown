-- ============================================================================
--  goapps finance — migration 000392_create_erp_integration.up.sql (usulan v0.3)
--  Master aturan valuasi ERP dikelola di goapps (pemilik: Finance, OQ-C).
--  Seed diambil dari ALTHARADEV 2026-09-25 (read-only):
--    MGTDAT.MGT_ITEM_COST_VAL_LOSS (115 baris), IM_VS_STATIC_VALUE 'ITEMSELLPRIC' (3),
--    OM_GRADE_CODE_1.GRADE_BL_SHORT_NAME (grade group).
--  Sebelum deploy PROD: bandingkan dengan PROD (recon_queries.sql §0). Beda → ganti seed.
--  Tidak ada ETL aturan; perubahan berikutnya lewat UI Finance (M-8), tercatat di audit.
--  File ini adalah draft dokumen, belum disalin ke repo goapps.
-- ============================================================================

BEGIN;

-- ---------------------------------------------------------------------------
-- 1. Grade group (milik goapps; sync cost_erp_grade dari ERP TIDAK boleh menimpa kolom ini)
-- ---------------------------------------------------------------------------
ALTER TABLE cost_erp_grade ADD COLUMN IF NOT EXISTS ceg_grade_group VARCHAR(20);

UPDATE cost_erp_grade g
   SET ceg_grade_group = s.grp
  FROM (VALUES
    ('A9/A', 'NS'),
    ('A9', 'NS'),
    ('AE', 'AE'),
    ('AM', 'NS'),
    ('APQ', 'BC'),
    ('AXa', 'POYA'),
    ('AXb', 'POYA'),
    ('AXc', 'POYA'),
    ('AXd', 'POYA'),
    ('AX', 'AX'),
    ('Aa', 'POYA'),
    ('Ab', 'POYA'),
    ('Ac', 'POYA'),
    ('Ad', 'POYA'),
    ('A', 'NS'),
    ('B1', 'BC'),
    ('B2', 'BC'),
    ('BB', 'BB'),
    ('BMX', 'BC'),
    ('B', 'BC'),
    ('C', 'BC'),
    ('JLT', 'JLT')
  ) AS s(code, grp)
 WHERE g.ceg_grade_code = s.code;
-- grade tanpa group di ERP (CI, FIN, GEN, MB, MC, NA, R, …) dibiarkan NULL → V-08 bila muncul di ADJ non-AX.

-- ---------------------------------------------------------------------------
-- 2. Harga jual acuan per basis (USD/kg)
-- ---------------------------------------------------------------------------
CREATE TABLE cst_erp_sell_price (
  cesp_basis      VARCHAR(15)   PRIMARY KEY,           -- SPPTY | SPITY | SPBSD
  cesp_price      NUMERIC(20,6) NOT NULL CHECK (cesp_price > 0),
  cesp_is_active  BOOLEAN       NOT NULL DEFAULT TRUE,
  created_at      TIMESTAMPTZ   NOT NULL DEFAULT now(),
  created_by      VARCHAR(64)   NOT NULL DEFAULT 'MIGRATION',
  updated_at      TIMESTAMPTZ,
  updated_by      VARCHAR(64)
);

INSERT INTO cst_erp_sell_price (cesp_basis, cesp_price) VALUES
    ('SPBSD', 1.4),
    ('SPITY', 1.5),
    ('SPPTY', 1.3);

-- ---------------------------------------------------------------------------
-- 3. Aturan value loss per (FG type, prod type, grade group)
-- ---------------------------------------------------------------------------
CREATE TABLE cst_erp_valloss_rule (
  cevr_id          SERIAL        PRIMARY KEY,
  cevr_fg_type     VARCHAR(15)   NOT NULL,             -- 'Type 1'..'Type 13'
  cevr_prod_type   VARCHAR(3)    NOT NULL CHECK (cevr_prod_type IN ('POY','PTY','ITY')),
  cevr_grade_group VARCHAR(20)   NOT NULL,
  cevr_basis       VARCHAR(15)   NOT NULL,             -- COST | SPPTY | SPITY | SPBSD
  cevr_val_loss    NUMERIC(20,6) NOT NULL DEFAULT 0,
  cevr_is_active   BOOLEAN       NOT NULL DEFAULT TRUE,
  created_at       TIMESTAMPTZ   NOT NULL DEFAULT now(),
  created_by       VARCHAR(64)   NOT NULL DEFAULT 'MIGRATION',
  updated_at       TIMESTAMPTZ,
  updated_by       VARCHAR(64),
  CONSTRAINT uq_cevr UNIQUE (cevr_fg_type, cevr_prod_type, cevr_grade_group)
  -- basis non-COST harus ada di cst_erp_sell_price: dicek di service (V-08), bukan FK,
  -- karena 'COST' bukan baris harga.
);

INSERT INTO cst_erp_valloss_rule (cevr_fg_type, cevr_prod_type, cevr_grade_group, cevr_basis, cevr_val_loss) VALUES
    ('Type 1', 'POY', 'BC', 'SPPTY', 0.5),
    ('Type 1', 'POY', 'JLT', 'SPPTY', 0.5),
    ('Type 1', 'POY', 'POYA', 'COST', 0.0),
    ('Type 1', 'PTY', 'AE', 'COST', 0.0),
    ('Type 1', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 1', 'PTY', 'BC', 'SPPTY', 0.1),
    ('Type 1', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 1', 'PTY', 'NS', 'COST', 0.05),
    ('Type 10', 'ITY', 'AE', 'COST', 0.05),
    ('Type 10', 'ITY', 'BB', 'SPITY', 0.6),
    ('Type 10', 'ITY', 'BC', 'SPITY', 0.6),
    ('Type 10', 'ITY', 'JLT', 'SPITY', 0.6),
    ('Type 10', 'ITY', 'NS', 'COST', 0.05),
    ('Type 10', 'POY', 'BC', 'COST', 0.0),
    ('Type 10', 'POY', 'JLT', 'COST', 0.0),
    ('Type 10', 'POY', 'POYA', 'COST', 0.0),
    ('Type 10', 'PTY', 'AE', 'COST', 0.05),
    ('Type 10', 'PTY', 'BB', 'SPPTY', 0.6),
    ('Type 10', 'PTY', 'BC', 'SPPTY', 0.6),
    ('Type 10', 'PTY', 'JLT', 'SPPTY', 0.6),
    ('Type 10', 'PTY', 'NS', 'COST', 0.05),
    ('Type 11', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 11', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 11', 'POY', 'POYA', 'COST', 0.0),
    ('Type 11', 'PTY', 'AE', 'COST', 0.0),
    ('Type 11', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 11', 'PTY', 'BC', 'SPPTY', 0.4),
    ('Type 11', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 11', 'PTY', 'NS', 'COST', 0.0),
    ('Type 12', 'POY', 'BB', 'COST', 0.5),
    ('Type 12', 'POY', 'BC', 'COST', 0.5),
    ('Type 12', 'POY', 'JLT', 'COST', 0.5),
    ('Type 12', 'POY', 'POYA', 'COST', 0.0),
    ('Type 12', 'PTY', 'AE', 'COST', 0.0),
    ('Type 12', 'PTY', 'BB', 'SPBSD', 0.4),
    ('Type 12', 'PTY', 'BC', 'SPBSD', 0.1),
    ('Type 12', 'PTY', 'JLT', 'SPBSD', 0.4),
    ('Type 12', 'PTY', 'NS', 'COST', 0.05),
    ('Type 13', 'POY', 'BC', 'SPPTY', 0.0),
    ('Type 13', 'POY', 'JLT', 'SPPTY', 0.0),
    ('Type 13', 'POY', 'POYA', 'COST', 0.0),
    ('Type 13', 'PTY', 'AE', 'COST', 0.0),
    ('Type 13', 'PTY', 'BB', 'SPPTY', 0.51),
    ('Type 13', 'PTY', 'BC', 'SPPTY', 0.51),
    ('Type 13', 'PTY', 'JLT', 'SPPTY', 0.51),
    ('Type 13', 'PTY', 'NS', 'COST', 0.05),
    ('Type 2', 'POY', 'BB', 'SPPTY', 0.5),
    ('Type 2', 'POY', 'BC', 'SPPTY', 0.5),
    ('Type 2', 'POY', 'JLT', 'SPPTY', 0.5),
    ('Type 2', 'POY', 'NS', 'COST', 0.0),
    ('Type 2', 'POY', 'POYA', 'COST', 0.0),
    ('Type 2', 'PTY', 'AE', 'COST', 0.0),
    ('Type 2', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 2', 'PTY', 'BC', 'SPPTY', 0.25),
    ('Type 2', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 2', 'PTY', 'NS', 'COST', 0.05),
    ('Type 3', 'POY', 'BB', 'SPPTY', 0.8),
    ('Type 3', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 3', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 3', 'POY', 'POYA', 'COST', 0.0),
    ('Type 3', 'PTY', 'AE', 'COST', 0.0),
    ('Type 3', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 3', 'PTY', 'BC', 'SPPTY', 0.0),
    ('Type 3', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 3', 'PTY', 'NS', 'COST', 0.05),
    ('Type 4', 'POY', 'BB', 'SPPTY', 0.8),
    ('Type 4', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 4', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 4', 'POY', 'POYA', 'COST', 0.0),
    ('Type 4', 'PTY', 'AE', 'COST', 0.0),
    ('Type 4', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 4', 'PTY', 'BC', 'SPPTY', 0.0),
    ('Type 4', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 4', 'PTY', 'NS', 'COST', 0.0),
    ('Type 5', 'POY', 'BC', 'SPPTY', 0.5),
    ('Type 5', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 5', 'POY', 'POYA', 'COST', 0.0),
    ('Type 5', 'PTY', 'AE', 'COST', 0.0),
    ('Type 5', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 5', 'PTY', 'BC', 'SPPTY', 0.4),
    ('Type 5', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 5', 'PTY', 'NS', 'COST', 0.05),
    ('Type 6', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 6', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 6', 'POY', 'POYA', 'COST', 0.0),
    ('Type 6', 'PTY', 'AE', 'COST', 0.0),
    ('Type 6', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 6', 'PTY', 'BC', 'SPPTY', 0.4),
    ('Type 6', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 6', 'PTY', 'NS', 'COST', 0.0),
    ('Type 7', 'POY', 'BB', 'SPPTY', 0.8),
    ('Type 7', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 7', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 7', 'POY', 'POYA', 'COST', 0.0),
    ('Type 7', 'PTY', 'AE', 'COST', 0.0),
    ('Type 7', 'PTY', 'BB', 'SPPTY', 0.4),
    ('Type 7', 'PTY', 'BC', 'SPPTY', 0.3),
    ('Type 7', 'PTY', 'JLT', 'SPPTY', 0.4),
    ('Type 7', 'PTY', 'NS', 'COST', 0.0),
    ('Type 8', 'POY', 'BC', 'SPPTY', 0.45),
    ('Type 8', 'POY', 'JLT', 'SPPTY', 0.8),
    ('Type 8', 'POY', 'POYA', 'COST', 0.0),
    ('Type 8', 'PTY', 'AE', 'COST', 0.0),
    ('Type 8', 'PTY', 'BB', 'SPPTY', 0.6),
    ('Type 8', 'PTY', 'BC', 'SPPTY', 0.6),
    ('Type 8', 'PTY', 'JLT', 'SPPTY', 0.6),
    ('Type 8', 'PTY', 'NS', 'COST', 0.0),
    ('Type 9', 'POY', 'BC', 'SPPTY', 0.0),
    ('Type 9', 'POY', 'JLT', 'SPPTY', 0.0),
    ('Type 9', 'POY', 'POYA', 'COST', 0.0),
    ('Type 9', 'PTY', 'AE', 'COST', 0.0),
    ('Type 9', 'PTY', 'BB', 'SPPTY', 0.5),
    ('Type 9', 'PTY', 'BC', 'SPPTY', 0.5),
    ('Type 9', 'PTY', 'JLT', 'SPPTY', 0.5),
    ('Type 9', 'PTY', 'NS', 'COST', 0.0);

-- cek seed: 115 aturan (COST 47, SPPTY 62, SPBSD 3, SPITY 3), 3 harga
DO $$
BEGIN
  IF (SELECT COUNT(*) FROM cst_erp_valloss_rule) <> 115 THEN
    RAISE EXCEPTION 'seed cst_erp_valloss_rule harus 115 baris';
  END IF;
  IF EXISTS (SELECT 1 FROM cst_erp_valloss_rule r
              WHERE r.cevr_basis <> 'COST'
                AND NOT EXISTS (SELECT 1 FROM cst_erp_sell_price p WHERE p.cesp_basis = r.cevr_basis)) THEN
    RAISE EXCEPTION 'basis aturan tanpa harga jual';
  END IF;
END $$;

-- ---------------------------------------------------------------------------
-- 4. Tabel integrasi (batch, demand, std, lock) — lihat GOAPPS_MODIFICATIONS.md M-1
-- ---------------------------------------------------------------------------
-- (DDL cst_erp_int_batch, cst_erp_adj_demand, cst_erp_std_cost, cst_period_lock ada di dokumen)

COMMIT;
