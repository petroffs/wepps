var readyLayoutInit = function () {
	$('.w_radius').height($('.w_radius').width());
	$('.w_admin').find('a').on('click', function (event) {
		event.stopPropagation();
	});
};
$(document).ready(readyLayoutInit);
//$(window).on('resize', readyLayoutInit);

class LayoutWepps {
	constructor(settings = {}) {
		if (settings != undefined) {
			this.settings = settings
		}
	};
	init() {
		$('body').removeClass('w_win_parent');
		$('html').removeClass('w_overflow');
		$('.w_win_bg2').remove();
		$('.w_win_bg').remove();
		$('.w_loader').remove();
		return 1;
	};
	remove() {
		let self = this;
		$('.w_win_element').fadeOut(300, function () {
			self.init();
		});
		return 2;
	};
	win(settings = {}) {
		let self = this;
		this.init();
		this.window = $('<div></div>');
		this.window.addClass('w_win_element');
		this.window.attr('id', 'w_win_element');

		this.closer = $('<div></div>');
		this.closer.addClass('w_win_closer');
		this.window.append(this.closer);

		this.closer.on('click', function () {
			self.remove();
		});

		this.content = $('<div></div>');
		this.content.addClass('w_win_content');
		this.window.append(this.content);
		switch (settings.size) {
			case 'small':
				this.window.addClass('w_win_small');
				break;
			case 'large':
				this.window.addClass('w_win_large');
				break;
			default:
				this.window.addClass('w_win_medium');
				break;
		};
		this.back = $('<div></div>');
		this.back.addClass('w_win_bg');
		this.back.append(this.window);
		this.back2 = $('<div></div>');
		this.back2.addClass('w_win_bg2');
		this.body = $('body');
		this.body.addClass('w_win_parent');
		this.body.prepend(this.back2);
		this.body.prepend(this.back);
		$('html').addClass('w_overflow');
		this.window.fadeIn();
		if (settings.content != undefined) {
			let clone = settings.content.clone();
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
			if ($('.w_win_element').has(e.target).length === 0 && $(e.target).hasClass('w_win_element') == false && $(e.target).hasClass('w_loader') == false) {
				self.remove();
			};
		});
		return 1;
	};
	request(settings = {}) {
		let self = this;
		$("#w_ajax").remove();
		$.ajax({
			type: "POST",
			url: settings.url,
			data: settings.data,
			beforeSend: function () {
				$('.w_loader').remove();
				let loader = $('<div class="w_loader"><div class="lds-spinner"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>');
				$('body').prepend(loader);
			}
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
			self.call();
		});
		return 1;
	};
	call() {

	};
	token() {
		return storageWepps.get('wepps_token');
	};
};

class UtilsWepps {
	money(val) {
		return val.toString().replace(/(\d)(?=(\d{3})+(?:\.\d+)?$)/g, "$1 ");
	};
	cookie(name) {
		let matches = document.cookie.match(new RegExp(
			"(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
		));
		return matches ? decodeURIComponent(matches[1]) : null;
	};
	theme() {
		const savedTheme = localStorage.getItem('w_theme');
		const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		var theme = savedTheme;
		if (theme==='' || theme === 'auto') {
			theme = prefersDark ? 'dark' : 'light';
		}
		$('html').attr('data-theme', theme);
		this.applyThemeIcons(savedTheme);
		this.themeToggle();
		return theme;
	};
	themeToggle() {
		$('.w_theme_switcher').on('click', function () {
			const currentTheme = localStorage.getItem('w_theme') || 'light';
			let newTheme;
			if (currentTheme === 'light') {
				newTheme = 'dark';
			} else if (currentTheme === 'dark') {
				newTheme = 'auto';
			} else if (currentTheme === 'auto') {
				newTheme = 'light';
			} else {
				newTheme = 'light';
			}
			localStorage.setItem('w_theme', newTheme);
			// Apply theme
			const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
			let actualTheme;
			if (newTheme === 'auto') {
				actualTheme = prefersDark ? 'dark' : 'light';
			} else {
				actualTheme = newTheme;
			}
			$('html').attr('data-theme', actualTheme);
			utilsWepps.applyThemeIcons(newTheme);
		});
	};
	applyThemeIcons(theme) {
		$('.theme-icon-light').addClass('w_hide');
		$('.theme-icon-dark').addClass('w_hide');
		$('.theme-icon-auto').addClass('w_hide');
		if (theme === 'light') {
			$('.theme-icon-light').removeClass('w_hide');
		} else if (theme === 'dark') {
			$('.theme-icon-dark').removeClass('w_hide');
		} else if (theme === 'auto') {
			$('.theme-icon-auto').removeClass('w_hide');
		}
	};
};

var utilsWepps = new UtilsWepps();
var layoutWepps = new LayoutWepps();