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
	$MenuPrint = "Y";
	$MenuPrintHrefPdf = "";
	$MenuPrintHrefXls = "Y";
	$MenuPrint = "Y";

	if ($MenuPrint === "Y"){
	}

	$linkPrint = "https://mgtapps.mutugading.com:4433/webapps/Efilling_File/";
	include ("form_header_print_js.php");
	include ("check_login.php");
	// echo $FORM_NAME."_SQL.php </br>";

	$cur_date = date("Y-m-d");
	//Get Parameter
	$DOCUMENT_TYPE_S = "Null";	if(isset($_POST['DOCUMENT_TYPE_S'])){$DOCUMENT_TYPE_S=$_POST['DOCUMENT_TYPE_S'];} if($DOCUMENT_TYPE_S===""){ $DOCUMENT_TYPE_S="Null"; }
	$DATE_FROM_S = "";	if(isset($_POST['DATE_FROM_S'])){$DATE_FROM_S=$_POST['DATE_FROM_S'];}
	$DATE_TO_S = "";	if(isset($_POST['DATE_TO_S'])){$DATE_TO_S=$_POST['DATE_TO_S'];}
	$FILE_STATUS_S = "";	if(isset($_POST['FILE_STATUS_S'])){$FILE_STATUS_S=$_POST['FILE_STATUS_S'];}
	$USER_DESC_FLTR_S= "";	if(isset($_POST['USER_DESC_FLTR_S'])){$USER_DESC_FLTR_S=$_POST['USER_DESC_FLTR_S'];}

	//echo "DOCUMENT_TYPE_S $DOCUMENT_TYPE_S : DATE_FROM_S $DATE_FROM_S<br>";

	//$sqlGet= "select replace(url_server,'$FORM_NAME','') GET_DATA from mst_menus where menu_id='$P_MENU_ID'";
	$sqlGet= "select 'https://mgtapps.mutugading.com:4433/webapps/'||Replace(FILE_NAME,'\','/') GET_DATA from mst_menus where menu_id='$P_MENU_ID'";
	//echo "sqlGet $sqlGet<br>";
	$urlLinkDtl = getData($conn,$sqlGet);

?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body  onload="Javascript: showData();" >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center" onclick="closeNav()">
	<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>"
				onkeypress="return event.keyCode != 13"
	>
	<?php	include ($FORM_NAME."_PARAM.php"); ?>
		<span class='blink'>
			<font size ='5' color = 'red'><div id="lable"></div></font>
		</span>
		<div id="dataHint"></div>
		<?php
			include ($FORM_NAME."_JS.php");
		?>
		<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">

		<input type="hidden" name="DOCUMENT_TYPE_S" id="DOCUMENT_TYPE_S">
		<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S" value="<?php echo $DATE_FROM_S; ?>">
		<input type="hidden" name="DATE_TO_S" id="DATE_TO_S" value="<?php echo $DATE_TO_S; ?>">
		<input type="hidden" name="FILE_STATUS_S" id="FILE_STATUS_S" value="<?php echo $FILE_STATUS_S; ?>">

		<input type="hidden" name="USER_DESC_FLTR_S" id="USER_DESC_FLTR_S" value="<?php echo $USER_DESC_FLTR_S; ?>">

	</form>
</div>
</body>
</html>
