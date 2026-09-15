<?php	
//echo "test $CMY_TYPE_S</br>";

if ($CMCD_NAME_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('CMCD_NAME_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	//echo "vWhere $vWhere </br>";
	$sqlSlct = " select distinct CMCD_NAME
				 from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
 				 where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
 				 and CYL_SYS_ID in (select CYL_SYS_ID from ($sqlData) where 1=1 $vWhere ) ";
	//echo "CMCD_NAME_S sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($CMCD_NAME_DT===""){
			$CMCD_NAME_DT = $rowRsSlct['CMCD_NAME'];
		} else {
			$CMCD_NAME_DT = $CMCD_NAME_DT.";".$rowRsSlct['CMCD_NAME'];
		}
	}
}
if ($CMY_TYPE_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('CMY_TYPE_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	//echo "vWhere $vWhere </br>";
	$sqlSlct = "select distinct CYL_TYPE CMY_TYPE from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id  and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYL_TYPE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($CMY_TYPE_DT===""){
			$CMY_TYPE_DT = $rowRsSlct['CMY_TYPE'];
		} else {
			$CMY_TYPE_DT = $CMY_TYPE_DT.";".$rowRsSlct['CMY_TYPE'];
		}
	}
}
if ($CMY_LUSTURE_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('CMY_LUSTURE_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CMY_LUSTURE from ($sqlData) d  where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CMY_LUSTURE";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($CMY_LUSTURE_DT===""){
			$CMY_LUSTURE_DT = $rowRsSlct['CMY_LUSTURE'];
		} else {
			$CMY_LUSTURE_DT = $CMY_LUSTURE_DT.";".$rowRsSlct['CMY_LUSTURE'];
		}
	}
}
if ($CYL_SHADE_NAME_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('CYL_SHADE_NAME_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYL_SHADE_NAME ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($CYL_SHADE_NAME_DT===""){
			$CYL_SHADE_NAME_DT = $rowRsSlct['CYL_SHADE_NAME'];
		} else {
			$CYL_SHADE_NAME_DT = $CYL_SHADE_NAME_DT.";".$rowRsSlct['CYL_SHADE_NAME'];
		}
	}
}
if ($DENIER_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('DENIER_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID $vWhere
				order by CYCC_TOP_13_DATA_VALUE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($DENIER_DT===""){
			$DENIER_DT = $rowRsSlct['DENIER'];
		} else {
			$DENIER_DT = $DENIER_DT.";".$rowRsSlct['DENIER'];
		}
	}
}
if ($FILAMENT_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('FILAMENT_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYCC_TOP_16_DATA_VALUE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($FILAMENT_DT===""){
			$FILAMENT_DT = $rowRsSlct['FILAMENT'];
		} else {
			$FILAMENT_DT = $FILAMENT_DT.";".$rowRsSlct['FILAMENT'];
		}
	}
	//echo "FILAMENT_DT $FILAMENT_DT</br>";
}
if ($INTERMINGLING_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('INTERMINGLING_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYCC_TOP_18_DATA_VALUE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($INTERMINGLING_DT===""){
			$INTERMINGLING_DT = $rowRsSlct['INTERMINGLING'];
		} else {
			$INTERMINGLING_DT = $INTERMINGLING_DT.";".$rowRsSlct['INTERMINGLING'];
		}
	}
	//echo "INTERMINGLING_DT $INTERMINGLING_DT</br>";
}
if ($HEATSET_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('HEATSET_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYCC_TOP_49_DATA_VALUE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($HEATSET_DT===""){
			$HEATSET_DT = $rowRsSlct['HEATSET'];
		} else {
			$HEATSET_DT = $HEATSET_DT.";".$rowRsSlct['HEATSET'];
		}
	}
	//echo "HEATSET_DT $HEATSET_DT</br>";
}
if ($CROSS_SECTION_S===""){
	$vWhere = "";
	$vWhere = getWhereCondition ('CROSS_SECTION_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
	$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData) d where cycc_cyl_sys_id=cyl_sys_id and CYL_CMY_SYS_ID = CMY_SYS_ID
				$vWhere order by CYCC_TOP_17_DATA_VALUE ";
	//echo "sqlSlct $sqlSlct</br>";
	$rsSlct = oci_parse($conn,$sqlSlct);
	oci_execute ($rsSlct);
	while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
		if ($CROSS_SECTION_DT===""){
			$CROSS_SECTION_DT = $rowRsSlct['CROSS_SECTION'];
		} else {
			$CROSS_SECTION_DT = $CROSS_SECTION_DT.";".$rowRsSlct['CROSS_SECTION'];
		}
	}
	//echo "HEATSET_DT $HEATSET_DT</br>";
}
?>
