var readyConfigExtInit = function() {
	$('*[data-url]').css('cursor','pointer');
	$('*[data-url]').on('click',function(e) {
		var href = $(this).data('url');
		location.href = href; 
	});
};
$(document).ready(readyConfigExtInit);


