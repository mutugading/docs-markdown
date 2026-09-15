-- ==============================================================
-- TRNSP005.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     190 WHEN-BUTTON-PRESSED
--      17 ON-YYYY
--       5 POST-QUERY
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 WHEN-CREATE-RECORD
--       3 ON-MESSAGE
--       2 ON-PRESSED

-- ---------- Statement SQL ----------
INSERT INTO MGT_TRANSP_MASTER ( MTM_NO,MTM_TRANSP_NAME,MTM_TRANSP_CODE,MTM_CUST_SUPP,MTM_CUST_SUPP_NAME,MTM_TRUCK_TYPE,MTM_TRUCK_CAP,MTM_DESTINATION,MTM_TYPE,MTM_CR_UID,MTM_CR_DT ) VALUES ( :b1,:b2,:b3,:b4,:b5,''TRAILER'',20000,:b6,''STO'',:b7,SYSDATE )
;

INSERT INTO MGT_TRANSP_RATE ( MTR_NO,MTR_MTM_NO,MTR_PRIORITY,MTR_RATE_TYPE,MTR_MAX_CAP,MTR_RATE,MTR_DESTINATION,MTR_TRUCK_TYPE,MTR_CR_UID,MTR_CR_DT ) VALUES ( :b1,:b2,1,''Q'',1,:b3,:b4,''TRAILER'',:b5,SYSDATE )
;

SELECT ''TR'' || LPAD(MAX(TO_NUMBER(SUBSTR(MTM_NO,3))) + 1 ,4,0) FROM MGT_TRANSP_MASTER
;

SELECT COUNT(MTM_NO) FROM MGT_TRANSP_MASTER WHERE MTM_TYPE = ''STO'' AND MTM_CUST_SUPP = :b1 AND MTM_TRANSP_CODE = :b2 AND MTM_DESTINATION = :b3
;

SELECT GR_NO,GR_DT,SUPP_CODE,SUPPLIER,TRANSPORTER,TRANSPORTER_CODE,DECODE(TRANSPORTER_CODE, NULL ,''BELUM DI SETUP'',''GRN BELUM APPROVE'') STATUS FROM (SELECT GH_TXN_CODE || ''-'' || GH_NO GR_NO,GH_DT GR_DT,GH_SUPP_CODE SUPP_CODE,SUPP_NAME SUPPLIER,GH_FLEX_05 TRANSPORTER,MTM_TRANSP_CODE TRANSPORTER_CODE FROM OT_GR_HEAD,OM_SUPPLIER,(SELECT MTM_TRANSP_CODE,MTM_TRANSP_NAME,MTM_CUST_SUPP FROM MGT_TRANSP_MASTER WHERE MTM_TYPE = ''STO'' GROUP BY MTM_TRANSP_CODE,MTM_TRANSP_NAME,MTM_CUST_SUPP ) WHERE GH_TXN_CODE = ''CHPGRN'' AND GH_SUPP_CODE = SUPP_CODE AND SUBSTR(SUPP_CODE,1,2) != ''IS'' AND GH_DT >= ''01-APR-2021'' AND MTM_TRANSP_NAME = GH_FLEX_05 AND MTM_CUST_SUPP = GH_SUPP_CODE AND NOT EXISTS (SELECT ''XXX'' FROM MGT_TRANSP_DETAIL_DN WHERE MTDD_DN_TXN_CODE = ''CHPGRN'' AND MTDD_DN_NO = GH_NO ) ) ORDER BY GR_NO
;

SELECT MGT_TRANSP_RATE_SEQ.NEXTVAL FROM DUAL
;


-- ---------- Blok PL/SQL yang terbaca di dalam form ----------
-- Catatan: Forms menyimpan sebagian kode sebagai p-code, jadi ekstraksi ini
-- tidak dijamin lengkap. Yang di bawah adalah blok yang teksnya masih utuh.

BEGIN
cDECLARE
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
		pkg.populate;
END; 

BEGIN
DECLARE 
  alert_button NUMBER; 
  lv_errtype VARCHAR2(3) := MESSAGE_TYPE;
  lv_errcod NUMBER := MESSAGE_CODE;
  lv_errtxt VARCHAR2(80) := MESSAGE_TEXT;
BEGIN 
  IF lv_errcod in (40102,40401,41050,40735,40501,40212) THEN
    null;
  ELSE 
    Message(lv_errtype||'-'||to_char(lv_errcod)||':  '||lv_errtxt);
    RAISE Form_Trigger_Failure; 
  END IF; 
    IF form_fatal OR form_failure THEN
     raise form_trigger_failure;
  END IF;   
END; 

BEGIN
|if :view.gr_no is not null and :view.gr_dt is not null and :view.supp_code is not null and :view.supp_name is not null and 
	:view.transp_code is not null and :view.transp_name is not null and :view.destination is not null then	 
	pkg.create_transp_master;
else
	message('Please Cek kolom ada yang belum di Isi !!');
	message('Please Cek kolom ada yang belum di Isi !!');
end if;	
END;

BEGIN
zclear_form(no_validate);
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1'); 
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
clear_block(no_validate);
go_block('gl_trans_head');	
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       	
--pkg.find;
END;

BEGIN
--MESSAGE (' X pos = '||Get_Window_Property ('CALENDAR_WIN', X_POS)||' - Y pos = '||Get_Window_Property ('CALENDAR_WIN', Y_POS));
hide_window('CALENDAR_WIN');
END;

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
  	set_Alert_Property(alert_id, ALERT_MESSAGE_TEXT, Msg); 
		dummy_var := Show_Alert(alert_id); 
END;

BEGIN
khide_window('CALENDAR_WIN');
copy(to_char(:calendar.curr_dt,'DD-MON-YYYY'),name_in('system.cursor_item'));
END;

begin
	go_block('view');
	clear_block(no_validate);
	first_record;
	for cd in c_data loop
  	exit when c_data%notfound;
    :view.no 						:= v_id;
		:view.gr_no 				:= cd.gr_no;
    :view.gr_dt 				:= cd.gr_dt;
    :view.status   			:= cd.status;
    :view.supp_code   	:= cd.supp_code;
    :view.supp_name 	  := cd.supplier;
    :view.transp_code   := cd.transporter_code;
    :view.transp_name 	:= cd.transporter;
    next_record;
    v_id := v_id + 1;	    
	end loop;
	first_record;
end;

begin
	begin
		select count(mtm_no)
		into v_check
		from mgt_transp_master
		where mtm_type = 'STO'
		and mtm_cust_supp   = :view.supp_code
		and mtm_transp_code = :view.transp_code
		and mtm_destination = :view.destination;
	end;

		begin
			select 'TR'||lpad(max(to_number(substr(mtm_no,3))) + 1,4,0) 
			into v_no
			from mgt_transp_master;
		end;

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
	:calendar.user_dt	:=	to_date(v_date,'DD-MON-YYYY');
  :calendar.curr_dt	:=	:calendar.user_dt;
exception
	when others then
		message('DATE_PRO - O T H E R S ...');
		raise form_trigger_failure;
end;		
