<?php
$objPHPExcel->getSheet(intval($SheetIdx))->getStyle($sheet . $rowNo)->getAlignment()->setWrapText(true);
if ($row1Data['CYCRL_JUSTIFY']==="C"){
  $objPHPExcel->getSheet(	intval($SheetIdx))
              ->getStyle($sheet . $rowNo)
              ->getAlignment()
              ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
}
if ($row1Data['CYCRL_JUSTIFY']==="R"){
  $objPHPExcel->getSheet(	intval($SheetIdx))
              ->getStyle($sheet . $rowNo)
              ->getAlignment()
              ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
}
if ($row1Data['CYCRL_IS_BOLD']==="Y"){
  $objPHPExcel->getSheet(	intval($SheetIdx))
              ->getStyle($sheet . $rowNo)->getFont()->setBold( true );
}
?>
