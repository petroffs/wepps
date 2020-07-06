$(document).ready(function() {
	var obj = $('#pps_modal');
	var modalContent = $('#setEmailContent').clone();
	modalContent.find('input[type="text"]').eq(0).attr('id','setEmailCodeClone');
	obj.find('div.uk-modal-header').html('<h2>Установка нового E-mail</h2>');
	obj.find('div.uk-modal-footer').html(''+
			'<button class="uk-button" type="button">Закрыть</button> '+
			'<button class="uk-button uk-button-success" type="button">Далее</button>'+
			'');
	obj.find('div.modal-content').html(modalContent);
	UIkit.modal(obj).show();
	$('div.uk-modal-footer').find('button').eq(0).on('click',function() {
		UIkit.modal(obj).hide();
	});
	$('div.uk-modal-footer').find('button').eq(1).on('click',function() {
		layoutWepps.request('action=setSettings&form=emailForm&email='+$('#setEmailInput').val()+'&code='+$('#setEmailCodeClone').val(), '/ext/User/Request.php','');
		UIkit.modal(obj).hide();
	});
});