CREATE OR REPLACE package body MGTAPPS.pkg_yarn_marketing is
    function fGet_POY_SysId_From_Captive(pCyl_Sys_Id varchar2) return varchar2 is
        vTotLp number:=20; vLp number:=0;
        vCyl_Sys_Id_Ck varchar2(30); vCYRC_YARN_TYPE varchar2(30);
    begin
        vCyl_Sys_Id_Ck := pCyl_Sys_Id;
        loop        
            for recDt in (                
                select d.* 
                from cst_yarn_left a 
                     ,cst_yarn_calculation b
                     ,cst_yarn_rm_hdr c
                     ,cst_yarn_rm_captive d
                where cyl_sys_id = vCyl_Sys_Id_Ck
                and a.cyl_sys_id=b.cyc_cyl_sys_id
                and b.cyc_top_no=55
                and b.CYC_SYS_ID=c.CYRH_CYC_SYS_ID
                and c.CYRH_TYPE='Captive Cost'
                and c.CYRH_SYS_ID=d.CYRC_CYRH_SYS_ID                
            ) loop
                vCYRC_YARN_TYPE :=  recDt.CYRC_YARN_TYPE;
                vCyl_Sys_Id_Ck  :=  recDt.CYRC_CYL_SYS_ID;
                if vCYRC_YARN_TYPE = 'POY' then
                    exit;
                end if;
            end loop;                        
            exit when vTotLp = vLp;
            vLp := vLp + 1;
        end loop;              
        if vCYRC_YARN_TYPE = 'POY' then 
            return vCyl_Sys_Id_Ck;
        else
            return null;
        end if;
    end fGet_POY_SysId_From_Captive;         
    function fGet_POY_SysId(pCyl_Sys_Id varchar2) return varchar2 is
        vPOY_SysId varchar2(30);
    begin    
        vPOY_SysId := fGet_POY_SysId_From_Captive(pCyl_Sys_Id);
        if vPOY_SysId is null then
            null;
        end if;
        return vPOY_SysId;
    end fGet_POY_SysId;

    function getPrdFowarding return varchar2 is
        vRtrn varchar2(300):= null;
    begin
        for rec in (
            select PARAM_VALUE GET_DATA from MST_PARAMS where PARAM_ID = 'CST_PRODUCT_FOWARDING'
        ) loop
            vRtrn := rec.GET_DATA;
        end loop;    
        return vRtrn;
    end getPrdFowarding;
    function fGet_CylSysId(pWhereItem varchar2,pWhereValue varchar2) return varchar2 is        
        TYPE DtCur  IS REF CURSOR;
        vDtCur    DtCur;
        vSql varchar2(32767);
        vDt varchar2(32767):=NULL;
    BEGIN
    
        vSql := 'SELECT CYL_SYS_ID FROM   mgtapps.cst_mst_yarn y 
                        ,mgtapps.cst_yarn_left l
                        ,mgtapps.cst_yarn_calculation_cur cc
                WHERE cycc_cyl_sys_id = cyl_sys_id
                AND cyl_cmy_sys_id = cmy_sys_id
                AND cyl_prs_type = cycc_prs_type
                and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt 
                ';
        vSql := vSql||' AND upper('||pWhereItem||') = upper('''||pWhereValue||''')';
        -- Open cursor & specify bind argument in USING clause:
        OPEN vDtCur FOR vsql;
        -- Fetch rows from result set one at a time:
        LOOP
            FETCH vDtCur INTO vDt;
        EXIT WHEN vDtCur%NOTFOUND;
        END LOOP;

        -- Close cursor:
        CLOSE vDtCur;
        
        return vDt;
    end fGet_CylSysId;
    
    function fGetCylTypeByMktCstLnk(pMktCstLnk varchar2) return varchar is
        vCylTypeMaterial varchar2(300);
    begin
        begin
            select distinct CYL_TYPE into vCylTypeMaterial 
            from mgtapps.cst_yarn_left yl
            where upper(CYL_MARKETING_COST_LINK) = upper(pMktCstLnk);
        exception
            when too_many_rows then   
                 vCylTypeMaterial := null;                                                   
        end;
        
        return vCylTypeMaterial;
    end;

    function fGetCylTypeByCylSysId(pCylSysId varchar2) return varchar is
        vCylTypeMaterial varchar2(300);
    begin
        begin
            select distinct CYL_TYPE into vCylTypeMaterial 
            from mgtapps.cst_yarn_left yl
            where CYL_SYS_ID = pCylSysId;
        exception
            when too_many_rows then   
                 vCylTypeMaterial := null;                                                   
        end;
        
        return vCylTypeMaterial;
    end;    

    function fGetVal_Calc_Cur(pCylSysId varchar2,pItemName varchar2) return varchar2 is
        TYPE DtCur  IS REF CURSOR;
        vDtCur    DtCur;
        vSql varchar2(32767);
        vDt varchar2(32767):=NULL;
    begin
       
         vSql := '
         SELECT '||pItemName||'
          FROM mgtapps.cst_mst_yarn y,
               mgtapps.cst_yarn_left l,
               mgtapps.cst_yarn_calculation_cur cc
         WHERE cycc_cyl_sys_id = cyl_sys_id
           AND cyl_cmy_sys_id = cmy_sys_id
           AND cyl_prs_type = cycc_prs_type
           and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
           AND cyl_sys_id = '||pCylSysId;
                  
       OPEN vDtCur FOR vsql;
        -- Fetch rows from result set one at a time:
        LOOP
            FETCH vDtCur INTO vDt;
        EXIT WHEN vDtCur%NOTFOUND;
        END LOOP;

        -- Close cursor:
        CLOSE vDtCur;
        
         return vDt;
    end fGetVal_Calc_Cur;
    

    function fGetPOYCurDt(pCst_Yarn_Left mgtapps.Cst_Yarn_Left%rowtype,pCYCRL_SOURCE_QUERY varchar2) return varchar2 is
        vReturn varchar2(1000); vTot_loop number;         
    begin
        if pcst_yarn_left.cyl_type = 'POY' then
            vReturn := null;
        else
            declare
                vMrktLink_Poy varchar2(1000);
                vCylSysId_Poy varchar2(1000);
                vCylSysId_Cek varchar2(1000);
            begin   
                vCylSysId_Cek := pcst_yarn_left.cyl_sys_id;       
                vTot_loop :=0; 
                loop 
                    vMrktLink_Poy := fGetVal_Calc_Cur(vCylSysId_Cek,'CYCC_TOP_20_DATA_VALUE');--get row material
                    exit when nvl(vMrktLink_Poy,'NULL')='NULL' or upper(vMrktLink_Poy) like 'POY%' or vTot_loop > 20;
                    vCylSysId_Cek := fGet_CylSysId('CYL_MARKETING_COST_LINK',vMrktLink_Poy);
                    vTot_loop :=vTot_loop + 1;
                end loop;
                
                vCylSysId_Poy:= fGet_CylSysId('CYL_MARKETING_COST_LINK',vMrktLink_Poy);
                vReturn := fGetVal_Calc_Cur(vCylSysId_Poy,pCYCRL_SOURCE_QUERY);                
                return vReturn;
            end;
        end if;
        return vReturn;
    end;
    
    function fGetDataProduct(
            pCyl_Sys_Id varchar2
            ,pCYCRL_SOURCE_TYPE varchar2
            ,pCYCRL_SOURCE_QUERY varchar2
    ) return varchar2 is
        vReturn varchar2(1000):='NULL';
        vcst_yarn_left mgtapps.cst_yarn_left%rowtype;
    begin
        vcst_yarn_left := null;
        begin
            select * into vcst_yarn_left 
            from mgtapps.cst_yarn_left
            where cyl_sys_id = pCyl_Sys_Id;
        exception
            when no_data_found then
                null; 
        end;        
        if upper(pCYCRL_SOURCE_TYPE) in ('MB NAME','DOZING','MB RATE','CHIPS PRICE') then
            if vcst_yarn_left.cyl_sys_id is not null then
                vReturn := fGetPOYCurDt(vcst_yarn_left,pCYCRL_SOURCE_QUERY);
            end if;
        elsif upper(pCYCRL_SOURCE_TYPE) in ('MB NAME','DOZING','MB RATE') then
            null;
        end if;
        
        return vReturn;
    end;
    
    FUNCTION fgetdatacustomer (pcylsysid VARCHAR2)
       RETURN VARCHAR2
    IS
       vrtn   VARCHAR2 (1000);
    BEGIN
       vrtn := NULL;

       FOR rec IN (SELECT distinct cmcd_name customer
                     FROM mgtapps.cst_yarn_left_cust lc,
                          mgtapps.cst_yarn_left l,
                          mgtapps.cst_mst_cust_data cd
                    WHERE lc.cylc_cyl_sys_id = l.cyl_sys_id
                      AND lc.cylc_cmcd_sys_id = cmcd_sys_id
                      and cyl_sys_id = pcylsysid)
       LOOP
          if vrtn is null then
            vrtn := rec.customer;
          else vrtn := vrtn||', '||rec.customer;
          end if;
       END LOOP;
       
       return vrtn;
    END fgetdatacustomer;
    
    function fGetRowMaterial(pCylSysId varchar2) return varchar2 is
        vRtn varchar2(200) := null;
    begin
    
        for recDt in (
            --select CYCC_TOP_20_DATA_VALUE from mgtapps.cst_yarn_calculation_cur
            --where CYCC_CYL_SYS_ID = pCylSysId
            
            select CYC_DATA_VALUE 
            from mgtapps.cst_yarn_calculation
            where CYC_TOP_NO = 20 
            and CYC_CYL_SYS_ID = pCylSysId            
            
        ) loop
            vRtn := recDt.CYC_DATA_VALUE;
        end loop;
                                
        return vRtn;
    end fGetRowMaterial;

    function fGetCylSysId(pMktCstLnk varchar2) return varchar2 is
        vRtn varchar2(200) := null;
    begin
    
        for recDt in (
            select CYL_SYS_ID
            from mgtapps.cst_yarn_left
            where upper(CYL_MARKETING_COST_LINK) = upper(pMktCstLnk)
            and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
        ) loop
            vRtn := recDt.CYL_SYS_ID;
        end loop;
                                
        return vRtn;
    end fGetCylSysId;
    
    function fGetCylSysID_MktCstLnk(pcyl_sys_id varchar2) return varchar2 is
        vMktCstLnk varchar2(300);
        vCylSysID_MktCstLnk varchar2(300);
        vTotLp number;
    begin
        vCylSysID_MktCstLnk := pcyl_sys_id; vTotLp :=0;
        loop
            vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);
            exit when vMktCstLnk like 'POY%' or vMktCstLnk is null or vTotLp > 20;
            vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
            vTotLp :=vTotLp+1;
        end loop;
        vCylSysID_MktCstLnk :=  fGetCylSysId(vMktCstLnk);
        return vCylSysID_MktCstLnk;               
    end fGetCylSysID_MktCstLnk;           
    
    function fGetCylSysID_Rm(pcylsysid varchar2) return varchar2 is
        vCylSysID_MktCstLnk varchar(30);
        vCylTypeMaterial  varchar(30);
        vMktCstLnk varchar(1000);
        vRtn varchar2(300):=null;
        vTot_loop number;
    begin
        for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcylsysid    
        ) loop
            if recDt.CYL_TYPE <> 'POY' then
                vCylSysID_MktCstLnk := pcylsysid; vTot_loop:=0;
                loop
                    vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);                    
                    vCylTypeMaterial := fGetCylTypeByMktCstLnk(vMktCstLnk);                    
                    vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
                    exit when vCylTypeMaterial = 'POY' or vCylTypeMaterial is null or vTot_loop > 50;            
                    vTot_loop := vTot_loop +1;        
                end loop;
            else vCylSysID_MktCstLnk := null;
            end if;        
        end loop;
        if vCylSysID_MktCstLnk is not null then
            vRtn :=vCylSysID_MktCstLnk;
        end if;
        return vRtn;
    end fGetCylSysID_Rm;
    
    function fGetCylSysID_byLvl(pcyl_sys_id varchar2,pTotPrd number,pLvl number) return varchar2 is
        PRAGMA AUTONOMOUS_TRANSACTION;
        vMktCstLnk varchar2(300);
        vCylSysID_MktCstLnk varchar2(300);
        vNo number:=0;
        
        /*TYPE CylSysID_Arr IS TABLE OF varchar2(30) INDEX BY PLS_INTEGER;
        CylSysID_Dt CylSysID_Arr;*/
        vCylTypeMktCstLnk varchar2(30);
        vCLLP_SYS_ID varchar2(30);
        vRtn varchar2(30); 
        verrmsg varchar(1000);
        vTot_loop number;
    begin
        vRtn := null;
        vCLLP_SYS_ID := TO_CHAR (SYSDATE, 'YYYYMMDD')
                        || TO_CHAR (pkg_seq_no.next_value ('CYL_SYS_ID','ADMIN',verrmsg),'fm00000');
    
        --vCylSysID_MktCstLnk := pcyl_sys_id;
        for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcyl_sys_id    
        ) loop
            if recDt.CYL_TYPE = 'POY' then
                vNo := 1;
                --CylSysID_Dt(vNo) := recDt.CYL_SYS_ID;
                insert into mgtapps.CST_LVL_LEFT_PROD(CLLP_SYS_ID,CLLP_LVL_PROD,CLLP_CYL_SYS_ID,CLLP_CREATED_TIMESTAMP)
                values (vCLLP_SYS_ID,1,recDt.CYL_SYS_ID,sysdate);commit;             
            else
                vMktCstLnk := recDt.CYL_MARKETING_COST_LINK;
                vCylTypeMktCstLnk := fGetCylTypeByMktCstLnk(vMktCstLnk);    
                vTot_loop :=0;
                loop                    
                    vNo :=  vNo + 1;
                    vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);                    
                    --CylSysID_Dt(pTotPrd - vNo + 1) := vCylSysID_MktCstLnk;
                    insert into mgtapps.CST_LVL_LEFT_PROD(CLLP_SYS_ID,CLLP_LVL_PROD,CLLP_CYL_SYS_ID,CLLP_CREATED_TIMESTAMP)
                    values (vCLLP_SYS_ID,pTotPrd - vNo + 1,vCylSysID_MktCstLnk,sysdate);commit;
                    --exit when vMktCstLnk like 'POY%';
                    exit when vCylTypeMktCstLnk = 'POY' or vTot_loop > 50;
                    vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);
                    vCylTypeMktCstLnk := fGetCylTypeByMktCstLnk(vMktCstLnk);        
                    vTot_loop :=vTot_loop+1;            
                end loop;
            end if;        
            --CylSysID_Dt(pTotPrd - vNo + 1) := vCylSysID_MktCstLnk;           
        end loop;    
    
        select CLLP_CYL_SYS_ID into vRtn
        from mgtapps.CST_LVL_LEFT_PROD
        where CLLP_SYS_ID = vCLLP_SYS_ID
        and CLLP_LVL_PROD = pLvl;
        
        delete from mgtapps.CST_LVL_LEFT_PROD  where CLLP_SYS_ID = vCLLP_SYS_ID;commit;
        
        return vRtn;               
    end fGetCylSysID_byLvl;
    
    function fGetDeepMaterial(pcyl_sys_id varchar2) return number is
        vMktCstLnk varchar2(300);
        vCylSysID_MktCstLnk varchar2(300);
        vNo number:=0;      
        vCylTypeMaterial varchar2(30);  
        vTot_loop number;
    begin
        for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcyl_sys_id    
        ) loop
            if recDt.CYL_TYPE = 'POY' then
                vNo := 1;
            else
                vCylSysID_MktCstLnk := pcyl_sys_id;
                vTot_loop := 0;
                loop
                    vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);                    
                    vCylTypeMaterial := fGetCylTypeByMktCstLnk(vMktCstLnk);                    
                    vNo :=  vNo + 1;
                    --exit when vMktCstLnk like 'POY%';
                    exit when vCylTypeMaterial = 'POY' or vCylTypeMaterial is null or vTot_loop >20 ;
                    vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
                    vTot_loop := vTot_loop + 1;
                end loop;
                vNo :=  vNo + 1;
            end if;        
        end loop;
        return vNo;               
    end fGetDeepMaterial;
    
    FUNCTION fgetmbname (pcylsysid VARCHAR2)
       RETURN VARCHAR2
    IS
       vrtn   VARCHAR2 (1000);
       vCylSysID_MktCstLnk varchar2(300);
       vTotPoy number;
       function fGet(pcyl_sys_id varchar2) return varchar2 is
        vTmp number;
       begin
        select 'x' into vTmp
        from mgtapps.cst_yarn_calculation_cur
        where CYCC_CYL_SYS_ID = pcyl_sys_id;
       exception
        when no_data_found then
            return null;
        when others then 
        for recDt in (
           
            select CYCC_TOP_64_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
            where CYCC_CYL_SYS_ID = pcyl_sys_id
                 
        ) loop
            vrtn := recDt.val;
        end loop;  
        return vrtn;
       end;
    BEGIN
        vrtn := NULL;
        
        SELECT count(-1) into vTotPoy 
        FROM cst_lvl_left_prod
        WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
        AND CLLP_TYPE = 'POY';
        
        if vTotPoy = 0 then
            for recYL in (

                select * from mgtapps.cst_yarn_left            
                where cyl_sys_id = pcylsysid
                
            ) loop
                if recYL.cyl_type = 'POY' then
                begin
                    vRtn := fGet(recYL.cyl_sys_id);       
                end;
                else 
                    if recYL.cyl_type = 'MELANGE' then
                        null;
                    else
                        vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                        vRtn := fGet(vCylSysID_MktCstLnk);
                    end if;       
                end if;          
            end loop;
        else
            if vTotPoy = 1 then
            
                SELECT CLLP_CYL_SYS_ID into vCylSysID_MktCstLnk
                FROM cst_lvl_left_prod
                WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
                AND CLLP_TYPE = 'POY';           
                
                vRtn := fGet(vCylSysID_MktCstLnk);
                     
            end if;
        end if;
        return vrtn;
    END fgetmbname; 
    
    FUNCTION fgetmbname_rm (pcyl_sys_id VARCHAR2)
       RETURN VARCHAR2
    IS
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
        vMktCstLnk varchar2(1000);
        vCylTypeMaterial varchar2(30);
        vTot_loop number;
    BEGIN
        vrtn := NULL;

        for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcyl_sys_id    
        ) loop
            if recDt.CYL_TYPE <> 'POY' then
                vCylSysID_MktCstLnk := pcyl_sys_id;
                vTot_loop := 0;
                loop
                    vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);                    
                    vCylTypeMaterial := fGetCylTypeByMktCstLnk(vMktCstLnk);                    
                    vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
                    exit when vCylTypeMaterial = 'POY' or vCylTypeMaterial is null or vTot_loop > 50;
                    vTot_loop := vTot_loop +1;                    
                end loop;
            else vCylSysID_MktCstLnk := null;
            end if;        
        end loop;
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetmbname (vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    END fgetmbname_rm;    
    
    FUNCTION fgetchip (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
       function fGet(pcyl_sys_id varchar2) return varchar2 is
       begin
            for recDt in (
                select CYCC_TOP_20_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id 
            ) loop
                vrtn := recDt.val;
            end loop;  
            return vrtn;
       end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            if recYL.cyl_type = 'POY' then
                vrtn := fGet(recYL.cyl_sys_id);
            else
                if recYL.cyl_type = 'MELANGE' then
                    null;
                else
                declare
                    vCylSysID_MktCstLnk varchar2(300);
                begin
                    vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                    vRtn := fGet(vCylSysID_MktCstLnk);              
                end;
                end if;
            end if;
        end loop;
       
       return vrtn;
    end fgetchip;
    
    FUNCTION fgetchip_rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
    BEGIN
        vrtn := NULL;
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetchip (vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetchip_rm;
    
    FUNCTION fgetchiprate (pcylsysid VARCHAR2) return varchar2 is
       vrtn   VARCHAR2 (1000);
       vCylSysID_MktCstLnk varchar2(300);
       function fGet(pcyl_sys_id varchar2) return varchar2 is
       begin
            for recDt in (
                /*select to_char(to_number(CYCC_TOP_55_DATA_VALUE),'fm999,999,999.9999') val 
                from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id*/
                select to_char(to_number(CYC_DATA_VALUE),'fm999,999,999.9999') val 
                from mgtapps.cst_yarn_calculation
                where CYC_CYL_SYS_ID = pcyl_sys_id and cyc_TOP_no = 55
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
       end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            if recYL.cyl_type = 'POY' then
            begin
                vrtn := fGet(recYL.cyl_sys_id);                                
            end;
            else 
                if recYL.cyl_type = 'MELANGE' then
                    null;
                else
                    vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                    vRtn := fGet(vCylSysID_MktCstLnk);
                end if;              
            end if;
        end loop;
       
       return vrtn;
    end fgetchiprate;    
       
    FUNCTION fgetchiprate_rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
        --vMktCstLnk varchar2(1000);
        --vCylTypeMaterial varchar2(30);
    BEGIN
        vrtn := NULL;

        /*for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcylsysid    
        ) loop
            if recDt.CYL_TYPE <> 'POY' then
                vCylSysID_MktCstLnk := pcylsysid;
                loop
                    vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);                    
                    vCylTypeMaterial := fGetCylTypeByMktCstLnk(vMktCstLnk);                    
                    vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
                    exit when vCylTypeMaterial = 'POY' or vCylTypeMaterial is null;                    
                end loop;
            else vCylSysID_MktCstLnk := null;
            end if;        
        end loop;*/
        
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetchiprate (vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetchiprate_rm;
        
    FUNCTION fgetRpDoz (pcylsysid VARCHAR2) return varchar2 is
       vrtn   VARCHAR2 (1000);
       vCylSysID_MktCstLnk varchar2(300);
       vTotPoy number;
       function fGet(pcyl_sys_id varchar2) return varchar2 is
       begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_71_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
       end;        
    BEGIN
       vrtn := NULL;

        SELECT count(-1) into vTotPoy 
        FROM cst_lvl_left_prod
        WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
        AND CLLP_TYPE = 'POY';
                
        if vTotPoy = 0 then

            for recYL in (
            
                select * from mgtapps.cst_yarn_left            
                where cyl_sys_id = pcylsysid
                
            ) loop
                if recYL.cyl_type = 'POY' then
                    vrtn := fGet(recYL.cyl_sys_id);  
                else 
                    if recYL.cyl_type = 'MELANGE' then
                        null;
                    else                
                        vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                        vRtn := fGet(vCylSysID_MktCstLnk);
                    end if;
                end if;
            end loop;
            
        else    
            if vTotPoy = 1 then
            
                SELECT CLLP_CYL_SYS_ID into vCylSysID_MktCstLnk
                FROM cst_lvl_left_prod
                WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
                AND CLLP_TYPE = 'POY';           
                
                vRtn := fGet(vCylSysID_MktCstLnk);
            end if;
        end if;
        return vrtn;
    end fgetRpDoz;
    
    FUNCTION fgetRpDoz_Rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
        --vMktCstLnk varchar2(1000);
        --vCylTypeMaterial varchar2(30);
    BEGIN
        vrtn := NULL;
                
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetRpDoz (vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetRpDoz_Rm;    
    
    FUNCTION fgetMbRate (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        vTotPoy number;
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_72_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;
    BEGIN
        vrtn := NULL;

        SELECT count(-1) into vTotPoy 
        FROM cst_lvl_left_prod
        WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
        AND CLLP_TYPE = 'POY';
        
        
        if vTotPoy = 0 then

            for recYL in (
            
                select * from mgtapps.cst_yarn_left            
                where cyl_sys_id = pcylsysid
                
            ) loop
                if recYL.cyl_type = 'POY' then
                    vrtn := fGet(recYL.cyl_sys_id);  
                else 
                    if recYL.cyl_type = 'MELANGE' then
                        null;
                    else            
                        vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                        vRtn := fGet(vCylSysID_MktCstLnk);
                    end if;    
                end if;
            end loop;
        else
        
            if vTotPoy = 1 then

                SELECT CLLP_CYL_SYS_ID into vCylSysID_MktCstLnk
                FROM cst_lvl_left_prod
                WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
                AND CLLP_TYPE = 'POY';           
                
                vRtn := fGet(vCylSysID_MktCstLnk);
            
            end if;
            
        end if;
       
       return vrtn;
    end fgetMbRate;
    
    FUNCTION fgetMbRate_Rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
    BEGIN
        vrtn := NULL;
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetMbRate(vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetMbRate_Rm;
    
    FUNCTION fgetMbCost (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        vTotPoy number;
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_73_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
        vrtn := NULL;
        
        select count(-1) into vTotPoy  
        from cst_lvl_left_prod
        where CLLP_CYL_SYS_ID_REFF = pcylsysid
        and CLLP_TYPE = 'POY';
        
        if vTotPoy > 1 then            
            declare
                vSum_denier number;
                vMbCost number;
                vSum_MbCost number;
            begin
            
                SELECT --cyrm_sec_no,cyl_prs_type,CYRC_LEFT_NO,CYRM_REV_DEN
                        sum(CYRM_REV_DEN)  into vSum_denier
                  FROM (  SELECT cyl_prs_type,f.*
                            FROM mgtapps.cst_lvl_left_prod a,
                                 mgtapps.cst_yarn_left b,
                                 mgtapps.cst_yarn_calculation c,
                                 mgtapps.cst_yarn_rm_hdr d,
                                 mgtapps.cst_yarn_rm_multi f
                           WHERE     CLLP_CYL_SYS_ID_REFF = pcylsysid--'2022101584922'
                                 AND CLLP_TYPE <> 'POY'
                                 AND cllp_cyl_sys_id = cyl_sys_id
                                 AND cyl_sys_id = cyc_cyl_sys_id
                                 AND cyc_top_no = 55
                                 AND CYC_FORMULA_TYPE = 'Raw_Material'
                                 AND cyc_sys_id = cyrh_cyc_sys_id
                                 AND CYRH_TYPE = 'Multi Yarn'
                                 AND cyrh_sys_id = cyrm_cyrh_sys_id
                        ORDER BY cyrm_sec_no) a,
                       (SELECT *
                          FROM mgtapps.cst_yarn_calculation a,
                               mgtapps.cst_yarn_rm_hdr b,
                               mgtapps.cst_yarn_rm_captive c
                         WHERE     cyc_top_no = 55
                               AND CYC_FORMULA_TYPE = 'Raw_Material'
                               AND cyc_sys_id = cyrh_cyc_sys_id
                               AND CYRH_TYPE = 'Captive Cost'
                               AND cyrc_yarn_type = 'POY'
                               AND cyrh_sys_id = cyrc_cyrh_sys_id) b
                 WHERE a.CYRM_CYL_SYS_ID = b.cyc_cyl_sys_id;                      
            
            
                /*select sum(nvl(CYCC_TOP_13_DATA_VALUE,0))  into vSum_denier
                from    mgtapps.cst_lvl_left_prod a
                        ,mgtapps.cst_yarn_calculation_cur b
                where CLLP_CYL_SYS_ID_REFF = pcylsysid
                and CLLP_TYPE = 'POY'
                and CLLP_CYL_SYS_ID = b.CYCC_CYL_SYS_ID;*/

                vSum_MbCost := 0;
                for recDt in (

SELECT cyrm_sec_no,a.cyl_prs_type,CYRC_LEFT_NO,CYRM_REV_DEN,mb_cost
  FROM (SELECT cyrm_sec_no,
               cyl_prs_type,
               CYRC_LEFT_NO,
               CYRM_REV_DEN
          FROM (  SELECT cyl_prs_type, f.*
                    FROM mgtapps.cst_lvl_left_prod a,
                         mgtapps.cst_yarn_left b,
                         mgtapps.cst_yarn_calculation c,
                         mgtapps.cst_yarn_rm_hdr d,
                         mgtapps.cst_yarn_rm_multi f
                   WHERE     CLLP_CYL_SYS_ID_REFF = pcylsysid--'2022101584922'
                         AND CLLP_TYPE <> 'POY'
                         AND cllp_cyl_sys_id = cyl_sys_id
                         AND cyl_sys_id = cyc_cyl_sys_id
                         AND cyc_top_no = 55
                         AND CYC_FORMULA_TYPE = 'Raw_Material'
                         AND cyc_sys_id = cyrh_cyc_sys_id
                         AND CYRH_TYPE = 'Multi Yarn'
                         AND cyrh_sys_id = cyrm_cyrh_sys_id
                ORDER BY cyrm_sec_no) a,
               (SELECT *
                  FROM mgtapps.cst_yarn_calculation a,
                       mgtapps.cst_yarn_rm_hdr b,
                       mgtapps.cst_yarn_rm_captive c
                 WHERE     cyc_top_no = 55
                       AND CYC_FORMULA_TYPE = 'Raw_Material'
                       AND cyc_sys_id = cyrh_cyc_sys_id
                       AND CYRH_TYPE = 'Captive Cost'
                       AND cyrc_yarn_type = 'POY'
                       AND cyrh_sys_id = cyrc_cyrh_sys_id) b
         WHERE a.CYRM_CYL_SYS_ID = b.cyc_cyl_sys_id) a,
       (SELECT CLLP_CYL_SYS_ID,
               cyl_prs_type,
               CLLP_LEFT_NO,
               TO_NUMBER (CYCC_TOP_73_DATA_VALUE) mb_cost
          FROM mgtapps.cst_lvl_left_prod a,
               mgtapps.cst_yarn_calculation_cur b,
               mgtapps.cst_yarn_left c
         WHERE     CLLP_CYL_SYS_ID_REFF = --'2022101584922'          --
         pcylsysid
               AND CLLP_TYPE = 'POY'
               AND CLLP_CYL_SYS_ID = b.CYCC_CYL_SYS_ID
               AND CLLP_CYL_SYS_ID = CYL_SYS_ID) b
 WHERE a.cyl_prs_type = b.cyl_prs_type AND CYRC_LEFT_NO = CLLP_LEFT_NO
                
                    
                ) loop
                    --=G6/(G6+G7)*I6 denier/tot denier * mb cost
                    vMbCost := (recDt.CYRM_REV_DEN/vSum_denier) * nvl(recDt.mb_cost,0);
                    vSum_MbCost := nvl(vSum_MbCost,0) + nvl(vMbCost,0);
                end loop;
                vrtn := nvl(vSum_MbCost,0);
            end;
        else
            if vTotPoy <> 1 then
                for recYL in (
                
                    select * from mgtapps.cst_yarn_left            
                    where cyl_sys_id = pcylsysid
                    
                ) loop
                    if recYL.cyl_type = 'POY' then
                        vrtn := fGet(recYL.cyl_sys_id);
                    else 
                        if recYL.cyl_type = 'MELANGE' then
                            null;
                        else            
                            vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                            vRtn := fGet(vCylSysID_MktCstLnk);
                        end if;
                    end if;
                end loop;
            else
            
                SELECT CLLP_CYL_SYS_ID into vCylSysID_MktCstLnk  
                FROM cst_lvl_left_prod 
                WHERE CLLP_CYL_SYS_ID_REFF = pcylsysid 
                AND CLLP_TYPE = 'POY';
                
                vRtn := fGet(vCylSysID_MktCstLnk);
            
            end if;
        end if;   
       return vrtn;
    end fgetMbCost;
    
    FUNCTION fgetMbCost_Rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
    BEGIN
        vrtn := NULL;
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetMbCost(vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetMbCost_Rm;

    FUNCTION fgetCngOvrLst (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_116_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            if recYL.cyl_type = 'POY' then
                vrtn := fGet(recYL.cyl_sys_id);
            else 
                if recYL.cyl_type = 'MELANGE' then
                    null;
                else            
                    vCylSysID_MktCstLnk := fGetCylSysID_MktCstLnk(recYL.cyl_sys_id);
                    vRtn := fGet(vCylSysID_MktCstLnk);
                end if;
            end if;
        end loop;
       
       return vrtn;
    end fgetCngOvrLst;
    
    FUNCTION fgetCngOvrLst_Rm (pcylsysid VARCHAR2) return varchar2 is
        vrtn varchar2(1000);
        vCylSysID_MktCstLnk varchar2(30):=null;
    BEGIN
        vrtn := NULL;
        vCylSysID_MktCstLnk := fGetCylSysID_Rm(pcylsysid);
        
        if vCylSysID_MktCstLnk is not null then
            vrtn := fgetCngOvrLst(vCylSysID_MktCstLnk);
        end if;

        return vrtn;
    end fgetCngOvrLst_Rm;
    
    FUNCTION fgetQualityLoss (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_104_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fgetQualityLoss;

    FUNCTION fgetFinalExFactoryCost (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_121_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fgetFinalExFactoryCost;
    
    FUNCTION fget_V1 (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_118_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       /*if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
          vrtn := vrtn;
       exception
        when others then
            null;
       end;          
       end if;*/
       
       return vrtn;
    end fget_V1;    
    
    --fget1_5Untl_3Ton
    FUNCTION fget_V2 (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_119_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       /*if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
       exception
        when others then
            null;
       end;          
       end if;*/
       
       return vrtn;
    end fget_V2; 
    
    FUNCTION fget_V3 (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_120_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       /*if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
       exception
        when others then
            null;
       end;          
       end if;*/
       
       return vrtn;
    end fget_V3;
    
    FUNCTION fget_V4 (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_121_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       /*if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
       exception
        when others then
            null;
       end;          
       end if;*/
       
       return vrtn;
    end fget_V4;
    
    FUNCTION fget_V5 (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select to_char(to_number(CYCC_TOP_122_DATA_VALUE),'fm999,999,999.9999') val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       /*if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
       exception
        when others then
            null;
       end;          
       end if;*/
       
       return vrtn;
    end FGET_V5;
    
    FUNCTION fget_PackingType (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_43_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       if vrtn is not null then
       begin
          vrtn := vrtn + to_number(getPrdFowarding);
       exception
        when others then
            null;
       end;          
       end if;
       
       return vrtn;
    end fget_PackingType;
    
    FUNCTION fget_NoOfBobbins (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_44_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_NoOfBobbins;
    
    FUNCTION fget_BobbinWeightAX (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_30_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                --select CYCC_TOP_45_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_BobbinWeightAX;
    
    FUNCTION fget_BoxWeight(pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                --select CYCC_TOP_39_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                select CYCC_TOP_45_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_BoxWeight;
    
    FUNCTION fget_DelPackingName (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_43_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_DelPackingName;  
    
    FUNCTION fget_DelPackBobinRate (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_46_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_DelPackBobinRate;
    
    FUNCTION fget_DelPackBoxRate (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_47_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_DelPackBoxRate;    
    
    FUNCTION fget_DelPackingCost (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_48_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_DelPackingCost;    
    
    FUNCTION fget_IntermigleCost (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_74_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_IntermigleCost;
    
    FUNCTION fget_FixedCost (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_91_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_FixedCost;
    
    FUNCTION fget_McName (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_7_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_McName;
    
    FUNCTION fget_McEff (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_10_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_McEff;
    
    FUNCTION fget_McSpeed (pcylsysid VARCHAR2) return varchar2 is
        vrtn   VARCHAR2 (1000);
        vCylSysID_MktCstLnk varchar2(300);
        function fGet(pcyl_sys_id varchar2) return varchar2 is
        begin
            for recDt in (
                select CYCC_TOP_11_DATA_VALUE val from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pcyl_sys_id
            ) loop
                vrtn := recDt.val;
            end loop;            
            return vrtn;
        end;        
    BEGIN
       vrtn := NULL;

        for recYL in (
        
            select * from mgtapps.cst_yarn_left            
            where cyl_sys_id = pcylsysid
            
        ) loop
            vrtn := fGet(recYL.cyl_sys_id);
        end loop;
       
       return vrtn;
    end fget_McSpeed;
    
    function isUseRmBO(pCYL_SYS_ID varchar2) return varchar2 is
        vIsRmBo varchar2(1):='N';
        vCYL_SYS_ID_cek varchar2(30);
        vCYC_DATA_VALUE varchar2(300);
        vTmp number;  
        vTotLp number;
    begin
        begin
            select 'x' into vTmp
            from mgtapps.cst_yarn_left
            where cyl_sys_id = pCYL_SYS_ID
            and CYL_TYPE = 'SUPERBA';
        exception
            when no_data_found then
                null;
        when others then
            begin
            vCYL_SYS_ID_cek := pCYL_SYS_ID;
            vTotLp := 0;
            loop                    
            
                SELECT CYC_DATA_VALUE into vCYC_DATA_VALUE
                  FROM  mgtapps.cst_yarn_calculation a
                 WHERE CYC_CYL_SYS_ID = vCYL_SYS_ID_cek
                 and CYC_TOP_NO = 20
                 and cyc_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt        
                 ;
                 
                 for rec in (         
                     
                    select MPD_VALUE from mgtapps.mst_param_data
                    where mpd_mpdk_key = 'COSTING TYPE RM BO'
                    and vCYC_DATA_VALUE like MPD_VALUE||'%'
                    and rownum = 1
                   
                 ) loop
                    vIsRmBo := 'Y';
                 end loop;

                 begin
                    select distinct CYL_SYS_ID into vCYL_SYS_ID_cek
                    from mgtapps.cst_yarn_left
                    where upper(CYL_MARKETING_COST_LINK) = upper(vCYC_DATA_VALUE) 
                    and cyl_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt;
                    
                 exception
                    when no_data_found then
                        vCYL_SYS_ID_cek := null;
                 end;
                exit when vCYL_SYS_ID_cek is null or vTotLp >=50 ;
                vTotLp := vTotLp + 1;
            end loop;
            end;
        end; 
        return vIsRmBo;
    end isUseRmBO;
    
    function fGetDeepMaterial_RmBO(pcyl_sys_id varchar2) return number is
        vMktCstLnk varchar2(300);
        vCylSysID_MktCstLnk varchar2(300);
        vNo number:=0;      
        vIsRmBo varchar2(30);  
    begin
        for recDt in (
            select * from mgtapps.cst_yarn_left l
            where l.cyl_sys_id = pcyl_sys_id    
        ) loop
            vCylSysID_MktCstLnk := pcyl_sys_id;
            loop
                vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);                    
                vNo :=  vNo + 1;

                for rec in (         
                 
                select MPD_VALUE from mgtapps.mst_param_data
                where mpd_mpdk_key = 'COSTING TYPE RM BO'
                and vMktCstLnk like MPD_VALUE||'%'
                and rownum = 1
               
                ) loop
                    vIsRmBo := 'Y';
                end loop;
                
                exit when vIsRmBo = 'Y';
                vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);
            end loop;
            vNo :=  vNo + 1;
        end loop;
        return vNo;               
    end fGetDeepMaterial_RmBO;           
    
    function fGetCylSysID_byLvl_RmBo(pcyl_sys_id varchar2,pTotLvl number,pGetLvl number) return varchar2 is
        vStsLp number;
        vRtn varchar2(30); 
        vMktCstLnk varchar2(300);
        vcyl_sys_id varchar2(30); 
    begin
        vRtn := null;
        vStsLp := pTotLvl;
        vcyl_sys_id := pcyl_sys_id; 
        loop
            vStsLp := vStsLp - 1;
            if pTotLvl = pGetLvl then
                vRtn := vcyl_sys_id; 
            else
                select CYC_DATA_VALUE into  vMktCstLnk
                from mgtapps.cst_yarn_calculation
                where cyc_cyl_sys_id = vcyl_sys_id
                and cyc_top_no = 20;
                
                vcyl_sys_id := fGetCylSysId(vMktCstLnk);                 
            end if;
            
            exit when vStsLp = pGetLvl or vStsLp = 1; 
        end loop;
        vRtn := vcyl_sys_id;
        return vRtn;               
    end fGetCylSysID_byLvl_RmBo;
    
    function fGetVal_Dty_Prod(pcyl_sys_id varchar2) return varchar2 is
        vRtn varchar2(30);
    begin
        select cyc_data_value into vRtn
        from mgtapps.cst_yarn_calculation
        where cyc_cyl_sys_id = pcyl_sys_id 
        and cyc_top_no = 82;
        
        return vRtn;    
    end fGetVal_Dty_Prod;
    
    function fGetDty_Prod(pcyl_sys_id varchar2) return varchar2 is
        verrmsg varchar2(300);
        vRtn varchar2(300); 
        
        vMktCstLnk varchar2(300);
        vCylTypeMktCstLnk varchar2(300);
        vNo number;
        vCylSysID_MktCstLnk varchar2(300);
        pTotPrd number; vTot_loop number;
    begin
        for recDt in (select * from mgtapps.cst_yarn_left where cyl_sys_id = pcyl_sys_id) loop
            vRtn := null;     
            if recDt.CYL_TYPE = 'POY' then
                null;
            elsif recDt.CYL_TYPE in ('PTY BO','PTY') then
                vRtn := fGetVal_Dty_Prod(pcyl_sys_id);
            else
            -- get PTY Product --
                if recDt.CYL_TYPE in ('TTY','PLY','SUPERBA','MEEREBAH') then
                    vMktCstLnk := recDt.CYL_MARKETING_COST_LINK;
                    vCylTypeMktCstLnk := fGetCylTypeByMktCstLnk(vMktCstLnk);    
                    vTot_loop :=0;
                    loop                    
                        vNo :=  vNo + 1;
                        vCylSysID_MktCstLnk := fGetCylSysId(vMktCstLnk);                    
                        exit when vCylTypeMktCstLnk = 'PTY' or vTot_loop > 50;
                        vMktCstLnk := fGetRowMaterial(vCylSysID_MktCstLnk);
                        vCylTypeMktCstLnk := fGetCylTypeByMktCstLnk(vMktCstLnk);      
                        vTot_loop :=vTot_loop +1;             
                    end loop;                                    
                    vRtn := fGetVal_Dty_Prod(vCylSysID_MktCstLnk);     
                elsif recDt.CYL_TYPE in ('ACY') then
                declare
                    vDeep_Acy number;
                    vCylSysId_Acy varchar2(30);
                begin
                
                    if MGTAPPS.Pkg_Gen_Lvl_55.fIs_RM55(pcyl_sys_id) = 'N' then                
                
                        select MGTAPPS.pkg_yarn_marketing.fGetDeepLvl_Acy(pcyl_sys_id) into vDeep_Acy
                        from dual;
                        
                        for recCk in 1..vDeep_Acy loop                    
                            --CYL_SYS_ID|20201100113                    
                           select replace(MGTAPPS.pkg_yarn_marketing.fGetCylSysId_Acy (pcyl_sys_id,recCk),'CYL_SYS_ID|','') GET_DATA
                           into vCylSysId_Acy 
                           from dual;
                           
                           if fGetCylTypeByCylSysId(vCylSysId_Acy) = 'PTY' then
                            vRtn := fGetVal_Dty_Prod(vCylSysId_Acy);     
                            exit;
                           end if;
                        end loop;
                    else
                        null;
                    end if;
                end;
                elsif recDt.CYL_TYPE in ('SUPERBA') then
                    null;
                end if;
                -- get PTY Product
            end if;
        end loop;
        
        return vRtn;
    end fGetDty_Prod;
    
    FUNCTION fGetDeepLvl_Acy (pcyl_sys_id VARCHAR2)
       RETURN VARCHAR2
    IS
       vCylSysId_Ck   VARCHAR2 (30);
       vNoDeep        NUMBER;
       vTmp number;
       vStsRM55 boolean:=false;
    BEGIN   
       vCylSysId_Ck := pcyl_sys_id;
       vNoDeep := 1;
       
       
       begin       
            select 'x' into vTmp
            from mgtapps.CST_LVL_LEFT_PROD
            where CLLP_CYL_SYS_ID_REFF = pcyl_sys_id;
       exception
            when no_data_found then
                vStsRM55 :=false;
            when others then
                vStsRM55 :=true;
       end;
       
       if not vStsRM55 then
       LOOP
          FOR recDtCyl IN (SELECT *
                             FROM mgtapps.cst_yarn_left
                            WHERE cyl_sys_id = vCylSysId_Ck)
          LOOP
             IF recDtCyl.cyl_type = 'ACY'
             THEN
                FOR redRm
                   IN (  SELECT c.*, CYC_PRS_TYPE
                           FROM mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     cyc_cyl_sys_id = vCylSysId_Ck
                                AND cyc_top_no IN 55
                                AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = c.CYRM_CYRH_SYS_ID
                       ORDER BY cyrm_sec_no)
                LOOP
                   vNoDeep := vNoDeep + 1;

                   IF redRm.CYRM_TYPE_DATA = 'Yarn-Cap'
                   THEN
                      vCylSysId_Ck :=
                         MGTAPPS.pkg_yarn_calculation.get_CYL_SYS_ID (
                            redRm.CYRM_YARN_LEFT_NO              --pLEFT_NO number
                                                   ,
                            redRm.CYC_PRS_TYPE                         --pPRS_TYPE
                                              );
                   END IF;
                END LOOP;
             ELSE
                FOR redRm
                   IN (SELECT c.*, CYC_PRS_TYPE
                         FROM mgtapps.cst_yarn_calculation a,
                              mgtapps.cst_yarn_rm_hdr b,
                              mgtapps.cst_yarn_rm_captive c
                        WHERE     cyc_cyl_sys_id = vCylSysId_Ck
                              AND cyc_top_no IN 55
                              AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                              AND B.CYRH_SYS_ID = c.CYRC_CYRH_SYS_ID)
                LOOP
                   vNoDeep := vNoDeep + 1;
                   vCylSysId_Ck :=
                      MGTAPPS.pkg_yarn_calculation.get_CYL_SYS_ID (
                         redRm.CYRC_LEFT_NO                      --pLEFT_NO number
                                           ,
                         redRm.CYC_PRS_TYPE                            --pPRS_TYPE
                                           );
                END LOOP;
             END IF;
             
             IF recDtCyl.cyl_type = 'POY' or vNoDeep = 20 then
                vCylSysId_Ck := NULL;
             end if;
          END LOOP;

          EXIT WHEN vCylSysId_Ck IS NULL;      
       END LOOP;
       else
            select count(-1) into vNoDeep
            from mgtapps.CST_LVL_LEFT_PROD
            where CLLP_CYL_SYS_ID_REFF = pcyl_sys_id;        
       end if; 
       RETURN vNoDeep;
    END fGetDeepLvl_Acy;
    
    FUNCTION fGetCylSysId_Acy (pcyl_sys_id    VARCHAR2,
                                                 pLvl_Prd       NUMBER)
       RETURN VARCHAR2
    IS
       PRAGMA AUTONOMOUS_TRANSACTION;
       vCLLP_SYS_ID   VARCHAR2 (30);
       verrmsg        VARCHAR2 (300);

       vCylSysId_Ck   VARCHAR2 (30);
       vNoDeep        NUMBER;
       vNoLvl         NUMBER;

       vRtn           VARCHAR2 (100);
       vTotLp         number:=0;
    BEGIN
       vCylSysId_Ck := pcyl_sys_id;
       vCLLP_SYS_ID :=
             TO_CHAR (SYSDATE, 'YYYYMMDD')
          || TO_CHAR (pkg_seq_no.next_value ('CYL_SYS_ID', 'ADMIN', verrmsg),
                      'fm00000');

       vNoDeep := 1;

       INSERT INTO mgtapps.CST_LVL_LEFT_PROD (CLLP_SYS_ID,
                                              CLLP_LVL_PROD,
                                              CLLP_CYL_SYS_ID,
                                              CLLP_CREATED_TIMESTAMP)
            VALUES (vCLLP_SYS_ID,
                    vNoDeep,
                    'CYL_SYS_ID|' || pcyl_sys_id,
                    SYSDATE);

       COMMIT;

       LOOP
          FOR recDtCyl IN (SELECT *
                             FROM mgtapps.cst_yarn_left
                            WHERE cyl_sys_id = vCylSysId_Ck)
          LOOP
             IF recDtCyl.cyl_type = 'ACY'
             THEN
                FOR redRm
                   IN (  SELECT c.*, CYC_PRS_TYPE
                           FROM mgtapps.cst_yarn_calculation a,
                                mgtapps.cst_yarn_rm_hdr b,
                                mgtapps.cst_yarn_rm_multi c
                          WHERE     cyc_cyl_sys_id = vCylSysId_Ck
                                AND cyc_top_no IN 55
                                AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                                AND B.CYRH_SYS_ID = c.CYRM_CYRH_SYS_ID
                       ORDER BY cyrm_sec_no DESC)
                LOOP
                   vNoDeep := vNoDeep + 1;

                   IF redRm.CYRM_TYPE_DATA = 'Yarn-Cap'
                   THEN
                      vCylSysId_Ck :=
                         MGTAPPS.pkg_yarn_calculation.get_CYL_SYS_ID (
                            redRm.CYRM_YARN_LEFT_NO,
                            redRm.CYC_PRS_TYPE);

                      INSERT
                        INTO mgtapps.CST_LVL_LEFT_PROD (CLLP_SYS_ID,
                                                        CLLP_LVL_PROD,
                                                        CLLP_CYL_SYS_ID,
                                                        CLLP_CREATED_TIMESTAMP)
                      VALUES (vCLLP_SYS_ID,
                              vNoDeep,
                              'CYL_SYS_ID|' || vCylSysId_Ck,
                              SYSDATE);

                      COMMIT;
                   ELSIF redRm.CYRM_TYPE_DATA = 'Stores'
                   THEN
                      INSERT
                        INTO mgtapps.CST_LVL_LEFT_PROD (CLLP_SYS_ID,
                                                        CLLP_LVL_PROD,
                                                        CLLP_CYL_SYS_ID,
                                                        CLLP_CREATED_TIMESTAMP)
                         VALUES (
                                   vCLLP_SYS_ID,
                                   vNoDeep,
                                      redRm.CYRM_TYPE_DATA
                                   || '|'
                                   || redRm.CYRM_MARKETING_CODE,
                                   SYSDATE);

                      COMMIT;
                   END IF;
                END LOOP;
             ELSE
                FOR redRm
                   IN (SELECT c.*, CYC_PRS_TYPE
                         FROM mgtapps.cst_yarn_calculation a,
                              mgtapps.cst_yarn_rm_hdr b,
                              mgtapps.cst_yarn_rm_captive c
                        WHERE     cyc_cyl_sys_id = vCylSysId_Ck
                              AND cyc_top_no IN 55
                              AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                              AND B.CYRH_SYS_ID = c.CYRC_CYRH_SYS_ID)
                LOOP
                   vNoDeep := vNoDeep + 1;
                   vCylSysId_Ck :=
                      MGTAPPS.pkg_yarn_calculation.get_CYL_SYS_ID (
                         redRm.CYRC_LEFT_NO                      --pLEFT_NO number
                                           ,
                         redRm.CYC_PRS_TYPE                            --pPRS_TYPE
                                           );

                   INSERT INTO mgtapps.CST_LVL_LEFT_PROD (CLLP_SYS_ID,
                                                          CLLP_LVL_PROD,
                                                          CLLP_CYL_SYS_ID,
                                                          CLLP_CREATED_TIMESTAMP)
                        VALUES (vCLLP_SYS_ID,
                                vNoDeep,
                                'CYL_SYS_ID|' || vCylSysId_Ck,
                                SYSDATE);

                   COMMIT;
                END LOOP;
             END IF;

             IF recDtCyl.cyl_type = 'POY'
             THEN
                vCylSysId_Ck := NULL;
             END IF;
          END LOOP;
          vTotLp := vTotLp + 1;   
          EXIT WHEN vCylSysId_Ck IS NULL or vTotLp >= 100;
       END LOOP;

       vRtn := NULL;
       vNoLvl := 1;

       FOR recDt IN (  SELECT *
                         FROM mgtapps.CST_LVL_LEFT_PROD
                        WHERE CLLP_SYS_ID = vCLLP_SYS_ID
                     ORDER BY CLLP_LVL_PROD DESC)
       LOOP
          IF pLvl_Prd = vNoLvl
          THEN
             vRtn := recDt.CLLP_CYL_SYS_ID;
             EXIT;
          END IF;

          vNoLvl := vNoLvl + 1;
       END LOOP;

       DELETE FROM mgtapps.CST_LVL_LEFT_PROD
             WHERE CLLP_SYS_ID = vCLLP_SYS_ID;

       COMMIT;

       RETURN vRtn;
    END fGetCylSysId_Acy;
    
    FUNCTION fGetDeepMaterial_Ity (pcyl_sys_id VARCHAR2)
       RETURN number
    IS
       vNoDeep        NUMBER;
    BEGIN
        vNoDeep := 0; 
        for recRm in (        
            select * 
            from    mgtapps.cst_yarn_calculation a
                    ,mgtapps.cst_yarn_rm_hdr b
                    ,mgtapps.cst_yarn_rm_multi c
            where CYC_CYL_SYS_ID = pcyl_sys_id
            and cyc_top_no = 55
            and CYRH_CYC_SYS_ID = CYC_SYS_ID            
            and CYC_FORMULA_TYPE = 'Raw_Material'             
            and CYRH_TYPE = 'Multi Yarn'
            and b.CYRH_SYS_ID = CYRM_CYRH_SYS_ID            
        ) loop
            vNoDeep := vNoDeep + 1;
        end loop; 
                
        RETURN vNoDeep;
    END fGetDeepMaterial_Ity;
    
    function fIs_POY_Product(pcyl_sys_id VARCHAR2) return varchar2 is
        vCk number;
    begin
        select 'x' into vCk 
        from cst_yarn_left
        where cyl_sys_id = pcyl_sys_id
        and cyl_type = 'POY';
    exception
        when no_data_found then return 'N'; 
        when others then return 'Y';
    end fIs_POY_Product;
    
    function fGetRm_Captive(pcyl_sys_id VARCHAR2) return varchar2 is
        vRtn varchar2(30):=null;
    begin
        for recDt in (
            select CYRC_CYL_SYS_ID
            from    cst_yarn_calculation a
                    ,cst_yarn_rm_hdr b
                    ,cst_yarn_rm_captive c
            where cyc_cyl_sys_id = pcyl_sys_id--2023031543276
            and A.CYC_TOP_NO = 55
            and cyc_formula_type = 'Raw_Material'
            and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
            and B.CYRH_TYPE = 'Captive Cost'
            and B.CYRH_SYS_ID = C.CYRC_CYRH_SYS_ID
        ) loop
            vRtn := recDt.CYRC_CYL_SYS_ID;
        end loop;
        
        return vRtn;
    end fGetRm_Captive;
    
    function fGetRm_Multi(pcyl_sys_id VARCHAR2) return varchar2 is
        vRtn varchar2(30):=null;
    begin
        for recDt in (
        
            select c.*
            from    cst_yarn_calculation a
                    ,cst_yarn_rm_hdr b
                    ,cst_yarn_rm_multi c
            where cyc_cyl_sys_id = pcyl_sys_id--2022121284128
            and A.CYC_TOP_NO = 55
            and cyc_formula_type = 'Raw_Material'
            and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
            and B.CYRH_TYPE = 'Multi Yarn'
            and B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
            and CYRM_TYPE_DATA = 'Yarn-Cap'
            
            
        ) loop
            vRtn := recDt.CYRM_CYL_SYS_ID;
        end loop;
        
        return vRtn;
    end fGetRm_Multi;    
    
    function fGet_Poy_Sys_Id(pcyl_sys_id VARCHAR2) return varchar2 is
        vRtn varchar2(30):=null; 
        vCylSysId_Poy varchar2(30):=null; vCylSysId_Ck varchar2(30):=null; vCylSysId_RM varchar2(30):=null;        
        vStsLp boolean;
    begin
        if fIs_POY_Product(pcyl_sys_id) = 'Y' then
            vCylSysId_Poy := pcyl_sys_id;
        else
            vCylSysId_Ck :=  pcyl_sys_id;
            vStsLp := true;
            loop
                vCylSysId_RM := MGTAPPS.pkg_yarn_marketing.fGetRm_Captive(vCylSysId_Ck);
                
                if vCylSysId_RM is null then
                    vCylSysId_RM := fGetRm_Multi(vCylSysId_Ck);
                end if; 
                
                if vCylSysId_RM is not null then
                    if fIs_POY_Product(vCylSysId_RM) = 'Y' then
                        vCylSysId_Poy:= vCylSysId_RM;
                        vRtn := vCylSysId_Poy;
                        vStsLp := false;
                    end if; 
                else
                    vStsLp := false;
                end if;
                
                exit when not vStsLp;
                vCylSysId_Ck := vCylSysId_RM;
            end loop;
        end if;
        vRtn := vCylSysId_Poy;
        return vRtn;
    end fGet_Poy_Sys_Id;

    function fGet_ManPower_Cst(pCyl_Sys_Id varchar2) return varchar2 is
        vRtn varchar2(150);
    begin    
        for recGetDt in (
            select CYCC_TOP_88_DATA_VALUE dt_val
            from cst_yarn_calculation_cur
            where cycc_cyl_sys_id = pCyl_Sys_Id
        ) loop
            vRtn := recGetDt.dt_val;
        end loop;
        return vRtn;
    end fGet_ManPower_Cst;
    
    function fGet_Over_Heads_Cst(pCyl_Sys_Id varchar2) return varchar2 is
        vRtn varchar2(150);
    begin    
        for recGetDt in (
            select CYCC_TOP_89_DATA_VALUE dt_val
            from cst_yarn_calculation_cur
            where cycc_cyl_sys_id = pCyl_Sys_Id
        ) loop
            vRtn := recGetDt.dt_val;
        end loop;
        return vRtn;
    end fGet_Over_Heads_Cst;
    
    function fGet_Poy_ManPower_cst(pcyl_sys_id VARCHAR2) return number is
        vRtn number:=null;
        vCylSysId_Poy varchar2(30):=null; 
    begin
    
        vCylSysId_Poy := fGet_Poy_Sys_Id(pcyl_sys_id); 
        
        if vCylSysId_Poy is not null then
            vRtn := fGet_ManPower_Cst(vCylSysId_Poy);
        end if;    
        
        return vRtn;
    end fGet_Poy_ManPower_cst;
    
    function fGet_Poy_Over_Heads_Cst(pcyl_sys_id VARCHAR2) return number is
        vRtn number:=null;
        vCylSysId_Poy varchar2(30):=null; 
    begin
    
        vCylSysId_Poy := fGet_Poy_Sys_Id(pcyl_sys_id); 
        
        if vCylSysId_Poy is not null then
            vRtn :=  fGet_Over_Heads_Cst(vCylSysId_Poy);
        end if;    
        
        return vRtn;
    end fGet_Poy_Over_Heads_Cst;
    
    function fIs_PTY_Product(pcyl_sys_id VARCHAR2) return varchar2 is
        vCk number;
    begin
        select 'x' into vCk 
        from cst_yarn_left
        where cyl_sys_id = pcyl_sys_id
        and cyl_type = 'PTY';
    exception
        when no_data_found then return 'N'; 
        when others then return 'Y';
    end fIs_PTY_Product;
    
    function fGet_Pty_Sys_Id(pcyl_sys_id VARCHAR2) return varchar2 is
        vRtn varchar2(30):=null; 
        vCylSysId_Pty varchar2(30):=null; vCylSysId_Ck varchar2(30):=null; vCylSysId_RM varchar2(30):=null;        
        vStsLp boolean;
    begin
        if fIs_PTY_Product(pcyl_sys_id) = 'Y' then
            vCylSysId_Pty := pcyl_sys_id;
        else
            vCylSysId_Ck :=  pcyl_sys_id;
            vStsLp := true;
            loop
                vCylSysId_RM := MGTAPPS.pkg_yarn_marketing.fGetRm_Captive(vCylSysId_Ck);
                
                if vCylSysId_RM is null then
                    vCylSysId_RM := fGetRm_Multi(vCylSysId_Ck);
                end if; 
                
                if vCylSysId_RM is not null then
                    if fIs_PTY_Product(vCylSysId_RM) = 'Y' then
                        vCylSysId_Pty:= vCylSysId_RM;
                        vRtn := vCylSysId_Pty;
                        vStsLp := false;
                    end if; 
                else
                    vStsLp := false;
                end if;
                
                exit when not vStsLp;
                vCylSysId_Ck := vCylSysId_RM;
            end loop;
        end if;
        vRtn := vCylSysId_Pty;
        return vRtn;
    end fGet_Pty_Sys_Id;
    
    function fGet_Pty_ManPower_cst(pcyl_sys_id VARCHAR2) return number is
        vRtn number:=null;
        vCylSysId_Pty varchar2(30):=null; 
    begin
    
        vCylSysId_Pty := fGet_Pty_Sys_Id(pcyl_sys_id); 
        
        if vCylSysId_Pty is not null then
            vRtn := fGet_ManPower_Cst(vCylSysId_Pty);
        end if;    
        
        return vRtn;
    end fGet_Pty_ManPower_cst;
    
    function fGet_Pty_Over_Heads_Cst(pcyl_sys_id VARCHAR2) return number is
        vRtn number:=null;
        vCylSysId_Pty varchar2(30):=null; 
    begin
    
        vCylSysId_Pty := fGet_Pty_Sys_Id(pcyl_sys_id); 
        
        if vCylSysId_Pty is not null then
            vRtn :=  fGet_Over_Heads_Cst(vCylSysId_Pty);
        end if;    
        
        return vRtn;
    end fGet_Pty_Over_Heads_Cst;
    
    function fGet_Final_Conversion( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number is
        vRtn number:=0; 
        vCyl_Type_Prs varchar2(30):=null; 
        vCyl_Sys_Id_Poy varchar2(30):=null;
        vPOY_RM_Rate number:=0; vPOY_MB_Cost number:=0;
        vCap_Cost number:=0; vDomestic_Cost number:=0;
    begin
        for recDt in ( select * from cst_yarn_left where cyl_sys_id=pCyl_Sys_Id_Prs ) loop
            vCyl_Type_Prs := recDt.Cyl_Type; 
        end loop;        
        if vCyl_Type_Prs='POY' then
            vCyl_Sys_Id_Poy:=pCyl_Sys_Id_Prs;
        else
            vCyl_Sys_Id_Poy:=fGet_POY_SysId(pCyl_Sys_Id_Prs);
        end if;
        
        -- Get POY 6.RM Rate. - POY 39.MB Cost.
        if vCyl_Sys_Id_Poy is not null then
            for recPoy in (
                select cyc_top_55,cyc_top_73
                from
                    (
                        select 1 no,cyc_data_value cyc_top_55 
                        from cst_yarn_calculation 
                        where cyc_cyl_sys_id=vCyl_Sys_Id_Poy
                        and cyc_top_no=55
                    ) a,(
                        select 1 no,cyc_data_value cyc_top_73
                        from cst_yarn_calculation 
                        where cyc_cyl_sys_id=vCyl_Sys_Id_Poy
                        and cyc_top_no=73
                    )b
                where a.no=b.no
            )loop
                 -- POY 6.RM Rate. - POY 39.MB Cost.
                vPOY_RM_Rate :=nvl(recPoy.cyc_top_55,0);
                vPOY_MB_Cost :=nvl(recPoy.cyc_top_73,0);
            end loop;
        else
            -- cek Poy From RM Store / POY BO
            for recPoy in (                
                select b.cyc_data_value--,c.* 
                from cst_yarn_left a 
                     ,cst_yarn_calculation b
                     ,cst_yarn_rm_hdr c
                     ,cst_yarn_rm_dtl d
                where cyl_sys_id = pCyl_Sys_Id_Prs
                and a.cyl_sys_id=b.cyc_cyl_sys_id
                and b.cyc_top_no=55 and cyrh_type='Store Rate'
                and b.CYC_SYS_ID=c.CYRH_CYC_SYS_ID
                and c.CYRH_SYS_ID=d.CYRD_CYRH_SYS_ID
                and d.CYRD_TYPE_DATA='From Group Item MKT Rate'
                                      
            )loop
                vPOY_RM_Rate :=nvl(recPoy.cyc_data_value,0);
            end loop;
            -- cek Poy From RM Store / POY BO
            null;
        end if;
        -- Get POY RM Rate. - POY MB Cost.

        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            -- 64.Domestic Cost. - POY 6.RM Rate. - POY 39.MB Cost.            
            -- nvl(CYCC_TOP_121_DATA_VALUE,0) + nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) - CYCC_TOP_55_DATA_VALUE - CYCC_TOP_73_DATA_VALUE         
            for recPoy in (
                select cyc_data_value+ nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) cyc_top_121
                from cst_yarn_calculation 
                where cyc_cyl_sys_id=pCyl_Sys_Id_Prs
                and cyc_top_no=121
            )loop
                vDomestic_Cost :=nvl(recPoy.cyc_top_121,0);
            end loop;                                    
            vRtn := nvl(vDomestic_Cost,0) - nvl(vPOY_RM_Rate,0) - nvl(vPOY_MB_Cost,0);
        end if;
        return vRtn;
    exception 
        when others then
            return null;
    end fGet_Final_Conversion;

    function fGet_Final_Conversion_Val( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number is
        vRtn number:=0; 
        vCyl_Type_Prs varchar2(30):=null; 
        vCyl_Sys_Id_Poy varchar2(30):=null;
        vPOY_RM_Rate number:=0; vPOY_MB_Cost number:=0;
        vCap_Cost number:=0; vDomestic_Cost number:=0;
    begin
        for recDt in ( select * from cst_yarn_left where cyl_sys_id=pCyl_Sys_Id_Prs ) loop
            vCyl_Type_Prs := recDt.Cyl_Type; 
        end loop;        
        if vCyl_Type_Prs='POY' then
            vCyl_Sys_Id_Poy:=pCyl_Sys_Id_Prs;
        else
            vCyl_Sys_Id_Poy:=fGet_POY_SysId(pCyl_Sys_Id_Prs);
        end if;
        
        -- Get POY 6.RM Rate. - POY 39.MB Cost.
        if vCyl_Sys_Id_Poy is not null then
            for recPoy in (
                select cyc_top_55,cyc_top_73
                from
                    (
                        select 1 no,cyc_data_value cyc_top_55 
                        from cst_yarn_calculation 
                        where cyc_cyl_sys_id=vCyl_Sys_Id_Poy
                        and cyc_top_no=55
                    ) a,(
                        select 1 no,cyc_data_value cyc_top_73
                        from cst_yarn_calculation 
                        where cyc_cyl_sys_id=vCyl_Sys_Id_Poy
                        and cyc_top_no=73
                    )b
                where a.no=b.no
            )loop
                 -- POY 6.RM Rate. - POY 39.MB Cost.
                vPOY_RM_Rate :=nvl(recPoy.cyc_top_55,0);
                vPOY_MB_Cost :=nvl(recPoy.cyc_top_73,0);
            end loop;
        else
            -- cek Poy From RM Store / POY BO
            for recPoy in (                
                select b.cyc_data_value--,c.* 
                from cst_yarn_left a 
                     ,cst_yarn_calculation b
                     ,cst_yarn_rm_hdr c
                     ,cst_yarn_rm_dtl d
                where cyl_sys_id = pCyl_Sys_Id_Prs
                and a.cyl_sys_id=b.cyc_cyl_sys_id
                and b.cyc_top_no=55 and cyrh_type='Store Rate'
                and b.CYC_SYS_ID=c.CYRH_CYC_SYS_ID
                and c.CYRH_SYS_ID=d.CYRD_CYRH_SYS_ID
                and d.CYRD_TYPE_DATA='From Group Item MKT Rate'
                                      
            )loop
                vPOY_RM_Rate :=nvl(recPoy.cyc_data_value,0);
            end loop;
            -- cek Poy From RM Store / POY BO
            null;
        end if;
        -- Get POY RM Rate. - POY MB Cost.

        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            -- 64.Domestic Cost. - POY 6.RM Rate. - POY 39.MB Cost.            
            -- nvl(CYCC_TOP_121_DATA_VALUE,0) + nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) - CYCC_TOP_55_DATA_VALUE - CYCC_TOP_73_DATA_VALUE         
            for recPoy in (
                select cyc_data_value+ nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) cyc_top_121
                from cst_yarn_calculation 
                where cyc_cyl_sys_id=pCyl_Sys_Id_Prs
                and cyc_top_no=121
            )loop
                vDomestic_Cost :=nvl(recPoy.cyc_top_121,0);
            end loop;                                    
            vRtn := nvl(vDomestic_Cost,0) - nvl(vPOY_RM_Rate,0) - nvl(vPOY_MB_Cost,0);
        end if;
        return vRtn;
    exception 
        when others then
            return null;
    end fGet_Final_Conversion_Val;
    
    function fGet_val128(pCyl_Sys_Id_Dt varchar2) return varchar2 is
    begin
        for recDt in (
                select * 
                from cst_yarn_calculation
                where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                and cyc_top_no = 128
        ) loop
            return recDt.cyc_data_value;
        end loop; 
        return null;
    end fGet_val128;
    
    function fGet_val95(pCyl_Sys_Id_Dt varchar2) return varchar2 is
    begin
        for recDt in (
                select * 
                from cst_yarn_calculation
                where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                and cyc_top_no = 95
        ) loop
            return recDt.cyc_data_value;
        end loop; 
        return null;
    end fGet_val95;
    
    function fGet_CostLess_QL_CO_Frwd( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2 is
        vRtn varchar2(300):=null;                                   
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            --vRtn := fGet_val128(pCyl_Sys_Id_Dt);
            vRtn := fGet_val95(pCyl_Sys_Id_Dt);
        end if;
        return vRtn;
    end fGet_CostLess_QL_CO_Frwd;
    
    function fGet_NSBC_SP( 
                            pCyl_Sys_Id_Dt varchar2 -- Product Final 
                            ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                           ) return varchar2 is
        vRtn varchar2(300):=null;                                   
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            for recDt in (
                select * from cst_mst_product_grade
                where cmpg_sys_id = 20230641
            ) loop
                return nvl(recDt.CMPG_STD_SELLING_PRICE,0);
            end loop; 
        end if;
        return null;
    end fGet_NSBC_SP;
    
    function fGet_Addl_NSBC_Loss( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2 is
        vRtn varchar2(300):=null;                                   
        vNSBC_SP varchar2(30):=null;
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            --(((100-CYCC_TOP_23_DATA_VALUE))%*128)-CYCC_TOP_104_DATA_VALUE
            for recDt in (
                select nvl(val23,0) val23,nvl(val128,0)val128,nvl(val104,0)val104,nvl(val95,0)val95,nvl(val127,0)val127
                from
                    (
                    select  1 no
                            ,cyc_data_value val23
                    from cst_yarn_calculation
                    where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                    and cyc_top_no = 23
                    ) a,(
                    select  1 no
                            ,cyc_data_value val128
                    from cst_yarn_calculation
                    where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                    and cyc_top_no = 128
                    ) b,(
                    select  1 no
                            ,cyc_data_value val104
                    from cst_yarn_calculation
                    where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                    and cyc_top_no = 104
                    ) c,(
                    select  1 no
                            ,cyc_data_value val95
                    from cst_yarn_calculation
                    where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                    and cyc_top_no = 95
                    ) d,(
                    select  1 no
                            ,cyc_data_value val127
                    from cst_yarn_calculation
                    where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                    and cyc_top_no = 127
                    ) e
                where a.no=b.no
                and a.no=c.no
                and a.no=d.no
                and a.no=e.no
            ) loop
            
                vNSBC_SP := fGet_NSBC_SP( 
                            pCyl_Sys_Id_Dt-- Product Final 
                            ,pCyl_Sys_Id_Prs-- Product Request
                           );
                --return ((100-nvl(recDt.CYCC_TOP_23_DATA_VALUE)/100) * recDt.CYCC_TOP_128_DATA_VALUE)
                --        -recDt.CYCC_TOP_104_DATA_VALUE;
                if recDt.val127 <> 0 then -- add 10-04-2026 req by mbak sintia
                    return (((100-recDt.val23)/100)*(recDt.val128-vNSBC_SP))-recDt.val104;
                -- add 10-04-2026 req by mbak sintia
                else
                    return (((100-recDt.val23)/100)*(recDt.val95-vNSBC_SP))-recDt.val104;
                end if;
                -- add 10-04-2026 req by mbak sintia
            end loop; 
        end if;
        return null;
    end fGet_Addl_NSBC_Loss;
    
    function fGet_Extra_Yarn_Persen( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2 is
        vRtn varchar2(300):=null;                                   
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            for recDt in (
                select * 
                from cst_yarn_calculation
                where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                and cyc_top_no = 127
            ) loop
                vRtn := recDt.cyc_data_value;
            end loop;
        end if;
        return vRtn;
    end fGet_Extra_Yarn_Persen;
    
    function fGet_Cost_Of_Extra_Yarn_Persen( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return varchar2 is
        vRtn varchar2(300):=null;                                   
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            for recDt in (
                select * 
                from cst_yarn_calculation
                where cyc_cyl_sys_id = pCyl_Sys_Id_Dt
                and cyc_top_no = 129
            ) loop
                vRtn := recDt.cyc_data_value;
            end loop;
        end if;
        return vRtn;
    end fGet_Cost_Of_Extra_Yarn_Persen;
    
    function fGet_Dom_Cost_AX_Grd_Only( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number is
        vRtn number:=0; vDomestic_Cost number:=0; vAddl_NSBC_Loss number:=0;
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            for recDt in (
                select nvl(CYCC_TOP_121_DATA_VALUE,0) + nvl(MGTAPPS.pkg_yarn_marketing.getPrdFowarding,0) GET_DATA 
                from mgtapps.cst_yarn_calculation_cur
                where CYCC_CYL_SYS_ID = pCyl_Sys_Id_Prs
                and CYCC_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
            ) loop
                vDomestic_Cost := recDt.GET_DATA;
            end loop;
        
            vAddl_NSBC_Loss := fGet_Addl_NSBC_Loss(
                                        pCyl_Sys_Id_Dt-- Product Final 
                                        ,pCyl_Sys_Id_Prs-- Product Request
                                       );
            vRtn := vDomestic_Cost+vAddl_NSBC_Loss;
        end if;
        return vRtn;
    exception 
        when others then
            return null;
    end fGet_Dom_Cost_AX_Grd_Only;
    
    function fGetDomCost_AXGrdOnly_WExtrCst( 
                                    pCyl_Sys_Id_Dt varchar2 -- Product Final 
                                    ,pCyl_Sys_Id_Prs VARCHAR2 -- Product Request
                                   ) return number is
        vRtn number:=0; 
        vDom_Cost_AX_Grd_Only number:=0;
        vCost_Of_Extra_Yarn_Persen number:=0;
    begin
        if pCyl_Sys_Id_Dt = pCyl_Sys_Id_Prs then
            vDom_Cost_AX_Grd_Only :=fGet_Dom_Cost_AX_Grd_Only(pCyl_Sys_Id_Dt,pCyl_Sys_Id_Prs);
            vCost_Of_Extra_Yarn_Persen := fGet_Cost_Of_Extra_Yarn_Persen(pCyl_Sys_Id_Dt,pCyl_Sys_Id_Prs);
            vRtn := vDom_Cost_AX_Grd_Only+vCost_Of_Extra_Yarn_Persen;                        
        end if;
        return vRtn;
    exception 
        when others then
            return null;
    end fGetDomCost_AXGrdOnly_WExtrCst;        
    
 end pkg_yarn_marketing; 
/
