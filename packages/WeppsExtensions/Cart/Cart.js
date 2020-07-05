var cartTopUpdate = function(data) {
	$('#cartTopQty').removeClass('pps_hide');
	$('#cartTopQty').text(data.qtyTop);
	$('#cartTopPriceAmount').parent().removeClass('pps_hide');
	$('#cartTopPriceAmount').closest('.itm').addClass('active');
	$('#cartTopPriceAmount').text(data.priceAmountTop);
}

var readyCartInit = function() {
	$('a.remove').on('click',function(event) {
		event.preventDefault();
		var id = $(this).data('id');
		layoutPPS.add('action=removePromt&id='+id,'/ext/Cart/Request.php');
	});
	$('select.qtyselect').on('change',function(event) {
		event.stopPropagation();
		var id = $(this).data('id');
		layoutPPS.request('action=qty&id='+id+'&qty='+$(this).val(),'/ext/Cart/Request.php',$('#cart-wrapper'));
	});
	$('#orderCart').on('click',function(event) {
		event.preventDefault();
		if ($(this).data('auth')==1) {
			location.href='/cart/order.html';
		} else {
			//console.log(11)
			$('#signInTop').trigger('click');
		}
	});
}
$(document).ready(readyCartInit);