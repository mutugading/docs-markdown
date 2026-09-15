<?php
	$SqlGet = "select nvl(MGTAPPS.pkg_pty_simulation.getPoyLeftNo($smltnCylLeftNo),0) GET_DATA from dual";
	$smltnLeftNoPoy = getData($conn,$SqlGet);

	//process Spin_1
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,20),0) GET_DATA from dual";
	$Spin_1_20 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,7),0) GET_DATA from dual";
	$Spin_1_7 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,8),0) GET_DATA from dual";
	$Spin_1_8 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,57),0) GET_DATA from dual";
	$Spin_1_57 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,22),0) GET_DATA from dual";
	$Spin_1_22 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,10),0) GET_DATA from dual";
	$Spin_1_10 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,11),0) GET_DATA from dual";
	$Spin_1_11 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,21),0) GET_DATA from dual";
	$Spin_1_21 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,55),0) GET_DATA from dual";
	$Spin_1_55 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,72),0) GET_DATA from dual";
	$Spin_1_72 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,71),0) GET_DATA from dual";
	$Spin_1_71= getData($conn,$SqlGet);
	//process Spin_1

	//process tx_1
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,20),0) GET_DATA from dual";
	$tx_1_20 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,82),0) GET_DATA from dual";
	$tx_1_82 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,7),0) GET_DATA from dual";
	$tx_1_7 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,8),0) GET_DATA from dual";
	$tx_1_8 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,57),0) GET_DATA from dual";
	$tx_1_57 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,22),0) GET_DATA from dual";
	$tx_1_22 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,10),0) GET_DATA from dual";
	$tx_1_10 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,11),0) GET_DATA from dual";
	$tx_1_11 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,21),0) GET_DATA from dual";
	$tx_1_21 = getData($conn,$SqlGet);
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,55),0) GET_DATA from dual";
	$tx_1_55 = getData($conn,$SqlGet);
	//process tx_1

	if ((int)$SMLT_NORM_TX_10_S===0){
		$SMLT_NORM_TX_10_S = $tx_1_10;
	}

	if ((int)$SMLT_NORM_TX_11_S===0){
		$SMLT_NORM_TX_11_S = $tx_1_11;
	}

	//echo "SMLT_NORM_SPIN_55_S $SMLT_NORM_SPIN_55_S = Spin_1_55 $Spin_1_55 </br>";
	if ((int)$SMLT_NORM_SPIN_55_S===0){
		$SMLT_NORM_SPIN_55_S = $Spin_1_55;
	}
	//echo "SMLT_NORM_SPIN_55_S $SMLT_NORM_SPIN_55_S = Spin_1_55 $Spin_1_55 </br>";
	if ((int)$SMLT_NORM_SPIN_72_S===0){
		$SMLT_NORM_SPIN_72_S = $Spin_1_72;
	}
	if ((int)$SMLT_NORM_SPIN_71_S===0){
		$SMLT_NORM_SPIN_71_S = $Spin_1_71;
	}

	//process Spin_2
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,93),0) GET_DATA from dual";
	$Spin_2_10 = getData($conn,$SqlGet);
	$index = 91;
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,$index),0) GET_DATA from dual";
	$Spin_2_11 = getData($conn,$SqlGet);

	$Spin_2_21 = ($Spin_1_21/100)*$Spin_1_55;

	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,73),0) GET_DATA from dual";
	$Spin_73 = getData($conn,$SqlGet);
	$Spin_2_71 = $Spin_73;
	//process Spin_2

	//process tx_2
	$index = 91;
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index),0) GET_DATA from dual";
	$tx_2_10 = getData($conn,$SqlGet);
	$index = 91;
	$SqlGet = "select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index),0) GET_DATA from dual";
	$tx_2_11 = getData($conn,$SqlGet);

	$tx_2_21 = ($tx_1_21/100)*$tx_1_55;
	//process tx_2

	//Process Simulate Norms
	$SMLT_NORM_TX_55 = $tx_1_55 + ($SMLT_NORM_SPIN_55_S - $Spin_1_55);
	//Process Simulate Norms

	//process Spin_3
	$Spin_3_10 = 0;
	if ($SMLT_NORM_SPIN_10_S <> 0){
		$Spin_3_10 = $Spin_1_10 * ($Spin_2_10 / $SMLT_NORM_SPIN_10_S) ;
	}
	$Spin_3_11 = 0;
	if ($SMLT_NORM_SPIN_11_S<>0){
		$Spin_3_11 = $Spin_1_11 * ($Spin_2_11 / $SMLT_NORM_SPIN_11_S);
	}

	$Spin_3_55 = 0;
	if ($Spin_1_55<>0){
		$Spin_3_55 = $SMLT_NORM_SPIN_55_S/$Spin_1_55;
	}

	//echo "SMLT_NORM_SPIN_55_S $SMLT_NORM_SPIN_55_S</br>SMLT_NORM_SPIN_72_S $SMLT_NORM_SPIN_72_S</br>SMLT_NORM_SPIN_71_S $SMLT_NORM_SPIN_71_S</br>";

	if (($Spin_1_72*$Spin_1_71)*($SMLT_NORM_SPIN_72_S*$SMLT_NORM_SPIN_71_S) <> 0){
			$Spin_3_71 = $Spin_2_71/($Spin_1_72*$Spin_1_71)*($SMLT_NORM_SPIN_72_S*$SMLT_NORM_SPIN_71_S);
	} else {
		$Spin_3_71 = 0;
	}

	//process Spin_3

	//process tx_3
	$tx_3_10 = 0;
	if ($SMLT_NORM_TX_10_S<>0){
		//echo "((int)$tx_1_10 * (int)$tx_2_10 ) / (int)$SMLT_NORM_TX_10_S";
		$tx_3_10 = $tx_1_10 * ($tx_2_10 / $SMLT_NORM_TX_10_S) ;
	}
	$tx_3_11 = 0;
	if ($SMLT_NORM_TX_11_S<>0){
		$tx_3_11 = ($tx_1_11 * $tx_2_11 ) / $SMLT_NORM_TX_11_S;
	}

	//process tx_3

	$Spin_2_73= $Spin_1_72;

	//prs Inc Cost
	//$inc_cost_10 = ($tx_3_10 - $tx_2_10) + ($Spin_3_10 - $Spin_2_10);
	$inc_cost_10 = ($tx_3_10 - $tx_2_10) ;
	//$inc_cost_11 = ($tx_3_11 - $tx_2_11) + ($Spin_3_11 - $Spin_2_11);
	$inc_cost_11 = ($tx_3_11 - $tx_2_11);

	$inc_cost_55 = ($SMLT_NORM_SPIN_55_S - $Spin_1_55);//$Spin_3_55($Spin_3_55 - $Spin_2_55);
	//echo "($Spin_3_71 - $Spin_2_71) </br>";
	$inc_cost_71 = ($Spin_3_71 - $Spin_2_71);

	if ($Delpack_name_S!=="Choose Data"){
		  //echo "CMBBC_BOX_COST $CMBBC_BOX_COST + DELPACKINGCOST_1 $DELPACKINGCOST_1 </br>";
			$inc_cost_packing = $IncrmntCost;
	} else {
			//$inc_cost_packing = $boxRate_1 ;
			$inc_cost_packing = 0;
	}

	$inc_cost_tot_adjsmnt = $inc_cost_10 + $inc_cost_11 + $inc_cost_55 + $inc_cost_71 + $inc_cost_packing ;

	$VB1_1 = getData($conn,"select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,118),0)
																+MGTAPPS.pkg_yarn_marketing.getPrdFowarding GET_DATA
													from dual");
	$VB1_2 = getData($conn,"select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,119),0)
																+MGTAPPS.pkg_yarn_marketing.getPrdFowarding GET_DATA
													from dual");
	$VB1_3 = getData($conn,"select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,120),0)
																+MGTAPPS.pkg_yarn_marketing.getPrdFowarding GET_DATA
													from dual");
	$VB1_4 = getData($conn,"select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,121),0)
																+MGTAPPS.pkg_yarn_marketing.getPrdFowarding GET_DATA
												   from dual");
	$VB1_5 = getData($conn,"select nvl(pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,122),0)
																+MGTAPPS.pkg_yarn_marketing.getPrdFowarding GET_DATA
													 from dual");

	$VB2_1 = $VB1_1 + $inc_cost_tot_adjsmnt;
	$VB2_2 = $VB1_2 + $inc_cost_tot_adjsmnt;
	$VB2_3 = $VB1_3 + $inc_cost_tot_adjsmnt;
	$VB2_4 = $VB1_4 + $inc_cost_tot_adjsmnt;
	$VB2_5 = $VB1_5 + $inc_cost_tot_adjsmnt;
?>
