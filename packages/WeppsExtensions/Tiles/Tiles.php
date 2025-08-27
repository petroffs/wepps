<?php
namespace WeppsExtensions\Tiles;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Tiles extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Tiles/Tiles.tpl';
				$obj = new Data("Services");
				$res = $obj->fetch("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Tiles/Tiles.{$this->rand}.css");
		$this->headers->js("/ext/Tiles/Tiles.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}