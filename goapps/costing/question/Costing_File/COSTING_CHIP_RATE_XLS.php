<?php
$SqlCk = "
select count(-1) GET_DATA from (
SELECT distinct cyc_data_value
  FROM (SELECT CLLP_CYL_SYS_ID,
               CLLP_CYL_SYS_ID_REFF,
               CLLP_LEFT_NO,
               b.cyl_type
          FROM (  SELECT *
                    FROM cst_lvl_left_prod a, cst_YARN_left b
                   WHERE     a.CLLP_CYL_SYS_ID_REFF = b.cyl_sys_id
                         AND cyl_sys_id = '".$CK_CYL_SYS_ID."'
                ORDER BY cllp_lvl_prod) a,
               cst_yarn_left b
         WHERE A.CLLP_CYL_SYS_ID = b.cyl_sys_id AND b.cyl_type = 'POY') a
         ,cst_yarn_calculation b
where CLLP_CYL_SYS_ID = CYC_CYL_SYS_ID and cyc_top_no = 20 )
";
$totRm_Poy =  getData($conn,$SqlCk);
//if ($USER_NAME==="1949"){echo "totRm_Poy $totRm_Poy";}
if ($totRm_Poy>1){
  $SqlData = "
  select sum(rm_val.cyc_data_value)/count(-1) GET_DATA
  from
  (
      SELECT CLLP_LEFT_NO,cyc_data_value
        FROM (SELECT CLLP_CYL_SYS_ID,
                     CLLP_CYL_SYS_ID_REFF,
                     CLLP_LEFT_NO,
                     b.cyl_type
                FROM (  SELECT *
                          FROM cst_lvl_left_prod a, cst_YARN_left b
                         WHERE     a.CLLP_CYL_SYS_ID_REFF = b.cyl_sys_id
                               AND cyl_sys_id = '".$CK_CYL_SYS_ID."'
                      ORDER BY cllp_lvl_prod) a,
                     cst_yarn_left b
               WHERE A.CLLP_CYL_SYS_ID = b.cyl_sys_id AND b.cyl_type = 'POY') a,
             cst_yarn_calculation b
       WHERE CLLP_CYL_SYS_ID = CYC_CYL_SYS_ID AND cyc_top_no = 20
       ) rm,(
      SELECT CLLP_LEFT_NO,cyc_data_value
        FROM (SELECT CLLP_CYL_SYS_ID,
                     CLLP_CYL_SYS_ID_REFF,
                     CLLP_LEFT_NO,
                     b.cyl_type
                FROM (  SELECT *
                          FROM cst_lvl_left_prod a, cst_YARN_left b
                         WHERE     a.CLLP_CYL_SYS_ID_REFF = b.cyl_sys_id
                               AND cyl_sys_id = '".$CK_CYL_SYS_ID."'
                      ORDER BY cllp_lvl_prod) a,
                     cst_yarn_left b
               WHERE A.CLLP_CYL_SYS_ID = b.cyl_sys_id AND b.cyl_type = 'POY') a,
             cst_yarn_calculation b
       WHERE CLLP_CYL_SYS_ID = CYC_CYL_SYS_ID AND cyc_top_no = 55
   ) rm_val
   where rm.CLLP_LEFT_NO=rm_val.CLLP_LEFT_NO
   ";
   $valDt24 =  getData($conn,$SqlData);
} else {

  $isMelangePrs="";
  if ($CK_CYL_TYPE==="MELANGE"){ $isMelangePrs="Y";	}
  if ($CK_CYL_TYPE==="MEEREBAH" && substr($CK_CYL_SHADE_NAME,0,8)==="MELANGE"){ $isMelangePrs="Y";	}

  if ($isMelangePrs==="Y"){
    $TotRecDt = 0; $DtMb = "";
    $SqlMbDt =
      " SELECT distinct YC.CYC_DATA_VALUE
        FROM (SELECT rm.*
                FROM mgtapps.cst_yarn_left yl,
                     mgtapps.cst_yarn_calculation yc,
                     mgtapps.cst_yarn_rm_hdr rh,
                     mgtapps.cst_yarn_rm_multi rm
               WHERE     yl.CYL_SYS_ID = '".$CK_CYL_SYS_ID."'
                     AND YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
                     AND YC.CYC_TOP_NO = 55
                     AND YC.CYC_SYS_ID = RH.CYRH_CYC_SYS_ID
                     AND RH.CYRH_SYS_ID = RM.CYRM_CYRH_SYS_ID) dt
             ,mgtapps.cst_yarn_left yl
             ,mgtapps.cst_yarn_calculation yc
      where CYL_SYS_ID = CYRM_CYL_SYS_ID
      and YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
      and YC.CYC_TOP_NO = 55 ";

      $rsMbDt = oci_parse($conn,$SqlMbDt);
      oci_execute ($rsMbDt);
      while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
        $DtMb = "";
        if (!empty($rowMbDt['CYC_DATA_VALUE'])){
            $TotRecDt++;
            $DtMb = $rowMbDt['CYC_DATA_VALUE'];
        }
        $valDt24 = $DtMb;
      }

      if ($valDt24===""){
        $SqlMbDt =
          "
          SELECT sum(CYC_DATA_VALUE)/count(-1) GET_DATA
          FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_CALCULATION b
          WHERE     CLLP_CYL_SYS_ID_REFF = '".$CK_CYL_SYS_ID."'
          AND a.CLLP_CYL_SYS_ID = b.CYC_CYL_SYS_ID
          AND CLLP_TYPE = 'POY'
          and cyc_top_no = 55
          ";

        //echo "second check<br> $SqlMbDt <br>";
        $valDt24 =  getData($conn,$SqlMbDt);
      }
    } else if ($CK_CYL_TYPE==="PTY BO"){
      //if ($USER_NAME==="1949"){ echo "PTY BO"; }
      $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                  where A.CYC_CYL_SYS_ID = '".$CK_CYL_SYS_ID."'
                  and A.CYC_TOP_NO = 55";
      $valDt24 =  getData($conn,$SqlData);
    } else if ($isUseRmBo==="Y"){
      //if ($USER_NAME==="1949"){ echo "isUseRmBo"; }
      $SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial_RmBO('".$CK_CYL_SYS_ID."') GET_DATA from dual ";
			$deepMaterial =  getData($conn,$SqlData);

      $sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
                          '".$CK_CYL_SYS_ID."'
                          ,$deepMaterial
                          ,2
                          ) GET_DATA
                   from dual";
      $CylSysId_UseBo =getData($conn,$sqlGetDt);

      $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                  where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
                  and A.CYC_TOP_NO = 55";
      $valDt24 =  getData($conn,$SqlData);
    }else{
      //if ($USER_NAME==="1949"){ echo "Cek Proses<br>"; }
      //cek RM PTY BO
      $sqlDtRm= "select MGTAPPS.pkg_yarn_calculation.fGetRM_PTY_BO('".$CK_CYL_SYS_ID."') GET_DATA from dual";
      $RM_PTY_BO = getData($conn,$sqlDtRm);

      if ($RM_PTY_BO !=="" ){
        //if ($USER_NAME==="1949"){ echo "not RM_PTY_BO"; }
        $SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
                    where A.CYC_CYL_SYS_ID = '$RM_PTY_BO'
                    and A.CYC_TOP_NO = 55";

        $valDt24 =  getData($conn,$SqlData);

      } else {
        //if ($USER_NAME==="1949"){ echo "Cek Proses 1 <br>"; }
        if ($isDt_RM55==="Y") {
          //if ($USER_NAME==="1949"){ echo "isDt_RM55"; }
          $SqlData = "select cyc_data_value GET_DATA
                      from cst_yarn_calculation a
                      where cyc_cyl_sys_id in (
                      SELECT CLLP_CYL_SYS_ID
                        FROM cst_lvl_left_prod
                       WHERE     CLLP_CYL_SYS_ID_REFF = '".$CK_CYL_SYS_ID."'
                             AND cllp_lvl_prod = (SELECT MAX (cllp_lvl_prod)
                                                    FROM cst_lvl_left_prod
                                                   WHERE CLLP_CYL_SYS_ID_REFF = '".$CK_CYL_SYS_ID."')
                                                   )
                      and cyc_top_no = 55	";
          //if ($USER_NAME==="1949"){ echo "$SqlData"; }
          $valDt24 =  getData($conn,$SqlData);

        } else {

          if (!empty($CK_FGETCHIPRATE)){
            $valDt24 = $CK_FGETCHIPRATE;
          } else {
            $sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$CK_CYL_SYS_ID."') GET_DATA from  dual";
            //if ($USER_NAME==="1949"){ echo "masuk sini <br>is Dt 55 $isDt_RM55<br>$sqlDtRm"; }
            $DtRm =  getData($conn,$sqlDtRm);
            $valDt24 = $DtRm;
          }
        }

      }

    }

    if ($FORM_NAME === "CST_2P1_0003"){
      $SqlGet =  "select MGTAPPS.pkg_yarn_valuation.fgetchiprate_rm('".$CK_CYL_SYS_ID."') GET_DATA from  dual ";
      $valDt24 = getData($conn,$SqlGet);
    }

    if ($CK_CYL_TYPE==="ACY"){
      //if ($USER_NAME==="1949"){ echo "ACY"; }
      $sqlDtRm= "select cyc_data_value GET_DATA
                from cst_yarn_calculation a
                     ,cst_yarn_rm_hdr b
                where cyc_cyl_sys_id = ".$CK_CYL_SYS_ID."
                and cyc_top_no = 55
                and A.CYC_FORMULA_TYPE = 'Raw_Material'
                and B.CYRH_TYPE = 'Multi Yarn'
                and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                ";
      //if ($USER_NAME==="1949"){ echo "masuk sini <br>ACY $isDt_RM55<br>$sqlDtRm"; }
      $DtRm =  getData($conn,$sqlDtRm);
      $valDt24 = $DtRm;
    } else {
      //if ($USER_NAME==="1949"){ echo "selain ACY"; }
      $sqlDtRm= "	select pkg_first_rm_from_store.fCkStatus('".$CK_CYL_SYS_ID."') GET_DATA
                  from dual";
      $ckDtStr =  getData($conn,$sqlDtRm);

      if ($ckDtStr==="Y"){
        $sqlDtRm= "select pkg_first_rm_from_store.fGetRMVal('".$CK_CYL_SYS_ID."') GET_DATA
                   from dual";
        $DtRm =  getData($conn,$sqlDtRm);
        $valDt24 = $DtRm;
      }
    }

}
?>
