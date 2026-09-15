CREATE OR REPLACE package MGTAPPS.pkg_yarn_marketing is
    function fGet_POY_SysId_From_Captive(pCyl_Sys_Id varchar2) return varchar2;
    function fGet_POY_SysId(pCyl_Sys_Id varchar2) return varchar2;

    function getPrdFowarding return varchar2;

    function fGet_CylSysId(pWhereItem varchar2,pWhereValue varchar2) return varchar2;
    
    function fGetCylSysID_byLvl(pcyl_sys_id varchar2,pTotPrd number,pLvl number) return varchar2;
    
    function fGetVal_Calc_Cur(pCylSysId varchar2,pItemName varchar2) return varchar2;

    function fGetDataProduct(
                pCyl_Sys_Id varchar2
                ,pCYCRL_SOURCE_TYPE varchar2
                ,pCYCRL_SOURCE_QUERY varchar2
                ) return varchar2; 
                
    function fGetRowMaterial(pCylSysId varchar2) return varchar2;                
                
    function fGetDeepMaterial(pcyl_sys_id varchar2) return number;                
                
    FUNCTION fgetdatacustomer (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetmbname (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetmbname_rm (pcyl_sys_id VARCHAR2) return varchar2;
    
    FUNCTION fgetchip (pcylsysid VARCHAR2) return varchar2;
    
    function fGetCylSysID_Rm(pcylsysid varchar2) return varchar2;
    
    FUNCTION fgetchip_rm (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetchiprate (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetchiprate_rm (pcylsysid VARCHAR2) return varchar2;    
    
    FUNCTION fgetRpDoz (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetRpDoz_Rm (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetMbRate (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetMbRate_Rm (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetMbCost (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetMbCost_Rm (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetCngOvrLst (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetCngOvrLst_Rm (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetQualityLoss (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fgetFinalExFactoryCost (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_V1 (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_V2 (pcylsysid VARCHAR2) return varchar2; 
    
    FUNCTION fget_V3 (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_V4 (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_V5 (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_PackingType (pcylsysid VARCHAR2) return varchar2 ;
    
    FUNCTION fget_NoOfBobbins (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_BobbinWeightAX (pcylsysid VARCHAR2) return varchar2 ;
    
    FUNCTION fget_BoxWeight(pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_DelPackingName (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_DelPackBobinRate (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_DelPackBoxRate (pcylsysid VARCHAR2) return varchar2;    
    
    FUNCTION fget_DelPackingCost (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_IntermigleCost (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_FixedCost (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_McName (pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_McEff(pcylsysid VARCHAR2) return varchar2;
    
    FUNCTION fget_McSpeed (pcylsysid VARCHAR2) return varchar2;
    
    function isUseRmBO(pCYL_SYS_ID varchar2) return varchar2;
    
    function fGetDeepMaterial_RmBO(pcyl_sys_id varchar2) return number;
    
    function fGetDty_Prod(pcyl_sys_id varchar2) return varchar2;
    
    function fGetCylSysID_byLvl_RmBo(pcyl_sys_id varchar2,pTotLvl number,pGetLvl number) return varchar2;
    
    FUNCTION fGetDeepLvl_Acy (pcyl_sys_id VARCHAR2)
       RETURN VARCHAR2;
       
    FUNCTION fGetCylSysId_Acy ( pcyl_sys_id    VARCHAR2
                                ,pLvl_Prd       NUMBER)
       RETURN VARCHAR2  ;
    function fGetCylTypeByMktCstLnk(pMktCstLnk varchar2) return varchar;
    
    function fGetCylSysId(pMktCstLnk varchar2) return varchar2;
    
    FUNCTION fGetDeepMaterial_Ity (pcyl_sys_id VARCHAR2) return number;
    
    function fGetRm_Captive(pcyl_sys_id VARCHAR2) return varchar2;
    
    function fGet_Poy_Sys_Id(pcyl_sys_id VARCHAR2) return varchar2;
    
    function fGet_Poy_ManPower_cst(pcyl_sys_id VARCHAR2) return number;
    
    function fGet_Poy_Over_Heads_Cst(pcyl_sys_id VARCHAR2) return number;
    
    function fGet_Pty_Sys_Id(pcyl_sys_id VARCHAR2) return varchar2;
    
    function fGet_Pty_ManPower_cst(pcyl_sys_id VARCHAR2) return number;
    
    function fGet_Pty_Over_Heads_Cst(pcyl_sys_id VARCHAR2) return number;
    
    function fGet_Final_Conversion(
                                    pCyl_Sys_Id_Dt varchar2
                                    ,pCyl_Sys_Id_Prs VARCHAR2                                    
                                   ) return number;
                                   
    function fGet_CostLess_QL_CO_Frwd( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2;
                                   
    function fGet_NSBC_SP( 
                            pCyl_Sys_Id_Dt varchar2 -- Product Final 
                            ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                           ) return varchar2;
                                   
    function fGet_Addl_NSBC_Loss( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2;                                   
                                   
    function fGet_Extra_Yarn_Persen( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2;
                                   
    function fGet_Cost_Of_Extra_Yarn_Persen( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2;
                                   
    function fGet_Dom_Cost_AX_Grd_Only( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number;
                                   
    function fGetDomCost_AXGrdOnly_WExtrCst( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number;                                   
    
end pkg_yarn_marketing; 
/
