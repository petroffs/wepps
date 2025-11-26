/**
 * SwiperManager — менеджер инициализации Swiper с учётом видимых слайдов.
 *
 * Описание:
 *  - Подсчитывает видимые слайды и аккуратно включает `loop`/`autoplay`
 *    только если это безопасно (или явно указано в параметрах).
 *  - Сохраняет созданный экземпляр Swiper в `$(container).data('swiper')`.
 *
 * Пример использования:
 *   var m = new SwiperManager();
 *   m.init('.swiper.selector', { autoplay: { delay: 3000 } });
 *
 * Методы:
 *  - init(selector: string, params?: Object): Swiper|null
 *      Инициализирует Swiper в контейнере `selector`, возвращает экземпляр или `null`,
 *      если контейнер не найден.
 *
 * Замечания:
 *  - Конструктор класса параметров не принимает; все параметры передаются в `init`.
 *  - Класс определён в глобальной области, вызовы `init` должны идти внутри ready.
 */
class SwiperManager {
	constructor() {
		this.selector = null;
		this.$container = $();
		this.params = {};
		this.instance = null;
	}

	// Простая глубокая слияние объектов (target <- src)
	mergeDeep(target, src) {
		for (var key in src) {
			if (src.hasOwnProperty(key)) {
				if (src[key] && typeof src[key] === 'object' && !Array.isArray(src[key])) {
					target[key] = target[key] || {};
					this.mergeDeep(target[key], src[key]);
				} else {
					target[key] = src[key];
				}
			}
		}
		return target;
	}

	// Попытаться получить slidesPerView из params или breakpoints
	getSlidesPerView(params) {
		if (!params) return 1;
		if (params.slidesPerView) return params.slidesPerView;
		if (params.breakpoints) {
			var w = window.innerWidth || document.documentElement.clientWidth;
			var bps = Object.keys(params.breakpoints).map(function (k) { return parseInt(k, 10); }).filter(Boolean).sort(function (a, b) { return a - b; });
			var chosen = null;
			for (var i = 0; i < bps.length; i++) {
				if (w >= bps[i]) chosen = bps[i];
			}
			if (chosen !== null) {
				var bp = params.breakpoints[chosen];
				return bp.slidesPerView || 1;
			}
		}
		return 1;
	}

	countVisibleSlides() {
		if (!this.$container || !this.$container.length) return 0;
		return this.$container.find('.swiper-slide:visible').length || 0;
	}

	init(selector, params = {}) {
		this.selector = selector;
		this.$container = $(selector);
		this.params = params || {};

		if (!this.$container.length) return null;

		var visibleSlides = this.countVisibleSlides();

		var defaultParams = {
			navigation: {
				nextEl: ".swiper-button-next",
				prevEl: ".swiper-button-prev",
			},
			pagination: {
				el: ".swiper-pagination",
				dynamicBullets: true,
			},
			observer: true,
			observeParents: true,
			autoplay: false,
			loop: false,
			loopedSlides: visibleSlides > 0 ? visibleSlides : 0,
		};

		// Сливаем пользовательские параметры
		if (this.params && typeof this.params === 'object') {
			this.mergeDeep(defaultParams, this.params);
		}

		var requiredSlides = this.getSlidesPerView(defaultParams);

		if (typeof this.params.loop !== 'undefined') {
			defaultParams.loop = this.params.loop;
		} else {
			defaultParams.loop = visibleSlides > requiredSlides;
		}

		if (typeof this.params.autoplay !== 'undefined') {
			defaultParams.autoplay = this.params.autoplay;
		}

		if (defaultParams.loop) {
			defaultParams.loopedSlides = Math.max(defaultParams.loopedSlides || 0, visibleSlides);
		}

		// создаём Swiper и сохраняем экземпляр
		this.instance = new Swiper(this.$container[0], defaultParams);
		// Сохраняем ссылку на экземпляр в data элемента для доступа позже
		this.$container.data('swiper', this.instance);
		return this.instance;
	}
}