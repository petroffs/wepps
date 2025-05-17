var readyAccordionInit = function() {
	$('.accordion-items').find('h2').on('click',function(event) {
		let parent = $(this).parent();
		let active = $('.accordion-items').find('.active');
		if (parent.hasClass('active')) {
			parent.removeClass('active');
			parent.find('.text').slideUp();	
		} else {
			setTimeout(function() {
				parent.addClass('active');
				parent.find('.text').slideDown();
			},100);
		};
	});
};
$(document).ready(readyAccordionInit);