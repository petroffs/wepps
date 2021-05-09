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
				$this->tpl = "";

				/*
				 * Переменные для глобального шаблона
				 */
				$this->headers->css("/ext/Blocks/Blocks.{$rand}.css");
				$this->headers->js("/ext/Blocks/Blocks.{$rand}.js");
				
				$obj = new DataWepps("s_Panels");
				$panels = $obj->getMax("t.DisplayOff=0 and t.DirectoryId='{$this->navigator->content['Id']}'");
				if (empty($panels)) {
					return;
				}
				
				/*
				 * Подключить шаблоны
				 */
				$obj = new DataWepps("s_Blocks");
				$obj->setJoin("inner join s_Panels p on p.Id = t.PanelId inner join s_Directories d on d.Id = p.DirectoryId");
				$res = $obj->getMax("t.DisplayOff=0 and d.Id='{$this->navigator->content['Id']}'");
				$blocks = [];
				foreach($res as $value) {
					$blocks[$value['PanelId']][] = $value;
				}
				foreach ($panels as $value) {
					$b = (!empty($blocks[$value['Id']])) ? $blocks[$value['Id']] : '';
					$smarty->assign('blocks',$b);
					$smarty->assign('panel',$value);
					if (empty($value['Template'])) {
						$this->tpl .= $smarty->fetch('packages/WeppsExtensions/Blocks/Blocks.tpl');
					}  else {
						$extensionClass = "\WeppsExtensions\\Blocks\\{$value['Template']}\\{$value['Template']}Wepps";
						$extension = new $extensionClass($this->navigator,$this->headers);
						$this->tpl .= $extension->tpl;
					}
				}
				
				//UtilsWepps::debugf($this->tpl,1);
				
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		//$smarty->assign('blocks',$smarty->fetch($this->tpl));
		$smarty->assign('blocks',$this->tpl);
		return;
	}
}
?>