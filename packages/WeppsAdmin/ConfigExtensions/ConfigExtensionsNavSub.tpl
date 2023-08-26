<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
	<div class="pps_flex_23 pps_flex_11_view_small">
		<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
			<div class="pps_flex_max pps_padding">
				<div class="descr">
					<div class="descr2">
						<div class="num3">{$ext.Descr}</div>
					</div>
					{foreach name="o" item="i" from=$ext.ENavArr}
					<div class="descr2">
						<div class="title3"><a href="/_pps/extensions/{$ext.Alias}/{$i.1}.html">{$i.0}</a></div>
						<div class="num3">{$i.1}</div>
					</div>
					{/foreach}
				</div>
			</div>
		</div>
	</div>
</div>