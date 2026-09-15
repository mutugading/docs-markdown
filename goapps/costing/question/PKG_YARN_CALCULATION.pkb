CREATE OR REPLACE package body MGTAPPS.pkg_yarn_calculation is    
    function fPeriodCostingGrpItem return varchar2 is
    begin
        return vPeriodCostingGrpItem;    
    end;
    function fPeriodCostingMb  return varchar2 is
    begin
        return vPeriodCostingMb;
    end;
    function fPrsIDMkt return varchar2 is
    begin
        return vIdMkt;
    end fPrsIDMkt;
    
    function fPrsIDVal return varchar2 is
    begin
        return vIdVal;
    end;

    procedure pDelYarnErrLog is
    begin
        delete from CST_YARN_ERR_LOG; commit;
    end;

    procedure pSetRecordProcess(pRecordProcess number) is
    begin
        vRecordProcess := pRecordProcess;
    end;
    
    function fGetRecordProcess return number is
    begin 
        return vRecordProcess;
    end;    
    
    function fGetPrm_Refresh_CST_YARN return varchar2 is
    begin
        return vREFRESH_CST_YARN;
    end;
    
    function fGetNmYarnPrs(pPRS_TYPE varchar2) return varchar2 is
       vMPD_NAME MST_PARAM_DATA.MPD_NAME%type;
    begin
        select MPD_NAME into vMPD_NAME 
        from MST_PARAM_DATA
        where MPD_SYS_ID =  pPRS_TYPE;
        
        return vMPD_NAME;
    exception
        when no_data_found then
            return null;
    end;
    
    function fGetMstYarnData(pCMY_SYS_ID varchar2) return mgtapps.cst_mst_yarn%rowtype is
       vcst_mst_yarn mgtapps.cst_mst_yarn%rowtype;
    begin
        select * into vcst_mst_yarn
        from mgtapps.cst_mst_yarn
        where CMY_SYS_ID =  pCMY_SYS_ID;
        
        return vcst_mst_yarn;
    exception
        when no_data_found then
            return null;
    end fGetMstYarnData;
    
    function fGet_CMY_SYS_ID(pCYL_SYS_ID varchar2) return varchar2 is
        vCMY_SYS_ID varchar2(30);
    begin
        select CYL_CMY_SYS_ID into vCMY_SYS_ID  
        from CST_YARN_LEFT l
        where CYL_SYS_ID = pCYL_SYS_ID;
        
        return vCMY_SYS_ID;
    exception
        when no_data_found then 
            return null;
    end fGet_CMY_SYS_ID;
    
    function fGetItemGrpNm(pCGCH_CGH_SYS_ID varchar2) return varchar2 is        
        vReturn varchar2(1000);
    begin
        vReturn := null;
        begin
            select CGH_GROUP_CODE into vReturn
            from mgtapps.CST_GRP_HEAD
            where CGH_SYS_ID = pCGCH_CGH_SYS_ID;
        exception
            when no_data_found then
                null;
        end;  
        return vReturn;
    end fGetItemGrpNm;
    
    function fGetItemGrpDesc(pCGCH_CGH_SYS_ID varchar2) return varchar2 is        
        vReturn varchar2(1000);
    begin
        vReturn := null;
        begin
            select CGH_DESCRIPTION into vReturn
            from mgtapps.CST_GRP_HEAD
            where CGH_SYS_ID = pCGCH_CGH_SYS_ID;
        exception
            when no_data_found then
                null;
        end;  
        return vReturn;
    end fGetItemGrpDesc;            
    
    function fGetItemGrpRate(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        
        if vCGCH_PERIOD_YEAR is not null and vCGCH_PERIOD_MONTH is not null then
            begin 
                select 
                       case when nvl(CGCH_MARKET_RATE2_FIX ,0) <> 0 then CGCH_MARKET_RATE2_FIX  
                       else nvl(CGCH_LANDED_COST,0) 
                       end CGCH_LANDED_COST--,CGCH_MARKET_RATE1_FIX 
                       into vReturn
                from mgtapps.CST_GRP_CONSUMP_HEAD
                where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
                and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
                and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
            exception
                when no_data_found then
                    null; 
            end;
        else
            begin
                select CGCHD_LANDED_COST
                into vReturn
                from mgtapps.CST_GRP_CONSUMP_HEAD_DFLT
                where CGCHD_CGH_SYS_ID = pCGCH_CGH_SYS_ID;
            exception
                when no_data_found then null;
            end;
        end if;
        
        return vReturn;
    end fGetItemGrpRate;        
    
    -- Marketing 
    function fGetItemGrpMktRate(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        if vCGCH_PERIOD_MONTH is not null and vCGCH_PERIOD_YEAR is not null then
        begin 
            select case when CGCH_MARKET_RATE1_FIX is not null then CGCH_MARKET_RATE1_FIX
                   else CGCH_MARKET_RATE1 end 
                into vReturn
            from mgtapps.CST_GRP_CONSUMP_HEAD
            where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
            and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
            and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
        exception
            when no_data_found then
                null; 
        end;
        end if;
        
        return vReturn;
    end fGetItemGrpMktRate;    
    
    function fGetItemGrpMktLc(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        if vCGCH_PERIOD_MONTH is not null and vCGCH_PERIOD_YEAR is not null then
        begin 
            select case when CGCH_MARKET_RATE2_FIX is not null then CGCH_MARKET_RATE2_FIX 
                   else CGCH_MARKET_RATE2 end into vReturn
            from mgtapps.CST_GRP_CONSUMP_HEAD
            where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
            and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
            and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
        exception
            when no_data_found then
                null; 
        end;
        end if;
        
        return vReturn;
    end fGetItemGrpMktLc;
    -- Marketing 
    
   -- Valuation   
   function fGetItemGrpValLC(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        if vCGCH_PERIOD_MONTH is not null and vCGCH_PERIOD_YEAR is not null then
        begin 
            select CGCH_LANDED_COST into vReturn
            from mgtapps.CST_GRP_CONSUMP_HEAD
            where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
            and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
            and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
        exception
            when no_data_found then
                null; 
        end;
        end if;
        
        return vReturn;
    end fGetItemGrpValLC;    
    
    function fGetItemGrpValConsump(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        if vCGCH_PERIOD_MONTH is not null and vCGCH_PERIOD_YEAR is not null then
        begin 
            select CGCH_CONSUMP into vReturn
            from mgtapps.CST_GRP_CONSUMP_HEAD
            where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
            and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
            and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
        exception
            when no_data_found then
                null; 
        end;
        end if;
        
        return vReturn;
    end fGetItemGrpValConsump;    
    
    function fGetItemGrpVal_StockRate(pCGCH_CGH_SYS_ID varchar2) return number is
        vCGCH_PERIOD_YEAR number;
        vCGCH_PERIOD_MONTH number;
        vReturn number;
        vErrmsg varchar2(1000);
    begin
        vReturn := 0;
        
        vCGCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'YYYY');
        vCGCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingGrpItem,vErrmsg),'YYYYMM'),'MM');
        if vCGCH_PERIOD_MONTH is not null and vCGCH_PERIOD_YEAR is not null then
        begin 
            select CGCH_STOCK_RATE into vReturn
            from mgtapps.CST_GRP_CONSUMP_HEAD
            where CGCH_CGH_SYS_ID = pCGCH_CGH_SYS_ID
            and CGCH_PERIOD_YEAR = vCGCH_PERIOD_YEAR
            and CGCH_PERIOD_MONTH = vCGCH_PERIOD_MONTH;
        exception
            when no_data_found then
                null; 
        end;
        end if;
        
        return vReturn;
    end fGetItemGrpVal_StockRate;    
    -- Valuation 
    
    function fGetDefaultPeriodCosting(pSts number) return date is
        vmst_params mst_params%rowtype;
    begin
        vmst_params := null;
        if pSts = 1 then
        begin
            select * into vmst_params 
            from mgtapps.mst_params
            where param_id = vPeriodCostingGrpItem;
        exception
            when no_data_found then
                null;
        end;
        elsif pSts = 2 then
        begin
            select * into vmst_params 
            from mgtapps.mst_params
            where param_id = vPeriodCostingMb;
        exception
            when no_data_found then
                null;
        end;
        end if;
        
        if vmst_params.PARAM_VALUE is not null then
        begin
            return to_date(vmst_params.PARAM_VALUE,'YYYYMM');
        exception
            when others then 
                return null;
        end;    
        else return null;
        end if;
    end fGetDefaultPeriodCosting;
    
    function getSql_M_B_MST(pCYC_SYS_ID varchar2) return varchar2 is
        vSql varchar2(1000);
    begin 
        vSql := null;
        for recMst in (               
             
            select * from MGTAPPS.CST_YARN_M_B_MST
            where CYMBM_CYC_SYS_ID = pCYC_SYS_ID
            and nvl(CYMBM_STS_CK,0) = 1 
            order by CYMBM_COLUMN_ID
                                                                        
        ) loop   
            vSql := 'select '||recMst.CYMBM_COLUMN_NAME||' from MGTAPPS.CST_MST_BATCH_HEAD '
                            ||' where CMBH_SYS_ID = '''||recMst.CYMBM_CMBH_SYS_ID||''' ';
                                        
        end loop;
        return vSql;
    end;
    
    -- Function For Master
    function fGetCaptpackName(pCMBBC_SYS_ID varchar2) return varchar2 is
        vCMBBC_TYPE mgtapps.CST_MST_BOX_BOBIN_COST.CMBBC_TYPE%type;
    begin
     select  CMBBC_TYPE into vCMBBC_Type 
     from mgtapps.CST_MST_BOX_BOBIN_COST 
     where CMBBC_SYS_ID = pCMBBC_SYS_ID;
     
     return vCMBBC_Type;
    exception
        when no_data_found then
            return null; 
    end;
    
    
    function fGetShadeCodeMarketingLink(pMarketingCostLink varchar2) return varchar2 is
        vCYL_SHADE_CODE varchar2(300);
    begin        
        select CYL_SHADE_CODE into vCYL_SHADE_CODE 
        from cst_yarn_left l
        where upper(CYL_MARKETING_COST_LINK) = pMarketingCostLink
        and rownum = 1;  
        
        return vCYL_SHADE_CODE;
    exception
        when no_data_found then
            return null;
    end;    
    
    function fGetShadeNameMarketingLink(pMarketingCostLink varchar2) return varchar2 is
        vCYL_SHADE_NAME varchar2(300);
    begin        
        select CYL_SHADE_NAME into vCYL_SHADE_NAME 
        from cst_yarn_left l
        where upper(CYL_MARKETING_COST_LINK) = pMarketingCostLink
        and rownum = 1;  
        
        return vCYL_SHADE_NAME;
    exception
        when no_data_found then
            return null;
    end;
    -- end Function For Master
    
    function fGetLeftNo(pCYL_TYPE varchar2,pCYL_PRS_TYPE varchar2) return number is
        vTmp number;
        vMax number;
        vReturn number;
    begin
        select max(to_number(CYL_LEFT_NO)) into vMax 
        from mgtapps.cst_yarn_left 
        where CYL_PRS_TYPE = pCYL_PRS_TYPE;
        
        if pCYL_TYPE = 'POY' then
           for recDt in 1..vMax loop
           begin
            select 'x' into vTmp
            from mgtapps.cst_yarn_left 
            where CYL_PRS_TYPE = pCYL_PRS_TYPE
            and CYL_LEFT_NO = recDt;
           exception
            when no_data_found then
                vReturn := recDt;
                exit;
            when others then
                null;
           end;
           vReturn := recDt+1;
           end loop;
        else 
            vReturn := vMax + 1;
        end if;
        
        return vReturn;
        
    end fGetLeftNo; 
    
    procedure updDefaultPeriodCosting(pSts number,pmst_params mst_params%rowtype) is
    begin
        --if pSts = 1 then
            update mst_params
                set PARAM_VALUE = pmst_params.PARAM_VALUE 
                    , MODIFIED_BY = pmst_params.MODIFIED_BY
                    , MODIFIED_TIMESTAMP = pmst_params.MODIFIED_TIMESTAMP
            where param_id = vPeriodCostingGrpItem;
        --elsif pSts = 2 then
            update mst_params
                set PARAM_VALUE = pmst_params.PARAM_VALUE 
                    , MODIFIED_BY = pmst_params.MODIFIED_BY
                    , MODIFIED_TIMESTAMP = pmst_params.MODIFIED_TIMESTAMP
            where param_id = vPeriodCostingMb;
        --end if;
        commit;        

    end updDefaultPeriodCosting;
    
    procedure pSetREFRESH_CST_YARN(pVal varchar2) is
    begin
        update mst_params
            set PARAM_VALUE = pVal
        where PARAM_ID = 'REFRESH_CST_YARN';
        commit;
    end pSetREFRESH_CST_YARN;
    
    procedure pDelRefFilterHdr(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2) is
    begin
        delete from CST_YARN_FILTER_HDR
        where CYFH_USER_ID = pUserId
        and CYFH_PRS_TYPE = pPRS_TYPE
        and nvl(CYFH_IS_EDIT,'N') = nvl(pCYFH_IS_EDIT,'N');      
        commit;  
    end;
    
    procedure pDelRefFilter(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null) is
    begin
        delete from CST_YARN_FILTER
        where CYF_USER_ID = pUserId
        and CYF_PRS_TYPE = pPRS_TYPE
        and nvl(CYF_IS_EDIT,'N') = nvl(pCYFH_IS_EDIT,'N');
        commit;                
    end;
    
    function fNeedOperator(pCYFH_PRS_NAME varchar2) return number is
    begin
        if upper(pCYFH_PRS_NAME) in ('VALUE OF 118.VB1-DEL COST','RM RATE','ALL DATA') then
            return 0;
        end if;
        
        return 1;
    end fNeedOperator; 
    
    function fCkFilterData(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null) return number is
        vTmp number;
    begin
        select 'x' into vTmp 
        from CST_YARN_FILTER
        where CYF_USER_ID=pUserId 
        and CYF_PRS_TYPE=pPRS_TYPE 
        and nvl(CYF_IS_EDIT,'N') =  nvl(pCYFH_IS_EDIT,'N');
    exception
        when no_data_found then
            return 0;
        when others then
            return 1;        
    end fCkFilterData;
    
    procedure pRefFilter(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null) is
        vWhere varchar2(32767);
        vSql  varchar2(32767);
        vSql_Ins  varchar2(32767);
        vErrPrs  varchar2(32767);
        vNoRec number;
        vPRS_TYPE_name varchar2(150);
        vStsAllData boolean:=false;
        
        vStsRownum boolean:=false;
        vWhere_Rownum varchar2(32767);
        vSts_GetTop55 boolean := false;
    begin
        --insert into ` values (pUserId||' Cek '||pUserId||' '||pPRS_TYPE||' '||pCYFH_IS_EDIT);commit;
        pDelRefFilter(pUserId,pPRS_TYPE,pCYFH_IS_EDIT);
        
        vPRS_TYPE_name := fGetNmYarnPrs(pPRS_TYPE);
        
        vWhere := null;
        begin
        vNoRec := 0;
        for recWhr in (                    
        
            select * from mgtapps.CST_YARN_FILTER_HDR                        
            where CYFH_USER_ID = pUserId
            and CYFH_PRS_TYPE = pPRS_TYPE
            and nvl(CYFH_IS_EDIT,'N') = nvl(pCYFH_IS_EDIT,'N')
            
        )loop
            vNoRec := vNoRec +1;
            if vNoRec > 1 then
                vWhere := vWhere ||' '||recWhr.CYFH_WHERE_CONDITION;
            end if; 
                        
            
            if recWhr.CYFH_ITEM_NAME = 'Value AX-wt (30)' then
                if recWhr.CYFH_OPERATOR in ('>','<','>=','<=','=') then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                                        select cyc_cyl_sys_id from cst_yarn_calculation
                                        where cyc_top_no =  30
                                        and to_number(nvl(CYC_DATA_VALUE,0)) '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' 
                                        and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';                                                                     
                elsif recWhr.CYFH_OPERATOR = 'Is Null' then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    select cyc_cyl_sys_id from cst_yarn_calculation
                    where cyc_top_no =  30
                    and CYC_DATA_VALUE is null 
                    and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';     
                    
                elsif upper(recWhr.CYFH_OPERATOR) = 'BETWEEN' then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    select cyc_cyl_sys_id from cst_yarn_calculation
                    where cyc_top_no =  30
                    and to_number(nvl(CYC_DATA_VALUE,0)) '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' and  '''||recWhr.CYFH_ITEM_VALUE_2||''' 
                    and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';                                                              
                end if;
            elsif recWhr.CYFH_ITEM_NAME = 'Value of 118.VB1-Del Cost' then
                if recWhr.CYFH_OPERATOR in ('>','<','>=','<=','=') then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                                        select cyc_cyl_sys_id from cst_yarn_calculation
                                        where cyc_top_no =  118
                                        and to_number(nvl(CYC_DATA_VALUE,0)) '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' 
                                        and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';                                                                     
                elsif recWhr.CYFH_OPERATOR = 'Is Null' then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    select cyc_cyl_sys_id from cst_yarn_calculation
                    where cyc_top_no =  118
                    and CYC_DATA_VALUE is null 
                    and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';                                                            
                end if;    
            elsif recWhr.CYFH_ITEM_NAME = 'RM Rate' then
                if recWhr.CYFH_OPERATOR in ('>','<','>=','<=','=') then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                                        select cyc_cyl_sys_id from cst_yarn_calculation
                                        where cyc_top_no =  55
                                        and to_number(nvl(CYC_DATA_VALUE,0)) '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' 
                                        and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';
                elsif recWhr.CYFH_OPERATOR = 'Is Null' then
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    select cyc_cyl_sys_id from cst_yarn_calculation
                    where cyc_top_no =  55
                    and CYC_DATA_VALUE is null 
                    and CYC_PRS_TYPE = '''||pPRS_TYPE||''') ';                    
                end if;     
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'ALL DATA' then
                vWhere := vWhere ||' CYL_SYS_ID in ( 
                                     select cyl_sys_id from cst_yarn_left
                                     where CYL_PRS_TYPE = '''||pPRS_TYPE||''') ';
                vStsAllData := true;                                      
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'CHECK DATA VALUE IN TOP NO' then
                if upper(recWhr.CYFH_OPERATOR) in ('LIKE','NOT LIKE') then
                    recWhr.CYFH_ITEM_VALUE := '%'||recWhr.CYFH_ITEM_VALUE||'%';
                end if;
                
                vWhere := vWhere 
                          ||' CYL_SYS_ID in (                 
                                select CYC_CYL_SYS_ID from cst_yarn_calculation t
                                where t.CYC_DATA_VALUE '
                                ||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' ) ';
                                                                                                
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'CHECK NULL VALUE IN TOP NO' then
                 
                if upper(recWhr.CYFH_OPERATOR) in ('IN','NOT IN') then 
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    
                                        SELECT cyl_sys_id
                                          FROM (SELECT 1 rec_no, t.*
                                                  FROM cst_yarn_top t
                                                 WHERE cyt_prs_type = '''||pPRS_TYPE||''' AND t.cyt_top_no '||recWhr.CYFH_OPERATOR||' ('||recWhr.CYFH_ITEM_VALUE||') ) t,
                                               (SELECT 1 rec_no, l.*
                                                  FROM cst_yarn_left l
                                                 WHERE cyl_prs_type = '''||pPRS_TYPE||''') l,
                                               (SELECT *
                                                  FROM cst_yarn_calculation
                                                 WHERE cyc_prs_type = '''||pPRS_TYPE||''' AND cyc_top_no '||recWhr.CYFH_OPERATOR||' ('||recWhr.CYFH_ITEM_VALUE||') ) c
                                         WHERE t.rec_no = l.rec_no AND cyl_sys_id = cyc_cyl_sys_id(+)
                                               AND cyc_data_value IS NULL                                        
                                         
                                         ) ';
                else 
                    vWhere := vWhere ||' CYL_SYS_ID in ( 
                    
                                        SELECT cyl_sys_id
                                          FROM (SELECT 1 rec_no, t.*
                                                  FROM cst_yarn_top t
                                                 WHERE cyt_prs_type = '''||pPRS_TYPE||''' AND t.cyt_top_no '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' ) t,
                                               (SELECT 1 rec_no, l.*
                                                  FROM cst_yarn_left l
                                                 WHERE cyl_prs_type = '''||pPRS_TYPE||''') l,
                                               (SELECT *
                                                  FROM cst_yarn_calculation
                                                 WHERE cyc_prs_type = '''||pPRS_TYPE||''' AND cyc_top_no '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' ) c
                                         WHERE t.rec_no = l.rec_no AND cyl_sys_id = cyc_cyl_sys_id(+)
                                               AND cyc_data_value IS NULL                                        
                                         
                                         ) ';                
                                                 
                end if;    
                                     
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'CYL_CUSTOMER' then
                vWhere := vWhere ||' CYL_SYS_ID in (                                 

                 select CYLC_CYL_SYS_ID
                 from mgtapps.cst_mst_cust_data,mgtapps.CST_YARN_LEFT_CUST
                 where CMCD_SYS_ID = CYLC_CMCD_SYS_ID
                 and CMCD_SYS_ID = '''||recWhr.CYFH_ITEM_VALUE||''' 
                                  
                                     ) ';         
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'PRODUCT VALID IN MARKETING' then
                vWhere := vWhere ||' CYL_SYS_ID in (                                 

                 select CYL_SYS_ID from cst_yarn_left where CYL_IS_VALID_PRD = ''Y''
                                  
                                     ) ';                                     
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'NOT YET TRANSFERED INTO VALUATION' then
                vWhere :=    vWhere 
                            ||' CYL_SYS_ID in ( '
                            ||'    SELECT cyl_sys_id
                                   FROM cst_yarn_left
                                   WHERE cyl_prs_type = '''||vIdMkt||'''
                                   AND cyl_sys_id NOT IN 
                                        (
                                           SELECT cylv_cyl_sys_id
                                           FROM cst_yarn_left_valuation b
                                         )
                             ) ';
                
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'PRODUCT WITH ROW MATERIAL POY' then
            
                vWhere :=    vWhere 
                                ||' CYL_SYS_ID in ( '
                                ||'    SELECT cyl_sys_id
                                      FROM mgtapps.cst_yarn_left l,
                                           (SELECT cyc_left_no left_no_prod, cyl_left_no left_no_rm,
                                                   cyl_type cyl_type_rm
                                              FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                             WHERE cyc_top_no = 20
                                               AND cyc_formula_type = ''Raw_Material''
                                               AND c.cyc_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
                                               AND UPPER (cyc_data_value) = UPPER (cyl_marketing_cost_link)
                                               AND cyl_type = ''POY'') dt
                                     WHERE cyl_left_no = left_no_prod
                                     and CYL_PRS_TYPE = '''||pPRS_TYPE||'''
                                 ) ';

            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'PRODUCT WITH ROW MATERIAL PTY' then
            
                vWhere :=    vWhere 
                                ||' CYL_SYS_ID in ( '
                                ||'    SELECT cyl_sys_id
                                      FROM mgtapps.cst_yarn_left l,
                                           (SELECT cyc_left_no left_no_prod, cyl_left_no left_no_rm,
                                                   cyl_type cyl_type_rm
                                              FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                             WHERE cyc_top_no = 20
                                               AND cyc_formula_type = ''Raw_Material''
                                               AND c.cyc_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
                                               AND UPPER (cyc_data_value) = UPPER (cyl_marketing_cost_link)
                                               AND cyl_type = ''PTY'') dt
                                     WHERE cyl_left_no = left_no_prod
                                     and CYL_PRS_TYPE = '''||pPRS_TYPE||'''
                                 ) ';                                             
                                                     
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'PRODUCT WITH ROW MATERIAL NOT POY OR PTY' then
            
                vWhere :=    vWhere 
                                ||' CYL_SYS_ID in ( '
                                ||'    SELECT cyl_sys_id
                                      FROM mgtapps.cst_yarn_left l,
                                           (SELECT cyc_left_no left_no_prod, cyl_left_no left_no_rm,
                                                   cyl_type cyl_type_rm
                                              FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                             WHERE cyc_top_no = 20
                                               AND cyc_formula_type = ''Raw_Material''
                                               AND c.cyc_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
                                               AND UPPER (cyc_data_value) = UPPER (cyl_marketing_cost_link)
                                               AND cyl_type not in (''POY'',''PTY'')) dt
                                     WHERE cyl_left_no = left_no_prod
                                     and CYL_PRS_TYPE = '''||pPRS_TYPE||'''
                                 ) ';
                                 
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'RAW MATERIAL TYPE PRODUCT' then
            
                vWhere :=   vWhere ||
                            ' CYL_SYS_ID in (  
                                SELECT distinct CYL_SYS_ID
                                from    mgtapps.cst_yarn_left yl
                                        ,mgtapps.cst_yarn_calculation yc
                                        , mgtapps.cst_yarn_rm_hdr rh
                                        , mgtapps.cst_yarn_rm_captive rc
                                where CYL_SYS_ID = CYC_CYL_SYS_ID
                                and CYC_PRS_TYPE = CYC_PRS_TYPE    
                                and CYC_TOP_NO = 20                
                                and CYC_SYS_ID =   CYRH_CYC_SYS_ID     
                                and CYRH_SYS_ID = CYRC_CYRH_SYS_ID ';                                
                                
                if  upper(recWhr.CYFH_OPERATOR) in ('=','<>') then
                    vWhere :=   vWhere ||' and CYRC_YARN_TYPE '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' ) ';
                elsif  upper(recWhr.CYFH_OPERATOR) in ('LIKE','NOT LIKE') then
                    vWhere :=   vWhere ||' and CYRC_YARN_TYPE '||recWhr.CYFH_OPERATOR||' ''%'||recWhr.CYFH_ITEM_VALUE||'%'' ) ';
                elsif  upper(recWhr.CYFH_OPERATOR) in ('IN','NOT IN') then
                    vWhere :=   vWhere ||' and CYRC_YARN_TYPE '||recWhr.CYFH_OPERATOR||' ('||recWhr.CYFH_ITEM_VALUE||') ) ';
                end if;                                 
                                 
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'ROW NUM' then
                vWhere := vWhere ||' 1=1 ';
                if recWhr.CYFH_OPERATOR in ('=','>','<','>=','<=') then
                    vWhere_Rownum := ' NO_REC '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' ';
                elsif upper(recWhr.CYFH_OPERATOR) = 'BETWEEN' then
                    vWhere_Rownum := ' NO_REC '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' and  '''||recWhr.CYFH_ITEM_VALUE_2||''' ';
                end if;
                vStsRownum :=true;
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'EXCLUDE MARKETING PRODUCT' then                   
                vWhere := vWhere ||' 1=1 ';                                                     
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'DELPACK NAME' then
            
                vWhere :=   vWhere ||
                ' CYL_SYS_ID in (  
                    select CYL_SYS_ID 
                    from    mgtapps.cst_yarn_calculation a
                            ,mgtapps.cst_yarn_left b
                    where a.CYC_CYL_SYS_ID = CYL_SYS_ID 
                    and CYL_PRS_TYPE = '''||pPRS_TYPE||'''
                    and a.cyc_top_no = 43';
                    
                    if  upper(recWhr.CYFH_OPERATOR) in ('=') then
                        vWhere :=   vWhere ||' and upper(CYC_DATA_VALUE) '||recWhr.CYFH_OPERATOR||' upper('''||recWhr.CYFH_ITEM_VALUE||''') ) ';
                    end if;                                 
            elsif Upper(recWhr.CYFH_ITEM_NAME) = 'GET RM CHILD FROM TOP 55' then
                vWhere := vWhere ||' 1=1 '; 
                vSts_GetTop55 := true;         
            /*elsif Upper(recWhr.CYFH_ITEM_NAME) = 'LOV_DTL_USED_REFF_TOP_20' then
                            
                vWhere :=   vWhere ||'
                   AND CYL_SYS_ID in (select distinct cyl_sys_id 
                    from    cst_yarn_calculation a
                            ,cst_yarn_left b
                    where CYL_SYS_ID = a.CYC_CYL_SYS_ID
                    and cyc_sys_id in (
                    select CYC_SYS_ID
                    from    cst_yarn_calculation a
                            ,cst_yarn_rm_hdr b
                            ,cst_yarn_rm_captive c
                    where CYC_PRS_TYPE = :pprs_type
                    and CYC_TOP_NO = 20
                    and CYC_SYS_ID = CYRH_CYC_SYS_ID
                    and CYC_FORMULA_TYPE = ''Raw_Material''
                    and CYRH_TYPE = ''Captive Cost''
                    and CYRH_SYS_ID = CYRC_CYRH_SYS_ID
                    and CYRC_CYL_SYS_ID = '''||recWhr.CYFH_ITEM_VALUE||'''
                    )
                   )';*/
                                        
            else           
                if recWhr.CYFH_ITEM_NAME in 
                    (
                        'Period ORION Reff (YYYY-MM)'
                        ,'Period View Data (YYYY-MM)'
                    ) then
                    vWhere := vWhere ||' 1 = 1 ';
                else
                    if recWhr.CYFH_ITEM_NAME = 'CYL_CREATED_TIMESTAMP' then
                       recWhr.CYFH_ITEM_NAME := 'to_char(CYL_CREATED_TIMESTAMP,''YYYY-MM-DD HH24:MI'')'; 
                    end if;
                    
                    if upper(recWhr.CYFH_OPERATOR) in ('LIKE','NOT LIKE') then
                        vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' ''%'||recWhr.CYFH_ITEM_VALUE||'%'' ';
                    elsif recWhr.CYFH_OPERATOR = '=' then
                        vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' ';
                    elsif upper(recWhr.CYFH_OPERATOR) = 'IN' then
                        vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' ('||recWhr.CYFH_ITEM_VALUE||') ';    
                    elsif upper(recWhr.CYFH_OPERATOR) = 'NOT IN' then
                        vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' ('||recWhr.CYFH_ITEM_VALUE||') ';                    
                    elsif upper(recWhr.CYFH_OPERATOR) = 'BETWEEN' then
                        vWhere := vWhere ||' '  ||recWhr.CYFH_ITEM_NAME||' '
                                                ||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' and  '''||recWhr.CYFH_ITEM_VALUE_2||''' ';
                    else                        
                        if recWhr.CYFH_OPERATOR in ('>','<','>=','<=') then
                            vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' '||recWhr.CYFH_ITEM_VALUE||' ';
                        else                    
                            vWhere := vWhere ||' '||recWhr.CYFH_ITEM_NAME||' '||recWhr.CYFH_OPERATOR||' '''||recWhr.CYFH_ITEM_VALUE||''' ';
                        end if;
                    end if;
                
                end if;                
            end if;  
            
            /*if Upper(recWhr.CYFH_ITEM_NAME) = 'ALL PRODUCT REFF BY TOP 20' then
                delete from MGTAPPS.CST_YARN_FILTER_TOP_20
                where CYFT2_USER_ID = pUserId
                and CYFT2_PRS_TYPE = pPRS_TYPE
                and CYFT2_IS_EDIT = pCYFH_IS_EDIT;
            end if;*/                      
            
        end loop;
                        
        if vWhere is not null then
            vWhere :=   '   where '||vWhere||' and CYL_PRS_TYPE  = '''||pPRS_TYPE||''' ';                        
        end if;
        
            
        
        if vWhere is not null then
            vSql_Ins  := 'insert into CST_YARN_FILTER(CYF_USER_ID, CYF_CYL_SYS_ID,CYF_PRS_TYPE,CYF_PRS_NAME ,CYF_IS_EDIT) ';
            if vStsRownum then
                vSql := 'select   '''||pUserId||''' CYF_USER_ID '
                    ||'         ,CYL_SYS_ID '
                    ||'         ,'''||pPRS_TYPE||''' CYF_PRS_TYPE '
                    ||'         ,'''||vPRS_TYPE_NAME||''' CYF_PRS_NAME '
                    ||'         ,'''||pCYFH_IS_EDIT||''' CYF_IS_EDIT,ROWNUM NO_REC '
                    ||'from CST_YARN_LEFT '||vWhere;
            
            else
                vSql := 'select   '''||pUserId||''' CYF_USER_ID '
                        ||'         ,CYL_SYS_ID '
                        ||'         ,'''||pPRS_TYPE||''' CYF_PRS_TYPE '
                        ||'         ,'''||vPRS_TYPE_NAME||''' CYF_PRS_NAME '
                        ||'         ,'''||pCYFH_IS_EDIT||''' CYF_IS_EDIT '
                        ||'from CST_YARN_LEFT '||vWhere;
            end if;
                    
            if pPRS_TYPE = vIdVal and not vStsAllData then
            declare
                vPeriodYearReff number;
                vPeriodMonthReff number;
                vDateFilder date;
            begin        
                
                SELECT to_date(CYFH_ITEM_VALUE,'YYYY-MM') into vDateFilder
                  FROM mgtapps.CST_YARN_FILTER_HDR
                 WHERE     CYFH_PRS_TYPE = pPRS_TYPE
                       AND CYFH_CREATED_BY = pUserId
                       AND nvl(CYFH_IS_EDIT,'NULL') = nvl(pCYFH_IS_EDIT,'NULL')             
                       AND CYFH_ITEM_NAME = 'Period ORION Reff (YYYY-MM)'
                       and rownum = 1;
                       
                vPeriodYearReff  := to_number(to_char(vDateFilder,'YYYY'));
                vPeriodMonthReff := to_number(to_char(vDateFilder,'MM'));  
                vSql := vSql||' and (CYL_LEFT_NO) in ' 
                            ||' ( 
                                select  CYL_LEFT_NO 
                                from    mgtapps.cst_yarn_left l
                                        ,mgtapps.cst_mst_yarn y
                                        ,cst_mst_orion_reff_dtl d 
                                        ,cst_mst_orion_reff_hdr h
                                where l.CYL_CMY_SYS_ID = CMY_SYS_ID and CMY_ITEM_CODE = CMORD_ITEM_CODE 
                                AND upper(CYL_SHADE_CODE) = upper(CMORD_ITEM_SHADE) 
                                and CMORH_PERIOD_YEAR = '||vPeriodYearReff||'
                                and CMORH_PERIOD_MONTH = '||vPeriodMonthReff ||'
                                and CMORH_SYS_ID = CMORD_CMORH_SYS_ID                                    
                                ) ';                           
            exception
                when no_data_found then
                    begin
                        select  to_number(to_char(period_prs,'YYYY')) thn
                                ,to_number(to_char(period_prs,'MM')) bln
                        into vPeriodYearReff,vPeriodMonthReff 
                        from (        
                        select to_date(PARAM_VALUE,'YYYYMM') period_prs from mst_params
                        where param_id = 'PERIOD_DEFAULT_GROUP_ITEM'
                        ) dt;
                    exception
                        when others then
                            vPeriodYearReff     := null;
                            vPeriodMonthReff    := null;
                    end;
                    if vPeriodYearReff is null or vPeriodMonthReff is null then
                        vSql := vSql||' and 1=0 ';
                    else                    
                        vSql := vSql||' and (CYL_LEFT_NO) in ' 
                                ||' ( 
                                    select  CYL_LEFT_NO 
                                    from    mgtapps.cst_yarn_left l
                                            ,mgtapps.cst_mst_yarn y
                                            ,cst_mst_orion_reff_dtl d 
                                            ,cst_mst_orion_reff_hdr h
                                    where l.CYL_CMY_SYS_ID = CMY_SYS_ID and CMY_ITEM_CODE = CMORD_ITEM_CODE 
                                    AND upper(CYL_SHADE_CODE) = upper(CMORD_ITEM_SHADE) 
                                    and CMORH_PERIOD_YEAR = '||vPeriodYearReff||'
                                    and CMORH_PERIOD_MONTH = '||vPeriodMonthReff ||'
                                    and CMORH_SYS_ID = CMORD_CMORH_SYS_ID                                    
                                    ) ';               
                    end if;
                when others then
                    vSql := vSql||' and 1=0 ';                                      
            end;                                            
            end if;                                
            
            if vStsRownum then
                vSql := vSql_Ins||
                        ' select   CYF_USER_ID ,CYL_SYS_ID,CYF_PRS_TYPE ,CYF_PRS_NAME,CYF_IS_EDIT from ( '||vSql||') where '
                        ||vWhere_Rownum;
            else
                vSql := vSql_Ins||vSql;
            end if;
            
            /*if pUserId = '1949' then
                insert into LOG_TRACE_PROCESS(
                    LTP_DATA, LTP_CREATED_TIMESTAMP, LTY_TYPE
                ) values (
                    vSql,sysdate,'FILTER'
                );commit;
            end if;*/
            
            execute immediate vSql;
            commit;
        end if;
        exception
            when others then
                vErrPrs := 'Error : Generate FILTER '||vSql||' '||sqlerrm;
                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);
        end;
        commit;
        
        if vSts_GetTop55 then
        
        begin
            pGet_RM_from_55.pSynchronize_filter(pUserId,pPRS_TYPE,'Y');
        end;  
        
                        
        declare
            vTmp number;
        begin
            for recDtIns in (
                
                select  CYF_USER_ID
                        , CRF5_CYL_SYS_ID CYF_CYL_SYS_ID
                        , CYF_CREATED_TIMESTAMP
                        , CYF_PRS_TYPE
                        , CYF_PRS_NAME
                        , CYF_IS_EDIT
                from MGTAPPS.CST_YARN_FILTER a
                     ,MGTAPPS.cst_RM_from_55 b
                where CYF_USER_ID = pUserId
                and CYF_PRS_TYPE = pPRS_TYPE
                and CYF_IS_EDIT = pCYFH_IS_EDIT
                and a.CYF_CYL_SYS_ID = b.CRF5_CYL_SYS_ID_REFF
                
            ) loop
            begin
                select 'x' into vTmp 
                from MGTAPPS.CST_YARN_FILTER a
                where CYF_USER_ID   =   recDtIns.CYF_USER_ID
                and CYF_CYL_SYS_ID  =   recDtIns.CYF_CYL_SYS_ID
                and CYF_PRS_TYPE    =   recDtIns.CYF_PRS_TYPE
                and CYF_IS_EDIT     =   recDtIns.CYF_IS_EDIT;
            exception
                when no_data_found then
                    insert into MGTAPPS.CST_YARN_FILTER 
                    (CYF_USER_ID,CYF_CYL_SYS_ID,CYF_CREATED_TIMESTAMP
                    ,CYF_PRS_TYPE,CYF_PRS_NAME,CYF_IS_EDIT)
                    values
                    (recDtIns.CYF_USER_ID,recDtIns.CYF_CYL_SYS_ID,sysdate
                    ,recDtIns.CYF_PRS_TYPE,recDtIns.CYF_PRS_NAME,recDtIns.CYF_IS_EDIT);
                    commit;
                when others then null;
            end;
            end loop;
        end;
        end if;

    end pRefFilter;
    
    procedure pCkYarnWithoutLoss(pCYC_PRS_TYPE varchar2) is
    begin
        for recDt in (        
        
            select CMOWL_CYL_SHADE_CODE, CMOWL_CYL_SHADE_NAME 
            from CST_MST_PRD_WITHOUT_LOSS
                        
        ) loop
            -- POY Set Null For Calculation Formula             
            update cst_yarn_calculation
                set CYC_FORMULA_TYPE=null, CYC_FORMULA_SCRIPT=null, CYC_DATA_VALUE=null
            where cyc_sys_id in ( 
            select cyc_sys_id
            from cst_yarn_calculation c
                 ,cst_yarn_left l
            where c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
            and l.CYL_TYPE = 'POY'
            and CYL_SHADE_CODE = recDt.CMOWL_CYL_SHADE_CODE
            and CYL_SHADE_NAME = recDt.CMOWL_CYL_SHADE_NAME
            and CYC_PRS_TYPE = CYL_PRS_TYPE
            and CYC_PRS_TYPE = pCYC_PRS_TYPE
            and c.CYC_TOP_NO between 107 and 112
            );                    
            -- End POY Set Null For Calculation Formula
            
            -- DTY Set Null For Calculation Formula             
            update cst_yarn_calculation
                set CYC_FORMULA_TYPE=null, CYC_FORMULA_SCRIPT=null, CYC_DATA_VALUE=null
            where CYC_PRS_TYPE = pCYC_PRS_TYPE 
            and cyc_sys_id in (
                         
            select cyc_sys_id
            from cst_yarn_calculation c
                 ,cst_yarn_left l
            where c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
            and l.CYL_TYPE = 'PTY'
            and c.CYC_PRS_TYPE = pCYC_PRS_TYPE
            and c.CYC_PRS_TYPE = CYL_PRS_TYPE
            and CYL_SHADE_CODE = recDt.CMOWL_CYL_SHADE_CODE
            and CYL_SHADE_NAME = recDt.CMOWL_CYL_SHADE_NAME
            and c.CYC_TOP_NO between 113 and 117
            
            );                    
            -- End DTY Set Null For Calculation Formula
        end loop;
    end pCkYarnWithoutLoss;
    
    function fGetValIntermingling(pCYMI_DESCRIPTION varchar2) return number is
        vCYMI_VALUE number;
    begin
        select CYMI_VALUE into vCYMI_VALUE 
        from CST_YARN_MST_INTERMINGLING
        where CYMI_DESCRIPTION = pCYMI_DESCRIPTION;
        
        return vCYMI_VALUE;
    exception
        when no_data_found then
            return null;                          
    end fGetValIntermingling;

    
    procedure pRefIntemingleVal(pUserID varchar2,pCYL_SYS_ID varchar2,pcyc_prs_type varchar2) is
        vDtCalc mgtapps.cst_yarn_calculation%rowtype;
        vTmp number;
        vValTmp number; 
        verrmsg varchar2(300);
    begin
        -- process 1
        for recDt in ( 
        select distinct CYL_SYS_ID,CYL_LEFT_NO,c.CYC_DATA_VALUE 
        from CST_YARN_FILTER f         
             ,CST_YARN_LEFT l
             ,CST_YARN_CALCULATION c
        where CYF_USER_ID = pUserID
        and l.CYL_SYS_ID = f.CYF_CYL_SYS_ID
        and l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
        and CYL_SYS_ID = pCYL_SYS_ID
        and c.CYC_TOP_NO = 18
        and c.CYC_DATA_VALUE in ('HIM','SIM','LIM','IM','NIM')
        and l.CYL_TYPE <> 'POY'
        and l.cyl_prs_type = cyc_prs_type
        and l.cyl_prs_type = cyf_prs_type
        and l.cyl_prs_type = pcyc_prs_type
        )loop
        begin
            select 'x' into vTmp 
            from CST_YARN_CALCULATION
            where CYC_CYL_SYS_ID = recDt.CYL_SYS_ID
            and CYC_TOP_NO = 74;
        exception
            when no_data_found then
                --Initial_Value
                vDtCalc.CYC_SYS_ID :=   TO_CHAR (SYSDATE, 'YYYYMMDD')
                                        ||TO_CHAR (pkg_seq_no.next_value ('CYC_SYS_ID',
	                                        pUserID,
	                                        verrmsg
				                                       ),'FM000000000000000000000');
                vDtCalc.CYC_CYL_SYS_ID := recDt.CYL_SYS_ID;
                vDtCalc.CYC_PROCESS_SEQ := 1;
                vDtCalc.CYC_CYT_SYS_ID := get_CYT_SYS_ID(74,pcyc_prs_type);
                vDtCalc.CYC_LEFT_NO := 74;
                vDtCalc.CYC_TOP_NO := recDt.CYL_LEFT_NO;
                vDtCalc.CYC_FORMULA_TYPE:='Initial_Value';
                
                vDtCalc.cyc_prs_type := pcyc_prs_type;
                vDtCalc.cyc_prs_name := fGetNmYarnPrs(pcyc_prs_type);
                --, CYC_FORMULA_SCRIPT
                begin
                    vValTmp := nvl(fGetValIntermingling(recDt.CYC_DATA_VALUE),0)/100;
                    vDtCalc.CYC_FORMULA_SCRIPT := vValTmp;
                    vDtCalc.CYC_DATA_VALUE := vValTmp;
                exception
                    when others then 
                        vDtCalc.CYC_FORMULA_SCRIPT := null;
                end;
                vDtCalc.CYC_PROCESS_SEQ := null;
                
                vDtCalc.CYC_CREATED_BY := pUserID;
                vDtCalc.CYC_CREATED_TIMESTAMP := sysdate;
                
                insert into CST_YARN_CALCULATION values vDtCalc;
            when others then
            begin
                select * into vDtCalc 
                from mgtapps.cst_yarn_calculation
                where CYC_CYL_SYS_ID = recDt.CYL_SYS_ID
                and CYC_TOP_NO = 74
                and cyc_prs_type = pcyc_prs_type;
                
                begin
                    vValTmp := nvl(fGetValIntermingling(recDt.CYC_DATA_VALUE),0)/100;
                exception
                    when others then
                       vValTmp := 0;
                end;
                
                vDtCalc.CYC_FORMULA_TYPE := 'Initial_Value';
                vDtCalc.CYC_PROCESS_SEQ := 1;
                update mgtapps.cst_yarn_calculation
                    set CYC_FORMULA_TYPE = vDtCalc.CYC_FORMULA_TYPE
                        ,CYC_PROCESS_SEQ = vDtCalc.CYC_PROCESS_SEQ
                        ,CYC_FORMULA_SCRIPT =  vValTmp
                        ,CYC_DATA_VALUE =  vValTmp
                where CYC_SYS_ID = vDtCalc.CYC_SYS_ID;
            end;            
        end;
        end loop;
        
        -- process 2
        for recDt in (             
        select distinct CYL_SYS_ID,CYL_LEFT_NO,c.CYC_DATA_VALUE 
        from CST_YARN_FILTER f         
             ,CST_YARN_LEFT l
             ,CST_YARN_CALCULATION c
        where CYF_USER_ID = pUserID
        and l.CYL_SYS_ID = f.CYF_CYL_SYS_ID
        and l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
        and CYL_SYS_ID = pCYL_SYS_ID
        and l.cyl_prs_type = cyc_prs_type
        and l.cyl_prs_type = cyf_prs_type
        and l.cyl_prs_type = pcyc_prs_type
        and c.CYC_TOP_NO = 18
        --and c.CYC_DATA_VALUE in ('HIM','SIM','LIM','IM','NIM')
        and l.CYL_TYPE <> 'POY'            
        )loop
        begin
            select 'x' into vTmp 
            from CST_YARN_CALCULATION
            where CYC_CYL_SYS_ID = recDt.CYL_SYS_ID
            and CYC_TOP_NO = 74
            and cyc_prs_type = pcyc_prs_type;
        exception
            when no_data_found then
                null;
            when others then
            begin
                select * into vDtCalc 
                from mgtapps.cst_yarn_calculation
                where CYC_CYL_SYS_ID = recDt.CYL_SYS_ID
                and CYC_TOP_NO = 74
                and cyc_prs_type = pcyc_prs_type
                ;
                if nvl(recDt.CYC_DATA_VALUE,'NULL') not in ('HIM','SIM','LIM','IM','NIM') then
                    update mgtapps.cst_yarn_calculation
                        set CYC_FORMULA_TYPE = null
                            ,CYC_PROCESS_SEQ = null
                            ,CYC_FORMULA_SCRIPT = null
                            ,CYC_DATA_VALUE =  null
                    where CYC_SYS_ID = vDtCalc.CYC_SYS_ID;
               end if;
            end;            
        end;
        end loop;      
        
        -- check OIL Rate
        for recDt in (       
            
            select dt_59.CYRD_RM_CODE RM_CODE_SOURCE,dt_60.CYRD_RM_CODE RM_CODE_DEST,CYRD_SYS_ID
            from
                (                                      
                select  CYC_CYL_SYS_ID,CYC_DATA_VALUE,CYRD_RM_CODE
                from CST_YARN_FILTER f         
                     ,CST_YARN_LEFT l
                     ,CST_YARN_CALCULATION c
                     ,cst_yarn_rm_hdr h
                     ,cst_yarn_rm_dtl d 
                where CYF_USER_ID = 'ADMIN'--pUserID
                and l.CYL_SYS_ID = f.CYF_CYL_SYS_ID
                and l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
                --and CYL_SYS_ID = pCYL_SYS_ID
                and c.CYC_TOP_NO = 59
                and CYRH_SYS_ID = CYRD_CYRH_SYS_ID
                and CYC_SYS_ID = CYRH_CYC_SYS_ID
                and CYRH_TYPE = 'Store Rate'      
                and l.cyl_prs_type = cyc_prs_type
                and l.cyl_prs_type = cyf_prs_type
                and l.cyl_prs_type = pcyc_prs_type                  
                ) dt_59
                ,(                        
                select CYRD_SYS_ID,CYC_CYL_SYS_ID,CYRD_RM_CODE
                        --,CYC_SYS_ID,c.CYC_TOP_NO,h.* 
                from CST_YARN_FILTER f         
                     ,CST_YARN_LEFT l
                     ,CST_YARN_CALCULATION c
                     ,cst_yarn_rm_hdr h
                     ,cst_yarn_rm_dtl d 
                where CYF_USER_ID = pUserID
                and l.CYL_SYS_ID = f.CYF_CYL_SYS_ID
                and l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
                --and CYL_SYS_ID = pCYL_SYS_ID
                and c.CYC_TOP_NO = 60
                and CYRH_SYS_ID = CYRD_CYRH_SYS_ID
                and CYC_SYS_ID = CYRH_CYC_SYS_ID
                and CYRH_TYPE = 'Store Rate'
                and l.cyl_prs_type = cyc_prs_type
                and l.cyl_prs_type = cyf_prs_type
                and l.cyl_prs_type = pcyc_prs_type                                        
                ) dt_60
           where dt_59.CYC_CYL_SYS_ID = dt_60.CYC_CYL_SYS_ID
           and dt_59.CYRD_RM_CODE <> dt_60.CYRD_RM_CODE
                                
        )loop
        begin
            update cst_yarn_rm_dtl
                set CYRD_RM_CODE = recDt.RM_CODE_SOURCE
            where CYRD_SYS_ID = recDt.CYRD_SYS_ID;
        end;
        end loop;              
        -- end check OIL Rate
    end pRefIntemingleVal;
    
    procedure pValuationProcess(pUserId varchar2,pErrMsg out varchar2) is
        vStsCk boolean:=false;
        vCST_YARN_LEFT CST_YARN_LEFT%rowtype;
        vTotTop number;
        vExp exception;
        verrmsg varchar2(1000);
        vTmp number;
    begin
    
        for recDt in (                                
            SELECT l.*
           FROM mgtapps.cst_yarn_left l
          WHERE l.cyl_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt --'20210800119' -- 
            AND CYL_IS_VALUATION = 'Y'
            and not exists (
            select * from mgtapps.cst_yarn_left_valuation 
            where CYLV_CYL_SYS_ID = CYL_SYS_ID
            )
            ORDER BY l.cyl_left_no                                            
        ) loop
        
            vErrMsg := null;
            -- insert into cst_yarn_left_valuation
                    
            begin
                select 'x' into vTmp
                from MGTAPPS.CST_YARN_LEFT
                where CYL_LEFT_NO = recDt.CYL_LEFT_NO
                and CYL_PRS_TYPE = vIdVal;
            exception
                when no_data_found then                                 
                    vCST_YARN_LEFT.CYL_SYS_ID :=    TO_CHAR (SYSDATE, 'YYYYMM')||
                                                    TO_CHAR (pkg_seq_no.next_value ('CYL_SYS_ID'
                                                                                    ,'ADMIN'
                                                                                    ,verrmsg)
                                                             ,'fm00000');
                                                             
                    vCST_YARN_LEFT.CYL_NAME                 :=  recDt.CYL_NAME;
                    vCST_YARN_LEFT.CYL_TYPE                 :=  recDt.CYL_TYPE;
                    vCST_YARN_LEFT.CYL_SHADE_CODE           :=  recDt.CYL_SHADE_CODE;
                    vCST_YARN_LEFT.CYL_SHADE_NAME           :=  recDt.CYL_SHADE_NAME;
                    vCST_YARN_LEFT.CYL_LEFT_NO              :=  recDt.CYL_LEFT_NO;
                    vCST_YARN_LEFT.CYL_CMY_SYS_ID           :=  recDt.CYL_CMY_SYS_ID;
                    vCST_YARN_LEFT.CYL_SEQ_NO               :=  recDt.CYL_SEQ_NO;
                    vCST_YARN_LEFT.CYL_ITEM_CODE            :=  recDt.CYL_ITEM_CODE;
                    vCST_YARN_LEFT.CYL_MACHINE_CODE         :=  recDt.CYL_MACHINE_CODE;
                    vCST_YARN_LEFT.CYL_MARKETING_COST_LINK  :=  recDt.CYL_MARKETING_COST_LINK;
                    vCST_YARN_LEFT.CYL_PRODUCT_QUALITY      :=  recDt.CYL_PRODUCT_QUALITY;
                    vCST_YARN_LEFT.CYL_CUSTOMER             :=  recDt.CYL_CUSTOMER;
                    vCST_YARN_LEFT.CYL_PRS_TYPE             :=  vIdVal;--'20210800120';
                    vCST_YARN_LEFT.CYL_PRS_NAME             :=  MGTAPPS.pkg_yarn_calculation.fGetNmYarnPrs(vCST_YARN_LEFT.CYL_PRS_TYPE);   
                    vCST_YARN_LEFT.CYL_SYS_ID_MKT_REFF      := recDt.cyl_sys_id;   

                    begin                    
                        select 'x' into vTmp
                        from mgtapps.cst_yarn_left_valuation
                        where nvl(CYLV_CYL_SYS_ID,'NULL') = nvl(vCST_YARN_LEFT.CYL_SYS_ID_MKT_REFF,'NULL');
                    exception
                        when no_data_found then
                            insert into cst_yarn_left_valuation(CYLV_CYL_SYS_ID, CYLV_CREATED_BY, CYLV_CREATED_TIMESTAMP)
                            values(vCST_YARN_LEFT.CYL_SYS_ID_MKT_REFF,pUserId,sysdate);
                        when others then 
                            null;
                    end;
                    
                    insert into MGTAPPS.CST_YARN_LEFT values  vCST_YARN_LEFT; commit;
            when others then
                select * into vCST_YARN_LEFT
                from MGTAPPS.CST_YARN_LEFT
                where CYL_LEFT_NO = recDt.CYL_LEFT_NO
                and CYL_PRS_TYPE = vIdVal;                   
            end;       				                                                            
        																		
            -- Copy Formula           
            copy_formula_valuation(
              pUserId--pUserId
              ,recDt.CYL_SYS_ID--recDt.CYL_SYS_ID-- :CST_YARN_LEFT.COPY_CYC_CYL_SYS_ID
              ,vCST_YARN_LEFT.CYL_SYS_ID--vCST_YARN_LEFT.CYL_SYS_ID --:CST_YARN_LEFT.CYL_SYS_ID
              ,verrmsg
            );            
            -- Copy Formula
            
            /*if vErrMsg is not null then
                insert into LOG_TRACE_PROCESS (LTP_DATA, LTP_CREATED_TIMESTAMP, LTY_TYPE)
                values(vErrMsg, sysdate, 'Valuation Data');
                
                commit;
        
                exit;
            end if;*/
        end loop;
    end pValuationProcess;         
    
    procedure process(pUserId varchar2,pPRS_TYPE varchar2,pCYFH_IS_EDIT varchar2 default null) is
        vDataValue CST_YARN_CALCULATION.CYC_DATA_VALUE%type;
        vErrPrs varchar2(32767);
        --vDataValueNum number;
        vSql varchar2(32767);
        vDataSql varchar2(32767);
        vDataFormula number;
        
        vDataFormula1 number;
        vDataFormula2 number;
        vDataFormula3 number;
        
        vOperator1 varchar2(30);
        vOperator2 varchar2(30);
        vOperator3 varchar2(30);
        
        -- for If Condition --
        vSts_Condition1 VARCHAR(5):='FALSE';
        vSts_Condition2 VARCHAR(5):='FALSE';
        vSts_if VARCHAR(5):='FALSE';
        vDtCondt1 varchar2(4000);
        vDtCondt2 varchar2(4000);
        vDtCondt3 varchar2(4000);
        vDtCondt4 varchar2(4000);
        
        vSqlCondt1 varchar2(4000);
        vSqlCondt2 varchar2(4000);
        vSqlCondt3 varchar2(4000);
        vSqlCondt4 varchar2(4000);
        
        vIFCOND_HDR MGTAPPS.CST_YARN_IFCOND_HDR%rowtype;
        vIFCOND_DTL MGTAPPS.CST_YARN_IFCOND_DTL%rowtype;
        vCYIH_CYC_SYS_ID varchar2(30);
        -- for If Condition --
        
        -- From Master Yarn
        vSqlDtMstYarn varchar2(4000);
        vTotMstYarn number;  
        vCMY_SYS_ID cst_mst_yarn.CMY_SYS_ID%type;
        
        -- From Same Rows
        vSqlDtSameRows varchar2(4000);
        vSqlDtSameVal varchar2(4000);
        vTotSameRows number;
        
        -- MB Data
        vCMCH_PERIOD_YEAR CST_MB_CONSUMP_HEAD.CMCH_PERIOD_YEAR%type;
        vCMCH_PERIOD_MONTH CST_MB_CONSUMP_HEAD.CMCH_PERIOD_MONTH%type;
        
        vSts_Refresh varchar2(1);
        
    begin
        begin
            select nvl(PARAM_VALUE,'N') into vSts_Refresh from  MST_PARAMS
            where PARAM_ID = vREFRESH_CST_YARN
            and rownum = 1;
        exception
            when no_data_found then    
                vSts_Refresh := 'N';         
        end;
        
        if vSts_Refresh = 'Y' then    
        
        
        pValuationProcess(pUserId,vErrPrs);
        
        if vErrPrs is not null then
            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME) 
            values(vErrPrs,pUserId,sysdate);
            commit;
            return;
        end if;
        
        
        pCkYarnWithoutLoss(pPRS_TYPE);
        
        --mgtapps.pkgYarnCalc_CkTopVal.process(pUserId,pPRS_TYPE);
        
        
        pRefrshValParam(pUserID,pPRS_TYPE);
        
        --pRefFilter(pUserId,pPRS_TYPE); 
        
        for recYarn in (
        
            select * 
            from CST_YARN_CALCULATION t
            where CYC_CYL_SYS_ID in (
                select CYF_CYL_SYS_ID 
                from CST_YARN_FILTER 
                where CYF_USER_ID = puserid
                and CYF_PRS_TYPE = pPRS_TYPE
                and nvl(CYF_IS_EDIT,'N') =  nvl(pCYFH_IS_EDIT,'N')
            )
            and CYC_PRS_TYPE = pPRS_TYPE
            order by CYC_PROCESS_SEQ,CYC_LEFT_NO, CYC_TOP_NO
            
        )loop
        begin
            vDataValue := null;           
            if upper(recYarn.CYC_FORMULA_TYPE) = 'LOV_DATA' then -- 1. LOV_DATA
            declare
                vCST_YARN_LOV_DATA CST_YARN_LOV_DATA%rowtype;
                vCST_YARN_LOV_MST CST_YARN_LOV_MST%rowtype;
                vQryLOV_DATA varchar2(1000); 
            begin
                vQryLOV_DATA := null; 
                select * into vCST_YARN_LOV_DATA 
                from CST_YARN_LOV_DATA
                where CYLD_CYC_SYS_ID = recYarn.CYC_SYS_ID;
                
                vDataValue := vCST_YARN_LOV_DATA.CYLD_LOV_DATA_VALUE;
            exception
                when no_data_found then 
                    null;
            end; 
                        
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'INITIAL_VALUE' then -- 2. INITIAL_VALUE
             vSql := 'select '''||recYarn.CYC_FORMULA_SCRIPT||''' dt from dual ';
             begin 
                vDataValue :=  MGTAPPS.fExecQuery(vSql);
             exception
                when others then
                    vErrPrs := sqlerrm||vSql||chr(13)||'upper(rec.CYC_FORMULA_TYPE) = ''INITIAL_VALUE'' :Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                    --insert into CST_YARN_ERR_LOG values(vErrPrs,sysdate);
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME) 
                    values(vErrPrs,pUserId,sysdate);
             end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_DATA' then --3. FROM_DATA
                vSqlDtSameRows := null;
                vTotSameRows := 0;
                vSqlDtSameVal := null;
                for recSameRows in (                                        
                    select * from MGTAPPS.CST_YARN_SAME_ROWS
                    where CYSR_CYC_SYS_ID = recYarn.CYC_SYS_ID 
                    order by nvl(CYSR_SEC_NO,0)                                                                                
                ) loop
                    vTotSameRows := vTotSameRows + 1;  
                    vSqlDtSameRows := 'select trim(CYC_DATA_VALUE) from CST_YARN_CALCULATION where CYC_SYS_ID ='''||recSameRows.CYSR_VALUE||''' --> Formula Yarn : '||recYarn.CYC_SYS_ID;
                    begin 
                        vSqlDtSameVal :=  MGTAPPS.fExecQuery(vSqlDtSameRows);
                    exception
                    when others then
                        vErrPrs := sqlerrm||vSqlDtSameRows||chr(13)||' YARN_SAME_ROWS :Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                        insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME) 
                        values(vErrPrs,pUserId,sysdate);
                    end;                    
                    
                    if vTotSameRows = 1 then
                        vDataValue := vSqlDtSameVal;
                    else
                        vDataValue := vDataValue||'-'||vSqlDtSameVal; 
                    end if;
                END LOOP;                                         
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FORMULA_DATA' then --4. FORMULA_DATA
                vDataFormula := 0;     
                vDataValue := 0;           
                
                for recForm in (            
                                        
                    select distinct * from MGTAPPS.CST_YARN_FORMULA_CALC
                    where CYFC_CYC_SYS_ID = recYarn.CYC_SYS_ID                     
                    order by CYFC_SEQ_NO
                                                                                                    
                ) loop
                
                    if nvl(recForm.CYFC_TYPE_1,'NULL') = 'SQRT' then -- SQRT                    
                        vSql := 'select nvl(trim(CYC_DATA_VALUE),0) from CST_YARN_CALCULATION '||
                                 'where CYC_SYS_ID ='''||recForm.CYFC_VALUE_1||''' ';
                                                                                            
                        begin
                            vDataFormula1 := null;                        
                            vDataFormula1 := nvl(MGTAPPS.fExecQuery(vSql),0);
                        exception
                            when others then
                                vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula 1 SQRT:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                        end;                                                         
                        vSql := 'select nvl(trim(CYC_DATA_VALUE),0) CYC_DATA_VALUE from CST_YARN_CALCULATION '||
                                'where CYC_SYS_ID ='''||recForm.CYFC_VALUE_2||''' ';
                        --insert into CST_YARN_ERR_LOG values('SQRT 2 '||vSql);                                 
                        begin
                            vDataFormula2 := null;                        
                            vDataFormula2 := nvl(MGTAPPS.fExecQuery(vSql),0);
                        exception
                            when others then
                                vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula 2 SQRT:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                    
                        end;                                              
                        vDataSql := 'select SQRT('||vDataFormula1||'/'||vDataFormula2||') from dual';
                        --insert into CST_YARN_ERR_LOG values('SQRT 3 '||vDataSql);           
                        begin               
                            vDataFormula := null;         
                            vDataFormula := nvl(MGTAPPS.fExecQuery(vDataSql),0);
                        exception
                            when others then
                                vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula SQRT:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                    
                        end;    
                    else
                      if upper(recForm.CYFC_TYPE_1) = 'FROM_TX_WEIGHT' then  -- FROM_TX_WEIGHT                   
                        vSql := 'select nvl(pkg_mst_value.fYARN_TX_WEIGHT('''||recForm.CYFC_VALUE_1||'''),0) CYTW_VALUE  from dual ';
                        begin                        
                            vDataFormula1 := null;   
                            vDataFormula1 := nvl(MGTAPPS.fExecQuery(vSql),0);
                        exception
                            when others then
                                vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula 1 FROM_TX_WEIGHT:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                                    
                        end;
                        
                        vDataFormula := vDataFormula1;                                                        
                      else               
                       -- 3 --
                        if recForm.CYFC_VALUE_3 is not null or recForm.CYFC_TOP_NO_3 is not null  then
                            -- Check Formula 3
                            vDataFormula3 := null;                
                            if upper(recForm.CYFC_TYPE_3) = 'INITIAL_VALUE' then
                             vSql          := 'select '||nvl(recForm.CYFC_VALUE_3,0)||' from dual';
                             begin
                                vDataFormula3 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula3 FIX VALUE:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;                                                         
                            elsif upper(recForm.CYFC_TYPE_3) = 'FROM_DATA' then
                             vSql          := 'select nvl(trim(CYC_DATA_VALUE),0) CYC_DATA_VALUE from mgtapps.CST_YARN_CALCULATION '||
                                              --'where CYC_SYS_ID ='''||recForm.CYFC_VALUE_3||''' ';
                                              ' where CYC_TOP_NO ='''||recForm.CYFC_TOP_NO_3||''' and CYC_PRS_TYPE = '''||pPRS_TYPE||''' '||
                                              ' and CYC_LEFT_NO = '||recYarn.CYC_LEFT_NO;
                                              
                             begin                                                                                          
                                vDataFormula3 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula3 FROM DATA:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;                                                                                 
                            end if;
                            -- Check Formula 3                        
                        end if;
                        
                        -- 2 --
                        if recForm.CYFC_VALUE_2 is not null or recForm.CYFC_TOP_NO_2 is not null then                
                            -- Check Formula 2
                            vDataFormula2 := null;
                            if upper(recForm.CYFC_TYPE_2) = 'INITIAL_VALUE' then
                             vSql          := 'select '||nvl(recForm.CYFC_VALUE_2,0)||' from dual';
                             begin
                                vDataFormula2 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula2 FIX VALUE:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                    values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;                                                         
                            elsif upper(recForm.CYFC_TYPE_2) = 'FROM_DATA' then
                             vSql          := 'select nvl(trim(CYC_DATA_VALUE),0) CYC_DATA_VALUE from CST_YARN_CALCULATION '||
                                              --'where CYC_SYS_ID ='''||recForm.CYFC_VALUE_2||''' ';
                                              ' where CYC_TOP_NO ='''||recForm.CYFC_TOP_NO_2||''' and CYC_PRS_TYPE = '''||pPRS_TYPE||''' '||
                                              ' and CYC_LEFT_NO = '||recYarn.CYC_LEFT_NO;
                             begin
                                vDataFormula2 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula2 FROM DATA:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;                                   
                                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                    values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;                                                                                 
                            end if;           
                            -- Check Formula 2                                              
                        end if;                    
                        
                        -- 1 --
                        -- Check Formula 1
                        if recForm.CYFC_VALUE_1 is not null or recForm.CYFC_TOP_NO_1 is not null then                
                            if upper(recForm.CYFC_TYPE_1) = 'INITIAL_VALUE' then
                             vSql          := 'select '||nvl(recForm.CYFC_VALUE_1,0)||' from dual';
                             begin                             
                                vDataFormula1 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula1 FIX VALUE:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                    values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;                                                         
                            elsif upper(recForm.CYFC_TYPE_1) = 'FROM_DATA' then
                             vSql          := 'select nvl(trim(CYC_DATA_VALUE),0) CYC_DATA_VALUE from CST_YARN_CALCULATION '||
                                              --'where CYC_SYS_ID ='''||recForm.CYFC_VALUE_1||''' ';
                                              ' where CYC_TOP_NO ='''||recForm.CYFC_TOP_NO_1||''' and CYC_PRS_TYPE = '''||pPRS_TYPE||''' '||
                                              ' and CYC_LEFT_NO = '||recYarn.CYC_LEFT_NO;
                             begin
                                vDataFormula1 := nvl(MGTAPPS.fExecQuery(vSql),0);
                             exception
                                when others then
                                    vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula1 FROM DATA:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                    values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                
                             end;
                            elsif nvl(recForm.CYFC_TYPE_1,'NULL') = 'Lov Data' then -- Lov Data
                                if recForm.CYFC_VALUE_LOV_1 = 'INTERMINGLING DATA' then
                                begin
                                    select CYMI_VALUE into vDataFormula1 
                                    from CST_YARN_MST_INTERMINGLING
                                    
                                    
                                    where CYMI_SYS_ID = recForm.CYFC_SYS_ID_LOV_1;
                                    
                                    --insert into CST_YARN_ERR_LOG values('Data CST_YARN_MST_INTERMINGLING Hasil : '||vDataFormula1);
                                exception
                                    when no_data_found then
                                        null; 
                                    --CYMI_DESCRIPTION CYFC_TYPE_1, CYFC_VALUE_1, CYFC_VALUE_LOV_1
                                end;    
                                end if;
                            end if;  
                          end if;                      
                        end if;                        
                        -- Check Formula 1                
                        
                        if recForm.CYFC_OPERATOR_2 is not null and recForm.CYFC_OPERATOR_3 is not null   
                        then                    
                            vDataSql := 'select '||nvl(vDataFormula1,0)||' '||recForm.CYFC_OPERATOR_2||' '||nvl(vDataFormula2,0)||' '
                                        ||recForm.CYFC_OPERATOR_3||' '||nvl(vDataFormula3,0)||' from dual';                                        
                        elsif recForm.CYFC_OPERATOR_2 is not null and recForm.CYFC_OPERATOR_3 is null
                        then                    
                            vDataSql := 'select '||nvl(vDataFormula1,0)||' '||recForm.CYFC_OPERATOR_2||' '||nvl(vDataFormula2,0)||' from dual';
                        elsif recForm.CYFC_OPERATOR_2 is null and recForm.CYFC_OPERATOR_3 is null
                        then                    
                            vDataSql := 'select '||nvl(vDataFormula1,0)||' from dual';
                        end if;
                                                                        
                        begin                        
                            vDataFormula := nvl(MGTAPPS.fExecQuery(vDataSql),0);
                        exception
                            when others then
                                vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                                                                    
                        end;
                    
                    end if;
                    
                    begin
                        vDataSql := 'select '||nvl(vDataValue,0)||' '||recForm.CYFC_OPERATOR_1||' '||nvl(vDataFormula,0)||' from dual ';
                        
                        vDataValue := nvl(MGTAPPS.fExecQuery(vDataSql),0);    
                                             
                    exception
                        when others then
                            vErrPrs := sqlerrm||vSql||chr(13)||'vDataFormula:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);    
                    end;
                                                            
                end loop;  
                --vDataValue := vDataFormula;  
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'IF_CONDITION' then --5. IF_CONDITION
                vIFCOND_HDR := null;
                vIFCOND_DTL := null;
                
                vDataValue := null;
                -- ** Check Condition ** --
                declare
                    vStsCond VARCHAR2(10):='TRUE';
                    vCmprDt1 varchar2(1);
                    vIfYarnType varchar2(30);
                begin
                for recCond in (                    
                
                    select * from MGTAPPS.CST_YARN_IFCOND_HDR
                    where CYIH_CYC_SYS_ID = recYarn.CYC_SYS_ID 
                    order by nvl(CYIH_SEQ_NO,0)
                                                                                
                ) loop               
                    vStsCond := 'TRUE';
                    -- CONDITION 1 --
                    vCYIH_CYC_SYS_ID := recCond.CYIH_CYC_SYS_ID;
                    vSqlCondt1 :=null;
                    vDtCondt1 := null;                     
                    if recCond.CYIH_OPRTR_1 is not null then     
                        if upper(recCond.CYIH_TYPE_DATA_A) = 'FROM_DATA' then
                            vSqlCondt1 :=  'SELECT trim(CYC_DATA_VALUE) CYC_DATA_VALUE
                                              FROM mgtapps.cst_yarn_calculation c
                                             WHERE cyc_cyl_sys_id IN (
                                                      SELECT cyl_sys_id
                                                        FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                                       WHERE cyc_sys_id = '||recCond.CYIH_CYC_SYS_ID||'
                                                         AND cyc_prs_type = '||pPRS_TYPE||'
                                                         AND cyl_sys_id = cyc_cyl_sys_id
                                                         AND cyc_prs_type = cyl_prs_type)
                                            and cyc_top_no = '||recCond.CYIH_TOP_NO_A||'';             
                            
                            vDtCondt1 := MGTAPPS.fExecQueryErr(vSqlCondt1,vErrPrs);
                            
                            -- CHECK CONDITION 1
                            
                            vSql := 'SELECT CASE WHEN '''
                                        ||nvl(vDtCondt1,0)||''' '||recCond.CYIH_OPRTR_1||' '''||nvl(recCond.CYIH_TOP_DATA_A,0)
                                        ||''' THEN ''1'' '
                                        ||' ELSE ''0'' '
                                        ||' END '
                                     ||' FROM DUAL ';
                            
                            vDtCondt1 := nvl(MGTAPPS.FEXECQUERY(vSql),0);
                            --if nvl(vDtCondt1,0) <> nvl(recCond.CYIH_TOP_DATA_A,0) then
                            IF NVL(vDtCondt1,'0') = 0 THEN
                                vStsCond := 'FALSE';
                            end if;
                            
                            -- CHECK CONDITION 1        
                        elsif upper(recCond.CYIH_TYPE_DATA_A) = 'YARN_TYPE' then                        
                        begin
                            select cyl_type into vIfYarnType
                            from    cst_yarn_calculation c
                                    ,cst_yarn_left l
                            where CYC_CYL_SYS_ID = CYL_SYS_ID
                            and CYC_SYS_ID = recCond.CYIH_CYC_SYS_ID;
                        exception
                            when no_data_found then
                                vIfYarnType := null;                                
                        end;                          
                            vSql := 'SELECT CASE WHEN '''
                                        ||recCond.CYIH_TOP_DATA_A||''' '||recCond.CYIH_OPRTR_1||' '''||vIfYarnType
                                        ||''' THEN ''1'' '
                                        ||' ELSE ''0'' '
                                        ||' END '
                                     ||' FROM DUAL ';

                            vDtCondt1 := nvl(MGTAPPS.FEXECQUERY(vSql),0);      
                            IF NVL(vDtCondt1,'0') = 0 THEN
                                vStsCond := 'FALSE';
                            end if;                                              
                        end if;
                    else vStsCond := 'FALSE';     
                    end if;
                    -- CONDITION 1 --
                    
                    if vStsCond = 'TRUE' then
                    -- CONDITION 2 --
                        vSqlCondt2 := null;
                        if recCond.CYIH_OPRTR_IF_1 is not null then
                            if recCond.CYIH_TYPE_DATA_B is not null then
                                if upper(recCond.CYIH_TYPE_DATA_B) = 'FROM_DATA' then
                                     vSqlCondt2 :=  'SELECT trim(CYC_DATA_VALUE) CYC_DATA_VALUE
                                     FROM mgtapps.cst_yarn_calculation c
                                     WHERE cyc_cyl_sys_id IN (
                                          SELECT cyl_sys_id
                                            FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                           WHERE cyc_sys_id = '||vCYIH_CYC_SYS_ID||'
                                             AND cyc_prs_type = '||pPRS_TYPE||'
                                             AND cyl_sys_id = cyc_cyl_sys_id
                                             AND cyc_prs_type = cyl_prs_type)
                                    and cyc_top_no = '||recCond.CYIH_TOP_NO_B||'';
                                    
                                    vDtCondt2 := null;
                                    vDtCondt2 := MGTAPPS.fExecQuery(vSqlCondt2);

                                    vSql := 'SELECT CASE WHEN '''
                                                ||nvl(vDtCondt2,0)||''' '||recCond.CYIH_OPRTR_2||' '''||nvl(recCond.CYIH_TOP_DATA_B,0)
                                                ||''' THEN ''1'' '
                                                ||' ELSE ''0'' '
                                                ||' END '
                                             ||' FROM DUAL ';
                                    
                                    vDtCondt2 := nvl(MGTAPPS.FEXECQUERY(vSql),0);
                                    
                                    --if vDtCondt2 <> recCond.CYIH_TOP_DATA_B then
                                    IF NVL(vDtCondt2,'0') = 0 THEN
                                        vStsCond := 'FALSE';
                                    end if;
                                end if;  
                            end if;
                        end if;                   
                    -- CONDITION 2 --
                    end if;
                    
                    if vStsCond = 'TRUE' then
                        if vDtCondt1 is null and vDtCondt2 is null then
                            vStsCond := 'FALSE';
                        end if;
                    end if;                    
                    
                    if vStsCond = 'TRUE' then
                        vIFCOND_HDR.CYIH_SYS_ID := recCond.CYIH_SYS_ID;
                        exit;                        
                    end if;                                                                                
                    -- Result If Condition --*/                                          
                end loop;                                    
                end;
                -- ** Check Condition ** --
                -- ** Get Result After Check Condition ** --
                if vIFCOND_HDR.CYIH_SYS_ID is not null then
                    for recCondDtl in (                    
                        
                        select d.*,
                               (
                                select nvl(max(CYID_SEQ_NO),1) max_dt
                                from MGTAPPS.CST_YARN_IFCOND_DTL
                                where CYID_CYIH_SYS_ID = vIFCOND_HDR.CYIH_SYS_ID
                               ) max_dt 
                        from MGTAPPS.CST_YARN_IFCOND_DTL d
                        where CYID_CYIH_SYS_ID = vIFCOND_HDR.CYIH_SYS_ID
                        order by nvl(CYID_SEQ_NO,0)
                        
                                 
                    )
                    loop    
                        if recCondDtl.CYID_TYPE_RLST_1 is not null then
                            if upper(recCondDtl.CYID_TYPE_RLST_1) = 'INITIAL_VALUE' then
                                vSql          := 'select '||recCondDtl.CYID_VALUE_RLST_1||' from dual';
                                begin
                                    vDataFormula1 := nvl(MGTAPPS.FEXECQUERY(vSql),0);
                                exception
                                    when no_data_found then
                                        null;
                                end;                                                         
                            elsif upper(recCondDtl.CYID_TYPE_RLST_1) = 'FROM_DATA' then
                                vSql := 'SELECT trim(CYC_DATA_VALUE) CYC_DATA_VALUE
                             FROM mgtapps.cst_yarn_calculation c
                             WHERE cyc_cyl_sys_id IN (
                                  SELECT cyl_sys_id
                                    FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                   WHERE cyc_sys_id = '||vCYIH_CYC_SYS_ID||'
                                     AND cyc_prs_type = '||pPRS_TYPE||'
                                     AND cyl_sys_id = cyc_cyl_sys_id
                                     AND cyc_prs_type = cyl_prs_type)
                            and cyc_top_no = '||recCondDtl.CYID_TOP_NO_RLST_1||'';
                            
                            
                                begin
                                    vDataFormula1 := nvl(MGTAPPS.FEXECQUERY(vSql),0);
                                exception
                                    when no_data_found then
                                        null;
                                end;                                                                                 
                            end if;
                        else vDataFormula1 := null;
                        end if;

                        if recCondDtl.CYID_TYPE_RLST_2 is not null then
                            if upper(recCondDtl.CYID_TYPE_RLST_2) = 'INITIAL_VALUE' then
                                vSql          := 'select '||recCondDtl.CYID_VALUE_RLST_2||' from dual';
                                begin
                                    vDataFormula2 := nvl(MGTAPPS.FEXECQUERY(vSql),0);
                                exception
                                    when no_data_found then
                                        null;
                                end;                                                         
                            elsif upper(recCondDtl.CYID_TYPE_RLST_2) = 'FROM_DATA' then
                                --vSql := 'select nvl(trim(CYC_DATA_VALUE),0) CYC_DATA_VALUE from CST_YARN_CALCULATION '||'where CYC_SYS_ID ='''||recCondDtl.CYID_VALUE_RLST_2||''' ';
                                vSql := 'SELECT trim(CYC_DATA_VALUE) CYC_DATA_VALUE
                                         FROM mgtapps.cst_yarn_calculation c
                                         WHERE cyc_cyl_sys_id IN (
                                              SELECT cyl_sys_id
                                                FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_left l
                                               WHERE cyc_sys_id = '||vCYIH_CYC_SYS_ID||'
                                                 AND cyc_prs_type = '||pPRS_TYPE||'
                                                 AND cyl_sys_id = cyc_cyl_sys_id
                                                 AND cyc_prs_type = cyl_prs_type)
                                        and cyc_top_no = '||recCondDtl.CYID_TOP_NO_RLST_2||'';                                
                                vDataFormula2 := nvl(MGTAPPS.FEXECQUERYERR(vSql,vErrPrs),0);
                            end if;
                        else vDataFormula2 := null;
                        end if;

                        if recCondDtl.max_dt = 1 then
                            if recCondDtl.CYID_OPRTR_1 is null then
                                vDataValue := null; 
                            else
                                if recCondDtl.CYID_OPRTR_2 is null then
                                    vDataValue := vDataFormula1;
                                else                            
                                   vSql := ' select '||vDataFormula1||' '||recCondDtl.CYID_OPRTR_2||' '||vDataFormula2||' from dual ';       
                                    begin
                                        vDataValue := MGTAPPS.fExecQuery(vSql);
                                    exception
                                        when no_data_found then
                                          vDataValue := '0';
                                    end;                            
                                end if;                            
                            end if;     
                        else
                        declare
                            vTmpDataValue varchar2(300);
                        begin
                            if recCondDtl.CYID_OPRTR_1 is null then
                                vTmpDataValue := null; 
                            else
                                if recCondDtl.CYID_OPRTR_2 is null then
                                    vTmpDataValue := vDataFormula1;
                                else                            
                                   vSql := ' select '||vDataFormula1||' '||recCondDtl.CYID_OPRTR_2||' '||vDataFormula2||' from dual ';       
                                    begin
                                        vTmpDataValue := MGTAPPS.fExecQuery(vSql);
                                    exception
                                        when no_data_found then
                                          vTmpDataValue := '0';
                                    end;                            
                                end if;                            
                            end if;
                            
                            if recCondDtl.max_dt = 1 then
                                vDataValue := vTmpDataValue;                                
                            else
                                vSql := ' select '||vDataValue||' '||recCondDtl.CYID_OPRTR_1||' '||vTmpDataValue||' from dual ';
                                vDataValue  := MGTAPPS.fExecQuery(vSql);                                                                 
                            end if;
                        end;                                                            
                        end if;                            
                    end loop;
                end if;            
            --end IF_CONDITION                    
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_MASTER_YARN' then --6. FROM_YARN_MASTER
                vSqlDtMstYarn := null;                                      
                vCMY_SYS_ID := fGet_CMY_SYS_ID(recYarn.CYC_CYL_SYS_ID);
                vDataValue := null;
                if vCMY_SYS_ID is not null then
                    vTotMstYarn := 0;
                    for recMst in (                    
                        select * from MGTAPPS.CST_YARN_PRODUCT_PARAM
                        where CYPP_CYC_SYS_ID = recYarn.CYC_SYS_ID
                        and nvl(CYPP_STS_CK,0) = 1 
                        order by CYPP_COLUMN_ID                                                            
                    ) loop   
                        vTotMstYarn := vTotMstYarn + 1;
                        if vTotMstYarn = 1 then
                            vSqlDtMstYarn := recMst.CYPP_COLUMN_NAME;
                        else
                            vSqlDtMstYarn := vSqlDtMstYarn||'||''-''||'||recMst.CYPP_COLUMN_NAME;
                        end if;
                    end loop;
                    if vSqlDtMstYarn is not null then
                       begin
                        vSqlDtMstYarn := 'select '||vSqlDtMstYarn||' from MGTAPPS.CST_MST_YARN where CMY_SYS_ID = '''||vCMY_SYS_ID||''' ';
                        vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                                                                
                       exception
                            when others then
                               vErrPrs := sqlerrm||vSql||chr(13)||'  From Master Yarn  Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE); 
                       end;                
                    end if;                    
                end if;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_MASTER_MACHINE' then --7. FROM_MASTER_MACHINE
                vSqlDtMstYarn := null;                                      
                vCMY_SYS_ID := fGet_CMY_SYS_ID(recYarn.CYC_CYL_SYS_ID);
                vDataValue := null; 
                if vCMY_SYS_ID is not null then
                    vTotMstYarn := 0;
                    for recMst in (                    
                        select distinct * from MGTAPPS.CST_YARN_MACHINE_PARAM
                        where CYMP_CYC_SYS_ID = recYarn.CYC_SYS_ID
                        and nvl(CYMP_STS_CK,0) = 1 
                        order by CYMP_COLUMN_ID                                                            
                    ) loop   
                        vTotMstYarn := vTotMstYarn + 1;
                        if vTotMstYarn = 1 then
                            vSqlDtMstYarn := recMst.CYMP_COLUMN_NAME;
                        else
                            vSqlDtMstYarn := vSqlDtMstYarn||'||''-''||'||recMst.CYMP_COLUMN_NAME;
                        end if;
                    end loop;
                    if vSqlDtMstYarn is not null then
                       begin
                        vSqlDtMstYarn := 'select '||vSqlDtMstYarn||' from MGTAPPS.CST_MST_YARN a,MGTAPPS.CST_MST_MACHINE b '
                                        ||' where CMY_SYS_ID = '''||vCMY_SYS_ID||''' '
                                        ||' and CMY_MACHINE_CODE = CMM_MACHINE_CODE ';
                        vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
                                        
                       exception
                            when others then
                               vErrPrs := sqlerrm||vSql||chr(13)||'  From Master Machine  Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(vErrPrs,pUserId,sysdate,pPRS_TYPE);  
                       end;                
                    end if;                    
                end if;                
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_BOX_BOBIN_COST' then --8. FROM_BOX_BOBIN_COST
                vSqlDtMstYarn := null;     
                vDataValue := null;                                 
                for recMst in (                    
                    select * from MGTAPPS.CST_YARN_BOX_BOBIN_COST
                    where CYBBC_CYC_SYS_ID = recYarn.CYC_SYS_ID
                    and nvl(CYBBC_STS_CK,0) = 1 
                    order by CYBBC_COLUMN_ID                                                            
                ) loop   
                    vSqlDtMstYarn := 'select '||recMst.CYBBC_COLUMN_NAME||' from MGTAPPS.CST_MST_BOX_BOBIN_COST '
                                    ||' where CMBBC_SYS_ID = '''||recMst.CYBBC_CMBBC_SYS_ID||''' ';
                end loop;
                if vSqlDtMstYarn is not null then
                   begin
                    vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
                                            
                   exception
                        when others then
                            vErrPrs := sqlerrm||vSqlDtMstYarn||chr(13)||'  From YARN_BOX_BOBIN_COST Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE); 
                   end;                
                end if;                    
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_SPINNING' then --12 From_Master_Batch_Spinning
                vSqlDtMstYarn := null;     
                vDataValue := null;                                 
                for recMst in (                    
                    
                    select * from MGTAPPS.CST_YARN_M_B_S_MST                    
                    where nvl(CYMBMS_STS_CK,0) = 1
                    
                    and CYMBMS_CYC_SYS_ID = recYarn.CYC_SYS_ID 
                    order by CYMBMS_COLUMN_ID                                                            
                ) loop   
                    vSqlDtMstYarn := 'select '||recMst.CYMBMS_COLUMN_NAME||' from MGTAPPS.CST_MST_BATCH_SPIN '
                                    ||' where CMBS_SYS_ID = '''||recMst.CYMBMS_CMBS_SYS_ID||''' '
                                    --||' where CMBS_CMBH_SYS_ID = '''||recMst.CYMBMS_CMBS_CMBH_SYS_ID||''' '
                                    --||' and nvl(CMBS_CODE,''NULL'') = '''||nvl(recMst.CYMBMS_CMBS_CODE,'NULL')||''' '
                                    ;
                                    
                end loop;
                if vSqlDtMstYarn is not null then
                   begin
                    vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
                                            
                   exception
                        when others then
                           vErrPrs := sqlerrm||' '||vSqlDtMstYarn||chr(13)||'  From CST_YARN_M_B_S_MST Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);
                   end;                
                end if;                
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_DATA' then --12. FROM_MASTER_BATCH_DATA
                vSqlDtMstYarn := null;
                vDataValue := null;                                      
                vSqlDtMstYarn := getSql_M_B_MST(recYarn.CYC_SYS_ID);                
                if vSqlDtMstYarn is not null then
                   begin
                    vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                                                                    
                   exception
                        when others then
                           vErrPrs := sqlerrm||vSqlDtMstYarn||chr(13)||'  CST_MST_BATCH_HEAD Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE); 
                   end;                
                end if;                
            
                if vDataValue is null then
                    vCMCH_PERIOD_YEAR := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingMb,vErrPrs),'YYYYMM'),'YYYY');
                    vCMCH_PERIOD_MONTH := to_char(to_date(MGTAPPS.fGetParamDBValue(vPeriodCostingMb,vErrPrs),'YYYYMM'),'MM');
                   
                    if vCMCH_PERIOD_YEAR is not null then
                        for recMst in (                    
                            select * from MGTAPPS.CST_YARN_M_B_DT                        
                            where CYMBD_CYC_SYS_ID = recYarn.CYC_SYS_ID
                            and nvl(CYMBD_STS_CK,0) = 1 
                            order by CYMBD_COLUMN_ID                                                            
                        ) loop   
                            vSqlDtMstYarn := 'select '||recMst.CYMBD_COLUMN_NAME||' from CST_MB_CONSUMP_HEAD '
                                            ||' where CMCH_CMBH_SYS_ID = '''||recMst.CYMBD_CMBH_SYS_ID||''' ' 
                                            ||' and CMCH_PERIOD_YEAR = '||vCMCH_PERIOD_YEAR
                                            ||' and CMCH_PERIOD_MONTH = '||vCMCH_PERIOD_MONTH;
                        end loop;
                        --insert into CST_YARN_ERR_LOG values(recYarn.CYC_SYS_ID||' '||vSqlDtMstYarn); 
                        if vSqlDtMstYarn is not null then
                           begin
                            vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                                                                    
                           exception
                                when others then
                                   vErrPrs := sqlerrm||vSqlDtMstYarn||chr(13)||'  From YARN_BOX_BOBIN_COST Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE); 
                           end;                
                        end if;                
                   end if;
                end if;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'RAW_MATERIAL' then --10. RAW_MATERIAL
              for recHdr in (                    
                    select * 
                    from CST_YARN_RM_HDR                    
                    where CYRH_CYC_SYS_ID = recYarn.CYC_SYS_ID
              ) loop
                if recHdr.CYRH_TYPE = 'Store Rate' then
                    for recDtl in (                        
                        select * 
                        from CST_YARN_RM_DTL                        
                        where CYRD_CYRH_SYS_ID = recHdr.CYRH_SYS_ID
                    ) 
                    loop
                       vSqlDtMstYarn := null; 
                       if recDtl.CYRD_TYPE_DATA = 'Group Item Name' then
                         vSqlDtMstYarn := 'select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm('''||recDtl.CYRD_RM_CODE||''') dt from dual';
                       elsif recDtl.CYRD_TYPE_DATA = 'From Group Item LC' then 
                        -- From Group Item LC
                         vSqlDtMstYarn := 'select MGTAPPS.pkg_yarn_calculation.fGetItemGrpRate('''||recDtl.CYRD_RM_CODE||''') dt from dual';
                       elsif recDtl.CYRD_TYPE_DATA = 'From Group Item MKT Rate' then
                       -- From Group Item MKT Rate
                        vSqlDtMstYarn := 'select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('''||recDtl.CYRD_RM_CODE||''') dt from dual';
                       elsif recDtl.CYRD_TYPE_DATA = 'From Group Item MKT LC' then
                       -- From Group Item MKT LC
                        vSqlDtMstYarn := 'select MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLC('''||recDtl.CYRD_RM_CODE||''') dt from dual';
                       elsif recDtl.CYRD_TYPE_DATA = 'From Group Item VAL LC' then
                       -- From Group Item VAL LC
                        vSqlDtMstYarn := 'select MGTAPPS.pkg_yarn_calculation.fGetItemGrpValLC('''||recDtl.CYRD_RM_CODE||''') dt from dual';                          
                       end if;
                        -- From MB
                        -- From Yarn
                       if vSqlDtMstYarn is not null then
                       begin    
                        vErrPrs := null;                   
                        vDataValue := MGTAPPS.fExecQueryErr(vSqlDtMstYarn,vErrPrs);                        
                        if vErrPrs is not null then
                           vErrPrs := vErrPrs||chr(13)||vSqlDtMstYarn||'  From Group Item Set vDataValue:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);
                        end if;                     
                       end;                       
                       end if;
                    end loop;
                elsif recHdr.CYRH_TYPE = 'Captive Cost' then
                    for recDtl in (                        
                        select * 
                        from CST_YARN_RM_CAPTIVE                        
                        where CYRC_CYRH_SYS_ID = recHdr.CYRH_SYS_ID
                    ) 
                    loop
                       begin    
                        vErrPrs := null;                   
                        vSqlDtMstYarn := 'select trim(CYC_DATA_VALUE) CYC_DATA_VALUE from cst_yarn_calculation '
                                         ||' where CYC_CYL_SYS_ID = '''||recDtl.CYRC_CYL_SYS_ID||''' '
                                         ||' and CYC_CYT_SYS_ID = '''||recDtl.CYRC_CYT_SYS_ID||'''  ';
                        vDataValue := MGTAPPS.fExecQueryErr(vSqlDtMstYarn,vErrPrs);                        
                        if vErrPrs is not null then
                           vErrPrs := vErrPrs||chr(13)||vSqlDtMstYarn||'  From Captive Cost:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);
                        end if; 
                       end;                       
                    end loop;
                elsif recHdr.CYRH_TYPE in ('Multi Yarn','Yarn Rate') then
                    if nvl(recHdr.CYRH_RM_COST,0) <> 0 then
                        vDataValue := recHdr.CYRH_RM_COST;                        
                    end if; 
                end if;
              end loop;
              
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_PRODUCT_GRADE' then --11. FROM_PRODUCT_GRADE
                vSqlDtMstYarn := null;                                      
                vDataValue := null;
                for recMst in (                    
                    select * from MGTAPPS.CST_YARN_PRODUCT_GRADE
                    where CYPG_CYC_SYS_ID = recYarn.CYC_SYS_ID
                    and nvl(CYPG_STS_CK,0) = 1 
                    order by CYPG_COLUMN_ID                                                            
                ) loop   
                   begin
                    vSqlDtMstYarn := 'select '||recMst.CYPG_COLUMN_NAME||' from MGTAPPS.CST_MST_PRODUCT_GRADE where CMPG_SYS_ID = '''
                                    ||recMst.CYPG_CMPG_SYS_ID||''' ';
                    vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
                                        
                   exception
                        when others then
                           vErrPrs := vSqlDtMstYarn||chr(13)||' MGTAPPS.CST_YARN_PRODUCT_GRADE:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);
                            
                   end;                
                end loop;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'SHADE_CODE' then --Shade_Code
            begin
                select b.CYL_SHADE_CODE into vDataValue
                from cst_yarn_left b
                     ,cst_yarn_calculation c
                where c.CYC_SYS_ID = recYarn.CYC_SYS_ID
                and b.CYL_SYS_ID = c.CYC_CYL_SYS_ID
                and b.CYL_SHADE_CODE is not null
                and c.cyc_PRS_TYPE = pPRS_TYPE
                and cyc_PRS_TYPE = cyl_PRS_TYPE
                ;
            exception
                when no_data_found then
                    null;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'SHADE_NAME' then --SHADE_NAME
            begin
                select b.CYL_SHADE_NAME into vDataValue
                from cst_yarn_left b
                     ,cst_yarn_calculation c
                where c.CYC_SYS_ID = recYarn.CYC_SYS_ID
                and b.CYL_SYS_ID = c.CYC_CYL_SYS_ID
                and b.CYL_SHADE_NAME is not null
                and c.cyc_PRS_TYPE = pPRS_TYPE
                and cyc_PRS_TYPE = cyl_PRS_TYPE;
            exception
                when no_data_found then
                    null;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'IS_VALUATION' then --Shade_Code
            begin
                select b.CYL_IS_VALUATION into vDataValue
                from cst_yarn_left b
                     ,cst_yarn_calculation c
                where c.CYC_SYS_ID = recYarn.CYC_SYS_ID
                and b.CYL_SYS_ID = c.CYC_CYL_SYS_ID
                and c.cyc_PRS_TYPE = pPRS_TYPE
                and cyc_PRS_TYPE = cyl_PRS_TYPE
                ;
            exception
                when no_data_found then
                    null;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'FROM_MARKETING_COST' then --SHADE_NAME
            begin
                select CYC_DATA_VALUE  into vDataValue
                from mgtapps.cst_yarn_calculation c
                where c.cyc_PRS_TYPE = vIdMkt--'20210800119'
                and CYC_LEFT_NO = recYarn.CYC_LEFT_NO
                and CYC_TOP_NO = recYarn.CYC_TOP_NO
                ;
                
            exception
                when no_data_found then
                    null;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'POY POWER FUNCTION' then -- POY Power Function
            begin
                vSqlDtMstYarn := 'select mgtapps.PkgFormulaYarn.fPoyPower_87('||recYarn.CYC_SYS_ID||') from dual ';
                vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
            exception
                when others then                
                    vDataValue := 0;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'POY MAN POWER FUNCTION' then -- POY Man Power Function
            begin
                vSqlDtMstYarn := 'select mgtapps.PkgFormulaYarn.fPoyManPower_88('||recYarn.CYC_SYS_ID||') from dual ';
                vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
            exception
                when others then                
                    vDataValue := 0;
            end;            
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'POY OVERHEADS FUNCTION' then -- POY Overheads Function
            begin
                vSqlDtMstYarn := 'select mgtapps.PkgFormulaYarn.fPoyOverheads_89('||recYarn.CYC_SYS_ID||') from dual ';
                vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
            exception
                when others then                
                    vDataValue := 0;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'POY CONS, SPARES FUNCTION' then -- POY Cons, Spares Function
            begin
                vSqlDtMstYarn := 'select mgtapps.PkgFormulaYarn.fPoyConsSprs_90('||recYarn.CYC_SYS_ID||') from dual ';
                vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
            exception
                when others then                
                    vDataValue := 0;
            end;
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'CUSTOMER PRODUCT FUNCTION' then -- CUSTOMER PRODUCT FUNCTION
            begin
                vSqlDtMstYarn := 'select mgtapps.PkgFormulaYarn.fGetCustomerProduct('||recYarn.CYC_SYS_ID||') from dual ';
                vDataValue := MGTAPPS.fExecQuery(vSqlDtMstYarn);                        
            exception
                when others then                
                    vDataValue := 0;
            end;                                
            elsif upper(recYarn.CYC_FORMULA_TYPE) = 'INTERMENGLING DATA' then -- Intermengling Data
            begin                
                SELECT cymi_value / 100
                  INTO vdatavalue
                  FROM mgtapps.cst_yarn_calculation c, mgtapps.cst_yarn_mst_intermingling i
                 WHERE c.cyc_prs_type = recyarn.cyc_prs_type                   --'20210800119'
                   AND cyc_top_no = 18
                   AND cyc_left_no IN (
                          SELECT cyc_left_no
                            FROM mgtapps.cst_yarn_calculation c
                           WHERE c.cyc_prs_type = recyarn.cyc_prs_type         --'20210800119'
                             AND cyc_sys_id = recyarn.cyc_sys_id             --'2021092367353'
                                                                )
                   AND UPPER (cymi_description) = UPPER (cyc_data_value);
            exception
                when zero_divide then
                   vdatavalue := 0;                 
            end;       
            end if;
            
            if nvl(recYarn.CYC_DATA_VALUE,'NULL') <> nvl(vDataValue,'NULL') then
                begin
                    vDataValue := round(to_number(vDataValue),4);                    
                exception
                    when others then
                        null;              
                end;                 
                update CST_YARN_CALCULATION
                    set CYC_DATA_VALUE = vDataValue
                        ,CYC_MODIFIED_BY = pUserId
                        , CYC_MODIFIED_TIMESTAMP = sysdate
                where CYC_SYS_ID = recYarn.CYC_SYS_ID;
                
            end if;
            
            MGTAPPS.Pkg_Yarn_Calculation_Prs.pIns_Cur( pUserId,recYarn.CYC_PRS_TYPE,recYarn.CYC_CYL_SYS_ID,recYarn.CYC_LEFT_NO,recYarn.CYC_TOP_NO,vDataValue,vErrPrs);
                        
            if recYarn.CYC_UPD_YARN_LEFT is not null then
            begin
                vSqlDtMstYarn := 'update CST_YARN_LEFT set '||recYarn.CYC_UPD_YARN_LEFT||'= '''||vDataValue||''' '   
                                 ||' where CYL_SYS_ID = '''||recYarn.CYC_CYL_SYS_ID||''' ';
                --insert into CST_YARN_ERR_LOG values(vSqlDtMstYarn);                                      
                execute immediate vSqlDtMstYarn;
            exception
                when others then
                    vErrPrs := vSqlDtMstYarn||chr(13)||' Update CST_YARN_LEFT:Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(vErrPrs,pUserId,sysdate,pPRS_TYPE);                      
            end;                                        
            end if;                
        exception
            when others then
                vErrPrs := vSqlDtMstYarn||chr(13)||' Process Calculate :Left '||recYarn.CYC_LEFT_NO||',Top '||recYarn.CYC_TOP_NO;
                --insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                --values(substr(vErrPrs,1,4000),pUserId,sysdate,pPRS_TYPE);         
        end;     
        
            --if nvl(recYarn.CYC_PROCESS_SEQ,0) = 0 and recYarn.CYC_TOP_NO = 18 then
            --    pRefIntemingleVal(pUserID,recYarn.CYC_CYL_SYS_ID,pPRS_TYPE);
            --end if;
        commit;    

        end loop;
        
        
        --pSetREFRESH_CST_YARN('N');
        -- update value If Condition Rslt 1
        BEGIN
           FOR updifcond IN (SELECT   c.cyc_prs_type, c.cyc_cyl_sys_id,
                                      c.cyc_left_no, cyid_sys_id, cyid_cyih_sys_id,
                                      cyid_type_rlst_1, cyid_value_rlst_1,
                                      cyid_top_no_rlst_1, cyid_value_data_rlst_1,
                                      v.cyc_sys_id, v.cyc_data_value
                                 FROM mgtapps.cst_yarn_ifcond_dtl d,
                                      mgtapps.cst_yarn_ifcond_hdr h,
                                      mgtapps.cst_yarn_calculation c,
                                      mgtapps.cst_yarn_filter f,
                                      mgtapps.cst_yarn_calculation v
                                WHERE cyih_sys_id = cyid_cyih_sys_id
                                  AND cyid_type_rlst_1 = 'From_Data'
                                  AND cyih_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id = cyf_cyl_sys_id
                                  AND cyf_user_id = pUserID
                                  AND c.cyc_prs_type = v.cyc_prs_type
                                  AND c.cyc_cyl_sys_id = v.cyc_cyl_sys_id
                                  AND cyid_top_no_rlst_1 = v.cyc_top_no
                                  AND NVL (cyid_value_data_rlst_1, 0) <>
                                                             NVL (v.cyc_data_value, 0)
                             ORDER BY cyc_left_no)
           LOOP
              UPDATE mgtapps.cst_yarn_ifcond_dtl
                 SET cyid_value_data_rlst_1 = updifcond.cyc_data_value
               WHERE cyid_sys_id = updifcond.cyid_sys_id;
           END LOOP;

           COMMIT;
        END;
        
        -- update value If Condition Rslt 2
        BEGIN
           FOR updifcond IN (SELECT   c.cyc_prs_type, c.cyc_cyl_sys_id,
                                      c.cyc_left_no, cyid_sys_id, cyid_cyih_sys_id,
                                      cyid_type_rlst_2, cyid_value_rlst_2,
                                      cyid_top_no_rlst_2, cyid_value_data_rlst_2,
                                      v.cyc_sys_id, v.cyc_data_value
                                 FROM mgtapps.cst_yarn_ifcond_dtl d,
                                      mgtapps.cst_yarn_ifcond_hdr h,
                                      mgtapps.cst_yarn_calculation c,
                                      mgtapps.cst_yarn_filter f,
                                      mgtapps.cst_yarn_calculation v
                                WHERE cyih_sys_id = cyid_cyih_sys_id
                                  AND cyid_type_rlst_2 = 'From_Data'
                                  AND cyih_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id = cyf_cyl_sys_id
                                  AND cyf_user_id = pUserID
                                  AND c.cyc_prs_type = v.cyc_prs_type
                                  AND c.cyc_cyl_sys_id = v.cyc_cyl_sys_id
                                  AND cyid_top_no_rlst_2 = v.cyc_top_no
                                  AND NVL (cyid_value_data_rlst_2, 0) <>
                                                             NVL (v.cyc_data_value, 0)
                             ORDER BY cyc_left_no)
           LOOP
              UPDATE mgtapps.cst_yarn_ifcond_dtl
                 SET cyid_value_data_rlst_2 = updifcond.cyc_data_value
               WHERE cyid_sys_id = updifcond.cyid_sys_id;
           END LOOP;

           COMMIT;
        END;        
        
        
        for recLeftTot in (
                        select CYL_SYS_ID, CYL_LEFT_NO,cyl_type 
                from    CST_YARN_FILTER f
                        ,CST_YARN_LEFT l 
                where CYF_USER_ID = puserid
                and CYF_PRS_TYPE = pPRS_TYPE
                and CYL_PRS_TYPE = CYF_PRS_TYPE
                and f.CYF_CYL_SYS_ID = l.CYL_SYS_ID
                and nvl(CYF_IS_EDIT,'N') =  nvl(pCYFH_IS_EDIT,'N')                
                order by CYL_LEFT_NO
        ) loop                                
            mgtapps.pkgYarnLeftTot.process( pUserID
                                            ,recLeftTot.CYL_SYS_ID
                                            );
        end loop;       
        end if;
        commit;
    end process;
    
    -- ** LEFT ** --

    function get_CYC_SYS_ID(pLEFT_NO number, pTOP_NO number,pPRS_TYPE varchar2)    return varchar2 is
        vCYC_SYS_ID CST_YARN_CALCULATION.CYC_SYS_ID%type;
    begin
        select CYC_SYS_ID into vCYC_SYS_ID 
        from CST_YARN_CALCULATION
        where CYC_LEFT_NO = pLEFT_NO
        and CYC_TOP_NO = pTOP_NO
        and CYC_PRS_TYPE = pPRS_TYPE;
        
        return vCYC_SYS_ID;
    exception
        when no_data_found then
            return null;
    end get_CYC_SYS_ID;
    
    function get_CYC_DATA_VALUE(pCYC_SYS_ID varchar2,pCYL_SYS_ID varchar2,pCYT_SYS_ID varchar2) return varchar2 is
        vCYC_DATA_VALUE CST_YARN_CALCULATION.CYC_DATA_VALUE%type;
    begin
        vCYC_DATA_VALUE := null;
        if pCYC_SYS_ID is not null then
        begin
            select CYC_DATA_VALUE into vCYC_DATA_VALUE 
            from CST_YARN_CALCULATION
            where CYC_SYS_ID = pCYC_SYS_ID;
                    
            return vCYC_DATA_VALUE;
        exception
            when no_data_found then
                null;
        end;
        elsif pCYL_SYS_ID is not null and pCYT_SYS_ID is not null then
        begin
            select CYC_DATA_VALUE into vCYC_DATA_VALUE 
            from CST_YARN_CALCULATION
            where CYC_CYL_SYS_ID = pCYL_SYS_ID 
            and CYC_CYT_SYS_ID = pCYT_SYS_ID;
                    
            return vCYC_DATA_VALUE;
        exception
            when no_data_found then
                null;
        end;        
        end if;
        return vCYC_DATA_VALUE;
    end get_CYC_DATA_VALUE;
    
    function get_CYL_SYS_ID(pLEFT_NO number,pCYL_PRS_TYPE varchar2)    return varchar2 is
        vCYL_SYS_ID CST_YARN_LEFT.CYL_SYS_ID%type;
    begin
        select CYL_SYS_ID into vCYL_SYS_ID 
        from CST_YARN_LEFT
        where CYL_LEFT_NO = pLEFT_NO
        and CYL_PRS_TYPE = pCYL_PRS_TYPE;
        
        return vCYL_SYS_ID;
    exception
        when no_data_found then
            return null;
    end get_CYL_SYS_ID;
    
    function get_LEFT_NO(pSts number,pParamValue varchar2,pPrsType varchar2)    return varchar2 is
        vCYL_LEFT_NO CST_YARN_LEFT.CYL_LEFT_NO%type;
    begin
        if pSts = 1 then
            select CYL_LEFT_NO into vCYL_LEFT_NO 
            from CST_YARN_LEFT
            where CYL_MARKETING_COST_LINK = pParamValue
            and CYL_PRS_TYPE = pPrsType ;
        end if;        
        return vCYL_LEFT_NO;
    exception
        when no_data_found then
            return null;
        when too_many_rows then
            return null;
    end get_LEFT_NO;
    
    function getTotTop(pCYT_PRS_TYPE varchar2) return number is
        vRtn number;
    begin
        select max(CYT_TOP_NO) into vRtn 
        from mgtapps.cst_yarn_top
        where CYT_PRS_TYPE = pCYT_PRS_TYPE;
        
        return vRtn;
    exception
    when no_data_found then
        return 0;             
    end getTotTop;

    function get_TOP_LABLE(pCYT_SYS_ID varchar2,pCYT_TOP_NO number)    return varchar2 is
        vCYT_NAME cst_yarn_top.CYT_NAME%type;
    begin
        vCYT_NAME := null;
        if pCYT_SYS_ID is not null then
        begin
            select CYT_NAME into vCYT_NAME 
            from cst_yarn_top
            where CYT_SYS_ID = pCYT_SYS_ID;
        exception
            when no_data_found then
                null;
        end;    
        elsif pCYT_TOP_NO is not null then
        begin
            select CYT_NAME into vCYT_NAME 
            from cst_yarn_top
            where CYT_TOP_NO = pCYT_TOP_NO;
        exception
            when no_data_found then
                null;
        end;             
        end if;
        
        return vCYT_NAME;
    end get_TOP_LABLE;
    
    function get_TOP_LABLE1(pCYT_PRS_TYPE varchar2,pCYT_TOP_NO number) return varchar2 is
        vCYT_NAME cst_yarn_top.CYT_NAME%type;
    begin
        begin
            select CYT_NAME into vCYT_NAME 
            from cst_yarn_top
            where CYT_TOP_NO = pCYT_TOP_NO
            and CYT_PRS_TYPE = pCYT_PRS_TYPE;
        exception
            when no_data_found then
                null;
        end;    
        
        return vCYT_NAME;
    end get_TOP_LABLE1;
    
    function get_YarnLeftData(pCYL_SYS_ID varchar2,pCYL_LEFT_NO number)    return cst_yarn_left%rowtype is
        vcst_yarn_left cst_yarn_left%rowtype;
    begin
        vcst_yarn_left:= null;
        if pCYL_SYS_ID is not null then
        begin
            select * into vcst_yarn_left 
            from cst_yarn_left
            where CYL_SYS_ID = pCYL_SYS_ID;
        exception
            when no_data_found then
                null;
        end;    
        elsif pCYL_LEFT_NO is not null then
        begin
            select * into vcst_yarn_left 
            from cst_yarn_left
            where CYL_LEFT_NO = pCYL_LEFT_NO;
        exception
            when no_data_found then
                null;
        end;             
        end if;
        
        return vcst_yarn_left;
    end get_YarnLeftData;
    
    
    function get_GROUP_CODE(pCGH_SYS_ID varchar2)    return varchar2 is
        vCGH_GROUP_CODE CST_GRP_HEAD.CGH_GROUP_CODE%type;
    begin
        vCGH_GROUP_CODE := null;
        begin
            select CGH_GROUP_CODE into vCGH_GROUP_CODE 
            from CST_GRP_HEAD
            where CGH_SYS_ID = pCGH_SYS_ID;
        exception
            when no_data_found then
                null;
        end;    
        
        return vCGH_GROUP_CODE;
    end get_GROUP_CODE;    
        
    function get_CYT_SYS_ID(pTOP_NO number,pCYT_PRS_TYPE varchar2)    return varchar2 is
        vCYT_SYS_ID CST_YARN_TOP.CYT_SYS_ID%type;
    begin
        select CYT_SYS_ID into vCYT_SYS_ID 
        from CST_YARN_TOP
        where CYT_TOP_NO = pTOP_NO
        and CYT_PRS_TYPE = pCYT_PRS_TYPE;
        
        return vCYT_SYS_ID;
    exception
        when no_data_found then
            return null;
    end get_CYT_SYS_ID;
    
    procedure pAddLeft(pData MGTAPPS.CST_YARN_LEFT%rowtype) is
        verrmsg varchar2(300);
        vCMY_SYS_ID MGTAPPS.CST_YARN_LEFT.CYL_SYS_ID%type;
    BEGIN 
      vCMY_SYS_ID :=
                TO_CHAR (SYSDATE, 'YYYYMM')
             || TO_CHAR (pkg_seq_no.next_value ('CMY_SYS_ID',
                                                'ADMIN',
                                                verrmsg
                                               ),
                         'fm00000'
                        );
        insert into CST_MST_YARN 
            (CMY_SYS_ID, CMY_NAME, CMY_TYPE, CMY_SHADE_CODE,CMY_SHADE_NAME)
        values 
            (vCMY_SYS_ID,pData.CYL_NAME,pData.CYL_TYPE,pData.CYL_SHADE_CODE,pData.CYL_SHADE_NAME );								
					
				
        insert into MGTAPPS.CST_YARN_LEFT(
            CYL_SYS_ID, CYL_NAME, CYL_TYPE, CYL_SHADE_CODE, CYL_SHADE_NAME
            ,CYL_LEFT_NO, CYL_CMY_SYS_ID
        ) values (
            pData.CYL_SYS_ID, pData.CYL_NAME, pData.CYL_TYPE, pData.CYL_SHADE_CODE, pData.CYL_SHADE_NAME
            ,pData.CYL_LEFT_NO, vCMY_SYS_ID
        );					
    end pAddLeft;
    -- ** LEFT ** --
    
    -- ** TOP ** --
    procedure pDelTop(pData CST_YARN_TOP%rowtype) is
    begin
        for rec in (            
            select * 
            from CST_YARN_CALCULATION
            where CYC_CYT_SYS_ID = pData.CYT_SYS_ID                 
        ) loop
        
            delete from CST_YARN_FORMULA_CALC
            where CYFC_CYC_SYS_ID = rec.CYC_SYS_ID;
            commit;
        
            delete from CST_YARN_IFCOND_DTL
            where CYID_CYIH_SYS_ID in 
            ( 
                select CYIH_SYS_ID 
                from CST_YARN_IFCOND_HDR where CYIH_CYC_SYS_ID = rec.CYC_SYS_ID
            );
            commit;
            
            delete from CST_YARN_IFCOND_HDR
            where CYIH_CYC_SYS_ID = rec.CYC_SYS_ID;      
            commit; 
        end loop;     
        
        delete from CST_YARN_CALCULATION
        where CYC_CYT_SYS_ID = pData.CYT_SYS_ID;
        
        delete CST_YARN_TOP
	    where CYT_SYS_ID = pData.CYT_SYS_ID;
    end pDelTop;    
    
    procedure pAddTop(pUserId varchar2,pData MGTAPPS.CST_YARN_TOP%rowtype) is
    	vCYT_SYS_ID MGTAPPS.CST_YARN_TOP.CYT_SYS_ID%type;
        verrmsg varchar(1000);
        vDt_YARN_CALCULATION CST_YARN_CALCULATION%rowtype;
    begin
        if nvl(pData.CYT_SYS_ID,'123') = '123' then  				
          vCYT_SYS_ID :=
                    TO_CHAR (SYSDATE, 'YYYYMM')
                 || TO_CHAR (pkg_seq_no.next_value ('CYT_SYS_ID',
                                                    'ADMIN',
                                                    verrmsg
                                                   ),
                             'fm00000'
                            );
        end if;                         	
				
        insert into MGTAPPS.CST_YARN_TOP(
            CYT_SYS_ID, CYT_NAME, CYT_TOP_NO
            ,CYT_PRS_TYPE, CYT_PRS_NAME
        ) values (
            vCYT_SYS_ID, pData.CYT_NAME, pData.CYT_TOP_NO
            ,pData.CYT_PRS_TYPE, pData.CYT_PRS_NAME
        );
        
        
        for rec in (
            select * from CST_YARN_LEFT
            where CYL_PRS_TYPE = pData.CYT_PRS_TYPE
        ) loop
        
            vDt_YARN_CALCULATION := null;
                
	        vDt_YARN_CALCULATION.CYC_SYS_ID :=
	        TO_CHAR (SYSDATE, 'YYYYMMDD')||
	        TO_CHAR (pkg_seq_no.next_value ('CYC_SYS_ID',
	                                        'ADMIN',
	                                        verrmsg
				                                       ),'FM000000000000000000000');
                                                       
            vDt_YARN_CALCULATION.CYC_CYT_SYS_ID := vCYT_SYS_ID;           
            vDt_YARN_CALCULATION.CYC_TOP_NO :=  pData.CYT_TOP_NO;
            
            vDt_YARN_CALCULATION.CYC_CYL_SYS_ID := rec.CYL_SYS_ID;
            vDt_YARN_CALCULATION.CYC_LEFT_NO    := rec.CYL_LEFT_NO;
            
            vDt_YARN_CALCULATION.CYC_PRS_TYPE   := pData.CYT_PRS_TYPE;
            vDt_YARN_CALCULATION.CYC_PRS_NAME   := pData.CYT_PRS_NAME;
            
            vDt_YARN_CALCULATION.CYC_CREATED_BY := pUserID;
            vDt_YARN_CALCULATION.CYC_CREATED_TIMESTAMP := sysdate;                                                   	      
                                                       
            insert into CST_YARN_CALCULATION 
            values vDt_YARN_CALCULATION;                                                         
        end loop;    
    end pAddTop;    
    -- ** TOP ** --
    
    procedure pClearFormula(
        pCYC_SYS_ID CST_YARN_CALCULATION.CYC_SYS_ID%type
        ) 
    is        
    begin
/*    
6	CST_YARN_BOX_BOBIN_COST
7	CST_YARN_PRODUCT_PARAM
8	CST_YARN_IFCOND_HDR
9	CST_YARN_FORMULA_CALC
10	CST_YARN_MACHINE_PARAM
11	CST_YARN_SAME_ROWS
*/
    
    
        delete from CST_YARN_BOX_BOBIN_COST where CYBBC_CYC_SYS_ID = pCYC_SYS_ID;
    
        delete from CST_YARN_FORMULA_CALC where CYFC_CYC_SYS_ID = pCYC_SYS_ID;
			
        delete from CST_YARN_MACHINE_PARAM where CYMP_CYC_SYS_ID = pCYC_SYS_ID;
			
        delete from CST_YARN_PRODUCT_PARAM where CYPP_CYC_SYS_ID = pCYC_SYS_ID;
			
        delete from CST_YARN_SAME_ROWS where CYSR_CYC_SYS_ID = pCYC_SYS_ID;
			
        delete from CST_YARN_IFCOND_DTL
        where CYID_CYIH_SYS_ID in ( select CYIH_SYS_ID 
                                    from CST_YARN_IFCOND_HDR 
                                    where CYIH_CYC_SYS_ID = pCYC_SYS_ID);
			
        delete from CST_YARN_IFCOND_HDR where CYIH_CYC_SYS_ID = pCYC_SYS_ID;
        
        DELETE FROM CST_YARN_M_B_S_MST WHERE CYMBMS_CYC_SYS_ID = pCYC_SYS_ID;
        
        DELETE FROM CST_YARN_M_B_DT WHERE CYMBD_CYC_SYS_ID = pCYC_SYS_ID;  
              
        DELETE FROM CST_YARN_M_B_MST WHERE CYMBM_CYC_SYS_ID = pCYC_SYS_ID;
        
        DELETE FROM CST_YARN_PRODUCT_GRADE WHERE CYPG_CYC_SYS_ID = pCYC_SYS_ID;
        
        DELETE FROM CST_YARN_RM_MULTI where CYRM_CYRH_SYS_ID in (select CYRH_SYS_ID from CST_YARN_RM_HDR where CYRH_CYC_SYS_ID = pCYC_SYS_ID);
        
        DELETE FROM CST_YARN_RM_DTL where CYRD_CYRH_SYS_ID in (select CYRH_SYS_ID from CST_YARN_RM_HDR where CYRH_CYC_SYS_ID = pCYC_SYS_ID);
        
        DELETE FROM CST_YARN_RM_CAPTIVE where CYRC_CYRH_SYS_ID in (select CYRH_SYS_ID from CST_YARN_RM_HDR where CYRH_CYC_SYS_ID = pCYC_SYS_ID);
        
        DELETE FROM CST_YARN_RM_UNEVEN_PKG where CYRUP_CYRH_SYS_ID in (select CYRH_SYS_ID from CST_YARN_RM_HDR where CYRH_CYC_SYS_ID = pCYC_SYS_ID);
                        
        DELETE FROM CST_YARN_RM_HDR where CYRH_CYC_SYS_ID = pCYC_SYS_ID;
        
        DELETE FROM CST_YARN_LOV_DATA where CYLD_CYC_SYS_ID = pCYC_SYS_ID;
        
    end pClearFormula; 
 
    procedure pDelYarnCalculation(pCYC_SYS_ID CST_YARN_CALCULATION.CYC_SYS_ID%type) is
    begin
        dbms_output.put_line('del '||pCYC_SYS_ID);
        pClearFormula(pCYC_SYS_ID);
                                         

        DELETE FROM cst_yarn_calculation a
              WHERE CYC_SYS_ID = pCYC_SYS_ID;
        dbms_output.put_line('del '||pCYC_SYS_ID);          
    end pDelYarnCalculation;
    
    procedure pDelLeft(pData CST_YARN_LEFT%rowtype) is
        vCyl_Sys_Id_Del varchar2(30);
    begin
        for rec in (            
            select * 
            from mgtapps.cst_yarn_calculation
            where CYC_CYL_SYS_ID = pData.CYL_SYS_ID                                 
        ) loop
            pDelYarnCalculation(rec.CYC_SYS_ID);
       end loop;
       
        update mgtapps.CST_YARN_LEFT
            set  CYL_IS_VALUATION = 'N'
        where CYL_SYS_ID = pData.cyl_sys_id_mkt_reff;
        
        update mgtapps.CST_YARN_UPD_DATA
            set CYUD_CYL_SYS_ID_NEW = null
                ,CYUD_STS_PRS = null
        where CYUD_CYL_SYS_ID_NEW = pData.CYL_SYS_ID;
        
        delete from mgtapps.CST_YARN_LEFT_TOT where CYLT_CYL_SYS_ID = pData.CYL_SYS_ID;
        
        delete from mgtapps.CST_YARN_CALCULATION_data where CYCD_CYL_SYS_ID = pData.CYL_SYS_ID;       
        
        delete from mgtapps.CST_YARN_CALCULATION_Cur where CYCC_CYL_SYS_ID = pData.CYL_SYS_ID;
        
        delete from mgtapps.CST_YARN_FILTER where CYF_CYL_SYS_ID = pData.CYL_SYS_ID;
        
        DELETE FROM mgtapps.CST_YARN_RM_MULTI 
        where CYRM_CYL_SYS_ID =  pData.CYL_SYS_ID;

        delete from mgtapps.CST_YARN_LEFT_CUST where cylc_cyl_sys_id  = pData.CYL_SYS_ID;

        delete from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = pData.CYL_SYS_ID;                
        
        DELETE FROM mgtapps.cst_yarn_left_valuation
        where CYLV_CYL_SYS_ID = pData.cyl_sys_id_mkt_reff;        
        
    end pDelLeft;
    
    procedure copy_top(--pCYL_SYS_ID_To varchar2,
                       pUserId varchar2
                       ,pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                       ,pRecYarnCalFrom cst_YARN_CALCULATION%rowtype
                       ,pCYL_SYS_ID_New out varchar2
                       ,pCYC_SYS_ID_Dest varchar2 default null
                       ,pCYC_PRS_TYPE varchar2
                       ,pErrMsg out varchar
                       ) is
        vCYC_SYS_ID varchar2(30);
        verrmsg varchar2(1000):=null;
        vDt_YARN_CALCULATION CST_YARN_CALCULATION%rowtype;
        recYarnCal CST_YARN_CALCULATION%rowtype;
        vDt_YARN_LEFT cst_YARN_LEFT%rowtype;
        
        vDt_YARN_FORMULA_CALC CST_YARN_FORMULA_CALC%rowtype;
        
        vExp exception; 
    begin       
        vDt_YARN_LEFT := pDt_YARN_LEFT;
        recYarnCal := pRecYarnCalFrom;
        vCYC_SYS_ID := null;
        vDt_YARN_CALCULATION := null;            
        vDt_YARN_CALCULATION.CYC_CYL_SYS_ID := vDt_YARN_LEFT.CYL_SYS_ID;     
        vDt_YARN_CALCULATION.CYC_LEFT_NO := vDt_YARN_LEFT.CYL_LEFT_NO;
                
        vDt_YARN_CALCULATION.CYC_CYT_SYS_ID := recYarnCal.CYC_CYT_SYS_ID;
        vDt_YARN_CALCULATION.CYC_TOP_NO  := recYarnCal.CYC_TOP_NO;
        vDt_YARN_CALCULATION.CYC_FORMULA_TYPE := recYarnCal.CYC_FORMULA_TYPE;
        vDt_YARN_CALCULATION.CYC_PROCESS_SEQ := recYarnCal.CYC_PROCESS_SEQ;
        vDt_YARN_CALCULATION.CYC_TOP_NO_COPY := recYarnCal.CYC_TOP_NO_COPY;
        vDt_YARN_CALCULATION.CYC_UPD_YARN_LEFT := recYarnCal.CYC_UPD_YARN_LEFT;
        vDt_YARN_CALCULATION.CYC_DATA_VALUE := recYarnCal.CYC_DATA_VALUE;
        vDt_YARN_CALCULATION.CYC_PRS_TYPE := pCYC_PRS_TYPE;
        vDt_YARN_CALCULATION.CYC_PRS_NAME := fGetNmYarnPrs(pCYC_PRS_TYPE);
        if vDt_YARN_CALCULATION.CYC_UPD_YARN_LEFT is not null then
            vDt_YARN_CALCULATION.CYC_DATA_VALUE := null;
        end if;
        
        --, CYC_FORMULA_SCRIPT, CYC_DATA_VALUE, CYC_PROCESS_SEQ, CYC_TOP_NO_COPY, CYC_DATA_VALUE_COPY                                               
                                                            
        if upper(recYarnCal.CYC_FORMULA_TYPE) = 'QUERY' then
        --1.Query
            vDt_YARN_CALCULATION.CYC_FORMULA_SCRIPT := recYarnCal.CYC_FORMULA_SCRIPT; 
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'INITIAL_VALUE' then
        --2.Initial_Value
            vDt_YARN_CALCULATION.CYC_FORMULA_SCRIPT := recYarnCal.CYC_FORMULA_SCRIPT; 
        end if;
        
        declare
            vCkExist number;
        begin
            select 'x' into vCkExist
            from CST_YARN_CALCULATION
            where CYC_LEFT_NO = vDt_YARN_CALCULATION.CYC_LEFT_NO
            and CYC_TOP_NO = vDt_YARN_CALCULATION.CYC_TOP_NO
            and CYC_PRS_TYPE = pCYC_PRS_TYPE;
        exception
            when no_data_found then
                vDt_YARN_CALCULATION.CYC_SYS_ID :=
                            TO_CHAR (SYSDATE, 'YYYYMMDD')||
                            TO_CHAR (pkg_seq_no.next_value ('CYC_SYS_ID',
                                                            'ADMIN',
                                                            verrmsg
                                                           ),'FM000000000000000000000');
                                                           
                vDt_YARN_CALCULATION.CYC_CREATED_BY := pUserID;
                vDt_YARN_CALCULATION.CYC_CREATED_TIMESTAMP := sysdate;                
                insert into CST_YARN_CALCULATION values vDt_YARN_CALCULATION;commit;
            when others then
                select CYC_SYS_ID into vDt_YARN_CALCULATION.CYC_SYS_ID
                from CST_YARN_CALCULATION
                where CYC_LEFT_NO = vDt_YARN_CALCULATION.CYC_LEFT_NO
                and CYC_TOP_NO = vDt_YARN_CALCULATION.CYC_TOP_NO
                and CYC_PRS_TYPE = pCYC_PRS_TYPE;
                
                update CST_YARN_CALCULATION
                    set CYC_FORMULA_TYPE    = vDt_YARN_CALCULATION.CYC_FORMULA_TYPE
                        , CYC_FORMULA_SCRIPT = vDt_YARN_CALCULATION.CYC_FORMULA_SCRIPT
                        , CYC_DATA_VALUE = vDt_YARN_CALCULATION.CYC_DATA_VALUE
                        , CYC_PROCESS_SEQ = vDt_YARN_CALCULATION.CYC_PROCESS_SEQ
                        , CYC_TOP_NO_COPY = vDt_YARN_CALCULATION.CYC_TOP_NO_COPY
                        , CYC_DATA_VALUE_COPY = vDt_YARN_CALCULATION.CYC_DATA_VALUE_COPY
                        , CYC_UPD_YARN_LEFT = vDt_YARN_CALCULATION.CYC_UPD_YARN_LEFT
                where CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;    commit;                              
        end;                                                   
                                                   
        pCYL_SYS_ID_New := vDt_YARN_CALCULATION.CYC_SYS_ID;                                
        
        if upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_DATA' then    
            --3.From_Data
        declare
            vCST_YARN_SAME_ROWS CST_YARN_SAME_ROWS%rowtype:=null;
        begin
            delete from CST_YARN_SAME_ROWS where CYSR_CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;        
            for recDt in (
                select * from CST_YARN_SAME_ROWS
                where CYSR_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                order by nvl(CYSR_SEC_NO,0)                    
            ) loop                                                
                vCST_YARN_SAME_ROWS := null;
                vCST_YARN_SAME_ROWS.CYSR_SYS_ID     :='123';
                vCST_YARN_SAME_ROWS.CYSR_CYC_SYS_ID :=vDt_YARN_CALCULATION.CYC_SYS_ID;
                vCST_YARN_SAME_ROWS.CYSR_TOP_NO     :=recDt.CYSR_TOP_NO;
                vCST_YARN_SAME_ROWS.CYSR_SEC_NO     :=recDt.CYSR_SEC_NO;
                vCST_YARN_SAME_ROWS.CYSR_VALUE      := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,recDt.CYSR_TOP_NO,pCYC_PRS_TYPE);
                --vCST_YARN_SAME_ROWS.CYSR_VALUE_DT
                        
                insert into CST_YARN_SAME_ROWS values vCST_YARN_SAME_ROWS;     commit;                   
            end loop;
        end;    
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FORMULA_DATA' then
            --FORMULA_DATA
            delete from CST_YARN_FORMULA_CALC where CYFC_CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;
            for recYarnForm in (                        
                select * from CST_YARN_FORMULA_CALC
                where CYFC_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                order by nvl(CYFC_SEQ_NO,0)
            )loop                                
                
                vDt_YARN_FORMULA_CALC := null;
                vDt_YARN_FORMULA_CALC.CYFC_CYC_SYS_ID := vDt_YARN_CALCULATION.CYC_SYS_ID;
                vDt_YARN_FORMULA_CALC.CYFC_SYS_ID :=
                        TO_CHAR (SYSDATE, 'YYYYMMDD')
                     || TO_CHAR (pkg_seq_no.next_value ('CYFC_SYS_ID',
                                                        'ADMIN',
                                                        verrmsg
                                                       ),
                                 'fm00000'              
                                ); 
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_1   :=  recYarnForm.CYFC_OPERATOR_1;                     
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_1   :=  recYarnForm.CYFC_TYPE_1;
                vDt_YARN_FORMULA_CALC.CYFC_SEQ_NO   :=  recYarnForm.CYFC_SEQ_NO;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1   :=  recYarnForm.CYFC_VALUE_1;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1   :=  recYarnForm.CYFC_TOP_NO_1;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1,pCYC_PRS_TYPE);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_1,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1   :=  recYarnForm.CYFC_TOP_NO_1; 
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1,pCYC_PRS_TYPE);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_1,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := recYarnForm.CYFC_VALUE_1;                           
                end if;                    
                        
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_2   :=  recYarnForm.CYFC_OPERATOR_2;  
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_2   :=  recYarnForm.CYFC_TYPE_2;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2   :=  recYarnForm.CYFC_VALUE_2;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2   :=  recYarnForm.CYFC_TOP_NO_2;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,pCYC_PRS_TYPE);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_2,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,pCYC_PRS_TYPE);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,pCYC_PRS_TYPE);                           
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM MASTER COSTING' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := recYarnForm.CYFC_VALUE_2;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2 := recYarnForm.CYFC_DT_VALUE_2;    
                end if;
                        
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_3   :=  recYarnForm.CYFC_OPERATOR_3;
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_3   :=  recYarnForm.CYFC_TYPE_3;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3   :=  recYarnForm.CYFC_VALUE_3;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_3  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3   :=  recYarnForm.CYFC_TOP_NO_3;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,pCYC_PRS_TYPE);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_3 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_3,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,pCYC_PRS_TYPE);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,pCYC_PRS_TYPE);
                end if;
                                            
                INSERT INTO CST_YARN_FORMULA_CALC VALUES vDt_YARN_FORMULA_CALC;   commit;        
            end loop;
                            
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'IF_CONDITION' then
        declare
            vDt_YARN_IFCOND_HDR  CST_YARN_IFCOND_HDR%rowtype;
            vDt_YARN_IFCOND_DTL  CST_YARN_IFCOND_DTL%rowtype;
        begin                    
            
            for recHdr in (
                select * from CST_YARN_IFCOND_HDR
                where CYIH_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                order by nvl(CYIH_SEQ_NO,0)                    
            ) loop
                vDt_YARN_IFCOND_HDR := null;
                vDt_YARN_IFCOND_HDR.CYIH_SYS_ID :=
                                          TO_CHAR (SYSDATE, 'YYYYMMDD')
                                                || TO_CHAR (pkg_seq_no.next_value (
                                                                        'CYIH_SYS_ID',
                                          'ADMIN',
                                          verrmsg),
                                'fm00000'
                                );
                vDt_YARN_IFCOND_HDR.CYIH_CYC_SYS_ID :=	vDt_YARN_CALCULATION.CYC_SYS_ID;
                vDt_YARN_IFCOND_HDR.CYIH_SEQ_NO := recHdr.CYIH_SEQ_NO;             
                vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_A  := recHdr.CYIH_TYPE_DATA_A;

                vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_A  := recHdr.CYIH_TOP_NO_A;
                vDt_YARN_IFCOND_HDR.CYIH_VALUE_A  := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_A,pCYC_PRS_TYPE);
                vDt_YARN_IFCOND_HDR.CYIH_TOP_DATA_A := recHdr.CYIH_TOP_DATA_A;                                                        
    
                vDt_YARN_IFCOND_HDR.CYIH_OPRTR_1 := recHdr.CYIH_OPRTR_1;
                vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_B  := recHdr.CYIH_TYPE_DATA_B;
 
                vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_B  := recHdr.CYIH_TOP_NO_B;
                vDt_YARN_IFCOND_HDR.CYIH_VALUE_B  := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_B,pCYC_PRS_TYPE);
                vDt_YARN_IFCOND_HDR.CYIH_TOP_DATA_B := recHdr.CYIH_TOP_DATA_B;      
                
                vDt_YARN_IFCOND_HDR.CYIH_OPRTR_IF_1  := recHdr.CYIH_OPRTR_IF_1;     
                vDt_YARN_IFCOND_HDR.CYIH_OPRTR_2   := recHdr.CYIH_OPRTR_2;                                                                                     
                begin
                    vDt_YARN_IFCOND_HDR.CYIH_CREATED_BY := pUserId;
                    vDt_YARN_IFCOND_HDR.CYIH_CREATED_TIMESTAMP := sysdate;
                    insert into cst_YARN_IFCOND_HDR values vDt_YARN_IFCOND_HDR;commit;
                exception
                    when others then
                        verrmsg := 'Error Insert cst_YARN_IFCOND_HDR '||sqlerrm;
                        raise vExp;
                end;
                        
                if verrmsg is null then
                    for recDtl in (
                        select * from mgtapps.CST_YARN_IFCOND_DTL
                        where CYID_CYIH_SYS_ID = recHdr.CYIH_SYS_ID
                        order by nvl(CYID_SEQ_NO,0)    
                    ) loop   
                        vDt_YARN_IFCOND_DTL := null;
                        vDt_YARN_IFCOND_DTL.CYID_SYS_ID :=
                                                  TO_CHAR (SYSDATE, 'YYYYMMDD')
                                                        || TO_CHAR (pkg_seq_no.next_value (
                                                                                'CYID_SYS_ID',
                                                  puserid,
                                                  verrmsg),
                                        'fm00000'
                                        );                                 
                        vDt_YARN_IFCOND_DTL.CYID_CYIH_SYS_ID := vDt_YARN_IFCOND_HDR.CYIH_SYS_ID;
                        vDt_YARN_IFCOND_DTL.CYID_SEQ_NO := recDtl.CYID_SEQ_NO;
                        vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1 := recDtl.CYID_TYPE_RLST_1;
                        if upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1) = 'FROM_DATA' then
                            vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_1 := recDtl.CYID_TOP_NO_RLST_1; 
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_1,pCYC_PRS_TYPE);                                
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_DATA_RLST_1 := get_CYC_DATA_VALUE(vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1,null,null);                                                        
                        elsif upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1) = 'INITIAL_VALUE' then
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1 := recDtl.CYID_VALUE_RLST_1;
                        end if;                            
                        vDt_YARN_IFCOND_DTL.CYID_OPRTR_1 := recDtl.CYID_OPRTR_1;
                                
                        vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2 := recDtl.CYID_TYPE_RLST_2;
                        vDt_YARN_IFCOND_DTL.CYID_OPRTR_2 := recDtl.CYID_OPRTR_2;
                        if upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2) = 'FROM_DATA' then
                            vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_2 := recDtl.CYID_TOP_NO_RLST_2; 
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_2,pCYC_PRS_TYPE);                                
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_DATA_RLST_2 := get_CYC_DATA_VALUE(vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2,null,null);                                                        
                        elsif upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2) = 'INITIAL_VALUE' then
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2 := recDtl.CYID_VALUE_RLST_2;
                        end if;
                        begin
                            insert into CST_YARN_IFCOND_DTL values vDt_YARN_IFCOND_DTL;commit;
                        exception
                        when others then
                            verrmsg := 'Error Insert CST_YARN_IFCOND_DTL '||sqlerrm;
                            
                            exit;
                        end;                                                                                                                                                       
                    end loop;
                end if;          
                if verrmsg is not null then
                   exit;
                end if;                                                                                                                                                                
            end loop;
            if verrmsg is not  null then
               raise vExp;
            end if;                            
        end;
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'RAW_MATERIAL' then                
        declare
            vCST_YARN_RM_HDR CST_YARN_RM_HDR%rowtype:= null;            
        begin 
            for recRMH in (                    
            select * from CST_YARN_RM_HDR                    
            where CYRH_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
            ) loop
                vCST_YARN_RM_HDR := null;
                vCST_YARN_RM_HDR.CYRH_SYS_ID := 
                    TO_CHAR (SYSDATE, 'YYYYMM')|| TO_CHAR (pkg_seq_no.next_value ('CYRH_SYS_ID',
                                        'ADMIN',verrmsg),'fm00000'
                );
                vCST_YARN_RM_HDR.CYRH_TYPE      := recRMH.CYRH_TYPE;
                --vCST_YARN_RM_HDR.CYRH_RM_COST   := recRMH.CYRH_RM_COST;
                vCST_YARN_RM_HDR.CYRH_CYC_SYS_ID:= vDt_YARN_CALCULATION.CYC_SYS_ID;
                        
              insert into CST_YARN_RM_HDR values vCST_YARN_RM_HDR;commit;
              
              --   CYRH_TYPE 
              --  'Store Rate' 
              --  'Captive Cost'
              --  'Yarn Rate'
              --  'Multi Yarn'                 
              if vCST_YARN_RM_HDR.CYRH_TYPE = 'Yarn Rate' then
              declare
                -- yarn
                vCST_YARN_RM_MULTI CST_YARN_RM_MULTI%rowtype;
                vRawMaterial varchar2(30);
                vLeft_No_Pty number;
                vMarketing_Pty varchar2(300);
              begin
                vCST_YARN_RM_MULTI := null;
                for recRmMulti in (                    
                    select * 
                    from CST_YARN_RM_MULTI
                    where CYRM_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                )loop
                begin
                    vRawMaterial := null;
                    vCST_YARN_RM_MULTI.CYRM_SYS_ID := TO_CHAR (SYSDATE, 'YYYYMM')
                                                     ||TO_CHAR (pkg_seq_no.next_value ('CYRM_SYS_ID','ADMIN',verrmsg),'fm00000');
                    vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_MULTI.CYRM_SEC_NO  := recRmMulti.CYRM_SEC_NO; 
                    vCST_YARN_RM_MULTI.CYRM_TYPE_DATA  := recRmMulti.CYRM_TYPE_DATA;
                    --vCST_YARN_RM_MULTI.CYRM_CYC_SYS_ID
                                        
                    if vCST_YARN_RM_HDR.CYRH_TYPE = 'Yarn Rate' then
                        -- 'Yarn-Cap'
                        if nvl(vCST_YARN_RM_MULTI.CYRM_TYPE_DATA,'NULL') = 'Yarn-Cap' then
                        begin
                            -- destination vDt_YARN_LEFT
                            -- CYRM_MARKETING_CODE, CYRM_YARN_TYPE, CYRM_YARN_TOP_NO, CYRM_YARN_LEFT_NO
                            vcst_yarn_rm_multi.CYRM_YARN_TYPE := recRmMulti.CYRM_YARN_TYPE;-- CYRM_YARN_TYPE
                            vcst_yarn_rm_multi.CYRM_YARN_TOP_NO := recRmMulti.CYRM_YARN_TOP_NO;
                            vcst_yarn_rm_multi.CYRM_YARN_LEFT_NO :=  recRmMulti.CYRM_YARN_LEFT_NO;                            
                            if vDt_YARN_LEFT.CYL_TYPE = 'PTY' then
                                -- Get Marketing Code
                                if recRmMulti.CYRM_YARN_TYPE = 'POY' then
                                    begin
                                        -- get raw material in top no 20
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code --CYRM_MARKETING_CODE
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                        and CYC_PRS_TYPE = pCYC_PRS_TYPE
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE;
                                    exception
                                        when no_data_found then
                                            null;
                                    end;
                                elsif recRmMulti.CYRM_YARN_TYPE = 'PTY' then
                                    SELECT cyl_marketing_cost_link
                                      INTO vcst_yarn_rm_multi.cyrm_marketing_code
                                      FROM cst_yarn_left d
                                      where d.cyl_left_no = vDt_YARN_LEFT.cyl_left_no
                                      and CYL_PRS_TYPE = pCYC_PRS_TYPE
                                       ;
                                       
                                       vcst_yarn_rm_multi.CYRM_YARN_LEFT_NO :=  vDt_YARN_LEFT.CYL_LEFT_NO;  
                                end if;
                                -- end Get Marketing Code
                            elsif vDt_YARN_LEFT.CYL_TYPE = 'TTY' then
                                if recRmMulti.CYRM_YARN_TYPE = 'PTY' then
                                    begin
                                        -- get raw material in top no 20
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code --CYRM_MARKETING_CODE
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                        and CYC_PRS_TYPE = pCYC_PRS_TYPE
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE;
                                    exception
                                        when no_data_found then
                                            null;
                                    end;                                
                                elsif recRmMulti.CYRM_YARN_TYPE = 'TTY' then
                                    SELECT cyl_marketing_cost_link
                                      INTO vcst_yarn_rm_multi.cyrm_marketing_code
                                      FROM cst_yarn_left d
                                      where d.cyl_left_no = vDt_YARN_LEFT.cyl_left_no
                                      and CYL_PRS_TYPE = pCYC_PRS_TYPE
                                       ;
                                elsif recRmMulti.CYRM_YARN_TYPE = 'POY' then
                                    -- get left no PTY
                                    begin                                    
                                        begin
                                            -- get raw material in top no 20
                                            select CYC_DATA_VALUE into vMarketing_Pty 
                                            from cst_yarn_calculation c
                                                 ,cst_yarn_left l
                                            where c.CYC_TOP_NO = 20
                                            and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                            and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                            and CYC_PRS_TYPE = pCYC_PRS_TYPE
                                            and CYC_PRS_TYPE = CYL_PRS_TYPE;
                                        exception
                                            when no_data_found then
                                                vMarketing_Pty := null;
                                        end;
                                        
                                        begin
                                            -- get raw material in top no 20
                                            select cyl_left_no into vLeft_No_Pty --cyl_left_no PTY
                                            from cst_yarn_left l
                                            where CYL_MARKETING_COST_LINK = vMarketing_Pty 
                                            and CYL_PRS_TYPE = pCYC_PRS_TYPE;
                                        exception
                                            when no_data_found then
                                                vLeft_No_Pty := null;
                                        end;
                                        
                                        -- get marketing POY
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code 
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vLeft_No_Pty
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE
                                        and CYC_PRS_TYPE = pCYC_PRS_TYPE;
                                    exception
                                        when no_data_found then
                                            null;
                                    end;      
                                end if;                                                                
                            end if;
                            --vCST_YARN_RM_MULTI.CYRM_YARN_TOP_NO := vDt_YARN_CALCULATION.CYC_TOP_NO;
                        exception
                            when others then
                                verrmsg := 'Error : '||recYarnCal.CYC_FORMULA_TYPE||' '||vCST_YARN_RM_MULTI.CYRM_TYPE_DATA||' '||sqlerrm;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);                                
                        end;
                        elsif nvl(vCST_YARN_RM_MULTI.CYRM_TYPE_DATA,'NULL') = 'Stores' then
                            vCST_YARN_RM_MULTI.CYRM_MARKETING_CODE := recRmMulti.CYRM_MARKETING_CODE;
                            vCST_YARN_RM_MULTI.CYRM_RM_RATE := recRmMulti.CYRM_RM_RATE;
                        end if;
                        --end Yarn-Cap'
                        
                        begin
                            insert into CST_YARN_RM_MULTI values vCST_YARN_RM_MULTI;
                        exception
                            when others then
                                verrmsg := 'Error : '||sqlerrm||' '||vCST_YARN_RM_MULTI.CYRM_SYS_ID;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);
                        end;
                                                
                        -- upd Header Rate
                        vCST_YARN_RM_HDR.CYRH_RM_COST := null;

                        select sum(CYRM_RM_RATE) into vCST_YARN_RM_HDR.CYRH_RM_COST
                        from CST_YARN_RM_MULTI
                        where CYRM_CYRH_SYS_ID = vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID;
                        
                        update CST_YARN_RM_HDR 
                            set CYRH_RM_COST = vCST_YARN_RM_HDR.CYRH_RM_COST
                        where CYRH_SYS_ID = vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID;commit;
                        
                    end if;           
                end; 
                end loop;
              end; 
              --  'Store Rate' --> CST_YARN_RM_DTL
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Store Rate' then
              declare
                vCST_YARN_RM_DTL CST_YARN_RM_DTL%rowtype:= null;
              begin                                   
              for recRMD in (                                          
                
                select * from CST_YARN_RM_DTL                                                            
                where CYRD_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                
              ) loop
                    vCST_YARN_RM_DTL := null;
                    vCST_YARN_RM_DTL.CYRD_SYS_ID := 
                        TO_CHAR (SYSDATE, 'YYYYMM')
                         || TO_CHAR (pkg_seq_no.next_value ('CYRD_SYS_ID',
                                                            'ADMIN',
                                                            verrmsg
                                                           ),
                                     'fm00000'
                                    );
                    vCST_YARN_RM_DTL.CYRD_CYRH_SYS_ID   := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_DTL.CYRD_SEC_NO        := recRMD.CYRD_SEC_NO;
                    vCST_YARN_RM_DTL.CYRD_TYPE_DATA     := recRMD.CYRD_TYPE_DATA;
                    vCST_YARN_RM_DTL.CYRD_RM_CODE       := recRMD.CYRD_RM_CODE;
                    insert into CST_YARN_RM_DTL values vCST_YARN_RM_DTL;           commit;                                 
              end loop;
              end;
              
              --  'Captive Cost' --> CST_YARN_RM_CAPTIVE
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Captive Cost' then              
              declare
                vCST_YARN_RM_CAPTIVE CST_YARN_RM_CAPTIVE%rowtype:= null;
              begin
              for recRMC in (                                          
                select * from CST_YARN_RM_CAPTIVE                                            
                where CYRC_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
              ) loop
                    vCST_YARN_RM_CAPTIVE := null;
                    
                    vCST_YARN_RM_CAPTIVE.CYRC_CYRH_SYS_ID := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_CAPTIVE.CYRC_YARN_TYPE := recRMC.CYRC_YARN_TYPE;
                    vCST_YARN_RM_CAPTIVE.CYRC_CYT_SYS_ID := recRMC.CYRC_CYT_SYS_ID;
                    vCST_YARN_RM_CAPTIVE.CYRC_TOP_NO := recRMC.CYRC_TOP_NO;
                    
                    vCST_YARN_RM_CAPTIVE.CYRC_CYL_SYS_ID := recRMC.CYRC_CYL_SYS_ID; 
                    vCST_YARN_RM_CAPTIVE.CYRC_LEFT_NO := recRMC.CYRC_LEFT_NO;
                    
                    insert into CST_YARN_RM_CAPTIVE values vCST_YARN_RM_CAPTIVE;        commit;                                    
              end loop;
              end;   
              --'Multi Yarn'     
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Multi Yarn' then
                begin
                    for recRmMulti in (
                    select * 
                    from mgtapps.CST_YARN_RM_MULTI
                    where CYRM_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                    ) loop                    
                        insert into mgtapps.CST_YARN_RM_MULTI
                        (CYRM_SYS_ID, CYRM_CYRH_SYS_ID, CYRM_SEC_NO --1
                        , CYRM_TYPE_DATA, CYRM_CYC_SYS_ID,CYRM_CYL_SYS_ID --2
                        , CYRM_CYT_SYS_ID, CYRM_MARKETING_CODE, CYRM_ORIG_DEN --3
                        , CYRM_DRAW_RATIO, CYRM_REV_DEN, CYRM_FILAMENTS --4
                        , CYRM_WASTE, CYRM_PRSN_SHARE ,CYRM_CONSUME --5
                        , CYRM_RM_RATE, CYRM_RM_COST, CYRM_YARN_TYPE --6
                        , CYRM_YARN_TOP_NO, CYRM_YARN_LEFT_NO, CYRM_TYPE_WASTE --7
                         )values
                         (
                        '123', vCST_YARN_RM_HDR.CYRH_SYS_ID, recRmMulti.CYRM_SEC_NO --1
                        , recRmMulti.CYRM_TYPE_DATA, recRmMulti.CYRM_CYC_SYS_ID,recRmMulti.CYRM_CYL_SYS_ID --2
                        , recRmMulti.CYRM_CYT_SYS_ID, recRmMulti.CYRM_MARKETING_CODE, recRmMulti.CYRM_ORIG_DEN --3
                        , recRmMulti.CYRM_DRAW_RATIO, recRmMulti.CYRM_REV_DEN, recRmMulti.CYRM_FILAMENTS --4
                        , recRmMulti.CYRM_WASTE, recRmMulti.CYRM_PRSN_SHARE ,recRmMulti.CYRM_CONSUME --5
                        , recRmMulti.CYRM_RM_RATE, recRmMulti.CYRM_RM_COST, recRmMulti.CYRM_YARN_TYPE --6
                        , recRmMulti.CYRM_YARN_TOP_NO, recRmMulti.CYRM_YARN_LEFT_NO, recRmMulti.CYRM_TYPE_WASTE --7
                         );commit;
                    end loop;
                END;
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Uneven Packing' then
                begin
                    for recUnvnPkg in (
                        select * 
                        from mgtapps.CST_YARN_RM_UNEVEN_PKG
                        where CYRUP_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                    ) loop
                        insert into mgtapps.CST_YARN_RM_UNEVEN_PKG
                        (
                          CYRUP_SYS_ID, CYRUP_CREATED_BY, CYRUP_CREATED_TIMESTAMP
                          ,CYRUP_CYRH_SYS_ID, CYRUP_SEC_NO, CYRUP_TYPE_DATA, CYRUP_CYL_SYS_ID
                          ,CYRUP_LEFT_NO, CYRUP_CYT_SYS_ID, CYRUP_TOP_NO, CYRUP_VALUE
                          ,CYRUP_PERSEN, CYRUP_RESULT
                        )
                        values(
                          '123', pUserId, sysdate
                          ,vCST_YARN_RM_HDR.CYRH_SYS_ID, recUnvnPkg.CYRUP_SEC_NO, recUnvnPkg.CYRUP_TYPE_DATA, recUnvnPkg.CYRUP_CYL_SYS_ID
                          ,recUnvnPkg.CYRUP_LEFT_NO, recUnvnPkg.CYRUP_CYT_SYS_ID, recUnvnPkg.CYRUP_TOP_NO, recUnvnPkg.CYRUP_VALUE
                          ,recUnvnPkg.CYRUP_PERSEN, recUnvnPkg.CYRUP_RESULT
                        ); 
                    end loop;
                end;  
              end if;
            end loop;
        end;            
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_MASTER_YARN' then
            declare
                vCST_YARN_PRODUCT_PARAM CST_YARN_PRODUCT_PARAM%rowtype:=null;
            begin
                for recDt in (
                
                    select * from CST_YARN_PRODUCT_PARAM
                    
                    where CYPP_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                    
                ) loop
                    vCST_YARN_PRODUCT_PARAM.CYPP_CYC_SYS_ID     := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_ID      := recDt.CYPP_COLUMN_ID;     
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_NAME    := recDt.CYPP_COLUMN_NAME;
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_DESC    := recDt.CYPP_COLUMN_DESC;
                    vCST_YARN_PRODUCT_PARAM.CYPP_STS_CK         := recDt.CYPP_STS_CK;
                            
                    begin
                        insert into CST_YARN_PRODUCT_PARAM values vCST_YARN_PRODUCT_PARAM;commit;
                    exception
                        when others then
                            verrmsg := sqlerrm||' insert into CST_YARN_PRODUCT_PARAM : '
                                    ||vCST_YARN_PRODUCT_PARAM.CYPP_CYC_SYS_ID||' -> '||vDt_YARN_CALCULATION.CYC_SYS_ID
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_ID||' -> '||recDt.CYPP_COLUMN_ID  
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_NAME||' -> '||recDt.CYPP_COLUMN_NAME
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_DESC||' -> '||recDt.CYPP_COLUMN_DESC
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_STS_CK||' -> '||recDt.CYPP_STS_CK
                                    ||':';
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);
                    end;                       
                end loop;  
            end;
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_MASTER_MACHINE' then
            declare
                vCST_YARN_MACHINE_PARAM CST_YARN_MACHINE_PARAM%rowtype:=null;
            begin
                delete from  CST_YARN_MACHINE_PARAM where CYMP_CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;                   
                for recDt in (
                    select * from CST_YARN_MACHINE_PARAM
                    where CYMP_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                ) loop
                    
                    vCST_YARN_MACHINE_PARAM.CYMP_CYC_SYS_ID := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_ID  := recDt.CYMP_COLUMN_ID;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_NAME:= recDt.CYMP_COLUMN_NAME;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_DESC:= recDt.CYMP_COLUMN_DESC;
                    vCST_YARN_MACHINE_PARAM.CYMP_STS_CK     := recDt.CYMP_STS_CK;
                    insert into CST_YARN_MACHINE_PARAM values vCST_YARN_MACHINE_PARAM;    commit;                   
                end loop;
            end;                
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_BOX_BOBIN_COST' then
            declare
                vCST_YARN_BOX_BOBIN_COST CST_YARN_BOX_BOBIN_COST%rowtype:=null;
            begin
                for recDt in (
                    select * from CST_YARN_BOX_BOBIN_COST
                    where CYBBC_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                ) loop
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_CYC_SYS_ID   := vDt_YARN_CALCULATION.CYC_SYS_ID;
                            
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_ID    := recDt.CYBBC_COLUMN_ID;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_NAME  := recDt.CYBBC_COLUMN_NAME;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_DESC  := recDt.CYBBC_COLUMN_DESC;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_STS_CK       := recDt.CYBBC_STS_CK;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_CMBBC_SYS_ID := recDt.CYBBC_CMBBC_SYS_ID;
                            
                    insert into CST_YARN_BOX_BOBIN_COST values vCST_YARN_BOX_BOBIN_COST;       commit;                 
                end loop;      
            end;                                    
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_DATA' then
            declare
                vCST_YARN_M_B_MST CST_YARN_M_B_MST%rowtype:=null;
            begin
                delete from  CST_YARN_M_B_MST where CYMBM_CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;
                for recDt in (
                    select * from CST_YARN_M_B_MST
                    where CYMBM_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                ) loop
                    vCST_YARN_M_B_MST.CYMBM_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_ID   := recDt.CYMBM_COLUMN_ID;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_NAME := recDt.CYMBM_COLUMN_NAME;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_DESC := recDt.CYMBM_COLUMN_DESC;
                    vCST_YARN_M_B_MST.CYMBM_STS_CK      := recDt.CYMBM_STS_CK;
                    --vCST_YARN_M_B_MST.CYMBM_CMBH_SYS_ID := recDt.CYMBM_CMBH_SYS_ID;
                    if recDt.CYMBM_STS_CK = 1 then
                    begin  
                        select CMBH_SYS_ID
                        into vCST_YARN_M_B_MST.CYMBM_CMBH_SYS_ID
                        from  cst_YARN_left l
                              ,CST_MST_BATCH_HEAD bh
                              ,CST_MST_BATCH_SPIN bs
                        where cyl_left_no = vDt_YARN_CALCULATION.cyc_left_no
                        and CYL_PRS_TYPE = pCYC_PRS_TYPE
                        and CYL_SHADE_NAME = CMBS_MGT_NAME
                        and CMBS_CMBH_SYS_ID = CMBH_SYS_ID;
                    exception
                        when no_data_found then
                            vCST_YARN_M_B_MST.CYMBM_CMBH_SYS_ID  := null;                                        
                    end;                     
                    else 
                        vCST_YARN_M_B_MST.CYMBM_CMBH_SYS_ID  := null;
                    end if;
                            
                    insert into CST_YARN_M_B_MST values vCST_YARN_M_B_MST;commit;
                end loop;
            end;
                    
            declare
                vCST_YARN_M_B_DT CST_YARN_M_B_DT%rowtype:=null;
            begin
                delete from CST_YARN_M_B_DT where CYMBD_CYC_SYS_ID = vDt_YARN_CALCULATION.CYC_SYS_ID;
                for recDt in (
                    select * from CST_YARN_M_B_DT
                    where CYMBD_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
                    and CYMBD_STS_CK = 1
                ) loop
                    vCST_YARN_M_B_DT.CYMBD_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                            
                    vCST_YARN_M_B_DT.CYMBD_COLUMN_ID    := recDt.CYMBD_COLUMN_ID;
                    vCST_YARN_M_B_DT.CYMBD_COLUMN_NAME  := recDt.CYMBD_COLUMN_NAME;
                    vCST_YARN_M_B_DT.CYMBD_COLUMN_DESC  := recDt.CYMBD_COLUMN_DESC;
                    vCST_YARN_M_B_DT.CYMBD_STS_CK       := recDt.CYMBD_STS_CK;
                    --vCST_YARN_M_B_DT.CYMBD_CMBH_SYS_ID  := recDt.CYMBD_CMBH_SYS_ID;
                    begin
                        select CMBH_SYS_ID into vCST_YARN_M_B_DT.CYMBD_CMBH_SYS_ID
                        from  cst_YARN_left l
                              ,CST_MST_BATCH_HEAD bh
                              ,CST_MST_BATCH_SPIN bs
                        where cyl_left_no = vDt_YARN_CALCULATION.cyc_left_no
                        and CYL_PRS_TYPE = pCYC_PRS_TYPE
                        and CYL_SHADE_NAME = CMBS_MGT_NAME
                        and CMBS_CMBH_SYS_ID = CMBH_SYS_ID;
                    exception
                        when no_data_found then
                            vCST_YARN_M_B_DT.CYMBD_CMBH_SYS_ID := null;
                    end;        
                    insert into CST_YARN_M_B_DT values vCST_YARN_M_B_DT;                        
                end loop;
            end;                
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_SPINNING' then
            declare
                vCST_YARN_M_B_S_MST CST_YARN_M_B_S_MST%rowtype:=null;
            begin
                vCST_YARN_M_B_S_MST := null;
                for recDt in (
                    select * from CST_YARN_M_B_S_MST
                    where CYMBMS_CYC_SYS_ID = recYarnCal.CYC_SYS_ID -- from source
                    and CYMBMS_STS_CK = 1
                ) loop
                    vCST_YARN_M_B_S_MST.CYMBMS_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_ID   := recDt.CYMBMS_COLUMN_ID;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_NAME := recDt.CYMBMS_COLUMN_NAME;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_DESC := recDt.CYMBMS_COLUMN_DESC;
                    vCST_YARN_M_B_S_MST.CYMBMS_STS_CK      := recDt.CYMBMS_STS_CK;
                    
                    -- get data sys id mb spinning
                    begin  
                        select CMBS_SYS_ID,CMBH_SYS_ID,CYL_SHADE_NAME
                        into vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID
                            ,vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID
                            ,vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE 
                        from  cst_YARN_left l
                              ,CST_MST_BATCH_HEAD bh
                              ,CST_MST_BATCH_SPIN bs
                        where cyl_left_no = vDt_YARN_CALCULATION.cyc_left_no
                        and CYL_PRS_TYPE = pCYC_PRS_TYPE
                        and CYL_SHADE_NAME = CMBS_MGT_NAME
                        and CMBS_CMBH_SYS_ID = CMBH_SYS_ID;
                    exception
                        when no_data_found then
                            vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID:= null;
                            vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID := null;
                            vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE := null;                                        
                    end;
                    
                    /*vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID  := null;           
                    vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID
                    vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE 
                    
                    begin                    
                        select CYMBM_CMBH_SYS_ID into vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID 
                        from CST_YARN_M_B_MST
                        where CYMBM_CYC_SYS_ID = (
                            select cyc_sys_id 
                            from cst_yarn_calculation
                            where CYC_CYL_SYS_ID = vDt_YARN_CALCULATION.CYC_CYL_SYS_ID
                            and CYC_TOP_NO = 64
                            and CYC_PRS_TYPE = pCYC_PRS_TYPE
                            )
                        and CYMBM_STS_CK = 1;
                    exception
                        when no_data_found then           
                        begin              
                            select distinct  CMBS_CMBH_SYS_ID into vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID 
                            from  cst_mst_batch_spin
                            where CMBS_ORION_ITEM_CODE = (
                                select CYC_DATA_VALUE
                                from cst_yarn_calculation
                                where CYC_CYL_SYS_ID = vDt_YARN_CALCULATION.CYC_CYL_SYS_ID
                                and CYC_TOP_NO = 63
                                and CYC_PRS_TYPE = pCYC_PRS_TYPE
                            );
                        exception
                            when no_data_found then
                                vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID := null;
                            when too_many_rows then
                                vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID := null;
                             
                        end;
                    end;
                    
                    if vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID is not null then                    
                    declare
                        vShadeCode varchar2(100):=null;
                        vShadeCode_v varchar2(100):=null;
                    begin
                        begin                    
                            select CYC_DATA_VALUE into vShadeCode
                            from cst_yarn_calculation
                            where CYC_CYL_SYS_ID = vDt_YARN_CALCULATION.CYC_CYL_SYS_ID
                            and CYC_TOP_NO = 5
                            and CYC_PRS_TYPE = pCYC_PRS_TYPE;
                            
                        exception
                            when no_data_found then    
                                vShadeCode := null;                                                 
                        end;
                        vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE := vShadeCode;                        
                    end;     
                    end if;
                                       
                    begin
                        select CMBS_SYS_ID into vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID 
                        from cst_mst_batch_SPIN
                        where CMBS_CMBH_SYS_ID = vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID
                        and CMBS_CODE = vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE;
                    exception
                        when no_data_found then
                            vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID  := null;   
                    end;*/                    
                    
                    insert into CST_YARN_M_B_S_MST values vCST_YARN_M_B_S_MST;commit;
                end loop;
            end;                         
        elsif upper(recYarnCal.CYC_FORMULA_TYPE) = 'FROM_PRODUCT_GRADE' then                
        declare
            vCST_YARN_PRODUCT_GRADE CST_YARN_PRODUCT_GRADE%rowtype:=null;
        begin
            for recDt in (
                select * from CST_YARN_PRODUCT_GRADE
                where CYPG_CYC_SYS_ID = recYarnCal.CYC_SYS_ID
            ) loop
                vCST_YARN_PRODUCT_GRADE.CYPG_CYC_SYS_ID     := vDt_YARN_CALCULATION.CYC_SYS_ID;
                        
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_ID      := recDt.CYPG_COLUMN_ID;
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_NAME    := recDt.CYPG_COLUMN_NAME;
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_DESC    := recDt.CYPG_COLUMN_DESC;
                vCST_YARN_PRODUCT_GRADE.CYPG_STS_CK         := recDt.CYPG_STS_CK;
                vCST_YARN_PRODUCT_GRADE.CYPG_CMPG_SYS_ID    := recDt.CYPG_CMPG_SYS_ID;
                        
                insert into CST_YARN_PRODUCT_GRADE values vCST_YARN_PRODUCT_GRADE;commit;
            end loop;
        end;   
        end if;      
    exception
        when vExp then
            verrmsg := verrmsg||' '||sqlerrm;
            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
            values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);      
    end copy_top;
    
    procedure copy_top_valuation(
                       pUserId varchar2
                       ,pDt_YARN_LEFT cst_YARN_LEFT%rowtype -- data Left Valuation
                       ,pRecYarnCalFrom cst_YARN_CALCULATION%rowtype  -- data Calculation Marketing
                       ,pCYT_SYS_ID_Dest varchar2
                       ,pErrMsg out varchar
                       ) is
        vCYC_SYS_ID varchar2(30);
        verrmsg varchar2(1000):=null;
        vDt_YARN_CALCULATION CST_YARN_CALCULATION%rowtype;
        recYarnCalFrom CST_YARN_CALCULATION%rowtype;
        vDt_YARN_LEFT cst_YARN_LEFT%rowtype;
        
        vDt_YARN_FORMULA_CALC CST_YARN_FORMULA_CALC%rowtype;
        
        vExp exception; 
    begin       
        vDt_YARN_LEFT := pDt_YARN_LEFT;
        recYarnCalFrom := pRecYarnCalFrom;
        
        vDt_YARN_CALCULATION := null;
        --vDt_YARN_CALCULATION.CYC_SYS_ID := pCYC_SYS_ID_Dest;            
        vDt_YARN_CALCULATION.CYC_CYL_SYS_ID := vDt_YARN_LEFT.CYL_SYS_ID;     
        vDt_YARN_CALCULATION.CYC_LEFT_NO := vDt_YARN_LEFT.CYL_LEFT_NO;
                
        --vDt_YARN_CALCULATION.CYC_CYT_SYS_ID := recYarnCal.CYC_CYT_SYS_ID;
        vDt_YARN_CALCULATION.CYC_CYT_SYS_ID := pCYT_SYS_ID_Dest;
        
        vDt_YARN_CALCULATION.CYC_TOP_NO  := recYarnCalFrom.CYC_TOP_NO;
        vDt_YARN_CALCULATION.CYC_FORMULA_TYPE := recYarnCalFrom.CYC_FORMULA_TYPE;
        vDt_YARN_CALCULATION.CYC_PROCESS_SEQ := recYarnCalFrom.CYC_PROCESS_SEQ;
        vDt_YARN_CALCULATION.CYC_TOP_NO_COPY := recYarnCalFrom.CYC_TOP_NO_COPY;
        vDt_YARN_CALCULATION.CYC_UPD_YARN_LEFT := recYarnCalFrom.CYC_UPD_YARN_LEFT;
        vDt_YARN_CALCULATION.CYC_DATA_VALUE := recYarnCalFrom.CYC_DATA_VALUE;
        vDt_YARN_CALCULATION.CYC_PRS_TYPE := vIdVal;
        vDt_YARN_CALCULATION.CYC_PRS_NAME := fGetNmYarnPrs(vIdVal);
        if vDt_YARN_CALCULATION.CYC_UPD_YARN_LEFT is not null then
            vDt_YARN_CALCULATION.CYC_DATA_VALUE := null;
        end if;
        
        --, CYC_FORMULA_SCRIPT, CYC_DATA_VALUE, CYC_PROCESS_SEQ, CYC_TOP_NO_COPY, CYC_DATA_VALUE_COPY
                                                            
        if upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'QUERY' then
        --1.Query
            vDt_YARN_CALCULATION.CYC_FORMULA_SCRIPT := recYarnCalFrom.CYC_FORMULA_SCRIPT; 
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'INITIAL_VALUE' then
        --2.Initial_Value
            vDt_YARN_CALCULATION.CYC_FORMULA_SCRIPT := recYarnCalFrom.CYC_FORMULA_SCRIPT; 
        end if;
        verrmsg := '1 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        
        vDt_YARN_CALCULATION.CYC_SYS_ID :=
                    TO_CHAR (SYSDATE, 'YYYYMMDD')||
                    TO_CHAR (pkg_seq_no.next_value ('CYC_SYS_ID',
                                                    'ADMIN',
                                                    verrmsg
                                                   ),'FM000000000000000000000');
                                                           
        vDt_YARN_CALCULATION.CYC_CREATED_BY := pUserID;
        vDt_YARN_CALCULATION.CYC_CREATED_TIMESTAMP := sysdate;                
        insert into CST_YARN_CALCULATION values vDt_YARN_CALCULATION;commit;
                                                   
        if upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_DATA' then    
            --3.From_Data
        declare
            vCST_YARN_SAME_ROWS CST_YARN_SAME_ROWS%rowtype:=null;
        begin
            for recDt in (
                select * from CST_YARN_SAME_ROWS
                where CYSR_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                order by nvl(CYSR_SEC_NO,0)                    
            ) loop
                vCST_YARN_SAME_ROWS := null;
                vCST_YARN_SAME_ROWS.CYSR_SYS_ID     :='123';
                vCST_YARN_SAME_ROWS.CYSR_CYC_SYS_ID :=vDt_YARN_CALCULATION.CYC_SYS_ID;
                vCST_YARN_SAME_ROWS.CYSR_TOP_NO     :=recDt.CYSR_TOP_NO;
                vCST_YARN_SAME_ROWS.CYSR_SEC_NO     :=recDt.CYSR_SEC_NO;
                vCST_YARN_SAME_ROWS.CYSR_VALUE      := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,recDt.CYSR_TOP_NO,vIdVal);
                --vCST_YARN_SAME_ROWS.CYSR_VALUE_DT
                
                insert into CST_YARN_SAME_ROWS values vCST_YARN_SAME_ROWS;    commit;                    
            end loop;
            verrmsg := '3 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        end;    
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FORMULA_DATA' then
            --FORMULA_DATA
            for recYarnForm in (                        
                select * from CST_YARN_FORMULA_CALC
                where CYFC_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                order by nvl(CYFC_SEQ_NO,0)
            )loop
                vDt_YARN_FORMULA_CALC := null;
                vDt_YARN_FORMULA_CALC.CYFC_CYC_SYS_ID := vDt_YARN_CALCULATION.CYC_SYS_ID;
                vDt_YARN_FORMULA_CALC.CYFC_SYS_ID :=
                        TO_CHAR (SYSDATE, 'YYYYMM')
                     || TO_CHAR (pkg_seq_no.next_value ('CYFC_SYS_ID',
                                                        'ADMIN',
                                                        verrmsg
                                                       ),
                                 'fm00000'              
                                ); 
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_1   :=  recYarnForm.CYFC_OPERATOR_1;                     
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_1   :=  recYarnForm.CYFC_TYPE_1;
                vDt_YARN_FORMULA_CALC.CYFC_SEQ_NO   :=  recYarnForm.CYFC_SEQ_NO;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1   :=  recYarnForm.CYFC_VALUE_1;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1   :=  recYarnForm.CYFC_TOP_NO_1;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1,vIdVal);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_1,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1   :=  recYarnForm.CYFC_TOP_NO_1; 
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_1,vIdVal);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_1 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_1,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_1) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_1 := recYarnForm.CYFC_VALUE_1;                           
                end if;                    
                        
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_2   :=  recYarnForm.CYFC_OPERATOR_2;  
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_2   :=  recYarnForm.CYFC_TYPE_2;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2   :=  recYarnForm.CYFC_VALUE_2;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2   :=  recYarnForm.CYFC_TOP_NO_2;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,vIdVal);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_2,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,vIdVal);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_2,vIdVal);                           
                end if;
                        
                vDt_YARN_FORMULA_CALC.CYFC_OPERATOR_3   :=  recYarnForm.CYFC_OPERATOR_3;
                vDt_YARN_FORMULA_CALC.CYFC_TYPE_3   :=  recYarnForm.CYFC_TYPE_3;
                if upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'INITIAL_VALUE' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3   :=  recYarnForm.CYFC_VALUE_3;
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3  := NULL;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_3  := NULL;
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'FROM_DATA' then
                   vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3   :=  recYarnForm.CYFC_TOP_NO_3;
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,vIdVal);
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_3 := get_CYC_DATA_VALUE(vDt_YARN_FORMULA_CALC.CYFC_VALUE_3,null,null);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'SQRT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,vIdVal);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_3) = 'FROM_TX_WEIGHT' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_3 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_FORMULA_CALC.CYFC_TOP_NO_3,vIdVal);
                elsif upper(vDt_YARN_FORMULA_CALC.CYFC_TYPE_2) = 'FROM MASTER COSTING' then
                   vDt_YARN_FORMULA_CALC.CYFC_VALUE_2 := recYarnForm.CYFC_VALUE_2;
                   vDt_YARN_FORMULA_CALC.CYFC_DT_VALUE_2 := recYarnForm.CYFC_DT_VALUE_2; 
                end if;
                                            
                INSERT INTO CST_YARN_FORMULA_CALC VALUES vDt_YARN_FORMULA_CALC;     commit;      
            end loop;
            verrmsg := '4 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;                
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'IF_CONDITION' then
        declare
            vDt_YARN_IFCOND_HDR  CST_YARN_IFCOND_HDR%rowtype;
            vDt_YARN_IFCOND_DTL  CST_YARN_IFCOND_DTL%rowtype;
        begin                    
            for recHdr in (
                select * from CST_YARN_IFCOND_HDR
                where CYIH_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                order by nvl(CYIH_SEQ_NO,0)                    
            ) loop
                vDt_YARN_IFCOND_HDR := null;
                vDt_YARN_IFCOND_HDR.CYIH_SYS_ID :=
                                          TO_CHAR (SYSDATE, 'YYYYMMDD')
                                                || TO_CHAR (pkg_seq_no.next_value (
                                                                        'CYIH_SYS_ID',
                                          'ADMIN',
                                          verrmsg),
                                'fm00000'
                                );
                vDt_YARN_IFCOND_HDR.CYIH_CYC_SYS_ID :=	vDt_YARN_CALCULATION.CYC_SYS_ID;
                vDt_YARN_IFCOND_HDR.CYIH_SEQ_NO := recHdr.CYIH_SEQ_NO;             
                vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_A  := recHdr.CYIH_TYPE_DATA_A;
                --if upper(vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_A) = 'FROM_DATA' then 
                vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_A  := recHdr.CYIH_TOP_NO_A;
                vDt_YARN_IFCOND_HDR.CYIH_VALUE_A  := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_A,vIdVal);
                vDt_YARN_IFCOND_HDR.CYIH_TOP_DATA_A := recHdr.CYIH_TOP_DATA_A;--get_CYC_DATA_VALUE(vDt_YARN_IFCOND_HDR.CYIH_VALUE_A,null,null);                                                        
                --elsif upper(vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_A) = 'INITIAL_VALUE' then
                 --   vDt_YARN_IFCOND_HDR.CYIH_VALUE_A := recHdr.CYIH_VALUE_A;
                 --   vDt_YARN_IFCOND_HDR.CYIH_TOP_DATA_A := recHdr.CYIH_TOP_DATA_A;                
                --end if;    
                vDt_YARN_IFCOND_HDR.CYIH_OPRTR_1 := recHdr.CYIH_OPRTR_1;
                vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_B  := recHdr.CYIH_TYPE_DATA_B;
                --if upper(vDt_YARN_IFCOND_HDR.CYIH_TYPE_DATA_B) = 'FROM_DATA' then 
                vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_B  := recHdr.CYIH_TOP_NO_B;
                vDt_YARN_IFCOND_HDR.CYIH_VALUE_B  := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_HDR.CYIH_TOP_NO_B,vIdVal);
                vDt_YARN_IFCOND_HDR.CYIH_TOP_DATA_B := recHdr.CYIH_TOP_DATA_B;
                
        begin
                    vDt_YARN_IFCOND_HDR.CYIH_CREATED_BY := pUserId;
                    vDt_YARN_IFCOND_HDR.CYIH_CREATED_TIMESTAMP := sysdate;
                    insert into cst_YARN_IFCOND_HDR values vDt_YARN_IFCOND_HDR;commit;
                exception
                    when others then
                        verrmsg := 'Error Insert cst_YARN_IFCOND_HDR ';
                        raise vExp;
                end;
                        
                if verrmsg is null then
                    for recDtl in (
                        select * from CST_YARN_IFCOND_DTL
                        where CYID_CYIH_SYS_ID = recHdr.CYIH_SYS_ID
                        order by nvl(CYID_SEQ_NO,0)    
                    ) loop   
                        vDt_YARN_IFCOND_DTL := null;
                        vDt_YARN_IFCOND_DTL.CYID_SYS_ID :=
                                                  TO_CHAR (SYSDATE, 'YYYYMMDD')
                                                        || TO_CHAR (pkg_seq_no.next_value (
                                                                                'CYID_SYS_ID',
                                                  puserid,
                                                  verrmsg),
                                        'fm00000'
                                        );                                 
                        vDt_YARN_IFCOND_DTL.CYID_CYIH_SYS_ID := vDt_YARN_IFCOND_HDR.CYIH_SYS_ID;
                        vDt_YARN_IFCOND_DTL.CYID_SEQ_NO := recDtl.CYID_SEQ_NO;
                        vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1 := recDtl.CYID_TYPE_RLST_1;
                        if upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1) = 'FROM_DATA' then
                            vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_1 := recDtl.CYID_TOP_NO_RLST_1; 
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_1,vIdVal);                                
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_DATA_RLST_1 := get_CYC_DATA_VALUE(vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1,null,null);                                                        
                        elsif upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_1) = 'INITIAL_VALUE' then
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_1 := recDtl.CYID_VALUE_RLST_1;
                        end if;                            
                        vDt_YARN_IFCOND_DTL.CYID_OPRTR_1 := recDtl.CYID_OPRTR_1;
                                
                        vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2 := recDtl.CYID_TYPE_RLST_2;
                        vDt_YARN_IFCOND_DTL.CYID_OPRTR_2 := recDtl.CYID_OPRTR_2;
                        if upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2) = 'FROM_DATA' then
                            vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_2 := recDtl.CYID_TOP_NO_RLST_2; 
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2 := get_CYC_SYS_ID(vDt_YARN_LEFT.CYL_LEFT_NO,vDt_YARN_IFCOND_DTL.CYID_TOP_NO_RLST_2,vIdVal);                                
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_DATA_RLST_2 := get_CYC_DATA_VALUE(vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2,null,null);                                                        
                        elsif upper(vDt_YARN_IFCOND_DTL.CYID_TYPE_RLST_2) = 'INITIAL_VALUE' then
                            vDt_YARN_IFCOND_DTL.CYID_VALUE_RLST_2 := recDtl.CYID_VALUE_RLST_2;
                        end if;
                        begin
                            insert into CST_YARN_IFCOND_DTL values vDt_YARN_IFCOND_DTL;
                        exception
                        when others then
                            verrmsg := 'Error Insert CST_YARN_IFCOND_DTL ';
                            exit;
                        end;                                                                                                                                                       
                    end loop;
                end if;          
                if verrmsg is not null then
                   exit;
                end if;                                                                                                                                                                                                                      
            end loop;
            /*if verrmsg is not  null then
               raise vExp;
            end if;
            verrmsg := '5 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;*/             
        end;
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'RAW_MATERIAL' then
        declare
            vCST_YARN_RM_HDR CST_YARN_RM_HDR%rowtype:= null;            
        begin 
            for recRMH in (                    
            select * from CST_YARN_RM_HDR                    
            where CYRH_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
            ) loop
                vCST_YARN_RM_HDR := null;
                vCST_YARN_RM_HDR.CYRH_SYS_ID := 
                    TO_CHAR (SYSDATE, 'YYYYMM')|| TO_CHAR (pkg_seq_no.next_value ('CYRH_SYS_ID',
                                        'ADMIN',verrmsg),'fm00000'
                );
                vCST_YARN_RM_HDR.CYRH_TYPE      := recRMH.CYRH_TYPE;
                --vCST_YARN_RM_HDR.CYRH_RM_COST   := recRMH.CYRH_RM_COST;
                vCST_YARN_RM_HDR.CYRH_CYC_SYS_ID:= vDt_YARN_CALCULATION.CYC_SYS_ID;
                        
              insert into CST_YARN_RM_HDR values vCST_YARN_RM_HDR;commit;
              
              --   CYRH_TYPE 
              --  'Store Rate' 
              --  'Captive Cost'
              --  'Yarn Rate'
              --  'Multi Yarn'                 
              --if vCST_YARN_RM_HDR.CYRH_TYPE = 'Yarn Rate' then
              if vCST_YARN_RM_HDR.CYRH_TYPE in ('Yarn Rate','Multi Yarn') then
              declare
                -- yarn
                vCST_YARN_RM_MULTI CST_YARN_RM_MULTI%rowtype;
                vRawMaterial varchar2(30);
                vLeft_No_Pty number;
                vMarketing_Pty varchar2(300);
              begin
                vCST_YARN_RM_MULTI := null;
                for recRmMulti in (                    
                    select * 
                    from CST_YARN_RM_MULTI
                    where CYRM_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                )loop
                begin
                    vRawMaterial := null;
                    vCST_YARN_RM_MULTI.CYRM_SYS_ID := TO_CHAR (SYSDATE, 'YYYYMM')
                                                     ||TO_CHAR (pkg_seq_no.next_value ('CYRM_SYS_ID','ADMIN',verrmsg),'fm00000');
                    vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_MULTI.CYRM_SEC_NO  := recRmMulti.CYRM_SEC_NO; 
                    vCST_YARN_RM_MULTI.CYRM_TYPE_DATA  := recRmMulti.CYRM_TYPE_DATA;
                                        
                    if vCST_YARN_RM_HDR.CYRH_TYPE in ('Yarn Rate','Multi Yarn') then
                        -- 'Yarn-Cap'
                        if nvl(vCST_YARN_RM_MULTI.CYRM_TYPE_DATA,'NULL') = 'Yarn-Cap' then
                        begin
                            -- destination vDt_YARN_LEFT
                            -- CYRM_MARKETING_CODE, CYRM_YARN_TYPE, CYRM_YARN_TOP_NO, CYRM_YARN_LEFT_NO
                            vcst_yarn_rm_multi.CYRM_YARN_TYPE := recRmMulti.CYRM_YARN_TYPE;-- CYRM_YARN_TYPE
                            vcst_yarn_rm_multi.CYRM_YARN_TOP_NO := recRmMulti.CYRM_YARN_TOP_NO;
                            vcst_yarn_rm_multi.CYRM_YARN_LEFT_NO :=  recRmMulti.CYRM_YARN_LEFT_NO;                            
                            if vDt_YARN_LEFT.CYL_TYPE = 'PTY' then
                                -- Get Marketing Code
                                if recRmMulti.CYRM_YARN_TYPE = 'POY' then
                                    begin
                                        -- get raw material in top no 20
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code --CYRM_MARKETING_CODE
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE
                                        and CYC_PRS_TYPE = '20210800119';
                                    exception
                                        when no_data_found then
                                            null;
                                    end;
                                elsif recRmMulti.CYRM_YARN_TYPE = 'PTY' then
                                    SELECT cyl_marketing_cost_link
                                      INTO vcst_yarn_rm_multi.cyrm_marketing_code
                                      FROM cst_yarn_left d
                                      where d.cyl_left_no = vDt_YARN_LEFT.cyl_left_no
                                      and CYL_PRS_TYPE = '20210800119'
                                       ;
                                end if;
                                -- end Get Marketing Code
                            elsif vDt_YARN_LEFT.CYL_TYPE = 'TTY' then
                                if recRmMulti.CYRM_YARN_TYPE = 'PTY' then
                                    begin
                                        -- get raw material in top no 20
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code --CYRM_MARKETING_CODE
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE
                                        and CYC_PRS_TYPE = '20210800119';
                                    exception
                                        when no_data_found then
                                            null;
                                    end;                                
                                elsif recRmMulti.CYRM_YARN_TYPE = 'TTY' then
                                    SELECT cyl_marketing_cost_link
                                      INTO vcst_yarn_rm_multi.cyrm_marketing_code
                                      FROM cst_yarn_left d
                                      where d.cyl_left_no = vDt_YARN_LEFT.cyl_left_no
                                      and CYL_PRS_TYPE = '20210800119'
                                       ;
                                elsif recRmMulti.CYRM_YARN_TYPE = 'POY' then
                                    -- get left no PTY
                                    begin                                    
                                        begin
                                            -- get raw material in top no 20
                                            select CYC_DATA_VALUE into vMarketing_Pty 
                                            from cst_yarn_calculation c
                                                 ,cst_yarn_left l
                                            where c.CYC_TOP_NO = 20
                                            and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                            and l.CYL_LEFT_NO = vDt_YARN_LEFT.CYL_LEFT_NO
                                            and CYC_PRS_TYPE = CYL_PRS_TYPE
                                            and CYC_PRS_TYPE = '20210800119';
                                        exception
                                            when no_data_found then
                                                vMarketing_Pty := null;
                                        end;
                                        
                                        begin
                                            -- get raw material in top no 20
                                            select cyl_left_no into vLeft_No_Pty --cyl_left_no PTY
                                            from cst_yarn_left l
                                            where CYL_MARKETING_COST_LINK = vMarketing_Pty 
                                            and CYl_PRS_TYPE = '20210800119';
                                        exception
                                            when no_data_found then
                                                vLeft_No_Pty := null;
                                        end;
                                        
                                        -- get marketing POY
                                        select c.cyc_data_value into vcst_yarn_rm_multi.cyrm_marketing_code 
                                        from cst_yarn_calculation c
                                             ,cst_yarn_left l
                                        where c.CYC_TOP_NO = 20
                                        and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                        and l.CYL_LEFT_NO = vLeft_No_Pty
                                        and CYC_PRS_TYPE = CYL_PRS_TYPE
                                        and CYC_PRS_TYPE = '20210800119'
                                        ;
                                    exception
                                        when no_data_found then
                                            null;
                                    end;      
                                end if;                                                                
                            end if;
                            --vCST_YARN_RM_MULTI.CYRM_YARN_TOP_NO := vDt_YARN_CALCULATION.CYC_TOP_NO;
                        exception
                            when others then
                                verrmsg := 'Error : '||recYarnCalFrom.CYC_FORMULA_TYPE||' '||vCST_YARN_RM_MULTI.CYRM_TYPE_DATA||' '||sqlerrm;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(verrmsg,pUserId,sysdate,vIdVal);                                
                        end;
                        elsif nvl(vCST_YARN_RM_MULTI.CYRM_TYPE_DATA,'NULL') = 'Stores' then
                            vCST_YARN_RM_MULTI.CYRM_MARKETING_CODE := recRmMulti.CYRM_MARKETING_CODE;
                            vCST_YARN_RM_MULTI.CYRM_RM_RATE := recRmMulti.CYRM_RM_RATE;
                        end if;
                        --end Yarn-Cap'
                        
                        
                        if vCST_YARN_RM_HDR.CYRH_TYPE in ('Multi Yarn') then
                            vCST_YARN_RM_MULTI.CYRM_CYC_SYS_ID      := recRmMulti.CYRM_CYC_SYS_ID;
                            vCST_YARN_RM_MULTI.CYRM_CYL_SYS_ID      := recRmMulti.CYRM_CYL_SYS_ID;
                            vCST_YARN_RM_MULTI.CYRM_CYT_SYS_ID      := recRmMulti.CYRM_CYT_SYS_ID;
                            vCST_YARN_RM_MULTI.CYRM_MARKETING_CODE  := recRmMulti.CYRM_MARKETING_CODE;
                            vCST_YARN_RM_MULTI.CYRM_ORIG_DEN        := recRmMulti.CYRM_ORIG_DEN;
                            vCST_YARN_RM_MULTI.CYRM_DRAW_RATIO      := recRmMulti.CYRM_DRAW_RATIO;
                            vCST_YARN_RM_MULTI.CYRM_REV_DEN         := recRmMulti.CYRM_REV_DEN;
                            vCST_YARN_RM_MULTI.CYRM_FILAMENTS       := recRmMulti.CYRM_FILAMENTS;
                            vCST_YARN_RM_MULTI.CYRM_WASTE           := recRmMulti.CYRM_WASTE;
                            vCST_YARN_RM_MULTI.CYRM_PRSN_SHARE      := recRmMulti.CYRM_PRSN_SHARE ;
                            vCST_YARN_RM_MULTI.CYRM_CONSUME         := recRmMulti.CYRM_CONSUME;
                            vCST_YARN_RM_MULTI.CYRM_RM_RATE         := recRmMulti.CYRM_RM_RATE;
                            vCST_YARN_RM_MULTI.CYRM_RM_COST         := recRmMulti.CYRM_RM_COST;
                            vCST_YARN_RM_MULTI.CYRM_YARN_TYPE       := recRmMulti.CYRM_YARN_TYPE;
                            vCST_YARN_RM_MULTI.CYRM_YARN_TOP_NO     := recRmMulti.CYRM_YARN_TOP_NO;
                            vCST_YARN_RM_MULTI.CYRM_YARN_LEFT_NO    := recRmMulti.CYRM_YARN_LEFT_NO;
                            vCST_YARN_RM_MULTI.CYRM_TYPE_WASTE      := recRmMulti.CYRM_TYPE_WASTE;
                        end if;
                        
                        begin
                            insert into CST_YARN_RM_MULTI values vCST_YARN_RM_MULTI;commit;
                        exception
                            when others then
                                verrmsg := 'Error : '||sqlerrm||' '||vCST_YARN_RM_MULTI.CYRM_SYS_ID;
                                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                                values(verrmsg,pUserId,sysdate,vIdVal);
                        end;
                                                
                        -- upd Header Rate
                        vCST_YARN_RM_HDR.CYRH_RM_COST := null;

                        select sum(CYRM_RM_RATE) into vCST_YARN_RM_HDR.CYRH_RM_COST
                        from CST_YARN_RM_MULTI
                        where CYRM_CYRH_SYS_ID = vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID;
                        
                        update CST_YARN_RM_HDR 
                            set CYRH_RM_COST = vCST_YARN_RM_HDR.CYRH_RM_COST
                        where CYRH_SYS_ID = vCST_YARN_RM_MULTI.CYRM_CYRH_SYS_ID;commit;
                        
                    end if;           
                end; 
                end loop;
              end; 
              --  'Store Rate' --> CST_YARN_RM_DTL
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Store Rate' then
              declare
                vCST_YARN_RM_DTL CST_YARN_RM_DTL%rowtype:= null;
              begin                                   
              for recRMD in (                                          
                
                select * from CST_YARN_RM_DTL                                                            
                where CYRD_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
                
              ) loop
                    vCST_YARN_RM_DTL := null;
                    vCST_YARN_RM_DTL.CYRD_SYS_ID := 
                        TO_CHAR (SYSDATE, 'YYYYMM')
                         || TO_CHAR (pkg_seq_no.next_value ('CYRD_SYS_ID',
                                                            'ADMIN',
                                                            verrmsg
                                                           ),
                                     'fm00000'
                                    );
                    vCST_YARN_RM_DTL.CYRD_CYRH_SYS_ID   := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_DTL.CYRD_SEC_NO        := recRMD.CYRD_SEC_NO;
                    if upper(recRMD.CYRD_TYPE_DATA) in ('FROM GROUP ITEM MKT RATE','FROM GROUP ITEM MKT LC','FROM GROUP ITEM LC') then
                        vCST_YARN_RM_DTL.CYRD_TYPE_DATA     := 'From Group Item VAL LC';
                    else
                        vCST_YARN_RM_DTL.CYRD_TYPE_DATA     := recRMD.CYRD_TYPE_DATA;                    
                    end if;
                    vCST_YARN_RM_DTL.CYRD_RM_CODE       := recRMD.CYRD_RM_CODE;
                    insert into CST_YARN_RM_DTL values vCST_YARN_RM_DTL;            commit;                                
              end loop;
              end;
              
              --  'Captive Cost' --> CST_YARN_RM_CAPTIVE
              elsif vCST_YARN_RM_HDR.CYRH_TYPE = 'Captive Cost' then              
              declare
                vCST_YARN_RM_CAPTIVE CST_YARN_RM_CAPTIVE%rowtype:= null;
              begin
              for recRMC in (                                          
                select * from CST_YARN_RM_CAPTIVE                                            
                where CYRC_CYRH_SYS_ID = recRMH.CYRH_SYS_ID
              ) loop
                    vCST_YARN_RM_CAPTIVE := null;
                    
                    vCST_YARN_RM_CAPTIVE.CYRC_CYRH_SYS_ID := vCST_YARN_RM_HDR.CYRH_SYS_ID;
                    vCST_YARN_RM_CAPTIVE.CYRC_YARN_TYPE := recRMC.CYRC_YARN_TYPE;
                    vCST_YARN_RM_CAPTIVE.CYRC_CYL_SYS_ID := recRMC.CYRC_CYL_SYS_ID; 
                    vCST_YARN_RM_CAPTIVE.CYRC_LEFT_NO := recRMC.CYRC_LEFT_NO;
                    vCST_YARN_RM_CAPTIVE.CYRC_CYT_SYS_ID := recRMC.CYRC_CYT_SYS_ID;
                    vCST_YARN_RM_CAPTIVE.CYRC_TOP_NO := recRMC.CYRC_TOP_NO;
                    
                    insert into CST_YARN_RM_CAPTIVE values vCST_YARN_RM_CAPTIVE;        commit;                                    
              end loop;
              end;   
              --'Multi Yarn'           
              end if;
            end loop;
        end;
        verrmsg := '6 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;            
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_MASTER_YARN' then
            declare
                vCST_YARN_PRODUCT_PARAM CST_YARN_PRODUCT_PARAM%rowtype:=null;
            begin
                for recDt in (
                
                    select * from CST_YARN_PRODUCT_PARAM
                    
                    where CYPP_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                    
                ) loop
                    vCST_YARN_PRODUCT_PARAM.CYPP_CYC_SYS_ID     := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_ID      := recDt.CYPP_COLUMN_ID;     
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_NAME    := recDt.CYPP_COLUMN_NAME;
                    vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_DESC    := recDt.CYPP_COLUMN_DESC;
                    vCST_YARN_PRODUCT_PARAM.CYPP_STS_CK         := recDt.CYPP_STS_CK;
                            
                    begin
                        insert into CST_YARN_PRODUCT_PARAM values vCST_YARN_PRODUCT_PARAM;commit;
                    exception
                        when others then
                            verrmsg := sqlerrm||' insert into CST_YARN_PRODUCT_PARAM : '
                                    ||vCST_YARN_PRODUCT_PARAM.CYPP_CYC_SYS_ID||' -> '||vDt_YARN_CALCULATION.CYC_SYS_ID
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_ID||' -> '||recDt.CYPP_COLUMN_ID  
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_NAME||' -> '||recDt.CYPP_COLUMN_NAME
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_COLUMN_DESC||' -> '||recDt.CYPP_COLUMN_DESC
                                    ||':'||vCST_YARN_PRODUCT_PARAM.CYPP_STS_CK||' -> '||recDt.CYPP_STS_CK
                                    ||':';
                            insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                            values(verrmsg,pUserId,sysdate,vIdVal);
                    end;                       
                end loop;  
            end;
            verrmsg := '7 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_MASTER_MACHINE' then            
            declare
                vCST_YARN_MACHINE_PARAM CST_YARN_MACHINE_PARAM%rowtype:=null;
            begin
                for recDt in (
                    select * from CST_YARN_MACHINE_PARAM
                    where CYMP_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                ) loop
                    vCST_YARN_MACHINE_PARAM.CYMP_CYC_SYS_ID := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_ID  := recDt.CYMP_COLUMN_ID;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_NAME:= recDt.CYMP_COLUMN_NAME;
                    vCST_YARN_MACHINE_PARAM.CYMP_COLUMN_DESC:= recDt.CYMP_COLUMN_DESC;
                    vCST_YARN_MACHINE_PARAM.CYMP_STS_CK     := recDt.CYMP_STS_CK;
                    insert into CST_YARN_MACHINE_PARAM values vCST_YARN_MACHINE_PARAM;    commit;                   
                end loop;
            end;                
            verrmsg := '8 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_BOX_BOBIN_COST' then            
            declare
                vCST_YARN_BOX_BOBIN_COST CST_YARN_BOX_BOBIN_COST%rowtype:=null;
            begin
                for recDt in (
                    select * from CST_YARN_BOX_BOBIN_COST
                    where CYBBC_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                ) loop
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_CYC_SYS_ID   := vDt_YARN_CALCULATION.CYC_SYS_ID;
                            
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_ID    := recDt.CYBBC_COLUMN_ID;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_NAME  := recDt.CYBBC_COLUMN_NAME;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_COLUMN_DESC  := recDt.CYBBC_COLUMN_DESC;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_STS_CK       := recDt.CYBBC_STS_CK;
                    vCST_YARN_BOX_BOBIN_COST.CYBBC_CMBBC_SYS_ID := recDt.CYBBC_CMBBC_SYS_ID;
                            
                    insert into CST_YARN_BOX_BOBIN_COST values vCST_YARN_BOX_BOBIN_COST;                        
                end loop;      
            end;                                    
            verrmsg := '9 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_DATA' then            
            declare
                vCST_YARN_M_B_MST CST_YARN_M_B_MST%rowtype:=null;
            begin
                for recDt in (
                    select * from CST_YARN_M_B_MST
                    where CYMBM_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                ) loop
                    vCST_YARN_M_B_MST.CYMBM_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_ID   := recDt.CYMBM_COLUMN_ID;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_NAME := recDt.CYMBM_COLUMN_NAME;
                    vCST_YARN_M_B_MST.CYMBM_COLUMN_DESC := recDt.CYMBM_COLUMN_DESC;
                    vCST_YARN_M_B_MST.CYMBM_STS_CK      := recDt.CYMBM_STS_CK;                                            
                    
                    vCST_YARN_M_B_MST.CYMBM_CMBH_SYS_ID := recDt.CYMBM_CMBH_SYS_ID;
                            
                    insert into CST_YARN_M_B_MST values vCST_YARN_M_B_MST;commit;
                end loop;
            end;
                    
            declare
                vCST_YARN_M_B_DT CST_YARN_M_B_DT%rowtype:=null;
            begin
                for recDt in (
                    select * from CST_YARN_M_B_DT
                    where CYMBD_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                    and CYMBD_STS_CK = 1
                ) loop
                    vCST_YARN_M_B_DT.CYMBD_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    if nvl(recDt.CYMBD_COLUMN_NAME,'NULL') = 'CMCH_COST_MARKETING' then
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_ID    := '9';                    
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_NAME  := 'CMCH_RM_VAL';
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_DESC  := 'Valuation Composition';                    
                    else        
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_ID    := recDt.CYMBD_COLUMN_ID;                    
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_NAME  := recDt.CYMBD_COLUMN_NAME;
                        vCST_YARN_M_B_DT.CYMBD_COLUMN_DESC  := recDt.CYMBD_COLUMN_DESC;
                    end if;
                    vCST_YARN_M_B_DT.CYMBD_STS_CK       := recDt.CYMBD_STS_CK;
                    vCST_YARN_M_B_DT.CYMBD_CMBH_SYS_ID := recDt.CYMBD_CMBH_SYS_ID;                
                            
                    insert into CST_YARN_M_B_DT values vCST_YARN_M_B_DT;               commit;         
                end loop;
            end;     
            verrmsg := '10 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;           
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_MASTER_BATCH_SPINNING' then            
            declare
                vCST_YARN_M_B_S_MST CST_YARN_M_B_S_MST%rowtype:=null;
            begin
                vCST_YARN_M_B_S_MST := null;
                for recDt in (
                    select * from CST_YARN_M_B_S_MST
                    where CYMBMS_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
                    and CYMBMS_STS_CK = 1
                ) loop
                    vCST_YARN_M_B_S_MST.CYMBMS_CYC_SYS_ID  := vDt_YARN_CALCULATION.CYC_SYS_ID;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_ID   := recDt.CYMBMS_COLUMN_ID;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_NAME := recDt.CYMBMS_COLUMN_NAME;
                    vCST_YARN_M_B_S_MST.CYMBMS_COLUMN_DESC := recDt.CYMBMS_COLUMN_DESC;
                    vCST_YARN_M_B_S_MST.CYMBMS_STS_CK      := recDt.CYMBMS_STS_CK;
                    vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CMBH_SYS_ID := recDt.CYMBMS_CMBS_CMBH_SYS_ID;
                    
                    vCST_YARN_M_B_S_MST.CYMBMS_CMBS_CODE := recDt.CYMBMS_CMBS_CODE;
                    
                    vCST_YARN_M_B_S_MST.CYMBMS_CMBS_SYS_ID := recDt.CYMBMS_CMBS_SYS_ID;
                                                            
                    insert into CST_YARN_M_B_S_MST values vCST_YARN_M_B_S_MST;commit;
                end loop;
            end;                         
            verrmsg := '11 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
        elsif upper(recYarnCalFrom.CYC_FORMULA_TYPE) = 'FROM_PRODUCT_GRADE' then                        
        declare
            vCST_YARN_PRODUCT_GRADE CST_YARN_PRODUCT_GRADE%rowtype:=null;
        begin
            for recDt in (
                select * from CST_YARN_PRODUCT_GRADE
                where CYPG_CYC_SYS_ID = recYarnCalFrom.CYC_SYS_ID
            ) loop
                vCST_YARN_PRODUCT_GRADE.CYPG_CYC_SYS_ID     := vDt_YARN_CALCULATION.CYC_SYS_ID;
                        
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_ID      := recDt.CYPG_COLUMN_ID;
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_NAME    := recDt.CYPG_COLUMN_NAME;
                vCST_YARN_PRODUCT_GRADE.CYPG_COLUMN_DESC    := recDt.CYPG_COLUMN_DESC;
                vCST_YARN_PRODUCT_GRADE.CYPG_STS_CK         := recDt.CYPG_STS_CK;
                vCST_YARN_PRODUCT_GRADE.CYPG_CMPG_SYS_ID    := recDt.CYPG_CMPG_SYS_ID;
                        
                insert into CST_YARN_PRODUCT_GRADE values vCST_YARN_PRODUCT_GRADE;commit;
            end loop;
        end;   
        end if;      
        verrmsg := '12 Top No valuation: '||recYarnCalFrom.CYC_TOP_NO||' '||sqlerrm;
    exception
        when vExp then
            null;        
    end copy_top_valuation;
    
    procedure copy_formula(
        pUserId varchar2,pCYL_SYS_ID_From varchar2,pCYL_SYS_ID_To varchar2,pCYC_PRS_TYPE varchar2,pErrMsg out varchar2) is
        
        vCYC_SYS_ID varchar2(30);
        verrmsg varchar2(200);
        vDt_YARN_LEFT CST_YARN_LEFT%rowtype;
        vDt_YARN_CALCULATION CST_YARN_CALCULATION%rowtype;
        vRecYarnCalFrom CST_YARN_CALCULATION%rowtype;
        
        vDt_YARN_FORMULA_CALC CST_YARN_FORMULA_CALC%rowtype;
        vTotSeqDt number;
        
        vDt_YARN_IFCOND_DTL CST_YARN_IFCOND_DTL%rowtype;
        vDt_YARN_IFCOND_HDR CST_YARN_IFCOND_HDR%rowtype;
        
        vCYL_SYS_ID_New varchar2(30);
        vCYC_SYS_ID_DT varchar2(30);
    begin
        pErrMsg := null;
        
        pDelYarnCalculation(pCYL_SYS_ID_TO);
        
        select max(nvl(CYC_PROCESS_SEQ,0))  
        into vTotSeqDt
        from CST_YARN_CALCULATION
        where CYC_CYL_SYS_ID = pCYL_SYS_ID_From
        and CYC_PRS_TYPE = pCYC_PRS_TYPE ;
        
        -- looping tot Seq Process in Formula From
        for vLoop in 0..vTotSeqDt 
        loop
            vDt_YARN_LEFT := null;
                
            select * into vDt_YARN_LEFT
            from  CST_YARN_LEFT
            where CYL_SYS_ID = pCYL_SYS_ID_To;                 
            -- Get Total Top No depend on Seq Process
            for vDtTopInPrs in (
                select CYC_CYT_SYS_ID, CYC_TOP_NO
                from CST_YARN_CALCULATION a
                where CYC_CYL_SYS_ID = pCYL_SYS_ID_From
                and nvl(CYC_PROCESS_SEQ,0) = vLoop
                and CYC_PRS_TYPE = pCYC_PRS_TYPE
                order by to_number(cyc_top_no)
            ) loop
            begin
                select * into vRecYarnCalFrom
                from CST_YARN_CALCULATION 
                where CYC_CYL_SYS_ID = pCYL_SYS_ID_From
                and CYC_CYT_SYS_ID = vDtTopInPrs.CYC_CYT_SYS_ID
                and CYC_PRS_TYPE = pCYC_PRS_TYPE
                ;
                
                begin             
                  select cyc_sys_id
                            into vCYC_SYS_ID_DT
                  from  CST_YARN_LEFT l
                        ,cst_yarn_calculation c
                  where CYL_SYS_ID = vDt_YARN_LEFT.CYL_SYS_ID
                  and CYC_CYT_SYS_ID = c.cyc_cyl_sys_id
                  and CYC_CYT_SYS_ID = vDtTopInPrs.CYC_CYT_SYS_ID
                  and CYC_PRS_TYPE = pCYC_PRS_TYPE
                  and CYC_PRS_TYPE = CYL_PRS_TYPE		
                  ;  
                exception
                    when no_data_found then
                        null;
                end;            
                       
		        copy_top(									 
                     pUserId
                   ,vDt_YARN_LEFT--pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                   ,vRecYarnCalFrom--pRecYarnCalFrom --cst_YARN_CALCULATION%rowtype
                   ,vCYL_SYS_ID_New
                   ,vCYC_SYS_ID_DT
                   ,pCYC_PRS_TYPE
                   ,verrmsg
                    );
                    
                if  verrmsg is not null then
                    verrmsg := 'Error Top No : '||vDtTopInPrs.CYC_TOP_NO||' '||verrmsg;
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                    values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);
                end if;                   	                
            exception
                when others then
                    verrmsg := 'Error Top No : '||vDtTopInPrs.CYC_TOP_NO||' '||sqlerrm;
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                    values(verrmsg,pUserId,sysdate,pCYC_PRS_TYPE);                                    
            end;
            end loop;
            -- Get Total Top No depend on Seq Process            
        end loop;
        -- End looping tot Seq Process in Formula From
    exception
        when others then
            dbms_output.put_line('eRROR : '||sqlerrm);
            pErrMsg := sqlerrm;             
            --rollback;            
    end copy_formula;
    
    procedure copy_formula_valuation(
        pUserId varchar2,pCYL_SYS_ID_From varchar2,pCYL_SYS_ID_To varchar2,pErrMsg out varchar2) is
        
        vCYC_SYS_ID varchar2(30);
        verrmsg varchar2(200);
        vDt_YARN_LEFT CST_YARN_LEFT%rowtype;
        vDt_YARN_CALCULATION CST_YARN_CALCULATION%rowtype;
        vRecYarnCalFrom CST_YARN_CALCULATION%rowtype;
        
        vDt_YARN_FORMULA_CALC CST_YARN_FORMULA_CALC%rowtype;
        vTotSeqDt number;
        
        vDt_YARN_IFCOND_DTL CST_YARN_IFCOND_DTL%rowtype;
        vDt_YARN_IFCOND_HDR CST_YARN_IFCOND_HDR%rowtype;
        
        vCYT_SYS_ID_Dest varchar2(30);
    begin
        pErrMsg := null;
        
        pDelYarnCalculation(pCYL_SYS_ID_TO);
        
        -- get total Sequence Process Marketing
        select max(nvl(CYC_PROCESS_SEQ,0))  
        into vTotSeqDt
        from CST_YARN_CALCULATION
        where CYC_CYL_SYS_ID = pCYL_SYS_ID_From;
        
        vDt_YARN_LEFT := null;
                    
        -- taking left data valuation 
        begin                
            select * into vDt_YARN_LEFT
            from  CST_YARN_LEFT
            where CYL_SYS_ID = pCYL_SYS_ID_To;
        exception
            when others then
                --verrmsg := '1 Error Top No valuation: pCYL_SYS_ID_To '||pCYL_SYS_ID_To||' '||sqlerrm;
                insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                values(verrmsg,pUserId,sysdate,vIdVal);                                    
        end;
                    
        
        -- looping tot Seq Process in Formula From
        for vLoop in 0..vTotSeqDt 
        loop
            -- Looping in TOP data Valuation            
            for recDtVal in (
                select CYC_CYT_SYS_ID, CYC_TOP_NO
                from CST_YARN_CALCULATION a
                where CYC_CYL_SYS_ID = pCYL_SYS_ID_From
                and nvl(CYC_PROCESS_SEQ,0) = vLoop
                and CYC_PRS_TYPE = vIdMkt
                order by to_number(cyc_top_no)
            ) loop
            begin
                begin
                    select * into vRecYarnCalFrom
                    from CST_YARN_CALCULATION 
                    where CYC_PRS_TYPE = vIdMkt
                    and CYC_CYL_SYS_ID = pCYL_SYS_ID_From
                    and CYC_TOP_NO = recDtVal.CYC_TOP_NO
                    ;
                exception
                when others then
                    --verrmsg := '2 Error Top No valuation: '||vDtTopInPrs.CYC_TOP_NO||' '||sqlerrm;
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                    values(verrmsg,pUserId,sysdate,vIdVal);                                    
                end;


                /*if vRecYarnCalFrom.CYC_TOP_NO between 1 and 29 then
                 vRecYarnCalFrom.cyc_formula_type := 'From_Marketing_Cost';
                elsif vRecYarnCalFrom.CYC_TOP_NO in (49,51) then
                 vRecYarnCalFrom.cyc_formula_type := 'From_Marketing_Cost';
                end if;*/
                
                -- get CYC_SYS_ID destination                		
                vCYT_SYS_ID_Dest := get_CYT_SYS_ID(recDtVal.CYC_TOP_NO,vIdVal);
                
		        copy_top_valuation(									 
                     pUserId
                   ,vDt_YARN_LEFT--pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                   ,vRecYarnCalFrom--pRecYarnCalFrom --cst_YARN_CALCULATION%rowtype
                   ,vCYT_SYS_ID_Dest
                   ,verrmsg
                    );
                    
                if  verrmsg is not null then
                    --verrmsg := '4 Error Top No valuation 4 : '||vDtTopInPrs.CYC_TOP_NO||' '||verrmsg;
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                    values(verrmsg,pUserId,sysdate,vIdVal);                                    
                end if;                   	                
            exception
                when others then
                    --verrmsg := '5 Error Top No valuation 5 : '||vDtTopInPrs.CYC_TOP_NO||' '||sqlerrm;
                    insert into CST_YARN_ERR_LOG(CYEL_ERR_LOG, CYEL_USER_NAME, CYEL_PROCESS_TIME,CYEL_PRS_TYPE) 
                    values(verrmsg,pUserId,sysdate,vIdVal);                                    
            end;
            end loop;
            -- Get Total Top No depend on Seq Process            
        end loop;
        -- End looping tot Seq Process in Formula From        
        
        -- copy Top 127
        declare
            vDtYarn_Lp mgtapps.CST_YARN_CALCULATION%rowtype;
        begin
            vDtYarn_Lp := null;
            /*if vDt_YARN_LEFT.cyl_type = 'POY' then
                select * into vDtYarn_127
                from CST_YARN_CALCULATION a
                where CYC_LEFT_NO = 1
                and CYC_TOP_NO = 127
                and CYC_PRS_TYPE = vIdVal;
            elsif vDt_YARN_LEFT.cyl_type = 'PTY' then
                select * into vDtYarn_127
                from CST_YARN_CALCULATION a
                where CYC_LEFT_NO = 27
                and CYC_TOP_NO = 127
                and CYC_PRS_TYPE = vIdVal;
            end if;*/
            
            for recDtLp in 127..128 loop
            
            SELECT *
              INTO vDtYarn_Lp
              FROM cst_yarn_calculation a
             WHERE cyc_left_no = recDtLp--27
               AND cyc_top_no IN (SELECT MIN (cyl_left_no)
                                    FROM cst_yarn_left
                                   WHERE cyl_type = vdt_yarn_left.cyl_type)
               AND cyc_prs_type = vidval;
            
            
            
            if vDtYarn_Lp.CYC_SYS_ID is not null then
                vCYT_SYS_ID_Dest := get_CYT_SYS_ID(
                recDtLp--127
                ,vIdVal);
                
                copy_top_valuation(									 
                         pUserId
                       ,vDt_YARN_LEFT--pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                       ,vDtYarn_Lp--pRecYarnCalFrom --cst_YARN_CALCULATION%rowtype
                       ,vCYT_SYS_ID_Dest
                       ,verrmsg);            
            end if;        
            
                            null;
            end loop;
               
        end;
        -- copy Top 127

    exception
        when others then
            dbms_output.put_line('eRROR : '||sqlerrm);
            pErrMsg := sqlerrm;             
            --rollback;            
    end copy_formula_valuation;
    
    -- copy 113 118        
    
    PROCEDURE pCopy_113_118(pUser_id varchar2
                            ,pCOPY_CYC_CYL_SYS_ID varchar2 --CST_YARN_LEFT.COPY_CYC_CYL_SYS_ID 
							,pLEFT_CYL_SYS_ID varchar2 --:CST_YARN_LEFT.CYL_SYS_ID
                            ,pCYC_PRS_TYPE varchar2
                            ,pCYL_TYPE_D varchar2 
							,pErrMsg out varchar2) IS
	vDt_YARN_LEFT CST_YARN_LEFT%rowtype;
	vRecYarnCalFrom cst_yarn_calculation%rowtype;
	vCYL_SYS_ID_New varchar2(30);
	
	vCYC_SYS_ID_DF varchar2(30);
	vCYC_SYS_ID_DT varchar2(30);
	vExp exception;
	
	Procedure prs_copy(recYL_cyc_top_no number,recYL_cyc_sys_id varchar2) is
	begin
			-- get data left will be copy
			select l.*
				into vDt_YARN_LEFT
      from  CST_YARN_LEFT l
      where CYL_SYS_ID = pLEFT_CYL_SYS_ID --:CST_YARN_LEFT.CYL_SYS_ID
      ;
      
			select cyc_sys_id
				into vCYC_SYS_ID_DT
      from  CST_YARN_LEFT l
            ,cst_yarn_calculation c
      where CYL_SYS_ID = vDt_YARN_LEFT.CYL_SYS_ID
      and CYL_SYS_ID = c.cyc_cyl_sys_id
      and CYC_TOP_NO = recYL_cyc_top_no;--pCYL_SYS_ID_To				        				        
      
      MGTAPPS.pkg_yarn_calculation.pClearFormula(vCYC_SYS_ID_DT);
      -- get data left will be copy				        				        					        					        

			-- get data calc source copy
      begin		              				        	
        select * into vRecYarnCalFrom
        from CST_YARN_CALCULATION 
        where CYC_SYS_ID = recYL_cyc_sys_id
				--:CST_YARN_CALCULATION.CYC_SYS_ID--:CST_YARN_CALCULATION.COPY_CYC_SYS_ID-- recYarnCal.CYC_SYS_ID
        ;		
      exception
      	when others then
     			--vErrMsg 
     			pErrMsg := 'ERROR : Get Calculation Data '||recYL_cyc_sys_id||' '||sqlerrm;						 		
     			--exit;
      		raise vExp;
      end;								
    
		MGTAPPS.pkg_yarn_calculation.copy_top
                    (									 
                     pUser_id
                     ,vDt_YARN_LEFT--pDt_YARN_LEFT cst_YARN_LEFT%rowtype
                     ,vRecYarnCalFrom--pRecYarnCalFrom --cst_YARN_CALCULATION%rowtype
                     ,vCYL_SYS_ID_New
                     ,vCYC_SYS_ID_DT
                     ,pCYC_PRS_TYPE
                     ,pErrMsg
                     );								
	end prs_copy;
begin
	IF pCYL_TYPE_d = 'PTY' THEN
		for recYL in 
		(
		  -- query left from
			select cyc_sys_id,cyc_cyt_sys_id,cyc_top_no,l.* 
      from  CST_YARN_LEFT l
      			,CST_YARN_calculation c
      where CYL_SYS_ID = pCOPY_CYC_CYL_SYS_ID--:CST_YARN_LEFT.COPY_CYC_CYL_SYS_ID -- copy
      and CYL_SYS_ID = CYC_CYL_SYS_ID
      and cyc_top_no  in (118,119,120,121,122)
      order by cyc_top_no
      ) loop
			
			--vErrMsg 
			pErrMsg:= null;
			prs_copy(recYL.cyc_top_no,recYL.cyc_sys_id);
			
			if pErrMsg is not null then
				exit;
			end if;

		end loop;
	elsIF pCYL_TYPE_d = 'TTY' THEN
		for recYL in 
		(
		  -- query left from
			select cyc_sys_id,cyc_cyt_sys_id,cyc_top_no,l.* 
      from  CST_YARN_LEFT l
      			,CST_YARN_calculation c
      where CYL_SYS_ID = pCOPY_CYC_CYL_SYS_ID--:CST_YARN_LEFT.COPY_CYC_CYL_SYS_ID -- copy
      and CYL_SYS_ID = CYC_CYL_SYS_ID
      and cyc_top_no  in (113,114,115,116,117)
      order by cyc_top_no
      ) loop
			
			pErrMsg := null;
			prs_copy(recYL.cyc_top_no,recYL.cyc_sys_id);
			
			if pErrMsg is not null then
				exit;
			end if;

		end loop;
	END IF;
exception
	when vExp then
		null;
	when others then
		pErrMsg := 'ERROR '||sqlerrm;
end;
    -- copy 113 118
    
    function getProductGradeType(pCMPG_SYS_ID varchar2) return varchar2 is    
       vCMPG_POY_B_C_GRADE CST_MST_PRODUCT_GRADE.CMPG_POY_B_C_GRADE%type;
    begin
        vCMPG_POY_B_C_GRADE := null;
        begin
            select  CMPG_POY_B_C_GRADE into vCMPG_POY_B_C_GRADE 
            from CST_MST_PRODUCT_GRADE
            where CMPG_SYS_ID = pCMPG_SYS_ID;
        exception
            when no_data_found then
                null;
        end;
        return vCMPG_POY_B_C_GRADE;
    end getProductGradeType;
    
    procedure pUpdMstCalcCost(pCyc_Sys_id varchar2,pcyc_prs_type varchar2,pErrMsg out varchar2) is
        vTmp number;
        vCYC_TOP_NO number;
        vCYL_TYPE mgtapps.CST_YARN_LEFT.CYL_TYPE%type; 
    begin
        -- gET Top NO
        BEGIN
            select CYC_TOP_NO,CYL_TYPE 
            into vCYC_TOP_NO,vCYL_TYPE
            from CST_YARN_CALCULATION t
                 ,CST_YARN_LEFT l
            where t.cyc_sys_id = pCyc_Sys_id
            and t.CYC_CYL_SYS_ID = l.CYL_SYS_ID 
            and cyc_prs_type = pcyc_prs_type
            and cyc_prs_type = cyl_prs_type;
        exception
            when no_data_found then
                vCYC_TOP_NO := null;
                vCYL_TYPE := null;
        END;    
    
        if vCYC_TOP_NO is not null and vCYL_TYPE = 'POY' then
            -- ** Update MB Data ** --
            declare
                vcmbh_sys_id varchar2(30);            
                vcmbs_sys_id varchar2(30);
            begin
                select 'x' into vTmp
                from CST_YARN_CALCULATION t
                     ,CST_YARN_LEFT l 
                where t.cyc_sys_id = pCyc_Sys_id        
                and t.cyc_CYL_SYS_ID = l.CYL_SYS_ID
                and CYC_TOP_NO in (63,64,65,66,67,68)
                and CYL_TYPE = 'POY';
            exception
                when no_data_found then
                    null;
                when others then 
                begin
                    begin
                    -- Get MB Id
                    SELECT cmbh_sys_id,cmbs_sys_id INTO vcmbh_sys_id,vcmbs_sys_id
                      FROM (
                            -- col 63
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 63
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                            UNION ALL
                            -- col 64
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 64
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                            UNION ALL
                            -- col 65
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 65
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                            UNION ALL
                            -- col 66
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 66
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                            UNION ALL
                            -- col 67
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 67
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                            UNION ALL
                            -- col 68
                            SELECT cyc_top_no, CYMBMS_CMBS_CMBH_SYS_ID cmbh_sys_id,CYMBMS_CMBS_SYS_ID CMBS_SYS_ID 
                              FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                             WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                          FROM cst_yarn_calculation t
                                                         WHERE cyc_sys_id = pcyc_sys_id)
                                                                                    --'2020107551'
                               AND t.cyc_top_no = 68
                               AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                               AND d.cymbms_sts_ck = 1
                               )
                     WHERE cyc_top_no = vcyc_top_no;
                    exception
                        when no_data_found then
                            vcmbh_sys_id := null;
                    end;                                                
                end;

                if vCYC_TOP_NO <> 63 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID                                
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 63
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;                    
                if vCYC_TOP_NO <> 64 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 64
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;
                if vCYC_TOP_NO <> 65 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 65
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;                    
                if vCYC_TOP_NO <> 66 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 66
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;
                if vCYC_TOP_NO <> 67 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 67
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;                        
                if vCYC_TOP_NO <> 68 then                        
                    update cst_yarn_m_b_s_mst
                        set CYMBMS_CMBS_CMBH_SYS_ID = vcmbh_sys_id,CYMBMS_CMBS_SYS_ID = vCMBS_SYS_ID
                    where cymbms_sts_ck = 1     
                    and (CYMBMS_CYC_SYS_ID,CYMBMS_COLUMN_ID) in (
                        SELECT CYMBMs_CYC_SYS_ID,CYMBMs_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_s_mst d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 68
                           AND t.cyc_sys_id = d.cymbms_cyc_sys_id
                           AND d.cymbms_sts_ck = 1);
                end if;
                if vCYC_TOP_NO <> 72 then                        
                    update cst_yarn_m_b_dt
                        set CYMBD_CMBH_SYS_ID = vcmbh_sys_id
                    where cymbd_sts_ck = 1     
                    and (CYMBd_CYC_SYS_ID,CYMBd_COLUMN_ID) in (
                        SELECT CYMBd_CYC_SYS_ID,CYMBd_COLUMN_ID
                          FROM cst_yarn_calculation t, cst_yarn_m_b_dt d
                         WHERE t.cyc_cyl_sys_id IN (SELECT t.cyc_cyl_sys_id
                                                      FROM cst_yarn_calculation t
                                                     WHERE cyc_sys_id = pcyc_sys_id) 
                           AND t.cyc_top_no = 72
                           AND t.cyc_sys_id = d.cymbd_cyc_sys_id
                           AND d.cymbd_sts_ck = 1);                                                                                                             
                end if;
            end;      
            -- ** Update MB Data ** --
            
            -- ** Update CHIPS Data ** --
            declare
                vCYRD_RM_CODE varchar2(30); 
            begin
                select 'x' into vTmp
                from CST_YARN_CALCULATION t
                     ,CST_YARN_LEFT l 
                where t.cyc_sys_id = pCyc_Sys_id        
                and t.cyc_CYL_SYS_ID = l.CYL_SYS_ID
                and CYC_TOP_NO in (55,56)
                and CYL_TYPE = 'POY';
            exception
                when no_data_found then
                    null;
                when others then
                begin
                    SELECT CYRD_RM_CODE into vCYRD_RM_CODE
                      FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h, cst_yarn_calculation c
                     WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                       AND cyrh_cyc_sys_id = c.cyc_sys_id
                       AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                  FROM cst_yarn_calculation c
                                                 WHERE cyc_sys_id = pCyc_Sys_id)
                       AND CYC_TOP_NO = vCYC_TOP_NO;
                end; 
               
                if vCYC_TOP_NO <> 55 then
                    UPDATE cst_yarn_rm_dtl
                       SET cyrd_rm_code = vcyrd_rm_code
                     WHERE cyrd_sys_id =
                              (SELECT cyrd_sys_id
                                 FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h,
                                      cst_yarn_calculation c
                                WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                                  AND cyrh_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                             FROM cst_yarn_calculation c
                                                            WHERE cyc_sys_id = pcyc_sys_id)
                                  AND cyc_top_no = 55);
                end if; 
                
                if vCYC_TOP_NO <> 56 then
                    UPDATE cst_yarn_rm_dtl
                       SET cyrd_rm_code = vcyrd_rm_code
                     WHERE cyrd_sys_id =
                              (SELECT cyrd_sys_id
                                 FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h,
                                      cst_yarn_calculation c
                                WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                                  AND cyrh_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                             FROM cst_yarn_calculation c
                                                            WHERE cyc_sys_id = pcyc_sys_id)
                                  AND cyc_top_no = 56);
                end if;                
                
            end;
            -- ** Update CHIPS Data ** --
            -- ** Update OIL Data ** --
            declare
                vCYRD_RM_CODE varchar2(30); 
            begin
                select 'x' into vTmp
                from CST_YARN_CALCULATION t
                     ,CST_YARN_LEFT l 
                where t.cyc_sys_id = pCyc_Sys_id        
                and t.cyc_CYL_SYS_ID = l.CYL_SYS_ID
                and CYC_TOP_NO in (59,60)
                and CYL_TYPE = 'POY';
            exception
                when no_data_found then
                    null;
                when others then
                begin
                    SELECT CYRD_RM_CODE into vCYRD_RM_CODE
                      FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h, cst_yarn_calculation c
                     WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                       AND cyrh_cyc_sys_id = c.cyc_sys_id
                       AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                  FROM cst_yarn_calculation c
                                                 WHERE cyc_sys_id = pCyc_Sys_id)
                       AND CYC_TOP_NO = vCYC_TOP_NO;
                end; 
               
                if vCYC_TOP_NO <> 59 then
                    UPDATE cst_yarn_rm_dtl
                       SET cyrd_rm_code = vcyrd_rm_code
                     WHERE cyrd_sys_id =
                              (SELECT cyrd_sys_id
                                 FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h,
                                      cst_yarn_calculation c
                                WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                                  AND cyrh_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                             FROM cst_yarn_calculation c
                                                            WHERE cyc_sys_id = pcyc_sys_id)
                                  AND cyc_top_no = 59);
                end if; 
                
                if vCYC_TOP_NO <> 60 then
                    UPDATE cst_yarn_rm_dtl
                       SET cyrd_rm_code = vcyrd_rm_code
                     WHERE cyrd_sys_id =
                              (SELECT cyrd_sys_id
                                 FROM cst_yarn_rm_dtl d, cst_yarn_rm_hdr h,
                                      cst_yarn_calculation c
                                WHERE h.cyrh_sys_id = d.cyrd_cyrh_sys_id
                                  AND cyrh_cyc_sys_id = c.cyc_sys_id
                                  AND c.cyc_cyl_sys_id IN (SELECT cyc_cyl_sys_id
                                                             FROM cst_yarn_calculation c
                                                            WHERE cyc_sys_id = pcyc_sys_id)
                                  AND cyc_top_no = 60);
                end if;                
                
            end;
            -- ** Update OIL Data ** --            
        end if;
        
        -- Upd Raw Materian --
        if vCYL_TYPE = 'PTY' or vCYL_TYPE = 'TTY' then
            declare
                vCST_YARN_RM_HDR_S mgtapps.CST_YARN_RM_HDR%rowtype;
                vCST_YARN_RM_CAPTIVE_S  mgtapps.CST_YARN_RM_CAPTIVE%rowtype;
                vCST_YARN_CALCULATION_S mgtapps.CST_YARN_CALCULATION%rowtype;
                
                vCST_YARN_CALCULATION_D mgtapps.CST_YARN_CALCULATION%rowtype;
                vCST_YARN_RM_HDR_D mgtapps.CST_YARN_RM_HDR%rowtype;
                vCST_YARN_RM_CAPTIVE_D  mgtapps.CST_YARN_RM_CAPTIVE%rowtype;
                
                vCYC_TOP_NO_D number;
                vCYRC_TOP_NO_D number;
                
            begin
                begin    
                    select * into vCST_YARN_RM_HDR_S
                    from CST_YARN_RM_HDR
                    where CYRH_CYC_SYS_ID = pcyc_sys_id
                    and CYRH_TYPE = 'Captive Cost';
                exception
                    when no_data_found then
                        vCST_YARN_RM_HDR_S := null;
                end;
            
                if vCST_YARN_RM_HDR_S.CYRH_SYS_ID is not null then
                    begin 
                        select * into vCST_YARN_RM_CAPTIVE_S 
                        from mgtapps.CST_YARN_RM_CAPTIVE
                        where CYRC_CYRH_SYS_ID = vCST_YARN_RM_HDR_S.CYRH_SYS_ID;
                        
                        -- get inform formula calc source 
                        select * into vCST_YARN_CALCULATION_S 
                        from mgtapps.CST_YARN_CALCULATION
                        where CYC_SYS_ID = pcyc_sys_id;

                        if vCST_YARN_CALCULATION_S.CYC_TOP_NO = 20 then
                            vCYC_TOP_NO_D := 55;
                            vCYRC_TOP_NO_D := 105;
                        elsif vCST_YARN_CALCULATION_S.CYC_TOP_NO = 55 then
                            vCYC_TOP_NO_D := 20;
                            vCYRC_TOP_NO_D := 2;
                        end if;
                        
                        -- find inform formula calc dest 
                        select * into vCST_YARN_CALCULATION_D 
                        from mgtapps.CST_YARN_CALCULATION
                        where CYC_CYL_SYS_ID = vCST_YARN_CALCULATION_S.CYC_CYL_SYS_ID
                        and CYC_TOP_NO = vCYC_TOP_NO_D;
                        
                        begin    
                            select * into vCST_YARN_RM_HDR_D
                            from CST_YARN_RM_HDR
                            where CYRH_CYC_SYS_ID = vCST_YARN_CALCULATION_D.cyc_sys_id
                            and CYRH_TYPE = 'Captive Cost';
                            
                        exception
                            when no_data_found then
                                vCST_YARN_RM_HDR_D := null;
                        end;                        
                        
                        update CST_YARN_RM_CAPTIVE
                            set CYRC_CYL_SYS_ID = vCST_YARN_RM_CAPTIVE_S.CYRC_CYL_SYS_ID
                                ,CYRC_LEFT_NO = vCST_YARN_RM_CAPTIVE_S.CYRC_LEFT_NO 
                        where CYRC_CYRH_SYS_ID = vCST_YARN_RM_HDR_D.CYRH_SYS_ID
                        and CYRC_TOP_NO = vCYRC_TOP_NO_D;                                                
                        
                    exception
                        when no_data_found then
                            vCST_YARN_RM_CAPTIVE_S        := null;                                                          
                    end;
                    
                end if;
            end;            
        end if; 
        -- Upd Raw Materian --  
    exception
        when others then
            pErrMsg := 'ERROR pUpdMstCalcCost : '||sqlerrm;        
   end pUpdMstCalcCost;   
   
   procedure pRefrshValParam(pUserId varchar2,pPRS_TYPE varchar2) is
        vDataVal varchar2(300);
        vCYC_LEFT_NO number;         
   begin
       -- Refresh Value Formula  
       for recDtLeft in (         
        
        select CYRM_SYS_ID,CYRM_MARKETING_CODE,CYRM_YARN_TOP_NO,CYRM_YARN_LEFT_NO
        from CST_YARN_RM_MULTI m
             --,CST_YARN_FILTER f
             ,CST_YARN_left L
        where CYRM_TYPE_DATA = 'Yarn-Cap'
        and CYRM_MARKETING_CODE = CYL_MARKETING_COST_LINK
        and CYL_PRS_TYPE = pPRS_TYPE
        --and CYF_CYL_SYS_ID = CYL_SYS_ID
        --and CYF_USER_ID = pUserId
        
       ) loop
       begin
            vCYC_LEFT_NO := get_LEFT_NO(1,recDtLeft.CYRM_MARKETING_CODE,pPRS_TYPE);
            if vCYC_LEFT_NO <> recDtLeft.CYRM_YARN_LEFT_NO then
                update CST_YARN_RM_MULTI
                    set CYRM_YARN_LEFT_NO = vCYC_LEFT_NO
                where CYRM_SYS_ID = recDtLeft.CYRM_SYS_ID;                
            end if;  
       end;
       end loop;
     
       for recDt in (                
            select m.* 
            from cst_yarn_rm_multi m
                 ,cst_yarn_rm_hdr h
            where m.CYRM_TYPE_DATA = 'Yarn-Cap'
            and m.CYRM_CYRH_SYS_ID = CYRH_SYS_ID
            and CYRH_TYPE = 'Yarn Rate'
            order by m.CYRM_SYS_ID                                    
        ) loop        
            -- UPDATE MARKETING Code
            if recDt.CYRM_TYPE_DATA = 'Yarn-Cap' and recDt.CYRM_MARKETING_CODE is null then
                begin
                
                    select CYL_MARKETING_COST_LINK into vDataVal 
                    from mgtapps.CST_YARN_LEFT 
                    where CYL_PRS_TYPE = pPRS_TYPE 
                    and CYL_LEFT_NO = recDt.CYRM_YARN_LEFT_NO;            
                exception
                    when no_data_found then
                        vDataVal := null;
                    when too_many_rows then
                        vDataVal := null;
                end;
            
                update cst_yarn_rm_multi
                    set CYRM_MARKETING_CODE = vDataVal
                where CYRM_SYS_ID =  recDt.CYRM_SYS_ID;
            end if;
            -- UPDATE MARKETING Code        
        
            -- UPDATE rm RATE
            begin
                select cyc_data_value into vDataVal
                from mgtapps.cst_yarn_calculation c
                     ,mgtapps.CST_YARN_LEFT l
                where c.CYC_CYL_SYS_ID = CYL_SYS_ID
                and CYL_MARKETING_COST_LINK = recDt.CYRM_MARKETING_CODE--'TTY0000020-X459T-WT-NA-S'
                and cyc_top_no = recDt.CYRM_YARN_TOP_NO
                AND CYL_LEFT_NO = recDt.CYRM_YARN_LEFT_NO
                and CYL_PRS_TYPE  = pPRS_TYPE;
            exception
                when no_data_found then
                    vDataVal := null;
                when too_many_rows then
                    vDataVal := null;
            end;
        
            update cst_yarn_rm_multi
                set CYRM_RM_RATE = vDataVal
            where CYRM_SYS_ID =  recDt.CYRM_SYS_ID;
            -- UPDATE rm RATE   
        end loop;
        
        for recHdr in (
            select CYRM_CYRH_SYS_ID, sum(CYRM_RM_RATE) CYRM_RM_RATE
            from cst_yarn_rm_multi m
                 ,cst_yarn_rm_hdr h
            where m.CYRM_TYPE_DATA = 'Yarn-Cap'
            and m.CYRM_CYRH_SYS_ID = CYRH_SYS_ID
            and CYRH_TYPE = 'Yarn Rate'
            group by CYRM_CYRH_SYS_ID
       ) loop
            update cst_yarn_rm_hdr
                set CYRH_RM_COST =recHdr.CYRM_RM_RATE
            where CYRH_SYS_ID =  recHdr.CYRM_CYRH_SYS_ID;
       end loop;
       
       declare
        vTmp number;
        vCYRC_CYL_SYS_ID varchar2(30);
        vCYRC_LEFT_NO number;
       begin       
       for recRM in (              
            select a.CYRH_SYS_ID,a.CYL_SHADE_CODE, a.CYL_SHADE_NAME from 
            (
            SELECT CYRH_SYS_ID,CYC_CYL_SYS_ID,l.CYL_SHADE_CODE, l.CYL_SHADE_NAME--,lc.CYL_SHADE_NAME,lc.CYL_SHADE_CODE                    
              FROM  cst_yarn_rm_hdr h
                    ,cst_yarn_calculation c
                    ,cst_yarn_left l
             WHERE CYRH_TYPE = 'Captive Cost'    
               and CYRH_CYC_SYS_ID = CYC_SYS_ID
               and CYC_CYL_SYS_ID = l.CYL_SYS_ID
               and CYC_PRS_TYPE = l.CYL_PRS_TYPE
               and CYC_PRS_TYPE = pPRS_TYPE              
               --and cyrh_cyc_sys_id = '20210885352'  
            ) a
            ,(                  
               SELECT CYRH_SYS_ID,CYL_SHADE_CODE,CYL_SHADE_NAME                    
              FROM  cst_yarn_rm_hdr h
                    ,cst_yarn_rm_captive hc                    
                    ,cst_yarn_left l                   
             WHERE CYRH_TYPE = 'Captive Cost'    
               and CYRH_SYS_ID = CYRC_CYRH_SYS_ID
               and CYRC_YARN_TYPE = 'POY'
               and CYRC_CYL_SYS_ID = CYL_SYS_ID                
            ) b
            where a.CYRH_SYS_ID = b.CYRH_SYS_ID
            and (a.CYL_SHADE_CODE<>b.CYL_SHADE_CODE or a.CYL_SHADE_NAME<>b.CYL_SHADE_NAME)             
       ) loop
       begin
        
        select count(-1) into vTmp 
        from cst_yarn_left
        where CYL_SHADE_CODE = recRM.CYL_SHADE_CODE
        and  CYL_SHADE_NAME = recRM.CYL_SHADE_NAME
        and CYL_TYPE = 'POY'
        and CYL_PRS_TYPE = pPRS_TYPE;
        
        if vTmp = 1 then
            select CYL_SYS_ID,CYL_LEFT_NO into vCYRC_CYL_SYS_ID,vCYRC_LEFT_NO 
            from cst_yarn_left
            where CYL_SHADE_CODE = recRM.CYL_SHADE_CODE
            and  CYL_SHADE_NAME = recRM.CYL_SHADE_NAME
            and CYL_TYPE = 'POY'
            and CYL_PRS_TYPE = pPRS_TYPE;
        else 
            vCYRC_CYL_SYS_ID := null;
            vCYRC_LEFT_NO  := null;
        end if; 
        
        update cst_yarn_rm_captive
            set CYRC_CYL_SYS_ID=vCYRC_CYL_SYS_ID, CYRC_LEFT_NO=vCYRC_LEFT_NO
        where CYRC_CYRH_SYS_ID = recRM.CYRH_SYS_ID;
                      
       end;
       end loop;         
       end;
       
       -- update PTY
        declare
            vCst_Yarn_Left Cst_Yarn_Left%rowtype;
        BEGIN
           FOR recdt IN (SELECT cyc_sys_id, cyl_type,cyl_sys_id                             --,*
                           FROM cst_yarn_calculation c, cst_yarn_left l
                          WHERE c.cyc_top_no = 55
                            AND c.cyc_cyl_sys_id = l.cyl_sys_id
                            AND l.cyl_type = 'PTY'
                            AND cyl_prs_type = cyc_prs_type
                            AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
                            AND (   cyc_data_value IS NULL
                                 OR cyc_data_value NOT LIKE 'PTY%'
                                )                                
                               )
           LOOP                         
            vCst_Yarn_left := null;
                    begin
                                
              select * into vCst_Yarn_left 
              from cst_yarn_left
              where  CYL_MARKETING_COST_LINK in ( 
              select CYC_DATA_VALUE 
              from cst_yarn_calculation
              where cyc_cyl_sys_id = recdt.cyl_sys_id
              and cyc_top_no = 20      
              );
                                
                                
            exception
                when no_data_found then
                    vCst_Yarn_left := null;
                when too_many_rows then
                    vCst_Yarn_left := null;
            end ;
        					
            if vCst_Yarn_left.cyl_sys_id is not null then
                update CST_YARN_RM_CAPTIVE
                    set CYRC_CYL_SYS_ID = vCst_Yarn_left.CYL_SYS_ID
                            ,CYRC_LEFT_NO = vCst_Yarn_left.CYL_LEFT_NO
                where CYRC_CYRH_SYS_ID in (
                select CYRH_SYS_ID
                from CST_YARN_RM_HDR
                where cyrh_cyc_sys_id = recdt.CYC_SYS_ID
                );
            end if;             
              
           END LOOP;
        END;       
       -- Update PTY                  
   end pRefrshValParam;
   
   function fGetMbHeadDt(pCMBH_SYS_ID varchar2) return CST_MST_BATCH_HEAD%rowtype is
    vDt CST_MST_BATCH_HEAD%rowtype;
   begin
    begin
       select * into vDt
        from CST_MST_BATCH_HEAD
        where CMBH_SYS_ID = pCMBH_SYS_ID;
    exception
        when no_data_found then
            vDt := null;    
    end;
    
    return vDt;
   end fGetMbHeadDt;
   
   procedure pLoadMbMstToSpin(pBATCH_SPIN CST_MST_BATCH_SPIN%rowtype,pErrMsg out varchar2) is
   begin
        pErrMsg := null;
        insert into CST_MST_BATCH_SPIN values pBATCH_SPIN ;
        
        commit;
   exception
    when others then
        pErrMsg := 'ERROR : When Load MB Master into MB Spinning '||sqlerrm;
   end pLoadMbMstToSpin;
   
   procedure updYarnPrsMbSpin
                (pCMBS_SYS_ID varchar2
                 ,pCMBS_CMBH_SYS_ID varchar2
                 ,pCMBS_CODE varchar2) is
   begin
    for recDt in (
        select *
        from CST_YARN_M_B_S_MST
        where CYMBMS_CMBS_SYS_ID = pCMBS_SYS_ID
        ) loop
            if  nvl(recDt.CYMBMS_CMBS_CMBH_SYS_ID,'NULL') <> nvl(pCMBS_CMBH_SYS_ID,'NULL') then
                update CST_YARN_M_B_S_MST
                    set CYMBMS_CMBS_CMBH_SYS_ID = pCMBS_CMBH_SYS_ID
                where CYMBMS_CMBS_SYS_ID = recDt.CYMBMS_CMBS_SYS_ID; 
            end if;
            if  nvl(recDt.CYMBMS_CMBS_CODE,'NULL') <> nvl(pCMBS_CODE,'NULL') then
                update CST_YARN_M_B_S_MST
                    set CYMBMS_CMBS_CODE = pCMBS_CODE
                where CYMBMS_CMBS_SYS_ID = recDt.CYMBMS_CMBS_SYS_ID; 
            end if;            
    end loop;
   end ;
   
  function fIsCstShadeCode(pShadeCode varchar2,pShadeName varchar2) return boolean is
  	vTmp number;
  begin
  	select 'x' into vTmp from CST_YARN_LEFT
  	where CYL_SHADE_CODE = pShadeCode
  	and CYL_SHADE_NAME = pShadeName;
  exception
  	when no_data_found then
  		return false;
  	when others then
  		return true;
  end fIsCstShadeCode;
  
  function fIsProdValuation(pCYL_SYS_ID varchar2) return boolean is
  	vTmp number;
  begin
    select 'x' into vtmp
    from mgtapps.CST_YARN_LEFT            
    where CYL_SYS_ID = pCYL_SYS_ID
    and CYL_IS_VALUATION = 'Y';
  exception
  	when no_data_found then
  		return false;
  	when others then
  		return true;
  end fIsProdValuation;  
  
  function fGetRM_PTY_BO(pCYL_SYS_ID varchar2) return varchar2 is
    vTmp number;
    vCylSysId varchar2(30):=null;
    vCylSysId_Reff varchar2(30):=null;
    vTotLp number;
  begin
    -- cek in Data RM 55
    -- cst_lvl_left_prod
    vCylSysId_Reff := pCYL_SYS_ID;
    begin
        select 'x' into vTMp
        from cst_lvl_left_prod
        where CLLP_CYL_SYS_ID_REFF = pCYL_SYS_ID;
    exception
        when no_data_found then
            null;
        when others then
        for rec in (
            SELECT CLLP_CYL_SYS_ID, CLLP_CYL_SYS_ID_REFF
              FROM  mgtapps.cst_lvl_left_prod a
                    , mgtapps.cst_yarn_left b
             WHERE     A.CLLP_CYL_SYS_ID = B.CYL_SYS_ID
                   AND B.CYL_TYPE = 'PTY BO'
                   AND CLLP_CYL_SYS_ID_REFF = vCylSysId_Reff
        ) loop
            vCylSysId := rec.CLLP_CYL_SYS_ID;
        end loop;    
    end;
    -- cek in Data RM 55
    
    -- cek in Normal RM(using PTY BO type)
    if vCylSysId is null then
        vTotLp := 1;
        loop 
            for recCk in (

                select c.*,d.*
                from   
                    mgtapps.cst_yarn_calculation a
                    ,mgtapps.cst_yarn_rm_hdr b
                    ,mgtapps.cst_yarn_rm_captive c
                    ,mgtapps.cst_yarn_left d
                where A.CYC_CYL_SYS_ID = vCylSysId_Reff--'2022122630234'
                and A.CYC_TOP_NO = 20
                and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                and B.CYRH_SYS_ID = C.CYRC_CYRH_SYS_ID
                and CYRC_CYL_SYS_ID = CYL_SYS_ID
            
            )
            loop
                vCylSysId_Reff := null;
                if recCk.CYL_TYPE = 'PTY BO' then
                    vCylSysId := recCk.cyl_sys_id;                
                else
                    vCylSysId_Reff := recCk.cyl_sys_id;                
                end if;
                
            end loop;
            exit when vCylSysId_Reff is null or vTotLp = 100;
            vTotLp := vTotLp + 1;
        end loop;
    end if;
    -- cek in Normal RM(using PTY BO type)
    
    -- cek in Normal RM(using not PTY BO type)
    if vCylSysId is null then
        for recCk in (
            
            select a.CLLP_LEFT_NO,CYC_CYL_SYS_ID
            from (
                    select a.* 
                    from (
                    select b.* from cst_yarn_left a,cst_lvl_left_prod b
                    where a.CYL_SYS_ID=pCYL_SYS_ID --'2025101735179'
                    and a.CYL_SYS_ID=b.CLLP_CYL_SYS_ID_REFF
                    order by cllp_seq_lvl desc
                    ) a where rownum=1  
                 ) a,cst_yarn_calculation b,cst_yarn_rm_hdr c,cst_yarn_rm_dtl d
            where a.CLLP_LEFT_NO=b.CYC_LEFT_NO 
            and b.CYC_PRS_TYPE='20210800119'
            and b.CYC_TOP_NO=55 and b.CYC_FORMULA_TYPE='Raw_Material'
            and b.CYC_SYS_ID=c.CYRH_CYC_SYS_ID
            and c.CYRH_TYPE='Store Rate' and c.CYRH_SYS_ID=d.CYRD_CYRH_SYS_ID
            and d.CYRD_TYPE_DATA = 'From Group Item MKT Rate'

        )
        loop
            vCylSysId := recCk.CYC_CYL_SYS_ID;                
        end loop;
    end if;
    -- cek in Normal RM(using not PTY BO type)
    return vCylSysId;
  end fGetRM_PTY_BO;
  
  function fIs_Final_Setup(pCYL_SYS_ID varchar2) return varchar2 is
    vRtrn varchar2(1):='N';
  begin
    for recDt in (select * from cst_yarn_left where CYL_SYS_ID = pCYL_SYS_ID) loop
        vRtrn := recDt.CYL_IS_FINAL_SETUP;
    end loop;
    return vRtrn;
  end fIs_Final_Setup;
end pkg_yarn_calculation; 
/
