class SuggestionsWepps {
	constructor(settings = {}) {
		this.input = $('#' + settings.input);
		this.action = settings.action
		this.delay = settings.delay || 300; // Задержка перед запросом
		this.url = settings.url || 'search.php'; // URL для запросов
		this.pathname = '/catalog/';
		this. results = $('<div>').addClass('w_suggestions w_hide');
		$(this.input).after(this.results);
	}
	init() {
		let suggestPage = 1;
		let suggestLoading = false;
		let hasMoreSuggestions = true;
		let inputTimeout = null;
		let loader = $('<div>').addClass('w_suggestions-loader').text('Загрузка...');
		let resultsItemClass = '.w_suggestions-item';
		$(this.input).after(loader)
		let self = this;
		$(this.input).on('input', function() {
			clearTimeout(inputTimeout); // Отменяем предыдущий таймер
			inputTimeout = setTimeout(() => {
				const $query = $(this).val().trim();
				if ($query.length > 2) {
					suggestPage = 1;
					hasMoreSuggestions = true;
					selectedIndex = -1; // Сброс выбора при новом вводе
					loadSuggestions($query, true);
				} else {
					self.inputDefault();
				}
			}, self.delay);
		});
		function loadSuggestions(query, reset = false) {
			if (suggestLoading || !hasMoreSuggestions) return;
			suggestLoading = true;
			loader.show();
			$.ajax({
				url: self.url,
				method: 'POST',
				data: {
					action: self.action,
					text: query,
					page: suggestPage,
				},
				success: function(response) {
					const $data = JSON.parse(response);
					if (reset) {
						self.results.html($data.html).removeClass('w_hide');
					} else {
						self.results.append($data.html).removeClass('w_hide');
					}
					hasMoreSuggestions = $data.hasMore;
					self.input.addClass('focus');
					suggestPage++;
				},
				complete: function() {
					suggestLoading = false;
					loader.hide();
				}
			});
		}
		// Скролл внутри блока подсказок
		self.results.scroll(function() {
			if (($(this).scrollTop() + $(this).innerHeight()) >= $(this)[0].scrollHeight - 50 && hasMoreSuggestions) {
				loadSuggestions(self.input.val().trim());
			}
		});
		let selectedIndex = -1; // Индекс выбранного элемента
		// Обработчик клавиатуры
		self.input.on('keydown', function(e) {
			const suggestions = self.results.find(resultsItemClass);
			if (e.key === 'Escape') {
				e.preventDefault();
				self.inputDefault();
			}
			// Стрелка вниз
			if (e.key === 'ArrowDown') {
				e.preventDefault();
				selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
				updateSelection(suggestions);
			}
			// Стрелка вверх
			if (e.key === 'ArrowUp') {
				e.preventDefault();
				selectedIndex = Math.max(selectedIndex - 1, -1);
				updateSelection(suggestions);
			}
			// Enter
			if (e.key === 'Enter') {
				self.afterSelectItem(this,suggestions,selectedIndex);
				self.inputDefault();
			}
		});
		// Обновление выделения
		function updateSelection(items) {
			items.removeClass('active');
			if (selectedIndex >= 0 && selectedIndex < items.length) {
				items.eq(selectedIndex)
					.addClass('active')
					.get(0)
					.scrollIntoView({ block: 'nearest' });
			}
		}
		self.results.on('click', resultsItemClass, function() {
			const suggestions = self.results.find(resultsItemClass);
			selectedIndex = $(this).index();
			self.afterSelectItem(self.input,suggestions,selectedIndex);
			self.inputDefault();
		});
	}
	afterSelectItem(self,suggestions,selectedIndex) {
		const selectedItem = suggestions.eq(selectedIndex);
		if (selectedItem.length && selectedIndex > -1) {
			location.href = selectedItem.data('url');
		} else {
			location.href = this.pathname + '?text=' + $(self).val();
		}
	}
	inputDefault() {
		this.results.empty().addClass('w_hide');
		this.input.removeClass('focus');
	}
}