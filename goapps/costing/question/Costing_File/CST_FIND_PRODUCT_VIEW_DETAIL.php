<?php
	$P_USER_ID = "$USER_NAME";
	$sqlCkType = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'";
	$CYL_TYPE_CK = getData($conn,$sqlCkType);

	$sqlCkRm55 = "select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
								where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'
								and rownum = 1
								";
	$STS_RM_55 = getData($conn,$sqlCkRm55);

	//check RM BO
	$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
	$isUseRmBo = getData($conn,$slctUseRm);
	if ($CYL_TYPE_CK==="ITY"){
		if ($USER_NAME==="1949") {echo "form 1 </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_ITY.php");
	}	else if ($STS_RM_55==="Y"){
		if ($USER_NAME==="1949") {echo "form 2 (55)</br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_RM_55.php");
	}else if ($CYL_TYPE_CK==="MELANGE"){
		if ($USER_NAME==="1949") {echo "form 3(MELANGE) </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_MELANGE.php");
	}else if ($CYL_TYPE_CK==="ACY"){
		if ($USER_NAME==="1949") {echo "form 4 (ACY) </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_ACY.php");
	}else if ($CYL_TYPE_CK==="PTY BO"){
		if ($USER_NAME==="1949") {echo "form 5 (PTY BO)</br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_PTY_BO.php");
	}else if ($isUseRmBo ==="Y"){
		if ($USER_NAME==="1949") {echo "form 6 </br>";}
		include("CST_FIND_PRODUCT_VIEW_DETAIL_USE_RM_BO.php");
	} else {
			if ($USER_NAME==="1949") {echo "form 7 </br>";}
			$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
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
		         		,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT,CYCRL_HIDE_SEQ_NO
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
					<td style="width:20%;font-size:13px;">
					<?php
						isBold($row1Data['CYCRL_IS_BOLD'],1);
						// if (!empty($row1Data['CYCRL_SEQ_NO'])){
						// 	if ($row1Data['CYCRL_HIDE_SEQ_NO']==="0"){ echo $row1Data['CYCRL_SEQ_NO']."."; }
						// }
						// if (!empty($row1Data['CYCRL_DESCRIPTION'])){ echo $row1Data['CYCRL_DESCRIPTION'].".";}
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
				<td style="width:20%;font-size:13px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
		<?php
				$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt) GET_DATA
										from dual";

				$CylSysId_dt =  getData($conn,$SqlData);
				$dtVal = getDtVal(	$conn
									,$CylSysId_dt
									,$sqlData
									,$row1Data['CYCRL_SOURCE_TYPE']
									,$CYCRL_SOURCE_QUERY//$row1Data['CYCRL_SOURCE_QUERY']
									,$row1Data['CYCRL_FORMAT_DATA']
									,$row1Data['CYCRL_LENGTH_DECIMAL']
									,$P_CYL_SYS_ID_DTL
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
<?php
}
?>
