<?
namespace PPSExtensions\Redirect;

use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class RedirectPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Redirect/Redirect.tpl';
				if (isset($this->navigator->child[0])) {
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: {$this->navigator->child[0]['Url']}");
					exit();
				}
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Redirect/Redirect.{$rand}.css");
		$this->headers->js("/ext/Redirect/Redirect.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>