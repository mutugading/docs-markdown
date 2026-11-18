<script type='text/javascript'>
  function getParameter(){
    if (document.getElementById('P_PAGE_NO')!==null ) {
      document.getElementById('P_PAGE_NO').value = document.getElementById('P_PAGE_NO').value;
    }
    if (document.getElementById('DATE_FROM')!==null ) {
      document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
    }
    if (document.getElementById('DATE_TO')!==null ) {
      document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
    }
    if (document.getElementById('FILTER_SCLT')!==null ) {
      FILTER_SCLT = document.getElementById('FILTER_SCLT');
      document.getElementById('FILTER_SCLT_S').value = FILTER_SCLT.options[FILTER_SCLT.selectedIndex].value;
    }
  	if (document.getElementById('FILTER_SCLT_VAL')!==null ){
				document.getElementById('FILTER_SCLT_VAL_S').value = document.getElementById('FILTER_SCLT_VAL').value;
  	}
    if (document.getElementById('FILTER_AREA')!==null ) {
      FILTER_AREA = document.getElementById('FILTER_AREA');
      document.getElementById('FILTER_AREA_S').value = FILTER_AREA.options[FILTER_AREA.selectedIndex].value;
    }
    if (document.getElementById('FILTER_SCLT_DATE')!==null ) {
      document.getElementById('FILTER_SCLT_DATE_S').value = document.getElementById('FILTER_SCLT_DATE').value;
    }
    if (document.getElementById('TOT_ROWS')!==null ) {
      document.getElementById('TOT_ROWS_S').value = document.getElementById('TOT_ROWS').value;
    }

    if (document.getElementById('Doc_N_A')!==null ) {
      var vDoc_N_A = document.getElementsByName('Doc_N_A');
  		var vDoc_N_A_v = "";
  		for (var i = 0, length = vDoc_N_A.length; i < length; i++) {
  				if (vDoc_N_A[i].checked) {
  						vDoc_N_A = "Y";
  				} else vDoc_N_A = "N";
  		}
  		document.getElementById('Doc_N_A_S').value =	vDoc_N_A;
    }

    if (document.getElementById('MCM_N_A')!==null ) {
      var vMCM_N_A = document.getElementsByName('MCM_N_A');
  		var vMCM_N_A_v = "";
      //alert("test 1");
  		for (var i = 0, length = vMCM_N_A.length; i < length; i++) {
  				if (vMCM_N_A[i].checked) {
  						vMCM_N_A = "Y";
  				} else vMCM_N_A = "N";
  		}
  		document.getElementById('MCM_N_A_S').value =	vMCM_N_A;
    }

  }

  function FilterFind(){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    //alert("ok " + document.getElementById('FILTER_SCLT_S').value + " " + document.getElementById('FILTER_SCLT_VAL_S').value);
    document.getElementById(vFormName).submit();
  }

  function viewDoc_NA(){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    //alert("test");
    document.getElementById(vFormName).submit();
  }

  function viewDoc_MCM_NA(){
    //alert("test");
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    //alert("test");
    document.getElementById(vFormName).submit();
  }

  function ViewData(){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    //alert("ok " + document.getElementById('FILTER_SCLT_S').value + " " + document.getElementById('FILTER_SCLT_VAL_S').value);
    document.getElementById(vFormName).submit();
  }

  function fClear(){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    //getParameter();
    document.getElementById('P_PAGE_NO').value = "";
    document.getElementById('DATE_FROM_S').value = "";
    document.getElementById('DATE_TO_S').value = "";

    document.getElementById('FILTER_SCLT_S').value = "";
    document.getElementById('FILTER_SCLT_VAL_S').value = "";
    document.getElementById('FILTER_SCLT_DATE_S').value = "";
    document.getElementById('FILTER_AREA_S').value = "";
    document.getElementById(vFormName).submit();
  }

  function ViewDtl(pPaymenyNo){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    document.getElementById('PAYMENT_NO_S').value = pPaymenyNo;
    document.getElementById(vFormName).submit();
  }

  function ViewFile(pFilePath){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    document.getElementById('pFilePath_S').value = pFilePath;
    document.getElementById(vFormName).submit();
  }

  function ViewFileDtl(pFilePath,pPAYMENT_NO_S){
    var vFormName ="<?php Print($FORM_NAME); ?>";
    //getParameter();
    document.getElementById('DATE_FROM_S').value = "<?php Print($DATE_FROM_S); ?>";
    document.getElementById('DATE_TO_S').value = "<?php Print($DATE_TO_S); ?>";
    document.getElementById('pFilePath_S').value = pFilePath;
    document.getElementById('PAYMENT_NO_S').value = pPAYMENT_NO_S;
    document.getElementById(vFormName).submit();
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

function GoRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
		// document.getElementById('P_PAGE_NO').value =document.getElementById('PAGE_NO').value;
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById(vFormName).submit();
	}
	function NextRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
		var vPageNo  = document.getElementById('P_PAGE_NO').value;
    var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('P_PAGE_NO').value;
    var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
    getParameter();
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
    getParameter();
    document.getElementById('P_PAGE_NO').value ="1";
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
    getParameter();
    var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('P_PAGE_NO').value =vLastPageNo;
		document.getElementById(vFormName).submit();
	}

</script>
