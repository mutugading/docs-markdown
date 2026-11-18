<table style="width:100%" >
  <tr>
  		<td align="center" colspan="<?php echo $totDisp; ?>">
<?php
  //Query Type Folder
  $sqlGetData = "select 'ADMIN' GET_DATA from MGTAPPS.EFILL_ADMIN_USER where EAU_USER_ID = '$USER_NAME'";
  $EFILL_ADMIN = getData($conn,$sqlGetData);

  if ($EFILL_ADMIN==="ADMIN"){
  $sqlList = "select 	ETD_SYS_ID
        from    EFILL_TYPE_DATA a";
  } else {
  $sqlList = "select 	ETD_SYS_ID
        from    EFILL_TYPE_DATA a
          ,EFILL_USER_ACCESS b
        where a.ETD_SYS_ID = b.EUA_ETD_SYS_ID
        and EUA_USER_ID = '$USER_NAME'";
  }
  //Query Type Folder
?>
  		Type Data  :
  			<select name="ETD_SYS_ID" id="ETD_SYS_ID" onchange="showData()">
  					<option value="NULL">NULL</option>
  <?php
      $rsDataList = oci_parse($conn,$sqlList);
  	oci_execute ($rsDataList);
  	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
  ?>
  				<option value="<?php echo $rowDataList['ETD_SYS_ID']; ?>"
  					<?php if($ETD_SYS_ID_S===$rowDataList['ETD_SYS_ID']){ echo "selected";}?>
  					>
  						<?php echo $rowDataList['ETD_SYS_ID']; ?>
  				</option>
  <?php
  	}
  ?>
  			</select>
  <?php
  	if ($ETD_SYS_ID_S!==""){
  		$stsView = "Y";
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
  		// end get Deep Folder
  		$x = 1;
  		//start loop
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
  		?>		Folder
  				<select name="FOLDER_VAL_1" id="FOLDER_VAL_1" onchange="showData()">
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
  			if ($FOLDER_VAL_1_S === "NULL"){$stsView="N";}
  			}
  		//end select 1
  		//condition
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
  		//end condition
  		//Folder 2
  		if ($x==2){
  		if ($FOLDER_VAL_2_S === "NULL"){$stsView="N";}
  		?>
  		<select name="FOLDER_VAL_2" id="FOLDER_VAL_2" onchange="showData()">
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
  		//end Folder 2

  	//select 3
  	if ($x===3){
  		if ($FOLDER_VAL_3_S === "NULL"){$stsView="N";}
  	?>
  		<select name="FOLDER_VAL_3" id="FOLDER_VAL_3" onchange="showData()">
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
  		if ($FOLDER_VAL_4_S === "NULL"){$stsView="N";}
  	?>
  		<select name="FOLDER_VAL_4" id="FOLDER_VAL_4" onchange="showData()">
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
  		if ($FOLDER_VAL_5_S === "NULL"){$stsView="N";}
  	?>
  		<select name="FOLDER_VAL_5" id="FOLDER_VAL_5" onchange="showData()">
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
  		//end loop

  		//View Data
  		if ($ETD_SYS_ID_S==="NULL")	{
  			$stsView = "N";
  		}
  		if ($deepFolder===2 && $FOLDER_VAL_2_S === "NULL")	{
  			$stsView="N";
  		}
  		if ($deepFolder===3 && $FOLDER_VAL_3_S === "NULL")	{
  			$stsView="N";
  		}
  		if ($deepFolder===4 && $FOLDER_VAL_4_S === "NULL")	{
  			$stsView="N";
  		}
  		if ($deepFolder===5 && $FOLDER_VAL_5_S === "NULL")	{
  			$stsView="N";
  		}

      //echo "stsView $stsView<br>";
  		if ($stsView==="Y"){
  ?>
  		<tr>
  			<td align="center">
  			Search Data
  			<input 	type="text" id="FIND_DATA" name="FIND_DATA" value="<?php echo $FIND_DATA_S; ?>"
  					onkeypress="Javascript: if (event.keyCode==13) showData();"
  			>
  			<input type="button" id="CLEAR" name="FIND" value="FIND" class="BUTTON btn_view" onclick="showData()" >
  			<input type="button" id="CLEAR" name="CLEAR" value="CLEAR" class="BUTTON btn_process" onclick="Process('CLEAR')" >
  			</td>
  		</tr>
  	</table>
  	<table style="width:100%" border="1">
  <?php

  		$folderDest = "Doc_Folder/$FOLDER_VAL_1_V";
  		if ($FOLDER_VAL_2_S !== ""){
  			$folderDest = "$folderDest/$FOLDER_VAL_2_V";
  		}
  		if ($FOLDER_VAL_3_S !== ""){
  			$folderDest = "$folderDest/$FOLDER_VAL_3_V";
  		}
  		if ($FOLDER_VAL_4_S !== ""){
  			$folderDest = "$folderDest/$FOLDER_VAL_4_V";
  		}
  		if ($FOLDER_VAL_5_S !== ""){
  			$folderDest = "$folderDest/$FOLDER_VAL_5_V";
  		}
  			//$folderDest = '"D:\XAMPP\htdocs\webapps\Doc_Folder\PDN\2021\"';
  			$folderLink = "D:/XAMPP/htdocs/webapps/$folderDest";

  			//echo "folder Destination $folderLink </br>";
  			$fileData = "";
  			$recDisp = 0;
  			$sqlGetData = 	"select nvl(ETD_TOT_DISPLAY,0) GET_DATA from EFILL_TYPE_DATA
  							 where ETD_SYS_ID = '$ETD_SYS_ID_S'";
  			$totDisp = getData($conn,$sqlGetData);
  			if ($totDisp === '0' ){
  				$totDisp =6;
  			} else {
  				$totDisp =(int)$totDisp;
  			}
  			$withDisp = 100/$totDisp;
  			//echo "ETD_SYS_ID_S $ETD_SYS_ID_S totDisp $totDisp";
  			$link="https://mgtapps.mutugading.com:4433/";
  			$stsFile = "";
  			$printLink = "";
  			//echo "$folderLink</br>";
  			if (is_dir($folderLink)){
  	    		if ($dh = opendir($folderLink)){
  	    			$noFile = 0;
  	    			$fileDt[]="";
  	    			$color = "";
  	    			while (($file = readdir($dh)) !== false){
  	    				$stsFile = "Y";
  	    				$printLink = "";
  						if (strpos(strtoupper($file),".PDF") === FALSE) {
  							$stsFile = "N";
  						}
  						if ($stsFile === "Y")	{
  							if ($FIND_DATA_S!==""){
  								if (strpos(strtoupper($file),strtoupper($FIND_DATA_S)) === FALSE) {
  									$stsFile = "N";
  								}
  							}
  						}
  						if ($stsFile==="Y") {
  							$noFile=$noFile+1;
  							$fileDt[$noFile] = $file;
  		    				$recDisp++;
  		    				if ($recDisp===1){
  		    					$color = "BUTTON btn_process";
  		    				}
  		    				if ($recDisp===2){$color = "BUTTON btn_add";}
  							if ($recDisp===3){$color = "BUTTON btn_delete";}
  							if ($recDisp===4){$color = "BUTTON btn_view";}
  							if ($recDisp===5){$color = "BUTTON btn_example";}
  							if ($recDisp===6){$color = "BUTTON btn_update";}
  							if ($recDisp===7){$color = "BUTTON btn_back";}

  							if ($recDisp<=$totDisp){
  								if ($recDisp==1){
  									?>
  									<tr>
  									<?php
  								}
  								$printLink = "$folderDest/$file";
  														?>
  		<td style="width:<?php echo $withDisp; ?>%" align="center">
        <?php $evntDel="delFile('$noFile','$file','$printLink')" ?>
  			<input 	type="button" value="<?php echo "$noFile. $file"; ?> "
  					onclick="<?php echo "Process('VIEW $printLink')"; ?>" class="<?php echo "$color"; ?>" >
        <button type="button" data-toggle="modal" data-target="#myModal" onclick="<?php echo $evntDel; ?>" class="BUTTON btn_delete">Del</button>
  		</td>
  <?php
  							}
  							if ($recDisp===$totDisp){
  								$recDisp=0;
  								?>
  								</tr>
  								<?php
  							}
  						}
  						if ($printLink!==""){
  							$printLink = "";
  						}
  					}
  			    	closedir($dh);
  				}else {
  					echo "NO Data";
  				}
  			} else {
  				echo "Folder invalid";
  			}
  		}
  		//end View Data
  	}
  ?>

  		</td>
  	</tr>
</table>
