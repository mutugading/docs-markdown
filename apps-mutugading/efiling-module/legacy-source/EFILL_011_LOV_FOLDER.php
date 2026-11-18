<!-- Data -->
<?php
$msgNoData = "No exist data";
$STS_LOV = ""; if(isset($_GET['STS_LOV'])) {$STS_LOV=$_GET['STS_LOV'];};
$FIND_LOV = ""; if(isset($_GET['FIND_LOV'])) {$FIND_LOV=$_GET['FIND_LOV'];};

$whereCond = "";
//echo "STS_LOV  $STS_LOV : FIND_LOV $FIND_LOV<br>";
//VIEW_FOLDER
  $sqlData = getSqlDoc($conn);//getSqlFolder($conn,$USER_NAME);
  //echo "sqlData $sqlData<br>";
?>
  <table style="width:40%" border="1" align="center">
    <tr >
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> colspan="3">
        Find : <input type="text" id="FIND_LOV" name="FIND_LOV" value="<?php echo $FIND_LOV; ?>" onchange="showLov_Folder()">
      </td> <!--1-->
    </tr>
    <tr >
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> > No</td> <!--1-->
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> > Folder </td><!--2-->
      <td <?php echo getStyle($font,"center"); echo $bgClrList; ?> > Select </td><!--3-->
    </tr>
<?php
  if ($FIND_LOV!==""){
    $whereCond = " and DOC_TYPE like '%'||upper('$FIND_LOV')||'%' ";
  }
  $sqlData = "select rownum REC_NO,a.* from (select * from ($sqlData) where 1=1 $whereCond)a";
  $rsData = oci_parse($conn,$sqlData);
  oci_execute ($rsData);
  while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $REC_NO =""; if(!empty($rowData['REC_NO'])){$REC_NO=$rowData['REC_NO'];}
    $DOC_TYPE =""; if(!empty($rowData['DOC_TYPE'])){$DOC_TYPE=$rowData['DOC_TYPE'];}

    $nmCk = "CK_".$DOC_TYPE; $evntCk = "ckFolder('$nmCk','$DOC_TYPE')";//ckFolder(pCK_Name,pCK_Val)
?>
  <tr >
    <td <?php echo getStyle($font,"center","10"); echo $bgClrListDtl; ?> > <?php echo $REC_NO; ?> </td> <!--1-->
    <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> > <?php echo $DOC_TYPE; ?> </td><!--2-->
    <td <?php echo getStyle($font,"center","10"); echo $bgClrListDtl; ?> >
      <input  type="checkbox" id="<?php echo $nmCk; ?>" onclick="<?php echo $evntCk; ?>" >
    </td><!--3-->
  </tr>
<?php
  }
  echo "</table>";
//VIEW_FOLDER
?>
