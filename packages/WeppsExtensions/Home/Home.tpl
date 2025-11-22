<div class="page home">
	<section>
		<h1>{$content.Name}</h1>
		<div class="text">
			{$content.Text1}
		</div>
	</section>
</div>
<div class="page services">
	<section>
		<section class="services-wrapper w_grid w_4col w_2col_view_medium w_gap_large w_animate">
		{foreach name="out" key="key" item="item" from=$services}
			<section>
				<div class="services-img">
					<img src="/pic/preview/{$item.Images_FileUrl}" class="w_image"/>
				</div>
				<div class="title">{$item.Name}</div>
			</section>
		{/foreach}
		</section>
	</section>
</div>
<div class="page gallery">
	<section>
		<div class="w_interval_large"></div>
		<section class="gallery-wrapper w_animate w_grid w_4col w_3col_view_medium w_gap_large">
			{foreach name=out item=item from=$gallery}
			<section>
				<div class="img w_overflow">
					<a href="/pic/full{$item.FileUrl}" class="image-gallery"><img src="/pic/medium{$item.FileUrl}" class="w_image w_pointer w_zoom" /></a>
				</div>
			</section>
			{/foreach}
		</section>
		<div class="w_interval_large"></div>
	</section>
</div>
<div class="page advantages">
	<section class="w_overflow_auto">
		<div class="w_interval_large"></div>
		<section class="advantages-wrapper">
			{foreach name="out" key="key" item="item" from=$services}
			<section class="w_flex w_inline_flex w_flex_row w_flex_center w_flex_margin_large">
				<div class="img w_flex_25 w_flex_12_view_medium w_flex_11_view_small">
					<img src="/pic/preview/{$item.Images_FileUrl}" class="w_image"/>
				</div>
				<div class="text w_flex_25 w_flex_12_view_medium w_flex_11_view_small">
					<div class="title">{$item.Name}</div>
					<div class="text">{$item.Descr}</div>
				</div>
			</section>
			{/foreach}
		</section>
	</section>
</div>
<div class="page map">
	<section class="w_overflow_auto">
		{if $contacts.0.Address}
		<div class="param mapData w_hide" data-coord="{$contacts.0.LatLng}" data-title="{$contacts.0.Name}" data-descr="{$contacts.0.Address}">{$contacts.0.Address}</div>
		{/if}
		<section id="map"></section>
	</section>
</div>