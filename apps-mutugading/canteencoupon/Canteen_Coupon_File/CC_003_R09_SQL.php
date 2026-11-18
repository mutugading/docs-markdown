<?php
    $sqlData = "select  ECES_SYS_ID, to_char(ECES_COUPON_DATE,'YYYY-MM-DD') ECES_COUPON_DATE, ECES_CREATED_BY
                        , to_char(ECES_CREATED_TIMESTAMP,'YYYY-MM-DD HH24:MI:SS') ECES_CREATED_TIMESTAMP
                        , ECES_PE_NIK,nvl(ECES_REMARK_PROCESS,'NULL')  ECES_REMARK_PROCESS
                        ,nvl(ECES_REMARK_HRM,'NULL') ECES_REMARK_HRM
                        , ECES_MODIFIED_BY ,USER_NAME USER_MODIFIED_BY
                        , to_char(ECES_MODIFIED_TIMESTAMP,'YYYY-MM-DD HH24:MI:SS') ECES_MODIFIED_TIMESTAMP
                from    MGTAPPS.EMP_COUPON_ERR_SCAN e
                        ,MST_USERS u
                WHERE trunc(ECES_COUPON_DATE) between trunc(to_date('$P_DATE_FROM_S','YYYY-MM-DD')) and trunc(to_date('$P_DATE_TO_S','YYYY-MM-DD'))
                and e.ECES_MODIFIED_BY = USER_ID(+)
                order by ECES_COUPON_DATE,ECES_CREATED_TIMESTAMP"
   ;



//echo $sqlData;
?>
