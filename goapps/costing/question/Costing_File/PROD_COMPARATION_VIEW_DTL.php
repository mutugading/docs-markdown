<?php
include("../conOraOci.php"); include ("../common_function.php");
$font="10";
$P_CYL_SYS_ID = $_POST['P_CYL_SYS_ID'] ?? '';
$FORM_NAME = $_POST['FORM_NAME'] ?? '';
$USER_NAME = $_POST['USER_NAME'] ?? '';

include($FORM_NAME."_SQL.php");

$P_CYCRM_SYS_ID = "20211206";
$SqlCk =   "select MGTAPPS.pkg_yarn_prs_all.fIsRunningPrs GET_DATA from dual";
$isRun = getData($conn,$SqlCk);

if ($isRun==="Y"){
	$SqlCk = "select 'Y' GET_DATA from mgtapps.CST_ALL_PROD_PRS_HDR
						where nvl(CAPPH_STS_PRS,0) = 0 and CAPPH_CYL_PRS_TYPE = '20210800119' ";
	$isMkt = getData($conn,$SqlCk);
	if ($isMkt!=="Y"){
		$isRun="N";
	}
}

if ($isRun==="N"){
  $font="10";
  if($P_CYL_SYS_ID!=="") {
		$widthSttg = "width:100%";//"width:$widthTbl%";
		$sqlCkType = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID'";
		$CYL_TYPE_CK = getData($conn,$sqlCkType);
		$IS_RM_55 = getData($conn
	                       ,"select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
	                         where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID'
	                         and rownum = 1
	                         ");
		if($IS_RM_55==="Y"){
			include($FORM_NAME."_VIEW_DTL_55.php");
		} else {
			if($CYL_TYPE_CK==="ITY"){
				include($FORM_NAME."_VIEW_DTL_ITY.php");
			}else if($CYL_TYPE_CK==="MELANGE"){
				include($FORM_NAME."_VIEW_DTL_MELANGE.php");
			}else if($CYL_TYPE_CK==="PTY BO"){
				include($FORM_NAME."_VIEW_DTL_PTY_BO.php");
			}else //if($CYL_TYPE_CK==="TTY" || $CYL_TYPE_CK==="PTY"  )
			{
				include($FORM_NAME."_VIEW_DTL_GENERAL.php");
			}
			// else {
			// 	echo "Under Construction";
			// }
		}
  }
}
?>
