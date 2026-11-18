<?php
$sqlData = "
SELECT  distinct payment_no, ph_doc_dt, supp_name, ph_bank_acnt_no, bank_name,
         ph_curr_code, ph_fc_amt, user_desc, ph_remarks, sbc_bank_name,
         sbc_bank_acnt_no, sbc_bank_others sbc_bank_acnt_name, trans_no,
         pd_ref_doc_ref, pd_fc_amt, PERIOD_YEAR, USER_FIELD_01, PH_FLEX_02, PH_FLEX_03, PH_FLEX_04
    FROM (SELECT sbc_supp_code, sbc_bank_name, sbc_bank_acnt_no,
                 sbc_bank_others, UPPER (sbc_bl_bank_addr1) sbc_bl_bank_addr1
            FROM mgtdat.om_supplier_bank_cont_detail),
         (SELECT ph_tran_code || '-' || ph_doc_no payment_no, ph_doc_dt,
                 ph_party_code, supp_name, ph_bank_acnt_no, bank_name,
                 ph_curr_code, ph_fc_amt, user_desc, ph_remarks,
                 pd_ref_tran_code || '-' || pd_ref_doc_no trans_no,
                 pd_ref_doc_ref, pd_fc_amt, SUBSTR (pd_ref_doc_no, 1, 4) PERIOD_YEAR,
                 user_field_01, PH_FLEX_02, PH_FLEX_03, PH_FLEX_04
            FROM mgtdat.ft_payment_header,
                 mgtdat.ft_payment_detail,
                 mgtdat.menu_user,
                 mgtdat.om_supplier,
                 mgtdat.fm_bank
           WHERE ph_doc_dt between TO_DATE ('$DATE_FROM_S', 'YYYY-MM-DD') and TO_DATE ('$DATE_TO_S', 'YYYY-MM-DD')
             AND ph_party_code = supp_code(+)
             AND ph_cr_uid = user_id
             AND pd_ph_sys_id = ph_sys_id
             AND ph_bank_code = bank_code(+)
          UNION ALL
          SELECT ph_tran_code || '-' || ph_doc_no payment_no, ph_doc_dt,
                 ph_party_code, supp_name, ph_bank_acnt_no, bank_name,
                 ph_curr_code, ph_fc_amt, user_desc, ph_remarks,
                 poad_config_fld_02 trans_no, poad_remarks, poad_amount,
                 SUBSTR (poad_config_fld_02,
                         INSTR (poad_config_fld_02, '-', 1) + 1,
                         4
                        ) yy,
                 user_field_01, PH_FLEX_02, PH_FLEX_03, PH_FLEX_04
            FROM mgtdat.ft_payment_header,
                 mgtdat.ft_payment_oth_acnt_detail,
                 mgtdat.menu_user,
                 mgtdat.om_supplier,
                 mgtdat.fm_bank
           WHERE ph_doc_dt between TO_DATE ('$DATE_FROM_S', 'YYYY-MM-DD') and TO_DATE ('$DATE_TO_S', 'YYYY-MM-DD')
             AND ph_party_code = supp_code(+)
             AND ph_cr_uid = user_id
             AND poad_ph_sys_id = ph_sys_id
             AND ph_bank_code = bank_code(+))
   WHERE ph_party_code = sbc_supp_code(+) AND user_field_01 = sbc_bl_bank_addr1(+)";
?>
