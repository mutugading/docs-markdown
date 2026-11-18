<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("conOraOci.php");	include ("../common_function.php"); include ("FORM_NAME.php"); include("EFILL_SQL.php");

	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

	//echo "STSPRS $STSPRS : FORM_NAME $FORM_NAME</br>";

	$FILE_S = "";	if(isset($_POST['FILE_S'])) {$FILE_S=$_POST['FILE_S'];}

	$ETD_SYS_ID_S = "NULL";if(isset($_POST['ETD_SYS_ID_S'])) {$ETD_SYS_ID_S=$_POST['ETD_SYS_ID_S'];}

	$FILE_FOLDER_S = "";if(isset($_POST['FILE_FOLDER_S'])) {$FILE_FOLDER_S=$_POST['FILE_FOLDER_S'];	}

	if ($FILE_FOLDER_S === "" ){$FILE_FOLDER_S = "NULL";}

	$FOLDER_VAL_1_S = "";	if(isset($_POST['FOLDER_VAL_1_S'])) {$FOLDER_VAL_1_S=$_POST['FOLDER_VAL_1_S'];}
	$FOLDER_VAL_1_V = "";	if(isset($_POST['FOLDER_VAL_1_V'])) {$FOLDER_VAL_1_V=$_POST['FOLDER_VAL_1_V'];}

	$FOLDER_VAL_2_S = "";	if(isset($_POST['FOLDER_VAL_2_S'])) {$FOLDER_VAL_2_S=$_POST['FOLDER_VAL_2_S'];}
	$FOLDER_VAL_2_V = "";	if(isset($_POST['FOLDER_VAL_2_V'])) {$FOLDER_VAL_2_V=$_POST['FOLDER_VAL_2_V'];}

	$FOLDER_VAL_3_S = "";	if(isset($_POST['FOLDER_VAL_3_S'])) {$FOLDER_VAL_3_S=$_POST['FOLDER_VAL_3_S'];}
	$FOLDER_VAL_3_V = "";	if(isset($_POST['FOLDER_VAL_2_V'])) {$FOLDER_VAL_3_V=$_POST['FOLDER_VAL_3_V'];}

	$FOLDER_VAL_4_S = "";	if(isset($_POST['FOLDER_VAL_4_S'])) {$FOLDER_VAL_4_S=$_POST['FOLDER_VAL_4_S'];}
	$FOLDER_VAL_4_V = "";	if(isset($_POST['FOLDER_VAL_4_V'])) {$FOLDER_VAL_4_V=$_POST['FOLDER_VAL_4_V'];}

	$FOLDER_VAL_5_S = ""; if(isset($_POST['FOLDER_VAL_5_S'])) {$FOLDER_VAL_5_S=$_POST['FOLDER_VAL_5_S'];}
	$FOLDER_VAL_5_V = ""; if(isset($_POST['FOLDER_VAL_5_V'])) {$FOLDER_VAL_5_V=$_POST['FOLDER_VAL_5_V'];}

	$FIND_DATA_S=""; if(isset($_POST['FIND_DATA_S'])) {$FIND_DATA_S=$_POST['FIND_DATA_S'];}
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";

	//$dir = "D:/XAMPP/htdocs/webapps/SCAN_DOC";
	$dir = "D:/XAMPP/htdocs/webapps/";

	$sqlGetData = "select param_value GET_DATA from MST_PARAMS where PARAM_ID = 'EFILL_FOLDER_SCAN'";
	$folderScanDoc = getData($conn,$sqlGetData);

	$dir = $dir.$folderScanDoc;
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";
	//$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";

	include ("form_header.php");
	include ("check_login.php");

	$totDisp = 7;

if ($STSPRS==="MOVE"){

	//$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc/$USER_NAME";
	$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc/$USER_NAME";
	// if ($USER_NAME==="RCS1283"){echo "source User Upload : $dir/</br>";die();}
	$filePrs = "";
	$noFile = 0;
	$vFileFrom = "";
 	$vFileDest = "";
 	$errPrs = "File and Folder Data not Same";
	if (is_dir($dir)){
		//echo "is dir $dir </br>";
    if ($dh = opendir($dir)){
	    //echo "Open $dir $dh<br> ";
			while (($file = readdir($dh)) !== false){
				if (substr($file,0,1)!=="."){
					$sql = "select MGTAPPS.pkg_effil_prs.fGetFormatFile('$ETD_SYS_ID_S') GET_DATA from dual";
					$ETD_FORMAT_FILE = getData($conn,$sql);
					$lenFormat = strlen($ETD_FORMAT_FILE);
					$fileCheck = substr($file,0,$lenFormat);
					//if ($USER_NAME==="RCS1283"){ echo "file $file : ETD_FORMAT_FILE $ETD_FORMAT_FILE : fileCheck $fileCheck </br>"; die();}
					$filePrs = "";
					if ( $fileCheck === $ETD_FORMAT_FILE){
						//echo "fileCheck $fileCheck : ETD_FORMAT_FILE $ETD_FORMAT_FILE : file $file </br>";
						$filePrs = $file;
						//echo "filePrs $filePrs</br>";
					}

					if ($filePrs!==""){

						$errPrs = "";
						$folderDest = "D:/XAMPP/htdocs/webapps/Doc_Folder/$FOLDER_VAL_1_V";
						// if ($USER_NAME==="RCS1283"){ echo "FOLDER_VAL_1_V $FOLDER_VAL_1_V : FOLDER_VAL_2_V $FOLDER_VAL_2_V<br>"; die();}
						if ($FOLDER_VAL_2_S !== ""){
							$folderDest = "$folderDest/$FOLDER_VAL_2_V";
							if ($FOLDER_VAL_1_V==="LDN SALES"){
								$fileCek_1 = "LDN-".$FOLDER_VAL_2_V;
							} else if ($FOLDER_VAL_1_V==="PDN SALES"){
								$fileCek_1 = "PDN-".$FOLDER_VAL_2_V;
							} else if ($FOLDER_VAL_1_V==="FP SALES"){
								$fileCek_1 = "FP-".$FOLDER_VAL_2_V;
							} else if ($FOLDER_VAL_1_V==="JWDN SALES"){
								$fileCek_1 = "JWDN-".$FOLDER_VAL_2_V;
							} else {
								$fileCek_1 = $FOLDER_VAL_1_V."-".$FOLDER_VAL_2_V;
							}
							//if ($USER_NAME==="RCS1283"){ echo "folderDest $folderDest<br>"; die();}
							$lnfileCek = strlen($fileCek_1);
							$fileCek_2 = substr($file,0,$lnfileCek);
							//echo "FOLDER_VAL_1_V $FOLDER_VAL_1_V : fileCek_1 $fileCek_1 : fileCek_2 $fileCek_2<br>";
							if ($fileCek_1 !== $fileCek_2){
								$stsCekFile = "Y";

								//Cek Exclude Format EFILL
								$sqlCk = "select * from mst_param_data where mpd_mpdk_key = 'EXCLUDE_DOC_CHECK_EFILL' and MPD_VALUE='$FOLDER_VAL_1_V' ";
								//echo "sqlCk $sqlCk<br>";
							  $rsDataCk = oci_parse($conn,$sqlCk);   //get access folder
								oci_execute ($rsDataCk);
								while ($rowDataCk = oci_fetch_array ($rsDataCk, OCI_BOTH)) {
									$stsCekFile = "";
								}
								//Cek Exclude Format EFILL

								// if ($FOLDER_VAL_1_V==="FP SALES" || $FOLDER_VAL_1_V==="KWI"  || $FOLDER_VAL_1_V==="SJGRN" || $FOLDER_VAL_1_V==="MCM" || $FOLDER_VAL_1_V==="PIB" ){
								// 	$stsCekFile = "";
								// }
								if ($stsCekFile === "Y"){
									$errPrs = "fileCek_1 $fileCek_1 : fileCek_2 $fileCek_2 not same";
								}
							}
						}
						if ($FOLDER_VAL_3_S !== ""){
							$folderDest = "$folderDest/$FOLDER_VAL_3_V";
						}
						if ($FOLDER_VAL_4_S !== ""){
							$folderDest = "$folderDest/$FOLDER_VAL_4_V";
						}
						if ($FOLDER_VAL_5_S !== ""){
							$folderDest = "$folderDest/$FOLDER_VAL_5_V";
						}

						if ($errPrs===""){

							$vFileFrom = "D:/XAMPP/htdocs/webapps/$folderScanDoc/$USER_NAME/$file";
							$vFileDest = "$folderDest/$file";

							//echo "vFileFrom $vFileFrom : vFileDest $vFileDest</br>";

							if (!is_dir($folderDest)){
								 mkdir($folderDest, 0777, true);
								 echo "<script>alert('directory $folderDest created !');</script>";
							}
							try {
							    //echo "vFileFrom $vFileFrom : vFileDest $vFileDest</br>";//die();
									if (!copy($vFileFrom,$vFileDest)) {
							        $errPrs = "vFileFrom $vFileFrom : vFileDest $vFileDest\n";
							    } else {
										unlink($vFileFrom);
							    }

									if ($errPrs ===""){
										//Log File
										$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
										$USER_NAME_DTL = getData($conn,$sqlGetData);

										$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
													ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
													)
													values (
													'123','$USER_NAME',sysdate,'Move File -> From : $vFileFrom into : $vFileDest ','$USER_NAME_DTL'
													)";
										//echo "$sqlIns </br>";//die();
										$result=oci_parse($conn,$sqlIns);
										if (!$result) {
										   $oerr = OCIError($conn);
										   echo "Fetch Code 1:".$oerr["message"];
										   exit;
										    die();
										}
										$prs = oci_execute($result);
										if (!$prs) {
										    $e = oci_error($result);  // For oci_execute errors pass the statement handle
										    print htmlentities($e['message']);
										    print "\n<pre>\n";
										    print htmlentities($e['sqltext']);
										    printf("\n%".($e['offset']+1)."s", "^");
										    print  "\n</pre>\n";
										    die();
										}
										//Log File
									}
								} catch (Exception $e) {

									//echo "<script>alert('Error".$e->getMessage()." !');</script>";
										echo 'Error When Move File ',  $e->getMessage(), "\n";
										$errPrs = "Y";
										die();
								}
						}
					}
				//echo "<br>";
				}
			}
		  closedir($dh);

		}
	}

		if ($errPrs ==="" ){

			echo "<script>alert('Process Complete !');</script>";
   		echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
   		echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
    	echo "<input type='hidden' id='STSPRS' name='STSPRS' value=''>";
			echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
			echo "<input type='hidden' name='ETD_SYS_ID_S' id='ETD_SYS_ID_S' value='$ETD_SYS_ID_S'>";
    	echo "</form>";

			echo "<script type='text/javascript'>";
   		echo "document.getElementById('$FORM_NAME').submit();";
    	echo "</script>";

		} else {
			viewBlink($errPrs,$font="5");
		}
	}
	$font = "13";
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('NIK').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
	//echo "$USER_NAME </br>";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
<input type="hidden" name="FILE_FROM_S" id="FILE_FROM_S" echo="<?php echo $FILE_FROM_S; ?>" >
<input type="hidden" name="FILE_TO_S" id="FILE_TO_S"  echo="<?php echo $FILE_TO_S; ?>">
<!-- DEFINE LAYOUT -->
<table style="width:100%" >
<!-- Type Data -->
<?php
	//get access folder
	$sqlList = getSqlFolder($conn,$USER_NAME);
	//get access folder
?>
	<tr>
		<td <?php echo getStyle($font,"center","50")?> colspan="<?php echo $totDisp; ?>">
		Type Data  :
			<select name="ETD_SYS_ID" id="ETD_SYS_ID" onchange="Process('')">
					<option value="NULL">NULL</option>
<?php
  $rsDataList = oci_parse($conn,$sqlList);   //get access folder
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
				<option value="<?php echo $rowDataList['ETD_SYS_ID']; ?>"
					<?php if($ETD_SYS_ID_S==$rowDataList['ETD_SYS_ID']){ echo "selected";}?>
					>
						<?php echo $rowDataList['ETD_SYS_ID']; ?>
				</option>
<?php
	}
?>
			</select>
			<button type="button" data-toggle="modal" data-target="#myModal" onclick="viewLovData('VIEW_FOLDER')">...</button>
			&nbsp&nbsp
			Search Data
			<input 	type="text" id="FIND_DATA" name="FIND_DATA" value="<?php echo $FIND_DATA_S; ?>"
					 style="width:300px;height:22px;text-align:left"
					onkeypress="Javascript: if (event.keyCode==13) Process('');"
			>
			<input type="button" id="CLEAR" name="CLEAR" value="CLEAR" class="BUTTON btn_process" onclick="Process('CLEAR')" >
		</td>
	</tr>
<?php
$StsFound = "N";
if ($ETD_SYS_ID_S!=="NULL"){
	$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc/$USER_NAME";
	//$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc";
	//echo "dir $dir</br>";
	if (is_dir($dir)){
		//echo "In dir $dir</br>";
    if ($dh = opendir($dir)){	    	//$recDt=-1;
			while (($file = readdir($dh)) !== false){
				//echo "file $file<br>";
				$sql = "select nvl(MGTAPPS.pkg_effil_prs.fGetFormatFile('$ETD_SYS_ID_S'),'NULL') GET_DATA from dual"; //echo "$sql </br>";
				$ETD_FORMAT_FILE = getData($conn,$sql);
				//echo "ETD_FORMAT_FILE $ETD_FORMAT_FILE<br>";
				//echo "file $file ETD_FORMAT_FILE $ETD_FORMAT_FILE FIND_DATA_S $FIND_DATA_S posisi ".strpos($ETD_FORMAT_FILE,$file)." </br>";
				//if (strpos($file,$ETD_SYS_ID_S) !== FALSE) {
				if ($ETD_FORMAT_FILE !== "NULL"){
					//echo "ETD_FORMAT_FILE $ETD_FORMAT_FILE : file $file<br>";
					if (strpos($file,$ETD_FORMAT_FILE) !== FALSE) {
						//echo "KETEMU</br>";
						if ($FIND_DATA_S!==""){
							if (strpos($file,$FIND_DATA_S) !== FALSE) {
								$StsFound = "Y";
							}
						} else {
							$StsFound = "Y";
						}
					}

				}
			}
		    closedir($dh);
		}
	}
	//echo "StsFound $StsFound<br>";
	if ($StsFound === "Y"){
		include("EFILL_002_FOLDER.PHP");
	} else {
		if ($FIND_DATA_S === ""){
			$msgBlink = "File Type Data $ETD_SYS_ID_S in Folder Scan User $USER_NAME Not Found<br>(Format File $ETD_FORMAT_FILE)";
		} else {
			$msgBlink = "File Type Data $ETD_SYS_ID_S with search condition $FIND_DATA_S in Folder Scan User $USER_NAME Not Found<br>(Format File $ETD_FORMAT_FILE)";
		}
		?>

		<td align="center">
			<?php echo viewBlink($msgBlink,$font="5"); ?>
		</td>
		<?php
	}
}
?>
<!-- Type Data -->
	<tr>
<?php
	//echo "ETD_SYS_ID_S $ETD_SYS_ID_S </br>";
	if ($ETD_SYS_ID_S!=="NULL"){
		$sql = "select nvl(ETD_FORMAT_FILE,'NULL') GET_DATA from EFILL_TYPE_DATA where ETD_SYS_ID = '$ETD_SYS_ID_S'";
		$ETD_FORMAT_FILE = getData($conn,$sql);
		//echo "dir $dir ETD_FORMAT_FILE .$ETD_FORMAT_FILE </br>";
		//echo "length ETD_FORMAT_FILE ".strlen(str_replace('"','',$ETD_FORMAT_FILE));
		if ($ETD_FORMAT_FILE!=="NULL"){
			if (is_dir($dir)){
		  		if ($dh = opendir($dir)){
		  		$recNo = 0;
	  			$recDisp = 0;
			  	$fileNo = 1;
			  	$color = "";
			  	$stsDt = "";
		    	while (($file = readdir($dh)) !== false){
		    		if ($FIND_DATA_S!==""){
						if (strpos($file,$FIND_DATA_S) !== FALSE) {
							$stsDt = 1;//true
			    		} else {
			    			$stsDt = 0;//true
			    		}
		    		} else {
		    			$stsDt = 1;//true
		    		}

		    		if ($stsDt===1)	{
			    		if (substr($file,0,strlen(str_replace('"','',$ETD_FORMAT_FILE))) === str_replace('"','',$ETD_FORMAT_FILE) ){
		    				$recDisp=$recDisp + 1;
			    			//echo "$file </br>";
			    			if ($recDisp===1){
			    				echo "<tr>";
			    				$color = "black";
			    			}
			    			if ($recDisp===2){$color = "Green";}
							if ($recDisp===3){$color = "Purple";}
							if ($recDisp===4){$color = "Olive";}
							if ($recDisp===5){$color = "red";}
							if ($recDisp===6){$color = "blue";}
							if ($recDisp===7){$color = "gray";}

						if ($recDisp<=$totDisp){
							echo "<td style='width:10%'> <font color = '$color'  size = '2'>$fileNo. $file</font></td>";
						}
						if ($recDisp===$totDisp){
			    			echo "</tr>";
							$recDisp=0;
						}
						$fileNo = $fileNo + 1;
						}
		    		}
			    }
			    closedir($dh);
				}
			}
		}
	}
?>
	</tr>
</table>

<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="FILE_S" id="FILE_S">
<input type="hidden" name="FOLDER_S" id="FOLDER_S">
<input type="hidden" name="ETD_SYS_ID_S" id="ETD_SYS_ID_S">
<input type="hidden" name="ETD_SYS_ID_S_OLD" id="ETD_SYS_ID_S_OLD" value="<?php echo $ETD_SYS_ID_S; ?>">
<input type="hidden" name="FOLDER_VAL_1_S" id="FOLDER_VAL_1_S">
<input type="hidden" name="FOLDER_VAL_1_V" id="FOLDER_VAL_1_V">

<input type="hidden" name="FOLDER_VAL_2_S" id="FOLDER_VAL_2_S">
<input type="hidden" name="FOLDER_VAL_2_V" id="FOLDER_VAL_2_V">

<input type="hidden" name="FOLDER_VAL_3_S" id="FOLDER_VAL_3_S">
<input type="hidden" name="FOLDER_VAL_3_V" id="FOLDER_VAL_3_V">

<input type="hidden" name="FOLDER_VAL_4_S" id="FOLDER_VAL_4_S">
<input type="hidden" name="FOLDER_VAL_4_V" id="FOLDER_VAL_4_V">

<input type="hidden" name="FOLDER_VAL_5_S" id="FOLDER_VAL_5_S">
<input type="hidden" name="FOLDER_VAL_5_V" id="FOLDER_VAL_5_V">

<input type="hidden" name="FILE_FOLDER_S" id="FILE_FOLDER_S">
<input type="hidden" name="FIND_DATA_S" id="FIND_DATA_S">
<?php
	//include("EFILL_002_JSCRIPT.php");
	include($FORM_NAME."_LOV_JS.php");
	include($FORM_NAME."_JS.php");
?>
</form>
</body>
</html>
