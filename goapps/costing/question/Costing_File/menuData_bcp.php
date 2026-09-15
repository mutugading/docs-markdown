<!--
<button class='dropdown-btn'>Master<i class='fa fa-caret-down'></i></button> **Button Menu**
  <div class='dropdown-container'></div> **Dropdown Button Menu**
-->
<div id="mySidenav" class="sidenav" align="left">
  <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">
    <font size='2' color='#f3215f'>
      Hide Main Menu
    </font>
  </a>
  <div align="center">
    <input type="text" id="searchMenu" placeholder="Find menu..." style="color: white;width: 130px;background-color: black;">
    <button type="button" onclick="searchMenu()" data-toggle="tooltip" data-placement="top" title="Expanse">&nbsp+&nbsp</button>
    <button type="button" onclick="resetDropdowns()" data-toggle="tooltip" data-placement="top" title="Collapse">&nbsp-&nbsp</button>
  </div>
<?php
  //Main Menu
  $isUsrAdmin = isUserAdmin($conn,$USER_NAME);
  $whrMenuActive = "";
  if ($isUsrAdmin==="N"){
    $whrMenuActive = " and STS_ACTIVE = 1 ";
  }

  $sqlApps = "
              select * from mgtapps.MST_APPL_MENU
              where mam_appl_menu_id<>'MGTAPPS_FORM_MENUS'
              and mam_appl_menu_id in (
              SELECT distinct APPLICATION_MENUS
                FROM  mst_menus a
                      ,(SELECT MENU_ID
                        FROM mst_group_menus gm, mst_group_users gu
                       WHERE gm.GROUPS_ID = gu.GROUPS_ID AND gu.USER_ID = '$USER_NAME'
                      UNION
                      SELECT menu_id
                        FROM MST_USER_MENUS mum
                       WHERE mum.USER_ID = '$USER_NAME') b
              where a.MENU_ID=b.MENU_ID and STS_ACTIVE=1
              )
             ";
  $rsApps = oci_parse($conn,$sqlApps);
  oci_execute ($rsApps);
  $menu="";
  while ($rowApps = oci_fetch_array ($rsApps, OCI_BOTH)) {
    $MAM_SYS_ID=""; if(!empty($rowApps['MAM_SYS_ID'])){$MAM_SYS_ID=$rowApps['MAM_SYS_ID'];}
    $MAM_APPL_MENU_ID=""; if(!empty($rowApps['MAM_APPL_MENU_ID'])){$MAM_APPL_MENU_ID=$rowApps['MAM_APPL_MENU_ID'];}
    $MAM_APPL_MENU_NAME=""; if(!empty($rowApps['MAM_APPL_MENU_NAME'])){$MAM_APPL_MENU_NAME=$rowApps['MAM_APPL_MENU_NAME'];}
    $MAM_SEQ_NO=""; if(!empty($rowApps['MAM_SEQ_NO'])){$MAM_SEQ_NO=$rowApps['MAM_SEQ_NO'];}
    $MAM_MENU_ID=""; if(!empty($rowApps['MAM_MENU_ID'])){$MAM_MENU_ID=$rowApps['MAM_MENU_ID'];}

    $menu=$menu."<button class='dropdown-btn'><font color='#A9E2F3'>$MAM_APPL_MENU_NAME</font><i class='fa fa-caret-down'></i></button><div class='dropdown-container'>";
    $sqlMenu = "select MENU_ID, MENU_NAME, MENU_TYPE, FILE_NAME from mst_menus t
                    where t.APPLICATION_MENUS = '$MAM_APPL_MENU_ID'
                    and MENU_ID <> '$MAM_MENU_ID'
                    and menu_id_parent = '$MAM_MENU_ID' $whrMenuActive
                    and t.STS_ACTIVE=1
                    and exists (
                        select MENU_ID
                        from (
                          select MENU_ID
                          from mst_group_menus gm
                              ,mst_group_users gu
                          where gm.GROUPS_ID = gu.GROUPS_ID
                          and gu.USER_ID = '$USER_NAME'
                          UNION
                          select menu_id
                          from MST_USER_MENUS mum
                          where mum.USER_ID  = '$USER_NAME'
                        ) gm
                        where t.MENU_ID = gm.MENU_ID
                    ) order by seq_no";
    $menu_dtl = "";
    $rsMenu = oci_parse($conn, $sqlMenu);
    oci_execute($rsMenu);
    while ($rowMenus = oci_fetch_array ($rsMenu, OCI_BOTH)) {
      $MENU_ID=""; if(!empty($rowMenus['MENU_ID'])){$MENU_ID=$rowMenus['MENU_ID'];}
      $MENU_NAME=""; if(!empty($rowMenus['MENU_NAME'])){$MENU_NAME=$rowMenus['MENU_NAME'];}
      $MENU_TYPE=""; if(!empty($rowMenus['MENU_TYPE'])){$MENU_TYPE=$rowMenus['MENU_TYPE'];}
      $FILE_NAME=""; if(!empty($rowMenus['FILE_NAME'])){$FILE_NAME=$rowMenus['FILE_NAME'];}
      $menu=$menu."<button class='dropdown-btn'>$MENU_NAME<i class='fa fa-caret-down'></i></button><div class='dropdown-container'>";

      //loop menu detail
      $menu_dtl = "";
      $sqlMenuDtl =
            "select MENU_ID, MENU_NAME, MENU_TYPE, FILE_NAME ,FGETFORMNAME(FILE_NAME) FILE_NAME_REAL,IN_ROOT_FOLDER
             from mst_menus t
             where t.APPLICATION_MENUS = '$MAM_APPL_MENU_ID' $whrMenuActive
             and menu_id_parent = '".$MENU_ID."'
             and t.STS_ACTIVE=1
             and exists (
              select MENU_ID from (
                select MENU_ID from mst_group_menus gm,mst_group_users gu
                where gm.GROUPS_ID = gu.GROUPS_ID
                and gu.USER_ID = '$USER_NAME'
                UNION ALL
                select menu_id
                from MST_USER_MENUS mum
                where mum.USER_ID  = '$USER_NAME'
                ) gm
              where t.MENU_ID = gm.MENU_ID
            ) order by seq_no";
      // echo "<font color='#A9E2F3'>sqlMenuDtl $sqlMenuDtl</font><br>";
      $rsMenuDtl = oci_parse($conn, $sqlMenuDtl);
      oci_execute($rsMenuDtl);
      while ($rowMenuDtl = oci_fetch_array ($rsMenuDtl, OCI_BOTH)) {
        $MENU_ID_DTL=""; if(!empty($rowMenuDtl['MENU_ID'])){$MENU_ID_DTL=$rowMenuDtl['MENU_ID'];}
        $MENU_NAME_DTL=""; if(!empty($rowMenuDtl['MENU_NAME'])){$MENU_NAME_DTL=$rowMenuDtl['MENU_NAME'];}
        $MENU_TYPE_DTL=""; if(!empty($rowMenuDtl['MENU_TYPE'])){$MENU_TYPE_DTL=$rowMenuDtl['MENU_TYPE'];}
        $FILE_NAME_DTL=""; if(!empty($rowMenuDtl['FILE_NAME'])){$FILE_NAME_DTL=$rowMenuDtl['FILE_NAME'];}
        $FILE_NAME_REAL=""; if(!empty($rowMenuDtl['FILE_NAME_REAL'])){$FILE_NAME_REAL=$rowMenuDtl['FILE_NAME_REAL'];}

        $IN_ROOT_FOLDER = ""; if(!empty($rowMenuDtl['IN_ROOT_FOLDER'])){ $IN_ROOT_FOLDER = $rowMenuDtl['IN_ROOT_FOLDER'];}

        if ($IN_ROOT_FOLDER==="N"){
            $menu_dtl = $menu_dtl."<a href='".$FILE_NAME_REAL."?P_MENU_ID=$MENU_ID_DTL' name='$MENU_ID_DTL' >".$MENU_NAME_DTL."</span></span></a>";
        } else {
            $menu_dtl = $menu_dtl."<a href='https://mgtapps.mutugading.com:4433/webapps/".$FILE_NAME_DTL."?P_MENU_ID=$MENU_ID_DTL' name='$MENU_ID_DTL' >".$MENU_NAME_DTL."</span></span></a>";
        }
        //$menu_dtl = $menu_dtl."<a href='".$FILE_NAME_DTL."?P_MENU_ID=$MENU_ID_DTL' >".$MENU_NAME_DTL."</a>";

      }
      // $menu_dtl = $menu_dtl."</div>";
      //echo "<font size='2' color='#f3215f'>MENU_NAME $MENU_NAME : $sqlMenuDtl</font><br>";
      //loop menu detail

      $menu=$menu.$menu_dtl."</div>";
    }
    //echo "<font size='2' color='#f3215f'>$MAM_APPL_MENU_ID $sqlMenu</font><br>";
    $menu=$menu."</div>";
  }
  ECHO $menu."<a href='logout' title='Logout'><font size='2' color='#f3215f'>Logout</font></a>";
  //Main Menu
?>
</div>
<script>
// Fungsi untuk pencarian menu
// Fungsi untuk pencarian menu
function searchMenu() {
  var input, filter, menuContainer, menuItems, i, txtValue;
  input = document.getElementById('searchMenu');
  filter = input.value.toUpperCase();
  menuContainer = document.getElementsByClassName('dropdown-container');

  // Reset semua dropdown sebelum pencarian baru
  resetDropdowns();

  // Loop untuk setiap menu dan sub-menu
  for (i = 0; i < menuContainer.length; i++) {
    menuItems = menuContainer[i].getElementsByTagName('a');
    var found = false;
    var foundInContainer = false;

    // Periksa item menu yang sesuai dengan filter
    for (var j = 0; j < menuItems.length; j++) {
      txtValue = menuItems[j].textContent || menuItems[j].innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        found = true;
        foundInContainer = true;
        menuItems[j].style.display = "block";  // Tampilkan item yang ditemukan
      }
      // else {
      //   menuItems[j].style.display = "none";  // Sembunyikan item yang tidak sesuai
      // }
    }

    // Menampilkan atau menyembunyikan menu berdasarkan hasil pencarian
    if (found) {
      menuContainer[i].style.display = '';  // Menampilkan container menu yang memiliki hasil
      if (foundInContainer) {
        var dropdownBtn = menuContainer[i].previousElementSibling;
        if (dropdownBtn && dropdownBtn.classList.contains('dropdown-btn')) {
          // Membuka dropdown jika ditemukan hasil pencarian
          if (dropdownBtn.getAttribute('aria-expanded') === 'false') {
            dropdownBtn.setAttribute('aria-expanded', 'true');
            menuContainer[i].style.display = 'block';  // Menampilkan dropdown
          }
        }
      }
    }
    // else {
    //   menuContainer[i].style.display = 'none'; // Menyembunyikan container menu yang tidak ada hasil
    // }
  }
}

// Fungsi untuk mereset dropdowns sebelum pencarian baru
function resetDropdowns() {
  var dropdownContainers = document.querySelectorAll('.dropdown-container');
  var dropdownBtns = document.querySelectorAll('.dropdown-btn');

  // Sembunyikan semua dropdowns
  dropdownContainers.forEach(function(container) {
    container.style.display = 'none';
  });

  // Setel semua dropdown button ke keadaan tertutup
  dropdownBtns.forEach(function(button) {
    button.setAttribute('aria-expanded', 'false');
  });
}

// Fungsi untuk toggle expand/collapse menu
function toggleDropdown(event) {
  var container = event.target.nextElementSibling;
  var isExpanded = container.style.display === "block";

  // Toggle dropdown
  alert("test");
  if (isExpanded) {
    container.style.display = "none";
    event.target.setAttribute('aria-expanded', 'false');
  } else {
    container.style.display = "block";
    event.target.setAttribute('aria-expanded', 'true');
  }
}

// Menambahkan event listener untuk tombol dropdown
document.querySelectorAll('.dropdown-btn').forEach(function (button) {
  button.addEventListener('click', function (event) {
    // Menambahkan pengecekan untuk elemen yang lebih dalam (level kedua)
    var container = event.target.nextElementSibling;
    if (container && container.classList.contains('dropdown-container')) {
      //toggleDropdown(event);
    }
  });

  button.setAttribute('aria-expanded', 'false');  // Menambahkan atribut untuk aksesibilitas
});


</script>
