-- ============================================================================
--  Query rekon v0.3.1 — Modul Integrasi ERP (goapps -> CST_GOAPPS_STD_COST -> OT_ADJ_ITEM)
--  Semua bagian Oracle bersifat READ-ONLY. Bind :P = 'YYYYMM', :B = batch id.
--  Bagian PG dijalankan di Postgres goapps (finance). Nama tabel PG = usulan 000392+.
--  v0.2 (CST_GOAPPS_ADJ_RATE, fallback cpm_flex_01) ada di archive_v0.2/.
--
--  Perubahan v0.3:
--    §0  cek objek interface baru + cek seed aturan vs PROD
--    §1c hanya sizing (resolusi resmi di goapps, §2b)
--    §2  coverage tanpa fallback; produk tanpa grade = AX
--    §4  memakai CST_GOAPPS_STD_COST (GSC_*)
--    §4a baru: duplikat Oracle = goapps (total kontrol + checksum)
--    §8  parallel run tanpa ekspektasi gap: SP* identik, COST/AX informasi
--    §9  baru: link kode item ERP legacy <-> goapps
--    §10 baru: satuan FG_CONVER_COST1..5 (T-4)
--    §11 baru: ekspor sampel hitung manual (kriteria penerimaan)
--    §12 baru: repoint — output objek lama vs V_GOAPPS_STD_COST_CUR
--
--  Perubahan v0.3.1:
--    MB (MBINVADJ/MBINVADJRP) dinilai goapps seperti yarn: §1b, §2, §4–§9 mencakup ketiga txn.
--    §1c tetap INVADJ saja (sizing aturan value loss yarn; MB grade A ERP = AX goapps).
--    §9d kandidat MB hanya produk tipe MB (cpt_type_code 'MB', kode CSTMB%); §9f baru: cek V-12.
--    §9a tidak lagi mengecualikan CMB. §9d diganti: kandidat produk untuk LinkErp (bukan review backfill).
-- ============================================================================

-- ---------------------------------------------------------------------------
-- §0 [Oracle] Struktur DEV vs PROD (jalankan di keduanya, bandingkan hasil)
-- ---------------------------------------------------------------------------
SELECT table_name, column_name, data_type, data_length, data_precision, data_scale, nullable
  FROM all_tab_columns
 WHERE owner = 'MGTDAT'
   AND table_name IN ('OT_ADJ_HEAD','OT_ADJ_ITEM','MGT_ITEM_COST_VAL_LOSS','IM_VS_STATIC_VALUE',
                      'OM_GRADE_CODE_1','OT_STD_COST_PRODUCTS_MGT',
                      'CST_GOAPPS_STD_BATCH','CST_GOAPPS_STD_COST','CST_GOAPPS_ADJ_LOG')
 ORDER BY table_name, column_id;
-- Tipe FG_* legacy menentukan tipe GSC_* / CAST di V_GOAPPS_STD_COST_CUR (ddl §5c).

SELECT name, type, MAX(line) lines
  FROM all_source
 WHERE owner = 'MGTDAT'
   AND name IN ('ODBTRG_COST_VAL_MGT','ODBTRG_FG_STD_COST','STD_FG_VALUE_INSERT','O_DGET_FG_ITEM_RATE_MGT',
                'FUNC_CHIP_WAC_MGT','PKG_GOAPPS_ADJ','TRG_GSB_GUARD','TRG_GSC_GUARD')
 GROUP BY name, type;

-- objek interface valid (setelah deploy DDL)
SELECT object_name, object_type, status
  FROM all_objects
 WHERE owner = 'MGTDAT'
   AND object_name IN ('CST_GOAPPS_STD_BATCH','CST_GOAPPS_STD_COST','CST_GOAPPS_ADJ_LOG','SEQ_GOAPPS_ADJ_LOG',
                       'TRG_GSB_GUARD','TRG_GSC_GUARD','V_GOAPPS_ADJ_DEMAND','V_GOAPPS_STD_COST_LATEST',
                       'V_GOAPPS_STD_COST_CUR','PKG_GOAPPS_ADJ')
 ORDER BY object_type, object_name;
-- harapan: semua VALID; trigger ENABLED (all_triggers.status).

-- Aturan: sumber seed goapps_000392_erp_rules_seed.sql (ALTHARADEV 2026-09-25).
-- Jalankan di PROD SEBELUM deploy seed. Beda = perbarui seed dulu.
-- Setelah seed, Finance memelihara aturan di goapps; tabel ERP ini tidak dibaca lagi.
SELECT MICVL_TYPE, MICVL_PROD_TYPE, MICVL_GRADE_GROUP, MICVL_BASIS, MICVL_VAL_LOSS
  FROM MGT_ITEM_COST_VAL_LOSS ORDER BY 1,2,3;                                   -- DEV: 115 baris
SELECT VSSV_CODE, VSSV_FIELD_01 FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = 'ITEMSELLPRIC';  -- DEV: 3
SELECT GRADE_CODE, GRADE_BL_SHORT_NAME FROM OM_GRADE_CODE_1 ORDER BY 1;         -- DEV: 22

-- [PG] pembanding isi seed
SELECT cevr_fg_type, cevr_prod_type, cevr_grade_group, cevr_basis, cevr_val_loss
  FROM cst_erp_valloss_rule ORDER BY 1,2,3;
SELECT cesp_basis, cesp_price FROM cst_erp_sell_price ORDER BY 1;
SELECT ceg_grade_code, ceg_grade_group FROM cost_erp_grade ORDER BY 1;

-- ---------------------------------------------------------------------------
-- §1 [Oracle] Demand & status periode
-- ---------------------------------------------------------------------------
-- 1a. ringkasan per txn
SELECT h.ADJH_TXN_CODE, COUNT(DISTINCT h.ADJH_SYS_ID) heads, COUNT(*) items,
       SUM(i.ADJI_QTY_BU)/1000 qty_kg, SUM(i.ADJI_VAL) val,
       SUM(CASE WHEN h.ADJH_APPR_STATUS = 3 THEN 1 ELSE 0 END) approved_items,
       SUM(CASE WHEN h.ADJH_POST_STATUS IS NOT NULL THEN 1 ELSE 0 END) posted_items,
       SUM(CASE WHEN NVL(i.ADJI_RATE,0) <= 0 THEN 1 ELSE 0 END) zero_rate_items
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
 GROUP BY h.ADJH_TXN_CODE;

-- 1b. granularitas INVADJ + MB: item+shade (coverage) vs item+grade+shade (valuasi)
SELECT COUNT(DISTINCT i.ADJI_ITEM_CODE||'|'||i.ADJI_GRADE_CODE_2)                        item_shade,
       COUNT(DISTINCT i.ADJI_ITEM_CODE||'|'||i.ADJI_GRADE_CODE_1||'|'||i.ADJI_GRADE_CODE_2) item_grade_shade
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'));

-- 1c. SIZING SAJA (sebelum backfill M-7): resolusi aturan non-AX memakai FG_TYPE std legacy.
--     Setelah go-live, resolusi resmi dihitung goapps dari cpm_erp_fg_type + aturan goapps (§2b).
WITH d AS (
  SELECT DISTINCT i.ADJI_ITEM_CODE item, i.ADJI_GRADE_CODE_1 grade, i.ADJI_GRADE_CODE_2 shade
    FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
   WHERE h.ADJH_TXN_CODE = 'INVADJ'
     AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
     AND i.ADJI_GRADE_CODE_1 <> 'AX'
), r AS (
  SELECT d.*, ax.FG_TYPE, g.GRADE_BL_SHORT_NAME grade_group,
         CASE SUBSTR(d.item,1,3) WHEN 'POY' THEN 'POY' WHEN 'ITY' THEN 'ITY' ELSE 'PTY' END prod_type,
         CASE WHEN ax.FG_ITEM_CODE IS NULL THEN 1 ELSE 0 END no_ax
    FROM d
    LEFT JOIN OT_STD_COST_PRODUCTS_MGT ax
           ON ax.FG_ITEM_CODE = d.item AND ax.FG_ITEM_GRADE = 'AX' AND ax.FG_ITEM_SHADE = d.shade
    LEFT JOIN OM_GRADE_CODE_1 g ON g.GRADE_CODE = d.grade
), z AS (
  SELECT CASE WHEN r.no_ax = 1          THEN 'NO_AX'
              WHEN r.FG_TYPE IS NULL     THEN 'NO_FG_TYPE'
              WHEN r.grade_group IS NULL THEN 'NO_GRADE_GROUP'
              WHEN m.MICVL_BASIS IS NULL THEN 'NO_RULE'
              ELSE m.MICVL_BASIS END resolution
    FROM r
    LEFT JOIN MGT_ITEM_COST_VAL_LOSS m
           ON m.MICVL_TYPE = r.FG_TYPE AND m.MICVL_PROD_TYPE = r.prod_type AND m.MICVL_GRADE_GROUP = r.grade_group
)
SELECT resolution, COUNT(*) combos FROM z GROUP BY resolution ORDER BY 2 DESC;
-- Di goapps "NO_AX" = item+shade tanpa cost AX goapps (§2). Tidak ada fallback ke std ERP (D-10):
-- setiap NO_AX / NO_MAPPING harus dibuatkan produk AX di costing.

-- ---------------------------------------------------------------------------
-- §2 [PG] Coverage item+shade terhadap cost AX ACTUAL APPROVED USD (tanpa fallback)
-- ---------------------------------------------------------------------------
WITH run AS (SELECT MAX(ced_run_id) run_id FROM cst_erp_adj_demand WHERE ced_period = :P),
dem AS (
  SELECT ced_item_code item, ced_shade_code shade,
         SUM(ced_qty_kg) qty_kg, COUNT(DISTINCT ced_grade_code) grades
    FROM cst_erp_adj_demand d JOIN run ON d.ced_run_id = run.run_id
   -- YARN + MB (v0.3.1)
   GROUP BY 1, 2
),
map AS (
  -- link wajib (D-11); produk tanpa grade = AX (OQ-F). Tidak ada COALESCE ke cpm_flex_01 / cpm_shade_code.
  SELECT m.cpm_product_sys_id, m.cpm_erp_item_code item, m.cpm_erp_grade_code_2 shade
    FROM cost_product_master m
   WHERE m.cpm_is_active
     AND COALESCE(NULLIF(m.cpm_grade_code,''),'AX') = 'AX'
),
cost AS (
  SELECT c.cpc_product_sys_id, c.cpc_status, c.cpc_currency_code, c.cpc_cost_per_unit, c.cpc_total_rm_cost
    FROM cst_product_cost c
   WHERE c.cpc_calculation_type = 'ACTUAL' AND c.cpc_period = :P
     AND c.cpc_status IN ('CALCULATED','VERIFIED','APPROVED')
)
SELECT dem.item, dem.shade, dem.qty_kg, dem.grades,
       COUNT(DISTINCT map.cpm_product_sys_id) n_products,
       CASE WHEN COUNT(map.cpm_product_sys_id) = 0              THEN 'NO_MAPPING'
            WHEN COUNT(DISTINCT map.cpm_product_sys_id) > 1     THEN 'INVALID'      -- V-04
            WHEN MAX(cost.cpc_status) IS NULL                   THEN 'NO_COST'
            WHEN MAX(cost.cpc_status) <> 'APPROVED'             THEN 'NOT_APPROVED'
            WHEN MAX(cost.cpc_currency_code) <> 'USD'           THEN 'INVALID'      -- V-09
            WHEN MAX(cost.cpc_cost_per_unit) <= 0
              OR MAX(cost.cpc_cost_per_unit) < MAX(cost.cpc_total_rm_cost) THEN 'INVALID'  -- V-01/V-03
            ELSE 'OK' END status
  FROM dem
  LEFT JOIN map  ON map.item = dem.item AND map.shade = dem.shade
  LEFT JOIN cost ON cost.cpc_product_sys_id = map.cpm_product_sys_id
 GROUP BY dem.item, dem.shade, dem.qty_kg, dem.grades
 ORDER BY status <> 'OK' DESC, dem.qty_kg DESC;
-- harapan sebelum push: semua OK.

-- 2b. [PG] resolusi derivasi batch :B (harapan: hanya OK; lainnya V-08 dan memblokir push)
SELECT cesc_status, cesc_basis, COUNT(*) combos
  FROM cst_erp_std_cost WHERE cesc_batch_id = :B
 GROUP BY 1, 2 ORDER BY 1, 2;

-- ---------------------------------------------------------------------------
-- §3 [Oracle -> golden test] Derivasi ERP dari std AX ERP + aturan
--    Hasil = expected untuk unit test domain/erpintegration/derive.go.
--    DEV 2026-09-25: 9.602/9.602 std cocok. Tidak berubah dari v0.2.
-- ---------------------------------------------------------------------------
WITH ax AS (
  SELECT FG_ITEM_CODE item, FG_ITEM_SHADE shade, FG_TYPE, FG_CHP_COST chp, FG_CHP_CON_KG kg,
         FG_CONVER_COST ax_conv
    FROM OT_STD_COST_PRODUCTS_MGT WHERE FG_ITEM_GRADE = 'AX'
), der AS (
  SELECT s.FG_ITEM_CODE item, s.FG_ITEM_GRADE grade, s.FG_ITEM_SHADE shade,
         s.FG_COST_PER_KG std_erp, s.FG_BASIS basis_erp, s.FG_PROD_VALUE_LOSS pvl_erp
    FROM OT_STD_COST_PRODUCTS_MGT s WHERE s.FG_ITEM_GRADE <> 'AX'
), x AS (
  SELECT der.*, ax.FG_TYPE, ax.chp, ax.kg, ax.ax_conv, m.MICVL_BASIS basis, NVL(m.MICVL_VAL_LOSS,0) loss,
         NVL(TO_NUMBER(sp.VSSV_FIELD_01),0) selling
    FROM der
    JOIN ax ON ax.item = der.item AND ax.shade = der.shade
    LEFT JOIN OM_GRADE_CODE_1 g ON g.GRADE_CODE = der.grade
    LEFT JOIN MGT_ITEM_COST_VAL_LOSS m
           ON m.MICVL_TYPE = ax.FG_TYPE AND m.MICVL_GRADE_GROUP = g.GRADE_BL_SHORT_NAME
          AND m.MICVL_PROD_TYPE = CASE SUBSTR(der.item,1,3) WHEN 'POY' THEN 'POY' WHEN 'ITY' THEN 'ITY' ELSE 'PTY' END
    LEFT JOIN IM_VS_STATIC_VALUE sp ON sp.VSSV_VS_CODE = 'ITEMSELLPRIC' AND sp.VSSV_CODE = m.MICVL_BASIS
), e AS (
  SELECT x.*,
         CASE WHEN basis = 'COST' THEN ROUND(chp*kg + ROUND(ax_conv - loss,5),5)
              ELSE ROUND(selling - loss,5) END exp_std,
         ROUND(chp*kg + ROUND(ax_conv,5),5) exp_ax_cost
    FROM x
)
SELECT NVL(basis,'<none>') basis, COUNT(*) combos,
       SUM(CASE WHEN ROUND(std_erp,5) = exp_std THEN 1 ELSE 0 END) match_std,
       SUM(CASE WHEN ROUND(pvl_erp,5) = ROUND(exp_std - exp_ax_cost,5) THEN 1 ELSE 0 END) match_pvl,
       MAX(ABS(std_erp - exp_std)) max_diff
  FROM e GROUP BY basis ORDER BY 2 DESC;
-- Sampel golden (mis. 30 per basis) untuk fixture test. Baris mismatch = std ERP yang di-update manual
-- setelah derivasi → catat, jangan dipakai sebagai golden.

-- ---------------------------------------------------------------------------
-- §4 [Oracle] Setelah VALUATE_ADJ: ADJ vs CST_GOAPPS_STD_COST batch :B
-- ---------------------------------------------------------------------------
SELECT CASE WHEN s.GSC_ITEM_CODE IS NULL THEN 'NOT_IN_STD'
            WHEN a.rate_variants = 1 AND a.max_rate = s.GSC_STD_COST AND a.batch = TO_CHAR(:B) THEN 'MATCH'
            ELSE 'DIFF' END recon_status,
       COUNT(*) combos, SUM(a.items) items, SUM(a.val) val
  FROM (SELECT i.ADJI_ITEM_CODE item, i.ADJI_GRADE_CODE_1 grade, i.ADJI_GRADE_CODE_2 shade,
               COUNT(*) items, COUNT(DISTINCT i.ADJI_RATE) rate_variants, MAX(i.ADJI_RATE) max_rate,
               SUM(i.ADJI_VAL) val, MAX(i.ADJI_FLEX_13) batch
          FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
         WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
           AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
         GROUP BY i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2) a
  LEFT JOIN CST_GOAPPS_STD_COST s
         ON s.GSC_BATCH_ID = :B AND s.GSC_ITEM_CODE = a.item
        AND s.GSC_GRADE_CODE = a.grade AND s.GSC_SHADE_CODE = a.shade
 GROUP BY CASE WHEN s.GSC_ITEM_CODE IS NULL THEN 'NOT_IN_STD'
               WHEN a.rate_variants = 1 AND a.max_rate = s.GSC_STD_COST AND a.batch = TO_CHAR(:B) THEN 'MATCH'
               ELSE 'DIFF' END;
-- harapan: hanya MATCH (NOT_IN_STD = coverage bolong → §7).

-- std yang dipush tapi tidak ada di ADJ (kombinasi hilang setelah ETL) → NOT_IN_ADJ
SELECT s.GSC_ITEM_CODE, s.GSC_GRADE_CODE, s.GSC_SHADE_CODE, s.GSC_STD_COST
  FROM CST_GOAPPS_STD_COST s
 WHERE s.GSC_BATCH_ID = :B
   AND NOT EXISTS (SELECT 1 FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
                    WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
                      AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
                      AND i.ADJI_ITEM_CODE = s.GSC_ITEM_CODE AND i.ADJI_GRADE_CODE_1 = s.GSC_GRADE_CODE
                      AND i.ADJI_GRADE_CODE_2 = s.GSC_SHADE_CODE);

-- flex sesuai std (sampel mismatch)
SELECT i.ADJI_SYS_ID, i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2,
       i.ADJI_FLEX_01, TO_CHAR(ROUND(NVL(s.GSC_CONV_COST,0),5),'FM990D00000')     exp_01,
       i.ADJI_FLEX_08, TO_CHAR(ROUND(NVL(s.GSC_AX_COST,0),5),'FM990D00000')       exp_08,
       i.ADJI_FLEX_11, TO_CHAR(ROUND(NVL(s.GSC_PROD_VAL_LOSS,0),5),'FM990D00000') exp_11,
       i.ADJI_FLEX_14, s.GSC_SOURCE
  FROM OT_ADJ_ITEM i
  JOIN CST_GOAPPS_STD_COST s ON s.GSC_BATCH_ID = :B AND s.GSC_ITEM_CODE = i.ADJI_ITEM_CODE
   AND s.GSC_GRADE_CODE = i.ADJI_GRADE_CODE_1 AND s.GSC_SHADE_CODE = i.ADJI_GRADE_CODE_2
 WHERE i.ADJI_FLEX_13 = TO_CHAR(:B)
   AND (NVL(i.ADJI_FLEX_01,'-') <> TO_CHAR(ROUND(NVL(s.GSC_CONV_COST,0),5),'FM990D00000')
     OR NVL(i.ADJI_FLEX_08,'-') <> TO_CHAR(ROUND(NVL(s.GSC_AX_COST,0),5),'FM990D00000')
     OR NVL(i.ADJI_FLEX_11,'-') <> TO_CHAR(ROUND(NVL(s.GSC_PROD_VAL_LOSS,0),5),'FM990D00000')
     OR NVL(i.ADJI_FLEX_14,'-') <> s.GSC_SOURCE)
   AND ROWNUM <= 50;

-- ---------------------------------------------------------------------------
-- §4a Duplikat Oracle = goapps (PRD §11.4). Bandingkan kedua hasil; harus identik.
-- ---------------------------------------------------------------------------
-- [PG] total kontrol + checksum batch :B
SELECT COUNT(*) n,
       SUM(cesc_std_cost)                     sum_std,
       SUM(COALESCE(cesc_conv_cost,0))        sum_conv,
       SUM(COALESCE(cesc_prod_value_loss,0))  sum_pvl,
       md5(string_agg(cesc_item_code||'|'||cesc_grade_code||'|'||cesc_shade_code||'|'
                      ||to_char(cesc_std_cost,'FM999990.00000'),
                      E'\n' ORDER BY cesc_item_code, cesc_grade_code, cesc_shade_code)) chk
  FROM cst_erp_std_cost
 WHERE cesc_batch_id = :B AND cesc_status = 'OK';

-- [Oracle] total kontrol header vs isi tabel (VALUATE_ADJ juga mengecek ini: ORA-20903/20905)
SELECT b.GSB_STATUS,
       b.GSB_ROW_COUNT, COUNT(s.GSC_ITEM_CODE)          n,
       b.GSB_SUM_STD,   SUM(s.GSC_STD_COST)             sum_std,
       b.GSB_SUM_CONV,  SUM(NVL(s.GSC_CONV_COST,0))     sum_conv,
       b.GSB_SUM_PVL,   SUM(NVL(s.GSC_PROD_VAL_LOSS,0)) sum_pvl
  FROM CST_GOAPPS_STD_BATCH b
  LEFT JOIN CST_GOAPPS_STD_COST s ON s.GSC_BATCH_ID = b.GSB_BATCH_ID
 WHERE b.GSB_BATCH_ID = :B
 GROUP BY b.GSB_STATUS, b.GSB_ROW_COUNT, b.GSB_SUM_STD, b.GSB_SUM_CONV, b.GSB_SUM_PVL;

-- [Oracle] checksum: 11g tidak punya STANDARD_HASH → spool baris ini lalu md5 di luar
--          (baris dipisah newline, tanpa newline akhir; harus = kolom chk PG di atas)
SELECT s.GSC_ITEM_CODE||'|'||s.GSC_GRADE_CODE||'|'||s.GSC_SHADE_CODE||'|'
       ||TO_CHAR(s.GSC_STD_COST,'FM999990.00000','NLS_NUMERIC_CHARACTERS=''.,''') line
  FROM CST_GOAPPS_STD_COST s
 WHERE s.GSC_BATCH_ID = :B
 ORDER BY s.GSC_ITEM_CODE, s.GSC_GRADE_CODE, s.GSC_SHADE_CODE;
-- catatan: urutan ORDER BY Oracle (NLS_SORT=BINARY) vs PG (collation) bisa beda untuk kode non-ASCII;
--          PG pakai COLLATE "C" bila checksum tidak sama padahal total sama.

-- [Oracle] status batch per periode: maksimal 1 batch VALUATED/APPROVED/LOCKED per periode
SELECT GSB_PERIOD, GSB_STATUS, COUNT(*) n, MAX(GSB_BATCH_ID) last_batch
  FROM CST_GOAPPS_STD_BATCH GROUP BY GSB_PERIOD, GSB_STATUS ORDER BY 1, 2;

-- ---------------------------------------------------------------------------
-- §5 [Oracle] Total nilai & konsistensi ADJI_VAL
-- ---------------------------------------------------------------------------
SELECT NVL(i.ADJI_FLEX_14,'<legacy>') source, i.ADJI_FLEX_06 basis,
       COUNT(*) items, SUM(i.ADJI_QTY_BU)/1000 qty_kg, SUM(i.ADJI_VAL) val,
       SUM(CASE WHEN i.ADJI_VAL <> ROUND(i.ADJI_QTY_BU/1000*i.ADJI_RATE,7) THEN 1 ELSE 0 END) val_inconsistent
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
 GROUP BY NVL(i.ADJI_FLEX_14,'<legacy>'), i.ADJI_FLEX_06
 ORDER BY 1, 2;
-- Bandingkan SUM(val) dengan PG: SUM(cesc_erp_value) batch :B. Nilai sebelum ditimpa (log):
SELECT COUNT(*) items, SUM(OLD_VAL) old_val FROM CST_GOAPPS_ADJ_LOG WHERE GAL_BATCH_ID = :B;
SELECT GSB_STATUS, GSB_VALUATED_DT, GSB_SUMMARY, GSB_ERROR FROM CST_GOAPPS_STD_BATCH WHERE GSB_BATCH_ID = :B;

-- ---------------------------------------------------------------------------
-- §6 [Oracle] Pra-approve: calon error 241441 (ODBTRG_COST_VAL_MGT)
-- ---------------------------------------------------------------------------
SELECT h.ADJH_SYS_ID, h.ADJH_NO, i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2, i.ADJI_RATE
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
   AND h.ADJH_APPR_STATUS != 3
   AND SUBSTR(i.ADJI_ITEM_CODE,1,3) IN ('POY','PTY','ACY','ITY','MMK','TTY','HOY')
   AND (NVL(i.ADJI_RATE,0) <= 0 OR i.ADJI_RATE > 20);
-- harapan: 0 baris.

-- ---------------------------------------------------------------------------
-- §7 [Oracle] NOT_COVERED: ADJ yang tidak dinilai batch :B (masuk setelah ETL / tanpa std)
-- ---------------------------------------------------------------------------
SELECT i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2,
       COUNT(*) items, SUM(i.ADJI_QTY_BU)/1000 qty_kg, MIN(h.ADJH_CR_DT) first_created,
       MAX(i.ADJI_RATE) current_rate
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
   AND h.ADJH_POST_STATUS IS NULL
   AND NVL(i.ADJI_FLEX_13,'-') <> TO_CHAR(:B)
 GROUP BY i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2
 ORDER BY qty_kg DESC;
-- harapan: 0 baris sebelum approve. Jika ada: ETL ulang → (buat produk AX bila perlu) → derive → push batch baru.
-- Baris ini TIDAK di-reset oleh VALUATE_ADJ; rate awalnya (WMS) tetap ada.

-- ---------------------------------------------------------------------------
-- §8 [Parallel run 202609–202610] rate legacy PROD vs goapps DEV — tanpa ekspektasi gap
--    Jalankan query ini di PROD (legacy) dan DEV (goapps), ekspor CSV, join di PG/Excel
--    dengan kunci (item, grade, shade), kelompokkan per basis:
--      SPPTY / SPITY / SPBSD : HARUS identik (tidak bergantung cost).
--      COST / AX             : informasi saja. Rumus sengaja dikoreksi; gap −5,13% tidak dinilai.
--    Penerimaan COST/AX = sign-off hitung manual sampel (§11).
-- ---------------------------------------------------------------------------
SELECT i.ADJI_FLEX_06 basis, i.ADJI_ITEM_CODE item, i.ADJI_GRADE_CODE_1 grade, i.ADJI_GRADE_CODE_2 shade,
       COUNT(DISTINCT i.ADJI_RATE) rate_variants, MAX(i.ADJI_RATE) rate,
       SUM(i.ADJI_QTY_BU)/1000 qty_kg, SUM(i.ADJI_VAL) val
  FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
 WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
   AND TRUNC(h.ADJH_DT) BETWEEN TO_DATE(:P||'01','YYYYMMDD') AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
 GROUP BY i.ADJI_FLEX_06, i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2;
-- [PG] setelah dimuat ke tmp_pr_legacy / tmp_pr_goapps:
-- SELECT COALESCE(l.basis,g.basis) basis, COUNT(*) combos,
--        SUM(CASE WHEN l.rate = g.rate THEN 1 ELSE 0 END) same_rate,
--        SUM(CASE WHEN l.item IS NULL THEN 1 ELSE 0 END)  only_goapps,
--        SUM(CASE WHEN g.item IS NULL THEN 1 ELSE 0 END)  only_legacy,
--        SUM(g.val) - SUM(l.val) delta_val
--   FROM tmp_pr_legacy l FULL JOIN tmp_pr_goapps g USING (item, grade, shade)
--  GROUP BY 1 ORDER BY 1;
-- lolos: basis SP* → same_rate = combos.

-- ---------------------------------------------------------------------------
-- §9 Link kode item ERP legacy <-> goapps (D-11, FR-10, PRD §11.2) — satu-satunya link yang wajib
-- ---------------------------------------------------------------------------
-- 9a. [Oracle] item+shade ERP yang harus terpetakan: demand INVADJ + MB 3 bulan s.d. P + std AX legacy
SELECT item, shade, MAX(src) src, SUM(qty_kg) qty_kg
  FROM (SELECT i.ADJI_ITEM_CODE item, i.ADJI_GRADE_CODE_2 shade, 'ADJ' src, SUM(i.ADJI_QTY_BU)/1000 qty_kg
          FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
         WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
           AND TRUNC(h.ADJH_DT) BETWEEN ADD_MONTHS(TO_DATE(:P||'01','YYYYMMDD'), -2)
                                    AND LAST_DAY(TO_DATE(:P||'01','YYYYMMDD'))
         GROUP BY i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_2
        UNION ALL
        SELECT FG_ITEM_CODE, FG_ITEM_SHADE, 'STD', 0
          FROM OT_STD_COST_PRODUCTS_MGT
         WHERE FG_ITEM_GRADE IN ('AX','A') AND (FG_ITEM_GRADE = 'AX' OR FG_ITEM_CODE LIKE 'CMB%'))
 GROUP BY item, shade;
-- MAX(src): 'STD' > 'ADJ' secara alfabet → baris ber-ADJ dikenali dari qty_kg > 0.
-- Muat hasilnya ke PG sebagai tmp_erp_item_shade(item, shade, src, qty_kg).

-- 9b. [PG] ERP -> goapps: item+shade tanpa produk AX aktif, atau dengan > 1 produk
SELECT e.item, e.shade, e.qty_kg,
       COUNT(m.cpm_product_sys_id) n_products,
       CASE WHEN COUNT(m.cpm_product_sys_id) = 0 THEN 'NO_PRODUCT' ELSE 'DUPLICATE' END status
  FROM tmp_erp_item_shade e
  LEFT JOIN cost_product_master m
         ON m.cpm_erp_item_code = e.item AND m.cpm_erp_grade_code_2 = e.shade
        AND m.cpm_is_active AND COALESCE(NULLIF(m.cpm_grade_code,''),'AX') = 'AX'
 GROUP BY e.item, e.shade, e.qty_kg
HAVING COUNT(m.cpm_product_sys_id) <> 1
 ORDER BY e.qty_kg DESC;
-- lolos parallel run: 0 baris dengan qty_kg > 0. Baris qty_kg = 0 (std lama) = informasi.

-- 9c. [PG] goapps -> ERP: produk aktif dengan link kosong / tidak ada di replika ERP / tanpa FG type
--     (V-06, V-08, V-11). Nama kolom replika cost_erp_item / cost_erp_shade: sesuaikan dengan migration existing.
SELECT m.cpm_product_sys_id, m.cpm_erp_item_code, m.cpm_erp_grade_code_2, m.cpm_grade_code, m.cpm_erp_fg_type,
       CASE WHEN m.cpm_erp_item_code IS NULL OR m.cpm_erp_grade_code_2 IS NULL THEN 'NO_LINK'
            WHEN i.cei_item_code IS NULL  THEN 'ITEM_NOT_IN_ERP'
            WHEN s.ces_shade_code IS NULL THEN 'SHADE_NOT_IN_ERP'
            WHEN m.cpm_erp_fg_type IS NULL THEN 'NO_FG_TYPE' END issue
  FROM cost_product_master m
  LEFT JOIN cost_erp_item  i ON i.cei_item_code  = m.cpm_erp_item_code
  LEFT JOIN cost_erp_shade s ON s.ces_shade_code = m.cpm_erp_grade_code_2
 WHERE m.cpm_is_active
   AND (m.cpm_erp_item_code IS NULL OR m.cpm_erp_grade_code_2 IS NULL
        OR i.cei_item_code IS NULL OR s.ces_shade_code IS NULL OR m.cpm_erp_fg_type IS NULL);
-- lolos: 0 baris (PRD §11.2).

-- 9d. [PG] resolusi NO_MAPPING (v0.3.1, tanpa backfill link):
--     item+shade ber-ADJ tanpa produk (9b NO_PRODUCT) + kandidat produk aktif AX yang belum punya kode item ERP.
--     Ada kandidat yang cocok → Costing "Link ke produk existing" (LinkErp). Tidak ada → "Buat produk cost baru".
WITH gap AS (
  SELECT e.item, e.shade, e.qty_kg
    FROM tmp_erp_item_shade e
   WHERE e.qty_kg > 0
     AND NOT EXISTS (SELECT 1 FROM cost_product_master m
                      WHERE m.cpm_erp_item_code = e.item AND m.cpm_erp_grade_code_2 = e.shade
                        AND m.cpm_is_active AND COALESCE(NULLIF(m.cpm_grade_code,''),'AX') = 'AX')
)
SELECT g.item, g.shade, g.qty_kg, i.cei_item_name erp_item_name,
       c.cpm_product_sys_id cand_product, c.cpm_product_code cand_code, c.cpm_product_name cand_name,
       c.cpm_shade_code cand_shade,
       CASE WHEN c.cpm_product_sys_id IS NULL THEN 'CREATE_NEW' ELSE 'LINK_CANDIDATE' END action
  FROM gap g
  LEFT JOIN cost_erp_item i ON i.cei_item_code = g.item
  LEFT JOIN (SELECT m.*, t.cpt_type_code
               FROM cost_product_master m JOIN cost_product_type t ON t.cpt_type_id = m.cpm_product_type_id) c
         ON c.cpm_is_active AND COALESCE(NULLIF(c.cpm_grade_code,''),'AX') = 'AX'
        AND c.cpm_erp_item_code IS NULL
        -- MB: 1 CSTMB (tipe MB) : 1 CMB; yarn tidak boleh ke tipe MB (V-12)
        AND (CASE WHEN g.item LIKE 'CMB%' THEN c.cpt_type_code = 'MB' AND c.cpm_product_code LIKE 'CSTMB%'
                  ELSE c.cpt_type_code <> 'MB' END)
        AND (c.cpm_shade_code = g.shade OR c.cpm_shade_code IS NULL)
        AND (c.cpm_product_name ILIKE '%' || g.item || '%' OR c.cpm_product_name ILIKE i.cei_item_name)
 ORDER BY g.qty_kg DESC, g.item, g.shade;
-- Kandidat hanya saran; keputusan link oleh Costing di aplikasi (V-04 unik, V-06 item/shade ada di replika).
-- Kolom cpm_product_name (000106) dan cei_item_name (000103) sesuai migration existing.

-- 9e. [PG] duplikat sebelum membuat uix_cpm_erp_item_shade (000394 / migration susulan)
SELECT cpm_erp_item_code, cpm_erp_grade_code_2, COUNT(*) n, string_agg(cpm_product_sys_id::text, ',') products
  FROM cost_product_master
 WHERE cpm_is_active AND COALESCE(NULLIF(cpm_grade_code,''),'AX') = 'AX'
   AND cpm_erp_item_code IS NOT NULL AND cpm_erp_grade_code_2 IS NOT NULL
 GROUP BY 1, 2 HAVING COUNT(*) > 1;

-- 9f. [PG] V-12 konsistensi tipe: CMB hanya ke produk tipe MB (CSTMB%), yarn tidak ke tipe MB
SELECT m.cpm_product_sys_id, m.cpm_product_code, t.cpt_type_code, m.cpm_erp_item_code, m.cpm_erp_grade_code_2
  FROM cost_product_master m JOIN cost_product_type t ON t.cpt_type_id = m.cpm_product_type_id
 WHERE m.cpm_is_active AND m.cpm_erp_item_code IS NOT NULL
   AND ((m.cpm_erp_item_code LIKE 'CMB%') <> (t.cpt_type_code = 'MB' AND m.cpm_product_code LIKE 'CSTMB%'));
-- lolos: 0 baris.

-- ---------------------------------------------------------------------------
-- §10 [Oracle] Satuan FG_CONVER_COST1..5 vs FG_CONVER_COST (T-4; laporan margin membagi 100)
-- ---------------------------------------------------------------------------
SELECT FG_ITEM_GRADE grade, FG_BASIS basis, COUNT(*) n,
       ROUND(AVG(FG_CONVER_COST),5)  avg_conv,
       ROUND(AVG(FG_CONVER_COST1),5) avg_c1, ROUND(AVG(FG_CONVER_COST2),5) avg_c2,
       ROUND(AVG(FG_CONVER_COST4),5) avg_c4, ROUND(AVG(FG_CONVER_COST5),5) avg_c5,
       ROUND(MEDIAN(FG_CONVER_COST1 / NULLIF(FG_CONVER_COST,0)),3) med_ratio_c1,
       ROUND(MEDIAN(FG_CONVER_COST5 / NULLIF(FG_CONVER_COST,0)),3) med_ratio_c5,
       SUM(CASE WHEN FG_CONVER_COST1 IS NULL THEN 1 ELSE 0 END) c1_null
  FROM OT_STD_COST_PRODUCTS_MGT
 WHERE FG_ITEM_CODE NOT LIKE 'CMB%'
 GROUP BY FG_ITEM_GRADE, FG_BASIS
 ORDER BY n DESC;
-- med_ratio ~ 1   → satuan sama (USD/kg): default NVL(GSC_CONV_COSTn, GSC_CONV_COST) di view benar.
-- med_ratio ~ 100 → tier disimpan ×100: goapps harus mengisi GSC_CONV_COSTn ×100, dan NVL di view
--                   diganti NVL(GSC_CONV_COSTn, GSC_CONV_COST*100). Putuskan sebelum repoint (T-4).

-- ---------------------------------------------------------------------------
-- §11 [PG] Ekspor sampel untuk hitung manual Finance (PRD §11.1, M-9)
--      tmp_manual_sample(item, grade, shade) = pilihan Finance, minimal 30 kombinasi:
--      POY/PTY/ITY; AX + turunan; basis COST/SPPTY/SPITY/SPBSD; ber-MB dan tanpa MB; produk baru dari coverage.
-- ---------------------------------------------------------------------------
SELECT s.cesc_item_code, s.cesc_grade_code, s.cesc_shade_code, s.cesc_prod_type, s.cesc_fg_type,
       s.cesc_grade_group, s.cesc_basis, s.cesc_ms_batch_item,
       c.cpc_cost_per_unit ax_total, c.cpc_total_rm_cost ax_rm,
       s.cesc_chp_cost, s.cesc_chp_con_kg, s.cesc_ax_conv_cost, s.cesc_value_loss, s.cesc_selling_price,
       s.cesc_conv_cost, s.cesc_ax_cost, s.cesc_std_cost, s.cesc_prod_value_loss,
       s.cesc_ax_cost_sys_id, s.cesc_ax_cost_version,
       NULL::numeric manual_ax_cost, NULL::numeric manual_std, NULL::text manual_note   -- diisi Finance
  FROM cst_erp_std_cost s
  LEFT JOIN cst_product_cost c ON c.cpc_sys_id = s.cesc_ax_cost_sys_id      -- nama PK: sesuaikan
 WHERE s.cesc_batch_id = :B
   AND (s.cesc_item_code, s.cesc_grade_code, s.cesc_shade_code) IN (SELECT item, grade, shade FROM tmp_manual_sample)
 ORDER BY s.cesc_basis, s.cesc_item_code, s.cesc_grade_code;
-- Parameter cost AX (route, CAPP, RM) dilihat di layar costing produk (cesc_ax_cost_sys_id / version).
-- Lolos: ROUND(manual_ax_cost,5) = cesc_ax_cost DAN ROUND(manual_std,5) = cesc_std_cost untuk semua baris.
-- Yang gagal: analisis; defect engine terbuka E-8/E-6/E-7 (P0), E-5 (P3) kemungkinan muncul di sini.

-- ---------------------------------------------------------------------------
-- §12 [Oracle, DEV] Repoint: output objek lama vs baru (sebelum cutover, M-ERP-4)
-- ---------------------------------------------------------------------------
-- 12a. isi view per sumber + tidak ada kunci ganda
SELECT SOURCE, COUNT(*) n FROM V_GOAPPS_STD_COST_CUR GROUP BY SOURCE;
SELECT COUNT(*) dup
  FROM (SELECT FG_ITEM_CODE, FG_ITEM_GRADE, FG_ITEM_SHADE FROM V_GOAPPS_STD_COST_CUR
         GROUP BY FG_ITEM_CODE, FG_ITEM_GRADE, FG_ITEM_SHADE HAVING COUNT(*) > 1);        -- harus 0

-- 12b. kombinasi tabel lama yang hilang dari view (harus 0 karena LEGACY_FROZEN)
SELECT COUNT(*) missing
  FROM OT_STD_COST_PRODUCTS_MGT o
 WHERE NOT EXISTS (SELECT 1 FROM V_GOAPPS_STD_COST_CUR v
                    WHERE v.FG_ITEM_CODE = o.FG_ITEM_CODE AND v.FG_ITEM_GRADE = o.FG_ITEM_GRADE
                      AND v.FG_ITEM_SHADE = o.FG_ITEM_SHADE);

-- 12c. selisih kolom yang dipakai objek repoint, per sumber/basis
SELECT v.SOURCE, NVL(v.FG_BASIS,'-') basis, COUNT(*) n,
       SUM(CASE WHEN ROUND(v.FG_COST_PER_KG,5) <> ROUND(o.FG_COST_PER_KG,5) THEN 1 ELSE 0 END)                 diff_std,
       SUM(CASE WHEN ROUND(NVL(v.FG_CONVER_COST,0),5) <> ROUND(NVL(o.FG_CONVER_COST,0),5) THEN 1 ELSE 0 END)   diff_conv,
       SUM(CASE WHEN ROUND(NVL(v.FG_CONVER_COST1,0),5) <> ROUND(NVL(o.FG_CONVER_COST1,0),5) THEN 1 ELSE 0 END) diff_conv1,
       SUM(CASE WHEN NVL(v.FG_CHP_ITEM_CODE,'-') <> NVL(o.FG_CHP_ITEM_CODE,'-') THEN 1 ELSE 0 END)             diff_chp_item,
       SUM(CASE WHEN NVL(v.FG_MS_BATCH_ITEM,'-') <> NVL(o.FG_MS_BATCH_ITEM,'-') THEN 1 ELSE 0 END)             diff_mb,
       SUM(CASE WHEN NVL(v.FG_TYPE,'-') <> NVL(o.FG_TYPE,'-') THEN 1 ELSE 0 END)                               diff_fg_type
  FROM V_GOAPPS_STD_COST_CUR v
  JOIN OT_STD_COST_PRODUCTS_MGT o
    ON o.FG_ITEM_CODE = v.FG_ITEM_CODE AND o.FG_ITEM_GRADE = v.FG_ITEM_GRADE AND o.FG_ITEM_SHADE = v.FG_ITEM_SHADE
 GROUP BY v.SOURCE, NVL(v.FG_BASIS,'-')
 ORDER BY 1, 2;
-- LEGACY_FROZEN: semua diff = 0.
-- GOAPPS_*: basis SP* → diff_std = 0; COST/AX berbeda = koreksi rumus (informasi).
-- diff_chp_item / diff_mb / diff_fg_type ≠ 0 → cek backfill atribut M-7 (job BackfillErpAttributes).

-- 12d. laporan margin: compile salinan ter-repoint di DEV (mis. VIEW_MARGIN_REPORT_GOAPPS), lalu per SO sampel:
-- SELECT <kunci SO>, <kolom FG Cost> FROM VIEW_MARGIN_REPORT        WHERE <SO sampel>
-- MINUS
-- SELECT <kunci SO>, <kolom FG Cost> FROM VIEW_MARGIN_REPORT_GOAPPS WHERE <SO sampel>;
-- "FG Cost" NULL di versi baru = tier kosong → cek NVL view dan satuan (§10).

-- 12e. rate awal WMS: compile O_DGET_FG_ITEM_RATE_MGT ter-repoint dengan nama lain di DEV,
--      panggil versi lama dan baru untuk setiap kombinasi demand §1b; bandingkan hasil dan kejadian 220079.
--      Kombinasi goapps baru (tidak ada di tabel lama) seharusnya kini MENDAPAT rate, bukan 220079.
