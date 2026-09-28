-- =====================================================================
-- HRIS-NIK · 99_rollback.sql · Rollback Fase 1–3 (+ Org Scope section)
-- Schema  : MGTHRIS  ·  Oracle 11g
-- Pakai   : sqlplus MGTHRIS@ALTHARADEV @99_rollback.sql <yyyymmdd>
--           <yyyymmdd> = tanggal backup BAK_HM_EMP_AUTH_ORG_<yyyymmdd> (mode SCOPE / APPLY).
--           Isi '-' bila section grant tidak perlu di-restore.
-- Ref     : plan.md §Rollback
-- Catatan : - Bila 04_cutover.sql sudah jalan, blok 0a mengembalikan HMEMD_NIK karyawan
--             aktif ke NIK lama (dari map HRIS primary) SEBELUM kolom bridge di-drop.
--             Transaksi baru pasca-cutover yang menyimpan NIK baru di kolom *_NIK / actor
--             TIDAK di-restore otomatis → jalankan laporan blok 0b dan koreksi manual.
--           - HMEMD_JOIN_DATE TIDAK di-restore otomatis.
--             Join date hasil fill payroll (TMP_JOIN_FILL) bisa di-restore manual
--             dari BAK_HM_EMP_DATA_<yyyymmdd> — lihat blok opsional di bawah.
--           - Tabel backup BAK_* & tabel kerja TMP_* tidak di-drop (hapus manual setelah aman).
--           - Idempotent: objek yang tidak ada dilewati (ORA-00942/04043/04080/02443/01418/00904/02289).
-- =====================================================================
SET DEFINE ON VERIFY OFF
SET SERVEROUTPUT ON
DEFINE BAK_DATE = '&1'
WHENEVER SQLERROR EXIT SQL.SQLCODE ROLLBACK

PROMPT == 0a. (Bila cutover sudah jalan) Kembalikan HMEMD_NIK karyawan ke NIK lama ==
DECLARE
  v_cut  VARCHAR2(200);
  v_cnt  NUMBER;
BEGIN
  BEGIN
    SELECT HNCF_VALUE INTO v_cut FROM MGTHRIS.HM_NIK_CONFIG WHERE HNCF_KEY = 'CUTOVER_DATE';
  EXCEPTION WHEN OTHERS THEN v_cut := NULL;   -- tabel config tidak ada / belum cutover
  END;
  IF v_cut IS NULL THEN
    DBMS_OUTPUT.PUT_LINE('SKIP : cutover belum dijalankan');
    RETURN;
  END IF;

  DBMS_SESSION.SET_IDENTIFIER('NIK_ADMIN');
  -- Trigger HM_EMP_DATA_TRG ikut menyinkronkan HT_EMP_LEAVE_BALANCE.HTELBC_HMEMD_NIK.
  EXECUTE IMMEDIATE q'[
    UPDATE MGTHRIS.HM_EMP_DATA e
       SET e.HMEMD_NIK = (SELECT m.HENM_NIK_OLD FROM MGTHRIS.HM_EMP_NIK_MAP m
                           WHERE m.HENM_HMEMD_SYS_ID = e.HMEMD_SYS_ID
                             AND m.HENM_SOURCE = 'HRIS' AND m.HENM_IS_PRIMARY = 'Y'
                             AND ROWNUM = 1)
     WHERE e.HMEMD_NIK = e.HMEMD_NIK_NEW
       AND EXISTS (SELECT 1 FROM MGTHRIS.HM_EMP_NIK_MAP m
                    WHERE m.HENM_HMEMD_SYS_ID = e.HMEMD_SYS_ID
                      AND m.HENM_SOURCE = 'HRIS' AND m.HENM_IS_PRIMARY = 'Y')]';
  v_cnt := SQL%ROWCOUNT;
  DBMS_OUTPUT.PUT_LINE('OK   : HMEMD_NIK dikembalikan ke NIK lama, ' || v_cnt || ' baris');
  DBMS_OUTPUT.PUT_LINE('INFO : karyawan baru pasca-cutover (NEW_HIRE/REHIRE/...) tetap ber-NIK baru');
  DBMS_OUTPUT.PUT_LINE('INFO : HMEMD_USER_MACHINE_ID hasil cutover tidak dihapus (= NIK lama, tetap valid)');
  COMMIT;
  DBMS_SESSION.CLEAR_IDENTIFIER;
END;
/

PROMPT == 0b. (Bila cutover sudah jalan) Laporan transaksi pasca-cutover yang menyimpan NIK baru ==
PROMPT    Daftar kolom actor/*_NIK: impact_analysis.md §1.2. Contoh pola cek (manual per tabel):
-- SELECT COUNT(*) FROM MGTHRIS.<TABEL> t
--  WHERE t.<KOLOM_NIK> IN (SELECT HMEMD_NIK_NEW FROM MGTHRIS.HM_EMP_DATA
--                           WHERE HMEMD_NIK_REASON = 'BACKFILL' AND HMEMD_NIK_NEW IS NOT NULL);
-- Koreksi: UPDATE ... SET <KOLOM_NIK> = map HRIS primary (join via HMEMD_NIK_NEW) — lakukan
-- SEBELUM blok 1 men-drop HMEMD_NIK_NEW / HM_EMP_NIK_MAP.
PROMPT    Jalankan koreksi 0b dulu bila ada data pasca-cutover; tekan Enter untuk lanjut, Ctrl+C untuk batal.
PAUSE

PROMPT == 0. Matikan auto-NIK dulu (hentikan generate baru) ==
DECLARE
  PROCEDURE exec_ignore (p_sql VARCHAR2) IS
  BEGIN
    EXECUTE IMMEDIATE p_sql;
    DBMS_OUTPUT.PUT_LINE('OK   : ' || p_sql);
  EXCEPTION WHEN OTHERS THEN
    IF SQLCODE IN (-942, -4043, -4080, -2443, -1418, -904, -2289) THEN
      DBMS_OUTPUT.PUT_LINE('SKIP : ' || p_sql || ' (' || SQLCODE || ')');
    ELSE
      RAISE;
    END IF;
  END;
BEGIN
  exec_ignore('ALTER TRIGGER MGTHRIS.HM_EMP_DATA_TRG_NIK DISABLE');

  -- 1. Trigger
  exec_ignore('DROP TRIGGER MGTHRIS.HM_EMP_DATA_TRG_NIK');
  exec_ignore('DROP TRIGGER MGTHRIS.HM_EMP_DATA_TRG_NIK_UPD');

  -- 2. View
  exec_ignore('DROP VIEW MGTHRIS.V_EMP_NIK_LOOKUP');

  -- 3. Map (+ trigger & sequence)
  exec_ignore('DROP TRIGGER MGTHRIS.HM_EMP_NIK_MAP_TRG01');
  exec_ignore('DROP TABLE MGTHRIS.HM_EMP_NIK_MAP PURGE');
  exec_ignore('DROP SEQUENCE MGTHRIS.HM_EMP_NIK_MAP_SEQ');

  -- 4. Fungsi, counter, config
  exec_ignore('DROP FUNCTION MGTHRIS.FN_GEN_NIK');
  exec_ignore('DROP TABLE MGTHRIS.HM_NIK_COUNTER PURGE');
  exec_ignore('DROP TABLE MGTHRIS.HM_NIK_CONFIG PURGE');

  -- 5. Constraint Org Scope
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_AUTHORIZE_ORG DROP CONSTRAINT CK_HMEATO_HIERARCHY');

  -- 6. Index & kolom bridge (DROP COLUMN ikut menghapus UQ/CK, di-drop eksplisit agar jelas)
  exec_ignore('DROP INDEX MGTHRIS.IX_HMEMD_NIK_LTRIM');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP CONSTRAINT UQ_HMEMD_NIK_NEW');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP CONSTRAINT CK_HMEMD_NIK_NEW');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP CONSTRAINT FK_HMEMD_PREV_SYS_ID');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP CONSTRAINT CK_HMEMD_NIK_REASON');
  exec_ignore('DROP INDEX MGTHRIS.IX_HMEMD_PREV_SYS_ID');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP COLUMN HMEMD_NIK_NEW');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP COLUMN HMEMD_PREV_SYS_ID');
  exec_ignore('ALTER TABLE MGTHRIS.HM_EMP_DATA DROP COLUMN HMEMD_NIK_REASON');
END;
/

PROMPT == 7. Restore section grant HM_EMP_AUTHORIZE_ORG dari BAK_HM_EMP_AUTH_ORG_&BAK_DATE ==
DECLARE
  v_bak  VARCHAR2(30) := 'BAK_HM_EMP_AUTH_ORG_' || '&BAK_DATE';
  v_cnt  NUMBER;
BEGIN
  IF '&BAK_DATE' = '-' THEN
    DBMS_OUTPUT.PUT_LINE('SKIP : restore section (parameter -)');
    RETURN;
  END IF;

  SELECT COUNT(*) INTO v_cnt FROM user_tables WHERE table_name = v_bak;
  IF v_cnt = 0 THEN
    RAISE_APPLICATION_ERROR(-20990, 'Tabel backup ' || v_bak || ' tidak ditemukan');
  END IF;

  EXECUTE IMMEDIATE
    'MERGE INTO MGTHRIS.HM_EMP_AUTHORIZE_ORG t
     USING MGTHRIS.' || v_bak || ' b
        ON (t.HMEATO_SYS_ID = b.HMEATO_SYS_ID)
      WHEN MATCHED THEN UPDATE SET t.HMEATO_HMSEC_SECT_ID = b.HMEATO_HMSEC_SECT_ID
     WHERE NVL(t.HMEATO_HMSEC_SECT_ID, ''#'') <> NVL(b.HMEATO_HMSEC_SECT_ID, ''#'')';
  DBMS_OUTPUT.PUT_LINE('OK   : section di-restore, ' || SQL%ROWCOUNT || ' baris');
  COMMIT;
END;
/

PROMPT == 8. (Opsional, manual) Restore join date hasil fill payroll ==
PROMPT    Jalankan hanya bila HR meminta join date dikembalikan ke NULL.
-- UPDATE MGTHRIS.HM_EMP_DATA e
--    SET e.HMEMD_JOIN_DATE = (SELECT b.HMEMD_JOIN_DATE FROM MGTHRIS.BAK_HM_EMP_DATA_<yyyymmdd> b
--                              WHERE b.HMEMD_SYS_ID = e.HMEMD_SYS_ID)
--  WHERE e.HMEMD_SYS_ID IN (SELECT HMEMD_SYS_ID FROM MGTHRIS.TMP_JOIN_FILL WHERE APPLIED_AT IS NOT NULL);
-- COMMIT;

PROMPT == 9. (Fase 2 saja) Restore objek payroll lintas schema ==
PROMPT    Bila fase 2 sudah jalan, jalankan DDL hasil export T0.4 (DBMS_METADATA.GET_DDL):
PROMPT      - TRG_HMEMD_TO_PAYROLL_EMP
PROMPT      - PRC_SYNC_PAYROLL_EMP
PROMPT      - JOB_SYNC_PAYROLL_EMP (DBMS_SCHEDULER, argumen p_nik_floor)
PROMPT    Perlebaran kolom VARCHAR2(5)->(15) tidak di-rollback.

PROMPT == 10. Cek sisa objek & objek invalid ==
SELECT object_name, object_type, status
  FROM user_objects
 WHERE object_name IN ('HM_NIK_CONFIG','HM_NIK_COUNTER','HM_EMP_NIK_MAP','HM_EMP_NIK_MAP_SEQ',
                       'FN_GEN_NIK','HM_EMP_DATA_TRG_NIK','HM_EMP_DATA_TRG_NIK_UPD',
                       'HM_EMP_NIK_MAP_TRG01','V_EMP_NIK_LOOKUP','IX_HMEMD_NIK_LTRIM',
                       'IX_HMEMD_PREV_SYS_ID')
    OR status <> 'VALID'
 ORDER BY object_type, object_name;

SELECT column_name SISA_KOLOM
  FROM user_tab_columns
 WHERE table_name = 'HM_EMP_DATA'
   AND column_name IN ('HMEMD_NIK_NEW','HMEMD_PREV_SYS_ID','HMEMD_NIK_REASON');

PROMPT == Selesai 99_rollback.sql (tabel BAK_* dan TMP_* masih ada) ==
UNDEFINE BAK_DATE
