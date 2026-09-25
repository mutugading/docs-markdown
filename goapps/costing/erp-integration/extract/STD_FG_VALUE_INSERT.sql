PROCEDURE        STD_FG_VALUE_INSERT(P_ITEM_CODE VARCHAR2,P_GRADE_CODE VARCHAR2,P_SHADE_CODE VARCHAR2) AS

    M_FG_ITEM_CODE OT_STD_COST_PRODUCTS_MGT.FG_ITEM_CODE%TYPE;
    M_FG_ITEM_NAME OT_STD_COST_PRODUCTS_MGT.FG_ITEM_NAME%TYPE;
    M_FG_CONVER_COST OT_STD_COST_PRODUCTS_MGT.FG_CONVER_COST%TYPE;
    M_FG_SHADE_NAME OT_STD_COST_PRODUCTS_MGT.FG_SHADE_NAME%TYPE;
    M_FG_CHP_ITEM_CODE OT_STD_COST_PRODUCTS_MGT.FG_CHP_ITEM_CODE%TYPE;
    M_FG_CHP_CON_KG OT_STD_COST_PRODUCTS_MGT.FG_CHP_CON_KG%TYPE;
    M_FG_CHP_COST OT_STD_COST_PRODUCTS_MGT.FG_CHP_COST%TYPE;
    M_FG_COST_PER_KG OT_STD_COST_PRODUCTS_MGT.FG_COST_PER_KG%TYPE;
    M_FG_ITEM_TYPE OT_STD_COST_PRODUCTS_MGT.FG_ITEM_TYPE%TYPE;
    M_ITEM_GRADE_CODE OT_STD_COST_PRODUCTS_MGT.FG_ITEM_GRADE%TYPE;
    M_ITEM_SHADE OT_STD_COST_PRODUCTS_MGT.FG_ITEM_SHADE%TYPE;
    M_FG_CONVER_COST1 OT_STD_COST_PRODUCTS_MGT.FG_CONVER_COST1%TYPE;
    M_FG_CONVER_COST2 OT_STD_COST_PRODUCTS_MGT.FG_CONVER_COST2%TYPE;
    M_FG_CONVER_COST4 OT_STD_COST_PRODUCTS_MGT.FG_CONVER_COST4%TYPE;
    M_FG_CONVER_COST5 OT_STD_COST_PRODUCTS_MGT.FG_CONVER_COST5%TYPE;
    M_FG_TYPE OT_STD_COST_PRODUCTS_MGT.FG_TYPE%TYPE;

    M_ITEM_VALUE  NUMBER;
    M_ITEM_STD_VAL NUMBER:=0;
    M_CONVER_COST NUMBER;
    M_CONVER_COST1 NUMBER;
    M_CONVER_COST2 NUMBER;
    M_CONVER_COST4 NUMBER;
    M_CONVER_COST5 NUMBER;
    P_CONVER_COST NUMBER;
    P_CONVER_COST1 NUMBER;
    P_CONVER_COST2 NUMBER;
    P_CONVER_COST4 NUMBER;
    P_CONVER_COST5 NUMBER;
    P_FLAG NUMBER;
    P_FLAG_FOUND NUMBER;
    P_FLAG_FIXED_COST NUMBER := 0;
    FG_SHADE_NAME VARCHAR2(200);
    FG_GRADE_GROUP VARCHAR2(200);
    FG_PROD_TYPE VARCHAR2(200);
    FG_ITEM_NAME VARCHAR2(200);
    M_FG_PRD_PER_DAY NUMBER;
    M_VALUE_LOSS NUMBER;
    M_BASIS VARCHAR2(200);
    M_SELLING_PRICE NUMBER;
    M_AX_COST NUMBER;
    M_PROD_VL NUMBER;

CURSOR C1 IS
SELECT FG_ITEM_CODE,
       FG_ITEM_NAME,
       FG_CONVER_COST,
       FG_SHADE_NAME,
       FG_CHP_ITEM_CODE,
       FG_CHP_CON_KG,
       FG_CHP_COST,
       FG_COST_PER_KG,
       FG_ITEM_TYPE,
       NVL(FG_MS_BATCH_ITEM,0),
       FG_CONVER_COST1,
       FG_CONVER_COST2,
       FG_CONVER_COST4,
       FG_CONVER_COST5,
       FG_TYPE
FROM OT_STD_COST_PRODUCTS_MGT
WHERE  FG_ITEM_GRADE    = 'AX'
AND FG_ITEM_CODE        = P_ITEM_CODE
AND FG_ITEM_SHADE       = P_SHADE_CODE;

BEGIN

    SELECT GRADE_BL_SHORT_NAME
    INTO FG_GRADE_GROUP
    FROM om_grade_code_1
    WHERE GRADE_CODE = P_GRADE_CODE;

    SELECT GRADE_NAME
    INTO  FG_SHADE_NAME
    FROM OM_GRADE_CODE_2
    WHERE GRADE_CODE=P_SHADE_CODE;

    SELECT ITEM_NAME
    INTO   FG_ITEM_NAME
    FROM OM_ITEM
    WHERE ITEM_CODE = P_ITEM_CODE;

    IF SUBSTR(P_ITEM_CODE,1,3) = 'POY' THEN
        FG_PROD_TYPE := 'POY';
    ELSIF SUBSTR(P_ITEM_CODE,1,3) = 'ITY' THEN
        FG_PROD_TYPE := 'ITY';
    ELSE
        FG_PROD_TYPE := 'PTY';
    END IF;

    M_FG_ITEM_CODE      := P_ITEM_CODE;
    M_ITEM_GRADE_CODE   := P_GRADE_CODE;
    M_ITEM_SHADE        := P_SHADE_CODE;

    IF C1%ISOPEN THEN
        CLOSE C1;
    END IF;

    OPEN C1;
        FETCH C1 INTO   M_FG_ITEM_CODE,
                        M_FG_ITEM_NAME,
                        M_FG_CONVER_COST,
                        M_FG_SHADE_NAME,
                        M_FG_CHP_ITEM_CODE,
                        M_FG_CHP_CON_KG,
                        M_FG_CHP_COST,
                        M_FG_COST_PER_KG,
                        M_FG_ITEM_TYPE,
                        M_FG_PRD_PER_DAY,
                        M_FG_CONVER_COST1,
                        M_FG_CONVER_COST2,
                        M_FG_CONVER_COST4,
                        M_FG_CONVER_COST5,
                        M_FG_TYPE;
        IF C1%FOUND THEN
            P_FLAG:=1;
        ELSE
            P_FLAG:=0;
        END IF;
    CLOSE C1;

    BEGIN
        SELECT MICVL_BASIS, MICVL_VAL_LOSS
        INTO M_BASIS, M_VALUE_LOSS
        FROM MGT_ITEM_COST_VAL_LOSS
        WHERE MICVL_TYPE = M_FG_TYPE
        AND MICVL_PROD_TYPE = FG_PROD_TYPE
        AND MICVL_GRADE_GROUP = FG_GRADE_GROUP;
    EXCEPTION WHEN NO_DATA_FOUND THEN
        M_BASIS := '';
        M_VALUE_LOSS := 0;
    END;


    BEGIN
        SELECT TO_NUMBER(VSSV_FIELD_01) SELL_PRICE
        INTO M_SELLING_PRICE
        FROM IM_VS_STATIC_VALUE
        WHERE VSSV_VS_CODE = 'ITEMSELLPRIC'
        AND VSSV_CODE = M_BASIS;
    EXCEPTION WHEN NO_DATA_FOUND THEN
        M_SELLING_PRICE := 0;
    END;


    IF M_BASIS = 'COST' THEN

        M_CONVER_COST := ROUND(NVL(M_FG_CONVER_COST - (M_VALUE_LOSS),0),5);
        M_CONVER_COST1 := ROUND(NVL(M_FG_CONVER_COST1 - (M_VALUE_LOSS),0),5);
        M_CONVER_COST2 := ROUND(NVL(M_FG_CONVER_COST2 - (M_VALUE_LOSS),0),5);
        M_CONVER_COST4 := ROUND(NVL(M_FG_CONVER_COST4 - (M_VALUE_LOSS),0),5);
        M_CONVER_COST5 := ROUND(NVL(M_FG_CONVER_COST5 - (M_VALUE_LOSS),0),5);

        M_ITEM_STD_VAL := ROUND((M_FG_CHP_COST * M_FG_CHP_CON_KG) + (M_CONVER_COST),5);
    ELSE
        M_ITEM_STD_VAL := ROUND(M_SELLING_PRICE - M_VALUE_LOSS,5);

        M_CONVER_COST := 0;
        M_CONVER_COST1 := 0;
        M_CONVER_COST2 := 0;
        M_CONVER_COST4 := 0;
        M_CONVER_COST5 := 0;

    END IF;

    M_AX_COST := ROUND((M_FG_CHP_COST * M_FG_CHP_CON_KG) + (M_FG_CONVER_COST),5);

    M_PROD_VL := ROUND(NVL(M_ITEM_STD_VAL,0) - NVL(M_AX_COST,0),5);

    IF P_FLAG <> 0 THEN
        INSERT INTO OT_STD_COST_PRODUCTS_MGT(FG_ITEM_CODE,
                                             FG_ITEM_NAME,
                                             FG_CONVER_COST,
                                             FG_ITEM_GRADE,
                                             FG_ITEM_SHADE,
                                             FG_SHADE_NAME,
                                             FG_CHP_ITEM_CODE,
                                             FG_CHP_CON_KG,
                                             FG_CHP_COST,
                                             FG_COST_PER_KG,
                                             FG_MS_BATCH_ITEM,
                                             FG_ITEM_CR_DT,
                                             FG_ITEM_CR_UID,
                                             FG_ITEM_TYPE,
                                             FG_CONVER_COST1,
                                             FG_CONVER_COST2,
                                             FG_CONVER_COST4,
                                             FG_CONVER_COST5,
                                             FG_TYPE,
                                             FG_BASIS,
                                             FG_SELLING_PRICE,
                                             FG_AX_COST,
                                             FG_VALUE_LOSS,
                                             FG_AX_CONV_COST,
                                             FG_PROD_VALUE_LOSS
                                            )
                                VALUES      (M_FG_ITEM_CODE,
                                             M_FG_ITEM_NAME,
                                             M_CONVER_COST,
                                             M_ITEM_GRADE_CODE,
                                             P_SHADE_CODE,
                                             M_FG_SHADE_NAME,
                                             M_FG_CHP_ITEM_CODE,
                                             M_FG_CHP_CON_KG,
                                             M_FG_CHP_COST,
                                             M_ITEM_STD_VAL,
                                             M_FG_PRD_PER_DAY,
                                             SYSDATE,
                                             'SYSADMIN',
                                             M_FG_ITEM_TYPE,
                                             M_CONVER_COST1,
                                             M_CONVER_COST2,
                                             M_CONVER_COST4,
                                             M_CONVER_COST5,
                                             M_FG_TYPE,
                                             M_BASIS,
                                             M_SELLING_PRICE,
                                             M_AX_COST,
                                             M_VALUE_LOSS,
                                             ROUND(M_FG_CONVER_COST,5),
                                             M_PROD_VL);
    END IF;

    COMMIT;
END ;
