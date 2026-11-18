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
include($P_FORM_NAME_S."_R01_SQL.PHP");

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
// //$pdf->Cell(190,7,'Laporan Pendapatan Pengeluaran('.$NAMA_MODUL.')',0,1,'C');
$pdf->Cell(0,7,"Dari Tanggal $P_DATE_FROM_S s/d $P_DATE_TO_S",0,1,'C');

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);

$vLNo = 10;$vLDate = 17;$vLS1 = 15;$vLSGs = 15;$vLS2 = 15;$vLS3 = 15;$vLSUi = 20;$vLT = 15;
$vStjKntn = 23;$vStjHrm = 23;

$pdf->SetFont('Arial','B',7);
$pdf->Cell($vLNo,6,'No',1,0,'C');
$pdf->Cell($vLDate,6,'Date',1,0,'C');
$pdf->Cell($vLS1,6,'Shift I',1,0,'C');
$pdf->Cell($vLSGs,6,'GS',1,0,'C');
$pdf->Cell($vLS2,6,'Shift II',1,0,'C');
$pdf->Cell($vLS3,6,'Shift III',1,0,'C');
$pdf->Cell($vLSUi,6,'Diluar Jadwal',1,0,'C');
$pdf->Cell($vLT,6,'Total',1,0,'C');
$pdf->Cell($vStjKntn,6,'Disetujui Kantin',1,0,'C');
$pdf->Cell($vStjHrm,6,'Disetujui Hrm',1,1,'C');


$sqlView =  "select * from (select rownum RECNO,to_char(ECD_DATE,'DD-MM-YYYY') ECD_DATE, TOT_S1, TOT_SGS, TOT_S2, TOT_S3, TOT_SUD
                                   ,nvl(TOT_S1,0)+nvl(TOT_SGS,0)+nvl(TOT_S2,0)+nvl(TOT_S3,0)+nvl(TOT_SUD,0) TOTAL
                            from ($sqlData ORDER BY ECD_DATE) t ) t ";

$rsData = oci_parse($conn,$sqlView);
oci_execute ($rsData);
$pdf->SetFont('Arial','',7);
$lnPurpose = 0;
//$maxLnPurpose = 50;
$TotKntn = 0; $TotHrm = 0;
while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $pdf->Cell($vLNo,6,$rowData['RECNO'],1,0,'C');
    $pdf->Cell($vLDate,6,$rowData['ECD_DATE'],1,0,'C');
    $pdf->Cell($vLS1,6,$rowData['TOT_S1'],1,0,'R');
    $pdf->Cell($vLSGs,6,$rowData['TOT_SGS'],1,0,'R');
    $pdf->Cell($vLS2,6,$rowData['TOT_S2'],1,0,'R');
    $pdf->Cell($vLS3,6,$rowData['TOT_S3'],1,0,'R');
    $pdf->Cell($vLSUi,6,$rowData['TOT_SUD'],1,0,'R');
    $pdf->Cell($vLT,6,$rowData['TOTAL'],1,0,'R');
    $sqlGtDt = "select  case when     ECDS_APPROVE_KANTIN_DATE is not null then
                                      to_char(ECDS_APPROVE_KANTIN_DATE,'DD-MM-YYYY HH24:MI')
                        else ' '
                        end  GET_DATA
                from EMP_COUPON_DATA_SUM
                where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowData['ECD_DATE']."'";
    $DtKantin = getData($conn,$sqlGtDt);
    $pdf->Cell($vStjKntn,6,$DtKantin,1,0,'C');
    if ($DtKantin !==" " && $DtKantin !==""){
        $TotKntn = $TotKntn + $rowData['TOTAL'];
    }
    $sqlGtDt = "select  case when     ECDS_APPROVE_HRM_DATE is not null then
                                      to_char(ECDS_APPROVE_HRM_DATE,'DD-MM-YYYY HH24:MI')
                        else ' '
                        end  GET_DATA
                from EMP_COUPON_DATA_SUM
                where to_char(ECDS_DATE,'DD-MM-YYYY') = '".$rowData['ECD_DATE']."'";
    $DtHrm = getData($conn,$sqlGtDt);
    $pdf->Cell($vStjHrm,6,$DtHrm,1,1,'C');
    if ($DtHrm !==" " && $DtHrm !=="" ){
        $TotHrm = $TotHrm + $rowData['TOTAL'];
    }

}
$sqlTotRows = "select sum(TOTAL) TOT_DATA from ( $sqlView ) ";
$totAll = getTotData($conn,$sqlTotRows);
$pdf->Cell($vLNo,6,'',0,0,'C');
$pdf->Cell($vLDate,6,'',0,0,'C');
$pdf->Cell($vLS1,6,'',0,0,'R');
$pdf->Cell($vLSGs,6,'',0,0,'R');
$pdf->Cell($vLS2,6,'',0,0,'R');
$pdf->Cell($vLS3,6,'',0,0,'R');
$pdf->Cell($vLSUi,6,'Total',1,0,'R');
$pdf->Cell($vLT,6,number_format($totAll),1,0,'R');
$pdf->Cell($vStjKntn,6,number_format($TotKntn),1,0,'R');
$pdf->Cell($vStjHrm,6,number_format($TotHrm),1,1,'R');
$pdf->Output();
?>
