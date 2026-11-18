<script type='text/javascript'>
	function getParameter(){
			document.getElementById('P_DATE_S').value = document.getElementById('P_DATE').value;
	}

	function Process(prs){
		//alert(prs.substr(0, 10));
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var prsForm = "Y";
		document.getElementById('STSPRS').value = prs;
		getParameter();
		if (prs === "CLEAR PARAM"){
			document.getElementById('P_DATE_S').value = "";
		}

		if (prs.substr(0, 10)==="SEND MAIL "){
			vConfirm = confirm("Send Document into Customer ?");
			if(vConfirm === false) {
				prsForm = "N";
			}
		}

		if (prsForm === "Y") {
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
