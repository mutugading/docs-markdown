
<?php session_start(); ?>
<?php
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=DataProduct_Yarn.xls");

$CMCD_NAME_S= "";
if(isset($_GET['CMCD_NAME_S'])) {
    $CMCD_NAME_S=$_GET['CMCD_NAME_S'];
    $CMCD_NAME_S=strtoupper($CMCD_NAME_S);
}
$CMY_TYPE_S= "";
if(isset($_GET['CMY_TYPE_S'])) {
    $CMY_TYPE_S=$_GET['CMY_TYPE_S'];
    $CMY_TYPE_S=strtoupper($CMY_TYPE_S);
}
$CMY_LUSTURE_S= "";
if(isset($_GET['CMY_LUSTURE_S'])) {
    $CMY_LUSTURE_S=$_GET['CMY_LUSTURE_S'];
    $CMY_LUSTURE_S=strtoupper($CMY_LUSTURE_S);
}
$CYL_SHADE_NAME_S= "";
if(isset($_GET['CYL_SHADE_NAME_S'])) {
    $CYL_SHADE_NAME_S=$_GET['CYL_SHADE_NAME_S'];
    $CYL_SHADE_NAME_S=strtoupper($CYL_SHADE_NAME_S);
}
$DENIER_S= "";
if(isset($_GET['DENIER_S'])) {
    $DENIER_S=$_GET['DENIER_S'];
    $DENIER_S=strtoupper($DENIER_S);
}
$FILAMENT_S= "";
if(isset($_GET['FILAMENT_S'])) {
    $FILAMENT_S=$_GET['FILAMENT_S'];
    $FILAMENT_S=strtoupper($FILAMENT_S);
}
$INTERMINGLING_S= "";
if(isset($_GET['INTERMINGLING_S'])) {
    $INTERMINGLING_S=$_GET['INTERMINGLING_S'];
    $INTERMINGLING_S=strtoupper($INTERMINGLING_S);
}
$HEATSET_S= "";
if(isset($_GET['HEATSET_S'])) {
    $HEATSET_S=$_GET['HEATSET_S'];
    $HEATSET_S=strtoupper($HEATSET_S);
}
$CROSS_SECTION_S= "";
if(isset($_GET['CROSS_SECTION_S'])) {
    $CROSS_SECTION_S=$_GET['CROSS_SECTION_S'];
    $CROSS_SECTION_S=strtoupper($CROSS_SECTION_S);
}

$paramData = "";
$paramVal = "";
if ($CMCD_NAME_S!=="" ){
  $paramData = "Customer Name : $CMCD_NAME_S";
}
if ($CMY_TYPE_S!=="" ){
  $paramVal = "Product Type : $CMY_TYPE_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($CMY_LUSTURE_S!=="" ){
  $paramVal = "Lusture : $CMY_LUSTURE_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($CYL_SHADE_NAME_S!=="" ){
  $paramVal = "Colour / Shade Name : $CYL_SHADE_NAME_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($DENIER_S!=="" ){
  $paramVal = "Denier : $CYL_SHADE_NAME_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($DENIER_S!=="" ){
  $paramVal = "Denier : $DENIER_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($FILAMENT_S!=="" ){
  $paramVal = "Filament : $FILAMENT_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($INTERMINGLING_S!=="" ){
  $paramVal = "Intermingling : $INTERMINGLING_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($HEATSET_S!=="" ){
  $paramVal = "Heatset : $HEATSET_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}
if ($CROSS_SECTION_S!=="" ){
  $paramVal = "Cross Section : $CROSS_SECTION_S";
  if ($paramData===""){
    $paramData = $paramVal;
  } else $paramData = "$paramData ; $paramVal";
}

include('CST_T_002_SQL.PHP');

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
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Costing_Product_Data.xls");
echo "<table>";
echo "<tr></tr>";
echo "<tr>";
echo "<td>Report : Costing Product Data </td>";
echo "</tr>";
if ($paramData!==""){
  echo "<tr>";
  echo "<td> with Paramter $paramData </td>";
  echo "</tr>";
}
echo "<tr></tr>";
echo "</table>";

include('CST_T_002_VIEW_DATA_WHERE.PHP');

function ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView){

  $sqlDtl ="select  $sqlField GET_DATA from (select ROWNUM REC_NO,d.* from ($sqlViewMst) d where CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt $vWhereView )";
  //echo "$sqlDtl</br>";
  $rsDtl = oci_parse($conn,$sqlDtl);
  oci_execute ($rsDtl);
  while ($rowRsDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
    $dtVal = "";
    if (!empty($rowRsDtl['GET_DATA'])){
      $dtVal = $rowRsDtl['GET_DATA'];
    }
    echo "<td>$dtVal</td>";
  }
}
?>
<table border="1">
    <table border="1">
    <!-- Customer -->
    <tr>
      <td>
          Customer
       </td>
<?php
    $sqlField = "CYCC_TOP_123_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 1.Type Product -->
    <tr>
      <td>
          1.Type Product
       </td>
<?php
    $sqlField = "CYL_TYPE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 2.Lusture -->
    <tr>
      <td>
          2.Lusture
       </td>
<?php
    $sqlField = "CMY_LUSTURE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 3.Shade Code -->
    <tr>
      <td>
          3.Shade Code
       </td>
<?php
    $sqlField = "CYL_SHADE_CODE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 4.Shade Name -->
    <tr>
      <td>
          4.Shade Name
       </td>
<?php
    $sqlField = "CYL_SHADE_NAME";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 5.Denier -->
    <tr>
      <td>
          5.Denier
       </td>
<?php
    $sqlField = "CYCC_TOP_13_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 6.Filament -->
    <tr>
      <td>
          6.Filament
       </td>
<?php
    $sqlField = "CYCC_TOP_16_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 7. Intermingling -->
    <tr>
      <td>
          7. Intermingling
      </td>
<?php
    $sqlField = "CYCC_TOP_18_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 8. Heatset -->
    <tr>
      <td>
          8. Heatset
      </td>
<?php
    $sqlField = "CYCC_TOP_49_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 9. Cross Section -->
    <tr>
      <td>
          9. Cross Section
      </td>
<?php
    $sqlField = "CYCC_TOP_17_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 10. MB Name -->
    <tr>
      <td>
          10. MB Name
       </td>
<?php
    $sqlField = "CYCC_TOP_64_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 11. Dozing -->
    <tr>
      <td>
          11. Dozing
       </td>
<?php
    $sqlField = "CYCC_TOP_71_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 12. Rate -->
    <tr>
      <td>
          12. Rate
       </td>
<?php
    $sqlField = "CYCC_TOP_72_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 13. 150/48 NIMPrice -->
    <tr>
      <td>
          13. 150/48 NIMPrice
       </td>
<?php
    $sqlField = "CYCC_TOP_72_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 14. Tx prdn/day -->
    <tr>
      <td>
          14. Tx prdn/day
<?php
    $sqlField = "CYCC_TOP_82_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
       </td>
    </tr>
    <!-- 15. Quantity -->
    <tr>
      <td>
          15. Quantity
       </td>
    </tr>
    <!-- 16. Select .Volume -->
    <tr>
      <td>
          16. Select .Volume
       </td>
    </tr>
    <!-- 17. Net Price -->
    <tr>
      <td>
          17. Net Price
       </td>
    </tr>
    <!-- 18. Cost Considered -->
    <tr>
      <td>
          18. Cost Considered
       </td>
    </tr>
    <!-- 19. Margin/kg -->
    <tr>
      <td>
          19. Margin/kg
       </td>
    </tr>
    <!-- 20. Margin/Day -->
    <tr>
      <td>
          20. Margin/Day
       </td>
    </tr>
    <!-- 21. Margin/order -->
    <tr>
      <td>
          21. Margin/order
       </td>
    </tr>
    <!-- 22. Selective Items -->
    <tr>
      <td>
          22. Selective Items
       </td>
    </tr>
    <!-- 23. Chip Price -->
    <tr>
      <td>
          23. Chip Price
       </td>
<?php
    $sqlField = "CYCC_TOP_55_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 24. RM Adjust -->
    <tr>
      <td>
          24. RM Adjust
       </td>
    </tr>
    <!--  a. MB Cost -->
    <tr>
      <td>
           &nbsp&nbsp a. MB Cost
       </td>
<?php
    $sqlField = "CYCC_TOP_73_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!--  b. MB Waste -->
    <tr>
      <td>
           &nbsp&nbsp b. MB Waste
      </td>
<?php
    $sqlField = "CYCC_TOP_73_DATA_VALUE";
    ViewDataDtl ($conn,$sqlField,$sqlViewMst,$vWhereView);
?>
    </tr>
    <!-- 25. Change over -->
    <tr>
      <td>
          25. Change over
       </td>
    </tr>
    <!-- 26. Quality Cost -->
    <tr>
      <td>
          26. Quality Cost
       </td>
    </tr>
    <!-- 27. POY FC -->
    <tr>
      <td>
          27. POY FC
       </td>
    </tr>
    <!-- 28. DTY FC -->
    <tr>
      <td>
          28. DTY FC
       </td>
    </tr>
    <!-- 29. TTY FC -->
    <tr>
      <td>
          29. TTY FC
       </td>
    </tr>
    <!-- 31. Forwarding -->
    <tr>
      <td>
          31. Forwarding
       </td>
    </tr>
    <!-- 33. Vol3. (>3<=6 T) -->
    <tr>
      <td>
          33. Vol3. (>3<=6 T)
       </td>
    </tr>
    <!-- 34. Vol2.(>1.5<=3 T) -->
    <tr>
      <td>
          34. Vol2.(>1.5<=3 T)
       </td>
    </tr>
    <!-- 35. Vol1. (<=1.5 T) -->
    <tr>
      <td>
          35. Vol1. (<=1.5 T)
       </td>
    </tr>
</table>
</body>
</html>
