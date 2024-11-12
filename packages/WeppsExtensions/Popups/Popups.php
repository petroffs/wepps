<?php
namespace WeppsExtensions\Popups;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class PopupsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Popups/Popups.tpl';
				break;
			default:
				ExceptionWepps::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Popups/Popups.{$this->rand}.css");
		$this->headers->js("/ext/Popups/Popups.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>