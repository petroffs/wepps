$(document).ready(function() {
	var obj = $('#pps_modal');
	obj.find('div.uk-modal-header').html('<h2>Удалить товар из корзины?</h2>');
	obj.find('div.uk-modal-footer').html(''+
			'<button class="uk-button" type="button">Отмена</button> '+
			'<button class="uk-button uk-button-danger" type="button">Удалить</button>'+
			'');
	UIkit.modal(obj).show();
	$('div.uk-modal-footer').find('button').eq(0).on('click',function() {
		UIkit.modal(obj).hide();
	});
	$('div.uk-modal-footer').find('button').eq(1).on('click',function() {
		UIkit.modal(obj).hide();
		ajaxExts('action=remove&id='+id,'/ext/Cart/Request.php');
	});
});