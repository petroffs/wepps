var readyAccordionInit = function() {
	$('.Accordion').find('.title').on('click',function(event) {
		var parent = $(this).parent();
		$('.Accordion').find('.active').removeClass('active');
		$('.Accordion').find('.descr').slideUp();
		setTimeout(function() {
			parent.find('.descr').slideDown();
			parent.addClass('active');
		},500);
		
		
	});
}
$(document).ready(readyAccordionInit);