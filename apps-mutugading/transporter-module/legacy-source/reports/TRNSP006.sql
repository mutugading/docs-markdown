-- ==============================================================
-- TRNSP006.RDF — query & struktur report (hasil ekstraksi)
-- ==============================================================

-- ---------- Tabel/view yang dibaca ----------
-- FROM MGT_TP_PROVISION_BILL

-- ---------- Judul & label kolom ----------
-- Amount
-- B(F0J
-- B0C0D F
-- BLUE
-- Bill Amount
-- Bill Date
-- Bill No
-- Bill No.
-- Capasity
-- Cf Sysdate
-- Curr
-- D H 
-- DD-MON-YYYY
-- DD/MM/YY
-- DD/MM/YYYY HH24:MI:SS
-- Date
-- Default
-- Destination
-- Destination Format
-- Destination Name
-- Destination Type
-- Diff.  Amount
-- Diff. Amount
-- Driver
-- E0F0G H
-- Enter values for the parameters
-- F(J0K
-- FP Date
-- FP. No.
-- Faktur Pajak Date
-- Faktur Pajak No.
-- Finreppl
-- GREEN
-- H0I0J L
-- JV No.
-- K0L0M N
-- L P 
-- MGTDAT
-- MGTDATxq
-- MGTDATxy
-- N0O0P R
-- NN n
-- NNNGNNNGNNNDNN
-- NNNGNNNGNNNGNNN
-- No. Polisi
-- Number of Copies
-- Others Amount
-- Output Mode
-- PARAMETER
-- PARAMETERFORMULA
-- PBW2
-- PPN Amount
-- PPh Amount
-- PT. MUTU GADING TEKSTIL
-- Payment Amount
-- Period : 
-- Period : All
-- Q0R0S T
-- Quantity
-- REPORT
-- RPT2XLS
-- Remark
-- Remarks
-- Report Parameters
-- Run in Background
-- Screen
-- Show Print Job Dialog
-- SpTpT
-- Status
-- Supp Code
-- Supplier
-- Supplier Code
-- Supplier Name
-- T X 
-- TJV No.
-- TN g
-- TOTAL
-- TPDN /TPCHP No.
-- TPDN/TPCHP No.
-- TRANSPORTER BILL
-- TRNSP006
-- Tahoma
-- Total
-- Total :
-- Total Amount
-- Tp Amt
-- Tp Amt Diff
-- Tp Amt Typing
-- Tp Cap
-- Tp Code
-- Tp Destination
-- Tp Driver
-- Tp Dt
-- Tp Jv No
-- Tp Name
-- Tp No
-- Tp Oth Amt
-- Tp Pol No
-- Tp Qty
-- Tp Remark
-- Tp Status
-- Tp Sys Id
-- Tp Tjv No
-- Tp Total Amt
-- Tp Truck Type
-- Tpb Bill Amt
-- Tpb Bill Dt
-- Tpb Bill No
-- Tpb Curr
-- Tpb Dt
-- Tpb Fp Dt
-- Tpb Fp No
-- Tpb Pph
-- Tpb Pph Amt
-- Tpb Ppn
-- Tpb Ppn Amt
-- Tpb Remark
-- Tpb Supp Code
-- Tpb Supp Name
-- Tpb Sys Id
-- Tpb Total
-- Tpb Trx No
-- Trans Code
-- Trans Type
-- Transaction Date
-- Transaction No
-- Transaction No.
-- Transporte Name
-- Transporter Name
-- Trx Date
-- Type
-- UM10
-- XpYpY
-- YPRINTJOB
-- ZMODE
-- aDESFORMAT
-- b(fpj
-- b(fpj8t
-- b(fpjpj8j0l8n8p
-- b0c0d
-- bDESNAME
-- black
-- blue
-- brcrv
-- cDESTYPE
-- class attributes
-- crvrv
-- cyan
-- d(jpn
-- darkblue
-- darkcyan
-- darkgray
-- darkmagenta
-- darkred
-- darkyellow
-- dflt
-- eN k
-- eN l
-- f(jpnpn8n0p8r8t
-- f0g0h
-- fCf Sysdate
-- fTotal:
-- g(jpnpn8n0p8r8t
-- g100b88
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
-- gray84
-- gray84(
-- gray88
-- gray92
-- gray92z
-- gray96
-- green
-- i8j8l0n
-- j(nprpr0r0s8t8v
-- j8l8n0o
-- k(npr
-- k0l8n0p8r8t
-- l p 
-- l0n8p0r8t8v
-- lTahoma
-- m0n0o0p0q0r0s
-- magenta
-- n(rpv
-- n(rpv0
-- npopw
-- o(rpv
-- ound
-- p p&p.
-- p p'p0
-- p p(p2
-- p p)p4
-- p p,p:
-- parameter
-- parameterformula
-- q0r0s0t0u0v0w
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
-- r0g88b88
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
-- rpv vpxpx
-- struct types
-- t x 
-- t0u0v
-- t0u0v0wpxpz0z
-- tk2 reserved
-- tk2 uiStrings
-- tpup
-- ut Mode
-- v0w0x0ypzp
-- v0w0x0ypzpz0z
-- vpwp
-- vpwpw
-- vpwpz
-- vrzr
-- white
-- wpwpw
-- wpxpxp
-- x0y0z
-- yellow
-- ysda
-- zod0

-- ---------- Query ----------
select a.tpb_trx_no,
;

-- ---------- PL/SQL (format trigger / AfterReport) ----------

function AfterReport return boolean is
begin
	if :rep_xlsflag = 'Y' then
  	RPT2XLS.Run;
  	RPT2XLS.release_memory;
	end if;
	if :M_DEST_TYPE = 'E' then
     RPT2XLS.run;  
  end if;
  return (TRUE);
end;

function F_tpb_trx_noFormatTrigger return boolean is
begin
	rpt2xls.put_cell(1, :tpb_trx_no);
  rpt2xls.put_cell(2, :tpb_dt);
  rpt2xls.put_cell(3, :tpb_bill_no);
  rpt2xls.put_cell(4, :tpb_bill_dt);
  rpt2xls.put_cell(5, :tpb_supp_code);
  rpt2xls.put_cell(6, :tpb_supp_name);
  rpt2xls.put_cell(7, :tpb_fp_no);
  rpt2xls.put_cell(8, :tpb_fp_dt);
  rpt2xls.put_cell(9, :tpb_curr);
  rpt2xls.put_cell(10, :tpb_bill_amt);	  	  
  rpt2xls.put_cell(11, :tpb_ppn);	  	  
  rpt2xls.put_cell(12, :tpb_ppn_amt);	  	  
  rpt2xls.put_cell(13, :tpb_pph);
  rpt2xls.put_cell(14, :tpb_pph_amt);
  rpt2xls.put_cell(15, :tpb_total);
  rpt2xls.put_cell(16, :tpb_remark);  
	rpt2xls.put_cell(17, :tp_no);
  rpt2xls.put_cell(18, :tp_dt);
  rpt2xls.put_cell(19, :tp_code);
  rpt2xls.put_cell(20, :tp_name);
  rpt2xls.put_cell(21, :tp_truck_type);
  rpt2xls.put_cell(22, :tp_cap);
  rpt2xls.put_cell(23, :tp_destination);
  rpt2xls.put_cell(24, :tp_pol_no);
  rpt2xls.put_cell(25, :tp_driver);
  rpt2xls.put_cell(26, :tp_qty);	  
  rpt2xls.put_cell(27, :tp_amt);	  
  rpt2xls.put_cell(28, :tp_oth_amt);
  rpt2xls.put_cell(29, :tp_total_amt);
  rpt2xls.put_cell(30, :tp_amt_typing);
  rpt2xls.put_cell(31, :tp_amt_diff);	  
  rpt2xls.put_cell(32, :tp_status);
  rpt2xls.put_cell(33, :tp_jv_no);
  rpt2xls.put_cell(34, :tp_tjv_no);
  rpt2xls.put_cell(35, :tp_remark);
  rpt2xls.new_line;
  return (TRUE);
end;

function M_1FormatTrigger return boolean is
  m_page_num number;
begin
	srw.get_page_num(m_page_num); 	
 	if m_page_num = 1 then
 		rpt2xls.put_cell(1, 'PT. MUTU GADING TEKSTIL', FontStyle => RPT2XLS.BOLD);
		rpt2xls.new_line;
	 	rpt2xls.put_cell(1, 'TRANSPORTER BILL',  FontSize => 14, FontStyle => RPT2XLS.BOLD);
	 	rpt2xls.new_line;
	 	rpt2xls.put_cell(1, :parameter, FontStyle => RPT2XLS.BOLD);
 		rpt2xls.new_line;
 		rpt2xls.new_line;
 		rpt2xls.put_cell(1, 'Transaction No', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(2, 'Transaction Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(3, 'Bill No', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(4, 'Bill Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(5, 'Supplier Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Supplier Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Faktur Pajak No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Faktur Pajak Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'Currency', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Bill Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(11, 'PPN', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(12, 'PPN Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(13, 'PPH', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'PPh Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Total', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Remark', FontStyle => RPT2XLS.BOLD);	  
 		rpt2xls.put_cell(17, 'TPDN /TPCHP No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'Trans Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Transporte Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(21, 'Trans Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(22, 'Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(23, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(24, 'No. Polisi', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(25, 'Driver', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(26, 'Quantity', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(27, 'Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(28, 'Others Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(29, 'Total Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(30, 'Bill Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(31, 'Diff. Amount', FontStyle => RPT2XLS.BOLD);	  
	  rpt2xls.put_cell(32, 'Status', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(33, 'JV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(34, 'TJV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(35, 'Remarks', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
 	end if;
  return (TRUE);
end;

function M_1FormatTrigger return boolean is
  m_page_num number;
begin
	srw.get_page_num(m_page_num); 	
 	if m_page_num = 1 then
 		rpt2xls.put_cell(1, 'PT. MUTU GADING TEKSTIL', FontStyle => RPT2XLS.BOLD);
		rpt2xls.new_line;
	 	rpt2xls.put_cell(1, 'TRANSPORTER BILL',  FontSize => 14, FontStyle => RPT2XLS.BOLD);
	 	rpt2xls.new_line;
	 	rpt2xls.put_cell(1, :parameter, FontStyle => RPT2XLS.BOLD);
 		rpt2xls.new_line;
 		rpt2xls.new_line;
 		rpt2xls.put_cell(1, 'Transaction No', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(2, 'Transaction Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(3, 'Bill No', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(4, 'Bill Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(5, 'Supplier Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Supplier Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Faktur Pajak No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Faktur Pajak Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'Currency', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Bill Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(11, 'PPN', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(12, 'PPN Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(13, 'PPH', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'PPh Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Total', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Remark', FontStyle => RPT2XLS.BOLD);	  
 		rpt2xls.put_cell(17, 'TPDN /TPCHP No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'Trans Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Transporte Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(21, 'Trans Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(22, 'Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(23, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(24, 'No. Polisi', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(25, 'Driver', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(26, 'Quantity', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(27, 'Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(28, 'Others Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(29, 'Total Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(30, 'Bill Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(31, 'Diff. Amount', FontStyle => RPT2XLS.BOLD);	  
	  rpt2xls.put_cell(32, 'Status', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(33, 'JV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(34, 'TJV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(35, 'Remarks', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
 	end if;
  return (TRUE);
end;

function F_tpb_trx_noFormatTrigger return boolean is
begin
	rpt2xls.put_cell(1, :tpb_trx_no);
  rpt2xls.put_cell(2, :tpb_dt);
  rpt2xls.put_cell(3, :tpb_bill_no);
  rpt2xls.put_cell(4, :tpb_bill_dt);
  rpt2xls.put_cell(5, :tpb_supp_code);
  rpt2xls.put_cell(6, :tpb_supp_name);
  rpt2xls.put_cell(7, :tpb_fp_no);
  rpt2xls.put_cell(8, :tpb_fp_dt);
  rpt2xls.put_cell(9, :tpb_curr);
  rpt2xls.put_cell(10, :tpb_bill_amt);	  	  
  rpt2xls.put_cell(11, :tpb_ppn);	  	  
  rpt2xls.put_cell(12, :tpb_ppn_amt);	  	  
  rpt2xls.put_cell(13, :tpb_pph);
  rpt2xls.put_cell(14, :tpb_pph_amt);
  rpt2xls.put_cell(15, :tpb_total);
  rpt2xls.put_cell(16, :tpb_remark);  
	rpt2xls.put_cell(17, :tp_no);
  rpt2xls.put_cell(18, :tp_dt);
  rpt2xls.put_cell(19, :tp_code);
  rpt2xls.put_cell(20, :tp_name);
  rpt2xls.put_cell(21, :tp_truck_type);
  rpt2xls.put_cell(22, :tp_cap);
  rpt2xls.put_cell(23, :tp_destination);
  rpt2xls.put_cell(24, :tp_pol_no);
  rpt2xls.put_cell(25, :tp_driver);
  rpt2xls.put_cell(26, :tp_qty);	  
  rpt2xls.put_cell(27, :tp_amt);	  
  rpt2xls.put_cell(28, :tp_oth_amt);
  rpt2xls.put_cell(29, :tp_total_amt);
  rpt2xls.put_cell(30, :tp_amt_typing);
  rpt2xls.put_cell(31, :tp_amt_diff);	  
  rpt2xls.put_cell(32, :tp_status);
  rpt2xls.put_cell(33, :tp_jv_no);
  rpt2xls.put_cell(34, :tp_tjv_no);
  rpt2xls.put_cell(35, :tp_remark);
  rpt2xls.new_line;
  return (TRUE);
end;

function F_Sumtp_amtPertpb_trx_noFormat return boolean is
begin
  rpt2xls.put_cell(27, 'TOTAL', FontStyle => RPT2XLS.BOLD);
  rpt2xls.put_cell(28, :Sumtp_amtPerReport);	  
  rpt2xls.put_cell(29, :Sumtp_oth_amtPerReport);
  rpt2xls.put_cell(30, :Sumtp_total_amtPerReport);
  rpt2xls.put_cell(31, :Sumtp_amt_typingPerReport);
  rpt2xls.put_cell(32, :Sumtp_amt_diffPerReport);	  
  rpt2xls.new_line;
  return (TRUE);
end;

function F_Sumtp_amtPertpb_trx_noFormat return boolean is
begin
  rpt2xls.put_cell(27, 'TOTAL', FontStyle => RPT2XLS.BOLD);
  rpt2xls.put_cell(28, :Sumtp_amtPerReport);	  
  rpt2xls.put_cell(29, :Sumtp_oth_amtPerReport);
  rpt2xls.put_cell(30, :Sumtp_total_amtPerReport);
  rpt2xls.put_cell(31, :Sumtp_amt_typingPerReport);
  rpt2xls.put_cell(32, :Sumtp_amt_diffPerReport);	  
  rpt2xls.new_line;
  return (TRUE);
end;0
.function parameterFormula return Char is
	v_return varchar2(500);
begin
  if :rep_value_1 is not null and :rep_value_2 is not null and :rep_value_3 is not null then
  	v_return := 'Period : '|| to_char(to_date(:rep_value_1,'DD/MM/YY'),'DD-MON-YYYY')||' To :'|| to_char(to_date(:rep_value_2,'DD/MM/YY'),'DD-MON-YYYY')||'        Transaction No : '||:rep_value_3;
  elsif :rep_value_1 is not null and :rep_value_2 is not null and :rep_value_3 is null then
  	v_return := 'Period : '|| to_char(to_date(:rep_value_1,'DD/MM/YY'),'DD-MON-YYYY')||' To :'|| to_char(to_date(:rep_value_2,'DD/MM/YY'),'DD-MON-YYYY');
  elsif :rep_value_1 is null and :rep_value_2 is null and :rep_value_3 is not null then
  	v_return := 'Period : All' ||'          Transaction No : '||:rep_value_3; 	
  end if;	       
  return v_return;
end;
