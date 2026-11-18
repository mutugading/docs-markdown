<table style="width:95%">
  <tr align="center">
    <td>
      Date From
      <input type="date" id="Date_From" name="Date_From" value="<?php echo $Date_From_S; ?>"  onchange="Process('')"
      >
      Date End
      <input type="date" id="Date_End" name="Date_End" value="<?php echo $Date_End_S; ?>"  onchange="Process('')"  >
    </td>
  </tr>
<?php
if ($isUserAdmin==="Y"){
  $sqlSlct = "SELECT 	distinct USER_ID, USER_NAME||'-'||USER_ID USER_NAME
                      ,max(ELA_CREATED_TIMESTAMP) ELA_CREATED_TIMESTAMP
              FROM  mgtapps.efill_log_access d,mgtapps.mst_users u
              where ela_created_by is not null and ELA_CREATED_BY = USER_ID
              and trunc(ELA_CREATED_TIMESTAMP)
              between to_date('$Date_From_S','YYYY-MM-DD') and to_date('$Date_End_S','YYYY-MM-DD')
              group by user_id, user_name
              ORDER BY ELA_CREATED_TIMESTAMP DESC";
  //echo "sqlSlct $sqlSlct";

?>
  <tr>
    <td align="center">
    Process By
    <select id="USER_ID" name="USER_ID" onchange="Process('')">
      <option value="ALL">ALL</option>
  <?php
      $rsSlct = oci_parse($conn,$sqlSlct);
      oci_execute ($rsSlct);
      while ($rowSlct = oci_fetch_array ($rsSlct, OCI_BOTH)) {
  ?>
      <option value="<?php echo $rowSlct['USER_ID']; ?>"
          <?php
            if($rowSlct['USER_ID']===$USER_ID_S){echo "selected";}
          ?>
      >
        <?php echo $rowSlct['USER_NAME']; ?>
      </option>
  <?php
      }
  ?>
    </select>
    <td>
  </tr>
<?php
}
?>
</table>
