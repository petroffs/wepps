var productsInit = function() {
	filtersWepps = new FiltersWepps({
		filters : 'nav-filters',
		sidebar : 'sidebar',
		content : 'content-wrapper'
	});
	filtersWepps.init();
	cart.addHandler();
	cart.favoritesHandler();
	cart.displayExists();
};

$(document).ready(productsInit);

$(window).on('popstate', function(event) {
	filtersWepps.responseByState(event.originalEvent.state);
});

$(document).ready(function() {
	//$('#pps-option-filters').trigger('click');
	/*
	$('.imgs-big').slick({
		slidesToShow : 1,
		slidesToScroll : 1,
		arrows : true,
		fade : false,
		asNavFor : '.imgs-prev'
	});
	$('.imgs-prev').slick({
		slidesToShow : 3,
		slidesToScroll : 1,
		asNavFor : '.imgs-big',
		arrows : false,
		dots : false,
		centerMode : false,
		focusOnSelect : true
	});
	*/
});