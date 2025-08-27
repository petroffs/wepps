<?php
require_once '../../../configloader.php';

use WeppsCore\Smarty;
use WeppsCore\Request;
use WeppsCore\Exception;
use WeppsExtensions\Cart\CartTemplates;
use WeppsExtensions\Cart\CartUtils;
use WeppsExtensions\Cart\Delivery\DeliveryUtils;
use WeppsExtensions\Cart\Payments\PaymentsUtils;
use WeppsExtensions\Products\ProductsUtils;

class RequestCart extends Request
{
	public function request($action = "")
	{
		$cartUtils = new CartUtils();
		switch ($action) {
			case 'add':
				self::add($cartUtils);
				break;
			case 'variations':
				self::displayVariations($cartUtils);
				break;
			case 'edit':
				if (empty($this->get['id'])) {
					Exception::error(400);
				}
				if (empty($this->get['quantity']) || !is_numeric($this->get['quantity'])) {
					$this->get['quantity'] = 1;
				}
				$cartUtils->edit($this->get['id'], $this->get['quantity']);
				self::displayCart($cartUtils);
				break;
			case 'check':
				$cartUtils->check(@$this->get['id']);
				self::displayCart($cartUtils);
				break;
			case 'remove':
				if (empty($this->get['id'])) {
					Exception::error(400);
				}
				$cartUtils->remove($this->get['id']);
				self::displayCart($cartUtils);
				break;
			case 'removeCart':
				$cartUtils->removeCart();
				break;
			case 'favorites':
				if (empty($this->get['id'])) {
					Exception::error(400);
				}
				$cartUtils->setFavorites($this->get['id']);
				break;
			case 'cities':
				$deliveryUtils = new DeliveryUtils();
				$res = $deliveryUtils->getCitiesByQuery($this->get['text'], (int) $this->get['page'] ?? 1);
				if (empty($res)) {
					echo json_encode([
						'hasMore' => false
					]);
					break;
				}
				$html = '';
				foreach ($res as $row) {
					$html .= '<div class="w_suggestions-item" data-id="' . $row['Id'] . '"><div>' . htmlspecialchars($row['Title']) . '</div></div>';
				}
				echo json_encode([
					'html' => $html,
					'hasMore' => true
				]);
				break;
			case "delivery":
				if (empty($this->get["citiesId"])) {
					http_response_code(404);
					exit();
				}
				$deliveryUtils = new DeliveryUtils();
				$cartUtils->setCartSummary();
				$delivery = $deliveryUtils->getTariffsByCitiesId($this->get['citiesId'], $cartUtils);
				if (!empty($delivery)) {
					$cartUtils->setCartCitiesId($this->get['citiesId']);
					self::displayCheckoutCart($cartUtils);
				}
				break;
			case 'deliveryOperations':
				$deliveryUtils = new DeliveryUtils();
				$cartUtils->setCartSummary();
				$deliveryUtils->setOperations($this->get, $cartUtils);
				break;
			case "payments":
				if (empty($this->get["deliveryId"])) {
					http_response_code(404);
					exit();
				}
				$paymentsUtils = new PaymentsUtils();
				$payments = $paymentsUtils->getByDeliveryId($this->get['deliveryId'], $cartUtils);
				if (!empty($payments)) {
					$cartUtils->setCartDelivery($this->get['deliveryId']);
				}
				self::displayCheckoutCart($cartUtils);
				break;
			case "shipping":
				if (empty($this->get["paymentsId"])) {
					http_response_code(404);
					exit();
				}
				$cartUtils->setCartPayments($this->get['paymentsId']);
				self::displayCheckoutCart($cartUtils);
				break;
			case 'addOrder':
				$response = $cartUtils->addOrder($this->get);
				echo $response['html'];
				exit();
			case 'copyOrder':
				break;
			default:
				Exception::error404();
				break;
		}
	}
	private function add(CartUtils $cartUtils)
	{
		if (empty($this->get['id']) || !is_numeric($this->get['id'])) {
			Exception::error(400);
		}
		$this->tpl = 'RequestAddCart.tpl';
		$productsUtils = new ProductsUtils();
		$element = $productsUtils->getProductsItem($this->get['id']);
		if (!empty($this->get['idv'])) {
			$ex = explode(',', $this->get['idv']);
			foreach ($ex as $value) {
				if (!is_numeric($value)) {
					continue;
				}
				$elementVariation = self::findById($element['W_Variations'], $value);
				if (empty($elementVariation)) {
					continue;
				}
				$arr = [
					'items' => $cartUtils->getCart()['items']
				];
				$quantity = 1;
				/* $elementVariationInCart = self::findById($arr, $this->get['id'] . '-' . $value, 'id');
				$inCart = (int) ($elementVariationInCart['qu'] ?? 0);
				$inStocks = (int) ($elementVariation['Stocks'] ?? 0);
				if ($inCart > 0) {
					$inCart++;
					$quantity = $inCart;
				}
				if ($inCart >= $inStocks) {
					$quantity = $inStocks;
				} */
				$cartUtils->add("{$this->get['id']}-{$value}", $quantity);
			}
		} else {
			$cartUtils->add($this->get['id']);
		}
		$cartSummary = $cartUtils->getCartSummary();
		$this->assign('cartSummary', $cartSummary);
		$this->assign('get', $this->get);
	}
	private function displayCart(CartUtils $cartUtils)
	{
		$this->tpl = 'RequestDefault.tpl';
		$smarty = Smarty::getSmarty();
		$template = new CartTemplates($smarty, $cartUtils);
		$template->default();
		return;
	}
	private function displayCheckoutCart(CartUtils $cartUtils)
	{
		$this->tpl = 'RequestCheckout.tpl';
		$smarty = Smarty::getSmarty();
		$template = new CartTemplates($smarty, $cartUtils);
		$template->checkout();
		return;
	}
	private function displayVariations(CartUtils $cartUtils)
	{
		$this->tpl = 'RequestVariations.tpl';
		if (empty($this->get['id']) || !is_numeric($this->get['id'])) {
			Exception::error(400);
		}
		$productsUtils = new ProductsUtils();
		$el = $productsUtils->getProductsItem($this->get['id']);
		$cartMetrics = $cartUtils->getCartMetrics();
		$this->assign('cartMetrics', $cartMetrics);
		$this->assign('element', $el);
		return;
	}
	private function findById(array $array, $id, string $key = 'Id')
	{
		foreach ($array as $group) {
			foreach ($group as $item) {
				if ($item[$key] == $id) {
					return $item;
				}
			}
		}
		return [];
	}
}
$request = new RequestCart($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);