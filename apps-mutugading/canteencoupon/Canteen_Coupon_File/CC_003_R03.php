<table style="width:100%"  align="center">
	<tr>
		<td align="center">
		Date Overtime <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')">
		</td>
	</tr>
	<tr>
		<td align="center">
<?php $sqlList = getSqlParams($conn,"SLCT_CC_003_R003"); ?>
			<select name="SLCT_CC_003_R003" id="SLCT_CC_003_R003" onchange="Process('CHANGE_REPORT')" >
				<option value="NULL">NULL</option>
<?php
	$rsDataList = oci_parse($conn,$sqlList);
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
				<option value="<?php echo $rowDataList['DESCRIP']; ?>" <?php if ($SLCT_CC_003_R003_S===$rowDataList['DESCRIP']){echo "selected"; }?>
				><?php echo $rowDataList['DESCRIP']; ?></option>
<?php
	}
?>
			</select>
			<input type="TEXT" name="SLCT_CC_003_R003_I" id="SLCT_CC_003_R003_I" value="<?php echo $SLCT_CC_003_R003_I_S; ?>" onchange="Process('OK')">
		</td>
	</tr>
</table>
<?php
	include($FORM_NAME."_R03_SQL.PHP");
	$sqlData = $sqlData." ORDER BY ECO_DATE,ECO_CREATED_TIMESTAMP ";
	//echo "</br> SLCT_CC_003_R003_S $SLCT_CC_003_R003_S";
	if ($SLCT_CC_003_R003_S==="NULL"){
		$sqlView = 	" select rownum RECNO,ECO_SYS_ID,ECO_CREATED_BY,ECO_PE_NIK,ECO_PE_NAMA,ECO_DATE,CREATE_BY_NAME,ECO_MPSM_SCH,ECO_MPSM_SCH_DESC,ECO_RELIEVER_STS from (".$sqlData.")";
	}else{
		include("CC_003_R03_WHERE.PHP");
	}

	$sqlTotRows = "select count(-1) TOT_DATA from ( $sqlView ) ";
	//echo "sqlTotRows $sqlTotRows";
	$totRows = getTotData($conn,$sqlTotRows);
	$P_ROWS_DISP = 15;
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
<table style="width:100%" align="center">
	<tr>
		<td style="width:100%" align="left">
			<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
			<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 50px;"  value="<?php echo $P_PAGE_NO;?>"  onchange="Process('NO_PAGE')" >
			: <?php echo $P_LAST_PAGE_NO; ?>
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
	</tr>
</table>
<table style="width:100%" border="1">
	<tr>
		<td align="center" style="width:3%">
			No
		</td>
		<td align="center" style="width:7%">
			DATE
		</td>
		<td align="center" style="width:7%">
			NIK
		</td>
		<td align="left" style="width:25%">
			NAME
		</td>
		<td align="center" style="width:10%">
			DEPT
		</td>
		<td align="center" style="width:15%">
			SCHEDULE
		</td>
		<td align="center" >
			ENTRY BY
		</td>
		<td align="center" style="width:15%">
			ACTUAL OVERTIME
		</td>
	</tr>
<?php
	//echo "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	$sqlView = "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	//echo $sqlView;
	$rsView = oci_parse($conn,$sqlView);
	oci_execute ($rsView);
	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$P_PE_NIK		= $rowView['ECO_PE_NIK'];
		include("../GET_DATA_EMP.PHP");
		$ActlOvrTm = getData($conn
							,"select to_char(ECD_ACTUAL_BREAKTIME,'DD-MM-YYYY HH24:MI') GET_DATA from emp_coupon_data
							where ECD_ECO_SYS_ID='".$rowView['ECO_SYS_ID']."'");
?>	<tr>
		<td <?php echo getStyle($font,"right"); ?>>
			<?php echo $rowView['RECNO']; ?>
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowView['ECO_DATE']; ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['ECO_PE_NIK']; ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php
				if ($rowView['ECO_RELIEVER_STS'] ==="Y"){
					echo $P_PE_NAMA." (RELIEVER)";
				}else echo $P_PE_NAMA;
			?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $P_PE_DEPT_ID;  ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['ECO_MPSM_SCH']." ".$rowView['ECO_MPSM_SCH_DESC']; ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['CREATE_BY_NAME']; ?>
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo "$ActlOvrTm"; ?>

		</td>
	</tr>
<?php
	}?>
</table>
