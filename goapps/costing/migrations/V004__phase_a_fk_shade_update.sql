-- ============================================================================
-- Costing Workflow Suite — Phase A Migration v1.2
-- Add FK constraint CPS_shade_id → cost_erp_shade
-- Requires: Phase B DDL v1.3+ already applied (cost_erp_shade exists)
-- ============================================================================

-- Pre-flight check: cost_erp_shade must exist
DO $$
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.tables
    WHERE table_name = 'cost_erp_shade'
  ) THEN
    RAISE EXCEPTION 'cost_erp_shade not found. Run Phase B DDL v1.3 first.';
  END IF;
END $$;

-- Add FK constraint (data validation first, then constraint)
-- If any existing CPS_shade_id doesn't match cost_erp_shade, this will fail
ALTER TABLE cost_product_spec
  ADD CONSTRAINT fk_cps_shade
  FOREIGN KEY (CPS_shade_id) REFERENCES cost_erp_shade(CES_shade_id)
  ON DELETE SET NULL;

-- Update column comment
COMMENT ON COLUMN cost_product_spec.CPS_shade_id
  IS 'FK ke cost_erp_shade(CES_shade_id). NULL allowed jika user pakai CPS_shade_custom_text.';

-- Verification query
-- SELECT conname, pg_get_constraintdef(oid)
-- FROM pg_constraint
-- WHERE conrelid = 'cost_product_spec'::regclass AND conname = 'fk_cps_shade';
