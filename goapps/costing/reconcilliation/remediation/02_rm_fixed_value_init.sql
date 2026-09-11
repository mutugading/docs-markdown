-- ============================================================================
--  DIBUAT OTOMATIS 2026-09-09 dari hasil recon Fase 0-4 + clarification_1.txt
--  MODE       : script ini MENULIS ke database. Sesi recon TIDAK menjalankannya.
--  DIJALANKAN : oleh IT Lead, manual, setelah review.
--  WAJIB      : jalankan di dalam transaksi, verifikasi hitungan, baru COMMIT.
-- ============================================================================

-- PAKET 02 - FIXED VALUE (flag = INIT + init_val_*)
-- Untuk group ber-KLARIFIKASI 'NO COST' / 'ITEM NOT FOUND' / 'ERROR'.
-- Latar: seluruh 350 group saat ini flag_valuation/marketing/simulation
--        = 'CONS' dan init_val_* NULL. Karena tidak ada data konsumsi
--        202608, tier tidak resolve -> flag_*_used = 'NONE' -> rate 0.
--        Mekanisme 'INIT' sudah tersedia di CHECK constraint (migration
--        000503) tapi belum pernah dipakai.
-- Nilai : diambil dari legacy CST_GRP_CONSUMP_HEAD periode 202608
--         ACTUAL  <- CGCH_LANDED_COST
--         SELLING <- CGCH_MARKET_RATE1
--
-- CATATAN: engine membaca cst_rm_group_head_period untuk periodenya.
--          Script ini meng-update ANCHOR dan SNAPSHOT 202608 sekaligus.
--          Lebih aman lewat UI aplikasi (write-through terjaga).

-- 202006015 PIG0000038 | ITEM NOT FOUND | route_rm=0 mb_recipe=0
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 40.3393797542422469280280866003510825044,
       flag_marketing = 'INIT',
       init_val_marketing = 40.3393797542422469280280866003510825044,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202006015';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 40.3393797542422469280280866003510825044,
       flag_marketing = 'INIT',
       init_val_marketing = 40.3393797542422469280280866003510825044,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202006015');
-- nilai: ACTUAL=40.3393797542422469280280866003510825044, SELLING=40.3393797542422469280280866003510825044

-- 202006074 MBB0000041 | ITEM NOT FOUND | route_rm=0 mb_recipe=0
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 1.97599039615846338535414165666266506603,
       flag_marketing = 'INIT',
       init_val_marketing = 1.97599039615846338535414165666266506603,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202006074';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 1.97599039615846338535414165666266506603,
       flag_marketing = 'INIT',
       init_val_marketing = 1.97599039615846338535414165666266506603,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202006074');
-- nilai: ACTUAL=1.97599039615846338535414165666266506603, SELLING=1.97599039615846338535414165666266506603

-- 202007184 POLY. IRON STONE MG-TR-120 LOT.A-3524 B, S.CODE : S6033F | NO COST (dikonfirmasi IT Lead 2026-09-10) | route_rm=3 mb_recipe=3
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 16.12,
       flag_marketing = 'INIT',
       init_val_marketing = 16.12,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202007184';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 16.12,
       flag_marketing = 'INIT',
       init_val_marketing = 16.12,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202007184');
-- nilai: ACTUAL=16.12, SELLING=16.12

-- 202007199 TIO2 LWS-100 | ITEM NOT FOUND | route_rm=0 mb_recipe=0
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 3.17243822004295849515089713826994424074,
       flag_marketing = 'INIT',
       init_val_marketing = 2.96490272109612580123675910329312547339,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202007199';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 3.17243822004295849515089713826994424074,
       flag_marketing = 'INIT',
       init_val_marketing = 2.96490272109612580123675910329312547339,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202007199');
-- nilai: ACTUAL=3.17243822004295849515089713826994424074, SELLING=2.96490272109612580123675910329312547339

-- 202007672 POLY. LILAC PURPLE TR-242-MGT, A-5220-A, S CODE : S5994F | ITEM NOT FOUND | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 8.694,
       flag_marketing = 'INIT',
       init_val_marketing = 8.694,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202007672';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 8.694,
       flag_marketing = 'INIT',
       init_val_marketing = 8.694,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202007672');
-- nilai: ACTUAL=8.694, SELLING=8.694

-- 202007683 MBC0000083 | NO COST | route_rm=2 mb_recipe=2
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 6.7,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202007683';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 6.7,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202007683');
-- nilai: SELLING=6.7

-- 202011603 SPD0000005 | NO COST | route_rm=0 mb_recipe=0
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 4.27175182748538011695906432748538011696,
       flag_marketing = 'INIT',
       init_val_marketing = 4.14733187134502923976608187134502923977,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202011603';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 4.27175182748538011695906432748538011696,
       flag_marketing = 'INIT',
       init_val_marketing = 4.14733187134502923976608187134502923977,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202011603');
-- nilai: ACTUAL=4.27175182748538011695906432748538011696, SELLING=4.14733187134502923976608187134502923977

-- 202209635 IRGASTAB 8201P | ITEM NOT FOUND | route_rm=3 mb_recipe=3
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 190,
       flag_marketing = 'INIT',
       init_val_marketing = 190,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202209635';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 190,
       flag_marketing = 'INIT',
       init_val_marketing = 190,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202209635');
-- nilai: ACTUAL=190, SELLING=190

-- 202211651 RED MGTP-3149C | NO COST | route_rm=2 mb_recipe=2
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 195,
       flag_marketing = 'INIT',
       init_val_marketing = 195,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202211651';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 195,
       flag_marketing = 'INIT',
       init_val_marketing = 195,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202211651');
-- nilai: ACTUAL=195, SELLING=195

-- 202302665 MGT LIGHT COLOUR | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 10,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202302665';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 10,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202302665');
-- nilai: SELLING=10

-- 202302666 MGT MEDIUM COLOUR | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 11,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202302666';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 11,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202302666');
-- nilai: SELLING=11

-- 202302667 MGT DARK COLOUR | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 12,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202302667';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 12,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202302667');
-- nilai: SELLING=12

-- 202302668 MGT EXTRA DARK COLOUR | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 16,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202302668';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 16,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202302668');
-- nilai: SELLING=16

-- 202303675 BLANC FIXE HXM | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 6.156,
       flag_marketing = 'INIT',
       init_val_marketing = 6.156,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202303675';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 6.156,
       flag_marketing = 'INIT',
       init_val_marketing = 6.156,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202303675');
-- nilai: ACTUAL=6.156, SELLING=6.156

-- 202403761 PE GIALLO 2028 | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 48.33,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202403761';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 48.33,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202403761');
-- nilai: SELLING=48.33

-- 202408790 HOSTASTAT FE 20 LIQUID | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 14.543,
       flag_marketing = 'INIT',
       init_val_marketing = 13.5,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202408790';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 14.543,
       flag_marketing = 'INIT',
       init_val_marketing = 13.5,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202408790');
-- nilai: ACTUAL=14.543, SELLING=13.5

-- 202409794 UV MB (TURKEY) | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 45,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202409794';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 45,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202409794');
-- nilai: SELLING=45

-- 202411807 ORACET YELLOW 140 NQ | NO COST | route_rm=2 mb_recipe=2
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 109.98,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411807';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 109.98,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411807');
-- nilai: SELLING=109.98

-- 202411808 PALIOGEN RED K3911 | ERROR, HAS TO CHECK BY DEV | route_rm=4 mb_recipe=4
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 209,
       flag_marketing = 'INIT',
       init_val_marketing = 209,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411808';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 209,
       flag_marketing = 'INIT',
       init_val_marketing = 209,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411808');
-- nilai: ACTUAL=209, SELLING=209

-- 202411810 HELIOGEN BLUE K6912FP (BASF) | NO COST | route_rm=3 mb_recipe=3
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 42,
       flag_marketing = 'INIT',
       init_val_marketing = 40.584,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411810';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 42,
       flag_marketing = 'INIT',
       init_val_marketing = 40.584,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411810');
-- nilai: ACTUAL=42, SELLING=40.584

-- 202411811 PALIOTOL YELLOW K 18100 UL (BASF) | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 47,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411811';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 47,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411811');
-- nilai: SELLING=47

-- 202411812 HELIOGEN GREEN K 8730 FP ( BASF) | NO COST | route_rm=2 mb_recipe=2
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 35.7,
       flag_marketing = 'INIT',
       init_val_marketing = 39.78,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411812';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 35.7,
       flag_marketing = 'INIT',
       init_val_marketing = 39.78,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411812');
-- nilai: ACTUAL=35.7, SELLING=39.78

-- 202411815 MASTERSET PES 1272 | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 21.7,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202411815';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 21.7,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202411815');
-- nilai: SELLING=21.7

-- 202412821 CINGUASIA PINK K4430 FP (BASF) | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 96,
       flag_marketing = 'INIT',
       init_val_marketing = 96,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202412821';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 96,
       flag_marketing = 'INIT',
       init_val_marketing = 96,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202412821');
-- nilai: ACTUAL=96, SELLING=96

-- 202504845 ORACET YELLOW 144 FE | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 102,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202504845';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 102,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202504845');
-- nilai: SELLING=102

-- 202506861 UV-PES-1879-GREEN 66BT | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_valuation = 'INIT',
       init_val_valuation = 43,
       flag_marketing = 'INIT',
       init_val_marketing = 17.48,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202506861';
UPDATE cst_rm_group_head_period
   SET flag_valuation = 'INIT',
       init_val_valuation = 43,
       flag_marketing = 'INIT',
       init_val_marketing = 17.48,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202506861');
-- nilai: ACTUAL=43, SELLING=17.48

-- 202506866 TINUVIN 1600 BASF | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 220,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202506866';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 220,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202506866');
-- nilai: SELLING=220

-- 202509875 SICOTRANS ROT K 2915 | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 39,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202509875';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 39,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202509875');
-- nilai: SELLING=39

-- 202511901 MASTERSET PES 1336 UV-SETAS | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 42.18,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202511901';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 42.18,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202511901');
-- nilai: SELLING=42.18

-- 202606935 CROMAPTHAL VIOLET 5800 | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 97.5,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202606935';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 97.5,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202606935');
-- nilai: SELLING=97.5

-- 202606936 PES 1561 BLACK+ ANTIMICROBIAL | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 25,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202606936';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 25,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202606936');
-- nilai: SELLING=25

-- 202606937 PES 1273  WHITE ANTIMICROBIAL | NO COST | route_rm=1 mb_recipe=1
UPDATE cst_rm_group_head
   SET flag_marketing = 'INIT',
       init_val_marketing = 38,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE group_code = '202606937';
UPDATE cst_rm_group_head_period
   SET flag_marketing = 'INIT',
       init_val_marketing = 38,
       updated_at = NOW(), updated_by = 'recon-202608-init'
 WHERE period = '202608'
   AND group_head_id = (SELECT group_head_id FROM cst_rm_group_head WHERE group_code = '202606937');
-- nilai: SELLING=38

