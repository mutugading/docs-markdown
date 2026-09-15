-- ==============================================================
-- TRNSP003.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     234 WHEN-BUTTON-PRESSED
--      21 ON-YYYY
--      12 KEY-NEXT-ITEM
--       6 KEY-HELP
--       3 WHEN-TIMER-EXPIRED
--       3 WHEN-RADIO-CHANGED
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 WHEN-CHECKBOX-CHANGED
--       3 POST-TEXT-ITEM
--       3 ON-MESSAGE
--       3 ON-ERROR

-- ---------- Statement SQL ----------
DELETE
;

INSERT INTO MGT_TP_PROVISION ( TP_ID,TP_SYS_ID,TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,TP_AMT,TP_OTH_AMT,TP_STATUS,TP_CR_DT,TP_CR_UID,TP_AMT_PPH_GROSSUP,TP_MAIN_ACNT,TP_DUE_DATE,TP_NAME_CODE ) VALUES ( :b1,:b2,:b3,:b4,:b5,:b6,:b7,:b8,:b9,:b10,:b11,:b12,:b13,:b14,:b15,:b16,SYSDATE,:b17,:b18,:b19,:b20,:b21 )
;

SELECT COUNT(TP_NO) FROM MGT_TP_PROVISION WHERE TP_NO = :b1
;

SELECT DECODE(NVL(SUPP_FLEX_06,''N''),''Y'',1,''N'',0) FROM OM_SUPPLIER WHERE NVL(SUPP_FRZ_FLAG_NUM,2) = 2 AND SUPP_CODE = :b1 GROUP BY SUPP_FLEX_06
;

SELECT MGT_TP_PROVISION_ID_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TP_PROVISION_SEQ.NEXTVAL FROM DUAL
;

SELECT MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO TP_NO,MTH_DT TP_DT,MTH_NO TP_CODE,MTH_TRANSP_NAME TP_NAME,MTH_TRUCK_TYPE TP_TRUCK_TYPE,MTH_TRUCK_CAP TP_CAP,MTH_DESTINATION TP_DESTINATION,MTH_NO_POLICE TP_POL_NO,MTH_DRIVER TP_DRIVER,TOT_DN_QTY TP_QTY,TOTAL_GROSS_QTY TP_GROSS_QTY,MTDC_TOTAL_AMOUNT TP_AMT,NVL(OTHCHG_AMOUNT,0) TP_OTH_AMT,''Unposted'' TP_STATUS, NULL TP_JV_NO,DECODE(A.MTH_TXN_CODE,''TPCHP'',''208026'',''TPDN'',''208027'', NULL ) MAIN_ACNT, NULL TP_SYS_ID,DECODE(TO_NUMBER(TO_CHAR(MTH_DT,''DD'')),1,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),2,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),3,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),4,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),5,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),6,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),7,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),8,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),9,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),10,TO_DATE(
;

SELECT MTM_TRANSP_CODE FROM MGT_TRANSP_MASTER WHERE MTM_NO = :b1
;

SELECT TP_ID FROM MGT_TP_PROVISION WHERE TP_DT BETWEEN :b1 AND :b2 AND SUBSTR(TP_NO,1,4) = ''TPDN'' AND TP_STATUS != ''Posted'' GROUP BY TP_ID
;

SELECT TP_ID FROM MGT_TP_PROVISION WHERE TP_DT BETWEEN :b1 AND :b2 AND SUBSTR(TP_NO,1,5) = ''TPCHP'' AND TP_STATUS != ''Posted'' GROUP BY TP_ID
;

SELECT TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,ROUND(TP_AMT) TP_AMT,ROUND(TP_OTH_AMT) TP_OTH_AMT,TP_STATUS,TP_JV_NO,TP_MAIN_ACNT MAIN_ACNT,TP_SYS_ID,DECODE(TO_NUMBER(TO_CHAR(TP_DT,''DD'')),1,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),2,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),3,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),4,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),5,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),6,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),7,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),8,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),9,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),10,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),11,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),12,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),13,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),14,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),15,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),TO_DATE(''25/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY'')) DUE_DATE,TP_NAME_CODE FROM MGT_TP_PROVISION WHERE TP_DT BETWEEN NVL(:b1,TP_DT) AND NVL(:b2,TP_DT) AND TP_NO LIKE :b3 || ''%'' ORDER BY 1
;

SELECT TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,ROUND(TP_AMT) TP_AMT,ROUND(TP_OTH_AMT) TP_OTH_AMT,TP_STATUS,TP_JV_NO,TP_MAIN_ACNT MAIN_ACNT,TP_SYS_ID,DECODE(TO_NUMBER(TO_CHAR(TP_DT,''DD'')),1,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),2,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),3,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),4,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),5,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),6,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),7,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),8,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),9,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),10,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),11,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),12,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),13,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),14,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),15,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY''),TO_DATE(''25/'' || TO_CHAR(ADD_MONTHS(TP_DT,1),''MM/YY'') ,''DD/MM/YY'')) DUE_DATE,TP_NAME_CODE FROM MGT_TP_PROVISION WHERE TP_DT BETWEEN NVL(:b1,TP_DT) AND NVL(:b2,TP_DT) AND TP_NO LIKE :b3 || ''%'' UNION ALL SELECT MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO TP_NO,MTH_DT TP_DT,MTH_NO TP_CODE,MTH_TRANSP_NAME TP_NAME,MTH_TRUCK_TYPE TP_TRUCK_TYPE,MTH_TRUCK_CAP TP_CAP,MTH_DESTINATION TP_DESTINATION,MTH_NO_POLICE TP_POL_NO,MTH_DRIVER TP_DRIVER,TOT_DN_QTY TP_QTY,TOTAL_GROSS_QTY TP_GROSS_QTY,MTDC_TOTAL_AMOUNT TP_AMT,NVL(OTHCHG_AMOUNT,0) TP_OTH_AMT,''Unposted'' TP_STATUS, NULL TP_JV_NO,DECODE(A.MTH_TXN_CODE,''TPCHP'',''208026'',''TPDN'',''208027'', NULL ) MAIN_ACNT, NULL TP_SYS_ID,DECODE(TO_NUMBER(TO_CHAR(MTH_DT,''DD'')),1,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),2,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),3,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),4,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),5,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),6,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),7,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),8,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),9,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),10,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),11,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),12,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),13,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),14,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),15,TO_DATE(''10/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY''),TO_DATE(''25/'' || TO_CHAR(ADD_MONTHS(MTH_DT,1),''MM/YY'') ,''DD/MM/YY'')) DUE_DATE,MTM_TRANSP_CODE TP_NAME_CODE FROM MGT_TRANSP_HEAD A,(SELECT MTH_SYS_I
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
  if lv_errcod in (40401,41050,40735,40501,40212,41011, 40102) then
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
SDECLARE
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
END; 

BEGIN
declare
	expired_timer varchar2(20);
	timer_id 			timer;	
begin	
	expired_timer:=get_application_property(timer_name);	
	if expired_timer = 'DATE_TO' then	
		go_item('button.date_to');
	end if;	
	if expired_timer = 'TYPE' then	
		go_item('button.type');
	end if;
	if expired_timer = 'PROVISION' then	
		go_item('button.provision');
	end if;
	if expired_timer = 'FIND' then	
		go_item('button.btn_find');
	end if;
	timer_id := find_timer(expired_timer);
	if not id_null(timer_id) THEN
		 delete_timer(timer_id);
	end if;	
end;	

BEGIN
3declare
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  if lv_errcod in (40401,41050,40735,40501,40212,41011, 40102) then		
    null;
  else
    message(lv_errtype||'-'||to_char(lv_errcod)||':  '||lv_errtxt);   
  end if; 
end;

BEGIN
khide_window('CALENDAR_WIN');
copy(to_char(:calendar.curr_dt,'DD-MON-YYYY'),name_in('system.cursor_item'));
END;

BEGIN
--MESSAGE (' X pos = '||Get_Window_Property ('CALENDAR_WIN', X_POS)||' - Y pos = '||Get_Window_Property ('CALENDAR_WIN', Y_POS));
hide_window('CALENDAR_WIN');
END;

BEGIN
declare
	timer_id  timer;
begin
	if :button.date_from is not null then
		timer_id := create_timer('DATE_TO',1,NO_REPEAT);
	end if;	
end;

BEGIN
go_item('button.date_from');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
declare
	timer_id  timer;
begin
	if :button.date_to is not null then
		timer_id := create_timer('TYPE',1,NO_REPEAT);
	end if;	
end;

BEGIN
go_item('button.date_to');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
declare
	timer_id  timer;
begin
	if :button.type is not null then
		timer_id := create_timer('PROVISION',1,NO_REPEAT);
	end if;	
end;

BEGIN
declare
	timer_id  timer;
begin
	if :button.type is not null then
		timer_id := create_timer('FIND',1,NO_REPEAT);
	end if;	
end;

BEGIN
.if :button.date_from is not null and :button.date_to is not null and :button.type is not null then
	if :button.provision  	= 'All' then
		pkg.find_data_all;
	elsif :button.provision = 'Sudah' then
		pkg.find_data_sudah;
	elsif :button.provision = 'Belum' then
		pkg.find_data_belum;
	elsif :button.provision is null then	
		message('Provision belum di isi !!');
		message('Provision belum di isi !!');
		go_item('button.provision');
	end if;	
else
	message('Ada Parameter yang belum di isi !!');
	message('Ada Parameter yang belum di isi !!');
end if;	
END;

BEGIN
begin
	clear_form(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

BEGIN
if :button.check_all = 1 then
	begin
		go_block('mgt_tp_provision');
		first_record;
		loop 
	  	:mgt_tp_provision.checked	:= 1; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
	end;

	begin		
		go_block('mgt_tp_provision');
		first_record;
		loop 
	  	:mgt_tp_provision.checked	:= 0; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
 	end;

BEGIN
if :button.voucher_date is not null then
	pkg.save;
	if :button.type = 'TPDN' then
		begin
			select tp_id
			into :parameter.p_temp_id
			from mgt_tp_provision
			where tp_dt between :button.date_from and :button.date_to
			and substr(tp_no,1,4) = 'TPDN'
			and tp_status != 'Posted'
			group by tp_id;		
		end;

		begin
			select tp_id
			into :parameter.p_temp_id
			from mgt_tp_provision
			where tp_dt between :button.date_from and :button.date_to
			and substr(tp_no,1,5) = 'TPCHP'
			and tp_status != 'Posted'
			group by tp_id;		
		end;

BEGIN
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
Gif :mgt_tp_provision.tp_status = 'Unposted' and :mgt_tp_provision.tp_jv_no is null then
	pkg.delete_item;
elsif :mgt_tp_provision.tp_status = 'Posted' and :mgt_tp_provision.tp_jv_no is not null then	
	message('Data Tidak bisa Di Hapus, JV Sudah di Buat !!');
	message('Data Tidak bisa Di Hapus, JV Sudah di Buat !!');
end if;	
END;

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

begin
	go_block('mgt_tp_provision');
	clear_block(no_validate);
	first_record;			
	v_no := 1;
	for cd in c_data loop
		exit when c_data%notfound;
		:mgt_tp_provision.no 							:= v_no;
		:mgt_tp_provision.tp_no						:= cd.tp_no; 
    :mgt_tp_provision.tp_dt						:= cd.tp_dt; 
    :mgt_tp_provision.tp_code 				:= cd.tp_code; 
    :mgt_tp_provision.tp_name					:= cd.tp_name; 
    :mgt_tp_provision.tp_truck_type 	:= cd.tp_truck_type; 
    :mgt_tp_provision.tp_cap					:= cd.tp_cap; 
    :mgt_tp_provision.tp_destination 	:= cd.tp_destination; 
    :mgt_tp_provision.tp_pol_no				:= cd.tp_pol_no; 
    :mgt_tp_provision.tp_driver				:= cd.tp_driver;
    :mgt_tp_provision.tp_qty					:= cd.tp_qty; 
    :mgt_tp_provision.tp_gross_qty		:= cd.tp_gross_qty; 
    :mgt_tp_provision.tp_amt					:= cd.tp_amt; 
    :mgt_tp_provision.tp_oth_amt			:= cd.tp_oth_amt;
    :mgt_tp_provision.tp_status				:= cd.tp_status;
    :mgt_tp_provision.tp_jv_no				:= cd.tp_jv_no;
    :mgt_tp_provision.tp_main_acnt    := cd.main_acnt;  
    :parameter.p_t
emp_id							:= cd.tp_sys_id;  
    :mgt_tp_provision.tp_due_date     := cd.due_date;    
    :mgt_tp_provision.tp_name_code 		:= cd.tp_name_code;       
    v_no := v_no + 1;
    next_record;		
	end loop;	
	first_record;	
end;

begin
	go_block('mgt_tp_provision');
	clear_block(no_validate);
	first_record;			
	v_no := 1;
	for cd in c_data loop
		exit when c_data%notfound;
		:mgt_tp_provision.no 							:= v_no;
		:mgt_tp_provision.tp_no						:= cd.tp_no; 
    :mgt_tp_provision.tp_dt						:= cd.tp_dt; 
    :mgt_tp_provision.tp_code 				:= cd.tp_code; 
    :mgt_tp_provision.tp_name					:= cd.tp_name; 
    :mgt_tp_provision.tp_truck_type 	:= cd.tp_truck_type; 
    :mgt_tp_provision.tp_cap					:= cd.tp_cap; 
    :mgt_tp_provision.tp_destination 	:= cd.tp_destination; 
    :mgt_tp_provision.tp_pol_no				:= cd.tp_pol_no; 
    :mgt_tp_provision.tp_driver				:= cd.tp_driver;
    :mgt_tp_provision.tp_qty					:= cd.tp_qty; 
    :mgt_tp_provision.tp_gross_qty		:= cd.tp_gross_qty; 
    :mgt_tp_provision.tp_amt					:= cd.tp_amt; 
    :mgt_tp_provision.tp_oth_amt			:= cd.tp_oth_amt;
    :mgt_tp_provision.tp_status				:= cd.tp_status;
    :mgt_tp_provision.tp_jv_no				:= cd.tp_jv_no;
    :mgt_tp_provision.tp_main_acnt    := cd.main_acnt;  
    :parameter.p_temp_id							:= cd.tp_sys_id;  
    :mgt_tp_provision.tp_due_date     := cd.due_date;
    :mgt_tp_provision.tp_name_code 		:= cd.tp_name_code;                    
    v_no := v_no + 1;
    next_record;		
	end loop;	
	first_record;	
end;

begin
	go_block('mgt_tp_provision');
	clear_block(no_validate);
	first_record;			
	v_no := 1;
	for cd in c_data loop
		exit when c_data%notfound;
		:mgt_tp_provision.no 							:= v_no;
		:mgt_tp_provision.tp_no						:= cd.tp_no; 
    :mgt_tp_provision.tp_dt						:= cd.tp_dt; 
    :mgt_tp_provision.tp_code 				:= cd.tp_code; 
    :mgt_tp_provision.tp_name					:= cd.tp_name; 
    :mgt_tp_provision.tp_truck_type 	:= cd.tp_truck_type; 
    :mgt_tp_provision.tp_cap					:= cd.tp_cap; 
    :mgt_tp_provision.tp_destination 	:= cd.tp_destination; 
    :mgt_tp_provision.tp_pol_no				:= cd.tp_pol_no; 
    :mgt_tp_provision.tp_driver				:= cd.tp_driver;
    :mgt_tp_provision.tp_qty					:= cd.tp_qty; 
    :mgt_tp_provision.tp_gross_qty		:= cd.tp_gross_qty; 
    :mgt_tp_provision.tp_amt					:= cd.tp_amt; 
    :mgt_tp_provision.tp_oth_amt			:= cd.tp_oth_amt;
    :mgt_tp_provision.tp_status				:= cd.tp_status;
    :mgt_tp_provision.tp_jv_no				:= cd.tp_jv_no;
    :mgt_tp_provision.tp_main_acnt    := cd.main_acnt;  
    :parameter.p_temp_id							:= cd.tp_sys_id;  
    :mgt_tp_provision.tp_due_date     := cd.due_date;  
    :mgt_tp_provision.tp_name_code 		:= cd.tp_name_code;                   
    v_no := v_no + 1;
    next_record;		
	end loop;	
	first_record;	
end;

begin
	delete_record;
	:system.message_level := 25;
	commit;
	:system.message_level := 0;		
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

begin	
	begin
		go_block('mgt_tp_provision');
		first_record;
		begin
			select mgt_tp_provision_id_seq.nextval into :parameter.p_temp_id from dual;
		end; 	

	  		begin
	  			select count(tp_no)
	  			into v_check
	  			from mgt_tp_provision
	  			where tp_no = :mgt_tp_provision.tp_no; 
	  		end;	  		

	  			begin
	  				select mtm_transp_code
						into v_transp_code
						from mgt_transp_master
						where mtm_no = :mgt_tp_provision.tp_code;
	  			end;	

					begin
	  				select decode(nvl(supp_flex_06,'N'), 'Y', 1, 'N', 0)
	  				into v_grossup
						from om_supplier
						where nvl(supp_frz_flag_num,2) = 2
						and supp_code = v_transp_code
						group by supp_flex_06;
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
