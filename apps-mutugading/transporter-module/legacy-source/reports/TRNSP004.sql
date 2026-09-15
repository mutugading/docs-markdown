-- ==============================================================
-- TRNSP004.RDF — query & struktur report (hasil ekstraksi)
-- ==============================================================

-- ---------- Tabel/view yang dibaca ----------
-- FROM MGT_TRANSP_HEAD
-- FROM OT_INVOICE_HEAD

-- ---------- Judul & label kolom ----------
-- Amount
-- B(F0J
-- B0C0D
-- BLUE
-- BpC R
-- BpC V
-- BpC X
-- Code 1
-- Code 2
-- Cs No
-- Customer
-- DD/MM/RR
-- DFTR
-- Date
-- Date From : 
-- Default
-- Delivery Note
-- Destination
-- Destination Format
-- Destination Name
-- Destination Type
-- Driver
-- E0F0G
-- Enter values for the parameters
-- F0G0H
-- Finreppl
-- Freight Amount
-- Freight Per Kg
-- Freight Val
-- GREEN
-- Grade
-- Grade Code 1
-- Grade Code 2
-- Gross WT
-- HTahoma
-- INVI
-- Inv No
-- Invh Cust Name
-- Invoice No.
-- Item Code
-- Item Description
-- JpK R
-- JpK T
-- JpK X
-- JpK Z
-- K0L0M
-- L0M0N
-- M N0P0Q
-- MC Department - Despatch
-- MGTDAT
-- MGTDATxu
-- N0O0P
-- NNNGNN0
-- NNNGNNNGNN0D00
-- NNNGNNNGNN0D000
-- NNNGNNNGNNNGNN0DNN
-- Name
-- Net WT
-- No Polisi
-- No. Polisi
-- Number of Copies
-- O0P0Q
-- Oth Chg Val
-- Othchg Net Wt
-- Other Charges Amount
-- Other Charges Per Kg
-- Output Mode
-- PBW2
-- Per Kg
-- Polis
-- Polisi
-- Print On : 
-- Product
-- Q0R0S
-- QTahoma
-- RATE
-- REPORT
-- RIBS
-- RPT2XLS
-- Rate
-- Rate Net Wt
-- Report Parameters
-- Run in Background
-- SUMVALPERREPORT
-- Screen
-- Show Print Job Dialog
-- SumVALPerReport
-- TRNSP004
-- TYPE
-- Tafterreport
-- Tahoma
-- Tot Val
-- Tota
-- Total Cost Amount
-- Total Cost Per Kg
-- Total:
-- Totrate Netwt
-- Transaction
-- Transaction Date
-- Transaction No
-- Transaction No.
-- Transp Name
-- Transp No
-- Transporter
-- Transporter Details
-- Transporter Name
-- Truck
-- Truck Capacity
-- Truck Type
-- Type
-- Type Product
-- W0X0Y
-- YPRINTJOB
-- ZMODE
-- aDESFORMAT
-- b(fpj0
-- b(fpjpj8j8l0n8p
-- b(fpjpj8j8l8n8p
-- bDESNAME
-- black
-- blue
-- brcrf
-- cDESTYPE
-- class attributes
-- cyan
-- d(jpn
-- d(jpnpn0n8p8r8t
-- d(jpnpn8n0p0q8r
-- d0e0f
-- darkblue
-- darkcyan
-- darkgray
-- darkmagenta
-- darkred
-- darkyellow
-- dflt
-- e(fpjpj8j0l8n8p
-- fTotal:
-- frjrs
-- g0h0i0j0k0l0m
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
-- h(nprpr0r0s8t8v
-- h0i0j0k
-- j(npnpn0o0p8r8t
-- j(npnpn8p0r8t8v
-- k0l0m0n0o0p0q
-- k0l8n0p8r8t
-- m(nprpr8r0t8v8x
-- magenta
-- n(rprpr8t0v8x8z
-- n0o0p
-- npopw
-- o0p0q0r0s0t0u
-- on T
-- oppp
-- p p'p.
-- p p(p0
-- p p,p8
-- p&p-
-- p(vpz
-- p)p3
-- p1pC
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
-- r0s0t0upvp
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
-- spsps
-- sptptp
-- struct types
-- t0u0v0wpxpz0z
-- tk2 reserved
-- tk2 uiStrings
-- trur
-- v0w0x
-- v0w0x0ypzp
-- v0w0x0ypzpz0z
-- vpwpw
-- vpwpz
-- white
-- wpwpw
-- x0ypzp
-- yellow

-- ---------- Query ----------
 select INVH_TXN_CODE, INVH_NO, inv_no, INVI_ITEM_CODE, INVI_ITEM_DESC, INVI_GRADE_CODE_1, INVI_GRADE_CODE_2, 
;

 select INVH_TXN_CODE, INVH_NO, inv_no, ITEM_CODE INVI_ITEM_CODE,ITEM_NAME INVI_ITEM_DESC, PRD_GRADE INVI_GRADE_CODE_1, nvl(SHADE_CODE,'NL') INVI_GRADE_CODE_2, 
;

 select INVH_TXN_CODE, INVH_NO, sum(F_DCONV_BU_TO_PL_MGT(INVI_ITEM_CODE, INVI_UOM_CODE, INVI_QTY_BU, 0)) NET_QTY
;

 select MTH_SYS_ID, sum(MTDD_DN_QTY) GROSS_QTY, sum(NET_QTY) NET_QTY 
;

 select a.MTH_SYS_ID, 
;

 select a.MTH_SYS_ID, sum(MTDC_TOTAL_RATE) MTDC_TOTAL_AMOUNT, GROSS_QTY, nvl(NET_QTY,nvl(GROSS_QTY,0)) NET_QTY,
;

select MTH_TXN_CODE||'-'||MTH_TRANSP_NO TRANSP_NO, MTH_DT, MTH_NO, MTH_TRANSP_NAME, MTH_TRUCK_TYPE, MTH_TRUCK_CAP, MTH_DESTINATION, MTH_NO_POLICE, MTH_DRIVER,
;

-- ---------- PL/SQL (format trigger / AfterReport) ----------

function AfterReport return boolean is
begin
  if :M_DEST_TYPE ='E' then
     RPT2XLS.Run;  
  end if;
  return (TRUE);
end;

function M_HEADERFormatTrigger return boolean is
  	M_PAGE_NUM NUMBER;
begin
  	SRW.GET_PAGE_NUM(M_PAGE_NUM);
	IF M_PAGE_NUM=1 THEN
		RPT2XLS.put_cell(1, 'MC Department - Despatch', FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
	  RPT2XLS.put_cell(7, 'Transporter Details', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
		--RPT2XLS.put_cell(1, 'Type : '||:P_TYPE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(1, 'Date From : '||:REP_VALUE_1|| ' To '||:REP_VALUE_2, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		--RPT2XLS.put_cell(8, 'Merge No. : '||:P_MERGE_NO, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		--RPT2XLS.put_cell(8, 'Grade : '||:P_GRADE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(18, 'Print On : '||SYSDATE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;
		RPT2XLS.put_cell(1, 'Transaction No.',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(2, 'Date',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(3, 'Transporter Name',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(4, 'Truck Type',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(5, 'Truck Capacity',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(6, 'Destination',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(7, 'No. Polisi',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(8, 'Driver',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(9, 'Delivery Note',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(10, 'Invoice No.',FontSize => 11, FontStyle => RPT2XLS.BOLD);		
		RPT2XLS.put_cell(11, 'Item Code',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(12, 'Item Description',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(13, 'Grade Code 1',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(14, 'Grade Code 2',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(15, 'Net WT',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(16, 'Gross WT',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(17, 'Type Product',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(18, 'Freight Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(19, 'Other Charges Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(20, 'Total Cost Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(21, 'Freight Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(22, 'Other Charges Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(23, 'Total Cost Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);		
		RPT2XLS.put_cell(24, 'Customer',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;
	END IF;	
  return (TRUE);
end;

function M_HEADERFormatTrigger return boolean is
  	M_PAGE_NUM NUMBER;
begin
  	SRW.GET_PAGE_NUM(M_PAGE_NUM);
	IF M_PAGE_NUM=1 THEN
		RPT2XLS.put_cell(1, 'MC Department - Despatch', FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
	  RPT2XLS.put_cell(7, 'Transporter Details', FontSize => 14, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;	
		--RPT2XLS.put_cell(1, 'Type : '||:P_TYPE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(1, 'Date From : '||:REP_VALUE_1|| ' To '||:REP_VALUE_2, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		--RPT2XLS.put_cell(8, 'Merge No. : '||:P_MERGE_NO, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		--RPT2XLS.put_cell(8, 'Grade : '||:P_GRADE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(18, 'Print On : '||SYSDATE, FontSize => 10, FontColor => 3, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;
		RPT2XLS.put_cell(1, 'Transaction No.',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(2, 'Date',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(3, 'Transporter Name',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(4, 'Truck Type',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(5, 'Truck Capacity',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(6, 'Destination',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(7, 'No. Polisi',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(8, 'Driver',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(9, 'Delivery Note',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(10, 'Invoice No.',FontSize => 11, FontStyle => RPT2XLS.BOLD);		
		RPT2XLS.put_cell(11, 'Item Code',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(12, 'Item Description',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(13, 'Grade Code 1',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(14, 'Grade Code 2',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(15, 'Net WT',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(16, 'Gross WT',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(17, 'Type Product',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(18, 'Freight Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(19, 'Other Charges Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(20, 'Total Cost Per Kg',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(21, 'Freight Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(22, 'Other Charges Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.put_cell(23, 'Total Cost Amount',FontSize => 11, FontStyle => RPT2XLS.BOLD);		
		RPT2XLS.put_cell(24, 'Customer',FontSize => 11, FontStyle => RPT2XLS.BOLD);
		RPT2XLS.new_line;
	END IF;	
  return (TRUE);
end;

function R_G_TRANSP_NOFormatTrigger return boolean is
begin
	IF my_globals.gv_var1 != :cs_no then
  	RPT2XLS.put_cell(1, :TRANSP_NO);
		RPT2XLS.put_cell(2, :MTH_DT);
		RPT2XLS.put_cell(3, :MTH_TRANSP_NAME);
		RPT2XLS.put_cell(4, :MTH_TRUCK_TYPE);
		RPT2XLS.put_cell(5, :MTH_TRUCK_CAP);
		RPT2XLS.put_cell(6, :MTH_DESTINATION);
		RPT2XLS.put_cell(7, :MTH_NO_POLICE);
		RPT2XLS.put_cell(8, :MTH_DRIVER);
		RPT2XLS.put_cell(9, :DN_NO);
		RPT2XLS.put_cell(10, :INV_NO);
		RPT2XLS.put_cell(11, :INVI_ITEM_CODE);
		RPT2XLS.put_cell(12, :INVI_ITEM_DESC);
		RPT2XLS.put_cell(13, :INVI_GRADE_CODE_1);
		RPT2XLS.put_cell(14, :INVI_GRADE_CODE_2);
		RPT2XLS.put_cell(15, :INVI_NET_WT);
		RPT2XLS.put_cell(16, :INVI_GROSS_WT);
		RPT2XLS.put_cell(17, :TYPE_PROD);
		RPT2XLS.put_cell(18, :rate_net_wt);
		RPT2XLS.put_cell(19, :othchg_net_wt);
		RPT2XLS.put_cell(20, :totrate_netwt);
		RPT2XLS.put_cell(21, :freight_val);
		RPT2XLS.put_cell(22, :oth_chg_val);
		RPT2XLS.put_cell(23, :tot_val);
		RPT2XLS.put_cell(24, :invh_cust_name);
		RPT2XLS.new_line;
		my_globals.gv_var1 := :cs_no;
	end if;
  return (TRUE);
end;

function AfterReport return boolean is
begin
  if :M_DEST_TYPE ='E' then
     RPT2XLS.Run;  
  end if;
  return (TRUE);
end;

function R_G_TRANSP_NOFormatTrigger return boolean is
begin
	IF my_globals.gv_var1 != :cs_no then
  	RPT2XLS.put_cell(1, :TRANSP_NO);
		RPT2XLS.put_cell(2, :MTH_DT);
		RPT2XLS.put_cell(3, :MTH_TRANSP_NAME);
		RPT2XLS.put_cell(4, :MTH_TRUCK_TYPE);
		RPT2XLS.put_cell(5, :MTH_TRUCK_CAP);
		RPT2XLS.put_cell(6, :MTH_DESTINATION);
		RPT2XLS.put_cell(7, :MTH_NO_POLICE);
		RPT2XLS.put_cell(8, :MTH_DRIVER);
		RPT2XLS.put_cell(9, :DN_NO);
		RPT2XLS.put_cell(10, :INV_NO);
		RPT2XLS.put_cell(11, :INVI_ITEM_CODE);
		RPT2XLS.put_cell(12, :INVI_ITEM_DESC);
		RPT2XLS.put_cell(13, :INVI_GRADE_CODE_1);
		RPT2XLS.put_cell(14, :INVI_GRADE_CODE_2);
		RPT2XLS.put_cell(15, :INVI_NET_WT);
		RPT2XLS.put_cell(16, :INVI_GROSS_WT);
		RPT2XLS.put_cell(17, :TYPE_PROD);
		RPT2XLS.put_cell(18, :rate_net_wt);
		RPT2XLS.put_cell(19, :othchg_net_wt);
		RPT2XLS.put_cell(20, :totrate_netwt);
		RPT2XLS.put_cell(21, :freight_val);
		RPT2XLS.put_cell(22, :oth_chg_val);
		RPT2XLS.put_cell(23, :tot_val);
		RPT2XLS.put_cell(24, :invh_cust_name);
		RPT2XLS.new_line;
		my_globals.gv_var1 := :cs_no;
	end if;
  return (TRUE);
end;
