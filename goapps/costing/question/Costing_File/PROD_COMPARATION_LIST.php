<div class="list" id="list">
	<table border="0" style="width:100%;">
		<tr>
			<td <?php echo getStyle("13","left","70"); ?>>
				Left No : <input type="text" id="FIND_LEFT_NO" style="width:100px; height:20px;" onchange="filterDt()">
				&nbsp&nbspType :
				<?php
						$sqlSlct = " 	SELECT DISTINCT CYL_TYPE
													from mgtapps.cst_yarn_left
													where CYL_IS_VALID_PRD = 'Y' ";
						$rsSlct = oci_parse($conn,$sqlSlct);
						oci_execute ($rsSlct);
				?>
						<select id="FIND_TYPE" onchange="filterDt()" <?php echo $styleSlct; ?> >
							<option value="NULL">NULL</option>
				<?php
						while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
				?>
							<option value="<?php echo $rowRsSlct['CYL_TYPE']; ?>"
								<?php if ($CYL_TYPE_S=== $rowRsSlct['CYL_TYPE']) {echo "selected"; } ?>
							>
								<?php echo $rowRsSlct['CYL_TYPE']; ?>
							</option>
				<?php
						}
				?>
						</select>
						&nbsp&nbsp
						Find : <input type="text" id="FIND_DATA" style="width:300px; height:20px;" onchange="filterDt()">
			</td>
			<td <?php echo getStyle("13","right"); ?>>
				<input type="button" id="FIRSTREC" value="<<" onclick="firstDt()" >
				<input type="button" id="PREVREC" value="<" onclick="prevDt()" >
				<input type="number" id="PAGE_NO" style="width: 40px;text-align: center"  value="<?php echo $PAGE_NO_S;?>"
						onkeypress="Javascript: if (event.keyCode==13) filterDt();"
						onchange="filterDt()"
				>&nbsp:&nbsp
				<input type="number" id="LAST_PAGE_NO" style="width: 40px;text-align: center"  value="<?php echo $PAGE_NO_S;?>"
						readonly
				>
				<input type="button" id="NEXTREC" value=">" onclick="nextDt()" >
				<input type="button" id="LASTREC" value=">>" onclick="lastDt()" >
			</td>
		</tr>
	</table>
	<table border="1" style="width:100%;">
    <thead>
        <tr>
						<td <?php echo getStyle($font,"center"); ?>>No</td>
            <td <?php echo getStyle($font,"center"); ?>>Left No</td>
            <td <?php echo getStyle($font,"center"); ?>>Type</td>
            <td <?php echo getStyle($font,"center"); ?>>Product Name</td>
            <td <?php echo getStyle($font,"center"); ?>>Shade Code</td>
            <td <?php echo getStyle($font,"center"); ?>>Shade Name</td>
            <td <?php echo getStyle($font,"center"); ?>>Item Code</td>
        </tr>
    </thead>
    <tbody id="tableBody">
        <!-- DATA AKAN DIISI VIA JS -->
    </tbody>
	</table>
</div>
