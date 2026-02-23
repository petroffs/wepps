<?php
namespace WeppsExtensions\Template\Blocks\Accordion;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class Accordion extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Accordion/Accordion.tpl');
		$this->headers->css("/ext/Template/Blocks/Accordion/Accordion.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Accordion/Accordion.{$this->rand}.js");
	}
}