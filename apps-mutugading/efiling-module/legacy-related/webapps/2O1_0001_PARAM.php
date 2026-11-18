<?php
	$page ="https://mgtapps.mutugading.com:4433/webapps/2O1_0001?P_MENU_ID=2O10000000";//real
	//$timeRefrsh = "60000";
	$timeRefrsh = "120000";//2mnt
	echo "<script> setTimeout(function() { window.location = '$page'; },$timeRefrsh); </script> ";

	$totRow = 1500;

	// $sqlDt = "select rownum NO_REC,t.*	from mgtdat.mgt_tp_provision_dn t
	// 					where TPD_STS_PRS is null or TPD_STS_DOC is null ";

	$sqlDt = "select rownum NO_REC,t.*
						from (
						select * from mgtdat.MGT_TRANSP_DETAIL_DN
						where  to_number(substr(MTDD_DN_NO,1,4)) >=2022
						and MTDD_DN_TXN_CODE in ('LDN','PDN')
						) t
	 					where MTDD_STS_PRS is null or MTDD_STS_DOC is null
						order by substr(MTDD_DN_NO,5)
						";
	//echo "$sqlDt </br>";
	$sqlList = "select * from ($sqlDt) t ";
	if ($totRow !== 0){
		$sqlList = "$sqlList where NO_REC <= $totRow ";
	}

	//echo "sqlList $sqlList";
	//D:\XAMPP\htdocs\webapps\Doc_Folder
	//D:/XAMPP/htdocs/webapps/Doc_Folder
	$pathFolder = "D:/XAMPP/htdocs/webapps/Doc_Folder";

	$sqlGet = "select count(-1) GET_DATA from ($sqlDt)";
	$totDt =  getData($conn,$sqlGet);

?>
<table style="width:100%" border="1">
	<tr><td align="center">Total Process <font color="red"><?php echo "$totDt </font>  Last Time Process <font color='red'> ". date("h:i:sa"); ?></font></td></tr>
<?php
	$rsDataList = oci_parse($conn,$sqlList);
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
		//$FileNm = $rowDataList['TPD_DN_NO'];
		$MTDD_DN_TXN_CODE = $rowDataList['MTDD_DN_TXN_CODE'];
		$MTDD_DN_NO  = $rowDataList['MTDD_DN_NO'];

		// $sqlGet = "select substr('$FileNm',1,instr('$FileNm','-')-1) GET_DATA from dual";
		// $docCode =  getData($conn,$sqlGet);
		$docCode =  $MTDD_DN_TXN_CODE;

		$FileNm = $MTDD_DN_TXN_CODE."-".$MTDD_DN_NO;

		//$sqlGet = "select substr('$FileNm',instr('$FileNm','-')+1,4) GET_DATA from dual";
		$sqlGet = "select substr('$MTDD_DN_NO',1,4) GET_DATA from dual";
		$yearPeriod =  getData($conn,$sqlGet);

		$fileCheck = $pathFolder."/".$docCode."/".$yearPeriod."/".$FileNm.".pdf";
		$existSts = "N";
		if (file_exists($fileCheck)) {
			$existSts = "Y";
		}

		//TPD_DN_NO, TPD_STS_PRS, TPD_STS_DOC
		$TPD_STS_PRS = "";
		$TPD_STS_DOC = "";
		$TPD_STS_DOC = $existSts;
		if ($existSts==="Y"){
			$TPD_STS_PRS = "Y";
		}
		$print = "Doc Code <font color='red'> $docCode </font>:"
							." File Name <font color='red'>$FileNm</font> : File Check <font color='red'>$fileCheck</font>"
							." : Exist Status <font color='red'>$existSts</font>";
		echo "<tr><td align='center'>$print</td></tr>";

		$sqlUpd =
				 // "update mgtdat.mgt_tp_provision_dn
					// 	set TPD_STS_DOC = '$TPD_STS_DOC'
					// 				,TPD_STS_PRS = '$TPD_STS_PRS'
					// where TPD_DN_NO = '$FileNm'";
					"update mgtdat.MGT_TRANSP_DETAIL_DN
 						set MTDD_STS_DOC = '$TPD_STS_DOC'
 									,MTDD_STS_PRS = '$TPD_STS_PRS'
 					where MTDD_DN_TXN_CODE = '$MTDD_DN_TXN_CODE'
					and MTDD_DN_NO = '$MTDD_DN_NO'";

			//echo "sqlUpd $sqlUpd";die();

			$rsIns = oci_parse($conn,$sqlUpd);
			oci_execute($rsIns, OCI_DEFAULT);
			if (!$rsIns) {
					//echo $sqlInsrt;
					$e = oci_error($rsIns);  // For oci_execute errors pass the statement handle
					print htmlentities($e['message']);
					print "\n<pre>\n";
					print htmlentities($e['sqltext']);
					printf("\n%".($e['offset']+1)."s", "^");
					print  "\n</pre>\n";
					die();
			}
			oci_commit($conn);
	}
?>
</table>
