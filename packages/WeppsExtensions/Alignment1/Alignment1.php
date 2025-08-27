<?php
namespace WeppsExtensions\Alignment1;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Alignment1 extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Alignment1/Alignment1.tpl';
				$smarty->assign('element',$this->navigator->content);
				$this->navigator->content['Text1'] = '';
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Alignment1/Alignment1.{$this->rand}.css");
		$this->headers->js("/ext/Alignment1/Alignment1.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}