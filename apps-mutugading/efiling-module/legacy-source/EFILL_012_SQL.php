<?php 
	$sqlViewHdr =
		"select PH_TXN_CODE||'-'||PH_NO PO_NO, PH_DT, PH_SUPP_CODE, SUPP_NAME, PH_FLEX_17
				,PH_TXN_CODE TRX_FOLDER,to_char(PH_DT,'YYYY') TRX_YY,PH_NO TRX_NO
		from mgtdat.ot_po_head, mgtdat.om_supplier
		where ph_dt between to_date('$DATE_FROM_S','YYYY-MM-DD') and to_date('$DATE_TO_S','YYYY-MM-DD')
		and PH_SUPP_CODE = SUPP_CODE
		";

	$sqlViewDtl =
		"select DISTINCT GR_REF_TXN_CODE||'-'||GR_REF_NO PO_NO, BH_TXN_CODE||'-'||BH_NO BILL_NO, BH_SUPP_CODE, SUPP_NAME, bh_supp_ref
				,BH_TXN_CODE,BH_NO,BH_DT
		from mgtdat.ot_bill_head, mgtdat.ot_bill_ref, mgtdat.om_supplier, mgtdat.ot_gr_ref
		where bh_sys_id = br_bh_sys_id
		and BH_SUPP_CODE = SUPP_CODE
		and BR_REF_H_SYS_ID = GR_GH_SYS_ID (+)
		";

?>
