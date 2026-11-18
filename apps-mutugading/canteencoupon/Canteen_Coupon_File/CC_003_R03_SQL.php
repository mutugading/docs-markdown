<?php
$sqlData =  "SELECT   ECO_SYS_ID,ECO_CREATED_BY,ECO_PE_NIK,ECO_PE_NAMA,to_char(ECO_date,'YYYY-MM-DD') ECO_DATE,USER_NAME CREATE_BY_NAME "
            ."        ,ECO_CREATED_TIMESTAMP,ECO_MPSM_SCH,nvl(ECO_MPSM_SCH_DESC,' ')ECO_MPSM_SCH_DESC,nvl(ECO_RELIEVER_STS,'N') ECO_RELIEVER_STS,ECO_DEPT_ID "
            ."FROM emp_coupon_overtime d,mst_users u WHERE d.ECO_CREATED_BY = u.USER_ID "
            ."and TRUNC (ECO_date) = TRUNC (TO_DATE ('$P_DATE_FROM_S', 'YYYY-MM-DD')) ";
//echo $sqlData;
?>
