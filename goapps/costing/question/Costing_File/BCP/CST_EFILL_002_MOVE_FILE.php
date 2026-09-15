<!-- DEFINE LAYOUT -->
<br><br>
<table style="width:100%" border="1">
	<tr>
		<td style="text-align: left;" >
			<b>MOVE FILE INTO</b>
		</td>
	</tr>
<!-- Type Data -->
<?php
	//get access folder
	//User is ADMIN
	$sqlGetData = "select 'ADMIN' GET_DATA
							 from MGTAPPS.EFILL_ADMIN_USER where EAU_USER_ID = '$USER_NAME'
							 ";
	$EFILL_ADMIN = getData($conn,$sqlGetData);

	if ($EFILL_ADMIN==="ADMIN"){
		$sqlList = "select 	ETD_SYS_ID from    EFILL_TYPE_DATA a where 1=1 ";
	} else {
	$sqlList = "
			select 	ETD_SYS_ID
				from    EFILL_TYPE_DATA a
					,EFILL_USER_ACCESS b
				where a.ETD_SYS_ID = b.EUA_ETD_SYS_ID
				and EUA_USER_ID = '$USER_NAME'";
	}
	$sqlList = "$sqlList and ETD_SYS_ID = 'CARPET PRODUCT'";
	//echo $sqlList;
?>
	<tr>
		<td align="center" >
		Type Data  :
			<select name="ETD_SYS_ID" id="ETD_SYS_ID" onchange="Process('')">
					<option value="NULL">NULL</option>
<?php
	  $rsDataList = oci_parse($conn,$sqlList);
		oci_execute ($rsDataList);
		while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
			<option value="<?php echo $rowDataList['ETD_SYS_ID']; ?>"
				<?php if($ETD_SYS_ID_S==$rowDataList['ETD_SYS_ID']){ echo "selected";}?>
				>
					<?php echo $rowDataList['ETD_SYS_ID']; ?>
			</option>
<?php
	}
?>
			</select>
		</td>
	</tr>
<?php
$StsFound = "N";
if ($ETD_SYS_ID_S!=="NULL"){
	$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc/$USER_NAME";
	//$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc";
	//echo "dir $dir</br>";
	if (is_dir($dir)){
	    if ($dh = opendir($dir)){
	    	//$recDt=-1;
			while (($file = readdir($dh)) !== false){
				$sql = "select nvl(MGTAPPS.pkg_effil_prs.fGetFormatFile('$ETD_SYS_ID_S'),'NULL') GET_DATA from dual";
				//echo "$sql </br>";
				$ETD_FOMAT_FILE = getData($conn,$sql);
				//echo "file $file ETD_FOMAT_FILE $ETD_FOMAT_FILE FIND_DATA_S $FIND_DATA_S posisi ".strpos($ETD_FOMAT_FILE,$file)." </br>";
				//if (strpos($file,$ETD_SYS_ID_S) !== FALSE) {
				if ($ETD_FOMAT_FILE !== "NULL"){

					if (strpos($file,$ETD_FOMAT_FILE) !== FALSE) {
						//echo "KETEMU</br>";
						if ($FIND_DATA_S!==""){
							if (strpos($file,$FIND_DATA_S) !== FALSE) {
								$StsFound = "Y";
							}
						} else {
							$StsFound = "Y";
						}
					}

				}
			}
    closedir($dh);
		}
	}

//folder move
if ($ETD_SYS_ID_S!=="NULL"){
	$btnMove = "TRUE";
	// get Deep Folder
	$sqlGetData =
	"SELECT MAX (lvl) GET_DATA
						 FROM (	SELECT *
    				 		FROM (
										select -1,level lvl,EDF_FOLDER MENU_NAME,null,EDF_SYS_ID MENU_ID
										from EFILL_DATA_FOLDER
										where EDF_ETD_SYS_ID = '$ETD_SYS_ID_S'
										connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
										start with EDF_SYS_ID_PARENT is null
										order by EDF_SYS_ID
                    )
    					  )";
	$deepFolder = getData($conn,$sqlGetData);
	//echo "$sqlGetData </br>";
	//echo "deepFolder $deepFolder";
	// end get Deep Folder
	$x = 1;
	$FOLDER_VAl_2_S= "";
	if(isset($_POST['FOLDER_VAl_2_S'])) {
	 	$FOLDER_VAl_2_S=$_POST['FOLDER_VAl_2_S'];
	}
?>

	<tr>
		<td align="center" colspan="<?php echo $totDisp; ?>">
			Select Folder Destination
<?php
	while($x <= $deepFolder ) {
	//select 1
	if ($x===1){
		$qryFldr =
			"SELECT lvl,EDF_FOLDER,EDF_FOLDER_ID
			  FROM (

					select -1,level lvl,EDF_FOLDER,null,EDF_SYS_ID EDF_FOLDER_ID,EDF_SYS_ID_PARENT EDF_FOLDER_PARENT
					from EFILL_DATA_FOLDER
					where EDF_ETD_SYS_ID ='$ETD_SYS_ID_S'
					connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
					start with EDF_SYS_ID_PARENT is null
					order by EDF_SYS_ID

             )
			where lvl = $x";
		//echo "$qryFldr </br>";
			//echo "FOLDER_VAL_1_S $FOLDER_VAL_1_S </br>";
		if($FOLDER_VAL_1_S==="NULL"){$btnMove="FALSE";}
	?>
		<select name="FOLDER_VAL_1" id="FOLDER_VAL_1" onchange="Process('')">
			<option value="NULL" >NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_1_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 1

	//select 2
	if ($x>1){
		$folderWhr = "";
		if ($x==2){
			$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_1_S' ";
		}else if ($x==3){
			$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_2_S' ";
		}else if ($x==4){
			$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_3_S' ";
		}else if ($x==5){
			$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_4_S' ";
		}
		$qryFldr =
			"SELECT lvl,EDF_FOLDER,EDF_FOLDER_ID,EDF_FOLDER_PARENT
			  FROM (SELECT *
			          FROM (
							select -1,level lvl,EDF_FOLDER,null,EDF_SYS_ID EDF_FOLDER_ID,EDF_SYS_ID_PARENT EDF_FOLDER_PARENT
							from EFILL_DATA_FOLDER
							where EDF_ETD_SYS_ID ='$ETD_SYS_ID_S'
							connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
							start with EDF_SYS_ID_PARENT is null
							order by EDF_SYS_ID
			                )
			        )
			where lvl = $x
			$folderWhr ";
		}
	if ($x==2){
		//echo "$qryFldr </br>";
		if($FOLDER_VAL_2_S==="NULL"){$btnMove="FALSE";}
	?>
		<select name="FOLDER_VAL_2" id="FOLDER_VAL_2" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_2_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 2
	//select 3
	if ($x===3){
		if($FOLDER_VAL_3_S==="NULL"){$btnMove="FALSE";}
	?>
		<select name="FOLDER_VAL_3" id="FOLDER_VAL_3" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_3_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 3
	//select 4
	if ($x===4){
		if($FOLDER_VAL_4_S==="NULL"){$btnMove="FALSE";}
	?>
		<select name="FOLDER_VAL_4" id="FOLDER_VAL_4" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_4_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 4
	//select 5
	if ($x===5){
		if($FOLDER_VAL_5_S==="NULL"){$btnMove="FALSE";}
	?>
		<select name="FOLDER_VAL_5" id="FOLDER_VAL_5" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_5_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 5
	$x++;
	}

	if ($btnMove==="TRUE"){
?>
	<input type="button" id="MOVE" name="MOVE" value="MOVE" class="BUTTON btn_delete" onclick="Process('MOVE')" >
<?php
	}
}
}


//folder move
?></td>
</tr>
</table>

<!-- DEFINE LAYOUT -->
