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
$OST_KEY_NO_S = "";
$P_USER_NAME= "";
if (isset($_SESSION['username'])) {
  $P_USER_NAME = $_SESSION['username'];
}

//echo "P_FILTER $P_FILTER : P_FILTER_VALUE $P_FILTER_VALUE";die();

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
  $excelNm = "Invoice Breakdown $P_DATE_FROM - $P_DATE_TO .XLS";
  $menuName =  getData($conn,"select menu_name GET_DATA from mst_menus where menu_id = '$P_MENU_ID'");
  header("Content-type: application/vnd-ms-excel");
  header("Content-Disposition: attachment; filename=$excelNm");
  echo "<table>";
  echo "<tr><td>Report : $menuName </td></tr>";
  echo "<tr><td>Period $P_DATE_FROM - $P_DATE_TO</td></tr>";
  echo "<tr></tr>";
  echo "</table>";
  $DATE_FROM_S = $P_DATE_FROM;
  $DATE_TO_S = $P_DATE_TO;

  include("EFILL_010_SQL.php");
  $sqlView =
    "
    select 	rownum REC_NO
            , INV_FOLDER
            ,	INV_YY
            ,	INV_NO,OST_KEY_NO
            ,	INVH_DT
            , INVH_CUST_NAME
            , INVH_CURR_CODE
            , INVH_TERM_CODE
            , SALES_AMT
            ,	SALES_TAX
            ,	SALES_TOT_AMT
            ,	FP_FILE
            ,	DN_NO
            ,	DN_FOLDER
            ,	DN_YY
            ,	FP_FOLDER
            ,	FP_YY
            , KWITANSI
            , KWI_NO
            , 'KWI' KWI_FOLDER
            , KWI_YY,KWI_NCA,KWI_ADDRESS
    FROM ( $sqlData) where 1=1
    ";
    $sqlWhere = "";
    if ($P_FILTER==="Invoice No"){$sqlWhere = " AND upper(INV_NO) like '%'||upper('$P_FILTER_VALUE')||'%' ";}

		if ($P_FILTER==="Date"){$sqlWhere = " AND to_char(INVH_DT,'YYYY-MM-DD') = '$P_FILTER_VALUE' ";}

		if ($P_FILTER==="Customer"){$sqlWhere = " AND upper(INVH_CUST_NAME) like '%'||upper('$P_FILTER_VALUE')||'%' ";}

		if ($P_FILTER==="Term Code"){$sqlWhere = " AND upper(INVH_TERM_CODE) like '%'||upper('$P_FILTER_VALUE')||'%'  "; }

		if ($P_FILTER==="FP Sales"){$sqlWhere = " AND upper(FP_FILE) like '%'||upper('$P_FILTER_VALUE')||'%'  ";}

		if ($P_FILTER==="DN No"){$sqlWhere = " AND upper(DN_NO) like '%'||upper('$P_FILTER_VALUE')||'%'  ";}

    if ($P_FILTER==="Kwitansi No"){$sqlWhere = " AND upper(KWI_NO) like '%'||upper('$P_FILTER_VALUE')||'%'  ";}

  $sqlViewData = $sqlView.$sqlWhere;
  $sqlViewData = "select * from ($sqlViewData)";
  //echo "<tr><td>$sqlViewData</td></tr>";
//echo $sqlData;die();
  $rsData = oci_parse($conn,$sqlViewData);
  oci_execute ($rsData);
  $recNo = 0;
  $LpData = "";
?>
  <table border="1">
  <tr>
			<td>No</td><!--1-->
			<td>Invoice No</td><!--2-->
			<td>Date</td><!--3-->
			<td>Customer</td><!--4-->
			<td>Currency</td><!--5-->
			<td>Term Code</td><!--6-->
			<td>Sales AMT</td><!--7-->
			<td>Sales TAX</td><!--8-->
			<td>Sales Total</td><!--9-->
			<td>FP Sales</td><!--10-->
			<td>DN No</td><!--11-->
			<td>Kwitansi</td><!--12-->
      <td>Kwitansi NCA</td><!--13-->
      <td>Kwitansi Address</td><!--14-->
		</tr>
<?php
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
      $recNo++;
?>
  <tr>
    <td><?php echo $rowData['REC_NO'];?></td><!--1-->
    <td><?php if (!empty($rowData['INV_NO'])) echo $rowData['INV_NO']; ?></td><!--2-->
    <td><?php if (!empty($rowData['INVH_DT'])) echo $rowData['INVH_DT'];?></td><!--3-->
    <td><?php if (!empty($rowData['INVH_CUST_NAME'])) echo $rowData['INVH_CUST_NAME'];?></td><!--4-->
    <td><?php if (!empty($rowData['INVH_CURR_CODE'])) echo $rowData['INVH_CURR_CODE'];?></td><!--5-->
    <td><?php if (!empty($rowData['INVH_TERM_CODE'])) echo $rowData['INVH_TERM_CODE'];?></td><!--6-->
    <td><?php if (!empty($rowData['SALES_AMT'])) echo $rowData['SALES_AMT'];?></td><!--7-->
    <td><?php if (!empty($rowData['SALES_TAX'])) echo $rowData['SALES_TAX'];?></td><!--8-->
    <td><?php if (!empty($rowData['SALES_TOT_AMT'])) echo $rowData['SALES_TOT_AMT'];?></td><!--9-->
    <td><?php if (!empty($rowData['FP_FILE'])) echo $rowData['FP_FILE'];?></td><!--10-->
    <td><?php if (!empty($rowData['DN_NO'])) echo $rowData['DN_NO'];?></td><!--11-->
    <td><?php if (!empty($rowData['KWI_NO'])) echo $rowData['KWI_NO'];?></td><!--12-->
    <td><?php if (!empty($rowData['KWI_NCA'])) echo $rowData['KWI_NCA'];?></td><!--13-->
    <td><?php if (!empty($rowData['KWI_ADDRESS'])) echo $rowData['KWI_ADDRESS'];?></td><!--14-->
  </tr>
<?php
  }
?>
</table>
</body>
</html>
