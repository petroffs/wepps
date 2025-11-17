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
				$obj = new Data("News");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions,6,$this->page,"t.Priority desc");
				$smarty->assign('elements',$res);
				$smarty->assign('paginator',$obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl'));
				$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
				break;
			default:
				$this->tpl = 'packages/WeppsExtensions/Legal/LegalItem.tpl';
				$res = $this->getItem("News");
				$smarty->assign('element',$res);
				$conditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
				$obj = new Data("News");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->fetch($conditions,3,1,"t.Priority desc");
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