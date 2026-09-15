<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');

include("conOraOci.php");
//include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");

$P_CYL_SYS_ID_DTL = ""; if(isset($_GET['P_CYL_SYS_ID_DTL'])) {$P_CYL_SYS_ID_DTL=$_GET['P_CYL_SYS_ID_DTL'];}

$P_CYCRM_SYS_ID = ""; if(isset($_GET['P_CYCRM_SYS_ID'])) {$P_CYCRM_SYS_ID=$_GET['P_CYCRM_SYS_ID'];}

$P_CHECK= ""; if(isset($_GET['P_CHECK'])){$P_CHECK=$_GET['P_CHECK'];}

$SqlData = "select CYL_TYPE GET_DATA from mgtapps.CST_YARN_LEFT where CYL_SYS_ID = '$P_CYL_SYS_ID_DTL' ";
$CYL_TYPE =  getData($conn,$SqlData);

$slctUseRm = "select MGTAPPS.pkg_yarn_marketing.isUseRmBO('$P_CYL_SYS_ID_DTL') GET_DATA from dual";
$isUseRmBo = getData($conn,$slctUseRm);

//echo "Ck 1 : isUseRmBo $isUseRmBo <br>";//die();

$sqlCkRm55 = "select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
              where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID_DTL'
              and rownum = 1
              ";
$STS_RM_55 = getData($conn,$sqlCkRm55);

if ($STS_RM_55==="Y"){
  // echo "CST_FIND_PRODUCT_XLS_RM_55.php";die();
  include ("CST_FIND_PRODUCT_XLS_RM_55.php");
} else {
  // echo "bukan CST_FIND_PRODUCT_XLS_RM_55.php";//die();
  // echo "isUseRmBo $isUseRmBo : CYL_TYPE $CYL_TYPE : ";die();
  if ($isUseRmBo==="Y"){
    include ("CST_FIND_PRODUCT_XLS_USE_RM_BO.php");
  } else {
    // if ($P_CYL_SYS_ID_DTL==="2026041643132"){ echo "CYL_TYPE $CYL_TYPE : STS_RM_55 $STS_RM_55 : CYL_TYPE $CYL_TYPE : isUseRmBo $isUseRmBo </br>"; die();}
    if ($CYL_TYPE==="ITY"){
      // echo "CYL_TYPE ITY ";die();
      include ("CST_FIND_PRODUCT_XLS_ITY.php");
    } else if ($CYL_TYPE==="MELANGE"){
      // if ($P_CYL_SYS_ID_DTL==="2026041643132"){ echo "PROCESS CYL_TYPE MELANGE : STS_RM_55 $STS_RM_55 : CYL_TYPE $CYL_TYPE : isUseRmBo $isUseRmBo </br>"; die();}
      include ("CST_FIND_PRODUCT_XLS_MELANGE.php");
    } else if ($CYL_TYPE==="ACY"){
      // echo "CYL_TYPE ACY ";die();
      include ("CST_FIND_PRODUCT_XLS_ACY.php");
    } else if ($CYL_TYPE==="PTY BO"){
      // echo "CYL_TYPE PTY BO ";die();
      include ("CST_FIND_PRODUCT_XLS_PTY_BO.php");
    } else {
      // echo "CYL_TYPE DFLT ";die();
      include ("CST_FIND_PRODUCT_XLS_DFLT.php");
    }
  }
}
?>
