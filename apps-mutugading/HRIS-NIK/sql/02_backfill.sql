-- =====================================================================
-- HRIS-NIK · 02_backfill.sql · Fase 0–1 (cleansing + backfill NIK baru)
-- Schema  : MGTHRIS (baca MGTDAT.MGT_PAYROLL_EMP)  ·  Oracle 11g
-- Ref     : spec.md §4, design.md F3/F4, PRD BR-01..BR-11
-- Seri    : per (company, bulan join). Baris tanpa company tidak di-generate (R6).
--
-- Parameter 1 = mode:
--   REPORT : buat/isi tabel kerja TMP_JOIN_FILL & TMP_NIK_DEDUP + tampilkan laporan.
--            Aman diulang — hanya menambah baris baru, keputusan HR tidak ditimpa.
--   APPLY  : backup, isi join date (yang di-approve), generate HMEMD_NIK_NEW,
--            set counter, isi HM_EMP_NIK_MAP, enable trigger auto-NIK.
--            Idempoten: baris yang sudah punya NIK_NEW / map tidak disentuh.
--   SCOPE  : (fase 4, T4.8) backup grant + HMEATO_HMSEC_SECT_ID = NULL.
--            Jalankan setelah REPORT pernah dijalankan (laporan R3/R4 membaca tabel kerja).
--
-- Prasyarat: 01_ddl.sql. Catatan: UPDATE join date / NIK_NEW ikut memicu trigger AFTER UPDATE
--            existing (HM_EMP_DATA_TRG → leave balance). Nilai NIK/NAME tidak berubah → aman.
--
-- Alur   : @02_backfill.sql REPORT  → HR review tabel kerja (T0.3)
--          @02_backfill.sql APPLY   → @03_verify.sql
-- Divisi : HRIS SLO/JKT/BDG  ↔  payroll PE_DIVISION SOLO/JAKARTA/(BANDUNG)
-- KTP    : dinormalisasi REGEXP_REPLACE(ktp,'[^0-9]',''), minimal 10 digit
-- =====================================================================
SET DEFINE ON
SET VERIFY OFF
SET SERVEROUTPUT ON SIZE UNLIMITED
SET LINESIZE 220 PAGESIZE 200
WHENEVER SQLERROR EXIT SQL.SQLCODE ROLLBACK

DEFINE MODE = '&1'
PROMPT == Mode: &MODE ==

BEGIN
  IF '&MODE' NOT IN ('REPORT', 'APPLY', 'SCOPE') THEN
    RAISE_APPLICATION_ERROR(-20000, 'Mode harus REPORT | APPLY | SCOPE');
  END IF;
END;
/

-- ---------------------------------------------------------------------
-- 0. Tabel kerja (dibuat sekali; di-drop manual setelah go-live stabil)
-- ---------------------------------------------------------------------
DECLARE
  PROCEDURE create_if_missing(p_name VARCHAR2, p_ddl VARCHAR2) IS
    v NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v FROM user_tables WHERE table_name = p_name;
    IF v = 0 THEN
      EXECUTE IMMEDIATE p_ddl;
      DBMS_OUTPUT.PUT_LINE('created ' || p_name);
    END IF;
  END;
BEGIN
  -- Usulan join date untuk HMEMD_JOIN_DATE NULL (BR-06).
  -- HR: HR_JOIN_DATE = isi manual (menang atas PE_DATE_JOIN); HR_APPROVED = 'N' untuk menahan.
  create_if_missing('TMP_JOIN_FILL', q'[
    CREATE TABLE TMP_JOIN_FILL (
      HMEMD_SYS_ID   VARCHAR2(30) PRIMARY KEY,
      HMEMD_NIK      VARCHAR2(30),
      HMEMD_NAME     VARCHAR2(150),
      DIV_ID         VARCHAR2(30),
      STS_DATA       VARCHAR2(30),
      RESIGN_DATE    DATE,
      PE_NIK         VARCHAR2(15),
      PE_DATE_JOIN   DATE,
      PE_MATCH_CNT   NUMBER,
      HR_JOIN_DATE   DATE,
      HR_APPROVED    CHAR(1) DEFAULT 'N' CHECK (HR_APPROVED IN ('Y','N')),
      HR_NOTE        VARCHAR2(200),
      APPLIED_AT     DATE,
      CREATED_AT     DATE DEFAULT SYSDATE)]');

  -- Kandidat duplikat orang (BR-08, BR-11).
  --   GRP_TYPE = 'KTP_HRIS'     : >1 baris HM_EMP_DATA dengan KTP sama (rehire / dobel input / SLO↔JKT).
  --              DECISION = 'MERGE' + SURVIVOR_SYS_ID (sama untuk semua baris grup) | 'SEPARATE'.
  --              DECISION NULL → seluruh grup DITAHAN (tidak di-generate) sampai diputuskan.
  --              GRP_CLASS (otomatis): OVERLAP    = masa kerja tumpang tindih → dobel input  → saran MERGE
  --                                    SEQUENTIAL = resign lalu join lagi (rehire)       → saran SEPARATE
  --                                                 (tiap baris NIK sendiri + HMEMD_PREV_SYS_ID, BR-08)
  --                                    JOIN_NULL  = ada join date NULL → ditahan sampai TMP_JOIN_FILL beres
  --   GRP_TYPE = 'PAYROLL_XDIV' : baris payroll divisi lain yang tidak punya baris HRIS di divisinya,
  --              KTP sama dengan karyawan HRIS. DECISION = 'MERGE' → dicatat sebagai alias PAYROLL_NIK.
  create_if_missing('TMP_NIK_DEDUP', q'[
    CREATE TABLE TMP_NIK_DEDUP (
      GRP_TYPE           VARCHAR2(20) NOT NULL,
      GRP_KEY            VARCHAR2(30) NOT NULL,
      HMEMD_SYS_ID       VARCHAR2(30) NOT NULL,
      HMEMD_NIK          VARCHAR2(30),
      HMEMD_NAME         VARCHAR2(150),
      DIV_ID             VARCHAR2(30),
      STS_DATA           VARCHAR2(30),
      JOIN_DATE          DATE,
      RESIGN_DATE        DATE,
      PE_NIK             VARCHAR2(15) DEFAULT '-' NOT NULL,
      PE_DIVISION        VARCHAR2(30),
      PE_SUB_ACNT        VARCHAR2(30),
      PE_DATE_JOIN       DATE,
      COMP_ID            VARCHAR2(30),
      GRP_CLASS          VARCHAR2(12),
      SUGGESTED_DECISION VARCHAR2(10),
      SUGGESTED_SURVIVOR VARCHAR2(30),
      DECISION           VARCHAR2(10) CHECK (DECISION IN ('MERGE','SEPARATE')),
      SURVIVOR_SYS_ID    VARCHAR2(30),
      HR_NOTE            VARCHAR2(200),
      CREATED_AT         DATE DEFAULT SYSDATE,
      CONSTRAINT PK_TMP_NIK_DEDUP PRIMARY KEY (GRP_TYPE, GRP_KEY, HMEMD_SYS_ID, PE_NIK))]');

  -- Tabel kerja versi lama (sebelum seri per company) → tambah kolom baru
  FOR c IN (SELECT 'COMP_ID VARCHAR2(30)' AS DDL, 'COMP_ID' AS COL FROM DUAL
            UNION ALL SELECT 'GRP_CLASS VARCHAR2(12)', 'GRP_CLASS' FROM DUAL
            UNION ALL SELECT 'SUGGESTED_DECISION VARCHAR2(10)', 'SUGGESTED_DECISION' FROM DUAL) LOOP
    DECLARE v NUMBER;
    BEGIN
      SELECT COUNT(*) INTO v FROM user_tab_columns WHERE table_name = 'TMP_NIK_DEDUP' AND column_name = c.COL;
      IF v = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE TMP_NIK_DEDUP ADD (' || c.DDL || ')';
        DBMS_OUTPUT.PUT_LINE('TMP_NIK_DEDUP + ' || c.COL);
      END IF;
    END;
  END LOOP;
END;
/

-- =====================================================================
-- REPORT
-- =====================================================================
BEGIN
  IF '&MODE' <> 'REPORT' THEN RETURN; END IF;

  -- 1. Join date NULL → usulan dari payroll (divisi sama, PE_NIK = NIK atau sama tanpa nol depan)
  INSERT INTO TMP_JOIN_FILL (HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NAME, DIV_ID, STS_DATA, RESIGN_DATE,
                             PE_NIK, PE_DATE_JOIN, PE_MATCH_CNT, HR_APPROVED, HR_NOTE)
  SELECT e.HMEMD_SYS_ID, e.HMEMD_NIK, e.HMEMD_NAME, e.HMEMD_HMDIV_DIV_ID, e.HMEMD_STS_DATA, e.HMEMD_RESIGN_DATE,
         p.PE_NIK, p.PE_DATE_JOIN, p.CNT,
         CASE WHEN p.CNT_DATE = 1 AND p.PE_DATE_JOIN IS NOT NULL THEN 'Y' ELSE 'N' END,
         CASE WHEN p.CNT IS NULL        THEN 'Tidak ada di payroll - isi HR_JOIN_DATE'
              WHEN p.CNT_DATE > 1       THEN 'Payroll ambigu (' || p.CNT || ' baris, tanggal beda) - review'
              WHEN p.PE_DATE_JOIN IS NULL THEN 'PE_DATE_JOIN kosong - isi HR_JOIN_DATE' END
    FROM MGTHRIS.HM_EMP_DATA e
    LEFT JOIN (SELECT DECODE(PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', PE_DIVISION) AS DIV_ID,
                      LTRIM(PE_NIK, '0') AS NIK_KEY,
                      MIN(PE_NIK) AS PE_NIK, MAX(PE_DATE_JOIN) AS PE_DATE_JOIN,
                      COUNT(*) AS CNT, COUNT(DISTINCT PE_DATE_JOIN) AS CNT_DATE
                 FROM MGTDAT.MGT_PAYROLL_EMP
                GROUP BY DECODE(PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', PE_DIVISION),
                         LTRIM(PE_NIK, '0')) p
      ON p.DIV_ID = e.HMEMD_HMDIV_DIV_ID AND p.NIK_KEY = LTRIM(e.HMEMD_NIK, '0')
   WHERE e.HMEMD_JOIN_DATE IS NULL
     AND NVL(e.HMEMD_IS_USER_APP, 0) <> 1
     AND e.HMEMD_NIK_NEW IS NULL
     AND NOT EXISTS (SELECT 1 FROM TMP_JOIN_FILL t WHERE t.HMEMD_SYS_ID = e.HMEMD_SYS_ID);
  DBMS_OUTPUT.PUT_LINE('TMP_JOIN_FILL   +' || SQL%ROWCOUNT);

  -- 2. KTP sama di HRIS. Survivor usulan: belum resign → join terbaru → SYS_ID terbesar.
  INSERT INTO TMP_NIK_DEDUP (GRP_TYPE, GRP_KEY, HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NAME, DIV_ID, STS_DATA,
                             JOIN_DATE, RESIGN_DATE, COMP_ID, SUGGESTED_SURVIVOR)
  SELECT 'KTP_HRIS', k.KTP, k.HMEMD_SYS_ID, k.HMEMD_NIK, k.HMEMD_NAME, k.HMEMD_HMDIV_DIV_ID, k.HMEMD_STS_DATA,
         k.HMEMD_JOIN_DATE, k.HMEMD_RESIGN_DATE, k.HMEMD_HMCOMP_COMP_ID,
         FIRST_VALUE(k.HMEMD_SYS_ID) OVER (PARTITION BY k.KTP
            ORDER BY CASE WHEN k.HMEMD_RESIGN_DATE IS NULL THEN 0 ELSE 1 END,
                     k.HMEMD_JOIN_DATE DESC NULLS LAST, k.HMEMD_SYS_ID DESC)
    FROM (SELECT e.*, REGEXP_REPLACE(e.HMEMD_KTP_NO, '[^0-9]', '') AS KTP,
                 COUNT(*) OVER (PARTITION BY REGEXP_REPLACE(e.HMEMD_KTP_NO, '[^0-9]', '')) AS CNT
            FROM MGTHRIS.HM_EMP_DATA e
           WHERE NVL(e.HMEMD_IS_USER_APP, 0) <> 1
             AND LENGTH(REGEXP_REPLACE(e.HMEMD_KTP_NO, '[^0-9]', '')) >= 10) k
   WHERE k.CNT > 1
     AND NOT EXISTS (SELECT 1 FROM TMP_NIK_DEDUP t
                      WHERE t.GRP_TYPE = 'KTP_HRIS' AND t.HMEMD_SYS_ID = k.HMEMD_SYS_ID);
  DBMS_OUTPUT.PUT_LINE('TMP_NIK_DEDUP KTP_HRIS     +' || SQL%ROWCOUNT);

  -- 2b. Klasifikasi grup KTP (dihitung ulang tiap REPORT; DECISION HR tidak disentuh).
  --     Company beda dalam satu grup = pindah company → selalu SEPARATE (BR-09), walau overlap.
  UPDATE TMP_NIK_DEDUP t
     SET t.GRP_CLASS =
           CASE WHEN EXISTS (SELECT 1 FROM TMP_NIK_DEDUP x
                              WHERE x.GRP_TYPE = 'KTP_HRIS' AND x.GRP_KEY = t.GRP_KEY AND x.JOIN_DATE IS NULL)
                  THEN 'JOIN_NULL'
                WHEN EXISTS (SELECT 1 FROM TMP_NIK_DEDUP a JOIN TMP_NIK_DEDUP b
                                ON b.GRP_TYPE = a.GRP_TYPE AND b.GRP_KEY = a.GRP_KEY
                               AND b.HMEMD_SYS_ID > a.HMEMD_SYS_ID
                               AND NVL(b.COMP_ID, '#') = NVL(a.COMP_ID, '#')
                             WHERE a.GRP_TYPE = 'KTP_HRIS' AND a.GRP_KEY = t.GRP_KEY
                               AND a.JOIN_DATE <= NVL(b.RESIGN_DATE, DATE '9999-12-31')
                               AND b.JOIN_DATE <= NVL(a.RESIGN_DATE, DATE '9999-12-31'))
                  THEN 'OVERLAP'
                ELSE 'SEQUENTIAL' END
   WHERE t.GRP_TYPE = 'KTP_HRIS';
  UPDATE TMP_NIK_DEDUP
     SET SUGGESTED_DECISION = DECODE(GRP_CLASS, 'OVERLAP', 'MERGE', 'SEQUENTIAL', 'SEPARATE', NULL)
   WHERE GRP_TYPE = 'KTP_HRIS';
  -- HR boleh menerima saran massal:
  --   UPDATE TMP_NIK_DEDUP SET DECISION = SUGGESTED_DECISION,
  --          SURVIVOR_SYS_ID = CASE WHEN SUGGESTED_DECISION = 'MERGE' THEN SUGGESTED_SURVIVOR END
  --    WHERE GRP_TYPE = 'KTP_HRIS' AND DECISION IS NULL AND SUGGESTED_DECISION IS NOT NULL;

  -- 3. Payroll divisi lain (tanpa baris HRIS di divisi itu) dengan KTP = karyawan HRIS
  INSERT INTO TMP_NIK_DEDUP (GRP_TYPE, GRP_KEY, HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NAME, DIV_ID, STS_DATA,
                             JOIN_DATE, RESIGN_DATE, PE_NIK, PE_DIVISION, PE_SUB_ACNT, PE_DATE_JOIN,
                             SUGGESTED_SURVIVOR)
  SELECT 'PAYROLL_XDIV', REGEXP_REPLACE(p.PE_KTP, '[^0-9]', ''),
         e.HMEMD_SYS_ID, e.HMEMD_NIK, e.HMEMD_NAME, e.HMEMD_HMDIV_DIV_ID, e.HMEMD_STS_DATA,
         e.HMEMD_JOIN_DATE, e.HMEMD_RESIGN_DATE, p.PE_NIK, p.PE_DIVISION, p.PE_SUB_ACNT, p.PE_DATE_JOIN,
         e.HMEMD_SYS_ID
    FROM MGTDAT.MGT_PAYROLL_EMP p
    JOIN MGTHRIS.HM_EMP_DATA e
      ON REGEXP_REPLACE(e.HMEMD_KTP_NO, '[^0-9]', '') = REGEXP_REPLACE(p.PE_KTP, '[^0-9]', '')
     AND e.HMEMD_HMDIV_DIV_ID <> DECODE(p.PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', p.PE_DIVISION)
     AND NVL(e.HMEMD_IS_USER_APP, 0) <> 1
   WHERE LENGTH(REGEXP_REPLACE(p.PE_KTP, '[^0-9]', '')) >= 10
     AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_DATA x
                      WHERE x.HMEMD_HMDIV_DIV_ID = DECODE(p.PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', p.PE_DIVISION)
                        AND LTRIM(x.HMEMD_NIK, '0') = LTRIM(p.PE_NIK, '0'))
     AND NOT EXISTS (SELECT 1 FROM TMP_NIK_DEDUP t
                      WHERE t.GRP_TYPE = 'PAYROLL_XDIV' AND t.HMEMD_SYS_ID = e.HMEMD_SYS_ID AND t.PE_NIK = p.PE_NIK);
  DBMS_OUTPUT.PUT_LINE('TMP_NIK_DEDUP PAYROLL_XDIV +' || SQL%ROWCOUNT);

  COMMIT;
END;
/

PROMPT
PROMPT -- R1. Kandidat akun sistem/non-karyawan (NIK non-numerik, belum HMEMD_IS_USER_APP=1) → review manual (BR-07)
SELECT HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NAME, HMEMD_HMDIV_DIV_ID DIV, HMEMD_STS_DATA STS
  FROM MGTHRIS.HM_EMP_DATA
 WHERE '&MODE' = 'REPORT'
   AND NVL(HMEMD_IS_USER_APP, 0) <> 1
   AND (HMEMD_NIK IS NULL OR NOT REGEXP_LIKE(HMEMD_NIK, '^[0-9]+$'))
 ORDER BY HMEMD_NIK;

PROMPT -- R2. NIK duplikat dalam satu company+divisi (harus dibereskan HR sebelum APPLY; tetap dapat NIK baru masing-masing)
SELECT HMEMD_HMCOMP_COMP_ID COMP, HMEMD_HMDIV_DIV_ID DIV, NVL(HMEMD_NIK, '(NULL)') NIK, COUNT(*) CNT
  FROM MGTHRIS.HM_EMP_DATA
 WHERE '&MODE' = 'REPORT'
 GROUP BY HMEMD_HMCOMP_COMP_ID, HMEMD_HMDIV_DIV_ID, HMEMD_NIK
HAVING COUNT(*) > 1;

PROMPT -- R3. Ringkasan join date
SELECT CASE WHEN HR_APPROVED = 'Y' THEN 'SIAP (payroll/HR)' ELSE 'BUTUH HR' END STATUS,
       CASE WHEN RESIGN_DATE IS NULL THEN 'aktif' ELSE 'resign' END EMP,
       COUNT(*) CNT
  FROM TMP_JOIN_FILL
 WHERE '&MODE' = 'REPORT' AND APPLIED_AT IS NULL
 GROUP BY CASE WHEN HR_APPROVED = 'Y' THEN 'SIAP (payroll/HR)' ELSE 'BUTUH HR' END,
          CASE WHEN RESIGN_DATE IS NULL THEN 'aktif' ELSE 'resign' END
 ORDER BY 1, 2;

PROMPT -- R4. Ringkasan dedup (DECISION NULL = belum diputuskan HR)
SELECT GRP_TYPE, NVL(GRP_CLASS, '-') GRP_CLASS, NVL(SUGGESTED_DECISION, '-') SARAN,
       NVL(DECISION, '(belum)') DECISION, COUNT(DISTINCT GRP_KEY) GRP, COUNT(*) ROWS_
  FROM TMP_NIK_DEDUP
 WHERE '&MODE' = 'REPORT'
 GROUP BY GRP_TYPE, GRP_CLASS, SUGGESTED_DECISION, DECISION
 ORDER BY 1, 2;

PROMPT -- R5. (company, bulan join) terpadat (cek kapasitas seri 9999 per company)
SELECT * FROM (
  SELECT HMEMD_HMCOMP_COMP_ID COMP, TO_CHAR(HMEMD_JOIN_DATE, 'YYYYMM') PERIOD, COUNT(*) CNT
    FROM MGTHRIS.HM_EMP_DATA
   WHERE '&MODE' = 'REPORT' AND HMEMD_JOIN_DATE IS NOT NULL AND NVL(HMEMD_IS_USER_APP, 0) <> 1
   GROUP BY HMEMD_HMCOMP_COMP_ID, TO_CHAR(HMEMD_JOIN_DATE, 'YYYYMM')
   ORDER BY 3 DESC)
 WHERE ROWNUM <= 5;

PROMPT -- R6. Karyawan tanpa company (tidak bisa di-generate, BR-01) → HR lengkapi
SELECT HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NAME, HMEMD_HMDIV_DIV_ID DIV
  FROM MGTHRIS.HM_EMP_DATA
 WHERE '&MODE' = 'REPORT' AND HMEMD_HMCOMP_COMP_ID IS NULL AND NVL(HMEMD_IS_USER_APP, 0) <> 1;

PROMPT -- R7. Anomali status vs resign (laporan HR; tidak memblok backfill)
PROMPT    DEV (non akun sistem): 250 baris kode 00 (Resign) tanpa resign date; 22 baris kode NULL.
SELECT NVL(HMEMD_HMEMPS_CODE, '(NULL)') STATUS,
       CASE WHEN HMEMD_RESIGN_DATE IS NULL THEN 'resign date NULL' ELSE 'resign date ada' END RESIGN,
       COUNT(*) CNT
  FROM MGTHRIS.HM_EMP_DATA
 WHERE '&MODE' = 'REPORT' AND NVL(HMEMD_IS_USER_APP, 0) <> 1
   AND (HMEMD_HMEMPS_CODE IS NULL
        OR (HMEMD_HMEMPS_CODE = '00' AND HMEMD_RESIGN_DATE IS NULL)
        OR (HMEMD_HMEMPS_CODE <> '00' AND HMEMD_RESIGN_DATE IS NOT NULL))
 GROUP BY NVL(HMEMD_HMEMPS_CODE, '(NULL)'),
          CASE WHEN HMEMD_RESIGN_DATE IS NULL THEN 'resign date NULL' ELSE 'resign date ada' END
 ORDER BY 1, 2;

-- =====================================================================
-- APPLY
-- =====================================================================
DECLARE
  v_tag  VARCHAR2(8) := TO_CHAR(SYSDATE, 'YYYYMMDD');
  v_n    NUMBER;
  v_by   VARCHAR2(30) := 'NIK_BACKFILL';

  PROCEDURE backup(p_src VARCHAR2, p_bak VARCHAR2) IS
    v NUMBER;
  BEGIN
    SELECT COUNT(*) INTO v FROM user_tables WHERE table_name = p_bak;
    IF v = 0 THEN
      EXECUTE IMMEDIATE 'CREATE TABLE ' || p_bak || ' AS SELECT * FROM MGTHRIS.' || p_src;
      DBMS_OUTPUT.PUT_LINE('backup ' || p_src || ' -> ' || p_bak);
    ELSE
      DBMS_OUTPUT.PUT_LINE('backup ' || p_bak || ' sudah ada (dipakai apa adanya)');
    END IF;
  END;
BEGIN
  IF '&MODE' <> 'APPLY' THEN RETURN; END IF;

  -- A. Backup (nama <= 30 char: BAK_HM_EMP_DATA_yyyymmdd, BAK_HM_EMP_AUTH_ORG_yyyymmdd)
  backup('HM_EMP_DATA',          'BAK_HM_EMP_DATA_' || v_tag);
  backup('HM_EMP_AUTHORIZE_ORG', 'BAK_HM_EMP_AUTH_ORG_' || v_tag);

  -- B. Validasi keputusan HR
  SELECT COUNT(*) INTO v_n
    FROM (SELECT GRP_TYPE, GRP_KEY
            FROM TMP_NIK_DEDUP
           WHERE DECISION = 'MERGE' AND GRP_TYPE = 'KTP_HRIS'
           GROUP BY GRP_TYPE, GRP_KEY
          HAVING COUNT(DISTINCT SURVIVOR_SYS_ID) <> 1
              OR COUNT(SURVIVOR_SYS_ID) <> COUNT(*)
              OR SUM(CASE WHEN SURVIVOR_SYS_ID = HMEMD_SYS_ID THEN 1 ELSE 0 END) = 0);
  IF v_n > 0 THEN
    RAISE_APPLICATION_ERROR(-20010, v_n || ' grup MERGE tidak valid: SURVIVOR_SYS_ID harus sama untuk semua baris grup dan merupakan salah satu anggota grup');
  END IF;

  -- C. Lengkapi join date (hanya kolom HMEMD_JOIN_DATE; HMEMD_NIK tidak disentuh)
  UPDATE MGTHRIS.HM_EMP_DATA e
     SET e.HMEMD_JOIN_DATE = (SELECT NVL(t.HR_JOIN_DATE, t.PE_DATE_JOIN)
                                FROM TMP_JOIN_FILL t WHERE t.HMEMD_SYS_ID = e.HMEMD_SYS_ID),
         e.HMEMD_MODIFIED_BY = v_by,
         e.HMEMD_MODIFIED_TIMESTAMP = SYSDATE
   WHERE e.HMEMD_JOIN_DATE IS NULL
     AND EXISTS (SELECT 1 FROM TMP_JOIN_FILL t
                  WHERE t.HMEMD_SYS_ID = e.HMEMD_SYS_ID
                    AND t.APPLIED_AT IS NULL
                    AND (t.HR_JOIN_DATE IS NOT NULL OR (t.HR_APPROVED = 'Y' AND t.PE_DATE_JOIN IS NOT NULL)));
  DBMS_OUTPUT.PUT_LINE('join date terisi   : ' || SQL%ROWCOUNT);

  UPDATE TMP_JOIN_FILL t SET t.APPLIED_AT = SYSDATE
   WHERE t.APPLIED_AT IS NULL
     AND EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_DATA e
                  WHERE e.HMEMD_SYS_ID = t.HMEMD_SYS_ID AND e.HMEMD_JOIN_DATE IS NOT NULL);

  -- D. Generate HMEMD_NIK_NEW (BR-05), lanjut dari counter bila re-run.
  --    Dikecualikan: akun sistem, join date NULL, baris non-survivor grup MERGE, grup KTP belum diputuskan.
  --    Seri per (company, bulan join) — BR-01/02.
  MERGE INTO MGTHRIS.HM_EMP_DATA e
  USING (
    SELECT x.HMEMD_SYS_ID,
           x.PERIOD || LPAD(NVL(c.HNC_LAST_NO, 0) + x.RN, 4, '0') AS NIK_NEW
      FROM (SELECT d.HMEMD_SYS_ID, d.HMEMD_HMCOMP_COMP_ID AS COMP_ID,
                   TO_CHAR(d.HMEMD_JOIN_DATE, 'YYYYMM') AS PERIOD,
                   ROW_NUMBER() OVER (
                     PARTITION BY d.HMEMD_HMCOMP_COMP_ID, TO_CHAR(d.HMEMD_JOIN_DATE, 'YYYYMM')
                     ORDER BY TRUNC(d.HMEMD_JOIN_DATE),
                              DECODE(d.HMEMD_HMDIV_DIV_ID, 'SLO', 1, 'JKT', 2, 'BDG', 3, 9),
                              CASE WHEN REGEXP_LIKE(d.HMEMD_NIK, '^[0-9]{1,15}$') THEN TO_NUMBER(d.HMEMD_NIK) END NULLS LAST,
                              d.HMEMD_NIK,
                              d.HMEMD_SYS_ID) AS RN
              FROM MGTHRIS.HM_EMP_DATA d
             WHERE d.HMEMD_NIK_NEW IS NULL
               AND d.HMEMD_JOIN_DATE IS NOT NULL
               AND d.HMEMD_HMCOMP_COMP_ID IS NOT NULL
               AND NVL(d.HMEMD_IS_USER_APP, 0) <> 1
               AND NOT EXISTS (SELECT 1 FROM TMP_NIK_DEDUP t          -- non-survivor
                                WHERE t.GRP_TYPE = 'KTP_HRIS' AND t.DECISION = 'MERGE'
                                  AND t.HMEMD_SYS_ID = d.HMEMD_SYS_ID
                                  AND t.SURVIVOR_SYS_ID <> d.HMEMD_SYS_ID)
               AND NOT EXISTS (SELECT 1 FROM TMP_NIK_DEDUP t          -- grup belum diputuskan
                                WHERE t.GRP_TYPE = 'KTP_HRIS' AND t.DECISION IS NULL
                                  AND t.HMEMD_SYS_ID = d.HMEMD_SYS_ID)) x
      LEFT JOIN MGTHRIS.HM_NIK_COUNTER c ON c.HNC_COMP_ID = x.COMP_ID AND c.HNC_PERIOD = x.PERIOD
  ) s
  ON (e.HMEMD_SYS_ID = s.HMEMD_SYS_ID)
  WHEN MATCHED THEN UPDATE SET e.HMEMD_NIK_NEW = s.NIK_NEW, e.HMEMD_NIK_REASON = 'BACKFILL';
  DBMS_OUTPUT.PUT_LINE('NIK_NEW generate   : ' || SQL%ROWCOUNT);

  -- E. Counter = max seri terpakai per (company, bulan)
  MERGE INTO MGTHRIS.HM_NIK_COUNTER c
  USING (SELECT HMEMD_HMCOMP_COMP_ID AS COMP_ID, SUBSTR(HMEMD_NIK_NEW, 1, 6) AS PERIOD,
                MAX(TO_NUMBER(SUBSTR(HMEMD_NIK_NEW, 7, 4))) AS LAST_NO
           FROM MGTHRIS.HM_EMP_DATA
          WHERE HMEMD_NIK_NEW IS NOT NULL
          GROUP BY HMEMD_HMCOMP_COMP_ID, SUBSTR(HMEMD_NIK_NEW, 1, 6)) s
  ON (c.HNC_COMP_ID = s.COMP_ID AND c.HNC_PERIOD = s.PERIOD)
  WHEN MATCHED THEN UPDATE SET c.HNC_LAST_NO = GREATEST(c.HNC_LAST_NO, s.LAST_NO), c.HNC_MODIFIED_TIMESTAMP = SYSDATE
  WHEN NOT MATCHED THEN INSERT (HNC_COMP_ID, HNC_PERIOD, HNC_LAST_NO) VALUES (s.COMP_ID, s.PERIOD, s.LAST_NO);
  DBMS_OUTPUT.PUT_LINE('counter comp+periode: ' || SQL%ROWCOUNT);

  -- E2. Relasi penempatan (grup KTP 'SEPARATE' = rehire/sequential): baris berikutnya menunjuk baris sebelumnya
  --     (urut join date). Hanya diisi bila masih NULL. Rehire historis tetap NIK masing-masing (BR-08/11).
  MERGE INTO MGTHRIS.HM_EMP_DATA e
  USING (SELECT HMEMD_SYS_ID, PREV_ID
           FROM (SELECT t.HMEMD_SYS_ID,
                        LAG(t.HMEMD_SYS_ID) OVER (PARTITION BY t.GRP_KEY
                                                  ORDER BY t.JOIN_DATE, t.HMEMD_SYS_ID) AS PREV_ID
                   FROM TMP_NIK_DEDUP t
                  WHERE t.GRP_TYPE = 'KTP_HRIS' AND t.DECISION = 'SEPARATE' AND t.JOIN_DATE IS NOT NULL)
          WHERE PREV_ID IS NOT NULL) s
  ON (e.HMEMD_SYS_ID = s.HMEMD_SYS_ID)
  WHEN MATCHED THEN UPDATE SET e.HMEMD_PREV_SYS_ID = s.PREV_ID
                         WHERE e.HMEMD_PREV_SYS_ID IS NULL;
  DBMS_OUTPUT.PUT_LINE('prev link SEPARATE : ' || SQL%ROWCOUNT);

  -- F. HM_EMP_NIK_MAP. Setiap blok: ROW_NUMBER untuk menghindari bentrok unique (NIK_OLD, SOURCE, DIV).
  -- F1. HRIS (primary) — NIK lama baris survivor
  INSERT INTO MGTHRIS.HM_EMP_NIK_MAP (HENM_HMEMD_SYS_ID, HENM_NIK_NEW, HENM_NIK_OLD, HENM_SOURCE,
                                      HENM_DIV_ID, HENM_IS_PRIMARY, HENM_CREATED_BY)
  SELECT HMEMD_SYS_ID, HMEMD_NIK_NEW, HMEMD_NIK, 'HRIS', HMEMD_HMDIV_DIV_ID, 'Y', v_by
    FROM (SELECT e.*, ROW_NUMBER() OVER (PARTITION BY e.HMEMD_NIK, e.HMEMD_HMDIV_DIV_ID ORDER BY e.HMEMD_SYS_ID) RN
            FROM MGTHRIS.HM_EMP_DATA e
           WHERE e.HMEMD_NIK_NEW IS NOT NULL AND e.HMEMD_NIK IS NOT NULL) e
   WHERE e.RN = 1
     AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                      WHERE m.HENM_NIK_OLD = e.HMEMD_NIK AND m.HENM_SOURCE = 'HRIS'
                        AND m.HENM_DIV_ID = e.HMEMD_HMDIV_DIV_ID);
  DBMS_OUTPUT.PUT_LINE('map HRIS           : ' || SQL%ROWCOUNT);

  -- F2. MERGED_ROW — baris non-survivor (tetap ada, NIK_NEW NULL) menunjuk ke survivor
  INSERT INTO MGTHRIS.HM_EMP_NIK_MAP (HENM_HMEMD_SYS_ID, HENM_NIK_NEW, HENM_NIK_OLD, HENM_SOURCE,
                                      HENM_DIV_ID, HENM_SRC_SYS_ID, HENM_NOTE, HENM_CREATED_BY)
  SELECT SURVIVOR_SYS_ID, NIK_NEW, HMEMD_NIK, 'MERGED_ROW', DIV_ID, HMEMD_SYS_ID, 'KTP ' || GRP_KEY, v_by
    FROM (SELECT t.*, s.HMEMD_NIK_NEW AS NIK_NEW,
                 ROW_NUMBER() OVER (PARTITION BY t.HMEMD_NIK, t.DIV_ID ORDER BY t.HMEMD_SYS_ID) RN
            FROM TMP_NIK_DEDUP t
            JOIN MGTHRIS.HM_EMP_DATA s ON s.HMEMD_SYS_ID = t.SURVIVOR_SYS_ID
           WHERE t.GRP_TYPE = 'KTP_HRIS' AND t.DECISION = 'MERGE'
             AND t.HMEMD_SYS_ID <> t.SURVIVOR_SYS_ID
             AND t.HMEMD_NIK IS NOT NULL
             AND s.HMEMD_NIK_NEW IS NOT NULL) t
   WHERE t.RN = 1
     AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                      WHERE m.HENM_NIK_OLD = t.HMEMD_NIK AND m.HENM_SOURCE = 'MERGED_ROW'
                        AND m.HENM_DIV_ID = t.DIV_ID);
  DBMS_OUTPUT.PUT_LINE('map MERGED_ROW     : ' || SQL%ROWCOUNT);


  -- F3. PAYROLL_NIK / PAYROLL_SUBACNT
  --     (a) payroll divisi sama, dicatat bila nilainya beda dari HMEMD_NIK (mis. nol depan beda)
  --     (b) payroll divisi lain yang di-MERGE HR (GRP_TYPE PAYROLL_XDIV) → alias ke survivor
  INSERT INTO MGTHRIS.HM_EMP_NIK_MAP (HENM_HMEMD_SYS_ID, HENM_NIK_NEW, HENM_NIK_OLD, HENM_SOURCE,
                                      HENM_DIV_ID, HENM_CREATED_BY)
  WITH b AS (
    SELECT e.HMEMD_SYS_ID, e.HMEMD_NIK_NEW AS NIK_NEW, e.HMEMD_NIK AS HRIS_NIK,
           p.PE_NIK, p.PE_SUB_ACNT, e.HMEMD_HMDIV_DIV_ID AS DIV_ID
      FROM MGTHRIS.HM_EMP_DATA e
      JOIN MGTDAT.MGT_PAYROLL_EMP p
        ON DECODE(p.PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', p.PE_DIVISION) = e.HMEMD_HMDIV_DIV_ID
       AND LTRIM(p.PE_NIK, '0') = LTRIM(e.HMEMD_NIK, '0')
     WHERE e.HMEMD_NIK_NEW IS NOT NULL
    UNION ALL
    SELECT s.HMEMD_SYS_ID, s.HMEMD_NIK_NEW, '#',
           t.PE_NIK, t.PE_SUB_ACNT,
           DECODE(t.PE_DIVISION, 'SOLO', 'SLO', 'JAKARTA', 'JKT', 'BANDUNG', 'BDG', t.PE_DIVISION)
      FROM TMP_NIK_DEDUP t
      JOIN MGTHRIS.HM_EMP_DATA s ON s.HMEMD_SYS_ID = NVL(t.SURVIVOR_SYS_ID, t.HMEMD_SYS_ID)
     WHERE t.GRP_TYPE = 'PAYROLL_XDIV' AND t.DECISION = 'MERGE'
       AND s.HMEMD_NIK_NEW IS NOT NULL
  ),
  a AS (
    SELECT HMEMD_SYS_ID, NIK_NEW, PE_NIK AS NIK_OLD, 'PAYROLL_NIK' AS SRC, DIV_ID
      FROM b WHERE PE_NIK IS NOT NULL AND PE_NIK <> HRIS_NIK
    UNION ALL
    SELECT HMEMD_SYS_ID, NIK_NEW, PE_SUB_ACNT, 'PAYROLL_SUBACNT', DIV_ID
      FROM b WHERE PE_SUB_ACNT IS NOT NULL
               AND PE_SUB_ACNT <> HRIS_NIK
               AND PE_SUB_ACNT <> NVL(PE_NIK, '#')
               AND PE_SUB_ACNT <> NIK_NEW
  )
  SELECT HMEMD_SYS_ID, NIK_NEW, NIK_OLD, SRC, DIV_ID, v_by
    FROM (SELECT a.*, ROW_NUMBER() OVER (PARTITION BY a.NIK_OLD, a.SRC, a.DIV_ID ORDER BY a.HMEMD_SYS_ID) RN
            FROM a) x
   WHERE x.RN = 1
     AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                      WHERE m.HENM_NIK_OLD = x.NIK_OLD AND m.HENM_SOURCE = x.SRC
                        AND NVL(m.HENM_DIV_ID, '#') = NVL(x.DIV_ID, '#'));
  DBMS_OUTPUT.PUT_LINE('map PAYROLL_*      : ' || SQL%ROWCOUNT);

  COMMIT;

  -- G. Aktifkan trigger auto-NIK (flag AUTO_ENABLED tetap 'N' → fase 1: hanya HMEMD_NIK_NEW)
  EXECUTE IMMEDIATE 'ALTER TRIGGER MGTHRIS.HM_EMP_DATA_TRG_NIK ENABLE';
  DBMS_OUTPUT.PUT_LINE('trigger HM_EMP_DATA_TRG_NIK ENABLED');

  -- Ringkasan
  FOR r IN (SELECT CASE WHEN NVL(HMEMD_IS_USER_APP, 0) = 1 THEN 'akun sistem'
                        WHEN HMEMD_NIK_NEW IS NOT NULL      THEN 'NIK_NEW terisi'
                        WHEN HMEMD_JOIN_DATE IS NULL        THEN 'tertahan: join date NULL'
                        ELSE 'tertahan: dedup/non-survivor' END AS K,
                   COUNT(*) AS CNT
              FROM MGTHRIS.HM_EMP_DATA
             GROUP BY CASE WHEN NVL(HMEMD_IS_USER_APP, 0) = 1 THEN 'akun sistem'
                           WHEN HMEMD_NIK_NEW IS NOT NULL      THEN 'NIK_NEW terisi'
                           WHEN HMEMD_JOIN_DATE IS NULL        THEN 'tertahan: join date NULL'
                           ELSE 'tertahan: dedup/non-survivor' END
             ORDER BY 1) LOOP
    DBMS_OUTPUT.PUT_LINE(RPAD(r.K, 32) || r.CNT);
  END LOOP;
END;
/

-- =====================================================================
-- SCOPE (fase 4 · T4.8) — grant existing jadi wildcard section agar perilaku tetap (spec §7)
-- =====================================================================
DECLARE
  v_bak VARCHAR2(30) := 'BAK_HM_EMP_AUTH_ORG_' || TO_CHAR(SYSDATE, 'YYYYMMDD');
  v     NUMBER;
BEGIN
  IF '&MODE' <> 'SCOPE' THEN RETURN; END IF;

  SELECT COUNT(*) INTO v FROM user_tables WHERE table_name = v_bak;
  IF v = 0 THEN
    EXECUTE IMMEDIATE 'CREATE TABLE ' || v_bak || ' AS SELECT * FROM MGTHRIS.HM_EMP_AUTHORIZE_ORG';
    DBMS_OUTPUT.PUT_LINE('backup -> ' || v_bak);
  END IF;

  UPDATE MGTHRIS.HM_EMP_AUTHORIZE_ORG
     SET HMEATO_HMSEC_SECT_ID = NULL
   WHERE HMEATO_HMSEC_SECT_ID IS NOT NULL;
  DBMS_OUTPUT.PUT_LINE('grant sect -> NULL : ' || SQL%ROWCOUNT);
  COMMIT;
END;
/

PROMPT == Selesai 02_backfill.sql (&MODE) ==
