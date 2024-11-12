<div class="page home">
	<div class="page2">
		<h1>{$content.Name}</h1>
		<div class="text">
			{$content.Text1}
		</div>
	</div>
</div>

<div class="page services">
	<div class="page2">
		<div class="services-wrapper pps_overflow_auto_1 pps_animate">
			<div class="pps_flex pps_flex_row pps_flex_row_str pps_flex_margin_large">
			{foreach name="out" key="key" item="item" from=$services}
				<section class="pps_flex_14">
					<div class="services-img">
						<img src="/pic/catprev/{$item.Images_FileUrl}" class="pps_image"/>
					</div>
					<div class="services-title">{$item.Name}</div>
				</section>
			{/foreach}
			</div>
		</div>
	</div>
</div>
<div class="page gallery">
	<div class="page2">
		<div class="pps_interval_large"></div>
		<div class="gallery-wrapper pps_flex pps_flex_row pps_flex_center pps_flex_row_str pps_flex_margin_large pps_animate">
			{foreach name=out item=item from=$gallery}
			<section class="pps_flex_14 pps_flex_12_view_medium pps_flex_11_view_small">
				<div class="gallery-img pps_overflow">
					<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/catbig{$item.FileUrl}" class="pps_image pps_pointer pps_zoom" /></a>
				</div>
			</section>
			{/foreach}
		</div>
		<div class="pps_interval_large"></div>
	</div>
</div>