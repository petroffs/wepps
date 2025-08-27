<?php
namespace WeppsExtensions\Template\Blocks\Example;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class Example extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Example/Example.tpl');
		$this->headers->css("/ext/Template/Blocks/Example/Example.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Example/Example.{$this->rand}.js");
	}
}