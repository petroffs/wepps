<?
namespace WeppsExtensions\Icons;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class IconsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Icons/Icons.tpl';
				$obj = new DataWepps("Gallery");
				$res = $obj->getMax("t.DisplayOff=0 and s1.Id='{$this->navigator->content['Id']}'");
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionWepps::error404();
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