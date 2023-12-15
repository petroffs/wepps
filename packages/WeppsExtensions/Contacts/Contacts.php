<?php
namespace WeppsExtensions\Contacts;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;

class ContactsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Contacts/Contacts.tpl';
				$obj = new DataWepps("Contacts");
				$res = $obj->getMax("t.DisplayOff=0");
				$tmp = array();
				foreach ($res as $value) {
					$keyurl = ($value['Alias']=='') ? 'second' : 'main';
					$tmp[$keyurl][] = $value;
				}
				if (isset($tmp['main'])) $smarty->assign('elementsMain',$tmp['main']);
				if (isset($tmp['second'])) $smarty->assign('elements',$tmp['second']);
				
				//UtilsWepps::debugf($tmp,0);
				
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		
		/*
		 * Для глобального шаблона
		 */
		$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.js");
		$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.css");
		$apikey = ConnectWepps::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Contacts/Contacts.{$this->rand}.css");
		$this->headers->js("/ext/Contacts/Contacts.{$this->rand}.js");
		
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}
?>