<?php
  $SqlDepMtrl = "select COUNT(-1) GET_DATA
              from mgtapps.CST_LVL_LEFT_PROD
              where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'";
  $deepMaterial =  getData($conn,$SqlDepMtrl);

  $sqlCkType = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'";
  $CYL_TYPE_CK = getData($conn,$sqlCkType);

  //get tot Max RM
	$SqlData = "select max(count(-1)) GET_DATA
							from mgtapps.CST_LVL_LEFT_PROD a
							where A.CLLP_CYL_SYS_ID_REFF ='$P_CYL_SYS_ID_DTL'
							and CLLP_TYPE_RM = 'Multi Yarn'
							group by CLLP_LEFT_NO_REFF";
  //echo "Max RM $SqlData";
	$maxRm =  getData($conn,$SqlData);
  if ($maxRm===""){
    $maxRm = 0;
  }

//  echo "maxRm $maxRm </br>";die();

  $sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_MaxRm('$P_CYL_SYS_ID_DTL') GET_DATA from dual";

  //echo "$sqlGet </br>";die();

	$maxRMDt = getData($conn,$sqlGet);
  $TotRmDt = $maxRMDt;
	//Get tot Max RM

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
//echo "$sql1 </br>";die();
$rs1Data = oci_parse($conn,$sql1);
oci_execute ($rs1Data);
$totRows = 0;
$totData = 0;
$SqlData = "select max(CYCRL_SEQ_NO) GET_DATA from ($sql1) ";
$MAX_CYCRL_SEQ_NO =  getData($conn,$SqlData);
$Product_Key="";
echo "<table border='1'>";
while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {

  $TotLpDt = 1;
  // if ($row1Data['CYCRL_SEQ_NO']==="6"){
  //   $TotLpDt = $maxRMDt+1;
  // }
  if ($row1Data['CYCRL_SEQ_NO']==="6"){
    if ($CYL_TYPE_CK==="ACY"){
      $TotLpDt = $maxRMDt+5;
    }else {
      $TotLpDt = $maxRMDt+1;
    }
  }else if ($row1Data['CYCRL_SEQ_NO']==="17"){
    if ($CYL_TYPE_CK==="ACY"){
      $TotLpDt = 2;
    }
  }

  for ($x = 1; $x <= $TotLpDt; $x++) {
    //description
    $CYCRL_DESCRIPTION_JUSTIFY  = $row1Data['CYCRL_DESCRIPTION_JUSTIFY'];
    // $DataValue = "";
    // if (!empty($row1Data['CYCRL_SEQ_NO'])){
    //     if ($row1Data['CYCRL_HIDE_SEQ_NO']==="0"){ $DataValue = $row1Data['CYCRL_SEQ_NO']."."; }
    // }
    // if (!empty($row1Data['CYCRL_DESCRIPTION'])){
    //     $DataValue = "$DataValue".$row1Data['CYCRL_DESCRIPTION'].".";
    // }
    $DataValue = printDescription(
                          $row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
                          ,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
                          ,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
                          );

    if ($row1Data['CYCRL_SEQ_NO']==="6"){
      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
      if ($TotLpDt===$x){
        $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
      }
      if ($TotRmDt>1){
        if ($TotRmDt<$x){
          if ($x - $TotRmDt === 1){ $DataValue = "Consume % of DTY"; } if ($x - $TotRmDt === 2){ $DataValue = "Consume % of SPD"; }
          if ($x - $TotRmDt === 3){ $DataValue = "Consume value of DTY"; } if ($x - $TotRmDt === 4){ $DataValue = "Consume value of SPD"; }
          if ($x - $TotRmDt === 5){ $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total"; }
        }
      }
    }
    if ($row1Data['CYCRL_SEQ_NO']==="17"){
      if ($x === 2){
          $DataValue = "Draw Ratio";
      }
    }

    if ($row1Data['CYCRL_IS_BOLD']==="Y"){
      echo "<tr><td><b>$DataValue</b></td>";
    } else {
      echo "<tr><td>$DataValue</td>";
    }
    //description

    //Product
    $lpDt = 0;
    //echo "deepMaterial $deepMaterial </br> SqlDepMtrl $SqlDepMtrl" ;die();
    while ($deepMaterial > $lpDt) {
      $lpDt++;
      if ($row1Data['CYCRL_SEQ_NO']==="1"){
          $ConvCostCaptive[$lpDt]=0; $ConvCostDel[$lpDt]=0; $TotalCostCaptive[$lpDt]=0;
          $TotalCostDel[$lpDt] = 0; $diffCaptive[$lpDt]=0; $diffDel[$lpDt]=0;
      }
      //Check Type Data
      $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_TypeData('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      $ckTypeDt = getData($conn,$sqlCk);
      //Check Left No
      $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylLeftNo('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      $ckLeftNoDt = getData($conn,$sqlCk);
      //Check Cyl Sys Id
      if ($ckTypeDt === "From Group Item MKT Rate"){
 			 $sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_IdReff('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      }else if ($ckTypeDt === "Stores"){
 			 $sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_StoreId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
 		  } else{
       $sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
      }
      $ckCylSysIdDt = getData($conn,$sqlCylSysIdDt);

      $CylSysId_dt = $ckCylSysIdDt;
      //if ($row1Data['CYCRL_SEQ_NO']==="76"){echo "cek 1<br>";}
      //sdie();
      $dtVal = getDtVal_RM55(	$conn
                ,$CylSysId_dt
                ,$sqlData
                ,$row1Data['CYCRL_SOURCE_TYPE']
                ,$row1Data['CYCRL_SOURCE_QUERY']
                ,$row1Data['CYCRL_FORMAT_DATA']
                ,$row1Data['CYCRL_LENGTH_DECIMAL']
                ,$row1Data['CYCRL_SEQ_NO']
                ,$P_CYL_SYS_ID_DTL
              );
      //dtCek
      $dtCek = 0;
      if (is_numeric($dtVal)){
        $dtCek = $dtVal;
      }
      //dtCek

      if ($row1Data['CYCRL_SEQ_NO']==="1"){
        //echo "$ckTypeDt<b>";
        if ($ckTypeDt === "From Group Item MKT Rate"
            || $ckTypeDt === "Stores" ){
          $sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm('$CylSysId_dt') GET_DATA from dual";
          //echo "$sqlGet";
          $dtVal = getData($conn,$sqlGet);
          //echo "$dtVal";
        }
      }

      if ($row1Data['CYCRL_SEQ_NO']==="17"){
        if ($x === 2 && $Product_Key==="Stores"){
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
          $dtVal =  getData($conn,$SqlData);
        }
      }

      if ($row1Data['CYCRL_SEQ_NO']==="6"){

        //process seq no 6
          //cek prod use multi RM
          $sqlGet =
          "SELECT  'Y' GET_DATA
           FROM MGTAPPS.CST_YARN_CALCULATION a,
                MGTAPPS.CST_YARN_RM_HDR b
           WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
                 AND cyc_top_no = 55
                 AND CYC_FORMULA_TYPE = 'Raw_Material'
                 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                 AND CYRH_TYPE = 'Multi Yarn'
          ";
          //echo "$sqlGet";die();
          $isRmMulti = getData($conn,$sqlGet);
          if ($isRmMulti!=="Y"){
            if ($TotLpDt !== $x ){
              $dtVal = "";
            } else {
              if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
                $sqlGet = "select
                              MGTAPPS.fSetDecimal(MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$CylSysId_dt'),3)
                              GET_DATA
                           from dual";
                //echo "$sqlGet";
                $dtVal = getData($conn,$sqlGet);
                //$dtVal = setNumber($conn,"Number",$dtVal,3);
              }
            }
          } else {
            if ($x<=$TotRmDt){
              //echo "TotRmDt $TotRmDt : TotLpDt $TotLpDt : x $x : lpDt $lpDt<br>";
              $SqlData = "
                          SELECT MGTAPPS.fSetDecimal(CYRM_RM_RATE,3)  GET_DATA
                            FROM mgtapps.cst_yarn_left l,
                                 mgtapps.cst_yarn_calculation a,
                                 mgtapps.cst_yarn_rm_hdr b,
                                 mgtapps.cst_yarn_rm_multi c
                           WHERE     L.CYL_SYS_ID = '$CylSysId_dt'
                                 AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                                 AND A.CYC_TOP_NO = 55
                                 AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                                 AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                                 and CYRM_SEC_NO = $x
                          ";
              //$dtVal =  getData($conn,$SqlData)."<br>$isRmMulti $$isRmMulti : CylSysId_dt $CylSysId_dt";
              $dtVal =  getData($conn,$SqlData);
            }
            if ($x - $TotRmDt === 1){
              $SqlData = "
                          SELECT
                            MGTAPPS.fSetDecimal(CYRM_CONSUME,3) GET_DATA
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
              //echo "1 $SqlData<br>";
              //$RmRate =  getData($conn,$SqlData);
              $dtVal = getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
            }
            if ($x - $TotRmDt === 2){
              $SqlData = "
                          SELECT MGTAPPS.fSetDecimal(CYRM_CONSUME,3)  GET_DATA
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
              //echo "2 $SqlData<br>";
              //$RmRate =  getData($conn,$SqlData);
              $dtVal =  getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
            }
            if ($x - $TotRmDt === 3){
              $SqlData = "
                          SELECT  MGTAPPS.fSetDecimal(CYRM_RM_COST,3) GET_DATA
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
              //$RmRate =  getData($conn,$SqlData);
              //echo "3 $SqlData<br>";
              $dtVal = getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
            }
            if ($x - $TotRmDt === 4){
              $SqlData = "
                          SELECT MGTAPPS.fSetDecimal(CYRM_RM_COST,3 ) GET_DATA
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
              //echo "4 $SqlData<br>";
              $RmRate =  getData($conn,$SqlData);
              $dtVal = $RmRate;
            }
          }

        //process seq no 6

      } else {

        if ($row1Data['CYCRL_SEQ_NO']==="7"){
  				if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
  					$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$CylSysId_dt') GET_DATA from dual";
  					//echo "$sqlGet";
  					$dtVal = getData($conn,$sqlGet);
  					$dtVal = setNumber($conn,"Number",$dtVal,3);
  				}
  			}

      }

      if ($row1Data['CYCRL_IS_BOLD']==="Y"){
        $dtVal = "<b>$dtVal</b>";
      }
      echo "<td>$dtVal</td>";
    }
    echo "</tr>";

    if ($row1Data['CYCRL_SEQ_NO']===1){
        echo "Cek proses";die();
    }
//Product
  }//disini
}
echo "</table>";
?>
</table>
</body>
</html>
