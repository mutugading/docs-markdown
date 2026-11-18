<?php
   if (!isset($_SESSION['username'])){
		echo "<form method='post' id='checkLogin' name='checkLogin' action='logout.php' >";
		echo "</form>";
		echo "<script type='text/javascript'>";
		echo "document.getElementById('checkLogin').submit();";
		echo "</script>";
	}
?>
