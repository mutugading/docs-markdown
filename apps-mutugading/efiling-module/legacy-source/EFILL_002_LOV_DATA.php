<!-- Data -->
<div class="main" style="overflow-x:auto;" align="center">
<?php
$msgNoData = "No exist data";
$font="12"; $bgClrList = "bgcolor='#F6E3CE'"; $bgClrListDtl = "bgcolor='#BCB8B8'";

$FORM_NAME = $_GET['FORM_NAME']; $USER_NAME = $_GET['USER_NAME']; $P_MENU_ID = $_GET['P_MENU_ID'];
include("../conOraOci.php");  include ("../common_function.php"); include("EFILL_SQL.php");

$STS_LOV = ""; if(isset($_GET['STS_LOV'])) {$STS_LOV=$_GET['STS_LOV'];};
$FIND_DATA = ""; if(isset($_GET['FIND_DATA'])) {$FIND_DATA=$_GET['FIND_DATA'];};

$whereCond = "";
//echo "STS_LOV  $STS_LOV : FIND_DATA $FIND_DATA<br>";
//VIEW_FOLDER
if ($STS_LOV==="VIEW_FOLDER"){

  $sqlData = getSqlFolder($conn,$USER_NAME);
?>
  <table style="width:40%" border="1" align="center">
    <tr >
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> No</td> <!--1-->
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> > Folder </td><!--2-->
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> > Select </td><!--3-->
    </tr>
<?php
  if ($FIND_DATA!==""){
    $whereCond = " and ETD_SYS_ID like '%'||upper('$FIND_DATA')||'%' ";
  }
  $sqlData = "select rownum REC_NO,a.* from (select * from ($sqlData) where 1=1 $whereCond)a";
  $rsData = oci_parse($conn,$sqlData);
  oci_execute ($rsData);
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $REC_NO =""; if(!empty($rowData['REC_NO'])){$REC_NO=$rowData['REC_NO'];}
    $ETD_SYS_ID =""; if(!empty($rowData['ETD_SYS_ID'])){$ETD_SYS_ID=$rowData['ETD_SYS_ID'];}

    $nmCk = "CK_".$ETD_SYS_ID; $evntCk = "ckLovData('$nmCk','$ETD_SYS_ID')";
?>
  <tr >
    <td <?php echo getStyle($font,"center","10"); echo $bgClrListDtl; ?> > <?php echo $REC_NO; ?> </td> <!--1-->
    <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> > <?php echo $ETD_SYS_ID; ?> </td><!--2-->
    <td <?php echo getStyle($font,"center","10"); echo $bgClrListDtl; ?> >
      <input  type="checkbox" id="<?php echo $nmCk; ?>" onclick="<?php echo $evntCk; ?>" >
    </td><!--3-->
  </tr>
<?php
  }
  echo "</table>";
}
//VIEW_FOLDER
?>
</div class="main">
