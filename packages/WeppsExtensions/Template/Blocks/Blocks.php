<?php
namespace WeppsExtensions\Template\Blocks;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;

class BlocksWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = "";

				/*
				 * Переменные для глобального шаблона
				 */
				$this->headers->css("/ext/Template/Blocks/Blocks.{$this->rand}.css");
				$this->headers->js("/ext/Template/Blocks/Blocks.{$this->rand}.js");
				$obj = new DataWepps("s_Panels");
				$panels = $obj->getMax("t.DisplayOff=0 and t.NavigatorId='{$this->navigator->content['Id']}'");
				if (empty($panels)) {
					return;
				}

				/*
				 * Подключить шаблоны
				 */
				$obj = new DataWepps("s_Blocks");
				$obj->setJoin("inner join s_Panels p on p.Id = t.PanelId inner join s_Navigator d on d.Id = p.NavigatorId");
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
						$this->tpl .= $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Blocks.tpl');
					}  else {
						$extensionClass = "\WeppsExtensions\\Template\\Blocks\\{$value['Template']}\\{$value['Template']}Wepps";
						$extension = new $extensionClass($this->navigator,$this->headers);
						$this->tpl .= $extension->tpl;
					}
				}
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$smarty->assign('blocks',$this->tpl);
		return;
	}
}
?>