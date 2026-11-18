<?php
	$sqlView = 	" select rownum RECNO,ECD_DATE,ECD_PE_NIK
											 , ECD_PE_NAMA, ECD_PE_DEPT_ID,ECD_ACTUAL_BREAKTIME
											 , SHIFT_BREAK_DESC,STS_OVERTIME,ecd_created_by
											 , SCHEDULE_BREAK,ON_SCHEDULE_BREAK,ECD_REMARKS,NOSHIFT
							  from (".$sqlData.") ";
	if ($SLCT_CC_003_R002_S==="ACTUAL SHIFT"){
		$sqlView = 	$sqlView." where upper(SHIFT_BREAK_DESC) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="OVERTIME"){
		$sqlView = 	$sqlView." where upper(STS_OVERTIME) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="NIK"){
		$sqlView = 	$sqlView." where upper(ECD_PE_NIK) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="NAME"){
		$sqlView = 	$sqlView." where upper(ECD_PE_NAMA) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="DEPARTMENT"){
		$sqlView = 	$sqlView." where upper(ECD_PE_DEPT_ID) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="PROCESS BY"){
		$sqlView = 	$sqlView." where upper(ECD_CREATED_BY) like '%'||upper('$SLCT_CC_003_R002_I_S')||'%' "	;
	}

	if ($SLCT_CC_003_R002_S==="PROCESS BY OTHERS"){
		$sqlView = 	$sqlView." where upper(ECD_CREATED_BY) not like 'KANTIN' "	;
	}

	if ($SLCT_CC_003_R002_S==="DIFFERENT WITH SCHEDULE"){
		$sqlView = 	$sqlView." where ON_SCHEDULE_BREAK = 'N' "	;
	}
?>
