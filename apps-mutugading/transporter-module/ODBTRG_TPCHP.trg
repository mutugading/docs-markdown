CREATE OR REPLACE TRIGGER MGTDAT.ODBTRG_TPCHP
--after insert or update ON MGTDAT.OT_GR_HEAD for each row
before update on MGTDAT.OT_GR_HEAD for each row
declare
cursor c_data (p_flex_05 varchar2, p_supp_code varchar2) is
select mtm_no, 
       :new.gh_dt dt, 
       mtm_transp_name transp_name, 
       mtm_truck_type truck_type, 
       mtm_truck_cap truck_cap, 
       mtm_destination destination, 
       :new.gh_flex_03 truck_no, 
       :new.gh_flex_04 driver, 
       :new.gh_txn_code gh_txn, 
       :new.gh_no   gh_no, 
       :new.gh_supp_code, 
       to_number(:new.gh_flex_06) gross_wt, 
       to_number(:new.gh_flex_06) qty, 
       mtr_rate rate, 
       to_number(:new.gh_flex_06) * mtr_rate amount, 
       mtr_destination destination_2, 
       mtr_truck_type truck_type_2,
       mtr_rate_type rate_type,
       mtr_priority priority_1
from mgt_transp_master, 
     mgt_transp_rate
where mtm_type = 'STO'
and mtm_no     = mtr_mtm_no       
and nvl(mtr_rate,0) != 0
and nvl(mtm_frz_flag_num,2) = 2
and mtm_transp_name = p_flex_05
and mtm_cust_supp   = p_supp_code 
and not exists (select 'XXX' from mgt_transp_detail_dn where mtdd_dn_txn_code ||'-'|| mtdd_dn_no = :new.gh_txn_code ||'-'|| :new.gh_no);

cursor c_detail (p_gh_sys_id number) is
select round(sum(gi_qty_bu)/1000,3) nett_qty 
from ot_gr_item 
where gi_gh_sys_id = p_gh_sys_id;

    v_id number;
    v_dn_id number;
    v_dncost_id number;
    v_no number;
    v_year number;
    v_next_no number;
    v_check number;  
    v_count number;
    v_check_grn number;
              
begin  
    if nvl(:new.gh_appr_status, 1) = 3 then --and nvl(:new.gh_status, 0) = 0 then 
        --if :new.gh_txn_code = 'CHPGRN' and :new.gh_ref_txn_code = 'JPO' and nvl(:new.gh_appr_status,1) = 3 then --Remark on 17 Jan 2022
        if nvl(:new.gh_txn_code,:old.gh_txn_code) = 'CHPGRN' and nvl(:new.gh_ref_txn_code,:old.gh_ref_txn_code) = 'JPO' then   
            --Added by Aam on 17 Mar 2022
            begin                    
                select count(b.gb_batch_no) batch_no 
                into v_count
                from ot_gr_item a,
                     ot_gr_batch b
                where a.gi_sys_id = b.gb_gi_sys_id
                and a.gi_gh_sys_id = nvl(:new.gh_sys_id, :old.gh_sys_id);   
            end;
            --End Added
            if v_count != 0 then            
                --Added by Aam on 17 Jan 2022
                begin
                    select count(mtm_no)
                    into v_check
                    from mgt_transp_master
                    where mtm_type      = 'STO' 
                    and nvl(mtm_frz_flag_num,2) = 2
                    and mtm_transp_name = nvl(:new.gh_flex_05,:old.gh_flex_05) 
                    and mtm_cust_supp   = nvl(:new.gh_supp_code,:old.gh_supp_code);
                end;        
                if v_check != 0 and nvl(nvl(:new.gh_appr_status,:old.gh_appr_status),1) = 3 then
                --End Added
                    begin
                        select count(mtdd_mth_sys_id) 
                        into v_check_grn 
                        from mgt_transp_detail_dn 
                        where mtdd_dn_txn_code ||'-'|| mtdd_dn_no = nvl(:new.gh_txn_code,:old.gh_txn_code) ||'-'|| nvl(:new.gh_no,:old.gh_no);
                    end;
                    if v_check_grn = 0 then    
                        for cd in c_data (nvl(:new.gh_flex_05,:old.gh_flex_05), nvl(:new.gh_supp_code,:old.gh_supp_code)) loop
                            --exit when c_data%notfound; --Open Remark 10 April 2023                        
                            --Added by Aam on 10 Mar 2021
                            if sql%notfound then 
                               raise_application('OP',2441452,'','','','','', '', '','');
                               exit when c_data%notfound;
                            else                               
                                for cd_det in c_detail (nvl(:new.gh_sys_id,:old.gh_sys_id)) loop
                                    if cd_det.nett_qty <= (nvl(:new.gh_flex_06,:old.gh_flex_06)) then  
                                        --if (nvl(:new.gh_flex_06,:old.gh_flex_06)) <= 50500 then --Added by Aam on 5 Nov 2021
                                        --if (nvl(:new.gh_flex_06,:old.gh_flex_06)) <= 52000 then --Added by Aam on 31 Mar 2022
                                        --if (nvl(:new.gh_flex_06,:old.gh_flex_06)) <= 57000 then --Added by Aam on 25 Apr 2022
                                        if (nvl(:new.gh_flex_06,:old.gh_flex_06)) <= 60000 then --Added by Aam on 09 Dec 2022                                                
                                            begin 
                                                select mgt_transp_head_seq.nextval into v_id from dual;    
                                            end;
                                            begin
                                                select nvl(max(mth_transp_no),0) + 1
                                                into v_no
                                                from mgt_transp_head
                                                where mth_txn_code = 'TPCHP' 
                                                --and to_char(mth_dt,'YYYY') = to_char(sysdate,'YYYY'); --remark 18 jan 2022 
                                                and substr(mth_transp_no,1,4) = to_char(sysdate,'YYYY'); 
                                            exception
                                                when others then
                                                    v_no := 1;
                                            end;
                                            if v_no = 1 then        
                                                v_year    := to_char(to_date(sysdate),'RRRR');    
                                                v_next_no := to_number(v_year||lpad(v_no,6,0));                    
                                            elsif v_no != 1 then
                                                v_next_no := v_no;                    
                                            end if;                       
                                            insert into mgt_transp_head (
                                                                         mth_sys_id,
                                                                         mth_txn_code,
                                                                         mth_transp_no,
                                                                         mth_no,
                                                                         mth_dt,
                                                                         mth_transp_name,
                                                                         mth_truck_type,
                                                                         mth_truck_cap,
                                                                         mth_destination,
                                                                         mth_no_police,
                                                                         mth_driver,
                                                                         mth_status,
                                                                         mth_cr_uid,
                                                                         mth_cr_dt
                                                                        )       
                                                                values  (
                                                                         v_id,
                                                                         'TPCHP',
                                                                         v_next_no,
                                                                         cd.mtm_no,
                                                                         cd.dt,
                                                                         cd.transp_name,
                                                                         cd.truck_type,
                                                                         cd.truck_cap,
                                                                         cd.destination,
                                                                         cd.truck_no,
                                                                         cd.driver,
                                                                         'Approved',
                                                                         :new.gh_cr_uid,
                                                                         sysdate
                                                                        );                     
                                            
                                            begin    
                                                select mgt_transp_detail_dn_seq.nextval into v_dn_id from dual;
                                            end;            
                                            insert into mgt_transp_detail_dn (
                                                                              mtdd_sys_id,
                                                                              mtdd_mth_sys_id,
                                                                              mtdd_dn_txn_code,
                                                                              mtdd_dn_no,
                                                                              mtdd_dn_qty,
                                                                              mtdd_used_status,
                                                                              mtdd_cr_uid,
                                                                              mtdd_cr_dt
                                                                             )
                                                                    values  (v_dn_id,
                                                                             v_id,                                         
                                                                             cd.gh_txn,
                                                                             cd.gh_no,
                                                                             cd.qty,
                                                                             'N',                   
                                                                             :new.gh_cr_uid,
                                                                             sysdate
                                                                            ); 
                                            begin    
                                                select mgt_transp_detail_cost_seq.nextval into v_dncost_id from dual;
                                            end;            
                                            insert into mgt_transp_detail_cost (
                                                                                mtdc_sys_id,
                                                                                mtdc_mth_sys_id,
                                                                                mtdc_priority,
                                                                                mtdc_rate_type,
                                                                                mtdc_qty,
                                                                                mtdc_rate,
                                                                                mtdc_total_rate,
                                                                                mtdc_destination,
                                                                                mtdc_truck_type,
                                                                                mtdc_cr_uid,
                                                                                mtdc_cr_dt
                                                                               )  
                                                                       values ( 
                                                                                v_dncost_id,
                                                                                v_id,                                         
                                                                                cd.priority_1,
                                                                                cd.rate_type,
                                                                                cd.qty,
                                                                                cd.rate,
                                                                                cd.amount,
                                                                                cd.destination_2,  
                                                                                cd.truck_type_2,                 
                                                                                :new.gh_cr_uid,
                                                                                sysdate
                                                                              ); 
                                        else
                                            raise_application('OP',2441456,'','','','','', '', '',''); 
                                        end if;
                                    else
                                       raise_application('OP',2441453,'','','','','', '', '',''); 
                                    end if;
                                end loop;
                            end if;
                        end loop;
                    end if;            
                else
                    raise_application('OP',2441461,'','','','','', '', '',''); 
                end if;
            else
                raise_application('OP',2441464,'','','','','', '', '','');
            end if;
        end if;  
    end if;              
end;
/