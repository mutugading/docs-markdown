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
      $pdf->Cell(30,0,"COSTING Find Product by Color",0,1,'C');
      // Line break
      //$pdf->Ln(20);
  //Logo

  $pdf->SetFont('Arial','B',7);

  $SqlData = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL' ";
  $CYL_TYPE =  getData($conn,$SqlData);
  if ($CYL_TYPE==="MELANGE"){
    include ("CST_FIND_PRODUCT_PDF_MELANGE.php");
  } else {
    include ("CST_FIND_PRODUCT_PDF_DFLT.php");
  }
?>
