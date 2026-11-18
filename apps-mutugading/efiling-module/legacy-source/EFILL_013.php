<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}
	$DATE_FROM_S = "";if(isset($_POST['DATE_FROM_S'])) {$DATE_FROM_S=$_POST['DATE_FROM_S'];}
	$DATE_TO_S = "";if(isset($_POST['DATE_TO_S'])) {$DATE_TO_S=$_POST['DATE_TO_S'];}
	if ($DATE_TO_S === ""){
		$curdate = date("Y-m-d");
		//echo "curdate $curdate</br>";
		$DATE_TO_S = $curdate;
	}
	$FILTER_SCLT_S = "";if(isset($_POST['FILTER_SCLT_S'])) {$FILTER_SCLT_S=$_POST['FILTER_SCLT_S'];}

	$FILTER_SCLT_VAL_S = "";if(isset($_POST['FILTER_SCLT_VAL_S'])) {$FILTER_SCLT_VAL_S=$_POST['FILTER_SCLT_VAL_S'];}

	$FILTER_SCLT_DATE_S= "";if(isset($_POST['FILTER_SCLT_DATE_S'])) {$FILTER_SCLT_DATE_S=$_POST['FILTER_SCLT_DATE_S'];}

	$linkFileName= "";if(isset($_POST['linkFileName'])) {$linkFileName=$_POST['linkFileName'];}

	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){
		$P_PAGE_NO= 1;
	}

	$linkFile = "";if(isset($_POST['linkFile'])) {$linkFile=$_POST['linkFile'];}

	$linkFileDtl = "";if(isset($_POST['linkFileDtl'])) {$linkFileDtl=$_POST['linkFileDtl'];}

	$OST_KEY_NO_S = "";if(isset($_POST['OST_KEY_NO_S'])) {$OST_KEY_NO_S=$_POST['OST_KEY_NO_S'];}

	$linkFileTrn = "";if(isset($_POST['linkFileTrn'])) {$linkFileTrn=$_POST['linkFileTrn'];}
	$linkFileTrnName= "";
	if(isset($_POST['linkFileTrnName'])) {
		$linkFileTrnName=$_POST['linkFileTrnName'];
	}
	$linkFilePO = "";
	if(isset($_POST['linkFilePO'])) {
		$linkFilePO=$_POST['linkFilePO'];
	}
	$linkFilePOName= "";
	if(isset($_POST['linkFilePOName'])) {
		$linkFilePOName=$_POST['linkFilePOName'];
	}

	$P_LAST_PAGE_NO = "";
	//Get Parameter

	//$dateH = date("H");
	//$date = date("Y-m-d");
	//$date = str_replace('-', '/', $date);

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";
	//$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";


	include ("form_header.php");
	include ("check_login.php");

	if ($P_MENU_ID===""){
		if(isset($_GET['P_MENU_ID'])) {
				$P_MENU_ID=$_GET['P_MENU_ID'];
		}
	}
	if ($FORM_NAME===""){
		if(isset($_POST['FORM_NAME'])) {
				$FORM_NAME=$_POST['FORM_NAME'];
		}
	}
	include($FORM_NAME.'_SQL.PHP');
	if ($USER_NAME===""){
		if(isset($_POST['USER_NAME'])) {
				$USER_NAME=$_POST['USER_NAME'];
		}
	}
	$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
	$USER_NAME_DTL = getData($conn,$sqlGetData);

	//echo "STSPRS $STSPRS : OST_KEY_NO_S $OST_KEY_NO_S";

?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('NIK').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
<!-- DEFINE LAYOUT -->
<?php
if ($linkFile!==""){
	$DetailAccess = "View Document -> Efilling PO Transaction Listing Detail -> $linkFile";
	pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
	echo "<font color = 'red'  size = '2.5'><b>$linkFileName</b></font>";
	echo "<iframe src='$linkFile' width='100%' style='height:100%'></iframe>";
} else if ($linkFileTrn!=="") {
	//echo "linkFileTrn $linkFileTrn</br>";
	$DetailAccess = "View Document -> Efilling PO Transaction Listing Detail -> $linkFileTrn";
	pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
	echo "<font color = 'red'  size = '2.5'><b>$linkFileTrnName</b></font>";
	echo "<iframe src='$linkFileTrn' width='100%' style='height:100%'></iframe>";
} else if ($linkFilePO!=="") {
	echo "linkFilePO $linkFilePO</br>";
	$DetailAccess = "View Document -> Efilling PO Transaction Listing Detail -> $linkFilePO";
	pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
	echo "<font color = 'red'  size = '2.5'><b>$linkFilePOName</b></font>";
	echo "<iframe src='$linkFilePO' width='100%' style='height:100%'></iframe>";
} else {
?>
	<table style="width:100%">
	   	<tr>
			<td align="center">
	   			<b>Period Start</b>&nbsp<input type="date" id="DATE_FROM" name="DATE_FROM" value="<?php echo $DATE_FROM_S; ?>">
	   			<b>end</b> <input type="date" id="DATE_TO" name="DATE_TO" value="<?php echo $DATE_TO_S; ?>">
				<input type="BUTTON" class="BUTTON btn_process" value="VIEW DATA" onclick="Process('VIEW_DATA')">
	   			<input type="BUTTON" class="BUTTON btn_delete" value="CLEAR" onclick="Process('CLEAR_FILTER')" >
	   		</td>
	   	</tr>
	</table>
	<?php
	if ($OST_KEY_NO_S !== ""){
		include($FORM_NAME.'_VIEW_DTL.PHP');
	} else {
		include($FORM_NAME.'_VIEW_DATA.PHP');
	}
}
?>

<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">
<input type="hidden" name="FORM_NAME" id="FORM_NAME" value="<?php echo $FORM_NAME; ?>">
<input type="hidden" name="USER_NAME" id="USER_NAME">

<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">

<input type="hidden" name="FILTER_SCLT_S" id="FILTER_SCLT_S">
<input type="hidden" name="FILTER_SCLT_VAL_S" id="FILTER_SCLT_VAL_S">
<input type="hidden" name="FILTER_SCLT_DATE_S" id="FILTER_SCLT_DATE_S">

<input type="hidden" name="linkFile" id="linkFile">
<input type="hidden" name="linkFileName" id="linkFileName" value="<?php echo $linkFileName; ?>">

<input type="hidden" name="linkFileTrn" id="linkFileTrn">
<input type="hidden" name="linkFileTrnName" id="linkFileTrnName">

<input type="hidden" name="linkFilePO" id="linkFilePO">
<input type="hidden" name="linkFilePOName" id="linkFilePOName">

<input type="hidden" name="OST_KEY_NO_S" id="OST_KEY_NO_S">


<input type="hidden" name="STSPRS" id="STSPRS">
<script type='text/javascript'>
	function getFilter(){
		if (document.getElementById('FILTER_SCLT')!==null ) {
			FILTER_SCLT = document.getElementById('FILTER_SCLT');
        	FILTER_SCLT_V = FILTER_SCLT.options[FILTER_SCLT.selectedIndex].value;
        	FILTER_SCLT_T = FILTER_SCLT.options[FILTER_SCLT.selectedIndex].text;
			//alert("ck 3");
        	document.getElementById('FILTER_SCLT_S').value = FILTER_SCLT_V;
        	if (document.getElementById('FILTER_SCLT_VAL')!==null ){
				document.getElementById('FILTER_SCLT_VAL_S').value = document.getElementById('FILTER_SCLT_VAL').value;
        	}

        	if (document.getElementById('FILTER_SCLT_DATE')!==null ) {
				document.getElementById('FILTER_SCLT_DATE_S').value = document.getElementById('FILTER_SCLT_DATE').value;
			}
		}
	}

	function Process(prs){
		//alert(prs);
		var vFormName ="<?php Print($FORM_NAME); ?>";
		//alert("cek 1");
		if (document.getElementById('DATE_FROM')!==null){
			document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		}
		if (document.getElementById('DATE_TO')!==null){
			document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		}
		// alert("cek 2");
		if (prs==="CLEAR_FILTER"){
			document.getElementById('DATE_FROM_S').value = "";
			document.getElementById('DATE_TO_S').value = "";
			document.getElementById('linkFile').value = "";
			document.getElementById('linkFileName').value = "";
			document.getElementById('OST_KEY_NO_S').value = "";
		}

		getFilter();
		//OPEN_FILE

		if (prs.substring(0,10)==="OPEN_FILE "){
			document.getElementById('linkFile').value = prs.substring(10);
		}

		if (prs.substring(0,9)==="VIEW_DTL "){
			document.getElementById('OST_KEY_NO_S').value = prs.substring(9);
		}

		if (prs.substring(0,14)==="OPEN_FILE_TRN "){
			document.getElementById('linkFileTrn').value = prs.substring(14);
			document.getElementById('linkFileTrnName').value = "<?php Print($linkFileTrnName); ?>";;
		}

		if (prs.substring(0,13)==="OPEN_FILE_PO "){
			document.getElementById('linkFilePO').value = prs.substring(13);
			document.getElementById('linkFilePOName').value = "<?php Print($linkFilePOName); ?>";;
		}

		document.getElementById('STSPRS').value = prs;
		document.getElementById('USER_NAME').value = "<?php echo $USER_NAME; ?>";
		//alert(document.getElementById('STSPRS').value);
		// alert("cek 2");
		document.getElementById(vFormName).submit();
	}

	var dropdown = document.getElementsByClassName("dropdown-btn");
	var i;

	for (i = 0; i < dropdown.length; i++) {
	  dropdown[i].addEventListener("click", function() {
	  this.classList.toggle("active");
	  var dropdownContent = this.nextElementSibling;
	  if (dropdownContent.style.display === "block") {
	  dropdownContent.style.display = "none";
	  } else {
	  dropdownContent.style.display = "block";
	  }
	  });
	}

	function openNav() {
	  document.getElementById("mySidenav").style.width = "250px";
	}

	function closeNav() {
	  document.getElementById("mySidenav").style.width = "0";
	}

	function GoRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value =document.getElementById('PAGE_NO').value;
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;

		getFilter();

		document.getElementById(vFormName).submit();
	}
	function NextRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		document.getElementById('P_PAGE_NO').value =vPageNo;

		getFilter();

		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		//alert("ck 2");
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value ="1";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;

		getFilter();

		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		getFilter();

		document.getElementById('P_PAGE_NO').value =vLastPageNo;
		document.getElementById(vFormName).submit();
	}

	</script>
</form>
</body>
</html>
