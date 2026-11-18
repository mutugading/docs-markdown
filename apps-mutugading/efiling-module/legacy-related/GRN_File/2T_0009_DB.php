<?php
$rfrsh = ""; $ITEM_FOCUS_S = "";

if ($STSPRS=== "VIEW_FILE"){
  echo $FILE_DOC_VIEW;
  $page = $FILE_DOC_VIEW;
  echo "$page";
  echo "<script>window.open('$page');</script>";
  $STSPRS="";
  $rfrsh = "Y";
} else if ($STSPRS=== "MERGE_FILE"){

  $folderDest = $fileDest."GRN_MERGE/";
  copy($GRN_FILE_MERGE,$folderDest.$GRN_NO_MERGE.".pdf");//copy GRN
  echo $SJGRN_FILE_MERGE."<br>";
  echo $folderDest."SJGRN-".$GRN_NO_MERGE."<br>";
  copy($SJGRN_FILE_MERGE,$folderDest."SJGRN-".$GRN_NO_MERGE.".pdf");//copy SJGRN

  $command = "D:\PDFtk_Server\bin\pdftk.exe";
	$FileMerge = "D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\".$GRN_NO_MERGE."_MERGE.pdf";

  $command = "$command D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\".$GRN_NO_MERGE.".pdf";//GRN
  $command = "$command D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\SJGRN-".$GRN_NO_MERGE.".pdf";//SJGRN

  $command = "$command output $FileMerge";//SJGRN

  echo "$command<br>";
  exec($command);

  $page = $fileWeb."GRN_MERGE/".$GRN_NO_MERGE."_MERGE.pdf";
  echo "$page";
  echo "<script>window.open('$page');</script>";
  $STSPRS="";
  $rfrsh = "Y";
}else if ($STSPRS=== "MERGE_ALL_FILE"){
  echo "GRN_TO_MERGE_S $GRN_TO_MERGE_S<br>";
  $GRN_TO_MERGE_TMP = $GRN_TO_MERGE_S;
  $n=0;
  while ($GRN_TO_MERGE_TMP !== "") {
    $folderDest = $fileDest."GRN_MERGE/";
    $GRN_TO_MERGE_PRS = "";
    $STR_POS = strpos($GRN_TO_MERGE_TMP,",");
    if (!empty($STR_POS)){
      $GRN_TO_MERGE_PRS = substr($GRN_TO_MERGE_TMP,0,$STR_POS);
      $GRN_TO_MERGE_TMP = substr($GRN_TO_MERGE_TMP,$STR_POS+1);
    }else{
      $GRN_TO_MERGE_PRS = $GRN_TO_MERGE_TMP;
      $GRN_TO_MERGE_TMP = "";
    }
    //echo "GRN_TO_MERGE_TMP $GRN_TO_MERGE_TMP<br>";

    //Process Merge
    $GRN_TO_MERGE_PRS=str_replace("'","",$GRN_TO_MERGE_PRS);
    echo "GRN_TO_MERGE_PRS $GRN_TO_MERGE_PRS<br>";
    $STR_POS_CODE =  strpos($GRN_TO_MERGE_PRS,"-");
    echo "STR_POS_PRS $STR_POS_CODE<br>";
    $CODE_GRN = substr($GRN_TO_MERGE_PRS,0,$STR_POS_CODE);
    $YEAR_GRN = substr($GRN_TO_MERGE_PRS,$STR_POS_CODE+1,4);
    echo "CODE_GRN $CODE_GRN : YEAR_GRN $YEAR_GRN<br>";
    $GRN_SRC=$fileDest.$CODE_GRN."/".$YEAR_GRN."/".$GRN_TO_MERGE_PRS.".pdf";
    echo "GRN_SRC $GRN_SRC<br>";
    //echo "folderDest $folderDest : folderScanDoc $folderScanDoc : file $file : FILE_FOLDER_S $FILE_FOLDER_S";die();
    $folderDest=$folderDest.$YEAR_GRN;
    if (!is_dir($folderDest)){
      mkdir($folderDest, 0777, true);
      //echo "<script>alert('directory $folderDest created !');</script>";
    }
    $GRN_DST=$folderDest."/".$GRN_TO_MERGE_PRS.".pdf";
    echo "GRN_DST $GRN_DST<br>";
    copy($GRN_SRC,$GRN_DST);//copy GRN
    $SJGRN_SRC=$fileDest."SJGRN"."/".$YEAR_GRN."/SJGRN-".$GRN_TO_MERGE_PRS.".pdf";
    $SJGRN_DST=$folderDest."/SJGRN-".$GRN_TO_MERGE_PRS.".pdf";
    echo "SJGRN_SRC $SJGRN_SRC <br>";
    echo "SJGRN_DST $SJGRN_DST <br>";
    copy($SJGRN_SRC,$SJGRN_DST);//copy SJGRN
    //execution
    $command = "D:\PDFtk_Server\bin\pdftk.exe";
  	$FileMerge = "D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\".$YEAR_GRN."\\".$GRN_TO_MERGE_PRS."_MERGE.pdf";
    $FileMerge_Src = "D:/XAMPP/htdocs/webapps/Doc_Folder/GRN_MERGE/".$YEAR_GRN."/".$GRN_TO_MERGE_PRS."_MERGE.pdf";

    $command = "$command D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\".$YEAR_GRN."\\".$GRN_TO_MERGE_PRS.".pdf";//GRN
    $command = "$command D:\\XAMPP\htdocs\webapps\Doc_Folder\GRN_MERGE\\".$YEAR_GRN."\\SJGRN-".$GRN_TO_MERGE_PRS.".pdf";//SJGRN

    $command = "$command output $FileMerge";//SJGRN

    echo "$command<br>";
    exec($command);
    //execution
    //copy into e-filling folder
    $sqlInsLg =
    "
      insert into MGTAPPS.GRN_MERGE_DOCS_DATA(
          GMDD_SYS_ID, GMDD_CREATED_BY, GMDD_CREATED_TIMESTAMP, GMDD_GRN_NO, GMDD_MERGE_TYPE
      ) values (
          '123','$USER_NAME',sysdate,'$GRN_TO_MERGE_PRS','GRN-SJGRN'
      )
    ";

    $rsInsLg = oci_parse($conn,$sqlInsLg);
    oci_execute($rsInsLg, OCI_DEFAULT);
    if (!$rsInsLg) {
        //echo $sqlInsrt;
        $e = oci_error($rsInsLg);  // For oci_execute errors pass the statement handle
        print htmlentities($e['message']);
        print "\n<pre>\n";
        print htmlentities($e['sqltext']);
        printf("\n%".($e['offset']+1)."s", "^");
        print  "\n</pre>\n";
        //echo $sqlInsrt;
    }
    oci_commit($conn);

    echo "FileMerge_Src $FileMerge_Src<br>";
    echo "GRN_SRC $GRN_SRC<br>";
    //copy into e-filling folder
    copy($FileMerge_Src,$GRN_SRC);//copy SJGRN
    unlink($FileMerge_Src);
    //Process Merge
  }
  $STSPRS=""; $GRN_TO_MERGE_S = "";
  $rfrsh = "Y";
  //die();

}

if ($rfrsh === "Y"){
  //refresh
  echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME".".php' >";
  echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
  echo "<input type='hidden' id='ITEM_FOCUS_S' name='ITEM_FOCUS_S' value='$ITEM_FOCUS_S'>";
  echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
  echo "<input type='hidden' name='STSPRS' id='STSPRS' value='$STSPRS'>";
  echo "<input type='hidden' name='ITEM_FOCUS_S' id='ITEM_FOCUS_S' value='$ITEM_FOCUS_S'>";
  echo "<input type='hidden' name='P_PAGE_NO' id='ITEM_FOCUS_S' value='$P_PAGE_NO'>";
  echo "<input type='hidden' name='TOT_ROWS_S' id='TOT_ROWS_S' value='$TOT_ROWS_S'>";
  echo "<input type='hidden' name='DATE_FROM_S' id='DATE_FROM_S' value='$DATE_FROM_S'>";
  echo "<input type='hidden' name='DATE_TO_S' id='DATE_TO_S' value='$DATE_TO_S'>";
  echo "<input type='hidden' name='GRN_TO_MERGE_S' id='GRN_TO_MERGE_S' value='$GRN_TO_MERGE_S'>";

  echo "</form>";
  echo "<script type='text/javascript'>";
  //die();
  echo "document.getElementById('$FORM_NAME').submit();";
  echo "</script>";
}
?>
