var readyTemplateInit = function() {
	$('*[data-url]').css('cursor','pointer');
	$('*[data-url]').on('click',function(e) {
		var href = $(this).data('url');
		location.href = href; 
	});
	/*
	$('#contactme').on('click',function(e) {
		layoutPPS.add('action=contactme','/ext/Addons/Request.php');
	});
	$('input[name="phone"]').inputmask({ "mask": "+ 7 (999) 999-99-99" });
	*/
	var approveform = function() {
		$('input[name="approve"]').on('change',function() {
			if ($(this).prop('checked')==true) {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled',false);
			} else {
				$(this).closest('form').find('input[type="submit"]').eq(0).prop('disabled','disabled');
			}
		});
	}
	approveform();
}
$(document).ready(readyTemplateInit);