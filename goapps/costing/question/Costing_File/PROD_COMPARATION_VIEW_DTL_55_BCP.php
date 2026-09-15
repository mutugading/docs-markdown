<?php
  $widthSttg = "width:100%";//"width:$widthTbl%";

  $IS_RM_55 = getData($conn
                       ,"select 'Y' GET_DATA from mgtapps.CST_LVL_LEFT_PROD
                         where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID'
                         and rownum = 1
                         ");

  $deepMaterial =  getData( $conn
                            ,"select Count(-1) GET_DATA from mgtapps.CST_LVL_LEFT_PROD
                              where CLLP_CYL_SYS_ID_REFF = '$P_CYL_SYS_ID'
                              "
                          );

  $widthTbl = ($deepMaterial+1) * 30;
  $widthSttg = "width:$widthTbl%";
  $bgClr = "#e6e1e1";

  $sqlDesc =
    "SELECT CYCRL_SEQ_NO,CYCRL_DESCRIPTION, NVL (CYCRL_JUSTIFY, 'L') CYCRL_JUSTIFY,
            NVL (CYCRL_FORMAT_DATA, 'NULL') CYCRL_FORMAT_DATA,
            CYCRL_IS_MANDATORY, NVL (CYCRL_IS_BOLD, 'N') CYCRL_IS_BOLD,
            NVL (CYCRL_LENGTH_DECIMAL, 0) CYCRL_LENGTH_DECIMAL,
            NVL (CYCRL_TEXT_COLOUR, 'NULL') CYCRL_TEXT_COLOUR
            ,nvl(CYCRL_SOURCE_QUERY,'NULL') CYCRL_SOURCE_QUERY
            ,nvl(CYCRL_SOURCE_TYPE,'NULL') CYCRL_SOURCE_TYPE
            ,DECODE(CYCRL_JUSTIFY,'L','LEFT','R','RIGHT','C','CENTER','LEFT') CYCRL_JUSTIFY
            ,CYCRL_IS_BOLD,CYCRL_DISP_IN_FINAL_PRODUCT,CYCRL_HIDE_SEQ_NO
        FROM mgtapps.cst_yarn_calc_rpt_lable b
        WHERE b.cycrl_cycrm_sys_id = '$P_CYCRM_SYS_ID'
        order by to_number(cycrl_seq_no) ";
?>
  <div class="main" style="overflow-x:auto;overflow-y:auto;" align="left" >
  <table style="<?php echo $widthSttg; ?>" border="1">

<?php
      $rs1Data = oci_parse($conn,$sqlDesc);
      oci_execute ($rs1Data);
      $totRows = 0;
      $totData = 0;
      while ($row1Data = oci_fetch_array ($rs1Data, OCI_BOTH)) {
?>
    <tr>
    <!-- Description -->
        <td style="width:20%;font-size:<?php echo $font; ?>px;">
        <?php
          isBold($row1Data['CYCRL_IS_BOLD'],1);
          echo printDescription(
                                $row1Data['CYCRL_SEQ_NO']//$CYCRL_SEQ_NO
                                ,$row1Data['CYCRL_HIDE_SEQ_NO']//$CYCRL_HIDE_SEQ_NO
                                ,$row1Data['CYCRL_DESCRIPTION']//$CYCRL_DESCRIPTION
                                );

          isBold($row1Data['CYCRL_IS_BOLD'],2);

        ?>
        </td>
    <!-- Description -->
    <!-- Product -->
<?php
    $CYCRL_JUSTIFY = !empty($row1Data['CYCRL_JUSTIFY'])? $row1Data['CYCRL_JUSTIFY']: "LEFT";
    $CYCRL_SOURCE_QUERY = !empty($row1Data['CYCRL_SOURCE_QUERY'])?$row1Data['CYCRL_SOURCE_QUERY']: "";

    $lpDt = 0; $CylSysId_dt =  "";
    while ($deepMaterial > $lpDt) {
      $lpDt++;
      if($IS_RM_55==="Y"){
        $sqlData= "
                   select *
                   from (
                         SELECT ROWNUM REC_NO,a.*
                         FROM (SELECT b.*,c.*
                               FROM cst_lvl_left_prod a
                                    ,cst_yarn_left b
                                    ,cst_yarn_calculation_cur c
                               WHERE cllp_cyl_sys_id_reff = '$P_CYL_SYS_ID'
                               and a.CLLP_CYL_SYS_ID = b.cyl_sys_id
                               and b.CYL_SYS_ID=CYCC_CYL_SYS_ID
                               ORDER BY cllp_seq_lvl DESC
                               ) a
                        )
                   where REC_NO=$lpDt
                  ";

        $sqlGet = "SELECT CYL_SYS_ID GET_DATA
                   FROM ($sqlData) a
                  ";
      }
      $CylSysId_dt =  getData($conn,$sqlGet);
?>
      <td style="width:20%;font-size:<?php echo $font; ?>px;" align="<?php echo $CYCRL_JUSTIFY; ?>" >
<?php
      // echo 	"P_CYL_SYS_ID $P_CYL_SYS_ID<br>";
      // <br>Query : ".$row1Data['CYCRL_SOURCE_QUERY']
      // 			."<br>Type : ".$row1Data['CYCRL_SOURCE_TYPE']."<br>";
      // echo "P_CYL_SYS_ID $P_CYL_SYS_ID : CylSysId_dt $CylSysId_dt : lpDt $lpDt ";
      $dtVal = getDtVal(	$conn
                ,$CylSysId_dt
                ,$sqlData
                ,$row1Data['CYCRL_SOURCE_TYPE']
                ,$CYCRL_SOURCE_QUERY//$row1Data['CYCRL_SOURCE_QUERY']
                ,$row1Data['CYCRL_FORMAT_DATA']
                ,$row1Data['CYCRL_LENGTH_DECIMAL']
                ,$lpDt
                ,""//$P_CYL_SYS_ID_DTL
              );
      //echo "$CylSysId_dt </br>";
      isBold($row1Data['CYCRL_IS_BOLD'],1);
      if ($row1Data['CYCRL_DISP_IN_FINAL_PRODUCT']==="Y"){
        if($deepMaterial === $lpDt){
          echo $dtVal;//print value
        }
      } else {
        echo $dtVal;//print value
      }
      isBold($row1Data['CYCRL_IS_BOLD'],2);
?>
      </td>
<?php
    }
?>
    <!-- Product -->
    </tr>
<?php
      }
?>
  </table>
  </div>
