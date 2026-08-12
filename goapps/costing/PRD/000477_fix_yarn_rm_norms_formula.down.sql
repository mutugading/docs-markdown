-- 000477_fix_yarn_rm_norms_formula.down.sql
-- Revert D4: restore the pre-000477 multiplicative form and remove the
-- RP_DOZING formula_param that 000477 added.

BEGIN;

-- 1. Restore original expression (live value from seed 000408).
UPDATE mst_formula
SET expression = '1.0 / (1.0 - WASTE_PERC / 100.0)',
    updated_at = NOW(),
    updated_by = 'migration_000477_down'
WHERE formula_code = 'F_YARN_RM_NORMS'
  AND deleted_at IS NULL;

-- 2. Remove the RP_DOZING formula_param link added by the up migration.
--    Only removes the specific (RM_NORMS, RP_DOZING) pair; leaves WASTE_PERC intact.
DELETE FROM formula_param fp
USING mst_formula f, mst_parameter p
WHERE fp.formula_id = f.id
  AND fp.param_id   = p.id
  AND f.formula_code = 'F_YARN_RM_NORMS' AND f.deleted_at IS NULL
  AND p.param_code   = 'RP_DOZING'       AND p.deleted_at IS NULL;

COMMIT;
