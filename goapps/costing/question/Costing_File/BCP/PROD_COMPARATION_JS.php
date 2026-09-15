<?php include("../common_js.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {

  // =============================
  // CLEAR BUTTON
  // =============================
  document.querySelectorAll('.clear-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const area = this.closest('.drop-area');
      area.querySelector('.drop-content').innerHTML = '<p>Drop item di sini</p>';
      this.style.display = 'inline-block';
    });
  });


  // =============================
  // DRAG START (UNTUK DATA DINAMIS)
  // =============================
  document.addEventListener('dragstart', function(e) {

    if (e.target.classList.contains('list-item')) {

      // 🔥 Jika sedang select text → batalkan drag
      const selection = window.getSelection().toString();
      if (selection.length > 0) {
        e.preventDefault();
        return;
      }

      e.dataTransfer.setData('cyl_sys_id', e.target.dataset.id);
      e.dataTransfer.setData('cyl_name', e.target.dataset.cylName);
    }

  });



  // =============================
  // DROP AREA
  // =============================
  const dropAreas = document.querySelectorAll('.drop-area');

  dropAreas.forEach(area => {

    area.addEventListener('dragover', (e) => {
      e.preventDefault();
      area.classList.add('drag-over');
    });

    area.addEventListener('dragleave', () => {
      area.classList.remove('drag-over');
    });

    area.addEventListener('drop', (e) => {
  e.preventDefault();
  area.classList.remove('drag-over');

  const cylSysId = e.dataTransfer.getData('cyl_sys_id');
  const cylName = e.dataTransfer.getData('cyl_name');
  var vFormName ="<?php Print($FORM_NAME); ?>";
  var vUserName ="<?php Print($USER_NAME); ?>";

  // alert("cylName  "+cylName);

  const content = area.querySelector('.drop-content');

  // 🔄 Ubah teks jadi loading
  content.innerHTML = '<p>In progress...</p>';

  fetch('PROD_COMPARATION_VIEW_DTL.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'P_CYL_SYS_ID=' + encodeURIComponent(cylSysId)
          +'&FORM_NAME='  +vFormName
          +'&USER_NAME='  +vUserName
  })
  .then(response => response.text())
  .then(data => {
    // ✅ Tampilkan data (teks loading otomatis hilang karena innerHTML diganti)
    const headerDesc = area.querySelector('.drop-desc');
    headerDesc.textContent = ' : ' + cylName;
    content.innerHTML = data;
  })
  .catch(err => {
    console.error(err);
    content.innerHTML = '<p>Terjadi kesalahan</p>';
  });
});


  });

});
</script>


<script>
// =============================
// SIDEBAR & DROPDOWN
// =============================

var dropdown = document.getElementsByClassName("dropdown-btn");

for (let i = 0; i < dropdown.length; i++) {
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


<script>
// =============================
// FILTER & RENDER TABLE
// =============================

function firstDt() {
  let pageInput = document.getElementById("PAGE_NO");
  pageInput.value = 1;
  filterDt();
}

function nextDt() {
  let pageInput = document.getElementById("PAGE_NO");
  pageInput.value = parseInt(pageInput.value || 0) + 1;
  filterDt();
}

function prevDt() {
  let pageInput = document.getElementById("PAGE_NO");
  if (parseInt(pageInput.value || 0)!==1){
    pageInput.value = parseInt(pageInput.value || 0) - 1;
    filterDt();
  }
}

function lastDt() {
  let pageInput = document.getElementById("PAGE_NO");
  pageInput.value = document.getElementById("LAST_PAGE_NO").value;
  filterDt();
}

function filterDt() {
  // alert("test");
	let left_no = document.getElementById("FIND_LEFT_NO").value;
  let type = document.getElementById("FIND_TYPE").value;
  let find = document.getElementById("FIND_DATA").value;
  let page_no = document.getElementById("PAGE_NO").value;
	// alert("left_no "+left_no);
  fetch("PROD_COMPARATION_LIST_DATA.php?FIND_TYPE=" + encodeURIComponent(type)
                                        +"&FIND_DATA=" + encodeURIComponent(find)
                                        + "&FIND_LEFT_NO=" +encodeURIComponent(left_no)
                                        + "&PAGE_NO=" +encodeURIComponent(page_no)
			  )
    .then(res => res.json())
    .then(data => renderTable(data))
    .catch(err => console.error(err));
}

function renderTable(data) {
  let tbody = document.getElementById("tableBody");
  tbody.innerHTML = "";

  // ✅ Ambil total data
  let totalData = 0;
  if (data.length > 0) {
    totalData = data[0].TOT_DT;
    document.getElementById("LAST_PAGE_NO").value = Math.ceil(totalData / 5);
  }

  data.forEach(row => {

    let tr = document.createElement("tr");

		const cellStyle = `<?php echo getStyle($font,"center"); ?>`;

    tr.innerHTML = `
      <td>
					<div  class="list-item" style="font-size:11px;text-align:center;" draggable="true"
                data-id="${row.CYL_SYS_ID}"
                data-cyl-name="${row.CYL_NAME}"
          >
						${row.REC_NO ?? ""}
					</div>
			</td>
      <td>
					<div  class="list-item" style="font-size:11px;text-align:center;" draggable="true"
                data-id="${row.CYL_SYS_ID}"
                data-cyl-name="${row.CYL_NAME}"
          >
						${row.CYL_LEFT_NO ?? ""}
					</div>
			</td>
      <td>
					<div  class="list-item"  style="font-size:11px;text-align:center;"  draggable="true"
                data-id="${row.CYL_SYS_ID}"
                data-cyl-name="${row.CYL_NAME}"
          >
						${row.CYL_TYPE ?? ""}
					</div>
			</td>
      <td>
				<div class="list-item"  style="font-size:11px;text-align:center;" draggable="true"
            data-id="${row.CYL_SYS_ID}"
            data-cyl-name="${row.CYL_NAME}"
            >
					${row.CYL_NAME ?? ""}
				</div>
			</td>
      <td>
				<div class="list-item"  style="font-size:11px;text-align:center;" draggable="true"
            data-id="${row.CYL_SYS_ID}"
            data-cyl-name="${row.CYL_NAME}"
        >
					${row.CYL_SHADE_CODE ?? ""}
				</div>
			</td>
      <td>
					<div  class="list-item" style="font-size:11px;text-align:center;" draggable="true"
                data-id="${row.CYL_SYS_ID}"
                data-cyl-name="${row.CYL_NAME}"
          >
						${row.CYL_SHADE_NAME ?? ""}
					</div>
			</td>
      <td>
					<div class="list-item" style="font-size:11px;text-align:center;" draggable="true"
             data-id="${row.CYL_SYS_ID}"
             data-cyl-name="${row.CYL_NAME}"
          >
						${row.CYL_ITEM_CODE ?? ""}
					</div>
			</td>
    `;

    tbody.appendChild(tr);
  });
}


// =============================
// AUTO LOAD TABLE
// =============================
window.addEventListener("load", filterDt);

</script>
