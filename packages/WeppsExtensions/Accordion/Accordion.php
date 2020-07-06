<?
namespace WeppsExtensions\Accordion;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;

class AccordionWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Accordion/Accordion.tpl';
				$obj = new DataWepps("Services");
				$res = $obj->getMax("t.DisplayOff=0");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionWepps::error404();
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