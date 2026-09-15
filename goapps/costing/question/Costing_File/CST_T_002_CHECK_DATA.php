<?php

	if ($STSPRS==="CMCD_NAME")	{

		$vWhere = getWhereCondition ('CMCD_NAME_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);

		$sqlGetData = "SELECT 'Y' GET_DATA  FROM mgtapps.cst_yarn_left_cust lc,mgtapps.cst_yarn_left l,mgtapps.cst_mst_cust_data cd,mgtapps.cst_mst_yarn y
						,mgtapps.cst_yarn_calculation_cur cc
		 				WHERE lc.cylc_cyl_sys_id = l.cyl_sys_id AND cycc_cyl_sys_id = l.cyl_sys_id and CYL_PRS_TYPE = CYCC_PRS_TYPE
		 				AND lc.cylc_cmcd_sys_id = cmcd_sys_id AND CMY_SYS_ID = CYL_CMY_SYS_ID and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
		 				and CMCD_NAME = trim('$CMCD_NAME_S') $vWhere
		 				and rownum=1 ";
		$GET_DATA = getData($conn,$sqlGetData);
		//echo "cek $STSPRS : $GET_DATA : sqlGetData $sqlGetData</br>";
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "CMCD_NAME";
			$ERR_MSG = "CUSTOMER ".strtoupper($CMCD_NAME_S)." not found";
			$CMY_TYPE_S = "";
		}
	}
	if ($STSPRS==="CMY_TYPE")	{
		$vWhere = getWhereCondition ('CMY_TYPE_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);

		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CMY_TYPE = trim('$CMY_TYPE_S') $vWhere and rownum=1 ";
		//echo "cek $STSPRS : $GET_DATA : sqlGetData $sqlGetData</br>";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "CMY_TYPE";
			$ERR_MSG = "PRODUCT ".strtoupper($CMY_TYPE_S)." not found";
			$CMY_TYPE_S = "";
		}
	}
	if ($STSPRS==="CMY_LUSTURE")	{
		$vWhere = getWhereCondition ('CMY_LUSTURE_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CMY_LUSTURE = trim('$CMY_LUSTURE_S') $vWhere and rownum=1 ";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "CMY_LUSTURE";
			$ERR_MSG = "LUSTURE ".strtoupper($CMY_LUSTURE_S)." not found";
			$CMY_LUSTURE_S = "";
		}
	}
	if ($STSPRS==="CYL_SHADE_NAME")	{
		$vWhere = getWhereCondition ('CYL_SHADE_NAME_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYL_SHADE_NAME = trim('$CYL_SHADE_NAME_S') $vWhere and rownum=1 ";
		//ECHO "$STSPRS check data $sqlGetData";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "CYL_SHADE_NAME";
			$ERR_MSG = "COLOUR ".strtoupper($CYL_SHADE_NAME_S)." not found";
			$CYL_SHADE_NAME_S = "";
		}
	}
	if ($STSPRS==="DENIER")	{
		$vWhere = getWhereCondition ('DENIER_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYCC_TOP_13_DATA_VALUE = '$DENIER_S' $vWhere and rownum=1 ";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "DENIER";
			$ERR_MSG = "DENIER ".strtoupper($DENIER_S)." not found";
			$DENIER_S = "";
		}
	}
	if ($STSPRS==="FILAMENT")	{
		$vWhere = getWhereCondition ('FILAMENT_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' $vWhere and rownum=1 ";
		//echo "$sqlGetData </br>";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "FILAMENT";
			$ERR_MSG = "FILAMENT ".strtoupper($FILAMENT_S)." not found";
			$FILAMENT_S = "";
		}
	}
	if ($STSPRS==="INTERMINGLING")	{
		$vWhere = getWhereCondition ('INTERMINGLING_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' $vWhere and rownum=1 ";
		//echo "$sqlGetData </br>";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "INTERMINGLING";
			$ERR_MSG = "INTERMINGLING ".strtoupper($INTERMINGLING_S)." not found";
			$INTERMINGLING_S = "";
		}
	}
	if ($STSPRS==="HEATSET")	{
		$vWhere = getWhereCondition ('HEATSET_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' $vWhere and rownum=1 ";
		//echo "$sqlGetData </br>";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "HEATSET";
			$ERR_MSG = "HEATSET ".strtoupper($HEATSET_S)." not found";
			$HEATSET_S = "";
		}
	}
	if ($STSPRS==="CROSS_SECTION")	{
		$vWhere = getWhereCondition ('CROSS_SECTION_S',$CMY_TYPE_S,$CMY_LUSTURE_S,$CYL_SHADE_NAME_S,$DENIER_S
								,$FILAMENT_S,$INTERMINGLING_S,$HEATSET_S,$CROSS_SECTION_S,$CMCD_NAME_S);
		$sqlGetData = "select 'Y' GET_DATA from ($sqlData) where CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' $vWhere and rownum=1 ";
		//echo "$sqlGetData </br>";
		$GET_DATA = getData($conn,$sqlGetData);
		if ($GET_DATA===""){
			$SET_FOCUS_INPUT = "CROSS_SECTION";
			$ERR_MSG = "CROSS SECTION ".strtoupper($CROSS_SECTION_S)." not found";
			$CROSS_SECTION_S = "";
		}
	}
?>
