<?php 
	$bgClrEntry = "style='height:20px; width:100px; background-color:yellow'";

	$SMLT_NORM_SPIN_10_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_10_S'])) {
		$SMLT_NORM_SPIN_10_S=$_POST['SMLT_NORM_SPIN_10_S'];
	}
	if($SMLT_NORM_SPIN_10_S === ""){
		$SMLT_NORM_SPIN_10_S = 0;
	}

	$SMLT_NORM_TX_10_S = "";
	if(isset($_POST['SMLT_NORM_TX_10_S'])) {
		$SMLT_NORM_TX_10_S=$_POST['SMLT_NORM_TX_10_S'];
	}
	if($SMLT_NORM_TX_10_S === ""){
		$SMLT_NORM_TX_10_S = 0;
	}

	$SMLT_NORM_SPIN_11_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_11_S'])) {
		$SMLT_NORM_SPIN_11_S=$_POST['SMLT_NORM_SPIN_11_S'];
	}
	if($SMLT_NORM_SPIN_11_S === ""){
		$SMLT_NORM_SPIN_11_S = 0;
	}

	$SMLT_NORM_TX_11_S = "";
	if(isset($_POST['SMLT_NORM_TX_11_S'])) {
		$SMLT_NORM_TX_11_S=$_POST['SMLT_NORM_TX_11_S'];
	}
	if($SMLT_NORM_TX_11_S === ""){
		$SMLT_NORM_TX_11_S = 0;
	}
	
	$SMLT_NORM_SPIN_21_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_21_S'])) {
		$SMLT_NORM_SPIN_21_S=$_POST['SMLT_NORM_SPIN_21_S'];
	}
	if ($SMLT_NORM_SPIN_21_S === ""){
		$SMLT_NORM_SPIN_21_S = 0;
	}


	$SMLT_NORM_TX_21_S = "";
	if(isset($_POST['SMLT_NORM_TX_21_S'])) {
		$SMLT_NORM_TX_21_S=$_POST['SMLT_NORM_TX_21_S'];
	}
	if ($SMLT_NORM_TX_21_S === ""){
		$SMLT_NORM_TX_21_S = 0;
	}

	$SMLT_NORM_SPIN_55_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_55_S'])) {
		$SMLT_NORM_SPIN_55_S=$_POST['SMLT_NORM_SPIN_55_S'];
	}
	if ($SMLT_NORM_SPIN_55_S === ""){
		$SMLT_NORM_SPIN_55_S = 0;
	}

	$SMLT_NORM_SPIN_72_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_72_S'])) {
		$SMLT_NORM_SPIN_72_S=$_POST['SMLT_NORM_SPIN_72_S'];
	}
	if($SMLT_NORM_SPIN_72_S === ""){
		$SMLT_NORM_SPIN_72_S = 0;
	}

	$SMLT_NORM_SPIN_71_S = "";
	if(isset($_POST['SMLT_NORM_SPIN_71_S'])) {
		$SMLT_NORM_SPIN_71_S=$_POST['SMLT_NORM_SPIN_71_S'];
	}
	if($SMLT_NORM_SPIN_71_S === ""){
		$SMLT_NORM_SPIN_71_S = 0;
	}

	echo "STSPRS $STSPRS : SMLT_NORM_SPIN_10_S $SMLT_NORM_SPIN_10_S : SMLT_NORM_SPIN_71_S $SMLT_NORM_SPIN_71_S";	
?>
<input type="hidden" name="SMLT_NORM_SPIN_10_S" id="SMLT_NORM_SPIN_10_S">	
<input type="hidden" name="SMLT_NORM_TX_10_S" id="SMLT_NORM_TX_10_S">	

<input type="hidden" name="SMLT_NORM_SPIN_11_S" id="SMLT_NORM_SPIN_11_S">	
<input type="hidden" name="SMLT_NORM_TX_11_S" id="SMLT_NORM_TX_11_S">	

<input type="hidden" name="SMLT_NORM_SPIN_21_S" id="SMLT_NORM_SPIN_21_S">	
<input type="hidden" name="SMLT_NORM_TX_21_S" id="SMLT_NORM_TX_21_S">	

<input type="hidden" name="SMLT_NORM_SPIN_55_S" id="SMLT_NORM_SPIN_55_S">	

<input type="hidden" name="SMLT_NORM_SPIN_72_S" id="SMLT_NORM_SPIN_72_S">	

<input type="hidden" name="SMLT_NORM_SPIN_71_S" id="SMLT_NORM_SPIN_71_S">	

<table style="width:95%">
		<tr>
			<td align="left">
				<b><font color="red" face="Verdana, Geneva, sans-serif" size="+1">PTY Simulation</font></b>
			</td>
		</tr>
	</table>

	<table style="width:95%" border="1" align="center">
		<tr>
			<td align="center" rowspan="2">
				Index
			</td>
			<td align="center" rowspan="2">
				Description
			</td>
			<td align="center" rowspan="2">
				Impact on
			</td>
			<td align="center" colspan="2">
				Present Norms
			</td>
			<td align="center" colspan="2">
				Present Cost
			</td>
			<td align="center" colspan="2">
				Simulated Norms
			</td>
			<td align="center" colspan="2">
				Simulated Cost
			</td>
			<td align="center" rowspan="2">
				Inc/ (Dec)
				</br>in Cost
			</td>													
		</tr>
		<tr>

			<td align="center">				
				Spinning	
			</td>
			<td align="center">
				TX	
			</td>
			<td align="center">
				Spinning	
			</td>
			<td align="center">
				TX		
			</td>
			<td align="center">
				Spinning	
			</td>
			<td align="center">
				TX	
			</td>
			<td align="center">
				Spinning	
			</td>
			<td align="center">
				TX	
			</td>												
		</tr>	
		<!-- Data -->
		<!-- 20	RM Input -->
<?php
	include("CST_FIND_PRODUCT_PTY_SMLTN_PRS.php");
?>
		<tr>
			<td align="center" >
				<?php
					$index = 20;
					echo $index;
				?>
			</td>
			<td align="center" >
				RM Input
			</td>
			<td align="center" >
				
			</td>
			<td align="center" >
				<?php 
					$SqlGet = "select MGTAPPS.pkg_pty_simulation.getPoyLeftNo($smltnCylLeftNo) GET_DATA from dual";
					$smltnLeftNoPoy = getData($conn,$SqlGet);							
					echo "($smltnLeftNoPoy)</br>$Spin_1_20";
				?>
			</td>
			<td align="center" >
				<?php 
					$SqlGet = "select pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index) GET_DATA from dual";
					$tx_1_20 = getData($conn,$SqlGet);		
					echo $tx_1_20;
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>
		</tr>
		<!-- Data -->
		<!-- 82	Production -->
		<tr>
			<td align="center" >
				<?php
					$index = 82;
					echo $index;
				?>				
			</td>
			<td align="center" >
				Production
			</td>
			<td align="center" >
				
			</td>
			<td align="center" >
			</td>
			<td align="center" >
				<?php 										
					echo setDecimal($conn,$tx_1_82,3);
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>														
		</tr>
		<!-- 7	Machine -->
		<tr>
			<td align="center" >
				<?php
					$index = 7;
					echo $index;
				?>								
			</td>
			<td align="center" >
				Machine
			</td>
			<td align="center" >
			</td>
			<td align="center" >
				<?php 					
					echo $Spin_1_7;
				?>								
			</td>
			<td align="center" >
				<?php 						
					echo $tx_1_7;
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>														
		</tr>
		<!-- 8	Position/End -->
		<tr>
			<td align="center" >
				<?php
					$index = 8;
					echo $index;
				?>								
			</td>
			<td align="center" >
				Position/End 
			</td>
			<td align="center" >								
			</td>
			<td align="center" >
				<?php 					
					echo setDecimal($conn,$Spin_1_8,3);		
				?>				
			</td>
			<td align="center" >
				<?php 				
					echo setDecimal($conn,$tx_1_8,3);		
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>														
		</tr>
		<!-- 57	RM Norm -->
		<tr>
			<td align="center" >
				<?php
					$index = 57;
					echo $index;
				?>								
			</td>
			<td align="center" >
				RM Norm
			</td>
			<td align="center" >							
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_1_57,3);			
				?>
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_1_57,3);			
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>		
		<!-- 22	OPU -->
		<tr>
			<td align="center" >
				<?php
					$index = 22;
					echo $index;
				?>								
			</td>
			<td align="center" >
				OPU
			</td>
			<td align="center" >	
				Oil-61						
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_1_22,3);		
				?>
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_1_22,3);			
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>															
		</tr>
		<!-- 10	Efficiency	FC-91 -->
		<tr>
			<td align="center" >
				<?php
					$index = 10;
					echo $index;
				?><!--1-->
			</td>
			<td align="center" >
				Efficiency
			</td><!--2-->
			<td align="center" >	
				FC-91						
			</td><!--3-->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_1_10,3);			
				?>
			</td><!--4-->
			<td align="center">
				<?php 
					echo setDecimal($conn,$tx_1_10,3);				
				?>
			</td><!--5-->
			<td align="center" >
				<?php 
					//$index = 91;					
					echo setDecimal($conn,$Spin_2_10,3);					
				?>
			</td><!--6-->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_2_10,3);					
				?>
			</td><!--7-->
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_SPIN_10" name="SMLT_NORM_SPIN_10" 
						<?php echo $bgClrEntry; ?>
						value="<?php echo $SMLT_NORM_SPIN_10_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>
			</td><!--8-->		
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_TX_10" name="SMLT_NORM_TX_10" 
						<?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_TX_10_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>				
			</td><!--9-->
			<td align="center" >		
				<?php 
					echo setDecimal($conn,$Spin_3_10,3)
				?>
			</td><!--10-->													
			<td align="center" >				
				<?php 
					echo setDecimal($conn,$tx_3_10,3)
				?>
			</td><!--11-->
			<td align="center" >				
				<?php 
					echo setDecimal($conn,$inc_cost_10,3)
				?>
			</td>															
		</tr>
		<!-- 11	Speed	FC-91 -->
		<tr>
			<td align="center" >
				<?php
					$index = 11;
					echo $index;
				?>								
			</td><!-- 1 -->
			<td align="center" >
				Speed
			</td><!-- 2 -->
			<td align="center" >	
				FC-91						
			</td><!-- 3 -->
			<td align="center" >
				<?php 							
					//echo $Spin_1_11;
					//setNumber($conn,"Number",$boxRate_1,3);
					echo setDecimal($conn,$Spin_1_11,3);
				?>
			</td><!-- 4 -->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_1_11,3);
				?>
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_2_11,3);	
				?>
			</td><!--6-->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_2_11,3);
				?>
			</td><!--7-->
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_SPIN_11" name="SMLT_NORM_SPIN_11"  <?php echo $bgClrEntry; ?>  
						value="<?php echo $SMLT_NORM_SPIN_11_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "				
				>
			</td><!--8-->		
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_TX_11" name="SMLT_NORM_TX_11" <?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_TX_11_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>				
			</td><!--9-->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_3_11,3)
				?>
			</td><!--10-->																						
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_3_11,3)
				?>
			</td><!--11-->
			<td align="center" >				
				<?php 
					echo setDecimal($conn,$inc_cost_11,3)
				?>				
			</td>															
		</tr>
		<!-- 21	Waste	RM (56x57)+58 -->
		<tr>
			<td align="center" >
				<?php
					$index = 21;
					echo $index;
				?>								
			</td>
			<td align="center" >
				Waste
			</td>
			<td align="center" >	
				RM (56x57)+58						
			</td>
			<td align="center" >
				<?php 					
					echo setDecimal($conn,$Spin_1_21,3);	
				?>
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_1_21,3);	
				?>
			</td>
			<td align="center" >
				<?php 					
					// $index = 56;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_56 = getData($conn,$SqlGet);	
					// $index = 57;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_57 = getData($conn,$SqlGet);	
					// $index = 58;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_58 = getData($conn,$SqlGet);	
					// $Spin_2_21 = ($val_56 * $val_57 ) + $val_58;
					echo setDecimal($conn,$Spin_2_21,5);	
				?>
			</td><!--6-->
			<td align="center" ><?php 					
					// $index = 56;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_56 = getData($conn,$SqlGet);	
					// $index = 57;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_57 = getData($conn,$SqlGet);	
					// $index = 58;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_tx($smltnCylLeftNo,$index) GET_DATA from dual";
					// $val_58 = getData($conn,$SqlGet);	
					// $tx_2_21 = ($val_56 * $val_57 ) + $val_58;
					echo setDecimal($conn,$tx_2_21,5);	
				?>
			</td><!--7-->
			<td align="center"  >
				<input 	type="text" id="SMLT_NORM_SPIN_21" name="SMLT_NORM_SPIN_21" <?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_SPIN_21_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>
			</td><!--8-->		
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_TX_21" name="SMLT_NORM_TX_21" <?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_TX_21_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>				
			</td><!--9-->
			<td align="center" >	
				<?php 
					//echo "($SMLT_NORM_SPIN_21_S/100)*$SMLT_NORM_SPIN_55_S";
					echo setDecimal($conn,$Spin_3_21,3)
				?>			
			</td>													
			<td align="center" >				
				<?php 
					echo setDecimal($conn,$tx_3_21,3)
				?>	
			</td>
			<td align="center" >		
				<?php 
					echo setDecimal($conn,$inc_cost_21,3)
				?>		
			</td>															
		</tr>
		<!-- 55	RM Rate -->
		<tr>
			<td align="center" >
				<?php
					$index = 55;
					echo $index;
				?>								
			</td>
			<td align="center" >
				RM Rate
			</td>
			<td align="center" >									
			</td>
			<td align="center" >
				<?php 					
					echo setDecimal($conn,$Spin_1_55,3);		
				?>
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$tx_1_55,3);		
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_SPIN_55" name="SMLT_NORM_SPIN_55" <?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_SPIN_55_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>
			</td><!--8-->		
			<td align="center" >	
				<?php 
					// $SMLT_NORM_TX_55_S = $tx_1_55 + ($SMLT_NORM_SPIN_55_S - $Spin_1_55);
					echo setDecimal($conn,$SMLT_NORM_TX_55,3)
				?>			
			</td><!--9-->
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_3_55,3)
				?>
			</td><!--10-->										
			<td align="center" >				
			</td>
			<td align="center" >	
			<?php 
					echo setDecimal($conn,$inc_cost_55,3)
				?>			
			</td>															
		</tr>
		<!-- 72	MB Rate -->
		<tr>
			<td align="center" >
				<?php
					$index = 72;
					echo $index;
				?>								
			</td>
			<td align="center" >
				MB Rate
			</td>
			<td align="center" >									
			</td>
			<td align="center" >
				<?php 
					echo setDecimal($conn,$Spin_1_72,3);		
				?>
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center"  >
				<input 	type="text" id="SMLT_NORM_SPIN_72" name="SMLT_NORM_SPIN_72" <?php echo $bgClrEntry; ?> 						
						value="<?php echo $SMLT_NORM_SPIN_72_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>
			</td><!--8-->		
			<td align="center" >
				
			</td><!--9-->			
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>		
		<!-- 71	MB Dozing	MB-73 -->
		<tr>
			<td align="center" >
				<?php
					$index = 71;
					echo $index;
				?>								
			</td><!-- 1 -->
			<td align="center" >
				MB Dozing
			</td><!-- 2 -->
			<td align="center" >									
			</td><!-- 3 -->
			<td align="center" >
				<b>
				<?php 
					//echo "Spin_1_72 $Spin_1_72 : Spin_1_73 $Spin_1_73";
					echo setDecimal($conn,$Spin_1_71,3);			
				?>
				</b>
			</td><!-- 4 -->
			<td align="center" >
			</td><!-- 5 -->
			<td align="center" >
			<?php 
					$index = 73;
					// $SqlGet = "select pkg_pty_simulation.fGetVal_Spinning($smltnCylLeftNo,$index) GET_DATA from dual";
					// $Spin_2_73 = getData($conn,$SqlGet);						
					echo setDecimal($conn,$Spin_2_71,3);			
			?><!-- 6 -->
			<td align="center" >
			</td>
			<td align="center" >
				<input 	type="text" id="SMLT_NORM_SPIN_71" name="SMLT_NORM_SPIN_71" <?php echo $bgClrEntry; ?> 
						value="<?php echo $SMLT_NORM_SPIN_71_S; ?>" 
					   	onkeypress="Javascript: if (event.keyCode==13) Process(''); "
				>
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>																
		</tr>
		<!-- Packing cost simulated-->
		<tr>
			<td align="center" colspan="3">
				<font color="red" face="Verdana, Geneva, sans-serif">Packing cost simulated</font>				
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>																
		</tr>
		<!-- Packing cost simulated-->
		<tr>
			<td align="center" colspan="3">
				Total adjustments
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>
			<td align="center" >
			</td>		
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>													
			<td align="center" >				
			</td>
			<td align="center" >				
			</td>																
		</tr>
		<!-- Data -->
	</table>