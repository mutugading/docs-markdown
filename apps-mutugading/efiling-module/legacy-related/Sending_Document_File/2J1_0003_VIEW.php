<?php
//ORDER BY cust_name, td_doc_dt ASC
//echo "mainSql $mainSql";
?>
<table  style="width:60%" border="1" align="center">
  <tr>
    <td style="width:3%;text-align: center;" >
      No
    </td>
    <td style="text-align: center;">
      Customer Code
    </td>
    <td style="text-align: center;">
      Customer Name
    </td>
    <td style="text-align: center;">
      Send Status
    </td>
    <td >
    </td>
  </tr>
<?php
$sqlViewMain = "select rownum NO_REC,d.* from (select distinct CUST_CODE,CUST_NAME from ( $mainSql ) order by CUST_NAME) d ";

//if ($USER_NAME === "1949") {echo "$sqlViewMain</br>";}

$rsDt = oci_parse($conn,$sqlViewMain);
oci_execute ($rsDt);
while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
  $btnDtl   = "Process('DTL $P_DATE_S|".$rowDt['CUST_CODE']."')";

  $sqlGetData = 	"select 'Y' GET_DATA
                   from MGTAPPS.LOG_SENDING_MAIL_CUST
                   where LSMC_CUST_CODE_REFF = '".$rowDt['CUST_CODE']."'
                   and LSMC_DATE_PROCESS_REFF = '$P_DATE_S' and rownum = 1";
  //echo "$sqlGetData </br>";
  $sendSts = getData($conn,$sqlGetData);
?>
  <tr>
    <td style="width:10%;text-align: right;" >
      <?php echo $rowDt['NO_REC']; ?>
    </td>
    <td >
      <?php echo $rowDt['CUST_CODE']; ?>
    </td>
    <td >
      <?php echo $rowDt['CUST_NAME']; ?>
    </td>
    <td align="center">
      <?php echo $sendSts; ?>
    </td>
    <td align="center">
      <input 	type="button" value="Detail" class="button btn_add"
              onclick="<?php echo $btnDtl; ?>"
      >
    </td>
  </tr>
<?php
} ?>
</table>
