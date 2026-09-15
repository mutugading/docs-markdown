Function FNC_TRANSP_AMT_INV_MGT(P_SYS_ID NUMBER)
RETURN NUMBER
IS
V_GROSS_QTY NUMBER;
V_NET_QTY NUMBER;
V_AMOUNT NUMBER;
V_RATE_PERKG NUMBER;
V_INV_NET_QTY NUMBER;
V_TRANSP_AMT NUMBER;
Begin
    BEGIN
        SELECT SUM(gross_qty) gross_qty, sum(net_qty) net_qty, SUM(amount) AMOUNT
        INTO V_GROSS_QTY, V_NET_QTY, V_AMOUNT
        FROM
        (
        select MTDD_MTH_SYS_ID, sum(MTDD_DN_QTY) gross_qty, sum(net_qty) net_qty,
                (
                select nvl(sum(MTDC_TOTAL_RATE),0) 
                from MGT_TRANSP_DETAIL_COST
                where mtdc_mth_sys_id = MTDD_MTH_SYS_ID
                ) amount
        from MGT_TRANSP_DETAIL_DN,
            (
            select invh_txn_code, invh_no, sum(INVI_QTY_BU) / 1000 net_qty
            from ot_invoice_head, ot_invoice_item
            where invh_sys_id = invi_invh_sys_id
            group by invh_txn_code, invh_no
            ) inv
        where invh_txn_code = MTDD_DN_TXN_CODE 
        and invh_no = MTDD_DN_NO
        and MTDD_MTH_SYS_ID
        in
        (
        select MTDD_MTH_SYS_ID
        from MGT_TRANSP_DETAIL_DN
        where (MTDD_DN_TXN_CODE, MTDD_DN_NO) in
        (
        select INVR_REF_TXN_CODE, INVR_REF_NO
        from ot_invoice_head, ot_invoice_ref
        where invh_sys_id = p_sys_id
        and INVH_SYS_ID = INVR_INVH_SYS_ID 
        GROUP BY invh_sys_id, INVR_REF_TXN_CODE, INVR_REF_NO
        )
        group by MTDD_MTH_SYS_ID
        )
        group by MTDD_MTH_SYS_ID
        );
    EXCEPTION WHEN NO_DATA_FOUND THEN
        V_GROSS_QTY := 0;  
        V_NET_QTY := 0;
        V_AMOUNT := 0;
    END;
    
    IF NVL(V_NET_QTY,0) = 0 THEN
        V_RATE_PERKG := 0;
    ELSE
        V_RATE_PERKG := NVL(V_AMOUNT,0) / NVL(V_NET_QTY,0);
    END IF;
    
    BEGIN
        select sum(INVI_QTY_BU) / 1000 net_qty
        INTO V_INV_NET_QTY 
        from ot_invoice_head, ot_invoice_item
        where invh_sys_id = invi_invh_sys_id
        AND invh_sys_id = p_sys_id;
    EXCEPTION WHEN NO_DATA_FOUND THEN
        V_INV_NET_QTY := 0;
    END;
    V_TRANSP_AMT := NVL(V_RATE_PERKG,0) * NVL(V_INV_NET_QTY,0);
    
    RETURN(V_TRANSP_AMT);
    
End;
