<?php
	$P_USER_ID = "$USER_NAME";
	$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial_RmBO($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
	$deepMaterial =  getData($conn,$SqlData);

	echo "SqlData $SqlData : deepMaterial $deepMaterial</br>";die();

	$sql1 =
		"SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
         		NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
         		CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
         		NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
         		NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
         		,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
         		,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
         		,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
         		,CYCRL_IS_BOLD
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
	$rs1Data = oci_parse($conn,$sql1);
	oci_execute ($rs1Data);
	$totRows = 0;
	$totData = 0;

	$widthTbl = ($deepMaterial+1) * 20;
	$widthSttg = "width:$widthTbl%";
	$bgClr = "#e6e1e1";

?>
<div class="main" style="overflow-x:auto;" align="center" >
<table style="<?php echo $widthSttg; ?>" border="1">

<?php
	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
?>
<tr>
<!-- Description -->
			<td style="width:20%">
			<?php
				isBold($row1Data['CYCRL_IS_BOLD'],1);
				if (!empty($row1Data['CYCRL_SEQ_NO'])){ echo $row1Data['CYCRL_SEQ_NO'].".";}

				if (!empty($row1Data['CYCRL_DESCRIPTION'])){ echo $row1Data['CYCRL_DESCRIPTION'].".";}
				isBold($row1Data['CYCRL_IS_BOLD'],2);

			?>
			</td>
<!-- Description -->
<!-- Product -->
<?php
		$CYCRL_JUSTIFY = "LEFT";
		if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
		$lpDt = 0;
		while ($deepMaterial > $lpDt) {
 			$lpDt++;
?>
		<td style="width:20%" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
		if ($lpDt===1){

			$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
													'$P_CYL_SYS_ID_DTL'
													,$deepMaterial
													,2
													) GET_DATA
									 from dual";
			$CylSysId_UseBo =getData($conn,$sqlGetDt);

			$dtVal = "-";//$row1Data['CYCRL_SEQ_NO'];
      if ($row1Data['CYCRL_SEQ_NO']==="3"){
        $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                    where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
                    and A.CYC_TOP_NO = 20 ";
        //echo $SqlData;die();
        $dtVal =  getData($conn,$SqlData);
      } else if ($row1Data['CYCRL_SEQ_NO']==="4"){
        $SqlData = "select CGH_DESCRIPTION GET_DATA
                    from mgtapps.cst_yarn_calculation a
                         ,mgtapps.cst_grp_head b
                    where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
                    and A.CYC_TOP_NO = 20
                    and A.CYC_DATA_VALUE = b.cgh_group_code";
        //echo $SqlData;die();
        $dtVal =  getData($conn,$SqlData);
      } else if ($row1Data['CYCRL_SEQ_NO']==="6"){
        $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                    where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
                    and A.CYC_TOP_NO = 55";
        //echo $SqlData;die();
        $dtVal =  getData($conn,$SqlData);
        $dtVal = setNumber($conn,"Number",$dtVal,3);
      } else if ($row1Data['CYCRL_SEQ_NO']==="7"){
        $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                    where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
                    and A.CYC_TOP_NO = 56";
        //echo $SqlData;die();
        $dtVal =  getData($conn,$SqlData);
        $dtVal = setNumber($conn,"Number",$dtVal,3);
      }


		} else {

			$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
			        						'$P_CYL_SYS_ID_DTL'
			        						,$deepMaterial
			        						,$lpDt
			        						) GET_DATA
									 from dual";
			$CylSysId_dt =getData($conn,$sqlGetDt);
			//$CylSysId_dt =  $P_CYL_SYS_ID_DTL;
			$dtVal = getDtVal(	$conn
								,$CylSysId_dt
								,$sqlData
								,$row1Data['CYCRL_SOURCE_TYPE']
								,$row1Data['CYCRL_SOURCE_QUERY']
								,$row1Data['CYCRL_FORMAT_DATA']
								,$row1Data['CYCRL_LENGTH_DECIMAL']
							);
			//echo "$CylSysId_dt </br>";
		}
		isBold($row1Data['CYCRL_IS_BOLD'],1);
		echo $dtVal;//print value
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
