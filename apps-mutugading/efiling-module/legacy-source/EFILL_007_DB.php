<?php
$rfrsh = ""; $ITEM_FOCUS_S = "";
if ($pFilePath_S!==""){
  $viewFile = "<script>window.open('$pFilePath_S');</script>";
  $pFilePath_S = "";
  echo "$viewFile";
  $STSPRS="";
  $rfrsh = "Y";
}

if ($rfrsh === "Y"){
  //refresh
  echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME".".php' >";
  echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
  echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value=$P_MENU_ID>";
  echo "<input type='hidden' name='P_PAGE_NO' id='P_PAGE_NO' value=$P_PAGE_NO>";
  //echo "<input type='hidden' id='ITEM_FOCUS_S' name='ITEM_FOCUS_S' value='$ITEM_FOCUS_S'>";
  echo "<input type='hidden' name='STSPRS' id='STSPRS' value='$STSPRS'>";
  echo "<input type='hidden' name='TOT_ROWS_S' id='TOT_ROWS_S' value='$TOT_ROWS_S'>";

  echo "<input type='hidden' name='DATE_FROM_S' id='DATE_FROM_S' value='$DATE_FROM_S'>";
  echo "<input type='hidden' name='DATE_TO_S' id='DATE_TO_S' value='$DATE_TO_S'>";
  echo "<input type='hidden' name='PAYMENT_NO_S' id='PAYMENT_NO_S' value='$PAYMENT_NO_S'>";
  echo "<input type='hidden' name='FILTER_SCLT_S' id='FILTER_SCLT_S' value='$FILTER_SCLT_S'>";
  echo "<input type='hidden' name='FILTER_SCLT_VAL_S' id='FILTER_SCLT_VAL_S' value='$FILTER_SCLT_VAL_S'>";
  echo "<input type='hidden' name='FILTER_AREA_S' id='FILTER_AREA_S' value='$FILTER_AREA_S'>";
  echo "<input type='hidden' name='FILTER_SCLT_DATE_S' id='FILTER_SCLT_DATE_S' value='$FILTER_SCLT_DATE_S'>";

  echo "</form>";
  echo "<script type='text/javascript'>";
  //die();
  echo "document.getElementById('$FORM_NAME').submit();";
  echo "</script>";
}
?>
