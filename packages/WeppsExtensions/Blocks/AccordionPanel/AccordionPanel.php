<?
namespace WeppsExtensions\Blocks\AccordionPanel;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Core\SmartyWepps;

class AccordionPanelWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$this->tpl = $smarty->fetch('packages/WeppsExtensions/Blocks/AccordionPanel/AccordionPanel.tpl');
		$this->headers->css("/ext/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.css");
		$this->headers->js("/ext/Blocks/AccordionPanel/AccordionPanel.{$this->rand}.js");
	}
}
?>