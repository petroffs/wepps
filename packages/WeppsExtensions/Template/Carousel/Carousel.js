$(document).ready(function(){
	var slickOptions = {
		autoplay: true,
		adaptiveHeight: true,
		arrows:true,
		dots:true,
		fade:true,
		infinite: true
	};
	var carousel = ($(window).width()>480) ? '.carousel' : '.carousel-mobile';
	$(carousel).slick(slickOptions).trigger('init');
	/* $(window).resize(function() {
		carousel = ($(window).width()>480) ? '.carousel' : '.carousel-mobile';
		$(carousel).slick('unslick');
	}); */
	if ($(carousel).hasClass('slick-initialized')) {
		$('.carousel-wrapper').css('opacity','1');
	}
});