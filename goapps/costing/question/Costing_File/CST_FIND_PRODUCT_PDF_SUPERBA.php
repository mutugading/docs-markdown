<?php
$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
$deepMaterial =  getData($conn,$SqlData);

echo "deepMaterial $deepMaterial</br>";die();

//echo "$sql1 </br>";
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
$SheetHeight = 3.5;
// $SheetWidth = 50;
$pdf->Cell(0,3,"",0,1,'C');
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
  $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
  if (!empty($row1Data['CYCRL_SEQ_NO'])){
      $DataValue = $row1Data['CYCRL_SEQ_NO'].".";
  }

  if (!empty($row1Data['CYCRL_DESCRIPTION'])){
      $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
  }
  $SheetWidth = 40;
  $pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);

  $pdf->SetFont('Arial','',7);
  if ($row1Data['CYCRL_IS_BOLD']==="Y"){
    $pdf->SetFont('Arial','B',7);
  }

  //Product
  $CYCRL_JUSTIFY = "LEFT";
  if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
  $lpDt = 0;
  $stsDtl = 0;
  while ($deepMaterial > $lpDt) {
    $lpDt++;
    $SqlData = "select
                  MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt)   GET_DATA
                from dual";
    $CylSysId_dt =  getData($conn,$SqlData);
    $dtVal = "";
    $dtVal =  getDtVal( $conn
                        ,$CylSysId_dt
                        ,$sqlData
                        ,$row1Data['CYCRL_SOURCE_TYPE']
                        ,$row1Data['CYCRL_SOURCE_QUERY']
                        ,$row1Data['CYCRL_FORMAT_DATA']
                        ,$row1Data['CYCRL_LENGTH_DECIMAL']
                      );
    //$SheetWidth = 50; $lnVal = 35;
    getParamRep($deepMaterial,$SheetWidth,$lnVal);
    if ($deepMaterial > $lpDt){
      $pdf->Cell($SheetWidth,$SheetHeight,substr($dtVal,0,$lnVal),1,0,$CYCRL_JUSTIFY);
      if($stsDtl===0){
        if (strlen($dtVal)>$lnVal){
          $stsDtl = 1;
        }
      }
    } else {
      $pdf->Cell($SheetWidth,$SheetHeight,substr($dtVal,0,$lnVal),1,1,$CYCRL_JUSTIFY);
      //Loop Detail
      //loop length data > $SheetWidth
      if($stsDtl===1){
        $SheetWidth = 40;
        $pdf->Cell($SheetWidth,$SheetHeight,"",1,0,$CYCRL_DESCRIPTION_JUSTIFY);
        //loop dtl
        $lpDt2 = 0;
        //$SheetWidth = 50;
        getParamRep($deepMaterial,$SheetWidth,$lnVal);
        while ($deepMaterial > $lpDt2) {
          $lpDt2++;

          $SqlData = "select
                            MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt2)   GET_DATA
                          from dual";
          $CylSysId_dt =  getData($conn,$SqlData);
          $dtVal = "";
          $dtVal =  getDtVal( $conn
                              ,$CylSysId_dt
                              ,$sqlData
                              ,$row1Data['CYCRL_SOURCE_TYPE']
                              ,$row1Data['CYCRL_SOURCE_QUERY']
                              ,$row1Data['CYCRL_FORMAT_DATA']
                              ,$row1Data['CYCRL_LENGTH_DECIMAL']
                            );

          if ($deepMaterial > $lpDt2){

            $pdf->Cell($SheetWidth,$SheetHeight,substr($dtVal,$lnVal),1,0,$CYCRL_JUSTIFY);
          } else {
            $pdf->Cell($SheetWidth,$SheetHeight,substr($dtVal,$lnVal),1,1,$CYCRL_JUSTIFY);
          }
        }
      }
      //loop length data > $SheetWidth
      //Loop Detail
    }
  }
//Product
}
?>
