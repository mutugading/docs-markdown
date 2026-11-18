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
	//echo $FORM_NAME."_SQL.php </br>";
	//echo "FORM_NAME $FORM_NAME : P_MENU_ID $P_MENU_ID : USER_NAME $USER_NAME<br>";

	$cur_date = date("Y-m-d");
	//Get Parameter
	$ETD_SYS_ID_S = "";	if(isset($_POST['ETD_SYS_ID_S'])){$ETD_SYS_ID_S=$_POST['ETD_SYS_ID_S'];}

	$FOLDER_VAL_1_S = "";	if(isset($_POST['FOLDER_VAL_1_S'])){$FOLDER_VAL_1_S=$_POST['FOLDER_VAL_1_S'];}
	$FOLDER_VAL_1_V = "";	if(isset($_POST['FOLDER_VAL_1_V'])){$FOLDER_VAL_1_V=$_POST['FOLDER_VAL_1_V'];}

	$FOLDER_VAL_2_S = "";	if(isset($_POST['FOLDER_VAL_2_S'])){$FOLDER_VAL_2_S=$_POST['FOLDER_VAL_2_S'];}
	$FOLDER_VAL_2_V = "";	if(isset($_POST['FOLDER_VAL_2_V'])){$FOLDER_VAL_2_V=$_POST['FOLDER_VAL_2_V'];}

	$FOLDER_VAL_3_S = "";	if(isset($_POST['FOLDER_VAL_3_S'])){$FOLDER_VAL_3_S=$_POST['FOLDER_VAL_3_S'];}
	$FOLDER_VAL_3_V = "";	if(isset($_POST['FOLDER_VAL_3_V'])){$FOLDER_VAL_3_V=$_POST['FOLDER_VAL_3_V'];}

	$FOLDER_VAL_4_S = "";	if(isset($_POST['FOLDER_VAL_4_S'])){$FOLDER_VAL_4_S=$_POST['FOLDER_VAL_4_S'];}
	$FOLDER_VAL_4_V = "";	if(isset($_POST['FOLDER_VAL_4_V'])){$FOLDER_VAL_4_V=$_POST['FOLDER_VAL_4_V'];}

	$FOLDER_VAL_5_S = "";	if(isset($_POST['FOLDER_VAL_5_S'])){$FOLDER_VAL_5_S=$_POST['FOLDER_VAL_5_S'];}
	$FOLDER_VAL_5_V = "";	if(isset($_POST['FOLDER_VAL_5_V'])){$FOLDER_VAL_5_V=$_POST['FOLDER_VAL_5_V'];}

	$FIND_DATA_S="";if(isset($_POST['FIND_DATA_S'])) {$FIND_DATA_S=$_POST['FIND_DATA_S'];	}

	$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
	$USER_NAME_DTL = getData($conn,$sqlGetData);

	//echo "ETD_SYS_ID_S $ETD_SYS_ID_S : FOLDER_VAL_1_S $FOLDER_VAL_1_S : FOLDER_VAL_1_V $FOLDER_VAL_1_V : FIND_DATA_S $FIND_DATA_S<br>";


?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center" onclick="closeNav()">
	<form action="<?php echo $FORM_NAME.'.PHP'; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>"
				onkeypress="return event.keyCode != 13"
	>
	<input type="hidden" name="REC_INFOCUS" id="REC_INFOCUS" value="<?php echo $REC_INFOCUS_S; ?>">
	<?php
		include ($FORM_NAME."_SHOW.php");
		include ($FORM_NAME."_JS.php");
	?>
	<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
	<input type="hidden" name="ETD_SYS_ID_S" id="ETD_SYS_ID_S" >

	<input type="hidden" name="FOLDER_VAL_1_S" id="FOLDER_VAL_1_S" >
	<input type="hidden" name="FOLDER_VAL_1_V" id="FOLDER_VAL_1_V" >

	<input type="hidden" name="FOLDER_VAL_2_S" id="FOLDER_VAL_2_S" >
	<input type="hidden" name="FOLDER_VAL_2_V" id="FOLDER_VAL_2_V" >

	<input type="hidden" name="FOLDER_VAL_3_S" id="FOLDER_VAL_3_S" >
	<input type="hidden" name="FOLDER_VAL_3_V" id="FOLDER_VAL_3_V" >

	<input type="hidden" name="FOLDER_VAL_4_S" id="FOLDER_VAL_4_S" >
	<input type="hidden" name="FOLDER_VAL_4_V" id="FOLDER_VAL_4_V" >

	<input type="hidden" name="FOLDER_VAL_5_S" id="FOLDER_VAL_5_S" >
	<input type="hidden" name="FOLDER_VAL_5_V" id="FOLDER_VAL_5_V" >

	<input type="hidden" name="FIND_DATA_S" id="FIND_DATA_S">

	<input type="hidden" name="VIEW_LINK" id="VIEW_LINK">

	<input type="hidden" name="LOG_REMARKS_S" id="LOG_REMARKS_S">

	</form>
</div>
</body>
</html>
