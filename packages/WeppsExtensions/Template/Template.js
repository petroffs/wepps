var readyTemplateInit = function() {
	$('*[data-url]').css('cursor','pointer');
	$('*[data-url]').on('click',function(e) {
		var href = $(this).data('url');
		location.href = href; 
	});
	/*
	$('#contactme').on('click',function(e) {
		layoutWepps.add('action=contactme','/ext/Addons/Request.php');
	});
	$('input[name="phone"]').inputmask({ "mask": "+ 7 (999) 999-99-99" });
	*/
}
$(document).ready(readyTemplateInit);