<?php
if ($DATE_FROM_S !=="" && $DATE_TO_S !=="" )	{

		//echo "sqlViewHdr $sqlViewHdr</br>";
		$sqlWhere = "";
		if ($FILTER_SCLT_S==="Payment No"){
			$sqlWhere = " AND OST_TRAN_CODE||'-'||OST_DOC_NO like '%$FILTER_SCLT_VAL_S%' ";
		}

		if ($FILTER_SCLT_S==="Supplier Name"){
			$sqlWhere = " AND upper(SUPP_NAME) like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
		}

		if ($FILTER_SCLT_S==="PO No"){
			$sqlWhere = " AND OST_KEY_NO in (
							SELECT distinct OM_OST_KEY_NO
							FROM mgtdat.fv_os_match,mgtdat.ft_os,
								(SELECT bh_txn_code, bh_no,gh_ref_txn_code || '-' || gh_ref_no match_po_no
								 FROM mgtdat.ot_bill_head, mgtdat.ot_gr_head
						     	 WHERE bh_ref_sys_id = gh_sys_id
						     	)
							WHERE ost_key_no = om_base_seq_no
						   	AND NVL (ost_tran_code, 'FXADJ') != ('FXADJ')
						   	AND ost_tran_code = bh_txn_code(+)
						   	AND ost_doc_no = bh_no(+)
						   	and MATCH_PO_NO like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ) ";

		}

		$sqlView = "select rownum REC_NO,d.* from ($sqlViewHdr $sqlWhere ORDER BY OST_DOC_DT, PAYMENT_NO) d ";

		$StsViewDt =  getData($conn,"select count(-1) GET_DATA from ($sqlView)  ");
		$totRows = $StsViewDt;

		if ($USER_NAME === "1949"){
			//echo "totRows $totRows : $sqlView </br>";
		}


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

		if ($P_PAGE_NO > $P_LAST_PAGE_NO){
			$P_PAGE_NO = $P_LAST_PAGE_NO;
		}


	if ($StsViewDt!=="0"){
		$filterDate = 0;
?>
<table style="width:100%" border="1">
	<tr align="center">
		<td style="text-align: center;" >
		Filter By
		<select name="FILTER_SCLT" id="FILTER_SCLT" onchange="Process('')">
			<option value="NULL">Null</option>
			<option value="Payment No"
					<?php if ($FILTER_SCLT_S==="Payment No"){echo "selected";} ?>
			>
				Payment No
			</option>
			<option value="Supplier Name"
					<?php if ($FILTER_SCLT_S==="Supplier Name"){
						echo "selected";
					} ?>
			>
				Supplier Name
			</option>
			<option value="PO No"
					<?php if ($FILTER_SCLT_S==="PO No"){
						echo "selected";
					} ?>
			>
				PO No
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
	<table style="width:100%" border="1" align="center">
		<tr>
			<td align="center" style="width:5%">
				No
			</td>
			<td align="center" style="width:15%">
				Print of Transfer
			</td>
			<td align="center" style="width:10%">
				Date
			</td>
			<td align="center" style="width:40%">
				Name
			</td>
			<td align="center">
				PO No Refference
			</td>
			<td align="center">
				Detail
			</td>
		</tr>
<?php
	$RecNO = 0;
	/*$sqlViewHdr =  "select  REC_NO,PAYMENT_NO, to_char(OST_DOC_DT,'YYYY-MM-DD') OST_DOC_DT, OST_SUB_ACNT_CODE, SUPP_NAME
							, PH_CURR_CODE,MATCH_DOC_DT, MATCH_PO_NO, MATCH_FC_AMT
							, TRX_FOLDER,TRX_YY,TRX_NO from ($sqlView)
					where REC_NO between $P_ROW_START and $P_ROW_END ";*/
	$sqlViewHdr =  "select   REC_NO,PAYMENT_NO, to_char(OST_DOC_DT,'YYYY-MM-DD') OST_DOC_DT, SUPP_NAME
						, TRX_FOLDER,TRX_YY,TRX_NO from ($sqlView)
				where REC_NO between $P_ROW_START and $P_ROW_END
				order by REC_NO ";
	if ($USER_NAME === "ADMIN" || $USER_NAME === "1949" ){
		//echo "$sqlViewHdr </br>";
	}
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
			//echo $rowDt['OST_KEY_NO']."</br>";//"linkFile $linkFile </br>";
			$lstDtl 	= "Process('VIEW_DTL ".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO']."')";
			if (file_exists($linkFile)){
				$linkData 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
				$linkFile	= $linkData.$rowDt['TRX_FOLDER']."/".$rowDt['TRX_YY']."/".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO'].".PDF";
				$lstData 	= "Process('OPEN_FILE $linkFile')";
				$linkFileName = $rowDt['TRX_FOLDER']."/".$rowDt['TRX_YY']."/".$rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO'].".PDF";
?>
		<input type="button" value="<?php echo $rowDt['TRX_FOLDER']."-".$rowDt['TRX_NO']; ?>" onclick="<?php echo $lstData; ?>" class="BUTTON btn_process">
<?php
			}else{
				echo "<font color = 'red'  size = '2.5'><b>".$rowDt['PAYMENT_NO']." NA</b></font>";
			}
			?>
		</td>
		<td align = "center">
			<?php
				echo $rowDt['OST_DOC_DT'];
			?>
		</td>
		<td align = "left">
			<?php
				if (!empty($rowDt['SUPP_NAME'])){
					echo $rowDt['SUPP_NAME'];
				}
			?>
		</td>
		<td align="left">
			<?php
				$OST_KEY_NO_PRM = fGet_OstKeyNo($conn,$rowDt['PAYMENT_NO'],$DATE_FROM_S,$DATE_TO_S);
				echo fGetPO2($conn,$OST_KEY_NO_PRM);
			?>
		</td>
		<td align="center">
<?php
		if ($OST_KEY_NO_PRM!==""){
			$prsDtl = "Process('VIEW_DTL $OST_KEY_NO_PRM ')";

?>
			<input type="button" value="DETAIL" class="BUTTON btn_add" onclick="<?php echo $prsDtl; ?>" >
<?php
		}
?>
		</td>
	</tr>
<?php
	}
?>
	</table>
<?php
}
?>
