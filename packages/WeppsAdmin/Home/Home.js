var readyAuthInit = function() {
	$('*[data-url]').css('cursor','pointer');
	$('*[data-url]').on('click',function(e) {
		var href = $(this).data('url');
		location.href = href; 
	});
	$('input[name="login"]').focus();
}

$(document).ready(readyAuthInit);