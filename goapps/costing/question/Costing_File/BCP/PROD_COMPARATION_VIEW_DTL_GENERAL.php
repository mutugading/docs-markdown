<?php
$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID) GET_DATA from dual ";
$deepMaterial =  getData($conn,$SqlData);

$sql1 =
	// "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
	// 				NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
	// 				CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
	// 				NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
	// 				NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
	// 				,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
	// 				,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
	// 				,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
	// 				,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT,CYCRL_HIDE_SEQ_NO
	// 		FROM mgtapps.cst_yarn_calc_rpt_lable b
	// 		WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
	// 		order by to_number(cycrl_seq_no) "
	getSqlDesc($P_CYCRM_SYS_ID);
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;

$widthTbl = ($deepMaterial+1) * 30;
$widthSttg = "width:$widthTbl%";
$bgClr = "#e6e1e1";

?>
<div class="main" style="overflow-x:auto;" align="left" >
<table style="<?php echo $widthSttg; ?>" border="1">

<?php
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
?>
<tr>
<!-- Description -->
		<td style="width:21%;font-size:<?php echo $font; ?>px;">
		<?php
			isBold($row1Data['CYCRL_IS_BOLD'],1);
			echo printDescription(
														$row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
														,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
														,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
														);

			isBold($row1Data['CYCRL_IS_BOLD'],2);

		?>
		</td>
<!-- Description -->
<!-- Product -->
<?php
	$CYCRL_JUSTIFY = "LEFT";
	if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}

	$CYCRL_SOURCE_QUERY = "";
	if (!empty($row1Data['CYCRL_SOURCE_QUERY'])){$CYCRL_SOURCE_QUERY = $row1Data['CYCRL_SOURCE_QUERY'];}

	$lpDt = 0;
	while ($deepMaterial > $lpDt) {
		$lpDt++;
?>
	<td style="width:15%;font-size:<?php echo $font; ?>px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
	$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID,$deepMaterial,$lpDt) GET_DATA
							from dual";

	$CylSysId_dt =  getData($conn,$SqlData);
	$dtVal = getDtVal(	$conn
						,$CylSysId_dt
						,$sqlData
						,$row1Data['CYCRL_SOURCE_TYPE']
						,$CYCRL_SOURCE_QUERY//$row1Data['CYCRL_SOURCE_QUERY']
						,$row1Data['CYCRL_FORMAT_DATA']
						,$row1Data['CYCRL_LENGTH_DECIMAL']
						,$P_CYL_SYS_ID
					);
	//echo "$CylSysId_dt </br>";
	isBold($row1Data['CYCRL_IS_BOLD'],1);
	if ($row1Data['CYCRL_DISP_IN_FINAL_PRODUCT']==="Y"){
		if($deepMaterial === $lpDt){
			echo $dtVal;//print value
		}
	} else {
		echo $dtVal;//print value
	}
	isBold($row1Data['CYCRL_IS_BOLD'],2);
?>
	</td>
<?php
	}
?>
<!-- Product -->
</tr>
<?php
}
?>
</table>
</div>
