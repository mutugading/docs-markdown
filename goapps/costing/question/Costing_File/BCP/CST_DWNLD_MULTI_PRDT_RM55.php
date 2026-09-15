<?php
	$SqlData = "select COUNT(-1) GET_DATA
							 from mgtapps.CST_LVL_LEFT_PROD
							 where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'";
	$deepMaterial =  getData($conn,$SqlData);

	//get tot Max RM
	$SqlData = "select max(count(-1)) GET_DATA
							from mgtapps.CST_LVL_LEFT_PROD a
							where A.CLLP_CYL_SYS_ID_REFF ='$P_CYL_SYS_ID_DTL'
							and CLLP_TYPE_RM = 'Multi Yarn'
							group by CLLP_LEFT_NO_REFF";
	$maxRm =  getData($conn,$SqlData);
	//Get tot Max RM

	if ($maxRm===""){
		$maxRm = 0;
	}

	$sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_MaxRm('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
	$maxRMDt = getData($conn,$sqlGet);

	$sql1 =
		"SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
         		NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
         		CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
         		NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
         		NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
         		,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
         		,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
         		,nvl(CYCRL_JUSTIFY,'L') CYCRL_JUSTIFY
         		,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
	$rs1Data = oci_parse($conn,$sql1);
	oci_execute ($rs1Data);
	$rowNo = 0;
	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
		// Description -->
		$sheet = getSheetCols(1);

		//check is RM Rate Data
		$TotLpDt = 1;
		if ($row1Data['CYCRL_SEQ_NO']==="6"){
			$TotLpDt = $maxRMDt+1;
		}
		//check is RM Rate Data

		for ($x = 1; $x <= $TotLpDt; $x++) {
				$rowNo++;
		//-- Description -->
				//isBold($row1Data['CYCRL_IS_BOLD'],1);
				if (!empty($row1Data['CYCRL_SEQ_NO'])){
					$DataValue = $row1Data['CYCRL_SEQ_NO'].".";
				}

				if (!empty($row1Data['CYCRL_DESCRIPTION'])){
					$DataValue = $row1Data['CYCRL_SEQ_NO'].".".$row1Data['CYCRL_DESCRIPTION'].".";
				}

				if ($row1Data['CYCRL_SEQ_NO']==="6"){
					$DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
					if ($TotLpDt===$x){
						$DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
					}
				}
				if ($SheetIdx===0){
					$objPHPExcel->getSheet(intval($SheetIdx))
											->setCellValue(	$sheet . $rowNo
																			,$DataValue
																		);
				} else {
					$objWorkSheet->setCellValue($sheet . $rowNo
																			,$DataValue
																			);
				}
				//echo $DataValue;
				//isBold($row1Data['CYCRL_IS_BOLD'],2);

		//-- Description -->
		//-- Product -->
		$lpDt = 0;
		while ($deepMaterial > $lpDt) {
			$lpDt++;
			$sheet = getSheetCols($lpDt+1);
			$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension($sheet)->setWidth(20);
			//Check Type Data
			$ckTypeDt = getData($conn,
													"select MGTAPPS.Pkg_Gen_Lvl_55.fGet_TypeData('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA
													 from dual");

			//Check Left No
			$ckLeftNoDt = getData($conn,
													"select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylLeftNo('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA
													 from dual");
			//Check Cyl Sys Id
			if ($ckTypeDt === "From Group Item MKT Rate"
				 || $ckTypeDt === "Stores" ){
			 $sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_IdReff('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
			} else{
			 $sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
			}
			$ckCylSysIdDt = getData($conn,$sqlGet);
			$CylSysId_dt =  $ckCylSysIdDt;

			if ($CYL_TYPE_S==="ACY"){
				//sdie();
				$dtVal = getDtVal_RM55(	$conn
									,$CylSysId_dt
									,$sqlData
									,$row1Data['CYCRL_SOURCE_TYPE']
									,$row1Data['CYCRL_SOURCE_QUERY']
									,$row1Data['CYCRL_FORMAT_DATA']
									,$row1Data['CYCRL_LENGTH_DECIMAL']
									,$row1Data['CYCRL_SEQ_NO']
								);

			} else {

				$dtVal = getDtVal(	$conn
									,$CylSysId_dt
									,$sqlData
									,$row1Data['CYCRL_SOURCE_TYPE']
									,$row1Data['CYCRL_SOURCE_QUERY']
									,$row1Data['CYCRL_FORMAT_DATA']
									,$row1Data['CYCRL_LENGTH_DECIMAL']
								);

			}

			if ($row1Data['CYCRL_SEQ_NO']==="1"){
				//echo "$ckTypeDt<b>";
				if ($ckTypeDt === "From Group Item MKT Rate"
						|| $ckTypeDt === "Stores" ){
					$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm('$CylSysId_dt') GET_DATA from dual";
					//echo "$sqlGet";
					$dtVal = getData($conn,$sqlGet);
					//echo "$dtVal";
				}
			}

			if ($row1Data['CYCRL_SEQ_NO']!=="6"){
				if ($row1Data['CYCRL_SEQ_NO']==="7"){
					if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
						$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$CylSysId_dt') GET_DATA from dual";
						//echo "$sqlGet";
						$dtVal = getData($conn,$sqlGet);
						//$dtVal = setNumber($conn,"Number",$dtVal,3);
						//echo $dtVal;
					} else {
							//echo $dtVal;//print value
					}
				} else {
					//echo $dtVal;//print value
				}
			} else {
				$isRmMulti = getData($conn,
														"SELECT  'Y' GET_DATA
														 FROM MGTAPPS.CST_YARN_CALCULATION a,
																	MGTAPPS.CST_YARN_RM_HDR b
														 WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
																	 AND cyc_top_no = 55
																	 AND CYC_FORMULA_TYPE = 'Raw_Material'
																	 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
																	 AND CYRH_TYPE = 'Multi Yarn'
														");

				if ($TotLpDt === $x){
					if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
						$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$CylSysId_dt') GET_DATA from dual";
						//echo "$sqlGet";
						$dtVal = getData($conn,$sqlGet);
						//$dtVal = setNumber($conn,"Number",$dtVal,3);
						//echo "$dtVal";
					} else {
							//echo $dtVal;//print value
					}
				}

				if($isRmMulti==="Y"){
					if ($TotLpDt > $x){
						$sqlGet = "SELECT CYRM_RM_RATE GET_DATA
							 FROM MGTAPPS.CST_YARN_CALCULATION a,
										MGTAPPS.CST_YARN_RM_HDR b,
										MGTAPPS.CST_YARN_RM_MULTI c
							WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
										AND cyc_top_no = 55
										AND CYC_FORMULA_TYPE = 'Raw_Material'
										AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
										AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
										AND CYRH_TYPE = 'Multi Yarn'
										and CYRM_SEC_NO = $x";
						$dtVal = getData($conn
													 ,$sqlGet);
						//echo setNumber($conn,"Number",$dtVal,3);
					}
				}
			}
			//isBold($row1Data['CYCRL_IS_BOLD'],2);
			if ($SheetIdx===0){
				$objPHPExcel->getSheet(intval($SheetIdx))
										->setCellValue(	$sheet . $rowNo
																		,$dtVal
																	);
			} else {
				$objWorkSheet->setCellValue($sheet . $rowNo
																		,$dtVal
																		);
			}
			include("CST_DWNLD_MULTI_PRDT_SET_DATA.php");
		}
		//-- Product -->
		}


	}
?>
