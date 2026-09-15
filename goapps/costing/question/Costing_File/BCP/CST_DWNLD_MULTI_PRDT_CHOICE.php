<?php
$P_CYL_SYS_ID_DTL = $rowRsView["CYL_SYS_ID"];
$sqlCkRm55 = "select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
							where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'
							and rownum = 1
							";
$STS_RM_55 = getData($conn,$sqlCkRm55);

if ($STS_RM_55==="Y"){
	include("CST_DWNLD_MULTI_PRDT_RM55.php");
} else {

	$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
	$isUseRmBo = getData($conn,$slctUseRm);

	if ($isUseRmBo === "Y"){

	} else {

		if ($CYL_TYPE_CK==="ITY"){
			include("CST_DWNLD_MULTI_PRDT_ITY.php");
		}else if ($CYL_TYPE_CK==="MELANGE"){
			include("CST_DWNLD_MULTI_PRDT_MELANGE.php");
		}else if ($CYL_TYPE_CK==="ACY"){
			include("CST_DWNLD_MULTI_PRDT_ACY.php");
		}else if ($CYL_TYPE_CK==="PTY BO"){
			include("CST_DWNLD_MULTI_PRDT_PTY_BO.php");
		}else{
			include("CST_DWNLD_MULTI_PRDT_DFLT.php");
		}

	}

}


$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension('A')->setWidth(20);
?>
