<table style="width:50%" align="center">
<?php
$sqlDtlHdr =
		"SELECT DISTINCT OST_TRAN_CODE || '-' || OST_DOC_NO PAYMENT_NO, to_char(OST_DOC_DT,'YYYY-MM-DD') OST_DOC_DT,SUPP_NAME
           FROM mgtdat.ft_os, mgtdat.om_supplier, mgtdat.ft_payment_header
          WHERE ost_tran_code NOT IN ('FXADJ', 'FXJ')
            AND SUBSTR (ost_main_acnt_code, 1, 3) = '203'
            AND ost_drcr_flag = 'D'
            AND ost_sub_acnt_code = supp_code
            AND ost_tran_code = ph_tran_code
            AND ost_doc_no = ph_doc_no
            and OST_KEY_NO in ($OST_KEY_NO_S)";

    $rsDtlHdr = oci_parse($conn,$sqlDtlHdr);
	oci_execute ($rsDtlHdr);
	while ($rowDtlHdr = oci_fetch_array ($rsDtlHdr, OCI_BOTH)) {
?>
	<tr>
		<td style="width:50%" align="right">
			Payment No :
		</td>
		<td style="width:50%">
			<?php echo $rowDtlHdr['PAYMENT_NO']; ?>
		</td>
	</tr>
	<tr>
		<td style="width:50%" align="right">
			Date :
		</td>
		<td style="width:50%">
			<?php echo $rowDtlHdr['OST_DOC_DT']; ?>
		</td>
	</tr>
	<tr>
		<td style="width:50%" align="right">
			Supplier :
		</td>
		<td style="width:50%">
			<?php echo $rowDtlHdr['SUPP_NAME']; ?>
		</td>
	</tr>
<?php
	}
?>
</table>
<table style="width:50%" border="1" align="center">
	<tr>
		<td style="width:10%" align="center">No</td>
		<td style="width:25%" align="center">TRX No</td>
		<td style="width:25%" align="center">PO No</td>
		<td align="center">Doc Date</td>
		<td align="center">Amount</td>
	</tr>
<?php
	$dtl_trx_no = substr($STSPRS,9);
	//echo "No Trx $dtl_trx_no </br>";

	//$sqlViewDtl = $sqlViewDtl." and OM_OST_KEY_NO in ($OST_KEY_NO_S) ";
	$sqlViewDtl = fGetSqlDtl($DATE_FROM_S,$DATE_TO_S,$OST_KEY_NO_S);
	//echo "sqlViewDtl $sqlViewDtl</br>";
	$rsDtl = oci_parse($conn,$sqlViewDtl);
	oci_execute ($rsDtl);
	$PaymentNo = "";
	$RecDtl = 0;
	while ($rowDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
		$RecDtl++;
?>
	<tr>
		<td style="width:10%" align="right">
		<?php
			echo $RecDtl;
		?>
		</td>
		<td style="width:20%" align="center">
		<?php
			if (!empty($rowDtl['TRN_CODE'])){
				$linkData 	= "D:/XAMPP/htdocs/webapps/Doc_Folder/";
				$linkFileTrn	= $linkData.$rowDtl['TRN_CODE_FOLDER']."/".$rowDtl['TRN_CODE_YY']."/".$rowDtl['TRN_CODE'].".PDF";
				//echo "linkFileTrn $linkFileTrn </br>";
				$lstDtl 	= "Process('VIEW_DTL_TRN ".$rowDtl['TRN_CODE_FOLDER']."-".$rowDtl['TRN_CODE']."')";
				if (file_exists($linkFileTrn)){
					$linkData 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
					$linkFileTrn	= $linkData.$rowDtl['TRN_CODE_FOLDER']."/".$rowDtl['TRN_CODE_YY']."/".$rowDtl['TRN_CODE'].".PDF";
					$lstDataTrn 	= "Process('OPEN_FILE_TRN $linkFileTrn')";
					$linkFileTrnName = $rowDtl['TRN_CODE_FOLDER']."/".$rowDtl['TRN_CODE_YY']."/".$rowDtl['TRN_CODE'].".PDF";
?>
					<input type="button" value="<?php echo $rowDtl['TRN_CODE']; ?>" onclick="<?php echo $lstDataTrn; ?>" class="BUTTON btn_add">
<?php
				}else{
					echo "<font color = 'red'  size = '2.5'><b>".$rowDtl['TRN_CODE']." NA</b></font>";
				}
			}
		?>
		</td>
		<td style="width:20%" align="center">
		<?php
			if (!empty($rowDtl['PO_NO'])){
				$linkData 	= "D:/XAMPP/htdocs/webapps/Doc_Folder/";
				$linkFileTrn	= $linkData.$rowDtl['PO_NO_FOLDER']."/".$rowDtl['PO_NO_YY']."/".$rowDtl['PO_NO'].".PDF";
				//echo "linkFileTrn $linkFileTrn </br>";
				$lstDtl 	= "Process('OPEN_FILE_PO ".$rowDtl['PO_NO_FOLDER']."-".$rowDtl['PO_NO']."')";
				if (file_exists($linkFileTrn)){
					$linkData 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
					$linkFilePO	= $linkData.$rowDtl['PO_NO_FOLDER']."/".$rowDtl['PO_NO_YY']."/".$rowDtl['PO_NO'].".PDF";
					$lstDataPO 	= "Process('OPEN_FILE_PO $linkFilePO')";
					$linkFilePOName = $rowDtl['PO_NO_FOLDER']."/".$rowDtl['PO_NO_YY']."/".$rowDtl['PO_NO'].".PDF";
?>
					<input type="button" value="<?php echo $rowDtl['PO_NO']; ?>" onclick="<?php echo $lstDataPO; ?>" class="BUTTON btn_process">
<?php
				}else{
					echo "<font color = 'red'  size = '2.5'><b>".$rowDtl['PO_NO']." NA</b></font>";
				}
			}
		?>
		</td>
		<td align="center">
		<?php
			if (!empty($rowDtl['MATCH_DOC_DT'])){
				echo $rowDtl['MATCH_DOC_DT'];
			}
		?>
		</td>
		<td align="right">
		<?php
			if (!empty($rowDtl['MATCH_FC_AMT'])){
				echo number_format($rowDtl['MATCH_FC_AMT']);
			}
		?>
		</td>
	</tr>
<?php
	}
?>
</table>
