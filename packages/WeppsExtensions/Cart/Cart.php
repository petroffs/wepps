<?php
namespace WeppsExtensions\Cart;

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
			case 'order/complete/ea201f29-82a3-4d59-a522-9ccc00af95e5/':
				
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$smarty->assign('normalView',0);
		$this->headers->css("/ext/Cart/Cart.{$this->headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}