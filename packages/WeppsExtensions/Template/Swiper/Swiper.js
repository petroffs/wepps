$(document).ready(function () {
	/* var slickOptions = {
		autoplay: true,
		adaptiveHeight: true,
		arrows: true,
		dots: true,
		fade: true,
		infinite: true
	};
	var carousel = ($(window).width() > 480) ? '.carousel' : '.carousel-mobile';
	$(carousel).slick(slickOptions).trigger('init');
	if ($(carousel).hasClass('slick-initialized')) {
		$('.carousel-wrapper').css('opacity', '1');
	} */

	var swiperDesktop = new Swiper(".swiper.swiper-desktop", {
		navigation: {
			nextEl: ".swiper-button-next",
			prevEl: ".swiper-button-prev",
		},
		pagination: {
			el: ".swiper-pagination",
			dynamicBullets: true,
		},
		autoplay: false,
		loop: true,
	});
	var swiperMobile = new Swiper(".swiper.swiper-mobile", {
		navigation: {
			nextEl: ".swiper-button-next",
			prevEl: ".swiper-button-prev",
		},
		pagination: {
			el: ".swiper-pagination",
			dynamicBullets: true,
		},
		autoplay: true,
		loop: true,
	});
});