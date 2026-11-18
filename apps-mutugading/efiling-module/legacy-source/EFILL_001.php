<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$STSPRS = "";	if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

	//echo "STSPRS $STSPRS";

	$FILE_FROM_S = "";if(isset($_POST['FILE_FROM_S'])) {$FILE_FROM_S=$_POST['FILE_FROM_S'];}

	$FILE_TO_S = "";if(isset($_POST['FILE_TO_S'])) {$FILE_TO_S=$_POST['FILE_TO_S'];}

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

	$dir = "D:/XAMPP/htdocs/webapps/SCAN_DOC/$USER_NAME";

?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../head_component.php") ?>
</head>
<body onload="Javascript: document.getElementById('FILE_TO[]').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
<input type="hidden" name="FILE_FROM_S" id="FILE_FROM_S" echo="<?php echo $FILE_FROM_S; ?>" >
<input type="hidden" name="FILE_TO_S" id="FILE_TO_S"  echo="<?php echo $FILE_TO_S; ?>">
<!-- DEFINE LAYOUT -->

<?php

	if ($STSPRS==="OK"){

		$msgBlink = "";
		if ($FILE_FROM_S!=="" && $FILE_TO_S !==""){
			rename($dir."/".$FILE_FROM_S,$dir."/".$FILE_TO_S.".pdf");
			$msgBlink = "RENAME FILE ".$FILE_FROM_S." to ".$FILE_TO_S.".pdf Complete";
		} else {
			$msgBlink = "FILE TO CAN'T NULL";
		}

		if ($msgBlink!==""){
?>
		<table style="width:100%" >
		<tr>
			<td colspan="2" align="center">
				<span class="blink">
					<p style="font-family:verdana;font-size:40px;color: red;">
					<?php
							echo "<b>  </b>";
					?>
					</p>
				</span>
			</td>
		</tr>
		</table>
<?php
		}

		$STSPRS = "";
	}
	//echo "dir $dir</br>";
	if (is_dir($dir)){
	  if ($dh = opendir($dir)){
	  	$recNo = 0;
	    while (($file = readdir($dh)) !== false){
	    if ($recNo===0){

	    	$fileExist = "N";
	    	//cek exist file in folder scan
	    	//
	    	if (strpos(strtoupper($file),"SCAN") === TRUE) {
	    		$fileExist ="Y";
	    	} else {
    			$sqlGetData = 	"select MGTAPPS.pkg_effil_prs.fCekFormatFile(upper('$file')) GET_DATA from dual";

				$fileExist = getData($conn,$sqlGetData);
				//echo "$sqlGetData $file $fileExist </br>";
	    	}
	    		//view document
	    		if ($fileExist ==="Y")
	    		{

?>
<table style="width:100%;"  border="0" >

	<tr>
		<td align="center">
		<p style="font-family:verdana;font-size:20px;color: red;">
		FORMAT FILE SCAN : </br>
		<?php echo getData($conn,"select MGTAPPS.pkg_effil_prs.fListFormatScan GET_DATA from dual"); ?>
		</p>
		</td>
	</tr>
	<tr >
		<td align="center">
			Source File Name : <input type="text" id="FILE_FROM[]" name="FILE_FROM[]" value="<?php echo $file; ?>" readonly>
			File To : 	<input 	type="text" id="FILE_TO[]" name="FILE_TO[]"
								onkeypress="Javascript: if (event.keyCode==13) Process('RENAME <?php echo $recNo; ?>'); ">
						<input 	type="BUTTON" class="BUTTON btn_process" value="Rename" onclick="Process('RENAME <?php echo $recNo; ?>')">

			<input type="hidden" id="REC_NO[]" name="REC_NO[]" value="<?php echo $recNo;?>">
		</td>
	</tr>
</table>
<table style="width:100%;height:70%"  border="1" >
	<tr>
		<td align="center" >
<?php

		echo "<iframe src=\"SCAN_DOC/$USER_NAME/".$file."\" width=\"100%\" style=\"height:100%\"></iframe>";
?>
		</td>
	</tr>
<?php
			$recNo = $recNo + 1;
		    	}
		    	//end view document
		    }
		    }
		    closedir($dh);
		  }
?>
</table>
<?php
		}
	//}
	//echo "$FILE_FROM_S --> $FILE_TO_S";
?>
<!-- DEFINE LAYOUT -->
</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<script type='text/javascript'>
	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vRecNo = 0;
		//alert(prs.substr(0,6));
		if (prs.substr(0,6)==="RENAME"){
			vRecNo	 = prs.substr(7);
		}

		var TotData ="<?php Print($recNo); ?>";
			TotData = Number(TotData) - 1;

		//for (var i = 0; i < vKetKantin.length; i++) {
		for (var i = 0; i <= TotData; i++) {
        	if (vRecNo==i){
				vDt = 	document.getElementsByName('FILE_FROM[]');
	            document.getElementById('FILE_FROM_S').value = vDt[i].value;
				vDt = 	document.getElementsByName('FILE_TO[]');
    	        document.getElementById('FILE_TO_S').value = vDt[i].value;
        	}
        }

        if (document.getElementById('FILE_TO_S').value!==""){
	        vConfirm = confirm("RENAME FILE " + document.getElementById('FILE_FROM_S').value + " TO " + document.getElementById('FILE_TO_S').value);
			if (vConfirm === true){
				document.getElementById('STSPRS').value = "OK";
				//alert(document.getElementById('STSPRS').value);
			}
		} else {
			alert("Type New File Name");
		}
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
