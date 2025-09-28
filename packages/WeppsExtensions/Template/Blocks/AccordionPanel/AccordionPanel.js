var blocksAccordionPanelInit = function() {
	$('.block-accordion').children('div.title').on('click',function(){
		var parent = $(this).parent();
		if (parent.hasClass('active')) {
			parent.removeClass('active');
			parent.children('.text').addClass('w_hide');
		} else {
			parent.addClass('active');
			parent.children('.text').removeClass('w_hide');
		};
	});
};
$(document).ready(blocksAccordionPanelInit);