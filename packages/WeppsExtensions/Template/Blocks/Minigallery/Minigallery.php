<?php
namespace WeppsExtensions\Template\Blocks\Minigallery;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class Minigallery extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Minigallery/Minigallery.tpl');
		$this->headers->css("/ext/Template/Blocks/Minigallery/Minigallery.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Minigallery/Minigallery.{$this->rand}.js");
	}
}