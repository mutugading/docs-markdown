<?php
$FOLDER = $ETD_SYS_ID_S." - ".$FOLDER_VAL_1_V." - ".$FOLDER_VAL_2_V;
if($FOLDER_VAL_3_V!==""){$FOLDER = $FOLDER."-".$FOLDER_VAL_3_V;}
if($FOLDER_VAL_4_V!==""){$FOLDER = $FOLDER."-".$FOLDER_VAL_4_V;}
if($FOLDER_VAL_5_V!==""){$FOLDER = $FOLDER."-".$FOLDER_VAL_5_V;}
?>

<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:60%" border="1">
    <tr >
      <td <?php echo getStyle(12,"right","40"); ?> >
        Folder :&nbsp&nbsp
      </td>
      <td <?php echo getStyle(12,"left"); ?> >
        &nbsp&nbsp<?php echo $FOLDER; ?>
      </td>
    </tr>
    <tr >
      <td <?php echo getStyle(12,"right"); ?> >
        File Name :&nbsp&nbsp
      </td>
      <td <?php echo getStyle(12,"left"); ?> >
        &nbsp&nbsp<?php echo   $P_NO_FILE.". $P_FILE_NM"; ?>
      </td>
    </tr>
    <tr >
      <td <?php echo getStyle(12,"right"); ?> >
        Remark :&nbsp&nbsp
      </td>
      <td <?php echo getStyle(12,"left"); ?> >
        &nbsp&nbsp<input type="text" id="REMARK" name="REMARK">
      </td>
    </tr>
    <tr >
      <td <?php echo getStyle(12,"center"); ?> colspan="2" >
<?php
    $btnDelFile="btnDelFile('$P_PRINT_LINK')";
?>
        <button type="button" onclick="<?php echo $btnDelFile; ?>" >Delete</button>
      </td>
    </tr>
  </table>
</div>
