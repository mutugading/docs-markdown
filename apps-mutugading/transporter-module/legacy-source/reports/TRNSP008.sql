-- ==============================================================
-- TRNSP008.RDF — query & struktur report (hasil ekstraksi)
-- ==============================================================

-- ---------- Tabel/view yang dibaca ----------
-- FROM MGT_TP_PROVISION

-- ---------- Judul & label kolom ----------
-- Amount
-- B(F0J
-- BLUE
-- Bill Amount
-- C0D0E F
-- Capasity
-- Cs No
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
-- Enter values for the parameters
-- F0G0H J
-- Finreppl
-- GREEN
-- I0J0K L
-- JV No.
-- L0M0N P
-- METE
-- MGTDAT
-- MGTDATxq
-- MGTDATxy
-- N Nn
-- NN n
-- NNNGNNNGNNNDNN
-- NNNGNNNGNNNGNNN
-- No. Polisi
-- Number of Copies
-- O0P0Q R
-- OLOR
-- Others Amount
-- Output Mode
-- PARAMETER
-- PARAMETERFORMULA
-- PBW2
-- PT. MUTU GADING TEKSTIL
-- Payment Amount
-- Period : 
-- Period : All
-- Quantity
-- REPORT
-- RIBS
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
-- TJV No.
-- TN g
-- TOTAL
-- TPDN /TPCHP No.
-- TPDN/TPCHP No.
-- TRANSPORTER BILL
-- TRANSPORTER PROVISION
-- TRNSP008
-- Tahoma
-- Total :
-- Total Amount
-- Total:
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
-- Tpb Bill No
-- Trans Code
-- Trans Type
-- Transporte Name
-- Transporter Name
-- Type
-- XpYpY
-- YPRINTJOB
-- ZMODE
-- aDESFORMAT
-- ault
-- b(fpj
-- b(fpjpj8j0l0m8n
-- b(fpjpj8j0l8n8p
-- b0c0d
-- bDESNAME
-- black
-- blue
-- brcrv
-- cDESTYPE
-- cUM10
-- class attributes
-- cyan
-- d(jpn
-- d(jpn0
-- d(jpnpn8n8p0r8t
-- dUM10
-- darkblue
-- darkcyan
-- darkgray
-- darkmagenta
-- darkred
-- darkyellow
-- dflt
-- eN k
-- eN l
-- f0g0h
-- fCf Sysdate
-- fTotal:
-- forma
-- g IS
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
-- i0j0k0l0m0n0o
-- i8j8l0n
-- k(npr
-- k(nprpr8r0t8v8x
-- k0l8n0p8r8t
-- l(rpv
-- l(rpvpv0v0w8x8z
-- lTahoma
-- m0n0o0p0q0r0s
-- magenta
-- no14
-- npopw
-- p p&p,
-- p p'p.
-- p p(p0
-- p p)p2
-- p p,p8
-- p p-p:
-- p0q0r
-- p0q0r0sptpv0v
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
-- struct types
-- t0u0v
-- t0u0v0wpxpz0z
-- tk2 reserved
-- tk2 uiStrings
-- tpup
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

-- ---------- Query ----------
select tp_no,
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
end;RP
TON
0 SRW2_COLUMN
!e=SRW2_DATA_MODEL
 h!

tySRW2_DISPLAY_TAG
!h

#p>SRW2_DISTRIBUTION
/fo
izSRW2_ELEMENT
SRW2_FIELD
%h&
'?>SRW2_FORMAT_EXCEPTION
V@Z
hSRW2_FRAME
SRW2_GROUP
jSRW2_GROUP_NODE
 foSRW2_HYPERLINK
 h!
SRW2_JAVA_APPLET
$h%
&  SRW2_JAVA_PROPERTIES
SRW2_LAYOUT
!h

SRW2_LAYOUT_GROUP
ark
SRW2_LINK

 $
=SRW2_LISTS
aySRW2_MATRIX
#h$
SRW2_OGD_COLUMN_MAP
 h!
SRW2_OG_DOCUMENT

SRW2_PARAM_FORM
 
SRW2_QUERY
!h

SRW2_TEXT_SEGMENT
50g
TOOL_ACCESS
TOOL_COMMENT
TOOL_LIBRARY
TOOL_MODULE
 $TOOL_PLSQL
VG_COLOR
VG_COLOR
)VG_COLOR
ITEMID
)VG_COLOR
CELLID
+VG_COLOR
NAME_SET
b50
-VG_COLOR
NAME_LENGTH
-VG_COLOR
COLOR_NAME
%VG_COLOR
RED
'VG_COLOR
GREEN
'VG_COLOR
BLUE
$ &

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

function M_1FormatTrigger return boolean is
  m_page_num number;
begin
	srw.get_page_num(m_page_num); 	
 	if m_page_num = 1 then
 		rpt2xls.put_cell(1, 'PT. MUTU GADING TEKSTIL', FontStyle => RPT2XLS.BOLD);
		rpt2xls.new_line;
	 	rpt2xls.put_cell(1, 'TRANSPORTER PROVISION',  FontSize => 14, FontStyle => RPT2XLS.BOLD);
	 	rpt2xls.new_line;
	 	rpt2xls.put_cell(1, :parameter, FontStyle => RPT2XLS.BOLD);
 		rpt2xls.new_line;
 		rpt2xls.new_line;
 		rpt2xls.put_cell(1, 'No.', FontStyle => RPT2XLS.BOLD);	  
 		rpt2xls.put_cell(2, 'TPDN /TPCHP No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(3, 'Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(4, 'Trans Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(5, 'Transporte Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Trans Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'No. Polisi', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Driver', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(11, 'Quantity', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(12, 'Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(13, 'Others Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'Total Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Bill Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Diff. Amount', FontStyle => RPT2XLS.BOLD);	  
	  rpt2xls.put_cell(17, 'Status', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'JV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'TJV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Remarks', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
 	end if;
  return (TRUE);
end;

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
end;RP
TON
0 SRW2_COLUMN
!e=SRW2_DATA_MODEL
 h!

tySRW2_DISPLAY_TAG
!h

#p>SRW2_DISTRIBUTION
/fo
izSRW2_ELEMENT
SRW2_FIELD
%h&
'?>SRW2_FORMAT_EXCEPTION
V@Z
hSRW2_FRAME
SRW2_GROUP
jSRW2_GROUP_NODE
 foSRW2_HYPERLINK
 h!
SRW2_JAVA_APPLET
$h%
&  SRW2_JAVA_PROPERTIES
SRW2_LAYOUT
!h

SRW2_LAYOUT_GROUP
ark
SRW2_LINK

 $
=SRW2_LISTS
aySRW2_MATRIX
#h$
SRW2_OGD_COLUMN_MAP
 h!
SRW2_OG_DOCUMENT

SRW2_PARAM_FORM
 
SRW2_QUERY
!h

SRW2_TEXT_SEGMENT
50g
TOOL_ACCESS
TOOL_COMMENT
TOOL_LIBRARY
TOOL_MODULE
 $TOOL_PLSQL
VG_COLOR
VG_COLOR
)VG_COLOR
ITEMID
)VG_COLOR
CELLID
+VG_COLOR
NAME_SET
b50
-VG_COLOR
NAME_LENGTH
-VG_COLOR
COLOR_NAME
%VG_COLOR
RED
'VG_COLOR
GREEN
'VG_COLOR
BLUE
$ &

function F_Sumtp_amtPertpb_trx_no1Forma return boolean is
begin
  rpt2xls.put_cell(10, 'TOTAL', FontStyle => RPT2XLS.BOLD);
  rpt2xls.put_cell(11, :Sumtp_qtyPerReport);	  
  rpt2xls.put_cell(12, :Sumtp_amtPerReport);	  
  rpt2xls.put_cell(13, :Sumtp_oth_amtPerReport);
  rpt2xls.put_cell(14, :Sumtp_total_amtPerReport);
  rpt2xls.put_cell(15, :Sumtp_amt_typingPerReport);
  rpt2xls.put_cell(16, :Sumtp_amt_diffPerReport);	  
  rpt2xls.new_line;
  return (TRUE);
end;

function F_noFormatTrigger return boolean is
begin
	if flag.var_1 != :cs_no then 
		rpt2xls.put_cell(1, :cs_no);  
		rpt2xls.put_cell(2, :tp_no);
	  rpt2xls.put_cell(3, :tp_dt);
	  rpt2xls.put_cell(4, :tp_code);
	  rpt2xls.put_cell(5, :tp_name);
	  rpt2xls.put_cell(6, :tp_truck_type);
	  rpt2xls.put_cell(7, :tp_cap);
	  rpt2xls.put_cell(8, :tp_destination);
	  rpt2xls.put_cell(9, :tp_pol_no);
	  rpt2xls.put_cell(10, :tp_driver);
	  rpt2xls.put_cell(11, :tp_qty);	  
	  rpt2xls.put_cell(12, :tp_amt);	  
	  rpt2xls.put_cell(13, :tp_oth_amt);
	  rpt2xls.put_cell(14, :tp_total_amt);
	  rpt2xls.put_cell(15, :tp_amt_typing);
	  rpt2xls.put_cell(16, :tp_amt_diff);	  
	  rpt2xls.put_cell(17, :tp_status);
	  rpt2xls.put_cell(18, :tp_jv_no);
	  rpt2xls.put_cell(19, :tp_tjv_no);
	  rpt2xls.put_cell(20, :tp_remark);
 		flag.var_1 := :cs_no;
 		rpt2xls.new_line;	
 	end if;
  return (TRUE);
end;

function F_Sumtp_amtPertpb_trx_no1Forma return boolean is
begin
  rpt2xls.put_cell(10, 'TOTAL', FontStyle => RPT2XLS.BOLD);
  rpt2xls.put_cell(11, :Sumtp_qtyPerReport);	  
  rpt2xls.put_cell(12, :Sumtp_amtPerReport);	  
  rpt2xls.put_cell(13, :Sumtp_oth_amtPerReport);
  rpt2xls.put_cell(14, :Sumtp_total_amtPerReport);
  rpt2xls.put_cell(15, :Sumtp_amt_typingPerReport);
  rpt2xls.put_cell(16, :Sumtp_amt_diffPerReport);	  
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
	 	rpt2xls.put_cell(1, 'TRANSPORTER PROVISION',  FontSize => 14, FontStyle => RPT2XLS.BOLD);
	 	rpt2xls.new_line;
	 	rpt2xls.put_cell(1, :parameter, FontStyle => RPT2XLS.BOLD);
 		rpt2xls.new_line;
 		rpt2xls.new_line;
 		rpt2xls.put_cell(1, 'No.', FontStyle => RPT2XLS.BOLD);	  
 		rpt2xls.put_cell(2, 'TPDN /TPCHP No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(3, 'Date', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(4, 'Trans Code', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(5, 'Transporte Name', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(6, 'Trans Type', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(7, 'Capasity', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(8, 'Destination', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(9, 'No. Polisi', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(10, 'Driver', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(11, 'Quantity', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(12, 'Amount', FontStyle => RPT2XLS.BOLD);	  	  
	  rpt2xls.put_cell(13, 'Others Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(14, 'Total Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(15, 'Bill Amount', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(16, 'Diff. Amount', FontStyle => RPT2XLS.BOLD);	  
	  rpt2xls.put_cell(17, 'Status', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(18, 'JV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(19, 'TJV No.', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.put_cell(20, 'Remarks', FontStyle => RPT2XLS.BOLD);
	  rpt2xls.new_line;
 	end if;
  return (TRUE);
end;

function F_Sumtp_amtPertpb_trx_no1Forma return boolean is
begin
  rpt2xls.put_cell(10, 'TOTAL', FontStyle => RPT2XLS.BOLD);
  rpt2xls.put_cell(11, :Sumtp_qtyPerReport);	  
  rpt2xls.put_cell(12, :Sumtp_amtPerReport);	  
  rpt2xls.put_cell(13, :Sumtp_oth_amtPerReport);
  rpt2xls.put_cell(14, :Sumtp_total_amtPerReport);
  rpt2xls.put_cell(15, :Sumtp_amt_typingPerReport);
  rpt2xls.put_cell(16, :Sumtp_amt_diffPerReport);	  
  rpt2xls.new_line;
  return (TRUE);
end; ,
00r100g88b75
& ( *
00r0g88b88
 ( * ,
	00r25g88b88
 ( * ,
	00r50g88b88
 ( * ,
	00r75g88b88
 ( * ,
	00r88g88b88
 ( * ,
00r100g88b88
' ( *
	00r0g88b100
 ( * ,
00r25g88b100@
 ( * ,
00r50g88b100
 ( * ,
00r75g88b100
 ( * ,
00r88g88b100
 * , .
00r100g88b100
 & ( *
, (
00custom5
 & ( *
00custom6
 & ( *
00custom7
 & ( *
00custom8
 $ & (
*ll
00gray16
 $ & (
00gray12
 $ & (
*ge
00gray8
 $ & (
*ay
00gray4
& (
00r0g100b0
 ( *
	00r25g100b0
 ( *
	00r50g100b0
 ( *
- 

	00r75g100b0
 ( *
	00r88g100b0
 ( *
00r100g100b0
' ( *
	00r0g100b50
 ( * ,
.00
00r25g100b50@
 ( * ,
.g7
00r50g100b50
 ( * ,
.5g

function F_noFormatTrigger return boolean is
begin
	if flag.var_1 != :cs_no then 
		rpt2xls.put_cell(1, :cs_no);  
		rpt2xls.put_cell(2, :tp_no);
	  rpt2xls.put_cell(3, :tp_dt);
	  rpt2xls.put_cell(4, :tp_code);
	  rpt2xls.put_cell(5, :tp_name);
	  rpt2xls.put_cell(6, :tp_truck_type);
	  rpt2xls.put_cell(7, :tp_cap);
	  rpt2xls.put_cell(8, :tp_destination);
	  rpt2xls.put_cell(9, :tp_pol_no);
	  rpt2xls.put_cell(10, :tp_driver);
	  rpt2xls.put_cell(11, :tp_qty);	  
	  rpt2xls.put_cell(12, :tp_amt);	  
	  rpt2xls.put_cell(13, :tp_oth_amt);
	  rpt2xls.put_cell(14, :tp_total_amt);
	  rpt2xls.put_cell(15, :tp_amt_typing);
	  rpt2xls.put_cell(16, :tp_amt_diff);	  
	  rpt2xls.put_cell(17, :tp_status);
	  rpt2xls.put_cell(18, :tp_jv_no);
	  rpt2xls.put_cell(19, :tp_tjv_no);
	  rpt2xls.put_cell(20, :tp_remark);
 		flag.var_1 := :cs_no;
 		rpt2xls.new_line;	
 	end if;
  return (TRUE);
end;

function F_noFormatTrigger return boolean is
begin
	if flag.var_1 != :cs_no then 
		rpt2xls.put_cell(1, :cs_no);  
		rpt2xls.put_cell(2, :tp_no);
	  rpt2xls.put_cell(3, :tp_dt);
	  rpt2xls.put_cell(4, :tp_code);
	  rpt2xls.put_cell(5, :tp_name);
	  rpt2xls.put_cell(6, :tp_truck_type);
	  rpt2xls.put_cell(7, :tp_cap);
	  rpt2xls.put_cell(8, :tp_destination);
	  rpt2xls.put_cell(9, :tp_pol_no);
	  rpt2xls.put_cell(10, :tp_driver);
	  rpt2xls.put_cell(11, :tp_qty);	  
	  rpt2xls.put_cell(12, :tp_amt);	  
	  rpt2xls.put_cell(13, :tp_oth_amt);
	  rpt2xls.put_cell(14, :tp_total_amt);
	  rpt2xls.put_cell(15, :tp_amt_typing);
	  rpt2xls.put_cell(16, :tp_amt_diff);	  
	  rpt2xls.put_cell(17, :tp_status);
	  rpt2xls.put_cell(18, :tp_jv_no);
	  rpt2xls.put_cell(19, :tp_tjv_no);
	  rpt2xls.put_cell(20, :tp_remark);
 		flag.var_1 := :cs_no;
 		rpt2xls.new_line;	
 	end if;
  return (TRUE);
end;
