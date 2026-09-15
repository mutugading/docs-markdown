<?php
	$P_USER_ID = "$USER_NAME";
	$SqlData = "select COUNT(-1) GET_DATA
							 from mgtapps.CST_LVL_LEFT_PROD
							 where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'";
	$deepMaterial =  getData($conn,$SqlData);

	//get tot Max RM
	$SqlData = "select max(count(-1)) GET_DATA
							from mgtapps.CST_LVL_LEFT_PROD a
							where A.CLLP_CYL_SYS_ID_REFF ='$P_CYL_SYS_ID_DTL'
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
         		,CYCRL_IS_BOLD,CYCRL_HIDE_SEQ_NO
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
	$rs1Data = oci_parse($conn,$sql1);
	oci_execute ($rs1Data);
	$totRows = 0;
	$totData = 0;

	$widthTbl = ($deepMaterial+1) * 20;
	$widthSttg = "width:$widthTbl%";
	$bgClr = "#e6e1e1";

?>
<div class="main" style="overflow-x:auto;" align="center" >
<table style="<?php echo $widthSttg; ?>" border="1">

<?php
	if ($maxRm===""){
		$maxRm = 0;
	}

	$sqlGet = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_MaxRm('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
	$maxRMDt = getData($conn,$sqlGet);
	$TotRmDt = $maxRMDt;
	while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {

		//check is RM Rate Data
		$TotLpDt = 1;
	  if ($row1Data['CYCRL_SEQ_NO']==="6"){
			if ($CYL_TYPE_CK==="ACY"){
				$TotLpDt = $maxRMDt+5;
			}else {
				$TotLpDt = $maxRMDt+1;
			}
	  }else if ($row1Data['CYCRL_SEQ_NO']==="17"){
			if ($CYL_TYPE_CK==="ACY"){
				$TotLpDt = 2;
			}
		}
	  //check is RM Rate Data

		for ($x = 1; $x <= $TotLpDt; $x++) {
?>
<tr>
<!-- Description -->
			<td style="width:20%;font-size:13px;">
			<?php
				isBold($row1Data['CYCRL_IS_BOLD'],1);
				// $DataValue = "";
				// if (!empty($row1Data['CYCRL_SEQ_NO'])){
				// 	if ($row1Data['CYCRL_HIDE_SEQ_NO']==="0"){ $DataValue = $row1Data['CYCRL_SEQ_NO']."."; }
				// }
				// if (!empty($row1Data['CYCRL_DESCRIPTION'])){
				// 	$DataValue = $DataValue.$row1Data['CYCRL_DESCRIPTION'];
				// }
				$DataValue = printDescription(
		                  								$row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
		                  								,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
		                  								,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
									                    );

				if ($row1Data['CYCRL_SEQ_NO']==="6"){
			    $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Rate ".$x;
			    if ($TotLpDt===$x){
			      $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total";
			    }

					if ($TotRmDt>1){
						if ($TotRmDt<$x){
							if ($x - $TotRmDt === 1){ $DataValue = "Consume % of DTY"; } if ($x - $TotRmDt === 2){ $DataValue = "Consume % of SPD"; }
							if ($x - $TotRmDt === 3){ $DataValue = "Consume value of DTY"; } if ($x - $TotRmDt === 4){ $DataValue = "Consume value of SPD"; }
							if ($x - $TotRmDt === 5){ $DataValue = $row1Data['CYCRL_SEQ_NO'].".RM Total"; }
						}
					}

			  }
				if ($row1Data['CYCRL_SEQ_NO']==="17"){
		      if ($x === 2){
		          $DataValue = "Draw Ratio";
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
			$Product_Key = "";
 			$lpDt++;
			//Check Type Data
			$ckTypeDt = getData($conn,
													"select MGTAPPS.Pkg_Gen_Lvl_55.fGet_TypeData('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA
													 from dual");
			//if ($USER_NAME ==="1949"){if ($row1Data['CYCRL_SEQ_NO']==="1"){echo "ckTypeDt $ckTypeDt<br>";}}
			//Check Left No
			$ckLeftNoDt = getData($conn,
													"select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylLeftNo('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA
													 from dual");
		 //Check Cyl Sys Id
		 if ($ckTypeDt === "From Group Item MKT Rate"){
	 	 	$sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_IdReff('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
		 }else if ($ckTypeDt === "Stores"){
			$sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_StoreId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
		 } else{
			$sqlCylSysIdDt = "select MGTAPPS.Pkg_Gen_Lvl_55.fGet_CylSysId('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual";
		 }
		 $ckCylSysIdDt = getData($conn,$sqlCylSysIdDt);
		 //if ($USER_NAME==="1949") {			 //if ($ckTypeDt==="Stores"){if ($row1Data['CYCRL_SEQ_NO']==="1"){ echo "$sqlCylSysIdDt $ckCylSysIdDt<br>";}}		 }
?>
		<td style="width:20%;font-size:13px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
		$CylSysId_dt =  $ckCylSysIdDt;

		isBold($row1Data['CYCRL_IS_BOLD'],1);
		if ($CYL_TYPE_S==="ACY"){
			//sdie();
			$dtVal = getDtVal_RM55(	$conn
								,$CylSysId_dt
								,$sqlData
								,$row1Data['CYCRL_SOURCE_TYPE']
								,$row1Data['CYCRL_SOURCE_QUERY']
								,$row1Data['CYCRL_FORMAT_DATA']
								,$row1Data['CYCRL_LENGTH_DECIMAL']
								,$row1Data['CYCRL_SEQ_NO']
								,$P_CYL_SYS_ID_DTL
							);

			$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysId_Acy ('$P_CYL_SYS_ID_DTL',$lpDt) GET_DATA from dual ";
      $Product_dt =  getData($conn,$SqlData);
      $StrPos = strpos($Product_dt,"|");
      $Product_Key = substr($Product_dt,0,$StrPos);
      $CylSysId_dt =  substr($Product_dt,$StrPos+1);


		} else {
			$dtVal = "";
			if ($ckTypeDt==="Stores"){
				//echo "Left NO $ckLeftNoDt";
			} else {
				if ($USER_NAME==="1949"){if ($row1Data['CYCRL_SEQ_NO']==="76"){echo "2 <br>";}}
				$dtVal =
								getDtVal_Usr(	$conn
													,$CylSysId_dt
													,$sqlData
													,$row1Data['CYCRL_SOURCE_TYPE']
													,$row1Data['CYCRL_SOURCE_QUERY']
													,$row1Data['CYCRL_FORMAT_DATA']
													,$row1Data['CYCRL_LENGTH_DECIMAL']
													,$USER_NAME
													,$row1Data['CYCRL_SEQ_NO']
													,$P_CYL_SYS_ID_DTL
												);

			}
		}

		if ($row1Data['CYCRL_SEQ_NO']==="1"){
			//echo "$ckTypeDt<b>";
			if ($ckTypeDt === "From Group Item MKT Rate"){
				$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm('$CylSysId_dt') GET_DATA from dual";
				//if ($USER_NAME==="1949") { echo "sqlGet $sqlGet<br>";}

				$dtVal = getData($conn,$sqlGet);
				//echo "$dtVal";
			} else if ($ckTypeDt === "Stores" ){

				$sqlGet = "select MGTAPPS.pkg_yarn_calculation.fGetItemGrpNm('$ckCylSysIdDt') GET_DATA from dual";
				$dtVal = getData($conn,$sqlGet);

			}
		}

		if ($row1Data['CYCRL_SEQ_NO']==="17"){
			if ($x === 2 && $Product_Key==="Stores"){
				$SqlData = "
										SELECT CYRM_DRAW_RATIO GET_DATA
											FROM mgtapps.cst_yarn_left l,
													 mgtapps.cst_yarn_calculation a,
													 mgtapps.cst_yarn_rm_hdr b,
													 mgtapps.cst_yarn_rm_multi c
										 WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
													 AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
													 AND A.CYC_TOP_NO = 55
													 AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
													 AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
													 and CYRM_SEC_NO = 2
										";
				$dtVal =  getData($conn,$SqlData);
			}
		}

		if ($row1Data['CYCRL_SEQ_NO']!=="6"){
			if ($row1Data['CYCRL_SEQ_NO']==="7"){
				if ($ckTypeDt === "From Group Item MKT Rate"){
					$sqlGet = "
										select
											MGTAPPS.fSetDecimal(MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$CylSysId_dt'),3) GET_DATA
										from dual";
					//echo "$sqlGet";
					$dtVal = getData($conn,$sqlGet);
					$dtVal = setNumber($conn,"Number",$dtVal,3);
					//echo $dtVal;
				} else if ($ckTypeDt === "Stores" ){
					$sqlGet = "
										select
											MGTAPPS.fSetDecimal(MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktLc('$ckCylSysIdDt'),3) GET_DATA
										from dual";
					//echo "$sqlGet";
					$dtVal = getData($conn,$sqlGet);
					$dtVal = setNumber($conn,"Number",$dtVal,3);

				} else {
						//echo $dtVal;//print value
				}
			} else {
				//echo $dtVal;//print value
			}
		} else {
		//process seq no 6

			//cek prod use multi RM
			$sqlGet =
			"SELECT  'Y' GET_DATA
			 FROM MGTAPPS.CST_YARN_CALCULATION a,
						MGTAPPS.CST_YARN_RM_HDR b
			 WHERE     CYC_CYL_SYS_ID = '$CylSysId_dt'
						 AND cyc_top_no = 55
						 AND CYC_FORMULA_TYPE = 'Raw_Material'
						 AND A.CYC_SYS_ID = B.CYRH_CYC_SYS_ID
						 AND CYRH_TYPE = 'Multi Yarn'
			";
			//echo "$sqlGet";
			$isRmMulti = getData($conn,$sqlGet);
			if ($isRmMulti!=="Y"){
				if ($TotLpDt !== $x ){
					$dtVal = "";
				} else {
					if ($ckTypeDt === "From Group Item MKT Rate"){
				    $sqlGet = "select
				                  MGTAPPS.fSetDecimal(MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$CylSysId_dt'),3)
				                  GET_DATA
				               from dual";
				    $dtVal = getData($conn,$sqlGet);
				  } else if ($ckTypeDt === "Stores" ){
						$sqlGet = "select
				                  MGTAPPS.fSetDecimal(MGTAPPS.pkg_yarn_calculation.fGetItemGrpMktRate('$ckCylSysIdDt'),3)
				                  GET_DATA
				               from dual";
					  //if($USER_NAME==="1949"){echo "sqlGet $sqlGet<br>";}
				    $dtVal = getData($conn,$sqlGet);
					}
				}
			} else {
				//if ($USER_NAME==="1949"){ echo "$x <= $TotRmDt<br>";}
				if ($x<=$TotRmDt){
					//echo "TotRmDt $TotRmDt : TotLpDt $TotLpDt : x $x : lpDt $lpDt<br>";
					//if ($USER_NAME==="1949"){ echo "cek 1<br>";}
					$SqlData = "
                      SELECT MGTAPPS.fSetDecimal(CYRM_RM_RATE,3)  GET_DATA
                        FROM mgtapps.cst_yarn_left l,
                             mgtapps.cst_yarn_calculation a,
                             mgtapps.cst_yarn_rm_hdr b,
                             mgtapps.cst_yarn_rm_multi c
                       WHERE     L.CYL_SYS_ID = '$CylSysId_dt'
                             AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
                             AND A.CYC_TOP_NO = 55
                             AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
                             AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
                             and CYRM_SEC_NO = $x
                      ";
					//if ($USER_NAME==="1949"){ echo "$SqlData<br>";}
          $dtVal =  getData($conn,$SqlData);
				}
				if ($x - $TotRmDt === 1){
					//if ($USER_NAME==="1949"){ echo "cek 2<br>";}
	        $SqlData = "
	                    SELECT
	                      MGTAPPS.fSetDecimal(CYRM_CONSUME,3) GET_DATA
	                      FROM mgtapps.cst_yarn_left l,
	                           mgtapps.cst_yarn_calculation a,
	                           mgtapps.cst_yarn_rm_hdr b,
	                           mgtapps.cst_yarn_rm_multi c
	                     WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
	                           AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
	                           AND A.CYC_TOP_NO = 55
	                           AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
	                           AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
	                           and CYRM_SEC_NO = 1
	                    ";
	        //$RmRate =  getData($conn,$SqlData);
	        $dtVal = getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
	      }
	      if ($x - $TotRmDt === 2){
					//if ($USER_NAME==="1949"){ echo "cek 3<br>";}
	        $SqlData = "
	                    SELECT MGTAPPS.fSetDecimal(CYRM_CONSUME,3)  GET_DATA
	                      FROM mgtapps.cst_yarn_left l,
	                           mgtapps.cst_yarn_calculation a,
	                           mgtapps.cst_yarn_rm_hdr b,
	                           mgtapps.cst_yarn_rm_multi c
	                     WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
	                           AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
	                           AND A.CYC_TOP_NO = 55
	                           AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
	                           AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
	                           and CYRM_SEC_NO = 2
	                    ";
	        //$RmRate =  getData($conn,$SqlData);
	        $dtVal =  getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
	      }
	      if ($x - $TotRmDt === 3){
					//if ($USER_NAME==="1949"){ echo "cek 4<br>";}
	        $SqlData = "
	                    SELECT  MGTAPPS.fSetDecimal(CYRM_RM_COST,3) GET_DATA
	                      FROM mgtapps.cst_yarn_left l,
	                           mgtapps.cst_yarn_calculation a,
	                           mgtapps.cst_yarn_rm_hdr b,
	                           mgtapps.cst_yarn_rm_multi c
	                     WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
	                           AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
	                           AND A.CYC_TOP_NO = 55
	                           AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
	                           AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
	                           and CYRM_SEC_NO = 1
	                    ";
	        //$RmRate =  getData($conn,$SqlData);
	        $dtVal = getData($conn,$SqlData);//setNumber($conn,"Number",$RmRate,3);
	      }
	      if ($x - $TotRmDt === 4){
					//if ($USER_NAME==="1949"){ echo "cek 5<br>";}
	        $SqlData = "
	                    SELECT MGTAPPS.fSetDecimal(CYRM_RM_COST,3 ) GET_DATA
	                      FROM mgtapps.cst_yarn_left l,
	                           mgtapps.cst_yarn_calculation a,
	                           mgtapps.cst_yarn_rm_hdr b,
	                           mgtapps.cst_yarn_rm_multi c
	                     WHERE     L.CYL_SYS_ID = '$P_CYL_SYS_ID_DTL'
	                           AND L.CYL_SYS_ID = A.CYC_CYL_SYS_ID
	                           AND A.CYC_TOP_NO = 55
	                           AND a.cyc_sys_id = B.CYRH_CYC_SYS_ID
	                           AND B.CYRH_SYS_ID = C.CYRM_CYRH_SYS_ID
	                           and CYRM_SEC_NO = 2
	                    ";
	        $RmRate =  getData($conn,$SqlData);
	        $dtVal = $RmRate;
	      }
			}

		//process seq no 6
		}
		//if ($USER_NAME==="1949"){if ($row1Data['CYCRL_SEQ_NO']==="38"){echo "sini <br>$sqlData<br>";}}
		echo $dtVal;//print value
		isBold($row1Data['CYCRL_IS_BOLD'],2);
		//if ($USER_NAME==="1949"){ if ($row1Data['CYCRL_SEQ_NO']==="39"){ die(); }}
?>
		</td>
<?php
	}
?>
<!-- Product -->
</tr>
<?php
		}
	}
?>
</table>
</div>
