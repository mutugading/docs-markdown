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
  $totRM = $deepMaterial+1;
  while ( $totRM > $lpDt) {
    $lpDt++;
    if ($row1Data['CYCRL_SEQ_NO']==="1"){
        $ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
        $TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
    }
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
    //Process Cek

  }
  //Value Data
  echo "</tr>";

  //Print Cek
  if ($P_CHECK=== "Y"){
    if ($row1Data['CYCRL_SEQ_NO']===$MAX_CYCRL_SEQ_NO){
      echo "<tr><td><p style='color:red'><b>CHECKING</b></td></td>";
      $lpDtCek = 0;
      while (($deepMaterial+1) > $lpDtCek) {
        $lpDtCek++;
        echo "<td></td>";
      }
      echo "</tr>";
      //Conv. Cost (Captive)
      echo "<tr><td>Conv. Cost (Captive)</td></td>";
      $lpDtCek = 0;
      while (($deepMaterial+1) > $lpDtCek) {
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
      while (($deepMaterial+1) > $lpDtCek) {
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
      while (($deepMaterial+1) > $lpDtCek) {
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
      while (($deepMaterial+1) > $lpDtCek) {
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
      while (($deepMaterial+1) > $lpDtCek) {
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
      while (($deepMaterial+1) > $lpDtCek) {
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
echo "</table>";
?>
</table>
</body>
</html>
