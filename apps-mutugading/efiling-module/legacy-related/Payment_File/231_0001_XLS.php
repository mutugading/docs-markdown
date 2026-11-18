<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");

$P_DATE_FROM = "";
if(isset($_GET['P_DATE_FROM'])) {
    $P_DATE_FROM=$_GET['P_DATE_FROM'];
}
$P_DATE_TO = "";
if(isset($_GET['P_DATE_TO'])) {
    $P_DATE_TO=$_GET['P_DATE_TO'];
}
$P_MENU_ID= "";
if(isset($_GET['P_MENU_ID'])) {
    $P_MENU_ID=$_GET['P_MENU_ID'];
}
$P_FILTER= "";
if(isset($_GET['P_FILTER'])) {
    $P_FILTER=$_GET['P_FILTER'];
}
$P_FILTER_VALUE= "";
if(isset($_GET['P_FILTER_VALUE'])) {
    $P_FILTER_VALUE=$_GET['P_FILTER_VALUE'];
}

$P_USER_NAME= "";
if (isset($_SESSION['username'])) {
  $P_USER_NAME = $_SESSION['username'];
}

$usrAreaLgn = getData($conn,"select nvl(USER_AREA,'ALL') GET_DATA from mgtapps.mst_USERs where USER_ID = '$P_USER_NAME' ");

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
$excelNm = "PAYMENT_SUPPORTING_DOCUMENT.XLS";
$menuName =  getData($conn,"select menu_name GET_DATA from mst_menus where menu_id = '$P_MENU_ID'");
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr><td>Report : $menuName </td></tr>";
echo "<tr><td>Period $P_DATE_FROM - $P_DATE_TO</td></tr>";
echo "<tr></tr>";
echo "</table>";
?>

    <table border="1">
        <tr>
            <td>No</td>
            <td>Payment No</td>
            <td>DOC. Date</td>
            <td>Supplier</td>
            <td>Supplier Bank Information</td>
            <td>Amount</td>
            <td>Bank Account MGT</td>
            <td>User</td>
            <td>Area User</td>
        </tr>
<?php
  $DATE_FROM_S = $P_DATE_FROM;
  $DATE_TO_S = $P_DATE_TO;

  $sqlWhere = "";
      if ($P_FILTER==="Payment No"){
          $sqlWhere = " AND PAYMENT_NO like '%$P_FILTER_VALUE%' ";
      }

      if ($P_FILTER==="Supplier Name"){
          $sqlWhere = " AND SUPP_NAME like '%'||upper('$P_FILTER_VALUE')||'%' ";
      }

      if ($P_FILTER==="Area User"){
          if ($P_FILTER_VALUE!=="ALL") {
              $sqlWhere = " AND upper(USER_FIELD_01) = upper('$P_FILTER_VALUE') ";
          }
      }
      if ($usrAreaLgn!=="ALL"){
        $sqlWhere = " AND upper(USER_FIELD_01) = upper('$usrAreaLgn') ";
      }

  include("EFILL_007_SQL.php");
  $sqlData = "select distinct PAYMENT_NO, PH_DOC_DT,SUPP_NAME,PH_BANK_ACNT_NO, BANK_NAME,SBC_BANK_NAME,SBC_BANK_ACNT_NO,SBC_BANK_ACNT_NAME,USER_DESC
                      ,USER_FIELD_01,PH_FC_AMT,PH_BANK_ACNT_NO,PH_CURR_CODE
              from ($sqlData $sqlWhere )  order by payment_no ";
//echo $sqlData;die();
  $rsData = oci_parse($conn,$sqlData);
  oci_execute ($rsData);
  $recNo = 0;
  $LpData = "";

  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
      $recNo++;
?>
  <tr>
      <td><?php echo $recNo; ?></td>
      <td><?php echo $rowData['PAYMENT_NO']; ?></td>
      <td><?php echo $rowData['PH_DOC_DT']; ?></td>
      <td>
        <?php
          if (!empty($rowData['SUPP_NAME'])){
            echo $rowData['SUPP_NAME'];
          }
        ?>
      </td>
      <td>
        <?php
          if (!empty($rowData['SBC_BANK_NAME'])){
            echo $rowData['SBC_BANK_NAME']."- ";
          }
        ?>
        </br>
        <?php
          if (!empty($rowData['SBC_BANK_ACNT_NO'])){
            echo $rowData['SBC_BANK_ACNT_NO']."- ";
          }
        ?>
        </br>
        <?php
          if (!empty($rowData['SBC_BANK_ACNT_NAME'])){
            echo $rowData['SBC_BANK_ACNT_NAME']." ";
          }
        ?>
      </td>
      <td>
        <?php
          if (!empty($rowData['PH_CURR_CODE'])){
            echo $rowData['PH_CURR_CODE'];
          }
          if (!empty($rowData['PH_FC_AMT'])){
            echo " ".number_format($rowData['PH_FC_AMT']);
          }
        ?>
      </td>
      <td>
        <?php
          if (!empty($rowData['PH_BANK_ACNT_NO'])){
            echo $rowData['PH_BANK_ACNT_NO'];
          }

        ?>
      </td>
      <td>
        <?php
          if (!empty($rowData['USER_DESC'])){
            echo $rowData['USER_DESC'];
          }
        ?>
      </td>
      <td>
        <?php
          if (!empty($rowData['USER_FIELD_01'])){
            echo $rowData['USER_FIELD_01'];
          }
        ?>
      </td>
  </tr>
<?php
  }
?>
</table>
</body>
</html>
