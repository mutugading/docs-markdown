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
include($P_FORM_NAME_S."_R04_SQL.PHP");

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
$pdf->Cell(0,7,"PERIODE : $P_DATE_FROM_S s/d $P_DATE_TO_S",0,1,'C');

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);
$lnStrt = 10;$lnHgt = 6;
$vLNo = 10;$vLHr = 25;$vLDate = 25;
$vLS1 = 15;$vLSGs = 15;$vLS2 = 15;$vLS3 = 15;$vLSUi = 20;$vLT = 15;
$vGrnTot = 23;$vStjHrm = 23;

$pdf->SetFont('Arial','B',7);
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell($vLNo,$lnHgt,'No',1,0,'C');
$pdf->Cell($vLHr,$lnHgt,'Hari',1,0,'C');
$pdf->Cell($vLDate,$lnHgt,'Tanggal',1,0,'C');
$pdf->Cell($vLS1,$lnHgt,'Shift 1',1,0,'C');
$pdf->Cell($vLSGs,$lnHgt,'GS',1,0,'C');
$pdf->Cell($vLS2,$lnHgt,'Shift 2',1,0,'C');
$pdf->Cell($vLS3,$lnHgt,'Shift 3',1,0,'C');
$pdf->Cell($vLSUi,$lnHgt,'Luar Jadwal',1,0,'C');
$pdf->Cell($vGrnTot,$lnHgt,'Grand Total',1,1,'C');


$sqlView =  "select rownum RECNO,t.* from ($sqlData) t ";
//$sqlView = "select * from ($sqlView) where RECNO between '$P_DATE_FROM_S' and $P_DATE_TO_S ";

//echo $sqlView;die();

$rsData = oci_parse($conn,$sqlView);
oci_execute ($rsData);
$pdf->SetFont('Arial','',7);
$totPgS1 = 0; $totPgGs = 0; $totPgS2 = 0; $totPgS3 = 0; $totPgOths = 0; $totPgGrd = 0;
while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $totPgS1 = $totPgS1+$rowData['ECDS_TOT_SHIFT_1']; $totPgGs = $totPgGs+$rowData['ECDS_TOT_SHIFT_GS'];
    $totPgS2 = $totPgS2+$rowData['ECDS_TOT_SHIFT_2']; $totPgS3 = $totPgS3+$rowData['ECDS_TOT_SHIFT_3'];
    $totPgOths = $totPgOths+$rowData['ECDS_TOT_OTHERS']; $totPgGrd = $totPgGrd+$rowData['GRAND_TOT'];
    $pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
    $pdf->Cell($vLNo,$lnHgt,$rowData['RECNO'],1,0,'R');
    $pdf->Cell($vLHr,$lnHgt,$rowData['HARI'],1,0,'L');
    $pdf->Cell($vLDate,$lnHgt,$rowData['ECDS_DATE_1'],1,0,'C');
    $pdf->Cell($vLS1,$lnHgt,$rowData['ECDS_TOT_SHIFT_1'],1,0,'R');
    $pdf->Cell($vLSGs,$lnHgt,$rowData['ECDS_TOT_SHIFT_GS'],1,0,'R');
    $pdf->Cell($vLS2,$lnHgt,$rowData['ECDS_TOT_SHIFT_2'],1,0,'R');
    $pdf->Cell($vLS3,$lnHgt,$rowData['ECDS_TOT_SHIFT_3'],1,0,'R');
    $pdf->Cell($vLSUi,$lnHgt,$rowData['ECDS_TOT_OTHERS'],1,0,'R');
    $pdf->Cell($vGrnTot,$lnHgt,number_format($rowData['GRAND_TOT']),1,1,'R');
}
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell($vLNo,$lnHgt,'',1,0,'C');
$pdf->Cell($vLHr,$lnHgt,'',1,0,'C');
$pdf->Cell($vLDate,$lnHgt,'Total',1,0,'R');
$pdf->Cell($vLS1,$lnHgt,number_format($totPgS1),1,0,'R');
$pdf->Cell($vLSGs,$lnHgt,number_format($totPgGs),1,0,'R');
$pdf->Cell($vLS2,$lnHgt,number_format($totPgS2),1,0,'R');
$pdf->Cell($vLS3,$lnHgt,number_format($totPgS3),1,0,'R');
$pdf->Cell($vLSUi,$lnHgt,number_format($totPgOths),1,0,'R');
$pdf->Cell($vGrnTot,$lnHgt,number_format($totPgGrd),1,1,'R');


$rateKupon = getData($conn,"select mpd_name GET_DATA from mst_param_data where MPD_sys_id = '20210400039' ");
$totHrg = (int) $rateKupon * $totPgGrd;

$trBlng = getData($conn,"select mgtdat.MGT_SAYMONEY($totHrg) GET_DATA from dual");

$pdf->Cell($vLNo,2,'',0,1,'C');

$lnHgt = 3;
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(16,$lnHgt,"TOTAL",0,0,"L");
$pdf->Cell(150,$lnHgt,": ".number_format($totPgGrd)." X @".number_format($rateKupon)." = ".number_format($totHrg),0,1,"L");
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(16,$lnHgt,"TERBILANG",0,0,"L");
$pdf->Cell(150,$lnHgt,": $trBlng",0,1,"L");

$pdf->Cell($vLNo,10,'',0,1,'C');

$lnHgt = 3.5;
$pdf->SetFont('Arial','b',10);
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(75,$lnHgt,"Mengetahui",0,0,"C");
$bln = getData($conn,"select initcap(MGTAPPS.fBulan_Indo(to_number(to_char(sysdate,'mm')))) GET_DATA from dual");
$thn = getData($conn,"select to_char(sysdate,'YYYY') GET_DATA from dual");
$pdf->Cell(75,$lnHgt,"karanganyar,    $bln $thn",0,1,"C");

$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(75,$lnHgt,"PT. Mutu Gading Tekstil",0,0,"C");
$pdf->Cell(75,$lnHgt,"",0,1,"C");

$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(75,$lnHgt,"Human Resources Management",0,0,"C");
$prshNm = getData($conn,"select MPD_NAME GET_DATA from MST_PARAM_DATA where mpd_sys_id = '20210400042' ");
$pdf->Cell(75,$lnHgt,$prshNm,0,1,"C");


$pdf->Cell($lnStrt,20,"",0,1,"L");
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");

$nmHrd = getData($conn,"select MPD_NAME GET_DATA from MST_PARAM_DATA where mpd_sys_id = '20210400041' ");
$pdf->SetFont('Arial','U',10);
$pdf->Cell(75,$lnHgt,"$nmHrd",0,0,"C");
$nmKnt = getData($conn,"select MPD_NAME GET_DATA from MST_PARAM_DATA where mpd_sys_id = '20210400043' ");
$pdf->Cell(75,$lnHgt,$nmKnt,0,1,"C");

$pdf->Cell($lnStrt,3,"",0,1,"L");
$pdf->Cell($lnStrt,$lnHgt,"",0,0,"L");
$pdf->Cell(75,$lnHgt,"HOD HRM",0,0,"C");
$pdf->Cell(75,$lnHgt,"Pengelola",0,1,"C");

$pdf->Output();
?>
