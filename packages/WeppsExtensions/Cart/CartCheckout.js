var cartCheckoutInit = function() {
	let inputTimeout = null;
	formWepps.minmaxAfter = function(id,inputVal) {
		clearTimeout(inputTimeout);
		inputTimeout = setTimeout(() => {
			console.log('alex');
			layoutWepps.request({
				data: 'action=add&id=' + id + '&quantity='+inputVal+'&cart=1',
				url: '/ext/Cart/Request.php'
			});
		}, 300);
	}
	formWepps.minmax();
}
$(document).ready(cartCheckoutInit);