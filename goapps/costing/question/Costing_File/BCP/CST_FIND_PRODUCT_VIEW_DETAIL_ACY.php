<?php
// $SqlData = getSqlDeepMelange($P_CYL_SYS_ID_DTL);
// $deepMaterial =  getData($conn,$SqlData);
$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepLvl_Acy('$P_CYL_SYS_ID_DTL') GET_DATA from dual ";
$deepMaterial =  getData($conn,$SqlData);
//echo " $SqlData <br> deepMaterial $deepMaterial";die();
?>
<div class="main" style="overflow-x:auto;" align="center" >
<table style="width:90%" border="1">
<?php
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
if ($USER_NAME==="1949"){echo "P_CYCRM_SYS_ID $P_CYCRM_SYS_ID"; }
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
  //check is RM Rate Data
  if ($row1Data['CYCRL_SEQ_NO']==="6"){
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
    if (!empty($row1Data['CYCRL_SEQ_NO'])){
        $DataValue = $row1Data['CYCRL_SEQ_NO'];
    }
    if (!empty($row1Data['CYCRL_DESCRIPTION'])){
        $DataValue = $DataValue.".".$row1Data['CYCRL_DESCRIPTION'];
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
?>
<tr>
  <td>
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
  $totRM = $deepMaterial;
  while ( $totRM > $lpDt) {
    $lpDt++;
    $dtVal = "$deepMaterial : $lpDt";
    if ($totRM > $lpDt){
      // = getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$lpDt);
      $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysId_Acy ('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual ";
      $Product_dt =  getData($conn,$SqlData);
      $StrPos = strpos($Product_dt,"|");
      $Product_Key = substr($Product_dt,0,$StrPos);
      $CylSysId_dt =  substr($Product_dt,$StrPos+1);
      //echo "$SqlData --> $CylSysId_dt";die();
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

?>
    <td align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
      isBold($row1Data['CYCRL_IS_BOLD'],1);
      echo $dtVal;//print value
      isBold($row1Data['CYCRL_IS_BOLD'],2);
?>
    </td>
<?php
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
      }
      //echo "<td>$dtVal</td>";
      ?>
          <td align="<?php echo $CYCRL_JUSTIFY; ?>" >
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
