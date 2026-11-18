<?php
include("../conOraOci.php"); include ("../common_function.php");
$FORM_NAME = $_GET['FORM_NAME']; $USER_NAME = $_GET['USER_NAME']; $P_MENU_ID = $_GET['P_MENU_ID'];
include ($FORM_NAME."_SQL.php");
$STS_LOV = $_GET['STS_LOV'];
$FIND_LOV = ""; if(isset($_GET['FIND_LOV'])){$FIND_LOV=$_GET['FIND_LOV'];}
echo "STS_LOV $STS_LOV<br>";
if ($STS_LOV==="DEL_FILE"){

  $P_NO_FILE = ""; if(isset($_GET['P_NO_FILE'])){$P_NO_FILE=$_GET['P_NO_FILE'];}
  $P_FILE_NM = ""; if(isset($_GET['P_FILE_NM'])){$P_FILE_NM=$_GET['P_FILE_NM'];}
  $P_PRINT_LINK = ""; if(isset($_GET['P_PRINT_LINK'])){$P_PRINT_LINK=$_GET['P_PRINT_LINK'];}

  $ETD_SYS_ID_S = ""; if(isset($_GET['ETD_SYS_ID_S'])){$ETD_SYS_ID_S=$_GET['ETD_SYS_ID_S'];}
  $FOLDER_VAL_1_S = ""; if(isset($_GET['FOLDER_VAL_1_S'])){$FOLDER_VAL_1_S=$_GET['FOLDER_VAL_1_S'];}
  $FOLDER_VAL_1_V = ""; if(isset($_GET['FOLDER_VAL_1_V'])){$FOLDER_VAL_1_V=$_GET['FOLDER_VAL_1_V'];}
  $FOLDER_VAL_2_S = ""; if(isset($_GET['FOLDER_VAL_2_S'])){$FOLDER_VAL_2_S=$_GET['FOLDER_VAL_2_S'];}
  $FOLDER_VAL_2_V = ""; if(isset($_GET['FOLDER_VAL_2_V'])){$FOLDER_VAL_2_V=$_GET['FOLDER_VAL_2_V'];}
  $FOLDER_VAL_3_S = ""; if(isset($_GET['FOLDER_VAL_3_S'])){$FOLDER_VAL_3_S=$_GET['FOLDER_VAL_3_S'];}
  $FOLDER_VAL_3_V = ""; if(isset($_GET['FOLDER_VAL_3_V'])){$FOLDER_VAL_3_V=$_GET['FOLDER_VAL_3_V'];}
  $FOLDER_VAL_4_S = ""; if(isset($_GET['FOLDER_VAL_4_S'])){$FOLDER_VAL_4_S=$_GET['FOLDER_VAL_4_S'];}
  $FOLDER_VAL_4_V = ""; if(isset($_GET['FOLDER_VAL_4_V'])){$FOLDER_VAL_4_V=$_GET['FOLDER_VAL_4_V'];}
  $FOLDER_VAL_5_S = ""; if(isset($_GET['FOLDER_VAL_5_S'])){$FOLDER_VAL_5_S=$_GET['FOLDER_VAL_5_S'];}
  $FOLDER_VAL_5_V = ""; if(isset($_GET['FOLDER_VAL_5_V'])){$FOLDER_VAL_5_V=$_GET['FOLDER_VAL_5_V'];}

  // echo "P_NO_FILE $P_NO_FILE : P_FILE_NM $P_FILE_NM : P_PRINT_LINK $P_PRINT_LINK<br>";
  //
  // echo "ETD_SYS_ID_S $ETD_SYS_ID_S : FOLDER_VAL_1_S $FOLDER_VAL_1_S: FOLDER_VAL_1_V $FOLDER_VAL_1_V<br>";
  // echo "FOLDER_VAL_2_S $FOLDER_VAL_2_S : FOLDER_VAL_2_V $FOLDER_VAL_2_V : FOLDER_VAL_3_S $FOLDER_VAL_3_S<br>";
  // echo "FOLDER_VAL_3_V $FOLDER_VAL_3_V : FOLDER_VAL_4_S $FOLDER_VAL_4_S : FOLDER_VAL_4_V $FOLDER_VAL_4_V<br>";
  // echo "FOLDER_VAL_5_S $FOLDER_VAL_5_S : FOLDER_VAL_5_V $FOLDER_VAL_5_V<br>";
  include ($FORM_NAME."_LOV_DEL_FILE.php");
}
?>
