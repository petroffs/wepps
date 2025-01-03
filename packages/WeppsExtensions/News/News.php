<?php
namespace WeppsExtensions\News;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;

class NewsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/News/News.tpl';
				$conditions = 't.DisplayOff=0';
				$obj = new DataWepps("News");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->getMax($conditions,12,$this->page,"t.NDate desc");
				$smarty->assign('elements', $res);
				$smarty->assign('paginator', $obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Template/Paginator/Paginator.tpl' ));
				$this->headers->css("/ext/Template/Paginator/Paginator.{$this->rand}.css");
			break;
			default:
				$this->tpl = 'packages/WeppsExtensions/News/NewsItem.tpl';
				$res = $this->getItem("News");
				$smarty->assign('element',$res);
				$conditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
				$obj = new DataWepps("News");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->getMax($conditions,3,1,"t.NDate");
				$smarty->assign('elements',$res);
				$smarty->assign('normalView',0);
			break;
		}
		$this->headers->css("/ext/News/News.{$this->rand}.css");
		$this->headers->js("/ext/News/News.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}