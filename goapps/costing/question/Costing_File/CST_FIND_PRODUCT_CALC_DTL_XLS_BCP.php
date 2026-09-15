<?php
//val data 11
  $valDt11 = "";
  if (!empty($rowRsView['FGET_V4'])){
    $valDt11 = $rowRsView['FGET_V4'];
  }
  $valDt11 = setNumber($conn,"Number",$valDt11,4);
  //echo "cek $valDt11<br>";

//val data 27
  $valDt27 = 0;
  $TotRecDt = 0; $DtMb = 0; $DtRm = 0; $isDt_RM55="";

  //cek final product is SUPERBA
  $SqlData =
  "
  SELECT cyc_data_value GET_DATA
    FROM  cst_yarn_left a
          ,cst_yarn_calculation b
   WHERE cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."' AND CYL_TYPE = 'SUPERBA'
   and A.CYL_SYS_ID=B.CYC_CYL_SYS_ID
   and cyc_top_no=73
   ";
  $DtRm = getData($conn,$SqlData);
  //cek final product is SUPERBA

  if ($DtRm===""){



  //cek is Dt RM 55
  $SqlData = "SELECT 'Y' GET_DATA FROM cst_lvl_left_prod
              WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND rownum=1 ";
  $isDt_RM55 		=  getData($conn,$SqlData);
  $TotPoy_Rm55 	= 0;
  $isMulti_RM 	= "";
  if ($isDt_RM55==="Y"){
      $SqlData =  "
                  SELECT count(-1) GET_DATA FROM cst_lvl_left_prod
                  WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND CLLP_TYPE = 'POY'
                  ";
      $TotPoy_Rm55 =  getData($conn,$SqlData);

      if ($TotPoy_Rm55===1){
        if (!empty($rowRsView['FGETMBCOST'])){
          $DtRm =	$rowRsView['FGETMBCOST'];
          //if ($USER_NAME==="1949"){ echo "cek1 $DtRm ";}
        } else {
          $sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
          //if ($USER_NAME==="1949"){echo $sqlDtRm;}
          $DtRm =  getData($conn,$sqlDtRm);
        }
      } else {

        $SqlData =
            "
            SELECT 'Y' GET_DATA
              FROM mgtapps.cst_yarn_left yl,
                   mgtapps.cst_yarn_calculation yc,
                   mgtapps.cst_yarn_rm_hdr rh,
                   mgtapps.cst_yarn_rm_multi rm
             WHERE     yl.CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
                   AND YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
                   AND YC.CYC_TOP_NO = 55
                   AND YC.CYC_SYS_ID = RH.CYRH_CYC_SYS_ID
                   AND RH.CYRH_SYS_ID = RM.CYRM_CYRH_SYS_ID
                   AND CYRM_YARN_TYPE = 'POY'
                   AND ROWNUM = 1
                   ";

          $isMulti_RM =  getData($conn,$SqlData);
          if ($isMulti_RM==="Y"){

            $SqlMbDt =
              " SELECT sum(YC.CYC_DATA_VALUE*CYRM_CONSUME) CYC_DATA_VALUE
                FROM (SELECT rm.*
                        FROM mgtapps.cst_yarn_left yl,
                             mgtapps.cst_yarn_calculation yc,
                             mgtapps.cst_yarn_rm_hdr rh,
                             mgtapps.cst_yarn_rm_multi rm
                       WHERE     yl.CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
                             AND YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
                             AND YC.CYC_TOP_NO = 55
                             AND YC.CYC_SYS_ID = RH.CYRH_CYC_SYS_ID
                             AND RH.CYRH_SYS_ID = RM.CYRM_CYRH_SYS_ID) dt
                     ,mgtapps.cst_yarn_left yl
                     ,mgtapps.cst_yarn_calculation yc
              where CYL_SYS_ID = CYRM_CYL_SYS_ID
              and YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
              and YC.CYC_TOP_NO = 73 ";
            //if ($USER_NAME==="1949") {echo "$SqlMbDt <br>";}
            $rsMbDt = oci_parse($conn,$SqlMbDt);
            oci_execute ($rsMbDt);
            $DtMb = "";
            while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
              $DtRm = $rowMbDt['CYC_DATA_VALUE'];
              //echo "DtMb $DtMb";
            }

          } else {

            $sqlGet = "
						select distinct cyc_cyl_sys_id GET_DATA
						from    cst_lvl_left_prod a
										,mgtapps.cst_yarn_calculation b
										,mgtapps.cst_yarn_rm_hdr c
										,mgtapps.cst_yarn_rm_multi d
						where CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
						and A.CLLP_CYL_SYS_ID = B.CYC_CYL_SYS_ID
						and B.CYC_TOP_NO = 55
						and cyc_sys_id = c.cyrh_cyc_sys_id
						and c.cyrh_type = 'Multi Yarn'
						and C.CYRH_SYS_ID = D.CYRM_CYRH_SYS_ID
						";
						$ckGet =  getData($conn,$sqlGet);
						$isMultiRM_2=""; $DtRm_CYL_SYS_ID="";
						if ($ckGet!==""){
							$isMultiRM_2="Y"; $DtRm_CYL_SYS_ID = $ckGet;
							//echo "sqlGet $sqlGet<br>isMultiRM_2 $isMultiRM_2<br>DtRm_CYL_SYS_ID $DtRm_CYL_SYS_ID<br>";
						}

            if ($isMultiRM_2==="Y"){
							$SqlData = "
							select sum(mb_calc)/100 GET_DATA from (
							select  c.CYRM_CONSUME,h.CYC_DATA_VALUE,i.CYC_DATA_VALUE
							        ,h.CYC_DATA_VALUE*i.CYC_DATA_VALUE mb_cost
							        ,c.CYRM_CONSUME*(h.CYC_DATA_VALUE*i.CYC_DATA_VALUE) mb_calc
							from cst_yarn_calculation a
							     ,cst_yarn_rm_hdr b
							     ,cst_yarn_rm_multi c
							     ,cst_yarn_left d
							     ,cst_yarn_calculation e
							     ,cst_yarn_rm_hdr f
							     ,cst_yarn_rm_captive g
							     ,cst_yarn_calculation h
							     ,cst_yarn_calculation i
							where a.cyc_cyl_sys_id = '$DtRm_CYL_SYS_ID'
							and A.CYC_TOP_NO = 55
							and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
							and b.cyrh_sys_id=C.CYRM_CYRH_SYS_ID
							and C.CYRM_CYL_SYS_ID=D.CYL_SYS_ID
							and D.CYL_SYS_ID=e.cyc_CYL_SYS_ID
							and e.CYC_TOP_NO = 55
							and E.CYC_SYS_ID=F.CYRH_CYC_SYS_ID
							and F.CYRH_SYS_ID=G.CYRC_CYRH_SYS_ID
							and G.CYRC_CYL_SYS_ID=h.cyc_cyl_sys_id
							and G.CYRC_CYL_SYS_ID=i.cyc_cyl_sys_id
							and H.CYC_TOP_NO = 71
							and i.CYC_TOP_NO = 72
							)";
							$DtRm =  getData($conn,$SqlData);

              if ($DtRm===""){
									$SqlData =
									"SELECT SUM (TOT) GET_DATA
  FROM (
SELECT POY.CLLP_CYL_SYS_ID,
       POY.CYC_DATA_VALUE MB_RATE,
       POY_DOZ.CYC_DATA_VALUE MOB_DOZ,
       POY.CYC_DATA_VALUE * POY_DOZ.CYC_DATA_VALUE DOZ_RATE,
       CYRM_CONSUME,
       ((POY.CYC_DATA_VALUE * POY_DOZ.CYC_DATA_VALUE) * CYRM_CONSUME/100) TOT
  FROM (SELECT *
          FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_CALCULATION b
         WHERE     CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
               AND a.CLLP_TYPE = 'POY'
               AND a.CLLP_CYL_SYS_ID = b.CYC_CYL_SYS_ID
               AND b.cyc_top_no = 72) POY,
         (SELECT *
          FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_CALCULATION b
         WHERE     CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
               AND a.CLLP_TYPE = 'POY'
               AND a.CLLP_CYL_SYS_ID = b.CYC_CYL_SYS_ID
               AND b.cyc_top_no = 71) POY_DOZ,
       (SELECT g.CYRC_CYL_SYS_ID, d.*
          FROM mgtapps.CST_LVL_LEFT_PROD a,
               mgtapps.CST_YARN_CALCULATION b,
               mgtapps.cst_yarn_rm_hdr c,
               mgtapps.cst_yarn_rm_multi d,
               mgtapps.CST_YARN_CALCULATION e,
               mgtapps.cst_yarn_rm_hdr f,
               mgtapps.cst_yarn_rm_captive g
         WHERE     CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
               AND a.CLLP_CYL_SYS_ID = b.CYC_CYL_SYS_ID
               AND b.cyc_top_no = 55
               AND b.cyc_sys_id = c.CYRH_CYC_SYS_ID
               AND c.CYRH_SYS_ID = d.CYRM_CYRH_SYS_ID
               AND d.CYRM_CYL_SYS_ID = e.CYC_CYL_SYS_ID
               AND e.cyc_top_no = 55
               AND e.cyc_sys_id = f.CYRH_CYC_SYS_ID
               AND f.CYRH_SYS_ID = g.CYRC_CYRH_SYS_ID
               AND g.CYRC_YARN_TYPE = 'POY') dt
 WHERE POY.CLLP_CYL_SYS_ID = dt.CYRC_CYL_SYS_ID
 and  POY_DOZ.CLLP_CYL_SYS_ID = dt.CYRC_CYL_SYS_ID
         )";


									 $DtRm =  getData($conn,$SqlData);
							}

              if ($DtRm===""){
                //get data POY BO
                $sqlGet = "
                                  select sum(TOT) GET_DATA from (
                                  SELECT cyrm_cyc_sys_id,b.cyc_cyl_sys_id,c.cyc_data_value mb_rate,CYRM_CONSUME,d.cyl_left_no,d.cyl_sys_id
                                        ,e.cyc_data_value mb_doz
                                        ,((c.cyc_data_value * e.cyc_data_value) * CYRM_CONSUME/100) TOT
                                    FROM (SELECT c.cyrm_cyc_sys_id,CYRM_CONSUME
                                            FROM  cst_yarn_calculation a
                                                  ,mgtapps.cst_yarn_rm_hdr b
                                                  ,mgtapps.cst_yarn_rm_multi c
                                           WHERE     cyc_cyl_sys_id = '$DtRm_CYL_SYS_ID'
                                                 AND cyc_top_no = 55
                                                 AND CYC_FORMULA_TYPE = 'Raw_Material'
                                                 AND cyrh_type = 'Multi Yarn'
                                                 AND cyrm_type_data = 'Yarn-Cap'
                                                 AND cyc_sys_id = CYRH_CYC_SYS_ID
                                                 AND CYRH_SYS_ID = CYRM_CYRH_SYS_ID) a
                                                 ,cst_yarn_calculation b
                                                 ,cst_yarn_calculation c
                                                 ,cst_yarn_left d
                                                 ,cst_yarn_calculation e
                                  where a.cyrm_cyc_sys_id=b.cyc_sys_id
                                  and b.cyc_cyl_sys_id=c.cyc_cyl_sys_id
                                  and c.cyc_top_no=72
                                  and b.cyc_cyl_sys_id=d.cyl_sys_id
                                  and d.cyl_sys_id=e.cyc_cyl_sys_id
                                  and e.cyc_top_no=71
                                  )
                          ";
                //if ($USER_NAME==="1949") {
                    //echo "isMultiRM_2 3 $isMultiRM_2 : DtRm $DtRm : CYL_SYS_ID $DtRm_CYL_SYS_ID<br>";
                    //echo "$sqlGet<br>";
                //}
                $DtRm =  getData($conn,$sqlGet);
              }

              if ($DtRm===""){
                //get data POY BO
                $sqlGet = "
              SELECT sum(cyrm_consume*(dv_71*dv_72))/100 GET_DATA
              FROM (SELECT 1 NO, c.cyrm_consume
              FROM cst_yarn_calculation a,
              cst_yarn_rm_hdr b,
              cst_yarn_rm_multi c,
              cst_yarn_left d,
              cst_yarn_calculation e
              WHERE a.cyc_cyl_sys_id = '$DtRm_CYL_SYS_ID'
              AND a.cyc_top_no = 55
              AND a.cyc_sys_id = b.cyrh_cyc_sys_id
              AND b.cyrh_sys_id = c.cyrm_cyrh_sys_id
              AND c.cyrm_cyl_sys_id = d.cyl_sys_id
              AND d.cyl_sys_id = e.cyc_cyl_sys_id
              AND e.cyc_top_no = 55) cnsmp,
              (SELECT 1 NO, cyc_data_value dv_55
              FROM cst_lvl_left_prod a, cst_yarn_calculation b
              WHERE a.cllp_cyl_sys_id_reff = '".$rowRsView['CYL_SYS_ID']."'
              AND a.cllp_type = 'POY'
              AND a.cllp_cyl_sys_id = b.cyc_cyl_sys_id
              AND cyc_top_no = 55) dv_55,
              (SELECT 1 NO, cyc_data_value dv_71
              FROM cst_lvl_left_prod a, cst_yarn_calculation b
              WHERE a.cllp_cyl_sys_id_reff = '".$rowRsView['CYL_SYS_ID']."'
              AND a.cllp_type = 'POY'
              AND a.cllp_cyl_sys_id = b.cyc_cyl_sys_id
              AND cyc_top_no = 71) dv_71,
              (SELECT 1 NO, cyc_data_value dv_72
              FROM cst_lvl_left_prod a, cst_yarn_calculation b
              WHERE a.cllp_cyl_sys_id_reff = '".$rowRsView['CYL_SYS_ID']."'
              AND a.cllp_type = 'POY'
              AND a.cllp_cyl_sys_id = b.cyc_cyl_sys_id
              AND cyc_top_no = 72) dv_72
              WHERE cnsmp.NO = dv_71.NO AND cnsmp.NO = dv_72.NO";

              $DtRm =  getData($conn,$sqlGet);

              }


						} else {
              $SqlData = "
              select sum(to_number(CYCC_TOP_73_DATA_VALUE)) GET_DATA
              from mgtapps.cst_yarn_calculation_cur
              where CYCC_CYL_SYS_ID in (
                SELECT CLLP_CYL_SYS_ID
                FROM cst_lvl_left_prod
                WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
                AND CLLP_TYPE = 'POY'
                )
              ";
              $DtRm =  getData($conn,$SqlData);
            }

          }

        }

    }
    //cek is Dt RM 55

    if ($isDt_RM55!=="Y"){
      if ($rowRsView['CYL_TYPE']==="MELANGE"){
        $SqlMbDt =
          " SELECT sum(YC.CYC_DATA_VALUE*CYRM_CONSUME) CYC_DATA_VALUE
            FROM (SELECT rm.*
                    FROM mgtapps.cst_yarn_left yl,
                         mgtapps.cst_yarn_calculation yc,
                         mgtapps.cst_yarn_rm_hdr rh,
                         mgtapps.cst_yarn_rm_multi rm
                   WHERE     yl.CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
                         AND YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
                         AND YC.CYC_TOP_NO = 55
                         AND YC.CYC_SYS_ID = RH.CYRH_CYC_SYS_ID
                         AND RH.CYRH_SYS_ID = RM.CYRM_CYRH_SYS_ID) dt
                 ,mgtapps.cst_yarn_left yl
                 ,mgtapps.cst_yarn_calculation yc
          where CYL_SYS_ID = CYRM_CYL_SYS_ID
          and YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
          and YC.CYC_TOP_NO = 73 ";
          //echo $SqlMbDt;
        $rsMbDt = oci_parse($conn,$SqlMbDt);
        oci_execute ($rsMbDt);
        while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
            $DtRm = $rowMbDt['CYC_DATA_VALUE'];
        }
      }else if ($rowRsView['CYL_TYPE']==="SUPERBA"){

      $SqlMbDt =
        " select CYC_DATA_VALUE GET_DATA
          from mgtapps.cst_yarn_calculation
          where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
          and cyc_top_no = 73 ";
      //echo $SqlMbDt;

      $DtRm =  getData($conn,$SqlMbDt);
      $valDt27 = $DtRm;

       } else{
        if (!empty($rowRsView['FGETMBCOST'])){
          $DtRm =	$rowRsView['FGETMBCOST'];
        } else {
          $sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
          $DtRm =  getData($conn,$sqlDtRm);
        }
      }
  }

  }

  $valDt27 = $DtRm;
  if ($rowRsView['CYL_TYPE']==="ACY"){
    $valDt27=0;
    //if ($USER_NAME==="1949") {echo "DtRm 2 $DtRm<br>";}
    //if ($USER_NAME === "1949") {echo $rowRsView['CYL_SYS_ID']."</br>";	}
  }
  //$valDt27 = setNumber($conn,"Number",$valDt27,4);
  //val data 24
  //val data 24
	$valDt24="";
	$CK_CYL_TYPE=$rowRsView['CYL_TYPE']; $CK_CYL_SHADE_NAME=$rowRsView['CYL_SHADE_NAME']; $CK_CYL_SYS_ID=$rowRsView['CYL_SYS_ID'];
	$CK_FGETCHIPRATE="";
	if (!empty($rowRsView['FGETCHIPRATE'])){
		$CK_FGETCHIPRATE=$rowRsView['FGETCHIPRATE'];
	}
	include("COSTING_CHIP_RATE_XLS.php");
	$valDt24 = setNumber($conn,"Number",$valDt24,4);
//val data 24
  //echo "valDt24 $valDt24";die();//for check
  //$valDt24 = setNumber($conn,"Number",$valDt24,4);
  //val data 24
  //die();//for check

//val data 11A
//$valDt11A = $valDt11 - $valDt24 - $valDt27;
//val data 11A

$sqlCk= "select fCalcFunc('$valDt11','$valDt24','-') GET_DATA from dual ";
$valDt11A =  getData($conn,$sqlCk);

$sqlCk= "select fCalcFunc('$valDt11A','$valDt27','-') GET_DATA from dual ";
$valDt11A =  getData($conn,$sqlCk);

?>
