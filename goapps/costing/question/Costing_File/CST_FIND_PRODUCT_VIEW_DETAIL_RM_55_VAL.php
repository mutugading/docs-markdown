<?php
	$P_USER_ID = "$USER_NAME";
	$SqlData = "select COUNT(-1) GET_DATA
							 from mgtapps.CST_LVL_LEFT_PROD
							 where CLLP_CYL_SYS_ID_REFF = '$CYL_SYS_ID_MKT'";
	$deepMaterial =  getData($conn,$SqlData);

	//get tot Max RM
	$SqlData = "select max(count(-1)) GET_DATA
							from mgtapps.CST_LVL_LEFT_PROD a
							where A.CLLP_CYL_SYS_ID_REFF ='$CYL_SYS_ID_MKT'
							and CLLP_TYPE_RM = 'Multi Yarn'
							group by CLLP_LEFT_NO_REFF";
	$maxRm =  getData($conn,$SqlData);
	//Get tot Max RM

	//$deepMaterial = 2;
	$sql1 =
		"SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
         		NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
         		CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
         		NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
         		NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
         		,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
         		,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
         		,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
         		,CYCRL_IS_BOLD
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
	$rs1Data = oci_parse($conn,$sql1);
	oci_execute ($rs1Data);
	$totRows = 0;
	$totData = 0;

	if ($USER_NAME==="1949"){
		//echo $sql1; die();
	}

	$widthTbl = ($deepMaterial+1) * 20;
	$widthSttg = "width:$widthTbl%";
	$bgClr = "#e6e1e1";

?>
<div class="main" style="overflow-x:auto;" align="center" >
<table style="<?php echo $widthSttg; ?>" border="1">

<?php
	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {

		//check is RM Rate Data
		$TotLpDt = 1;
	  if ($row1Data['CYCRL_SEQ_NO']==="6"){
	    $TotLpDt = $maxRm+1;
	  }
	  //check is RM Rate Data


		for ($x = 1; $x <= $TotLpDt; $x++) {

?>
<tr>
<!-- Description -->
			<td style="width:20%">
			<?php
				isBold($row1Data['CYCRL_IS_BOLD'],1);
				if (!empty($row1Data['CYCRL_SEQ_NO'])){
					$DataValue = $row1Data['CYCRL_SEQ_NO'].".";
				}

				if (!empty($row1Data['CYCRL_DESCRIPTION'])){
					$DataValue = $row1Data['CYCRL_DESCRIPTION'].".";
				}

				if ($row1Data['CYCRL_SEQ_NO']==="6"){
			    $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
			    if ($TotLpDt===$x){
			      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
			    }
			  }
				echo $DataValue;
				isBold($row1Data['CYCRL_IS_BOLD'],2);

			?>
			</td>
<!-- Description -->
<!-- Product -->
<?php
		$CYCRL_JUSTIFY = "LEFT";
		if (!empty($row1Data['CYCRL_JUSTIFY'])){$CYCRL_JUSTIFY = $row1Data['CYCRL_JUSTIFY'];}
		$lpDt = 0;
		while ($deepMaterial > $lpDt) {
 			$lpDt++;
			//Check Type Data
			$sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_TypeData('$CYL_SYS_ID_MKT',$lpDt) GET_DATA from dual";
			$ckTypeDt = getData($conn,$sqlCk);
			//Check Left No
			$sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylLeftNo('$CYL_SYS_ID_MKT',$lpDt) GET_DATA from dual";
			$ckLeftNoDt = getData($conn,$sqlCk);

		 	//Check Cyl Sys Id
			//$sqlCk = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$CYL_SYS_ID_MKT',$lpDt) GET_DATA from dual";
			$sqlCk = "select  MGTAPPS.pkg_yarn_calculation.get_CYL_SYS_ID($ckLeftNoDt, MGTAPPS.pkg_yarn_calculation.fPrsIDVal) GET_DATA from dual";;
			//echo "ckTypeDt $ckTypeDt : ckLeftNoDt $ckLeftNoDt : sqlCk $sqlCk";//die();
		 	$ckCylSysIdDt = getData($conn,$sqlCk);
?>
		<td style="width:20%" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
		$CylSysId_dt =  $ckCylSysIdDt;
		isBold($row1Data['CYCRL_IS_BOLD'],1);
		$dtVal =
						getDtVal_Valuation(	$conn
											,$CylSysId_dt
											,$PERIOD_DATA_S
											,$row1Data['CYCRL_SEQ_NO']
											,$sqlDataVal
											,$row1Data['CYCRL_SOURCE_TYPE']
											,$row1Data['CYCRL_SOURCE_QUERY']
											,$row1Data['CYCRL_FORMAT_DATA']
											,$row1Data['CYCRL_LENGTH_DECIMAL']
										);
		if ($row1Data['CYCRL_SEQ_NO']!=="6"){
			echo $dtVal;//print value
		} else {
			$isRmMulti = getData($conn,
		 											"SELECT  'Y' GET_DATA
										  		 FROM MGTAPPS.CST_YARN_CALCULATION a,
										       			MGTAPPS.CST_YARN_RM_HDR b
													 WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
													       AND cyc_top_no = 55
													       AND CYC_FORMULA_TYPE = 'Raw_Material'
													       AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
													       AND CYRH_TYPE = 'Multi Yarn'
													");

			if ($TotLpDt === $x){
				echo $dtVal;//print value
			}

			if($isRmMulti==="Y"){
				if ($TotLpDt > $x){

					$CylSysId_Rm = getData($conn
												 ,"SELECT CYRM_CYL_SYS_ID GET_DATA
													  FROM MGTAPPS.CST_YARN_CALCULATION a,
													       MGTAPPS.CST_YARN_RM_HDR b,
													       MGTAPPS.CST_YARN_RM_MULTI c
													 WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
													       AND cyc_top_no = 55
													       AND CYC_FORMULA_TYPE = 'Raw_Material'
													       AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
													       AND CYRM_CYRH_SYS_ID = CYRH_SYS_ID
													       AND CYRH_TYPE = 'Multi Yarn'
																 and CYRM_SEC_NO = $x
																 ");
					$dtVal = getData($conn
												 ,"select CYC_DATA_VALUE GET_DATA
							 						from cst_yarn_calculation t
							 						where cyc_cyl_sys_id = '$CylSysId_Rm'
							 						and cyc_top_no = 105 ");

					echo setNumber($conn,"Number",$dtVal,3);
				}
			}



		}
		isBold($row1Data['CYCRL_IS_BOLD'],2);

?>
		</td>
<?php
	}
?>
<!-- Product -->
</tr>
<?php
		if ($row1Data['CYCRL_SEQ_NO']==="1"){
			//die();
		}


		}
	}
?>
</table>
</div>
