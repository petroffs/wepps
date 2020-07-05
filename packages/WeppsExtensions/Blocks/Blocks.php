<?
namespace WeppsExtensions\Blocks;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;

class BlocksWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Blocks/Blocks.tpl';

				$obj = new DataWepps("s_Panels");
				$panels = $obj->getMax("t.DisplayOff=0 and t.DirectoryId='{$this->navigator->content['Id']}'");
				//UtilsWepps::debugf($panels);
				$obj = new DataWepps("s_Blocks");
				$obj->setJoin("inner join s_Panels p on p.Id = t.PanelId inner join s_Directories d on d.Id = p.DirectoryId");
				$res = $obj->getMax("t.DisplayOff=0 and d.Id='{$this->navigator->content['Id']}'");
				
				$blocks = [];
				foreach($res as $value) {
					$blocks[$value['PanelId']][] = $value;
				}
				$smarty->assign('panels',$panels);
				$smarty->assign('blocks',$blocks);
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		/*
		 * Переменные для глобального шаблона
		 */
		$this->headers->css("/ext/Blocks/Blocks.{$rand}.css");
		$this->headers->js("/ext/Blocks/Blocks.{$rand}.js");
		
		$smarty->assign('blocks',$smarty->fetch($this->tpl));
		return;
	}
}
?>