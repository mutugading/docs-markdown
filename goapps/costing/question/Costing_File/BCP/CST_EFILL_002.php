<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	$SCAN_DOC_CST = "SCAN_DOC_CST";
	$fileIsReady = "N";
	//Get Parameter
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}

	//echo "STSPRS $STSPRS </br>";
	//Get Parameter

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	include ("form_header.php");
	include ("check_login.php");

	if ($FORM_NAME===""){
		if(isset($_POST['FORM_NAME'])) {
			$FORM_NAME=$_POST['FORM_NAME'];
		}
	}

	$ETD_SYS_ID_S="";
	if(isset($_POST['ETD_SYS_ID_S'])) {
		$ETD_SYS_ID_S=$_POST['ETD_SYS_ID_S'];
	}
	if ($ETD_SYS_ID_S===""){
		$ETD_SYS_ID_S = "NULL";
	}
	//echo "ETD_SYS_ID_S $ETD_SYS_ID_S<br>";

	$FOLDER_VAL_1_S="";
	if(isset($_POST['FOLDER_VAL_1_S'])) {
		$FOLDER_VAL_1_S=$_POST['FOLDER_VAL_1_S'];
	}
	if ($FOLDER_VAL_1_S===""){
		$FOLDER_VAL_1_S = "NULL";
	}

	$FOLDER_VAL_2_S="";
	if(isset($_POST['FOLDER_VAL_2_S'])) {
		$FOLDER_VAL_2_S=$_POST['FOLDER_VAL_2_S'];
	}
	if ($FOLDER_VAL_2_S===""){
		$FOLDER_VAL_2_S = "NULL";
	}

	$FOLDER_VAL_3_S="";
	if(isset($_POST['FOLDER_VAL_3_S'])) {
		$FOLDER_VAL_3_S=$_POST['FOLDER_VAL_3_S'];
	}
	if ($FOLDER_VAL_3_S===""){
		$FOLDER_VAL_3_S = "NULL";
	}

	$folderScanDoc = "";
	$FIND_DATA_S = "";
	include ($FORM_NAME."_SQL.php");
	$dirDest = "D:/XAMPP/htdocs/webapps/$SCAN_DOC_CST/$USER_NAME";
	//echo "$dirDest </br>";
	if (!is_dir($dirDest)){
		mkdir($dirDest, 0777, true);
		echo "directory $dirDest created </br>";
	}

	//upload
	if ($STSPRS==="UPLOAD") {
		//$files = $_FILES;

		$filename = "";
		$recDt = 0;
		if(isset($_FILES['listGambar']['name'][$recDt])){
			$filename = basename($_FILES['listGambar']['name'][$recDt]);
			move_uploaded_file($_FILES["listGambar"]["tmp_name"][$recDt],"$dirDest/$filename");
			//echo "filename $recDt $filename <br>";
			while ( $filename !=="") {
				if(isset($_FILES['listGambar']['name'][$recDt])){
					$filename = basename($_FILES['listGambar']['name'][$recDt]);
					echo "move $recDt filename $dirDest/$filename <br>";//	die();
					move_uploaded_file($_FILES["listGambar"]["tmp_name"][$recDt],"$dirDest/$filename");


					//Log File
					$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
					$USER_NAME_DTL = getData($conn,$sqlGetData);

					$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
								ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
								)
								values (
								'123','$USER_NAME',sysdate,'Upload File -> $filename ','$USER_NAME_DTL'
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
				} else {$filename = "";}

				$recDt++;
			}

		}

		echo "<script>alert('Data Successfully Uploaded !');</script>";

		echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
		echo "<input type='hidden' id='FORM_NAME' name='FORM_NAME' value=$FORM_NAME>";
		echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value=$USER_NAME>";
		echo "<input type='hidden' id='P_MENU_ID' name='P_MENU_ID' value=$P_MENU_ID>";
		echo "<input type='hidden' id='STSPRS' name='STSPRS' value=''>";
		echo "<input type='hidden' id='STSPRS' name='STSPRS' value='CLEAR_FILE'>";
		echo "</form>";
		//die();
		echo "<script type='text/javascript'>";
		echo "document.getElementById('$FORM_NAME').submit();";
		echo "</script>";
}
//Upload

//Move File
if ($STSPRS==="MOVE"){
	$dir = "D:/XAMPP/htdocs/webapps/$SCAN_DOC_CST/$USER_NAME";
	$filePrs = "";
	$noFile = 0;
	$vFileFrom = "";
 	$vFileDest = "";
 	$errPrs = "";
	if (is_dir($dir)){
			//echo "is dir $dir </br>";die();
	    if ($dh = opendir($dir)){
	    //echo "dh $dh ";die();
			while (($file = readdir($dh)) !== false){
				$filePrs = "";
				//echo "file $file </br>";die();
				//if ($filePrs!==""){

					$FOLDER_VAL_1_V = fGetFolderNm($conn,$FOLDER_VAL_1_S);
					$folderDest = "Doc_Folder/$FOLDER_VAL_1_V";
					//echo "FOLDER_VAL_1_S $FOLDER_VAL_1_S $folderDest";die();
					if ($FOLDER_VAL_2_S !== ""){
						$FOLDER_VAL_2_V = fGetFolderNm($conn,$FOLDER_VAL_2_S);
						$folderDest = "$folderDest/$FOLDER_VAL_2_V";
					}
					//echo "FOLDER_VAL_2_V $FOLDER_VAL_2_V $folderDest";die();
					// if ($FOLDER_VAL_3_S !== ""){
					// 	$FOLDER_VAL_3_V = fGetFolderNm($conn,$FOLDER_VAL_3_S);
					// 	$folderDest = "$folderDest/$FOLDER_VAL_3_V";
					// }
					//echo "FOLDER_VAL_3_V $FOLDER_VAL_3_V $folderDest";die();
					// if ($FOLDER_VAL_4_S !== ""){
					// 	$FOLDER_VAL_4_V = fGetFolderNm($conn,$FOLDER_VAL_4_S);
					// 	$folderDest = "$folderDest/$FOLDER_VAL_4_V";
					// }
					// if ($FOLDER_VAL_5_S !== ""){
					// 	$FOLDER_VAL_5_V = fGetFolderNm($conn,$FOLDER_VAL_5_S);
					// 	$folderDest = "$folderDest/$FOLDER_VAL_5_V";
					// }

					$vFileFrom = "$SCAN_DOC_CST/$USER_NAME/$file";
 					$vFileDest = "$folderDest/$file";

 					//echo "folderDest $folderDest : vFileFrom $vFileFrom";die();
					if (!is_dir($folderDest)){
						mkdir($folderDest, 0777, true);
						echo "<script>alert('directory $folderDest created !');</script>";
					}


					//$sqlGetData = "select pkg_efilling.fCkDestFolderFile('$folderDest','$file') GET_DATA from dual";
					//$stsFilePrs = getData($conn,$sqlGetData);

					//if ($stsFilePrs==="TRUE"){
					if (substr($file, 0,1)!=="."){
						//echo "folderDest $folderDest : file $file : vFileFrom $vFileFrom";die();
						try {
							$vFileFrom = "D:/XAMPP/htdocs/webapps/$vFileFrom";
							$vFileDest = "D:/XAMPP/htdocs/webapps/$vFileDest";
							echo "vFileFrom $vFileFrom : vFileDest $vFileDest</br>";
							copy($vFileFrom,$vFileDest);
							//die();
							unlink($vFileFrom);

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
						} catch (Exception $e) {

							//echo "<script>alert('Error".$e->getMessage()." !');</script>";
								echo 'Error When Move File ',  $e->getMessage(), "\n";
								$errPrs = "Y";
								die();
						}

					}
					//}
				//}
			}
		    closedir($dh);
		    //die();
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

		}
	}
//Move File
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>"
	enctype="multipart/form-data">
<div class="main" style="overflow-x:auto;">
<!-- DEFINE LAYOUT -->
<body>
	<?php include ($FORM_NAME."_UPLD.php"); ?>
	<?php
		include ($FORM_NAME."_LST_FILE.php");
		if ($fileIsReady === "Y"){
			include ($FORM_NAME."_MOVE_FILE.php");
		}
	?>
</body>
</html>
<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="FORM_NAME" id="FORM_NAME" value="<?php echo $FORM_NAME; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="ETD_SYS_ID_S" id="ETD_SYS_ID_S">

<input type="hidden" name="FOLDER_VAL_1_S" id="FOLDER_VAL_1_S">
<input type="hidden" name="FOLDER_VAL_2_S" id="FOLDER_VAL_2_S">
<input type="hidden" name="FOLDER_VAL_3_S" id="FOLDER_VAL_3_S">

<?php include ($FORM_NAME."_JS.php"); ?>
</form>
</body>
</html>
