<div class="main" style="overflow-x:auto;" align="center">
<?php
	$SqlView = "SELECT 	CMY_NAME
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
               ";
	//if ($USER_NAME === "1949" ){echo $SqlView;}

	if ($FORM_NAME === "CST_2P1_0003"){
		$SqlView = "$SqlView AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidVal ";
	} else {
		$SqlView = "$SqlView AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidMkt ";
	}
	if ($CYL_IS_PROJECT_S==="Y"){
			$SqlView = " $SqlView and nvl(CYL_IS_PROJECT,'N') = nvl('$CYL_IS_PROJECT_S','N') ";
	}

	//if ($USER_NAME === "1949"){echo $SqlView;	}

	if ($FORM_NAME !== "CST_2P1_0003"){
		// if ($FORM_NAME === "CST_T_007"){
		// 		$SqlView = " $SqlView and CYL_IS_PROJECT = 'Y' ";
		// } else {
				$SqlView = " $SqlView and CYL_IS_VALID_PRD = 'Y' ";
		// }
	}

	$isCondValid = " AND CYL_IS_VALID_PRD = 'Y' ";
	/*if ($USER_NAME === "1949" ) {
		$SqlGet = "
								select null GET_DATA
								from mst_param_data t where t.MPD_MPDK_KEY = 'USER ADMIN COSTING'
								and mpd_value = upper('$USER_NAME')
							";
		//echo $SqlGet;
		$isCondValid = getData($conn,$SqlGet);
	}*/
	if ($FORM_NAME === "CST_T_005"){
		$SqlView = "select 	CMY_NAME
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
											 ,MPD_SEQ_NO
								from (
											SELECT 	CMY_NAME
															,CYCC_TOP_13_DATA_VALUE
															,CYCC_TOP_16_DATA_VALUE
															,CYCC_TOP_18_DATA_VALUE
															,CYCC_TOP_49_DATA_VALUE
															,CYCC_TOP_17_DATA_VALUE
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
												AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
												$isCondValid
												AND CYL_TYPE <> 'POY'
											) dt
										,(SELECT MPD_VALUE, MPD_SEQ_NO
						          FROM MGTAPPS.MST_PARAM_DATA
						         	WHERE mpd_mpdk_key = 'GUIDELINES SEQ'
											) dt_seqno
 								WHERE dt_seqno.MPD_VALUE(+) = dt.CYL_SYS_ID ";
	}

  $SqlViewWhere = "";

	if ($FORM_NAME === "CST_T_001") {
		$SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
	} else {

		if ( $CYL_TYPE_S !=="NULL"){
	  	$SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
	  }

	}

	if ( $CYL_PRODUCT_QUALITY_S !=="NULL"){
		$SqlViewWhere = "$SqlViewWhere and CYL_PRODUCT_QUALITY = '$CYL_PRODUCT_QUALITY_S' ";
	}

  if ( $CYL_SHADE_CODE_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
  }
  if ( $CYL_SHADE_NAME_S !=="NULL"){
		if ($CYL_SHADE_NAME_S !=="UNSELECTED") {
			if (substr($CYL_SHADE_NAME_S,0,1)==="(") {
				$SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME in $CYL_SHADE_NAME_S ";
			} else {
				$SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
			}
		}
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
		$SqlViewWhere = "$SqlViewWhere and CYL_LEFT_NO = '$CYL_LEFT_NO_S' ";
	}
  if ( $CUSTOMER_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
					where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
					and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
  }

	if ($FORM_NAME === "CST_T_005"){
			$SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere ORDER BY NVL (MPD_SEQ_NO, 999999) ) d ";
	} else {
			$SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
	}

	//if ($USER_NAME==="1949"){	}

  $SqlCnt = "select count(-1) GET_DATA from ($SqlView)  ";
    //echo "SqlCnt $SqlCnt</br>";

	$StsViewDt =  getData($conn,$SqlCnt);

	$totRows = $StsViewDt;

	$P_ROWS_DISP = 13;
    $P_LAST_PAGE_NO = ($totRows / $P_ROWS_DISP);
    //echo "P_LAST_PAGE_NO $P_LAST_PAGE_NO";
    if (strpos($P_LAST_PAGE_NO,'.') > 0 ){
    	$P_LAST_PAGE_NO = substr($P_LAST_PAGE_NO,0,strpos($P_LAST_PAGE_NO,'.'));
    	$P_LAST_PAGE_NO = $P_LAST_PAGE_NO + 1;
    }
    if ($P_LAST_PAGE_NO==0){
    	$P_LAST_PAGE_NO=1;
    }
  	if ($P_PAGE_NO=="1"){
	    $P_ROW_START  = 1;
	    $P_ROW_END    = $P_ROWS_DISP;
	} else {
	    $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
	    $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
	}

	if ($P_PAGE_NO > $P_LAST_PAGE_NO){
		$P_PAGE_NO = $P_LAST_PAGE_NO;
	}

	function lblNo($no,$clr){
		echo "<font size='1'color='$clr'>($no)</font></br>";
	}
	$lblNcClr = "red";

?>
<table style="width:100%">
	<tr >
		<td  align="center">
		<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
		<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
		<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 55px;"  value="<?php echo $P_PAGE_NO;?>"
				onkeypress="Javascript: if (event.keyCode==13) GoRecord();"
		>
		: <?php echo $P_LAST_PAGE_NO; ?>
		<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
		<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
	</tr>
</table>
<!--<div class="main" style="overflow-x:auto;" align="center">-->
<table style="width:150%" border="1">
	<tr>
		<td align="center">
			No
		</td>
		<td align="center">
			Left No </br>
			Product
		</td>
		<td align="center">
			Product Name
		</td>
<?php if ($byShadeMulti === "Y") { ?>
	<td align="center" >
		Shade Name
	</td>
<?php } ?>
		<td align="center" style="width:3%" align="center">
			<?php lblNo(1,$lblNcClr); ?>No
		</td>
<?php if ($byType === "Y") { ?>
		<td align="center" style="width:15%">
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td align="center" style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td align="center" style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } ?>
		<td align="center"  style="width:15%">
			<?php lblNo(4,$lblNcClr); ?>Name
		</td>

		<td align="center" >
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td>

		<td align="center" style="width:6%">
				<b><?php lblNo(6,$lblNcClr); ?>
				<?php echo $lbl_6; ?>
				</b>
		</td>
		<td align="center" >
			<?php lblNo(7,$lblNcClr); ?>Chip Rate
		</td>
		<td align="center" ><?php lblNo(8,$lblNcClr); ?>MB cost
		</td>
		<td align="center" style="width:7%">
			<b><?php lblNo('9',$lblNcClr); ?>
			Conv-Mb Cost</br>
			6-7-8</br></b>
		</td>


		<td align="center" >
			<?php lblNo(10,$lblNcClr); ?>Eff
		</td>
		<td align="center" >
			<?php lblNo(11,$lblNcClr); ?>Speed
		</td>

		<td align="center" >
			<?php lblNo(12,$lblNcClr); ?>V1<1.5</br>
		</td>
		<td align="center" >
			<?php lblNo(13,$lblNcClr); ?>V2=>1.5<3</br>
		</td>
		<td align="center" >
			<?php lblNo(14,$lblNcClr); ?>V3=>3<6
		</td>

		<td align="center" >
			<?php lblNo(15,$lblNcClr); ?>V5<12
		</td>


		<td align="center" style="width:3%">
			<?php lblNo(16,$lblNcClr); ?>Denier
		</td>
		<td align="center" style="width:4%">
			<?php lblNo(17,$lblNcClr); ?>Filament
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(18,$lblNcClr); ?>Intermingling
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(19,$lblNcClr); ?>Heatset
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(20,$lblNcClr); ?>Cross Section
		</td>
		<td align="center" style="width:3%">
			<?php lblNo(21,$lblNcClr); ?>Lusture
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(22,$lblNcClr); ?>MB Name
		</td>
		<td align="center" >
			<?php lblNo(23,$lblNcClr); ?>Chip
		</td>
		<td align="center" >
			<?php lblNo(24,$lblNcClr); ?>Packing type
		</td>
		<td align="center" >
			<?php lblNo(25,$lblNcClr); ?>Bobbin weight
		</td>
		<td align="center" >
			<?php lblNo(26,$lblNcClr); ?>no of Bobbins
		</td>

		<td align="center" >
			<?php lblNo(27,$lblNcClr); ?>Dozing
		</td>
		<td align="center" >
			<?php lblNo(28,$lblNcClr); ?>MB Rate
		</td>
		<td align="center" ><?php lblNo(29,$lblNcClr); ?>Change</br>
			Over Loss
		</td>
		<td align="center" >
			<?php lblNo(30,$lblNcClr); ?>Quality Loss
		</td>
		<td align="center" >
			<?php lblNo(31,$lblNcClr); ?>Intermigle Cost
		</td>
		<td align="center" >
			<?php lblNo(32,$lblNcClr); ?>Fixed Cost
		</td>
		<td align="center" >
			<?php lblNo(33,$lblNcClr); ?>Del-Pack cost
		</td>
		<td align="center" >
			<?php lblNo(34,$lblNcClr); ?>Final Ex</br>
			Factory</br>
			Cost
		</td>
		<td align="center" >
			<?php lblNo(35,$lblNcClr); ?>Fowarding
		</td>
		<td align="center" >
			<?php lblNo(36,$lblNcClr); ?>DTY Prod
		</td>
	</tr>
<?php

	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";

	//if ($USER_NAME === "1949") {echo "$SqlView</br>";	}
	if ($CYL_TYPE_S === "ACY"){
		//echo "CYL_TYPE_S $CYL_TYPE_S under Check</br>";
		//$SqlView = "$SqlView and CYL_LEFT_NO = 2915 ";
		//echo "SqlView $SqlView</br>";
		//die();
	}

  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);
	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$btnDtl = "Process('DETAIL ".$rowRsView['CYL_SYS_ID']."')";
		$btnSmltn = "Process('SIMULATION ".$rowRsView['CYL_SYS_ID']."')";
//cek Prosuct Use Bo
$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
//echo $slctUseRm;
$isUseRmBo = getDataUser($conn,$slctUseRm,$USER_NAME);
if ($isUseRmBo === "Y") {
	$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial_RmBO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual ";
	$deepMaterial =  getData($conn,$SqlData);
}


//val data 11
		$valDt11 = "";
		if (!empty($rowRsView['FGET_V4'])){
			$valDt11 = $rowRsView['FGET_V4'];
		}
		$valDt11 = setNumber($conn,"Number",$valDt11,4);

//val data 27
		$valDt27 = 0;
		$TotRecDt = 0; $DtMb = 0; $DtRm = 0;

		//if ($USER_NAME==="1949"){echo $rowRsView['CYL_TYPE'];}

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
			$DtMb = "";
			while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
				$DtRm = $rowMbDt['CYC_DATA_VALUE'];
				//echo "DtMb $DtMb";
			}
		} if ($rowRsView['CYL_TYPE']==="SUPERBA"){

		$SqlMbDt =
			" select CYC_DATA_VALUE GET_DATA
				from mgtapps.cst_yarn_calculation
				where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
				and cyc_top_no = 73 ";
		//echo $SqlMbDt;

		$DtRm =  getData($conn,$SqlMbDt);
		$valDt27 = $DtRm;

	 }  else{
			if (!empty($rowRsView['FGETMBCOST'])){
				$DtRm =	$rowRsView['FGETMBCOST'];
				//if ($USER_NAME==="1949"){ echo "cek1 $DtRm ";}
			} else {
				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
				//if ($USER_NAME==="1949"){echo $sqlDtRm;}
				$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
			}
		}

		$valDt27 = $DtRm;
	//$valDt27 = setNumber($conn,"Number",$valDt27,4);

//val data 24
		$valDt24 = "";
		//if ($USER_NAME==="1949"){ echo $rowRsView['CYL_TYPE']; }
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
				/*if ($TotRecDt>1){
						$DtMb = "</br>$DtMb";
				}*/
				$valDt24 = $DtMb;
			}
		} else if ($rowRsView['CYL_TYPE']==="PTY BO"){
			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
									where A.CYC_CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
									and A.CYC_TOP_NO = 55";
			$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
		} else if ($isUseRmBo==="Y"){

			$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
													'".$rowRsView['CYL_SYS_ID']."'
													,$deepMaterial
													,2
													) GET_DATA
									 from dual";
			$CylSysId_UseBo =getDataUser($conn,$sqlGetDt,$USER_NAME);

			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
									where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
									and A.CYC_TOP_NO = 55";
			$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
		}else{
			//cek RM PTY BO
			$sqlDtRm= "select MGTAPPS.pkg_yarn_calculation.fGetRM_PTY_BO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
			$RM_PTY_BO = getDataUser($conn,$sqlDtRm,$USER_NAME);

			if ($RM_PTY_BO !=="" ){

				$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
										where A.CYC_CYL_SYS_ID = '$RM_PTY_BO'
										and A.CYC_TOP_NO = 55";

				$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);

			} else {

				if (!empty($rowRsView['FGETCHIPRATE'])){
					$valDt24 = $rowRsView['FGETCHIPRATE'];
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
					$valDt24 = $DtRm;
				}

			}

		}

		if ($FORM_NAME === "CST_2P1_0003"){
			$SqlGet =  "select MGTAPPS.pkg_yarn_valuation.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual ";
			$valDt24 = getDataUser($conn,$SqlGet,$USER_NAME);
		}

		$valDt24 = setNumber($conn,"Number",$valDt24,4);
//val data 24

//val data 11A
//$sqlTotDt = "select $valDt11 - $valDt24 - $valDt27 GET_DATA From dual";echo $sqlTotDt;
$sqlGet= "select nvl(to_number('$valDt11'),0) - nvl(to_number('$valDt24'),0) - nvl(to_number('$valDt27'),0) GET_DATA from dual";
//echo $sqlGet."</br>";
$valDt11A =  getDataUser($conn,$sqlGet,$USER_NAME);
//echo "valDt11 $valDt11 - valDt24 $valDt24 - valDt27 $valDt27";
//$valDt11A = $valDt11 - $valDt24 - $valDt27;
//val data 11A

	?>
	<tr>
		<td align="center">
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
		<td align="center">
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
		<td align="center">
			<?php
				if (!empty($rowRsView['CYL_PRODUCT_QUALITY'])){
						echo $rowRsView['CYL_PRODUCT_QUALITY'];
				}
			?>
		</td>
<?php if ($byShadeMulti === "Y") { ?>
			<td align="center" >
			<?php echo $rowRsView['CYL_SHADE_NAME']; ?>
			</td>
<?php } ?>
		<td align="right" >
			<!-- 1 -->
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
<?php if ($byType === "Y") { ?>
		<td align="left">
			<!-- Customer -->
			<?php
				if ($CUSTOMER_S==="NULL"){
					if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; }
				} else {
					echo $CUSTOMER_S;
				}

			?>
		</td>
		<td align="center">
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td align="center">
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td align="left" >
			<!-- Customer -->
			<?php if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; } ?>
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td align="center" >
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td align="center" >
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } ?>
		<td align="left" >
			<?php if (!empty($rowRsView['CMY_NAME'])){ echo $rowRsView['CMY_NAME']; } ?>
		</td><!-- 4 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_MCNAME'])){ echo $rowRsView['FGET_MCNAME']; } ?>
		</td><!-- 5 -->
		<td align="right"
		 <?php echo "style='font-size: 18px;background-color:$ClrView11'"; ?>
		>
			<b><?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?></b>
		</td><!-- 6 -->
		<td align="center"
			<?php echo "style='font-size: 18px;background-color:$ClrView24'"; ?>
		>
			<b>
				<?php
				echo $valDt24;
				?>
			</b>
		</td><!-- 7 -->
		<td align="right"
			<?php echo "style='font-size: 18px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td><!-- 8 -->
		<td align="right"
				<?php echo "style='font-size: 18px;background-color:$ClrView11A'"; ?>
		>
			<b>
				<?php
					echo setNumber($conn,"Number",$valDt11A,4);
				?>
			</b>
		</td><!-- 9 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
		</td><!-- 10 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
		</td><!-- 11 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGET_V1'])){
				//echo $rowRsView['FGET_V1']."</br>";
				echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);

			}?>
		</td><!-- 12 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);  }?>
		</td><!-- 13 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?>
		</td><!-- 14 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?>
		</td><!-- 15 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['DENIER'])){
					echo $rowRsView['DENIER'];
				}
			?>
		</td><!-- 16 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['FILAMENT'])){
					echo $rowRsView['FILAMENT'];
				}
			?>
		</td><!-- 17 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['INTERMINGLING'])){
					echo $rowRsView['INTERMINGLING'];
				}
			?>
		</td><!-- 18 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['HEATSET'])){
					echo $rowRsView['HEATSET'];
				}
			?>
		</td><!-- 19 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['CROSS_SECTION'])){
					echo $rowRsView['CROSS_SECTION'];
				}
			?>
		</td><!-- 20 -->
		<td align="center">
			<?php
				if (!empty($rowRsView['CMY_LUSTURE'])){
					echo $rowRsView['CMY_LUSTURE'];
				}
			?>
		</td><!-- 21 -->
		<td align="center">
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
				} else {
					if (!empty($rowRsView['FGETMBNAME'])){
							echo $rowRsView['FGETMBNAME'];
					} else {
						$sqlMbNmRaw = "select MGTAPPS.pkg_yarn_marketing.fgetmbname_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
						$MbNmRaw =  getData($conn,$sqlMbNmRaw);
						echo $MbNmRaw;
					}
				}
			?>
		</td><!-- 22 -->
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
				$stsPic = "blue";
				while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
					$DtMb = "";
					if (!empty($rowMbDt['CYC_DATA_VALUE'])){
							$TotRecDt++;
							$DtMb = $rowMbDt['CYC_DATA_VALUE'];
					}
					if ($TotRecDt>1){$DtMb = "</br>$DtMb";}
					if ($DtMb!=="") {echo "<p style='color:$stsPic;'>$DtMb</p>";}
					if ($stsPic === "blue"){ $stsPic = "red";} else {$stsPic = "blue";}
				}
			} else {
				if (!empty($rowRsView['FGETCHIP'])){
					echo $rowRsView['FGETCHIP'];
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchip_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo $DtRm;
				}
			}
			?>
		</td><!-- 23 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
		</td><!-- 24 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];  }?>
		</td><!-- 25 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>
		</td><!-- 26 -->
		<td align="right">
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
					and YC.CYC_TOP_NO = 71 ";
					//echo $SqlMbDt;
				$rsMbDt = oci_parse($conn,$SqlMbDt);
				oci_execute ($rsMbDt);
				while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
					$DtMb = "";
					if (!empty($rowMbDt['CYC_DATA_VALUE'])){
							$TotRecDt++;
							$DtMb = $rowMbDt['CYC_DATA_VALUE'];
							$DtMb = setNumber($conn,"Number",$DtMb,2);
					}
					if ($TotRecDt>1){
							$DtMb = "</br>$DtMb";
					}
					if ($DtMb!=="") { echo $DtMb; }
				}
			} else {
				if (!empty($rowRsView['FGETRPDOZ'])){
					echo setNumber($conn,"Number",$rowRsView['FGETRPDOZ'],2);
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETRPDOZ_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo setNumber($conn,"Number",$DtRm,2);
				}
			}
			?>
		</td><!-- 27 -->
		<td align="right">
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
					and YC.CYC_TOP_NO = 72 ";
					//echo $SqlMbDt;
				$rsMbDt = oci_parse($conn,$SqlMbDt);
				oci_execute ($rsMbDt);
				while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
					$DtMb = "";
					if (!empty($rowMbDt['CYC_DATA_VALUE'])){
							$TotRecDt++;
							$DtMb = $rowMbDt['CYC_DATA_VALUE'];
							$DtMb = setNumber($conn,"Number",$DtMb,3);
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
					echo setNumber($conn,"Number",$DtRm,3);
				}
			}

			?>
		</td><!-- 28 -->
		<td align="right">
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
				$stsPic === "blue";
				while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
					$DtMb = "";
					if (!empty($rowMbDt['CYC_DATA_VALUE'])){
							$TotRecDt++;
							$DtMb = $rowMbDt['CYC_DATA_VALUE'];
							$DtMb = setNumber($conn,"Number",$DtMb,3);
					}
					if ($TotRecDt>1){
							$DtMb = "</br>$DtMb";
					}
					if ($DtMb!=="") {echo "<p style='color:$stsPic;'>$DtMb</p>";}
					if ($stsPic === "blue"){ $stsPic = "red";} else {$stsPic = "blue";}
				}
			} else {
				if (!empty($rowRsView['FGETCNGOVRLST'])){
					echo setNumber($conn,"Number",$rowRsView['FGETCNGOVRLST'],3);
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETCNGOVRLST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo setNumber($conn,"Number",$DtRm,3);
				}
			}
			?>
		</td><!-- 29 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
		</td><!-- 30 -->
		<td align="center" >
			<?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);
			}?>
		</td><!-- 31 -->
		<td align="center" >
			<?php
				if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }
			?>
		</td><!-- 32 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){
					echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);
				}?>
		</td><!-- 33 -->
		<td align="right">
			<?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
			}
			?>
		</td><!-- 34 -->
		<td align="right">
			<?php
				echo $CST_PRODUCT_FOWARDING;
			?>
		</td><!-- 35 -->
		<td align="right">
		<?php
				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fGetDty_Prod(".$rowRsView['CYL_SYS_ID'].") GET_DATA from dual";
				$DtRm =  getData($conn,$sqlDtRm);
				echo setNumber($conn,"Number",$DtRm,0);
				//echo $DtRm;
		?>
		</td><!-- 36 -->
	</tr>
	<?php
	}
	?>
</table>
</div>
<?php echo $fotLbl; ?>
