<script type='text/javascript'>
	function paramSelect(){
		var FIND_SELECT=""; var FIND_SELECT_v=""; var FIND_SELECT_t="";
		if (document.getElementById('FIND_SELECT')!==null ) {
        	FIND_SELECT = document.getElementById('FIND_SELECT');
        	FIND_SELECT_v = FIND_SELECT.options[FIND_SELECT.selectedIndex].value;
        	FIND_SELECT_t = FIND_SELECT.options[FIND_SELECT.selectedIndex].text;
			document.getElementById("FIND_SELECT_S").value =  FIND_SELECT_v;
			//alert("FIND_SELECT_S "+ document.getElementById("FIND_SELECT_S").value);
    	}

		if (document.getElementById('USER_ID')!==null ) {
        	document.getElementById("USER_ID_S").value =  document.getElementById("USER_ID").value;
			//alert("FIND_SELECT_S "+ document.getElementById("FIND_SELECT_S").value);
    	}

    	document.getElementById("FIND_SELECT_VAL_S").value =  document.getElementById("FIND_SELECT_VAL").value;

			document.getElementById("Date_From_S").value =  document.getElementById("Date_From").value;
			document.getElementById("Date_End_S").value =  document.getElementById("Date_End").value;
	}

	function Process(prs){
		//alert(prs);
		var vFormName ="<?php Print($FORM_NAME); ?>";

		paramSelect();

		document.getElementById('STSPRS').value = prs;
		//alert(document.getElementById('STSPRS').value);
		document.getElementById(vFormName).submit();
	}

	function GoRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value =document.getElementById('PAGE_NO').value;
		paramSelect();
		document.getElementById(vFormName).submit();
	}
	function NextRecord() {
		//alert("next");
		var vFormName ="<?php Print($FORM_NAME); ?>";
		//alert("next 1");
		var vPageNo  = document.getElementById('PAGE_NO').value;
		//alert("next 2");
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";

		paramSelect();

		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		document.getElementById('P_PAGE_NO').value =vPageNo;
		//alert("next l");
		document.getElementById(vFormName).submit();
	}

	function PrevRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
		paramSelect();
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}

	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value ="1";
		paramSelect();
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		paramSelect();
		document.getElementById('P_PAGE_NO').value =vLastPageNo;
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
	</script>
