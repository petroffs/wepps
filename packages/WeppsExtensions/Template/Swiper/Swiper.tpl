<div class="swiper swiper-desktop w_hide_view_small" data-swiper-autoplay="2000">
	<div class="swiper-wrapper">
		{foreach name=out item=item key=key from=$carousel}
		<div class="swiper-slide">
			<img src="/pic/slider{$item.Image_FileUrl}">
		</div>
		{/foreach}
	</div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-pagination"></div>
</div>
<div class="swiper swiper-mobile w_hide w_block_view_small" data-swiper-autoplay="2000">
	<div class="swiper-wrapper">
		{foreach name=out item=item key=key from=$carousel}
		<div class="swiper-slide">
			<img src="/pic/sliderm{$item.ImageMobile_FileUrl}">
		</div>
		{/foreach}
	</div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-pagination"></div>
</div>
