var navInit = function() {
	$('ul.header-nav').children('li').on('mouseenter', function(event) {
		event.stopPropagation();
		event.preventDefault();
		$(this).addClass('hover');
		$(this).find('ul').removeClass('w_hide');
	});
	$('ul.header-nav').children('li').on('mouseleave', function(event) {
		event.stopPropagation();
		event.preventDefault();
		$(this).removeClass('hover');
		$(this).find('ul').addClass('w_hide');
	});
	$('a#header-nav,a#footer-nav').on('click', function (e) {
		e.preventDefault();
		if ($(window).width() > 810) {
			var el = $('nav.header-nav-wrapper');
			el.toggleClass('w_hide_off');
			return;
		};
		var navClone = $('nav.header-nav-wrapper').clone();
		var themeSwitcher = $('.theme-switcher').eq(0).clone();
		navClone.append(themeSwitcher);
		layoutWepps.modal({ size: 'medium', content: navClone });
		$('#w_modal ul.header-nav li a').on('click', function (e) {
			let li = $(this).parent('li');
			if (li.find('ul').length > 0) {
				e.preventDefault();
				$(this).toggleClass('open');
				li.find('ul').toggleClass('w_hide');
			}
		});
		return;
	});
	$('#header-profile[data-auth="0"],#footer-profile[data-auth="0"]').on('click',function(e) {
		e.preventDefault();
		layoutWepps.modal({ size: 'medium', data: 'action=sign-in-popup', url: '/ext/Profile/Request.php' });
	});
	const el = document.querySelector("header");
	const observer = new IntersectionObserver( 
	  ([e]) => e.target.classList.toggle("is-pinned", e.intersectionRatio < 1),
	  { threshold: [1] }
	);
	observer.observe(el);
};

$(document).ready(function() {
	navInit();
	let suggestionsSearch = new SuggestionsWepps({
		input : 'search-input',
		action: 'suggestions',
		url : '/ext/Products/Request.php'
	});
	suggestionsSearch.init();
});