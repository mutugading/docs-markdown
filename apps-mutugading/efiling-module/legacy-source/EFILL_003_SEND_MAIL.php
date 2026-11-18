<?php 
	include "mailSales.php";
	$senderMail = "mgtsales@mutugading.com";
	$subjectMail = "Mutu Gading Sales";
	$mail->AddAddress("danang.wahyu.wibowo@gmail.com","Nama Penerima");  //email tujuan email
	$mail->MsgHTML("Tes Kirim Email Smtp Authentication IDwebhost");
	//if($mail->Send()) echo "Pesan Berhasil Di Kirim";
	//else echo "Email Gagal Di Kirim";
	if ($ESMS_FILE_ATTACH_S === ""){
		$ESMS_FILE_ATTACH_S = str_replace("OPEN MAIL ","", $STSPRS);
	}

?>
<table style="width:100%" align="center" border="1">
	<tr>
		<td colspan="2" align="center">
			<?php echo "Attachment file $ESMS_FILE_ATTACH_S"; ?>
		</td>
	</tr>
	<tr>
		<td style="width:20%" align="right">
			From
		</td>
		<td align="left">
			<?php
				echo $senderMail;
			?>
		</td>
	</tr>
	<tr>
		<td style="width:20%" align="right">
			To
		</td>
		<td align="left">
			<input 	type="email" id="ESMS_TO" name="ESMS_TO"
					value="<?php echo $ESMS_TO_S; ?>"
					size="75" multiple
			>
		</td>
	</tr>
	<tr>
		<td style="width:20%" align="right">
			Cc
		</td>
		<td align="left">
			<input 	type="email" id="ESMS_CC" name="ESMS_CC"
					value="<?php echo $ESMS_CC_S; ?>"
					size="75" multiple
			>
		</td>
	</tr>
	<tr>
		<td style="width:20%" align="right">
			Message
		</td>
		<td align="left">
			<textarea id="ESMS_MESSAGES" name="ESMS_MESSAGES" rows="15" cols="76">
			<?php
				echo $ESMS_MESSAGES_S;
			?>
			</textarea>
		</td>
	</tr>

	<tr>
		<td colspan="2" align="center">
			<input type="button" value="SEND" class="BUTTON btn_process" onclick="Process('SEND MAIL')" >
			<input type="button" value="CLEAR" class="BUTTON btn_cancel" onclick="Process('CLEAR')" >
		</td>
	</tr>
</table>
