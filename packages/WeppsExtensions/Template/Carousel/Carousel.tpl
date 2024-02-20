<div class="carouselContainer">
	<div class="carousel">
		{foreach name=out item=item key=key from=$carousel}
		<div class="item" style="background-image:url(/pic/slider{$item.Image_FileUrl});"></div>
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
	$('.carousel').slick(slickOptions);
	$(window).resize(function() {
		//	$('.carousel').slick('unslick');
		//	$('.carousel').slick(slickOptions);
	});
});
</script>
