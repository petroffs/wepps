<?
namespace WeppsExtensions\Redirect;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class RedirectWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Redirect/Redirect.tpl';
				if (isset($this->navigator->child[0])) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: {$this->navigator->child[0]['Url']}");
					exit();
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Redirect/Redirect.{$this->rand}.css");
		$this->headers->js("/ext/Redirect/Redirect.{$this->rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>