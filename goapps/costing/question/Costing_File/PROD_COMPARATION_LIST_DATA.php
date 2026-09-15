<?php
  include("../conOraOci.php");

  $FIND_TYPE_S = $_GET['FIND_TYPE'] ?? "";
  $FIND_DATA_S = $_GET['FIND_DATA'] ?? "";
  $FIND_LEFT_NO_S = $_GET['FIND_LEFT_NO'] ?? "";
  $PAGE_NO_S = $_GET['PAGE_NO'] ?? "2";

  $vWhere = "";

  if ($FIND_LEFT_NO_S !== "" && $FIND_LEFT_NO_S !== "NULL") {
      $vWhere .= " and a.cyl_left_no = :left_no ";
  }

  if ($FIND_TYPE_S !== "" && $FIND_TYPE_S !== "NULL") {
      $vWhere .= " and a.cyl_type = :type ";
  }

  if ($FIND_DATA_S !== "") {
      $vWhere .= " and upper(CYL_LEFT_NO||CYL_TYPE||CYL_NAME||CYL_SYS_ID||CYL_SHADE_CODE||CYL_SHADE_NAME||CYL_ITEM_CODE)
                   like '%' || upper(:find) || '%' ";
  }

  $sqlData= "
            select ROWNUM REC_NO,a.*,COUNT(*) OVER() AS TOT_DT
            from (
                  select distinct a.*
                  from cst_yarn_left a
                  where CYL_IS_VALID_PRD='Y'
                  $vWhere
                  order by cyl_left_no
                 ) a
            ";

  if($PAGE_NO_S==="1"){
    $ROW_START=1; $ROW_END=5;
  } else {
    $ROW_START=5*($PAGE_NO_S-1); $ROW_END=5*($PAGE_NO_S);
  }

  $sql = " select * from ($sqlData) where REC_NO between $ROW_START and $ROW_END ";

  $stmt = oci_parse($conn, $sql);

  if ($FIND_TYPE_S !== "" && $FIND_TYPE_S !== "NULL") {
      oci_bind_by_name($stmt, ":type", $FIND_TYPE_S);
  }
  if ($FIND_LEFT_NO_S !== "" && $FIND_LEFT_NO_S !== "NULL") {
      oci_bind_by_name($stmt, ":left_no",$FIND_LEFT_NO_S);
  }
  if ($FIND_DATA_S !== "") {
      oci_bind_by_name($stmt, ":find", $FIND_DATA_S);
  }

  oci_execute($stmt);

  $data = [];

  while ($row = oci_fetch_assoc($stmt)) {
      $data[] = $row;
  }

  header('Content-Type: application/json');
  echo json_encode($data);
  exit;

?>
