<?php
$font = 10;
$style_1 = "style='font-size:".$font."px;text-align:center;'";

$bgPrsHdr = "bgcolor='#E9CDCD'";

function getSqlList($pDateForm,$pDateTo){
  return "
  SELECT  GMDD_CREATED_BY MERGE_USER_ID
        , USER_NAME MERGE_USER_NAME
        , GMDD_CREATED_TIMESTAMP MERGE_DT
        , GMDD_GRN_NO GRNNO
        , GH_DT
        , TO_DATE (GH_FLEX_02, 'DDMMRRRR') SJLNDT
        , GMDD_MERGE_TYPE,GH_SUPP_CODE ,GS_SUPP_NAME,GS_DEPT
   FROM MGTAPPS.GRN_MERGE_DOCS_DATA a
        ,MGTAPPS.MST_USERS b
        ,mgtdat.ot_gr_head c
        ,mgtdat.mgt_grn_scan d
  WHERE     GMDD_MERGE_TYPE = 'GRN-SJGRN'
        AND USER_ID = GMDD_CREATED_BY
        AND GS_GH_SYS_ID = GH_SYS_ID
        and GMDD_GRN_NO = GH_TXN_CODE || '-' || GH_NO
        AND trunc(GMDD_CREATED_TIMESTAMP) BETWEEN TO_DATE (
                                                                    '$pDateForm',
                                                                    'YYYY/MM/DD')
                                                             AND TO_DATE (
                                                                    '$pDateTo',
                                                                    'YYYY/MM/DD')
  ";

}

?>
