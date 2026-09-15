-- ==============================================================
-- TRNSP006.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     197 WHEN-BUTTON-PRESSED
--      31 ON-YYYY
--       6 WHEN-CREATE-RECORD
--       6 KEY-HELP
--       4 ON-PRESSD
--       3 WHEN-RADIO-CHANGED
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 PRE-UPDATE
--       3 PRE-INSERT
--       3 POST-QUERY
--       3 ON-MESSAGE
--       3 ON-ERROR

-- ---------- Statement SQL ----------
DELETE
;

SELECT MGT_TRANSP_DETAIL_OTHCHG_SEQ.NEXTVAL FROM DUAL
;


-- ---------- Blok PL/SQL yang terbaca di dalam form ----------
-- Catatan: Forms menyimpan sebagian kode sebagai p-code, jadi ekstraksi ini
-- tidak dijamin lengkap. Yang di bawah adalah blok yang teksnya masih utuh.

BEGIN
eDECLARE
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
  	execute_query;  	
END; 

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
Sdeclare
	v_id	number;
begin	
	begin 
		select mgt_transp_detail_othchg_seq.nextval into v_id from dual;	
	end;

BEGIN
begin	
	:mgt_transp_detail_othchg.mtdo_upd_dt		:= sysdate;
	:mgt_transp_detail_othchg.mtdo_upd_uid	:= :parameter.m_user_id;
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
--MESSAGE (' X pos = '||Get_Window_Property ('CALENDAR_WIN', X_POS)||' - Y pos = '||Get_Window_Property ('CALENDAR_WIN', Y_POS));
hide_window('CALENDAR_WIN');
END;

BEGIN
 X pos =<
roperty =
 X_POS)|>
Get_Wind?
LENDAR_W@
de_windoA
ON-PRESSD
CEL_BT)
STANDARD
FORMS4W
"PKG INIT"<anonymous>""
CALENDAR_WIN
5package pkg is
procedure save;
procedure find;
end;

BEGIN
khide_window('CALENDAR_WIN');
copy(to_char(:calendar.curr_dt,'DD-MON-YYYY'),name_in('system.cursor_item'));
END;

begin	
	first_record;	
	loop 
    if :mgt_transp_detail_othchg.mtdo_status != 'Provision' then	
      :system.message_level := 25;
			commit;
			:system.message_level := 0;                                                     
    end if;    
    exit when :system.last_record = 'TRUE';
    next_record;
  end loop;  
  first_record;  
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

begin
	vlov := show_lov('LOV_FIND');
	if :parameter.p_sys_id is not null then
		set_block_property('mgt_transp_detail_othchg',default_where,' mtdo_mth_sys_id = '''||:parameter.p_sys_id||''' ');				
		go_block('mgt_transp_detail_othchg');					
		execute_query(no_validate);		
	end if;	
	if :parameter.p_sys_id is null then			
		go_block('mgt_transp_detail_othchg');
	end if;	
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       										
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
