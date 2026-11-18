<?php
include("../conOraOci.php"); include ("../common_function.php"); $font="12"; include("EFILL_SQL.php");
$FORM_NAME = $_GET['FORM_NAME']; $USER_NAME = $_GET['USER_NAME']; $P_MENU_ID = $_GET['P_MENU_ID']; $STS_LOV = $_GET['STS_LOV'];
$FIND_LOV = $_GET['FIND_LOV'];
include ($FORM_NAME."_SQL.php");
//echo "FORM_NAME $FORM_NAME : USER_NAME $USER_NAME : P_MENU_ID $P_MENU_ID : STS_LOV $STS_LOV : FIND_LOV $FIND_LOV<br>";
$font="12"; $bgClrList = "bgcolor='#F6E3CE'"; $bgClrListDtl = "bgcolor='#BCB8B8'";
if ($STS_LOV==="LOV_FOLDER"){
		include ($FORM_NAME."_LOV_FOLDER.php");
}
?>
