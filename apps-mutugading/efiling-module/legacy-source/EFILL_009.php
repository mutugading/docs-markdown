<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");
	include ($FORM_NAME."_SQL.php");

	//echo "$AMSW_SCHEDULER_NAME_FND_S $AMSW_SCHEDULER_NAME_FND_S<br>";
	$bgClrEntry = "style='text-align:center;height:20px; width:100px; background-color:yellow'";
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	$MenuPrintHrefPdf = "";
	$MenuPrintHrefXls = "";
	$MenuPrint = "Y";

	if ($MenuPrint === "Y"){
	}

	include ("form_header.php");
	include ("check_login.php");
	// echo $FORM_NAME."_SQL.php </br>";

	$cur_date = date("Y-m-d");
	//Get Parameter
	$DATE_FROM_S = $cur_date;	if(isset($_POST['DATE_FROM_S'])){$DATE_FROM_S=$_POST['DATE_FROM_S'];} if($DATE_FROM_S ===""){ $DATE_FROM_S=$cur_date;}
	$DATE_TO_S = $cur_date;	if(isset($_POST['DATE_TO_S'])){$DATE_TO_S=$_POST['DATE_TO_S'];} if($DATE_TO_S ===""){ $DATE_TO_S=$cur_date;}
  $AREA_PRM_S = "ALL";	if(isset($_POST['AREA_PRM'])){$AREA_PRM_S=$_POST['AREA_PRM'];} if($AREA_PRM_S===""){ $AREA_PRM_S="ALL"; }
	$TRANS_CODE_PRM_S = "";	if(isset($_POST['TRANS_CODE_PRM_S'])){$TRANS_CODE_PRM_S=$_POST['TRANS_CODE_PRM_S'];}
	$P_PAGE_NO="1"; if(isset($_POST['P_PAGE_NO'])){$P_PAGE_NO=$_POST['P_PAGE_NO'];} if($P_PAGE_NO===""){ $P_PAGE_NO="1"; }
	$TOT_ROWS_S="15"; if(isset($_POST['TOT_ROWS_S'])){$TOT_ROWS_S=$_POST['TOT_ROWS_S'];} if($TOT_ROWS_S===""){ $TOT_ROWS_S="15"; }

	$FILTER_BY_S = "Null";	if(isset($_POST['FILTER_BY_S'])){$FILTER_BY_S=$_POST['FILTER_BY_S'];} if($FILTER_BY_S===""){ $FILTER_BY_S="Null"; }
	$FILTER_VAL_S = "";	if(isset($_POST['FILTER_VAL_S'])){$FILTER_VAL_S=$_POST['FILTER_VAL_S'];}

	$REC_INFOCUS_S = "";	if(isset($_POST['REC_INFOCUS'])){$REC_INFOCUS_S=$_POST['REC_INFOCUS'];}

	$sqlCnt = "select count(-1) TOT_DATA from (".getSqlShow($DATE_FROM_S,$DATE_TO_S,$AREA_PRM_S,$TRANS_CODE_PRM_S).") a";
	//echo "$sqlCnt<br>";
  // $StsViewDt =  getData($conn,$sqlCnt);
	// $totDt = $StsViewDt;
	//echo "totDt $totDt : TOT_ROWS_S $TOT_ROWS_S<br>";
	$P_LAST_PAGE_NO = getTotPage($conn,$sqlCnt,$TOT_ROWS_S);
	//$P_LAST_PAGE_NO = ceil($totDt / $TOT_ROWS_S);
	if ($P_LAST_PAGE_NO===0){$P_LAST_PAGE_NO=1;}

?>
<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
	<link rel="stylesheet" type="text/css" href="../css/MENU.css">
	<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
	<link rel="stylesheet" type="text/css" href="../css/blink.css">
	<link rel="stylesheet" type="text/css" href="../css/CURSOR.css">
	<link rel="stylesheet" type="text/css" href="../css/buttonPagging.css">

	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

	<!-- tooltip -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
	<!-- tooltip -->

	<style type="text/css">
		a {
		  border: 1px solid black;
		}

		table {
		  border-collapse: collapse;
		  width: 100%;
		}

		th {
		  height: 80px;
		}
	}
	</style>
</head>
<body  onload="Javascript: showData();" >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center" onclick="closeNav()">
	<form action="<?php echo $FORM_NAME.'.PHP'; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>"
				onkeypress="return event.keyCode != 13"
	>
	<input type="hidden" name="REC_INFOCUS" id="REC_INFOCUS" value="<?php echo $REC_INFOCUS_S; ?>">
	<?php	include ($FORM_NAME."_PARAM.php"); ?>
		<span class='blink'>
			<font size ='5' color = 'red'><div id="lable"></div></font>
		</span>
		<div id="dataHint"></div>
		<div>
			<iframe id="VIEW_FILE" width='100%' style="height:0%" hidden></iframe>
		</div>
		<?php
			include ($FORM_NAME."_JS.php");
		?>
		<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
		<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
		<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">
		<input type="hidden" name="AREA_PRM_S" id="AREA_PRM_S">
		<input type="hidden" name="TRANS_CODE_PRM_S" id="TRANS_CODE_PRM_S">
		<input type="hidden" name="TOT_ROWS_S" id="TOT_ROWS_S">
		<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO">

		<input type="hidden" name="FILTER_BY_S" id="FILTER_BY_S">
		<input type="hidden" name="FILTER_VAL_S" id="FILTER_VAL_S">

	</form>
</div>
</body>
</html>
