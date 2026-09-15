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
             FROM mgtapps.cst_mst_yarn y,
                  mgtapps.cst_yarn_left l,
                  mgtapps.cst_yarn_calculation_cur cc
            WHERE cycc_cyl_sys_id = cyl_sys_id
              AND cyl_cmy_sys_id = cmy_sys_id
              AND cyl_prs_type = cycc_prs_type
              AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_IS_VALID_PRD = 'Y' ";
  //$SqlViewWhere = " and CYL_TYPE = '$CYL_TYPE_S' ";
	//$SqlViewWhere = " and CYL_TYPE = '$CYL_TYPE_S' ";
	//echo "CYL_SHADE_NAME_S $CYL_SHADE_NAME_S</br>";
	if ($USER_NAME === "1949"){
		//echo "SqlView $SqlView</br>";
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

  $SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
  $SqlCnt = "select count(-1) GET_DATA from ($SqlView)  ";
    //echo "SqlCnt $SqlCnt</br>";

	$StsViewDt =  getData($conn,$SqlCnt);

	$totRows = $StsViewDt;

	$P_ROWS_DISP = 10;
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
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:150%" border="1">
	<tr>
		<td align="center">

		</td>
<?php if ($byShadeMulti === "Y") { ?>
	<td align="center" >
		Shade Name
	</td>
<?php } ?>
		<td align="center" style="width:3%" align="center">
			<?php lblNo(1,$lblNcClr); ?>No
		</td>
<?php if ($byType === "Y") { ?>
		<td align="center" style="width:15%">
			<?php lblNo(2,$lblNcClr); ?>Customer
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td align="center" style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Customer
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td align="center" style="width:5%">
			<?php lblNo(2,$lblNcClr); ?>Type
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(3,$lblNcClr); ?>Shade Name
		</td>
<?php } ?>
		<td align="center"  style="width:15%">
			<?php lblNo(4,$lblNcClr); ?>Name
		</td>

		<td align="center" >
			<?php lblNo(5,$lblNcClr); ?>&nbspM/C&nbsp
		</td>
		<td align="center" >
			<?php lblNo(6,$lblNcClr); ?>Eff
		</td>
		<td align="center" >
			<?php lblNo(7,$lblNcClr); ?>Speed
		</td>

		<td align="center" >
			<?php lblNo(8,$lblNcClr); ?>V1<1.5</br>
		</td>
		<td align="center" >
			<?php lblNo(9,$lblNcClr); ?>V2=>1.5<3</br>
		</td>
		<td align="center" >
			<?php lblNo(10,$lblNcClr); ?>V3=>3<6
		</td>
		<td align="center" >
				<b><?php lblNo(11,$lblNcClr); ?>
				V4=>6<12</br>
				</b>
		</td>
		<td align="center" >
			<?php lblNo(12,$lblNcClr); ?>V5<12
		</td>

		<td align="center" style="width:6%">
			<?php lblNo('11 A',$lblNcClr); ?>
			Conv-Mb Cost</br>
			11-24-27</br>
		</td>

		<td align="center" style="width:3%">
			<?php lblNo(13,$lblNcClr); ?>Denier
		</td>
		<td align="center" style="width:4%">
			<?php lblNo(14,$lblNcClr); ?>Filament
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(15,$lblNcClr); ?>Intermingling
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(16,$lblNcClr); ?>Heatset
		</td>
		<td align="center" style="width:5%">
			<?php lblNo(17,$lblNcClr); ?>Cross Section
		</td>
		<td align="center" style="width:3%">
			<?php lblNo(18,$lblNcClr); ?>Lusture
		</td>
		<td align="center" style="width:15%">
			<?php lblNo(19,$lblNcClr); ?>MB Name
		</td>
		<td align="center" >
			<?php lblNo(20,$lblNcClr); ?>Chip
		</td>
		<td align="center" >
			<?php lblNo(21,$lblNcClr); ?>Packing type
		</td>
		<td align="center" >
			<?php lblNo(22,$lblNcClr); ?>Bobbin weight
		</td>
		<td align="center" >
			<?php lblNo(23,$lblNcClr); ?>no of Bobbins
		</td>
		<td align="center" >
			<?php lblNo(24,$lblNcClr); ?>Chip Rate
		</td>
		<td align="center" >
			<?php lblNo(25,$lblNcClr); ?>Dozing
		</td>
		<td align="center" >
			<?php lblNo(26,$lblNcClr); ?>MB Rate
		</td>
		<td align="center" ><?php lblNo(27,$lblNcClr); ?>MB cost
		</td>
		<td align="center" ><?php lblNo(28,$lblNcClr); ?>Change</br>
			Over Loss
		</td>
		<td align="center" >
			<?php lblNo(29,$lblNcClr); ?>Quality Loss
		</td>

		<td align="center" >
			<?php lblNo(30,$lblNcClr); ?>Intermigle Cost
		</td>
		<td align="center" >
			<?php lblNo(31,$lblNcClr); ?>Fixed Cost
		</td>

		<td align="center" >
			<?php lblNo(32,$lblNcClr); ?>Del-Pack cost
		</td>
		<td align="center" >
			<?php lblNo(33,$lblNcClr); ?>Final Ex</br>
			Factory</br>
			Cost
		</td>
		<td align="center" >
			<?php lblNo(34,$lblNcClr); ?>Fowarding
		</td>

		<td align="center">
			Left No </br>
			Product
		</td>
	</tr>
<?php
	$SqlView = "$sqlSelect
				from ($SqlView) d
				where REC_NO between $P_ROW_START and $P_ROW_END  ";
	if ($USER_NAME === "1949"){
		if ($CYL_TYPE_S === "MELANGE"){
			//die();
		}
		//echo "$SqlView</br>";
	}
	//echo "SqlView $SqlView</br>";
  $rsView = oci_parse($conn,$SqlView);
	oci_execute ($rsView);
	while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
		$btnDtl = "Process('DETAIL ".$rowRsView['CYL_SYS_ID']."')";
		$btnSmltn = "Process('SIMULATION ".$rowRsView['CYL_SYS_ID']."')";

//val data 11
		$valDt11 = "";
		if (!empty($rowRsView['FGET_V4'])){
			$valDt11 = $rowRsView['FGET_V4'];
		}
		$valDt11 = setNumber($conn,"Number",$valDt11,4);

//val data 27
		$valDt27 = 0;
		$TotRecDt = 0; $DtMb = 0; $DtRm = 0;
		if ($rowRsView['CYL_TYPE']==="MELANGE"){
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
				and YC.CYC_TOP_NO = 73 ";
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
			}
		}else{
			if (!empty($rowRsView['FGETMBCOST'])){
				$DtRm =	$rowRsView['FGETMBCOST'];
				//echo "cek1 $DtRm ";
			} else {
				$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.FGETMBCOST_RM('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
				echo $sqlDtRm;
				$DtRm =  getData($conn,$sqlDtRm);
			}
		}
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
$valDt11A = $valDt11 - $valDt24 - $valDt27;
//val data 11A

	?>
	<tr>
		<td align="center">
		<div class="tooltip">
			<!-- Detail -->
        	<button style="font-size:20px" onclick="<?php echo $btnDtl;  ?>"><i class="fa fa-file-pdf-o"
        		data-toggle="tooltip" data-placement="top" title="Detail Product"
        	></i></button>
        </div>
        &nbsp
        <div class="tooltip">
  			<!-- Detail -->
        	<button style="font-size:20px" onclick="<?php echo $btnSmltn;  ?>"
        			data-toggle="tooltip" data-placement="top" title="Simulation Product"
        	>
        		<i class="fa fa-calculator"></i></button>
        </div>
		</td>
<?php if ($byShadeMulti === "Y") { ?>
			<td align="center" >
			<?php echo $rowRsView['CYL_SHADE_NAME']; ?>
			</td>
<?php } ?>
		<td align="right" >
			<!-- 1 -->
			<?php echo $rowRsView['REC_NO']; ?>
		</td>
<?php if ($byType === "Y") { ?>
		<td align="left">
			<!-- Customer -->
			<?php
				if ($CUSTOMER_S==="NULL"){
					if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; }
				} else {
					echo $CUSTOMER_S;
				}

			?>
		</td>
		<td align="center">
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } else if ($byShade === "Y") { ?>
		<td align="center">
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td align="left" >
			<!-- Customer -->
			<?php if (!empty($rowRsView['CUSTOMER'])){ echo $rowRsView['CUSTOMER']; } ?>
		</td>
<?php } else if ($byCust === "Y") { ?>
		<td align="center" >
			<!-- Type -->
			<?php if (!empty($rowRsView['CYL_TYPE'])){ echo $rowRsView['CYL_TYPE']; } ?>
		</td>
		<td align="center" >
			<!-- Shade Name -->
			<?php
				if (!empty($rowRsView['CYL_SHADE_CODE'])){
					echo $rowRsView['CYL_SHADE_NAME']."-".$rowRsView['CYL_SHADE_CODE'];
				}
			?>
		</td>
<?php } ?>
		<td align="left" >
			<!-- 4 -->
			<?php if (!empty($rowRsView['CMY_NAME'])){ echo $rowRsView['CMY_NAME']; } ?>
		</td>

		<td align="center" >
			<!-- 5 -->
			<?php if (!empty($rowRsView['FGET_MCNAME'])){ echo $rowRsView['FGET_MCNAME']; } ?>
		</td>
		<td align="center" >
			<!-- 6 -->
			<?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?>
		</td>
		<td align="center" >
			<!-- 7 -->
			<?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?>
		</td>


		<td align="right">
			<!-- 8 -->
			<?php if (!empty($rowRsView['FGET_V1'])){

				//echo $rowRsView['FGET_V1']."</br>";
				echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);

			}?>
		</td>

		<td align="right"><!-- 9 -->
			<?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);  }?>
		</td>

		<td align="right"><!-- 10 -->
			<?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?>
		</td>

		<td align="right"
		 <?php echo "style='font-size: 18px;background-color:$ClrView11'"; ?>
		><!-- 11 -->
			<b><?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?></b>

		</td>

		<td align="right"><!-- 12 -->
			<?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?>
		</td>

		<td align="right"
				<?php echo "style='font-size: 18px;background-color:$ClrView11A'"; ?>
		><!-- 11A (11-24-27) -->
			<b>
				<?php
					echo setNumber($conn,"Number",$valDt11A,4);
				?>
			</b>
		</td>

		<td align="center">
			<!-- 13 -->
			<?php
				if (!empty($rowRsView['DENIER'])){
					echo $rowRsView['DENIER'];
				}
			?>
		</td>
		<td align="center">
			<!-- 14 -->
			<?php
				if (!empty($rowRsView['FILAMENT'])){
					echo $rowRsView['FILAMENT'];
				}
			?>
		</td>
		<td align="center">
			<!-- 15 -->
			<?php
				if (!empty($rowRsView['INTERMINGLING'])){
					echo $rowRsView['INTERMINGLING'];
				}
			?>
		</td>
		<td align="center">
			<!-- 16 -->
			<?php
				if (!empty($rowRsView['HEATSET'])){
					echo $rowRsView['HEATSET'];
				}
			?>
		</td>
		<td align="center">
			<!-- 17 -->
			<?php
				if (!empty($rowRsView['CROSS_SECTION'])){
					echo $rowRsView['CROSS_SECTION'];
				}
			?>
		</td>
		<td align="center">
			<!-- 18 -->
			<?php
				if (!empty($rowRsView['CMY_LUSTURE'])){
					echo $rowRsView['CMY_LUSTURE'];
				}
			?>
		</td>
		<td align="center">
			<!-- 19 -->
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
						and YC.CYC_TOP_NO = 64 ";
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
					if (!empty($rowRsView['FGETMBNAME'])){
							echo $rowRsView['FGETMBNAME'];
					} else {
						$sqlMbNmRaw = "select MGTAPPS.pkg_yarn_marketing.fgetmbname_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
						$MbNmRaw =  getData($conn,$sqlMbNmRaw);
						echo $MbNmRaw;
					}
				}
			?>
		</td>
		<td>
			<!-- 20 -->
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
		</td>
		<td align="center" >
			<!-- 21  Packing type -->
			<?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?>
		</td>
		<td align="center" >
			<!-- 22 Bobbin weight  -->
			<?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];  }?>
		</td>
		<td align="center" >
			<!-- 23 no of Bobbins -->
			<?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?>

		</td>
		<td align="center"
			<?php echo "style='font-size: 18px;background-color:$ClrView24'"; ?>
		>
			<!-- 24 -->
			<!-- fgetchip MGTAPPS.pkg_yarn_marketing.fgetchip('".$rowRsView['CYL_SYS_ID']."')  -->
			<b>
				<?php
				echo $valDt24;
				?>
			</b>
		</td>
		<td align="right">
			<!-- 25 -->
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
		</td>
		<td align="right">
			<!-- 26 -->
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
		</td>
		<td align="right"
			<?php echo "style='font-size: 18px;background-color:$ClrView27'"; ?>
		>
			<?php echo setNumber($conn,"Number",$valDt27,4); ?>
		</td>			<!-- 27 -->
		<td align="right">
			<!-- 28 -->
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
		</td>
		<td align="right">
			<!-- 29 -->
			<?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
			<!-- fgetCngOvrLst  MGTAPPS.pkg_yarn_marketing.fgetCngOvrLst('".$rowRsView['CYL_SYS_ID']."') -->
		</td>

		<td align="center" >
			<!-- 30 -->
			<?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);
			}?>
		</td>
		<td align="center" >
			<!-- 31 -->
			<?php if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }?>
		</td>

		<td align="right">
			<!-- 32 -->
			<?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){
					echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);
				}?>
			<!-- fgetCngOvrLst  MGTAPPS.pkg_yarn_marketing.fgetQualityLoss('".$rowRsView['CYL_SYS_ID']."')  -->
		</td>
		<td align="right">
			<!-- 33 -->
			<?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){
				echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
			}?>
			<!-- fgetCngOvrLst  MGTAPPS.pkg_yarn_marketing.fgetQualityLoss('".$rowRsView['CYL_SYS_ID']."')  -->
		</td>
		<td align="right">
			<!-- 34 -->
			<?php echo $CST_PRODUCT_FOWARDING; ?>
		</td>

		<td align="center">
			<?php echo $rowRsView['CYL_LEFT_NO']; ?>
		</td>
	</tr>
	<?php
	}
	?>
</table>
</div>
<?php echo $fotLbl; ?>
