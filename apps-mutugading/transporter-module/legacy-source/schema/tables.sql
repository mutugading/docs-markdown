-- Struktur tabel modul Transporter — schema MGTDAT
-- Diambil dari data dictionary pada 2026-09-15.
-- Tabel backup manual (MGT_TP_PROVISION_<ddmmyyyy> dsb) sengaja tidak disertakan.

-- ============================ KOLOM ============================
MGT_TP_PROVISION               1  TP_ID                      NUMBER           NOT NULL
MGT_TP_PROVISION               2  TP_SYS_ID                  NUMBER           NOT NULL
MGT_TP_PROVISION               3  TP_NO                      VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION               4  TP_DT                      DATE             NOT NULL
MGT_TP_PROVISION               5  TP_CODE                    VARCHAR2(15)     
MGT_TP_PROVISION               6  TP_NAME                    VARCHAR2(240)    
MGT_TP_PROVISION               7  TP_TRUCK_TYPE              VARCHAR2(150)    
MGT_TP_PROVISION               8  TP_CAP                     NUMBER           
MGT_TP_PROVISION               9  TP_DESTINATION             VARCHAR2(240)    
MGT_TP_PROVISION              10  TP_POL_NO                  VARCHAR2(30)     
MGT_TP_PROVISION              11  TP_DRIVER                  VARCHAR2(100)    
MGT_TP_PROVISION              12  TP_QTY                     NUMBER           
MGT_TP_PROVISION              13  TP_GROSS_QTY               NUMBER           
MGT_TP_PROVISION              14  TP_AMT                     NUMBER           
MGT_TP_PROVISION              15  TP_OTH_AMT                 NUMBER           
MGT_TP_PROVISION              16  TP_STATUS                  VARCHAR2(50)     
MGT_TP_PROVISION              17  TP_JV_NO                   VARCHAR2(50)     
MGT_TP_PROVISION              18  TP_CR_DT                   DATE             
MGT_TP_PROVISION              19  TP_CR_UID                  VARCHAR2(50)     
MGT_TP_PROVISION              20  TP_UP_DT                   DATE             
MGT_TP_PROVISION              21  TP_UP_UID                  VARCHAR2(50)     
MGT_TP_PROVISION              22  TP_MATCH_STATUS            VARCHAR2(1)      
MGT_TP_PROVISION              23  TP_TPB_SYS_ID              NUMBER           
MGT_TP_PROVISION              24  TP_TOTAL_AMT               NUMBER           
MGT_TP_PROVISION              25  TP_AMT_TYPING              NUMBER           
MGT_TP_PROVISION              26  TP_AMT_DIFF                NUMBER           
MGT_TP_PROVISION              27  TP_TJV_NO                  VARCHAR2(50)     
MGT_TP_PROVISION              28  TP_AMT_PPH_GROSSUP         NUMBER           
MGT_TP_PROVISION              29  TP_REMARK                  VARCHAR2(250)    
MGT_TP_PROVISION              30  TP_MAIN_ACNT               VARCHAR2(15)     
MGT_TP_PROVISION              31  TP_AMT_POSTING             NUMBER           
MGT_TP_PROVISION              32  TP_DN_NO                   VARCHAR2(100)    
MGT_TP_PROVISION              33  TP_JV_DT                   DATE             
MGT_TP_PROVISION              34  TP_DUE_DATE                DATE             
MGT_TP_PROVISION              35  TP_NAME_CODE               VARCHAR2(15)     
MGT_TP_PROVISION              36  TP_GROSSUP_VALUE           NUMBER           
MGT_TP_PROVISION              37  TP_STATUS_DOC              VARCHAR2(30)     
MGT_TP_PROVISION_ADD           1  TPA_ID                     NUMBER           NOT NULL
MGT_TP_PROVISION_ADD           2  TPA_SYS_ID                 NUMBER           NOT NULL
MGT_TP_PROVISION_ADD           3  TPA_NO                     VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION_ADD           4  TPA_DT                     DATE             NOT NULL
MGT_TP_PROVISION_ADD           5  TPA_CODE                   VARCHAR2(15)     
MGT_TP_PROVISION_ADD           6  TPA_NAME                   VARCHAR2(240)    
MGT_TP_PROVISION_ADD           7  TPA_TRUCK_TYPE             VARCHAR2(150)    
MGT_TP_PROVISION_ADD           8  TPA_CAP                    NUMBER           
MGT_TP_PROVISION_ADD           9  TPA_DESTINATION            VARCHAR2(240)    
MGT_TP_PROVISION_ADD          10  TPA_POL_NO                 VARCHAR2(30)     
MGT_TP_PROVISION_ADD          11  TPA_DRIVER                 VARCHAR2(100)    
MGT_TP_PROVISION_ADD          12  TPA_QTY                    NUMBER           
MGT_TP_PROVISION_ADD          13  TPA_GROSS_QTY              NUMBER           
MGT_TP_PROVISION_ADD          14  TPA_AMT                    NUMBER           
MGT_TP_PROVISION_ADD          15  TPA_OTH_AMT                NUMBER           
MGT_TP_PROVISION_ADD          16  TPA_STATUS                 VARCHAR2(50)     
MGT_TP_PROVISION_ADD          17  TPA_JV_NO                  VARCHAR2(50)     
MGT_TP_PROVISION_ADD          18  TPA_CR_DT                  DATE             
MGT_TP_PROVISION_ADD          19  TPA_CR_UID                 VARCHAR2(50)     
MGT_TP_PROVISION_ADD          20  TPA_UP_DT                  DATE             
MGT_TP_PROVISION_ADD          21  TPA_UP_UID                 VARCHAR2(50)     
MGT_TP_PROVISION_ADD          22  TPA_MATCH_STATUS           VARCHAR2(1)      
MGT_TP_PROVISION_ADD          23  TPA_TPBA_SYS_ID            NUMBER           
MGT_TP_PROVISION_ADD          24  TPA_TOTAL_AMT              NUMBER           
MGT_TP_PROVISION_ADD          25  TPA_AMT_TYPING             NUMBER           
MGT_TP_PROVISION_ADD          26  TPA_AMT_DIFF               NUMBER           
MGT_TP_PROVISION_ADD          27  TPA_TJV_NO                 VARCHAR2(50)     
MGT_TP_PROVISION_ADD          28  TPA_TJV_ADD_NO             VARCHAR2(50)     
MGT_TP_PROVISION_ADD          29  TPA_AMT_PPH_GROSSUP        NUMBER           
MGT_TP_PROVISION_ADD          30  TPA_REMARK                 VARCHAR2(250)    
MGT_TP_PROVISION_ADD          31  TPA_MAIN_ACNT              VARCHAR2(15)     
MGT_TP_PROVISION_ADD          32  TPA_AMT_POSTING            NUMBER           
MGT_TP_PROVISION_ADD          33  TPA_DN_NO                  VARCHAR2(100)    
MGT_TP_PROVISION_ADD          34  TPA_JV_DT                  DATE             
MGT_TP_PROVISION_ADD          35  TPA_DUE_DATE               DATE             
MGT_TP_PROVISION_ADD          36  TPA_NAME_CODE              VARCHAR2(15)     
MGT_TP_PROVISION_ADD          37  TPA_GROSSUP_VALUE          NUMBER           
MGT_TP_PROVISION_ADD          38  TPA_STATUS_DOC             VARCHAR2(30)     
MGT_TP_PROVISION_BILL          1  TPB_SYS_ID                 NUMBER           NOT NULL
MGT_TP_PROVISION_BILL          2  TPB_TRX_NO                 VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION_BILL          3  TPB_DT                     DATE             NOT NULL
MGT_TP_PROVISION_BILL          4  TPB_SUPP_CODE              VARCHAR2(15)     NOT NULL
MGT_TP_PROVISION_BILL          5  TPB_SUPP_NAME              VARCHAR2(250)    
MGT_TP_PROVISION_BILL          6  TPB_BILL_NO                VARCHAR2(100)    
MGT_TP_PROVISION_BILL          7  TPB_CURR                   VARCHAR2(30)     
MGT_TP_PROVISION_BILL          8  TPB_FP_NO                  VARCHAR2(50)     
MGT_TP_PROVISION_BILL          9  TPB_FP_DT                  DATE             
MGT_TP_PROVISION_BILL         10  TPB_BILL_AMT               NUMBER           
MGT_TP_PROVISION_BILL         11  TPB_PPN                    NUMBER           
MGT_TP_PROVISION_BILL         12  TPB_PPN_AMT                NUMBER           
MGT_TP_PROVISION_BILL         13  TPB_PPH                    NUMBER           
MGT_TP_PROVISION_BILL         14  TPB_PPH_AMT                NUMBER           
MGT_TP_PROVISION_BILL         15  TPB_TOTAL                  NUMBER           
MGT_TP_PROVISION_BILL         16  TPB_REMARK                 VARCHAR2(250)    
MGT_TP_PROVISION_BILL         17  TPB_CR_DT                  DATE             
MGT_TP_PROVISION_BILL         18  TPB_CR_UID                 VARCHAR2(50)     
MGT_TP_PROVISION_BILL         19  TPB_UP_DT                  DATE             
MGT_TP_PROVISION_BILL         20  TPB_UP_UID                 VARCHAR2(50)     
MGT_TP_PROVISION_BILL         21  TPB_BILL_DT                DATE             
MGT_TP_PROVISION_BILL         22  TPB_STATUS                 VARCHAR2(20)     
MGT_TP_PROVISION_BILL         23  TPB_TYPE                   VARCHAR2(10)     NOT NULL
MGT_TP_PROVISION_BILL         24  TPB_DUE_DATE               DATE             
MGT_TP_PROVISION_BILL         25  TPB_VOUCHER_DATE           DATE             
MGT_TP_PROVISION_BILL         26  TPB_RECEIPT_DATE           DATE             
MGT_TP_PROVISION_BILL_ADD      1  TPBA_SYS_ID                NUMBER           NOT NULL
MGT_TP_PROVISION_BILL_ADD      2  TPBA_TRX_NO                VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION_BILL_ADD      3  TPBA_DT                    DATE             NOT NULL
MGT_TP_PROVISION_BILL_ADD      4  TPBA_SUPP_CODE             VARCHAR2(15)     NOT NULL
MGT_TP_PROVISION_BILL_ADD      5  TPBA_SUPP_NAME             VARCHAR2(250)    
MGT_TP_PROVISION_BILL_ADD      6  TPBA_BILL_NO               VARCHAR2(100)    
MGT_TP_PROVISION_BILL_ADD      7  TPBA_CURR                  VARCHAR2(30)     
MGT_TP_PROVISION_BILL_ADD      8  TPBA_FP_NO                 VARCHAR2(50)     
MGT_TP_PROVISION_BILL_ADD      9  TPBA_FP_DT                 DATE             
MGT_TP_PROVISION_BILL_ADD     10  TPBA_BILL_AMT              NUMBER           
MGT_TP_PROVISION_BILL_ADD     11  TPBA_PPN                   NUMBER           
MGT_TP_PROVISION_BILL_ADD     12  TPBA_PPN_AMT               NUMBER           
MGT_TP_PROVISION_BILL_ADD     13  TPBA_PPH                   NUMBER           
MGT_TP_PROVISION_BILL_ADD     14  TPBA_PPH_AMT               NUMBER           
MGT_TP_PROVISION_BILL_ADD     15  TPBA_TOTAL                 NUMBER           
MGT_TP_PROVISION_BILL_ADD     16  TPBA_REMARK                VARCHAR2(250)    
MGT_TP_PROVISION_BILL_ADD     17  TPBA_CR_DT                 DATE             
MGT_TP_PROVISION_BILL_ADD     18  TPBA_CR_UID                VARCHAR2(50)     
MGT_TP_PROVISION_BILL_ADD     19  TPBA_UP_DT                 DATE             
MGT_TP_PROVISION_BILL_ADD     20  TPBA_UP_UID                VARCHAR2(50)     
MGT_TP_PROVISION_BILL_ADD     21  TPBA_BILL_DT               DATE             
MGT_TP_PROVISION_BILL_ADD     22  TPBA_STATUS                VARCHAR2(20)     
MGT_TP_PROVISION_BILL_ADD     23  TPBA_TYPE                  VARCHAR2(10)     NOT NULL
MGT_TP_PROVISION_BILL_ADD     24  TPBA_DUE_DATE              DATE             
MGT_TP_PROVISION_BILL_ADD     25  TPBA_VOUCHER_DATE          DATE             
MGT_TP_PROVISION_BILL_ADD     26  TPBA_VOUCHER_DATE_ADD      DATE             
MGT_TP_PROVISION_DEL           1  TP_ID                      NUMBER           
MGT_TP_PROVISION_DEL           2  TP_SYS_ID                  NUMBER           NOT NULL
MGT_TP_PROVISION_DEL           3  TP_NO                      VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION_DEL           4  TP_DT                      DATE             NOT NULL
MGT_TP_PROVISION_DEL           5  TP_CODE                    VARCHAR2(15)     
MGT_TP_PROVISION_DEL           6  TP_NAME                    VARCHAR2(240)    
MGT_TP_PROVISION_DEL           7  TP_TRUCK_TYPE              VARCHAR2(150)    
MGT_TP_PROVISION_DEL           8  TP_CAP                     NUMBER           
MGT_TP_PROVISION_DEL           9  TP_DESTINATION             VARCHAR2(240)    
MGT_TP_PROVISION_DEL          10  TP_POL_NO                  VARCHAR2(30)     
MGT_TP_PROVISION_DEL          11  TP_DRIVER                  VARCHAR2(100)    
MGT_TP_PROVISION_DEL          12  TP_QTY                     NUMBER           
MGT_TP_PROVISION_DEL          13  TP_GROSS_QTY               NUMBER           
MGT_TP_PROVISION_DEL          14  TP_AMT                     NUMBER           
MGT_TP_PROVISION_DEL          15  TP_OTH_AMT                 NUMBER           
MGT_TP_PROVISION_DEL          16  TP_STATUS                  VARCHAR2(50)     
MGT_TP_PROVISION_DEL          17  TP_JV_NO                   VARCHAR2(50)     
MGT_TP_PROVISION_DEL          18  TP_CR_DT                   DATE             
MGT_TP_PROVISION_DEL          19  TP_CR_UID                  VARCHAR2(50)     
MGT_TP_PROVISION_DEL          20  TP_UP_DT                   DATE             
MGT_TP_PROVISION_DEL          21  TP_UP_UID                  VARCHAR2(50)     
MGT_TP_PROVISION_DEL          22  TP_MATCH_STATUS            VARCHAR2(1)      
MGT_TP_PROVISION_DEL          23  TP_TPB_SYS_ID              NUMBER           
MGT_TP_PROVISION_DEL          24  TP_TOTAL_AMT               NUMBER           
MGT_TP_PROVISION_DEL          25  TP_AMT_TYPING              NUMBER           
MGT_TP_PROVISION_DEL          26  TP_AMT_DIFF                NUMBER           
MGT_TP_PROVISION_DEL          27  TP_TJV_NO                  VARCHAR2(50)     
MGT_TP_PROVISION_DEL          28  TP_AMT_PPH_GROSSUP         NUMBER           
MGT_TP_PROVISION_DEL          29  TP_REMARK                  VARCHAR2(250)    
MGT_TP_PROVISION_DEL          30  TP_MAIN_ACNT               VARCHAR2(15)     
MGT_TP_PROVISION_DEL          31  TP_AMT_POSTING             NUMBER           
MGT_TP_PROVISION_DEL          32  TP_DN_NO                   VARCHAR2(100)    
MGT_TP_PROVISION_DEL          33  TP_JV_DT                   DATE             
MGT_TP_PROVISION_DEL          34  TP_DUE_DATE                DATE             
MGT_TP_PROVISION_DEL          35  TP_NAME_CODE               VARCHAR2(15)     
MGT_TP_PROVISION_DEL          36  TP_GROSSUP_VALUE           NUMBER           
MGT_TP_PROVISION_DEL          37  TP_STATUS_DOC              VARCHAR2(30)     
MGT_TP_PROVISION_DN            1  TPD_SYS_ID                 NUMBER           NOT NULL
MGT_TP_PROVISION_DN            2  TPD_TP_SYS_ID              NUMBER           NOT NULL
MGT_TP_PROVISION_DN            3  TPD_NO                     VARCHAR2(50)     NOT NULL
MGT_TP_PROVISION_DN            4  TPD_DT                     DATE             NOT NULL
MGT_TP_PROVISION_DN            5  TPD_DN_NO                  VARCHAR2(15)     NOT NULL
MGT_TP_PROVISION_DN            6  TPD_QTY                    NUMBER           
MGT_TP_PROVISION_DN            7  TPD_STS_PRS                VARCHAR2(1)      
MGT_TP_PROVISION_DN            8  TPD_STS_DOC                VARCHAR2(1)      
MGT_TP_PROVISION_DN            9  TPD_CR_DT                  DATE             
MGT_TP_PROVISION_DN           10  TPD_CR_UID                 VARCHAR2(20)     
MGT_TP_PROVISION_DN           11  TPD_UP_DT                  DATE             
MGT_TP_PROVISION_DN           12  TPD_UP_UID                 VARCHAR2(20)     
MGT_TP_PROVISION_TPCHP         1  TP_ID                      NUMBER           
MGT_TP_PROVISION_TPCHP         2  TP_SYS_ID                  NUMBER           NOT NULL
MGT_TP_PROVISION_TPCHP         3  TP_NO                      VARCHAR2(100)    NOT NULL
MGT_TP_PROVISION_TPCHP         4  TP_DT                      DATE             NOT NULL
MGT_TP_PROVISION_TPCHP         5  TP_CODE                    VARCHAR2(15)     
MGT_TP_PROVISION_TPCHP         6  TP_NAME                    VARCHAR2(240)    
MGT_TP_PROVISION_TPCHP         7  TP_TRUCK_TYPE              VARCHAR2(150)    
MGT_TP_PROVISION_TPCHP         8  TP_CAP                     NUMBER           
MGT_TP_PROVISION_TPCHP         9  TP_DESTINATION             VARCHAR2(240)    
MGT_TP_PROVISION_TPCHP        10  TP_POL_NO                  VARCHAR2(30)     
MGT_TP_PROVISION_TPCHP        11  TP_DRIVER                  VARCHAR2(100)    
MGT_TP_PROVISION_TPCHP        12  TP_QTY                     NUMBER           
MGT_TP_PROVISION_TPCHP        13  TP_GROSS_QTY               NUMBER           
MGT_TP_PROVISION_TPCHP        14  TP_AMT                     NUMBER           
MGT_TP_PROVISION_TPCHP        15  TP_OTH_AMT                 NUMBER           
MGT_TP_PROVISION_TPCHP        16  TP_STATUS                  VARCHAR2(50)     
MGT_TP_PROVISION_TPCHP        17  TP_JV_NO                   VARCHAR2(50)     
MGT_TP_PROVISION_TPCHP        18  TP_CR_DT                   DATE             
MGT_TP_PROVISION_TPCHP        19  TP_CR_UID                  VARCHAR2(50)     
MGT_TP_PROVISION_TPCHP        20  TP_UP_DT                   DATE             
MGT_TP_PROVISION_TPCHP        21  TP_UP_UID                  VARCHAR2(50)     
MGT_TP_PROVISION_TPCHP        22  TP_MATCH_STATUS            VARCHAR2(1)      
MGT_TP_PROVISION_TPCHP        23  TP_TPB_SYS_ID              NUMBER           
MGT_TP_PROVISION_TPCHP        24  TP_TOTAL_AMT               NUMBER           
MGT_TP_PROVISION_TPCHP        25  TP_AMT_TYPING              NUMBER           
MGT_TP_PROVISION_TPCHP        26  TP_AMT_DIFF                NUMBER           
MGT_TP_PROVISION_TPCHP        27  TP_TJV_NO                  VARCHAR2(50)     
MGT_TP_PROVISION_TPCHP        28  TP_AMT_PPH_GROSSUP         NUMBER           
MGT_TP_PROVISION_TPCHP        29  TP_REMARK                  VARCHAR2(250)    
MGT_TP_PROVISION_TPCHP        30  TP_MAIN_ACNT               VARCHAR2(15)     
MGT_TP_PROVISION_TPCHP        31  TP_AMT_POSTING             NUMBER           
MGT_TP_PROVISION_TPCHP        32  TP_DN_NO                   VARCHAR2(100)    
MGT_TP_PROVISION_TPCHP        33  TP_JV_DT                   DATE             
MGT_TP_PROVISION_TPCHP        34  TP_DUE_DATE                DATE             
MGT_TP_PROVISION_TPCHP        35  TP_NAME_CODE               VARCHAR2(15)     
MGT_TP_PROVISION_TPCHP        36  TP_GROSSUP_VALUE           NUMBER           
MGT_TP_PROVISION_TPCHP        37  TP_STATUS_DOC              VARCHAR2(30)     
MGT_TRANSP_DETAIL_COST         1  MTDC_SYS_ID                NUMBER           NOT NULL
MGT_TRANSP_DETAIL_COST         2  MTDC_MTH_SYS_ID            NUMBER           
MGT_TRANSP_DETAIL_COST         3  MTDC_PRIORITY              NUMBER           
MGT_TRANSP_DETAIL_COST         4  MTDC_RATE_TYPE             VARCHAR2(10)     
MGT_TRANSP_DETAIL_COST         5  MTDC_QTY                   NUMBER           
MGT_TRANSP_DETAIL_COST         6  MTDC_RATE                  NUMBER           
MGT_TRANSP_DETAIL_COST         7  MTDC_TOTAL_RATE            NUMBER           
MGT_TRANSP_DETAIL_COST         8  MTDC_DESTINATION           VARCHAR2(100)    
MGT_TRANSP_DETAIL_COST         9  MTDC_TRUCK_TYPE            VARCHAR2(50)     
MGT_TRANSP_DETAIL_COST        10  MTDC_CR_UID                VARCHAR2(15)     
MGT_TRANSP_DETAIL_COST        11  MTDC_CR_DT                 DATE             
MGT_TRANSP_DETAIL_COST        12  MTDC_UPD_UID               VARCHAR2(15)     
MGT_TRANSP_DETAIL_COST        13  MTDC_UPD_DT                DATE             
MGT_TRANSP_DETAIL_DN           1  MTDD_SYS_ID                NUMBER           NOT NULL
MGT_TRANSP_DETAIL_DN           2  MTDD_MTH_SYS_ID            NUMBER           
MGT_TRANSP_DETAIL_DN           3  MTDD_DN_TXN_CODE           VARCHAR2(15)     
MGT_TRANSP_DETAIL_DN           4  MTDD_DN_NO                 NUMBER           
MGT_TRANSP_DETAIL_DN           5  MTDD_DN_QTY                NUMBER           
MGT_TRANSP_DETAIL_DN           6  MTDD_USED_STATUS           VARCHAR2(1)      
MGT_TRANSP_DETAIL_DN           7  MTDD_CR_UID                VARCHAR2(15)     
MGT_TRANSP_DETAIL_DN           8  MTDD_CR_DT                 DATE             
MGT_TRANSP_DETAIL_DN           9  MTDD_UPD_UID               VARCHAR2(15)     
MGT_TRANSP_DETAIL_DN          10  MTDD_UPD_DT                DATE             
MGT_TRANSP_DETAIL_DN          11  MTDD_STS_PRS               CHAR(1)          
MGT_TRANSP_DETAIL_DN          12  MTDD_STS_DOC               VARCHAR2(1)      
MGT_TRANSP_DETAIL_OTHCHG       1  MTDO_SYS_ID                NUMBER           NOT NULL
MGT_TRANSP_DETAIL_OTHCHG       2  MTDO_MTH_SYS_ID            NUMBER           
MGT_TRANSP_DETAIL_OTHCHG       3  MTDO_OTHCHG_ID             VARCHAR2(20)     
MGT_TRANSP_DETAIL_OTHCHG       4  MTDO_OTHCHG_NAME           VARCHAR2(200)    
MGT_TRANSP_DETAIL_OTHCHG       5  MTDO_OTHCHG_REMARKS        VARCHAR2(200)    
MGT_TRANSP_DETAIL_OTHCHG       6  MTDO_OTHCHG_AMOUNT         NUMBER           
MGT_TRANSP_DETAIL_OTHCHG       7  MTDO_CR_UID                VARCHAR2(15)     
MGT_TRANSP_DETAIL_OTHCHG       8  MTDO_CR_DT                 DATE             
MGT_TRANSP_DETAIL_OTHCHG       9  MTDO_UPD_UID               VARCHAR2(15)     
MGT_TRANSP_DETAIL_OTHCHG      10  MTDO_UPD_DT                DATE             
MGT_TRANSP_DETAIL_OTHCHG      11  MTDO_STATUS                VARCHAR2(20)     
MGT_TRANSP_DETAIL_OTHCHG      12  MTDO_TRANSP_NO             VARCHAR2(30)     
MGT_TRANSP_DETAIL_OTHCHG      13  MTDO_TRANSP_DT             DATE             
MGT_TRANSP_DETAIL_OTHCHG      14  MTDO_TRANSP_NAME           VARCHAR2(200)    
MGT_TRANSP_DETAIL_OTHCHG      15  MTDO_DESTINATION           VARCHAR2(100)    
MGT_TRANSP_DETAIL_OTHCHG      16  MTDO_TRUCK_NO              VARCHAR2(30)     
MGT_TRANSP_DETAIL_OTHCHG      17  MTDO_DRIVER                VARCHAR2(30)     
MGT_TRANSP_DN_AUTO             1  MTDA_SYS_ID                NUMBER           
MGT_TRANSP_DN_AUTO             2  MTDA_DN_SYS_ID             NUMBER           NOT NULL
MGT_TRANSP_DN_AUTO             3  MTDA_DN_NO                 VARCHAR2(15)     
MGT_TRANSP_DN_AUTO             4  MTDA_DN_DT                 DATE             
MGT_TRANSP_DN_AUTO             5  MTDA_DN_QTY                NUMBER           
MGT_TRANSP_DN_AUTO             6  MTDA_TRANS_CODE            VARCHAR2(20)     
MGT_TRANSP_DN_AUTO             7  MTDA_TRANS_NAME            VARCHAR2(200)    
MGT_TRANSP_DN_AUTO             8  MTDA_TRUCK_TYPE            VARCHAR2(30)     
MGT_TRANSP_DN_AUTO             9  MTDA_TRUCK_CAP             NUMBER           
MGT_TRANSP_DN_AUTO            10  MTDA_POLICE_NO             VARCHAR2(20)     
MGT_TRANSP_DN_AUTO            11  MTDA_DRIVER                VARCHAR2(75)     
MGT_TRANSP_DN_AUTO            12  MTDA_DESTINATION           VARCHAR2(150)    
MGT_TRANSP_DN_AUTO            13  MTDA_TRANSP_TXN            VARCHAR2(75)     
MGT_TRANSP_DN_AUTO            14  MTDA_CR_UID                VARCHAR2(15)     
MGT_TRANSP_DN_AUTO            15  MTDA_CR_DT                 DATE             
MGT_TRANSP_DN_AUTO            16  MTDA_UPD_UID               VARCHAR2(15)     
MGT_TRANSP_DN_AUTO            17  MTDA_UPD_DT                DATE             
MGT_TRANSP_DN_AUTO            18  MTDA_CUST_NAME             VARCHAR2(200)    
MGT_TRANSP_DN_AUTO            19  MTDA_GROSS_WT              NUMBER           
MGT_TRANSP_DN_AUTO            20  MTDA_BOX                   NUMBER           
MGT_TRANSP_DN_AUTO            21  MTDA_GROSS_WT_TEMP         NUMBER           
MGT_TRANSP_DN_AUTO            22  MTDA_BOX_TEMP              NUMBER           
MGT_TRANSP_HEAD                1  MTH_SYS_ID                 NUMBER           NOT NULL
MGT_TRANSP_HEAD                2  MTH_TXN_CODE               VARCHAR2(10)     NOT NULL
MGT_TRANSP_HEAD                3  MTH_TRANSP_NO              NUMBER(10,0)     NOT NULL
MGT_TRANSP_HEAD                4  MTH_NO                     VARCHAR2(20)     NOT NULL
MGT_TRANSP_HEAD                5  MTH_DT                     DATE             NOT NULL
MGT_TRANSP_HEAD                6  MTH_TRANSP_NAME            VARCHAR2(200)    
MGT_TRANSP_HEAD                7  MTH_TRUCK_TYPE             VARCHAR2(50)     
MGT_TRANSP_HEAD                8  MTH_TRUCK_CAP              NUMBER           
MGT_TRANSP_HEAD                9  MTH_DESTINATION            VARCHAR2(100)    
MGT_TRANSP_HEAD               10  MTH_NO_POLICE              VARCHAR2(30)     
MGT_TRANSP_HEAD               11  MTH_DRIVER                 VARCHAR2(100)    
MGT_TRANSP_HEAD               12  MTH_CR_UID                 VARCHAR2(15)     
MGT_TRANSP_HEAD               13  MTH_CR_DT                  DATE             
MGT_TRANSP_HEAD               14  MTH_UPD_UID                VARCHAR2(15)     
MGT_TRANSP_HEAD               15  MTH_UPD_DT                 DATE             
MGT_TRANSP_HEAD               16  MTH_STATUS                 VARCHAR2(50)     
MGT_TRANSP_HEAD               17  MTH_AMENDMENT              NUMBER           
MGT_TRANSP_HEAD               18  MTH_TRANSP_CODE            VARCHAR2(10)     
MGT_TRANSP_HEAD               19  MTH_REMARK                 VARCHAR2(240)    
MGT_TRANSP_MASTER              1  MTM_NO                     VARCHAR2(15)     NOT NULL
MGT_TRANSP_MASTER              2  MTM_TRANSP_NAME            VARCHAR2(200)    
MGT_TRANSP_MASTER              3  MTM_TRUCK_TYPE             VARCHAR2(50)     
MGT_TRANSP_MASTER              4  MTM_TRUCK_CAP              NUMBER           
MGT_TRANSP_MASTER              5  MTM_DESTINATION            VARCHAR2(100)    
MGT_TRANSP_MASTER              6  MTM_CR_UID                 VARCHAR2(15)     
MGT_TRANSP_MASTER              7  MTM_CR_DT                  DATE             
MGT_TRANSP_MASTER              8  MTM_UPD_UID                VARCHAR2(15)     
MGT_TRANSP_MASTER              9  MTM_UPD_DT                 DATE             
MGT_TRANSP_MASTER             10  MTM_CUST_SUPP              VARCHAR2(20)     
MGT_TRANSP_MASTER             11  MTM_CUST_SUPP_NAME         VARCHAR2(200)    
MGT_TRANSP_MASTER             12  MTM_TYPE                   VARCHAR2(3)      
MGT_TRANSP_MASTER             13  MTM_TRANSP_CODE            VARCHAR2(20)     
MGT_TRANSP_MASTER             14  MTM_FRZ_FLAG_NUM           NUMBER           
MGT_TRANSP_RATE                1  MTR_NO                     NUMBER           NOT NULL
MGT_TRANSP_RATE                2  MTR_MTM_NO                 VARCHAR2(15)     
MGT_TRANSP_RATE                3  MTR_PRIORITY               NUMBER           
MGT_TRANSP_RATE                4  MTR_RATE_TYPE              VARCHAR2(10)     
MGT_TRANSP_RATE                5  MTR_MAX_CAP                NUMBER           
MGT_TRANSP_RATE                6  MTR_RATE                   NUMBER           
MGT_TRANSP_RATE                7  MTR_DESTINATION            VARCHAR2(100)    
MGT_TRANSP_RATE                8  MTR_TRUCK_TYPE             VARCHAR2(50)     
MGT_TRANSP_RATE                9  MTR_CR_UID                 VARCHAR2(15)     
MGT_TRANSP_RATE               10  MTR_CR_DT                  DATE             
MGT_TRANSP_RATE               11  MTR_UPD_UID                VARCHAR2(15)     
MGT_TRANSP_RATE               12  MTR_UPD_DT                 DATE             

-- ======================== CONSTRAINT ========================
MGT_TP_PROVISION             MGT_TP_PROVISION_PK              P  TP_SYS_ID, TP_NO, TP_ID
MGT_TP_PROVISION_ADD         MGT_TP_PROVISION_ADD_PK          P  TPA_SYS_ID, TPA_NO, TPA_ID
MGT_TP_PROVISION_BILL        MGT_TP_PROVISION_BILL_PK         P  TPB_TRX_NO, TPB_DT, TPB_TYPE
MGT_TP_PROVISION_BILL_ADD    MGT_TP_PROVISION_BILL_ADD_PK     P  TPBA_TRX_NO, TPBA_DT, TPBA_TYPE
MGT_TP_PROVISION_DN          MGT_TP_PROVISION_DN_PK           P  TPD_SYS_ID, TPD_TP_SYS_ID, TPD_NO, TPD_DT, TPD_DN_NO
MGT_TRANSP_DETAIL_COST       MGT_TRANSP_DETAIL_COST_PK        P  MTDC_SYS_ID
MGT_TRANSP_DETAIL_DN         MGT_TRANSP_DETAIL_DN_PK          P  MTDD_SYS_ID
MGT_TRANSP_DETAIL_OTHCHG     MGT_TRANSP_DETAIL_OTHCHG_PK      P  MTDO_SYS_ID
MGT_TRANSP_DETAIL_OTHCHG_AAM MGT_TRANSP_DETAIL_OTHCHG1_PK     P  MTDO_TRANSP_NO, MTDO_TRANSP_DT, MTDO_TRANSP_NAME, MTDO_OTHCHG_REMARKS
MGT_TRANSP_DN_AUTO           MGT_TRANSP_DN_AUTO_IDX           P  MTDA_DN_SYS_ID
MGT_TRANSP_HEAD              MGT_TRANSP_HEAD_PK               P  MTH_SYS_ID
MGT_TRANSP_MASTER            MGT_TRANSP_MASTER_PK             P  MTM_NO
MGT_TRANSP_RATE              MGT_TRANSP_RATE_PK               P  MTR_NO
MGT_TRANSP_RATE              MGT_TRANSP_RATE_FK               R  MTR_MTM_NO

-- ========================== INDEX ===========================
MGT_TP_PROVISION             MGT_TP_PROVISION_PK                UNIQUE
MGT_TP_PROVISION_ADD         MGT_TP_PROVISION_ADD_PK            UNIQUE
MGT_TP_PROVISION_BILL        MGT_TP_PROVISION_BILL_PK           UNIQUE
MGT_TP_PROVISION_BILL_ADD    MGT_TP_PROVISION_BILL_ADD_PK       UNIQUE
MGT_TP_PROVISION_DN          MGT_TP_PROVISION_DN_PK             UNIQUE
MGT_TRANSP_DETAIL_COST       MGT_TRANSP_DETAIL_COST_PK          UNIQUE
MGT_TRANSP_DETAIL_DN         MGT_TRANSP_DETAIL_DN_PK            UNIQUE
MGT_TRANSP_DETAIL_OTHCHG     MGT_TRANSP_DETAIL_OTHCHG_PK        UNIQUE
MGT_TRANSP_DETAIL_OTHCHG_AAM MGT_TRANSP_DETAIL_OTHCHG1_PK       UNIQUE
MGT_TRANSP_DN_AUTO           MGT_TRANSP_DN_AUTO_IDX             UNIQUE
MGT_TRANSP_HEAD              MGT_TRANSP_HEAD_PK                 UNIQUE
MGT_TRANSP_MASTER            MGT_TRANSP_MASTER_PK               UNIQUE
MGT_TRANSP_RATE              MGT_TRANSP_RATE_PK                 UNIQUE

-- ========================= SEQUENCE =========================
MGT_TP_PROVISION_ADD_SEQ           last_number=47  increment=1
MGT_TP_PROVISION_BILL_ADD_SEQ      last_number=6  increment=1
MGT_TP_PROVISION_BILL_SEQ          last_number=4094  increment=1
MGT_TP_PROVISION_DN_SEQ            last_number=802  increment=1
MGT_TP_PROVISION_ID_SEQ            last_number=4636  increment=1
MGT_TP_PROVISION_SEQ               last_number=47290  increment=1
MGT_TRANSP_DETAIL_COST_SEQ         last_number=42312  increment=1
MGT_TRANSP_DETAIL_DN_SEQ           last_number=96476  increment=1
MGT_TRANSP_DETAIL_OTHCHG_SEQ       last_number=891  increment=1
MGT_TRANSP_DN_AUTO_SEQ             last_number=29257  increment=1
MGT_TRANSP_HEAD_SEQ                last_number=34468  increment=1
MGT_TRANSP_RATE_SEQ                last_number=876  increment=1
