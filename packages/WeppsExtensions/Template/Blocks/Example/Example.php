<?php
namespace WeppsExtensions\Template\Blocks\Example;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\SmartyWepps;

class ExampleWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Template/Blocks/Example/Example.tpl');
		$this->headers->css("/ext/Template/Blocks/Example/Example.{$this->rand}.css");
		$this->headers->js("/ext/Template/Blocks/Example/Example.{$this->rand}.js");
	}
}
?>