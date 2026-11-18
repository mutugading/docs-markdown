<!-- The Modal -->
<div class="modal" id="myModal">
	<input type="HIDDEN" name="STS_LOV" id="STS_LOV" style="height:0px" readonly>
	<div class="modal-dialog modal-xl">
		<div class="modal-content">
	<!-- Modal Header -->
				<table align="center" width="100%">
					<tr>
						<td align="center" width="20%">
							Find
							<input 	type="TEXT" name="MODAL_FIND_DATA" id="MODAL_FIND_DATA" style="width:100px;height:22px;text-align:left"
											onchange="findLovData()"
							>
						</td>
						<td align="center">
							<input type="TEXT" name="LABLE_MODAL" id="LABLE_MODAL" readonly
										style="width:250px;height:22px;text-align:center"
							>
						</td>
						<td align="right" width="20%">
							<button type="button" onclick="closeModal()">X</button>
						</td>
					</tr>
				</table style="width:99%" border="2" align="center">
	<!-- Modal Header -->
	<!-- Modal body -->
			<div class="modal-body">
					<div id="txtHint"></div>
			</div>
	<!-- Modal body -->
		</div>
	</div>
</div>
<!-- The Modal -->

<script>
	function closeModal(){
		var modal = document.getElementById('myModal');
	  modal.style.display = "none";

		Process('');
	}

	function findLovData(){
		var STS_LOV =  getTxtVal('STS_LOV');
		viewLovData(STS_LOV);
	}

	function ckLovData(pCK_Name,pCK_Val){
		var STS_LOV =  getTxtVal('STS_LOV');
		if (confirm("Select this Folder "+pCK_Val+"?")){
			if (STS_LOV==="VIEW_FOLDER"){
				document.getElementById('ETD_SYS_ID').value=pCK_Val;
				closeModal();
			}
		}
		document.getElementById(pCK_Name).checked=false
		//viewLovData(STS_LOV);
	}

	function viewLovData(pSTS_LOV){
		var USER_NAME = "<?php Print($USER_NAME); ?>";
		var FORM_NAME = "<?php Print($FORM_NAME); ?>";
		var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";
		var FIND_DATA = getTxtVal('MODAL_FIND_DATA');

		document.getElementById('STS_LOV').value = pSTS_LOV;
		if (pSTS_LOV==="VIEW_FOLDER"){
				document.getElementById('LABLE_MODAL').value = "List E-Filling Folder";
		}

		const xhttp = new XMLHttpRequest();
	  document.getElementById("txtHint").innerHTML = null;
	  xhttp.onload = function() {
	    document.getElementById("txtHint").innerHTML = this.responseText;
	  }
	  xhttp.open("GET", FORM_NAME+"_LOV_DATA.php?"
	                    +"FORM_NAME="+FORM_NAME
	                    +"&USER_NAME="+USER_NAME
	                    +"&P_MENU_ID="+P_MENU_ID
											+"&STS_LOV="+pSTS_LOV
	                    +"&FIND_DATA="+FIND_DATA
	                  );

	  xhttp.send();
	}


	function findLovGroup(){
		var STS_LOV = getTxtVal('STS_LOV');
		if (STS_LOV==="LOV_GRP"){
	    viewLovGroup();
	  }
	}

</script>
