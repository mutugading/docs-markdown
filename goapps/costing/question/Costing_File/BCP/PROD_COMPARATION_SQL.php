<?php
  $sqlData =
    "
    SELECT *  FROM mgtapps.cst_mst_yarn y,mgtapps.cst_yarn_left l,mgtapps.cst_yarn_calculation_cur cc
    WHERE cycc_cyl_sys_id = cyl_sys_id AND cyl_cmy_sys_id = cmy_sys_id AND cyl_prs_type = cycc_prs_type
    and CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
    ";

  function isBold($pCYCRL_IS_BOLD,$pSts){
    if ($pCYCRL_IS_BOLD==="Y"){
      if ($pSts===1){ echo "<b>"; }
      else if ($pSts===2){ echo "</b>"; }
    }
  }

  function printDescription(
              $CYCRL_SEQ_NO
              ,$CYCRL_HIDE_SEQ_NO
              ,$CYCRL_DESCRIPTION
            ){
      $rtn=$CYCRL_DESCRIPTION;
      if($CYCRL_HIDE_SEQ_NO==="0"){ $rtn=$CYCRL_SEQ_NO.".".$rtn.".";}
      return $rtn;
  }

  function getDtVal(	$conn
            ,$CylSysId_dt
            ,$sqlData
            ,$pCYCRL_SOURCE_TYPE
            ,$pCYCRL_SOURCE_QUERY
            ,$pCYCRL_FORMAT_DATA
            ,$lengthDec
            ,$noDt
            ,$P_CYL_SYS_ID_DTL=""
            ){

    $dtVal = "";
    if ($pCYCRL_SOURCE_TYPE === "NULL"){
      $sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from ($sqlData) where CYL_SYS_ID = $CylSysId_dt";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "MB Name"){
      $sqlDtDtl = "select MGTAPPS.pkg_yarn_marketing.fgetmbname ($CylSysId_dt) GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Default Value"){
      $sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Formula STD SP AX"){
      $sqlDtDtl = "
                  select CMPG_STD_SELLING_PRICE GET_DATA
                  from mgtapps.cst_mst_product_grade
                  where cmpg_poy_b_c_grade in (
                    select cyc_data_value from mgtapps.cst_yarn_calculation a
                    where cyc_cyl_sys_id = '$CylSysId_dt'
                    and cyc_top_no = 97
                  )
                  ";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Final Conversion"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Final_Conversion('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "CostLess_QL_CO_Frwd"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_CostLess_QL_CO_Frwd('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Addl_NSBC_Loss"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Addl_NSBC_Loss('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "NSBC_SP"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_NSBC_SP('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Extra_Yarn_Persen"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Cost_Of_Extra_Yarn_Persen"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Cost_Of_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Dom_Cost_AX_Grd_Only"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Dom_Cost_AX_Grd_Only('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else {
      if ($pCYCRL_SOURCE_QUERY !== "Source Query"){
        if ($CylSysId_dt!==""){
          $sqlDtDtl = $pCYCRL_SOURCE_QUERY;
          $dtVal = getData($conn,str_replace(":P_CYL_SYS_ID_DTL",$CylSysId_dt ,$sqlDtDtl));
        }
      }
    }
    if ($dtVal==="" || $dtVal==="0"){
      $dtVal="-";
    }else{
      if ($pCYCRL_FORMAT_DATA === "Number"){
        //$dtVal = setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal);
        //echo "$pCYCRL_FORMAT_DATA $lengthDec</br>";
        $dtVal = setNumber($conn,"Number",$dtVal,$lengthDec);
      }
    }
    // if($noDt==="5"){die();}
    return $dtVal;
  }

  function setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal,$lengthDec){
		if ($pCYCRL_FORMAT_DATA === "Number"){
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

		 	$dtVal = setDecimal($conn,$dtVal,$lengthDec);
		}
		return $dtVal;
	}

  function getDtVal_Usr(	$conn
						,$CylSysId_dt
						,$sqlData
						,$pCYCRL_SOURCE_TYPE
						,$pCYCRL_SOURCE_QUERY
						,$pCYCRL_FORMAT_DATA
						,$lengthDec
						,$USER_NAME
						,$CYCRL_SEQ_NO
						,$P_CYL_SYS_ID_DTL=""
						){

		$dtVal = "";
		// echo "pCYCRL_SOURCE_TYPE $pCYCRL_SOURCE_TYPE <br>";
		if ($pCYCRL_SOURCE_TYPE === "NULL"){
			$sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from ($sqlData) where CYL_SYS_ID = $CylSysId_dt";
			 // echo "$sqlDtDtl<br>";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "MB Name"){
			//if ($CYCRL_SEQ_NO==="38") {if ($USER_NAME==="1949") { echo "pCYCRL_SOURCE_TYPE === MB Name";}}
			$sqlDtDtl = "select MGTAPPS.pkg_yarn_marketing.fgetmbname ($CylSysId_dt) GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Default Value"){
			//if ($CYCRL_SEQ_NO==="38") {if ($USER_NAME==="1949") { echo "pCYCRL_SOURCE_TYPE === Default Value";}}
			$sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Formula STD SP AX"){
			//if ($CYCRL_SEQ_NO==="38") {if ($USER_NAME==="1949") { echo "pCYCRL_SOURCE_TYPE === Formula STD SP AX";}}
			$sqlDtDtl = "
									select CMPG_STD_SELLING_PRICE GET_DATA
									from mgtapps.cst_mst_product_grade
									where cmpg_poy_b_c_grade in (
										select cyc_data_value from mgtapps.cst_yarn_calculation a
										where cyc_cyl_sys_id = '$CylSysId_dt'
										and cyc_top_no = 97
									)
									";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Final Conversion"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_Final_Conversion('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "CostLess_QL_CO_Frwd"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_CostLess_QL_CO_Frwd('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Addl_NSBC_Loss"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_Addl_NSBC_Loss('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "NSBC_SP"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_NSBC_SP('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Extra_Yarn_Persen"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Cost_Of_Extra_Yarn_Persen"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_Cost_Of_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else if ($pCYCRL_SOURCE_TYPE === "Dom_Cost_AX_Grd_Only"){
			$sqlDtDtl = "select pkg_yarn_marketing.fGet_Dom_Cost_AX_Grd_Only('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
			$dtVal = getData($conn,$sqlDtDtl);
		} else {
			//if ($CYCRL_SEQ_NO==="38") {if ($USER_NAME==="1949") { echo "pCYCRL_SOURCE_TYPE === Source Query";}}
			if ($pCYCRL_SOURCE_QUERY !== "Source Query"){
				if ($CylSysId_dt!==""){
					$sqlDtDtl = $pCYCRL_SOURCE_QUERY;
					$dtVal = getData($conn,str_replace(":P_CYL_SYS_ID_DTL",$CylSysId_dt ,$sqlDtDtl));
				}
			}
		}

		//if ($USER_NAME==="1949") {echo "hasil $dtVal</br>";}

		if ($dtVal==="" || $dtVal==="0"){
			$dtVal="-";
		}else{
			if ($pCYCRL_FORMAT_DATA === "Number"){
			 	//$dtVal = setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal);
			 	//echo "$pCYCRL_FORMAT_DATA $lengthDec</br>";
			 	$dtVal = setNumber($conn,"Number",$dtVal,$lengthDec);
			}
		}
		return $dtVal;
	}

  function getDtVal_RM55(	$conn
            ,$CylSysId_dt
            ,$sqlData
            ,$pCYCRL_SOURCE_TYPE
            ,$pCYCRL_SOURCE_QUERY
            ,$pCYCRL_FORMAT_DATA
            ,$lengthDec
            ,$CYCRL_SEQ_NO
            ,$P_CYL_SYS_ID_DTL=""
            ){

    //if ($CYCRL_SEQ_NO	=== "82"){ echo "sqlData $pCYCRL_SOURCE_TYPE";	die(); }

    $dtVal = "";
    if ($pCYCRL_SOURCE_TYPE === "NULL"){
      $sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from ($sqlData) where CYL_SYS_ID = '$CylSysId_dt' ";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "MB Name"){
      $sqlDtDtl = "select MGTAPPS.pkg_yarn_marketing.fgetmbname ('$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Default Value"){
      $sqlDtDtl = "select $pCYCRL_SOURCE_QUERY GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Formula STD SP AX"){
      $sqlDtDtl = "
                  select CMPG_STD_SELLING_PRICE GET_DATA
                  from mgtapps.cst_mst_product_grade
                  where cmpg_poy_b_c_grade in (
                    select cyc_data_value from mgtapps.cst_yarn_calculation a
                    where cyc_cyl_sys_id = '$CylSysId_dt'
                    and cyc_top_no = 97
                  )
                  ";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Final Conversion"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Final_Conversion('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "CostLess_QL_CO_Frwd"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_CostLess_QL_CO_Frwd('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Addl_NSBC_Loss"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Addl_NSBC_Loss('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "NSBC_SP"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_NSBC_SP('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Extra_Yarn_Persen"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Cost_Of_Extra_Yarn_Persen"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Cost_Of_Extra_Yarn_Persen('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else if ($pCYCRL_SOURCE_TYPE === "Dom_Cost_AX_Grd_Only"){
      $sqlDtDtl = "select pkg_yarn_marketing.fGet_Dom_Cost_AX_Grd_Only('$P_CYL_SYS_ID_DTL','$CylSysId_dt') GET_DATA from dual";
      $dtVal = getData($conn,$sqlDtDtl);
    } else {
      if ($pCYCRL_SOURCE_QUERY !== "Source Query"){
        if ($CylSysId_dt!==""){
          $sqlDtDtl = $pCYCRL_SOURCE_QUERY;
          $dtVal = getData($conn,str_replace(":P_CYL_SYS_ID_DTL",$CylSysId_dt ,$sqlDtDtl));
        } else {
          $dtVal = "0";
        }
      }
    }

    if ($dtVal==="" || $dtVal==="0"){
      $dtVal="-";
    }else{
      if ($pCYCRL_FORMAT_DATA === "Number"){
        //$dtVal = setNumber($conn,$pCYCRL_FORMAT_DATA,$dtVal);
        //echo "$pCYCRL_FORMAT_DATA $lengthDec</br>";
        $dtVal = setNumber($conn,"Number",$dtVal,$lengthDec);
      }
    }

    return $dtVal;
  }

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
            and cyl_prs_type = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt
            ";

    $Return =  getData($conn,$sqlDt);

    return $Return;
  }

  function getSqlDesc($P_CYCRM_SYS_ID){
    return
    "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
            NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
            CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
            NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
            NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
            ,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
            ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
            ,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
            ,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT,CYCRL_HIDE_SEQ_NO
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        and CYCRL_SEQ_NO in (1,2,4,5,6,7,8,14,15,16,18,21,33,34,40,54,56,60,62,64,65,77,82)
        order by to_number(cycrl_seq_no) ";
  }

?>
