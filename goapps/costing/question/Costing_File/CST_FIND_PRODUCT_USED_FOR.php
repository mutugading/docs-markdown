<?php
// $SqlCk =   "select MGTAPPS.pkg_yarn_prs_all.fIsRunningPrs GET_DATA from dual";
// $isRun = getData($conn,$SqlCk);
//
// if ($isRun==="Y"){
// 	$SqlCk = "select 'Y' GET_DATA from mgtapps.CST_ALL_PROD_PRS_HDR
// 						where nvl(CAPPH_STS_PRS,0) = 0 and CAPPH_CYL_PRS_TYPE = '20210800119' ";
// 	$isMkt = getData($conn,$SqlCk);
// 	if ($isMkt!=="Y"){
// 		$isRun="N";
// 	}
// }
//
// if ($isRun==="N"){
	//if ($USER_NAME === "1949"){ echo "$USER_NAME : STSPRS $STSPRS : CYL_TYPE_S $CYL_TYPE_S : CYL_LEFT_NO_S $CYL_LEFT_NO_S </br>";	}
	include ($FORM_NAME."_PARAM.php");
	//if ($USER_NAME==="1949"){ echo "FORM_NAME $FORM_NAME<br>"; }
	if ($STSPRS==="VIEW_DATA"){
		include ("CST_FIND_PRODUCT_VIEW_USED_FOR.php");
	}
// }else {
// 	include("CST_VIEW_LOG_MKT_PRS.php");
// }
?>
