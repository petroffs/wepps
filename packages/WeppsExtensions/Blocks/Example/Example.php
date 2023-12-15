<?php
namespace WeppsExtensions\Blocks\Example;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\SmartyWepps;

class ExampleWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Blocks/Example/Example.tpl');
		$this->headers->css("/ext/Blocks/Example/Example.{$this->rand}.css");
		$this->headers->js("/ext/Blocks/Example/Example.{$this->rand}.js");
	}
}
?>