-- ==============================================================
-- TRNSP004.fmb — SQL yang tertanam di dalam form (hasil ekstraksi)
-- Tanda kutip ganda pada literal ('' ) adalah artefak penyimpanan Forms.
-- ==============================================================

-- ---------- Trigger yang ada di form ----------
--     263 WHEN-BUTTON-PRESSED
--      39 ON-YYYY
--      17 KEY-HELP
--      16 KEY-NEXT-ITEM
--      15 POST-TEXT-ITEM
--       9 WHEN-VALIDATE-ITEM
--       8 POST-QUERY
--       6 WHEN-NEW-ITEM-INSTANCE
--       6 WHEN-CREATE-RECORD
--       6 PRE-UPDATE
--       6 PRE-INSERT
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
--       1 WHEN-BUTTON-PRES
--       1 ON-PRESSED

-- ---------- Statement SQL ----------
DELETE
;

DELETE FROM MGT_TP_PROVISION WHERE TP_TJV_NO IS NULL AND TP_NO = :b1 AND TP_SYS_ID = :b2 AND TP_TPB_SYS_ID = :b3
;

INSERT
;

INSERT INTO MGT_TP_PROVISION_DEL SELECT * FROM MGT_TP_PROVISION WHERE TP_TJV_NO IS NULL AND TP_NO = :b1 AND TP_SYS_ID = :b2 AND TP_TPB_SYS_ID = :b3
;

SELECT 1 FROM MGT_TP_PROVISION M WHERE M.TP_TPB_SYS_ID = :b1
;

SELECT A.MTDD_DN_TXN_CODE || ''-'' || A.MTDD_DN_NO DN_NO,A.MTDD_DN_QTY QTY,A.MTDD_SYS_ID,A.MTDD_STS_PRS,A.MTDD_STS_DOC,B.INVH_DT FROM MGT_TRANSP_DETAIL_DN A,(SELECT INVH_TXN_CODE || ''-'' || INVH_NO DN_NO,INVH_DT FROM OT_INVOICE_HEAD WHERE INVH_TXN_CODE IN ( ''LDN'',''PDN'' )) B WHERE A.MTDD_MTH_SYS_ID = :b1 AND A.MTDD_DN_TXN_CODE || ''-'' || A.MTDD_DN_NO = B.DN_NO ORDER BY A.MTDD_SYS_ID
;

SELECT COUNT(TP_ID) FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1
;

SELECT COUNT(TP_NO) FROM MGT_TP_PROVIS
;

SELECT COUNT(TP_TPB_SYS_ID) FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1
;

SELECT COUNT(TP_TPB_SYS_ID) FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1 AND TP_NO LIKE ''TPCHP%''
;

SELECT COUNT(TP_TPB_SYS_ID) FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1 AND TP_NO LIKE ''TPDN%''
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_01 = ''PPN 1%'' AND VSSV_CODE = :b1
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_01 = ''PPN'' AND VSSV_CODE = :b1
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_02 = ''NON PPH'' AND VSSV_CODE = :b1
;

SELECT COUNT(VSSV_CODE) FROM IM_VS_STATIC_VALUE WHERE VSSV_VS_CODE = ''TRANSPORTER'' AND NVL(VSSV_FRZ_FLAG_NUM,2) = 2 AND VSSV_FIELD_02 = ''PPH 0.5'' AND VSSV_CODE = :b1 AND TO_DATE(SYSDATE) <= TO_DATE(VSSV_FIELD_03,''DDMMYYYY'')
;

SELECT JML_STS_DOC FROM (SELECT A.TP_TPB_SYS_ID,C.MTDD_STS_DOC STS_DOC,COUNT(C.MTDD_STS_DOC) JML_STS_DOC FROM MGT_TP_PROVISION A,MGT_TRANSP_HEAD B,MGT_TRANSP_DETAIL_DN C WHERE A.TP_NO = B.MTH_TXN_CODE || ''-'' || B.MTH_TRANSP_NO AND B.MTH_SYS_ID = C.MTDD_MTH_SYS_ID AND A.TP_NO LIKE ''TPDN%'' AND C.MTDD_STS_DOC = ''N'' AND A.TP_TPB_SYS_ID = :b1 AND A.TP_TPB_SYS_ID IS NOT NULL GROUP BY A.TP_TPB_SYS_ID,C.MTDD_STS_DOC )
;

SELECT JML_STS_DOC FROM (SELECT MTDD_STS_DOC STS_DOC,COUNT(MTDD_STS_DOC) JML_STS_DOC FROM MGT_TRANSP_HEAD,MGT_TRANSP_DETAIL_DN WHERE MTDD_MTH_SYS_ID = MTH_SYS_ID AND MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO = :b1 AND NVL(MTDD_STS_DOC,''N'') = ''N'' GROUP BY MTDD_STS_DOC )
;

SELECT MAX(TP_DUE_DATE) DUE_DATE FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1
;

SELECT MAX(TP_ID) FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1
;

SELECT MGT_TP_PROVISION_BILL_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TP_PROVISION_ID_SEQ.NEXTVAL FROM DUAL
;

SELECT MGT_TP_PROVISION_SEQ.NEXTVAL FROM DUAL
;

SELECT MTH_SYS_ID FROM MGT_TRANSP_HEAD WHERE MTH_TXN_CODE || ''-'' || MTH_TRANSP_NO = :b1
;

SELECT NVL(MAX(SUBSTR(TPB_TRX_NO,7,10)),0) + 1 FROM MGT_TP_PROVISION_BILL WHERE SUBSTR(TPB_TRX_NO,1,5) = ''TBILL'' AND NVL(SUBSTR(TPB_TRX_NO,7,4),TO_CHAR(TO_DATE(SYSDATE),''RRRR'')) = TO_CHAR(TO_DATE(SYSDATE),''RRRR'')
;

SELECT NVL(SUPP_FLEX_06,''N'') GROSS_UP FROM OM_SUPPLIER WHERE NVL(SUPP_FRZ_FLAG_NUM,2) = 2 AND SUPP_CODE = :b1 GROUP BY NVL(SUPP_FLEX_06,''N'')
;

SELECT SUPP_NAME FROM OM_SUPPLIER WHERE SUPP_CODE = :b1 AND NVL(SUPP_FRZ_FLAG_NUM,2) = 2
;

SELECT TPB_BILL_AMT FROM MGT_TP_PROVISION_BILL WHERE TPB_SYS_ID = :b1
;

SELECT TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,TP_AMT,TP_OTH_AMT,TP_MATCH_STATUS,TP_JV_NO,TP_STATUS,SJ_NO,GROSS,MAIN_ACNT,TP_DUE_DATE,TP_NAME_CODE FROM (SELECT B.LDN,A.TP_NO,A.TP_DT,A.TP_CODE,A.TP_NAME,A.TP_TRUCK_TYPE,A.TP_CAP,A.TP_DESTINATION,A.TP_POL_NO,A.TP_DRIVER,A.TP_QTY,A.TP_GROSS_QTY,A.TP_AMT,A.TP_OTH_AMT,A.TP_MATCH_STATUS,A.TP_JV_NO,A.TP_STATUS,DECODE(B.MTH_TXN_CODE,''TPCHP'',''208026'',''TPDN'',''208027'', NULL ) MAIN_ACNT,A.TP_DUE_DATE,A.TP_NAME_CODE FROM MGT_TP_PROVISION A,MGT_TRANSP_TRX_VIEW B WHERE A.TP_NO = B.TPDN AND A.TP_TJV_NO IS NULL AND A.TP_NAME_CODE = :b1 AND NOT EXISTS (SELECT ''XXX'' FROM FT_CUR_TRANS_HEADER WHE
;

SELECT TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,TP_AMT,TP_OTH_AMT,TP_MATCH_STATUS,TP_JV_NO,TP_STATUS,SJ_NO,GROSS,MAIN_ACNT,TP_DUE_DATE,TP_NAME_CODE FROM (SELECT B.LDN,A.TP_NO,A.TP_DT,A.TP_CODE,A.TP_NAME,A.TP_TRUCK_TYPE,A.TP_CAP,A.TP_DESTINATION,A.TP_POL_NO,A.TP_DRIVER,A.TP_QTY,A.TP_GROSS_QTY,A.TP_AMT,A.TP_OTH_AMT,A.TP_MATCH_STATUS,A.TP_JV_NO,A.TP_STATUS,DECODE(B.MTH_TXN_CODE,''TPCHP'',''208026'',''TPDN'',''208027'', NULL ) MAIN_ACNT,A.TP_DUE_DATE,A.TP_NAME_CODE FROM MGT_TP_PROVISION A,MGT_TRANSP_TRX_VIEW B WHERE A.TP_NO = B.TPDN AND A.TP_TJV_NO IS NULL AND A.TP_NAME_CODE = :b1 AND NOT EXISTS (SELECT ''XXX'' FROM FT_CUR_TRANS_HEADER WHERE A.TP_JV_NO = TH_TRAN_CODE || ''-'' || TH_DOC_NO AND NVL(TH_APPR_STATUS,0) != 3 ) GROUP BY B.LDN,A.TP_NO,A.TP_DT,A.TP_CODE,A.TP_NAME,A.TP_TRUCK_TYPE,A.TP_CAP,A.TP_DESTINATION,A.TP_POL_NO,A.TP_DRIVER,A.TP_QTY,A.TP_GROSS_QTY,A.TP_AMT,A.TP_OTH_AMT,A.TP_MATCH_STATUS,A.TP_JV_NO,A.TP_STATUS,B.MTH_TXN_CODE,A.TP_DUE_DATE,A.TP_NAME_CODE ) A,(SELECT GH_TXN_CODE || ''-'' || GH_NO GR_NO,GH_FLEX_01 SJ_NO,GH_FLEX_06 GROSS FROM OT_GR_HEAD WHERE GH_TXN_CODE = ''CHPGRN'' ) B WHERE A.LDN = B.GR_NO (+) AND A.TP_NO = :b2 GROUP BY TP_NO,TP_DT,TP_CODE,TP_NAME,TP_TRUCK_TYPE,TP_CAP,TP_DESTINATION,TP_POL_NO,TP_DRIVER,TP_QTY,TP_GROSS_QTY,TP_AMT,TP_OTH_AMT,TP_MATCH_STATUS,TP_JV_NO,TP_STATUS,SJ_NO,GROSS,MAIN_ACNT,TP_DUE_DATE,TP_NAME_CODE ORDER BY TP_NO DESC,TP_DT DESC
;

SELECT TP_NO,TP_TOTAL_AMT,TP_AMT_TYPING,TP_AMT_DIFF,TP_DN_NO,MAX(TP_SYS_ID) TP_SYS_ID FROM MGT_TP_PROVISION WHERE TP_TPB_SYS_ID = :b1 AND TP_TJV_NO IS NULL GROUP BY TP_NO,TP_TOTAL_AMT,TP_AMT_TYPING,TP_AMT_DIFF,TP_DN_NO ORDER BY TP_SYS_ID
;

UPDATE MGT_TP_PROVISION SET TP_TPB_SYS_ID=:b1,TP_TOTAL_AMT=:b2,TP_AMT_TYPING=:b3,TP_AMT_DIFF=:b4,TP_UP_DT=SYSDATE,TP_UP_UID=:b5,TP_DN_NO=:b6 WHERE TP_NO = :b7 AND TP_JV_NO IS NOT NULL AND TP_TJV_NO IS NULL
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
  if lv_errcod in (40401,41050,40735,40501,40212,41011, 40102, 41816) then		
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
		go_item('mgt_tp_provision.tp_amt_typing');
	end if;	
	if expired_timer = 'NEXT_ITEM_DT' then	
		go_item('mgt_tp_provision.tp_dt');
	end if;	
	if expired_timer = 'NEXT_DT' then	
		go_item('mgt_tp_provision.tp_amt_typing');
	end if;	
	if expired_timer = 'NEXT_REMARK' then	
		go_item('mgt_tp_provision.tp_remark');
	end if;	
	if expired_timer = 'NEW_RECORD' then	
		next_record;
		go_item('mgt_tp_provision.tp_no');
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
	v_pph_2 number;
	v_gross_up varchar2(1);	
begin
	begin
	  select nvl(supp_flex_06, 'N') gross_up
	  into v_gross_up
	  from om_supplier
	  where nvl(supp_frz_flag_num,2) = 2                
	  and supp_code = :mgt_tp_provision_bill.tpb_supp_code
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
		and vssv_code = :mgt_tp_provision_bill.tpb_supp_code;
	end;

	begin
		select count(vssv_code)
		into v_pph_1
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORTER'
		and nvl(vssv_frz_flag_num, 2) = 2
		and vssv_field_02 = 'NON PPH'
		and vssv_code = :mgt_tp_provision_bill.tpb_supp_code;
	end;

	begin
		select count(vssv_code)
		into v_pph_2
		from im_vs_static_value 
		where vssv_vs_code = 'TRANSPORTER'
		and nvl(vssv_frz_flag_num, 2) = 2
		and vssv_field_02 = 'PPH 0.5'
		and vssv_code = :mgt_tp_provision_bill.tpb_supp_code
		and to_date(sysdate) <= to_date(vssv_field_03,'DDMMYYYY');  --Added by Aam on 05 Jul 2024 Expired Date for PPh 0.5%;
	end;

	begin
		select tpb_bill_amt
		into :mgt_tp_provision_bill.tpb_bill_amt
		from mgt_tp_provision_bill
		where tpb_sys_id = :parameter.p_sys_id;
	exception
		when others then
			:mgt_tp_provision_bill.tpb_bill_amt := 0;
	end;

BEGIN
:declare
  lv_errtype varchar(3) := message_type;
  lv_errcod number := message_code;
  lv_errtxt varchar(80) := message_text;
begin
  if lv_errcod in (40401,41050,40735,40501,40212,41011, 40102, 41816) then		
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
declare
	v_type varchar2(10);
	v_bulan varchar2(5);
begin	
	if :mgt_tp_provision_bill.tpb_voucher_date is null then
		if nvl(:mgt_tp_provision_bill.tpb_bill_amt,0) != 0 then
			:mgt_tp_provision_bill.tpb_total := nvl(:mgt_tp_provision_bill.tpb_bill_amt,0) + nvl(:mgt_tp_provision_bill.tpb_ppn_amt,0) - nvl(:mgt_tp_provision_bill.tpb_pph_amt,0);	
		end if;	
		if :mgt_tp_provision_bill.tpb_due_date is null then
			begin
				select max(tp_due_date) due_date
				into :mgt_tp_provision_bill.tpb_due_date
				from mgt_tp_provision
				where tp_tpb_sys_id = :mgt_tp_provision_bill.tpb_sys_id; 
			exception
				when others then
					:mgt_tp_provision_bill.tpb_due_date := null;
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
  -- Begin MGT_TP_PROVISION detail program section
  --
  IF ( (:MGT_TP_PROVISION_BILL.TPB_SYS_ID is not null) ) THEN   
    rel_id := Find_Relation('MGT_TP_PROVISION_BILL.MGT_TP_PROVISIO_MGT_TP_PROVISI');   
    Query_Master_Details(rel_id, 'MGT_TP_PROVISION');   
  END IF;
  --
  -- End MGT_TP_PROVISION detail program section
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
  -- Begin MGT_TP_PROVISION detail declare section
  --
  CURSOR MGT_TP_PROVISION_cur IS      
    SELECT 1 FROM MGT_TP_PROVISION M     
    WHERE M.TP_TPB_SYS_ID = :MGT_TP_PROVISION_BILL.TPB_SYS_ID;
  --
  -- End MGT_TP_PROVISION detail declare section
  --
-- End default relation declare section
-- Begin default relation program section
BEGIN
  --
  -- Begin MGT_TP_PROVISION detail program section
  --
  OPEN MGT_TP_PROVISION_cur;     
  FETCH MGT_TP_PROVISION_cur INTO Dummy_Define;     
  IF ( MGT_TP_PROVISION_cur%found ) THEN     
    Message('Cannot delete master record when matching detail records exist.');     
    CLOSE MGT_TP_PROVISION_cur;     
    RAISE Form_Trigger_Failure;     
  END IF;
  CLOSE MGT_TP_PROVISION_cur;
  --
  -- End MGT_TP_PROVISION detail program section
  --
END;

BEGIN
5declare
	v_id	number;
begin	
	begin
		select mgt_tp_provision_bill_seq.nextval into v_id from dual;
	end;

BEGIN
xbegin	
	:mgt_tp_provision_bill.tpb_up_dt  	:= sysdate;
	:mgt_tp_provision_bill.tpb_up_uid 	:= :parameter.m_user_id;
end;

BEGIN
declare
	v_pph_1 number;
begin	
	if :mgt_tp_provision_bill.tpb_supp_code is not null then
		begin
			select supp_name
			into :mgt_tp_provision_bill.tpb_supp_name
			from om_supplier
			where supp_code = :mgt_tp_provision_bill.tpb_supp_code
			and nvl(supp_frz_flag_num, 2) = 2;
		end;	

		begin
			select count(vssv_code)
			into v_pph_1
			from im_vs_static_value 
			where vssv_vs_code = 'TRANSPORTER'
			and nvl(vssv_frz_flag_num, 2) = 2
			and vssv_field_02 = 'NON PPH'
			and vssv_code = :mgt_tp_provision_bill.tpb_supp_code;
		end;

BEGIN
go_item('mgt_tp_provision_bill.tpb_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill.tpb_bill_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill.tpb_fp_dt');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD-MON-YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
declare	
	v_ppn_1 number;		
begin
	if :mgt_tp_provision_bill.tpb_voucher_date is null then		
		begin
			select count(vssv_code)
			into v_ppn_1
			from im_vs_static_value 
			where vssv_vs_code = 'TRANSPORTER'
			and nvl(vssv_frz_flag_num, 2) = 2
			and vssv_field_01 = 'PPN'
			and vssv_code = :mgt_tp_provision_bill.tpb_supp_code;		
		end;

BEGIN
Hdeclare
	v_gross_up varchar2(1);
	v_pph_1 number;
	v_pph_2 number;
begin
	if :mgt_tp_provision_bill.tpb_voucher_date is null then	
		begin
		  select nvl(supp_flex_06, 'N') gross_up
		  into v_gross_up
		  from om_supplier
		  where nvl(supp_frz_flag_num,2) = 2                
		  and supp_code = :mgt_tp_provision_bill.tpb_supp_code
		  group by nvl(supp_flex_06, 'N');
		exception
	   	when others then
	    	v_gross_up := 'N';        
		end;

		begin
			select count(vssv_code)
			into v_pph_2
			from im_vs_static_value 
			where vssv_vs_code = 'TRANSPORTER'
			and nvl(vssv_frz_flag_num, 2) = 2
			and vssv_field_02 = 'PPH 0.5'
			and vssv_code = :mgt_tp_provision_bill.tpb_supp_code
			and to_date(sysdate) <= to_date(vssv_field_03,'DDMMYYYY');  --Added by Aam on 05 Jul 2024 Expired Date for PPh 0.5%;
		end;

BEGIN
go_item('mgt_tp_provision_bill.tpb_receipt_date');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
go_item('mgt_tp_provision_bill.tpb_due_date');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
E:system.message_level := 25;
commit;
:system.message_level := 0;			
declare
cursor c_data (p_sysid number) is 
select tp_no,
	     tp_total_amt,
	     tp_amt_typing,
	     tp_amt_diff,
	     tp_dn_no, --Added by Aam on 18 Apr 2022
	     max(tp_sys_id) tp_sys_id
from mgt_tp_provision
where tp_tpb_sys_id = p_sysid
and tp_tjv_no is null
group by tp_no,
	     tp_total_amt,
	     tp_amt_typing,
	     tp_amt_diff,
	     tp_dn_no
order by tp_sys_id;
	v_check number;
begin	
	if nvl(:mgt_tp_provision_bill.tpb_status, 'XXX') != 'GENERATE' then
		:system.message_level := 25;
		commit;
		:system.message_level := 0;			
		begin
			select count(tp_tpb_sys_id) 
			into v_check
			from mgt_tp_provision 
			where tp_tpb_sys_id = :parameter.p_sys_id;
		end;

					begin
						update mgt_tp_provision
						set tp_tpb_sys_id = :parameter.p_sys_id,
						    tp_total_amt	= cd.tp_total_amt,
			     			tp_amt_typing = cd.tp_amt_typing,
			     			tp_amt_diff   = cd.tp_amt_diff,
			     			tp_up_dt			= sysdate,
						    tp_up_uid			= :parameter.m_user_id,
						    tp_dn_no      = cd.tp_dn_no
						where tp_no		    = cd.tp_no
						and tp_jv_no is not null     
						and tp_tjv_no is null;
					end;

					begin
						select count(tp_no)
						into v_check
						from mgt_tp_provision 
						where tp_no 			= cd.tp_no
						and tp_tpb_sys_id = :parameter.p_sys_id;						
					end;				

						begin
							insert into mgt_tp_provision_del
							select * from mgt_tp_provision 
							where tp_tjv_no is null 
							and tp_no 	  = cd.tp_no      
							and tp_sys_id = cd.tp_sys_id
							and tp_tpb_sys_id = :parameter.p_sys_id;
						end;

						begin
							delete from mgt_tp_provision 
							where tp_tjv_no is null
							and tp_no 	  = cd.tp_no     
							and tp_sys_id = cd.tp_sys_id
							and tp_tpb_sys_id = :parameter.p_sys_id;
						end;					

BEGIN
go_item('mgt_tp_provision_bill.tpb_voucher_date');
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
clear_block(no_validate);
:parameter.p_sys_id := null;
:parameter.p_temp_id := null; 
:parameter.p_del_flag := null;   
pkg.new_no;
END;

BEGIN
begin
	clear_form(no_validate);
	Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');  
	:parameter.p_del_flag := null;     		
end;

BEGIN
clear_form(no_validate);
go_block('mgt_tp_provision_bill');	
Read_Image_File('\\192.168.0.1\Orionbin\ALTHARABIN\Icons\LOGO.BMP', 'BMP', 'IMAGE.IMAGE_1');  
:parameter.p_del_flag := null;     	
pkg.find;
END;

BEGIN
Sdelete_record;
:system.message_level := 25;
commit;
:system.message_level := 0;		
END;

BEGIN
declare
	v_id	number;
	v_check number;
begin	
	begin
		select mgt_tp_provision_seq.nextval into v_id from dual;
	end;	

	begin
		select count(tp_id)
		into v_check
		from mgt_tp_provision
		where tp_tpb_sys_id = :parameter.p_sys_id;	
	end;

		begin
			select max(tp_id)
			into :parameter.p_temp_id
			from mgt_tp_provision
			where tp_tpb_sys_id = :parameter.p_sys_id;			
		end;	

BEGIN
wif :mgt_tp_provision_bill.tpb_sys_id is not null then
	pkg.print;
else
	alert_message('Please Save before !');
end if;	
END;

BEGIN
0declare
	v_check number;
begin	
	:mgt_tp_provision.tp_up_dt  		:= sysdate;
	:mgt_tp_provision.tp_up_uid 		:= :parameter.m_user_id;
	if :parameter.p_del_flag is null then
		--message('Masuk');
		:mgt_tp_provision.tp_tpb_sys_id := nvl(:mgt_tp_provision_bill.tpb_sys_id,:parameter.p_sys_id);	
	end if;	
end;

BEGIN
declare
	v_jml number;
	v_cursor varchar2(150);
begin
	v_cursor := :system.cursor_item;
	if nvl(:mgt_tp_provision.tp_amt,0) != 0 then
		:mgt_tp_provision.tp_total_amt := round(nvl(:mgt_tp_provision.tp_amt,0) + nvl(:mgt_tp_provision.tp_oth_amt,0));		
	end if;	
	if nvl(:mgt_tp_provision.tp_total_amt,0) != 0 then
		:mgt_tp_provision.tp_amt_diff := round(nvl(:mgt_tp_provision.tp_total_amt,0) - nvl(:mgt_tp_provision.tp_amt_typing,0));		
	end if;
	if nvl(:mgt_tp_provision.tp_amt_typing,0) != 0 and :mgt_tp_provision.tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') then
		:mgt_tp_provision.tp_amt_diff := round(nvl(:mgt_tp_provision.tp_total_amt,0) - nvl(:mgt_tp_provision.tp_amt_typing,0));		
	end if;
	if :mgt_tp_provision.tp_no is not null then
		begin
		  select jml_sts_doc
		  into v_jml
			from
			(
				select mtdd_sts_doc sts_doc,
		           count(mtdd_sts_doc) jml_sts_doc
		    from  mgt_transp_head, mgt_transp_detail_dn
		    where mtdd_mth_sys_id = mth_sys_id 
		    and mth_txn_code ||'-'|| mth_transp_no = :mgt_tp_provision.tp_no
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
	if nvl(:mgt_tp_provision.tp_amt_diff,0) != 0 then
		timer_id := create_timer('NEXT_REMARK',1,NO_REPEAT);
	end if;	  
end;

BEGIN
declare
 timer_id  timer;
begin
	if :mgt_tp_provision.tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') and :mgt_tp_provision.tp_dt is not null then			
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
			       tp_name_code
			into 	 :mgt_tp_provision.tp_no,
						 :mgt_tp_provision.tp_dt,
			       :mgt_tp_provision.tp_code,
			       :mgt_tp_provision.tp_name,
			       :mgt_tp_provision.tp_truck_type,
			       :mgt_tp_provision.tp_cap,
			       :mgt_tp_provision.tp_destination,
			       :mgt_tp_provision.tp_pol_no,
			       :mgt_tp_provision.tp_driver,
			       :mgt_tp_provision.tp_qty,
			       :mgt_tp_provision.tp_gross_qty,
			       :mgt_tp_provision.tp_amt,
			       :mgt_tp_provision.tp_oth_amt,
			       :mgt_tp_provision.tp_match_status,
			       :mgt_tp_provision.tp_jv_no,
			       :mgt_tp_provision.tp_status,
			       :mgt_tp_provision.tp_dn_no,
			       :mgt_tp_provision.gross,
			       :mgt_tp_provision.tp_main_acnt,
			       :mgt_tp_provision.tp_due_date,
			       :mgt_tp_provision.tp_name_code
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
			       a.tp_name_code
			from mgt_tp_provision a,
			     mgt_transp_trx_view b
			where a.tp_no = b.tpdn
			and a.tp_tjv_no is null			
			and a.tp_name_code = :mgt_tp_provision_bill.tpb_supp_code
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
			       a.tp_name_code    
			) a,
			(
			select gh_txn_code||'-'|| gh_no gr_no, gh_flex_01 sj_no, gh_flex_06 gross from ot_gr_head where gh_txn_code = 'CHPGRN'
			) b
			where a.ldn = b.gr_no (+)
			and a.tp_no = :parameter.tp_no
			group by tp_no,
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
			       tp_name_code
			into 	 :mgt_tp_provision.tp_no,
						 :mgt_tp_provision.tp_dt,
			       :mgt_tp_provision.tp_code,
			       :mgt_tp_provision.tp_name,
			       :mgt_tp_provision.tp_truck_type,
			       :mgt_tp_provision.tp_cap,
			       :mgt_tp_provision.tp_destination,
			       :mgt_tp_provision.tp_pol_no,
			       :mgt_tp_provision.tp_driver,
			       :mgt_tp_provision.tp_qty,
			       :mgt_tp_provision.tp_gross_qty,
			       :mgt_tp_provision.tp_amt,
			       :mgt_tp_provision.tp_oth_amt,
			       :mgt_tp_provision.tp_match_status,
			       :mgt_tp_provision.tp_jv_no,
			       :mgt_tp_provision.tp_status,
			       :mgt_tp_provision.tp_dn_no,
			       :mgt_tp_provision.gross,
			       :mgt_tp_provision.tp_main_acnt,
			       :mgt_tp_provision.tp_due_date,
			       :mgt_tp_provision.tp_name_code
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
			       a.tp_name_code
			from mgt_tp_provision a,
			     mgt_transp_trx_view b
			where a.tp_no = b.tpdn
			and a.tp_tjv_no is null			
			and a.tp_name_code = :mgt_tp_provision_bill.tpb_supp_code
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
			       a.tp_name_code    
			) a,
			(
			select gh_txn_code||'-'|| gh_no gr_no, gh_flex_01 sj_no, gh_flex_06 gross from ot_gr_head where gh_txn_code = 'CHPGRN'
			) b
			where a.ldn = b.gr_no (+)
			and a.ldn = :mgt_tp_provision.tp_no			
			group by tp_no,
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
				       tp_name_code	
			order by tp_no desc,
			         tp_dt desc;
		exception

declare
 timer_id  timer;
begin
	--if :mgt_tp_provision.tp_no is not null and :mgt_tp_provision.tp_no not in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') then --remark by Aam on 20 Feb 2023 
	if :mgt_tp_provision.tp_no is not null then	
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
			       tp_name_code
			into 	 :mgt_tp_provision.tp_no,
						 :mgt_tp_provision.tp_dt,
			       :mgt_tp_provision.tp_code,
			       :mgt_tp_provision.tp_name,
			       :mgt_tp_provision.tp_truck_type,
			       :mgt_tp_provision.tp_cap,
			       :mgt_tp_provision.tp_destination,
			       :mgt_tp_provision.tp_pol_no,
			       :mgt_tp_provision.tp_driver,
			       :mgt_tp_provision.tp_qty,
			       :mgt_tp_provision.tp_gross_qty,
			       :mgt_tp_provision.tp_amt,
			       :mgt_tp_provision.tp_oth_amt,
			       :mgt_tp_provision.tp_match_status,
			       :mgt_tp_provision.tp_jv_no,
			       :mgt_tp_provision.tp_status,
			       :mgt_tp_provision.tp_dn_no,
			       :mgt_tp_provision.gross,
			       :mgt_tp_provision.tp_main_acnt,
			       :mgt_tp_provision.tp_due_date,
			       :mgt_tp_provision.tp_name_code
			from
			(        			
			--select vssv_code ldn,
			  --     vssv_name tp_no,
			    --   null      tp_dt,
			      -- null      tp_code,
			       --null      tp_name,
			       --null      tp_truck_type,
			       --null      tp_cap,
			       --null      tp_destination,
			       --null      tp_pol_no,
			       --null      tp_driver,
			       --null      tp_qty,
			       --null      tp_gross_qty,
			       --null      tp_amt,
			       --null      tp_oth_amt,
			       --null      tp_match_status,
			       --'NON TPDN/TPCHP'     tp_jv_no,
			       --'Unposted'     tp_status,
			       --vssv_field_01 main_acnt     
			--from im_vs_static_value 
			--where vssv_vs_code = 'TRANSPORT' 
			--and nvl(vssv_frz_flag_num,2) = 2
			--union all			
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
			       decode(b.mth_txn_code, 'TPCHP', '208026', 'TPDN', '208027', nul
l) main_acnt,
			       a.tp_due_date,
			       a.tp_name_code
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
			and a.tp_tjv_no is null			
			and a.tp_name_code = :mgt_tp_provision_bill.tpb_supp_code
			--and not exists (select 'xxx' from mgt_tp_provision where a.tp_no = tp_no and tp_tpb_sys_id is not null)
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

BEGIN
declare
 timer_id  timer;
begin
	if :mgt_tp_provision.tp_dt is not null and :mgt_tp_provision.tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') then
		timer_id := create_timer('NEXT_DT',1,NO_REPEAT);
	end if;	
end;

BEGIN
declare
 timer_id  timer;
begin
	--Remark by Aam on 22 Mar 2021
	if :mgt_tp_provision.tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS') and nvl(:mgt_tp_provision.tp_amt_typing,0) is not null then
		:mgt_tp_provision.tp_total_amt := :mgt_tp_provision.tp_amt_typing;
	end if;	
	if :mgt_tp_provision.tp_amt_typing is not null then
		timer_id := create_timer('NEW_RECORD',1,NO_REPEAT);
	end if;	
end;

BEGIN
2declare
	v_sys_id number;
	v_cursor varchar2(100);
begin	
	v_cursor := :system.cursor_item;
	if v_cursor is not null then	
		begin
			select mth_sys_id
			into v_sys_id
			from mgt_transp_head
			where mth_txn_code ||'-'|| mth_transp_no = :mgt_tp_provision.tp_no;
		end;

BEGIN
 --if :mgt_tp_provision.tp_status = 'Unposted' and :mgt_tp_provision.tp_jv_no is null then
if nvl(:mgt_tp_provision.tp_status,'NA') = 'Posted' and :mgt_tp_provision.tp_jv_no is not null then
	pkg.delete_item;
else
	alert_message('Data Tidak bisa Di Hapus, TJV Sudah di Buat !!');
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
vbegin	
	clear_block(no_validate);
	hide_view('DN_CAN');
	hide_window('DN_WINDOW');
	go_block('mgt_tp_provision');
end;

BEGIN
copy(to_char(nvl(ltrim(rtrim(name_in(:system.cursor_item))),sysdate),'DD/MM/YYYY'),'calendar.user_dt');	
calendar_populate_month_pro(:calendar.user_dt);
END;

BEGIN
{begin	
	clear_block(no_validate);
	hide_view('MAS_PRINT');
	hide_window('MAS_PRINT');
	go_block('MGT_TRX_STORE_HEAD');
end;

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
	:parameter.p_voucher_dt := :mgt_tp_provision_bill.tpb_voucher_date;
	go_block('mgt_tp_provision_bill');	
  :parameter.p_sys_id := :mgt_tp_provision_bill.tpb_sys_id;		            
	if :parameter.p_voucher_dt is not null then
		if nvl(:mgt_tp_provision_bill.tpb_ppn,0) != 0 then
			if :mgt_tp_provision_bill.tpb_fp_no is null or :mgt_tp_provision_bill.tpb_fp_dt is null then  			            			
				alert_message('Please insert No. Faktur Pajak atau Tanggal Faktur Pajak !!');			  
				:parameter.run_generate := 0;
			else
				:parameter.run_generate := 1;
			end if;
		elsif nvl(:mgt_tp_provision_bill.tpb_ppn,0) = 0 then
				:parameter.run_generate := 1;
		end if;
		if :parameter.run_generate = 1 then
			begin			
				select jml_sts_doc
	      into v_jml
				from
				(
					select  a.tp_tpb_sys_id,
		                    c.mtdd_sts_doc sts_doc,
		                    count(c.mtdd_sts_doc) jml_sts_doc
					from mgt_tp_provision a,
					     mgt_transp_head b,
		           mgt_transp_detail_dn c
					where a.tp_no = b.mth_txn_code ||'-'|| b.mth_transp_no
					and b.mth_sys_id = c.mtdd_mth_sys_id
					and a.tp_no like 'TPDN%'
					and c.mtdd_sts_doc = 'N'
					and a.tp_tpb_sys_id = :parameter.p_sys_id
					and a.tp_tpb_sys_id is not null
					group by a.tp_tpb_sys_id,
		               c.mtdd_sts_doc
		    );           
	    exception
	      when no_data_found then
	         v_jml := 0; 
	    end;    

	      begin
	          select count(tp_tpb_sys_id)
	          into v_check_tpdn
	          from mgt_tp_provision
	          where tp_tpb_sys_id = :parameter.p_sys_id
	          and tp_no like 'TPDN%';
	      end;		

	      begin
	          select count(tp_tpb_sys_id)
	          into v_check_tpchp
	          from mgt_tp_provision
	          where tp_tpb_sys_id = :parameter.p_sys_id
	          and tp_no like 'TPCHP%';
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
