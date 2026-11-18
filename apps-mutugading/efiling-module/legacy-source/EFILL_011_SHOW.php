<?php
  include("../conOraOci.php"); include ("../common_function.php");
  $FORM_NAME = "";	if(isset($_GET['FORM_NAME'])){$FORM_NAME=$_GET['FORM_NAME'];}
  include ($FORM_NAME."_SQL.php"); $font="12";
  $P_MENU_ID = "";	if(isset($_GET['P_MENU_ID'])){$P_MENU_ID=$_GET['P_MENU_ID'];}
  $USER_NAME = "";	if(isset($_GET['USER_NAME'])){$USER_NAME=$_GET['USER_NAME'];}
  //$P_PAGE_NO = "";	if(isset($_GET['P_PAGE_NO'])){$P_PAGE_NO=$_GET['P_PAGE_NO'];} if($P_PAGE_NO===""){$P_PAGE_NO="0";}
  //$TOT_ROWS = "";	if(isset($_GET['TOT_ROWS'])){$TOT_ROWS=$_GET['TOT_ROWS'];} //if($P_PAGE_NO===""){$P_PAGE_NO="0";}

  $DATE_FROM = "";	if(isset($_GET['DATE_FROM'])){$DATE_FROM=$_GET['DATE_FROM'];}
  $DATE_TO = "";	if(isset($_GET['DATE_TO'])){$DATE_TO=$_GET['DATE_TO'];}
  $FILE_STATUS = "All File";	if(isset($_GET['FILE_STATUS'])){$FILE_STATUS=$_GET['FILE_STATUS'];}
  if ($FILE_STATUS===""){$FILE_STATUS = "All File";}

  $USER_DESC_FLTR = "";	if(isset($_GET['USER_DESC_FLTR'])){$USER_DESC_FLTR=$_GET['USER_DESC_FLTR'];}
  if ($USER_DESC_FLTR===""){$USER_DESC_FLTR = "All";}

  $DOCUMENT_TYPE = "";	if(isset($_GET['DOCUMENT_TYPE'])){$DOCUMENT_TYPE=$_GET['DOCUMENT_TYPE'];}
  if($DOCUMENT_TYPE!=="Null"){
    if($DOCUMENT_TYPE!==""){
    //echo "DOCUMENT_TYPE $DOCUMENT_TYPE : DATE_FROM $DATE_FROM : DATE_TO $DATE_TO <br>";
    //echo "FILE_STATUS $FILE_STATUS<br>";
    //echo "USER_DESC_FLTR $USER_DESC_FLTR<br>";
    //$sqlDt = getSqlView ($DATE_FROM,$DATE_TO,$DOCUMENT_TYPE);
    $sqlDt = getSqlData($DATE_FROM,$DATE_TO);
    $sqlCkDt="select count(-1) GET_DATA from ($sqlDt)a where TH_TRAN_CODE='$DOCUMENT_TYPE'";
    $sqlName="select distinct USER_DESC from ($sqlDt)a where TH_TRAN_CODE='$DOCUMENT_TYPE' order by USER_DESC";
    $totDt = getData($conn,$sqlCkDt);


    $whereDt=" where TH_TRAN_CODE='$DOCUMENT_TYPE' ";
    if ($USER_DESC_FLTR!=="All"){ $whereDt=" $whereDt and USER_DESC='$USER_DESC_FLTR' "; }
    $sqlDt = "
              select  ROWNUM REC_NO
                            ,TH_TRAN_CODE TRX_FOLDER
                            ,SUBSTR (TH_DOC_NO, 1, 4) TRX_YY
                            ,TH_TRAN_CODE|| '-' ||TH_DOC_NO TRX_NO
                            ,TO_CHAR (TH_DOC_DT,'YYYY-MM-DD') TRX_DT
                            ,TH_DESC
                            ,TH_CR_UID CRT_BY
                            ,USER_DESC CRT_DESC
                            ,TO_CHAR (TH_CR_DT, 'YYYY-MM-DD') CRT_DT
              from
              (
                select * from (select a.* from ($sqlDt)a $whereDt ) a
                order by TH_DOC_DT desc,TH_TRAN_CODE || '-' || TH_DOC_NO
              )a
              ";
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:100%" border="0">
    <tr >
      <td <?php echo getStyle(12,"center"); ?> >
        <b>Date Start : </b> <input type="date" id="DATE_FROM" value="<?php echo $DATE_FROM; ?>" onchange="changePrm('DATE_FROM',this.value)">
        &nbsp&nbsp<b>Date End : </b> <input type="date" id="DATE_TO" value="<?php echo $DATE_TO; ?>" onchange="changePrm('DATE_TO',this.value)">
        &nbsp&nbsp<input type="checkbox" id="VIEW_SQL" name="VIEW_SQL" onclick="viewSql()">&nbspView Sql
        &nbsp&nbspFile Status :
        <select id="FILE_STATUS" onchange="changePrm('FILE_STATUS',this.value)">
          <option value="All File" <?php if($FILE_STATUS==="All File"){echo "selected";} ?> >All File</option>
          <option value="All Exist File" <?php if($FILE_STATUS==="All Exist File"){echo "selected";} ?>>All Exist File</option>
          <option value="All Not Exist File" <?php if($FILE_STATUS==="All Not Exist File"){echo "selected";} ?>>All Not Exist File</option>
        <select>
      </td>
    </tr>
</table>
<br>
<table style="width:100%" border="0">
<?php
    if ($totDt!=="0"){
      //echo "totDt $totDt<br>$sqlName<br>";
      $rsName = oci_parse($conn,$sqlName);
      oci_execute ($rsName);
?>
  <tr >
    <td <?php echo getStyle(12,"center"); ?> >
      User Documents :&nbsp&nbsp
      <select id="USER_DESC_FLTR" onchange="changePrm('USER_DESC_FLTR',this.value)">
        <option value="All" >All</option>
<?php
      while ($rowRsName = oci_fetch_array ($rsName, OCI_BOTH)) {
        $USER_DESC=""; if(!empty($rowRsName['USER_DESC'])){$USER_DESC=$rowRsName['USER_DESC'];}//1
?>
      <option value="<?php echo $USER_DESC; ?>" <?php if($USER_DESC_FLTR===$USER_DESC){echo "selected";} ?>><?php echo $USER_DESC; ?></option>
<?php
      }
?>
      <select>
      </td>
    </tr>
<?php
    }
?>
  </table>
</div>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <div id="DIV_SQL" class="main" style="overflow-x:auto;overflow-y:auto;height:0px;visibility:hidden" align="center">
    <?php echo $sqlDt; ?>
  </div>
</div>
<?php
      //if($USER_NAME==="1949"){ echo "FILE_STATUS $FILE_STATUS<br>"; };
      if ($DATE_FROM!=="" && $DATE_TO!==""){
        if ($FILE_STATUS==="All File"){
          include ($FORM_NAME."_SHOW_DATA.php");
        } else if ($FILE_STATUS==="All Exist File"){
          include ($FORM_NAME."_SHOW_DATA_EXIST.php");
        } else if ($FILE_STATUS==="All Not Exist File"){
          include ($FORM_NAME."_SHOW_DATA_NOTEXIST.php");
        }
      }
    }
  }
?>
