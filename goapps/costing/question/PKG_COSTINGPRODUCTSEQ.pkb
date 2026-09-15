CREATE OR REPLACE PACKAGE BODY MGTAPPS.pkg_CostingProductSeq AS
    
    procedure pIns_Rm_StoreRate(
            pCYRH_SYS_ID varchar2
            ,pCPSTH_SYS_ID varchar2
            ,pRM_SEQUENCE number
            ,pLEFT_NO_INTO number
            ) is
       vCPSTD CST_PRODUCT_SEQ_TAB_DTL%rowtype;
    begin        
        for recRMDtl in (                            
                            select * 
                            from    cst_yarn_rm_dtl a
                                    ,CST_GRP_HEAD b
                            where CYRD_CYRH_SYS_ID=pCYRH_SYS_ID
                            and CYRD_RM_CODE=CGH_SYS_ID
                                
                         ) loop                                     
             
            vCPSTD := null;                                
            vCPSTD.CPSTD_SYS_ID             := '123';                           
            vCPSTD.CPSTD_CPSTH_SYS_ID       := pCPSTH_SYS_ID;
            vCPSTD.CPSTD_RM_ITEM_NAME	    := recRMDtl.CGH_DESCRIPTION;
            --vCPSTD.CPSTD_RM_SEQUENCE	    := pRM_SEQUENCE;
            vCPSTD.CPSTD_RM_TYPE            := 'Store Rate';
            vCPSTD.CPSTD_RM_ITEM_CODE       := recRMDtl.CGH_GROUP_CODE;
            vCPSTD.CPSTD_RM_LEFT_NO         := recRMDtl.CGH_SYS_ID;
            vCPSTD.CPSTD_RM_LEFT_NO_INTO    := pLEFT_NO_INTO;
            vCPSTD.CPSTD_RM_SEQUENCE_REAL   := pRM_SEQUENCE;
            vCPSTD.CPSTD_RM_TOP_2           := recRMDtl.CGH_DESCRIPTION;
                                
            insert into CST_PRODUCT_SEQ_TAB_DTL values vCPSTD;
                            
       end loop;		
    
    end pIns_Rm_StoreRate;
    
    procedure pIns_Rm_Captive(
            pCYRH_SYS_ID varchar2
            ,pCPSTH_SYS_ID varchar2
            ,pRM_SEQUENCE number
            ,pRM_LEFT_NO_INTO number
            ) is
       vCPSTD CST_PRODUCT_SEQ_TAB_DTL%rowtype;
    begin        
        for recRMDtl in (                            
        
                            select * 
                            from    cst_yarn_rm_captive a
                                    ,cst_yarn_left b
                                    ,cst_yarn_calculation c                                       
                            where CYRC_CYRH_SYS_ID=pCYRH_SYS_ID
                            and CYRC_CYL_SYS_ID=CYL_SYS_ID
                            and CYRC_CYL_SYS_ID=CYC_CYL_SYS_ID
                            and CYC_TOP_NO=2
                                
                         ) loop                                     
             
            vCPSTD := null;
            vCPSTD.CPSTD_SYS_ID             := '123';
            vCPSTD.CPSTD_CPSTH_SYS_ID       :=  pCPSTH_SYS_ID;            
            vCPSTD.CPSTD_RM_ITEM_CODE       :=  recRMDtl.CYL_ITEM_CODE; 
            vCPSTD.CPSTD_RM_TOP_2           :=  recRMDtl.CYC_DATA_VALUE; 
            vCPSTD.CPSTD_RM_ITEM_NAME       :=  recRMDtl.CYL_NAME;
            vCPSTD.CPSTD_RM_CYL_TYPE        :=  recRMDtl.CYL_TYPE;
            vCPSTD.CPSTD_RM_SHADE_CODE      :=  recRMDtl.CYL_SHADE_CODE;
            vCPSTD.CPSTD_RM_SHADE_NAME      :=  recRMDtl.CYL_SHADE_NAME;
            vCPSTD.CPSTD_RM_LEFT_NO         :=  recRMDtl.CYL_LEFT_NO;
            --vCPSTD.CPSTD_RM_SEQUENCE        :=  pRM_SEQUENCE;
            vCPSTD.CPSTD_RM_TYPE            :=  'Captive Cost';
            vCPSTD.CPSTD_CYL_SYS_ID         :=  recRMDtl.CYL_SYS_ID;
            vCPSTD.CPSTD_RM_LEFT_NO_INTO    :=  pRM_LEFT_NO_INTO;
            vCPSTD.CPSTD_RM_SEQUENCE_REAL   :=  pRM_SEQUENCE;            
                                           
            insert into CST_PRODUCT_SEQ_TAB_DTL values vCPSTD;
                            
       end loop;		
    
    end pIns_Rm_Captive;

    procedure pIns_Rm_MultiYarn(
            pCYRH_SYS_ID varchar2
            ,pCPSTH_SYS_ID varchar2
            ,pRM_SEQUENCE number
            ,pLEFT_NO_INTO number
            ) is
       vCPSTD CST_PRODUCT_SEQ_TAB_DTL%rowtype;
    begin        
        for recRMDtl in (             
                
                            select * 
                            from    cst_yarn_rm_multi a                                       
                            where CYRM_CYRH_SYS_ID=pCYRH_SYS_ID
                            order by cyrm_sec_no
                               
        ) loop                                     
             
            if recRMDtl.CYRM_TYPE_DATA='Yarn-Cap' then
                for recMulti in (
                
                    select a.*,b.cyc_data_value 
                    from    cst_yarn_left a
                            ,cst_yarn_calculation b
                    where CYL_SYS_ID=recRMDtl.CYRM_CYL_SYS_ID                    
                    and CYL_SYS_ID=CYC_CYL_SYS_ID
                    and CYC_TOP_NO=2
                
                ) loop
            
                    vCPSTD := null;
                    vCPSTD.CPSTD_SYS_ID             := '123';
                    vCPSTD.CPSTD_CPSTH_SYS_ID       :=  pCPSTH_SYS_ID;                    
                    vCPSTD.CPSTD_RM_ITEM_CODE       :=  recMulti.CYL_ITEM_CODE; 
                    vCPSTD.CPSTD_RM_TOP_2           :=  recMulti.CYC_DATA_VALUE; 
                    vCPSTD.CPSTD_RM_ITEM_NAME       :=  recMulti.CYL_NAME;
                    vCPSTD.CPSTD_RM_CYL_TYPE        :=  recMulti.CYL_TYPE;
                    vCPSTD.CPSTD_RM_SHADE_CODE      :=  recMulti.CYL_SHADE_CODE;
                    vCPSTD.CPSTD_RM_SHADE_NAME      :=  recMulti.CYL_SHADE_NAME;
                    vCPSTD.CPSTD_RM_LEFT_NO         :=  recMulti.CYL_LEFT_NO;
                    --vCPSTD.CPSTD_RM_SEQUENCE        :=  pRM_SEQUENCE;		            
                    vCPSTD.CPSTD_RM_SUB_SEQUENCE    :=  recRMDtl.cyrm_sec_no;
                    vCPSTD.CPSTD_RM_TYPE            :=  'Multi Yarn';
                    vCPSTD.CPSTD_CYL_SYS_ID         :=  recMulti.CYL_SYS_ID;
                    vCPSTD.CPSTD_RM_LEFT_NO_INTO    :=  pLEFT_NO_INTO;    
                    vCPSTD.CPSTD_RM_SUB_TYPE        :=  recRMDtl.CYRM_TYPE_DATA;
                    vCPSTD.CPSTD_RM_SEQUENCE_REAL   :=  pRM_SEQUENCE;
                                                   
                    insert into CST_PRODUCT_SEQ_TAB_DTL values vCPSTD;
                
                end loop;
                
            elsif recRMDtl.CYRM_TYPE_DATA='Stores' then   

                for recMulti in (
                                            
                        SELECT *  FROM cst_yarn_rm_multi a,CST_GRP_HEAD b 
                        WHERE CYRM_CYRH_SYS_ID = pcyrh_sys_id 
                        AND cgh_sys_id = CYRM_MARKETING_CODE                        
                        
                                
                     ) loop                                     
             
                    vCPSTD := null;                                                           
                    vCPSTD.CPSTD_CPSTH_SYS_ID       := pCPSTH_SYS_ID;		
                    vCPSTD.CPSTD_RM_TOP_2           := recMulti.CGH_GROUP_CODE; 
                    vCPSTD.CPSTD_RM_ITEM_NAME	    := recMulti.CGH_DESCRIPTION;
                    vCPSTD.CPSTD_RM_SEQUENCE	    := pRM_SEQUENCE;		
                    vCPSTD.CPSTD_RM_SUB_SEQUENCE    := recRMDtl.cyrm_sec_no;
                    vCPSTD.CPSTD_RM_TYPE            := 'Multi Yarn';
                    vCPSTD.CPSTD_RM_SUB_TYPE        :=  recRMDtl.CYRM_TYPE_DATA;
                                            
                    insert into CST_PRODUCT_SEQ_TAB_DTL values vCPSTD;
                    
                end loop;    
            
            end if;
                            
       end loop;		
    
    end pIns_Rm_MultiYarn;

    procedure pIns_Rm_Uneven_Packing(
            pCYRH_SYS_ID varchar2
            ,pCPSTH_SYS_ID varchar2
            ,pRM_SEQUENCE number
            ,pLEFT_NO_INTO number
            ) is
       vCPSTD CST_PRODUCT_SEQ_TAB_DTL%rowtype;
    begin        
        for recRMDtl in (             
                
                            select * 
                            from    cst_yarn_rm_uneven_pkg a                                       
                            where CYRUP_CYRH_SYS_ID=pCYRH_SYS_ID
                            order by CYRUP_SEC_NO
                               
        ) loop                                     
             
            for recUneven in (
                
                select a.*,b.cyc_data_value 
                from    cst_yarn_left a
                        ,cst_yarn_calculation b
                where CYL_SYS_ID=recRMDtl.CYRUP_CYL_SYS_ID              
                and CYL_SYS_ID=CYC_CYL_SYS_ID
                and CYC_TOP_NO=2
                
            ) loop
            
                vCPSTD := null;
                vCPSTD.CPSTD_SYS_ID             := '123';
                vCPSTD.CPSTD_CPSTH_SYS_ID       :=  pCPSTH_SYS_ID;                    
                vCPSTD.CPSTD_RM_ITEM_CODE       :=  recUneven.CYL_ITEM_CODE; 
                vCPSTD.CPSTD_RM_TOP_2           :=  recUneven.CYC_DATA_VALUE; 
                vCPSTD.CPSTD_RM_ITEM_NAME       :=  recUneven.CYL_NAME;
                vCPSTD.CPSTD_RM_CYL_TYPE        :=  recUneven.CYL_TYPE;
                vCPSTD.CPSTD_RM_SHADE_CODE      :=  recUneven.CYL_SHADE_CODE;
                vCPSTD.CPSTD_RM_SHADE_NAME      :=  recUneven.CYL_SHADE_NAME;
                vCPSTD.CPSTD_RM_LEFT_NO         :=  recUneven.CYL_LEFT_NO;
                --vCPSTD.CPSTD_RM_SEQUENCE        :=  pRM_SEQUENCE;		            
                vCPSTD.CPSTD_RM_SUB_SEQUENCE    :=  recRMDtl.CYRUP_SEC_NO;
                vCPSTD.CPSTD_RM_TYPE            :=  'Uneven Packing';
                vCPSTD.CPSTD_CYL_SYS_ID         :=  recUneven.CYL_SYS_ID;
                vCPSTD.CPSTD_RM_LEFT_NO_INTO    :=  pLEFT_NO_INTO;    
                vCPSTD.CPSTD_RM_SUB_TYPE        :=  recRMDtl.CYRUP_TYPE_DATA;
                vCPSTD.CPSTD_RM_SEQUENCE_REAL   :=  pRM_SEQUENCE;
                                                   
                insert into CST_PRODUCT_SEQ_TAB_DTL values vCPSTD;
                
            end loop;
                
                            
       end loop;		
    
    end pIns_Rm_Uneven_Packing;
    
    procedure pLoad_Data(
                pUserId varchar2
                ,pCYL_TYPE varchar2
                ,pCYL_LEFT_NO number:=null 
                ) is
        vCPSTH CST_PRODUCT_SEQ_TAB_HDR%rowtype;                
    begin
        -- load into header        
        for recDt in (
            
            select a.*,CYC_DATA_VALUE
            from 
                (
                
                select * 
                from cst_yarn_left a
                where CYL_PRS_TYPE='20210800119' 
                and CYL_TYPE = nvl(pCYL_TYPE,CYL_TYPE)
                and CYL_LEFT_NO=nvl(pCYL_LEFT_NO,CYL_LEFT_NO)
                and CYL_LEFT_NO not in (
                
                select MPD_VALUE from mst_param_Data 
                where MPD_MPDK_KEY = 'CST_PROD_SEQ_SKIP'
    
                
                )
                
                ) a
                ,cst_yarn_calculation b
            where CYL_SYS_ID=CYC_CYL_SYS_ID
            and CYC_TOP_NO=2
             
        ) loop
        
            delete from CST_PRODUCT_SEQ_TAB_DTL
            where CPSTD_CPSTH_SYS_ID in (
            select CPSTH_SYS_ID from CST_PRODUCT_SEQ_TAB_HDR
            where CPSTH_FG_LEFT_NO=recDt.CYL_LEFT_NO
            );
        
            delete from CST_PRODUCT_SEQ_TAB_HDR
            where CPSTH_FG_LEFT_NO=recDt.CYL_LEFT_NO;
        
            vCPSTH := null;
            vCPSTH.CPSTH_SYS_ID:='123';
            vCPSTH.CPSTH_CREATED_BY:=pUserId;
            vCPSTH.CPSTH_CREATED_TIMESTAMP:=sysdate;
            vCPSTH.CPSTH_FG_ITEM_CODE:=recDt.CYL_ITEM_CODE;
            vCPSTH.CPSTH_FG_TOP_2:=recDt.CYC_DATA_VALUE;
            vCPSTH.CPSTH_FG_ITEM_NAME:=recDt.CYL_NAME;
            vCPSTH.CPSTH_FG_CYL_TYPE:=recDt.CYL_TYPE;
            vCPSTH.CPSTH_FG_SHADE_CODE:=recDt.CYL_SHADE_CODE;
            vCPSTH.CPSTH_FG_SHADE_NAME:=recDt.CYL_SHADE_NAME;
            vCPSTH.CPSTH_FG_LEFT_NO:=recDt.CYL_LEFT_NO;
            
            begin
                select CYCPS_SEQ_NO into vCPSTH.CPSTH_FG_SEQUENCE 
                from CST_YARN_CALC_PROD_SEQ b
                where CYCPS_PRODUCT_TYPE=recDt.CYL_TYPE
                and rownum=1;
            exception
                when no_Data_found then vCPSTH.CPSTH_FG_SEQUENCE :=null;
            end;
            
            insert into CST_PRODUCT_SEQ_TAB_HDR values vCPSTH;
            -- Get RM Dtl
            declare
                vRM_SEQUENCE number;
                vCPSTD CST_PRODUCT_SEQ_TAB_DTL%rowtype;
                vStsLoop boolean;
            begin
                for recCPSTH in (
                
                    select * 
                    from CST_PRODUCT_SEQ_TAB_HDR
                    where CPSTH_FG_LEFT_NO=recDt.CYL_LEFT_NO
                         
                ) loop
                    
                    -- check RM 1st vRM_SEQUENCE
                    for recRM in (
                    
                        select * 
                        from    cst_yarn_calculation a
                                ,cst_yarn_rm_hdr b
                        where a.CYC_CYL_SYS_ID=recDt.CYL_SYS_ID
                        and a.CYC_TOP_NO=55      
                        and a.CYC_SYS_ID=b.CYRH_CYC_SYS_ID
                        and a.CYC_FORMULA_TYPE = 'Raw_Material'
                        
                    )loop
                        vRM_SEQUENCE :=1;
                        if recRM.CYRH_TYPE='Store Rate' then                            
                            pIns_Rm_StoreRate(recRM.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recRM.CYC_LEFT_NO);
                        elsif recRM.CYRH_TYPE='Captive Cost' then
                            pIns_Rm_Captive(recRM.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recRM.CYC_LEFT_NO);
                        elsif recRM.CYRH_TYPE='Multi Yarn' then
                            pIns_Rm_MultiYarn(recRM.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recRM.CYC_LEFT_NO);
                        elsif recRM.CYRH_TYPE='Uneven Packing' then
                            pIns_Rm_Uneven_Packing(recRM.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recRM.CYC_LEFT_NO);
                        end if;
                    end loop;
                    -- check RM 1st vRM_SEQUENCE
                    
                    -- loop RM in 1st vRM_SEQUENCE
                    for recIn1Seq in (                    
                        select * 
                        from CST_PRODUCT_SEQ_TAB_DTL a
                             ,cst_yarn_calculation b
                             ,cst_yarn_rm_hdr c                        
                        where CPSTD_CPSTH_SYS_ID=recCPSTH.CPSTH_SYS_ID
                        and CPSTD_RM_SEQUENCE_REAL=1--CPSTD_RM_SEQUENCE=1
                        and CPSTD_CYL_SYS_ID=CYC_CYL_SYS_ID
                        and CYC_SYS_ID=CYRH_CYC_SYS_ID
                        and CYC_TOP_NO=55
                        order by nvl(CPSTD_RM_SUB_SEQUENCE,0)
                    ) loop                        
                        if recIn1Seq.CYRH_TYPE='Store Rate' then
                            vRM_SEQUENCE :=vRM_SEQUENCE+1;                            
                            pIns_Rm_StoreRate(recIn1Seq.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recIn1Seq.CYC_LEFT_NO);
                        elsif recIn1Seq.CYRH_TYPE='Captive Cost' then
                            vRM_SEQUENCE :=vRM_SEQUENCE+1;
                            pIns_Rm_Captive(recIn1Seq.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recIn1Seq.CYC_LEFT_NO);
                        elsif recIn1Seq.CYRH_TYPE='Multi Yarn' then
                            vRM_SEQUENCE :=vRM_SEQUENCE+1;
                            pIns_Rm_MultiYarn(recIn1Seq.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recIn1Seq.CYC_LEFT_NO);
                        elsif recIn1Seq.CYRH_TYPE='Uneven Packing' then
                            vRM_SEQUENCE :=vRM_SEQUENCE+1;
                            pIns_Rm_Uneven_Packing(recIn1Seq.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recIn1Seq.CYC_LEFT_NO);                            
                        end if;
                        
                        loop
                            vStsLoop := false;
                            for recLp in (
                            
                                select * 
                                from CST_PRODUCT_SEQ_TAB_DTL a
                                     ,cst_yarn_calculation b
                                     ,cst_yarn_rm_hdr c                        
                                where CPSTD_CPSTH_SYS_ID=recCPSTH.CPSTH_SYS_ID
                                and CPSTD_RM_SEQUENCE_REAL=vRM_SEQUENCE
                                and CPSTD_CYL_SYS_ID=CYC_CYL_SYS_ID
                                and CYC_SYS_ID=CYRH_CYC_SYS_ID
                                and CYC_TOP_NO=55
                                order by nvl(CPSTD_RM_SUB_SEQUENCE,0)
                            
                            ) loop
                                                            
                                if recLp.CYRH_TYPE='Store Rate' then                            
                                    vRM_SEQUENCE :=vRM_SEQUENCE+1;
                                    pIns_Rm_StoreRate(recLp.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recLp.CYC_LEFT_NO);
                                    vStsLoop := true;
                                elsif recLp.CYRH_TYPE='Captive Cost' then
                                    vRM_SEQUENCE :=vRM_SEQUENCE+1;
                                    pIns_Rm_Captive(recLp.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recLp.CYC_LEFT_NO);
                                    vStsLoop := true;
                                elsif recLp.CYRH_TYPE='Multi Yarn' then
                                    vRM_SEQUENCE :=vRM_SEQUENCE+1;
                                    vStsLoop := true;
                                    pIns_Rm_MultiYarn(recLp.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recLp.CYC_LEFT_NO);
                                elsif recIn1Seq.CYRH_TYPE='Uneven Packing' then
                                    vRM_SEQUENCE :=vRM_SEQUENCE+1;
                                    vStsLoop := true;
                                    pIns_Rm_Uneven_Packing(recLp.CYRH_SYS_ID,recCPSTH.CPSTH_SYS_ID,vRM_SEQUENCE,recLp.CYC_LEFT_NO);                               
                                end if;
                            
                            end loop;                                                    
                            exit when not vStsLoop;                                 
                        end loop;                                                    
                    end loop;  
                    -- loop RM in 1st vRM_SEQUENCE
                      
                                                                     
                end loop;              
            end;                                                                                                      
            commit;                                                                                   
            -- Get RM Dtl    
        end loop; 
        -- load into header
                                  
    end pLoad_Data;
    
    -- update Sequence
/* Formatted on 2026/05/09 06:14 (Formatter Plus v4.8.8) */
    PROCEDURE pupd_seq(pCYL_LEFT_NO number:=null)
    IS
       vseq      NUMBER;
       vseqtmp   NUMBER;
    BEGIN
       FOR rechdr IN (SELECT   cpsth_sys_id, cpsth_fg_sequence, cpsth_fg_left_no,
                               MAX (cpstd_rm_sequence_real)
                          FROM cst_product_seq_tab_hdr a,
                               cst_product_seq_tab_dtl b
                         WHERE cpsth_sys_id = cpstd_cpsth_sys_id
                      --and CPSTH_FG_LEFT_NO = 12917
                      GROUP BY cpsth_sys_id, cpsth_fg_sequence, cpsth_fg_left_no
                      --having max(CPSTD_RM_SEQUENCE_REAL)  > 4
                      ORDER BY cpsth_fg_sequence DESC, cpsth_fg_left_no)
       LOOP
          vseq := NULL;
          vseqtmp := NULL;

          FOR recdtl IN (SELECT   *
                             FROM cst_product_seq_tab_dtl
                            WHERE cpstd_cpsth_sys_id = rechdr.cpsth_sys_id
                         ORDER BY cpstd_rm_sequence_real DESC,
                                  NVL (cpstd_rm_sub_sequence, 0))
          LOOP
             IF vseq IS NULL
             THEN
                vseq := 1;
                vseqtmp := recdtl.cpstd_rm_sequence_real;
             ELSE
                IF vseqtmp <> recdtl.cpstd_rm_sequence_real
                THEN
                   vseqtmp := recdtl.cpstd_rm_sequence_real;
                   vseq := vseq + 1;
                END IF;
             END IF;

             UPDATE cst_product_seq_tab_dtl
                SET cpstd_rm_sequence = vseq
              WHERE cpstd_sys_id = recdtl.cpstd_sys_id;

             COMMIT;
          END LOOP;
       END LOOP;
    END; 
            -- update Sequence  

    FUNCTION get_data(pCYL_LEFT_NO number:=null) RETURN COSTING_PRODUCT_SEQ_TAB PIPELINED IS
    BEGIN
        FOR rec IN (
            
            select
                CPSTH_FG_ITEM_CODE FG_ITEM_CODE
                ,CPSTH_FG_TOP_2 FG_TOP_2
                ,CPSTH_FG_ITEM_NAME FG_ITEM_NAME
                ,CPSTH_FG_CYL_TYPE FG_CYL_TYPE
                ,CPSTH_FG_SHADE_CODE FG_SHADE_CODE
                ,CPSTH_FG_SHADE_NAME FG_SHADE_NAME
                ,CPSTH_FG_LEFT_NO FG_LEFT_NO
                ,CPSTH_FG_SEQUENCE FG_SEQUENCE
                ,CPSTD_RM_ITEM_CODE RM_ITEM_CODE
                ,CPSTD_RM_TOP_2 RM_TOP_2
                ,CPSTD_RM_ITEM_NAME RM_ITEM_NAME
                ,CPSTD_RM_CYL_TYPE RM_CYL_TYPE
                ,CPSTD_RM_SHADE_CODE RM_SHADE_CODE
                ,CPSTD_RM_SHADE_NAME RM_SHADE_NAME
                ,CPSTD_RM_LEFT_NO RM_LEFT_NO
                ,CPSTD_CYL_SYS_ID    
                ,CPSTD_RM_SEQUENCE RM_SEQUENCE
                ,CPSTD_RM_TYPE
                ,CPSTD_RM_SUB_SEQUENCE RM_SUB_SEQUENCE                                            
                ,CPSTD_RM_LEFT_NO_INTO
                ,CPSTD_RM_SUB_TYPE
                ,CPSTD_RM_SEQUENCE_REAL
            from CST_PRODUCT_SEQ_TAB_HDR a
                 ,CST_PRODUCT_SEQ_TAB_DTL b
            where CPSTH_SYS_ID=CPSTD_CPSTH_SYS_ID            
            --and CPSTH_FG_LEFT_NO = nvl(pCYL_LEFT_NO,CPSTH_FG_LEFT_NO)
            order by CPSTH_FG_SEQUENCE,CPSTH_FG_LEFT_NO,CPSTD_RM_SEQUENCE,nvl(CPSTD_RM_SUB_SEQUENCE,0)
            

        ) LOOP
            PIPE ROW(COSTING_PRODUCT_SEQ_OBJ(
                rec.FG_ITEM_CODE --1
                ,rec.FG_TOP_2 --2
                ,rec.FG_ITEM_NAME --3
                ,rec.FG_CYL_TYPE --4
                ,rec.FG_SHADE_CODE --5
                ,rec.FG_SHADE_NAME --6
                ,rec.FG_LEFT_NO --7
                ,rec.FG_SEQUENCE --8
                ,rec.RM_ITEM_CODE --9
                ,rec.RM_TOP_2 --10
                ,rec.RM_ITEM_NAME --11
                ,rec.RM_CYL_TYPE --12
                ,rec.RM_SHADE_CODE --13
                ,rec.RM_SHADE_NAME --14
                ,rec.RM_LEFT_NO --15
                ,rec.CPSTD_CYL_SYS_ID --16
                ,rec.RM_SEQUENCE --17
                ,rec.CPSTD_RM_TYPE --18
                ,rec.RM_SUB_SEQUENCE --19                
                ,rec.CPSTD_RM_SUB_TYPE --21
                ,rec.CPSTD_RM_LEFT_NO_INTO --20
                ,rec.CPSTD_RM_SEQUENCE_REAL --21
            ));
        END LOOP;

        RETURN;
    END;
    
    FUNCTION get_LeftNo_Product(pCYL_LEFT_NO number:=null) RETURN varchar2 is
    begin
        null;
    end;
END; 
/
