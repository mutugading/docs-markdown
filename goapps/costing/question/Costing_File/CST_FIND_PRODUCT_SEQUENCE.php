<?php
	$CYL_SYS_ID_PRD_SEQ = substr($STSPRS,17); $font="12";
	// echo "CYL_SYS_ID_PRD_SEQ $CYL_SYS_ID_PRD_SEQ";
	$sqlGet = "select CYL_LEFT_NO GET_DATA from CST_YARN_LEFT";
	$CYL_LEFT_NO_PRD_SEQ = getData($conn,$sqlGet);

	// $sqlPrs =
	// "
	// 	begin
	// 	 MGTAPPS.pkg_CostingProductSeq.pLoad_Data(
	// 	                'ADMIN'
	// 	                ,null
	// 	                ,$CYL_LEFT_NO_PRD_SEQ
	// 	                );
	//
	// 	 MGTAPPS.pkg_CostingProductSeq.pupd_seq($CYL_LEFT_NO_PRD_SEQ);
	// 	end;
	// ";
	//
	// //echo $sqlPrs;
	//
	// $stid = oci_parse($conn, $sqlPrs);
	// oci_execute($stid);


?>
<div class="main" style="overflow-x:auto;" align="center">
	<table style="width:70%" border="1">
		<tr>
			<td <?php echo getStyle($font,"center"); ?>> <b>ITEM CODE</b> </td>
			<td <?php echo getStyle($font,"center"); ?>> <b>NAME</b> </td>
			<td <?php echo getStyle($font,"center"); ?>> <b>PRODUCT NO</b> </td>
			<td <?php echo getStyle($font,"center"); ?>> <b>TYPE</b> </td>
			<td <?php echo getStyle($font,"center"); ?>> <b>PRODUCT INTO</b> </td>
		</tr
<?php
		$SqlView = "select CYL_ITEM_CODE, CYL_NAME, CYL_LEFT_NO, CYL_TYPE from cst_yarn_left where cyl_sys_id = $CYL_SYS_ID_PRD_SEQ";
	  $rsView = oci_parse($conn,$SqlView);
		oci_execute ($rsView);
		while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
			$CYL_ITEM_CODE = !empty($rowRsView['CYL_ITEM_CODE']) ? $rowRsView['CYL_ITEM_CODE'] : "";
			$CYL_NAME = !empty($rowRsView['CYL_NAME']) ? $rowRsView['CYL_NAME'] : "";
			$CYL_LEFT_NO = !empty($rowRsView['CYL_LEFT_NO']) ? $rowRsView['CYL_LEFT_NO'] : "";
			$CYL_TYPE = !empty($rowRsView['CYL_TYPE']) ? $rowRsView['CYL_TYPE'] : "";
?>
		<tr>
			<td <?php echo getStyle($font,"center"); ?>>
				<?php echo $CYL_ITEM_CODE; ?>
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				<?php echo $CYL_NAME; ?>
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				<?php echo $CYL_LEFT_NO; ?>
			</td>
			<td <?php echo getStyle($font,"center"); ?>>
				<?php echo $CYL_TYPE; ?>
			</td>
		</tr>
<?php
		}
?>

<?php
		$SqlView = "
			SELECT   CPSTD_RM_ITEM_CODE RM_ITEM_CODE, CPSTD_RM_ITEM_NAME RM_ITEM_NAME, CPSTD_RM_LEFT_NO RM_LEFT_NO, CPSTD_RM_CYL_TYPE RM_CYL_TYPE
							 ,CPSTD_RM_LEFT_NO_INTO RM_LEFT_NO_INTO
			    FROM cst_yarn_left,cst_product_seq_tab_hdr a, cst_product_seq_tab_dtl b
			   WHERE cyl_sys_id = $CYL_SYS_ID_PRD_SEQ
				 	AND cyl_left_no=cpsth_fg_left_no
				 	and cpsth_sys_id = cpstd_cpsth_sys_id
			ORDER BY cpsth_fg_sequence,
			         cpsth_fg_left_no,
			         cpstd_rm_sequence_real,
			         NVL (cpstd_rm_sub_sequence, 0)
			";
	  $rsView = oci_parse($conn,$SqlView);
		oci_execute ($rsView);
		while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
			$RM_ITEM_CODE = !empty($rowRsView['RM_ITEM_CODE']) ? $rowRsView['RM_ITEM_CODE'] : "";
			$RM_ITEM_NAME = !empty($rowRsView['RM_ITEM_NAME']) ? $rowRsView['RM_ITEM_NAME'] : "";
			$RM_LEFT_NO = !empty($rowRsView['RM_LEFT_NO']) ? $rowRsView['RM_LEFT_NO'] : "";
			$RM_CYL_TYPE = !empty($rowRsView['RM_CYL_TYPE']) ? $rowRsView['RM_CYL_TYPE'] : "";
			$RM_LEFT_NO_INTO = !empty($rowRsView['RM_LEFT_NO_INTO']) ? $rowRsView['RM_LEFT_NO_INTO'] : "";
?>
		<tr>
			<td <?php echo getStyle($font,"center"); ?>> <?php echo $RM_ITEM_CODE; ?> </td>
			<td <?php echo getStyle($font,"center"); ?>> <?php echo $RM_ITEM_NAME; ?> </td>
			<td <?php echo getStyle($font,"center"); ?>> <?php echo $RM_LEFT_NO; ?> </td>
			<td <?php echo getStyle($font,"center"); ?>> <?php echo $RM_CYL_TYPE; ?> </td>
			<td <?php echo getStyle($font,"center"); ?>> <?php echo $RM_LEFT_NO_INTO; ?> </td>
		</tr>
<?php
		}
?>

	</table>
</div>
