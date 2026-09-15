<?php session_start(); ?>
<?php
  header('Cache-Control: no-cache, must-revalidate, max-age=0');
  header('Cache-Control: post-check=0, pre-check=0',false);
  header('Pragma: no-cache');
include("conOraOci.php");
//include ("check_login.php");
include ("common_function.php");
include ("CST_FIND_PRODUCT_SQL.php");
//$PCYCRM_SYS_ID = "20201103";
$P_CYL_SYS_ID_DTL = "";
if(isset($_GET['P_CYL_SYS_ID_DTL'])) {
  $P_CYL_SYS_ID_DTL=$_GET['P_CYL_SYS_ID_DTL'];
}

$P_CYCRM_SYS_ID = "";
if(isset($_GET['P_CYCRM_SYS_ID'])) {
  $P_CYCRM_SYS_ID=$_GET['P_CYCRM_SYS_ID'];
}

function getParamRep(
    $deepMaterial
    ,&$SheetWidth
    ,&$lnVal
    ){
  //normal
  $SheetWidth = 50;
  //normal
  $lnVal = 35;
  if ((int)$deepMaterial===4){
    $SheetWidth = 39;
    $lnVal = 24;
  } else if ((int)$deepMaterial===5){
    $SheetWidth = 35;
    $lnVal = 20;
  } else if ((int)$deepMaterial===6){
    $SheetWidth = 10;
    $lnVal = 5;
  }
}

require('fpdf17/fpdf.php');
// intance object dan memberikan pengaturan halaman PDF
$pdf = new FPDF('P','mm','A4');


// Create New Page
$pdf->AddPage();
//$pdf->Image('Log_3.jpg',10,1,8);

// Logo
$pdf->Image('Log_3.jpg',10,1,10);
// Arial bold 15
$pdf->SetFont('Arial','B',10);
// Move to the right
$pdf->Cell(80);
// Title
$pdf->Cell(30,0,"COSTING Find Product by Color",0,0,'C');

$SqlData = "select to_char(sysdate,'YYYY-MM-DD HH24:MI') GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL' ";
$curDate =  getData($conn,$SqlData);
$pdf->Cell(85,0,"Date Print : $curDate",0,1,'R');
// Line break
//$pdf->Ln(20);
//Logo

//$pdf->SetFont('Arial','B',7);
$pdf->SetFont('Arial','',7);

$SqlData = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL' ";
$CYL_TYPE =  getData($conn,$SqlData);

$sql1 =
  "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION,NVL (CYCRL_DESCRIPTION_JUSTIFY, 'L') CYCRL_DESCRIPTION_JUSTIFY
          ,NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA
          ,CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,NVL (CYCRL_LENGTH_DECIMAL, '0') CYCRL_LENGTH_DECIMAL
          ,NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
          ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
      FROM mgtapps.cst_yarn_calc_rpt_lable b
      WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
      order by to_number(cycrl_seq_no)
  ";

if ($CYL_TYPE==="MELANGE"){
  include ("CST_FIND_PRODUCT_PDF_MELANGE.php");
} else {
  include ("CST_FIND_PRODUCT_PDF_DFLT.php");
}

$pdf->Output();
?>
