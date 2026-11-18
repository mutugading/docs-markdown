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
// intance object dan memberikan pengaturan halaman PDF
$pdf = new FPDF('P','mm','A4');
// membuat halaman baru
$pdf->AddPage();
$pdf->SetFont('Arial','B',15);
$pdf->Image('USERLOGO.jpg',5,3,20);
$pdf->SetFont('Arial','',11);
$pdf->Cell(0,3,"PT MUTU GADING TEKSTIL",0,1,'C');
$pdf->SetFont('Arial','B',11);
// mencetak string 
//$pdf->Cell(190,7,'PENERUS RUKUN MAKMUR',0,1,'C');
$menuName =  getData($conn,"select menu_name GET_DATA from mst_menus where menu_id = '$P_MENU_ID'");      
$pdf->Cell(0,5,$menuName,0,1,'C');
$pdf->SetFont('Arial','',9);
// //$pdf->Cell(190,7,'Laporan Pendapatan Pengeluaran('.$NAMA_MODUL.')',0,1,'C');
$pdf->Cell(0,3,"Date From $P_DATE_FROM to $P_DATE_TO",0,1,'C');
$pdf->Cell(0,3,"Filter By $P_FILTER = $P_FILTER_VALUE ",0,1,'C');

// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);

$pdf->SetFont('Arial','B',7);

$lnNo = 5; $PymntNo = 25; $DocDt = 15; 
$Supp = 29; $SuppBI = 42; $Amnt = 17; $BAG = 25; $USR = 15; $AREA = 15;

$pdf->Cell($lnNo,6,'No',1,0,'C');
$pdf->Cell($PymntNo,6,'Payment No',1,0,'C');
$pdf->Cell($DocDt,6,'DOC. Date',1,0,'C');
$pdf->Cell($Supp,6,'Supplier',1,0,'C');
$pdf->Cell($SuppBI,6,'Supplier Bank Information',1,0,'C');
$pdf->Cell($Amnt,6,'Amount',1,0,'C');
$pdf->Cell($BAG,6,'Bank Account MGT',1,0,'C');
$pdf->Cell($USR,6,'User',1,0,'C');
$pdf->Cell($AREA,6,'Area User',1,1,'C');

$DATE_FROM_S = $P_DATE_FROM;
$DATE_TO_S = $P_DATE_TO;

//echo "P_FILTER $P_FILTER ; P_FILTER_VALUE $P_FILTER_VALUE";

$sqlWhere = "";
    if ($P_FILTER==="Payment No"){
        $sqlWhere = " AND PAYMENT_NO like '%$P_FILTER_VALUE%' ";
    }

    if ($P_FILTER==="Supplier Name"){
        $sqlWhere = " AND SUPP_NAME like '%'||upper('$P_FILTER_VALUE')||'%' ";   
    }

    if ($P_FILTER==="Area User"){
        if ($P_FILTER_VALUE!=="ALL") {
            $sqlWhere = " AND upper(USER_FIELD_01) = upper('$P_FILTER_VALUE') "; 
        }
    }

    if ($usrAreaLgn!=="ALL"){ 
        $sqlWhere = " AND upper(USER_FIELD_01) = upper('$usrAreaLgn') ";
    }       

include("EFILL_007_SQL.php");
$sqlData = "select distinct PAYMENT_NO, PH_DOC_DT,SUPP_NAME,PH_BANK_ACNT_NO, BANK_NAME,SBC_BANK_NAME,SBC_BANK_ACNT_NO,SBC_BANK_ACNT_NAME,USER_DESC
                    ,USER_FIELD_01,PH_FC_AMT,PH_BANK_ACNT_NO,PH_CURR_CODE 
            from ($sqlData $sqlWhere )  order by payment_no ";
//echo $sqlData;die();
$rsData = oci_parse($conn,$sqlData);              
oci_execute ($rsData);
$recNo = 0;
$LpData = "";

$SuppDt = 17; $SuppBIDt = 25; $USRDt = 10;

while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
    $recNo++;

    // lp 0
    $pdf->Cell($lnNo,6,$recNo,1,0,'R');
    $pdf->Cell($PymntNo,6,$rowData['PAYMENT_NO'],1,0,'C');
    $pdf->Cell($DocDt,6,$rowData['PH_DOC_DT'],1,0,'C');

    $SUPP_NAME = ""; 
    if (!empty($rowData['SUPP_NAME'])){
        $SUPP_NAME = substr($rowData['SUPP_NAME'],0,$SuppDt);    
    }
    $pdf->Cell($Supp,6,$SUPP_NAME,1,0,'L');

    $SBC_BANK_NAME = "";
    if (!empty($rowData['SBC_BANK_NAME'])){
        $SBC_BANK_NAME = $rowData['SBC_BANK_NAME'];    
    }
    $pdf->Cell($SuppBI,6,$SBC_BANK_NAME,1,0,'l');

    if (!empty($rowData['PH_CURR_CODE'])){
        $pdf->Cell($Amnt,6,$rowData['PH_CURR_CODE'],1,0,'R');
    } else $pdf->Cell($Amnt,6,"",1,0,'R');    

    if (!empty($rowData['PH_BANK_ACNT_NO'])){
        $pdf->Cell($BAG,6,$rowData['PH_BANK_ACNT_NO'],1,0,'C');    
    } else $pdf->Cell($BAG,6,"",1,0,'C');
    

    if (!empty($rowData['USER_DESC'])){
        $pdf->Cell($USR,6,substr($rowData['USER_DESC'],0,$USRDt),1,0,'C');
    } else $pdf->Cell($USR,6,"",1,0,'C');
        
    if (!empty($rowData['USER_FIELD_01'])){
        $pdf->Cell($USR,6,$rowData['USER_FIELD_01'],1,1,'C');
    } else $pdf->Cell($USR,6,"",1,1,'C');

    // lp 0

    $lp = 1;
    do {        

        if ($lp===1){

            $pdf->Cell($lnNo,6,"",1,0,'R');
            $pdf->Cell($PymntNo,6,"",1,0,'C');
            $pdf->Cell($DocDt,6,"",1,0,'C');

            $SUPP_NAME = ""; 
            if (!empty($rowData['SUPP_NAME'])){
                $SUPP_NAME = substr($rowData['SUPP_NAME'],$SuppDt,$SuppDt);    
            }
            $pdf->Cell($Supp,6,$SUPP_NAME,1,0,'L');

            $SBC_BANK_ACNT_NO = "";
            if (!empty($rowData['SBC_BANK_ACNT_NO'])){
                $SBC_BANK_ACNT_NO = $rowData['SBC_BANK_ACNT_NO'];    
            }
            $pdf->Cell($SuppBI,6,$SBC_BANK_ACNT_NO,1,0,'l');

            $pdf->Cell($Amnt,6,number_format($rowData['PH_FC_AMT']),1,0,'R');
            $pdf->Cell($BAG,6,'',1,0,'C');
            
            if (!empty($rowData['USER_DESC'])){
                $pdf->Cell($USR,6,substr($rowData['USER_DESC'],$USRDt),1,0,'C');
            } else $pdf->Cell($USR,6,"",1,0,'C');            

            $pdf->Cell($USR,6,"",1,1,'C');
            
        }

         if ($lp===2){

            $pdf->Cell($lnNo,6,"",1,0,'R');
            $pdf->Cell($PymntNo,6,"",1,0,'C');
            $pdf->Cell($DocDt,6,"",1,0,'C');

            $SUPP_NAME = ""; 
            if (!empty($rowData['SUPP_NAME'])){
                $SUPP_NAME = substr($rowData['SUPP_NAME'],$SuppDt*2,$SuppDt);    
            }
            $pdf->Cell($Supp,6,$SUPP_NAME,1,0,'L');

            $SBC_BANK_ACNT_NAME = "";
            if (!empty($rowData['SBC_BANK_ACNT_NAME'])){
                $SBC_BANK_ACNT_NAME = $rowData['SBC_BANK_ACNT_NAME'];    
            }
            $pdf->Cell($SuppBI,6,substr($SBC_BANK_ACNT_NAME,0,$SuppBIDt),1,0,'l');

            $pdf->Cell($Amnt,6,'',1,0,'C');
            $pdf->Cell($BAG,6,'',1,0,'C');
            $pdf->Cell($USR,6,"",1,0,'L');               
            $pdf->Cell($USR,6,"",1,1,'L');

            if (strlen($SBC_BANK_ACNT_NAME) >= $SuppBIDt) {
                $pdf->Cell($lnNo,6,"",1,0,'R');
                $pdf->Cell($PymntNo,6,"",1,0,'C');
                $pdf->Cell($DocDt,6,"",1,0,'C');
                $pdf->Cell($Supp,6,"",1,0,'L');
                $pdf->Cell($SuppBI,6,substr($SBC_BANK_ACNT_NAME,$SuppBIDt),1,0,'l');
                $pdf->Cell($Amnt,6,'',1,0,'C');
                $pdf->Cell($BAG,6,'',1,0,'C');
                $pdf->Cell($USR,6,"",1,0,'L');               
                $pdf->Cell($USR,6,"",1,1,'L');
            }            
            
        }

        $lp++;
    } while ($lp <= 2);



}

// setting jenis font yang akan digunakan
/*$pdf->SetFont('Arial','B',22);
$sqlView = "";
include("FIND_DATA_SQL.PHP");
//echo $sqlView;//die();
$rsData = oci_parse($conn,$sqlView);              
oci_execute ($rsData);
$lnStrt = 60;$lnHgt = 6;$lnTi2k = 3; $lnP = 102;

while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
// mencetak string 
$EAH_REP_LABLE_1 = getData($conn,"select EAH_REP_LABLE_1 GET_DATA from EVENT_ATTD_HDR where EAH_SYS_ID = '$EAH_SYS_ID_FD'");   
//$pdf->Cell(0,0,"BERSAMA TNI DAN WALUBI",0,1,'C');
$pdf->Cell(0,0,"$EAH_REP_LABLE_1",0,1,'C');
$EAH_REP_LABLE_2 = getData($conn,"select EAH_REP_LABLE_2 GET_DATA from EVENT_ATTD_HDR where EAH_SYS_ID = '$EAH_SYS_ID_FD'"); 
$pdf->SetFont('Arial','B',15);
//$pdf->Cell(0,13,"Tanggal 17-18 September 2021",0,1,'C');
$pdf->Cell(0,13,"$EAH_REP_LABLE_2",0,1,'C');
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,3,"Di PT MUTU GADING TEKSTIL",0,1,'C');
//$pdf->Cell(0,3,"Tanggal 14-15 Agustus 2021",0,1,'C');


// // Memberikan space kebawah agar tidak terlalu rapat
$pdf->Cell(10,7,'',0,1);

$pdf->SetFont('Arial','B',17);
$lngthPage = 190;
    $pdf->Cell($lngthPage,0,'UNDANGAN NOMOR '.$rowData['EDR_REG_NO'],0,1,'C');
    $pdf->Cell(10,1,'',0,1);
    $pdf->SetFont('Arial','',9);

    $pdf->Cell($lngthPage,$lnHgt,str_pad("=",$lnP, "=", STR_PAD_LEFT),0,1,'c');
    $lnHgt = 3.5 ;
    $pdf->Cell($lnStrt,$lnHgt,'Tipe Peserta',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_TYPE'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'NIK (MGT)',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_PE_NIK'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'NIK (KTP)',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_INDENTITY_TYPE']."-".$rowData['EDR_INDENTITY_NO'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'NAMA (KTP)',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_NAME'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'Tanggal Lahir',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_BIRTH_DATE'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'Tanggal dan Waktu Vaksin',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_DAYS'].", ".$rowData['EDR_DATE']."  ".$rowData['EDR_TIME_FROM']." - ".$rowData['EDR_TIME_TO'],0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'No Telp',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    if (!empty($rowData['EDR_ADDRESS'])){
        $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_NO_TELP'],0,1,'L');
    } else $pdf->Cell($lnStrt,$lnHgt," ",0,1,'L');

    $pdf->Cell($lnStrt,$lnHgt,'Alamat',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');

    if (empty($rowData['EDR_ADDRESS'])){
        $pdf->Cell($lnStrt,$lnHgt," ",0,1,'L');
    } else $pdf->Cell($lnStrt,$lnHgt,substr($rowData['EDR_ADDRESS'],0,100) ,0,1,'L');    
    

    if (strlen($rowData['EDR_ADDRESS']) > 100) {
        $pdf->Cell($lnStrt,$lnHgt,'',0,0,'R');
        $pdf->Cell($lnTi2k,$lnHgt,'',0,0,'l');

        if (empty($rowData['EDR_ADDRESS'])){
            $pdf->Cell($lnStrt,$lnHgt," ",0,1,'L');        
        }
        else $pdf->Cell($lnStrt,$lnHgt,substr($rowData['EDR_ADDRESS'],100) ,0,1,'L');        
    }

    $pdf->Cell($lnStrt,$lnHgt,'Remarks',0,0,'R');
    $pdf->Cell($lnTi2k,$lnHgt,':',0,0,'l');
    $pdf->Cell($lnStrt,$lnHgt,$rowData['EDR_REMARKS'],0,1,'L');
}    
$pdf->Cell($lngthPage,$lnHgt,str_pad("=",$lnP, "=", STR_PAD_LEFT),0,1,'c');
$pdf->Image('USERLOGO.jpg',10,78,20);
//$pdf->Image('WALUBI.jpg',112,100,130);
$pdf->Cell(10,3,'',0,1);
$pdf->SetFont('Arial','',8);
$lnHgt = 3;
$lnStrt = 100;
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Hadir 30 menit sebelum jadwal yang ditentukan",100,1,'L');
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Memakai masker, menjaga jarak, membawa sanitizer",0,1,'L');
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Membawa KTP asli",0,1,'L');
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Membawa bulpen",0,1,'L');
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Nomor handphone harus diisi dan bisa digunakan untuk SMS",0,1,'L');
$pdf->Cell(20,$lnHgt,"",100,0,'L');
$pdf->Cell($lnStrt,$lnHgt,"- Nomor undangan ditunjukkan saat registrasi",0,1,'L');*/
$pdf->Output();
?>