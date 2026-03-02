/**
 * TooltipWepps — компонент тултипов
 *
 * Автоинициализация:
 *   Добавьте атрибут data-tooltip="Текст тултипа" к любому элементу.
 *   Дополнительные атрибуты:
 *     data-tooltip-placement="top|bottom|left|right"  (по умолчанию: top)
 *     data-tooltip-trigger="hover|click"              (по умолчанию: hover)
 *     data-tooltip-html="true"                        (разрешить HTML в содержимом)
 *
 * Программное использование:
 *   const tip = new TooltipWepps({
 *     target: '#myElement',   // селектор или jQuery-объект
 *     text: 'Подсказка <b>жирным</b>',
 *     html: true,
 *     placement: 'bottom',
 *     trigger: 'click',
 *     delay: 150,
 *   });
 *   tip.init();
 */
class TooltipWepps {
	constructor(settings = {}) {
		this.target    = $(settings.target);
		this.text      = settings.text      || '';
		this.html      = settings.html      || false;
		this.placement = settings.placement || 'top';
		this.trigger   = settings.trigger   || 'hover';
		this.delay     = settings.delay     !== undefined ? settings.delay : 100;
		this._fixed    = settings.fixed     !== undefined ? settings.fixed : null; // null = определить автоматически
		this._tip      = null;
		this._showTimer = null;
		this._hideTimer = null;
	};

	// ─── Публичные методы ──────────────────────────────────────────────────────

	init() {
		let self = this;
		if (!this.target.length) return this;
		// Убираем нативный тултип браузера
		if (this.target.attr('title')) {
			if (!this.text) this.text = this.target.attr('title');
			this.target.removeAttr('title');
		};

		// Запоминаем позицию мыши для прицела стрелки
		this.target.on('mousemove.tooltip', function (e) {
			self._mouseX = e.clientX;
			self._mouseY = e.clientY;
		});

		if (this.trigger === 'click') {
			this.target.on('click.tooltip', function (e) {
				e.stopPropagation();
				self._tip && self._tip.hasClass('w_tooltip_visible')
					? self.hide()
					: self.show();
			});
			$(document).on('click.tooltip_outside_' + this._uid(), function () {
				self.hide();
			});
		} else {
			this.target.on('mouseenter.tooltip', function () {
				clearTimeout(self._hideTimer);
				self._showTimer = setTimeout(() => self.show(), self.delay);
			});
			this.target.on('mouseleave.tooltip', function () {
				clearTimeout(self._showTimer);
				self._hideTimer = setTimeout(() => self.hide(), self.delay);
			});
		}
		return this;
	};

	show() {
		if (!this.text) return this;
		this._build();
		this._position();
		this._tip.addClass('w_tooltip_visible');
		return this;
	};

	hide() {
		if (!this._tip) return this;
		this._tip.removeClass('w_tooltip_visible');
		return this;
	};

	destroy() {
		this.hide();
		this.target.off('.tooltip');
		$(document).off('.tooltip_outside_' + this._uid());
		if (this._tip) {
			this._tip.remove();
			this._tip = null;
		}
		return this;
	};

	// ─── Внутренние методы ────────────────────────────────────────────────────

	_uid() {
		if (!this.__uid) {
			this.__uid = Math.random().toString(36).slice(2);
		}
		return this.__uid;
	};

	_build() {
		if (this._tip && this._tip.length) return;
		// Кешируем fixed один раз — DOM не меняется
		if (this._fixed === null) this._fixed = this._isFixed();
		this._tip = $('<div>')
			.addClass('w_tooltip w_tooltip_' + this.placement);
		this.html ? this._tip.html(this.text) : this._tip.text(this.text);
		$('body').append(this._tip);

		if (this.trigger === 'hover') {
			let self = this;
			this._tip.on('mouseenter', function () {
				clearTimeout(self._hideTimer);
			});
			this._tip.on('mouseleave', function () {
				self._hideTimer = setTimeout(() => self.hide(), self.delay);
			});
		}
	};

	_isFixed() {
		let el = this.target[0];
		while (el && el !== document.body) {
			if (window.getComputedStyle(el).position === 'fixed') return true;
			el = el.parentElement;
		}
		return false;
	};

	_position() {
		const tgt    = this.target[0].getBoundingClientRect();
		const fixed  = this._fixed;
		const scroll = fixed ? { x: 0, y: 0 } : { x: window.scrollX, y: window.scrollY };
		const vw     = window.innerWidth;
		const vh     = window.innerHeight;
		const tip    = this._tip;
		const gap    = 8;

		// Сброс позиции и класса — одна запись в DOM
		tip[0].style.cssText += `;position:${fixed ? 'fixed' : 'absolute'};top:-9999px;left:-9999px`;
		tip.removeClass('w_tooltip_top w_tooltip_bottom w_tooltip_left w_tooltip_right');

		// Читаем размеры один раз
		const tw = tip.outerWidth();
		const th = tip.outerHeight();

		let placement = this.placement;

		// Flip: проверяем не вылезет ли тултип за viewport и меняем сторону
		if      (placement === 'top'    && tgt.top    < th + gap)          placement = 'bottom';
		else if (placement === 'bottom' && tgt.bottom + th + gap > vh)     placement = 'top';
		else if (placement === 'left'   && tgt.left   < tw + gap)          placement = 'right';
		else if (placement === 'right'  && tgt.right  + tw + gap > vw)     placement = 'left';

		tip.addClass('w_tooltip_' + placement);

		let top, left;

		switch (placement) {
			case 'bottom':
				top  = tgt.bottom + scroll.y + gap;
				left = tgt.left   + scroll.x + tgt.width / 2 - tw / 2;
				break;
			case 'left':
				top  = tgt.top  + scroll.y + tgt.height / 2 - th / 2;
				left = tgt.left + scroll.x - tw - gap;
				break;
			case 'right':
				top  = tgt.top   + scroll.y + tgt.height / 2 - th / 2;
				left = tgt.right + scroll.x + gap;
				break;
			case 'top':
			default:
				top  = tgt.top  + scroll.y - th - gap;
				left = tgt.left + scroll.x + tgt.width / 2 - tw / 2;
				break;
		}

		// Прижимаем к краям viewport по горизонтали
		left = Math.max(scroll.x + gap, Math.min(left, scroll.x + vw - tw - gap));

		tip.css({ top: top, left: left });

		// Стрелка указывает на курсор мыши, а не в центр тултипа
		const arrowPad = 10; // минимальный отступ от края тултипа
		let arrowOffset;
		if (placement === 'top' || placement === 'bottom') {
			if (this._mouseX !== undefined) {
				arrowOffset = Math.round(this._mouseX + scroll.x - left);
				arrowOffset = Math.max(arrowPad, Math.min(arrowOffset, tw - arrowPad));
				tip[0].style.setProperty('--w-arrow', arrowOffset + 'px');
			} else {
				tip[0].style.removeProperty('--w-arrow');
			}
		} else {
			if (this._mouseY !== undefined) {
				arrowOffset = Math.round(this._mouseY + scroll.y - top);
				arrowOffset = Math.max(arrowPad, Math.min(arrowOffset, th - arrowPad));
				tip[0].style.setProperty('--w-arrow', arrowOffset + 'px');
			} else {
				tip[0].style.removeProperty('--w-arrow');
			}
		};
	};
}

// ─── Автоинициализация ─────────────────────────────────────────────────────

function tooltipWeppsInit() {
	$('[data-tooltip]').each(function () {
		const $el = $(this);
		if ($el.data('_tooltip_init')) return;
		$el.data('_tooltip_init', true);

		new TooltipWepps({
			target:    $el,
			text:      $el.data('tooltip'),
			html:      $el.data('tooltip-html') === true || $el.data('tooltip-html') === 'true',
			placement: $el.data('tooltip-placement') || 'top',
			trigger:   $el.data('tooltip-trigger')   || 'hover',
		}).init();
	});
};

$(document).ready(tooltipWeppsInit);
