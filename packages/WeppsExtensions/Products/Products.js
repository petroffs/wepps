var productsInit = function() {
	
	
	$('.optionsSort').find('select').on('change',function() {
		var sel = $(this).val();
		$.cookie("optionsSort", sel, { expires: 7, path: '/' });
		displayFilteredProducts(1);
	});
	$('div.paginator').find('a[data-page]').on('click',function(event) {
		event.preventDefault();
		var page = parseInt($(this).data('page'));
		displayFilteredProducts(page);
		
	});
}

$(document).ready(productsInit);

$(document).ready(function() {
	$('li.pps_expand').find('a').on('click', function(event) {
		event.preventDefault();
		var items = $(this).closest('ul').find('li')
		if (items.filter('.pps_hide').length != 0) {
			items.removeClass('pps_hide');
			$(this).text('Скрыть');
		} else {
			$('html, body').animate({
				scrollTop : items.parent().offset().top - 35
			}, 500);

			var href = $(this);
			setTimeout(function() {
				items.filter(function(index) {
					if (index >= 10)
						$(this).addClass('pps_hide');
				});
				href.parent().removeClass('pps_hide');
				href.text('Еще');
			}, 500);
		}
	});

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
	$('.pps.pps_checkbox').find('input[type="checkbox"]').on('change', function() {
		console.log(501)
		var last = $(this).closest('div.extFilters').data('id');
		var page = 1;
		var optionCount = $('.optionsCount').eq(0);
		optionCount.attr('data-last',last);
		optionCount.attr('data-check',$(this).prop('checked'));
		displayFilteredProducts(page);
	});
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

