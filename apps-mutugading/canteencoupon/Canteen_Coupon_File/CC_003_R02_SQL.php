<?php
     $sqlData =  "SELECT to_char(ecd_date,'YYYY-MM-DD') ecd_date,ecd_pe_nik, nvl(ecd_pe_nama,' ') ecd_pe_nama, nvl(ecd_pe_dept_id,' ')ecd_pe_dept_id, "
                 ."      to_char(ecd_actual_breaktime,'YYYY-MM-DD HH24:MI') ECD_ACTUAL_BREAKTIME, "
                 ."      pkgcanteencoupon.fgetshiftbreak
                         (   TO_CHAR (SYSDATE, 'YYYYMMDD')
                          || TO_CHAR (ecd_actual_breaktime, 'HH24MISS')
                         ) SHIFT_BREAK_DESC, "
                 ."     CASE
                           WHEN ecd_eco_sys_id IS NULL
                              THEN 'N'
                           ELSE 'Y'
                        END sts_overtime,ECD_CREATED_BY,ECD_CREATED_TIMESTAMP,NVL(ECD_REMARKS,' ') ECD_REMARKS
                        ,MGTAPPS.pkgCanteenCoupon.fGet_SchlBreak(ECD_PE_NIK,ECD_DATE) SCHEDULE_BREAK
                        ,nvl(MGTAPPS.pkgCanteenCoupon.fGet_onSchlBreak(ECD_PE_NIK,ECD_DATE,ecd_actual_breaktime),'N') ON_SCHEDULE_BREAK
                        ,MGTAPPS.pkgCanteenCoupon.fGet_NOSHIFT(ECD_PE_NIK,ECD_DATE) NOSHIFT
                        "
                 ."FROM emp_coupon_data "
                 ."WHERE TRUNC (ecd_date) between TRUNC (TO_DATE ('$P_DATE_FROM_S', 'YYYY-MM-DD')) and TRUNC (TO_DATE ('$P_DATE_TO_S', 'YYYY-MM-DD')) ";

?>
