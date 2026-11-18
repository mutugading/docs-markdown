<script type='text/javascript'>
	function getFilter(){
		if (document.getElementById('FILTER_SCLT')!==null ) {
			FILTER_SCLT = document.getElementById('FILTER_SCLT');
        	FILTER_SCLT_V = FILTER_SCLT.options[FILTER_SCLT.selectedIndex].value;
        	FILTER_SCLT_T = FILTER_SCLT.options[FILTER_SCLT.selectedIndex].text;
			//alert("ck 3");
        	document.getElementById('FILTER_SCLT_S').value = FILTER_SCLT_V;
        	if (document.getElementById('FILTER_SCLT_VAL')!==null ){
				document.getElementById('FILTER_SCLT_VAL_S').value = document.getElementById('FILTER_SCLT_VAL').value;
        	}

        	if (document.getElementById('FILTER_SCLT_DATE')!==null ) {
				document.getElementById('FILTER_SCLT_DATE_S').value = document.getElementById('FILTER_SCLT_DATE').value;
			}
		}
	}

	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		//alert("cek 1");
		if (document.getElementById('DATE_FROM')!==null){
			document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		}
		if (document.getElementById('DATE_TO')!==null){
			document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		}
		// alert("cek 2");
		if (prs==="CLEAR_FILTER"){
			document.getElementById('DATE_FROM_S').value = "";
			document.getElementById('DATE_TO_S').value = "";
			document.getElementById('linkFile').value = "";
		}

		getFilter();
		//OPEN_FILE

		if (prs.substring(0,9)==="OPEN_FILE"){
			//alert(prs.substring(0,9));
			//alert(prs.substring(9));
			document.getElementById('linkFile').value = prs.substring(9);
		}
		//alert(prs.substring(0,13));
		if (prs.substring(0,13)==="DTL_OPEN_FILE"){
			//alert(prs.substring(0,9));
			//alert(prs.substring(9));
			document.getElementById('linkFile').value = prs.substring(13);
		}

		document.getElementById('STSPRS').value = prs;
		document.getElementById('USER_NAME').value = "<?php echo $USER_NAME; ?>";
		//alert(document.getElementById('STSPRS').value);
		// alert("cek 2");
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
		document.getElementById('P_PAGE_NO').value =document.getElementById('PAGE_NO').value;
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		getFilter();
		document.getElementById(vFormName).submit();
	}
	function NextRecord() {
		//alert("cek 1");
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		//alert("page no "+vPageNo);
		document.getElementById('P_PAGE_NO').value =vPageNo;
		getFilter();
		//alert("cek 2");
		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		getFilter();
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value ="1";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		getFilter();
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		getFilter();

		document.getElementById('P_PAGE_NO').value =vLastPageNo;
		document.getElementById(vFormName).submit();
	}
</script>
