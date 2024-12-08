<?php
namespace WeppsExtensions\Services;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class ServicesWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Services/Services.tpl';
				$conditions = "";
				$obj = new DataWepps("Services");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->getMax($conditions,20,$this->page,"t.Priority");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$this->headers->css("/ext/Services/Services.{$this->rand}.css");
		$this->headers->js("/ext/Services/Services.{$this->rand}.js");
		$this->headers->css ( "/ext/Template/Paginator/Paginator.{$this->rand}.css" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}