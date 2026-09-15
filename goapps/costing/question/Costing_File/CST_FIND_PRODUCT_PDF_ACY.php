<?php
  //echo "CYL_TYPE $CYL_TYPE : P_CYL_SYS_ID_DTL $P_CYL_SYS_ID_DTL</br>";
  echo "P_CYL_SYS_ID_DTL $P_CYL_SYS_ID_DTL</br>";
  echo "check $P_CYL_SYS_ID_DTL</br>";
  $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepLvl_Acy('$P_CYL_SYS_ID_DTL') GET_DATA from dual ";
  echo "$SqlData $SqlData</br>";
  if ($P_CYL_SYS_ID_DTL === "2022110989709"){
    die();
  }
  
  $deepMaterial =  getData($conn,$SqlData);

  $rs1Data = oci_parse($conn,$sql1);
  oci_execute ($rs1Data);
  $totRows = 0;
  $totData = 0;
  $SheetHeight = 3.5;
  // $SheetWidth = 50;

  $pdf->Cell(0,3,"",0,1,'C');
  while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
    //check is RM Rate Data
    $TotRmDt = 1;
    if ($row1Data['CYCRL_SEQ_NO']==="6"){
      //Get Tot Material top 55 ACY
      $SqlData = "SELECT COUNT (-1) GET_DATA  FROM mgtapps.cst_yarn_left l,mgtapps.cst_yarn_calculation a,mgtapps.cst_yarn_rm_hdr b,mgtapps.cst_yarn_rm_multi c
                   WHERE L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                         AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                         AND A.CYC_TOP_NO = 55
                         AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                         AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID";

      $TotRmDt = getData($conn,$SqlData);
      $TotLpDt = $TotRmDt;
      $TotLpDt = $TotLpDt + 5;
    } else if ($row1Data['CYCRL_SEQ_NO']==="17"){
      $TotLpDt = 2;
    } else {
      $TotLpDt = 1;
    }
    //check is RM Rate Data

    $pdf->SetFont('Arial','B',5);
    if ($row1Data['CYCRL_IS_BOLD']==="Y"){
      $pdf->SetFont('Arial','B',5);
    }
    $GrpCode =  "";
    for ($xDtMtrl = 1; $xDtMtrl <= $TotLpDt; $xDtMtrl++) {//Loop in Total Product
      //Description Data
      $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
      if (!empty($row1Data['CYCRL_SEQ_NO'])){
          $DataValue = $row1Data['CYCRL_SEQ_NO'].".";
      }
      if (!empty($row1Data['CYCRL_DESCRIPTION'])){
          $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
      }

      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$xDtMtrl;
        if ($TotRmDt>1){
          if ($TotRmDt<$xDtMtrl){
            if ($xDtMtrl - $TotRmDt === 1){ $DataValue = "Consume % of DTY"; } if ($xDtMtrl - $TotRmDt === 2){ $DataValue = "Consume % of SPD"; }
            if ($xDtMtrl - $TotRmDt === 3){ $DataValue = "Consume value of DTY"; } if ($xDtMtrl - $TotRmDt === 4){ $DataValue = "Consume value of SPD"; }
            if ($xDtMtrl - $TotRmDt === 5){ $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total"; }
          }
          //$DataValue = $row1Data['CYCRL_SEQ_NO'].".$DataValue";
        }
      }

      if ($row1Data['CYCRL_SEQ_NO']==="17"){
        if ($xDtMtrl === 2){
            $DataValue = "Draw Ratio";
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

      $SqlData = "SELECT count(-1) GET_DATA
                    FROM mgtapps.cst_yarn_left l,
                         mgtapps.cst_yarn_calculation a,
                         mgtapps.cst_yarn_rm_hdr b,
                         mgtapps.cst_yarn_rm_multi c
                   WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                         AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                         AND A.CYC_TOP_NO = 55
                         AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                         AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID";

      $totRM = //getData($conn,$SqlData);
               $deepMaterial;

      while ( $totRM > $lpDt) {
        $lpDt++;
        $dtVal = "$deepMaterial : $lpDt";
        if ($totRM > $lpDt){

          $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysId_Acy ('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual ";
          $Product_dt =  getData($conn,$SqlData);
          $StrPos = strpos($Product_dt,"|");
          //echo "StrPos $StrPos</br>";
          $Product_Key = substr($Product_dt,0,$StrPos);
          //echo "Product_Key $Product_Key</br>";
          $CylSysId_dt =  substr($Product_dt,$StrPos+1);
          //echo "CylSysId_dt $CylSysId_dt</br>";

          //$CylSysId_dt = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$lpDt);
          $dtVal = "";
          if ($xDtMtrl===1){
            $dtVal =  getDtVal( $conn
                                ,$CylSysId_dt
                                ,$sqlData
                                ,$row1Data['CYCRL_SOURCE_TYPE']
                                ,$row1Data['CYCRL_SOURCE_QUERY']
                                ,$row1Data['CYCRL_FORMAT_DATA']
                                ,$row1Data['CYCRL_LENGTH_DECIMAL']
                              );
          }

          if ($row1Data['CYCRL_SEQ_NO']==="1"){
            if ($Product_Key==="Stores"){
              $SqlData = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm($CylSysId_dt) GET_DATA from dual";
              $GrpCode =  getData($conn,$SqlData);
              if (substr($GrpCode,0,3)==="SPD"){
                  $dtVal = "SPD";
              }
            }
          }

          if ($row1Data['CYCRL_SEQ_NO']==="3"){
            if ($Product_Key==="Stores"){
              $SqlData = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm($CylSysId_dt) GET_DATA from dual";
              $GrpCode =  getData($conn,$SqlData);
              $dtVal = $GrpCode;
            }
          }

          if ($row1Data['CYCRL_SEQ_NO']==="4"){
            if ($Product_Key==="Stores"){
              $SqlData = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpDesc($CylSysId_dt) GET_DATA from dual";
              $GrpCode =  getData($conn,$SqlData);
              $dtVal = $GrpCode;
            }
          }

          if ($row1Data['CYCRL_SEQ_NO']==="6" && $xDtMtrl===1){
            if ($Product_Key==="Stores"){
              $SqlData = " select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate($CylSysId_dt) GET_DATA from dual";
              //echo $SqlData;die();
              $RmRate =  getData($conn,$SqlData);
              $dtVal = setNumber($conn,"Number",$RmRate,3);
            }
          }

          if ($row1Data['CYCRL_SEQ_NO']==="7" && $xDtMtrl===1){
            if ($Product_Key==="Stores"){
              $SqlData = " select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc($CylSysId_dt) GET_DATA from dual";
              //echo $SqlData;die();
              $RmRate =  getData($conn,$SqlData);
              $dtVal = setNumber($conn,"Number",$RmRate,3);
            }
          }

          if ($row1Data['CYCRL_SEQ_NO']==="17"){
            if ($xDtMtrl === 2 && $Product_Key==="Stores"){
              $SqlData = "
                          SELECT CYRM_DRAW_RATIO GET_DATA
                            FROM mgtapps.cst_yarn_left l,
                                 mgtapps.cst_yarn_calculation a,
                                 mgtapps.cst_yarn_rm_hdr b,
                                 mgtapps.cst_yarn_rm_multi c
                           WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                 AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                 AND A.CYC_TOP_NO = 55
                                 AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                 AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                 and CYRM_SEC_NO = 2
                          ";
              $RmRate =  getData($conn,$SqlData);
              $dtVal =  $RmRate;
            }
          }

          $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
        } else {
          //Left No FINAL Product
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
            if ($xDtMtrl < $totRM  ){
                $CylSysId_RM = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$xDtMtrl);
                if ($xDtMtrl === 1) {
                $SqlData = "select CYC_DATA_VALUE GET_DATA
                            from cst_yarn_calculation t
                            where cyc_cyl_sys_id = '$CylSysId_RM'
                            and cyc_top_no = 105";
                $dtVal = getData($conn,$SqlData);
              } else if ($xDtMtrl === 2) {
                $SqlData = "
                            SELECT cyrm_rm_rate GET_DATA
                              FROM mgtapps.cst_yarn_left l,
                                   mgtapps.cst_yarn_calculation a,
                                   mgtapps.cst_yarn_rm_hdr b,
                                   mgtapps.cst_yarn_rm_multi c
                             WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                   AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                   AND A.CYC_TOP_NO = 55
                                   AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                   AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                   and CYRM_TYPE_DATA = '$Product_Key'
                            ";
                //echo $SqlData;die();
                $RmRate =  getData($conn,$SqlData);
                $dtVal = setNumber($conn,"Number",$RmRate,3);
                //$dtVal = "";
              }
              $dtVal = setNumber($conn,"Number",$dtVal,3);
            }
           if ($xDtMtrl - $TotRmDt === 1){
             $SqlData = "
                         SELECT CYRM_CONSUME GET_DATA
                           FROM mgtapps.cst_yarn_left l,
                                mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                AND A.CYC_TOP_NO = 55
                                AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                and CYRM_SEC_NO = 1
                         ";
             $RmRate =  getData($conn,$SqlData);
             $dtVal = setNumber($conn,"Number",$RmRate,3);
           }
           if ($xDtMtrl - $TotRmDt === 2){
             $SqlData = "
                         SELECT CYRM_CONSUME GET_DATA
                           FROM mgtapps.cst_yarn_left l,
                                mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                AND A.CYC_TOP_NO = 55
                                AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                and CYRM_SEC_NO = 2
                         ";
             $RmRate =  getData($conn,$SqlData);
             $dtVal =  setNumber($conn,"Number",$RmRate,3);
           }
           if ($xDtMtrl - $TotRmDt === 3){
             $SqlData = "
                         SELECT CYRM_RM_COST GET_DATA
                           FROM mgtapps.cst_yarn_left l,
                                mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                AND A.CYC_TOP_NO = 55
                                AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                and CYRM_SEC_NO = 1
                         ";
             $RmRate =  getData($conn,$SqlData);
             $dtVal = setNumber($conn,"Number",$RmRate,3);
           } if ($xDtMtrl - $TotRmDt === 4){
             $SqlData = "
                         SELECT CYRM_RM_COST GET_DATA
                           FROM mgtapps.cst_yarn_left l,
                                mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
                                AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                AND A.CYC_TOP_NO = 55
                                AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                and CYRM_SEC_NO = 2
                         ";
             $RmRate =  getData($conn,$SqlData);
             $dtVal = setNumber($conn,"Number",$RmRate,3);
           }
         } else {
            $dtVal =  getDtVal( $conn
                                ,$CylSysId_dt
                                ,$sqlData
                                ,$row1Data['CYCRL_SOURCE_TYPE']
                                ,$row1Data['CYCRL_SOURCE_QUERY']
                                ,$row1Data['CYCRL_FORMAT_DATA']
                                ,$row1Data['CYCRL_LENGTH_DECIMAL']
                              );
          }
          $pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,1,$CYCRL_JUSTIFY);
          //Left No FINAL Product
        }
      }
      //Value Data
    }

  }

  //die();
?>
