<?php
namespace WeppsExtensions\Template\Blocks\Hero;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class Hero extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Hero/Hero.tpl');
		$this->headers->css("/ext/Template/Blocks/Hero/Hero.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Hero/Hero.{$this->rand}.js");
	}
}