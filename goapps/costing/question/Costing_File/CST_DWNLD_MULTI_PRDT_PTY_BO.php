<?php
	//$P_CYL_SYS_ID_DTL = $rowRsView["CYL_SYS_ID"];

	$yearPrs =  getData($conn,
							"select to_char(to_date(PARAM_VALUE,'YYYYMM'),'YYYY') GET_DATA
							from mgtapps.mst_params
							where param_id = 'PERIOD_DEFAULT_MB'
							");

	$monthPrs =  getData($conn,
							"select to_char(to_date(PARAM_VALUE,'YYYYMM'),'MM') GET_DATA
							from mgtapps.mst_params
							where param_id = 'PERIOD_DEFAULT_MB'
							");

	$StsRM = "Multi";
	$SqlData = "
							SELECT  count(-1) GET_DATA
							  FROM MGTAPPS.CST_YARN_CALCULATION a,
							       MGTAPPS.CST_YARN_RM_HDR b
							       ,MGTAPPS.CST_YARN_RM_MULTI c
							 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
							       AND cyc_top_no = 55
							       AND CYC_FORMULA_TYPE = 'Raw_Material'
							       AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
							       AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
							       AND CYRH_TYPE = 'Multi Yarn'
						 ";
	$totRM =  getData($conn,$SqlData);
	if ($totRM==="0"){
		$SqlData = "

								SELECT count(-1) GET_DATA
								  FROM MGTAPPS.CST_YARN_CALCULATION a,
								       MGTAPPS.CST_YARN_RM_HDR b
								 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
								       AND cyc_top_no = 55
								       AND CYC_FORMULA_TYPE = 'Raw_Material'
								       AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
								       AND CYRH_TYPE = 'Store Rate'

							";
		$totRM =  getData($conn,$SqlData);
		$StsRM = "Store";
	}
	$deepMaterial = $totRM + 1;
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
		$sheet = getSheetCols(1);
		//check is RM Rate Data
	  if ($row1Data['CYCRL_SEQ_NO']==="6"){
	    $TotLpDt = $totRM+1;
	  } else {
	    $TotLpDt = 1;
	  }
	  //check is RM Rate Data

		for ($x = 1; $x <= $TotLpDt; $x++) {
			$rowNo++;
		// Description -->
			if (!empty($row1Data['CYCRL_SEQ_NO'])){
				$DataValue = $row1Data['CYCRL_SEQ_NO'].".";
			}
			if (!empty($row1Data['CYCRL_DESCRIPTION'])){
				$DataValue = $row1Data['CYCRL_DESCRIPTION'].".";
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
		// Description -->

		// Product -->
		$lpDt = 0;
		while ($deepMaterial > $lpDt) {
			$lpDt++;
			$sheet = getSheetCols($lpDt+1);
			$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension($sheet)->setWidth(20);
			if ($lpDt <= $totRM ){
				$dtVal = "-";//$row1Data['CYCRL_SEQ_NO'];
				if ($row1Data['CYCRL_SEQ_NO']==="3"){
					if ($StsRM === "Multi" ) {
						$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a
														 ,MGTAPPS.CST_YARN_RM_HDR b
														 ,MGTAPPS.CST_YARN_RM_MULTI c
														 ,CST_GRP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Multi Yarn'
														 and CGH_SYS_ID = CYRM_MARKETING_CODE
														 and CYRM_SEC_NO = $lpDt ";

				  }
					if ($StsRM === "Store" ) {
						$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
													FROM MGTAPPS.CST_YARN_CALCULATION a,
															 MGTAPPS.CST_YARN_RM_HDR b,
															 MGTAPPS.CST_YARN_RM_DTL c,
															 MGTAPPS.CST_GRP_HEAD d
												 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
															 AND cyc_top_no = 55
															 AND CYC_FORMULA_TYPE = 'Raw_Material'
															 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
															 AND CYRD_CYRH_SYS_ID = CYRH_SYS_ID
															 AND CYRH_TYPE = 'Store Rate'
															 AND CYRD_RM_CODE = CGH_SYS_ID   ";
				 }
				$dtVal =  getData($conn,$SqlData);
			} else if ($row1Data['CYCRL_SEQ_NO']==="4"){
				if ($StsRM === "Multi" ) {
					$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a
														 ,MGTAPPS.CST_YARN_RM_HDR b
														 ,MGTAPPS.CST_YARN_RM_MULTI c
														 ,MGTAPPS.CST_GRP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Multi Yarn'
														 and CGH_SYS_ID = CYRM_MARKETING_CODE
														 and CYRM_SEC_NO = $lpDt ";
				}

				if ($StsRM === "Store" ) {
					$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a,
														 MGTAPPS.CST_YARN_RM_HDR b,
														 MGTAPPS.CST_YARN_RM_DTL c,
														 MGTAPPS.CST_GRP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRD_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Store Rate'
														 AND CYRD_RM_CODE = CGH_SYS_ID   ";
				}

				//echo $SqlData;die();
				$dtVal =  getData($conn,$SqlData);
			} else if ($row1Data['CYCRL_SEQ_NO']==="6"){

				if ($StsRM === "Multi" ) {

					$SqlData = " SELECT case when CGCH_MARKET_RATE1_FIX is not null then
																		CGCH_MARKET_RATE1_FIX
															else CGCH_MARKET_RATE1
															end
															GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a,
														 MGTAPPS.CST_YARN_RM_HDR b,
														 MGTAPPS.CST_YARN_RM_MULTI c
														 ,MGTAPPS.CST_GRP_CONSUMP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Multi Yarn'
														 AND CYRM_SEC_NO = $lpDt
														 and CGCH_PERIOD_YEAR = $yearPrs
														 and CGCH_PERIOD_MONTH = $monthPrs
														 and CYRM_MARKETING_CODE = CGCH_CGH_SYS_ID
														 ";
				}

				if ($StsRM === "Store" ) {
					$SqlData = "SELECT CYC_DATA_VALUE GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
										";
				}
				if ($TotLpDt===$x){
					$dtVal =  getData($conn,$SqlData);
					$dtVal = setNumber($conn,"Number",$dtVal,3);
				}
			} else if ($row1Data['CYCRL_SEQ_NO']==="7"){

				if ($StsRM === "Multi" ) {
									$SqlData = " SELECT CYRM_RM_RATE GET_DATA
																FROM MGTAPPS.CST_YARN_CALCULATION a,
																		 MGTAPPS.CST_YARN_RM_HDR b,
																		 MGTAPPS.CST_YARN_RM_MULTI c
															 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
																		 AND cyc_top_no = 55
																		 AND CYC_FORMULA_TYPE = 'Raw_Material'
																		 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
																		 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
																		 AND CYRH_TYPE = 'Multi Yarn'
																		 AND CYRM_SEC_NO = $lpDt
																		 ";
				}

				if ($StsRM === "Store" ) {
					$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
											where A.CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
											and A.CYC_TOP_NO = 56";
				}

				$dtVal =  getData($conn,$SqlData);
				//$dtVal = setNumber($conn,"Number",$dtVal,3);
			}


		} else {
			$CylSysId_dt =  $P_CYL_SYS_ID_DTL;
			$dtVal = getDtVal(	$conn
								,$CylSysId_dt
								,$sqlData
								,$row1Data['CYCRL_SOURCE_TYPE']
								,$row1Data['CYCRL_SOURCE_QUERY']
								,$row1Data['CYCRL_FORMAT_DATA']
								,$row1Data['CYCRL_LENGTH_DECIMAL']
							);

			if ($row1Data['CYCRL_SEQ_NO']==="6" && $TotLpDt>$x ){

				$SqlData = " SELECT
														case when CGCH_MARKET_RATE2_FIX is not null then
																	CGCH_MARKET_RATE2_FIX
														else CGCH_MARKET_RATE2
														end GET_DATA
											FROM MGTAPPS.CST_YARN_CALCULATION a,
													 MGTAPPS.CST_YARN_RM_HDR b,
													 MGTAPPS.CST_YARN_RM_MULTI c
													 ,MGTAPPS.CST_GRP_CONSUMP_HEAD d
										 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
													 AND cyc_top_no = 55
													 AND CYC_FORMULA_TYPE = 'Raw_Material'
													 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
													 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
													 AND CYRH_TYPE = 'Multi Yarn'
													 AND CYRM_SEC_NO = $x
													 and CGCH_PERIOD_YEAR = $yearPrs
													 and CGCH_PERIOD_MONTH = $monthPrs
													 and CYRM_MARKETING_CODE = CGCH_CGH_SYS_ID
													 ";
				$dtVal =  getData($conn,$SqlData);
				//$dtVal = setNumber($conn,"Number",$dtVal,3);
			}
			//echo "$CylSysId_dt </br>";
		}

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
		}//sini

	}
?>
