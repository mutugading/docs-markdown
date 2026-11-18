<?php
include("../conOraOci.php"); include ("../common_function.php"); $font="16";
$FORM_NAME = ""; if(isset($_GET['FORM_NAME'])){$FORM_NAME=$_GET['FORM_NAME'];}
$USER_NAME = ""; if(isset($_GET['USER_NAME'])){$USER_NAME=$_GET['USER_NAME'];}
$P_MENU_ID = ""; if(isset($_GET['P_MENU_ID'])){$P_MENU_ID=$_GET['P_MENU_ID'];}
include ($FORM_NAME."_SQL.php");
$STS_LOV = ""; if(isset($_GET['STS_LOV'])){$STS_LOV=$_GET['STS_LOV'];}
$FIND_LOV = ""; if(isset($_GET['FIND_LOV'])){$FIND_LOV=$_GET['FIND_LOV'];}
$TRX_NO = ""; if(isset($_GET['TRX_NO'])){$TRX_NO=$_GET['TRX_NO'];}
//echo "FORM_NAME $FORM_NAME : USER_NAME $USER_NAME : P_MENU_ID $P_MENU_ID : TRX_NO $TRX_NO : STS_LOV $STS_LOV : FIND_LOV $FIND_LOV<br>";
//View Detail
//header

$sqlDt = getSqlDtl($TRX_NO);
//echo "$sqlDt<br>";

$sqlViewData = "select distinct TPB_TRX_NO,to_char(TPB_DT,'dd-mm-yyyy') TPB_DT, TPB_SUPP_NAME, TPB_BILL_NO,to_char(TPB_BILL_DT,'dd-mm-yyyy') TPB_BILL_DT from ($sqlDt) ";
//if ($USER_NAME==="1949"){echo "sqlViewData $sqlViewData</br>";	}
$rsDtHdr = oci_parse($conn,$sqlViewData);
oci_execute ($rsDtHdr);
while ($rowDtHdr = oci_fetch_array ($rsDtHdr, OCI_BOTH)) {
?>
<table style="width:100%">
	<tr>
		<td <?php echo getStyle($font,"right","50"); ?> >TRX NO :</td>
		<td <?php echo getStyle($font,"left"); ?> > <b><?php echo $rowDtHdr['TPB_TRX_NO']; ?></b> </td>
	</tr>
	<tr>
		<td <?php echo getStyle($font,"right"); ?> >Date :</td>
		<td <?php echo getStyle($font,"left"); ?> ><b><?php echo $rowDtHdr['TPB_DT']; ?></b></td>
	</tr>
	<tr>
		<td <?php echo getStyle($font,"right"); ?> >Supplier :</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<b><?php
				if (!empty($rowDtHdr['TPB_SUPP_NAME'])){
					echo $rowDtHdr['TPB_SUPP_NAME'];
				}
			?></b>
		</td>
	</tr>
	<tr>
		<td <?php echo getStyle($font,"right"); ?> >BILL NO :</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<b><?php
				if (!empty($rowDtHdr['TPB_BILL_NO'])){
					echo $rowDtHdr['TPB_BILL_NO']."</br>";
				}
			?></b>
		</td>
	</tr>
	<tr>
		<td <?php echo getStyle($font,"right"); ?> >BILL Date :</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<b><?php
				if (!empty($rowDtHdr['TPB_BILL_DT'])){
					echo $rowDtHdr['TPB_BILL_DT'];
				}
			?>
			</b>
		</td>
	</tr>
</table>
<?php
}
?>
<table style="width:100%" border="1">
	<tr>
		<td <?php echo getStyle($font,"center","5"); ?> >No</td> <!--1-->
		<td <?php echo getStyle($font,"center","10"); ?>  >TP no</td> <!--2-->
		<td <?php echo getStyle($font,"center","10"); ?> >TP Date</td> <!--3-->
		<td <?php echo getStyle($font,"center","10"); ?> >TP QTY</td> <!--4-->
		<td <?php echo getStyle($font,"center"); ?> >DN TXN CODE</td> <!--5-->
		<td <?php echo getStyle($font,"center","30"); ?> >DN NO</td> <!--6-->
		<td <?php echo getStyle($font,"center"); ?> >DN Date</td> <!--7-->
		<td <?php echo getStyle($font,"center"); ?>>DN QTY</td> <!--8-->
	</tr>
<?php
	$font="13";
	$sqlDtl = "select TPB_TRX_NO, TPB_DT, TPB_SUPP_NAME, TPB_BILL_NO, TPB_BILL_DT, TP_NO,
										to_char(TP_DT,'YYYY-MM-DD') TP_DT, TP_QTY, MTDD_DN_TXN_CODE, DN_NO,
										to_char(DN_DT,'YYYY-MM-DD') DN_DT, MTDD_DN_QTY,YY
						 from ($sqlDt)";
	// if ($USER_NAME==="1949"){echo "sqlDtl $sqlDtl</br>";}
	$Rectl = 0;
	$rsDtl = oci_parse($conn,$sqlDtl);
	oci_execute ($rsDtl);
	$linkdtl = ""; $linkFile = ""; $stsSame = "N"; $TP_NO_TMP = ""	;	$TP_DT = "";
	while ($rowDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
		$TP_NO = ""	;if (!empty($rowDtl['TP_NO'])){$TP_NO = $rowDtl['TP_NO'];}

		if ($Rectl===0){
			$stsSame = "N";
			$TP_NO_TMP = $TP_NO;
		} else {
			if ($TP_NO_TMP !== $TP_NO){
				$stsSame = "N";
				$TP_NO_TMP = $TP_NO;
			} else {
				$stsSame = "Y";
			}
		}

		//https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/NSBR/2021/NSBR-2021003748.pdf?P_MENU_ID=2G10000000
		$TP_NO = "";
		$TP_DT = "";
		$folderYY = "";	if (!empty($rowDtl['YY'])){$folderYY = $rowDtl['YY']; }

		$TpQty = "";
		if ($stsSame === "N"){
			$Rectl++;
			if (!empty($rowDtl['TP_NO'])){$TP_NO = $rowDtl['TP_NO'];}
			if (!empty($rowDtl['TP_DT'])){$TP_DT =$rowDtl['TP_DT'];}
			if (!empty($rowDtl['TP_QTY'])) {$TpQty = number_format($rowDtl['TP_QTY']);}
		}
		$folerDoc = "";if (!empty($rowDtl['MTDD_DN_TXN_CODE'])){$folerDoc	= $rowDtl['MTDD_DN_TXN_CODE'];}
		$nameDn 	= "";if (!empty($rowDtl['DN_NO'])){$nameDn 	= $rowDtl['DN_NO'];}
		$DN_DT 	= "";if (!empty($rowDtl['DN_DT'])){$DN_DT 	= $rowDtl['DN_DT'];}
		$MTDD_DN_QTY = "";if (!empty($rowDtl['MTDD_DN_QTY'])) {$MTDD_DN_QTY = number_format($rowDtl['MTDD_DN_QTY']);}

		$linkData 	= "webapps/Doc_Folder/$folerDoc/$folderYY/$nameDn".".PDF";
		$linkdtl 	= "https://mgtapps.mutugading.com:4433/$linkData";
		$linkFile 	= "D:/XAMPP/htdocs/$linkData";

		//echo "linkdtl $linkdtl</br>";
		//echo "linkFile $linkFile</br>";
?>
	<tr>
		<td <?php echo getStyle($font,"left"); ?> > <?php if ($stsSame==="N"){ echo $Rectl; } ?> </td>  <!--1-->
		<td <?php echo getStyle($font,"left"); ?> >  <?php echo $TP_NO; ?> </td>  <!--2-->
		<td <?php echo getStyle($font,"center"); ?> > <?php echo $TP_DT; ?> </td>  <!--3-->
		<td <?php echo getStyle($font,"right"); ?> > <?php echo $TpQty; ?> </td>  <!--4-->
		<td <?php echo getStyle($font,"center"); ?> > <?php echo $folerDoc; ?> </td>  <!--5-->
		<td <?php echo getStyle($font,"center"); ?> >
			<font color = "red"  size = '3'>
			<b>
		<?php
		if (file_exists($linkFile)) {
			$viewFile = "viewFile('$linkdtl','$TRX_NO')";
		?>
				<button onclick="<?php echo $viewFile; ?>"> <?php  echo $nameDn; ?> </button>
		<?php
		} else { echo "$nameDn (FILE NA)"; }
		?>
			</b>
			</font>
		</td> <!--6-->
		<td <?php echo getStyle($font,"center"); ?> > <?php echo $DN_DT; ?> </td> <!--7-->
		<td <?php echo getStyle($font,"right"); ?> > <?php echo $MTDD_DN_QTY; ?> </td> <!--8-->
	</tr>
<?php
	}
?>
</table>
<?php
//View Detail
