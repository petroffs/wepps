var readyTabsInit = function() {
	$("ul.tabs").find("li").on('click', function(event) {
		$(this).siblings().removeClass('active');
		$(this).addClass('active');
		var items = $('div.tabs-content').find('div.tabs-item');
		items.removeClass('active');
		var item = items.get($(this).index());
		$(item).addClass('active');
	});
	$("ul.tabs").find("li").eq(0).trigger('click');
}

$(document).ready(readyTabsInit);