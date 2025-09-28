<div class="lists-items w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin">
	<div class="w_flex_23 w_flex_11_view_small">
		<div class="w_flex w_flex_col w_bg_silver w_height">
			<div class="w_flex_max w_padding">
				<div class="descr">
					{$ext.Descr}
				</div>	
				{foreach name="o" item="i" from=$ext.ENavArr}
				<div class="container">
					<div class="container-title"><a href="/_wepps/extensions/{$ext.Alias}/{$i.1}.html">{$i.0}</a></div>
					<div class="container-descr">{$i.1}</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>
</div>