<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Connect\ConnectWepps;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;

class CartWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$cartUtils = new CartUtilsWepps();
		$this->tpl = 'packages/WeppsExtensions/Cart/Cart.tpl';
		$this->headers->meta('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">');
		$this->headers->meta('<meta http-equiv="Pragma" content="no-cache">');
		$this->headers->meta('<meta http-equiv="Expires" content="0">');
		switch (NavigatorWepps::$pathItem) {
			case '':
				$template = new CartTemplatesWepps($smarty,$cartUtils);
				$template->default();
				break;
			case 'checkout':
				$this->extensionData['element'] = 1;
				$headers = new TemplateHeadersWepps();
				$cartUtils->setHeaders($headers);
				$template = new CartTemplatesWepps($smarty,$cartUtils);
				$template->checkout();
				break;
			case 'order':
				$this->extensionData['element'] = 1;
				$template = new CartTemplatesWepps($smarty,$cartUtils);
				$template->order();
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$smarty->assign('normalView',0);
		$apikey = ConnectWepps::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Cart/Cart.{$this->headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}