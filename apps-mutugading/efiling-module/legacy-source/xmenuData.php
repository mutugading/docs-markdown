<div id="mySidenav" class="sidenav" align="left">
  <a href="javascript:void(0)" class="closebtn" onclick="closeNav()">
    <font size='2' color='#f3215f'>
      Hide Main Menu
    </font>
  </a>
<?php
    //Main Menu
    $isUsrAdmin = isUserAdmin($conn,$USER_NAME);
    $whrMenuActive = "";
    if ($isUsrAdmin==="N"){
      $whrMenuActive = " and STS_ACTIVE = 1 ";
    }
    $sqlMenuData = "select MENU_ID, MENU_NAME, MENU_TYPE, FILE_NAME from mst_menus t
            where t.APPLICATION_MENUS = 'MGTAPPS_WEB_MENUS'
            and MENU_ID <> '2000000000'
            and menu_id_parent = '2000000000' $whrMenuActive
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
    // if ($USER_NAME === "SINTIA" || $USER_NAME === "1949" )        {
    //      echo $sqlMenuData."</br></br>";
    // }
    $MENU_ID="";
    $MENU_NAME="";
    $MENU_TYPE="";
    $FILE_NAME="";
    $menu =null;

    $rs = oci_parse($conn, $sqlMenuData);
    oci_execute($rs);

    while (oci_fetch($rs)) {
      $MENU_ID    = oci_result($rs, 'MENU_ID');
      $MENU_NAME  = oci_result($rs, 'MENU_NAME');
      $MENU_TYPE  = oci_result($rs, 'MENU_TYPE');
      $FILE_NAME  = oci_result($rs, 'FILE_NAME');
      //header button
      $menu = $menu."<button class='dropdown-btn'>".$MENU_NAME."<i class='fa fa-caret-down'></i></button>";
        //dtl button
        $sqlMenuDataDtl =
              "select MENU_ID, MENU_NAME, MENU_TYPE, FILE_NAME ,IN_ROOT_FOLDER
               from mst_menus t
                  where t.APPLICATION_MENUS = 'MGTAPPS_WEB_MENUS' $whrMenuActive
                  and menu_id_parent = '".$MENU_ID."'
                  and exists (
                select MENU_ID
                from (
                  select MENU_ID
                  from mst_group_menus gm
                      ,mst_group_users gu
                  where gm.GROUPS_ID = gu.GROUPS_ID
                  and gu.USER_ID = '$USER_NAME'
                  UNION ALL
                  select menu_id
                  from MST_USER_MENUS mum
                  where mum.USER_ID  = '$USER_NAME'
                ) gm
                where t.MENU_ID = gm.MENU_ID
                  ) order by seq_no";
           $conn1 = $conn;
           // if ($USER_NAME === "SINTIA" || $USER_NAME === "1949" )        {
           //    echo $sqlMenuDataDtl."</br></br>";
           // }
           $stid = oci_parse($conn1,$sqlMenuDataDtl);
           oci_execute($stid, OCI_DEFAULT);
           $MENU_ID_dtl="";
           $MENU_NAME_dtl="";
           $MENU_TYPE_dtl="";
           $FILE_NAME_dtl="";
           $menu_dtl = "<div class='dropdown-container'>";

            while (($row = oci_fetch_array($stid, OCI_BOTH)) != false) {
              // Use the uppercase column names for the associative array indices
              $MENU_ID_dtl    =""; if(!empty($row['MENU_ID'])){ $MENU_ID_dtl=$row['MENU_ID']; }
              $MENU_NAME_dtl  =""; if(!empty($row['MENU_NAME'])){ $MENU_NAME_dtl=$row['MENU_NAME']; }
              $MENU_TYPE_DTL  =""; if(!empty($row['MENU_TYPE'])){ $MENU_TYPE_DTL=$row['MENU_TYPE']; }
              $FILE_NAME_dtl  =""; if(!empty($row['FILE_NAME'])){ $FILE_NAME_dtl=$row['FILE_NAME']; }
              $IN_ROOT_FOLDER =""; if(!empty($row['IN_ROOT_FOLDER'])){ $IN_ROOT_FOLDER=$row['IN_ROOT_FOLDER']; }

              if ($IN_ROOT_FOLDER==="N"){
                  echo $FILE_NAME_dtl = str_replace("COSTING_FILE\\","",strtoupper($FILE_NAME_dtl));
                  $menu_dtl = $menu_dtl."<a href='".$FILE_NAME_dtl."?P_MENU_ID=$MENU_ID_dtl' >".$MENU_NAME_dtl."</span></span></a>";
              } else {
                  $menu_dtl = $menu_dtl."<a href='https://mgtapps.mutugading.com:4433/webapps/".$FILE_NAME_dtl."?P_MENU_ID=$MENU_ID_dtl' >".$MENU_NAME_dtl."</span></span></a>";
              }
            }
            $menu_dtl = $menu_dtl."</div>";
            //echo "Menu Dtl ".$menu_dtl;
            $menu=$menu.$menu_dtl;
    }
    ECHO $menu."<a href='logout' title='Logout'><font size='2' color='#f3215f'>Logout</font></a>";
  ?>
</div>
