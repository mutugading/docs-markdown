<?php
  $rfrsh = "";
  if ($STSPRS==="GENERATE_QUOTATION"){
    $Sql = "begin
                  :r :=  MGTAPPS.pkg_sales_quotation.fGenerate('$USER_NAME');
            end;";
    //echo $Sql;die();
    $stid = oci_parse($conn, $Sql);
    oci_bind_by_name($stid, ':r', $r, 4000);
    oci_execute($stid);
    if (substr($r,0,2)==="OK"){
      echo "<script>alert('Successfully Generated');</script>";
      echo $r."<br>".substr($r,2);//die();
      //$frmPrs = "webapps/Sales_File/2S1_002.PHP";
      //$PRS = "EDIT_HDR ".substr($r,2);
      $MENU_ID = "2S40000000";
      // $pathScdlr = "https://mgtapps.mutugading.com:4433/webapps/Sales_File/2S1_003";
      // $pathScdlr = $pathScdlr."?P_USER_ID=$USER_NAME&P_MENU_ID=$MENU_ID&STSPRS=$PRS";
      $P_SQH_SYS_ID = substr($r,2);

      $page = "https://mgtapps.mutugading.com:4433/webapps/Sales_File/QUOTATION_FORM.PHP?"
              ."P_MENU_ID=$MENU_ID&P_USER_NAME=$USER_NAME&P_SQH_SYS_ID=$P_SQH_SYS_ID";
      //echo "$page";die();
      echo "<script>window.open('$page');</script>";


      //echo "Process pathScdlr $pathScdlr</br>";die();
      $rfrsh = "Y";
      $STSPRS = "VIEW_DATA";
    }else{
      echo "<span class='blink'><p align='center'><font face='Comic sans MS' size ='5' color = 'red'>$r</font></p></span>";
    }
  }
  if ($STSPRS==="ADD_QUOTATION"){
    $Sql = "begin
                  :r :=  MGTAPPS.pkg_sales_quotation.fInsTmp('$USER_NAME','$SQD_CYL_LEFT_NO_DT');
            end;";
    //echo $Sql;die();
    $stid = oci_parse($conn, $Sql);
    oci_bind_by_name($stid, ':r', $r, 300);
    oci_execute($stid);
    if (substr($r,0,2)==="OK"){
      $rfrsh = "Y";
      //echo "<script>alert('Successfully Inserted');</script>";
      $STSPRS = "VIEW_DATA";
      //echo "$STSPRS<br>";die();
    }else{
      echo "<span class='blink'><p align='center'><font face='Comic sans MS' size ='5' color = 'red'>$r</font></p></span>";
    }
  }
  if ($STSPRS==="REMOVE_QUOTATION"){
    $Sql = "begin
                  :r :=  MGTAPPS.pkg_sales_quotation.fRemoveTmp('$USER_NAME','$SQD_CYL_LEFT_NO_DT');
            end;";
    //echo $Sql;die();
    $stid = oci_parse($conn, $Sql);
    oci_bind_by_name($stid, ':r', $r, 300);
    oci_execute($stid);
    if (substr($r,0,2)==="OK"){
      $rfrsh = "Y";
      //echo "<script>alert('Successfully Removed');</script>";
      $STSPRS = "VIEW_DATA";
      //echo "$STSPRS<br>";die();
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
