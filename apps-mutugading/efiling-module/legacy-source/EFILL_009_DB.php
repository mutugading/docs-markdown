<?php
  include("../conOraOci.php");
  $USER_NAME=$_POST['USER_NAME']; $FORM_NAME=$_POST['FORM_NAME']; $STS_PRS=$_POST['STS_PRS'];
  if ($STS_PRS==="ADD_MAIL"){
    $MAIL=$_POST['MAIL']; $CUST_CODE=$_POST['CUST_CODE'];

    $insDt =
              "
              insert into mgtapps.mst_email_cust
              (
                MEC_SYS_ID, MEC_CREATED_BY, MEC_CREATED_TIMESTAMP
                , MEC_CUST_CODE, MEC_MAIL, MEC_ACTIVE
              ) values (
                '123', '$USER_NAME', sysdate
                , '$CUST_CODE', '$MAIL', 'Y'
              )
              ";

    $rsInsLg = oci_parse($conn,$insDt);
    oci_execute($rsInsLg, OCI_DEFAULT);
    oci_commit($conn);

    echo json_encode(array("Status"=>"Process Adding Mail Complete"));

  } else if ($STS_PRS==="RMV_MAIL"){
    $MEC_SYS_ID=$_POST['MEC_SYS_ID'];

    $insDt = " delete from mgtapps.mst_email_cust where MEC_SYS_ID = '$MEC_SYS_ID' ";

    $rsInsLg = oci_parse($conn,$insDt);
    oci_execute($rsInsLg, OCI_DEFAULT);
    oci_commit($conn);

    echo json_encode(array("Status"=>"Process Deleting Mail Complete "));

  } else if ($STS_PRS==="ACTV_MAIL"){
    $MEC_SYS_ID=$_POST['MEC_SYS_ID']; $ACTIVE_VAL=$_POST['ACTIVE_VAL'];

    if ($ACTIVE_VAL==="Y"){ $ACTIVE_UPDT="N";}
    else if ($ACTIVE_VAL==="N"){ $ACTIVE_UPDT="Y";}
    $insDt =
              "
              update mgtapps.mst_email_cust
                set MEC_ACTIVE = '$ACTIVE_UPDT'
                    ,MEC_MODIFIED_BY = '$USER_NAME'
                    , MEC_MODIFIED_TIMESTAMP = sysdate
              where MEC_SYS_ID = $MEC_SYS_ID
              ";

    $rsInsLg = oci_parse($conn,$insDt);
    oci_execute($rsInsLg, OCI_DEFAULT);
    oci_commit($conn);

    echo json_encode(array("Status"=>" Process Updating data mComplete "));
  }
?>
