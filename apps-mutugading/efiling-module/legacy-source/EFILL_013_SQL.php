<?php 
	$sqlViewHdr =
		"SELECT DISTINCT OST_TRAN_CODE||'-'||OST_DOC_NO PAYMENT_NO, OST_DOC_DT, OST_SUB_ACNT_CODE, SUPP_NAME
				,'MCM' TRX_FOLDER,to_char(OST_DOC_DT,'YYYY') TRX_YY,OST_TRAN_CODE||'-'||OST_DOC_NO TRX_NO
		FROM mgtdat.ft_os, mgtdat.om_supplier, mgtdat.FT_PAYMENT_HEADER
		WHERE ost_tran_code not in ('FXADJ','FXJ')
		and substr(ost_main_acnt_code,1,3) = '203'
		AND OST_DOC_DT between to_date('$DATE_FROM_S','YYYY-MM-DD') and to_date('$DATE_TO_S','YYYY-MM-DD')
		AND OST_DRCR_FLAG = 'D'
		and ost_sub_acnt_code = SUPP_CODE
		AND OST_TRAN_CODE = PH_TRAN_CODE
		AND OST_DOC_NO = PH_DOC_NO
		";

	$sqlViewDtl =
	"SELECT OM_OST_KEY_NO MATCH_SYS_ID,
       OST_TRAN_CODE || '-' || OST_DOC_NO MATCH_TRN_CODE,
       to_char(OST_DOC_DT,'YYYY-MM-DD') MATCH_DOC_DT, OST_KEY_NO MATCH_KEY_NO,
       NVL (OM_BASE_FC_AMT, OM_BASE_FOREX_FC_AMT) MATCH_FC_AMT, MATCH_PO_NO
  	FROM mgtdat.fv_os_match,
       mgtdat.ft_os,
       (SELECT bh_txn_code, bh_no,
               gh_ref_txn_code || '-' || gh_ref_no match_po_no
          FROM mgtdat.ot_bill_head, mgtdat.ot_gr_head
         WHERE bh_ref_sys_id = gh_sys_id)
 	WHERE ost_key_no = om_base_seq_no
	   AND NVL (ost_tran_code, 'FXADJ') != ('FXADJ')
	   AND ost_tran_code = bh_txn_code(+)
	   AND ost_doc_no = bh_no(+)";

	function fGetSqlDtl($DATE_FROM_S,$DATE_TO_S,$OM_OST_KEY_NO){
		return
		"SELECT   	PAYMENT_NO, OST_DOC_DT, OST_SUB_ACNT_CODE, SUPP_NAME, PH_CURR_CODE,
         			PH_FC_AMT
         			, MATCH_TRN_CODE TRN_CODE
	        		,SUBSTR(MATCH_TRN_CODE,1,INSTR(MATCH_TRN_CODE,'-')-1) TRN_CODE_FOLDER
	        		,SUBSTR(MATCH_TRN_CODE,INSTR(MATCH_TRN_CODE,'-')+1,4) TRN_CODE_YY
         			, to_char(MATCH_DOC_DT,'YYYY-MM-DD') MATCH_DOC_DT
         			, MATCH_PO_NO PO_NO
					,SUBSTR(MATCH_PO_NO,1,INSTR(MATCH_PO_NO,'-')-1) PO_NO_FOLDER
	        		,SUBSTR(MATCH_PO_NO,INSTR(MATCH_PO_NO,'-')+1,4) PO_NO_YY
         			, MATCH_FC_AMT
         			,OST_KEY_NO
    	FROM (SELECT DISTINCT ost_tran_code || '-' || ost_doc_no payment_no,
                          ost_doc_dt, ost_sub_acnt_code, supp_name,
                          ph_curr_code, ph_fc_amt, ost_key_no
                     FROM mgtdat.ft_os, mgtdat.om_supplier, mgtdat.ft_payment_header
                    WHERE ost_tran_code NOT IN ('FXADJ', 'FXJ')
                      AND SUBSTR (ost_main_acnt_code, 1, 3) = '203'
                      AND ost_doc_dt BETWEEN TO_DATE ('$DATE_FROM_S','YYYY-MM-DD')AND TO_DATE ('$DATE_TO_S','YYYY-MM-DD')
                      AND ost_drcr_flag = 'D'
                      AND ost_sub_acnt_code = supp_code
                      AND ost_tran_code = ph_tran_code
                      AND ost_doc_no = ph_doc_no
                      AND ph_bank_code IS NOT NULL),
         (SELECT om_ost_key_no match_sys_id,
                 ost_tran_code || '-' || ost_doc_no match_trn_code,
                 ost_doc_dt match_doc_dt, ost_key_no match_key_no,
                 NVL (om_base_fc_amt, om_base_forex_fc_amt) match_fc_amt,
                 NVL (om_base_lc_amt, om_base_forex_lc_amt) match_lc_amt,
                 match_po_no
            FROM mgtdat.fv_os_match,
                 mgtdat.ft_os,
                 (SELECT bh_txn_code, bh_no,
                         gh_ref_txn_code || '-' || gh_ref_no match_po_no
                    FROM mgtdat.ot_bill_head, mgtdat.ot_gr_head
                   WHERE bh_ref_sys_id = gh_sys_id)
           WHERE ost_key_no = om_base_seq_no
             AND NVL (ost_tran_code, 'FXADJ') != ('FXADJ')
             AND ost_tran_code = bh_txn_code(+)
             AND ost_doc_no = bh_no(+))
	   WHERE ost_key_no = match_sys_id(+)
   		and ost_key_no in ($OM_OST_KEY_NO)
		ORDER BY ost_doc_dt, payment_no";
	}

	function fGetPO2($conn,$OM_OST_KEY_NO){
		$return = "";
		if ($OM_OST_KEY_NO!==""){
			$sqlDt =
			"SELECT  OM_OST_KEY_NO,MATCH_PO_NO,NVL (om_base_fc_amt, om_base_forex_fc_amt) MATCH_FC_AMT
			FROM mgtdat.fv_os_match,mgtdat.ft_os,
				(SELECT bh_txn_code, bh_no,gh_ref_txn_code || '-' || gh_ref_no match_po_no
				 FROM mgtdat.ot_bill_head, mgtdat.ot_gr_head
		     	 WHERE bh_ref_sys_id = gh_sys_id
		     	)
			WHERE ost_key_no = om_base_seq_no
		   	AND NVL (ost_tran_code, 'FXADJ') != ('FXADJ')
		   	AND ost_tran_code = bh_txn_code(+)
		   	AND ost_doc_no = bh_no(+)
		   	and OM_OST_KEY_NO in ($OM_OST_KEY_NO) ";

			$rsDt = oci_parse($conn,$sqlDt);
			oci_execute ($rsDt);

			while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
				if (!empty($rowDt['MATCH_PO_NO'])){
					if ($return!==""){
						$return = $return.",".$rowDt['MATCH_PO_NO'];
					} else {
						$return = $rowDt['MATCH_PO_NO'];
					}

				}
			}
		}

		return $return;
	}

	function fGet_OstKeyNo($conn,$PAYMENT_NO,$DATE_FROM_S,$DATE_TO_S){
		$sqlDt =
		"SELECT d.*
            FROM (SELECT DISTINCT OST_TRAN_CODE || '-'|| OST_DOC_NO ,OST_KEY_NO
                             FROM mgtdat.ft_os,
                                  mgtdat.om_supplier,
                                  mgtdat.ft_payment_header
                            WHERE ost_tran_code NOT IN ('FXADJ', 'FXJ')
                              AND SUBSTR (ost_main_acnt_code, 1, 3) = '203'
                              AND ost_doc_dt BETWEEN TO_DATE ('$DATE_FROM_S',
                                                              'YYYY-MM-DD'
                                                             )
                                                 AND TO_DATE ('$DATE_TO_S',
                                                              'YYYY-MM-DD'
                                                             )
                              AND ost_drcr_flag = 'D'
                              AND ost_sub_acnt_code = supp_code
                              AND ost_tran_code = ph_tran_code
                              AND ost_doc_no = ph_doc_no
                              and ost_tran_code || '-'|| ost_doc_no = '$PAYMENT_NO'
        ) d";

        //echo $sqlDt;

        $rsDt = oci_parse($conn,$sqlDt);
		oci_execute ($rsDt);
		$return = "";
		while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			if (!empty($rowDt['OST_KEY_NO'])){
				if ($return!==""){
					$return = $return.",".$rowDt['OST_KEY_NO'];
				} else {
					$return = $rowDt['OST_KEY_NO'];
				}

			}
		}
		return $return;
	}

?>
