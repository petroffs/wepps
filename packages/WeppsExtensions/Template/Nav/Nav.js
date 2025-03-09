var navInit = function() {
	$('ul.header-nav').children('li').on('mouseenter', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).addClass('hover');
		$(this).find('ul').removeClass('w_hide');
	});
	$('ul.header-nav').children('li').on('mouseleave', function(event) {
		event.stopPropagation()
		event.preventDefault();
		$(this).removeClass('hover');
		$(this).find('ul').addClass('w_hide');
	});
	$('a#header-nav,a#footer-nav').on('click', function(e) {
		e.preventDefault()
		if ($(window).width()>810) {
			var el = $('nav.header-nav-wrapper');
			el.toggleClass('w_hide_off');
			return;
		}
		if ($(".w_nav").length!=0) {
			$(".w_nav").remove();
		} else {
			$('body').addClass('w_modal_parent');
			let popup = $('<div>');
			popup.id = 'w_nav';
			popup.addClass('w_nav');
			$('body').prepend(popup);
			popup.css('height', $( document ).height());
			let header = $('<section>');
			header.addClass('header-wrapper-top');
			header.append(("<div class=\"closer\"><i class=\"bi bi-x-lg\"></i></div>"));
			header.append("<div class=\"logo\"><a href=\"/\"><img src=\"/ext/Template/files/wepps-white.svg\" class=\"pps_image\"/></a></div>");
			popup.append(header);
			let nav = $('ul.header-nav').eq(0).clone();
			nav.addClass('w_header-nav');
			nav.removeClass('header-nav');
			popup.append(nav);
			popup.find('.closer').on('click', function() {
				$(this).closest('.w_nav').remove();
				$('body').removeClass('w_modal_parent');
			});
			popup.find('.has-childs').children('a').on('click',function(event) {
				event.preventDefault();
				$(this).toggleClass('open');
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

var searchInit = function() {	
	$('#header-search0').select2({ 
		maximumSelectionLength: 1,  
		placeholder: 'Поиск' }
	).on("select2:select", function(e) {
			let id = $(this).val();
			if (id==0) {
				return;
			}
			console.log(id);
		}
	).on("select2:opening",function(e) {
		$(this).siblings('.select2').find('input.select2-search__field').on('keyup', function(e) {
		   if(e.keyCode === 13) {
				//console.log($(this).val());
				$('#header-search-text').val($(this).val());
				$(this).closest('form').submit();			
		   } 
		});
	});
	
	$(document).off('mouseup');
	select2Ajax({
		id: '#header-search',
		url: '/ext/Products/Request.php?action=search',
		max: 1
	});
	$('#header-search').on("select2:select", function(e) {
			let id = $(this).val();
			if (id==0) {
				return;
			}
			console.log(id);
		}
	);
	$('#header-search')	.on("select2:opening",function(e) {
		$(this).siblings('.select2').find('input.select2-search__field').on('keyup', function(e) {
		   if(e.keyCode === 13) {
				//console.log($(this).val());
				$('#header-search-text').val($(this).val());
				$(this).closest('form').submit();			
		   } 
		});
	});
}

$(document).ready(function() {
	navInit();
	searchInit();
});