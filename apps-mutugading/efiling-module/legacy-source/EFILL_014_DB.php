<?php
  include("../conOraOci.php");
  $USER_NAME=$_POST['USER_NAME']; $FORM_NAME=$_POST['FORM_NAME']; $STS_PRS=$_POST['STS_PRS'];

  if ($STS_PRS==="DELETE_FILE"){

    $P_REMARK=""; if(isset($_POST['P_REMARK'])){$P_REMARK=$_POST['P_REMARK'];}
    $P_PRINT_LINK=""; if(isset($_POST['P_PRINT_LINK'])){$P_PRINT_LINK=$_POST['P_PRINT_LINK'];}
    $P_USER_NAME_DTL=""; if(isset($_POST['P_USER_NAME_DTL'])){$P_USER_NAME_DTL=$_POST['P_USER_NAME_DTL'];}
    $linkDel="D:/XAMPP/htdocs/webapps/$P_PRINT_LINK";
		unlink($linkDel);//D:/XAMPP/htdocs/webapps/
    echo json_encode(array("Status"=>"Success Update $linkDel"));

    $sqlIns = //"test $STS_PRS $P_REMARK $P_PRINT_LINK $P_USER_NAME_DTL"
        "	insert into mgtapps.EFILL_LOG_ACCESS(
          ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
          )
          values (
          '123','$USER_NAME',sysdate,'View Document(Delete Document) -> "
                ."$P_PRINT_LINK --> Remarks : $P_REMARK','$P_USER_NAME_DTL'
          )"
          ;

      echo json_encode(array("Status"=>$sqlIns));
      $rsInsLg = oci_parse($conn,$sqlIns);
      oci_execute($rsInsLg, OCI_DEFAULT);
      if ($rsInsLg) {
          oci_commit($conn);
          echo json_encode(array("Status"=>"Success Update $sqlIns"));
      }

  } ?>
