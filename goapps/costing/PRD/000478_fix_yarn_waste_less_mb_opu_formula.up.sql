-- 000478_fix_yarn_waste_less_mb_opu_formula.up.sql
-- Fix D5 (Defect Register POY Yarn 202607): F_YARN_WASTE_LESS_MB_OPU pakai konsep
-- formula yang salah.
--
-- BEFORE (live, seed 000408):
--   type       = CALCULATION
--   expression = '(1.0 - WASTE_PERC / 100.0) - (MB_SP_DOZING / 100.0) - (OPU / 100.0)'
--   result 1558 = 0.996   (yield factor — konsep keliru)
--   formula_params = WASTE_PERC(1), MB_SP_DOZING(2), OPU(3)
--
-- AFTER (legacy PkgFormulaYarn):
--   expression = 'RM_NORMS * RM_LANDED_COST - RM_RATE'
--   verify 1558: 1.008 × 1.0381 − 1.0193 = 0.0271
--   formula_params = RM_NORMS, RM_LANDED_COST, RM_RATE
--
-- CLASSIFICATION: ENGINE-via-DATA (CALCULATION → engine evaluate expression). Sama
-- seperti D4 (000477): edit expression + formula_param, tanpa perubahan Go.
--
-- ============================================================================
-- ⛔ JANGAN APPLY DULU — DUA PRASYARAT BELUM TERPENUHI
-- ============================================================================
-- Expression baru merujuk RM_LANDED_COST dan RM_RATE. Verifikasi ke 0.0271 HANYA
-- benar jika:
--   (1) RM_RATE = base stage rate (1.0193)  → butuh D6 (ENG-YARN-02, engine).
--       Saat ini RM_RATE = RM_LANDED = landed (1.0381), keduanya kolaps.
--   (2) RM_LANDED_COST = landed dengan komponen freight (1.0381).
--       Preflight RM V2 P3: freight_rate di cst_rm_group_detail NULL untuk SEMUA
--       523 detail aktif. Jika V2 recompute jalan dengan freight=0, RM_LANDED jatuh
--       ke base → RM_LANDED ≈ RM_RATE → D5 gagal verify. Sumber 0.01875 harus
--       diselesaikan dulu (lihat catatan preflight).
--
-- Jika D5 di-apply SEBELUM D6: RM_LANDED_COST = RM_RATE = 1.0381 (kolaps), maka
-- WASTE_LESS_MB_OPU = 1.008×1.0381 − 1.0381 = 0.0083 (SALAH, bukan 0.0271).
-- Salah, tapi bukan crash — akan auto-benar begitu D6 memisahkan base vs landed.
-- Tetap: APPLY BERSAMAAN / SETELAH D6 supaya tak ada state intermediate keliru,
-- dan recompute 1558 langsung bisa verify 0.0271.
--
-- Rekomendasi urutan: RM V2 (isu freight) → D6 (ENG-YARN-02) → D5 (file ini) →
-- recompute 1558 → verify WASTE_LESS_MB_OPU = 0.0271.
-- ============================================================================

-- ============================================================
-- PREFLIGHT (jalankan di luar transaksi):
-- STEP 1 (duplicate guard): harus EXACTLY 1 baris live.
--   SELECT count(*) FROM mst_formula
--   WHERE formula_code='F_YARN_WASTE_LESS_MB_OPU' AND deleted_at IS NULL;
--   -- EXPECT: 1
-- STEP 2 (snapshot before):
--   SELECT expression FROM mst_formula
--   WHERE formula_code='F_YARN_WASTE_LESS_MB_OPU' AND deleted_at IS NULL;
--   -- EXPECT: '(1.0 - WASTE_PERC / 100.0) - (MB_SP_DOZING / 100.0) - (OPU / 100.0)'
-- STEP 3 (referenced params resolvable):
--   SELECT param_code FROM mst_parameter
--   WHERE param_code IN ('RM_NORMS','RM_LANDED_COST','RM_RATE') AND deleted_at IS NULL;
--   -- EXPECT: 3 baris.
-- ============================================================

BEGIN;

-- 1. Rewrite expression ke bentuk legacy.
UPDATE mst_formula
SET expression = 'RM_NORMS * RM_LANDED_COST - RM_RATE',
    updated_at = NOW(),
    updated_by = 'migration_000478'
WHERE formula_code = 'F_YARN_WASTE_LESS_MB_OPU'
  AND deleted_at IS NULL;
-- >>> CHECK: 1 row. Jika 0/>1 → ROLLBACK.

-- 2. Buang formula_param lama yang tak lagi dirujuk (WASTE_PERC, MB_SP_DOZING, OPU).
DELETE FROM formula_param fp
USING mst_formula f, mst_parameter p
WHERE fp.formula_id = f.id
  AND fp.param_id   = p.id
  AND f.formula_code = 'F_YARN_WASTE_LESS_MB_OPU' AND f.deleted_at IS NULL
  AND p.param_code IN ('WASTE_PERC','MB_SP_DOZING','OPU')
  AND p.deleted_at IS NULL;
-- >>> CHECK: 3 rows deleted.

-- 3. Tambah formula_param baru (RM_NORMS, RM_LANDED_COST, RM_RATE) untuk topo-sort.
--    NOT EXISTS guard = idempotent (pola 000465/000477).
WITH f AS (
    SELECT id FROM mst_formula
    WHERE formula_code = 'F_YARN_WASTE_LESS_MB_OPU' AND deleted_at IS NULL
)
INSERT INTO formula_param (formula_id, param_id, sort_order)
SELECT f.id, p.id, v.ord
FROM f
CROSS JOIN (VALUES ('RM_NORMS',1),('RM_LANDED_COST',2),('RM_RATE',3)) AS v(code, ord)
JOIN mst_parameter p ON p.param_code = v.code AND p.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1 FROM formula_param fp
    WHERE fp.formula_id = f.id AND fp.param_id = p.id
);
-- >>> CHECK: 3 rows inserted (first run).

-- ============================================================
-- POST-CHECK (dalam txn, verify lalu COMMIT):
--   SELECT expression FROM mst_formula
--   WHERE formula_code='F_YARN_WASTE_LESS_MB_OPU' AND deleted_at IS NULL;
--   -- EXPECT: 'RM_NORMS * RM_LANDED_COST - RM_RATE'
--
--   SELECT p.param_code, fp.sort_order
--   FROM formula_param fp
--   JOIN mst_formula f  ON f.id=fp.formula_id AND f.formula_code='F_YARN_WASTE_LESS_MB_OPU'
--   JOIN mst_parameter p ON p.id=fp.param_id
--   ORDER BY fp.sort_order;
--   -- EXPECT: RM_NORMS(1), RM_LANDED_COST(2), RM_RATE(3) — dan TIDAK ada WASTE_PERC/MB_SP_DOZING/OPU
-- ============================================================

COMMIT;

-- POST-MIGRATION VERIFY (setelah D6 + freight beres): recompute 1558 →
--   WASTE_LESS_MB_OPU (TOP 58) = 0.0271, EXCEPT vs v_stg_cycc_long = 0.
