var productsInit = function() {
	filtersWepps = new FiltersWepps({
		filters : 'nav-filters'
	});
	filtersWepps.init();
}

$(document).ready(productsInit);

$(window).on('popstate', function(event) {
	filtersWepps.responseByState(event.originalEvent.state);
});

$(document).ready(function() {
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