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
include($P_FORM_NAME_S."_R01_SQL.PHP");

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
            <td>Date</td>
            <td>Shift I</td>
            <td>GS</td>
            <td>Shift II</td>
            <td>Shift III</td>
            <td>Diluar Jadwal</td>
            <td>Total</td>
            <td>Disetujui Kantin</td>
            <td>Disetujui HRM</td>
        </tr>
<?php
  $sqlView =  "select * from (select rownum RECNO,to_char(ECD_DATE,'DD-MM-YYYY') ECD_DATE, TOT_S1, TOT_SGS, TOT_S2, TOT_S3, TOT_SUD
                                    ,nvl(TOT_S1,0)+nvl(TOT_SGS,0)+nvl(TOT_S2,0)+nvl(TOT_S3,0)+nvl(TOT_SUD,0) TOTAL from ("
              .$sqlData." ORDER BY ECD_DATE) t ) t ";

  $rsData = oci_parse($conn,$sqlView);
  oci_execute ($rsData);
  $no = 0;
  $TotKntn = 0;   $TotHrm=0; $TotAll=0;
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $no = $no + 1;
    echo"<tr>";
    echo"<td>".$rowData['RECNO']."</td>";
    echo"<td>".$rowData['ECD_DATE']."</td>";
    echo"<td>".$rowData['TOT_S1']."</td>";
    echo"<td>".$rowData['TOT_SGS']."</td>";
    echo"<td>".$rowData['TOT_S2']."</td>";
    echo"<td>".$rowData['TOT_S3']."</td>";
    echo"<td>".$rowData['TOT_SUD']."</td>";
    echo"<td>".$rowData['TOTAL']."</td>";
    $TotAll=$TotAll + $rowData['TOTAL'];
    $sqlGtDt = "select  case when     ECDS_APPROVE_KANTIN_DATE is not null then
                                      to_char(ECDS_APPROVE_KANTIN_DATE,'DD-MM-YYYY HH24:MI')
                        else ' '
                        end  GET_DATA
                from EMP_COUPON_DATA_SUM
                where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowData['ECD_DATE']."'";
    $DtKantin = getData($conn,$sqlGtDt);
    echo"<td>$DtKantin</td>";
    if ($DtKantin !==" " && $DtKantin !==""){
        $TotKntn = $TotKntn + $rowData['TOTAL'];
    }
    $sqlGtDt = "select  case when     ECDS_APPROVE_HRM_DATE is not null then
                                      to_char(ECDS_APPROVE_HRM_DATE,'DD-MM-YYYY HH24:MI')
                        else ' '
                        end  GET_DATA
                from EMP_COUPON_DATA_SUM
                where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowData['ECD_DATE']."'";
    $DtHrm = getData($conn,$sqlGtDt);
    echo"<td>$DtHrm</td>";
    if ($DtHrm !==" " && $DtHrm !=="" ){
        $TotHrm = $TotHrm + $rowData['TOTAL'];
    }
    echo"</tr>";
  }
?>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  <td>TOTAL</td>
  <td><?php echo $TotAll; ?></td>
  <td><?php echo $TotKntn; ?></td>
  <td><?php echo $TotHrm; ?></td>
</table>
</body>
</html>
