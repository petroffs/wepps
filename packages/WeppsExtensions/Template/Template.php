<?php
namespace WeppsExtensions\Template;

use WeppsCore\Smarty;
use WeppsCore\Exception;
use WeppsCore\Extension;
use WeppsExtensions\Template\Blocks\Blocks;
use WeppsCore\Connect;

/**
 * Class Template
 *
 * Отвечает за рендеринг шаблонов с использованием Smarty, интеграцию статических ресурсов,
 * дополнительных модулей и обработку маршрутов. Управляет данными, передаваемыми в шаблон,
 * и обеспечивает отображение страницы.
 *
 * @author  Aleksei Petrov
 */
class Template extends Extension {
	/**
     * Обрабатывает запрос, собирает данные и отображает шаблон.
     *
     * Функционал:
     * 1. Передаёт данные из `$this->navigator` в шаблон (маршрут, язык, контент).
     * 2. Регистрирует CSS/JS-файлы с уникальным суффиксом для обхода кэширования.
     * 3. Загружает дополнительные модули (`TemplateAddons`, `Blocks`).
     * 4. Проверяет существование элемента по маршруту, иначе вызывает ошибку 404.
     * 5. Отображает шаблон через Smarty.
     *
     * @throws Exception Если маршрут не существует.
     * @uses Smarty::getSmarty() Для получения экземпляра шаблонизатора.
     * @uses TemplateAddons Для расширения функционала.
     * @uses Blocks Для управления блоками на странице.
     * @uses Exception::error404() Для обработки ошибок маршрутизации.
     */
	public function request() {
		$smarty = Smarty::getSmarty();
		$smarty->assign('parent',$this->navigator->parent);
		$smarty->assign('child',$this->navigator->child);
		$smarty->assign('way',$this->navigator->way);
		$smarty->assign('language',$this->navigator->lang);
		$smarty->assign('multilang',$this->navigator->multilang);
		$smarty->assign('content',$this->navigator->content);
		$tpl = str_replace(".tpl", "", $this->navigator->tpl['tpl']);
		$this->headers->css("/ext/Template/{$tpl}.{$this->rand}.css");
		
		/*
		 * Дополнительный глобальный функционал
		 */
		new TemplateAddons($this->navigator,$this->headers, $_GET);
		
		/*
		 * Раширение
		 */
		if ($this->navigator->content['Extension_FileExt']) {
			$extensionClass = "\WeppsExtensions\\{$this->navigator->content['Extension_FileExt']}\\{$this->navigator->content['Extension_FileExt']}";
			$extension = new $extensionClass($this->navigator,$this->headers,$_GET);
		}
		$navigator = &$this->navigator;
		if ($navigator::$pathItem!='' && !isset($extension->extensionData['element'])) {
			Exception::error404();
		}
		
		/*
		 * Панели и блоки
		 */
		if ($this->navigator->content['IsBlocksActive']==1) {
			new Blocks($this->navigator, $this->headers, $_GET);
		}
		
		/*
		 * Управление
		 */
		if (@Connect::$projectData['user']['ShowAdmin']==1) {
			$this->headers->css("/packages/WeppsAdmin/Admin/Admin.{$this->rand}.css");
			$this->headers->js("/packages/WeppsAdmin/Admin/Admin.{$this->rand}.js");
		}

		/*
		 * Передача данных в шаблон
		 */
		$this->headers->js("/ext/Template/{$tpl}.{$this->rand}.js");
		$smarty->assign('headers', $this->headers->get());
		$smarty->assign('content',$this->navigator->content);
		$smarty->assign('nav',$this->navigator->nav);
		
		/*
		 * Вывод в шаблон
		 */
		$smarty->display(__DIR__.'/'.$this->navigator->tpl['tpl']);
	}
}