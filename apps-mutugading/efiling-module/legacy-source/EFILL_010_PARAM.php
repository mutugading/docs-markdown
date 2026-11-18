<table style="width:100%">
  <tr >
    <td style="text-align: center;" >
      Date From <input type="date" name="DATE_FROM" id="DATE_FROM" value="<?php echo $DATE_FROM_S; ?>">
      Date To <input type="date" name="DATE_TO_" id="DATE_TO" value="<?php echo $DATE_TO_S; ?>">
       <input type="BUTTON" value="View Data" class="BUTTON btn_process" onclick="Process('VIEW_DATA')">
<?php		 if ($DATE_FROM_S!=="" || $DATE_TO_S!=="" ){?>
        <input type="BUTTON" value="Clear" class="BUTTON btn_clear" onclick="Process('CLEAR')">
<?php 		 } ?>
    </td>
  </tr>
<?php
  if ($StsViewDt!=="0"){
    $filterDate = 0;
?>
  <tr >
    <td style="text-align: center;" >
      Filter By
      <select name="FILTER_SCLT" id="FILTER_SCLT" onchange="Process('')">
        <option value="NULL">Null</option>
        <option value="Invoice No" <?php if ($FILTER_SCLT_S==="Invoice No"){echo "selected";} ?> >
          Invoice No
        </option>
        <option value="Date" <?php if ($FILTER_SCLT_S==="Date"){ echo "selected"; $filterDate = 1;} ?> >
          Date
        </option>
        <option value="Customer" <?php if ($FILTER_SCLT_S==="Customer"){echo "selected";} ?> >
          Customer
        </option>
        <option value="Term Code" <?php if ($FILTER_SCLT_S==="Term Code"){echo "selected";} ?> >
          Term Code
        </option>
        <option value="FP Sales" <?php if ($FILTER_SCLT_S==="FP Sales"){ echo "selected"; } ?> >
          FP Sales
        </option>
        <option value="DN No" <?php if ($FILTER_SCLT_S==="DN No"){echo "selected";} ?>>
          DN No
        </option>
        <option value="Kwitansi No" <?php if ($FILTER_SCLT_S==="Kwitansi No"){echo "selected";} ?>>
          Kwitansi No
        </option>
        <option value="NCA" <?php if ($FILTER_SCLT_S==="NCA"){echo "selected";} ?>>
          NCA
        </option>
      </select>
<?php 	if ($filterDate===0){?>
      <input 	type="text" name="FILTER_SCLT_VAL" id="FILTER_SCLT_VAL" value="<?php echo $FILTER_SCLT_VAL_S; ?>" >
<?php 	} else { ?>
      <input 	type="DATE" name="FILTER_SCLT_DATE" id="FILTER_SCLT_DATE" value="<?php echo $FILTER_SCLT_DATE_S; ?>" >
<?php  	}
  ?>
      <input type="BUTTON"  value="FIND" class="BUTTON btn_delete" onclick="Process('FIND')">
    </td>
  </tr>
<?php
  }
?>
</table>
