<?php
namespace WeppsExtensions\Example;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class ExampleWepps extends ExtensionWepps {
	public function request() {
		//$this->destinationTpl = 'extension'; //horizontalBottomTpl
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Example/Example.tpl';
				$obj = new DataWepps("Example");
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
		$this->headers->css("/ext/Example/Example.{$this->rand}.css");
		$this->headers->js("/ext/Example/Example.{$this->rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>