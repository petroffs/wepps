<?
namespace PPSExtensions\Gallery;
use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Utils\TemplateHeadersPPS;

class GalleryPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Gallery/Gallery.tpl';
				$obj = new DataPPS("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->getMax("t.TableName='Gallery' and fg.DirectoryId = '{$this->navigator->content['Id']}'",500,1,'t.Priority');
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/packages/vendor_local/fresco/css/fresco/fresco.css");
		$this->headers->js("/packages/vendor_local/fresco/js/fresco/fresco.js");
		$this->headers->css("/ext/Gallery/Gallery.{$rand}.css");
		$this->headers->js("/ext/Gallery/Gallery.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>