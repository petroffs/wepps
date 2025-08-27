<?php
namespace WeppsExtensions\Childs;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\Utils;

class Childs extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Childs/Childs.tpl';
				#Utils::debug($this->navigator->child,1);
				$smarty->assign('elements',$this->navigator->child);
				break;
			default:
				Exception::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Childs/Childs.{$this->rand}.css");
		#$this->headers->js("/ext/Childs/Childs.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>