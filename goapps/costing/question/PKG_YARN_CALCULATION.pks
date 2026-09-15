CREATE OR REPLACE package MGTAPPS.pkg_yarn_calculation is
    vIdMkt varchar2(30):='20210800119';
    vIdVal varchar2(30):='20210800120';

    vPeriodCostingGrpItem varchar2(100):='PERIOD_DEFAULT_GROUP_ITEM';
    vPeriodCostingMb varchar2(100):='PERIOD_DEFAULT_MB';
    
    vREFRESH_CST_YARN varchar2(100) := 'REFRESH_CST_YARN';
    
    vRecordProcess number;
    
    function fPeriodCostingGrpItem return varchar2;
    function fPeriodCostingMb  return varchar2;    
    
    function fPrsIDMkt return varchar2;
    
    function fPrsIDVal return varchar2;    
    
    procedure pDelYarnErrLog;
    
    procedure pSetRecordProcess(pRecordProcess number);
    
    function fGetNmYarnPrs(pPRS_TYPE varchar2) return varchar2;
    
    function fGetMstYarnData(pCMY_SYS_ID varchar2) return mgtapps.cst_mst_yarn%rowtype;
    
    function fGetRecordProcess return number;
    
    function fGetPrm_Refresh_CST_YARN return varchar2;

    function fGet_CMY_SYS_ID(pCYL_SYS_ID varchar2) return varchar2;

    function fGetItemGrpNm(pCGCH_CGH_SYS_ID varchar2) return varchar2; 
    
    function fGetItemGrpDesc(pCGCH_CGH_SYS_ID varchar2) return varchar2;

    function fGetItemGrpRate(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function fGetItemGrpMktRate(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function fGetItemGrpMktLc(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function fGetItemGrpValLC(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function fGetItemGrpValConsump(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function fGetItemGrpVal_StockRate(pCGCH_CGH_SYS_ID varchar2) return number;
    
    function get_CYC_DATA_VALUE(pCYC_SYS_ID varchar2,pCYL_SYS_ID varchar2,pCYT_SYS_ID varchar2) return varchar2;
    
    function fGetDefaultPeriodCosting(pSts number) return date;
    
    function get_LEFT_NO(pSts number,pParamValue varchar2,pPrsType varchar2)    return varchar2;
    
    function getSql_M_B_MST(pCYC_SYS_ID varchar2) return varchar2;
    
    -- Function For Master
    function fGetCaptpackName(pCMBBC_SYS_ID varchar2) return varchar2;    
    function fGetShadeCodeMarketingLink(pMarketingCostLink varchar2) return varchar2;        
    function fGetShadeNameMarketingLink(pMarketingCostLink varchar2) return varchar2;    
    -- End Function For Master
    
    function fGetLeftNo(pCYL_TYPE varchar2,pCYL_PRS_TYPE varchar2) return number;
    
    procedure updDefaultPeriodCosting(pSts number,pmst_params mst_params%rowtype);

    procedure pSetREFRESH_CST_YARN(pVal varchar2);
    procedure pDelRefFilterHdr(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2);
    procedure pRefFilter(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null);
    procedure pDelRefFilter(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null);
    function fCkFilterData(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null) return number;
    function fNeedOperator(pCYFH_PRS_NAME varchar2) return number;
    
    procedure pValuationProcess(pUserId varchar2,pErrMsg out varchar2);
    procedure process(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null);
    
    
    
    -- ** LEFT ** --    
    function get_YarnLeftData(pCYL_SYS_ID varchar2,pCYL_LEFT_NO number) return cst_yarn_left%rowtype;
    function get_CYL_SYS_ID(pLEFT_NO number,pCYL_PRS_TYPE varchar2) return varchar2;
    procedure pDelLeft(pData CST_YARN_LEFT%rowtype);
    procedure copy_formula(pUserId varchar2,pCYL_SYS_ID_From varchar2,pCYL_SYS_ID_To varchar2,pCYC_PRS_TYPE varchar2,pErrMsg out varchar2);    
    procedure copy_formula_valuation(pUserId varchar2,pCYL_SYS_ID_From varchar2,pCYL_SYS_ID_To varchar2,pErrMsg out varchar2);
    procedure pAddLeft(pData MGTAPPS.CST_YARN_LEFT%rowtype);
    
    PROCEDURE pCopy_113_118(pUser_id varchar2
                            ,pCOPY_CYC_CYL_SYS_ID varchar2 --CST_YARN_LEFT.COPY_CYC_CYL_SYS_ID 
							,pLEFT_CYL_SYS_ID varchar2 --:CST_YARN_LEFT.CYL_SYS_ID
                            ,pCYC_PRS_TYPE varchar2
                            ,pCYL_TYPE_D varchar2 
							,pErrMsg out varchar2);        
    -- ** LEFT ** --
    
    function get_CYC_SYS_ID(pLEFT_NO number, pTOP_NO number,pPRS_TYPE varchar2)    return varchar2;
    function get_GROUP_CODE(pCGH_SYS_ID varchar2) return varchar2;
    
    -- ** TOP ** --
    function getTotTop(pCYT_PRS_TYPE varchar2) return number;
           
    function get_TOP_LABLE(pCYT_SYS_ID varchar2,pCYT_TOP_NO number) return varchar2;  
    function get_TOP_LABLE1(pCYT_PRS_TYPE varchar2,pCYT_TOP_NO number) return varchar2;    
    function get_CYT_SYS_ID(pTOP_NO number,pCYT_PRS_TYPE varchar2) return varchar2;
    procedure copy_top(--pCYL_SYS_ID_To varchar2,
                        pUserId varchar2
                       ,pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                       ,pRecYarnCalFrom cst_YARN_CALCULATION%rowtype
                       ,pCYL_SYS_ID_New out varchar2
                       ,pCYC_SYS_ID_Dest varchar2 default null
                       ,pCYC_PRS_TYPE varchar2
                       ,pErrMsg out varchar);
    
    procedure pDelTop(pData CST_YARN_TOP%rowtype);
    
    procedure pAddTop(pUserId varchar2,pData MGTAPPS.CST_YARN_TOP%rowtype);    
    -- ** TOP ** --
    
    procedure pDelYarnCalculation(pCYC_SYS_ID CST_YARN_CALCULATION.CYC_SYS_ID%type);    
    
    procedure pClearFormula
    (
    pCYC_SYS_ID CST_YARN_CALCULATION.CYC_SYS_ID%type
    );
    
    function getProductGradeType(pCMPG_SYS_ID varchar2) return varchar2;
    
    procedure pUpdMstCalcCost(pCyc_Sys_id varchar2,pcyc_prs_type varchar2,pErrMsg out varchar2);
    
    procedure pRefrshValParam(pUserID varchar2,pPRS_TYPE varchar2);
    
    function fGetMbHeadDt(pCMBH_SYS_ID varchar2) return CST_MST_BATCH_HEAD%rowtype;
    
    procedure pLoadMbMstToSpin(pBATCH_SPIN CST_MST_BATCH_SPIN%rowtype,pErrMsg out varchar2);
    
    procedure updYarnPrsMbSpin
                (pCMBS_SYS_ID varchar2
                 ,pCMBS_CMBH_SYS_ID varchar2
                 ,pCMBS_CODE varchar2);

    function fIsCstShadeCode(pShadeCode varchar2,pShadeName varchar2) return boolean;
    
    function fIsProdValuation(pCYL_SYS_ID varchar2) return boolean;
    
    function fGetRM_PTY_BO(pCYL_SYS_ID varchar2) return varchar2;
    
    function fIs_Final_Setup(pCYL_SYS_ID varchar2) return varchar2;    
end pkg_yarn_calculation; 
/
