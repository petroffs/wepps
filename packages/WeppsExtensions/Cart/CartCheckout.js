$(document).ready(() => {
	cart.init();
	let suggestionsRegions = new SuggestionsWepps({
		input: 'cart-region',
		action: 'cities',
		url: '/ext/Cart/Request.php'
	});
	suggestionsRegions.init();
	suggestionsRegions.afterSelectItem = function (self, suggestions, selectedIndex) {
		const selectedItem = suggestions.eq(selectedIndex);
		if (selectedItem.length && selectedIndex > -1) {
			$(self).val(selectedItem.text())
			layoutWepps.request({
				data: 'action=delivery&citiesId=' + selectedItem.data('id') + '&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-delivery-settings').eq(0)
			});
			//console.log(selectedItem.data('url'))
			//$(self.input).val(selectedItem.data('url'));
		}
	}
});
let cartDelivery = function() {
	$('#cart-delivery-settings').removeClass('w_hide');
	$('input[type="radio"][name="delivery"]').change(function (e) { 
		e.preventDefault();
		layoutWepps.request({
			data: 'action=payments&deliveryId=' + $(this).val() + '&context=cart',
			url: '/ext/Cart/Request.php',
			obj: $('#cart-payments-settings').eq(0)
		});
	});
}
let cartPayments = function() {
	$('#cart-payments-settings').removeClass('w_hide');
	$('input[type="radio"][name="payments"]').change(function (e) { 
		e.preventDefault();
		layoutWepps.request({
			data: 'action=shipping&paymentsId=' + $(this).val() + '&context=cart',
			url: '/ext/Cart/Request.php'
		});
	});
}