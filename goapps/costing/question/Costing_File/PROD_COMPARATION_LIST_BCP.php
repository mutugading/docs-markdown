<div class="list" id="list">
	<table border="0" style="width:100%;">
		<tr>
			<td <?php echo getStyle("13","center"); ?>>
				Type :
				<?php
						$sqlSlct = " 	SELECT DISTINCT CYL_TYPE
													from mgtapps.cst_yarn_left
													where CYL_IS_VALID_PRD = 'Y' ";
						$rsSlct = oci_parse($conn,$sqlSlct);
						oci_execute ($rsSlct);
				?>
						<select name="CYL_TYPE" id="CYL_TYPE" onchange="filterDt()" <?php echo $styleSlct; ?> >
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
		</tr>
	</table>
	<table border="1" style="width:100%;">
		<tr>
			<td <?php echo getStyle($font,"center"); ?>>
				Left No
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				Type
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				Product Name
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				Shade Code
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				Shade Name
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				Item Code
			</td>
		</tr>
	<?php
		// foreach($items as $item) {
		$vWhere="";
		if($CYL_TYPE_S!==""){
			$vWhere=" and a.cyl_type ='$CYL_TYPE_S' ";
		}
		if($FIND_DATA_S!==""){
			$vWhere=" and	upper(CYL_LEFT_NO||CYL_TYPE||CYL_NAME||CYL_SYS_ID||CYL_SHADE_CODE||CYL_SHADE_NAME||CYL_ITEM_CODE) like '%||upper('$FIND_DATA_S')||%'";
		}

		$SqlView = "
								select *
								from
									(
									select distinct a.*
									from 	cst_yarn_left a
										 		,cst_lvl_left_prod b
									WHERE a.CYL_SYS_ID=b.CLLP_CYL_SYS_ID_REFF $vWhere
									) where rownum<=5
								";

		if ($USER_NAME === "1949") {echo "$SqlView</br>";	}
		$rsView = oci_parse($conn,$SqlView);
		oci_execute ($rsView);
		while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
			$CYL_LEFT_NO = $rowRsView['CYL_LEFT_NO'] ?? "";
			$CYL_TYPE = $rowRsView['CYL_TYPE'] ?? "";
			$CYL_NAME = $rowRsView['CYL_NAME'] ?? "";
			$CYL_SYS_ID = $rowRsView['CYL_SYS_ID'] ?? "";
			$CYL_SHADE_CODE = $rowRsView['CYL_SHADE_CODE'] ?? "";
			$CYL_SHADE_NAME = $rowRsView['CYL_SHADE_NAME'] ?? "";
			$CYL_ITEM_CODE = $rowRsView['CYL_ITEM_CODE'] ?? "";
	?>
		<tr>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_LEFT_NO; ?>
				</div>
			</td>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_TYPE; ?>
				</div>
			</td>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_NAME; ?>
				</div>
			</td>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_SHADE_CODE; ?>
				</div>
			</td>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_SHADE_NAME; ?>
				</div>
			</td>
			<td <?php echo getStyle($font,"center"); ?> >
				<div class="list-item"
						 draggable="true"
						 data-id="<?php echo $CYL_SYS_ID; ?>">
					<?php echo $CYL_ITEM_CODE; ?>
				</div>
			</td>
		</tr>
	<?php
		}
	?>
</table>
</div>
