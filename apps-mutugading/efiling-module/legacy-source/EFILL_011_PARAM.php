<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:100%" border="0">
    <tr >
<?php
  $sqlSlct = getSqlDoc($conn);
  // if ($USER_NAME==="1949"){echo "DOC_TYPE_S sqlSlct $sqlSlct</br>";}  
?>
      <td <?php echo getStyle(12,"center","40"); ?> >
        <b>Dcoument Type : </b>
        <select name="DOCUMENT_TYPE" id="DOCUMENT_TYPE" onchange="showData()">
          <option value="Null">Null</option>
<?php
      $rsSlct = oci_parse($conn,$sqlSlct);
      oci_execute ($rsSlct);
      while ($rowRsSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
        $DOC_TYPE=""; if(!empty($rowRsSlct['DOC_TYPE'])){$DOC_TYPE=$rowRsSlct['DOC_TYPE'];}//1
?>
        	<option value="<?php echo $DOC_TYPE; ?>" <?php if($DOC_TYPE===$DOCUMENT_TYPE_S){echo "selected";} ?> ><?php echo $DOC_TYPE; ?></option>
<?php
      }
?>
        </select>
        &nbsp<button type="button" data-toggle="modal" data-target="#myModal" onclick="showLov_Folder()">...</button>
        &nbsp<button type="button" onclick="showData()" >&nbspRefresh&nbsp</button>
      </td>
    </tr>
  </table>
</div>
