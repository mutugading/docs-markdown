package body        pkg_transporter is 

procedure jv_provision (p_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tp_id,
       month_mm,
       year_yy,
       doc_ref,
       jv_dt  
from 
(
select tp_id,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PROVISION YARN TRANSPORT Month ' ||to_char(p_date,'Mon YYYY') doc_ref,
       p_date jv_dt       
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,4) = 'TPDN'
group by tp_id         
)
/*
where not exists(select 'XXX' 
                  from  
                ( select th_doc_cal_month, th_doc_cal_year, th_doc_ref
                  from ft_unposted_trans_header
                  where th_tran_code = 'JV'
                  union all
                  select th_doc_cal_month, th_doc_cal_year, th_doc_ref
                  from ft_cur_trans_header
                  where th_tran_code = 'JV'
                  union all
                  select th_doc_cal_month, th_doc_cal_year, th_doc_ref
                  from ft_prv_trans_header
                  where th_tran_code = 'JV'
                 ) 
                 where th_doc_cal_month = month_mm
                 and th_doc_cal_year    = year_yy
                 and th_doc_ref         = doc_ref
                )*/
group by tp_id,
         month_mm,
         year_yy,
         doc_ref,
         jv_dt;

cursor c_data (p_sysid number) is            
select  tp_id,
        null tp_no, 
        null tp_dt,
        null tp_code,
        tp_name,
        null tp_truck_type,
        0 tp_cap,
        null tp_destination,
        null tp_pol_no,
        null tp_driver,
        0 tp_qty,
        0 tp_gross_qty,
        sum(amt) amt, 
        sum(round(amt * curs_usd_b(last_day(trunc(jv_dt,'MM')), 'IDR'),2)) amt_usd,                       
        curr,
        drcr,
        main_acnt,
        jv_dt,
        doc_ref
from
(
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt, 
        'IDR' curr,
        'D' drcr,
        '404001' main_acnt,
        p_date jv_dt, 
        'PROVISION YARN TRANSPORT Month ' ||to_char(p_date,'Mon YYYY')||', '||tp_name doc_ref 
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,4) = 'TPDN'
)  
group by tp_id,
         tp_name,
         curr,
         drcr,
         main_acnt,
         jv_dt,
         doc_ref
union all               
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        amt, 
        round(amt * curs_usd_b(last_day(trunc(jv_dt,'MM')), 'IDR'),2) amt_usd,                       
        curr,
        drcr,
        main_acnt,
        jv_dt,
        doc_ref
from
(
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt, 
        'IDR' curr,
        'C' drcr,
        '208027' main_acnt,
        p_date jv_dt,         
        'Month ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_no || ' ' || tp_name doc_ref        
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,4) = 'TPDN'
)
order by drcr desc, tp_no;
 
    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_auth_sys_id number;
    v_iden varchar2(12);
      
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select --tdoc_cal_year || lpad (tdoc_cur_no + 1, 6, 0)
                   tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0) --Added by Aam on 01 Apr 2021
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'JV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year; --Added by Aam on 01 Apr 2021
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;       
        DUMMY_MGT('2 v_doc_no : '||v_doc_no); 
        begin
            select count(th_doc_no)
            into v_check_doc_no
            from ft_unposted_trans_header
            where th_tran_code = 'JV' 
            and th_doc_no = v_doc_no;
        end;
        if v_check_doc_no != 0 then
            v_doc_no := v_doc_no + 1;
        end if;        
        DUMMY_MGT('3 v_check_doc_no : '||v_check_doc_no); 
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_annotation,
                                             th_cr_uid,
                                             th_cr_dt
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'JV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.jv_dt, 
                                             ch.jv_dt,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_ref,
                                             ch.doc_ref,
                                             p_user_id,
                                             sysdate);
        DUMMY_MGT('4 Header OK');        
        begin
            select tauth_sys_id.nextval into v_auth_sys_id from dual;
        end;
        begin
            select tauth_tbl_identifier
            into v_iden
            from
            (
            select  tauth_txn_code, 
                    tauth_tbl_identifier 
            from ft_txn_auth 
            group by tauth_txn_code, 
                     tauth_tbl_identifier
            )
            where tauth_txn_code = 'JV';
        end;
        insert into ft_txn_auth (   tauth_sys_id,
                                    tauth_head_sys_id,
                                    tauth_comp_code,
                                    tauth_txn_code,
                                    tauth_doc_no,
                                    tauth_seq_no,
                                    tauth_group_code,
                                    tauth_cr_uid,
                                    tauth_cr_dt,
                                    tauth_doc_dt,
                                    tauth_acnt_year, 
                                    tauth_tbl_identifier 
                                )
                    values      (   v_auth_sys_id,
                                    vh_sys_id,
                                    '002',
                                    'JV', 
                                    v_doc_no, 
                                     1,
                                     '0',
                                    p_user_id,
                                    sysdate,
                                    ch.jv_dt,
                                    v_acnt_year,
                                    v_iden 
                                );         
        for cd in c_data (ch.tp_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'JV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.jv_dt,
                                                    cd.main_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    cd.amt_usd,
                                                    cd.amt_usd,
                                                    cd.amt_usd,
                                                    cd.amt,
                                                    cd.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tp_no,
                                                    to_char(cd.tp_dt,'DDMMYYYY'),
                                                    cd.tp_name,
                                                    cd.tp_destination,
                                                    cd.tp_pol_no,
                                                    cd.tp_driver,                                                     
                                                    p_user_id,
                                                    sysdate);
            v_seq_no := v_seq_no + 1;                
            begin
                update mgt_tp_provision
                set tp_jv_no    = 'JV' || '-' || v_doc_no,  
                    tp_status   = 'Posted',
                    tp_up_dt    = sysdate,
                    tp_up_uid   = p_user_id,
                    tp_jv_dt    = p_date  --added by Aam on 28 Oct 2021
                where tp_no     = cd.tp_no
                and tp_name     = cd.tp_name;
            end;
        end loop;
        DUMMY_MGT('5 Detail OK');
    end loop;
    commit;
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;
    DUMMY_MGT('6 v_check_docno : '||v_check_docno);
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'JV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure tjv_bill (p_tpb_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       tpb_supp_name ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_supp_code supp_code,       
       tpb_due_date due_date, /* Added by Aam on 13 Apr 2022*/
       tpb_fp_no fp_no,
       tpb_fp_dt fp_dt,
       tpb_bill_amt dpp,
       tpb_ppn_amt  ppn_amt,
       tpb_ppn      ppn_percentage,
       round(nvl(tpb_ppn_amt,0) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) ppn_amt_usd       
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sys_id
and not exists(select 'XXX' 
               from  
                  (select th_flex_10
                   from ft_unposted_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_cur_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_prv_trans_header
                   where th_tran_code = 'TJV'
                  ) 
               where th_flex_10 = tpb_trx_no
              );

cursor c_data (p_tpb_sysid number) is            
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       --null tpb_fp_no,
       decode(substr(tpb_fp_no,1,2),'08',tpb_fp_no, null) tpb_fp_no, 
       null tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_total amt, 
       round(tpb_total * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'C' drcr,
       '203001' main_acnt,
       tpb_supp_code sub_acnt,
       tpb_bill_amt bill_amt,
       null ppn,
       --null ppn_amt,
       tpb_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpb_due_date due_date,
       null supp_code       
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
union all
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       null tpb_fp_no, 
       null tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_pph_amt amt, 
       round(tpb_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'C' drcr,
       '206005' main_acnt,
       null sub_acnt,
       tpb_bill_amt bill_amt,
       null ppn,
       null ppn_amt,
       tpb_pph pph,
       tpb_pph_amt pph_amt,
       null amt_bill,
       tpb_due_date due_date,
       tpb_supp_code supp_code
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
and nvl(tpb_pph_amt,0) != 0
union all
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' FP No. '||tpb_fp_no doc_desc,
       p_date jv_dt,
       tpb_ppn_amt amt, 
       round(tpb_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'D' drcr,
       case 
        when substr(tpb_fp_no,1,2) = '05' then '108005' 
        else '108004' 
       end main_acnt,
       null sub_acnt,
       tpb_bill_amt bill_amt,
       tpb_ppn ppn,
       tpb_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpb_due_date due_date,
       tpb_supp_code supp_code
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
and nvl(tpb_ppn_amt,0) != 0
and substr(tpb_fp_no,1,2) not in ('08')
union all
select tp_no           tpb_trx_no, 
       tp_dt           tpb_dt,
       tp_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tp_destination  destination,
       tp_pol_no       pol,
       tp_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_name  || ' ' || tp_no doc_ref,
       'ANGKUTAN BENANG PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tp_no doc_desc,
       p_date jv_dt,
       --remark by Aam  on 20 May 2022
       /*
       decode(round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)),0,tp_amt_typing, round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)))  amt,
       decode(round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),0, round(tp_amt_typing * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),
              round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       */
       decode(nvl(tp_amt_pph_grossup,0),0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt,
       decode(nvl(tp_amt_pph_grossup,0),0, round((nvl(tp_amt,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2)) amt_usd,       
       'IDR' curr,
       'D' drcr,
       tp_main_acnt main_acnt,
       null sub_acnt,
       null bill_amt,
       tpb_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tp_amt_typing amt_bill,
       tp_due_date due_date,
       null supp_code       
from mgt_tp_provision, mgt_tp_provision_bill
where tp_tpb_sys_id = tpb_sys_id 
and tp_tpb_sys_id   = p_tpb_sysid
and (tp_status = 'Posted' or tp_status = 'Unposted')
and tp_no like 'TPDN%'
and tp_jv_no is not null
and tp_tjv_no is null
union all
select tp_no           tpb_trx_no, 
       tp_dt           tpb_dt,
       tp_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tp_destination  destination,
       tp_pol_no       pol,
       tp_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_name  || ' ' || tp_no doc_ref,
       'ANGKUTAN BENANG PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tp_no doc_desc,
       p_date jv_dt,
       --remark by Aam  on 20 May 2022
       /*
       decode(round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)),0,tp_amt_typing, round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)))  amt,
       decode(round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),0, round(tp_amt_typing * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),
              round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       */
       --remark by Aam  on 21 Dec 2022
       /*
       decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt,
       decode(tp_amt_pph_grossup,0, round((nvl(tp_amt,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2)) amt_usd,
       */
       decode(decode(nvl(tp_amt_pph_grossup,0),0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02))),0, tp_amt_typing,
              decode(nvl(tp_amt_pph_grossup,0),0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)))
              ) amt,
       decode(decode(nvl(tp_amt_pph_grossup,0),0, round((nvl(tp_amt,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2)),
              0, round(tp_amt_typing * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),
              decode(nvl(tp_amt_pph_grossup,0),0, round((nvl(tp_amt,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2))
              ) amt_usd,
       'IDR' curr,
       'D' drcr,
       --decode(tp_no, 'OTHERS', '401001', tp_main_acnt) main_acnt,
       tp_main_acnt main_acnt,
       null sub_acnt,
       null bill_amt,
       tpb_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tp_amt_typing amt_bill,
       tp_due_date due_date,
       null supp_code       
from mgt_tp_provision, mgt_tp_provision_bill
where tp_tpb_sys_id = tpb_sys_id 
and tp_tpb_sys_id   = p_tpb_sysid
and (tp_status = 'Posted' or tp_status = 'Unposted')
and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
--and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG')
and tp_jv_no is not null
and tp_tjv_no is null;

    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_tot_amt        number;   
    v_tot_amt_typing number;
    v_tot_amt_diff   number;
    v_drcr varchar2(1);
    v_amt_usd number;  
    v_gross_up varchar2(1);
    v_amt_grossup number;
    v_amt_grossup_com number;
    v_amt_grossup_pph number := 0;
    v_amt_grossup_ppn number := 0;
    v_amt_usd_grossup number; 
    v_amt_grossup_tot number; 
    v_check_1         number;
    v_cr varchar2(1);
    v_cr_amt number;
    v_cr_amt_usd number;
    v_dr varchar2(1);
    v_dr_amt number;
    v_dr_amt_usd number;
    v_credit number;
    v_debit number; 
    v_auth_sys_id number;
    v_iden varchar2(12);
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select --tdoc_cal_year || lpad (tdoc_cur_no + 1, 6, 0)
                   tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0) --Added by Aam on 01 Apr 2021
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'TJV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year; --Added by Aam on 01 Apr 2021
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;        
        DUMMY_MGT('2 v_doc_no : '||v_doc_no);
        begin
            select nvl(supp_flex_06, 'N') gross_up
            into v_gross_up
            from mgt_transp_master,
                 om_supplier
            where mtm_transp_code = supp_code
            and mtm_transp_code = ch.supp_code
            and nvl(supp_frz_flag_num,2) = 2                
            group by supp_flex_06;
        exception
            when others then
                v_gross_up := 'N';        
        end;
        DUMMY_MGT('3 v_gross_up : '||v_gross_up);                            
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_flex_10,
                                             th_cr_uid,
                                             th_cr_dt,
                                             th_flex_03,
                                             th_flex_04,
                                             th_flex_05,
                                             th_flex_06,
                                             th_flex_07,
                                             th_flex_08,
                                             th_flex_09                                             
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'TJV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.tpb_dt, --ch.jv_dt, remarks 03 Feb 2023 by Aam
                                             ch.due_date, -- ch.jv_dt,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_desc,
                                             ch.tpb_trx_no,
                                             p_user_id,
                                             sysdate,
                                             ch.fp_no,
                                             ch.fp_dt,
                                             ch.supp_code,
                                             ch.dpp,
                                             ch.ppn_amt,
                                             ch.ppn_percentage,
                                             ch.ppn_amt_usd
                                             );
        begin
            select tauth_sys_id.nextval into v_auth_sys_id from dual;
        end;
        begin
            select tauth_tbl_identifier
            into v_iden
            from
            (
            select  tauth_txn_code, 
                    tauth_tbl_identifier 
            from ft_txn_auth 
            group by tauth_txn_code, 
                     tauth_tbl_identifier
            )
            where tauth_txn_code = 'TJV';
        end;
        insert into ft_txn_auth (   tauth_sys_id,
                                    tauth_head_sys_id,
                                    tauth_comp_code,
                                    tauth_txn_code,
                                    tauth_doc_no,
                                    tauth_seq_no,
                                    tauth_group_code,
                                    tauth_cr_uid,
                                    tauth_cr_dt,
                                    tauth_doc_dt,
                                    tauth_acnt_year, 
                                    tauth_tbl_identifier 
                                )
                    values      (   v_auth_sys_id,
                                    vh_sys_id,
                                    '002',
                                    'TJV', 
                                    v_doc_no, 
                                     1,
                                     '0',
                                    p_user_id,
                                    sysdate,
                                    ch.jv_dt,
                                    v_acnt_year,
                                    v_iden 
                                );   
        for cd in c_data (p_tpb_sys_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;
            if v_gross_up = 'N' then
                v_amt_grossup     := cd.amt;
                v_amt_usd_grossup := cd.amt_usd;                   
            elsif v_gross_up = 'Y' then                
                if cd.main_acnt = '203001' then --Cr bill_amount
                    /*
                    v_amt_grossup     := cd.bill_amt;
                    v_amt_grossup_com := cd.bill_amt;
                    v_amt_usd_grossup := round(cd.bill_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                    */
                    --Modify by Aam on 16 Sep 2022
                    v_amt_grossup     := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_grossup_com := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_usd_grossup := round((cd.bill_amt + nvl(cd.ppn_amt,0)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                elsif cd.main_acnt = '206005' then --Cr pph
                    v_amt_grossup     := cd.pph_amt;                    
                    v_amt_grossup_pph := cd.pph_amt;
                    v_amt_usd_grossup := round(cd.pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);    
                elsif cd.main_acnt = '108004' then --Dr ppn  --Pos Logistic 
                    v_amt_grossup     := cd.ppn_amt;
                    v_amt_grossup_ppn := cd.ppn_amt;
                    v_amt_usd_grossup := round(cd.ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                        
                --elsif cd.main_acnt = '208027' then --Dr
                --elsif cd.main_acnt in ('208027', '401001', '401009', '401011') then --Dr
                elsif cd.main_acnt in ('208027', '404001', '401009', '401011') then --Dr
                    /*
                    if cd.ppn is null then
                        v_amt_grossup     := round(cd.amt_bill/0.98);
                        v_amt_usd_grossup := round(v_amt_grossup * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2);
                    else 
                        v_amt_grossup     := round(cd.amt_bill/0.98) - round(cd.amt_bill * (cd.ppn/100));
                        v_amt_usd_grossup := round(v_amt_grossup * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                    end if;   
                    */
                    --remark by Aam on 19 May 2022
                    /*
                    -- Added by Aam on 30 Mar 2021
                    if abs(cd.amt_bill - cd.amt) < 1000 then      
                        if cd.ppn is null then
                            v_amt_grossup     := round(cd.amt_bill/0.98);
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                        else 
                            v_amt_grossup     := round(cd.amt_bill/0.98) - round(cd.amt_bill * (cd.ppn/100));
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                        end if;
                    elsif abs(cd.amt_bill - cd.amt) >= 1000 then
                        if cd.ppn is null then
                            v_amt_grossup     := round(cd.amt/0.98);
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                        else 
                            v_amt_grossup     := round(cd.amt/0.98) - round(cd.amt * (cd.ppn/100));
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                        end if;                        
                    end if;
                    -- End Added  
                    -- End Added
                    */ 
                    -- end remark  
                    --Added by Aam on 19 May 2022                         
                    v_amt_grossup     := round(cd.amt);
                    v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                    --End Added                                                                       
                end if;              
            end if;  
            DUMMY_MGT('4 v_amt_grossup : '||v_amt_grossup||', v_amt_usd_grossup : '||v_amt_usd_grossup);                   
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_01,
                                                    td_flex_02,
                                                    td_flex_03,
                                                    td_flex_04,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.due_date, --cd.jv_dt,
                                                    cd.main_acnt,
                                                    cd.sub_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_grossup,
                                                    decode(cd.main_acnt,'108004', nvl(cd.doc_desc,cd.doc_ref)||' FP NO :'||cd.tpb_fp_no, nvl(cd.doc_desc,cd.doc_ref)),
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tpb_fp_no,
                                                    to_char(cd.tpb_fp_dt, 'DDMMYYYY'),
                                                    cd.supp_code,
                                                    cd.tpb_bill_no,
                                                    cd.tpb_trx_no,
                                                    to_char(cd.tpb_dt,'DDMMYYYY'),
                                                    cd.tpb_supp_name,
                                                    cd.destination,
                                                    cd.pol,
                                                    cd.driver,                                                     
                                                    p_user_id,
                                                    sysdate);
                                                    
            v_seq_no := v_seq_no + 1;
            --Added by Aam on 21 May 2022
            begin
                update mgt_tp_provision
                set tp_grossup_value = round(v_amt_grossup - tp_amt)
                where tp_no = cd.tpb_trx_no
                and tp_no like 'TP%';
            end;   
            --End Added                                     
        end loop;
        if v_gross_up = 'N' then
            /*
            begin                        
                select nvl(sum(tp_total_amt),0), nvl(sum(tp_amt_typing),0), nvl(abs(sum(tp_amt_diff)),0) 
                into v_tot_amt, v_tot_amt_typing, v_tot_amt_diff
                from mgt_tp_provision
                where tp_tpb_sys_id = p_tpb_sys_id
                and tp_jv_no is not null; 
            end;
            if v_tot_amt != 0 then                                 
                if v_tot_amt >  v_tot_amt_typing then
                    v_drcr := 'C';
                elsif v_tot_amt < v_tot_amt_typing then
                    v_drcr := 'D';
                end if;
            end if;
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            */
            --Added by Aam on 14 Maret 2023
            --Credit
            begin                        
                select (nvl(tpb_total,0) + nvl(tpb_pph_amt,0))
                into v_credit  
                from mgt_tp_provision_bill
                where tpb_sys_id = p_tpb_sys_id
                and tpb_status is null;
            end;
            --Debit
            begin                        
                select sum(tot)
                into v_debit
                from
                (
                select nvl(tpb_ppn_amt,0) tot
                from mgt_tp_provision_bill
                where tpb_sys_id = p_tpb_sys_id
                union
                select nvl(sum(nvl(tp_total_amt,tp_amt_typing)),0) tot 
                from mgt_tp_provision
                where tp_tpb_sys_id = p_tpb_sys_id
                );                 
            end;    
            v_tot_amt_diff := v_credit - v_debit;            
            if v_tot_amt_diff != 0 then                                 
                if v_tot_amt_diff <  0 then
                    v_drcr := 'C';
                else 
                    v_drcr := 'D';
                end if;
            end if;
            begin
                select round(abs(v_tot_amt_diff) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            --End Added
            DUMMY_MGT('5 v_tot_amt : '||v_tot_amt||', v_tot_amt_typing : '||v_tot_amt_typing||', v_tot_amt_diff : '||v_tot_amt_diff);            
        elsif v_gross_up = 'Y' then 
            /*
            begin                                 
                select sum(round(tp_amt_typing/0.98))
                into v_amt_grossup_tot      
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null
                and tp_tjv_no is null;
            end;
            if (v_amt_grossup_tot + nvl(v_amt_grossup_ppn,0))   > (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)) then
                v_drcr := 'C';
            elsif (v_amt_grossup_tot + nvl(v_amt_grossup_ppn,0)) < (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)) then
                v_drcr := 'D';
            end if;
            v_tot_amt_diff := abs(v_amt_grossup_tot - (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)));
            begin
                select round(v_tot_amt_diff * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            DUMMY_MGT('6 v_amt_grossup_tot : '||v_amt_grossup_tot||', v_tot_amt_diff : '||v_tot_amt_diff);
            */   
            -- Added by Aam on 30 Mar 2021        
            /*
            begin                                 
                select count(tp_tpb_sys_id)
                into v_check_1       
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null                
                and tp_tjv_no is null
                and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS');
                --and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG');
            end;
            */            
            --if v_check_1 = 0 then             
                --Remark by Aam on 20 May 2022
                /* 
                --select nvl(sum(tp_total_amt),0), sum(round(tp_amt_typing/0.98)), round(nvl(abs(sum(tp_amt_diff)),0)/0.98)
                select nvl(sum(tp_total_amt),0), sum(tp_amt_typing), round(nvl(abs(sum(tp_amt_diff)),0)/0.98)
                into v_tot_amt, v_amt_grossup_tot, v_tot_amt_diff       
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null
                and tp_tjv_no is null;
                and abs(tp_amt_diff) > 1000;  
                */
                --End remark
                --Added by Aam on 20 May 2022
               /* select sum(amt), sum(amt_groosup), sum(amt_diff)
                into v_tot_amt,  v_amt_grossup_tot, v_tot_amt_diff                           
                from
                (
                select nvl(tp_total_amt,0) amt, round(nvl(tp_total_amt,0) + nvl(tp_total_amt,0) *0.02) amt_groosup, nvl(tp_amt_typing,0) amt_typing, round(nvl(abs(tp_amt_diff),0)) amt_diff
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null
                and tp_tjv_no is null
                );
                --End Added 
            else*/
                --remark by Aam on 20 May 2022
                /*
                begin                                 
                    select nvl(sum(tp_total_amt),0), sum(round(tp_amt_typing/0.98)), round(nvl(abs(sum(tp_amt_diff)),0)/0.98)
                    into v_tot_amt, v_amt_grossup_tot, v_tot_amt_diff       
                    from mgt_tp_provision
                    where tp_tpb_sys_id   = p_tpb_sys_id
                    and tp_jv_no is not null
                    and tp_tjv_no is null
                    and abs(tp_amt_diff) > 1000
                    and tp_no not in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS');
                end;
                */
                --End remark
                --Added by Aam on 20 May 2022
                /*select sum(amt), sum(amt_groosup), sum(amt_diff)
                into v_tot_amt,  v_amt_grossup_tot, v_tot_amt_diff                           
                from
                (
                select nvl(tp_total_amt,0) amt, round(nvl(tp_total_amt,0) + nvl(tp_total_amt,0) *0.02) amt_groosup, nvl(tp_amt_typing,0) amt_typing, round(nvl(abs(tp_amt_diff),0)) amt_diff
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null
                and tp_tjv_no is null
                and tp_no not in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
                );
                --End Added                
            end if;  */
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_cr, v_cr_amt, v_cr_amt_usd                   
                from
                (
                select 'C' drcr,
                       '203001' main_acnt,
                       --tpb_bill_amt amt,
                       --round(tpb_bill_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                       --Modify by Aam on 16 Sep 2022 
                       tpb_bill_amt + nvl(tpb_ppn_amt,0) amt,
                       round((tpb_bill_amt + nvl(tpb_ppn_amt,0)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd                 
                from mgt_tp_provision_bill
                where tpb_sys_id   = p_tpb_sys_id
                union all
                select 'C' drcr,
                       '206005' main_acnt,
                       tpb_pph_amt amt,
                       round(tpb_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill
                where tpb_sys_id   = p_tpb_sys_id
                and nvl(tpb_pph_amt,0) != 0
                )
                group by drcr;
            end;
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_dr, v_dr_amt, v_dr_amt_usd                   
                from
                (            
                select 'D' drcr,
                       '108004' main_acnt,
                       tpb_ppn_amt amt, 
                       round(tpb_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill
                where tpb_sys_id   = p_tpb_sys_id
                and nvl(tpb_ppn_amt,0) != 0
                union all
                select 'D' drcr,
                       tp_main_acnt main_acnt,
                       decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt,
                       decode(tp_amt_pph_grossup,0, round((nvl(tp_amt,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2)) amt_usd                     
                from mgt_tp_provision, mgt_tp_provision_bill
                where tp_tpb_sys_id = tpb_sys_id 
                and tp_tpb_sys_id   = p_tpb_sys_id
                and (tp_status = 'Posted' or tp_status = 'Unposted')
                and tp_no like 'TPDN%'
                --and tp_jv_no is not null
                --and tp_tjv_no is null
                union all
                select 'D' drcr,
                       tp_main_acnt main_acnt,
                       decode(tp_amt_pph_grossup,0, nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0))*0.02)) ) amt,
                       decode(tp_amt_pph_grossup,0, round((nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round((nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt_typing,0) + nvl(tp_oth_amt,0))*0.02)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR') ,2)) amt_usd              
                from mgt_tp_provision, mgt_tp_provision_bill
                where tp_tpb_sys_id = tpb_sys_id 
                and tp_tpb_sys_id   = p_tpb_sys_id
                and (tp_status = 'Posted' or tp_status = 'Unposted')
                and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
                )
                group by drcr;
            end;                              
            --v_tot_amt_diff := abs(v_tot_amt_diff);
            v_tot_amt_diff := abs(v_cr_amt - v_dr_amt); 
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end; 
            --if (v_tot_amt - v_amt_grossup_tot) < 0 then
            if v_cr_amt < v_dr_amt then
                v_drcr := 'C';
            else
                v_drcr := 'D';
            end if;                       
            DUMMY_MGT('6 v_amt_grossup_tot : '||v_amt_grossup_tot||', v_tot_amt_diff : '||v_tot_amt_diff||', v_amt_usd : '||v_amt_usd );
            -- End Added
        end if;        
        --if v_tot_amt != 0 and v_tot_amt_diff != 0 then
        --if (v_gross_up = 'N' and v_tot_amt != 0 and v_tot_amt_diff != 0) or (v_gross_up = 'Y' and v_tot_amt_diff != 0)then
        if (v_gross_up = 'N' and v_tot_amt_diff != 0) or (v_gross_up = 'Y' and v_tot_amt_diff != 0)then         
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    ch.jv_dt,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    ch.due_date, --ch.jv_dt,
                                                    '404001',
                                                    null,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    ch.tpb_curr,   
                                                    v_drcr,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    abs(v_tot_amt_diff),
                                                    'Different on Provision '|| ch.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    p_user_id,
                                                    sysdate);              
        
        end if;
    end loop;
    commit;
    begin
        update mgt_tp_provision
        set tp_tjv_no       = 'TJV' || '-' || v_doc_no,    
            tp_status       = 'Posted',
            tp_match_status = 'Y',           
            tp_up_dt        = sysdate,
            tp_up_uid       = p_user_id
        where tp_tpb_sys_id     = p_tpb_sys_id
        and tp_jv_no is not null;
    end;
    begin
        update mgt_tp_provision_bill
        set tpb_status   = 'GENERATE',              
            tpb_up_dt    = sysdate,
            tpb_up_uid   = p_user_id--,
            --tpb_bill_dt  = p_date  --added by Aam on 28 Oct 2021
        where tpb_sys_id = p_tpb_sys_id;
    end;        
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;    
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'TJV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure jv_provision_chp (p_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tp_id,
       month_mm,
       year_yy,
       doc_ref,
       jv_dt  
from 
(
select tp_id,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PROVISION CHIPS TRANSPORT Month ' ||to_char(p_date,'Mon YYYY') doc_ref,
       p_date jv_dt       
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,5) = 'TPCHP'
group by tp_id         
)
group by tp_id,
         month_mm,
         year_yy,
         doc_ref,
         jv_dt;

cursor c_data (p_sysid number) is            
select  tp_id,
        null tp_no, 
        null tp_dt,
        null tp_code,
        tp_name,
        null tp_truck_type,
        0 tp_cap,
        null tp_destination,
        null tp_pol_no,
        null tp_driver,
        0 tp_qty,
        0 tp_gross_qty,
        sum(amt) amt, 
        sum(round(amt * curs_usd_b(last_day(trunc(jv_dt,'MM')), 'IDR'),2)) amt_usd,                       
        curr,
        drcr,
        main_acnt,
        jv_dt,
        doc_ref
from
(
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt, 
        'IDR' curr,
        'D' drcr,
        '401001' main_acnt,
        p_date jv_dt, 
        'PROVISION CHIPS TRANSPORT Month ' ||to_char(p_date,'Mon YYYY') ||', '|| tp_name doc_ref 
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,5) = 'TPCHP'
)  
group by tp_id,
         tp_name,
         curr,
         drcr,
         main_acnt,
         jv_dt,
         doc_ref
union all               
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        amt, 
        round(amt * curs_usd_b(last_day(trunc(jv_dt,'MM')), 'IDR'),2) amt_usd,                       
        curr,
        drcr,
        main_acnt,
        jv_dt,
        doc_ref
from
(
select  tp_id,
        tp_no, 
        tp_dt,
        tp_code,
        tp_name,
        tp_truck_type,
        tp_cap,
        tp_destination,
        tp_pol_no,
        tp_driver,
        tp_qty,
        tp_gross_qty,
        decode(tp_amt_pph_grossup,0, nvl(tp_amt,0) + nvl(tp_oth_amt,0), round((nvl(tp_amt,0) + nvl(tp_oth_amt,0)) + ((nvl(tp_amt,0) + nvl(tp_oth_amt,0))*0.02)) ) amt, 
        'IDR' curr,
        'C' drcr,
        '208026' main_acnt,
        p_date jv_dt,         
        'Month ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_no  || ' ' || tp_name doc_ref        
from mgt_tp_provision
where tp_id   = p_sys_id
and tp_status = 'Unposted'
and tp_jv_no is null
and substr(tp_no,1,5) = 'TPCHP'
)
order by drcr desc, tp_no;
 
    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_auth_sys_id number;
    v_iden varchar2(12);
      
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select --tdoc_cal_year || lpad (tdoc_cur_no + 1, 6, 0)
                   tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0) --Added by Aam on 01 Apr 2021 
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'JV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year; --Added by Aam on 01 Apr 2021
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;
        DUMMY_MGT('2 v_doc_no : '||v_doc_no);             
        begin
            select count(th_doc_no)
            into v_check_doc_no
            from ft_unposted_trans_header
            where th_tran_code = 'JV' 
            and th_doc_no = v_doc_no;
        end;
        if v_check_doc_no != 0 then
            v_doc_no := v_doc_no + 1;
        end if;        
        DUMMY_MGT('3 v_check_doc_no : '||v_check_doc_no);
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_annotation,
                                             th_cr_uid,
                                             th_cr_dt
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'JV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.jv_dt, 
                                             ch.jv_dt,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_ref,
                                             ch.doc_ref,
                                             p_user_id,
                                             sysdate);
        begin
            select tauth_sys_id.nextval into v_auth_sys_id from dual;
        end;
        begin
            select tauth_tbl_identifier
            into v_iden
            from
            (
            select  tauth_txn_code, 
                    tauth_tbl_identifier 
            from ft_txn_auth 
            group by tauth_txn_code, 
                     tauth_tbl_identifier
            )
            where tauth_txn_code = 'JV';
        end;
        insert into ft_txn_auth (   tauth_sys_id,
                                    tauth_head_sys_id,
                                    tauth_comp_code,
                                    tauth_txn_code,
                                    tauth_doc_no,
                                    tauth_seq_no,
                                    tauth_group_code,
                                    tauth_cr_uid,
                                    tauth_cr_dt,
                                    tauth_doc_dt,
                                    tauth_acnt_year, 
                                    tauth_tbl_identifier 
                                )
                    values      (   v_auth_sys_id,
                                    vh_sys_id,
                                    '002',
                                    'JV', 
                                    v_doc_no, 
                                     1,
                                     '0',
                                    p_user_id,
                                    sysdate,
                                    ch.jv_dt,
                                    v_acnt_year,
                                    v_iden 
                                );  
        
        DUMMY_MGT('5 Done Insert Header');                 
        for cd in c_data (ch.tp_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'JV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.jv_dt,
                                                    cd.main_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    cd.amt_usd,
                                                    cd.amt_usd,
                                                    cd.amt_usd,
                                                    cd.amt,
                                                    cd.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tp_no,
                                                    to_char(cd.tp_dt,'DDMMYYYY'),
                                                    cd.tp_name,
                                                    cd.tp_destination,
                                                    cd.tp_pol_no,
                                                    cd.tp_driver,                                                     
                                                    p_user_id,
                                                    sysdate);
            v_seq_no := v_seq_no + 1;                
            begin
                update mgt_tp_provision
                set tp_jv_no    = 'JV' || '-' || v_doc_no,  
                    tp_status   = 'Posted',
                    tp_up_dt    = sysdate,
                    tp_up_uid   = p_user_id
                    --tp_jv_dt    = p_date  --added by Aam on 28 Oct 2021
                where tp_no     = cd.tp_no
                and tp_name     = cd.tp_name;
            end;
        end loop;
        DUMMY_MGT('6 one Insert Detail');                                  
    end loop;
    commit;
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;
    DUMMY_MGT('7 v_check_docno : '||v_check_docno||', Date : '||p_date||', H Sys Id  : '|| vh_sys_id||', p_sys_id : '|| p_sys_id);                                  
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'JV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure tjv_bill_chp (p_tpb_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       tpb_supp_name ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_supp_code supp_code,       
       tpb_due_date due_date, /* Added by Aam on 13 Apr 2022*/
       tpb_fp_no fp_no,
       tpb_fp_dt fp_dt,
       tpb_bill_amt dpp,
       tpb_ppn_amt  ppn_amt,
       tpb_ppn      ppn_percentage,
       round(nvl(tpb_ppn_amt,0) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) ppn_amt_usd                 
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sys_id
and not exists(select 'XXX' 
               from  
                  (select th_flex_10
                   from ft_unposted_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_cur_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_prv_trans_header
                   where th_tran_code = 'TJV'
                  ) 
               where th_flex_10 = tpb_trx_no
              );

cursor c_data (p_tpb_sysid number) is            
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_total amt, 
       round(tpb_total * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'C' drcr,
       '203001' main_acnt,
       tpb_supp_code sub_acnt,
       tpb_bill_amt bill_amt,
       null ppn,
       --null ppn_amt,
       tpb_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpb_due_date due_date    
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
union all
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpb_pph_amt amt, 
       round(tpb_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'C' drcr,
       '206005' main_acnt,
       null sub_acnt,
       tpb_bill_amt bill_amt,
       null ppn,
       null ppn_amt,
       tpb_pph pph,
       tpb_pph_amt pph_amt,
       null amt_bill,
       tpb_due_date due_date   
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
and nvl(tpb_pph_amt,0) != 0
union all
select tpb_trx_no,
       tpb_dt,
       tpb_supp_name,
       tpb_bill_no,
       tpb_curr,
       null destination,
       null pol,
       null driver,
       tpb_fp_no, 
       tpb_fp_dt,
       tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpb_bill_no  doc_ref,
       tpb_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' FP No. '||tpb_fp_no doc_desc,
       p_date jv_dt,
       tpb_ppn_amt amt, 
       round(tpb_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpb_curr curr,
       'D' drcr,
       '108004' main_acnt,
       null sub_acnt,
       tpb_bill_amt bill_amt,
       tpb_ppn ppn,
       tpb_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpb_due_date due_date   
from mgt_tp_provision_bill
where tpb_sys_id   = p_tpb_sysid
and nvl(tpb_ppn_amt,0) != 0
union all
select tp_no           tpb_trx_no, 
       tp_dt           tpb_dt,
       tp_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tp_destination  destination,
       tp_pol_no       pol,
       tp_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_name  || ' ' || tp_no doc_ref,
       'ANGKUTAN CHIPS PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tp_no doc_desc,
       p_date jv_dt,
       decode(round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)),0,tp_amt_typing, round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)))  amt,
       decode(round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),0, round(tp_amt_typing * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),
              round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       'IDR' curr,
       'D' drcr,
       tp_main_acnt main_acnt,
       null sub_acnt,
       null bill_amt,
       null ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tp_amt_typing amt_bill,
       tp_due_date due_date   
from mgt_tp_provision
where tp_tpb_sys_id   = p_tpb_sysid
and (tp_status = 'Posted' or tp_status = 'Unposted')
and tp_no like 'TPCHP%'
and tp_jv_no is not null
and tp_tjv_no is null
union all
select tp_no           tpb_trx_no, 
       tp_dt           tpb_dt,
       tp_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tp_destination  destination,
       tp_pol_no       pol,
       tp_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tp_name  || ' ' || tp_no doc_ref,
       'ANGKUTAN CHIPS PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tp_no doc_desc,
       p_date jv_dt,
       decode(round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)),0,tp_amt_typing, round(nvl(tp_amt,0) + nvl(tp_oth_amt,0)))  amt,
       decode(round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),0, round(tp_amt_typing * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2),
              round(round((nvl(tp_amt,0)+nvl(tp_oth_amt,0))) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       'IDR' curr,
       'D' drcr,
       --decode(tp_no, 'OTHERS', '401001', tp_main_acnt) main_acnt, 
       tp_main_acnt main_acnt,
       null sub_acnt,
       null bill_amt,
       null ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tp_amt_typing amt_bill,
       tp_due_date due_date   
from mgt_tp_provision
where tp_tpb_sys_id   = p_tpb_sysid
and (tp_status = 'Posted' or tp_status = 'Unposted')
--and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG')
and tp_jv_no is not null
and tp_tjv_no is null;
 
    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_tot_amt        number;   
    v_tot_amt_typing number;
    v_tot_amt_diff   number;
    v_drcr varchar2(1);
    v_amt_usd number;
    v_gross_up varchar2(1);
    v_amt_grossup number;
    v_amt_grossup_com number;
    v_amt_grossup_pph number := 0;
    v_amt_grossup_ppn number := 0;
    v_amt_usd_grossup number; 
    v_amt_grossup_tot number; 
    v_check_1         number;
      
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select --tdoc_cal_year || lpad (tdoc_cur_no + 1, 6, 0)
                   tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0) --Added by Aam on 01 Apr 2021
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'TJV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year; --Added by Aam on 01 Apr 2021
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;   
        DUMMY_MGT('2 v_doc_no : '||v_doc_no);     
        begin
            select nvl(supp_flex_06, 'N') gross_up
            into v_gross_up
            from mgt_transp_master,
                 om_supplier
            where mtm_transp_code = supp_code
            and mtm_transp_code = ch.supp_code
            and nvl(supp_frz_flag_num,2) = 2                
            group by supp_flex_06;
        exception
            when others then
                v_gross_up := 'N';        
        end;
        DUMMY_MGT('4 v_gross_up : '||v_gross_up);                            
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_flex_10,
                                             th_cr_uid,
                                             th_cr_dt,
                                             th_flex_03,
                                             th_flex_04,
                                             th_flex_05,
                                             th_flex_06,
                                             th_flex_07,
                                             th_flex_08,
                                             th_flex_09                                              
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'TJV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.tpb_dt, --ch.jv_dt, remarks 03 Feb 2023 by Aam  
                                             ch.due_date, --ch.jv_dt,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_desc,
                                             ch.tpb_trx_no,
                                             p_user_id,
                                             sysdate,
                                             ch.fp_no,
                                             ch.fp_dt,
                                             ch.supp_code,
                                             ch.dpp,
                                             ch.ppn_amt,
                                             ch.ppn_percentage,
                                             ch.ppn_amt_usd
                                             );
           DUMMY_MGT('5 Done Insert Header');                                                             
        for cd in c_data (p_tpb_sys_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end; 
            if v_gross_up = 'N' then
                v_amt_grossup     := cd.amt;
                v_amt_usd_grossup := cd.amt_usd;                   
            elsif v_gross_up = 'Y' then                
                if cd.main_acnt = '203001' then --Cr bill_amount
                    /*
                    v_amt_grossup     := cd.bill_amt;
                    v_amt_grossup_com := cd.bill_amt;
                    v_amt_usd_grossup := round(cd.bill_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                    */
                    --Modify by Aam on 16 Sep 2022
                    v_amt_grossup     := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_grossup_com := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_usd_grossup := round((cd.bill_amt + nvl(cd.ppn_amt,0)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                elsif cd.main_acnt = '206005' then --Cr pph
                    v_amt_grossup     := cd.pph_amt;                    
                    v_amt_grossup_pph := cd.pph_amt;
                    v_amt_usd_grossup := round(cd.pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                            
                elsif cd.main_acnt = '108004' then --Dr ppn  --Pos Logistic 
                    v_amt_grossup     := cd.ppn_amt;
                    v_amt_grossup_ppn := cd.ppn_amt;
                    v_amt_usd_grossup := round(cd.ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                        
                --elsif cd.main_acnt = '208026' then --Dr
                elsif cd.main_acnt in ('208026', '401001', '401009', '401011') then --Dr
                    /*
                    if cd.ppn is null then
                        v_amt_grossup     := round(cd.amt_bill/0.98);
                        v_amt_usd_grossup := round(v_amt_grossup * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2);
                    else 
                        v_amt_grossup     := round(cd.amt_bill/0.98) - round(cd.amt_bill * (cd.ppn/100));
                        v_amt_usd_grossup := round(v_amt_grossup * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                    end if;
                    */
                    -- Added by Aam on 30 Mar 2021
                    if abs(cd.amt_bill - cd.amt) < 1000 then      
                        if cd.ppn is null then
                            v_amt_grossup     := round(cd.amt_bill/0.98);
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                        else 
                            v_amt_grossup     := round(cd.amt_bill/0.98) - round(cd.amt_bill * (cd.ppn/100));
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                        end if;
                    elsif abs(cd.amt_bill - cd.amt) >= 1000 then
                        if cd.ppn is null then
                            v_amt_grossup     := round(cd.amt/0.98);
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                        else 
                            v_amt_grossup     := round(cd.amt/0.98) - round(cd.amt * (cd.ppn/100));
                            v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                    
                        end if;                        
                    end if;
                    -- End Added                                              
                else
                   v_amt_grossup     := cd.amt;
                   v_amt_usd_grossup := cd.amt_usd; 
                end if;              
            end if;   
            DUMMY_MGT('6 cd.main_acnt : '||cd.main_acnt||', vd_sys_id : '||vd_sys_id||', v_seq_no : '||v_seq_no||', vh_sys_id : '|| vh_sys_id);                                                                                        
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_01,
                                                    td_flex_02,
                                                    td_flex_04,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.due_date, --cd.jv_dt,
                                                    cd.main_acnt,
                                                    cd.sub_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_grossup,
                                                    decode(cd.main_acnt,'108004', nvl(cd.doc_desc,cd.doc_ref)||' FP NO :'||cd.tpb_fp_no, nvl(cd.doc_desc,cd.doc_ref)),--nvl(cd.doc_desc,cd.doc_ref),
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tpb_fp_no,
                                                    to_char(cd.tpb_fp_dt, 'DDMMYYYY'),
                                                    cd.tpb_bill_no,
                                                    cd.tpb_trx_no,
                                                    to_char(cd.tpb_dt,'DDMMYYYY'),
                                                    cd.tpb_supp_name,
                                                    cd.destination,
                                                    cd.pol,
                                                    cd.driver,                                                     
                                                    p_user_id,
                                                    sysdate);
            v_seq_no := v_seq_no + 1;                                        
        end loop; 
        DUMMY_MGT('7 Done Insert Detail');        
        if v_gross_up = 'N' then
            begin                        
                select nvl(sum(tp_total_amt),0), nvl(sum(tp_amt_typing),0), nvl(abs(sum(tp_amt_diff)),0) 
                into v_tot_amt, v_tot_amt_typing, v_tot_amt_diff
                from mgt_tp_provision
                where tp_tpb_sys_id = p_tpb_sys_id
                and tp_jv_no is not null; 
            end;
            if v_tot_amt != 0 then                                 
                if v_tot_amt >  v_tot_amt_typing then
                    v_drcr := 'C';
                elsif v_tot_amt < v_tot_amt_typing then
                    v_drcr := 'D';
                end if;
            end if;
            
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;            
        elsif v_gross_up = 'Y' then 
            /*
            begin
                select sum(round(tp_amt_typing/0.98))
                into v_amt_grossup_tot       
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null
                and tp_tjv_no is null;
            end;
            if (v_amt_grossup_tot + nvl(v_amt_grossup_ppn,0))   > (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)) then
                v_drcr := 'C';
            elsif (v_amt_grossup_tot + nvl(v_amt_grossup_ppn,0)) < (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)) then
                v_drcr := 'D';
            end if;
            v_tot_amt_diff := abs(v_amt_grossup_tot - (v_amt_grossup_com + nvl(v_amt_grossup_pph,0)));
            begin
                select round(v_tot_amt_diff * curs_usd (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            */
            -- Added by Aam on 30 Mar 2021        
            begin                                 
                select count(tp_tpb_sys_id)
                into v_check_1       
                from mgt_tp_provision
                where tp_tpb_sys_id   = p_tpb_sys_id
                and tp_jv_no is not null                
                and tp_tjv_no is null
                and tp_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS');
            end;            
            if v_check_1 = 0 then             
                begin                                 
                    select nvl(sum(tp_total_amt),0), sum(round(tp_amt_typing/0.98)), round(nvl(abs(sum(tp_amt_diff)),0)/0.98)
                    into v_tot_amt, v_amt_grossup_tot, v_tot_amt_diff       
                    from mgt_tp_provision
                    where tp_tpb_sys_id   = p_tpb_sys_id
                    and tp_jv_no is not null
                    and tp_tjv_no is null
                    and abs(tp_amt_diff) > 1000;
                end;
            else
                begin                                 
                    select nvl(sum(tp_total_amt),0), sum(round(tp_amt_typing/0.98)), round(nvl(abs(sum(tp_amt_diff)),0)/0.98)
                    into v_tot_amt, v_amt_grossup_tot, v_tot_amt_diff       
                    from mgt_tp_provision
                    where tp_tpb_sys_id   = p_tpb_sys_id
                    and tp_jv_no is not null
                    and tp_tjv_no is null
                    and abs(tp_amt_diff) > 1000
                    and tp_no not in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS');
                end;
            end if;                            
            v_tot_amt_diff := abs(v_tot_amt_diff);
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end; 
            if (v_tot_amt - v_amt_grossup_tot) < 0 then
                v_drcr := 'D';
            else
                v_drcr := 'C';
            end if;                       
            DUMMY_MGT('8 v_amt_grossup_tot : '||v_amt_grossup_tot||', v_tot_amt_diff : '||v_tot_amt_diff||', v_amt_usd : '||v_amt_usd );
            -- End Added            
        end if;        
        if v_tot_amt != 0 and v_tot_amt_diff != 0 then                           
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    ch.jv_dt,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    ch.due_date, --ch.jv_dt,
                                                    '401001',
                                                    null,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    ch.tpb_curr,   
                                                    v_drcr,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_tot_amt_diff,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    p_user_id,
                                                    sysdate);              
        
        end if;
        DUMMY_MGT('9 Done Insert Different'); 
    end loop;
    commit;
    begin
        update mgt_tp_provision
        set tp_tjv_no       = 'TJV' || '-' || v_doc_no,
            tp_status       = 'Posted',
            tp_match_status = 'Y',       
            tp_up_dt        = sysdate,
            tp_up_uid       = p_user_id
        where tp_tpb_sys_id     = p_tpb_sys_id
        and tp_jv_no is not null;
    end;
    begin
        update mgt_tp_provision_bill
        set tpb_status   = 'GENERATE',                        
            tpb_up_dt    = sysdate,
            tpb_up_uid   = p_user_id
            ---tpb_bill_dt  = p_date  --added by Aam on 28 Oct 2021
        where tpb_sys_id = p_tpb_sys_id;
    end;        
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'TJV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure tjv_bill_add (p_tpb_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'ADD ON '|| tpba_supp_name ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_supp_code supp_code,
       tpba_due_date due_date,
       tpba_fp_no fp_no,
       tpba_fp_dt fp_dt,
       tpba_bill_amt dpp,
       tpba_ppn_amt  ppn_amt,
       tpba_ppn      ppn_percentage,
       round(nvl(tpba_ppn_amt,0) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) ppn_amt_usd           
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sys_id
and tpba_status is null
and not exists(select 'XXX' 
               from  
                  (select th_flex_10
                   from ft_unposted_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_cur_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_prv_trans_header
                   where th_tran_code = 'TJV'
                  ) 
               where th_flex_10 = tpba_trx_no
              );

cursor c_data (p_tpb_sysid number) is            
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_total amt, 
       round(tpba_total * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'C' drcr,
       '203001' main_acnt,
       tpba_supp_code sub_acnt,
       tpba_bill_amt bill_amt,
       null ppn,
       tpba_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpba_due_date due_date       
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and tpba_status is null
union all
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_pph_amt amt, 
       round(tpba_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'C' drcr,
       '206005' main_acnt,
       null sub_acnt,
       tpba_bill_amt bill_amt,
       null ppn,
       null ppn_amt,
       tpba_pph pph,
       tpba_pph_amt pph_amt,
       null amt_bill,
       tpba_due_date due_date
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and nvl(tpba_pph_amt,0) != 0
and tpba_status is null
union all
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' FP No. '||tpba_fp_no doc_desc,
       p_date jv_dt,
       tpba_ppn_amt amt, 
       round(tpba_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'D' drcr,
       '108004' main_acnt,
       null sub_acnt,
       tpba_bill_amt bill_amt,
       tpba_ppn ppn,
       tpba_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpba_due_date due_date
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and nvl(tpba_ppn_amt,0) != 0
and tpba_status is null
union all
select tpa_no           tpb_trx_no, 
       tpa_dt           tpb_dt,
       tpa_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tpa_destination  destination,
       tpa_pol_no       pol,
       tpa_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tpa_name  || ' ' || tpa_no doc_ref,
       'ANGKUTAN BENANG PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tpa_no doc_desc,
       p_date jv_dt,
       decode(nvl(supp_flex_06, 'N'),'N', nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))) amt,       
       decode(nvl(supp_flex_06, 'N'),'N', round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,       
       'IDR' curr,
       'D' drcr,
       '404001' main_acnt,
       null sub_acnt,
       null bill_amt,
       tpba_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tpa_amt_typing amt_bill,
       tpa_due_date due_date       
from mgt_tp_provision_add, mgt_tp_provision_bill_add, om_supplier
where tpa_tpba_sys_id = tpba_sys_id 
and tpa_name_code     = supp_code
and tpa_tpba_sys_id   = p_tpb_sysid
and (tpa_status = 'Posted' or tpa_status = 'Unposted')
and tpa_no like 'TPDN%'
and tpa_jv_no is not null
and tpa_tjv_no is not null
and tpa_tjv_add_no is null
union all
select tpa_no           tpb_trx_no, 
       tpa_dt           tpb_dt,
       tpa_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tpa_destination  destination,
       tpa_pol_no       pol,
       tpa_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tpa_name  || ' ' || tpa_no doc_ref,
       'ANGKUTAN BENANG PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tpa_no doc_desc,
       p_date jv_dt,
       decode(nvl(supp_flex_06, 'N'),'N', nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))) amt,       
       decode(nvl(supp_flex_06, 'N'),'N', round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       'IDR' curr,
       'D' drcr,
       '404001' main_acnt,
       null sub_acnt,
       null bill_amt,
       tpba_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tpa_amt_typing amt_bill,
       tpa_due_date due_date       
from mgt_tp_provision_add, mgt_tp_provision_bill_add, om_supplier
where tpa_tpba_sys_id = tpba_sys_id
and tpa_name_code     = supp_code (+)
and tpa_tpba_sys_id   = p_tpb_sysid
and (tpa_status = 'Posted' or tpa_status = 'Unposted')
and tpa_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
and tpa_jv_no is not null
--and tpa_tjv_no is not null
and tpa_tjv_add_no is null;

    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_tot_amt        number;   
    v_tot_amt_typing number;
    v_tot_amt_diff   number;
    v_drcr varchar2(1);
    v_amt_usd number;  
    v_gross_up varchar2(1);
    v_amt_grossup number;
    v_amt_grossup_com number;
    v_amt_grossup_pph number := 0;
    v_amt_grossup_ppn number := 0;
    v_amt_usd_grossup number; 
    v_amt_grossup_tot number; 
    v_check_1         number;
    v_cr varchar2(1);
    v_cr_amt number;
    v_cr_amt_usd number;
    v_dr varchar2(1);
    v_dr_amt number;
    v_dr_amt_usd number;
     
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0)
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'TJV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year;
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;        
        DUMMY_MGT('2 v_doc_no : '||v_doc_no);
        begin
            select nvl(supp_flex_06, 'N') gross_up
            into v_gross_up
            from mgt_transp_master,
                 om_supplier
            where mtm_transp_code = supp_code
            and mtm_transp_code = ch.supp_code
            and nvl(supp_frz_flag_num,2) = 2                
            group by supp_flex_06;
        exception
            when others then
                v_gross_up := 'N';        
        end;
        DUMMY_MGT('3 v_gross_up : '||v_gross_up);                            
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_flex_10,
                                             th_cr_uid,
                                             th_cr_dt,
                                             th_flex_03,
                                             th_flex_04,
                                             th_flex_05,
                                             th_flex_06,
                                             th_flex_07,
                                             th_flex_08,
                                             th_flex_09                                              
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'TJV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.tpba_dt, --ch.jv_dt, remarks 03 Feb 2023 by Aam  
                                             ch.due_date,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_desc,
                                             ch.tpba_trx_no,
                                             p_user_id,
                                             sysdate,
                                             ch.fp_no,
                                             ch.fp_dt,
                                             ch.supp_code,
                                             ch.dpp,
                                             ch.ppn_amt,
                                             ch.ppn_percentage,
                                             ch.ppn_amt_usd
                                             );
        for cd in c_data (p_tpb_sys_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;
            if v_gross_up = 'N' then
                v_amt_grossup     := cd.amt;
                v_amt_usd_grossup := cd.amt_usd;                   
            elsif v_gross_up = 'Y' then                
                if cd.main_acnt = '203001' then --Cr bill_amount
                    v_amt_grossup     := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_grossup_com := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_usd_grossup := round((cd.bill_amt + nvl(cd.ppn_amt,0)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                elsif cd.main_acnt = '206005' then --Cr pph
                    v_amt_grossup     := cd.pph_amt;                    
                    v_amt_grossup_pph := cd.pph_amt;
                    v_amt_usd_grossup := round(cd.pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);    
                elsif cd.main_acnt = '108004' then --Dr ppn  --Pos Logistic 
                    v_amt_grossup     := cd.ppn_amt;
                    v_amt_grossup_ppn := cd.ppn_amt;
                    v_amt_usd_grossup := round(cd.ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                        
                --elsif cd.main_acnt in ('208027', '401001', '401009', '401011') then --Dr
                elsif cd.main_acnt in ('208027', '404001', '401009', '401011') then --Dr                   
                    v_amt_grossup     := round(cd.amt);
                    v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                                                                                           
                end if;              
            end if;  
            DUMMY_MGT('4 v_amt_grossup : '||v_amt_grossup||', v_amt_usd_grossup : '||v_amt_usd_grossup);                   
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_01,
                                                    td_flex_02,
                                                    td_flex_04,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.due_date,
                                                    cd.main_acnt,
                                                    cd.sub_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_grossup,
                                                    decode(cd.main_acnt,'108004', nvl(cd.doc_desc,cd.doc_ref)||' FP NO :'||cd.tpba_fp_no, nvl(cd.doc_desc,cd.doc_ref)),
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tpba_fp_no,
                                                    to_char(cd.tpba_fp_dt, 'DDMMYYYY'),
                                                    cd.tpba_bill_no,
                                                    cd.tpba_trx_no,
                                                    to_char(cd.tpba_dt,'DDMMYYYY'),
                                                    cd.tpba_supp_name,
                                                    cd.destination,
                                                    cd.pol,
                                                    cd.driver,                                                     
                                                    p_user_id,
                                                    sysdate);
                                                    
            v_seq_no := v_seq_no + 1;
            begin
                update mgt_tp_provision_add
                set tpa_grossup_value = round(v_amt_grossup - tpa_amt)
                where tpa_no = cd.tpba_trx_no
                and tpa_no like 'TP%';
            end;                                                    
        end loop;
        if v_gross_up = 'N' then
            begin                        
                select nvl(b.tpba_bill_amt,0), nvl(sum(a.tpa_amt_typing),0), (nvl(sum(a.tpa_amt_typing),0)- nvl(b.tpba_bill_amt,0)) diff
                into v_tot_amt, v_tot_amt_typing, v_tot_amt_diff
                from mgt_tp_provision_add a,
                     mgt_tp_provision_bill_add b
                where a.tpa_tpba_sys_id = b.tpba_sys_id
                and a.tpa_tpba_sys_id = p_tpb_sys_id
                and a.tpa_jv_no is not null 
                and a.tpa_tjv_no is not null
                group by b.tpba_bill_amt;
            end;
            if v_tot_amt != 0 then                                 
                if v_tot_amt >  v_tot_amt_typing then
                    v_drcr := 'D';
                elsif v_tot_amt < v_tot_amt_typing then
                    v_drcr := 'C';
                end if;
            end if;
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            DUMMY_MGT('5 v_tot_amt : '||v_tot_amt||', v_tot_amt_typing : '||v_tot_amt_typing||', v_tot_amt_diff : '||v_tot_amt_diff);            
        elsif v_gross_up = 'Y' then             
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_cr, v_cr_amt, v_cr_amt_usd                   
                from
                (
                select 'C' drcr,
                       '203001' main_acnt,
                       tpba_bill_amt amt,
                       round(tpba_bill_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd                 
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                union all
                select 'C' drcr,
                       '206005' main_acnt,
                       -tpba_pph_amt amt,
                       -round(tpba_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                and nvl(tpba_pph_amt,0) != 0
                )
                group by drcr;
            end;
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_dr, v_dr_amt, v_dr_amt_usd                   
                from
                (            
                select 'D' drcr,
                       '108004' main_acnt,
                       tpba_ppn_amt amt, 
                       round(tpba_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                and nvl(tpba_ppn_amt,0) != 0
                union all
                select 'D' drcr,
                       tpa_main_acnt main_acnt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)*0.02))) amt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd                     
                from mgt_tp_provision_add, mgt_tp_provision_bill_add
                where tpa_tpba_sys_id = tpba_sys_id 
                and tpa_tpba_sys_id   = p_tpb_sys_id
                and (tpa_status = 'Posted' or tpa_status = 'Unposted')
                and tpa_no like 'TPDN%'
                union all
                select 'D' drcr,
                       tpa_main_acnt main_acnt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)*0.02))) amt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd              
                from mgt_tp_provision_add, mgt_tp_provision_bill_add
                where tpa_tpba_sys_id = tpba_sys_id 
                and tpa_tpba_sys_id   = p_tpb_sys_id
                and (tpa_status = 'Posted' or tpa_status = 'Unposted')
                and tpa_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
                )
                group by drcr;
            end;                              
            v_tot_amt_diff := abs(v_cr_amt - v_dr_amt); 
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end; 
            if v_cr_amt < v_dr_amt then
                v_drcr := 'C';
            else
                v_drcr := 'D';
            end if;                       
            DUMMY_MGT('6 v_amt_grossup_tot : '||v_amt_grossup_tot||', v_tot_amt_diff : '||v_tot_amt_diff||', v_amt_usd : '||v_amt_usd );            
        end if;        
        if (v_gross_up = 'N' and v_tot_amt != 0 and v_tot_amt_diff > 0) or (v_gross_up = 'Y' and v_tot_amt_diff != 0) then         
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    ch.jv_dt,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    ch.due_date, 
                                                    '404001',
                                                    null,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    ch.tpba_curr,   
                                                    v_drcr,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_tot_amt_diff,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    p_user_id,
                                                    sysdate);              
        
        end if;
    end loop;
    DUMMY_MGT('7 Insert ft_unposted_trans_detail');    
    commit;
    begin
        update mgt_tp_provision_add
        set tpa_tjv_add_no   = 'TJV' || '-' || v_doc_no,    
            tpa_up_dt        = sysdate,
            tpa_up_uid       = p_user_id,
            tpa_status       = 'Posted'
        where tpa_tpba_sys_id     = p_tpb_sys_id
        and tpa_jv_no is not null;
    end;
    begin
        update mgt_tp_provision_bill_add
        set tpba_status   = 'GENERATE',                          
            tpba_up_dt    = sysdate,
            tpba_up_uid   = p_user_id
            --tpba_bill_dt  = p_date
        where tpba_sys_id = p_tpb_sys_id;
    end;        
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;    
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'TJV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure tjv_bill_chp_add (p_tpb_sys_id number, p_user_id varchar2, p_date date) is
cursor c_head is 
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'ADD ON '|| tpba_supp_name ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_supp_code supp_code,
       tpba_due_date due_date,
       tpba_fp_no fp_no,
       tpba_fp_dt fp_dt,
       tpba_bill_amt dpp,
       tpba_ppn_amt  ppn_amt,
       tpba_ppn      ppn_percentage,
       round(nvl(tpba_ppn_amt,0) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) ppn_amt_usd           
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sys_id
and tpba_status is null
and not exists(select 'XXX' 
               from  
                  (select th_flex_10
                   from ft_unposted_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_cur_trans_header
                   where th_tran_code = 'TJV'
                   union all
                   select th_flex_10
                   from ft_prv_trans_header
                   where th_tran_code = 'TJV'
                  ) 
               where th_flex_10 = tpba_trx_no
              );

cursor c_data (p_tpb_sysid number) is            
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_total amt, 
       round(tpba_total * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'C' drcr,
       '203001' main_acnt,
       tpba_supp_code sub_acnt,
       tpba_bill_amt bill_amt,
       null ppn,
       tpba_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpba_due_date due_date       
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and tpba_status is null
union all
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY') doc_desc,
       p_date jv_dt,
       tpba_pph_amt amt, 
       round(tpba_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'C' drcr,
       '206005' main_acnt,
       null sub_acnt,
       tpba_bill_amt bill_amt,
       null ppn,
       null ppn_amt,
       tpba_pph pph,
       tpba_pph_amt pph_amt,
       null amt_bill,
       tpba_due_date due_date
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and nvl(tpba_pph_amt,0) != 0
and tpba_status is null
union all
select tpba_trx_no,
       tpba_dt,
       tpba_supp_name,
       tpba_bill_no,
       tpba_curr,
       null destination,
       null pol,
       null driver,
       tpba_fp_no, 
       tpba_fp_dt,
       tpba_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE '|| to_char(p_date,'Mon YYYY')||' Invoice : '||tpba_bill_no  doc_ref,
       tpba_remark ||' PERIODE '|| to_char(p_date,'Mon YYYY')||' FP No. '||tpba_fp_no doc_desc,
       p_date jv_dt,
       tpba_ppn_amt amt, 
       round(tpba_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd,                       
       tpba_curr curr,
       'D' drcr,
       '108004' main_acnt,
       null sub_acnt,
       tpba_bill_amt bill_amt,
       tpba_ppn ppn,
       tpba_ppn_amt ppn_amt,
       null pph,
       null pph_amt,
       null amt_bill,
       tpba_due_date due_date
from mgt_tp_provision_bill_add
where tpba_sys_id   = p_tpb_sysid
and nvl(tpba_ppn_amt,0) != 0
and tpba_status is null
union all
select tpa_no           tpb_trx_no, 
       tpa_dt           tpb_dt,
       tpa_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tpa_destination  destination,
       tpa_pol_no       pol,
       tpa_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tpa_name  || ' ' || tpa_no doc_ref,
       'ANGKUTAN CHIP PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tpa_no doc_desc,
       p_date jv_dt,
       decode(nvl(supp_flex_06, 'N'),'N', nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))) amt,       
       decode(nvl(supp_flex_06, 'N'),'N', round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,       
       'IDR' curr,
       'D' drcr,
       tpa_main_acnt main_acnt, --'401001' main_acnt,
       null sub_acnt,
       null bill_amt,
       tpba_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tpa_amt_typing amt_bill,
       tpa_due_date due_date       
from mgt_tp_provision_add, mgt_tp_provision_bill_add, om_supplier
where tpa_tpba_sys_id = tpba_sys_id 
and tpa_name_code     = supp_code
and tpa_tpba_sys_id   = p_tpb_sysid
and (tpa_status = 'Posted' or tpa_status = 'Unposted')
and tpa_no like 'TPCHP%'
and tpa_jv_no is not null
and tpa_tjv_no is not null
and tpa_tjv_add_no is null
union all
select tpa_no           tpb_trx_no, 
       tpa_dt           tpb_dt,
       tpa_name         tpb_supp_name,
       null            tpb_bill_no,
       null            tpb_curr,
       tpa_destination  destination,
       tpa_pol_no       pol,
       tpa_driver       driver,
       null            tpb_fp_no,
       null            tpb_fp_dt,
       0               tpb_total,
       to_char(p_date,'MM') month_mm,
       to_char(p_date,'YYYY') year_yy,
       'PERIODE ' ||to_char(p_date,'Mon YYYY') || ' ' || tpa_name  || ' ' || tpa_no doc_ref,
       'ANGKUTAN CHIP PERIODE '||to_char(p_date,'Mon YYYY') ||' '|| tpa_no doc_desc,
       p_date jv_dt,
       decode(nvl(supp_flex_06, 'N'),'N', nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))) amt,       
       decode(nvl(supp_flex_06, 'N'),'N', round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd,
       'IDR' curr,
       'D' drcr,
       tpa_main_acnt main_acnt, --'401001' main_acnt,
       null sub_acnt,
       null bill_amt,
       tpba_ppn ppn,
       null ppn_amt,
       null pph,
       null pph_amt,
       tpa_amt_typing amt_bill,
       tpa_due_date due_date       
from mgt_tp_provision_add, mgt_tp_provision_bill_add, om_supplier
where tpa_tpba_sys_id = tpba_sys_id
and tpa_name_code     = supp_code (+) 
and tpa_tpba_sys_id   = p_tpb_sysid
and (tpa_status = 'Posted' or tpa_status = 'Unposted')
and tpa_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
and tpa_jv_no is not null
--and tpa_tjv_no is not null
and tpa_tjv_add_no is null;

    vh_sys_id        number;
    vd_sys_id        number;
    v_doc_no         number;
    v_check_detail   number;
    v_year           number;
    v_acnt_month     number;
    v_acnt_year      number;
    v_seq_no         number := 1;
    v_check_docno    number;
    v_check_doc_no   number;
    v_main_acc_vat_reco varchar2(20);
    v_amt            number;  
    v_tot_amt        number;   
    v_tot_amt_typing number;
    v_tot_amt_diff   number;
    v_drcr varchar2(1);
    v_amt_usd number;  
    v_gross_up varchar2(1);
    v_amt_grossup number;
    v_amt_grossup_com number;
    v_amt_grossup_pph number := 0;
    v_amt_grossup_ppn number := 0;
    v_amt_usd_grossup number; 
    v_amt_grossup_tot number; 
    v_check_1         number;
    v_cr varchar2(1);
    v_cr_amt number;
    v_cr_amt_usd number;
    v_dr varchar2(1);
    v_dr_amt number;
    v_dr_amt_usd number;
     
begin
    for ch in c_head loop
        exit when c_head%notfound;        
        begin
            select th_sys_id.nextval into vh_sys_id from dual;
        end;
        begin
            select aper_cal_year, aper_cal_month, aper_acnt_year
            into v_year, v_acnt_month, v_acnt_year
            from fm_acnt_period
            where aper_comp_code    = '002'
            and aper_frm_dt         <= ch.jv_dt
            and aper_to_dt          >= ch.jv_dt;
        exception 
            when no_data_found then
                v_year       := null;
                v_acnt_month := null;
                v_acnt_year  := null;
        end;
        DUMMY_MGT('1 v_year : '||v_year||', v_acnt_month : '||v_acnt_month||', v_acnt_year :'||v_acnt_year);
        begin
            select tdoc_cal_year||lpad(tdoc_period,2,0)||lpad(tdoc_cur_no + 1,4,0)
            into v_doc_no
            from fm_tran_doc_no
            where tdoc_comp_code    = '002'
            and tdoc_tran_code      = 'TJV'
            and tdoc_period         = to_number(to_char(p_date,'MM'))
            and tdoc_cal_year       = v_year
            and tdoc_acnt_year      = v_acnt_year;
        exception 
            when no_data_found then
                v_doc_no    := null;
        end;        
        DUMMY_MGT('2 v_doc_no : '||v_doc_no);
        begin
            select nvl(supp_flex_06, 'N') gross_up
            into v_gross_up
            from mgt_transp_master,
                 om_supplier
            where mtm_transp_code = supp_code
            and mtm_transp_code = ch.supp_code
            and nvl(supp_frz_flag_num,2) = 2                
            group by supp_flex_06;
        exception
            when others then
                v_gross_up := 'N';        
        end;
        DUMMY_MGT('3 v_gross_up : '||v_gross_up);                            
        insert into ft_unposted_trans_header(th_sys_id,
                                             th_comp_code,
                                             th_acnt_year,
                                             th_tran_code,
                                             th_doc_no,
                                             th_doc_dt,
                                             th_doc_cal_year,
                                             th_doc_cal_month,
                                             th_doc_ref,
                                             th_doc_ref_dt,
                                             th_doc_due_dt,
                                             th_divn_code,
                                             th_dept_code,
                                             th_amd_no,
                                             th_submit_status,
                                             th_appr_status,
                                             th_desc,
                                             th_flex_10,
                                             th_cr_uid,
                                             th_cr_dt,
                                             th_flex_03,
                                             th_flex_04,
                                             th_flex_05,
                                             th_flex_06,
                                             th_flex_07,
                                             th_flex_08,
                                             th_flex_09                                               
                                            )
                                    values  (vh_sys_id,
                                             '002',
                                             v_acnt_year,
                                             'TJV',
                                             v_doc_no,
                                             ch.jv_dt, 
                                             v_year,
                                             v_acnt_month,
                                             ch.doc_ref,
                                             ch.tpba_dt, --ch.jv_dt, remarks 03 Feb 2023 by Aam  
                                             ch.due_date,                
                                             '001',
                                             'FIN',
                                             0,
                                             0,
                                             1,
                                             ch.doc_desc,
                                             ch.tpba_trx_no,
                                             p_user_id,
                                             sysdate,
                                             ch.fp_no,
                                             ch.fp_dt,
                                             ch.supp_code,
                                             ch.dpp,
                                             ch.ppn_amt,
                                             ch.ppn_percentage,
                                             ch.ppn_amt_usd
                                             );
        for cd in c_data (p_tpb_sys_id) loop
            exit when c_data%notfound;
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;
            if v_gross_up = 'N' then
                v_amt_grossup     := cd.amt;
                v_amt_usd_grossup := cd.amt_usd;                   
            elsif v_gross_up = 'Y' then                
                if cd.main_acnt = '203001' then --Cr bill_amount
                    v_amt_grossup     := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_grossup_com := (cd.bill_amt + nvl(cd.ppn_amt,0));
                    v_amt_usd_grossup := round((cd.bill_amt + nvl(cd.ppn_amt,0)) * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);
                elsif cd.main_acnt = '206005' then --Cr pph
                    v_amt_grossup     := cd.pph_amt;                    
                    v_amt_grossup_pph := cd.pph_amt;
                    v_amt_usd_grossup := round(cd.pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);    
                elsif cd.main_acnt = '108004' then --Dr ppn  --Pos Logistic 
                    v_amt_grossup     := cd.ppn_amt;
                    v_amt_grossup_ppn := cd.ppn_amt;
                    v_amt_usd_grossup := round(cd.ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                        
                elsif cd.main_acnt in ('208027', '401001', '401009', '401011') then --Dr                   
                    v_amt_grossup     := round(cd.amt);
                    v_amt_usd_grossup := round(v_amt_grossup * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2);                                                                                           
                end if;              
            end if;  
            DUMMY_MGT('4 v_amt_grossup : '||v_amt_grossup||', v_amt_usd_grossup : '||v_amt_usd_grossup);                   
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_flex_01,
                                                    td_flex_02,
                                                    td_flex_04,
                                                    td_flex_15,
                                                    td_flex_16,
                                                    td_flex_17,
                                                    td_flex_18,
                                                    td_flex_19,
                                                    td_flex_20,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    cd.jv_dt,
                                                    cd.doc_ref,
                                                    cd.due_date,
                                                    cd.main_acnt,
                                                    cd.sub_acnt,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    cd.curr,   
                                                    cd.drcr,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_usd_grossup,
                                                    v_amt_grossup,
                                                    decode(cd.main_acnt,'108004', nvl(cd.doc_desc,cd.doc_ref)||' FP NO :'||cd.tpba_fp_no, nvl(cd.doc_desc,cd.doc_ref)),
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    cd.tpba_fp_no,
                                                    to_char(cd.tpba_fp_dt, 'DDMMYYYY'),
                                                    cd.tpba_bill_no,
                                                    cd.tpba_trx_no,
                                                    to_char(cd.tpba_dt,'DDMMYYYY'),
                                                    cd.tpba_supp_name,
                                                    cd.destination,
                                                    cd.pol,
                                                    cd.driver,                                                     
                                                    p_user_id,
                                                    sysdate);
                                                    
            v_seq_no := v_seq_no + 1;
            begin
                update mgt_tp_provision_add
                set tpa_grossup_value = round(v_amt_grossup - tpa_amt)
                where tpa_no = cd.tpba_trx_no
                and tpa_no like 'TP%';
            end;                                                    
        end loop;
        if v_gross_up = 'N' then
            begin                        
                select nvl(b.tpba_bill_amt,0), nvl(sum(a.tpa_amt_typing),0), (nvl(sum(a.tpa_amt_typing),0)- nvl(b.tpba_bill_amt,0)) diff
                into v_tot_amt, v_tot_amt_typing, v_tot_amt_diff
                from mgt_tp_provision_add a,
                     mgt_tp_provision_bill_add b
                where a.tpa_tpba_sys_id = b.tpba_sys_id
                and a.tpa_tpba_sys_id = p_tpb_sys_id
                and a.tpa_jv_no is not null 
                and a.tpa_tjv_no is not null
                group by b.tpba_bill_amt;
            end;
            if v_tot_amt != 0 then                                 
                if v_tot_amt >  v_tot_amt_typing then
                    v_drcr := 'D';
                elsif v_tot_amt < v_tot_amt_typing then
                    v_drcr := 'C';
                end if;
            end if;
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end;
            DUMMY_MGT('5 v_tot_amt : '||v_tot_amt||', v_tot_amt_typing : '||v_tot_amt_typing||', v_tot_amt_diff : '||v_tot_amt_diff);            
        elsif v_gross_up = 'Y' then             
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_cr, v_cr_amt, v_cr_amt_usd                   
                from
                (
                select 'C' drcr,
                       '203001' main_acnt,
                       tpba_bill_amt amt,
                       round(tpba_bill_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd                 
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                union all
                select 'C' drcr,
                       '206005' main_acnt,
                       -tpba_pph_amt amt,
                       -round(tpba_pph_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                and nvl(tpba_pph_amt,0) != 0
                )
                group by drcr;
            end;
            begin    
                select drcr,
                       sum(amt) amt,
                       sum(amt_usd) amt_usd
                into   v_dr, v_dr_amt, v_dr_amt_usd                   
                from
                (            
                select 'D' drcr,
                       '108004' main_acnt,
                       tpba_ppn_amt amt, 
                       round(tpba_ppn_amt * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) amt_usd
                from mgt_tp_provision_bill_add
                where tpba_sys_id   = p_tpb_sys_id
                and nvl(tpba_ppn_amt,0) != 0
                union all
                select 'D' drcr,
                       tpa_main_acnt main_acnt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)*0.02))) amt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd                     
                from mgt_tp_provision_add, mgt_tp_provision_bill_add
                where tpa_tpba_sys_id = tpba_sys_id 
                and tpa_tpba_sys_id   = p_tpb_sys_id
                and (tpa_status = 'Posted' or tpa_status = 'Unposted')
                and tpa_no like 'TPDN%'
                union all
                select 'D' drcr,
                       tpa_main_acnt main_acnt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, nvl(tpa_amt_typing,0), round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)*0.02))) amt,
                       decode(nvl(tpa_amt_pph_grossup,0),0, round(nvl(tpa_amt_typing,0)* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2), round(round(nvl(tpa_amt_typing,0) + (nvl(tpa_amt_typing,0)* 0.02))* curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2)) amt_usd              
                from mgt_tp_provision_add, mgt_tp_provision_bill_add
                where tpa_tpba_sys_id = tpba_sys_id 
                and tpa_tpba_sys_id   = p_tpb_sys_id
                and (tpa_status = 'Posted' or tpa_status = 'Unposted')
                and tpa_no in ('PALLET', 'RETUR BENANG', 'AMBIL BARANG', 'OTHERS')
                )
                group by drcr;
            end;                              
            v_tot_amt_diff := abs(v_cr_amt - v_dr_amt); 
            begin
                select round(v_tot_amt_diff * curs_usd_b (last_day(trunc(p_date,'MM')), 'IDR'),2) into v_amt_usd from dual;
            end; 
            if v_cr_amt < v_dr_amt then
                v_drcr := 'C';
            else
                v_drcr := 'D';
            end if;                       
            DUMMY_MGT('6 v_amt_grossup_tot : '||v_amt_grossup_tot||', v_tot_amt_diff : '||v_tot_amt_diff||', v_amt_usd : '||v_amt_usd );            
        end if;        
        if (v_gross_up = 'N' and v_tot_amt != 0 and v_tot_amt_diff > 0) or (v_gross_up = 'Y' and v_tot_amt_diff != 0) then         
            begin
                select td_sys_id.nextval into vd_sys_id from dual;
            end;                                                
            insert into ft_unposted_trans_detail   (td_sys_id,
                                                    td_seq_no,
                                                    td_th_sys_id,
                                                    td_comp_code,
                                                    td_acnt_year,
                                                    td_tran_code,
                                                    td_doc_no,
                                                    td_doc_dt,
                                                    td_doc_ref,
                                                    td_doc_due_dt,
                                                    td_main_acnt_code,
                                                    td_sub_acnt_code,
                                                    td_divn_code,
                                                    td_dept_code,
                                                    td_head_no_1,
                                                    td_head_no_2,
                                                    td_curr_code,
                                                    td_doc_drcr_flag,
                                                    td_doc_amt,
                                                    td_doc_amt_2,
                                                    td_doc_amt_3,
                                                    td_fc_amt,
                                                    td_desc,
                                                    td_dbk_print_flag,
                                                    td_led_print_flag,
                                                    td_month_prc_flag,
                                                    td_pymt_appr_flag,
                                                    td_cr_uid,
                                                    td_cr_dt
                                                    )                    
                                            values (vd_sys_id,
                                                    v_seq_no,
                                                    vh_sys_id,
                                                    '002',
                                                    v_acnt_year,
                                                    'TJV',
                                                    v_doc_no,
                                                    ch.jv_dt,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    ch.due_date, 
                                                    '401001',
                                                    null,
                                                    '001',
                                                    'FIN',
                                                    1,
                                                    2,
                                                    ch.tpba_curr,   
                                                    v_drcr,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_amt_usd,
                                                    v_tot_amt_diff,
                                                    'Different on Provision '|| ch.doc_ref,
                                                    'N',
                                                    'N',
                                                    'N',
                                                    '0',
                                                    p_user_id,
                                                    sysdate);              
        
        end if;
    end loop;
    commit;
    begin
        update mgt_tp_provision_add
        set tpa_tjv_add_no   = 'TJV' || '-' || v_doc_no,    
            tpa_up_dt        = sysdate,
            tpa_up_uid       = p_user_id,
            tpa_status       = 'Posted'
        where tpa_tpba_sys_id     = p_tpb_sys_id
        and tpa_jv_no is not null;
    end;
    begin
        update mgt_tp_provision_bill_add
        set tpba_status   = 'GENERATE',                          
            tpba_up_dt    = sysdate,
            tpba_up_uid   = p_user_id
            --tpba_bill_dt  = p_date
        where tpba_sys_id = p_tpb_sys_id;
    end;        
    begin
        select count(td_doc_no)
        into v_check_docno
        from ft_unposted_trans_detail
        where td_th_sys_id = vh_sys_id;
    end;    
    if v_check_docno != 0 then
        v_doc_no := to_number(substr(v_doc_no,7,4));        
        begin
            update fm_tran_doc_no
            set tdoc_cur_no     = v_doc_no
            where tdoc_comp_code= '002'
            and tdoc_tran_code  = 'TJV'
            and tdoc_cal_year   = v_year
            and tdoc_acnt_year  = v_acnt_year
            and tdoc_period     = to_number(to_char(p_date,'MM'));
        end;
        commit;
    end if;
end;

procedure insert_data (p_date date, p_user_id varchar2, p_tp_code varchar2, p_tp_type varchar2, p_tp_cap number, p_tp_police varchar2, p_tp_driver varchar2, p_tp_destination varchar2) is
cursor c_head is
select mtda_trans_code,
       mtda_trans_name,
       mtda_truck_type,
       mtda_truck_cap,
       mtda_police_no,
       mtda_driver,
       mtda_destination
       --substr(mtda_dn_no,1,3) txn_code
from mgt_transp_dn_auto
where mtda_dn_dt    = p_date
and mtda_trans_code	= p_tp_code
and mtda_truck_type = p_tp_type
and mtda_truck_cap  = p_tp_cap
and mtda_police_no 	= p_tp_police
and mtda_driver		= p_tp_driver
and mtda_destination= p_tp_destination
and mtda_transp_txn is null
group by mtda_trans_code,
         mtda_trans_name,
         mtda_truck_type,
         mtda_truck_cap,
         mtda_police_no,
         mtda_driver,
         mtda_destination
         --substr(mtda_dn_no,1,3)
order by mtda_trans_code;

cursor c_dn (p_dt date, p_tp_code varchar2, p_tp_type varchar2, p_tp_cap number, p_tp_police varchar2, p_tp_driver varchar2, p_tp_destination varchar2) is
select --substr(mtda_dn_no,1,3) txn_code,
       --substr(mtda_dn_no,5,10) txn_no,
       --Added by Aam on 30 Apr 2025 
       case 
        when substr(mtda_dn_no,1,3) = 'LDN' then 'LDN'
        when substr(mtda_dn_no,1,3) = 'JWD' then 'JWDN'
       end txn_code,
       case 
        when substr(mtda_dn_no,1,3) = 'LDN' then substr(mtda_dn_no,5,10)
        when substr(mtda_dn_no,1,3) = 'JWD' then substr(mtda_dn_no,6,10)
       end txn_no,  
       mtda_dn_qty qty,
       mtda_dn_no       
from mgt_transp_dn_auto
where mtda_dn_dt = p_dt
and mtda_transp_txn is null
and mtda_trans_code		= p_tp_code
and mtda_truck_type 	= p_tp_type
and mtda_truck_cap  	= p_tp_cap
and mtda_police_no 		= p_tp_police
and mtda_driver			= p_tp_driver
and mtda_destination	= p_tp_destination
order by mtda_dn_no;

cursor c_cost (p_dt date, p_tp_code varchar2) is
select mtda_trans_code,
       mtda_trans_name, 
       mtr_priority,
       mtr_rate_type,
       mtr_max_cap,       
       mtr_rate,
       decode(mtr_rate_type, 'W', mtr_rate, 'Q', round(mtr_max_cap * mtr_rate)) total_rate,
       mtr_destination,
       mtr_truck_type,
       qty, 
       mtm_truck_cap      
from 
(
select a.mtda_trans_code,
       a.mtda_trans_name, 
       b.mtr_priority,
       b.mtr_rate_type,
       decode(b.mtr_max_cap, 1, (a.qty - b.mtm_truck_cap), b.mtr_max_cap) mtr_max_cap, 
       b.mtr_rate,
       b.mtr_destination,
       b.mtr_truck_type,
       a.qty,
       b.mtm_truck_cap
from (
      select mtda_dn_dt,
             mtda_trans_code,
             mtda_trans_name,
             sum(mtda_dn_qty) qty  
      from mgt_transp_dn_auto 
      where mtda_dn_dt = p_dt
      and mtda_transp_txn is null
      and mtda_trans_code   = p_tp_code
      and mtda_police_no 	= p_tp_police
      and mtda_driver		= p_tp_driver
      and mtda_destination  = p_tp_destination
      group by mtda_dn_dt,
               mtda_trans_code,
               mtda_trans_name               
      ) a,
     (      
      select a.mtm_no,
             a.mtm_truck_cap,
             b.mtr_priority,
             b.mtr_rate_type,
             b.mtr_max_cap,
             b.mtr_rate,
             b.mtr_destination,
             b.mtr_truck_type 
      from mgt_transp_master a, 
           mgt_transp_rate b
      where a.mtm_no = b.mtr_mtm_no
      and a.mtm_no = p_tp_code
     ) b 
where a.mtda_trans_code = b.mtm_no 
group by a.mtda_trans_code,
         a.mtda_trans_name, 
         b.mtr_priority,
         b.mtr_rate_type,
         b.mtr_max_cap,
         b.mtr_rate,
         b.mtr_destination,
         b.mtr_truck_type,
         a.qty,
         b.mtm_truck_cap         
)
where mtr_max_cap >= 1
order by mtr_priority;
    v_id number;
	v_id_dn number;
	v_id_cost number;
	v_no number;
	v_transp_no number;
begin
	for ch in c_head loop
		exit when c_head%notfound;			
		begin 
			select mgt_transp_head_seq.nextval into v_id from dual;	
		end;						
		begin
			select max(mth_transp_no)
			into v_no
			from mgt_transp_head
			where mth_txn_code = 'TPDN'
			and to_char(mth_dt,'YYYY') = to_char(p_date,'YYYY'); --to_char(sysdate,'YYYY'); --remarks by Aam on 06 Jan 2025
		exception
			when others then
				v_no := null; 
		end;	
		if v_no is null then
			v_transp_no := to_number(to_char(p_date,'YYYY')||'000001'); --to_number(to_char(sysdate,'YYYY')||'000001'); --remarks by Aam on 06 Jan 2025
		else
			v_transp_no := v_no+1;
		end if;		
		insert into mgt_transp_head (   mth_sys_id,
                                        mth_txn_code,   
                                        mth_transp_no,
                                        mth_dt,
                                        mth_no,
                                        mth_transp_name,		                              
                                        mth_no_police,
                                        mth_driver,  
                                        mth_destination,		                              
                                        mth_truck_cap,
                                        mth_truck_type,
                                        mth_remark,
                                        mth_amendment,
                                        mth_status,																	
                                        mth_cr_uid,
                                        mth_cr_dt																
									)
                            values  (   v_id,
                                        'TPDN',	 
                                        v_transp_no,
                                        p_date,
                                        ch.mtda_trans_code,
                                        ch.mtda_trans_name,
                                        ch.mtda_police_no,
                                        ch.mtda_driver,
                                        ch.mtda_destination,
                                        ch.mtda_truck_cap,
                                        ch.mtda_truck_type,       														
                                        ch.mtda_trans_name ||' ' ||'Auto',
                                        0, 
                                        null,												         												          
                                        p_user_id,
                                        sysdate 												          
                                     );	
		for cdn in c_dn (p_date, ch.mtda_trans_code, ch.mtda_truck_type, ch.mtda_truck_cap, ch.mtda_police_no, ch.mtda_driver, ch.mtda_destination) loop
            begin 
                select mgt_transp_detail_dn_seq.nextval into v_id_dn from dual;	
            end;
			insert into mgt_transp_detail_dn    (	mtdd_sys_id,
                                                    mtdd_mth_sys_id,
                                                    mtdd_dn_txn_code,   
                                                    mtdd_dn_no,
                                                    mtdd_dn_qty,
                                                    mtdd_cr_uid,
                                                    mtdd_cr_dt																
                                                )
                                        values  (   v_id_dn,
                                                    v_id,
                                                    cdn.txn_code,
                                                    cdn.txn_no,
                                                    cdn.qty,
                                                    p_user_id,
                                                    sysdate 												          
                                                );	
		end loop;
		for cc in c_cost (p_date, ch.mtda_trans_code) loop
			begin 
				select mgt_transp_detail_cost_seq.nextval into v_id_cost from dual;	
			end;		
			insert into mgt_transp_detail_cost  (	mtdc_sys_id,
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
                                        values  (   v_id_cost,
                                                    v_id,
                                                    cc.mtr_priority,
                                                    cc.mtr_rate_type,
                                                    cc.mtr_max_cap, 
                                                    cc.mtr_rate,
                                                    cc.total_rate,
                                                    cc.mtr_destination,
                                                    cc.mtr_truck_type,
                                                    p_user_id,
                                                    sysdate 												          
                                                 );	
		end loop;
		begin
            update mgt_transp_dn_auto
            set  mtda_transp_txn = 'TPDN-'||v_transp_no,
                 mtda_upd_uid    = p_user_id,
                 mtda_upd_dt     = sysdate
            where mtda_dn_dt      = p_date
            and mtda_trans_code	= p_tp_code
            and mtda_truck_type = p_tp_type
            and mtda_truck_cap  = p_tp_cap
            and mtda_police_no 	= p_tp_police
            and mtda_driver		= p_tp_driver
            and mtda_destination= p_tp_destination
            and mtda_transp_txn is null;            
		end;			
	end loop;	
	commit;
end;

end;
