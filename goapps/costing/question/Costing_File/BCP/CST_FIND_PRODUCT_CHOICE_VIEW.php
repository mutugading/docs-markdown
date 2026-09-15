<?php
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
	//if ($USER_NAME === "1949"){ echo "$USER_NAME : STSPRS $STSPRS : CYL_TYPE_S $CYL_TYPE_S : CYL_LEFT_NO_S $CYL_LEFT_NO_S </br>";	}
	if (substr($STSPRS,0,7) === "DETAIL " ){
		//if ($USER_NAME === "1949"){ echo "CST_FIND_PRODUCT_VIEW_DETAIL </br>";	}
		include ("CST_FIND_PRODUCT_VIEW_DETAIL.php");
	} else if (substr($STSPRS,0,11) === "SIMULATION " ){
		include ("CST_FIND_PRODUCT_SIMULATION.php");
	} else{
		//include ($FORM_NAME."_PARAM.php");
		include ($FORM_NAME."_PARAM.php");
		//if ($USER_NAME==="1949"){ echo "FORM_NAME $FORM_NAME<br>"; }
		if ($STSPRS==="VIEW_DATA"){
			if ($CYL_TYPE_S==="SUPERBA"){
				if ($USER_NAME==="1949"){ echo "include CST_FIND_PRODUCT_VIEW_SUPERBA<br>"; }
				include ("CST_FIND_PRODUCT_VIEW_SUPERBA.php");
			}else {
				if ($USER_NAME==="1949"){ echo "include form CST_FIND_PRODUCT_VIEW<br>"; }
				include ("CST_FIND_PRODUCT_VIEW.php");
			}
		}
	}
}else {
	include("CST_VIEW_LOG_MKT_PRS.php");
}
?>
</div>
