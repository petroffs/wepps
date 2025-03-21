<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;

class CartWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$cartUtils = new CartUtilsWepps();
		$cartSummary = $cartUtils->getCartSummary();
		switch (NavigatorWepps::$pathItem) {
			case '':
				if ($cartSummary['quantity']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
					break;
				}
				$this->tpl = 'packages/WeppsExtensions/Cart/Cart.tpl';
				$smarty->assign('cartSummary',$cartSummary);
				$smarty->assign('cartTotalTpl',$smarty->fetch('packages/WeppsExtensions/Cart/CartTotal.tpl'));
				break;
			case 'order':
				
				break;
			default:
				ExceptionWepps::error404();
				break;
		}
		$this->headers->css("/ext/Cart/Cart.{$this->headers::$rand}.css");
		$this->headers->js("/ext/Cart/Cart.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}