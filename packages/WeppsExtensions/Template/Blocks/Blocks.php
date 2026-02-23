<?php
namespace WeppsExtensions\Template\Blocks;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;

class Blocks extends Extension
{
	public function request()
	{
		$smarty = Smarty::getSmarty();
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = "";
				$this->headers->css("/ext/Template/Blocks/Blocks.{$this->rand}.css");
				$this->headers->js("/ext/Template/Blocks/Blocks.{$this->rand}.js");
				$obj = new Data("s_Panels");
				$obj->setJoin("left join s_Panels s on s.Id = t.SourcePandelId");
				$obj->setConcat("t.Id as OriginId, IF(t.Name != '', t.Name, s.Name) as Name, IF(t.Descr != '', t.Descr, s.Descr) as Descr, IF(t.Template != '', t.Template, s.Template) as Template, IF(t.LayoutCSS != '', t.LayoutCSS, s.LayoutCSS) as LayoutCSS, IF(t.SourcePandelId > 0, t.SourcePandelId, t.Id) as Id");
				$panels = $obj->fetch("t.IsHidden=0 and t.NavigatorId='{$this->navigator->content['Id']}'");
				if (empty($panels)) {
					return;
				}
				$panelsId = array_column($panels, 'Id');
				$panelsParams = Connect::$instance->in($panelsId);
				$obj = new Data("s_Blocks");
				$obj->setParams($panelsId);
				$res = $obj->fetch("t.IsHidden=0 and t.PanelId in (" . $panelsParams . ")",1500);
				if (empty($res)) {
					return;
				}
				$blocks = [];
				foreach ($res as $value) {
					$blocks[$value['PanelId']][] = $value;
				}
				foreach ($panels as $value) {
					$b = (!empty($blocks[$value['Id']])) ? $blocks[$value['Id']] : '';
					$smarty->assign('blocks', $b);
					$smarty->assign('panel', $value);
					$extensionClass = "\WeppsExtensions\\Template\\Blocks\\{$value['Template']}\\{$value['Template']}";
					if (!class_exists($extensionClass)) {
						$value['Template'] = '';
					}
					if (empty($value['Template'])) {
						$this->tpl .= $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Blocks.tpl');
					} else {
						$extension = new $extensionClass($this->navigator, $this->headers);
						$this->tpl .= $extension->tpl;
					}
				}
				break;
			default:
				Exception::error404();
				break;
		}
		$smarty->assign('normalView', 0);
		$smarty->assign('blocks', $this->tpl);
		return;
	}
}