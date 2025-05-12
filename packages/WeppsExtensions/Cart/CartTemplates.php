<?php
namespace WeppsExtensions\Cart;

use Smarty;
use WeppsCore\Core\NavigatorWepps;
use WeppsCore\Core\SmartyWepps;
use WeppsCore\Core\ExtensionWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsUtilsWepps;

class CartTemplatesWepps {
    private $smarty;
    private $cartUtils;
    private $cartSummary;
    public function __construct(Smarty\Smarty $smarty,CartUtilsWepps $cartUtils) {
        $this->smarty = &$smarty;
        $this->cartUtils = &$cartUtils;
		$this->cartUtils->setCartSummary();
		$this->cartSummary = $this->cartUtils->getCartSummary();
    }
    public function default() : void {
        $this->cartUtils->setCartSummary();
        if ($this->cartSummary['quantity']==0) {
            #$this->navigator->content['Name'] = "Ваша корзина пуста";
            #$this->tpl = 'packages/WeppsExtensions/Cart/CartEmpty.tpl';
            $this->smarty->assign('cartDefaultTpl',$this->smarty->fetch(__DIR__ .'/CartEmpty.tpl'));
            return;
        }
        $this->smarty->assign('cartSummary',$this->cartSummary);
        $this->smarty->assign('cartText',[
                'goodsCount' => TextTransformsWepps::ending2("товар",$this->cartSummary['quantityActive'])
        ]);
        if (!empty($this->cartSummary['favorites']['items'])) {
            $this->smarty->assign('cartFavorites',array_column($this->cartSummary['favorites']['items'],'id'));
        }
        $this->smarty->assign('cartDefaultTpl',$this->smarty->fetch(__DIR__ . '/CartDefault.tpl'));
    }
    public function checkout() : void {
        if ($this->cartSummary['quantityActive']==0) {
            #$this->navigator->content['Name'] = "Ваша корзина пуста";
            $this->smarty->assign('cartDefaultTpl',$this->smarty->fetch(__DIR__ .'/CartEmpty.tpl'));
            return;
        }
        #UtilsWepps::debug($this->cartSummary,1);
        $this->smarty->assign('cartSummary',$this->cartSummary);
        $this->smarty->assign('cartText',[
                'goodsCount' => TextTransformsWepps::ending2("товар",$this->cartSummary['quantityActive'])
        ]);
        $checkout = $this->cartUtils->getCheckoutData();
        $this->smarty->assign('cartCity',$checkout['city']);
        $this->smarty->assign('delivery',$checkout['delivery']);
        $this->smarty->assign('deliveryActive',$checkout['deliveryActive']);
        $this->smarty->assign('payments',$checkout['payments']);
        $this->smarty->assign('paymentsActive',$checkout['paymentsActive']);
        $this->smarty->assign('cartDefaultTpl',$this->smarty->fetch(__DIR__ . '/CartCheckout.tpl'));
        return;
    }
}