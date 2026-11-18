<br><br>
<table style="width:100%" border="1">
	<tr>
		<td style="text-align: left;" >
			<b>LIST FILE UPLOAD</b>
		</td>
	</tr>
<?php

	$dir = "D:/XAMPP/htdocs/webapps/$SCAN_DOC_CST/$USER_NAME";
	//$dir = "D:/XAMPP/htdocs/webapps/$folderScanDoc";
	//echo "dir $dir</br>";
	if (is_dir($dir)){
	    if ($dh = opendir($dir)){
	    	//$recDt=-1;
      $noFile = 0;
			while (($file = readdir($dh)) !== false){
        if (substr($file,0,1)!=="."){
            $noFile++; $fileIsReady = "Y";
            if ($noFile===1){
                echo "<tr>";
            }
            echo "<td align='center'>".$noFile.".".$file."</td>";
        }
			}
      if ($noFile!==0){
          echo "</tr>";
      }

	    closedir($dh);
		}
	}
?>
</table>
