<?php
namespace WeppsExtensions\Alignment1;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class Alignment1Wepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Alignment1/Alignment1.tpl';
				$smarty->assign('element',$this->navigator->content);
				$this->navigator->content['Text1'] = '';
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$this->headers->css("/ext/Alignment1/Alignment1.{$this->rand}.css");
		$this->headers->js("/ext/Alignment1/Alignment1.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}