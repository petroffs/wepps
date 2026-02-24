<?php
namespace WeppsExtensions\Template\Blocks\Tabs;

use WeppsCore\Extension;
use WeppsCore\Smarty;

class Tabs extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Tabs/Tabs.tpl');
		$this->headers->css("/ext/Template/Blocks/Tabs/Tabs.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Tabs/Tabs.{$this->rand}.js");
	}
}