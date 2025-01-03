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
				$conditions = 't.DisplayOff=0';
				$obj = new DataWepps("News");
				$res = $obj->getMax($conditions,6,$this->page,'t.Priority desc');
				$smarty->assign('elements',$res);
				$smarty->assign('paginator',$obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
				$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$this->headers->css("/ext/_Example10/_Example10.{$this->rand}.css");
		$this->headers->js("/ext/_Example10/_Example10.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}