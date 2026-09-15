<?php
  function fGetSqlDt(){

    return "SELECT  CMY_NAME
                        ,CYCC_TOP_13_DATA_VALUE DENIER
                        ,CYCC_TOP_16_DATA_VALUE FILAMENT
                        ,CYCC_TOP_18_DATA_VALUE INTERMINGLING
                        ,CYCC_TOP_49_DATA_VALUE HEATSET
                        ,CYCC_TOP_17_DATA_VALUE CROSS_SECTION
                        ,CMY_LUSTURE
                        ,CYL_SYS_ID
                        ,CYL_SHADE_CODE
                        ,CYL_SHADE_NAME
                        ,CYL_LEFT_NO
                        ,CYL_TYPE,CYL_PRODUCT_QUALITY
                 FROM mgtapps.cst_mst_yarn y,
                      mgtapps.cst_yarn_left l,
                      mgtapps.cst_yarn_calculation_cur cc
                WHERE cycc_cyl_sys_id = cyl_sys_id
                  AND cyl_cmy_sys_id = cmy_sys_id
                  AND cyl_prs_type = cycc_prs_type
                  AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_IS_VALID_PRD = 'Y' ";

  }
?>
