<?php
	$conn = oci_connect('<REDACTED>', '<REDACTED>', '<REDACTED>');
	//$conn = oci_connect('<REDACTED>', '<REDACTED>', '<REDACTED>')(HOST=mgtracdb-scan.mutugading.com)(PORT=1521)) (CONNECT_DATA=(SERVER=DEDICATED) (SERVICE_NAME = ALTHARA)))');
	if (!$conn) {
	    $e = oci_error();
		echo htmlentities($e[‘message’]);
	}
?>
