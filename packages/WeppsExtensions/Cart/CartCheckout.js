$(document).ready(() => {
	cart.init();
	let suggestionsRegions = new SuggestionsWepps({
		input: 'cart-region',
		action: 'cities',
		url: '/ext/Cart/Request.php'
		//url : '/ext/Products/Request.php?action=suggestions'
	});
	suggestionsRegions.init();
	suggestionsRegions.afterSelectItem = function (self, suggestions, selectedIndex) {
		const selectedItem = suggestions.eq(selectedIndex);
		if (selectedItem.length && selectedIndex > -1) {
			//$(self).val(selectedItem.text())
			layoutWepps.request({
				data: 'action=delivery&cityId=' + selectedItem.data('id') + '&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-delivey-settings').eq(0)
			});
			//console.log(selectedItem.data('url'))
			//$(self.input).val(selectedItem.data('url'));
		}
	}
});