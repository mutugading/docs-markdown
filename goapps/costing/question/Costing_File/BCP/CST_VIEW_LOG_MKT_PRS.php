<?php
$mgtErr = "Please wait until All Calculation Process Complete";
echo "
      <span class='blink'>
      <p align='center'>
      <font face = 'Comic sans MS' size ='5' color = 'red'>".$mgtErr."</font>
      </p>
      </span>
      ";
?>
<div class="main" style="overflow-x:auto;" align="center">
  <table style="width:50%" align="center" border="1">
    <tr>
      <td align="center" colspan="8">
        Log Process
      </td>
    <tr>
    <tr>
      <td align="center">No</td>
      <td align="center">Process Type</td>
      <td align="center">Total Data</td>
      <td align="center">Total Process</td>
      <td align="center">% Process</td>
      <td align="center">Total Left</td>
      <td align="center">Status</td>
      <td align="center">Total Running</td>
    <tr>
<?php
  $sqlSlct =
  "
  SELECT 	CYCPS_SEQ_NO,
          CYCPS_PRODUCT_TYPE,
          MGTAPPS.fSetDecimal (nvl(CAPPD_TOT_DATA,'0'),0) CAPPD_TOT_DATA,
          MGTAPPS.fSetDecimal (nvl(TOT_PRS,'0'),0) TOT_PRS,
          CASE
            WHEN CAPPD_TOT_DATA <> 0
            THEN MGTAPPS.fSetDecimal ( (TOT_PRS / CAPPD_TOT_DATA) * 100, 2)
            else '0'
          END PRSN_PRS
          ,CASE
            WHEN CAPPD_TOT_DATA <> 0 then
                CAPPD_TOT_DATA - TOT_PRS
            else 0
            end TOT_LEFT
          ,case when CAPPD_STS_PRS is not NULL then
                  decode(CAPPD_STS_PRS,1,'Complete','Running')
                else '-'
           end STS_PRS,nvl(TOT_RUN,0) TOT_RUN
  FROM (  SELECT CYCPS_SEQ_NO,
           CYCPS_PRODUCT_TYPE,
           CAPPD_TOT_DATA,CAPPD_STS_PRS,
           (
             SELECT COUNT (-1) FROM mgtapps.CST_ALL_PROD_PRS_LOG_DTL WHERE CAPPLD_CAPPD_SYS_ID = CAPPD_SYS_ID
           ) TOT_PRS,
           (
             SELECT COUNT (-1) FROM mgtapps.CST_ALL_PROD_PRS_LOG_DTL
             WHERE CAPPLD_CAPPD_SYS_ID = CAPPD_SYS_ID
             AND NVL(CAPPLD_STS_PRS, 0) = 0
           ) TOT_RUN
      FROM cst_yarn_calc_prod_seq a,
           (SELECT CAPPD_CYL_TYPE, CAPPD_TOT_DATA, CAPPD_SYS_ID,CAPPD_STS_PRS
              FROM mgtapps.CST_ALL_PROD_PRS_HDR a,
                   mgtapps.CST_ALL_PROD_PRS_DTL b
             WHERE     NVL (CAPPH_STS_PRS, 0) = 0
                   AND CAPPH_CYL_PRS_TYPE = '20210800119'
                   AND a.CAPPH_SYS_ID = CAPPD_CAPPH_SYS_ID) b
     WHERE CYCPS_PRODUCT_TYPE = CAPPD_CYL_TYPE(+)
  ORDER BY CYCPS_SEQ_NO)
  ";

  $rsView = oci_parse($conn,$sqlSlct);
  oci_execute ($rsView);
  //echo $sqlSlct;
  while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
    $b1="";$b2="";
    if ($rowRsView['STS_PRS']==="Running"){
      $b1="<font face = 'Comic sans MS' size ='4' color = 'red'>";
      $b2="</font>";
    }
?>
<tr>
<td align="right"> <?php echo $b1.$rowRsView['CYCPS_SEQ_NO'].$b2; ?>	</td>
<td align="left">	<?php echo $b1.$rowRsView['CYCPS_PRODUCT_TYPE'].$b2; ?>	</td>
<td align="right"> <?php echo $b1.$rowRsView['CAPPD_TOT_DATA'].$b2; ?>&nbsp&nbsp&nbsp	</td>
<td align="right"> <?php echo $b1.$rowRsView['TOT_PRS'].$b2; ?>&nbsp&nbsp&nbsp	</td>
<td align="right"> <?php echo $b1.$rowRsView['PRSN_PRS'].$b2; ?>&nbsp&nbsp&nbsp	</td>
<td align="right"> <?php echo $b1.$rowRsView['TOT_LEFT'].$b2; ?>&nbsp&nbsp&nbsp	</td>
<td align="center"> <?php echo $b1.$rowRsView['STS_PRS'].$b2; ?>	</td>
<td align="right"> <?php echo $b1.$rowRsView['TOT_RUN'].$b2; ?>&nbsp&nbsp&nbsp	</td>
<tr>

<?php
  }
?>
</table>
<?php

$mgtErr = "Last Refresh <br> ".date('l jS \of F Y h:i:s A');
echo "
    <span class='blink'>
    <p align='center'>
    <font face = 'Comic sans MS' size ='2' color = 'blue'>".$mgtErr."</font>
    </p>
    </span>
    ";

$page ="https://mgtapps.mutugading.com:4433/webapps/Costing_File/CST_T_002?P_MENU_ID=2G20000000";//real
$timeRefrsh = "10000";
//$timeRefrsh = "60000";
//$timeRefrsh = "120000";//2mnt
echo "<script> setTimeout(function() { window.location = '$page'; },$timeRefrsh); </script> ";
?>
