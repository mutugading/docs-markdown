package        pkg_transporter is 

procedure jv_provision (p_sys_id number, p_user_id varchar2, p_date date);
procedure tjv_bill (p_tpb_sys_id number, p_user_id varchar2, p_date date);
procedure jv_provision_chp (p_sys_id number, p_user_id varchar2, p_date date);
procedure tjv_bill_chp (p_tpb_sys_id number, p_user_id varchar2, p_date date);
procedure tjv_bill_add (p_tpb_sys_id number, p_user_id varchar2, p_date date);
procedure tjv_bill_chp_add (p_tpb_sys_id number, p_user_id varchar2, p_date date);
procedure insert_data (p_date date, p_user_id varchar2, p_tp_code varchar2, p_tp_type varchar2, p_tp_cap number, p_tp_police varchar2, p_tp_driver varchar2, p_tp_destination varchar2);

end;
