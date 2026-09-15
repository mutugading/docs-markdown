CREATE OR REPLACE package body MGTAPPS.PkgFormulaYarn is
    vCmnPOYDenier number;
    vCmnDTY_prd number;
    vSpinPwrMnth number;
    vSpinMnPwrMnth number;
    vSpinOvrhdsMnth number;
    vSpinCnsSprsMnth number;
    vSpinCstMnth number;
    
    procedure getParam is
    begin
        vCmnPOYDenier       := fGetParamDataVal('20210900147');
        vCmnDTY_prd         := fGetParamDataVal('20210900149');
        vSpinPwrMnth        := fGetParamDataVal('20210900151');
        vSpinMnPwrMnth      := fGetParamDataVal('20210900152');
        vSpinOvrhdsMnth     := fGetParamDataVal('20210900153');
        vSpinCnsSprsMnth    := fGetParamDataVal('20210900154');
        
        vSpinCstMnth        := nvl(vCmnPOYDenier,0)+nvl(vSpinPwrMnth,0)+nvl(vSpinMnPwrMnth,0)+nvl(vSpinOvrhdsMnth,0)+nvl(vSpinCnsSprsMnth,0);
    end;
    
    function fPoyPower_87(pCycSysId varchar2) return number is
        vCstYarnLeft mgtapps.Cst_Yarn_Left%rowtype:=null;
        vCstYarnCalc mgtapps.Cst_Yarn_Calculation%rowtype:=null;
        vCstMstMachine mgtapps.CST_MST_MACHINE%rowtype:=null;

        vDenierPoy number;
        vRslt number; 
    begin
        begin
            vCstYarnLeft := null;    
        
            select l.* 
            into vCstYarnLeft 
            from    mgtapps.Cst_Yarn_Left l
                    ,mgtapps.Cst_Yarn_Calculation c
            where l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
            and CYC_SYS_ID = pCycSysId
            and l.CYL_TYPE = 'POY'
            
            ;
        exception
            when no_data_found then
                vCstYarnLeft := null;
        end;
        
        vRslt := null;
        
        if vCstYarnLeft.cyl_sys_id is not null then
            getParam;
            
            -- get Denier POY costing 13             
            begin
                select * into vCstYarnCalc 
                from mgtapps.cst_yarn_calculation
                where cyc_cyl_sys_id = vCstYarnLeft.cyl_sys_id
                and CYC_top_no = 14--13 at 2022-07-12 change into TOP No 14
                ;
            exception
                when no_data_found then
                     vCstYarnCalc := null;
            --vDenierPoy
            end;
            
            -- get machine mst data
            begin
                select * into vCstMstMachine from mgtapps.CST_MST_MACHINE
                where CMM_MACHINE_CODE = vCstYarnLeft.CYL_MACHINE_CODE;
            exception
                when no_Data_found then
                    vCstMstMachine := null;
            end;
            
            -- Spin Pwr Mnth/ DTY prod * Common POY Denier
            vRslt := nvl(vSpinPwrMnth,0) / nvl(vCmnDTY_prd,0) * nvl(vCmnPOYDenier,0);
            
            vRslt := vRslt / nvl(vCstYarnCalc.CYC_DATA_VALUE,0) * nvl(vCstMstMachine.CMM_WEIGHTAGE,1);
            
            return vRslt; 
        end if;
                                
    end fPoyPower_87;    

    function fPoyManPower_88(pCycSysId varchar2) return number is
        vCstYarnLeft mgtapps.Cst_Yarn_Left%rowtype:=null;
        vCstYarnCalc mgtapps.Cst_Yarn_Calculation%rowtype:=null;
        vCstMstMachine mgtapps.CST_MST_MACHINE%rowtype:=null;

        vDenierPoy number;
        vRslt number; 
    begin
        begin
            vCstYarnLeft := null;    
        
            select l.* 
            into vCstYarnLeft 
            from    mgtapps.Cst_Yarn_Left l
                    ,mgtapps.Cst_Yarn_Calculation c
            where l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
            and CYC_SYS_ID = pCycSysId
            and l.CYL_TYPE = 'POY'
            
            ;
        exception
            when no_data_found then
                vCstYarnLeft := null;
        end;
        
        vRslt := null;
        
        if vCstYarnLeft.cyl_sys_id is not null then
            getParam;
            
            -- get Denier POY costing 13 
            begin
                select * into vCstYarnCalc 
                from mgtapps.cst_yarn_calculation
                where cyc_cyl_sys_id = vCstYarnLeft.cyl_sys_id
                and CYC_top_no = 14--13 at 2022-07-12 change into TOP No 14
                ;
            exception
                when no_data_found then
                     vCstYarnCalc := null;
            --vDenierPoy
            end;
            
            -- get machine mst data
            begin
                select * into vCstMstMachine from mgtapps.CST_MST_MACHINE
                where CMM_MACHINE_CODE = vCstYarnLeft.CYL_MACHINE_CODE;
            exception
                when no_Data_found then
                    vCstMstMachine := null;
            end;
            
            -- manPwr/ DTY prod * Common POY Denier
            vRslt := nvl(vSpinMnPwrMnth,0) / nvl(vCmnDTY_prd,0) * nvl(vCmnPOYDenier,0);
            
            vRslt := vRslt / nvl(vCstYarnCalc.CYC_DATA_VALUE,0) * nvl(vCstMstMachine.CMM_WEIGHTAGE,1);
            
            return vRslt; 
        end if;
                                
    end fPoyManPower_88;
    
    function fPoyOverheads_89(pCycSysId varchar2) return number is
        vCstYarnLeft mgtapps.Cst_Yarn_Left%rowtype:=null;
        vCstYarnCalc mgtapps.Cst_Yarn_Calculation%rowtype:=null;
        vCstMstMachine mgtapps.CST_MST_MACHINE%rowtype:=null;

        vDenierPoy number;
        vRslt number; 
    begin
        begin
            vCstYarnLeft := null;    
        
            select l.* 
            into vCstYarnLeft 
            from    mgtapps.Cst_Yarn_Left l
                    ,mgtapps.Cst_Yarn_Calculation c
            where l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
            and CYC_SYS_ID = pCycSysId
            and l.CYL_TYPE = 'POY'
            
            ;
        exception
            when no_data_found then
                vCstYarnLeft := null;
        end;
        
        vRslt := null;
        
        if vCstYarnLeft.cyl_sys_id is not null then
            getParam;
            
            -- get Denier POY costing 13 
            begin
                select * into vCstYarnCalc 
                from mgtapps.cst_yarn_calculation
                where cyc_cyl_sys_id = vCstYarnLeft.cyl_sys_id
                and CYC_top_no = 14--13 at 2022-07-12 change into TOP No 14
                ;
            exception
                when no_data_found then
                     vCstYarnCalc := null;
            --vDenierPoy
            end;
            
            -- get machine mst data
            begin
                select * into vCstMstMachine from mgtapps.CST_MST_MACHINE
                where CMM_MACHINE_CODE = vCstYarnLeft.CYL_MACHINE_CODE;
            exception
                when no_Data_found then
                    vCstMstMachine := null;
            end;
            
            -- manPwr/ DTY prod * Common POY Denier
            vRslt := nvl(vSpinOvrhdsMnth,0) / nvl(vCmnDTY_prd,0) * nvl(vCmnPOYDenier,0);
            
            vRslt := vRslt / nvl(vCstYarnCalc.CYC_DATA_VALUE,0) * nvl(vCstMstMachine.CMM_WEIGHTAGE,1);
            
            return vRslt; 
        end if;
                                
    end fPoyOverheads_89;
    
    function fPoyConsSprs_90(pCycSysId varchar2) return number is
        vCstYarnLeft mgtapps.Cst_Yarn_Left%rowtype:=null;
        vCstYarnCalc mgtapps.Cst_Yarn_Calculation%rowtype:=null;
        vCstMstMachine mgtapps.CST_MST_MACHINE%rowtype:=null;

        vDenierPoy number;
        vRslt number; 
    begin
        begin
            vCstYarnLeft := null;    
        
            select l.* 
            into vCstYarnLeft 
            from    mgtapps.Cst_Yarn_Left l
                    ,mgtapps.Cst_Yarn_Calculation c
            where l.CYL_SYS_ID = c.CYC_CYL_SYS_ID
            and CYC_SYS_ID = pCycSysId
            and l.CYL_TYPE = 'POY'
            
            ;
        exception
            when no_data_found then
                vCstYarnLeft := null;
        end;
        
        vRslt := null;
        
        if vCstYarnLeft.cyl_sys_id is not null then
            getParam;
            
            -- get Denier POY costing 13 
            begin
                select * into vCstYarnCalc 
                from mgtapps.cst_yarn_calculation
                where cyc_cyl_sys_id = vCstYarnLeft.cyl_sys_id
                and CYC_top_no = 14--13 at 2022-07-12 change into TOP No 14
                ;
            exception
                when no_data_found then
                     vCstYarnCalc := null;
            --vDenierPoy
            end;
            
            -- get machine mst data
            begin
                select * into vCstMstMachine from mgtapps.CST_MST_MACHINE
                where CMM_MACHINE_CODE = vCstYarnLeft.CYL_MACHINE_CODE;
            exception
                when no_Data_found then
                    vCstMstMachine := null;
            end;
            
            -- manPwr/ DTY prod * Common POY Denier
            vRslt := nvl(vSpinCnsSprsMnth,0) / nvl(vCmnDTY_prd,0) * nvl(vCmnPOYDenier,0);
            
            vRslt := vRslt / nvl(vCstYarnCalc.CYC_DATA_VALUE,0) * nvl(vCstMstMachine.CMM_WEIGHTAGE,1);
            
            return vRslt; 
        end if;                                
    end fPoyConsSprs_90;
    
    function fOPU_prsn(pCyl_Sys_id varchar2) return varchar2 is
        vReturn varchar2(300);
    begin
        SELECT  opu.cyc_data_value||' / '||oil.cyc_data_value
        into vReturn
          FROM (SELECT 1 dtno
                  FROM DUAL) a,
               (SELECT 1 dtno, c.cyc_data_value || '%' cyc_data_value
                  FROM mgtapps.cst_yarn_calculation c
                 WHERE cyc_cyl_sys_id = pCyl_Sys_id--'20210500130'                    --pCYC_SYS_ID
                       AND cyc_top_no = 22) opu,
               (SELECT 1 dtno, c.cyc_data_value  cyc_data_value
                  FROM mgtapps.cst_yarn_calculation c
                 WHERE cyc_cyl_sys_id = pCyl_Sys_id--'20210500130'                    --pCYC_SYS_ID
                       AND cyc_top_no = 60) oil
         WHERE a.dtno = opu.dtno(+)
         and a.dtno = oil.dtno(+);
         
         return vReturn;
    exception
        when no_data_found then 
            return null;         
    end fOPU_prsn;
    
    function fGetCustomerProduct(pCyc_Sys_id varchar2) return varchar2 is
        vRtn varchar2(32767):=null;
    begin
        for recDt in (
            select distinct b.* 
            from    cst_yarn_calculation c
                    ,cst_yarn_left l 
                    ,cst_yarn_left_cust a
                    ,cst_mst_cust_data b                                  
            where CYC_SYS_ID = pCyc_Sys_id
            and CYLC_CMCD_SYS_ID = CMCD_SYS_ID
            and cyc_cyl_sys_id = cyl_sys_id
            and cyl_sys_id = cylc_cyl_sys_id
        ) loop
            if vRtn is null then
                vRtn := recdt.CMCD_NAME;
            elsif vRtn is not null then
                vRtn := vRtn||', '||recdt.CMCD_NAME;
            end if;
        end loop;
        return vRtn;
    end fGetCustomerProduct;     
    
    function fGetSuperbaShadeName(pCyc_Sys_id varchar2) return varchar2 is
        vRtn varchar2(32767):=null;
    begin
        for recDt in (
            select a.mpd_name_1 
            from    mgtapps.mst_param_data a
                    ,mgtapps.cst_yarn_left b
            where mpd_mpdk_key = 'SP COST SUPERBA'
            and A.MPD_NAME = B.CYL_SHADE_CODE
            and cyl_sys_id = pCyc_Sys_id
            and rownum = 1
        ) loop
            vRtn := recDt.mpd_name_1;
        end loop;
    
        return vRtn;
    end fGetSuperbaShadeName;    
    
    function fGetSoftnerCost(pCyl_Sys_id varchar2) return varchar2 is
        vRtn varchar2(32767):=null;
    begin
        for recDt in (
        
            select mpd_value 
            from   mgtapps.cst_yarn_left b
                    ,mgtapps.mst_param_data c
            where b.cyl_sys_id = pCyl_Sys_id
            and c.mpd_mpdk_key = 'SOFTNER_COST_SUPERBA'
            and B.CYL_PRODUCT_QUALITY=c.MPD_NAME
            
        
        ) loop
            vRtn := recDt.mpd_value;
        end loop;
    
        return vRtn;
    end fGetSoftnerCost;    
end;
/
