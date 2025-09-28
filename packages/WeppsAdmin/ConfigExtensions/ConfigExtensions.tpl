<div class="way">
	<ul class="w_list w_flex w_flex_row w_flex_start">
		<li><a href="/_wepps/">Главная</a></li>
		<li><a href="/_wepps/extensions/">Системные расширения</a></li>
		{if $way}
		{foreach name="out" item="item" key="key" from=$way}
		<li><a href="{$item.Url}">{$item.Name}</a></li>
		{/foreach}
		{/if}
	</ul>
</div>
<div class="w_flex w_flex_row w_flex_row_str w_flex_margin w_animate">
	{$extsNavTpl}
	<div class="w_flex_45 w_flex_11_view_medium w_flex w_flex_col">
		<div class="w_flex_max">
			<h2>{$content.Name}</h2>
			{if $extTpl}
			{$extTpl}
			{else}	
			<div class="lists-items w_flex w_flex_row w_flex_row_str w_flex_start w_flex_margin">
				{foreach name="out" item="item" from=$exts}
				<div class="w_flex_13 w_flex_11_view_small">
					<div class="w_flex w_flex_col w_bg_silver w_height">
						<div class="w_flex_max w_padding">
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
