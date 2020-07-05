<?
namespace PPSExtensions\Accordion;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;
use PPS\Utils\UtilsPPS;

class AccordionPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Accordion/Accordion.tpl';
				$obj = new DataPPS("Services");
				$res = $obj->getMax("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Accordion/Accordion.css");
		$this->headers->js("/ext/Accordion/Accordion.js");
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>