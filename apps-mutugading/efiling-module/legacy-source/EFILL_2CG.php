<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");	include ("../common_function.php"); include ("FORM_NAME.php"); include("EFILL_SQL.php");
  //echo "FORM_NAME $FORM_NAME : P_MENU_ID $P_MENU_ID<br>";

  //Get Parameter
  $STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

  $ETD_SYS_ID_S = "NULL";	 if(isset($_POST['ETD_SYS_ID_S'])) {$ETD_SYS_ID_S=$_POST['ETD_SYS_ID_S'];}

  $ETD_SYS_ID_S_OLD = "NULL";	 if(isset($_POST['ETD_SYS_ID_S_OLD'])) {$ETD_SYS_ID_S_OLD=$_POST['ETD_SYS_ID_S_OLD'];}

  $FOLDER_VAL_1_S= "NULL"; if(isset($_POST['FOLDER_VAL_1_S'])){$FOLDER_VAL_1_S=$_POST['FOLDER_VAL_1_S'];}
  $FOLDER_VAL_1_V = "";	if(isset($_POST['FOLDER_VAL_1_V'])){$FOLDER_VAL_1_V=$_POST['FOLDER_VAL_1_V'];}

  $FOLDER_VAL_2_S = "";	if(isset($_POST['FOLDER_VAL_2_S'])){$FOLDER_VAL_2_S=$_POST['FOLDER_VAL_2_S'];}
  $FOLDER_VAL_2_V = "";	if(isset($_POST['FOLDER_VAL_2_V'])){$FOLDER_VAL_2_V=$_POST['FOLDER_VAL_2_V'];}

  $FOLDER_VAL_3_S = "";	if(isset($_POST['FOLDER_VAL_3_S'])) {$FOLDER_VAL_3_S=$_POST['FOLDER_VAL_3_S'];}
  $FOLDER_VAL_3_V = "";	if(isset($_POST['FOLDER_VAL_2_V'])){$FOLDER_VAL_3_V=$_POST['FOLDER_VAL_3_V'];}

  $FOLDER_VAL_4_S = "";	if(isset($_POST['FOLDER_VAL_4_S'])) {$FOLDER_VAL_4_S=$_POST['FOLDER_VAL_4_S'];}
  $FOLDER_VAL_4_V = "";	if(isset($_POST['FOLDER_VAL_4_V'])) {$FOLDER_VAL_4_V=$_POST['FOLDER_VAL_4_V'];}

  $FOLDER_VAL_5_S = ""; if(isset($_POST['FOLDER_VAL_5_S'])) {$FOLDER_VAL_5_S=$_POST['FOLDER_VAL_5_S'];}
  $FOLDER_VAL_5_V = "";	if(isset($_POST['FOLDER_VAL_5_V'])) {$FOLDER_VAL_5_V=$_POST['FOLDER_VAL_5_V'];}
  $FIND_DATA_S	= ""; if(isset($_POST['FIND_DATA_S'])){$FIND_DATA_S=$_POST['FIND_DATA_S'];}

  $VIEW_LINK="";
    if(isset($_POST['VIEW_LINK'])) {
    $VIEW_LINK=$_POST['VIEW_LINK'];
  }
  //Get Parameter

  $MenuHeader1 = "Y";
  $MenuUtamaHdr = "Y";
  $MenuLogoutHdr = "Y";
  $MenuPrint = "";
  //$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
  //					."&P_MENU_ID=$P_MENU_ID";
  //$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
  //					."&P_MENU_ID=$P_MENU_ID";


  include ("form_header_efill.php");
  include ("check_login.php");

?>
<!DOCTYPE html>
<html>
<head>
  <?php include("head_component_efill.php") ?>
<body>
<?php include("menuData.php"); ?>
<form action="<?php echo "$FORM_NAME"; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
  <?php
		include($FORM_NAME."_LAYOUT.php");
		include("../CALCULATOR_FORM.php");
	?>
</form>
<?php
  include($FORM_NAME."_LOV_JS.php");
  include($FORM_NAME."_JS.php");
?>
</body>
</html>
</div>
