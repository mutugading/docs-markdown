<?php
	$conn = oci_connect('mgtapps', 'mgtapps', 'althara');
	//$conn = oci_connect('mgtapps', 'mgtapps', '(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=mgtracdb-scan.mutugading.com)(PORT=1521)) (CONNECT_DATA=(SERVER=DEDICATED) (SERVICE_NAME = ALTHARA)))');
	if (!$conn) {
	    $e = oci_error();
		echo htmlentities($e[‘message’]);
	}
?>
