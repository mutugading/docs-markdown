<script type='text/javascript'>
	function getParameter(){
		if (document.getElementById('TOT_ROWS')!==null){
			document.getElementById('TOT_ROWS_S').value = document.getElementById('TOT_ROWS').value;
		}
		if (document.getElementById('P_PAGE_NO')!==null){
			document.getElementById('P_PAGE_NO').value = <?php Print($P_PAGE_NO); ?>
		}
		if (document.getElementById('DATE_FROM')!==null){
			document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		}
		if (document.getElementById('DATE_TO')!==null){
			document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
		}
		if (document.getElementById('GS_GRN_NO_SLCT')!==null){
			document.getElementById('GS_GRN_NO_SLCT_S').value = document.getElementById('GS_GRN_NO_SLCT').value;
		}
		if (document.getElementById('GH_DT_SLCT')!==null){
			document.getElementById('GH_DT_SLCT_S').value = document.getElementById('GH_DT_SLCT').value;
		}
	}

	function getValItem(pItmName){
		return document.getElementById(pItmName).value;
	}

	function ViewDoc(pFile){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		getParameter();
		document.getElementById('STSPRS').value = "VIEW_FILE";
		document.getElementById('FILE_DOC_VIEW').value = pFile;
		document.getElementById(vFormName).submit();
	}

	function ViewList(){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		getParameter();
		document.getElementById('STSPRS').value = "VIEW_DATA";
		document.getElementById(vFormName).submit();
	}

	function MergeDocs(pGrnNo,pGrnFile,pSjGrnFile){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		getParameter();
		if (confirm("Merge File GRN "+pGrnNo+"?")){
			document.getElementById('STSPRS').value = "MERGE_FILE";
			document.getElementById('GRN_NO_MERGE').value = pGrnNo;
			document.getElementById('GRN_FILE_MERGE').value = pGrnFile;
			document.getElementById('SJGRN_FILE_MERGE').value = pSjGrnFile;
		}
		document.getElementById(vFormName).submit();
	}

	function MergeAllDocs(){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		getParameter();
		if (confirm("Merge ALL File Selected ?")){
			document.getElementById('STSPRS').value = "MERGE_ALL_FILE";
			document.getElementById('GRN_TO_MERGE_S').value = "<?php Print($GRN_TO_MERGE_S); ?>";
		}
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
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";

		getParameter();

		document.getElementById(vFormName).submit();
	}
	function NextRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
		var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		getParameter();
		document.getElementById('P_PAGE_NO').value =vPageNo;
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
		getParameter();
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value ="1";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		getParameter();
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		//alert("last");
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
				var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";
		//alert("last 1 " + vFormName);
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		getParameter();
		document.getElementById('P_PAGE_NO').value =vLastPageNo;
		document.getElementById(vFormName).submit();
	}
</script>
