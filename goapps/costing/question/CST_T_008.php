<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");
	include ($FORM_NAME."_SQL.php");

	$byType = "Y";
	$byShade = "";
	$byCust = "";
	$byShadeMulti = "";
	$CYL_IS_PROJECT_S = "";
	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}
	if ($STSPRS === ""){$STSPRS="VIEW_DATA";}
	//echo "STSPRS $STSPRS</br>";
	// $CYL_TYPE_S="";	if(isset($_POST['CYL_TYPE_S'])) {$CYL_TYPE_S=$_POST['CYL_TYPE_S'];}
	// if ($CYL_TYPE_S===""){$CYL_TYPE_S="NULL";}

	$CYL_TYPE_S = !empty($_POST['CYL_TYPE_S']) ? $_POST['CYL_TYPE_S'] : 'NULL';

	$CMY_NAME_S = !empty($_POST['CMY_NAME_S']) ? $_POST['CMY_NAME_S'] : 'NULL';
	// echo "CYL_TYPE_S $CYL_TYPE_S : CMY_NAME_S $CMY_NAME_S<br>";

	$CYL_SHADE_CODE_S="";if(isset($_POST['CYL_SHADE_CODE_S'])) {$CYL_SHADE_CODE_S=$_POST['CYL_SHADE_CODE_S'];}
	if ($CYL_SHADE_CODE_S===""){$CYL_SHADE_CODE_S="NULL";}

	$CYL_SHADE_NAME_S="";if(isset($_POST['CYL_SHADE_NAME_S'])) {$CYL_SHADE_NAME_S=$_POST['CYL_SHADE_NAME_S'];}
	if ($CYL_SHADE_NAME_S===""){$CYL_SHADE_NAME_S="NULL";}
	//echo "CYL_SHADE_NAME_S $CYL_SHADE_NAME_S</br>";

	$CYL_PRODUCT_QUALITY_S="";if(isset($_POST['CYL_PRODUCT_QUALITY_S'])) {$CYL_PRODUCT_QUALITY_S=$_POST['CYL_PRODUCT_QUALITY_S'];}
	if ($CYL_PRODUCT_QUALITY_S===""){$CYL_PRODUCT_QUALITY_S="NULL";}

	$CMY_LUSTURE_S="";if(isset($_POST['CMY_LUSTURE_S'])){$CMY_LUSTURE_S=$_POST['CMY_LUSTURE_S'];}
	if ($CMY_LUSTURE_S===""){$CMY_LUSTURE_S="NULL";}

	$CYL_LEFT_NO_S="";if(isset($_POST['CYL_LEFT_NO_S'])){$CYL_LEFT_NO_S=$_POST['CYL_LEFT_NO_S'];}

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

	$CMY_PRODUCTION_S="";if(isset($_POST['CMY_PRODUCTION_S'])) {$CMY_PRODUCTION_S=$_POST['CMY_PRODUCTION_S'];}
	if ($CMY_PRODUCTION_S===""){$CMY_PRODUCTION_S="NULL";}
	//echo "CMY_PRODUCTION_S $CMY_PRODUCTION_S<br>";
	$SHADE_CHOICE="";if(isset($_POST['SHADE_CHOICE'])) {$SHADE_CHOICE=$_POST['SHADE_CHOICE'];}

	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){$P_PAGE_NO= 1;}
	$P_LAST_PAGE_NO = "";

	$P_CYL_SYS_ID_DTL	= "";if (substr($STSPRS,0,7) === "DETAIL " ){$P_CYL_SYS_ID_DTL	= substr($STSPRS,7);}
	$P_CYCRM_SYS_ID = "20211206";
	$P_CYC_PRS_TYPE = "20210800119";


	//Get Parameter
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	if ($P_CYL_SYS_ID_DTL !== ""){
		$MenuPrint = "Y";

		$MenuPrintHrefPdf = "CST_FIND_PRODUCT_PDF?P_CYL_SYS_ID_DTL=$P_CYL_SYS_ID_DTL&P_CYCRM_SYS_ID=$P_CYCRM_SYS_ID";
		$MenuPrintHrefXls = "CST_FIND_PRODUCT_XLS?P_CYL_SYS_ID_DTL=$P_CYL_SYS_ID_DTL&P_CYCRM_SYS_ID=$P_CYCRM_SYS_ID";

	}else if ($STSPRS==="VIEW_DATA"){
		$MenuPrint = "Y";
		$MenuPrintHrefPdf = "";
		$MenuPrintPrdDtl="";
		$FormXls = $FORM_NAME;
		if ($CYL_TYPE_S==="SUPERBA"){
			$FormXls = "CST_T_006";
			$MenuPrintPrdDtl = "CST_T_008_PROD_DTL_XLS?P_FORM_NAME=$FORM_NAME&CYL_TYPE_S=$CYL_TYPE_S";
		}else {
			if($CYL_TYPE_S!=="NULL"){
				$MenuPrintPrdDtl = $FormXls."_PROD_DTL_XLS?P_FORM_NAME=$FORM_NAME&CYL_TYPE_S=$CYL_TYPE_S";
			}
		}
		$MenuPrintHrefXls = $FormXls."_VIEW_XLS?P_FORM_NAME=$FORM_NAME"
							."&CYL_TYPE_S=$CYL_TYPE_S&CMY_NAME_S=$CMY_NAME_S&CYL_SHADE_CODE_S=$CYL_SHADE_CODE_S"
							."&CYL_SHADE_NAME_S=$CYL_SHADE_NAME_S&CMY_LUSTURE_S=$CMY_LUSTURE_S&DENIER_S=$DENIER_S"
							."&FILAMENT_S=$FILAMENT_S&INTERMINGLING_S=$INTERMINGLING_S&CROSS_SECTION_S=$CROSS_SECTION_S"
							."&HEATSET_S=$HEATSET_S&CUSTOMER_S=$CUSTOMER_S&CYL_PRODUCT_QUALITY_S=$CYL_PRODUCT_QUALITY_S"
							."&CYL_LEFT_NO_S=$CYL_LEFT_NO_S&CMY_PRODUCTION_S=$CMY_PRODUCTION_S";
	}

	$SqlView=fGetSqlDt();

	$SqlViewWhere = "";
	if ( $CYL_TYPE_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
	}
	if ( $CMY_NAME_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and upper(CMY_NAME) like '%'||upper('$CMY_NAME_S')||'%' ";
	}
	if ( $CYL_SHADE_CODE_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
	}
	if ( $CYL_SHADE_NAME_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
	}

	if ( $CMY_LUSTURE_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CMY_LUSTURE = '$CMY_LUSTURE_S' ";
	}

	if ( $CMY_PRODUCTION_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CMY_PRODUCTION = '$CMY_PRODUCTION_S' ";
	}

	if ( $DENIER_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYCC_TOP_13_DATA_VALUE = '$DENIER_S' ";
	}

	if ( $FILAMENT_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' ";
	}

	if ( $INTERMINGLING_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' ";
	}

	if ( $CROSS_SECTION_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' ";
	}

	if ( $HEATSET_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' ";
	}
	if ( $CUSTOMER_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
					where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
					and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
	}
	if ( $CYL_PRODUCT_QUALITY_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_PRODUCT_QUALITY = '$CYL_PRODUCT_QUALITY_S' ";
	}
	if ( $CYL_LEFT_NO_S !=="NULL"){
		if ( $CYL_LEFT_NO_S !==""){
			$SqlViewWhere = "$SqlViewWhere and CYL_LEFT_NO = '$CYL_LEFT_NO_S' ";
		}
	}

	$sqlTotDt = "select count(-1) GET_DATA from ($SqlView $SqlViewWhere) d";	//include ("form_header.php");
	// echo "sql Tot Data <br>$sqlTotDt<br>";
	$TOT_DT = getData($conn,$sqlTotDt);
	include ("form_header_all_product.php");
	include ("check_login.php");

	include ("CST_FIND_PRODUCT_SQL.php");

	$styleSlct = "style='color:blue'";
	//if ($USER_NAME==="1949"){echo "$TOT_DT $SqlView</br>";}
	// if ($USER_NAME==="1949"){echo "CYL_TYPE_S $CYL_TYPE_S : MenuPrintPrdDtl $MenuPrintPrdDtl </br>";}
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
<input type="hidden" name="CYL_PRODUCT_QUALITY_S" id="CYL_PRODUCT_QUALITY_S">
<input type="hidden" name="SHADE_CHOICE" id="SHADE_CHOICE">
<input type="hidden" name="CMY_LUSTURE_S" id="CMY_LUSTURE_S">
<input type="hidden" name="DENIER_S" 	id="DENIER_S">
<input type="hidden" name="FILAMENT_S" 	id="FILAMENT_S">
<input type="hidden" name="INTERMINGLING_S" id="INTERMINGLING_S">
<input type="hidden" name="HEATSET_S" id="HEATSET_S">
<input type="hidden" name="CROSS_SECTION_S" id="CROSS_SECTION_S">
<input type="hidden" name="CUSTOMER_S" id="CUSTOMER_S">
<input type="hidden" name="CMY_PRODUCTION_S" id="CMY_PRODUCTION_S">

<input type="hidden" name="CMY_NAME_S" id="CMY_NAME_S">

<input type="hidden" name="CYL_LEFT_NO_S" id="CYL_LEFT_NO_S">

<input type="hidden" name="Delpack_name_S" id="Delpack_name_S">
<input type="hidden" name="NO_OF_BOBBINS_S" id="NO_OF_BOBBINS_S">
<input type="hidden" name="AX_WT_S" id="AX_WT_S">

<?php include ($FORM_NAME."_JS.php"); ?>

</form>
</div>
</body>
</html>
