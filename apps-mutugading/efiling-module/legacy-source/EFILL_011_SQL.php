<?php

  function getTotPage($conn,$sqlCnt,$TOT_ROWS){
    $totData = 0;
    $rsDt = oci_parse($conn,$sqlCnt);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$totData=$rsRowDt['TOT_DATA'];
		}
  	return ceil($totData / $TOT_ROWS);
  }



  function getSqlDoc ($conn){

    $WHERE_DOC_DTE = "";
    $sqlDt = "select * from MST_PARAM_DATA where MPD_MPDK_KEY='EFIL_SCAN_DOCS_TYPE' order by nvl(mpd_seq_no,99)";
    $rsDt = oci_parse($conn,$sqlDt);
		oci_execute ($rsDt); $totDt=0;
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
      $MPD_VALUE=$rsRowDt['MPD_VALUE'];
      $totDt++;
      if($totDt===1){ $WHERE_DOC_DTE=$WHERE_DOC_DTE."'$MPD_VALUE'"; }
      else $WHERE_DOC_DTE=$WHERE_DOC_DTE.",'$MPD_VALUE'";

		}

    if ($WHERE_DOC_DTE!==""){
        $WHERE_DOC_DTE="where ETD_SYS_ID in ($WHERE_DOC_DTE)";
    }
    return "
            select ETD_SYS_ID DOC_TYPE
            from mgtapps.EFILL_TYPE_DATA
            $WHERE_DOC_DTE
            order by ETD_SYS_ID
          ";

    
  }

  function getSqlData($PERIOD_START_S,$PERIOD_END_S){

    return
          "
SELECT TH_TRAN_CODE , TH_DOC_NO , TH_TRAN_CODE || '-' || TH_DOC_NO TRX_NO, TH_DOC_DT, TH_DESC, TH_CR_UID, USER_DESC, TH_CR_DT
FROM
(
SELECT th_tran_code, th_doc_no, th_doc_dt, th_desc, TH_CR_UID, USER_DESC, TH_CR_DT
FROM mgtdat.ft_cur_trans_header, mgtdat.MENU_USER
WHERE th_doc_dt between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND th_tran_code NOT IN ('FXJ','FXADJ','RJV')
AND TH_CR_UID = USER_ID
union all
SELECT th_tran_code, th_doc_no, th_doc_dt, th_desc, TH_CR_UID, USER_DESC, TH_CR_DT
FROM mgtdat.ft_prv_trans_header, mgtdat.MENU_USER
WHERE th_doc_dt between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND th_tran_code NOT IN ('FXJ','FXADJ','RJV')
AND TH_CR_UID = USER_ID
) where not exists
(select 1 from
(
    select pgdn_gen_tran_code rh_tran_code, pgdn_gen_doc_no rh_doc_no
    from mgtdat.FT_PAYMENT_HEADER, mgtdat.FT_PAYMENT_GEN_DOC_NOS
    WHERE PH_DOC_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
    AND PGDN_PH_SYS_ID = PH_SYS_ID
    UNION ALL
    SELECT INVH_TH_TRAN_CODE, INVH_TH_DOC_NO
    FROM mgtdat.ot_invoice_head
    WHERE NVL (invh_appr_status, 0) IN (3)
    AND INVH_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
    AND NVL(INVH_STATUS,0) = 1
    UNION ALL
    SELECT ADJH_TH_TRAN_CODE, ADJH_TH_DOC_NO
    FROM mgtdat.ot_adj_head
    WHERE NVL (adjh_appr_status, 0) IN (3)
    AND ADJH_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
    AND NVL(ADJH_POST_STATUS,0) = 1
    )
where  th_tran_code = rh_tran_code
and th_doc_no = rh_doc_no
)
UNION ALL
select  PH_TRAN_CODE, PH_DOC_NO, PH_TRAN_CODE||'-'||PH_DOC_NO TRX_NO, PH_DOC_DT, PH_REMARKS, PH_CR_UID, USER_DESC, PH_CR_DT
from mgtdat.FT_PAYMENT_HEADER, mgtdat.MENU_USER
WHERE PH_DOC_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND PH_CR_UID = USER_ID
UNION ALL
SELECT INVH_TXN_CODE, INVH_NO, INVH_TXN_CODE||'-'||INVH_NO, INVH_DT, INVH_ANNOTATION, INVH_CR_UID, USER_DESC, INVH_CR_DT
FROM mgtdat.ot_invoice_head, mgtdat.MENU_USER
WHERE NVL (invh_appr_status, 0) IN (3)
AND INVH_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND NVL(INVH_STATUS,0) = 1
AND INVH_CR_UID = USER_ID
UNION ALL
SELECT ADJH_TXN_CODE, ADJH_NO, ADJH_TXN_CODE||'-'||ADJH_NO, ADJH_DT, ADJH_ANNOTATION, ADJH_CR_UID, USER_DESC, ADJH_CR_DT
FROM mgtdat.ot_adj_head, mgtdat.MENU_USER
WHERE NVL (adjh_appr_status, 0) IN (3)
AND ADJH_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND NVL(ADJH_POST_STATUS,0) = 1
AND ADJH_CR_UID = USER_ID
union all
SELECT   'FP SALES' TRX_FOLDER,
TO_NUMBER(CASE
WHEN invh_fp_no IS NULL
THEN ''
ELSE  REPLACE (REPLACE (invh_fp_no, '.', ''), '-', '')
END) TRANS_NO,
CASE
WHEN invh_fp_no IS NULL
THEN ''
ELSE    'FP-'
|| REPLACE (REPLACE (invh_fp_no, '.', ''), '-', '')
END TRX_NO,
invh_dt TRX_DT, NULL, a.INVH_CR_UID, a.USER_DESC, a.INVH_CR_DT
FROM (SELECT *
FROM mgtdat.ot_invoice_head a, mgtdat.MENU_USER
WHERE invh_type = 1
AND INVH_CR_UID = USER_ID
AND a.invh_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')) a,
mgtdat.ft_os,
mgtdat.ot_tax_invoice_mgt b,
mgtdat.ot_invoice_ref
WHERE a.invh_txn_code = ost_tran_code
AND a.invh_no = ost_doc_no
AND a.invh_txn_code NOT IN ('EIN')
AND a.invh_sys_id = b.invh_sys_id(+)
AND a.invh_sys_id = invr_invh_sys_id(+)
and invh_fp_no is not null
union all
SELECT DISTINCT SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1) trx_folder,
TO_NUMBER(SUBSTR (trans_no, INSTR (trans_no, '-') + 1)) trans_no,
SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1)
|| '-'
|| SUBSTR (trans_no, INSTR (trans_no, '-') + 1) trx_no,
trx_dt trx_dt, NULL, null CR_UID, null USER_DESC, null CR_DT
FROM (SELECT DISTINCT    'KWI-'
|| LPAD (kwi_yy, 2, 0)
|| LPAD (kwi_mm, 2, 0)
|| LPAD (kwi_slno, 5, 0) trans_no,
TO_CHAR (kwi_date, 'YYYY') folder_year,
kwi_date trx_dt, kwi_cust, cust_name
FROM mgtdat.pakkwi, mgtdat.om_customer
WHERE kwi_cust = cust_code
AND kwi_date BETWEEN  TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD'))
UNION ALL
SELECT PH_TXN_CODE, PH_NO, PH_TXN_CODE||'-'||PH_NO, PH_DT, PH_ANNOTATION, PH_CR_UID, USER_DESC, PH_CR_DT
FROM mgtdat.ot_po_head, mgtdat.MENU_USER
WHERE NVL (ph_appr_status, 0) IN (3)
AND PH_DT between TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
AND PH_CR_UID = USER_ID
ORDER BY 1,2
          ";

  }

  function getSqlView ($PERIOD_START_S,$PERIOD_END_S,$DOC_TYPE_S){
		$Type_S = "";
		$return = "";
    //echo "PERIOD_START_S $PERIOD_START_S : PERIOD_END_S $PERIOD_END_S : DOC_TYPE_S $DOC_TYPE_S";

		if ($DOC_TYPE_S==="PDN"||$DOC_TYPE_S==="LDN"||$DOC_TYPE_S==="LDN SALES"){
			if ($DOC_TYPE_S==="LDN"||$DOC_TYPE_S==="LDN SALES"){
				$Type_S = "LDN";
			} else {
				$DOC_TYPE_S = "LDN";
			}

			$return =
			"SELECT   	distinct invr_ref_txn_code || '-' || invr_ref_no TRX_NO
						,invr_ref_txn_code TRX_FOLDER
						,SUBSTR (invr_ref_no, 1, 4) TRX_YY
		        ,to_char(invh_dt,'YYYY-MM-DD') TRX_DT
            ,a.INVH_CR_UID CRT_BY
		    FROM (SELECT *
		            FROM mgtdat.ot_invoice_head a
		           WHERE invh_type = 1
		             AND a.invh_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD')
		                               AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')) a,
		         mgtdat.ft_os,
		         mgtdat.ot_tax_invoice_mgt b,
		         mgtdat.ot_invoice_ref
		   	WHERE a.invh_txn_code = ost_tran_code
		     AND a.invh_no = ost_doc_no
		     AND a.invh_txn_code NOT IN ('EIN')
		     AND a.invh_sys_id = b.invh_sys_id(+)
		     AND a.invh_sys_id = invr_invh_sys_id(+)
		     and invr_ref_txn_code = '$Type_S'
			ORDER BY to_char(invh_dt,'YYYY-MM-DD')";
		} else if ($DOC_TYPE_S==="LIN") {
			$return =
			"SELECT   	distinct a.invh_txn_code, a.invh_no
						,a.invh_txn_code || '-' || a.invh_no TRX_NO
						,a.invh_txn_code TRX_FOLDER
     		   	, SUBSTR (a.invh_no, 1, 4) TRX_YY
     		   	,to_char(invh_dt,'YYYY-MM-DD') TRX_DT
    		FROM (SELECT *
            		FROM mgtdat.ot_invoice_head a
           		 WHERE invh_type = 1
             	 AND a.invh_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD')
                               AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')) a,
         		mgtdat.ft_os,
	         	mgtdat.ot_tax_invoice_mgt b,
	         	mgtdat.ot_invoice_ref
	   		WHERE a.invh_txn_code = ost_tran_code
	     	AND a.invh_no = ost_doc_no
	     	AND a.invh_txn_code NOT IN ('EIN')
	     	AND a.invh_sys_id = b.invh_sys_id(+)
	     	AND a.invh_sys_id = invr_invh_sys_id(+)
	     	and a.invh_txn_code = '$DOC_TYPE_S'
	     	";
		} else if ($DOC_TYPE_S==="FP SALES"){
			$return =
			"SELECT   CASE
		            WHEN invh_fp_no IS NULL
		               THEN ''
		            ELSE    'FP-'
		                 || REPLACE (REPLACE (invh_fp_no, '.', ''), '-', '')
		         END TRX_NO,
		         'FP SALES' TRX_FOLDER, SUBSTR (a.invh_no, 1, 4) TRX_YY
		         ,to_char(invh_dt,'YYYY-MM-DD') TRX_DT
		    FROM (SELECT *
		            FROM mgtdat.ot_invoice_head a
		           WHERE invh_type = 1
		             AND a.invh_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD')
		                               AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')) a,
		         mgtdat.ft_os,
		         mgtdat.ot_tax_invoice_mgt b,
		         mgtdat.ot_invoice_ref
		   	WHERE a.invh_txn_code = ost_tran_code
		     AND a.invh_no = ost_doc_no
		     AND a.invh_txn_code NOT IN ('EIN')
		     AND a.invh_sys_id = b.invh_sys_id(+)
		     AND a.invh_sys_id = invr_invh_sys_id(+)
		     and invh_fp_no is not null
			ORDER BY invh_dt, invh_fp_no";
		} else if ( $DOC_TYPE_S==="NSBR"||$DOC_TYPE_S==="CSBR"||$DOC_TYPE_S==="JJV"||$DOC_TYPE_S==="LPO"||$DOC_TYPE_S==="IPO"||$DOC_TYPE_S==="JV"
					||$DOC_TYPE_S==="JSBR"||$DOC_TYPE_S==="TJV"||$DOC_TYPE_S==="LSPO"||$DOC_TYPE_S==="SCSR"||$DOC_TYPE_S==="CN"||$DOC_TYPE_S==="LCPO"
					||$DOC_TYPE_S==="JPO"||$DOC_TYPE_S==="SSBR"||$DOC_TYPE_S==="ICPO"){

			$return = "
			SELECT DISTINCT to_char(ph_doc_dt,'YYYY-MM-DD') trx_dt,to_char(ph_doc_dt,'YYYY') TRX_YY,
                SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1) TRX_FOLDER,
                SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1)||'-'||SUBSTR (trans_no, INSTR (trans_no, '-') + 1) TRX_NO
           FROM (SELECT ph_tran_code || '-' || ph_doc_no payment_no,
                        ph_doc_dt, ph_party_code, supp_name, ph_bank_acnt_no,
                        bank_name, ph_curr_code, ph_fc_amt, user_desc,
                        ph_remarks,
                        pd_ref_tran_code || '-' || pd_ref_doc_no trans_no,
                        pd_ref_doc_ref, pd_fc_amt,
                        SUBSTR (pd_ref_doc_no, 1, 4) period_year,
                        user_field_01
                   FROM mgtdat.ft_payment_header,
                        mgtdat.ft_payment_detail,
                        mgtdat.menu_user,
                        mgtdat.om_supplier,
                        mgtdat.fm_bank
                  WHERE ph_doc_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD')
                                      AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
                    AND pd_ref_tran_code = '$DOC_TYPE_S'
                    AND ph_party_code = supp_code(+)
                    AND ph_cr_uid = user_id
                    AND pd_ph_sys_id = ph_sys_id
                    AND ph_bank_code = bank_code(+)
                 UNION ALL
                 SELECT ph_tran_code || '-' || ph_doc_no payment_no,
                        ph_doc_dt, ph_party_code, supp_name, ph_bank_acnt_no,
                        bank_name, ph_curr_code, ph_fc_amt, user_desc,
                        ph_remarks, poad_config_fld_02 trans_no, poad_remarks,
                        poad_amount,
                        SUBSTR (poad_config_fld_02,
                                INSTR (poad_config_fld_02, '-', 1) + 1,
                                4
                               ) yy,
                        user_field_01
                   FROM mgtdat.ft_payment_header,
                        mgtdat.ft_payment_oth_acnt_detail,
                        mgtdat.menu_user,
                        mgtdat.om_supplier,
                        mgtdat.fm_bank
                  WHERE ph_doc_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD')
                                      AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
                    AND SUBSTR (poad_config_fld_02, 1, INSTR (poad_config_fld_02, '-') - 1) = '$DOC_TYPE_S'
                    AND ph_party_code = supp_code(+)
                    AND ph_cr_uid = user_id
                    AND poad_ph_sys_id = ph_sys_id
                    AND ph_bank_code = bank_code(+))
			";

		} else if ($DOC_TYPE_S==="KWI"){
			$return =
			"SELECT DISTINCT TO_CHAR (trx_dt, 'YYYY-MM-DD') trx_dt,
                TO_CHAR (trx_dt, 'YYYY') trx_yy,
                SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1) trx_folder,
                   SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1)
                || '-'
                || SUBSTR (trans_no, INSTR (trans_no, '-') + 1) trx_no
           FROM (SELECT DISTINCT    'KWI-'
                                 || LPAD (kwi_yy, 2, 0)
                                 || LPAD (kwi_mm, 2, 0)
                                 || LPAD (kwi_slno, 5, 0) trans_no,
                                 TO_CHAR (kwi_date, 'YYYY') folder_year,
                                 kwi_date trx_dt, kwi_cust, cust_name
                            FROM mgtdat.pakkwi, mgtdat.om_customer
                           WHERE kwi_cust = cust_code
                             AND kwi_date BETWEEN TO_DATE ('$PERIOD_START_S',
                                                           'YYYY-MM-DD'
                                                          )
                                              AND TO_DATE ('$PERIOD_END_S',
                                                           'YYYY-MM-DD'
                                                          ))
       		ORDER BY trx_dt, trx_no";

		} else if ($DOC_TYPE_S==="BR" || $DOC_TYPE_S==="ADVBRCC" || $DOC_TYPE_S==="BRCC")  {

      $return =
      "
      select * from (
      select DISTINCT rh_tran_code,
                            rh_doc_no,
                            rh_tran_code || '-' || rh_doc_no     TRX_NO,
                            rh_tran_code                         TRX_FOLDER,
                            SUBSTR (rh_doc_no, 1, 4)                TRX_YY,
                            TO_CHAR (rh_doc_dt, 'YYYY-MM-DD')         TRX_DT
      from mgtdat.ft_receipt_header
      where rh_doc_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
      AND RH_PAY_SLIP_GEN_YN_NUM = 1
      UNION
      select DISTINCT TH_TRAN_CODE, TH_DOC_NO,
                            th_tran_code || '-' || TH_DOC_NO     TRX_NO,
                            th_tran_code                         TRX_FOLDER,
                            SUBSTR (th_doc_no, 1, 4)                TRX_YY,
                            TO_CHAR (TH_DOC_DT, 'YYYY-MM-DD')         TRX_DT
      from mgtdat.ft_cur_trans_header
      where th_doc_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
      and TH_TRAN_CODE in ('BR','ADVBRCC','BRCC')
      UNION
      select DISTINCT TH_TRAN_CODE, TH_DOC_NO,
                            th_tran_code || '-' || TH_DOC_NO     TRX_NO,
                            th_tran_code                         TRX_FOLDER,
                            SUBSTR (th_doc_no, 1, 4)                TRX_YY,
                            TO_CHAR (TH_DOC_DT, 'YYYY-MM-DD')         TRX_DT
      from mgtdat.ft_prv_trans_header
      where th_doc_dt BETWEEN TO_DATE ('$PERIOD_START_S', 'YYYY-MM-DD') AND TO_DATE ('$PERIOD_END_S', 'YYYY-MM-DD')
      and TH_TRAN_CODE in ('BR','ADVBRCC','BRCC')
      ) where rh_tran_code = '$DOC_TYPE_S'
      ";

    }
		return $return;
	}

?>
