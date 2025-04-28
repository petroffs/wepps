<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;

class CartWepps extends ExtensionWepps {
	public function request() {
		$smarty = SmartyWepps::getSmarty();
		$cartUtils = new CartUtilsWepps();
		$cartSummary = $cartUtils->getCartSummary();
		#UtilsWepps::debug($cartSummary,1);
		switch (NavigatorWepps::$pathItem) {
			case '':
				if ($cartSummary['quantity']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
					break;
				}
				$this->tpl = 'packages/WeppsExtensions/Cart/Cart.tpl';
				$smarty->assign('cartSummary',$cartSummary);
				#UtilsWepps::debug($cartSummary,1);
				$smarty->assign('cartText',[
						'goodsCount' => TextTransformsWepps::ending2("товар",$cartSummary['quantityActive'])
				]);
				if (!empty($cartSummary['favorites']['items'])) {
					$smarty->assign('cartFavorites',array_column($cartSummary['favorites']['items'],'id'));
				}
				$smarty->assign('cartCheckoutTpl',$smarty->fetch('packages/WeppsExtensions/Cart/CartCheckout.tpl'));
				break;
			case 'settings':
				$this->extensionData['element'] = 1;
				if ($cartSummary['quantityActive']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
					break;
				}
				$smarty->assign('cartSummary',$cartSummary);
				$smarty->assign('cartText',[
						'goodsCount' => TextTransformsWepps::ending2("товар",$cartSummary['quantityActive'])
				]);
				$this->tpl = 'packages/WeppsExtensions/Cart/CartSettings.tpl';
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
		$this->headers->js("/ext/Cart/CartCheckout.{$this->headers::$rand}.js");
		$smarty->assign($this->targetTpl,$smarty->fetch($this->tpl));
		return;
	}
}