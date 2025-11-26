var swiperManager = new SwiperManager();

$(document).ready(function () {
	swiperManager.init('.swiper.swiper-desktop', {});
	swiperManager.init('.swiper.swiper-mobile', { autoplay: { delay: 3000 } });
});