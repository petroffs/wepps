<?php
namespace WeppsExtensions\Template\Blocks\AccordionPanel;

use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\SmartyWepps;

class AccordionPanelWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/AccordionPanel/AccordionPanel.tpl');
		$this->headers->css("/ext/Template/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.js");
	}
}