<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");
	//echo "P_MENU_ID $P_MENU_ID</br>";

	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

	//echo "STSPRS $STSPRS";
	$MPD_NAME_S = "";if(isset($_POST['MPD_NAME_S'])) {$MPD_NAME_S=$_POST['MPD_NAME_S'];}

	$MPD_SYS_ID_S = "";if(isset($_POST['MPD_SYS_ID_S'])) {$MPD_SYS_ID_S=$_POST['MPD_SYS_ID_S'];}

	$MPD_REMARKS_S = "";if(isset($_POST['MPD_REMARKS_S'])) {$MPD_REMARKS_S=$_POST['MPD_REMARKS_S'];}

	$DATE_IDUL_S = "";if(isset($_POST['DATE_IDUL_S'])) {$DATE_IDUL_S=$_POST['DATE_IDUL_S'];}

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

	$Refsh = "";
	if ($STSPRS=== "SAVE_ADD_IDUL"){

			$sqlInsLg = "insert into MGTAPPS.mst_param_data
								(MPD_SYS_ID, MPD_CREATED_BY, MPD_CREATED_TIMESTAMP, MPD_MPDK_KEY
								,MPD_NAME , MPD_REMARKS)
						 values ('123'		,'$USER_NAME'			,sysdate 		,'IDUL_FITRI_DATE_DATA'
						 		,'$DATE_IDUL_S','Date of Idul Fitri Period ".substr($DATE_IDUL_S,0,7)."')";

			//echo $sqlInsLg;
		  	$rsInsLg = oci_parse($conn,$sqlInsLg);
		  	oci_execute($rsInsLg, OCI_DEFAULT);
			if (!$rsInsLg) {
			    //echo $sqlInsrt;
			    $e = oci_error($rsInsLg);  // For oci_execute errors pass the statement handle
			    print htmlentities($e['message']);
			    print "\n<pre>\n";
			    print htmlentities($e['sqltext']);
			    printf("\n%".($e['offset']+1)."s", "^");
			    print  "\n</pre>\n";
			    //echo $sqlInsrt;
			}
		  	oci_commit($conn);
		  	echo "<script>alert('Insert Data Idul Fitri Date Successfully!');</script>";
		  	$Refsh = "Y";
	}

	if ($STSPRS==="MPD_NAME"){
		$sqlUpd = 	"update MST_PARAM_DATA
						set MPD_NAME = '$MPD_NAME_S'
							,MPD_REMARKS = '$MPD_REMARKS_S'
							,MPD_MODIFIED_BY = '$USER_NAME'
							,MPD_MODIFIED_TIMESTAMP = sysdate
					where MPD_SYS_ID = '$MPD_SYS_ID_S'";

		//echo $sqlUpd;
	  	$rsUpd = oci_parse($conn,$sqlUpd);
	  	oci_execute($rsUpd, OCI_DEFAULT);
		if (!$rsUpd) {
		    //echo $sqlInsrt;
		    $e = oci_error($rsUpd);  // For oci_execute errors pass the statement handle
		    print htmlentities($e['message']);
		    print "\n<pre>\n";
		    print htmlentities($e['sqltext']);
		    printf("\n%".($e['offset']+1)."s", "^");
		    print  "\n</pre>\n";
		    //echo $sqlInsrt;
		}
	  	oci_commit($conn);
	  	echo "<script>alert('Data $MPD_REMARKS_S Successfully updated !');</script>";
	  	$Refsh = "Y";
	} else if ($STSPRS==="MPD_NAME2"){
		$sqlUpd = 	"update MST_PARAM_DATA
						set MPD_NAME = '$MPD_NAME_S'
							,MPD_MODIFIED_BY = '$USER_NAME'
							,MPD_MODIFIED_TIMESTAMP = sysdate
					where MPD_SYS_ID = '$MPD_SYS_ID_S'";

		//echo $sqlUpd;
	  	$rsUpd = oci_parse($conn,$sqlUpd);
	  	oci_execute($rsUpd, OCI_DEFAULT);
		if (!$rsUpd) {
		    //echo $sqlInsrt;
		    $e = oci_error($rsUpd);  // For oci_execute errors pass the statement handle
		    print htmlentities($e['message']);
		    print "\n<pre>\n";
		    print htmlentities($e['sqltext']);
		    printf("\n%".($e['offset']+1)."s", "^");
		    print  "\n</pre>\n";
		    //echo $sqlInsrt;
		}
	  	oci_commit($conn);
	  	echo "<script>alert('Data $MPD_REMARKS_S Successfully updated !');</script>";
	  	$Refsh = "Y";
	} else if ($STSPRS==="MPD_NAME3"){
		//echo "Msuk MPD_NAME3";
		$sqlUpd = 	"update MST_PARAM_DATA
						set MPD_NAME = '$MPD_NAME_S'
							,MPD_MODIFIED_BY = '$USER_NAME'
							,MPD_MODIFIED_TIMESTAMP = sysdate
					where MPD_SYS_ID = '$MPD_SYS_ID_S'";

		//echo $sqlUpd;
	  	$rsUpd = oci_parse($conn,$sqlUpd);
	  	oci_execute($rsUpd, OCI_DEFAULT);
		if (!$rsUpd) {
		    //echo $sqlInsrt;
		    $e = oci_error($rsUpd);  // For oci_execute errors pass the statement handle
		    print htmlentities($e['message']);
		    print "\n<pre>\n";
		    print htmlentities($e['sqltext']);
		    printf("\n%".($e['offset']+1)."s", "^");
		    print  "\n</pre>\n";
		    //echo $sqlInsrt;
		}
	  	oci_commit($conn);
	  	echo "<script>alert('Data $MPD_REMARKS_S Successfully updated !');</script>";
	  	$Refsh = "Y";
	}else if ($STSPRS==="DEL_IDUL"){
		$sqlDel = 	"DELETE FROM  MST_PARAM_DATA where MPD_SYS_ID = '$MPD_SYS_ID_S'";

		//echo $sqlDel;//die;
	  	$rsDel = oci_parse($conn,$sqlDel);
	  	oci_execute($rsDel, OCI_DEFAULT);
		if (!$rsDel) {
		    //echo $sqlInsrt;
		    $e = oci_error($rsDel);  // For oci_execute errors pass the statement handle
		    print htmlentities($e['message']);
		    print "\n<pre>\n";
		    print htmlentities($e['sqltext']);
		    printf("\n%".($e['offset']+1)."s", "^");
		    print  "\n</pre>\n";
		    //echo $sqlInsrt;
		}
	  	oci_commit($conn);
	  	echo "<script>alert('Data Successfully deleted !');</script>";
	  	$Refsh = "Y";
	}

	if ($Refsh === "Y"){
		//echo "P_MENU_ID $P_MENU_ID";
		echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
        echo "<input type='text' id='STSPRS' name='STSPRS' value=''> ";
        echo "<input type='text' name='P_MENU_ID' id='P_MENU_ID' value='".$P_MENU_ID."' >";
        echo "</form>";
        echo "<script type='text/javascript'>";
        echo "document.getElementById('$FORM_NAME').submit();";
        echo "</script>";
	}
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
<div class="main" style="overflow-x:auto;" align="center">
<!-- DEFINE LAYOUT -->
<table style="width:50%" align="left" border="1">
	<tr>
		<td align="center" colspan="4">
			<b>RATE COUPON</b>
		</td>
	</tr>
	<tr>
		<td align="center">
			No
		</td>
		<td align="center">
			Sys Id
		</td>
		<td align="center">
			Description
		</td>
		<td align="center" style="width:5%">
			Value
		</td>
	</tr>
<?php
	$sqlParam1 = 	"select MPD_SYS_ID, MPD_NAME, MPD_REMARKS
					from MST_PARAM_DATA t where t.MPD_MPDK_KEY = 'RATE_KUPON_MAKAN'
					and MPD_SYS_ID in ('20210400046','20210400039','20210400040','20210500050','20210800118')";

	$rsParam1 = oci_parse($conn,$sqlParam1);
	$noParam1 =0;
	oci_execute ($rsParam1);
	$no = 0;
	while ($rowParam1 = oci_fetch_array ($rsParam1, OCI_BOTH)) {
		$noParam1 	= $noParam1 + 1;
		$updParam1 	= "MPD_NAME $noParam1";
?>
	<tr>
		<td align="center"><?php echo $noParam1; ?></td>
		<td align="left"><?php echo $rowParam1['MPD_SYS_ID']; ?></td>
		<td align="left">
			<input type="text" name="MPD_REMARKS[]" id="MPD_REMARKS[]" value="<?php echo $rowParam1['MPD_REMARKS'];?>"
				style="width: 400px;text-align: left;"
				onkeypress="Javascript: if (event.keyCode==13) Process(
				'<?php echo $updParam1; ?>'
				);"

				<?php if ($STSPRS === "ADD_IDUL"){ echo "readonly";}?>
			>
		</td>
		<td align="right">
			<input type="text" name="MPD_NAME[]" id="MPD_NAME[]" value="<?php echo number_format($rowParam1['MPD_NAME']);?>"
				style="width: 60px;text-align: right;"
				onkeypress="Javascript: if (event.keyCode==13) Process(
				'<?php echo $updParam1; ?>'
				);"

				<?php if ($STSPRS === "ADD_IDUL"){ echo "readonly";}?>
			>
			<input type="hidden" name="MPD_SYS_ID[]" id="MPD_SYS_ID[]" value="<?php echo $rowParam1['MPD_SYS_ID'];?>"
			>
		</td>
	</tr>
<?php
	}
?>
</table>
<table style="width:50%" align="left" border="1">
	<tr>
		<td align="center" colspan="3">
			Assign Parameter
		</td>
	</tr>
	<tr>
		<td align="center">
			No
		</td>
		<td align="center">
			Description
		</td>
		<td align="center">
			Value
		</td>
	</tr>
<?php
	$sqlParam2 = 	"select MPD_SYS_ID, MPD_NAME, MPD_REMARKS
					from MST_PARAM_DATA t where t.MPD_MPDK_KEY = 'RATE_KUPON_MAKAN'
					and MPD_SYS_ID in ('20210400041','20210400042','20210400043')";

	$rsParam2 = oci_parse($conn,$sqlParam2);
	$noParam2 =0;
	oci_execute ($rsParam2);
	$no = 0;
	while ($rowParam2 = oci_fetch_array ($rsParam2, OCI_BOTH)) {
		$noParam2 	= $noParam2 + 1;
		$updParam2 	= "MPD_NAME2 $noParam2";
?>
	<tr>
		<td align="center"><?php echo $noParam2; ?></td>
		<td align="left"><?php echo $rowParam2['MPD_SYS_ID']."-".$rowParam2['MPD_REMARKS']; ?></td>
		<td align="right">
			<input type="text" name="MPD_NAME2[]" id="MPD_NAME2[]" value="<?php echo $rowParam2['MPD_NAME'];?>"
				onkeypress="Javascript: if (event.keyCode==13) Process(
				'<?php echo $updParam2; ?>'
				);"

				<?php if ($STSPRS === "ADD_IDUL"){ echo "readonly";}?>
			>
			<input type="hidden" name="MPD_SYS_ID2[]" id="MPD_SYS_ID2[]" value="<?php echo $rowParam2['MPD_SYS_ID'];?>" >
			<input type="hidden" name="MPD_REMARKS2[]" id="MPD_REMARKS2[]" value="<?php echo $rowParam2['MPD_REMARKS'];?>" >

		</td>
	</tr>
<?php
	}
?>
</table>
</div>
<div class="main" style="overflow-x:auto;"  align="center">
	<table style="width:50%" align="center" border="1">
	<tr>
		<td align="center" colspan="2">
			IDUL FITRI Date Data
		</td>
		<td align="center" >
			<?php if ($STSPRS !== "ADD_IDUL"){ ?>
			<input type="BUTTON" name="ADD_IDUL" id="ADD_IDUL" value="ADD" class="BUTTON btn_process" onclick="Process('ADD_IDUL')">
		<?php } ?>
		</td>
	</tr>
	<?php if ($STSPRS === "ADD_IDUL"){ ?>
	<tr>
		<td align="center">
			Idul Fitri Date :
		</td>
		<td align="center">
			<input type="date" name="DATE_IDUL" id="DATE_IDUL">
		</td>
		<td align="center">
			<input type="BUTTON" value="SAVE" class="BUTTON btn_save" onclick="Process('SAVE_ADD_IDUL')">
			<input type="BUTTON" value="CANCEL" class="BUTTON btn_back" onclick="Process('CANCEL_ADD_IDUL')">
		</td>
	</tr>
	<?php }	?>
	<tr>
		<td align="center">
			No
		</td>
		<td align="center">
			Description
		</td>
		<td align="center">
			Value
		</td>
	</tr>
<?php
	$sqlParam3 = 	"select MPD_SYS_ID, MPD_NAME, MPD_REMARKS
					from MST_PARAM_DATA t where t.MPD_MPDK_KEY = 'IDUL_FITRI_DATE_DATA'
					order by to_date(MPD_NAME,'YYYY-MM-DD')
					";

	$rsParam3 = oci_parse($conn,$sqlParam3);
	$noParam3 =0;
	oci_execute ($rsParam3);
	$no = 0;
	while ($rowParam3 = oci_fetch_array ($rsParam3, OCI_BOTH)) {
		$noParam3 	= $noParam3 + 1;
		$updParam3 	= "MPD_NAME3 $noParam3";
?>
	<tr>
		<td align="center"><?php echo $noParam3; ?></td>
		<td align="left"><?php echo $rowParam3['MPD_SYS_ID']."-".$rowParam3['MPD_REMARKS']; ?></td>
		<td align="right">
			<input type="date" name="MPD_NAME3[]" id="MPD_NAME3[]" value="<?php echo $rowParam3['MPD_NAME'];?>"
				onkeypress="Javascript: if (event.keyCode==13) Process(
				'<?php echo $updParam3; ?>'
				);"

				<?php if ($STSPRS === "ADD_IDUL"){ echo "readonly";}?>
			>
			<input type="BUTTON" value="DEL" class="BUTTON btn_delete"
				<?php if ($STSPRS === "ADD_IDUL"){ echo "readonly";}?>
				onclick="Process('<?php echo "DEL_IDUL".$noParam3; ?>')"

			>
			<input type="hidden" name="MPD_SYS_ID3[]" id="MPD_SYS_ID3[]" value="<?php echo $rowParam3['MPD_SYS_ID'];?>" >
			<input type="hidden" name="MPD_REMARKS3[]" id="MPD_REMARKS3[]" value="<?php echo $rowParam3['MPD_REMARKS'];?>" >

		</td>
	</tr>
<?php } ?>
	</table>
</div>
<!-- DEFINE LAYOUT -->

<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">

<input type="hidden" name="MPD_NAME_S" id="MPD_NAME_S">
<input type="hidden" name="MPD_SYS_ID_S" id="MPD_SYS_ID_S">
<input type="hidden" name="MPD_REMARKS_S" id="MPD_REMARKS_S">

<input type="hidden" name="DATE_IDUL_S" id="DATE_IDUL_S">
<script type='text/javascript'>
	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		//var vFormName ="CC_007";
		if ( prs.substr(0,8) === "MPD_NAME" && prs.substr(0,9) !== "MPD_NAME2" && prs.substr(0,9) !== "MPD_NAME3"
			){
			var n = prs.substr(9);
				n = Number(n);
			 	n = n - 1;
			 	var vMPD_NAME = document.getElementsByName('MPD_NAME[]');
			 		vMPD_NAME_d = "";
			 	var vMPD_SYS_ID = document.getElementsByName('MPD_SYS_ID[]');
			 		vMPD_SYS_ID_d = "";
			 	var vMPD_REMARKS = document.getElementsByName('MPD_REMARKS[]');
			 		vMPD_REMARKS_d = "";
	        for (var i = 0; i < vMPD_NAME.length; i++) {
	        	if (n==i){
	             vMPD_NAME_d = vMPD_NAME[i].value;
	             vMPD_NAME_d = vMPD_NAME_d.replace(",", "");
	             vMPD_SYS_ID_d = vMPD_SYS_ID[i].value;
	             vMPD_REMARKS_d	= vMPD_REMARKS[i].value;
	        	}
	        }

	        vConfirm = confirm("Update "+vMPD_REMARKS_d+" ?");
            if (vConfirm === true){
				document.getElementById('STSPRS').value = prs.substr(0,8);
				document.getElementById('MPD_NAME_S').value = vMPD_NAME_d;
				document.getElementById('MPD_SYS_ID_S').value = vMPD_SYS_ID_d;
				document.getElementById('MPD_REMARKS_S').value = vMPD_REMARKS_d;

            }
		} else if ( prs.substr(0,9) === "MPD_NAME2"){
			//alert("Process "+prs.substr(0,9))
			var n = prs.substr(10);
				n = Number(n);
			 	n = n - 1;
			 	var vMPD_NAME = document.getElementsByName('MPD_NAME2[]');
			 		vMPD_NAME_d = "";
			 	var vMPD_SYS_ID = document.getElementsByName('MPD_SYS_ID2[]');
			 		vMPD_SYS_ID_d = "";
			 	var vMPD_REMARKS = document.getElementsByName('MPD_REMARKS2[]');
			 		vMPD_REMARKS_d = "";
	        for (var i = 0; i < vMPD_NAME.length; i++) {
	        	if (n==i){
	             vMPD_NAME_d = vMPD_NAME[i].value;
	             vMPD_NAME_d = vMPD_NAME_d.replace(",", "");
	             vMPD_SYS_ID_d = vMPD_SYS_ID[i].value;
	             vMPD_REMARKS_d	= vMPD_REMARKS[i].value;
	        	}
	        }

	        vConfirm = confirm("Update "+vMPD_REMARKS_d+" ?");
	        //alert("confirm "+ prs.substr(0,9));
            if (vConfirm === true){
            	//alert(prs.substr(0,9));
				document.getElementById('STSPRS').value = prs.substr(0,9);
				document.getElementById('MPD_NAME_S').value = vMPD_NAME_d;
				document.getElementById('MPD_SYS_ID_S').value = vMPD_SYS_ID_d;
				document.getElementById('MPD_REMARKS_S').value = vMPD_REMARKS_d;
            }

		}else if ( prs.substr(0,9) === "MPD_NAME3"){
			var n = prs.substr(10);
				n = Number(n);
			 	n = n - 1;
			 	var vMPD_NAME = document.getElementsByName('MPD_NAME3[]');
			 		vMPD_NAME_d = "";
			 	var vMPD_SYS_ID = document.getElementsByName('MPD_SYS_ID3[]');
			 		vMPD_SYS_ID_d = "";
			 	var vMPD_REMARKS = document.getElementsByName('MPD_REMARKS3[]');
			 		vMPD_REMARKS_d = "";
	        for (var i = 0; i < vMPD_NAME.length; i++) {
	        	if (n==i){
	             vMPD_NAME_d = vMPD_NAME[i].value;
	             vMPD_NAME_d = vMPD_NAME_d.replace(",", "");
	             vMPD_SYS_ID_d = vMPD_SYS_ID[i].value;
	             vMPD_REMARKS_d	= vMPD_REMARKS[i].value;
	        	}
	        }

	        vConfirm = confirm("Update Data IDUL FITRI Date "+vMPD_REMARKS_d+" ?");
            if (vConfirm === true){
				document.getElementById('STSPRS').value = prs.substr(0,9);
				document.getElementById('MPD_NAME_S').value = vMPD_NAME_d;
				document.getElementById('MPD_SYS_ID_S').value = vMPD_SYS_ID_d;
				document.getElementById('MPD_REMARKS_S').value = vMPD_REMARKS_d;
            }

		} else if ( prs.substr(0,8) === "DEL_IDUL"){
			var n = prs.substr(8);
				//alert(n);
				n = Number(n);
			 	n = n - 1;
			 	var vMPD_NAME = document.getElementsByName('MPD_NAME3[]');
			 		vMPD_NAME_d = "";
			 	var vMPD_SYS_ID = document.getElementsByName('MPD_SYS_ID3[]');
			 		vMPD_SYS_ID_d = "";
			 	var vMPD_REMARKS = document.getElementsByName('MPD_REMARKS3[]');
			 		vMPD_REMARKS_d = "";
	        for (var i = 0; i < vMPD_NAME.length; i++) {
	        	if (n==i){
	             vMPD_NAME_d = vMPD_NAME[i].value;
	             vMPD_NAME_d = vMPD_NAME_d.replace(",", "");
	             vMPD_SYS_ID_d = vMPD_SYS_ID[i].value;
	             vMPD_REMARKS_d	= vMPD_REMARKS[i].value;
	        	}
	        }

	        vConfirm = confirm("Delete "+vMPD_REMARKS_d+" ?");
            if (vConfirm === true){
				document.getElementById('STSPRS').value = prs.substr(0,8);
				document.getElementById('MPD_NAME_S').value = vMPD_NAME_d;
				document.getElementById('MPD_SYS_ID_S').value = vMPD_SYS_ID_d;
				document.getElementById('MPD_REMARKS_S').value = vMPD_REMARKS_d;

            }

		} else if ( prs === "ADD_IDUL"){
			vConfirm = confirm("Add data Idul Fitri Date ?");
            if (vConfirm === true){
            	document.getElementById('STSPRS').value = "ADD_IDUL";
            }
		} else if ( prs === "CANCEL_ADD_IDUL"){
			vConfirm = confirm("Cancel data Idul Fitri Date ?");
            if (vConfirm === true){
            	document.getElementById('STSPRS').value = "";
            } else {
            	document.getElementById('STSPRS').value = "ADD_IDUL";
            }
		} else if ( prs === "SAVE_ADD_IDUL"){

			if (document.getElementById('DATE_IDUL').value !== ""){
				vConfirm = confirm("Save data Idul Fitri Date ?");
	            if (vConfirm !== true){
	            	document.getElementById('STSPRS').value = "";
	            } else {
	            	document.getElementById('STSPRS').value = prs;
	            	document.getElementById('DATE_IDUL_S').value = document.getElementById('DATE_IDUL').value;
	            }
			} else {
				alert("Idul Fitri Date can''t null");
				document.getElementById('STSPRS').value = "ADD_IDUL";
			}
		}
		//alert(vFormName+" "+document.getElementById('STSPRS').value);
		//alert("P_MENU_ID " + document.getElementById('P_MENU_ID').value);
		document.getElementById(vFormName).submit();
	}

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
