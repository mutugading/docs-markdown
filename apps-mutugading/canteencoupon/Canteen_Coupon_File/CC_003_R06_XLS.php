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

$P_DATE_TO_S= "";
  if(isset($_POST['P_DATE_TO_S'])) {
    $P_DATE_TO_S=$_POST['P_DATE_TO_S'];
  }

if ($P_DATE_TO_S=== ""){
  if(isset($_GET['P_DATE_TO'])) {
    $P_DATE_TO_S=$_GET['P_DATE_TO'];
  }
}

$P_FORM_NAME_S="";
if ($P_FORM_NAME_S=== ""){
  if(isset($_GET['P_FORM_NAME'])) {
    $P_FORM_NAME_S=$_GET['P_FORM_NAME'];
  }
}
include($P_FORM_NAME_S."_R04_SQL.PHP");

$P_REPORT_ID_S="";
if ($P_REPORT_ID_S=== ""){
  if(isset($_GET['P_REPORT_ID'])) {
    $P_REPORT_ID_S=$_GET['P_REPORT_ID'];
  }
}

$P_REPORT_NAME_S = getData($conn,"select DESCRIPTION GET_DATA from mst_reports where REPORT_ID = '$P_REPORT_ID_S'");

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
$excelNm = $P_REPORT_NAME_S."_".$P_DATE_FROM_S."_to_". $P_DATE_TO_S.".Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : $P_REPORT_NAME_S </td>";
echo "</tr>";
echo "<tr>";
echo "<td>Tanggal Kupon dari $P_DATE_FROM_S s/d $P_DATE_TO_S </td>";
echo "</tr>";
echo "<tr></tr>";
echo "</table>";
?>
    <table border="1">
        <tr>
            <td>No</td>
            <td>Hari</td>
            <td>Tanggal</td>
            <td>Shift 3</td>
        </tr>

<?php
 $totPgS2 = 0;
  $sqlView =  "select rownum RECNO,t.* from ($sqlData) t ";
  $rsData = oci_parse($conn,$sqlView);
  oci_execute ($rsData);
  $no = 0;
  $TotKntn = 0;   $TotHrm=0; $TotAll=0;
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $totPgS2 = $totPgS2+$rowData['ECDS_TOT_SHIFT_2'];

    $no = $no + 1;
    echo"<tr>";
    echo"<td>".$rowData['RECNO']."</td>";
    echo"<td>".$rowData['HARI']."</td>";
    echo"<td>".$rowData['ECDS_DATE_1']."</td>";
    echo"<td>".$rowData['ECDS_TOT_SHIFT_2']."</td>";
    echo"</tr>";
  }
    echo"<tr>";
    echo"<td></td>";
    echo"<td></td>";
    echo"<td>Total</td>";
    echo"<td>$totPgS2</td>";
    echo"</tr>";
?>
</table>
</body>
</html>
