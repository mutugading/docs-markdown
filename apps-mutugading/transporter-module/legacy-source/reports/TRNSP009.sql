-- ==============================================================
-- TRNSP009.RDF — query & struktur report (hasil ekstraksi)
-- ==============================================================

-- ---------- Tabel/view yang dibaca ----------
-- FROM FT_OS
-- FROM FV_OS_MATCH
-- FROM FV_TRANS_HEADER
-- FROM MGT_TP_PROVISION

-- ---------- Judul & label kolom ----------
-- Amount
-- As On : 
-- B(F0J
-- B0C0D
-- BLUE
-- Code
-- Created...
-- Cs Amt Supp
-- DATE
-- DD-MM-RR
-- DD-MM-RR HH24:MI:SS
-- DD/MM/RR
-- Date
-- Date:
-- Default
-- Destination Format
-- Destination Name
-- Destination Type
-- Due Date
-- Enter values for the parameters
-- F(J N
-- Finance
-- Finance Doc
-- Finreppl
-- GREEN
-- Grand Total : 
-- JV / Bill
-- JV / Bill Date
-- JV or Bill Date
-- JpK P
-- JpK R
-- JpK T
-- JpK V
-- JpK X
-- JpK Z
-- MGTDAT
-- MGTDATxy
-- MS Sans Serif
-- NGNN
-- NNNGNNNGNNNGNN0
-- Number of Copies
-- Output Mode
-- PBW2
-- PT. MUTU GADING TEKSTIL
-- Per0
-- Q0R0S
-- REMARKS
-- REPORT
-- RIBS
-- RPT2XLS
-- RUNONLYota
-- Remarks
-- Report Parameters
-- Run in Background
-- SUMAMTPERREPORT
-- Screen
-- Show Print Job Dialog
-- Status
-- Sub Total : 
-- SumAMTPerReport
-- Supp
-- Supp Code
-- Supp Name
-- Supplier Code
-- Supplier Name
-- TRANS 
-- TRANS0
-- TRANSPORTER BUDGET
-- TRNSP009
-- Trans
-- Trans Date
-- Trans No
-- Trans No.
-- Transporter Budget
-- Version:
-- YPRINTJOB
-- ZMODE
-- a(bpf
-- a(bpfpf8f8h0j8l
-- a(bpfpf8f8h8j8l
-- aDESFORMAT
-- ault
-- b(fpjpj8j8l8n8p
-- b0c0d
-- bDESNAME
-- bROSSTRUCTS
-- black
-- blue
-- brcrf
-- cDESTYPE
-- class attributes
-- cxf8f8h8j8l
-- cyan
-- d(jpnpn8n0p8r8t
-- d0e0f
-- darkblue
-- darkcyan
-- darkgray
-- darkmagenta
-- darkred
-- darkyellow
-- dflt
-- e8CDx
-- eROSOBJMAP
-- escrip
-- f0g0h
-- fTotal : 
-- frjrs
-- g(jpnpn8n0p8r8t
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
-- help
-- i(jpn
-- j0k0l0mpnpn0n
-- j8j8l0n8p
-- k0l0m0n0o0p0q
-- k0l8n8p8r8t
-- magenta
-- mt S
-- n(r(v(z
-- n0o0p0qprpr0r
-- npopw
-- o(rpvpv0v0w8x8z
-- o0p0q0r0s0t0u
-- on Format
-- oppp
-- p Name
-- p'p/
-- p(p1
-- p)p3
-- p0q0r0sptp
-- pushb
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
-- r88g25b88
-- r88g50b0
-- r88g50b100
-- r88g75b0
-- rpsps
-- rpspu
-- rpspw
-- rwlo
-- s0t0u0v0w0x0y
-- spsps
-- ss attri
-- string
-- struct typedp
-- struct types
-- t0u0v0wpxp
-- t0u0v0wpxpz0z
-- tk2 reserved
-- tk2 uiStrings
-- trur
-- ttrig
-- v0w0x
-- v0w0x0ypzpz0z
-- vpwpw
-- vpwpz
-- white
-- wpwpw
-- wpypy
-- xpypyp
-- yellow

-- ---------- Query ----------
 SELECT OM_OST_KEY_NO MATCH_SYS_ID, OST_TRAN_CODE||'-'||OST_DOC_NO MATCH_TRN_CODE, OST_DOC_DT MATCH_DOC_DT, OST_KEY_NO MATCH_KEY_NO,
;

 SELECT OST_TRAN_CODE||'-'||OST_DOC_NO OS_TRAN_CODE, OST_KEY_NO, OST_LC_AMT, OST_FC_AMT
;

 select TPB_SUPP_CODE, TPB_SUPP_NAME, TPB_TRX_NO, TP_TJV_NO, TPB_DT, TPB_BILL_DT, NVL(OST_FC_AMT,TPB_TOTAL) OST_FC_AMT, 
;

SELECT MTM_TRANSP_CODE, SUPP_NAME, TP_NO, TP_DT, TP_JV_NO, TP_JV_DT JV_BILL_DT, SUM(NVL(TP_AMT,0) + NVL(TP_OTH_AMT,0)) AMT, 'PROVISION' REMARKS,
;

SELECT TH_TRAN_CODE||'-'||TH_DOC_NO FIN_TRANS, 'POSTED' STS FROM FV_TRANS_HEADER
;

select TPB_SUPP_CODE, TPB_SUPP_NAME, TPB_TRX_NO, TPB_DT, TP_TJV_NO, TPB_BILL_DT, NVL(OST_FC_AMT,0) - NVL(SUM(MATCH_FC_AMT),0) OUTSTANDING, 'BILL' REMARKS, DUE_DATE, STS
;

select TPB_SUPP_CODE, TPB_SUPP_NAME, TPB_TRX_NO, TP_TJV_NO, TPB_DT, TPB_BILL_DT, OST_FC_AMT, DUE_DATE, NVL(OST_KEY_NO,1111111) OST_KEY_NO, STS
;

-- ---------- PL/SQL (format trigger / AfterReport) ----------

function AfterReport return boolean is
begin
  if :M_DEST_TYPE = 'E' then
     RPT2XLS.run;  
  end if;
  return (TRUE);
end;

function B_23FormatTrigger return boolean is
	m_page_num number;
begin
  srw.get_page_num(m_page_num);
	if m_page_num = 1 then
		RPT2XLS.put_cell(2, 'PT. MUTU GADING TEKSTIL', FontSize => 12, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
	  RPT2XLS.put_cell(2, 'TRANSPORTER BUDGET', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
		RPT2XLS.put_cell(1, 'No.', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(2, 'Supplier Code', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(3, 'Supplier Name', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(4, 'Trans No.', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(5, 'Trans Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(6, 'Finance Doc', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(7, 'JV or Bill Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(8, 'Amount', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(9, 'Remarks', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(10, 'Due Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(11, 'Status', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;		
	end if;
	return (TRUE);
END;

function R_G_MTM_TRANSP_CODE1FormatTrig return boolean is
begin
	if flag.var_1 != :cs_nmr||:TP_NO then
  	RPT2XLS.put_cell(1, :cs_nmr);
		RPT2XLS.put_cell(2, :MTM_TRANSP_CODE);
		RPT2XLS.put_cell(3, :SUPP_NAME);
		RPT2XLS.put_cell(4, :TP_NO);
		RPT2XLS.put_cell(5, :TP_DT);
		RPT2XLS.put_cell(6, :TP_JV_NO);
		RPT2XLS.put_cell(7, :JV_BILL_DT);
		RPT2XLS.put_cell(8, :AMT);
		RPT2XLS.put_cell(9, :REMARKS);		
		RPT2XLS.put_cell(10, :DUE_DATE);
		RPT2XLS.put_cell(11, :STS);
		RPT2XLS.new_line;		
		flag.var_1 := :cs_nmr||:TP_NO;
	end if;	
  return (TRUE);
end;

function AfterReport return boolean is
begin
  if :M_DEST_TYPE = 'E' then
     RPT2XLS.run;  
  end if;
  return (TRUE);
end;

function B_23FormatTrigger return boolean is
	m_page_num number;
begin
  srw.get_page_num(m_page_num);
	if m_page_num = 1 then
		RPT2XLS.put_cell(2, 'PT. MUTU GADING TEKSTIL', FontSize => 12, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
	  RPT2XLS.put_cell(2, 'TRANSPORTER BUDGET', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
		RPT2XLS.put_cell(1, 'No.', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(2, 'Supplier Code', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(3, 'Supplier Name', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(4, 'Trans No.', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(5, 'Trans Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(6, 'Finance Doc', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(7, 'JV or Bill Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(8, 'Amount', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(9, 'Remarks', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(10, 'Due Date', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(11, 'Status', FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;		
	end if;
	return (TRUE);
END;

function R_G_MTM_TRANSP_CODE1FormatTrig return boolean is
begin
	if flag.var_1 != :cs_nmr||:TP_NO then
  	RPT2XLS.put_cell(1, :cs_nmr);
		RPT2XLS.put_cell(2, :MTM_TRANSP_CODE);
		RPT2XLS.put_cell(3, :SUPP_NAME);
		RPT2XLS.put_cell(4, :TP_NO);
		RPT2XLS.put_cell(5, :TP_DT);
		RPT2XLS.put_cell(6, :TP_JV_NO);
		RPT2XLS.put_cell(7, :JV_BILL_DT);
		RPT2XLS.put_cell(8, :AMT);
		RPT2XLS.put_cell(9, :REMARKS);		
		RPT2XLS.put_cell(10, :DUE_DATE);
		RPT2XLS.put_cell(11, :STS);
		RPT2XLS.new_line;		
		flag.var_1 := :cs_nmr||:TP_NO;
	end if;	
  return (TRUE);
end;
