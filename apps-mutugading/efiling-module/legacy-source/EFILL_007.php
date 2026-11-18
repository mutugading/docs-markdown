<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}
	//echo "STSPRS $STSPRS<br>";
	$DATE_FROM_S = "";if(isset($_POST['DATE_FROM_S'])) {$DATE_FROM_S=$_POST['DATE_FROM_S'];}
	$DATE_TO_S = "";if(isset($_POST['DATE_TO_S'])){$DATE_TO_S=$_POST['DATE_TO_S'];}
	if ($DATE_TO_S === ""){
		$curdate = date("Y-m-d");
		//echo "curdate $curdate</br>";
		$DATE_TO_S = $curdate;
	}

	$PAYMENT_NO_S = "";	if(isset($_POST['PAYMENT_NO_S'])){$PAYMENT_NO_S=$_POST['PAYMENT_NO_S'];}
	$FILTER_SCLT_S = "";if(isset($_POST['FILTER_SCLT_S'])){$FILTER_SCLT_S=$_POST['FILTER_SCLT_S'];}
	$FILTER_SCLT_VAL_S = "";if(isset($_POST['FILTER_SCLT_VAL_S'])){$FILTER_SCLT_VAL_S=$_POST['FILTER_SCLT_VAL_S'];}

	$FILTER_AREA_S= "";if(isset($_POST['FILTER_AREA_S'])) {$FILTER_AREA_S=$_POST['FILTER_AREA_S'];}
	$FILTER_SCLT_DATE_S= "";if(isset($_POST['FILTER_SCLT_DATE_S'])){$FILTER_SCLT_DATE_S=$_POST['FILTER_SCLT_DATE_S'];}
	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])){$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){$P_PAGE_NO= 1;}
	$P_LAST_PAGE_NO = "";

	$TOT_ROWS_S = "";if(isset($_POST['TOT_ROWS_S'])){$TOT_ROWS_S=$_POST['TOT_ROWS_S'];}
	if ($TOT_ROWS_S===""){$TOT_ROWS_S="10";}

	$pFilePath_S = "";if(isset($_POST['pFilePath_S'])){$pFilePath_S=$_POST['pFilePath_S'];}

	$Doc_N_A_S = "";if(isset($_POST['Doc_N_A_S'])){$Doc_N_A_S=$_POST['Doc_N_A_S'];}
	$MCM_N_A_S = "";if(isset($_POST['MCM_N_A_S'])){$MCM_N_A_S=$_POST['MCM_N_A_S'];}

	$StsViewDt = "";

	//Get Parameter

	//$dateH = date("H");
	//$date = str_replace('-', '/', $date);
	//echo "pFilePath_S $pFilePath_S<br>";

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "Y";
	if ($STSPRS === "VIEW_DATA" || $STSPRS === "FIND"){
		$MenuPrint = "Y";
	}

	$P_FILTER = $FILTER_SCLT_S;
	$P_FILTER_VALUE = "";

	if ($P_FILTER ==="Area User"){
		$P_FILTER_VALUE = $FILTER_AREA_S;
	} else {
		$P_FILTER_VALUE = $FILTER_SCLT_VAL_S;
	}

	$MenuPrintHrefPdf = "";
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_DATE_FROM=$DATE_FROM_S&P_DATE_TO=$DATE_TO_S&P_MENU_ID=$P_MENU_ID&P_FILTER=$P_FILTER"
	//					."&P_FILTER_VALUE=$P_FILTER_VALUE";
	$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_DATE_FROM=$DATE_FROM_S&P_DATE_TO=$DATE_TO_S&P_MENU_ID=$P_MENU_ID&P_FILTER=$P_FILTER"
						."&P_FILTER_VALUE=$P_FILTER_VALUE";


	include ("form_header.php");
	include ("check_login.php");

	$font = "12";

	$usrAreaLgn = getData($conn,"select nvl(USER_AREA,'ALL') GET_DATA from mgtapps.mst_USERs where USER_ID = '$USER_NAME' ");
	//echo "usrAreaLgn $usrAreaLgn</br>";
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('DATE_FROM').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
	$sqlView = "";
	include($FORM_NAME."_SQL.php");
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>" onkeypress="return event.keyCode != 13">
<div class="main" style="overflow-x:auto;overflow-y:auto;">
<!-- DEFINE LAYOUT -->
<?php

	if ($pFilePath_S!==""){
		include($FORM_NAME."_DB.php");
	}

	include($FORM_NAME."_LIST.php");
?>
<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="TOT_ROWS_S" id="TOT_ROWS_S" value="<?php echo $TOT_ROWS_S; ?>">

<input type="hidden" name="STSPRS" id="STSPRS">

<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">

<input type="hidden" name="PAYMENT_NO_S" id="PAYMENT_NO_S">
<input type="hidden" name="FILTER_SCLT_S" id="FILTER_SCLT_S">
<input type="hidden" name="FILTER_SCLT_VAL_S" id="FILTER_SCLT_VAL_S">

<input type="hidden" name="FILTER_AREA_S" id="FILTER_AREA_S">
<input type="hidden" name="FILTER_SCLT_DATE_S" id="FILTER_SCLT_DATE_S">

<input type="hidden" name="Doc_N_A_S" id="Doc_N_A_S">
<input type="hidden" name="MCM_N_A_S" id="MCM_N_A_S">

<input type="hidden" name="pFilePath_S" id="pFilePath_S" >

<?php include($FORM_NAME."_JS.php"); ?>

</form>
</body>
</html>
