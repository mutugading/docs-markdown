-- ============================================================================
--  DIBUAT OTOMATIS 2026-09-09 dari hasil recon Fase 0-4 + clarification_1.txt
--  MODE       : script ini MENULIS ke database. Sesi recon TIDAK menjalankannya.
--  DIJALANKAN : oleh IT Lead, manual, setelah review.
--  WAJIB      : jalankan di dalam transaksi, verifikasi hitungan, baru COMMIT.
-- ============================================================================

-- PAKET 06 - LDR ADJUSTMENT (mbs_ldr_adjustment_pct)
-- Keputusan user: selisih antara mbs_ldr_calculated_pct dan
--   mbs_run_ldr_pct dicatat sebagai adjustment, karena bisa jadi ada
--   adjustment dari user atas nilai calculated.
-- Rumus: adjustment = run_ldr_pct - calculated_pct
--        sehingga calculated + adjustment = run_ldr (LDR aktual).
-- Saat ini mbs_ldr_adjustment_pct NULL untuk seluruh 2.799 baris.

-- 923 spin terdampak. Satu statement set-based (lebih aman dari 923 UPDATE):
UPDATE mst_mb_spin
   SET mbs_ldr_adjustment_pct = mbs_run_ldr_pct - mbs_ldr_calculated_pct,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-adj'
 WHERE mbs_run_ldr_pct        IS NOT NULL
   AND mbs_ldr_calculated_pct IS NOT NULL
   AND abs(mbs_run_ldr_pct - mbs_ldr_calculated_pct) > 0.0001
   AND deleted_at IS NULL;
-- harapan: 923 baris terpengaruh

-- Verifikasi:
-- SELECT count(*) FILTER (WHERE mbs_ldr_adjustment_pct IS NOT NULL) AS terisi,
--        count(*) FILTER (WHERE abs(mbs_ldr_calculated_pct
--               + coalesce(mbs_ldr_adjustment_pct,0) - mbs_run_ldr_pct) > 0.0001)
--          AS tidak_konsisten
--   FROM mst_mb_spin WHERE mbs_run_ldr_pct IS NOT NULL
--     AND mbs_ldr_calculated_pct IS NOT NULL;

