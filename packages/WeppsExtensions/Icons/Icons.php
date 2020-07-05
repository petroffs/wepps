<?
namespace PPSExtensions\Icons;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class IconsPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Icons/Icons.tpl';
				$obj = new DataPPS("Gallery");
				$res = $obj->getMax("t.DisplayOff=0 and s1.Id='{$this->navigator->content['Id']}'");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Icons/Icons.{$rand}.css");
		$this->headers->js("/ext/Icons/Icons.{$rand}.js");
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>