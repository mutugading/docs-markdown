<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}

	$ETD_SYS_ID_S = "NULL";
	 if(isset($_POST['ETD_SYS_ID_S'])) {
	 	$ETD_SYS_ID_S=$_POST['ETD_SYS_ID_S'];
    }

  $ETD_SYS_ID_S_OLD = "NULL";
 	if(isset($_POST['ETD_SYS_ID_S_OLD'])) {
 	$ETD_SYS_ID_S_OLD=$_POST['ETD_SYS_ID_S_OLD'];
  }

	$FOLDER_VAL_1_S= "NULL";
	if(isset($_POST['FOLDER_VAL_1_S'])) {
	 	$FOLDER_VAL_1_S=$_POST['FOLDER_VAL_1_S'];
  }
	$FOLDER_VAL_1_V = "";
	if(isset($_POST['FOLDER_VAL_1_V'])) {
	 	$FOLDER_VAL_1_V=$_POST['FOLDER_VAL_1_V'];
	}

	$FOLDER_VAL_2_S = "";
	if(isset($_POST['FOLDER_VAL_2_S'])) {
	 	$FOLDER_VAL_2_S=$_POST['FOLDER_VAL_2_S'];
	}
	$FOLDER_VAL_2_V = "";
	if(isset($_POST['FOLDER_VAL_2_V'])) {
	 	$FOLDER_VAL_2_V=$_POST['FOLDER_VAL_2_V'];
	}

	$FOLDER_VAL_3_S = "";
	if(isset($_POST['FOLDER_VAL_3_S'])) {
	 	$FOLDER_VAL_3_S=$_POST['FOLDER_VAL_3_S'];
	}
	$FOLDER_VAL_3_V = "";
	if(isset($_POST['FOLDER_VAL_2_V'])) {
	 	$FOLDER_VAL_3_V=$_POST['FOLDER_VAL_3_V'];
	}

	$FOLDER_VAL_4_S = "";
	if(isset($_POST['FOLDER_VAL_4_S'])) {
	 	$FOLDER_VAL_4_S=$_POST['FOLDER_VAL_4_S'];
	}
	$FOLDER_VAL_4_V = "";
	if(isset($_POST['FOLDER_VAL_4_V'])) {
	 	$FOLDER_VAL_4_V=$_POST['FOLDER_VAL_4_V'];
	}

	$FOLDER_VAL_5_S = "";
    if(isset($_POST['FOLDER_VAL_5_S'])) {
	 	$FOLDER_VAL_5_S=$_POST['FOLDER_VAL_5_S'];
	}
	$FOLDER_VAL_5_V = "";
	if(isset($_POST['FOLDER_VAL_5_V'])) {
	 	$FOLDER_VAL_5_V=$_POST['FOLDER_VAL_5_V'];
	}
    $FIND_DATA_S="";
    if(isset($_POST['FIND_DATA_S'])) {
	 	$FIND_DATA_S=$_POST['FIND_DATA_S'];
	}

	$VIEW_LINK="";
    if(isset($_POST['VIEW_LINK'])) {
	 	$VIEW_LINK=$_POST['VIEW_LINK'];
	}
	//echo "STSPRS $STSPRS <br> VIEW_LINK $VIEW_LINK<br>";
	//Get Parameter

	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";
	//$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";


	include ("form_header.php");
	include ("check_login.php");

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
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;overflow-y:auto;">
<!-- DEFINE LAYOUT -->
<?php
if ($VIEW_LINK!==""){
	$sqlGetData = 	"select MGTAPPS.fGetUserName('$USER_NAME')GET_DATA from dual";
	$USER_NAME_DTL = getData($conn,$sqlGetData);

	$sqlIns = "	insert into mgtapps.EFILL_LOG_ACCESS(
				ELA_SYS_ID, ELA_CREATED_BY, ELA_CREATED_TIMESTAMP, ELA_DETAIL_ACCESS, ELA_USER_NAME
				)
				values (
				'123','$USER_NAME',sysdate,'View Document -> $MENU_NAME -> $VIEW_LINK','$USER_NAME_DTL'
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
	$VIEW_LINK= "https://mgtapps.mutugading.com:4433/webapps/$VIEW_LINK";
	//echo "cek VIEW_LINK $VIEW_LINK<b>";
	echo "<iframe src='$VIEW_LINK' width='100%' style='height:100%'></iframe>";
} else {

	$sqlGetData = "select 'ADMIN' GET_DATA from MGTAPPS.EFILL_ADMIN_USER where EAU_USER_ID = '$USER_NAME'";
	$EFILL_ADMIN = getData($conn,$sqlGetData);

	if ($EFILL_ADMIN==="ADMIN"){
		$sqlList = "select 	ETD_SYS_ID from    EFILL_TYPE_DATA a where ETD_SYS_ID = 'CARPET PRODUCT' ";
	} else {
		$sqlList = "select 	ETD_SYS_ID
					from mgtapps.EFILL_TYPE_DATA a
							,mgtapps.EFILL_USER_ACCESS b
					where a.ETD_SYS_ID = b.EUA_ETD_SYS_ID
					and EUA_USER_ID = '$USER_NAME'
					and ETD_SYS_ID = 'CARPET PRODUCT'
					";
	}

	//if ($USER_NAME==="1399"){echo "sqlList $sqlList<br>";	}
?>
<table style="width:100%" >
<tr>
		<td align="center" colspan="<?php echo $totDisp; ?>">
		Type Data  :
			<select name="ETD_SYS_ID" id="ETD_SYS_ID" onchange="Process('')">
					<option value="NULL">NULL</option>
<?php
    //get access folder
  $rsDataList = oci_parse($conn,$sqlList);
	oci_execute ($rsDataList);
	while ($rowDataList = oci_fetch_array ($rsDataList, OCI_BOTH)) {
?>
				<option value="<?php echo $rowDataList['ETD_SYS_ID']; ?>"
					<?php if($ETD_SYS_ID_S===$rowDataList['ETD_SYS_ID']){ echo "selected";}?>
					>
						<?php echo $rowDataList['ETD_SYS_ID']; ?>
				</option>
<?php
	}
?>
			</select>
<?php
	if ($ETD_SYS_ID_S!==""){
		$stsView = "Y";
		// get Deep Folder
		$sqlGetData = 	"SELECT MAX (lvl) GET_DATA
						 FROM (	SELECT *
    				 		FROM (
								select -1,level lvl,EDF_FOLDER MENU_NAME,null,EDF_SYS_ID MENU_ID
								from EFILL_DATA_FOLDER
								where EDF_ETD_SYS_ID = '$ETD_SYS_ID_S'
								connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
								start with EDF_SYS_ID_PARENT is null
								order by EDF_SYS_ID
                     			  )
    					  )";
		$deepFolder = getData($conn,$sqlGetData);
		// end get Deep Folder
		$x = 1;
		//start loop
		while($x <= $deepFolder ) {
			//select 1
			if ($x===1){
				$qryFldr =
					"SELECT lvl,EDF_FOLDER,EDF_FOLDER_ID
			  		FROM (
						select -1,level lvl,EDF_FOLDER,null,EDF_SYS_ID EDF_FOLDER_ID,EDF_SYS_ID_PARENT EDF_FOLDER_PARENT
						from EFILL_DATA_FOLDER
						where EDF_ETD_SYS_ID ='$ETD_SYS_ID_S'
						connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
						start with EDF_SYS_ID_PARENT is null
						order by EDF_SYS_ID
			            )
					where lvl = $x";
		?>		Folder
				<select name="FOLDER_VAL_1" id="FOLDER_VAL_1" onchange="Process('')">
					<option value="NULL" >NULL</option>
					<?php
					$rsDataFldr = oci_parse($conn,$qryFldr);
					oci_execute ($rsDataFldr);
					while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
					?>
					<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
					<?php
					if ($FOLDER_VAL_1_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
					?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
					<?php
					}
		?>
				</select>
<?php
			if ($FOLDER_VAL_1_S === "NULL"){$stsView="N";}
			}
		//end select 1
		//condition
		if ($x>1){
			$folderWhr = "";
			if ($x==2){
				$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_1_S' ";
			}else if ($x==3){
				$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_2_S' ";
			}else if ($x==4){
				$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_3_S' ";
			}else if ($x==5){
				$folderWhr = " and EDF_FOLDER_PARENT = '$FOLDER_VAL_4_S' ";
			}
			$qryFldr =
			"SELECT lvl,EDF_FOLDER,EDF_FOLDER_ID,EDF_FOLDER_PARENT
			  FROM (SELECT *
			          FROM (
							select -1,level lvl,EDF_FOLDER,null,EDF_SYS_ID EDF_FOLDER_ID,EDF_SYS_ID_PARENT EDF_FOLDER_PARENT
							from EFILL_DATA_FOLDER
							where EDF_ETD_SYS_ID ='$ETD_SYS_ID_S'
							connect by prior EDF_SYS_ID = EDF_SYS_ID_PARENT
							start with EDF_SYS_ID_PARENT is null
							order by EDF_SYS_ID
			                )
			        )
			where lvl = $x
			$folderWhr ";
		}
		//end condition
		//Folder 2
		if ($x==2){
		if ($FOLDER_VAL_2_S === "NULL"){$stsView="N";}

			//if ($USER_NAME==="1949"){

				$sqlGetData = 	"select MGTAPPS.pkg_efilling.fGetQryUserYear('$USER_NAME') GET_DATA from dual";
				$sqlUserYr = getData($conn,$sqlGetData);

				if ($sqlUserYr !==""){
					$qryFldr = "$qryFldr and EDF_FOLDER in ($sqlUserYr)";
					//echo "</br>$qryFldr and EDF_FOLDER in ($sqlUserYr) </br>";
				}
			//}

		?>
		<select name="FOLDER_VAL_2" id="FOLDER_VAL_2" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_2_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
		<?php
		}
		?>
		</select>
		<?php
		}
		//end Folder 2

	//select 3
	if ($x===3){
		if ($FOLDER_VAL_3_S === "NULL"){$stsView="N";}
	?>
		<select name="FOLDER_VAL_3" id="FOLDER_VAL_3" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_3_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 3
	//select 4
	if ($x===4){
		if ($FOLDER_VAL_4_S === "NULL"){$stsView="N";}
	?>
		<select name="FOLDER_VAL_4" id="FOLDER_VAL_4" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_4_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 4
	//select 5
	if ($x===5){
		if ($FOLDER_VAL_5_S === "NULL"){$stsView="N";}
	?>
		<select name="FOLDER_VAL_5" id="FOLDER_VAL_5" onchange="Process('')">
			<option value="NULL">NULL</option>
			<?php
				$rsDataFldr = oci_parse($conn,$qryFldr);
				oci_execute ($rsDataFldr);
				while ($rowDataFldr = oci_fetch_array ($rsDataFldr, OCI_BOTH)) {
			?>
			<option value="<?php echo $rowDataFldr['EDF_FOLDER_ID']; ?>"
			<?php
			if ($FOLDER_VAL_5_S===$rowDataFldr['EDF_FOLDER_ID']){echo "selected";}
			?>><?php echo $rowDataFldr['EDF_FOLDER']; ?> </option>
<?php
		}
?>
		</select>
<?php
	}
	//end select 5
			$x++;
		}
		//end loop

		//View Data
		if ($ETD_SYS_ID_S==="NULL")	{
			$stsView = "N";
		}
		if ($deepFolder===2 && $FOLDER_VAL_2_S === "NULL")	{
			$stsView="N";
		}
		if ($deepFolder===3 && $FOLDER_VAL_3_S === "NULL")	{
			$stsView="N";
		}
		if ($deepFolder===4 && $FOLDER_VAL_4_S === "NULL")	{
			$stsView="N";
		}
		if ($deepFolder===5 && $FOLDER_VAL_5_S === "NULL")	{
			$stsView="N";
		}

		if ($stsView==="Y"){
?>
		<tr>
			<td align="center">
			Search Data
			<input 	type="text" id="FIND_DATA" name="FIND_DATA" value="<?php echo $FIND_DATA_S; ?>"
					onkeypress="Javascript: if (event.keyCode==13) Process('');"
			>
			<input type="button" id="CLEAR" name="FIND" value="FIND" class="BUTTON btn_view" onclick="Process('')" >
			<input type="button" id="CLEAR" name="CLEAR" value="CLEAR" class="BUTTON btn_process" onclick="Process('CLEAR')" >
			</td>
		</tr>
	</table>
	<table style="width:100%" border="1">
<?php

		$folderDest = "Doc_Folder/$FOLDER_VAL_1_V";
		if ($FOLDER_VAL_2_S !== ""){
			$folderDest = "$folderDest/$FOLDER_VAL_2_V";
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
			//$folderDest = '"D:\XAMPP\htdocs\webapps\Doc_Folder\PDN\2021\"';
			$folderLink = "D:/XAMPP/htdocs/webapps/$folderDest";

			//echo "folder Destination $folderLink </br>";
			$fileData = "";
			$recDisp = 0;
			$sqlGetData = 	"select nvl(ETD_TOT_DISPLAY,0) GET_DATA from EFILL_TYPE_DATA
							 where ETD_SYS_ID = '$ETD_SYS_ID_S'";
			$totDisp = getData($conn,$sqlGetData);
			if ($totDisp === '0' ){
				$totDisp =7;
			} else {
				$totDisp =(int)$totDisp;
			}
			$withDisp = 100/$totDisp;
			//echo "ETD_SYS_ID_S $ETD_SYS_ID_S totDisp $totDisp";
			$link="https://mgtapps.mutugading.com:4433/";
			$stsFile = "";
			$printLink = "";
			//echo "$folderLink</br>";
			if (is_dir($folderLink)){
	    		if ($dh = opendir($folderLink)){
	    			$noFile = 0;
	    			$fileDt[]="";
	    			$color = "";
	    			while (($file = readdir($dh)) !== false){
	    				$stsFile = "Y";
	    				$printLink = "";
						if (strpos(strtoupper($file),".PDF") === FALSE) {
							$stsFile = "N";
						}
						if ($stsFile === "Y")	{
							if ($FIND_DATA_S!==""){
								if (strpos(strtoupper($file),strtoupper($FIND_DATA_S)) === FALSE) {
									$stsFile = "N";
								}
							}
						}
						if ($stsFile==="Y") {
							$noFile=$noFile+1;
							$fileDt[$noFile] = $file;
		    				$recDisp++;
		    				if ($recDisp===1){
		    					$color = "BUTTON btn_process";
		    				}
		    				if ($recDisp===2){$color = "BUTTON btn_add";}
							if ($recDisp===3){$color = "BUTTON btn_delete";}
							if ($recDisp===4){$color = "BUTTON btn_view";}
							if ($recDisp===5){$color = "BUTTON btn_example";}
							if ($recDisp===6){$color = "BUTTON btn_update";}
							if ($recDisp===7){$color = "BUTTON btn_back";}

							if ($recDisp<=$totDisp){
								if ($recDisp==1){
									?>
									<tr>
									<?php
								}
								$printLink = "$folderDest/$file";
														?>
		<td style="width:<?php echo $withDisp; ?>%" align="center">
			<input 	type="button" value="<?php echo "$noFile. $file"; ?> "
					onclick="<?php echo "Process('VIEW $printLink')"; ?>" class="<?php echo "$color"; ?>" >
		</td>
<?php
							}
							if ($recDisp===$totDisp){
								$recDisp=0;
								?>
								</tr>
								<?php
							}
						}
						if ($printLink!==""){
							$printLink = "";
						}
					}
			    	closedir($dh);
				}else {
					echo "NO Data";
				}
			} else {
				echo "Folder invalid";
			}
		}
		//end View Data
	}
?>

		</td>
	</tr>
</table>
<?php
}
?>
<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="ETD_SYS_ID_S" id="ETD_SYS_ID_S" >
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

<input type="hidden" name="FIND_DATA_S" id="FIND_DATA_S">

<input type="hidden" name="VIEW_LINK" id="VIEW_LINK">
<script type='text/javascript'>
	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('STSPRS').value = prs;

		var ETD_SYS_ID_S=document.getElementById('ETD_SYS_ID');
		var ETD_SYS_ID_S_OLD=document.getElementById('ETD_SYS_ID_S_OLD').value;
        var ETD_SYS_ID_v = ETD_SYS_ID_S.options[ETD_SYS_ID_S.selectedIndex].value;
        document.getElementById("ETD_SYS_ID_S").value =  ETD_SYS_ID_v;

		var FOLDER_VAL_1 = "";		var FOLDER_VAL_1_V ="";      var FOLDER_VAL_1_T = "";
        if (document.getElementById('FOLDER_VAL_1')!==null ) {
        	FOLDER_VAL_1 = document.getElementById('FOLDER_VAL_1');
        	FOLDER_VAL_1_V = FOLDER_VAL_1.options[FOLDER_VAL_1.selectedIndex].value;
        	FOLDER_VAL_1_T = FOLDER_VAL_1.options[FOLDER_VAL_1.selectedIndex].text;
        	//alert("FOLDER_VAL_1_T " + FOLDER_VAL_1_T + "ETD_SYS_ID_S_OLD "+ETD_SYS_ID_S_OLD );
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_1_S").value =  FOLDER_VAL_1_V;
        	} else {
				document.getElementById("FOLDER_VAL_1_S").value =  "";
        	}
    	}

        var FOLDER_VAL_2 = "";        var FOLDER_VAL_2_V = "";		var FOLDER_VAL_2_T = "";
        if (document.getElementById('FOLDER_VAL_2')!==null ) {
        	FOLDER_VAL_2 = document.getElementById('FOLDER_VAL_2');
        	FOLDER_VAL_2_V = FOLDER_VAL_2.options[FOLDER_VAL_2.selectedIndex].value;
        	FOLDER_VAL_2_T = FOLDER_VAL_2.options[FOLDER_VAL_2.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_2_S").value =  FOLDER_VAL_2_V;
        	} else {
				document.getElementById("FOLDER_VAL_2_S").value =  "NULL";
        	}
    	}

		var FOLDER_VAL_3 = "";        var FOLDER_VAL_3_V = "";var FOLDER_VAL_3_T = "";
    	if (document.getElementById('FOLDER_VAL_3')!==null ) {
        	FOLDER_VAL_3 = document.getElementById('FOLDER_VAL_3');
        	FOLDER_VAL_3_V = FOLDER_VAL_3.options[FOLDER_VAL_3.selectedIndex].value;
        	FOLDER_VAL_3_T = FOLDER_VAL_3.options[FOLDER_VAL_3.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_3_S").value =  FOLDER_VAL_3_V;
        	} else {
				document.getElementById("FOLDER_VAL_3_S").value =  "NULL";
        	}
    	}
        var FOLDER_VAL_4 ="";        var FOLDER_VAL_4_V = ""; var FOLDER_VAL_4_T = "";
    	if (document.getElementById('FOLDER_VAL_4')!==null ) {
        	FOLDER_VAL_4 = document.getElementById('FOLDER_VAL_4');
        	FOLDER_VAL_4_V = FOLDER_VAL_4.options[FOLDER_VAL_4.selectedIndex].value;
        	FOLDER_VAL_4_T = FOLDER_VAL_4.options[FOLDER_VAL_4.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_4_S").value =  FOLDER_VAL_4_V;
        	} else {
				document.getElementById("FOLDER_VAL_4_S").value =  "NULL";
        	}
    	}
        var FOLDER_VAL_5 = ""; var FOLDER_VAL_5_V = ""; var FOLDER_VAL_5_T = "";
    	if (document.getElementById('FOLDER_VAL_5')!==null ) {
        	FOLDER_VAL_5 = document.getElementById('FOLDER_VAL_5');
        	FOLDER_VAL_5_V = FOLDER_VAL_5.options[FOLDER_VAL_5.selectedIndex].value;
        	FOLDER_VAL_5_T = FOLDER_VAL_5.options[FOLDER_VAL_5.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_5_S").value =  FOLDER_VAL_5_V;
        	} else {
				document.getElementById("FOLDER_VAL_5_S").value =  "NULL";
        	}
    	}

    	//alert("cek 2 "+prs);

    	if (document.getElementById('FIND_DATA')!==null){

    		if (prs==="CLEAR"){
    			document.getElementById("FIND_DATA_S").value =  "";
    			document.getElementById("ETD_SYS_ID_S").value =  "";
    			document.getElementById('FOLDER_VAL_1_S').value="";
    		} else {
    			document.getElementById("FIND_DATA_S").value =  document.getElementById("FIND_DATA").value;
    		}

    	}

    	if (document.getElementById('FOLDER_VAL_1_S').value==="") {
    		document.getElementById('FOLDER_VAL_2_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_3_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_2_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_3_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_3_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_4_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}
    	//alert("cek 3");
		if (document.getElementById('FOLDER_VAL_1_S').value!==""){
            document.getElementById('FOLDER_VAL_1_V').value = FOLDER_VAL_1_T;
		}
		//alert("cek 3 1");
    	if (document.getElementById('FOLDER_VAL_2_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_2_V').value = FOLDER_VAL_2_T;
    	}
    	if (document.getElementById('FOLDER_VAL_3_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_3_V').value = FOLDER_VAL_3_T;
    	}
    	if (document.getElementById('FOLDER_VAL_4_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_4_V').value = FOLDER_VAL_4_T;
    	}
    	if (document.getElementById('FOLDER_VAL_5_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_5_V').value = FOLDER_VAL_5_T;
    	}
    	if (prs.substr(0,4)==="VIEW"){
    		document.getElementById('VIEW_LINK').value = prs.substr(5);
    	}


		document.getElementById(vFormName).submit();
	}
	//function process

	var dropdown = document.getElementsByClassName("dropdown-btn");
	var i;

	for (i = 0; i < dropdown.length; i++) {
	  dropdown[i].addEventListener("click", function() {
	  this.classList.toggle("active");
	  var dropdownContent = this.nextElementSibling;
	  if (dropdownContent.style.display === "block") {
	  dropdownContent.style.display = "none";
	  } else {
	  dropdownContent.style.display = "block";
	  }
	  });
	}

	function openNav() {
	  document.getElementById("mySidenav").style.width = "250px";
	}

	function closeNav() {
	  document.getElementById("mySidenav").style.width = "0";
	}
	</script>
</form>
</body>
</html>
