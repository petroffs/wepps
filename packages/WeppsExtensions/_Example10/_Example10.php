<?php
namespace WeppsExtensions\_Example10;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class _Example10 extends Extension
{
	public function request()
	{
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/_Example10/_Example10.tpl';
				$conditions = 't.DisplayOff=0';
				$obj = new Data("News");
				$res = $obj->fetch($conditions, 6, $this->page, 't.Priority desc');
				$smarty->assign('elements', $res);
				$smarty->assign('paginator', $obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
				$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/_Example10/_Example10.{$this->rand}.css");
		$this->headers->js("/ext/_Example10/_Example10.{$this->rand}.js");
		$smarty->assign($this->targetTpl, $smarty->fetch($this->tpl));
		return;
	}
}