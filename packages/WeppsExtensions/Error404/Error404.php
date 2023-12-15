<?php
namespace WeppsExtensions\Error404;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;

class Error404Wepps extends ExtensionWepps {
	public function request() {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$this->tpl = $root.'/packages/WeppsExtensions/Error404/Error404.tpl';
		$smarty = SmartyWepps::getSmarty();
		$this->headers->css("/ext/Error404/Error404.{$this->rand}.css");
		$this->headers->js("/ext/Error404/Error404.{$this->rand}.js");
		$this->extensionData['element'] = 1;
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));		
		return;
	}
}

?>