<?php
  function getSqlFolder($conn,$USER_NAME){
    $sqlGetData = "select 'ADMIN' GET_DATA from MGTAPPS.EFILL_ADMIN_USER where EAU_USER_ID = '$USER_NAME'";
  	$EFILL_ADMIN = getData($conn,$sqlGetData);

    //echo "EFILL_ADMIN $EFILL_ADMIN<br>";

    $sqlList = "";
  	if ($EFILL_ADMIN==="ADMIN"){
  		$sqlList =
            "
            select 	ETD_SYS_ID
  					from    EFILL_TYPE_DATA a
            order by ETD_SYS_ID
            ";
  	} else {
  		$sqlList =
            "
            select ETD_SYS_ID
  					from   EFILL_TYPE_DATA a,EFILL_USER_ACCESS b
  					where a.ETD_SYS_ID = b.EUA_ETD_SYS_ID
  					and EUA_USER_ID = '$USER_NAME'
            order by ETD_SYS_ID
            ";
  	}

    return $sqlList;
  }
?>
