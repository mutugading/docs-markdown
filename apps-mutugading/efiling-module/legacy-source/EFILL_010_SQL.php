<?php
$sqlData =
"
SELECT ROWNUM REC_NO,
       INV_FOLDER,
       INV_YY,
       INV_NO,
       OST_KEY_NO,
       TO_CHAR (INVH_DT, 'DD-MM-YYYY') INVH_DT,
       INVH_CUST_NAME,
       INVH_CURR_CODE,
       INVH_TERM_CODE,
       SALES_AMT,
       SALES_TAX,
       SALES_TOT_AMT,
       FP_FILE,
       DN_NO,
       DN_FOLDER,
       DN_YY,
       FP_FOLDER,
       FP_YY,
       KWITANSI,
       KWI_NCA,
       KWI_ADDRESS,
       KWI_TXN,
       KWI_YYYY KWI_YY,
       KWITANSI KWI_NO
  FROM (  SELECT A.INVH_TXN_CODE,
                 A.INVH_NO,
                 A.INVH_TXN_CODE || '-' || A.INVH_NO INV_NO,
                 A.INVH_TXN_CODE INV_FOLDER,
                 SUBSTR (A.INVH_NO, 1, 4) INV_YY,
                 INVH_DT,
                 INVH_CUST_CODE,
                 INVH_CURR_CODE,
                 INVH_CUST_NAME,
                 INVH_TERM_CODE,
                 (SELECT SUM (INVI_FC_ACT_VAL)
                    FROM MGTDAT.OT_INVOICE_ITEM
                   WHERE A.INVH_SYS_ID = INVI_INVH_SYS_ID)
                    SALES_AMT,
                 (SELECT NVL (
                            SUM (
                               DECODE (ITED_TED_TYPE_NUM,
                                       1, ITED_NET_FC_AMT,
                                       0)),
                            0)
                    FROM MGTDAT.OT_INVOICE_ITEM_TED
                   WHERE ITED_H_SYS_ID = A.INVH_SYS_ID)
                    SALES_TAX,
                 OST_FC_AMT SALES_TOT_AMT,
                 CASE
                    WHEN INVH_FP_NO IS NULL
                    THEN
                       ''
                    ELSE
                          'FP-'
                       || REPLACE (REPLACE (INVH_FP_NO, '.', ''), '-', '')
                 END
                    FP_FILE,
                 'FP SALES' FP_FOLDER,
                 SUBSTR (A.INVH_NO, 1, 4) FP_YY,
                 INVR_REF_TXN_CODE || '-' || INVR_REF_NO DN_NO, /*INVR_REF_TXN_CODE||' SALES' DN_FOLDER,*/
                 INVR_REF_TXN_CODE DN_FOLDER,
                 SUBSTR (INVR_REF_NO, 1, 4) DN_YY,
                 'FP SALES' FPADV_FOLDER,
                 OST_KEY_NO,
                 CASE
                    WHEN KWI_YY IS NOT NULL
                    THEN
                          'KWI-'
                       || LPAD (KWI_YY, 2, 0)
                       || LPAD (KWI_MM, 2, 0)
                       || LPAD (KWI_SLNO, 5, 0)
                 END
                    KWITANSI,
                 KWI_TXN,
                 KWI_YYYY,
                 KWI_NCA,
                 KWI_ADDRESS
            FROM (SELECT *
                    FROM mgtdat.ot_invoice_head a
                   WHERE     invh_type = 1
                         AND A.INVH_DT BETWEEN TO_DATE ('$DATE_FROM_S','YYYY-MM-DD')
                                           AND TO_DATE ('$DATE_TO_S','YYYY-MM-DD')) a,
                 mgtdat.ft_os,
                 mgtdat.ot_tax_invoice_mgt b,
                 mgtdat.ot_invoice_ref,
                 (SELECT KWI_YY,
                         KWI_MM,
                         KWI_SLNO,
                         KWI_INV_NO,
                         'KWI' KWI_TXN,
                         TO_CHAR (KWI_DATE, 'YYYY') KWI_YYYY,
                         KWI_NCA,
                         VSSV_NAME KWI_CUST,
                            VSSV_FIELD_01
                         || CASE
                               WHEN VSSV_FIELD_02 IS NOT NULL
                               THEN
                                  ', ' || VSSV_FIELD_02
                            END
                         || CASE
                               WHEN VSSV_FIELD_03 IS NOT NULL
                               THEN
                                  ', ' || VSSV_FIELD_03
                            END
                         || CASE
                               WHEN VSSV_FIELD_04 IS NOT NULL
                               THEN
                                  ', ' || VSSV_FIELD_04
                            END
                         || CASE
                               WHEN VSSV_FIELD_05 IS NOT NULL
                               THEN
                                  ', ' || VSSV_FIELD_05
                            END
                            KWI_ADDRESS
                    FROM mgtdat.IM_VS_STATIC_VALUE, mgtdat.PAKKWI
                   WHERE     vssv_vs_code = 'NCA'
                         AND KWI_NCA = VSSV_CODE
                         AND KWI_INV_DATE BETWEEN TO_DATE ('$DATE_FROM_S','YYYY-MM-DD')
                                              AND TO_DATE ('$DATE_TO_S','YYYY-MM-DD'))
           WHERE     a.invh_txn_code = ost_tran_code
                 AND a.invh_no = ost_doc_no
                 AND a.invh_txn_code NOT IN ('EIN')
                 AND a.invh_sys_id = b.invh_sys_id(+)
                 AND a.invh_sys_id = invr_invh_sys_id(+)
                 AND a.invh_txn_code || '-' || a.invh_no = KWI_INV_NO(+)
        ORDER BY invh_dt, a.invh_txn_code, a.invh_no)
"
                              ;

  $sqlDataDtl = "
SELECT OM_OST_KEY_NO MATCH_SYS_ID,
       OST_TRAN_CODE || '-' || OST_DOC_NO MATCH_TRN_CODE,
       to_char(OST_DOC_DT,'DD-MM-YYYY') MATCH_DOC_DT,
       NVL (OM_BASE_FC_AMT, OM_BASE_FOREX_FC_AMT) MATCH_FC_AMT,
       NVL (OM_BASE_LC_AMT, OM_BASE_FOREX_LC_AMT) MATCH_LC_AMT,
       CASE
          WHEN INVH_FP_NO IS NULL
             THEN ''
          ELSE    'FP-'
               || REPLACE (REPLACE (INVH_FP_NO, '.', ''), '-', '')
       END FP_ADV,
       'FP SALES' EFILLING_FOLDER, SUBSTR (OST_DOC_NO, 1, 4) FP_YY
       ,OST_TRAN_CODE || '-' || OST_DOC_NO
  FROM mgtdat.fv_os_match, mgtdat.ft_os, mgtdat.ot_tax_invoice_mgt
 WHERE ost_key_no = om_base_seq_no
   AND ost_tran_code = invh_txn_code(+)
   AND ost_doc_no = invh_doc_no(+)
   AND OST_TRAN_CODE NOT IN ('FXADJ','FXJ')
   and om_ost_key_no = $OST_KEY_NO_S       ";
?>
