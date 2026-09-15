<?php include("CST_FIND_PRODUCT_VIEW_QUOTATION_PAGE.php") ?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:150%" border="1">
	<tr>
		<td <?php echo getStyle($font,"center"); ?>>
			Check
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			Left No </br>
			Product
		</td>
<?php if ($byShadeMulti === "Y") { ?>
	<td <?php echo getStyle($font,"center"); ?> >
		Shade Name
	</td>
<?php } ?>
		<td <?php echo getStyle($font,"center"); ?> style="width:3%" align="center">
			<?php lblNo(1,$lblNcClr); ?>No
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?> style="width:15%">
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?> style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td <?php echo getStyle($font,"center"); ?> style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } ?>
		<td <?php echo getStyle($font,"center"); ?>  style="width:15%">
			<?php lblNo(4,$lblNcClr); ?>Name
		</td>

		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td>

		<td <?php echo getStyle($font,"center"); ?> style="width:6%">
				<b><?php lblNo(6,$lblNcClr); ?>
				<?php echo $lbl_6; ?>
				</b>
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php lblNo(7,$lblNcClr); ?>Chip Rate
		</td>
		<td <?php echo getStyle($font,"center"); ?> ><?php lblNo(8,$lblNcClr); ?>SP cost
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:7%">
			<b><?php lblNo('9',$lblNcClr); ?>
			Conv-SP Cost</br>
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
		<td <?php echo getStyle($font,"center"); ?> style="width:3%">
			<?php lblNo(16,$lblNcClr); ?>Denier
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:4%">
			<?php lblNo(17,$lblNcClr); ?>Filament
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:5%">
			<?php lblNo(18,$lblNcClr); ?>Intermingling
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:5%">
			<?php lblNo(19,$lblNcClr); ?>Heatset
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:5%">
			<?php lblNo(20,$lblNcClr); ?>Cross Section
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:3%">
			<?php lblNo(21,$lblNcClr); ?>Lusture
		</td>
		<td <?php echo getStyle($font,"center"); ?> style="width:15%">
			<?php lblNo(22,$lblNcClr); ?>SP Name
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
		<td <?php echo getStyle($font,"center"); ?> ><?php lblNo(29,$lblNcClr); ?>Change</br>
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
	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";

	 if ($USER_NAME === "1949") {
	 	//echo "$SqlView</br>";
	 }
  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);
	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$btnDtl = "Process('DETAIL ".$rowRsView['CYL_SYS_ID']."')";
		$btnSmltn = "Process('SIMULATION ".$rowRsView['CYL_SYS_ID']."')";
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
		$SqlMbDt =
			" select CYC_DATA_VALUE GET_DATA
				from mgtapps.cst_yarn_calculation
				where cyc_cyl_sys_id = '".$rowRsView['CYL_SYS_ID']."'
				and cyc_top_no = 73 ";
		//echo $SqlMbDt;

		$DtRm =  getData($conn,$SqlMbDt);
		$valDt27 = $DtRm;
		//$valDt27 = setNumber($conn,"Number",$valDt27,4);

//val data 24
		$valDt24 = "";
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
			if (!empty($rowRsView['FGETCHIPRATE'])){
				$valDt24 = $rowRsView['FGETCHIPRATE'];
			} else {
				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchiprate_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
				$DtRm =  getData($conn,$sqlDtRm);
				$valDt24 = $DtRm;
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
		<td <?php echo getStyle($font,"center"); ?> >
			<input type="checkbox"  id="CHOOSE[]" name="CHOOSE[]"  value="<?php echo $rowRsView['CYL_LEFT_NO']; ?>">
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
<?php if ($byShadeMulti === "Y") { ?>
			<td align="center" >
			<?php echo $rowRsView['CYL_SHADE_NAME']; ?>
			</td>
<?php } ?>
		<td <?php echo getStyle($font,"right"); ?> >
			<!-- 1 -->
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
<?php if ($byType === "Y") { ?>
		<td <?php echo getStyle($font,"left"); ?> >
			<!-- 2 Customer -->
			<?php
				if ($CUSTOMER_S==="NULL"){
					if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; }
				} else {
					echo $CUSTOMER_S;
				}

			?>
		</td>
		<td <?php echo getStyle($font,"center"); ?>>
			<!-- 3 Shade Name -->
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
		<!-- 3 Shade Name -->


		<td <?php echo getStyle($font,"left"); ?> >

			<?php if (!empty($rowRsView['CMY_NAME'])){ echo $rowRsView['CMY_NAME']; } ?>
		</td><!-- 4 Name -->

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
		<td align="right"
			<?php echo "style='font-size: 14px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td><!-- 8 -->
		<td align="right"
				<?php echo "style='font-size: 14px;background-color:$ClrView11A'"; ?>
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
		</td><!-- 23 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
		</td><!-- 24 -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];  }?>
		</td><!-- 25  -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>
		</td><!-- 26  -->
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
		</td><!-- 27  -->
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
		</td><!-- 28  -->
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
		</td><!-- 29  -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
			<!-- fgetCngOvrLst  MGTAPPS.pkg_yarn_marketing.fgetCngOvrLst('".$rowRsView['CYL_SYS_ID']."') -->
		</td><!-- 30  -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);
			}?>
		</td><!-- 31  -->
		<td <?php echo getStyle($font,"center"); ?> >
			<?php if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }?>
		</td><!-- 32  -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){
					echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);
				}?>
		</td><!-- 33  -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
			}?>
		</td><!-- 34  -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php echo $CST_PRODUCT_FOWARDING; ?>
		</td><!-- 35  -->
		<td <?php echo getStyle($font,"right"); ?>>
			<?php
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fGetDty_Prod(".$rowRsView['CYL_SYS_ID'].") GET_DATA from dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo setNumber($conn,"Number",$DtRm,0);
					//echo $DtRm;
			?>
		</td><!-- 36  -->
	</tr>
	<?php
	}
	?>
</table>
</div>
<?php include("CST_FIND_PRODUCT_VIEW_QUOTATION_ADD.php") ?>
