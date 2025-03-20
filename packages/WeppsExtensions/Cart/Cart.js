var cartTopUpdate = function(data) {
	$('#cartTopQty').removeClass('pps_hide');
	$('#cartTopQty').text(data.qtyTop);
	$('#cartTopPriceAmount').parent().removeClass('pps_hide');
	$('#cartTopPriceAmount').closest('.itm').addClass('active');
	$('#cartTopPriceAmount').text(data.priceAmountTop);
}

var cartInit = function() {
	$('.cart-add').on('click',function(e) {
		e.preventDefault();
		let id = $(this).data('id');
		console.log(id);
		layoutWepps.request({
			data:'action=add&id='+id,
			url:'/ext/Cart/Request.php'
		});
	});
}
$(document).ready(cartInit);