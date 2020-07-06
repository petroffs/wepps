var readyProfileInit = function() {
	$('.orders').find('.itemheader').on('click',function(event) {
		var obj = $('#pps_modal');
		var modalContent = $(this).find('.ordermore').html();
		obj.find('div.uk-modal-header').html('<h2>Заказ '+$(this).data('order')+'</h2>');
		obj.find('div.uk-modal-footer').html(''+
				'<button class="uk-button" type="button">Закрыть</button> '+
				'');
		obj.find('div.modal-content').html(modalContent).addClass('ordermore2');
		UIkit.modal(obj).show();
		$('div.uk-modal-footer').find('button').eq(0).on('click',function() {
			UIkit.modal(obj).hide();
		});
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
$(document).ready(readyProfileInit);