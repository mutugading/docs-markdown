<?php session_start(); ?>
<?php
header('Cache-Control: no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0',false);
header('Pragma: no-cache');

include("../conOraOci.php");
include ("../common_function.php");
include ("FORM_NAME.php");

$MenuHeader1 = "Y";
$MenuUtamaHdr = "Y";
$MenuLogoutHdr = "Y";
$MenuPrint = "";
$MenuPrintHrefPdf = "";
$MenuPrintHrefXls = "";

include ("form_header.php");
include ("check_login.php");

/* ===============================
   DATA (BISA DIGANTI QUERY DB)
=================================*/

// $items = [
//   ["id" => 1, "name" => "Item 1"],
//   ["id" => 2, "name" => "Item 2"],
//   ["id" => 3, "name" => "Item 3"],
// ];

$details = ["A", "B", "C"];
?>
<!DOCTYPE html>
<html>
<head>
<?php
include("../menuStyle.php");
include("../head_component.php");
?>
<meta charset="UTF-8">

<style>
html, body {
  height: 100%;
  margin: 0;
}

.container {
  width: 100%;      /* penuh lebar layar */
  height: 100vh;     /* penuh tinggi layar */
  box-sizing: border-box;
  padding: 20px;
  display: flex;
  flex-direction: column;
}

.list {
  border: 1px dashed #ccc;
  padding: 20px;
  min-height: 150px;
}

.list-item {
  background-color: #f1f1f1;
  border: 1px solid #aaa;
  cursor: grab;
}

.details {
  display: flex;
  gap: 15px;          /* beri jarak lebih lega */
}

.drop-area {
  flex: 2;
  border: 2px dashed #ccc;
  min-height: 300px;
  height: 300px;              /* tinggi tetap */
  max-height: 300px;          /* batasi tinggi */
  overflow: auto;             /* munculkan scroll */
  padding: 10px;
  box-sizing: border-box;
  transition: background-color 0.2s;
}

.drop-area.drag-over {
  background-color: #e0f7fa;
}

.drop-title {
  font-weight: bold;
  margin-bottom: 10px;
}

.drop-area table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;   /* cegah melebar */
  word-wrap: break-word; /* teks panjang turun */
}

.drop-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.clear-btn {
  background: #ff5252;
  color: white;
  border: none;
  padding: 3px 8px;
  cursor: pointer;
  font-size: 12px;
  border-radius: 3px;
}

.clear-btn:hover {
  background: #d32f2f;
}
</style>
</head>

<body>
  <?php
  	include("menuData.php");
  ?>
<form action="<?php echo $FORM_NAME; ?>" method="post" name="<?php echo $FORM_NAME; ?>" id="<?php echo $FORM_NAME; ?>">
  <!-- ================= LIST ================= -->
  <div>
    <h3>Drag salah satu</h3>

    <div class="list" id="list">
      <?php
				// foreach($items as $item) {
				$SqlView = "select * from cst_yarn_left where rownum<=5";

				//if ($USER_NAME === "1949") {echo "$SqlView</br>";	}
				$rsView = oci_parse($conn,$SqlView);
				oci_execute ($rsView);
				while ($rowRsView = oci_fetch_array ($rsView, OCI_BOTH)) {
					$CYL_NAME = $rowRsView['CYL_NAME'] ?? "";
					$CYL_SYS_ID = $rowRsView['CYL_SYS_ID'] ?? "";

			?>
        <div class="list-item"
             draggable="true"
             data-id="<?php echo $CYL_SYS_ID; ?>">
          <?php echo $CYL_NAME; ?>
        </div>
      <?php
				}
			?>
    </div>
  </div>

  <!-- ================= DETAILS ================= -->
  <div class="details">
    <?php foreach($details as $detail) { ?>
			<div class="drop-area" id="detail-<?= strtolower($detail); ?>">
			  <div class="drop-header">
			    <span class="drop-title">Detail <?= $detail; ?></span>
			    <button type="button" class="clear-btn">Clear</button>
			  </div>
			  <div class="drop-content">
			    <p>Drop item di sini</p>
			  </div>
			</div>
    <?php } ?>
  </div>

</div>
<?php include ($FORM_NAME."_JS.php"); ?>
<script>
document.querySelectorAll('.clear-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const area = this.closest('.drop-area');
    area.querySelector('.drop-content').innerHTML = '<p>Drop item di sini</p>';
    // tampilkan tombol lagi jika sebelumnya di-hide
    this.style.display = 'inline-block';
  });
});

const items = document.querySelectorAll('.list-item');
const dropAreas = document.querySelectorAll('.drop-area');

items.forEach(item => {
  item.addEventListener('dragstart', (e) => {
    e.dataTransfer.setData('text/plain', item.dataset.id);
  });
});

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

  const id = e.dataTransfer.getData('text/plain');

  fetch('PROD_COMPARATION_VIEW_DTL.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'id=' + id
  })
  .then(response => response.text())
	.then(data => {
	  const content = area.querySelector('.drop-content');
	  content.innerHTML = data;

	  // opsional: hilangkan tombol Clear jika ingin
	  // area.querySelector('.clear-btn').style.display = 'none';
	});
});

});
</script>
</form>
</body>
</html>
