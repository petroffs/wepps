<?php
namespace WeppsExtensions\Childs;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class ChildsWepps extends ExtensionWepps {
	public function request() {
		//$this->destinationTpl = 'extension'; //horizontalBottomTpl
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Childs/Childs.tpl';
				//$obj = new DataWepps("s_Directories");
				//$res = $obj->getMax("t.DisplayOff=0 and t.ParentDir = '{$this->navigator->content['Id']}'");
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
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>