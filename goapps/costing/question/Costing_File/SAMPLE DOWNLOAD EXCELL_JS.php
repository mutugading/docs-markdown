<script type='text/javascript'>

	function getParameter(){
		document.getElementById('PERIOD_YEAR_S').value = document.getElementById('PERIOD_YEAR').value;

		if (document.getElementById('PERIOD_MONTH')!==null ) {
      	PERIOD_MONTH = document.getElementById('PERIOD_MONTH');
				document.getElementById('PERIOD_MONTH_S').value = PERIOD_MONTH.options[PERIOD_MONTH.selectedIndex].value;
  	}

	}

	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vSTSPRS ="<?php Print($STSPRS); ?>";
		var vStsSubmit = "Y" ;
		getParameter();

		if (document.getElementById('PERIOD_YEAR_S').value===""){
			vStsSubmit = "N" ;
			alert("PERIOD YEAR can't Null");
		}

		if (vStsSubmit === "Y" ){
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
