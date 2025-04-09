$(document).ready(()=>{
	cart.init();
	
	let suggestionsRegions = new SuggestionsWepps({
		input : 'cart-region',
		action: 'cities',
		url : '/ext/Cart/Request.php'
		//url : '/ext/Products/Request.php?action=suggestions'
	});
	suggestionsRegions.init();
	suggestionsRegions.afterSelectItem = function(self,suggestions,selectedIndex) {
			const selectedItem = suggestions.eq(selectedIndex);
			if (selectedItem.length && selectedIndex > -1) {
				console.log($(self).val(selectedItem.data('id')))
				//console.log(selectedItem.data('url'))
				//$(self.input).val(selectedItem.data('url'));
			}
		}
});