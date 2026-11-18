<?php //if($USER_NAME==="1949"){ echo "GO FILE_STATUS $FILE_STATUS<br>"; }; ?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
<table style="width:100%" border="1">
<?php
  //echo "DATE_FROM $DATE_FROM DATE_TO $DATE_TO DOCUMENT_TYPE $DOCUMENT_TYPE<br>";
  $rsDt = oci_parse($conn,$sqlDt);
  oci_execute ($rsDt); $totLp=0;
  $folderDt="D:/XAMPP/htdocs/webapps/Doc_Folder";
  //if($USER_NAME==="1949"){ echo "sqlDt $sqlDt<br>"; };
  while ($rowRsDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
    $totLp++;
    $REC_NO=""; if(!empty($rowRsDt['REC_NO'])){$REC_NO=$rowRsDt['REC_NO'];}
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
    <td <?php echo getStyle(12,"center"); ?> >
      <?php
        echo "<b><font color='green'>".$REC_NO.").</font></b> ".$TRX_DT."<br><font color='blue'>$TRX_NO<br>$CRT_BY $CRT_DESC</font>";

        $sqlGet= "select 'Y' GET_DATA  from MST_PARAM_DATA where MPD_MPDK_KEY like 'EFIL_CK_SCAN_DOC_UNNORMAL' and MPD_NAME='$TRX_FOLDER' ";
        //echo "sqlGet $sqlGet<br>";
        $IS_UNNORMAL = getData($conn,$sqlGet);

        if ($IS_UNNORMAL==="Y"){
          $folderCk=$folderDt."/".$TRX_FOLDER."/".$TRX_YY."/".$TRX_NO;

          $pattern = $folderCk . '*'; // Mencari semua yang dimulai dengan nama folderCk
          $matchingFolders = glob($pattern);

          if (empty($matchingFolders)) {
              echo "<br><font color='red'>not exist</font>";
          } else {
              echo "<br><b>exists</b><br>";
              print_r($matchingFolders);
          }
        }else{
          if (!file_exists($folderCk)) {
            echo "<br><font color='red'>not exist</font>";
          } else {
            echo "<br>exists";
          }
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
