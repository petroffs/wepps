<?
namespace PPSExtensions\Childs;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class ChildsPPS extends ExtensionPPS {
	public function request() {
		//$this->destinationTpl = 'extension'; //horizontalBottomTpl
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Childs/Childs.tpl';
				//$obj = new DataPPS("s_Directories");
				//$res = $obj->getMax("t.DisplayOff=0 and t.ParentDir = '{$this->navigator->content['Id']}'");
				$smarty->assign('elements',$this->navigator->child);
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Childs/Childs.{$rand}.css");
		$this->headers->js("/ext/Childs/Childs.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>