<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
$P_MENU_ID="";if(isset($_GET['P_MENU_ID'])){$P_MENU_ID=$_GET['P_MENU_ID'];}
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ($FORM_NAME."_SQL.php");
$DOCUMENT_TYPE="";if(isset($_GET['P_DOCUMENT_TYPE'])){$DOCUMENT_TYPE=$_GET['P_DOCUMENT_TYPE'];}
$DATE_FROM="";if(isset($_GET['P_DATE_FROM'])){$DATE_FROM=$_GET['P_DATE_FROM'];}
$DATE_TO="";if(isset($_GET['P_DATE_TO'])){$DATE_TO=$_GET['P_DATE_TO'];}
$FILE_STATUS = "";	if(isset($_GET['P_FILE_STATUS'])){$FILE_STATUS=$_GET['P_FILE_STATUS'];}
if ($FILE_STATUS===""){$FILE_STATUS = "All File";}

$USER_DESC_FLTR= "";	if(isset($_GET['USER_DESC_FLTR'])){$USER_DESC_FLTR=$_GET['USER_DESC_FLTR'];}
if ($USER_DESC_FLTR===""){$USER_DESC_FLTR = "All";}
//$sqlDt = getSqlView ($DATE_FROM,$DATE_TO,$DOCUMENT_TYPE);
$sqlDt = getSqlData($DATE_FROM,$DATE_TO);

$whrDt=" where TH_TRAN_CODE='$DOCUMENT_TYPE' ";
if ($USER_DESC_FLTR!=="All"){
  $whrDt=" $whrDt and USER_DESC='$USER_DESC_FLTR' ";
}

$sqlDt = "select
              TH_TRAN_CODE TRX_FOLDER
              ,SUBSTR (TH_DOC_NO, 1, 4) TRX_YY
              ,TH_TRAN_CODE|| '-' ||TH_DOC_NO TRX_NO
              ,TO_CHAR (TH_DOC_DT,'YYYY-MM-DD') TRX_DT
              ,TH_DESC
              ,TH_CR_UID CRT_BY
              ,USER_DESC CRT_DESC
              ,TO_CHAR (TH_CR_DT, 'YYYY-MM-DD') CRT_DT
          from (select a.* from ($sqlDt)a $whrDt ) a order by TH_DOC_DT desc,TH_TRAN_CODE || '-' || TH_DOC_NO
          ";
?>

<html>
<head>
</head>
<body>
    <style type="text/css">
    body{
        font-family: sans-serif;
    }
    table{
        margin: 20px auto;
        border-collapse: collapse;
    }
    table th,
    table td{
        border: 1px solid #3c3c3c;
        padding: 3px 8px;

    }
    a{
        background: blue;
        color: #fff;
        padding: 8px 10px;
        text-decoration: none;
        border-radius: 2px;
    }
    </style>
<?php
$excelNm = "Check Scan Document.xls";
$menuName =  getData($conn,"select menu_name GET_DATA from mst_menus where menu_id = '$P_MENU_ID'");
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr><td>Report : $menuName </td></tr>";
echo "<tr><td>Document Type $DOCUMENT_TYPE Period $DATE_FROM - $DATE_TO Status File $FILE_STATUS</td></tr>";
echo "<tr></tr>";
echo "</table>";
if ($FILE_STATUS==="All File"){
?>
  <div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:100%" border="1">
  <?php
    //echo "DATE_FROM $DATE_FROM DATE_TO $DATE_TO DOCUMENT_TYPE $DOCUMENT_TYPE<br>";
    $rsDt = oci_parse($conn,$sqlDt);
    //echo "<tr><td>$sqlDt</td></tr>";
    oci_execute ($rsDt); $totLp=0;
    $folderDt="D:/XAMPP/htdocs/webapps/Doc_Folder"; $REC_NO=0;
    while ($rowRsDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
      $totLp++; $REC_NO++;
      $TRX_DT=""; if(!empty($rowRsDt['TRX_DT'])){$TRX_DT=$rowRsDt['TRX_DT'];}
      $TRX_YY=""; if(!empty($rowRsDt['TRX_YY'])){$TRX_YY=$rowRsDt['TRX_YY'];}
      $TRX_FOLDER=""; if(!empty($rowRsDt['TRX_FOLDER'])){$TRX_FOLDER=$rowRsDt['TRX_FOLDER'];}
      $TRX_NO=""; if(!empty($rowRsDt['TRX_NO'])){$TRX_NO=$rowRsDt['TRX_NO'];}
      $CRT_BY=""; if(!empty($rowRsDt['CRT_BY'])){$CRT_BY=$rowRsDt['CRT_BY'];}
      $CRT_DESC=""; if(!empty($rowRsDt['CRT_DESC'])){$CRT_DESC=$rowRsDt['CRT_DESC'];}
      $TH_DESC=""; if(!empty($rowRsDt['TH_DESC'])){$TH_DESC=$rowRsDt['TH_DESC'];}

      $folderCk=$folderDt."/".$TRX_FOLDER."/".$TRX_YY."/".$TRX_NO.".pdf";
      if($totLp===1){echo "<tr>";}
  ?>
      <td  >
        <?php
          echo "<b><font color='green'>".$REC_NO.").</font></b> ".$TRX_DT."<br><font color='blue'>$TRX_NO<br>$CRT_BY $CRT_DESC</font>";
          if (!file_exists($folderCk)) {
            echo "<br><font color='red'>not exist</font>";
          } else {
            echo "<br>exists";
          }
          //echo "<br>$folderCk";
        ?>
      </td>
  <?php
      if($totLp===7){
        echo "</tr>";
        $totLp=0;
      }
    }
  ?>
  </table>
  </div>
<?php
} else if ($FILE_STATUS==="All Exist File"){
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
<table style="width:100%" border="1">
<?php
  //echo "DATE_FROM $DATE_FROM DATE_TO $DATE_TO DOCUMENT_TYPE $DOCUMENT_TYPE<br>";
  $rsDt = oci_parse($conn,$sqlDt);
  oci_execute ($rsDt); $totLp=0;
  $folderDt="D:/XAMPP/htdocs/webapps/Doc_Folder"; $REC_NO=0;
  while ($rowRsDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
    $TRX_DT=""; if(!empty($rowRsDt['TRX_DT'])){$TRX_DT=$rowRsDt['TRX_DT'];}
    $TRX_YY=""; if(!empty($rowRsDt['TRX_YY'])){$TRX_YY=$rowRsDt['TRX_YY'];}
    $TRX_FOLDER=""; if(!empty($rowRsDt['TRX_FOLDER'])){$TRX_FOLDER=$rowRsDt['TRX_FOLDER'];}
    $TRX_NO=""; if(!empty($rowRsDt['TRX_NO'])){$TRX_NO=$rowRsDt['TRX_NO'];}
    $CRT_BY=""; if(!empty($rowRsDt['CRT_BY'])){$CRT_BY=$rowRsDt['CRT_BY'];}
    $folderCk=$folderDt."/".$TRX_FOLDER."/".$TRX_YY."/".$TRX_NO.".pdf";
    $CRT_DESC=""; if(!empty($rowRsDt['CRT_DESC'])){$CRT_DESC=$rowRsDt['CRT_DESC'];}
    $TH_DESC=""; if(!empty($rowRsDt['TH_DESC'])){$TH_DESC=$rowRsDt['TH_DESC'];}
    if (file_exists($folderCk)) {
      $totLp++; $REC_NO++;
      if($totLp===1){echo "<tr>";}
    ?>
      <td >
        <?php echo "<b><font color='green'>".$REC_NO.").</font></b> ".$TRX_DT."<br><font color='blue'>$TRX_NO<br>$CRT_BY $CRT_DESC</font><br>exists"; ?>
      </td>
    <?php
        if($totLp===7){echo "</tr>"; $totLp=0;}
     }
   }
?>
</table>
</div>
<?php
} else if ($FILE_STATUS==="All Not Exist File"){
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
<table style="width:100%" border="1">
<?php
  //echo "DATE_FROM $DATE_FROM DATE_TO $DATE_TO DOCUMENT_TYPE $DOCUMENT_TYPE<br>";
  $rsDt = oci_parse($conn,$sqlDt);
  oci_execute ($rsDt); $totLp=0;
  $folderDt="D:/XAMPP/htdocs/webapps/Doc_Folder"; $REC_NO=0;
  while ($rowRsDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
    $TRX_DT=""; if(!empty($rowRsDt['TRX_DT'])){$TRX_DT=$rowRsDt['TRX_DT'];}
    $TRX_YY=""; if(!empty($rowRsDt['TRX_YY'])){$TRX_YY=$rowRsDt['TRX_YY'];}
    $TRX_FOLDER=""; if(!empty($rowRsDt['TRX_FOLDER'])){$TRX_FOLDER=$rowRsDt['TRX_FOLDER'];}
    $TRX_NO=""; if(!empty($rowRsDt['TRX_NO'])){$TRX_NO=$rowRsDt['TRX_NO'];}
    $CRT_BY=""; if(!empty($rowRsDt['CRT_BY'])){$CRT_BY=$rowRsDt['CRT_BY'];}
    $CRT_DESC=""; if(!empty($rowRsDt['CRT_DESC'])){$CRT_DESC=$rowRsDt['CRT_DESC'];}
    $TH_DESC=""; if(!empty($rowRsDt['TH_DESC'])){$TH_DESC=$rowRsDt['TH_DESC'];}

    $folderCk=$folderDt."/".$TRX_FOLDER."/".$TRX_YY."/".$TRX_NO.".pdf";
    if (!file_exists($folderCk)) {
      $totLp++; $REC_NO++;
      if($totLp===1){echo "<tr>";}
    ?>
      <td>
        <?php
          echo "<b><font color='green'>".$REC_NO.").</font></b> ".$TRX_DT."<br><font color='blue'>$TRX_NO<br>$CRT_BY $CRT_DESC</font><br><font color='red'>not exist</font>";
        ?>
      </td>
    <?php
        if($totLp===7){echo "</tr>"; $totLp=0;}
     }
   }
?>
</table>
</div>
<?php
}
?>
</body>
</html>
