-- ==============================================================
-- TRNSP001.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     222 WHEN-BUTTON-PRESSED
--      25 ON-YYYY
--       9 KEY-COMMIT
--       8 KEY-NEXT-ITEM
--       6 PRE-UPDATE
--       6 PRE-INSERT
--       4 ON-CLEAR-DETAILS
--       4 KEY-HELP
--       3 WHEN-RADIO-CHANGED
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 WHEN-CREATE-RECORD
--       3 PRE-FORM
--       3 ON-POPULATE-DETAILS
--       3 ON-CHECK-DELETE-MASTER
--       3 KEY-PRINT
--       3 KEY-CLRREC
--       1 ON-PRESSED

-- ---------- Statement SQL ----------
DELETE
;

DELETE FROM MGT_TRANSP_MASTER WHERE MTM_NO = :b1
;

DELETE FROM MGT_TRANSP_RATE WHERE MTR_MTM_NO = :b1
;

DELETE FROM MGT_TRANSP_RATE WHERE MTR_NO = :b1
;

INSERT
;

SELECT ''TR'' || LPAD(MAX(TO_NUMBER(SUBSTR(MTM_NO,3))) + 1 ,4,0) FROM MGT_TRANSP_MASTER
;

SELECT 1 FROM MGT_TRANSP_RATE M WHERE M.MTR_MTM_NO = :b1
;

SELECT COUNT(MTM_NO) FROM MGT_TRANSP_MASTER WHERE MTM_NO = :b1
;

SELECT MGT_TRANSP_RATE_SEQ.NEXTVAL FROM DUAL
;

SELECT USER_GROUP_ID FROM MENU_USER WHERE USER_ID = NVL(:b1,''ADMIN2'')
;

SELECT VSSV_CODE,VSSV_NAME FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''DESTINATION'' AND VSSV_FRZ_FLAG_NUM = 2
;

SELECT VSSV_CODE,VSSV_NAME FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSP_TYPE'' AND VSSV_FRZ_FLAG_NUM = 2
;

SELECT VSSV_CODE,VSSV_NAME FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TYPE_TRUCK'' AND VSSV_FRZ_FLAG_NUM = 2
;


-- ---------- Blok PL/SQL yang terbaca di dalam form ----------
-- Catatan: Forms menyimpan sebagian kode sebagai p-code, jadi ekstraksi ini
-- tidak dijamin lengkap. Yang di bawah adalah blok yang teksnya masih utuh.

BEGIN
declare
    Lebar        Number(8);
    Tinggi       Number(8);
    Xpos         Number(8);
    Ypos         Number(8);
    v_sid				 number;
    a						 varchar(30);
    b						 varchar(30);
    v_no				 number;
		v_trial_no	 number;
		flist				 number := 1;
begin
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
    
    /* user current access*/
  	/*
  	begin
			select sid into v_sid from v$mystat where rownum <=1; 		
  	end;	

		begin
			select  substr(machine,instr(machine,'\',1)+1,100) 
			into	:parameter.p_user_name
			from v$session
			where sid = v_sid; 
		end;		

 		begin
 			select user_group_id
			into :parameter.p_user_group
			from menu_user
			where user_id = nvl(:parameter.m_user_id,'ADMIN2');
 		end;				

BEGIN
-- Begin default relation program section
BEGIN
  Clear_All_Master_Details;
END;

BEGIN
Y--:system.message_level := 25;
commit;                     
--:system.message_level := 0;
END;

BEGIN
declare
	v_id	number;
	v_no	number;
	v_trial_no	number;
begin	
	null;
	/*begin 
		select mgt_transp_rate_seq.nextval into v_id from dual;
	end;*/
	:mgt_transp_master.mtm_cr_dt			:= sysdate;
	:mgt_transp_master.mtm_cr_uid			:= :parameter.m_user_id;
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
  -- Begin MGT_TRANSP_RATE detail program section
  --
  IF ( (:MGT_TRANSP_MASTER.MTM_NO is not null) ) THEN   
    rel_id := Find_Relation('MGT_TRANSP_MASTER.MGT_TRANSP_MAST_MGT_TRANSP_RAT');   
    Query_Master_Details(rel_id, 'MGT_TRANSP_RATE');   
  END IF;
  --
  -- End MGT_TRANSP_RATE detail program section
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
  -- Begin MGT_TRANSP_RATE detail declare section
  --
  CURSOR MGT_TRANSP_RATE_cur IS      
    SELECT 1 FROM MGT_TRANSP_RATE M     
    WHERE M.MTR_MTM_NO = :MGT_TRANSP_MASTER.MTM_NO;
  --
  -- End MGT_TRANSP_RATE detail declare section
  --
-- End default relation declare section
-- Begin default relation program section
BEGIN
  --
  -- Begin MGT_TRANSP_RATE detail program section
  --
  OPEN MGT_TRANSP_RATE_cur;     
  FETCH MGT_TRANSP_RATE_cur INTO Dummy_Define;     
  IF ( MGT_TRANSP_RATE_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TRANSP_RATE_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TRANSP_RATE_cur;
  --
  -- End MGT_TRANSP_RATE detail program section
  --
END;

BEGIN
tbegin	
	:mgt_transp_master.mtm_upd_dt			:= sysdate;
	:mgt_transp_master.mtm_upd_uid		:= :parameter.m_user_id;
end;	

BEGIN
begin
	clear_form(no_validate);	
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       	
end;

BEGIN
declare
	v_id	number;
begin	
	begin 
		select mgt_transp_rate_seq.nextval into v_id from dual;
	end;	

BEGIN
Fgo_block('mgt_transp_rate');
go_item('mgt_transp_rate.mtr_rate_type');
END;

BEGIN
qbegin	
	:mgt_transp_rate.mtr_upd_dt				:= sysdate;
	:mgt_transp_rate.mtr_upd_uid			:= :parameter.m_user_id;
end;	

BEGIN
{begin	
	clear_block(no_validate);
	hide_view('MAS_PRINT');
	hide_window('MAS_PRINT');
	go_block('MGT_TRX_STORE_HEAD');
end;

BEGIN
declare
	al_btn number;
begin
	if :parameter.p_user_group = 'SYSTEM' then
		pkg.delete_item;
	else
		set_alert_property('AL_ERR', alert_message_text, 'Data Master tidak Bisa di delete harap hubungi IT Software !');
	  al_btn := show_alert('AL_ERR');
	  while al_btn = alert_button2 loop
	      al_btn := show_alert('AL_ERR');
	  end loop;
	end if;	
end;	

BEGIN
if :mgt_transp_master.mtm_no is not null and :mgt_transp_master.mtm_transp_code is not null and :mgt_transp_master.mtm_truck_type is not null and 
	:mgt_transp_master.mtm_truck_cap is not null and :mgt_transp_master.mtm_destination is not null then	
	pkg.save;
else
	message('Silahkan cek ada data yang belum di isi kecuali Customer / Supplier !!');
	message('Silahkan cek ada data yang belum di isi kecuali Customer / Supplier !!');
end if;	
END;

BEGIN
go_block('mgt_transp_rate');	
clear_block(no_validate);
go_block('mgt_transp_master');	
clear_block(no_validate);
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');
pkg.find;
END;

BEGIN
bdeclare
	al_btn number;
begin
	if :parameter.p_user_group = 'SYSTEM' then
		pkg.delete_head;
	else
		set_alert_property('AL_ERR', alert_message_text, 'Data Master tidak Bisa di delete harap hubungi IT Software !');
	  al_btn := show_alert('AL_ERR');
	  while al_btn = alert_button2 loop
	      al_btn := show_alert('AL_ERR');
	  end loop;
	end if;	
end;	

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
if :calendar.month_it is not null then
	if TO_CHAR(:calendar.curr_dt,'YYYY')||:calendar.month_it <> to_char(:calendar.user_dt,'YYYYMM') THEN
		:calendar.curr_dt	:= to_date('01-'||:calendar.month_it||to_char(:calendar.curr_dt, '-YYYY'),'DD-MM-YYYY');
	else
		:calendar.curr_dt	:= to_date(TO_CHAR(:calendar.user_dt, 'DD-')||:calendar.month_it||to_char(:calendar.curr_dt,'-YYYY'),'DD-MM-YYYY');
	end if;
calendar_populate_month_pro(:calendar.curr_dt);
end if;
END;

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

BEGIN
  	set_Alert_Property(alert_id, ALERT_MESSAGE_TEXT, Msg); 
		dummy_var := Show_Alert(alert_id); 
END;

begin
	select 'TR'||lpad(max(to_number(substr(mtm_no,3))) + 1,4,0) 
	into :mgt_transp_master.mtm_no
	from mgt_transp_master;
end;

begin	
	go_block('mgt_transp_master');
	begin
		select count(mtm_no)
		into v_check
		from mgt_transp_master
		where mtm_no = :mgt_transp_master.mtm_no;
	exception
		when others then
			v_check := 0;	
	end;

begin
	go_block('mgt_transp_master');
	vlov := show_lov('LOV_FIND');
	if :mgt_transp_master.mtm_no is not null then	
		set_block_property('mgt_transp_master',default_where,' mtm_no = '''||:mgt_transp_master.mtm_no||''' ');						
		go_block('mgt_transp_master');					
		execute_query(no_validate);		
		go_block('mgt_transp_rate');
	end if;	
	if :mgt_transp_master.mtm_no is null then	
		go_block('mgt_transp_master');
	end if;	
end;

begin	
	begin 
		delete from mgt_transp_rate
		where mtr_mtm_no 	= :mgt_transp_master.mtm_no;
	end;

	begin 
		delete from mgt_transp_master
		where mtm_no 	= :mgt_transp_master.mtm_no;
	end;	

begin
	if :mgt_transp_master.mtm_no is not null then
		begin 
			delete from mgt_transp_rate
			where mtr_no 	= :mgt_transp_rate.mtr_no;
		end;	

begin
	pl_id := get_parameter_list('tmpdata'); 
	if not id_null(pl_id) then
		destroy_parameter_list(pl_id); 
	end if; 
	pl_id := Create_Parameter_List('tmpdata'); 
	add_parameter(pl_id,'PARAMFORM',text_parameter,'NO');
	add_parameter(pl_id,'P_SYS_ID',text_parameter,:mgt_transp_master.mtm_no);
	add_parameter(pl_id,'REP_VALUE_2',text_parameter,:PRINT.DATE_2);
	add_parameter(pl_id,'REP_VALUE_3',text_parameter,:PRINT.NO_FROM);
	add_parameter(pl_id,'REP_VALUE_4',text_parameter,:PRINT.NO_TO);
	add_parameter(pl_id,'REP_VALUE_5',text_parameter,:PRINT.SUPP);
	if :radio_options = 'PRINTER' then
		oth := :RADIO_OPTIONS;
	  add_parameter(pl_id,'DESTYPE',text_parameter,oth);
	  n_copies := :print.copies;
		add_parameter(pl_id,'COPIES',text_parameter,n_copies );
	elsif :radio_options = 'FILE' then /* PDF */
		oth := :RADIO_OPTIONS;
	  add_parameter(pl_id,'DESTYPE',text_parameter,oth);
		add_parameter(pl_id,'DESNAME',text_parameter,'C:\TEMP\'||:print.pdf_name||'.PDF');
		add_parameter(pl_id,'DESFORMAT',text_parameter,'PDF'); 		
	elsif :radio_options = 'E' then  --Excel
		v_date	:= to_char(sysdate,'DDMMYYYY_HH24MISS');
		v_name := 'SampleMaterial'||v_date;
		add_parameter(pl_id,'DESTYPE',text_parameter,'File');
		add_parameter(pl_id,'DESNAME',text_parameter,'C:\TEMP\'||v_name||'.XLS');
		add_parameter(pl_id,'M_DEST_TYPE',text_parameter,'E');					
	end if; 
	run_product(REPORTS, '\\192.168.0.1\orionbin\bin\MB0002.rep', synchronous, runtime, filesyste
m, pl_id, null); 
end;

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
/NSPC2/CHECK_PACKAGE_FAILURE
"PKG INIT"QUERY_MASTER_DETAILS"REL_ID"ID"DETAIL"OLDMSG"RELDEF""
"QUERY_MASTER_DETAILS"REL_ID"RELATION"DETAIL"VARCHAR2"OLDMSG"2"RELDEF"5"GET_RELATION_PROPERTY"DEFERRED_COORDINATION"System.Message_Level""="FALSE"GO_BLOCK"CHECK_PACKAGE_FAILURE"10"EXECUTE_QUERY"SET_BLOCK_PROPERTY"COORDINATION_STATUS"NON_COORDINATED"FORM_TRIGGER_FAILURE"RAISE""
QUERY_MASTER_DETAILS
FALSE
calendar.it14
P51_19_JAN_201708_30_10
BEGIN
,calendar_set_user_date_pro('calendar.it15');
END;

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
    --
    IF mastblk = trigblk THEN
      --
      -- If something in the form is changed, find the
      -- first changed block below the master
      --
      IF frmstat = 'CHANGED' THEN
        curblk := First_Changed_Block_Below(mastblk);
        --
        -- If we find a changed block below, go there
        -- and Ask to commit the changes.
        --
        IF curblk IS NOT NULL THEN
          Go_Block(curblk);
          Check_Package_Failure;
          Clear_Block(ASK_COMMIT);
          --
          -- If user cancels commit dialog, raise error
          --
          IF NOT ( :System.Form_Status = 'QUERY'
                   OR :System.Block_Status = 'NEW' ) THEN
            RAISE Form_Trigger_Failure;
          END IF;
        END IF;
      END IF;
    END IF;
  END IF;
  --
  -- Clear all the detail blocks for this master without
  -- any further asking to commit.
  --
  currel := Get_Block_Property(trigblk, FIRST_MASTER_RELATION);
  WHILE currel IS NOT NULL LOOP
    curdtl := Get_Relation_Property(currel, DETAIL_NAME);
    IF Get_Block_Property(curdtl, STATUS) <> 'NEW'  THEN
      Go_Block(curdtl);
      Check_Package_Failure;
      Clear_Block(NO_VALIDATE);
      IF :System.Block_Status <> 'NEW' THEN
        RAISE Form_Trigger_Failure;
      END IF;
    END IF;
    currel := Get_Relation_Property(currel, NEXT_MASTER_RELATION);
  END LOOP;
  --
  -- Put cursor back where it started
  --
  IF :System.Cursor_Item <> startitm THEN
    Go_Item(startitm);
    Check_Package_Failure;
  END IF;
EXCEPTION
  WHEN Form_Trigger_Failure THEN
    IF :System.Cursor_Item <> startitm THEN
      Go_Item(startitm);
    END IF;
    RAISE;
END Clear_All_Master_Details;
STANDARD
FORMS40
FORMS4C
SQLFORMS
/NSPC2/CHECK_PACKAGE_FAILURE

BEGIN
--MESSAGE (' X pos = '||Get_Window_Property ('CALENDAR_WIN', X_POS)||' - Y pos = '||Get_Window_Property ('CALENDAR_WIN', Y_POS));
hide_window('CALENDAR_WIN');
END;

BEGIN
  IF NOT ( Form_Success ) THEN
    RAISE Form_Trigger_Failure;
  END IF;
END;
