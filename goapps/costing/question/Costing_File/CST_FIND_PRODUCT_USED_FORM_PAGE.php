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
											,CYL_ITEM_CODE
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
	if ($USER_NAME === "1949"){
			//echo $SqlView;
	}
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
		 //if ($USER_NAME === "1949" ) {	echo "$SqlView</br>"; }
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
<div class="main" style="overflow-x:auto;" align="center">
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
