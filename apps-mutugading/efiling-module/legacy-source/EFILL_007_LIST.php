<!-- DEFINE LAYOUT -->
<?php
	$sqlView =
		"select
        distinct
        substr(PAYMENT_NO,1,instr(PAYMENT_NO,'-')-1) PAYMENT_NO_FOLDER,PAYMENT_NO
        ,substr(PAYMENT_NO,instr(PAYMENT_NO,'-')+1,4) PAYMENT_NO_YY
        ,to_char(PH_DOC_DT,'DD-MM-YYYY') PH_DOC_DT, SUPP_NAME,PH_FC_AMT, USER_DESC
				,replace(PH_BANK_ACNT_NO,'.','') PH_BANK_ACNT_NO, BANK_NAME,PH_CURR_CODE
				,nvl(SBC_BANK_NAME,PH_FLEX_03) SBC_BANK_NAME
				,REPLACE (nvl(SBC_BANK_ACNT_NO,PH_FLEX_04), '.', '')     SBC_BANK_ACNT_NO
				,nvl(SBC_BANK_ACNT_NAME,PH_FLEX_02) SBC_BANK_ACNT_NAME
				,USER_FIELD_01
		FROM ($sqlData) where 1=1
		";

	$StsViewDt =  getData($conn,"select count(-1) GET_DATA from ($sqlView)  ");
	//echo "$sqlView</br>";
  if ($PAYMENT_NO_S===""){
?>
<table style="width:100%">
<?php
	if ($StsViewDt!=="0"){
		$filterArea = "0";
		$filterDate = 0;
?>
<?php
	}
?>
</table>
<?php
}
	if ($PAYMENT_NO_S===""){
		$sqlWhere = "";
		if ($FILTER_SCLT_S==="Payment No"){
			$sqlWhere = " AND PAYMENT_NO like '%$FILTER_SCLT_VAL_S%' ";
		}

		if ($FILTER_SCLT_S==="Doc. Date"){
			$sqlWhere = " AND to_char(PH_DOC_DT,'YYYY-MM-DD') = '$FILTER_SCLT_DATE_S' ";
		}

		if ($FILTER_SCLT_S==="Supplier Name"){
			$sqlWhere = " AND SUPP_NAME like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
		}

		if ($FILTER_SCLT_S==="Transaction No"){
			$sqlWhere = " AND payment_no in (select distinct payment_no FROM ( $sqlData) where  TRANS_NO like '%'||upper('$FILTER_SCLT_VAL_S')||'%' )";
		}

		if ($FILTER_SCLT_S==="User Name"){
			$sqlWhere = " AND upper(USER_DESC) like '%'||upper('$FILTER_SCLT_VAL_S')||'%' ";
		}

		if ($FILTER_SCLT_S==="Area User"){
			if ($FILTER_AREA_S!=="ALL") {
				$sqlWhere = " AND upper(USER_FIELD_01) = upper('$FILTER_AREA_S') ";
			}
		}

		if ($usrAreaLgn!=="ALL"){
			$sqlWhere = "$sqlWhere AND upper(USER_FIELD_01) = upper('$usrAreaLgn') ";
		}

		$sqlView = "select rownum REC_NO,d.* from ($sqlView $sqlWhere order by payment_no) d ";

		//echo "$sqlView </br>";

		$StsViewDt =  getData($conn,"select count(-1) GET_DATA from ($sqlView)  ");
		$totRows = $StsViewDt;

		$P_ROWS_DISP = $TOT_ROWS_S;
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

		if ($P_PAGE_NO > $P_LAST_PAGE_NO){
			$P_PAGE_NO = $P_LAST_PAGE_NO;
		}

    $sts_BtnClr = ""; if ($DATE_FROM_S!==""){ $sts_BtnClr = "Y";}
    $isFilterDocDt = ""; if ($FILTER_SCLT_S==="Doc. Date"){ $isFilterDocDt = "Y";}
    $isDocNA = $Doc_N_A_S;
?>
<table style="width:100%" >
	<tr>
    <td <?php echo getStyle($font,"center","70"); ?>>
      Date From <input type="date" name="DATE_FROM" id="DATE_FROM" value="<?php echo $DATE_FROM_S; ?>" onchange="ViewData()">
      Date To <input type="date" name="DATE_TO" id="DATE_TO" value="<?php echo $DATE_TO_S; ?>" onchange="ViewData()">
<?php
  if ($DATE_FROM_S!==""){
?>
  &nbsp&nbspFilter By
  <select name="FILTER_SCLT" id="FILTER_SCLT" onchange="ViewData()">
    <option value="NULL">Null</option>
    <option value="Payment No"
      <?php if ($FILTER_SCLT_S==="Payment No"){echo "selected";} ?>
    >
      Payment No
    </option>
    <option value="Doc. Date"
      <?php if ($FILTER_SCLT_S==="Doc. Date"){ echo "selected"; } ?>
    >
      Doc. Date
    </option>
    <option value="Supplier Name"
      <?php if ($FILTER_SCLT_S==="Supplier Name"){echo "selected";} ?>>Supplier
    </option>
    <option value="Transaction No"
      <?php if ($FILTER_SCLT_S==="Transaction No"){echo "selected";} ?>>Transaction No
    </option>
		<option value="User Name"
      <?php if ($FILTER_SCLT_S==="User Name"){echo "selected";} ?>>User Name
    </option>
<?php		if ($usrAreaLgn==="ALL"){ ?>
				<option value="Area User"
					<?php if ($FILTER_SCLT_S==="Area User"){
							echo "selected";
							$filterArea = "1";
						} ?>>Area User
				</option>
<?php 		} ?>
  </select>
<?php
	//echo "filterArea $filterArea";
  if ($isFilterDocDt==="Y"){
?>
  	<input 	type="DATE" name="FILTER_SCLT_DATE" id="FILTER_SCLT_DATE" value="<?php echo $FILTER_SCLT_DATE_S; ?>" onchange="ViewData()">
<?php
	} else {
		if ($filterArea==="1"){
//User Area
			$sqlUsrArea = "select MPD_NAME DESCRIPT,  MPD_VALUE ID_TYPE from mst_param_Data where MPD_MPDK_KEY = 'USER_AREA' order by mpd_seq_no";
			$rsUsrArea = oci_parse($conn,$sqlUsrArea);
			oci_execute ($rsUsrArea);
?>
		<select name="FILTER_AREA" id="FILTER_AREA" onchange="ViewData()">
			<option value="ALL">ALL</option>
<?php
		while ($rowUsrArea = oci_fetch_array ($rsUsrArea, OCI_BOTH)) {
?>
		<option value="<?php echo $rowUsrArea['DESCRIPT']; ?>"
			<?php if ($FILTER_AREA_S===$rowUsrArea['DESCRIPT']){echo "selected";} ?>
		>
			<?php echo $rowUsrArea['DESCRIPT']; ?>
		</option>
?>
<?php
		}
?>

	</select>

<?php
//End User Area
		} else {
?>
		<input 	type="text" name="FILTER_SCLT_VAL" id="FILTER_SCLT_VAL" value="<?php echo $FILTER_SCLT_VAL_S; ?>" onchange="ViewData()" >
<?php
		}
	}


}
?>
<?php if($sts_BtnClr === "Y") {
?>
    <input type="BUTTON" value="Clear" class="BUTTON btn_clear" onclick="fClear()">
<?php
}?>
		&nbsp
		<input type="checkbox" id="Doc_N_A" name="Doc_N_A" onclick="viewDoc_NA()"
					 <?php
					 	if ($Doc_N_A_S==="Y"){echo "checked";}
					 ?>
		>
		Doc N/A
		&nbsp
		<input type="checkbox" id="MCM_N_A" name="MCM_N_A" onclick="viewDoc_MCM_NA()"
					 <?php
					 	if ($MCM_N_A_S==="Y"){echo "checked";}
					 ?>
		>
		MCM N/A
		</td>
    <td <?php echo getStyle($font,"center"); ?> >

      <b>
      Tot. Rows
      <input  type="text" id="TOT_ROWS" name="TOT_ROWS" style="width:40px;height:20px;" value="<?php echo $TOT_ROWS_S; ?>"
              onchange="ViewData()"
      >
      </b>
			<input type="button" class="BUTTON btn_firstrow" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="PREVREC" value="<" onclick="PrevRecord()" >
			<input type="number" name="P_PAGE_NO" id="P_PAGE_NO" style="width: 55px;"  value="<?php echo $P_PAGE_NO;?>"
					onkeypress="Javascript: if (event.keyCode==13) GoRecord();"
			>
			: <?php echo $P_LAST_PAGE_NO; ?>
			<input type="button" class="BUTTON btn_firstrow" id="NEXTREC" value=">" onclick="NextRecord()" >
			<input type="button" class="BUTTON btn_prevrow" id="LASTREC" value=">>" onclick="LastRecord()" >

    </td>
  </tr>
</table>
<table style="width:100%" border="1">
	<tr>
		<td <?php echo getStyle($font,"center","3"); ?> ><b>No</b></td> <!--1-->
		<td <?php echo getStyle($font,"center","15"); ?> ><b>Payment No</b></td> <!--2-->
		<td <?php echo getStyle($font,"center","7"); ?>><b>Doc. Date</b></td> <!--3-->
		<td <?php echo getStyle($font,"center","33"); ?>><b>Supplier</b></td> <!--4-->
		<td <?php echo getStyle($font,"center","20"); ?>><b>Supplier Bank Information</b></td><!--5-->
		<td <?php echo getStyle($font,"center","12"); ?>><b>Amount</b></td> <!--6-->
		<td <?php echo getStyle($font,"center","20"); ?>><b>Bank Account MGT</b></td> <!--7-->
		<td <?php echo getStyle($font,"center"); ?> ><b>User</b></td> <!--8-->
		<td <?php echo getStyle($font,"center"); ?> ><b>Area</b></td> <!--9-->
		<td <?php echo getStyle($font,"center"); ?> ></td> <!--10-->
		</b>
	</tr>
<?php
	$RecNO = 0;
  $sqlDt = "select * from ($sqlView) where REC_NO between $P_ROW_START and $P_ROW_END";
	//if ($USER_NAME==="1949"){echo $sqlDt; }

	$rsDt = oci_parse($conn,$sqlDt);
	oci_execute ($rsDt);
	$PaymentNo = "";
  $folderDoc = "D:/XAMPP/htdocs/webapps/Doc_Folder";
	while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
		$RecNO++;
    $lstData 	= "ViewDtl('".$rowDt['PAYMENT_NO']."')";

    $PAYMENT_NO_FOLDER="";if(!empty($rowDt['PAYMENT_NO_FOLDER'])){$PAYMENT_NO_FOLDER=$rowDt['PAYMENT_NO_FOLDER'];}
    $PAYMENT_NO_YY="";if(!empty($rowDt['PAYMENT_NO_YY'])){$PAYMENT_NO_YY=$rowDt['PAYMENT_NO_YY'];}

    $PAYMENT_NO="";if(!empty($rowDt['PAYMENT_NO'])){$PAYMENT_NO=$rowDt['PAYMENT_NO'];}
    $PH_DOC_DT="";if(!empty($rowDt['PH_DOC_DT'])){$PH_DOC_DT=$rowDt['PH_DOC_DT'];}
    $SUPP_NAME="";if(!empty($rowDt['SUPP_NAME'])){$SUPP_NAME=$rowDt['SUPP_NAME'];}

    $SBC_BANK_NAME="";if(!empty($rowDt['SBC_BANK_NAME'])){$SBC_BANK_NAME=$rowDt['SBC_BANK_NAME'];}
    $SBC_BANK_ACNT_NO="";if(!empty($rowDt['SBC_BANK_ACNT_NO'])){$SBC_BANK_ACNT_NO=$rowDt['SBC_BANK_ACNT_NO'];}
    $SBC_BANK_ACNT_NAME="";if(!empty($rowDt['SBC_BANK_ACNT_NAME'])){$SBC_BANK_ACNT_NAME=$rowDt['SBC_BANK_ACNT_NAME'];}
    $PH_CURR_CODE="";if(!empty($rowDt['PH_CURR_CODE'])){$PH_CURR_CODE=$rowDt['PH_CURR_CODE'];}
    $PH_FC_AMT="";if(!empty($rowDt['PH_FC_AMT'])){$PH_FC_AMT=$rowDt['PH_FC_AMT'];}
    $PH_BANK_ACNT_NO="";if(!empty($rowDt['PH_BANK_ACNT_NO'])){$PH_BANK_ACNT_NO=$rowDt['PH_BANK_ACNT_NO'];}
    $USER_DESC="";if(!empty($rowDt['USER_DESC'])){$USER_DESC=$rowDt['USER_DESC'];}
    $USER_FIELD_01="";if(!empty($rowDt['USER_FIELD_01'])){$USER_FIELD_01=$rowDt['USER_FIELD_01'];}

    $FileDoc = $folderDoc."/".$PAYMENT_NO_FOLDER."/".$PAYMENT_NO_YY."/".$PAYMENT_NO.".pdf";
    $FileDoc_Exist = "N"; if (file_exists($FileDoc)){ $FileDoc_Exist = "Y";}

    $stsViewGrid = "Y"; if($isDocNA==="Y" && $FileDoc_Exist==="Y"){ $stsViewGrid = "N"; }

		$FileDoc_MCM = $folderDoc."/MCM/".$PAYMENT_NO_YY."/MCM-".$PAYMENT_NO.".pdf"; $FileDoc_Exist_MCM = "N";
		$FileDoc_Exist_MCM = "N"; if (file_exists($FileDoc_MCM)){ $FileDoc_Exist_MCM = "Y";}

		//echo "stsViewGrid $stsViewGrid : FileDoc_Exist_MCM $FileDoc_Exist_MCM : MCM_N_A_S $MCM_N_A_S<br>";
		if ($stsViewGrid === "Y") {
			if ($FileDoc_Exist_MCM==="Y" && $MCM_N_A_S === "Y"){
				$stsViewGrid = "N";
			}
		}

    if ($stsViewGrid === "Y"){
?>
	<tr>
		<td <?php echo getStyle($font,"right"); ?> > <?php echo $rowDt['REC_NO'];?> </td>  <!--1-->
		<td <?php echo getStyle($font,"left"); ?>>
      <?php
      if ($FileDoc_Exist==="N"){
        echo $PAYMENT_NO;
      } else {
        $FilePdf = "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$PAYMENT_NO_FOLDER."/".$PAYMENT_NO_YY."/".$PAYMENT_NO.".pdf";
        $viewFile = "ViewFile('$FilePdf')";
      ?>
        <button onclick="<?php echo $viewFile; ?>"><?php echo $PAYMENT_NO; ?></button>
      <?php
			}

      if ($FileDoc_Exist_MCM === "Y") {
        $FilePdf_MCM = "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/MCM/".$PAYMENT_NO_YY."/MCM-".$PAYMENT_NO.".pdf";
        $viewFile_MCM = "ViewFile('$FilePdf_MCM')";
      ?>
        <br><button onclick="<?php echo $viewFile_MCM; ?>"><?php echo "MCM-".$PAYMENT_NO; ?></button>
      <?php
      }
      ?>
    </td> <!--2-->
		<td <?php echo getStyle($font,"center"); ?> > <?php echo $PH_DOC_DT; ?> </td> <!--3-->
		<td <?php echo getStyle($font,"left"); ?> >	<?php	echo $SUPP_NAME; ?></td> <!--4-->
		<td <?php echo getStyle($font,"left"); ?> >
			<?php
				if ($SBC_BANK_NAME!==""){ echo $SBC_BANK_NAME."</br>"; }
				if ($SBC_BANK_ACNT_NO!==""){ echo $SBC_BANK_ACNT_NO."</br>"; }
				if ($SBC_BANK_ACNT_NAME!==""){ echo $SBC_BANK_ACNT_NAME."</br>"; }
			?>
		</td> <!--5-->
		<td <?php echo getStyle($font,"right"); ?> > <?php echo $PH_CURR_CODE." ".number_format($PH_FC_AMT); ?> </td> <!--6-->
		<td <?php echo getStyle($font,"center"); ?> > <?php echo $PH_BANK_ACNT_NO; ?> </td> <!--7-->
		<td <?php echo getStyle($font,"left"); ?> > <?php echo $USER_DESC ?> </td> <!--8-->
		<td <?php echo getStyle($font,"left"); ?> > <?php echo $USER_FIELD_01; ?></td>  <!--9-->
		<td <?php echo getStyle($font,"left"); ?> >
			<input 	type="BUTTON" name="BTN_DTL[]" id="BTN_DTL[]" value="Detail" class="BUTTON btn_view"
					onclick="<?php echo $lstData; ?>">
		</td> <!--10-->
	</tr>
<?php
  }
	}
} else {

	include($FORM_NAME."_LIST_DTL.php");

}
?>

<!-- DEFINE LAYOUT -->
