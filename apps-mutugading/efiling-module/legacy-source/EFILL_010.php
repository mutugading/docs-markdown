<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("conOraOci.php");
	include ("common_function.php");
	include ("FORM_NAME.php");

	clearstatcache();
	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

	$DATE_FROM_S = "";if(isset($_POST['DATE_FROM_S'])) {$DATE_FROM_S=$_POST['DATE_FROM_S'];}

	$DATE_TO_S = "";if(isset($_POST['DATE_TO_S'])) {$DATE_TO_S=$_POST['DATE_TO_S'];}

	if ($DATE_TO_S === ""){
		$curdate = date("Y-m-d");
		//echo "curdate $curdate</br>";
		$DATE_TO_S = $curdate;
	}

	$FILTER_SCLT_S = "";if(isset($_POST['FILTER_SCLT_S'])) {$FILTER_SCLT_S=$_POST['FILTER_SCLT_S'];}

	$FILTER_SCLT_VAL_S = "";if(isset($_POST['FILTER_SCLT_VAL_S'])) {$FILTER_SCLT_VAL_S=$_POST['FILTER_SCLT_VAL_S'];}

	$FILTER_SCLT_DATE_S= "";if(isset($_POST['FILTER_SCLT_DATE_S'])) {$FILTER_SCLT_DATE_S=$_POST['FILTER_SCLT_DATE_S'];}

	$OST_KEY_NO_S = "";if(isset($_POST['OST_KEY_NO_S'])) {$OST_KEY_NO_S=$_POST['OST_KEY_NO_S'];}

	$INV_NO_S = "";if(isset($_POST['INV_NO_S'])) {$INV_NO_S=$_POST['INV_NO_S'];}

	$DnFile_S= ""; 	if(isset($_POST['DnFile_S'])) {		$DnFile_S=$_POST['DnFile_S'];	}

	$invcFile_S= "";	if(isset($_POST['invcFile_S'])) {		$invcFile_S=$_POST['invcFile_S'];	}

	$FpFile_S= "";	if(isset($_POST['FpFile_S'])) {		$FpFile_S=$_POST['FpFile_S'];	}

	$KwiFile_S= "";	if(isset($_POST['KwiFile_S'])) {		$KwiFile_S=$_POST['KwiFile_S'];	}

	$FP_ADV_S= "";	if(isset($_POST['FP_ADV_S'])) {		$FP_ADV_S=$_POST['FP_ADV_S'];	}
	//echo "FP_ADV_S $FP_ADV_S";
	$P_PAGE_NO = "";	if(isset($_POST['P_PAGE_NO'])) {		$P_PAGE_NO=$_POST['P_PAGE_NO'];	}
	if ($P_PAGE_NO===""){
		$P_PAGE_NO= 1;
	}

	$P_LAST_PAGE_NO = "";
	$StsViewDt = "";
	$linkData 	= "D:/XAMPP/htdocs";
	$invcFile = "";
	$DnFile = "";

	//echo "STSPRS $STSPRS : KwiFile_S $KwiFile_S</br> ";
	//echo "STSPRS $STSPRS : invcFile_S $invcFile_S </br>";
	//echo "OST_KEY_NO_S $OST_KEY_NO_S ";
	//echo "FILTER_SCLT_S $FILTER_SCLT_S</br>";
	//Get Parameter

	//$dateH = date("H");
	//$date = date("Y-m-d");
	//$date = str_replace('-', '/', $date);

	$MenuHeader1 = "Y";
	if (substr($STSPRS,0,11) ==="MERGE FILE "){
		$MenuUtamaHdr = "";
	} else {
		$MenuUtamaHdr = "Y";
	}
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	if ($STSPRS === "VIEW_DATA" || $STSPRS === "FIND"){
		$MenuPrint = "Y";
	}

	$P_FILTER = $FILTER_SCLT_S;
	$P_FILTER_VALUE = "";

	if ($P_FILTER ==="Area User"){
		$P_FILTER_VALUE = $FILTER_SCLT_DATE_S;
	} else {
		$P_FILTER_VALUE = $FILTER_SCLT_VAL_S;
	}

	$MenuPrintHrefPdf = "";
	// $MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_DATE_FROM=$DATE_FROM_S&P_DATE_TO=$DATE_TO_S&P_MENU_ID=$P_MENU_ID&P_FILTER=$P_FILTER"
	// 					."&P_FILTER_VALUE=$P_FILTER_VALUE";
	$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_DATE_FROM=$DATE_FROM_S&P_DATE_TO=$DATE_TO_S&P_MENU_ID=$P_MENU_ID&P_FILTER=$P_FILTER"
											."&P_FILTER_VALUE=$P_FILTER_VALUE";


	include ("form_header.php");
	include ("check_login.php");

	$sqlGetData = 	"select IS_READ_ONLY GET_DATA
    							from    mgtapps.MST_GROUPS a,mgtapps.MST_GROUP_USERS b
    							where a.GROUPS_ID = b.GROUPS_ID
    							and a.GROUPS_NAME like 'INVOICE BREAKDOWN'
    							and b.USER_ID = '$USER_NAME'";
	$UserReadOnly = getData($conn,$sqlGetData);

	$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
	$USER_NAME_DTL = getData($conn,$sqlGetData);


	$usrAreaLgn = getData($conn,"select nvl(USER_AREA,'ALL') GET_DATA from mgtapps.mst_USERs where USER_ID = '$USER_NAME' ");
	//echo "usrAreaLgn $usrAreaLgn</br>";
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('DATE_FROM').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
	$sqlView = "";
	include($FORM_NAME."_SQL.php");
?>
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">

<!-- DEFINE LAYOUT -->
<?php
	//if ($USER_NAME==="1949"){echo "STSPRS $STSPRS substr($STSPRS,0,13) <br>";}

	if (substr($STSPRS,0,13) ==="VIEW_KWI_FILE"){
		$DetailAccess = "Invoice Breakdown -> View Kwitansi -> $KwiFile_S";
		pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
		echo "<font color = 'red'  size = '2.5'><b>$KwiFile_S</b></font>";
		echo "<iframe src='$KwiFile_S' width='100%' style='height:100%'></iframe>";
	}else if (substr($STSPRS,0,13) ==="VIEW_INV_FILE"){
		$DetailAccess = "Invoice Breakdown -> View Invoice File -> $invcFile_S";
		pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
		echo "<font color = 'red'  size = '2.5'><b>$invcFile_S</b></font>";
		echo "<iframe src='$invcFile_S' width='100%' style='height:100%'></iframe>";
	}else if (substr($STSPRS,0,18)==="VIEW_FP_SALES_FILE"){
		$DetailAccess = "Invoice Breakdown -> View Fp Sales File -> $FpFile_S";
		pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
		echo "<font color = 'red'  size = '2.5'><b>$FpFile_S</b></font>";
		echo "<iframe src='$FpFile_S' width='100%' style='height:100%'></iframe>";
	}else if (substr($STSPRS,0,13)==="VIEW_LDN_FILE"){
		$DnFileView = "D:/XAMPP/htdocs/".substr($DnFile_S,37);
		//echo "DnFileView $DnFileView</br>";
		if (file_exists( $DnFileView)) {
			$DetailAccess = "Invoice Breakdown -> View DN File -> $DnFile_S";
			pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL);
			echo "<font color = 'red'  size = '2.5'><b>$DnFile_S</b></font>";
			echo "<iframe src='$DnFile_S' width='100%' style='height:100%'></iframe>";
		} else {
?>
		<table  style="width:100%">
			<tr>
				<td align="center">
					<?php echo "Data File $DnFile_S not found ";?>
				</td>
			</tr>
		</table>
<?php
		}
	}else if (substr($STSPRS,0,11) ==="MERGE FILE " || $STSPRS==="MERGE FILEDTL"){
		include($FORM_NAME."_MERGE.php");
	} else {
		$sqlView =
			"
			select 	rownum REC_NO
							, INV_FOLDER
							,	INV_YY
							,	INV_NO,OST_KEY_NO
							,	INVH_DT
							, INVH_CUST_NAME
							, INVH_CURR_CODE
							, INVH_TERM_CODE
							, SALES_AMT
							,	SALES_TAX
							,	SALES_TOT_AMT
							,	FP_FILE
							,	DN_NO
							,	DN_FOLDER
							,	DN_YY
							,	FP_FOLDER
							,	FP_YY
							, KWITANSI
							, KWI_NO
							, 'KWI' KWI_FOLDER
							, KWI_YY ,KWI_NCA
			FROM ( $sqlData) where 1=1
			";

			$sqlWhere = "";
			if ($FILTER_SCLT_S==="Invoice No"){
				$sqlWhere = " AND upper(INV_NO) like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
			}
			if ($FILTER_SCLT_S==="Date"){
				$sqlWhere = " AND to_char(INVH_DT,'YYYY-MM-DD') = '$FILTER_SCLT_DATE_S' ";
			}
			if ($FILTER_SCLT_S==="Customer"){
				$sqlWhere = " AND upper(INVH_CUST_NAME) like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
			}
			if ($FILTER_SCLT_S==="Term Code"){
				$sqlWhere = " AND upper(INVH_TERM_CODE) like '%'||upper('$FILTER_SCLT_VAL_S')||'%'  ";
			}
			if ($FILTER_SCLT_S==="FP Sales"){
				$sqlWhere = " AND upper(FP_FILE) like '%'||upper('$FILTER_SCLT_VAL_S')||'%'  ";
			}
			if ($FILTER_SCLT_S==="DN No"){
				$sqlWhere = " AND upper(DN_NO) like '%'||upper('$FILTER_SCLT_VAL_S')||'%'  ";
			}
			if ($FILTER_SCLT_S==="Kwitansi No"){
				$sqlWhere = " AND upper(KWI_NO) like '%'||upper('$FILTER_SCLT_VAL_S')||'%'  ";
			}
			if ($FILTER_SCLT_S==="NCA"){
				$sqlWhere = " AND upper(KWI_NCA) like '%'||upper('$FILTER_SCLT_VAL_S')||'%'  ";
			}
			$sqlView = $sqlView.$sqlWhere;

			$StsViewDt =  getData($conn,"select count(-1) GET_DATA from ($sqlView)  ");

			$fontSize = "font-size:13px";

			//if ($USER_NAME==="1949"){ echo "$sqlView <br>"; }
			$totRows = $StsViewDt;
			$P_ROWS_DISP = 15;
	    $P_LAST_PAGE_NO = ($totRows / $P_ROWS_DISP);
	    //echo "P_LAST_PAGE_NO $P_LAST_PAGE_NO";
	    if (strpos($P_LAST_PAGE_NO,'.') > 0 ){
	    	$P_LAST_PAGE_NO = substr($P_LAST_PAGE_NO,0,strpos($P_LAST_PAGE_NO,'.'));
	    	$P_LAST_PAGE_NO = $P_LAST_PAGE_NO + 1;
	    }
	    if ($P_LAST_PAGE_NO==0){
	    	$P_LAST_PAGE_NO=1;
	    }
			if ($P_LAST_PAGE_NO<$P_PAGE_NO){
	    	$P_PAGE_NO = $P_LAST_PAGE_NO;
	    }

	  	if ($P_PAGE_NO=="1"){
		    $P_ROW_START  = 1;
		    $P_ROW_END    = $P_ROWS_DISP;
			} else {
		    $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
		    $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
			}
			//echo "$sqlView</br>";
			include ($FORM_NAME."_PARAM.php");
			if ($P_PAGE_NO > $P_LAST_PAGE_NO){
				$P_PAGE_NO = $P_LAST_PAGE_NO;
			}
			if ($OST_KEY_NO_S===""){
		  // header
	?>
	<div class="main" style="overflow-x:auto;overflow-y:auto;" onclick="closeNav()">
	<table style="width:100%" border="1">
		<tr>
			<td align="center">
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
	</div>
	<div class="main" style="overflow-x:auto;overflow-y:auto;" >
	<table style="width:100%" border="1">
		<tr>
			<td align="center" > <p>No</p> </td> <!--1-->
			<td align="center" style="width:10%"> <p>Invoice No</p> </td> <!--2-->
			<td align="center" style="width:7%"><p>Date</p></td><!--3-->
			<td align="center" style="width:20%"><p>Customer</p></td><!--4-->
			<td align="center"><p>Currency</p></td><!--5-->
			<td align="center" ><p>Term Code</p></td><!--6-->
			<td align="center"><p>Sales AMT</p></td><!--7-->
			<td align="center"><p>Sales TAX</p></td><!--8-->
			<td align="center"><p>Sales Total</p></td><!--9-->
			<td align="center" style="width:13%"><p>FP Sales</p></td><!--10-->
			<td align="center" style="width:10%"><p>DN No</p></td><!--11-->
			<td align="center" style="width:10%"><p>Kwitansi</p></td><!--12-->
			<td align="center" style="width:5%"><p>NCA</p></td><!--12 A-->
			<td align="center"><p>Matching Link</p></td><!--13-->
		</tr>
	<?php
		//echo "$sqlView </br>";
		//echo "FILTER_SCLT_S $FILTER_SCLT_S </br>";
		$sqlViewData = $sqlView;
		$RecNO = 0;
		$sqlViewData = "select * from ($sqlViewData) where REC_NO between $P_ROW_START and $P_ROW_END";
		if ($USER_NAME==="1949"){
			//echo "sqlViewData $sqlViewData</br>";
		}

		$rsDt = oci_parse($conn,$sqlViewData);
		oci_execute ($rsDt);
		//$PaymentNo = "";


		while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$stsinvcFile = "";
			$lstData 	= "Process('VIEW_DATA $RecNO')";
			$mrgData 	="Process('MERGE FILE $RecNO')";
			$invcFile = $linkData."/webapps/Doc_Folder/".$rowDt['INV_FOLDER']."/".$rowDt['INV_YY']."/".$rowDt['INV_NO'].".pdf";
			$linkInvcdtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDt['INV_FOLDER']."/".$rowDt['INV_YY']."/".$rowDt['INV_NO'].".pdf";
			$stsinvcFile = "";
			$INVH_DT_S 			= $rowDt['INVH_DT'];
			if (!empty($rowDt['INVH_CUST_NAME'])){
				$INVH_CUST_NAME_S= $rowDt['INVH_CUST_NAME'];
			}
			if (!empty($rowDt['INVH_CURR_CODE'])){
				$INVH_CURR_CODE_S = $rowDt['INVH_CURR_CODE'];
			}
			if (!empty($rowDt['INVH_TERM_CODE'])){
				$INVH_TERM_CODE_S = $rowDt['INVH_TERM_CODE'];
			}
			$RecNO++;
	?>
		<tr>
			<td align = "right" style="<?php echo $fontSize; ?>;">
				<p><?php echo $rowDt['REC_NO'];?></p>
			</td><!--1-->
			<td align = "center" >
				<p><b>
				<font color = "red"  size = '1.5'>
				<?php
				if (!empty($rowDt['INV_NO'])){
					if (file_exists($invcFile)) {
						$stsinvcFile = "Y";
						if ($UserReadOnly==="Y"){
							echo $rowDt['INV_NO'];
						} else {
						$prsInvFile = "Process('VIEW_INV_FILE $linkInvcdtl')";
				?>
					<input 	type="button"
									value="<?php  echo $rowDt['INV_NO'];?>"
									style="background-color: #81F79F;font-size: 12px;cursor: pointer;";
									onclick="<?php echo $prsInvFile; ?>">
					<?php
						}
					} else {
						echo $rowDt['INV_NO']." </br>(FILE NA)";
						$invcFile = "";
					}
				} else { $invcFile = ""; }
				?>
				</font>
				</b>
				<input type="hidden" name="INV_NO[]" id="INV_NO[]" value="<?php echo $rowDt['INV_NO']; ?>">
				<input type="hidden" name="OST_KEY_NO[]" id="OST_KEY_NO[]" value="<?php echo $rowDt['OST_KEY_NO']; ?>">
				<input type="hidden" name="invcFile[]" id="invcFile[]" value="<?php echo $invcFile; ?>">
				</p>
			</td><!--2-->
			<td align = "center" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['INVH_DT'])){
						echo $rowDt['INVH_DT'];
					}
				?></p>
			</td><!--3-->
			<td align = "left" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['INVH_CUST_NAME'])){
						echo $rowDt['INVH_CUST_NAME'];
					}
				?></p>
			</td><!--4-->
			<td align = "center" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['INVH_CURR_CODE'])){
						echo $rowDt['INVH_CURR_CODE'];
					}
				?></p>
			</td><!--5-->
			<td align = "center" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['INVH_TERM_CODE'])){
						echo $rowDt['INVH_TERM_CODE'];
					}
				?></p>
			</td><!--6-->
			<td align = "right" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['SALES_AMT'])){
						echo number_format($rowDt['SALES_AMT']);
					}
				?></p>
			</td><!--7-->
			<td align = "right" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['SALES_TAX'])){
						echo number_format($rowDt['SALES_TAX']);
					}
				?></p>
			</td><!--8-->
			<td align = "right" style="<?php echo $fontSize; ?>;">
				<p><?php
					if (!empty($rowDt['SALES_TOT_AMT'])){
						echo number_format($rowDt['SALES_TOT_AMT']);
					}
				?></p>
			</td><!--9-->
			<td align = "center">
				<p><b>
				<font color = "green"  size = '1.5'>
				<?php
				$stsFpFile = "";
				if (!empty($rowDt['FP_FILE'])){
					$FpFile = $linkData."/webapps/Doc_Folder/".$rowDt['FP_FOLDER']."/".$rowDt['FP_YY']."/".$rowDt['FP_FILE'].".pdf";
					//echo "$FpFile</br>";
					$linkFpdtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDt['FP_FOLDER']."/".$rowDt['FP_YY']."/".$rowDt['FP_FILE'].".pdf";
					if (file_exists($FpFile)) {
						$stsFpFile = "Y";
						 if ($UserReadOnly==="Y"){
							 echo $rowDt['FP_FILE'];
						 } else	{
						$prsFpSlsFile = "Process('VIEW_FP_SALES_FILE $linkFpdtl')";
				?>
					<input 	type="button"
									style="background-color: #F6CECE;font-size: 12px;cursor: pointer;";
									onclick="<?php echo $prsFpSlsFile; ?>"
									value="<?php  echo $rowDt['FP_FILE']; ?>">
				<?php
						}
					} else {
						echo $rowDt['FP_FILE']."</br> (FILE NA)";
						$FpFile = "";
					}
				} else { $FpFile = ""; }
				?>
				</font>
				</b>
				<?php
				?>
				<input type="hidden" name="FpFile[]" id="FpFile[]" value="<?php echo $FpFile; ?>">
				</p>
			</td><!--10-->
			<td align = "center">
				<p><b>
				<font color = "blue"  size = '1.5'>
				<?php
				$stsDnFile = "";
				if (!empty($rowDt['DN_NO'])){
					$DnFile = $linkData."/webapps/Doc_Folder/".$rowDt['DN_FOLDER']."/".$rowDt['DN_YY']."/".$rowDt['DN_NO'].".pdf";
					$DnSalesFile = $linkData."/webapps/Doc_Folder/".$rowDt['DN_FOLDER']." SALES/".$rowDt['DN_YY']."/".$rowDt['DN_NO'].".pdf";
					//echo "$DnSalesFile</br>";
					$linkDndtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDt['DN_FOLDER']."/".$rowDt['DN_YY']."/".$rowDt['DN_NO'].".pdf";
					$linkSalesDndtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDt['DN_FOLDER']." SALES/".$rowDt['DN_YY']."/".$rowDt['DN_NO'].".pdf";
					if (file_exists($DnFile)||file_exists($DnSalesFile)) {
						//echo "DnSalesFile $DnSalesFile : DnSalesFile $DnSalesFile</br>";
						$stsDnFile = "Y";
						if (file_exists($DnFile)){
							$prsLdnFile = "Process('VIEW_LDN_FILE $linkDndtl')";
						} else {
							$prsLdnFile = "Process('VIEW_LDN_FILE $linkSalesDndtl')";
							$DnFile = $DnSalesFile;
						}
						if ($UserReadOnly==="Y"){
							echo $rowDt['DN_NO'];
						}else{
						?>
							<input 	type="button"
											style="background-color: #ECCEF5;font-size: 12px;cursor: pointer;";
											onclick="<?php echo $prsLdnFile; ?>"
											value="<?php  echo $rowDt['DN_NO']; ?>">
				<?php
						}
					} else {
						echo $rowDt['DN_NO']."</br> (FILE NA)";
						$DnFile = "";
					}
				} else { $FpFile = ""; }
				?>
				</font>
				</b>
				<input type="hidden" name="DnFile[]" id="DnFile[]" value="<?php echo $DnFile; ?>">
				</p>
			</td><!--11-->
			<td align="center">
				<p>
					<?php
						$KwiFile = "";
						if (!empty($rowDt['KWI_NO'])){
							if (!empty($rowDt['KWI_FOLDER']) && !empty($rowDt['KWI_YY'])){
									$KwiFile = $linkData."/webapps/Doc_Folder/".$rowDt['KWI_FOLDER']."/".$rowDt['KWI_YY']."/".$rowDt['KWI_NO'].".pdf";
							}
							if (file_exists($KwiFile)) {
								$linkKwidtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDt['KWI_FOLDER']."/".$rowDt['KWI_YY']."/".$rowDt['KWI_NO'].".pdf";
								$prsKwiFile = "Process('VIEW_KWI_FILE $linkKwidtl')";
								if ($UserReadOnly==="Y"){
										echo $rowDt['KWI_NO'];
								} else {
					?>
								<input 	type="button"
												style="background-color: #A9BCF5;font-size: 12px;cursor: pointer;";
												value="<?php  echo $rowDt['KWI_NO']; ?>"
												onclick="<?php echo $prsKwiFile; ?>"
												>
				  <?php
								}
							} else {
								echo $rowDt['KWI_NO']."</br> (FILE NA)";
							}
					}
					?>
					<input type="hidden" name="KwiFile[]" id="KwiFile[]" value="<?php echo $KwiFile; ?>">
				</p>
			</td><!--12-->
			<td align="center" >
				<b><font size = '1'><?php if (!empty($rowDt['KWI_NCA'])){echo $rowDt['KWI_NCA'];} ?></font></b>
			</td><!--12 A-->
			<td align = "center" style="width:10%">
				<b>
				<input 	type="BUTTON" name="BTN_DTL[]" id="BTN_DTL[]" value="Detail"
								style="background-color: #F3F781;font-size: 12px;cursor: pointer;";
								onclick="<?php echo $lstData; ?>"
				>
			<?php
				if ($UserReadOnly==="N"){
			?>
				<input 	type="BUTTON" name="BTN_DTL[]" id="BTN_DTL[]" value="Merge File"
								style="background-color: #A9A9F5;font-size: 12px;cursor: pointer;";
								onclick="<?php echo $mrgData; ?>">
			<?php
				}
			?>
				</b>
			</td><!--13-->
		</tr>
	<?php
		}
	} else {
	//View Detail
	//$sqlViewData = "select distinct INV_NO, INVH_DT, INVH_CUST_NAME, INVH_CURR_CODE, INVH_TERM_CODE from ($sqlView and INV_NO = '$OST_KEY_NO_S') ";
	//$sqlViewData = $sqlDataDtl;
	?>
	<table style="width:100%">
	<?php
		$sqlDtlHdr = $sqlView." and INV_NO = '$INV_NO_S' ";
		//echo "sqlDtlHdr $sqlDtlHdr</br>";
		$rsDtlHdr = oci_parse($conn,$sqlDtlHdr);
		oci_execute ($rsDtlHdr);
		while ($rowDtHdr = oci_fetch_array ($rsDtlHdr, OCI_BOTH)) {
	?>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Invoice No :
			</td>
			<td>
			<font color = "red"  size = '1.5'>
			<b>
				<?php
					if (!empty($rowDtHdr['INV_NO'])){
						$invcFile = $linkData."/webapps/Doc_Folder/".$rowDtHdr['INV_FOLDER']."/".$rowDtHdr['INV_YY']."/".$rowDtHdr['INV_NO'].".pdf";
						$linkInvcdtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDtHdr['INV_FOLDER']."/".$rowDtHdr['INV_YY']."/".$rowDtHdr['INV_NO'].".pdf";
						if (file_exists($invcFile)){
							if ($UserReadOnly==="N"){
				?>
							<a href="<?php echo $linkInvcdtl ?>"><?php echo $rowDtHdr['INV_NO']; ?> </a>
				<?php
								} else {echo $rowDtHdr['INV_NO'];}
						}
					}
				?>
			</b>
			</font>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Date :
			</td>
			<td style="text-align: left;" >
				<b>	<?php if (!empty($rowDtHdr['INVH_DT'])){ echo $rowDtHdr['INVH_DT']; } ?> </b>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Customer :
			</td>
			<td style="text-align: left;" >
				<b>	<?php if (!empty($rowDtHdr['INVH_CUST_NAME'])){ echo $rowDtHdr['INVH_CUST_NAME']; } ?> </b>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Currency :
			</td>
			<td style="text-align: left;" >
				<b>	<?php if (!empty($rowDtHdr['INVH_CURR_CODE'])){ echo $rowDtHdr['INVH_CURR_CODE']; } ?> </b>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Term Code :
			</td>
			<td style="text-align: left;" >
				<b>	<?php if (!empty($rowDtHdr['INVH_TERM_CODE'])){ echo $rowDtHdr['INVH_TERM_CODE']; } ?> </b>
			</td>
		</tr>
		<tr>
			<td style="text-align: right;" style="width:50%">
				Sales Total Amt :
			</td>
			<td style="text-align: left;" >
				<b>	<?php if (!empty($rowDtHdr['SALES_TOT_AMT'])){ echo number_format($rowDtHdr['SALES_TOT_AMT']); } ?> </b>
			</td>
		</tr>

		<tr>
			<td style="text-align: right;" style="width:50%">
				DN No :
			</td>
			<td style="text-align: left;" >
			<font color = "blue"  size = '1.5'>
			<b>
				<?php
					if (!empty($rowDtHdr['DN_NO'])){
						$DnFile = $linkData."/webapps/Doc_Folder/".$rowDtHdr['DN_FOLDER']."/".$rowDtHdr['DN_YY']."/".$rowDtHdr['DN_NO'].".pdf";
						$linkDndtl 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDtHdr['DN_FOLDER']."/".$rowDtHdr['DN_YY']."/".$rowDtHdr['DN_NO'].".pdf";
						if (file_exists($DnFile)){
				?>
							<a href="<?php echo $linkDndtl ?>"><?php echo $rowDtHdr['DN_NO']; ?> </a>
				<?php
						}
					}
				?>
			</b>
			</font>
			</td>
		</tr>
	<?php
		}

		$MergeDtlMsg = "Process('MERGE FILEDTL')";
		$sqlDtl = "select rownum REC_NO,d.* from ($sqlDataDtl) d";
		//echo "$MergeDtlMsg </br>";
		if ($UserReadOnly==="N"){
	?>
		<tr>
			<td  align="right" style="width:50%">
				<input 	type="BUTTON" value="Merge File" class="BUTTON btn_process" onclick="<?php echo $MergeDtlMsg; ?>">
			</td>
			<td ></td>
		</tr>
<?php } ?>
	</table>

	<table style="width:100%" border="1">
		<tr>
			<td align="center" style="width:5%"> No</td>
			<td align="center"  >Match Trn Code</td>
			<td align="center" >Doc Date</td>
			<td align="center" >Match FC Amt</td>
			<td align="center" >FP Sales</td>

		</tr>
	<?php
		$sqlDtl = "select rownum REC_NO,d.* from ($sqlDataDtl) d";
		if ($USER_NAME==="1949"){
			//echo "$sqlDtl </br>";
		}
		$Recdtl = 0;
		$rsDtl = oci_parse($conn,$sqlDtl);
		oci_execute ($rsDtl);
		while ($rowDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
			$Recdtl++;
	?>
		<tr>
			<td align="center" style="width:5%"> <?php echo $rowDtl['REC_NO']; ?> </td>
			<td align="center"  > <?php echo $rowDtl['MATCH_TRN_CODE']; ?> </td>
			<td align="center"  > <?php echo $rowDtl['MATCH_DOC_DT']; ?> </td>
			<td align="right" > <?php echo number_format($rowDtl['MATCH_FC_AMT']); ?> </td>
			<td align="center">
				<font color = "green"  size = '1.5'>
				<b>
				<?php
					$FP_ADV = "";
					if (!empty($rowDtl['FP_ADV'])){
						$FP_ADV = $rowDtl['FP_ADV'];
						$FpSalesFile = $linkData."/webapps/Doc_Folder/".$rowDtl['EFILLING_FOLDER']."/".$rowDtl['FP_YY']."/".$FP_ADV.".pdf";
						$linkFpSales 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDtl['EFILLING_FOLDER']."/".$rowDtl['FP_YY']."/".$rowDtl['FP_ADV'].".pdf";
						//echo("$FpSalesFile </br>");
						if (file_exists($FpSalesFile)){
				?>
							<a href="<?php echo $linkFpSales ?>"><?php echo $FP_ADV; ?> </a>
				<?php
						} else {
							echo "$FP_ADV FILE NA";
						}
					}
				?>
				</b>
				<font color = "green"  size = '1.5'>
				<input type="hidden" name="FP_ADV[]" id="FP_ADV[]" value="<?php echo $FP_ADV; ?>">
				<input type="hidden" name="FpSalesFile[]" id="FpSalesFile[]" value="<?php echo $FpSalesFile; ?>">
			</td>
		</tr>
	<?php
		}
	?>
	</table>
	<?php
	}
}
?>

<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">

<input type="hidden" name="DATE_FROM_S" id="DATE_FROM_S">
<input type="hidden" name="DATE_TO_S" id="DATE_TO_S">

<input type="hidden" name="FILTER_SCLT_S" id="FILTER_SCLT_S">
<input type="hidden" name="FILTER_SCLT_VAL_S" id="FILTER_SCLT_VAL_S">
<input type="hidden" name="FILTER_SCLT_DATE_S" id="FILTER_SCLT_DATE_S">

<input type="hidden" name="OST_KEY_NO_S" id="OST_KEY_NO_S">
<input type="hidden" name="INV_NO_S" id="INV_NO_S">

<input type="hidden" name="DnFile_S" id="DnFile_S">

<input type="hidden" name="invcFile_S" id="invcFile_S">
<input type="hidden" name="FpFile_S" id="FpFile_S">
<input type="hidden" name="KwiFile_S" id="KwiFile_S">

<input type="hidden" name="FP_ADV_S" id="FP_ADV_S">

<?php include ($FORM_NAME."_JS.php"); ?>

</form>
</body>
</html>
