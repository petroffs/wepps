<?php
namespace WeppsExtensions\Cart;

use WeppsCore\Core\SmartyWepps;
use WeppsCore\Utils\RequestWepps;
use WeppsCore\Exception\ExceptionWepps;
use WeppsCore\Utils\TemplateHeadersWepps;
use WeppsCore\Utils\UtilsWepps;
use WeppsExtensions\Cart\Delivery\DeliveryUtilsWepps;
use WeppsExtensions\Cart\Payments\PaymentsUtilsWepps;
use WeppsExtensions\Products\ProductsUtilsWepps;

require_once '../../../config.php';
require_once '../../../autoloader.php';
require_once '../../../configloader.php';

class RequestCartWepps extends RequestWepps
{
	public function request($action = "")
	{
		$cartUtils = new CartUtilsWepps();
		switch ($action) {
			case 'add':
				self::add($cartUtils);
				break;
			case 'variations':
				self::displayVariations($cartUtils);
				break;
			case 'edit':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
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
					ExceptionWepps::error(400);
				}
				$cartUtils->remove((int) $this->get['id']);
				self::displayCart($cartUtils);
				break;
			case 'removeCart':
				$cartUtils->removeCart();
				break;
			case 'favorites':
				if (empty($this->get['id'])) {
					ExceptionWepps::error(400);
				}
				$cartUtils->setFavorites($this->get['id']);
				break;
			case 'cities':
				$deliveryUtils = new DeliveryUtilsWepps();
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
				$deliveryUtils = new DeliveryUtilsWepps();
				$cartUtils->setCartSummary();
				$delivery = $deliveryUtils->getTariffsByCitiesId($this->get['citiesId'], $cartUtils);
				if (!empty($delivery)) {
					$cartUtils->setCartCitiesId($this->get['citiesId']);
					self::displayCheckoutCart($cartUtils);
				}
				break;
			case 'deliveryOperations':
				$deliveryUtils = new DeliveryUtilsWepps();
				$cartUtils->setCartSummary();
				$deliveryUtils->setOperations($this->get, $cartUtils);
				break;
			case "payments":
				if (empty($this->get["deliveryId"])) {
					http_response_code(404);
					exit();
				}
				$paymentsUtils = new PaymentsUtilsWepps();
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
				ExceptionWepps::error404();
				break;
		}
	}
	private function add(CartUtilsWepps $cartUtils)
	{
		if (empty($this->get['id']) || !is_numeric($this->get['id'])) {
			ExceptionWepps::error(400);
		}
		$this->tpl = 'RequestAddCart.tpl';
		$productsUtils = new ProductsUtilsWepps();
		$element = $productsUtils->getProductsItem($this->get['id']);
		if (!empty($this->get['idv'])) {
			#UtilsWepps::debug($this->get,1);
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
				$elementVariationInCart = self::findById($arr, $this->get['id'] . '-' . $value, 'id');
				$quantity = 1;
				$inCart = (int) ($elementVariationInCart['qu'] ?? 0);
				$inStocks = (int) ($elementVariation['Stocks'] ?? 0);
				if ($inCart > 0) {
					$inCart++;
					$quantity = $inCart;
				}
				;

				if ($inCart >= $inStocks) {
					$quantity = $inStocks;
				}
				$cartUtils->add("{$this->get['id']}-{$value}", $quantity);
			}
		} else {
			$cartUtils->add($this->get['id']);
		}
		$cartSummary = $cartUtils->getCartSummary();
		$this->assign('cartSummary', $cartSummary);
		$this->assign('get', $this->get);
	}
	private function displayCart(CartUtilsWepps $cartUtils)
	{
		$this->tpl = 'RequestDefault.tpl';
		$smarty = SmartyWepps::getSmarty();
		$template = new CartTemplatesWepps($smarty, $cartUtils);
		$template->default();
		return;
	}
	private function displayCheckoutCart(CartUtilsWepps $cartUtils)
	{
		$this->tpl = 'RequestCheckout.tpl';
		$smarty = SmartyWepps::getSmarty();
		$template = new CartTemplatesWepps($smarty, $cartUtils);
		$template->checkout();
		return;
	}
	private function displayVariations(CartUtilsWepps $cartUtils)
	{
		$this->tpl = 'RequestVariations.tpl';
		if (empty($this->get['id']) || !is_numeric($this->get['id'])) {
			ExceptionWepps::error(400);
		}
		$productsUtils = new ProductsUtilsWepps();
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
				#UtilsWepps::debug($item,0);
				if ($item[$key] == $id) {
					return $item;
				}
			}
		}
		return [];
	}
}

$request = new RequestCartWepps($_REQUEST);
$smarty->assign('get', $request->get);
$smarty->display($request->tpl);