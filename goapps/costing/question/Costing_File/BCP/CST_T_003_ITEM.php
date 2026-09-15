<table style="width:100%">
	<tr>
		<td align="center" style="width:50%">
			DENIER
<?php
	if($DENIER_S!==""){
		echo $DENIER_S;
		} else {
			$sqlSlct = sqlListData($BrwsShdCd_S,13);//Query For Denier
			//echo "sqlLstDenier $sqlSlct </br>";
			$rsSlctDen = oci_parse($conn,$sqlSlct);
			oci_execute ($rsSlctDen);
?>
		<select name="DENIER" id="DENIER">
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlctDen = oci_fetch_array ($rsSlctDen, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlctDen['CYC_DATA_VALUE']; ?>"><?php echo $rowRsSlctDen['CYC_DATA_VALUE']; ?></option>
<?php
		}
?>
		</select>
<?php
	}
?>
		</td>
		<td align="center" style="width:50%">
			FILAMENT
<?php
	if($FILAMENT_S!==""){
		echo $FILAMENT_S;
	} else {
		$sqlSlct = sqlListData($BrwsShdCd_S,16);//Query For Filament
		//echo "sqlLstFilamen $sqlSlct </br>";
		$rsSlctFlmn = oci_parse($conn,$sqlSlct);
		oci_execute ($rsSlctFlmn);
?>
		<select name="FILAMENT" id="FILAMENT">
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlctFlmn = oci_fetch_array ($rsSlctFlmn, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlctFlmn['CYC_DATA_VALUE']; ?>"><?php echo $rowRsSlctFlmn['CYC_DATA_VALUE']; ?></option>
<?php
		}
?>
		</select>
<?php
	}
?>
		</td>
	</tr>
	<tr>
		<td align="center" style="width:20%" colspan="2">
<?php
	if ($STSPRS==="FIND_DATA"){
?>
			<input type="BUTTON" value="CLEAR" class="BUTTON btn_clear" onclick="Process('CLEAR_DATA')">
<?php
	} else {
?>
			<input type="BUTTON" value="FIND" class="BUTTON btn_process" onclick="Process('FIND_DATA')">
<?php
	}
?>
		</td>
	</tr>
</table>
