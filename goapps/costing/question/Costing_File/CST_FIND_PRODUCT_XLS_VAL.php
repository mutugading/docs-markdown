<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");

$P_PERIOD_DATA = "";
if(isset($_GET['P_PERIOD_DATA'])) {
  $P_PERIOD_DATA=$_GET['P_PERIOD_DATA'];
}

$P_CYL_SYS_ID_DTL = "";
if(isset($_GET['P_CYL_SYS_ID_DTL'])) {
  $P_CYL_SYS_ID_DTL=$_GET['P_CYL_SYS_ID_DTL'];
}

$P_CYCRM_SYS_ID = "";
if(isset($_GET['P_CYCRM_SYS_ID'])) {
  $P_CYCRM_SYS_ID=$_GET['P_CYCRM_SYS_ID'];
}

$P_CHECK= "";
if(isset($_GET['P_CHECK'])) {
  $P_CHECK=$_GET['P_CHECK'];
}

$SqlData = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL' ";
$CYL_TYPE =  getData($conn,$SqlData);

$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
$isUseRmBo = getData($conn,$slctUseRm);

//getSysId marketing
$SqlData = "
            select mkt.CYL_SYS_ID GET_DATA
            from    mgtapps.cst_yarn_left val
                    ,mgtapps.cst_yarn_left mkt
            where val.CYL_SYS_ID='$P_CYL_SYS_ID_DTL'
            and val.CYL_LEFT_NO = mkt.CYL_LEFT_NO
            and mkt.CYL_PRS_TYPE = MGTAPPS.pkg_yarn_calculation.fPrsIDMkt";
//echo "SqlData $SqlData</br>";
//die();
$CYL_SYS_ID_MKT =  getData($conn,$SqlData);
//getSysId marketing

$sqlCkRm55 = "select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
              where CLLP_CYL_SYS_ID_REFF = '$CYL_SYS_ID_MKT'
              and rownum = 1
              ";
$STS_RM_55 = getData($conn,$sqlCkRm55);

//echo "STS_RM_55 $STS_RM_55 : CYL_TYPE $CYL_TYPE : isUseRmBo $isUseRmBo </br>";die();
if ($CYL_TYPE==="ITY"){
  include ("CST_FIND_PRODUCT_XLS_ITY.php");
} else if ($STS_RM_55==="Y"){
  include ("CST_FIND_PRODUCT_XLS_RM_55_VAL.php");
} else if ($CYL_TYPE==="MELANGE"){
  include ("CST_FIND_PRODUCT_XLS_MELANGE_VAL.php");
} else if ($CYL_TYPE==="ACY"){
  include ("CST_FIND_PRODUCT_XLS_ACY_VAL.php");
}else if ($CYL_TYPE==="PTY BO"){
  include ("CST_FIND_PRODUCT_XLS_PTY_BO_VAL.php");
} else if ($isUseRmBo==="Y"){
  include ("CST_FIND_PRODUCT_XLS_USE_RM_BO_VAL.php");
} else {
  include ("CST_FIND_PRODUCT_XLS_DFLT_VAL.php");
}
?>
