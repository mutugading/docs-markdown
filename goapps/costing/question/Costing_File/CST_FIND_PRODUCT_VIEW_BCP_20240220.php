<div class="main" style="overflow-x:auto;" align="center">
<?php
	//if ($USER_NAME==="1949"){ echo "Form CST_FIND_PRODUCT_VIEW<br>"; }
	$font = 12;
	$SqlView = "SELECT 	CMY_NAME
											,CYCC_TOP_13_DATA_VALUE DENIER
											,CYCC_TOP_16_DATA_VALUE FILAMENT
											,CYCC_TOP_18_DATA_VALUE INTERMINGLING
											,CYCC_TOP_49_DATA_VALUE HEATSET
											,CYCC_TOP_17_DATA_VALUE CROSS_SECTION
											,CMY_LUSTURE,CMY_PRODUCTION
											,CYL_SYS_ID
											,CYL_SHADE_CODE
											,CYL_SHADE_NAME
											,CYL_LEFT_NO
											,CYL_TYPE
											,CYL_PRODUCT_QUALITY
											,CYL_ITEM_CODE
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
	} else {
		$SqlView = " $SqlView AND CYL_IS_VALID_PRD = 'Y' ";
	}

	//if ($USER_NAME === "1949"){echo $SqlView;	}

	//$isCondValid = " AND CYL_IS_VALID_PRD = 'Y' ";
	//if ($USER_NAME === "1949" ) { echo "CYL_IS_PROJECT_S $CYL_IS_PROJECT_S<br> isCondValid $isCondValid<br>";	}
	if ($FORM_NAME === "CST_T_005"){
		$SqlView = "select 	CMY_NAME
												,CYCC_TOP_13_DATA_VALUE DENIER
												,CYCC_TOP_16_DATA_VALUE FILAMENT
												,CYCC_TOP_18_DATA_VALUE INTERMINGLING
												,CYCC_TOP_49_DATA_VALUE HEATSET
												,CYCC_TOP_17_DATA_VALUE CROSS_SECTION
												,CMY_LUSTURE,CMY_PRODUCTION
												,CYL_SYS_ID
												,CYL_SHADE_CODE
												,CYL_SHADE_NAME
												,CYL_LEFT_NO
												,CYL_TYPE,CYL_ITEM_CODE
											 ,MPD_SEQ_NO
								from (
											SELECT 	CMY_NAME
															,CYCC_TOP_13_DATA_VALUE
															,CYCC_TOP_16_DATA_VALUE
															,CYCC_TOP_18_DATA_VALUE
															,CYCC_TOP_49_DATA_VALUE
															,CYCC_TOP_17_DATA_VALUE
															,CMY_LUSTURE,CMY_PRODUCTION
															,CYL_SYS_ID
															,CYL_SHADE_CODE
															,CYL_SHADE_NAME
															,CYL_LEFT_NO
															,CYL_TYPE
															,CYL_ITEM_CODE
											 FROM mgtapps.cst_mst_yarn y,
														mgtapps.cst_yarn_left l,
														mgtapps.cst_yarn_calculation_cur cc
											WHERE cycc_cyl_sys_id = cyl_sys_id
												AND cyl_cmy_sys_id = cmy_sys_id
												AND cyl_prs_type = cycc_prs_type
												AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt
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
	if ( $CMY_PRODUCTION_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CMY_PRODUCTION = '$CMY_PRODUCTION_S' ";
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

	//if ($USER_NAME==="1949"){ echo "$SqlView <br>";	}

  $SqlCnt = "select count(-1) GET_DATA from ($SqlView)  ";
    //echo "SqlCnt $SqlCnt</br>";

	$StsViewDt =  getData($conn,$SqlCnt);

	$totRows = $StsViewDt;

	$P_ROWS_DISP = 15;
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
</div>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:240%" border="1">
	<tr>
		<td <?php echo getStyle($font,"center","2"); ?>>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			Left No </br>
			Product
		</td>
<?php if ($byShadeMulti === "Y") { ?>
	<td <?php echo getStyle($font,"center"); ?> >
		Shade Name
	</td>
<?php } ?>
		<td <?php echo getStyle($font,"center","2"); ?>>
			<?php lblNo(1,$lblNcClr); ?>No
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"center","10"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
		<td <?php echo getStyle($font,"center","10"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td align="center" style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td <?php echo getStyle($font,"center","12"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
		<td <?php echo getStyle($font,"center","15"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } ?>
		<td <?php echo getStyle($font,"center","12"); ?>>
			<?php lblNo(4,$lblNcClr); ?>Name
		</td>

		<td <?php echo getStyle($font,"center","3"); ?> >
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td>

		<td <?php echo getStyle($font,"center","4"); ?>>
				<b><?php lblNo(6,$lblNcClr); ?>
				<?php echo $lbl_6; ?>
				</b>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(7,$lblNcClr); ?><font color="blue">R.M</font> / Chip Rate
		</td>
		<td <?php echo getStyle($font,"center"); ?> ><?php lblNo(8,$lblNcClr); ?>MB cost
		</td>
		<td <?php echo getStyle($font,"center","3"); ?>>
			<b><?php lblNo('9',$lblNcClr); ?>
			Conv-Mb Cost</br>
			6-7-8</br></b>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(10,$lblNcClr); ?>Eff
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(11,$lblNcClr); ?>Speed
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(12,$lblNcClr); ?>V1<1.5</br>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(13,$lblNcClr); ?>V2=>1.5<3</br>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(14,$lblNcClr); echo "V3=>3<6"; ?>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(15,$lblNcClr); echo "V5<12"; ?>
		</td>

		<td <?php echo getStyle($font,"center","3"); ?>>
			<?php lblNo(16,$lblNcClr); ?>Denier
		</td>
		<td <?php echo getStyle($font,"center","4"); ?>>
			<?php lblNo(17,$lblNcClr); ?>Filament
		</td>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(18,$lblNcClr); ?>Intermingling
		</td>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(19,$lblNcClr); ?>Heatset
		</td>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(20,$lblNcClr); ?>Cross Section
		</td>
		<td <?php echo getStyle($font,"center","3"); ?>>
			<?php lblNo(21,$lblNcClr); ?>Lusture
		</td>
		<td <?php echo getStyle($font,"center","20"); ?>>
			<?php lblNo(22,$lblNcClr); ?>MB Name
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(23,$lblNcClr); ?>Chip
		</td>
		<td <?php echo getStyle($font,"center","10"); ?> >
			<?php lblNo(24,$lblNcClr); ?>Packing type
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(25,$lblNcClr); ?>Bobbin weight
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(26,$lblNcClr); ?>no of Bobbins
		</td>

		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(27,$lblNcClr); ?>Dozing
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(28,$lblNcClr); ?>MB Rate
		</td>
		<td <?php echo getStyle($font,"center"); ?> ><?php lblNo(29,$lblNcClr); ?>Change</br>
			Over Loss
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(30,$lblNcClr); ?>Quality Loss
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(31,$lblNcClr); ?>Intermigle Cost
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(32,$lblNcClr); ?>Fixed Cost
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(33,$lblNcClr); ?>Del-Pack cost
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(34,$lblNcClr); ?>Final Ex</br>
			Factory</br>
			Cost
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(35,$lblNcClr); ?>Fowarding
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(36,$lblNcClr); ?>DTY Prod
		</td>
	</tr>
<?php

	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";

	//if ($USER_NAME === "1949") {echo "$SqlView</br>";	}
  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);
	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
		//if ($USER_NAME === "1949") {echo "Cek CYL Sys ID ".$rowRsView['CYL_SYS_ID']."</br>";}
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

		//cek is Dt RM 55
		$SqlData = "SELECT 'Y' GET_DATA FROM cst_lvl_left_prod
								WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND rownum=1 ";
		$isDt_RM55 		=  getData($conn,$SqlData);
		$TotPoy_Rm55 	= 0;
		$isMulti_RM 	= "";
		//if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55 ".$rowRsView['CYL_SYS_ID']."<br>";}
		if ($isDt_RM55==="Y"){
				$SqlData =  "
										SELECT count(-1) GET_DATA FROM cst_lvl_left_prod
						        WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND CLLP_TYPE = 'POY'
										";
				$TotPoy_Rm55 =  getData($conn,$SqlData);
				//if ($USER_NAME==="1949"){echo "TotPoy_Rm55 $TotPoy_Rm55 ".$rowRsView['CYL_SYS_ID']."<br>";}
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
					//if ($USER_NAME==="1949"){echo "TotPoy_Rm55 > 1 <br>";}
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
					//if ($USER_NAME==="1949"){ echo "$SqlData<br>isMulti_RM $isMulti_RM<br>"; }
					//if ($USER_NAME==="1949"){echo "isMulti_RM $TotPoy_Rm55 ".$rowRsView['CYL_SYS_ID']."<br>";}
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
						$rsMbDt = oci_parse($conn,$SqlMbDt);
						oci_execute ($rsMbDt);
						$DtMb = "";
						while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
							$DtRm = $rowMbDt['CYC_DATA_VALUE'];
							//echo "DtMb $DtMb";
						}
						//if ($USER_NAME==="1949") {echo "$SqlMbDt<br>DtRm $DtRm<br>";}
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
							//if ($USER_NAME==="1949") {echo "isMultiRM_2 $isMultiRM_2<br>";}
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

		//if ($USER_NAME==="1949") {echo "isDt_RM55 $isDt_RM55<br>";}
		//if ($USER_NAME==="1949") {echo "valDt27 1 $valDt27<br>";}
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
				//if ($USER_NAME==="1949") {echo "$SqlMbDt <br>";}
				$rsMbDt = oci_parse($conn,$SqlMbDt);
				oci_execute ($rsMbDt);
				$DtMb = "";
				while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
					$DtRm = $rowMbDt['CYC_DATA_VALUE'];
					//echo "DtMb $DtMb";
				}
				//if ($USER_NAME==="1949") {echo "DtRm 1 $DtRm<br>";}
			}
		else if ($rowRsView['CYL_TYPE']==="SUPERBA"){
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
				//if ($USER_NAME==="1949"){ echo "cek1 $DtRm ";}
			} else {
				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
				//if ($USER_NAME==="1949"){echo $sqlDtRm;}
				$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
			}
			//if ($USER_NAME==="1949") {echo "DtRm 2 $DtRm<br>";}
		}
	}

	$valDt27 = $DtRm;
	if ($rowRsView['CYL_TYPE']==="ACY"){
		$valDt27=0;
		//if ($USER_NAME==="1949") {echo "DtRm 2 $DtRm<br>";}
		//if ($USER_NAME === "1949") {echo $rowRsView['CYL_SYS_ID']."</br>";	}
	}
	//if ($USER_NAME==="1949") {echo "valDt27 2 $valDt27<br>";}
	//$valDt27 = setNumber($conn,"Number",$valDt27,4);

	//val data 24
	$valDt24="";
	$CK_CYL_TYPE=$rowRsView['CYL_TYPE']; $CK_CYL_SHADE_NAME=$rowRsView['CYL_SHADE_NAME']; $CK_CYL_SYS_ID=$rowRsView['CYL_SYS_ID'];
	$CK_FGETCHIPRATE="";
	if (!empty($rowRsView['FGETCHIPRATE'])){
		$CK_FGETCHIPRATE=$rowRsView['FGETCHIPRATE'];
	}
	include("COSTING_CHIP_RATE.php");
	// //if ($USER_NAME==="1949"){echo "CYL_TYPE ".$rowRsView['CYL_TYPE']." CYL_SHADE_NAME ".substr($rowRsView['CYL_SHADE_NAME'],0,8); }
	// $isMelangePrs="";
	// if ($rowRsView['CYL_TYPE']==="MELANGE"){ $isMelangePrs="Y";	}
	// if ($rowRsView['CYL_TYPE']==="MEEREBAH" && substr($rowRsView['CYL_SHADE_NAME'],0,8)==="MELANGE"){ $isMelangePrs="Y";	}
	//
	// //if ($rowRsView['CYL_TYPE']==="MELANGE"){
	// if ($isMelangePrs==="Y"){
	// 	//if ($USER_NAME==="1949"){ echo "MELANGE<br>";}
	// 	$TotRecDt = 0; $DtMb = "";
	// 	$SqlMbDt =
	// 		" SELECT distinct YC.CYC_DATA_VALUE
	// 			FROM (SELECT rm.*
	// 							FROM mgtapps.cst_yarn_left yl,
	// 									 mgtapps.cst_yarn_calculation yc,
	// 									 mgtapps.cst_yarn_rm_hdr rh,
	// 									 mgtapps.cst_yarn_rm_multi rm
	// 						 WHERE     yl.CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
	// 									 AND YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
	// 									 AND YC.CYC_TOP_NO = 55
	// 									 AND YC.CYC_SYS_ID = RH.CYRH_CYC_SYS_ID
	// 									 AND RH.CYRH_SYS_ID = RM.CYRM_CYRH_SYS_ID) dt
	// 					 ,mgtapps.cst_yarn_left yl
	// 					 ,mgtapps.cst_yarn_calculation yc
	// 		where CYL_SYS_ID = CYRM_CYL_SYS_ID
	// 		and YL.CYL_SYS_ID = YC.CYC_CYL_SYS_ID
	// 		and YC.CYC_TOP_NO = 55 ";
	//
	// 		//if ($USER_NAME==="1949"){ echo "SqlMbDt $SqlMbDt";}
	// 		$rsMbDt = oci_parse($conn,$SqlMbDt);
	// 		oci_execute ($rsMbDt);
	// 		while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
	// 			$DtMb = "";
	// 			if (!empty($rowMbDt['CYC_DATA_VALUE'])){
	// 					$TotRecDt++;
	// 					$DtMb = $rowMbDt['CYC_DATA_VALUE'];
	// 			}
	// 			/*if ($TotRecDt>1){
	// 					$DtMb = "</br>$DtMb";
	// 			}*/
	// 			$valDt24 = $DtMb;
	// 		}
	//
	// 		if ($valDt24===""){
	// 			$SqlMbDt =
	// 				"
	// 				SELECT sum(CYC_DATA_VALUE)/count(-1) GET_DATA
  // 				FROM mgtapps.CST_LVL_LEFT_PROD a, mgtapps.CST_YARN_CALCULATION b
 	// 				WHERE     CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
  //      		AND a.CLLP_CYL_SYS_ID = b.CYC_CYL_SYS_ID
  //      		AND CLLP_TYPE = 'POY'
  //      		and cyc_top_no = 55
	// 				";
	//
	// 			//echo "second check<br> $SqlMbDt <br>";
	// 			$valDt24 =  getDataUser($conn,$SqlMbDt,$USER_NAME);
	// 		}
	// 	} else if ($rowRsView['CYL_TYPE']==="PTY BO"){
	// 		//if ($USER_NAME==="1949"){ echo "PTY BO"; }
	// 		$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
	// 								where A.CYC_CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
	// 								and A.CYC_TOP_NO = 55";
	// 		$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
	// 	} else if ($isUseRmBo==="Y"){
	// 		//if ($USER_NAME==="1949"){ echo "isUseRmBo"; }
	// 		$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
	// 												'".$rowRsView['CYL_SYS_ID']."'
	// 												,$deepMaterial
	// 												,2
	// 												) GET_DATA
	// 								 from dual";
	// 		$CylSysId_UseBo =getDataUser($conn,$sqlGetDt,$USER_NAME);
	//
	// 		$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
	// 								where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
	// 								and A.CYC_TOP_NO = 55";
	// 		$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
	// 	}else{
	// 		//if ($USER_NAME==="1949"){ echo "Cek Proses<br>"; }
	// 		//cek RM PTY BO
	// 		$sqlDtRm= "select MGTAPPS.pkg_yarn_calculation.fGetRM_PTY_BO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
	// 		$RM_PTY_BO = getDataUser($conn,$sqlDtRm,$USER_NAME);
	//
	// 		if ($RM_PTY_BO !=="" ){
	// 			if ($USER_NAME==="1949"){ echo "not RM_PTY_BO"; }
	// 			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
	// 									where A.CYC_CYL_SYS_ID = '$RM_PTY_BO'
	// 									and A.CYC_TOP_NO = 55";
	//
	// 			$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
	//
	// 		} else {
	// 			//if ($USER_NAME==="1949"){ echo "Cek Proses 1 <br>"; }
	// 			if ($isDt_RM55==="Y") {
	// 				//if ($USER_NAME==="1949"){ echo "isDt_RM55"; }
	// 				$SqlData = "select cyc_data_value GET_DATA
	// 										from cst_yarn_calculation a
	// 										where cyc_cyl_sys_id in (
	// 										SELECT CLLP_CYL_SYS_ID
	// 										  FROM cst_lvl_left_prod
	// 										 WHERE     CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."'
	// 										       AND cllp_lvl_prod = (SELECT MAX (cllp_lvl_prod)
	// 										                              FROM cst_lvl_left_prod
	// 										                             WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."')
	// 										                             )
	// 										and cyc_top_no = 55	";
	// 				//if ($USER_NAME==="1949"){ echo "$SqlData"; }
	// 				$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
	//
	// 			} else {
	//
	// 				if (!empty($rowRsView['FGETCHIPRATE'])){
	// 					$valDt24 = $rowRsView['FGETCHIPRATE'];
	// 				} else {
	// 					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
	// 					//if ($USER_NAME==="1949"){ echo "masuk sini <br>is Dt 55 $isDt_RM55<br>$sqlDtRm"; }
	// 					$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
	// 					$valDt24 = $DtRm;
	// 				}
	// 			}
	//
	// 		}
	//
	// 	}
	//
	// 	if ($FORM_NAME === "CST_2P1_0003"){
	// 		$SqlGet =  "select MGTAPPS.pkg_yarn_valuation.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual ";
	// 		$valDt24 = getDataUser($conn,$SqlGet,$USER_NAME);
	// 	}
	//
	// 	if ($rowRsView['CYL_TYPE']==="ACY"){
	// 		//if ($USER_NAME==="1949"){ echo "ACY"; }
	// 		$sqlDtRm= "select cyc_data_value GET_DATA
	// 							from cst_yarn_calculation a
	// 									 ,cst_yarn_rm_hdr b
	// 							where cyc_cyl_sys_id = ".$rowRsView['CYL_SYS_ID']."
	// 							and cyc_top_no = 55
	// 							and A.CYC_FORMULA_TYPE = 'Raw_Material'
	// 							and B.CYRH_TYPE = 'Multi Yarn'
	// 							and A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
	// 							";
	// 		//if ($USER_NAME==="1949"){ echo "masuk sini <br>ACY $isDt_RM55<br>$sqlDtRm"; }
	// 		$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
	// 		$valDt24 = $DtRm;
	// 	} else {
	// 		//if ($USER_NAME==="1949"){ echo "selain ACY"; }
	// 		$sqlDtRm= "	select pkg_first_rm_from_store.fCkStatus('".$rowRsView['CYL_SYS_ID']."') GET_DATA
	// 								from dual";
	// 		$ckDtStr =  getDataUser($conn,$sqlDtRm,$USER_NAME);
	//
	// 		if ($ckDtStr==="Y"){
	// 			$sqlDtRm= "select pkg_first_rm_from_store.fGetRMVal('".$rowRsView['CYL_SYS_ID']."') GET_DATA
	// 								 from dual";
	// 			$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
	// 			$valDt24 = $DtRm;
	// 		}
	//
	// 	}

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
		<td <?php echo getStyle($font,"center"); ?>>
		<div class="tooltip">
			<!-- Detail -->
    	<button style="font-size:20px" onclick="<?php echo $btnDtl;  ?>">
				<i class="fa fa-file-pdf-o" data-toggle="tooltip" data-placement="top" title="Detail Product"></i>
			</button>
		<!-- Detail -->
    	<button style="font-size:20px" onclick="<?php echo $btnSmltn;  ?>"
    			data-toggle="tooltip" data-placement="top" title="Simulation Product"
    	>
    		<i class="fa fa-calculator"></i></button>
    </div>
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
<?php if ($byShadeMulti === "Y") { ?>
		<td <?php echo getStyle($font,"left"); ?> >
			<?php echo $rowRsView['CYL_SHADE_NAME']; ?>
		</td>
<?php } ?>
		<td <?php echo getStyle($font,"right"); ?> >
			<!-- 1 -->
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"left"); ?>>
			<!-- Customer -->
			<?php
				if ($CUSTOMER_S==="NULL"){
					if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; }
				} else {
					echo $CUSTOMER_S;
				}
			?>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
				if (!empty($rowRsView['CYL_PRODUCT_QUALITY'])){
						echo $rowRsView['CYL_PRODUCT_QUALITY'];
				}
			?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?>>
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<!-- Customer -->
			<?php if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; } ?>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
				if (!empty($rowRsView['CYL_PRODUCT_QUALITY'])){
						echo $rowRsView['CYL_PRODUCT_QUALITY'];
				}
			?>
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?> >
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
				if (!empty($rowRsView['CYL_PRODUCT_QUALITY'])){
						echo $rowRsView['CYL_PRODUCT_QUALITY'];
				}
			?>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } ?>
		<td <?php echo getStyle($font,"left"); ?> >
			<?php if (!empty($rowRsView['CMY_NAME'])){
								echo $rowRsView['CMY_NAME']."<br>".$rowRsView['CYL_ITEM_CODE'];
						}
			?>
		</td><!-- 4 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCNAME'])){ echo $rowRsView['FGET_MCNAME']; } ?>
		</td><!-- 5 -->
		<td align="right"
		 <?php echo "style='font-size: 15px;background-color:$ClrView11'"; ?>
		>
			<b><?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?></b>
		</td><!-- 6 -->
		<td align="center"
			<?php echo "style='font-size: 15px;background-color:$ClrView24'"; ?>
		>
			<b>
				<?php
				echo $valDt24;
				?>
			</b>
		</td><!-- 7 -->
		<td align="right"
			<?php echo "style='font-size: 15px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td><!-- 8 -->
		<td align="right"
				<?php echo "style='font-size: 15px;background-color:$ClrView11A'"; ?>
		>
			<b>
				<?php
					echo setNumber($conn,"Number",$valDt11A,4);
				?>
			</b>
		</td><!-- 9 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
		</td><!-- 10 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
		</td><!-- 11 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V1'])){
				//echo $rowRsView['FGET_V1']."</br>";
				echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);

			}?>
		</td><!-- 12 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);  }?>
		</td><!-- 13 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?>
		</td><!-- 14 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?>
		</td><!-- 15 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['DENIER'])){
					echo $rowRsView['DENIER'];
				}
			?>
		</td><!-- 16 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['FILAMENT'])){
					echo $rowRsView['FILAMENT'];
				}
			?>
		</td><!-- 17 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['INTERMINGLING'])){
					echo $rowRsView['INTERMINGLING'];
				}
			?>
		</td><!-- 18 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['HEATSET'])){
					echo $rowRsView['HEATSET'];
				}
			?>
		</td><!-- 19 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['CROSS_SECTION'])){
					echo $rowRsView['CROSS_SECTION'];
				}
			?>
		</td><!-- 20 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['CMY_LUSTURE'])){
					echo $rowRsView['CMY_LUSTURE'];
				}
			?>
		</td><!-- 21 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
			//$isDt_RM55==="Y" is data RM 55
			//$TotPoy_Rm55=== Tot POY RM
			//$isMulti_RM RM 55 multi`
			//if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55<br>";}
			$MbNmRaw = "";
			echo "<br>";
			if ($isDt_RM55==="Y"){
					//if ($USER_NAME==="1949"){echo "TotPoy_Rm55 $TotPoy_Rm55<br>";}
					if ($TotPoy_Rm55 > 1){
						//if ($USER_NAME==="1949"){echo "isMulti_RM $isMulti_RM<br>";}
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

							//if ($USER_NAME==="1949"){echo "SqlMbDt $SqlMbDt<br>";}

							oci_execute ($rsMbDt);
							$clr = "red";
							while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
								$DtMb = "";
								if (!empty($rowMbDt['CYC_DATA_VALUE'])){
										$TotRecDt++;
										$DtMb = "<font color='$clr'>".$rowMbDt['CYC_DATA_VALUE']."</font>";
								}
								if ($TotRecDt>1){
										$DtMb = "</br>$DtMb";
								}
								if ($DtMb!=="") { echo $DtMb; }
								if ($clr === "red"){
									$clr = "blue";
								}else if ($clr === "blue"){
									$clr = "red";
								}
							}
						} else {
							//if ($USER_NAME==="1949"){echo "cek sini<br>";}
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
							 			$DtMb = "</br>$DtMb";
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

						//echo $MbNmRaw;
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
					$clr = "red";
					while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
						$DtMb = "";
						if (!empty($rowMbDt['CYC_DATA_VALUE'])){
								$TotRecDt++;
								$DtMb = "<font color='$clr'>".$rowMbDt['CYC_DATA_VALUE']."</font>";
						}
						if ($TotRecDt>1){
								$DtMb = "</br>$DtMb";
						}
						if ($DtMb!=="") { echo $DtMb; }
						if ($clr === "red"){
							$clr = "blue";
						}else if ($clr === "blue"){
							$clr = "red";
						}
					}
				} else {
					if (!empty($rowRsView['FGETMBNAME'])){
							echo $rowRsView['FGETMBNAME'];
					} else {
						$sqlMbNmRaw = "select MGTAPPS.pkg_yarn_marketing.fgetmbname_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
						$MbNmRaw =  getData($conn,$sqlMbNmRaw);

						//echo $MbNmRaw;
					}
				}
			}

			if ($rowRsView['CYL_TYPE']==="SUPERBA"){
				$sqlMbNmRaw = " select CYC_DATA_VALUE GET_DATA
												from mgtapps.cst_yarn_calculation
												where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
												and cyc_top_no = 64
											";
				$MbNmRaw =  getData($conn,$sqlMbNmRaw);
			}
			echo $MbNmRaw;
			?>
		</td><!-- 22 -->
		<td <?php echo getStyle($font,"center"); ?>>
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
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
		</td><!-- 24 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];  }?>
		</td><!-- 25 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>
		</td><!-- 26 -->
		<td <?php echo getStyle($font,"right"); ?>>
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
									 $DtMb = "</br>$DtMb";
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
										$DtMb = "</br>$DtMb";
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
					$clr = "red";
					while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
						$DtMb = "";
						if (!empty($rowMbDt['CYC_DATA_VALUE'])){
								$TotRecDt++;
								$DtMb = $rowMbDt['CYC_DATA_VALUE'];
								$DtMb = setNumber($conn,"Number",$DtMb,2);
								$DtMb = "<font color='$clr'>$DtMb</font>";
						}
						if ($TotRecDt>1){
								$DtMb = "</br>$DtMb";
						}
						if ($DtMb!=="") { echo $DtMb; }
						if ($clr === "red"){
							$clr = "blue";
						} else if ($clr === "blue"){
							$clr = "red";
						}
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

			}
		?>
		</td><!-- 27 -->
		<td <?php echo getStyle($font,"right"); ?>>
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
									 $DtMb = "</br>$DtMb";
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
										$DtMb = "</br>$DtMb";
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
					oci_execute ($rsMbDt);$clr="red";
					while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
						$DtMb = "";
						if (!empty($rowMbDt['CYC_DATA_VALUE'])){
								$TotRecDt++;
								$DtMb = $rowMbDt['CYC_DATA_VALUE'];
								$DtMb = setNumber($conn,"Number",$DtMb,3);
								$DtMb = "<font color='$clr'>$DtMb</font>";
						}
						if ($TotRecDt>1){
								$DtMb = "</br>$DtMb";
						}
						if ($DtMb!=="") {
							echo $DtMb;
						}
						if ($clr==="red"){ $clr="blue"; } else if ($clr==="blue"){$clr="red";}
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

			}

			?>
		</td><!-- 28 -->
		<td <?php echo getStyle($font,"right"); ?>>
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
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
		</td><!-- 30 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);
			}?>
		</td><!-- 31 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
				if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }
			?>
		</td><!-- 32 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){
					echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);
				}?>
		</td><!-- 33 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
			}
			?>
		</td><!-- 34 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
				echo $CST_PRODUCT_FOWARDING;
			?>
		</td><!-- 35 -->
		<td <?php echo getStyle($font,"right"); ?>>
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
