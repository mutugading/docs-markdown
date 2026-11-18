<?php

	$ECES_REMARK_HRM_S= "";
	if(isset($_POST['ECES_REMARK_HRM_S'])) {
			$ECES_REMARK_HRM_S=$_POST['ECES_REMARK_HRM_S'];
	}
	$ECES_SYS_ID_S= "";
	if(isset($_POST['ECES_SYS_ID_S'])) {
			$ECES_SYS_ID_S=$_POST['ECES_SYS_ID_S'];
	}

	//echo "ECES_SYS_ID_S $ECES_SYS_ID_S : ECES_REMARK_HRM_S $ECES_REMARK_HRM_S : STSPRS $STSPRS";
	if ($STSPRS === "UPD HRM SCAN ERR") {
		$sql = "update MGTAPPS.EMP_COUPON_ERR_SCAN
			set ECES_REMARK_HRM = '$ECES_REMARK_HRM_S'
					, ECES_MODIFIED_BY= '$USER_NAME'
					, ECES_MODIFIED_TIMESTAMP= sysdate
			where ECES_SYS_ID = '$ECES_SYS_ID_S'
			";
			//echo "$sql";die();
		$result=oci_parse($conn,$sql);
		if (!$result) {
			 $oerr = OCIError($conn);
			 echo "Error Process :".$oerr["message"]." $sql";
			 exit;
				die();
		}
		$prs = oci_execute($result);
		if (!$prs) {
				$e = oci_error($result);  // For oci_execute errors pass the statement handle
				print htmlentities($e['message']);
				print "\n<pre>\n";
				print htmlentities($e['sqltext']);
				printf("\n%".($e['offset']+1)."s", "^");
				print  "\n</pre>\n";
				die();
		} else {
			echo "<script>alert('Data telah terupdate...!!');</script>";
			echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
			echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
			echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";

			echo "<input type='hidden' name='P_DATE_FROM_S' id='P_DATE_FROM_S' value='$P_DATE_FROM_S'>";
			echo "<input type='hidden' name='P_DATE_TO_S' id='P_DATE_TO_S' value='$P_DATE_TO_S'>";

			//die();
			echo "<input type='hidden' id='P_REPORT_ID_S' name='P_REPORT_ID_S' value='202204021' >";
			echo "<input type='hidden' name='P_REPORT_NAME_S' id='P_REPORT_NAME_S' value='CC_003_R09'>";
			echo "</form>";
			//echo "EAH_SYS_ID_S $EAH_SYS_ID_S</br>";die();
			echo "<script type='text/javascript'>";
			echo "document.getElementById('$FORM_NAME').submit();";
			echo "</script>";
		}
	}
?>
<input type="hidden" id="ECES_REMARK_HRM_S" name="ECES_REMARK_HRM_S" >
<input type="hidden" id="ECES_SYS_ID_S" name="ECES_SYS_ID_S" >
<table style="width:100%"  align="center">
	<tr>
		<td align="center">
		Date From <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')" >
		To <input type="date" name="P_DATE_TO" id="P_DATE_TO" value="<?php echo $P_DATE_TO_S; ?>" onchange="Process('OK')" >
		</td>
	</tr>
</table>
<?php
	include($FORM_NAME."_R09_SQL.PHP");

	$sqlView = 	"select rownum RECNO,t.* from ($sqlData) t ";

	//echo "$sqlView</br>";

	$sqlTotRows = "select count(-1) TOT_DATA from ( $sqlView ) ";
	$totRows = getTotData($conn,$sqlTotRows);
	//echo "tot rows ".$totRows;
	$P_ROWS_DISP = 10
	;
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
<table style="width:80%" border="1" align="center">
	<tr>
		<td align="center" colspan="7">
			<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
			<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 50px;"  value="<?php echo $P_PAGE_NO;?>"  onchange="Process('NO_PAGE')" >
			: <?php echo $P_LAST_PAGE_NO; ?>
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
	</tr>
	<tr>
		<tr>
		<td align="center" style="width:5%">
			No
		</td><!-- 1 -->
		<td align="center" style="width:15%">
			Tgl Kupon
		</td><!-- 2 -->
		<td align="center" style="width:15%">
			NIK
		</td><!-- 3 -->
		<td align="center" >
			Waktu Scan
		</td><!-- 4 -->
		<td align="center" >
			Keterangan Process
		</td><!-- 5 -->
		<td align="center" >
			Keterangan HRM
		</td><!-- 6 -->
		<td align="center" >
			Update Oleh
		</td><!-- 7 -->
	</tr>
<?php

	 $sqlView = " select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	 //echo "$sqlView </br>";
	 $rsView = oci_parse($conn,$sqlView);
	 oci_execute ($rsView);
	// $totPerPage = 0;
	$lpNo = 0;
	$totPgS1 = 0; $totPgGs = 0; $totPgS2 = 0; $totPgS3 = 0; $totPgOths = 0; $totPgGrd = 0;

	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$updPrs = "updScanErr('UPD DT $lpNo')";
?>
<input type="hidden" id="ECES_SYS_ID[]" name="ECES_SYS_ID[]" value="<?php echo $rowView['ECES_SYS_ID']; ?>" >
<tr>
	<tr>
	<td align="center" style="width:5%">
		<?php echo $rowView['RECNO']; ?>
	</td><!-- 1 -->
	<td align="center" style="width:15%">
		<?php echo $rowView['ECES_COUPON_DATE']; ?>
	</td><!-- 2 -->
	<td align="center" style="width:15%">
		<?php echo $rowView['ECES_PE_NIK']; ?>
	</td><!-- 3 -->
	<td align="center" style="width:15%">
		<?php echo $rowView['ECES_CREATED_TIMESTAMP']; ?>
	</td><!-- 4 -->
	<td align="center" style="width:15%">
		<?php echo $rowView['ECES_REMARK_PROCESS']; ?>
	</td><!-- 5 -->
	<td align="center" style="width:15%">
		<input type="text" id="ECES_REMARK_HRM[]" name="ECES_REMARK_HRM[]" value="<?php echo $rowView['ECES_REMARK_HRM']; ?>" onchange="<?php echo $updPrs; ?>">
	</td><!-- 6 -->
	<td align="center" style="width:15%">
		<?php
		 	if (!empty($rowView['ECES_MODIFIED_BY'])){
				echo $rowView['ECES_MODIFIED_BY']."-".$rowView['USER_MODIFIED_BY']."</br>".$rowView['ECES_MODIFIED_TIMESTAMP'];
			}
		?>
	</td><!-- 7 -->
</tr>
<?php
		$lpNo = $lpNo + 1;
	}
?>
</table>
