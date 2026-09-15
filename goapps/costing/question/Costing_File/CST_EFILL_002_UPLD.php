<table style="width:100%" border="1">
	<tr>
		<td style="text-align: left;" >
			<b>UPLOAD FILE</b>
		</td>
	</tr>
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
