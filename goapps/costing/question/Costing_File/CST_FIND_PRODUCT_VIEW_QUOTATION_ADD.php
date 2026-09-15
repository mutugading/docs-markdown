<?php
  $sqlList = "
              select rownum REC_NO,SQT_SYS_ID,d.* from (
								select b.*,SQT_SYS_ID from mgtapps.SLS_QUOTATION_TMP a,mgtapps.cst_yarn_left b
								where cyl_sys_id = sqt_cyl_sys_id and SQT_CREATED_BY = '$USER_NAME'
                order by SQT_CREATED_TIMESTAMP,cyl_left_no
              ) d
              ";

  $totDt = getData($conn,"select count(-1) GET_DATA from ($sqlList)");
?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:100%">
	<tr >
		<td  align="center">
		    <input 	type="button" style="font-size:13px;" value="Add" onclick="AddQuotation('ADD_QUOTATION')"
				>
<?php
	if ($totDt!=="0"){
?>
				<input 	type="button" value="Remove"  style="font-size:13px;"
								onclick="RemoveQuotation('REMOVE_QUOTATION')"
				>
				<input 	type="button" value="Generate"  style="font-size:13px;"
								onclick="GenerateQuotation('GENERATE_QUOTATION')"
				>
<?php
	}
?>
		</td>
	</tr>
</table>
</div>
<div class="main" style="overflow-x:auto;" align="center">
<table style="auto;width:90%;" border="1" >
  <tr>
    <td <?php echo getStyle($font,"center","5"); echo $bgClrLbl; ?> >No</td><!--1-->
    <td <?php echo getStyle($font,"center","10"); echo $bgClrLbl; ?> >
      <input type="checkbox"  id="CK_ALL" name="CK_ALL"  value="Y" onclick="CkAllRemove()"
        <?php if ($CK_ALL_S==="Y") {echo "checked";} ?>
      >
      Product Left No
    </td><!--2-->
    <td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Name</td><!--3-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Shade Code</td><!--4-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Shade Name</td><!--5-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Item Code</td><!--6-->
  </tr>
<?php
  $rsList = oci_parse($conn,$sqlList);
  oci_execute ($rsList);
  while ($rowList = oci_fetch_array ($rsList, OCI_BOTH)) {
?>
  <tr>
    <td <?php echo getStyle($font,"center");  ?>><?php echo $rowList['REC_NO']; ?></td><!--1-->
		<td <?php echo getStyle($font,"left");  ?>>
			<input type="checkbox"  id="REMOVE[]" name="REMOVE[]"  value="<?php echo $rowList['SQT_SYS_ID']; ?>"
        <?php if ($CK_ALL_S==="Y") {echo "checked";} ?>
      ><?php echo $rowList['CYL_LEFT_NO']; ?>
		</td><!--2-->
		<td <?php echo getStyle($font,"left");  ?>><?php echo $rowList['CYL_NAME']; ?> </td><!--3-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_SHADE_CODE']; ?></td><!--4-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_SHADE_NAME']; ?></td><!--5-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_ITEM_CODE']; ?></td><!--6-->
	</tr>
<?php
	}
?>
</table>
</div>
