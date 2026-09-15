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
      $conn //1
      ,$SQVT_CYL_LEFT_NO //2
      ,$SQVT_CREATED_BY //3
      ,$SQVT_CHIP_RATE //4
      ,$SQVT_MB_COST //5
      ,$SQVT_CONVERTION_COST //6
      ,$SQVT_DOM_COST_INCLD_TRNSPRT //7
      ,$SQVT_DTY_PRD_KG=0 //8
      ,$SQVT_MB_DOZ_PRSN=0 //9
      ,$SQVT_MB_RATE=0 //10
      ,$SQVT_FG_FINAL_PACKING=""
      ,$SQVT_FG_BOBBIN_WEIGHT=0,$SQVT_FG_NO_OF_BOBBINS=0,$SQVT_FG_PACKING_COST=0,$SQVT_CHANGE_OVER_LOSS=0,$SQVT_FG_QUALITY_LOSS=0
      ,$SQVT_POY_MAN_POWER_COST=0,$SQVT_POY_OVER_HEADS_COST=0,$SQVT_DTY_MAN_POWER_COST=0,$SQVT_DTY_OVER_HEADS_COST=0

    ){

      // $sqlCk = "select count(-1) GET_DATA from SLS_QUOTATION_VIEW_TMP
      //           where SQVT_CYL_LEFT_NO = $SQVT_CYL_LEFT_NO and SQVT_CREATED_BY = '$SQVT_CREATED_BY'";
      // $ckDt = getData($conn,$sqlCk);

      //if ($ckDt==="0"){
        // $rsDelLg = null;
        // $sqlDel = "delete from SLS_QUOTATION_VIEW_TMP where SQVT_CYL_LEFT_NO = $SQVT_CYL_LEFT_NO and SQVT_CREATED_BY = '$SQVT_CREATED_BY'";
        // $rsDelLg = oci_parse($conn,$sqlDel);
        // oci_execute($rsDelLg, OCI_DEFAULT);
        // if (!$rsDelLg) {
        //     $e = oci_error($rsDelLg);  // For oci_execute errors pass the statement handle
        //     print htmlentities($e['message']);
        //     print "\n<pre>\n";
        //     print htmlentities($e['sqltext']);
        //     printf("\n%".($e['offset']+1)."s", "^");
        //     print  "\n</pre>\n";
        // }
        // oci_commit($conn);

        //       $SQVT_CYL_LEFT_NO,'$SQVT_CREATED_BY',sysdate
        //       ,,,
        //       ,,,
        //       ,,,
        //       ,,,
        //       ,,,
        //       ,,

        $SQVT_MB_DOZ_PRSN = str_replace("</br>", "", $SQVT_MB_DOZ_PRSN);
        $SQVT_MB_DOZ_PRSN = str_replace("</n>", "", $SQVT_MB_DOZ_PRSN);

        $SQVT_MB_RATE = str_replace("</br>", "", $SQVT_MB_RATE);
        $SQVT_MB_RATE = str_replace("</n>", "", $SQVT_MB_RATE);

        $sqlLoad = "begin
                      :r :=
                        MGTAPPS.pkg_sales_quotation.fIns_view_tmp
                                (
                                    $SQVT_CYL_LEFT_NO
                                    ,'$SQVT_CREATED_BY'
                                    ,'$SQVT_CHIP_RATE'
                                    ,'$SQVT_MB_COST'
                                    ,'$SQVT_CONVERTION_COST'
                                    ,'$SQVT_DOM_COST_INCLD_TRNSPRT'
                                    ,'$SQVT_DTY_PRD_KG'
                                    ,'$SQVT_MB_DOZ_PRSN'
                                    ,'$SQVT_MB_RATE'
                                    ,'$SQVT_FG_FINAL_PACKING'
                                    ,'$SQVT_FG_BOBBIN_WEIGHT'
                                    ,'$SQVT_FG_NO_OF_BOBBINS'
                                    ,'$SQVT_FG_PACKING_COST'
                                    ,'$SQVT_CHANGE_OVER_LOSS'
                                    ,'$SQVT_FG_QUALITY_LOSS'
                                    ,'$SQVT_POY_MAN_POWER_COST'
                                    ,'$SQVT_POY_OVER_HEADS_COST'
                                    ,'$SQVT_DTY_MAN_POWER_COST'
                                    ,'$SQVT_DTY_OVER_HEADS_COST'
                                );
                    end;
                    ";

        //die();
        $stid = null;

        $stid = oci_parse($conn, $sqlLoad);
        //oci_bind_by_name($stid, ':p', $p);
        oci_bind_by_name($stid, ':r', $r, 500);

        oci_execute($stid);

        if ($r !== "OK"){
          echo "sqlLoad $sqlLoad </br>";//die();
          echo $r;
          die();
        }

        // $sqlIns =
        //   "
        //   insert into SLS_QUOTATION_VIEW_TMP(
        //     SQVT_CYL_LEFT_NO,SQVT_CREATED_BY,SQVT_CREATED_TIMESTAMP
        //     ,SQVT_CHIP_RATE,SQVT_MB_COST,SQVT_CONVERTION_COST
        //     ,SQVT_DOM_COST_INCLD_TRNSPRT,SQVT_DTY_PRD_KG,SQVT_MB_DOZ_PRSN
        //     ,SQVT_MB_RATE,SQVT_FG_FINAL_PACKING,SQVT_FG_BOBBIN_WEIGHT
        //     ,SQVT_FG_NO_OF_BOBBINS,SQVT_FG_PACKING_COST,SQVT_CHANGE_OVER_LOSS
        //     ,SQVT_FG_QUALITY_LOSS,SQVT_POY_MAN_POWER_COST,SQVT_POY_OVER_HEADS_COST
        //     ,SQVT_DTY_MAN_POWER_COST,SQVT_DTY_OVER_HEADS_COST
        //     )
        //   values (
        //       $SQVT_CYL_LEFT_NO,'$SQVT_CREATED_BY',sysdate
        //       ,nvl('$SQVT_CHIP_RATE','0'),nvl('$SQVT_MB_COST','0'),nvl('$SQVT_CONVERTION_COST','0')
        //       ,nvl('$SQVT_DOM_COST_INCLD_TRNSPRT','0'),nvl('$SQVT_DTY_PRD_KG','0'),nvl('$SQVT_MB_DOZ_PRSN','0')
        //       ,nvl('$SQVT_MB_RATE','0'),'$SQVT_FG_FINAL_PACKING',nvl('$SQVT_FG_BOBBIN_WEIGHT','0')
        //       ,nvl('$SQVT_FG_NO_OF_BOBBINS','0'),nvl('$SQVT_FG_PACKING_COST','0'),nvl('$SQVT_CHANGE_OVER_LOSS','0')
        //       ,nvl('$SQVT_FG_QUALITY_LOSS','0'),nvl('$SQVT_POY_MAN_POWER_COST','0'),nvl('$SQVT_POY_OVER_HEADS_COST','0')
        //       ,nvl('$SQVT_DTY_MAN_POWER_COST','0'),nvl('$SQVT_DTY_OVER_HEADS_COST','0')
        //     )
        //     ";
        //
        //   //if ($USER_NAME==="1949") {
        //     echo "$sqlIns</br>";
        //   //}//die();
          // $rsInsLg = oci_parse($conn,$sqlIns);
          // oci_execute($rsInsLg, OCI_DEFAULT);
          // if (!$rsInsLg) {
          //     $e = oci_error($rsInsLg);  // For oci_execute errors pass the statement handle
          //     print htmlentities($e['message']);
          //     print "\n<pre>\n";
          //     print htmlentities($e['sqltext']);
          //     printf("\n%".($e['offset']+1)."s", "^");
          //     print  "\n</pre>\n";
          //     echo "$sqlIns</br>";
          // }
          // oci_commit($conn);
      //}
  }
?>
