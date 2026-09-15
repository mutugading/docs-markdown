<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("conOraOci.php");
	include ("common_function.php");
	include ("FORM_NAME.php");
	//include ("CST_T_009_VIEW_XLS_FUNCTION.php");

	$byType = "Y";
	$byShade = "";
	$byCust = "";
	$byShadeMulti = "";
	$CYL_IS_PROJECT_S = "";
	//Get Parameter
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}
	$PERIOD_MONTH_S = "";
	if(isset($_POST['PERIOD_MONTH_S'])) {
		$PERIOD_MONTH_S=$_POST['PERIOD_MONTH_S'];
	}
	$PERIOD_YEAR_S = "";
	if(isset($_POST['PERIOD_YEAR_S'])) {
		$PERIOD_YEAR_S=$_POST['PERIOD_YEAR_S'];
	}
	//Get Parameter

	echo "STSPRS	$STSPRS : PERIOD_MONTH_S $PERIOD_MONTH_S : PERIOD_YEAR_S $PERIOD_YEAR_S";

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "Y";
	$MenuPrintHrefPdf = "";
	$MenuPrintHrefXls = $FORM_NAME."_DWNLD_XLS?FORM_NAME=".$FORM_NAME."&PERIOD_YEAR_S=$PERIOD_YEAR_S&PERIOD_MONTH_S=$PERIOD_MONTH_S";
	include ("form_header_download.php");
	include ("check_login.php");

	$styleSlct = "style='color:blue'";
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
<link rel="stylesheet" type="text/css" href="../css/MENU_2.css">
<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
<link rel="stylesheet" type="text/css" href="../css/blink.css">
<link rel="stylesheet" type="text/css" href="../css/AUTO_COMPLETE.css">
<link rel="stylesheet" type="text/css" href="../css/tooltips.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="../style.css">

<!-- tooltip -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<!-- tooltip -->

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style type="text/css">
	a {
	  border: 1px solid black;
	}
	table {
	  border-collapse: collapse;
	  width: 100%;
	}
</style>
</head>
<body >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<!--<div class="main" style="overflow-x:auto;" align="center">-->
<!-- DEFINE LAYOUT -->
<?php

	if ( $STSPRS === "PROCESS")//		$PERIOD_MONTH_S!=="" && $PERIOD_YEAR_S !==""	)
	{
		//CST_T_010

	} else {
		include ($FORM_NAME."_PARAM.php");
	}
?>

<!-- DEFINE LAYOUT -->
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">

<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="PERIOD_YEAR_S" id="PERIOD_YEAR_S">
<input type="hidden" name="PERIOD_MONTH_S" id="PERIOD_MONTH_S">

<?php include ($FORM_NAME."_JS.php"); ?>

</form>
</body>
</html>
