<section class="gallery-wrapper w_animate w_grid w_4col w_2col_view_medium w_1col_view_small w_gap">
	{foreach name=out item=item from=$elements}
	<section>
		<div class="gallery-img w_overflow">
			<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/medium{$item.FileUrl}" class="w_image w_pointer w_zoom" /></a>
		</div>
	</section>
	{/foreach}
</section>
<div class="w_interval_medium"></div>