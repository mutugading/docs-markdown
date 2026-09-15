<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');
include("conOraOci.php");
include ("check_login.php");
include ("common_function.php");
include ("FORM_NAME.php");
include ("CST_FIND_PRODUCT_SQL.php");


$CYL_TYPE_S = "NULL";
if(isset($_GET['CYL_TYPE_S'])) {
  $CYL_TYPE_S=$_GET['CYL_TYPE_S'];
}

$CYL_SHADE_CODE_S = "NULL";
if(isset($_GET['CYL_SHADE_CODE_S'])) {
  $CYL_SHADE_CODE_S=$_GET['CYL_SHADE_CODE_S'];
}
$CYL_SHADE_NAME_S = "NULL";
if(isset($_GET['CYL_SHADE_NAME_S'])) {
  $CYL_SHADE_NAME_S=$_GET['CYL_SHADE_NAME_S'];
}

$CMY_LUSTURE_S = "NULL";
if(isset($_GET['CMY_LUSTURE_S'])) {
  $CMY_LUSTURE_S=$_GET['CMY_LUSTURE_S'];
}

$DENIER_S = "NULL";
if(isset($_GET['DENIER_S'])) {
  $DENIER_S=$_GET['DENIER_S'];
}

$FILAMENT_S = "NULL";
if(isset($_GET['FILAMENT_S'])) {
  $FILAMENT_S=$_GET['FILAMENT_S'];
}

$INTERMINGLING_S = "NULL";
if(isset($_GET['INTERMINGLING_S'])) {
  $INTERMINGLING_S=$_GET['INTERMINGLING_S'];
}

$CROSS_SECTION_S = "NULL";
if(isset($_GET['CROSS_SECTION_S'])) {
  $CROSS_SECTION_S=$_GET['CROSS_SECTION_S'];
}

$HEATSET_S = "NULL";
if(isset($_GET['HEATSET_S'])) {
  $HEATSET_S=$_GET['HEATSET_S'];
}

$CUSTOMER_S = "NULL";
if(isset($_GET['CUSTOMER_S'])) {
  $CUSTOMER_S=$_GET['CUSTOMER_S'];
}


$SqlData = "select MGTAPPS.pkg_yarn_marketing.fGetDeepMaterial($P_CYL_SYS_ID_DTL) GET_DATA from dual ";

$SqlView = "SELECT  CMY_NAME
            ,CYCC_TOP_13_DATA_VALUE DENIER
            ,CYCC_TOP_16_DATA_VALUE FILAMENT
            ,CYCC_TOP_18_DATA_VALUE INTERMINGLING
            ,CYCC_TOP_49_DATA_VALUE HEATSET
            ,CYCC_TOP_17_DATA_VALUE CROSS_SECTION
            ,CMY_LUSTURE
            ,CYL_SYS_ID
            ,CYL_SHADE_CODE
            ,CYL_SHADE_NAME
            ,CYL_LEFT_NO
            ,CYL_TYPE
                   FROM mgtapps.cst_mst_yarn y,
                        mgtapps.cst_yarn_left l,
                        mgtapps.cst_yarn_calculation_cur cc
                  WHERE cycc_cyl_sys_id = cyl_sys_id
                    AND cyl_cmy_sys_id = cmy_sys_id
                    AND cyl_prs_type = cycc_prs_type
                    AND cyl_prs_type = mgtapps.pkg_yarn_calculation.fprsidmkt AND CYL_IS_VALID_PRD = 'Y' ";

  $SqlViewWhere = "";
  if ( $CYL_TYPE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_TYPE = '$CYL_TYPE_S' ";
  }
  if ( $CYL_SHADE_CODE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_CODE = '$CYL_SHADE_CODE_S' ";
  }
  if ( $CYL_SHADE_NAME_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SHADE_NAME = '$CYL_SHADE_NAME_S' ";
  }

  if ( $CMY_LUSTURE_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CMY_LUSTURE = '$CMY_LUSTURE_S' ";
  }

  if ( $DENIER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_13_DATA_VALUE = '$DENIER_S' ";
  }

  if ( $FILAMENT_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_16_DATA_VALUE = '$FILAMENT_S' ";
  }

  if ( $INTERMINGLING_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_18_DATA_VALUE = '$INTERMINGLING_S' ";
  }

  if ( $CROSS_SECTION_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_17_DATA_VALUE = '$CROSS_SECTION_S' ";
  }

  if ( $HEATSET_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYCC_TOP_49_DATA_VALUE = '$HEATSET_S' ";
  }


  if ( $CUSTOMER_S !=="NULL"){
    $SqlViewWhere = "$SqlViewWhere and CYL_SYS_ID in ( select distinct CYL_SYS_ID  from mgtapps.CST_YARN_LEFT_CUST lc,mgtapps.CST_YARN_LEFT l,mgtapps.CST_MST_CUST_DATA cd
          where lc.CYLC_CYL_SYS_ID = l.CYL_SYS_ID and lc.CYLC_CMCD_SYS_ID = CMCD_SYS_ID
          and upper(CMCD_NAME) like '%$CUSTOMER_S%' )";
  }

?>

<html>
<head>
</head>
<body>
    <style type="text/css">
    body{
        font-family: sans-serif;
    }
    table{
        margin: 20px auto;
        border-collapse: collapse;
    }
    table th,
    table td{
        border: 1px solid #3c3c3c;
        padding: 3px 8px;

    }
    a{
        background: blue;
        color: #fff;
        padding: 8px 10px;
        text-decoration: none;
        border-radius: 2px;
    }
    </style>
<?php
$excelNm = "Find_Product_View.Xls";
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$excelNm");
echo "<table>";
echo "<tr>";
echo "<td>Report : Product by Customer $CUSTOMER_S </td>";
echo "</tr>";
echo "</table>";
?>
<table>";
  <tr>
    <td>
      1)<br>No
    </td>
    <td>
      2)<br>Type
    </td>
    <td>
      3)<br>Shade Name
    </td>
    <td>
      4)<br>Name
    </td>
    <td>
      5)<br>M/C 
    </td>
    <td>
      6)<br>Eff
    </td>
    <td>
      7)<br>Speed
    </td>
    <td>
      8)<br>V1<1.5
    </td>
    <td>
      9)<br>V2=>1.5<3
    </td>
    <td>
      10)<br>V3=>3<6
    </td>
    <td>
      11)<br>V4=>6<12
    </td>
    <td>
      12)<br>V5<12
    </td>
    <td>
      13)<br>Denier
    </td>
    <td>
      14)<br>Filament
    </td>
    <td>
      15)<br>Intermingling
    </td>
    <td>
      16)<br>Heatset
    </td>
    <td>
      17)<br>Cross Section
    </td>
    <td>
      18)<br>Lusture
    </td>
    <td>
      19)<br>MB Name
    </td>
    <td>
      20)<br>Chip
    </td>
    <td>
      21)<br>Packing type
    </td>
    <td>
      22)<br>Bobbin weight
    </td>
    <td>
      23)<br>no of Bobbins
    </td>
    <td>
      24)<br>Chip Rate
    </td>
    <td>
      25)<br>Dozing
    </td>
    <td>
      26)<br>MB Rate
    </td>
    <td>
      27)<br>MB cost
    </td>
    <td>
      28)<br>Change Over Loss
    </td>
    <td>
      29)<br>Quality Loss
    </td>
    <td>
      30)<br>Intermigle Cost
    </td>
    <td>
      31)<br>Fixed Cost
    </td>
    <td>
      32)<br>Del-Pack cost
    </td>
    <td>
      33)<br>Final Ex Factory Cost
    </td>
    <td>
      (34)<br>Fowarding
    </td>
  </tr>
<?php


  $SqlView = "select ROWNUM REC_NO,d.* from ($SqlView $SqlViewWhere order by CMY_NAME ) d ";
  $SqlView = "$sqlSelect from ($SqlView) d ";

  //echo "<tr><td>$SqlView</td></tr>";
  $rsView = oci_parse($conn,$SqlView);
  oci_execute ($rsView);
  while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
    //$btnDtl = "Process('DETAIL ".$rowRsView['CYL_SYS_ID']."')";
?>
<tr>
    <td>
      <?php echo $rowRsView['REC_NO']; ?> <!-- 1 no -->
    </td>
    <td>
      <?php echo $rowRsView['CYL_TYPE']; ?><!--2 Type-->
    </td>
    <td>
      <?php echo $rowRsView['CYL_SHADE_NAME']; ?><!--3 Shade Name-->
    </td>
    <td>
      <?php echo $rowRsView['CMY_NAME']; ?><!--4 Name-->
    </td>
    <td>
      <?php echo $rowRsView['CMY_NAME']; ?><!--5 M/C -->
    </td>
    <td>
        <?php if (!empty($rowRsView['FGET_MCEFF'])){ echo $rowRsView['FGET_MCEFF']; } ?><!---6 Eff -->
    </td>
    <td>
        <?php if (!empty($rowRsView['FGET_MCSPEED'])){ echo $rowRsView['FGET_MCSPEED']; } ?><!--7Speed-->
    </td>
    <td>
        <?php if (!empty($rowRsView['FGET_V1'])){echo setNumber($conn,"Number",$rowRsView['FGET_V1'],2);}?> <!---8 V1-->
    </td>
    <td>
        <?php if (!empty($rowRsView['FGET_V2'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V2'],2);}?><!--9 V2=>1.5<3-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_V3'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V3'],2);  }?><!--10 V3=>3<6-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_V4'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V4'],4);  }?><!--11<br>V4=>6<12-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_V5'])){ echo setNumber($conn,"Number",$rowRsView['FGET_V5'],2);  }?><!--12<br>V5<12-->
    </td>
    <td>
      <?php if (!empty($rowRsView['DENIER'])){echo $rowRsView['DENIER']; }?><!--13 Denier-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FILAMENT'])){echo $rowRsView['FILAMENT'];}?><!--14 Filament-->
    </td>
    <td>
      <?php if (!empty($rowRsView['INTERMINGLING'])){echo $rowRsView['INTERMINGLING'];}?><!--15 Intermingling-->
    </td>
    <td>
      <?php if (!empty($rowRsView['HEATSET'])){ echo $rowRsView['HEATSET'];}?><!--16 Heatset-->
    </td>
    <td>
      <?php if (!empty($rowRsView['CROSS_SECTION'])){ echo $rowRsView['CROSS_SECTION']; }?><!--17 Cross-->
    </td>
    <td>
      <?php if (!empty($rowRsView['CMY_LUSTURE'])){ echo $rowRsView['CMY_LUSTURE'];}?> <!---18 Lusture-->
    </td>
    <td>
      <?php
        if (!empty($rowRsView['FGETMBNAME'])){
          echo $rowRsView['FGETMBNAME'];
        } else {
					$sqlMbNmRaw = "select MGTAPPS.pkg_yarn_marketing.fgetmbname_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$MbNmRaw =  getData($conn,$sqlMbNmRaw);
					echo $MbNmRaw;
				}
      ?> <!----19 MB Name-->
    </td>
    <td>
      <?php
        if (!empty($rowRsView['FGETCHIP'])){
          echo $rowRsView['FGETCHIP'];
        } else {
					$sqlDtRm= "select MGTAPPS.pkg_yarn_marketing.fgetchip_rm('".$rowRsView['CYL_SYS_ID']."') GET_DATA from  dual";
					$DtRm =  getData($conn,$sqlDtRm);
					echo $DtRm;
				}
      ?>
      <!--20 Chip-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_PACKINGTYPE'])){ echo $rowRsView['FGET_PACKINGTYPE'];  }?><!--21 Packing type-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_BOBBINWEIGHTAX'])){ echo $rowRsView['FGET_BOBBINWEIGHTAX'];}?><!--22<br>Bobbin weight-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_NOOFBOBBINS'])){ echo $rowRsView['FGET_NOOFBOBBINS'];  }?><!--23 no of Bobbins-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETCHIPRATE'])){ echo setNumber($conn,"Number",$rowRsView['FGETCHIPRATE'],2);}?><!--24<br>Chip Rate -->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETRPDOZ'])){ echo setNumber($conn,"Number",$rowRsView['FGETRPDOZ'],2); }?><!--25 Dozing-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETMBRATE'])){ echo setNumber($conn,"Number",$rowRsView['FGETMBRATE'],3);}?><!--26 MB Rate-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETMBCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGETMBCOST'],3);}?><!--27 MB cost-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETCNGOVRLST'])){ echo setNumber($conn,"Number",$rowRsView['FGETCNGOVRLST'],3);}?><!--28<br>Change Over Loss-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETQUALITYLOSS'])){ echo setNumber($conn,"Number",$rowRsView['FGETQUALITYLOSS'],3);  }?>
      <!--29<br>Quality Loss-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_INTERMIGLECOST'])){echo setNumber($conn,"Number",$rowRsView['FGET_INTERMIGLECOST'],3);}?>
      <!--30<br>Intermigle Cost-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_FIXEDCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_FIXEDCOST'],3);  }?>
      <!--31<br>Fixed Cost-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGET_DELPACKINGCOST'])){ echo setNumber($conn,"Number",$rowRsView['FGET_DELPACKINGCOST'],3);}?>
      <!--32<br>Del-Pack cost-->
    </td>
    <td>
      <?php if (!empty($rowRsView['FGETFINALEXFACTORYCOST'])){echo setNumber($conn,"Number",$rowRsView['FGETFINALEXFACTORYCOST'],3);
      }?>
      <!--33<br>Final Ex Factory Cost-->
    </td>
    <td>
      <?php echo $CST_PRODUCT_FOWARDING; ?><!--34<br>Fowarding-->
    </td>
  </tr>

<?php
}
?>
</table>
</body>
</html>
