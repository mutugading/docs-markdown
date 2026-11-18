<?php
   $sqlData =  "select  fHari_Indo(to_char(M.ECDS_DATE,'D')) HARI
                       ,ECDS_DATE,to_char(ECDS_DATE,'DD-MM-YYYY') ECDS_DATE_1, ECDS_TOT_SHIFT_1, ECDS_TOT_SHIFT_GS, ECDS_TOT_SHIFT_2, ECDS_TOT_SHIFT_3, ECDS_TOT_OTHERS
                       ,nvl(ECDS_TOT_SHIFT_1,0)+nvl(ECDS_TOT_SHIFT_GS,'0')+nvl(ECDS_TOT_SHIFT_2,'0')+nvl(ECDS_TOT_SHIFT_3,'0')+nvl(ECDS_TOT_OTHERS,'0') GRAND_TOT
               from EMP_COUPON_DATA_SUM m
               WHERE trunc(ECDS_DATE) between trunc(to_date('$P_DATE_FROM_S','YYYY-MM-DD'))
               and trunc(to_date('$P_DATE_TO_S','YYYY-MM-DD'))
               and ECDS_APPROVE_HRM_DATE is not null
               order by M.ECDS_DATE ";

//echo $sqlData;
?>
