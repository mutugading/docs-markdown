<?php include("../common_js.php"); ?>
<!-- The Modal -->
<div class="modal fade" id="myModal">
  <div style="width:95%">
    <div class="modal-content">
      <!-- Modal Header -->
      <div align="right">
        <button type="button" onclick="closeModal()">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        <div id="txtHint"></div>
      </div>

    </div>
  </div>
</div>
<!-- The Modal -->
<script type='text/javascript'>

  function PrsDt(pSTS_PRS,pCUST_CODE,pNM_CEK,pMEC_MAIL='',pMEC_SYS_ID='',pVAL_ACTIVE=''){

    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var vUSER_NAME = "<?php Print($USER_NAME); ?>";
    var vURL = vFORM_NAME+"_DB.php";
    var vConfirm = "";
    var vAJAX_PRS = "Y";
    var vREC_INFOCUS = getTxtVal('REC_INFOCUS');
    if (pSTS_PRS==="ADD_MAIL"){
        var vMail = getTxtVal('ADD_MAIL');
        if (vMail===""){
          alert("Mail can't Null");
          vAJAX_PRS = "N";
        }
        if (vAJAX_PRS === "Y"){
          vConfirm = " Add this Mail "+vMail+" ? ";
          if(!confirm(vConfirm)){
            vAJAX_PRS = "N";
          }
        }
        if (vAJAX_PRS === "Y"){
          $.ajax({
            url: vURL,
            type: "POST",
            cache: false,
            data:{
              USER_NAME: vUSER_NAME,
              FORM_NAME: vFORM_NAME,
              STS_PRS: pSTS_PRS,
              CUST_CODE: pCUST_CODE,
              MAIL: vMail
            },
            success: function(dataResult){
              var dataResult = JSON.parse(dataResult);
              alert(dataResult.Status);
              showLov('VIEW_DATA_MAIL',pCUST_CODE,vREC_INFOCUS);
            }
          });

        }
      document.getElementById(pNM_CEK).checked = false;
    } else if (pSTS_PRS==="RMV_MAIL"){
      if(!confirm('Remove this mail '+pMEC_MAIL+'')){
        vAJAX_PRS = "N";
      }
      if (vAJAX_PRS === "Y"){
        $.ajax({
          url: vURL,
          type: "POST",
          cache: false,
          data:{
            USER_NAME: vUSER_NAME,
            FORM_NAME: vFORM_NAME,
            STS_PRS: pSTS_PRS,
            MEC_SYS_ID: pNM_CEK
          },
          success: function(dataResult){
            var dataResult = JSON.parse(dataResult);
            alert(dataResult.Status);
            showLov('VIEW_DATA_MAIL',pCUST_CODE,vREC_INFOCUS);
          }
        });

      }
      document.getElementById(pNM_CEK).checked = false;
    } else if (pSTS_PRS==="ACTV_MAIL"){
      if (pVAL_ACTIVE==='Y'){
        vConfirm="Set deactived this mail "+pMEC_MAIL+"?";
      }else if (pVAL_ACTIVE==='N'){
        vConfirm="Set actived this mail "+pMEC_MAIL+"?";
      }
      // alert('Process Cek');
      if(!confirm(vConfirm)){
        vAJAX_PRS = "N";
      }
      if (vAJAX_PRS === "Y"){
        $.ajax({
          url: vURL,
          type: "POST",
          cache: false,
          data:{
            USER_NAME: vUSER_NAME,
            FORM_NAME: vFORM_NAME,
            STS_PRS: pSTS_PRS,
            MEC_SYS_ID: pMEC_SYS_ID,
            ACTIVE_VAL: pVAL_ACTIVE
          },
          success: function(dataResult){
            var dataResult = JSON.parse(dataResult);
            alert(dataResult.Status);
            showLov('VIEW_DATA_MAIL',pCUST_CODE,vREC_INFOCUS);
          }
        });
      }
      if (vAJAX_PRS === "N"){
        if (pVAL_ACTIVE==='Y'){
          document.getElementById(pNM_CEK).checked = true;
        }else if (pVAL_ACTIVE==='N'){
          document.getElementById(pNM_CEK).checked = false;
        }
      }
    }
  }

  function getParameter(){
    document.getElementById("ETD_SYS_ID_S").value=getListVal("ETD_SYS_ID");

    document.getElementById("FOLDER_VAL_1_S").value=getListVal('FOLDER_VAL_1');
    document.getElementById("FOLDER_VAL_1_V").value=getListText('FOLDER_VAL_1');

    document.getElementById("FOLDER_VAL_2_S").value=getListVal('FOLDER_VAL_2');
    document.getElementById("FOLDER_VAL_2_V").value=getListText('FOLDER_VAL_2');

    document.getElementById("FOLDER_VAL_3_S").value=getListVal('FOLDER_VAL_3');
    document.getElementById("FOLDER_VAL_3_V").value=getListText('FOLDER_VAL_3');

    document.getElementById("FOLDER_VAL_4_S").value=getListVal('FOLDER_VAL_4');
    document.getElementById("FOLDER_VAL_4_V").value=getListText('FOLDER_VAL_4');

    document.getElementById("FOLDER_VAL_5_S").value=getListVal('FOLDER_VAL_5');
    document.getElementById("FOLDER_VAL_5_V").value=getListText('FOLDER_VAL_5');

    document.getElementById("FIND_DATA_S").value=getTxtVal('FIND_DATA');

    // var FOLDER_VAL_1=  var FOLDER_VAL_1_V= ('FOLDER_VAL_1');
    // var FOLDER_VAL_2= getListVal('FOLDER_VAL_2'); var FOLDER_VAL_2_V= getListText('FOLDER_VAL_2');
    // var FOLDER_VAL_3= getListVal('FOLDER_VAL_3'); var FOLDER_VAL_3_V= getListText('FOLDER_VAL_3');
    // var FOLDER_VAL_4= getListVal('FOLDER_VAL_4'); var FOLDER_VAL_4_V= getListText('FOLDER_VAL_4');
    // var FOLDER_VAL_5= getListVal('FOLDER_VAL_5'); var FOLDER_VAL_5_V= getListText('FOLDER_VAL_5');
    //
    // var FIND_DATA =getTxtVal('FIND_DATA');

  }

	function closeModal(){
		var modal = document.getElementById('myModal');
		modal.style.display = "none";
    //showData();
    var REC_INFOCUS = getTxtVal('REC_INFOCUS');
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    getParameter();
    document.getElementById(FORM_NAME).submit();
	}

  function viewSql(){
    if (document.getElementById('DIV_SQL')!==null ) {
      if (document.getElementById('VIEW_SQL').checked === true){
        document.getElementById('DIV_SQL').style.visibility='visible';
        document.getElementById('DIV_SQL').style.height='100px';
      } else {
        document.getElementById('DIV_SQL').style.visibility='hidden';
        document.getElementById('DIV_SQL').style.height='0px';
      }
    }
  }

  function btnDelFile(pPRINT_LINK){
    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var vUSER_NAME = "<?php Print($USER_NAME); ?>";
    var vUSER_NAME_DTL = "<?php Print($USER_NAME_DTL); ?>";
    var vURL = vFORM_NAME+"_DB.php";
    var vAJAX_PRS = "Y";
    if(!confirm("Delete this File ?")){
      vAJAX_PRS = "N";
    }

    var vREMARK=document.getElementById("REMARK").value;
    alert(vREMARK);
    if (vREMARK===""){
      vAJAX_PRS = "N";
      Alert("Remark can't Null");
    }

    if (vAJAX_PRS === "Y"){
      alert("vURL "+vURL);

      alert
          ("vUSER_NAME_DTL "+vUSER_NAME_DTL
            +" vREMARK "+vREMARK
            +" vUSER_NAME "+vUSER_NAME
            +" vFORM_NAME "+vFORM_NAME
            +" pPRINT_LINK "+pPRINT_LINK
          );
      $.ajax({
        url: vURL,
        type: "POST",
        cache: false,
        data:{
          USER_NAME: vUSER_NAME
          ,FORM_NAME: vFORM_NAME
          ,STS_PRS: "DELETE_FILE"
          ,P_REMARK: vREMARK
          ,P_PRINT_LINK: pPRINT_LINK
          ,P_USER_NAME_DTL: vUSER_NAME_DTL
        },
        success: function(dataResult){
          var dataResult = JSON.parse(dataResult);
          alert(dataResult.Status);
          closeModal();
        }
      });
    }
  }

  function delFile(pNoFile,pFileNm,pPrintLink){
		var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

    var ETD_SYS_ID_S = "<?php Print($ETD_SYS_ID_S); ?>";

  	var FOLDER_VAL_1_S = "<?php Print($FOLDER_VAL_1_S); ?>";
  	var FOLDER_VAL_1_V = "<?php Print($FOLDER_VAL_1_V); ?>";

    var FOLDER_VAL_2_S = "<?php Print($FOLDER_VAL_2_S); ?>";
  	var FOLDER_VAL_2_V = "<?php Print($FOLDER_VAL_2_V); ?>";

    var FOLDER_VAL_3_S = "<?php Print($FOLDER_VAL_3_S); ?>";
  	var FOLDER_VAL_3_V = "<?php Print($FOLDER_VAL_3_V); ?>";

    var FOLDER_VAL_4_S = "<?php Print($FOLDER_VAL_4_S); ?>";
  	var FOLDER_VAL_4_V = "<?php Print($FOLDER_VAL_4_V); ?>";

    var FOLDER_VAL_5_S = "<?php Print($FOLDER_VAL_5_S); ?>";
  	var FOLDER_VAL_5_V = "<?php Print($FOLDER_VAL_5_V); ?>";

		const xhttp = new XMLHttpRequest();
		document.getElementById("txtHint").innerHTML = null;
		xhttp.onload = function() {
			document.getElementById("txtHint").innerHTML = this.responseText;
		}
		var FORM_NAME_MDL = FORM_NAME+"_LOV.php";

    //alert("GDM_DEPT_SLCT "+pGDM_DEPT_SLCT);
		xhttp.open("GET", FORM_NAME_MDL+"?"
											+"FORM_NAME="+FORM_NAME
											+"&USER_NAME="+USER_NAME
											+"&P_MENU_ID="+P_MENU_ID
											+"&STS_LOV=DEL_FILE"
                      +"&P_NO_FILE="+pNoFile
                      +"&P_FILE_NM="+pFileNm
                      +"&P_PRINT_LINK="+pPrintLink
                      +"&ETD_SYS_ID_S="+ETD_SYS_ID_S
                      +"&FOLDER_VAL_1_S="+FOLDER_VAL_1_S
                      +"&FOLDER_VAL_1_V="+FOLDER_VAL_1_V
                      +"&FOLDER_VAL_2_S="+FOLDER_VAL_2_S
                      +"&FOLDER_VAL_2_V="+FOLDER_VAL_2_V
                      +"&FOLDER_VAL_3_S="+FOLDER_VAL_3_S
                      +"&FOLDER_VAL_3_V="+FOLDER_VAL_3_V
                      +"&FOLDER_VAL_4_S="+FOLDER_VAL_4_S
                      +"&FOLDER_VAL_4_V="+FOLDER_VAL_4_V
                      +"&FOLDER_VAL_5_S="+FOLDER_VAL_5_S
                      +"&FOLDER_VAL_5_V="+FOLDER_VAL_5_V
							);

		xhttp.send();

    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;
	}

  function saveAdd(pStsPrs){
    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var vUSER_NAME = "<?php Print($USER_NAME); ?>";
    var vURL = vFORM_NAME+"_DB.php";
    var vAJAX_PRS = "Y";

    var AMSW_SCHEDULER_NAME_I = getTxtVal('AMSW_SCHEDULER_NAME_I');
    var AMSW_DESC_I = getTxtVal('AMSW_DESC_I');
    var AMSW_EXEC_TYPE_I = getListVal('AMSW_EXEC_TYPE_I');
    var AMSW_PATH_I = getTxtVal('AMSW_PATH_I');
    var AMSW_EXEC_TIME_I = getTxtVal('AMSW_EXEC_TIME_I');

    var AMSW_SYS_ID_I = getTxtVal('AMSW_SYS_ID_I');
    if(AMSW_SCHEDULER_NAME_I===""){
      alert("Scheduler Name can't null");
      vAJAX_PRS = "N";
    }
    if(AMSW_DESC_I==="" && vAJAX_PRS === "Y" ){
      alert("Scheduler Description can't null");
      vAJAX_PRS = "N";
    }
    if(AMSW_EXEC_TYPE_I==="" && vAJAX_PRS === "Y" ){
      alert("Execute Type can't null");
      vAJAX_PRS = "N";
    }
    if(AMSW_PATH_I==="" && vAJAX_PRS === "Y" ){
      alert("Path can't null");
      vAJAX_PRS = "N";
    }
    if(AMSW_EXEC_TIME_I==="" && vAJAX_PRS === "Y" ){
      alert("Execute Time can't null");
      vAJAX_PRS = "N";
    }
    if (vAJAX_PRS === "Y"){
      if (!confirm("Save this data ?")){
        vAJAX_PRS = "N";
      }
    }
    alert(pStsPrs);
    if (vAJAX_PRS === "Y"){
      $.ajax({
        url: vURL,
        type: "POST",
        cache: false,
        data:{
          USER_NAME: vUSER_NAME
          ,FORM_NAME: vFORM_NAME
          ,STS_PRS: pStsPrs
          ,AMSW_SCHEDULER_NAME: AMSW_SCHEDULER_NAME_I
          ,AMSW_DESC: AMSW_DESC_I
          ,AMSW_EXEC_TYPE: AMSW_EXEC_TYPE_I
          ,AMSW_PATH: AMSW_PATH_I
          ,AMSW_EXEC_TIME: AMSW_EXEC_TIME_I
          ,AMSW_SYS_ID: AMSW_SYS_ID_I
        },
        success: function(dataResult){
          var dataResult = JSON.parse(dataResult);
          alert(dataResult.Status);
          closeModal();
          showData();
        }
      });
    }
    document.getElementById("SAVE_ADD").checked = false;
	}

  function rmvDt(pIdCk,pName,pAMSW_SYS_ID){
    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var vUSER_NAME = "<?php Print($USER_NAME); ?>";
    var vURL = vFORM_NAME+"_DB.php";
    var vAJAX_PRS = "Y";

    if (!confirm("Delete this scheduler "+pName+" ?")){
      vAJAX_PRS = "N";
    }

    if (vAJAX_PRS === "Y"){
      $.ajax({
        url: vURL,
        type: "POST",
        cache: false,
        data:{
          USER_NAME: vUSER_NAME
          ,FORM_NAME: vFORM_NAME
          ,STS_PRS: "RMV_DT"
          ,AMSW_SYS_ID: pAMSW_SYS_ID
        },
        success: function(dataResult){
          var dataResult = JSON.parse(dataResult);
          alert(dataResult.Status);
          closeModal();
          showData();
        }
      });
    }
    document.getElementById(pIdCk).checked = false;
	}

  function ckAllMail(pAMSW_SYS_ID,pCK_STS){
    var vIdCk="CK_ALL_MAIL";
    var vFORM_NAME = "<?php Print($FORM_NAME); ?>";
    var vUSER_NAME = "<?php Print($USER_NAME); ?>";
    var vURL = vFORM_NAME+"_DB.php";
    var vSTS_CHECK="";
    //alert("status " + document.getElementById(vIdCk).checked);
    if (document.getElementById(vIdCk).checked===true){vSTS_CHECK="CHECK ALL";}
    var vConfirm="Re-Check All Mail ?";
    //alert("vSTS_CHECK "+pCK_STS);
    if (vSTS_CHECK==="CHECK ALL"){vConfirm="Check All Mail ?";}

    if(confirm(vConfirm)){
      $.ajax({
        url: vURL,
        type: "POST",
        cache: false,
        data:{
          USER_NAME: vUSER_NAME
          ,FORM_NAME: vFORM_NAME
          ,STS_PRS: "CK_ALL_MAIL"
          ,AMSW_SYS_ID: pAMSW_SYS_ID
          ,STS_CHECK: vSTS_CHECK
        },
        success: function(dataResult){
          var dataResult = JSON.parse(dataResult);
          alert(dataResult.Status);
          closeModal();
          showData();
        }
      });
    } else {
      if (vSTS_CHECK==="CHECK ALL"){
        document.getElementById(vIdCk).checked = false;
      } else {
        document.getElementById(vIdCk).checked = true;
      }
    }
	}

  function editDt(pREC_INFOCUS,pAMSW_SYS_ID) {
		var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

		const xhttp = new XMLHttpRequest();
		document.getElementById("txtHint").innerHTML = null;
		xhttp.onload = function() {
			document.getElementById("txtHint").innerHTML = this.responseText;
		}

		var FORM_NAME_MDL = FORM_NAME+"_LOV.php";
    //alert("GDM_DEPT_SLCT "+pGDM_DEPT_SLCT);
		xhttp.open("GET", FORM_NAME_MDL+"?"
											+"FORM_NAME="+FORM_NAME
											+"&USER_NAME="+USER_NAME
											+"&P_MENU_ID="+P_MENU_ID
											+"&STS_LOV=EDIT_DT"
                      +"&pAMSW_SYS_ID="+pAMSW_SYS_ID
                      +"&REC_INFOCUS="+pREC_INFOCUS
							);

		xhttp.send();

    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;

	}

  function reSend(pName,pPath) {
    if (confirm("Re-Process this Scheduler " + pName + "?")){
      //alert(pPath);
      window.open(pPath);
    }
  }
  function showLov(pStsLov,pAMSW_SYS_ID,pGDM_DEPT_SLCT='',pREC_INFOCUS=''){
		var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

		const xhttp = new XMLHttpRequest();
    var vFIND_LOV = getTxtVal('FIND_LOV');
		document.getElementById("txtHint").innerHTML = null;
		xhttp.onload = function() {
			document.getElementById("txtHint").innerHTML = this.responseText;
		}

		var FORM_NAME_MDL = FORM_NAME+"_LOV.php";

    //alert("GDM_DEPT_SLCT "+pGDM_DEPT_SLCT);
		xhttp.open("GET", FORM_NAME_MDL+"?"
											+"FORM_NAME="+FORM_NAME
											+"&USER_NAME="+USER_NAME
											+"&P_MENU_ID="+P_MENU_ID
											+"&STS_LOV="+pStsLov
                      +"&FIND_LOV="+vFIND_LOV
                      +"&pAMSW_SYS_ID="+pAMSW_SYS_ID
                      +"&pGDM_DEPT_SLCT="+pGDM_DEPT_SLCT
                      +"&REC_INFOCUS="+pREC_INFOCUS
							);

		xhttp.send();
    document.getElementById("REC_INFOCUS").value = pREC_INFOCUS;
	}

  function showData(){
    //alert('test');
    var USER_NAME = "<?php Print($USER_NAME); ?>";
    var FORM_NAME = "<?php Print($FORM_NAME); ?>";
    var P_MENU_ID = "<?php Print($P_MENU_ID); ?>";

    // const xhttp = new XMLHttpRequest();
    // document.getElementById("lable").innerHTML = "Please Wait";
    // document.getElementById("dataHint").innerHTML = null;
    // xhttp.onload = function() {
    //   document.getElementById("dataHint").innerHTML = this.responseText;
    //   document.getElementById("lable").innerHTML = null;
    //   //document.getElementById(REC_INFOCUS).focus();
    //   document.getElementById("ETD_SYS_ID_S").value = pREC_INFOCUS;
    // }
    // var FORM_NAME_MDL = FORM_NAME+"_SHOW.php";
    // //alert("FORM_NAME_MDL "+FORM_NAME_MDL);
    // xhttp.open("GET", FORM_NAME_MDL+"?"
    //                   +"FORM_NAME="+FORM_NAME
    //                   +"&P_MENU_ID="+P_MENU_ID
    //                   +"&USER_NAME="+USER_NAME
    //                   +"&ETD_SYS_ID_S="+ETD_SYS_ID
    //                   +"&FOLDER_VAL_1_S="+FOLDER_VAL_1
    //                   +"&FOLDER_VAL_1_V="+FOLDER_VAL_1_V
    //                   +"&FOLDER_VAL_2_S="+FOLDER_VAL_2+"&FOLDER_VAL_2_V="+FOLDER_VAL_2_V
    //                   +"&FOLDER_VAL_3_S="+FOLDER_VAL_3+"&FOLDER_VAL_3_V="+FOLDER_VAL_3_V
    //                   +"&FOLDER_VAL_4_S="+FOLDER_VAL_4+"&FOLDER_VAL_4_V="+FOLDER_VAL_4_V
    //                   +"&FOLDER_VAL_5_S="+FOLDER_VAL_5+"&FOLDER_VAL_5_V="+FOLDER_VAL_5_V
    //                   +"&FIND_DATA_S="+FIND_DATA
    //                 );
    // xhttp.send();

    getParameter();

    document.getElementById(FORM_NAME).submit();
    //viewSql();
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

	function GoRecord(pPageNo) {
    setTxtVal('PAGE_NO',pPageNo);
    showData();
	}
	function NextRecord() {
    var vPageNo  =  getTxtVal('PAGE_NO');//document.getElementById('PAGE_NO').value;
    var vLastPageNo = getTxtVal('P_LAST_PAGE_NO');
		if (parseInt(vPageNo) < parseInt(vLastPageNo) ){
			vPageNo = parseInt(vPageNo) + 1;
		}
    GoRecord(vPageNo);
	}

	function PrevRecord() {
    var vPageNo  =  getTxtVal('PAGE_NO');//document.getElementById('PAGE_NO').value;
		if (parseInt(vPageNo) > 1  ){
			vPageNo = parseInt(vPageNo) - 1;
		}
    GoRecord(vPageNo);
	}

	function FirstRecord() {
    GoRecord(1);
	}

	function LastRecord() {
    var vLastPageNo = getTxtVal('P_LAST_PAGE_NO');
    GoRecord(vLastPageNo);
	}

</script>
