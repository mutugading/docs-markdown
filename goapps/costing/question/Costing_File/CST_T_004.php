<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	$byType = "";
	$byShade = "";
	$byCust = "Y";
	$byShadeMulti = "";

	//Get Parameter
	$STSPRS = "";if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}
	$CYL_TYPE_S="";	if(isset($_POST['CYL_TYPE_S'])) {$CYL_TYPE_S=$_POST['CYL_TYPE_S'];}
	if ($CYL_TYPE_S===""){$CYL_TYPE_S="NULL";}

	$CYL_SHADE_CODE_S="";if(isset($_POST['CYL_SHADE_CODE_S'])) {$CYL_SHADE_CODE_S=$_POST['CYL_SHADE_CODE_S'];}
	if ($CYL_SHADE_CODE_S===""){$CYL_SHADE_CODE_S="NULL";}

	$CYL_SHADE_NAME_S="";if(isset($_POST['CYL_SHADE_NAME_S'])) {$CYL_SHADE_NAME_S=$_POST['CYL_SHADE_NAME_S'];}
	if ($CYL_SHADE_NAME_S===""){$CYL_SHADE_NAME_S="NULL";}

	$CYL_PRODUCT_QUALITY_S="";if(isset($_POST['CYL_PRODUCT_QUALITY_S'])) {$CYL_PRODUCT_QUALITY_S=$_POST['CYL_PRODUCT_QUALITY_S'];}
	if ($CYL_PRODUCT_QUALITY_S===""){$CYL_PRODUCT_QUALITY_S="NULL";}

	$CMY_LUSTURE_S="";if(isset($_POST['CMY_LUSTURE_S'])) {$CMY_LUSTURE_S=$_POST['CMY_LUSTURE_S'];}
	if ($CMY_LUSTURE_S===""){$CMY_LUSTURE_S="NULL";}

	$CMY_PRODUCTION_S="";if(isset($_POST['CMY_PRODUCTION_S'])) {$CMY_PRODUCTION_S=$_POST['CMY_PRODUCTION_S'];}
	if ($CMY_PRODUCTION_S===""){$CMY_PRODUCTION_S="NULL";}

	$DENIER_S="";if(isset($_POST['DENIER_S'])) {$DENIER_S=$_POST['DENIER_S'];}
	if ($DENIER_S===""){$DENIER_S="NULL";}

	$FILAMENT_S="";	if(isset($_POST['FILAMENT_S'])) {$FILAMENT_S=$_POST['FILAMENT_S'];}
	if ($FILAMENT_S===""){$FILAMENT_S="NULL";}

	$INTERMINGLING_S="";if(isset($_POST['INTERMINGLING_S'])) {$INTERMINGLING_S=$_POST['INTERMINGLING_S'];}
	if ($INTERMINGLING_S===""){$INTERMINGLING_S="NULL";}

	$HEATSET_S="";if(isset($_POST['HEATSET_S'])) {$HEATSET_S=$_POST['HEATSET_S'];}
	if ($HEATSET_S===""){$HEATSET_S="NULL";}

	$CROSS_SECTION_S="";if(isset($_POST['CROSS_SECTION_S'])) {$CROSS_SECTION_S=$_POST['CROSS_SECTION_S'];}
	if ($CROSS_SECTION_S===""){$CROSS_SECTION_S="NULL";}

	$CUSTOMER_S="";if(isset($_POST['CUSTOMER_S'])) {$CUSTOMER_S=$_POST['CUSTOMER_S'];}
	if ($CUSTOMER_S===""){$CUSTOMER_S="NULL";}

	$CYL_LEFT_NO_S="";if(isset($_POST['CYL_LEFT_NO_S'])) {$CYL_LEFT_NO_S=$_POST['CYL_LEFT_NO_S'];}

	$SHADE_CHOICE="";	if(isset($_POST['SHADE_CHOICE'])) {$SHADE_CHOICE=$_POST['SHADE_CHOICE'];}
	$CYL_IS_PROJECT_S = "";
	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){$P_PAGE_NO= 1;} $P_LAST_PAGE_NO = "";

	$P_CYL_SYS_ID_DTL	= "";
	if (substr($STSPRS,0,7) === "DETAIL " ){
		$P_CYL_SYS_ID_DTL	= substr($STSPRS,7);
	}
	$P_CYCRM_SYS_ID = "20211206";
	$P_CYC_PRS_TYPE = "20210800119";
	//echo "STSPRS $STSPRS</br>";

	//Get Parameter
	//echo "STSPRS $STSPRS</br>";

	//$dateH = date("H");
	//$date = date("Y-m-d");
	//$date = str_replace('-', '/', $date);

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	if ($P_CYL_SYS_ID_DTL !== ""){
		$MenuPrint = "Y";
		$MenuPrintHrefPdf = "CST_FIND_PRODUCT_PDF?P_CYL_SYS_ID_DTL=$P_CYL_SYS_ID_DTL&P_CYCRM_SYS_ID=$P_CYCRM_SYS_ID";
		$MenuPrintHrefXls = "CST_FIND_PRODUCT_XLS?P_CYL_SYS_ID_DTL=$P_CYL_SYS_ID_DTL&P_CYCRM_SYS_ID=$P_CYCRM_SYS_ID";
	} else if ($STSPRS==="VIEW_DATA"){
		$MenuPrint = "Y";
		$MenuPrintHrefPdf = "";
		$MenuPrintHrefXls = $FORM_NAME."_VIEW_XLS?P_FORM_NAME=$FORM_NAME"
							."&CYL_TYPE_S=$CYL_TYPE_S&CYL_SHADE_CODE_S=$CYL_SHADE_CODE_S"
							."&CYL_SHADE_NAME_S=$CYL_SHADE_NAME_S&CMY_LUSTURE_S=$CMY_LUSTURE_S&DENIER_S=$DENIER_S"
							."&FILAMENT_S=$FILAMENT_S&INTERMINGLING_S=$INTERMINGLING_S&CROSS_SECTION_S=$CROSS_SECTION_S"
							."&HEATSET_S=$HEATSET_S&CUSTOMER_S=$CUSTOMER_S&CYL_LEFT_NO_S=$CYL_LEFT_NO_S&CMY_PRODUCTION_S=$CMY_PRODUCTION_S";
	}
	include ("form_header.php");
	include ("check_login.php");

	if ($USER_NAME === "1949"){
		//echo "STSPRS $STSPRS</br>";
	}

	include ("CST_FIND_PRODUCT_SQL.php");

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
<meta name="viewport" content="width=device-width, initial-scale=1.0">

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
</style>
</head>
<body >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<div class="main" style="overflow-x:auto;" align="center" onclick="closeNav()">
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<!-- DEFINE LAYOUT -->
<?php
	include ("CST_FIND_PRODUCT_CHOICE_VIEW.php");
?>

<!-- DEFINE LAYOUT -->
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">

<input type="hidden" name="STSPRS" id="STSPRS">

<input type="hidden" name="CYL_TYPE_S" id="CYL_TYPE_S">
<input type="hidden" name="CYL_SHADE_CODE_S" id="CYL_SHADE_CODE_S">
<input type="hidden" name="CYL_SHADE_NAME_S" id="CYL_SHADE_NAME_S">

<input type="hidden" name="SHADE_CHOICE" id="SHADE_CHOICE">
<input type="hidden" name="CYL_PRODUCT_QUALITY_S" id="CYL_PRODUCT_QUALITY_S">
<input type="hidden" name="CMY_LUSTURE_S" id="CMY_LUSTURE_S">
<input type="hidden" name="CMY_PRODUCTION_S" id="CMY_PRODUCTION_S">

<input type="hidden" name="DENIER_S" 	id="DENIER_S">
<input type="hidden" name="FILAMENT_S" 	id="FILAMENT_S">
<input type="hidden" name="INTERMINGLING_S" id="INTERMINGLING_S">
<input type="hidden" name="HEATSET_S" id="HEATSET_S">
<input type="hidden" name="CROSS_SECTION_S" id="CROSS_SECTION_S">
<input type="hidden" name="CUSTOMER_S" id="CUSTOMER_S">

<input type="hidden" name="CYL_LEFT_NO_S" id="CYL_LEFT_NO_S">

<input type="hidden" name="Delpack_name_S" id="Delpack_name_S">
<input type="hidden" name="NO_OF_BOBBINS_S" id="NO_OF_BOBBINS_S">
<input type="hidden" name="AX_WT_S" id="AX_WT_S">

<?php include ($FORM_NAME."_JS.php"); ?>

</form>
</div>
</body>
</html>
