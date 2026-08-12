-- ============================================================================
-- PREFLIGHT — RM V2 wiring + recompute (period 202607)
-- ============================================================================
-- Tujuan: pastikan prasyarat V2 recompute terpenuhi SEBELUM Ilham wire handler
-- V2 di main.go dan trigger recompute. V2 membaca:
--   1. cst_item_cons_stk_po  (source qty/val per item+grade+period)  <-- STAGING
--   2. cst_rm_group_detail   (valuation inputs: freight/duty/anti-dumping/transport)
--   3. fix_rate FL manual     (di OUTPUT cst_rm_cost_detail, editable Finance)
-- Loader V2 (syncdata_v2_source.go): stock = stores+dept (digabung); PO = last_po_1 saja.
--
-- Semua query READ-ONLY. Jalankan berurutan, narrow → wide. Stop di check pertama
-- yang FAIL dan telusuri sebelum lanjut.
-- ============================================================================


-- P0 — Apakah staging 202607 ADA sama sekali? (paling sempit dulu)
SELECT count(*) AS rows_202607
FROM cst_item_cons_stk_po
WHERE period = '202607';
-- EXPECT: > 0. Bandingkan ke 202606 di P5.
-- FAIL (0): staging 202607 BELUM di-load. Ini blocker mutlak — load
--           cst_item_cons_stk_po dari legacy PRC_CST_CONSSTKPO_MGT dulu.
--           (Catatan: TED per-line PO correction di legacy masih pending Finance —
--            konfirmasi staging yang di-load sudah versi terkoreksi.)


-- P1 — Kualitas staging: berapa yang punya rate non-nol per stage?
SELECT
    count(*)                                                        AS total_rows,
    count(*) FILTER (WHERE COALESCE(cons_qty,0)  > 0)               AS has_cons,
    count(*) FILTER (WHERE COALESCE(stores_qty,0)+COALESCE(dept_qty,0) > 0) AS has_stock_merged,
    count(*) FILTER (WHERE COALESCE(last_po_qty1,0) > 0)            AS has_po1
FROM cst_item_cons_stk_po
WHERE period = '202607';
-- EXPECT: has_cons / has_stock_merged / has_po1 masing-masing porsi wajar (bukan 0).
-- FAIL (semua 0 di satu stage): stage itu tak akan terisi di output → cascade
--       jatuh ke stage berikut / landed 0. Cek apakah load hanya sebagian kolom.


-- P2 — ⭐ COVERAGE (paling penting): item di grup aktif yang TIDAK punya row staging 202607.
--       Item tanpa staging → source qty 0 → landed 0 (SILENT, tanpa error).
SELECT gd.group_head_id, gd.item_code, COALESCE(gd.grade_code,'') AS grade_code
FROM cst_rm_group_detail gd
LEFT JOIN cst_item_cons_stk_po s
       ON s.period     = '202607'
      AND s.item_code  = gd.item_code
      AND COALESCE(s.grade_code,'') = COALESCE(gd.grade_code,'')
WHERE gd.is_active = TRUE
  AND gd.is_dummy  = FALSE
  AND gd.deleted_at IS NULL
  AND s.item_code IS NULL          -- tidak ketemu di staging
ORDER BY gd.group_head_id, gd.item_code;
-- EXPECT: 0 baris (semua item grup aktif tercover).
-- FAIL (>0): item-item ini akan landed 0 di V2. Putuskan: load staging yang kurang,
--            atau tandai item non-aktif, sebelum recompute massal.
-- Ringkasnya (jumlah saja):
--   SELECT count(*) FROM (... query di atas ...) x;


-- P3 — Valuation inputs (freight FL adder dkk) di grup aktif: ada / kosong?
--       Ingat: landed = rate_based + freight (di 1558 freight=0.01875). freight NULL/0
--       = tanpa FL adder (landed = base). Konfirmasi ini disengaja, bukan input hilang.
SELECT
    count(*)                                                   AS active_details,
    count(*) FILTER (WHERE freight_rate    IS NULL)            AS freight_null,
    count(*) FILTER (WHERE COALESCE(freight_rate,0)    = 0)    AS freight_zero,
    count(*) FILTER (WHERE duty_pct        IS NOT NULL)        AS has_duty,
    count(*) FILTER (WHERE anti_dumping_pct IS NOT NULL)       AS has_antidump,
    count(*) FILTER (WHERE transport_rate  IS NOT NULL)        AS has_transport
FROM cst_rm_group_detail
WHERE is_active = TRUE AND is_dummy = FALSE AND deleted_at IS NULL;
-- EXPECT: freight_null/zero kecil untuk grup yang seharusnya ada FL adder.
-- FAIL (freight_null tinggi padahal grup impor): valuation input belum di-entry →
--       landed akan = base (understated). Ini Finance input, bukan derived.


-- P4 — ⚠️ RISIKO WIPE FL: apakah 202607 sudah punya fix_rate (FL manual) yang
--       bisa hilang saat recompute? (FL backfill 202606 = 126 grup; 202607 mungkin
--       sudah/belum di-backfill.)
SELECT
    count(*)                                              AS detail_rows_202607,
    count(*) FILTER (WHERE fix_rate IS NOT NULL)          AS has_fix_rate,
    count(*) FILTER (WHERE COALESCE(fix_rate,0) > 0)      AS fix_rate_nonzero
FROM cst_rm_cost_detail
WHERE period = '202607';
-- EXPECT (interpretasi, bukan pass/fail): 
--   - Jika has_fix_rate > 0: KONFIRMASI ke Ilham apakah V2 recompute PRESERVE fix_rate
--     (edit_fix_rate_handler ada, tapi full recompute bisa menimpa). Kalau bisa ke-wipe,
--     export/backup fix_rate 202607 dulu SEBELUM recompute.
--   - Jika 0: FL 202607 belum di-backfill → bagian dari kerjaan V2 (bukan risiko wipe).


-- P5 — Baseline sanity: bandingkan coverage 202607 vs 202606 (periode yang sudah
--       terekonsiliasi ~80%). Selisih besar = load 202607 parsial.
SELECT period,
       count(*)                                        AS rows_total,
       count(DISTINCT item_code)                       AS distinct_items,
       count(*) FILTER (WHERE COALESCE(cons_qty,0) > 0) AS has_cons
FROM cst_item_cons_stk_po
WHERE period IN ('202606','202607')
GROUP BY period
ORDER BY period;
-- EXPECT: rows_total & distinct_items 202607 ≈ 202606 (bisnis stabil bulan-ke-bulan).
-- FAIL (202607 << 202606): load 202607 belum lengkap.


-- P6 — Grup aktif yang akan dihitung (scope recompute).
SELECT count(DISTINCT gh.group_head_id) AS active_groups
FROM cst_rm_group_head gh
WHERE gh.deleted_at IS NULL;         -- sesuaikan jika ada flag is_active di head
-- EXPECT: sesuai jumlah grup RM yang diharapkan (bandingkan ke handover / 202606).


-- ============================================================================
-- RINGKASAN GATE (semua harus hijau sebelum V2 recompute 202607):
--   P0 > 0            staging ada
--   P1 stage non-nol  kualitas load
--   P2 = 0 baris      coverage lengkap  ← paling kritis
--   P3 freight wajar  FL adder ter-entry
--   P4 keputusan      preserve/backup fix_rate bila sudah ada
--   P5 ≈ 202606       load tidak parsial
--   P6 sesuai         scope grup benar
-- ============================================================================
