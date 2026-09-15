<head>
<title>Mutu Gading Tekstil</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!--<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">-->
<link rel="stylesheet" href="../w3.css">
</head>
<style type="text/css">
.dropbtn {
  background-color: #ff0000;
  color: white;
  padding: 2px;
  font-size: 10px;
  border: none;
  cursor: pointer;
}

.dropbtn:hover, .dropbtn:focus {
  background-color: #2980B9;
}

.dropdown {
  position: relative;
  display: inline-block;
}

.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f1f1f1;
  min-width: 50px;
  overflow: auto;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

.dropdown-content a {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
}

.dropdown a:hover {background-color: #d8acac;}

.show {display: block;}

</style>
<div class="w3-container w3-blue">
  <?php $curDays = date("l")." ".date("d-m-Y"); ?>
  <table class="w3-container w3-blue" width="100%" border="0" >
  <br>
    <tr>
    <td style="width:20%;">
    <img src="../USERLOGO.bmp" style="width:40%;">
    </td>
    <td align="center">
        <br><br><br>
<?php
  $clockClr = "";
  if(isset($_POST['clockClr'])) {
    $clockClr=$_POST['clockClr'];
  }

  if ($clockClr==="") {
    $clockClr = "white";
  }else if ($clockClr==="white") {
    $clockClr = "red";
  }else if ($clockClr==="red") {
    $clockClr = "blue";
  }else if ($clockClr==="blue") {
    $clockClr = "green";
  }else if ($clockClr==="green") {
    $clockClr = "white";
  }
?>
  <input type="hidden" name="clockClr" id="clockClr" value="<?php echo $clockClr; ?>" >
        <b><font color="<?php echo $clockClr; ?>" size="+2"><div id="clock"></div></font></b>
        <script type="text/javascript">
        <!--
        function showTime() {
            var a_p = "";
            var today = new Date();
            var curr_hour = today.getHours();
            var curr_minute = today.getMinutes();
            var curr_second = today.getSeconds();
            if (curr_hour < 12) {
                a_p = "AM";
            } else {
                a_p = "PM";
            }
            if (curr_hour == 0) {
                curr_hour = 12;
            }
            if (curr_hour > 12) {
                curr_hour = curr_hour - 12;
            }
            curr_hour = checkTime(curr_hour);
            curr_minute = checkTime(curr_minute);
            curr_second = checkTime(curr_second);
            var $curDays =  "<?php Print($curDays); ?>";
            document.getElementById('clock').innerHTML = curr_hour + ":" + curr_minute + ":" + curr_second + " " + a_p;
            }

        function checkTime(i) {
            if (i < 10) {
                i = "0" + i;
            }
            return i;
        }
        setInterval(showTime, 500);
        //-->
        </script>
    </td>
    <td></td>
    </tr>
  	<tr>
  		<td style="width:20%;">
  			MUTU GADING TEKSTIL
  		</td>
      <td align="center">
        <?php echo $curDays; ?>
      </td>
  		<td style="width:20%;text-align:right">
  			<?php
          //echo "test ".$GLOBALS['USER_NAME'];
         $usrname = "";
         if (isset($_SESSION['username'])) {
          $usrname = $_SESSION['username'];
         } else $usrname = "";

         $USER_NAME = $usrname;
          if ($usrname!==""){
            echo "User : ".$usrname;
          }else {
            echo date("l")." ".date("Y/m/d");
          }
        ?>
  		</td>
  	</tr>
    <tr>
      <td>
<?php
  if ($MenuUtamaHdr == "Y"){?>
  <span style="font-size:10px;cursor:pointer" onclick="openNav()"><font color='red'>&#9776;Main Menu</font></span>
<?php } ?>
      </td>
      <td style="text-align:center" style="font-size:10px">
        <font color='white'>
          <?php
            if ($MENU_NAME !== "MAIN MENU"){
              echo "Menu : $MENU_NAME";
            }
          ?></font>
      </td>
      <td style="text-align:right">
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
</div>
<script type="text/javascript">
/* When the user clicks on the button,
toggle between hiding and showing the dropdown content */
function myFunction() {
  document.getElementById("myDropdown").classList.toggle("show");
}

function myPrint() {
  document.getElementById("PrintMenu").classList.toggle("show");
}

// Close the dropdown if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {
    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}
</script>
