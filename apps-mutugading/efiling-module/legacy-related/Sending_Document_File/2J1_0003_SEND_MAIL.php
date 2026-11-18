<?php
	echo "STSPRS : $STSPRS </br>";
	$splitPos     = strpos($STSPRS,"|" )+1;
	$custProcess  = substr($STSPRS,$splitPos);
	$dtProcess    = $P_DATE_S;
	echo "custProcess $custProcess | dtProcess	$dtProcess </br>";
	include("../EMAIL_SALES_SETUP.php");
	getSetupEmail($conn,$E_Host,$E_Username,$E_UserPassword);
	//echo "E_Host $E_Host : E_Username $E_Username : E_UserPassword $E_UserPassword : mainSql $mainSql";

	$sqlDtMail = "$mainSql and TD_SUB_ACNT_CODE = ";
	//echo "E_Host $E_Host : E_Username $E_Username : E_UserPassword $E_UserPassword"; die();

	$P_CUST_CODE = $custProcess;
	include("../ORION_CUST_DT.php");
	$ESMS_MESSAGES_S = "";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p>Kepada Yth	:	$CUST_NAME_DT <br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."Attn 	:	Bagian Keuangan<br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<br /><br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."Dengan hormat,<br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."Bersama ini kami kirimkan dokumen Faktur Pajak dari transaksi penerimaan uang dengan rincian sebagai berikut :</p>";

	//body messages
	include "../PHPMailer/class.phpmailer.php";
	$mail = new PHPMailer;
	$mail->IsSMTP();
	$mail->SMTPSecure = 'ssl';
	$mail->Host = $E_Host; //host masing2 provider email
	$mail->SMTPDebug = 2;
	$mail->Port = 465;
	$mail->SMTPAuth = true;
	$mail->Username = $E_Username; //user email
	$mail->Password = $E_UserPassword; //password email


	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<table border='0'>";
	$bgcolor = "bgcolor='#EFF2FB'";
	$ESMS_MESSAGES_S =
										$ESMS_MESSAGES_S
										."<tr style='width:70%'>
											<td ".$bgcolor." align='center' style='width:10%'>No</td>
											<td ".$bgcolor." align='center' >No Faktur Pajak</td>
											<td ".$bgcolor." align='center' >Date</td>
											<td ".$bgcolor." align='center' >Amount</td>
											<td ".$bgcolor." align='center' >Reff No</td>
										</tr>";
	$sqlViewData = "select rownum REC_NO,d.* from ($mainSql and TD_SUB_ACNT_CODE ='$custProcess') d";
	//echo "$sqlViewData</br>";
	//die();
	$rsDt = oci_parse($conn,$sqlViewData);
	oci_execute ($rsDt);
	//$FILE_ATTACH_S = "D:/XAMPP/htdocs/webapps/Doc_Folder/KWITANSI_BREAKDOWN/$KWI_YY/".$dtl_kwitansi_no."_SIGNED.pdf";
	$FILE_ATTACH_LINK = "D:/XAMPP/htdocs/webapps/Doc_Folder/";
	while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
		$bgcolor = "bgcolor='#E6E6E6'";

		$ESMS_MESSAGES_S =
											$ESMS_MESSAGES_S
											."<tr style='width:60%'>
												<td align='right' $bgcolor>".$rowDt['REC_NO']."</td>
												<td align='left' $bgcolor>".$rowDt['FP_NO']."</td>
												<td align='center' $bgcolor>".$rowDt['FP_DT']."</td>
												<td align='right' $bgcolor >".number_format($rowDt['ADVANCE_AMT'])."</td>
												<td align='center' $bgcolor  >".$rowDt['ADVANCE_NO']."</td>
											</tr>";

		$FILE_ATTACH_S = $FILE_ATTACH_LINK.$rowDt['FP_FOLDER']."/".$rowDt['FOLDER_YY']."/".$rowDt['FP_NO'].".pdf";
		//echo $FILE_ATTACH_S;die();
   	$mail->AddAttachment($FILE_ATTACH_S);      // attachment

	}

	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."</table>";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p>Atas perhatian dan kerja samanya kami ucapkan terima kasih.<br />";

	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p></p>";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p></p>";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p></p>";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."<p>Hormat Kami,<br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."PT. Mutu Gading Tekstil<br />";
	$ESMS_MESSAGES_S = $ESMS_MESSAGES_S."Bagian Keuangan</p>";

	$body             = $ESMS_MESSAGES_S; //pdf file from any folder
	$mail->SetFrom($E_Username, 'Sales Mutu Gading');

	$sqlGetData = 	"select 'Y' GET_DATA from mgtapps.MST_EMAIL_CUST where mec_cust_code = '$P_CUST_CODE' and MEC_ACTIVE = 'Y' and rownum = 1";
	echo "$sqlGetData </br>";
	//die();
	$stsEmailCust = getData($conn,$sqlGetData);
	$stsEmailCust = "Y";

	if ($stsEmailCust === ""){
		echo "<script>alert('No Email Address Active, Please contact Admin !!!');</script>";
	} else {

		/*$sqlSendTo = 	"select *
						from MST_EMAIL_CUST
						where mec_cust_code = '$P_CUST_CODE'
						and MEC_ACTIVE = 'Y'
						order by mec_seq_no";
		$rsSendTo = oci_parse($conn,$sqlSendTo);
		oci_execute ($rsSendTo);
		$stsEmailCust = "";
		while ($rowDt = oci_fetch_array ($rsSendTo, OCI_BOTH)) {
			$mail->AddAddress($rowDt['MEC_MAIL']);
			echo $rowDt['MEC_MAIL']."</br>";
		}*/

		//mgtfin@mutugading.com
		//mgtfin@mutugading.com

		/*$sqlSendTo = 	"SELECT AMGMT_MAIL_TYPE, AMGMT_MAIL_ADDRS
  						 FROM mgtapps.alrt_m_grp_mail a, mgtapps.alrt_m_grp_mail_to b
 						 WHERE amgm_sys_id = amgmt_amgm_sys_id
   						 AND amgm_sys_id = '202208016'
   						 AND amgm_is_active = 1 and AMGMT_IS_ACTIVE=1";
		$rsSendTo = oci_parse($conn,$sqlSendTo);
		oci_execute ($rsSendTo);
		$stsEmailCust = "";
		while ($rowDt = oci_fetch_array ($rsSendTo, OCI_BOTH)) {
			if ($rowDt['AMGMT_MAIL_TYPE'] = 'TO'){
				$mail->AddAddress($rowDt['AMGMT_MAIL_ADDRS']);
			} else if ($rowDt['AMGMT_MAIL_TYPE'] = 'TO'){
				$mail->AddReplyTo($rowDt['AMGMT_MAIL_ADDRS']);
			}
		}*/

		$SqlMailTo = "SELECT * FROM mgtapps.alrt_m_grp_mail_to WHERE amgmt_amgm_sys_id = '202208016'
								AND AMGMT_IS_ACTIVE = 1 ";
		$rsMailTo = oci_parse($conn,$SqlMailTo);
		oci_execute ($rsMailTo);
		while ($rowMailTo = oci_fetch_array ($rsMailTo, OCI_BOTH)) {
			// if ($dtEmailCust===""){
			// 	$dtEmailCust = $rowCustDt['AMGMT_MAIL_ADDRS'];
			// } else {
			// 	$dtEmailCust = "$dtEmailCust".",".$rowCustDt['AMGMT_MAIL_ADDRS'];
			// }

			if ($rowMailTo['AMGMT_MAIL_TYPE'] = 'TO'){
				$mail->AddAddress($rowMailTo['AMGMT_MAIL_ADDRS']);
			} else if ($rowMailTo['AMGMT_MAIL_TYPE'] = 'CC'){
				$mail->addCC($rowMailTo['AMGMT_MAIL_ADDRS']);
			}
		}

		$mail->Subject    = "Finance PT. Mutu Gading Tekstil (Dokumen Faktur Pajak)";
		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
		$mail->MsgHTML($body);

		$LSMC_DETAIL_PROCESS = "Faktur Pajak Advance to $P_CUST_CODE Advance Date $dtProcess";
		//die();

		//$mail->AddAttachment("pdf/ex2.gif"); // attachment
		include("../INS_LOG_SENDING_MAIL.php");
		if(!$mail->Send())
	   	{
				$LSMC_ERROR_MESSAGE = 	"Mailer Error: ".$mail->ErrorInfo."; !!!";
				insLogSendingMail(
					$conn
          ,'123',$USER_NAME,'FAKTUR PAJAK ADVANCE'
          ,2,$LSMC_DETAIL_PROCESS,$LSMC_ERROR_MESSAGE
					,$P_CUST_CODE,$dtProcess
				 );
	    	echo "<script>alert('Mailer Error: ".$mail->ErrorInfo."; !!!');</script>";
	   	} else   {
				insLogSendingMail(
					$conn
          ,'123',$USER_NAME,'FAKTUR PAJAK ADVANCE'
          ,1,$LSMC_DETAIL_PROCESS,''
					,$P_CUST_CODE,$dtProcess
				  );
	   		echo "<script>alert('Message sent !!!');</script>";
	 	}
 	}
 	$STSPRS = "DTL ".$P_DATE_S."|".$P_CUST_CODE;
	echo "$STSPRS</br>";
	echo "<form method='post' id='$FORM_NAME' name='$FORM_NAME' action='$FORM_NAME' >";
	echo "<input type='hidden' id='USER_NAME' name='USER_NAME' value='$USER_NAME'>";
	echo "<input type='hidden' id='STSPRS' name='STSPRS' value='$STSPRS'>";
	echo "<input type='hidden' name='P_MENU_ID' id='P_MENU_ID' value='$P_MENU_ID'>";
	echo "<input type='hidden' name='P_DATE_S' id='P_DATE_S' value='$P_DATE_S'>";
	echo "</form>";
	echo "<script type='text/javascript'>";
	echo "document.getElementById('$FORM_NAME').submit();";
	echo "</script>";
?>
