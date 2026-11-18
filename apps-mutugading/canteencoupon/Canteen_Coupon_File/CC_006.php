<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	$ErrMsg = "";
	$STSPRS = "";
	if(isset($_POST['STSPRS'])) {
		$STSPRS=$_POST['STSPRS'];
	}

	$NIK_S = "";
	if(isset($_POST['NIK_S'])) {
		$NIK_S=$_POST['NIK_S'];
	}

	$ECD_DATE_S = "";
	if(isset($_POST['ECD_DATE_S'])) {
		$ECD_DATE_S=$_POST['ECD_DATE_S'];
	}

	$ECD_SYS_ID_S = "";
	if(isset($_POST['ECD_SYS_ID_S'])) {
		$ECD_SYS_ID_S=$_POST['ECD_SYS_ID_S'];
	}

	$ECD_ACTUAL_BREAKTIME_S = "";
	if(isset($_POST['ECD_ACTUAL_BREAKTIME_S'])) {
		$ECD_ACTUAL_BREAKTIME_S=$_POST['ECD_ACTUAL_BREAKTIME_S'];
	}

	$insByAdmin= "";
	if(isset($_POST['insByAdmin'])) {
		$insByAdmin=$_POST['insByAdmin'];
	}

	$stsEdit = "";
	if(isset($_POST['stsEdit'])) {
		$stsEdit=$_POST['stsEdit'];
	}

	$ECO_SYS_ID_S= "";
	if(isset($_POST['ECO_SYS_ID_S'])) {
		$ECO_SYS_ID_S=$_POST['ECO_SYS_ID_S'];
	}
	//echo "NIK_S : $NIK_S";
	//echo "STSPRS -> $STSPRS ; stsEdit --> $stsEdit ; insByAdmin : $insByAdmin";

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

	function fUpdOverTime($conn,$ECO_SYS_ID_S,$ECO_STATUS){
		$sqlPrs = "update MGTAPPS.EMP_COUPON_OVERTIME set ECO_STATUS = ";
		if ($ECO_STATUS!==""){
			$sqlPrs = $sqlPrs."'$ECO_STATUS'";
		}else {
			$sqlPrs = $sqlPrs."null";
		}
		$sqlPrs = $sqlPrs." where ECO_SYS_ID = '$ECO_SYS_ID_S' ";

		//echo $sqlPrs;

	  	$rsPrs = oci_parse($conn,$sqlPrs);
	  	oci_execute($rsPrs, OCI_DEFAULT);
		if (!$rsPrs) {

		    $e = oci_error($rsPrs);  // For oci_execute errors pass the statement handle
		    print htmlentities($e['message']);
		    print "\n<pre>\n";
		    print htmlentities($e['sqltext']);
		    printf("\n%".($e['offset']+1)."s", "^");
		    print  "\n</pre>\n";
		    echo $sqlPrs;
		}
		oci_commit($conn);
		return null;
	}

	//echo "STSPRS $STSPRS";

	$sts="";
	$stsNIK="";
	$stsEdit="";

	$PE_NAMA 		=	"";
	$PE_DEPT_ID 	=	"";
	$PE_LINKFOTO	=	"";
	$PE_LINKFOTO_USER	=	"";
	$ECD_SCHEDULE_BREAKTIME	=	"";
	$ECD_ACTUAL_BREAKTIME	=	"";
	$Refresh="";

	if ($STSPRS === "BTN_CLEAR"){
		$NIK_S = "";
		$ECD_DATE_S = "";
		$PE_NAMA 		=	"";
		$PE_DEPT_ID 	=	"";
		$PE_LINKFOTO	=	"";
		$PE_LINKFOTO_USER	=	"";
		$ECD_SCHEDULE_BREAKTIME	=	"";
		$ECD_ACTUAL_BREAKTIME	=	"";
	}
	$stsNikOut = "";
	if ($NIK_S !== ""){
		include("../CHECK_NIK.PHP");
	 	//$sql = "select count(-1) TOT_DATA from MGTDAT.MGT_PAYROLL_EMP where pe_sts_payroll = 2 and PE_NIK = '$NIK_S'";
		//$totRows = getTotData($conn,$sql);
		//echo "stsNikOut $stsNikOut : ErrMsg $ErrMsg";
		if ($ErrMsg === "" ) {
			if ($stsNikOut === "Y"){
					$sqlData =	"select 	nvl(OS_NAME,' ')PE_NAMA
					        		,nvl(OS_DEPT,' ')PE_DEPT_ID
					        		,'foto/NO PICTURE.jpg' PE_LINKFOTO
					        		,'foto_user/'||OS_BARCODE_ID||'.jpg' PE_LINKFOTO_USER
					        		,' 'ECD_SCHEDULE_BREAKTIME
					        		,' 'ECD_SCHEDULE_BREAKTIME_END
					        		,to_char(sysdate,'HH24:MI:SS') ECD_ACTUAL_BREAKTIME
								from mgtattd.mgt_employee_os t1
								where OS_BARCODE_ID = '$NIK_S' ";
			}else{
				$sqlData = "select 	PE_NAMA,PE_DEPT_ID
									,nvl(replace(substr(PE_LINKFOTO,3),'192.168.0.2\Primata\Shared Folder\Photos\','foto\'),'foto\NO PICTURE.jpg') PE_LINKFOTO
									,nvl('../foto_user/'||PE_LINKFOTO_USER,'../foto/NO PICTURE.jpg') PE_LINKFOTO_USER
									,nvl(to_char(MPSM_BREAK_START,'HH24:MI:SS'),' ') ECD_SCHEDULE_BREAKTIME
									,to_char(sysdate,'HH24:MI:SS') ECD_ACTUAL_BREAKTIME
							from MGTDAT.MGT_PAYROLL_EMP t1
							     ,(
							     	select *
							     	from mgtapps.EMP_JADWAL
							     		,MGTDAT.MGT_PAYROLL_SCH_MASTER t3
							     	where to_char(EJ_MPJS_TGL,'YYYY-MM-DD') = '$ECD_DATE_S'
							     	and EJ_MPJS_NOSHIFT = MPSM_SCH
							     ) t2
							where pe_sts_payroll = 2
							and t1.PE_NIK = t2.EJ_MPJS_NIK(+)
							and t1.PE_NIK = '$NIK_S'";
			}
			//echo "$sqlData </br>";
			$rsData = oci_parse($conn,$sqlData);
			oci_execute ($rsData);
			while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
				$PE_NAMA 		=	$rowData['PE_NAMA'];
				$PE_DEPT_ID 	=	$rowData['PE_DEPT_ID'];
				$PE_LINKFOTO	=	$rowData['PE_LINKFOTO'];
				$PE_LINKFOTO_USER	=	$rowData['PE_LINKFOTO_USER'];
				$ECD_SCHEDULE_BREAKTIME	=	$rowData['ECD_SCHEDULE_BREAKTIME'];
				$ECD_ACTUAL_BREAKTIME	=	$rowData['ECD_ACTUAL_BREAKTIME'];
			}
			$stsNIK="Y";
		}
	}
	//echo "</br>ErrMsg $ErrMsg";

	if ($ErrMsg === ""){
		if ($ECD_DATE_S!==""){
			$sqlDt = "
					select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_DATA_SUM
					where trunc(ECDS_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
					";
			//echo $sqlDt;
			$totRows = getTotData($conn,$sqlDt);
			if ($totRows!=="0") {
				$ErrMsg = "Total Kupon untuk tanggal $ECD_DATE_S sudah disetujui kantin !!!";
			}
		}
	}

	if ($ErrMsg === ""){
		if (//$ECD_DATE_S !== "" &&
			$ECD_ACTUAL_BREAKTIME_S !=="" ){
			$sqlDt = "
					select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_DATA
					where trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
					and ECD_PE_NIK = '$NIK_S'
					and ECD_INS_BY_ADMIN is null
					and ECD_ECO_SYS_ID is not null
					";
			//echo $sqlDt;
			$totRows = getTotData($conn,$sqlDt);
			if ($totRows!=="0") {
				if ($STSPRS !== "BTN_EDIT"){
					$ErrMsg = "Coupon Data NIK $NIK_S tanggal $ECD_DATE_S sudah diambil langsung di kantin !!!";
				}
			}

			if ($ErrMsg===""){
				$sqlDt = "
						select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_DATA
						where trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
						and ECD_PE_NIK = '$NIK_S'
						and ECD_INS_BY_ADMIN = 'Y'
						and ECD_ECO_SYS_ID is not null
						";
				//echo $sqlDt;
				$totRows = getTotData($conn,$sqlDt);
				if ($totRows!=="0") {
					if ($STSPRS !== "BTN_EDIT"){
						$ErrMsg = "Coupon Data NIK $NIK_S tanggal $ECD_DATE_S sudah Entry !!!";
						$sqlData = "select 	to_char(ECD_ACTUAL_BREAKTIME,'YYYY-MM-DD')||'T'||to_char(ECD_ACTUAL_BREAKTIME,'HH24:MI') ECD_ACTUAL_BREAKTIME
											,ECD_SYS_ID
									from EMP_COUPON_DATA
									where ECD_PE_NIK = '$NIK_S'
									and trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
									and ECD_ECO_SYS_ID is not null";
						$rsData = oci_parse($conn,$sqlData);
						oci_execute ($rsData);
						while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
							$ECD_ACTUAL_BREAKTIME_S	= $rowData['ECD_ACTUAL_BREAKTIME'];
							$ECD_SYS_ID_S = $rowData['ECD_SYS_ID'];
						}
					}
					$stsEdit="Y";
					$insByAdmin = "Y";
				}
			}
		}


		if ($STSPRS==="BTN_DELETE"){
			$sqlPrs = "delete from MGTAPPS.EMP_COUPON_DATA where ECD_SYS_ID = '$ECD_SYS_ID_S' ";
			//echo $sqlPrs;//die();
		  	$rsPrs = oci_parse($conn,$sqlPrs);
		  	oci_execute($rsPrs, OCI_DEFAULT);
			if (!$rsPrs) {

			    $e = oci_error($rsPrs);  // For oci_execute errors pass the statement handle
			    print htmlentities($e['message']);
			    print "\n<pre>\n";
			    print htmlentities($e['sqltext']);
			    printf("\n%".($e['offset']+1)."s", "^");
			    print  "\n</pre>\n";
			    echo $sqlPrs;
			}
			oci_commit($conn);

			$exe = fUpdOverTime($conn,$ECO_SYS_ID_S,"");

	        echo "<script type='text/javascript'>";
	        echo "alert('Data telah terhapus');";
	        echo "</script>";
			$Refresh="Y";
	        $STSPRS = "";
	        $NIK_S = "";
	        $ECD_DATE_S = "";
	        $ECD_ACTUAL_BREAKTIME_S = "";
	        $ECO_SYS_ID_S = "";
		}else if ($STSPRS==="CHECK_DATA"){
			$sqlDt = "
					select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_DATA
					where trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
					and ECD_PE_NIK = '$NIK_S' and ECD_ECO_SYS_ID is not null
					";
			//echo $sqlDt;
			$totRows = getTotData($conn,$sqlDt);
			if ($totRows==="0"){
		        echo "<script type='text/javascript'>";
		        echo "alert('Coupon Overtime Data NIK $NIK_S Coupon Date $ECD_DATE_S not Exist');";
		        echo "</script>";
		        $insByAdmin="Y";
			} else {
				//getDataPrs($conn,$NIK_S,$ECD_DATE_S);
				$sqlData = "select 	to_char(max(ECD_ACTUAL_BREAKTIME),'YYYY-MM-DD')
							||'T'||to_char(max(ECD_ACTUAL_BREAKTIME),'HH24:MI:SS') ECD_ACTUAL_BREAKTIME
									,ECD_SYS_ID
									,ECD_ECO_SYS_ID
							from EMP_COUPON_DATA
							where ECD_PE_NIK = '$NIK_S'
							and trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD')) and ECD_ECO_SYS_ID is not null
							group by ECD_SYS_ID,ECD_ECO_SYS_ID
							";
				//echo "</br> $sqlData";
				$rsData = oci_parse($conn,$sqlData);
				oci_execute ($rsData);
				while ($rowData = oci_fetch_array ($rsData, OCI_BOTH)) {
					$ECD_ACTUAL_BREAKTIME_S	= $rowData['ECD_ACTUAL_BREAKTIME'];
					$ECD_SYS_ID_S = $rowData['ECD_SYS_ID'];
					$ECO_SYS_ID_S = $rowData['ECD_ECO_SYS_ID'];
				}
				$sqlDt = 	"select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_DATA
							where trunc(ECD_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
							and ECD_PE_NIK = '$NIK_S' and ECD_INS_BY_ADMIN = 'Y' and ECD_ECO_SYS_ID is not null";
				//echo $sqlDt;
				$stsEdit = "Y";
				if(getTotData($conn,$sqlDt)!=="0"){
					$insByAdmin = "Y";
				}
			}
	        $STSPRS="";
			//$Refresh="Y";
		}else if ($STSPRS==="BTN_EDIT"){
			$sqlPrs = "update MGTAPPS.EMP_COUPON_DATA
							set	ECD_DATE = to_date('$ECD_DATE_S','YYYY-MM-DD')
								,ECD_ACTUAL_BREAKTIME = to_date(replace('$ECD_ACTUAL_BREAKTIME_S','T',' '),'YYYY-MM-DD HH24:MI:SS')
						  where ECD_SYS_ID = '$ECD_SYS_ID_S' ";

			//echo $sqlPrs;die();
		  	$rsPrs = oci_parse($conn,$sqlPrs);
		  	oci_execute($rsPrs, OCI_DEFAULT);
			if (!$rsPrs) {

			    $e = oci_error($rsPrs);  // For oci_execute errors pass the statement handle
			    print htmlentities($e['message']);
			    print "\n<pre>\n";
			    print htmlentities($e['sqltext']);
			    printf("\n%".($e['offset']+1)."s", "^");
			    print  "\n</pre>\n";
			    echo $sqlPrs;
			}
			oci_commit($conn);
	        echo "<script type='text/javascript'>";
	        echo "alert('Data telah Diupdate');";
	        echo "</script>";
			//$Refresh="Y";
		}else if ($STSPRS==="BTN_SAVE"){
			$sqlDt = "
					select count(-1) TOT_DATA from MGTAPPS.EMP_COUPON_OVERTIME
					where trunc(ECO_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
					and ECO_PE_NIK = '$NIK_S'
					and ECO_STATUS is null
					";
			//echo $sqlDt;
			$totRows = getTotData($conn,$sqlDt);
			if ($totRows==="0"){
				$ErrMsg="Entry Overtime First for NIK '$NIK_S' at Coupon Date  $ECD_DATE_S !!!";
			}
			if ($ErrMsg===""){

				$sqlDt = "select ECO_SYS_ID GET_DATA from MGTAPPS.EMP_COUPON_OVERTIME
						where trunc(ECO_DATE) = trunc(to_date('$ECD_DATE_S','YYYY-MM-DD'))
						and ECO_PE_NIK = '$NIK_S'
						and ECO_STATUS is null
						";
				//echo $sqlDt;
				$ECD_ECO_SYS_ID = getData($conn,$sqlDt);

				if ($ECD_SCHEDULE_BREAKTIME!==""){
					$ECD_SCHEDULE_BREAKTIME = "12:00:00";
				}

				$PE_NAMA = str_replace("'","''", $PE_NAMA);

				$sqlInsrt = "insert into MGTAPPS.EMP_COUPON_DATA( "
							."ECD_SYS_ID, ECD_CREATED_BY, ECD_CREATED_TIMESTAMP " //1
							.",ECD_PE_NIK, ECD_PE_NAMA, ECD_PE_DEPT_ID " //2
							.", ECD_DATE,ECD_INS_BY_ADMIN " //3
							.", ECD_SCHEDULE_BREAKTIME" //4
							.", ECD_ACTUAL_BREAKTIME" //5
							.",	ECD_ECO_SYS_ID"
							.")values("
							."'123','$USER_NAME',sysdate" //1
							.",'$NIK_S','$PE_NAMA','$PE_DEPT_ID' " //2
							.",to_date('$ECD_DATE_S','YYYY-MM-DD'),'Y'"//3
							.",to_date('$ECD_DATE_S$ECD_SCHEDULE_BREAKTIME','YYYY-MM-DDHH24:MI:SS')"//4
							.",to_date(replace('$ECD_ACTUAL_BREAKTIME_S','T',' '),'YYYY-MM-DD HH24:MI:SS')" //5
							.",'$ECD_ECO_SYS_ID'"
							.")";

				//echo $sqlInsrt;die();
			  	$rsInst = oci_parse($conn,$sqlInsrt);
			  	oci_execute($rsInst, OCI_DEFAULT);
				if (!$rsInst) {
				    //echo $sqlInsrt;
				    $e = oci_error($rsInst);  // For oci_execute errors pass the statement handle
				    print htmlentities($e['message']);
				    print "\n<pre>\n";
				    print htmlentities($e['sqltext']);
				    printf("\n%".($e['offset']+1)."s", "^");
				    print  "\n</pre>\n";
				    //echo $sqlInsrt;
				}
			  	oci_commit($conn);
				$ECO_SYS_ID_S = $ECD_ECO_SYS_ID;
			  	$exe = fUpdOverTime($conn,$ECO_SYS_ID_S,"1");

			  	$sqlInsrt="";
			  	$STSPRS = "";
			  	$ErrMsg="Data Telah Tersimpan";
			  	$stsEdit = "Y";
			  	$insByAdmin = "Y";
			}
		}

		if ($Refresh=== "Y"){
		    echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
	        echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
	        echo "<input type='hidden' id='STSPRS' name='STSPRS' value='$STSPRS'>";
	        echo "<input type='hidden' id='NIK_S' name='NIK_S' value='$NIK_S'>";
	        echo "<input type='hidden' id='ECD_DATE_S' name='ECD_DATE_S' value='$ECD_DATE_S'>";
	        echo "<input type='hidden' id='ECD_ACTUAL_BREAKTIME_S' name='ECD_ACTUAL_BREAKTIME_S' value='$ECD_ACTUAL_BREAKTIME_S'>";
	        echo "<input type='hidden' id='ECD_SYS_ID_S' name='ECD_SYS_ID_S' value='$ECD_SYS_ID_S'>";
	        echo "</form>";
	        echo "<script type='text/javascript'>";
	        echo "document.getElementById('$FORM_NAME').submit();";
	        echo "</script>";
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
<link rel="stylesheet" type="text/css" href="../css/MENU.css">
<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
<link rel="stylesheet" type="text/css" href="../css/BLINK_KARAKTER.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style type="text/css">
.centerImage
{
    margin: auto;
    display: block;
}
</style>
</head>
<body onload="Javascript: document.getElementById('NIK').focus();">
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:15px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<div class="main" style="overflow-x:auto;">
	<table style="width:100%"  border="1" align="center">
		<tr>
			<td align="right" style="width:30%">
				<p style="<?php echo $styleTxt;?>">
					NIK
				</p>
			</td>
			<td align="left" style="width:30%">
				<p style="<?php echo $styleTxt;?>">
					<input type="text" id="NIK" name="NIK"
					value="<?php echo $NIK_S; ?>"
					onkeypress="Javascript: if (event.keyCode==13) Process('');"

					<?php
						if ($stsNIK==="Y"){
							echo "readonly";
						}
					?>
					>
				</p>
			</td>
			<td rowspan="5">
			<?php
				//echo "$PE_LINKFOTO<br>";
				if ($PE_LINKFOTO!==""){
					//echo "$PE_LINKFOTO_USER<br>";
					if(!file_exists($PE_LINKFOTO_USER)){
						$PE_LINKFOTO_USER = "../foto/NO PICTURE.jpg";
					}
					//echo "$PE_LINKFOTO_USER<br>";
				?>
				<img src="<?php echo $PE_LINKFOTO_USER ?>"
					 alt="<?php echo "$NIK_S - $PE_NAMA" ?>" style="width:300px;height:300px;"
					 class="centerImage"
				>
			<?php } ?>
			</td>
		<tr>
			<td align="right">
				<p style="<?php echo $styleTxt;?>">
					NAMA
				</p>
			</td>
			<td align="left">
				<p style="<?php echo $styleTxt;?>">
	<?php
					if ($PE_NAMA!==""){
						echo "$NIK_S - $PE_NAMA";
					}
	?>
				</p>
			</td>
		</tr>
		<tr>
			<td align="right">
				<p style="<?php echo $styleTxt;?>">
					DEPARTEMEN
				</p>
			</td>
			<td align="left">
				<p style="<?php echo $styleTxt;?>">
	<?php
					if ($PE_DEPT_ID!==""){
						echo $PE_DEPT_ID;
					}
	?>
				</p>
			</td>
		</tr>
		<tr>
			<td align="right">
				<p style="<?php echo $styleTxt;?>">
					COUPON OVERTIME DATE
				</p>
			</td>
			<td align="left">
				<p style="<?php echo $styleTxt;?>">
					<b>
						<input 	type="date" id="ECD_DATE" name="ECD_DATE" value="<?php echo $ECD_DATE_S;?>"
								onkeypress="Javascript: if (event.keyCode==13) Process('CHECK_DATA');"
						>
					</b>
				</p>
				<input type="button" id="BTN_VIEW" name="BTN_VIEW" value="Check Data" onclick="Process('CHECK_DATA')" class="BUTTON btn_view">
			</td>
		</tr>
		<tr>
			<td align="right">
				<p style="<?php echo $styleTxt;?>">
					REAL BREAK OVER TIME
				</p>
			</td>
			<td align="left">
				<p style="<?php echo $styleTxt;?>">
				<input type="datetime-local" id="ECD_ACTUAL_BREAKTIME" name="ECD_ACTUAL_BREAKTIME" value="<?php echo $ECD_ACTUAL_BREAKTIME_S; ?>">
				</p>
			</td>
		</tr>
<?php
	//echo "</br>ErrMsg $ErrMsg ; NIK_S $NIK_S";
	if ($ErrMsg==="" && $insByAdmin ==="" && $stsEdit ==="Y" ){
		$ErrMsg = "Insert by Scan Canteen";
	}
	if ($ErrMsg!=="" && $NIK_S===""){
		$ErrMsg="";
	}
	if ($ErrMsg!=="") {
		echo "<tr>";
		echo "	<td align='center' style='width:100%' colspan='4'>";
		echo "	<b><span class='blink'><p style='font-family:verdana;font-size:15px;color:red'>$ErrMsg</p></span></b>";
		echo "	</td>";
		echo "</tr>";
	}
?>
		<tr>
			<td align="center" colspan="4">
<?php 	if ($stsNIK==="Y"){
	 		if ($stsEdit===""){
				if ($insByAdmin==="Y"){
	?>
				<input type="button" value="SAVE" id="BTN_SAVE" name="BTN_SAVE" class="BUTTON btn_save" onclick="Process('BTN_SAVE')">
<?php 			}
			}else if ($stsEdit==="Y"){
					if ($insByAdmin==="Y"){
				?>
				<input type="button" value="Save EDIT" id="BTN_EDIT" name="BTN_EDIT" class="BUTTON btn_save" onclick="Process('BTN_EDIT')">
				<input type="button" value="DELETE" id="BTN_EDIT" name="BTN_EDIT" class="BUTTON btn_delete" onclick="Process('BTN_DELETE')">
<?php				}
			}
		} ?>
			<input type="button" value="CLEAR" id="BTN_CLEAR" name="BTN_CLEAR" class="BUTTON btn_clear" onclick="Process('BTN_CLEAR')">
			</td>
		</tr>

	</table>

</div>
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="STSPRS" id="STSPRS">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO">
<input type="hidden" name="NIK_S" id="NIK_S">
<input type="hidden" name="ECD_DATE_S" id="ECD_DATE_S">
<input type="hidden" name="ECD_ACTUAL_BREAKTIME_S" id="ECD_ACTUAL_BREAKTIME_S">
<input type="hidden" name="ECD_SYS_ID_S" id="ECD_SYS_ID_S" value="<?php echo $ECD_SYS_ID_S; ?>">
<input type="hidden" name="insByAdmin" id="insByAdmin" value="<?php echo $insByAdmin; ?>">
<input type="hidden" name="ECO_SYS_ID_S" id="ECO_SYS_ID_S"  value="<?php echo $ECO_SYS_ID_S; ?>">
<script type='text/javascript'>
	function Process(prs){
		//alert(prs);
		var prs0 = prs;
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var stsNIK ="<?php Print($stsNIK); ?>";
		var ErrMsg = ""; var vMsgConfirm = "";
		var vECD_SYS_ID = "";
		var vRecNo = "";

		var vECD_DATE = "";

		if (document.getElementById('NIK').value===""){
			ErrMsg = "Nik can't Null";
		}else {
			document.getElementById('NIK_S').value = document.getElementById('NIK').value;
		}

		if (prs==="BTN_DELETE"){
			document.getElementById('ECD_DATE_S').value = document.getElementById('ECD_DATE').value;
			document.getElementById('ECD_ACTUAL_BREAKTIME_S').value = document.getElementById('ECD_ACTUAL_BREAKTIME').value;
			vMsgConfirm = "Delete Data ?";
		}else if (prs==="CHECK_DATA"){
			document.getElementById('NIK_S').value = document.getElementById('NIK').value;
			if (document.getElementById('ECD_DATE').value ==="" ){
				ErrMsg = "COUPON DATE can't Null";
				prs = "";
			} else {
				document.getElementById('ECD_DATE_S').value = document.getElementById('ECD_DATE').value;
			}
		}
		else if (prs==="BTN_EDIT"){
			document.getElementById('NIK_S').value = document.getElementById('NIK').value;
			document.getElementById('ECD_DATE_S').value = document.getElementById('ECD_DATE').value;
			document.getElementById('ECD_ACTUAL_BREAKTIME_S').value = document.getElementById('ECD_ACTUAL_BREAKTIME').value;
			vMsgConfirm = "Save Perubahan Data ?";
		}else if (prs==="BTN_SAVE"){
			if (ErrMsg===""){
				if (document.getElementById('ECD_ACTUAL_BREAKTIME').value===""){
					ErrMsg = "Actual Break Time can't Null";
				}
			}

			if (ErrMsg===""){
				if (document.getElementById('NIK').value===""){
					ErrMsg = "NIK can't Null";
				}
			}

			document.getElementById('ECD_ACTUAL_BREAKTIME_S').value = document.getElementById('ECD_ACTUAL_BREAKTIME').value;
			document.getElementById('ECD_DATE_S').value = document.getElementById('ECD_DATE').value;
			document.getElementById('NIK_S').value = document.getElementById('NIK').value;
			// if (ErrMsg===""){
			// 	prs = "INSERT DATA";
			// }
			vMsgConfirm = "Save Data ?";
		}else if (prs==="BTN_CLEAR"){
			vMsgConfirm = "Clear Data ?";
		}

		if (ErrMsg!==""){
			alert(ErrMsg);
		} else {
			if (vMsgConfirm!==""){
				if (confirm(vMsgConfirm)!==true){
					if (prs!=="BTN_CLEAR"){
						prs = "";
					}
					document.getElementById('ECD_DATE').value = document.getElementById('ECD_DATE_S').value;
					document.getElementById('ECD_ACTUAL_BREAKTIME').value = document.getElementById('ECD_ACTUAL_BREAKTIME_S').value;
					document.getElementById('NIK_S').value = "<?php Print($NIK_S); ?>";
				} else {
					//alert(prs);
					if(prs==="BTN_CLEAR") {
						document.getElementById('ECD_DATE').value = "";
						document.getElementById('ECD_ACTUAL_BREAKTIME').value = "";
						document.getElementById('NIK').value = "";
						document.getElementById('NIK_S').value = "";
						document.getElementById('ECO_SYS_ID_S').value = "";
						document.getElementById('ECD_SYS_ID_S').value = "";
						document.getElementById('insByAdmin').value = "";
					} else {
						document.getElementById('ECD_DATE_S').value = document.getElementById('ECD_DATE').value;
						document.getElementById('ECD_ACTUAL_BREAKTIME_S').value = document.getElementById('ECD_ACTUAL_BREAKTIME').value;
						document.getElementById('NIK_S').value = "<?php Print($NIK_S); ?>";
					}
				}
			}
		}
		//alert(prs);
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
