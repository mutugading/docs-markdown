<table style="width:100%"  align="center">
	<tr>
		<td align="center">
		Date From <input type="date" name="P_DATE_FROM" id="P_DATE_FROM" value="<?php echo $P_DATE_FROM_S; ?>" onchange="Process('OK')" >
		To <input type="date" name="P_DATE_TO" id="P_DATE_TO" value="<?php echo $P_DATE_TO_S; ?>" onchange="Process('OK')" >
		</td>
	</tr>
</table>
<?php
	include($FORM_NAME."_R01_SQL.PHP");

	$sqlView = 	"select * from (select 	rownum RECNO,to_char(ECD_DATE,'DD-MM-YYYY') ECD_DATE, TOT_S1, TOT_SGS, TOT_S2, TOT_S3, TOT_SUD
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
		<td align="center" style="width:8%">
			DATE
		</td>
		<td align="center" style="width:8%">
			SHIFT I
		</td>
		<td align="center" style="width:8%">
			GS
		</td>
		<td align="center" style="width:8%">
			SHIFT II
		</td>
		<td align="center" style="width:8%">
			SHIFT III
		</td>
		<td align="center" style="width:8%">
			DILUAR JADWAL
		</td>
		<td align="center"  style="width:8%">
			TOTAL
		</td>
		<td align="center">
			DISETUJUI KANTIN
		</td>
		<td align="center">
			DISETUJUI HRM
		</td>
	</tr>
<?php
	$sqlTotRows = "select sum(TOTAL) TOT_DATA from ( $sqlView ) ";
	//echo $sqlTotRows;
	$totAll = getTotData($conn,$sqlTotRows);

	$sqlView = "select * from ($sqlView) where RECNO between $P_ROW_START and $P_ROW_END ";

	//if ($USER_NAME === "1949"){echo "$sqlView <br>";}

	$rsView = oci_parse($conn,$sqlView);
	oci_execute ($rsView);
	$totPerPage = 0;
	$totPerPageKntn = 0;

	$sqlTotRows = 	"select sum(nvl(ECDS_TOT_SHIFT_1,0) + nvl(ECDS_TOT_SHIFT_GS,0) + nvl(ECDS_TOT_SHIFT_2,0) + nvl(ECDS_TOT_SHIFT_3,0) + nvl(ECDS_TOT_OTHERS,0)) TOT_DATA
					from EMP_COUPON_DATA_SUM
					where ECDS_APPROVE_KANTIN_DATE is not null
					and  trunc(ECDS_DATE) between trunc(to_date('$P_DATE_FROM_S','YYYY-MM-DD')) and trunc(to_date('$P_DATE_TO_S','YYYY-MM-DD'))";
	//echo $sqlTotRows;
	$totAllKntn = getTotData($conn,$sqlTotRows);

	$totPerPageHrm = 0;
	$sqlTotRows = "select sum(nvl(ECDS_TOT_SHIFT_1,0) + nvl(ECDS_TOT_SHIFT_GS,0) + nvl(ECDS_TOT_SHIFT_2,0) + nvl(ECDS_TOT_SHIFT_3,0) + nvl(ECDS_TOT_OTHERS,0)) TOT_DATA
					from EMP_COUPON_DATA_SUM
					where ECDS_APPROVE_HRM_DATE is not null
					and  trunc(ECDS_DATE) between trunc(to_date('$P_DATE_FROM_S','YYYY-MM-DD')) and trunc(to_date('$P_DATE_TO_S','YYYY-MM-DD'))";
	//echo $sqlTotRows;
	$totAllHrm = getTotData($conn,$sqlTotRows);
	while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$totPerPage = $totPerPage + $rowView['TOTAL'];
		$sqlGtDt = "select 	case when ECDS_APPROVE_KANTIN_DATE is not null then
								'Tanggal : '||to_char(ECDS_APPROVE_KANTIN_DATE,'DD/MM/YYYY HH24:MI')||', Keterangan : '||ECDS_KANTIN_REMARK
							else ' '
							end  GET_DATA
					from EMP_COUPON_DATA_SUM
					where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowView['ECD_DATE']."'";
	    //echo $sqlGtDt;
		$DtKantin = getData($conn,$sqlGtDt);
		if ($DtKantin!==" " && $DtKantin!==""){
			$totPerPageKntn = $totPerPageKntn + $rowView['TOTAL'];
		}
		$sqlGtDt = "select 	case when ECDS_APPROVE_HRM_DATE is not null then
        						'Tanggal : '||to_char(ECDS_APPROVE_HRM_DATE,'DD/MM/YYYY HH24:MI')||', Keterangan : '||ECDS_HRM_REMARK
        					else ' '
        					end  GET_DATA
					from EMP_COUPON_DATA_SUM
					where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowView['ECD_DATE']."'";
				//echo $sqlGtDt;
		$DtHrm = getData($conn,$sqlGtDt);
		if ($DtHrm!==" " && $DtHrm!==""){
			$totPerPageHrm = $totPerPageHrm + $rowView['TOTAL'];
		}
?>	<tr>
		<td align="right">
			<?php echo $rowView['RECNO']; ?>
		</td>
		<td align="center">
			<?php echo $rowView['ECD_DATE']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['TOT_S1']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['TOT_SGS']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['TOT_S2']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['TOT_S3']; ?>
		</td>
		<td align="right">
			<?php echo $rowView['TOT_SUD']; ?>
		</td>
		<td align="right">
			<?php echo number_format($rowView['TOTAL']); ?>
		</td>
		<td align="left">
			<?php echo $DtKantin; ?>
		</td>
		<td align="left">
			<?php echo $DtHrm; ?>
		</td>
	</tr>
<?php
	}?>
	<tr>
		<td align="right" colspan="7">
			Total per Page
		</td>
		<td align="right">
			<?php echo number_format($totPerPage); ?>
		</td>
		<td align="right"><?php echo number_format($totPerPageKntn); ?></td>
		<td align="right"><?php echo number_format($totPerPageHrm); ?></td>
	</tr>
	<tr>
		<td align="right" colspan="7">
			Total All
		</td>
		<td align="right">
			<?php echo number_format($totAll); ?>
		</td>
		<td align="right"><?php echo number_format($totAllKntn); ?></td>
		<td align="right"><?php echo number_format($totAllHrm); ?></td>
	</tr>
</table>
