<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("../conOraOci.php");
include ("../check_login.php");
include ("../common_function.php");
$P_DATE_FROM_S= ""; if(isset($_POST['P_DATE_FROM_S'])) {$P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];}

if ($P_DATE_FROM_S=== ""){if(isset($_GET['P_DATE_FROM'])) {$P_DATE_FROM_S=$_GET['P_DATE_FROM']; }}

$P_DATE_TO_S= "";if(isset($_POST['P_DATE_TO_S'])) {$P_DATE_TO_S=$_POST['P_DATE_TO_S'];}

if ($P_DATE_TO_S=== ""){ if(isset($_GET['P_DATE_TO'])) {
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

require('../fpdf17/fpdf.php');
// intance object dan memberikan pengaturan halaman PDF
$pdf = new FPDF('L','mm','A4');
// membuat halaman baru
$pdf->AddPage();
$pdf->Image('../USERLOGO.jpg',5,3,20);
// setting jenis font yang akan digunakan
$pdf->SetFont('Arial','B',11);
// mencetak string
//$pdf->Cell(190,7,'PENERUS RUKUN MAKMUR',0,1,'C');
$pdf->Cell(0,0,$P_REPORT_NAME_S,0,1,'C');
$pdf->SetFont('Arial','',9);
// //$pdf->Cell(190,7,'Laporan Pendapatan Pengeluaran('.$NAMA_MODUL.')',0,1,'C');
$pdf->Cell(0,7,"Tanggal Kupon $P_DATE_FROM_S",0,1,'C');
if ($SLCT_CC_003_R002_S!=="NULL"){
  $pdf->SetFont('Arial','b',8);
  $pdf->Cell(0,0,"$SLCT_CC_003_R002_S like %$SLCT_CC_003_R002_I_S% ",0,1,'C');
}

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);

$vLNo = 6;//1
$vLDt = 15;//2
$vLNik = 14;//3
$vLName = 43;//4
$vDept = 18;//5
$vActlTm = 23;//6
$vActlShft = 21;//7
$vScdlBrk = 23;//8
$vOnScdl = 19;//8
$vOvrTm = 13;//9
$vCrtBy = 13;//10
$vRrmks = 49;//11

$pdf->SetFont('Arial','B',7);

$pdf->Cell($vLNo,6,'No',1,0,'C');//1
$pdf->Cell($vLDt,6,'Date',1,0,'C');//2
$pdf->Cell($vLNik,6,'NIK',1,0,'C');//3
$pdf->Cell($vLName,6,'Name',1,0,'C');//4
$pdf->Cell($vDept,6,'Department',1,0,'C');//5
$pdf->Cell($vActlTm,6,'Actual Scan',1,0,'C');//6
$pdf->Cell($vActlShft,6,'Actual Break',1,0,'C');//7
$pdf->Cell($vScdlBrk,6,'Schedule Break',1,0,'C');//8
$pdf->Cell($vOnScdl,6,'ON Schedule',1,0,'C');//8
$pdf->Cell($vOvrTm,6,'Overtime',1,0,'C');//9
$pdf->Cell($vCrtBy,6,'Entry By',1,0,'C');//10
$pdf->Cell($vRrmks,6,'Remarks',1,1,'C');//11

include("CC_003_R02_WHERE.php");

$rsData = oci_parse($conn,$sqlView);
oci_execute ($rsData);
$pdf->SetFont('Arial','',7);
$lnPurpose = 0;
//$maxLnPurpose = 50;
while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $Stsbrk = "";if (!empty($rowData['SCHEDULE_BREAK'])){$Stsbrk = $rowData['SCHEDULE_BREAK'];}
    $Stsbrk = "(".$rowData['NOSHIFT'].") ".$Stsbrk;
    $ON_SCHEDULE_BREAK = ""; if (!empty($rowData['ON_SCHEDULE_BREAK'])){ $ON_SCHEDULE_BREAK = $rowData['ON_SCHEDULE_BREAK']; }
    $ECD_CREATED_BY = ""; if (!empty($rowData['ECD_CREATED_BY'])){ $ECD_CREATED_BY = $rowData['ECD_CREATED_BY']; }

    $SHIFT_BREAK_DESC = $rowData['SHIFT_BREAK_DESC'];

    $pdf->Cell($vLNo,6,$rowData['RECNO'],1,0,'R');//1
    $pdf->Cell($vLDt,6,$rowData['ECD_DATE'],1,0,'L');//2
    $pdf->Cell($vLNik,6,$rowData['ECD_PE_NIK'],1,0,'L');//3
    $pdf->Cell($vLName,6,$rowData['ECD_PE_NAMA'],1,0,'L');//4
    $pdf->Cell($vDept,6,$rowData['ECD_PE_DEPT_ID'],1,0,'L');//5
    $pdf->Cell($vActlTm,6,$rowData['ECD_ACTUAL_BREAKTIME'],1,0,'C');//6
    $pdf->Cell($vActlShft,6,$SHIFT_BREAK_DESC,1,0,'L');//7
    $pdf->Cell($vScdlBrk,6,$Stsbrk,1,0,'C');//8
    $pdf->Cell($vOnScdl,6,$ON_SCHEDULE_BREAK,1,0,'C');//8
    $pdf->Cell($vOvrTm,6,$rowData['STS_OVERTIME'],1,0,'C');//9
    $pdf->Cell($vCrtBy,6,$ECD_CREATED_BY,1,0,'L');//10
    $pdf->Cell($vRrmks,6,$rowData['ECD_REMARKS'],1,1,'L');//11
}

$pdf->Output();
?>
