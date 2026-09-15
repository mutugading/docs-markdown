<div class="details" style="overflow-x:auto;overflow-y:auto;">
	<?php foreach($details as $detail) { ?>
		<div class="drop-area" id="detail-<?= strtolower($detail); ?>">
			<div class="drop-header">
				<b><span class="drop-title" style="font-size:11px;color: #ff0000;">
						Product <?= $detail; ?>
						<span class="drop-desc" style="font-size:11px;color: #0000e6;"></span>
				</b>
				</span>
				<button type="button" class="clear-btn">Clear</button>
			</div>
			<div class="drop-content" >
				<p>Drop item di sini</p>
			</div>
		</div>
	<?php } ?>
</div>
