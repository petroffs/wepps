<?
namespace PPSExtensions\Example;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Utils\TemplateHeadersPPS;

class ExamplePPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty ();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Example/Example.tpl';
				$extensionConditions = "";
				//if ($this->navigator->content['Id']==11) $extensionConditions = "t.DisplayOff=0 and t.DirectoryId='{$this->navigator->content['Id']}'";
				$obj = new DataPPS("Example");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url");
				$res = $obj->getMax($extensionConditions,5,$this->page,"t.Priority");
				$smarty->assign('elements',$res);
				$smarty->assign('paginator',$obj->paginator);
				$smarty->assign('paginatorTpl', $smarty->fetch('packages/PPSExtensions/Addons/Paginator/Paginator.tpl'));
				break;
			default:
				$this->tpl = 'packages/PPSExtensions/Example/ExampleItem.tpl';
				$res = $this->getItem("Example");
				$smarty->assign('element',$res);
				$extensionConditions = "t.DisplayOff=0 and t.Id!='{$res['Id']}'";
				$obj = new DataPPS("Example");
				$obj->setConcat("concat('{$this->navigator->content['Url']}',if(t.KeyUrl!='',t.KeyUrl,t.Id),'.html') as Url");
				$res = $obj->getMax($extensionConditions,3,1,"t.Priority");
				$smarty->assign('elements',$res);
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Example/Example.{$rand}.css");
		$this->headers->js("/ext/Example/Example.{$rand}.js");
		$this->headers->css ( "/ext/Addons/Paginator/Paginator.{$rand}.css" );
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>