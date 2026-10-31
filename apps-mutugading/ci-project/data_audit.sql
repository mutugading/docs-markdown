-- CI Project — P0 data audit (tasks.md T01 / T02)
--
-- Read-only. Run against the MGTAPPS schema in production, and paste the results
-- into gap-analysis.md §6 as the answers to Q1, Q2 and Q6.
--
-- The dev run (2026-08-31) is already written up there. The server is Oracle 11g
-- Release 11.2.0.4, so no `FETCH FIRST` / `OFFSET` — use ROWNUM if a query here ever
-- needs limiting.
--
-- Two of these gate a migration:
--   * §1 blocks add_ci_project_saving_unique_index — that migration THROWS while any
--     duplicate remains, on purpose.
--   * §3 decides whether add_ci_project_main_indexes creates the title index; it
--     skips it and logs a warning when duplicates exist, so the audit is what turns
--     it on rather than a code change.

-- 1. Duplicate (project, month) saving rows.
--    The report left-joins ci_project_saving once per month column, so a duplicate
--    pair multiplies the project row in the list and makes its other two month
--    figures wrong.
--    Resolution rule: keep the most recently updated row, delete the rest. Do NOT
--    sum them — the legacy bug wrote the full amount into each duplicate, so summing
--    doubles the saving.
SELECT cps_cpm_sys_id,
       cps_date,
       COUNT(*)         AS row_count,
       SUM(cps_saving)  AS summed_saving
  FROM ci_project_saving
 GROUP BY cps_cpm_sys_id, cps_date
HAVING COUNT(*) > 1
 ORDER BY cps_cpm_sys_id, cps_date;

-- 2. Saving dates that are not a clean first-of-month at midnight.
--    Both the report join and the saving upsert match cps_date exactly, so a row
--    with a stray time component or a mid-month date is invisible to the page.
SELECT COUNT(*) AS unclean_dates
  FROM ci_project_saving
 WHERE cps_date <> TRUNC(cps_date, 'MM');

-- 3. Case-insensitive duplicate project titles.
--    While any exist, the functional unique index on UPPER(cpm_title) cannot be
--    created and the service-level check stays the only guard.
SELECT UPPER(cpm_title) AS upper_title,
       COUNT(*)         AS title_count
  FROM ci_project_main
 WHERE cpm_title IS NOT NULL
 GROUP BY UPPER(cpm_title)
HAVING COUNT(*) > 1
 ORDER BY 2 DESC;

-- 4. The legacy access-control rows.
--    ADMIN becomes the 'CI Project - Admin' role, CAN_VIEW_ALL becomes
--    'CI Project - Viewer'. Assigning them is a reviewed, manual step (T18) — do not
--    automate a permission grant out of a dropdown table.
SELECT type, code, name
  FROM ci_project_master
 WHERE type IN ('ADMIN', 'CAN_VIEW_ALL', 'APPROVE')
 ORDER BY type, code;

-- 5. Department codes on projects that no active employee shares.
--    A mismatch silently hides a project from its own department, because the scope
--    compares cpm_dept with the employee's hmemd_hmdep_dept_id (Q6). Run the two
--    halves separately if the schemas are not linked.
SELECT cpm_dept, COUNT(*) AS project_count
  FROM ci_project_main
 GROUP BY cpm_dept
 ORDER BY cpm_dept;

-- 6. Department codes offered by the master table, for the same comparison.
SELECT code, name
  FROM ci_project_master
 WHERE type = 'DEPT'
 ORDER BY code;

-- 7. Project values that have no master row behind them.
--    These are the codes the master page must not let anyone create a conflict with.
SELECT 'GROUP' AS master_type, cpm_group AS code, COUNT(*) AS project_count
  FROM ci_project_main a
 WHERE cpm_group IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM ci_project_master m WHERE m.type = 'GROUP' AND m.code = a.cpm_group)
 GROUP BY cpm_group
 UNION ALL
SELECT 'STATUS', cpm_status, COUNT(*)
  FROM ci_project_main a
 WHERE cpm_status IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM ci_project_master m WHERE m.type = 'STATUS' AND m.code = a.cpm_status)
 GROUP BY cpm_status
 UNION ALL
SELECT 'SAV_TYPE', cpm_sav_type, COUNT(*)
  FROM ci_project_main a
 WHERE cpm_sav_type IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM ci_project_master m WHERE m.type = 'SAV_TYPE' AND m.code = a.cpm_sav_type)
 GROUP BY cpm_sav_type
 UNION ALL
SELECT 'FREQUENCY', cpm_frequency, COUNT(*)
  FROM ci_project_main a
 WHERE cpm_frequency IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM ci_project_master m WHERE m.type = 'FREQUENCY' AND m.code = a.cpm_frequency)
 GROUP BY cpm_frequency
 ORDER BY 1, 2;
