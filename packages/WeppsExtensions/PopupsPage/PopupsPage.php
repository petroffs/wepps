<?php
namespace WeppsExtensions\PopupsPage;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class PopupsPageWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/PopupsPage/PopupsPage.tpl';
				break;
			default:
				ExceptionWepps::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/PopupsPage/PopupsPage.{$this->rand}.css");
		$this->headers->js("/ext/PopupsPage/PopupsPage.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>