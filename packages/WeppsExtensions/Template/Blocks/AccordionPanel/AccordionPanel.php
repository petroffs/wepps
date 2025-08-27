<?php
namespace WeppsExtensions\Template\Blocks\AccordionPanel;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class AccordionPanel extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/AccordionPanel/AccordionPanel.tpl');
		$this->headers->css("/ext/Template/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.js");
	}
}