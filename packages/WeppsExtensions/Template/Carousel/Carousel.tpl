<div class="carousel-wrapper">
	<div class="carousel carousel-desktop">
		{foreach name=out item=item key=key from=$carousel}
		<img src="/pic/slider{$item.Image_FileUrl}">
		{/foreach}
	</div>
	<div class="carousel carousel-mobile">
		{foreach name=out item=item key=key from=$carousel}
		<img src="/pic/sliderm{$item.ImageMobile_FileUrl}">
		{/foreach}
	</div>
</div>