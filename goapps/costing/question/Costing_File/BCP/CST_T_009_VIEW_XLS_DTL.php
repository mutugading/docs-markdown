<?php
  $sheetNm = "Product No ".$rowRsView["CYL_LEFT_NO"];

  $sqlCkType = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '".$rowRsView["CYL_SYS_ID"]."'";
	$CYL_TYPE_CK = getData($conn,$sqlCkType);

  $P_CYCRM_SYS_ID = "20211206";
  $P_CYC_PRS_TYPE = "20210800119";
  if ($SheetIdx===0){
    $objPHPExcel->getSheet(intval($SheetIdx))->setTitle($sheetNm);
  } else {
      $objWorkSheet = $objPHPExcel->createSheet($SheetIdx); //Setting index when creating
      $objWorkSheet->setTitle("$sheetNm");
  }
  include("CST_DWNLD_MULTI_PRDT_CHOICE.php");
?>
