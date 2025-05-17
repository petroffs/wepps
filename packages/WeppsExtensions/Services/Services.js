var readyServicesInit = function() {
	$('.services-wrapper').find('a').on('click',function(e) {
		e.preventDefault();
		console.log($(this).find('.services-text'));
		layoutWepps.modal({ size:'medium',content: $(this).find('.services-text')});
	});
};
$(document).ready(readyServicesInit);