<script type='text/javascript'>

	function getParameter(){
		if (document.getElementById('CYL_TYPE')!==null ) {
      	CYL_TYPE = document.getElementById('CYL_TYPE');
      	document.getElementById('CYL_TYPE_S').value = CYL_TYPE.options[CYL_TYPE.selectedIndex].value;
  	}
		if (document.getElementById('CYL_SHADE_CODE')!==null ) {
      	CYL_SHADE_CODE = document.getElementById('CYL_SHADE_CODE');
      	document.getElementById('CYL_SHADE_CODE_S').value = CYL_SHADE_CODE.options[CYL_SHADE_CODE.selectedIndex].value;
  	}
  	if (document.getElementById('CYL_SHADE_NAME')!==null ) {
      	CYL_SHADE_NAME = document.getElementById('CYL_SHADE_NAME');
      	document.getElementById('CYL_SHADE_NAME_S').value = CYL_SHADE_NAME.options[CYL_SHADE_NAME.selectedIndex].value;
  	}

		if (document.getElementById('CYL_PRODUCT_QUALITY')!==null ) {
      	CYL_PRODUCT_QUALITY = document.getElementById('CYL_PRODUCT_QUALITY');
      	document.getElementById('CYL_PRODUCT_QUALITY_S').value = CYL_PRODUCT_QUALITY.options[CYL_PRODUCT_QUALITY.selectedIndex].value;
  	}

  	if (document.getElementById('CMY_LUSTURE')!==null ) {
      	CMY_LUSTURE = document.getElementById('CMY_LUSTURE');
      	document.getElementById('CMY_LUSTURE_S').value = CMY_LUSTURE.options[CMY_LUSTURE.selectedIndex].value;
  	}
  	if (document.getElementById('DENIER')!==null ) {
      	DENIER = document.getElementById('DENIER');
      	document.getElementById('DENIER_S').value = DENIER.options[DENIER.selectedIndex].value;
  	}
  	if (document.getElementById('FILAMENT')!==null ) {
      	FILAMENT = document.getElementById('FILAMENT');
      	document.getElementById('FILAMENT_S').value = FILAMENT.options[FILAMENT.selectedIndex].value;
  	}
  	if (document.getElementById('INTERMINGLING')!==null ) {
      	INTERMINGLING = document.getElementById('INTERMINGLING');
      	document.getElementById('INTERMINGLING_S').value = INTERMINGLING.options[INTERMINGLING.selectedIndex].value;
  	}
  	if (document.getElementById('CROSS_SECTION')!==null ) {
      	CROSS_SECTION = document.getElementById('CROSS_SECTION');
      	document.getElementById('CROSS_SECTION_S').value = CROSS_SECTION.options[CROSS_SECTION.selectedIndex].value;
  	}
  	if (document.getElementById('CUSTOMER')!==null ) {
      	CUSTOMER = document.getElementById('CUSTOMER');
      	document.getElementById('CUSTOMER_S').value = CUSTOMER.options[CUSTOMER.selectedIndex].value;
  	}
		// if (document.getElementById('LEFT_NO')!==null ) {
    //   	document.getElementById('LEFT_NO_S').value = document.getElementById('LEFT_NO').value;
  	// }
  	if (document.getElementById('HEATSET')!==null ) {
      	HEATSET = document.getElementById('HEATSET');
      	document.getElementById('HEATSET_S').value = HEATSET.options[HEATSET.selectedIndex].value;
  	}
	}

	function getParamSimulation(){
		if (document.getElementById('Delpack_name')!==null ) {
        	Delpack_name = document.getElementById('Delpack_name');
        	document.getElementById('Delpack_name_S').value = Delpack_name.options[Delpack_name.selectedIndex].value;
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
		var vFormName ="<?php Print($FORM_NAME); ?>";
		var CYL_TYPE_V="";

		var CYL_TYPE_S ="<?php Print($CYL_TYPE_S); ?>";
		var vSTSPRS ="<?php Print($STSPRS); ?>";
		getParameter();
		getParamSimulation();
		getParamPtySmltn();
		//alert( prs + " CUSTOMER_S " + document.getElementById('CYL_TYPE_S').value);

		if (document.getElementById('CYL_TYPE_S').value === "NULL" || document.getElementById('CYL_TYPE_S').value !== CYL_TYPE_S	) {
			document.getElementById('CUSTOMER_S').value = "";
			document.getElementById('CYL_SHADE_CODE_S').value = "";
			document.getElementById('CYL_SHADE_NAME_S').value = "";
			document.getElementById('DENIER_S').value = "";
			document.getElementById('FILAMENT_S').value = "";
			document.getElementById('INTERMINGLING_S').value = "";
			document.getElementById('CROSS_SECTION_S').value = "";
			document.getElementById('CMY_LUSTURE_S').value = "";
			document.getElementById('HEATSET_S').value = "";
		}

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

		//SIMULATION
		if ( vSTSPRS.substring(0, 11)==="SIMULATION "){
			prs = vSTSPRS;
		}

		document.getElementById('STSPRS').value = prs;
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
