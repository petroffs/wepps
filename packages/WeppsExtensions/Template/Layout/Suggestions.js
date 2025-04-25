class SuggestionsWepps {
	constructor(settings = {}) {
		this.input = $('#' + settings.input);
		this.action = settings.action
		this.delay = settings.delay || 300;
		this.url = settings.url || 'search.php';
		this.pathname = '/catalog/';
		this.results = $('<div>').addClass('w_suggestions w_hide');
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
		$(this.input).on('input', function () {
			clearTimeout(inputTimeout);
			inputTimeout = setTimeout(() => {
				const $query = $(this).val().trim();
				if ($query.length > 2) {
					suggestPage = 1;
					hasMoreSuggestions = true;
					selectedIndex = -1; // Сброс выбора при новом вводе
					loadSuggestions($query, true);
				} else {
					self.remove();
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
				success: function (response) {
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
				complete: function () {
					suggestLoading = false;
					loader.hide();
				}
			});
		}
		// Скролл внутри блока подсказок
		self.results.scroll(function () {
			if (($(this).scrollTop() + $(this).innerHeight()) >= $(this)[0].scrollHeight - 50 && hasMoreSuggestions) {
				loadSuggestions(self.input.val().trim());
			}
		});
		let selectedIndex = -1; // Индекс выбранного элемента
		// Обработчик клавиатуры
		self.input.on('keydown', function (e) {
			const suggestions = self.results.find(resultsItemClass);
			if (e.key === 'Escape') {
				e.preventDefault();
				self.remove();
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
				self.afterSelectItem(this, suggestions, selectedIndex);
				self.remove();
			}
		});
		// Обновление выделения
		function updateSelection(items) {
			items.removeClass('focus');
			if (selectedIndex >= 0 && selectedIndex < items.length) {
				items.eq(selectedIndex)
					.addClass('focus')
					.get(0)
					.scrollIntoView({ block: 'nearest' });
			}
		}
		self.results.on('click', resultsItemClass, function () {
			const suggestions = self.results.find(resultsItemClass);
			selectedIndex = $(this).index();
			self.afterSelectItem(self.input, suggestions, selectedIndex);
			self.remove();
		});
		$(document).on("click", function (event) {
			let t = $(event.target).attr('id');
			if (t!=self.input.attr('id')) {
				self.remove();
			}
			if (!$(event.target).closest('label').length) {
				//self.remove();
			}
		});
	}
	afterSelectItem(self, suggestions, selectedIndex) {
		const selectedItem = suggestions.eq(selectedIndex);
		if (selectedItem.length && selectedIndex > -1) {
			location.href = selectedItem.data('url');
		} else {
			location.href = this.pathname + '?text=' + $(self).val();
		}
	}
	remove() {
		this.results.empty().addClass('w_hide');
		this.input.removeClass('focus');
	}
}