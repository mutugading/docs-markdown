<script type='text/javascript'>
	function periodParam(){
		document.getElementById('DATE_FROM_S').value = document.getElementById('DATE_FROM').value;
		document.getElementById('DATE_TO_S').value = document.getElementById('DATE_TO').value;
	}

	function whereCond(){
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
			//alert("ck 3 " + document.getElementById('FILTER_SCLT_DATE_S').value);
		}
	}

	function mergeFileLdn(){
		var vFileDN = document.getElementById('DnFile_S').value;
		//alert(vFileDN);
		if 	(vFileDN!==""){
        	if (vFileDN.indexOf("SALES")!==-1) {
        		document.getElementById('DnFile_S').value = vFileDN.substr(0,38)+vFileDN.substr(38);
        	}else{
	        	vConfirm = confirm("Press 'OK' for Blue LDN 'Cancel' for Green LDN");
	        	if (vConfirm === true){
	        		document.getElementById('DnFile_S').value = vFileDN.substr(0,38)+" SALES"+vFileDN.substr(38);
	        	}
	        }
        }
	}

	function Process(prs){
		var vFormName ="<?php Print($FORM_NAME); ?>";
		//alert(prs);
		if (prs=== "CLEAR"){
			prs="";
			document.getElementById('DATE_FROM').value = "";
			document.getElementById('DATE_TO').value = "";
		}
		//alert("ck 1");
		document.getElementById('STSPRS').value = prs;

		periodParam();
		whereCond();

		//alert("ck 4");
		if (prs.substr(0,10)==="VIEW_DATA "){
			var recNo = Number(prs.substr(10));
			//alert("recNo "+recNo);
			var vOST_KEY_NO = document.getElementsByName("OST_KEY_NO[]");
			var vINV_NO = document.getElementsByName("INV_NO[]");
	        for (var i=0;i < vOST_KEY_NO.length; i++){
	        	if (recNo===i) {
	        		document.getElementById('OST_KEY_NO_S').value = vOST_KEY_NO[i].value;
	        		document.getElementById('INV_NO_S').value = vINV_NO[i].value;
	        	}
            }
		}
		if (prs.substr(0,11)==="MERGE FILE "){
			var recNo = Number(prs.substr(11));
			var vDnFile = document.getElementsByName("DnFile[]");
			var vinvcFile = document.getElementsByName("invcFile[]");
			var vFpFile = document.getElementsByName("FpFile[]");
			var vOST_KEY_NO = document.getElementsByName("OST_KEY_NO[]");
			var vINV_NO = document.getElementsByName("INV_NO[]");
	        for (var i=0;i < vDnFile.length; i++){
	        	if (recNo===i) {
	        		document.getElementById('DnFile_S').value = vDnFile[i].value;
	        		document.getElementById('invcFile_S').value = vinvcFile[i].value;
	        		//alert("REc vFpFile "+i+" "+vFpFile[i].value);
	        		document.getElementById('FpFile_S').value = vFpFile[i].value;
	        		document.getElementById('OST_KEY_NO_S').value = vOST_KEY_NO[i].value;
	        		document.getElementById('INV_NO_S').value = vINV_NO[i].value;

	        	}
            }

			mergeFileLdn();
            //alert(document.getElementById('DnFile_S').value);

		}

	    //alert(prs.substr(0,13));
		if (prs.substr(0,13)==="MERGE FILEDTL"){
			//alert(prs.substr(13));
			var vTotDtl = prs.substr(13);
				//vTotDtl = number(vTotDtl)-1;

			//alert(prs+" Total Detail " + vTotDtl);
    		document.getElementById('OST_KEY_NO_S').value = "<?php Print($OST_KEY_NO_S); ?>";
    		document.getElementById('INV_NO_S').value = "<?php Print($INV_NO_S); ?>";
			var vFP_ADV = document.getElementsByName("FP_ADV[]");
	        var vFP_ADV_S = [];

	        var vFpSalesFile = document.getElementsByName("FpSalesFile[]");
	        var vFpSalesFile_S = [];

	        for (var i=0;i < vFP_ADV.length; i++){
            	vFP_ADV_S.push(vFP_ADV[i].value);
            	vFpSalesFile_S.push(vFpSalesFile[i].value);
	        }
	        //alert(document.getElementById('invcFile_S').value);
	        document.getElementById('invcFile_S').value = "<?php print($invcFile); ?>";
	        document.getElementById('DnFile_S').value = "<?php print($DnFile); ?>";

	        document.getElementById('FP_ADV_S').value = vFP_ADV_S;
	        document.getElementById('FpFile_S').value = vFpSalesFile_S;

	        mergeFileLdn();
            //alert(document.getElementById('DnFile_S').value);

		}

		if (prs.substr(0,13)==="VIEW_INV_FILE"){
			document.getElementById('invcFile_S').value = prs.substr(13);
		}
		if (prs.substr(0,18)==="VIEW_FP_SALES_FILE"){
			document.getElementById('FpFile_S').value = prs.substr(18);
		}
		if (prs.substr(0,13)==="VIEW_KWI_FILE"){
			document.getElementById('KwiFile_S').value = prs.substr(13);			
		}
		if (prs.substr(0,13)==="VIEW_LDN_FILE"){
			//alert (prs);
			//alert (prs.indexOf("SALES"));
			vFileDN = prs.substr(13);
			if (prs.indexOf("SALES")!==-1) {
				document.getElementById('DnFile_S').value = vFileDN.substr(0,59)+vFileDN.substr(59);
			} else {
				vConfirm = confirm("Press 'OK' for Blue LDN 'Cancel' for Green LDN ");
				if (vConfirm !== true){
					document.getElementById('DnFile_S').value = vFileDN;
				} else {
					document.getElementById('DnFile_S').value = vFileDN.substr(0,59)+" SALES"+vFileDN.substr(59);
				}
			}
		}

		//alert("ck 5");
		document.getElementById(vFormName).submit();
		//alert("ck 6");
	}

	function GoRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value =document.getElementById('PAGE_NO').value;
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		periodParam();
		whereCond();
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
		periodParam();
		whereCond();
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
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		periodParam();
		whereCond();
		document.getElementById('P_PAGE_NO').value =vPageNo;
		document.getElementById(vFormName).submit();
	}
	function FirstRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		document.getElementById('P_PAGE_NO').value ="1";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		periodParam();
		whereCond();
		document.getElementById(vFormName).submit();
	}

	function LastRecord() {
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var vPageNo  = document.getElementById('PAGE_NO').value;
        var vLastPageNo  ="<?php Print($P_LAST_PAGE_NO); ?>";

		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";
		periodParam();
		whereCond();

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
