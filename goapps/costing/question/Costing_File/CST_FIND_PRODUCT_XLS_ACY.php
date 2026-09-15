<?php
// $SqlData = getSqlDeepMelange($P_CYL_SYS_ID_DTL);
// $deepMaterial =  getData($conn,$SqlData);
$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepLvl_Acy('$P_CYL_SYS_ID_DTL') GET_DATA from dual ";
$deepMaterial =  getData($conn,$SqlData);
?>
<html>
<head>
</head>
<body>
    <style type="text/css">
    body{
        font-family: sans-serif;
    }
    table{
        margin: 20px auto;
        border-collapse: collapse;
    }
    table th,
    table td{
        border: 1px solid #3c3c3c;
        padding: 3px 8px;

    }
    a{
        background: blue;
        color: #fff;
        padding: 8px 10px;
        text-decoration: none;
        border-radius: 2px;
    }
    </style>
<?php
$excelNm = "Find_Product_By_Color.Xls";
if ($P_CHECK=== "Y"){
  $excelNm = "Find_Product_By_Color_Check.Xls";
}
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : Find Color by Product </td>";
echo "</tr>";
echo "</table>";

$sql1 =
  "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION,NVL (CYCRL_DESCRIPTION_JUSTIFY, 'L') CYCRL_DESCRIPTION_JUSTIFY
          ,NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA
          ,CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,NVL (CYCRL_LENGTH_DECIMAL, '0') CYCRL_LENGTH_DECIMAL
          ,NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
          ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE,CYCRL_IS_BOLD,CYCRL_HIDE_SEQ_NO
      FROM mgtapps.cst_yarn_calc_rpt_lable b
      WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
      order by to_number(cycrl_seq_no) ";
//echo "$sql1 </br>";
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;

$SqlData = "select max(CYCRL_SEQ_NO) GET_DATA from ($sql1) ";
$MAX_CYCRL_SEQ_NO =  getData($conn,$SqlData);

echo "<table border='1'>";
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
  //check is RM Rate Data
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

  for ($xDtMtrl = 1; $xDtMtrl <= $TotLpDt; $xDtMtrl++) {
  //Description Data
  $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
  // $DataValue = "";
  // if (!empty($row1Data['CYCRL_SEQ_NO'])){
  //     if ($row1Data['CYCRL_HIDE_SEQ_NO']==="0"){ $DataValue = $row1Data['CYCRL_SEQ_NO']."."; }
  // }
  // if (!empty($row1Data['CYCRL_DESCRIPTION'])){
  //     $DataValue = $DataValue.$row1Data['CYCRL_DESCRIPTION'];
  //     if ($row1Data['CYCRL_HIDE_SEQ_NO']!=="0"){ $DataValue = $DataValue."." }
  // }
  $DataValue = printDescription(
                        $row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
                        ,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
                        ,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
                        );

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

  //$SheetWidth = 40;
  //$pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);
  if ($row1Data['CYCRL_IS_BOLD']==="Y"){
    echo "<tr><td><b>$DataValue</b></td>";
  } else {
    echo "<tr><td>$DataValue</td>";
  }
  //Description Data

  //Value Data
  $lpDt = 0;
  $totRM = $deepMaterial;
  while ( $totRM > $lpDt) {
    $lpDt++;
    if ($row1Data['CYCRL_SEQ_NO']==="1"){
        $ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
        $TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
    }
    $dtVal = "$deepMaterial : $lpDt";
    if ($totRM > $lpDt){
      $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysId_Acy ('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual ";
      $Product_dt =  getData($conn,$SqlData);
      $StrPos = strpos($Product_dt,"|");
      //echo "StrPos $StrPos</br>";
      $Product_Key = substr($Product_dt,0,$StrPos);
      //echo "Product_Key $Product_Key</br>";
      $CylSysId_dt =  substr($Product_dt,$StrPos+1);
      $dtVal = "";
      if ($xDtMtrl===1){
        $dtVal =  getDtVal( $conn
                            ,$CylSysId_dt
                            ,$sqlData
                            ,$row1Data['CYCRL_SOURCE_TYPE']
                            ,$row1Data['CYCRL_SOURCE_QUERY']
                            ,$row1Data['CYCRL_FORMAT_DATA']
                            ,$row1Data['CYCRL_LENGTH_DECIMAL']
                            ,$P_CYL_SYS_ID_DTL
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

      //$pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
      if ($row1Data['CYCRL_IS_BOLD']==="Y"){
        echo "<td><b>$dtVal</b></td>";
      } else {
        echo "<td>$dtVal</td>";
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
                          ,$P_CYL_SYS_ID_DTL
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
                            ,$P_CYL_SYS_ID_DTL
                          );
      }
      //$pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,1,$CYCRL_JUSTIFY);
      if ($row1Data['CYCRL_IS_BOLD']==="Y"){
        echo "<td><b>$dtVal</b></td>";
      } else {
        echo "<td>$dtVal</td>";
      }
      //echo "<td>$dtVal</td>";
    }

    //Process Cek
    //dtCek
    $dtCek = 0;
    if ( $dtVal!=="-" ){
     $dtCek = str_replace(",","",$dtVal);
    } else {
     $dtCek=0;
    }
    if ($dtCek==="NA" || $dtCek===""){
      $dtCek=0;
    }
    //dtCek
    if ($row1Data['CYCRL_SEQ_NO']==="6"){
      $TotalCostCaptive[$lpDt] = $dtCek;
      $TotalCostDel[$lpDt] = $dtCek;
      //echo "tambah total TotalCostCaptive[$lpDt] ".$TotalCostCaptive[$lpDt];
    }

    if (intval($row1Data['CYCRL_SEQ_NO'])>=36 && intval($row1Data['CYCRL_SEQ_NO'])<=52){
        $ConvCostCaptive[$lpDt] = floatval($ConvCostCaptive[$lpDt] ?? 0) + floatval($dtCek);//$ConvCostCaptive[$lpDt]=$ConvCostCaptive[$lpDt] + $dtCek;
        $ConvCostDel[$lpDt]=floatval($ConvCostDel[$lpDt] ?? 0) + floatval($dtCek);//$ConvCostDel[$lpDt]=$ConvCostDel[$lpDt] + $dtCek;
    }
    if (intval($row1Data['CYCRL_SEQ_NO'])===54 || intval($row1Data['CYCRL_SEQ_NO'])===56){
        $ConvCostCaptive[$lpDt]=$ConvCostCaptive[$lpDt] + $dtCek;
    }
    if (intval($row1Data['CYCRL_SEQ_NO'])===55 || intval($row1Data['CYCRL_SEQ_NO'])===57){
        $ConvCostDel[$lpDt]=$ConvCostDel[$lpDt] + $dtCek;
    }
    if ($row1Data['CYCRL_SEQ_NO']==="60"){
      $diffCaptive[$lpDt]=$dtCek;
    }
    if ($row1Data['CYCRL_SEQ_NO']==="61"){
      $diffDel[$lpDt]=$dtCek;
    }
    //Process Cek
  }
  //Value Data
  echo "</tr>";

  //Print Cek
  if ($P_CHECK=== "Y"){
    if ($row1Data['CYCRL_SEQ_NO']===$MAX_CYCRL_SEQ_NO){
      echo "<tr><td><p style='color:red'><b>CHECKING</b></td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        echo "<td></td>";
      }
      echo "</tr>";

      //Conv. Cost (Captive)
      echo "<tr><td>Conv. Cost (Captive)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        if ($ConvCostCaptive[$lpDtCek]!==0){
          echo "<td>".$ConvCostCaptive[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //Conv. Cost (Captive)
      //Conv. Cost (Del)
      echo "<tr><td>Conv. Cost (Del)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        if ($ConvCostDel[$lpDtCek]!==0){
          echo "<td>".$ConvCostDel[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //Conv. Cost (Del)
      //$TotalCostCaptive = =C77+$ConvCostCaptive
      echo "<tr><td>Total Cost (Captive)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        $TotalCostCaptive[$lpDtCek] = $TotalCostCaptive[$lpDtCek] + $ConvCostCaptive[$lpDtCek];
        if ($TotalCostCaptive[$lpDtCek]!==0){
          echo "<td>".$TotalCostCaptive[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //$TotalCostCaptive = =C77+$ConvCostCaptive
      //$TotalCostDel = ==RM Total+ConvCostDel
      echo "<tr><td>Total Cost (Del)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        $TotalCostDel[$lpDtCek] = $TotalCostDel[$lpDtCek] + $ConvCostDel[$lpDtCek];
        if ($TotalCostDel[$lpDtCek]!==0){
          echo "<td>".$TotalCostDel[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //$TotalCostCaptive = =C77+$ConvCostCaptive
      //$diffCaptive = Total Cost (Captive) - 60.CapCost with Q.Loss.
      echo "<tr><td>diff (Captive)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        $diffCaptive[$lpDtCek] = $TotalCostCaptive[$lpDtCek] - $diffCaptive[$lpDtCek];
        if ($diffCaptive[$lpDtCek]!==0){
          echo "<td>".$diffCaptive[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //$TotalCostCaptive = =C77+$ConvCostCaptive
      //$diffDel = Total Cost (Del) - 61.DelCost with Q.Loss.
      echo "<tr><td>diff (Del)</td></td>";
      $lpDtCek = 0;
      while ($deepMaterial > $lpDtCek) {
        $lpDtCek++;
        $diffDel[$lpDtCek] = $TotalCostDel[$lpDtCek] - $diffDel[$lpDtCek];
        if ($diffDel[$lpDtCek]!==0){
          echo "<td>".$diffDel[$lpDtCek]."</td>";
        } else {
          echo "<td></td>";
        }
      }
      echo "</tr>";
      //$diff (Del)
    }
  }//Print Cek

  }
}
echo "</table>";
?>
</table>
</body>
</html>
