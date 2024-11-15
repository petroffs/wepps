<div class="carousel-wrapper">
	<div class="carousel carousel-desktop">
		{foreach name=out item=item key=key from=$carousel}
		<img src="/pic/slider{$item.Image_FileUrl}">
		{/foreach}
	</div>
	<div class="carousel carousel-mobile">
		{foreach name=out item=item key=key from=$carousel}
		<img src="/pic/slider{$item.ImageMobile_FileUrl}">
		{/foreach}
	</div>
</div>

<script>
$(document).ready(function(){
	var slickOptions = {
		autoplay: true,
		adaptiveHeight: true,
		arrows:true,
		dots:true,
		fade:true,
		infinite: true
	}
	var carousel = ($(window).width()>480) ? '.carousel' : '.carousel-mobile';
	$(carousel).slick(slickOptions);
	$(window).resize(function() {
		carousel = ($(window).width()>480) ? '.carousel' : '.carousel-mobile';
		$(carousel).slick('unslick');
		$(carousel).slick(slickOptions);
	});
});
</script>
