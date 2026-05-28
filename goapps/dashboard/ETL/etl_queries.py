"""
SQL queries used by ETL.

Centralized here for:
  - Easy review by DBA
  - Single source of truth
  - Version control as code
"""

# =====================================================================
# ORACLE SOURCE QUERY
# =====================================================================
# This is the query provided by the IT Leader.
# Returns current month data only (closing month if today <= day 5).
# Output columns:
#   MFMG_TYPE       VARCHAR  -- 'MIS'
#   MFMG_GROUP_1    VARCHAR  -- 'EBITDA' or 'NET PROFIT'
#   MFMG_GROUP_2    VARCHAR  -- 'INCOME', 'PRODUCTION COST', ...
#   MFMG_GROUP_3    VARCHAR  -- 'LOCAL SALES', 'CHIPS COST', ...
#   MFMG_GROUP_1_ORD INT
#   MFMG_GROUP_2_ORD INT
#   MFMG_GROUP_3_ORD INT
#   PERIODE         VARCHAR(6)  -- 'YYYYMM'
#   CURR_PERIOD     NUMERIC     -- Signed value (raw, accounting convention)

ORACLE_MIS_QUERY = """
SELECT
    MFMG_TYPE,
    MFMG_GROUP_1,
    MFMG_GROUP_2,
    MFMG_GROUP_3,
    MFMG_GROUP_1_ORD,
    MFMG_GROUP_2_ORD,
    MFMG_GROUP_3_ORD,
    aper_cal_year || LPAD(aper_cal_month, 2, 0) AS PERIODE,
    (NVL(SUM(abal_mtd_dr), 0) - NVL(SUM(abal_lc_mtd_cr), 0)) * NVL(MFMG_VALUE, 1) AS CURR_PERIOD
FROM
    MGT_FIN_MIS_GRP,
    (
        SELECT
            aper_cal_year, aper_cal_month, month_name,
            main_acnt_code, main_acnt_name,
            NULL AS ABAL_SUB_ACNT_CODE,
            NULL AS SUB_ACNT_NAME,
            open_balance, abal_mtd_dr, abal_lc_mtd_cr, closing_bal
        FROM fv_main_ac_tb_lc
        UNION ALL
        SELECT
            aper_cal_year, aper_cal_month, month_name,
            main_acnt_code, main_acnt_name,
            ABAL_SUB_ACNT_CODE, SUB_ACNT_NAME,
            open_balance, abal_mtd_dr, abal_lc_mtd_cr, closing_bal
        FROM fv_sub_ac_tb_lc
    )
WHERE
    aper_cal_year = TO_NUMBER(TO_CHAR(ADD_MONTHS(to_date(sysdate,'DD/MM/RRRR'),-1),'RRRR'))
    and aper_cal_month = TO_NUMBER(TO_CHAR(ADD_MONTHS(to_date(sysdate,'DD/MM/RRRR'),-1),'MM'))
    AND aper_cal_year <= TO_NUMBER(TO_CHAR(SYSDATE, 'RRRR'))
    AND MFMG_MAIN_ACNT = main_acnt_code
    AND NVL(MFMG_SUB_ACNT, 'NA') = NVL(ABAL_SUB_ACNT_CODE, 'NA')
    AND MFMG_TYPE = 'MIS'
GROUP BY
    MFMG_TYPE, MFMG_GROUP_1, MFMG_GROUP_2, MFMG_GROUP_3,
    aper_cal_year, aper_cal_month, month_name,
    MFMG_GROUP_1_ORD, MFMG_GROUP_2_ORD, MFMG_GROUP_3_ORD,
    MFMG_VALUE
ORDER BY
    aper_cal_year, aper_cal_month,
    MFMG_GROUP_1_ORD, MFMG_GROUP_2_ORD, MFMG_GROUP_3_ORD
"""


# =====================================================================
# POSTGRES TARGET QUERIES
# =====================================================================

# Resolve source code → ID (called once per ETL run)
PG_GET_SOURCE_ID = """
SELECT DS_SOURCE_ID FROM DATA_SOURCE
WHERE DS_SOURCE_CODE = %s AND DS_IS_ACTIVE = TRUE
"""

# Get job ID by name (for ETL_JOB_LOG FK)
PG_GET_JOB_ID = """
SELECT EJ_JOB_ID FROM ETL_JOB
WHERE EJ_JOB_NAME = %s AND EJ_IS_ACTIVE = TRUE
"""

# UPSERT statement template.
# Uses ON CONFLICT pada business key untuk safe re-run.
# %s placeholders will be expanded by execute_values() / executemany.
PG_UPSERT_FACT_METRIC = """
INSERT INTO FACT_METRIC (
    FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
    FM_GROUP_1_ORDER, FM_GROUP_2_ORDER, FM_GROUP_3_ORDER,
    FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_PERIODE_LABEL,
    FM_VALUE, FM_DISPLAY_VALUE, FM_UOM, FM_SCENARIO,
    FM_SOURCE_ID, FM_LOADED_AT, FM_IS_ACTIVE
) VALUES %s
ON CONFLICT (
    FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
    FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_SCENARIO, FM_DIMENSION_KEY
) DO UPDATE SET
    FM_GROUP_1_ORDER  = EXCLUDED.FM_GROUP_1_ORDER,
    FM_GROUP_2_ORDER  = EXCLUDED.FM_GROUP_2_ORDER,
    FM_GROUP_3_ORDER  = EXCLUDED.FM_GROUP_3_ORDER,
    FM_PERIODE_LABEL  = EXCLUDED.FM_PERIODE_LABEL,
    FM_VALUE          = EXCLUDED.FM_VALUE,
    FM_DISPLAY_VALUE  = EXCLUDED.FM_DISPLAY_VALUE,
    FM_UOM            = EXCLUDED.FM_UOM,
    FM_SOURCE_ID      = EXCLUDED.FM_SOURCE_ID,
    FM_LOADED_AT      = EXCLUDED.FM_LOADED_AT,
    FM_IS_ACTIVE      = TRUE
"""

# Refresh materialized views (called after UPSERT)
PG_REFRESH_MVS = "SELECT REFRESH_DASHBOARD_MVS()"

# ETL_JOB_LOG inserts (one at start, one at end)
PG_INSERT_LOG_START = """
INSERT INTO ETL_JOB_LOG (
    EJL_JOB_ID, EJL_STARTED_AT, EJL_STATUS, EJL_TRIGGERED_BY
) VALUES (%s, NOW(), 'RUNNING', %s)
RETURNING EJL_LOG_ID
"""

PG_UPDATE_LOG_END = """
UPDATE ETL_JOB_LOG SET
    EJL_ENDED_AT      = NOW(),
    EJL_STATUS        = %s,
    EJL_ROWS_AFFECTED = %s,
    EJL_ERROR_MESSAGE = %s
WHERE EJL_LOG_ID = %s
"""
