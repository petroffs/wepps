var productsItemInit = function() {
	cart.addHandler();
	cart.favoritesHandler();
	if ($('.fotos-container').eq(0).hasClass('slick-initialized')) $('.fotos-container').eq(0).slick('unslick');
	var slickOptions = {
		autoplay : false,
		adaptiveHeight : false,
		arrows : false,
		dots : false,
		fade : false,
		infinite : true,
	};
	//$('.fotos-container').eq(0).slick(slickOptions);
	
	var slickOptions = {
		slidesToShow: 5,
		slidesToScroll: 1,
		autoplay : false,
		adaptiveHeight : false,
		arrows : false,
		dots : false,
		fade : false,
		infinite : true,
		asNavFor: '.fotos-container',
		variableWidth: false,
		centerMode: false,
		focusOnSelect: true
	};
	//$('.fotos-nav').eq(0).slick(slickOptions);
};
$(document).ready(productsItemInit);