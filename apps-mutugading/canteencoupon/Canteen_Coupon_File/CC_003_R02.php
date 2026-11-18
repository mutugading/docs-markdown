<?php
	//echo "P_DATE_TO $P_DATE_TO_S";
	//echo "SLCT_CC_003_R002_S $SLCT_CC_003_R002_S";
?>
<table style="width:100%" align="center">
	<tr>
		<td align="center">
			Date <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')">
			<input type="date" name="P_DATE_TO" id="P_DATE_TO" value="<?php echo $P_DATE_TO_S; ?>" onchange="Process('OK')">
		</td>
	</tr>
	<tr>
		<td align="center">
<?php $sqlList = getSqlParams($conn,"SLCT_CC_003_R002"); ?>
			<select name="SLCT_CC_003_R002" id="SLCT_CC_003_R002" onchange="Process('CHANGE_REPORT')" >
				<option value="NULL">NULL</option>
<?php
	$rsDataList = oci_parse($conn,$sqlList);
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
				<option value="<?php echo $rowDataList['DESCRIP']; ?>" <?php if ($SLCT_CC_003_R002_S===$rowDataList['DESCRIP']){echo "selected"; }?>
				><?php echo $rowDataList['DESCRIP']; ?></option>
<?php
	}
?>
			</select>
			<input type="TEXT" name="SLCT_CC_003_R002_I" id="SLCT_CC_003_R002_I" value="<?php echo $SLCT_CC_003_R002_I_S; ?>" onchange="Process('OK')">
		</td>
	</tr>
</table>
<?php
	include($FORM_NAME."_R02_SQL.PHP");
	$sqlData = $sqlData." ORDER BY ECD_DATE,ECD_ACTUAL_BREAKTIME ";
	if ($SLCT_CC_003_R002_S==="NULL"){
		$sqlView = 	" 	select 	rownum RECNO,ECD_DATE,ECD_PE_NIK, ECD_PE_NAMA
														, ECD_PE_DEPT_ID,ECD_ACTUAL_BREAKTIME,SHIFT_BREAK_DESC,STS_OVERTIME
														,ECD_CREATED_BY,SCHEDULE_BREAK,ON_SCHEDULE_BREAK,ECD_REMARKS,NOSHIFT
														from (".$sqlData.")";
	}else{
		include("CC_003_R02_WHERE.php");
	}
	//echo $sqlView;
	$sqlTotRows = "select count(-1) TOT_DATA from ( $sqlView ) ";
	$totRows = getTotData($conn,$sqlTotRows);
	//echo "tot rows ".$totRows;
	$P_ROWS_DISP = 25;
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
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
	</tr>
</table>
<table style="width:100%" border="1">
	<tr>
		<td align="center" style="width:3%">No</td><!-- 1 -->
		<td align="center" style="width:7%">DATE</td><!-- 2 -->
		<td align="center" style="width:7%">NIK</td><!-- 3 -->
		<td align="center">NAME</td><!-- 4 -->
		<td align="center" style="width:10%">DEPARTMENT</td><!-- 5 -->
		<td align="center" style="width:10%">ACTUAL SCAN</td><!-- 6 -->
		<td align="center" style="width:10%">ACTUAL BREAK</td><!-- 7 -->
		<td align="center" style="width:10%">SCHEDULE BREAK</td><!-- 8 -->
		<td align="center" style="width:10%">ON SCHEDULE</td><!-- 8 -->
		<td align="center" style="width:7%">OVERTIME</td><!-- 9 -->
		<td align="center" style="width:7%">ENTRY BY</td><!-- 10 -->
		<td align="center" style="width:10%">REMARKS</td><!-- 11 -->
	</tr>
<?php
	$fontSize = "font-size:12px";
	//echo "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	$sqlView = "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	if ($USER_NAME = "0686" || $USER_NAME = "1949"){
			//echo $sqlView;
	}

	$rsView = oci_parse($conn,$sqlView);
	oci_execute ($rsView);
	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		// $ECD_PE_NIK = $rowView['ECD_PE_NIK']; $ECD_DATE = $rowView['ECD_DATE'];
		// $brkStrt = getBrkStrt($conn,$ECD_PE_NIK,$ECD_DATE);
		// $brkEnd = getBrkEnd($conn,$ECD_PE_NIK,$ECD_DATE);
		// $Stsbrk = "";

		$Stsbrk = ""; if (!empty($rowView['SCHEDULE_BREAK'])){$Stsbrk = $rowView['SCHEDULE_BREAK'];	}
		$ON_SCHEDULE_BREAK = "";if (!empty($rowView['ON_SCHEDULE_BREAK'])){$ON_SCHEDULE_BREAK = $rowView['ON_SCHEDULE_BREAK'];}
		$ECD_CREATED_BY = "";if (!empty($rowView['ECD_CREATED_BY'])){$ECD_CREATED_BY = $rowView['ECD_CREATED_BY'];}
?>	<tr>
		<td <?php echo getStyle($font,"right"); ?> >
			<?php echo $rowView['RECNO']; ?>
		</td><!-- 1 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowView['ECD_DATE']; ?>
		</td><!-- 2 -->
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['ECD_PE_NIK']; ?>
		</td><!-- 3 -->
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['ECD_PE_NAMA']; ?>
		</td><!-- 4 -->
		<td <?php echo getStyle($font,"left"); ?>>
			<?php echo $rowView['ECD_PE_DEPT_ID']; ?>
		</td><!-- 5 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowView['ECD_ACTUAL_BREAKTIME']; ?>
		</td><!-- 6 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowView['SHIFT_BREAK_DESC']; ?>
		</td><!-- 7 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo "(".$rowView['NOSHIFT'].") ".$Stsbrk; ?>
		</td><!-- 8 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $ON_SCHEDULE_BREAK; ?>
		</td><!-- 8 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowView['STS_OVERTIME']; ?>
		</td><!-- 9 -->
		<td <?php echo getStyle($font,"center"); ?>> <?php echo $ECD_CREATED_BY; ?> </td><!-- 10 -->
		<td <?php echo getStyle($font,"left"); ?> >
			<?php echo $rowView['ECD_REMARKS']; ?>
		</td><!-- 11 -->
	</tr>
<?php
	}?>
</table>
