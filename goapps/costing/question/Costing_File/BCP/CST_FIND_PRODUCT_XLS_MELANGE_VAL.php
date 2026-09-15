<?php
// $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";
// $deepMaterial =  getData($conn,$SqlData);
$SqlData = getSqlDeepMelange($P_CYL_SYS_ID_DTL);
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
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : Find Color by Product </td>";
echo "</tr>";
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
echo "<table border='1'>";
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
  echo "<tr><td>$DataValue</td>";
  //Description Data

  //Value Data
  $lpDt = 0;
  $totRM = $deepMaterial+1;
  while ( $totRM > $lpDt) {
    $lpDt++;
    $dtVal = "$deepMaterial : $lpDt";
    if ($totRM > $lpDt){
      //$CylSysId_dt = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$lpDt);
      $CylSysId_dt = getCylSysIdRMMelangeVal($conn,$P_CYL_SYS_ID_DTL,$lpDt);
      $dtVal = "";
      if ($x===1){
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
      }
      echo "<td>$dtVal</td>";
    } else {
      //Left No MILANGE
      $CylSysId_dt =  $P_CYL_SYS_ID_DTL;
      $dtVal = "";
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

      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        if ($x < $totRM ){
          //$CylSysId_RM = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$x);
          $CylSysId_dt = getCylSysIdRMMelangeVal($conn,$P_CYL_SYS_ID_DTL,$lpDt);
          $SqlData = "select CYC_DATA_VALUE GET_DATA
                      from cst_yarn_calculation t
                      where cyc_cyl_sys_id = '$P_CYCRM_SYS_ID'
                      and cyc_top_no = 105";
          $dtVal = getData($conn,$SqlData);

          $dtVal = setNumber($conn,"Number",$dtVal,3);
        }
      }
      echo "<td>$dtVal</td>";
    }
  }
  //Value Data
  echo "</tr>";
  }
}
echo "</table>";
?>
</table>
</body>
</html>
