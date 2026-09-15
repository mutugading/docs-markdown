<?php
  function delViewTmp(
    $conn
    ,$SQVT_CREATED_BY
  ){

    $delSql = "delete from SLS_QUOTATION_VIEW_TMP where SQVT_CREATED_BY = '$SQVT_CREATED_BY'";

    $rsDelLg = oci_parse($conn,$delSql);
    oci_execute($rsDelLg, OCI_DEFAULT);
    if (!$rsDelLg) {
        $e = oci_error($rsDelLg);  // For oci_execute errors pass the statement handle
        print htmlentities($e['message']);
        print "\n<pre>\n";
        print htmlentities($e['sqltext']);
        printf("\n%".($e['offset']+1)."s", "^");
        print  "\n</pre>\n";
    }
    oci_commit($conn);

  }

  function insViewTmp(
      $conn
      ,$SQVT_CYL_LEFT_NO
      ,$SQVT_CREATED_BY
      ,$SQVT_CHIP_RATE
      ,$SQVT_MB_COST
      ,$SQVT_CONVERTION_COST
      ,$SQVT_DOM_COST_INCLD_TRNSPRT
    ){

      $sqlCk = "select count(-1) GET_DATA from SLS_QUOTATION_VIEW_TMP
                where SQVT_CYL_LEFT_NO = $SQVT_CYL_LEFT_NO and SQVT_CREATED_BY = '$SQVT_CREATED_BY'";
      $ckDt = getData($conn,$sqlCk);

      if ($ckDt==="0"){
        $sqlIns =
          "
          insert into SLS_QUOTATION_VIEW_TMP(
            SQVT_CYL_LEFT_NO,SQVT_CREATED_BY,SQVT_CREATED_TIMESTAMP,SQVT_CHIP_RATE
            ,SQVT_MB_COST,SQVT_CONVERTION_COST,SQVT_DOM_COST_INCLD_TRNSPRT
            )
          values (
              $SQVT_CYL_LEFT_NO,'$SQVT_CREATED_BY',sysdate
              ,nvl('$SQVT_CHIP_RATE',0),nvl('$SQVT_MB_COST',0),nvl('$SQVT_CONVERTION_COST',0),nvl('$SQVT_DOM_COST_INCLD_TRNSPRT',0)
            )
            ";

          //echo "$sqlIns</br>";

          $rsInsLg = oci_parse($conn,$sqlIns);
          oci_execute($rsInsLg, OCI_DEFAULT);
          if (!$rsInsLg) {
              $e = oci_error($rsInsLg);  // For oci_execute errors pass the statement handle
              print htmlentities($e['message']);
              print "\n<pre>\n";
              print htmlentities($e['sqltext']);
              printf("\n%".($e['offset']+1)."s", "^");
              print  "\n</pre>\n";
          }
          oci_commit($conn);
      }
  }
?>
