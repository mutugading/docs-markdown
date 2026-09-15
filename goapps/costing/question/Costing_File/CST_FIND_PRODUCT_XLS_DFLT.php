<?php
$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
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
  $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
  // $DataValue = "";
  // if (!empty($row1Data['CYCRL_SEQ_NO'])){
  //   if ($row1Data['CYCRL_HIDE_SEQ_NO']==="0"){ $DataValue = $row1Data['CYCRL_SEQ_NO']."."; }
  // }
  // if (!empty($row1Data['CYCRL_DESCRIPTION'])){
  //     $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
  // }
  $DataValue = printDescription(
                        $row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
                        ,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
                        ,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
                        );
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
    $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl($P_CYL_SYS_ID_DTL,$deepMaterial,$lpDt) GET_DATA from dual";
    $CylSysId_dt =  getData($conn,$SqlData);
    $dtVal = getDtVal($conn
                      ,$CylSysId_dt
                      ,$sqlData
                      ,$row1Data['CYCRL_SOURCE_TYPE']
                      ,$row1Data['CYCRL_SOURCE_QUERY']
                      ,$row1Data['CYCRL_FORMAT_DATA']
                      ,$row1Data['CYCRL_LENGTH_DECIMAL']
                      ,$P_CYL_SYS_ID_DTL
                      );

    if ($row1Data['CYCRL_IS_BOLD']==="Y"){
      echo "<td><b>$dtVal</b></td>";
    } else {
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
