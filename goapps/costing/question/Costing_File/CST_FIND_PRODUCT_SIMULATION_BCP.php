<?php
	$AX_WT_S = 0;
	if(isset($_POST['AX_WT_S'])) {
		$AX_WT_S=$_POST['AX_WT_S'];
	}
	$NO_OF_BOBBINS_S= 0;
	if(isset($_POST['NO_OF_BOBBINS_S'])) {
		$NO_OF_BOBBINS_S=$_POST['NO_OF_BOBBINS_S'];
	}
	$Delpack_name_S= "Choose Data";
	if(isset($_POST['Delpack_name_S'])) {
		$Delpack_name_S=$_POST['Delpack_name_S'];
	}

	$btnPrsSmltn = "Process('".str_replace(" PROCESS", "", $STSPRS)." PROCESS')";

	//echo "AX_WT_S $AX_WT_S : NO_OF_BOBBINS_S $NO_OF_BOBBINS_S : Delpack_name_S = $Delpack_name_S";
	if(isset($_POST['Delpack_name_S'])) {
		$Delpack_name_S=$_POST['Delpack_name_S'];
	}
	if ($Delpack_name_S===""){
		$Delpack_name_S= "Choose Data";
	}
	//echo "Delpack_name_S $Delpack_name_S</br>";
	$CMBBC_BOX_COST = 0; $CMBBC_BOBIN_COST = 0;

	$BoxWeightNet_1 = 0; $AX_WT_1 = 0; $NO_OF_BOBBINS_1 = 0;
	$bobinRate_1 = 0; $boxRate_1=0;
	$DELPACKINGCOST_1 = 0;$V1_1 = 0;$V2_1 = 0;$V3_1 = 0;$V4_1 = 0;$V5_1 = 0;

	$existingRasio1 = 0;
	$smltnType = "";
	$smltnCylLeftNo = "";
?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:95%" >
	<tr>
		<td align="center">
			PRODUCT	REFFERENCE
		</td>
	</tr>
</table>
<table style="width:100%" >
<?php
	$CYL_SYS_ID_SMLTN = (substr(str_replace(" PROCESS", "", $STSPRS),11));
	$sqlViewSmltn = str_replace("REC_NO,","",$sqlSelect)." from ($sqlData and CYL_SYS_ID = '$CYL_SYS_ID_SMLTN' )d ";
	//echo  $sqlViewSmltn;
	$rsViewSmltn = oci_parse($conn,$sqlViewSmltn);
	oci_execute ($rsViewSmltn);
	while ($rowRsSmltn = oci_fetch_array ($rsViewSmltn, OCI_BOTH)) {
		$smltnType = $rowRsSmltn['CYL_TYPE'];
		$smltnCylLeftNo = $rowRsSmltn['CYL_LEFT_NO'];
?>
	<tr>
		<td align="center"> Type : <b><?php echo$smltnType; ?></b> </td>
		<td align="center"> Shade Name : <b><?php echo $rowRsSmltn['CYL_SHADE_NAME']; ?></b> </td>
		<td align="center"> Product Name : <b><?php echo $rowRsSmltn['CMY_NAME']; ?></b></td>
		<td align="center">Left No : <b><?php echo $rowRsSmltn['CYL_LEFT_NO']; ?></b></td>
	</tr>
<?php
	}
?>
</table>
<table style="width:95%" border="1">
	<tr>
		<td align="center">Delpack name</td><!--1-->
		<td align="center">Box Rate</td><!--1 Box Rate-->
		<td align="center">Bobin Rate</td><!--1 Bobin Rate-->
		<td align="center">AX-wt</td><!--2-->
		<td align="center">No of Bobbins</td><!--3-->
		<td align="center">Box weight (net)</td><!--4-->
		<td align="center">Del-Pack cost</td><!--5-->
		<td align="center">Vol Buc-1</td><!--6-->
		<td align="center">Vol Buc-2</td><!--7-->
		<td align="center">Vol Buc-3</td><!--8-->
		<td align="center">Vol Buc-4</td><!--9-->
		<td align="center">Vol Buc-5</td><!--10-->

	</tr>
<?php
	$rsDtlSmltn = oci_parse($conn,$sqlViewSmltn);
	oci_execute ($rsDtlSmltn);
	while ($rowRsDtlSmltn = oci_fetch_array ($rsDtlSmltn, OCI_BOTH)) {
		if (!empty($rowRsDtlSmltn['FGET_DELPACKINGNAME'])){ $AX_WT_1 = $rowRsDtlSmltn['FGET_BOBBINWEIGHTAX'];}
		if (!empty($rowRsDtlSmltn['FGET_NOOFBOBBINS'])){ $NO_OF_BOBBINS_1 = $rowRsDtlSmltn['FGET_NOOFBOBBINS'];}
		if (!empty($rowRsDtlSmltn['FGET_BOXWEIGHT'])){ $BoxWeightNet_1 = $rowRsDtlSmltn['FGET_BOXWEIGHT'];}
		if (!empty($rowRsDtlSmltn['FGET_DELPACKBOBINRATE'])){ $bobinRate_1 = $rowRsDtlSmltn['FGET_DELPACKBOBINRATE'];}
		if (!empty($rowRsDtlSmltn['FGET_DELPACKBOXRATE'])){ $boxRate_1 = $rowRsDtlSmltn['FGET_DELPACKBOXRATE'];}

		if (!empty($rowRsDtlSmltn['FGET_DELPACKINGCOST'])){ $DELPACKINGCOST_1 = $rowRsDtlSmltn['FGET_DELPACKINGCOST'];}
		if (!empty($rowRsDtlSmltn['FGET_V1'])){ $V1_1 = $rowRsDtlSmltn['FGET_V1'];}
		if (!empty($rowRsDtlSmltn['FGET_V2'])){ $V2_1 = $rowRsDtlSmltn['FGET_V2'];}
		if (!empty($rowRsDtlSmltn['FGET_V3'])){ $V3_1 = $rowRsDtlSmltn['FGET_V3'];}
		if (!empty($rowRsDtlSmltn['FGET_V4'])){ $V4_1 = $rowRsDtlSmltn['FGET_V4'];}
		if (!empty($rowRsDtlSmltn['FGET_V5'])){ $V5_1 = $rowRsDtlSmltn['FGET_V5'];}
		if (!empty($rowRsDtlSmltn['CYL_LEFT_NO'])){ $CYL_LEFT_NO = $rowRsDtlSmltn['CYL_LEFT_NO'];}
?>
	<tr>
		<td align="center"><?php if (!empty($rowRsDtlSmltn['FGET_DELPACKINGNAME'])){echo $rowRsDtlSmltn['FGET_DELPACKINGNAME'];} ?></td><!--1-->
		<td align="center"><?php echo setNumber($conn,"Number",$boxRate_1,3); ?></td><!--1 Box Rate-->
		<td align="center"><?php echo setNumber($conn,"Number",$bobinRate_1,3); ?></td><!--1 Bobin Rate-->
		<td align="center"><?php echo $AX_WT_1; ?></td><!--2-->
		<td align="center"><?php echo $NO_OF_BOBBINS_1; ?></td><!--3-->
		<td align="center"><?php echo $BoxWeightNet_1; ?></td><!--4-->
		<td align="center"><?php echo setNumber($conn,"Number",$DELPACKINGCOST_1,3); ?></td><!--5-->
		<td align="center"><?php echo setNumber($conn,"Number",$V1_1,2); ?></td><!--6-->
		<td align="center"><?php echo setNumber($conn,"Number",$V2_1,2); ?></td><!--7-->
		<td align="center"><?php echo setNumber($conn,"Number",$V3_1,2); ?></td><!--8-->
		<td align="center"><?php echo setNumber($conn,"Number",$V4_1,2); ?></td><!--9-->
		<td align="center"><?php echo setNumber($conn,"Number",$V5_1,2); ?></td><!--10-->
	</tr>
<?php
	}
?>
</table>
<?php
	$GrossBoxWt1 = $AX_WT_1  * $NO_OF_BOBBINS_1;
	if ($GrossBoxWt1!==0){
		$existingRasio1 = $BoxWeightNet_1 / $GrossBoxWt1;
	}
	//echo "AX_WT_1=$AX_WT_1 : NO_OF_BOBBINS_1= $NO_OF_BOBBINS_1 : GrossBoxWt1= $GrossBoxWt1 : BoxWeightNet_1= $BoxWeightNet_1"
	//	 .": existingRasio1= $existingRasio1 </br>";
	$styleReadOnly = "style='background-color:#D8D8D8;width:200px;height:12px' readonly";
?>
&nbsp&nbsp&nbsp
<table style="width:95%" >
	<tr>
		<td align="center">
			SIMULATION PACKING
		</td>
	</tr>
</table>
<table style="width:80%" border="0">
	<tr>
		<td align="right" style="width:40%">Delpack name</td>
		<td align="left">
<?php
	$sqlSlct = "select * from mgtapps.cst_mst_box_bobin_cost order by CMBBC_TYPE";
	//echo $sqlSlct;
?>
			<select id="Delpack_name" name="Delpack_name" onchange="<?php echo $btnPrsSmltn; ?>">
				<option value="Choose Data">Choose Data</option>
<?php
    $rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
				<option value="<?php echo $rowRsSlct['CMBBC_TYPE']; ?>"
					<?php
						if ( $rowRsSlct['CMBBC_TYPE'] === $Delpack_name_S ){ echo "selected"; }
					?>
				><?php echo $rowRsSlct['CMBBC_TYPE']; ?>

				</option>
<?php
	}
?>
		</select>
		</td>
	</tr>
<?php
	if ($Delpack_name_S!=="Choose Data"){
	$sqlSlct = "select CMBBC_BOX_COST,CMBBC_BOBIN_COST from mgtapps.cst_mst_box_bobin_cost where CMBBC_TYPE = '$Delpack_name_S' ";
	$rsSlct = oci_parse($conn,$sqlSlct);
	//echo $sqlSlct;
	oci_execute ($rsSlct);

	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if (!empty($rowRsSlct['CMBBC_BOX_COST'])){
			$CMBBC_BOX_COST = setNumber($conn,"Number",$rowRsSlct['CMBBC_BOX_COST'],4);
		}
		if (!empty($rowRsSlct['CMBBC_BOBIN_COST'])){
			$CMBBC_BOBIN_COST = setNumber($conn,"Number",$rowRsSlct['CMBBC_BOBIN_COST'],4);
		}
	}
?>
	<tr>
		<td align='right'>BOX Rate</td>
		<td align='left'>
			<input type="text" name="CMBBC_BOX_COST" id="CMBBC_BOX_COST" value="<?php echo $CMBBC_BOX_COST;?>" align="right"
				<?php echo $styleReadOnly;?>
			>
		</td>
	</tr>
	<tr>
		<td align='right'>BOBIN Rate</td>
		<td align='left'>
			<input type="text" name="CMBBC_BOBIN_COST" id="CMBBC_BOBIN_COST" value="<?php echo $CMBBC_BOBIN_COST;?>" align="right"
			<?php echo $styleReadOnly;?> >
		</td>
	</tr>
	<tr>
		<td align="right">AX-wt</td>
		<td>
			<input type="Number" id="AX_WT" name="AX_WT" value="<?php echo $AX_WT_S; ?>">
		</td>
	</tr>
	<tr>
		<td align="right">No of Bobbins</td>
		<td>
			<input type="Number" id="NO_OF_BOBBINS" name="NO_OF_BOBBINS" value="<?php echo $NO_OF_BOBBINS_S; ?>">
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
			<input type="button" value="Process" class="BUTTON btn_process" onclick="<?php echo $btnPrsSmltn; ?>">
		</td>
	</tr>
</table>
<table style="width:80%" border="1">
<?php
	if ($AX_WT_S!=="" && $NO_OF_BOBBINS_S !=="") {
?>
<?php
	$AX_WT_2 	 =  $AX_WT_S; $NO_OF_BOBBINS_2 = $NO_OF_BOBBINS_S;

	if ($AX_WT_2===""){$AX_WT_2=0;} if ($NO_OF_BOBBINS_2===""){$NO_OF_BOBBINS_2=0;}
	//echo "AX_WT_2 $AX_WT_2 * NO_OF_BOBBINS_2 $NO_OF_BOBBINS_2</br>";
	$GrossBoxWt2 = $AX_WT_2 * $NO_OF_BOBBINS_2;
	$BoxWeightNet_2 = $existingRasio1 * $GrossBoxWt2;

	//echo "BoxWeightNet_2 $BoxWeightNet_2 * GrossBoxWt2 $GrossBoxWt2</br>";
	if ($GrossBoxWt2!==0){
		$existingRasio2 = $BoxWeightNet_2 / $GrossBoxWt2;
	} else $existingRasio2 = 0;

	$boxRate_2 = $CMBBC_BOX_COST; $bobinRate_2 = $CMBBC_BOBIN_COST;
	//echo "AX_WT_2= $AX_WT_2 : NO_OF_BOBBINS_2= $NO_OF_BOBBINS_2 : GrossBoxWt2= $GrossBoxWt2:BoxWeightNet_2= $BoxWeightNet_2"
	//	  .":existingRasio2= $existingRasio2:boxRate_2= $boxRate_2:bobinRate_2= $bobinRate_2</br>";

	if ($BoxWeightNet_1!==0){
		$DelPackCost_1 = ($boxRate_1+($bobinRate_1*$NO_OF_BOBBINS_1))/$BoxWeightNet_1;
	}	else {$DelPackCost_1 =0;}

	if($BoxWeightNet_2!==0){
		$DelPackCost_2 = ($boxRate_2+($bobinRate_2*$NO_OF_BOBBINS_2))/$BoxWeightNet_2;
	} else {$DelPackCost_2 = 0;}

	//$IncrmntCost = $DelPackCost_1 + $DelPackCost_2;
	if ($DelPackCost_1 === $DelPackCost_2){
		$IncrmntCost = 0;
	} else {
		$IncrmntCost = $DelPackCost_2 - $DelPackCost_1;
	}
	$V1_2 = $V1_1 + $IncrmntCost;$V2_2 = $V2_1 + $IncrmntCost;$V3_2 = $V3_1 + $IncrmntCost;$V4_2 = $V4_1 + $IncrmntCost;$V5_2 = $V5_1 + $IncrmntCost;
?>
	<tr>
		<td align="center">Box weight (net)</td>
		<td align="center">Del-Pack cost</td>
		<td align="center">Incre mental Cost</td>
		<td align="center">Vol Buc-1</td>
		<td align="center">Vol Buc-2</td>
		<td align="center">Vol Buc-3</td>
		<td align="center">Vol Buc-4</td>
		<td align="center">Vol Buc-5</td>
	</tr>
	<tr>
		<td align="center"> <?php echo $BoxWeightNet_2;?></td>
		<td align="center"> <?php  echo setNumber($conn,"Number",$DelPackCost_2,3); ?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$IncrmntCost,3); ?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$V1_2,2); ?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$V2_2,2); ?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$V3_2,2); ?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$V4_2,2);?> </td>
		<td align="center"> <?php echo setNumber($conn,"Number",$V5_2,2); ?> </td>
	</tr>
<?php
		}
	}
?>
</table>
</br>
<?php if ($smltnType==="PTY") {
	include("CST_FIND_PRODUCT_PTY_SMLTN.php");
}?>
</br>
<table style="width:100%">
	<tr>
		<td align="center">
			<input type="button" value="Clear" class="BUTTON btn_back" onclick="Process('CLEAR_DATA')">
		</td>
	</tr>
</table>
</div>
