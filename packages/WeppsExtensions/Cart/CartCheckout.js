var cartCheckoutInit = function() {
	let inputTimeout = null;
	formWepps.minmaxAfter = function(id,inputVal) {
		clearTimeout(inputTimeout);
		inputTimeout = setTimeout(() => {
			layoutWepps.request({
				data: 'action=edit&id=' + id + '&quantity='+inputVal+'&context=cart',
				url: '/ext/Cart/Request.php',
				obj: $('#cart-checkout')
			});
		}, 300);
	}
	formWepps.minmax();
}
$(document).ready(cartCheckoutInit);