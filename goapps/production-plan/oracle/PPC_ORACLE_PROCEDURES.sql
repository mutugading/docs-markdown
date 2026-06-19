-- =============================================================================
-- PPC Production Planning System
-- Oracle Refresh Procedures
-- Schema: MGTDAT
-- Generated: Juni 2026
-- Updated: Juni 2026 — rev 2 (TXT/TWT TQM, SPG CUT/NOT_TRANSFER/TQM)
-- =============================================================================
-- 3 procedures:
--   PRC_PPC_TXT_PRODUCTION  → MGTDAT.PPC_TXT_PRODUCTION
--   PRC_PPC_SPG_PRODUCTION  → MGTDAT.PPC_SPG_PRODUCTION
--   PRC_PPC_GRADE_ACTUAL    → MGTDAT.PPC_GRADE_ACTUAL
--
-- Schedule:
--   PRC_PPC_TXT_PRODUCTION  → setiap 10–15 menit
--   PRC_PPC_SPG_PRODUCTION  → setiap 10–15 menit
--   PRC_PPC_GRADE_ACTUAL    → setiap 15 menit
-- =============================================================================


-- =============================================================================
-- PROCEDURE 1: PRC_PPC_TXT_PRODUCTION
-- Refresh PPC_TXT_PRODUCTION dari ASPTXT.TXTTRANSFER
-- Window  : TRN_PRD_DT >= TRUNC(SYSDATE) - 7
-- Kenapa 7 hari: TQM TYPE=6 dan TYPE=7 bisa dibuat beberapa hari
--               setelah produksi TYPE=1. Window 7 hari memastikan
--               status final per bobbin selalu akurat.
--
-- ⚠️  TRN_STS: 0=FULL, 1=UNFULL (KEBALIKAN dari DOFF_OPTION SPG)
-- ⚠️  TRN_APP_REL: 0/NULL=Belum dicek, 1=DG/Rejected, 2=Normal/Released
--
-- Logic TQM (embedded di TXTTRANSFER):
--   Base produksi  : TRN_TYPE = 1 (outer query)
--   Status per pos : TRN_NO terbesar per (MERGE+PRD_DT+MCNO+DOFF+POS)
--     FINAL_TYPE != 7 AND APP_REL = 2 → NORMAL_BOBS
--     FINAL_TYPE = 7                  → DOWNGRADE_BOBS
--     APP_REL != 2 atau NULL          → PENDING_BOBS
-- =============================================================================

CREATE OR REPLACE PROCEDURE MGTDAT.PRC_PPC_TXT_PRODUCTION IS
BEGIN

    -- Step 1: Delete window 7 hari berdasarkan PROD_DATE (TRN_PRD_DT)
    DELETE FROM MGTDAT.PPC_TXT_PRODUCTION
    WHERE PROD_DATE >= TRUNC(SYSDATE) - 7;

    -- Step 2: Insert fresh aggregate
    INSERT INTO MGTDAT.PPC_TXT_PRODUCTION (
        LOT_NO, MACHINE_NO, AREA,
        TRN_DATE, TRN_SHIFT, PROD_DATE, DOFF_NO,
        TOTAL_BOBBINS,
        FULL_BOBBINS, UNFULL_BOBBINS,
        NORMAL_BOBS, DOWNGRADE_BOBS, PENDING_BOBS,
        PACK_CEK_BOBS,
        LAST_UPDATED
    )
    SELECT
        t1.TRN_MERGE                                                AS LOT_NO,
        t1.TRN_MCNO                                                 AS MACHINE_NO,
        NVL(mc.MACH_DEPT, 'TXT')                                   AS AREA,
        t1.TRN_DATE                                                 AS TRN_DATE,
        t1.TRN_SHIFT                                                AS TRN_SHIFT,
        t1.TRN_PRD_DT                                               AS PROD_DATE,
        t1.TRN_DOFF                                                 AS DOFF_NO,
        -- Total bobbin dari produksi asli TYPE=1
        COUNT(t1.TRN_POS)                                          AS TOTAL_BOBBINS,
        -- ⚠️ TRN_STS: 0=FULL, 1=UNFULL
        SUM(CASE WHEN t1.TRN_STS = 0 THEN 1 ELSE 0 END)           AS FULL_BOBBINS,
        SUM(CASE WHEN t1.TRN_STS = 1 THEN 1 ELSE 0 END)           AS UNFULL_BOBBINS,
        -- TQM Normal: latest transaction TYPE!=7 dan APP_REL=2
        SUM(CASE WHEN lf.FINAL_TYPE != 7
                  AND NVL(lf.FINAL_APP_REL, 0) = 2
                 THEN 1 ELSE 0 END)                                AS NORMAL_BOBS,
        -- TQM Downgrade Final: latest transaction TYPE=7
        SUM(CASE WHEN lf.FINAL_TYPE = 7
                 THEN 1 ELSE 0 END)                                AS DOWNGRADE_BOBS,
        -- TQM Pending: masih DG di-hold TQM atau belum dicek
        SUM(CASE WHEN NVL(lf.FINAL_APP_REL, 0) != 2
                  OR  lf.FINAL_APP_REL IS NULL
                 THEN 1 ELSE 0 END)                                AS PENDING_BOBS,
        -- Pack handover: ada TRN_PACK_CEK=1 di transaksi manapun untuk posisi ini
        SUM(CASE WHEN NVL(lf.HAS_PACK_CEK, 0) = 1
                 THEN 1 ELSE 0 END)                                AS PACK_CEK_BOBS,
        SYSDATE                                                     AS LAST_UPDATED
    FROM
        ASPTXT.TXTTRANSFER t1
        LEFT JOIN ASPTXT.TXTMACH mc ON t1.TRN_MCNO = mc.MACH_NO
        -- Subquery: status final per posisi dari TRN_NO terbesar
        -- TIDAK difilter tanggal — TYPE=6/7 dari tanggal lain harus masuk
        LEFT JOIN (
            SELECT
                it.TRN_MERGE,
                it.TRN_PRD_DT,
                it.TRN_MCNO,
                it.TRN_DOFF,
                it.TRN_POS,
                MAX(it.TRN_TYPE)
                    KEEP (DENSE_RANK LAST ORDER BY it.TRN_NO)      AS FINAL_TYPE,
                MAX(it.TRN_APP_REL)
                    KEEP (DENSE_RANK LAST ORDER BY it.TRN_NO)      AS FINAL_APP_REL,
                MAX(NVL(it.TRN_PACK_CEK, 0))                      AS HAS_PACK_CEK
            FROM ASPTXT.TXTTRANSFER it
            WHERE it.TRN_TYPE IN (1, 2, 3, 4, 6, 7, 8, 9)
            GROUP BY
                it.TRN_MERGE,
                it.TRN_PRD_DT,
                it.TRN_MCNO,
                it.TRN_DOFF,
                it.TRN_POS
        ) lf ON (
            lf.TRN_MERGE  = t1.TRN_MERGE  AND
            lf.TRN_PRD_DT = t1.TRN_PRD_DT AND
            lf.TRN_MCNO   = t1.TRN_MCNO   AND
            lf.TRN_DOFF   = t1.TRN_DOFF   AND
            lf.TRN_POS    = t1.TRN_POS
        )
    WHERE
        t1.TRN_TYPE   = 1                           -- hanya produksi asli sebagai base
        AND t1.TRN_PRD_DT >= TRUNC(SYSDATE) - 7    -- window 7 hari
    GROUP BY
        t1.TRN_MERGE,
        t1.TRN_MCNO,
        NVL(mc.MACH_DEPT, 'TXT'),
        t1.TRN_DATE,
        t1.TRN_SHIFT,
        t1.TRN_PRD_DT,
        t1.TRN_DOFF;

    COMMIT;

EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END PRC_PPC_TXT_PRODUCTION;
/


-- =============================================================================
-- PROCEDURE 2: PRC_PPC_SPG_PRODUCTION
-- Refresh PPC_SPG_PRODUCTION dari DOFFCONT + TRANSFER + TQMAPP
-- Window  : DOFF_DATE >= TRUNC(SYSDATE) - 2
--
-- DOFFCONT → qty (GROSS, WEIGHT_PER_BOB)
-- TRANSFER → flag mana yang transferred, mana yang cut
--            TRN_STATUS=2 semua masuk subquery
--            TRN_TYPE!=4 = TRANSFERRED, TRN_TYPE=4 = CUT
-- TQMAPP   → TQM result via pointer di TRANSFER:
--            TRN_APP_REL_DT=TQM_PRD_DT, TRN_APP_REL_DOFF=TQM_DOFF
--            TRN_POS=TQM_POS, TRN_BOB=TQM_BOB, TRN_MERGE=TQM_MERGE
--
-- NOT_TRANSFER = GROSS - TRANSFERRED - CUT (belum ada di TRANSFER)
-- TQM_GRADE: 1=Normal, 0=Down Grade, NULL/no match=Not Checked
--
-- Sanity checks:
--   GROSS = TRANSFERRED + CUT + NOT_TRANSFER
--   TRANSFERRED = NORMAL + DOWNGRADE + NOT_CHECKED
-- =============================================================================

CREATE OR REPLACE PROCEDURE MGTDAT.PRC_PPC_SPG_PRODUCTION IS
BEGIN

    -- Step 1: Delete window 2 hari
    DELETE FROM MGTDAT.PPC_SPG_PRODUCTION
    WHERE DOFF_DATE >= TRUNC(SYSDATE) - 2;

    -- Step 2: Insert fresh aggregate
    INSERT INTO MGTDAT.PPC_SPG_PRODUCTION (
        LOT_NO, MACHINE_LINE, DOFF_DATE, POSITION_NO, DOFF_NO,
        DOFF_OPTION, DOFF_CATEGORY, INCLUDE_IN_SUGGEST,
        GROSS_BOBBINS, WEIGHT_PER_BOB, GROSS_WEIGHT_KG,
        TRANSFERRED_BOBS, NET_WEIGHT_KG,
        CUT_BOBBINS, NOT_TRANSFER,
        NORMAL_BOBS, DOWNGRADE_BOBS, NOT_CHECKED_BOBS, TQM_DONE_BOBS,
        LAST_UPDATED
    )
    SELECT
        d.DOFF_MERGE                                                AS LOT_NO,
        d.DOFF_LINE                                                 AS MACHINE_LINE,
        d.DOFF_DATE                                                 AS DOFF_DATE,
        d.DOFF_POSITION                                             AS POSITION_NO,
        d.DOFF_NO                                                   AS DOFF_NO,
        d.DOFF_OPTION                                               AS DOFF_OPTION,
        -- Kategori doff
        CASE
            WHEN NVL(d.DOFF_REMARKS, 'NORMAL') IN ('RM', 'RMDG')
                THEN 'WASTE_RM'
            WHEN NVL(d.DOFF_REMARKS, 'NORMAL') IN ('SB','FF','PL','TG','MO','GR','FR')
                THEN 'STARTUP_BREAK'
            WHEN UPPER(NVL(d.DOFF_DESC, '')) LIKE '%CO%'
              OR NVL(d.DOFF_REMARKS, 'NORMAL') = 'CO'
                THEN 'CHANGEOVER'
            ELSE 'NORMAL'
        END                                                         AS DOFF_CATEGORY,
        -- Flag suggest: NORMAL category + FULL bobbin
        CASE
            WHEN d.DOFF_OPTION = 1
             AND NVL(d.DOFF_REMARKS, 'NORMAL')
                 NOT IN ('RM','RMDG','SB','FF','PL','TG','MO','GR','FR')
             AND NVL(UPPER(d.DOFF_DESC), '') NOT LIKE '%CO%'
             AND NVL(d.DOFF_REMARKS, 'NORMAL') <> 'CO'
                THEN 1
            ELSE 0
        END                                                         AS INCLUDE_IN_SUGGEST,
        -- Qty dari DOFFCONT (gross)
        d.DOFF_NO_END                                               AS GROSS_BOBBINS,
        d.DOFF_WT                                                   AS WEIGHT_PER_BOB,
        d.DOFF_NO_END * d.DOFF_WT                                 AS GROSS_WEIGHT_KG,
        -- Dari TRANSFER: transferred (exclude cut)
        NVL(tr.TRANSFERRED_BOBS, 0)                                AS TRANSFERRED_BOBS,
        NVL(tr.TRANSFERRED_BOBS, 0) * d.DOFF_WT                  AS NET_WEIGHT_KG,
        -- Dari TRANSFER: dipotong/waste (TRN_TYPE=4)
        NVL(tr.CUT_BOBS, 0)                                        AS CUT_BOBBINS,
        -- Belum ada di TRANSFER sama sekali
        d.DOFF_NO_END - NVL(tr.TRANSFERRED_BOBS, 0) - NVL(tr.CUT_BOBS, 0)
                                                                    AS NOT_TRANSFER,
        -- TQM breakdown (hanya dari bobbin yang transferred)
        NVL(tr.NORMAL_BOBS, 0)                                     AS NORMAL_BOBS,
        NVL(tr.DOWNGRADE_BOBS, 0)                                  AS DOWNGRADE_BOBS,
        NVL(tr.NOT_CHECKED_BOBS, 0)                               AS NOT_CHECKED_BOBS,
        NVL(tr.NORMAL_BOBS, 0) + NVL(tr.DOWNGRADE_BOBS, 0)       AS TQM_DONE_BOBS,
        SYSDATE                                                     AS LAST_UPDATED
    FROM ASPSPG.DOFFCONT d
    -- TRANSFER: semua TRN_STATUS=2, CUT dan TRANSFERRED dihitung terpisah
    LEFT JOIN (
        SELECT
            t.TRN_MERGE,
            t.TRN_PRD_DT,
            t.TRN_POS,
            t.TRN_DOFF,
            -- Transferred: TRN_TYPE != 4
            SUM(CASE WHEN t.TRN_TYPE != 4 THEN 1 ELSE 0 END)      AS TRANSFERRED_BOBS,
            -- Cut/waste: TRN_TYPE = 4
            SUM(CASE WHEN t.TRN_TYPE  = 4 THEN 1 ELSE 0 END)      AS CUT_BOBS,
            -- TQM hanya untuk bobbin yang transferred (TRN_TYPE != 4)
            SUM(CASE WHEN t.TRN_TYPE != 4 AND q.TQM_GRADE = 1
                     THEN 1 ELSE 0 END)                            AS NORMAL_BOBS,
            SUM(CASE WHEN t.TRN_TYPE != 4 AND q.TQM_GRADE = 0
                     THEN 1 ELSE 0 END)                            AS DOWNGRADE_BOBS,
            SUM(CASE WHEN t.TRN_TYPE != 4 AND t.TRN_APP_REL_DT IS NULL
                     THEN 1 ELSE 0 END)                            AS NOT_CHECKED_BOBS
        FROM ASPSPG.TRANSFER t
        -- TQM: exact match via pointer di TRANSFER
        LEFT JOIN ASPSPG.TQMAPP q ON (
            q.TQM_MERGE   = t.TRN_MERGE         AND
            q.TQM_PRD_DT  = t.TRN_APP_REL_DT    AND
            q.TQM_DOFF    = t.TRN_APP_REL_DOFF  AND
            q.TQM_POS     = t.TRN_POS           AND
            q.TQM_BOB     = t.TRN_BOB
        )
        WHERE t.TRN_STATUS = 2
          AND t.TRN_PRD_DT >= TRUNC(SYSDATE) - 2
        GROUP BY t.TRN_MERGE, t.TRN_PRD_DT, t.TRN_POS, t.TRN_DOFF
    ) tr ON (
        tr.TRN_MERGE  = d.DOFF_MERGE    AND
        tr.TRN_PRD_DT = d.DOFF_DATE     AND
        tr.TRN_POS    = d.DOFF_POSITION AND
        tr.TRN_DOFF   = d.DOFF_NO
    )
    WHERE d.DOFF_DATE >= TRUNC(SYSDATE) - 2;

    COMMIT;

EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END PRC_PPC_SPG_PRODUCTION;
/


-- =============================================================================
-- PROCEDURE 3: PRC_PPC_GRADE_ACTUAL
-- Refresh PPC_GRADE_ACTUAL dari PAKPKGDUP + PAKPKG + PAKPKGDUPAM
-- Window  : PKG_PK_PUT_DATE >= TRUNC(SYSDATE) - 1
-- Logic   : Delete lot yang ada activity kemarin/hari ini,
--           lalu insert SEMUA history packing untuk lot tersebut
--           supaya TOTAL_QTY_KG selalu akurat
-- =============================================================================

CREATE OR REPLACE PROCEDURE MGTDAT.PRC_PPC_GRADE_ACTUAL IS
BEGIN

    -- Step 1: Delete lot yang ada packing baru dalam window
    DELETE FROM MGTDAT.PPC_GRADE_ACTUAL
    WHERE ORIGINAL_LOT_NO IN (
        SELECT DISTINCT
            CASE
                WHEN p.PKG_GRADE IN ('B', 'BB')
                    THEN NVL(pk.PKG_MERGE_NO, p.PKG_MERGE_NO)
                ELSE p.PKG_MERGE_NO
            END
        FROM ASPAK.PAKPKGDUP p
        LEFT JOIN ASMAR.PAKPKG pk ON (
            p.PKG_LOT_YEAR   = pk.PKG_LOT_YEAR   AND
            p.PKG_LOT_SLNO   = pk.PKG_LOT_SLNO   AND
            p.PKG_LOT_CRTNNO = pk.PKG_LOT_CRTNNO
        )
        WHERE p.PKG_PK_PUT_DATE >= TRUNC(SYSDATE) - 1
          AND p.PKG_DEPT IN ('TXT', 'TWT')
    );

    -- Step 2: Insert SEMUA history packing untuk lot yang baru dihapus
    INSERT INTO MGTDAT.PPC_GRADE_ACTUAL (
        ORIGINAL_LOT_NO,
        GRADE,
        DEPT,
        TOTAL_QTY_KG,
        TOTAL_BOBBIN_COUNT,
        LAST_PACKING_DATE,
        LAST_UPDATED
    )
    SELECT
        CASE
            WHEN p.PKG_GRADE IN ('B', 'BB')
                THEN NVL(pk.PKG_MERGE_NO, p.PKG_MERGE_NO)
            ELSE p.PKG_MERGE_NO
        END                                                         AS ORIGINAL_LOT_NO,
        -- Grade AM dibreakup ke A9/A via PAKPKGDUPAM
        NVL(pam.PKG_GRADE, p.PKG_GRADE)                           AS GRADE,
        p.PKG_DEPT                                                  AS DEPT,
        SUM(NVL(pam.PKG_QTY, p.PKG_QTY))                         AS TOTAL_QTY_KG,
        SUM(NVL(pam.PKG_SUB_UNITS, p.PKG_SUB_UNITS))             AS TOTAL_BOBBIN_COUNT,
        MAX(p.PKG_PKNG_DATE)                                       AS LAST_PACKING_DATE,
        SYSDATE                                                     AS LAST_UPDATED
    FROM ASPAK.PAKPKGDUP p
    -- PAKPKG: outer join — hanya ada row untuk grade B/BB
    LEFT JOIN ASMAR.PAKPKG pk ON (
        p.PKG_LOT_YEAR   = pk.PKG_LOT_YEAR   AND
        p.PKG_LOT_SLNO   = pk.PKG_LOT_SLNO   AND
        p.PKG_LOT_CRTNNO = pk.PKG_LOT_CRTNNO
    )
    -- PAKPKGDUPAM: outer join — hanya ada row untuk grade AM
    LEFT JOIN ASPAK.PAKPKGDUPAM pam ON (
        p.PKG_LOT_YEAR   = pam.PKG_LOT_YEAR  AND
        p.PKG_LOT_SLNO   = pam.PKG_LOT_SLNO  AND
        p.PKG_LOT_CRTNNO = pam.PKG_LOT_CRTNNO
    )
    LEFT JOIN ASPAK.MMSMERGE m ON p.PKG_MERGE_NO = m.MERGE_CODE
    WHERE
        p.PKG_DEPT IN ('TXT', 'TWT')
        AND NVL(m.MERGE_PAK_STATUS, 'S') <> 'B'
        -- Hanya insert untuk lot yang baru saja dihapus
        AND (
            CASE
                WHEN p.PKG_GRADE IN ('B', 'BB')
                    THEN NVL(pk.PKG_MERGE_NO, p.PKG_MERGE_NO)
                ELSE p.PKG_MERGE_NO
            END
        ) IN (
            SELECT DISTINCT
                CASE
                    WHEN p2.PKG_GRADE IN ('B', 'BB')
                        THEN NVL(pk2.PKG_MERGE_NO, p2.PKG_MERGE_NO)
                    ELSE p2.PKG_MERGE_NO
                END
            FROM ASPAK.PAKPKGDUP p2
            LEFT JOIN ASMAR.PAKPKG pk2 ON (
                p2.PKG_LOT_YEAR   = pk2.PKG_LOT_YEAR   AND
                p2.PKG_LOT_SLNO   = pk2.PKG_LOT_SLNO   AND
                p2.PKG_LOT_CRTNNO = pk2.PKG_LOT_CRTNNO
            )
            WHERE p2.PKG_PK_PUT_DATE >= TRUNC(SYSDATE) - 1
              AND p2.PKG_DEPT IN ('TXT', 'TWT')
        )
    GROUP BY
        CASE
            WHEN p.PKG_GRADE IN ('B', 'BB')
                THEN NVL(pk.PKG_MERGE_NO, p.PKG_MERGE_NO)
            ELSE p.PKG_MERGE_NO
        END,
        NVL(pam.PKG_GRADE, p.PKG_GRADE),
        p.PKG_DEPT;

    COMMIT;

EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE;
END PRC_PPC_GRADE_ACTUAL;
/


-- =============================================================================
-- OPTIONAL: DBMS_SCHEDULER setup
-- Uncomment dan adjust sesuai kebutuhan jadwal
-- =============================================================================

/*
-- PRC_PPC_TXT_PRODUCTION: setiap 15 menit
BEGIN
    DBMS_SCHEDULER.CREATE_JOB(
        job_name        => 'MGTDAT.JOB_PPC_TXT_PRODUCTION',
        job_type        => 'STORED_PROCEDURE',
        job_action      => 'MGTDAT.PRC_PPC_TXT_PRODUCTION',
        start_date      => SYSTIMESTAMP,
        repeat_interval => 'FREQ=MINUTELY; INTERVAL=15',
        enabled         => TRUE,
        comments        => 'PPC: Refresh TXT/TWT production summary setiap 15 menit'
    );
END;
/

-- PRC_PPC_SPG_PRODUCTION: setiap 15 menit
BEGIN
    DBMS_SCHEDULER.CREATE_JOB(
        job_name        => 'MGTDAT.JOB_PPC_SPG_PRODUCTION',
        job_type        => 'STORED_PROCEDURE',
        job_action      => 'MGTDAT.PRC_PPC_SPG_PRODUCTION',
        start_date      => SYSTIMESTAMP,
        repeat_interval => 'FREQ=MINUTELY; INTERVAL=15',
        enabled         => TRUE,
        comments        => 'PPC: Refresh SPG production summary setiap 15 menit'
    );
END;
/

-- PRC_PPC_GRADE_ACTUAL: setiap 15 menit
BEGIN
    DBMS_SCHEDULER.CREATE_JOB(
        job_name        => 'MGTDAT.JOB_PPC_GRADE_ACTUAL',
        job_type        => 'STORED_PROCEDURE',
        job_action      => 'MGTDAT.PRC_PPC_GRADE_ACTUAL',
        start_date      => SYSTIMESTAMP,
        repeat_interval => 'FREQ=MINUTELY; INTERVAL=15',
        enabled         => TRUE,
        comments        => 'PPC: Refresh grade actual dari packing setiap 15 menit'
    );
END;
/
*/


-- =============================================================================
-- VERIFICATION QUERIES
-- Jalankan setelah procedure pertama kali dieksekusi untuk validasi
-- =============================================================================

/*
-- Row count per table
SELECT 'PPC_TXT_PRODUCTION' tbl, COUNT(*) cnt FROM MGTDAT.PPC_TXT_PRODUCTION
UNION ALL
SELECT 'PPC_SPG_PRODUCTION', COUNT(*) FROM MGTDAT.PPC_SPG_PRODUCTION
UNION ALL
SELECT 'PPC_GRADE_ACTUAL', COUNT(*) FROM MGTDAT.PPC_GRADE_ACTUAL;

-- Sample TXT: spot check lot qU04qB006
SELECT LOT_NO, MACHINE_NO, TRN_DATE, TRN_SHIFT, DOFF_NO,
       TOTAL_BOBBINS, FULL_BOBBINS, UNFULL_BOBBINS,
       NORMAL_BOBS, DOWNGRADE_BOBS, PENDING_BOBS, PACK_CEK_BOBS
FROM MGTDAT.PPC_TXT_PRODUCTION
WHERE LOT_NO = 'qU04qB006'
ORDER BY TRN_DATE, DOFF_NO;
-- Expected doff 34: TOTAL=20, FULL=20, NORMAL=18, DOWNGRADE=2, PENDING=0

-- Sanity TXT: NORMAL + DOWNGRADE + PENDING = TOTAL
SELECT LOT_NO, TRN_DATE, DOFF_NO,
       TOTAL_BOBBINS,
       NORMAL_BOBS + DOWNGRADE_BOBS + PENDING_BOBS AS SUM_CHECK,
       TOTAL_BOBBINS - (NORMAL_BOBS + DOWNGRADE_BOBS + PENDING_BOBS) AS DIFF
FROM MGTDAT.PPC_TXT_PRODUCTION
WHERE TOTAL_BOBBINS != (NORMAL_BOBS + DOWNGRADE_BOBS + PENDING_BOBS);
-- Harusnya 0 rows

-- Sample SPG: spot check lot 11F3226
SELECT LOT_NO, MACHINE_LINE, DOFF_DATE, DOFF_NO,
       GROSS_BOBBINS, TRANSFERRED_BOBS, CUT_BOBBINS, NOT_TRANSFER,
       NORMAL_BOBS, DOWNGRADE_BOBS, NOT_CHECKED_BOBS, TQM_DONE_BOBS
FROM MGTDAT.PPC_SPG_PRODUCTION
WHERE LOT_NO = '11F3226' AND POSITION_NO = 201
ORDER BY DOFF_DATE, DOFF_NO;

-- Sanity SPG check 1: GROSS = TRANSFERRED + CUT + NOT_TRANSFER
SELECT LOT_NO, DOFF_DATE, DOFF_NO,
       GROSS_BOBBINS,
       TRANSFERRED_BOBS + CUT_BOBBINS + NOT_TRANSFER AS SUM_CHECK,
       GROSS_BOBBINS - (TRANSFERRED_BOBS + CUT_BOBBINS + NOT_TRANSFER) AS DIFF
FROM MGTDAT.PPC_SPG_PRODUCTION
WHERE GROSS_BOBBINS != (TRANSFERRED_BOBS + CUT_BOBBINS + NOT_TRANSFER);
-- Harusnya 0 rows

-- Sanity SPG check 2: TRANSFERRED = NORMAL + DOWNGRADE + NOT_CHECKED
SELECT LOT_NO, DOFF_DATE, DOFF_NO,
       TRANSFERRED_BOBS,
       NORMAL_BOBS + DOWNGRADE_BOBS + NOT_CHECKED_BOBS AS TQM_SUM,
       TRANSFERRED_BOBS - (NORMAL_BOBS + DOWNGRADE_BOBS + NOT_CHECKED_BOBS) AS DIFF
FROM MGTDAT.PPC_SPG_PRODUCTION
WHERE TRANSFERRED_BOBS != (NORMAL_BOBS + DOWNGRADE_BOBS + NOT_CHECKED_BOBS);
-- Harusnya 0 rows

-- Sample GRADE: cek beberapa lot
SELECT ORIGINAL_LOT_NO, GRADE, DEPT, TOTAL_QTY_KG, TOTAL_BOBBIN_COUNT
FROM MGTDAT.PPC_GRADE_ACTUAL
ORDER BY ORIGINAL_LOT_NO, GRADE
FETCH FIRST 20 ROWS ONLY;
*/
