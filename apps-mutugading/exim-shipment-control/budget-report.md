# Est. Budget Shipment — Report Definition

Weekly budget the Import team sends Finance, for every BL whose **ETA** falls in the chosen range.

## Parameter

| Key | Label | Type | Required |
|---|---|---|---|
| `ETA` | ETA | `date_range` | yes |

## Rules

- ETA = `sif_eta_actual`, else `sif_eta_calculated`. A BL with no ETA is not in any range.
- All statuses, drafts included. A draft with no estimate yet shows Duty as 0.
- Est. Budget = Duty + Freight + THC, in rupiah. No supplier invoice value, no local charges.
- Freight/THC rate: ERP tax rate (`mgtdat.fm_exchange_rate`, type `T`) covering the ETA,
  else `ship_kurs_pajak`. No rate found → that cell and Est. Budget stay empty (never a silent 0),
  and the subtotal leaves that BL out — fix the rate, rerun.
- Rows: FCL, LCL, BY AIR (courier counts as air), sorted by ETA; a subtotal per group, TOTAL last.

## Query

```sql
WITH bl AS (
    SELECT f.sif_sys_id,
           f.sif_supplier_name,
           f.sif_bl_no,
           NVL(f.sif_eta_actual, f.sif_eta_calculated) AS eta,
           CASE f.sif_transport_mode
                WHEN 'SEA_FCL' THEN 1 WHEN 'SEA_LCL' THEN 2 ELSE 3 END AS grp_sort,
           CASE f.sif_transport_mode
                WHEN 'SEA_FCL' THEN 'FCL' WHEN 'SEA_LCL' THEN 'LCL' ELSE 'BY AIR' END AS grp_name,
           f.sif_est_duty_total,
           f.sif_freight_total,
           UPPER(TRIM(f.sif_freight_currency)) AS freight_ccy,
           f.sif_thc_total,
           UPPER(TRIM(f.sif_thc_currency)) AS thc_ccy
      FROM ship_import_file f
     WHERE TRUNC(NVL(f.sif_eta_actual, f.sif_eta_calculated))
           BETWEEN TO_DATE(:ETA_START, 'YYYY-MM-DD') AND TO_DATE(:ETA_END, 'YYYY-MM-DD')
),
cont AS (
    -- "FCL 40X5, FCL 20X2" — one entry per container type
    SELECT sif_sys_id,
           LISTAGG('FCL ' || REPLACE(ctype, 'FCL') || 'X' || TO_CHAR(qty, 'FM999990'), ', ')
               WITHIN GROUP (ORDER BY ctype DESC) AS container
      FROM (SELECT c.sic_sif_sys_id AS sif_sys_id, c.sic_container_type AS ctype, SUM(c.sic_qty) AS qty
              FROM ship_import_container c
             WHERE c.sic_sif_sys_id IN (SELECT sif_sys_id FROM bl)
             GROUP BY c.sic_sif_sys_id, c.sic_container_type)
     GROUP BY sif_sys_id
),
priced AS (
    SELECT bl.*,
           CASE WHEN bl.grp_sort = 1 THEN NVL(cont.container, 'FCL') ELSE bl.grp_name END AS container,
           NVL(bl.sif_est_duty_total, 0) AS duty_idr,
           CASE WHEN NVL(bl.sif_freight_total, 0) = 0 THEN 0
                WHEN bl.freight_ccy = 'IDR' THEN bl.sif_freight_total
                ELSE bl.sif_freight_total * COALESCE(
                    (SELECT MAX(r.cer_exg_rate) KEEP (DENSE_RANK FIRST ORDER BY r.cer_eff_to_dt)
                       FROM mgtdat.fm_exchange_rate r
                      WHERE r.cer_conv_fm_curr_code = bl.freight_ccy AND r.cer_conv_to_curr_code = 'IDR'
                        AND r.cer_exg_rate_type = 'T' AND r.cer_exg_rate > 0
                        AND TRUNC(bl.eta) BETWEEN TRUNC(r.cer_eff_frm_dt) AND TRUNC(r.cer_eff_to_dt)),
                    (SELECT MAX(k.skp_rate) KEEP (DENSE_RANK FIRST ORDER BY k.skp_valid_from DESC)
                       FROM ship_kurs_pajak k
                      WHERE k.skp_currency = bl.freight_ccy AND k.skp_active = 1
                        AND TRUNC(bl.eta) >= TRUNC(k.skp_valid_from)
                        AND (k.skp_valid_to IS NULL OR TRUNC(bl.eta) <= TRUNC(k.skp_valid_to))))
           END AS freight_idr,
           CASE WHEN NVL(bl.sif_thc_total, 0) = 0 THEN 0
                WHEN bl.thc_ccy = 'IDR' THEN bl.sif_thc_total
                ELSE bl.sif_thc_total * COALESCE(
                    (SELECT MAX(r.cer_exg_rate) KEEP (DENSE_RANK FIRST ORDER BY r.cer_eff_to_dt)
                       FROM mgtdat.fm_exchange_rate r
                      WHERE r.cer_conv_fm_curr_code = bl.thc_ccy AND r.cer_conv_to_curr_code = 'IDR'
                        AND r.cer_exg_rate_type = 'T' AND r.cer_exg_rate > 0
                        AND TRUNC(bl.eta) BETWEEN TRUNC(r.cer_eff_frm_dt) AND TRUNC(r.cer_eff_to_dt)),
                    (SELECT MAX(k.skp_rate) KEEP (DENSE_RANK FIRST ORDER BY k.skp_valid_from DESC)
                       FROM ship_kurs_pajak k
                      WHERE k.skp_currency = bl.thc_ccy AND k.skp_active = 1
                        AND TRUNC(bl.eta) >= TRUNC(k.skp_valid_from)
                        AND (k.skp_valid_to IS NULL OR TRUNC(bl.eta) <= TRUNC(k.skp_valid_to))))
           END AS thc_idr
      FROM bl
      LEFT JOIN cont ON cont.sif_sys_id = bl.sif_sys_id
)
SELECT CASE WHEN GROUPING(grp_name) = 1 THEN NULL ELSE grp_name END AS "Group",
       CASE WHEN GROUPING(grp_name) = 1 THEN 'TOTAL'
            WHEN GROUPING(sif_sys_id) = 1 THEN 'SUBTOTAL ' || grp_name
            ELSE sif_supplier_name END                                AS "Supplier",
       eta                                                            AS "ETA",
       container                                                      AS "Container",
       sif_bl_no                                                      AS "AWB / BL No.",
       ROUND(SUM(duty_idr), 2)                                        AS "Duty",
       ROUND(SUM(freight_idr), 2)                                     AS "Freight",
       ROUND(SUM(thc_idr), 2)                                         AS "THC",
       ROUND(SUM(duty_idr + freight_idr + thc_idr), 2)                AS "Est. Budget"
  FROM priced
 GROUP BY ROLLUP ((grp_sort, grp_name),
                  (sif_sys_id, sif_supplier_name, eta, container, sif_bl_no))
 ORDER BY grp_sort NULLS LAST, GROUPING(sif_sys_id), eta, sif_supplier_name
```
