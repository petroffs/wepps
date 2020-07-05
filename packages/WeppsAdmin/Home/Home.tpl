
<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_row_str">
	<div class="pps_flex_11 way">
		<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
			<li>&nbsp;</li>
		</ul>
	</div>
	<div class="pps_flex_11 pps_flex_11_view_medium pps_flex pps_flex_col pps_padding centercontent">
		<div class="pps_border pps_flex_max pps_padding">
			<h2>{$content.Name}</h2>
			<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
				{foreach name="out" item="item" key="key" from=$navhome}
				<div class="pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small" data-url="/_pps/{$key}/">
					<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
						<div class="pps_flex_max pps_padding">
							<div class="title">{$item.Name}</div>
						</div>
					</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>
