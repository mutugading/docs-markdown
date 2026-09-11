-- ============================================================================
--  PAKET 07 - SET LDR SISI BARU DARI NILAI LEGACY
--  Dibuat 2026-09-10 atas instruksi IT Lead:
--    'update system baru dengan LDR dari legacy karena user pake legacy
--     sebagai patokan'
--
--  URUTAN PENTING: paket ini WAJIB jalan SEBELUM paket 06.
--    07 menyetel mbs_run_ldr_pct := LDR legacy
--    06 menghitung adjustment := run_ldr - calculated_pct
--  sehingga calculated + adjustment = run_ldr = legacy. Kalau 06 jalan
--  lebih dulu, adjustment-nya jadi kedaluwarsa.
--
--  Cakupan: HANYA spin yang LDR-nya BERBEDA dan pasangannya matched.
--  Nilai legacy non-numerik (2 baris: '2.00%%' dan '3.00POY 445/96/RND/DSD')
--  TIDAK termasuk - keduanya berkelas 'nonnumeric-source', bukan 'differs'.
-- ============================================================================

BEGIN;

-- ---- LDR AKTUAL (104 spin) -> mbs_run_ldr_pct

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.53,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104024';
--   STELLA BLUE              LDR_AKTUAL: 1.7500 -> 1.53 (delta 0.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.01,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104068';
--   VENUS RD BR              LDR_AKTUAL: 2.0800 -> 2.01 (delta 0.0700)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.23,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104172';
--   EMGE BLUE                LDR_AKTUAL: 3.8700 -> 3.23 (delta 0.6400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.19,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104226';
--   RADIO BL                 LDR_AKTUAL: 2.3700 -> 2.19 (delta 0.1800)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.6,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104292';
--   GLADSOME BR              LDR_AKTUAL: 1.7700 -> 1.6 (delta 0.1700)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.75,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104293';
--   CAFE LEON                LDR_AKTUAL: 3.7000 -> 3.75 (delta -0.0500)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.35,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104296';
--   DARK WINE                LDR_AKTUAL: 2.3400 -> 2.35 (delta -0.0100)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.20,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104300';
--   BEIGE                    LDR_AKTUAL: 1.2500 -> 1.20 (delta 0.0500)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.92,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104307';
--   EARTH                    LDR_AKTUAL: 1.8600 -> 1.92 (delta -0.0600)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104318';
--   COBALT BLUE BR           LDR_AKTUAL: 3.6400 -> 3 (delta 0.6400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.55,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104320';
--   SPINACH GREEN BR         LDR_AKTUAL: 3.0000 -> 2.55 (delta 0.4500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.34,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104351';
--   LARK BEIGE               LDR_AKTUAL: 1.5200 -> 1.34 (delta 0.1800)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.96,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104368';
--   MULTI BLUE BR            LDR_AKTUAL: 1.8900 -> 1.96 (delta -0.0700)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.41,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104393';
--   LOMO BROWN BR            LDR_AKTUAL: 3.4500 -> 3.41 (delta 0.0400)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.76,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104399';
--   DISTAN GY                LDR_AKTUAL: 1.6700 -> 1.76 (delta -0.0900)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.45,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104436';
--   MILIM BL                 LDR_AKTUAL: 3.6700 -> 2.45 (delta 1.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.07,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202104513';
--   SURF BLUE                LDR_AKTUAL: 2.1600 -> 2.07 (delta 0.0900)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202109581';
--   DARK VERMELHO            LDR_AKTUAL: 1.7100 -> 1.5 (delta 0.2100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.28,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110592';
--   FUEGO BL                 LDR_AKTUAL: 2.0100 -> 2.28 (delta -0.2700)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.27,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110596';
--   CLASSIC BL BR            LDR_AKTUAL: 3.7200 -> 4.27 (delta -0.5500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.51,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110598';
--   SEXY PL                  LDR_AKTUAL: 1.4800 -> 1.51 (delta -0.0300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.19,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110604';
--   ARSENE RD                LDR_AKTUAL: 2.2900 -> 2.19 (delta 0.1000)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.4,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110610';
--   WALDEN CM BR             LDR_AKTUAL: 1.5000 -> 1.4 (delta 0.1000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.49,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202110618';
--   DREAM BL                 LDR_AKTUAL: 1.7300 -> 1.49 (delta 0.2400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202111648';
--   HEAVEN BL BR             LDR_AKTUAL: 3.2000 -> 2.5 (delta 0.7000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.83,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202111652';
--   NEBO BL                  LDR_AKTUAL: 3.1000 -> 2.83 (delta 0.2700)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202111658';
--   KIRANA BL BR             LDR_AKTUAL: 1.9000 -> 1.5 (delta 0.4000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.58,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202111661';
--   BUTTER CREAM             LDR_AKTUAL: 1.6300 -> 1.58 (delta 0.0500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202111662';
--   ALPAKA CM BR             LDR_AKTUAL: 2.0000 -> 1.5 (delta 0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.86,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202112676';
--   RETRO BL                 LDR_AKTUAL: 1.9500 -> 1.86 (delta 0.0900)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202201695';
--   PROJECT BN BR            LDR_AKTUAL: 2.2500 -> 1.5 (delta 0.7500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.71,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202201706';
--   TURMERIC YW              LDR_AKTUAL: 2.7600 -> 2.71 (delta 0.0500)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.24,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202201728';
--   SASHA GY                 LDR_AKTUAL: 1.2000 -> 1.24 (delta -0.0400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.77,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202202735';
--   SINAR GREEN              LDR_AKTUAL: 3.5900 -> 3.77 (delta -0.1800)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.49,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202206849';
--   DINO BN                  LDR_AKTUAL: 1.4700 -> 1.49 (delta -0.0200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.50,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202211044';
--   CLIENT CM BR             LDR_AKTUAL: 1.5400 -> 1.50 (delta 0.0400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.91,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202211045';
--   SINAR GN BR              LDR_AKTUAL: 3.6800 -> 4.91 (delta -1.2300)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.07,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202212076';
--   HYPE YW                  LDR_AKTUAL: 2.1300 -> 2.07 (delta 0.0600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202212094';
--   PAPRIKA MN BR            LDR_AKTUAL: 4.0000 -> 3 (delta 1.0000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.05,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202305241';
--   SPINACH GREEN BR         LDR_AKTUAL: 2.5300 -> 2.05 (delta 0.4800)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.22,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202305257';
--   RUBY MAROON              LDR_AKTUAL: 2.3500 -> 2.22 (delta 0.1300)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.48,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202305264';
--   LOCK GY                  LDR_AKTUAL: 1.5900 -> 1.48 (delta 0.1100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.39,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202309487';
--   TAHITI BL                LDR_AKTUAL: 1.4700 -> 1.39 (delta 0.0800)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202309490';
--   ST LUCIA BL              LDR_AKTUAL: 1.5800 -> 1.5 (delta 0.0800)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.79,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202310530';
--   MULTI BLUE BR            LDR_AKTUAL: 1.7300 -> 1.79 (delta -0.0600)  [legacy Current / produksi]

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202311559';
--   ORCHID MN BR             LDR_AKTUAL: 3.7000 -> 2.5 (delta 1.2000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.25,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202312572';
--   ROSARIO PK               LDR_AKTUAL: 2.2400 -> 2.25 (delta -0.0100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 5.57,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202402627';
--   CRIMSON RD               LDR_AKTUAL: 5.8000 -> 5.57 (delta 0.2300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.14,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202409932';
--   JANESHA CM               LDR_AKTUAL: 1.1000 -> 1.14 (delta -0.0400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.85,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202409937';
--   RYUMI BN                 LDR_AKTUAL: 2.0000 -> 1.85 (delta 0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 0.89,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202409938';
--   BOWIE CM                 LDR_AKTUAL: 1.0000 -> 0.89 (delta 0.1100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.23,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202410980';
--   EROSA PK                 LDR_AKTUAL: 2.3400 -> 2.23 (delta 0.1100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.43,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202411006';
--   GARNI GN                 LDR_AKTUAL: 2.6500 -> 2.43 (delta 0.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.52,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202411035';
--   NOERA PK                 LDR_AKTUAL: 2.0000 -> 2.52 (delta -0.5200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.73,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202412061';
--   STARRY BL                LDR_AKTUAL: 2.6800 -> 2.73 (delta -0.0500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.29,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202501151';
--   NOCTIS BL                LDR_AKTUAL: 2.1500 -> 1.29 (delta 0.8600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202501153';
--   PEPON RD                 LDR_AKTUAL: 4.0000 -> 3 (delta 1.0000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.15,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505252';
--   VICTORY GN               LDR_AKTUAL: 2.8200 -> 3.15 (delta -0.3300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.77,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505254';
--   STORMY BN                LDR_AKTUAL: 6.3300 -> 4.77 (delta 1.5600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.36,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505255';
--   KATNISS GN               LDR_AKTUAL: 4.2000 -> 4.36 (delta -0.1600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.45,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505256';
--   SOLACE WT                LDR_AKTUAL: 1.5000 -> 1.45 (delta 0.0500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505262';
--   YODA BK                  LDR_AKTUAL: 6.2200 -> 5 (delta 1.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505263';
--   CARPA BL-02              LDR_AKTUAL: 5.5000 -> 4 (delta 1.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.4,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505267';
--   POCO GN-01               LDR_AKTUAL: 4.4000 -> 3.4 (delta 1.0000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.53,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505268';
--   SMORES WT                LDR_AKTUAL: 2.1000 -> 1.53 (delta 0.5700)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.15,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505272';
--   MORTIS OG                LDR_AKTUAL: 4.0000 -> 4.15 (delta -0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.6,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505276';
--   KAZE MN                  LDR_AKTUAL: 4.0000 -> 3.6 (delta 0.4000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 5.41,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505279';
--   MUYLDER GN               LDR_AKTUAL: 5.2800 -> 5.41 (delta -0.1300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.74,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505280';
--   SENKA GN-02              LDR_AKTUAL: 5.0000 -> 3.74 (delta 1.2600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.16,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202505284';
--   LUDWIG GN-02             LDR_AKTUAL: 4.2500 -> 4.16 (delta 0.0900)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.33,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202506299';
--   LUFFY GN                 LDR_AKTUAL: 3.2000 -> 3.33 (delta -0.1300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 5.10,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202506302';
--   MALICE GN-01             LDR_AKTUAL: 5.0000 -> 5.10 (delta -0.1000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.78,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202506303';
--   ARATA RD                 LDR_AKTUAL: 2.7500 -> 2.78 (delta -0.0300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.92,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202506321';
--   VINEGAR OG               LDR_AKTUAL: 1.9800 -> 1.92 (delta 0.0600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202506360';
--   FIG MN                   LDR_AKTUAL: 6.0000 -> 5 (delta 1.0000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.39,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202507373';
--   HUSK GY                  LDR_AKTUAL: 2.1000 -> 1.39 (delta 0.7100)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.1,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202508435';
--   HIMERU BN                LDR_AKTUAL: 6.1600 -> 4.1 (delta 2.0600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.07,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202509485';
--   BEAUTY BL                LDR_AKTUAL: 2.3700 -> 2.07 (delta 0.3000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.81,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202509536';
--   CLASSIC BLUE             LDR_AKTUAL: 3.5700 -> 2.81 (delta 0.7600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202509546';
--   NOKTA GN BR              LDR_AKTUAL: 2.4500 -> 1.5 (delta 0.9500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202510555';
--   PURIN WT                 LDR_AKTUAL: 2.1500 -> 1.5 (delta 0.6500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.15,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202511589';
--   HALLSTAT GY              LDR_AKTUAL: 2.0000 -> 2.15 (delta -0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.15,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202511590';
--   HALLSTAT GY-02           LDR_AKTUAL: 2.0000 -> 2.15 (delta -0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.78,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202511595';
--   UBUD BL                  LDR_AKTUAL: 3.0000 -> 2.78 (delta 0.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202511599';
--   ANDRES BK-01             LDR_AKTUAL: 5.0000 -> 4.5 (delta 0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.95,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202511601';
--   BERING BL BR             LDR_AKTUAL: 3.1000 -> 2.95 (delta 0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.60,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605873';
--   GAMBILIA MN              LDR_AKTUAL: 3.4000 -> 3.60 (delta -0.2000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.86,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605875';
--   HALIN GN                 LDR_AKTUAL: 2.0000 -> 1.86 (delta 0.1400)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 4.65,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605877';
--   ALVOR PL                 LDR_AKTUAL: 5.0000 -> 4.65 (delta 0.3500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.8,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605886';
--   STELLA BL BR             LDR_AKTUAL: 2.1600 -> 1.8 (delta 0.3600)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.85,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605903';
--   PETRON BK AT             LDR_AKTUAL: 4.3500 -> 2.85 (delta 1.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202605906';
--   VALLETA BG               LDR_AKTUAL: 1.8200 -> 1.5 (delta 0.3200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606911';
--   AWESOME BG BR            LDR_AKTUAL: 2.0000 -> 2.5 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.50,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606915';
--   SEED CM BR               LDR_AKTUAL: 2.0000 -> 2.50 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 3.25,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606918';
--   ORMIN GN                 LDR_AKTUAL: 3.5000 -> 3.25 (delta 0.2500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.6,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606920';
--   ESME BL                  LDR_AKTUAL: 3.0000 -> 2.6 (delta 0.4000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.87,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606921';
--   OSHA GY                  LDR_AKTUAL: 2.0000 -> 1.87 (delta 0.1300)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.15,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606929';
--   BURSA BG                 LDR_AKTUAL: 2.0000 -> 2.15 (delta -0.1500)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606930';
--   SALO GN                  LDR_AKTUAL: 2.0000 -> 2.5 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 1.78,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606931';
--   ARKLOW BN                LDR_AKTUAL: 2.0000 -> 1.78 (delta 0.2200)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606933';
--   COLLAR BL                LDR_AKTUAL: 2.0000 -> 2.5 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606934';
--   ANTALYA BL               LDR_AKTUAL: 2.0000 -> 2.5 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606935';
--   GALWAY BN                LDR_AKTUAL: 2.0000 -> 2.5 (delta -0.5000)

UPDATE mst_mb_spin SET mbs_run_ldr_pct = 2.05,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202606936';
--   MAINE BN                 LDR_AKTUAL: 2.0000 -> 2.05 (delta -0.0500)

-- ---- LDR RENCANA (2 spin) -> mbs_ldr_prsn

UPDATE mst_mb_spin SET mbs_ldr_prsn = 3,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202212094';
--   PAPRIKA MN BR            LDR_RENCANA: 3.1500 -> 3 (delta 0.1500)

UPDATE mst_mb_spin SET mbs_ldr_prsn = 1.5,
       updated_at = NOW(), updated_by = 'recon-202608-ldr-legacy'
 WHERE mbs_oracle_sys_id = '202507373';
--   HUSK GY                  LDR_RENCANA: 2.0000 -> 1.5 (delta 0.5000)

-- Verifikasi sebelum COMMIT: harapan 106 baris ter-update.
-- SELECT count(*) FROM mst_mb_spin
--  WHERE updated_by = 'recon-202608-ldr-legacy';

COMMIT;
