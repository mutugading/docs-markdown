<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}

	//$bgClrEntry = "style='text-align:center;height:20px; width:200px; background-color:yellow'";

	$P_DATE_S = "";
	if(isset($_POST['P_DATE_S'])) {
		$P_DATE_S=$_POST['P_DATE_S'];
	}

	//echo "P_DATE $P_DATE_S";
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	$MenuPrintHrefPdf = "";
	$MenuPrintHrefXls = "";

	include ("form_header.php");
	include ("check_login.php");
	include ($FORM_NAME."_SQL.php");

	//echo "STSPRS $STSPRS : FORM_NAME $FORM_NAME </br>";
	$mainSql = "";
	if ($P_DATE_S!==""){
			$mainSql = getMainSql($P_DATE_S);
	}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
<link rel="stylesheet" type="text/css" href="../css/MENU.css">
<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
<link rel="stylesheet" type="text/css" href="../css/blink.css">
<link rel="stylesheet" type="text/css" href="../css/CURSOR.css">

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
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
	<!-- DEFINE LAYOUT -->
	<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
		<?php
			//if($USER_NAME==="1949")	{echo $STSPRS; }

			if (substr($STSPRS,0,10)==="VIEW FILE "){
					echo substr($STSPRS,10);
					echo "<iframe src='".substr($STSPRS,10)."' width='100%' style='height:100%'></iframe>";
			} else {

				include ($FORM_NAME."_PARAM.php");
				if ($P_DATE_S!==""){
					if (substr($STSPRS,0,4)==="DTL "){
						include ($FORM_NAME."_VIEW_DETAIL.php");
					} //SEND MAIL
					else if (substr($STSPRS,0,10)==="SEND MAIL "){
						include ($FORM_NAME."_SEND_MAIL.php");
					} else include ($FORM_NAME."_VIEW.php");
				}

			}
		?>
	</div>
	<!-- DEFINE LAYOUT -->
	<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
	<input type="hidden" name="STSPRS" id="STSPRS">
	<input type="hidden" name="P_DATE_S" id="P_DATE_S">
	<?php include ($FORM_NAME."_JS.php"); ?>
</form>
</body>
</html>
