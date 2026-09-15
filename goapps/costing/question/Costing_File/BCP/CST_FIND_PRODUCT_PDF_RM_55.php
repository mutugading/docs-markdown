<?php

$SqlDepMtrl = "select COUNT(-1) GET_DATA
            from mgtapps.CST_LVL_LEFT_PROD
            where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'";
$deepMaterial =  getData($conn,$SqlDepMtrl);

//get tot Max RM
$SqlData = "select max(count(-1)) GET_DATA
            from mgtapps.CST_LVL_LEFT_PROD a
            where A.CLLP_CYL_SYS_ID_REFF ='$P_CYL_SYS_ID_DTL'
            and CLLP_TYPE_RM = 'Multi Yarn'
            group by CLLP_LEFT_NO_REFF";
//echo "Max RM $SqlData";
$maxRm =  getData($conn,$SqlData);
if ($maxRm===""){
  $maxRm = 0;
}
$sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_MaxRm('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
$maxRMDt = getData($conn,$sqlGet);
//Get tot Max RM

$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
$SheetHeight = 3.5;
// $SheetWidth = 50;
$pdf->Cell(0,3,"",0,1,'C');
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
  $TotLpDt = 1;
  if ($row1Data['CYCRL_SEQ_NO']==="6"){
    $TotLpDt = $maxRMDt+1;
  }

  for ($x = 1; $x <= $TotLpDt; $x++) {

    $pdf->SetFont('Arial','',7);
    if ($row1Data['CYCRL_IS_BOLD']==="Y"){
      $pdf->SetFont('Arial','B',7);
    }

    //Description
    $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
    if (!empty($row1Data['CYCRL_SEQ_NO'])){
        $DataValue = $row1Data['CYCRL_SEQ_NO'].".";
    }

    if (!empty($row1Data['CYCRL_DESCRIPTION'])){
        $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
    }
    //$SheetWidth = 40;
    $SheetWidth = 35;
    $pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);
    //Description

    //Product
    $CYCRL_JUSTIFY = "LEFT";
    if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
    $lpDt = 0;
    $stsDtl = 0;
    while ($deepMaterial > $lpDt) {
      $lpDt++;
      $dtVal = "";
      if ($row1Data['CYCRL_SEQ_NO']==="1"){
          $ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
          $TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
      }
      //Check Type Data
      $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_TypeData('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      $ckTypeDt = getData($conn,$sqlCk);
      //Check Left No
      $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylLeftNo('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      $ckLeftNoDt = getData($conn,$sqlCk);
      //Check Cyl Sys Id
      if ($ckTypeDt === "From Group Item MKT Rate"
         || $ckTypeDt === "Stores" ){
       $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_IdReff('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      } else{
       $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      }
      $ckCylSysIdDt = getData($conn,$sqlCk);

      $CylSysId_dt = $ckCylSysIdDt;
      $dtVal = getDtVal_RM55(	$conn
                ,$CylSysId_dt
                ,$sqlData
                ,$row1Data['CYCRL_SOURCE_TYPE']
                ,$row1Data['CYCRL_SOURCE_QUERY']
                ,$row1Data['CYCRL_FORMAT_DATA']
                ,$row1Data['CYCRL_LENGTH_DECIMAL']
                ,$row1Data['CYCRL_SEQ_NO']
              );
      //dtCek
      $dtCek = 0;
      if (is_numeric($dtVal)){
        $dtCek = $dtVal;
      }
      //dtCek

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

      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        if ($TotLpDt === $x){
          if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
            $sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$CylSysId_dt') GET_DATA from dual";
            //echo "$sqlGet";
            $dtVal = getData($conn,$sqlGet);
            $dtVal = setNumber($conn,"Number",$dtVal,3);
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
              $dtVal = getData($conn,$sqlGet);
              $dtVal = setNumber($conn,"Number",$dtVal,3);
            }
          } else {
              $dtVal = "";
          }
        }
      } else {

        if ($row1Data['CYCRL_SEQ_NO']==="7"){
          if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
            $sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$CylSysId_dt') GET_DATA from dual";
            //echo "$sqlGet";
            $dtVal = getData($conn,$sqlGet);
            $dtVal = setNumber($conn,"Number",$dtVal,3);
          }
        }
      }

      // //$SheetWidth = 50;
      getParamRep($deepMaterial,$SheetWidth,$lnVal);
      if ($deepMaterial > $lpDt){
        $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
      } else {
        $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,1,$CYCRL_JUSTIFY);
      }
    }
//Product
  }
}
?>
