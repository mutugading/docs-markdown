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

  function getSqlShow($DATE_FROM_S,$DATE_TO_S){

    return "
            SELECT   TPB_TRX_NO, TPB_DT,TPB_SUPP_CODE,TPB_SUPP_NAME, TPB_BILL_NO, TPB_BILL_DT, TP_NO,
                     TP_DT, TP_QTY, MTDD_DN_TXN_CODE, DN_NO, MTDD_DN_QTY,YY
                FROM mgtdat.mgt_tp_provision_bill,
                     mgtdat.mgt_tp_provision,
                     (SELECT mth_txn_code || '-' || mth_transp_no tpdn_no, mth_no, mth_dt,
                             mth_transp_name, mth_truck_type, mth_truck_cap,
                             mth_destination, mth_no_police, mth_driver, mtdd_dn_txn_code,
                             mtdd_dn_txn_code || '-' || mtdd_dn_no dn_no, mtdd_dn_qty,substr(mtdd_dn_no,1,4) YY
                        FROM mgtdat.mgt_transp_head, mgtdat.mgt_transp_detail_dn
                       WHERE mth_sys_id = mtdd_mth_sys_id)
               WHERE
                     tpb_dt BETWEEN TO_DATE ('$DATE_FROM_S', 'YYYY-MM-DD') and TO_DATE ('$DATE_TO_S', 'YYYY-MM-DD')
                 AND tpb_sys_id = tp_tpb_sys_id
                 AND tp_no = tpdn_no(+)
            ORDER BY TPB_DT,tpb_trx_no, tp_sys_id, dn_no ";

  }

  function getSqlDtl($TRX_NO){

    return "
            SELECT   TPB_TRX_NO, TPB_DT,TPB_SUPP_CODE,TPB_SUPP_NAME, TPB_BILL_NO, TPB_BILL_DT, TP_NO,
                     TP_DT, TP_QTY, MTDD_DN_TXN_CODE, DN_NO,DN_DT, MTDD_DN_QTY,YY
                FROM mgtdat.mgt_tp_provision_bill,
                     mgtdat.mgt_tp_provision,
                     (SELECT mth_txn_code || '-' || mth_transp_no tpdn_no, mth_no, mth_dt,
                             mth_transp_name, mth_truck_type, mth_truck_cap,
                             mth_destination, mth_no_police, mth_driver, mtdd_dn_txn_code,
                             mtdd_dn_txn_code || '-' || mtdd_dn_no dn_no, invh_dt dn_dt, mtdd_dn_qty,substr(mtdd_dn_no,1,4) YY
                        FROM mgtdat.mgt_transp_head, mgtdat.mgt_transp_detail_dn,
                                 (
                                 select invh_txn_code, invh_no, invh_dt
                                 from mgtdat.ot_invoice_head
                                 where invh_type in (2,3)
                                 union all
                                 select GH_TXN_CODE, GH_NO, GH_DT
                                 from mgtdat.ot_gr_head
                                 )
                       WHERE mth_sys_id = mtdd_mth_sys_id
                       and mtdd_dn_txn_code =  invh_txn_code (+)
                       and mtdd_dn_no = invh_no (+)
                       )
               WHERE TPB_TRX_NO = '$TRX_NO'
                 AND tpb_sys_id = tp_tpb_sys_id
                 AND tp_no = tpdn_no(+)
            ORDER BY TPB_DT,tpb_trx_no, tp_sys_id, dn_no ";


  }

?>
