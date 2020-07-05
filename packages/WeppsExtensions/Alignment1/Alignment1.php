<?
namespace PPSExtensions\Alignment1;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class Alignment1PPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Alignment1/Alignment1.tpl';
				$smarty->assign('element',$this->navigator->content);
				$this->navigator->content['Text1'] = '';
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Alignment1/Alignment1.{$rand}.css");
		$this->headers->js("/ext/Alignment1/Alignment1.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>