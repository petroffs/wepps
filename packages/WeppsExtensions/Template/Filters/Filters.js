class FiltersWepps {
	constructor(settings = {}) {
		this.settings = settings;
		this.sidebar = $('.' + settings.sidebar);
		this.responseLoader = $('#' + settings.responseLoader);
		this.layout = new LayoutWepps();
		this.layout.removeBefore = function () {
			$('.' + settings.sidebar).detach().prependTo('.' + settings.content).addClass('w_hide_view_medium');
		}
	};
	init() {
		let self = this;
		$('#pps-options-sort').find('select').off('select2:select').on('select2:select', function (e) {
			var sel = $(this).val();
			$.cookie("wew_sort", sel, { expires: 365, path: '/' });
			self.response(1);
		});
		$('.paginator-wrapper').find('a[data-page]').off('click').on('click', function (e) {
			event.stopPropagation();
			event.preventDefault();
			var page = parseInt($(this).data('page'));
			self.response(page, 'top');
		});
		$('.pps.w_checkbox').find('input[type="checkbox"]').off('change').on('change', function (e) {
			event.preventDefault();
			let filters = $(this).closest('div.nav-filters');
			let last = filters.data('id');
			let page = 1;
			/*let checked = $(this).prop('checked');
			let y = $('.nav-filters[data-id="'+last+'"]').find('input[name="'+$(this).attr('name')+'"]');
			$.each(y,function(key,value) {
				$(value).prop('checked',checked);
			});*/
			self.sidebar.attr('data-last', last);
			self.sidebar.attr('data-check', $(this).prop('checked'));
			self.response(page);
		});
		$('.nav-filters-reset').find('input').off('click').on('click', function (e) {
			event.stopPropagation();
			event.preventDefault();
			let el = $('.sidebar').find('input[type="checkbox"]');
			el.prop('disabled', false);
			el.prop('checked', false);
			/*if ($('#products-sidebar').hasClass('w_hide_view_small')==false) {
				$('.products-sidebar-nav>a').trigger('click');
			}*/
			self.response(1, 'top');
		});
		$('.nav-filters-apply').find('input').off('click').on('click', function (e) {
			event.stopPropagation();
			event.preventDefault();
			self.layout.remove();
		});
		$('li.w_expand').find('a').off('click').on('click', function (event) {
			event.stopPropagation();
			event.preventDefault();
			var items = $(this).closest('ul').find('li');
			if (items.filter('.w_hide').length != 0) {
				items.removeClass('w_hide');
				$(this).text('Скрыть');
			} else {
				$('html, body').animate({
					scrollTop: items.parent().offset().top - 35
				}, 500);
				var href = $(this);
				setTimeout(function () {
					items.filter(function (index) {
						if (index >= 10 && !$(this).hasClass('w_expand')) {
							$(this).addClass('w_hide');
						}
					});
					href.text('Еще');
				}, 500);
			}
		});
		$('#pps-option-filters').off('click').on('click', function (e) {
			e.preventDefault();
			self.layout.modal({ size: 'large' });
			self.sidebar.detach().appendTo(".w_modal_content").removeClass('w_hide_view_medium');
		});
		return true;
	};
	response(page = 1, gotop, state) {
		let content = $('.' + this.settings.content);
		let last = this.sidebar.attr('data-last');
		let checked = this.sidebar.attr('data-check');
		let search = this.sidebar.attr('data-search');
		var state = (state) ? state : '';
		let serialized = 'action=filters&link=' + location.pathname + '&last=' + last + '&checked=' + checked + '&page=' + page + '&state=' + state + '&text=' + search;
		let filters = this.sidebar.find('.' + this.settings.filters);
		let url = this.sidebar.attr('data-url');
		$.each(filters, function (key, value) {
			var labels = $(value).find('.pps.w_checkbox').find('input:checked');
			if (labels.length) {
				var str = '';
				$.each(labels, function (k, v) {
					str += $(v).attr('name') + '|';
				});
				str = str.slice(0, -1);
				serialized += '&f_' + $(value).data('id') + '=' + str;
				var mytitle = $(this).find('.title')
			}
		});
		layoutWepps.request({ data: serialized, url: url, obj: this.responseLoader });
		if (gotop == 'top') {
			$("html, body").animate({ scrollTop: content.offset().top - $('header').height() }, 600);
			//$('label.pps.tooltipstered').tooltipster('destroy');
		}
	};
	responseByState(state) {
		if (!state) {
			return;
		};
		$('div.nav-filters').find('input[type="checkbox"]').prop('checked', false);
		$('div.nav-filters').find('.title').removeClass('active');
		this.sidebar.attr('data-last', state.last);
		this.sidebar.attr('data-check', state.checked);
		$.each(state, function (k, v) {
			if (k.substr(0, 2) == 'f_') {
				var ex = v.split(',');
				var key = k.substr(2);
				var filter = $('div.nav-filters-' + key);
				filter.children('.title').addClass('active');
				$.each(ex, function (num, value) {
					filter.find('input[name="' + value + '"]').prop('checked', true);
				});
			}
		});
		this.response(state.page, '', 'popstate');
	};
	test() {
		console.log('test');
	};
};