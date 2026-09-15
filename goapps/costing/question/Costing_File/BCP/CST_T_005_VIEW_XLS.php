<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");

$P_FORM_NAME = "NULL";if(isset($_GET['P_FORM_NAME'])) {$P_FORM_NAME=$_GET['P_FORM_NAME'];}

$CYL_TYPE_S = "NULL";if(isset($_GET['CYL_TYPE_S'])) {$CYL_TYPE_S=$_GET['CYL_TYPE_S'];}

$CYL_SHADE_CODE_S = "NULL";if(isset($_GET['CYL_SHADE_CODE_S'])){$CYL_SHADE_CODE_S=$_GET['CYL_SHADE_CODE_S'];}
$CYL_SHADE_NAME_S = "NULL";if(isset($_GET['CYL_SHADE_NAME_S'])){$CYL_SHADE_NAME_S=$_GET['CYL_SHADE_NAME_S'];}

$CMY_LUSTURE_S = "NULL";if(isset($_GET['CMY_LUSTURE_S'])){$CMY_LUSTURE_S=$_GET['CMY_LUSTURE_S'];}

$DENIER_S = "NULL";if(isset($_GET['DENIER_S'])){$DENIER_S=$_GET['DENIER_S'];}

$FILAMENT_S = "NULL";if(isset($_GET['FILAMENT_S'])){$FILAMENT_S=$_GET['FILAMENT_S'];}

$INTERMINGLING_S = "NULL";if(isset($_GET['INTERMINGLING_S'])){$INTERMINGLING_S=$_GET['INTERMINGLING_S'];}

$CROSS_SECTION_S = "NULL";if(isset($_GET['CROSS_SECTION_S'])){$CROSS_SECTION_S=$_GET['CROSS_SECTION_S'];}

$HEATSET_S = "NULL";if(isset($_GET['HEATSET_S'])){$HEATSET_S=$_GET['HEATSET_S'];}

$CUSTOMER_S = "NULL";if(isset($_GET['CUSTOMER_S'])){$CUSTOMER_S=$_GET['CUSTOMER_S'];}

$CYL_LEFT_NO_S="NULL";if(isset($_GET['CYL_LEFT_NO_S'])) {$CYL_LEFT_NO_S=$_GET['CYL_LEFT_NO_S'];}

$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";

$SqlView = "SELECT  CMY_NAME
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
                    ,CYL_TYPE
                   FROM mgtapps.cst_mst_yarn y,
                        mgtapps.cst_yarn_left l,
                        mgtapps.cst_yarn_calculation_cur cc
                  WHERE cycc_cyl_sys_id = cyl_sys_id
                    AND cyl_cmy_sys_id = cmy_sys_id
                    AND cyl_prs_type = cycc_prs_type
                    AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_TYPE <> 'POY' ";

  $SqlViewWhere = "";
  if ( $CYL_TYPE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
  }
  if ( $CYL_SHADE_CODE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
  }
  if ( $CYL_SHADE_NAME_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
  }

  if ( $CMY_LUSTURE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CMY_LUSTURE = '$CMY_LUSTURE_S' ";
  }

  if ( $DENIER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_13_DATA_VALUE = '$DENIER_S' ";
  }

  if ( $FILAMENT_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' ";
  }

  if ( $INTERMINGLING_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' ";
  }

  if ( $CROSS_SECTION_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' ";
  }

  if ( $HEATSET_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' ";
  }

  if ( $CYL_LEFT_NO_S!=="NULL"){
    if ( $CYL_LEFT_NO_S !==""){
      $SqlViewWhere = "$SqlViewWhere and CYL_LEFT_NO = '$CYL_LEFT_NO_S' ";
    }
  }

  if ( $CUSTOMER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
          where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
          and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
  }

?>

<html>
<head>
</head>
<body>
    <style type="text/css">
    body{
        font-family: sans-serif;
    }
    table{
        margin: 20px auto;
        border-collapse: collapse;
    }
    table th,
    table td{
        border: 1px solid #3c3c3c;
        padding: 3px 8px;

    }
    a{
        background: blue;
        color: #fff;
        padding: 8px 10px;
        text-decoration: none;
        border-radius: 2px;
    }
    </style>
<?php
$excelNm = "Summary List Product Guidelines.Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : Summary List Product Customer $CUSTOMER_S </td>";
echo "</tr>";
echo "</table>";
?>
<table>

<?php
  include('CST_FIND_PRODUCT_VIEW_XLS_HDR.php');

  //$SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";

  $SqlView = "select dt.*,MPD_SEQ_NO
                from ($SqlView) dt
                    ,(SELECT MPD_VALUE, MPD_SEQ_NO
                      FROM MGTAPPS.MST_PARAM_DATA
                      WHERE mpd_mpdk_key = 'GUIDELINES SEQ'
                      ) dt_seqno
                WHERE dt_seqno.MPD_VALUE(+) = dt.CYL_SYS_ID ";

  $SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere ORDER BY NVL (MPD_SEQ_NO, 999999) ) d ";

  $SqlView = "$sqlSelect from ($SqlView) d ";

  //echo "<tr><td>$SqlView</td></tr>";

  $rsView = oci_parse($conn,$SqlView);
  oci_execute ($rsView);
  while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
    include("CST_FIND_PRODUCT_CALC_DTL_XLS.php");
    /*//val data 11
    		$valDt11 = "";
    		if (!empty($rowRsView['FGET_V4'])){
    			$valDt11 = $rowRsView['FGET_V4'];
    		}
    		$valDt11 = setNumber($conn,"Number",$valDt11,4);

    //val data 27
    		$valDt27 = 0;
    		$TotRecDt = 0; $DtMb = 0; $DtRm = 0;

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
                $DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
              }
            } else {

              $SqlData =
                  "SELECT 'Y' GET_DATA
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
      		}else{
      			if (!empty($rowRsView['FGETMBCOST'])){
      				$DtRm =	$rowRsView['FGETMBCOST'];
      			} else {
      				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
      				$DtRm =  getData($conn,$sqlDtRm);
      			}
      		}

        }
    		$valDt27 = $DtRm;
    		$valDt27 = setNumber($conn,"Number",$valDt27,4);

    //val data 24
    		$valDt24 = "";
    		if ($rowRsView['CYL_TYPE']==="MELANGE"){
    			$TotRecDt = 0; $DtMb = "";
    			$SqlMbDt =
    				" SELECT distinct YC.CYC_DATA_VALUE
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
    				and YC.CYC_TOP_NO = 55 ";
    			$rsMbDt = oci_parse($conn,$SqlMbDt);
    			oci_execute ($rsMbDt);
    			while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
    				$DtMb = "";
    				if (!empty($rowMbDt['CYC_DATA_VALUE'])){
    						$TotRecDt++;
    						$DtMb = $rowMbDt['CYC_DATA_VALUE'];
    				}
    				if ($TotRecDt>1){
    						$DtMb = "</br>$DtMb";
    				}
    				$valDt24 = $DtMb;
    			}
    		}else if ($rowRsView['CYL_TYPE']==="PTY BO"){

    			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
    									where A.CYC_CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
    									and A.CYC_TOP_NO = 55";
    			$valDt24 =  getData($conn,$SqlData);

    		}else{

          $sqlDtRm= "select MGTAPPS.pkg_yarn_calculation.fGetRM_PTY_BO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
    			$RM_PTY_BO = getData($conn,$sqlDtRm);

    			if ($RM_PTY_BO !=="" ){

    				$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
    										where A.CYC_CYL_SYS_ID = '$RM_PTY_BO'
    										and A.CYC_TOP_NO = 55";

    				$valDt24 =  getData($conn,$SqlData);

    			} else {

            if (!empty($rowRsView['FGETCHIPRATE'])){
      				$valDt24 = $rowRsView['FGETCHIPRATE'];
      			} else {
      				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
      				$DtRm =  getData($conn,$sqlDtRm);
      				$valDt24 = $DtRm;
      			}

          }

    		}

        if ($rowRsView['CYL_TYPE']==="ACY"){
          $sqlDtRm= "select CYRH_RM_COST GET_DATA
                    from cst_yarn_calculation a
                         ,cst_yarn_rm_hdr b
                    where cyc_cyl_sys_id = ".$rowRsView['CYL_SYS_ID']."
                    and cyc_top_no = 55
                    and A.CYC_FORMULA_TYPE = 'Raw_Material'
                    and B.CYRH_TYPE = 'Multi Yarn'
                    and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
                    ";
          //if ($USER_NAME==="1949"){ echo "masuk sini <br>is Dt 55 $isDt_RM55<br>$sqlDtRm"; }
          $DtRm =  getData($conn,$sqlDtRm);
          $valDt24 = $DtRm;
        }

    		//$valDt24 = setNumber($conn,"Number",$valDt24,2);
    //val data 24

    //val data 11A
    //$valDt11A = $valDt11 - $valDt24 - $valDt27;
    //val data 11A
    $sqlCk= "select fCalcFunc('$valDt11','$valDt24','-') GET_DATA from dual ";
    $valDt11A =  getData($conn,$sqlCk);
    //echo "$sqlCk<br>";
    $sqlCk= "select fCalcFunc('$valDt11A','$valDt27','-') GET_DATA from dual ";
    $valDt11A =  getData($conn,$sqlCk);*/


?>
<tr>
    <td>
      <?php echo $rowRsView['CYL_LEFT_NO']; ?> <!-- Left no -->
    </td>
    <td>
      <?php echo $rowRsView['REC_NO']; ?> <!-- 1 no -->
    </td>
    <td>
      <?php echo $rowRsView['CYL_TYPE']; ?><!--2 Type-->
    </td>
    <td>
      <?php echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE']; ?><!--3 Shade Name-->
    </td>
    <td>
      <?php echo $rowRsView['CMY_NAME']; ?><!--4 Name-->
    </td>
    <td>
      <?php echo $rowRsView['FGET_MCNAME']; ?><!--5 M/C -->
    </td>

    <td <?php echo "style='font-size: 18px;background-color:$ClrView11'"; ?> >
      <b><?php echo setNumber($conn,"Number",$valDt11,4); ?></b>
    </td><!-- 6 -->
    <td <?php echo "style='font-size: 18px;background-color:$ClrView24'"; ?> >
      <b><?php echo setNumber($conn,"Number",$valDt24,4); ?></b>
    </td><!-- 7 -->
    <td <?php echo "style='font-size: 18px;background-color:$ClrView27'"; ?> >
      <?php echo setNumber($conn,"Number",$valDt27,4); ?>
    </td><!-- 8 -->
    <td align="right"
				<?php echo "style='font-size: 18px;background-color:$ClrView11A'"; ?>
		>
			<b><?php echo $valDt11A; ?></b>
		</td><!-- 9 -->
    <td>
        <?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
    </td><!---10 -->
    <td>
        <?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
    </td><!--11-->
    <td>
        <?php if (!empty($rowRsView['FGET_V1'])){echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);}?>
    </td><!---12-->
    <td>
        <?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);}?>
    </td><!--13-->
    <td>
      <?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?>
    </td><!--14-->
    <td>
      <?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?>
    </td><!--15-->
    <td>
      <?php if (!empty($rowRsView['DENIER'])){echo $rowRsView['DENIER']; }?>
    </td><!--16-->
    <td>
      <?php if (!empty($rowRsView['FILAMENT'])){echo $rowRsView['FILAMENT'];}?>
    </td><!--17-->
    <td>
      <?php if (!empty($rowRsView['INTERMINGLING'])){echo $rowRsView['INTERMINGLING'];}?>
    </td><!--18-->
    <td>
      <?php if (!empty($rowRsView['HEATSET'])){ echo $rowRsView['HEATSET'];}?>
    </td><!--19-->
    <td>
      <?php if (!empty($rowRsView['CROSS_SECTION'])){ echo $rowRsView['CROSS_SECTION']; }?>
    </td><!--20-->
    <td>
      <?php if (!empty($rowRsView['CMY_LUSTURE'])){ echo $rowRsView['CMY_LUSTURE'];}?>
    </td><!---21-->
    <td>
      <?php
      //$isDt_RM55==="Y" is data RM 55
      //$TotPoy_Rm55=== Tot POY RM
      //$isMulti_RM RM 55 multi`
      //if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55<br>";}
      if ($isDt_RM55==="Y"){
          if ($TotPoy_Rm55 > 1){
            if ($isMulti_RM==="Y"){
              $TotRecDt = 0; $DtMb = "";
              $SqlMbDt =
                " SELECT YC.CYC_DATA_VALUE
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
                and YC.CYC_TOP_NO = 64
                and YC.CYC_DATA_VALUE is not null ";
              $rsMbDt = oci_parse($conn,$SqlMbDt);
              oci_execute ($rsMbDt);
              $clr = "red";
              while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
                $DtMb = "";
                if (!empty($rowMbDt['CYC_DATA_VALUE'])){
                    $TotRecDt++;
                    $DtMb = "<font color='$clr'>".$rowMbDt['CYC_DATA_VALUE']."</font>";
                }
                if ($TotRecDt>1){
                    $DtMb = "<br>$DtMb";
                }
                if ($DtMb!=="") { echo $DtMb; }
                if ($clr === "red"){
                  $clr = "blue";
                }else if ($clr === "blue"){
                  $clr = "red";
                }
              }
            } else {
               $TotRecDt = 0; $DtMb = "";
               $SqlMbDt =
                "
                SELECT CYCC_TOP_64_DATA_VALUE DT_VAL
                  FROM (SELECT CLLP_CYL_SYS_ID,CLLP_SEQ_LVL
                          FROM cst_lvl_left_prod a
                         WHERE CLLP_TYPE = 'POY' AND CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')a
                         ,mgtapps.cst_yarn_calculation_cur b
                where   CLLP_CYL_SYS_ID = CYCC_CYL_SYS_ID
                order by CLLP_SEQ_LVL";

               $rsMbDt = oci_parse($conn,$SqlMbDt);
               oci_execute ($rsMbDt);
               $clr = "red";
               while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
                $DtMb = "";
                if (!empty($rowMbDt['DT_VAL'])){
                    $TotRecDt++;
                    $DtMb = "<font color='$clr'>".$rowMbDt['DT_VAL']."</font>";
                }
                if ($TotRecDt>1){
                    $DtMb = "<br>$DtMb";
                }
                if ($DtMb!=="") { echo $DtMb; }
                if ($clr === "red"){
                  $clr = "blue";
                }else if ($clr === "blue"){
                  $clr = "red";
                }
               }

            }
          } else {
            $sqlMbNmRaw =
            "SELECT CLLP_CYL_SYS_ID GET_DATA FROM cst_lvl_left_prod
            WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND CLLP_TYPE = 'POY' ";
            $CYCC_CYL_SYS_ID =  getData($conn,$sqlMbNmRaw);

            $sqlMbNmRaw =
            "select CYCC_TOP_64_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation_cur
            where CYCC_CYL_SYS_ID = '$CYCC_CYL_SYS_ID'";
            $MbNmRaw =  getData($conn,$sqlMbNmRaw);

            echo $MbNmRaw;
          }
      }
      //cek is Dt RM 55

      if ($isDt_RM55!=="Y"){

        if ($rowRsView['CYL_TYPE']==="MELANGE"){
          $TotRecDt = 0; $DtMb = "";
          $SqlMbDt =
            " SELECT YC.CYC_DATA_VALUE
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
            and YC.CYC_TOP_NO = 64 ";
          $rsMbDt = oci_parse($conn,$SqlMbDt);
          oci_execute ($rsMbDt);
          while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
            $DtMb = "";
            if (!empty($rowMbDt['CYC_DATA_VALUE'])){
                $TotRecDt++;
                $DtMb = $rowMbDt['CYC_DATA_VALUE'];
            }
            if ($TotRecDt>1){
                $DtMb = "</br>$DtMb";
            }
            if ($DtMb!=="") { echo $DtMb; }
          }
        }else{
          if (!empty($rowRsView['FGETMBNAME'])){
            echo $rowRsView['FGETMBNAME'];
          } else {
  					$sqlMbNmRaw = "select MGTAPPS.pkg_yarn_marketing.fgetmbname_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
  					$MbNmRaw =  getData($conn,$sqlMbNmRaw);
  					echo $MbNmRaw;
  				}
        }

      }
      ?>
    </td><!--22-->
    <td>
      <?php
      if ($rowRsView['CYL_TYPE']==="MELANGE"){
        $TotRecDt = 0; $DtMb = "";
        $SqlMbDt =
          " SELECT distinct YC.CYC_DATA_VALUE
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
          and YC.CYC_TOP_NO = 20 ";
        $rsMbDt = oci_parse($conn,$SqlMbDt);
        oci_execute ($rsMbDt);
        while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
          $DtMb = "";
          if (!empty($rowMbDt['CYC_DATA_VALUE'])){
              $TotRecDt++;
              $DtMb = $rowMbDt['CYC_DATA_VALUE'];
          }
          if ($TotRecDt>1){
              $DtMb = "</br>$DtMb";
          }
          if ($DtMb!=="") { echo $DtMb; }
        }
      }else{
        if (!empty($rowRsView['FGETCHIP'])){
          echo $rowRsView['FGETCHIP'];
        } else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchip_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo $DtRm;
				}
      }
      ?>
    </td><!--23-->
    <td>
      <?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
    </td><!--24-->
    <td>
      <?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];}?>
    </td><!--25-->
    <td>
      <?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>
    </td><!--26-->
    <td>
      <?php
      //$isDt_RM55==="Y" is data RM 55
			//$TotPoy_Rm55=== Tot POY RM
			//$isMulti_RM RM 55 multi`
			//if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55<br>";}
			if ($isDt_RM55==="Y"){
					if ($TotPoy_Rm55 > 1){
						if ($isMulti_RM==="Y"){

							$TotRecDt = 0; $DtMb = "";
							$SqlMbDt =
							 "
							 SELECT CYCC_TOP_71_DATA_VALUE DT_VAL
								 FROM (SELECT CLLP_CYL_SYS_ID,CLLP_SEQ_LVL
												 FROM cst_lvl_left_prod a
												WHERE CLLP_TYPE = 'POY' AND CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')a
												,mgtapps.cst_yarn_calculation_cur b
							 where   CLLP_CYL_SYS_ID = CYCC_CYL_SYS_ID
							 order by CLLP_SEQ_LVL";

							$rsMbDt = oci_parse($conn,$SqlMbDt);
							oci_execute ($rsMbDt);
							$clr = "red";
							while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
							 $DtMb = "";
							 if (!empty($rowMbDt['DT_VAL'])){
									 $TotRecDt++;
									 $DtMb = "<font color='$clr'>".$rowMbDt['DT_VAL']."</font>";
							 }
							 if ($TotRecDt>1){
									 $DtMb = "<br>$DtMb";
							 }
							 if ($DtMb!=="") { echo $DtMb; }
							 if ($clr === "red"){
								 $clr = "blue";
							 }else if ($clr === "blue"){
								 $clr = "red";
							 }
							}

						} else {
							 $TotRecDt = 0; $DtMb = "";
							 $SqlMbDt =
								"
								SELECT CYCC_TOP_71_DATA_VALUE DT_VAL
									FROM (SELECT CLLP_CYL_SYS_ID,CLLP_SEQ_LVL
													FROM cst_lvl_left_prod a
												 WHERE CLLP_TYPE = 'POY' AND CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')a
												 ,mgtapps.cst_yarn_calculation_cur b
								where   CLLP_CYL_SYS_ID = CYCC_CYL_SYS_ID
								order by CLLP_SEQ_LVL";

							 $rsMbDt = oci_parse($conn,$SqlMbDt);
							 oci_execute ($rsMbDt);
							 $clr = "red";
							 while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
								$DtMb = "";
								if (!empty($rowMbDt['DT_VAL'])){
										$TotRecDt++;
										$DtMb = "<font color='$clr'>".$rowMbDt['DT_VAL']."</font>";
								}
								if ($TotRecDt>1){
										$DtMb = "<br>$DtMb";
								}
								if ($DtMb!=="") { echo $DtMb; }
								if ($clr === "red"){
									$clr = "blue";
								}else if ($clr === "blue"){
									$clr = "red";
								}
							 }

						}
					} else {
						$sqlMbNmRaw =
						"SELECT CLLP_CYL_SYS_ID GET_DATA FROM cst_lvl_left_prod
						WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND CLLP_TYPE = 'POY' ";
						$CYCC_CYL_SYS_ID =  getData($conn,$sqlMbNmRaw);

						$sqlMbNmRaw =
						"select CYCC_TOP_71_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation_cur
						where CYCC_CYL_SYS_ID = '$CYCC_CYL_SYS_ID'";
						$MbNmRaw =  getData($conn,$sqlMbNmRaw);

						echo $MbNmRaw;
					}
			}
			//cek is Dt RM 55


      if ($isDt_RM55!=="Y"){
        if ($rowRsView['CYL_TYPE']==="MELANGE"){
          $TotRecDt = 0; $DtMb = "";
          $SqlMbDt =
            " SELECT YC.CYC_DATA_VALUE
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
            and YC.CYC_TOP_NO = 71 ";
            //echo $SqlMbDt;
          $rsMbDt = oci_parse($conn,$SqlMbDt);
          oci_execute ($rsMbDt);
          while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
            $DtMb = "";
            if (!empty($rowMbDt['CYC_DATA_VALUE'])){
                $TotRecDt++;
                $DtMb = $rowMbDt['CYC_DATA_VALUE'];
            }
            if ($TotRecDt>1){
                $DtMb = "</br>$DtMb";
            }
            if ($DtMb!=="") { echo $DtMb; }
          }
        }else{
          if (!empty($rowRsView['FGETRPDOZ'])){
            echo setNumber($conn,"Number",$rowRsView['FGETRPDOZ'],2);
          } else {
  					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETRPDOZ_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
  					$DtRm =  getData($conn,$sqlDtRm);
  					echo $DtRm;
  				}
        }
      }
      ?>
    </td><!--27-->
    <td>
      <?php
      //$isDt_RM55==="Y" is data RM 55
			//$TotPoy_Rm55=== Tot POY RM
			//$isMulti_RM RM 55 multi`
			//if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55<br>";}
			if ($isDt_RM55==="Y"){
					if ($TotPoy_Rm55 > 1){
						if ($isMulti_RM==="Y"){

							$TotRecDt = 0; $DtMb = "";
							$SqlMbDt =
							 "
							 SELECT CYCC_TOP_72_DATA_VALUE DT_VAL
								 FROM (SELECT CLLP_CYL_SYS_ID,CLLP_SEQ_LVL
												 FROM cst_lvl_left_prod a
												WHERE CLLP_TYPE = 'POY' AND CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')a
												,mgtapps.cst_yarn_calculation_cur b
							 where   CLLP_CYL_SYS_ID = CYCC_CYL_SYS_ID
							 order by CLLP_SEQ_LVL";

							$rsMbDt = oci_parse($conn,$SqlMbDt);
							oci_execute ($rsMbDt);
							$clr = "red";
							while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
							 $DtMb = "";
							 if (!empty($rowMbDt['DT_VAL'])){
									 $TotRecDt++;
									 $DtMb = "<font color='$clr'>".$rowMbDt['DT_VAL']."</font>";
							 }
							 if ($TotRecDt>1){
									 $DtMb = "<br>$DtMb";
							 }
							 if ($DtMb!=="") { echo $DtMb; }
							 if ($clr === "red"){
								 $clr = "blue";
							 }else if ($clr === "blue"){
								 $clr = "red";
							 }
							}

						} else {
							 $TotRecDt = 0; $DtMb = "";
							 $SqlMbDt =
								"
								SELECT CYCC_TOP_72_DATA_VALUE DT_VAL
									FROM (SELECT CLLP_CYL_SYS_ID,CLLP_SEQ_LVL
													FROM cst_lvl_left_prod a
												 WHERE CLLP_TYPE = 'POY' AND CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')a
												 ,mgtapps.cst_yarn_calculation_cur b
								where   CLLP_CYL_SYS_ID = CYCC_CYL_SYS_ID
								order by CLLP_SEQ_LVL";

							 $rsMbDt = oci_parse($conn,$SqlMbDt);
							 oci_execute ($rsMbDt);
							 $clr = "red";
							 while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
								$DtMb = "";
								if (!empty($rowMbDt['DT_VAL'])){
										$TotRecDt++;
										$DtMb = "<font color='$clr'>".$rowMbDt['DT_VAL']."</font>";
								}
								if ($TotRecDt>1){
										$DtMb = "<br>$DtMb";
								}
								if ($DtMb!=="") { echo $DtMb; }
								if ($clr === "red"){
									$clr = "blue";
								}else if ($clr === "blue"){
									$clr = "red";
								}
							 }

						}
					} else {
						$sqlMbNmRaw =
						"SELECT CLLP_CYL_SYS_ID GET_DATA FROM cst_lvl_left_prod
						WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND CLLP_TYPE = 'POY' ";
						$CYCC_CYL_SYS_ID =  getData($conn,$sqlMbNmRaw);

						$sqlMbNmRaw =
						"select CYCC_TOP_72_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation_cur
						where CYCC_CYL_SYS_ID = '$CYCC_CYL_SYS_ID'";
						$MbNmRaw =  getData($conn,$sqlMbNmRaw);

						echo $MbNmRaw;
					}
			}
			//cek is Dt RM 55

      if ($isDt_RM55!=="Y"){

        if ($rowRsView['CYL_TYPE']==="MELANGE"){
          $TotRecDt = 0; $DtMb = "";
          $SqlMbDt =
            " SELECT YC.CYC_DATA_VALUE
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
            and YC.CYC_TOP_NO = 72 ";
            //echo $SqlMbDt;
          $rsMbDt = oci_parse($conn,$SqlMbDt);
          oci_execute ($rsMbDt);
          while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
            $DtMb = "";
            if (!empty($rowMbDt['CYC_DATA_VALUE'])){
                $TotRecDt++;
                $DtMb = $rowMbDt['CYC_DATA_VALUE'];
            }
            if ($TotRecDt>1){
                $DtMb = "</br>$DtMb";
            }
            if ($DtMb!=="") {
              echo $DtMb;
            }
          }
        }else{
          if (!empty($rowRsView['FGETMBRATE'])){
            echo setNumber($conn,"Number",$rowRsView['FGETMBRATE'],3);
          } else {
            $sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBRATE_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
            $DtRm =  getData($conn,$sqlDtRm);
            echo $DtRm;
          }
        }

      }

      ?>
    </td><!--28-->
    <td>
      <?php
      if ($rowRsView['CYL_TYPE']==="MELANGE"){
        $TotRecDt = 0; $DtMb = "";
        $SqlMbDt =
          " SELECT YC.CYC_DATA_VALUE
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
          and YC.CYC_TOP_NO = 116 ";
          //echo $SqlMbDt;
        $rsMbDt = oci_parse($conn,$SqlMbDt);
        oci_execute ($rsMbDt);
        while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
          $DtMb = "";
          if (!empty($rowMbDt['CYC_DATA_VALUE'])){
              $TotRecDt++;
              $DtMb = $rowMbDt['CYC_DATA_VALUE'];
          }
          if ($TotRecDt>1){
              $DtMb = "</br>$DtMb";
          }
          if ($DtMb!=="") {
            echo $DtMb;
          }
        }
      }else{
        if (!empty($rowRsView['FGETCNGOVRLST'])){
          echo setNumber($conn,"Number",$rowRsView['FGETCNGOVRLST'],3);
        } else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETCNGOVRLST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo $DtRm;
				}
      }
      ?>
    </td><!--29-->
    <td>
      <?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
    </td><!--30-->
    <td>
      <?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);}?>
    </td><!--31-->
    <td>
      <?php if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }?>
    </td><!--32-->
    <td>
      <?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);}?>
    </td><!--33-->
    <td>
      <?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
      }?>
    </td><!--34-->
    <td>
      <?php echo $CST_PRODUCT_FOWARDING; ?>
    </td><!--35-->
    <td>
      <?php
          $sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fGetDty_Prod(".$rowRsView['CYL_SYS_ID'].") GET_DATA from dual";
          $DtRm =  getData($conn,$sqlDtRm);
          echo setNumber($conn,"Number",$DtRm,0);
          //echo $DtRm;
      ?>
    </td><!--36-->
  </tr>

<?php
}
?>
</table>
<?php echo $fotLbl; ?>
</body>
</html>
