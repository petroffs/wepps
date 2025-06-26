<?php
namespace WeppsExtensions\Cart;

use Smarty;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\TextTransforms\TextTransformsWepps;
use WeppsCore\Utils\UtilsWepps;

class CartTemplatesWepps
{
	private $smarty;
	private $cartUtils;
	private $cartSummary;
	private $headers;
	public function __construct(Smarty\Smarty $smarty, CartUtilsWepps $cartUtils)
	{
		$this->smarty = &$smarty;
		$this->cartUtils = &$cartUtils;
		$this->headers = $this->cartUtils->getHeaders();
		$this->cartUtils->setCartSummary();
		$this->cartSummary = $this->cartUtils->getCartSummary();
	}
	public function default(): void
	{
		if ($this->cartSummary['quantity'] == 0) {
			$this->empty();
			return;
		}
		$this->smarty->assign('cartSummary', $this->cartSummary);
		$this->smarty->assign('cartText', [
			'goodsCount' => TextTransformsWepps::ending2("товар", $this->cartSummary['quantityActive'])
		]);
		if (!empty($this->cartSummary['favorites']['items'])) {
			$this->smarty->assign('cartFavorites', array_column($this->cartSummary['favorites']['items'], 'id'));
		}
		$this->smarty->assign('cartDefaultTpl', $this->smarty->fetch(__DIR__ . '/CartDefault.tpl'));
	}
	public function checkout(): void
	{
		if ($this->cartSummary['quantityActive'] == 0) {
			$this->empty();
			return;
		}
		$this->smarty->assign('cartSummary', $this->cartSummary);
		$this->smarty->assign('cartText', [
			'goodsCount' => TextTransformsWepps::ending2("товар", $this->cartSummary['quantityActive'])
		]);
		$checkout = $this->cartUtils->getCheckoutData();
		if (!empty($checkout['deliveryOperations']['tpl'])) {
			$this->smarty->assign('deliveryMinify', $this->cartUtils->getHeaders()->get()['cssjs']);
			$this->smarty->assign('deliveryOperations', $checkout['deliveryOperations']);
			$this->smarty->assign('deliveryOperationsTpl', $this->smarty->fetch(__DIR__ . '/Delivery/' . $checkout['deliveryOperations']['tpl']));
		}
		$this->smarty->assign('cartCity', $checkout['city']);
		$this->smarty->assign('delivery', $checkout['delivery']);
		$this->smarty->assign('deliveryActive', $checkout['deliveryActive']);
		$this->smarty->assign('payments', $checkout['payments']);
		$this->smarty->assign('paymentsActive', $checkout['paymentsActive']);
		$this->smarty->assign('cartDefaultTpl', $this->smarty->fetch(__DIR__ . '/CartCheckout.tpl'));
		return;
	}
	public function order(): void
	{
		$order = $this->cartUtils->getOrderByGuid(@$_GET['id']);
		if (empty($order) || $order['OStatus']!=1) {
			ExceptionWepps::error404();
		}
		$className = "\WeppsExtensions\\Cart\\Payments\\{$order['PaymentsExt']}";
		/**
		 * @var \WeppsExtensions\Cart\Payments\PaymentsWepps $class
		 */
        $class = new $className([],$this->cartUtils);
		$result = $class->getOperations($order);
		if (!empty($result['tpl'])) {
			$this->smarty->assign('operationsData',$result);
			$this->smarty->assign('operationsTpl', $this->smarty->fetch(__DIR__ . '/Payments/' . $result['tpl']));
		}

		$this->smarty->assign('order',$order);
		$this->headers->css("/ext/Cart/CartEmpty.{$this->headers::$rand}.css");
		$this->smarty->assign('cartDefaultTpl', $this->smarty->fetch(__DIR__ . '/CartOrder.tpl'));
		return;
	}
	public function empty(): void
	{
		$this->headers->css("/ext/Cart/CartEmpty.{$this->headers::$rand}.css");
		$this->smarty->assign('cartDefaultTpl', $this->smarty->fetch(__DIR__ . '/CartEmpty.tpl'));
		return;
	}
}