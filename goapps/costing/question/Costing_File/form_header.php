<?php
  include ("../form_header_title.php");
  include ("../form_header_main.php");
?>
<div class="w3-container w3-blue">
  <table class="w3-container w3-blue" width="100%" border="0" >
    <tr>
      <td style="width:20%;" >
        <?php if ($MenuUtamaHdr == "Y"){?>
          <span style="font-size:10px;cursor:pointer" onclick="openNav()"><font color='red'>&#9776;Main Menu</font></span>
        <?php } ?>
        <?php include ("../form_header_check_hide.php"); ?>
      </td>
      <td style="text-align:center" style="font-size:8px">
        <font color='white'>
          <?php
            if ($MENU_NAME !== "MAIN MENU"){
              echo "Menu : $MENU_NAME";
            }
          ?>
        </font>
      </td>
      <td style="text-align:right;font-size:8px;width:20%;">
          <?php if ($MenuPrint=="Y") {?>
            <div class="dropdown">
              <button onclick="myPrint()" class="dropbtn">Download</button>
              <div id="PrintMenu" class="dropdown-content">
          <?php if($MenuPrintHrefPdf!==""){?>
                    <a href="<?php echo $MenuPrintHrefPdf; ?>" title='Print PDF'><font size='1'>PDF</font></a>";
          <?php }
                if($MenuPrintHrefXls!==""){?>
                   <a href='<?php echo $MenuPrintHrefXls; ?>' title='Print Excel'><font size='1'>Excel</font></a>";
          <?php }?>
              </div>
          </div>
          <?php } ?>
      </td>
    </tr>
  </table>
  <input type="hidden" id="CEk_PRS" >
</div>
<?php
  $sqlGet = "
              select MMH_STATUS GET_DATA from mst_menus_header
              where MMH_USER_ID = '$USER_NAME'
              and MMH_MENU_ID = '$P_MENU_ID'
            ";

  $MMH_STATUS = getData($conn,$sqlGet);
  if ($MMH_STATUS==="N"){
    echo "<script type='text/JavaScript'>
            document.getElementById('ck_header_main').checked = true;
            document.getElementById('header_main').style.visibility='hidden';
            document.getElementById('header_main').style.height='0px';
          </script>";

  }
  include ("../form_header_js.php");
?>
