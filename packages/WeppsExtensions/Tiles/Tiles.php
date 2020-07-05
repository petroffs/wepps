<?
namespace PPSExtensions\Tiles;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class TilesPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Tiles/Tiles.tpl';
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
		$this->headers->css("/ext/Tiles/Tiles.{$rand}.css");
		$this->headers->js("/ext/Tiles/Tiles.{$rand}.js");
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>