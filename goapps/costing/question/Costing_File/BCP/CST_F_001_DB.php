<?php
  $rfrsh = "";
  if ($STSPRS==="SEARCH_DATA"){
    echo "test<br> $SQD_CYL_LEFT_NO_DT<br>";
    $Sql = "begin
                  :r :=  MGTAPPS.pkg_search_prd_used_for.pProcess('$USER_NAME','$SQD_CYL_LEFT_NO_DT');
            end;";
    $stid = oci_parse($conn, $Sql);
    oci_bind_by_name($stid, ':r', $r, 300);
    oci_execute($stid);

    //echo "$Sql<br>$r";die();

    if (substr($r,0,2)==="OK"){
      $rfrsh = "Y";
      $STSPRS = "VIEW_DATA";
    }else{
      echo "<span class='blink'><p align='center'><font face='Comic sans MS' size ='5' color = 'red'>$r</font></p></span>";
    }
  }

  if ($rfrsh === "Y"){
    //refresh
    echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME".".php' >";
    echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
    echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
    echo "<input type='hidden' name='STSPRS' id='STSPRS' value='$STSPRS'>";
    echo "</form>";
    echo "<script type='text/javascript'>";
    //die();
    echo "document.getElementById('$FORM_NAME').submit();";
    echo "</script>";
  }
?>
