var readyNavInit = function() {
	$('ul.header-nav').children('li').on('mouseenter', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).addClass('hover');
		$(this).find('ul').removeClass('pps_hide');
	});
	$('ul.header-nav').children('li').on('mouseleave', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).removeClass('hover');
		$(this).find('ul').addClass('pps_hide');
	});
	
	$('a#header-nav').on('click', function(e) {
		e.preventDefault()
		if ($(".pps_nav").length!=0) {
			console.log($(".pps_nav").length);
			$(".pps_nav").remove();
		} else {
			$('body').addClass('w_modal_parent');
			var el = $('<div>');
			el.id = 'pps_nav';
			el.addClass('pps_nav');
			$('body').prepend(el);
			el.css('height', $( document ).height());
			var elHeader = $('<div>');
			elHeader.addClass('w_grid w_2col w_ai_center w_ji_end_view_small');
			elHeader.append("<div class=\"logo\"><a href=\"/\"><img src=\"/ext/Template/files/wepps-logo-i.svg\" class=\"pps_image\"/></a></div>");
			elHeader.append(("<div class=\"closer\"><i class=\"bi bi-x-lg\"></i></div>"));
			var clone = $('ul.header-nav').eq(0).clone();
			clone.id = 'header-nav-clone';
			el.append(clone);
			clone.before(elHeader);
			//clone.before("<div class=\"logo\"><a href=\"/\"><img src=\"/ext/Template/files/wepps-logo-paddings-i.svg\" class=\"pps_image\"/></a></div>")
			el.find('.closer').on('click', function() {
				$(this).closest('.pps_nav').remove();
				$('body').removeClass('w_modal_parent');
			});
			$('.has-childs').children('a').on('click',function(event) {
				event.preventDefault();
				var t = $(this).closest('ul.header-nav').find('a');
				t.removeClass('open');
				$(this).addClass('open');
				var el = $(this).siblings('ul');
				if (el.hasClass('pps_hide')) {
					el.removeClass('pps_hide');
					$(this).addClass('open');
				} else {
					el.addClass('pps_hide');
					$(this).removeClass('open');
				}
			});
		}
	});
	const el = document.querySelector("header")
	const observer = new IntersectionObserver( 
	  ([e]) => e.target.classList.toggle("is-pinned", e.intersectionRatio < 1),
	  { threshold: [1] }
	);
	observer.observe(el);
}

$(document).ready(readyNavInit);



