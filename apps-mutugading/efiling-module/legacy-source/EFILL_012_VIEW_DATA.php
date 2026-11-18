<?php
if ($DATE_FROM_S !=="" && $DATE_TO_S !=="" )	{

	//echo "sqlViewHdr $sqlViewHdr</br>";
	$sqlWhere = "";
	if ($FILTER_SCLT_S==="PO No"){
		$sqlWhere = " AND ph_txn_code|| '-' ||ph_no like '%$FILTER_SCLT_VAL_S%' ";
	}

	if ($FILTER_SCLT_S==="Supplier Name"){
		$sqlWhere = " AND SUPP_NAME like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
	}

	if ($FILTER_SCLT_S==="By User"){
		$sqlWhere = " AND PH_FLEX_17 like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
	}

	$sqlView = "select rownum REC_NO,d.* from ($sqlViewHdr $sqlWhere order by PH_DT, po_no) d ";

	//echo "$sqlView </br>";

	$StsViewDt =  getData($conn,"select count(-1) GET_DATA from ($sqlView)  ");
	$totRows = $StsViewDt;

	$P_ROWS_DISP = 15;
    $P_LAST_PAGE_NO = ceil($totRows / $P_ROWS_DISP);
    //echo "P_LAST_PAGE_NO $P_LAST_PAGE_NO : P_PAGE_NO $P_PAGE_NO</br>";
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

	if ($P_PAGE_NO > $P_LAST_PAGE_NO){
		$P_PAGE_NO = $P_LAST_PAGE_NO;
	}
	//echo "P_LAST_PAGE_NO $P_ROW_END s/d P_LAST_PAGE_NO $P_LAST_PAGE_NO </br>";

	if ($StsViewDt!=="0"){
		$filterDate = 0;
?>
<table style="width:100%" border="1">
	<tr align="center">
		<td style="text-align: center;" >
		Filter By
		<select name="FILTER_SCLT" id="FILTER_SCLT" onchange="Process('')">
			<option value="NULL">Null</option>
			<option value="PO No"
					<?php if ($FILTER_SCLT_S==="PO No"){echo "selected";} ?>
			>
				PO No
			</option>
			<option value="Supplier Name"
					<?php if ($FILTER_SCLT_S==="Supplier Name"){
						echo "selected";
					} ?>
			>
				Supplier Name
			</option>
			<option value="By User"
					<?php if ($FILTER_SCLT_S==="By User"){
						echo "selected";
					} ?>
			>
				By User
			</option>
		</select>
<?php 	if ($filterDate===0){ ?>
		<input style="font-size:11pt;height:20px;width:200px;text-transform:uppercase;" 	type="text" name="FILTER_SCLT_VAL" id="FILTER_SCLT_VAL" value="<?php echo $FILTER_SCLT_VAL_S; ?>" >
<?php 	}else if ($filterDate===1){ ?>
			<input 	type="DATE" name="FILTER_SCLT_DATE" id="FILTER_SCLT_DATE" value="<?php echo $FILTER_SCLT_DATE_S; ?>" >
<?php 	} ?>
		<input type="BUTTON"  value="FIND" class="BUTTON btn_delete" onclick="Process('FIND')">
		</td>
	</tr>
</table>
<?php
	}
?>
	<table style="width:100%" border="1">
	<tr>
		<td align="center" colspan="10">
			<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
			<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 55px;"  value="<?php echo $P_PAGE_NO;?>"
					onkeypress="Javascript: if (event.keyCode==13) GoRecord();"
			>
			: <?php echo $P_LAST_PAGE_NO; ?>
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
	</tr>
	</table>
	<table style="width:100%" border="1">
		<tr>
			<td align="center" style="width:5%">
				No
			</td>
			<td align="center" style="width:15%">
				Po No
			</td>
			<td align="center" style="width:10%">
				Date
			</td>
			<td align="center">
				Supplier Code
			</td>
			<td align="center">
				Name
			</td>
			<td align="center">
				By User
			</td>
			<td align="center" style="width:5%">
				Bill
			</td>
		</tr>
<?php
	$RecNO = 0;
	$sqlViewHdr =  "select REC_NO,PO_NO,to_char(PH_DT,'YYYY-MM-DD') PH_DT,PH_SUPP_CODE, SUPP_NAME,PH_FLEX_17,TRX_FOLDER,TRX_YY,TRX_NO from ($sqlView)
					where REC_NO between $P_ROW_START and $P_ROW_END ";

	//if ($USER_NAME==="ADMIN") {
		//echo "$sqlViewHdr </br>";
	//}

	$rsDt = oci_parse($conn,$sqlViewHdr);
	oci_execute ($rsDt);
	$PaymentNo = "";
	while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
		$RecNO++;
?>
	<tr>
		<td align = "right">
			<?php echo $rowDt['REC_NO'];?>
		</td>
		<td align = "center">
			<?php
			$linkData 	= "D:/XAMPP/htdocs/webapps/Doc_Folder/";
			$linkFile	= $linkData.$rowDt['TRX_FOLDER']."/".$rowDt['TRX_YY']."/".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO'].".PDF";
			//echo "linkFile $linkFile </br>";
			$lstDtl 	= "Process('VIEW_DTL ".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO']."')";
			if (file_exists($linkFile)){
				$linkData 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
				$linkFile	= $linkData.$rowDt['TRX_FOLDER']."/".$rowDt['TRX_YY']."/".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO'].".PDF";
				$lstData 	= "Process('OPEN_FILE $linkFile')";
				$linkFileName= $rowDt['TRX_FOLDER']."/".$rowDt['TRX_YY']."/".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO'].".PDF";
?>
		<input type="button" value="<?php echo $rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO']; ?>" onclick="<?php echo $lstData; ?>" class="BUTTON btn_process">
<?php
			}else{
				echo "<font color = 'red'  size = '2.5'><b>".$rowDt['PO_NO']." NA</b></font>";
			}
			?>
		</td>
		<td align = "center">
			<?php
				echo $rowDt['PH_DT'];
			?>
		</td>
		<td align = "left">
			<?php
				if (!empty($rowDt['PH_SUPP_CODE'])){
					echo $rowDt['PH_SUPP_CODE'];
				}
			?>
		</td>
		<td align = "left">
			<?php
				if (!empty($rowDt['SUPP_NAME'])){
					echo $rowDt['SUPP_NAME'];
				}
			?>
		</td>
		<td align = "center">
			<?php
				if (!empty($rowDt['PH_FLEX_17'])){
					echo $rowDt['PH_FLEX_17'];
				}
			?>
		</td>
		<td align = "center">
			<?php
				$sqlGetData = 	"select count(-1) GET_DATA from ($sqlViewDtl) where PO_NO ='".$rowDt['PO_NO']."' ";
				$ckDtl = getData($conn,$sqlGetData);
				//echo "$sqlGetData : $ckDtl";
				//echo $lstDtl;
			?>
			<input 	type="BUTTON" name="BTN_DTL[]" id="BTN_DTL[]" value="Detail" class="BUTTON btn_view" onclick="<?php echo $lstDtl; ?>">
		</td>
	</tr>
<?php
	}
?>
	</table>
<?php
}
?>
