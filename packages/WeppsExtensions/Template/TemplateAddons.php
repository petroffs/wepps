<?php

namespace WeppsExtensions\Template;

use WeppsCore\Extension;
use WeppsCore\Data;
use WeppsCore\Smarty;
use WeppsExtensions\Cart\CartUtils;

if (!class_exists('WeppsExtensions\Template\TemplateAddons')) {
	/**
	 * Class TemplateAddons
	 *
	 * Расширяет базовый класс `Extension`, добавляя функциональность для работы с шаблонами:
	 * - регистрация статических ресурсов (CSS/JS),
	 * - обработка данных корзины,
	 * - настройка навигации,
	 * - загрузка информации об организации и соцсетях.
	 *
	 * Используется для интеграции дополнительных компонентов в шаблоны проекта.
	 *
	 * @package WeppsExtensions\Template
	 * @author Aleksei Petrov
	 * @see Extension
	 * @uses Smarty::getSmarty() Для передачи данных в шаблон
	 * @uses CartUtils::getCartMetrics() Для получения метрик корзины
	 * @uses Data::fetchmini() Для загрузки данных организации
	 * @uses Data::fetch() Для фильтрации социальных сетей
	 */
	class TemplateAddons extends Extension
	{
		/**
		 * Обрабатывает запрос, регистрирует ресурсы и передаёт данные в шаблон.
		 *
		 * Метод:
		 * 1. Регистрирует JS/CSS-файлы библиотек (jQuery, Select2, Bootstrap).
		 * 2. Получает и передаёт метрики корзины.
		 * 3. Подгружает навигационные данные и шаблон навигации.
		 * 4. Загружает информацию об организации и социальные сети.
		 * 5. Устанавливает флаг `normalView` для отображения контента.
		 *
		 * @return void
		 * @throws \WeppsCore\Exception Если не удалось загрузить данные из базы
		 */
		public function request()
		{
			// Регистрация статических ресурсов
			$smarty = Smarty::getSmarty();
			$this->headers->js("/packages/vendor/components/jquery/jquery.min.js");
			$this->headers->css("/packages/vendor/twbs/bootstrap-icons/font/bootstrap-icons.min.css");
			$this->headers->js("/packages/vendor/select2/select2/dist/js/select2.min.js");
			$this->headers->js("/packages/vendor/select2/select2/dist/js/i18n/ru.js");
			$this->headers->css("/packages/vendor/select2/select2/dist/css/select2.min.css");
			$this->headers->css("/ext/Template/Layout/Settings.{$this->rand}.css");
			$this->headers->js("/ext/Template/Layout/Layout.{$this->rand}.js");
			$this->headers->css("/ext/Template/Layout/Layout.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Flexbox.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Grid.{$this->rand}.css");
			$this->headers->css("/ext/Template/Layout/Modal.{$this->rand}.css");
			$this->headers->js("/ext/Template/Layout/Suggestions.{$this->rand}.js");
			$this->headers->css("/ext/Template/Layout/Suggestions.{$this->rand}.css");
			$this->headers->js("/ext/Template/Forms/Forms.{$this->rand}.js");
			$this->headers->css("/ext/Template/Forms/Forms.{$this->rand}.css");

			/**
			 * Метрики корзины
			 * @see CartUtils::getCartMetrics()
			 */
			$cartUtils = new CartUtils();
			$cartMetrics = $cartUtils->getCartMetrics();
			$smarty->assign('cartMetrics', $cartMetrics);

			/**
			 * Навигация
			 * @see Smarty::fetch()
			 */
			$this->headers->js("/ext/Template/Nav/Nav.{$this->rand}.js");
			$this->headers->css("/ext/Template/Nav/Nav.{$this->rand}.css");
			$smarty->assign('nav', $this->navigator->nav);
			$smarty->assign('navTpl', $smarty->fetch(__DIR__ . '/Nav/Nav.tpl'));

			/**
			 * Информация об организации
			 * @see Data::fetchmini()
			 */
			$obj = new Data("Organizations");
			$smarty->assign('org', $obj->fetchmini()[0]);
			unset($obj);

			/**
			 * Социальные сети
			 * @see Data::fetch()
			 */
			$obj = new Data("ServList");
			$res = $obj->fetch("t.Categories='Соцсети' and t.DisplayOff=0");
			$smarty->assign('socials', $res);
			unset($obj);

			/**
			 * Флаг для отображения контента
			 */
			$smarty->assign('normalView', 1);
		}
	}
}