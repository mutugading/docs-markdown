-- ==============================================================
-- TRNSP003.rdf — query & struktur report (hasil ekstraksi)
-- ==============================================================

-- ---------- Tabel/view yang dibaca ----------
-- FROM AND
-- FROM DUAL
-- FROM MGT_TRANSP_VIEW

-- ---------- Judul & label kolom ----------
-- AFTERPFORM
-- B(F0J
-- B0C0D
-- BLUE
-- BpC R
-- BpC V
-- CUSTOMER
-- Capasity 
-- Code
-- Cost Master Batch
-- Cost Transaction
-- Courier New
-- Customer
-- DATE        : 
-- DD/MM/RR
-- DESTINATION
-- DESTINATION : 
-- DN No
-- DN No.
-- DUAL
-- Data
-- Date
-- Default
-- Destination
-- Destination Format
-- Destination Name
-- Destination Type
-- Dn No
-- E0F0G
-- Enter values for the parameters
-- FORMULA
-- Fields
-- FinishedQuery
-- Finreppl
-- Fonts
-- GADING TEKSTIL
-- GREEN
-- Grade 1
-- Grade 2
-- Gross
-- HyperlinksWW
-- Item Code
-- Item Desc
-- Item Description
-- JpK R
-- JpK T
-- JpK V
-- JpK X
-- JpK Z
-- Lutransporter
-- MGTDAT
-- MGTDATxu
-- MatrixCell
-- NNNGNNNGNNNGNNNGNN0D00
-- No Police
-- No. Police
-- No. Transaction
-- Number of Copies
-- Output Mode
-- PACKAGE flag IS
-- PBW2
-- PT. MUTU 
-- PT. MUTU GADING TEKSTIL
-- PUBLIC
-- Qty Gross
-- Qty Nett
-- Quantity
-- Quantity 
-- Quantity Gross
-- Quantity Nett
-- REPORT
-- RIBS
-- RPT2XLS
-- Rate
-- Report Parameters
-- Run in Background
-- Screen
-- Sections
-- Shade Name
-- Show Print Job Dialog
-- TOTA
-- TRANSPORTER
-- TRANSPORTER : 
-- TRANSPORTER REGISTER
-- TRNSP003
-- TYPE
-- Tahoma
-- Temp
-- Templates
-- Total
-- Total Rate
-- Transaction
-- Transp
-- Transp Code
-- Transp Dt
-- Transp No
-- Transporter
-- Transporter Code
-- Truck
-- Truck Cap
-- Truck Capasity
-- Truck Qty
-- Truck Quantity
-- Truck Rate
-- Truck Type
-- Type
-- YPRINTJOB
-- ZMODE
-- a(bpfpf8f0h8j8l
-- aDESFORMAT
-- bDESNAME
-- black
-- blue
-- brcrz
-- cDESTYPE
-- class attributes
-- cyan
-- d0e0f
-- darkblue
-- darkcyan
-- darkgray
-- darkmagenta
-- darkred
-- darkyellow
-- de Code
-- dflt
-- f0g0h
-- formula
-- gray
-- gray36
-- gray40
-- gray44
-- gray48
-- gray52z
-- gray56p
-- gray60ffffff
-- gray68Q
-- gray72G
-- gray80333333
-- gray84(
-- gray88
-- gray92
-- gray96
-- green
-- h(nprpr8r0t8v8x
-- h0i0j0k
-- j(npr
-- j(nprpr8r0t8v8x
-- k0l0m0n0o0p0q
-- k0l8n0p8r8t
-- ls.pu
-- magenta
-- menu
-- n(rpv
-- n(rpv0
-- npopo
-- npopw
-- o0p0q0r0s0t0u
-- oppp
-- p p(p0
-- p(p1
-- p)p3
-- p0q0r0sptpv0v
-- pfunction Formula return Char is
-- r0g0b0
-- r0g0b100
-- r0g0b50
-- r0g0b75
-- r0g0b88
-- r0g25b0
-- r0g25b50
-- r0g25b75
-- r0g25b88
-- r0g50b0
-- r0g50b50
-- r0g50b75
-- r0g50b88
-- r0g75b0
-- r0s0t
-- r0s0t0upvpv0v
-- r100g0b0
-- r100g0b100
-- r100g25b100
-- r100g25b50
-- r100g25b75
-- r100g25b88
-- r100g50b100
-- r100g50b50
-- r100g50b75
-- r100g50b88
-- r25g0b0
-- r50g0b0
-- r50g0b50
-- r50g0b75
-- r50g0b88
-- r50g25b0
-- r50g25b100
-- r50g50b0
-- r50g50b100
-- r50g75b0
-- r75g0b0
-- r75g0b50
-- r75g0b75
-- r75g0b88
-- r75g25b0
-- r75g25b100
-- r75g50b0
-- r75g50b100
-- r75g75b0
-- r88g0b0
-- r88g0b50
-- r88g0b75
-- r88g0b88
-- r88g25b0
-- r88g25b100
-- r88g50b0
-- r88g50b100
-- r88g75b0
-- rpsps
-- rpspu
-- rpspw
-- rter
-- rwbhre
-- rwbwcm
-- rwbwcn
-- rwbwdi
-- rwbwdo
-- rwbwet
-- rwbwfe
-- rwbwfi1
-- rwbwgi
-- rwbwmf
-- rwbwmr
-- rwbwol
-- rwbwrg
-- rwbwsr
-- rwbwtp
-- rwbwtr
-- rwbwvpul
-- rwlb
-- rwlo
-- s0t0u0v0w0x0ypz(
-- sptptp
-- string
-- struct types
-- t0u0v0wpxp
-- t0u0v0wpxpz0z
-- tk2 reserved
-- tk2 uiStrings
-- ttransporter
-- type
-- v0w0x
-- v0w0x0ypzp
-- v0w0x0ypzpz0z
-- vpwpw
-- vpwpz
-- white
-- window
-- wizard
-- yellow
-- zdestination

-- ---------- Query ----------
			select to_char(to_date(:rep_value_1,'DD/MM/RRRR'),'DD MON RRRR')||' To '||to_char(to_date(:rep_value_2,'DD/MM/RRRR'),'DD MON RRRR')
;

SELECT TO_CHAR(TO_DATE(:b1,''DD/MM/RRRR''),''DD MON RRRR'') || '' To '' || TO_CHAR(TO_DATE(:b2,''DD/MM/RRRR''),''DD MON RRRR'') FROM DUAL
;

select TRANSP_NO, 
;

-- ---------- PL/SQL (format trigger / AfterReport) ----------

function AfterReport return boolean is
begin
	if :M_DEST_TYPE = 'E' then
     RPT2XLS.run;  
  end if;
  return (TRUE);
end;

function M_1FormatTrigger return boolean is
	m_page_num number;
begin
	srw.get_page_num(m_page_num);
	if m_page_num = 1 then
  	rpt2xls.put_cell(1, 'PT. MUTU GADING TEKSTIL', FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'TRANSPORTER REGISTER', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'DATE        : '||:cf_date, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'TRANSPORTER : '||:cf_transp, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'DESTINATION : '||:cp_dest, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.new_line;  		
  	rpt2xls.put_cell(1, 'No. Transaction', FontStyle => RPT2XLS.BOLD);
  	rpt2xls.put_cell(2, 'Date', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(3, 'No. Police', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(4, 'Transporter', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(5, 'Truck Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Transporter Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'Truck Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Truck Quantity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(11, 'Truck Rate', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(12, 'Total Rate', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(13, 'Customer', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'DN No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Item Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Item Description', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(17, 'Grade 1', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'Grade 2', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'Quantity Gross', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Quantity Nett', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
  end if;
  return (true);
end;

function M_1FormatTrigger return boolean is
	m_page_num number;
begin
	srw.get_page_num(m_page_num);
	if m_page_num = 1 then
  	rpt2xls.put_cell(1, 'PT. MUTU GADING TEKSTIL', FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'TRANSPORTER REGISTER', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'DATE        : '||:cf_date, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'TRANSPORTER : '||:cf_transp, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.put_cell(1, 'DESTINATION : '||:cp_dest, FontColor => 3, FontStyle => RPT2XLS.BOLD);
  	rpt2xls.new_line;
  	rpt2xls.new_line;  		
  	rpt2xls.put_cell(1, 'No. Transaction', FontStyle => RPT2XLS.BOLD);
  	rpt2xls.put_cell(2, 'Date', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(3, 'No. Police', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(4, 'Transporter', FontStyle => RPT2XLS.BOLD);
 		rpt2xls.put_cell(5, 'Truck Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Transporter Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'Truck Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Truck Quantity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(11, 'Truck Rate', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(12, 'Total Rate', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(13, 'Customer', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'DN No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Item Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Item Description', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(17, 'Grade 1', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'Grade 2', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'Quantity Gross', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Quantity Nett', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
  end if;
  return (true);
end;

function AfterPForm return boolean is
begin
	if :rep_value_1 is not null and :rep_value_2 is not null then
   	:p_date_from	:= to_date(:rep_value_1,'DD/MM/RR');
    :p_date_to	  := to_date(:rep_value_2,'DD/MM/RR');
	end if;
	if :rep_value_3 is not null then
		:l_transporter  := ' and transporter = :rep_value_3 ';
	else
		:l_transporter  := '';
	end if;	
	if :rep_value_4 is not null then
		:l_destination  := ' and destination = :rep_value_4 ';
	else
		:l_destination  := '';
  end if;	  
  return (true);
end;

function F_transp_noFormatTrigger return boolean is
begin	
	if flag.var_6 != :cs_no then
	  rpt2xls.put_cell(1, :transp_no);
		rpt2xls.put_cell(2, :transp_dt);
		rpt2xls.put_cell(3, :no_police);
		rpt2xls.put_cell(4, :transporter);
		rpt2xls.put_cell(5, :truck_type);
		rpt2xls.put_cell(6, :destination);
		rpt2xls.put_cell(7, :transp_code);
		rpt2xls.put_cell(8, :type);
		rpt2xls.put_cell(9, :truck_cap);
		rpt2xls.put_cell(10, :truck_qty);
		rpt2xls.put_cell(11, :truck_rate);
		rpt2xls.put_cell(12, :total_rate);
		rpt2xls.put_cell(13, :customer);
		rpt2xls.put_cell(14, :dn_no);
		rpt2xls.put_cell(15, :item_code);
		rpt2xls.put_cell(16, :item_desc);
		rpt2xls.put_cell(17, :grade_1);
		rpt2xls.put_cell(18, :grade_2);
		rpt2xls.put_cell(19, :qty_gross);
		rpt2xls.put_cell(20, :qty_nett);
		if flag.var_1 = :total_rate and
		 flag.var_2 = :grade_1 and  
		 flag.var_3 = :grade_2 and 
		 flag.var_4 = :qty_gross and 
		 flag.var_5 = :qty_nett  then
		 rpt2xls.put_cell(21, 'Double Data Please Delete this row !!', FontColor => 3);
		end if;
		flag.var_1 := :total_rate;
		flag.var_2 := :grade_1;
		flag.var_3 := :grade_2;
		flag.var_4 := :qty_gross;
		flag.var_5 := :qty_nett;		
		rpt2xls.new_line;				
	end if;	
	flag.var_6 := :cs_no;
  return (true);
end;

function AfterReport return boolean is
begin
	if :M_DEST_TYPE = 'E' then
     RPT2XLS.run;  
  end if;
  return (TRUE);
end;

function AfterPForm return boolean is
begin
	if :rep_value_1 is not null and :rep_value_2 is not null then
   	:p_date_from	:= to_date(:rep_value_1,'DD/MM/RR');
    :p_date_to	  := to_date(:rep_value_2,'DD/MM/RR');
	end if;
	if :rep_value_3 is not null then
		:l_transporter  := ' and transporter = :rep_value_3 ';
	else
		:l_transporter  := '';
	end if;	
	if :rep_value_4 is not null then
		:l_destination  := ' and destination = :rep_value_4 ';
	else
		:l_destination  := '';
  end if;	  
  return (true);
end;

function F_transp_noFormatTrigger return boolean is
begin	
	if flag.var_6 != :cs_no then
	  rpt2xls.put_cell(1, :transp_no);
		rpt2xls.put_cell(2, :transp_dt);
		rpt2xls.put_cell(3, :no_police);
		rpt2xls.put_cell(4, :transporter);
		rpt2xls.put_cell(5, :truck_type);
		rpt2xls.put_cell(6, :destination);
		rpt2xls.put_cell(7, :transp_code);
		rpt2xls.put_cell(8, :type);
		rpt2xls.put_cell(9, :truck_cap);
		rpt2xls.put_cell(10, :truck_qty);
		rpt2xls.put_cell(11, :truck_rate);
		rpt2xls.put_cell(12, :total_rate);
		rpt2xls.put_cell(13, :customer);
		rpt2xls.put_cell(14, :dn_no);
		rpt2xls.put_cell(15, :item_code);
		rpt2xls.put_cell(16, :item_desc);
		rpt2xls.put_cell(17, :grade_1);
		rpt2xls.put_cell(18, :grade_2);
		rpt2xls.put_cell(19, :qty_gross);
		rpt2xls.put_cell(20, :qty_nett);
		if flag.var_1 = :total_rate and
		 flag.var_2 = :grade_1 and  
		 flag.var_3 = :grade_2 and 
		 flag.var_4 = :qty_gross and 
		 flag.var_5 = :qty_nett  then
		 rpt2xls.put_cell(21, 'Double Data Please Delete this row !!', FontColor => 3);
		end if;
		flag.var_1 := :total_rate;
		flag.var_2 := :grade_1;
		flag.var_3 := :grade_2;
		flag.var_4 := :qty_gross;
		flag.var_5 := :qty_nett;		
		rpt2xls.new_line;				
	end if;	
	flag.var_6 := :cs_no;
  return (true);
end;pw
select  TRANSP_NO, 
        TRANSP_DT, 
        NO_POLICE, 
        TRANSPORTER, 
        TRUCK_TYPE, 
        DESTINATION, 
        CUSTOMER, 
        TRANSP_CODE, 
        TYPE, 
        TRUCK_CAP, 
        TRUCK_QTY, 
        TRUCK_RATE, 
        TOTAL_RATE, 
        DN_NO, 
        ITEM_CODE, 
        ITEM_DESC, 
        GRADE_1, 
        GRADE_2, 
        QTY_GROSS, 
        QTY_NETT
from mgt_transp_view
where transp_dt between :p_date_from and :p_date_to
&l_transporter
&l_destination
X(^ b
n(z
SQL*ReportWriterKg
REPORT
MGTDAT
TRNSP003
MGTDATxu
gh~_
MGTDATxu
h~_
TOTA
ITEM_CODE
 , .
00r50g100b100
 * , .
00r75g100b100
 * , .
