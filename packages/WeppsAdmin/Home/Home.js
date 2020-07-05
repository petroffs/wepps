var readyAuthInit = function() {
	$('#auth-form').find('input[type="button"]').eq(0).on('click',function(event) {
		$(this).closest('form').submit();
	});
	
	$('*[data-url]').css('cursor','pointer');
	$('*[data-url]').on('click',function(e) {
		var href = $(this).data('url');
		location.href = href; 
	});
	$('#login').focus();
}

$(document).ready(readyAuthInit);