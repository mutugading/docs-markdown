<?php include("../common_js.php"); ?>
<!-- The Modal -->
<div class="modal fade" id="myModal">
  <div style="width:95%">
    <div class="modal-content">
      <!-- Modal Header -->
      <div align="right">
        <button type="button" onclick="closeModal()">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div id="txtHint"></div>
      </div>

    </div>
  </div>
</div>
<!-- The Modal -->
<script type='text/javascript'>

  function viewFile(pFileLink,pTRX_NO){
    //alert(pFileLink);
    window.open(pFileLink);
    var vREC_INFOCUS = getTxtVal('REC_INFOCUS');
    showLov('VIEW_DETIL',pTRX_NO,vREC_INFOCUS);
    // var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    // getParameter();
    // document.getElementById(FORM_NAME).submit();
  }

  function getParameter(){
    document.getElementById('TOT_ROWS_S').value = getTxtVal('TOT_ROWS');
    document.getElementById('P_PAGE_NO').value = getTxtVal('PAGE_NO');
    document.getElementById('DATE_FROM_S').value = getTxtVal('DATE_FROM');
    document.getElementById('DATE_TO_S').value = getTxtVal('DATE_TO');
    document.getElementById('FILTER_BY_S').value = getListVal('FILTER_BY');
    document.getElementById('FILTER_VAL_S').value = getTxtVal('FILTER_VAL');
  }

	function closeModal(){
		var modal = document.getElementById('myModal');
		modal.style.display = "none";
    //showData();
    var REC_INFOCUS = getTxtVal('REC_INFOCUS');
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    getParameter();
    document.getElementById(FORM_NAME).submit();

	}

  function viewSql(){

    if (document.getElementById('DIV_SQL')!==null ) {
      if (document.getElementById('VIEW_SQL').checked === true){
        document.getElementById('DIV_SQL').style.visibility='visible';
        document.getElementById('DIV_SQL').style.height='100px';
      } else {
        document.getElementById('DIV_SQL').style.visibility='hidden';
        document.getElementById('DIV_SQL').style.height='0px';
      }
    }
  }

  function showLov(pStsLov,pTRX_NO,pREC_INFOCUS){
		var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

    var vFIND_LOV = getTxtVal('FIND_LOV');
		const xhttp = new XMLHttpRequest();
		document.getElementById("txtHint").innerHTML = null;
    // document.getElementById('VIEW_FILE').src = null;
		xhttp.onload = function() {
			document.getElementById("txtHint").innerHTML = this.responseText;
		}

		var FORM_NAME_MDL = FORM_NAME+"_LOV.php";
		//alert("FORM_NAME_MDL "+FORM_NAME_MDL);
		xhttp.open("GET", FORM_NAME_MDL+"?"
											+"FORM_NAME="+FORM_NAME
											+"&USER_NAME="+USER_NAME
											+"&P_MENU_ID="+P_MENU_ID
											+"&STS_LOV="+pStsLov
                      +"&FIND_LOV="+vFIND_LOV
                      +"&TRX_NO="+pTRX_NO
							);

		xhttp.send();
    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;
	}

  function showData(){
    //alert('test');
    var iframe = document.getElementById("VIEW_FILE");
    iframe.src = "about:blank";
    var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";
    var P_PAGE_NO = getTxtVal('PAGE_NO');
    var TOT_ROWS = getTxtVal('TOT_ROWS');

    var DATE_FROM = getTxtVal('DATE_FROM');
    var DATE_TO = getTxtVal('DATE_TO');
    var FILTER_BY = getListVal('FILTER_BY');
    var FILTER_VAL = getTxtVal('FILTER_VAL');
    var REC_INFOCUS = getTxtVal('REC_INFOCUS');

    var viewDt = "Y";
    if (FILTER_BY!=="Null"){
      if (FILTER_VAL === ""){
        viewDt = "N";
      }
    }

    if (viewDt === "Y"){
      const xhttp = new XMLHttpRequest();
      document.getElementById("lable").innerHTML = "Please Wait";
      document.getElementById("dataHint").innerHTML = null;
      xhttp.onload = function() {
        document.getElementById("dataHint").innerHTML = this.responseText;
        document.getElementById("lable").innerHTML = null;
        document.getElementById("P_LAST_PAGE_NO").value = getTxtVal('TOT_PAGE');
        document.getElementById(REC_INFOCUS).focus();
      }
      var FORM_NAME_MDL = FORM_NAME+"_SHOW.php";
      //alert("DATE_FROM "+DATE_FROM+" DATE_TO "+DATE_TO);
      xhttp.open("GET", FORM_NAME_MDL+"?"
                        +"FORM_NAME="+FORM_NAME
                        +"&P_MENU_ID="+P_MENU_ID
                        +"&USER_NAME="+USER_NAME
                        +"&P_PAGE_NO="+P_PAGE_NO
                        +"&TOT_ROWS="+TOT_ROWS
                        +"&DATE_FROM="+DATE_FROM
                        +"&DATE_TO="+DATE_TO
                        +"&FILTER_BY="+FILTER_BY
                        +"&FILTER_VAL="+FILTER_VAL
                        +"&REC_INFOCUS="+REC_INFOCUS
                      );
      xhttp.send();
      viewSql();
    }
  }

	var dropdown = document.getElementsByClassName("dropdown-btn");
	var i;

	for (i = 0; i < dropdown.length; i++) {
	  dropdown[i].addEventListener("click", function() {
	  this.classList.toggle("active");
	  var dropdownContent = this.nextElementSibling;
	  if (dropdownContent.style.display === "block") {
	  dropdownContent.style.display = "none";
	  } else {
	  dropdownContent.style.display = "block";
	  }
	  });
	}

	function openNav() {
	  document.getElementById("mySidenav").style.width = "250px";
	}

	function closeNav() {
	  document.getElementById("mySidenav").style.width = "0";
	}

	function GoRecord(pPageNo) {
    setTxtVal('PAGE_NO',pPageNo);
    showData();
	}
	function NextRecord() {
    var vPageNo  =  getTxtVal('PAGE_NO');//document.getElementById('PAGE_NO').value;
    var vLastPageNo = getTxtVal('P_LAST_PAGE_NO');
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
    GoRecord(vPageNo);
	}

	function PrevRecord() {
    var vPageNo  =  getTxtVal('PAGE_NO');//document.getElementById('PAGE_NO').value;
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
    GoRecord(vPageNo);
	}

	function FirstRecord() {
    GoRecord(1);
	}

	function LastRecord() {
    var vLastPageNo = getTxtVal('P_LAST_PAGE_NO');
    GoRecord(vLastPageNo);
	}

</script>
