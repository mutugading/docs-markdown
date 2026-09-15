-- ==============================================================
-- TRNSP002.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     249 WHEN-BUTTON-PRESSED
--      20 ON-YYYY
--      15 KEY-NEXT-ITEM
--      13 PRE-UPDATE
--      13 POST-QUERY
--      12 PRE-INSERT
--      11 WHEN-CREATE-RECORD
--       9 KEY-HELP
--       6 WHEN-NEW-RECORD-INSTANCE
--       6 WHEN-LIST-CHANGED
--       5 WHEN-NEW-BLOCK-INSTANCE
--       4 ON-CLEAR-DETAILS
--       3 WHEN-VALIDATE-ITEM
--       3 WHEN-TIMER-EXPIRED
--       3 WHEN-RADIO-CHANGED
--       3 WHEN-NEW-ITEM-INSTANCE
--       3 WHEN-NEW-FORM-INSTANCE
--       3 ON-POPULATE-DETAILS
--       3 ON-MESSAGE
--       3 ON-ERROR
--       3 ON-CHECK-DELETE-MASTER
--       3 KEY-EXEQRY
--       1 ON-PRESSED

-- ---------- Statement SQL ----------
DELETE
;

DELETE FROM MGT_TRANSP_DETAIL_COST WHERE MTDC_MTH_SYS_ID = :b1
;

DELETE FROM MGT_TRANSP_DETAIL_DN WHERE MTDD_MTH_SYS_ID = :b1
;

DELETE FROM MGT_TRANSP_HEAD WHERE MTH_SYS_ID = :b1
;

INSERT
;

SELECT 1 FROM MGT_TRANSP_DETAIL_COST M WHERE M.MTDC_MTH_SYS_ID = :b1
;

SELECT 1 FROM MGT_TRANSP_DETAIL_DN M WHERE M.MTDD_MTH_SYS_ID = :b1
;

SELECT 1 FROM MGT_TRANSP_DETAIL_OTHCHG M WHERE M.MTDO_MTH_SYS_ID = :b1
;

SELECT B.MTDD_DN_NO FROM MGT_TRANSP_HEAD A,MGT_TRANSP_DETAIL_DN B WHERE A.MTH_SYS_ID = B.MTDD_MTH_SYS_ID AND NVL(B.MTDD_USED_STATUS,''N'') = ''N'' AND B.MTDD_DN_TXN_CODE IN ( ''LDN'',''JWDN'' ) AND B.MTDD_DN_NO = :b1
;

SELECT COUNT(MTDC_RATE_TYPE) FROM MGT_TRANSP_DETAIL_COST WHERE MTDC_MTH_SYS_ID = :b1
;

SELECT COUNT(MTDC_RATE_TYPE) FROM MGT_TRANSP_DETAIL_COST WHERE MTDC_MTH_SYS_ID = :b1 AND MTDC_RATE_TYPE = ''Q''
;

SELECT COUNT(MTDC_RATE_TYPE) FROM MGT_TRANSP_DETAIL_COST WHERE MTDC_MTH_SYS_ID = :b1 AND MTDC_RATE_TYPE = ''W''
;

SELECT COUNT(MTH_STATUS) FROM MGT_TRANSP_HEAD WHERE MTH_STATUS = ''Approved'' AND MTH_SYS_ID = :b1
;

SELECT COUNT(MTH_TRANSP_NO) FROM MGT_TRANSP_HEAD WHERE MTH_TRANSP_NO = :b1 AND MTH_TXN_CODE = :b2
;

SELECT COUNT(MTR_PRIORITY) FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b1
;

SELECT COUNT(MTR_PRIORITY) FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b1 AND MTR_RATE_TYPE = ''W''
;

SELECT COUNT(TP_JV_NO) FROM MGT_TP_PROVISION WHERE TP_NO = :b1 || ''-'' || :b2
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORT'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_CODE = :b1
;

SELECT MAX(MTH_TRANSP_NO) FROM MGT_TRANSP_HEAD WHERE MTH_TXN_CODE = ''TPDN'' AND TO_CHAR(MTH_DT,''YYYY'') = TO_CHAR(:b1,''YYYY'')
;

SELECT MAX(MTH_TRANSP_NO) FROM MGT_TRANSP_HEAD WHERE MTH_TXN_CODE = ''TPDN'' AND TO_CHAR(MTH_DT,''YYYY'') = TO_CHAR(SYSDATE,''YYYY'')
;

SELECT MGT_TRANSP_DETAIL_COST_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TRANSP_DETAIL_DN_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TRANSP_DETAIL_OTHCHG_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TRANSP_HEAD_SEQ.NEXTVAL FROM DUAL
;

SELECT MTH_NO FROM MGT_TRANSP_HEAD WHERE MTH_SYS_ID = :b1
;

SELECT MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO TRANSP_NO,MTH_DT TRANSP_DT,MTH_TRANSP_NAME TRANSP_NAME,MTH_DESTINATION DESTINATION,MTH_NO_POLICE TRUCK_NO,MTH_DRIVER DRIVER,''Provision'' STATUS FROM MGT_TRANSP_HEAD WHERE MTH_SYS_ID = :b1
;

SELECT MTR_PRIORITY,MTR_RATE_TYPE,:b1,MTR_RATE,MTR_DESTINATION,MTR_TRUCK_TYPE,MTR_RATE FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b2 AND MTR_RATE_TYPE = ''Q''
;

SELECT MTR_PRIORITY,MTR_RATE_TYPE,ABS(:b1),MTR_RATE,MTR_DESTINATION,MTR_TRUCK_TYPE,MTR_RATE FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b2 AND MTR_RATE_TYPE = ''Q''
;

SELECT MTR_PRIORITY,MTR_RATE_TYPE,MTR_MAX_CAP,MTR_RATE,MTR_DESTINATION,MTR_TRUCK_TYPE,MTR_RATE FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b1 AND MTR_RATE_TYPE = ''W''
;

SELECT NVL(MTH_TRUCK_CAP,0) FROM MGT_TRANSP_HEAD WHERE MTH_SYS_ID = :b1
;

SELECT NVL(MTH_TRUCK_CAP,0) FROM MGT_TRANSP_HEAD WHERE MTH_SYS_ID = NVL(:b1,:b2)
;

SELECT SUBSTR(PRD_ORION_ITEM,1,3) ITEM,SUM(PRD_GROSS_WT) GROSS_WT,COUNT(PRD_SUB_UNITS) BOX FROM OT_WMS_PACK_TABLE_ALTHARA WHERE PRD_DOC_TXN_CODE IN ( ''LDN'',''JWDN'' ) AND PRD_DOC_TXN_CODE = :b1 AND PRD_DOCNO_SLNO = :b2 GROUP BY PRD_ORION_ITEM UNION ALL SELECT SUBSTR(INVI_ITEM_CODE,1,3) ITEM,SUM(TO_NUMBER(INVI_FLEX_01)) GROSS_WT,SUM(TO_NUMBER(INVI_FLEX_02)) BOX FROM OT_INVOICE_HEAD,OT_INVOICE_ITEM WHERE INVH_SYS_ID = INVI_INVH_SYS_ID AND INVH_TXN_CODE = ''PDN'' AND INVH_TXN_CODE = :b1 AND INVH_NO = :b2 GROUP BY SUBSTR(INVI_ITEM_CODE,1,3)
;

SELECT TXN_CODE,LD
;

SELECT VSSV_CODE FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORT'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_CODE = :b1
;


-- ---------- Blok PL/SQL yang terbaca di dalam form ----------
-- Catatan: Forms menyimpan sebagian kode sebagai p-code, jadi ekstraksi ini
-- tidak dijamin lengkap. Yang di bawah adalah blok yang teksnya masih utuh.

BEGIN
declare
  alert_button number; 
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  --if lv_errcod in (40102,40401,41050,40735,40501,40212) then
  if lv_errcod = 40102 then
    null;
  else
    message(lv_errtype||'-'||to_char(lv_errcod)||':  '||lv_errtxt);
    raise Form_Trigger_Failure; 
  end if; 
  if form_fatal or form_failure then
     raise form_trigger_failure;
  end if;   
end;

BEGIN
Hdeclare
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  --if lv_errcod in (40102,40401,41050,40735,40501,40212) then
  if lv_errcod = 40102 then
    null;
  else
    message(lv_errtype||'-'||to_char(lv_errcod)||':  '||lv_errtxt);   
  end if; 
end;

BEGIN
pDECLARE
    Lebar        Number(8);
    Tinggi       Number(8);
    Xpos         Number(8);
    Ypos         Number(8);
    v_sid				 number;
    v_bag				 varchar2(15);
    v_bag_no		 varchar2(15);
    v_year			 varchar2(4);	
BEGIN
		/* position canvas */
    Set_Window_Property (Forms_MDI_Window, Window_state, maximize);
    Set_Window_Property ('MAIN_WINDOW', Window_state, maximize);
    
    Lebar := Get_Window_Property(Forms_MDI_Window, Width);
    Tinggi:= Get_Window_Property(Forms_MDI_Window, Height);
    
    Xpos := (Lebar - Get_Window_Property('MAIN_WINDOW',Width))/2;
    Ypos := (Tinggi - Get_Window_Property('MAIN_WINDOW',Height))/2;
        
    Ypos := 5;
    Set_window_Property('MAIN_WINDOW', Position, Xpos, Ypos);
  	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       	
  	  	
  	pkg.new_transp;  	
END; 

BEGIN
-- Begin default relation program section
BEGIN
  Clear_All_Master_Details;
END;

BEGIN
#declare
	expired_timer varchar2(20);
	timer_id 			timer;	
begin	
	expired_timer:=get_application_property(timer_name);	
	if expired_timer = 'NEXT_ITEM' then	
		next_record;
		first_record;
		last_record;
	end if;	
	if expired_timer = 'DN_NO' then	
		next_record;
		first_record;
		last_record;
		go_item('mgt_transp_detail_dn.mtdd_dn_no');
	end if;	
	if expired_timer = 'GO_BLOK' then	
		next_record;
		first_record;
		last_record;
		go_block('mgt_transp_detail_dn');
		go_item('mgt_transp_detail_dn.mtdd_dn_no');
	end if;	
	if expired_timer = 'QTY_ENABLED' then			
		--if get_item_property('mgt_transp_detail_dn.mtdd_dn_qty',enabled) = 'FALSE' then
			---set_item_property('mgt_transp_detail_dn',enabled,property_true);
			--set_item_property('mgt_transp_detail_dn',navigable,property_true);
			set_item_property('mgt_transp_detail_dn.mtdd_dn_qty',insert_allowed,property_true);
			set_item_property('mgt_transp_detail_dn.mtdd_dn_qty',update_allowed,property_true);
		--end if;		
	elsif expired_timer = 'QTY_DISABLED' then			
		--if get_item_property('mgt_transp_detail_dn.mtdd_dn_qty',enabled) = 'TRUE' then
			--set_item_property('mgt_transp_detail_dn',enabled,property_false);
			---set_item_property('mgt_transp_detail_dn',navigable,property_false);
			set_item_property('mgt_transp_detail_dn.mtdd_dn_qty',insert_allowed,property_false);
			set_item_property('mgt_transp_detail_dn.mtdd_dn_qty',update_allowed,property_false);
		--end if;		
	end if;	
	timer_id := find_timer(expired_timer);
	if not id_null(timer_id) THEN
		 delete_timer(timer_id);
	end if;	
end;	

BEGIN
7pkg.populate;
if :parameter.p_sys_id is not null then
	if :system.cursor_block = 'mgt_transp_head' then
		go_block('mgt_transp_head');	
		set_block_property('mgt_transp_head',default_where,' mth_sys_id   = '''||:parameter.p_sys_id||''' ');		
		go_block('mgt_transp_head');	
		execute_query;	
	end if;	
end if;
END;

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_head_seq.nextval into v_id from dual;	
	end;

BEGIN
-- Begin default relation declare section
DECLARE
  recstat     VARCHAR2(20) := :System.record_status;   
  startitm    VARCHAR2(61) := :System.cursor_item;   
  rel_id      Relation;
-- End default relation declare section
-- Begin default relation program section
BEGIN
  IF ( recstat = 'NEW' or recstat = 'INSERT' ) THEN   
    RETURN;
  END IF;
  --
  -- Begin MGT_TRANSP_DETAIL_COST detail program section
  --
  IF ( (:MGT_TRANSP_HEAD.MTH_SYS_ID is not null) ) THEN   
    rel_id := Find_Relation('MGT_TRANSP_HEAD.MGT_TRANSP_COST');   
    Query_Master_Details(rel_id, 'MGT_TRANSP_DETAIL_COST');   
  END IF;
  --
  -- End MGT_TRANSP_DETAIL_COST detail program section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_DN detail program section
  --
  IF ( (:MGT_TRANSP_HEAD.MTH_SYS_ID is not null) ) THEN   
    rel_id := Find_Relation('MGT_TRANSP_HEAD.MGT_TRANSP_DN');   
    Query_Master_Details(rel_id, 'MGT_TRANSP_DETAIL_DN');   
  END IF;
  --
  -- End MGT_TRANSP_DETAIL_DN detail program section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_OTHCHG detail program section
  --
  IF ( (:MGT_TRANSP_HEAD.MTH_SYS_ID is not null) ) THEN   
    rel_id := Find_Relation('MGT_TRANSP_HEAD.MGT_TRANSP_HEAD_MGT_TRANSP_DET');   
    Query_Master_Details(rel_id, 'MGT_TRANSP_DETAIL_OTHCHG');   
  END IF;
  --
  -- End MGT_TRANSP_DETAIL_OTHCHG detail program section
  --
  IF ( :System.cursor_item <> startitm ) THEN     
     Go_Item(startitm);     
     Check_Package_Failure;     
  END IF;
END;

BEGIN
-- Begin default relation declare section
DECLARE
  Dummy_Define CHAR(1);
  --
  -- Begin MGT_TRANSP_DETAIL_COST detail declare section
  --
  CURSOR MGT_TRANSP_DETAIL_COST_cur IS      
    SELECT 1 FROM MGT_TRANSP_DETAIL_COST M     
    WHERE M.MTDC_MTH_SYS_ID = :MGT_TRANSP_HEAD.MTH_SYS_ID;
  --
  -- End MGT_TRANSP_DETAIL_COST detail declare section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_DN detail declare section
  --
  CURSOR MGT_TRANSP_DETAIL_DN_cur IS      
    SELECT 1 FROM MGT_TRANSP_DETAIL_DN M     
    WHERE M.MTDD_MTH_SYS_ID = :MGT_TRANSP_HEAD.MTH_SYS_ID;
  --
  -- End MGT_TRANSP_DETAIL_DN detail declare section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_OTHCHG detail declare section
  --
  CURSOR MGT_TRANSP_DETAIL_OTHCHG_cur IS      
    SELECT 1 FROM MGT_TRANSP_DETAIL_OTHCHG M     
    WHERE M.MTDO_MTH_SYS_ID = :MGT_TRANSP_HEAD.MTH_SYS_ID;
  --
  -- End MGT_TRANSP_DETAIL_OTHCHG detail declare section
  --
-- End default relation declare section
-- Begin default relation program section
BEGIN
  --
  -- Begin MGT_TRANSP_DETAIL_COST detail program section
  --
  OPEN MGT_TRANSP_DETAIL_COST_cur;     
  FETCH MGT_TRANSP_DETAIL_COST_cur INTO Dummy_Define;     
  IF ( MGT_TRANSP_DETAIL_COST_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TRANSP_DETAIL_COST_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TRANSP_DETAIL_COST_cur;
  --
  -- End MGT_TRANSP_DETAIL_COST detail program section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_DN detail program section
  --
  OPEN MGT_TRANSP_DETAIL_DN_cur;     
  FETCH MGT_TRANSP_DETAIL_DN_cur INTO Dummy_Define;     
  IF ( MGT_TRANSP_DETAIL_DN_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TRANSP_DETAIL_DN_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TRANSP_DETAIL_DN_cur;
  --
  -- End MGT_TRANSP_DETAIL_DN detail program section
  --
  --
  -- Begin MGT_TRANSP_DETAIL_OTHCHG detail program section
  --
  OPEN MGT_TRANSP_DETAIL_OTHCHG_cur;     
  FETCH MGT_TRANSP_DETAIL_OTHCHG_cur INTO Dummy_Define;     
  IF ( MGT_TRANSP_DETAIL_OTHCHG_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TRANSP_DETAIL_OTHCHG_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TRANSP_DETAIL_OTHCHG_cur;
  --
  -- End MGT_TRANSP_DETAIL_OTHCHG detail program section
  --
END;

BEGIN
=declare
	v_date_from date;
	v_date_to date;
begin	
	if :mgt_transp_head.mth_dt is not null then
		v_date_from := to_date(sysdate)-5;
		v_date_to	 	:= to_date(sysdate);
		if :mgt_transp_head.mth_dt between v_date_from and v_date_to then  			
			pkg.new_transp_manual;
		else
			message('Transaction Date Tidak bisa di Backdate lebih dari 5 Hari !!');	
			message('Transaction Date Tidak bisa di Backdate lebih dari 5 Hari !!');	
		end if;						
	end if;
end;	

BEGIN
nbegin	
	:mgt_transp_head.mth_upd_uid := :parameter.m_user_id;
	:mgt_transp_head.mth_upd_dt  := sysdate; 
end;	

BEGIN
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
\pkg.save_head;
go_block('mgt_transp_detail_dn');
go_item('mgt_transp_detail_dn.mtdd_dn_no');
END;

BEGIN
declare
	v_check number;
begin	
	if :mgt_transp_head.mth_status = 'Approved' then
		begin
			select count(tp_jv_no)
			into v_check
			from mgt_tp_provision
			where tp_no = :mgt_transp_head.mth_txn_code||'-'||:mgt_transp_head.mth_transp_no;
		end;

BEGIN
if :mgt_transp_head.mth_status is null then
	if :mgt_transp_head.mth_cr_uid != :parameter.m_user_id then
		:mgt_transp_head.mth_status := 'Approved';
		:parameter.status := 'Approved';
		:system.message_level := 25;
		commit;
		:system.message_level := 0;	
	else
		message('User Approve Tidak Boleh Sama dengan USer Create !!');
		message('User Approve Tidak Boleh Sama dengan USer Create !!');
	end if;
end if;	  
END;

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_detail_dn_seq.nextval into v_id from dual;	
	end;

BEGIN
zbegin	
	:mgt_transp_detail_dn.mtdd_upd_dt		:= sysdate;
	:mgt_transp_detail_dn.mtdd_upd_uid	:= :parameter.m_user_id;
end;	

BEGIN
wnull;
if nvl(:parameter.status,'XXX') != 'Approved' then
	pkg.cost_auto;
	pkg.check_cost;
end if;
pkg.populate;	
END;

BEGIN
kif nvl(:parameter.status,'XXX') != 'Approved' then
	pkg.cost_auto;
	pkg.check_cost;
end if;	
pkg.populate;	
END;

BEGIN
nif nvl(:parameter.status,'XXX') != 'Approved' then	
	pkg.cost_auto;
	pkg.check_cost;	
end if;	
pkg.populate;	
END;

BEGIN
Qif :mgt_transp_detail_dn.mtdd_dn_txn_code is not null then
	pkg.enabled;
end if;	
END;

BEGIN
declare	
	timer_id  timer;
begin
	--pkg.populate;
	--pkg.check_scan;
	pkg.check_scan;
	pkg.populate;
	pkg.cost_auto;
	pkg.check_cost;		
	if :mgt_transp_detail_dn.mtdd_dn_no is not null then
		timer_id := create_timer('DN_NO',1,NO_REPEAT);
	end if;	
end;

BEGIN
declare	
	timer_id  timer;
begin
	if :mgt_transp_detail_dn.mtdd_dn_no is not null then
		timer_id := create_timer('NEXT_ITEM',1,NO_REPEAT);
	end if;	
end;

BEGIN
&null;
go_block('mgt_transp_detail_dn');	
clear_block(no_validate);
go_block('mgt_transp_detail_cost');	
clear_block(no_validate);
go_block('mgt_transp_head');	
clear_block(no_validate);
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');
pkg.find;
END;

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_detail_cost_seq.nextval into v_id from dual;	
	end;

BEGIN
begin	
	:mgt_transp_detail_cost.mtdc_upd_dt		:= sysdate;
	:mgt_transp_detail_cost.mtdc_upd_uid	:= :parameter.m_user_id;
end;	

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_detail_othchg_seq.nextval into v_id from dual;	
	end;

	begin
		select mth_txn_code ||'-'|| mth_transp_no transp_no,
		       mth_dt transp_dt,
		       mth_transp_name transp_name,
		       mth_destination destination, 
		       mth_no_police truck_no,
		       mth_driver    driver,
		       'Provision' status		       
		into 	 :mgt_transp_detail_othchg.mtdo_transp_no,
		       :mgt_transp_detail_othchg.mtdo_transp_dt,
		       :mgt_transp_detail_othchg.mtdo_transp_name,
		       :mgt_transp_detail_othchg.mtdo_destination,
		       :mgt_transp_detail_othchg.mtdo_truck_no,
		       :mgt_transp_detail_othchg.mtdo_driver,
		       :mgt_transp_detail_othchg.mtdo_status		       
		from mgt_transp_head
		where mth_sys_id = :mgt_transp_detail_othchg.mtdo_mth_sys_id;
	end;

BEGIN
begin	
	:mgt_transp_detail_othchg.mtdo_upd_dt		:= sysdate;
	:mgt_transp_detail_othchg.mtdo_upd_uid	:= :parameter.m_user_id;
end;	

BEGIN
{begin	
	clear_block(no_validate);
	hide_view('view_can');
	hide_window('view_win');
	go_block('mgt_transp_detail_dn');
end;

BEGIN
declare
	v_check number;
begin
	if :mgt_transp_head.mth_transp_no is not null and :mgt_transp_head.mth_dt is not null and :mgt_transp_head.mth_no is not null then		
		begin
			select count(tp_jv_no)
			into v_check
			from mgt_tp_provision
			where tp_no = :mgt_transp_head.mth_txn_code||'-'||:mgt_transp_head.mth_transp_no;
		end;

BEGIN
clear_form(no_validate);
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');
pkg.find;
END;

BEGIN
begin
	clear_form(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

BEGIN
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
if (to_char(:calendar.curr_dt, 'YYYY')-1)||to_char(:calendar.curr_dt, 'MM') <> to_char(:calendar.user_dt,'YYYYMM') THEN
	:calendar.curr_dt	:= to_date('01'||TO_CHAR(:calendar.curr_dt,'-MON-')||(TO_CHAR(:calendar.curr_dt, 'YYYY')-1),'DD-MON-YYYY');
else
	:calendar.curr_dt	:= to_date(to_char(:calendar.user_dt,'DD')||to_char(:calendar.curr_dt,'-MON-')||(to_char(:calendar.curr_dt,'YYYY')+1),'DD-MON-YYYY');		
end if;
calendar_populate_month_pro(:calendar.curr_dt);
END;

BEGIN
if (to_char(:calendar.curr_dt,'YYYY')+1)||to_char(:calendar.curr_dt,'MM') <> to_char(:calendar.user_dt,'YYYYMM') THEN
	:calendar.curr_dt	:= to_date('01'||to_char(:calendar.curr_dt,'-MON-')||(to_char(:calendar.curr_dt, 'YYYY')+1),'DD-MON-YYYY');
else
	:calendar.curr_dt	:= to_date(to_char(:calendar.user_dt,'DD')||to_char(:calendar.curr_dt,'-MON-')||(TO_CHAR(:calendar.curr_dt,'YYYY')+1),'DD-MON-YYYY');
end if;
calendar_populate_month_pro(:calendar.curr_dt);
END;

BEGIN
begin
  IF :radio_options = 'SCREEN' then
		--:copies := null;
			--set_item_property('blk_main.copies',enabled,property_FALSE);
			go_item('blk_main.CONTINUE');
  end if;
end;

BEGIN
{begin	
	clear_block(no_validate);
	hide_view('MAS_PRINT');
	hide_window('MAS_PRINT');
	go_block('MGT_TRX_STORE_HEAD');
end;

BEGIN
if :calendar.month_it is not null then
	if TO_CHAR(:calendar.curr_dt,'YYYY')||:calendar.month_it <> to_char(:calendar.user_dt,'YYYYMM') THEN
		:calendar.curr_dt	:= to_date('01-'||:calendar.month_it||to_char(:calendar.curr_dt, '-YYYY'),'DD-MM-YYYY');
	else
		:calendar.curr_dt	:= to_date(TO_CHAR(:calendar.user_dt, 'DD-')||:calendar.month_it||to_char(:calendar.curr_dt,'-YYYY'),'DD-MM-YYYY');
	end if;
calendar_populate_month_pro(:calendar.curr_dt);
end if;
END;

BEGIN
khide_window('CALENDAR_WIN');
copy(to_char(:calendar.curr_dt,'DD-MON-YYYY'),name_in('system.cursor_item'));
END;

BEGIN
  --
  -- Initialize Local Variable(s)
  --
  reldef := Get_Relation_Property(rel_id, DEFERRED_COORDINATION);
  oldmsg := :System.Message_Level;
  --
  -- If NOT Deferred, Goto detail and execute the query.
  --
  IF reldef = 'FALSE' THEN
    Go_Block(detail);
    Check_Package_Failure;
    :System.Message_Level := '10';
    Execute_Query;
    :System.Message_Level := oldmsg;
  ELSE
    --
    -- Relation is deferred, mark the detail block as un-coordinated
    --
    Set_Block_Property(detail, COORDINATION_STATUS, NON_COORDINATED);
  END IF;
EXCEPTION
    WHEN Form_Trigger_Failure THEN
      :System.Message_Level := oldmsg;
      RAISE;
END Query_Master_Details;
STANDARD
FORMS4C
FORMS40
SQLFORMS
/NSPC3/CHECK_PACKAGE_FAILURE
"PKG INIT"QUERY_MASTER_DETAILS"REL_ID"ID"DETAIL"OLDMSG"RELDEF""
"QUERY_MASTER_DETAILS"REL_ID"RELATION"DETAIL"VARCHAR2"OLDMSG"2"RELDEF"5"GET_RELATION_PROPERTY"DEFERRED_COORDINATION"System.Message_Level""="FALSE"GO_BLOCK"CHECK_PACKAGE_FAILURE"10"EXECUTE_QUERY"SET_BLOCK_PROPERTY"COORDINATION_STATUS"NON_COORDINATED"FORM_TRIGGER_FAILURE"RAISE""
QUERY_MASTER_DETAILS
FALSE
ND IF;
EXCEPTION
    WHEN Form_Trigger_Failure THEN
      :System.Message_Level := oldmsg;
      RAISE;
END Query_Master_Details;
STANDARD
FORMS4C
FORMS40
SQLFORMS
/NSPC3/CHECK_PACKAGE_FAILURE
"PKG INIT"QUERY_MASTER_DETAILS"REL_ID"ID"DETAIL"OLDMSG"RELDEF""
"QUERY_MASTER_DETAILS"REL_ID"RELATION"DETAIL"VARCHAR2"OLDMSG"2"RELDEF"5"GET_RELATION_PROPERTY"DEFERRED_COORDINATION"System.Message_Level""="FALSE"GO_BLOCK"CHECK_PACKAGE_FAILURE"10"EXECUTE_QUERY"SET_BLOCK_PROPERTY"COORDINATION_STATUS"NON_COORDINATED"FORM_TRIGGER_FAILURE"RAISE""
CLEAR_ALL_MASTER_DETAILS
sPROCEDURE Clear_All_Master_Details IS
  mastblk  VARCHAR2(30);  -- Initial Master Block Causing Coord
  coordop  VARCHAR2(30);  -- Operation Causing the Coord
  trigblk  VARCHAR2(30);  -- Cur Block On-Clear-Details Fires On
  startitm VARCHAR2(61);  -- Item in which cursor started
  frmstat  VARCHAR2(15);  -- Form Status
  curblk   VARCHAR2(30);  -- Current Block
  currel   VARCHAR2(30);  -- Current Relation
  curdtl   VARCHAR2(30);  -- Current Detail Block
  FUNCTION First_Changed_Block_Below(Master VARCHAR2)
  RETURN VARCHAR2 IS
    curblk VARCHAR2(30);  -- Current Block
    currel VARCHAR2(30);  -- Current Relation
    retblk VARCHAR2(30);  -- Return Block
  BEGIN
    --
    -- Initialize Local Vars
    --
    curblk := Master;
    currel := Get_Block_Property(curblk,  FIRST_MASTER_RELATION);
    --
    -- While there exists another relation for this block
    --
    WHILE currel IS NOT NULL LOOP
      --
      -- Get the name of the detail block
      --
      curblk := Get_Relation_Property(currel, DETAIL_NAME);
      --
      -- If this block has changes, return its name
      --
      IF ( Get_Block_Property(curblk, STATUS) = 'CHANGED' ) THEN
        RETURN curblk;
      ELSE
        --
        -- No changes, recursively look for changed blocks below
        --
        retblk := First_Changed_Block_Below(curblk);
        --
        -- If some block below is changed, return its name
        --
        IF retblk IS NOT NULL THEN
          RETURN retblk;
        ELSE
          --
          -- Consider the next relation
          --
          currel := Get_Relation_Property(currel, NEXT_MASTER_RELATION);
        END IF;
      END IF;
    END LOOP;
    --
    -- No changed blocks were found
    --
    RETURN NULL;
  END First_Changed_Block_Below;
BEGIN
  --
  -- Init Local Vars
  --
  mastblk  := :System.Master_Block;
  coordop  := :System.Coordination_Operation;
  trigblk  := :System.Trigger_Block;
  startitm := :System.Cursor_Item;
  frmstat  := :System.Form_Status;
  --
  -- If the coordination operation is anything but CLEAR_RECORD or
  -- SYNCHRONIZE_BLOCKS, then continue checking.
  --
  IF coordop NOT IN ('CLEAR_RECORD', 'SYNCHRONIZE_BLOCKS') THEN
    --
    -- If we're processing the driving master block...

begin
	clear_form(no_validate);
	begin
		select max(mth_transp_no)
		into v_no
		from mgt_transp_head
		where mth_txn_code = 'TPDN'
		and to_char(mth_dt,'YYYY') = to_char(sysdate,'YYYY');
	exception
		when others then
			v_no := null; 
	end;	

begin
	begin
		select max(mth_transp_no)
		into v_no
		from mgt_transp_head
		where mth_txn_code = 'TPDN'
		and to_char(mth_dt,'YYYY') = to_char(:mgt_transp_head.mth_dt,'YYYY');
	exception
		when others then
			v_no := null; 
	end;

begin	
	begin
		select count(mth_transp_no)
		into v_check
		from mgt_transp_head
		where mth_transp_no = :mgt_transp_head.mth_transp_no
		and mth_txn_code	  = :mgt_transp_head.mth_txn_code;
	exception
		when others then
			v_check := 0;	
	end;

begin	
	go_block('mgt_transp_head');
	if :mgt_transp_head.mth_status = 'Approved' then
		set_alert_property('AL_ERR', alert_message_text, 'Transaksi : '||:mgt_transp_head.mth_txn_code||'-'||:mgt_transp_head.mth_transp_no|| ' Status Sudah APPROVED, Tidak bisa di Edit !!');
    al_btn := show_alert('AL_ERR');
    while al_btn = alert_button2 loop
        al_btn := show_alert('AL_ERR');
    end loop;
    go_item('mgt_transp_head.mth_no');
	elsif :mgt_transp_head.mth_status is null then			
		begin
			select count(mth_transp_no)
			into v_check
			from mgt_transp_head
			where mth_transp_no = :mgt_transp_head.mth_transp_no
			and mth_txn_code	  = :mgt_transp_head.mth_txn_code;
		exception
			when others then
				v_check := 0;	
		end;

begin
	if :mgt_transp_head.mth_transp_no is not null then
		set_block_property('mgt_transp_head',default_where,'  and mth_txn_code = ''TPDN'' mth_transp_no = '''||:mgt_transp_head.mth_transp_no||''' ');				
		go_block('mgt_transp_head');					
		execute_query(no_validate);		
		--go_block('mgt_transp_detail_dn');
	end if;	
f :parameter.p_sys_id is null then			
		go_block('mgt_transp_head');
	end if;	
	go_block('mgt_transp_detail_dn');
	go_item('mgt_transp_detail_dn.mtdd_dn_no');		
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       										
end;

begin
	vlov := show_lov('LOV_FIND');
	if :parameter.p_sys_id is not null then
		set_block_property('mgt_transp_head',default_where,' mth_sys_id = '''||:parameter.p_sys_id||''' ');				
		go_block('mgt_transp_head');					
		execute_query(no_validate);		
	end if;	
	if :parameter.p_sys_id is null then			
		go_block('mgt_transp_head');
	end if;	
	go_block('mgt_transp_detail_dn');
	go_item('mgt_transp_detail_dn.mtdd_dn_no');		
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       										
end;

begin
	begin 
		delete from mgt_transp_detail_dn
		where mtdd_mth_sys_id 	= :mgt_transp_head.mth_sys_id;
	end;

	begin 
		delete from mgt_transp_detail_cost
		where mtdc_mth_sys_id 	= :mgt_transp_head.mth_sys_id;
	end;

	begin 
		delete from mgt_transp_head
		where mth_sys_id 	= :mgt_transp_head.mth_sys_id;
	end;	

begin
	begin
    select count(mth_status)
    into v_status
    from mgt_transp_head
    where mth_status = 'Approved'
    and mth_sys_id = :parameter.p_sys_id;
  end; 

		begin
	    select count(mtdc_rate_type)
	    into v_check
	    from mgt_transp_detail_cost
	    where mtdc_mth_sys_id = :parameter.p_sys_id;
	  exception
	  	when no_data_found then 
	    	v_check := 0;
	  end; 

	  	begin
	      delete from mgt_transp_detail_cost
	      where mtdc_mth_sys_id = :parameter.p_sys_id;
	  	end;   

begin
	delete_record;
	:system.message_level := 25;
	commit;
	:system.message_level := 0;		
	pkg.cost_auto;
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

begin
	pl_id := get_parameter_list('tmpdata'); 
	if not id_null(pl_id) then
		destroy_parameter_list(pl_id); 
	end if; 
	pl_id := Create_Parameter_List('tmpdata'); 
	add_parameter(pl_id,'PARAMFORM',text_parameter,'NO');
	add_parameter(pl_id,'P_SYS_ID',text_parameter,:mgt_transp_head.mth_sys_id);	
	run_product(REPORTS, '\\192.168.0.1\orionbin\bin\TRNSP001.rep', synchronous, runtime, filesystem, pl_id, null); 
end;

begin
	-- Added 12 Jan 2023
	begin
		select vssv_code
		into v_check_txn
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORT' 
		and nvl(vssv_frz_flag_num,2) = 2
		and vssv_code = :mgt_transp_detail_dn.mtdd_dn_txn_code; 
	exception 
    when others then
    	v_check_txn := null;	
	end;

		begin
			select nvl(mth_truck_cap,0)
			into v_cap 
			from mgt_transp_head
			where mth_sys_id = nvl(:mgt_transp_head.mth_sys_id, :mgt_transp_detail_cost.mtdc_mth_sys_id);
		exception
			when no_data_found then 
				v_cap := null;
		end;	

begin
	go_block('mgt_transp_detail_cost');
	if :mgt_transp_detail_cost.mtdc_rate_type is not null then
    if :mgt_transp_detail_cost.mtdc_rate_type = 'W' then
    	:mgt_transp_detail_cost.mtdc_total_rate := :mgt_transp_detail_cost.mtdc_rate;
    elsif :mgt_transp_detail_cost.mtdc_rate_type = 'Q' then
    	:mgt_transp_detail_cost.mtdc_total_rate := nvl(:mgt_transp_detail_cost.mtdc_qty,1) * :mgt_transp_detail_cost.mtdc_rate;    
    end if;
    :system.message_level := 25;
    commit;
    :system.message_level := 0;   
	end if;  
	go_block('mgt_transp_detail_dn');
	go_item('mgt_transp_detail_dn.mtdd_dn_no');
end;

begin
  :parameter.p_sys_id := :mgt_transp_detail_dn.mtdd_mth_sys_id;
  begin
      select mth_no
      into v_no
      from mgt_transp_head
      where mth_sys_id    = :parameter.p_sys_id;
  exception
      when no_data_found then 
          v_no := null;
  end; 

  begin        
      select count(mtr_priority)                     
      into v_mix
      from mgt_transp_rate
      where mtr_mtm_no    = v_no;
  end;
