<?
namespace WeppsExtensions\Gallery;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;

class GalleryWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Gallery/Gallery.tpl';
				$obj = new DataWepps("s_Files");
				$obj->setJoin("inner join Gallery as fg on fg.Id=t.TableNameId and t.TableName='Gallery'");
				$res = $obj->getMax("t.TableName='Gallery' and fg.DirectoryId = '{$this->navigator->content['Id']}'",500,1,'t.Priority');
				$smarty->assign('elements',$res);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/packages/vendor/dimsemenov/magnific-popup/dist/magnific-popup.css");
		$this->headers->js("/packages/vendor/dimsemenov/magnific-popup/dist/jquery.magnific-popup.js");
		$this->headers->css("/ext/Gallery/Gallery.{$this->rand}.css");
		$this->headers->js("/ext/Gallery/Gallery.{$this->rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>