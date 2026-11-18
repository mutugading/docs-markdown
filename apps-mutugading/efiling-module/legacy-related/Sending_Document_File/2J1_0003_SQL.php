<?php

  function getMainSql($P_DATE_S){
      return
      "
      SELECT cust_name,
             td_sub_acnt_code cust_code,
             td_doc_dt advance_dt,
             TO_CHAR (td_doc_dt, 'YYYY-MM-DD') fp_dt,
             a.invh_txn_code || '-' || a.invh_doc_no advance_no,
             a.invh_doc_no,
                'FP-'
             || REPLACE (REPLACE (a.invh_fp_no, '.', ''), '-')
                fp_no,
             c.td_fc_amt advance_amt,
             a.invh_sys_id sys_id,
             SUBSTR (a.invh_doc_no, 1, 4) folder_yy,
             'FP SALES' fp_folder,
             a.invh_txn_code adv_folder
      FROM mgtdat.ot_tax_invoice_mgt a,
           (SELECT td_sub_acnt_code,
                   td_doc_dt,
                   td_tran_code,
                   td_doc_no,
                   td_fc_amt,
                   cust_name
              FROM mgtdat.ft_cur_trans_detail, mgtdat.om_customer
             WHERE     TRUNC (td_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
                   AND td_sub_acnt_code IS NOT NULL
                   AND td_doc_drcr_flag = 'D'
                   AND td_sub_acnt_code = cust_code
                   and not exists (select 'xxx' from mgtdat.ft_reversal_jv where rjv_orgl_tran_code = td_tran_code and rjv_orgl_doc_no = td_doc_no)
            UNION ALL
            SELECT td_sub_acnt_code,
                   td_doc_dt,
                   td_tran_code,
                   td_doc_no,
                   td_fc_amt,
                   cust_name
              FROM mgtdat.ft_prv_trans_detail, mgtdat.om_customer
             WHERE     TRUNC (td_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
                   AND td_sub_acnt_code IS NOT NULL
                   AND td_doc_drcr_flag = 'D'
                   AND td_sub_acnt_code = cust_code
                   and not exists (select 'xxx' from mgtdat.ft_reversal_jv where rjv_orgl_tran_code = td_tran_code and rjv_orgl_doc_no = td_doc_no)
                   ) b,
           (SELECT td_tran_code, td_doc_no, td_fc_amt
              FROM mgtdat.ft_cur_trans_detail,
                   mgtdat.fm_main_account
             WHERE     TRUNC (td_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
                   AND td_main_acnt_code = main_acnt_code
                   AND main_acnt_catg = 'BA'
                   and not exists (select 'xxx' from mgtdat.ft_reversal_jv where rjv_orgl_tran_code = td_tran_code and rjv_orgl_doc_no = td_doc_no)
            UNION ALL
            SELECT td_tran_code, td_doc_no, td_fc_amt
              FROM mgtdat.ft_prv_trans_detail,
                   mgtdat.fm_main_account
             WHERE     TRUNC (td_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
                   AND td_main_acnt_code = main_acnt_code
                   AND main_acnt_catg = 'BA'
                   and not exists (select 'xxx' from mgtdat.ft_reversal_jv where rjv_orgl_tran_code = td_tran_code and rjv_orgl_doc_no = td_doc_no)
                   ) c
     WHERE     a.invh_txn_code = 'ADVBRCC'
           AND TRUNC (a.invh_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
           AND a.invh_txn_code || '-' || a.invh_doc_no =
                  b.td_tran_code || '-' || b.td_doc_no
           AND a.invh_txn_code || '-' || a.invh_doc_no =
                  c.td_tran_code || '-' || c.td_doc_no
      ";
  }

  function getMainSqlOld($P_DATE_S){
      return
      "SELECT
            (SELECT cust_name FROM mgtdat.om_customer WHERE cust_code = b.td_sub_acnt_code)cust_name
            ,td_sub_acnt_code cust_code
            ,td_doc_dt advance_dt
            ,substr(a.invh_doc_no,1,4) FOLDER_YY
            ,a.invh_txn_code || '-' || a.invh_doc_no advance_no
            ,a.invh_txn_code ADV_FOLDER
            ,'FP-' || REPLACE (REPLACE (a.invh_fp_no, '.', ''), '-') fp_no
            ,'FP SALES' FP_FOLDER
            ,a.invh_sys_id sys_id
      FROM mgtdat.ot_tax_invoice_mgt a, mgtdat.ft_cur_trans_detail b
     WHERE     a.invh_txn_code = 'ADVBRCC'
           AND trunc(a.invh_doc_dt) = trunc(to_date('$P_DATE_S','YYYY-MM-DD'))
           AND a.invh_txn_code || '-' || a.invh_doc_no =
                  b.td_tran_code || '-' || b.td_doc_no
           AND b.td_sub_acnt_code IS NOT NULL
           AND b.td_doc_drcr_flag = 'D'
      ";
  }

?>
