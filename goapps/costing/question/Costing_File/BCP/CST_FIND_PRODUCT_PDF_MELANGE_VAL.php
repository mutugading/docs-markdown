<?php
  //echo "CYL_TYPE $CYL_TYPE : P_CYL_SYS_ID_DTL $P_CYL_SYS_ID_DTL</br>";
  $SqlDeepMaterial = getSqlDeepMelange($P_CYL_SYS_ID_DTL);

  //echo "SqlDeepMaterial $SqlDeepMaterial</br>";die();
  $deepMaterial =  getData($conn,$SqlDeepMaterial);
  //echo "deepMaterial $deepMaterial</br>";die();
  //echo "deepMaterial $deepMaterial";

  $rs1Data = oci_parse($conn,$sql1);
  oci_execute ($rs1Data);
  $totRows = 0;
  $totData = 0;
  $SheetHeight = 3.5;
  // $SheetWidth = 50;

  $pdf->Cell(0,3,"",0,1,'C');
  while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
    //check is RM Rate Data
    if ($row1Data['CYCRL_SEQ_NO']==="6"){
      $TotLpDt = $deepMaterial+1;
    } else {
      $TotLpDt = 1;
    }
    //check is RM Rate Data

    for ($x = 1; $x <= $TotLpDt; $x++) {
    //Description Data
    $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
    if (!empty($row1Data['CYCRL_SEQ_NO'])){
        $DataValue = $row1Data['CYCRL_SEQ_NO'].".";
    }
    if (!empty($row1Data['CYCRL_DESCRIPTION'])){
        $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
    }

    if ($row1Data['CYCRL_SEQ_NO']==="6"){
      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
      if ($TotLpDt===$x){
        $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
      }
    }

    $SheetWidth = 40;
    $pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);
    //Description Data

    //Value Data
    getParamRep($deepMaterial,$SheetWidth,$lnVal);
    $CYCRL_JUSTIFY = "LEFT";  $dtVal="";  $lnVal="";
    if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
    $lpDt = 0;
    $totRM = $deepMaterial+1;
    while ( $totRM > $lpDt) {
      $lpDt++;
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
        $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
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
        $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,1,$CYCRL_JUSTIFY);
      }
    }
    //Value Data
    }

  }

  //die();
?>
