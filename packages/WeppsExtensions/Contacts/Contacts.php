<?
namespace PPSExtensions\Contacts;

use PPS\Core\NavigatorPPS;
use PPS\Core\SmartyPPS;
use PPS\Core\DataPPS;
use PPS\Core\ExtensionPPS;
use PPS\Utils\UtilsPPS;
use PPS\Utils\TemplateHeadersPPS;
use PPS\Exception\ExceptionPPS;
use PPS\Connect\ConnectPPS;

class ContactsPPS extends ExtensionPPS {
	public function request() {
		$smarty = SmartyPPS::getSmarty();
		$rand = $this->headers::$rand;
		switch (NavigatorPPS::$pathItem) {
			case '':
				$this->tpl = 'packages/PPSExtensions/Contacts/Contacts.tpl';
				$obj = new DataPPS("Contacts");
				$res = $obj->getMax("t.DisplayOff=0");
				$tmp = array();
				foreach ($res as $value) {
					$keyurl = ($value['KeyUrl']=='') ? 'second' : 'main';
					$tmp[$keyurl][] = $value;
				}
				if (isset($tmp['main'])) $smarty->assign('elementsMain',$tmp['main']);
				if (isset($tmp['second'])) $smarty->assign('elements',$tmp['second']);
				
				//UtilsPPS::debugf($tmp,0);
				
				break;
			default:
				ExceptionPPS::error404();
				break;
		}
		/**
		 * Для глобального шаблона
		 */
		$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$rand}.js");
		$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$rand}.css");
		$apikey = ConnectPPS::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Contacts/Contacts.{$rand}.css");
		$this->headers->js("/ext/Contacts/Contacts.{$rand}.js");
		
		$smarty->assign($this->destinationTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>