<?
namespace WeppsExtensions\Blocks\Example;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\SmartyWepps;

class ExampleWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$rand = $this->headers::$rand;
		
		
		
		$this->headers->css("/ext/Blocks/Example/Example.{$rand}.css");
		$this->headers->js("/ext/Blocks/Example/Example.{$rand}.js");
	}
}
?>