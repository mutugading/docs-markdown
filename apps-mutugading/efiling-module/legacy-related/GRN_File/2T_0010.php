<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("GRN_SQL.php");
	include ("FORM_NAME.php");

	$cur_date = date("Y-m-d");
	//Get Parameter
	$STSPRS = "";	if(isset($_GET['STSPRS'])){$STSPRS=$_GET['STSPRS'];}
	if ($STSPRS===""){if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}}

	$ITEM_FOCUS_S=""; if(isset($_POST['ITEM_FOCUS_S'])) {$ITEM_FOCUS_S=$_POST['ITEM_FOCUS_S'];}

	$DATE_FROM_S=""; if(isset($_POST['DATE_FROM_S'])) {$DATE_FROM_S=$_POST['DATE_FROM_S'];}
	$DATE_TO_S=""; if(isset($_POST['DATE_TO_S'])) {$DATE_TO_S=$_POST['DATE_TO_S'];}
	if ($DATE_TO_S===""){$DATE_TO_S=$cur_date;}
	$GS_GRN_NO_SLCT_S=""; if(isset($_POST['GS_GRN_NO_SLCT_S'])) {$GS_GRN_NO_SLCT_S=$_POST['GS_GRN_NO_SLCT_S'];}
	$GH_DT_SLCT_S=""; if(isset($_POST['GH_DT_SLCT_S'])) {$GH_DT_SLCT_S=$_POST['GH_DT_SLCT_S'];}

	$FILE_DOC_VIEW=""; if(isset($_POST['FILE_DOC_VIEW'])) {$FILE_DOC_VIEW=$_POST['FILE_DOC_VIEW'];}

	$GRN_NO_MERGE=""; if(isset($_POST['GRN_NO_MERGE'])) {$GRN_NO_MERGE=$_POST['GRN_NO_MERGE'];}
	$GRN_FILE_MERGE=""; if(isset($_POST['GRN_FILE_MERGE'])) {$GRN_FILE_MERGE=$_POST['GRN_FILE_MERGE'];}
	$SJGRN_FILE_MERGE=""; if(isset($_POST['SJGRN_FILE_MERGE'])) {$SJGRN_FILE_MERGE=$_POST['SJGRN_FILE_MERGE'];}

	$GRN_TO_MERGE_S=""; if(isset($_POST['GRN_TO_MERGE_S'])) {$GRN_TO_MERGE_S=$_POST['GRN_TO_MERGE_S'];}
	$TOT_ROWS_S= 0;
	if(isset($_POST['TOT_ROWS_S'])) {$TOT_ROWS_S=$_POST['TOT_ROWS_S'];}
	if ($TOT_ROWS_S=== 0){
		//$TOT_ROWS_S = getMaxRowDisp($conn);
		$TOT_ROWS_S=20;
	}
	$P_PAGE_NO = "";
	if(isset($_POST['P_PAGE_NO'])) { $P_PAGE_NO=$_POST['P_PAGE_NO']; }
	if ($P_PAGE_NO===""){ $P_PAGE_NO= 1; }
	$P_LAST_PAGE_NO = "";
	//echo "STSPRS $STSPRS : P_PAGE_NO $P_PAGE_NO<br>";
	//echo "STSPRS $STSPRS<br>";

	$bgClrEntry = "style='text-align:center;height:20px; width:100px; background-color:yellow'";
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	include ("form_header.php");
	include ("check_login.php");
	// include ("GRN_SQL.php");
	include ($FORM_NAME."_SQL.php");

	$SqlGet = "select 'Y' GET_DATA from MST_PARAM_DATA where MPD_MPDK_KEY = 'ADMIN_GRN_APPROVE' and MPD_VALUE = '$USER_NAME' ";
	$USER_ADMIN =  getData($conn,$SqlGet);

	$USER_DEPT =  "";
	if ($USER_ADMIN===""){
		$SqlGet = "select TRIM(USER_DEPT) GET_DATA from mst_users where user_id = '$USER_NAME' ";
		$USER_DEPT =  getData($conn,$SqlGet);
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
<link rel="stylesheet" type="text/css" href="../css/BUTTON_BORDER.css">
<link rel="stylesheet" type="text/css" href="../css/buttonPagging.css">

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

<!-- tooltip -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>
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

	th {
	  height: 80px;
	}
}
</style>
</head>
<body
	<?php if ($ITEM_FOCUS_S!==""){ ?>
		onload="Javascript: document.getElementById('<?php echo $ITEM_FOCUS_S;  ?>').focus();"
	<?php } ?>
>
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<form action="<?php echo $FORM_NAME.'.PHP'; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>" onkeypress="return event.keyCode != 13">
	<!-- DEFINE LAYOUT -->
	<?php
  	$bgClrList = "bgcolor='#F6E3CE'"; $bgClrListDtl = "bgcolor='#BCB8B8'";
		$fileDest = "d:/XAMPP/htdocs/webapps/Doc_Folder/";
		$fileWeb = "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
		if ($STSPRS==="VIEW_FILE" || $STSPRS==="MERGE_FILE" || $STSPRS==="MERGE_ALL_FILE"){
			include ($FORM_NAME."_DB.php");
		}

		$sqlList = "";
		if ($DATE_FROM_S!=="" && $DATE_TO_S	!==""){
				$sqlList = getSqlList($DATE_FROM_S,$DATE_TO_S);
				//echo "$sqlList<br>";
		}

		include ($FORM_NAME."_PARAM.php");
		if ($sqlList !== ""){
				include ($FORM_NAME."_LIST.php");
		}
	?>

	<!-- DEFINE LAYOUT -->
	<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
	<input type="hidden" name="STSPRS" id="STSPRS">
	<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">
	<input type="hidden" name="TOT_ROWS_S" id="TOT_ROWS_S" value="<?php echo $TOT_ROWS_S; ?>">
	<input type="hidden" name="ITEM_FOCUS_S" id="ITEM_FOCUS_S" >

	<!-- Parameter -->
	<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
	<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">
	<input type="hidden" name="GS_GRN_NO_SLCT_S" id="GS_GRN_NO_SLCT_S">
	<input type="hidden" name="GH_DT_SLCT_S" id="GH_DT_SLCT_S">
	<!-- Parameter -->

	<input type="hidden" name="FILE_DOC_VIEW" id="FILE_DOC_VIEW">

	<input type="hidden" name="GRN_NO_MERGE" id="GRN_NO_MERGE">
	<input type="hidden" name="GRN_FILE_MERGE" id="GRN_FILE_MERGE">
	<input type="hidden" name="SJGRN_FILE_MERGE" id="SJGRN_FILE_MERGE">

	<input type="hidden" name="GRN_TO_MERGE_S" id="GRN_TO_MERGE_S">

	<?php include ($FORM_NAME."_JS.php"); ?>
</form>
</body>
</html>
