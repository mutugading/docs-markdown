<?php
  include("../conOraOci.php"); include ("../common_function.php");
  $FORM_NAME = "";	if(isset($_GET['FORM_NAME'])){$FORM_NAME=$_GET['FORM_NAME'];}
  $P_MENU_ID = "";	if(isset($_GET['P_MENU_ID'])){$P_MENU_ID=$_GET['P_MENU_ID'];}
  $USER_NAME = "";	if(isset($_GET['USER_NAME'])){$USER_NAME=$_GET['USER_NAME'];}
  $P_PAGE_NO = "";	if(isset($_GET['P_PAGE_NO'])){$P_PAGE_NO=$_GET['P_PAGE_NO'];} if($P_PAGE_NO===""){$P_PAGE_NO="0";}
  $TOT_ROWS = "";	if(isset($_GET['TOT_ROWS'])){$TOT_ROWS=$_GET['TOT_ROWS'];} //if($P_PAGE_NO===""){$P_PAGE_NO="0";}

  $DATE_FROM="";	if(isset($_GET['DATE_FROM'])){$DATE_FROM=$_GET['DATE_FROM'];}
  $DATE_TO="";	if(isset($_GET['DATE_TO'])){$DATE_TO=$_GET['DATE_TO'];}
  $FILTER_BY="";	if(isset($_GET['FILTER_BY'])){$FILTER_BY=$_GET['FILTER_BY'];}
  $FILTER_VAL="";	if(isset($_GET['FILTER_VAL'])){$FILTER_VAL=$_GET['FILTER_VAL'];}

  $REC_INFOCUS="";	if(isset($_GET['REC_INFOCUS'])){$REC_INFOCUS=$_GET['REC_INFOCUS'];}

  include ($FORM_NAME."_SQL.php"); $font="12";

  //echo "DATE_FROM $DATE_FROM : DATE_TO $DATE_TO <br>";
  //echo "FILTER_BY $FILTER_BY : FILTER_VAL $FILTER_VAL <br>";

  $sqlDt = getSqlShow($DATE_FROM,$DATE_TO);

  $whereSql = " where 1=1 ";

  if ($FILTER_BY!=="" && $FILTER_VAL!==""){
    if ($FILTER_BY === "Trx No"){
      $whereSql = " $whereSql and upper(TPB_TRX_NO) like '%'||upper('$FILTER_VAL')||'%' ";
    }
    if ($FILTER_BY === "Date (DD-MM-YYYY)"){
      $whereSql = " $whereSql and to_char(TPB_DT,'DD-MM-YYYY') like '%'||upper('$FILTER_VAL')||'%' ";
    }
    if ($FILTER_BY === "Supplier"){
      $whereSql = " $whereSql and upper(TPB_SUPP_CODE||TPB_SUPP_NAME) like '%'||upper('$FILTER_VAL')||'%' ";
    }
    if ($FILTER_BY === "Bill No"){
      $whereSql = " $whereSql and upper(TPB_BILL_NO) like '%'||upper('$FILTER_VAL')||'%' ";
    }
    if ($FILTER_BY === "Bill Date (DD-MM-YYYY)"){
      $whereSql = " $whereSql and to_char(TPB_BILL_DT,'DD-MM-YYYY') like '%'||upper('$FILTER_VAL')||'%' ";
    }

    if ($FILTER_BY === "LDN No"){
        $whereSql =
        " where tpb_trx_no in
                (
                SELECT   distinct tpb_trx_no
                FROM 	mgtdat.mgt_tp_provision_bill,
                        mgtdat.mgt_tp_provision,
                        (

                        SELECT mth_txn_code || '-' || mth_transp_no tpdn_no, mth_no,
                                mth_dt, mth_transp_name, mth_truck_type,
                                mth_truck_cap, mth_destination, mth_no_police,
                                mth_driver, mtdd_dn_txn_code,
                                mtdd_dn_txn_code || '-' || mtdd_dn_no dn_no,
                                mtdd_dn_qty, SUBSTR (mtdd_dn_no, 1, 4) yy
                        FROM mgtdat.mgt_transp_head, mgtdat.mgt_transp_detail_dn
                        WHERE mth_sys_id = mtdd_mth_sys_id
                        )
                WHERE tpb_dt BETWEEN TO_DATE ('$DATE_FROM', 'YYYY-MM-DD')
                AND TO_DATE ('$DATE_TO', 'YYYY-MM-DD')
                AND tpb_sys_id = tp_tpb_sys_id
                AND tp_no = tpdn_no(+)
                AND  dn_no like '%'||upper('$FILTER_VAL')||'%'
                      )
                    ";
    }

    $sqlDt = "select * from ($sqlDt) $whereSql ";
  }

  //echo "$sqlDt<br>";die();

  $sqlDt = "select  rownum REC_NO
                    ,TPB_TRX_NO
                    ,to_char(TPB_DT,'DD-MM-YYYY') TPB_DT
                    ,TPB_SUPP_CODE,TPB_SUPP_NAME,TPB_BILL_NO,
                    to_char(TPB_BILL_DT,'DD-MM-YYYY') TPB_BILL_DT
            from (  select distinct TPB_TRX_NO,TPB_DT,TPB_SUPP_CODE,TPB_SUPP_NAME,TPB_BILL_NO,TPB_BILL_DT
                    from ($sqlDt) a
                  ) a
            ";

  //echo "$sqlDt<br>";
  $P_ROWS_DISP = $TOT_ROWS;
  if ($P_PAGE_NO=="1"){
    $P_ROW_START  = 1;
    $P_ROW_END    = $P_ROWS_DISP;
  } else {
      $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
      $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
  }
  //echo "P_ROW_START $P_ROW_START : P_ROW_END $P_ROW_END : AREA_PRM $AREA_PRM <br>";
  $sqlCnt = "select count(-1) TOT_DATA from ($sqlDt) a";
  //echo "sqlCnt $sqlCnt<br>";
  $TOT_PAGE = getTotPage($conn,$sqlCnt,$TOT_ROWS);
  //echo "TOT_ROWS $TOT_ROWS : TOT_PAGE $TOT_PAGE<br>";
  $font = "12";
?>
  <input type="hidden" name="TOT_PAGE" id="TOT_PAGE" value="<?php echo $TOT_PAGE;  ?>">
<?php
  $sqlDt = "select * from ($sqlDt) a where REC_NO between $P_ROW_START and $P_ROW_END";
?>
<div id="DIV_SQL" class="main" style="overflow-x:auto;overflow-y:auto;height:0px;visibility:hidden" align="center">
  <?php echo $sqlDt; ?>
</div>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="width:80%" border="1">
    <tr>
      <td <?php echo getStyle($font,"center","5"); ?> >No</td>
      <td <?php echo getStyle($font,"center","15"); ?> >Trx No</td>
      <td <?php echo getStyle($font,"center","10"); ?> >Date</td>
      <td <?php echo getStyle($font,"center"); ?> >Supplier</td>
      <td <?php echo getStyle($font,"center","20"); ?> >BILL No</td>
      <td <?php echo getStyle($font,"center"); ?> >BILL Date</td>
      <td <?php echo getStyle($font,"center"); ?>>Detail</td>
</tr>
  <?php
    //echo "$sqlDt<br>";
    $rsDt = oci_parse($conn,$sqlDt);
    oci_execute ($rsDt);
    while ($rowDt = oci_fetch_array ($rsDt, OCI_BOTH)) {
      $REC_NO=""; if(!empty($rowDt['REC_NO'])){$REC_NO=$rowDt['REC_NO'];}//1
      $TPB_TRX_NO=""; if(!empty($rowDt['TPB_TRX_NO'])){$TPB_TRX_NO=$rowDt['TPB_TRX_NO'];}//2
      $TPB_DT=""; if(!empty($rowDt['TPB_DT'])){$TPB_DT=$rowDt['TPB_DT'];}//3
      $TPB_SUPP_CODE=""; if(!empty($rowDt['TPB_SUPP_CODE'])){$TPB_SUPP_CODE=$rowDt['TPB_SUPP_CODE'];}//4
      $TPB_SUPP_NAME=""; if(!empty($rowDt['TPB_SUPP_NAME'])){$TPB_SUPP_NAME=$rowDt['TPB_SUPP_NAME'];}//4
      $TPB_BILL_NO=""; if(!empty($rowDt['TPB_BILL_NO'])){$TPB_BILL_NO=$rowDt['TPB_BILL_NO'];}//5
      $TPB_BILL_DT=""; if(!empty($rowDt['TPB_BILL_DT'])){$TPB_BILL_DT=$rowDt['TPB_BILL_DT'];}//6

      $viewDtl 	= "showLov('VIEW_DETIL','$TPB_TRX_NO','$REC_NO')";
?>
<tr>
		<td <?php echo getStyle($font,"right"); ?>>
      <input type="text" id="<?php echo $REC_NO;?>" value="<?php echo $REC_NO; ?>" style="width:50px;" readonly >
    </td>
		<td <?php echo getStyle($font,"left"); ?>> &nbsp <?php echo $TPB_TRX_NO; ?></td>
		<td <?php echo getStyle($font,"center"); ?>> <?php echo $TPB_DT; ?> </td>
		<td <?php echo getStyle($font,"left"); ?>> &nbsp <?php echo $TPB_SUPP_CODE."-".$TPB_SUPP_NAME; ?></td>
		<td <?php echo getStyle($font,"left"); ?>> &nbsp <?php echo $TPB_BILL_NO; ?> </td>
		<td <?php echo getStyle($font,"center"); ?>> <?php echo $TPB_BILL_DT; ?> </td>
		<td <?php echo getStyle($font,"center"); ?>>
      <button type="button" data-toggle="modal" data-target="#myModal" onclick="<?php echo $viewDtl; ?>" >
        ...
      </button>
		</td>
	</tr>
  <?php
    }
   ?>
  </table>
</div>
