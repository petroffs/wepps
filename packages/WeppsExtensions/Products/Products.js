var productsInit = function() {
	$filtersWepps = new FiltersWepps({
		filters : 'nav-filters'
	});
	$filtersWepps.init();
}

$(document).ready(productsInit);

$(document).ready(function() {
	/*
	$('.imgs-big').slick({
		slidesToShow : 1,
		slidesToScroll : 1,
		arrows : true,
		fade : false,
		asNavFor : '.imgs-prev'
	});
	$('.imgs-prev').slick({
		slidesToShow : 3,
		slidesToScroll : 1,
		asNavFor : '.imgs-big',
		arrows : false,
		dots : false,
		centerMode : false,
		focusOnSelect : true
	});
	*/
});

var displayFilteredProducts = function(page) {
	var optionCount = $('.optionsCount').eq(0);
	var last = optionCount.attr('data-last');
	var checked = optionCount.attr('data-check');
	var obj = $('div.products-wrapper').find('div.products-container').eq(0);
	obj.fadeOut();
	var obj = '<i class="uk-icon-refresh uk-icon-spin uk-icon-large refresh"></i>';
	$('div.products-wrapper').find('div.products-container').before(obj);
	var serialized = 'action=filters&url='+location.pathname+'&last='+last+'&checked='+checked+'&page='+page;
	var blockFilters = $('div.extFilters');
	$.each(blockFilters,function(key,value) {
		var labels =  $(value).find('.pps.pps_checkbox').find('input:checked');
		if (labels.length) {
			var str = '';
			$.each(labels, function(k, v) {
				str += $(v).attr('name')+',';
			});
			str = str.slice(0,-1);
			serialized += '&filter_' + $(value).data('id') + '=' + str;
		}
	});
	url = "/packages/WeppsExtensions/Products/Request.php";
	//ajaxExts(serialized, url);
	layoutWepps.request(serialized, url);
	$("html, body").animate({ scrollTop: 0 }, 600);
	//location.href = "#!"+serialized;
}

