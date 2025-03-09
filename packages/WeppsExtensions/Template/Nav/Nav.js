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


var search2Init = function() {
	let suggestPage = 1;
	let suggestLoading = false;
	let hasMoreSuggestions = true;

	// Обработчик ввода
	$('#search-input').on('input', function() {
	  const query = $(this).val().trim();
	  
	  if(query.length > 2) {
	    suggestPage = 1;
	    hasMoreSuggestions = true;
	    loadSuggestions(query, true);
	  } else {
	    $('#suggestions').hide().empty();
	  }
	});

	// Загрузка подсказок
	function loadSuggestions(query, reset = false) {
	  if(suggestLoading || !hasMoreSuggestions) return;
	  
	  suggestLoading = true;
	  $('#suggestions .suggestions-loader').show();

	  $.ajax({
	    url: '/ext/Products/Request.php?action=search2',
	    method: 'POST',
	    data: {
	      action: 'search2',
	      query: query,
	      page: suggestPage,
	      limit: 15
	    },
	    success: function(response) {
	      const data = JSON.parse(response);
	      
	      if(reset) {
	        $('#suggestions').html(data.html);
	      } else {
	        $('#suggestions').append(data.html);
	      }
	      
	      hasMoreSuggestions = data.hasMore;
	      $('#suggestions').show();
	      suggestPage++;
	    },
	    complete: function() {
	      suggestLoading = false;
	      $('.suggestions-loader').hide();
	    }
	  });
	}

	// Скролл внутри блока подсказок
	$('#suggestions').scroll(function() {
	  const $this = $(this);
	  const scrollPosition = $this.scrollTop() + $this.innerHeight();
	  
	  if(scrollPosition >= $this[0].scrollHeight - 50 && hasMoreSuggestions) {
	    loadSuggestions($('#search-input').val().trim());
	  }
	});

	// Клик по подсказке
	$('#suggestions').on('click', '.suggestion-item', function() {
	  $('#search-input').val($(this).text());
	  $('#suggestions').hide();
	  loadResults(true);
	});
	
	let selectedIndex = -1; // Индекс выбранного элемента

	// Обработчик клавиатуры
	$('#search-input').on('keydown', function(e) {
	  const $suggestions = $('#suggestions .suggestion-item');
	  const suggestionsCount = $suggestions.length;
	  
	  // Стрелка вниз
	  if(e.key === 'ArrowDown') {
	    e.preventDefault();
	    selectedIndex = Math.min(selectedIndex + 1, suggestionsCount - 1);
	    updateSelection($suggestions);
	  }
	  
	  // Стрелка вверх
	  if(e.key === 'ArrowUp') {
	    e.preventDefault();
	    selectedIndex = Math.max(selectedIndex - 1, -1);
	    updateSelection($suggestions);
	  }
	  
	  // Enter
	  if(e.key === 'Enter') {
	    const selectedItem = $suggestions.eq(selectedIndex);
	    if(selectedItem.length) {
	      $('#search-input').val(selectedItem.text());
	      $('#suggestions').hide();
	      loadResults(true);
	    }
	  }
	});

	// Обновление выделения
	function updateSelection(items) {
	  items.removeClass('active');
	  
	  if(selectedIndex >= 0 && selectedIndex < items.length) {
	    items.eq(selectedIndex)
	      .addClass('active')
	      .get(0)
	      .scrollIntoView({ block: 'nearest' });
	  }
	}

	// Модифицируем обработчик ввода
	$('#search-input').on('input', function() {
	  selectedIndex = -1; // Сброс выбора при новом вводе
	  // ... остальной код обработчика ввода ...
	});

	// При открытии подсказок добавляем обработчик
	$('#suggestions').on('mouseenter', '.suggestion-item', function() {
	  selectedIndex = $(this).index();
	  updateSelection($('#suggestions .suggestion-item'));
	});
}

$(document).ready(function() {
	navInit();
	searchInit();
	search2Init();
});