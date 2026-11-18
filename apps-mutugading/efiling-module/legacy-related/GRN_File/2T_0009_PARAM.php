<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
<table border="0" align="center">
  <tr>
    <td <?php echo getStyle(12,"center"); ?> >
      <b>
        Date From
        <input  type="DATE" id="DATE_FROM" name="DATE_FROM" style="width:120px;height:20px;" onchange="ViewList()"
                value="<?php echo $DATE_FROM_S; ?>"
        >
      </b>
      &nbsp&nbsp&nbsp
      <b>
        Date To
        <input  type="DATE" id="DATE_TO" name="DATE_TO" style="width:120px;height:20px;" onchange="ViewList()"
                value="<?php echo $DATE_TO_S; ?>"
        >
      </b>
    </td>
  </tr>
<?php
  if ($DATE_FROM_S!=="" && $DATE_TO_S!==""){
    $sqlSLctGrnDt = "select distinct GH_DT,to_char(GH_DT,'DD-MM-YYYY') GH_DT_SLCT from ($sqlList) order by GH_DT";
    //echo $sqlSLctGrnDt;
?>
<tr>
  <td <?php echo getStyle(12,"center"); ?> >
    <b>
      <label for="GH_DT_SLCT">GRN Date </label>
      <select name="GH_DT_SLCT" id="GH_DT_SLCT" onchange="ViewList('')">
        <option value="ALL">ALL</option>
<?php
      $rsSlct = oci_parse($conn,$sqlSLctGrnDt);
      oci_execute ($rsSlct);
      while ($rowSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
?>
      <option value="<?php echo $rowSlct['GH_DT_SLCT']; ?>"
        <?php if ($GH_DT_SLCT_S===$rowSlct['GH_DT_SLCT']){echo "selected";} ?>
      >
        <?php echo $rowSlct['GH_DT_SLCT'];?>
      </option>
<?php
      }
?>
      </select>
    </b>
    &nbsp&nbsp
    <b>
      GRN No
      <input type="TEXT" id="GS_GRN_NO_SLCT" name="GS_GRN_NO_SLCT" style="width:200px;height:20px;"
        value="<?php echo $GS_GRN_NO_SLCT_S; ?>" onchange="ViewList('')"
      >
      <button onclick="ViewList()">Find</button>
    </b>
    <b>
      <label for="STATUS_SLCT">Status </label>
      <select name="STATUS_SLCT" id="STATUS_SLCT" onchange="ViewList('')">
        <option value="ALL">ALL</option>
        <option value="Ready for Merging"
          <?php if ($STATUS_SLCT_S==="Ready for Merging"){echo "selected";} ?>
        >Ready for Merging</option>
        <option value="Not Ready for Merging"
          <?php if ($STATUS_SLCT_S==="Not Ready for Merging"){echo "selected";} ?>
        >Not Ready for Merging</option>
      </select>
    </b>
  </td>
</tr>
<?php
  }
?>
</table>
<div>
