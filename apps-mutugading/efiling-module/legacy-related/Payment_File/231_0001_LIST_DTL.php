<?php
  $font = "13";
  //header
  //echo $sqlView." and PAYMENT_NO = '$PAYMENT_NO_S'";
  $sqlDtDtl = $sqlView." and PAYMENT_NO = '$PAYMENT_NO_S'";
  //echo "STSPRS $STSPRS:PAYMENT_NO_S $PAYMENT_NO_S:DATE_FROM_S $DATE_FROM_S:DATE_TO_S $DATE_TO_S<br>";

  //echo "$sqlDtDtl<br>";
  $rsDtHdr = oci_parse($conn,$sqlDtDtl);
  oci_execute ($rsDtHdr);
  while ($rowDtHdr = oci_fetch_array ($rsDtHdr, OCI_BOTH)) {
?>
<br>
<table style="width:100%">
<tr>
  <td <?php echo getStyle($font,"right","50"); ?> > PAYMENT NO : </td>
  <td <?php echo getStyle($font,"left"); ?> > <b><?php echo $rowDtHdr['PAYMENT_NO']; ?></b> </td>
</tr>
<tr>
  <td <?php echo getStyle($font,"right","50"); ?> > Date : </td>
  <td <?php echo getStyle($font,"left"); ?>  ><b><?php echo $rowDtHdr['PH_DOC_DT']; ?></b></td>
</tr>
<tr>
  <td <?php echo getStyle($font,"right"); ?> >Supplier Name :</td>
  <td <?php echo getStyle($font,"left"); ?>  >
    <b><?php
      if (!empty($rowDtHdr['SUPP_NAME'])){
        echo $rowDtHdr['SUPP_NAME'];
      }
    ?></b>
  </td>
</tr>
<tr>
  <td <?php echo getStyle($font,"right"); ?> >Supplier Bank Information :</td>
  <td <?php echo getStyle($font,"left"); ?>  >
    <b><?php
      if (!empty($rowDtHdr['SBC_BANK_NAME'])){
        echo $rowDtHdr['SBC_BANK_NAME']."</br>";
      }
      if (!empty($rowDtHdr['SBC_BANK_ACNT_NO'])){
        echo $rowDtHdr['SBC_BANK_ACNT_NO']."</br>";
      }
      if (!empty($rowDtHdr['SBC_BANK_ACNT_NAME'])){
        echo $rowDtHdr['SBC_BANK_ACNT_NAME']."</br>";
      }
    ?></b>
  </td>
</tr>
<tr>
  <td <?php echo getStyle($font,"right"); ?>  >Total Amount :</td>
  <td <?php echo getStyle($font,"left"); ?> >
    <b><?php
      if (!empty($rowDtHdr['PH_FC_AMT'])){
        echo $rowDtHdr['PH_CURR_CODE']." ".number_format($rowDtHdr['PH_FC_AMT']);
      }
    ?>
    </b>
  </td>
</tr>
<tr>
  <td <?php echo getStyle($font,"right"); ?>  >MGT Rek No :</td>
  <td <?php echo getStyle($font,"left"); ?>  >
    <b><?php
      if (!empty($rowDtHdr['PH_BANK_ACNT_NO'])){
        echo $rowDtHdr['PH_BANK_ACNT_NO'];
      }
    ?>
    </b>
  </td>
</tr>
</table>
<?php
}?>
<table style="width:100%" border="1">
<tr>
  <td <?php echo getStyle($font,"center"); ?> >No</td>
  <td <?php echo getStyle($font,"center"); ?> >Transaction No</td>
  <td <?php echo getStyle($font,"center"); ?> >Doc Reff</td>
  <td <?php echo getStyle($font,"center"); ?> >Amount</td>
</tr>
<?php
$sqlDtl =
  "
  select distinct	TRANS_NO, PD_REF_DOC_REF,PD_FC_AMT,USER_DESC,PH_BANK_ACNT_NO
      ,SUBSTR (trans_no, 1, INSTR (trans_no, '-') - 1)||'/'||PERIOD_YEAR|| '/'||trans_no||'.pdf' PATH_FILE
      ,PH_BANK_ACNT_NO, BANK_NAME,PH_CURR_CODE, PH_FC_AMT
  FROM ( $sqlData) where payment_no = '$PAYMENT_NO_S'
  ";
 if ($USER_NAME==="ADMIN"){
  //echo "sqlDtl $sqlDtl</br>";
 }
$Rectl = 0;
$rsDtl = oci_parse($conn,$sqlDtl);
oci_execute ($rsDtl);
$linkdtl = "";
$linkFile = "";
while ($rowDtl = oci_fetch_array ($rsDtl, OCI_BOTH)) {
  $Rectl++;
  //https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/NSBR/2021/NSBR-2021003748.pdf?P_MENU_ID=2G10000000
  $linkdtl = "https://mgtapps.mutugading.com:4433/webapps/Doc_Folder/".$rowDtl['PATH_FILE'];
  $linkFile = "D:/XAMPP/htdocs/webapps/Doc_Folder/".$rowDtl['PATH_FILE'];
?>
<tr>
  <td <?php echo getStyle($font,"left"); ?> > <?php echo $Rectl; ?></td>
  <td <?php echo getStyle($font,"left"); ?> >
    <font color = "red"  size = '4'>
    <b>
<?php 	//echo $linkFile."</br>";
  if (file_exists($linkFile)) {
    $viewFileDtl = "ViewFileDtl('$linkdtl','$PAYMENT_NO_S')";
?>
      <button onclick="<?php echo $viewFileDtl; ?>"><?php echo $rowDtl['TRANS_NO']; ?></button>
<?php 	}	else {
    if (!empty($rowDtl['TRANS_NO'])){
      echo $rowDtl['TRANS_NO'];
    }
    echo "(File NA)";
  }
  ?>
    </b>
    </font>
  </td>
  <td <?php echo getStyle($font,"center"); ?> ><?php echo $rowDtl['PD_REF_DOC_REF']; ?></td>
  <td <?php echo getStyle($font,"Right"); ?>> <?php echo number_format($rowDtl['PD_FC_AMT']); ?> </td>

</tr>
<?php
}
?>
</table>
