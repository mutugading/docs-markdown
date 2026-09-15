<script type='text/javascript'>
  function checkFile(){
    var x = document.getElementById("listGambar[]");
    var txt = ""; var totSize = 0;
    if ('files' in x) {
      if (x.files.length == 0) {
        txt = "Select one or more files.";
      } else {
        for (var i = 0; i < x.files.length; i++) {
          var file = x.files[i];
          if ('size' in file) {
  					totSize = totSize + file.size;
          }
        }
  			if (totSize > 41943040){
  				txt = "File size must not be more than 41943040 Bytes'";
  			}
      }
    }
  	if (txt!==""){
  		x.value = "";
  		alert(txt);
  	}

  }

  function getParameter(){
		if (document.getElementById('ETD_SYS_ID')!==null ) {
      	ETD_SYS_ID = document.getElementById('ETD_SYS_ID');
      	document.getElementById('ETD_SYS_ID_S').value = ETD_SYS_ID.options[ETD_SYS_ID.selectedIndex].value;
  	}
    //alert('ETD_SYS_ID_S ' + document.getElementById('ETD_SYS_ID_S').value);
    if (document.getElementById('FOLDER_VAL_1')!==null ) {
      	FOLDER_VAL_1 = document.getElementById('FOLDER_VAL_1');
      	document.getElementById('FOLDER_VAL_1_S').value = FOLDER_VAL_1.options[FOLDER_VAL_1.selectedIndex].value;
  	}
    //alert('FOLDER_VAL_1_S ' + document.getElementById('FOLDER_VAL_1_S').value);
    if (document.getElementById('FOLDER_VAL_2')!==null ) {
      	FOLDER_VAL_2 = document.getElementById('FOLDER_VAL_2');
      	document.getElementById('FOLDER_VAL_2_S').value = FOLDER_VAL_2.options[FOLDER_VAL_2.selectedIndex].value;
  	}
    //alert('FOLDER_VAL_2_S ' + document.getElementById('FOLDER_VAL_2_S').value);
    if (document.getElementById('FOLDER_VAL_3')!==null ) {
      	FOLDER_VAL_3 = document.getElementById('FOLDER_VAL_3');
      	document.getElementById('FOLDER_VAL_3_S').value = FOLDER_VAL_3.options[FOLDER_VAL_3.selectedIndex].value;
  	}
    //alert('FOLDER_VAL_3_S ' + document.getElementById('FOLDER_VAL_3_S').value);
  }

	function Process(prs){
		//alert('test');
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vMsgConfirm = "";
    getParameter();
    //alert('test 1');
    //alert('test 2 ' + document.getElementById('ETD_SYS_ID_S').value);
    vPrs_0 = prs;
    vStsPrs = "Y";
		if (prs==="UPLOAD") {
			vMsgConfirm = "Upload File ?"
		}else if (prs==="MOVE") {
			vMsgConfirm = "Move File ?"
		}

		if (vMsgConfirm!==""){
			vConfirm = confirm(vMsgConfirm);
			if (vConfirm !== true){
				prs = vPrs_0;
        vStsPrs = "N";
			}
		}	else {
			prs = vPrs_0;
		}

		//alert(document.getElementById('STSPRS').value);
    if (vStsPrs === "Y"){
      document.getElementById('STSPRS').value = prs;
  		document.getElementById(vFormName).submit();
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
</script>
