-- =====================================================================
-- HRIS-NIK · 04_cutover.sql · Fase 3 — Cutover identity (NIK baru jadi ID utama)
-- Schema  : MGTHRIS  ·  Oracle 11g
-- Pakai   : sqlplus MGTHRIS@ALTHARADEV @04_cutover.sql <MODE> <yyyy-mm-dd>
--           MODE = CHECK (default aman, read-only) | APPLY
--           <yyyy-mm-dd> = tanggal cutover (hari pertama transaksi memakai NIK baru)
-- Ref     : PRD BR-08/BR-09, spec.md §11 (Cutover), plan.md Fase 3
--
-- Prinsip :
--   - Setelah cutover, HMEMD_NIK karyawan AKTIF = HMEMD_NIK_NEW → semua modul yang membaca
--     HMEMD_NIK (payroll, absensi, leave, approval, actor) otomatis memakai NIK baru.
--   - NIK lama TIDAK hilang: tersimpan di HM_EMP_NIK_MAP (HRIS, primary) + backup.
--   - FK antar tabel aman: ke-39 FK ke HM_EMP_DATA memakai HMEMD_SYS_ID, bukan NIK.
--   - Data transaksi historis (< cutover) TIDAK di-mass-update: tetap menyimpan NIK lama dan
--     di-resolve via HMEMD_SYS_ID / V_EMP_NIK_LOOKUP (resolver dua nilai).
--   - Karyawan resign tidak diubah (NIK lama tetap = identitas historisnya).
--   - HMEMD_USER_ID (login) TIDAK diubah; login dengan NIK baru via alias (spec §6).
--   - PIN mesin: HMEMD_USER_MACHINE_ID := NIK lama (LTRIM '0') bila kosong → tidak perlu re-enroll.
-- =====================================================================
SET DEFINE ON VERIFY OFF
SET SERVEROUTPUT ON SIZE UNLIMITED
SET LINESIZE 220 PAGESIZE 200
DEFINE MODE     = '&1'
DEFINE CUT_DATE = '&2'
WHENEVER SQLERROR EXIT SQL.SQLCODE ROLLBACK

PROMPT == C0. Prasyarat (semua harus 0 / OK sebelum APPLY) ==

PROMPT -- C0.1 Flag AUTO_ENABLED harus Y (fase 2 berjalan: karyawan baru sudah ber-NIK baru)
SELECT HNCF_KEY, HNCF_VALUE,
       CASE WHEN HNCF_KEY = 'AUTO_ENABLED' AND HNCF_VALUE = 'Y' THEN 'OK'
            WHEN HNCF_KEY = 'CUTOVER_DATE' AND HNCF_VALUE IS NULL THEN 'OK'
            ELSE 'BLOCK' END STATUS
  FROM MGTHRIS.HM_NIK_CONFIG
 WHERE HNCF_KEY IN ('AUTO_ENABLED','CUTOVER_DATE');

PROMPT -- C0.2 [0] Kolom payroll lintas schema masih VARCHAR2(<10) (NIK 10 digit akan terpotong)
SELECT owner, table_name, column_name, data_length
  FROM all_tab_columns
 WHERE owner IN ('MGTDAT','MGTAPPS','MGTHRIS')
   AND (column_name LIKE '%NIK%' OR column_name LIKE '%SUB_ACNT%')
   AND data_type LIKE '%CHAR%'
   AND data_length < 10
 ORDER BY 1, 2, 3;

PROMPT -- C0.3 [0] Karyawan aktif ber-NIK_NEW tanpa map HRIS primary (NIK lama akan hilang)
SELECT e.HMEMD_SYS_ID, e.HMEMD_NIK, e.HMEMD_NIK_NEW
  FROM MGTHRIS.HM_EMP_DATA e
 WHERE e.HMEMD_RESIGN_DATE IS NULL
   AND e.HMEMD_NIK_NEW IS NOT NULL
   AND e.HMEMD_NIK IS NOT NULL
   AND e.HMEMD_NIK <> e.HMEMD_NIK_NEW
   AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                    WHERE m.HENM_HMEMD_SYS_ID = e.HMEMD_SYS_ID
                      AND m.HENM_SOURCE = 'HRIS' AND m.HENM_IS_PRIMARY = 'Y'
                      AND m.HENM_NIK_OLD = e.HMEMD_NIK);

PROMPT -- C0.4 [0] NIK_NEW bentrok dengan HMEMD_NIK karyawan lain (unik operasional per company)
SELECT a.HMEMD_HMCOMP_COMP_ID COMP, a.HMEMD_NIK_NEW, b.HMEMD_SYS_ID OTHER_SYS_ID, b.HMEMD_NIK OTHER_NIK
  FROM MGTHRIS.HM_EMP_DATA a
  JOIN MGTHRIS.HM_EMP_DATA b
    ON b.HMEMD_NIK = a.HMEMD_NIK_NEW
   AND b.HMEMD_SYS_ID <> a.HMEMD_SYS_ID
   AND NVL(b.HMEMD_HMCOMP_COMP_ID, '#') = NVL(a.HMEMD_HMCOMP_COMP_ID, '#')
 WHERE a.HMEMD_RESIGN_DATE IS NULL AND a.HMEMD_NIK_NEW IS NOT NULL;

PROMPT -- C0.5 [info] Karyawan aktif yang BELUM punya NIK_NEW (tidak ikut cutover → tetap NIK lama)
SELECT CASE WHEN HMEMD_HMCOMP_COMP_ID IS NULL THEN 'company NULL'
            WHEN HMEMD_JOIN_DATE IS NULL THEN 'join date NULL'
            ELSE 'dedup / non-survivor' END ALASAN, COUNT(*) CNT
  FROM MGTHRIS.HM_EMP_DATA
 WHERE HMEMD_RESIGN_DATE IS NULL AND HMEMD_NIK_NEW IS NULL AND NVL(HMEMD_IS_USER_APP, 0) <> 1
 GROUP BY CASE WHEN HMEMD_HMCOMP_COMP_ID IS NULL THEN 'company NULL'
               WHEN HMEMD_JOIN_DATE IS NULL THEN 'join date NULL'
               ELSE 'dedup / non-survivor' END;

PROMPT -- C0.6 [info] Anomali status: kode 00 (Resign) tanpa resign date ikut dianggap AKTIF
PROMPT    DEV: 250 baris. HR wajib melengkapi HMEMD_RESIGN_DATE dulu agar tidak ikut di-cutover.
SELECT NVL(HMEMD_HMEMPS_CODE, '(NULL)') STATUS, COUNT(*) CNT
  FROM MGTHRIS.HM_EMP_DATA
 WHERE HMEMD_RESIGN_DATE IS NULL AND HMEMD_NIK_NEW IS NOT NULL AND NVL(HMEMD_IS_USER_APP, 0) <> 1
 GROUP BY HMEMD_HMEMPS_CODE ORDER BY 1;

PROMPT -- C0.7 [info] Kandidat cutover
SELECT HMEMD_HMCOMP_COMP_ID COMP, COUNT(*) KANDIDAT,
       SUM(CASE WHEN HMEMD_USER_MACHINE_ID IS NULL THEN 1 ELSE 0 END) PIN_AKAN_DIISI
  FROM MGTHRIS.HM_EMP_DATA
 WHERE HMEMD_RESIGN_DATE IS NULL
   AND NVL(HMEMD_HMEMPS_CODE, '#') <> '00'
   AND HMEMD_NIK_NEW IS NOT NULL
   AND NVL(HMEMD_NIK, '#') <> HMEMD_NIK_NEW
 GROUP BY HMEMD_HMCOMP_COMP_ID;

PROMPT == C1. APPLY ==
DECLARE
  v_bak   VARCHAR2(30) := 'BAK_HM_EMP_DATA_CUT_' || TO_CHAR(SYSDATE, 'YYYYMMDD');
  v_flag  VARCHAR2(200);
  v_cut   VARCHAR2(200);
  v_date  DATE;
  v_cnt   NUMBER;
BEGIN
  IF UPPER('&MODE') <> 'APPLY' THEN
    DBMS_OUTPUT.PUT_LINE('MODE=&MODE → hanya cek prasyarat, tidak ada perubahan.');
    RETURN;
  END IF;

  v_date := TO_DATE('&CUT_DATE', 'YYYY-MM-DD');

  SELECT MAX(DECODE(HNCF_KEY, 'AUTO_ENABLED', HNCF_VALUE)),
         MAX(DECODE(HNCF_KEY, 'CUTOVER_DATE', HNCF_VALUE))
    INTO v_flag, v_cut
    FROM MGTHRIS.HM_NIK_CONFIG;
  IF NVL(v_flag, 'N') <> 'Y' THEN
    RAISE_APPLICATION_ERROR(-20910, 'AUTO_ENABLED harus Y sebelum cutover');
  END IF;
  IF v_cut IS NOT NULL THEN
    RAISE_APPLICATION_ERROR(-20911, 'Cutover sudah pernah dijalankan (' || v_cut || ')');
  END IF;

  -- Gate map (C0.3)
  SELECT COUNT(*) INTO v_cnt
    FROM MGTHRIS.HM_EMP_DATA e
   WHERE e.HMEMD_RESIGN_DATE IS NULL AND e.HMEMD_NIK_NEW IS NOT NULL
     AND e.HMEMD_NIK IS NOT NULL AND e.HMEMD_NIK <> e.HMEMD_NIK_NEW
     AND NOT EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                      WHERE m.HENM_HMEMD_SYS_ID = e.HMEMD_SYS_ID AND m.HENM_SOURCE = 'HRIS'
                        AND m.HENM_IS_PRIMARY = 'Y' AND m.HENM_NIK_OLD = e.HMEMD_NIK);
  IF v_cnt > 0 THEN
    RAISE_APPLICATION_ERROR(-20912, v_cnt || ' karyawan aktif tanpa map HRIS primary (C0.3)');
  END IF;

  -- 1. Backup kolom identitas
  EXECUTE IMMEDIATE 'CREATE TABLE MGTHRIS.' || v_bak || ' AS
    SELECT HMEMD_SYS_ID, HMEMD_NIK, HMEMD_NIK_NEW, HMEMD_USER_ID, HMEMD_USER_MACHINE_ID,
           HMEMD_HMCOMP_COMP_ID, HMEMD_RESIGN_DATE, SYSDATE AS BAK_AT
      FROM MGTHRIS.HM_EMP_DATA';
  DBMS_OUTPUT.PUT_LINE('OK   : backup ' || v_bak);

  DBMS_SESSION.SET_IDENTIFIER('NIK_ADMIN');

  -- 2. Kunci PIN mesin = NIK lama (tanpa nol depan) → mesin absensi tidak perlu re-enroll
  UPDATE MGTHRIS.HM_EMP_DATA
     SET HMEMD_USER_MACHINE_ID = LTRIM(HMEMD_NIK, '0')
   WHERE HMEMD_RESIGN_DATE IS NULL
     AND NVL(HMEMD_HMEMPS_CODE, '#') <> '00'
     AND HMEMD_NIK_NEW IS NOT NULL
     AND HMEMD_NIK IS NOT NULL
     AND HMEMD_NIK <> HMEMD_NIK_NEW
     AND HMEMD_USER_MACHINE_ID IS NULL;
  DBMS_OUTPUT.PUT_LINE('OK   : PIN mesin diisi NIK lama, ' || SQL%ROWCOUNT || ' baris');

  -- 3. Cutover: HMEMD_NIK := HMEMD_NIK_NEW (HMEMD_USER_ID tidak diubah)
  --    HM_EMP_DATA_TRG ikut memperbarui HT_EMP_LEAVE_BALANCE.HTELBC_HMEMD_NIK.
  --    TRG_HMEMD_TO_PAYROLL_EMP (bila update-aware) meneruskan ke MGTDAT.
  UPDATE MGTHRIS.HM_EMP_DATA
     SET HMEMD_NIK = HMEMD_NIK_NEW
   WHERE HMEMD_RESIGN_DATE IS NULL
     AND NVL(HMEMD_HMEMPS_CODE, '#') <> '00'
     AND HMEMD_NIK_NEW IS NOT NULL
     AND NVL(HMEMD_NIK, '#') <> HMEMD_NIK_NEW;
  v_cnt := SQL%ROWCOUNT;
  DBMS_OUTPUT.PUT_LINE('OK   : HMEMD_NIK := NIK_NEW, ' || v_cnt || ' karyawan aktif');

  -- 4. Tandai tanggal cutover (dipakai resolver: transaksi < tanggal ini ber-NIK lama)
  UPDATE MGTHRIS.HM_NIK_CONFIG
     SET HNCF_VALUE = TO_CHAR(v_date, 'YYYY-MM-DD'),
         HNCF_MODIFIED_BY = USER, HNCF_MODIFIED_TIMESTAMP = SYSDATE
   WHERE HNCF_KEY = 'CUTOVER_DATE';

  DBMS_SESSION.CLEAR_IDENTIFIER;
  COMMIT;
  DBMS_OUTPUT.PUT_LINE('DONE : cutover ' || TO_CHAR(v_date, 'YYYY-MM-DD') || ' — lanjut 03_verify.sql V22–V25');
EXCEPTION
  WHEN OTHERS THEN
    ROLLBACK;
    DBMS_SESSION.CLEAR_IDENTIFIER;
    RAISE;
END;
/

PROMPT == C2. Pasca-APPLY: leave balance harus sinkron (0 baris) ==
SELECT l.HTELBC_HMEMD_SYS_ID, l.HTELBC_HMEMD_NIK, e.HMEMD_NIK
  FROM MGTHRIS.HT_EMP_LEAVE_BALANCE l
  JOIN MGTHRIS.HM_EMP_DATA e ON e.HMEMD_SYS_ID = l.HTELBC_HMEMD_SYS_ID
 WHERE e.HMEMD_RESIGN_DATE IS NULL
   AND NVL(l.HTELBC_HMEMD_NIK, '#') <> NVL(e.HMEMD_NIK, '#')
   AND ROWNUM <= 50;

PROMPT == C3. (Opsional, keputusan HR/Finance) Transaksi OPEN yang ikut NIK baru ==
PROMPT    Default: TIDAK diubah; resolver dua nilai membaca NIK lama & baru via HMEMD_SYS_ID.
PROMPT    Bila modul tertentu hanya bisa mencari NIK tunggal (tanpa SYS_ID), update baris OPEN saja:
-- UPDATE MGTHRIS.<TABEL_TRX> t
--    SET t.<KOLOM_NIK> = (SELECT e.HMEMD_NIK FROM MGTHRIS.HM_EMP_DATA e
--                          WHERE e.HMEMD_SYS_ID = t.<KOLOM_SYS_ID>)
--  WHERE t.<STATUS> IN ('DRAFT','SUBMITTED')
--    AND t.<KOLOM_SYS_ID> IN (SELECT HMEMD_SYS_ID FROM MGTHRIS.BAK_HM_EMP_DATA_CUT_<yyyymmdd>
--                              WHERE HMEMD_NIK <> HMEMD_NIK_NEW);
-- Tabel tanpa SYS_ID (ATTENDANCES, HM_ATT_LOGS, HT_EMP_LEAVE_DTL, ...) → lihat impact_analysis §1.2.

PROMPT == Selesai 04_cutover.sql ==
UNDEFINE MODE
UNDEFINE CUT_DATE
