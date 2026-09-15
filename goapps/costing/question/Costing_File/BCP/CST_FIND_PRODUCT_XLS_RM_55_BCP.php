<?php
  $SqlDepMtrl = "select COUNT(-1) GET_DATA
              from mgtapps.CST_LVL_LEFT_PROD
              where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'";
  $deepMaterial =  getData($conn,$SqlDepMtrl);

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
  $sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_MaxRm('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
	$maxRMDt = getData($conn,$sqlGet);
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

  $TotLpDt = 1;
  if ($row1Data['CYCRL_SEQ_NO']==="6"){
    $TotLpDt = $maxRMDt+1;
  }

  for ($x = 1; $x <= $TotLpDt; $x++) {
    //description
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
      if ($ckTypeDt === "From Group Item MKT Rate"
 				 || $ckTypeDt === "Stores" ){
 			 $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_IdReff('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
 		 } else{
 			 $sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
 		 }
      $ckCylSysIdDt = getData($conn,$sqlCk);

      $CylSysId_dt = $ckCylSysIdDt;

        //sdie();
        $dtVal = getDtVal_RM55(	$conn
                  ,$CylSysId_dt
                  ,$sqlData
                  ,$row1Data['CYCRL_SOURCE_TYPE']
                  ,$row1Data['CYCRL_SOURCE_QUERY']
                  ,$row1Data['CYCRL_FORMAT_DATA']
                  ,$row1Data['CYCRL_LENGTH_DECIMAL']
                  ,$row1Data['CYCRL_SEQ_NO']
                );
        // $dtVal = getDtVal($conn
        //                 ,$CylSysId_dt
        //                 ,$sqlData
        //                 ,$row1Data['CYCRL_SOURCE_TYPE']
        //                 ,$row1Data['CYCRL_SOURCE_QUERY']
        //                 ,$row1Data['CYCRL_FORMAT_DATA']
        //                 ,$row1Data['CYCRL_LENGTH_DECIMAL']
        //                 );
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

      if ($row1Data['CYCRL_IS_BOLD']==="Y"){
        $dtVal = "<b>$dtVal</b>";
      }
      if ($row1Data['CYCRL_SEQ_NO']==="6"){
        if ($TotLpDt === $x){

          if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
  					$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$CylSysId_dt') GET_DATA from dual";
  					//echo "$sqlGet";
  					$dtVal = getData($conn,$sqlGet);
  					$dtVal = setNumber($conn,"Number",$dtVal,3);
  				}
  				echo "<td>$dtVal</td>";
  			} else {

          $CylSysId_Rm = getData($conn
												 ,"SELECT CYRM_CYL_SYS_ID GET_DATA
													  FROM MGTAPPS.CST_YARN_CALCULATION a,
													       MGTAPPS.CST_YARN_RM_HDR b,
													       MGTAPPS.CST_YARN_RM_MULTI c
													 WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
													       AND cyc_top_no = 55
													       AND CYC_FORMULA_TYPE = 'Raw_Material'
													       AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
													       AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
													       AND CYRH_TYPE = 'Multi Yarn'
																 and CYRM_SEC_NO = $x
																 ");
					$dtVal = getData($conn
												 ,"select CYC_DATA_VALUE GET_DATA
							 						from cst_yarn_calculation t
							 						where cyc_cyl_sys_id = '$CylSysId_Rm'
							 						and cyc_top_no = 105 ");

          $dtVal = setNumber($conn,"Number",$dtVal,3);
          echo "<td>$dtVal</td>";
        }
      } else {

        if ($row1Data['CYCRL_SEQ_NO']==="7"){
  				if ($ckTypeDt === "From Group Item MKT Rate"|| $ckTypeDt === "Stores" ){
  					$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$CylSysId_dt') GET_DATA from dual";
  					//echo "$sqlGet";
  					$dtVal = getData($conn,$sqlGet);
  					$dtVal = setNumber($conn,"Number",$dtVal,3);
  					echo $dtVal;
  				}
  			}
        echo "<td>$dtVal</td>";
      }

      //Process Cek
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

//Product
  }//disini
}
echo "</table>";
?>
</table>
</body>
</html>
