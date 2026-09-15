<?php
	$sqlData =
			"SELECT *  FROM mgtapps.cst_mst_yarn y,mgtapps.cst_yarn_left l,mgtapps.cst_yarn_calculation_cur cc
			WHERE cycc_cyl_sys_id = cyl_sys_id AND cyl_cmy_sys_id = cmy_sys_id AND cyl_prs_type = cycc_prs_type ";

	function getWhereCondition ($WHERE_TYPE,$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S){
			$CMCD_NAME_W = "";
			if ($CMCD_NAME_S!==""){
				$CMCD_NAME_W = " and CYL_SYS_ID in (select CYL_SYS_ID from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l
								,mgtapps.CST_MST_CUST_DATA cd where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
								and CMCD_NAME = '$CMCD_NAME_S')";
			}
			$CMY_TYPE_W = "";
			if ($CMY_TYPE_S!==""){
				$CMY_TYPE_W = " and CMY_TYPE='$CMY_TYPE_S' ";
			}
			$CMY_LUSTURE_W = "";
			if ($CMY_LUSTURE_S!==""){
				$CMY_LUSTURE_W = " and CMY_LUSTURE='$CMY_LUSTURE_S' ";
			}
			$CYL_SHADE_NAME_W = "";
			if ($CYL_SHADE_NAME_S!==""){
				$CYL_SHADE_NAME_W = " and CYL_SHADE_NAME='$CYL_SHADE_NAME_S' ";
			}
			$DENIER_W = "";
			if ($DENIER_S!==""){
				$DENIER_W = " and CYCC_TOP_13_DATA_VALUE='$DENIER_S' ";
			}
			$FILAMENT_W = "";
			if ($FILAMENT_S!==""){
				$FILAMENT_W = " and CYCC_TOP_16_DATA_VALUE='$FILAMENT_S' ";
			}
			$INTERMINGLING_W = "";
			if ($INTERMINGLING_S!==""){
				$INTERMINGLING_W = " and CYCC_TOP_18_DATA_VALUE='$INTERMINGLING_S' ";
			}
			$HEATSET_W = "";
			if ($HEATSET_S!==""){
				$HEATSET_W = " and CYCC_TOP_49_DATA_VALUE='$HEATSET_S' ";
			}
			$CROSS_SECTION_W = "";
			if ($CROSS_SECTION_S!==""){
				$CROSS_SECTION_W = " and CYCC_TOP_17_DATA_VALUE='$CROSS_SECTION_S' ";
			}
			$return = "";

			if ($WHERE_TYPE==="CMCD_NAME_S"){
				$return = "$CMY_TYPE_W $CMY_LUSTURE_W  $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $HEATSET_W
							$CROSS_SECTION_W ";
			}
			if ($WHERE_TYPE==="CMY_TYPE_S"){
				$return = "$CMY_LUSTURE_W  $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $HEATSET_W $CROSS_SECTION_W
							$CMCD_NAME_W";
			}
			if ($WHERE_TYPE==="CMY_LUSTURE_S"){
				$return = "$CMY_TYPE_W  $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $HEATSET_W $CROSS_SECTION_W $CMCD_NAME_W";
			}
			if ($WHERE_TYPE==="CYL_SHADE_NAME_S"){
				$return = "$CMY_TYPE_W  $CMY_LUSTURE_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $HEATSET_W $CROSS_SECTION_W $CMCD_NAME_W";
			}
			if ($WHERE_TYPE==="DENIER_S"){
				$return = "$CMY_TYPE_W  $CMY_LUSTURE_W $CYL_SHADE_NAME_W $FILAMENT_W  $INTERMINGLING_W $HEATSET_W $CROSS_SECTION_W
							$CMCD_NAME_W";
			}
			if ($WHERE_TYPE==="FILAMENT_S"){
				$return = " $CMY_TYPE_W  $CMY_LUSTURE_W $CYL_SHADE_NAME_W $DENIER_W $INTERMINGLING_W $HEATSET_W $CROSS_SECTION_W
							$CMCD_NAME_W";
			}
			if ($WHERE_TYPE==="INTERMINGLING_S"){
				$return = "$CMY_TYPE_W  $CMY_LUSTURE_W $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $HEATSET_W $CROSS_SECTION_W $CMCD_NAME_W ";
			}
			if ($WHERE_TYPE==="HEATSET_S"){
				$return = "$CMY_TYPE_W  $CMY_LUSTURE_W $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $CROSS_SECTION_W
							$CMCD_NAME_W		";
			}
			if ($WHERE_TYPE==="CROSS_SECTION_S"){
				$return = "$CMY_TYPE_W  $CMY_LUSTURE_W $CYL_SHADE_NAME_W $DENIER_W $FILAMENT_W $INTERMINGLING_W $HEATSET_W $CMCD_NAME_W";
			}

			return $return;
		}

?>
