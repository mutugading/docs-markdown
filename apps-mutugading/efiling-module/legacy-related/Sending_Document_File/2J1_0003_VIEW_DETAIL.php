<table  style="width:100%" align="center">
  <tr>
  <?php
    //if($USER_NAME==="1949")	{echo "STSPRS : $STSPRS </br>"; }
    $stsBtnSend   = "Y"; $stsAdvDoc   = "Y"; $stsFpDoc   = "Y";
    $splitPos     = strpos($STSPRS,"|" )+1;
    $dtProcess    = $P_DATE_S;
    $custProcess  = substr($STSPRS,$splitPos);

    $sqlGetData = 	"select 'Y' GET_DATA
                     from MGTAPPS.LOG_SENDING_MAIL_CUST
                     where LSMC_CUST_CODE_REFF = '$custProcess'
                     and LSMC_DATE_PROCESS_REFF = '$dtProcess' and rownum = 1";
    //echo "$ </br>";
    //if($USER_NAME==="1949")	{echo "sqlGetData : $sqlGetData </br>"; }
    $sendSts = getData($conn,$sqlGetData);

    $SqlCust = "select * from mgtdat.OM_CUSTOMER where CUST_CODE = '$custProcess' ";
    $rsDtCust = oci_parse($conn,$SqlCust);
    oci_execute ($rsDtCust);
    while ($rowCustDt = oci_fetch_array ($rsDtCust, OCI_BOTH)) {
  ?>
    <td align="center">
      <b>
        <font color="red" face="Verdana, Geneva, sans-serif" size="-1">
          <?php
            echo $rowCustDt['CUST_CODE']." - ".$rowCustDt['CUST_NAME']."</br>";
            //View Customer Email
            $MEC_CUST_CODE = $rowCustDt['CUST_CODE'];
            $dtEmailCust="";
            //include("DATA_EMAIL_CUST.php");
            if ($dtEmailCust==="No EMAIL Active"){
              $dtEmailCust="";
            }

            $SqlCust = "SELECT * FROM mgtapps.alrt_m_grp_mail_to WHERE amgmt_amgm_sys_id = '202208016'
                        AND AMGMT_IS_ACTIVE = 1 ";
            $rsDtCust = oci_parse($conn,$SqlCust);
            oci_execute ($rsDtCust);
            while ($rowCustDt = oci_fetch_array ($rsDtCust, OCI_BOTH)) {
              if ($dtEmailCust===""){
                $dtEmailCust = $rowCustDt['AMGMT_MAIL_ADDRS'];
              } else {
                $dtEmailCust = "$dtEmailCust".",".$rowCustDt['AMGMT_MAIL_ADDRS'];
              }

            }
            echo "</br>($dtEmailCust)";


            //View Customer Email
          ?>
        </font>
      </b>
    </td>
  <?php
    }


    if ($dtEmailCust==="No EMAIL Active") {
      $stsBtnSend   = "";
    }
  ?>
</table>
<table  style="width:60%" border="1" align="center">
</tr>
  <tr>
    <td style="width:3%;text-align: center;" >
      No
    </td>
    <td style="text-align: center;">
      Advance No
    </td>
    <td style="text-align: center;">
      FP No
    </td>
    <td align="center">
      Sys Id
    </td>
  </tr>
<?php
  $linkDoc = "D:/XAMPP/htdocs/webapps/Doc_Folder/";
  $linkFileDoc 	= "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/";

  //echo "dtProcess $dtProcess | custProcess $custProcess </br>";
  $dtlSql = "select rownum NO_REC, d.* from ($mainSql and td_sub_acnt_code = '$custProcess' order by FP_NO) d ";
  //echo "dtlSql $dtlSql </br>";
  //if($USER_NAME==="1949")	{echo "dtlSql : $dtlSql </br>"; }
  $rsDt = oci_parse($conn,$dtlSql);
  oci_execute ($rsDt);
  while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
    $FP_NO = $rowDt['FP_NO'];
    $fileFpSales = $linkDoc.$rowDt['FP_FOLDER']."/".$rowDt['FOLDER_YY']."/".$FP_NO.".pdf";

    $ADVANCE_NO = $rowDt['ADVANCE_NO'];
    $fileAdv = $linkDoc.$rowDt['ADV_FOLDER']."/".$rowDt['FOLDER_YY']."/".$ADVANCE_NO.".pdf";

    $linkFileFpSales = "VIEW FILE ".$linkFileDoc.$rowDt['FP_FOLDER']."/".$rowDt['FOLDER_YY']."/".$FP_NO.".pdf";
    $linkFileAdv = "VIEW FILE ".$linkFileDoc.$rowDt['ADV_FOLDER']."/".$rowDt['FOLDER_YY']."/".$ADVANCE_NO.".pdf";
  ?>
  <tr>
    <td style="width:3%;text-align: center;" >
      <?php echo $rowDt['NO_REC']; ?>
    </td>
    <td style="text-align: center;">
      <?php
        //echo "$fileAdv </br>";
        if (file_exists($fileAdv)){
      ?>
        <input type="button" value="<?php  echo $ADVANCE_NO;?>" class="BUTTON btn_delete" onclick="<?php echo "Process('$linkFileAdv')"; ?>">
      <?php
        } else {
          $stsAdvDoc   = "N";
      ?>
          <b><font color="red" face="Verdana, Geneva, sans-serif" size="-1"><?php echo $ADVANCE_NO."(File NA)"; ?> </font></b>
        <?php
        }
      ?>
    </td>
    <td style="text-align: center;">
      <?php
        if (file_exists($fileFpSales)){
      ?>
        <input type="button" value="<?php echo $FP_NO;?>" class="BUTTON btn_add" onclick="<?php echo "Process('$linkFileFpSales')"; ?>">
      <?php
    } else {
         $stsFpDoc   = "N";
      ?>
      <b><font color="red" face="Verdana, Geneva, sans-serif" size="-1"><?php echo $FP_NO."(File NA)"; ?> </font></b>
    <?php
    }
      ?>
    </td>
    <td align="center">
      <?php echo $rowDt['SYS_ID']; ?>
    </td>
  </tr>
  <?php
  }
  $prsSendMail = "SEND MAIL ".substr($STSPRS,4);
  //echo "stsBtnSend $stsBtnSend : stsAdvDoc $stsAdvDoc : stsFpDoc $stsFpDoc : prsSendMail $prsSendMail";
  if ($stsBtnSend==="Y" //&& $stsAdvDoc==="Y"
      &&$stsFpDoc==="Y"){
  ?>
    <tr>
      <td colspan="4" align="center">
        <input type="button" value="SEND Mail" class="BUTTON btn_process" onclick="<?php echo "Process('$prsSendMail')"; ?>">
      </td>
    </tr>
  <?php
  }

?>
<table>
&nbsp&nbsp&nbsp&nbsp
<?php
  if ($sendSts==="Y"){
?>
<table  style="width:60%" border="0" align="center">
  <tr>
    <td align="center">
      LOG SENDING EMAIL
    </td>
  </tr>
</table>
<table  style="width:60%" border="1" align="center">
  <tr>
    <td style="width:20%;text-align: center;" >
      No
    </td>
    <td align="center">
      Date Send
    </td>
  </tr>
<?php
  $SqlLog = "select rownum REC_NO,to_char(LSMC_CREATED_TIMESTAMP,'YYYY-MM-DD HH24:MI:SS') LSMC_CREATED_TIMESTAMP
              from (
              select * from MGTAPPS.LOG_SENDING_MAIL_CUST
              where LSMC_CUST_CODE_REFF = '$custProcess'
              and LSMC_DATE_PROCESS_REFF = '$dtProcess'
              order by LSMC_CREATED_TIMESTAMP desc
              ) d ";
  $rsDtLog = oci_parse($conn,$SqlLog);
  oci_execute ($rsDtLog);
  while ($rowDtLog = oci_fetch_array ($rsDtLog, OCI_BOTH)) {
?>
    <tr>
      <td style="width:20%;text-align: center;" >
        <?php echo $rowDtLog['REC_NO']; ?>
      </td>
      <td align="center">
        <?php echo $rowDtLog['LSMC_CREATED_TIMESTAMP']; ?>
      </td>
    </tr>
<?php
  }
  echo "</table>";
}
?>
