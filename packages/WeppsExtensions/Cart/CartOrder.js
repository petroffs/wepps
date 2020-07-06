var readyCartOrderInit = function() {
	$('div.qty').on('click','button',function(event) {
		event.preventDefault();
		var id = $(this).data('id');
		var option = ($(this).html()=='+') ? 'add' : 'remove';
		layoutWepps.request('action=qty&id='+id+'&option='+option,'/ext/Cart/Request.php');
	});
	
	$('a.remove').on('click',function(event) {
		event.preventDefault();
		var id = $(this).data('id');
		layoutWepps.request('action=removePromt&id='+id,'/ext/Cart/Request.php',$('#pps_modal').find('div.modal-content').eq(0));
	});
	
	if ($( "#cities" ).length) {
		$( "#cities" ).autocomplete({
		      source: "/ext/Cart/Request.php?action=cities",
		      minLength: 2,
		      select: function( event, ui ) {
		    	  layoutWepps.request('action=delivery&city='+ui.item.Name+'&cityId='+ui.item.Id, '/ext/Cart/Request.php',$('#delivery'));
		    	  $('#payment').html('');
		    	  $('.cart-other').css('opacity',0.5);
		      }
		});
	}
}
$(document).ready(readyCartOrderInit);

var cartPriceAdd = function(total) {
	var delivery = $('input[name="delivery"]:checked').eq(0).attr('data-price');
	var payment = ($('input[name="payment"]:checked').length>0) ? $('input[name="payment"]:checked').eq(0).attr('data-price') : 0;
	var price = parseFloat(delivery) + parseFloat(payment)
	var price = layoutWepps.money(price.toString());
	//$('#priceDeliveryPayment').html(layoutWepps.money(price.toString()));
	
	
	if (price == 0) {
		$('#priceDeliveryPayment').html('(без учета доставки)');
		$('#priceDeliveryPayment2').html('(без учета доставки)');
	} else {
		$('#priceDeliveryPayment').html('(стоимость доставки &ndash;&nbsp;<span class="price"><span>'+price+'</span></span>)');
		$('#priceDeliveryPayment2').html('(стоимость доставки &ndash;&nbsp;<span class="price"><span>'+price+'</span></span>)');
	}
	
	$('#priceTotal').html(layoutWepps.money(total));
	$('#priceTotal2').html(layoutWepps.money(total));
	
	var about = $('#priceDeliveryPaymentBlock');
	about.effect('highlight');
}