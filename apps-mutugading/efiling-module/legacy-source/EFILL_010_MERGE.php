<?php
	//echo "invcFile_S $invcFile_S</br>";
	$command = "D:\PDFtk_Server\bin\pdftk.exe";
	$FileMerge = "D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S."_MERGE.pdf";

	$linkFileMerge = "";

	$invcFile_Dest = "";
	$invcFile_dt ="";
	if ($invcFile_S!==""){
		if (file_exists($invcFile_S)) {
			$invcFile_dt = str_replace("/", "\\", $invcFile_S);
			$invcFile_Dest ="D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S."invfile.pdf";
			if ($USER_NAME==="1949"){echo "copy($invcFile_dt,$invcFile_Dest)<br>";}
			copy($invcFile_dt,$invcFile_Dest);
		}
	}
	if ($invcFile_Dest !== ""){
		$command = "$command $invcFile_Dest ";
	}

	$DnFile_Dest = "";
	$DnFile_dt = ""	;
	//echo "DnFile_S $DnFile_S</br>";
	if 	($DnFile_S!==""){
		if (file_exists($DnFile_S)) {
			$DnFile_dt = str_replace("/", "\\", $DnFile_S);
			$DnFile_Dest = "D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S."DnFile.pdf";
			copy($DnFile_dt, $DnFile_Dest);
		}
	}


	$FpFile_Dest = "";
	if (substr($STSPRS,0,11) ==="MERGE FILE "){
		$FpFile_Dest = "";
		$FpFile_dt = "";
		if ($FpFile_S!==""){
			if (file_exists($FpFile_S)) {
				$FpFile_dt = str_replace("/", "\\", $FpFile_S);
				$FpFile_Dest = "D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S."FpFile.pdf";
				copy($FpFile_dt, $FpFile_Dest);
			}
		}

		if ($FpFile_Dest !== ""){
			$command = "$command $FpFile_Dest ";
		}
		if ($DnFile_Dest !== ""){
			$command = "$command $DnFile_Dest ";
		}
		$command = "$command output $FileMerge";
		exec($command);
		if ($invcFile_Dest!==""){
			unlink($invcFile_Dest);
		}
		if ($DnFile_Dest!==""){
			unlink($DnFile_Dest);
		}
		if ($FpFile_Dest!==""){
			unlink($FpFile_Dest);
		}
		$linkFileMerge = "/webapps/Doc_Folder/MERGE_FILE/$INV_NO_S"."_MERGE.pdf";
		//log EFILL
		$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
					ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
					)
					values (
					'123','$USER_NAME',sysdate,'View Document -> $MENU_NAME -> $linkFileMerge','$USER_NAME_DTL'
					)";
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
		//Log EFILL

		//echo "$linkFileMerge </br>";
	} else 	if ($STSPRS ==="MERGE FILEDTL"){
		//echo "STSPRS $STSPRS/br>";
		//echo "FP_ADV_S $FP_ADV_S </br>".strpos($FP_ADV_S, ',');
		if ($FP_ADV_S!==""){
			if (strpos($FP_ADV_S, ',') !==false ){
				//multi FP
				//echo "multi FP FP_ADV_S $FP_ADV_S</br>";
				$FP_ADV_DT = $FP_ADV_S;
				$FpFile_S_DT = $FpFile_S;
				//echo "multi FP FP_ADV_DT $FP_ADV_DT</br>";
				//echo "multi FP FpFile_S_DT $FpFile_S_DT</br>";
				do {
					if (strpos($FP_ADV_DT, ',') !==false){
						$FpFile_lp = substr($FpFile_S_DT,0,strpos($FpFile_S_DT, ','));
						$FP_ADV_LP = substr($FP_ADV_DT,0,strpos($FP_ADV_DT, ','));

						$FpFile_S_DT= substr($FpFile_S_DT,strpos($FpFile_S_DT, ',')+1);
						$FP_ADV_DT = substr($FP_ADV_DT,strpos($FP_ADV_DT, ',')+1);
					} else {
						$FpFile_lp = $FpFile_S_DT;
						$FP_ADV_LP = $FP_ADV_DT;
						$FP_ADV_DT = "";
					}
					//echo "FpFile_lp $FpFile_lp : FP_ADV_LP $FP_ADV_LP </br>";

					//echo "FP_ADV_LP $FP_ADV_LP </br>";
					//echo "FP_ADV_DT $FP_ADV_DT </br>";

					$FpFile_Dest = "";
					$FpFile_dt = "";
					if ($FpFile_lp!==""){
						if (file_exists($FpFile_lp)) {
							$FpFile_dt = str_replace("/", "\\", $FpFile_lp);
							$FpFile_Dest = "D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S.$FP_ADV_LP."FpFile.pdf";
							//echo "copy($FpFile_dt, $FpFile_Dest)</br>";
							copy($FpFile_dt, $FpFile_Dest);

							$command = "$command $FpFile_Dest ";
						} else {
							echo "file $FpFile_lp not exist";
						}
					}


				} while ($FP_ADV_DT!=="");
			} else {
				//FP just one
				//echo "just one </br>";
				$FpFile_Dest = "";
				$FpFile_dt = "";
				if (file_exists($FpFile_S)) {
					$FpFile_dt = str_replace("/", "\\", $FpFile_S);
					$FpFile_Dest = "D:\\XAMPP\htdocs\webapps\Doc_Folder\MERGE_FILE\\".$INV_NO_S."FpFile.pdf";
					copy($FpFile_dt, $FpFile_Dest);
				}

				if ($FpFile_Dest !== ""){
					$command = "$command $FpFile_Dest ";
				}
			}
		}
		if ($DnFile_Dest !== ""){
			$command = "$command $DnFile_Dest ";
		}
		$command = "$command output $FileMerge";
		exec($command);
		if ($invcFile_Dest!==""){
			unlink($invcFile_Dest);
		}
		if ($DnFile_Dest!==""){
			unlink($DnFile_Dest);
		}
		if ($FpFile_Dest!==""){
			unlink($FpFile_Dest);
		}
		$linkFileMerge = "/webapps/Doc_Folder/MERGE_FILE/$INV_NO_S"."_MERGE.pdf";

		//log EFILL
		$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
					ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
					)
					values (
					'123','$USER_NAME',sysdate,'View Document -> $MENU_NAME -> $linkFileMerge','$USER_NAME_DTL'
					)";
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
		//Log EFILL

	}
	if ($linkFileMerge!=="") {
?>
		<font color = 'red'  size = '2.5'><b><?php echo $linkFileMerge; ?></b></font>
		<iframe src="<?php echo $linkFileMerge; ?>" width="100%" height="100%" style="border:2;">test
		</iframe>
<?php
	}
?>
