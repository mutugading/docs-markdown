<?php
include("conOraOci.php");
include ("common_function.php");
//include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");
include ("CST_DWNLD_MULTI_PRDT_SQL.php");

$FORM_NAME = "NULL";
if(isset($_GET['FORM_NAME'])) {
  $FORM_NAME=$_GET['FORM_NAME'];
}

$PERIOD_MONTH_S = "NULL";
if(isset($_GET['PERIOD_MONTH_S'])) {
  $PERIOD_MONTH_S=$_GET['PERIOD_MONTH_S'];
}

$PERIOD_YEAR_S = "NULL";
if(isset($_GET['PERIOD_YEAR_S'])) {
  $PERIOD_YEAR_S=$_GET['PERIOD_YEAR_S'];
}

/** Include PHPExcel */
require_once dirname(__FILE__) . '/PHPExcel-1.8/Classes/PHPExcel.php';
// Create new PHPExcel object
$objPHPExcel = new PHPExcel();
$SheetIdx = 0;
$objPHPExcel->getSheet(intval($SheetIdx))->setTitle("MB Cost Period $PERIOD_YEAR_S - $PERIOD_MONTH_S");

// header
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'NO') //1
            ->setCellValue('B1', 'STATUS') //2
            ->setCellValue('C1', 'CHECK STATUS') //3
            ->setCellValue('D1', 'MB ORION ITEM CODE') //4

            ->setCellValue('E1', 'MB SPG ORION') //5
            ->setCellValue('F1', 'MB COSTING') //6
            ->setCellValue('G1', 'VS NUMBER') //7
            ->setCellValue('H1', 'VALUATION COMPOSITION') //8
            ->setCellValue('I1', 'MARKETING COMPOSITION') //9
            ->setCellValue('J1', 'SIMULATION COMPOSITION') //10

            ->setCellValue('K1', 'PROCESS CODE') //11
            ->setCellValue('L1', 'PROCESS VALUE') //12
            ->setCellValue('M1','THROUGH PUT CODE') //13
            ->setCellValue('N1','THROUGH PUT CODE') //14
            ->setCellValue('O1','MACHINE CODE') //15

            ->setCellValue('P1','TOTAL FIX CODE') //16
            ->setCellValue('Q1','% WASTE') //17
            ->setCellValue('R1','% QUALITY LOSS') //18
            ->setCellValue('S1','% EFFICIENCY') //19
            ->setCellValue('T1','DEVELOPMENT EXPENSE') //20

            ->setCellValue('U1','PACKING') //21
            ->setCellValue('V1','NET PRODUCTION / DAY') //22
            ->setCellValue('W1','WASTE VALUATION') //23
            ->setCellValue('X1','FIXED COST') //24
            ->setCellValue('Y1','COST_OTHERS') //25

            ->setCellValue('Z1','CONV COST V') //26
            ->setCellValue('AA1','COST VALUATION') //27
            ->setCellValue('AB1','COST MARKETING') //28
            ->setCellValue('AC1','COST SIMULATION') //29
            ->setCellValue('AD1','NO') //30

            ->setCellValue('AE1','CODE') //31
            ->setCellValue('AF1','COLOURANT') //32
            ->setCellValue('AG1','COMPOSITION') //33
            ->setCellValue('AH1','RM RATE VALUATION') //34
            ->setCellValue('AI1','RM RATE MARKETING') //35

            ->setCellValue('AJ1','RM RATE SIMULATION') //36
            ->setCellValue('AK1','COST VALUATION') //37
            ->setCellValue('AL1','COST MARKETING') //38
            ->setCellValue('AM1','COST SIMULATION') //39
            ->setCellValue('AN1','MB SYS ID') //40

            ->setCellValue('AO1','D/F') //41
            ->setCellValue('AP1','LDR (%)') //42
            ->setCellValue('AQ1','FINAL_PRODUCT') //43
            ->setCellValue('AR1','LDR (%)') //44
            ;


          $sql1 =
        		"SELECT CMCH_CMBH_STATUS,CMCH_CHECK_STATUS,CMCH_ORION_ITEM_CODE,CMCH_MB_SPG_ORION,
                   CMCH_MB_COSTING,CMCH_RM_VAL,CMCH_RM_MKT,CMCH_RM_SML,CMCH_NO_OF_PROCESS_CODE,
                   CMCH_NO_OF_PROCESS_VAL,CMCH_THROUGH_PUT_CODE,CMCH_THROUGH_PUT_VAL,CMCH_CMM_MACHINE_CODE,
                   CMCH_CMM_TOT_FXD_CST,CMCH_WASTE,CMCH_QUALITY_LOSS,CMCH_EFFICIENCY,CMCH_DEV_EXPENSE,
                   CMCH_PACKING,CMCH_NET_PROD_PER_DAY,CMCH_WASTE_VALUATION,CMCH_FIXED_COST,CMCH_COST_OTHERS,
                   CMCH_CONV_COST_V,CMCH_COST_VALUATION,CMCH_COST_MARKETING,CMCH_COST_SIMULATION,
                   CMCI_CMBI_SEC_NO,CMCI_CMBI_CODE,CMCI_CMBI_COLOURANT,CMCI_RM_VAL_1,CMCI_RM_MKT_1,
                   CMCI_RM_SML_1,CMCI_CMBI_COMPOSITION,CMCI_RM_VAL_2,CMCI_RM_MKT_2, CMCI_RM_SML_2,
                   CMCI_CMBH_SYS_ID,CMBH_D_F,CMBH_RUN_LDR_PRSN,CMBH_FINAL_PRODUCT,CMBH_LDR_PRSN,
                   CMCH_FIXED_COST_FINAL,CMBH_VS_NUMBER
            FROM cst_mb_consump_head h, cst_mb_consump_item i, cst_mst_batch_head c
             WHERE     CMCH_PERIOD_YEAR = 2023
                   AND CMCH_PERIOD_MONTH = 2
                   AND CMCH_PERIOD_YEAR = CMCI_PERIOD_YEAR
                   AND CMCH_PERIOD_MONTH = CMCI_PERIOD_MONTH
                   AND CMCH_CMBH_SYS_ID = CMCI_CMBH_SYS_ID
                   AND c.CMBH_SYS_ID = CMCI_CMBH_SYS_ID
                   and ROWNUM < 1000
          ORDER BY CMCI_CMBH_SYS_ID, CMCI_CMBI_SEC_NO
          ";
          $rowNo = 1; $MB_COSTING =""; $REC_NO = 0;
        	$rs1Data = oci_parse($conn,$sql1);
        	oci_execute ($rs1Data);
          $REC_NO_TMP = 0;
        	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
            $rowNo++;
            $MB_COSTING_DT ="";
            if (!empty($row1Data['CMCH_MB_COSTING'])){
                $MB_COSTING_DT = $row1Data['CMCH_MB_COSTING'];
            }
            if ($MB_COSTING==="") {
              $MB_COSTING = $MB_COSTING_DT;
              $REC_NO = 1;
            } else {
              if ($MB_COSTING !== $MB_COSTING_DT){
                $MB_COSTING = $MB_COSTING_DT;
                $REC_NO++;
              }
            }
            for ($x = 1; $x <= 44; $x++) {

                include ($FORM_NAME."_DWNLD_XLS_SET_DT.php");

                $sheet = getSheetCols($x);
                $objPHPExcel->getSheet(intval($SheetIdx))
        										->setCellValue(	$sheet . $rowNo
        																		,"$dtVal"
        																	);
            }
          }

// mengeset sheet 2 yang aktif
//$objPHPExcel->setActiveSheetIndex(1);

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="MB Cost Data.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
