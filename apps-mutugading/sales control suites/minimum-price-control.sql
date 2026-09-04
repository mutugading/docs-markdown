-- =====================================================================
-- Sales Control Suite - Phase 1: Minimum Price Control
-- Schema : MGTDAT (Oracle 11g)
-- Author : Indra Kurniawan
--
-- URUTAN DEPLOY: A -> B -> C -> D -> E
-- Bagian E (trigger) dipasang PALING AKHIR, setelah UI Laravel siap.
-- Memasang trigger sebelum itu = memblok approval tanpa jalan keluar.
--
-- VERIFIKASI WAJIB sebelum deploy - lihat PRD bagian 4.8.
-- =====================================================================


-- =====================================================================
-- BAGIAN A - TABEL
-- =====================================================================

-- ---------------------------------------------------------------------
-- MGT_APPROVAL_REQUEST (MAR_) - dipakai bersama 3 modul kontrol
-- ---------------------------------------------------------------------
CREATE TABLE MGTDAT.MGT_APPROVAL_REQUEST
(
  MAR_SYS_ID          NUMBER(12)          NOT NULL,
  MAR_REQ_NO          VARCHAR2(30 BYTE)   NOT NULL,
  MAR_REVISION        NUMBER(3)           DEFAULT 0 NOT NULL,
  MAR_CTRL_TYPE       VARCHAR2(12 BYTE)   NOT NULL,
  MAR_STATUS          VARCHAR2(12 BYTE)   DEFAULT 'DRAFT' NOT NULL,
  MAR_CUST_CODE       VARCHAR2(12 BYTE),
  MAR_SOH_SYS_ID      NUMBER(12),
  MAR_TXN_CODE        VARCHAR2(12 BYTE),
  MAR_DOC_NO          NUMBER(10),
  MAR_DOC_DT          DATE,
  MAR_CURR_CODE       VARCHAR2(12 BYTE),
  MAR_AMOUNT          NUMBER,
  MAR_VALID_FROM      DATE,
  MAR_VALID_TO        DATE,
  MAR_REASON          VARCHAR2(2000 BYTE),
  MAR_PRINT_COUNT     NUMBER(4)           DEFAULT 0,
  MAR_PRINT_DT        DATE,
  MAR_PRINT_UID       VARCHAR2(12 BYTE),
  MAR_BOD_DOC_NO      VARCHAR2(60 BYTE),
  MAR_BOD_DOC_DT      DATE,
  MAR_BOD_SIGNER      VARCHAR2(120 BYTE),
  MAR_ATTACH_PATH     VARCHAR2(500 BYTE),
  MAR_ATTACH_HASH     VARCHAR2(64 BYTE),
  MAR_APPR_UID        VARCHAR2(12 BYTE),
  MAR_APPR_DT         DATE,
  MAR_REJ_REASON      VARCHAR2(2000 BYTE),
  MAR_CR_UID          VARCHAR2(12 BYTE)   NOT NULL,
  MAR_CR_DT           DATE                NOT NULL,
  MAR_UPD_UID         VARCHAR2(12 BYTE),
  MAR_UPD_DT          DATE
)
TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTDAT.MGT_APPROVAL_REQUEST.MAR_CTRL_TYPE IS 'PRICELIST | MINPRICE | OVERDUE | CRLIMIT';
COMMENT ON COLUMN MGTDAT.MGT_APPROVAL_REQUEST.MAR_STATUS    IS 'DRAFT | PRINTED | APPROVED | REJECTED | CANCELLED | VOID';
COMMENT ON COLUMN MGTDAT.MGT_APPROVAL_REQUEST.MAR_AMOUNT    IS 'Nominal tambahan - hanya dipakai CTRL_TYPE = CRLIMIT';
COMMENT ON COLUMN MGTDAT.MGT_APPROVAL_REQUEST.MAR_ATTACH_HASH IS 'SHA-256 file scan, untuk deteksi file diganti';

CREATE UNIQUE INDEX MGTDAT.MGT_APPROVAL_REQUEST_PK
  ON MGTDAT.MGT_APPROVAL_REQUEST (MAR_SYS_ID)
  TABLESPACE ORION;

CREATE UNIQUE INDEX MGTDAT.MGT_APPROVAL_REQUEST_UK01
  ON MGTDAT.MGT_APPROVAL_REQUEST (MAR_REQ_NO, MAR_REVISION)
  TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_APPROVAL_REQUEST_NX01
  ON MGTDAT.MGT_APPROVAL_REQUEST (MAR_CTRL_TYPE, MAR_STATUS, MAR_CUST_CODE)
  TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_APPROVAL_REQUEST_NX02
  ON MGTDAT.MGT_APPROVAL_REQUEST (MAR_SOH_SYS_ID)
  TABLESPACE ORION;

ALTER TABLE MGTDAT.MGT_APPROVAL_REQUEST ADD (
  CONSTRAINT MGT_APPROVAL_REQUEST_PK PRIMARY KEY (MAR_SYS_ID)
    USING INDEX MGTDAT.MGT_APPROVAL_REQUEST_PK ENABLE VALIDATE,
  CONSTRAINT MGT_APPROVAL_REQUEST_UK01 UNIQUE (MAR_REQ_NO, MAR_REVISION)
    USING INDEX MGTDAT.MGT_APPROVAL_REQUEST_UK01 ENABLE VALIDATE,
  CONSTRAINT MGT_APPROVAL_REQUEST_C01
    CHECK (MAR_CTRL_TYPE IN ('PRICELIST','MINPRICE','OVERDUE','CRLIMIT')) ENABLE VALIDATE,
  CONSTRAINT MGT_APPROVAL_REQUEST_C02
    CHECK (MAR_STATUS IN ('DRAFT','PRINTED','APPROVED','REJECTED','CANCELLED','VOID')) ENABLE VALIDATE,
  CONSTRAINT MGT_APPROVAL_REQUEST_C03
    CHECK (MAR_STATUS <> 'APPROVED' OR MAR_ATTACH_PATH IS NOT NULL) ENABLE VALIDATE
);


-- ---------------------------------------------------------------------
-- MGT_APPROVAL_LINE (MAL_) - baris pengecualian, Phase 1 = MINPRICE
-- ---------------------------------------------------------------------
CREATE TABLE MGTDAT.MGT_APPROVAL_LINE
(
  MAL_SYS_ID           NUMBER(12)         NOT NULL,
  MAL_MAR_SYS_ID       NUMBER(12)         NOT NULL,
  MAL_SOI_SYS_ID       NUMBER(12),
  MAL_ITEM_CODE        VARCHAR2(20 BYTE),
  MAL_GRADE_CODE_1     VARCHAR2(12 BYTE),
  MAL_GRADE_CODE_2     VARCHAR2(40 BYTE),
  MAL_UOM_CODE         VARCHAR2(12 BYTE),
  MAL_CURR_CODE        VARCHAR2(12 BYTE),
  MAL_APPROVED_RATE    NUMBER,
  MAL_APPROVED_QTY_BU  NUMBER,
  MAL_MIN_PRICE_USD    NUMBER(18,3),
  MAL_RATE_USD         NUMBER(18,6),
  MAL_CR_UID           VARCHAR2(12 BYTE)  NOT NULL,
  MAL_CR_DT            DATE               NOT NULL
)
TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTDAT.MGT_APPROVAL_LINE.MAL_APPROVED_RATE IS
  'Harga yang disetujui dalam currency transaksi. Pengikat: harus sama persis dengan SOI_RATE saat approve.';

CREATE UNIQUE INDEX MGTDAT.MGT_APPROVAL_LINE_PK
  ON MGTDAT.MGT_APPROVAL_LINE (MAL_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_APPROVAL_LINE_NX01
  ON MGTDAT.MGT_APPROVAL_LINE (MAL_SOI_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_APPROVAL_LINE_NX02
  ON MGTDAT.MGT_APPROVAL_LINE (MAL_MAR_SYS_ID) TABLESPACE ORION;

ALTER TABLE MGTDAT.MGT_APPROVAL_LINE ADD (
  CONSTRAINT MGT_APPROVAL_LINE_PK PRIMARY KEY (MAL_SYS_ID)
    USING INDEX MGTDAT.MGT_APPROVAL_LINE_PK ENABLE VALIDATE,
  CONSTRAINT MGT_APPROVAL_LINE_FK01 FOREIGN KEY (MAL_MAR_SYS_ID)
    REFERENCES MGTDAT.MGT_APPROVAL_REQUEST (MAR_SYS_ID) ENABLE VALIDATE
);


-- ---------------------------------------------------------------------
-- MGT_MIN_PRICE (MMP_) - master harga minimum
-- ---------------------------------------------------------------------
CREATE TABLE MGTDAT.MGT_MIN_PRICE
(
  MMP_SYS_ID          NUMBER(12)          NOT NULL,
  MMP_MAR_SYS_ID      NUMBER(12),
  MMP_SCOPE_LEVEL     VARCHAR2(6 BYTE)    NOT NULL,
  MMP_SCOPE_VALUE     VARCHAR2(20 BYTE)   NOT NULL,
  MMP_GRADE_CODE_1    VARCHAR2(12 BYTE)   DEFAULT '*' NOT NULL,
  MMP_GRADE_CODE_2    VARCHAR2(40 BYTE)   DEFAULT '*' NOT NULL,
  MMP_UOM_CODE        VARCHAR2(12 BYTE)   NOT NULL,
  MMP_MIN_PRICE_USD   NUMBER(18,3)        NOT NULL,
  MMP_TOLERANCE_PCT   NUMBER(5,2)         DEFAULT 0,
  MMP_VALID_FROM      DATE                NOT NULL,
  MMP_VALID_TO        DATE,
  MMP_STATUS          VARCHAR2(10 BYTE)   DEFAULT 'DRAFT' NOT NULL,
  MMP_REMARKS         VARCHAR2(2000 BYTE),
  MMP_CR_UID          VARCHAR2(12 BYTE)   NOT NULL,
  MMP_CR_DT           DATE                NOT NULL,
  MMP_UPD_UID         VARCHAR2(12 BYTE),
  MMP_UPD_DT          DATE
)
TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTDAT.MGT_MIN_PRICE.MMP_SCOPE_LEVEL  IS 'ITEM | GROUP | ALL';
COMMENT ON COLUMN MGTDAT.MGT_MIN_PRICE.MMP_SCOPE_VALUE  IS 'Kode item / kode group / * untuk level ALL';
COMMENT ON COLUMN MGTDAT.MGT_MIN_PRICE.MMP_GRADE_CODE_1 IS 'Nilai grade atau * (semua)';
COMMENT ON COLUMN MGTDAT.MGT_MIN_PRICE.MMP_GRADE_CODE_2 IS 'Nilai grade atau * (semua)';
COMMENT ON COLUMN MGTDAT.MGT_MIN_PRICE.MMP_MIN_PRICE_USD IS
  'Harga minimum USD per MMP_UOM_CODE. 3 desimal, samakan dengan batas ODBTRG_SOI_DECML_DIGIT_MGT.';

CREATE UNIQUE INDEX MGTDAT.MGT_MIN_PRICE_PK
  ON MGTDAT.MGT_MIN_PRICE (MMP_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_MIN_PRICE_NX01
  ON MGTDAT.MGT_MIN_PRICE (MMP_STATUS, MMP_SCOPE_LEVEL, MMP_SCOPE_VALUE)
  TABLESPACE ORION;

ALTER TABLE MGTDAT.MGT_MIN_PRICE ADD (
  CONSTRAINT MGT_MIN_PRICE_PK PRIMARY KEY (MMP_SYS_ID)
    USING INDEX MGTDAT.MGT_MIN_PRICE_PK ENABLE VALIDATE,
  CONSTRAINT MGT_MIN_PRICE_C01
    CHECK (MMP_SCOPE_LEVEL IN ('ITEM','GROUP','ALL')) ENABLE VALIDATE,
  CONSTRAINT MGT_MIN_PRICE_C02
    CHECK (MMP_STATUS IN ('DRAFT','APPROVED','VOID')) ENABLE VALIDATE,
  CONSTRAINT MGT_MIN_PRICE_C03
    CHECK (MMP_MIN_PRICE_USD > 0) ENABLE VALIDATE,
  CONSTRAINT MGT_MIN_PRICE_C04
    CHECK (MMP_VALID_TO IS NULL OR MMP_VALID_TO >= MMP_VALID_FROM) ENABLE VALIDATE,
  CONSTRAINT MGT_MIN_PRICE_FK01 FOREIGN KEY (MMP_MAR_SYS_ID)
    REFERENCES MGTDAT.MGT_APPROVAL_REQUEST (MAR_SYS_ID) ENABLE VALIDATE
);

-- CATATAN: periode tumpang tindih untuk kunci yang sama TIDAK bisa
-- ditegakkan constraint di Oracle 11g. Validasi di Laravel saat simpan.


-- ---------------------------------------------------------------------
-- MGT_PRICE_CHECK_LOG (MPCL_) - bukti audit setiap pemeriksaan
-- ---------------------------------------------------------------------
CREATE TABLE MGTDAT.MGT_PRICE_CHECK_LOG
(
  MPCL_SYS_ID          NUMBER(12)         NOT NULL,
  MPCL_SOH_SYS_ID      NUMBER(12),
  MPCL_SOI_SYS_ID      NUMBER(12),
  MPCL_TXN_CODE        VARCHAR2(12 BYTE),
  MPCL_DOC_NO          NUMBER(10),
  MPCL_DOC_DT          DATE,
  MPCL_ITEM_CODE       VARCHAR2(20 BYTE),
  MPCL_GRADE_CODE_1    VARCHAR2(12 BYTE),
  MPCL_GRADE_CODE_2    VARCHAR2(40 BYTE),
  MPCL_UOM_CODE        VARCHAR2(12 BYTE),
  MPCL_QTY_BU          NUMBER,
  MPCL_CURR_CODE       VARCHAR2(12 BYTE),
  MPCL_RATE            NUMBER,
  MPCL_EXG_DIVISOR     NUMBER,
  MPCL_EXG_RATE_DT     DATE,
  MPCL_EXG_RATE_SRC    VARCHAR2(10 BYTE),
  MPCL_RATE_USD        NUMBER(18,6),
  MPCL_NET_RATE_USD    NUMBER(18,6),
  MPCL_HAS_DISCOUNT    VARCHAR2(1 BYTE),
  MPCL_MMP_SYS_ID      NUMBER(12),
  MPCL_MIN_PRICE_USD   NUMBER(18,3),
  MPCL_RESULT          VARCHAR2(12 BYTE),
  MPCL_MAR_SYS_ID      NUMBER(12),
  MPCL_APPR_UID        VARCHAR2(12 BYTE),
  MPCL_CR_DT           DATE               NOT NULL
)
TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 4M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTDAT.MGT_PRICE_CHECK_LOG.MPCL_EXG_RATE_SRC IS 'BCA | BCA_PREV | ORION | USD';
COMMENT ON COLUMN MGTDAT.MGT_PRICE_CHECK_LOG.MPCL_RESULT       IS 'PASS | PASS_NORULE | OVERRIDE | INHERIT | FAIL';
COMMENT ON COLUMN MGTDAT.MGT_PRICE_CHECK_LOG.MPCL_NET_RATE_USD IS
  'Monitoring saja - validasi memakai gross. Untuk review basis validasi nanti.';

CREATE UNIQUE INDEX MGTDAT.MGT_PRICE_CHECK_LOG_PK
  ON MGTDAT.MGT_PRICE_CHECK_LOG (MPCL_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_PRICE_CHECK_LOG_NX01
  ON MGTDAT.MGT_PRICE_CHECK_LOG (MPCL_SOH_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTDAT.MGT_PRICE_CHECK_LOG_NX02
  ON MGTDAT.MGT_PRICE_CHECK_LOG (MPCL_CR_DT, MPCL_RESULT) TABLESPACE ORION;

ALTER TABLE MGTDAT.MGT_PRICE_CHECK_LOG ADD (
  CONSTRAINT MGT_PRICE_CHECK_LOG_PK PRIMARY KEY (MPCL_SYS_ID)
    USING INDEX MGTDAT.MGT_PRICE_CHECK_LOG_PK ENABLE VALIDATE
);


-- =====================================================================
-- BAGIAN B - SEQUENCE
-- =====================================================================

CREATE SEQUENCE MGTDAT.MGT_APPROVAL_REQUEST_SEQ
  START WITH 1 MINVALUE 1 MAXVALUE 999999999999 NOCYCLE CACHE 20 NOORDER;

CREATE SEQUENCE MGTDAT.MGT_APPROVAL_LINE_SEQ
  START WITH 1 MINVALUE 1 MAXVALUE 999999999999 NOCYCLE CACHE 20 NOORDER;

CREATE SEQUENCE MGTDAT.MGT_MIN_PRICE_SEQ
  START WITH 1 MINVALUE 1 MAXVALUE 999999999999 NOCYCLE CACHE 20 NOORDER;

CREATE SEQUENCE MGTDAT.MGT_PRICE_CHECK_LOG_SEQ
  START WITH 1 MINVALUE 1 MAXVALUE 999999999999 NOCYCLE CACHE 50 NOORDER;


-- =====================================================================
-- BAGIAN C - KONFIGURASI DAN MESSAGE REGISTRY
-- =====================================================================

-- Daftar TXN_CODE yang masuk scope kontrol harga minimum.
-- Mengikuti pola TOL_SO_MGT yang dipakai ODBTRG_SOI_TOL_MGT.
-- VERIFIKASI struktur IM_VS_STATIC_VALUE sebelum jalan.
INSERT INTO MGTDAT.IM_VS_STATIC_VALUE (VSSV_VS_CODE, VSSV_CODE, VSSV_FRZ_FLAG_NUM)
  VALUES ('MINPRC_MGT', 'ESC', 2);
INSERT INTO MGTDAT.IM_VS_STATIC_VALUE (VSSV_VS_CODE, VSSV_CODE, VSSV_FRZ_FLAG_NUM)
  VALUES ('MINPRC_MGT', 'LSC', 2);
INSERT INTO MGTDAT.IM_VS_STATIC_VALUE (VSSV_VS_CODE, VSSV_CODE, VSSV_FRZ_FLAG_NUM)
  VALUES ('MINPRC_MGT', 'STA', 2);

-- Message registry. VERIFIKASI nama kolom dengan DESC IM_APP_ERROR_MESSAGE.
INSERT INTO MGTDAT.IM_APP_ERROR_MESSAGE (AEM_APP_CODE, AEM_ERROR_CODE, AEM_MESSAGE_ENG, AEM_MESSAGE_FOR)
  VALUES ('CUST', 1012110,
          'Harga di bawah minimum price. Baris: &1. Ajukan approval pengecualian sebelum approve dokumen.',
          'Harga di bawah minimum price. Baris: &1. Ajukan approval pengecualian sebelum approve dokumen.');

INSERT INTO MGTDAT.IM_APP_ERROR_MESSAGE (AEM_APP_CODE, AEM_ERROR_CODE, AEM_MESSAGE_ENG, AEM_MESSAGE_FOR)
  VALUES ('CUST', 1012111,
          'Kurs untuk tanggal dokumen tidak ditemukan (&1 - &2). Hubungi Finance untuk input kurs.',
          'Kurs untuk tanggal dokumen tidak ditemukan (&1 - &2). Hubungi Finance untuk input kurs.');

INSERT INTO MGTDAT.IM_APP_ERROR_MESSAGE (AEM_APP_CODE, AEM_ERROR_CODE, AEM_MESSAGE_ENG, AEM_MESSAGE_FOR)
  VALUES ('CUST', 1012112,
          'Currency &1 tidak didukung kontrol minimum price. Hanya USD dan IDR.',
          'Currency &1 tidak didukung kontrol minimum price. Hanya USD dan IDR.');

COMMIT;


-- =====================================================================
-- BAGIAN D - PACKAGE
-- =====================================================================

CREATE OR REPLACE PACKAGE MGTDAT.PKG_MGT_PRICE_CTRL AS

  -- Divisor selalu dinormalkan ke IDR per USD (angka ribuan).
  -- USD_rate = SOI_RATE / divisor.  Untuk USD, divisor = 1.
  PROCEDURE P_GET_USD_DIVISOR (
      p_curr_code  IN  VARCHAR2,
      p_txn_dt     IN  DATE,
      o_divisor    OUT NUMBER,
      o_rate_dt    OUT DATE,
      o_source     OUT VARCHAR2);

  FUNCTION F_GET_ITEM_GROUP (p_item_code IN VARCHAR2) RETURN VARCHAR2;

  PROCEDURE P_GET_MIN_PRICE (
      p_item_code  IN  VARCHAR2,
      p_grade_1    IN  VARCHAR2,
      p_grade_2    IN  VARCHAR2,
      p_uom_code   IN  VARCHAR2,
      p_txn_dt     IN  DATE,
      o_mmp_sys_id OUT NUMBER,
      o_min_price  OUT NUMBER,
      o_tolerance  OUT NUMBER);

  FUNCTION F_GET_OVERRIDE (
      p_soi_sys_id IN NUMBER,
      p_rate       IN NUMBER,
      p_qty_bu     IN NUMBER) RETURN NUMBER;

  PROCEDURE P_VALIDATE_SO (
      p_soh_sys_id IN NUMBER,
      p_txn_code   IN VARCHAR2,
      p_doc_no     IN NUMBER,
      p_doc_dt     IN DATE,
      p_curr_code  IN VARCHAR2,
      p_ref_sys_id IN NUMBER,
      p_appr_uid   IN VARCHAR2);

END PKG_MGT_PRICE_CTRL;
/


CREATE OR REPLACE PACKAGE BODY MGTDAT.PKG_MGT_PRICE_CTRL AS

  C_MAX_FALLBACK_DAYS CONSTANT NUMBER := 7;
  C_DIV_MIN           CONSTANT NUMBER := 1000;
  C_DIV_MAX           CONSTANT NUMBER := 100000;

  -- -------------------------------------------------------------------
  -- Kurs. TIDAK memakai mgt_get_exg_rate_bca / mgt_get_exg_rate apa adanya:
  -- keduanya mengembalikan 1 kalau tidak ketemu, yang untuk validasi floor
  -- berarti semua harga IDR lolos tanpa error. Di sini fail closed.
  -- -------------------------------------------------------------------
  PROCEDURE P_GET_USD_DIVISOR (
      p_curr_code  IN  VARCHAR2,
      p_txn_dt     IN  DATE,
      o_divisor    OUT NUMBER,
      o_rate_dt    OUT DATE,
      o_source     OUT VARCHAR2)
  IS
    v_cer NUMBER;
  BEGIN
    o_divisor := NULL;
    o_rate_dt := NULL;
    o_source  := NULL;

    IF p_curr_code = 'USD' THEN
      o_divisor := 1;
      o_rate_dt := p_txn_dt;
      o_source  := 'USD';
      RETURN;
    END IF;

    IF p_curr_code <> 'IDR' THEN
      RAISE_APPLICATION('CUST', 1012112, p_curr_code, '', '', '', '', '', '', '');
    END IF;

    -- 1. Kurs BCA pada tanggal dokumen
    BEGIN
      SELECT MERS_VALUE, TO_DATE(MERS_DATE,'DD/MM/RR')
        INTO o_divisor, o_rate_dt
        FROM MGTAPPS.MST_EXC_RATE_SAL
       WHERE MERS_TERMS = 'LC_0_DAYS'
         AND MERS_TYPE  = 'EXCHANGE'
         AND NVL(MERS_VALUE,0) <> 0
         AND TO_DATE(MERS_DATE,'DD/MM/RR') = TRUNC(p_txn_dt);
      o_source := 'BCA';
    EXCEPTION
      WHEN NO_DATA_FOUND THEN o_divisor := NULL;
      WHEN TOO_MANY_ROWS THEN o_divisor := NULL;
    END;

    -- 2. Kurs BCA terakhir sebelum tanggal dokumen, maksimal mundur 7 hari.
    --    Beda dengan fungsi lama yang memakai < tanggal - 1 (melewati H-1).
    IF o_divisor IS NULL THEN
      BEGIN
        SELECT MERS_VALUE, MERS_DT
          INTO o_divisor, o_rate_dt
          FROM (SELECT MERS_VALUE, TO_DATE(MERS_DATE,'DD/MM/RR') MERS_DT
                  FROM MGTAPPS.MST_EXC_RATE_SAL
                 WHERE MERS_TERMS = 'LC_0_DAYS'
                   AND MERS_TYPE  = 'EXCHANGE'
                   AND NVL(MERS_VALUE,0) <> 0
                   AND TO_DATE(MERS_DATE,'DD/MM/RR') <  TRUNC(p_txn_dt)
                   AND TO_DATE(MERS_DATE,'DD/MM/RR') >= TRUNC(p_txn_dt) - C_MAX_FALLBACK_DAYS
                 ORDER BY TO_DATE(MERS_DATE,'DD/MM/RR') DESC)
         WHERE ROWNUM = 1;
        o_source := 'BCA_PREV';
      EXCEPTION
        WHEN NO_DATA_FOUND THEN o_divisor := NULL;
      END;
    END IF;

    -- 3. Fallback kurs Orion. Query langsung, TIDAK lewat mgt_get_exg_rate:
    --    fallback di fungsi itu tidak memfilter currency pair di subquery.
    IF o_divisor IS NULL THEN
      BEGIN
        SELECT CER_EXG_RATE, CER_EFF_FRM_DT
          INTO v_cer, o_rate_dt
          FROM (SELECT CER_EXG_RATE, CER_EFF_FRM_DT
                  FROM MGTDAT.FM_EXCHANGE_RATE
                 WHERE CER_CONV_FM_CURR_CODE = 'IDR'
                   AND CER_CONV_TO_CURR_CODE = 'USD'
                   AND CER_EXG_RATE_TYPE     = 'B'
                   AND NVL(CER_EXG_RATE,0)  <> 0
                   AND CER_EFF_FRM_DT <= TRUNC(p_txn_dt)
                   AND CER_EFF_TO_DT   >= TRUNC(p_txn_dt)
                 ORDER BY CER_EFF_FRM_DT DESC)
         WHERE ROWNUM = 1;

        -- Orion menyimpan 1/kurs (mis. 0.000058). Balik ke IDR per USD.
        o_divisor := 1 / v_cer;
        o_source  := 'ORION';
      EXCEPTION
        WHEN NO_DATA_FOUND THEN o_divisor := NULL;
      END;
    END IF;

    IF o_divisor IS NULL THEN
      RAISE_APPLICATION('CUST', 1012111, p_curr_code,
                        TO_CHAR(p_txn_dt,'DD-MON-YYYY'), '', '', '', '', '', '');
    END IF;

    -- Sanity check. Kalau asumsi arah kurs salah, gagalnya di sini -
    -- bukan diam-diam meloloskan harga.
    IF o_divisor < C_DIV_MIN OR o_divisor > C_DIV_MAX THEN
      RAISE_APPLICATION('CUST', 1012111, p_curr_code || ' divisor=' || TO_CHAR(o_divisor),
                        TO_CHAR(p_txn_dt,'DD-MON-YYYY'), '', '', '', '', '', '');
    END IF;
  END P_GET_USD_DIVISOR;


  -- -------------------------------------------------------------------
  -- Grouping FG belum didefinisikan di OM_ITEM.
  -- Resolver sudah siap level GROUP; isi fungsi ini kalau kolomnya ada.
  -- -------------------------------------------------------------------
  FUNCTION F_GET_ITEM_GROUP (p_item_code IN VARCHAR2) RETURN VARCHAR2
  IS
  BEGIN
    RETURN NULL;
  END F_GET_ITEM_GROUP;


  -- -------------------------------------------------------------------
  -- Resolusi wildcard: ambil rule yang paling spesifik.
  -- GRADE_2 bobot 2, GRADE_1 bobot 1 - grade_2 menang kalau keduanya ada.
  -- -------------------------------------------------------------------
  PROCEDURE P_GET_MIN_PRICE (
      p_item_code  IN  VARCHAR2,
      p_grade_1    IN  VARCHAR2,
      p_grade_2    IN  VARCHAR2,
      p_uom_code   IN  VARCHAR2,
      p_txn_dt     IN  DATE,
      o_mmp_sys_id OUT NUMBER,
      o_min_price  OUT NUMBER,
      o_tolerance  OUT NUMBER)
  IS
    v_group VARCHAR2(20);
  BEGIN
    o_mmp_sys_id := NULL;
    o_min_price  := NULL;
    o_tolerance  := 0;

    v_group := F_GET_ITEM_GROUP(p_item_code);

    BEGIN
      SELECT MMP_SYS_ID, MMP_MIN_PRICE_USD, NVL(MMP_TOLERANCE_PCT,0)
        INTO o_mmp_sys_id, o_min_price, o_tolerance
        FROM (SELECT MMP_SYS_ID, MMP_MIN_PRICE_USD, MMP_TOLERANCE_PCT
                FROM MGTDAT.MGT_MIN_PRICE
               WHERE MMP_STATUS = 'APPROVED'
                 AND TRUNC(p_txn_dt) >= MMP_VALID_FROM
                 AND TRUNC(p_txn_dt) <= NVL(MMP_VALID_TO, TO_DATE('31-12-2099','DD-MM-YYYY'))
                 AND MMP_UOM_CODE = p_uom_code
                 AND MMP_GRADE_CODE_1 IN ('*', p_grade_1)
                 AND MMP_GRADE_CODE_2 IN ('*', p_grade_2)
                 AND (   (MMP_SCOPE_LEVEL = 'ITEM'  AND MMP_SCOPE_VALUE = p_item_code)
                      OR (MMP_SCOPE_LEVEL = 'GROUP' AND MMP_SCOPE_VALUE = v_group)
                      OR (MMP_SCOPE_LEVEL = 'ALL'))
               ORDER BY CASE MMP_SCOPE_LEVEL
                          WHEN 'ITEM'  THEN 100
                          WHEN 'GROUP' THEN 50
                          ELSE 0 END
                      + CASE WHEN MMP_GRADE_CODE_2 <> '*' THEN 2 ELSE 0 END
                      + CASE WHEN MMP_GRADE_CODE_1 <> '*' THEN 1 ELSE 0 END DESC,
                        MMP_VALID_FROM DESC)
       WHERE ROWNUM = 1;
    EXCEPTION
      WHEN NO_DATA_FOUND THEN
        o_mmp_sys_id := NULL;
        o_min_price  := NULL;
    END;
  END P_GET_MIN_PRICE;


  -- -------------------------------------------------------------------
  -- Override berlaku hanya kalau rate SAMA PERSIS dan qty tidak melebihi
  -- yang disetujui. Turunkan harga atau naikkan qty setelah approve =
  -- override tidak cocok lagi, harus request ulang.
  -- -------------------------------------------------------------------
  FUNCTION F_GET_OVERRIDE (
      p_soi_sys_id IN NUMBER,
      p_rate       IN NUMBER,
      p_qty_bu     IN NUMBER) RETURN NUMBER
  IS
    v_mar_sys_id NUMBER;
  BEGIN
    SELECT MAR_SYS_ID
      INTO v_mar_sys_id
      FROM (SELECT MAR_SYS_ID
              FROM MGTDAT.MGT_APPROVAL_LINE, MGTDAT.MGT_APPROVAL_REQUEST
             WHERE MAL_MAR_SYS_ID = MAR_SYS_ID
               AND MAR_CTRL_TYPE  = 'MINPRICE'
               AND MAR_STATUS     = 'APPROVED'
               AND TRUNC(SYSDATE) <= NVL(MAR_VALID_TO, TO_DATE('31-12-2099','DD-MM-YYYY'))
               AND MAL_SOI_SYS_ID = p_soi_sys_id
               AND MAL_APPROVED_RATE = p_rate
               AND NVL(MAL_APPROVED_QTY_BU,0) >= NVL(p_qty_bu,0)
             ORDER BY MAR_APPR_DT DESC)
     WHERE ROWNUM = 1;

    RETURN v_mar_sys_id;
  EXCEPTION
    WHEN NO_DATA_FOUND THEN RETURN NULL;
  END F_GET_OVERRIDE;


  -- -------------------------------------------------------------------
  -- Validasi seluruh dokumen. Semua pelanggaran dikumpulkan, satu error.
  -- -------------------------------------------------------------------
  PROCEDURE P_VALIDATE_SO (
      p_soh_sys_id IN NUMBER,
      p_txn_code   IN VARCHAR2,
      p_doc_no     IN NUMBER,
      p_doc_dt     IN DATE,
      p_curr_code  IN VARCHAR2,
      p_ref_sys_id IN NUMBER,
      p_appr_uid   IN VARCHAR2)
  IS
    CURSOR c_scope IS
      SELECT 'X' FROM MGTDAT.IM_VS_STATIC_VALUE
       WHERE VSSV_VS_CODE = 'MINPRC_MGT'
         AND VSSV_CODE    = p_txn_code;

    CURSOR c_item IS
      SELECT SOI_SYS_ID, SOI_SOI_SYS_ID, SOI_ITEM_CODE,
             SOI_GRADE_CODE_1, SOI_GRADE_CODE_2, SOI_UOM_CODE,
             NVL(SOI_RATE,0) SOI_RATE, NVL(SOI_QTY_BU,0) SOI_QTY_BU,
             NVL(SOI_DISC_PERC,0) SOI_DISC_PERC
        FROM MGTDAT.OT_SO_ITEM
       WHERE SOI_SOH_SYS_ID = p_soh_sys_id
         AND NVL(SOI_SHORT_CLO_STATUS,2) = 2
         AND NVL(SOI_FOC_YN,'N') <> 'Y'
         AND NVL(SOI_RATE,0) > 0
       ORDER BY SOI_SYS_ID;

    v_dummy       VARCHAR2(1);
    v_divisor     NUMBER;
    v_rate_dt     DATE;
    v_source      VARCHAR2(10);
    v_mmp_sys_id  NUMBER;
    v_min_price   NUMBER;
    v_tolerance   NUMBER;
    v_rate_usd    NUMBER;
    v_net_usd     NUMBER;
    v_floor       NUMBER;
    v_mar_sys_id  NUMBER;
    v_result      VARCHAR2(12);
    v_has_disc    VARCHAR2(1);
    v_msg         VARCHAR2(1800) := NULL;
    v_cnt         NUMBER := 0;
  BEGIN
    -- Hanya TXN_CODE yang terdaftar
    IF c_scope%ISOPEN THEN CLOSE c_scope; END IF;
    OPEN c_scope; FETCH c_scope INTO v_dummy; CLOSE c_scope;
    IF NVL(v_dummy,'N') <> 'X' THEN
      RETURN;
    END IF;

    -- Kurs diambil sekali per dokumen, bukan per baris
    P_GET_USD_DIVISOR(p_curr_code, p_doc_dt, v_divisor, v_rate_dt, v_source);

    FOR r IN c_item LOOP

      P_GET_MIN_PRICE(r.SOI_ITEM_CODE, r.SOI_GRADE_CODE_1, r.SOI_GRADE_CODE_2,
                      r.SOI_UOM_CODE, p_doc_dt,
                      v_mmp_sys_id, v_min_price, v_tolerance);

      v_rate_usd := ROUND(r.SOI_RATE / v_divisor, 6);
      v_net_usd  := ROUND((r.SOI_RATE * (1 - r.SOI_DISC_PERC/100)) / v_divisor, 6);
      v_mar_sys_id := NULL;

      -- Monitoring keberadaan baris discount (TED type 2)
      BEGIN
        SELECT 'Y' INTO v_has_disc FROM DUAL
         WHERE EXISTS (SELECT 'X' FROM MGTDAT.OT_SO_ITEM_TED
                        WHERE ITED_H_SYS_ID = p_soh_sys_id
                          AND ITED_TED_TYPE_NUM = 2);
      EXCEPTION
        WHEN NO_DATA_FOUND THEN v_has_disc := 'N';
      END;

      IF v_min_price IS NULL THEN
        v_result := 'PASS_NORULE';
      ELSE
        v_floor := v_min_price * (1 - NVL(v_tolerance,0)/100);

        IF v_rate_usd >= v_floor THEN
          v_result := 'PASS';
        ELSE
          v_mar_sys_id := F_GET_OVERRIDE(r.SOI_SYS_ID, r.SOI_RATE, r.SOI_QTY_BU);

          IF v_mar_sys_id IS NOT NULL THEN
            v_result := 'OVERRIDE';
          ELSIF p_ref_sys_id IS NOT NULL AND r.SOI_SOI_SYS_ID IS NOT NULL THEN
            -- Warisan dari dokumen induk (STA -> ESC).
            -- VERIFIKASI: pemetaan baris diasumsikan lewat SOI_SOI_SYS_ID.
            v_mar_sys_id := F_GET_OVERRIDE(r.SOI_SOI_SYS_ID, r.SOI_RATE, r.SOI_QTY_BU);
            IF v_mar_sys_id IS NOT NULL THEN
              v_result := 'INHERIT';
            ELSE
              v_result := 'FAIL';
            END IF;
          ELSE
            v_result := 'FAIL';
          END IF;
        END IF;
      END IF;

      IF v_result = 'FAIL' THEN
        v_cnt := v_cnt + 1;
        IF LENGTH(NVL(v_msg,'')) < 1500 THEN
          v_msg := v_msg
                || CASE WHEN v_msg IS NULL THEN '' ELSE '; ' END
                || r.SOI_ITEM_CODE || '/' || r.SOI_GRADE_CODE_1 || '/' || r.SOI_GRADE_CODE_2
                || ' USD ' || TO_CHAR(v_rate_usd,'FM9999990.000')
                || ' < min ' || TO_CHAR(v_min_price,'FM9999990.000');
        END IF;
      END IF;

      INSERT INTO MGTDAT.MGT_PRICE_CHECK_LOG (
        MPCL_SYS_ID, MPCL_SOH_SYS_ID, MPCL_SOI_SYS_ID, MPCL_TXN_CODE,
        MPCL_DOC_NO, MPCL_DOC_DT, MPCL_ITEM_CODE, MPCL_GRADE_CODE_1,
        MPCL_GRADE_CODE_2, MPCL_UOM_CODE, MPCL_QTY_BU, MPCL_CURR_CODE,
        MPCL_RATE, MPCL_EXG_DIVISOR, MPCL_EXG_RATE_DT, MPCL_EXG_RATE_SRC,
        MPCL_RATE_USD, MPCL_NET_RATE_USD, MPCL_HAS_DISCOUNT,
        MPCL_MMP_SYS_ID, MPCL_MIN_PRICE_USD, MPCL_RESULT, MPCL_MAR_SYS_ID,
        MPCL_APPR_UID, MPCL_CR_DT)
      VALUES (
        MGTDAT.MGT_PRICE_CHECK_LOG_SEQ.NEXTVAL, p_soh_sys_id, r.SOI_SYS_ID, p_txn_code,
        p_doc_no, p_doc_dt, r.SOI_ITEM_CODE, r.SOI_GRADE_CODE_1,
        r.SOI_GRADE_CODE_2, r.SOI_UOM_CODE, r.SOI_QTY_BU, p_curr_code,
        r.SOI_RATE, v_divisor, v_rate_dt, v_source,
        v_rate_usd, v_net_usd, v_has_disc,
        v_mmp_sys_id, v_min_price, v_result, v_mar_sys_id,
        p_appr_uid, SYSDATE);

    END LOOP;

    IF v_cnt > 0 THEN
      RAISE_APPLICATION('CUST', 1012110, v_msg, '', '', '', '', '', '', '');
    END IF;
  END P_VALIDATE_SO;

END PKG_MGT_PRICE_CTRL;
/
SHOW ERRORS;


-- =====================================================================
-- BAGIAN E - TRIGGER
-- PASANG PALING AKHIR, setelah UI Laravel siap dan UAT selesai.
-- =====================================================================

CREATE OR REPLACE TRIGGER MGTDAT.ODBTRG_MIN_PRICE_MGT
   BEFORE UPDATE ON MGTDAT.OT_SO_HEAD
   FOR EACH ROW
   WHEN (NVL(NEW.SOH_APPR_STATUS,0) = 3 AND NVL(OLD.SOH_APPR_STATUS,0) <> 3)
BEGIN
   -- BEFORE, bukan AFTER: error muncul sebelum trigger side-effect lain
   -- jalan (ODBTRG_SO_CBD_MGT, ODBTRG_QRCODE_SO, summary WMS).
   -- Baca OT_SO_ITEM dari trigger di OT_SO_HEAD - tabel berbeda,
   -- tidak kena ORA-04091.
   MGTDAT.PKG_MGT_PRICE_CTRL.P_VALIDATE_SO(
      p_soh_sys_id => :NEW.SOH_SYS_ID,
      p_txn_code   => :NEW.SOH_TXN_CODE,
      p_doc_no     => :NEW.SOH_NO,
      p_doc_dt     => :NEW.SOH_DT,
      p_curr_code  => :NEW.SOH_CURR_CODE,
      p_ref_sys_id => :NEW.SOH_REF_SYS_ID,
      p_appr_uid   => :NEW.SOH_APPR_UID);
END;
/
SHOW ERRORS;


-- =====================================================================
-- BAGIAN F - GRANT (sesuaikan nama user Laravel)
-- =====================================================================
-- GRANT SELECT, INSERT, UPDATE ON MGTDAT.MGT_APPROVAL_REQUEST  TO <user_laravel>;
-- GRANT SELECT, INSERT, UPDATE ON MGTDAT.MGT_APPROVAL_LINE     TO <user_laravel>;
-- GRANT SELECT, INSERT, UPDATE ON MGTDAT.MGT_MIN_PRICE         TO <user_laravel>;
-- GRANT SELECT                 ON MGTDAT.MGT_PRICE_CHECK_LOG   TO <user_laravel>;
-- GRANT EXECUTE                ON MGTDAT.PKG_MGT_PRICE_CTRL    TO <user_laravel>;
-- GRANT SELECT ON MGTDAT.MGT_APPROVAL_REQUEST_SEQ  TO <user_laravel>;
-- GRANT SELECT ON MGTDAT.MGT_APPROVAL_LINE_SEQ     TO <user_laravel>;
-- GRANT SELECT ON MGTDAT.MGT_MIN_PRICE_SEQ         TO <user_laravel>;


-- =====================================================================
-- BAGIAN G - SMOKE TEST (jalankan di TEST, bukan produksi)
-- =====================================================================
/*
-- G1. Cek arah kurs. Divisor harus angka ribuan (mis. 17250).
DECLARE
  v_div NUMBER; v_dt DATE; v_src VARCHAR2(10);
BEGIN
  MGTDAT.PKG_MGT_PRICE_CTRL.P_GET_USD_DIVISOR('IDR', TRUNC(SYSDATE), v_div, v_dt, v_src);
  DBMS_OUTPUT.PUT_LINE('divisor=' || v_div || ' dt=' || v_dt || ' src=' || v_src);
END;
/

-- G2. Insert rule contoh, lalu cek resolusi.
INSERT INTO MGTDAT.MGT_MIN_PRICE (
  MMP_SYS_ID, MMP_SCOPE_LEVEL, MMP_SCOPE_VALUE, MMP_GRADE_CODE_1, MMP_GRADE_CODE_2,
  MMP_UOM_CODE, MMP_MIN_PRICE_USD, MMP_TOLERANCE_PCT, MMP_VALID_FROM,
  MMP_STATUS, MMP_CR_UID, MMP_CR_DT)
VALUES (
  MGTDAT.MGT_MIN_PRICE_SEQ.NEXTVAL, 'ITEM', '<item_code>', '*', '<grade2>',
  'KG', 1.250, 0, TRUNC(SYSDATE) - 30,
  'APPROVED', 'TEST', SYSDATE);

-- G3. Validasi satu dokumen tanpa mengubah statusnya.
--     Harus raise kalau ada baris di bawah minimum.
BEGIN
  MGTDAT.PKG_MGT_PRICE_CTRL.P_VALIDATE_SO(
    <soh_sys_id>, '<txn_code>', <no>, <dt>, '<curr>', NULL, 'TEST');
END;
/

-- G4. Lihat hasilnya.
SELECT MPCL_ITEM_CODE, MPCL_RATE, MPCL_EXG_DIVISOR, MPCL_EXG_RATE_SRC,
       MPCL_RATE_USD, MPCL_MIN_PRICE_USD, MPCL_RESULT
  FROM MGTDAT.MGT_PRICE_CHECK_LOG
 WHERE MPCL_SOH_SYS_ID = <soh_sys_id>
 ORDER BY MPCL_SYS_ID;

ROLLBACK;
*/
