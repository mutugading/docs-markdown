
<table style="width:80%" border="1" align="center">
	<tr>
		<td style="width:10%" align="center">No</td>
		<td style="width:20%" align="center">Bill No</td>
		<td align="center">Suppplier Code</td>
		<td align="center">Name</td>
	</tr>
<?php
	$dtl_trx_no = substr($STSPRS,9);
	//echo "No Trx $dtl_trx_no </br>";

	$sqlViewDtl = "select BH_TXN_CODE TRX_FOLDER,to_char(BH_DT,'YYYY') TRX_YY,BH_NO TRX_NO,d.* from ($sqlViewDtl and GR_REF_TXN_CODE||'-'||GR_REF_NO = '$dtl_trx_no' ) d ";
	//echo "sqlViewDtl $sqlViewDtl</br>";

	$rsDtDtl = oci_parse($conn,$sqlViewDtl);
	oci_execute ($rsDtDtl);
	$PaymentNo = "";
	$RecNoDtl = 0;
	while ($rowDtDtl = oci_fetch_array ($rsDtDtl, OCI_BOTH)) {
		$RecNoDtl++;
?>
	<tr>
		<td>
			<?php echo $RecNoDtl;?>
		</td>
		<td align="center">
		<?php
			$linkData 	= "D:/XAMPP/htdocs/webapps/Doc_Folder/";
			$linkFileDtl	= $linkData.$rowDtDtl['TRX_FOLDER']."/".$rowDtDtl['TRX_YY']."/".$rowDtDtl['TRX_FOLDER']."-".$rowDtDtl['TRX_NO'].".PDF";
			//echo "linkFile $linkFileDtl </br>";
			$lstDtl 	= "Process('VIEW_DTL ".$rowDtDtl['TRX_FOLDER']."-".$rowDtDtl['TRX_NO']."')";
			if (file_exists($linkFileDtl)){
				$linkData 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
				$linkFileDtl	= $linkData.$rowDtDtl['TRX_FOLDER']."/".$rowDtDtl['TRX_YY']."/".$rowDtDtl['TRX_FOLDER']."-".$rowDtDtl['TRX_NO'].".PDF";
				$lstDataDtl 	= "Process('DTL_OPEN_FILE $linkFileDtl')";
				$linkFileName	= $rowDtDtl['TRX_FOLDER']."/".$rowDtDtl['TRX_YY']."/".$rowDtDtl['TRX_FOLDER']."-".$rowDtDtl['TRX_NO'].".PDF";
		?>
		<input type="button" value="<?php echo $rowDtDtl['TRX_FOLDER']."-".$rowDtDtl['TRX_NO']; ?>" onclick="<?php echo $lstDataDtl; ?>" class="BUTTON btn_process">
		<?php
			}else{
				echo "<font color = 'red'  size = '2.5'><b>".$rowDtDtl['BILL_NO']." NA</b></font>";
			}
		?>
		</td>
		<td><?php echo $rowDtDtl['BH_SUPP_CODE'];?></td>
		<td><?php echo $rowDtDtl['SUPP_NAME'];?></td>
	</tr>
<?php
	}
?>
</table>
