<?php
namespace WeppsExtensions\Services;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Services extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty ();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Services/Services.tpl';
				$conditions = "";
				$obj = new Data("Services");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions,20,$this->page,"t.Priority");
				$smarty->assign('elements',$res);
				break;
			default:
				Exception::error404();
				break;
		}
		$this->headers->css("/ext/Services/Services.{$this->rand}.css");
		$this->headers->js("/ext/Services/Services.{$this->rand}.js");
		$this->headers->css ( "/ext/Template/Paginator/Paginator.{$this->rand}.css" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}