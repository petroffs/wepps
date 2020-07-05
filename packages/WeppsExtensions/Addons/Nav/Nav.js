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
	
	$('div.navico').on('click', function() {
		if ($(".hoverBox").length!=0) {
			console.log($(".hoverBox").length);
			$(".hoverBox").remove();
		} else {
			$('body').addClass('hoverBoxParent');
			var el = $('<div>');
			el.id = 'hoverBox';
			el.addClass('hoverBox');
			$('body').prepend(el);
			el.css('height', $( document ).height());
			var clone = $('ul.nav').eq(0).clone();
			clone.id = 'menuInnClone';
			el.append(clone);
			clone.before("<div class=\"close\"></div>")
			clone.before("<div class=\"logo\"><a href=\"/\"><img src=\"/ext/Template/files/logo.jpg\" class=\"pps_image\"/></a></div>")
			el.find('.close').on('click', function() {
				$(this).parent().remove();
				$('body').removeClass('hoverBoxParent');
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