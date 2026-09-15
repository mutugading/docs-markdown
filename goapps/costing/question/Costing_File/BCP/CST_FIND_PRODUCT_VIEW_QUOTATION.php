<?php
	include("CST_FIND_PRODUCT_USED_FORM_PAGE.php");//die();
?>
</div>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:240%" border="1">
	<tr>
		<td <?php echo getStyle($font,"center"); ?> >
			Check
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			Left No </br>
			Product
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			Item Code
		</td>
<?php if ($byShadeMulti === "Y") { ?>
	<td <?php echo getStyle($font,"center"); ?> >
		Shade Name
	</td>
<?php } ?>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(1,$lblNcClr); ?>No
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td>
		<td <?php echo getStyle($font,"center","7"); ?> >
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td <?php echo getStyle($font,"center","12"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td <?php echo getStyle($font,"center","18"); ?>>
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } ?>
		<td <?php echo getStyle($font,"center","12"); ?>>
			<?php lblNo(4,$lblNcClr); ?>Name
		</td>

		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td>

		<td <?php echo getStyle($font,"center","2"); ?>>
				<b><?php lblNo(6,$lblNcClr); ?>
				<?php echo $lbl_6; ?>
				</b>
		</td>
		<td <?php echo getStyle($font,"center","2"); ?> >
			<?php lblNo(7,$lblNcClr); ?>Chip Rate
		</td>
		<td <?php echo getStyle($font,"center","2"); ?> >
			<?php lblNo(8,$lblNcClr); ?>MB cost
		</td>
		<td <?php echo getStyle($font,"center","2"); ?>>
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
			<?php lblNo(14,$lblNcClr); ?>V3=>3<6
		</td>

		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(15,$lblNcClr); ?>V5<12
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(16,$lblNcClr); ?>Denier
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(17,$lblNcClr); ?>Filament
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(18,$lblNcClr); ?>Intermingling
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(19,$lblNcClr); ?>Heatset
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(20,$lblNcClr); ?>Cross Section
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php lblNo(21,$lblNcClr); ?>Lusture
		</td>
		<td <?php echo getStyle($font,"center","7"); ?>>
			<?php lblNo(22,$lblNcClr); ?>MB Name
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(23,$lblNcClr); ?>Chip
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
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
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(29,$lblNcClr); ?>Change</br>
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
	delViewTmp($conn,'$USER_NAME');

	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";

	//if ($USER_NAME === "1949") {echo "$SqlView</br>";	}
  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);

	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
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
						//if ($USER_NAME==="1949") {echo "$SqlMbDt <br>";}
						$rsMbDt = oci_parse($conn,$SqlMbDt);
						oci_execute ($rsMbDt);
						$DtMb = "";
						while ($rowMbDt = oci_fetch_array ($rsMbDt, OCI_BOTH)) {
							$DtRm = $rowMbDt['CYC_DATA_VALUE'];
							//echo "DtMb $DtMb";
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
		//cek is Dt RM 55

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
	//if ($USER_NAME==="1949") {echo "valDt27 2 $valDt27<br>";}
	//$valDt27 = setNumber($conn,"Number",$valDt27,4);

//val data 24
		$valDt24 = "";
		//if ($USER_NAME==="1949"){ echo $rowRsView['CYL_TYPE']; }
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
				/*if ($TotRecDt>1){
						$DtMb = "</br>$DtMb";
				}*/
				$valDt24 = $DtMb;
			}
		} else if ($rowRsView['CYL_TYPE']==="PTY BO"){
			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
									where A.CYC_CYL_SYS_ID = '".$rowRsView['CYL_SYS_ID']."'
									and A.CYC_TOP_NO = 55";
			$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
		} else if ($isUseRmBo==="Y"){

			$sqlGetDt = "select MGTAPPS.pkg_yarn_marketing.fGetCylSysID_byLvl_RmBo(
													'".$rowRsView['CYL_SYS_ID']."'
													,$deepMaterial
													,2
													) GET_DATA
									 from dual";
			$CylSysId_UseBo =getDataUser($conn,$sqlGetDt,$USER_NAME);

			$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
									where A.CYC_CYL_SYS_ID = '$CylSysId_UseBo'
									and A.CYC_TOP_NO = 55";
			$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);
		}else{
			//cek RM PTY BO
			$sqlDtRm= "select MGTAPPS.pkg_yarn_calculation.fGetRM_PTY_BO('".$rowRsView['CYL_SYS_ID']."') GET_DATA from dual";
			$RM_PTY_BO = getDataUser($conn,$sqlDtRm,$USER_NAME);

			if ($RM_PTY_BO !=="" ){

				$SqlData = "select CYC_DATA_VALUE GET_DATA from mgtapps.cst_yarn_calculation a
										where A.CYC_CYL_SYS_ID = '$RM_PTY_BO'
										and A.CYC_TOP_NO = 55";

				$valDt24 =  getDataUser($conn,$SqlData,$USER_NAME);

			} else {

				if (!empty($rowRsView['FGETCHIPRATE'])){
					$valDt24 = $rowRsView['FGETCHIPRATE'];
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getDataUser($conn,$sqlDtRm,$USER_NAME);
					$valDt24 = $DtRm;
				}

			}

		}

		if ($FORM_NAME === "CST_2P1_0003"){
			$SqlGet =  "select MGTAPPS.pkg_yarn_valuation.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual ";
			$valDt24 = getDataUser($conn,$SqlGet,$USER_NAME);
		}

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
			<input type="checkbox"  id="CHOOSE[]" name="CHOOSE[]"  value="<?php echo $rowRsView['CYL_LEFT_NO']; ?>">
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?>>
			<?php if(!empty($rowRsView['CYL_ITEM_CODE'])) {echo $rowRsView['CYL_ITEM_CODE'];} ?>
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
<?php } else if ($byCust === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?> >
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
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
			<?php if (!empty($rowRsView['CMY_NAME'])){ echo $rowRsView['CMY_NAME']; } ?>
		</td><!-- 4 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_MCNAME'])){ echo $rowRsView['FGET_MCNAME']; } ?>
		</td><!-- 5 -->
		<td <?php echo getStyle($font,"right"); ?>
		 <?php echo "style='font-size: 18px;background-color:$ClrView11'"; ?>
		>
			<b><?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?></b>
		</td><!-- 6 -->
		<td <?php echo getStyle($font,"center"); ?>
			<?php echo "style='font-size: 18px;background-color:$ClrView24'"; ?>
		>
			<b>
				<?php
				echo $valDt24;
				?>
			</b>
		</td><!-- 7 -->
		<td <?php echo getStyle($font,"right"); ?>
			<?php echo "style='font-size: 18px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td><!-- 8 -->
		<td <?php echo getStyle($font,"right"); ?>
				<?php echo "style='font-size: 18px;background-color:$ClrView11A'"; ?>
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
		<td <?php echo getStyle($font,"left"); ?>>
			<?php
			$Tmp_22 = "";
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
										if ($Tmp_22===""){
												$Tmp_22 = $rowMbDt['CYC_DATA_VALUE'];
										} else {
												$Tmp_22 = $Tmp_22."|".$rowMbDt['CYC_DATA_VALUE'];
										}
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
										if ($Tmp_22===""){
												$Tmp_22 = $rowMbDt['DT_VAL'];
										} else {
												$Tmp_22 = $Tmp_22."|".$rowMbDt['DT_VAL'];
										}
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

						$Tmp_22 = $MbNmRaw;

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

						echo $MbNmRaw;
					}
				}

			}
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
			<?php
			$SQVT_FG_FINAL_PACKING = "";
			if (!empty($rowRsView['FGET_PACKINGTYPE'])){
				$SQVT_FG_FINAL_PACKING = $rowRsView['FGET_PACKINGTYPE'];
				echo $rowRsView['FGET_PACKINGTYPE'];
			}?>
		</td><!-- 24 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
			  $SQVT_FG_BOBBIN_WEIGHT =0;
				if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){
					$SQVT_FG_BOBBIN_WEIGHT = $rowRsView['FGET_BOBBINWEIGHTAX'];
					echo $rowRsView['FGET_BOBBINWEIGHTAX'];
				}
			?>
		</td><!-- 25 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
			$SQVT_FG_NO_OF_BOBBINS = 0;
			if (!empty($rowRsView['FGET_NOOFBOBBINS'])){
				$SQVT_FG_NO_OF_BOBBINS = $rowRsView['FGET_NOOFBOBBINS'];
				echo $rowRsView['FGET_NOOFBOBBINS'];
			}?>
		</td><!-- 26 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
			$Tmp_27 = "";
			//$isDt_RM55==="Y" is data RM 55
			//$TotPoy_Rm55=== Tot POY RM
			//$isMulti_RM RM 55 multi`
			//if ($USER_NAME==="1949"){echo "isDt_RM55 $isDt_RM55<br>";}
			$SQVT_MB_DOZ_PRSN = "";
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
									 if($Tmp_27===""){
										 $Tmp_27 = $rowMbDt['DT_VAL'];
									 } else {
										 $Tmp_27 = $Tmp_27."|".$rowMbDt['DT_VAL'];
									 }
							 }
							 if ($TotRecDt>1){
									 $DtMb = "</br>$DtMb";
							 }
							 if ($DtMb!=="") {
								 $SQVT_MB_DOZ_PRSN = $DtMb;
								 echo $DtMb;
							 }
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
										if($Tmp_27===""){
 										 $Tmp_27 = $rowMbDt['DT_VAL'];
 									 	} else {
 										 $Tmp_27 = $Tmp_27."|".$rowMbDt['DT_VAL'];
 									 	}
								}
								if ($TotRecDt>1){
										$DtMb = "</br>$DtMb";
								}
								if ($DtMb!=="") {
									$SQVT_MB_DOZ_PRSN = $DtMb;
									echo $DtMb;
								}
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
						$SQVT_MB_DOZ_PRSN = $MbNmRaw;
						$Tmp_27 = $MbNmRaw;
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
								if($Tmp_27===""){
								 $Tmp_27 = $rowMbDt['CYC_DATA_VALUE'];
								} else {
								 $Tmp_27 = $Tmp_27."|".$rowMbDt['CYC_DATA_VALUE'];
								}
						}
						if ($TotRecDt>1){
								$DtMb = "</br>$DtMb";
						}
						if ($DtMb!=="") {
							$SQVT_MB_DOZ_PRSN = $DtMb;
							echo $DtMb;
						}
						if ($clr === "red"){
							$clr = "blue";
						} else if ($clr === "blue"){
							$clr = "red";
						}
					}
				} else {
					if (!empty($rowRsView['FGETRPDOZ'])){
						$SQVT_MB_DOZ_PRSN = $rowRsView['FGETRPDOZ'];
						$Tmp_27 = $rowRsView['FGETRPDOZ'];
						echo setNumber($conn,"Number",$rowRsView['FGETRPDOZ'],2);
					} else {
						$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETRPDOZ_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
						$DtRm =  getData($conn,$sqlDtRm);
						$SQVT_MB_DOZ_PRSN = $DtRm;
						$Tmp_27 = $DtRm;
						echo setNumber($conn,"Number",$DtRm,2);
					}
				}

			}
		?>
		</td><!-- 27 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
			$SQVT_MB_RATE = 0; $Tmp_28 = "";
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
									 if ($Tmp_28 === ""){
										 $Tmp_28 = $rowMbDt['DT_VAL'];
									 } else {
										 $Tmp_28 = $Tmp_28."|".$rowMbDt['DT_VAL'];
									 }
							 }
							 if ($TotRecDt>1){
									 $DtMb = "</br>$DtMb";
							 }
							 if ($DtMb!=="") {
								 $SQVT_MB_RATE = $DtMb;
								 echo $DtMb;
							 }
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
										if ($Tmp_28 === ""){
											$Tmp_28 = $rowMbDt['DT_VAL'];
										} else {
											$Tmp_28 = $Tmp_28."|".$rowMbDt['DT_VAL'];
										}
								}
								if ($TotRecDt>1){
										$DtMb = "</br>$DtMb";
								}
								if ($DtMb!=="") {
									$SQVT_MB_RATE = $DtMb;
									echo $DtMb;
								}
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
						$SQVT_MB_RATE = $MbNmRaw;
						$Tmp_28 = $MbNmRaw;
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
								if ($Tmp_28 === ""){
									$Tmp_28 = $rowMbDt['CYC_DATA_VALUE'];
								} else {
									$Tmp_28 = $Tmp_28."|".$rowMbDt['CYC_DATA_VALUE'];
								}

						}
						if ($TotRecDt>1){
								$DtMb = "</br>$DtMb";
						}
						if ($DtMb!=="") {
							$SQVT_MB_RATE = $DtMb;
							echo $DtMb;
						}
						if ($clr==="red"){ $clr="blue"; } else if ($clr==="blue"){$clr="red";}
					}
				}else{
					if (!empty($rowRsView['FGETMBRATE'])){
						$Tmp_28 = $rowRsView['FGETMBRATE'];
						echo setNumber($conn,"Number",$rowRsView['FGETMBRATE'],3);
						$SQVT_MB_RATE = $rowRsView['FGETMBRATE'];
					} else {
						$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBRATE_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
						$DtRm =  getData($conn,$sqlDtRm);
						$Tmp_28 = $DtRm;
						$SQVT_MB_RATE = $DtRm;
						echo setNumber($conn,"Number",$DtRm,3);
					}
				}

			}

			?>
		</td><!-- 28 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
			$SQVT_CHANGE_OVER_LOSS = 0;
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
					if ($DtMb!=="") {
						echo "<p style='color:$stsPic;'>$DtMb</p>";
					}
					if ($stsPic === "blue"){ $stsPic = "red";} else {$stsPic = "blue";}
				}
			} else {
				if (!empty($rowRsView['FGETCNGOVRLST'])){
					$SQVT_CHANGE_OVER_LOSS = $rowRsView['FGETCNGOVRLST'];
					echo setNumber($conn,"Number",$rowRsView['FGETCNGOVRLST'],3);
				} else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETCNGOVRLST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					$SQVT_CHANGE_OVER_LOSS = $DtRm;
					echo setNumber($conn,"Number",$DtRm,3);
				}
			}
			?>
		</td><!-- 29 -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
			$SQVT_FG_QUALITY_LOSS = 0;
			if (!empty($rowRsView['FGETQUALITYLOSS'])){
				$SQVT_FG_QUALITY_LOSS = $rowRsView['FGETQUALITYLOSS'];
				echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);
			}?>
		</td><!-- 30 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php
			if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
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
					$SQVT_FG_PACKING_COST = $rowRsView['FGET_DELPACKINGCOST'];
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
				$SQVT_DTY_PRD_KG = $DtRm;
				echo setNumber($conn,"Number",$DtRm,0);
				//echo $DtRm;
		?>
		</td><!-- 36 -->
	</tr>
	<?php
		$SQVT_DOM_COST_INCLD_TRNSPRT = 0;
		if (!empty($rowRsView['FGET_V4'])){ $SQVT_DOM_COST_INCLD_TRNSPRT = $rowRsView['FGET_V4'];  }
		insViewTmp(
				$conn  //1
				,$rowRsView['CYL_LEFT_NO']  //2
				,$USER_NAME //3
				,$valDt24  //4
				,$valDt27  //5
				,$valDt11A  //6
				,$SQVT_DOM_COST_INCLD_TRNSPRT  //7
				,$SQVT_DTY_PRD_KG //8
				,$Tmp_27 //9
				,$Tmp_28 //10
				,$SQVT_FG_FINAL_PACKING,$SQVT_FG_BOBBIN_WEIGHT,$SQVT_FG_NO_OF_BOBBINS,$SQVT_FG_PACKING_COST,$SQVT_CHANGE_OVER_LOSS,$SQVT_FG_QUALITY_LOSS
	      //,$SQVT_POY_MAN_POWER_COST,$SQVT_POY_OVER_HEADS_COST,$SQVT_DTY_MAN_POWER_COST,$SQVT_DTY_OVER_HEADS_COST
			);
	}
	?>
</table>
</div>
<?php include("CST_FIND_PRODUCT_VIEW_QUOTATION_ADD.php") ?>
