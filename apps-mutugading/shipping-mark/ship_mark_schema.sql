-- =============================================================================
-- SHIPPING MARK MODULE — schema DDL (Oracle 11g)  draft v0.1
-- Target schema: MGTHRIS (config app data)  |  reads Orion data from MGTDAT
-- Convention: column-prefix per table (SMT_ / SMTL_ / SMCM_ / SMPL_)
-- =============================================================================
--
-- FIELD SOURCE RESOLUTION (setelah konfirmasi 11 Jul 2026)
-- -----------------------------------------------------------------------------
-- Sumber data = 2 tabel di MGTDAT, dijoin lewat scan code:
--     OT_SO_SCAN_MGT  ss   (primary; sama dengan query pallet)
--     OT_WMS_PACK_TABLE_ALTHARA prd  (enrichment)
--     JOIN: ss.SS_SCAN_CODE = prd.PRD_SCAN_CODE
--
--   KEY               TYPE      SUMBER / ATURAN
--   ITEM              SYSTEM    soim_item_code / item desc
--   LOT_NO / MERGE    SYSTEM    ss.SS_BATCH_NO  (atau prd.PRD_MERGE_NO)
--   NET_WT            SYSTEM    ss.SS_QTY            (SUM di grain kasar)
--   GROSS_WT          SYSTEM    ss.SS_GROSS_WGT      (SUM di grain kasar)
--   TARE              SYSTEM    prd.PRD_TARE_WT      (fallback: GROSS-NET)
--   PALLET_NO         SYSTEM    ss.SS_PALLET_NO diformat + n/total
--   CARTON_NO         SYSTEM    ss.SS_CART_NO + n/total
--   TUBE_COLOR        SYSTEM    prd.PRD_COLOR
--   BOBBIN_QTY        SYSTEM    prd.PRD_SUB_UNITS   -- ⚠ CONFIRM: user bilang PRD_QTY,
--                                                   -- tapi ada CHECK PRD_QTY = PRD_NET_WT
--                                                   -- (jadi PRD_QTY = berat, bukan count).
--                                                   -- PRD_SUB_UNITS lebih mungkin = jumlah bobbin.
--   BOX_TYPE          SYSTEM    prd.PRD_BOX_TYPE
--   COLOR_MGT         SYSTEM    ss.SS_INVI_SHADE (nama shade versi MGT)
--   CONTRACT_NO (ESC) SYSTEM    ESC dari SO — SSC tidak simpan; perlu join kecil:
--                               ss -> OT_SO_ITEM_MGT (SOIM_SYS_ID) -> SOIM_SOH_SYS_ID
--                               -> SO head (ambil no. ESC). Atau via prd.PRD_SOH_SYS_ID.
--   CUST_MATERIAL_NO  MASTER    SHIP_MARK_CUST_MAP (ATTR_TYPE='MATERIAL_NO', key=item)
--   CUST_ITEM_CODE    MASTER    SHIP_MARK_CUST_MAP (ATTR_TYPE='ITEM_CODE',  key=item)
--   COLOR_CUST        MANUAL    diketik dulu (next integration: field di SO).
--                               -- boleh disimpan ke MAP (ATTR_TYPE='COLOR_NAME', key=shade)
--                               -- via learn-as-you-go tanpa ubah schema.
--   NCM               MASTER    SHIP_MARK_CUST_MAP (ATTR_TYPE='NCM', key=item)
--   CUST_PO_NO        MANUAL    next integration (cek field di SO dgn tim sales)
--   INVOICE_NO        MANUAL    BEDA dari SOHM_INVH_NO; belum ada integrasi -> manual dulu
--   LC_NO             MANUAL    muncul setelah SO; belum ada sistem -> manual dulu
--   POS_NO            MANUAL
--   BRAND_LABEL       MASTER    per customer
--
-- CATATAN COLOR: preferensi per customer beragam — ada yg mau color name customer
-- saja, ada yg color customer + shade MGT, ada yg shade MGT saja. Template tinggal
-- menyusun 0/1/2 baris color (COLOR_CUST dan/atau COLOR_MGT). Tidak perlu flag khusus.
--
-- CATATAN MASTER MAP: belum ada master apapun. 1 FG bisa punya nama berbeda per
-- customer (material & color). Maka SHIP_MARK_CUST_MAP dibuat generik (ATTR_TYPE +
-- MGT_KEY + CUST_VALUE) supaya satu tabel menampung material/color/item-code/NCM,
-- dan diisi learn-as-you-go (entri manual pertama -> simpan -> otomatis berikutnya).
-- =============================================================================


-- =============================================================================
-- 1) SHIP_MARK_TEMPLATE  (SMT_) — header template, di-default per customer
-- =============================================================================
CREATE TABLE MGTHRIS.SHIP_MARK_TEMPLATE
(
    SMT_TEMPLATE_ID     NUMBER(12)        NOT NULL,
    SMT_TEMPLATE_CODE   VARCHAR2(30 BYTE) NOT NULL,
    SMT_TEMPLATE_NAME   VARCHAR2(120 BYTE),
    SMT_CUST_CODE       VARCHAR2(12 BYTE),          -- logical ref MGTDAT.OM_CUSTOMER.CUST_CODE (NULL = shared)
    SMT_SHIP_TO         VARCHAR2(60 BYTE),          -- pembeda destinasi (mis. MELBOURNE vs CPT)
    SMT_PARTY_ROLE      VARCHAR2(20 BYTE),          -- BUYER/CONSIGNEE/NOTIFY/SHIPPER/RECEIVER
    SMT_GRAIN           VARCHAR2(12 BYTE) DEFAULT 'PALLET' NOT NULL, -- UNIFORM/ITEM/ITEM_LOT/PALLET/CARTON
    SMT_PAPER_SIZE      VARCHAR2(10 BYTE) DEFAULT 'A5'     NOT NULL, -- A4/A5/CUSTOM
    SMT_WIDTH_MM        NUMBER,                     -- utk CUSTOM
    SMT_HEIGHT_MM       NUMBER,
    SMT_ORIENTATION     VARCHAR2(1 BYTE)  DEFAULT 'P',    -- P/L
    SMT_DEFAULT_COPIES  NUMBER(2)         DEFAULT 1  NOT NULL,
    SMT_IS_DEFAULT      VARCHAR2(1 BYTE)  DEFAULT 'N' NOT NULL, -- default template utk customer
    SMT_IS_SUPP         VARCHAR2(1 BYTE)  DEFAULT 'N' NOT NULL, -- label tambahan (brand/L-C)
    SMT_ACTIVE_YN       VARCHAR2(1 BYTE)  DEFAULT 'Y' NOT NULL,
    SMT_VERSION_NO      NUMBER            DEFAULT 1,
    SMT_CR_UID          VARCHAR2(20 BYTE),
    SMT_CR_DT           DATE              DEFAULT SYSDATE,
    SMT_UPD_UID         VARCHAR2(20 BYTE),
    SMT_UPD_DT          DATE,
    CONSTRAINT SHIP_MARK_TEMPLATE_PK PRIMARY KEY (SMT_TEMPLATE_ID),
    CONSTRAINT SMT_TEMPLATE_CODE_UK  UNIQUE (SMT_TEMPLATE_CODE),
    CONSTRAINT SMT_GRAIN_CK  CHECK (SMT_GRAIN IN ('UNIFORM','ITEM','ITEM_LOT','PALLET','CARTON'))
);

CREATE INDEX MGTHRIS.SMT_CUST_IDX ON MGTHRIS.SHIP_MARK_TEMPLATE (SMT_CUST_CODE, SMT_ACTIVE_YN);

CREATE SEQUENCE MGTHRIS.SMT_SEQ START WITH 1 INCREMENT BY 1 CACHE 20;

CREATE OR REPLACE TRIGGER MGTHRIS.TRG_SMT_BI
  BEFORE INSERT ON MGTHRIS.SHIP_MARK_TEMPLATE FOR EACH ROW
BEGIN
  IF :NEW.SMT_TEMPLATE_ID IS NULL THEN
SELECT MGTHRIS.SMT_SEQ.NEXTVAL INTO :NEW.SMT_TEMPLATE_ID FROM DUAL;
END IF;
END;
/


-- =============================================================================
-- 2) SHIP_MARK_TEMPLATE_LINE  (SMTL_) — baris field terurut
-- =============================================================================
CREATE TABLE MGTHRIS.SHIP_MARK_TEMPLATE_LINE
(
    SMTL_LINE_ID       NUMBER(12)        NOT NULL,
    smtl_smt_template_id   NUMBER(12)        NOT NULL,   -- FK -> SMT
    SMTL_SORT_ORDER    NUMBER(4)         NOT NULL,
    SMTL_LINE_TYPE     VARCHAR2(10 BYTE) DEFAULT 'FIELD' NOT NULL, -- FIELD/TITLE/FOOTER/STATIC/SPACER
    SMTL_LABEL_TEXT    VARCHAR2(120 BYTE),           -- teks label kiri (NULL utk baris tanpa label)
    SMTL_VALUE_TYPE    VARCHAR2(10 BYTE),            -- STATIC/SYSTEM/MASTER/MANUAL/COMPUTED
    SMTL_VALUE_REF     VARCHAR2(40 BYTE),            -- field key (ITEM, LOT_NO, CUST_MATERIAL_NO, ...)
    SMTL_STATIC_VALUE  VARCHAR2(2000 BYTE),          -- isi utk STATIC
    SMTL_COL_POS       NUMBER(1)         DEFAULT 1,  -- 1 / 2  (baris 2-kolom: GW + NW)
    SMTL_INDENT        NUMBER(1)         DEFAULT 0,  -- sub-indent (WEIGHT -> Net/Gross)
    SMTL_IS_BOLD       VARCHAR2(1 BYTE)  DEFAULT 'N',
    SMTL_FONT_SIZE     NUMBER(3),
    SMTL_CR_UID        VARCHAR2(20 BYTE),
    SMTL_CR_DT         DATE              DEFAULT SYSDATE,
    SMTL_UPD_UID       VARCHAR2(20 BYTE),
    SMTL_UPD_DT        DATE,
    CONSTRAINT SHIP_MARK_TEMPLATE_LINE_PK PRIMARY KEY (SMTL_LINE_ID),
    CONSTRAINT SMTL_TEMPLATE_FK FOREIGN KEY (smtl_smt_template_id)
        REFERENCES MGTHRIS.SHIP_MARK_TEMPLATE (SMT_TEMPLATE_ID) ON DELETE CASCADE,
    CONSTRAINT SMTL_VALUE_TYPE_CK CHECK (SMTL_VALUE_TYPE IN ('STATIC','SYSTEM','MASTER','MANUAL','COMPUTED'))
);

CREATE INDEX MGTHRIS.smtl_smt_template_idX ON MGTHRIS.SHIP_MARK_TEMPLATE_LINE (smtl_smt_template_id, SMTL_SORT_ORDER);

CREATE SEQUENCE MGTHRIS.SMTL_SEQ START WITH 1 INCREMENT BY 1 CACHE 20;

CREATE OR REPLACE TRIGGER MGTHRIS.TRG_SMTL_BI
    BEFORE INSERT ON MGTHRIS.SHIP_MARK_TEMPLATE_LINE FOR EACH ROW
    WHEN (NEW.SMTL_LINE_ID IS NULL)
BEGIN
    :NEW.SMTL_LINE_ID := MGTHRIS.SMTL_SEQ.NEXTVAL;
END;
/


-- =============================================================================
-- 3) SHIP_MARK_CUST_MAP  (SMCM_) — mapping nilai per-customer, learn-as-you-go
--    Generik: 1 tabel menampung MATERIAL_NO / ITEM_CODE / COLOR_NAME / NCM / ...
-- =============================================================================
CREATE TABLE MGTHRIS.SHIP_MARK_CUST_MAP
(
    SMCM_MAP_ID     NUMBER(12)         NOT NULL,
    SMCM_CUST_CODE  VARCHAR2(12 BYTE)  NOT NULL,     -- logical ref OM_CUSTOMER.CUST_CODE
    SMCM_ATTR_TYPE  VARCHAR2(20 BYTE)  NOT NULL,     -- MATERIAL_NO/ITEM_CODE/COLOR_NAME/NCM
    SMCM_MGT_KEY    VARCHAR2(60 BYTE)  NOT NULL,     -- item/grade (material/item/ncm) atau shade (color)
    SMCM_CUST_VALUE VARCHAR2(240 BYTE) NOT NULL,     -- string versi customer
    SMCM_ACTIVE_YN  VARCHAR2(1 BYTE)   DEFAULT 'Y' NOT NULL,
    SMCM_CR_UID     VARCHAR2(20 BYTE),
    SMCM_CR_DT      DATE               DEFAULT SYSDATE,
    SMCM_UPD_UID    VARCHAR2(20 BYTE),
    SMCM_UPD_DT     DATE,
    CONSTRAINT SHIP_MARK_CUST_MAP_PK PRIMARY KEY (SMCM_MAP_ID),
    CONSTRAINT SMCM_UK UNIQUE (SMCM_CUST_CODE, SMCM_ATTR_TYPE, SMCM_MGT_KEY)
);

CREATE SEQUENCE MGTHRIS.SMCM_SEQ START WITH 1 INCREMENT BY 1 CACHE 20;

CREATE OR REPLACE TRIGGER MGTHRIS.TRG_SMCM_BI
    BEFORE INSERT ON MGTHRIS.SHIP_MARK_CUST_MAP FOR EACH ROW
    WHEN (NEW.SMCM_MAP_ID IS NULL)
BEGIN
    :NEW.SMCM_MAP_ID := MGTHRIS.SMCM_SEQ.NEXTVAL;
END;
/


-- =============================================================================
-- 4) SHIP_MARK_PRINT_LOG  (SMPL_) — jejak generate + reprint + snapshot manual
-- =============================================================================
CREATE TABLE MGTHRIS.SHIP_MARK_PRINT_LOG
(
    SMPL_LOG_ID        NUMBER(12)        NOT NULL,
    SMPL_SSC_TXN_CODE  VARCHAR2(12 BYTE) NOT NULL,   -- sohm_txn_code
    SMPL_SSC_NO        NUMBER            NOT NULL,    -- sohm_no
    smtl_smt_template_id   NUMBER(12)        NOT NULL,    -- FK -> SMT
    SMPL_GRAIN         VARCHAR2(12 BYTE),             -- snapshot grain saat generate
    SMPL_COPIES        NUMBER(2),
    SMPL_UNIT_COUNT    NUMBER,                        -- jumlah label yg dihasilkan
    SMPL_MANUAL_VALUES CLOB,                          -- JSON per-item (ClobJson cast) — PO, color_cust, dst
    SMPL_CR_UID        VARCHAR2(20 BYTE),
    SMPL_CR_DT         DATE              DEFAULT SYSDATE,
    CONSTRAINT SHIP_MARK_PRINT_LOG_PK PRIMARY KEY (SMPL_LOG_ID),
    CONSTRAINT SMPL_TEMPLATE_FK FOREIGN KEY (smtl_smt_template_id)
        REFERENCES MGTHRIS.SHIP_MARK_TEMPLATE (SMT_TEMPLATE_ID)
);

CREATE INDEX MGTHRIS.SMPL_SSC_IDX ON MGTHRIS.SHIP_MARK_PRINT_LOG (SMPL_SSC_TXN_CODE, SMPL_SSC_NO);

CREATE SEQUENCE MGTHRIS.SMPL_SEQ START WITH 1 INCREMENT BY 1 CACHE 20;

CREATE OR REPLACE TRIGGER MGTHRIS.TRG_SMPL_BI
    BEFORE INSERT ON MGTHRIS.SHIP_MARK_PRINT_LOG FOR EACH ROW
    WHEN (NEW.SMPL_LOG_ID IS NULL)
BEGIN
    :NEW.SMPL_LOG_ID := MGTHRIS.SMPL_SEQ.NEXTVAL;
END;
/


-- =============================================================================
-- SEED — contoh 1 template konkret:  TPL_BEKAERT_AU
--   Party BUYER · grain PALLET · A5 · copies 1
--   (ganti 'BEKAUS' dgn cust_code Bekaert Australia yg asli di OM_CUSTOMER)
-- =============================================================================
INSERT INTO MGTHRIS.SHIP_MARK_TEMPLATE
 (SMT_TEMPLATE_CODE, SMT_TEMPLATE_NAME, SMT_CUST_CODE, SMT_PARTY_ROLE,
  SMT_GRAIN, SMT_PAPER_SIZE, SMT_DEFAULT_COPIES, SMT_IS_DEFAULT, SMT_CR_UID)
 VALUES
 ('BEKAERT_AU','Bekaert Australia','BEKAUS','BUYER',
  'PALLET','A5',1,'Y','SYSTEM');

-- baris field (pakai TEMPLATE_ID yg baru terbentuk)
INSERT INTO MGTHRIS.SHIP_MARK_TEMPLATE_LINE
(smtl_smt_template_id, SMTL_SORT_ORDER, SMTL_LINE_TYPE, SMTL_LABEL_TEXT, SMTL_VALUE_TYPE, SMTL_VALUE_REF, SMTL_STATIC_VALUE)
SELECT SMT_TEMPLATE_ID, s.ord, s.ltype, s.lbl, s.vtype, s.vref, s.sval
FROM   MGTHRIS.SHIP_MARK_TEMPLATE
           CROSS JOIN (
    SELECT  1 ord, 'FIELD'  ltype, 'BUYER'            lbl, 'STATIC' vtype, NULL               vref, 'BEKAERT AUSTRALIA PTY LTD' sval FROM DUAL UNION ALL
    SELECT  2, 'FIELD',  'CUSTOMER PO NO',   'MANUAL', 'CUST_PO_NO',       NULL FROM DUAL UNION ALL
    SELECT  3, 'FIELD',  'ITEM',             'SYSTEM', 'ITEM',             NULL FROM DUAL UNION ALL
    SELECT  4, 'FIELD',  'COLOR',            'MANUAL', 'COLOR_CUST',       NULL FROM DUAL UNION ALL
    SELECT  5, 'FIELD',  'CUST. MATERIAL NO','MASTER', 'CUST_MATERIAL_NO', NULL FROM DUAL UNION ALL
    SELECT  6, 'FIELD',  'LOT NO.',          'SYSTEM', 'LOT_NO',           NULL FROM DUAL UNION ALL
    SELECT  7, 'FIELD',  'NET WEIGHT',       'SYSTEM', 'NET_WT',           NULL FROM DUAL UNION ALL
    SELECT  8, 'FIELD',  'GROSS WEIGHT',     'SYSTEM', 'GROSS_WT',         NULL FROM DUAL UNION ALL
    SELECT  9, 'FIELD',  'PALLET NO.',       'SYSTEM', 'PALLET_NO',        NULL FROM DUAL UNION ALL
    SELECT 10, 'FOOTER', NULL,               'STATIC', NULL,               'MADE IN INDONESIA' FROM DUAL
) s
WHERE SMT_TEMPLATE_CODE = 'BEKAERT_AU';

COMMIT;

-- Contoh isi master map (learn-as-you-go akan mengisi ini otomatis dari entri user):
-- INSERT INTO MGTHRIS.SHIP_MARK_CUST_MAP (SMCM_CUST_CODE, SMCM_ATTR_TYPE, SMCM_MGT_KEY, SMCM_CUST_VALUE)
--   VALUES ('BEKAUS','MATERIAL_NO','PTY 150/48/RND/SD/HIM/DH/N/2/SZ','10006010');

