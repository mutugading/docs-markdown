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
											,CYL_TYPE,CYL_ITEM_CODE
											,CYL_PRODUCT_QUALITY
											,CYL_SUPERBA_POY
             FROM mgtapps.cst_mst_yarn y,
                  mgtapps.cst_yarn_left l,
                  mgtapps.cst_yarn_calculation_cur cc
            WHERE cycc_cyl_sys_id = cyl_sys_id
              AND cyl_cmy_sys_id = cmy_sys_id
              AND cyl_prs_type = cycc_prs_type
              AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt ";
	//echo $FORM_NAME."</br>";
	$isCondValid = "";
	if ($CYL_IS_PROJECT_S==="Y"){
		$SqlView = " $SqlView and nvl(CYL_IS_PROJECT,'N') = nvl('$CYL_IS_PROJECT_S','N') ";
	} else {
		$isCondValid = " AND CYL_IS_VALID_PRD = 'Y' ";
		$SqlView = " $SqlView and CYL_IS_VALID_PRD = 'Y' ";
	}
	//if ($USER_NAME === "1949"){echo $SqlView;}
	if ($USER_NAME === "1949" ) {
		$SqlGet = "
								select null GET_DATA
								from mst_param_data t where t.MPD_MPDK_KEY = 'USER ADMIN COSTING'
								and mpd_value = upper('$USER_NAME')
							";
		//echo $SqlGet;
		$isCondValid = getData($conn,$SqlGet);
	}
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
		// if ($USER_NAME === "1949" ) {
		// 	echo "$SqlView</br>";
		// }
	}
  $SqlViewWhere = "";
	if ( $CYL_TYPE_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
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
	if ( $CYL_PRODUCT_QUALITY_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CYL_PRODUCT_QUALITY = '$CYL_PRODUCT_QUALITY_S' ";
  }
  if ( $CMY_LUSTURE_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CMY_LUSTURE = '$CMY_LUSTURE_S' ";
  }
	if ( $CMY_PRODUCTION_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CMY_PRODUCTION = '$CMY_PRODUCTION_S' ";
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
  if ( $CUSTOMER_S !=="NULL"){
  	$SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
					where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
					and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
  }
	if ( $CYL_LEFT_NO_S !==""){
		$SqlViewWhere = "$SqlViewWhere and CYL_LEFT_NO = '$CYL_LEFT_NO_S' ";
	}
	if ($FORM_NAME === "CST_T_005"){
			$SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere ORDER BY NVL (MPD_SEQ_NO, 999999) ) d ";
	} else {
			$SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
	}
	//echo $SqlView;
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
	$font="12";
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
		<td align="center">
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			Left No </br>
			Product
		</td>
<?php
	if ($byShadeMulti === "Y") {
?>
	<td align="center" >
		Shade Name
	</td>
<?php
	}
?>
		<td <?php echo getStyle($font,"center","1"); ?> >
			<?php lblNo(1,$lblNcClr); ?>No
		</td><!--1-->
<?php
	if ($byType === "Y") {
?>
		<td <?php echo getStyle($font,"center","8"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td><!--2-->
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
		<td <?php echo getStyle($font,"center","8"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td><!--3-->
<?php
	} else if ($byShade === "Y") {
?>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Type
		</td><!--2-->
		<td <?php echo getStyle($font,"center","10"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td><!--3-->
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
<?php
	} else if ($byCust === "Y") {
?>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Type
		</td><!--2-->
		<td <?php echo getStyle($font,"center"); ?> >
			Product Name
		</td>
		<td <?php echo getStyle($font,"center","10"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td><!--3-->
<?php
		}
?>
		<td <?php echo getStyle($font,"center","10"); ?>>
			<?php lblNo(4,$lblNcClr); ?>Name
		</td><!--4-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td><!--5-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(10,$lblNcClr); ?>Eff
		</td><!--10-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(11,$lblNcClr); ?>Speed
		</td><!--11-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(23,$lblNcClr); ?>Chip
		</td><!--23-->
		<td <?php echo getStyle($font,"center","3"); ?>>
				<b><?php lblNo(6,$lblNcClr); ?>
				<?php echo $lbl_6; ?>
				</b>
		</td><!--6-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(7,$lblNcClr); ?><font color="blue">R.M</font> / Chip Rate
		</td><!--7-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(8,$lblNcClr); ?>SP cost
		</td><!--8-->
		<td <?php echo getStyle($font,"center","3"); ?>>
			<b><?php lblNo('9',$lblNcClr); ?>
			Conv-SP Cost</br>
			6-7-8</br></b>
		</td><!--9-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(12,$lblNcClr); ?>V1<1.5</br>
		</td><!--12-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(13,$lblNcClr); ?>V2=>1.5<3</br>
		</td><!--13-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(14,$lblNcClr); ?><?php echo "V3=>3<6"; ?>
		</td><!--14-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(15,$lblNcClr); ?><?php echo "V5<12"; ?>
		</td><!--15-->
		<td <?php echo getStyle($font,"center","3"); ?>>
			<?php lblNo(16,$lblNcClr); ?>Denier
		</td><!--16-->
		<td <?php echo getStyle($font,"center","4"); ?>>
			<?php lblNo(17,$lblNcClr); ?>Filament
		</td><!--17-->
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(18,$lblNcClr); ?>Intermingling
		</td><!--18-->
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(19,$lblNcClr); ?>Heatset
		</td><!--19-->
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(20,$lblNcClr); ?>Cross Section
		</td><!--20-->
		<td <?php echo getStyle($font,"center","3"); ?>>
			<?php lblNo(21,$lblNcClr); ?>Lusture
		</td><!--21-->
		<td <?php echo getStyle($font,"center","15"); ?>>
			<?php lblNo(22,$lblNcClr); ?>SP Name
		</td><!--22-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(27,$lblNcClr); ?>Dozing
		</td><!--27-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(28,$lblNcClr); ?>MB Rate
		</td><!--28-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(24,$lblNcClr); ?>Packing type
		</td><!--24-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(25,$lblNcClr); ?>Bobbin weight
		</td><!--25-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(26,$lblNcClr); ?>no of Bobbins
		</td><!--26-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(33,$lblNcClr); ?>Del-Pack cost
		</td><!--33-->
		<td <?php echo getStyle($font,"center"); ?> ><?php lblNo(29,$lblNcClr); ?>Change</br>
			Over Loss
		</td><!--29-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(30,$lblNcClr); ?>Quality Loss
		</td><!--30-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(31,$lblNcClr); ?>Intermigle Cost
		</td><!--31-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(32,$lblNcClr); ?>Fixed Cost
		</td><!--32-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(34,$lblNcClr); ?>Final Ex</br>
			Factory</br>
			Cost
		</td><!--34-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(35,$lblNcClr); ?>Fowarding
		</td><!--35-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(36,$lblNcClr); ?>DTY Prod
		</td><!--36-->
	</tr>
<?php
	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";

	//if ($USER_NAME === "1949") {echo "$SqlView</br>";}
  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);
	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$CYL_SUPERBA_POY=$rowRsView['CYL_SUPERBA_POY'];
		//cek is Dt RM 55
		$SqlData = "SELECT 'Y' GET_DATA FROM cst_lvl_left_prod
								WHERE CLLP_CYL_SYS_ID_REFF = '".$rowRsView['CYL_SYS_ID']."' AND rownum=1 ";
		$isDt_RM55 		=  getData($conn,$SqlData);

		$btnDtl = "Process('DETAIL ".$rowRsView['CYL_SYS_ID']."')";
		$btnSmltn = "Process('SIMULATION ".$rowRsView['CYL_SYS_ID']."')";
		$btnProdSeq = "Process('PRODUCT SEQUENCE ".$rowRsView['CYL_SYS_ID']."')";
		//cek Prosuct Use Bo
		$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
		//echo $slctUseRm;
		$isUseRmBo = getData($conn,$slctUseRm);
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

		//if($USER_NAME==="1949"){ echo "isDt_RM55 $isDt_RM55<br>"; };
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
			//if($USER_NAME==="1949"){ echo "totPOY $totPOY<br>"; };
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
				//if($USER_NAME==="1949"){ echo "CLLP_CYL_SYS_ID $CLLP_CYL_SYS_ID<br>"; };
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
			//if($USER_NAME==="1949"){ echo $SqlMbDt."<br>"; };
			$DtRm =  getData($conn,$SqlMbDt);
			$valDt27 = $DtRm;
		}

		if ($rowRsView['CYL_TYPE']==="SUPERBA" || $rowRsView['CYL_SUPERBA_POY'] ==="N" ){
				$SqlMbDt = " select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation
										  where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."' and cyc_top_no = 73 ";
				$DtRm =  getData($conn,$SqlMbDt);
				$valDt27 = $DtRm;
		}
		//$valDt27 = setNumber($conn,"Number",$valDt27,4);

		//val data 24
		$valDt24 = "";

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
		                         AND cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
		                ORDER BY cllp_lvl_prod) a,
		               cst_yarn_left b
		         WHERE A.CLLP_CYL_SYS_ID = b.cyl_sys_id AND b.cyl_type = 'POY') a
		         ,cst_yarn_calculation b
		where CLLP_CYL_SYS_ID = CYC_CYL_SYS_ID and cyc_top_no = 20 )
		";
		$totRm_Poy =  getDataUser($conn,$SqlCk,$USER_NAME);
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
		                               AND cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
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
		                               AND cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
		                      ORDER BY cllp_lvl_prod) a,
		                     cst_yarn_left b
		               WHERE A.CLLP_CYL_SYS_ID = b.cyl_sys_id AND b.cyl_type = 'POY') a,
		             cst_yarn_calculation b
		       WHERE CLLP_CYL_SYS_ID = CYC_CYL_SYS_ID AND cyc_top_no = 55
		   ) rm_val
		   where rm.CLLP_LEFT_NO=rm_val.CLLP_LEFT_NO
		   ";
		   $valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
		} else {

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
			} else if ($rowRsView['CYL_TYPE']==="PTY BO"){
				$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
										where A.CYC_CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
										and A.CYC_TOP_NO = 55";
				$valDt24 =  getData($conn,$SqlData);
			} else if ($isUseRmBo==="Y"){

				$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
														'".$rowRsView['CYL_SYS_ID']."'
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
				//if($USER_NAME==="1949"){echo "check disini <br>";}
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
					//if($USER_NAME==="1949"){echo "totPOY $totPOY<br>";}
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
						//if($USER_NAME==="1949"){ echo "CLLP_CYL_SYS_ID $CLLP_CYL_SYS_ID<br>"; };
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
//$sqlTotDt = "select $valDt11 - $valDt24 - $valDt27 GET_DATA From dual";echo $sqlTotDt;
$sqlGet= "select nvl(to_number('$valDt11'),0) - nvl(to_number('$valDt24'),0) - nvl(to_number('$valDt27'),0) GET_DATA from dual";
//echo $sqlGet."</br>";
$valDt11A =  getData($conn,$sqlGet);
//echo "valDt11 $valDt11 - valDt24 $valDt24 - valDt27 $valDt27";
//$valDt11A = $valDt11 - $valDt24 - $valDt27;
//val data 11A

	?>
	<tr>
		<td <?php echo getStyle($font,"center","2"); ?>>
			<div class="tooltip">
				<!-- Detail -->
	    	<button style="font-size:20px" onclick="<?php echo $btnDtl;  ?>">
					<i class="fa fa-file-pdf-o" data-toggle="tooltip" data-placement="top" title="Detail Product"></i>
				</button>
				<!-- Simulation -->
	    	<button style="font-size:20px" onclick="<?php echo $btnSmltn;  ?>"
	    			data-toggle="tooltip" data-placement="top" title="Simulation Product"
	    	>
	    		<i class="fa fa-calculator"></i>
				</button>
				<!-- Product Sequence -->
				<button style="font-size:20px" onclick="<?php echo $btnProdSeq;  ?>"
	    			data-toggle="tooltip" data-placement="top" title="Product Sequence"
	    	>
					<i class="fa fa-file-text-o"></i>
				</button>
	    </div>
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
<?php if ($byShadeMulti === "Y") { ?>
			<td <?php echo getStyle($font,"center"); ?> >
			<?php echo $rowRsView['CYL_SHADE_NAME']; ?>
			</td>
<?php } ?>
		<td <?php echo getStyle($font,"right"); ?> >
			<!--1-->
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"left"); ?>>
			<!--2 Customer-->
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
			<!--3 Shade Name-->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?>>
			<!--Type-->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<!--Customer-->
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
			<!--Type-->
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
			<!--Shade Name-->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } ?>
		<!--3 Shade Name-->
		<td <?php echo getStyle($font,"left"); ?> >
			<?php if (!empty($rowRsView['CMY_NAME'])){ echo $rowRsView['CMY_NAME']."<br>".$rowRsView['CYL_ITEM_CODE'];} ?>
		</td><!--4 Name-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCNAME'])){ echo $rowRsView['FGET_MCNAME']; } ?>
		</td><!--5-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
		</td><!--10-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
		</td><!--11-->
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
				if (!empty($rowRsView['FGETCHIP'])){
					echo $rowRsView['FGETCHIP'];
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchip_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo $DtRm;
				}
			}
			?>
			<!-- MB Name MGTAPPS.pkg_yarn_marketing.fgetmbname -->
		</td><!--23-->
		<td align="right"
		 <?php echo "style='font-size: 15px;background-color:$ClrView11'"; ?>
		>
			<b><?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?></b>
		</td><!--6-->
		<td align="center"
			<?php echo "style='font-size: 15px;background-color:$ClrView24'"; ?>
		>
			<b>
				<?php
				echo $valDt24;
				?>
			</b>
		</td><!--7-->
		<td align="right"
			<?php echo "style='font-size: 15px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td><!--8-->
		<td align="right"
				<?php echo "style='font-size: 15px;background-color:$ClrView11A'"; ?>
		>
			<b>
				<?php
					echo setNumber($conn,"Number",$valDt11A,4);
				?>
			</b>
		</td><!--9-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V1'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);
			}?>
		</td><!--12-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);  }?>
		</td><!--13-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?>
		</td><!--14-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?>
		</td><!--15-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['DENIER'])){
					echo $rowRsView['DENIER'];
				}
			?>
		</td><!--16-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['FILAMENT'])){
					echo $rowRsView['FILAMENT'];
				}
			?>
		</td><!--17-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['INTERMINGLING'])){
					echo $rowRsView['INTERMINGLING'];
				}
			?>
		</td><!--18-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['HEATSET'])){
					echo $rowRsView['HEATSET'];
				}
			?>
		</td><!--19-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['CROSS_SECTION'])){
					echo $rowRsView['CROSS_SECTION'];
				}
			?>
		</td><!--20-->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php
				if (!empty($rowRsView['CMY_LUSTURE'])){
					echo $rowRsView['CMY_LUSTURE'];
				}
			?>
		</td><!--21-->
		<td <?php echo getStyle($font,"center"); ?>>
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
		</td><!--27-->
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
						echo setNumber($conn,"Number",$DtMb,3);
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
		</td><!--28-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
		</td><!--24-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];  }?>
		</td><!--25 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>
		</td><!--26 -->
		<td <?php echo getStyle($font,"center"); ?>>
			<?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){
					echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);
				}?>
		</td><!--33-->
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
						echo setNumber($conn,"Number",$DtMb,3);
					}
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
			<!-- fgetMbCost  MGTAPPS.pkg_yarn_marketing.fgetMbCost('".$rowRsView['CYL_SYS_ID']."')  -->
		</td><!--29-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
			<!-- fgetCngOvrLst  MGTAPPS.pkg_yarn_marketing.fgetCngOvrLst('".$rowRsView['CYL_SYS_ID']."') -->
		</td><!--30-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);
			}?>
		</td><!--31-->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }?>
		</td><!--32-->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
			}?>
		</td><!--34-->
		<td a<?php echo getStyle($font,"right"); ?>>
			<?php echo $CST_PRODUCT_FOWARDING; ?>
		</td><!--35-->
		<td <?php echo getStyle($font,"right"); ?>>
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
</div>
<?php echo $fotLbl; ?>
