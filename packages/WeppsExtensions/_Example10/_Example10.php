<?php
namespace WeppsExtensions\_Example10;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class _Example10Wepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/_Example10/_Example10.tpl';
				#$obj = new DataWepps("_Example10");
				$obj = new DataWepps("News");
				$res = $obj->getMax("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}

		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/_Example10/_Example10.{$this->rand}.css");
		$this->headers->js("/ext/_Example10/_Example10.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}