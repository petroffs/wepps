<?php
namespace WeppsExtensions\Contacts;

use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Data;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\Connect;
use WeppsCore\Utils;

if (!session_id()) {
	session_start();
}

class Contacts extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		#Utils::debug($_SESSION,21);
		switch (Navigator::$pathItem) {
			case '':
				$this->tpl = 'packages/WeppsExtensions/Contacts/Contacts.tpl';
				$obj = new Data("Contacts");
				$res = $obj->fetch("t.DisplayOff=0");
				#Utils::debug($res,1);
				$smarty->assign('elements',$res);
				if (isset($_SESSION['uploads']['feedback-form'])) {
					$smarty->assign('uploaded', $_SESSION['uploads']['feedback-form']);
				}
				#unset($_SESSION['uploads']);
				#feedback-upload
				#Utils::debug($_SESSION['uploads']['feedback-form'],1);
				break;
			default:
				Exception::error404();
				break;
		}
		#Utils::debug($_SESSION['uploads'],21);
		$this->headers->js("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.js");
		$this->headers->css("/ext/Addons/YandexMaps/YandexMaps.{$this->rand}.css");
		$apikey = Connect::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Contacts/Contacts.{$this->rand}.css");
		$this->headers->js("/ext/Contacts/Contacts.{$this->rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}