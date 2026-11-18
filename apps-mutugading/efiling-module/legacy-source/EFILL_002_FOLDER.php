<?php

	$btnMove = "TRUE";
	// get Deep Folder
	$sqlGetData = 	"SELECT MAX (lvl) GET_DATA
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



 ?>
		</td>
	</tr>
