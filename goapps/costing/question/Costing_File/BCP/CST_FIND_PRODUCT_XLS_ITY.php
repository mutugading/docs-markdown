<?php
$StsRM = "Store";

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
echo "deepMaterial $deepMaterial</br>"
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
          ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE,CYCRL_IS_BOLD
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
  if ($row1Data['CYCRL_SEQ_NO']==="6"){
    $TotLpDt = $totRM+1;
  } else {
    $TotLpDt = 1;
  }

  for ($x = 1; $x <= $TotLpDt; $x++) {
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
    if ($row1Data['CYCRL_IS_BOLD']==="Y"){
      echo "<tr><td><b>$DataValue</b></td>";
    } else {
      echo "<tr><td>$DataValue</td>";
    }

    //Product
    $lpDt = 0;
    while ($deepMaterial > $lpDt) {
      $lpDt++;
      if ($row1Data['CYCRL_SEQ_NO']==="1"){
          $ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
          $TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
      }

      if ($lpDt <= $totRM ){
        $dtVal = "-";
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
          //echo $SqlData;die();
          if ($TotLpDt===$x){
  	        $dtVal =  getData($conn,$SqlData);
  	        $dtVal = setNumber($conn,"Number",$dtVal,3);
  				}
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

      } else {
        $CylSysId_dt = $P_CYL_SYS_ID_DTL;
        $dtVal = getDtVal($conn
                          ,$CylSysId_dt
                          ,$sqlData
                          ,$row1Data['CYCRL_SOURCE_TYPE']
                          ,$row1Data['CYCRL_SOURCE_QUERY']
                          ,$row1Data['CYCRL_FORMAT_DATA']
                          ,$row1Data['CYCRL_LENGTH_DECIMAL']
                          );

        if ($row1Data['CYCRL_SEQ_NO']==="6" && $TotLpDt>$x ){
  				$SqlData = " SELECT case when CGCH_MARKET_RATE2_FIX is not null then
                                    CGCH_MARKET_RATE2_FIX
                              else CGCH_MARKET_RATE2
                              end GET_DATA
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
  													 AND CYRM_SEC_NO = $x
  													 and CGCH_PERIOD_YEAR = $yearPrs
  													 and CGCH_PERIOD_MONTH = $monthPrs
  													 and CYRM_MARKETING_CODE = CGCH_CGH_SYS_ID
  													 ";
  				$dtVal =  getData($conn,$SqlData);
  				$dtVal = setNumber($conn,"Number",$dtVal,3);
  			}
      }

      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        $TotalCostCaptive[$lpDt] = $dtVal;
        $TotalCostDel[$lpDt] = $dtVal;
        //echo "tambah total TotalCostCaptive[$lpDt] ".$TotalCostCaptive[$lpDt];
      }

      //$ConvCostCaptive =SUM(C38:C54)+C56+C58
      //$ConvCostDel = =SUM(C38:C54)+C57+C59
      $dtCek = 0;
      if ($dtVal!=="-"){
       $dtCek = str_replace(",","",$dtVal);
      }
      if (intval($row1Data['CYCRL_SEQ_NO'])>=36 && intval($row1Data['CYCRL_SEQ_NO'])<=52){
          $ConvCostCaptive[$lpDt]=$ConvCostCaptive[$lpDt] + $dtCek;
          $ConvCostDel[$lpDt]=$ConvCostDel[$lpDt] + $dtCek;
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

      if ($row1Data['CYCRL_IS_BOLD']==="Y"){
        echo "<td><b>$dtVal</b></td>";
      } else {
        echo "<td>$dtVal</td>";
      }

      if ($deepMaterial === $lpDt){
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
          }
          //Print Cek
      }

    }
}
//Product
}

echo "</table>";
?>
</table>
</body>
</html>
