-- =============================================================================
-- PPC Production Planning System
-- Oracle Summary Tables DDL
-- Schema: MGTDAT
-- Generated: Juni 2026
-- Updated: Juni 2026 — rev 2 (TXT/TWT TQM columns, SPG TQM + CUT/NOT_TRANSFER)
-- =============================================================================
-- Tujuan:
--   3 summary tables baru sebagai staging layer antara Oracle raw tables
--   dan PostgreSQL PPC system via ETL Go.
--   Pattern: DELETE window + INSERT fresh aggregate + LAST_UPDATED watermark
-- =============================================================================


-- =============================================================================
-- TABLE 1: PPC_TXT_PRODUCTION
-- Source  : ASPTXT.TXTTRANSFER + ASPTXT.TXTMACH
-- Scope   : TXT dan TWT bobbin production + TQM status, agregasi per shift
-- Window  : TRN_PRD_DT >= TRUNC(SYSDATE) - 7  (7 hari rolling)
--           Lebih lebar dari SPG karena TQM TYPE=6/7 bisa dibuat beberapa
--           hari setelah produksi TYPE=1 asli
-- ETL key : LAST_UPDATED (watermark untuk ETL Go)
-- Nat. key: LOT_NO + MACHINE_NO + TRN_DATE + TRN_SHIFT + DOFF_NO
--
-- ⚠️  TRN_STS: 0 = FULL, 1 = UNFULL (KEBALIKAN dari DOFF_OPTION SPG)
-- ⚠️  TRN_APP_REL: 0/NULL=Belum dicek, 1=DG/Rejected, 2=Normal/Released
--     TQM embedded di TXTTRANSFER — tidak ada tabel TQM terpisah
--     Status final per bobbin ditentukan dari TRN_NO terbesar per posisi:
--       TYPE!=7 AND APP_REL=2 → NORMAL
--       TYPE=7               → DOWNGRADE FINAL (meski APP_REL=2)
--       APP_REL=1 tanpa TYPE=6/7 lanjutan → PENDING (di-hold TQM)
--       APP_REL IS NULL      → belum dicek TQM
-- =============================================================================

CREATE TABLE MGTDAT.PPC_TXT_PRODUCTION
(
    LOT_NO              VARCHAR2(15 BYTE)   NOT NULL,
    MACHINE_NO          VARCHAR2(4 BYTE)    NOT NULL,
    AREA                VARCHAR2(3 BYTE)    NOT NULL,   -- TXT / TWT
    TRN_DATE            DATE                NOT NULL,   -- tanggal transfer (basis grouping shift)
    TRN_SHIFT           VARCHAR2(1 BYTE)    NOT NULL,   -- shift saat transfer: 1/2/3
    PROD_DATE           DATE,                           -- TRN_PRD_DT, tanggal produksi aktual
    DOFF_NO             NUMBER(3)           NOT NULL,
    -- Qty bobbin dari TYPE=1 (produksi asli)
    TOTAL_BOBBINS       NUMBER(6)           DEFAULT 0,
    FULL_BOBBINS        NUMBER(6)           DEFAULT 0,  -- TRN_STS = 0 (FULL)
    UNFULL_BOBBINS      NUMBER(6)           DEFAULT 0,  -- TRN_STS = 1 (UNFULL)
    -- TQM status breakdown (status final per posisi dari TRN_NO terbesar)
    NORMAL_BOBS         NUMBER(6)           DEFAULT 0,  -- FINAL_TYPE!=7 AND APP_REL=2
    DOWNGRADE_BOBS      NUMBER(6)           DEFAULT 0,  -- FINAL_TYPE=7 (final defect release)
    PENDING_BOBS        NUMBER(6)           DEFAULT 0,  -- APP_REL!=2 (DG di-hold TQM, belum final)
    -- Pack handover
    PACK_CEK_BOBS       NUMBER(6)           DEFAULT 0,  -- ada TRN_PACK_CEK=1 di transaksi manapun
    LAST_UPDATED        DATE                NOT NULL    -- SYSDATE saat refresh, watermark ETL
)
TABLESPACE ORAASFIN
PCTFREE    10
INITRANS   4
MAXTRANS   255
STORAGE (
    INITIAL    2M
    NEXT       1M
    MINEXTENTS 1
    MAXEXTENTS UNLIMITED
)
LOGGING
NOCOMPRESS
NOCACHE;

COMMENT ON TABLE  MGTDAT.PPC_TXT_PRODUCTION IS
    'PPC summary: TXT/TWT bobbin production + TQM status per lot per mesin per shift. '
    'Rolling 7 hari (TRN_PRD_DT). TQM embedded di TXTTRANSFER via TYPE=1/6/7 journey. '
    'Source: ASPTXT.TXTTRANSFER + ASPTXT.TXTMACH.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.TRN_DATE        IS
    'Tanggal transfer (TRN_DATE dari TYPE=1). Basis grouping shift.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.PROD_DATE       IS
    'TRN_PRD_DT - tanggal produksi aktual bobbin. Basis DELETE window.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.FULL_BOBBINS    IS
    '⚠️ TRN_STS=0 = FULL (kebalikan DOFF_OPTION SPG). Dipakai: count × lm_std_weight_full.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.UNFULL_BOBBINS  IS
    '⚠️ TRN_STS=1 = UNFULL (kebalikan DOFF_OPTION SPG). Dipakai: count × lm_std_weight_unfull.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.NORMAL_BOBS     IS
    'Bobbin lulus TQM final. FINAL_TYPE!=7 AND TRN_APP_REL=2. Include yang normal di retest TYPE=6.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.DOWNGRADE_BOBS  IS
    'Bobbin final defect. FINAL_TYPE=7 (TRN_APP_REL=2 tapi TYPE=7 = bukan normal).';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.PENDING_BOBS    IS
    'Bobbin masih di-hold TQM. DG di TYPE=1/6 tapi belum ada TYPE=6/7 lanjutan. Atau belum dicek.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.PACK_CEK_BOBS   IS
    'Bobbin sudah handover ke packing (TRN_PACK_CEK=1 di transaksi manapun). Belum tentu dipacking.';
COMMENT ON COLUMN MGTDAT.PPC_TXT_PRODUCTION.LAST_UPDATED    IS
    'SYSDATE saat row di-insert. ETL Go pakai ini sebagai watermark incremental.';

-- Index untuk ETL watermark query
CREATE INDEX MGTDAT.IDX_PPC_TXT_LASTUPD
    ON MGTDAT.PPC_TXT_PRODUCTION (LAST_UPDATED)
    TABLESPACE ORAASFIN;

-- Index untuk natural key
CREATE UNIQUE INDEX MGTDAT.IDX_PPC_TXT_NATKEY
    ON MGTDAT.PPC_TXT_PRODUCTION (LOT_NO, MACHINE_NO, TRN_DATE, TRN_SHIFT, DOFF_NO)
    TABLESPACE ORAASFIN;

-- Index untuk DELETE window (by PROD_DATE)
CREATE INDEX MGTDAT.IDX_PPC_TXT_PRODDATE
    ON MGTDAT.PPC_TXT_PRODUCTION (PROD_DATE)
    TABLESPACE ORAASFIN;


-- =============================================================================
-- TABLE 2: PPC_SPG_PRODUCTION
-- Source  : ASPSPG.DOFFCONT (qty) + ASPSPG.TRANSFER (flag) + ASPSPG.TQMAPP (TQM)
-- Scope   : SPG bobbin production per doff per posisi + TQM breakdown
-- Window  : DOFF_DATE >= TRUNC(SYSDATE) - 2  (2 hari rolling)
-- ETL key : LAST_UPDATED
-- Nat. key: LOT_NO + MACHINE_LINE + DOFF_DATE + POSITION_NO + DOFF_NO
--
-- Sanity checks:
--   GROSS_BOBBINS = TRANSFERRED_BOBS + CUT_BOBBINS + NOT_TRANSFER
--   TRANSFERRED_BOBS = NORMAL_BOBS + DOWNGRADE_BOBS + NOT_CHECKED_BOBS
--
-- DOFF_OPTION: 1=FULL, 2=UNFULL
-- TQM join: TQMAPP via TRN_APP_REL_DT + TRN_APP_REL_DOFF + TRN_POS + TRN_BOB
--   TQM_GRADE=1=Normal, TQM_GRADE=0=Down Grade, TRN_APP_REL_DT IS NULL=Not Checked
-- =============================================================================

CREATE TABLE MGTDAT.PPC_SPG_PRODUCTION
(
    LOT_NO              VARCHAR2(10 BYTE)   NOT NULL,   -- DOFF_MERGE
    MACHINE_LINE        VARCHAR2(3 BYTE)    NOT NULL,   -- DOFF_LINE (A1, A2, B1, dll)
    DOFF_DATE           DATE                NOT NULL,   -- tanggal doffing
    POSITION_NO         NUMBER(3)           NOT NULL,   -- DOFF_POSITION
    DOFF_NO             NUMBER(6)           NOT NULL,   -- DOFF_NO
    DOFF_OPTION         NUMBER(1),                      -- 1=FULL, 2=UNFULL
    DOFF_CATEGORY       VARCHAR2(15 BYTE),
    -- DOFF_CATEGORY values:
    --   NORMAL          : produksi normal
    --   WASTE_RM        : DOFF_REMARKS IN ('RM','RMDG')
    --   STARTUP_BREAK   : DOFF_REMARKS IN ('SB','FF','PL','TG','MO','GR','FR')
    --   CHANGEOVER      : DOFF_DESC LIKE '%CO%' atau DOFF_REMARKS='CO'
    INCLUDE_IN_SUGGEST  NUMBER(1)           DEFAULT 0,  -- 1=masuk suggest qty PPC
    -- Dari DOFFCONT: gross production (semua keluar mesin)
    GROSS_BOBBINS       NUMBER(6)           DEFAULT 0,  -- DOFF_NO_END
    WEIGHT_PER_BOB      NUMBER(10,4),                   -- DOFF_WT (kg)
    GROSS_WEIGHT_KG     NUMBER(14,4),                   -- GROSS_BOBBINS × WEIGHT_PER_BOB
    -- Dari ASPSPG.TRANSFER: breakdown status
    TRANSFERRED_BOBS    NUMBER(6)           DEFAULT 0,  -- COUNT TRN_TYPE!=4, TRN_STATUS=2
    NET_WEIGHT_KG       NUMBER(14,4),                   -- TRANSFERRED_BOBS × WEIGHT_PER_BOB
    CUT_BOBBINS         NUMBER(6)           DEFAULT 0,  -- COUNT TRN_TYPE=4 (dipotong/waste)
    NOT_TRANSFER        NUMBER(6)           DEFAULT 0,  -- GROSS - TRANSFERRED - CUT (belum ada di TRANSFER)
    -- TQM breakdown (dari TQMAPP via pointer di TRANSFER)
    NORMAL_BOBS         NUMBER(6)           DEFAULT 0,  -- TQM_GRADE=1
    DOWNGRADE_BOBS      NUMBER(6)           DEFAULT 0,  -- TQM_GRADE=0
    NOT_CHECKED_BOBS    NUMBER(6)           DEFAULT 0,  -- TRN_APP_REL_DT IS NULL
    TQM_DONE_BOBS       NUMBER(6)           DEFAULT 0,  -- NORMAL + DOWNGRADE
    LAST_UPDATED        DATE                NOT NULL
)
TABLESPACE ORAASFIN
PCTFREE    10
INITRANS   4
MAXTRANS   255
STORAGE (
    INITIAL    2M
    NEXT       1M
    MINEXTENTS 1
    MAXEXTENTS UNLIMITED
)
LOGGING
NOCOMPRESS
NOCACHE;

COMMENT ON TABLE  MGTDAT.PPC_SPG_PRODUCTION IS
    'PPC summary: SPG production per doff per posisi + TQM breakdown. '
    'Qty dari DOFFCONT, transfer status dari ASPSPG.TRANSFER, TQM dari ASPSPG.TQMAPP. '
    'Sanity: GROSS=TRANSFERRED+CUT+NOT_TRANSFER, TRANSFERRED=NORMAL+DOWNGRADE+NOT_CHECKED.';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.GROSS_BOBBINS    IS
    'Total bobbin keluar dari mesin (DOFFCONT.DOFF_NO_END). Termasuk yang nanti dipotong.';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.TRANSFERRED_BOBS IS
    'Bobbin yang sudah ditransfer ke lag area (TRN_STATUS=2, TRN_TYPE!=4).';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.CUT_BOBBINS      IS
    'Bobbin dipotong/waste saat transfer (TRN_STATUS=2, TRN_TYPE=4). Beda dengan NOT_TRANSFER.';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.NOT_TRANSFER      IS
    'Bobbin belum ada di TRANSFER sama sekali (GROSS - TRANSFERRED - CUT).';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.NORMAL_BOBS      IS
    'Bobbin lulus TQM (TQM_GRADE=1 dari TQMAPP, hanya yang transferred).';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.DOWNGRADE_BOBS   IS
    'Bobbin down grade TQM (TQM_GRADE=0 dari TQMAPP, hanya yang transferred).';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.NOT_CHECKED_BOBS IS
    'Bobbin transferred tapi belum ada acuan TQM (TRN_APP_REL_DT IS NULL).';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.TQM_DONE_BOBS    IS
    'NORMAL_BOBS + DOWNGRADE_BOBS. Sudah ada hasil TQM apapun.';
COMMENT ON COLUMN MGTDAT.PPC_SPG_PRODUCTION.INCLUDE_IN_SUGGEST IS
    '1=masuk suggest qty PPC (NORMAL category + FULL bobbin). 0=exclude.';

CREATE INDEX MGTDAT.IDX_PPC_SPG_LASTUPD
    ON MGTDAT.PPC_SPG_PRODUCTION (LAST_UPDATED)
    TABLESPACE ORAASFIN;

CREATE UNIQUE INDEX MGTDAT.IDX_PPC_SPG_NATKEY
    ON MGTDAT.PPC_SPG_PRODUCTION (LOT_NO, MACHINE_LINE, DOFF_DATE, POSITION_NO, DOFF_NO)
    TABLESPACE ORAASFIN;

CREATE INDEX MGTDAT.IDX_PPC_SPG_DOFFDATE
    ON MGTDAT.PPC_SPG_PRODUCTION (DOFF_DATE)
    TABLESPACE ORAASFIN;


-- =============================================================================
-- TABLE 3: PPC_GRADE_ACTUAL
-- Source  : ASPAK.PAKPKGDUP + ASMAR.PAKPKG + ASPAK.PAKPKGDUPAM
-- Scope   : Grade aktual dari packing, agregasi per original lot per grade
-- Window  : PKG_PK_PUT_DATE >= TRUNC(SYSDATE) - 1
-- ETL key : LAST_UPDATED
-- Nat. key: ORIGINAL_LOT_NO + GRADE + DEPT
-- Catatan :
--   Grade B/BB → original lot dari PAKPKG (outer join)
--   Grade AM   → breakup ke A9+A dari PAKPKGDUPAM (outer join)
--   Grade lain → PKG_MERGE_NO sudah = original lot
-- =============================================================================

CREATE TABLE MGTDAT.PPC_GRADE_ACTUAL
(
    ORIGINAL_LOT_NO     VARCHAR2(15 BYTE)   NOT NULL,
    GRADE               VARCHAR2(5 BYTE)    NOT NULL,   -- AX/AE/A9/A/AM/APQ/B/BB/C/JLT
    DEPT                VARCHAR2(3 BYTE)    NOT NULL,   -- TXT / TWT
    TOTAL_QTY_KG        NUMBER(14,3)        DEFAULT 0,
    TOTAL_BOBBIN_COUNT  NUMBER(8)           DEFAULT 0,
    LAST_PACKING_DATE   DATE,
    LAST_UPDATED        DATE                NOT NULL
)
TABLESPACE ORAASFIN
PCTFREE    10
INITRANS   4
MAXTRANS   255
STORAGE (
    INITIAL    1M
    NEXT       1M
    MINEXTENTS 1
    MAXEXTENTS UNLIMITED
)
LOGGING
NOCOMPRESS
NOCACHE;

COMMENT ON TABLE  MGTDAT.PPC_GRADE_ACTUAL IS
    'PPC summary: grade aktual dari packing per original lot per grade. '
    'Agregasi per lot+grade, bukan per box. Rolling 1 hari. '
    'Source: ASPAK.PAKPKGDUP + ASMAR.PAKPKG + ASPAK.PAKPKGDUPAM.';
COMMENT ON COLUMN MGTDAT.PPC_GRADE_ACTUAL.ORIGINAL_LOT_NO IS
    'Original lot no sesuai WO di PPC. Grade B/BB dari ASMAR.PAKPKG, grade lain dari PAKPKGDUP.';
COMMENT ON COLUMN MGTDAT.PPC_GRADE_ACTUAL.GRADE IS
    'Grade dari packing. Grade AM dibreakup ke A9+A via PAKPKGDUPAM.';
COMMENT ON COLUMN MGTDAT.PPC_GRADE_ACTUAL.TOTAL_QTY_KG IS
    'Total net weight kg untuk lot+grade ini (PKG_QTY atau PAKPKGDUPAM.PKG_QTY untuk AM).';

CREATE INDEX MGTDAT.IDX_PPC_GRADE_LASTUPD
    ON MGTDAT.PPC_GRADE_ACTUAL (LAST_UPDATED)
    TABLESPACE ORAASFIN;

CREATE UNIQUE INDEX MGTDAT.IDX_PPC_GRADE_NATKEY
    ON MGTDAT.PPC_GRADE_ACTUAL (ORIGINAL_LOT_NO, GRADE, DEPT)
    TABLESPACE ORAASFIN;

-- Grant akses baca ke ETL user jika diperlukan
-- GRANT SELECT ON MGTDAT.PPC_TXT_PRODUCTION  TO <etl_user>;
-- GRANT SELECT ON MGTDAT.PPC_SPG_PRODUCTION  TO <etl_user>;
-- GRANT SELECT ON MGTDAT.PPC_GRADE_ACTUAL    TO <etl_user>;
