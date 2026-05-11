var productsItemInit = function () {
	cart.addHandler();
	cart.addVariationsHandler();
	cart.favoritesHandler();
	cart.displayExists();

	// Инициализируем галерею товара
	productsSwiper.init();
};

var productsSwiper = {
	state: {
		mainSwiper: null,
		thumbsSwiper: null,
		imagesByColor: null,
		currentColor: null
	},

	init: function () {
		this.loadImagesByColor();
		this.initGallery();
		this.attachEventHandlers();
	},

	loadImagesByColor: function () {
		if (typeof imagesByColor !== 'undefined') {
			this.state.imagesByColor = imagesByColor;

			// Генерируем кнопки цветов в контейнер .gallery-colors-menu
			if (Object.keys(this.state.imagesByColor).length > 1) {
				this.renderColorButtons();
			}
		}
	},

	initGallery: function () {
		// Мини превьюшки (только если элемент существует в DOM)
		if (document.querySelector('.swiper-gallery-thumbs')) {
			this.state.thumbsSwiper = new Swiper('.swiper-gallery-thumbs', {
				spaceBetween: 12,
				slidesPerView: 4,
				freeMode: true,
				watchSlidesProgress: true,
			});
		}

		// Основной галлери с синхронизацией на превьюшки
		if (document.querySelector('.swiper-gallery-main')) {
			this.state.mainSwiper = new Swiper('.swiper-gallery-main', {
				spaceBetween: 10,
				navigation: {
					nextEl: '.swiper-gallery-main .swiper-button-next',
					prevEl: '.swiper-gallery-main .swiper-button-prev',
				},
				thumbs: {
					swiper: this.state.thumbsSwiper || null,
				},
			});
		}
	},

	attachEventHandlers: function () {
		var self = this;

		// Клики на кнопки цветов или блоки section
		$(document).on('click', '.color-button, section[data-color]', function (e) {
			e.preventDefault();
			var colorName = $(this).data('color');

			if (colorName) {
				self.updateGalleryForColor(colorName);
				self.highlightColorButton(colorName);
			}
		});

		// События изменения цвета (совместимость)
		$(document).on('colorSelected', function (e, colorName) {
			self.updateGalleryForColor(colorName);
			self.highlightColorButton(colorName);
		});
	},

	renderColorButtons: function () {
		var menuContainer = document.querySelector('.gallery-colors-menu');
		if (!menuContainer) return;

		var firstColor = true;
		for (var colorName in this.state.imagesByColor) {
			if (this.state.imagesByColor.hasOwnProperty(colorName)) {
				var colorTitle = (colorName == 'default') ? $('h1').text() : colorName;
				
				var label = document.createElement('label');
				label.className = 'w_label w_button';
				
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'color-button' + (firstColor ? ' active' : '');
				button.setAttribute('data-color', colorName);
				button.setAttribute('data-tooltip', colorName);
				button.textContent = colorTitle;
				
				label.appendChild(button);
				menuContainer.appendChild(label);
				firstColor = false;
			};
			tooltipWeppsInit();
		};
	},

	highlightColorButton: function (colorName) {
		$('.gallery-colors-menu .color-button').removeClass('active');
		$('.gallery-colors-menu .color-button[data-color="' + colorName + '"]').addClass('active');
	},

	updateGalleryForColor: function (colorName) {
		if (!this.state.imagesByColor || !this.state.imagesByColor[colorName]) {
			return;
		}

		// Сохраняем позицию скролла
		var scrollTop = $(window).scrollTop();

		this.state.currentColor = colorName;

		// Удаляем все текущие слайды
		if (this.state.mainSwiper) {
			this.state.mainSwiper.removeAllSlides();
		}
		if (this.state.thumbsSwiper) {
			this.state.thumbsSwiper.removeAllSlides();
		}

		// Добавляем только слайды нужного цвета
		var images = this.state.imagesByColor[colorName];
		images.forEach(function(img) {
			// Добавляем в основную галерею
			if (this.state.mainSwiper) {
				var mainSlide = document.createElement('div');
				mainSlide.className = 'swiper-slide';
				
				var mainImg = document.createElement('img');
				mainImg.src = img.mediumv;
				mainImg.className = 'w_image';
				
				mainSlide.appendChild(mainImg);
				this.state.mainSwiper.appendSlide(mainSlide);
			}

			// Добавляем в превью галерею
			if (this.state.thumbsSwiper) {
				var thumbSlide = document.createElement('div');
				thumbSlide.className = 'swiper-slide';
				
				var thumbImg = document.createElement('img');
				thumbImg.src = img.preview;  // preview для миниатюр
				thumbImg.className = 'w_image';
				
				thumbSlide.appendChild(thumbImg);
				this.state.thumbsSwiper.appendSlide(thumbSlide);
			}
		}.bind(this));

		// Обновляем свайперы
		if (this.state.mainSwiper) {
			this.state.mainSwiper.update();
			this.state.mainSwiper.slideTo(0, 0);
		}
		if (this.state.thumbsSwiper) {
			this.state.thumbsSwiper.update();
			this.state.thumbsSwiper.slideTo(0, 0);
		}

		// Восстанавливаем позицию скролла
		$(window).scrollTop(scrollTop);
	},

};

$(document).ready(productsItemInit);