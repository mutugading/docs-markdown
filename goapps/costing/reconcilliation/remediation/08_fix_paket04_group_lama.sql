-- ============================================================================
--  PAKET 08 - KOREKSI 4 BARIS PAKET 04 YANG MENUNJUK GROUP PRA-REMAP
--  Dibuat 2026-09-10.
--
--  AKAR MASALAH (kesalahan urutan eksekusi, bukan kesalahan data sumber):
--    Paket 01 me-remap group code pukul 10:56. Paket 04 dijalankan pukul 11:00
--    dan meng-INSERT baris komposisi dengan group_head_id hasil resolusi
--    tanggal 2026-09-09 -- yaitu SEBELUM remap. Akibatnya 4 baris baru
--    menunjuk group yang justru sudah di-retire oleh paket 01.
--
--  Remap yang berlaku (sumber: 01_rm_group_code_remap.sql):
--    202006002 (PIG0000005) -> 202007204 (YELLOW MGTP-2159)
--    202006051 (DYE0000001) -> 202007176 (OPTICAL BRIGHTNER-1 / FWA-393)
--
--  Target uuid TIDAK di-hardcode: di-resolve lewat group_code supaya tidak
--  bisa salah tempel.
--
--  SESUDAH INI: 4 head terkait WAJIB di-Validate ulang lewat UI. Tiga di
--  antaranya (20260105082, 20260205129, 20260205137) sudah ter-Validate pukul
--  15:03 dengan pointer yang salah, jadi versi aktifnya harus diganti versi
--  baru. Satu (20260605323) belum punya versi sama sekali.
-- ============================================================================

UPDATE mst_mb_composition c
   SET mbcm_group_head_id = (SELECT group_head_id FROM cst_rm_group_head
                              WHERE group_code = '202007204'),
       mbcm_updated_at = NOW(), mbcm_updated_by = 'recon-202608-fix'
 WHERE c.deleted_at IS NULL
   AND c.mbcm_created_by = 'recon-202608-recipe'
   AND c.mbcm_group_head_id = (SELECT group_head_id FROM cst_rm_group_head
                                WHERE group_code = '202006002');
-- harapan: 3 baris

UPDATE mst_mb_composition c
   SET mbcm_group_head_id = (SELECT group_head_id FROM cst_rm_group_head
                              WHERE group_code = '202007176'),
       mbcm_updated_at = NOW(), mbcm_updated_by = 'recon-202608-fix'
 WHERE c.deleted_at IS NULL
   AND c.mbcm_created_by = 'recon-202608-recipe'
   AND c.mbcm_group_head_id = (SELECT group_head_id FROM cst_rm_group_head
                                WHERE group_code = '202006051');
-- harapan: 1 baris
