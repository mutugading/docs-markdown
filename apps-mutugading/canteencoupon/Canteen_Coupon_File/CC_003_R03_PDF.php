<?php session_start(); ?>
<?php
include("../conOraOci.php");
include ("../check_login.php");
include ("../common_function.php");
$P_DATE_FROM_S= "";
  if(isset($_POST['P_DATE_FROM_S'])) {
    $P_DATE_FROM_S=$_POST['P_DATE_FROM_S'];
  }

if ($P_DATE_FROM_S=== ""){
  if(isset($_GET['P_DATE_FROM'])) {
    $P_DATE_FROM_S=$_GET['P_DATE_FROM'];
  }
}

$P_FORM_NAME_S="";
if ($P_FORM_NAME_S=== ""){
  if(isset($_GET['P_FORM_NAME'])) {
    $P_FORM_NAME_S=$_GET['P_FORM_NAME'];
  }
}
include($P_FORM_NAME_S."_R03_SQL.PHP");

$P_REPORT_ID_S="";
if ($P_REPORT_ID_S=== ""){
  if(isset($_GET['P_REPORT_ID'])) {
    $P_REPORT_ID_S=$_GET['P_REPORT_ID'];
  }
}

$P_REPORT_NAME_S = getData($conn,"select DESCRIPTION GET_DATA from mst_reports where REPORT_ID = '$P_REPORT_ID_S'");

$SLCT_CC_003_R003_S="";
if ($SLCT_CC_003_R003_S=== ""){
  if(isset($_GET['SLCT_CC_003_R003_S'])) {
    $SLCT_CC_003_R003_S=$_GET['SLCT_CC_003_R003_S'];
  }
}
$SLCT_CC_003_R003_I_S="";
if ($SLCT_CC_003_R003_I_S=== ""){
  if(isset($_GET['SLCT_CC_003_R003_I_S'])) {
    $SLCT_CC_003_R003_I_S=$_GET['SLCT_CC_003_R003_I_S'];
  }
}

require('../fpdf17/fpdf.php');
// intance object dan memberikan pengaturan halaman PDF
$pdf = new FPDF('P','mm','A4');
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
if ($SLCT_CC_003_R003_S!=="NULL"){
  $pdf->SetFont('Arial','b',8);
  $pdf->Cell(0,0,"$SLCT_CC_003_R003_S like %$SLCT_CC_003_R003_I_S% ",0,1,'C');
}

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);

$vLNo = 7;$vLDate = 14;$vNik = 15;$vName = 55;$vDept = 20;$vSchdl = 25;$vEntryBy = 35;$vActl = 21;

$pdf->SetFont('Arial','B',7);
$pdf->Cell($vLNo,6,'No',1,0,'C');
$pdf->Cell($vLDate,6,'Date',1,0,'C');
$pdf->Cell($vNik,6,'NIK',1,0,'C');
$pdf->Cell($vName,6,'Name',1,0,'C');
$pdf->Cell($vDept,6,'Dept',1,0,'C');
$pdf->Cell($vSchdl,6,'Schedule',1,0,'C');
$pdf->Cell($vEntryBy,6,'Entry By',1,0,'C');
$pdf->Cell($vActl,6,'Actual',1,1,'C');

include("CC_003_R03_WHERE.PHP");
//echo $sqlView;

$rsData = oci_parse($conn,$sqlView);
oci_execute ($rsData);
$pdf->SetFont('Arial','',7);
$lnPurpose = 0;
//$maxLnPurpose = 50;
while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $pdf->Cell($vLNo,6,$rowData['RECNO'],1,0,'R');
    $pdf->Cell($vLDate,6,$rowData['ECO_DATE'],1,0,'C');
    $pdf->Cell($vNik,6,$rowData['ECO_PE_NIK'],1,0,'L');
    $P_PE_NIK   = $rowData['ECO_PE_NIK'];
    include("../GET_DATA_EMP.PHP");
    if ($rowData['ECO_RELIEVER_STS']==="Y"){
      $P_PE_NAMA = $P_PE_NAMA." (RELIEVER)";
    }
    $pdf->Cell($vName,6,$P_PE_NAMA,1,0,'L');
    $pdf->Cell($vDept,6,$P_PE_DEPT_ID,1,0,'L');
    $pdf->Cell($vSchdl,6,$rowData['ECO_MPSM_SCH']." ".$rowData['ECO_MPSM_SCH_DESC'],1,0,'L');
    $pdf->Cell($vEntryBy,6,$rowData['CREATE_BY_NAME'],1,0,'L');
    $ActlOvrTm = getData($conn
                  ,"select to_char(ECD_ACTUAL_BREAKTIME,'DD-MM-YYYY HH24:MI') GET_DATA from emp_coupon_data
                  where ECD_ECO_SYS_ID='".$rowData['ECO_SYS_ID']."'");
    $pdf->Cell($vActl,6,$ActlOvrTm,1,1,'C');
}
$pdf->Output();
?>
