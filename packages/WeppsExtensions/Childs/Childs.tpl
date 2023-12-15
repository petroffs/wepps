<div class="ext-childs">
	<div class="items pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_medium pps_animate">
		{foreach name="out" item="item" from=$elements}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<div class="item pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small">
			<a href="{$language.link}{$item.UrlMenu|default:$item.Url}">
				<div class="img">
					{if $images.0}
					<img src="/pic/catdir{$images.0}" class="pps_image"/>
					{else}
					<img src="/ext/Template/files/noimage.png" class="pps_image"/>
					{/if}
				</div>
				<div class="text pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">
					<div class="title">
						{$item.Name}
					</div>
				</div>
			</a>
		</div>
		{/foreach}
	</div>
</div>
<div class="pps_interval_medium"></div>