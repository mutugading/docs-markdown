-- 000478_fix_yarn_waste_less_mb_opu_formula.down.sql
-- Revert D5: restore pre-000478 expression + formula_params.

BEGIN;

-- 1. Restore original expression.
UPDATE mst_formula
SET expression = '(1.0 - WASTE_PERC / 100.0) - (MB_SP_DOZING / 100.0) - (OPU / 100.0)',
    updated_at = NOW(),
    updated_by = 'migration_000478_down'
WHERE formula_code = 'F_YARN_WASTE_LESS_MB_OPU'
  AND deleted_at IS NULL;

-- 2. Remove the RM_* formula_params added by up.
DELETE FROM formula_param fp
USING mst_formula f, mst_parameter p
WHERE fp.formula_id = f.id
  AND fp.param_id   = p.id
  AND f.formula_code = 'F_YARN_WASTE_LESS_MB_OPU' AND f.deleted_at IS NULL
  AND p.param_code IN ('RM_NORMS','RM_LANDED_COST','RM_RATE')
  AND p.deleted_at IS NULL;

-- 3. Re-add the original formula_params.
WITH f AS (
    SELECT id FROM mst_formula
    WHERE formula_code = 'F_YARN_WASTE_LESS_MB_OPU' AND deleted_at IS NULL
)
INSERT INTO formula_param (formula_id, param_id, sort_order)
SELECT f.id, p.id, v.ord
FROM f
CROSS JOIN (VALUES ('WASTE_PERC',1),('MB_SP_DOZING',2),('OPU',3)) AS v(code, ord)
JOIN mst_parameter p ON p.param_code = v.code AND p.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1 FROM formula_param fp
    WHERE fp.formula_id = f.id AND fp.param_id = p.id
);

COMMIT;
