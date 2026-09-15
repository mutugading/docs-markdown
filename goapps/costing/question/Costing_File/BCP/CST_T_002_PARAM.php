<?php
	$WHERE_PARAM = "";
	if ($CYL_SHADE_NAME_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' "; }
	if ($DENIER_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYCC_TOP_13_DATA_VALUE = '$DENIER_S' "; }
	if ($FILAMENT_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' "; }
	if ($INTERMINGLING_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' "; }
	if ($HEATSET_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' "; }
	if ($CROSS_SECTION_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' "; }
	if ($CMY_LUSTURE_S!=="NULL") { $WHERE_PARAM = " $WHERE_PARAM and CMY_LUSTURE = '$CMY_LUSTURE_S' "; }
?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:100%">
	<tr>
<!-- Shade Name - Code -->
		<td align="center">
			<b>Shade Name</b> </br>
<?php
		$sqlSlct = " SELECT DISTINCT CYL_SHADE_NAME from mgtapps.cst_yarn_left WHERE CYL_IS_VALID_PRD = 'Y' order by CYL_SHADE_NAME ";
		//echo $sqlSlct;
		$rsSlct = oci_parse($conn,$sqlSlct);
		oci_execute ($rsSlct);
?>
		<select name="CYL_SHADE_NAME" id="CYL_SHADE_NAME" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?>
<?php
		//if ($CYL_SHADE_CODE_S !== "NULL" || $ShadeCodeName!==""){echo "disabled"; }
?>
		>
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CYL_SHADE_NAME']; ?>"
				<?php

					if ($SHADE_CHOICE!=="CYL_SHADE_CODE"){
						if ($CYL_SHADE_NAME_S !== "NULL"){
							if ($CYL_SHADE_NAME_S=== $rowRsSlct['CYL_SHADE_NAME']) {echo "selected"; }
						}
					} else {
						if ($ShadeCodeName!== "") {
							if ($ShadeCodeName=== $rowRsSlct['CYL_SHADE_NAME']) {
								echo "selected";
								$CYL_SHADE_NAME_S = $rowRsSlct['CYL_SHADE_NAME'];
							}
						}
					}
				?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['CYL_SHADE_NAME']; ?></font></b>
			</option>
<?php
		}
?>
		</select>

		</td>
<!-- Shade Name - Code -->
	</tr>
</table>
</div>
<?php
	//echo "SHADE_CHOICE $SHADE_CHOICE";
	$font = "12";
	if ($CYL_SHADE_NAME_S!=="NULL"){
?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:100%">
	<tr>
<!-- Left No -->
		<td <?php echo getStyle($font,"center"); ?>>
			Left No </br>
			<input type="TEXT" id="CYL_LEFT_NO" name="CYL_LEFT_NO" style="width:75px;height:20px;"
				value="<?php echo $CYL_LEFT_NO_S; ?>"
				onkeypress="Javascript: if (event.keyCode==13) Process('VIEW_DATA');"
			>&nbsp
			<button onclick="Process('VIEW_DATA')">Find</button>
		</td>
<!-- Left No -->
<!-- Product Type -->
		<td <?php echo getStyle($font,"center"); ?>>
			Product Type </br>
<?php
		//$sqlSlct = sqlSlctPrdType();
		$sqlSlct = sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"CYL_TYPE",$WHERE_PARAM)." order by CYL_TYPE ";
		//echo $sqlSlct;
		$rsSlct = oci_parse($conn,$sqlSlct);
		oci_execute ($rsSlct);
?>
		<select name="CYL_TYPE" id="CYL_TYPE" onchange="Process('VIEW_DATA')"

				<?php echo $styleSlct; ?>
		>
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CYL_TYPE']; ?>"
				<?php
					if ($CYL_TYPE_S=== $rowRsSlct['CYL_TYPE']) {echo "selected"; }
				?>

			>
				<?php echo $rowRsSlct['CYL_TYPE']; ?>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Product Type -->
<!-- Customer -->
		<td <?php echo getStyle($font,"center"); ?>>
			Customer </br>
<?php
	//$sqlSlct = sqlSlctCust($sqlData,"SELECT DISTINCT cmcd_name CUSTOMER ");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"CUSTOMER",$WHERE_PARAM);
	//echo "$sqlSlct </br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="CUSTOMER" id="CUSTOMER" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CUSTOMER']; ?>"
				<?php  if ($CUSTOMER_S=== $rowRsSlct['CUSTOMER']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['CUSTOMER']; ?></font></b>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Customer -->

<!-- Product Name -->
<?php if ($CYL_TYPE_S==="SUPERBA"){ ?>
		<td <?php echo getStyle($font,"center"); ?>>
			Product Name  </br>
<?php
		$sqlSlct =sqlSlctTypeBaseSuperba($conn,$sqlData,$CYL_TYPE_S,"CYL_PRODUCT_QUALITY",$WHERE_PARAM)." order by CYL_PRODUCT_QUALITY ";
		$rsSlct = oci_parse($conn,$sqlSlct);
		oci_execute ($rsSlct);
?>
		<select name="CYL_PRODUCT_QUALITY" id="CYL_PRODUCT_QUALITY" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?>
		>
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CYL_PRODUCT_QUALITY']; ?>"
				<?php

					if ($CYL_PRODUCT_QUALITY_S=== $rowRsSlct['CYL_PRODUCT_QUALITY']) {
						echo "selected";
						$CYL_PRODUCT_QUALITY_S = $rowRsSlct['CYL_PRODUCT_QUALITY'];
					}
				?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['CYL_PRODUCT_QUALITY']; ?></font></b>
			</option>
<?php
		}
?>
		</select>

		</td>
<?php } ?>
<!-- Product Name -->

<!-- Denier -->
		<td  <?php echo getStyle($font,"center"); ?>>
			Denier </br>
<?php
	//$sqlSlct = sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,"DENIER");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"DENIER",$WHERE_PARAM)." order by to_number(DENIER) ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="DENIER" id="DENIER" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['DENIER']; ?>"
				<?php  if ($DENIER_S=== $rowRsSlct['DENIER']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['DENIER']; ?></font></b>
			</option>
<?php
		}
?>
</select>
		</td>
<!-- Denier -->
<!-- Filament -->
		<td  <?php echo getStyle($font,"center"); ?>>
			Filament </br>
<?php
	//$sqlSlct = sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,"FILAMENT");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"FILAMENT",$WHERE_PARAM)." order by to_number(FILAMENT) ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="FILAMENT" id="FILAMENT" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['FILAMENT']; ?>"
				<?php  if ($FILAMENT_S=== $rowRsSlct['FILAMENT']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['FILAMENT']; ?></font></b>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Filament -->
<!-- Intermingling -->
		<td <?php echo getStyle($font,"center"); ?>>
			Intermingling </br>
<?php
	//$sqlSlct = sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,"INTERMINGLING");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"INTERMINGLING",$WHERE_PARAM)." order by INTERMINGLING ";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="INTERMINGLING" id="INTERMINGLING" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['INTERMINGLING']; ?>"
				<?php  if ($INTERMINGLING_S=== $rowRsSlct['INTERMINGLING']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['INTERMINGLING']; ?></font></b>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Intermingling -->
<!-- Heatset -->
		<td <?php echo getStyle($font,"center"); ?>>
			Heatset </br>
<?php
	//$sqlSlct = sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,"HEATSET");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"HEATSET",$WHERE_PARAM)." order by HEATSET ";
	//echo "heatset $HEATSET_S";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
		<select name="HEATSET" id="HEATSET" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
			echo $rowRsSlct['heatset'];
?>
			<option value="<?php echo $rowRsSlct['HEATSET']; ?>"
				<?php  if ($HEATSET_S=== $rowRsSlct['HEATSET']) {echo "selected"; } ?>
			>
				<?php if(!empty($rowRsSlct['HEATSET'])) {echo $rowRsSlct['HEATSET'];} ?>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Heatset -->
<!-- Cross Section -->
		<td <?php echo getStyle($font,"center"); ?>>
			Cross Section </br>
<?php
	//$sqlSlct = sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,"CROSS_SECTION");
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"CROSS_SECTION",$WHERE_PARAM)." order by CROSS_SECTION ";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="CROSS_SECTION" id="CROSS_SECTION" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CROSS_SECTION']; ?>"
				<?php  if ($CROSS_SECTION_S=== $rowRsSlct['CROSS_SECTION']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['CROSS_SECTION']; ?></font></b>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Cross Section -->
<!-- Lusture -->
		<td <?php echo getStyle($font,"center"); ?>>
			Lusture </br>
<?php
	//$sqlSlct = sqlSlctLusture();
	$sqlSlct =sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,"LUSTURE",$WHERE_PARAM);
	//echo $sqlSlct;
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
?>
<select name="CMY_LUSTURE" id="CMY_LUSTURE" onchange="Process('VIEW_DATA')" <?php echo $styleSlct; ?> >
			<option value="NULL">NULL</option>
<?php
		while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
			<option value="<?php echo $rowRsSlct['CMY_LUSTURE']; ?>"
				<?php  if ($CMY_LUSTURE_S=== $rowRsSlct['CMY_LUSTURE']) {echo "selected"; } ?>
			>
				<b><font color='red' ><?php echo $rowRsSlct['CMY_LUSTURE']; ?></font></b>
			</option>
<?php
		}
?>
		</select>
		</td>
<!-- Lusture -->
	</tr>
</table>
</div>
<?php
	}
?>
