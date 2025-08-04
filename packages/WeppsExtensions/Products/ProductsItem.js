var productsItemInit = function() {
	cart.addHandler();
	cart.addVariationsHandler();
	cart.favoritesHandler();
	cart.displayExists();
};
$(document).ready(productsItemInit);