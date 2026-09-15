<?php
//echo "masuk MELANGE</br>";
$SqlData = getSqlDeepMelange($P_CYL_SYS_ID);
//echo "SqlData $SqlData</br>";
$deepMaterial =  getData($conn,$SqlData);
?>
<div class="main" style="overflow-x:auto;" align="center" >
<table style="width:90%" border="1">
<?php
$sql1 = getSqlDesc($P_CYCRM_SYS_ID)
  // "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION,NVL (CYCRL_DESCRIPTION_JUSTIFY, 'L') CYCRL_DESCRIPTION_JUSTIFY
  //         ,NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA
  //         ,CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,NVL (CYCRL_LENGTH_DECIMAL, '0') CYCRL_LENGTH_DECIMAL
  //         ,NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
  //         ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE,CYCRL_IS_BOLD
  //     FROM mgtapps.cst_yarn_calc_rpt_lable b
  //     WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID' and cycrl_seq_no <= 74
  //     order by to_number(cycrl_seq_no) "
  ;
//echo "$sql1 </br>";
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
$SheetHeight = 3.5;
$SheetWidth = 50;
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
      $DataValue = $row1Data['CYCRL_SEQ_NO'];
  }
  if (!empty($row1Data['CYCRL_DESCRIPTION'])){
      $DataValue = $DataValue.".".$row1Data['CYCRL_DESCRIPTION'];
  }

  if ($row1Data['CYCRL_SEQ_NO']==="6"){
    $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
    if ($TotLpDt===$x){
      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
    }
  }
?>
<tr>
  <td style="width:15%;font-size:<?php echo $font; ?>px;">
<?php
    isBold($row1Data['CYCRL_IS_BOLD'],1);
    //if (!empty($row1Data['CYCRL_SEQ_NO'])){ echo $row1Data['CYCRL_SEQ_NO'].".";}

    echo $DataValue.".";
    isBold($row1Data['CYCRL_IS_BOLD'],2);
?>
  </td>
<?php
  $CYCRL_JUSTIFY = "LEFT";
  if (!empty($row1Data['CYCRL_JUSTIFY'])){
    if ($row1Data['CYCRL_JUSTIFY']==="L") {
      $CYCRL_JUSTIFY = "LEFT";
    }
    if ($row1Data['CYCRL_JUSTIFY']==="R") {
      $CYCRL_JUSTIFY = "RIGHT";
    }
    if ($row1Data['CYCRL_JUSTIFY']==="C") {
      $CYCRL_JUSTIFY = "CENTER";
    }
  }
  $lpDt = 0;
  $totRM = $deepMaterial+1;
  while ( $totRM > $lpDt) {
    $lpDt++;
    $dtVal = "$deepMaterial : $lpDt";
    if ($totRM > $lpDt){
      $CylSysId_dt = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID,$lpDt);
      $dtVal = "";
      if ($x===1){
        $dtVal =  getDtVal( $conn
                            ,$CylSysId_dt
                            ,$sqlData
                            ,$row1Data['CYCRL_SOURCE_TYPE']
                            ,$row1Data['CYCRL_SOURCE_QUERY']
                            ,$row1Data['CYCRL_FORMAT_DATA']
                            ,$row1Data['CYCRL_LENGTH_DECIMAL']
														,$row1Data['CYCRL_SEQ_NO']
														,$P_CYL_SYS_ID
                          );
      }
?>
    <td style="width:15%;font-size:<?php echo $font; ?>px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
      isBold($row1Data['CYCRL_IS_BOLD'],1);
      echo $dtVal;//print value
      isBold($row1Data['CYCRL_IS_BOLD'],2);
?>
    </td>
<?php
    } else {
      //Left No MILANGE
      $CylSysId_dt =  $P_CYL_SYS_ID;
      $dtVal = "";
      $dtVal =  getDtVal( $conn
                          ,$CylSysId_dt
                          ,$sqlData
                          ,$row1Data['CYCRL_SOURCE_TYPE']
                          ,$row1Data['CYCRL_SOURCE_QUERY']
                          ,$row1Data['CYCRL_FORMAT_DATA']
                          ,$row1Data['CYCRL_LENGTH_DECIMAL']
													,$row1Data['CYCRL_SEQ_NO']
													,$P_CYL_SYS_ID
                        );

      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        if ($x < $totRM ){
          $CylSysId_RM = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID,$x);
          $SqlData = "select CYC_DATA_VALUE GET_DATA
                      from cst_yarn_calculation t
                      where cyc_cyl_sys_id = '$CylSysId_RM'
                      and cyc_top_no = 105";
          $dtVal = getData($conn,$SqlData);

          $dtVal = setNumber($conn,"Number",$dtVal,3);
        }
      }
      //echo "<td>$dtVal</td>";
      ?>
          <td style="width:15%;font-size:<?php echo $font; ?>px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
      <?php
            isBold($row1Data['CYCRL_IS_BOLD'],1);
            echo $dtVal;//print value
            isBold($row1Data['CYCRL_IS_BOLD'],2);
      ?>
          </td>
      <?php
    }
  }
  //Value Data
  echo "</tr>";
  }
}
echo "</table>";
?>
</table>
</div>
