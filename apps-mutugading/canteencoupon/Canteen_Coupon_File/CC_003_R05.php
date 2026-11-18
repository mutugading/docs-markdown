<table style="width:100%"  align="center">	
	<tr>
		<td align="center">
		Date From <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')" >
		To <input type="date" name="P_DATE_TO" id="P_DATE_TO" value="<?php echo $P_DATE_TO_S; ?>" onchange="Process('OK')" >
		</td>
	</tr>
</table>
<?php
	include($FORM_NAME."_R04_SQL.PHP");

	$sqlView = 	"select rownum RECNO,t.* from ($sqlData) t ";

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
<table style="width:70%" align="center">
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
<div align="center">
<table style="width:70%" border="1" align="center">
	<tr>
		<tr>
		<td align="center" style="width:5%">
			No
		</td>
		<td align="center" style="width:15%">
			HARI
		</td>
		<td align="center" style="width:15%">
			TANGGAL
		</td>
		<td align="center" style="width:11%">
			SHIFT 3
		</td>
	</tr>
<?php
	$sqlTot = "select sum(ECDS_TOT_SHIFT_3) TOT_DATA from ( $sqlView ) ";
	$totAllS3 = getTotData($conn,$sqlTot);

	$sqlView = "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";
	$rsView = oci_parse($conn,$sqlView);
	oci_execute ($rsView);
	// $totPerPage = 0;
	$lpNo = 0;
	$totPgS3 = 0;

	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$lpNo = $lpNo + 1;
		$totPgS3 = $totPgS3+$rowView['ECDS_TOT_SHIFT_3'];

?>	<tr>
		<td align="right">
			<?php echo $rowView['RECNO']; ?>
		</td>
		<td align="left">
			<?php echo $rowView['HARI']; ?>
		</td>
		<td align="center">
			<?php echo $rowView['ECDS_DATE_1']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['ECDS_TOT_SHIFT_3']; ?>
		</td>
	</tr>
<?php
	}?>
	<tr>
		<td align="right" colspan="3">
			Total per Halaman
		</td>
		<td align="right"><?php echo number_format($totPgS3); ?></td>
	</tr>
	<tr>
		<td align="right" colspan="3">
			Total Semua
		</td>
		<td align="right"><?php echo number_format($totAllS3); ?></td>
	</tr>
</table>
</div>
