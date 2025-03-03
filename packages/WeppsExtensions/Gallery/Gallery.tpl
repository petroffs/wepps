<section class="gallery-wrapper pps_animate w_grid w_4col w_2col_view_medium w_1col_view_small w_gap">
	{foreach name=out item=item from=$elements}
	<section>
		<div class="gallery-img pps_overflow">
			<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/catbig{$item.FileUrl}" class="pps_image pps_pointer pps_zoom" /></a>
		</div>
	</section>
	{/foreach}
</section>
<div class="pps_interval_medium"></div>