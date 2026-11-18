<?php

  $sqlWhere = " where 1=1 ";
  if($GS_GRN_NO_SLCT_S!==""){
    $sqlWhere = " $sqlWhere AND GRNNO like '%$GS_GRN_NO_SLCT_S%' ";
  }
  if($GH_DT_SLCT_S!=="ALL"){
    if ($GH_DT_SLCT_S!==""){
        $sqlWhere = " $sqlWhere AND to_char(GH_DT,'DD-MM-YYYY') like '$GH_DT_SLCT_S' ";
    }
  }

  $sqlList ="select ROWNUM REC_NO,substr(GRNNO,1,instr(GRNNO,'-')-1) GRN_CODE
                    ,substr(substr(GRNNO,instr(GRNNO,'-')+1),1,4) GRN_YEAR
                    ,GRNNO
                    ,to_char(GH_DT,'DD-MM-YYYY') GH_DT
                    ,to_char(SJLNDT,'DD-MM-YYYY') SJLNDT
                    ,GH_SUPP_CODE ,GS_SUPP_NAME,GS_DEPT
                    ,MERGE_USER_ID,MERGE_USER_NAME
                    ,to_char(MERGE_DT,'DD-MM-YYYY HH24:MI') MERGE_DT
                    /*,GRN_CODE
                    ,GRN_YEAR
                    ,GRNNO
                    ,to_char(GH_DT,'DD-MM-YYYY') GH_DT
                    ,to_char(SJLNDT,'DD-MM-YYYY') SJLNDT
                    ,GH_SUPP_CODE
                    ,GS_SUPP_NAME
                    ,POno
                    ,GS_DEPT
                    ,HODCUSER
                    ,to_char(HOD_MC_APP_DT,'DD-MM-YYYY') HOD_MC_APP_DT*/
             from (select * from ($sqlList) $sqlWhere) a
             ";
  //if ($USER_NAME==="1949"){echo "$sqlList<br>";}
  //die();
  $SqlCnt = "select count(-1) GET_DATA from ($sqlList)  ";
  $StsViewDt =  getData($conn,$SqlCnt);
  //echo "SqlCnt $SqlCnt";

	$totRows = $StsViewDt;

	$P_ROWS_DISP = $TOT_ROWS_S;
  //if ($USER_NAME==="1949"){echo "$SqlCnt<br>totRows $totRows : P_ROWS_DISP $P_ROWS_DISP<br>";}
  $P_LAST_PAGE_NO = ($totRows / $P_ROWS_DISP);
  if (strpos($P_LAST_PAGE_NO,'.') > 0 ){
  	$P_LAST_PAGE_NO = substr($P_LAST_PAGE_NO,0,strpos($P_LAST_PAGE_NO,'.'));
  	$P_LAST_PAGE_NO = $P_LAST_PAGE_NO + 1;
  }
  if ($P_LAST_PAGE_NO==0){
  	$P_LAST_PAGE_NO=1;
  }
  if ($P_PAGE_NO > $P_LAST_PAGE_NO){
    $P_PAGE_NO = $P_LAST_PAGE_NO;
  }

  if ($P_PAGE_NO=="1"){
    $P_ROW_START  = 1;
    $P_ROW_END    = $P_ROWS_DISP;
	} else {
	    $P_ROW_START = (($P_PAGE_NO - 1) * $P_ROWS_DISP)+1;
	    $P_ROW_END   = $P_ROW_START + ($P_ROWS_DISP-1);
	}
  $font = "10";
?>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
<table style="width:100%">
  <tr >
    <td <?php echo getStyle($font,"left",20); ?>>
      <b>
      Tot. Rows
      <input  type="text" id="TOT_ROWS" name="TOT_ROWS" style="width:40px;height:20px;" value="<?php echo $TOT_ROWS_S; ?>"
              onchange="ViewList('')"
      >
      </b>
    </td>
    <td <?php echo getStyle($font,"center"); ?>>
      <b>
      <input type="button" class="buttonPaging" id="FIRSTREC" value="<<" onclick="FirstRecord()" >
      <input type="button" class="buttonPaging" id="PREVREC" value="<" onclick="PrevRecord()" >
      <input type="number"  name="PAGE_NO" id="PAGE_NO" style="width: 55px;"  value="<?php echo $P_PAGE_NO;?>"
          onkeypress="Javascript: if (event.keyCode==13) GoRecord();"
      >
      : <?php echo $P_LAST_PAGE_NO; ?>
      <input type="button" class="buttonPaging" id="NEXTREC" value=">" onclick="NextRecord()" >
      <input type="button" class="buttonPaging" id="LASTREC" value=">>" onclick="LastRecord()" >
      </b>
    </td>
    <td <?php echo getStyle($font,"right",20); ?>>
    </td>
  </tr>
</table>
</div>
<div class="main" style="overflow-x:auto;overflow-y:auto;" align="center">
  <table style="auto;width:100%;" border="1" >
    <tr>
      <td <?php echo getStyle($font,"center","3"); echo $bgClrListDtl; ?> >No</td><!--1-->
      <td <?php echo getStyle($font,"center","13"); echo $bgClrListDtl; ?> >Merge By</td><!--2-->
      <td <?php echo getStyle($font,"center","8"); echo $bgClrListDtl; ?> >Merge Date</td><!--2-->
      <td <?php echo getStyle($font,"center","10"); echo $bgClrListDtl; ?> >GRN No</td><!--2-->
      <td <?php echo getStyle($font,"center","6"); echo $bgClrListDtl; ?> >GRN Date</td><!--3-->
      <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> >Department</td><!--3-->
      <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> >Supplier</td><!--4-->
      <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> >GRN File</td><!--5-->
      <td <?php echo getStyle($font,"center"); echo $bgClrListDtl; ?> >SJ-GRN</td><!--6-->
    </tr>
<?php
  $sqlListHdr = "select * from ( $sqlList)
                where REC_NO between $P_ROW_START and $P_ROW_END";
  //echo "$sqlList";
  $rsList = oci_parse($conn,$sqlListHdr);
  oci_execute ($rsList);
  $GRN_TO_MERGE_S = "";
  while ($rowList = oci_fetch_array ($rsList, OCI_BOTH)) {
    //$font = "12";
    $REC_NO="";if(!empty($rowList['REC_NO'])){$REC_NO=$rowList['REC_NO'];}
    $GRN_CODE="";if(!empty($rowList['GRN_CODE'])){$GRN_CODE=$rowList['GRN_CODE'];}
    $GRN_YEAR="";if(!empty($rowList['GRN_YEAR'])){$GRN_YEAR=$rowList['GRN_YEAR'];}
    $GRNNO="";if(!empty($rowList['GRNNO'])){$GRNNO=$rowList['GRNNO'];}
    $GH_DT="";if(!empty($rowList['GH_DT'])){$GH_DT=$rowList['GH_DT'];}
    $SJLNDT="";if(!empty($rowList['SJLNDT'])){$SJLNDT=$rowList['SJLNDT'];}
    $GH_SUPP_CODE="";if(!empty($rowList['GH_SUPP_CODE'])){$GH_SUPP_CODE=$rowList['GH_SUPP_CODE'];}
    $GS_SUPP_NAME="";if(!empty($rowList['GS_SUPP_NAME'])){$GS_SUPP_NAME=$rowList['GS_SUPP_NAME'];}
    $PONO="";if(!empty($rowList['PONO'])){$PONO=$rowList['PONO'];}
    $GS_DEPT="";if(!empty($rowList['GS_DEPT'])){$GS_DEPT=$rowList['GS_DEPT'];}
    $HODCUSER="";if(!empty($rowList['HODCUSER'])){$HODCUSER=$rowList['HODCUSER'];}
    $HOD_MC_APP_DT="";if(!empty($rowList['HOD_MC_APP_DT'])){$HOD_MC_APP_DT=$rowList['HOD_MC_APP_DT'];}

    $MERGE_USER_ID="";if(!empty($rowList['MERGE_USER_ID'])){$MERGE_USER_ID=$rowList['MERGE_USER_ID'];}
    $MERGE_USER_NAME="";if(!empty($rowList['MERGE_USER_NAME'])){$MERGE_USER_NAME=$rowList['MERGE_USER_NAME'];}
    $MERGE_DT="";if(!empty($rowList['MERGE_DT'])){$MERGE_DT=$rowList['MERGE_DT'];}

    $fileGrn = $fileDest.$GRN_CODE."/".$GRN_YEAR."/".$GRNNO.".pdf";
    $fileSjGrn = $fileDest."SJGRN/".$GRN_YEAR."/SJGRN-".$GRNNO.".pdf";

    $FileGrnExist = ""; if(file_exists($fileGrn)){$FileGrnExist="Y";}
    $FileSjGrnExist = ""; if(file_exists($fileSjGrn)){$FileSjGrnExist="Y";}

    $stsViewTr = "Y";
    if ($stsViewTr === "Y"){
      if ($GRN_TO_MERGE_S===""){
        $GRN_TO_MERGE_S="'$GRNNO'";
      } else {
        $GRN_TO_MERGE_S=$GRN_TO_MERGE_S.",'$GRNNO'";
      }
?>
    <tr>
      <td <?php echo getStyle($font,"right");  ?> ><?php echo $REC_NO; ?></td><!--1-->
      <td <?php echo getStyle($font,"left");  ?> >&nbsp<?php echo $MERGE_USER_ID."-".$MERGE_USER_NAME; ?></td><!--2-->
      <td <?php echo getStyle($font,"center");  ?> ><?php echo $MERGE_DT; ?></td><!--2-->
      <td <?php echo getStyle($font,"left");  ?> >&nbsp<?php echo $GRNNO; ?></td><!--2-->
      <td <?php echo getStyle($font,"center");  ?> ><?php echo $GH_DT; ?></td><!--3-->
      <td <?php echo getStyle($font,"center");  ?> ><?php echo $GS_DEPT; ?></td><!--3-->
      <td <?php echo getStyle($font,"left");  ?> >&nbsp<?php echo $GH_SUPP_CODE."-".$GS_SUPP_NAME; ?></td><!--4-->
      <td <?php echo getStyle($font,"left");  ?> >
  <?php
      if ($FileGrnExist==="Y"){
          $fileGrnWeb = $fileWeb.$GRN_CODE."/".$GRN_YEAR."/".$GRNNO.".pdf";
          $evntBtn="ViewDoc('$fileGrnWeb')";
  ?>
          &nbsp&nbsp<button onclick="<?php echo $evntBtn; ?>">...</button>&nbsp&nbsp<?php echo $GRNNO; ?>
  <?php
      } else {
        echo "<font color='red'>$GRNNO (NA)</font>";
      }
  ?>
      </td><!--5-->
      <td <?php echo getStyle($font,"left");  ?> >
  <?php
      if ($FileSjGrnExist==="Y"){
          $fileSjGrnWeb = $fileWeb."SJGRN/".$GRN_YEAR."/SJGRN-".$GRNNO.".pdf";
          $evntBtn="ViewDoc('$fileSjGrnWeb')";
  ?>
          &nbsp&nbsp<button onclick="<?php echo $evntBtn; ?>">...</button>&nbsp&nbsp<?php echo "SJGRN-".$GRNNO; ?>
  <?php
      } else {
        echo "<font color='red'>SJGRN-".$GRNNO." (NA)</font>";
      }
  ?>
      </td><!--6-->
      </td><!--12-->
    </tr>
  <?php
    }
    //include ($FORM_NAME."_LIST_DTL.php");
  }
  ?>
</table>
</div>
