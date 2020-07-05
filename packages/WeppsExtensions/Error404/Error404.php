<?
namespace WeppsExtensions\Error404;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\Core\ExtensionWepps;

class Error404Wepps extends ExtensionWepps {
	public function request() {
		$root = $_SERVER['DOCUMENT_ROOT'];
		$this->tpl = $root.'/packages/WeppsExtensions/Error404/Error404.tpl';
		$smarty = SmartyWepps::getSmarty();
		$this->headers->css("/ext/Error404/Error404.css");
		$this->headers->js("/ext/Error404/Error404.js");
		$this->extensionData['element'] = 1;
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));		
		return;
	}
}

?>