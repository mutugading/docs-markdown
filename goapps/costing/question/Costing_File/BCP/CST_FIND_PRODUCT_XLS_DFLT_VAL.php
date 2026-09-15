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
$SqlData = "select MGTAPPS.pkg_yarn_valuation.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
$deepMaterial =  getData($conn,$SqlData);

$excelNm = "Find_Product_By_Color.Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr><td>Report : Find Color by Product </td></tr>";
echo "<tr><td>Report : CST_FIND_PRODUCT_XLS_DFLT</td></tr>";
echo "</table>";

$sqlDataVal =	sqlDataVal($P_PERIOD_DATA);

$sql1 =
  "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION,NVL (CYCRL_DESCRIPTION_JUSTIFY, 'L') CYCRL_DESCRIPTION_JUSTIFY
          ,NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA
          ,CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,NVL (CYCRL_LENGTH_DECIMAL, '0') CYCRL_LENGTH_DECIMAL
          ,NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
          ,nvl(replace(CYCRL_SOURCE_QUERY,'MGTAPPS.pkg_yarn_calculation.fPrsIDMkt','MGTAPPS.pkg_yarn_calculation.fPrsIDVal'),'NULL') CYCRL_SOURCE_QUERY
          ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE,CYCRL_IS_BOLD
      FROM mgtapps.cst_yarn_calc_rpt_lable b
      WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
      order by to_number(cycrl_seq_no) ";
//echo "$sql1 </br>";
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
// $SheetHeight = 3.5;
// $SheetWidth = 50;
echo "<table border='1'>";
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
  $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
  if (!empty($row1Data['CYCRL_SEQ_NO'])){
      $DataValue = $row1Data['CYCRL_SEQ_NO'].".";
  }

  if (!empty($row1Data['CYCRL_DESCRIPTION'])){
      $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
  }
  echo "<tr><td>$DataValue</td>";
  //$pdf->Cell($SheetWidth,$SheetHeight,$DataValue,1,0,$CYCRL_DESCRIPTION_JUSTIFY);

  //Product
  $lpDt = 0;
  while ($deepMaterial > $lpDt) {
    $lpDt++;
    $SqlData = "select MGTAPPS.pkg_yarn_valuation.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt) GET_DATA from dual";
    $CylSysId_dt =  getData($conn,$SqlData);
    $dtVal =
            getDtVal_Valuation(	$conn
    									,$CylSysId_dt
    									,$P_PERIOD_DATA
    									,$row1Data['CYCRL_SEQ_NO']
    									,$sqlDataVal
    									,$row1Data['CYCRL_SOURCE_TYPE']
    									,$row1Data['CYCRL_SOURCE_QUERY']
    									,$row1Data['CYCRL_FORMAT_DATA']
    									,$row1Data['CYCRL_LENGTH_DECIMAL']
    								);
    if ($deepMaterial > $lpDt){
      //$pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,0,$CYCRL_JUSTIFY);
        echo "<td>$dtVal</td>";
    } else {//$pdf->Cell($SheetWidth,$SheetHeight,$dtVal,1,1,$CYCRL_JUSTIFY);
        echo "<td>$dtVal</td>";
    }
  }
  echo "</tr>";
//Product
}
echo "</table>";
?>
</table>
</body>
</html>
