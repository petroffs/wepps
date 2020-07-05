<?
namespace PPSExtensions\Example;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class ExamplePPS extends ExtensionPPS {
	public function request() {
		//$this->destinationTpl = 'extension'; //horizontalBottomTpl
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Example/Example.tpl';
				$obj = new DataPPS("Example");
				$res = $obj->getMax("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Example/Example.{$rand}.css");
		$this->headers->js("/ext/Example/Example.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>