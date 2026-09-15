<?php
$P_FORM_NAME = "NULL";if(isset($_GET['P_FORM_NAME'])){$P_FORM_NAME=$_GET['P_FORM_NAME'];}
$CYL_TYPE_S = "NULL";if(isset($_GET['CYL_TYPE_S'])){$CYL_TYPE_S=$_GET['CYL_TYPE_S'];}
include("../conOraOci.php");

$sqlUpd = "
          SELECT CYL_LEFT_NO
          FROM cst_yarn_left a
          WHERE cyl_type = '$CYL_TYPE_S' and CYL_IS_VALID_PRD='Y' and CYL_PRS_TYPE='20210800119'
          and not exists
              (
              select * from cst_product_seq_tab_hdr b
              where cpsth_fg_left_no = cyl_left_no
              )
          ";
$rsUpd = oci_parse($conn,$sqlUpd);
oci_execute ($rsUpd);
while ($rowRsUpd = oci_fetch_array ($rsUpd, OCI_BOTH)) {
	$CYL_LEFT_NO = !empty($rowRsUpd['CYL_LEFT_NO']) ? $rowRsUpd['CYL_LEFT_NO'] : "";

  $sqlPrs =
  "
    begin
     MGTAPPS.pkg_CostingProductSeq.pLoad_Data(
                    'ADMIN'
                    ,null
                    ,$CYL_LEFT_NO
                    );

     MGTAPPS.pkg_CostingProductSeq.pupd_seq($CYL_LEFT_NO);
    end;
  ";


  $stid = oci_parse($conn, $sqlPrs);
  oci_execute($stid);
}

$excelNm = "Costing Product Detail Type $CYL_TYPE_S";
$excelNmFile = $excelNm.".Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNmFile");
echo "<table>";
echo "</table>";
?>
<table>
  <tr>
    <td colspan="10" align="center">Report : <?php echo $excelNm; ?> </td>
  </tr>
  <tr>
      <td>ITEM Code</td> <!-- 1 -->
      <td>NAME</td> <!-- 2 -->
      <td>PRODUCT NO</td> <!-- 3 -->
      <td>TYPE</td><!-- 4 -->
      <td>DATA STATUS</td><!-- 5 -->
      <td>MACHINE</td><!-- 6 -->
      <td>EFFICIENCY</td><!-- 7 -->
      <td>SPEED</td><!-- 8 -->
      <td>TPM</td><!-- 9 -->
      <td>DENIER</td><!-- 10 -->
  </tr>
<?php

  $sqlParent = "
  SELECT    CPSTH_FG_LEFT_NO, CPSTH_FG_ITEM_CODE ITEM_CODE, CPSTH_FG_ITEM_NAME ITEM_NAME, CPSTH_FG_LEFT_NO LEFT_NO, CPSTH_FG_CYL_TYPE CYL_TYPE
            ,CMY_MACHINE_CODE, CMY_MACHINE_EFFICIENCY, CMY_MACHINE_SPEED, CMY_MACHINE_TPM, CMY_PROD_DENIER
      FROM cst_yarn_left a,cst_product_seq_tab_hdr b,cst_mst_yarn c
     WHERE CYL_TYPE = '$CYL_TYPE_S' and CYL_IS_VALID_PRD='Y'
      AND cyl_left_no=cpsth_fg_left_no
      and cyl_CMY_SYS_ID=CMY_SYS_ID
  ORDER BY cpsth_fg_sequence,
           cpsth_fg_left_no
   ";
   $rsParent = oci_parse($conn,$sqlParent);
   oci_execute ($rsParent);
   while ($rowRsParent = oci_fetch_array ($rsParent, OCI_BOTH)) {
     $CPSTH_FG_LEFT_NO = !empty($rowRsParent['CPSTH_FG_LEFT_NO']) ? $rowRsParent['CPSTH_FG_LEFT_NO'] : "";
     $ITEM_CODE = !empty($rowRsParent['ITEM_CODE']) ? $rowRsParent['ITEM_CODE'] : "";
     $ITEM_NAME = !empty($rowRsParent['ITEM_NAME']) ? $rowRsParent['ITEM_NAME'] : "";
     $LEFT_NO = !empty($rowRsParent['LEFT_NO']) ? $rowRsParent['LEFT_NO'] : "";
     $CYL_TYPE = !empty($rowRsParent['CYL_TYPE']) ? $rowRsParent['CYL_TYPE'] : "";
     $CMY_MACHINE_CODE = !empty($rowRsParent['CMY_MACHINE_CODE']) ? $rowRsParent['CMY_MACHINE_CODE'] : "";
     $CMY_MACHINE_EFFICIENCY = !empty($rowRsParent['CMY_MACHINE_EFFICIENCY']) ? $rowRsParent['CMY_MACHINE_EFFICIENCY'] : "";
     $CMY_MACHINE_SPEED = !empty($rowRsParent['CMY_MACHINE_SPEED']) ? $rowRsParent['CMY_MACHINE_SPEED'] : "";
     $CMY_MACHINE_TPM = !empty($rowRsParent['CMY_MACHINE_TPM']) ? $rowRsParent['CMY_MACHINE_TPM'] : "";
     $CMY_PROD_DENIER = !empty($rowRsParent['CMY_PROD_DENIER']) ? $rowRsParent['CMY_PROD_DENIER'] : "";
?>
     <tr>
         <td><?php echo $ITEM_CODE; ?></td> <!-- 1 -->
         <td><?php echo $ITEM_NAME; ?></td> <!-- 2 -->
         <td><?php echo $LEFT_NO; ?></td> <!-- 3 -->
         <td><?php echo $CYL_TYPE; ?></td><!-- 4 -->
         <td>Parent</td><!-- 5 -->
         <td><?php echo $CMY_MACHINE_CODE; ?></td><!-- 6 -->
         <td><?php echo $CMY_MACHINE_EFFICIENCY; ?></td><!-- 7 -->
         <td><?php echo $CMY_MACHINE_SPEED; ?></td><!-- 8 -->
         <td><?php echo $CMY_MACHINE_TPM; ?></td><!-- 9 -->
         <td><?php echo $CMY_PROD_DENIER; ?></td><!-- 10 -->
     </tr>
<?php

      $sqlDtl = "
      SELECT   CPSTD_RM_ITEM_CODE RM_ITEM_CODE, CPSTD_RM_ITEM_NAME RM_ITEM_NAME, CPSTD_RM_LEFT_NO RM_LEFT_NO, CPSTD_RM_CYL_TYPE RM_CYL_TYPE
              ,CMY_MACHINE_CODE, CMY_MACHINE_EFFICIENCY, CMY_MACHINE_SPEED, CMY_MACHINE_TPM, CMY_PROD_DENIER
          FROM cst_yarn_left a,cst_product_seq_tab_hdr b, cst_product_seq_tab_dtl c,cst_mst_yarn d
         WHERE cyl_left_no=$CPSTH_FG_LEFT_NO
          and cyl_left_no=CPSTH_FG_LEFT_NO
          and cpsth_sys_id = cpstd_cpsth_sys_id
          and cyl_CMY_SYS_ID=CMY_SYS_ID
      ORDER BY cpsth_fg_sequence,
               cpsth_fg_left_no,
               cpstd_rm_sequence_real,
               NVL (cpstd_rm_sub_sequence, 0)
       ";
       // echo "sqlDtl $sqlDtl";die();
       $rsDtl = oci_parse($conn,$sqlDtl);
       oci_execute ($rsDtl);
       while ($rowRsDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
         $RM_ITEM_CODE = !empty($rowRsDtl['RM_ITEM_CODE']) ? $rowRsDtl['RM_ITEM_CODE'] : "";
         $RM_ITEM_NAME = !empty($rowRsDtl['RM_ITEM_NAME']) ? $rowRsDtl['RM_ITEM_NAME'] : "";
         $RM_LEFT_NO = !empty($rowRsDtl['RM_LEFT_NO']) ? $rowRsDtl['RM_LEFT_NO'] : "";
         $RM_CYL_TYPE = !empty($rowRsDtl['RM_CYL_TYPE']) ? $rowRsDtl['RM_CYL_TYPE'] : "";
         $CMY_MACHINE_CODE = !empty($rowRsDtl['CMY_MACHINE_CODE']) ? $rowRsDtl['CMY_MACHINE_CODE'] : "";
         $CMY_MACHINE_EFFICIENCY = !empty($rowRsDtl['CMY_MACHINE_EFFICIENCY']) ? $rowRsDtl['CMY_MACHINE_EFFICIENCY'] : "";
         $CMY_MACHINE_SPEED = !empty($rowRsDtl['CMY_MACHINE_SPEED']) ? $rowRsDtl['CMY_MACHINE_SPEED'] : "";
         $CMY_MACHINE_TPM = !empty($rowRsDtl['CMY_MACHINE_TPM']) ? $rowRsDtl['CMY_MACHINE_TPM'] : "";
         $CMY_PROD_DENIER = !empty($rowRsDtl['CMY_PROD_DENIER']) ? $rowRsParent['CMY_PROD_DENIER'] : "";
 ?>
      <tr>
          <td><?php echo $RM_ITEM_CODE; ?></td> <!-- 1 -->
          <td><?php echo $RM_ITEM_NAME; ?></td> <!-- 2 -->
          <td><?php echo $RM_LEFT_NO; ?></td> <!-- 3 -->
          <td><?php echo $RM_CYL_TYPE; ?></td><!-- 4 -->
          <td>Child</td><!-- 5 -->
          <td><?php echo $CMY_MACHINE_CODE; ?></td><!-- 6 -->
          <td><?php echo $CMY_MACHINE_EFFICIENCY; ?></td><!-- 7 -->
          <td><?php echo $CMY_MACHINE_SPEED; ?></td><!-- 8 -->
          <td><?php echo $CMY_MACHINE_TPM; ?></td><!-- 9 -->
          <td><?php echo $CMY_PROD_DENIER; ?></td><!-- 10 -->
      </tr>
 <?php
       }
   }
?>
</table>
</body>
</html>
