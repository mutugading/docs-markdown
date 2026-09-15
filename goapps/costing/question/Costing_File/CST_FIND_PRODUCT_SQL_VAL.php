<?php

	$ClrView11 = '#CEF6F5';  $ClrView11A = '#81F7D8'; $ClrView24 = '#F6D8CE'; $ClrView27 = '#F6CEEC';

	$fotLbl = "Note : <b><font color='red'>'11A'</font> - Slight difference in conversion cost of same product with different shades is due to different dozing of MB, Change Over Loss and Quality Loss</b>";

	$sqlData =
		"
		SELECT *  FROM mgtapps.cst_mst_yarn y,mgtapps.cst_yarn_left l,mgtapps.cst_yarn_calculation_cur cc
		WHERE cycc_cyl_sys_id = cyl_sys_id AND cyl_cmy_sys_id = cmy_sys_id AND cyl_prs_type = cycc_prs_type
		and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fprsidval
		";

	$sqlSelect = "select 	REC_NO,d.*
					,MGTAPPS.pkg_yarn_valuation.fgetmbname(CYL_SYS_ID) FGETMBNAME
					,MGTAPPS.pkg_yarn_valuation.FGETCHIP(CYL_SYS_ID) FGETCHIP
					,MGTAPPS.pkg_yarn_valuation.FGETCHIPRATE(CYL_SYS_ID) FGETCHIPRATE
					,MGTAPPS.pkg_yarn_valuation.FGETRPDOZ(CYL_SYS_ID) FGETRPDOZ
					,MGTAPPS.pkg_yarn_valuation.FGETMBRATE(CYL_SYS_ID) FGETMBRATE
					,MGTAPPS.pkg_yarn_valuation.FGETMBCOST(CYL_SYS_ID) FGETMBCOST
					,MGTAPPS.pkg_yarn_valuation.FGETCNGOVRLST(CYL_SYS_ID) FGETCNGOVRLST
					,MGTAPPS.pkg_yarn_valuation.FGETQUALITYLOSS(CYL_SYS_ID) FGETQUALITYLOSS
					,MGTAPPS.pkg_yarn_valuation.fGetFinalExFactoryCost(CYL_SYS_ID) FGETFINALEXFACTORYCOST
					,MGTAPPS.pkg_yarn_valuation.fget_V1(CYL_SYS_ID)+MGTAPPS.pkg_yarn_valuation.getPrdFowarding	FGET_V1
					,MGTAPPS.pkg_yarn_valuation.fget_V2(CYL_SYS_ID)+MGTAPPS.pkg_yarn_valuation.getPrdFowarding  FGET_V2
					,MGTAPPS.pkg_yarn_valuation.fget_V3(CYL_SYS_ID)+MGTAPPS.pkg_yarn_valuation.getPrdFowarding  FGET_V3
					,MGTAPPS.pkg_yarn_valuation.fget_V4(CYL_SYS_ID)+MGTAPPS.pkg_yarn_valuation.getPrdFowarding  FGET_V4
					,MGTAPPS.pkg_yarn_valuation.FGET_V5(CYL_SYS_ID)+MGTAPPS.pkg_yarn_valuation.getPrdFowarding  FGET_V5
					,MGTAPPS.pkg_yarn_valuation.fgetdatacustomer(CYL_SYS_ID) CUSTOMER
					,MGTAPPS.pkg_yarn_valuation.fget_BoxWeight(CYL_SYS_ID) FGET_BOXWEIGHT
					,MGTAPPS.pkg_yarn_valuation.fget_PackingType (CYL_SYS_ID) FGET_PACKINGTYPE
    				,MGTAPPS.pkg_yarn_valuation.fget_NoOfBobbins (CYL_SYS_ID)FGET_NOOFBOBBINS
    				,MGTAPPS.pkg_yarn_valuation.fget_BobbinWeightAX (CYL_SYS_ID) FGET_BOBBINWEIGHTAX
    				,MGTAPPS.pkg_yarn_valuation.fget_DelPackingName (CYL_SYS_ID) FGET_DELPACKINGNAME
    				,MGTAPPS.pkg_yarn_valuation.FGET_DELPACKINGCOST(CYL_SYS_ID) FGET_DELPACKINGCOST
    				,MGTAPPS.pkg_yarn_valuation.fget_IntermigleCost (CYL_SYS_ID) FGET_INTERMIGLECOST
    				,MGTAPPS.pkg_yarn_valuation.fget_FixedCost (CYL_SYS_ID) FGET_FIXEDCOST
    				,MGTAPPS.pkg_yarn_valuation.fget_McName (CYL_SYS_ID)  FGET_MCNAME
    				,MGTAPPS.pkg_yarn_valuation.fget_McEff(CYL_SYS_ID)  FGET_MCEFF
    				,MGTAPPS.pkg_yarn_valuation.fget_McSpeed (CYL_SYS_ID) FGET_MCSPEED
    				,MGTAPPS.pkg_yarn_valuation.fget_DelPackBobinRate (CYL_SYS_ID) FGET_DELPACKBOBINRATE
    				,MGTAPPS.pkg_yarn_valuation.fget_DelPackBoxRate (CYL_SYS_ID) FGET_DELPACKBOXRATE
    			";


	function sqlSlctPrdType()
		{
			return "select distinct CYL_TYPE from mgtapps.cst_yarn_left
					where CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fprsidval
					order by CYL_TYPE";				;
		}

	function sqlSlctShadeCd($CYL_TYPE)
		{
			return "select distinct CYL_SHADE_CODE
						from cst_yarn_left
						where CYL_SHADE_CODE is not null
						and CYL_TYPE = '$CYL_TYPE'
						and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fprsidval
						order by CYL_SHADE_CODE";				;
		}

	function sqlSlctShadeNm($CYL_TYPE)
		{
			return "select distinct CYL_SHADE_NAME
						from mgtapps.cst_yarn_left
						where CYL_SHADE_NAME is not null
						and CYL_TYPE = '$CYL_TYPE'
						and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fprsidval
						order by CYL_SHADE_NAME";				;
		}

	function sqlSlctCust($sqlData,$itemSlct)
		{
			$sql = " $itemSlct from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
			 				 where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
			 				 and CYL_SYS_ID in (select CYL_SYS_ID from ($sqlData))  ";
			return $sql;
		}

	function getShadeCodeName($conn,$CYL_SHADE_CODE_S,$CYL_SHADE_NAME_S)
		{
			$sql = "";
			$sqlWhere = "";
			if ($CYL_SHADE_CODE_S!=="NULL"){
				$sql 		= "CYL_SHADE_NAME";
				$sqlWhere 	= "where CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
			}
			if ($CYL_SHADE_NAME_S!=="NULL"){
				$sql = "CYL_SHADE_CODE";
				$sqlWhere 	= "where CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
			}

			if ($sql !== ""){
				$sql = "select $sql GET_DATA from mgtapps.cst_yarn_left $sqlWhere and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fprsidval ";
			}
			//echo "sql $sql";
			if ($sql !== ""){
				return getData($conn,$sql);
			} else return "";


		}

	function sqlSlctLusture()
		{
			return "select distinct CMY_LUSTURE from mgtapps.cst_mst_yarn order by CMY_LUSTURE";
		}

	function sqlSlctOthrs($conn,$sqlData,$CYL_TYPE_S,$CYL_SHADE_CODE_S,$CYL_SHADE_NAME_S,$TYPE_PARAM){

		$sqlData = "$sqlData and CYL_TYPE = '$CYL_TYPE_S'  ";

		if ($CYL_SHADE_CODE_S!=="NULL"){
			$sqlData = "$sqlData and  CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
		}
		if ($CYL_SHADE_NAME_S!=="NULL"){
			$sqlData = "$sqlData and  CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
		}

		if ($TYPE_PARAM==="DENIER"){
			$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData)";
		}
		if ($TYPE_PARAM==="FILAMENT"){
			$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData)";
		}
		if ($TYPE_PARAM==="INTERMINGLING"){
			$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData)";
		}
		if ($TYPE_PARAM==="HEATSET"){
			$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData)";
		}
		if ($TYPE_PARAM==="CROSS_SECTION"){
			$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData)";
		}
		if ($TYPE_PARAM==="CUSTOMER"){
			//$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData)";
			$sqlSlct = sqlSlctCust($sqlData,"select distinct CMCD_NAME CUSTOMER ");

			//echo "sqlSlct $sqlSlct";
		}
		return $sqlSlct;
		}

	function sqlSlctOthsCust($conn,$sqlData,$CYL_TYPE_S,$CUSTOMER_S,$TYPE_PARAM){

		$sqlData = "$sqlData and CYL_TYPE = '$CYL_TYPE_S'  ";

		$sqlCst = 	sqlSlctCust($sqlData,"SELECT DISTINCT CYL_SYS_ID ")." and upper(CMCD_NAME) like '$CUSTOMER_S' ";

		//echo "$sqlData and CYL_SYS_ID in ($sqlCst) ";die();

		$sqlData = "$sqlData and cyl_sys_id in (select cyl_sys_id from ($sqlCst) )";
		if ($CUSTOMER_S!=="NULL"){
			$sqlData = "$sqlData and  CYL_SYS_ID in ($sqlCst) ";
			//echo $sqlData;die();
		}

		if ($TYPE_PARAM==="DENIER"){
			$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData)";
		}
		if ($TYPE_PARAM==="FILAMENT"){
			$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData)";
		}
		if ($TYPE_PARAM==="INTERMINGLING"){
			$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData)";
		}
		if ($TYPE_PARAM==="HEATSET"){
			$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData)";
		}
		if ($TYPE_PARAM==="CROSS_SECTION"){
			$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData)";
		}
		if ($TYPE_PARAM==="CYL_SHADE_CODE"){
			$sqlSlct = "select distinct CYL_SHADE_CODE from ($sqlData)";
		}
		if ($TYPE_PARAM==="CYL_SHADE_NAME"){
			$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData)";
		}
		return $sqlSlct;
		}
function sqlSlctTypeBase($conn,$sqlData,$CYL_TYPE_S,$TYPE_PARAM,$WHERE_PARAM){
		$sqlData = "$sqlData and cyl_type = '$CYL_TYPE_S'  $WHERE_PARAM ";

		//if ($USER_NAME==="1949"){
		//echo "$sqlData </br>";
		//}

		if ($TYPE_PARAM==="CUSTOMER"){
			$sqlSlct = 	sqlSlctCust($sqlData,"SELECT DISTINCT CMCD_NAME CUSTOMER")." order by CMCD_NAME";
		}
		if ($TYPE_PARAM==="DENIER"){
			$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData) where CYCC_TOP_13_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="FILAMENT"){
			$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData) where CYCC_TOP_16_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="INTERMINGLING"){
			$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData) where CYCC_TOP_18_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="HEATSET"){
			$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData) where CYCC_TOP_49_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CROSS_SECTION"){
			$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData) where CYCC_TOP_17_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CYL_SHADE_CODE"){
			$sqlSlct = "select distinct CYL_SHADE_CODE from ($sqlData) where CYL_SHADE_CODE is not null ";
		}
		if ($TYPE_PARAM==="CYL_SHADE_NAME"){
			$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData) where CYL_SHADE_NAME is not null ";
		}
		if ($TYPE_PARAM==="LUSTURE"){
			$sqlSlct = "select distinct CMY_LUSTURE from mgtapps.cst_mst_yarn where cmy_sys_id in (select cmy_sys_id from ($sqlData)) order by CMY_LUSTURE";
			//$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData) where CYL_SHADE_NAME is not null ";
		}

		return $sqlSlct;
		}

function sqlSlctCustBase($conn,$sqlData,$CUSTOMER_S,$TYPE_PARAM,$WHERE_PARAM){
		$sqlCst = 	sqlSlctCust($sqlData,"SELECT DISTINCT CYL_SYS_ID ")." and upper(CMCD_NAME) like '$CUSTOMER_S' ";
		//echo $sqlCst;die();
		$sqlData = "$sqlData and cyl_sys_id in (select cyl_sys_id from ($sqlCst) )   $WHERE_PARAM ";

		if ($TYPE_PARAM==="CYL_TYPE"){
			$sqlSlct = "select distinct CYL_TYPE from ($sqlData)";
		}
		if ($TYPE_PARAM==="DENIER"){
			$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData) where CYCC_TOP_13_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="FILAMENT"){
			$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData) where CYCC_TOP_16_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="INTERMINGLING"){
			$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData) where CYCC_TOP_18_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="HEATSET"){
			$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData) where CYCC_TOP_49_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CROSS_SECTION"){
			$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData) where CYCC_TOP_17_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CYL_SHADE_CODE"){
			$sqlSlct = "select distinct CYL_SHADE_CODE from ($sqlData) where CYL_SHADE_CODE is not null ";
		}
		if ($TYPE_PARAM==="CYL_SHADE_NAME"){
			$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData) where CYL_SHADE_NAME is not null ";
		}
		if ($TYPE_PARAM==="LUSTURE"){
			$sqlSlct = "select distinct CMY_LUSTURE from mgtapps.cst_mst_yarn where cmy_sys_id in (select cmy_sys_id from ($sqlData)) order by CMY_LUSTURE";
			//$sqlSlct = "select distinct CYL_SHADE_NAME from ($sqlData) where CYL_SHADE_NAME is not null ";
		}
		return $sqlSlct;
		}
function sqlSlctShadeBase($conn,$sqlData,$CYL_SHADE_NAME_S,$TYPE_PARAM,$WHERE_PARAM){
		if (substr($CYL_SHADE_NAME_S,0,1)==="(") {
				$sqlData = "$sqlData and CYL_SHADE_NAME in $CYL_SHADE_NAME_S  ";
		} else {
				$sqlData = "$sqlData and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S'  ";
		}

		$sqlData = "$sqlData $WHERE_PARAM ";

		if ($TYPE_PARAM==="CUSTOMER"){
			$sqlSlct = 	sqlSlctCust($sqlData,"SELECT DISTINCT CMCD_NAME CUSTOMER")." order by CMCD_NAME";
		}
		if ($TYPE_PARAM==="DENIER"){
			$sqlSlct = "select distinct CYCC_TOP_13_DATA_VALUE DENIER from ($sqlData) where CYCC_TOP_13_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="FILAMENT"){
			$sqlSlct = "select distinct CYCC_TOP_16_DATA_VALUE FILAMENT from ($sqlData) where CYCC_TOP_16_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="INTERMINGLING"){
			$sqlSlct = "select distinct CYCC_TOP_18_DATA_VALUE INTERMINGLING from ($sqlData) where CYCC_TOP_18_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="HEATSET"){
			$sqlSlct = "select distinct CYCC_TOP_49_DATA_VALUE HEATSET from ($sqlData) where CYCC_TOP_49_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CROSS_SECTION"){
			$sqlSlct = "select distinct CYCC_TOP_17_DATA_VALUE CROSS_SECTION from ($sqlData) where CYCC_TOP_17_DATA_VALUE is not null ";
		}
		if ($TYPE_PARAM==="CYL_TYPE"){
			$sqlSlct = "select distinct CYL_TYPE from ($sqlData) where CYL_TYPE is not null ";
		}
		if ($TYPE_PARAM==="LUSTURE"){
			$sqlSlct = "select distinct CMY_LUSTURE from mgtapps.cst_mst_yarn where cmy_sys_id in (select cmy_sys_id from ($sqlData)) order by CMY_LUSTURE";
		}

		return $sqlSlct;
		}

	function setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal,$lengthDec){
		//echo ("$pCYCRL_FORMAT_DATA $lengthDec</br>");
		if ($pCYCRL_FORMAT_DATA === "Number"){
			//echo " $pCYCRL_FORMAT_DATA ".strpos($dtVal, ".");
			$pos = strpos($dtVal, ".");
			if (strpos($dtVal, ".")!==false && $pCYCRL_FORMAT_DATA === "Number"){
				$sqlDtDtl = "SELECT SUBSTR (dt, 1,INSTR (dt, '.'))||rpad(SUBSTR (dt, INSTR (dt, '.')+1),4,'0') GET_DATA
  				FROM (SELECT TO_CHAR (TO_NUMBER ($dtVal), 'fm999,999,999.9999') dt
          			 FROM DUAL)";
				//$sqlDtDtl = "select to_char(to_number($dtVal),'fm999,999,999.9999') from dual ";
				$dtVal = getData($conn,$sqlDtDtl);
			}

			if (substr($dtVal,0,1)==="."){
				$dtVal = "0".$dtVal;
			} else if (substr($dtVal,0,2)==="-.") {
				$dtVal = "-0.".substr($dtVal,2);
			}

			/*if ($lengthDec > 0){
			 	$sqlDtDtl = "select fSetDecimal('$dtVal',$lengthDec) GET_DATA from dual";
			 	//echo "$sqlDtDtl</br>";
			} else {
				$sqlDtDtl = "select to_char(round(replace('$dtVal',',')),'fm999,999,999,999') GET_DATA from dual";
			 	//echo "$sqlDtDtl</br>";
			}
		 	$dtVal = getData($conn,$sqlDtDtl);			*/
		 	$dtVal = setDecimal($conn,$dtVal,$lengthDec);
		}
		return $dtVal;
	}

	function getDtVal(	$conn
						,$CylSysId_dt
						,$sqlData
						,$pCYCRL_SOURCE_TYPE
						,$pCYCRL_SOURCE_QUERY
						,$pCYCRL_FORMAT_DATA
						,$lengthDec
						){

		$dtVal = "";
		if ($pCYCRL_SOURCE_TYPE === "NULL"){
			$sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from ($sqlData) where CYL_SYS_ID = $CylSysId_dt";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "MB Name"){
			$sqlDtDtl = "select MGTAPPS.pkg_yarn_valuation.fgetmbname ($CylSysId_dt) GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Default Value"){
			$sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		}else {
			if ($pCYCRL_SOURCE_QUERY !== "Source Query"){
				$sqlDtDtl = $pCYCRL_SOURCE_QUERY;
				$dtVal = getData($conn,str_replace(":P_CYL_SYS_ID_DTL",$CylSysId_dt ,$sqlDtDtl));
			}
		}

		if ($pCYCRL_FORMAT_DATA === "Number"){
		 	//$dtVal = setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal);
		 	//echo "$pCYCRL_FORMAT_DATA $lengthDec</br>";
		 	$dtVal = setNumber($conn,"Number",$dtVal,$lengthDec);
		}

		return $dtVal;
	}
	function isBold($pCYCRL_IS_BOLD,$pSts){
		if ($pCYCRL_IS_BOLD==="Y"){
			if ($pSts===1){ echo "<b>"; }
			else if ($pSts===2){ echo "</b>"; }
		}
	}

	$CST_PRODUCT_FOWARDING = $StsViewDt =  getData($conn,"select MGTAPPS.pkg_yarn_valuation.getPrdFowarding GET_DATA from dual ");

	function getSqlDeepMelange($P_CYL_SYS_ID_DTL){
		return "select count(-1) GET_DATA
							from mgtapps.cst_yarn_left yl
									 ,mgtapps.cst_yarn_calculation yc
									 ,mgtapps.cst_yarn_rm_hdr yrh
									 ,mgtapps.cst_yarn_rm_multi yrm
							where yl.cyl_sys_id = '$P_CYL_SYS_ID_DTL'
							and  yl.cyl_sys_id = yc.cyc_cyl_sys_id
							and cyc_top_no = 55
							and yc.cyc_sys_id = cyrh_cyc_sys_id
							and cyrh_sys_id = cyrm_cyrh_sys_id";
	}

	function getCylSysIdRMMelange($conn,$P_CYL_SYS_ID_DTL,$Rank_Dt){
		$sqlDt =
						"SELECT CYL_SYS_ID GET_DATA  FROM (
								SELECT CYRM_YARN_LEFT_NO, RANK () OVER (ORDER BY CYRM_YARN_LEFT_NO) RANK_DT
								FROM mgtapps.cst_yarn_left yl,
											 mgtapps.cst_yarn_calculation yc,
											 mgtapps.cst_yarn_rm_hdr yrh,
											 mgtapps.cst_yarn_rm_multi yrm
								 WHERE     yl.cyl_sys_id = '$P_CYL_SYS_ID_DTL'
											 AND yl.cyl_sys_id = yc.cyc_cyl_sys_id
											 AND cyc_top_no = 55
											 AND yc.cyc_sys_id = cyrh_cyc_sys_id
											 AND cyrh_sys_id = cyrm_cyrh_sys_id
									), mgtapps.cst_yarn_left yl
						where RANK_DT = $Rank_Dt
						and cyl_left_no = CYRM_YARN_LEFT_NO
						and cyl_prs_type = MGTAPPS.pkg_yarn_calculation.fprsidval
						";

		$Return =  getData($conn,$sqlDt);

		return $Return;
	}

?>
