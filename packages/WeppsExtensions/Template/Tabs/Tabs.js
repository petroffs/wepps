var readyTabsInit = function() {
	$("ul.w_tabs").find("li").on('click', function(event) {
		$(this).siblings().removeClass('active');
		$(this).addClass('active');
		var items = $('div.w_tabs_content').find('div.w_tabs_item');
		items.removeClass('active');
		var item = items.get($(this).index());
		$(item).addClass('active');
	});
	$("ul.w_tabs").find("li").eq(0).trigger('click');
};
$(document).ready(readyTabsInit);