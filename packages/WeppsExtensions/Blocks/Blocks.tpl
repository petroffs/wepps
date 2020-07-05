{foreach name="panels" item="panel" from=$panels}
<div class="pps_panel">
	<div class="wrapper">
		<div
			class="pps_blocks pps_sortable pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
			{foreach name="blocks" item="block" from=$blocks[$panel.Id]}
			<div class="pps_block pps_flex_13">{$block.Name}</div>
			{/foreach}
			
		</div>
	</div>
</div>
{/foreach}

{*
<div class="pps_panel">
	<div class="wrapper">
	<div class="pps_blocks pps_sortable pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
		<div class="pps_block pps_flex_13">1</div>
		<div class="pps_block pps_flex_13">2</div>
		<div class="pps_block pps_flex_13">3</div>
		<div class="pps_block pps_flex_13">4</div>
		<div class="pps_block pps_flex_13">5</div>
		<div class="pps_block pps_flex_13">6</div>
		<div class="pps_block pps_flex_13">7</div>
		<div class="pps_block pps_flex_13">8</div>
		<div class="pps_block pps_flex_13">9</div>
		<div class="pps_block pps_flex_13">10</div>
		<div class="pps_block pps_flex_13">11</div>
		<div class="pps_block pps_flex_13">12</div>
	</div>
	</div>
	<div class="wrapper">
	<div class="pps_blocks pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
		<div class="pps_block pps_flex_14">4</div>
		<div class="pps_block pps_flex_14">5</div>
		<div class="pps_block pps_flex_14">6</div>
	</div>
	</div>
</div>
*}