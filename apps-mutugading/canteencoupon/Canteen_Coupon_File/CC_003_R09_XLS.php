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
include($P_FORM_NAME_S."_R09_SQL.PHP");

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
            <td>TGL Kupon</td>
            <td>NIK</td>
            <td>Waktu Scan</td>
            <td>Keterangan Process</td>
            <td>Keterangan HRM</td>
            <td>Update Oleh</td>
        </tr>

<?php
  $totPgS1 = 0; $totPgGs = 0; $totPgS2 = 0; $totPgS3 = 0; $totPgOths = 0; $totPgGrd = 0;

  include($P_FORM_NAME_S."_R09_SQL.PHP");
  $sqlView = " select rownum NOREC,d.* from ($sqlData)d ";

  //$sqlView =  "select rownum RECNO,t.* from ($sqlData) t ";
  $rsData = oci_parse($conn,$sqlView);
  oci_execute ($rsData);
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    echo"<tr>";
    echo"<td>".$rowData['NOREC']."</td>";
    echo"<td>".$rowData['ECES_COUPON_DATE']."</td>";
    echo"<td>".$rowData['ECES_PE_NIK']."</td>";
    echo"<td>".$rowData['ECES_CREATED_TIMESTAMP']."</td>";
    echo"<td>".$rowData['ECES_REMARK_PROCESS']."</td>";
    echo"<td>".$rowData['ECES_REMARK_HRM']."</td>";
    if (!empty($rowView['USER_MODIFIED_BY'])){
      echo"<td>".$rowView['USER_MODIFIED_BY']."</td>";
    } else {
      echo"<td></td>";
    }
    echo"</tr>";

  }
?>
</table>
</body>
</html>
