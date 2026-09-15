<?php
	function locFileEfilling(){
		return "D:/XAMPP/htdocs/webapps/Doc_Folder/";
	}

	function webFileEfilling(){
		return "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";
	}

	function locFileKwiBreakDown(){
		return "D:\\XAMPP\htdocs\webapps\Doc_Folder\KWITANSI_BREAKDOWN\\";
	}

	function cekExistFileScan($locFile,$folderFile,$folderYear,$fileName,$fileExtention) {
		$return = "N";
		$locFile	= "D:/XAMPP/htdocs/webapps/Doc_Folder/";
		$fileCheck	= $locFile;
		if ($folderFile!==""){
			$fileCheck	= $fileCheck."/".$folderFile;
		}
		if ($folderYear!==""){
			$fileCheck	= $fileCheck."/".$folderYear;
		}

		$fileCheck	= $fileCheck."/".$fileName.".".$fileExtention;
		//$fileCheck	= $locFile.$folderFile."/".$folderYear."/".$fileName.".".$fileExtention;
		//echo "fileCheck $fileCheck</br>";
		if (file_exists($fileCheck)){
			$return = "Y";
		}
		return $return;
	}

	function ckUserPwd(	$USER_NAME
						,$USER_PWD
						,$STS_PRS // 2 check pwd
					  )
		{
			$return = "0";
			//$sqldata =	"Select count(-1) TOT_DATA from MST_USERS where USER_ID = '".$username."' ";
			if ($STS_PRS=="2") //check pwd
			{
				//$sqldata =	$sqldata." and USER_PASSWORD = ENKRIPSI.acak('".$password."')";
			}

			$sqldata =	"Select count(-1) TOT_DATA from MST_USERS where USER_ID = 'ADMIN' ";

		 	$result = odbc_exec($conn,$sql);

			while(odbc_fetch_row($result)){
			         for($i=1;$i<=odbc_num_fields($result);$i++){
			        echo "Result is ".odbc_result($result,$i);
			    }
			}

			return null;
		}

	function getDtEmp($conn,$USER_ID)
		{
			//echo "USER_ID -> $USER_ID";
			$sqlUsr =
				"select m.USER_NAME,USER_DEPT
				from mst_users m
				where USER_ID = '$USER_ID'
				";

			$rsUsr = oci_parse($conn,$sqlUsr);
			oci_execute ($rsUsr);
			while ($rsRowUsr = oci_fetch_array ($rsUsr, OCI_BOTH)) {
				$USER_NAME_REQ=$rsRowUsr['USER_NAME'];
				$USER_DEPT = $rsRowUsr['USER_DEPT'];
				echo $sqlUsr." ".$USER_NAME_REQ." ".$USER_DEPT;
			}
			return null;
		}

	function getSqlHslpType($conn){
		//echo "USER_ID -> $USER_ID";
		/*$sql =  "select PARAM_VALUE from mst_params where param_id = 'HSLP_TYPE'";
		$return = "";

		$rsDt = oci_parse($conn,$sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['PARAM_VALUE'];
		}*/

		$return =  "SELECT mpd_value key_value, mpd_name key_id FROM mst_param_data t   WHERE t.mpd_mpdk_key = 'HSLP_TYPE' ORDER BY TO_NUMBER (mpd_name) ";
		return $return;
	}

	function getSqlParams($conn,$ParamId){
		//echo "USER_ID -> $USER_ID";
		$sql =  "select PARAM_VALUE from mst_params where param_id = '$ParamId'";
		$return = "0";

		$rsDt = oci_parse($conn,$sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['PARAM_VALUE'];
		}
		//echo "return $return";
		return $return;
	}

	function getTotData($conn,$Sql){
		$return = 0;
		//echo $Sql;
		$rsDt = oci_parse($conn,$Sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['TOT_DATA'];
		}
		if ($return===""){
			$return = 0;
		}
		return $return;
	}

	function getData($conn,$Sql,$USER_NAME = ""){
		$return = "";
		if ($USER_NAME === "1949"){
			echo "$Sql </br>";
		}

		$rsDt = oci_parse($conn,$Sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['GET_DATA'];
		}
		return $return;
	}

	function getDataUser($conn,$Sql,$USER_NAME){
		$return = "";
		if ($USER_NAME==="1949"){
			//echo "$Sql </br>";//die();
		}
		$rsDt = oci_parse($conn,$Sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['GET_DATA'];
		}
		return $return;
	}

	function getDescHslpType($conn,$HslpType){
		//echo "USER_ID -> $USER_ID";
		$sql =  getSqlHslpType($conn);
		$return = "";
		$sql = "select * from ($sql) where key_id=$HslpType";
		//echo $sql;
		$rsDt = oci_parse($conn,$sql);
		oci_execute ($rsDt);
		while ($rsRowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
			$return=$rsRowDt['KEY_VALUE'];
		}
		return $return;
	}

	function getMenuName($conn,$MENU_ID)
		{
			//echo "USER_ID -> $USER_ID";
			$return = "";
			$sqlMenu =
				"select MENU_NAME from MST_MENUS m
				where MENU_ID = '$MENU_ID'
				";

			$rsMenu = oci_parse($conn,$sqlMenu);
			oci_execute ($rsMenu);
			while ($rsRowMenu = oci_fetch_array ($rsMenu, OCI_BOTH)) {
				$return=$rsRowMenu['MENU_NAME'];
			}
			return $return;
		}

	function getMainMenuName($conn,$MENU_ID)
		{
			//echo "USER_ID -> $USER_ID";
			$return = "";

			//get Parent Menu
			$sqlMenu =
				"select MENU_ID_PARENT from MST_MENUS m
				where MENU_ID = '$MENU_ID'
				";

			$rsMenu = oci_parse($conn,$sqlMenu);
			oci_execute ($rsMenu);
			$parentMenu = "";
			while ($rsRowMenu = oci_fetch_array ($rsMenu, OCI_BOTH)) {
				$parentMenu=$rsRowMenu['MENU_ID_PARENT'];
			}

			if ($parentMenu!==""){
				$return = getMenuName($conn,$parentMenu);
			}

			return $return;
		}

	function getFileName($conn,$MENU_ID)
		{
			//echo "MENU_ID -> $MENU_ID";
			$return = "";
			$sqlMenu =
				"select FILE_NAME from MST_MENUS m
				where MENU_ID = '$MENU_ID'
				";

			$rsMenu = oci_parse($conn,$sqlMenu);
			oci_execute ($rsMenu);
			while ($rsRowMenu = oci_fetch_array ($rsMenu, OCI_BOTH)) {
				$return=$rsRowMenu['FILE_NAME'];
			}
			//echo "Return -> $return";
			if ($return!==""){
				$return = strtoupper($return);
				$return = str_replace(".PHP","",$return);
			}
			//echo "Return -> $return";

			return $return;
		}

	function pInsEfillLogAccess($conn,$USER_NAME,$DetailAccess,$USER_NAME_DTL){
		$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
							ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
							)
							values (
							'123','$USER_NAME',sysdate,'$DetailAccess','$USER_NAME_DTL'
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

	}

	function setDecimal($conn,$dtVal,$lengthDec){
		if ($lengthDec > 0){
		 	$sqlDtDtl = "select fSetDecimal(replace('$dtVal',','),$lengthDec) GET_DATA from dual";
	 		//echo "1 $sqlDtDtl</br>";
		} else {
			$sqlDtDtl = "select to_char(round(replace('$dtVal',',')),'fm999,999,999,999') GET_DATA from dual";
			//echo "2 $sqlDtDtl</br>";
		}
		//echo "$sqlDtDtl</br>";
	 	$dtVal = getData($conn,$sqlDtDtl);

	 	return $dtVal;
	}

	function isUserAdmin($conn,$USER_NAME){
		$sts = 'N';
		$sqlUsr = "select * from mst_group_users where GROUPS_ID = 1 and USER_ID = '$USER_NAME'";
		$rsUsr = oci_parse($conn,$sqlUsr);
		oci_execute ($rsUsr);
		while ($rsRowUsr = oci_fetch_array ($rsUsr, OCI_BOTH)) {
			$sts = 'Y';
		}

	 	return $sts;
	}

?>
