<?php
namespace WeppsExtensions\Childs;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;

class ChildsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Childs/Childs.tpl';
				#UtilsWepps::debug($this->navigator->child,1);
				$smarty->assign('elements',$this->navigator->child);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Childs/Childs.{$this->rand}.css");
		$this->headers->js("/ext/Childs/Childs.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>