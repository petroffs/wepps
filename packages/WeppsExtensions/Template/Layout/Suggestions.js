var suggestionsInit = function() {
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
	    $('#w_suggestions').hide().empty();
	  }
	});

	// Загрузка подсказок
	function loadSuggestions(query, reset = false) {
	  if(suggestLoading || !hasMoreSuggestions) return;
	  
	  suggestLoading = true;
	  $('#w_suggestions .w_suggestions-loader').show();

	  $.ajax({
	    url: '/ext/Products/Request.php?action=suggestions',
	    method: 'POST',
	    data: {
	      action: 'suggestions',
	      query: query,
	      page: suggestPage,
	      limit: 12
	    },
	    success: function(response) {
	      const data = JSON.parse(response);
	      
	      if(reset) {
	        $('#w_suggestions').html(data.html);
	      } else {
	        $('#w_suggestions').append(data.html);
	      }
	      
	      hasMoreSuggestions = data.hasMore;
	      $('#w_suggestions').show();
	      suggestPage++;
	    },
	    complete: function() {
	      suggestLoading = false;
	      $('.w_suggestions-loader').hide();
	    }
	  });
	}

	// Скролл внутри блока подсказок
	$('#w_suggestions').scroll(function() {
	  const $this = $(this);
	  const scrollPosition = $this.scrollTop() + $this.innerHeight();
	  
	  if(scrollPosition >= $this[0].scrollHeight - 50 && hasMoreSuggestions) {
	    loadSuggestions($('#search-input').val().trim());
	  }
	});

	// Клик по подсказке
	$('#w_suggestions').on('click', '.w_suggestions-item', function() {
	  $('#search-input').val($(this).text());
	  $('#w_suggestions').hide();
	  //loadResults(true);
	});
	
	let selectedIndex = -1; // Индекс выбранного элемента

	// Обработчик клавиатуры
	$('#search-input').on('keydown', function(e) {
	  const $suggestions = $('#w_suggestions .w_suggestions-item');
	  const suggestionsCount = $suggestions.length;
	  
	  if(e.key === 'Escape') {
	      e.preventDefault();
	      $('#w_suggestions').hide().empty()
	    }
		
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
	      $('#w_suggestions').hide();
	      //loadResults(true);
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
	$('#w_suggestions').on('mouseenter', '.w_suggestions-item', function() {
	  selectedIndex = $(this).index();
	  updateSelection($('#w_suggestions .w_suggestions-item'));
	});
}

$(document).ready(function() {
	suggestionsInit();
});