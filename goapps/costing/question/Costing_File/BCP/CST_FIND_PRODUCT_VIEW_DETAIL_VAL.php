<div class="main" style="overflow-x:auto;" align="center" >
<table style="width:100%">
	<tr>
		<td align="center"><b><?php echo "PERIOD PROCESS <font color='red' > $PERIOD_DATA_S </font>"; ?></br></td>
	</tr>
</table>
<?php
	$P_USER_ID = "$USER_NAME";
	$P_CYCRM_SYS_ID = 20221008;
	$sqlCkType = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'";
	$CYL_TYPE_CK = getData($conn,$sqlCkType);

	//getSysId marketing
	$SqlData = "
							select mkt.CYL_SYS_ID GET_DATA
							from    mgtapps.cst_yarn_left val
							        ,mgtapps.cst_yarn_left mkt
							where val.CYL_SYS_ID='$P_CYL_SYS_ID_DTL'
							and val.CYL_LEFT_NO = mkt.CYL_LEFT_NO
							and mkt.CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt";
	//echo "SqlData $SqlData</br>";
	//die();
	$CYL_SYS_ID_MKT =  getData($conn,$SqlData);
	//getSysId marketing

	$sqlCkRm55 = "select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
								where CLLP_CYL_SYS_ID_REFF = '$CYL_SYS_ID_MKT'
								and rownum = 1
								";
	$STS_RM_55 = getData($conn,$sqlCkRm55);

	//check RM BO

	$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('$CYL_SYS_ID_MKT') GET_DATA from dual";
	$isUseRmBo = getData($conn,$slctUseRm);
	//echo "isUseRmBo $isUseRmBo : STS_RM_55 $STS_RM_55 </br>";
	//die();
	//check RM BO


	if ($USER_NAME==="1949"){
		 //echo "$CYL_TYPE_CK $STSPRS : CYL_SYS_ID_DTL $P_CYL_SYS_ID_DTL : CYL_TYPE_CK $CYL_TYPE_CK : isUseRmBo $isUseRmBo : STS_RM_55 $STS_RM_55 </br>";
	}

	if ($CYL_TYPE_CK==="ITY"){
		if ($USER_NAME==="1949") {echo "form CST_FIND_PRODUCT_XLS_ITY </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_ITY.php");
	}	else if ($STS_RM_55==="Y"){
		if ($USER_NAME==="1949") {echo "form CST_FIND_PRODUCT_VIEW_DETAIL_RM_55_VAL </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_RM_55_VAL.php");
	}else if ($CYL_TYPE_CK==="MELANGE"){
		//if ($USER_NAME==="1949") {echo "form CST_FIND_PRODUCT_VIEW_DETAIL_MELANGE_VAL </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_MELANGE_VAL.php");
	}else if ($CYL_TYPE_CK==="ACY"){
		if ($USER_NAME==="1949") {echo "form CST_FIND_PRODUCT_VIEW_DETAIL_ACY_VAL </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_ACY_VAL.php");
	}else if ($CYL_TYPE_CK==="PTY BO"){
		if ($USER_NAME==="1949") {echo "form CST_FIND_PRODUCT_VIEW_DETAIL_PTY_BO_VAL </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_PTY_BO_VAL.php");
	}else if ($isUseRmBo ==="Y"){
		if ($USER_NAME==="1949") { echo "form CST_FIND_PRODUCT_VIEW_DETAIL_USE_RM_BO_VAL </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_USE_RM_BO_VAL.php");
	} else {
			$SqlData = "select MGTAPPS.pkg_yarn_valuation.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
			if ($USER_NAME==="1949") { echo "form Default </br>";}
			$deepMaterial =  getData($conn,$SqlData);
			$sql1 =
				"SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
		         		NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
		         		CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
		         		NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
		         		NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
		         		,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
		         		,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
		         		,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
		         		,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT
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

				$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt) GET_DATA
										from dual";
				$CylSysId_dt_Mkt =  getData($conn,$SqlData);

				$SqlData = "SELECT cyl_sys_id GET_DATA FROM cst_yarn_left b
										WHERE CYL_LEFT_NO IN (
											SELECT a.CYL_LEFT_NO FROM cst_yarn_left a WHERE a.cyl_sys_id = '$CylSysId_dt_Mkt'
										)AND CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIdVal ";
				$CylSysId_dt =  getData($conn,$SqlData);

				$dtVal = getDtVal_Valuation(	$conn
									,$CylSysId_dt
									,$PERIOD_DATA_S
									,$row1Data['CYCRL_SEQ_NO']
									,$sqlDataVal
									,$row1Data['CYCRL_SOURCE_TYPE']
									,$row1Data['CYCRL_SOURCE_QUERY']
									,$row1Data['CYCRL_FORMAT_DATA']
									,$row1Data['CYCRL_LENGTH_DECIMAL']
								);
				if ($row1Data['CYCRL_SEQ_NO']==="8"){

					//echo $row1Data['CYCRL_SOURCE_QUERY']." ".$row1Data['CYCRL_FORMAT_DATA']." ".$row1Data['CYCRL_LENGTH_DECIMAL'];

					//echo "CYCRL_SOURCE_TYPE ".$row1Data['CYCRL_SOURCE_TYPE'];

				}
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
<?php
}
?>
