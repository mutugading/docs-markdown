<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	//Get Parameter
	$currDate=date('Y-m-d');
	$STSPRS = "";if(isset($_POST['STSPRS'])) {$STSPRS=$_POST['STSPRS'];}

	$P_PAGE_NO = "";if(isset($_POST['P_PAGE_NO'])) {$P_PAGE_NO=$_POST['P_PAGE_NO'];}
	if ($P_PAGE_NO===""){
		$P_PAGE_NO= 1;
	}
	$P_LAST_PAGE_NO = "";
  $font="11";
	$FIND_SELECT_S= "";
	if(isset($_POST['FIND_SELECT_S'])) {
		$FIND_SELECT_S=$_POST['FIND_SELECT_S'];
	}
	$FIND_SELECT_VAL_S= "";
	if(isset($_POST['FIND_SELECT_VAL_S'])) {
		$FIND_SELECT_VAL_S=$_POST['FIND_SELECT_VAL_S'];
	}
	$USER_ID_S= "";
	if(isset($_POST['USER_ID_S'])) {
		$USER_ID_S=$_POST['USER_ID_S'];
	}
	if ($USER_ID_S=== ""){
		$USER_ID_S= "ALL";
	}

	$Date_From_S= "";
	if(isset($_POST['Date_From_S'])) {
		$Date_From_S=$_POST['Date_From_S'];
	}
	if ($Date_From_S=== ""){
		$Date_From_S= $currDate;
	}
	$Date_End_S= "";
	if(isset($_POST['Date_End_S'])) {
		$Date_End_S=$_POST['Date_End_S'];
	}
	if ($Date_End_S=== ""){
		$Date_End_S= $currDate;
	}
	//Get Parameter

	//$dateH = date("H");
	//$date = date("Y-m-d");
	//$date = str_replace('-', '/', $date);

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

	$sqlGetData = 	"select 'Y' GET_DATA from mgtapps.MST_GROUPS g ,mgtapps.MST_GROUP_USERS u where groups_name = 'ADMIN' and g.GROUPS_ID = u.GROUPS_ID "
					."and u.USER_ID = '$USER_NAME'";
	$isUserAdmin = getData($conn,$sqlGetData);
	//echo "$USER_NAME is user Admin --> $isUserAdmin : USER_ID $USER_ID_S</br>";

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
<form action="<?php echo $FORM_NAME.'.PHP'; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<!-- DEFINE LAYOUT -->
<div class="main" style="overflow-x:auto;" align="center">
<?php
	include($FORM_NAME."_PARAM.php");

	$where = " and trunc(ELA_CREATED_TIMESTAMP) between to_date('$Date_From_S','YYYY-MM-DD') and to_date('$Date_End_S','YYYY-MM-DD') ";
	if ($isUserAdmin!=="Y"){
		$where = " $where and ELA_CREATED_BY = '$USER_NAME' ";
	} else {
		if ($USER_ID_S !== "ALL"){
			$where = " $where and ELA_CREATED_BY = '$USER_ID_S' ";
		}
	}

	if ($FIND_SELECT_S==="FIND_DATA ACCESS_DATE"){
		if ($FIND_SELECT_VAL_S !==""){
			$where = " $where AND to_char(ELA_CREATED_TIMESTAMP,'YYYY-MM-DD') like upper('$FIND_SELECT_VAL_S') ";
		}
	}else if($FIND_SELECT_S==="FIND_DATA DETAIL_ACCESS"){
		if ($FIND_SELECT_VAL_S !==""){
			$where = " $where AND upper(ELA_DETAIL_ACCESS) like '%'||upper('$FIND_SELECT_VAL_S')||'%' ";
		}
	}

	//echo "Date_From_S $Date_From_S Date_End_S  $Date_End_S";


	$sqlDt = 	"select rownum NO_REC,d.*
				from (	select
							 ELA_CREATED_BY
							, to_char(ELA_CREATED_TIMESTAMP,'YYYY-MM-DD HH24:MI:SS') ELA_CREATED_TIMESTAMP
							, ELA_DETAIL_ACCESS,USER_NAME,USER_ID
						from efill_log_access d	,mgtapps.mst_users u
					where ELA_CREATED_BY = USER_ID $where
					order by ELA_CREATED_TIMESTAMP desc
				) d ";


	$sqlTotRows = "select count(-1) TOT_DATA from ( $sqlDt ) ";
	// if ($USER_NAME === "1949"){
	// 	echo "$sqlTotRows</br>";
	// }
	$totRows = getTotData($conn,$sqlTotRows);

	$P_ROWS_DISP = 30;
    $P_LAST_PAGE_NO = ($totRows / $P_ROWS_DISP);
    //echo "P_LAST_PAGE_NO $P_LAST_PAGE_NO";
    if (strpos($P_LAST_PAGE_NO,'.') > 0 ){
    	$P_LAST_PAGE_NO = substr($P_LAST_PAGE_NO,0,strpos($P_LAST_PAGE_NO,'.'));
    	$P_LAST_PAGE_NO = $P_LAST_PAGE_NO + 1;
    }
    if ($P_LAST_PAGE_NO==0){
    	$P_LAST_PAGE_NO=1;
    }
  	if ($P_PAGE_NO=="1"){
	    $P_ROW_START  = 1;
	    $P_ROW_END    = $P_ROWS_DISP;
	} else {
	    $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
	    $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
	}
?>
<table style="width:95%" border="1">
	<tr >
		<td  <?php echo getStyle($font,"center"); ?> class="a" colspan="2">
			<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
			<input type="number" name="PAGE_NO" id="PAGE_NO" style="width: 55px;"  value="<?php echo $P_PAGE_NO;?>"
					onkeypress="Javascript: if (event.keyCode==13) GoRecord();"
			>
			: <?php echo $P_LAST_PAGE_NO; ?>
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			FIND BY
			<select name="FIND_SELECT" id="FIND_SELECT" onchange="Process('FIND_SELECT')">
				<option value="NULL">NULL</option>
				<option value="FIND_DATA ACCESS_DATE"
						<?php if($FIND_SELECT_S==="FIND_DATA ACCESS_DATE"){echo "selected";} ?>
				>
					ACCESS DATE
				</option>
				<option value="FIND_DATA DETAIL_ACCESS"
						<?php if($FIND_SELECT_S==="FIND_DATA DETAIL_ACCESS"){echo "selected";} ?> >
					DETAIL ACCESS
				</option>
				}
			</select>
			<input type="hidden" name="FIND_SELECT_S" id="FIND_SELECT_S">
			<?php
				if($FIND_SELECT_S==="FIND_DATA ACCESS_DATE") {
			?>
				<input type="date" name="FIND_SELECT_VAL" id="FIND_SELECT_VAL" value="<?php echo $FIND_SELECT_VAL_S;?>"
					   onchange="Process('FIND_DATA');"
				>
			<?php
				} else {
			?>
				<input type="text" name="FIND_SELECT_VAL" id="FIND_SELECT_VAL" value="<?php echo $FIND_SELECT_VAL_S;?>"
					   onkeypress="Javascript: if (event.keyCode==13) Process('FIND_DATA');"
				>
			<?php
				}
			?>
			<input type="button" value="FIND" onclick="Process('FIND_DATA')">
			<input type="hidden" name="FIND_SELECT_VAL_S" id="FIND_SELECT_VAL_S">
		</td>
	</tr>
	<tr>
		<td <?php echo getStyle($font,"center","3"); ?>>
			No
		</td>
		<td <?php echo getStyle($font,"center","20"); ?>>
			Access Date
		</td>
		<td <?php echo getStyle($font,"center"); ?> >
			Detail Access
		</td>
	</tr>
<?php
	$sqlDt = "select * from ($sqlDt) where NO_REC between $P_ROW_START and $P_ROW_END ";

	// if ($USER_NAME === "1949") {
	// 		echo $sqlDt;
	// }

	$rsDt = oci_parse($conn,$sqlDt);
	oci_execute ($rsDt);
	while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
?>
	<tr>
		<td <?php echo getStyle($font,"center","5"); ?>>
			<?php echo $rowDt['NO_REC']; ?>
		</td>
		<td <?php echo getStyle($font,"center","20"); ?>>
			<?php echo $rowDt['ELA_CREATED_TIMESTAMP']; ?>
		</td>
		<td <?php echo getStyle($font,"left"); ?> >
			<?php
				if ($isUserAdmin==="Y"){
					echo $rowDt['USER_NAME'].'-'.$rowDt['USER_ID']."</br>";
				}
				echo $rowDt['ELA_DETAIL_ACCESS'];
			?>
		</td>
	</tr>
<?php
	}
?>
</table>
</div>
<!-- DEFINE LAYOUT -->
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="P_PAGE_NO" id="P_PAGE_NO" value="<?php echo $P_PAGE_NO; ?>">

<input type="hidden" name="STSPRS" id="STSPRS">

<input type="hidden" name="USER_ID_S" id="USER_ID_S">
<input type="hidden" name="Date_From_S" id="Date_From_S">
<input type="hidden" name="Date_End_S" id="Date_End_S">

<?php	echo include($FORM_NAME."_JS.php"); ?>

</form>
</body>
</html>
