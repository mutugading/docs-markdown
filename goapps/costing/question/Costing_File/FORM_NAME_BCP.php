<?php
	$P_MENU_ID = "";
	if(isset($_GET['P_MENU_ID'])) {
		$P_MENU_ID = $_GET['P_MENU_ID'];
	}
	if ($P_MENU_ID === ""){
		if(isset($_POST['P_MENU_ID'])) {
			$P_MENU_ID =$_POST['P_MENU_ID'];
		}
	}

	//echo "$P_MENU_ID<br>";
	$FORM_NAME = "";
	$FORM_NAME = getFileName($conn,$P_MENU_ID);
	//echo "$FORM_NAME<br>";
	$FORM_NAME = str_replace("COSTING_FILE\\","",strtoupper($FORM_NAME));
	//echo "$FORM_NAME<br>";

	$MENU_NAME = "";
	$MENU_NAME = getMenuName($conn,$P_MENU_ID);

	$MAIN_MENU_NAME = "";
	$MAIN_MENU_NAME = getMainMenuName($conn,$P_MENU_ID);

	if ($MAIN_MENU_NAME !== "") {
		$MENU_NAME = $MAIN_MENU_NAME." - ".$MENU_NAME;
	}
	//echo "Form Name $P_MENU_ID - $FORM_NAME";
?>
