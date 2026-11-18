<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//$sqlGetData = "select to_char(sysdate-1,'YYYY-MM-DD') GET_DATA from dual";
	$sqlGetData = "select mgtapps.pkgCanteenCoupon.fGetMaxDate('1') GET_DATA from dual";
	$currDate = getData($conn,$sqlGetData);

	//$currDate=date('Y-m-d');
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}
	//echo "STSPRS $STSPRS";

	$P_PAGE_NO = "";
	if(isset($_POST['P_PAGE_NO'])) {
		$P_PAGE_NO=$_POST['P_PAGE_NO'];
	}
	if ($P_PAGE_NO===""){
		$P_PAGE_NO= 1;
	}

	//echo "P_PAGE_NO $P_PAGE_NO";

	$P_DATE_FROM_S= "";
	if(isset($_POST['P_DATE_FROM_S'])) {
		$P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];
	}

	if ($P_DATE_FROM_S=== ""){
		$P_DATE_FROM_S=$currDate;
	}

	$P_DATE_TO_S= "";
	if(isset($_POST['P_DATE_TO_S'])) {
		$P_DATE_TO_S=$_POST['P_DATE_TO_S'];
	}

	if ($P_DATE_TO_S=== ""){
		$P_DATE_TO_S=$currDate;
	}
	$sqlGetData 	= 	"select
								case when trunc(sysdate) = trunc(to_date('$P_DATE_FROM_S','YYYY-MM-DD')) then
            						to_char(trunc(sysdate-1),'YYYY-MM-DD')
       							else '$P_DATE_FROM_S'
       							end GET_DATA
						from dual";
	//echo "<br> $sqlGetData";
	$P_DATE_FROM_S 	= getData($conn,$sqlGetData);

	$sqlGetData 	= 	"select
								case when trunc(sysdate) = trunc(to_date('$P_DATE_TO_S','YYYY-MM-DD')) then
            						to_char(trunc(sysdate-1),'YYYY-MM-DD')
       							else '$P_DATE_TO_S'
       							end GET_DATA
						from dual";
	//echo "<br> $sqlGetData";
	$P_DATE_TO_S 	= getData($conn,$sqlGetData);
	//echo "<br> $P_DATE_FROM_S $P_DATE_TO_S";

	$ECDS_SYS_ID_S= "";
	if(isset($_POST['ECDS_SYS_ID_S'])) {
		$ECDS_SYS_ID_S=$_POST['ECDS_SYS_ID_S'];
	}
	$ECDS_DATE_S = "";
	if(isset($_POST['ECDS_DATE_S'])) {
		$ECDS_DATE_S=$_POST['ECDS_DATE_S'];
	}//echo $ECDS_DATE_S;
	$ECDS_TOT_SHIFT_1_S = "";
	if(isset($_POST['ECDS_TOT_SHIFT_1_S'])) {
		$ECDS_TOT_SHIFT_1_S=$_POST['ECDS_TOT_SHIFT_1_S'];
	}
	$ECDS_TOT_SHIFT_GS_S = "";
	if(isset($_POST['ECDS_TOT_SHIFT_GS_S'])) {
		$ECDS_TOT_SHIFT_GS_S=$_POST['ECDS_TOT_SHIFT_GS_S'];
	}
	$ECDS_TOT_SHIFT_2_S = "";
	if(isset($_POST['ECDS_TOT_SHIFT_2_S'])) {
		$ECDS_TOT_SHIFT_2_S=$_POST['ECDS_TOT_SHIFT_2_S'];
	}
	$ECDS_TOT_SHIFT_3_S = "";
	if(isset($_POST['ECDS_TOT_SHIFT_3_S'])) {
		$ECDS_TOT_SHIFT_3_S=$_POST['ECDS_TOT_SHIFT_3_S'];
	}
	$ECDS_TOT_OTHERS_S = "";
	if(isset($_POST['ECDS_TOT_OTHERS_S'])) {
		$ECDS_TOT_OTHERS_S=$_POST['ECDS_TOT_OTHERS_S'];
	}
	$ECDS_APPROVE_KANTIN_S = "";
	if(isset($_POST['ECDS_APPROVE_KANTIN_S'])) {
		$ECDS_APPROVE_KANTIN_S=$_POST['ECDS_APPROVE_KANTIN_S'];
	}
	$ECDS_APPROVE_KANTIN_DATE_S = "";
	if(isset($_POST['ECDS_APPROVE_KANTIN_DATE_S'])) {
		$ECDS_APPROVE_KANTIN_DATE_S=$_POST['ECDS_APPROVE_KANTIN_DATE_S'];
	}
	$ECDS_KANTIN_REMARK_S="";
	if(isset($_POST['ECDS_KANTIN_REMARK_S'])) {
		$ECDS_KANTIN_REMARK_S=$_POST['ECDS_KANTIN_REMARK_S'];
	}
	$ECDS_HRM_REMARK_S="";
	if(isset($_POST['ECDS_HRM_REMARK_S'])) {
		$ECDS_HRM_REMARK_S=$_POST['ECDS_HRM_REMARK_S'];
	}

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "N";
	// if ($P_REPORT_ID_S === "202102012"){
	// 	$MenuPrintHrefPdf = $FORM_NAME."_R01_PDF.php?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
	// 	$MenuPrintHrefXls = $FORM_NAME."_R01_XLS.php?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
	// }else if ($P_REPORT_ID_S === "202102013"){
	// 	$MenuPrintHrefPdf = $FORM_NAME."_R02_PDF.php?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
	// 	$MenuPrintHrefXls = $FORM_NAME."_R02_XLS.php?P_DATE_FROM=$P_DATE_FROM_S&P_DATE_TO=$P_DATE_TO_S&P_FORM_NAME=$FORM_NAME&P_REPORT_ID=$P_REPORT_ID_S";
	// }
	include ("form_header.php");
	include ("check_login.php");

	$Refresh = "";
	if ($STSPRS ==="INS_KANTIN"){
		$sqlInsrt = "insert into MGTAPPS.EMP_COUPON_DATA_SUM( "
					."ECDS_SYS_ID, ECDS_CREATED_BY, ECDS_CREATED_TIMESTAMP, ECDS_DATE " //1
					.", ECDS_TOT_SHIFT_1, ECDS_TOT_SHIFT_GS, ECDS_TOT_SHIFT_2, ECDS_TOT_SHIFT_3 " //2
					.", ECDS_TOT_OTHERS, ECDS_APPROVE_KANTIN, ECDS_APPROVE_KANTIN_DATE "//3
					.",	ECDS_KANTIN_REMARK "//4
					.")values ( "
					."'123','$USER_NAME',sysdate,to_date('$ECDS_DATE_S','DD/MM/YYYY') "//1
					.", $ECDS_TOT_SHIFT_1_S, $ECDS_TOT_SHIFT_GS_S, $ECDS_TOT_SHIFT_2_S, $ECDS_TOT_SHIFT_3_S " //2
					.", $ECDS_TOT_OTHERS_S, '$USER_NAME', SYSDATE "//3
					.",	'$ECDS_KANTIN_REMARK_S' "//4
					.")";

			//echo $sqlInsrt;die();

			//echo $sqlInsrt;
		  	$rsInst = oci_parse($conn,$sqlInsrt);
		  	oci_execute($rsInst, OCI_DEFAULT);
		  	$errIns = "";
			if (!$rsInst) {
			    //echo $sqlInsrt;
			    $e = oci_error($rsInst);  // For oci_execute errors pass the statement handle
			    $errIns = htmlentities($e['message']);
			    //print htmlentities($e['message']);
			    //print "\n<pre>\n";
			    //print htmlentities($e['sqltext']);
			    //printf("\n%".($e['offset']+1)."s", "^");
			    //print  "\n</pre>\n";
			    //echo $sqlInsrt;
			}
			if ($errIns!==""){
				die();
			}
		  	oci_commit($conn);
		  	$Refresh= "Y";
	}else if ($STSPRS ==="UPD_KANTIN"){
			$sqlUpd =
					"update MGTAPPS.EMP_COUPON_DATA_SUM
						set ECDS_HRM_REMARK = '$ECDS_HRM_REMARK_S'
							, ECDS_APPROVE_HRM_DATE = sysdate
							, ECDS_APPROVE_HRM = '$USER_NAME'
					where ECDS_SYS_ID = '$ECDS_SYS_ID_S'
					";

			//echo $sqlUpd;//die();

			//echo $sqlInsrt;
		  	$rsUpd = oci_parse($conn,$sqlUpd);
		  	oci_execute($rsUpd, OCI_DEFAULT);
		  	$errUpd = "";
			if (!$rsUpd) {
			    //echo $sqlInsrt;
			    $e = oci_error($rsUpd);  // For oci_execute errors pass the statement handle
			    $errUpd = htmlentities($e['message']);
			    //print htmlentities($e['message']);
			    //print "\n<pre>\n";
			    //print htmlentities($e['sqltext']);
			    //printf("\n%".($e['offset']+1)."s", "^");
			    //print  "\n</pre>\n";
			    //echo $sqlInsrt;
			}
			if ($errUpd!==""){
				die();
			}
		  	oci_commit($conn);
		  	$Refresh= "Y";
	}

	if ($Refresh=== "Y"){
	    echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
        echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
        echo "<input type='hidden' id='STSPRS' name='STSPRS' value=''>";
        echo "<input type='hidden' id='P_PAGE_NO' name='P_PAGE_NO' value=$P_PAGE_NO>";
        echo "<input type='hidden' id='P_DATE_FROM_S' name='P_DATE_FROM_S' value=$P_DATE_FROM_S>";
        echo "<input type='hidden' id='P_DATE_TO_S' name='P_DATE_TO_S' value=$P_DATE_TO_S>";
        echo "</form>";
        echo "<script type='text/javascript'>";
        echo "document.getElementById('$FORM_NAME').submit();";
        echo "</script>";
	}

	$P_LAST_PAGE_NO = "1";
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

	//echo "STSPRS $STSPRS";
	$sqlGetData = "select pkgCanteenCoupon.fIsAdminMgt('$USER_NAME') GET_DATA from dual";
	$isAdminMgt = getData($conn,$sqlGetData);
	$sqlGetData = "select pkgCanteenCoupon.fIsAdminKantin('$USER_NAME') GET_DATA from dual";
	$isAdminKantin = getData($conn,$sqlGetData);
	//echo "isAdminMgt $isAdminMgt isAdminKantin $isAdminKantin";
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

	$P_DATE_TO_S = $currDate;
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
	<table style="width:100%"  align="center">
		<tr>
			<td align="center">
			Date From <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')" >
			To <input type="date" name="P_DATE_TO" id="P_DATE_TO" value="<?php echo $P_DATE_TO_S; ?>" onchange="Process('OK')" >
			</td>
		</tr>
	</table>
<?php
	include("CC_003_R01_SQL.PHP");

	$sqlView = 	"select * from (select 	rownum RECNO,to_char(ECD_DATE,'DD/MM/YYYY') ECD_DATE, TOT_S1, TOT_SGS, TOT_S2, TOT_S3, TOT_SUD
										,nvl(TOT_S1,0)+nvl(TOT_SGS,0)+nvl(TOT_S2,0)+nvl(TOT_S3,0)+nvl(TOT_SUD,0) TOTAL
								from ($sqlData ORDER BY ECD_DATE) t ) t ";

	$sqlTotRows = "select count(-1) TOT_DATA from ( $sqlView ) ";
	$totRows = getTotData($conn,$sqlTotRows);
	//echo "tot rows ".$totRows;
	$P_ROWS_DISP = 10;
    $P_LAST_PAGE_NO = ($totRows / $P_ROWS_DISP);
    //echo "P_LAST_PAGE_NO $P_LAST_PAGE_NO";
    if (strpos($P_LAST_PAGE_NO,'.') > 0 ){
    	$P_LAST_PAGE_NO = substr($P_LAST_PAGE_NO,0,strpos($P_LAST_PAGE_NO,'.'));
    	$P_LAST_PAGE_NO = $P_LAST_PAGE_NO + 1;
    }
    if ($P_LAST_PAGE_NO==0){
    	$P_LAST_PAGE_NO=1;
    }
  	if ($P_PAGE_NO=="1"){
	    $P_ROW_START  = 1;
	    $P_ROW_END    = $P_ROWS_DISP;
	} else {
	    $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
	    $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
	}
?>
	<table style="width:100%" s>
		<tr>
			<td style="width:100%" align="left">
				<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
				<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
				<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 50px;"  value="<?php echo $P_PAGE_NO;?>"  onchange="Process('NO_PAGE')" >
				: <?php echo $P_LAST_PAGE_NO; ?>
				<input type="button" class="BUTTON btn_nextrow" id="NEXTREC" value=">" onclick="NextRecord()" >
				<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
			</td>
		</tr>
	</table>
	<table style="width:100%"  align="center" border="1">
		<tr>
			<td align="center" rowspan="2" style="width:5%">
				No
			</td>
			<td align="center" rowspan="2" style="width:7%">
				Tanggal
			</td>
			<td align="center" rowspan="2" style="width:8%">
				Shift I
			</td>
			<td align="center" rowspan="2" style="width:8%">
				Shift GS
			</td>
			<td align="center" rowspan="2" style="width:8%">
				Shift II
			</td>
			<td align="center" rowspan="2" style="width:8%">
				SHIFT III
			</td>
			<td align="center" rowspan="2" style="width:8%">
				Diluar Jadwal
			</td>
			<td align="center" rowspan="2" style="width:8%">
				Total
			</td>
			<td align="center" colspan="4" >
				Persetujuan
			</td>
		</tr>
		<tr>
			<td align="center">
				Keterangan
			</td>
			<td align="center" style="width:7%">
				Kantin
			</td>
			<td align="center">
				Keterangan
			</td>
			<td align="center" style="width:7%">
				HRM
			</td>
		</tr>
<?php
	$sqlTotRows = "select sum(TOTAL) TOT_DATA from ( $sqlView ) ";
	$totAll = getTotData($conn,$sqlTotRows);

	$sqlView = "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	$rsView = oci_parse($conn,$sqlView);
	oci_execute ($rsView);
	$totPerPage = 0;
	$TotData = 0;
	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$TotData = $TotData + 1;
		$totPerPage = $totPerPage + $rowView['TOTAL'];
?>	<tr>
		<td align="center">
			<?php echo $rowView['RECNO']; ?>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['ECD_DATE']; ?>" id="ECD_DATE[]" name="ECD_DATE[]"
			style="width: 110px;text-align: center" readonly
			>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['TOT_S1']; ?>" id="TOT_S1[]" name="TOT_S1[]"
			style="width: 50px;text-align: right" readonly
			>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['TOT_SGS']; ?>" id="TOT_SGS[]" name="TOT_SGS[]"
			style="width: 50px;text-align: right" readonly
			>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['TOT_S2']; ?>" id="TOT_S2[]" name="TOT_S2[]"
			style="width: 50px;text-align: right" readonly
			>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['TOT_S3']; ?>" id="TOT_S3[]" name="TOT_S3[]"
			style="width: 50px;text-align: right" readonly
			>
		</td>
		<td align="center">
			<input type="text" value="<?php echo $rowView['TOT_SUD']; ?>" id="TOT_SUD[]" name="TOT_SUD[]"
			style="width: 50px;text-align: right" readonly
			>
		</td>
		<td align="center">
			<?php echo $rowView['TOTAL']; ?>
		</td>
	<?php
		$stsCkDt = "";
		$sqlCkDt = "select mgtapps.pkgCanteenCoupon.fCkExistSum(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY')) GET_DATA from dual";//28/02/2021
		$stsCkDt = getData($conn,$sqlCkDt);
		$sqlvDt = "select to_char(ECDS_APPROVE_KANTIN_DATE,'DD/MM/YYYY HH24:MI:SS') GET_DATA from EMP_COUPON_DATA_SUM where trunc(ECDS_DATE) = trunc(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY'))";
		$vDtAprvKtn = getData($conn,$sqlvDt);
		$sqlvDt = "select ECDS_KANTIN_REMARK GET_DATA from EMP_COUPON_DATA_SUM where trunc(ECDS_DATE) = trunc(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY'))";
		$vDtKetKtn = getData($conn,$sqlvDt);
		$sqlvDt = "select to_char(ECDS_APPROVE_HRM_DATE,'DD/MM/YYYY HH24:MI:SS') GET_DATA
				  from EMP_COUPON_DATA_SUM where trunc(ECDS_DATE) = trunc(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY'))";

		$vDtAprvMgt = getData($conn,$sqlvDt);
		$sqlvDt = "select ECDS_HRM_REMARK GET_DATA from EMP_COUPON_DATA_SUM where trunc(ECDS_DATE) = trunc(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY'))";
		$vDtKetMgt = getData($conn,$sqlvDt);
		$valBtnKantin	= 	"KANTIN ".$rowView['RECNO'];
		$valBtnMgt	= 	"MGT ".$rowView['RECNO'];
		//Kantin
	?>
			<td align="center">
				<input 	type="text" name="ECDS_KANTIN_REMARK[]" id="ECDS_KANTIN_REMARK[]"
						onkeypress="Javascript: if (event.keyCode==13) Process('$valBtnKantin');"
						value = "<?php echo $vDtKetKtn; ?>"

						<?php
						if ($isAdminKantin!=="Y"){
							echo "readonly";
						} else {
							if ($stsCkDt!=="0"){
								echo "readonly";
							}
						}
						?>

				>
			</td>
			<td>
			<?php
				if ($isAdminKantin==="Y" && $stsCkDt==="0"){
			?>
				<input 	type="button" name="BTN_KANTIN[]" id="BTN_KANTIN[]" value="Setuju"
						onclick="Process('<?php echo $valBtnKantin; ?>')"
						class="BUTTON btn_save"
				>
			<?php
				}else echo $vDtAprvKtn;
			?>

			</td>
			<td align="center">
				<input 	type="text" name="ECDS_HRM_REMARK[]" id="ECDS_HRM_REMARK[]"
						onkeypress="Javascript: if (event.keyCode==13) Process('$valBtnMgt');"
						value = "<?php echo $vDtKetMgt; ?>"
						<?php
						if ($isAdminMgt!=="Y"){
							echo "readonly";
						} else {
							if ($stsCkDt!=="1"){
								echo "readonly";
							}
						}
						?>
				>
			</td>
			<td>
			<?php
				if ($isAdminMgt==="Y" && $stsCkDt==="1"){
					if ($vDtAprvMgt==="") {
			?>
				<input 	type="button" name="BTN_MGT[]" id="BTN_MGT[]" value="Setuju"
						onclick="Process('<?php echo $valBtnMgt; ?>')"
						class="BUTTON btn_save"
				>
			<?php
					} else {echo $vDtAprvMgt; }
				} else {echo $vDtAprvMgt; }

		$sqlvDt = "select ECDS_SYS_ID GET_DATA from EMP_COUPON_DATA_SUM where trunc(ECDS_DATE) = trunc(to_date('".$rowView['ECD_DATE']."','DD/MM/YYYY'))";
			?>
			</td>
		<input type="hidden" name="ECDS_SYS_ID[]" id="ECDS_SYS_ID[]" value="<?php echo getData($conn,$sqlvDt); ?>">
	</tr>
<?php
	}?>
	</table>
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO">

<input type="hidden" name="P_REPORT_NAME_S" id="P_REPORT_NAME_S">
<input type="hidden" name="P_DATE_FROM_S" id="P_DATE_FROM_S">
<input type="hidden" name="P_DATE_TO_S" id="P_DATE_TO_S">
<input type="hidden" name="P_ADMIN_KANTIN" id="P_ADMIN_KANTIN" value=<?php echo$isAdminKantin; ?> >
<input type="hidden" name="P_ADMIN_MGT" id="P_ADMIN_MGT" value=<?php echo$isAdminMgt; ?> >

<input type="hidden" name="ECDS_SYS_ID_S" id="ECDS_SYS_ID_S">
<input type="hidden" name="ECDS_DATE_S" id="ECDS_DATE_S">
<input type="hidden" name="ECDS_TOT_SHIFT_1_S" id="ECDS_TOT_SHIFT_1_S">
<input type="hidden" name="ECDS_TOT_SHIFT_GS_S" id="ECDS_TOT_SHIFT_GS_S">
<input type="hidden" name="ECDS_TOT_SHIFT_2_S" id="ECDS_TOT_SHIFT_2_S">
<input type="hidden" name="ECDS_TOT_SHIFT_3_S" id="ECDS_TOT_SHIFT_3_S">
<input type="hidden" name="ECDS_TOT_OTHERS_S" id="ECDS_TOT_OTHERS_S">

<input type="hidden" name="ECDS_APPROVE_KANTIN_S" id="ECDS_APPROVE_KANTIN_S">
<input type="hidden" name="ECDS_APPROVE_KANTIN_DATE_S" id="ECDS_APPROVE_KANTIN_S">
<input type="hidden" name="ECDS_KANTIN_REMARK_S" id="ECDS_KANTIN_REMARK_S">

<input type="hidden" name="ECDS_APPROVE_HRM_S" id="ECDS_APPROVE_HRM_S">
<input type="hidden" name="ECDS_APPROVE_HRM_DATE_S" id="ECDS_APPROVE_HRM_DATE_S">
<input type="hidden" name="ECDS_HRM_REMARK_S" id="ECDS_HRM_REMARK_S">

<script type='text/javascript'>
	function Process(Prs){
		var prs0 = Prs;
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vErrMsg = ""; var vMsgConfirm = "";

		//var vKetAdminMgt = ""; var vKetKantin = "";
		var vRecNo = 0;

		if (Prs.substr(0,6)==="KANTIN"){
			vRecNo	 = Prs.substr(7);
		} else if (Prs.substr(0,3)==="MGT"){
			vRecNo	 = Prs.substr(4);
		}
		//alert("vRecNo " + vRecNo);
		if (document.getElementById('P_ADMIN_KANTIN').value==="Y"
			||document.getElementById('P_ADMIN_MGT').value==="Y"
			){
			var		n = Number(vRecNo);
        			n = n - 1;
			var 	vDt = "";

			var TotData ="<?php Print($TotData); ?>";
			TotData = Number(TotData) - 1;

			//for (var i = 0; i < vKetKantin.length; i++) {
			for (var i = 0; i <= TotData; i++) {
            	if (n==i){
            		 //alert("kantin");
            		 vDt = document.getElementsByName('ECDS_KANTIN_REMARK[]');
            		 document.getElementById('ECDS_KANTIN_REMARK_S').value = vDt[i].value;
            		 vDt = document.getElementsByName('ECDS_HRM_REMARK[]');
					 document.getElementById('ECDS_HRM_REMARK_S').value = vDt[i].value;
					 vDt = document.getElementsByName('ECDS_SYS_ID[]');
					 document.getElementById('ECDS_SYS_ID_S').value = vDt[i].value;

            		 vDt = document.getElementsByName('ECD_DATE[]');
					 document.getElementById('ECDS_DATE_S').value = vDt[i].value;

					 vDt = document.getElementsByName('TOT_S1[]');
					 document.getElementById('ECDS_TOT_SHIFT_1_S').value = vDt[i].value;

					 vDt = document.getElementsByName('TOT_SGS[]');
					 document.getElementById('ECDS_TOT_SHIFT_GS_S').value = vDt[i].value;

					 vDt = document.getElementsByName('TOT_S2[]');
					 document.getElementById('ECDS_TOT_SHIFT_2_S').value = vDt[i].value;

					 vDt = document.getElementsByName('TOT_S3[]');
					 document.getElementById('ECDS_TOT_SHIFT_3_S').value = vDt[i].value;

					 vDt = document.getElementsByName('TOT_SUD[]');
					 document.getElementById('ECDS_TOT_OTHERS_S').value = vDt[i].value;

            	}
            }
		}
		if (Prs.substr(0,6)==="KANTIN"){
			if (document.getElementById('ECDS_KANTIN_REMARK_S').value ===""){
				vErrMsg = "Kolom Keterangan persetujuan Kantin tidak boleh kosong";
			}
			vMsgConfirm = "KANTIN setuju dengan hasil perhitungan KUPON ?"
			Prs = "INS_KANTIN";
		}else if (Prs.substr(0,3)==="MGT"){
			if (document.getElementById('ECDS_HRM_REMARK_S').value ===""){
				vErrMsg = "Kolom Keterangan persetujuan HRM-MGT tidak boleh kosong";
			}
			vMsgConfirm = "HRM-MGT setuju dengan hasil perhitungan KUPON ?"
			Prs = "UPD_KANTIN";
		}

		if (vErrMsg !== ""){
			alert(vErrMsg);
			Prs = "";
		} else {
			if (vMsgConfirm !== ""){
	            vConfirm = confirm(vMsgConfirm);
				if (vConfirm !== true){
					Prs = "";
				}
			}
		}

		//alert(Prs);

		document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		document.getElementById('STSPRS').value = Prs;
		document.getElementById('P_PAGE_NO').value = "<?php Print($P_PAGE_NO); ?>";
		//alert(document.getElementById('STSPRS').value);
		document.getElementById(vFormName).submit();
	}

	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
		document.getElementById('P_PAGE_NO').value ="1";
		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
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
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (document.getElementById('P_DATE_FROM') !=null) {
			document.getElementById('P_DATE_FROM_S').value = document.getElementById('P_DATE_FROM').value;
		}
		if (document.getElementById('P_DATE_TO') !=null) {
			document.getElementById('P_DATE_TO_S').value = document.getElementById('P_DATE_TO').value;
		}
		document.getElementById('P_PAGE_NO').value =vLastPageNo;
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
	</script>
</form>
</body>
</html>
