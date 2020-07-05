<div class="pps_flex_11 pps_flex pps_flex_row pps_flex_row_str pps_animate">
	<div class="pps_flex_11 way">
		<ul class="pps_list pps_flex pps_flex_row pps_flex_start">
			<li><a href="/_pps/">Главная</a></li>
			<li><a href="/_pps/extensions/">Системные расширения</a></li>
			{if $way}
			{foreach name="out" item="item" key="key" from=$way}
			<li><a href="{$item.Url}">{$item.Name}</a></li>
			{/foreach}
			{/if}
		</ul>
	</div>
	{$extsNavTpl}
	<div class="pps_flex_45 pps_flex_11_view_medium pps_flex pps_flex_col pps_padding centercontent">
		<div class="pps_border pps_flex_max pps_padding">
			<h2>{$content.Name}</h2>
			{if $ext}
			{$ext}
			{else}	
			<div class="lists-items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin">
				{foreach name="out" item="item" key="key" from=$exts}
				<div class="pps_flex_13 pps_flex_11_view_small">
					<div class="pps_flex pps_flex_col pps_bg_silver pps_height">
						<div class="pps_flex_max pps_padding">
							<div class="title">{$item.Name}</div>
							<div class="descr">
								<div class="descr2">
									<div class="num3">{$item.Descr}</div>
								</div>
								{foreach name="o" item="i" key="k" from=$item.ENav|explode:"\n"}
								{assign var="extlink" value=$i|strarr}
								<div class="descr2">
									<div class="title3"><a href="/_pps/extensions/{$item.KeyUrl}/{$extlink.1}.html">{$extlink.0}</a></div>
									<div class="num3">{$extlink.1}</div>
								</div>
								{/foreach}
							</div>
						</div>
					</div>
				</div>
				{/foreach}
			</div>
			{/if}		
		</div>
	</div>
</div>
