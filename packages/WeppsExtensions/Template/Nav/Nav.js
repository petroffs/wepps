var readyNavInit = function() {
	$('ul.nav').children('li').on('mouseenter', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).addClass('hover');
		$(this).find('ul').removeClass('pps_hide');
	});
	$('ul.nav').children('li').on('mouseleave', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).removeClass('hover');
		$(this).find('ul').addClass('pps_hide');
	});
	
	$('a#navicon').on('click', function(e) {
		e.preventDefault()
		if ($(".pps_nav").length!=0) {
			console.log($(".pps_nav").length);
			$(".pps_nav").remove();
		} else {
			$('body').addClass('pps_nav_parent');
			var el = $('<div>');
			el.id = 'pps_nav';
			el.addClass('pps_nav');
			$('body').prepend(el);
			el.css('height', $( document ).height());
			var clone = $('ul.nav').eq(0).clone();
			clone.id = 'menuInnClone';
			el.append(clone);
			clone.before("<div class=\"close\"></div>")
			clone.before("<div class=\"logo\"><a href=\"/\"><img src=\"/ext/Template/files/wepps-logo-paddings-i.svg\" class=\"pps_image\"/></a></div>")
			el.find('.close').on('click', function() {
				$(this).parent().remove();
				$('body').removeClass('pps_nav_parent');
			});
			$('.hasChilds').children('a').on('click',function(event) {
				event.preventDefault();
				var t = $(this).closest('ul.nav').find('a');
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
}

$(document).ready(readyNavInit);