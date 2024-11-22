<div class="pps_interval"></div>
<div class="pps_overflow_auto">
	<section class="childs-wrapper pps_flex pps_flex_row pps_flex_row_str pps_flex_start pps_flex_margin_medium pps_animate">
		{foreach name="out" item="item" from=$elements}
		{assign var="images" value=$item.Images_FileUrl|strarr}
		<section class="item pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small">
			<a href="{$language.link}{$item.UrlMenu|default:$item.Url}">
				<div class="childs-img">
					{if $images.0}
					<img src="/pic/catdir{$images.0}" class="pps_image"/>
					{else}
					<img src="/ext/Template/files/noimage.png" class="pps_image"/>
					{/if}
				</div>
				<div class="childs-title pps_flex_23 pps_flex_12_view_medium pps_flex_11_view_small">
					{$item.Name}
				</div>
			</a>
		</section>
		{/foreach}
	</section>
</div>
<div class="pps_interval_medium"></div>