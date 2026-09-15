<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");

/** Include PHPExcel */
require_once dirname(__FILE__) . '/PHPExcel-1.8/Classes/PHPExcel.php';
// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

$P_FORM_NAME = "NULL";
if(isset($_GET['P_FORM_NAME'])) {
  $P_FORM_NAME=$_GET['P_FORM_NAME'];
}

$CYL_TYPE_S = "NULL";
if(isset($_GET['CYL_TYPE_S'])) {
  $CYL_TYPE_S=$_GET['CYL_TYPE_S'];
}

$CYL_SHADE_CODE_S = "NULL";
if(isset($_GET['CYL_SHADE_CODE_S'])) {
  $CYL_SHADE_CODE_S=$_GET['CYL_SHADE_CODE_S'];
}
$CYL_SHADE_NAME_S = "NULL";
if(isset($_GET['CYL_SHADE_NAME_S'])) {
  $CYL_SHADE_NAME_S=$_GET['CYL_SHADE_NAME_S'];
}

$CMY_LUSTURE_S = "NULL";
if(isset($_GET['CMY_LUSTURE_S'])) {
  $CMY_LUSTURE_S=$_GET['CMY_LUSTURE_S'];
}

$DENIER_S = "NULL";
if(isset($_GET['DENIER_S'])) {
  $DENIER_S=$_GET['DENIER_S'];
}

$FILAMENT_S = "NULL";
if(isset($_GET['FILAMENT_S'])) {
  $FILAMENT_S=$_GET['FILAMENT_S'];
}

$INTERMINGLING_S = "NULL";
if(isset($_GET['INTERMINGLING_S'])) {
  $INTERMINGLING_S=$_GET['INTERMINGLING_S'];
}

$CROSS_SECTION_S = "NULL";
if(isset($_GET['CROSS_SECTION_S'])) {
  $CROSS_SECTION_S=$_GET['CROSS_SECTION_S'];
}

$HEATSET_S = "NULL";
if(isset($_GET['HEATSET_S'])) {
  $HEATSET_S=$_GET['HEATSET_S'];
}

$CUSTOMER_S = "NULL";
if(isset($_GET['CUSTOMER_S'])) {
  $CUSTOMER_S=$_GET['CUSTOMER_S'];
}

$CYL_PRODUCT_QUALITY_S="NULL";
if(isset($_GET['CYL_PRODUCT_QUALITY_S'])) {
  $CYL_PRODUCT_QUALITY_S=$_GET['CYL_PRODUCT_QUALITY_S'];
}

$CYL_LEFT_NO_S="NULL";
if(isset($_GET['CYL_LEFT_NO_S'])) {
  $CYL_LEFT_NO_S=$_GET['CYL_LEFT_NO_S'];
}
//$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";

$SqlView = "SELECT  CMY_NAME
                    ,CYCC_TOP_13_DATA_VALUE DENIER
                    ,CYCC_TOP_16_DATA_VALUE FILAMENT
                    ,CYCC_TOP_18_DATA_VALUE INTERMINGLING
                    ,CYCC_TOP_49_DATA_VALUE HEATSET
                    ,CYCC_TOP_17_DATA_VALUE CROSS_SECTION
                    ,CMY_LUSTURE
                    ,CYL_SYS_ID
                    ,CYL_SHADE_CODE
                    ,CYL_SHADE_NAME
                    ,CYL_LEFT_NO
                    ,CYL_TYPE
             FROM mgtapps.cst_mst_yarn y,
                  mgtapps.cst_yarn_left l,
                  mgtapps.cst_yarn_calculation_cur cc
            WHERE cycc_cyl_sys_id = cyl_sys_id
              AND cyl_cmy_sys_id = cmy_sys_id
              AND cyl_prs_type = cycc_prs_type
              AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_IS_VALID_PRD = 'Y' ";

  $SqlViewWhere = "";
  if ( $CYL_TYPE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
  }
  if ( $CYL_SHADE_CODE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
  }
  if ( $CYL_SHADE_NAME_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
  }

  if ( $CMY_LUSTURE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CMY_LUSTURE = '$CMY_LUSTURE_S' ";
  }

  if ( $DENIER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_13_DATA_VALUE = '$DENIER_S' ";
  }

  if ( $FILAMENT_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' ";
  }

  if ( $INTERMINGLING_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' ";
  }

  if ( $CROSS_SECTION_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' ";
  }

  if ( $HEATSET_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' ";
  }
  if ( $CUSTOMER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
          where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
          and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
  }
  if ( $CYL_PRODUCT_QUALITY_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_PRODUCT_QUALITY = '$CYL_PRODUCT_QUALITY_S' ";
  }
  if ( $CYL_LEFT_NO_S !=="NULL"){
    if ( $CYL_LEFT_NO_S !==""){
      $SqlViewWhere = "$SqlViewWhere and CYL_LEFT_NO = '$CYL_LEFT_NO_S' ";
    }
  }

?>

<html>
<head>
</head>
<body>
<?php
  $SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
  $SqlView = "$sqlSelect from ($SqlView) d ";
  $rsView = oci_parse($conn,$SqlView);
  oci_execute ($rsView);
  while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {


    // Add some data
    $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Hello')
                ->setCellValue('B2', 'world!')
                ->setCellValue('C1', 'Hello')
                ->setCellValue('D2', 'world!');

    // // Rename worksheet
    // $objPHPExcel->getActiveSheet(0)->setTitle('Simple');

    // memberi nama sheet pertama dengan nama 'Sheet 1'
    $objPHPExcel->getSheet(0)->setTitle('Sheet 1');

    // Membuat sheet kedua dengan nama 'Sheet 2'
    $myWorkSheet = new PHPExcel_Worksheet($objPHPExcel, 'Sheet 2');
    $objPHPExcel->addSheet($myWorkSheet, 1);

    $objPHPExcel->setActiveSheetIndex(1)
                ->setCellValue('A1', 'aHello')
                ->setCellValue('B2', 'world!')
                ->setCellValue('C1', 'Hello')
                ->setCellValue('D2', 'world!');


    // mengeset sheet 2 yang aktif
    $objPHPExcel->setActiveSheetIndex(1);

    // Redirect output to a client’s web browser (Excel2007)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Multi Product.xlsx"');
    header('Cache-Control: max-age=0');
    // If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

    // If you're serving to IE over SSL, then the following may be needed
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
    header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header ('Pragma: public'); // HTTP/1.0

    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;


  }
?>
</body>
</html>
