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

  function getSqlShow(){

    return "
              select * from ALRT_M_SCDLR_WEB order by AMSW_CREATED_TIMESTAMP
           ";
  }

  function getSqlDtl($dtlCustCode){

    return "
            select rownum REC_NO,MEC_SYS_ID,MEC_SEQ_NO, MEC_MAIL, MEC_ACTIVE
            from mst_email_cust where MEC_CUST_CODE = '$dtlCustCode'
            ";
  }

  function getSqlGroup(){
    return "select * from mst_groups a order by groups_name";
  }

  function getSqlScdlrMail(){
    return "select * from ALRT_M_SCDLR_WEB_MAIL";
  }

?>
