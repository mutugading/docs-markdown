PROCEDURE O_VAL_TRANSPORTER(P_TRANSPORTER_CODE  IN     VARCHAR2,
								              P_INDIC             IN     VARCHAR2,
								              P_DESC              IN OUT VARCHAR2,
								              P_ERR_WAR_FLAG      IN   VARCHAR2 ) IS

P_LANG_CODE    OW_FS.LANG_CODE%TYPE;
P_FULL_NAME    OW_FS.NAME%TYPE;
P_SHORT_NAME   OW_FS.SHORT_NAME%TYPE;
P_SER_NO       OW_FS.SER_NO%TYPE;
P_FRZ_FLAG_NUM OW_FS.FLAG_NUM%TYPE;

CURSOR  C1 IS
        SELECT DECODE(P_LANG_CODE,'ENG',TRANS_NAME, TRANS_BL_NAME) ,
               DECODE(P_LANG_CODE,'ENG',TRANS_SHORT_NAME,TRANS_BL_SHORT_NAME), TRANS_FRZ_FLAG_NUM
        FROM   OM_TRANSPORTER
        WHERE  TRANS_CODE = P_TRANSPORTER_CODE;
BEGIN

       --P_SER_NO := 29;
       ORNDBPKG_GLOBAL.O_DGET_LANG_CODE(P_LANG_CODE);
       IF C1%ISOPEN THEN
          CLOSE C1;
       END IF ;
       OPEN C1;
       FETCH C1 INTO P_FULL_NAME, P_SHORT_NAME, P_FRZ_FLAG_NUM;
       IF C1%NOTFOUND THEN
          P_DESC := '';
          IF P_ERR_WAR_FLAG = 'E' THEN
             --RAISE_APPLICATION_ERROR(-20001,'Invalid Transporter Code');
			 RAISE_APPLICATION('OP', 220426 ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'' );
          END IF ;
       END  IF ;
       IF P_FRZ_FLAG_NUM = 1 THEN
          IF P_ERR_WAR_FLAG = 'E' THEN
             --RAISE_APPLICATION_ERROR(-20002,'Transporter Code is freezed');
			 RAISE_APPLICATION('OP', 220427 ,'' ,'' ,'' ,'' ,'' ,'' ,'' ,'' );
          END IF ;
       END IF ;
       IF P_INDIC = 'N' THEN
          P_DESC := P_FULL_NAME;
       ELSE
          P_DESC := P_SHORT_NAME;
       END IF ;
       CLOSE C1;
END;
