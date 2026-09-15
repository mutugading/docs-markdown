-- =====================================================================
-- VIEW MGTDAT.MGT_TRANSP_VIEW
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.MGT_TRANSP_VIEW AS
select  a.mth_txn_code ||'-'|| a.mth_transp_no transp_no,
        a.mth_dt                            transp_dt,
        a.mth_no_police                     no_police,    
        a.mth_transp_name                   transporter,
        a.mth_truck_type                    truck_type,
        a.mth_destination                   destination,        
        d.invh_cust_name                    customer,
        a.mth_no                            transp_code,                      
        c.mtdc_rate_type                    type,  
        a.mth_truck_cap                     truck_cap,       
        c.mtdc_qty                          truck_qty,
        c.mtdc_rate                         truck_rate,
        c.mtdc_total_rate                   total_rate,
        b.mtdd_dn_txn_code ||'-'|| b.mtdd_dn_no dn_no,
        e.invi_item_code                    item_code, 
        e.invi_item_desc                    item_desc, 
        e.invi_grade_code_1                 grade_1, 
        e.invi_grade_code_2                 grade_2,
        f.gross_wt                          qty_gross,
        e.invi_qty_bu/1000                  qty_nett,
        a.mth_sys_id                                                                                      
from mgt_transp_head a,
     mgt_transp_detail_dn b,
     mgt_transp_detail_cost c,
     ot_invoice_head d,
     ot_invoice_item e,
     (select sum(prd_gross_wt) gross_wt,
             prd_doc_txn_code ||'-'|| prd_docno_slno ldn_no,
             prd_orion_item item_code,
             prd_grade grade_1  
      from ot_wms_pack_table_althara
      where prd_pkng_date between to_date('01-JAN-2016') and sysdate
      and prd_doc_txn_code   = 'LDN'
      group by  prd_doc_txn_code ||'-'|| prd_docno_slno,
                prd_orion_item,
                prd_grade 
    ) f     
where a.mth_sys_id  = b.mtdd_mth_sys_id      
and a.mth_sys_id    = c.mtdc_mth_sys_id
and b.mtdd_dn_txn_code ||'-'|| b.mtdd_dn_no = d.invh_txn_code ||'-'|| d.invh_no
and d.invh_sys_id   = e.invi_invh_sys_id
and b.mtdd_dn_txn_code ||'-'|| b.mtdd_dn_no = f.ldn_no 
and e.invi_item_code                        = f.item_code
and e.invi_grade_code_1                     = f.grade_1
and a.mth_dt between to_date('01-JAN-2017') and sysdate 
order by 2,1,14 
;

-- =====================================================================
-- VIEW MGTDAT.MGT_TRANSP_TRX_VIEW
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.MGT_TRANSP_TRX_VIEW AS
select  a.mth_sys_id,
        a.mth_txn_code ||'-'|| a.mth_transp_no tpdn,
        substr(replace(replace(replace(replace(b.mtdd_dn_txn_code ||'-'|| b.mtdd_dn_no, 'PALLET-', 'PALLET'),'RETUR BENANG-','RETUR BENANG'),'AMBIL BARANG-', 'AMBIL BARANG'),'OTHERS-', 'OTHERS'),1,30)  ldn,
        a.mth_txn_code             
from mgt_transp_head a,
  mgt_transp_detail_dn b
where a.mth_sys_id = b.mtdd_mth_sys_id
;

-- =====================================================================
-- VIEW MGTDAT.TRANSP_LDN_PN
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.TRANSP_LDN_PN AS
SELECT DISTINCT
           a.invh_dt
               dt,
           a.invh_txn_code
               txn,
           a.invh_no
               no,
           c.gross_wt
               qty,
           a.invh_cust_name
               cust,
           NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1)
               city,
           c.gross_wt
               gross,
           a.invh_flex_06
               transp_code,
           a.invh_flex_02
               transp_truck,
           a.invh_flex_07
               transp_desti
      FROM ot_invoice_head  a,
           ot_invoice_item  b,
           (  SELECT prd_doc_txn_code || '-' || prd_docno_slno     ldn_no,
                     SUM (prd_gross_wt)                            gross_wt,
                     SUM (prd_net_wt)                              net_wt
                FROM ot_wms_pack_table_althara
               WHERE prd_doc_txn_code in ('LDN','JWDN')
            --and prd_pkng_date between to_date('01-JAN-2016') and sysdate
            GROUP BY prd_doc_txn_code || '-' || prd_docno_slno) c
     WHERE     a.invh_sys_id = b.invi_invh_sys_id
           AND a.invh_txn_code in ('LDN','JWDN')
           AND a.invh_appr_status = 3
           AND a.invh_dt BETWEEN TO_DATE ('01-JAN-2016') AND SYSDATE
           AND a.invh_txn_code || '-' || a.invh_no = c.ldn_no(+)
           AND NOT EXISTS
                   (SELECT 'X'
                      FROM mgt_transp_detail_dn
                     WHERE     NVL (mtdd_used_status, 'N') = 'N'
                           AND mtdd_dn_txn_code || '-' || mtdd_dn_no =
                               a.invh_txn_code || '-' || a.invh_no)                               
    UNION ALL
      SELECT a.invh_dt
                 dt,
             a.invh_txn_code
                 txn,
             a.invh_no
                 no,
             SUM (TO_NUMBER (b.invi_flex_01))
                 qty,
             a.invh_cust_name
                 cust,
             NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1)
                 city,
             SUM (TO_NUMBER (b.invi_flex_01))
                 gross,
             a.invh_flex_06
                 transp_code,
             a.invh_flex_02
                 transp_truck,
             a.invh_flex_07
                 transp_desti
        FROM ot_invoice_head a, ot_invoice_item b
       WHERE     a.invh_sys_id = b.invi_invh_sys_id
             AND a.invh_txn_code = 'PDN'
             AND a.invh_appr_status = 3
             AND a.invh_dt BETWEEN TO_DATE ('01-JAN-2016') AND SYSDATE
             AND NOT EXISTS
                     (SELECT 'X'
                        FROM mgt_transp_detail_dn
                       WHERE     NVL (mtdd_used_status, 'N') = 'N'
                             AND mtdd_dn_txn_code || '-' || mtdd_dn_no =
                                 a.invh_txn_code || '-' || a.invh_no)
    GROUP BY a.invh_dt,
             a.invh_txn_code,
             a.invh_no,
             a.invh_cust_name,
             NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1),
             a.invh_flex_06,
             a.invh_flex_02,
             a.invh_flex_07
    UNION ALL
    SELECT NULL          dt,
           vssv_code     txn,
           NULL          no,
           0             qty,
           NULL          cust,
           NULL          city,
           NULL          gross,
           NULL          transp_code,
           NULL          transp_truck,
           NULL          transp_desti
      FROM im_vs_static_value
     WHERE vssv_vs_code = 'TRANSPORT' AND NVL (vssv_frz_flag_num, 2) = 2
    ORDER BY 3 DESC
;

-- =====================================================================
-- VIEW MGTDAT.TRANSP_LDN_PN_AUTO
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.TRANSP_LDN_PN_AUTO AS
SELECT DISTINCT
          a.invh_dt dt,
          a.invh_txn_code txn,
          a.invh_no no,
          c.gross_wt qty,
          a.invh_cust_name cust,
          NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1) city,
          c.gross_wt gross,
          a.invh_flex_06 transp_code,
          a.invh_flex_02 transp_truck,
          a.invh_flex_07 transp_desti
     FROM ot_invoice_head a,
          ot_invoice_item b,
          (  SELECT prd_doc_txn_code || '-' || prd_docno_slno ldn_no,
                    SUM (prd_gross_wt) gross_wt,
                    SUM (prd_net_wt) net_wt
               FROM ot_wms_pack_table_althara
              WHERE prd_doc_txn_code = 'LDN'
           --and prd_pkng_date between to_date('01-JAN-2016') and sysdate
           GROUP BY prd_doc_txn_code || '-' || prd_docno_slno) c
    WHERE     a.invh_sys_id = b.invi_invh_sys_id
          AND a.invh_txn_code = 'LDN'
          AND a.invh_appr_status = 3
          AND a.invh_dt BETWEEN TO_DATE ('01-JAN-2016') AND SYSDATE
          AND a.invh_txn_code || '-' || a.invh_no = c.ldn_no(+)
          /*
          AND NOT EXISTS
                 (SELECT 'X'
                    FROM mgt_transp_detail_dn
                   WHERE     NVL (mtdd_used_status, 'N') = 'N'
                         AND mtdd_dn_txn_code || '-' || mtdd_dn_no =
                                a.invh_txn_code || '-' || a.invh_no) */
   UNION ALL
     SELECT a.invh_dt dt,
            a.invh_txn_code txn,
            a.invh_no no,
            SUM (TO_NUMBER (b.invi_flex_01)) qty,
            a.invh_cust_name cust,
            NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1) city,
            SUM (TO_NUMBER (b.invi_flex_01)) gross,
            a.invh_flex_06 transp_code,
            a.invh_flex_02 transp_truck,
            a.invh_flex_07 transp_desti
       FROM ot_invoice_head a, ot_invoice_item b
      WHERE     a.invh_sys_id = b.invi_invh_sys_id
            AND a.invh_txn_code = 'PDN'
            AND a.invh_appr_status = 3
            AND a.invh_dt BETWEEN TO_DATE ('01-JAN-2016') AND SYSDATE
            /*
            AND NOT EXISTS
                   (SELECT 'X'
                      FROM mgt_transp_detail_dn
                     WHERE     NVL (mtdd_used_status, 'N') = 'N'
                           AND mtdd_dn_txn_code || '-' || mtdd_dn_no =
                                  a.invh_txn_code || '-' || a.invh_no) */
   GROUP BY a.invh_dt,
            a.invh_txn_code,
            a.invh_no,
            a.invh_cust_name,
            NVL (a.invh_ship_addr_line_2, a.invh_ship_addr_line_1),
            a.invh_flex_06,
            a.invh_flex_02,
            a.invh_flex_07   
   ORDER BY 3 DESC
;

-- =====================================================================
-- VIEW MGTDAT.TRANSP_DESTI_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.TRANSP_DESTI_V AS
select distinct mtm_destination   
from mgt_transp_master 
where mtm_type = 'DES' 
and mtm_transp_code is not null
order by mtm_destination
;

-- =====================================================================
-- VIEW MGTDAT.TRANSP_LIST_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.TRANSP_LIST_V AS
SELECT mtm_no transp_code,
               mtm_transp_name
            || ' - '
            || mtm_destination
            || ' - '
            || mtm_truck_type
            || ' - '
            || mtm_transp_code
               description
       FROM mgt_transp_master
      WHERE mtm_type = 'DES' 
      AND mtm_transp_code IS NOT NULL
      AND nvl(mtm_frz_flag_num,2) = 2
   GROUP BY mtm_no,
               mtm_transp_name
            || ' - '
            || mtm_destination
            || ' - '
            || mtm_truck_type
            || ' - '
            || mtm_transp_code
   ORDER BY description, mtm_no
;

-- =====================================================================
-- VIEW MGTDAT.TRANSPORTER_BUDGET_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.TRANSPORTER_BUDGET_V AS
SELECT mtm_transp_code,
             supp_name,
             tp_no,
             tp_dt,
             tp_jv_no,
             TH_DOC_DT                                                jv_bill_dt,
             SUM (NVL (tp_amt, 0) + NVL (tp_oth_amt, 0))             amt,
             'PROVISION'                                             remarks,
             CASE
                 WHEN TO_NUMBER (TO_CHAR (tp_dt, 'DD')) BETWEEN 1 AND 15
                 THEN
                     TO_DATE (
                         '10/' || TO_CHAR (ADD_MONTHS (tp_dt, 1), 'MM/YY'),
                         'DD/MM/YY')
                 WHEN TO_NUMBER (TO_CHAR (tp_dt, 'DD')) BETWEEN 16 AND 31
                 THEN
                     TO_DATE (
                         '25/' || TO_CHAR (ADD_MONTHS (tp_dt, 1), 'MM/YY'),
                         'DD/MM/YY')
             END                                                     due_date,
             CASE WHEN sts IS NULL THEN 'NOT POSTED' ELSE sts END    sts,
             DECODE (SUBSTR (tp_no, 1, INSTR (tp_no, '-', 1) - 1),
                     'TPCHP', 'CHIPS',
                     'TPDN', 'YARN',
                     'OTHERS')                                       TYPE
        FROM mgt_tp_provision,
             mgt_transp_master,
             om_supplier,
             (SELECT th_tran_code || '-' || th_doc_no fin_trans, 'POSTED' sts, TH_DOC_DT
                FROM fv_trans_header)
       WHERE     tp_match_status IS NULL
             AND tp_code = mtm_no
             AND mtm_transp_code = supp_code
             AND tp_jv_no = fin_trans(+)
    GROUP BY tp_jv_no,
             tp_dt,
             mtm_transp_code,
             supp_name,
             tp_no,
             TH_DOC_DT,
             sts,
             tp_no             
    UNION ALL    
      SELECT tpb_supp_code,
             tpb_supp_name,
             tpb_trx_no,
             tpb_dt,
             tp_tjv_no,
             tpb_bill_dt,
             NVL (ost_fc_amt, 0) - NVL (SUM (match_fc_amt), 0)     outstanding,
             'BILL'                                                remarks,
             due_date,
             sts,
             TYPE
        FROM (SELECT tpb_supp_code,
                     tpb_supp_name,
                     tpb_trx_no,
                     tp_tjv_no,
                     tpb_dt,
                     tpb_bill_dt,
                     ost_fc_amt,
                     due_date,
                     NVL (ost_key_no, 1111111)     ost_key_no,
                     sts,
                     tpb_type                      TYPE
                FROM (  SELECT tpb_supp_code,
                               tpb_supp_name,
                               tpb_trx_no,
                               tp_tjv_no,
                               tpb_dt,
                               tpb_bill_dt,
                               NVL (ost_fc_amt, tpb_total)    ost_fc_amt,
                               MAX (
                                   CASE
                                       WHEN TO_NUMBER (TO_CHAR (tp_dt, 'DD')) BETWEEN 1
                                                                                  AND 15
                                       THEN
                                           TO_DATE (
                                                  '10/'
                                               || TO_CHAR (ADD_MONTHS (tp_dt, 1),
                                                           'MM/YY'),
                                               'DD/MM/YY')
                                       WHEN TO_NUMBER (TO_CHAR (tp_dt, 'DD')) BETWEEN 16
                                                                                  AND 31
                                       THEN
                                           TO_DATE (
                                                  '25/'
                                               || TO_CHAR (ADD_MONTHS (tp_dt, 1),
                                                           'MM/YY'),
                                               'DD/MM/YY')
                                   END)                       due_date,
                               CASE
                                   WHEN     tp_tjv_no IS NOT NULL
                                        AND ost_fc_amt IS NULL
                                   THEN
                                       'NOT POSTED'
                                   WHEN tp_tjv_no IS NULL AND ost_fc_amt IS NULL
                                   THEN
                                       'NOT GENERATED'
                                   WHEN ost_fc_amt IS NOT NULL
                                   THEN
                                       'POSTED'
                               END                            sts,
                               tpb_type,
                               ost_key_no
                          FROM mgt_tp_provision,
                               mgt_tp_provision_bill,
                               (SELECT ost_tran_code || '-' || ost_doc_no
                                           os_tran_code,
                                       ost_key_no,
                                       ost_lc_amt,
                                       ost_fc_amt
                                  FROM ft_os)
                         WHERE     tpb_sys_id = tp_tpb_sys_id
                               AND tp_tjv_no = os_tran_code(+)
                      GROUP BY tp_tjv_no,
                               tpb_dt,
                               tpb_trx_no,
                               tpb_bill_dt,
                               tpb_supp_code,
                               tpb_supp_name,
                               ost_fc_amt,
                               tpb_total,
                               ost_key_no,
                               tpb_type)),
             (SELECT om_ost_key_no
                         match_sys_id,
                     ost_tran_code || '-' || ost_doc_no
                         match_trn_code,
                     ost_doc_dt
                         match_doc_dt,
                     ost_key_no
                         match_key_no,
                     NVL (om_base_fc_amt, om_base_forex_fc_amt)
                         match_fc_amt,
                     NVL (om_base_lc_amt, om_base_forex_lc_amt)
                         match_lc_amt
                FROM fv_os_match, ft_os
               WHERE     ost_key_no = om_base_seq_no
                     AND NVL (ost_tran_code, 'FXADJ') != ('FXADJ')) matching
       WHERE     ost_key_no = match_sys_id(+)
             AND tpb_trx_no NOT IN ('TBILL-2022000696', 'TBILL-2022000718')
    GROUP BY tp_tjv_no,
             tpb_dt,
             tpb_trx_no,
             tpb_bill_dt,
             tpb_supp_code,
             tpb_supp_name,
             ost_fc_amt,
             due_date,
             sts,
             TYPE
      HAVING NVL (ost_fc_amt, 0) - NVL (SUM (match_fc_amt), 0) <> 0      
    ORDER BY
        supp_name,
        due_date,
        tp_dt,
        tp_jv_no
;

-- =====================================================================
-- VIEW MGTDAT.EXPORT_COST_TRANSPORTER_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.EXPORT_COST_TRANSPORTER_V AS
select supp_code, supp_name
from om_supplier
where nvl(supp_frz_flag_num, 2) = 2 
and supp_code in ('LS00371', 'LS00674', 'LS00871', 'LS00894', 'LS00896', 'LS01100', 'LS01124', 'LS01627', 'LS01632', 'LS01680', 'LS01809', 'LS02362', 'LS02421', 'LS02455', 'LS02470', 'LS02489')
order by supp_name
;

-- =====================================================================
-- VIEW MGTDAT.MGT_PEND_LDN_TO_TPDN_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.MGT_PEND_LDN_TO_TPDN_V AS
SELECT a.invh_sys_id,
       a.dn_no,
       a.dn_dt,
       a.qty,
       a.cust_code,
       a.cust_name,
       a.truck_no,
       a.telp,
       a.Driver,
       a.transporter_code,
       a.transporter_name,
       a.destination
FROM (  SELECT  a.invh_sys_id,
                a.invh_txn_code || '-' || a.invh_no dn_no,
                a.invh_dt dn_dt,
                a.invh_cust_code cust_code,
                c.cust_name,
                a.invh_flex_02 truck_no,
                a.invh_flex_15 telp,
                a.invh_flex_03 Driver,
                a.invh_flex_06 transporter_code,
                d.supp_name transporter_name,
                a.invh_flex_07 destination,
                SUM (b.invi_qty_bu / 1000) qty
           FROM ot_invoice_head a,
                ot_invoice_item b,
                om_customer c,
                --om_supplier d
                (
                 select mtm_no, mtm_transp_name, mtm_transp_code, supp_name
                 from om_supplier,
                      mgt_transp_master
                 where mtm_transp_code = supp_code
                 ) d          
          WHERE     a.invh_sys_id = b.invi_invh_sys_id
                AND a.invh_txn_code IN ('LDN', 'PDN')
                AND a.invh_appr_status = 3
                AND a.invh_cust_code = c.cust_code
                --AND a.invh_flex_06 = d.supp_code
                AND a.invh_flex_06 = d.mtm_no
                AND a.invh_dt BETWEEN '01-JAN-2024' AND TO_DATE (SYSDATE)
                AND a.invh_txn_code || '-' || a.invh_no NOT IN ('LDN-2021007529', 'LDN-2022001817')
                AND NOT EXISTS (SELECT 'XXX' FROM ot_cust_sale_ret_head WHERE csrh_ref_txn_code || '-' || csrh_ref_no = a.invh_txn_code || '-' || a.invh_no)
                AND NOT EXISTS (SELECT 'XXX' FROM mgt_transp_detail_dn WHERE mtdd_dn_txn_code || '-' || mtdd_dn_no = a.invh_txn_code || '-' || a.invh_no)
       GROUP BY a.invh_sys_id,
                a.invh_txn_code || '-' || a.invh_no,
                a.invh_dt,
                a.invh_cust_code,
                c.cust_name,
                a.invh_flex_02,
                a.invh_flex_15,
                a.invh_flex_03,
                a.invh_flex_06,
                d.supp_name,
                a.invh_flex_07
       ORDER BY 2, 3 ASC
       ) a
;

-- =====================================================================
-- VIEW MGTDAT.MGT_PEND_TPDN_TO_PROVISION_V
-- =====================================================================
CREATE OR REPLACE VIEW MGTDAT.MGT_PEND_TPDN_TO_PROVISION_V AS
SELECT mth_txn_code || '-' || mth_transp_no tp_no,
            mth_dt tp_dt,
            mth_no tp_code,
            mth_transp_name tp_name,
            mth_truck_type tp_truck_type,
            mth_truck_cap tp_cap,
            mth_destination tp_destination,
            mth_no_police tp_pol_no,
            mth_driver tp_driver,
            tot_dn_qty tp_qty,
            total_gross_qty tp_gross_qty,
            mtdc_total_amount tp_amt,
            NVL (othchg_amount, 0) tp_oth_amt,
            DECODE (a.mth_txn_code,
                    'TPCHP', '208026',
                    'TPDN', '208027',
                    NULL)
               tp_main_acnt
       FROM mgt_transp_head a,
            (  SELECT mth_sys_id,
                      SUM (mtdc_total_rate) mtdc_total_amount,
                      SUM (mtdc_qty) total_gross_qty
                 FROM mgt_transp_head, mgt_transp_detail_cost
                WHERE     mth_sys_id = mtdc_mth_sys_id
                      AND mth_dt BETWEEN '01-JAN-2021' AND TO_DATE (SYSDATE)
             GROUP BY mth_sys_id) trn,
            (  SELECT mtdo_mth_sys_id, SUM (mtdo_othchg_amount) othchg_amount
                 FROM mgt_transp_head, mgt_transp_detail_othchg
                WHERE     mth_sys_id = mtdo_mth_sys_id
                      AND mth_dt BETWEEN '01-JAN-2021' AND TO_DATE (SYSDATE)
             GROUP BY mtdo_mth_sys_id) othchg,
            (  SELECT mtdd_mth_sys_id dn_sys_id, SUM (mtdd_dn_qty) tot_dn_qty
                 FROM mgt_transp_head, mgt_transp_detail_dn
                WHERE     mth_sys_id = mtdd_mth_sys_id
                      AND mth_dt BETWEEN '01-JAN-2021' AND TO_DATE (SYSDATE)
             GROUP BY mtdd_mth_sys_id) totdn
      WHERE     a.mth_sys_id = trn.mth_sys_id(+)
            AND a.mth_sys_id = mtdo_mth_sys_id(+)
            AND a.mth_sys_id = dn_sys_id(+)
            AND a.mth_transp_name NOT IN ('BY PARTY', 'JET')
            AND a.mth_dt BETWEEN '01-JAN-2021' AND TO_DATE (SYSDATE)
            AND a.mth_transp_code != 'LS00550'
            AND NOT EXISTS
                   (SELECT 'XXX'
                      FROM mgt_tp_provision
                     WHERE     tp_dt BETWEEN '01-JAN-2021'
                                         AND TO_DATE (SYSDATE)
                           AND tp_no = mth_txn_code || '-' || mth_transp_no)
   ORDER BY tp_no, tp_dt
;

