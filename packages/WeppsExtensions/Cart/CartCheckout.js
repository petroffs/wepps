$(document).ready(() => {
	//cart.initCheckout();
	
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