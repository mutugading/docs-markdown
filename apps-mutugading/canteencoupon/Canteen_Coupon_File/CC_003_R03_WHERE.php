<?php
	$sqlView = 	" select rownum RECNO,ECO_SYS_ID,ECO_CREATED_BY,ECO_PE_NIK,ECO_PE_NAMA,ECO_DATE,CREATE_BY_NAME,ECO_MPSM_SCH,ECO_MPSM_SCH_DESC,ECO_RELIEVER_STS  from (".$sqlData.") ";
	if ($SLCT_CC_003_R003_S==="NIK"){
	 	$sqlView = 	$sqlView." where upper(ECO_PE_NIK) like '%'||upper('$SLCT_CC_003_R003_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R003_S==="NAME"){
	 	$sqlView = 	$sqlView." where upper(ECO_PE_NAMA) like '%'||upper('$SLCT_CC_003_R003_I_S')||'%' "	;
	}
	if ($SLCT_CC_003_R003_S==="DEPARTMENT"){
	 	$sqlView = 	$sqlView." where upper(ECO_DEPT_ID) like '%'||upper('$SLCT_CC_003_R003_I_S')||'%' "	;
	}
 	//echo $sqlView;
?>
