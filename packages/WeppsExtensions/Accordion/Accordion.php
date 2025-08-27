<?php
namespace WeppsExtensions\Accordion;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Accordion extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Accordion/Accordion.tpl';
				$obj = new Data("Services");
				$res = $obj->fetch("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Accordion/Accordion.{$this->rand}.css");
		$this->headers->js("/ext/Accordion/Accordion.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>