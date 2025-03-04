/*layoutWeppsFilters = new LayoutWepps();
layoutWeppsFilters.call = function() {}*/

var productsInit = function() {
	filtersWepps = new FiltersWepps({
		filters : 'nav-filters'
	});
	filtersWepps.init();
	
	$('#pps-option-filters').off('click');
	$('#pps-option-filters').on('click',function(e) {
		e.preventDefault();
		layoutWepps.modal({ size:'large',content: $('.sidebar').eq(0)});
		productsInit();
	});
	
}

$(document).ready(productsInit);

$(window).on('popstate', function(event) {
	filtersWepps.responseByState(event.originalEvent.state);
});

$(document).ready(function() {
	$('#pps-option-filters').trigger('click');
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