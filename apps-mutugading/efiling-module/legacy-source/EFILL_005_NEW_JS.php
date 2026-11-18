<?php include("../common_js.php"); ?>
<script type='text/javascript'>
	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('STSPRS').value = prs;

		var ETD_SYS_ID_S=document.getElementById('ETD_SYS_ID');
		var ETD_SYS_ID_S_OLD=document.getElementById('ETD_SYS_ID_S_OLD').value;
        var ETD_SYS_ID_v = ETD_SYS_ID_S.options[ETD_SYS_ID_S.selectedIndex].value;
        document.getElementById("ETD_SYS_ID_S").value =  ETD_SYS_ID_v;

		var FOLDER_VAL_1 = "";		var FOLDER_VAL_1_V ="";      var FOLDER_VAL_1_T = "";
        if (document.getElementById('FOLDER_VAL_1')!==null ) {
        	FOLDER_VAL_1 = document.getElementById('FOLDER_VAL_1');
        	FOLDER_VAL_1_V = FOLDER_VAL_1.options[FOLDER_VAL_1.selectedIndex].value;
        	FOLDER_VAL_1_T = FOLDER_VAL_1.options[FOLDER_VAL_1.selectedIndex].text;
        	//alert("FOLDER_VAL_1_T " + FOLDER_VAL_1_T + "ETD_SYS_ID_S_OLD "+ETD_SYS_ID_S_OLD );
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_1_S").value =  FOLDER_VAL_1_V;
        	} else {
				document.getElementById("FOLDER_VAL_1_S").value =  "";
        	}
    	}

        var FOLDER_VAL_2 = "";        var FOLDER_VAL_2_V = "";		var FOLDER_VAL_2_T = "";
        if (document.getElementById('FOLDER_VAL_2')!==null ) {
        	FOLDER_VAL_2 = document.getElementById('FOLDER_VAL_2');
        	FOLDER_VAL_2_V = FOLDER_VAL_2.options[FOLDER_VAL_2.selectedIndex].value;
        	FOLDER_VAL_2_T = FOLDER_VAL_2.options[FOLDER_VAL_2.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_2_S").value =  FOLDER_VAL_2_V;
        	} else {
				document.getElementById("FOLDER_VAL_2_S").value =  "NULL";
        	}
    	}

		var FOLDER_VAL_3 = "";        var FOLDER_VAL_3_V = "";var FOLDER_VAL_3_T = "";
    	if (document.getElementById('FOLDER_VAL_3')!==null ) {
        	FOLDER_VAL_3 = document.getElementById('FOLDER_VAL_3');
        	FOLDER_VAL_3_V = FOLDER_VAL_3.options[FOLDER_VAL_3.selectedIndex].value;
        	FOLDER_VAL_3_T = FOLDER_VAL_3.options[FOLDER_VAL_3.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_3_S").value =  FOLDER_VAL_3_V;
        	} else {
				document.getElementById("FOLDER_VAL_3_S").value =  "NULL";
        	}
    	}
        var FOLDER_VAL_4 ="";        var FOLDER_VAL_4_V = ""; var FOLDER_VAL_4_T = "";
    	if (document.getElementById('FOLDER_VAL_4')!==null ) {
        	FOLDER_VAL_4 = document.getElementById('FOLDER_VAL_4');
        	FOLDER_VAL_4_V = FOLDER_VAL_4.options[FOLDER_VAL_4.selectedIndex].value;
        	FOLDER_VAL_4_T = FOLDER_VAL_4.options[FOLDER_VAL_4.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_4_S").value =  FOLDER_VAL_4_V;
        	} else {
				document.getElementById("FOLDER_VAL_4_S").value =  "NULL";
        	}
    	}
        var FOLDER_VAL_5 = ""; var FOLDER_VAL_5_V = ""; var FOLDER_VAL_5_T = "";
    	if (document.getElementById('FOLDER_VAL_5')!==null ) {
        	FOLDER_VAL_5 = document.getElementById('FOLDER_VAL_5');
        	FOLDER_VAL_5_V = FOLDER_VAL_5.options[FOLDER_VAL_5.selectedIndex].value;
        	FOLDER_VAL_5_T = FOLDER_VAL_5.options[FOLDER_VAL_5.selectedIndex].text;
        	if (ETD_SYS_ID_v === ETD_SYS_ID_S_OLD){
				document.getElementById("FOLDER_VAL_5_S").value =  FOLDER_VAL_5_V;
        	} else {
				document.getElementById("FOLDER_VAL_5_S").value =  "NULL";
        	}
    	}

    	//alert("cek 2 "+prs);

    	if (document.getElementById('FIND_DATA')!==null){

    		if (prs==="CLEAR"){
    			document.getElementById("FIND_DATA_S").value =  "";
    			document.getElementById("ETD_SYS_ID_S").value =  "";
    			document.getElementById('FOLDER_VAL_1_S').value="";
    		} else {
    			document.getElementById("FIND_DATA_S").value =  document.getElementById("FIND_DATA").value;
    		}

    	}

    	if (document.getElementById('FOLDER_VAL_1_S').value==="") {
    		document.getElementById('FOLDER_VAL_2_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_3_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_2_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_3_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_3_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_4_S').value = "NULL";
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}

    	if (document.getElementById('FOLDER_VAL_4_S').value === "NULL") {
    		document.getElementById('FOLDER_VAL_5_S').value = "NULL";
    	}
    	//alert("cek 3");
		if (document.getElementById('FOLDER_VAL_1_S').value!==""){
            document.getElementById('FOLDER_VAL_1_V').value = FOLDER_VAL_1_T;
		}
		//alert("cek 3 1");
    	if (document.getElementById('FOLDER_VAL_2_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_2_V').value = FOLDER_VAL_2_T;
    	}
    	if (document.getElementById('FOLDER_VAL_3_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_3_V').value = FOLDER_VAL_3_T;
    	}
    	if (document.getElementById('FOLDER_VAL_4_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_4_V').value = FOLDER_VAL_4_T;
    	}
    	if (document.getElementById('FOLDER_VAL_5_S').value!=="NULL"){
    		document.getElementById('FOLDER_VAL_5_V').value = FOLDER_VAL_5_T;
    	}
    	if (prs.substr(0,4)==="VIEW"){
    		document.getElementById('VIEW_LINK').value = prs.substr(5);
    	}


		document.getElementById(vFormName).submit();
	}
	//function process

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
<?php include("../CALCULATOR_FORM_JS.php"); ?>
