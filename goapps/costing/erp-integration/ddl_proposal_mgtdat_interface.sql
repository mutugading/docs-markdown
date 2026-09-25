-- ============================================================================
--  USULAN DDL v0.3.1 — Duplikat std cost goapps di MGTDAT + valuasi ADJ
--  Status : PROPOSAL, belum dieksekusi di DB mana pun. Review DBA/ERP dulu.
--  Target : Oracle 11.2.0.4 (ALTHARADEV) -> ALTHARA (PROD). Tanpa IDENTITY (11g).
--
--  Arsitektur v0.3 (PRD D-1, D-5, D-9):
--    - goapps (single source) menyalin std per periode+batch ke CST_GOAPPS_STD_COST.
--      Hanya session user GOAPPS_IF yang boleh menulis (grant + trigger guard).
--    - PKG_GOAPPS_ADJ.VALUATE_ADJ membaca CST_GOAPPS_STD_COST batch-nya -> OT_ADJ_ITEM.
--    - V_GOAPPS_STD_COST_CUR = pengganti baca OT_STD_COST_PRODUCTS_MGT (kolom kompatibel);
--      objek ERP yang membaca tabel lama di-repoint (§6). Tabel lama dibekukan.
--  v0.2 (CST_GOAPPS_ADJ_BATCH/RATE) ada di archive_v0.2/.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Batch header (1 baris per push goapps)
-- ---------------------------------------------------------------------------
CREATE TABLE MGTDAT.CST_GOAPPS_STD_BATCH (
  GSB_BATCH_ID     NUMBER(18)   PRIMARY KEY,     -- = goapps ceib_batch_id
  GSB_PERIOD       VARCHAR2(6)  NOT NULL,        -- YYYYMM
  GSB_SEQ          NUMBER(4)    NOT NULL,        -- = ceib_seq
  GSB_STATUS       VARCHAR2(12) NOT NULL,        -- PUSHED|VALUATED|APPROVED|LOCKED|FAILED|SUPERSEDED
  GSB_RULE_HASH    VARCHAR2(64),                 -- = ceib_rule_hash (snapshot aturan)
  -- total kontrol yang dideklarasikan goapps; VALUATE_ADJ menghitung ulang & menolak bila beda
  GSB_ROW_COUNT    NUMBER       NOT NULL,
  GSB_SUM_STD      NUMBER       NOT NULL,        -- SUM(GSC_STD_COST)
  GSB_SUM_CONV     NUMBER       NOT NULL,        -- SUM(NVL(GSC_CONV_COST,0))
  GSB_SUM_PVL      NUMBER       NOT NULL,        -- SUM(NVL(GSC_PROD_VAL_LOSS,0))
  GSB_PUSHED_BY    VARCHAR2(64),                 -- user goapps (bukan user Oracle)
  GSB_PUSHED_DT    DATE DEFAULT SYSDATE,
  GSB_VALUATED_DT  DATE,
  GSB_LOCKED_DT    DATE,
  GSB_SUMMARY      VARCHAR2(4000),
  GSB_ERROR        VARCHAR2(4000),
  CONSTRAINT UQ_GSB_PERIOD_SEQ UNIQUE (GSB_PERIOD, GSB_SEQ),
  CONSTRAINT CK_GSB_STATUS CHECK (GSB_STATUS IN ('PUSHED','VALUATED','APPROVED','LOCKED','FAILED','SUPERSEDED'))
);

-- ---------------------------------------------------------------------------
-- 2. Duplikat std cost goapps (pengganti CST_GOAPPS_ADJ_RATE v0.2)
--    1 baris per (batch, item, grade, shade). Insert-only: re-push = batch baru.
--    Tipe kolom FG_* legacy: cocokkan dengan all_tab_columns (recon §0) sebelum deploy.
-- ---------------------------------------------------------------------------
CREATE TABLE MGTDAT.CST_GOAPPS_STD_COST (
  GSC_BATCH_ID        NUMBER(18)   NOT NULL,
  GSC_PERIOD          VARCHAR2(6)  NOT NULL,
  GSC_ITEM_CODE       VARCHAR2(12) NOT NULL,     -- FG_ITEM_CODE
  GSC_GRADE_CODE      VARCHAR2(12) NOT NULL,     -- FG_ITEM_GRADE
  GSC_SHADE_CODE      VARCHAR2(12) NOT NULL,     -- FG_ITEM_SHADE
  GSC_ITEM_NAME       VARCHAR2(240),
  GSC_SHADE_NAME      VARCHAR2(240),
  GSC_SOURCE          VARCHAR2(16) NOT NULL,     -- GOAPPS_AX|GOAPPS_DERIVED|GOAPPS_MB   -> FLEX_14
  GSC_STD_COST        NUMBER(20,5) NOT NULL,     -- FG_COST_PER_KG                     -> ADJI_RATE
  GSC_CONV_COST       NUMBER(20,5),              -- FG_CONVER_COST                     -> FLEX_01
  GSC_CONV_COST1      NUMBER(20,5),              -- FG_CONVER_COST1 (tier qty SO, T-4; satuan = legacy)
  GSC_CONV_COST2      NUMBER(20,5),              -- FG_CONVER_COST2
  GSC_CONV_COST4      NUMBER(20,5),              -- FG_CONVER_COST4
  GSC_CONV_COST5      NUMBER(20,5),              -- FG_CONVER_COST5
  GSC_CHP_CON_KG      NUMBER(20,5),              -- FG_CHP_CON_KG                      -> FLEX_02
  GSC_CHP_COST        NUMBER(20,5),              -- FG_CHP_COST                        -> FLEX_03
  GSC_CHP_ITEM_CODE   VARCHAR2(50),              -- FG_CHP_ITEM_CODE                   -> FLEX_04
  GSC_FG_TYPE         VARCHAR2(15),              -- FG_TYPE                            -> FLEX_05
  GSC_BASIS           VARCHAR2(15),              -- FG_BASIS                           -> FLEX_06
  GSC_SELLING_PRICE   NUMBER(20,5),              --                                    -> FLEX_07
  GSC_AX_COST         NUMBER(20,5),              --                                    -> FLEX_08
  GSC_AX_CONV_COST    NUMBER(20,5),              --                                    -> FLEX_09
  GSC_VALUE_LOSS      NUMBER(20,5),              --                                    -> FLEX_10
  GSC_PROD_VAL_LOSS   NUMBER(20,5),              -- FG_PROD_VALUE_LOSS                 -> FLEX_11
  GSC_MS_BATCH_ITEM   VARCHAR2(12),              -- FG_MS_BATCH_ITEM                   -> FLEX_12
  GSC_ITEM_TYPE       VARCHAR2(20),              -- FG_ITEM_TYPE (kompatibilitas)
  GSC_PRD_PER_DAY     NUMBER,                    -- FG_PRD_PER_DAY (kompatibilitas)
  GSC_AX_COST_SYS_ID  NUMBER(18),                -- audit: cst_product_cost sumber
  GSC_AX_COST_VERSION NUMBER(6),
  GSC_PUSHED_DT       DATE DEFAULT SYSDATE,
  CONSTRAINT PK_CST_GOAPPS_STD_COST PRIMARY KEY (GSC_BATCH_ID, GSC_ITEM_CODE, GSC_GRADE_CODE, GSC_SHADE_CODE),
  CONSTRAINT FK_GSC_BATCH FOREIGN KEY (GSC_BATCH_ID) REFERENCES MGTDAT.CST_GOAPPS_STD_BATCH,
  CONSTRAINT CK_GSC_STD CHECK (GSC_STD_COST > 0)
);
CREATE INDEX MGTDAT.IX_GSC_KEY ON MGTDAT.CST_GOAPPS_STD_COST (GSC_ITEM_CODE, GSC_GRADE_CODE, GSC_SHADE_CODE, GSC_PERIOD);

-- ---------------------------------------------------------------------------
-- 3. Guard single source (D-1): hanya session GOAPPS_IF yang boleh DML.
--    USER = session user, juga saat dipanggil dari package AUTHID DEFINER.
--    Pengecualian darurat hanya lewat change control DBA (ALTER TRIGGER ... DISABLE).
-- ---------------------------------------------------------------------------
CREATE OR REPLACE TRIGGER MGTDAT.TRG_GSB_GUARD
BEFORE INSERT OR UPDATE OR DELETE ON MGTDAT.CST_GOAPPS_STD_BATCH
BEGIN
  IF USER <> 'GOAPPS_IF' THEN
    RAISE_APPLICATION_ERROR(-20910, 'CST_GOAPPS_STD_BATCH hanya boleh diubah oleh goapps (GOAPPS_IF).');
  END IF;
END;
/

CREATE OR REPLACE TRIGGER MGTDAT.TRG_GSC_GUARD
BEFORE INSERT OR UPDATE OR DELETE ON MGTDAT.CST_GOAPPS_STD_COST
FOR EACH ROW
DECLARE
  v_status VARCHAR2(12);
BEGIN
  IF USER <> 'GOAPPS_IF' THEN
    RAISE_APPLICATION_ERROR(-20910, 'CST_GOAPPS_STD_COST hanya boleh diubah oleh goapps (GOAPPS_IF).');
  END IF;
  IF UPDATING OR DELETING THEN
    RAISE_APPLICATION_ERROR(-20911, 'CST_GOAPPS_STD_COST insert-only; koreksi = push batch baru.');
  END IF;
  SELECT GSB_STATUS INTO v_status FROM MGTDAT.CST_GOAPPS_STD_BATCH WHERE GSB_BATCH_ID = :NEW.GSC_BATCH_ID;
  IF v_status <> 'PUSHED' THEN
    RAISE_APPLICATION_ERROR(-20912, 'Batch '||:NEW.GSC_BATCH_ID||' berstatus '||v_status||'; baris tidak boleh ditambah.');
  END IF;
END;
/

-- ---------------------------------------------------------------------------
-- 4. Log nilai ADJ sebelum ditimpa (rollback sebelum posting) — sama dengan v0.2
-- ---------------------------------------------------------------------------
CREATE SEQUENCE MGTDAT.SEQ_GOAPPS_ADJ_LOG;
CREATE TABLE MGTDAT.CST_GOAPPS_ADJ_LOG (
  GAL_ID          NUMBER(18)  PRIMARY KEY,
  GAL_BATCH_ID    NUMBER(18)  NOT NULL,
  GAL_ADJI_SYS_ID NUMBER      NOT NULL,
  OLD_RATE NUMBER, OLD_VAL NUMBER,
  OLD_FLEX_01 VARCHAR2(240), OLD_FLEX_02 VARCHAR2(240), OLD_FLEX_03 VARCHAR2(240), OLD_FLEX_04 VARCHAR2(240),
  OLD_FLEX_05 VARCHAR2(240), OLD_FLEX_06 VARCHAR2(240), OLD_FLEX_07 VARCHAR2(240), OLD_FLEX_08 VARCHAR2(240),
  OLD_FLEX_09 VARCHAR2(240), OLD_FLEX_10 VARCHAR2(240), OLD_FLEX_11 VARCHAR2(240), OLD_FLEX_12 VARCHAR2(240),
  OLD_FLEX_13 VARCHAR2(240), OLD_FLEX_14 VARCHAR2(240),
  GAL_DT DATE DEFAULT SYSDATE
);
CREATE INDEX MGTDAT.IX_GAL_BATCH ON MGTDAT.CST_GOAPPS_ADJ_LOG (GAL_BATCH_ID);

-- ---------------------------------------------------------------------------
-- 5. View
-- ---------------------------------------------------------------------------
-- 5a. Demand ADJ (ETL + rekon) — sama dengan v0.2
CREATE OR REPLACE VIEW MGTDAT.V_GOAPPS_ADJ_DEMAND AS
SELECT TO_CHAR(h.ADJH_DT,'YYYYMM')       PERIOD,
       h.ADJH_TXN_CODE                   TXN_CODE,
       i.ADJI_ITEM_CODE                  ITEM_CODE,
       i.ADJI_GRADE_CODE_1               GRADE_CODE,
       i.ADJI_GRADE_CODE_2               SHADE_CODE,
       COUNT(*)                          ITEM_COUNT,
       SUM(i.ADJI_QTY_BU)/1000           QTY_KG,
       COUNT(DISTINCT i.ADJI_RATE)       RATE_VARIANTS,
       MIN(i.ADJI_RATE)                  MIN_RATE,
       MAX(i.ADJI_RATE)                  MAX_RATE,
       SUM(i.ADJI_VAL)                   ADJ_VAL,
       SUM(CASE WHEN h.ADJH_APPR_STATUS = 3 THEN 1 ELSE 0 END)          APPROVED_ITEMS,
       SUM(CASE WHEN h.ADJH_POST_STATUS IS NOT NULL THEN 1 ELSE 0 END)  POSTED_ITEMS,
       MAX(i.ADJI_FLEX_13)               GOAPPS_BATCH,
       MAX(i.ADJI_FLEX_14)               GOAPPS_SOURCE
FROM   MGTDAT.OT_ADJ_HEAD h
JOIN   MGTDAT.OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
WHERE  h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
GROUP BY TO_CHAR(h.ADJH_DT,'YYYYMM'), h.ADJH_TXN_CODE,
         i.ADJI_ITEM_CODE, i.ADJI_GRADE_CODE_1, i.ADJI_GRADE_CODE_2;
-- goapps SELALU memfilter PERIOD = :P. Jika lambat, pakai query ber-range TRUNC(ADJH_DT) (DATA_MAPPING_SPEC §2.1).

-- 5b. Std goapps terkini per (item, grade, shade): baris dari batch valid dengan periode/batch terbaru
--     (carry-forward kombinasi yang tidak diproduksi bulan ini). Hanya data goapps.
CREATE OR REPLACE VIEW MGTDAT.V_GOAPPS_STD_COST_LATEST AS
SELECT *
  FROM (SELECT s.*, b.GSB_STATUS,
               ROW_NUMBER() OVER (PARTITION BY s.GSC_ITEM_CODE, s.GSC_GRADE_CODE, s.GSC_SHADE_CODE
                                  ORDER BY s.GSC_PERIOD DESC, s.GSC_BATCH_ID DESC) rn
          FROM MGTDAT.CST_GOAPPS_STD_COST s
          JOIN MGTDAT.CST_GOAPPS_STD_BATCH b ON b.GSB_BATCH_ID = s.GSC_BATCH_ID
         WHERE b.GSB_STATUS IN ('VALUATED','APPROVED','LOCKED'))
 WHERE rn = 1;

-- 5c. Pengganti baca OT_STD_COST_PRODUCTS_MGT — nama kolom legacy (repoint cukup ganti nama objek).
--     Baris legacy yang belum pernah ada di goapps ikut tampil sebagai SOURCE='LEGACY_FROZEN'
--     (produk lama yang belum pernah di-push goapps) HANYA untuk laporan margin / rate awal WMS. Valuasi ADJ tidak memakai view ini.
CREATE OR REPLACE VIEW MGTDAT.V_GOAPPS_STD_COST_CUR AS
SELECT g.GSC_ITEM_CODE                            FG_ITEM_CODE,
       g.GSC_GRADE_CODE                           FG_ITEM_GRADE,
       g.GSC_SHADE_CODE                           FG_ITEM_SHADE,
       g.GSC_STD_COST                             FG_COST_PER_KG,
       g.GSC_CONV_COST                            FG_CONVER_COST,
       NVL(g.GSC_CONV_COST1, g.GSC_CONV_COST)     FG_CONVER_COST1,   -- NVL: tanpa ini "FG Cost" margin = NULL
       NVL(g.GSC_CONV_COST2, g.GSC_CONV_COST)     FG_CONVER_COST2,
       NVL(g.GSC_CONV_COST4, g.GSC_CONV_COST)     FG_CONVER_COST4,
       NVL(g.GSC_CONV_COST5, g.GSC_CONV_COST)     FG_CONVER_COST5,
       g.GSC_CHP_ITEM_CODE                        FG_CHP_ITEM_CODE,
       g.GSC_CHP_CON_KG                           FG_CHP_CON_KG,
       g.GSC_CHP_COST                             FG_CHP_COST,
       g.GSC_FG_TYPE                              FG_TYPE,
       g.GSC_BASIS                                FG_BASIS,
       g.GSC_PROD_VAL_LOSS                        FG_PROD_VALUE_LOSS,
       g.GSC_MS_BATCH_ITEM                        FG_MS_BATCH_ITEM,
       g.GSC_ITEM_TYPE                            FG_ITEM_TYPE,
       g.GSC_PRD_PER_DAY                          FG_PRD_PER_DAY,
       g.GSC_SOURCE                               SOURCE,
       g.GSC_PERIOD                               PERIOD,
       g.GSC_BATCH_ID                             BATCH_ID
  FROM MGTDAT.V_GOAPPS_STD_COST_LATEST g
UNION ALL
SELECT o.FG_ITEM_CODE, o.FG_ITEM_GRADE, o.FG_ITEM_SHADE,
       o.FG_COST_PER_KG, o.FG_CONVER_COST,
       o.FG_CONVER_COST1, o.FG_CONVER_COST2, o.FG_CONVER_COST4, o.FG_CONVER_COST5,
       o.FG_CHP_ITEM_CODE, o.FG_CHP_CON_KG, o.FG_CHP_COST,
       o.FG_TYPE, o.FG_BASIS, o.FG_PROD_VALUE_LOSS, o.FG_MS_BATCH_ITEM,
       o.FG_ITEM_TYPE, o.FG_PRD_PER_DAY,
       'LEGACY_FROZEN', NULL, NULL
  FROM MGTDAT.OT_STD_COST_PRODUCTS_MGT o
 WHERE NOT EXISTS (SELECT 1 FROM MGTDAT.CST_GOAPPS_STD_COST s
                    JOIN MGTDAT.CST_GOAPPS_STD_BATCH b ON b.GSB_BATCH_ID = s.GSC_BATCH_ID
                   WHERE b.GSB_STATUS IN ('VALUATED','APPROVED','LOCKED')
                     AND s.GSC_ITEM_CODE = o.FG_ITEM_CODE AND s.GSC_GRADE_CODE = o.FG_ITEM_GRADE
                     AND s.GSC_SHADE_CODE = o.FG_ITEM_SHADE);
-- Catatan: bila tipe kolom legacy beda (mis. FG_PRD_PER_DAY VARCHAR2), sesuaikan GSC_* atau CAST di sini.
-- Catatan: kolom FG_* di atas = yang dipakai objek §6; kolom legacy lain sengaja tidak dibawa.

-- ---------------------------------------------------------------------------
-- 6. Package — satu-satunya pintu tulis ke OT_ADJ_ITEM dari goapps
--    Tanpa COMMIT di dalam; goapps commit setelah call sukses.
-- ---------------------------------------------------------------------------
CREATE OR REPLACE PACKAGE MGTDAT.PKG_GOAPPS_ADJ AUTHID DEFINER AS
  -- goapps: INSERT batch (status PUSHED) + baris std, lalu panggil ini
  PROCEDURE VALUATE_ADJ (P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2);
  -- opsional (D-8): approve head yang semua itemnya dinilai batch ini, rate > 0
  PROCEDURE APPROVE_ADJ (P_BATCH_ID IN NUMBER, P_APPR_UID IN VARCHAR2, P_SUMMARY OUT VARCHAR2);
  -- rollback sebelum posting: kembalikan nilai ADJ dari log batch
  PROCEDURE RESTORE_ADJ (P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2);
  -- setelah posting: tandai batch LOCKED (FR-8)
  PROCEDURE LOCK_BATCH  (P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2);
END PKG_GOAPPS_ADJ;
/

CREATE OR REPLACE PACKAGE BODY MGTDAT.PKG_GOAPPS_ADJ AS

  -- v0.3.1: yarn (INVADJ) dan MB (MBINVADJ/MBINVADJRP) sama-sama dinilai dari batch goapps (OQ-5, D-7).

  FUNCTION period_start(p VARCHAR2) RETURN DATE IS BEGIN RETURN TO_DATE(p||'01','YYYYMMDD'); END;
  FUNCTION period_end  (p VARCHAR2) RETURN DATE IS BEGIN RETURN LAST_DAY(TO_DATE(p||'01','YYYYMMDD')); END;

  FUNCTION batch_period(P_BATCH_ID NUMBER) RETURN VARCHAR2 IS
    v VARCHAR2(6);
  BEGIN
    SELECT GSB_PERIOD INTO v FROM CST_GOAPPS_STD_BATCH WHERE GSB_BATCH_ID = P_BATCH_ID;
    RETURN v;
  END;

  FUNCTION posted_heads(p VARCHAR2) RETURN NUMBER IS
    n NUMBER;
  BEGIN
    SELECT COUNT(*) INTO n FROM OT_ADJ_HEAD
     WHERE ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
       AND TRUNC(ADJH_DT) BETWEEN period_start(p) AND period_end(p)
       AND ADJH_POST_STATUS IS NOT NULL;
    RETURN n;
  END;

  PROCEDURE assert_not_posted(p VARCHAR2) IS
    n NUMBER := posted_heads(p);
  BEGIN
    IF n > 0 THEN
      RAISE_APPLICATION_ERROR(-20901, 'Periode '||p||' sudah ada ADJ posted ('||n||' head). Integrasi ditolak.');
    END IF;
  END;

  -- total kontrol: duplikat Oracle harus sama dengan yang dideklarasikan goapps (PRD §11.4)
  PROCEDURE assert_batch_complete(P_BATCH_ID NUMBER) IS
    b CST_GOAPPS_STD_BATCH%ROWTYPE;
    n NUMBER; s_std NUMBER; s_conv NUMBER; s_pvl NUMBER;
  BEGIN
    SELECT * INTO b FROM CST_GOAPPS_STD_BATCH WHERE GSB_BATCH_ID = P_BATCH_ID;
    IF b.GSB_STATUS <> 'PUSHED' THEN
      RAISE_APPLICATION_ERROR(-20905, 'Batch '||P_BATCH_ID||' berstatus '||b.GSB_STATUS||', bukan PUSHED.');
    END IF;
    SELECT COUNT(*), NVL(SUM(GSC_STD_COST),0), NVL(SUM(NVL(GSC_CONV_COST,0)),0), NVL(SUM(NVL(GSC_PROD_VAL_LOSS,0)),0)
      INTO n, s_std, s_conv, s_pvl
      FROM CST_GOAPPS_STD_COST WHERE GSC_BATCH_ID = P_BATCH_ID;
    IF n <> b.GSB_ROW_COUNT OR s_std <> b.GSB_SUM_STD OR s_conv <> b.GSB_SUM_CONV OR s_pvl <> b.GSB_SUM_PVL THEN
      RAISE_APPLICATION_ERROR(-20903, 'Batch '||P_BATCH_ID||' tidak lengkap/tidak konsisten: rows '||n||'/'||b.GSB_ROW_COUNT
                                      ||', sum_std '||s_std||'/'||b.GSB_SUM_STD);
    END IF;
  END;

  PROCEDURE VALUATE_ADJ(P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2) IS
    p VARCHAR2(6) := batch_period(P_BATCH_ID);
    n_upd NUMBER; n_nocov NUMBER; n_bad NUMBER;
  BEGIN
    assert_not_posted(p);
    assert_batch_complete(P_BATCH_ID);

    -- tolak rate > 20 untuk prefix yang dijaga trigger ODBTRG_COST_VAL_MGT (goapps sudah validasi V-07)
    SELECT COUNT(*) INTO n_bad FROM CST_GOAPPS_STD_COST
     WHERE GSC_BATCH_ID = P_BATCH_ID AND GSC_STD_COST > 20
       AND SUBSTR(GSC_ITEM_CODE,1,3) IN ('POY','PTY','ACY','ITY','MMK','TTY','HOY');
    IF n_bad > 0 THEN
      RAISE_APPLICATION_ERROR(-20902, n_bad||' rate > 20 di batch '||P_BATCH_ID);
    END IF;

    -- log nilai lama untuk baris yang akan diubah
    INSERT INTO CST_GOAPPS_ADJ_LOG (GAL_ID, GAL_BATCH_ID, GAL_ADJI_SYS_ID, OLD_RATE, OLD_VAL,
           OLD_FLEX_01, OLD_FLEX_02, OLD_FLEX_03, OLD_FLEX_04, OLD_FLEX_05, OLD_FLEX_06, OLD_FLEX_07,
           OLD_FLEX_08, OLD_FLEX_09, OLD_FLEX_10, OLD_FLEX_11, OLD_FLEX_12, OLD_FLEX_13, OLD_FLEX_14)
    SELECT SEQ_GOAPPS_ADJ_LOG.NEXTVAL, P_BATCH_ID, i.ADJI_SYS_ID, i.ADJI_RATE, i.ADJI_VAL,
           i.ADJI_FLEX_01, i.ADJI_FLEX_02, i.ADJI_FLEX_03, i.ADJI_FLEX_04, i.ADJI_FLEX_05, i.ADJI_FLEX_06, i.ADJI_FLEX_07,
           i.ADJI_FLEX_08, i.ADJI_FLEX_09, i.ADJI_FLEX_10, i.ADJI_FLEX_11, i.ADJI_FLEX_12, i.ADJI_FLEX_13, i.ADJI_FLEX_14
      FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
     WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
       AND TRUNC(h.ADJH_DT) BETWEEN period_start(p) AND period_end(p)
       AND h.ADJH_APPR_STATUS != 3 AND h.ADJH_POST_STATUS IS NULL
       AND EXISTS (SELECT 1 FROM CST_GOAPPS_STD_COST s
                    WHERE s.GSC_BATCH_ID = P_BATCH_ID AND s.GSC_ITEM_CODE = i.ADJI_ITEM_CODE
                      AND s.GSC_GRADE_CODE = i.ADJI_GRADE_CODE_1 AND s.GSC_SHADE_CODE = i.ADJI_GRADE_CODE_2);

    -- hanya baris yang punya std di batch; baris tanpa std TIDAK di-reset (dilaporkan NOT_COVERED)
    -- format flex ditulis inline: fungsi privat package tidak bisa dipanggil dari SQL (PLS-00231)
    UPDATE OT_ADJ_ITEM i
       SET (ADJI_ITEM_DESC, ADJI_RATE, ADJI_VAL,
            ADJI_FLEX_01, ADJI_FLEX_02, ADJI_FLEX_03, ADJI_FLEX_04, ADJI_FLEX_05, ADJI_FLEX_06,
            ADJI_FLEX_07, ADJI_FLEX_08, ADJI_FLEX_09, ADJI_FLEX_10, ADJI_FLEX_11, ADJI_FLEX_12,
            ADJI_FLEX_13, ADJI_FLEX_14) =
           (SELECT it.ITEM_NAME,
                   s.GSC_STD_COST,
                   ROUND(i.ADJI_QTY_BU/1000 * s.GSC_STD_COST, 7),
                   TO_CHAR(ROUND(NVL(s.GSC_CONV_COST,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_CHP_CON_KG,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_CHP_COST,0),5),'FM990D00000'),
                   s.GSC_CHP_ITEM_CODE, s.GSC_FG_TYPE, s.GSC_BASIS,
                   TO_CHAR(ROUND(NVL(s.GSC_SELLING_PRICE,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_AX_COST,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_AX_CONV_COST,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_VALUE_LOSS,0),5),'FM990D00000'),
                   TO_CHAR(ROUND(NVL(s.GSC_PROD_VAL_LOSS,0),5),'FM990D00000'),
                   s.GSC_MS_BATCH_ITEM,
                   TO_CHAR(P_BATCH_ID), s.GSC_SOURCE                        -- D-6 (OQ-E disetujui)
              FROM CST_GOAPPS_STD_COST s
              JOIN OM_ITEM it ON it.ITEM_CODE = s.GSC_ITEM_CODE
             WHERE s.GSC_BATCH_ID = P_BATCH_ID AND s.GSC_ITEM_CODE = i.ADJI_ITEM_CODE
               AND s.GSC_GRADE_CODE = i.ADJI_GRADE_CODE_1 AND s.GSC_SHADE_CODE = i.ADJI_GRADE_CODE_2)
     WHERE i.ADJI_SYS_ID IN (SELECT l.GAL_ADJI_SYS_ID FROM CST_GOAPPS_ADJ_LOG l WHERE l.GAL_BATCH_ID = P_BATCH_ID);
    n_upd := SQL%ROWCOUNT;

    SELECT COUNT(*) INTO n_nocov
      FROM OT_ADJ_HEAD h JOIN OT_ADJ_ITEM i ON i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
     WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
       AND TRUNC(h.ADJH_DT) BETWEEN period_start(p) AND period_end(p)
       AND h.ADJH_APPR_STATUS != 3 AND h.ADJH_POST_STATUS IS NULL
       AND NVL(i.ADJI_FLEX_13,'-') <> TO_CHAR(P_BATCH_ID);

    UPDATE CST_GOAPPS_STD_BATCH SET GSB_STATUS = 'SUPERSEDED'
     WHERE GSB_PERIOD = p AND GSB_BATCH_ID <> P_BATCH_ID AND GSB_STATUS NOT IN ('SUPERSEDED','FAILED');
    P_SUMMARY := '{"adj_items_updated":'||n_upd||',"adj_items_not_covered":'||n_nocov||'}';
    UPDATE CST_GOAPPS_STD_BATCH
       SET GSB_STATUS = 'VALUATED', GSB_VALUATED_DT = SYSDATE, GSB_SUMMARY = P_SUMMARY
     WHERE GSB_BATCH_ID = P_BATCH_ID;
  END VALUATE_ADJ;

  PROCEDURE APPROVE_ADJ(P_BATCH_ID IN NUMBER, P_APPR_UID IN VARCHAR2, P_SUMMARY OUT VARCHAR2) IS
    p VARCHAR2(6) := batch_period(P_BATCH_ID);
    n NUMBER;
  BEGIN
    assert_not_posted(p);
    UPDATE OT_ADJ_HEAD h
       SET ADJH_APPR_STATUS = 3, ADJH_APPR_UID = P_APPR_UID, ADJH_APPR_DT = SYSDATE
     WHERE h.ADJH_TXN_CODE IN ('INVADJ','MBINVADJ','MBINVADJRP')
       AND TRUNC(h.ADJH_DT) BETWEEN period_start(p) AND period_end(p)
       AND h.ADJH_APPR_STATUS != 3
       AND NOT EXISTS (SELECT 1 FROM OT_ADJ_ITEM i
                        WHERE i.ADJI_ADJH_SYS_ID = h.ADJH_SYS_ID
                          AND (NVL(i.ADJI_RATE,0) <= 0 OR NVL(i.ADJI_FLEX_13,'-') <> TO_CHAR(P_BATCH_ID)));
    n := SQL%ROWCOUNT;
    UPDATE CST_GOAPPS_STD_BATCH SET GSB_STATUS = 'APPROVED' WHERE GSB_BATCH_ID = P_BATCH_ID;
    P_SUMMARY := '{"adj_heads_approved":'||n||'}';
  END APPROVE_ADJ;

  PROCEDURE RESTORE_ADJ(P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2) IS
    p VARCHAR2(6) := batch_period(P_BATCH_ID);
    n NUMBER;
  BEGIN
    assert_not_posted(p);
    UPDATE OT_ADJ_ITEM i
       SET (ADJI_RATE, ADJI_VAL, ADJI_FLEX_01, ADJI_FLEX_02, ADJI_FLEX_03, ADJI_FLEX_04, ADJI_FLEX_05,
            ADJI_FLEX_06, ADJI_FLEX_07, ADJI_FLEX_08, ADJI_FLEX_09, ADJI_FLEX_10, ADJI_FLEX_11, ADJI_FLEX_12,
            ADJI_FLEX_13, ADJI_FLEX_14) =
           (SELECT l.OLD_RATE, l.OLD_VAL, l.OLD_FLEX_01, l.OLD_FLEX_02, l.OLD_FLEX_03, l.OLD_FLEX_04, l.OLD_FLEX_05,
                   l.OLD_FLEX_06, l.OLD_FLEX_07, l.OLD_FLEX_08, l.OLD_FLEX_09, l.OLD_FLEX_10, l.OLD_FLEX_11, l.OLD_FLEX_12,
                   l.OLD_FLEX_13, l.OLD_FLEX_14
              FROM CST_GOAPPS_ADJ_LOG l
             WHERE l.GAL_BATCH_ID = P_BATCH_ID AND l.GAL_ADJI_SYS_ID = i.ADJI_SYS_ID)
     WHERE i.ADJI_SYS_ID IN (SELECT GAL_ADJI_SYS_ID FROM CST_GOAPPS_ADJ_LOG WHERE GAL_BATCH_ID = P_BATCH_ID)
       AND i.ADJI_FLEX_13 = TO_CHAR(P_BATCH_ID);        -- jangan timpa hasil batch yang lebih baru
    n := SQL%ROWCOUNT;
    -- FAILED -> batch keluar dari V_GOAPPS_STD_COST_CUR; batch sebelumnya TIDAK otomatis aktif lagi
    -- (tetap SUPERSEDED). Bila perlu, push ulang batch dari goapps.
    UPDATE CST_GOAPPS_STD_BATCH SET GSB_STATUS = 'FAILED', GSB_ERROR = 'RESTORED' WHERE GSB_BATCH_ID = P_BATCH_ID;
    P_SUMMARY := '{"adj_items_restored":'||n||'}';
  END RESTORE_ADJ;

  PROCEDURE LOCK_BATCH(P_BATCH_ID IN NUMBER, P_SUMMARY OUT VARCHAR2) IS
    p VARCHAR2(6) := batch_period(P_BATCH_ID);
    n NUMBER := posted_heads(p);
  BEGIN
    IF n = 0 THEN
      RAISE_APPLICATION_ERROR(-20904, 'Periode '||p||' belum ada ADJ posted; lock ditolak.');
    END IF;
    UPDATE CST_GOAPPS_STD_BATCH SET GSB_STATUS = 'LOCKED', GSB_LOCKED_DT = SYSDATE
     WHERE GSB_BATCH_ID = P_BATCH_ID AND GSB_STATUS IN ('VALUATED','APPROVED');
    IF SQL%ROWCOUNT = 0 THEN
      RAISE_APPLICATION_ERROR(-20905, 'Batch '||P_BATCH_ID||' bukan VALUATED/APPROVED.');
    END IF;
    P_SUMMARY := '{"locked":1,"posted_heads":'||n||'}';
  END LOCK_BATCH;

END PKG_GOAPPS_ADJ;
/

-- ---------------------------------------------------------------------------
-- 7. Repoint objek pembaca OT_STD_COST_PRODUCTS_MGT (PRD §10, M-ERP-4) — saat cutover
--    Sumber objek tidak disalin di sini; lakukan per objek:
--      (1) backup DDL:  SELECT DBMS_METADATA.GET_DDL('<TYPE>','<NAME>','MGTDAT') FROM dual;
--      (2) ganti referensi  OT_STD_COST_PRODUCTS_MGT  ->  V_GOAPPS_STD_COST_CUR
--          (nama kolom sama; tidak ada perubahan logika lain)
--      (3) compile, cek ALL_ERRORS, uji output lama vs baru (recon §10).
-- ---------------------------------------------------------------------------
-- 7a. Daftar objek yang masih merujuk tabel lama (setelah repoint hanya tersisa proses legacy yang tidak dipanggil lagi)
SELECT name, type FROM all_dependencies
 WHERE referenced_owner = 'MGTDAT' AND referenced_name = 'OT_STD_COST_PRODUCTS_MGT'
 ORDER BY type, name;

-- 7b. Target repoint:
--   O_DGET_FG_ITEM_RATE_MGT  : cursor FG_COST_PER_KG by item/grade/shade -> view. Mode 'E' (220079) kini
--                              hanya muncul bila kombinasi tidak ada di goapps MAUPUN legacy frozen.
--   FUNC_CHIP_WAC_MGT        : cursor C1 (FG_CONVER_COST, FG_CHP_ITEM_CODE, FG_CHP_CON_KG, FG_MS_BATCH_ITEM) -> view.
--                              Fallback C2 ke CST_YARN_CALCULATION_CUR tidak diubah.
--   VIEW_MARGIN_REPORT, VIEW_MARGIN_REPORT_TODAY (+ _DEV*, T-3): outer join std -> view; kolom
--                              FG_CONVER_COST1/2/4/5 dijamin tidak NULL (NVL di view 5c).
--   PRODUCTS_LIST_COST       : opsional -> view (daftar gap resmi = coverage goapps).
--   TIDAK di-repoint         : STD_FG_VALUE_INSERT, STD_FG_COST_UPD_PRD, CHP_WAC_UPD, ODBTRG_FG_STD_COST
--                              (proses legacy; tidak dipanggil lagi untuk yarn maupun MB, dibiarkan ada
--                              sebagai jalur rollback 1 periode).
--   Rollback repoint         : compile ulang DDL backup (1).

-- ---------------------------------------------------------------------------
-- 8. User interface GOAPPS_IF (M-ERP-2)
-- ---------------------------------------------------------------------------
-- CREATE USER GOAPPS_IF IDENTIFIED BY "<secret>";
-- GRANT CREATE SESSION TO GOAPPS_IF;
-- -- ETL (read)
-- GRANT SELECT ON MGTDAT.V_GOAPPS_ADJ_DEMAND      TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.OT_ADJ_HEAD              TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.OT_ADJ_ITEM              TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.OM_ITEM                  TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.OM_GRADE_CODE_1          TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.OM_GRADE_CODE_2          TO GOAPPS_IF;
-- -- seed sekali + parallel run (boleh di-REVOKE setelah go-live)
-- GRANT SELECT ON MGTDAT.OT_STD_COST_PRODUCTS_MGT TO GOAPPS_IF;   -- backfill atribut FG_TYPE/chips/MB item + laporan link §9
-- GRANT SELECT ON MGTDAT.MGT_ITEM_COST_VAL_LOSS   TO GOAPPS_IF;   -- cek seed aturan vs PROD (recon §0)
-- GRANT SELECT ON MGTDAT.IM_VS_STATIC_VALUE       TO GOAPPS_IF;
-- -- duplikat std (single source)
-- GRANT SELECT, INSERT, UPDATE ON MGTDAT.CST_GOAPPS_STD_BATCH TO GOAPPS_IF;   -- UPDATE: status oleh package
-- GRANT SELECT, INSERT         ON MGTDAT.CST_GOAPPS_STD_COST  TO GOAPPS_IF;   -- insert-only
-- GRANT SELECT ON MGTDAT.V_GOAPPS_STD_COST_CUR    TO GOAPPS_IF;
-- GRANT SELECT ON MGTDAT.CST_GOAPPS_ADJ_LOG       TO GOAPPS_IF;
-- GRANT EXECUTE ON MGTDAT.PKG_GOAPPS_ADJ          TO GOAPPS_IF;
-- -- pembaca lain (laporan / query costing di Oracle): hanya SELECT, role ditentukan DBA (T-1)
-- GRANT SELECT ON MGTDAT.CST_GOAPPS_STD_COST      TO <role_laporan>;
-- GRANT SELECT ON MGTDAT.V_GOAPPS_STD_COST_CUR    TO <role_laporan>;
