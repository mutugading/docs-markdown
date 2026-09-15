<?php
	//$P_CYL_SYS_ID_DTL = $rowRsView["CYL_SYS_ID"];
	$SqlData = getSqlDeepMelange($P_CYL_SYS_ID_DTL);
	//echo "SqlData $SqlData<br>";die();
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
		// Description -->
		$sheet = getSheetCols(1);
		//check is RM Rate Data
		if ($row1Data['CYCRL_SEQ_NO']==="6"){
	    $TotLpDt = $deepMaterial+1;
	  } else {
	    $TotLpDt = 1;
	  }
	  //check is RM Rate Data

		for ($x = 1; $x <= $TotLpDt; $x++) {
			$rowNo++;
			//Description
			if (!empty($row1Data['CYCRL_SEQ_NO'])){
				$DataValue = $row1Data['CYCRL_SEQ_NO'].".";
			}
			if (!empty($row1Data['CYCRL_DESCRIPTION'])){
				$DataValue = $DataValue.$row1Data['CYCRL_DESCRIPTION'].".";
			}
			if ($row1Data['CYCRL_SEQ_NO']==="6"){
		    $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
		    if ($TotLpDt===$x){
		      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
		    }
		  }

			if ($SheetIdx===0){
				$objPHPExcel->getSheet(intval($SheetIdx))
										->setCellValue(	$sheet . $rowNo,$DataValue);
			} else {
				$objWorkSheet->setCellValue($sheet . $rowNo,$DataValue);
			}
			//Description

			//Product
			$lpDt = 0;
			$totRM = $deepMaterial+1;
			while ( $totRM > $lpDt) {
				$lpDt++;
				$sheet = getSheetCols($lpDt+1);
				$objPHPExcel->getSheet(intval($SheetIdx))->getColumnDimension($sheet)->setWidth(20);
				if ($row1Data['CYCRL_SEQ_NO']==="1"){
						$ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
						$TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
				}
				$dtVal = "$deepMaterial : $lpDt";
				if ($totRM > $lpDt){
					$CylSysId_dt = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$lpDt);
					$dtVal = "";
					if ($x===1){
						$dtVal =  getDtVal( $conn
																,$CylSysId_dt
																,$sqlData
																,$row1Data['CYCRL_SOURCE_TYPE']
																,$row1Data['CYCRL_SOURCE_QUERY']
																,$row1Data['CYCRL_FORMAT_DATA']
																,$row1Data['CYCRL_LENGTH_DECIMAL']
															);
					}
					if ($SheetIdx===0){
						$objPHPExcel->getSheet(intval($SheetIdx))
												->setCellValue(	$sheet . $rowNo ,$dtVal );
					} else {
						$objWorkSheet->setCellValue($sheet . $rowNo,$dtVal);
					}
				} else {
					//Left No MILANGE
					$CylSysId_dt =  $P_CYL_SYS_ID_DTL;
					$dtVal = "";
					$dtVal =  getDtVal( $conn
															,$CylSysId_dt
															,$sqlData
															,$row1Data['CYCRL_SOURCE_TYPE']
															,$row1Data['CYCRL_SOURCE_QUERY']
															,$row1Data['CYCRL_FORMAT_DATA']
															,$row1Data['CYCRL_LENGTH_DECIMAL']
														);

					if ($row1Data['CYCRL_SEQ_NO']==="6"){
						if ($x < $totRM ){
							$CylSysId_RM = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$x);
							$SqlData = "select CYC_DATA_VALUE GET_DATA
													from cst_yarn_calculation t
													where cyc_cyl_sys_id = '$CylSysId_RM'
													and cyc_top_no = 105";
							$dtVal = getData($conn,$SqlData);
							$dtVal = setNumber($conn,"Number",$dtVal,3);
						}
					}
					if ($SheetIdx===0){
						$objPHPExcel->getSheet(intval($SheetIdx))
												->setCellValue(	$sheet . $rowNo ,$dtVal );
					} else {
						$objWorkSheet->setCellValue($sheet . $rowNo,$dtVal);
					}
				}
				//$objPHPExcel->getSheet(intval($SheetIdx))->getStyle($sheet . $rowNo)->getAlignment()->setWrapText(true);
				include("CST_DWNLD_MULTI_PRDT_SET_DATA.php");
			}
			//-- Product -->
		}
	}
?>
