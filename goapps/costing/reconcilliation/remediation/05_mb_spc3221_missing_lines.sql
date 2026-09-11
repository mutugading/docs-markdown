-- ============================================================================
--  DIBUAT OTOMATIS 2026-09-09 dari hasil recon Fase 0-4 + clarification_1.txt
--  MODE       : script ini MENULIS ke database. Sesi recon TIDAK menjalankannya.
--  DIJALANKAN : oleh IT Lead, manual, setelah review.
--  WAJIB      : jalankan di dalam transaksi, verifikasi hitungan, baru COMMIT.
-- ============================================================================

-- PAKET 05 - INSERT LINE 'SPC 3221' YANG HILANG PADA 4 RESEP
-- Akar masalah: legacy group 202208630 (SPC 3221) TIDAK ADA di sistem
--   baru, sehingga baris komposisinya gagal termigrasi dan sum resep
--   jadi 75 / 75 / 65 / 40,420 (bukan 100).
-- Keputusan user: SPC 3221 sudah di-retire; yang mereferensikannya
--   diarahkan ke RED MGTP-3221 (group_code 202007188).
--
-- Head kelima (20250904908 MGT AKOVA BN 6785) TIDAK ada di sini -
--   seluruh resepnya hilang, jadi ditangani di PAKET 04.

-- head 20241004279 | MGT NEWRY BN 6628 N-D-03671-B | sum sekarang 75.000 -> +25 = 100.000
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
SELECT gen_random_uuid(), 'fb9b52a8-489a-4850-bb5e-679eea899ea4'::uuid,
       COALESCE(MAX(mbcm_seq_no), 0) + 1,
       '87c48292-8a1e-4f7d-ab1c-1db31556ebfc'::uuid, 25, 'GROUP',
       FALSE, '20241026252', NOW(), 'recon-202608-spc3221'
  FROM mst_mb_composition WHERE mbcm_mbh_id = 'fb9b52a8-489a-4850-bb5e-679eea899ea4'::uuid;

-- head 20241204383 | MGT NEWRY BN 6628-D-03671-B | sum sekarang 75.000 -> +25 = 100.000
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
SELECT gen_random_uuid(), 'aeb216da-e366-4959-a4d7-47c295a60eb8'::uuid,
       COALESCE(MAX(mbcm_seq_no), 0) + 1,
       '87c48292-8a1e-4f7d-ab1c-1db31556ebfc'::uuid, 25, 'GROUP',
       FALSE, '20241226686', NOW(), 'recon-202608-spc3221'
  FROM mst_mb_composition WHERE mbcm_mbh_id = 'aeb216da-e366-4959-a4d7-47c295a60eb8'::uuid;

-- head 20250804838 | MGT IXORA RD 3431--B | sum sekarang 65.000 -> +35 = 100.000
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
SELECT gen_random_uuid(), 'e87a1c89-7545-4c1e-9602-5a83b3d4c9dc'::uuid,
       COALESCE(MAX(mbcm_seq_no), 0) + 1,
       '87c48292-8a1e-4f7d-ab1c-1db31556ebfc'::uuid, 35, 'GROUP',
       FALSE, '20250832177', NOW(), 'recon-202608-spc3221'
  FROM mst_mb_composition WHERE mbcm_mbh_id = 'e87a1c89-7545-4c1e-9602-5a83b3d4c9dc'::uuid;

-- head 20250804886 | MGT ZENBU RD 3426-D-04141-B | sum sekarang 40.420 -> +59.58 = 100.000
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
SELECT gen_random_uuid(), '31390674-8be0-46c4-a224-6e08f420b37a'::uuid,
       COALESCE(MAX(mbcm_seq_no), 0) + 1,
       '87c48292-8a1e-4f7d-ab1c-1db31556ebfc'::uuid, 59.58, 'GROUP',
       FALSE, '20250832425', NOW(), 'recon-202608-spc3221'
  FROM mst_mb_composition WHERE mbcm_mbh_id = '31390674-8be0-46c4-a224-6e08f420b37a'::uuid;

-- Verifikasi setelah insert (harus 100.000 semua):
-- SELECT h.mbh_oracle_sys_id, round(sum(c.mbcm_composition_pct),3)
--   FROM mst_mb_composition c JOIN mst_mb_head h ON h.mbh_id=c.mbcm_mbh_id
--  WHERE h.mbh_oracle_sys_id IN ('20241004279','20241204383',
--                                '20250804838','20250804886')
--    AND c.deleted_at IS NULL GROUP BY 1;

