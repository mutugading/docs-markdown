<?php session_start(); ?>
<?php
include("conOraOci.php");
//include ("check_login.php");
include ("common_function.php");
$P_DATE_FROM = "";
if(isset($_GET['P_DATE_FROM'])) {
    $P_DATE_FROM=$_GET['P_DATE_FROM'];
}
$P_DATE_TO = "";
if(isset($_GET['P_DATE_TO'])) {
    $P_DATE_TO=$_GET['P_DATE_TO'];
}
$P_MENU_ID= "";
if(isset($_GET['P_MENU_ID'])) {
    $P_MENU_ID=$_GET['P_MENU_ID'];
}
$P_FILTER= "";
if(isset($_GET['P_FILTER'])) {
    $P_FILTER=$_GET['P_FILTER'];
}
$P_FILTER_VALUE= "";
if(isset($_GET['P_FILTER_VALUE'])) {
    $P_FILTER_VALUE=$_GET['P_FILTER_VALUE'];
}

$P_USER_NAME= "";
if (isset($_SESSION['username'])) {
  $P_USER_NAME = $_SESSION['username'];
}

$usrAreaLgn = getData($conn,"select nvl(USER_AREA,'ALL') GET_DATA from mgtapps.mst_USERs where USER_ID = '$P_USER_NAME' ");

require('fpdf17/fpdf.php');
// // intance object dan memberikan pengaturan halaman PDF
$pdf = new FPDF('L','mm','A4');
// membuat halaman baru
$pdf->AddPage();
$pdf->SetFont('Arial','B',15);
$pdf->Image('../USERLOGO.jpg',5,3,20);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,3,"PT MUTU GADING TEKSTIL",0,1,'C');
$pdf->SetFont('Arial','B',11);
// mencetak string
$menuName =  getData($conn,"select menu_name GET_DATA from mst_menus where menu_id = '$P_MENU_ID'");
$pdf->Cell(0,5,$menuName,0,1,'C');
$pdf->SetFont('Arial','',9);
$pdf->Cell(0,3,"Date From $P_DATE_FROM to $P_DATE_TO",0,1,'C');
// $pdf->Cell(0,3,"Filter By $P_FILTER = $P_FILTER_VALUE ",0,1,'C');
//
// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);
$pdf->SetFont('Arial','B',7);

$lnNo = 5; $TrxNo = 25; $Dt = 15;
$Supp = 70; $BillNo = 40; $BillDate = 17;

$pdf->Cell($lnNo,6,'No',1,0,'C');
$pdf->Cell($TrxNo,6,'Invoice No',1,0,'C');
$pdf->Cell($Dt,6,'Date',1,0,'C');
$pdf->Cell($Supp,6,'Supplier',1,0,'C');
$pdf->Cell($BillNo,6,'Customer',1,0,'C');
$pdf->Cell($BillNo,6,'Currency',1,0,'C');
$pdf->Cell($BillNo,6,'Term Code',1,0,'C');
$pdf->Cell($BillNo,6,'Sales AMT',1,0,'C');
$pdf->Cell($BillNo,6,'Sales TAX',1,0,'C');
$pdf->Cell($BillNo,6,'Sales Total',1,0,'C');
$pdf->Cell($BillNo,6,'FP Sales',1,0,'C');
$pdf->Cell($BillDate,6,'DN No',1,1,'C');

//No ; Invoice No ; Date ; Customer ; Currency ; Term Code ; Sales AMT ; Sales TAX
//; Sales Total ; FP Sales ;

Kwitansi
//
//
// $DATE_FROM_S = $P_DATE_FROM;
// $DATE_TO_S = $P_DATE_TO;
//
// //echo "P_FILTER $P_FILTER ; P_FILTER_VALUE $P_FILTER_VALUE";
//
// $sqlWhere = "";
//     if ($P_FILTER==="Trx No"){
//         $sqlWhere = " AND tpb_trx_no like '%$P_FILTER_VALUE%' ";
//     }
//
//     if ($P_FILTER==="Date"){
//         $sqlWhere = " AND  to_char(tpb_dt,'YYYY-MM-DD') = '$P_FILTER_VALUE' ";
//     }
//
//     if ($P_FILTER==="Supplier"){
//         $sqlWhere = " AND upper(tpb_supp_name) like '%$P_FILTER_VALUE%' ";
//     }
//
//     if ($P_FILTER==="Bill No"){
//         $sqlWhere = " AND upper(tpb_bill_no) like '%$P_FILTER_VALUE%' ";
//     }
//
//     if ($P_FILTER==="Bill Date"){
//         $sqlWhere = " AND  to_char(tpb_bill_dt,'YYYY-MM-DD') = '$P_FILTER_VALUE' ";
//     }
//
// include("EFILL_010_SQL.php");
// $sqlData = "select distinct TPB_TRX_NO, TO_CHAR(TPB_DT,'YYYY-MM-DD') TPB_DT, TPB_SUPP_NAME, TPB_BILL_NO, to_char(TPB_BILL_DT,'YYYY-MM-DD') TPB_BILL_DT from ($sqlData $sqlWhere )";
// //echo $sqlData;die();
// $rsData = oci_parse($conn,$sqlData);
// oci_execute ($rsData);
// $recNo = 0;
// $LpData = "";
//
// $SuppDt = 17; $BillNoDt = 25; $USRDt = 10;
//
// while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
//     $recNo++;
//
//     // lp 0
//     $pdf->Cell($lnNo,6,$recNo,1,0,'R');
//     $pdf->Cell($TrxNo,6,$rowData['TPB_TRX_NO'],1,0,'C');
//     $pdf->Cell($Dt,6,$rowData['TPB_DT'],1,0,'C');
//
//     $SUPP_NAME = "";
//     if (!empty($rowData['TPB_SUPP_NAME'])){
//         $SUPP_NAME = substr($rowData['TPB_SUPP_NAME'],0,$SuppDt);
//     }
//     $pdf->Cell($Supp,6,$SUPP_NAME,1,0,'L');
//
//     $TPB_BILL_NO = "";
//     if (!empty($rowData['TPB_BILL_NO'])){
//         $TPB_BILL_NO = $rowData['TPB_BILL_NO'];
//     }
//     $pdf->Cell($BillNo,6,$TPB_BILL_NO,1,0,'l');
//
//     if (!empty($rowData['TPB_BILL_DT'])){
//         $pdf->Cell($BillDate,6,$rowData['TPB_BILL_DT'],1,1,'R');
//     } else $pdf->Cell($BillDate,6,"",1,1,'R');
//
// }
$pdf->Output();
?>
