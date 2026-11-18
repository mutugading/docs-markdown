<?php
$font = 12;
$style_1 = "style='font-size:".$font."px;text-align:center;'";

$bgPrsHdr = "bgcolor='#E9CDCD'";

function getSqlList($pDateForm,$pDateTo){
    return "
    SELECT
         GH_TXN_CODE GRN_CODE,
         substr(GH_NO,1,4) GRN_YEAR,
         GH_TXN_CODE || '-' || GH_NO GRNNO,
         GH_DT,
         TO_DATE (GH_FLEX_02, 'DDMMRRRR') SJLNDT,
         GH_SUPP_CODE,
         GS_SUPP_NAME,
         GH_REF_TXN_CODE || '-' || GH_REF_NO POno,
         GS_DEPT,
         GS_HOD_MC_APP_UID || ' - ' || HODMCWEB.USER_NAME HODCUSER,
         GS_HOD_MC_APP_DT HOD_MC_APP_DT
    FROM mgtdat.mgt_grn_scan, mgtdat.ot_gr_head, MGTAPPS.MST_USERS HODMCWEB
   WHERE     TO_DATE (GS_HOD_MC_APP_DT, 'dd/mm/yy') BETWEEN TO_DATE (
                                                               '$pDateForm',
                                                               'YYYY/MM/DD')
                                                        AND TO_DATE (
                                                               '$pDateTo',
                                                               'YYYY/MM/DD')
         AND GS_GH_SYS_ID = GH_SYS_ID
         AND not exists (
                        select * from MGTAPPS.GRN_MERGE_DOCS_DATA
                        where GMDD_MERGE_TYPE = 'GRN-SJGRN'
                        and GMDD_GRN_NO = GH_TXN_CODE || '-' || GH_NO
                        )
         AND NVL (GS_HOD_MC_APP_UID, 'NA') = HODMCWEB.USER_ID(+)
  ORDER BY GRNNO, GH_DT, TO_DATE (GS_HOD_ACCEPT_DT, 'dd/mm/yy')
  ";

}

?>
