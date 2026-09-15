<?php
  function fGetFolderNm($conn,$EDF_SYS_ID){

    $sqlGet =
          "
          select EDF_FOLDER GET_DATA from EFILL_DATA_FOLDER
          where EDF_SYS_ID = '$EDF_SYS_ID'
          ";

    //echo "$sqlGet <br>";
    $rtn = getData($conn,$sqlGet);

    return ($rtn);

  }
?>
