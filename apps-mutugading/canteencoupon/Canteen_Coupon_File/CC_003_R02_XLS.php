<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("../conOraOci.php");
include ("../check_login.php");
include ("../common_function.php");
include ("FORM_NAME.php");

$P_DATE_FROM_S= ""; if(isset($_POST['P_DATE_FROM_S'])) {$P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];}

if ($P_DATE_FROM_S=== ""){
  if(isset($_GET['P_DATE_FROM'])) {
    $P_DATE_FROM_S=$_GET['P_DATE_FROM'];
  }
}

$P_DATE_TO_S= "";if(isset($_POST['P_DATE_TO_S'])) {$P_DATE_TO_S=$_POST['P_DATE_TO_S'];}
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
include($P_FORM_NAME_S."_R02_SQL.PHP");

$P_REPORT_ID_S="";
if ($P_REPORT_ID_S=== ""){
  if(isset($_GET['P_REPORT_ID'])) {
    $P_REPORT_ID_S=$_GET['P_REPORT_ID'];
  }
}

$SLCT_CC_003_R002_S="";
if ($SLCT_CC_003_R002_S=== ""){
  if(isset($_GET['SLCT_CC_003_R002_S'])) {
    $SLCT_CC_003_R002_S=$_GET['SLCT_CC_003_R002_S'];
  }
}
$SLCT_CC_003_R002_I_S="";
if ($SLCT_CC_003_R002_I_S=== ""){
  if(isset($_GET['SLCT_CC_003_R002_I_S'])) {
    $SLCT_CC_003_R002_I_S=$_GET['SLCT_CC_003_R002_I_S'];
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
$excelNm = $P_REPORT_NAME_S."_".$P_DATE_FROM_S.".Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr><td>Report : $P_REPORT_NAME_S </td></tr>";
echo "<tr><td>Tanggal Kupon $P_DATE_FROM_S </td></tr>";
echo "<tr></tr>";
if ($SLCT_CC_003_R002_S!=="NULL"){
  echo "<tr><td>$SLCT_CC_003_R002_S like %$SLCT_CC_003_R002_I_S%</td></tr>";
}
echo "</table>";
?>
    <table border="1">
        <tr>
            <td>No</td><!--1-->
            <td>Date</td><!--2-->
            <td>NIK</td><!--3-->
            <td>Name</td><!--4-->
            <td>Department</td><!--5-->
            <td>Actual Scan</td><!--6-->
            <td>Actual Break</td><!--7-->
            <td>Schedule Break</td><!--8-->
            <td>ON Schedule</td><!--8-->
            <td>Overtime</td><!--9-->
            <td>Entry By</td><!--10-->
            <td>Remarks</td><!--11-->
        </tr>
<?php
  include("CC_003_R02_WHERE.php");

  $rsData = oci_parse($conn,$sqlView);
  oci_execute ($rsData);
  $no = 0;
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $no = $no + 1;

    $Stsbrk = ""; if (!empty($rowData['SCHEDULE_BREAK'])){$Stsbrk = $rowData['SCHEDULE_BREAK'];}
    $Stsbrk = "(".$rowData['NOSHIFT'].") ".$Stsbrk;
    $ON_SCHEDULE_BREAK = "";if (!empty($rowData['ON_SCHEDULE_BREAK'])){$ON_SCHEDULE_BREAK = $rowData['ON_SCHEDULE_BREAK'];}
    $ECD_CREATED_BY = "";if (!empty($rowData['ECD_CREATED_BY'])){$ECD_CREATED_BY = $rowData['ECD_CREATED_BY'];}
    $SHIFT_BREAK_DESC = $rowData['SHIFT_BREAK_DESC'];

    echo"<tr>";
    echo"<td>".$rowData['RECNO']."</td>";//1
    echo"<td>".$rowData['ECD_DATE']."</td>";//2
    echo"<td>".$rowData['ECD_PE_NIK']."</td>";//3
    echo"<td>".$rowData['ECD_PE_NAMA']."</td>";//4
    echo"<td>".$rowData['ECD_PE_DEPT_ID']."</td>";//5
    echo"<td>".$rowData['ECD_ACTUAL_BREAKTIME']."</td>";//6
    echo"<td>".$SHIFT_BREAK_DESC."</td>";//7
    echo"<td>".$Stsbrk."</td>";//8
    echo"<td>".$ON_SCHEDULE_BREAK."</td>";//8
    echo"<td>".$rowData['STS_OVERTIME']."</td>";//9
    echo"<td>".$ECD_CREATED_BY."</td>";//10
    echo"<td>".$rowData['ECD_REMARKS']."</td>";//11
    echo"</tr>";
  }
?>
</table>
</body>
</html>
