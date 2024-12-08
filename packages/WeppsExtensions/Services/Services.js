var readyServicesInit = function() {
	$('.services-wrapper').find('a').on('click',function(e) {
		e.preventDefault();
		//console.log('d');
		
		layoutWepps.win({ size:'medium',content: $(this).find('.services-text')});
	});
}
$(document).ready(readyServicesInit);