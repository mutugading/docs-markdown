<?php include("../common_js.php"); ?>
<script type='text/javascript'>
	function getParameter(){
		document.getElementById('CYL_TYPE_S').value=getListVal('CYL_TYPE');
		document.getElementById('CYL_SHADE_CODE_S').value=getListVal('CYL_SHADE_CODE');
		document.getElementById('CYL_SHADE_NAME_S').value=getListVal('CYL_SHADE_NAME');
		document.getElementById('CMY_LUSTURE_S').value=getListVal('CMY_LUSTURE');
		document.getElementById('CYL_PRODUCT_QUALITY_S').value=getListVal('CYL_PRODUCT_QUALITY');
		document.getElementById('DENIER_S').value=getListVal('DENIER');
		document.getElementById('FILAMENT_S').value=getListVal('FILAMENT');
		document.getElementById('INTERMINGLING_S').value=getListVal('INTERMINGLING');
		document.getElementById('CROSS_SECTION_S').value=getListVal('CROSS_SECTION');
		document.getElementById('CUSTOMER_S').value=getListVal('CUSTOMER');
		document.getElementById('HEATSET_S').value=getListVal('HEATSET');
		document.getElementById('CYL_LEFT_NO_S').value=getTxtVal('CYL_LEFT_NO');
		document.getElementById('CMY_PRODUCTION_S').value=getListVal('CMY_PRODUCTION');
	}

	function getParamSimulation(){
		if (document.getElementById('Delpack_name')!==null ) {
        	Delpack_name = document.getElementById('Delpack_name');
        	Delpack_name_V = Delpack_name.options[Delpack_name.selectedIndex].value;
        	Delpack_name_T = Delpack_name.options[Delpack_name.selectedIndex].text;
        	document.getElementById('Delpack_name_S').value = Delpack_name_V;
    	}
    	if (document.getElementById('AX_WT')!==null ) {
    		document.getElementById('AX_WT_S').value = document.getElementById('AX_WT').value;
 			//alert("AX_WT "+document.getElementById('AX_WT').value);
    	}
    	if (document.getElementById('NO_OF_BOBBINS')!==null ) {
    		document.getElementById('NO_OF_BOBBINS_S').value = document.getElementById('NO_OF_BOBBINS').value;
    	}
	}

	function getParamPtySmltn(){
		//alert("Cek ");
		if (document.getElementById('SMLT_NORM_SPIN_10')!==null ) {
				document.getElementById('SMLT_NORM_SPIN_10_S').value = document.getElementById('SMLT_NORM_SPIN_10').value;
			}
			if (document.getElementById('SMLT_NORM_TX_10')!==null ) {
				document.getElementById('SMLT_NORM_TX_10_S').value = document.getElementById('SMLT_NORM_TX_10').value;
			}


			if (document.getElementById('SMLT_NORM_SPIN_11')!==null ) {
				document.getElementById('SMLT_NORM_SPIN_11_S').value = document.getElementById('SMLT_NORM_SPIN_11').value;
			}
			if (document.getElementById('SMLT_NORM_TX_11')!==null ) {
				document.getElementById('SMLT_NORM_TX_11_S').value = document.getElementById('SMLT_NORM_TX_11').value;
			}

			if (document.getElementById('SMLT_NORM_SPIN_55')!==null ) {
				document.getElementById('SMLT_NORM_SPIN_55_S').value = document.getElementById('SMLT_NORM_SPIN_55').value;
			}

			if (document.getElementById('SMLT_NORM_SPIN_72')!==null ) {
				document.getElementById('SMLT_NORM_SPIN_72_S').value = document.getElementById('SMLT_NORM_SPIN_72').value;
			}


			if (document.getElementById('SMLT_NORM_SPIN_71')!==null ) {
				document.getElementById('SMLT_NORM_SPIN_71_S').value = document.getElementById('SMLT_NORM_SPIN_71').value;
			}
			//alert("Cek 1");
	}

	function Process(prs){
		//alert(prs);
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var CYL_TYPE_V="";
		var vSTSPRS ="<?php Print($STSPRS); ?>";
		var CUSTOMER_S ="<?php Print($CUSTOMER_S); ?>";
		//alert("cek 1");
		getParameter();
		getParamSimulation();
		getParamPtySmltn();
		//alert("cek 2");
		//alert( prs + " CUSTOMER_S " + document.getElementById('CUSTOMER_S').value);

		if (document.getElementById('CUSTOMER_S').value === "NULL" || document.getElementById('CUSTOMER_S').value !== CUSTOMER_S	) {
			document.getElementById('CYL_TYPE_S').value = "";
			document.getElementById('CYL_SHADE_CODE_S').value = "";
			document.getElementById('CYL_SHADE_NAME_S').value = "";
			document.getElementById('DENIER_S').value = "";
			document.getElementById('FILAMENT_S').value = "";
			document.getElementById('INTERMINGLING_S').value = "";
			document.getElementById('CROSS_SECTION_S').value = "";
			document.getElementById('CMY_LUSTURE_S').value = "";
			document.getElementById('HEATSET_S').value = "";
		}
		//alert("cek 3");
		if (prs === "CLEAR_DATA"){
			vConfirm = confirm("Clear Data?");
			if(vConfirm === true) {
				document.getElementById('CYL_SHADE_NAME_S').value = "" ;
				document.getElementById('CUSTOMER_S').value = "";
				document.getElementById('CYL_TYPE_S').value  = "";
				document.getElementById('DENIER_S').value = "";
				document.getElementById('FILAMENT_S').value = "";
				document.getElementById('INTERMINGLING_S').value = "";
				document.getElementById('CROSS_SECTION_S').value = "";
				document.getElementById('CMY_LUSTURE_S').value = "";
				document.getElementById('HEATSET_S').value = "";
				vSTSPRS = "";
      } else {
      	prs = vSTSPRS;
      }
		}
		//alert("cek 4");
		//SIMULATION
		if ( vSTSPRS.substring(0, 11)==="SIMULATION "){
			prs = vSTSPRS;
		}
		//alert("cek 5");
		document.getElementById('STSPRS').value = prs;
		//alert("cek 6");
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
		document.getElementById('STSPRS').value = "<?php Print($STSPRS); ?>";	;
		document.getElementById('P_PAGE_NO').value =vPageNo;
		getParameter();
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
