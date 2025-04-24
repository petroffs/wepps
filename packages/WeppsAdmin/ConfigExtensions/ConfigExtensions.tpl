<div class="way">
	<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/extensions/">Системные расширения</a></li>
		{if $way}
		{foreach name="out" item="item" key="key" from=$way}
		<li><a href="{$item.Url}">{$item.Name}</a></li>
		{/foreach}
		{/if}
	</ul>
</div>
<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin pps_animate">
	{$extsNavTpl}
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col">
		<div class="pps_flex_max">
			<h2>{$content.Name}</h2>
			{if $extTpl}
			{$extTpl}
			{else}	
			<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
				{foreach name="out" item="item" from=$exts}
				<div class="pps_flex_13 pps_flex_11_view_small">
					<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
						<div class="pps_flex_max pps_padding">
							<div class="title">{$item.Name}</div>
							<div class="descr">
								{$item.Descr}
							</div>
							{foreach name="o" item="i" from=$item.ENavArr}
							<div class="container">
								<div class="container-title"><a href="/_wepps/extensions/{$item.Alias}/{$i.1}.html">{$i.0}</a></div>
								<div class="container-descr">{$i.1}</div>
							</div>
							{/foreach}
						</div>
					</div>
				</div>
				{/foreach}
			</div>
			{/if}		
		</div>
	</div>
</div>
