<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}
	$DATE_FROM_S = "";if(isset($_POST['DATE_FROM_S'])) {$DATE_FROM_S=$_POST['DATE_FROM_S'];}
	$DATE_TO_S = "";if(isset($_POST['DATE_TO_S'])) {$DATE_TO_S=$_POST['DATE_TO_S'];}
	if ($DATE_TO_S === ""){
		$curdate = date("Y-m-d");
		//echo "curdate $curdate</br>";
		$DATE_TO_S = $curdate;
	}

	$FILTER_SCLT_S = "";if(isset($_POST['FILTER_SCLT_S'])) {$FILTER_SCLT_S=$_POST['FILTER_SCLT_S'];}

	$FILTER_SCLT_VAL_S = "";if(isset($_POST['FILTER_SCLT_VAL_S'])) {$FILTER_SCLT_VAL_S=$_POST['FILTER_SCLT_VAL_S'];}

	$FILTER_SCLT_DATE_S= "";if(isset($_POST['FILTER_SCLT_DATE_S'])) {$FILTER_SCLT_DATE_S=$_POST['FILTER_SCLT_DATE_S'];}

	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){
		$P_PAGE_NO= 1;
	}

	$P_LAST_PAGE_NO = "";
	//echo "1 P_PAGE_NO $P_PAGE_NO : P_LAST_PAGE_NO $P_LAST_PAGE_NO</br>";

	$linkFile = "";if(isset($_POST['linkFile'])) {$linkFile=$_POST['linkFile'];}

	$linkFileDtl = "";
	if(isset($_POST['linkFileDtl'])) {
		$linkFileDtl=$_POST['linkFileDtl'];
	}

	$linkFileName= "";
	if(isset($_POST['linkFileName'])) {
		$linkFileName=$_POST['linkFileName'];
	}

	//Get Parameter

	//$dateH = date("H");
	//$date = date("Y-m-d");
	//$date = str_replace('-', '/', $date);

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";
	//$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";


	include ("form_header.php");
	include ("check_login.php");

	if ($P_MENU_ID===""){
		if(isset($_GET['P_MENU_ID'])) {
				$P_MENU_ID=$_GET['P_MENU_ID'];
		}
	}
	if ($FORM_NAME===""){
		if(isset($_POST['FORM_NAME'])) {
				$FORM_NAME=$_POST['FORM_NAME'];
		}
	}
	include($FORM_NAME.'_SQL.PHP');

	if ($USER_NAME===""){
		if(isset($_POST['USER_NAME'])) {
				$USER_NAME=$_POST['USER_NAME'];
		}
	}
	$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
	$USER_NAME_DTL = getData($conn,$sqlGetData);

	//echo "STSPRS $STSPRS : FORM_NAME $FORM_NAME : linkFile $linkFile </br>";

?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('NIK').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
<!-- DEFINE LAYOUT -->
<?php
if ($linkFile!=="" || $linkFileDtl!==""){
	$DetailAccess = "";
	if ($linkFile!=="" ){
		$DetailAccess = "View Document -> Efilling PO Transaction Listing -> $linkFile";
		pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
		echo "$linkFileName </br>";
		echo "<iframe src='$linkFile' width='100%' style='height:100%'></iframe>";
	} else if ($linkFileDtl!=="" ){
		$DetailAccess = "View Document -> Efilling PO Transaction Listing Detail -> $linkFileDtl";
		pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
		echo "$linkFileName </br>";
		echo "<iframe src='$linkFileDtl' width='100%' style='height:100%'></iframe>";
	}
} else {
	if (substr($STSPRS,0,9)==="VIEW_DTL " ){
		//echo "No Trx ".substr($STSPRS,9)." </br>";
		include($FORM_NAME.'_VIEW_DTL.PHP');
	} else {
?>
	<table style="width:100%">
	   	<tr>
			<td align="center">
	   			<b>Period Start</b>&nbsp<input type="date" id="DATE_FROM" name="DATE_FROM" value="<?php echo $DATE_FROM_S; ?>">
	   			<b>end</b> <input type="date" id="DATE_TO" name="DATE_TO" value="<?php echo $DATE_TO_S; ?>">
				<input type="BUTTON" class="BUTTON btn_process" value="VIEW DATA" onclick="Process('VIEW_DATA')">
	   			<input type="BUTTON" class="BUTTON btn_delete" value="CLEAR" onclick="Process('CLEAR_FILTER')" >
	   		</td>
	   	</tr>
	</table>
	<?php
	include($FORM_NAME.'_VIEW_DATA.PHP');
	}
}
?>

<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">
<input type="hidden" name="FORM_NAME" id="FORM_NAME" value="<?php echo $FORM_NAME; ?>">
<input type="hidden" name="USER_NAME" id="USER_NAME">

<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">

<input type="hidden" name="FILTER_SCLT_S" id="FILTER_SCLT_S">
<input type="hidden" name="FILTER_SCLT_VAL_S" id="FILTER_SCLT_VAL_S">
<input type="hidden" name="FILTER_SCLT_DATE_S" id="FILTER_SCLT_DATE_S">

<input type="hidden" name="linkFile" id="linkFile">
<input type="hidden" name="linkFileName" id="linkFileName" value="<?php echo $linkFileName; ?>">

<input type="hidden" name="STSPRS" id="STSPRS">
<?php
	include($FORM_NAME.'_JS.PHP');
?>
</form>
</body>
</html>
