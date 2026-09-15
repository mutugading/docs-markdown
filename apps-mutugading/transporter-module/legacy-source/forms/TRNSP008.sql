-- ==============================================================
-- TRNSP008.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     178 WHEN-BUTTON-PRESSED
--      25 ON-YYYY
--       9 WHEN-CHECKBOX-CHANGED
--       5 PRE-INSERT
--       4 KEY-NEXT-ITEM
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 WHEN-CREATE-RECORD
--       3 PRE-UPDATE
--       3 POST-QUERY
--       3 ON-MESSAGE
--       3 ON-ERROR
--       1 WHEN-BUTTON-PRES

-- ---------- Statement SQL ----------
SELECT A.INVH_SYS_ID,A.LDN_NO,A.LDN_DT,A.TRANSP_CODE,A.TRANSP_NAME,A.TRUCK_TYPE,A.TRUCK_CAP,A.NO_POLICE,A.DRIVER,A.DESTINATION,NVL(B.MTDA_TRANSP_TXN,C.TPDN_NO) TRANSP_TXN,A.CUST_NAME FROM (SELECT A.INVH_SYS_ID,A.INVH_TXN_CODE || ''-'' || A.INVH_NO LDN_NO,A.INVH_DT LDN_DT,A.INVH_FLEX_06 TRANSP_CODE,A.INVH_FLEX_02 NO_POLICE,A.INVH_FLEX_03 DRIVER,A.INVH_FLEX_07 DESTINATION,B.MTM_TRANSP_NAME TRANSP_NAME,B.MTM_TRUCK_TYPE TRUCK_TYPE,B.MTM_TRUCK_CAP TRUCK_CAP,C.CUST_NAME FROM OT_INVOICE_HEAD A,MGT_TRANSP_MASTER B,OM_CUSTOMER C WHERE A.INVH_TXN_CODE IN ( ''LDN'',''PDN'',''JWDN'' ) AND A.INVH_DT = :b1 AND A.INVH_FLEX_06 IS NOT NULL AND A.INVH_FLEX_06 = B.MTM_NO AND A.INVH_CUST_CODE = C.CUST_CODE ) A,MGT_TRANSP_DN_AUTO B,(SELECT MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO TPDN_NO,MTDD_DN_TXN_CODE || ''-'' || MTDD_DN_NO DN_NO FROM MGT_TRANSP_HEAD,MGT_TRANSP_DETAIL_DN WHERE MTH_SYS_ID = MTDD_MTH_SYS_ID ) C WHERE A.INVH_SYS_ID = B.MTDA_DN_SYS_ID (+) AND A.LDN_NO = C.DN_NO (+) AND EXISTS (SELECT ''xxx'' FROM MGT_TRANSP_DN_AUTO WHERE MTDA_DN_NO = A.LDN_NO ) UNION ALL SELECT A.INVH_SYS_ID,A.LDN_NO,A.LDN_DT,A.TRANSP_CODE,A.TRANSP_NAME,A.TRUCK_TYPE,A.TRUCK_CAP,A.NO_POLICE,A.DRIVER,A.DESTINATION,NVL(B.MTDA_TRANSP_TXN,C.TPDN_NO) TRANSP_TXN,A.CUST_NAME FROM (SELECT A.INVH_SYS_ID,A.INVH_TXN_CODE || ''-'' || A.INVH_NO LDN_NO,A.INVH_DT LDN_DT,A.INVH_FLEX_06 TRANSP_CODE,A.INVH_FLEX_02 NO_POLICE,A.INVH_FLEX_03 DRIVER,A.INVH_FLEX_07 DESTINATION,B.MTM_TRANSP_NAME TRANSP_NAME,B.MTM_TRUCK_TYPE TRUCK_TYPE,B.MTM_TRUCK_CAP TRUCK_CAP,C.CUST_NAME FROM OT_INVOICE_HEAD A,MGT_TRANSP_MASTER B,OM_CUSTOMER C WHERE A.INVH_TXN_CODE IN ( ''LDN'',''PDN'',''JWDN'' ) AND A.INVH_DT = :b1 AND A.INVH_FLEX_06 IS NOT NULL AND A.INVH_FLEX_06 = B.MTM_NO AND A.INVH_CUST_CODE = C.CUST_CODE ) A,MGT_TRANSP_DN_AUTO B,(SELECT MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO TPDN_NO,MTDD_DN_TXN_CODE || ''-'' || MTDD_DN_NO DN_NO FROM MGT_TRANSP_HEAD,MGT_TRANSP_DETAIL_DN WHERE MTH_SYS_ID = MTDD_MTH_SYS_ID ) C WHERE A.INVH_SYS_ID = B.MTDA_DN_SYS_ID (+) AND A.LDN_NO = C.DN_NO (+) AND NOT EXISTS (SELECT ''xxx'' FROM MGT_TRANSP_DN_AUTO WHERE MTDA_DN_NO = A.LDN_NO ) ORDER BY 8,9,10,6,4,2
;

SELECT MGT_TRANSP_DN_AUTO_SEQ.NEXTVAL FROM DUAL
;

SELECT SUM(PRD_GROSS_WT) GROSS_WT,COUNT(PRD_SUB_UNITS) BOX FROM OT_WMS_PACK_TABLE_ALTHARA WHERE PRD_DOC_TXN_CODE IN ( ''LDN'',''JWDN'' ) AND PRD_DOC_TXN_CODE || ''-'' || PRD_DOCNO_SLNO = :b1
;

SELECT SUM(TO_NUMBER(INVI_FLEX_01)) GROSS_WT,SUM(TO_NUMBER(INVI_FLEX_02)) BOX FROM OT_INVOICE_HEAD,OT_INVOICE_ITEM WHERE INVH_SYS_ID = INVI_INVH_SYS_ID AND INVH_TXN_CODE = ''PDN'' AND INVH_TXN_CODE || ''-'' || INVH_NO = :b1
;


-- ---------- Blok PL/SQL yang terbaca di dalam form ----------
-- Catatan: Forms menyimpan sebagian kode sebagai p-code, jadi ekstraksi ini
-- tidak dijamin lengkap. Yang di bawah adalah blok yang teksnya masih utuh.

BEGIN
 X pos =:
roperty ;
 X_POS)|=
Get_Wind>
LENDAR_W?
de_windo@
  ND;
gray96-P
 "CEL_BT)
r25g25b0
	r100g50b0
	00r75g75b50
	00r88g88b50
"PKG INIT"<anonymous>""
CALENDAR_WIN
Upackage pkg is
procedure find (p_date date);
procedure generate (p_date date);
END;

BEGIN
JDECLARE
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
    Set_Window_Property ('MAIN_WIN', Window_state, maximize);    
    
    Lebar := Get_Window_Property(Forms_MDI_Window, Width);
    Tinggi:= Get_Window_Property(Forms_MDI_Window, Height);
        
    Xpos := (Lebar - Get_Window_Property('MAIN_WIN',Width))/2;
    Ypos := (Tinggi - Get_Window_Property('MAIN_WIN',Height))/2;
        
    Ypos := 5;
    Set_window_Property('MAIN_WIN', Position, Xpos, Ypos);
  	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');   
END; 

BEGIN
declare
  alert_button number; 
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  if lv_errcod in (40102,40401,41050,40735,40501,40212) then
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
*declare
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  if lv_errcod in (40102,40401,41050,40735,40501,40212) then
    null;
  else
    message(lv_errtype||'-'||to_char(lv_errcod)||':  '||lv_errtxt);   
  end if; 
end;

BEGIN
go_item('ldn_date.ldn_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
Kif :ldn_date.ldn_dt is not null then
	pkg.find(:ldn_date.ldn_dt);	
end if;
END;

BEGIN
,declare
	al_id alert;	
	al number;	
	v_status number;
begin	
	al_id	:= find_alert('PESAN');
	set_alert_property(al_id, alert_message_text, 'Apakah anda yakin akan di Generate ?');
	al 		:= show_alert('PESAN');
	if al = alert_button1 then 	    	
		pkg.generate (:ldn_date.ldn_dt);
	end if;						
end;

BEGIN
Ldeclare
	al_id alert;	
	al number;	
	v_status number;
begin	
	al_id	:= find_alert('PESAN');
	set_alert_property(al_id, alert_message_text, 'Apakah anda yakin akan di SAVE !!');
	al 		:= show_alert('PESAN');
	if al = alert_button1 then 	    	
		:system.message_level := 0;
		commit;
		:system.message_level := 25;
	end if;						
end;

BEGIN
begin
	clear_form(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

BEGIN
if :ldn_date.check_all = 1 then
	begin
		go_block('mgt_transp_dn_auto');
		first_record;
		loop 
	  	:mgt_transp_dn_auto.checked := 1; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
	end;

	begin		
		go_block('mgt_transp_dn_auto');
		first_record;
		loop 
	  	:mgt_transp_dn_auto.checked := 0; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
 	end;

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_dn_auto_seq.nextval into v_id from dual;	
	end;

BEGIN
vbegin	
	:mgt_transp_dn_auto.mtda_upd_dt		:= sysdate;
	:mgt_transp_dn_auto.mtda_upd_uid	:= :parameter.m_user_id;
end;	

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
ldeclare
begin
	if :mgt_transp_dn_auto.checked = 0 then		
 		:mgt_transp_dn_auto.mtda_gross_wt_temp	:= 0;
 		:mgt_transp_dn_auto.mtda_box_temp 			:= 0;
 	elsif :mgt_transp_dn_auto.checked = 1 then
 		:mgt_transp_dn_auto.mtda_gross_wt_temp	:= :mgt_transp_dn_auto.mtda_gross_wt;
 		:mgt_transp_dn_auto.mtda_box_temp 			:= :mgt_transp_dn_auto.mtda_box;
	end if;	
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
--MESSAGE (' X pos = '||Get_Window_Property ('CALENDAR_WIN', X_POS)||' - Y pos = '||Get_Window_Property ('CALENDAR_WIN', Y_POS));
hide_window('CALENDAR_WIN');
END;

BEGIN
khide_window('CALENDAR_WIN');
copy(to_char(:calendar.curr_dt,'DD-MON-YYYY'),name_in('system.cursor_item'));
END;

begin
	if to_number(to_char(p_date, 'YYYY')) between  1900 and 9999 then		
		--TO_CHAR(last_day(sys_date)
		v_end_date	:= to_char(last_day(p_date),'DD');
		v_start_day	:= rtrim(ltrim(to_char(trunc(p_date,'MONTH'),'DAY')));
		if v_start_day		= 'SUNDAY' then
			v_start_date	:=	1;
		elsif v_start_day = 'MONDAY' then
			v_start_date	:=	2;
		elsif v_start_day = 'TUESDAY' then
			v_start_date	:=	3;
		elsif v_start_day = 'WEDNESDAY' then
			v_start_date	:=	4;	
		elsif v_start_day = 'THURSDAY' then
			v_start_date	:=	5;	
		elsif v_start_day = 'FRIDAY' then
			v_start_date	:=	6;
		elsif v_start_day = 'SATURDAY' then
			v_start_date	:=	7;
		end if;	
		--MESSAGE ('DAY = '||v_start_day||' - S.Date = '||v_start_date||' - E.Date = '||v_end_date);
		v_active_bt	:= v_start_date;
		if v_end_date > 0 then					
			-- Resetting the Calendar
			for i in 1..v_tot_cal_tb loop
				set_item_property('calendar.it'||lpad(i,2,'0'),visible,property_false);
				set_item_property('calendar.it'||lpad(i,2,'0'),visual_attribute,'default');
				set_item_property('calendar.it'||lpad(i,2,'0'),label, '');
			end loop;									
			-- Generating the Calendar for the required MONTH YEAR
			for i in 1..v_tot_cal_tb loop
				/*
				if p_date = TO_DATE('01-NOV-2007', 'DD-MON-YYYY') then
					MESSAGE (i||' <= '||v_end_date||' AND '||NVL(v_active_bt, 0.0)||' <= '||v_tot_cal_tb);
				END if;
				*/			
				if	i <= v_end_date	and  v_active_bt <= v_tot_cal_tb then								
					--MESSAGE (TO_CHAR(p_date,'YYYYMMDD')||' = '||TO_CHAR(:calendar.user_dt,'YYYYMMDD'));				
					-- Making current date bold				
					if to_char(p_date,'YYYYMMDD') = to_char(:calendar.user_dt,'YYYYMMDD')	and i = to_char(p_date,'DD')	then		
						set_item_property('calendar.it'||lpad(v_active_bt, 2, '0'), visual_attribute,'calendar_curr_dt_va');
						:calendar.curr_dt_it	:=	'calendar.it'||lpad(v_active_bt, 2, '0');					
					end if;				
					-- Assigning date according to current month days				
					set_item_property ('calendar.it'||lpad(v_active_bt,2,'0'),label,to_char(i));
					set_item_property ('calendar.it'||lpad(v_active_bt,2,'0'),visible,property_true);
					set_item_property ('calendar.it'||lpad(v_active_bt,2,'0'),enabled,property_true);				
					v_active_bt	:= v_active_bt+1;				
				end if;
			end loop;		
			:calendar.curr_dt	:= p_date;
		end if;		
		-- Populating Month
		:calendar.month_it	:= to_char(p_date, 'MM');		
		show_window('CALENDAR_WIN');
		show_view('CALENDAR_CANVAS');	
	else
		message('Calendar not available for Year '||to_char(p_date,'YYYY'));
		message('Calendar not available for Year '||to_char(p_date,'YYYY'));		
	end if;	
exception
	when others then
		message ('CALENDAR_POPULATE_MONTH_PRO - O T H E R S ...');
		raise form_trigger_failure;
end;

begin	
	if get_item_property(p_it_name,label) is not null then		
		v_date	:= lpad(get_item_property(p_it_name,label), 2, '0')||to_char(:calendar.curr_dt,'-MON-YYYY');		
		-- Marking the selected date on the calendar
		set_item_property(:calendar.curr_Dt_it, visual_attribute,'default');
		set_item_property(p_it_name, visual_attribute,'calendar_curr_dt_va');
		:calendar.curr_dt_it	:=	p_it_name;		
	else		
		v_date	:= '01'||to_char(:calendar.curr_dt,'-MON-YYYY');
		-- Marking the selected date on the calendar
		set_item_property(:calendar.curr_dt_it, visual_attribute, 'default');
		set_item_property('calendar.IT01', visual_attribute,'calendar_curr_dt_va');		
		:calendar.curr_dt_it	:=	'calendar.it01';		
	end if;
	--:calendar.user_dt	:=	to_date(v_date,'DD-MON-YYYY');
	:calendar.user_dt	:=	to_date(v_date,'DD/MM/YYYY');
  :calendar.curr_dt	:=	:calendar.user_dt;
exception
	when others then
		message('DATE_PRO - O T H E R S ...');
		raise form_trigger_failure;
end;		

BEGIN
	set_Alert_Property(alert_id, ALERT_MESSAGE_TEXT, Msg); 
	dummy_var := Show_Alert(alert_id); 
END;

begin
	go_block('mgt_transp_dn_auto');	
	clear_block(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');	
	first_record;	
	for cd in c_data (p_date) loop
		if (substr(cd.ldn_no,1,3) = 'LDN' or substr(cd.ldn_no,1,4) = 'JWDN') then
			begin
				select sum(prd_gross_wt) gross_wt,
				       count(prd_sub_units) box
				into   v_qty,
						   v_box
				from ot_wms_pack_table_althara
				where prd_doc_txn_code in ('LDN', 'JWDN')
				and prd_doc_txn_code || '-' || prd_docno_slno = cd.ldn_no;
			exception
				when others then
					v_qty := 0;
					v_box := 0;
			end;

			begin							
				select sum(to_number(invi_flex_01)) gross_wt,
				       sum(to_number(invi_flex_02)) box
				into 	 v_qty,
							 v_box
				from ot_invoice_head,
				     ot_invoice_
item
				where invh_sys_id = invi_invh_sys_id 
				and invh_txn_code = 'PDN'
				and invh_txn_code || '-' || invh_no = cd.ldn_no;
			exception
				when others then
					v_qty := 0;
			end;

begin
	go_block('mgt_transp_dn_auto');	
	:system.message_level := 0;
	commit;
	:system.message_level := 25;
  first_record;
	loop 
    if :mgt_transp_dn_auto.checked = 1 and :mgt_transp_dn_auto.mtda_transp_txn is null then		
			pkg_transporter.insert_data (p_date, :parameter.m_user_id, :mgt_transp_dn_auto.mtda_trans_code, :mgt_transp_dn_auto.mtda_truck_type, :mgt_transp_dn_auto.mtda_truck_cap, 
			                             :mgt_transp_dn_auto.mtda_police_no, :mgt_transp_dn_auto.mtda_driver, :mgt_transp_dn_auto.mtda_destination);
		end if;
    exit when :system.last_record = 'TRUE';
    next_record;
	end loop;	
	set_block_property('mgt_transp_dn_auto',default_where,' where mtda_dn_dt = '''||p_date||''' ');					
	go_block('mgt_transp_dn_auto');		
	execute_query(no_validate);				
  first_record;     
end;
