<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
	<div class="pps_flex_23 pps_flex_11_view_small">
		<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
			<div class="pps_flex_max pps_padding">
				<div class="descr">
					{$ext.Descr}
				</div>	
				{foreach name="o" item="i" from=$ext.ENavArr}
				<div class="container">
					<div class="container-title"><a href="/_pps/extensions/{$ext.Alias}/{$i.1}.html">{$i.0}</a></div>
					<div class="container-descr">{$i.1}</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>