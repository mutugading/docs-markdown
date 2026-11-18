<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");
	$currDate=date('Y-m-d');
	$STSPRS = "";	if(isset($_POST['STSPRS'])){$STSPRS=$_POST['STSPRS'];}

	$P_PAGE_NO = ""; if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){$P_PAGE_NO= 1;}

	//echo "P_PAGE_NO $P_PAGE_NO";
	$P_REPORT_ID_S= "";if(isset($_POST['P_REPORT_ID_S'])) {$P_REPORT_ID_S=$_POST['P_REPORT_ID_S'];}
	if ($P_REPORT_ID_S=== ""){$P_REPORT_ID_S = "202102012";}

	$P_DATE_FROM_S= "";if(isset($_POST['P_DATE_FROM_S'])){$P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];}
	if ($P_DATE_FROM_S=== ""){$P_DATE_FROM_S=$currDate;}

	$P_DATE_TO_S= "";if(isset($_POST['P_DATE_TO_S'])) {$P_DATE_TO_S=$_POST['P_DATE_TO_S'];}

	//echo "P_DATE_TO_S $P_DATE_TO_S";

	if ($P_DATE_TO_S=== ""){$P_DATE_TO_S=$currDate;}

	$SLCT_CC_003_R002_S = "";if(isset($_POST['SLCT_CC_003_R002_S'])) {$SLCT_CC_003_R002_S=$_POST['SLCT_CC_003_R002_S'];}

	$SLCT_CC_003_R002_I_S= ""; if(isset($_POST['SLCT_CC_003_R002_I_S'])) {$SLCT_CC_003_R002_I_S=$_POST['SLCT_CC_003_R002_I_S'];}

	$SLCT_CC_003_R003_S="";if(isset($_POST['SLCT_CC_003_R003_S'])){$SLCT_CC_003_R003_S=$_POST['SLCT_CC_003_R003_S'];}
	//echo "SLCT_CC_003_R003_S -> $SLCT_CC_003_R003_S";
	$SLCT_CC_003_R003_I_S= "";
	if(isset($_POST['SLCT_CC_003_R003_I_S'])) {
			$SLCT_CC_003_R003_I_S=$_POST['SLCT_CC_003_R003_I_S'];
	}


	$P_LAST_PAGE_NO = "1";


	$P_REPORT_NAME_S = getData($conn,"select REPORT_NAME GET_DATA from mst_reports where REPORT_ID = '$P_REPORT_ID_S'");

	//echo "P_REPORT_ID_S $P_REPORT_ID_S : P_REPORT_NAME_S $P_REPORT_NAME_S";

	$dateH = date("H");
	$date = date("Y-m-d");
	$date = str_replace('-', '/', $date);

	//echo $date;

	$timeLimit = getSqlParams($conn,"ECD_TIME_LIMIT_COUPON");
	//$timeLimit = (int)$timeLimit;
	//echo "$date $dateH $timeLimit";
	if ((int)$dateH <= (int) $timeLimit) {
		//$date = strtotime("-1 day", $date);
		$date = date('m-d-Y',strtotime($date . "-1 days"));
	} else {
		$date = strtotime($date);
		$date = date('Y-m-d', $date);
	}

	//echo "STSPRS -> $STSPRS : P_REPORT_ID_S $P_REPORT_ID_S</br>";

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "Y";
	$MenuPrintHrefPdf = "";
	$MenuPrintHrefXls = "";
	if ($P_REPORT_ID_S === "202102012"){
		$MenuPrintHrefPdf = $FORM_NAME."_R01_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
		$MenuPrintHrefXls = $FORM_NAME."_R01_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
	}else if ($P_REPORT_ID_S === "202102013"){
		$MenuPrintHrefPdf = $FORM_NAME."_R02_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R002_S=$SLCT_CC_003_R002_S&SLCT_CC_003_R002_I_S=$SLCT_CC_003_R002_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R02_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R002_S=$SLCT_CC_003_R002_S&SLCT_CC_003_R002_I_S=$SLCT_CC_003_R002_I_S";
	}else if ($P_REPORT_ID_S === "202103014"){
		$MenuPrintHrefPdf = $FORM_NAME."_R03_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R03_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}else if ($P_REPORT_ID_S === "202104015"){
		$MenuPrintHrefPdf = $FORM_NAME."_R04_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R04_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}else if ($P_REPORT_ID_S === "202104016"){
		$MenuPrintHrefPdf = $FORM_NAME."_R05_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R05_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}else if ($P_REPORT_ID_S === "202104017"){
		$MenuPrintHrefPdf = $FORM_NAME."_R06_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R06_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}else if ($P_REPORT_ID_S === "202105018"){
		$MenuPrintHrefPdf = $FORM_NAME."_R07_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R07_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}else if ($P_REPORT_ID_S === "202108019"){
		$MenuPrintHrefPdf = $FORM_NAME."_R08_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R08_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	} else if ($P_REPORT_ID_S === "202204021") {
		$MenuPrintHrefPdf = $FORM_NAME."_R09_PDF?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
		$MenuPrintHrefXls = $FORM_NAME."_R09_XLS?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S".
							"&SLCT_CC_003_R003_S=$SLCT_CC_003_R003_S&SLCT_CC_003_R003_I_S=$SLCT_CC_003_R003_I_S";
	}
	include ("form_header.php");
	include ("check_login.php");

	//echo "STSPRS $STSPRS";
	$descCouponTambahan = getData($conn,"select replace(upper(MPD_REMARKS),'NILAI RATE','') GET_DATA
										 from MST_PARAM_DATA where mpd_sys_id = '20210800118'");
  $font = "12";

?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body>
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:15px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;" onclick="closeNav()" align="center">
	<table style="width:100%"  align="center">
		<tr>
			<td align="center">
				REPORT NAME
<?php
	$sqlList = "select MPSM_SCH, MPSM_DESCR from MGTDAT.MGT_PAYROLL_SCH_MASTER
				where mpsm_start is not null
				order by MPSM_SCH";
	//echo $sqlList;
?>
			<select name="P_REPORT_ID" id="P_REPORT_ID" onchange="Process('CHANGE_REPORT')" >
<?php
    $sqlList = "select to_char(REPORT_ID) REPORT_ID, DESCRIPTION, REPORT_NAME from mst_reports where REPORT_GROUP_ID = '202102005'  order by REPORT_ID ";
    $rsDataList = oci_parse($conn,$sqlList);
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
				<option value="<?php echo $rowDataList['REPORT_ID']; ?>"
						<?php if ($P_REPORT_ID_S==$rowDataList['REPORT_ID']){echo "selected"; }?> >
						<?php echo $rowDataList['REPORT_ID']."-".$rowDataList['DESCRIPTION']; ?>
				</option>
<?php
	}
?>
			</select>
			</td>
		</tr>
	</table>
<?php
//if ($USER_NAME==="1949") {echo "$P_REPORT_NAME_S </br>";}
include($P_REPORT_NAME_S);
?>
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO">

<input type="hidden" name="P_REPORT_ID_S" id="P_REPORT_ID_S">
<input type="hidden" name="P_REPORT_NAME_S" id="P_REPORT_NAME_S">

<input type="hidden" name="P_DATE_FROM_S" id="P_DATE_FROM_S">
<input type="hidden" name="P_DATE_TO_S" id="P_DATE_TO_S">


<input type="hidden" id="SLCT_CC_003_R002_S" name="SLCT_CC_003_R002_S" >
<input type="hidden" id="SLCT_CC_003_R002_I_S" name="SLCT_CC_003_R002_I_S" >

<input type="hidden" id="SLCT_CC_003_R003_S" name="SLCT_CC_003_R003_S" >
<input type="hidden" id="SLCT_CC_003_R003_I_S" name="SLCT_CC_003_R003_I_S" >



<script type='text/javascript'>
	function getParamDate(){
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var pRepId = document.getElementById('P_REPORT_ID');
		var pRepId_t = pRepId.options[pRepId.selectedIndex].text;
		var pRepId_v = pRepId.options[pRepId.selectedIndex].value;

		// if (document.getElementById('P_DATE_FROM') !=null) {
		// 	document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		// }
		// if (document.getElementById('P_DATE_TO') !=null) {
		// 	document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		// }
		getParamDate();

		document.getElementById('SLCT_CC_003_R002_S').value = "<?php Print($SLCT_CC_003_R002_S); ?>";
		document.getElementById('SLCT_CC_003_R002_I_S').value = "<?php Print($SLCT_CC_003_R002_I_S); ?>";
		document.getElementById('SLCT_CC_003_R003_S').value = "<?php Print($SLCT_CC_003_R003_S); ?>";
		document.getElementById('SLCT_CC_003_R003_I_S').value = "<?php Print($SLCT_CC_003_R003_I_S); ?>";

		document.getElementById('P_REPORT_ID_S').value = pRepId_v;
		document.getElementById('P_PAGE_NO').value ="1";
		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		var pRepId = document.getElementById('P_REPORT_ID');
		var pRepId_t = pRepId.options[pRepId.selectedIndex].text;
		var pRepId_v = pRepId.options[pRepId.selectedIndex].value;

		// if (document.getElementById('P_DATE_FROM') !=null) {
		// 	document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		// }
		// if (document.getElementById('P_DATE_TO') !=null) {
		// 	document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		// }
		getParamDate();
		document.getElementById('SLCT_CC_003_R002_S').value = "<?php Print($SLCT_CC_003_R002_S); ?>";
		document.getElementById('SLCT_CC_003_R002_I_S').value = "<?php Print($SLCT_CC_003_R002_I_S); ?>";

		document.getElementById('SLCT_CC_003_R003_S').value = "<?php Print($SLCT_CC_003_R003_S); ?>";
		document.getElementById('SLCT_CC_003_R003_I_S').value = "<?php Print($SLCT_CC_003_R003_I_S); ?>";

		document.getElementById('P_REPORT_ID_S').value = pRepId_v;
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}

	function NextRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		var pRepId = document.getElementById('P_REPORT_ID');
		var pRepId_t = pRepId.options[pRepId.selectedIndex].text;
		var pRepId_v = pRepId.options[pRepId.selectedIndex].value;
		// if (document.getElementById('P_DATE_FROM') !=null) {
		// 	document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		// }
		// if (document.getElementById('P_DATE_TO') !=null) {
		// 	document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		// }
		getParamDate();
		document.getElementById('SLCT_CC_003_R002_S').value = "<?php Print($SLCT_CC_003_R002_S); ?>";
		document.getElementById('SLCT_CC_003_R002_I_S').value = "<?php Print($SLCT_CC_003_R002_I_S); ?>";

		document.getElementById('SLCT_CC_003_R003_S').value = "<?php Print($SLCT_CC_003_R003_S); ?>";
		document.getElementById('SLCT_CC_003_R003_I_S').value = "<?php Print($SLCT_CC_003_R003_I_S); ?>";

		document.getElementById('P_REPORT_ID_S').value = pRepId_v;
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		var pRepId = document.getElementById('P_REPORT_ID');
		var pRepId_t = pRepId.options[pRepId.selectedIndex].text;
		var pRepId_v = pRepId.options[pRepId.selectedIndex].value;
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
		document.getElementById('SLCT_CC_003_R002_S').value = "<?php Print($SLCT_CC_003_R002_S); ?>";
		document.getElementById('SLCT_CC_003_R002_I_S').value = "<?php Print($SLCT_CC_003_R002_I_S); ?>";

		document.getElementById('SLCT_CC_003_R003_S').value = "<?php Print($SLCT_CC_003_R003_S); ?>";
		document.getElementById('SLCT_CC_003_R003_I_S').value = "<?php Print($SLCT_CC_003_R003_I_S); ?>";

		document.getElementById('P_REPORT_ID_S').value = pRepId_v;
		document.getElementById('P_PAGE_NO').value =vLastPageNo;
		document.getElementById(vFormName).submit();
	}

	function Process(Prs){
		//alert(Prs);
		var prs0 = Prs;
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vErrMsg = ""; var vMsgConfirm = "";
		//alert(document.getElementById('P_REPORT_ID'));
		//alert("1");
		var pRepId = document.getElementById('P_REPORT_ID');
		var pRepId_t = pRepId.options[pRepId.selectedIndex].text;
		var pRepId_v = pRepId.options[pRepId.selectedIndex].value;
		document.getElementById('P_REPORT_ID_S').value = pRepId_v;
		//alert(pRepId_v);
		if (Prs==='CHANGE_REPORT'){
			document.getElementById('P_PAGE_NO').value = "1";
		} else {
			document.getElementById('P_PAGE_NO').value = "<?php Print($P_PAGE_NO); ?>";
		}

		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		//if (pRepId_v==="202102012"){//report consumption
			if (document.getElementById('P_DATE_TO') !=null) {
				document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
			}
		//}

		//alert("pRepId_v " + pRepId_v);
		var pSlct_v = "NULL";
		if (pRepId_v==="202102013"){//report consumption
			if (document.getElementById('SLCT_CC_003_R002') !=null) {
				var pSlct = document.getElementById('SLCT_CC_003_R002');
				var pSlct_t = pSlct.options[pSlct.selectedIndex].text;
				pSlct_v = pSlct.options[pSlct.selectedIndex].value;
				//alert(pSlct_v);
				document.getElementById('SLCT_CC_003_R002_S').value = pSlct_v;
				if (document.getElementById('SLCT_CC_003_R002_I') !=null) {
					document.getElementById('SLCT_CC_003_R002_I_S').value = document.getElementById('SLCT_CC_003_R002_I').value;
				}

			}
		}

		if (pRepId_v==="202103014"){//report consumption
			if (document.getElementById('SLCT_CC_003_R003') !=null) {
				var pSlct = document.getElementById('SLCT_CC_003_R003');
				var pSlct_t = pSlct.options[pSlct.selectedIndex].text;
				pSlct_v = pSlct.options[pSlct.selectedIndex].value;
				//alert("Pilihan select " + pSlct_v);
				document.getElementById('SLCT_CC_003_R003_S').value = pSlct_v;
				if (document.getElementById('SLCT_CC_003_R003_I') !=null) {
					document.getElementById('SLCT_CC_003_R003_I_S').value = document.getElementById('SLCT_CC_003_R003_I').value;
				}
			}
		}
		//alert("test 2");
		document.getElementById('STSPRS').value = Prs;


		//alert("test 3");
		document.getElementById(vFormName).submit();
	}

	function updScanErr(Prs) {
		//UPD DT
		//alert('cek');
		var vNoData = Prs.substring(7);
		//alert("cek prs "+vNoData);
		var ECES_SYS_ID = document.getElementsByName('ECES_SYS_ID[]');
		var ECES_REMARK_HRM = document.getElementsByName('ECES_REMARK_HRM[]');
		var vECES_SYS_ID = ECES_SYS_ID[vNoData].value;
		var vECES_REMARK_HRM = ECES_REMARK_HRM[vNoData].value;
		//alert ("ECES_REMARK_HRM " + vECES_REMARK_HRM + "vECES_SYS_ID " + vECES_SYS_ID);
		document.getElementById('STSPRS').value = "UPD HRM SCAN ERR";
		document.getElementById('P_REPORT_ID_S').value = "202204021";
		document.getElementById('P_REPORT_NAME_S').value = "CC_003_R09";

		document.getElementById('ECES_SYS_ID_S').value = vECES_SYS_ID;
		document.getElementById('ECES_REMARK_HRM_S').value = vECES_REMARK_HRM;

		getParamDate();

		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById(vFormName).submit();
		//alert("cek prs "+vECES_SYS_ID);
		//alert("test Scan ERror " + Prs + "rec no "+vNoData);
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
	</script>
</form>
</body>
</html>
