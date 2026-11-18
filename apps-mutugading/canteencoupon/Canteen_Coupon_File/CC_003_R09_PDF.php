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

$P_DATE_TO_S= "";
  if(isset($_POST['P_DATE_TO_S'])) {
    $P_DATE_TO_S=$_POST['P_DATE_TO_S'];
  }

if ($P_DATE_TO_S=== ""){
  if(isset($_GET['P_DATE_TO'])) {
    $P_DATE_TO_S=$_GET['P_DATE_TO'];
  }
}

$P_FORM_NAME_S="";
if ($P_FORM_NAME_S=== ""){
  if(isset($_GET['P_FORM_NAME'])) {
    $P_FORM_NAME_S=$_GET['P_FORM_NAME'];
  }
}
include($P_FORM_NAME_S."_R09_SQL.PHP");

$P_REPORT_ID_S="";
if ($P_REPORT_ID_S=== ""){
  if(isset($_GET['P_REPORT_ID'])) {
    $P_REPORT_ID_S=$_GET['P_REPORT_ID'];
  }
}

$P_REPORT_NAME_S = getData($conn,"select DESCRIPTION GET_DATA from mst_reports where REPORT_ID = '$P_REPORT_ID_S'");

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
//$pdf->Cell(0,7,$descCouponTambahan,0,1,'C');
// //$pdf->Cell(190,7,'Laporan Pendapatan Pengeluaran('.$NAMA_MODUL.')',0,1,'C');
$pdf->Cell(0,7,"PERIODE : $P_DATE_FROM_S s/d $P_DATE_TO_S",0,1,'C');

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);
$lnStrt = 10;$lnHgt = 6;
$vLNo = 10;$vTglKpn = 15;$vNik = 15;$vWktScn = 25;$vKetPrcs = 37;$vKetHrm = 50;$vUpdOleh = 30;

$pdf->SetFont('Arial','B',7);
//$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell($vLNo,$lnHgt,'No',1,0,'C');
$pdf->Cell($vTglKpn,$lnHgt,'TGL Kupon',1,0,'C');
$pdf->Cell($vNik,$lnHgt,'NIK',1,0,'C');
$pdf->Cell($vWktScn,$lnHgt,'Waktu Scan',1,0,'C');
$pdf->Cell($vKetPrcs,$lnHgt,'Keterangan Process',1,0,'C');
$pdf->Cell($vKetHrm,$lnHgt,'Keterangan HRM',1,0,'C');
$pdf->Cell($vUpdOleh,$lnHgt,'Update Oleh',1,1,'C');

include($P_FORM_NAME_S."_R09_SQL.PHP");

$sqlView = " select rownum NOREC,d.* from ($sqlData)d ";
//echo "$sqlView </br>";
$rsView = oci_parse($conn,$sqlView);
oci_execute ($rsView);
while ($rowView = oci_fetch_array ($rsView, OCI_BOTH)) {

  $pdf->Cell($vLNo,$lnHgt,$rowView['NOREC'],1,0,'C');
  $pdf->Cell($vTglKpn,$lnHgt,$rowView['ECES_COUPON_DATE'],1,0,'C');
  $pdf->Cell($vNik,$lnHgt,$rowView['ECES_PE_NIK'],1,0,'C');
  $pdf->Cell($vWktScn,$lnHgt,$rowView['ECES_CREATED_TIMESTAMP'],1,0,'C');
  $pdf->Cell($vKetPrcs,$lnHgt,$rowView['ECES_REMARK_PROCESS'],1,0,'L');
  $pdf->Cell($vKetHrm,$lnHgt,$rowView['ECES_REMARK_HRM'],1,0,'L');
  if (!empty($rowView['USER_MODIFIED_BY'])){
      $pdf->Cell($vUpdOleh,$lnHgt,$rowView['USER_MODIFIED_BY'],1,1,'C');
  } else {
    $pdf->Cell($vUpdOleh,$lnHgt,'',1,1,'C');
  }
}

$pdf->Output();
?>
