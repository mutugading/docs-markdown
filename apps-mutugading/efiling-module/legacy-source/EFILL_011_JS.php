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

document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape' || event.keyCode === 27) {
    closeModal();
  }
});

  function printDt(pTypeFile,pLink){
    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var MENU_ID = "<?php Print($P_MENU_ID); ?>";

    var vUrlLink = "<?php Print($urlLinkDtl); ?>";


    var DOCUMENT_TYPE = getListVal('DOCUMENT_TYPE');
    var DATE_FROM = getTxtVal('DATE_FROM');
    var DATE_TO = getTxtVal('DATE_TO');
    var FILE_STATUS = getListVal('FILE_STATUS');
    var USER_DESC_FLTR = getListVal('USER_DESC_FLTR');

    var vLink=pLink;
    if(pTypeFile==="XLS 1"){
      vUrlLink=vUrlLink+"_XLS?";
    }else if(pTypeFile==="XLS 2"){
      vUrlLink=vUrlLink+"_XLS_2?";
    }
    vUrlLink=vUrlLink
              +"P_MENU_ID="+MENU_ID
              +"&P_DOCUMENT_TYPE="+DOCUMENT_TYPE
              +"&P_DATE_FROM="+DATE_FROM
              +"&P_DATE_TO="+DATE_TO
              +"&P_FILE_STATUS="+FILE_STATUS
              +"&USER_DESC_FLTR="+USER_DESC_FLTR
              ;

      window.open(vUrlLink,'_blank');

    //window.open(vLink,'_blank');
    //alert("test print "+vFORM_NAME+" "+DOCUMENT_TYPE+" "+DATE_FROM+" "+DATE_TO+" "+FILE_STATUS);
  }

  function getParameter(){
    // document.getElementById('TOT_ROWS_S').value = getTxtVal('TOT_ROWS');
    // //document.getElementById('P_PAGE_NO').value = getTxtVal('PAGE_NO');
    // document.getElementById('DOCUMENT_TYPE_S').value = getListVal('DOCUMENT_TYPE');
    // document.getElementById('FILTER_VAL_S').value = getTxtVal('FILTER_VAL');
  }

	function closeModal(){
		var modal = document.getElementById('myModal');
		modal.style.display = "none";
    //showData();
    //var REC_INFOCUS = getTxtVal('REC_INFOCUS');
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    //getParameter();
    document.getElementById(FORM_NAME).submit();

	}

  function viewSql(){
    //alert('sql');
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

  function showLov_Folder(){
    var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

    var vFIND_LOV = getTxtVal('FIND_LOV');
    var DATE_FROM = getTxtVal('DATE_FROM');
    var DATE_TO = getTxtVal('DATE_TO');
    var FILE_STATUS = getListVal('FILE_STATUS');

    const xhttp = new XMLHttpRequest();
    document.getElementById("txtHint").innerHTML = null;
    xhttp.onload = function() {
      document.getElementById("txtHint").innerHTML = this.responseText;
    }

    var FORM_NAME_MDL = FORM_NAME+"_LOV.php";
    //alert("FORM_NAME_MDL "+FORM_NAME_MDL);
    xhttp.open("GET", FORM_NAME_MDL+"?"
                      +"FORM_NAME="+FORM_NAME
                      +"&USER_NAME="+USER_NAME
                      +"&P_MENU_ID="+P_MENU_ID
                      +"&STS_LOV=LOV_FOLDER"
                      +"&FIND_LOV="+vFIND_LOV
              );

    xhttp.send();
    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;
  }

  function ckFolder(pCK_Name,pCK_Val){
    if (confirm("Select this Folder "+pCK_Val+"?")){
      document.getElementById('DOCUMENT_TYPE_S').value=pCK_Val;
      //alert(getTxtVal('DATE_FROM'));
      document.getElementById("DATE_FROM_S").value =getTxtVal('DATE_FROM');
      closeModal();
    }
    document.getElementById(pCK_Name).checked=false
    //viewLovData(STS_LOV);
  }

  function showLov(pStsLov,pCUST_CODE,pREC_INFOCUS=''){
		var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

    var vFIND_LOV = getTxtVal('FIND_LOV');
		const xhttp = new XMLHttpRequest();
		document.getElementById("txtHint").innerHTML = null;
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
                      +"&CUST_CODE="+pCUST_CODE
							);

		xhttp.send();
    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;
	}

  function changePrm(pID_PARAM,pVAL_PARAM){
    if (pID_PARAM==="DATE_FROM"){
      document.getElementById("DATE_FROM_S").value = pVAL_PARAM;
    }
    if (pID_PARAM==="DATE_TO"){
      document.getElementById("DATE_TO_S").value = pVAL_PARAM;
    }
    if (pID_PARAM==="FILE_STATUS"){
      document.getElementById("FILE_STATUS_S").value = pVAL_PARAM;
    }
    if (pID_PARAM==="USER_DESC_FLTR"){
      document.getElementById("USER_DESC_FLTR_S").value = pVAL_PARAM;
    }
    showData();
  }

  function showData(){
    //alert('test');
    var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";
    // var P_PAGE_NO = getTxtVal('PAGE_NO');
    // var TOT_ROWS = getTxtVal('TOT_ROWS');

    //alert("DATE_FROM_S "+getTxtVal('DATE_FROM_S'));

    var DOCUMENT_TYPE = getListVal('DOCUMENT_TYPE');
    var DATE_FROM = getTxtVal('DATE_FROM');if (DATE_FROM===""){DATE_FROM = getTxtVal('DATE_FROM_S');}
    var DATE_TO = getTxtVal('DATE_TO');if (DATE_TO===""){DATE_TO = getTxtVal('DATE_TO_S');}
    var FILE_STATUS = getListVal('FILE_STATUS');if (FILE_STATUS===""){FILE_STATUS = getTxtVal('FILE_STATUS_S');}
    var USER_DESC_FLTR = getListVal('USER_DESC_FLTR');if (USER_DESC_FLTR===""){USER_DESC_FLTR = getTxtVal('USER_DESC_FLTR_S');}
    //alert("FILE_STATUS "+FILE_STATUS);
    const xhttp = new XMLHttpRequest();
    document.getElementById("lable").innerHTML = "Please Wait";
    document.getElementById("dataHint").innerHTML = null;
    xhttp.onload = function() {
      document.getElementById("dataHint").innerHTML = this.responseText;
      document.getElementById("lable").innerHTML = null;
      document.getElementById("DOCUMENT_TYPE_S").value =DOCUMENT_TYPE;
    }
    var FORM_NAME_MDL = FORM_NAME+"_SHOW.php";
    xhttp.open("GET", FORM_NAME_MDL+"?"
                      +"FORM_NAME="+FORM_NAME
                      +"&P_MENU_ID="+P_MENU_ID
                      +"&USER_NAME="+USER_NAME
                      +"&DOCUMENT_TYPE="+DOCUMENT_TYPE
                      +"&DATE_FROM="+DATE_FROM
                      +"&DATE_TO="+DATE_TO
                      +"&FILE_STATUS="+FILE_STATUS
                      +"&USER_DESC_FLTR="+USER_DESC_FLTR
                    );
    xhttp.send();
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
