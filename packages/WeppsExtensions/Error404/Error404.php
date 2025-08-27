<?php
namespace WeppsExtensions\Error404;

use WeppsCore\Smarty;
use WeppsCore\Extension;

class Error404 extends Extension {
	public function request() {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$this->tpl = $root.'/packages/WeppsExtensions/Error404/Error404.tpl';
		$smarty = Smarty::getSmarty();
		$this->headers->css("/ext/Error404/Error404.{$this->rand}.css");
		$this->headers->js("/ext/Error404/Error404.{$this->rand}.js");
		$smarty->assign('normalView',0);
		$this->extensionData['element'] = 1;
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));		
		return;
	}
}