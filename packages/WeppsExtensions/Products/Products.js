var productsInit = function() {
	filtersWepps = new FiltersWepps({
		filters : 'nav-filters',
		sidebar : 'sidebar',
		content : 'content-wrapper',
		responseLoader : 'products-loader'
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