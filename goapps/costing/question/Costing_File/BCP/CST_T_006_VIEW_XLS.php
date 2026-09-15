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

$P_FORM_NAME = "NULL"; if(isset($_GET['P_FORM_NAME'])) {$P_FORM_NAME=$_GET['P_FORM_NAME'];}

$CYL_TYPE_S = "NULL";if(isset($_GET['CYL_TYPE_S'])){$CYL_TYPE_S=$_GET['CYL_TYPE_S'];}

$CYL_SHADE_CODE_S = "NULL";if(isset($_GET['CYL_SHADE_CODE_S'])) {$CYL_SHADE_CODE_S=$_GET['CYL_SHADE_CODE_S'];}
$CYL_SHADE_NAME_S = "NULL";if(isset($_GET['CYL_SHADE_NAME_S'])) {$CYL_SHADE_NAME_S=$_GET['CYL_SHADE_NAME_S'];}

$CYL_PRODUCT_QUALITY_S="";if(isset($_GET['CYL_PRODUCT_QUALITY_S'])) {$CYL_PRODUCT_QUALITY_S=$_GET['CYL_PRODUCT_QUALITY_S'];}

$CMY_LUSTURE_S = "NULL";if(isset($_GET['CMY_LUSTURE_S'])) {$CMY_LUSTURE_S=$_GET['CMY_LUSTURE_S'];}

$DENIER_S = "NULL";if(isset($_GET['DENIER_S'])) {$DENIER_S=$_GET['DENIER_S'];}

$FILAMENT_S = "NULL";if(isset($_GET['FILAMENT_S'])) {$FILAMENT_S=$_GET['FILAMENT_S'];}

$INTERMINGLING_S = "NULL";if(isset($_GET['INTERMINGLING_S'])) {$INTERMINGLING_S=$_GET['INTERMINGLING_S'];}

$CROSS_SECTION_S = "NULL";if(isset($_GET['CROSS_SECTION_S'])) {$CROSS_SECTION_S=$_GET['CROSS_SECTION_S'];}

$HEATSET_S = "NULL";if(isset($_GET['HEATSET_S'])){$HEATSET_S=$_GET['HEATSET_S'];}

$CUSTOMER_S = "NULL";if(isset($_GET['CUSTOMER_S'])){$CUSTOMER_S=$_GET['CUSTOMER_S'];}

$CYL_LEFT_NO_S="";if(isset($_GET['CYL_LEFT_NO_S'])){$CYL_LEFT_NO_S=$_GET['CYL_LEFT_NO_S'];}
//$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";

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
                    ,CYL_PRODUCT_QUALITY
             FROM mgtapps.cst_mst_yarn y,
                  mgtapps.cst_yarn_left l,
                  mgtapps.cst_yarn_calculation_cur cc
            WHERE cycc_cyl_sys_id = cyl_sys_id
              AND cyl_cmy_sys_id = cmy_sys_id
              AND cyl_prs_type = cycc_prs_type
              AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_IS_VALID_PRD = 'Y' ";

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

  if ( $CYL_PRODUCT_QUALITY_S!=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_PRODUCT_QUALITY = '$CYL_PRODUCT_QUALITY_S' ";
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

  if ( $CYL_LEFT_NO_S !==""){
    if ( $CYL_LEFT_NO_S !=="NULL"){
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
$excelNm = "Summary List Product Type Base.Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : Summary List Product Type $CYL_TYPE_S </td>";
echo "</tr>";
echo "</table>";
?>
<table>
<?php
  include('CST_FIND_PRODUCT_VIEW_XLS_HDR.php');

  $SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
  $SqlView = "$sqlSelect from ($SqlView) d ";

  //echo "<tr><td>$SqlView</td></tr>";
  $rsView = oci_parse($conn,$SqlView);
  oci_execute ($rsView);
  while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {

      //cek is Dt RM 55
    	$SqlData = "SELECT 'Y' GET_DATA FROM cst_lvl_left_prod
    							WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND rownum=1 ";
    	$isDt_RM55 		=  getData($conn,$SqlData);

    //val data 11
    		$valDt11 = "";
    		if (!empty($rowRsView['FGET_V4'])){
    			$valDt11 = $rowRsView['FGET_V4'];
    		}
    		$valDt11 = setNumber($conn,"Number",$valDt11,4);

    //val data 27
    if($isDt_RM55==="Y"){
			$SqlGet =
				"
			  SELECT count(-1) GET_DATA
			  FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_LEFT b
			  WHERE     a.CLLP_CYL_SYS_ID_REFF = CYL_SYS_ID
			   and CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
			   and cllp_type = 'POY'
				 ";
			$totPOY =  getData($conn,$SqlGet);
			if ($totPOY==="1"){
				$SqlGet =
					"
				  SELECT CLLP_CYL_SYS_ID GET_DATA
				  FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_LEFT b
				  WHERE     a.CLLP_CYL_SYS_ID_REFF = CYL_SYS_ID
				   and CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
				   and cllp_type = 'POY'
					 ";

				$CLLP_CYL_SYS_ID =  getData($conn,$SqlGet);

				$SqlMbDt =
					" select CYC_DATA_VALUE GET_DATA
						from mgtapps.cst_yarn_calculation
						where cyc_cyl_sys_id = '$CLLP_CYL_SYS_ID'
						and cyc_top_no = 73 ";
				//if($USER_NAME==="1949"){ echo $SqlMbDt."<br>"; };
				$DtRm =  getData($conn,$SqlMbDt);
				$valDt27 = $DtRm;

			}
		} else {
      $SqlMbDt =
  			" select CYC_DATA_VALUE GET_DATA
  				from mgtapps.cst_yarn_calculation
  				where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
  				and cyc_top_no = 73 ";
  		//echo $SqlMbDt;

  		$DtRm =  getData($conn,$SqlMbDt);
  		$valDt27 = $DtRm;
    }

    if ($rowRsView['CYL_TYPE']==="SUPERBA" || $rowRsView['CYL_SUPERBA_POY'] ==="N" ){
				$SqlMbDt = " select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation
										  where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."' and cyc_top_no = 73 ";
				$DtRm =  getData($conn,$SqlMbDt);
				$valDt27 = $DtRm;
		}

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

            if($isDt_RM55!=="Y"){
              if (!empty($rowRsView['FGETCHIPRATE'])){
        				$valDt24 = $rowRsView['FGETCHIPRATE'];
        			} else {
        				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
        				$DtRm =  getData($conn,$sqlDtRm);
        				$valDt24 = $DtRm;
        			}
            } else {

              $SqlGet =
      					"
      					SELECT count(-1) GET_DATA
      					FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_LEFT b
      					WHERE     a.CLLP_CYL_SYS_ID_REFF = CYL_SYS_ID
      					 and CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
      					 and cllp_type = 'POY'
      					 ";
      				$totPOY =  getData($conn,$SqlGet);
      				if ($totPOY==="1"){

                $SqlGet =
      						"
      					  SELECT CLLP_CYL_SYS_ID GET_DATA
      					  FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_LEFT b
      					  WHERE     a.CLLP_CYL_SYS_ID_REFF = CYL_SYS_ID
      					   and CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
      					   and cllp_type = 'POY'
      						 ";
      					$CLLP_CYL_SYS_ID =  getData($conn,$SqlGet);
      					$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
      											where A.CYC_CYL_SYS_ID = '$CLLP_CYL_SYS_ID'
      											and A.CYC_TOP_NO = 55";
      					$valDt24 =  getData($conn,$SqlData);

              }

            }
          }
    		}
    		$valDt24 = setNumber($conn,"Number",$valDt24,4);
    //val data 24

    //val data 11A
    if ($valDt11===""){$valDt11=0;} if ($valDt24===""){$valDt24=0;} if ($valDt27===""){$valDt27=0;}
    //echo "valDt11 $valDt11 - valDt24 $valDt24 - valDt27 $valDt27 <br>";
    $valDt11A = $valDt11 - $valDt24 - $valDt27;
    //val data 11A

?>
<tr>
    <td>
      <?php echo $rowRsView['CYL_LEFT_NO']; ?> <!-- Left no -->
    </td>
    <td>
      <?php echo $rowRsView['REC_NO']; ?> <!-- 1 no -->
    </td>
    <td>
      <?php
        if ($CUSTOMER_S!=="NULL"){
          echo $CUSTOMER_S;
        } else {
          if (!empty($rowRsView['CUSTOMER'])) {
            echo $rowRsView['CUSTOMER'];
          }
        }
      ?>
    </td> <!--2 Customer-->
    <td>
      <?php echo $rowRsView['CYL_PRODUCT_QUALITY']; ?>
    </td><!--3 Shade Name-->
    <td>
      <?php echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE']; ?>
    </td><!--3 Shade Name-->
    <td>
      <?php echo $rowRsView['CMY_NAME']; ?>
    </td><!--4 Name-->
    <td>
      <?php echo $rowRsView['FGET_MCNAME']; ?>
    </td><!--5 M/C -->
    <td <?php echo "style='font-size: 18px;background-color:$ClrView11'"; ?> >
      <b><?php echo $valDt11; ?></b>
    </td><!--6-->
    <td <?php echo "style='font-size: 18px;background-color:$ClrView24'"; ?> >
      <b><?php echo $valDt24; ?></b>
    </td><!--7-->
    <td <?php echo "style='font-size: 18px;background-color:$ClrView27'"; ?> >
      <?php echo setNumber($conn,"Number",$valDt27,4); ?>
    </td><!--8-->
    <td align="right"
				<?php echo "style='font-size: 18px;background-color:$ClrView11A'"; ?>
		>
			<b><?php echo $valDt11A; ?></b>
		</td><!--9-->
    <td>
        <?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
    </td><!---10-->
    <td>
        <?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
    </td><!--11-->
    <td>
        <?php if (!empty($rowRsView['FGET_V1'])){echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);}?>
    </td><!--12-->
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
    </td><!--21-->
    <td>
      <?php
				$sqlMbNmRaw = " select CYC_DATA_VALUE GET_DATA
												from mgtapps.cst_yarn_calculation
												where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
												and cyc_top_no = 64
											";
				$MbNmRaw =  getData($conn,$sqlMbNmRaw);
				echo $MbNmRaw;
			?>
    </td><!--22-->
    <td>
      <?php
      if ($rowRsView['CYL_TYPE'] === "MELANGE"){
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
      }
      else{
        if (!empty($rowRsView['FGETCHIP'])){ echo $rowRsView['FGETCHIP'];
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
      if ($rowRsView['CYL_TYPE'] === "MELANGE"){
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
      ?>
    </td><!--27-->
    <td>
      <?php
      if ($rowRsView['CYL_TYPE'] === "MELANGE"){
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
      ?>
    </td><!--28-->
    <td>
      <?php
      if ($rowRsView['CYL_TYPE'] === "MELANGE"){
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
