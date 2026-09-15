CREATE OR REPLACE PACKAGE MGTAPPS.pkg_CostingProductSeq AS
    procedure pLoad_Data(
                pUserId varchar2
                ,pCYL_TYPE varchar2:=null
                ,pCYL_LEFT_NO number:=null
                );

    PROCEDURE pupd_seq(pCYL_LEFT_NO number:=null);

    FUNCTION get_data(pCYL_LEFT_NO number:=null) RETURN COSTING_PRODUCT_SEQ_TAB PIPELINED;
    
    FUNCTION get_LeftNo_Product(pCYL_LEFT_NO number:=null) RETURN varchar2;
END; 
/
