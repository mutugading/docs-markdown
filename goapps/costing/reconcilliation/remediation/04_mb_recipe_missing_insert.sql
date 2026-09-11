-- ============================================================================
--  DIBUAT OTOMATIS 2026-09-09 dari hasil recon Fase 0-4 + clarification_1.txt
--  MODE       : script ini MENULIS ke database. Sesi recon TIDAK menjalankannya.
--  DIJALANKAN : oleh IT Lead, manual, setelah review.
--  WAJIB      : jalankan di dalam transaksi, verifikasi hitungan, baru COMMIT.
-- ============================================================================

-- PAKET 04 - INSERT KOMPOSISI RESEP MB YANG HILANG DI SISTEM BARU
-- Head: 30 head bucket 'recipe-missing-in-new' (semuanya check_status
--       'Waiting', mbh_current_version = 0, is_active = false).
-- Target: mst_mb_composition (WORKING SET).
--
-- SETELAH INSERT: MB harus di-Validate lewat aplikasi supaya
--   mst_mb_composition_version terbentuk (mbcv_version naik dari 0).
--   Tanpa Validate, engine tetap tidak melihat resep ini.

-- Kolom mbcm_legacy_sys_id diisi CMBI_SYS_ID legacy supaya jejak
-- provenance tetap ada dan idempotency bisa diperiksa.

-- ---- head 20250904906 | MGT PIETRA GY 7879 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'c1c08b03-8de5-44d2-84ab-7aef6eebbb89'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 1.75, 'GROUP',
        FALSE, '20250932524', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'c1c08b03-8de5-44d2-84ab-7aef6eebbb89'::uuid, 2,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 0.77, 'GROUP',
        FALSE, '20250932525', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'c1c08b03-8de5-44d2-84ab-7aef6eebbb89'::uuid, 3,
        '25e9bf9c-196a-48e9-aba3-a04f7e74e242'::uuid, 0.58, 'GROUP',
        FALSE, '20250932526', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'c1c08b03-8de5-44d2-84ab-7aef6eebbb89'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20250932527', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'c1c08b03-8de5-44d2-84ab-7aef6eebbb89'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 94.9, 'GROUP',
        FALSE, '20250932528', NOW(), 'recon-202608-recipe');

-- ---- head 20250904908 | MGT AKOVA BN 6785 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '0953e67c-f5ea-4df3-8208-55db77a91229'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 6.72, 'GROUP',
        FALSE, '20250932534', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '0953e67c-f5ea-4df3-8208-55db77a91229'::uuid, 2,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 29.4, 'GROUP',
        FALSE, '20250932535', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '0953e67c-f5ea-4df3-8208-55db77a91229'::uuid, 3,
        '87c48292-8a1e-4f7d-ab1c-1db31556ebfc'::uuid, 41.28, 'GROUP',
        FALSE, '20250932536', NOW(), 'recon-202608-recipe');  -- [SPC 3221 group retired -> RED MGTP-3221 per keputusan user]
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '0953e67c-f5ea-4df3-8208-55db77a91229'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20250932537', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '0953e67c-f5ea-4df3-8208-55db77a91229'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 20.6, 'GROUP',
        FALSE, '20250932538', NOW(), 'recon-202608-recipe');

-- ---- head 20250904916 | MGT CALEN GY 7881 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 7.863, 'GROUP',
        FALSE, '20250932582', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 2,
        'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid, 0.463, 'GROUP',
        FALSE, '20250932583', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 3,
        '09d2bd7e-7e3a-4850-a1bf-e2e914d73c57'::uuid, 12.303, 'GROUP',
        FALSE, '20250932584', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 4,
        '3f39b8db-c5ba-443a-8836-b6291df7344e'::uuid, 7.575, 'GROUP',
        FALSE, '20250932585', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20250932586', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'bfb844df-5616-4ed4-9210-bebe55910526'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 69.796, 'GROUP',
        FALSE, '20250932587', NOW(), 'recon-202608-recipe');

-- ---- head 20251205005 | MGT HARUKA BN 6747 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '38314c51-2468-435b-b929-6d1632de8e39'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 9.6, 'GROUP',
        FALSE, '20251233064', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '38314c51-2468-435b-b929-6d1632de8e39'::uuid, 2,
        '25990272-c0b1-4ea5-bb2d-c01b99de5b35'::uuid, 13.04, 'GROUP',
        FALSE, '20251233065', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '38314c51-2468-435b-b929-6d1632de8e39'::uuid, 3,
        '636dcb90-2923-49c5-b8d2-a214b6bdd5fb'::uuid, 2.4, 'GROUP',
        FALSE, '20251233066', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '38314c51-2468-435b-b929-6d1632de8e39'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20251233067', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '38314c51-2468-435b-b929-6d1632de8e39'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 72.96, 'GROUP',
        FALSE, '20251233068', NOW(), 'recon-202608-recipe');

-- ---- head 20251205006 | MGT STREAK GY 7843 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '17fd3d67-d512-4555-b3de-7c88d2344116'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 3.105, 'GROUP',
        FALSE, '20251233069', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '17fd3d67-d512-4555-b3de-7c88d2344116'::uuid, 2,
        'd541c910-53ed-4d1c-adf2-0d58f2afb8e0'::uuid, 2.191, 'GROUP',
        FALSE, '20251233070', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '17fd3d67-d512-4555-b3de-7c88d2344116'::uuid, 3,
        '88cc233a-e6d5-4717-be14-6880cd7aee19'::uuid, 0.989, 'GROUP',
        FALSE, '20251233071', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '17fd3d67-d512-4555-b3de-7c88d2344116'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20251233072', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '17fd3d67-d512-4555-b3de-7c88d2344116'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 91.715, 'GROUP',
        FALSE, '20251233073', NOW(), 'recon-202608-recipe');

-- ---- head 20251205007 | MGT PARI GY 7844 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '75a2e8d2-cd0e-438b-98ef-0657fc8d0530'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 0.468, 'GROUP',
        FALSE, '20251233079', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '75a2e8d2-cd0e-438b-98ef-0657fc8d0530'::uuid, 2,
        '88cc233a-e6d5-4717-be14-6880cd7aee19'::uuid, 0.063, 'GROUP',
        FALSE, '20251233080', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '75a2e8d2-cd0e-438b-98ef-0657fc8d0530'::uuid, 3,
        'a1cd3817-af4d-44cf-a8e5-ffe114e428b9'::uuid, 0.054, 'GROUP',
        FALSE, '20251233081', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '75a2e8d2-cd0e-438b-98ef-0657fc8d0530'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20251233082', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '75a2e8d2-cd0e-438b-98ef-0657fc8d0530'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 97.415, 'GROUP',
        FALSE, '20251233083', NOW(), 'recon-202608-recipe');

-- ---- head 20251205009 | MGT SYRUP MN 3432 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '699a2d94-dc27-4729-8c5a-3a9e21db9316'::uuid, 1,
        'd541c910-53ed-4d1c-adf2-0d58f2afb8e0'::uuid, 1.2033, 'GROUP',
        FALSE, '20251233089', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '699a2d94-dc27-4729-8c5a-3a9e21db9316'::uuid, 2,
        'a1cd3817-af4d-44cf-a8e5-ffe114e428b9'::uuid, 14.883, 'GROUP',
        FALSE, '20251233090', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '699a2d94-dc27-4729-8c5a-3a9e21db9316'::uuid, 3,
        'ca2b07d4-1eee-45e7-8208-bfead7d79515'::uuid, 12.35, 'GROUP',
        FALSE, '20251233091', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '699a2d94-dc27-4729-8c5a-3a9e21db9316'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20251233092', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '699a2d94-dc27-4729-8c5a-3a9e21db9316'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 69.5637, 'GROUP',
        FALSE, '20251233093', NOW(), 'recon-202608-recipe');

-- ---- head 20260105068 | MGT RANTI CM 2231 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'a836d62d-1d81-4d46-a651-2c4f18cf314a'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 0.035, 'GROUP',
        FALSE, '20260133403', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'a836d62d-1d81-4d46-a651-2c4f18cf314a'::uuid, 2,
        '87bb9209-fa72-4189-bdf3-19fc445bcea3'::uuid, 0.414, 'GROUP',
        FALSE, '20260133404', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'a836d62d-1d81-4d46-a651-2c4f18cf314a'::uuid, 3,
        'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid, 0.564, 'GROUP',
        FALSE, '20260133405', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'a836d62d-1d81-4d46-a651-2c4f18cf314a'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260133406', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'a836d62d-1d81-4d46-a651-2c4f18cf314a'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 96.987, 'GROUP',
        FALSE, '20260133407', NOW(), 'recon-202608-recipe');

-- ---- head 20260105082 | MGT Orinoco GY 7445 | 7 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 2.77, 'GROUP',
        FALSE, '20260133475', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 2,
        '636dcb90-2923-49c5-b8d2-a214b6bdd5fb'::uuid, 0.138, 'GROUP',
        FALSE, '20260133476', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 3,
        'af7df1ef-2f66-4d0d-9cf5-67bf8003846c'::uuid, 3.34, 'GROUP',
        FALSE, '20260133477', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 4,
        '88cc233a-e6d5-4717-be14-6880cd7aee19'::uuid, 4.33, 'GROUP',
        FALSE, '20260133478', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 5,
        'a8495860-c151-4de6-951e-6365639c7ca5'::uuid, 0.433, 'GROUP',
        FALSE, '20260133479', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 6,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260133480', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e64716a1-b300-4c5f-9db8-05567d633ce2'::uuid, 7,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 86.989, 'GROUP',
        FALSE, '20260133481', NOW(), 'recon-202608-recipe');

-- ---- head 20260105086 | MGT LICORICE BK 7852 | 4 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '63bde93d-a76f-4b70-9dff-c000a3bc2327'::uuid, 1,
        '09d2bd7e-7e3a-4850-a1bf-e2e914d73c57'::uuid, 8.89, 'GROUP',
        FALSE, '20260133498', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '63bde93d-a76f-4b70-9dff-c000a3bc2327'::uuid, 2,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 18.885, 'GROUP',
        FALSE, '20260133499', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '63bde93d-a76f-4b70-9dff-c000a3bc2327'::uuid, 3,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260133500', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '63bde93d-a76f-4b70-9dff-c000a3bc2327'::uuid, 4,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 70.225, 'GROUP',
        FALSE, '20260133501', NOW(), 'recon-202608-recipe');

-- ---- head 20260205124 | MGT CHARTER BN 6823 | 7 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 1,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 4.62, 'GROUP',
        FALSE, '20260233720', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_mb_ref_mbh_id, mbcm_is_carrier, mbcm_legacy_sys_id,
       mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 2,
        NULL, 10.61, 'MB',
        '9fdf7166-ff81-4708-b0b8-13b7c745678a'::uuid, FALSE, '20260233721', NOW(), 'recon-202608-recipe');
--   nested MB -> 20220803365 (MGT SPC RD 3221/30-D-02464 FOR HERON)
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 3,
        'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid, 15.167, 'GROUP',
        FALSE, '20260233722', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 4,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 10.834, 'GROUP',
        FALSE, '20260233723', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 5,
        '8f0756df-9148-4c63-b8c7-3eab620dd40d'::uuid, 28.89, 'GROUP',
        FALSE, '20260233724', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 6,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233725', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'db23dff5-06c7-42f8-82de-6891cd954058'::uuid, 7,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 27.879, 'GROUP',
        FALSE, '20260233726', NOW(), 'recon-202608-recipe');

-- ---- head 20260205125 | MGT CARNE BN 6806 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '8b639857-2589-49b3-8238-9b54f70a20af'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 1, 'GROUP',
        FALSE, '20260233727', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '8b639857-2589-49b3-8238-9b54f70a20af'::uuid, 2,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 1.85, 'GROUP',
        FALSE, '20260233728', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '8b639857-2589-49b3-8238-9b54f70a20af'::uuid, 3,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 3.53, 'GROUP',
        FALSE, '20260233729', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '8b639857-2589-49b3-8238-9b54f70a20af'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233730', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '8b639857-2589-49b3-8238-9b54f70a20af'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 91.62, 'GROUP',
        FALSE, '20260233731', NOW(), 'recon-202608-recipe');

-- ---- head 20260205126 | MGT GIBSON BN 6824 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 1,
        '8f0756df-9148-4c63-b8c7-3eab620dd40d'::uuid, 45.5, 'GROUP',
        FALSE, '20260233732', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_mb_ref_mbh_id, mbcm_is_carrier, mbcm_legacy_sys_id,
       mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 2,
        NULL, 26.41, 'MB',
        '9fdf7166-ff81-4708-b0b8-13b7c745678a'::uuid, FALSE, '20260233733', NOW(), 'recon-202608-recipe');
--   nested MB -> 20220803365 (MGT SPC RD 3221/30-D-02464 FOR HERON)
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 3,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 11.67, 'GROUP',
        FALSE, '20260233734', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 4,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 3.621, 'GROUP',
        FALSE, '20260233735', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233736', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ae19927b-7ff3-4ea4-8a21-93b073fdc4ec'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 10.799, 'GROUP',
        FALSE, '20260233737', NOW(), 'recon-202608-recipe');

-- ---- head 20260205127 | MGT SENAPE BN 6808 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5f4a5da7-d7f2-4dd1-9ee1-a9210183b236'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 4.085, 'GROUP',
        FALSE, '20260233738', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5f4a5da7-d7f2-4dd1-9ee1-a9210183b236'::uuid, 2,
        '474078e6-5b5a-4806-b274-65caba9179ae'::uuid, 8.5, 'GROUP',
        FALSE, '20260233739', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5f4a5da7-d7f2-4dd1-9ee1-a9210183b236'::uuid, 3,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 14.13, 'GROUP',
        FALSE, '20260233740', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5f4a5da7-d7f2-4dd1-9ee1-a9210183b236'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233741', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5f4a5da7-d7f2-4dd1-9ee1-a9210183b236'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 71.285, 'GROUP',
        FALSE, '20260233742', NOW(), 'recon-202608-recipe');

-- ---- head 20260205128 | MGT GLACE BN 6811 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'f7199e54-e1a7-4e66-aa07-29334c803f09'::uuid, 1,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 7.875, 'GROUP',
        FALSE, '20260233743', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'f7199e54-e1a7-4e66-aa07-29334c803f09'::uuid, 2,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 3.675, 'GROUP',
        FALSE, '20260233744', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'f7199e54-e1a7-4e66-aa07-29334c803f09'::uuid, 3,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 8.625, 'GROUP',
        FALSE, '20260233745', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'f7199e54-e1a7-4e66-aa07-29334c803f09'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233746', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'f7199e54-e1a7-4e66-aa07-29334c803f09'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 77.825, 'GROUP',
        FALSE, '20260233747', NOW(), 'recon-202608-recipe');

-- ---- head 20260205129 | MGT MARCIO GN 4343 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 1,
        '23042d16-9c6b-4790-a6c7-5f3aae96d9dc'::uuid, 6.21, 'GROUP',
        FALSE, '20260233748', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 2,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 6.785, 'GROUP',
        FALSE, '20260233749', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 3,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 17.88, 'GROUP',
        FALSE, '20260233750', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 4,
        'af7df1ef-2f66-4d0d-9cf5-67bf8003846c'::uuid, 4.021, 'GROUP',
        FALSE, '20260233751', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233752', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '5ef2ee45-6b20-412c-8f58-1132663b4d8a'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 63.104, 'GROUP',
        FALSE, '20260233753', NOW(), 'recon-202608-recipe');

-- ---- head 20260205130 | MFT PASSI BN 6807 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '575a8162-31f8-403e-9ed7-f4248be41041'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 3.727, 'GROUP',
        FALSE, '20260233754', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '575a8162-31f8-403e-9ed7-f4248be41041'::uuid, 2,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 12.403, 'GROUP',
        FALSE, '20260233755', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '575a8162-31f8-403e-9ed7-f4248be41041'::uuid, 3,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 3.543, 'GROUP',
        FALSE, '20260233756', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '575a8162-31f8-403e-9ed7-f4248be41041'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233757', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '575a8162-31f8-403e-9ed7-f4248be41041'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 78.327, 'GROUP',
        FALSE, '20260233758', NOW(), 'recon-202608-recipe');

-- ---- head 20260205131 | MGT QUINA BG 6810 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 0.3, 'GROUP',
        FALSE, '20260233759', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 2,
        '23042d16-9c6b-4790-a6c7-5f3aae96d9dc'::uuid, 0.451, 'GROUP',
        FALSE, '20260233760', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 3,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 4.18, 'GROUP',
        FALSE, '20260233761', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 4,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 0.65, 'GROUP',
        FALSE, '20260233762', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233763', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e2b5e65b-164b-4e36-9598-85503a15cf02'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 92.419, 'GROUP',
        FALSE, '20260233764', NOW(), 'recon-202608-recipe');

-- ---- head 20260205132 | MGT CAVE GY 7901 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '1403727d-2bc3-4814-bf19-50e68042004a'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 1.085, 'GROUP',
        FALSE, '20260233765', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '1403727d-2bc3-4814-bf19-50e68042004a'::uuid, 2,
        '09d2bd7e-7e3a-4850-a1bf-e2e914d73c57'::uuid, 5.3, 'GROUP',
        FALSE, '20260233766', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '1403727d-2bc3-4814-bf19-50e68042004a'::uuid, 3,
        'a1cd3817-af4d-44cf-a8e5-ffe114e428b9'::uuid, 0.5, 'GROUP',
        FALSE, '20260233767', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '1403727d-2bc3-4814-bf19-50e68042004a'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233768', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '1403727d-2bc3-4814-bf19-50e68042004a'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 91.115, 'GROUP',
        FALSE, '20260233769', NOW(), 'recon-202608-recipe');

-- ---- head 20260205133 | MGT HAWKER BL 5846 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 1,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 5.5, 'GROUP',
        FALSE, '20260233770', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_mb_ref_mbh_id, mbcm_is_carrier, mbcm_legacy_sys_id,
       mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 2,
        NULL, 19.25, 'MB',
        '9fdf7166-ff81-4708-b0b8-13b7c745678a'::uuid, FALSE, '20260233771', NOW(), 'recon-202608-recipe');
--   nested MB -> 20220803365 (MGT SPC RD 3221/30-D-02464 FOR HERON)
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 3,
        '2c6eb01e-b2d4-4c57-999f-c6a7159fedf0'::uuid, 54.167, 'GROUP',
        FALSE, '20260233772', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 4,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 3.467, 'GROUP',
        FALSE, '20260233773', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233774', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd402a7f2-018e-440a-b4cf-952b881052d1'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 15.616, 'GROUP',
        FALSE, '20260233775', NOW(), 'recon-202608-recipe');

-- ---- head 20260205135 | MGT GALLET GY 7932 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd087f501-ede8-4988-ae24-13950737544e'::uuid, 1,
        '88cc233a-e6d5-4717-be14-6880cd7aee19'::uuid, 0.89, 'GROUP',
        FALSE, '20260233782', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd087f501-ede8-4988-ae24-13950737544e'::uuid, 2,
        '09d2bd7e-7e3a-4850-a1bf-e2e914d73c57'::uuid, 3.1, 'GROUP',
        FALSE, '20260233783', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd087f501-ede8-4988-ae24-13950737544e'::uuid, 3,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 6.885, 'GROUP',
        FALSE, '20260233784', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd087f501-ede8-4988-ae24-13950737544e'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233785', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'd087f501-ede8-4988-ae24-13950737544e'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 87.125, 'GROUP',
        FALSE, '20260233786', NOW(), 'recon-202608-recipe');

-- ---- head 20260205137 | MGT MAORI WT 1110 | 4 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e5ff3e76-1496-43d8-aa8b-b0bafc276bbf'::uuid, 1,
        '5521a0c6-d947-4641-84c7-d0affd7fec47'::uuid, 21, 'GROUP',
        FALSE, '20260233792', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e5ff3e76-1496-43d8-aa8b-b0bafc276bbf'::uuid, 2,
        '6d3cbb26-2e6d-4183-a313-d19c5be56315'::uuid, 2.1, 'GROUP',
        FALSE, '20260233793', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e5ff3e76-1496-43d8-aa8b-b0bafc276bbf'::uuid, 3,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233794', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e5ff3e76-1496-43d8-aa8b-b0bafc276bbf'::uuid, 4,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 74.9, 'GROUP',
        FALSE, '20260233795', NOW(), 'recon-202608-recipe');

-- ---- head 20260205138 | MGT ALBURY GY 7931 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 1,
        '25990272-c0b1-4ea5-bb2d-c01b99de5b35'::uuid, 1, 'GROUP',
        FALSE, '20260233796', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 2,
        '629c0c14-a9ed-4067-8ff1-c70f6a677642'::uuid, 2.405, 'GROUP',
        FALSE, '20260233797', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 3,
        '8bd24b0b-7c71-42a8-aa44-e876c6d6bf14'::uuid, 10.8, 'GROUP',
        FALSE, '20260233798', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 4,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 6.23, 'GROUP',
        FALSE, '20260233799', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233800', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '24a95da9-b751-4129-b2f7-6dae16ad4c51'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 77.565, 'GROUP',
        FALSE, '20260233801', NOW(), 'recon-202608-recipe');

-- ---- head 20260205140 | MGT GREGORY BN 6814 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'dad0d099-c24c-4a7a-ae0b-d454747a8101'::uuid, 1,
        'ca2b07d4-1eee-45e7-8208-bfead7d79515'::uuid, 7.5, 'GROUP',
        FALSE, '20260233808', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'dad0d099-c24c-4a7a-ae0b-d454747a8101'::uuid, 2,
        '8f0756df-9148-4c63-b8c7-3eab620dd40d'::uuid, 62.5, 'GROUP',
        FALSE, '20260233809', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'dad0d099-c24c-4a7a-ae0b-d454747a8101'::uuid, 3,
        '474078e6-5b5a-4806-b274-65caba9179ae'::uuid, 3.5, 'GROUP',
        FALSE, '20260233810', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'dad0d099-c24c-4a7a-ae0b-d454747a8101'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260233811', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'dad0d099-c24c-4a7a-ae0b-d454747a8101'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 24.5, 'GROUP',
        FALSE, '20260233812', NOW(), 'recon-202608-recipe');

-- ---- head 20260305180 | MGT LORIENT BL 5629 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'edd2addd-6e80-4840-bcb9-5102777bbb3a'::uuid, 1,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 0.1, 'GROUP',
        FALSE, '20260335074', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'edd2addd-6e80-4840-bcb9-5102777bbb3a'::uuid, 2,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 1.2, 'GROUP',
        FALSE, '20260335075', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'edd2addd-6e80-4840-bcb9-5102777bbb3a'::uuid, 3,
        '09d2bd7e-7e3a-4850-a1bf-e2e914d73c57'::uuid, 36, 'GROUP',
        FALSE, '20260335076', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'edd2addd-6e80-4840-bcb9-5102777bbb3a'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260335077', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'edd2addd-6e80-4840-bcb9-5102777bbb3a'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 60.7, 'GROUP',
        FALSE, '20260335078', NOW(), 'recon-202608-recipe');

-- ---- head 20260605321 | MGT DORTMUND YW 2495 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '352bee07-fb51-4e72-9bbd-e348c52e976e'::uuid, 1,
        '545623f1-a10d-4ecd-9109-b784824823e2'::uuid, 6, 'GROUP',
        FALSE, '20260638532', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '352bee07-fb51-4e72-9bbd-e348c52e976e'::uuid, 2,
        '25990272-c0b1-4ea5-bb2d-c01b99de5b35'::uuid, 27.456, 'GROUP',
        FALSE, '20260638533', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '352bee07-fb51-4e72-9bbd-e348c52e976e'::uuid, 3,
        '1fb50b90-63b9-4074-9d20-cb71ae913568'::uuid, 0.2496, 'GROUP',
        FALSE, '20260638534', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '352bee07-fb51-4e72-9bbd-e348c52e976e'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260638535', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '352bee07-fb51-4e72-9bbd-e348c52e976e'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 64.2944, 'GROUP',
        FALSE, '20260638536', NOW(), 'recon-202608-recipe');

-- ---- head 20260605322 | MGT SAKURA PK 3505 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 1,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 2.7, 'GROUP',
        FALSE, '20260638537', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 2,
        'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid, 0.18, 'GROUP',
        FALSE, '20260638538', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 3,
        '47926ea1-fb22-4321-8a25-7467b887aef4'::uuid, 0.1003, 'GROUP',
        FALSE, '20260638539', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 4,
        '5521a0c6-d947-4641-84c7-d0affd7fec47'::uuid, 3.75, 'GROUP',
        FALSE, '20260638540', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260638541', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '9f6ffd7b-5051-4950-b9a8-381920a025d8'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 91.2697, 'GROUP',
        FALSE, '20260638542', NOW(), 'recon-202608-recipe');

-- ---- head 20260605323 | MGT SALFORD RD 3506 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_mb_ref_mbh_id, mbcm_is_carrier, mbcm_legacy_sys_id,
       mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '6e2831c7-ea2e-4c08-9cc2-fdc1e42b9c7d'::uuid, 1,
        NULL, 51.718, 'MB',
        '9fdf7166-ff81-4708-b0b8-13b7c745678a'::uuid, FALSE, '20260638543', NOW(), 'recon-202608-recipe');
--   nested MB -> 20220803365 (MGT SPC RD 3221/30-D-02464 FOR HERON)
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '6e2831c7-ea2e-4c08-9cc2-fdc1e42b9c7d'::uuid, 2,
        'af7df1ef-2f66-4d0d-9cf5-67bf8003846c'::uuid, 17.0683, 'GROUP',
        FALSE, '20260638544', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '6e2831c7-ea2e-4c08-9cc2-fdc1e42b9c7d'::uuid, 3,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 10.78, 'GROUP',
        FALSE, '20260638545', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '6e2831c7-ea2e-4c08-9cc2-fdc1e42b9c7d'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260638546', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), '6e2831c7-ea2e-4c08-9cc2-fdc1e42b9c7d'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 18.4337, 'GROUP',
        FALSE, '20260638547', NOW(), 'recon-202608-recipe');

-- ---- head 20260605324 | MGT PEGASI PL 5914 | 6 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 1,
        '8cabaa6e-87df-4bc6-a0d9-f7b5cc9596a5'::uuid, 0.77, 'GROUP',
        FALSE, '20260638548', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 2,
        '636dcb90-2923-49c5-b8d2-a214b6bdd5fb'::uuid, 1.02, 'GROUP',
        FALSE, '20260638549', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 3,
        'dfb29128-4e7e-4cfd-9722-fda6846362c6'::uuid, 1.75, 'GROUP',
        FALSE, '20260638550', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 4,
        '5521a0c6-d947-4641-84c7-d0affd7fec47'::uuid, 3.75, 'GROUP',
        FALSE, '20260638551', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 5,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260638552', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'e71b3f4e-c682-42a7-b288-2b6532fc328b'::uuid, 6,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 90.71, 'GROUP',
        FALSE, '20260638553', NOW(), 'recon-202608-recipe');

-- ---- head 20260605325 | MGT RIGEL GN 4371 | 5 line legacy
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ffbe0a5a-5366-415b-ae79-d25558d3a71e'::uuid, 1,
        'bd61f606-4ef7-4364-a20c-7d43b3093814'::uuid, 2.108, 'GROUP',
        FALSE, '20260638554', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ffbe0a5a-5366-415b-ae79-d25558d3a71e'::uuid, 2,
        '0b313df7-a431-4ed1-97cf-c4743a05b8f7'::uuid, 1.146, 'GROUP',
        FALSE, '20260638555', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ffbe0a5a-5366-415b-ae79-d25558d3a71e'::uuid, 3,
        'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid, 0.772, 'GROUP',
        FALSE, '20260638556', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ffbe0a5a-5366-415b-ae79-d25558d3a71e'::uuid, 4,
        'f78a5ee7-3c7f-4796-bb53-3c73b307f2b1'::uuid, 2, 'GROUP',
        FALSE, '20260638557', NOW(), 'recon-202608-recipe');
INSERT INTO mst_mb_composition (mbcm_id, mbcm_mbh_id, mbcm_seq_no,
       mbcm_group_head_id, mbcm_composition_pct, mbcm_source_type,
       mbcm_is_carrier, mbcm_legacy_sys_id, mbcm_created_at, mbcm_created_by)
VALUES (gen_random_uuid(), 'ffbe0a5a-5366-415b-ae79-d25558d3a71e'::uuid, 5,
        '05f480d0-71e8-4128-b9bb-0dbf6aa4b4e0'::uuid, 93.974, 'GROUP',
        FALSE, '20260638558', NOW(), 'recon-202608-recipe');

