<?php
	//$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension('A')->setWidth(5);
	//$P_CYL_SYS_ID_DTL = $rowRsView["CYL_SYS_ID"];
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
         		,nvl(CYCRL_JUSTIFY,'L') CYCRL_JUSTIFY
         		,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
	$rs1Data = oci_parse($conn,$sql1);
	oci_execute ($rs1Data);
	$rowNo = 0;
	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
			$rowNo++;
		// Description -->
			//$sheetCol = "A$rowNo";
			$sheet = getSheetCols(1);
			$DataValue = "";
			if (!empty($row1Data['CYCRL_SEQ_NO'])){
				$DataValue = $row1Data['CYCRL_SEQ_NO'].".";
			}
			if (!empty($row1Data['CYCRL_DESCRIPTION'])){
				$DataValue = $DataValue.$row1Data['CYCRL_DESCRIPTION'].".";
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
			$objPHPExcel->getSheet(intval($SheetIdx))->getStyle($sheet . $rowNo)->getAlignment()->setWrapText(true);
		// Description -->
		//Product -->
			$CYCRL_JUSTIFY = "LEFT";
			$lpDt = 0;
			while ($deepMaterial > $lpDt) {
	 			$lpDt++;
				$sheet = getSheetCols($lpDt+1);
				$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension($sheet)->setWidth(20);
				$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt) GET_DATA
										from dual";
				$CylSysId_dt =  getData($conn,$SqlData);
				$CYCRL_SOURCE_TYPE = "";
				if (!empty($row1Data['CYCRL_SOURCE_TYPE'])){
					$CYCRL_SOURCE_TYPE = $row1Data['CYCRL_SOURCE_TYPE'];
				}
				$CYCRL_SOURCE_QUERY="";
				if (!empty($row1Data['CYCRL_SOURCE_QUERY'])){
					$CYCRL_SOURCE_QUERY=$row1Data['CYCRL_SOURCE_QUERY'];
				}
				$CYCRL_FORMAT_DATA="";
				if (!empty($row1Data['CYCRL_FORMAT_DATA'])){
					$CYCRL_FORMAT_DATA=$row1Data['CYCRL_FORMAT_DATA'];
				}
				$CYCRL_LENGTH_DECIMAL="";
				if (!empty($row1Data['CYCRL_LENGTH_DECIMAL'])){
					$CYCRL_LENGTH_DECIMAL=$row1Data['CYCRL_LENGTH_DECIMAL'];
				}

				$dtVal = getDtVal(
									$conn
									,$CylSysId_dt
									,$sqlData
									,$CYCRL_SOURCE_TYPE
									,$CYCRL_SOURCE_QUERY//$row1Data['CYCRL_SOURCE_QUERY']
									,$CYCRL_FORMAT_DATA
									,$CYCRL_LENGTH_DECIMAL
								);
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
				// $objPHPExcel->getSheet(	intval($SheetIdx))
				// 						->getStyle($sheet . $rowNo)
				// 						->getAlignment()->setWrapText(true);
				//
				// if ($row1Data['CYCRL_JUSTIFY']==="C"){
				// 	$objPHPExcel->getSheet(	intval($SheetIdx))
				// 							->getStyle($sheet . $rowNo)
				// 							->getAlignment()
				// 							->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				// }
				// if ($row1Data['CYCRL_JUSTIFY']==="R"){
				// 	$objPHPExcel->getSheet(	intval($SheetIdx))
				// 							->getStyle($sheet . $rowNo)
				// 							->getAlignment()
				// 							->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
				// }
				// if ($row1Data['CYCRL_IS_BOLD']==="Y"){
				// 	$objPHPExcel->getSheet(	intval($SheetIdx))
				// 							->getStyle($sheet . $rowNo)->getFont()->setBold( true );
				// }
				include("CST_DWNLD_MULTI_PRDT_SET_DATA.php");
			}
		//Product -->
		}
?>
