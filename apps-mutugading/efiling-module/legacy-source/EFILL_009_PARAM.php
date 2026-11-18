<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:100%" border="0">
    <tr >
      <td <?php echo getStyle(12,"center"); ?> colspan="2" onchange="showData()">
        Date From <input type="date" name="DATE_FROM" id="DATE_FROM" value="<?php echo $DATE_FROM_S; ?>">
        Date To <input type="date" name="DATE_TO_" id="DATE_TO" value="<?php echo $DATE_TO_S; ?>">
      </td>
    </tr >
    <tr >
      <td <?php echo getStyle(12,"center","40"); ?> >
        <b>Filter By : </b>
        <select name="FILTER_BY" id="FILTER_BY" onchange="showData()">
          <option value="Null">Null</option>
        	<option value="Trx No" <?php if($FILTER_BY_S==="Trx No"){echo "selected";} ?> >Trx No</option>
          <option value="Date (DD-MM-YYYY)" <?php if($FILTER_BY_S==="Date (DD-MM-YYYY)"){echo "selected";} ?> >Date (DD-MM-YYYY)</option>
          <option value="Supplier" <?php if($FILTER_BY_S==="Supplier"){echo "selected";} ?> >Supplier</option>
          <option value="Bill No" <?php if($FILTER_BY_S==="Bill No"){echo "selected";} ?> >Bill No</option>
          <option value="Bill Date (DD-MM-YYYY)" <?php if($FILTER_BY_S==="Bill Date (DD-MM-YYYY)"){echo "selected";} ?> >Bill Date (DD-MM-YYYY)</option>
          <option value="LDN No" <?php if($FILTER_BY_S==="LDN No"){echo "selected";} ?> >LDN No</option>
        </select>
        &nbsp&nbsp

        <input type="TEXT" name="FILTER_VAL" id="FILTER_VAL" value="<?php echo $FILTER_VAL_S; ?>"
               onchange="showData()"
        >
      </td>
    </tr>
  </table>
</div>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:100%" border="0">
    <tr>
      <td <?php echo getStyle(12,"left"); ?>>
        <b>TOT Rows</b>
        <input type="number" name="TOT_ROWS" id="TOT_ROWS" style="width: 55px;"  value="<?php echo $TOT_ROWS_S;?>" onchange="showData()">
        &nbsp
        <input type="checkbox" id="VIEW_SQL" name="VIEW_SQL" onclick="viewSql()">&nbspView Sql
      </td>
      <td <?php echo getStyle(12,"right"); ?>>
        <b>
        <input type="button" class="buttonPaging" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
        <input type="button" class="buttonPaging" id="PREVREC" value="<" onclick="PrevRecord()" >
        <input type="number"  name="PAGE_NO" id="PAGE_NO" style="width:50px;text-align:center;"  value="<?php echo $P_PAGE_NO;?>"
            onkeypress="Javascript: if (event.keyCode==13) GoRecord(this.value);"
        >
        :
        <input type="number"  name="P_LAST_PAGE_NO" id="P_LAST_PAGE_NO" style="width:50px;text-align:center;" value="<?php echo $P_LAST_PAGE_NO;?>" >
        <input type="button" class="buttonPaging" id="NEXTREC" value=">" onclick="NextRecord()" >
        <input type="button" class="buttonPaging" id="LASTREC" value=">>" onclick="LastRecord()" >
        </b>
      </td>
    </tr>
  </table>
</div>
