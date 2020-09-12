<?
namespace WeppsExtensions\Services;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class ServicesWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty ();
		$rand = $this->headers::$rand;
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Services/Services.tpl';
				$extensionConditions = "";
				//if ($this->navigator->content['Id']==11) $extensionConditions = "t.DisplayOff=0 and t.DirectoryId='{$this->navigator->content['Id']}'";
				$obj = new DataWepps("Services");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->getMax($extensionConditions,5,$this->page,"t.Priority");
				$smarty->assign('elements',$res);
				$smarty->assign('paginator',$obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/WeppsExtensions/Addons/Paginator/Paginator.tpl'));
				break;
			default:
				$this->tpl = 'packages/WeppsExtensions/Services/ServicesItem.tpl';
				$res = $this->getItem("Services");
				$smarty->assign('element',$res);
				$extensionConditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
				$obj = new DataWepps("Services");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.Alias!='',t.Alias,t.Id),'.html') as Url");
				$res = $obj->getMax($extensionConditions,3,1,"t.Priority");
				$smarty->assign('elements',$res);
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Services/Services.{$rand}.css");
		$this->headers->js("/ext/Services/Services.{$rand}.js");
		$this->headers->css ( "/ext/Addons/Paginator/Paginator.css" );
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>