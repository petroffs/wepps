<?php
namespace WeppsExtensions\Legal;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;

class Legal extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty ();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Legal/Legal.tpl';
				$conditions = "t.DisplayOff=0";
				$obj = new Data("Legal");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions,30,1,"t.Priority");
				$smarty->assign('elements',$res);
				break;
			default:
				$this->tpl = 'packages/WeppsExtensions/Legal/LegalItem.tpl';
				$res = $this->getItem("Legal");
				$smarty->assign('element',$res);
				$conditions = "t.DisplayOff=0";
				$obj = new Data("Legal");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions,30,1,"t.Priority");
				$smarty->assign('elements',$res);
				$smarty->assign('normalView',0);
				break;
		}
		$this->headers->css("/ext/Legal/Legal.{$this->rand}.css");
		$this->headers->js("/ext/Legal/Legal.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}