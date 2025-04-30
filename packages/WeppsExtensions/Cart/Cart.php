<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsUtilsWepps;

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
				$smarty->assign('cartText',[
						'goodsCount' => TextTransformsWepps::ending2("товар",$cartSummary['quantityActive'])
				]);
				if (!empty($cartSummary['favorites']['items'])) {
					$smarty->assign('cartFavorites',array_column($cartSummary['favorites']['items'],'id'));
				}
				$smarty->assign('cartDefaultTpl',$smarty->fetch('packages/WeppsExtensions/Cart/CartDefault.tpl'));
				break;
			case 'checkout':
				$this->extensionData['element'] = 1;
				if ($cartSummary['quantityActive']==0) {
					$this->navigator->content['Name'] = "Ваша корзина пуста";
					$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
					break;
				}
				$this->tpl = 'packages/WeppsExtensions/Cart/Cart.tpl';
				$smarty->assign('cartSummary',$cartSummary);
				$smarty->assign('cartText',[
						'goodsCount' => TextTransformsWepps::ending2("товар",$cartSummary['quantityActive'])
				]);
				$deliveryUtils = new DeliveryUtilsWepps();
				$paymentsUtils = new PaymentsUtilsWepps();

				if (!empty($cartSummary['delivery']['citiesId'])) {
					$cartCity = $deliveryUtils->getCitiesById($cartSummary['delivery']['citiesId']);
					if (!empty($cartCity[0]['Id'])) {
						$deliveryActive = "0";
						$payments = [];
						$paymentsActive = "0";
						$delivery = $deliveryUtils->getDeliveryTariffsByCitiesId($cartCity[0]['Id']);
						if (!empty($cartSummary['delivery']['deliveryId'])) {
							$deliveryActive = (string) $cartSummary['delivery']['deliveryId'];
							$payments = $paymentsUtils->getPaymentsByDeliveryId($deliveryActive);
							if (!empty($cartSummary['payments']['paymentsId'])) {
								$paymentsActive = $cartSummary['payments']['paymentsId'];
							}
						}
						$smarty->assign('cartCity',$cartCity[0]);
						$smarty->assign('delivery',$delivery);
						$smarty->assign('deliveryActive',$deliveryActive);
						$smarty->assign('payments',$payments);
						$smarty->assign('paymentsActive',$paymentsActive);
					}
				}
				#UtilsWepps::debug($paymentsActive,1);
				#$payments = $paymentsUtils->getPayments($cartSummary[''][
				$smarty->assign('cartDefaultTpl',$smarty->fetch('packages/WeppsExtensions/Cart/CartCheckout.tpl'));
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