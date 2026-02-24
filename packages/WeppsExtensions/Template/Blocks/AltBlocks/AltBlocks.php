<?php
namespace WeppsExtensions\Template\Blocks\AltBlocks;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class AltBlocks extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/AltBlocks/AltBlocks.tpl');
		$this->headers->css("/ext/Template/Blocks/AltBlocks/AltBlocks.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/AltBlocks/AltBlocks.{$this->rand}.js");
	}
}