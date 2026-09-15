<div class="main" style="overflow-x:auto;" align="center">
<?php
?>
<table style="width:60%" align="center">
	<tr>
		<td align="center" >
			Month &nbsp&nbsp
			<select name="PERIOD_MONTH" id="PERIOD_MONTH" >
			<?php
			for ($x = 1; $x <= 12; $x++) {
				$valDt = ""; $descDt = "";
				if ($x <10){
					$valDt = "0$x";
				}

				if ($x===1){$descDt = "JAN";} if ($x===2){$descDt = "FEB";} if ($x===3){$descDt = "MAR";}
				if ($x===4){$descDt = "APR";} if ($x===5){$descDt = "MAY";} if ($x===6){$descDt = "JUN";}
				if ($x===7){$descDt = "JUL";} if ($x===8){$descDt = "AUG";} if ($x===9){$descDt = "SEP";}
				if ($x===10){$descDt = "OCT";} if ($x===11){$descDt = "NOV";} if ($x===12){$descDt = "DEC";}

			?>
				<option value="<?php echo $valDt; ?>">
					<b><font color='red' ><?php echo $descDt; ?></font></b>
				</option>
			<?php
			}
			?>
			</select>
			Year &nbsp&nbsp
			<input type="TEXT" id="PERIOD_YEAR" name="PERIOD_YEAR" style="width:75px;height:20px;"
				value="<?php echo $PERIOD_YEAR_S; ?>"
			>

			<br>
			<button value="PROCESS" onclick="Process('PROCESS')">PROCESS</button>
		</td>
	</tr>
</table>
</div>
