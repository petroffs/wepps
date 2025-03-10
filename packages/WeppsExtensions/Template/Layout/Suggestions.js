class SuggestionsWepps {
	constructor(settings = {}) {
		this.input = $('#'+settings.input);
	    this.delay = settings.delay || 300; // Задержка перед запросом
	    this.limit = settings.limit || 12;  // Лимит подсказок за один запрос
	    this.url = settings.url || 'search.php'; // URL для запросов
	}
	init() {
		let suggestPage = 1;
		let suggestLoading = false;
		let hasMoreSuggestions = true;
		let inputTimeout = null;
		let results = $('<div>').attr('id','w_suggestions');
		let loader = $('<div>').addClass('w_suggestions-loader').text('Загрузка...');
		let resultsItemClass = '.w_suggestions-item';
		$(this.input).after(results);
		$(this.input).after(loader)
		let self = this;
		$(this.input).on('input', function() {
		clearTimeout(inputTimeout); // Отменяем предыдущий таймер
		 inputTimeout = setTimeout(() => {
		    const query = $(this).val().trim();
		    if(query.length > 2) {
		      suggestPage = 1;
		      hasMoreSuggestions = true;
		      selectedIndex = -1; // Сброс выбора при новом вводе
		      loadSuggestions(query, true);
		    } else {
		      results.hide().empty();
		    }
		  }, 300);
		});
		function loadSuggestions(query, reset = false) {
			if(suggestLoading || !hasMoreSuggestions) return;
			suggestLoading = true;
			loader.show();
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
		        results.html(data.html);
		      } else {
		        results.append(data.html);
		      }
		      hasMoreSuggestions = data.hasMore;
		      results.show();
		      suggestPage++;
		    },
		    complete: function() {
		      suggestLoading = false;
		      loader.hide();
		    }
		  });
		}
		// Скролл внутри блока подсказок
			results.scroll(function() {
			  const $this = $(this);
			  const scrollPosition = $this.scrollTop() + $this.innerHeight();
			  if(scrollPosition >= $this[0].scrollHeight - 50 && hasMoreSuggestions) {
			    loadSuggestions(self.input.val().trim());
			  }
			});
			// Клик по подсказке
			results.on('click', resultsItemClass, function() {
			 self.input.val($(this).text());
			  results.hide();
			  //loadResults(true);
			});
			let selectedIndex = -1; // Индекс выбранного элемента
			// Обработчик клавиатуры
			self.input.on('keydown', function(e) {
			  const $suggestions = results.find(resultsItemClass);
			  const suggestionsCount = $suggestions.length;
			  const input = this.input;
			  if(e.key === 'Escape') {
			      e.preventDefault();
			      results.hide().empty()
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
			      self.input.val(selectedItem.text());
			      results.hide();
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
			self.input.on('input', function() {
			  selectedIndex = -1; // Сброс выбора при новом вводе
			  // ... остальной код обработчика ввода ...
			});
			// При открытии подсказок добавляем обработчик
			results.on('mouseenter', resultsItemClass, function() {
			  selectedIndex = $(this).index();
			  updateSelection(results.find(resultsItemClass));
			});
	}
}