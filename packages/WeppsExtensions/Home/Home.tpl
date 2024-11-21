<div class="page home">
	<section>
		<h1>{$content.Name}</h1>
		<div class="text">
			{$content.Text1}
		</div>
	</section>
</div>
<div class="page services">
	<section class="pps_overflow_auto">
		<section class="services-wrapper pps_flex pps_flex_row pps_flex_row_str pps_flex_margin_large">
			{foreach name="out" key="key" item="item" from=$services}
				<section class="pps_flex_14 pps_flex_12_view_medium">
					<div class="services-img">
						<img src="/pic/catprev/{$item.Images_FileUrl}" class="pps_image"/>
					</div>
					<div class="title">{$item.Name}</div>
				</section>
			{/foreach}
		</section>
	</section>
</div>
<div class="page gallery">
	<section>
		<div class="pps_interval_large"></div>
		<section class="gallery-wrapper pps_flex pps_flex_row pps_flex_center pps_flex_row_str pps_flex_margin_large pps_animate">
			{foreach name=out item=item from=$gallery}
			<section class="pps_flex_14 pps_flex_13_view_medium">
				<div class="img pps_overflow">
					<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/catbig{$item.FileUrl}" class="pps_image pps_pointer pps_zoom" /></a>
				</div>
			</section>
			{/foreach}
		</section>
		<div class="pps_interval_large"></div>
	</section>
</div>
<div class="page advantages">
	<section class="pps_overflow_auto">
		<div class="pps_interval_large"></div>
		<section class="advantages-wrapper">
			{foreach name="out" key="key" item="item" from=$services}
			<section class="pps_flex pps_inline_flex pps_flex_row pps_flex_center pps_flex_margin_large">
				<div class="img pps_flex_25 pps_flex_12_view_medium pps_flex_11_view_small">
					<img src="/pic/catprev/{$item.Images_FileUrl}" class="pps_image"/>
				</div>
				<div class="text pps_flex_25 pps_flex_12_view_medium pps_flex_11_view_small">
					<div class="title">{$item.Name}</div>
					<div class="text">{$item.Descr}</div>
				</div>
			</section>
			{/foreach}
		</section>
	</section>
</div>
<div class="page map">
	<section class="pps_overflow_auto">
		{if $contacts.0.Address}
		<div class="param mapData pps_hide" data-coord="{$contacts.0.LatLng}" data-title="{$contacts.0.Name}" data-descr="{$contacts.0.Address}">{$contacts.0.Address}</div>
		{/if}
		<section id="map"></section>
	</section>
</div>