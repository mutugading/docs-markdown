<?php
	$sqlDataVal =	sqlDataVal($PERIOD_DATA_S);

	//echo "form Product Choice : STSPRS $STSPRS</br>";
	if ($USER_NAME === "1949"){
		//echo "$USER_NAME STSPRS $STSPRS</br>";
	}
	if (substr($STSPRS,0,7) === "DETAIL " ){
		include ("CST_FIND_PRODUCT_VIEW_DETAIL_VAL.php");
	} else{
		include ($FORM_NAME."_PARAM.php");
		if ($STSPRS==="VIEW_DATA"){
			include ("CST_FIND_PRODUCT_VIEW_VAL.php");
		}
	}
?>
