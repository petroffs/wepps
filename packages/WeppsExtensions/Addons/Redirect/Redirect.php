<?php
namespace WeppsExtensions\Addons\Redirect;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Redirect extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Addons/Redirect/Redirect.tpl';
				if (isset($this->navigator->child[0])) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: {$this->navigator->child[0]['Url']}");
					exit();
				}
				break;
			default:
				Exception::error404();
				break;
		}
		
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Redirect/Redirect.{$this->rand}.css");
		$this->headers->js("/ext/Redirect/Redirect.{$this->rand}.js");
		
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>