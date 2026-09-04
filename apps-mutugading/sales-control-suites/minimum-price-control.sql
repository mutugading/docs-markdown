-- =====================================================================
-- Sales Control Suite - Phase 1: Minimum Price Control
-- Oracle 11g
-- Author : Indra Kurniawan
--
-- DUA SKEMA:
--   MGTHRIS - 4 tabel baru + baris HM_MST_SEQUENCES dan trigger sys id-nya.
--             Milik aplikasi Laravel, karena MGTHRIS adalah koneksi default
--             aplikasi.
--               SALES_CTL_APPR_REQUEST     (SCAR_)  - dipakai 3 modul kontrol
--               SALES_CTL_APPR_LINE        (SCAL_)  - dipakai 3 modul kontrol
--               SALES_MIN_PRICE            (SMP_)   - khusus minimum price
--               SALES_MIN_PRICE_CHECK_LOG  (SMPCL_) - khusus minimum price
--   MGTDAT  - data Orion (OT_SO_HEAD, OT_SO_ITEM, FM_EXCHANGE_RATE,
--             IM_VS_STATIC_VALUE, IM_APP_ERROR_MESSAGE), package, trigger.
--             Aplikasi hanya MEMBACA skema ini.
--
-- Nama tabel dipendekkan jadi APPR_REQUEST / APPR_LINE (bukan APPROVAL_*)
-- supaya nama index dan constraint tetap di bawah batas 30 byte Oracle 11g:
-- SALES_CTL_APPROVAL_REQUEST_UK01 = 31 byte, ditolak.
--
-- URUTAN DEPLOY: A -> B -> C -> F1 -> D -> F2 -> E
--   B (counter + trigger sys id) HARUS sesudah A: trigger-nya menempel di
--   tabel bagian A. Prasyaratnya MGTHRIS.HM_MST_SEQUENCES dan
--   MGTHRIS.PKG_HM_SEQUENCES, dua-duanya sudah ada di skema ini.
--   F1 (grant MGTHRIS -> MGTDAT) HARUS sebelum D.
--   Package memakai definer's rights: kalau grant belum ada, package tetap
--   compile bersih lalu gagal ORA-00942 waktu jalan. Lihat bagian F.
--   Bagian E (trigger) dipasang PALING AKHIR, setelah UI Laravel siap.
--   Memasang trigger sebelum itu = memblok approval tanpa jalan keluar.
--
-- VERIFIKASI WAJIB sebelum deploy - lihat PRD bagian 4.8
-- dan .docs-me/sales-control-suites/verification.md bagian 1.
-- =====================================================================


-- =====================================================================
-- BAGIAN A - TABEL  (di MGTHRIS)
--
-- VERIFIKASI: klausa TABLESPACE di bawah masih ORION, ikut skrip asli
-- waktu tabel ini direncanakan di MGTDAT. Cek tablespace yang dipakai
-- MGTHRIS (USER_TS_QUOTAS / USER_TABLES) dan sesuaikan sebelum jalan.
-- =====================================================================

-- ---------------------------------------------------------------------
-- SALES_CTL_APPR_REQUEST (SCAR_) - dipakai bersama 3 modul kontrol
-- ---------------------------------------------------------------------
CREATE TABLE MGTHRIS.SALES_CTL_APPR_REQUEST
(
    SCAR_SYS_ID          NUMBER(12)          NOT NULL,
    SCAR_REQ_NO          VARCHAR2(30 BYTE)   NOT NULL,
    SCAR_REVISION        NUMBER(3)           DEFAULT 0 NOT NULL,
    SCAR_CTRL_TYPE       VARCHAR2(12 BYTE)   NOT NULL,
    SCAR_STATUS          VARCHAR2(12 BYTE)   DEFAULT 'DRAFT' NOT NULL,
    SCAR_CUST_CODE       VARCHAR2(12 BYTE),
    SCAR_SOH_SYS_ID      NUMBER(12),
    SCAR_TXN_CODE        VARCHAR2(12 BYTE),
    SCAR_DOC_NO          NUMBER(10),
    SCAR_DOC_DT          DATE,
    SCAR_CURR_CODE       VARCHAR2(12 BYTE),
    SCAR_AMOUNT          NUMBER,
    SCAR_VALID_FROM      DATE,
    SCAR_VALID_TO        DATE,
    SCAR_REASON          VARCHAR2(2000 BYTE),
    SCAR_PRINT_COUNT     NUMBER(4)           DEFAULT 0,
    SCAR_PRINT_DT        DATE,
    SCAR_PRINT_UID       VARCHAR2(12 BYTE),
    SCAR_BOD_DOC_NO      VARCHAR2(60 BYTE),
    SCAR_BOD_DOC_DT      DATE,
    SCAR_BOD_SIGNER      VARCHAR2(120 BYTE),
    SCAR_ATTACH_PATH     VARCHAR2(500 BYTE),
    SCAR_ATTACH_HASH     VARCHAR2(64 BYTE),
    SCAR_APPR_UID        VARCHAR2(12 BYTE),
    SCAR_APPR_DT         DATE,
    SCAR_REJ_REASON      VARCHAR2(2000 BYTE),
    SCAR_CR_UID          VARCHAR2(12 BYTE)   NOT NULL,
    SCAR_CR_DT           DATE                NOT NULL,
    SCAR_UPD_UID         VARCHAR2(12 BYTE),
    SCAR_UPD_DT          DATE
)
    TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTHRIS.SALES_CTL_APPR_REQUEST.SCAR_CTRL_TYPE IS 'PRICELIST | MINPRICE | OVERDUE | CRLIMIT';
COMMENT ON COLUMN MGTHRIS.SALES_CTL_APPR_REQUEST.SCAR_STATUS    IS 'DRAFT | PRINTED | APPROVED | REJECTED | CANCELLED | VOID';
COMMENT ON COLUMN MGTHRIS.SALES_CTL_APPR_REQUEST.SCAR_AMOUNT    IS 'Nominal tambahan - hanya dipakai CTRL_TYPE = CRLIMIT';
COMMENT ON COLUMN MGTHRIS.SALES_CTL_APPR_REQUEST.SCAR_ATTACH_HASH IS 'SHA-256 file scan, untuk deteksi file diganti';

CREATE UNIQUE INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_PK
    ON MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_SYS_ID)
    TABLESPACE ORION;

CREATE UNIQUE INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_UK01
    ON MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_REQ_NO, SCAR_REVISION)
    TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_NX01
    ON MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_CTRL_TYPE, SCAR_STATUS, SCAR_CUST_CODE)
    TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_NX02
    ON MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_SOH_SYS_ID)
    TABLESPACE ORION;

ALTER TABLE MGTHRIS.SALES_CTL_APPR_REQUEST ADD (
  CONSTRAINT SALES_CTL_APPR_REQUEST_PK PRIMARY KEY (SCAR_SYS_ID)
    USING INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_PK ENABLE VALIDATE,
  CONSTRAINT SALES_CTL_APPR_REQUEST_UK01 UNIQUE (SCAR_REQ_NO, SCAR_REVISION)
    USING INDEX MGTHRIS.SALES_CTL_APPR_REQUEST_UK01 ENABLE VALIDATE,
  CONSTRAINT SALES_CTL_APPR_REQUEST_C01
    CHECK (SCAR_CTRL_TYPE IN ('PRICELIST','MINPRICE','OVERDUE','CRLIMIT')) ENABLE VALIDATE,
  CONSTRAINT SALES_CTL_APPR_REQUEST_C02
    CHECK (SCAR_STATUS IN ('DRAFT','PRINTED','APPROVED','REJECTED','CANCELLED','VOID')) ENABLE VALIDATE,
  CONSTRAINT SALES_CTL_APPR_REQUEST_C03
    CHECK (SCAR_STATUS <> 'APPROVED' OR SCAR_ATTACH_PATH IS NOT NULL) ENABLE VALIDATE
);


-- ---------------------------------------------------------------------
-- SALES_CTL_APPR_LINE (SCAL_) - baris pengecualian, Phase 1 = MINPRICE
-- ---------------------------------------------------------------------
CREATE TABLE MGTHRIS.SALES_CTL_APPR_LINE
(
    SCAL_SYS_ID           NUMBER(12)         NOT NULL,
    SCAL_SCAR_SYS_ID       NUMBER(12)         NOT NULL,
    SCAL_SOI_SYS_ID       NUMBER(12),
    SCAL_ITEM_CODE        VARCHAR2(20 BYTE),
    SCAL_GRADE_CODE_1     VARCHAR2(12 BYTE),
    SCAL_GRADE_CODE_2     VARCHAR2(40 BYTE),
    SCAL_UOM_CODE         VARCHAR2(12 BYTE),
    SCAL_CURR_CODE        VARCHAR2(12 BYTE),
    SCAL_APPROVED_RATE    NUMBER,
    SCAL_APPROVED_QTY_BU  NUMBER,
    SCAL_MIN_PRICE_USD    NUMBER(18,3),
    SCAL_RATE_USD         NUMBER(18,6),
    SCAL_CR_UID           VARCHAR2(12 BYTE)  NOT NULL,
    SCAL_CR_DT            DATE               NOT NULL
)
    TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTHRIS.SALES_CTL_APPR_LINE.SCAL_APPROVED_RATE IS
  'Harga yang disetujui dalam currency transaksi. Pengikat: harus sama persis dengan SOI_RATE saat approve.';

CREATE UNIQUE INDEX MGTHRIS.SALES_CTL_APPR_LINE_PK
    ON MGTHRIS.SALES_CTL_APPR_LINE (SCAL_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_CTL_APPR_LINE_NX01
    ON MGTHRIS.SALES_CTL_APPR_LINE (SCAL_SOI_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_CTL_APPR_LINE_NX02
    ON MGTHRIS.SALES_CTL_APPR_LINE (SCAL_SCAR_SYS_ID) TABLESPACE ORION;

ALTER TABLE MGTHRIS.SALES_CTL_APPR_LINE ADD (
  CONSTRAINT SALES_CTL_APPR_LINE_PK PRIMARY KEY (SCAL_SYS_ID)
    USING INDEX MGTHRIS.SALES_CTL_APPR_LINE_PK ENABLE VALIDATE,
  CONSTRAINT SALES_CTL_APPR_LINE_FK01 FOREIGN KEY (SCAL_SCAR_SYS_ID)
    REFERENCES MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_SYS_ID) ENABLE VALIDATE
);


-- ---------------------------------------------------------------------
-- SALES_MIN_PRICE (SMP_) - master harga minimum
-- ---------------------------------------------------------------------
CREATE TABLE MGTHRIS.SALES_MIN_PRICE
(
    SMP_SYS_ID          NUMBER(12)          NOT NULL,
    SMP_SCAR_SYS_ID      NUMBER(12),
    SMP_SCOPE_LEVEL     VARCHAR2(6 BYTE)    NOT NULL,
    SMP_SCOPE_VALUE     VARCHAR2(20 BYTE)   NOT NULL,
    SMP_GRADE_CODE_1    VARCHAR2(12 BYTE)   DEFAULT '*' NOT NULL,
    SMP_GRADE_CODE_2    VARCHAR2(40 BYTE)   DEFAULT '*' NOT NULL,
    SMP_UOM_CODE        VARCHAR2(12 BYTE)   NOT NULL,
    SMP_MIN_PRICE_USD   NUMBER(18,3)        NOT NULL,
    SMP_TOLERANCE_PCT   NUMBER(5,2)         DEFAULT 0,
    SMP_VALID_FROM      DATE                NOT NULL,
    SMP_VALID_TO        DATE,
    SMP_STATUS          VARCHAR2(10 BYTE)   DEFAULT 'DRAFT' NOT NULL,
    SMP_REMARKS         VARCHAR2(2000 BYTE),
    SMP_CR_UID          VARCHAR2(12 BYTE)   NOT NULL,
    SMP_CR_DT           DATE                NOT NULL,
    SMP_UPD_UID         VARCHAR2(12 BYTE),
    SMP_UPD_DT          DATE
)
    TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 1M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE.SMP_SCOPE_LEVEL  IS 'ITEM | GROUP | ALL';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE.SMP_SCOPE_VALUE  IS 'Kode item / kode group / * untuk level ALL';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE.SMP_GRADE_CODE_1 IS 'Nilai grade atau * (semua)';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE.SMP_GRADE_CODE_2 IS 'Nilai grade atau * (semua)';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE.SMP_MIN_PRICE_USD IS
  'Harga minimum USD per SMP_UOM_CODE. 3 desimal, samakan dengan batas ODBTRG_SOI_DECML_DIGIT_MGT.';

CREATE UNIQUE INDEX MGTHRIS.SALES_MIN_PRICE_PK
    ON MGTHRIS.SALES_MIN_PRICE (SMP_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_MIN_PRICE_NX01
    ON MGTHRIS.SALES_MIN_PRICE (SMP_STATUS, SMP_SCOPE_LEVEL, SMP_SCOPE_VALUE)
    TABLESPACE ORION;

ALTER TABLE MGTHRIS.SALES_MIN_PRICE ADD (
  CONSTRAINT SALES_MIN_PRICE_PK PRIMARY KEY (SMP_SYS_ID)
    USING INDEX MGTHRIS.SALES_MIN_PRICE_PK ENABLE VALIDATE,
  CONSTRAINT SALES_MIN_PRICE_C01
    CHECK (SMP_SCOPE_LEVEL IN ('ITEM','GROUP','ALL')) ENABLE VALIDATE,
  CONSTRAINT SALES_MIN_PRICE_C02
    CHECK (SMP_STATUS IN ('DRAFT','APPROVED','VOID')) ENABLE VALIDATE,
  CONSTRAINT SALES_MIN_PRICE_C03
    CHECK (SMP_MIN_PRICE_USD > 0) ENABLE VALIDATE,
  CONSTRAINT SALES_MIN_PRICE_C04
    CHECK (SMP_VALID_TO IS NULL OR SMP_VALID_TO >= SMP_VALID_FROM) ENABLE VALIDATE,
  CONSTRAINT SALES_MIN_PRICE_FK01 FOREIGN KEY (SMP_SCAR_SYS_ID)
    REFERENCES MGTHRIS.SALES_CTL_APPR_REQUEST (SCAR_SYS_ID) ENABLE VALIDATE
);

-- CATATAN: periode tumpang tindih untuk kunci yang sama TIDAK bisa
-- ditegakkan constraint di Oracle 11g. Validasi di Laravel saat simpan.


-- ---------------------------------------------------------------------
-- SALES_MIN_PRICE_CHECK_LOG (SMPCL_) - bukti audit setiap pemeriksaan
-- ---------------------------------------------------------------------
CREATE TABLE MGTHRIS.SALES_MIN_PRICE_CHECK_LOG
(
    SMPCL_SYS_ID          NUMBER(12)         NOT NULL,
    SMPCL_SOH_SYS_ID      NUMBER(12),
    SMPCL_SOI_SYS_ID      NUMBER(12),
    SMPCL_TXN_CODE        VARCHAR2(12 BYTE),
    SMPCL_DOC_NO          NUMBER(10),
    SMPCL_DOC_DT          DATE,
    SMPCL_ITEM_CODE       VARCHAR2(20 BYTE),
    SMPCL_GRADE_CODE_1    VARCHAR2(12 BYTE),
    SMPCL_GRADE_CODE_2    VARCHAR2(40 BYTE),
    SMPCL_UOM_CODE        VARCHAR2(12 BYTE),
    SMPCL_QTY_BU          NUMBER,
    SMPCL_CURR_CODE       VARCHAR2(12 BYTE),
    SMPCL_RATE            NUMBER,
    SMPCL_EXG_DIVISOR     NUMBER,
    SMPCL_EXG_RATE_DT     DATE,
    SMPCL_EXG_RATE_SRC    VARCHAR2(10 BYTE),
    SMPCL_RATE_USD        NUMBER(18,6),
    SMPCL_NET_RATE_USD    NUMBER(18,6),
    SMPCL_HAS_DISCOUNT    VARCHAR2(1 BYTE),
    SMPCL_SMP_SYS_ID      NUMBER(12),
    SMPCL_MIN_PRICE_USD   NUMBER(18,3),
    SMPCL_RESULT          VARCHAR2(12 BYTE),
    SMPCL_SCAR_SYS_ID      NUMBER(12),
    SMPCL_APPR_UID        VARCHAR2(12 BYTE),
    SMPCL_CR_DT           DATE               NOT NULL
)
    TABLESPACE ORION
PCTFREE 10 INITRANS 50 MAXTRANS 255
STORAGE (INITIAL 4M NEXT 1M MINEXTENTS 1 MAXEXTENTS UNLIMITED PCTINCREASE 0);

COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE_CHECK_LOG.SMPCL_EXG_RATE_SRC IS 'BCA | BCA_PREV | ORION | USD';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE_CHECK_LOG.SMPCL_RESULT       IS 'PASS | PASS_NORULE | OVERRIDE | INHERIT | FAIL';
COMMENT ON COLUMN MGTHRIS.SALES_MIN_PRICE_CHECK_LOG.SMPCL_NET_RATE_USD IS
  'Monitoring saja - validasi memakai gross. Untuk review basis validasi nanti.';

CREATE UNIQUE INDEX MGTHRIS.SALES_MIN_PRICE_CHECK_LOG_PK
    ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG (SMPCL_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_MIN_PRICE_CHECK_LOG_NX01
    ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG (SMPCL_SOH_SYS_ID) TABLESPACE ORION;

CREATE INDEX MGTHRIS.SALES_MIN_PRICE_CHECK_LOG_NX02
    ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG (SMPCL_CR_DT, SMPCL_RESULT) TABLESPACE ORION;

ALTER TABLE MGTHRIS.SALES_MIN_PRICE_CHECK_LOG ADD (
  CONSTRAINT SALES_MIN_PRICE_CHECK_LOG_PK PRIMARY KEY (SMPCL_SYS_ID)
    USING INDEX MGTHRIS.SALES_MIN_PRICE_CHECK_LOG_PK ENABLE VALIDATE
);


-- =====================================================================
-- BAGIAN B - SEQUENCE  (di MGTHRIS, ikut tabelnya)
--
-- BUKAN objek CREATE SEQUENCE. Empat sys id ini memakai mekanisme rumah:
-- satu baris per tabel di MGTHRIS.HM_MST_SEQUENCES, dibaca dari PHP oleh
-- App\Helpers\SysIdHelper dan dari PL/SQL oleh MGTHRIS.PKG_HM_SEQUENCES.
-- Satu counter untuk kedua sisi, jadi aplikasi dan package tidak mungkin
-- mengeluarkan id yang sama. Lihat schema.md bagian 7.1.
--
-- Nama sequence adalah NILAI kolom, bukan identifier Oracle, jadi tidak
-- kena batas 30 byte dan ditulis lengkap {TABEL}_{KOLOM}_SEQ.
--
-- Migration Laravel menulis empat baris yang sama (schema.md bagian 6).
-- Kedua jalur aman dijalankan dua kali: insert di sini dilewati kalau
-- namanya sudah ada, trigger-nya CREATE OR REPLACE.
-- =====================================================================

-- ---------------------------------------------------------------------
-- B1. Baris counter.
--
-- HMMS_NUMBER_FORMAT = 'TM9' : format "text minimum" Oracle - angka apa
--   adanya, tanpa padding, tanpa lebar yang bisa jebol. SysIdHelper
--   memberi padding sebanyak karakter '0' di string ini, dan TM9 tidak
--   punya satu pun, jadi hasilnya angka polos. JANGAN tulis '0': kelihatan
--   sama artinya lalu berhenti di 9, karena TO_CHAR(10, '0') = '#'.
-- HMMS_SEQ_TYPE = 0 : SysId, tanpa prefix. Tipe 1 (TrnNo, berprefix) di
--   sini adalah SCAR_REQ_NO, dan nomor itu TIDAK dibuat lewat mekanisme ini
--   karena counter-nya reset per prefix per tahun - lihat schema.md 7.2.
-- HMMS_MAX_VALUE = NULL : tanpa plafon. Sequence berplafon hanya muncul
--   sebagai insert gagal bertahun-tahun kemudian, dan ini surrogate key.
-- ---------------------------------------------------------------------
INSERT INTO MGTHRIS.HM_MST_SEQUENCES (
    HMMS_SEQ_ID, HMMS_SEQ_NAME, HMMS_SEQ_DESC, HMMS_TABLE_NAME, HMMS_COLUMN_NAME,
    HMMS_START_WITH, HMMS_INCREMENT_BY, HMMS_MIN_VALUE, HMMS_MAX_VALUE,
    HMMS_LAST_VALUE, HMMS_NUMBER_FORMAT, HMMS_PREFIX, HMMS_SEQ_TYPE,
    HMMS_CREATED_BY, HMMS_CREATED_AT)
SELECT (SELECT NVL(MAX(HMMS_SEQ_ID), 0) FROM MGTHRIS.HM_MST_SEQUENCES) + ROWNUM,
       s.nm, s.ds, s.tb, s.cl, 1, 1, 1, NULL, 0, 'TM9', NULL, 0, 'SYSTEM', SYSDATE
FROM (
         SELECT 'SALES_CTL_APPR_REQUEST_SCAR_SYS_ID_SEQ' nm,
                'Sales Control SALES_CTL_APPR_REQUEST.SCAR_SYS_ID primary key' ds,
                'sales_ctl_appr_request' tb, 'scar_sys_id' cl FROM DUAL
         UNION ALL SELECT 'SALES_CTL_APPR_LINE_SCAL_SYS_ID_SEQ',
                          'Sales Control SALES_CTL_APPR_LINE.SCAL_SYS_ID primary key',
                          'sales_ctl_appr_line', 'scal_sys_id' FROM DUAL
         UNION ALL SELECT 'SALES_MIN_PRICE_SMP_SYS_ID_SEQ',
                          'Sales Control SALES_MIN_PRICE.SMP_SYS_ID primary key',
                          'sales_min_price', 'smp_sys_id' FROM DUAL
         UNION ALL SELECT 'SALES_MIN_PRICE_CHECK_LOG_SMPCL_SYS_ID_SEQ',
                          'Sales Control SALES_MIN_PRICE_CHECK_LOG.SMPCL_SYS_ID primary key',
                          'sales_min_price_check_log', 'smpcl_sys_id' FROM DUAL
     ) s
WHERE NOT EXISTS (
    SELECT 1 FROM MGTHRIS.HM_MST_SEQUENCES x WHERE x.HMMS_SEQ_NAME = s.nm);

COMMIT;

-- ---------------------------------------------------------------------
-- B2. Trigger sys id, satu per tabel.
--
-- Mengisi primary key hanya kalau insert-nya tidak menyertakan nilai.
-- p_date_format => NULL supaya hasilnya angka sequence polos, bukan
-- didahului tanggal.
--
-- Dua alasan trigger ini ada, padahal Laravel membuat id-nya sendiri:
--   1. Package MGTDAT.PKG_MGT_PRICE_CTRL insert ke SALES_MIN_PRICE_CHECK_LOG
--      dan tidak bisa memanggil PHP. Dengan trigger ini package cukup tidak
--      menyebut SMPCL_SYS_ID di insert-nya. Ini mengganti NEXTVAL yang dulu
--      ada di sana, sekaligus menghapus satu grant (bagian F1).
--   2. Insert manual - smoke test bagian G, DBA membetulkan satu baris -
--      tetap dapat key yang benar tanpa tahu mekanismenya.
--
-- Laravel tetap membuat id-nya lewat SysIdHelper, bukan menyandarkan diri
-- pada trigger, karena id header dibutuhkan SEBELUM insert (jadi parent key
-- baris-barisnya) dan driver ini tidak punya RETURNING di 11g.
--
-- Nama trigger ADALAH identifier: dua di antaranya pas 30 byte, dan
-- CHECK_LOG dipendekkan jadi LOG supaya masuk. Lihat schema.md bagian 0.
-- ---------------------------------------------------------------------
CREATE OR REPLACE TRIGGER MGTHRIS.SALES_CTL_APPR_REQ_SYS_ID_TRG
  BEFORE INSERT ON MGTHRIS.SALES_CTL_APPR_REQUEST
  FOR EACH ROW WHEN (new.SCAR_SYS_ID IS NULL)
BEGIN
  :new.SCAR_SYS_ID := MGTHRIS.PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO(
      p_seq_name    => 'SALES_CTL_APPR_REQUEST_SCAR_SYS_ID_SEQ',
      p_user_name   => 'SYSTEM',
      p_date_format => NULL);
END;
/
SHOW ERRORS;

CREATE OR REPLACE TRIGGER MGTHRIS.SALES_CTL_APPR_LINE_SYS_ID_TRG
  BEFORE INSERT ON MGTHRIS.SALES_CTL_APPR_LINE
  FOR EACH ROW WHEN (new.SCAL_SYS_ID IS NULL)
BEGIN
  :new.SCAL_SYS_ID := MGTHRIS.PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO(
      p_seq_name    => 'SALES_CTL_APPR_LINE_SCAL_SYS_ID_SEQ',
      p_user_name   => 'SYSTEM',
      p_date_format => NULL);
END;
/
SHOW ERRORS;

CREATE OR REPLACE TRIGGER MGTHRIS.SALES_MIN_PRICE_SYS_ID_TRG
  BEFORE INSERT ON MGTHRIS.SALES_MIN_PRICE
  FOR EACH ROW WHEN (new.SMP_SYS_ID IS NULL)
BEGIN
  :new.SMP_SYS_ID := MGTHRIS.PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO(
      p_seq_name    => 'SALES_MIN_PRICE_SMP_SYS_ID_SEQ',
      p_user_name   => 'SYSTEM',
      p_date_format => NULL);
END;
/
SHOW ERRORS;

CREATE OR REPLACE TRIGGER MGTHRIS.SALES_MIN_PRICE_LOG_SYS_ID_TRG
  BEFORE INSERT ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG
  FOR EACH ROW WHEN (new.SMPCL_SYS_ID IS NULL)
BEGIN
  :new.SMPCL_SYS_ID := MGTHRIS.PKG_HM_SEQUENCES.GET_NEXT_SEQ_NO(
      p_seq_name    => 'SALES_MIN_PRICE_CHECK_LOG_SMPCL_SYS_ID_SEQ',
      p_user_name   => 'SYSTEM',
      p_date_format => NULL);
END;
/
SHOW ERRORS;

-- Cek: empat baris counter, empat trigger ENABLED.
-- SELECT HMMS_SEQ_NAME, HMMS_LAST_VALUE, HMMS_NUMBER_FORMAT, HMMS_SEQ_TYPE
--   FROM MGTHRIS.HM_MST_SEQUENCES WHERE HMMS_SEQ_NAME LIKE 'SALES\_%' ESCAPE '\';
-- SELECT TRIGGER_NAME, STATUS, TABLE_NAME FROM USER_TRIGGERS
--  WHERE TRIGGER_NAME LIKE 'SALES%SYS_ID_TRG';


-- =====================================================================
-- BAGIAN C - KONFIGURASI DAN MESSAGE REGISTRY  (tetap di MGTDAT)
--
-- Dua tabel ini milik Orion, tidak ikut pindah. Aplikasi hanya membaca
-- IM_VS_STATIC_VALUE; IM_APP_ERROR_MESSAGE hanya dipakai Forms.
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
-- BAGIAN D - PACKAGE  (di MGTDAT, membaca 4 tabel di MGTHRIS)
--
-- JALANKAN BAGIAN F1 DULU. Package ini definer's rights: privilege lewat
-- role tidak terlihat, jadi tanpa grant langsung ke MGTDAT package tetap
-- compile VALID lalu gagal ORA-00942 pada approval SO pertama.
-- Setelah compile, cek: SELECT STATUS FROM USER_OBJECTS
--                        WHERE OBJECT_NAME = 'PKG_MGT_PRICE_CTRL';
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
      o_smp_sys_id OUT NUMBER,
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
      o_smp_sys_id OUT NUMBER,
      o_min_price  OUT NUMBER,
      o_tolerance  OUT NUMBER)
IS
    v_group VARCHAR2(20);
BEGIN
    o_smp_sys_id := NULL;
    o_min_price  := NULL;
    o_tolerance  := 0;

    v_group := F_GET_ITEM_GROUP(p_item_code);

BEGIN
SELECT SMP_SYS_ID, SMP_MIN_PRICE_USD, NVL(SMP_TOLERANCE_PCT,0)
INTO o_smp_sys_id, o_min_price, o_tolerance
FROM (SELECT SMP_SYS_ID, SMP_MIN_PRICE_USD, SMP_TOLERANCE_PCT
      FROM MGTHRIS.SALES_MIN_PRICE
      WHERE SMP_STATUS = 'APPROVED'
        AND TRUNC(p_txn_dt) >= SMP_VALID_FROM
        AND TRUNC(p_txn_dt) <= NVL(SMP_VALID_TO, TO_DATE('31-12-2099','DD-MM-YYYY'))
        AND SMP_UOM_CODE = p_uom_code
        AND SMP_GRADE_CODE_1 IN ('*', p_grade_1)
        AND SMP_GRADE_CODE_2 IN ('*', p_grade_2)
        AND (   (SMP_SCOPE_LEVEL = 'ITEM'  AND SMP_SCOPE_VALUE = p_item_code)
          OR (SMP_SCOPE_LEVEL = 'GROUP' AND SMP_SCOPE_VALUE = v_group)
          OR (SMP_SCOPE_LEVEL = 'ALL'))
      ORDER BY CASE SMP_SCOPE_LEVEL
                   WHEN 'ITEM'  THEN 100
                   WHEN 'GROUP' THEN 50
                   ELSE 0 END
                   + CASE WHEN SMP_GRADE_CODE_2 <> '*' THEN 2 ELSE 0 END
                   + CASE WHEN SMP_GRADE_CODE_1 <> '*' THEN 1 ELSE 0 END DESC,
               SMP_VALID_FROM DESC)
WHERE ROWNUM = 1;
EXCEPTION
      WHEN NO_DATA_FOUND THEN
        o_smp_sys_id := NULL;
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
    v_scar_sys_id NUMBER;
BEGIN
SELECT SCAR_SYS_ID
INTO v_scar_sys_id
FROM (SELECT SCAR_SYS_ID
      FROM MGTHRIS.SALES_CTL_APPR_LINE, MGTHRIS.SALES_CTL_APPR_REQUEST
      WHERE SCAL_SCAR_SYS_ID = SCAR_SYS_ID
        AND SCAR_CTRL_TYPE  = 'MINPRICE'
        AND SCAR_STATUS     = 'APPROVED'
        AND TRUNC(SYSDATE) <= NVL(SCAR_VALID_TO, TO_DATE('31-12-2099','DD-MM-YYYY'))
        AND SCAL_SOI_SYS_ID = p_soi_sys_id
        AND SCAL_APPROVED_RATE = p_rate
        AND NVL(SCAL_APPROVED_QTY_BU,0) >= NVL(p_qty_bu,0)
      ORDER BY SCAR_APPR_DT DESC)
WHERE ROWNUM = 1;

RETURN v_scar_sys_id;
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
    v_smp_sys_id  NUMBER;
    v_min_price   NUMBER;
    v_tolerance   NUMBER;
    v_rate_usd    NUMBER;
    v_net_usd     NUMBER;
    v_floor       NUMBER;
    v_scar_sys_id  NUMBER;
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
                      v_smp_sys_id, v_min_price, v_tolerance);

      v_rate_usd := ROUND(r.SOI_RATE / v_divisor, 6);
      v_net_usd  := ROUND((r.SOI_RATE * (1 - r.SOI_DISC_PERC/100)) / v_divisor, 6);
      v_scar_sys_id := NULL;

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
          v_scar_sys_id := F_GET_OVERRIDE(r.SOI_SYS_ID, r.SOI_RATE, r.SOI_QTY_BU);

          IF v_scar_sys_id IS NOT NULL THEN
            v_result := 'OVERRIDE';
          ELSIF p_ref_sys_id IS NOT NULL AND r.SOI_SOI_SYS_ID IS NOT NULL THEN
            -- Warisan dari dokumen induk (STA -> ESC).
            -- VERIFIKASI: pemetaan baris diasumsikan lewat SOI_SOI_SYS_ID.
            v_scar_sys_id := F_GET_OVERRIDE(r.SOI_SOI_SYS_ID, r.SOI_RATE, r.SOI_QTY_BU);
            IF v_scar_sys_id IS NOT NULL THEN
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

      -- SMPCL_SYS_ID sengaja TIDAK disebut: trigger
      -- SALES_MIN_PRICE_LOG_SYS_ID_TRG (bagian B2) yang mengisinya dari
      -- HM_MST_SEQUENCES. Dulu di sini ada NEXTVAL.
INSERT INTO MGTHRIS.SALES_MIN_PRICE_CHECK_LOG (
    SMPCL_SOH_SYS_ID, SMPCL_SOI_SYS_ID, SMPCL_TXN_CODE,
    SMPCL_DOC_NO, SMPCL_DOC_DT, SMPCL_ITEM_CODE, SMPCL_GRADE_CODE_1,
    SMPCL_GRADE_CODE_2, SMPCL_UOM_CODE, SMPCL_QTY_BU, SMPCL_CURR_CODE,
    SMPCL_RATE, SMPCL_EXG_DIVISOR, SMPCL_EXG_RATE_DT, SMPCL_EXG_RATE_SRC,
    SMPCL_RATE_USD, SMPCL_NET_RATE_USD, SMPCL_HAS_DISCOUNT,
    SMPCL_SMP_SYS_ID, SMPCL_MIN_PRICE_USD, SMPCL_RESULT, SMPCL_SCAR_SYS_ID,
    SMPCL_APPR_UID, SMPCL_CR_DT)
VALUES (
           p_soh_sys_id, r.SOI_SYS_ID, p_txn_code,
           p_doc_no, p_doc_dt, r.SOI_ITEM_CODE, r.SOI_GRADE_CODE_1,
           r.SOI_GRADE_CODE_2, r.SOI_UOM_CODE, r.SOI_QTY_BU, p_curr_code,
           r.SOI_RATE, v_divisor, v_rate_dt, v_source,
           v_rate_usd, v_net_usd, v_has_disc,
           v_smp_sys_id, v_min_price, v_result, v_scar_sys_id,
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
-- ---------------------------------------------------------------------
-- F1. MGTHRIS -> MGTDAT.  JALANKAN SEBELUM BAGIAN D.
--
-- Package dimiliki MGTDAT dan jalan dengan definer's rights, jadi ia hanya
-- melihat privilege yang diberikan LANGSUNG ke MGTDAT. Privilege lewat role
-- TIDAK terlihat: package compile VALID, lalu gagal ORA-00942 waktu jalan,
-- padahal DBA bisa query tabelnya dengan santai dari SQL*Plus.
-- Jangan berikan lewat role. Jangan berikan UPDATE atau DELETE.
-- Jalankan sebagai MGTHRIS:
-- ---------------------------------------------------------------------
-- GRANT SELECT ON MGTHRIS.SALES_CTL_APPR_REQUEST        TO MGTDAT;
-- GRANT SELECT ON MGTHRIS.SALES_CTL_APPR_LINE           TO MGTDAT;
-- GRANT SELECT ON MGTHRIS.SALES_MIN_PRICE               TO MGTDAT;
-- GRANT INSERT ON MGTHRIS.SALES_MIN_PRICE_CHECK_LOG     TO MGTDAT;
--
-- Empat grant, tidak ada yang kelima. MGTDAT tidak perlu akses apa pun ke
-- HM_MST_SEQUENCES atau PKG_HM_SEQUENCES: trigger sys id milik MGTHRIS dan
-- jalan dengan privilege MGTHRIS, jadi ia yang mengambil counter-nya.
--
-- Cek hasilnya sebagai MGTDAT - view ini hanya menampilkan grant langsung:
-- SELECT TABLE_NAME, PRIVILEGE, GRANTOR FROM USER_TAB_PRIVS_RECD
--  WHERE OWNER = 'MGTHRIS' AND TABLE_NAME LIKE 'SALES_%';

-- ---------------------------------------------------------------------
-- F2. MGTDAT -> MGTHRIS.  Untuk aplikasi Laravel.
--
-- User Laravel adalah MGTHRIS sendiri, jadi 4 tabel baru tidak perlu grant.
-- Yang perlu hanya akses baca ke Orion dan execute package.
-- Sebagian mungkin sudah ada - modul Finance sudah membaca MGTDAT hari ini.
-- Cek dulu sebelum minta ke DBA. Jalankan sebagai MGTDAT:
-- ---------------------------------------------------------------------
-- GRANT SELECT  ON MGTDAT.OT_SO_HEAD         TO MGTHRIS;
-- GRANT SELECT  ON MGTDAT.OT_SO_ITEM         TO MGTHRIS;
-- GRANT SELECT  ON MGTDAT.OM_ITEM            TO MGTHRIS;
-- GRANT SELECT  ON MGTDAT.OM_CUSTOMER        TO MGTHRIS;   -- kalau layar butuh nama customer
-- GRANT SELECT  ON MGTDAT.IM_VS_STATIC_VALUE TO MGTHRIS;
-- GRANT EXECUTE ON MGTDAT.PKG_MGT_PRICE_CTRL TO MGTHRIS;
--
-- CATATAN: dulu SALES_MIN_PRICE_CHECK_LOG dijaga read-only untuk aplikasi
-- dengan cara TIDAK memberi grant INSERT. Sekarang tabel itu milik MGTHRIS,
-- jadi penjagaannya pindah ke kode (model guard + test). Lihat schema.md 1.1.


-- =====================================================================
-- BAGIAN G - SMOKE TEST (jalankan di TEST, bukan produksi)
-- =====================================================================
/*
-- G0. AKSES LINTAS SKEMA. Jalankan sebagai MGTDAT, PALING AWAL.
--     Kalau salah satu gagal, F1 belum jalan atau diberikan lewat role.
--     Percuma menjalankan G1-G4 sebelum ini lolos.
SELECT COUNT(*) FROM MGTHRIS.SALES_MIN_PRICE;
SELECT COUNT(*) FROM MGTHRIS.SALES_CTL_APPR_REQUEST;
SELECT COUNT(*) FROM MGTHRIS.SALES_CTL_APPR_LINE;

-- Tanpa SMPCL_SYS_ID: kalau trigger B2 jalan, key-nya terisi sendiri.
-- Baris ini sekaligus membuktikan trigger MGTHRIS bisa mengambil counter
-- saat insert-nya datang dari MGTDAT.
INSERT INTO MGTHRIS.SALES_MIN_PRICE_CHECK_LOG (SMPCL_CR_DT) VALUES (SYSDATE);
ROLLBACK;

-- G1. Cek arah kurs. Divisor harus angka ribuan (mis. 17250).
DECLARE
  v_div NUMBER; v_dt DATE; v_src VARCHAR2(10);
BEGIN
  MGTDAT.PKG_MGT_PRICE_CTRL.P_GET_USD_DIVISOR('IDR', TRUNC(SYSDATE), v_div, v_dt, v_src);
  DBMS_OUTPUT.PUT_LINE('divisor=' || v_div || ' dt=' || v_dt || ' src=' || v_src);
END;
/

-- G2. Insert rule contoh, lalu cek resolusi.
INSERT INTO MGTHRIS.SALES_MIN_PRICE (
  SMP_SCOPE_LEVEL, SMP_SCOPE_VALUE, SMP_GRADE_CODE_1, SMP_GRADE_CODE_2,
  SMP_UOM_CODE, SMP_MIN_PRICE_USD, SMP_TOLERANCE_PCT, SMP_VALID_FROM,
  SMP_STATUS, SMP_CR_UID, SMP_CR_DT)
VALUES (
  'ITEM', '<item_code>', '*', '<grade2>',
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
SELECT SMPCL_ITEM_CODE, SMPCL_RATE, SMPCL_EXG_DIVISOR, SMPCL_EXG_RATE_SRC,
       SMPCL_RATE_USD, SMPCL_MIN_PRICE_USD, SMPCL_RESULT
  FROM MGTHRIS.SALES_MIN_PRICE_CHECK_LOG
 WHERE SMPCL_SOH_SYS_ID = <soh_sys_id>
 ORDER BY SMPCL_SYS_ID;

ROLLBACK;
*/
