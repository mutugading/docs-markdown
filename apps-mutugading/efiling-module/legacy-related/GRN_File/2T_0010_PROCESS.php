<?php
  //echo "$STSPRS ".substr($STSPRS,0,9)."<br>";
  $SQH_SYS_ID_S = "";
  if (substr($STSPRS,0,9)==="EDIT_HDR " ){
    include("2T_0001_PROCESS_HDR_EDIT.php");
  }
?>
