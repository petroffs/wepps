var readyExampleInit = function() {
	$('.elements.Example').eq(0).find('.item').on('click',function(e) {
		var href = $(this).find('a').eq(0);
		location.href = href.attr('href'); 
	}) ;
	/*
	$('.elements.Example').eq(0).find('.item').on('mouseenter',function(e) {
		var img = $(this).find('img').eq(0);
		img.attr('src',img.data('hover'));
	});
	$('.elements.Example').eq(0).find('.item').on('mouseleave',function(e) {
		var img = $(this).find('img').eq(0);
		img.attr('src',img.data('default'));
	});
	*/
}
$(document).ready(readyExampleInit);