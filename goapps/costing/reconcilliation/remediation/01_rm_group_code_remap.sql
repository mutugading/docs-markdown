-- ============================================================================
--  DIBUAT OTOMATIS 2026-09-09 dari hasil recon Fase 0-4 + clarification_1.txt
--  MODE       : script ini MENULIS ke database. Sesi recon TIDAK menjalankannya.
--  DIJALANKAN : oleh IT Lead, manual, setelah review.
--  WAJIB      : jalankan di dalam transaksi, verifikasi hitungan, baru COMMIT.
-- ============================================================================

-- PAKET 01 - REMAP GROUP CODE (KLARIFIKASI numerik)
-- Sumber: kolom KLARIFIKASI di cmp_01_rm_cost_matrix_202608_clarification.xlsx
-- Arti  : group_code lama salah dipetakan saat migrasi; harus menunjuk
--         group_code hasil klarifikasi yang sudah ada di sistem baru.
--
-- CATATAN PENTING soal mst_mb_composition_version:
--   Tabel version adalah SNAPSHOT IMMUTABLE yang dikonsumsi engine.
--   Script ini HANYA mengubah working set (mst_mb_composition) dan route.
--   Supaya perubahan sampai ke engine, MB terkait harus di-Validate ulang
--   lewat aplikasi (yang membuat versi baru). Jangan UPDATE tabel version
--   secara langsung.

-- 202006002 (PIG0000005) -> 202007204 (YELLOW MGTP-2159) | route_rm=259, mb_recipe_lines=258
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007204',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006002';
-- harapan: 259 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = 'a75f29fe-5f86-431a-8cb3-84cf4afdfd8b'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = 'af7df1ef-2f66-4d0d-9cf5-67bf8003846c'::uuid
   AND deleted_at IS NULL;
-- harapan: 258 baris terpengaruh

-- 202006010 (PIG0000016) -> 202007167 (GREEN MGTP-4014) | route_rm=1, mb_recipe_lines=1
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007167',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006010';
-- harapan: 1 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = '625ce6fa-448c-486e-b3a9-ff0d439164ca'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '72dcf681-db84-4bfb-a69c-8da8efef7cf0'::uuid
   AND deleted_at IS NULL;
-- harapan: 1 baris terpengaruh

-- 202006011 (CHM0000098) -> 202007619 (LICOWAX-E) | route_rm=98, mb_recipe_lines=98
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007619',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006011';
-- harapan: 98 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = '5c80b66a-9d12-4d1f-9ae8-695c45cbfbd3'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = 'e38fb390-a535-49be-b164-b29475509769'::uuid
   AND deleted_at IS NULL;
-- harapan: 98 baris terpengaruh

-- 202006014 (DYE0000014) -> 202007161 (BLUE MGTS-5054) | route_rm=13, mb_recipe_lines=13
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007161',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006014';
-- harapan: 13 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = 'f33f902c-34ed-4f13-8322-642cab48cbdb'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '3e1a52a3-d016-4576-8cdd-8db9b081ba1a'::uuid
   AND deleted_at IS NULL;
-- harapan: 13 baris terpengaruh

-- SKIP 202006023 -> 202007600 : tanpa referensi - tidak perlu aksi

-- 202006051 (DYE0000001) -> 202007176 (OPTICAL BRIGHTNER-1 (FWA-393)) | route_rm=22, mb_recipe_lines=21
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007176',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006051';
-- harapan: 22 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = '94d667e0-d81c-41af-8edc-b8e1147ae56a'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '6d3cbb26-2e6d-4183-a313-d19c5be56315'::uuid
   AND deleted_at IS NULL;
-- harapan: 21 baris terpengaruh

-- SKIP 202006054 -> 202007594 : tanpa referensi - tidak perlu aksi

-- 202006084 (DYE0000006) -> 202007179 (ORANGE MGTS-3072) | route_rm=20, mb_recipe_lines=20
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007179',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202006084';
-- harapan: 20 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = '5c9c707c-3911-407d-9c4c-ab5731db75ed'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '7f543b6f-e865-4a34-8eea-1256052ec10e'::uuid
   AND deleted_at IS NULL;
-- harapan: 20 baris terpengaruh

-- 202007173 (HOMBITON LCS) -> 202007618 (HOMBITAN LCS) | route_rm=54, mb_recipe_lines=54
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007618',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202007173';
-- harapan: 54 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = 'ef71b0bb-8023-4457-a402-23acfb556612'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '94c58a48-c8d2-4056-bf2b-1ffe0687d904'::uuid
   AND deleted_at IS NULL;
-- harapan: 54 baris terpengaruh

-- 202007182 (PV FAST RED BNP) -> 202007191 (RED MGTP-3221 CLA) | route_rm=13, mb_recipe_lines=13
UPDATE cost_route_rm
   SET crm_rm_group_code = '202007191',
       crm_updated_at    = NOW(),
       crm_updated_by    = 'recon-202608-remap'
 WHERE crm_rm_type       = 'GROUP'
   AND crm_rm_group_code = '202007182';
-- harapan: 13 baris terpengaruh

UPDATE mst_mb_composition
   SET mbcm_group_head_id = '2bd22794-903d-48c6-9230-8118fcc3293f'::uuid,
       mbcm_updated_at    = NOW(),
       mbcm_updated_by    = 'recon-202608-remap'
 WHERE mbcm_group_head_id = '68e3d31f-750b-4573-b76c-03bf2523f45b'::uuid
   AND deleted_at IS NULL;
-- harapan: 13 baris terpengaruh

-- SKIP 202007184 -> 202007184 : NO COST (dikonfirmasi IT Lead) - ditangani PAKET 02

