<section class="gallery-wrapper pps_flex pps_flex_row pps_flex_center pps_flex_row_str pps_flex_margin_large pps_animate">
	{foreach name=out item=item from=$elements}
	<section class="pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small">
		<div class="gallery-img pps_overflow">
			<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/catbig{$item.FileUrl}" class="pps_image pps_pointer pps_zoom" /></a>
		</div>
	</section>
	{/foreach}
</section>
<div class="pps_interval_medium"></div>