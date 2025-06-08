<?php
namespace WeppsExtensions\Contacts;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\DataWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Utils\UtilsWepps;

class ContactsWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		switch (NavigatorWepps::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Contacts/Contacts.tpl';
				$obj = new DataWepps("Contacts");
				$res = $obj->fetch("t.DisplayOff=0");
				#UtilsWepps::debug($res,1);
				$smarty->assign('elements',$res);
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