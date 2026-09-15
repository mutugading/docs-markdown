-- ==============================================================
-- TRNSP007.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     269 WHEN-BUTTON-PRESSED
--      35 ON-YYYY
--      20 POST-TEXT-ITEM
--      14 KEY-NEXT-ITEM
--      12 KEY-HELP
--       9 WHEN-VALIDATE-ITEM
--       7 PRE-UPDATE
--       6 WHEN-NEW-ITEM-INSTANCE
--       6 WHEN-CREATE-RECORD
--       6 POST-QUERY
--       5 PRE-INSERT
--       4 ON-CLEAR-DETAILS
--       3 WHEN-TIMER-EXPIRED
--       3 WHEN-RADIO-CHANGED
--       3 WHEN-NEW-FORM-INSTANCE
--       3 WHEN-LIST-CHANGED
--       3 WHEN-CHECKBOX-CHANGED
--       3 POST-CHANGE
--       3 ON-POPULATE-DETAILS
--       3 ON-MESSAGE
--       3 ON-ERROR
--       3 ON-CHECK-DELETE-MASTER
--       1 ON-PRESSED

-- ---------- Statement SQL ----------
DELETE
;

INSERT
;

SELECT 1 FROM MGT_TP_PROVISION_ADD M WHERE M.TPA_TPBA_SYS_ID = :b1
;

SELECT COUNT(TPA_ID) FROM MGT_TP_PROVISION_ADD WHERE TPA_TPBA_SYS_ID = :b1
;

SELECT COUNT(TPA_TPBA_SYS_ID) FROM MGT_TP_PROVISION_ADD WHERE TPA_TPBA_SYS_ID = :b1 AND TPA_NO LIKE ''TPCHP%''
;

SELECT COUNT(TPA_TPBA_SYS_ID) FROM MGT_TP_PROVISION_ADD WHERE TPA_TPBA_SYS_ID = :b1 AND TPA_NO LIKE ''TPDN%''
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_01 = ''PPN 1%'' AND VSSV_CODE = :b1
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_02 = ''NON PPH'' AND VSSV_CODE = :b1
;

SELECT JML_STS_DOC FROM (SELECT A.TPA_TPBA_SYS_ID,C.MTDD_STS_DOC STS_DOC,COUNT(C.MTDD_STS_DOC) JML_STS_DOC FROM MGT_TP_PROVISION_ADD A,MGT_TRANSP_HEAD B,MGT_TRANSP_DETAIL_DN C WHERE A.TPA_NO = B.MTH_TXN_CODE || ''-'' || B.MTH_TRANSP_NO AND B.MTH_SYS_ID = C.MTDD_MTH_SYS_ID AND A.TPA_NO LIKE ''TPDN%'' AND C.MTDD_STS_DOC = ''N'' AND A.TPA_TPBA_SYS_ID = :b1 AND A.TPA_TPBA_SYS_ID IS NOT NULL GROUP BY A.TPA_TPBA_SYS_ID,C.MTDD_STS_DOC )
;

SELECT JML_STS_DOC FROM (SELECT MTDD_STS_DOC STS_DOC,COUNT(MTDD_STS_DOC) JML_STS_DOC FROM MGT_TRANSP_HEAD,MGT_TRANSP_DETAIL_DN WHERE MTDD_MTH_SYS_ID = MTH_SYS_ID AND MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO = :b1 AND NVL(MTDD_STS_DOC,''N'') = ''N'' GROUP BY MTDD_STS_DOC )
;

SELECT MAX(TPA_DUE_DATE) DUE_DATE FROM MGT_TP_PROVISION_ADD WHERE TPA_TPBA_SYS_ID = :b1
;

SELECT MAX(TPA_ID) FROM MGT_TP_PROVISION_ADD WHERE TPA_TPBA_SYS_ID = :b1
;

SELECT MGT_TP_PROVISION_ADD_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TP_PROVISION_BILL_ADD_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TP_PROVISION_ID_SEQ.NEXTVAL FROM DUAL
;

SELECT MTDD_DN_TXN_CODE || ''-'' || MTDD_DN_NO DN_NO,MTDD_DN_QTY QTY,MTDD_SYS_ID,MTDD_STS_PRS,MTDD_STS_DOC FROM MGT_TRANSP_DETAIL_DN WHERE MTDD_MTH_SYS_ID = :b1 ORDER BY MTDD_SYS_ID
;

SELECT MTH_SYS_ID FROM MGT_TRANSP_HEAD WHERE MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO = :b1
;

SELECT NVL(MAX(SUBSTR(TPBA_TRX_NO,10,10)),0) + 1 FROM MGT_TP_PROVISION_BILL_ADD WHERE SUBSTR(TPBA_TRX_NO,1,8) = ''TBILLADD'' AND NVL(SUBSTR(TPBA_TRX_NO,10,4),TO_CHAR(TO_DATE(SYSDATE),''RRRR'')
;

SELECT NVL(SUPP_FLEX_06,''N'') GROSS_UP FROM OM_SUPPLIER WHERE NVL(SUPP_FRZ_FLAG_NUM,2) = 2 AND SUPP_CODE = :b1 GROUP BY NVL(SUPP_FLEX_06,''N'')
;

SELECT SUPP_NAME FROM OM_SUPPLIER WHERE SUPP_CODE = :b1 AND NVL(SUPP_FRZ_FLAG_NUM,2) = 2
;

SELECT TPBA_BILL_AMT FROM MGT_TP_PROVISION_BILL_ADD WHERE TPBA_SYS_ID = :b1
;

SELECT TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,TP_AMT,TP_OTH_AMT,TP_MATCH_STATUS,TP_JV_NO,TP_STATUS,SJ_NO,GROSS,MAIN_ACNT,TP_DUE_DATE,TP_NAME_CODE,TP_TJV_NO,TP_JV_DT FROM (SELECT B.LDN,A.TP_NO,A.TP_DT,A.TP_CODE,A.TP_NAME,A.TP_TRUCK_TYPE,A.TP_CAP,A.TP_DESTINATION,A.TP_POL_NO,A.TP_DRIVER,A.TP_QTY,A.TP_GROSS_QTY,A.TP_AMT,A.TP_OTH_AMT,A.TP_MATCH_STATUS,A.TP_JV_NO,A.TP_STATUS,DECODE(B.MTH_TXN_CODE,''TPCHP'',''208026'',''TPDN'',''208027'', NULL ) MAIN_ACNT,A.TP_DUE_DATE,A.TP_NAME_CODE,A.TP_TJV_NO,A.TP_JV_DT FROM MGT_TP_PROVISION A,(SELECT A.MTH_SYS_ID,A.MTH_TXN_CODE || ''-'' || A.MTH_TRANSP_NO TPDN,B.MTDD_DN_TXN_CODE || ''-'' || B.MTDD_DN_NO LDN,A.MTH_TXN_CODE FROM MGT_TRANSP_HEAD A,MGT_TRANSP_DETAIL_DN B WHERE A.MTH_SYS_ID = B.MTDD_MTH_SYS_ID ) B WHERE A.TP_NO = B.TPDN AND A.TP_TJV_NO IS NOT NULL AND A.TP_NAME_CODE = :b1 AND NOT EXISTS (SELECT ''XXX'' FROM FT_CUR_TRANS_HEADER WHERE A.TP_JV_NO = TH_TRAN_CODE || ''-'' || TH_DOC_NO AND NVL(TH_APPR_STATUS,0) != 3 ) GROUP BY B.LDN,A.TP_NO,A.TP_DT,A.TP_CODE,A.TP_NAME,A.TP_TRUCK_TYPE,A.TP_CAP,A.TP_DESTINATION,A.TP_POL_NO,A.TP_DRIVER,A.TP_QTY,A.TP_GROSS_QTY,A.TP_AMT,A.TP_OTH_AMT,A.TP_MATCH_STATUS,A.TP_JV_NO,A.TP_STATUS,B.MTH_TXN_CODE,A.TP_DUE_DATE,A.TP_NAME_CODE,A.TP_TJV_NO,A.TP_JV_DT ) A,(SELECT GH_TXN_CODE || ''-'' || GH_NO GR_NO,GH_FLEX_01 SJ_NO,GH_FLEX_06 GROSS FROM OT_GR_HEAD WHERE GH_TXN_CODE = ''CHPGRN'' ) B WHERE A.LDN = B.GR_NO (+) AND A.LDN = :b2 ORDER BY TP_NO DESC,TP_DT DESC
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
LDECLARE
    Lebar Number(8);
    Tinggi Number(8);
    Xpos Number(8);
    Ypos Number(8);
    v_sid	number;
    v_bag varchar2(15);
    v_bag_no varchar2(15);
    v_year varchar2(4);	
    v_pph_1 number;
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
  	pkg.new_no;
END; 

BEGIN
declare
	expired_timer varchar2(20);
	timer_id 			timer;	
begin	
	expired_timer:=get_application_property(timer_name);	
	if expired_timer = 'NEXT_ITEM' then	
		go_item('mgt_tp_provision_add.tpa_amt_typing');
	end if;	
	if expired_timer = 'NEXT_ITEM_DT' then	
		go_item('mgt_tp_provision_add.tpa_dt');
	end if;	
	if expired_timer = 'NEXT_DT' then	
		go_item('mgt_tp_provision_add.tpa_amt_typing');
	end if;	
	if expired_timer = 'NEXT_REMARK' then	
		go_item('mgt_tp_provision_add.tpa_remark');
	end if;	
	if expired_timer = 'NEW_RECORD' then	
		next_record;
		go_item('mgt_tp_provision_add.tpa_no');
	end if;
	timer_id := find_timer(expired_timer);
	if not id_null(timer_id) THEN
		 delete_timer(timer_id);
	end if;	
end;	

BEGIN
declare	
	v_ppn_1 number;	
	v_pph_1 number;	
	v_gross_up varchar2(1);	
begin
	begin
	  select nvl(supp_flex_06, 'N') gross_up
	  into v_gross_up
	  from om_supplier
	  where nvl(supp_frz_flag_num,2) = 2                
	  and supp_code = :mgt_tp_provision_bill_add.tpba_supp_code
	  group by nvl(supp_flex_06, 'N');
	exception
   	when others then
    	v_gross_up := 'N';        
	end;	

	begin
		select count(vssv_code)
		into v_ppn_1
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORTER'
		and nvl(vssv_frz_flag_num, 2) = 2
		and vssv_field_01 = 'PPN 1%'
		and vssv_code = :mgt_tp_provision_bill_add.tpba_supp_code;
	end;

	begin
		select count(vssv_code)
		into v_pph_1
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORTER'
		and nvl(vssv_frz_flag_num, 2) = 2
		and vssv_field_02 = 'NON PPH'
		and vssv_code = :mgt_tp_provision_bill_add.tpba_supp_code;
	end;

	begin
		select tpba_bill_amt
		into :mgt_tp_provision_bill_add.tpba_bill_amt
		from mgt_tp_provision_bill_add
		where tpba_sys_id = :parameter.p_sys_id;
	exception
		when others then
			:mgt_tp_provision_bill_add.tpba_bill_amt := 0;
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
-- Begin default relation program section
BEGIN
  Clear_All_Master_Details;
END;

BEGIN
  IF NOT ( Form_Success ) THEN
    RAISE Form_Trigger_Failure;
  END IF;
END;

BEGIN
declare
	v_type varchar2(10);
	v_bulan varchar2(5);
begin	
	if nvl(:mgt_tp_provision_bill_add.tpba_bill_amt,0) != 0 then
		:mgt_tp_provision_bill_add.tpba_total := nvl(:mgt_tp_provision_bill_add.tpba_bill_amt,0) + nvl(:mgt_tp_provision_bill_add.tpba_ppn_amt,0) - nvl(:mgt_tp_provision_bill_add.tpba_pph_amt,0);	
	end if;	
	if :mgt_tp_provision_bill_add.tpba_due_date is null then
		begin
			select max(tpa_due_date) due_date
			into :mgt_tp_provision_bill_add.tpba_due_date
			from mgt_tp_provision_add
			where tpa_tpba_sys_id = :mgt_tp_provision_bill_add.tpba_sys_id; 
		exception
			when others then
				:mgt_tp_provision_bill_add.tpba_due_date := null;
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
  -- Begin MGT_TP_PROVISION_ADD detail program section
  --
  IF ( (:MGT_TP_PROVISION_BILL_ADD.TPBA_SYS_ID is not null) ) THEN   
    rel_id := Find_Relation('MGT_TP_PROVISION_BILL_ADD.MGT_TP_PROVISIO_MGT_TP_PROVISI');   
    Query_Master_Details(rel_id, 'MGT_TP_PROVISION_ADD');   
  END IF;
  --
  -- End MGT_TP_PROVISION_ADD detail program section
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
  -- Begin MGT_TP_PROVISION_ADD detail declare section
  --
  CURSOR MGT_TP_PROVISION_ADD_cur IS      
    SELECT 1 FROM MGT_TP_PROVISION_ADD M     
    WHERE M.TPA_TPBA_SYS_ID = :MGT_TP_PROVISION_BILL_ADD.TPBA_SYS_ID;
  --
  -- End MGT_TP_PROVISION_ADD detail declare section
  --
-- End default relation declare section
-- Begin default relation program section
BEGIN
  --
  -- Begin MGT_TP_PROVISION_ADD detail program section
  --
  OPEN MGT_TP_PROVISION_ADD_cur;     
  FETCH MGT_TP_PROVISION_ADD_cur INTO Dummy_Define;     
  IF ( MGT_TP_PROVISION_ADD_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TP_PROVISION_ADD_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TP_PROVISION_ADD_cur;
  --
  -- End MGT_TP_PROVISION_ADD detail program section
  --
END;

BEGIN
Jdeclare
	v_id	number;
begin	
	begin
		select mgt_tp_provision_bill_add_seq.nextval into v_id from dual;
	end;

BEGIN
begin	
	:mgt_tp_provision_bill_add.tpba_up_dt  	:= sysdate;
	:mgt_tp_provision_bill_add.tpba_up_uid 	:= :parameter.m_user_id;
end;

BEGIN
declare
	v_pph_1 number;
begin	
	if :mgt_tp_provision_bill_add.tpba_supp_code is not null then
		begin
			select supp_name
			into :mgt_tp_provision_bill_add.tpba_supp_name
			from om_supplier
			where supp_code = :mgt_tp_provision_bill_add.tpba_supp_code
			and nvl(supp_frz_flag_num, 2) = 2;
		end;	

		begin
			select count(vssv_code)
			into v_pph_1
			from im_vs_static_value 
			where vssv_vs_code = 'TRANSPORTER'
			and nvl(vssv_frz_flag_num, 2) = 2
			and vssv_field_02 = 'NON PPH'
			and vssv_code = :mgt_tp_provision_bill_add.tpba_supp_code;
		end;

BEGIN
go_item('mgt_tp_provision_bill_add.tpba_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill_add.tpba_bill_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill_add.tpba_fp_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
declare	
	v_ppn_1 number;		
begin
	begin
		select count(vssv_code)
		into v_ppn_1
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORTER'
		and nvl(vssv_frz_flag_num, 2) = 2
		and vssv_field_01 = 'PPN 1%'
		and vssv_code = :mgt_tp_provision_bill_add.tpba_supp_code;
	end;

BEGIN
declare
	v_gross_up varchar2(1);
	v_pph_1 number;
begin
	begin
	  select nvl(supp_flex_06, 'N') gross_up
	  into v_gross_up
	  from om_supplier
	  where nvl(supp_frz_flag_num,2) = 2                
	  and supp_code = :mgt_tp_provision_bill_add.tpba_supp_code
	  group by nvl(supp_flex_06, 'N');
	exception
   	when others then
    	v_gross_up := 'N';        
	end;

BEGIN
go_item('mgt_tp_provision_bill_add.tpba_due_date');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill_add.tpba_voucher_date');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
 begin	
	--if nvl(:mgt_tp_provision_bill_add.tpba_status, 'XXX') != 'GENERATE' then
		:system.message_level := 25;
		commit;
		:system.message_level := 0;			
		alert_message('Data Sudah Tersimpan !!');
	--else
	--	alert_message('TJV Sudah dibuat, Tidak bisa di edit !!');
	--end if;	
end;

BEGIN
sbegin
	clear_block(no_validate);
	:parameter.p_sys_id  := null;
  :parameter.p_temp_id := null; 
	pkg.new_no;
end;

BEGIN
begin
	clear_form(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       		
end;

BEGIN
clear_form(no_validate);
go_block('mgt_tp_provision_bill_add');	
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');       	
pkg.find;
END;

BEGIN
1begin	
	if nvl(:mgt_tp_provision_bill_add.tpba_status, 'XXX') != 'GENERATE' then
		delete_record;
		:system.message_level := 25;
		commit;
		:system.message_level := 0;			
		alert_message('Data Sudah Di Delete !!');
	else
		alert_message('Status Sudah Generate, Tidak bisa di Delete !!');
	end if;	
end;

BEGIN
!declare
	v_id	number;
	v_check number;
begin	
	begin
		select mgt_tp_provision_add_seq.nextval into v_id from dual;
	end;	

  begin
		select count(tpa_id)
		into v_check
		from mgt_tp_provision_add
		where tpa_tpba_sys_id = :parameter.p_sys_id;	
	end;	

		begin
			select max(tpa_id)
			into :parameter.p_temp_id
			from mgt_tp_provision_add
			where tpa_tpba_sys_id = :parameter.p_sys_id;			
		end;	

BEGIN
|if :mgt_tp_provision_bill_add.tpba_sys_id is not null then
	pkg.print;
else
	alert_message('Please Save before !');
end if;	
END;

BEGIN
declare
	v_check number;
begin	
	:mgt_tp_provision_add.tpa_up_dt  			:= sysdate;
	:mgt_tp_provision_add.tpa_up_uid 		  := :parameter.m_user_id;
	:mgt_tp_provision_add.tpa_tpba_sys_id := nvl(:mgt_tp_provision_bill_add.tpba_sys_id,:parameter.p_sys_id);	
end;

BEGIN
declare
	v_jml number;
	v_cursor varchar2(150);
begin
	v_cursor := :system.cursor_item;
	if nvl(:mgt_tp_provision_add.tpa_amt,0) != 0 then
		:mgt_tp_provision_add.tpa_total_amt := round(nvl(:mgt_tp_provision_add.tpa_amt,0) + nvl(:mgt_tp_provision_add.tpa_oth_amt,0));		
	end if;	
	if :mgt_tp_provision_add.tpa_no is not null then
		begin
		  select jml_sts_doc
		  into v_jml
			from
			(
				select mtdd_sts_doc sts_doc,
		           count(mtdd_sts_doc) jml_sts_doc
		    from  mgt_transp_head, mgt_transp_detail_dn
		    where mtdd_mth_sys_id = mth_sys_id 
		    and mth_txn_code ||'-'|| mth_transp_no = :mgt_tp_provision_add.tpa_no
		    and nvl(mtdd_sts_doc, 'N') = 'N'
		    group by mtdd_sts_doc
		 	);
		exception
		  when no_data_found then
		  	v_jml := 0; 
		end; 

BEGIN
declare
 timer_id  timer;
begin
	if nvl(:mgt_tp_provision_add.tpa_amt_diff,0) != 0 then
		timer_id := create_timer('NEXT_REMARK',1,NO_REPEAT);
	end if;	  
end;

BEGIN
=declare
 timer_id  timer;
begin
	if :mgt_tp_provision_add.tpa_no is not null and :mgt_tp_provision_add.tpa_no not in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') then
		begin							
			select tp_no,
			       tp_dt,
			       tp_code,
			       tp_name,
			       tp_truck_type,
			       tp_cap,
			       tp_destination,
			       tp_pol_no,
			       tp_driver,
			       tp_qty,
			       tp_gross_qty,
			       tp_amt,
			       tp_oth_amt,
			       tp_match_status,
			       tp_jv_no,
			       tp_status,
			       sj_no,
			       gross,
			       main_acnt,
			       tp_due_date,
			       tp_name_code,
			       tp_tjv_no,
			       tp_jv_dt
			into 	 :mgt_tp_provision_add.tpa_no,
						 :mgt_tp_provision_add.tpa_dt,
			       :mgt_tp_provision_add.tpa_code,
			       :mgt_tp_provision_add.tpa_name,
			       :mgt_tp_provision_add.tpa_truck_type,
			       :mgt_tp_provision_add.tpa_cap,
			       :mgt_tp_provision_add.tpa_destination,
			       :mgt_tp_provision_add.tpa_pol_no,
			       :mgt_tp_provision_add.tpa_driver,
			       :mgt_tp_provision_add.tpa_qty,
			       :mgt_tp_provision_add.tpa_gross_qty,
			       :mgt_tp_provision_add.tpa_amt,
			       :mgt_tp_provision_add.tpa_oth_amt,
			       :mgt_tp_provision_add.tpa_match_status,
			       :mgt_tp_provision_add.tpa_jv_no,			       
			       :mgt_tp_provision_add.tpa_status,
			       :mgt_tp_provision_add.tpa_dn_no,
			       :mgt_tp_provision_add.gross,
			       :mgt_tp_provision_add.tpa_main_acnt,
			       :mgt_tp_provision_add.tpa_due_date,
			       :mgt_tp_provision_add.tpa_name_code,
			       :mgt_tp_provision_add.tpa_tjv_no,
			       :mgt_tp_provision_add.tpa_jv_dt
			from
			(        
			select b.ldn,
			       a.tp_no,
			       a.tp_dt,
			       a.tp_code,
			       a.tp_name,
			       a.tp_truck_type,
			       a.tp_cap,
			       a.tp_destination,
			       a.tp_pol_no,
			       a.tp_driver,
			       a.tp_qty,
			       a.tp_gross_qty,
			       a.tp_amt,
			       a.tp_oth_amt,
			       a.tp_match_status,
			       a.tp_jv_no,
			       a.tp_status,
			       decode(b.mth_txn_code, 'TPCHP', '208026', 'TPDN', '208027', null) main_acnt,
			       a.tp_due_date,
			       a.tp_name_code,
			       a.tp_tjv_no,
			       a.tp_jv_dt
			from mgt_tp_provision a,
			     (
			     select a.mth_sys_id,
			            a.mth_txn_code ||'-'|| a.mth_transp_no tpdn,
			            b.mtdd_dn_txn_code ||'-'|| b.mtdd_dn_no ldn,
			            a.mth_txn_code
			     from mgt_transp_head a,
			          mgt_transp_detail_dn b
			     where a.mth_sys_id = b.mtdd_mth_sys_id 
			    ) b
			where a.tp_no = b.tpdn
			and a.tp_tjv_no is not null			
			and a.tp_name_code = :mgt_tp_provision_bill_add.tpba_supp_code
			and not exists (select 'XXX' from ft_cur_trans_header where a.tp_jv_no = th_tran_code ||'-'|| th_doc_no and nvl(th_appr_status,0)!= 3)
			group by b.ldn,
			       a.tp_no,
			       a.tp_dt,
			       a.tp_code,
			       a.tp_name,
			       a.tp_truck_type,
			       a.tp_cap,
			       a.tp_destination,
			       a.tp_pol_no,
			       a.tp_driver,
			       a.tp_qty,
			       a.tp_gross_qty,
			       a.tp_amt,
			       a.tp_oth_amt,
			       a.tp_match_status,
			       a.tp_jv_no,
			       a.tp_status,
			       b.mth_txn_code,
			       a.tp_due_date,
			       a.tp_name_code,
			       a.tp_tjv_no,
			       a.tp_jv_dt    
			) a,
			(
			select gh_txn_code||'-'|| gh_no gr_no, gh_flex_01 sj_no, gh_flex_06 gross from ot_gr_head where gh_txn_code = 'CHPGRN'
			) b
			where a.ldn = b.gr_no (+)
			and a.ldn = :mgt_tp_provision_add.tpa_no
			order by tp_no desc,
			         tp_dt desc;
		exception

BEGIN
declare
 timer_id  timer;
begin
	if :mgt_tp_provision_add.tpa_dt is not null and :mgt_tp_provision_add.tpa_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') then
		timer_id := create_timer('NEXT_DT',1,NO_REPEAT);
	end if;	
end;

BEGIN
declare
 timer_id  timer;
begin
	if :mgt_tp_provision_add.tpa_amt_typing is not null then
		timer_id := create_timer('NEW_RECORD',1,NO_REPEAT);
	end if;	
end;

BEGIN
7declare
	v_sys_id number;
	v_cursor varchar2(100);
begin	
	v_cursor := :system.cursor_item;
	if v_cursor is not null then	
		begin
			select mth_sys_id
			into v_sys_id
			from mgt_transp_head
			where mth_txn_code ||'-'|| mth_transp_no = :mgt_tp_provision_add.tpa_no;
		end;

BEGIN
cbegin	
	delete_record;
	:system.message_level := 25;
	commit;
	:system.message_level := 0;			
end;

BEGIN
zbegin	
	clear_block(no_validate);
	hide_view('DN_CAN');
	hide_window('DN_WINDOW');
	go_block('mgt_tp_provision_add');
end;

BEGIN
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
declare
	v_check_tpdn number;
	v_check_tpchp number;
	v_temp_id number;
	v_jml number;
begin
	:system.message_level := 25;
	commit;
	:system.message_level := 0;		
	:parameter.p_voucher_dt := :mgt_tp_provision_bill_add.tpba_voucher_date_add;
	go_block('mgt_tp_provision_bill_add');	
  :parameter.p_sys_id := :mgt_tp_provision_bill_add.tpba_sys_id;
  if :parameter.p_voucher_dt is not null then
		begin			
			select jml_sts_doc
      into v_jml
			from
			(
				select  a.tpa_tpba_sys_id,
	              c.mtdd_sts_doc sts_doc,
	              count(c.mtdd_sts_doc) jml_sts_doc
				from mgt_tp_provision_add a,
				     mgt_transp_head b,
	           mgt_transp_detail_dn c
				where a.tpa_no = b.mth_txn_code ||'-'|| b.mth_transp_no
				and b.mth_sys_id = c.mtdd_mth_sys_id
				and a.tpa_no like 'TPDN%'
				and c.mtdd_sts_doc = 'N'
				and a.tpa_tpba_sys_id = :parameter.p_sys_id
				and a.tpa_tpba_sys_id is not null
				group by a.tpa_tpba_sys_id,
	               c.mtdd_sts_doc
	    );           
    exception
      when no_data_found then
         v_jml := 0; 
    end;    

      begin
          select count(tpa_tpba_sys_id)
          into v_check_tpdn
          from mgt_tp_provision_add
          where tpa_tpba_sys_id = :parameter.p_sys_id
          and tpa_no like 'TPDN%';
      end;		

      begin
          select count(tpa_tpba_sys_id)
          into v_check_tpchp
          from mgt_tp_provision_add
          where tpa_tpba_sys_id = :parameter.p_sys_id
          and tpa_no like 'TPCHP%';
      end;

BEGIN
if :button.check_all = 1 then
	begin
		go_block('mgt_tp_provision');
		first_record;
		loop 
	  	:mgt_tp_provision_add.checked	:= 1; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
	end;

	begin		
		go_block('mgt_tp_provision');
		first_record;
		loop 
	  	:mgt_tp_provision_add.checked	:= 0; 
	  	exit when :system.last_record = 'TRUE';
	  	next_record;
		end loop;	
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

begin		
	begin
		select nvl(max(substr(tpba_trx_no,10,10)),0)+1
		into v_no
		from mgt_tp_provision_bill_add
		where substr(tpba_trx_no,1,8)	= 'TBILLADD'
		and nvl(substr(tpba_trx_no,10,4), to_char(to_date(sysdate),'RRRR')) = to_char(to_date(sysdate),'RRRR');
	exception
		when no_data_found then
			v_no := 1;
	end;

begin	
	go_block('mgt_tp_provision_add');  
	clear_block(no_validate);
	go_block('mgt_tp_provision_bill_add');  
	clear_block(no_validate);
	vlov := show_lov('LOV_FIND');	
	if :parameter.p_sys_id is not null then	
		set_block_property('mgt_tp_provision_bill_add',default_where,' tpba_sys_id = '''||:parameter.p_sys_id||''' ');						
		go_block('mgt_tp_provision_bill_add');					
		execute_query(no_validate);			
	end if;	
	if :parameter.p_sys_id is null then	
		go_block('mgt_tp_provision_bill_add');
	end if;
	:parameter.p_flag 	:= 0;
end;

begin
	go_block('mgt_tp_provision_dn');		
	v_no := 1;
	for cd in c_data loop
		exit when c_data%notfound;
		:mgt_tp_provision_dn.no	  	   		:= v_no;
		:mgt_tp_provision_dn.tpd_dn_no 		:= cd.dn_no;
		:mgt_tp_provision_dn.tpd_qty   		:= cd.qty;
		:mgt_tp_provision_dn.tpd_sts_prs	:= cd.mtdd_sts_prs;
		:mgt_tp_provision_dn.tpd_sts_doc	:= cd.mtdd_sts_doc;
		v_no := v_no + 1; 
		next_record;		
	end loop;
	first_record;
end;

begin 
	pi_id := get_parameter_list('Para_List');
	if not id_null(pi_id) then
		destroy_parameter_list(pi_id);
	end if;	
	pi_id := create_parameter_list('Para_List');
	add_parameter(pi_id,'PARAMFORM',text_parameter,'NO'); 	
	add_parameter(pi_id,'P_TPB_SYS_ID',text_parameter,:mgt_tp_provision_bill_add.tpba_sys_id); 	
	run_product(
							reports,
							'\\192.168.0.1\Orionbin\BIN\TRNSP005.rep',
							synchronous,
							runtime,
							filesystem,
							pi_id,
							null
						 ); 
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
