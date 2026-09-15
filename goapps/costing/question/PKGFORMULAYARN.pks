CREATE OR REPLACE package MGTAPPS.PkgFormulaYarn is
    function fPoyPower_87(pCycSysId varchar2) return number;
    function fPoyManPower_88(pCycSysId varchar2) return number;
    function fPoyOverheads_89(pCycSysId varchar2) return number;
    function fPoyConsSprs_90(pCycSysId varchar2) return number;
    
    function fOPU_prsn(pCyl_Sys_id varchar2) return varchar2;
    
    function fGetCustomerProduct(pCyc_Sys_id varchar2) return varchar2;
    
    function fGetSuperbaShadeName(pCyc_Sys_id varchar2) return varchar2;
    
    function fGetSoftnerCost(pCyl_Sys_id varchar2) return varchar2;
end;
/
