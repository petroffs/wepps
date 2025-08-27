<?php
namespace WeppsExtensions\Template\Blocks;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Blocks extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = "";
				$this->headers->css("/ext/Template/Blocks/Blocks.{$this->rand}.css");
				$this->headers->js("/ext/Template/Blocks/Blocks.{$this->rand}.js");
				$obj = new Data("s_Panels");
				$panels = $obj->fetch("t.DisplayOff=0 and t.NavigatorId='{$this->navigator->content['Id']}'");
				if (empty($panels)) {
					return;
				}

				$obj = new Data("s_Blocks");
				$obj->setJoin("join s_Panels p on p.Id = t.PanelId inner join s_Navigator d on d.Id = p.NavigatorId");
				$res = $obj->fetch("t.DisplayOff=0 and d.Id='{$this->navigator->content['Id']}'");
				
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
				Exception::error404();
				break;
		}
		$smarty->assign('blocks',$this->tpl);
		return;
	}
}