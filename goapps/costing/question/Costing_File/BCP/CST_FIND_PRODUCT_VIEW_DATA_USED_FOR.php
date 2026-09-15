<?php
  $sqlList = "
              select rownum REC_NO,CTPUF_CYL_SYS_ID,d.* from (

                select  distinct CTPUF_CYL_SYS_ID,c.*
                from cst_tmp_prd_used_for a
                     ,cst_yarn_left c
                where a.CTPUF_CREATED_BY = '$USER_NAME'
                and CTPUF_CYL_SYS_ID = c.CYL_SYS_ID
                order by cyl_left_no

              ) d
              ";

  $totDt = getData($conn,"select count(-1) GET_DATA from ($sqlList)");
?>
<div class="main" style="overflow-x:auto;" align="center">
<table style="width:100%">
	<tr >
		<td  align="center">
		    <input 	type="button" style="font-size:12px;background-color: #00BFFF;"
								value="Search Data" onclick="SearchData('SEARCH_DATA')"
				>
		</td>
	</tr>
</table>
</div>
<div class="main" style="overflow-x:auto;" align="center">
<table style="auto;width:90%;" border="1" >
  <tr>
    <td <?php echo getStyle($font,"center","5"); echo $bgClrLbl; ?> >No</td><!--1-->
    <td <?php echo getStyle($font,"center","10"); echo $bgClrLbl; ?> > Sys Id </td><!--2-->
    <td <?php echo getStyle($font,"center","10"); echo $bgClrLbl; ?> > RM Left No </td><!--2-->
    <td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Type</td><!--3-->
    <td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Name</td><!--3-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Shade Code</td><!--4-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Shade Name</td><!--5-->
		<td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Item Code</td><!--6-->
    <td <?php echo getStyle($font,"center"); echo $bgClrLbl; ?> >Reff In Top</td><!--6-->
  </tr>
<?php
  //$bgClrLbl = "bgcolor='#F6E3CE'"
  //if ($USER_NAME==="1949"){echo $sqlList;}die();
  $rsList = oci_parse($conn,$sqlList);
  oci_execute ($rsList);
  while ($rowList = oci_fetch_array ($rsList, OCI_BOTH)) {
?>
  <tr>
    <td <?php echo getStyle($font,"left");?> ><?php echo $rowList['REC_NO']; ?></td><!--1-->
    <td <?php echo getStyle($font,"left");?> ><?php echo $rowList['CYL_SYS_ID']; ?></td><!--2-->
		<td <?php echo getStyle($font,"left");?> ><?php echo $rowList['CYL_LEFT_NO']; ?></td><!--2-->
    <td <?php echo getStyle($font,"left"); ?>><?php echo $rowList['CYL_TYPE']; ?></td><!--7-->
    <td <?php echo getStyle($font,"left");  ?>><?php echo $rowList['CYL_NAME']; ?> </td><!--3-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_SHADE_CODE']; ?></td><!--4-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_SHADE_NAME']; ?></td><!--5-->
		<td <?php echo getStyle($font,"center"); ?>><?php echo $rowList['CYL_ITEM_CODE']; ?></td><!--6-->
    <td <?php echo getStyle($font,"center"); ?>></td><!--6-->
	</tr>
<?php

    $sqlListDtl = "
            select rownum REC_NO,d.*,CDPUF_TOP_NO_TYPE,CDPUF_TOP_NO_CHECKING from (

                                select l.*,CDPUF_TOP_NO_TYPE,CDPUF_TOP_NO_CHECKING
                                from cst_data_prd_used_for d
                                     ,cst_yarn_calculation c
                                     ,cst_yarn_left l
                                where CDPUF_CREATED_BY = '$USER_NAME'
                                and d.CDPUF_CTPUF_CYL_SYS_ID = '".$rowList['CTPUF_CYL_SYS_ID']."'
                                and d.CDPUF_CYC_SYS_ID = c.CYC_SYS_ID
                                and c.CYC_CYL_SYS_ID = l.CYL_SYS_ID
                                order by cyl_left_no,CDPUF_TOP_NO_TYPE
            ) d
            ";
    //if ($USER_NAME==="1949"){
      //echo $sqlListDtl;
    //}//die();
    $rsListDtl = oci_parse($conn,$sqlListDtl);
    oci_execute ($rsListDtl);
    $noDtl = 0; $DTL_CYL_LEFT_NO = "";
    while ($rowListDtl = oci_fetch_array ($rsListDtl, OCI_BOTH)) {
      if ($rowListDtl['CDPUF_TOP_NO_TYPE'] === "TOP 20") {
        $bgClrDtl = "bgcolor='#E0F2F7'";
      }else if ($rowListDtl['CDPUF_TOP_NO_TYPE'] === "TOP 55") {
        $bgClrDtl = "bgcolor='#BDBDBD'";
      }

?>
    <tr>
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl;?> >
        &nbsp&nbsp&nbsp&nbsp
        <?php
          if ($DTL_CYL_LEFT_NO === "") {
              $noDtl++;echo $noDtl;
          } else {
              if ($DTL_CYL_LEFT_NO !== $rowListDtl['CYL_LEFT_NO']){
                $noDtl++;echo $noDtl;
              }
          }
          $DTL_CYL_LEFT_NO = $rowListDtl['CYL_LEFT_NO'];
        ?></td><!--1-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl;?> ><?php echo $rowListDtl['CYL_SYS_ID']; ?></td><!--2-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl;?> ><?php echo $rowListDtl['CYL_LEFT_NO']; ?></td><!--2-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl; ?>><?php echo $rowListDtl['CYL_TYPE']; ?></td><!--7-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl;  ?>><?php echo $rowListDtl['CYL_NAME']; ?> </td><!--3-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl; ?>><?php echo $rowListDtl['CYL_SHADE_CODE']; ?></td><!--4-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl; ?>><?php echo $rowListDtl['CYL_SHADE_NAME']; ?></td><!--5-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl; ?>><?php echo $rowListDtl['CYL_ITEM_CODE']; ?></td><!--6-->
      <td <?php echo getStyle($font,"left"); echo $bgClrDtl; ?>><?php echo $rowListDtl['CDPUF_TOP_NO_CHECKING']; ?></td><!--6-->
    </tr>
<?php
    }
	}
?>
</table>
</div>
