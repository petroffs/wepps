<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Connect;
use WeppsCore\Navigator;
use WeppsCore\Smarty;
use WeppsCore\Extension;
use WeppsCore\Exception;
use WeppsCore\TemplateHeaders;
use WeppsCore\Utils;

class Cart extends Extension {
	public function request() {
		$smarty = Smarty::getSmarty();
		$cartUtils = new CartUtils();
		$this->tpl = __DIR__ . '/Cart.tpl';
		$this->headers->meta('<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">');
		$this->headers->meta('<meta http-equiv="Pragma" content="no-cache">');
		$this->headers->meta('<meta http-equiv="Expires" content="0">');
		$cartUtils->setHeaders($this->headers);
		switch (Navigator::$pathItem) {
			case '':
				$template = new CartTemplates($smarty,$cartUtils);
				$template->default();
				break;
			case 'checkout':
				$this->extensionData['element'] = 1;
				$headers = new TemplateHeaders();
				$cartUtils->setHeaders($headers); # зачем ?
				$template = new CartTemplates($smarty,$cartUtils);
				$template->checkout();
				break;
			case 'order':
				$this->extensionData['element'] = 1;
				$template = new CartTemplates($smarty,$cartUtils);
				$template->order();
				break;
			case 'notice':
				$this->extensionData['element'] = 1;
				break;
			default:
				/* $this->extensionData['element'] = 1;
				$template = new CartTemplates($smarty,$cartUtils);
				$template->empty(); */
				Exception::error404();
				break;
		}
		$smarty->assign('normalView',0);
		$apikey = Connect::$projectServices['yandexmaps']['apikey'];
		$this->headers->js("https://api-maps.yandex.ru/2.1/?lang=ru_RU&apikey={$apikey}");
		$this->headers->css("/ext/Cart/Cart.{$this->headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}