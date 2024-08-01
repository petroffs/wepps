<?php
namespace WeppsExtensions\News;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Utils\UtilsWepps;

class NewsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/News/News.tpl';
				$extensionConditions = "";
				$obj = new DataWepps ( "News" );
				$obj->setConcat ( "concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url" );
				$res = $obj->getMax ( $extensionConditions, 5, $this->page, "t.Priority" );
				$smarty->assign ( 'elements', $res );
				$smarty->assign ( 'paginator', $obj->paginator );
				$smarty->assign ( 'paginatorTpl', $smarty->fetch ( 'packages/WeppsExtensions/Template/Paginator/Paginator.tpl' ) );
			break;
			default:
				$this->tpl = 'packages/WeppsExtensions/News/NewsItem.tpl';
				$res = $this->getItem ( "News" );
				$smarty->assign ( 'element', $res );
				$extensionConditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
				$obj = new DataWepps ( "News" );
				$obj->setConcat ( "concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url" );
				$res = $obj->getMax($extensionConditions,3,1,"t.Priority");
				$smarty->assign('elements',$res);
			break;
		}
		
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/News/News.{$this->rand}.css");
		$this->headers->js("/ext/News/News.{$this->rand}.js");
		$this->headers->css ( "/ext/Template/Paginator/Paginator.{$this->rand}.css" );
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>