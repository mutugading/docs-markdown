<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

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
	//$MenuPrintHrefPdf = $FORM_NAME."_PDF.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";
	//$MenuPrintHrefXls = $FORM_NAME."_XLS.php?P_EJ_MPJS_TGL=$EJ_MPJS_TGL_S&P_EJ_MPJS_TGL2=$EJ_MPJS_TGL2_S&P_SELECT=$P_SELECT_S&P_SELECT_VAL=$SELECT_VAL_S&P_DEPARTMENT=$DEPARTMENT_S"
	//					."&P_MENU_ID=$P_MENU_ID";


	include ("form_header.php");
	include ("check_login.php");

	if ($FORM_NAME===""){
		if(isset($_POST['FORM_NAME'])) {
			$FORM_NAME=$_POST['FORM_NAME'];
		}
	}
	//echo "FORM_NAME $FORM_NAME";

	$dirDest = "D:/XAMPP/htdocs/webapps/SCAN_DOC/$USER_NAME";
	//echo "$dirDest </br>";
	if (!is_dir($dirDest)){
		mkdir($dirDest, 0777, true);
		echo "directory $dirDest created </br>";
	}


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

?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
<link rel="stylesheet" type="text/css" href="../css/MENU.css">
<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style type="text/css">
.blink {
	animation: blink-animation 1s steps(5, start) infinite;
	-webkit-animation: blink-animation 1s steps(5, start) infinite;
}
@keyframes blink-animation {
	to {
		visibility: hidden;
	}
}
@-webkit-keyframes blink-animation {
to {
	visibility: hidden;
	}
}

.centerImage
{
    margin: auto;
    display: block;
}

.p1 {
  font-family: "Times New Roman", Times, serif;
}

.p2 {
  font-family: Arial, Helvetica, sans-serif;
}

.p3 {
  font-family: "Lucida Console", "Courier New", monospace;
}
</style>
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

<table style="width:100%">
	<tr >
		<td style="text-align: center;" >
			<input type="file" name="listGambar[]" id="listGambar[]" accept="application/pdf"
						 multiple class="BUTTON btn_clear"
						 onchange="checkFile()"
			>
			<input type="BUTTON" value="Upload" onclick="Process('UPLOAD')" class="BUTTON btn_process">
		</td>
	</tr>
	<tr>
		<td align="center">
			<p style="color:red" class="p1"><b>File can not more than 41943040 Bytes (41.94304 Mb)</b></p>
		</td>
	</tr>
</table>

</body>
</html>
<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="FORM_NAME" id="FORM_NAME" value="<?php echo $FORM_NAME; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<script type='text/javascript'>
function checkFile(){
  var x = document.getElementById("listGambar[]");
  var txt = ""; var totSize = 0;
  if ('files' in x) {
    if (x.files.length == 0) {
      txt = "Select one or more files.";
    } else {
      for (var i = 0; i < x.files.length; i++) {
        //txt += "<br><strong>" + (i+1) + ". file</strong><br>";
        var file = x.files[i];
        // if ('name' in file) {
        //   txt += "name: " + file.name + "<br>";
        // }
        if ('size' in file) {
					totSize = totSize + file.size;
          //txt += "size: " + file.size + " bytes <br>";
        }
      }
			//txt = "Total size " + totSize;
			if (totSize > 41943040){
				txt = "File size must not be more than 41943040 Bytes'";
			}
    }
  }
	if (txt!==""){
		x.value = "";
		alert(txt);
	}

}


	function Process(prs){
		//alert(prs);
		var vFormName ="<?php Print($FORM_NAME); ?>";

		var vMsgConfirm = "";
		if (prs==="UPLOAD") {
			vMsgConfirm = "Upload File ?"
		}

		if (vMsgConfirm!==""){
			vConfirm = confirm(vMsgConfirm);
			if (vConfirm !== true){
				prs = vPrs_0;
			}
		}	else {
			prs = vPrs_0;
		}

		//alert(document.getElementById('STSPRS').value);
		document.getElementById('STSPRS').value = prs;
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
