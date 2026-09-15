<?php session_start();?>
<?php
	header('Cache-Control: no-cache, must-revalidate, max-age=0');
	header('Cache-Control: post-check=0, pre-check=0',false);
	header('Pragma: no-cache');
  include("../conOraOci.php");
	include ("../common_function.php");
	include ("FORM_NAME.php");

	$byType = "Y";
	$byShade = "";
	$byCust = "";
	$byShadeMulti = "";
	$CYL_IS_PROJECT_S = "";
	//Get Parameter

	//Get Parameter
	$MenuHeader1 = "Y";
	$MenuUtamaHdr = "Y";
	$MenuLogoutHdr = "Y";
	$MenuPrint = "";

	include ("form_header.php");
	include ("check_login.php");

	$CYL_TYPE_S = !empty($_POST['CYL_TYPE_S'])? $_POST['CYL_TYPE_S']: "";
	$FIND_DATA_S = !empty($_POST['FIND_DATA_S'])? $_POST['FIND_DATA_S']: "";

	$PAGE_NO_S = !empty($_POST['PAGE_NO_S'])? $_POST['PAGE_NO_S']: "1";

	// echo "CYL_TYPE_S $CYL_TYPE_S<br>";

	$styleSlct = "style='color:blue'";
	//if ($USER_NAME==="1949"){echo "$TOT_DT $SqlView</br>";}
	$details = ["A", "B", "C"];
	$font="12";
?>
<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" type="text/css" href="../css/BUTTON.css">
<link rel="stylesheet" type="text/css" href="../css/MENU_2.css">
<link rel="stylesheet" type="text/css" href="../css/TR_HOVER.css">
<link rel="stylesheet" type="text/css" href="../css/blink.css">
<link rel="stylesheet" type="text/css" href="../css/AUTO_COMPLETE.css">
<link rel="stylesheet" type="text/css" href="../css/tooltips.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" type="text/css" href="../style.css">

<!-- tooltip -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<!-- tooltip -->

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style type="text/css">
	table {
	  border-collapse: collapse;
	  width: 100%;
	}
</style>
<style>
html, body {
  height: 100%;
  margin: 0;
}

.container {
  width: 100%;      /* penuh lebar layar */
  height: 100vh;     /* penuh tinggi layar */
  box-sizing: border-box;
  padding: 20px;
  display: flex;
  flex-direction: column;
}

.list {
  border: 1px dashed #ccc;
  padding: 20px;
  min-height: 150px;
}

.list-item {
  background-color: #f1f1f1;
  border: 1px solid #aaa;
  cursor: grab;
	user-select: text;              /* Bisa di blok */
  -webkit-user-select: text;      /* Chrome/Safari */
  -moz-user-select: text;         /* Firefox */
}
.list-item:active {
  cursor: grabbing;
}

.details {
  display: flex;
  gap: 15px;          /* beri jarak lebih lega */
}

.drop-area {
  flex: 2;
  border: 2px dashed #ccc;
  min-height: 300px;
  height: 350px;              /* tinggi tetap */
  overflow: auto;             /* munculkan scroll */
  padding: 10px;
  box-sizing: border-box;
  transition: background-color 0.2s;
}

.drop-area.drag-over {
  background-color: #e0f7fa;
}

.drop-title {
  font-weight: bold;
}

.drop-area table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;   /* cegah melebar */
  word-wrap: break-word; /* teks panjang turun */
}

.drop-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1px;
}

.clear-btn {
  background: #ff5252;
  color: white;
  border: none;
  padding: 1px 3px;
  cursor: pointer;
  font-size: 11px;
  border-radius: 1px;
}

.clear-btn:hover {
  background: #d32f2f;
}
</style>
<style>
.spinner {
  width: 20px;
  height: 20px;
  border: 3px solid #ddd;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  margin-right: 8px;
  vertical-align: middle;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 15px;
  font-weight: 500;
  color: #555;
}
</style>
</head>
<body >
<?php
	include("menuData.php");
	$styleTxt = "font-family:verdana;font-size:33px";
?>
<!--<form action="ATTND_002.PHP" method="post" name="ATTND_002" id="ATTND_002">-->
<div class="main" style="overflow-x:auto;" align="center" onclick="closeNav()">
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
<!-- DEFINE LAYOUT -->

<!-- ================= LIST ================= -->
<?php include ($FORM_NAME."_LIST.php"); ?>

<!-- ================= DETAILS ================= -->
<?php include ($FORM_NAME."_DTL.php"); ?>

<!-- DEFINE LAYOUT -->
<input type="hidden" name="P_MENU_ID" id="P_MENU_ID" value="<?php echo $P_MENU_ID; ?>">
<input type="hidden" name="CYL_TYPE_S" id="CYL_TYPE_S"  value="<?php echo $CYL_TYPE_S; ?>">
<input type="hidden" name="FIND_DATA_S" id="FIND_DATA_S"  value="<?php echo $FIND_DATA_S; ?>">
<input type="hidden" name="PAGE_NO_S" id="PAGE_NO_S"  value="<?php echo $PAGE_NO_S; ?>">


<?php include ($FORM_NAME."_JS.php"); ?>
</form>
</div>
</body>
</html>
