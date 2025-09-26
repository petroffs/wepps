let layoutInit = function () {
	$('.w_radius').height($('.w_radius').width());
	$('.w_admin').find('a').on('click', function (event) {
		event.stopPropagation();
	});
};
$(document).ready(layoutInit);
//$(window).on('resize', readyLayoutInit);

class LayoutWepps {
	constructor(settings = {}) {
		if (settings != undefined) {
			this.settings = settings
		}
	};
	init() {
		$('body').removeClass('w_modal_parent');
		$('html').removeClass('w_overflow');
		$('.w_modal_wrapper').remove();
		$('.w_loader').remove();
		return 1;
	};
	remove() {
		let self = this;
		self.removeBefore();
		$('.w_modal').fadeOut(300, function () {
			self.init();
			self.removeAfter()
		});
		return 2;
	};
	removeBefore() {

	};
	removeAfter() {

	};
	modal(settings = {}) {
		let self = this;
		this.init();
		this.window = $('<div></div>');
		this.window.addClass('w_modal');
		this.window.attr('id', 'w_modal');
		this.closer = $('<div></div>');
		this.closer.addClass('w_modal_closer').addClass('bi').addClass('bi-x');
		this.window.append(this.closer);
		this.closer.on('click', function () {
			self.remove();
		});
		this.content = $('<div></div>');
		this.content.addClass('w_modal_content');
		this.window.append(this.content);
		switch (settings.size) {
			case 'small':
			case 'large':
				break;
			default:
				settings.size = 'medium';
				break;
		};
		this.window.addClass('w_modal_' + settings.size);
		this.back = $('<div></div>');
		this.back.addClass('w_modal_wrapper');
		this.back.addClass('w_modal_' + settings.size);
		this.back.append(this.window);
		this.body = $('body');
		this.body.addClass('w_modal_parent');
		this.body.prepend(this.back2);
		this.body.prepend(this.back);
		$('html').addClass('w_overflow');
		this.window.fadeIn();
		if (settings.content != undefined) {
			let clone = settings.content.clone();
			clone.removeClass('w_hide');
			clone.removeClass('w_hide_view_medium');
			clone.removeClass('w_hide_view_small');
			clone.removeClass('w_hide');
			this.content.html(clone);
		} else if (settings.url != undefined && settings.data != undefined) {
			settings.obj = this.content;
			this.request(settings);
		};
		$(document).off('keyup');
		$(document).keyup(function (e) {
			if (e.keyCode == 27) {
				self.remove();
			}
		});
		$(document).off('mouseup');
		$(document).mouseup(function (e) {
			if ($('.w_modal').has(e.target).length === 0 && $(e.target).hasClass('w_modal') == false && $(e.target).hasClass('w_loader') == false) {
				self.remove();
			}
		});
		return 1;
	};
	loader() {
		$('.w_loader').remove();
		let loader = $('<div class="w_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
		$('body').prepend(loader);
	};
	request(settings = {}) {
		let self = this;
		$("#w_ajax").remove();
		$.ajax({
			type: "POST",
			url: settings.url,
			data: settings.data,
			beforeSend: self.loader()
		}).done(function (responseText) {
			$('.w_loader').fadeOut();
			setTimeout(function () {
				$('.w_loader').remove();
			}, 500);
			if (settings.obj) {
				settings.obj.html(responseText);
				$("#w_ajax").remove();
			} else {
				var t = $("<div></div>");
				t.attr("id", "w_ajax");
				t.html(responseText);
				$(document.body).prepend(t);
				t.css('display', 'none');
				$("#w_ajax").remove();
			};
			self.requestAfter();
		});
		return 1;
	};
	requestAfter() {

	};
	handler(settings) {
		let obj = settings.obj;
		let event = settings.event;
		let fn = settings.fn;
		let delay = settings.delay ?? 300;
		let timeout = null;
		obj.off(event);
		obj.on(event, function (e) {
			let self = $(this);
			e.preventDefault();
			clearTimeout(timeout);
			timeout = setTimeout(() => {
				fn(self)
			}, delay);
		});
	};
};

class UtilsWepps {
	digit(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	};
};

let layoutWepps = new LayoutWepps();
let utilsWepps = new UtilsWepps();