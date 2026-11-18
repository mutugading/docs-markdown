<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("../conOraOci.php");
include ("../check_login.php");
include ("../common_function.php");
include ("FORM_NAME.php");

$P_DATE_FROM_S= "";
  if(isset($_POST['P_DATE_FROM_S'])) {
    $P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];
  }

if ($P_DATE_FROM_S=== ""){
  if(isset($_GET['P_DATE_FROM'])) {
    $P_DATE_FROM_S=$_GET['P_DATE_FROM'];
  }
}

$P_FORM_NAME_S="";
if ($P_FORM_NAME_S=== ""){
  if(isset($_GET['P_FORM_NAME'])) {
    $P_FORM_NAME_S=$_GET['P_FORM_NAME'];
  }
}
include($P_FORM_NAME_S."_R03_SQL.PHP");

$P_REPORT_ID_S="";
if ($P_REPORT_ID_S=== ""){
  if(isset($_GET['P_REPORT_ID'])) {
    $P_REPORT_ID_S=$_GET['P_REPORT_ID'];
  }
}

$P_REPORT_NAME_S = getData($conn,"select DESCRIPTION GET_DATA from mst_reports where REPORT_ID = '$P_REPORT_ID_S'");

$SLCT_CC_003_R003_S="";
if ($SLCT_CC_003_R003_S=== ""){
  if(isset($_GET['SLCT_CC_003_R003_S'])) {
    $SLCT_CC_003_R003_S=$_GET['SLCT_CC_003_R003_S'];
  }
}
$SLCT_CC_003_R003_I_S="";
if ($SLCT_CC_003_R003_I_S=== ""){
  if(isset($_GET['SLCT_CC_003_R003_I_S'])) {
    $SLCT_CC_003_R003_I_S=$_GET['SLCT_CC_003_R003_I_S'];
  }
}

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
$excelNm = $P_REPORT_NAME_S."_".$P_DATE_FROM_S.".Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr><td>Report : $P_REPORT_NAME_S </td></tr>";
echo "<tr><td>Tanggal Kupon $P_DATE_FROM_S </td></tr>";
if ($SLCT_CC_003_R003_S!=="NULL"){
  echo "<tr><td>$SLCT_CC_003_R003_S like %$SLCT_CC_003_R003_I_S% </td></tr>";
}
echo "<tr></tr>";
echo "</table>";
?>
    <table border="1">
        <tr>
            <td>No</td>
            <td>DATE</td>
            <td>NIK</td>
            <td>NAME</td>
            <td>DEPT</td>
            <td>SCHEDULER OVERTIME</td>
            <td>ENTRY BY</td>
            <td>ACTUAL OVERTIME</td>
        </tr>
<?php
  /*$sqlView =  "select rownum RECNO,ECO_SYS_ID,ECO_CREATED_BY,ECO_PE_NIK,ECO_DATE,CREATE_BY_NAME,ECO_MPSM_SCH,ECO_MPSM_SCH_DESC,ECO_RELIEVER_STS
               from ($sqlData order by ECO_CREATED_TIMESTAMP)";
  if ($SLCT_CC_003_R003_S==="NIK"){
    $sqlView =  $sqlView." where upper(ECO_PE_NIK) like '%'||upper('$SLCT_CC_003_R003_I_S')||'%' "  ;
  }

  if ($SLCT_CC_003_R003_S==="NAME"){
    $sqlView =  $sqlView." where upper(ECO_PE_NAMA) like '%'||upper('$SLCT_CC_003_R003_I_S')||'%' " ;
  }*/
  include("CC_003_R03_WHERE.PHP");
  $rsData = oci_parse($conn,$sqlView);
  oci_execute ($rsData);
  $no = 0;
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $no = $no + 1;
    echo"<tr>";
    echo"<td>".$rowData['RECNO']."</td>";
    echo"<td>".$rowData['ECO_DATE']."</td>";
    echo"<td>".$rowData['ECO_PE_NIK']."</td>";

    $P_PE_NIK   = $rowData['ECO_PE_NIK'];
    include("../GET_DATA_EMP.PHP");
    if ($rowData['ECO_RELIEVER_STS']==="Y"){
      $P_PE_NAMA = $P_PE_NAMA." (RELIEVER)";
    }
    echo"<td>$P_PE_NAMA</td>";
    echo"<td>$P_PE_DEPT_ID</td>";
    echo"<td>".$rowData['ECO_MPSM_SCH']." ".$rowData['ECO_MPSM_SCH_DESC']."</td>";
    echo"<td>".$rowData['CREATE_BY_NAME']."</td>";
    $ActlOvrTm = getData($conn
                  ,"select to_char(ECD_ACTUAL_BREAKTIME,'DD-MM-YYYY HH24:MI') GET_DATA from emp_coupon_data
                  where ECD_ECO_SYS_ID='".$rowData['ECO_SYS_ID']."'");
    echo"<td>$ActlOvrTm</td>";
    echo"</tr>";
  }
?>
</table>
</body>
</html>
