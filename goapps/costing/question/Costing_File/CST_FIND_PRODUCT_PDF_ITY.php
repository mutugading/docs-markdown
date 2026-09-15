<?php

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

$StsRM = "Store";
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

$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
$SheetHeight = 3.5;
// $SheetWidth = 50;
$pdf->Cell(0,3,"",0,1,'C');
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
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
  $SheetWidth = 50;
  $pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);
  //Description

  //Product
  $CYCRL_JUSTIFY = "LEFT";
  if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
  $lpDt = 0;
  $stsDtl = 0;
  while ($deepMaterial > $lpDt) {
    $lpDt++;

    if ($lpDt <= $totRM ){
      $dtVal = "-";//$row1Data['CYCRL_SEQ_NO'];
      if ($row1Data['CYCRL_SEQ_NO']==="3"){
        if ($StsRM === "Store" ) {
					//echo "StsRM $StsRM";
					$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a,
											       MGTAPPS.CST_YARN_RM_HDR b,
											       MGTAPPS.CST_YARN_RM_MULTI c,
											       MGTAPPS.CST_GRP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Multi Yarn'
														 AND CYRM_MARKETING_CODE = CGH_SYS_ID
														 AND CYRM_SEC_NO = $lpDt  ";
					//echo "$SqlData</br>";//die();
				}
        $dtVal =  getData($conn,$SqlData);
      } else if ($row1Data['CYCRL_SEQ_NO']==="4"){
        if ($StsRM === "Store" ) {
					$SqlData = "SELECT CGH_DESCRIPTION GET_DATA
												FROM MGTAPPS.CST_YARN_CALCULATION a,
											       MGTAPPS.CST_YARN_RM_HDR b,
											       MGTAPPS.CST_YARN_RM_MULTI c,
											       MGTAPPS.CST_GRP_HEAD d
											 WHERE     CYC_CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
														 AND cyc_top_no = 55
														 AND CYC_FORMULA_TYPE = 'Raw_Material'
														 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
														 AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
														 AND CYRH_TYPE = 'Multi Yarn'
														 AND CYRM_MARKETING_CODE = CGH_SYS_ID
														 AND CYRM_SEC_NO = $lpDt  ";
				}
        $dtVal =  getData($conn,$SqlData);
      } else if ($row1Data['CYCRL_SEQ_NO']==="6"){
        if ($StsRM === "Store" ) {
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
        $dtVal =  getData($conn,$SqlData);
        $dtVal = setNumber($conn,"Number",$dtVal,3);
      } else if ($row1Data['CYCRL_SEQ_NO']==="7"){

        if ($StsRM === "Store" ) {
          $SqlData = " SELECT case when CGCH_MARKET_RATE2_FIX is not null then
                                    CGCH_MARKET_RATE2_FIX
                              else CGCH_MARKET_RATE2
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
        //echo $SqlData;die();
        $dtVal =  getData($conn,$SqlData);
        $dtVal = setNumber($conn,"Number",$dtVal,3);
      }



      $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
    } else {
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
    //end if
  }
//Product
}
?>
